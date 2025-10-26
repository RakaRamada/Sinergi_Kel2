<?php
require_once "../../config/koneksi.php";

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// require '../vendor/autoload.php'; // pastikan path sesuai

if (isset($_POST['register'])) {
    $nama = $_POST['nama_lengkap'];
    $email = $_POST['email'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $role = $_POST['role'];
    $code = md5($email . time());

    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->SMTPDebug = SMTP::DEBUG_OFF;
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'sinergi.tik24@gmail.com';
        $mail->Password   = 'jzqz zlot alna qqda'; // app password gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port       = 465;

        //Recipients
        $mail->setFrom('sinergi.tik24@gmail.com', 'PBL SINERGI');
        $mail->addAddress($email, $nama);

        //Content
        $link = "http://localhost/sinergi/app/controllers/PostController.php?code=$code";

        $mail->isHTML(true);
        $mail->Subject = 'Verifikasi Akun SINERGI';
        $mail->Body    = "Hai $nama, <br><br>
                          Terima kasih sudah mendaftar di website ini.<br>
                          Mohon verifikasi akun kamu dengan klik link berikut:<br>
                          <a href='$link'>$link</a>";

       if ($mail->send()) {
            // Ganti nama tabel ke 'users' sesuai yang kita buat sebelumnya
            $sql_users = "INSERT INTO users (nama_lengkap, email, password, role, verifikasi_kode, is_verif)
                    VALUES ('$nama', '$email', '$password', '$role', '$code', $is_verif)";

            // Gunakan oci_parse dan oci_execute
            $statement = oci_parse($conn, $sql_users); // Gunakan variabel koneksi Oracle ($conn)
            
            if (oci_execute($statement)) {
                 echo "<script>alert('Registrasi berhasil, silahkan cek email untuk verifikasi akun anda');window.location='login.php'</script>";
            } else {
                 $e = oci_error($statement);
                 // Tampilkan error database jika insert gagal, agar mudah di-debug
                 echo "<script>alert('Registrasi berhasil, namun gagal menyimpan data ke database. Error: " . htmlentities($e['message']) . "');window.location='register.php'</script>";
            }
            oci_free_statement($statement);
            oci_close($conn);

        }
    } catch (Exception $e) {
        echo "❌ Email gagal dikirim. Error: {$mail->ErrorInfo}";
    }
}
?>
