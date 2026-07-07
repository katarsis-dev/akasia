<?php
$currentPage = $page ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Akasia Motor</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>

<body>
    <div class="admin-shell">
        <div class="sidebar-overlay" id="sidebarOverlay"></div>

        <aside class="sidebar">
            <div class="brand-box d-flex justify-content-center border-bottom-0 pt-4 pb-2">
                <img src="assets/images/logo.png" alt="Akasia Motor"
                    style="max-width: 85%; height: auto; filter: brightness(1.1) contrast(1.1); mix-blend-mode: screen;">
            </div>