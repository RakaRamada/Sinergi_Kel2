<?php
// File: app/views/partials/tab_chat.php
// VERSI FINAL: Support Multi Image, Dokumen, Caption, Reply Preview, & Delete Modal

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
            // Logic Separator Tanggal
            $tanggal_pesan_ini = formatTanggalChat($message['created_at_iso'] ?? null);
            $tanggal_pesan_sebelumnya = ($i > 0) ? formatTanggalChat($messages[$i-1]['created_at_iso'] ?? null) : null;

            if ($tanggal_pesan_ini && $tanggal_pesan_ini !== $tanggal_pesan_sebelumnya):
        ?>
        <div class="flex justify-center my-4 sticky top-2 z-10 pointer-events-none">
            <span
                class="bg-gray-200 text-gray-600 text-[10px] font-bold px-3 py-1 rounded-full uppercase tracking-wide shadow-sm backdrop-blur-sm bg-opacity-80">
                <?= htmlspecialchars($tanggal_pesan_ini) ?>
            </span>
        </div>
        <?php endif; ?>

        <?php 
            // Variabel Helper
            $msg_type = $message['message_type'] ?? 'text';
            $msg_id = $message['message_id'] ?? 0; 
            $sender_id = $message['sender_id'] ?? null;
            
            // Handle CLOB/Text Content
            $isi_pesan_string = '';
            if (isset($message['isi_pesan'])) {
                 if (is_object($message['isi_pesan']) && $message['isi_pesan'] instanceof OCILob) {
                     $isi_pesan_string = $message['isi_pesan']->read($message['isi_pesan']->size());
                 } else {
                     $isi_pesan_string = $message['isi_pesan'];
                 }
            }
            $isi_pesan_string = trim($isi_pesan_string);

            $created_at_time = $message['created_at_time'] ?? ''; 
            $sender_nama = $message['sender_nama'] ?? 'User';
            $is_my_message = ($sender_id == $_SESSION['user_id']);

            // --- LOGIC REPLY DATA ---
            // Teks untuk preview saat tombol reply ditekan
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
            
            // Escape untuk Javascript
            $js_reply_text = htmlspecialchars($reply_data_text, ENT_QUOTES);
            $js_sender_name = htmlspecialchars($sender_nama, ENT_QUOTES);

            // Pesan Sistem (Join/Leave)
            if ($msg_type === 'join' || $msg_type === 'leave'):
        ?>
        <div class="flex justify-center my-2">
            <div class="bg-gray-200 text-gray-500 text-[10px] px-3 py-1 rounded-full font-medium italic">
                <?= htmlspecialchars($isi_pesan_string) ?>
            </div>
        </div>
        <?php continue; endif; ?>

        <?php
            // Style Bubble
            if ($is_my_message) {
                $align_class = 'justify-end';
                $bubble_class = 'bg-black text-white rounded-l-xl rounded-br-xl rounded-tr-none shadow-sm';
                $text_color = 'text-gray-100';
                $time_color = 'text-gray-400';
                $sender_html = ''; 
                $reply_bg = 'bg-gray-800 border-gray-600 text-gray-300';
            } else {
                $align_class = 'justify-start';
                $bubble_class = 'bg-white text-gray-900 rounded-r-xl rounded-bl-xl rounded-tl-none border border-gray-200 shadow-sm';
                $text_color = 'text-gray-800';
                $time_color = 'text-gray-400';
                $sender_html = '<p class="text-[11px] font-bold mb-0.5 text-orange-600 leading-none">'.htmlspecialchars($sender_nama).'</p>';
                $reply_bg = 'bg-gray-100 border-gray-300 text-gray-600';
            }
        ?>

        <div class="flex <?= $align_class ?> group/msg relative w-full mb-4" id="message-<?= $msg_id ?>">
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

                <div class="<?= $bubble_class ?> px-3 py-2 flex flex-col">
                    <?= $sender_html ?>

                    <?php if (!empty($message['reply_to_message_id'])): ?>
                    <?php 
                        $repText = $message['replied_message_text']; 
                        // Labeli manual jika teks reply kosong
                        if (empty(trim($repText))) {
                            $rType = $message['replied_message_type'] ?? 'text';
                            if ($rType === 'image') $repText = '📷 [Gambar]';
                            elseif ($rType === 'document') $repText = '📄 ' . ($message['replied_filename'] ?? 'Dokumen');
                        }
                    ?>
                    <div class="mb-1 rounded p-1.5 text-[10px] border-l-2 <?= $reply_bg ?> bg-opacity-50 select-none">
                        <span
                            class="font-bold block text-blue-500 mb-0.5"><?= htmlspecialchars($message['replied_sender_nama']) ?></span>
                        <span class="truncate block opacity-80"><?= htmlspecialchars($repText) ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($msg_type === 'image'): ?>
                    <?php 
                            $rawPath = $message['file_path'] ?? '';
                            $images = !empty($rawPath) ? explode(',', $rawPath) : [];
                            $imgCount = count($images);
                            $jsonImages = htmlspecialchars(json_encode($images), ENT_QUOTES, 'UTF-8');
                            $coverImage = isset($images[0]) ? trim($images[0]) : '';
                        ?>
                    <?php if(!empty($coverImage)): ?>
                    <div class="relative mt-1 mb-2 group cursor-pointer max-w-[300px]">
                        <div class="relative overflow-hidden rounded-xl border border-gray-200 shadow-sm">
                            <img src="<?= $coverImage ?>" onclick='openGallery(<?= $jsonImages ?>, 0)'
                                class="w-full h-64 object-cover transition transform duration-500 group-hover:scale-105">

                            <?php if($imgCount > 1): ?>
                            <div
                                class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition pointer-events-none">
                            </div>
                            <div
                                class="absolute bottom-2 right-2 bg-black/70 text-white text-xs px-3 py-1.5 rounded-full font-bold backdrop-blur-sm pointer-events-none shadow-md">
                                +<?= $imgCount - 1 ?> Foto
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php elseif ($msg_type === 'document'): ?>
                    <?php 
                            $docPaths = explode(',', $message['file_path'] ?? '');
                            $docNames = explode(',', $message['original_filename'] ?? '');
                        ?>
                    <div class="flex flex-col gap-2 mt-1 mb-2 max-w-[300px]">
                        <?php foreach($docPaths as $idx => $path): ?>
                        <?php 
                                    $fullPath = trim($path);
                                    if(empty($fullPath)) continue;
                                    if (strpos($fullPath, '/Sinergi') === false) $fullPath = '/Sinergi/public/uploads/group_files/' . $fullPath;
                                    
                                    $showName = isset($docNames[$idx]) && !empty(trim($docNames[$idx])) ? trim($docNames[$idx]) : basename($fullPath);
                                    $ext = strtolower(pathinfo($fullPath, PATHINFO_EXTENSION));
                                    $iconColor = ($ext == 'pdf') ? 'text-red-500 bg-red-50' : 'text-blue-500 bg-blue-50';
                                    
                                    // Teks Dokumen
                                    $docTextColor = $is_my_message ? 'text-gray-900' : 'text-gray-800';
                                ?>
                        <a href="<?= $fullPath ?>" target="_blank"
                            class="flex items-center gap-3 p-3 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition group/doc text-left shadow-sm">
                            <div
                                class="w-10 h-10 rounded-lg flex items-center justify-center shadow-sm <?= $iconColor ?>">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-bold <?= $docTextColor ?> truncate" title="<?= $showName ?>">
                                    <?= $showName ?></p>
                                <p class="text-[10px] text-gray-500 uppercase font-semibold tracking-wider"><?= $ext ?>
                                    FILE</p>
                            </div>
                            <div class="text-gray-400 group-hover:text-blue-600 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                </svg>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    </div>

                    <?php endif; ?>

                    <?php if (!empty($isi_pesan_string)): ?>
                    <div class="text-sm <?= $text_color ?> leading-snug whitespace-normal break-words">
                        <?= nl2br(htmlspecialchars($isi_pesan_string)) ?>
                    </div>
                    <?php endif; ?>

                    <div class="flex items-center justify-end gap-1 mt-1 select-none opacity-80">
                        <span class="text-[9px] <?= $time_color ?>">
                            <?= htmlspecialchars($created_at_time) ?>
                        </span>
                        <?php if($is_my_message): ?>
                        <svg class="w-3 h-3 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="flex flex-col items-center justify-center h-full text-gray-400">
            <svg class="w-16 h-16 mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                </path>
            </svg>
            <p class="text-sm">Belum ada percakapan. Mulai sapa teman-teman!</p>
        </div>
        <?php endif; ?>
    </div>

    <div
        class="w-full bg-white border-t border-gray-200 px-3 py-2 z-20 shrink-0 relative shadow-[0_-2px_10px_rgba(0,0,0,0.05)]">

        <div id="reply-preview-area"
            class="hidden absolute bottom-full left-0 w-full bg-gray-50 border-t border-gray-200 p-2 shadow-sm z-10">
        </div>

        <div id="file-preview-area"
            class="hidden absolute bottom-full left-4 mb-2 bg-white p-1 rounded-lg shadow-lg border z-20"></div>

        <form id="chat-form" method="POST" class="flex items-end gap-2" enctype="multipart/form-data">
            <input type="hidden" name="group_id" value="<?= $group_id ?>">
            <input type="hidden" name="reply_to_message_id" id="reply-input-id" value="">

            <label id="attach-btn"
                class="p-2 text-gray-500 hover:bg-gray-100 rounded-full cursor-pointer transition shrink-0 transform active:scale-95">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                        d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13">
                    </path>
                </svg>
                <input type="file" id="file-upload-input" name="file_upload[]" class="hidden" multiple>
            </label>

            <div
                class="flex-1 bg-gray-100 rounded-2xl flex items-center px-4 py-1 border border-transparent focus-within:border-gray-300 focus-within:bg-white transition">
                <textarea id="message-input" name="isi_pesan" placeholder="Ketik pesan..."
                    class="w-full bg-transparent border-none focus:ring-0 text-sm resize-none overflow-hidden py-2 leading-relaxed text-gray-800"
                    style="min-height: 24px; max-height: 100px;" rows="1"></textarea>
            </div>

            <button type="submit" id="send-btn" disabled
                class="p-2 bg-black text-white rounded-full hover:bg-gray-800 transition shrink-0 shadow-sm flex items-center justify-center disabled:opacity-50 disabled:cursor-not-allowed transform active:scale-95">
                <svg class="w-5 h-5 ml-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"
                        transform="rotate(90 12 12)" />
                </svg>
            </button>
        </form>
    </div>

</div>
</div>

<div id="deleteModal"
    class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm transition-opacity opacity-0"
    aria-modal="true">

    <div id="deleteChatContent"
        class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-sm transform scale-95 transition-transform duration-200">
        <div class="text-center">
            <div class="mx-auto flex items-center justify-center h-14 w-14 rounded-full bg-red-100 mb-4">
                <svg class="h-8 w-8 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                </svg>
            </div>
            <h3 class="text-lg leading-6 font-bold text-gray-900">Hapus Pesan?</h3>
            <p class="text-sm text-gray-500 mt-2">
                Pesan ini akan dihapus secara permanen.
            </p>
        </div>
        <div class="mt-6 flex gap-3">
            <button type="button" onclick="window.closeDeleteModal()"
                class="w-full inline-flex justify-center rounded-xl border border-gray-300 shadow-sm px-4 py-2.5 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 transition cursor-pointer">
                Batal
            </button>
            <button type="button" id="confirmDeleteBtn"
                class="w-full inline-flex justify-center rounded-xl border border-transparent shadow-sm px-4 py-2.5 bg-red-600 text-base font-medium text-white hover:bg-red-700 transition cursor-pointer shadow-red-200">
                Hapus
            </button>
        </div>
    </div>
</div>

<div id="imageGalleryModal"
    class="hidden fixed inset-0 z-[9999] bg-black/95 flex flex-col justify-center items-center backdrop-blur-md transition-opacity opacity-0"
    aria-modal="true">

    <button onclick="closeGallery()"
        class="absolute top-4 right-4 text-white/70 hover:text-white p-2 z-[10000] transition bg-white/10 rounded-full hover:bg-white/20 cursor-pointer">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </button>

    <div class="relative w-full h-full flex items-center justify-center p-4">

        <button id="galleryPrevBtn" onclick="navigateGallery(-1)"
            class="absolute left-4 text-white/50 hover:text-white hover:scale-110 transition p-2 z-[105] cursor-pointer">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
            </svg>
        </button>

        <img id="galleryImage" src=""
            class="max-w-full max-h-full object-contain shadow-2xl transition-transform duration-300 select-none">

        <button id="galleryNextBtn" onclick="navigateGallery(1)"
            class="absolute right-4 text-white/50 hover:text-white hover:scale-110 transition p-2 z-[105] cursor-pointer">
            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
            </svg>
        </button>
    </div>

    <div class="absolute bottom-6 text-white font-medium bg-black/50 px-4 py-1 rounded-full text-sm backdrop-blur-sm">
        <span id="galleryCounter">1 / 1</span>
    </div>
</div>