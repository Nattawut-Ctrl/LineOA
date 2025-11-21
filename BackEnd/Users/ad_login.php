<?php
session_start();
require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';

// ถ้าล็อกอินอยู่แล้ว ให้เด้งเข้าแดชบอร์ดเลย
if (isset($_SESSION['admin_id'])) {
    header('Location: ../Stock/addStock.php');
    exit;
}

function getErrorMessage($code)
{
    switch ($code) {
        case 'invalid':
            return 'อีเมล / ชื่อผู้ใช้ หรือรหัสผ่านไม่ถูกต้อง';
        case 'required':
            return 'กรุณากรอกข้อมูลให้ครบถ้วน';
        case 'inactive':
            return 'บัญชีผู้ใช้ยังไม่เปิดใช้งาน กรุณาติดต่อผู้ดูแลระบบ';
        default:
            return 'ไม่สามารถเข้าสู่ระบบได้ กรุณาลองใหม่อีกครั้ง';
    }
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
    <title>เข้าสู่ระบบผู้ดูแล</title>

    <?php include BACKEND_PATH . '/partials/admin_head.php'; ?>

    <style>
        :root {
            --primary-color: #0d6efd;
            --bg-gradient: linear-gradient(135deg, #0d6efd 0%, #6610f2 100%);
        }

        * {
            box-sizing: border-box;
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
            max-width: 430px;
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

        .btn-login {
            border-radius: 0.7rem;
            padding: 0.65rem;
            font-weight: 500;
        }

        .divider-text {
            text-align: center;
            font-size: 0.85rem;
            color: #999;
            position: relative;
            margin: 1rem 0;
        }

        .divider-text::before,
        .divider-text::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 42%;
            height: 1px;
            background-color: #e2e2e2;
        }

        .divider-text::before {
            left: 0;
        }

        .divider-text::after {
            right: 0;
        }

        .auth-footer {
            padding: 0.75rem 1.5rem 1.0rem;
            border-top: 1px solid #f1f1f1;
            background: #fafafa;
            font-size: 0.85rem;
        }

        @media (max-width: 576px) {
            .auth-wrapper {
                padding: 1rem;
            }

            .auth-card {
                border-radius: 0.75rem;
            }
        }
    </style>
</head>

<body>

    <div class="auth-wrapper">
        <div class="auth-card">

            <!-- Header -->
            <div class="auth-card-header">
                <div class="brand-circle">
                    <i class="bi bi-box-seam"></i>
                </div>
                <h1 class="h4 auth-title mb-1">เข้าสู่ระบบผู้ดูแล</h1>
                <p class="text-muted auth-subtitle mb-0">
                    ลงชื่อเข้าใช้เพื่อจัดการสินค้า การจอง และข้อมูลระบบ
                </p>
            </div>

            <!-- Body -->
            <div class="auth-body">

                <?php if (!empty($errorText)): ?>
                    <div class="alert alert-danger py-2 px-3 mb-3">
                        <small><?= htmlspecialchars($errorText) ?></small>
                    </div>
                <?php endif; ?>

                <form action="ad_login_check.php" method="POST" novalidate>
                    <div class="mb-3">
                        <label for="email" class="form-label">อีเมล / ชื่อผู้ใช้</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="bi bi-person"></i>
                            </span>
                            <input
                                type="text"
                                class="form-control"
                                id="email"
                                name="email"
                                placeholder="กรอกอีเมลหรือชื่อผู้ใช้"
                                required>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label for="password" class="form-label">รหัสผ่าน</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="bi bi-lock"></i>
                            </span>
                            <input
                                type="password"
                                class="form-control"
                                id="password"
                                name="password"
                                placeholder="กรอกรหัสผ่าน"
                                required>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="1" id="remember" name="remember">
                            <label class="form-check-label" for="remember">
                                จำการเข้าสู่ระบบ
                            </label>
                        </div>
                        <!-- ลิงก์ลืมรหัสผ่าน (อนาคตทำหน้า reset ก็มาแก้ href ได้) -->
                        <a href="#" class="small text-decoration-none text-primary-emphasis">
                            ลืมรหัสผ่าน?
                        </a>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-login">
                        <i class="bi bi-box-arrow-in-right me-1"></i> เข้าสู่ระบบ
                    </button>
                </form>

                <div class="divider-text small">
                    หรือ
                </div>

                <p class="text-center text-muted small mb-0">
                    หากยังไม่มีบัญชีผู้ดูแล กรุณาติดต่อผู้ดูแลระบบหลัก
                </p>
            </div>

            <!-- Footer -->
            <div class="auth-footer d-flex justify-content-between align-items-center">
                <span class="text-muted">&copy; <?= date('Y') ?> ระบบจัดการสินค้า</span>
                <!-- ถ้ามีลิงก์กลับหน้าเว็บหลัก -->
                <!-- <a href="../index.php" class="small text-decoration-none">กลับหน้าเว็บไซต์</a> -->
            </div>

        </div>
    </div>

    <script>
        // Auto hide alert (เช่น login error) หลัง 3 วิ
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