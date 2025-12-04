/*
 * File: public/assets/js/chat_app.js
 * VERSI FINAL KOMPLIT: Fix Enter, Reply Preview, Delete Modal, Realtime
 */

// --- 1. INISIALISASI VARIABEL GLOBAL ---
// Variabel ini harus ada di window agar tidak reset
window.replyingToMessage = null;
window.messageIdToDelete = null;

// --- 2. FUNGSI-FUNGSI GLOBAL (Diakses dari onclick HTML) ---

// Toggle Menu Dropdown (Titik tiga)
window.toggleMessageMenu = function (event, menuId) {
  event.stopPropagation();
  // Tutup menu lain
  document.querySelectorAll(".message-dropdown").forEach((el) => {
    if (el.id !== menuId) el.classList.add("hidden");
  });
  // Toggle menu yang diklik
  const menu = document.getElementById(menuId);
  if (menu) menu.classList.toggle("hidden");
};

// Tutup semua dropdown
window.closeAllDropdowns = function () {
  document.querySelectorAll(".message-dropdown").forEach((el) => {
    el.classList.add("hidden");
  });
};

// Handle Klik Tombol Reply
window.handleReply = function (msgId, senderName, textContent) {
  window.replyingToMessage = {
    id: msgId,
    name: senderName,
    text: textContent,
  };

  const previewArea = document.getElementById("reply-preview-area");
  if (previewArea) {
    previewArea.classList.remove("hidden");
    const sender = senderName === "Anda" ? "Anda" : escapeHTML(senderName);

    previewArea.innerHTML = `
            <div class="flex items-center justify-between bg-gray-100 p-2 rounded-lg border-l-4 border-blue-500">
                <div class="overflow-hidden">
                    <p class="font-bold text-xs text-blue-600 mb-0.5">Membalas ${sender}</p>
                    <p class="text-xs text-gray-600 truncate">${escapeHTML(
                      textContent
                    )}</p>
                </div>
                <button type="button" onclick="cancelReply()" class="ml-2 text-gray-400 hover:text-red-500 p-1">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
        `;
  }

  // Fokus ke input
  const input = document.getElementById("message-input");
  if (input) input.focus();

  window.closeAllDropdowns();
};

// Batalkan Reply
window.cancelReply = function () {
  window.replyingToMessage = null;
  const previewArea = document.getElementById("reply-preview-area");
  if (previewArea) {
    previewArea.innerHTML = "";
    previewArea.classList.add("hidden");
  }
};

// Buka Modal Hapus
window.openDeleteModal = function (msgId) {
  window.messageIdToDelete = msgId;
  const modal = document.getElementById("deleteModal");
  if (modal) modal.classList.remove("hidden");
  window.closeAllDropdowns();
};

// Tutup Modal Hapus
window.closeDeleteModal = function () {
  window.messageIdToDelete = null;
  const modal = document.getElementById("deleteModal");
  if (modal) modal.classList.add("hidden");
};

// Helper Escape HTML
function escapeHTML(str) {
  if (!str) return "";
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

// --- 3. LOGIKA UTAMA (SAAT DOM SIAP) ---
document.addEventListener("DOMContentLoaded", function () {
  // Referensi Element
  const chatBox = document.getElementById("chat-box");
  const chatForm = document.getElementById("chat-form");
  const messageInput = document.getElementById("message-input");
  const fileInput = document.getElementById("file-upload-input");
  const filePreviewArea = document.getElementById("file-preview-area");
  const sendBtn = document.getElementById("send-btn");
  const confirmDeleteBtn = document.getElementById("confirmDeleteBtn");

  let stagedFile = null;

  // Auto Scroll Bawah saat load
  if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;

  // --- EVENT LISTENER: KLIK GLOBAL (Tutup Dropdown) ---
  document.addEventListener("click", function (e) {
    if (
      !e.target.closest(".message-dropdown") &&
      !e.target.closest('button[onclick^="toggleMessageMenu"]')
    ) {
      window.closeAllDropdowns();
    }
  });

  // --- EVENT LISTENER: FILE INPUT ---
  if (fileInput) {
    fileInput.addEventListener("change", (e) => {
      if (e.target.files && e.target.files.length > 0) {
        stagedFile = e.target.files[0];
        showFilePreviewUI(stagedFile);
      }
    });
  }

  function showFilePreviewUI(file) {
    if (!filePreviewArea) return;
    if (!file) {
      filePreviewArea.classList.add("hidden");
      filePreviewArea.innerHTML = "";
      return;
    }
    filePreviewArea.classList.remove("hidden");
    const isImage = file.type.startsWith("image/");
    const iconSrc = isImage
      ? URL.createObjectURL(file)
      : "/Sinergi/public/assets/icons/document.svg";

    filePreviewArea.innerHTML = `
            <div class="flex items-center p-2 bg-gray-100 rounded-lg border border-gray-300">
                ${
                  isImage
                    ? `<img src="${iconSrc}" class="w-10 h-10 object-cover rounded mr-3">`
                    : `<div class="p-2 bg-white rounded mr-3 border font-bold text-xs">DOC</div>`
                }
                <div class="flex-1 overflow-hidden">
                    <p class="text-xs font-bold truncate">${escapeHTML(
                      file.name
                    )}</p>
                    <p class="text-[10px] text-gray-500">${(
                      file.size / 1024
                    ).toFixed(1)} KB</p>
                </div>
                <button type="button" id="cancel-file-btn" class="ml-2 p-1 text-gray-400 hover:text-red-500">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>`;

    document.getElementById("cancel-file-btn").addEventListener("click", () => {
      stagedFile = null;
      if (fileInput) fileInput.value = null;
      showFilePreviewUI(null);
    });
  }

  // --- EVENT LISTENER: ENTER KEY ---
  if (messageInput) {
    messageInput.addEventListener("keydown", (e) => {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault(); // Jangan bikin baris baru
        // Trigger submit secara manual
        if (chatForm) chatForm.dispatchEvent(new Event("submit"));
      }
    });
  }

  // --- EVENT LISTENER: SUBMIT FORM ---
  if (chatForm) {
    chatForm.addEventListener("submit", async (e) => {
      e.preventDefault();

      // Cegah Double Send
      if (sendBtn.disabled) return;
      sendBtn.disabled = true;

      const isiPesan = messageInput ? messageInput.value.trim() : "";
      const hiddenInput = chatForm.querySelector('input[name="group_id"]');
      const targetId = hiddenInput ? hiddenInput.value : 0;

      // Validasi: Jangan kirim kosong
      if (isiPesan === "" && !stagedFile) {
        sendBtn.disabled = false;
        return;
      }

      const formData = new FormData();
      formData.append("group_id", targetId);
      formData.append("isi_pesan", isiPesan);

      if (stagedFile) {
        formData.append("file_upload", stagedFile, stagedFile.name);
      }

      // Masukkan data Reply jika ada
      if (window.replyingToMessage) {
        formData.append("reply_to_message_id", window.replyingToMessage.id);
      }

      // RESET UI SEGERA (Optimistic UI)
      if (messageInput) messageInput.value = "";
      showFilePreviewUI(null);
      window.cancelReply(); // Reset reply state
      stagedFile = null;
      if (fileInput) fileInput.value = null;

      try {
        // Kirim ke Backend
        await fetch("index.php?page=store-message", {
          method: "POST",
          body: formData,
        });
        // Scroll ke bawah (data akan muncul via polling)
        if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
      } catch (error) {
        console.error(error);
        alert("Gagal kirim pesan. Periksa koneksi.");
      } finally {
        sendBtn.disabled = false;
      }
    });
  }

  // --- EVENT LISTENER: DELETE CONFIRM ---
  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener("click", function () {
      if (window.messageIdToDelete) {
        fetch("index.php?page=delete-message", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ message_id: window.messageIdToDelete }),
        })
          .then((r) => r.json())
          .then((d) => {
            if (d.success) {
              // Hapus elemen dari layar
              const el = document.getElementById(
                "message-" + window.messageIdToDelete
              );
              if (el) el.remove();
              window.closeDeleteModal();
            } else {
              alert(d.message || "Gagal menghapus pesan.");
              window.closeDeleteModal();
            }
          })
          .catch((err) => {
            console.error(err);
            alert("Terjadi kesalahan koneksi.");
            window.closeDeleteModal();
          });
      }
    });
  }

  // --- POLLING REALTIME ---
  function startPolling() {
    if (!chatBox) return;

    let lastId = parseInt(chatBox.dataset.lastMessageId || 0);
    const urlParams = new URLSearchParams(window.location.search);
    const groupId = urlParams.get("group_id");

    if (!groupId) return;

    fetch(
      `index.php?page=check-new-messages&group_id=${groupId}&last_message_id=${lastId}`
    )
      .then((res) => res.json())
      .then((newMessages) => {
        if (newMessages && newMessages.length > 0) {
          // Refresh halaman untuk memuat pesan baru dengan PHP rendering yang rapi
          location.reload();
        }
      })
      .catch(() => {}) // Silent error
      .finally(() => {
        setTimeout(startPolling, 3000); // Cek setiap 3 detik
      });
  }

  // Mulai polling
  startPolling();
});
