<?php
// app/admin/views/partials/sidebar.php
// Fixed left sidebar with 3 icons (logo, report, profile)
// Replace the SVG files in /Sinergi/public/assets/icons/ as needed
?>
<aside class="fixed top-0 left-0 h-full w-20 bg-white border-r border-gray-200 flex flex-col items-center py-6 space-y-6 z-40">
  <!-- Logo -->
  <a href="/Sinergi/app/admin/views/dashboard.php" class="block p-1 hover:opacity-90" title="Dashboard">
    <img src="/Sinergi/public/assets/icons/logo.svg" alt="Logo" class="w-8 h-8">
  </a>

  <!-- Report (main) -->
  <a href="/Sinergi/app/admin/views/dashboard.php" class="block p-1 hover:bg-gray-50 rounded" title="Laporan">
    <img src="/Sinergi/public/assets/icons/report.svg" alt="Laporan" class="w-7 h-7">
  </a>

  <div class="flex-1"></div>

  <!-- Profile -->
  <a href="/Sinergi/app/admin/views/profile.php" class="block p-1 hover:bg-gray-50 rounded" title="Profil Admin">
    <img src="/Sinergi/public/assets/icons/profile.svg" alt="Profil" class="w-9 h-9 rounded-full">
  </a>

</aside>

<!-- spacer so main content won't be under the sidebar -->
<div class="w-20 flex-shrink-0"></div>
