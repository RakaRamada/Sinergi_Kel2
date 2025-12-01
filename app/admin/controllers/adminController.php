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

    private function checkAdminAccess() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 5) {
            header('Location: /Sinergi/index.php?page=login');
            exit;
        }
    }

    // --- PAGES ---

    public function dashboard() {
        $this->checkAdminAccess();
        include __DIR__ . '/../views/dashboard.php';
    }

    public function analytics() {
        $this->checkAdminAccess();
        // Hanya me-load View, data diambil via API (AJAX) agar dinamis
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

    // --- API HANDLERS ---

    // NEW: API untuk Chart dengan Filter
    public function apiGetChartData() {
        $this->checkAdminAccess();
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $filter = $_GET['filter'] ?? 'user'; // user, post, community

        try {
            $months = [];
            $data = [];
            $label = '';

            if ($filter === 'user') {
                // DATA 1: User Growth
                $raw = $this->userModel->getUserGrowthAnalytics();
                // Gabungkan semua role jadi satu total per bulan
                $aggregated = [];
                foreach($raw as $r) {
                    $m = $r['BULAN'];
                    if(!isset($aggregated[$m])) $aggregated[$m] = 0;
                    $aggregated[$m] += $r['TOTAL'];
                }
                
                $label = 'Pertumbuhan User';
                ksort($aggregated);
                $months = array_keys($aggregated);
                $data = array_values($aggregated);

            } elseif ($filter === 'post') {
                // DATA 2: Report Trend (Postingan Bermasalah)
                $raw = $this->reportModel->getReportTrendAnalytics();
                
                $label = 'Laporan Postingan Masuk';
                foreach($raw as $r) {
                    $months[] = $r['BULAN'];
                    $data[] = $r['TOTAL'];
                }

            } elseif ($filter === 'community') {
                // DATA 3: Komunitas (Dummy Data)
                // Karena tabel belum ada, kita generate dummy pattern
                $label = 'Pertumbuhan Komunitas';
                $months = ['2025-06', '2025-07', '2025-08', '2025-09', '2025-10', '2025-11', '2025-12'];
                $data = [5, 8, 12, 15, 20, 28, 35];
            }

            echo json_encode([
                'status' => 'success',
                'label' => $label,
                'labels' => $months, // Sumbu X
                'data' => $data      // Sumbu Y
            ]);

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }

    public function apiGetReports() {
        $this->checkAdminAccess();
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $status = $_GET['status'] ?? null;
        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 100;
        $reports = $this->reportModel->getAllReports($status, $limit);
        $stats = $this->reportModel->getReportStats();

        $formatted = [];
        foreach ($reports as $r) {
            $avatar = !empty($r['REPORTER_AVATAR']) ? $r['REPORTER_AVATAR'] : '/Sinergi/public/assets/images/default_avatar.png';
            $formatted[] = [
                'REPORT_ID' => $r['REPORT_ID'],
                'POST_ID' => $r['POST_ID'],
                'REPORTER_NAMA' => $r['REPORTER_NAMA'] ?? 'User',
                'REPORTER_USERNAME' => $r['REPORTER_USERNAME'] ?? 'unknown',
                'REPORTER_AVATAR_FIXED' => $avatar,
                'TANGGAL_FORMAT' => $r['CREATED_AT_STR'],
                'STATUS' => $r['STATUS'],
                'REASON' => $r['REASON'],
                'POST_OWNER_ID' => $r['POST_OWNER_ID'], 
                'POST_OWNER_USERNAME' => $r['POST_OWNER_USERNAME'],
                'IS_OWNER_BANNED' => ($r['OWNER_BANNED_STATUS'] == 1)
            ];
        }

        echo json_encode(['status' => 'success', 'stats' => $stats, 'data' => $formatted], JSON_UNESCAPED_SLASHES);
        exit;
    }

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