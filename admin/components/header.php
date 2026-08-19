<?php
/**
 * Komponen Header HTML Admin - PT Jaya Teknis
 */
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/config.php';
}
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/../../config/session.php';
}

$pageTitle = $pageTitle ?? 'Purchasing Management';
$currentUser = getCurrentUser() ?? [];
$companyProfile = getCompanyProfile();
$companyName = $companyProfile['nama'] ?? 'PT Jaya Teknis';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= htmlspecialchars($companyName) ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <!-- Custom Design Styles -->
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/app.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/styles/responsive.css">

    <script>
        // Global App Config untuk JavaScript Frontend
        const BASE_URL = '<?= BASE_URL ?>';
        const API_TOKEN = '<?= $_SESSION['api_token'] ?? '' ?>';
        const CURRENT_USER = <?= json_encode($currentUser) ?>;
    </script>
</head>
<body>
<div id="app-wrapper">
