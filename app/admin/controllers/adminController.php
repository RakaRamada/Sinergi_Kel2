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

    // --- [BARU] FUNGSI HELPER PATH AVATAR ---
    private function fixAvatarPath($url) {
        // 1. Jika kosong, pakai default
        if (empty($url)) {
            return '/Sinergi/public/assets/images/user.png'; 
        }
        // 2. Jika sudah mengandung slash '/', berarti itu path lengkap (misal default dari DB)
        if (strpos($url, '/') !== false) {
            return $url;
        }
        // 3. Jika cuma nama file, tambahkan prefix folder upload
        return '/Sinergi/public/uploads/avatars/' . $url;
    }

    private function checkAdminAccess() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 5) {
            $isApi = isset($_SERVER['HTTP_X_REQUESTED_WITH']) || (isset($_GET['page']) && strpos($_GET['page'], 'api') !== false);
            
            if ($isApi) {
                if (ob_get_length()) ob_clean();
                header('Content-Type: application/json');
                echo json_encode(['status' => 'error', 'message' => 'Unauthorized Access']);
                exit;
            } else {
                header('Location: /Sinergi/index.php?page=login');
                exit;               
            }
        }
    }

    // --- PAGES ---
    
    // ... dashboard & analytics tetap sama ...

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
        $reportId = $_GET['id'] ?? 0;
        if ($reportId) {
            $report = $this->reportModel->getReportById($reportId);
            if (!$report) {
                echo "<script>alert('Laporan tidak ditemukan'); window.location='/Sinergi/index.php?page=admin-dashboard';</script>";
                exit;
            }

            // --- [FIX] PERBAIKI PATH GAMBAR SEBELUM DIKIRIM KE VIEW ---
            $report['REPORTER_AVATAR'] = $this->fixAvatarPath($report['REPORTER_AVATAR'] ?? '');
            $report['POST_OWNER_AVATAR'] = $this->fixAvatarPath($report['POST_OWNER_AVATAR'] ?? '');
            
            // Fix juga gambar postingan jika cuma nama file (opsional, jaga-jaga)
            if (!empty($report['POST_IMAGE']) && strpos($report['POST_IMAGE'], '/') === false) {
                 $report['POST_IMAGE'] = '/Sinergi/public/assets/uploads/' . $report['POST_IMAGE'];
            }

            include __DIR__ . '/../views/detail_report.php';
        } else {
            header('Location: /Sinergi/index.php?page=admin-dashboard');
            exit;
        }
    }

    public function profile() {
        $this->checkAdminAccess();
        include __DIR__ . '/../views/profile.php';
    }

    // --- API HANDLERS (JSON) ---

    // 1. API GET DATA LAPORAN (PAGINATION)
    public function apiGetReports() {
        $this->checkAdminAccess();
        
        if (ob_get_length()) ob_clean(); 
        header('Content-Type: application/json');

        try {
            $status = $_GET['status'] ?? null;
            if ($status === '') $status = null;
            
            $page = isset($_GET['p']) ? (int)$_GET['p'] : 1;
            if ($page < 1) $page = 1;
            $limit = 10; 

            $totalRecords = $this->reportModel->countAllReports($status);
            $totalPages = ceil($totalRecords / $limit);

            $reports = $this->reportModel->getAllReports($status, $page, $limit);
            $stats = $this->reportModel->getReportStats();

            $formatted = [];
            foreach ($reports as $r) {
                // --- [FIX] GUNAKAN HELPER DI SINI ---
                $avatar = $this->fixAvatarPath($r['REPORTER_AVATAR']);
                
                $formatted[] = [
                    'REPORT_ID' => $r['REPORT_ID'],
                    'POST_ID' => $r['POST_ID'],
                    'REPORTER_NAMA' => $r['REPORTER_NAMA'] ?? 'User',
                    'REPORTER_USERNAME' => $r['REPORTER_USERNAME'] ?? 'unknown',
                    'REPORTER_AVATAR_FIXED' => $avatar, // Ini yang dipakai frontend
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
                'data' => $formatted,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_records' => $totalRecords,
                    'limit' => $limit
                ]
            ], JSON_UNESCAPED_SLASHES);

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ... sisa method lain (apiGetChartData, apiProcessReport) biarkan tetap sama ...
    public function apiGetChartData() {
        $this->checkAdminAccess();
        
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $filter = $_GET['filter'] ?? 'user'; 

        try {
            $label = '';
            $raw = [];

            // 1. Ambil Data Mentah dari DB
            if ($filter === 'user') {
                $raw = $this->reportModel->getUserGrowthAnalytics();
                $label = 'Pertumbuhan User Baru';
            } elseif ($filter === 'post') {
                $raw = $this->reportModel->getPostGrowthAnalytics();
                $label = 'Jumlah Postingan Baru';
            } elseif ($filter === 'community') { 
                $raw = $this->reportModel->getGroupGrowthAnalytics();
                $label = 'Pertumbuhan Group Baru';
            }

            // 2. LOGIKA "ZERO-FILL": Isi bulan kosong dengan 0
            // Kita buat array 12 bulan terakhir secara manual
            $finalData = [];
            $finalLabels = [];
            
            // Loop 11 bulan lalu sampai bulan ini
            for ($i = 11; $i >= 0; $i--) {
                $monthKey = date('Y-m', strtotime("-$i months")); // Contoh: "2024-12"
                $monthLabel = date('M Y', strtotime("-$i months")); // Contoh: "Dec 2024"
                
                // Cari apakah bulan ini ada di data DB ($raw)
                $found = false;
                $value = 0;
                
                foreach($raw as $r) {
                    if($r['BULAN'] == $monthKey) {
                        $value = (int)$r['TOTAL'];
                        break;
                    }
                }
                
                $finalLabels[] = $monthLabel;
                $finalData[] = $value;
            }

            echo json_encode([
                'status' => 'success',
                'label' => $label,
                'labels' => $finalLabels, // Kirim label yang sudah urut & lengkap
                'data' => $finalData      // Kirim data yang ada angka 0-nya
            ]);

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    // 3. API PROCESS ACTION (Delete, Ban, Status)
    public function apiProcessReport() {
        $this->checkAdminAccess();
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $admin_id = $_SESSION['user_id']; 
        $action = $_POST['action'] ?? '';
        $report_id = $_POST['report_id'] ?? 0;
        $response = ['status' => false, 'message' => 'Invalid Request'];

        try {
            if ($action === 'delete_post') {
                $response = $this->reportModel->deleteReportedPost($report_id, $admin_id);
            } elseif ($action === 'ban_user') {
                $uid = $_POST['user_id'] ?? 0;
                if ($uid) $response = $this->reportModel->banUser($uid, $admin_id);
            } elseif ($action === 'mark_resolved' || $action === 'mark_rejected') {
                $status = ($action === 'mark_resolved') ? 'resolved' : 'rejected';
                $response = $this->reportModel->updateReportStatus($report_id, $admin_id, $status, 'Updated by Admin');
            }
        } catch (Exception $e) {
            $response = ['status' => false, 'message' => $e->getMessage()];
        }

        echo json_encode($response);
        exit;
    }
}
?>