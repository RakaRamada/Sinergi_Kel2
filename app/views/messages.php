<?php
// File: app/views/messages.php
// Konten ini dipanggil oleh index.php

$current_forum_id = isset($_GET['forum_id']) ? (int)$_GET['forum_id'] : null;
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
    $last_message_id = 0;
    if (isset($messages) && !empty($messages)) {
        $last_message_id = end($messages)['message_id'] ?? 0;
    }
    ?>
    <div id="chat-box" class="flex-grow p-6 overflow-y-auto space-y-4 bg-gray-50"
        data-last-message-id="<?= $last_message_id ?>">

        <?php if (isset($messages) && is_array($messages) && !empty($messages)): ?>
        <?php foreach ($messages as $message): ?>
        <?php 
                $msg_id = $message['message_id'] ?? 0; 
                $sender_id = $message['sender_id'] ?? null;
                $isi_pesan_string = $message['isi_pesan'] ?? '';
                $created_at_time = $message['created_at_time'] ?? ''; 
                $sender_nama = $message['sender_nama'] ?? 'User';
                $current_user_id = $_SESSION['user_id'] ?? null;
                $is_my_message = ($sender_id == $current_user_id); 
            ?>
        <?php if ($is_my_message): ?>
        <div class="flex justify-end" id="message-<?= $msg_id ?>">
            <div class="bg-blue-500 text-white p-3 rounded-lg max-w-[70%]">
                <?= htmlspecialchars($isi_pesan_string) ?>
                <div class="text-xs text-blue-100 mt-1 text-right">
                    <?= htmlspecialchars($created_at_time) ?>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="flex justify-start" id="message-<?= $msg_id ?>">
            <div class="bg-gray-200 p-3 rounded-lg max-w-[70%]">
                <p class="text-xs font-semibold mb-1 text-blue-600"><?= htmlspecialchars($sender_nama) ?></p>
                <?= htmlspecialchars($isi_pesan_string) ?>
                <div class="text-xs text-gray-500 mt-1 text-right">
                    <?= htmlspecialchars($created_at_time) ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
        <?php else: ?>
        <div id="no-message-placeholder" class="text-center text-gray-500">Belum ada pesan di forum ini.</div>
        <?php endif; ?>
    </div>

    <div class="p-4 border-t border-gray-200 bg-white">
        <form id="chat-form" method="POST" class="flex items-center">
            <input type="hidden" name="forum_id" value="<?= $current_forum_id ?>">
            <input type="text" id="message-input" name="isi_pesan" placeholder="Ketik pesan..."
                class="flex-grow py-2 px-4 border rounded-full bg-gray-100 mr-2" autocomplete="off">
            <button type="submit" class="bg-blue-500 text-white rounded-full p-2 hover:bg-blue-600">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                    stroke="currentColor" class="w-6 h-6">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
                </svg>
            </button>
        </form>
    </div>

    <?php else: ?>
    <div class="flex-grow flex items-center justify-center text-gray-500">
        Pilih forum dari daftar di samping untuk memulai percakapan.
    </div>
    <?php endif; ?>
</aside>


<script>
<?php if ($current_forum_id): // Hanya jalankan JS jika ada di dalam forum ?>

// Ambil ID user yang sedang login dari PHP Session
const CURRENT_USER_ID = <?= (int)($_SESSION['user_id'] ?? 0) ?>;
const FORUM_ID = <?= (int)$current_forum_id ?>;

// Ambil elemen-elemen penting
const chatBox = document.getElementById('chat-box');
const chatForm = document.getElementById('chat-form');
const messageInput = document.getElementById('message-input');

// Fungsi untuk scroll ke bawah
function scrollToBottom() {
    if (chatBox) {
        chatBox.scrollTop = chatBox.scrollHeight;
    }
}

// Fungsi untuk menambahkan pesan baru ke DOM
function appendMessage(message) {
    if (!chatBox) return;

    // Perbaikan Bug Gema: Cek apakah pesan sudah ada
    const existingMessage = document.getElementById(`message-${message.message_id}`);
    if (existingMessage) {
        return; // JANGAN TAMBAHKAN, pesan sudah ada
    }

    const placeholder = document.getElementById('no-message-placeholder');
    if (placeholder) {
        placeholder.remove();
    }

    const isMyMessage = (message.sender_id == CURRENT_USER_ID);
    let messageHTML = '';

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

    const timeString = message.created_at_time || '';

    if (isMyMessage) {
        messageHTML = `
        <div class="flex justify-end" id="message-${message.message_id}">
            <div class="bg-blue-500 text-white p-3 rounded-lg max-w-[70%]">
                ${escapeHTML(message.isi_pesan)}
                <div class="text-xs text-blue-100 mt-1 text-right">${timeString}</div>
            </div>
        </div>
        `;
    } else {
        messageHTML = `
        <div class="flex justify-start" id="message-${message.message_id}">
            <div class="bg-gray-200 p-3 rounded-lg max-w-[70%]">
                <p class="text-xs font-semibold mb-1 text-blue-600">${escapeHTML(message.sender_nama)}</p>
                ${escapeHTML(message.isi_pesan)}
                <div class="text-xs text-gray-500 mt-1 text-right">${timeString}</div>
            </div>
        </div>
        `;
    }
    chatBox.innerHTML += messageHTML;
}

// Fungsi utama Long Polling
async function startPolling(lastMessageId) {
    if (!chatBox) return; // Hentikan jika chatbox tidak ada

    try {
        const response = await fetch(
            `index.php?page=check-new-messages&forum_id=${FORUM_ID}&last_message_id=${lastMessageId}`);
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
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


// Intersep Form Kirim Pesan (AJAX)
if (chatForm && messageInput) {
    chatForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const isiPesan = messageInput.value;
        if (isiPesan.trim() === '') return;

        const formData = new FormData(chatForm);
        messageInput.value = '';

        try {
            const response = await fetch('index.php?page=store-message', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error('Gagal mengirim pesan');
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
            messageInput.value = isiPesan;
        }
    });
}


// --- Inisialisasi ---
if (chatBox) {
    scrollToBottom();
    let initialLastId = parseInt(chatBox.dataset.lastMessageId, 10);
    startPolling(initialLastId);
}

<?php endif; ?>
</script>