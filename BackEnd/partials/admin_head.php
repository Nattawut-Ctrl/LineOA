<?php $pageTitle = $pageTitle ?? 'Admin Dashboard'; ?>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title><?= htmlspecialchars($pageTitle) ?></title>

<!-- Bootstrap 5 -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

<!-- Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
  body {
    font-family: "Segoe UI", Arial, sans-serif;
    background: #eef1f6;
  }

  /* Topbar */
  .topbar {
    background: #2196f3;
    color: white;
    padding: 12px 25px;
    font-size: 1.1rem;
    font-weight: 600;
  }

  /* Sidebar */
  .sidebar {
    width: 240px;
    background: #ffffff;
    height: 100vh;
    padding-top: 20px;
    position: fixed;
    border-right: 1px solid #e5e7eb;
  }

  .sidebar .nav-link {
    color: #4b5563;
    font-size: 0.95rem;
    padding: 12px 20px;
    border-radius: 8px;
    margin: 4px 10px;
    display: flex;
    align-items: center;
    gap: 12px;
  }

  /* ===== Sidebar fixes (no overflow on hover) ===== */
  .main-sidebar {
    background: #0f172a;
    overflow-x: hidden;
    /* กันล้นแนวนอน */
  }

  .nav-sidebar {
    overflow-x: hidden;
    /* กันล้นแนวนอนอีกชั้น */
  }

  .nav-sidebar .nav-link {
    border-radius: 10px;
    margin: 4px 8px;
    transition: background .18s ease, color .18s ease;
    color: #e5e7eb;
    position: relative;
  }

  .nav-sidebar .nav-link:hover {
    background: rgba(255, 255, 255, .06);
    transform: none !important;
    /* ✅ ห้ามเลื่อนออกนอกกรอบ */
  }

  .nav-sidebar .nav-link.active {
    background: rgba(47, 128, 237, .18);
    color: #fff;
    font-weight: 600;
    box-shadow: inset 3px 0 0 #2f80ed;
  }


  /* Content */
  .content-area {
    margin-left: 260px;
    padding: 25px;
  }

  /* Dashboard Cards */
  .dash-card {
    background: white;
    border-radius: 12px;
    padding: 35px 25px;
    text-align: center;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    transition: .2s;
  }

  .dash-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.08);
    cursor: pointer;
  }

  .dash-card i {
    font-size: 45px;
    color: #4b5563;
    margin-bottom: 15px;
  }

  .dash-card p {
    font-size: 1.15rem;
    margin: 0;
    color: #374151;
    font-weight: 500;
  }
</style>