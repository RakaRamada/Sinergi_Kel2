<?php
// File: app/models/GroupModel.php

class GroupModel {
    
    private $conn;

    public function __construct($dbConnection) {
        $this->conn = $dbConnection;
    }

    /**
     * 1. Mengambil semua group (Untuk fallback/admin)
     */
    public function getAllGroups() {
        $sql = "SELECT group_id, nama_group, deskripsi, created_by_user_id, created_at 
                FROM groups ORDER BY created_at DESC";

        $stmt = oci_parse($this->conn, $sql);
        
        if (!oci_execute($stmt)) {
             return []; 
        }

        $groups = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $groups[] = array_change_key_case($row, CASE_LOWER); 
        }

        oci_free_statement($stmt);
        return $groups;
    }

    /**
     * 2. Mengambil detail satu group
     */
    public function getGroupById($group_id) {
        $sql = "SELECT 
                    group_id, 
                    nama_group, 
                    deskripsi, 
                    group_image, 
                    created_by_user_id, 
                    is_private,
                    TO_CHAR(created_at, 'YYYY-MM-DD HH24:MI:SS') AS created_at 
                FROM groups 
                WHERE group_id = :fid";
        
        $stmt = oci_parse($this->conn, $sql);
        $clean_group_id = (int)$group_id;
        oci_bind_by_name($stmt, ':fid', $clean_group_id);
        
        if (!oci_execute($stmt)) return null;

        $group = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return $group ? array_change_key_case($group, CASE_LOWER) : null;
    }

    /**
     * 3. Mengambil group milik User (Beserta pesan terakhir)
     */
    public function getGroupsByUserId($user_id) {
        $safe_user_id = (int)$user_id; 

        $sql = "SELECT 
                    f.group_id, f.nama_group, f.group_image,
                    lm.isi_pesan AS last_message_text,
                    lm.message_type AS last_message_type,
                    lm.sender_nama AS last_message_sender,
                    lm.sender_id AS last_message_sender_id,
                    TO_CHAR(lm.created_at, 'HH24:MI') AS last_message_time,
                    (
                        SELECT COUNT(mc.message_id)
                        FROM messages mc
                        WHERE mc.group_id = f.group_id
                        AND mc.message_id > NVL(fm.last_read_message_id, 0) 
                        AND mc.sender_id != :uid1
                    ) AS unread_count
                FROM 
                    groups f
                JOIN 
                    group_members fm ON f.group_id = fm.group_id
                OUTER APPLY (
                    SELECT 
                        m.isi_pesan, m.message_type, m.sender_id,
                        u.nama_lengkap AS sender_nama, m.created_at
                    FROM messages m
                    JOIN users u ON m.sender_id = u.user_id
                    WHERE m.group_id = f.group_id
                    ORDER BY m.created_at DESC
                    FETCH FIRST 1 ROW ONLY
                ) lm
                WHERE 
                    fm.user_id = :uid2
                    AND fm.status = 'active'
                ORDER BY 
                    lm.created_at DESC NULLS LAST, f.nama_group ASC";
        
        $stmt = oci_parse($this->conn, $sql);
        
        oci_bind_by_name($stmt, ':uid1', $safe_user_id, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':uid2', $safe_user_id, -1, SQLT_INT);

        if (!oci_execute($stmt)) return [];

        $groups = [];
        while ($row = oci_fetch_assoc($stmt)) {
            // Handle CLOB pesan terakhir
            $last_message_string = '';
            if (isset($row['LAST_MESSAGE_TEXT']) && $row['LAST_MESSAGE_TEXT'] instanceof OCILob) {
                $last_message_string = $row['LAST_MESSAGE_TEXT']->read($row['LAST_MESSAGE_TEXT']->size());
            } elseif (isset($row['LAST_MESSAGE_TEXT']) && is_string($row['LAST_MESSAGE_TEXT'])) {
                $last_message_string = $row['LAST_MESSAGE_TEXT'];
            }
            $row['LAST_MESSAGE_TEXT'] = $last_message_string; 

            $groups[] = array_change_key_case($row, CASE_LOWER);
        }

        oci_free_statement($stmt);
        return $groups;
    }

    /**
     * 4. Membuat Group Baru
     */
    public function createGroup($nama_group, $deskripsi, $creator_user_id, $image_name, $is_private) {
        $clean_private = (int)$is_private;
        $clean_creator_id = (int)$creator_user_id;
        $new_group_id = 0;

        $sql = "INSERT INTO groups (nama_group, deskripsi, created_by_user_id, group_image, is_private, created_at)
                VALUES (:p_nama, EMPTY_CLOB(), :p_creator, :p_img, :p_private, SYSDATE)
                RETURNING group_id, deskripsi INTO :p_new_id, :p_desk_clob";

        $stmt = oci_parse($this->conn, $sql);
        $clob = oci_new_descriptor($this->conn, OCI_D_LOB);

        oci_bind_by_name($stmt, ':p_nama', $nama_group);
        oci_bind_by_name($stmt, ':p_creator', $clean_creator_id, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':p_img', $image_name);
        oci_bind_by_name($stmt, ':p_private', $clean_private, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':p_desk_clob', $clob, -1, OCI_B_CLOB);
        oci_bind_by_name($stmt, ':p_new_id', $new_group_id, -1, SQLT_INT);

        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
             oci_rollback($this->conn); return false;
        }

        if (!$clob->save($deskripsi)) {
            oci_rollback($this->conn); return false;
        }
        
        oci_commit($this->conn);
        oci_free_statement($stmt);
        oci_free_descriptor($clob);

        return $new_group_id; 
    }

    /**
     * 5. JOIN GROUP (PERBAIKAN UTAMA DISINI)
     * Menggunakan logika UPSERT (Update or Insert) dan Chat Otomatis
     */
    public function joinGroup($user_id, $group_id, $status = 'active', $role = 'member') {
        $clean_uid = (int)$user_id;
        $clean_fid = (int)$group_id;
        
        // 1. Cek apakah user sudah ada di tabel? (misal invited, pending, atau left)
        $sqlCheck = "SELECT count(*) as hitung FROM group_members WHERE user_id = :p_uid AND group_id = :p_fid";
        $stmtCheck = oci_parse($this->conn, $sqlCheck);
        oci_bind_by_name($stmtCheck, ':p_uid', $clean_uid);
        oci_bind_by_name($stmtCheck, ':p_fid', $clean_fid);
        oci_execute($stmtCheck);
        $row = oci_fetch_assoc($stmtCheck);
        $sudah_ada = ($row['HITUNG'] > 0);
        oci_free_statement($stmtCheck);
        
        // 2. Insert atau Update (Sertakan ROLE)
        if ($sudah_ada) {
            // FIX: Data sudah ada (misal invited), jadi kita UPDATE
            $sql = "UPDATE group_members 
                    SET status = :p_stat, role = :p_role, joined_at = SYSTIMESTAMP 
                    WHERE user_id = :p_uid AND group_id = :p_fid";
        } else {
            // FIX: Data belum ada, jadi INSERT baru
            $sql = "INSERT INTO group_members (user_id, group_id, status, role, joined_at) 
                    VALUES (:p_uid, :p_fid, :p_stat, :p_role, SYSTIMESTAMP)";
        }

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':p_uid', $clean_uid);
        oci_bind_by_name($stmt, ':p_fid', $clean_fid);
        oci_bind_by_name($stmt, ':p_stat', $status);
        oci_bind_by_name($stmt, ':p_role', $role); // Bind Role

        $success = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
        
        if (!$success) { 
             $e = oci_error($stmt);
             error_log("Gagal Join Group: " . $e['message']);
             oci_free_statement($stmt);
             return false;
        }
        
        oci_free_statement($stmt);

        // 3. AUTO CHAT: Kirim pesan "Bergabung" jika status active
        if ($status === 'active') {
            $this->createSystemMessage($clean_fid, $clean_uid);
        }

        return true; 
    }

    /**
     * PRIVATE HELPER: Membuat pesan otomatis di chat
     */
    private function createSystemMessage($group_id, $user_id) {
        // A. Ambil nama user
        $sqlUser = "SELECT nama_lengkap FROM users WHERE user_id = :p_u";
        $stmtU = oci_parse($this->conn, $sqlUser);
        oci_bind_by_name($stmtU, ':p_u', $user_id);
        oci_execute($stmtU);
        $rowU = oci_fetch_assoc($stmtU);
        $nama = $rowU['NAMA_LENGKAP'] ?? 'Seseorang';
        oci_free_statement($stmtU);

        // B. Siapkan pesan
        $msg = "$nama telah bergabung dengan group.";

        // C. Insert ke tabel messages
        // Pastikan tabel messages kamu punya kolom: group_id, user_id, message, created_at
        $sqlMsg = "INSERT INTO messages (group_id, user_id, message, created_at) 
                   VALUES (:p_gid, :p_uid, :p_msg, SYSTIMESTAMP)";
        
        $stmtM = oci_parse($this->conn, $sqlMsg);
        oci_bind_by_name($stmtM, ':p_gid', $group_id);
        oci_bind_by_name($stmtM, ':p_uid', $user_id);
        oci_bind_by_name($stmtM, ':p_msg', $msg);
        
        oci_execute($stmtM, OCI_COMMIT_ON_SUCCESS);
        oci_free_statement($stmtM);
    }

    /**
     * 6. Leave Group (Hanya DB Logic)
     */
    public function leaveGroup($user_id, $group_id) {
        $safe_user_id = (int)$user_id;
        $safe_group_id = (int)$group_id;

        $sql = "DELETE FROM group_members WHERE user_id = :p_uid AND group_id = :p_gid";
        
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':p_uid', $safe_user_id);
        oci_bind_by_name($stmt, ':p_gid', $safe_group_id);

        if (!oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
             return false;
        }

        oci_free_statement($stmt);
        return true;
    }

    /**
     * 7. Search Groups
     */
    public function searchGroups($searchTerm, $current_user_id = 0) {
        $sql = "SELECT g.group_id, g.nama_group, g.deskripsi, g.group_image, g.is_private,
                   gm.status AS membership_status
            FROM groups g
            LEFT JOIN group_members gm ON (g.group_id = gm.group_id AND gm.user_id = :p_search_uid)
            WHERE UPPER(TO_CHAR(SUBSTR(g.deskripsi, 1, 4000))) LIKE :p_term
            ORDER BY g.created_at DESC";
                
        $searchTermWildcard = '%' . strtoupper($searchTerm) . '%';
        $stmt = oci_parse($this->conn, $sql);
        
        $clean_uid = (int)$current_user_id;
        
        oci_bind_by_name($stmt, ':p_term', $searchTermWildcard);
        oci_bind_by_name($stmt, ':p_search_uid', $clean_uid);

        if (!oci_execute($stmt)) return [];

        $groups = [];
        while ($row = oci_fetch_assoc($stmt)) {
            if (isset($row['DESKRIPSI']) && $row['DESKRIPSI'] instanceof OCILob) {
                $row['DESKRIPSI'] = $row['DESKRIPSI']->read($row['DESKRIPSI']->size());
            }
            $groups[] = array_change_key_case($row, CASE_LOWER);
        }
        oci_free_statement($stmt);
        return $groups;
    }

    /**
     * 8. Ambil Anggota Group
     */
    public function getGroupMembers($group_id) {
        $safe_group_id = (int)$group_id;

        $sql = "SELECT u.user_id, u.username, u.nama_lengkap, u.avatar_url, r.role_name, fm.role as group_role
                FROM group_members fm
                JOIN users u ON fm.user_id = u.user_id
                JOIN roles r ON u.role_id = r.role_id
                WHERE fm.group_id = :fid AND fm.status = 'active'
                ORDER BY 
                    CASE WHEN fm.role = 'owner' THEN 1 
                         WHEN fm.role = 'admin' THEN 2 
                         ELSE 3 END ASC, 
                    u.nama_lengkap ASC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':fid', $safe_group_id);
        oci_execute($stmt);

        $members = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $members[] = array_change_key_case($row, CASE_LOWER);
        }
        oci_free_statement($stmt);
        return $members;
    }

    /**
     * 9. Ambil Pending Members (Request Join)
     */
    public function getPendingMembers($group_id) {
        $safe_group_id = (int)$group_id;

        $sql = "SELECT u.user_id, u.username, u.nama_lengkap, u.avatar_url 
                FROM group_members fm
                JOIN users u ON fm.user_id = u.user_id
                WHERE fm.group_id = :fid AND fm.status = 'pending'
                ORDER BY fm.joined_at ASC";

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':fid', $safe_group_id);
        oci_execute($stmt);

        $pending = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $pending[] = array_change_key_case($row, CASE_LOWER);
        }
        oci_free_statement($stmt);
        return $pending;
    }

    /**
     * 10. Update Group
     */
    public function updateGroup($group_id, $nama_group, $deskripsi, $image_name, $is_private) {
        $clean_group_id = (int)$group_id;
        $clean_private  = (int)$is_private;

        if ($image_name !== null) {
            $sql = "UPDATE groups 
                    SET nama_group = :p_nama, 
                        deskripsi = EMPTY_CLOB(), 
                        group_image = :p_img,
                        is_private = :p_private
                    WHERE group_id = :p_gid
                    RETURNING deskripsi INTO :p_desk_clob";
        } else {
            $sql = "UPDATE groups 
                    SET nama_group = :p_nama, 
                        deskripsi = EMPTY_CLOB(),
                        is_private = :p_private
                    WHERE group_id = :p_gid
                    RETURNING deskripsi INTO :p_desk_clob";
        }

        $stmt = oci_parse($this->conn, $sql);
        $clob = oci_new_descriptor($this->conn, OCI_D_LOB);

        oci_bind_by_name($stmt, ':p_nama', $nama_group);
        oci_bind_by_name($stmt, ':p_gid', $clean_group_id, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':p_private', $clean_private, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':p_desk_clob', $clob, -1, OCI_B_CLOB);
        
        if ($image_name !== null) {
            oci_bind_by_name($stmt, ':p_img', $image_name);
        }

        if (!oci_execute($stmt, OCI_NO_AUTO_COMMIT)) {
             oci_rollback($this->conn); return false;
        }

        if (!$clob->save($deskripsi)) {
            oci_rollback($this->conn); return false;
        }
        
        oci_commit($this->conn);
        oci_free_statement($stmt);
        oci_free_descriptor($clob);

        return true;
    }

    /**
     * 11. Cari User untuk di-invite
     */
    public function getUsersAvailableForGroup($group_id, $search = '') {
        $sql = "SELECT user_id, username, nama_lengkap, avatar_url 
                FROM users 
                WHERE user_id NOT IN (
                    SELECT user_id FROM group_members WHERE group_id = :fid
                )";

        if (!empty($search)) {
            $sql .= " AND (UPPER(nama_lengkap) LIKE :search OR UPPER(username) LIKE :search)";
        }
        $sql .= " ORDER BY nama_lengkap ASC FETCH FIRST 20 ROWS ONLY"; 

        $stmt = oci_parse($this->conn, $sql);
        $fid = (int)$group_id;
        oci_bind_by_name($stmt, ':fid', $fid);

        if (!empty($search)) {
            $s = '%' . strtoupper($search) . '%';
            oci_bind_by_name($stmt, ':search', $s);
        }

        if (!@oci_execute($stmt)) return [];

        $users = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $users[] = array_change_key_case($row, CASE_LOWER);
        }
        oci_free_statement($stmt);
        return $users;
    }

    /**
     * 12. Kick Member (Hanya DB Logic)
     */
    public function kickMember($group_id, $target_user_id) {
        $sql = "DELETE FROM group_members 
                WHERE user_id = :p_user_id AND group_id = :p_group_id";
                
        $stmt = oci_parse($this->conn, $sql);
        
        $clean_uid = (int)$target_user_id;
        $clean_fid = (int)$group_id;
        
        oci_bind_by_name($stmt, ':p_user_id', $clean_uid);
        oci_bind_by_name($stmt, ':p_group_id', $clean_fid);

        $res = oci_execute($stmt, OCI_COMMIT_ON_SUCCESS); 
        oci_free_statement($stmt);
        
        return $res;
    }

    /**
     * 13. Process Join Request (Approve/Reject)
     */
    public function processJoinRequest($user_id, $group_id, $action) {
        $clean_uid = (int)$user_id;
        $clean_fid = (int)$group_id;
        $clean_action = trim($action);

        if ($clean_action === 'approve') {
            $sql = "UPDATE group_members SET status = 'active' 
                    WHERE user_id = :p_request_uid AND group_id = :p_request_gid";
        } elseif ($clean_action === 'reject') {
            $sql = "DELETE FROM group_members 
                    WHERE user_id = :p_request_uid AND group_id = :p_request_gid";
        } else {
            return false;
        }

        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':p_request_uid', $clean_uid, -1, SQLT_INT);
        oci_bind_by_name($stmt, ':p_request_gid', $clean_fid, -1, SQLT_INT);
        
        if (!oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
            oci_free_statement($stmt);
            return false;
        }
        oci_free_statement($stmt);
        return true;
    }

    /**
     * 14. Invite User (Hanya DB Insert)
     */
    public function inviteUserToGroup($user_id, $group_id) {
        // Cek dulu
        $sqlCheck = "SELECT count(*) as hitung FROM group_members 
                     WHERE user_id = :p_uid AND group_id = :p_gid";
        $stmtCheck = oci_parse($this->conn, $sqlCheck);
        
        $clean_uid = (int)$user_id;
        $clean_gid = (int)$group_id;
        
        oci_bind_by_name($stmtCheck, ':p_uid', $clean_uid);
        oci_bind_by_name($stmtCheck, ':p_gid', $clean_gid);
        oci_execute($stmtCheck);
        $row = oci_fetch_assoc($stmtCheck);
        oci_free_statement($stmtCheck);
        
        if ($row['HITUNG'] > 0) return false;

        $sqlInsert = "INSERT INTO group_members (user_id, group_id, status, joined_at) 
                      VALUES (:p_uid, :p_gid, 'invited', SYSTIMESTAMP)";
        $stmt = oci_parse($this->conn, $sqlInsert);
        oci_bind_by_name($stmt, ':p_uid', $clean_uid);
        oci_bind_by_name($stmt, ':p_gid', $clean_gid);
        
        if (!oci_execute($stmt, OCI_COMMIT_ON_SUCCESS)) {
            return false;
        }
        oci_free_statement($stmt);
        return true;
    }

    /**
     * 15. Helper Cek Member
     */
    public function isGroupMember($user_id, $group_id) {
        $sql = "SELECT count(*) as hitung FROM group_members 
                WHERE user_id = :p_uid AND group_id = :p_gid AND status = 'active'";
                
        $stmt = oci_parse($this->conn, $sql);
        $clean_uid = (int)$user_id;
        $clean_gid = (int)$group_id;
        
        oci_bind_by_name($stmt, ':p_uid', $clean_uid);
        oci_bind_by_name($stmt, ':p_gid', $clean_gid);
        
        if (!oci_execute($stmt)) return false;
        
        $row = oci_fetch_assoc($stmt);
        oci_free_statement($stmt);

        return ($row['HITUNG'] > 0);
    }

    public function updateMemberRole($group_id, $target_user_id, $new_role) {
        if (!in_array($new_role, ['admin', 'member'])) return false;

        $sql = "UPDATE group_members SET role = :p_role 
                WHERE group_id = :p_gid AND user_id = :p_uid";
        
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':p_role', $new_role);
        oci_bind_by_name($stmt, ':p_gid', $group_id);
        oci_bind_by_name($stmt, ':p_uid', $target_user_id);
        
        return oci_execute($stmt, OCI_COMMIT_ON_SUCCESS);
    }

    /**
     * BARU: Hapus Grup Total (Fitur Super Owner)
     */
    public function deleteGroup($group_id, $owner_id) {
        // 1. Cek Owner (Kolom: created_by_user_id)
        $checkSql = "SELECT group_id FROM groups WHERE group_id = :p_chk_gid AND created_by_user_id = :p_chk_uid";
        $stmtCheck = oci_parse($this->conn, $checkSql);
        
        // Bind parameter
        oci_bind_by_name($stmtCheck, ':p_chk_gid', $group_id);
        oci_bind_by_name($stmtCheck, ':p_chk_uid', $owner_id);
        
        if (!oci_execute($stmtCheck)) {
            $e = oci_error($stmtCheck); die("ERROR CEK OWNER: " . $e['message']);
        }
        
        if (!oci_fetch_assoc($stmtCheck)) {
            // Jika tidak ketemu, berarti ID salah atau user bukan owner
            return false; 
        }
        oci_free_statement($stmtCheck);

        // === FASE BERSIH-BERSIH (CASCADE MANUAL) ===

        // A. Hapus Notifikasi (FIX: Pakai RELATED_GROUP_ID)
        $sqlNotif = "DELETE FROM notifications WHERE related_group_id = :p_del_gid";
        $stmtNotif = oci_parse($this->conn, $sqlNotif);
        oci_bind_by_name($stmtNotif, ':p_del_gid', $group_id);
        
        if (!oci_execute($stmtNotif, OCI_NO_AUTO_COMMIT)) {
            $e = oci_error($stmtNotif); die("ERROR HAPUS NOTIF: " . $e['message']);
        }
        oci_free_statement($stmtNotif);

        // B. Hapus Member (Kolom: group_id)
        $sqlMem = "DELETE FROM group_members WHERE group_id = :p_del_gid";
        $stmtMem = oci_parse($this->conn, $sqlMem);
        oci_bind_by_name($stmtMem, ':p_del_gid', $group_id);
        
        if (!oci_execute($stmtMem, OCI_NO_AUTO_COMMIT)) {
            $e = oci_error($stmtMem); die("ERROR HAPUS MEMBER: " . $e['message']);
        }
        oci_free_statement($stmtMem);

        // C. Hapus Pesan (Kolom: group_id)
        // (Tidak perlu hapus message_attachments karena tabelnya tidak ada)
        $sqlMsg = "DELETE FROM messages WHERE group_id = :p_del_gid";
        $stmtMsg = oci_parse($this->conn, $sqlMsg);
        oci_bind_by_name($stmtMsg, ':p_del_gid', $group_id);
        
        if (!oci_execute($stmtMsg, OCI_NO_AUTO_COMMIT)) {
            $e = oci_error($stmtMsg); die("ERROR HAPUS MESSAGES: " . $e['message']);
        }
        oci_free_statement($stmtMsg);

        // === FASE FINAL: HAPUS GRUP ===
        $sqlGroup = "DELETE FROM groups WHERE group_id = :p_del_gid";
        $stmtGroup = oci_parse($this->conn, $sqlGroup);
        oci_bind_by_name($stmtGroup, ':p_del_gid', $group_id);

        if (!oci_execute($stmtGroup, OCI_NO_AUTO_COMMIT)) {
            $e = oci_error($stmtGroup);
            oci_rollback($this->conn); // Batalkan semua jika induk gagal dihapus
            die("ERROR HAPUS GRUP UTAMA: " . $e['message']);
        }

        // Jika sampai sini, berarti sukses semua. COMMIT!
        oci_commit($this->conn);
        return true;
    }

    /**
     * Helper: Cek Role User di Grup
     */
    public function getUserRole($user_id, $group_id) {
        $sql = "SELECT role FROM group_members WHERE user_id = :p_uid AND group_id = :p_gid";
        $stmt = oci_parse($this->conn, $sql);
        oci_bind_by_name($stmt, ':p_uid', $user_id);
        oci_bind_by_name($stmt, ':p_gid', $group_id);
        oci_execute($stmt);
        $row = oci_fetch_assoc($stmt);
        return $row ? strtolower($row['ROLE']) : null;
    }

    /**
     * Mengambil grup terpopuler berdasarkan jumlah member
     */
    public function getPopularGroups($limit = 5) {
        $sql = "SELECT g.group_id, g.nama_group, g.group_image, g.is_private,
                       (SELECT COUNT(*) FROM group_members gm WHERE gm.group_id = g.group_id AND gm.status = 'active') as member_count
                FROM groups g
                ORDER BY member_count DESC
                FETCH FIRST :p_limit ROWS ONLY";

        $stmt = oci_parse($this->conn, $sql);
        $clean_limit = (int)$limit;
        oci_bind_by_name($stmt, ':p_limit', $clean_limit);

        if (!oci_execute($stmt)) return [];

        $groups = [];
        while ($row = oci_fetch_assoc($stmt)) {
            $groups[] = array_change_key_case($row, CASE_LOWER);
        }
        oci_free_statement($stmt);
        return $groups;
    }
}


?>