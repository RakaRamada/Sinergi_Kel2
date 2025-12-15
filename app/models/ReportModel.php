<?php
// app/models/ReportModel.php
// VERSI PAMUNGKAS: SQL CLOB TO STRING CONVERSION

class ReportModel {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    // --- 1. CREATE REPORT (TETAP SAMA) ---
    public function createReport($postId, $reporterId, $reason) {
        $sql = "INSERT INTO REPORTS (REPORT_ID, POST_ID, REPORTER_USER_ID, REASON, STATUS, CREATED_AT) 
                VALUES (REPORTS_SEQ.NEXTVAL, :p_post_id, :p_reporter_id, :p_reason, 'pending', SYSDATE)";
        
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':p_post_id', $postId);
        oci_bind_by_name($stmt, ':p_reporter_id', $reporterId);
        oci_bind_by_name($stmt, ':p_reason', $reason);

        if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
            return ['status' => 'success', 'message' => 'Laporan berhasil dikirim'];
        } else {
            $e = oci_error($stmt);
            return ['status' => 'error', 'message' => 'Database Error: ' . $e['message']];
        }
    }

    // --- 2. GET ALL REPORTS (JURUS SQL CONVERT) ---
    public function getAllReports($status = null, $page = 1, $limit = 10) {
        $clean_limit = (int)$limit;
        $clean_page  = (int)$page;
        $min = ($clean_page - 1) * $clean_limit + 1;
        $max = $clean_page * $clean_limit;

        // PERUBAHAN PENTING ADA DI SINI:
        // Kita gunakan "TO_CHAR(SUBSTR(p.KONTEN, 1, 3000))"
        // Ini memaksa Oracle mengubah CLOB menjadi string maksimal 3000 karakter.
        // PHP akan menerimanya sebagai string biasa, bukan Object. Aman!
        $baseSql = "SELECT r.REPORT_ID, r.POST_ID, r.REPORTER_USER_ID, r.REASON, r.STATUS, r.ADMIN_NOTES, 
                           TO_CHAR(r.CREATED_AT, 'YYYY-MM-DD HH24:MI:SS') AS CREATED_AT_STR, 
                           u_reporter.USERNAME AS REPORTER_USERNAME, u_reporter.NAMA_LENGKAP AS REPORTER_NAMA, u_reporter.AVATAR_URL AS REPORTER_AVATAR, 
                           
                           TO_CHAR(SUBSTR(p.KONTEN, 1, 3000)) AS POST_KONTEN, 
                           
                           p.POST_IMAGE AS POST_IMAGE, 
                           p.USER_ID AS POST_OWNER_ID, u_owner.USERNAME AS POST_OWNER_USERNAME, u_owner.NAMA_LENGKAP AS POST_OWNER_NAMA, 
                           u_owner.IS_BANNED AS OWNER_BANNED_STATUS 
                    FROM REPORTS r 
                    LEFT JOIN USERS u_reporter ON r.REPORTER_USER_ID = u_reporter.USER_ID 
                    LEFT JOIN POSTINGAN p ON r.POST_ID = p.POST_ID 
                    LEFT JOIN USERS u_owner ON p.USER_ID = u_owner.USER_ID";
        
        if ($status) $baseSql .= " WHERE r.STATUS = :p_status";
        $baseSql .= " ORDER BY (CASE WHEN r.STATUS = 'pending' THEN 0 ELSE 1 END) ASC, r.CREATED_AT DESC";

        // Pagination Direct Injection
        $sql = "SELECT * FROM ( SELECT a.*, ROWNUM rnum FROM ( $baseSql ) a WHERE ROWNUM <= $max ) WHERE rnum >= $min";
        
        $stmt = oci_parse($this->conn, $sql);
        if ($status) oci_bind_by_name($stmt, ':p_status', $status);
        
        if (!oci_execute($stmt)) return [];
        
        $results = [];

        // KARENA SUDAH DIUBAH DI SQL, KITA TIDAK PERLU LOOP RIBET LAGI
        // Cukup fetch normal saja
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = array_change_key_case($row, CASE_UPPER);
        }

        return $results;
    }

    // --- 3. GET REPORT DETAIL (SQL CONVERT JUGA) ---
    public function getReportById($reportId) {
        // Sama, gunakan TO_CHAR(SUBSTR(...)) disini juga
        $sql = "SELECT r.*, TO_CHAR(r.CREATED_AT, 'DD-MM-YYYY HH24:MI') AS CREATED_AT_STR, 
                u_reporter.USERNAME AS REPORTER_USERNAME, u_reporter.AVATAR_URL AS REPORTER_AVATAR, 
                u_reporter.NAMA_LENGKAP AS REPORTER_NAMA,
                
                TO_CHAR(SUBSTR(p.KONTEN, 1, 3000)) AS POST_KONTEN, 
                
                p.POST_IMAGE, p.USER_ID AS POST_OWNER_ID,
                u_owner.USERNAME AS POST_OWNER_USERNAME, u_owner.NAMA_LENGKAP AS POST_OWNER_NAMA,
                u_owner.IS_BANNED AS POST_OWNER_BANNED_STATUS 
                FROM REPORTS r 
                LEFT JOIN USERS u_reporter ON r.REPORTER_USER_ID = u_reporter.USER_ID 
                LEFT JOIN POSTINGAN p ON r.POST_ID = p.POST_ID 
                LEFT JOIN USERS u_owner ON p.USER_ID = u_owner.USER_ID 
                WHERE r.REPORT_ID = :p_rid";
        
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':p_rid', $reportId);
        oci_execute($stmt);
        
        $row = oci_fetch_assoc($stmt);
        return $row ? array_change_key_case($row, CASE_UPPER) : null;
    }

    // --- 4. DATA COUNTER & STATS ---
    public function countAllReports($status = null) {
        $sql = "SELECT COUNT(*) AS TOTAL FROM REPORTS r";
        if ($status) $sql .= " WHERE r.STATUS = :p_status";
        $stmt = oci_parse($this->conn, $sql);
        if ($status) oci_bind_by_name($stmt, ':p_status', $status);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        return $row['TOTAL'] ?? 0;
    }

    public function getReportStats() {
        $sql = "SELECT COUNT(*) AS total, 
                       SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending, 
                       SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) AS reviewed, 
                       SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved 
                FROM reports";
        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        return $row ? array_change_key_case($row, CASE_LOWER) : ['total'=>0,'pending'=>0,'reviewed'=>0,'resolved'=>0];
    }

    // --- 5. ACTION HANDLERS ---
    public function banUser($userId, $adminId) {
        $sql = "UPDATE USERS SET IS_BANNED = 1 WHERE USER_ID = :p_user_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':p_user_id', $userId);
        if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) return ['status' => true, 'message' => 'User dibanned'];
        return ['status' => false, 'message' => 'Gagal DB'];
    }

    public function deleteReportedPost($reportId, $adminId) {
        $sqlGet = "SELECT POST_ID FROM REPORTS WHERE REPORT_ID = :p_rid";
        $stmtGet = oci_parse($this->conn, $sqlGet);
        oci_bind_by_name($stmtGet, ':p_rid', $reportId);
        oci_execute($stmtGet);
        $row = oci_fetch_assoc($stmtGet);
        $postId = $row['POST_ID'] ?? null;
        
        if (!$postId) {
             $this->updateReportStatus($reportId, $adminId, 'resolved', 'Postingan sudah tidak ada');
             return ['status' => true, 'message' => 'Postingan tidak ditemukan, status laporan diperbarui.'];
        }

        $sqlDel = "DELETE FROM POSTINGAN WHERE POST_ID = :p_pid";
        $stmtDel = oci_parse($this->conn, $sqlDel);
        oci_bind_by_name($stmtDel, ':p_pid', $postId);
        
        if (!oci_execute($stmtDel, OCI_NO_AUTO_COMMIT)) return ['status' => false, 'message' => 'Gagal menghapus data postingan'];

        $sqlUpdate = "UPDATE REPORTS SET STATUS = 'resolved', REVIEWED_BY = :p_aid, ADMIN_NOTES = 'Post dihapus oleh Admin' WHERE REPORT_ID = :p_rid";
        $stmtUp = oci_parse($this->conn, $sqlUpdate);
        oci_bind_by_name($stmtUp, ':p_aid', $adminId);
        oci_bind_by_name($stmtUp, ':p_rid', $reportId);
        
        if(!oci_execute($stmtUp, OCI_NO_AUTO_COMMIT)) {
            oci_rollback($this->conn);
            return ['status' => false, 'message' => 'Gagal update status laporan'];
        }
        
        oci_commit($this->conn);
        return ['status' => true, 'message' => 'Postingan dihapus & Laporan diselesaikan'];
    }

    public function updateReportStatus($reportId, $adminId, $status, $notes) {
        $sql = "UPDATE REPORTS SET STATUS = :p_status, REVIEWED_BY = :p_aid, ADMIN_NOTES = :p_notes WHERE REPORT_ID = :p_rid";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':p_status', $status);
        oci_bind_by_name($stmt, ':p_aid', $adminId);
        oci_bind_by_name($stmt, ':p_notes', $notes);
        oci_bind_by_name($stmt, ':p_rid', $reportId);
        if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) return ['status' => true];
        return ['status' => false, 'message' => 'Gagal Update Status'];
    }

    // --- 6. ANALYTICS ---
    private function fetchAnalytics($sql) {
        $stid = oci_parse($this->conn, $sql);
        oci_execute($stid);
        $results = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC)) { $results[] = $row; }
        return $results;
    }
    public function getReportTrendAnalytics() { return $this->fetchAnalytics("SELECT TO_CHAR(created_at, 'YYYY-MM') AS bulan, COUNT(*) as total FROM reports WHERE created_at >= ADD_MONTHS(SYSDATE, -12) GROUP BY TO_CHAR(created_at, 'YYYY-MM') ORDER BY bulan ASC"); }
    public function getUserGrowthAnalytics() { return $this->fetchAnalytics("SELECT TO_CHAR(created_at, 'YYYY-MM') AS bulan, COUNT(*) as total FROM users WHERE created_at >= ADD_MONTHS(SYSDATE, -12) GROUP BY TO_CHAR(created_at, 'YYYY-MM') ORDER BY bulan ASC"); }
    public function getPostGrowthAnalytics() { return $this->fetchAnalytics("SELECT TO_CHAR(created_at, 'YYYY-MM') AS bulan, COUNT(*) as total FROM postingan WHERE created_at >= ADD_MONTHS(SYSDATE, -12) GROUP BY TO_CHAR(created_at, 'YYYY-MM') ORDER BY bulan ASC"); }
    public function getGroupGrowthAnalytics() { return $this->fetchAnalytics("SELECT TO_CHAR(created_at, 'YYYY-MM') AS bulan, COUNT(*) as total FROM groups WHERE created_at >= ADD_MONTHS(SYSDATE, -12) GROUP BY TO_CHAR(created_at, 'YYYY-MM') ORDER BY bulan ASC"); }
}
?>