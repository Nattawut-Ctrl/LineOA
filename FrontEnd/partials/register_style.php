<?php
$logoUrl = $logoUrl ?? 'https://res.cloudinary.com/dfs4n2p9b/image/upload/v1764295982/Medicine_Naresuan_pyacvu.png';
?>
<style>
    body.auth-bg {
        background-color: #f5f5f5;
        font-family: "Kanit", system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
    }

    /* Navbar สไตล์ Shopee */
    .auth-navbar {
        background-color: #ffffff;
        border-bottom: 1px solid #f2f2f2;
        padding-top: 10px;
        padding-bottom: 10px;
    }

    .auth-navbar .navbar-brand {
        display: flex;
        align-items: center;
        font-weight: 700;
        font-size: 1.4rem;
        color: #ee4d2d;
    }

    .auth-navbar .navbar-brand span {
        margin-left: 8px;
    }

    .auth-navbar .navbar-brand:hover {
        color: #ff6a3c;
    }

    .auth-navbar-logo {
        height: 40px;
        width: auto;
        border-radius: 50%;
        border: 2px solid #ffdccf;
        background: #fff;
        object-fit: cover;
    }

    /* การ์ดสมัครสมาชิก */
    .auth-card {
        border-radius: 18px;
        border: 1px solid #ffe0d6;
        box-shadow: 0 14px 35px rgba(0, 0, 0, 0.06);
        overflow: hidden;
    }

    .auth-card-header {
        background: linear-gradient(135deg, #ff7337, #ee4d2d);
        color: #fff;
        border: none;
    }

    .auth-card-header h2 {
        font-weight: 700;
    }

    .auth-card-header small {
        opacity: 0.9;
    }

    /* ปุ่มส้มสไตล์ Shopee */
    .btn-auth-primary {
        background-color: #ee4d2d;
        border-color: #ee4d2d;
        color: #fff;
        font-weight: 700;
        letter-spacing: 0.02em;
    }

    .btn-auth-primary:hover {
        background-color: #ff6a3c;
        border-color: #ff6a3c;
        color: #fff;
    }

    .btn-auth-outline {
        border-color: #ee4d2d;
        color: #ee4d2d;
        font-weight: 600;
    }

    .btn-auth-outline:hover {
        background-color: #ffeee8;
        border-color: #ff6a3c;
        color: #ff6a3c;
    }

    /* input */
    .auth-form-label {
        font-weight: 600;
        color: #333;
    }

    .auth-form-control {
        border-radius: 10px;
        border: 1px solid #ddd;
    }

    .auth-form-control:focus {
        border-color: #ee4d2d;
        box-shadow: 0 0 0 0.15rem rgba(238, 77, 45, 0.18);
    }

    .auth-note {
        font-size: 0.8rem;
        color: #999;
    }
</style>

<!-- Navbar ด้านบน -->
<nav class="navbar auth-navbar">
    <div class="container">
        <a class="navbar-brand" href="#">
            <img src="<?php echo htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8'); ?>"
                 alt="Logo"
                 class="auth-navbar-logo me-2">
            <span>Line Shop</span>
        </a>
    </div>
</nav>
