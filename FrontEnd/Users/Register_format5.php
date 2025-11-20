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

    <!-- ฟอนต์ -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #6366f1;
            --primary-soft: rgba(99, 102, 241, 0.12);
            --accent: #fb7185;
            --bg: #f1f5f9;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            font-family: "Kanit", system-ui, -apple-system, "Segoe UI", sans-serif;
            background:
                radial-gradient(circle at 0 0, #bfdbfe 0, transparent 45%),
                radial-gradient(circle at 100% 0, #fecaca 0, transparent 45%),
                radial-gradient(circle at 100% 100%, #bbf7d0 0, transparent 45%),
                #e5e7eb;
            color: #0f172a;
            overflow-x: hidden;
        }

        /* ================== INTRO SHOPPING ANIMATION ================== */

        #introScreen {
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at top, #1e293b 0, #020617 55%),
                linear-gradient(135deg, #4f46e5, #ec4899);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #e5e7eb;
            z-index: 3000;
            transition: opacity 0.6s ease, visibility 0.6s ease;
        }

        #introScreen.hidden-intro {
            opacity: 0;
            visibility: hidden;
            pointer-events: none;
        }

        .intro-card {
            width: min(360px, 90vw);
            background: rgba(15, 23, 42, 0.82);
            border-radius: 1.5rem;
            padding: 1.5rem 1.6rem 1.3rem;
            box-shadow:
                0 25px 60px rgba(0, 0, 0, 0.8),
                0 0 0 1px rgba(148, 163, 184, 0.3);
            position: relative;
            overflow: hidden;
        }

        .intro-card::before {
            content: "";
            position: absolute;
            inset: -40%;
            background: radial-gradient(circle at top,
                    rgba(248, 250, 252, 0.18),
                    transparent 60%);
            opacity: 0.5;
            pointer-events: none;
        }

        .intro-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 0.75rem;
        }

        .intro-icon-wrap {
            width: 52px;
            height: 52px;
            border-radius: 1.2rem;
            background: #f97316;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow:
                0 16px 32px rgba(248, 113, 22, 0.8),
                0 0 0 3px rgba(15, 23, 42, 0.8);
            position: relative;
            overflow: visible;
            animation: introCartBounce 1.6s ease-in-out infinite;
        }

        .intro-icon-wrap i {
            font-size: 1.6rem;
            color: #fff7ed;
        }

        /* สินค้าลอย ๆ ด้านบนรถเข็น */
        .intro-tag {
            position: absolute;
            top: -10px;
            right: -12px;
            background: #22c55e;
            color: #ecfdf5;
            font-size: 0.6rem;
            padding: 0.15rem 0.45rem;
            border-radius: 999px;
            box-shadow: 0 8px 18px rgba(22, 163, 74, 0.9);
            display: flex;
            align-items: center;
            gap: 0.15rem;
        }

        .intro-tag i {
            font-size: 0.8rem;
        }

        @keyframes introCartBounce {
            0%, 100% {
                transform: translateY(0);
            }

            40% {
                transform: translateY(-6px);
            }

            70% {
                transform: translateY(-2px);
            }
        }

        .intro-title-main {
            font-size: 1.05rem;
            font-weight: 600;
        }

        .intro-title-sub {
            font-size: 0.8rem;
            color: #cbd5f5;
        }

        /* รถเข็นวิ่ง */
        .intro-cart-track {
            margin-top: 0.9rem;
            margin-bottom: 0.9rem;
            height: 80px;
            position: relative;
            overflow: visible;
        }

        .intro-cart {
            position: absolute;
            left: -60px;
            bottom: 0;
            width: 70px;
            height: 46px;
            border-radius: 1rem;
            background: linear-gradient(135deg, #f97316, #fb7185);
            box-shadow:
                0 14px 32px rgba(15, 23, 42, 0.9),
                0 0 0 1px rgba(255, 247, 237, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
            animation: introCartMove 2.8s cubic-bezier(0.16, 0.8, 0.3, 1.02) infinite;
        }

        .intro-cart-inner {
            width: 80%;
            height: 70%;
            border-radius: 0.7rem;
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.4);
            position: relative;
        }

        .intro-cart-item {
            position: absolute;
            width: 12px;
            height: 12px;
            border-radius: 0.35rem;
            background: #facc15;
            top: 4px;
            left: 6px;
            box-shadow: 0 0 10px rgba(250, 204, 21, 0.8);
        }

        .intro-cart-item:nth-child(2) {
            background: #22c55e;
            top: 10px;
            left: 22px;
            box-shadow: 0 0 10px rgba(34, 197, 94, 0.8);
        }

        .intro-cart-item:nth-child(3) {
            background: #38bdf8;
            top: -2px;
            right: 6px;
            box-shadow: 0 0 10px rgba(56, 189, 248, 0.8);
        }

        .intro-cart-wheel {
            position: absolute;
            bottom: -8px;
            width: 12px;
            height: 12px;
            border-radius: 999px;
            background: #020617;
            box-shadow: 0 0 0 3px #e5e7eb;
        }

        .intro-cart-wheel.left {
            left: 10px;
        }

        .intro-cart-wheel.right {
            right: 10px;
        }

        @keyframes introCartMove {
            0% {
                transform: translateX(0) translateY(0);
            }

            40% {
                transform: translateX(220px) translateY(-3px);
            }

            60% {
                transform: translateX(240px) translateY(0);
            }

            100% {
                transform: translateX(260px) translateY(2px);
            }
        }

        .intro-track-line {
            position: absolute;
            bottom: 4px;
            left: 0;
            right: 0;
            height: 2px;
            border-radius: 999px;
            background: linear-gradient(90deg,
                    rgba(148, 163, 184, 0.2),
                    rgba(148, 163, 184, 0.4),
                    rgba(148, 163, 184, 0.2));
        }

        /* แถบโหลด */
        .intro-progress-wrap {
            margin-bottom: 0.45rem;
        }

        .intro-progress-bar {
            width: 100%;
            height: 7px;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.9);
            overflow: hidden;
            border: 1px solid rgba(148, 163, 184, 0.7);
        }

        .intro-progress-fill {
            width: 40%;
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg,
                    #a5b4fc,
                    #f9a8d4,
                    #facc15,
                    #a5b4fc);
            animation: introProgress 1.6s linear infinite;
        }

        @keyframes introProgress {
            0% {
                transform: translateX(-80%);
            }

            100% {
                transform: translateX(200%);
            }
        }

        .intro-bottom {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.72rem;
            color: #cbd5f5;
        }

        .intro-skip-btn {
            border: none;
            background: rgba(15, 23, 42, 0.7);
            color: #e5e7eb;
            font-size: 0.7rem;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, 0.7);
            cursor: pointer;
        }

        .intro-skip-btn:hover {
            background: rgba(15, 23, 42, 0.9);
        }

        /* ================== REGISTER CARD (เหมือนเวอร์ชันน่ารักเดิม) ================== */

        .container-register {
            position: relative;
            z-index: 1;
        }

        .card-register {
            background: #ffffff;
            border-radius: 1.5rem;
            border: 1px solid rgba(148, 163, 184, 0.45);
            box-shadow:
                0 20px 50px rgba(148, 163, 184, 0.35),
                0 0 0 1px rgba(255, 255, 255, 0.8);
            max-width: 480px;
            width: 100%;
            margin-inline: auto;
            overflow: hidden;
            transform-origin: center;
            opacity: 0;
            transform: translateY(12px) scale(0.97);
            transition:
                opacity 0.6s ease,
                transform 0.6s ease;
        }

        .card-register.show {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .card-header-cute {
            background: linear-gradient(135deg, #6366f1, #fb7185);
            padding: 1.3rem 1.6rem 1rem;
            color: #ffffff;
            position: relative;
        }

        .card-header-cute::after {
            content: "";
            position: absolute;
            inset: auto 0 -18px 0;
            height: 18px;
            background: #ffffff;
            border-radius: 50% 50% 0 0;
        }

        .header-top-line {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 0.75rem;
        }

        .header-brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .header-logo {
            width: 34px;
            height: 34px;
            border-radius: 999px;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6366f1;
            box-shadow: 0 8px 22px rgba(15, 23, 42, 0.25);
        }

        .header-text-main {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .header-text-sub {
            font-size: 0.75rem;
            opacity: 0.9;
        }

        .header-pill {
            font-size: 0.7rem;
            padding: 0.2rem 0.65rem;
            border-radius: 999px;
            background: rgba(15, 23, 42, 0.16);
            border: 1px solid rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(8px);
        }

        .header-main-title {
            margin-top: 0.7rem;
        }

        .header-main-title h2 {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 0.1rem;
        }

        .header-main-title p {
            font-size: 0.78rem;
            opacity: 0.95;
        }

        .loading-dots {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            margin-top: 0.35rem;
        }

        .dot {
            width: 6px;
            height: 6px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.9);
            opacity: 0.5;
            animation: bounceDot 1.3s infinite ease-in-out;
        }

        .dot:nth-child(2) {
            animation-delay: 0.15s;
        }

        .dot:nth-child(3) {
            animation-delay: 0.3s;
        }

        @keyframes bounceDot {
            0%, 60%, 100% {
                transform: translateY(0);
                opacity: 0.5;
            }

            30% {
                transform: translateY(-4px);
                opacity: 1;
            }
        }

        .card-body-cute {
            padding: 1.5rem 1.6rem 1.3rem;
        }

        /* Profile */
        .profile-box {
            text-align: center;
            margin-bottom: 1.2rem;
        }

        .profile-avatar-wrap {
            width: 96px;
            height: 96px;
            border-radius: 999px;
            margin-inline: auto;
            padding: 4px;
            background: conic-gradient(from 180deg,
                    #a5b4fc,
                    #f9a8d4,
                    #bef264,
                    #a5b4fc);
        }

        .profile-avatar-inner {
            width: 100%;
            height: 100%;
            border-radius: inherit;
            background: #f9fafb;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .profile-img {
            width: 86px;
            height: 86px;
            border-radius: 999px;
            object-fit: cover;
        }

        .profile-placeholder {
            width: 86px;
            height: 86px;
            border-radius: 999px;
            background: linear-gradient(135deg, #c7d2fe, #fbcfe8);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
        }

        .profile-name {
            margin-top: 0.6rem;
            font-weight: 500;
            font-size: 1rem;
        }

        .profile-sub {
            font-size: 0.75rem;
            color: #6b7280;
        }

        /* Form fields */
        .field-label {
            font-size: 0.82rem;
            margin-bottom: 0.15rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            color: #111827;
        }

        .field-label span.required {
            color: #ef4444;
        }

        .field-hint {
            font-size: 0.7rem;
            color: #9ca3af;
        }

        .input-shell {
            position: relative;
        }

        .input-cute,
        .select-cute {
            width: 100%;
            border-radius: 0.9rem;
            border: 1px solid #cbd5e1;
            padding: 0.7rem 0.85rem;
            font-size: 0.9rem;
            outline: none;
            background: #f9fafb;
            transition:
                border-color 0.16s ease,
                box-shadow 0.16s ease,
                background 0.16s ease,
                transform 0.08s ease;
        }

        .input-cute::placeholder {
            color: #9ca3af;
        }

        .input-cute:focus,
        .select-cute:focus {
            border-color: var(--primary);
            background: #ffffff;
            box-shadow:
                0 0 0 1px rgba(129, 140, 248, 0.45),
                0 8px 18px rgba(129, 140, 248, 0.18);
            transform: translateY(-1px);
        }

        .input-highlight {
            position: absolute;
            inset: -2px;
            border-radius: 1rem;
            background: radial-gradient(circle at 0 0,
                    rgba(129, 140, 248, 0.25),
                    transparent 60%);
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.2s ease;
        }

        .input-cute:focus + .input-highlight,
        .select-cute:focus + .input-highlight {
            opacity: 1;
        }

        .error-text {
            font-size: 0.72rem;
            color: #b91c1c;
            margin-top: 0.18rem;
        }

        /* Error box */
        .alert-soft {
            border-radius: 1rem;
            background: #fee2e2;
            border: 1px solid #fecaca;
            color: #7f1d1d;
            font-size: 0.82rem;
        }

        .alert-soft-title {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            font-weight: 600;
            margin-bottom: 0.2rem;
        }

        .alert-soft ul {
            margin: 0;
            padding-left: 1.2rem;
        }

        .alert-soft li {
            margin-bottom: 0.1rem;
        }

        /* ปุ่ม */
        .btn-cute-primary {
            border-radius: 999px;
            border: none;
            width: 100%;
            padding: 0.85rem 1rem;
            font-size: 0.9rem;
            font-weight: 600;
            background: linear-gradient(135deg, #6366f1, #f97316);
            color: #ffffff;
            box-shadow:
                0 12px 25px rgba(99, 102, 241, 0.45),
                0 0 0 1px rgba(255, 255, 255, 0.9);
            display: inline-flex;
            justify-content: center;
            align-items: center;
            gap: 0.3rem;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            transform-origin: center;
            transition:
                transform 0.1s ease-out,
                box-shadow 0.1s ease-out,
                filter 0.1s ease-out;
        }

        .btn-cute-primary:hover {
            filter: brightness(1.03);
            transform: translateY(-1px);
            box-shadow:
                0 16px 30px rgba(99, 102, 241, 0.6),
                0 0 0 1px rgba(255, 255, 255, 0.9);
        }

        .btn-cute-primary:active {
            transform: translateY(1px);
            box-shadow:
                0 8px 18px rgba(99, 102, 241, 0.4),
                0 0 0 1px rgba(255, 255, 255, 0.9);
        }

        .bottom-note {
            text-align: center;
            font-size: 0.72rem;
            color: #6b7280;
            margin-top: 0.5rem;
        }

        /* Responsive */
        @media (max-width: 576px) {
            .card-register {
                margin-inline: 1rem;
                border-radius: 1.25rem;
            }

            .card-header-cute {
                padding: 1.1rem 1.2rem 0.9rem;
            }

            .card-body-cute {
                padding: 1.3rem 1.2rem 1.1rem;
            }

            .header-main-title h2 {
                font-size: 1.18rem;
            }

            .profile-avatar-wrap {
                width: 88px;
                height: 88px;
            }

            .profile-img,
            .profile-placeholder {
                width: 78px;
                height: 78px;
            }

            .intro-card {
                width: min(320px, 92vw);
            }
        }
    </style>
</head>

<body>

    <!-- ================= INTRO SHOPPING SCREEN ================= -->
    <div id="introScreen">
        <div class="intro-card">
            <div class="intro-header">
                <div class="intro-icon-wrap">
                    <i class="bi bi-cart-check"></i>
                    <div class="intro-tag">
                        <i class="bi bi-stars"></i>
                        <span>NEW</span>
                    </div>
                </div>
                <div>
                    <div class="intro-title-main">กำลังเตรียมร้านให้คุณ...</div>
                    <div class="intro-title-sub">เชื่อมต่อระบบและดึงข้อมูลจาก LINE</div>
                </div>
            </div>

            <div class="intro-cart-track">
                <div class="intro-cart">
                    <div class="intro-cart-inner">
                        <div class="intro-cart-item"></div>
                        <div class="intro-cart-item"></div>
                        <div class="intro-cart-item"></div>
                    </div>
                    <div class="intro-cart-wheel left"></div>
                    <div class="intro-cart-wheel right"></div>
                </div>
                <div class="intro-track-line"></div>
            </div>

            <div class="intro-progress-wrap">
                <div class="intro-progress-bar">
                    <div class="intro-progress-fill"></div>
                </div>
            </div>

            <div class="intro-bottom">
                <span>กำลังโหลดแบบฟอร์มสมัครสมาชิก...</span>
                <button type="button" class="intro-skip-btn" id="introSkipBtn">
                    ข้าม
                </button>
            </div>
        </div>
    </div>

    <!-- ================= REGISTER FORM ================= -->
    <div class="container container-register d-flex align-items-center justify-content-center min-vh-100 py-4">
        <div class="card-register" id="registerWrapper">
            <!-- Header -->
            <div class="card-header-cute">
                <div class="header-top-line">
                    <div class="header-brand">
                        <div class="header-logo">
                            <i class="bi bi-chat-heart"></i>
                        </div>
                        <div>
                            <div class="header-text-main">LINE Member</div>
                            <div class="header-text-sub">เชื่อมบัญชีอย่างปลอดภัย</div>
                        </div>
                    </div>
                    <div class="header-pill">
                        <i class="bi bi-shield-check me-1"></i> Verified
                    </div>
                </div>

                <div class="header-main-title">
                    <h2>สมัครสมาชิกใหม่</h2>
                    <p>ล็อกอินด้วย LINE แล้วกรอกข้อมูลไม่กี่ข้อ ก็เริ่มใช้งานระบบได้ทันที</p>

                    <div class="loading-dots" aria-hidden="true">
                        <div class="dot"></div>
                        <div class="dot"></div>
                        <div class="dot"></div>
                    </div>
                </div>
            </div>

            <!-- Body -->
            <div class="card-body-cute">
                <!-- Error messages -->
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-soft alert-dismissible fade show mb-3" role="alert">
                        <div class="alert-soft-title">
                            <i class="bi bi-exclamation-circle-fill"></i>
                            <span>กรุณาตรวจสอบข้อมูลอีกครั้ง</span>
                        </div>
                        <ul class="mb-0">
                            <?php foreach ($errors as $e): ?>
                                <li><?php echo htmlspecialchars($e, ENT_QUOTES, 'UTF-8'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"
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
                    <div class="profile-box">
                        <div class="profile-avatar-wrap">
                            <div class="profile-avatar-inner">
                                <?php if ($picture_url): ?>
                                    <img src="<?php echo htmlspecialchars($picture_url, ENT_QUOTES, 'UTF-8'); ?>"
                                        alt="LINE Profile Picture" class="profile-img">
                                <?php else: ?>
                                    <div class="profile-placeholder">
                                        <span>👤</span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="profile-name">
                            <?php echo htmlspecialchars($display_name ?: '(ไม่ทราบชื่อจาก LINE)', ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div class="profile-sub">
                            ข้อมูลจาก LINE Official Account
                        </div>
                    </div>

                    <!-- Form fields -->
                    <div class="mb-3">
                        <label class="field-label">
                            <span>คำนำหน้า <span class="required">*</span></span>
                        </label>
                        <div class="input-shell">
                            <select name="title" class="select-cute" required>
                                <option value="">-- เลือกคำนำหน้า --</option>
                                <option value="นาย" <?php echo ($title === 'นาย') ? 'selected' : ''; ?>>นาย</option>
                                <option value="นาง" <?php echo ($title === 'นาง') ? 'selected' : ''; ?>>นาง</option>
                                <option value="นางสาว" <?php echo ($title === 'นางสาว') ? 'selected' : ''; ?>>นางสาว</option>
                            </select>
                            <div class="input-highlight"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="field-label">
                            <span>ชื่อจริง <span class="required">*</span></span>
                        </label>
                        <div class="input-shell">
                            <input type="text"
                                class="input-cute"
                                name="first_name" required
                                value="<?php echo htmlspecialchars($first_name ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="กรอกชื่อจริงของคุณ">
                            <div class="input-highlight"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="field-label">
                            <span>นามสกุล <span class="required">*</span></span>
                        </label>
                        <div class="input-shell">
                            <input type="text"
                                class="input-cute"
                                name="last_name" required
                                value="<?php echo htmlspecialchars($last_name ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="กรอกนามสกุลของคุณ">
                            <div class="input-highlight"></div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="field-label">
                            <span>เบอร์โทรศัพท์ <span class="required">*</span></span>
                            <span class="field-hint">ใช้สำหรับติดต่อยืนยันการใช้งาน</span>
                        </label>
                        <div class="input-shell">
                            <input type="tel"
                                class="input-cute"
                                id="phone" name="phone"
                                maxlength="12" inputmode="numeric" required
                                value="<?php echo htmlspecialchars($phone ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="0xx-xxx-xxxx">
                            <div class="input-highlight"></div>
                        </div>
                        <div id="phone-error" class="error-text"></div>
                    </div>

                    <div class="mb-2">
                        <label class="field-label">
                            <span>เลขบัตรประชาชน 13 หลัก <span class="required">*</span></span>
                            <span class="field-hint">ข้อมูลจะถูกเก็บรักษาอย่างปลอดภัย</span>
                        </label>
                        <div class="input-shell">
                            <input type="tel"
                                class="input-cute"
                                id="citizen_id" name="citizen_id"
                                maxlength="17" inputmode="numeric" required
                                value="<?php echo htmlspecialchars($citizen_id ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                placeholder="1-2345-67890-12-3">
                            <div class="input-highlight"></div>
                        </div>
                        <div id="citizen-id-error" class="error-text"></div>
                    </div>

                    <div class="d-grid gap-2 mt-4">
                        <button type="submit" class="btn-cute-primary">
                            <span>สมัครสมาชิก</span>
                            <i class="bi bi-arrow-right-short"></i>
                        </button>
                    </div>

                    <p class="bottom-note">
                        การสมัครแสดงว่าคุณยอมรับเงื่อนไขการใช้บริการและนโยบายความเป็นส่วนตัวของระบบ
                    </p>
                </form>
            </div>
        </div>
    </div>

    <!-- JS format เบอร์/บัตรประชาชน + validate -->
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

    <!-- JS คุม intro → ไปหน้า register -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const intro = document.getElementById("introScreen");
            const skipBtn = document.getElementById("introSkipBtn");
            const wrapper = document.getElementById("registerWrapper");
            const hasErrors = <?php echo !empty($errors) ? 'true' : 'false'; ?>;

            // ถ้ามี error (reload หลังกรอกผิด) => ข้าม intro เลย
            if (hasErrors) {
                intro.classList.add("hidden-intro");
                wrapper.classList.add("show");
                return;
            }

            function showRegister() {
                intro.classList.add("hidden-intro");
                wrapper.classList.add("show");
            }

            // auto เปลี่ยนจอหลัง 2.4 วิ
            setTimeout(showRegister, 2400);

            // ปุ่ม "ข้าม"
            if (skipBtn) {
                skipBtn.addEventListener("click", function() {
                    showRegister();
                });
            }
        });
    </script>

</body>
</html>