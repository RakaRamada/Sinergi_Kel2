<?php
// File: app/views/messages.php
// Konten ini dipanggil oleh index.php

$current_forum_id = isset($_GET['forum_id']) ? (int)$_GET['forum_id'] : null;

// --- TAMBAHKAN FUNGSI HELPER INI ---
if (!function_exists('formatTanggalChat')) {
    /**
     * Mengubah tanggal ISO menjadi format chat (Hari ini, Kemarin, dll.)
     * @param string $tanggal_iso String tanggal dari DB (format: YYYY-MM-DDTHH:MI:SS)
     * @return string|null Teks yang akan ditampilkan sebagai divider
     */
    function formatTanggalChat($tanggal_iso) {
        if (!$tanggal_iso) return null;
        
        try {
            // PENTING: Atur zona waktu server kamu. 
            // Jika server dan user di Indonesia, 'Asia/Jakarta' adalah pilihan aman.
            $tz = new DateTimeZone('Asia/Jakarta');
            
            // 1. Buat patokan waktu "Hari Ini" (jam 00:00)
            $today = new DateTime('now', $tz);
            $today->setTime(0, 0, 0); // Reset ke awal hari

            // 2. Buat waktu pesan dari data ISO (misal: '2025-11-04T18:44:00')
            $messageDate = new DateTime($tanggal_iso, $tz);
            $messageDate->setTime(0, 0, 0); // Reset ke awal hari juga

            // 3. Hitung selisih hari
            $diff = $today->diff($messageDate);
            $diffDays = (int)$diff->format('%r%a'); // %r%a memberi tanda (misal -1 untuk kemarin)

            // 4. Tentukan logikanya
            if ($diffDays === 0) {
                return 'Hari ini';
            } elseif ($diffDays === -1) {
                return 'Kemarin';
            } elseif ($diffDays > -7) {
                // Jika masih dalam 7 hari terakhir, tampilkan nama harinya
                // Kita buat manual agar tidak bergantung 'locale' server
                $hari = [
                    'Sunday'    => 'Minggu',
                    'Monday'    => 'Senin',
                    'Tuesday'   => 'Selasa',
                    'Wednesday' => 'Rabu',
                    'Thursday'  => 'Kamis',
                    'Friday'    => 'Jumat',
                    'Saturday'  => 'Sabtu'
                ];
                $namaHariInggris = $messageDate->format('l'); // 'l' = nama hari lengkap (misal: 'Tuesday')
                return $hari[$namaHariInggris] ?? $namaHariInggris; // 'Selasa'
            } else {
                // Jika sudah lama, tampilkan tanggal lengkap
                return $messageDate->format('d/m/Y'); // Format: 04/11/2025
            }
        } catch (Exception $e) {
            // Jika format tanggalnya aneh, jangan tampilkan apa-apa
            error_log('Error formatTanggalChat: ' . $e->getMessage());
            return null;
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

<aside class="col-span-6 flex flex-col h-screen">

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

    <?php
    // Ambil ID pesan terakhir (kode ini sudah ada)
    $last_message_id = 0;
    if (isset($messages) && !empty($messages)) {
        $last_message_id = end($messages)['message_id'] ?? 0;
    }
    ?>

    <div id="chat-box" class="flex-grow p-6 overflow-y-auto space-y-4 bg-gray-50"
        data-last-message-id="<?= $last_message_id ?>">

        <?php if (isset($messages) && is_array($messages) && !empty($messages)): ?>

        <?php 
        // Variabel pelacak tanggal (dari langkah sebelumnya)
        $tanggal_divider_terakhir = ''; 
        ?>

        <?php foreach ($messages as $message): ?>

        <?php
            // Logika divider tanggal (dari langkah sebelumnya)
            $tanggal_pesan_raw = $message['created_at_iso'] ?? null;
            $tanggal_pesan_format = formatTanggalChat($tanggal_pesan_raw);

            if ($tanggal_pesan_format && $tanggal_pesan_format !== $tanggal_divider_terakhir):
            ?>
        <div class="text-center my-3 chat-date-divider"
            data-date-string="<?= htmlspecialchars($tanggal_pesan_format) ?>">
            <span class="bg-gray-200 text-gray-700 text-xs font-semibold px-3 py-1 rounded-full">
                <?= htmlspecialchars($tanggal_pesan_format) ?>
            </span>
        </div>
        <?php
                $tanggal_divider_terakhir = $tanggal_pesan_format; 
            endif;
            // --- AKHIR LOGIKA TANGGAL ---
            ?>

        <?php 
            // --- LOGIKA RENDER PESAN BARU ---
            $msg_type = $message['message_type'] ?? 'text';
            $msg_id = $message['message_id'] ?? 0; 
            $sender_id = $message['sender_id'] ?? null;
            $isi_pesan_string = $message['isi_pesan'] ?? '';
            $created_at_time = $message['created_at_time'] ?? ''; 
            $sender_nama = $message['sender_nama'] ?? 'User';
            $current_user_id = $_SESSION['user_id'] ?? null;
            $is_my_message = ($sender_id == $current_user_id);

            // Data file (BARU)
            $file_path = $message['file_path'] ?? null;
            $original_filename = $message['original_filename'] ?? null;
            $file_url = '/Sinergi/public/uploads/forum_files/' . $file_path;

            // Tentukan kelas CSS berdasarkan pengirim
            $bubble_class = $is_my_message ? 'bg-blue-500 text-white' : 'bg-gray-200';
            $time_class = $is_my_message ? 'text-blue-100' : 'text-gray-500';
            $align_class = $is_my_message ? 'justify-end' : 'justify-start';
            $sender_name_html = !$is_my_message ? '<p class="text-xs font-semibold mb-1 text-blue-600">' . htmlspecialchars($sender_nama) . '</p>' : '';
            $caption_html = !empty($isi_pesan_string) ? '<p class="mt-2 text-sm">' . htmlspecialchars($isi_pesan_string) . '</p>' : '';

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
            <div class="<?= $bubble_class ?> p-2 rounded-lg max-w-[70%]">
                <?= $sender_name_html ?>
                <a href="<?= $file_url ?>" target="_blank" class="cursor-pointer">
                    <img src="<?= $file_url ?>" alt="<?= htmlspecialchars($original_filename) ?>"
                        class="rounded-md w-64 h-64 object-cover">
                </a>
                <?= $caption_html ?>
                <div class="text-xs <?= $time_class ?> mt-1 text-right">
                    <?= htmlspecialchars($created_at_time) ?>
                </div>
            </div>
        </div>
        <?php 
                break;

                // --- KASUS: PESAN DOKUMEN ---
                case 'document':
            ?>
        <div class="flex <?= $align_class ?>" id="message-<?= $msg_id ?>">
            <div class="<?= $bubble_class ?> p-3 rounded-lg max-w-[70%]">
                <?= $sender_name_html ?>
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
            </div>
        </div>
        <?php 
                break;

                // --- KASUS: PESAN TEKS (DEFAULT) ---
                case 'text':
                default:
            ?>
        <div class="flex <?= $align_class ?>" id="message-<?= $msg_id ?>">
            <div class="<?= $bubble_class ?> p-3 rounded-lg max-w-[70%]">
                <?= $sender_name_html ?>
                <?= htmlspecialchars($isi_pesan_string) ?>
                <div class="text-xs <?= $time_class ?> mt-1 text-right">
                    <?= htmlspecialchars($created_at_time) ?>
                </div>
            </div>
        </div>
        <?php 
                break;
            endswitch; 
            // --- AKHIR LOGIKA RENDER BARU ---
            ?>

        <?php endforeach; ?>

        <?php else: ?>
        <div id="no-message-placeholder" class="text-center text-gray-500">Belum ada pesan di forum ini.</div>
        <?php endif; ?>
    </div>

    <div class="p-4 border-t border-gray-200 bg-white">

        <div id="file-preview-area" class="mb-2"></div>

        <div class="relative">

            <div id="attach-menu"
                class="hidden absolute bottom-full mb-5 left-24  w-[250px] bg-white text-gray-800 rounded-lg p-3 shadow-xl z-50">

                <div class="attach-menu-item flex items-center px-3 py-3 rounded-md cursor-pointer hover:bg-gray-100"
                    id="attach-image">
                    <img src="/Sinergi/public/assets/icons/image.svg" alt="Photos & videos" class="w-6 h-6 mr-3">
                    <span>Photos & videos</span>
                </div>

                <div class="attach-menu-item flex items-center px-3 py-3 rounded-md cursor-pointer hover:bg-gray-100"
                    id="attach-document">
                    <img src="/Sinergi/public/assets/icons/document.svg" alt="Document" class="w-6 h-6 mr-3">
                    <span>Document</span>
                </div>
            </div>

            <form id="chat-form" method="POST" class="flex items-center" enctype="multipart/form-data">

                <input type="hidden" name="forum_id" value="<?= $current_forum_id ?>">

                <input type="file" id="image-upload-input" name="file_upload" accept="image/png, image/jpeg, image/gif"
                    class="hidden">
                <input type="file" id="document-upload-input" name="file_upload"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt" class="hidden">

                <button type="button" id="attach-btn" class="p-2 rounded-full hover:bg-gray-200 mr-2 cursor-pointer">
                    <img src="/Sinergi/public/assets/icons/attach.svg" alt="Attach" class="w-7 h-7">
                </button>

                <input type="text" id="message-input" name="isi_pesan" placeholder="Ketik pesan..."
                    class="flex-grow py-2 px-4 border rounded-full bg-gray-100 mr-2" autocomplete="off">

                <button type="submit" class="p-2 ml-2 cursor-pointer rounded-full hover:bg-gray-200">
                    <img src="/Sinergi/public/assets/icons/send.svg" alt="Notifikasi" class="w-8 h-8">
                </button>
            </form>
        </div>
    </div>

    <?php else: ?>
    <div class="flex-grow flex items-center justify-center text-gray-500">
        Pilih forum dari daftar di samping untuk memulai percakapan.
    </div>
    <?php endif; ?>
</aside>


<script>
<?php if ($current_forum_id): // Hanya jalankan JS jika ada di dalam forum ?>

// --- (SEMUA KODE JS DARI LANGKAH 3 ADA DI SINI) ---
const CURRENT_USER_ID = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
const FORUM_ID = <?= (int)$current_forum_id ?>;
const chatBox = document.getElementById('chat-box');
const chatForm = document.getElementById('chat-form');
const messageInput = document.getElementById('message-input');

// --- BARU: Ambil elemen-elemen untuk upload file ---
const attachBtn = document.getElementById('attach-btn');
const attachMenu = document.getElementById('attach-menu');
const attachImageBtn = document.getElementById('attach-image');
const attachDocBtn = document.getElementById('attach-document');
const imageInput = document.getElementById('image-upload-input');
const docInput = document.getElementById('document-upload-input');
const filePreviewArea = document.getElementById('file-preview-area');

// --- BARU: Variabel untuk menyimpan file yang akan di-upload ---
let stagedFile = null;

// --- Helper Function (Pindahan, agar bisa dipakai di mana-mana) ---
function escapeHTML(str) {
    if (typeof str !== 'string') return '';
    return str.replace(/[&<>"']/g, function(m) {
        return {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        } [m];
    });
}

// --- Helper Function (LAMA) ---
function formatTanggalChatJS(tanggalISO) {
    if (!tanggalISO) return null;
    try {
        const today = new Date();
        const msgDate = new Date(tanggalISO);
        today.setHours(0, 0, 0, 0);
        msgDate.setHours(0, 0, 0, 0);
        const diffTime = today.getTime() - msgDate.getTime();
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        if (diffDays === 0) return 'Hari ini';
        if (diffDays === 1) return 'Kemarin';
        if (diffDays < 7) {
            const hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            return hari[msgDate.getDay()];
        }
        const d = String(msgDate.getDate()).padStart(2, '0');
        const m = String(msgDate.getMonth() + 1).padStart(2, '0');
        const y = msgDate.getFullYear();
        return `${d}/${m}/${y}`;
    } catch (e) {
        return null;
    }
}
let lastPrintedDate = '';

// --- Fungsi scroll ke bawah (LAMA) ---
function scrollToBottom() {
    if (chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
}

// --- FUNGSI appendMessage (VERSI LENGKAP DAN SUDAH DIPERBAIKI) ---
function appendMessage(message) {
    if (!chatBox) return;
    const existingMessage = document.getElementById(`message-${message.message_id}`);
    if (existingMessage) return;
    const placeholder = document.getElementById('no-message-placeholder');
    if (placeholder) placeholder.remove();

    // --- 1. Logika Divider Tanggal (KODE LENGKAP) ---
    const tanggalISO = message.created_at_iso;
    let dateDividerHTML = '';
    if (tanggalISO) {
        const tanggalFormat = formatTanggalChatJS(tanggalISO);
        if (tanggalFormat && tanggalFormat !== lastPrintedDate) {
            dateDividerHTML = `
            <div class="text-center my-3 chat-date-divider" data-date-string="${escapeHTML(tanggalFormat)}">
                <span class="bg-gray-200 text-gray-700 text-xs font-semibold px-3 py-1 rounded-full">
                    ${escapeHTML(tanggalFormat)}
                </span>
            </div>
            `;
            lastPrintedDate = tanggalFormat;
        }
    }

    // --- 2. Logika Render Pesan BARU (KODE LENGKAP) ---
    let messageHTML = '';
    const isMyMessage = (message.sender_id == CURRENT_USER_ID);

    // Ambil semua data dari JSON
    const msg_type = message.message_type || 'text';
    const msg_id = message.message_id || 0;
    const isi_pesan = escapeHTML(message.isi_pesan || '');
    const timeString = escapeHTML(message.created_at_time || '');
    const sender_nama = escapeHTML(message.sender_nama || 'User');

    const file_path = escapeHTML(message.file_path || '');
    const original_filename = escapeHTML(message.original_filename || '');
    const file_url = `/Sinergi/public/uploads/forum_files/${file_path}`;

    const bubble_class = isMyMessage ? 'bg-blue-500 text-white' : 'bg-gray-200';
    const time_class = isMyMessage ? 'text-blue-100' : 'text-gray-500';
    const align_class = isMyMessage ? 'justify-end' : 'justify-start';
    const sender_name_html = !isMyMessage ? `<p class="text-xs font-semibold mb-1 text-blue-600">${sender_nama}</p>` :
        '';
    const caption_html = (isi_pesan.length > 0) ? `<p class="mt-2 text-sm">${isi_pesan}</p>` : '';

    switch (msg_type) {
        // --- KASUS: PESAN SISTEM (KODE LENGKAP) ---
        case 'join':
        case 'leave':
            messageHTML = `
            <div class="text-center text-sm text-gray-500 my-2" id="message-${msg_id}">
                ${isi_pesan}
                <span class="text-xs ml-1">${timeString}</span>
            </div>
            `;
            break;

            // --- KASUS: PESAN GAMBAR (KODE LENGKAP + PERBAIKAN UKURAN) ---
        case 'image':
            messageHTML = `
            <div class="flex ${align_class}" id="message-${msg_id}">
                <div class="${bubble_class} p-2 rounded-lg max-w-[70%]">
                    ${sender_name_html}
                    <a href="${file_url}" target="_blank" class="cursor-pointer">
                        <img src="${file_url}" alt="${original_filename}" class="rounded-md w-64 h-64 object-cover">
                    </a>
                    ${caption_html}
                    <div class="text-xs ${time_class} mt-1 text-right">
                        ${timeString}
                    </div>
                </div>
            </div>
            `;
            break;

            // --- KASUS: PESAN DOKUMEN (KODE LENGKAP) ---
        case 'document':
            messageHTML = `
            <div class="flex ${align_class}" id="message-${msg_id}">
                <div class="${bubble_class} p-3 rounded-lg max-w-[70%]">
                    ${sender_name_html}
                    <a href="${file_url}" download="${original_filename}" class="flex items-center bg-white/20 p-2 rounded-lg hover:bg-white/40 transition-colors">
                        <img src="/Sinergi/public/assets/icons/document.svg" alt="Doc" class="w-8 h-8 mr-2 ${isMyMessage ? 'invert' : ''}">
                        <div class="flex-1 overflow-hidden">
                            <p class="font-medium truncate">${original_filename}</p>
                            <span class="text-xs">Dokumen</span>
                        </div>
                    </a>
                    ${caption_html}
                    <div class="text-xs ${time_class} mt-1 text-right">
                        ${timeString}
                    </div>
                </div>
            </div>
            `;
            break;

            // --- KASUS: PESAN TEKS (KODE LENGKAP) ---
        case 'text':
        default:
            messageHTML = `
            <div class="flex ${align_class}" id="message-${msg_id}">
                <div class="${bubble_class} p-3 rounded-lg max-w-[70%]">
                    ${sender_name_html}
                    ${isi_pesan}
                    <div class="text-xs ${time_class} mt-1 text-right">
                        ${timeString}
                    </div>
                </div>
            </div>
            `;
            break;
    }

    chatBox.innerHTML += dateDividerHTML;
    chatBox.innerHTML += messageHTML;
}

// --- (SISA KODE LENGKAP DARI LANGKAH 3/6) ---
async function startPolling(lastMessageId) {
    if (!chatBox) return;
    try {
        const response = await fetch(
            `index.php?page=check-new-messages&forum_id=${FORUM_ID}&last_message_id=${lastMessageId}`);
        if (!response.ok) {
            const errorText = await response.text();
            throw new Error(`HTTP error! status: ${response.status}. Response: ${errorText}`);
        }
        const newMessages = await response.json();
        let newLastId = lastMessageId;
        if (newMessages.length > 0) {
            newMessages.forEach(msg => {
                appendMessage(msg);
                newLastId = msg.message_id;
            });
            chatBox.dataset.lastMessageId = newLastId;
            scrollToBottom();
        }
        startPolling(newLastId);
    } catch (error) {
        console.error('Long polling error:', error);
        setTimeout(() => startPolling(lastMessageId), 5000);
    }
}
if (attachBtn && attachMenu) {
    attachBtn.addEventListener('click', (e) => {
        e.stopPropagation();
        attachMenu.classList.toggle('hidden');
    });
    document.addEventListener('click', (e) => {
        if (!attachMenu.contains(e.target) && !attachBtn.contains(e.target)) {
            attachMenu.classList.add('hidden');
        }
    });
}
if (attachImageBtn && imageInput) {
    attachImageBtn.addEventListener('click', () => {
        imageInput.click();
        attachMenu.classList.add('hidden');
    });
}
if (attachDocBtn && docInput) {
    attachDocBtn.addEventListener('click', () => {
        docInput.click();
        attachMenu.classList.add('hidden');
    });
}

function showFilePreview(file) {
    if (!file) {
        filePreviewArea.innerHTML = '';
        filePreviewArea.classList.add('hidden');
        messageInput.placeholder = 'Ketik pesan...';
        return;
    }
    filePreviewArea.classList.remove('hidden');
    const isImage = file.type.startsWith('image/');
    const iconSrc = isImage ? URL.createObjectURL(file) : '/Sinergi/public/assets/icons/document.svg';
    const fileType = isImage ? 'Gambar' : 'Dokumen';
    filePreviewArea.innerHTML = `
        <div class="relative flex items-center p-2 bg-gray-100 rounded-lg border border-gray-300">
            <img src="${iconSrc}" alt="Preview" class="${isImage ? 'w-12 h-12 rounded object-cover' : 'w-10 h-10'} mr-3">
            <div class="flex-1 overflow-hidden">
                <p class="text-sm font-medium text-gray-900 truncate">${escapeHTML(file.name)}</p>
                <p class="text-xs text-gray-500">${fileType} - ${(file.size / 1024).toFixed(1)} KB</p>
            </div>
            <button type="button" id="cancel-file-btn" class="absolute top-1 right-1 p-1 rounded-full hover:bg-gray-300">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>
    `;
    messageInput.placeholder = 'Tambahkan caption... (opsional)';
    document.getElementById('cancel-file-btn').addEventListener('click', () => {
        stagedFile = null;
        imageInput.value = null;
        docInput.value = null;
        showFilePreview(null);
    });
}
imageInput.addEventListener('change', handleFileSelect);
docInput.addEventListener('change', handleFileSelect);

function handleFileSelect(event) {
    if (event.target.files && event.target.files.length > 0) {
        stagedFile = event.target.files[0];
        showFilePreview(stagedFile);
    }
}
if (chatForm && messageInput) {
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const isiPesan = messageInput.value;
        if (isiPesan.trim() === '' && !stagedFile) return;
        const formData = new FormData();
        formData.append('forum_id', FORUM_ID);
        formData.append('isi_pesan', isiPesan);
        if (stagedFile) {
            formData.append('file_upload', stagedFile, stagedFile.name);
        }
        messageInput.value = '';
        showFilePreview(null);
        stagedFile = null;
        imageInput.value = null;
        docInput.value = null;
        try {
            const response = await fetch('index.php?page=store-message', {
                method: 'POST',
                body: formData
            });
            if (!response.ok) {
                messageInput.value = isiPesan;
                const errorText = await response.text();
                throw new Error(`Gagal mengirim pesan. Status: ${response.status}. Pesan: ${errorText}`);
            }
            const savedMessage = await response.json();
            if (savedMessage && savedMessage.message_id) {
                appendMessage(savedMessage);
                chatBox.dataset.lastMessageId = savedMessage.message_id;
                scrollToBottom();
            } else {
                throw new Error('Respon server tidak valid');
            }
        } catch (error) {
            console.error('Gagal mengirim pesan:', error);
            if (!stagedFile) messageInput.value = isiPesan;
            alert(`Terjadi kesalahan: ${error.message}`);
        }
    });
}
if (chatBox) {
    scrollToBottom();
    let initialLastId = parseInt(chatBox.dataset.lastMessageId, 10);
    const lastDivider = chatBox.querySelector('.chat-date-divider:last-of-type');
    if (lastDivider) {
        lastPrintedDate = lastDivider.dataset.dateString;
    }
    startPolling(initialLastId);
}

<?php endif; ?>
</script>