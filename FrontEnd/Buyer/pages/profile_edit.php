<?php
require_once __DIR__ . '/../components/init.php';
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ข้อมูลส่วนตัว | Line Shop</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <?php include BASE_PATH . '/shared/partials/bootstrap.php'; ?>

    <style>
        body {
            background: #f5f5f7;
            padding-bottom: 80px;
        }

        .profile-avatar {
            width: 72px;
            height: 72px;
            border-radius: 50%;
            object-fit: cover;
        }
    </style>
</head>

<body>

    <!-- Topbar -->
    <nav class="navbar navbar-dark sticky-top"
        style="background:linear-gradient(90deg,#ee4d2d,#ff8f5a);">
        <div class="container-fluid">
            <a href="profile.php" class="btn btn-link text-white">
                <i class="bi bi-chevron-left"></i>
            </a>
            <span class="navbar-brand mx-auto">ข้อมูลส่วนตัว</span>
            <span></span>
        </div>
    </nav>

    <div class="container py-3">

        <!-- โปรไฟล์ LINE (อ่านอย่างเดียว) -->
        <div class="card mb-3 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <img src="<?= htmlspecialchars($user['picture_url'] ?? '') ?>"
                    class="profile-avatar border"
                    alt="profile">
                <div>
                    <div class="fw-semibold">
                        <?= htmlspecialchars($user['display_name'] ?? '') ?>
                    </div>
                    <div class="small text-muted">
                        รูปและชื่อมาจาก LINE (แก้ไขใน LINE)
                    </div>
                </div>
            </div>
        </div>

        <!-- ฟอร์มแก้ไขข้อมูล -->
        <form method="post" action="profile_update.php">
            <div class="card shadow-sm">
                <div class="card-body">

                    <div class="mb-3">
                        <label class="form-label">คำนำหน้า</label>
                        <select name="title" class="form-select">
                            <option value="">-- เลือก --</option>
                            <?php
                            $titles = ['นาย', 'นาง', 'นางสาว'];
                            foreach ($titles as $t):
                            ?>
                                <option value="<?= $t ?>"
                                    <?= (($user['title'] ?? '') === $t) ? 'selected' : '' ?>>
                                    <?= $t ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ชื่อ</label>
                        <input type="text"
                            name="first_name"
                            class="form-control"
                            value="<?= htmlspecialchars($user['first_name'] ?? '') ?>"
                            placeholder="ชื่อจริง">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">นามสกุล</label>
                        <input type="text"
                            name="last_name"
                            class="form-control"
                            value="<?= htmlspecialchars($user['last_name'] ?? '') ?>"
                            placeholder="นามสกุล">
                    </div>

                    <div class="mb-3">
                        <label class="form-label">เบอร์โทรศัพท์</label>
                        <input type="tel"
                            name="phone"
                            class="form-control"
                            value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
                            placeholder="0XXXXXXXXX">
                        <div class="form-text">
                            ใช้สำหรับการติดต่อและจัดส่งสินค้า
                        </div>
                    </div>

                </div>
            </div>

            <button type="submit"
                class="btn btn-primary w-100 mt-3">
                บันทึกข้อมูล
            </button>
        </form>

    </div>

    <?php include BASE_PATH . '/shared/partials/sweetalert.php'; ?>

    <?php if (isset($_GET['success']) && $_GET['success'] == '1'): ?>
        <script>
            SA.success('บันทึกข้อมูลสำเร็จ', 'ข้อมูลส่วนตัวถูกอัปเดตแล้ว', function() {
                const url = new URL(window.location.href);
                url.searchParams.delete('success');
                url.searchParams.delete('error');
                window.history.replaceState({}, document.title, url.pathname + url.search);
                window.location.href = 'profile.php';
            });
        </script>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <script>
            SA.error('บันทึกไม่สำเร็จ', 'กรุณาลองใหม่อีกครั้ง');
        </script>
    <?php endif; ?>
</body>

</html>