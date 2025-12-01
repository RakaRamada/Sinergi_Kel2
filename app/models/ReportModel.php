<?php
// app/models/ReportModel.php

class ReportModel {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    // --- FITUR ANALITIK BARU ---

    // Mengambil tren jumlah laporan per bulan
    public function getReportTrendAnalytics() {
        $sql = "SELECT 
                    TO_CHAR(created_at, 'YYYY-MM') AS bulan, 
                    COUNT(*) as total 
                FROM reports 
                WHERE created_at >= ADD_MONTHS(SYSDATE, -12)
                GROUP BY TO_CHAR(created_at, 'YYYY-MM')
                ORDER BY bulan ASC";
                
        $stid = oci_parse($this->conn, $sql);
        oci_execute($stid);
        
        $results = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
            $results[] = $row;
        }
        return $results;
    }

    // Mengambil statistik jenis pelanggaran
    public function getViolationStats() {
        $sql = "SELECT reason, COUNT(*) as total FROM reports GROUP BY reason";
        $stid = oci_parse($this->conn, $sql);
        oci_execute($stid);
        
        $results = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC)) {
            $results[] = $row;
        }
        return $results;
    }

    // --- FITUR REPORT EKSISTING (JANGAN DIUBAH) ---

    public function createReport($postId, $reporterUserId, $reason) {
        $sql_check = "SELECT COUNT(*) AS TOTAL FROM reports WHERE post_id = :chk_post_id AND reporter_user_id = :chk_user_id";
        $stmt_check = oci_parse($this->conn, $sql_check);
        oci_bind_by_name($stmt_check, ':chk_post_id', $postId);
        oci_bind_by_name($stmt_check, ':chk_user_id', $reporterUserId);
        oci_execute($stmt_check);
        $row = oci_fetch_assoc($stmt_check);
        oci_free_statement($stmt_check);
        
        if ($row['TOTAL'] > 0) return ['status' => false, 'message' => 'Laporan duplikat'];

        $sql_id = "SELECT NVL(MAX(REPORT_ID), 0) + 1 AS NEXT_ID FROM REPORTS";
        $stmt_id = oci_parse($this->conn, $sql_id);
        oci_execute($stmt_id);
        $row_id = oci_fetch_assoc($stmt_id);
        $newId = $row_id['NEXT_ID'];

        $sql = "INSERT INTO reports (REPORT_ID, POST_ID, REPORTER_USER_ID, REASON, STATUS, CREATED_AT) VALUES (:val_rid, :val_pid, :val_uid, :val_reason, 'pending', SYSTIMESTAMP)";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':val_rid', $newId);
        oci_bind_by_name($stmt, ':val_pid', $postId);
        oci_bind_by_name($stmt, ':val_uid', $reporterUserId);
        oci_bind_by_name($stmt, ':val_reason', $reason);

        if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) return ['status' => true, 'message' => 'Berhasil'];
        return ['status' => false, 'message' => 'Gagal DB'];
    }

    public function getReportStats() {
        $sql = "SELECT COUNT(*) AS total, SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending, SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) AS reviewed, SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved FROM reports";
        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        return $row ? array_change_key_case($row, CASE_LOWER) : ['total'=>0,'pending'=>0,'reviewed'=>0,'resolved'=>0];
    }

    public function getAllReports($status = null, $limit = 100) {
        $sql = "SELECT r.REPORT_ID, r.POST_ID, r.REPORTER_USER_ID, r.REASON, r.STATUS, r.ADMIN_NOTES, TO_CHAR(r.CREATED_AT, 'YYYY-MM-DD HH24:MI:SS') AS CREATED_AT_STR, u_reporter.USERNAME AS REPORTER_USERNAME, u_reporter.NAMA_LENGKAP AS REPORTER_NAMA, u_reporter.AVATAR_URL AS REPORTER_AVATAR, p.KONTEN AS POST_KONTEN, p.POST_IMAGE AS POST_IMAGE, p.USER_ID AS POST_OWNER_ID, u_owner.USERNAME AS POST_OWNER_USERNAME, u_owner.NAMA_LENGKAP AS POST_OWNER_NAMA, u_owner.IS_BANNED AS OWNER_BANNED_STATUS FROM REPORTS r LEFT JOIN USERS u_reporter ON r.REPORTER_USER_ID = u_reporter.USER_ID LEFT JOIN POSTINGAN p ON r.POST_ID = p.POST_ID LEFT JOIN USERS u_owner ON p.USER_ID = u_owner.USER_ID";
        if ($status) $sql .= " WHERE r.STATUS = :status";
        $sql .= " ORDER BY r.CREATED_AT DESC";
        if ($limit > 0) $sql = "SELECT * FROM ($sql) WHERE ROWNUM <= :limit";
        
        $stmt = oci_parse($this->conn, $sql);
        if ($status) oci_bind_by_name($stmt, ':status', $status);
        if ($limit > 0) oci_bind_by_name($stmt, ':limit', $limit);
        oci_execute($stmt);
        
        $results = [];
        while ($row = oci_fetch_assoc($stmt)) $results[] = array_change_key_case($row, CASE_UPPER);
        return $results;
    }

    public function getReportById($reportId) {
        $sql = "SELECT r.*, TO_CHAR(r.CREATED_AT, 'DD-MM-YYYY HH24:MI') AS CREATED_AT_STR, u_reporter.USERNAME AS REPORTER_USERNAME, u_reporter.AVATAR_URL AS REPORTER_AVATAR, p.KONTEN AS POST_KONTEN, p.POST_IMAGE, u_owner.USERNAME AS POST_OWNER_USERNAME, u_owner.IS_BANNED AS POST_OWNER_BANNED_STATUS FROM REPORTS r LEFT JOIN USERS u_reporter ON r.REPORTER_USER_ID = u_reporter.USER_ID LEFT JOIN POSTINGAN p ON r.POST_ID = p.POST_ID LEFT JOIN USERS u_owner ON p.USER_ID = u_owner.USER_ID WHERE r.REPORT_ID = :rid";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':rid', $reportId);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        return $row ? array_change_key_case($row, CASE_UPPER) : null;
    }

    public function banUser($userId, $adminId) {
        $sql = "UPDATE USERS SET IS_BANNED = 1 WHERE USER_ID = :user_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);
        if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) return ['status' => true, 'message' => 'User dibanned'];
        return ['status' => false, 'message' => 'Gagal'];
    }

    public function deleteReportedPost($reportId, $adminId) {
        $sqlGet = "SELECT POST_ID FROM REPORTS WHERE REPORT_ID = :rid";
        $stmtGet = oci_parse($this->conn, $sqlGet);
        oci_bind_by_name($stmtGet, ':rid', $reportId);
        oci_execute($stmtGet);
        $row = oci_fetch_assoc($stmtGet);
        $postId = $row['POST_ID'] ?? null;
        if (!$postId) return ['status' => false, 'message' => 'Post ID tidak ada'];

        $sqlDel = "DELETE FROM POSTINGAN WHERE POST_ID = :pid";
        $stmtDel = oci_parse($this->conn, $sqlDel);
        oci_bind_by_name($stmtDel, ':pid', $postId);
        if (!oci_execute($stmtDel, OCI_NO_AUTO_COMMIT)) return ['status' => false, 'message' => 'Gagal hapus post'];

        $sqlUpdate = "UPDATE REPORTS SET STATUS = 'resolved', REVIEWED_BY = :aid, ADMIN_NOTES = 'Post dihapus' WHERE REPORT_ID = :rid";
        $stmtUp = oci_parse($this->conn, $sqlUpdate);
        oci_bind_by_name($stmtUp, ':aid', $adminId);
        oci_bind_by_name($stmtUp, ':rid', $reportId);
        oci_execute($stmtUp, OCI_NO_AUTO_COMMIT);
        
        oci_commit($this->conn);
        return ['status' => true, 'message' => 'Postingan dihapus'];
    }

    public function updateReportStatus($reportId, $adminId, $status, $notes) {
        $sql = "UPDATE REPORTS SET STATUS = :status, REVIEWED_BY = :aid, ADMIN_NOTES = :notes WHERE REPORT_ID = :rid";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':status', $status);
        oci_bind_by_name($stmt, ':aid', $adminId);
        oci_bind_by_name($stmt, ':notes', $notes);
        oci_bind_by_name($stmt, ':rid', $reportId);
        if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) return ['status' => true];
        return ['status' => false, 'message' => 'Gagal'];
    }
}
?>