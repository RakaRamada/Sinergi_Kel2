/*
 * ============================================
 * File: /public/assets/js/chat_app.js
 * Kode JavaScript lengkap untuk aplikasi chat Sinergi
 * VERSI FINAL V4 (Metode "Tanda Manual" oleh Dinda)
 *
 * PERUBAHAN UTAMA:
 * 1. initializeFlowbite() diubah total.
 * 2. Sekarang menggunakan atribut 'data-fb-init' untuk menandai
 * dropdown yang sudah aktif.
 * 3. Ini 100% akan mengaktifkan dropdown baru tanpa error.
 * ============================================
 */

// --- 1. AMBIL SEMUA ELEMEN DOM PENTING ---
const chatBox = document.getElementById("chat-box");
const chatForm = document.getElementById("chat-form");
const messageInput = document.getElementById("message-input");
const attachBtn = document.getElementById("attach-btn");
const fileInput = document.getElementById("file-upload-input");
const filePreviewArea = document.getElementById("file-preview-area");
const replyPreviewArea = document.getElementById("reply-preview-area");
const sendBtn = document.getElementById("send-btn");
const deleteModal = document.getElementById("delete-confirm-modal");
const modalBtnCancel = document.getElementById("modal-btn-cancel");
const modalBtnConfirmDelete = document.getElementById(
  "modal-btn-confirm-delete"
);
const stickyDateHeader = document.getElementById("sticky-date-header");
const stickyDateSpan = stickyDateHeader
  ? stickyDateHeader.querySelector("span")
  : null;
let currentStickyDate = null;
let stagedFile = null;
let replyingToMessage = null;
let messageIdPendingDelete = null;
let lastPrintedDate = "";

// --- 2. FUNGSI-FUNGSI HELPER ---

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

function formatTanggalChatJS(tanggalISO) {
  if (!tanggalISO) return null;
  try {
    const msgDateObj = new Date(tanggalISO);
    const msg_y = msgDateObj.getFullYear();
    const msg_m = String(msgDateObj.getMonth() + 1).padStart(2, "0");
    const msg_d = String(msgDateObj.getDate()).padStart(2, "0");
    const msg_date_str = `${msg_y}-${msg_m}-${msg_d}`;

    const todayObj = new Date();
    const today_y = todayObj.getFullYear();
    const today_m = String(todayObj.getMonth() + 1).padStart(2, "0");
    const today_d = String(todayObj.getDate()).padStart(2, "0");
    const today_str = `${today_y}-${today_m}-${today_d}`;

    const yesterdayObj = new Date();
    yesterdayObj.setDate(todayObj.getDate() - 1);
    const yest_y = yesterdayObj.getFullYear();
    const yest_m = String(yesterdayObj.getMonth() + 1).padStart(2, "0");
    const yest_d = String(yesterdayObj.getDate()).padStart(2, "0");
    const yesterday_str = `${yest_y}-${yest_m}-${yest_d}`;

    if (msg_date_str === today_str) {
      return "Hari ini";
    } else if (msg_date_str === yesterday_str) {
      return "Kemarin";
    }

    const today_time = new Date(today_str).getTime();
    const msg_time = new Date(msg_date_str).getTime();
    const diff_time = today_time - msg_time;
    const diff_days_manual = Math.ceil(diff_time / (1000 * 60 * 60 * 24));

    if (diff_days_manual > 1 && diff_days_manual < 7) {
      const hari = [
        "Minggu",
        "Senin",
        "Selasa",
        "Rabu",
        "Kamis",
        "Jumat",
        "Sabtu",
      ];
      return hari[msgDateObj.getDay()];
    } else {
      return `${msg_d}/${msg_m}/${msg_y}`;
    }
  } catch (e) {
    console.error("Error formatTanggalChatJS:", e);
    return null;
  }
}

function scrollToBottom() {
  if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
}

// --- 3. FUNGSI UTAMA APLIKASI ---

/**
 * =======================================================
 * FUNGSI BARU (Akal-akalan V4 - Tanda Manual)
 * =======================================================
 * Ini adalah perbaikan final.
 */
function initializeFlowbite() {
  // Cek dulu apakah class Dropdown-nya ada
  if (typeof Dropdown !== "function") {
    console.warn("Flowbite JS (Dropdown class) belum siap.");
    return;
  }

  // 1. Cari SEMUA tombol dropdown YANG BELUM DITANDAI
  const buttonsToInit = document.querySelectorAll(
    ".fb-dropdown-btn:not([data-fb-init='true'])"
  );

  // console.log(`Menemukan ${buttonsToInit.length} dropdown baru untuk di-init.`);

  buttonsToInit.forEach((button) => {
    const menuId = button.dataset.dropdownToggle;
    const menu = document.getElementById(menuId);

    if (menu) {
      // 2. Buat instance dropdown baru
      new Dropdown(menu, button, {
        placement: "left-start",
        strategy: "fixed",
      });

      // 3. TANDAI tombolnya agar tidak diinisialisasi lagi
      button.dataset.fbInit = "true";
    }
  });
}

/**
 * =======================================================
 * FUNGSI STICKY DATE (Sudah Benar)
 * =======================================================
 */
function updateStickyDate() {
  if (!chatBox || !stickyDateSpan) return;
  let dividerToShow = null;
  const isScrolledToBottom =
    chatBox.scrollHeight - chatBox.scrollTop - chatBox.clientHeight < 50;

  if (isScrolledToBottom) {
    dividerToShow = lastPrintedDate;
  } else {
    let topDividerText = null;
    const chatBoxTop = chatBox.getBoundingClientRect().top + 8;
    const dividers = chatBox.querySelectorAll(".chat-date-divider");
    for (let i = 0; i < dividers.length; i++) {
      const dividerRect = dividers[i].getBoundingClientRect();
      if (dividerRect.top < chatBoxTop) {
        topDividerText = dividers[i].dataset.dateString;
      }
    }
    dividerToShow = topDividerText;
  }

  if (dividerToShow && dividerToShow !== currentStickyDate) {
    stickyDateSpan.textContent = dividerToShow;
    stickyDateHeader.style.opacity = "1";
    stickyDateHeader.style.transform = "translateY(0)";
    currentStickyDate = dividerToShow;
  } else if (!dividerToShow && currentStickyDate !== null) {
    stickyDateHeader.style.opacity = "0";
    stickyDateHeader.style.transform = "translateY(-100%)";
    currentStickyDate = null;
  }
}

/**
 * =======================================================
 * FUNGSI APPEND MESSAGE (VERSI "BODOH" - Sudah Benar)
 * =======================================================
 * HANYA menambahkan HTML. TIDAK ADA inisialisasi JS.
 */
function appendMessage(message) {
  if (!chatBox) return;
  const existingMessage = document.getElementById(
    `message-${message.message_id}`
  );
  if (existingMessage) return;
  const placeholder = document.getElementById("no-message-placeholder");
  if (placeholder) placeholder.remove();

  // 1. Logika Divider Tanggal (Visible / "Duplikat")
  const tanggalISO = message.created_at_iso;
  let dateDividerHTML = "";
  if (tanggalISO) {
    const tanggalFormat = formatTanggalChatJS(tanggalISO);
    if (tanggalFormat && tanggalFormat !== lastPrintedDate) {
      dateDividerHTML = `
            <div class="text-center chat-date-divider my-2" data-date-string="${escapeHTML(
              tanggalFormat
            )}">
                <span class="bg-gray-200 text-gray-700 text-xs font-semibold px-3 py-1 rounded-full">
                    ${escapeHTML(tanggalFormat)}
                </span>
            </div>
            `;
      lastPrintedDate = tanggalFormat;
    }
  }

  // 2. Ambil Data Pesan
  let messageHTML = "";
  const isMyMessage = message.sender_id == CURRENT_USER_ID;
  const msg_type = message.message_type || "text";
  const msg_id = message.message_id || 0;
  const isi_pesan = escapeHTML(message.isi_pesan || "");
  const timeString = escapeHTML(message.created_at_time || "");
  const sender_nama = escapeHTML(message.sender_nama || "User");
  const file_path = escapeHTML(message.file_path || "");
  const original_filename = escapeHTML(message.original_filename || "");
  const file_url = `/Sinergi/public/uploads/forum_files/${file_path}`;

  // 3. Atur Style Dinamis
  const bubble_class = isMyMessage
    ? "bg-gray-800 text-gray-100"
    : "bg-gray-200 text-gray-800";
  const time_class = isMyMessage ? "text-gray-400" : "text-gray-500";
  const align_class = isMyMessage ? "justify-end" : "justify-start";
  const sender_name_html = !isMyMessage
    ? `<p class="text-xs font-semibold mb-1 text-gray-800">${sender_nama}</p>`
    : "";
  const caption_html =
    isi_pesan.length > 0 ? `<p class="mt-2 text-sm">${isi_pesan}</p>` : "";

  // 4. Logika Render Balasan
  const reply_to_id = message.reply_to_message_id || null;
  const replied_text = escapeHTML(message.replied_message_text || "");
  const replied_sender = escapeHTML(message.replied_sender_nama || "");
  let reply_box_html = "";
  if (reply_to_id && replied_sender) {
    const replied_sender_display =
      replied_sender === CURRENT_USER_NAME ? "Anda" : replied_sender;
    const reply_box_style = isMyMessage
      ? "bg-black/20 text-gray-100"
      : "bg-black/10 text-gray-700";
    const reply_sender_style = isMyMessage ? "text-gray-100" : "text-gray-800";
    reply_box_html = `
        <div class="mb-2 p-2 rounded-lg text-sm ${reply_box_style}">
            <p class="font-semibold text-xs ${reply_sender_style}">Membalas ${replied_sender_display}</p>
            <p class="truncate opacity-80">${replied_text}</p>
        </div>
        `;
  }

  // 5. HTML Dropdown Flowbite
  const reply_data_text = msg_type === "text" ? isi_pesan : original_filename;
  let delete_button_html = "";
  if (isMyMessage) {
    delete_button_html = `
        <li>
            <a href="#" 
               class="btn-flowbite-delete flex items-center px-4 py-2 text-sm text-red-600 hover:bg-gray-100"
               data-message-id="${msg_id}">
                <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12.496 0c-.34.052-.68.107-1.022.166m11.474 0a48.108 48.108 0 00-3.478-.397m-7.496 0c-.34.052-.68.107-1.022.166" />
                </svg>
                Hapus
            </a>
        </li>`;
  }
  const dropdown_html = `
    <button type="button" 
            id="dropdown-btn-${msg_id}" 
            data-dropdown-toggle="dropdown-menu-${msg_id}"
            class="fb-dropdown-btn absolute top-1 right-1 p-1 rounded-full 
                   opacity-0 group-hover:opacity-100 
                   bg-black/20 hover:bg-black/40 backdrop-blur-sm 
                   transition-all duration-150 cursor-pointer">
        <svg class="w-6 h-6 text-white drop-shadow-sm" fill="currentColor" viewBox="0 0 20 20">
            <path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" fill-rule="evenodd"></path>
        </svg>
    </button>
    <div id="dropdown-menu-${msg_id}" class="hidden z-50 bg-white rounded-lg shadow-lg w-56 py-2">
        <ul class="py-1 text-sm text-gray-700">
            <li>
                <a href="#" 
                   class="btn-flowbite-reply flex items-center px-4 py-2 hover:bg-gray-100"
                   data-message-id="${msg_id}"
                   data-reply-name="${sender_nama}"
                   data-reply-text="${reply_data_text}">
                    <svg class="w-5 h-5 mr-3" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" />
                    </svg>
                    Balas
                </a>
            </li>
            ${delete_button_html}
        </ul>
    </div>
    `;

  // 6. Render HTML Pesan
  switch (msg_type) {
    case "join":
    case "leave":
      messageHTML = `
            <div class="text-center text-sm text-gray-500 my-2">
                ${isi_pesan}
                <span class="text-xs ml-1">${timeString}</span>
            </div>
            `;
      break;
    case "image":
      messageHTML = `
            <div class="flex ${align_class}" id="message-${msg_id}">
                <div class="group relative ${bubble_class} p-2 rounded-lg max-w-[70%] break-words">
                    ${sender_name_html}
                    ${reply_box_html}
                    <a href="${file_url}" target="_blank" class="cursor-pointer">
                        <img src="${file_url}" alt="${original_filename}" class="rounded-md" style="max-width: 384px; max-height: 384px;">
                    </a>
                    ${caption_html}
                    <div class="text-xs ${time_class} mt-1 text-right">
                        ${timeString}
                    </div>
                    ${dropdown_html} 
                </div>
            </div>
            `;
      break;
    case "document":
      messageHTML = `
            <div class="flex ${align_class}" id="message-${msg_id}">
                <div class="group relative ${bubble_class} p-2 rounded-lg max-w-[70%] break-words">
                    ${sender_name_html}
                    ${reply_box_html}
                    <a href="${file_url}" download="${original_filename}" class="flex items-center bg-white/20 p-2 rounded-lg hover:bg-white/40 transition-colors">
                        <img src="/Sinergi/public/assets/icons/document.svg" alt="Doc" class="w-8 h-8 mr-2 ${
                          isMyMessage ? "invert" : ""
                        }">
                        <div class="flex-1 overflow-hidden">
                            <p class="font-medium truncate">${original_filename}</p>
                            <span class="text-xs">Dokumen</span>
                        </div>
                    </a>
                    ${caption_html}
                    <div class="text-xs ${time_class} mt-1 text-right">
                        ${timeString}
                    </div>
                    ${dropdown_html} 
                </div>
            </div>
            `;
      break;
    default: // case 'text'
      messageHTML = `
            <div class="flex ${align_class}" id="message-${msg_id}">
                <div class="group relative ${bubble_class} p-2 rounded-lg max-w-[70%] break-words">
                    ${sender_name_html}
                    ${reply_box_html}
                    ${isi_pesan}
                    <div class="text-xs ${time_class} mt-1 text-right">
                        ${timeString}
                    </div>
                    ${dropdown_html} 
                </div>
            </div>
            `;
      break;
  }

  chatBox.innerHTML += dateDividerHTML;
  chatBox.innerHTML += messageHTML;

  // 7. TIDAK ADA KODE INIT DI SINI
}

/**
 * =======================================================
 * FUNGSI POLLING (DIPERBARUI)
 * =======================================================
 * Memanggil initializeFlowbite() setelah ada pesan
 */
async function startPolling(lastMessageId) {
  let newLastId = lastMessageId;

  try {
    const response = await fetch(
      `index.php?page=check-new-messages&forum_id=${FORUM_ID}&last_message_id=${lastMessageId}`
    );
    if (!response.ok) {
      const errorText = await response.text();
      throw new Error(
        `HTTP error! status: ${response.status}. Response: ${errorText}`
      );
    }
    const newMessages = await response.json();

    if (newMessages.length > 0) {
      newMessages.forEach((msg) => {
        appendMessage(msg); // 1. Tambah HTML
        newLastId = msg.message_id;
      });
      chatBox.dataset.lastMessageId = newLastId;

      scrollToBottom(); // 2. Scroll ke bawah
      updateStickyDate(); // 3. Update tanggal

      // 4. PANGGIL FUNGSI INISIALISASI BARU
      initializeFlowbite();
    }
  } catch (error) {
    console.error("Long polling error:", error);
    // Jika GAGAL, tunggu 5 detik
    setTimeout(() => startPolling(newLastId), 5000);
    return; // Keluar
  }

  // Jika SUKSES, tunggu 1 detik
  setTimeout(() => startPolling(newLastId), 1000);
}

// --- SISA KODE HELPER (TIDAK BERUBAH) ---

/**
 * Menampilkan preview file yang akan di-upload
 */
function showFilePreview(file) {
  if (!file) {
    filePreviewArea.innerHTML = "";
    filePreviewArea.classList.add("hidden");
    messageInput.placeholder = "Ketik pesan...";
    return;
  }
  filePreviewArea.classList.remove("hidden");
  const isImage = file.type.startsWith("image/");
  const iconSrc = isImage
    ? URL.createObjectURL(file)
    : "/Sinergi/public/assets/icons/document.svg";
  const fileType = isImage ? "Gambar" : "Dokumen";

  filePreviewArea.innerHTML = `
        <div class="flex items-center p-2 bg-gray-100 rounded-lg border border-gray-300">
            <img src="${iconSrc}" alt="Preview" class="${
    isImage ? "w-12 h-12 rounded object-cover" : "w-10 h-10"
  } mr-3">
            <div class="flex-1 overflow-hidden">
                <p class="text-sm font-medium text-gray-900 truncate">${escapeHTML(
                  file.name
                )}</p>
                <p class="text-xs text-gray-500">${fileType} - ${(
    file.size / 1024
  ).toFixed(1)} KB</p>
            </div>
            <button type="button" id="cancel-file-btn" 
                    class="ml-2 flex-shrink-0 p-2 rounded-full hover:bg-gray-200 cursor-pointer">
                <img src="/Sinergi/public/assets/icons/cross.svg" alt="Batal" class="w-7 h-7">
            </button>
        </div>
    `;
  messageInput.placeholder = "Tambahkan Keterangan... (opsional)";

  setTimeout(() => {
    const cancelButton = document.getElementById("cancel-file-btn");
    if (cancelButton) {
      cancelButton.addEventListener("click", () => {
        stagedFile = null;
        fileInput.value = null;
        showFilePreview(null);
      });
    }
  }, 0);
}

function handleFileSelect(event) {
  if (event.target.files && event.target.files.length > 0) {
    stagedFile = event.target.files[0];
    showFilePreview(stagedFile);
  }
}

/**
 * Menampilkan/membatalkan preview balasan
 */
function cancelReply() {
  replyingToMessage = null;
  replyPreviewArea.innerHTML = "";
  replyPreviewArea.classList.add("hidden");
}

function showReplyPreview(message) {
  if (!message) {
    cancelReply();
    return;
  }
  replyingToMessage = message;

  const senderNameDisplay =
    message.name === CURRENT_USER_NAME ? "Anda" : escapeHTML(message.name);
  replyPreviewArea.innerHTML = `
        <div class="flex items-center p-2 bg-gray-200 rounded-lg border-l-4 border-gray-800">
            <div class="flex-1 overflow-hidden">
                <p class="font-semibold text-xs text-gray-800">Membalas ${senderNameDisplay}</p>
                <p class="text-sm truncate">${escapeHTML(message.text)}</p>
            </div>
            <button type="button" id="cancel-reply-btn" 
                    class="ml-2 flex-shrink-0 p-2 rounded-full hover:bg-gray-300 cursor-pointer">
                <img src="/Sinergi/public/assets/icons/cross.svg" alt="Batal" class="w-7 h-7">
            </button>
        </div>
    `;
  replyPreviewArea.classList.remove("hidden");
  document
    .getElementById("cancel-reply-btn")
    .addEventListener("click", cancelReply);
}

/**
 * Mengatur textarea agar tumbuh otomatis
 */
const autoGrowTextarea = (element) => {
  element.style.height = "auto";
  element.style.height = element.scrollHeight + "px";
};

// --- 4. EVENT LISTENERS (TIDAK BERUBAH) ---

// Listener untuk form kirim pesan
if (chatForm && messageInput) {
  chatForm.addEventListener("submit", async (e) => {
    e.preventDefault();
    const isiPesan = messageInput.value;
    if (isiPesan.trim() === "" && !stagedFile) return;

    const formData = new FormData();
    formData.append("forum_id", FORUM_ID);
    formData.append("isi_pesan", isiPesan);

    if (stagedFile) {
      formData.append("file_upload", stagedFile, stagedFile.name);
    }

    if (replyingToMessage) {
      formData.append("reply_to_message_id", replyingToMessage.id);
    }

    const originalPesan = isiPesan;
    messageInput.value = "";
    showFilePreview(null);
    cancelReply();
    stagedFile = null;
    fileInput.value = null;

    setTimeout(() => {
      autoGrowTextarea(messageInput);
    }, 0);

    try {
      const response = await fetch("index.php?page=store-message", {
        method: "POST",
        body: formData,
      });

      if (!response.ok) {
        messageInput.value = originalPesan;
        const errorText = await response.text();
        throw new Error(
          `Gagal mengirim pesan. Status: ${response.status}. Pesan: ${errorText}`
        );
      }

      scrollToBottom();
    } catch (error) {
      console.error("Gagal mengirim pesan:", error);
      if (!stagedFile) messageInput.value = originalPesan;
      alert(`Terjadi kesalahan: ${error.message}`);
    }
  });
}

// Listener untuk tombol Attach
if (attachBtn && fileInput) {
  attachBtn.addEventListener("click", () => {
    fileInput.click();
  });
}

// Listener untuk input file
if (fileInput) {
  fileInput.addEventListener("change", handleFileSelect);
}

// Listener untuk Textarea (Auto-grow dan Enter-to-send)
if (messageInput && messageInput.tagName.toLowerCase() === "textarea") {
  autoGrowTextarea(messageInput);
  messageInput.addEventListener("input", () => {
    autoGrowTextarea(messageInput);
  });
  messageInput.addEventListener("keydown", (e) => {
    if (e.key === "Enter" && !e.shiftKey) {
      e.preventDefault();
      if (sendBtn) {
        sendBtn.click();
      }
    }
  });
}

// Listener untuk Tombol Balas/Hapus (Event Delegation)
if (chatBox) {
  chatBox.addEventListener("click", (e) => {
    // 1. Cek tombol 'Balas'
    const replyButton = e.target.closest(".btn-flowbite-reply");
    if (replyButton) {
      e.preventDefault();
      showReplyPreview({
        id: replyButton.dataset.messageId,
        name: replyButton.dataset.replyName,
        text: replyButton.dataset.replyText,
      });
      messageInput.focus();

      const dropdownId = replyButton.closest('div[id^="dropdown-menu-"]').id;
      if (typeof FlowbiteInstances === "object") {
        const dropdownInstance = FlowbiteInstances.getInstance(
          "Dropdown",
          dropdownId
        );
        if (dropdownInstance) {
          dropdownInstance.hide();
        }
      }
      return;
    }

    // 2. Cek tombol 'Hapus'
    const deleteButton = e.target.closest(".btn-flowbite-delete");
    if (deleteButton) {
      e.preventDefault();
      messageIdPendingDelete = deleteButton.dataset.messageId; // Simpan ID
      if (deleteModal) {
        deleteModal.classList.remove("hidden"); // Tampilkan modal
      }

      const dropdownId = deleteButton.closest('div[id^="dropdown-menu-"]').id;
      if (typeof FlowbiteInstances === "object") {
        const dropdownInstance = FlowbiteInstances.getInstance(
          "Dropdown",
          dropdownId
        );
        if (dropdownInstance) {
          dropdownInstance.hide();
        }
      }
      return;
    }
  });
}

// Listener untuk Modal Hapus
if (modalBtnCancel) {
  modalBtnCancel.addEventListener("click", () => {
    deleteModal.classList.add("hidden");
    messageIdPendingDelete = null;
  });
}

if (deleteModal) {
  deleteModal.addEventListener("click", (e) => {
    if (e.target === deleteModal) {
      modalBtnCancel.click();
    }
  });
}

if (modalBtnConfirmDelete) {
  modalBtnConfirmDelete.addEventListener("click", async () => {
    if (!messageIdPendingDelete) return;

    const messageIdToDelete = messageIdPendingDelete;
    deleteModal.classList.add("hidden");
    messageIdPendingDelete = null;

    try {
      const response = await fetch("index.php?page=delete-message", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
        },
        body: JSON.stringify({
          message_id: messageIdToDelete,
        }),
      });
      const result = await response.json();
      if (result.success) {
        const messageElement = document.getElementById(
          `message-${messageIdToDelete}`
        );
        if (messageElement) {
          messageElement.remove();
        }
        updateStickyDate();
      } else {
        throw new Error(result.error || "Gagal menghapus pesan.");
      }
    } catch (error) {
      console.error("Error:", error);
      alert(`Terjadi kesalahan: ${error.message}`);
    }
  });
}

// Listener untuk Sticky Date Header
if (chatBox && stickyDateSpan) {
  chatBox.addEventListener("scroll", updateStickyDate);
}

// --- 5. INISIALISASI SAAT HALAMAN DIMUAT ---

if (chatBox) {
  // Tunda 0 detik untuk memastikan Flowbite JS siap
  setTimeout(() => {
    scrollToBottom();
    let initialLastId = parseInt(chatBox.dataset.lastMessageId, 10);

    // Inisialisasi lastPrintedDate dari PHP
    const lastDivider = chatBox.querySelector(
      ".chat-date-divider:last-of-type"
    );
    if (lastDivider) {
      lastPrintedDate = lastDivider.dataset.dateString;
    }

    updateStickyDate();

    // =======================================================
    // PANGGIL FUNGSI INISIALISASI BARU (untuk pesan dari PHP)
    // =======================================================
    initializeFlowbite();

    // Mulai polling
    startPolling(initialLastId);
  }, 0);
}
