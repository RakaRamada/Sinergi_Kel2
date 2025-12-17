<?php
// app/models/ReportModel.php

class ReportModel {
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    // --- 1. GET ALL REPORTS (Fitur Pagination + Fix CLOB Achonk) ---
    public function getAllReports($status = null, $page = 1, $limit = 10) {
        $clean_limit = (int)$limit;
        $clean_page  = (int)$page;
        $min = ($clean_page - 1) * $clean_limit + 1;
        $max = $clean_page * $clean_limit;

        // PENTING: TO_CHAR(SUBSTR(...)) dipertahankan agar CLOB terbaca string
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
        
        // Sorting: Pending duluan, baru tanggal
        $baseSql .= " ORDER BY (CASE WHEN r.STATUS = 'pending' THEN 0 ELSE 1 END) ASC, r.CREATED_AT DESC";

        $sql = "SELECT * FROM ( SELECT a.*, ROWNUM rnum FROM ( $baseSql ) a WHERE ROWNUM <= :p_max ) WHERE rnum >= :p_min";
        
        $stmt = oci_parse($this->conn, $sql);
        if ($status) oci_bind_by_name($stmt, ':p_status', $status);
        oci_bind_by_name($stmt, ':p_max', $max);
        oci_bind_by_name($stmt, ':p_min', $min);
        
        oci_execute($stmt);
        
        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = array_change_key_case($row, CASE_UPPER);
        }
        return $results;
    }

    // --- 2. GET REPORT DETAIL (Fix CLOB Achonk) ---
    public function getReportById($reportId) {
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

    // --- 3. COUNTER & STATS ---
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

    // --- 4. ACTION HANDLERS (Logika Teman: Transactional & Complete) ---
    
    // Fitur Ban User + Auto Resolve Report
    public function banUser($userId, $reportId, $adminId) {
        // 1. Ban User
        $sqlBan = "UPDATE USERS SET IS_BANNED = 1 WHERE USER_ID = :p_user_id";
        $stmtBan = oci_parse($this->conn, $sqlBan);
        oci_bind_by_name($stmtBan, ':p_user_id', $userId);
        
        // Gunakan OCI_NO_AUTO_COMMIT agar bisa di-rollback jika langkah kedua gagal
        if (!oci_execute($stmtBan, OCI_NO_AUTO_COMMIT)) return ['status' => false, 'message' => 'Gagal ban user'];

        // 2. Update Laporan jadi Resolved
        $sqlReport = "UPDATE REPORTS SET STATUS = 'resolved', REVIEWED_BY = :p_aid, ADMIN_NOTES = 'User telah dibanned' WHERE REPORT_ID = :p_rid";
        $stmtReport = oci_parse($this->conn, $sqlReport);
        oci_bind_by_name($stmtReport, ':p_aid', $adminId);
        oci_bind_by_name($stmtReport, ':p_rid', $reportId);
        
        if (!oci_execute($stmtReport, OCI_NO_AUTO_COMMIT)) {
            oci_rollback($this->conn); // Batalkan ban jika update laporan gagal
            return ['status' => false, 'message' => 'Gagal update status laporan'];
        }
        
        oci_commit($this->conn); // Komit kedua perubahan
        return ['status' => true, 'message' => 'User dibanned & Laporan diselesaikan'];
    }

    public function deleteReportedPost($reportId, $adminId) {
    // Step 0: Ambil POST_ID dari laporan
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

    // Ambil info postingan: gambar DAN pemilik untuk notifikasi
    $sqlInfo = "SELECT p.POST_IMAGE, p.USER_ID, r.REASON 
                FROM POSTINGAN p 
                JOIN REPORTS r ON r.POST_ID = p.POST_ID 
                WHERE p.POST_ID = :p_pid AND r.REPORT_ID = :p_rid";
    $stmtInfo = oci_parse($this->conn, $sqlInfo);
    oci_bind_by_name($stmtInfo, ':p_pid', $postId);
    oci_bind_by_name($stmtInfo, ':p_rid', $reportId);
    oci_execute($stmtInfo);
    $rowInfo = oci_fetch_assoc($stmtInfo);
    $imagePath = $rowInfo['POST_IMAGE'] ?? null;
    $postOwnerId = $rowInfo['USER_ID'] ?? null;
    $reportReason = $rowInfo['REASON'] ?? 'Pelanggaran Konten';

    // [NEW] Kirim notifikasi ke pemilik postingan
    if ($postOwnerId) {
        $notifMessage = "Postingan Anda telah dihapus oleh Admin karena pelanggaran: " . $reportReason;
        $sqlNotifInsert = "INSERT INTO NOTIFICATIONS (USER_ID, RELATED_USER_ID, TYPE, MESSAGE, IS_READ, CREATED_AT) 
                           VALUES (:p_uid, :p_aid, 'warning', :p_msg, 0, SYSDATE)";
        $stmtNotifIns = oci_parse($this->conn, $sqlNotifInsert);
        oci_bind_by_name($stmtNotifIns, ':p_uid', $postOwnerId);
        oci_bind_by_name($stmtNotifIns, ':p_aid', $adminId);
        oci_bind_by_name($stmtNotifIns, ':p_msg', $notifMessage);
        oci_execute($stmtNotifIns, OCI_NO_AUTO_COMMIT);
    }

    // ============================================================
    // [FIX] Step 1: Update Laporan jadi Resolved SEBELUM hapus post
    // ============================================================
    $sqlUpdate = "UPDATE REPORTS SET STATUS = 'resolved', REVIEWED_BY = :p_aid, ADMIN_NOTES = 'Post dihapus oleh Admin' WHERE REPORT_ID = :p_rid";
    $stmtUp = oci_parse($this->conn, $sqlUpdate);
    oci_bind_by_name($stmtUp, ':p_aid', $adminId);
    oci_bind_by_name($stmtUp, ':p_rid', $reportId);
    
    if(!oci_execute($stmtUp, OCI_NO_AUTO_COMMIT)) {
        oci_rollback($this->conn);
        return ['status' => false, 'message' => 'Gagal update status laporan'];
    }

    // Step 2: Hapus Notifikasi terkait post
    $sqlNotif = "DELETE FROM NOTIFICATIONS WHERE RELATED_POST_ID = :p_pid";
    $stmtNotif = oci_parse($this->conn, $sqlNotif);
    oci_bind_by_name($stmtNotif, ':p_pid', $postId);
    oci_execute($stmtNotif, OCI_NO_AUTO_COMMIT);

    // Step 3: Hapus Komentar di post
    $sqlComments = "DELETE FROM COMMENTS WHERE POST_ID = :p_pid";
    $stmtComments = oci_parse($this->conn, $sqlComments);
    oci_bind_by_name($stmtComments, ':p_pid', $postId);
    oci_execute($stmtComments, OCI_NO_AUTO_COMMIT);

    // Step 4: Hapus Likes di post
    $sqlLikes = "DELETE FROM LIKES WHERE POST_ID = :p_pid";
    $stmtLikes = oci_parse($this->conn, $sqlLikes);
    oci_bind_by_name($stmtLikes, ':p_pid', $postId);
    oci_execute($stmtLikes, OCI_NO_AUTO_COMMIT);

    // Step 5: Hapus Post dari database
    $sqlDel = "DELETE FROM POSTINGAN WHERE POST_ID = :p_pid";
    $stmtDel = oci_parse($this->conn, $sqlDel);
    oci_bind_by_name($stmtDel, ':p_pid', $postId);
    
    if (!oci_execute($stmtDel, OCI_NO_AUTO_COMMIT)) {
        $err = oci_error($stmtDel);
        oci_rollback($this->conn);
        return ['status' => false, 'message' => 'Gagal hapus post: ' . ($err['message'] ?? 'Unknown error')];
    }
    
    oci_commit($this->conn);

    // Step 6: Hapus file gambar dari storage (setelah commit)
    if (!empty($imagePath)) {
        $fullPath = $_SERVER['DOCUMENT_ROOT'] . '/sinergi/public/assets/uploads/' . basename($imagePath);
        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }

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

    // --- 5. ANALYTICS (Logika Teman: Support Year Filter) ---
    // Menggunakan parameter :p_year agar grafik bisa difilter
    
    public function getUserGrowthAnalytics($year) { return $this->fetchAnalyticsByYear('USERS', $year); }
    public function getPostGrowthAnalytics($year) { return $this->fetchAnalyticsByYear('POSTINGAN', $year); }
    public function getGroupGrowthAnalytics($year) { return $this->fetchAnalyticsByYear('GROUPS', $year); }
    public function getReportGrowthAnalytics($year) { return $this->fetchAnalyticsByYear('REPORTS', $year); }

    private function fetchAnalyticsByYear($tableName, $year) {
        // Query grouping by MM (bulan angka) pada tahun tertentu
        $sql = "SELECT TO_CHAR(created_at, 'MM') AS BULAN_ANGKA, COUNT(*) as TOTAL 
                FROM $tableName 
                WHERE TO_CHAR(created_at, 'YYYY') = :p_year
                GROUP BY TO_CHAR(created_at, 'MM')
                ORDER BY BULAN_ANGKA ASC";
        
        $stid = oci_parse($this->conn, $sql);
        oci_bind_by_name($stid, ':p_year', $year);
        oci_execute($stid);
        
        $results = [];
        while ($row = oci_fetch_array($stid, OCI_ASSOC)) { 
            $results[] = $row; 
        }
        return $results;
    }

    // --- 1. CREATE REPORT (DENGAN CEK DUPLIKAT) ---
    public function createReport($postId, $reporterId, $reason) {
        
        // [LANGKAH 1] Cek apakah user ini sudah pernah lapor postingan ini?
        $checkSql = "SELECT COUNT(*) AS TOTAL FROM REPORTS 
                     WHERE POST_ID = :p_post_id AND REPORTER_USER_ID = :p_reporter_id";
        $checkStmt = oci_parse($this->conn, $checkSql);
        oci_bind_by_name($checkStmt, ':p_post_id', $postId);
        oci_bind_by_name($checkStmt, ':p_reporter_id', $reporterId);
        oci_execute($checkStmt);
        $row = oci_fetch_assoc($checkStmt);

        // Jika hasilnya > 0, berarti sudah pernah lapor. Kita tolak!
        if ($row && $row['TOTAL'] > 0) {
            return [
                'status' => 'error', 
                'message' => 'Anda sudah melaporkan postingan ini sebelumnya.'
            ];
        }

        // [LANGKAH 2] Jika belum lapor, baru kita masukkan ke database (INSERT)
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

    // --- 6. BLACKLIST MANAGEMENT ---

    /**
     * Ambil semua user yang dibanned
     */
    public function getBannedUsers() {
        $sql = "SELECT u.USER_ID, u.USERNAME, u.NAMA_LENGKAP, u.EMAIL, u.AVATAR_URL,
                       TO_CHAR(u.CREATED_AT, 'DD-MM-YYYY') AS JOINED_DATE
                FROM USERS u
                WHERE u.IS_BANNED = 1
                ORDER BY u.NAMA_LENGKAP ASC";
        
        $stmt = oci_parse($this->conn, $sql);
        oci_execute($stmt);
        
        $results = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $results[] = array_change_key_case($row, CASE_UPPER);
        }
        return $results;
    }

    /**
     * Unban user - kembalikan akses login
     */
    public function unbanUser($userId) {
        $sql = "UPDATE USERS SET IS_BANNED = 0 WHERE USER_ID = :p_user_id";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':p_user_id', $userId);
        
        if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
            return ['status' => true, 'message' => 'User berhasil di-unban'];
        }
        return ['status' => false, 'message' => 'Gagal unban user'];
    }

    // --- 7. REPORT CLEANUP (PURGE) ---

    /**
     * Hapus satu laporan yang sudah resolved
     */
    public function deleteResolvedReport($reportId) {
        // Pastikan hanya hapus yang resolved
        $sql = "DELETE FROM REPORTS WHERE REPORT_ID = :p_rid AND STATUS = 'resolved'";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':p_rid', $reportId);
        
        if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
            $deleted = oci_num_rows($stmt);
            if ($deleted > 0) {
                return ['status' => true, 'message' => 'Laporan berhasil dihapus'];
            }
            return ['status' => false, 'message' => 'Laporan tidak ditemukan atau belum resolved'];
        }
        return ['status' => false, 'message' => 'Gagal menghapus laporan'];
    }

    /**
     * Hapus semua laporan yang sudah resolved
     */
    public function deleteAllResolvedReports() {
        $sql = "DELETE FROM REPORTS WHERE STATUS = 'resolved'";
        $stmt = oci_parse($this->conn, $sql);
        
        if (oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
            $deleted = oci_num_rows($stmt);
            return ['status' => true, 'message' => "Berhasil menghapus $deleted laporan selesai", 'count' => $deleted];
        }
        return ['status' => false, 'message' => 'Gagal menghapus laporan'];
    }
}
?>