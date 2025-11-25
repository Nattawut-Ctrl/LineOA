<?php
$pageTitle = $pageTitle ?? 'Admin Dashboard';
$extraHead = $extraHead ?? '';
?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?></title>

<!-- Kanit -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap">

<!-- Bootstrap 5 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<!-- Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">

<!-- AdminLTE 4 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0-rc3/dist/css/adminlte.min.css">

<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/admin.css?v=<?= filemtime(BASE_PATH.'/assets/css/admin.css') ?>">
<?= $extraHead ?>
