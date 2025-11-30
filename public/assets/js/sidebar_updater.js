/*
 * ============================================
 * File: /public/assets/js/sidebar_updater.js
 * Polling Sidebar V4 (Fix Badge & Anti Lag)
 * ============================================
 */

const urlParams = new URLSearchParams(window.location.search);
// Pastikan ini Integer agar perbandingannya akurat
const CURRENT_GROUP_ID_ACTIVE = parseInt(urlParams.get("group_id"), 10) || 0;
const GROUP_LIST_CONTAINER = document.getElementById("group-list-container");

// Penyimpanan data terakhir untuk Anti-Lag
let lastSidebarDataString = "";

/**
 * Fungsi membangun HTML satu group
 */
function buildGroupHtml(group) {
  // Pastikan ID group dari DB juga Integer
  const group_id = parseInt(group.group_id, 10) || 0;
  const nama_group = group.nama_group || "Group Tanpa Nama";
  let image_path = "/Sinergi/public/assets/images/user.png";

  if (group.group_image) {
    image_path = `/Sinergi/public/uploads/group_profiles/${escapeHTML(
      group.group_image
    )}`;
  }

  // --- Logika Snippet ---
  let snippet_html = "";
  const last_msg_text = group.last_message_text || "";
  const last_msg_type = group.last_message_type || "";
  const last_msg_sender = group.last_message_sender || "";
  const last_msg_sender_id = group.last_message_sender_id || 0;

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
  const unread_count = parseInt(group.unread_count, 10) || 0;
  let notif_html = "";

  // SYARAT TAMPIL BADGE:
  // 1. Jumlah pesan belum dibaca > 0
  // 2. DAN Group ID ini BUKAN group yang sedang dibuka (CURRENT_GROUP_ID_ACTIVE)
  if (unread_count > 0 && group_id !== CURRENT_GROUP_ID_ACTIVE) {
    notif_html = `<span class="ml-2 bg-gray-900 text-white text-xs font-bold px-2 py-0.5 rounded-full">${unread_count}</span>`;
  }

  // --- Logika Waktu ---
  const time_html = group.last_message_time
    ? `<span class="text-xs text-gray-500 flex-shrink-0 ml-2">${escapeHTML(
        group.last_message_time
      )}</span>`
    : "";

  // --- Logika Aktif (Background Abu) ---
  const active_class =
    group_id === CURRENT_GROUP_ID_ACTIVE ? "bg-gray-100 font-semibold" : "";

  return `
    <a href="index.php?page=messages&group_id=${group_id}" 
       class="flex items-start p-4 border-b border-gray-200 hover:bg-gray-50 ${active_class}">
        <img src="${image_path}" alt="Profil Group" class="w-10 h-10 rounded-full mr-3 object-cover flex-shrink-0">
        <div class="flex-1 overflow-hidden">
            <div class="flex justify-between items-center">
                <p class="font-bold truncate">${escapeHTML(nama_group)}</p>
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
  if (!GROUP_LIST_CONTAINER || typeof CURRENT_USER_ID === "undefined") {
    setTimeout(pollSidebar, 1000);
    return;
  }

  fetch("index.php?page=get-sidebar-updates")
    .then((response) => response.json())
    .then((groups) => {
      if (groups && Array.isArray(groups)) {
        const currentDataString = JSON.stringify(groups);

        // Anti-Lag Check
        if (currentDataString === lastSidebarDataString) {
          return;
        }
        lastSidebarDataString = currentDataString;

        let newHtml = "";
        if (groups.length === 0) {
          newHtml = `<div class="p-4 text-center text-gray-500"><p>Anda belum bergabung dengan group diskusi apapun.</p></div>`;
        } else {
          groups.forEach((group) => {
            newHtml += buildGroupHtml(group);
          });
        }
        GROUP_LIST_CONTAINER.innerHTML = newHtml;
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
