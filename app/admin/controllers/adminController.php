<?php
// app/admin/controllers/adminController.php (FIXED)

require_once __DIR__ . '/../../models/ReportModel.php';

class AdminController {

    private $conn;
    private $reportModel;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->reportModel = new ReportModel($this->conn);
    }

    // ==========================================================
    // MIDDLEWARE: CEK AKSES ADMIN
    // ==========================================================
    private function checkAdminAccess() {
        if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 5) {
            header('Location: /Sinergi/index.php?page=login');
            exit;
        }
    }

    // ==========================================================
    // BAGIAN 1: VIEW (TAMPILAN HALAMAN)
    // ==========================================================

    public function dashboard() {
        $this->checkAdminAccess();
        include __DIR__ . '/../views/dashboard.php';
    }

    public function detailReport() {
        $this->checkAdminAccess();
        
        // Ambil data laporan berdasarkan ID
        $reportId = $_GET['id'] ?? 0;
        
        if ($reportId) {
            try {
                $report = $this->reportModel->getReportById($reportId);
                
                if (!$report) {
                    echo "<script>alert('Laporan tidak ditemukan'); window.location='/Sinergi/index.php?page=admin-dashboard';</script>";
                    exit;
                }
                
                include __DIR__ . '/../views/detail_report.php';
            } catch (Exception $e) {
                die("Error: " . $e->getMessage());
            }
        } else {
            header('Location: /Sinergi/index.php?page=admin-dashboard');
            exit;
        }
    }

    public function profile() {
        $this->checkAdminAccess();
        include __DIR__ . '/../views/profile.php';
    }

    // ==========================================================
    // BAGIAN 2: API (LOGIC JSON)
    // ==========================================================

    public function apiGetReports() {
        $this->checkAdminAccess();
        
        // Bersihkan output buffer untuk memastikan hanya JSON yang dikirim
        if (ob_get_length()) ob_clean();
        
        header('Content-Type: application/json');

        try {
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

            echo json_encode([
                'status' => 'success',
                'stats' => $stats,
                'data' => $formatted
            ], JSON_UNESCAPED_SLASHES);

        } catch (Exception $e) {
            echo json_encode([
                'status' => 'error', 
                'message' => $e->getMessage()
            ]);
        }
        exit;
    }

    public function apiProcessReport() {
        $this->checkAdminAccess();
        
        if (ob_get_length()) ob_clean();
        header('Content-Type: application/json');

        $admin_id = $_SESSION['user_id']; 
        $action = $_POST['action'] ?? '';
        $report_id = $_POST['report_id'] ?? 0;

        try {
            $response = ['status' => false, 'message' => 'Invalid Request'];

            if ($action === 'delete_post') {
                $response = $this->reportModel->deleteReportedPost($report_id, $admin_id);
            } 
            elseif ($action === 'ban_user') {
                $user_to_ban = $_POST['user_id'] ?? 0;
                if ($user_to_ban) {
                    $response = $this->reportModel->banUser($user_to_ban, $admin_id);
                } else {
                    $response = ['status' => false, 'message' => 'ID User tidak ditemukan'];
                }
            }
            elseif ($action === 'mark_resolved' || $action === 'mark_rejected') {
                $status = ($action === 'mark_resolved') ? 'resolved' : 'rejected';
                $response = $this->reportModel->updateReportStatus($report_id, $admin_id, $status, 'Updated by Admin');
            }

            echo json_encode($response);

        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
}
?>