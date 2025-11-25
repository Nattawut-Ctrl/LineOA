<?php
require_once UTILS_PATH.'/admin_guard.php';
require_admin();
?>
<!DOCTYPE html>
<html lang="th">
<head>
  <?php include BACKEND_PATH."/partials/admin_head.php"; ?>
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
  <?php include BACKEND_PATH."/partials/admin_navbar.php"; ?>
  <?php include BACKEND_PATH."/partials/admin_sidebar.php"; ?>
  <main class="app-main">
    <div class="app-content-header">
      <div class="container-fluid d-flex justify-content-between align-items-center">
        <h3 class="mb-0"><?= htmlspecialchars($pageTitle ?? "") ?></h3>
        <?php if (!empty($breadcrumb ?? [])): ?>
          <ol class="breadcrumb float-sm-end">
            <?php foreach($breadcrumb as $b): ?>
              <li class="breadcrumb-item <?= $b['active']?'active':'' ?>">
                <?php if($b['active']): ?>
                  <?= htmlspecialchars($b['label']) ?>
                <?php else: ?>
                  <a href="<?= htmlspecialchars($b['href']) ?>"><?= htmlspecialchars($b['label']) ?></a>
                <?php endif; ?>
              </li>
            <?php endforeach; ?>
          </ol>
        <?php endif; ?>
      </div>
    </div>
    <div class="app-content">
      <div class="container-fluid">
