/*
 * ============================================
 * File: /public/assets/js/sidebar_updater.js
 * Polling Sidebar V4 (Fix Badge & Anti Lag)
 * ============================================
 */

const urlParams = new URLSearchParams(window.location.search);
// Pastikan ini Integer agar perbandingannya akurat
const CURRENT_FORUM_ID_ACTIVE = parseInt(urlParams.get("forum_id"), 10) || 0;
const FORUM_LIST_CONTAINER = document.getElementById("forum-list-container");

// Penyimpanan data terakhir untuk Anti-Lag
let lastSidebarDataString = "";

/**
 * Fungsi membangun HTML satu forum
 */
function buildForumHtml(forum) {
  // Pastikan ID forum dari DB juga Integer
  const forum_id = parseInt(forum.forum_id, 10) || 0;
  const nama_forum = forum.nama_forum || "Forum Tanpa Nama";
  let image_path = "/Sinergi/public/assets/images/user.png";

  if (forum.forum_image) {
    image_path = `/Sinergi/public/uploads/forum_profiles/${escapeHTML(
      forum.forum_image
    )}`;
  }

  // --- Logika Snippet ---
  let snippet_html = "";
  const last_msg_text = forum.last_message_text || "";
  const last_msg_type = forum.last_message_type || "";
  const last_msg_sender = forum.last_message_sender || "";
  const last_msg_sender_id = forum.last_message_sender_id || 0;

  if (!last_msg_sender) {
    snippet_html =
      '<p class="text-sm text-gray-600 truncate italic">Klik untuk masuk</p>';
  } else {
    let sender_display =
      last_msg_sender_id == CURRENT_USER_ID
        ? "Anda"
        : escapeHTML(last_msg_sender);
    let message_content = "";

    switch (last_msg_type) {
      case "image":
        message_content = "[Gambar]";
        break;
      case "document":
        message_content = "[Dokumen]";
        break;
      case "join":
      case "leave":
        message_content = escapeHTML(last_msg_text);
        sender_display = "";
        break;
      default:
        message_content = escapeHTML(last_msg_text);
        break;
    }

    const prefix = sender_display ? `${sender_display}: ` : "";
    snippet_html = `<p class="text-sm text-gray-600 truncate">${prefix}${message_content}</p>`;
  }

  // --- Logika Notif (FIXED) ---
  const unread_count = parseInt(forum.unread_count, 10) || 0;
  let notif_html = "";

  // SYARAT TAMPIL BADGE:
  // 1. Jumlah pesan belum dibaca > 0
  // 2. DAN Forum ID ini BUKAN forum yang sedang dibuka (CURRENT_FORUM_ID_ACTIVE)
  if (unread_count > 0 && forum_id !== CURRENT_FORUM_ID_ACTIVE) {
    notif_html = `<span class="ml-2 bg-gray-900 text-white text-xs font-bold px-2 py-0.5 rounded-full">${unread_count}</span>`;
  }

  // --- Logika Waktu ---
  const time_html = forum.last_message_time
    ? `<span class="text-xs text-gray-500 flex-shrink-0 ml-2">${escapeHTML(
        forum.last_message_time
      )}</span>`
    : "";

  // --- Logika Aktif (Background Abu) ---
  const active_class =
    forum_id === CURRENT_FORUM_ID_ACTIVE ? "bg-gray-100 font-semibold" : "";

  return `
    <a href="index.php?page=messages&forum_id=${forum_id}" 
       class="flex items-start p-4 border-b border-gray-200 hover:bg-gray-50 ${active_class}">
        <img src="${image_path}" alt="Profil Forum" class="w-10 h-10 rounded-full mr-3 object-cover flex-shrink-0">
        <div class="flex-1 overflow-hidden">
            <div class="flex justify-between items-center">
                <p class="font-bold truncate">${escapeHTML(nama_forum)}</p>
                ${time_html}
            </div>
            <div class="flex justify-between items-center mt-1">
                <div class="flex-1 overflow-hidden">
                    ${snippet_html}
                </div>
                ${notif_html}
            </div>
        </div>
    </a>
    `;
}

function pollSidebar() {
  if (!FORUM_LIST_CONTAINER || typeof CURRENT_USER_ID === "undefined") {
    setTimeout(pollSidebar, 1000);
    return;
  }

  fetch("index.php?page=get-sidebar-updates")
    .then((response) => response.json())
    .then((forums) => {
      if (forums && Array.isArray(forums)) {
        const currentDataString = JSON.stringify(forums);

        // Anti-Lag Check
        if (currentDataString === lastSidebarDataString) {
          return;
        }
        lastSidebarDataString = currentDataString;

        let newHtml = "";
        if (forums.length === 0) {
          newHtml = `<div class="p-4 text-center text-gray-500"><p>Anda belum bergabung dengan forum diskusi apapun.</p></div>`;
        } else {
          forums.forEach((forum) => {
            newHtml += buildForumHtml(forum);
          });
        }
        FORUM_LIST_CONTAINER.innerHTML = newHtml;
      }
    })
    .catch((err) => console.error("Gagal polling sidebar:", err))
    .finally(() => {
      setTimeout(pollSidebar, 3000);
    });
}

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

document.addEventListener("DOMContentLoaded", function () {
  pollSidebar();
});
