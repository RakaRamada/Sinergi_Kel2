<?php
// app/admin/views/partials/header.php
// Minimal header: load tailwind css (lokal), buka tag body & wrapper
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>Admin - SINERGI</title>
    <link href="/Sinergi/public/css/output.css" rel="stylesheet">
    <style>
    /* small helper to allow table scroll inside full-height layout */
    .table-scroll {
        max-height: calc(100vh - 220px);
        overflow: auto;
    }
    </style>
</head>

<body class="bg-gray-100 text-gray-800 antialiased">
    <div class="flex min-h-screen">