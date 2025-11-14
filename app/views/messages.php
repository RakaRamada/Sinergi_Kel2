<?php
// File: app/views/messages.php
// Konten ini dipanggil oleh index.php

$current_forum_id = isset($_GET['forum_id']) ? (int)$_GET['forum_id'] : null;

// --- TAMBAHKAN FUNGSI HELPER INI ---
if (!function_exists('formatTanggalChat')) {
    function formatTanggalChat($tanggal_iso) {
        if (!$tanggal_iso) return null;
        
        try {
            // 1. Set zona waktu (ini penting untuk 'now')
            $tz = new DateTimeZone('Asia/Jakarta');
            
            // 2. Dapatkan tanggal HARI INI sebagai string 'Y-m-d'
            $today_str = (new DateTime('now', $tz))->format('Y-m-d');
            
            // 3. Dapatkan tanggal KEMARIN sebagai string 'Y-m-d'
            $yesterday_str = (new DateTime('yesterday', $tz))->format('Y-m-d');

            // 4. Ambil HANYA bagian tanggal (Y-m-d) dari string ISO
            $msg_date_str = substr($tanggal_iso, 0, 10); // Hasil: '2025-11-14'

            // 5. Bandingkan string-nya (JAUH LEBIH AMAN)
            if ($msg_date_str === $today_str) {
                return 'Hari ini';
            } elseif ($msg_date_str === $yesterday_str) {
                return 'Kemarin';
            }
            
            // 6. Jika bukan keduanya, baru kita ubah formatnya
            $msgDateObj = new DateTime($tanggal_iso, $tz);
            
            // Hitung selisih hari (pakai strtotime yang lebih universal)
            $today_time = strtotime($today_str); // Waktu 00:00 hari ini
            $msg_time = strtotime($msg_date_str);   // Waktu 00:00 pesan
            
            // Perhitungan selisih hari manual
            $diff_seconds = $today_time - $msg_time;
            $diff_days_manual = $diff_seconds / (60 * 60 * 24); 

            // --- INI ADALAH PERBAIKANNYA ---
            // Cek apakah selisihnya antara 2 s/d 6 hari
            if ($diff_days_manual > 1 && $diff_days_manual < 7) { 
                $hari = [
                    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
                ];
                return $hari[$msgDateObj->format('l')] ?? $msgDateObj->format('l');
            } else {
                // Jika lebih dari seminggu (atau error), tampilkan tanggal
                return $msgDateObj->format('d/m/Y');
            }

        } catch (Exception $e) {
            error_log('Error formatTanggalChat (V-Final): ' . $e->getMessage());
            // Fallback jika ada error, tampilkan tanggal saja
            try {
                return (new DateTime($tanggal_iso))->format('d/m/Y');
            } catch (Exception $ex) {
                return null; // Gagal total
            }
        }
    }
}
// --- AKHIR FUNGSI HELPER ---
?>

<main class="col-span-4 border-r border-gray-200 flex flex-col h-screen">

    <div class="p-4 border-b border-gray-200">
        <h2 class="text-xl font-bold mb-4">Forum Diskusi Anda</h2>
    </div>

    <div class="flex-1 overflow-y-auto">
        <?php if (isset($forums) && is_array($forums) && !empty($forums)): ?>
        <?php foreach ($forums as $forum): ?>
        <?php
            $forum_id = $forum['forum_id'] ?? 0;
            $nama_forum = $forum['nama_forum'] ?? 'Forum Tanpa Nama';
            
            // Logika Deskripsi (CLOB)
            $deskripsi_raw = $forum['deskripsi'] ?? null;
            $deskripsi_string = ($deskripsi_raw instanceof OCILob) ? $deskripsi_raw->read($deskripsi_raw->size()) : (is_string($deskripsi_raw) ? $deskripsi_raw : '');
            $deskripsi = !empty($deskripsi_string) ? htmlspecialchars($deskripsi_string) : 'Klik untuk masuk ke forum';
            
            // Logika Gambar Forum (Kolom Kiri)
            $image_path = '/Sinergi/public/assets/images/user.png'; // Gambar default
            if (!empty($forum['forum_image'])) {
                $image_path = '/Sinergi/public/uploads/forum_profiles/' . htmlspecialchars($forum['forum_image']);
            }
        ?>
        <a href="index.php?page=messages&forum_id=<?= $forum_id ?>" class="flex items-start p-4 border-b border-gray-200 hover:bg-gray-50 
                              <?php if ($current_forum_id === $forum_id) echo 'bg-gray-100 font-semibold'; ?>">

            <img src="<?= $image_path ?>" alt="Profil Forum" class="w-10 h-10 rounded-full mr-3 object-cover">

            <div class="flex-1 overflow-hidden">
                <p class="font-bold"><?= htmlspecialchars($nama_forum) ?></p>
                <p class="text-sm text-gray-600 truncate"><?= $deskripsi ?></p>
            </div>
        </a>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="p-4 text-center text-gray-500">
            <p>Anda belum bergabung dengan forum diskusi apapun.</p>
        </div>
        <?php endif; ?>
    </div>

    <div class="p-4 border-t border-gray-200 bg-white">
        <a href="index.php?page=create-forum"
            class="flex items-center justify-center w-full bg-gray-900 text-white font-bold py-2 px-4 rounded-full hover:bg-gray-600 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5"
                stroke="currentColor" class="w-6 h-6 mr-2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Buat Forum
        </a>
    </div>
</main>

<aside class="col-span-6 flex flex-col h-screen relative">

    <?php if (isset($forumInfo) && is_array($forumInfo) && !empty($forumInfo)): ?>

    <a href="index.php?page=forum-details&forum_id=<?= $current_forum_id ?>"
        class="block p-4 border-b border-gray-200 hover:bg-gray-100 transition-colors duration-150 cursor-pointer">
        <div class="flex justify-between items-center">
            <div class="flex items-center">
                <?php
                    // Logika Gambar Forum (Header Kanan)
                    $header_image_path = '/Sinergi/public/assets/images/user.png'; // Default
                    if (!empty($forumInfo['forum_image'])) {
                        $header_image_path = '/Sinergi/public/uploads/forum_profiles/' . htmlspecialchars($forumInfo['forum_image']);
                    }
                ?>
                <img src="<?= $header_image_path ?>" alt="Profil Forum"
                    class="w-10 h-10 rounded-full mr-3 object-cover">

                <div>
                    <p class="font-bold"><?= htmlspecialchars($forumInfo['nama_forum'] ?? 'Nama Forum') ?></p>
                    <?php 
                        $deskripsi_forum_raw = $forumInfo['deskripsi'] ?? null;
                        $deskripsi_forum_string = ($deskripsi_forum_raw instanceof OCILob) ? $deskripsi_forum_raw->read($deskripsi_forum_raw->size()) : (is_string($deskripsi_forum_raw) ? $deskripsi_forum_raw : '');
                    ?>
                    <p class="text-sm text-gray-500"><?= htmlspecialchars($deskripsi_forum_string) ?></p>
                </div>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-5 h-5 text-gray-400">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
        </div>
    </a>

    <div id="sticky-date-header"
        class="absolute top-20 left-0 right-3 z-10 text-center py-1 transition-all duration-200"
        style="opacity: 0; transform: translateY(-100%);">
        <span class="bg-gray-200 text-gray-700 text-xs font-semibold px-3 py-1 rounded-full shadow-md">
        </span>
    </div>
    <?php
    // Ambil ID pesan terakhir (kode ini sudah ada)
    $last_message_id = 0;
    if (isset($messages) && !empty($messages)) {
        $last_message_id = end($messages)['message_id'] ?? 0;
    }
    ?> <div id="chat-box" class="flex-grow p-6 overflow-y-auto space-y-4 bg-gray-50"
        data-last-message-id="<?= $last_message_id ?>">

        <?php if (isset($messages) && is_array($messages) && !empty($messages)): ?>

        <?php
        // --- AWAL BLOK BARU (LEBIH KUAT) ---
        // Kita ubah loop-nya untuk mendapatkan $i (index)
        ?>
        <?php foreach ($messages as $i => $message): ?>

        <?php
            // 1. Ambil tanggal pesan ini
            $tanggal_pesan_ini = formatTanggalChat($message['created_at_iso'] ?? null);
            $tanggal_pesan_sebelumnya = null;

            // 2. Jika ini bukan pesan pertama ($i > 0), ambil tanggal pesan sebelumnya
            if ($i > 0) {
                // Kita akses $messages[$i-1] untuk pesan sebelumnya
                $tanggal_pesan_sebelumnya = formatTanggalChat($messages[$i-1]['created_at_iso'] ?? null);
            }

            // 3. Tampilkan divider HANYA jika tanggalnya ada DAN tidak sama dengan tanggal sebelumnya
            // INI ADALAH LOGIKA YANG BENAR: h-0 opacity-0
            if ($tanggal_pesan_ini && $tanggal_pesan_ini !== $tanggal_pesan_sebelumnya):
    ?>
        <div class="text-center chat-date-divider my-2" data-date-string="<?= htmlspecialchars($tanggal_pesan_ini) ?>">
            <span class="bg-gray-200 text-gray-700 text-xs font-semibold px-3 py-1 rounded-full">
                <?= htmlspecialchars($tanggal_pesan_ini) ?>
            </span>
        </div>
        <?php
    endif;
    ?>

        <?php 
            // --- GANTI TOTAL BLOK DEFINISI VARIABEL INI ---
            $msg_type = $message['message_type'] ?? 'text';
            $msg_id = $message['message_id'] ?? 0; 
            $sender_id = $message['sender_id'] ?? null;
            $isi_pesan_string = $message['isi_pesan'] ?? '';
            $created_at_time = $message['created_at_time'] ?? ''; 
            $sender_nama = $message['sender_nama'] ?? 'User';
            $current_user_id = $_SESSION['user_id'] ?? null;
            $is_my_message = ($sender_id == $current_user_id);

            // Data file (aman)
            $file_path = $message['file_path'] ?? '';
            $original_filename = $message['original_filename'] ?? ''; // <-- DIPERBAIKI
            $file_url = '/Sinergi/public/uploads/forum_files/' . $file_path;

            // Data Reply (aman)
            $reply_to_id = $message['reply_to_message_id'] ?? null;
            $replied_text = $message['replied_message_text'] ?? ''; // <-- DIPERBAIKI
            $replied_sender = $message['replied_sender_nama'] ?? ''; // <-- DIPERBAIKI
            // --- AKHIR BLOK ---

            // Tentukan kelas CSS (Tidak berubah)
            $bubble_class = $is_my_message ? 'bg-gray-800 text-gray-100' : 'bg-gray-200';
            $time_class = $is_my_message ? 'text-gray-400' : 'text-gray-500';
            $align_class = $is_my_message ? 'justify-end' : 'justify-start';
            $sender_name_html = !$is_my_message ? '<p class="text-xs font-semibold mb-1 text-gray-800">' . htmlspecialchars($sender_nama) . '</p>' : '';
            $caption_html = !empty($isi_pesan_string) ? '<p class="mt-2 text-sm">' . htmlspecialchars($isi_pesan_string) . '</p>' : '';

            // HTML Kotak Balasan (Tidak berubah)
            $reply_box_html = '';
            if ($reply_to_id && $replied_sender) {
                $replied_sender_display = ($replied_sender == $_SESSION['nama_lengkap']) ? 'Anda' : htmlspecialchars($replied_sender);
                $reply_box_html = '
                <div class="mb-2 p-2 rounded-md bg-black/10 text-sm opacity-80">
                    <p class="font-semibold text-xs text-gray-100">Membalas ' . $replied_sender_display . '</p>
                    <p class="truncate">' . htmlspecialchars($replied_text) . '</p>
                </div>
                ';
            }

         
            // ... (kode $reply_box_html) ...

            /// --- GANTI TOTAL BLOK INI ---
            
            // Tentukan data apa yang akan dibalas
            $reply_data_text = ($msg_type === 'text') ? $isi_pesan_string : $original_filename;

            // Kita siapkan data untuk tombol Hapus
            $delete_button_html = '';
            if ($is_my_message) {
                $delete_button_html = '
                <li>
                    <a href="#" 
                       class="btn-flowbite-delete flex items-center px-4 py-2 text-sm text-red-600 hover:bg-gray-100"
                       data-message-id="' . $msg_id . '">
                        <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12.496 0c-.34.052-.68.107-1.022.166m11.474 0a48.108 48.108 0 00-3.478-.397m-7.496 0c-.34.052-.68.107-1.022.166" />
                        </svg>
                        Hapus
                    </a>
                </li>';
            }

            // Ini adalah HTML trigger BARU
            $trigger_btn_html = '
            <button type="button" 
                    id="dropdown-btn-' . $msg_id . '" 
                    data-dropdown-toggle="dropdown-menu-' . $msg_id . '"
                    class="fb-dropdown-btn absolute top-1 right-1 p-1 rounded-full 
                           opacity-0 group-hover:opacity-100 
                           bg-black/20 hover:bg-black/40 backdrop-blur-sm 
                           transition-all duration-150 cursor-pointer">
                <svg class="w-6 h-6 text-white drop-shadow-sm" fill="currentColor" viewBox="0 0 20 20">
                    <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" fill-rule="evenodd"></path>
                </svg>
            </button>

            <div id="dropdown-menu-' . $msg_id . '" class="hidden z-50 bg-white rounded-lg shadow-lg w-56 py-2">
                <ul class="py-1 text-sm text-gray-700">
                    <li>
                        <a href="#" 
                           class="btn-flowbite-reply flex items-center px-4 py-2 hover:bg-gray-100"
                           data-message-id="' . $msg_id . '"
                           data-reply-name="' . htmlspecialchars($sender_nama) . '"
                           data-reply-text="' . htmlspecialchars($reply_data_text) . '">
                            <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                            </svg>
                            Balas
                        </a>
                    </li>
                    ' . $delete_button_html . ' </ul>
            </div>
            ';
            // --- AKHIR BLOK BARU ---


            switch ($msg_type):
                
                // --- KASUS: PESAN SISTEM (JOIN/LEAVE) ---
                case 'join':
                case 'leave':
            ?>
        <div class="text-center text-sm text-gray-500 my-2">
            <?= htmlspecialchars($isi_pesan_string) ?>
            <span class="text-xs ml-1"><?= htmlspecialchars($created_at_time) ?></span>
        </div>
        <?php 
                break;

                // --- KASUS: PESAN GAMBAR ---
                case 'image':
?>
        <div class="flex <?= $align_class ?>" id="message-<?= $msg_id ?>">
            <div class="group relative <?= $bubble_class ?> p-2 rounded-lg max-w-[70%] break-words">
                <?= $sender_name_html ?>
                <?= $reply_box_html ?>
                <a href="<?= $file_url ?>" target="_blank" class="cursor-pointer">
                    <img src="<?= $file_url ?>" alt="<?= htmlspecialchars($original_filename) ?>" class="rounded-md"
                        style="max-width: 384px; max-height: 384px;">
                </a>
                <?= $caption_html ?>
                <div class="text-xs <?= $time_class ?> mt-1 text-right">
                    <?= htmlspecialchars($created_at_time) ?>
                </div>

                <?= $trigger_btn_html ?>
            </div>
        </div>
        <?php 
        break;

                // --- KASUS: PESAN DOKUMEN ---
                case 'document':
?>
        <div class="flex <?= $align_class ?>" id="message-<?= $msg_id ?>">
            <div class="group relative <?= $bubble_class ?> p-3 rounded-lg max-w-[70%] break-words">
                <?= $sender_name_html ?>
                <?= $reply_box_html ?>
                <a href="<?= $file_url ?>" download="<?= htmlspecialchars($original_filename) ?>"
                    class="flex items-center bg-white/20 p-2 rounded-lg hover:bg-white/40 transition-colors">
                    <img src="/Sinergi/public/assets/icons/document.svg" alt="Doc"
                        class="w-8 h-8 mr-2 <?= $is_my_message ? 'invert' : '' ?>">
                    <div class="flex-1 overflow-hidden">
                        <p class="font-medium truncate"><?= htmlspecialchars($original_filename) ?></p>
                        <span class="text-xs">Dokumen</span>
                    </div>
                </a>
                <?= $caption_html ?>
                <div class="text-xs <?= $time_class ?> mt-1 text-right">
                    <?= htmlspecialchars($created_at_time) ?>
                </div>

                <?= $trigger_btn_html ?>
            </div>
        </div>
        <?php 
        break;

                // --- KASUS: PESAN TEKS (DEFAULT) ---
                case 'text':
                default:
            ?>
        <div class="flex <?= $align_class ?>" id="message-<?= $msg_id ?>">
            <div class="group relative <?= $bubble_class ?> p-3 rounded-lg max-w-[70%] break-words">
                <?= $sender_name_html ?>
                <?= $reply_box_html ?>
                <?= htmlspecialchars($isi_pesan_string) ?>
                <div class="text-xs <?= $time_class ?> mt-1 text-right">
                    <?= htmlspecialchars($created_at_time) ?>
                </div>

                <?= $trigger_btn_html ?>
            </div>
        </div>
        <?php 
                break;
            endswitch; 
            ?>

        <?php endforeach; ?>

        <?php else: ?>
        <div id="no-message-placeholder" class="text-center text-gray-500">Belum ada pesan di forum ini.</div>
        <?php endif; ?>
    </div>

    <div class="p-4 border-t border-gray-200 bg-white">

        <div id="reply-preview-area" class="hidden mb-2">
        </div>
        <div id="file-preview-area" class="mb-2"></div>

        <div class="relative">

            <form id="chat-form" method="POST" class="flex items-end" enctype="multipart/form-data">

                <input type="hidden" name="forum_id" value="<?= $current_forum_id ?>">

                <input type="file" id="file-upload-input" name="file_upload"
                    accept="image/png, image/jpeg, image/gif, .pdf, .doc, .docx, .xls, .xlsx, .ppt, .pptx, .txt"
                    class="hidden">

                <button type="button" id="attach-btn"
                    class="p-2 rounded-full hover:bg-gray-200 mr-2 mb-1 cursor-pointer">
                    <img src="/Sinergi/public/assets/icons/attach.svg" class="w-7 h-7">
                </button>

                <textarea id="message-input" name="isi_pesan" placeholder="Ketik pesan..."
                    class="flex-grow py-2.5 px-4 border rounded-2xl bg-gray-100 mr-2 mb-1 resize-none overflow-y-hidden max-h-32 "
                    rows="1" autocomplete="off"></textarea>

                <button type="submit" id="send-btn" class="p-2 ml-2 cursor-pointer rounded-full hover:bg-gray-200 mb-1">
                    <img src="/Sinergi/public/assets/icons/send.svg" alt="Kirim" class="w-8 h-8">
                </button>

            </form>
        </div>
    </div>

    <?php else: ?>
    <div class="flex-grow flex items-center justify-center text-gray-500">
        Pilih forum dari daftar di samping untuk memulai percakapan.
    </div>
    <?php endif; ?>


    <div id="delete-confirm-modal" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-lg max-w-sm w-full p-6 mx-auto">
            <h3 class="text-lg font-semibold text-gray-900 mb-2">Hapus Pesan</h3>
            <p class="text-sm text-gray-600 mb-6">
                Anda yakin ingin menghapus pesan ini secara permanen? Tindakan ini tidak dapat diurungkan.
            </p>
            <div class="flex justify-end space-x-3">
                <button type="button" id="modal-btn-cancel"
                    class="py-2 px-4 rounded-lg text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 transition-colors cursor-pointer">
                    Batal
                </button>
                <button type="button" id="modal-btn-confirm-delete"
                    class="py-2 px-4 rounded-lg text-sm font-medium text-white bg-red-600 hover:bg-red-700 transition-colors cursor-pointer">
                    Ya, Hapus
                </button>
            </div>
        </div>
    </div>
</aside>


<script>
<?php if ($current_forum_id): // Hanya definisikan jika ada di dalam forum ?>

// LANGKAH 1: Definisikan variabel global DARI PHP
// Ini HARUS ada SEBELUM memanggil file chat_app.js
const CURRENT_USER_ID = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
const FORUM_ID = <?= (int)$current_forum_id ?>;
const CURRENT_USER_NAME = '<?= htmlspecialchars($_SESSION['nama_lengkap'] ?? '') ?>';

<?php endif; ?>
</script>

<?php if ($current_forum_id): ?>

<script src="/Sinergi/public/assets/js/chat_app.js"></script>

<?php endif; ?>