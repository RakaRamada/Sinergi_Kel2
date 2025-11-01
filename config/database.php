<?php
// Tampilkan semua error untuk debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// 1. Sertakan file koneksi (sesuaikan path jika perlu)
// Asumsi file ini berada di /app/scripts/ atau sejenisnya, naik 2 level
require_once 'koneksi.php'; 

echo "Memulai eksekusi skrip database...<br>";

// 2. Siapkan Array Query (Setiap perintah SQL jadi satu elemen string)
$queries = [
    // --- HAPUS TABEL LAMA ---
    "DROP TABLE notifications",
    "DROP TABLE messages",
    "DROP TABLE comments",
    "DROP TABLE likes",
    "DROP TABLE postingan",
    "DROP TABLE forums",
    "DROP TABLE users",
    "DROP TABLE roles",

    // --- BUAT TABEL BARU ---
    // 1. Tabel Roles
    "CREATE TABLE roles (
        role_id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
        role_name VARCHAR2(50) NOT NULL UNIQUE
    )",

    // 2. Tabel Users
    "CREATE TABLE users (
        user_id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
        role_id NUMBER NOT NULL,
        nama_lengkap VARCHAR2(100) NOT NULL,
        username VARCHAR2(50) NOT NULL UNIQUE,
        email VARCHAR2(100) NOT NULL UNIQUE,
        password_hash VARCHAR2(255) NOT NULL,
        verification_token VARCHAR2(255),
        verification_token_expires_at TIMESTAMP,
        is_verified NUMBER(1) DEFAULT 1 NOT NULL,
        created_at TIMESTAMP DEFAULT SYSTIMESTAMP NOT NULL,
        CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(role_id)
    )",

    // 3. Tabel Forums
    "CREATE TABLE forums (
        forum_id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
        nama_forum VARCHAR2(255) NOT NULL,
        deskripsi CLOB,
        created_by_user_id NUMBER NOT NULL,
        created_at TIMESTAMP DEFAULT SYSTIMESTAMP NOT NULL,
        CONSTRAINT fk_forums_creator FOREIGN KEY (created_by_user_id) REFERENCES users(user_id)
    )",

    // 4. Tabel Postingan
    "CREATE TABLE postingan (
        post_id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
        user_id NUMBER NOT NULL,
        forum_id NUMBER NULL,
        konten CLOB,
        post_image VARCHAR2(500),
        created_at TIMESTAMP DEFAULT SYSTIMESTAMP NOT NULL,
        CONSTRAINT fk_postingan_user FOREIGN KEY (user_id) REFERENCES users(user_id),
        CONSTRAINT fk_postingan_forum FOREIGN KEY (forum_id) REFERENCES forums(forum_id)
    )",
    
    // 5. Tabel Likes
    "CREATE TABLE likes (
        like_id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
        user_id NUMBER NOT NULL,
        post_id NUMBER NOT NULL,
        created_at TIMESTAMP DEFAULT SYSTIMESTAMP NOT NULL,
        CONSTRAINT fk_likes_user FOREIGN KEY (user_id) REFERENCES users(user_id),
        CONSTRAINT fk_likes_post FOREIGN KEY (post_id) REFERENCES postingan(post_id),
        CONSTRAINT uq_user_post_like UNIQUE (user_id, post_id)
    )",

    // 6. Tabel Comments
    "CREATE TABLE comments (
        comment_id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
        user_id NUMBER NOT NULL,
        post_id NUMBER NOT NULL,
        isi_komen VARCHAR2(1000) NOT NULL,
        parent_comment_id NUMBER NULL,
        created_at TIMESTAMP DEFAULT SYSTIMESTAMP NOT NULL,
        CONSTRAINT fk_comments_user FOREIGN KEY (user_id) REFERENCES users(user_id),
        CONSTRAINT fk_comments_post FOREIGN KEY (post_id) REFERENCES postingan(post_id),
        CONSTRAINT fk_comments_parent FOREIGN KEY (parent_comment_id) REFERENCES comments(comment_id)
    )",

    // 7. Tabel Messages (Untuk Forum)
    "CREATE TABLE messages (
        message_id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
        forum_id NUMBER NOT NULL,
        sender_id NUMBER NOT NULL,
        isi_pesan CLOB NOT NULL,
        parent_message_id NUMBER NULL,
        created_at TIMESTAMP DEFAULT SYSTIMESTAMP NOT NULL,
        CONSTRAINT fk_messages_forum FOREIGN KEY (forum_id) REFERENCES forums(forum_id),
        CONSTRAINT fk_messages_sender FOREIGN KEY (sender_id) REFERENCES users(user_id),
        CONSTRAINT fk_messages_parent FOREIGN KEY (parent_message_id) REFERENCES messages(message_id)
    )",

    // 8. Tabel Notifications
    "CREATE TABLE notifications (
        notif_id NUMBER GENERATED ALWAYS AS IDENTITY PRIMARY KEY,
        user_id NUMBER NOT NULL,
        type VARCHAR2(50) NOT NULL,
        related_post_id NUMBER NULL,
        related_user_id NUMBER NULL,
        related_forum_id NUMBER NULL,
        is_read NUMBER(1) DEFAULT 0 NOT NULL,
        created_at TIMESTAMP DEFAULT SYSTIMESTAMP NOT NULL,
        CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(user_id),
        CONSTRAINT fk_notif_related_post FOREIGN KEY (related_post_id) REFERENCES postingan(post_id),
        CONSTRAINT fk_notif_related_user FOREIGN KEY (related_user_id) REFERENCES users(user_id),
        CONSTRAINT fk_notif_related_forum FOREIGN KEY (related_forum_id) REFERENCES forums(forum_id)
    )",

    // --- ISI DATA AWAL ---
    "INSERT INTO roles (role_name) VALUES ('Mahasiswa')",
    "INSERT INTO roles (role_name) VALUES ('Dosen')",
    "INSERT INTO roles (role_name) VALUES ('Alumni')",
    "INSERT INTO roles (role_name) VALUES ('Mitra Industri')",
    "INSERT INTO roles (role_name) VALUES ('Admin')",
    "COMMIT", // Commit setelah insert roles

    // **PENTING: Ganti HASH_PASSWORD_123 dengan hash asli dari password_hash()**
    // Asumsi role_id Mahasiswa=1, Dosen=2
    "INSERT INTO users (role_id, nama_lengkap, username, email, password_hash) VALUES (1, 'Achonk Ganteng', 'achonk', 'achonk@test.com', 'HASH_PASSWORD_123')",
    "INSERT INTO users (role_id, nama_lengkap, username, email, password_hash) VALUES (2, 'Budi Dosen', 'budi_dosen', 'budi@test.com', 'HASH_PASSWORD_123')",
    "COMMIT", // Commit setelah insert users

    // Asumsi user_id Budi Dosen = 2
    "INSERT INTO forums (nama_forum, deskripsi, created_by_user_id) VALUES ('PHP Native Dasar', 'Diskusi dasar-dasar PHP tanpa framework', 2)",
    "COMMIT", // Commit setelah insert forums
    
    // Asumsi user_id Achonk=1, forum_id PHP=1
    "INSERT INTO postingan (user_id, forum_id, konten, post_image) VALUES (1, 1, 'Bagaimana cara koneksi ke Oracle pakai OCI8?', NULL)",
    "INSERT INTO postingan (user_id, forum_id, konten, post_image) VALUES (1, NULL, 'Ini postingan di feed umum.', '/Sinergi/public/assets/images/jkw.jpeg')",
    "COMMIT", // Commit setelah insert postingan

    // Asumsi user_id=2 (Budi), post_id=1 (post Achonk)
    "INSERT INTO comments (user_id, post_id, isi_komen) VALUES (2, 1, 'Mantap postingan pertamanya!')",
    "COMMIT", // Commit setelah insert comments

    // Asumsi user_id=2 (Budi), post_id=1 (post Achonk)
    "INSERT INTO likes (user_id, post_id) VALUES (2, 1)",
    "COMMIT", // Commit setelah insert likes
    
    // Asumsi user_id Achonk=1, Budi=2, forum_id PHP=1
    "INSERT INTO messages (forum_id, sender_id, isi_pesan) VALUES (1, 1, 'Halo, ada yang bisa bantu koneksi OCI8?')",
    "INSERT INTO messages (forum_id, sender_id, isi_pesan) VALUES (1, 2, 'Coba cek dokumentasi PHP, Achonk.')",
    "COMMIT" // Commit setelah insert messages
];

// 3. Loop & Eksekusi
foreach ($queries as $sql) {
    if (trim($sql) === '') continue; // Lewati string kosong

    echo "Mengeksekusi: " . htmlspecialchars(substr(trim($sql), 0, 80)) . "... ";

    $stmt = oci_parse($conn, $sql);
    
    // Gunakan @ untuk menekan warning default, kita tangani error manual
    // Gunakan OCI_NO_AUTO_COMMIT agar bisa dikontrol dengan COMMIT;
    if (@oci_execute($stmt, OCI_NO_AUTO_COMMIT)) { 
        // Cek apakah ini perintah COMMIT
        if (strtoupper(trim($sql)) === 'COMMIT') {
             oci_commit($conn); // Lakukan commit manual
             echo "<span style='color: green;'>✅ Transaksi di-commit.</span><br>";
        } else {
             echo "<span style='color: green;'>✅ Berhasil.</span><br>";
        }
    } else {
        $e = oci_error($stmt);
        // Cek error spesifik ORA-00942 untuk DROP TABLE
        if (strpos(strtoupper(trim($sql)), 'DROP TABLE') === 0 && $e['code'] == 942) {
            echo "<span style='color: orange;'>⚠️ Tabel tidak ada (diabaikan).</span><br>";
        } else {
            // Tampilkan error lainnya
            echo "<span style='color: red;'>❌ Gagal: " . htmlentities($e['message'], ENT_QUOTES) . "</span><br>";
            oci_rollback($conn); // Batalkan transaksi jika ada error
            oci_close($conn);
            die("Eksekusi dihentikan karena error."); // Hentikan skrip
        }
    }
    oci_free_statement($stmt);
}

echo "Eksekusi skrip database selesai.<br>";

// 4. Tutup Koneksi
oci_close($conn);
?>