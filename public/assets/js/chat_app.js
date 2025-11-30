/*
 * ============================================
 * File: /public/assets/js/chat_app.js
 * Kode JavaScript Chat Sinergi (Fixed Clean)
 * Fitur: Realtime Polling, Kirim Pesan, Reply, Delete, Clean Text
 * ============================================
 */

// --- 1. INISIALISASI VARIABEL ---
const chatBox = document.getElementById("chat-box");
const chatForm = document.getElementById("chat-form");
const messageInput = document.getElementById("message-input");
const fileInput = document.getElementById("file-upload-input");
const filePreviewArea = document.getElementById("file-preview-area");
const replyPreviewArea = document.getElementById("reply-preview-area");
const sendBtn = document.getElementById("send-btn");

let stagedFile = null;
let replyingToMessage = null;

// --- 2. FUNGSI HELPER UTAMA ---

function escapeHTML(str) {
  if (typeof str !== "string") return "";
  return str.replace(/[&<>"']/g, function (m) {
    return {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#039;",
    }[m];
  });
}

function scrollToBottom() {
  if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
}

// --- 3. FUNGSI APPEND MESSAGE (MENAMPILKAN PESAN) ---
function appendMessage(message) {
  if (!chatBox) return;
  if (document.getElementById(`message-${message.message_id}`)) return;

  const isMyMessage = message.sender_id == CURRENT_USER_ID;
  const msg_type = message.message_type || "text";
  const isi_pesan = escapeHTML((message.isi_pesan || "").trim()); // TRIM PENTING
  const timeString = escapeHTML(message.created_at_time || "");
  const sender_nama = escapeHTML(message.sender_nama || "User");
  const file_path = escapeHTML(message.file_path || "");
  const original_filename = escapeHTML(message.original_filename || "");
  const file_url = `/Sinergi/public/uploads/group_files/${file_path}`;

  // 1. Pesan Sistem
  if (msg_type === "join" || msg_type === "leave") {
    // HTML One-Liner
    const sysHtml = `<div class="text-center text-xs text-gray-500 my-2 font-medium">${isi_pesan}</div>`;
    chatBox.insertAdjacentHTML("beforeend", sysHtml);
    return;
  }

  // 2. Config Style
  let align_class, bubble_class, text_class, meta_class, sender_html, reply_bg;

  if (isMyMessage) {
    align_class = "justify-end";
    bubble_class =
      "bg-black text-white rounded-l-xl rounded-br-xl rounded-tr-none shadow-sm";
    text_class = "text-gray-100";
    meta_class = "text-gray-400";
    sender_html = "";
    reply_bg = "bg-gray-800 border-gray-600";
  } else {
    align_class = "justify-start";
    bubble_class =
      "bg-white text-gray-900 rounded-r-xl rounded-bl-xl rounded-tl-none border border-gray-200 shadow-sm";
    text_class = "text-gray-800";
    meta_class = "text-gray-400";
    // One-liner tanpa spasi
    sender_html = `<p class="text-[11px] font-bold mb-0.5 text-orange-600 leading-none">${sender_nama}</p>`;
    reply_bg = "bg-gray-100 border-gray-300";
  }

  // 3. Konten Reply (One-Liner)
  let reply_html = "";
  if (message.reply_to_message_id) {
    const repSender =
      message.replied_sender_nama === CURRENT_USER_NAME
        ? "Anda"
        : escapeHTML(message.replied_sender_nama);
    reply_html = `<div class="mb-1 rounded p-1 text-[10px] border-l-2 ${reply_bg} opacity-90"><span class="font-bold block text-blue-500">${repSender}</span><span class="truncate block opacity-80">${escapeHTML(
      message.replied_message_text
    )}</span></div>`;
  }

  // 4. Konten Utama (One-Liner)
  let content_html = "";
  if (msg_type === "image") {
    content_html = `<a href="${file_url}" target="_blank" class="block mb-1 mt-1"><img src="${file_url}" class="rounded-lg w-full h-auto max-h-64 object-cover"></a>`;
    if (isi_pesan)
      content_html += `<p class="text-sm ${text_class} mb-1 leading-snug">${isi_pesan}</p>`;
  } else if (msg_type === "document") {
    content_html = `<a href="${file_url}" download="${original_filename}" class="flex items-center gap-3 bg-gray-500/10 p-2 rounded-lg mb-1 mt-1"><div class="bg-white p-1.5 rounded-full"><svg class="w-4 h-4 text-black" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg></div><div class="text-xs truncate underline">${original_filename}</div></a>`;
    if (isi_pesan)
      content_html += `<p class="text-sm ${text_class} leading-snug whitespace-pre-wrap break-words">${isi_pesan}</p>`;
  } else {
    // FIX SPASI: Tidak ada spasi antara tag P dan konten
    content_html = `<p class="text-sm ${text_class} leading-snug whitespace-pre-wrap break-words">${isi_pesan}</p>`;
  }

  const js_reply_text = escapeHTML(
    (msg_type === "text" ? isi_pesan : original_filename).substring(0, 50)
  ).replace(/'/g, "\\'");
  const js_sender_name = sender_nama.replace(/'/g, "\\'");

  // 5. Dropdown Menu (One-Liner Template)
  const dropdown_menu = `<div id="menu-${
    message.message_id
  }" class="hidden absolute top-6 right-0 bg-white shadow-xl rounded-lg border border-gray-100 w-32 z-50 overflow-hidden py-1 message-dropdown"><button onclick="handleReply(${
    message.message_id
  }, '${js_sender_name}', '${js_reply_text}')" class="w-full text-left px-3 py-2 text-xs text-gray-700 hover:bg-gray-50 flex items-center gap-2"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg> Balas</button>${
    isMyMessage
      ? `<button onclick="handleDelete(${message.message_id})" class="w-full text-left px-3 py-2 text-xs text-red-600 hover:bg-red-50 flex items-center gap-2"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg> Hapus</button>`
      : ""
  }</div>`;

  // 6. RAKIT HTML FINAL (One-Liner untuk mencegah spasi hantu)
  const finalHtml = `<div class="flex ${align_class} group/msg relative w-full" id="message-${message.message_id}"><div class="relative max-w-[85%] sm:max-w-[65%] min-w-[100px]"><button onclick="toggleMessageMenu(event, 'menu-${message.message_id}')" class="absolute top-0 right-0 m-1 p-1 rounded-full bg-black/10 hover:bg-black/20 text-gray-500 opacity-0 group-hover/msg:opacity-100 transition z-20"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg></button>${dropdown_menu}<div class="${bubble_class} px-3 py-1.5 flex flex-col">${sender_html}${reply_html}${content_html}<div class="text-[9px] ${meta_class} self-end mt-0.5 leading-none select-none">${timeString}</div></div></div></div>`;

  chatBox.insertAdjacentHTML("beforeend", finalHtml);
}

// --- 4. FUNGSI POLLING REALTIME ---
async function startPolling(lastMessageId) {
  let newLastId = lastMessageId;
  try {
    const response = await fetch(
      `index.php?page=check-new-messages&group_id=${GROUP_ID}&forum_id=${GROUP_ID}&last_message_id=${lastMessageId}`
    );
    if (!response.ok) throw new Error("Server Error");
    const newMessages = await response.json();
    if (newMessages && newMessages.length > 0) {
      newMessages.forEach((msg) => {
        appendMessage(msg);
        newLastId = msg.message_id;
      });
      if (chatBox) chatBox.dataset.lastMessageId = newLastId;
      scrollToBottom();
    }
  } catch (error) {
    setTimeout(() => startPolling(newLastId), 3000);
    return;
  }
  setTimeout(() => startPolling(newLastId), 1000);
}

// --- 5. LOGIC PREVIEW & REPLY ---
function showFilePreview(file) {
  if (!file) {
    filePreviewArea.innerHTML = "";
    filePreviewArea.classList.add("hidden");
    return;
  }
  filePreviewArea.classList.remove("hidden");
  const isImage = file.type.startsWith("image/");
  const iconSrc = isImage
    ? URL.createObjectURL(file)
    : "/Sinergi/public/assets/icons/document.svg";

  // HTML One-Liner
  filePreviewArea.innerHTML = `<div class="flex items-center p-2 bg-gray-100 rounded-lg border border-gray-300"><img src="${iconSrc}" class="${
    isImage ? "w-10 h-10 object-cover" : "w-8 h-8"
  } rounded mr-3"><div class="flex-1 overflow-hidden"><p class="text-xs font-bold truncate">${escapeHTML(
    file.name
  )}</p><p class="text-[10px] text-gray-500">${(file.size / 1024).toFixed(
    1
  )} KB</p></div><button type="button" id="cancel-file-btn" class="ml-2 p-1 text-gray-400 hover:text-red-500"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button></div>`;

  document.getElementById("cancel-file-btn").addEventListener("click", () => {
    stagedFile = null;
    fileInput.value = null;
    showFilePreview(null);
  });
}

function showReplyPreview(message) {
  if (!message) {
    cancelReply();
    return;
  }
  replyingToMessage = message;
  const senderDisplay =
    message.name === CURRENT_USER_NAME ? "Anda" : escapeHTML(message.name);

  // HTML One-Liner
  replyPreviewArea.innerHTML = `<div class="flex items-center justify-between bg-gray-100 p-2 rounded-lg border-l-4 border-black"><div class="overflow-hidden"><p class="font-bold text-xs text-blue-600 mb-0.5">Membalas ${senderDisplay}</p><p class="text-xs text-gray-600 truncate">${escapeHTML(
    message.text
  )}</p></div><button type="button" id="cancel-reply-btn" class="ml-2 text-gray-400 hover:text-red-500 p-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg></button></div>`;

  replyPreviewArea.classList.remove("hidden");
  document
    .getElementById("cancel-reply-btn")
    .addEventListener("click", cancelReply);
}

function cancelReply() {
  replyingToMessage = null;
  replyPreviewArea.innerHTML = "";
  replyPreviewArea.classList.add("hidden");
}

// --- 6. EVENT LISTENERS ---
if (chatForm && messageInput) {
  if (fileInput) {
    fileInput.addEventListener("change", (e) => {
      if (e.target.files && e.target.files.length > 0) {
        stagedFile = e.target.files[0];
        showFilePreview(stagedFile);
      }
    });
  }

  messageInput.addEventListener("keydown", (e) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      if (sendBtn) sendBtn.click();
    }
  });

  chatForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const isiPesan = messageInput.value.trim(); // TRIM DISINI JUGA
    const hiddenInput = chatForm.querySelector('input[name="forum_id"]');
    const targetId = hiddenInput ? hiddenInput.value : GROUP_ID;

    if (isiPesan === "" && !stagedFile) return;

    const formData = new FormData();
    formData.append("forum_id", targetId);
    formData.append("isi_pesan", isiPesan);

    if (stagedFile) formData.append("file_upload", stagedFile, stagedFile.name);
    if (replyingToMessage)
      formData.append("reply_to_message_id", replyingToMessage.id);

    // Reset UI
    messageInput.value = "";
    showFilePreview(null);
    cancelReply();
    stagedFile = null;
    if (fileInput) fileInput.value = null;

    try {
      await fetch("index.php?page=store-message", {
        method: "POST",
        body: formData,
      });
      scrollToBottom();
    } catch (error) {
      console.error(error);
      alert("Gagal kirim pesan");
    }
  });
}

// --- 7. STARTUP ---
if (chatBox) {
  setTimeout(() => {
    scrollToBottom();
    let initialLastId = parseInt(chatBox.dataset.lastMessageId, 10) || 0;
    startPolling(initialLastId);
  }, 100);
}
