<?php
/**
 * Shared header for dashboard-style pages.
 * Expects $pageTitle to optionally be set before include.
 */
$pageTitle = $pageTitle ?? APP_NAME;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle) ?></title>
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/app.css">
<link rel="stylesheet" href="<?= ASSETS_URL ?>/css/dashboard.css">
<script>window.APP_API_BASE = <?= json_encode(APP_URL . '/api') ?>;</script>
</head>
<body>
<div class="app-shell">
    <aside class="sidebar">
        <div class="sidebar-logo">PHP Page Builder</div>
        <nav class="sidebar-nav">
            <a href="<?= APP_URL ?>/dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">Dashboard</a>
        </nav>
        <div class="sidebar-footer">
            <span><?= e($_SESSION['username'] ?? '') ?></span>
            <a href="<?= APP_URL ?>/logout.php" class="logout-link">Logout</a>
        </div>
    </aside>
    <main class="main-content">
