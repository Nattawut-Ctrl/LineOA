<?php
session_start();

// ถ้า login อยู่แล้ว ไม่ต้องสมัครซ้ำ
if (isset($_SESSION['admin_id'])) {
    header('Location: ../Stock/addStock.php');
    exit;
}

function getErrorMessage($code)
{
    switch ($code) {
        case 'required':
            return 'กรุณากรอกข้อมูลให้ครบถ้วน';
        case 'password_mismatch':
            return 'รหัสผ่านและยืนยันรหัสผ่านไม่ตรงกัน';
        case 'exists_username':
            return 'ชื่อผู้ใช้นี้ถูกใช้แล้ว กรุณาใช้ชื่ออื่น';
        case 'exists_email':
            return 'อีเมลนี้ถูกใช้แล้ว กรุณาใช้อีเมลอื่น';
        default:
            return 'ไม่สามารถสมัครผู้ดูแลได้ กรุณาลองใหม่อีกครั้ง';
    }
}

$successText = '';
if (!empty($_GET['success']) && $_GET['success'] === 'registered') {
    $successText = 'สมัครผู้ดูแลสำเร็จแล้ว กรุณาเข้าสู่ระบบ';
}

$errorText = '';
if (!empty($_GET['error'])) {
    $errorText = getErrorMessage($_GET['error']);
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>สมัครผู้ดูแลระบบ</title>

    <?php include BACKEND_PATH . '/partials/admin_head.php'; ?>

    <style>
        :root {
            --primary-color: #0d6efd;
            --bg-gradient: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);
        }

        body {
            font-family: 'Kanit', sans-serif;
            min-height: 100vh;
            margin: 0;
            background: var(--bg-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .auth-wrapper {
            width: 100%;
            max-width: 480px;
            padding: 1.5rem;
        }

        .auth-card {
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 20px 45px rgba(0, 0, 0, 0.18);
            overflow: hidden;
        }

        .auth-card-header {
            padding: 1.5rem 1.5rem 0.75rem;
            border-bottom: none;
        }

        .brand-circle {
            width: 52px;
            height: 52px;
            border-radius: 999px;
            background: rgba(13, 110, 253, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 0.75rem;
        }

        .brand-circle i {
            font-size: 28px;
            color: var(--primary-color);
        }

        .auth-title {
            font-weight: 600;
        }

        .auth-subtitle {
            font-size: 0.9rem;
        }

        .auth-body {
            padding: 1.25rem 1.5rem 1.5rem;
        }

        .form-control {
            border-radius: 0.7rem;
        }

        .form-control:focus {
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }

        .btn-submit {
            border-radius: 0.7rem;
            padding: 0.65rem;
            font-weight: 500;
        }

        .auth-footer {
            padding: 0.75rem 1.5rem 1.0rem;
            border-top: 1px solid #f1f1f1;
            background: #fafafa;
            font-size: 0.85rem;
        }
    </style>
</head>

<body>

<div class="auth-wrapper">
    <div class="auth-card">

        <!-- Header -->
        <div class="auth-card-header">
            <div class="brand-circle">
                <i class="bi bi-person-plus"></i>
            </div>
            <h1 class="h4 auth-title mb-1">สมัครผู้ดูแลระบบ</h1>
            <p class="text-muted auth-subtitle mb-0">
                สร้างบัญชีผู้ดูแลเพื่อจัดการระบบหลังบ้าน
            </p>
        </div>

        <!-- Body -->
        <div class="auth-body">

            <?php if (!empty($successText)): ?>
                <div class="alert alert-success py-2 px-3 mb-3">
                    <small><?= htmlspecialchars($successText) ?></small>
                </div>
            <?php endif; ?>

            <?php if (!empty($errorText)): ?>
                <div class="alert alert-danger py-2 px-3 mb-3">
                    <small><?= htmlspecialchars($errorText) ?></small>
                </div>
            <?php endif; ?>

            <form action="ad_register_save.php" method="POST" novalidate>
                <div class="mb-3">
                    <label class="form-label">ชื่อ-นามสกุล</label>
                    <input
                        type="text"
                        class="form-control"
                        name="full_name"
                        placeholder="เช่น นางสาวตัวอย่าง แอดมิน"
                        required>
                </div>

                <div class="mb-3">
                    <label class="form-label">ชื่อผู้ใช้ (Username)</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-person"></i>
                        </span>
                        <input
                            type="text"
                            class="form-control"
                            name="username"
                            placeholder="กำหนดชื่อผู้ใช้"
                            required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">อีเมล</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-envelope"></i>
                        </span>
                        <input
                            type="email"
                            class="form-control"
                            name="email"
                            placeholder="example@mail.com"
                            required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">รหัสผ่าน</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-lock"></i>
                        </span>
                        <input
                            type="password"
                            class="form-control"
                            name="password"
                            placeholder="กำหนดรหัสผ่าน"
                            required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">ยืนยันรหัสผ่าน</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="bi bi-lock-fill"></i>
                        </span>
                        <input
                            type="password"
                            class="form-control"
                            name="password_confirm"
                            placeholder="กรอกรหัสผ่านอีกครั้ง"
                            required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary w-100 btn-submit">
                    <i class="bi bi-person-plus me-1"></i> สมัครผู้ดูแล
                </button>
            </form>

            <p class="text-center text-muted small mb-0 mt-3">
                มีบัญชีผู้ดูแลอยู่แล้ว?
                <a href="ad_login.php" class="text-decoration-none">
                    เข้าสู่ระบบที่นี่
                </a>
            </p>
        </div>

        <!-- Footer -->
        <div class="auth-footer d-flex justify-content-between align-items-center">
            <span class="text-muted">&copy; <?= date('Y') ?> ระบบจัดการสินค้า</span>
        </div>

    </div>
</div>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const alertEl = document.querySelector('.alert');
        if (alertEl) {
            setTimeout(() => {
                const bsAlert = new bootstrap.Alert(alertEl);
                bsAlert.close();
            }, 3000);
        }
    });
</script>

</body>
</html>
