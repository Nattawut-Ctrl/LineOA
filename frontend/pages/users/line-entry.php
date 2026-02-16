<?php
require_once '../../../config.php';
?>
<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>เข้าสู่ระบบผ่าน LINE</title>

  <!-- โหลด SDK ของ LIFF -->
  <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600&family=Kanit:wght@300;400;600&display=swap');

    :root{
      --bg1:#e6f0ff;
      --bg2:#f7fbff;
      --card:#ffffff;
      --accent:#00c300;
      --muted:#6b7280;
      --glass: rgba(255,255,255,0.6);
    }

    html,body{
      height:100%;
    }

    body {
      font-family: "Inter", "Kanit", sans-serif;
      display: flex;
      align-items: center;
      justify-content: center;
      background: linear-gradient(135deg,var(--bg1),var(--bg2));
      color: #111827;
      margin:0;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
      padding:24px;
    }

    .wrap {
      width:100%;
      max-width:420px;
      margin:24px;
    }

    .card {
      background: linear-gradient(180deg, rgba(255,255,255,0.9), var(--card));
      border-radius:12px;
      box-shadow: 0 8px 30px rgba(16,24,40,0.08);
      padding:28px;
      text-align:center;
      backdrop-filter: blur(6px);
    }

    .brand {
      display:flex;
      gap:12px;
      align-items:center;
      justify-content:center;
      margin-bottom:12px;
    }

    .logo {
      width:56px;
      height:56px;
      border-radius:12px;
      background:linear-gradient(135deg,#00c300,#00a3f7);
      display:inline-flex;
      align-items:center;
      justify-content:center;
      color:white;
      font-weight:700;
      box-shadow: 0 6px 18px rgba(3,7,18,0.06);
      flex:0 0 56px;
    }

    h2.title {
      margin:0;
      font-size:1.25rem;
      font-weight:600;
      color:#0f172a;
    }

    p.subtitle {
      margin:6px 0 18px 0;
      color:var(--muted);
      font-size:0.95rem;
    }

    .spinner {
      width:64px;
      height:64px;
      margin:10px auto 14px;
      border-radius:50%;
      position:relative;
      display:inline-block;
      filter: drop-shadow(0 6px 18px rgba(2,6,23,0.07));
    }

    .spinner::before, .spinner::after {
      content:'';
      position:absolute;
      inset:0;
      border-radius:50%;
      border:6px solid rgba(0,0,0,0.06);
      animation:rotate 1.4s linear infinite;
      box-sizing:border-box;
    }
    .spinner::after{
      border-color:transparent rgba(0,163,247,0.9) rgba(0,163,247,0.35) transparent;
      transform:rotate(45deg);
      animation-duration:1.1s;
    }

    @keyframes rotate{
      to{ transform: rotate(360deg); }
    }

    .status {
      font-size:0.95rem;
      color:var(--muted);
      margin-top:8px;
    }

    .profile {
      display:flex;
      gap:12px;
      align-items:center;
      justify-content:center;
      margin-top:14px;
    }
    .avatar{
      width:56px;height:56px;border-radius:50%;object-fit:cover;box-shadow:0 6px 18px rgba(2,6,23,0.06);
    }
    .profile .meta { text-align:left; }
    .meta .name { font-weight:600; color:#0f172a; }
    .meta .id { font-size:0.85rem; color:var(--muted); margin-top:2px; }

    .btn {
      display:inline-block;
      margin-top:16px;
      background:linear-gradient(90deg,#00c300 0%, #00a3f7 100%);
      color:#fff;
      padding:10px 18px;
      border-radius:10px;
      text-decoration:none;
      font-weight:600;
      box-shadow: 0 8px 20px rgba(3,7,18,0.08);
      border: none;
      cursor: pointer;
    }

    .muted-link{
      display:block;margin-top:12px;color:var(--muted);font-size:0.85rem;text-decoration:none;
    }

    @media (max-width:420px){
      .card{ padding:20px; }
    }
  </style>
</head>

<body>
  <div class="wrap">
    <div class="card" id="app">
      <div class="brand">
        <div class="logo">LINE</div>
        <div>
          <h2 class="title">เข้าสู่ระบบด้วย LINE</h2>
          <div class="subtitle">รวดเร็ว ปลอดภัย และเชื่อมต่อกับบัญชีของคุณ</div>
        </div>
      </div>

      <div class="spinner" aria-hidden="true"></div>
      <div class="status" id="status">กำลังเชื่อมต่อกับ LINE...</div>

      <!-- profile preview (hidden until available) -->
      <div class="profile" id="profile" style="display:none;">
        <img src="" alt="avatar" class="avatar" id="avatar">
        <div class="meta">
          <div class="name" id="displayName"></div>
          <div class="id" id="userId"></div>
        </div>
      </div>

      <button class="btn" id="manualContinue" style="display:none;">ดำเนินการต่อ</button>
      <a class="muted-link" href="#" id="helpLink">ไม่สามารถเข้าสู่ระบบได้? ลองอีกครั้ง</a>
    </div>
  </div>

  <script>
    const liffId = "2008995534-0KoF8ybe";

    const params = new URLSearchParams(location.search);
    const from = params.get("from") || "shop";

    const FRONTEND_URL = "<?= FRONTEND_URL ?>";

    const statusEl = document.getElementById('status');
    const profileEl = document.getElementById('profile');
    const avatarEl = document.getElementById('avatar');
    const nameEl = document.getElementById('displayName');
    const idEl = document.getElementById('userId');
    const manualBtn = document.getElementById('manualContinue');
    const helpLink = document.getElementById('helpLink');

    function setStatus(text){
      statusEl.textContent = text;
    }

    async function submitForm(profile){
      setStatus('ส่งข้อมูลไปยังระบบ...');
      const form = document.createElement("form");
      form.method = "POST";
      form.action = FRONTEND_URL + "/actions/users/checkLineUser.php";

      const fields = {
        line_uid: profile.userId,
        display_name: profile.displayName || '',
        picture_url: profile.pictureUrl || '',
        from
      };

      for(const k in fields){
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = k;
        inp.value = fields[k];
        form.appendChild(inp);
      }

      document.body.appendChild(form);
      form.submit();
    }

    async function main() {
      try {
        setStatus('เตรียมการเชื่อมต่อ LIFF...');
        // If LIFF missing (desktop test), show manual fallback
        if (typeof liff === 'undefined') {
          setStatus('LIFF ไม่พร้อมใช้งาน — คุณสามารถดำเนินการต่อด้วยปุ่มด้านล่าง');
          manualBtn.style.display = 'inline-block';
          manualBtn.addEventListener('click', ()=> {
            // open fallback flow: show simple prompt to paste LINE user id (minimal)
            const uid = prompt('วาง line userId ของคุณ (สำหรับทดสอบ):') || '';
            if(!uid) return;
            submitForm({ userId: uid, displayName: 'Guest', pictureUrl: '' });
          });
          return;
        }

        await liff.init({ liffId });
        setStatus('ตรวจสอบสถานะการล็อกอิน...');
        if (!liff.isLoggedIn()) {
          setStatus('กำลังเปลี่ยนไปยังหน้าเข้าสู่ระบบ LINE...');
          liff.login();
          return;
        }

        setStatus('ดึงข้อมูลโปรไฟล์...');
        const profile = await liff.getProfile();

        // show preview
        avatarEl.src = profile.pictureUrl || 'https://via.placeholder.com/128?text=User';
        nameEl.textContent = profile.displayName || 'LINE User';
        idEl.textContent = profile.userId;
        profileEl.style.display = 'flex';
        setStatus('ยืนยันข้อมูลและกำลังส่ง...');

        // small delay for UX then submit
        setTimeout(()=> submitForm(profile), 600);

      } catch (err) {
        console.error("LIFF error:", err);
        setStatus('เกิดข้อผิดพลาด: ' + (err.message || err));
        helpLink.addEventListener('click', (e)=> {
          e.preventDefault();
          location.reload();
        });
      }
    }

    main();
  </script>
</body>

</html>