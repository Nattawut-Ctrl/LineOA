<?php include __DIR__ . '/../components/navbar.php'; ?>

<nav class="navbar navbar-dark navbar-glass sticky-top shadow-sm">
        <div class="container-fluid px-3 py-2">

            <a class="navbar-brand fw-bold d-none d-md-flex align-items-center gap-2 me-3" href="#">
                <span class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center"
                    style="width:32px;height:32px;">
                    <i class="bi bi-bag-check text-danger"></i>
                </span>
                <span>Line-Shop</span>
            </a>

            <form class="navbar-search-form" role="search">
                <div class="input-group navbar-search-input-group">
                    <span class="input-group-text">
                        <i class="bi bi-search text-secondary"></i>
                    </span>
                    <input class="form-control"
                        type="search"
                        placeholder="ค้นหาสินค้า เช่น เสื้อยืด, รองเท้า, กระเป๋า..."
                        aria-label="ค้นหา"
                        id="searchInput"
                        readonly>
                    <!-- <button class="btn btn-light btn-search d-none d-sm-inline" type="button">
                        <i class="bi bi-camera"></i>
                    </button> -->
                </div>
            </form>


            <!-- ขวาสุด: ชื่อ user + ตะกร้า -->
            <div class="d-flex align-items-center ms-2 gap-2">
                <span class="text-white-50 d-none d-md-inline small">
                    <i class="bi bi-person-circle me-1"></i>
                    <?php echo htmlspecialchars($user['first_name']); ?>
                </span>

                <button class="btn btn-link text-white position-relative p-0" id="cartIcon" type="button">
                    <i class="bi bi-cart3 fs-4"></i>
                    <span class="position-absolute top-0 start-100 translate-middle badge bg-warning text-dark rounded-pill"
                        id="cartCountBadge" style="font-size:0.65rem; display:none;">
                        0
                    </span>
                </button>
            </div>
        </div>
    </nav>