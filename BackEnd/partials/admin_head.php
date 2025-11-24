<?php $pageTitle = $pageTitle ?? 'Admin | Line-Shop'; ?>
<title><?= htmlspecialchars($pageTitle) ?></title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<link href="https://fonts.googleapis.com/css?family=Kanit&subset=thai,latin" rel="stylesheet">

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/css/bootstrap.min.css" rel="stylesheet">

<!-- AdminLTE v4 (BS5) -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0/dist/css/adminlte.min.css">

<!-- Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.5.2/css/all.min.css">

<style>
  body {
    font-family: 'Kanit', sans-serif;
    background: #f6f7fb;
  }

  /* ✅ เน้น theme ให้ดูเป็นแบรนด์ */
  .brand-gradient {
    background: linear-gradient(135deg, #ff9248, #ff6b6b);
    color: #fff !important;
  }

  .brand-gradient .brand-text {
    letter-spacing: 0.3px;
  }

  .main-sidebar {
    background: #111827; /* slate-900 */
  }

  .nav-sidebar .nav-link {
    border-radius: 10px;
    margin: 4px 8px;
    transition: all .2s ease;
  }

  .nav-sidebar .nav-link.active {
    background: linear-gradient(90deg, #ff8a4c, #ff6b6b);
    color: #fff;
    box-shadow: 0 8px 18px rgba(255,107,107,.25);
  }

  .nav-sidebar .nav-link:hover {
    background: rgba(255,255,255,.08);
    transform: translateX(2px);
  }

  .small-box {
    border-radius: 16px;
    box-shadow: 0 8px 20px rgba(16,24,40,.08);
    overflow: hidden;
  }

  .content-wrapper {
    background: transparent;
  }
</style>
