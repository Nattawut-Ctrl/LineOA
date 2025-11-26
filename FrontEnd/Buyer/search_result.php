<?php
session_start();

require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once SERVICES_PATH . '/productService.php';
require_once SERVICES_PATH . '/userService.php';

$conn    = connectDBWithLog();
$user_id = (int)($_SESSION['user_id'] ?? 0);

if ($user_id <= 0) {
    header("Location: ../Users/line-entry.php?from=shop");
    exit;
}

$user     = getUserById($conn, $user_id);
$products = array_values(getAllProductsWithVariants($conn));
$q        = trim($_GET['q'] ?? '');
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ผลการค้นหา "<?php echo htmlspecialchars($q); ?>" | Line-Shop</title>
    <?php include BASE_PATH . '/partials/bootstrap.php'; ?>

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

        .sort-bar {
            background: #ffffff;
            border-bottom: 1px solid #eee;
        }

        .sort-btn {
            font-size: 0.8rem;
        }

        .sort-btn.active {
            background: linear-gradient(135deg, #ff7043, #ffb74d);
            color: #fff;
            border-color: transparent;
        }

        .product-card {
            border-radius: 1rem;
            cursor: pointer;
            transition: transform 0.18s ease, box-shadow 0.18s ease;
        }

        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
        }

        .product-img-wrap {
            height: 160px;
            background: linear-gradient(135deg, #fff3e0, #ffebee);
        }

        .product-img-wrap img {
            object-fit: cover;
        }

        .badge-stock {
            font-size: 0.7rem;
        }
    </style>
</head>

<body>

    <!-- แถบค้นหาด้านบน (ค้นหาใหม่ได้เลย) -->
    <nav class="navbar top-search-bar navbar-dark sticky-top py-2">
        <div class="container-fluid px-2">
            <button class="btn btn-link text-white me-2" type="button" onclick="window.location.href='search.php'">
                <i class="bi bi-chevron-left fs-4"></i>
            </button>

            <form class="flex-grow-1" action="search_result.php" method="get">
                <div class="input-group top-search-input-group">
                    <span class="input-group-text bg-white border-0">
                        <i class="bi bi-search text-secondary"></i>
                    </span>
                    <input type="search"
                           class="form-control"
                           name="q"
                           id="searchResultInput"
                           placeholder="ค้นหาสินค้า..."
                           value="<?php echo htmlspecialchars($q); ?>"
                           autocomplete="off">
                    <button class="btn btn-light border-0" type="submit">ค้นหา</button>
                </div>
            </form>
        </div>
    </nav>

    <!-- แถบเรียง / ฟิลเตอร์ -->
    <div class="sort-bar sticky-top" style="top:56px;">
        <div class="container py-2 d-flex gap-2 small overflow-auto">
            <button class="btn btn-sm btn-outline-secondary flex-fill sort-btn active" data-sort="relevant">
                ใกล้เคียงที่สุด
            </button>
            <button class="btn btn-sm btn-outline-secondary flex-fill sort-btn" data-sort="latest">
                ล่าสุด
            </button>
            <button class="btn btn-sm btn-outline-secondary flex-fill sort-btn" data-sort="best">
                ขายดี
            </button>
            <button class="btn btn-sm btn-outline-secondary flex-fill sort-btn" data-sort="price_asc">
                ราคา ↑
            </button>
            <button class="btn btn-sm btn-outline-secondary flex-fill sort-btn" data-sort="price_desc">
                ราคา ↓
            </button>
        </div>
    </div>

    <main class="container py-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="small text-muted" id="resultTitle">
                ผลการค้นหา: "<?php echo htmlspecialchars($q); ?>"
            </span>
            <small class="text-muted" id="resultCount"></small>
        </div>

        <div id="resultGrid" class="row g-3 g-md-4"></div>
    </main>

    <script>
        const allProducts = <?php echo json_encode($products, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
        const initialKeyword = <?php echo json_encode($q, JSON_UNESCAPED_UNICODE); ?>;

        let currentKeyword = initialKeyword || '';
        let currentSort = 'relevant';

        function filterProducts(keyword) {
            keyword = (keyword || '').trim().toLowerCase();
            if (!keyword) return [...allProducts];
            return allProducts.filter(p =>
                (p.name || '').toLowerCase().includes(keyword) ||
                (p.description || '').toLowerCase().includes(keyword)
            );
        }

        function sortProducts(list, sortKey) {
            const sorted = [...list];
            switch (sortKey) {
                case 'latest':
                    sorted.sort((a, b) => Number(b.id) - Number(a.id));
                    break;
                case 'price_asc':
                    sorted.sort((a, b) => Number(a.price || 0) - Number(b.price || 0));
                    break;
                case 'price_desc':
                    sorted.sort((a, b) => Number(b.price || 0) - Number(a.price || 0));
                    break;
                case 'best':
                    // สมมติใช้ stock น้อย = น่าจะขายดี (placeholder)
                    sorted.sort((a, b) => Number(a.stock || 0) - Number(b.stock || 0));
                    break;
                case 'relevant':
                default:
                    break;
            }
            return sorted;
        }

        function renderResults() {
            const grid = document.getElementById('resultGrid');
            const title = document.getElementById('resultTitle');
            const countEl = document.getElementById('resultCount');

            let list = filterProducts(currentKeyword);
            list = sortProducts(list, currentSort);

            grid.innerHTML = '';

            if (!currentKeyword) {
                title.textContent = 'สินค้าแนะนำสำหรับคุณ';
            } else {
                title.textContent = 'ผลการค้นหา: "' + currentKeyword + '"';
            }

            if (!list.length) {
                countEl.textContent = '';
                grid.innerHTML = '<div class="col-12 text-muted small">ไม่พบสินค้าที่ค้นหา</div>';
                return;
            }

            countEl.textContent = list.length + ' รายการ';

            list.forEach(p => {
                const col = document.createElement('div');
                col.className = 'col-6 col-md-4 col-lg-3';

                col.innerHTML = `
                    <div class="card product-card h-100 border-0 bg-white"
                         onclick="window.location.href='product-detail.php?id=${encodeURIComponent(p.id)}'">
                        <div class="position-relative product-img-wrap rounded-top-4 overflow-hidden">
                            <img src="${p.image}" class="w-100 h-100">
                            ${p.category ? `
                                <span class="badge text-bg-light position-absolute top-0 start-0 m-2 rounded-pill shadow-sm small">
                                    <i class="bi bi-tag me-1 text-danger"></i>${p.category}
                                </span>` : ``}
                            <span class="badge bg-success-subtle text-success-emphasis position-absolute bottom-0 end-0 m-2 badge-stock shadow-sm">
                                คงเหลือ ${Number(p.stock || 0)}
                            </span>
                        </div>
                        <div class="card-body p-2 p-md-3 d-flex flex-column">
                            <h6 class="small fw-semibold text-truncate mb-1">${p.name}</h6>
                            <p class="text-danger fw-bold mb-1">฿${Number(p.price || 0).toLocaleString()}</p>
                            <small class="text-muted text-truncate flex-grow-1">${p.description || ''}</small>
                        </div>
                    </div>
                `;

                grid.appendChild(col);
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            const input = document.getElementById('searchResultInput');
            // if (input) input.focus();

            document.querySelectorAll('.sort-btn').forEach(btn => {
                btn.addEventListener('click', () => {
                    document.querySelectorAll('.sort-btn').forEach(b => b.classList.remove('active'));
                    btn.classList.add('active');
                    currentSort = btn.dataset.sort || 'relevant';
                    renderResults();
                });
            });

            renderResults();
        });
    </script>

</body>
</html>
