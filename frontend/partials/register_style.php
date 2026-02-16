<?php
// อนุญาตให้ override โลโก้จากหน้าอื่นได้ ถ้าไม่ส่งมาก็ใช้ค่า default นี้
$logoUrl = $logoUrl ?? 'https://res.cloudinary.com/dfs4n2p9b/image/upload/v1764295982/Medicine_Naresuan_pyacvu.png';
?>
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
        /* กันเนื้อหาโดน navbar ทับ */
        padding-top: 72px;
    }

    /* ========== NAVBAR แบบเดิม แต่โทนใหม่ให้เข้ากับการ์ด ========== */

    .auth-navbar {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 2000;
        backdrop-filter: blur(14px);
        -webkit-backdrop-filter: blur(14px);
        background:
            linear-gradient(90deg, rgba(15, 23, 42, 0.92), rgba(30, 64, 175, 0.96));
        border-bottom: 1px solid rgba(148, 163, 184, 0.5);
        padding-top: 8px;
        padding-bottom: 8px;
    }

    .auth-navbar .navbar-brand {
        display: flex;
        align-items: center;
        font-weight: 700;
        font-size: 1.1rem;
        color: #e5e7eb;
        letter-spacing: 0.02em;
    }

    .auth-navbar .navbar-brand span {
        margin-left: 8px;
    }

    .auth-navbar .navbar-brand small {
        display: block;
        font-weight: 400;
        font-size: 0.72rem;
        color: #9ca3af;
        margin-top: -2px;
    }

    .auth-navbar .navbar-brand:hover {
        color: #ffffff;
    }

    .auth-navbar-logo {
        height: 40px;
        width: 40px;
        border-radius: 999px;
        border: 2px solid rgba(248, 250, 252, 0.85);
        background: #fff;
        object-fit: cover;
        box-shadow: 0 8px 18px rgba(15, 23, 42, 0.6);
    }

    .auth-navbar-pill {
        font-size: 0.7rem;
        padding: 0.2rem 0.7rem;
        border-radius: 999px;
        border: 1px solid rgba(248, 250, 252, 0.7);
        color: #e5e7eb;
        display: inline-flex;
        align-items: center;
        gap: 0.25rem;
        background: linear-gradient(135deg,
                rgba(129, 140, 248, 0.35),
                rgba(248, 113, 22, 0.35));
    }

    .auth-navbar-pill i {
        font-size: 0.9rem;
    }

    @media (max-width: 576px) {
        body {
            padding-top: 64px;
        }

        .auth-navbar .navbar-brand span strong {
            font-size: 0.98rem;
        }

        .auth-navbar-logo {
            height: 34px;
            width: 34px;
        }
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
        z-index: 1900; /* ต่ำกว่า navbar นิดนึง */
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

    /* ================== REGISTER CARD ================== */

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

    .success-icon-big {
        font-size: 3.2rem;
        margin-bottom: 0.4rem;
    }

    .success-text-main {
        font-size: 1.05rem;
        font-weight: 600;
    }

    .success-text-sub {
        font-size: 0.85rem;
        color: #6b7280;
    }

    .btn-success-soft {
        border-radius: 999px;
        padding: 0.7rem 1rem;
        font-size: 0.9rem;
        font-weight: 600;
    }

    .btn-outline-soft {
        border-radius: 999px;
        padding: 0.7rem 1rem;
        font-size: 0.9rem;
        font-weight: 600;
    }

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

<!-- NAVBAR ด้านบน (ใช้ร่วมได้ทั้ง register.php และหน้าอื่นที่ include partial นี้) -->
<nav class="navbar auth-navbar">
    <div class="container d-flex justify-content-between align-items-center">
        <a class="navbar-brand" href="#">
            <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8'); ?>"
                 alt="Logo"
                 class="auth-navbar-logo me-2">
            <span>
                <strong>Line Shop</strong>
                <small>สมัครสมาชิก / เชื่อมต่อ LINE</small>
            </span>
        </a>

        <div class="d-none d-sm-block">
            <div class="auth-navbar-pill">
                <i class="bi bi-shield-lock"></i>
                <span>ข้อมูลของคุณถูกเข้ารหัสอย่างปลอดภัย</span>
            </div>
        </div>
    </div>
</nav>
