<?php
session_start();

// ✅ ใช้ db_with_log (มี connectDBWithLog + db_query + writeLog ในตัว)
require_once '../../utils/db_with_log.php';

$conn = connectDBWithLog();

function clean($s)
{
    return trim($s ?? '');
}

$errors = [];

// รับค่า GET จาก checkLineUser.php
$line_uid     = clean($_GET['line_uid']     ?? '');
$display_name = clean($_GET['display_name'] ?? '');
$picture_url  = clean($_GET['picture_url']  ?? '');

$first_name = '';
$last_name  = '';
$phone      = '';
$citizen_id = '';
$title      = '';

// ถ้า POST (กดปุ่มสมัคร)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $line_uid     = clean($_POST['line_uid']     ?? '');
    $display_name = clean($_POST['display_name'] ?? '');
    $picture_url  = clean($_POST['picture_url']  ?? '');
    $title        = clean($_POST['title']        ?? '');
    $first_name   = clean($_POST['first_name']   ?? '');
    $last_name    = clean($_POST['last_name']    ?? '');
    $phone        = clean($_POST['phone']        ?? '');
    $citizen_id   = clean($_POST['citizen_id']   ?? '');

    $errors = [];

    // ดึงค่าดิบจากฟอร์ม
    $phone_raw      = $_POST['phone']      ?? '';
    $citizen_raw    = $_POST['citizen_id'] ?? '';

    // เอาเฉพาะตัวเลข
    $phone_digits   = preg_replace('/\D/', '', $phone_raw);
    $citizen_digits = preg_replace('/\D/', '', $citizen_raw);

    $phone      = $phone_digits;
    $citizen_id = $citizen_digits;

    if ($line_uid === '') {
        $errors[] = "ไม่พบ LINE UID กรุณาเข้าสมัครผ่านปุ่มใน LINE อีกครั้ง";
    }
    if ($title === '') {
        $errors[] = "กรุณาเลือกคำนำหน้า";
    }
    if ($first_name === '') {
        $errors[] = "กรุณากรอกชื่อจริง";
    }
    if ($last_name === '') {
        $errors[] = "กรุณากรอกนามสกุล";
    }
    if (!preg_match('/^[0-9]{10}$/', $phone)) {
        $errors[] = "กรุณากรอกเบอร์โทรศัพท์ให้ถูกต้อง 10 หลัก (เฉพาะตัวเลข)";
    }
    if (!preg_match('/^[0-9]{13}$/', $citizen_id)) {
        $errors[] = "กรุณากรอกเลขบัตรประชาชนให้ถูกต้อง 13 หลัก (เฉพาะตัวเลข)";
    }

    if (empty($errors)) {

        // ✅ INSERT ผ่าน db_query → จะเขียน log ให้อัตโนมัติ (action=insert, table=users)
        $sql = "
            INSERT INTO users 
                (line_uid, display_name, picture_url, title, first_name, last_name, phone, citizen_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ";

        try {
            db_query(
                $conn,
                $sql,
                [
                    $line_uid,
                    $display_name,
                    $picture_url,
                    $title,
                    $first_name,
                    $last_name,
                    $phone,
                    $citizen_id
                ],
                "ssssssss"
            );

            // ดึง id ที่เพิ่ง insert
            $user_id = $conn->insert_id;
            $_SESSION['user_id'] = $user_id;

            // สมัครสำเร็จ → ไปหน้า Buyer
            header("Location: ../Buyer/Buyer.php");
            exit;
        } catch (Throwable $e) {
            // ถ้า INSERT fail db_query จะเขียน log status=error ให้แล้ว
            $errors[] = "บันทึกข้อมูลไม่สำเร็จ: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สมัครสมาชิก</title>
    <?php include '../../bootstrap.php'; ?>

    <style>
        body {
            min-height: 100vh;
            margin: 0;
            background:
                radial-gradient(circle at top, rgba(76, 175, 80, 0.2) 0, transparent 55%),
                radial-gradient(circle at bottom, rgba(255, 193, 7, 0.2) 0, transparent 55%),
                radial-gradient(circle at top right, #1a237e 0, #000 55%);
            background-color: #02040b;
            font-family: system-ui, -apple-system, "Segoe UI", sans-serif;
            color: #e6f1ff;
        }

        .casino-card {
            background: radial-gradient(circle at top left, #101520 0, #050812 55%);
            border-radius: 1.5rem;
            border: 1px solid rgba(255, 255, 255, 0.06);
            box-shadow:
                0 0 45px rgba(0, 0, 0, 0.95),
                0 0 16px rgba(0, 255, 128, 0.25);
            position: relative;
            overflow: hidden;
        }

        .casino-card::before {
            content: "";
            position: absolute;
            inset: -40%;
            background: conic-gradient(from 140deg,
                    rgba(0, 255, 128, 0.0),
                    rgba(0, 255, 128, 0.6),
                    rgba(255, 235, 59, 0.0),
                    rgba(255, 193, 7, 0.6),
                    rgba(0, 255, 128, 0.0));
            opacity: 0.18;
            filter: blur(22px);
            z-index: -1;
        }

        .casino-header {
            background: radial-gradient(circle at top, #fff59d 0, #ffb300 35%, #ef6c00 70%, #4e1b04 100%);
            box-shadow: 0 18px 40px rgba(0, 0, 0, 0.85);
            border-bottom: 1px solid rgba(255, 255, 255, 0.18);
        }

        .casino-header h2 {
            letter-spacing: 0.06em;
            text-transform: uppercase;
            text-shadow:
                0 0 12px rgba(0, 0, 0, 0.7),
                0 0 18px rgba(255, 255, 255, 0.35);
        }

        .casino-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(0, 0, 0, 0.25);
            border: 1px solid rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(8px);
            font-size: 0.8rem;
        }

        .casino-chip-dot {
            width: 7px;
            height: 7px;
            border-radius: 50%;
            background: radial-gradient(circle, #76ff03 0, #00e676 60%, transparent 100%);
            box-shadow: 0 0 10px rgba(0, 230, 118, 0.9);
        }

        .casino-label {
            color: #cfd8ff;
        }

        .casino-input,
        .casino-select {
            background-color: #050815;
            border: 1px solid #283347;
            color: #e6f1ff;
        }

        .casino-input::placeholder {
            color: #5c6a89;
        }

        .casino-input:focus,
        .casino-select:focus {
            background-color: #050815;
            border-color: #00e676;
            box-shadow: 0 0 0 0.2rem rgba(0, 230, 118, 0.35);
            color: #ffffff;
        }

        .casino-profile-ring {
            border: 3px solid rgba(255, 235, 59, 0.95);
            box-shadow:
                0 0 20px rgba(255, 235, 59, 0.7),
                0 0 35px rgba(255, 193, 7, 0.6);
        }

        .casino-btn-primary {
            background: linear-gradient(135deg, #00e676, #aeea00);
            border: none;
            color: #04110a;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.45);
            box-shadow:
                0 0 16px rgba(0, 230, 118, 0.7),
                0 10px 25px rgba(0, 0, 0, 0.9);
            letter-spacing: 0.04em;
        }

        .casino-btn-primary:hover {
            filter: brightness(1.05);
            box-shadow:
                0 0 25px rgba(0, 230, 118, 0.85),
                0 14px 30px rgba(0, 0, 0, 0.95);
        }

        .casino-btn-primary:active {
            transform: translateY(1px);
            box-shadow:
                0 0 14px rgba(0, 230, 118, 0.7),
                0 6px 16px rgba(0, 0, 0, 0.95);
        }

        .casino-helper {
            color: #90a4c8;
        }

        .casino-alert {
            background: rgba(176, 0, 32, 0.9);
            border: 1px solid rgba(255, 82, 82, 0.7);
            color: #ffebee;
        }

        /* ---- RGB GLOW ANIMATION ---- */
        @keyframes rgb-border {
            0% {
                border-color: #ff0000;
                box-shadow: 0 0 15px #ff0000, 0 0 30px rgba(255, 0, 0, 0.5);
            }

            25% {
                border-color: #00ff00;
                box-shadow: 0 0 15px #00ff00, 0 0 30px rgba(0, 255, 0, 0.5);
            }

            50% {
                border-color: #00cfff;
                box-shadow: 0 0 15px #00cfff, 0 0 30px rgba(0, 207, 255, 0.5);
            }

            75% {
                border-color: #aa00ff;
                box-shadow: 0 0 15px #aa00ff, 0 0 30px rgba(170, 0, 255, 0.5);
            }

            100% {
                border-color: #ff0000;
                box-shadow: 0 0 15px #ff0000, 0 0 30px rgba(255, 0, 0, 0.5);
            }
        }

        /* ---- PULSE GLOW ---- */
        @keyframes pulse-glow {

            0%,
            100% {
                box-shadow: 0 0 25px rgba(255, 255, 255, 0.12),
                    0 0 55px rgba(0, 255, 128, 0.35);
            }

            50% {
                box-shadow: 0 0 35px rgba(255, 255, 255, 0.22),
                    0 0 65px rgba(0, 255, 255, 0.55);
            }
        }

        /* ---- FINAL CARD STYLE ---- */
        .casino-card {
            position: relative;
            background: radial-gradient(circle at top left, #101520 0%, #050812 55%);
            border-radius: 1.3rem;
            border: 3px solid #ff0000;
            /* default red */
            overflow: hidden;
            animation: rgb-border 5s linear infinite, pulse-glow 3s ease-in-out infinite;
        }

        /* ==== PROMO FLOAT CARD (เว็บพนันฟีลคาสิโน) ==== */
        @keyframes promo-pulse {

            0%,
            100% {
                transform: translateY(0);
                box-shadow:
                    0 0 18px rgba(0, 230, 118, 0.6),
                    0 0 35px rgba(255, 235, 59, 0.4);
                opacity: 1;
            }

            50% {
                transform: translateY(-4px);
                box-shadow:
                    0 0 26px rgba(0, 230, 255, 0.9),
                    0 0 50px rgba(170, 0, 255, 0.6);
                opacity: 0.9;
            }
        }

        @keyframes promo-rgb-border {
            0% {
                border-color: #ff1744;
            }

            25% {
                border-color: #00e676;
            }

            50% {
                border-color: #00e5ff;
            }

            75% {
                border-color: #d500f9;
            }

            100% {
                border-color: #ff1744;
            }
        }

        .promo-floating-card {
            position: fixed;
            right: 1.25rem;
            bottom: 2.5rem;
            width: 260px;
            max-width: 75vw;
            background: radial-gradient(circle at top, #1b2538 0%, #060910 60%);
            border-radius: 1rem;
            border: 2px solid #ff1744;
            padding: 0.9rem 1rem 1rem;
            color: #ffffff;
            box-shadow:
                0 0 35px rgba(0, 0, 0, 0.95),
                0 0 22px rgba(0, 230, 118, 0.7);
            z-index: 2050;
            animation:
                promo-pulse 2.6s ease-in-out infinite,
                promo-rgb-border 5s linear infinite;
        }

        /* เส้นแสงวิ่งด้านบนการ์ด */
        .promo-floating-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: -30%;
            width: 60%;
            height: 2px;
            background: linear-gradient(90deg,
                    transparent,
                    rgba(255, 255, 255, 0.9),
                    transparent);
            filter: blur(1px);
            animation: promo-line 2.3s linear infinite;
        }

        @keyframes promo-line {
            0% {
                transform: translateX(0);
                opacity: 0;
            }

            20% {
                opacity: 1;
            }

            80% {
                opacity: 1;
            }

            100% {
                transform: translateX(260%);
                opacity: 0;
            }
        }

        .promo-title {
            font-size: 0.92rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            display: flex;
            align-items: center;
            gap: 0.35rem;
        }

        .promo-badge {
            font-size: 0.8rem;
            padding: 0.15rem 0.5rem;
            border-radius: 999px;
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.35);
        }

        .promo-percent {
            font-size: 1.8rem;
            font-weight: 900;
            background: linear-gradient(135deg, #ffff8d, #ffea00, #ff9100);
            -webkit-background-clip: text;
            color: transparent;
            text-shadow:
                0 0 12px rgba(255, 234, 0, 0.8),
                0 0 24px rgba(255, 145, 0, 0.9);
        }

        .promo-sub {
            font-size: 0.8rem;
            color: #c5cae9;
        }

        .promo-btn {
            background: linear-gradient(135deg, #00e676, #aeea00);
            border: none;
            font-size: 0.85rem;
            font-weight: 700;
            padding: 0.45rem 0.75rem;
            border-radius: 999px;
            color: #04110a;
            box-shadow:
                0 0 14px rgba(0, 230, 118, 0.8),
                0 8px 20px rgba(0, 0, 0, 0.85);
        }

        .promo-btn:hover {
            filter: brightness(1.05);
        }

        .promo-close-btn {
            position: absolute;
            top: 4px;
            right: 6px;
            background: transparent;
            border: none;
            color: #ffffffcc;
            font-size: 0.8rem;
            cursor: pointer;
        }

        .promo-close-btn:hover {
            color: #ffffff;
        }

        /* ปรับตำแหน่งบนจอเล็ก → แปะด้านล่างกลาง ๆ */
        @media (max-width: 576px) {
            .promo-floating-card {
                left: 50%;
                right: auto;
                transform: translateX(-50%);
                bottom: 0.75rem;
                width: 92vw;
                max-width: 350px;
                padding: 0.7rem 0.8rem 0.8rem;
            }

            .promo-title {
                font-size: 0.8rem;
            }

            .promo-percent {
                font-size: 1.5rem;
            }

            .promo-sub {
                font-size: 0.75rem;
            }

            .promo-btn {
                font-size: 0.8rem;
                padding: 0.35rem 0.65rem;
            }
        }

        @media (max-width: 576px) {
            .promo-floating-card {
                left: 50%;
                right: auto;
                transform: translateX(-50%);
                bottom: 1rem;
            }
        }

        /* ===== ปรับสำหรับจอมือถือ ===== */
        @media (max-width: 576px) {
            body {
                /* ให้เนื้อหาขึ้นมาสูงหน่อย + ไม่ต้อง shadow เยอะ */
                background:
                    radial-gradient(circle at top, rgba(76, 175, 80, 0.18) 0, transparent 55%),
                    radial-gradient(circle at bottom, rgba(255, 193, 7, 0.18) 0, transparent 55%),
                    radial-gradient(circle at top right, #0b1025 0, #000 55%);
            }

            .container.min-vh-100 {
                align-items: flex-start !important;
                /* ไม่ต้องดันให้อยู่กลางจอเป๊ะ ๆ */
                padding-top: 1.5rem !important;
                padding-bottom: 2.5rem !important;
            }

            .casino-card {
                border-radius: 1rem;
                /* ลดความหนาแน่นของเงาบนมือถือ */
                box-shadow:
                    0 0 28px rgba(0, 0, 0, 0.9),
                    0 0 12px rgba(0, 255, 128, 0.25);
            }

            .casino-header {
                padding-top: 0.85rem !important;
                padding-bottom: 0.85rem !important;
            }

            .casino-header h2 {
                font-size: 1.25rem;
            }

            .casino-chip span.text-light {
                font-size: 0.74rem;
            }

            .card-body.p-4 {
                padding: 1.1rem !important;
            }

            .casino-profile-ring {
                width: 90px !important;
                height: 90px !important;
            }

            .casino-profile-ring img {
                width: 90px !important;
                height: 90px !important;
            }

            .card-body h4 {
                font-size: 1rem;
            }

            .form-label.fs-6 {
                font-size: 0.9rem;
            }

            .form-control.form-control-lg,
            .form-select.form-select-lg {
                font-size: 0.9rem;
                padding-top: 0.5rem;
                padding-bottom: 0.45rem;
            }

            .casino-btn-primary {
                font-size: 0.9rem;
                padding-top: 0.7rem !important;
                padding-bottom: 0.7rem !important;
            }

            .casino-helper {
                font-size: 0.75rem;
            }
        }
    </style>
</head>

<body>

    <div class="container d-flex align-items-center justify-content-center min-vh-100 py-4 px-3">
        <div class="card casino-card w-100" style="max-width: 520px;">

            <!-- Header -->
            <div class="card-header casino-header text-center py-4 rounded-top-4 border-0 text-dark">
                <h2 class="mb-1 fw-bold">สมัครสมาชิก</h2>
                <div class="casino-chip mt-1">
                    <span class="casino-chip-dot"></span>
                    <span class="text-light">เชื่อมบัญชีผ่าน LINE • กรอกข้อมูลไม่กี่ขั้นตอน</span>
                </div>
            </div>

            <div class="card-body p-4 p-md-4">
                <!-- Error messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert casino-alert alert-dismissible fade show rounded-3" role="alert">
                        <div class="fw-bold mb-2">⚠️ พบข้อผิดพลาด:</div>
                        <?php foreach ($errors as $e): ?>
                            <div class="small mb-1">✗ <?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></div>
                        <?php endforeach; ?>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="alert"
                            aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form method="post" id="registerForm">
                    <!-- Hidden fields -->
                    <input type="hidden" name="line_uid"
                        value="<?php echo htmlspecialchars($line_uid, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="display_name"
                        value="<?php echo htmlspecialchars($display_name, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="picture_url"
                        value="<?php echo htmlspecialchars($picture_url, ENT_QUOTES, 'UTF-8'); ?>">

                    <!-- Profile section -->
                    <div class="text-center mb-4">
                        <?php if ($picture_url): ?>
                            <img src="<?php echo htmlspecialchars($picture_url, ENT_QUOTES, 'UTF-8'); ?>"
                                alt="LINE Profile Picture"
                                class="rounded-circle shadow-sm mb-3 casino-profile-ring"
                                width="120" height="120" style="object-fit: cover;">
                        <?php else: ?>
                            <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3 shadow-sm casino-profile-ring"
                                style="width: 120px; height: 120px; background: radial-gradient(circle at top, #263238 0, #000 70%);">
                                <span class="fs-1 text-light">👤</span>
                            </div>
                        <?php endif; ?>
                        <h4 class="fw-bold mt-2 text-light">
                            <?php echo htmlspecialchars($display_name ?: '(ไม่ทราบ)', ENT_QUOTES, 'UTF-8'); ?>
                        </h4>
                        <small class="casino-helper">ข้อมูลจาก LINE Official</small>
                    </div>

                    <!-- Form fields -->

                    <div class="mb-3">
                        <label class="form-label fw-bold fs-6 casino-label">
                            คำนำหน้า <span class="text-danger">*</span>
                        </label>
                        <select name="title"
                            class="form-select form-select-lg rounded-2 border-2 casino-select" required>
                            <option value="">-- เลือกคำนำหน้า --</option>
                            <option value="นาย" <?php echo ($title === 'นาย') ? 'selected' : ''; ?>>นาย</option>
                            <option value="นาง" <?php echo ($title === 'นาง') ? 'selected' : ''; ?>>นาง</option>
                            <option value="นางสาว" <?php echo ($title === 'นางสาว') ? 'selected' : ''; ?>>นางสาว</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold fs-6 casino-label">
                            ชื่อจริง <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                            class="form-control form-control-lg rounded-2 border-2 casino-input"
                            name="first_name" required
                            value="<?php echo htmlspecialchars($first_name ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="กรอกชื่อจริง">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold fs-6 casino-label">
                            นามสกุล <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                            class="form-control form-control-lg rounded-2 border-2 casino-input"
                            name="last_name" required
                            value="<?php echo htmlspecialchars($last_name ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="กรอกนามสกุล">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold fs-6 casino-label">
                            เบอร์โทรศัพท์ <span class="text-danger">*</span>
                        </label>
                        <input type="tel"
                            class="form-control form-control-lg rounded-2 border-2 casino-input"
                            id="phone" name="phone"
                            maxlength="12" inputmode="numeric" required
                            value="<?php echo htmlspecialchars($phone ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="xxx-xxx-xxxx">
                        <small id="phone-error" class="text-danger d-block mt-1"></small>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-bold fs-6 casino-label">
                            เลขบัตรประชาชน 13 หลัก <span class="text-danger">*</span>
                        </label>
                        <input type="tel"
                            class="form-control form-control-lg rounded-2 border-2 casino-input"
                            id="citizen_id" name="citizen_id"
                            maxlength="17" inputmode="numeric" required
                            value="<?php echo htmlspecialchars($citizen_id ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="1-2345-67890-12-3">
                        <small id="citizen-id-error" class="text-danger d-block mt-1"></small>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit"
                            class="btn btn-lg fw-bold rounded-2 py-3 text-uppercase casino-btn-primary">
                            ✓ สมัครสมาชิก
                        </button>
                    </div>

                    <p class="text-center casino-helper small mt-4 mb-0">
                        การสมัครแสดงว่าคุณยอมรับเงื่อนไขการใช้บริการของระบบ
                    </p>
                </form>
            </div>
        </div>
    </div>


    <!-- PROMO FLOAT CARD -->
    <div class="promo-floating-card" id="promoCard" style="display:none;">
        <button type="button" class="promo-close-btn" id="promoCloseBtn">
            ✕
        </button>
        <div class="d-flex flex-column gap-1">
            <div class="promo-title">
                <span class="text-warning">🔥 โปรพิเศษสมาชิกใหม่</span>
                <span class="promo-badge">LIMITED</span>
            </div>
            <div class="d-flex align-items-baseline gap-2">
                <span class="promo-percent">90%</span>
                <div class="promo-sub">
                    สมัครตอนนี้รับฟรี<br>ส่วนลดสูงสุดสำหรับการใช้งานระบบ
                </div>
            </div>
            <div class="mt-1 d-flex justify-content-between align-items-center">
                <small class="text-success">สมัครไม่กี่ขั้นตอน • ใช้เวลาไม่ถึง 1 นาที</small>
                <button type="button" class="promo-btn" id="promoGoBtn">
                    สมัครตอนนี้
                </button>
            </div>
        </div>
    </div>

    <!-- JS เดิมของการ format เบอร์/บัตร ประชาชน เหมือนที่มีอยู่แล้ว -->
    <script>
        document.querySelector("form").addEventListener("submit", function(event) {
            var phone = document.getElementById("phone");
            var citizenId = document.getElementById("citizen_id");

            document.getElementById("phone-error").innerText = "";
            document.getElementById("citizen-id-error").innerText = "";

            const phoneDigits = phone.value.replace(/\D/g, '');
            const citizenDigits = citizenId.value.replace(/\D/g, '');

            if (!/^[0-9]{10}$/.test(phoneDigits)) {
                event.preventDefault();
                document.getElementById("phone-error").innerText =
                    "กรุณากรอกเบอร์โทรศัพท์ 10 หลัก (เฉพาะตัวเลข)";
            }

            if (!/^[0-9]{13}$/.test(citizenDigits)) {
                event.preventDefault();
                document.getElementById("citizen-id-error").innerText =
                    "กรุณากรอกเลขบัตรประชาชน 13 หลัก (เฉพาะตัวเลข)";
            }
        });

        const phoneInput = document.getElementById("phone");
        phoneInput.addEventListener("input", function() {
            let value = this.value.replace(/\D/g, '');

            if (value.length > 3 && value.length <= 6) {
                this.value = value.slice(0, 3) + '-' + value.slice(3);
            } else if (value.length > 6) {
                this.value = value.slice(0, 3) + '-' + value.slice(3, 6) + '-' + value.slice(6, 10);
            } else {
                this.value = value;
            }
        });

        const citizenIdInput = document.getElementById("citizen_id");
        citizenIdInput.addEventListener("input", function() {
            let value = this.value.replace(/\D/g, '');
            let len = value.length;
            let result = '';

            if (len > 0) result = value.slice(0, 1);
            if (len > 1) result += "-" + value.slice(1, 5);
            if (len > 5) result += "-" + value.slice(5, 10);
            if (len > 10) result += "-" + value.slice(10, 12);
            if (len > 12) result += "-" + value.slice(12, 13);

            this.value = result;
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const promoCard = document.getElementById("promoCard");
            const promoClose = document.getElementById("promoCloseBtn");
            const promoGoBtn = document.getElementById("promoGoBtn");

            // ถ้าเคยปิดไปแล้วในรอบนี้ (localStorage) จะไม่แสดงอีก
            const hidePromo = localStorage.getItem("hidePromoCard");
            if (!hidePromo) {
                // ดีเลย์ให้หน้าโหลดเสร็จก่อนค่อยเด้งขึ้นมา
                setTimeout(() => {
                    if (promoCard) promoCard.style.display = "block";
                }, 1200);
            }

            if (promoClose) {
                promoClose.addEventListener("click", function() {
                    if (promoCard) promoCard.style.display = "none";
                    localStorage.setItem("hidePromoCard", "1");
                });
            }

            if (promoGoBtn) {
                promoGoBtn.addEventListener("click", function() {
                    const form = document.getElementById("registerForm");
                    if (form) {
                        form.scrollIntoView({
                            behavior: "smooth",
                            block: "start"
                        });
                    }
                });
            }
        });
    </script>

</body>

</html>