<?php
// app/admin/controllers/adminController.php

require_once __DIR__ . '/../../models/ReportModel.php';
require_once __DIR__ . '/../../models/UserModel.php'; 

class AdminController {

    private $conn;
    private $reportModel;
    private $userModel;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $this->reportModel = new ReportModel($this->conn);
        $this->userModel = new UserModel($this->conn);
    }

    // --- HELPER FUNCTIONS ---
    // (Pake punya kamu karena lebih robust)
    private function fixAvatarPath($url) {
        if (empty($url)) {
            return '/sinergi/public/assets/images/user.png'; 
        }
        if (strpos($url, '/') !== false) {
            return $url;
        }
        return '/sinergi/public/uploads/avatars/' . $url;
    }

    private function checkAdminAccess() {
        if (!isset($_SESSION['user_id']) || ($_SESSION['role_id'] ?? 0) != 5) {
            $isApi = isset($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_GET['page']) && strpos($_GET['page'], 'api') !== false);
            
            if ($isApi) {
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized Access']);
                exit;
            } else {
                header('Location: /sinergi/index.php?page=login');
                exit;               
            }
        }
    }

    // --- VIEW PAGES ---
    public function dashboard() {
        $this->checkAdminAccess();
        include __DIR__ . '/../views/dashboard.php';
    }

    public function analytics() {
        $this->checkAdminAccess();
        include __DIR__ . '/../views/analytics.php';
    }

    public function detailReport() {
        $this->checkAdminAccess();
        $id = $_GET['id'] ?? 0;
        
        if ($id) {
            $report = $this->reportModel->getReportById($id);
            if (!$report) {
                echo "<script>alert('Laporan tidak ditemukan'); window.location='/sinergi/index.php?page=admin-dashboard';</script>";
                exit;
            }

            // Fix Path Gambar
            $report['REPORTER_AVATAR'] = $this->fixAvatarPath($report['REPORTER_AVATAR'] ?? '');
            $report['POST_OWNER_AVATAR'] = $this->fixAvatarPath($report['POST_OWNER_AVATAR'] ?? '');
            
            if (!empty($report['POST_IMAGE']) && strpos($report['POST_IMAGE'], '/') === false) {
                 $report['POST_IMAGE'] = '/sinergi/public/assets/uploads/' . $report['POST_IMAGE'];
            }

            include __DIR__ . '/../views/detail_report.php';
        } else {
            header('Location: /sinergi/index.php?page=admin-dashboard');
            exit;
        }
    }

    public function profile() {
        $this->checkAdminAccess();
        include __DIR__ . '/../views/profile.php';
    }

    // --- API HANDLERS ---

    public function apiGetReports() {
        $this->checkAdminAccess();
        if (ob_get_length()) ob_clean(); 
        header('Content-Type: application/json');

        try {
            $status = $_GET['status'] ?? null;
            if ($status === '') $status = null;
            
            $page = max(1, (int)($_GET['p'] ?? 1));
            $limit = 5; 

            $total = $this->reportModel->countAllReports($status);
            $reports = $this->reportModel->getAllReports($status, $page, $limit);
            $stats = $this->reportModel->getReportStats();

            $data = [];
            foreach ($reports as $r) {
                $data[] = [
                    'REPORT_ID' => $r['REPORT_ID'],
                    'POST_ID' => $r['POST_ID'],
                    'REPORTER_NAMA' => $r['REPORTER_NAMA'] ?? 'User',
                    'REPORTER_USERNAME' => $r['REPORTER_USERNAME'] ?? 'unknown',
                    'REPORTER_AVATAR_FIXED' => $this->fixAvatarPath($r['REPORTER_AVATAR']),
                    'TANGGAL_FORMAT' => $r['CREATED_AT_STR'],
                    'STATUS' => $r['STATUS'],
                    'REASON' => $r['REASON'],
                    'POST_OWNER_ID' => $r['POST_OWNER_ID'], 
                    'POST_OWNER_USERNAME' => $r['POST_OWNER_USERNAME'],
                    'IS_OWNER_BANNED' => ($r['OWNER_BANNED_STATUS'] == 1)
                ];
            }

            echo json_encode([
                'status' => 'success', 
                'stats' => $stats, 
                'data' => $data, 
                'pagination' => [
                    'current_page' => $page, 
                    'total_pages' => ceil($total/$limit), 
                    'total_records' => $total
                ]
            ], JSON_UNESCAPED_SLASHES);

        } catch (Exception $e) { 
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]); 
        }
        exit;
    }

    public function apiProcessReport() {
        $this->checkAdminAccess();
        if (ob_get_length()) ob_clean(); 
        header('Content-Type: application/json');

        $aid = $_SESSION['user_id']; 
        $act = $_POST['action'] ?? ''; 
        $rid = $_POST['report_id'] ?? 0;
        
        try {
            $res = ['status' => false, 'message' => 'Invalid Request'];

            if ($act == 'delete_post') {
                // ReportModel->deleteReportedPost() already handles:
                // 1. Deleting post + comments + likes + notifications
                // 2. Auto-marking report as 'resolved' (Step 5)
                $res = $this->reportModel->deleteReportedPost($rid, $aid);
            } 
            elseif ($act == 'ban_user') {
                $uid = $_POST['user_id'] ?? 0;
                // Menggunakan function banUser yang sudah diupdate (Butuh 3 parameter: uid, report_id, admin_id)
                $res = $this->reportModel->banUser($uid, $rid, $aid);
            } 
            elseif (in_array($act, ['mark_resolved', 'mark_rejected'])) {
                $st = ($act == 'mark_resolved') ? 'resolved' : 'rejected';
                $res = $this->reportModel->updateReportStatus($rid, $aid, $st, 'Updated by Admin');
            }

            echo json_encode($res);

        } catch (Exception $e) { 
            echo json_encode(['status' => false, 'message' => $e->getMessage()]); 
        }
        exit;
    }

    // API ANALYTICS (Pake Logika Teman yang support parameter Year)
    public function apiGetChartData() {
        $this->checkAdminAccess();
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $filter = $_GET['filter'] ?? 'user'; 
        $year   = $_GET['year'] ?? date('Y'); // Ambil tahun, default sekarang

        try {
            $label = '';
            $raw = [];

            if ($filter === 'user') {
                $raw = $this->reportModel->getUserGrowthAnalytics($year);
                $label = "User Baru ($year)";
            } elseif ($filter === 'post') {
                $raw = $this->reportModel->getPostGrowthAnalytics($year);
                $label = "Postingan ($year)";
            } elseif ($filter === 'community') { 
                $raw = $this->reportModel->getGroupGrowthAnalytics($year);
                $label = "Group Baru ($year)";
            } elseif ($filter === 'reports') { 
                $raw = $this->reportModel->getReportGrowthAnalytics($year);
                $label = "Laporan Masuk ($year)";
            }

            // Logic: Loop Bulan 1 sampai 12 (Jan - Des)
            // Agar grafik selalu rapi dari kiri ke kanan (Januari -> Desember)
            $finalData = [];
            $finalLabels = [];
            
            for ($m = 1; $m <= 12; $m++) {
                $monthNum = str_pad($m, 2, '0', STR_PAD_LEFT); // '01', '02'...
                $monthName = date('M', mktime(0, 0, 0, $m, 10)); // Jan, Feb...
                
                $value = 0;
                foreach ($raw as $r) {
                    // Mencocokkan dengan alias di query Model (BULAN_ANGKA)
                    if (isset($r['BULAN_ANGKA']) && $r['BULAN_ANGKA'] == $monthNum) {
                        $value = (int)$r['TOTAL'];
                        break;
                    }
                }
                
                $finalLabels[] = $monthName;
                $finalData[] = $value;
            }

            echo json_encode([
                'status' => 'success',
                'label' => $label,
                'labels' => $finalLabels,
                'data' => $finalData
            ]);

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    // --- BLACKLIST PAGE ---
    public function blacklist() {
        $this->checkAdminAccess();
        include __DIR__ . '/../views/blacklist.php';
    }

    // --- API: Get Banned Users ---
    public function apiGetBannedUsers() {
        $this->checkAdminAccess();
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        try {
            $users = $this->reportModel->getBannedUsers();
            
            $data = [];
            foreach ($users as $u) {
                $data[] = [
                    'USER_ID' => $u['USER_ID'],
                    'USERNAME' => $u['USERNAME'],
                    'NAMA_LENGKAP' => $u['NAMA_LENGKAP'],
                    'EMAIL' => $u['EMAIL'],
                    'AVATAR_URL' => $this->fixAvatarPath($u['AVATAR_URL']),
                    'JOINED_DATE' => $u['JOINED_DATE']
                ];
            }

            echo json_encode(['status' => 'success', 'data' => $data, 'total' => count($data)]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    // --- API: Unban User ---
    public function apiUnbanUser() {
        $this->checkAdminAccess();
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $userId = $_POST['user_id'] ?? 0;
        
        if (!$userId) {
            echo json_encode(['status' => false, 'message' => 'User ID tidak valid']);
            exit;
        }

        $result = $this->reportModel->unbanUser($userId);
        echo json_encode($result);
        exit;
    }

    // --- API: Delete Single Resolved Report ---
    public function apiDeleteReport() {
        $this->checkAdminAccess();
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $reportId = $_POST['report_id'] ?? 0;
        
        if (!$reportId) {
            echo json_encode(['status' => false, 'message' => 'Report ID tidak valid']);
            exit;
        }

        $result = $this->reportModel->deleteResolvedReport($reportId);
        echo json_encode($result);
        exit;
    }

    // --- API: Delete All Resolved Reports ---
    public function apiPurgeReports() {
        $this->checkAdminAccess();
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $result = $this->reportModel->deleteAllResolvedReports();
        echo json_encode($result);
        exit;
    }
}
?>