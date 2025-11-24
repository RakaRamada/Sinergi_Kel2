<?php
// File: app/views/messages.php
// Konten ini dipanggil oleh index.php

$current_forum_id = isset($_GET['forum_id']) ? (int)$_GET['forum_id'] : null;

// --- FUNGSI HELPER TANGGAL ---
if (!function_exists('formatTanggalChat')) {
    function formatTanggalChat($tanggal_iso) {
        if (!$tanggal_iso) return null;
        
        try {
            $tz = new DateTimeZone('Asia/Jakarta');
            $today_str = (new DateTime('now', $tz))->format('Y-m-d');
            $yesterday_str = (new DateTime('yesterday', $tz))->format('Y-m-d');
            $msg_date_str = substr($tanggal_iso, 0, 10); 

            if ($msg_date_str === $today_str) {
                return 'Hari ini';
            } elseif ($msg_date_str === $yesterday_str) {
                return 'Kemarin';
            }
            
            $msgDateObj = new DateTime($tanggal_iso, $tz);
            $today_time = strtotime($today_str);
            $msg_time = strtotime($msg_date_str);
            $diff_seconds = $today_time - $msg_time;
            $diff_days_manual = $diff_seconds / (60 * 60 * 24); 

            if ($diff_days_manual > 1 && $diff_days_manual < 7) { 
                $hari = [
                    'Sunday' => 'Minggu', 'Monday' => 'Senin', 'Tuesday' => 'Selasa',
                    'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu'
                ];
                return $hari[$msgDateObj->format('l')] ?? $msgDateObj->format('l');
            } else {
                return $msgDateObj->format('d/m/Y');
            }

        } catch (Exception $e) {
            return null;
        }
    }
}
?>

<main class="col-span-4 border-r border-gray-200 flex flex-col h-screen">

    <div class="p-4 border-b border-gray-200">
        <h2 class="text-xl font-bold mb-4">Forum Diskusi Anda</h2>
    </div>

    <div id="forum-list-container" class="flex-1 overflow-y-auto custom-scrollbar">
        <?php if (isset($forums) && is_array($forums) && !empty($forums)): ?>
        <?php foreach ($forums as $forum): ?>
        <?php
            $forum_id = $forum['forum_id'] ?? 0;
            $nama_forum = $forum['nama_forum'] ?? 'Forum Tanpa Nama';
            
            // Gambar Forum
            $image_path = '/Sinergi/public/assets/images/user.png';
            if (!empty($forum['forum_image'])) {
                $image_path = '/Sinergi/public/uploads/forum_profiles/' . htmlspecialchars($forum['forum_image']);
            }

            // Snippet Pesan Terakhir
            $snippet_html = '';
            $last_msg_text = $forum['last_message_text'] ?? '';
            $last_msg_type = $forum['last_message_type'] ?? '';
            $last_msg_sender = $forum['last_message_sender'] ?? '';
            $last_msg_sender_id = $forum['last_message_sender_id'] ?? 0;
            $current_user_id = $_SESSION['user_id'] ?? 0;

            if (empty($last_msg_sender)) {
                $deskripsi_raw = $forum['deskripsi'] ?? null;
                $deskripsi_string = ($deskripsi_raw instanceof OCILob) ? $deskripsi_raw->read($deskripsi_raw->size()) : (is_string($deskripsi_raw) ? $deskripsi_raw : 'Klik untuk masuk');
                $snippet_html = '<p class="text-sm text-gray-600 truncate italic">' . htmlspecialchars($deskripsi_string) . '</p>';
            } else {
                $sender_display = ($last_msg_sender_id == $current_user_id) ? 'Anda' : htmlspecialchars($last_msg_sender);
                
                $message_content = '';
                switch ($last_msg_type) {
                    case 'image': $message_content = '[Gambar]'; break;
                    case 'document': $message_content = '[Dokumen]'; break;
                    case 'join':
                    case 'leave':
                        $message_content = htmlspecialchars($last_msg_text);
                        $sender_display = '';
                        break;
                    default: $message_content = htmlspecialchars($last_msg_text); break;
                }
                
                $prefix = $sender_display ? $sender_display . ': ' : '';
                $snippet_html = '<p class="text-sm text-gray-600 truncate">' . $prefix . $message_content . '</p>';
            }
            
            // --- UPDATE LOGIKA NOTIFIKASI (Hide badge jika sedang dibuka) ---
            $unread_count = (int)($forum['unread_count'] ?? 0);
            $notif_html = '';
            if ($unread_count > 0 && $forum_id !== $current_forum_id) {
                $notif_html = '<span id="notif-badge-' . $forum_id . '" class="ml-2 bg-gray-900 text-white text-xs font-bold px-2 py-0.5 rounded-full">' . $unread_count . '</span>';
            }
        ?>

        <a href="index.php?page=messages&forum_id=<?= $forum_id ?>" class="flex items-start p-4 border-b border-gray-200 hover:bg-gray-50 
                  <?php if ($current_forum_id === $forum_id) echo 'bg-gray-100 font-semibold'; ?>">

            <img src="<?= $image_path ?>" alt="Profil Forum"
                class="w-10 h-10 rounded-full mr-3 object-cover flex-shrink-0">

            <div class="flex-1 overflow-hidden">
                <div class="flex justify-between items-center">
                    <p class="font-bold truncate"><?= htmlspecialchars($nama_forum) ?></p>
                    <?php if (!empty($forum['last_message_time'])): ?>
                    <span
                        class="text-xs text-gray-500 flex-shrink-0 ml-2"><?= htmlspecialchars($forum['last_message_time']) ?></span>
                    <?php endif; ?>
                </div>
                <div class="flex justify-between items-center mt-1">
                    <div class="flex-1 overflow-hidden">
                        <?= $snippet_html ?>
                    </div>
                    <?= $notif_html ?>
                </div>
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
        class="block p-4 border-b border-gray-200 hover:bg-gray-100 transition-colors duration-150 cursor-pointer bg-white z-20">
        <div class="flex justify-between items-center">
            <div class="flex items-center">
                <?php
                    $header_image_path = '/Sinergi/public/assets/images/user.png'; 
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
                    <p class="text-sm text-gray-500 truncate max-w-md"><?= htmlspecialchars($deskripsi_forum_string) ?>
                    </p>
                </div>
            </div>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                stroke="currentColor" class="w-5 h-5 text-gray-400">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
            </svg>
        </div>
    </a>

    <div id="sticky-date-header"
        class="absolute top-20 left-0 right-3 z-10 text-center py-1 transition-all duration-200 pointer-events-none"
        style="opacity: 0; transform: translateY(-100%);">
        <span class="bg-gray-200 text-gray-700 text-xs font-semibold px-3 py-1 rounded-full shadow-md">
        </span>
    </div>

    <?php
    $last_message_id = 0;
    if (isset($messages) && !empty($messages)) {
        $last_message_id = end($messages)['message_id'] ?? 0;
    }
    ?>

    <div id="chat-box" class="flex-grow p-6 overflow-y-auto space-y-4 bg-gray-50 custom-scrollbar"
        data-last-message-id="<?= $last_message_id ?>">

        <?php if (isset($messages) && is_array($messages) && !empty($messages)): ?>

        <?php foreach ($messages as $i => $message): ?>

        <?php
            // Logika Divider Tanggal
            $tanggal_pesan_ini = formatTanggalChat($message['created_at_iso'] ?? null);
            $tanggal_pesan_sebelumnya = ($i > 0) ? formatTanggalChat($messages[$i-1]['created_at_iso'] ?? null) : null;

            if ($tanggal_pesan_ini && $tanggal_pesan_ini !== $tanggal_pesan_sebelumnya):
        ?>
        <div class="text-center chat-date-divider my-2" data-date-string="<?= htmlspecialchars($tanggal_pesan_ini) ?>">
            <span class="bg-gray-200 text-gray-700 text-xs font-semibold px-3 py-1 rounded-full">
                <?= htmlspecialchars($tanggal_pesan_ini) ?>
            </span>
        </div>
        <?php endif; ?>

        <?php 
            $msg_type = $message['message_type'] ?? 'text';
            $msg_id = $message['message_id'] ?? 0; 
            $sender_id = $message['sender_id'] ?? null;
            $isi_pesan_string = $message['isi_pesan'] ?? '';
            $created_at_time = $message['created_at_time'] ?? ''; 
            $sender_nama = $message['sender_nama'] ?? 'User';
            $current_user_id = $_SESSION['user_id'] ?? null;
            $is_my_message = ($sender_id == $current_user_id);

            $file_path = $message['file_path'] ?? '';
            $original_filename = $message['original_filename'] ?? ''; 
            $file_url = '/Sinergi/public/uploads/forum_files/' . $file_path;

            $reply_to_id = $message['reply_to_message_id'] ?? null;
            $replied_text = $message['replied_message_text'] ?? ''; 
            $replied_sender = $message['replied_sender_nama'] ?? ''; 

            // --- UPDATE STYLE TEMA (HITAM vs PUTIH) ---
            if ($is_my_message) {
                // GAYA PENGIRIM (SAYA) - Hitam
                $bubble_class = 'bg-gray-900 text-white rounded-tr-none shadow-md';
                $time_class = 'text-gray-400';
                $align_class = 'justify-end';
                $sender_name_html = ''; // Nama saya disembunyikan
                
                // Style Reply Saya
                $reply_box_style = 'bg-gray-700 text-gray-200 border-l-4 border-gray-500';
                $reply_sender_style = 'text-gray-300';
                
            } else {
                // GAYA PENERIMA (ORANG LAIN) - Putih
                $bubble_class = 'bg-white text-gray-900 rounded-tl-none border border-gray-200 shadow-sm';
                $time_class = 'text-gray-400';
                $align_class = 'justify-start';
                $sender_name_html = '<p class="text-xs font-bold mb-1 text-blue-600">' . htmlspecialchars($sender_nama) . '</p>';
                
                // Style Reply Orang Lain
                $reply_box_style = 'bg-gray-100 text-gray-600 border-l-4 border-gray-400';
                $reply_sender_style = 'text-gray-800';
            }

            $caption_html = !empty($isi_pesan_string) ? '<p class="mt-1 text-sm leading-relaxed">' . nl2br(htmlspecialchars($isi_pesan_string)) . '</p>' : '';

            // HTML Kotak Balasan (Disesuaikan warnanya)
            $reply_box_html = '';
            if ($reply_to_id && $replied_sender) {
                $replied_sender_display = ($replied_sender == $_SESSION['nama_lengkap']) ? 'Anda' : htmlspecialchars($replied_sender);
                $reply_box_html = '
                <div class="mb-2 p-2 rounded text-xs ' . $reply_box_style . '">
                    <p class="font-bold mb-0.5 ' . $reply_sender_style . '">Membalas ' . $replied_sender_display . '</p>
                    <p class="truncate opacity-90">' . htmlspecialchars($replied_text) . '</p>
                </div>
                ';
            }
            
            // Tentukan teks untuk reply data
            $reply_data_text = ($msg_type === 'text') ? $isi_pesan_string : $original_filename;

            // Tombol Hapus (Hanya Pesan Saya)
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

            // Dropdown Menu Trigger
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

            switch ($msg_type):
                
                // --- PESAN SISTEM ---
                case 'join':
                case 'leave':
            ?>
        <div class="text-center text-sm text-gray-500 my-2">
            <?= htmlspecialchars($isi_pesan_string) ?>
            <span class="text-xs ml-1"><?= htmlspecialchars($created_at_time) ?></span>
        </div>
        <?php 
                break;

                // --- PESAN GAMBAR ---
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

                // --- PESAN DOKUMEN ---
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

                // --- PESAN TEKS ---
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
        <div id="no-message-placeholder" class="text-center text-gray-500 h-full flex items-center justify-center">
            <p>Belum ada pesan di forum ini. Jadilah yang pertama menyapa!</p>
        </div>
        <?php endif; ?>
    </div>

    <div class="p-4 border-t border-gray-200 bg-white">

        <div id="reply-preview-area" class="hidden mb-2"></div>
        <div id="file-preview-area" class="mb-2 hidden"></div>

        <div class="relative">
            <form id="chat-form" method="POST" class="flex items-end" enctype="multipart/form-data">
                <input type="hidden" name="forum_id" value="<?= $current_forum_id ?>">
                <input type="file" id="file-upload-input" name="file_upload"
                    accept="image/png, image/jpeg, image/gif, .pdf, .doc, .docx, .xls, .xlsx, .ppt, .pptx, .txt"
                    class="hidden">

                <button type="button" id="attach-btn"
                    class="p-2 rounded-full hover:bg-gray-200 mr-2 mb-1 cursor-pointer transition">
                    <img src="/Sinergi/public/assets/icons/attach.svg" class="w-7 h-7">
                </button>

                <textarea id="message-input" name="isi_pesan" placeholder="Ketik pesan..."
                    class="flex-grow py-2.5 px-4 border border-gray-300 rounded-2xl bg-gray-50 mr-2 mb-1 resize-none overflow-y-hidden max-h-32 focus:outline-none focus:border-gray-500 focus:ring-1 focus:ring-gray-500 transition"
                    rows="1" autocomplete="off"></textarea>

                <button type="submit" id="send-btn"
                    class="p-2 ml-2 cursor-pointer rounded-full hover:bg-gray-200 mb-1 transition">
                    <img src="/Sinergi/public/assets/icons/send.svg" alt="Kirim" class="w-8 h-8">
                </button>
            </form>
        </div>
    </div>

    <?php else: ?>
    <div class="flex-grow flex flex-col items-center justify-center text-gray-500 bg-gray-50">
        <img src="/Sinergi/public/assets/images/user.png" class="w-24 h-24 opacity-20 mb-4 grayscale">
        <p class="text-lg font-medium">Selamat Datang di Forum Diskusi Sinergi</p>
        <p class="text-sm mt-2">Pilih forum dari daftar di samping untuk memulai percakapan.</p>
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
<?php if ($current_forum_id): ?>
// Variabel Global untuk JS
const CURRENT_USER_ID = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
const FORUM_ID = <?= (int)$current_forum_id ?>;
const CURRENT_USER_NAME = '<?= htmlspecialchars($_SESSION['nama_lengkap'] ?? '') ?>';
<?php endif; ?>
</script>

<?php if ($current_forum_id): ?>
<script src="/Sinergi/public/assets/js/chat_app.js"></script>
<?php endif; ?>

<script src="/Sinergi/public/assets/js/sidebar_updater.js"></script>