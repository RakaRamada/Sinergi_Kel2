<?php
// File: app/views/partials/tab_chat.php
// VERSI FINAL: Logic Reply Dokumen, Modal Hapus, Struktur Rapih

$group_id = $current_group_id; 
$last_message_id = 0;
if (isset($messages) && !empty($messages)) {
    $last_message_id = end($messages)['message_id'] ?? 0;
}
?>

<div class="flex flex-col h-full bg-gray-100 relative">

    <div id="chat-box" class="flex-1 overflow-y-auto p-4 space-y-2 custom-scrollbar"
        data-last-message-id="<?= $last_message_id ?>">

        <?php if (isset($messages) && is_array($messages) && !empty($messages)): ?>
        <?php foreach ($messages as $i => $message): ?>
        <?php
            // Logic Tanggal (Separator)
            $tanggal_pesan_ini = formatTanggalChat($message['created_at_iso'] ?? null);
            $tanggal_pesan_sebelumnya = ($i > 0) ? formatTanggalChat($messages[$i-1]['created_at_iso'] ?? null) : null;

            if ($tanggal_pesan_ini && $tanggal_pesan_ini !== $tanggal_pesan_sebelumnya):
        ?>
        <div class="flex justify-center my-4 sticky top-2 z-10 pointer-events-none">
            <span
                class="bg-gray-200 text-gray-600 text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wide">
                <?= htmlspecialchars($tanggal_pesan_ini) ?>
            </span>
        </div>
        <?php endif; ?>

        <?php 
            // Variabel Dasar Pesan
            $msg_type = $message['message_type'] ?? 'text';
            $msg_id = $message['message_id'] ?? 0; 
            $sender_id = $message['sender_id'] ?? null;
            $isi_pesan_string = trim($message['isi_pesan'] ?? '');
            $created_at_time = $message['created_at_time'] ?? ''; 
            $sender_nama = $message['sender_nama'] ?? 'User';
            $is_my_message = ($sender_id == $_SESSION['user_id']);
            $file_url = '/Sinergi/public/uploads/group_files/' . ($message['file_path'] ?? '');

            // --- FIX PENTING: LOGIKA TEKS UNTUK REPLY & PREVIEW ---
            // Tentukan apa teks representasi pesan ini (jika direply nanti)
            $reply_data_text = $isi_pesan_string;
            
            // Jika teks kosong, cek tipe pesan (Gambar/Dokumen)
            if (empty($reply_data_text)) {
                if ($msg_type === 'image') {
                    $reply_data_text = '📷 [Gambar]';
                } elseif ($msg_type === 'document') {
                    $docName = $message['original_filename'] ?? 'Dokumen';
                    $reply_data_text = '📄 ' . $docName;
                }
            }
            
            // Siapkan variable JS safe
            $js_reply_text = htmlspecialchars($reply_data_text, ENT_QUOTES);
            $js_sender_name = htmlspecialchars($sender_nama, ENT_QUOTES);
            // -----------------------------------------------------

            // Pesan Sistem (Join/Leave) -> Tampilan Beda
            if ($msg_type === 'join' || $msg_type === 'leave'):
        ?>
        <div class="flex justify-center my-2">
            <div class="bg-gray-200 text-gray-500 text-[10px] px-3 py-1 rounded-full font-medium">
                <?= htmlspecialchars($isi_pesan_string) ?>
            </div>
        </div>
        <?php continue; endif; ?>

        <?php
            // Style Bubble (Kanan/Kiri)
            if ($is_my_message) {
                $align_class = 'justify-end';
                $bubble_class = 'bg-black text-white rounded-l-xl rounded-br-xl rounded-tr-none shadow-sm';
                $text_color = 'text-gray-100';
                $time_color = 'text-gray-400';
                $sender_html = ''; 
                $reply_bg = 'bg-gray-800 border-gray-600';
            } else {
                $align_class = 'justify-start';
                $bubble_class = 'bg-white text-gray-900 rounded-r-xl rounded-bl-xl rounded-tl-none border border-gray-200 shadow-sm';
                $text_color = 'text-gray-800';
                $time_color = 'text-gray-400';
                $sender_html = '<p class="text-[11px] font-bold mb-0.5 text-orange-600 leading-none">'.htmlspecialchars($sender_nama).'</p>';
                $reply_bg = 'bg-gray-100 border-gray-300';
            }
        ?>

        <div class="flex <?= $align_class ?> group/msg relative w-full" id="message-<?= $msg_id ?>">
            <div class="relative max-w-[85%] sm:max-w-[65%] min-w-[100px]">

                <button onclick="toggleMessageMenu(event, 'menu-<?= $msg_id ?>')"
                    class="absolute top-0 right-0 m-1 p-1 rounded-full bg-black/10 hover:bg-black/20 text-gray-500 opacity-0 group-hover/msg:opacity-100 transition z-20">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </button>

                <div id="menu-<?= $msg_id ?>"
                    class="hidden absolute top-6 right-0 bg-white shadow-xl rounded-lg border border-gray-100 w-32 z-50 overflow-hidden py-1 message-dropdown">

                    <button
                        onclick="handleReply(<?= $msg_id ?>, '<?= $js_sender_name ?>', '<?= substr($js_reply_text, 0, 50) ?>')"
                        class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path>
                        </svg>
                        Balas
                    </button>

                    <?php if ($is_my_message): ?>
                    <button onclick="openDeleteModal(<?= $msg_id ?>)"
                        class="w-full text-left px-3 py-2 text-xs text-red-600 hover:bg-red-50 flex items-center gap-2">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                            </path>
                        </svg>
                        Hapus
                    </button>
                    <?php endif; ?>
                </div>

                <div class="<?= $bubble_class ?> px-3 py-1.5 flex flex-col">
                    <?= $sender_html ?>

                    <?php if (!empty($message['reply_to_message_id'])): ?>
                    <?php 
                            // Teks Pesan Asli yang dibalas
                            $repText = $message['replied_message_text']; 
                            
                            // Jika teks asli kosong (misal yang dibalas itu Gambar/Doc), kita labeli manual
                            if (empty(trim($repText))) {
                                $rType = $message['replied_message_type'] ?? 'text';
                                if ($rType === 'image') $repText = '📷 [Gambar]';
                                elseif ($rType === 'document') $repText = '📄 ' . ($message['replied_filename'] ?? 'Dokumen');
                            }
                        ?>
                    <div class="mb-1 rounded p-1 text-[10px] border-l-2 <?= $reply_bg ?> opacity-90 bg-opacity-50">
                        <span
                            class="font-bold block text-blue-500"><?= htmlspecialchars($message['replied_sender_nama']) ?></span>
                        <span class="truncate block opacity-80"><?= htmlspecialchars($repText) ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($msg_type === 'image'): ?>
                    <a href="<?= $file_url ?>" target="_blank" class="block mb-1 mt-1">
                        <img src="<?= $file_url ?>" class="rounded-lg w-full h-auto max-h-64 object-cover">
                    </a>
                    <?php if(!empty($isi_pesan_string)): ?>
                    <p class="text-sm <?= $text_color ?> mb-1 leading-snug">
                        <?= nl2br(htmlspecialchars($isi_pesan_string)) ?></p>
                    <?php endif; ?>

                    <?php elseif ($msg_type === 'document'): ?>
                    <a href="<?= $file_url ?>" download="<?= htmlspecialchars($message['original_filename']) ?>"
                        class="flex items-center gap-3 bg-gray-500/10 p-2 rounded-lg mb-1 mt-1">
                        <div class="bg-white p-1.5 rounded-full"><svg class="w-4 h-4 text-black" fill="none"
                                stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg></div>
                        <div class="text-xs truncate underline"><?= htmlspecialchars($message['original_filename']) ?>
                        </div>
                    </a>

                    <?php else: ?>
                    <p class="text-sm <?= $text_color ?> leading-snug whitespace-normal break-words">
                        <?= nl2br(htmlspecialchars($isi_pesan_string)) ?></p>
                    <?php endif; ?>

                    <div class="text-[9px] <?= $time_color ?> self-end mt-0.5 leading-none select-none">
                        <?= htmlspecialchars($created_at_time) ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="flex flex-col items-center justify-center h-full text-gray-400">
            <p class="text-sm">Belum ada percakapan.</p>
        </div>
        <?php endif; ?>
    </div>

    <div class="w-full bg-white border-t border-gray-200 px-3 py-2 z-20 shrink-0 relative">

        <div id="reply-preview-area"
            class="hidden absolute bottom-full left-0 w-full bg-gray-50 border-t border-gray-200 p-2 shadow-sm z-10">
        </div>

        <div id="file-preview-area"
            class="hidden absolute bottom-full left-4 mb-2 bg-white p-1 rounded-lg shadow-lg border z-20"></div>

        <form id="chat-form" method="POST" class="flex items-end gap-2" enctype="multipart/form-data">
            <input type="hidden" name="forum_id" value="<?= $group_id ?>">

            <input type="hidden" name="reply_to_message_id" id="reply-input-id" value="">

            <label id="attach-btn"
                class="p-2 text-gray-500 hover:bg-gray-100 rounded-full cursor-pointer transition shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13">
                    </path>
                </svg>
                <input type="file" id="file-upload-input" name="file_upload" class="hidden">
            </label>

            <div class="flex-1 bg-gray-100 rounded-2xl flex items-center px-4 py-1">
                <textarea id="message-input" name="isi_pesan" placeholder="Ketik pesan..."
                    class="w-full bg-transparent border-none focus:ring-0 text-sm resize-none overflow-hidden py-2"
                    style="min-height: 24px; max-height: 100px;" rows="1"></textarea>
            </div>

            <button type="submit" id="send-btn"
                class="p-2 bg-black text-white rounded-full hover:bg-gray-800 transition shrink-0 shadow-sm flex items-center justify-center">
                <svg class="w-5 h-5 ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
                        transform="rotate(90 12 12)" />
                </svg>
            </button>
        </form>
    </div>
</div>

<div id="deleteModal"
    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 backdrop-blur-sm transition-opacity">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm transform scale-100 transition-transform">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-bold text-gray-900" id="modal-title">Hapus Pesan?</h3>
            <div class="mt-2">
                <p class="text-sm text-gray-500">
                    Pesan ini akan dihapus secara permanen dan tidak dapat dikembalikan.
                </p>
            </div>
        </div>
        <div class="mt-5 sm:mt-6 flex gap-3">
            <button type="button" onclick="closeDeleteModal()"
                class="w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:text-sm transition">
                Batal
            </button>
            <button type="button" id="confirmDeleteBtn"
                class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:text-sm transition">
                Hapus
            </button>
        </div>
    </div>
</div>

<script src="/Sinergi/public/assets/js/chat_app.js?v=<?= time() ?>"></script>