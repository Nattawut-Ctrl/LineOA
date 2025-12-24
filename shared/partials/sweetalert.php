<?php
// partials/sweetalert.php
// ใช้สำหรับ include ในทุกหน้าเพื่อเรียก SweetAlert2 ได้ทันที
?>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
// ---- Helper กลาง (เรียกใช้ได้ทุกหน้า) ----
window.SA = {
  success: function (title, text, thenFn) {
    Swal.fire({
      icon: 'success',
      title: title || 'สำเร็จ',
      text: text || '',
      confirmButtonText: 'ตกลง',
      confirmButtonColor: '#ee4d2d'
    }).then(() => { if (typeof thenFn === 'function') thenFn(); });
  },

  error: function (title, text, thenFn) {
    Swal.fire({
      icon: 'error',
      title: title || 'เกิดข้อผิดพลาด',
      text: text || '',
      confirmButtonText: 'ตกลง'
    }).then(() => { if (typeof thenFn === 'function') thenFn(); });
  },

  confirm: function (title, text, okText, cancelText, thenFn) {
    Swal.fire({
      icon: 'question',
      title: title || 'ยืนยันการทำรายการ',
      text: text || '',
      showCancelButton: true,
      confirmButtonText: okText || 'ยืนยัน',
      cancelButtonText: cancelText || 'ยกเลิก',
      confirmButtonColor: '#ee4d2d'
    }).then((result) => { if (typeof thenFn === 'function') thenFn(result.isConfirmed); });
  }
};
</script>
