<!DOCTYPE html>
<html lang="th">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>เข้าสู่ระบบผ่าน LINE</title>

  <!-- โหลด SDK ของ LIFF -->
  <script src="https://static.line-scdn.net/liff/edge/2/sdk.js"></script>

  <style>
    body {
      font-family: "Kanit", sans-serif;
      text-align: center;
      background-color: #f4f7fa;
      color: #333;
      padding: 50px;
    }

    .loading {
      font-size: 1.2em;
      color: #666;
    }
  </style>
</head>

<body>
  <h2>กำลังเข้าสู่ระบบผ่าน LINE...</h2>
  <p class="loading">กรุณารอสักครู่...</p>

  <script>
    const liffId = "2008474276-zZ2DZolb";

    const params = new URLSearchParams(location.search);
    const from = params.get("from") || "shop";

    async function main() {
      try {
        // เริ่มต้น LIFF
        await liff.init({
          liffId
        });

        // ถ้ายังไม่ล็อกอิน ให้ไป login ก่อน
        if (!liff.isLoggedIn()) {
          liff.login();
          return;
        }

        // ดึงข้อมูลโปรไฟล์ของผู้ใช้
        const profile = await liff.getProfile();
        const userId = profile.userId;
        const name = profile.displayName;
        const pictureUrl = profile.pictureUrl || "";

        // สร้างฟอร์มส่งข้อมูลไป checkLineUser.php
        const form = document.createElement("form");
        form.method = "POST";
        form.action = "checkLineUser.php";

        const uidInput = document.createElement("input");
        uidInput.type = "hidden";
        uidInput.name = "line_uid";
        uidInput.value = userId;
        form.appendChild(uidInput);

        const nameInput = document.createElement("input");
        nameInput.type = "hidden";
        nameInput.name = "display_name";
        nameInput.value = name;
        form.appendChild(nameInput);

        const picInput = document.createElement("input");
        picInput.type = "hidden";
        picInput.name = "picture_url";
        picInput.value = pictureUrl;
        form.appendChild(picInput);

        const fromInput = document.createElement("input");
        fromInput.type = "hidden";
        fromInput.name = "from";
        fromInput.value = from;
        form.appendChild(fromInput);

        document.body.appendChild(form);
        form.submit();

      } catch (err) {
        console.error("LIFF error:", err);
        document.body.innerHTML = `
          <h2>เกิดข้อผิดพลาดในการเข้าสู่ระบบ</h2>
          <p>${err.message}</p>
        `;
      }
    }

    main();
  </script>
</body>

</html>