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
  const content = document.getElementById("deleteChatContent"); // ID Konten Dalam

  if (modal && content) {
    // 1. Hilangkan class hidden dulu
    modal.classList.remove("hidden");

    // 2. Force Browser Reflow (Trik biar animasi CSS jalan)
    void modal.offsetWidth;

    // 3. Masukkan class animasi (Muncul pelan + Membesar dikit)
    modal.classList.remove("opacity-0");
    modal.classList.add("opacity-100");

    content.classList.remove("scale-95");
    content.classList.add("scale-100");
  }

  window.closeAllDropdowns();
};

window.closeDeleteModal = function () {
  window.messageIdToDelete = null;
  const modal = document.getElementById("deleteModal");
  const content = document.getElementById("deleteChatContent");

  if (modal && content) {
    // 1. Animasi Keluar (Hilang pelan + Mengecil dikit)
    modal.classList.remove("opacity-100");
    modal.classList.add("opacity-0");

    content.classList.remove("scale-100");
    content.classList.add("scale-95");

    // 2. Tambahkan class hidden setelah animasi selesai (200ms)
    setTimeout(() => {
      modal.classList.add("hidden");
    }, 200);
  }
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

  let collectedFiles = [];

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

  if (fileInput) {
    fileInput.addEventListener("change", (e) => {
      if (e.target.files && e.target.files.length > 0) {
        const newFiles = Array.from(e.target.files);

        // Validasi Max 5
        if (collectedFiles.length + newFiles.length > 5) {
          alert("Maksimal 5 gambar sekaligus!");
          return;
        }

        collectedFiles = collectedFiles.concat(newFiles);
        showFilePreviewUI(); // Update Tampilan
        fileInput.value = ""; // Reset input biar bisa pilih file yang sama lagi
      }
    });
  }

  function showFilePreviewUI() {
    if (!filePreviewArea) return;

    // Reset tombol kirim state setiap kali preview berubah
    updateSendButtonState();

    if (collectedFiles.length === 0) {
      filePreviewArea.classList.add("hidden");
      filePreviewArea.innerHTML = "";
      return;
    }

    filePreviewArea.classList.remove("hidden");
    // Styling Container: Horizontal Scroll, Padding rapi
    filePreviewArea.className =
      "flex gap-3 overflow-x-auto p-3 bg-gray-50 border-t border-gray-200 w-full absolute bottom-full left-0 z-10 shadow-sm";

    let htmlContent = "";

    collectedFiles.forEach((file, index) => {
      const isImage = file.type.startsWith("image/");
      const src = isImage
        ? URL.createObjectURL(file)
        : "/Sinergi/public/assets/icons/document.svg";

      // Template Card per Item
      htmlContent += `
            <div class="relative flex-shrink-0 group w-20 h-20 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden animate-fade-in-up">
                <img src="${src}" class="w-full h-full object-cover">
                
                <div class="absolute inset-0 bg-black/0 group-hover:bg-black/10 transition"></div>

                <button onclick="removeFile(${index})" 
                        class="absolute top-1 right-1 bg-white/80 hover:bg-red-500 hover:text-white text-gray-600 rounded-full p-0.5 shadow-sm transition transform scale-90 hover:scale-110">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>

                <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-[8px] px-1 py-0.5 text-center truncate">
                    ${(file.size / 1024).toFixed(0)} KB
                </div>
            </div>
        `;
    });

    filePreviewArea.innerHTML = htmlContent;
  }

  // Fungsi Global untuk Hapus File dari Array
  window.removeFile = function (index) {
    collectedFiles.splice(index, 1); // Hapus dari array
    showFilePreviewUI(); // Render ulang
  };

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

      if (sendBtn.disabled) return;

      // UI Loading State
      const originalIcon = sendBtn.innerHTML;
      sendBtn.disabled = true;
      sendBtn.innerHTML = `<svg class="animate-spin h-5 w-5 text-white" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`;

      const isiPesan = messageInput ? messageInput.value.trim() : "";
      const hiddenInput = chatForm.querySelector('input[name="group_id"]');
      const targetId = hiddenInput ? hiddenInput.value : 0;

      const formData = new FormData();
      formData.append("group_id", targetId);
      formData.append("isi_pesan", isiPesan);

      // Append File dari Array collectedFiles
      if (collectedFiles.length > 0) {
        collectedFiles.forEach((file) => {
          formData.append("file_upload[]", file);
        });
      }

      if (window.replyingToMessage) {
        formData.append("reply_to_message_id", window.replyingToMessage.id);
      }

      // RESET UI OPTIMISTIC
      if (messageInput) messageInput.value = "";
      collectedFiles = []; // Kosongkan array
      showFilePreviewUI(); // Hilangkan preview
      if (window.cancelReply) window.cancelReply();

      try {
        await fetch("index.php?page=store-message", {
          method: "POST",
          body: formData,
        });
        if (chatBox) chatBox.scrollTop = chatBox.scrollHeight;
      } catch (error) {
        console.error(error);
        alert("Gagal kirim pesan.");
      } finally {
        // Balikin tombol kirim
        sendBtn.innerHTML = originalIcon;
        updateSendButtonState(); // Cek lagi statusnya
      }
    });
  }

  function updateSendButtonState() {
    if (!sendBtn) return;

    const hasText = messageInput && messageInput.value.trim().length > 0;
    const hasFiles = collectedFiles.length > 0;

    // Tombol aktif jika ada Teks ATAU ada File
    if (hasText || hasFiles) {
      sendBtn.disabled = false;
      sendBtn.classList.remove("opacity-50", "cursor-not-allowed");
      sendBtn.classList.add("hover:bg-gray-800"); // Efek hover aktif
    } else {
      sendBtn.disabled = true;
      sendBtn.classList.add("opacity-50", "cursor-not-allowed");
      sendBtn.classList.remove("hover:bg-gray-800");
    }
  }

  if (messageInput) {
    messageInput.addEventListener("input", updateSendButtonState);
  }

  // --- EVENT LISTENER: DELETE CONFIRM ---
  if (confirmDeleteBtn) {
    confirmDeleteBtn.addEventListener("click", function () {
      if (window.messageIdToDelete) {
        confirmDeleteBtn.innerText = "...";
        confirmDeleteBtn.disabled = true;

        fetch("index.php?page=delete-message", {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({ message_id: window.messageIdToDelete }),
        })
          .then(async (r) => {
            const text = await r.text(); // Baca response mentah
            console.log("RESPONSE SERVER:", text); // <-- CEK CONSOLE BROWSER (F12)

            try {
              return JSON.parse(text);
            } catch (err) {
              console.error("JSON PARSE ERROR:", err);
              // Tampilkan isi sampah yang bikin error di alert
              throw new Error(
                "Respon Server Rusak: " + text.substring(0, 50) + "..."
              );
            }
          })
          .then((d) => {
            // Cek segala kemungkinan sukses
            if (d.status === "success" || d.success === true) {
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
            alert("Error: " + err.message);
            window.closeDeleteModal();
          })
          .finally(() => {
            confirmDeleteBtn.innerText = "Hapus";
            confirmDeleteBtn.disabled = false;
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

  // --- FITUR GALLERY SLIDER / LIGHTBOX ---

  // Variabel Global Gallery
  let currentGalleryImages = [];
  let currentGalleryIndex = 0;

  window.openGallery = function (images, startIndex) {
    const modal = document.getElementById("imageGalleryModal");
    const imgEl = document.getElementById("galleryImage");
    const prevBtn = document.getElementById("galleryPrevBtn");
    const nextBtn = document.getElementById("galleryNextBtn");

    // Simpan data
    currentGalleryImages = images;
    currentGalleryIndex = startIndex;

    // Tampilkan Modal
    modal.classList.remove("hidden");
    void modal.offsetWidth; // Reflow
    modal.classList.remove("opacity-0");

    // Update Gambar
    updateGalleryView();

    // Listener Keyboard (Esc & Arrow)
    document.addEventListener("keydown", galleryKeyHandler);
  };

  window.closeGallery = function () {
    const modal = document.getElementById("imageGalleryModal");
    modal.classList.add("opacity-0");
    setTimeout(() => {
      modal.classList.add("hidden");
    }, 300);
    // Hapus Listener Keyboard
    document.removeEventListener("keydown", galleryKeyHandler);
  };

  window.navigateGallery = function (direction) {
    // direction: -1 (kiri) atau 1 (kanan)
    const newIndex = currentGalleryIndex + direction;

    // Cek batas array
    if (newIndex >= 0 && newIndex < currentGalleryImages.length) {
      currentGalleryIndex = newIndex;
      updateGalleryView();
    }
  };

  function updateGalleryView() {
    const imgEl = document.getElementById("galleryImage");
    const counterEl = document.getElementById("galleryCounter");
    const prevBtn = document.getElementById("galleryPrevBtn");
    const nextBtn = document.getElementById("galleryNextBtn");

    // Animasi Ganti Gambar (Fade Out-In dikit)
    imgEl.style.opacity = "0.5";
    setTimeout(() => {
      imgEl.src = currentGalleryImages[currentGalleryIndex].trim();
      imgEl.style.opacity = "1";
    }, 150);

    // Update Counter
    counterEl.innerText = `${currentGalleryIndex + 1} / ${
      currentGalleryImages.length
    }`;

    // Hide/Show Buttons kalau di ujung
    if (currentGalleryIndex === 0) prevBtn.classList.add("hidden");
    else prevBtn.classList.remove("hidden");

    if (currentGalleryIndex === currentGalleryImages.length - 1)
      nextBtn.classList.add("hidden");
    else nextBtn.classList.remove("hidden");
  }

  function galleryKeyHandler(e) {
    if (e.key === "Escape") window.closeGallery();
    if (e.key === "ArrowLeft") window.navigateGallery(-1);
    if (e.key === "ArrowRight") window.navigateGallery(1);
  }
  // Mulai polling
  startPolling();
});
