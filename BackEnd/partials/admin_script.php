<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.7/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@4.0.0/dist/js/adminlte.min.js"></script>

<script>
  // ✅ ปุ่มย่อ/ขยาย sidebar แบบ mini (desktop)
  const btnMini = document.getElementById("btnSidebarMini");
  btnMini?.addEventListener("click", (e) => {
    e.preventDefault();
    document.body.classList.toggle("sidebar-collapse");

    // จำสถานะไว้
    localStorage.setItem("sidebar-collapse",
      document.body.classList.contains("sidebar-collapse") ? "1" : "0"
    );
  });

  // ✅ โหลดสถานะเดิมตอนเปิดหน้าใหม่
  const collapsed = localStorage.getItem("sidebar-collapse");
  if (collapsed === "1") {
    document.body.classList.add("sidebar-collapse");
  }
</script>
