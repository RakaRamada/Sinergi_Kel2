<?php
// app/models/ReportModel.php

class ReportModel {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    // ==========================================================
    // USER: MEMBUAT LAPORAN
    // ==========================================================
    
    public function createReport($postId, $reporterUserId, $reason) {
        // Cek duplikasi laporan
        $sql_check = "SELECT COUNT(*) AS TOTAL FROM reports 
                      WHERE post_id = :chk_post_id AND reporter_user_id = :chk_user_id";
        
        $stmt_check = oci_parse($this->conn, $sql_check);
        oci_bind_by_name($stmt_check, ':chk_post_id', $postId);
        oci_bind_by_name($stmt_check, ':chk_user_id', $reporterUserId);
        oci_execute($stmt_check);
        $row = oci_fetch_assoc($stmt_check);
        oci_free_statement($stmt_check);
        
        if ($row['TOTAL'] > 0) {
            return ['status' => false, 'message' => 'Anda sudah melaporkan postingan ini sebelumnya.'];
        }

        // Generate ID Manual
        $sql_id = "SELECT NVL(MAX(REPORT_ID), 0) + 1 AS NEXT_ID FROM REPORTS";
        $stmt_id = oci_parse($this->conn, $sql_id);
        oci_execute($stmt_id);
        $row_id = oci_fetch_assoc($stmt_id);
        $newId = $row_id['NEXT_ID'];
        oci_free_statement($stmt_id);

        // Insert Laporan
        $sql = "INSERT INTO reports (REPORT_ID, POST_ID, REPORTER_USER_ID, REASON, STATUS, CREATED_AT) 
                VALUES (:val_rid, :val_pid, :val_uid, :val_reason, 'pending', SYSTIMESTAMP)";
        
        $stmt = oci_parse($this->conn, $sql);
        
        oci_bind_by_name($stmt, ':val_rid', $newId);
        oci_bind_by_name($stmt, ':val_pid', $postId);
        oci_bind_by_name($stmt, ':val_uid', $reporterUserId);
        oci_bind_by_name($stmt, ':val_reason', $reason);

        if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
            oci_free_statement($stmt);
            return ['status' => true, 'message' => 'Laporan berhasil dikirim'];
        } else {
            $e = oci_error($stmt);
            oci_free_statement($stmt);
            return ['status' => false, 'message' => 'Gagal menyimpan database: ' . $e['message']];
        }
    }

    // ==========================================================
    // ADMIN: MENGELOLA LAPORAN
    // ==========================================================

    public function getReportStats() {
        $sql = "SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
                    SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) AS reviewed,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved
                FROM reports";
        
        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        
        return $row ? array_change_key_case($row, CASE_LOWER) : [
            'total' => 0, 'pending' => 0, 'reviewed' => 0, 'resolved' => 0
        ];
    }

    public function getAllReports($status = null, $limit = 100) {
        $sql = "SELECT 
                    r.REPORT_ID,
                    r.POST_ID,
                    r.REPORTER_USER_ID,
                    r.REASON,
                    r.STATUS,
                    r.ADMIN_NOTES,
                    TO_CHAR(r.CREATED_AT, 'YYYY-MM-DD HH24:MI:SS') AS CREATED_AT_STR,
                    u_reporter.USERNAME AS REPORTER_USERNAME,
                    u_reporter.NAMA_LENGKAP AS REPORTER_NAMA,
                    u_reporter.AVATAR_URL AS REPORTER_AVATAR,
                    p.KONTEN AS POST_KONTEN,
                    p.POST_IMAGE AS POST_IMAGE,
                    p.USER_ID AS POST_OWNER_ID,
                    u_owner.USERNAME AS POST_OWNER_USERNAME,
                    u_owner.NAMA_LENGKAP AS POST_OWNER_NAMA,
                    u_owner.IS_BANNED AS OWNER_BANNED_STATUS
                FROM REPORTS r
                LEFT JOIN USERS u_reporter ON r.REPORTER_USER_ID = u_reporter.USER_ID
                LEFT JOIN POSTINGAN p ON r.POST_ID = p.POST_ID
                LEFT JOIN USERS u_owner ON p.USER_ID = u_owner.USER_ID";
        
        if ($status !== null && $status !== '') {
            $sql .= " WHERE r.STATUS = :status";
        }
        
        $sql .= " ORDER BY r.CREATED_AT DESC";
        
        if ($limit > 0) {
            $sql = "SELECT * FROM ($sql) WHERE ROWNUM <= :limit";
        }
        
        $stmt = oci_parse($this->conn, $sql);
        
        if ($status !== null && $status !== '') {
            oci_bind_by_name($stmt, ':status', $status);
        }
        if ($limit > 0) {
            oci_bind_by_name($stmt, ':limit', $limit);
        }
        
        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            throw new Exception("SQL Error: " . $e['message']);
        }
        
        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = array_change_key_case($row, CASE_UPPER);
        }
        oci_free_statement($stmt);
        return $results;
    }

    // TAMBAHAN: Fungsi untuk mendapatkan detail report berdasarkan ID
    public function getReportById($reportId) {
        $sql = "SELECT 
                    r.REPORT_ID,
                    r.POST_ID,
                    r.REPORTER_USER_ID,
                    r.REASON,
                    r.STATUS,
                    r.ADMIN_NOTES,
                    TO_CHAR(r.CREATED_AT, 'DD-MM-YYYY HH24:MI') AS CREATED_AT_STR,
                    u_reporter.USERNAME AS REPORTER_USERNAME,
                    u_reporter.NAMA_LENGKAP AS REPORTER_NAMA,
                    u_reporter.AVATAR_URL AS REPORTER_AVATAR,
                    p.KONTEN AS POST_KONTEN,
                    p.POST_IMAGE AS POST_IMAGE,
                    TO_CHAR(p.CREATED_AT, 'DD-MM-YYYY HH24:MI') AS POST_CREATED_AT,
                    p.USER_ID AS POST_OWNER_ID,
                    u_owner.USERNAME AS POST_OWNER_USERNAME,
                    u_owner.NAMA_LENGKAP AS POST_OWNER_NAMA,
                    u_owner.AVATAR_URL AS POST_OWNER_AVATAR,
                    u_owner.IS_BANNED AS POST_OWNER_BANNED_STATUS,
                    r_owner.ROLE_NAME AS POST_OWNER_ROLE,
                    (SELECT COUNT(*) FROM likes WHERE post_id = p.post_id) AS POST_LIKES,
                    (SELECT COUNT(*) FROM comments WHERE post_id = p.post_id) AS POST_COMMENTS
                FROM REPORTS r
                LEFT JOIN USERS u_reporter ON r.REPORTER_USER_ID = u_reporter.USER_ID
                LEFT JOIN POSTINGAN p ON r.POST_ID = p.POST_ID
                LEFT JOIN USERS u_owner ON p.USER_ID = u_owner.USER_ID
                LEFT JOIN ROLES r_owner ON u_owner.ROLE_ID = r_owner.ROLE_ID
                WHERE r.REPORT_ID = :rid";
        
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':rid', $reportId);
        
        if (!oci_execute($stmt)) {
            $e = oci_error($stmt);
            throw new Exception("SQL Error: " . $e['message']);
        }
        
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);
        
        return $row ? array_change_key_case($row, CASE_UPPER) : null;
    }

    public function banUser($userId, $adminId) {
        $sql = "UPDATE USERS SET IS_BANNED = 1 WHERE USER_ID = :user_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':user_id', $userId);
        
        if (!oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
            $e = oci_error($stmt);
            return ['status' => false, 'message' => 'Gagal banned user: ' . $e['message']];
        }
        oci_free_statement($stmt);
        return ['status' => true, 'message' => 'User berhasil dibanned'];
    }

    public function deleteReportedPost($reportId, $adminId) {
        $sqlGet = "SELECT POST_ID FROM REPORTS WHERE REPORT_ID = :rid";
        $stmtGet = oci_parse($this->conn, $sqlGet);
        oci_bind_by_name($stmtGet, ':rid', $reportId);
        oci_execute($stmtGet);
        $row = oci_fetch_assoc($stmtGet);
        $postId = $row['POST_ID'] ?? null;
        oci_free_statement($stmtGet);

        if (!$postId) return ['status' => false, 'message' => 'Post ID tidak ditemukan'];

        $sqlDel = "DELETE FROM POSTINGAN WHERE POST_ID = :pid";
        $stmtDel = oci_parse($this->conn, $sqlDel);
        oci_bind_by_name($stmtDel, ':pid', $postId);
        
        if (!oci_execute($stmtDel, OCI_NO_AUTO_COMMIT)) {
            $e = oci_error($stmtDel);
            oci_rollback($this->conn);
            return ['status' => false, 'message' => 'Gagal hapus DB: ' . $e['message']];
        }

        $sqlUpdate = "UPDATE REPORTS SET STATUS = 'resolved', REVIEWED_BY = :aid, ADMIN_NOTES = 'Postingan dihapus' WHERE REPORT_ID = :rid";
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
        
        if(oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
            oci_free_statement($stmt);
            return ['status' => true, 'message' => 'Status diupdate'];
        } else {
             $e = oci_error($stmt);
             oci_free_statement($stmt);
             return ['status' => false, 'message' => $e['message']];
        }
    }
}
?>