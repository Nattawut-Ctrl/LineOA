<?php
session_start();

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/user_guard.php';
require_once SERVICES_PATH . '/userService.php';

$conn    = connectDBWithLog();
$user_id = require_user_id();

$user = getUserById($conn, $user_id);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ค้นหาสินค้า | Line-Shop</title>
    <?php require_once SHARED_PARTIALS_PATH . '/bootstrap.php'; ?>

    <style>
        body {
            background: #f5f5f7;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
        }

        .top-search-bar {
            background: linear-gradient(90deg,
                    rgba(238, 77, 45, 0.97),
                    rgba(255, 143, 90, 0.97));
        }

        .top-search-input-group {
            background: #fff;
            border-radius: 999px;
            overflow: hidden;
        }

        .top-search-input-group .form-control {
            border: 0;
            font-size: 0.9rem;
        }

        .chip {
            border-radius: 999px;
            padding: 4px 10px;
            font-size: 0.8rem;
        }

        .chip-history {
            background: #f1f1f1;
        }

        .chip-suggest {
            background: #fff3e0;
            color: #e65100;
        }
    </style>
</head>

<body>

    <!-- แถบค้นหาเต็มด้านบน -->
    <nav class="navbar top-search-bar navbar-dark sticky-top py-2">
        <div class="container-fluid px-2">
            <button class="btn btn-link text-white me-2" type="button" onclick="window.location.href='Buyer.php'">
                <i class="bi bi-chevron-left fs-4"></i>
            </button>

            <!-- form นี้ยิงไปหน้า search_result.php -->
            <form class="flex-grow-1" action="<?= FRONTEND_URL ?>/pages/buyer/search_result.php" method="get">
                <div class="input-group top-search-input-group">
                    <span class="input-group-text bg-white border-0">
                        <i class="bi bi-search text-secondary"></i>
                    </span>
                    <input type="search"
                           class="form-control"
                           name="q"
                           id="searchPageInput"
                           placeholder="ค้นหาสินค้า เช่น เสื้อยืด, รองเท้า, กระเป๋า..."
                           autocomplete="off">
                    <button class="btn btn-light border-0" type="submit">ค้นหา</button>
                </div>
            </form>

            <button class="btn btn-link text-white ms-2 d-none d-sm-inline" type="button">
                <i class="bi bi-camera fs-5"></i>
            </button>
        </div>
    </nav>

    <main class="container py-3">

        <!-- ประวัติการค้นหา -->
        <section class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small text-muted">ประวัติการค้นหา</span>
                <button class="btn btn-link btn-sm p-0 small" type="button" onclick="clearHistory()">
                    ล้างประวัติ
                </button>
            </div>
            <div id="historyContainer" class="d-flex flex-wrap gap-2"></div>
        </section>

        <!-- คำแนะนำยอดนิยม -->
        <section class="mb-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="small text-muted">คำค้นหายอดนิยม</span>
            </div>
            <div id="suggestContainer" class="d-flex flex-wrap gap-2">
                <button class="btn btn-sm chip chip-suggest" type="button" onclick="goSearch('เสื้อยืด')">เสื้อยืด</button>
                <button class="btn btn-sm chip chip-suggest" type="button" onclick="goSearch('รองเท้า')">รองเท้า</button>
                <button class="btn btn-sm chip chip-suggest" type="button" onclick="goSearch('กางเกง')">กางเกง</button>
            </div>
        </section>

        <!-- จะเพิ่มสินค้าแนะนำเบา ๆ ตรงนี้ก็ได้ในอนาคต -->
    </main>

    <script>
        const HISTORY_KEY = 'lineShopSearchHistory';

        function getHistory() {
            try {
                return JSON.parse(localStorage.getItem(HISTORY_KEY)) || [];
            } catch (e) {
                return [];
            }
        }

        function saveHistory(list) {
            localStorage.setItem(HISTORY_KEY, JSON.stringify(list.slice(0, 10)));
        }

        function addHistory(keyword) {
            keyword = keyword.trim();
            if (!keyword) return;
            let history = getHistory().filter(k => k !== keyword);
            history.unshift(keyword);
            saveHistory(history);
            renderHistory();
        }

        function clearHistory() {
            localStorage.removeItem(HISTORY_KEY);
            renderHistory();
        }

        function renderHistory() {
            const container = document.getElementById('historyContainer');
            const history = getHistory();
            container.innerHTML = '';
            if (!history.length) {
                container.innerHTML = '<span class="text-muted small">ยังไม่มีประวัติการค้นหา</span>';
                return;
            }
            history.forEach(k => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-sm chip chip-history';
                btn.textContent = k;
                btn.onclick = () => goSearch(k);
                container.appendChild(btn);
            });
        }

        function goSearch(keyword) {
            if (!keyword) return;
            addHistory(keyword);
            window.location.href = 'search_result.php?q=' + encodeURIComponent(keyword);
        }

        document.addEventListener('DOMContentLoaded', () => {
            renderHistory();

            const form = document.querySelector('form[action="search_result.php"]');
            const input = document.getElementById('searchPageInput');

            if (form && input) {
                form.addEventListener('submit', () => {
                    const kw = input.value.trim();
                    if (kw) addHistory(kw);
                });
                input.focus();
            }
        });
    </script>

</body>
</html>
