<?php
session_start();

require_once dirname(__DIR__, 3) . '/config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';

require_admin();
$conn = connectDBWithLog();

// สินค้าสำหรับ dropdown
$products = [];
$resP = db_query($conn, "SELECT id, name FROM products ORDER BY id DESC");
if ($resP) {
    while ($r = $resP->fetch_assoc()) {
        $products[] = $r;
    }
}

// variants ทั้งหมด (ทำ map ใน JS)
$variantsMap = [];
$resV = db_query($conn, "SELECT id, product_id, variant_name FROM product_variants ORDER BY id ASC");
if ($resV) {
    while ($v = $resV->fetch_assoc()) {
        $pid = (int)$v['product_id'];
        if (!isset($variantsMap[$pid])) $variantsMap[$pid] = [];
        $variantsMap[$pid][] = [
            'id' => (int)$v['id'],
            'name' => $v['variant_name'],
        ];
    }
}

$pageTitle  = "ใบรับของ (รับสินค้าเข้าคงคลัง)";
$activeMenu = "receipt";

$API_CONFIRM = rtrim(BACKEND_URL, '/') . '/api/stock/receipt_confirm.php';
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>ใบรับของ | Line Shop</title>
    <?php include BACKEND_PATH . '/partials/admin_head.php'; ?>
    <style>
        .card {
            border-radius: 0.85rem;
        }

        .table td,
        .table th {
            vertical-align: middle;
        }

        .num {
            text-align: right;
        }
    </style>
    <script>
        const VARIANTS_MAP = <?= json_encode($variantsMap, JSON_UNESCAPED_UNICODE) ?>;
        let ACTIVE_ROW = null;

        function buildVariantOptions(productId, selectedId = 0) {
            if (!productId) {
                return `<option value="0" selected>(เลือกสินค้าเพื่อแสดงตัวเลือก)</option>`;
            }
            const vars = VARIANTS_MAP[productId] || [];
            if (!vars.length) {
                return `<option value="0" selected>(ไม่มีตัวเลือก)</option>`;
            }

            let html = ``;
            for (const v of vars) {
                const sel = (selectedId && Number(selectedId) === Number(v.id)) ? 'selected' : '';
                html += `<option value="${v.id}" ${sel}>${v.name}</option>`;
            }
            return html;
        }

        function onProductChange(rowEl) {
            const pSel = rowEl.querySelector('select[name="product_id[]"]');
            const vSel = rowEl.querySelector('select[name="variant_id[]"]');
            const pid = Number(pSel.value || 0);

            const vars = VARIANTS_MAP[pid] || [];
            vSel.innerHTML = buildVariantOptions(pid, 0);

            if (vars.length) {
                vSel.required = true;
                // auto-select ตัวแรกเสมอ
                vSel.value = String(vars[0].id);
            } else {
                // ถ้าไม่มี variant จริง ๆ ค่อยไม่บังคับ (แต่แนะนำให้ทุก product มี default variant)
                vSel.required = false;
                vSel.value = '0';
            }
        }

        function addRow() {
            const tbody = document.getElementById('itemsBody');
            const tpl = document.getElementById('rowTemplate');
            const rowFrag = tpl.content.cloneNode(true);
            tbody.appendChild(rowFrag);

            const lastRow = tbody.querySelector('tr:last-child');
            const pSel = lastRow.querySelector('select[name="product_id[]"]');
            const vSel = lastRow.querySelector('select[name="variant_id[]"]');

            // ถ้าอย่างน้อยมี 2 แถว ให้คัดลอกข้อมูลจากแถวก่อนหน้า
            const allRows = tbody.querySelectorAll('tr');
            if (allRows.length >= 2) {
                const prevRow = allRows[allRows.length - 2]; // แถวก่อนหน้า
                const prevProductId = prevRow.querySelector('select[name="product_id[]"]').value;
                const prevVariantId = prevRow.querySelector('select[name="variant_id[]"]').value;

                // คัดลอกค่าจากแถวก่อนหน้า
                pSel.value = prevProductId;

                // rebuild variants + enforce required ตาม onProductChange()
                onProductChange(lastRow);

                // ตั้งค่า variant เดียวกับแถวก่อนหน้า (ถ้ามี)
                if (prevVariantId && prevVariantId !== '0') {
                    vSel.value = prevVariantId;
                }
            } else {
                // ถ้ายังไม่ได้เลือกสินค้า ให้แสดง option เริ่มต้นไว้ก่อน
                const pid = Number(pSel.value || 0);
                vSel.innerHTML = buildVariantOptions(pid, 0);
            }
        }

        function addProductOptionToAllSelects(productId, productName) {
            const selects = document.querySelectorAll('select[name="product_id[]"]');
            selects.forEach(sel => {
                // กันเพิ่มซ้ำ
                if (sel.querySelector(`option[value="${productId}"]`)) return;

                const opt = document.createElement('option');
                opt.value = String(productId);
                opt.textContent = productName;

                const firstOpt = sel.querySelector('option');
                if (firstOpt && firstOpt.nextSibling) {
                    sel.insertBefore(opt, firstOpt.nextSibling);
                } else {
                    sel.appendChild(opt);
                }
            });
        }

        function setRowProductVariant(rowEl, productId, variantId) {
            const pSel = rowEl.querySelector('select[name="product_id[]"]');
            const vSel = rowEl.querySelector('select[name="variant_id[]"]');

            pSel.value = String(productId);

            // rebuild variants + enforce required ตาม onProductChange()
            onProductChange(rowEl);

            if (variantId && vSel) {
                vSel.value = String(variantId);
            }
        }

        function removeRow(btn) {
            const tr = btn.closest('tr');
            if (!tr) return;
            const tbody = document.getElementById('itemsBody');
            if (tbody.querySelectorAll('tr').length <= 1) {
                // อย่างน้อยต้องมี 1 แถว
                tr.querySelector('input[name="qty[]"]').value = '';
                tr.querySelector('input[name="cost_price[]"]').value = '';
                tr.querySelector('input[name="sell_price[]"]').value = '';
                return;
            }
            tr.remove();
        }

        document.addEventListener('DOMContentLoaded', () => {
            addRow();

            document.addEventListener('click', (e) => {
                const tr = e.target.closest('#itemsBody tr');
                if (tr) ACTIVE_ROW = tr;
            });

            document.addEventListener('focusin', (e) => {
                const tr = e.target.closest('#itemsBody tr');
                if (tr) ACTIVE_ROW = tr;
            });

            document.getElementById('btnOpenCreateProduct').addEventListener('click', function() {
                window.location.href = 'addStock.php';
            });
        });
    </script>
</head>

<body class="layout-fixed sidebar-expand-lg bg-body-tertiary sidebar-mini">
    <div class="app-wrapper">
        <?php include BACKEND_PATH . '/partials/admin_navbar.php'; ?>
        <?php include BACKEND_PATH . '/partials/admin_sidebar.php'; ?>

        <main class="app-main">
            <div class="app-content">
                <div class="container-fluid pt-3 pb-4">

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="mb-0">ใบรับของ</h4>
                            <div class="text-muted small">สร้างล็อตสินค้า + เพิ่มสต๊อก + (ตัวเลือก) อัปเดตราคาขาย</div>
                        </div>
                        <div>
                            <a class="btn btn-outline-secondary" href="<?= BACKEND_URL ?>/pages/stock/receipts.php">ดูรายการใบรับของ</a>
                            <a class="btn btn-outline-primary" href="<?= BACKEND_URL ?>/pages/stock/lots.php">ดูล็อตทั้งหมด</a>
                        </div>
                    </div>

                    <?php if (!empty($_GET['error'])): ?>
                        <div class="alert alert-danger">เกิดข้อผิดพลาด: <?= htmlspecialchars($_GET['error']) ?></div>
                    <?php endif; ?>

                    <button id="btnOpenCreateProduct" type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#modalCreateProduct">
                        + สร้างสินค้าใหม่
                    </button>

                    <div class="modal fade" id="modalCreateProduct" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <form id="formCreateProduct" class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">สร้างสินค้าใหม่ (พร้อม Variant “มาตรฐาน”)</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>

                                <div class="modal-body">
                                    <div class="row g-3">
                                        <div class="col-md-8">
                                            <label class="form-label">ชื่อสินค้า</label>
                                            <input name="name" class="form-control" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">ราคาเริ่มต้น (ใส่ไว้ก่อน)</label>
                                            <input name="price" type="number" step="0.01" min="0" class="form-control" value="0">
                                        </div>

                                        <div class="col-md-6">
                                            <label class="form-label">หมวดหมู่ (category)</label>
                                            <input name="category" class="form-control" placeholder="เช่น การ์ดเกม / อุปกรณ์ / อื่นๆ">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">หน่วยนับ (unit)</label>
                                            <input name="unit" class="form-control" placeholder="เช่น ชิ้น / ซอง / กล่อง">
                                        </div>

                                        <div class="col-12">
                                            <label class="form-label">คำอธิบาย</label>
                                            <textarea name="description" class="form-control" rows="3"></textarea>
                                        </div>
                                    </div>

                                    <div class="alert alert-info mt-3 mb-0">
                                        ระบบจะสร้าง Variant “มาตรฐาน” ให้อัตโนมัติ เพื่อใช้เป็น SKU ในการขาย/รับเข้า (Shopee-like)
                                    </div>
                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                                    <button type="submit" class="btn btn-primary">บันทึก</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <form method="POST" action="<?= $API_CONFIRM ?>">
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-3">
                                        <label class="form-label">วันที่รับของ</label>
                                        <input type="date" name="receipt_date" class="form-control" value="<?= htmlspecialchars(date('Y-m-d')) ?>" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">ชื่อร้านค้า (ไม่บังคับ)</label>
                                        <input type="text" name="supplier_name" class="form-control" placeholder="เช่น ร้าน A">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">เลขอ้างอิง/เลขบิล (ไม่บังคับ)</label>
                                        <input type="text" name="reference_no" class="form-control" placeholder="เช่น INV-123">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">ตัวเลือก</label>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" value="1" id="updatePrice" name="update_price">
                                            <label class="form-check-label" for="updatePrice">อัปเดตราคาขายตามใบรับของนี้</label>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">หมายเหตุ</label>
                                        <textarea name="note" class="form-control" rows="2" placeholder="(ไม่บังคับ)"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div class="fw-semibold">รายการสินค้าในใบรับของ</div>
                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="addRow()">+ เพิ่มรายการ</button>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover mb-0">
                                        <thead>
                                            <tr>
                                                <th style="width: 26%">สินค้า</th>
                                                <th style="width: 22%">ตัวเลือก (Variant)</th>
                                                <th class="num" style="width: 10%">จำนวนรับเข้า</th>
                                                <th class="num" style="width: 14%">ต้นทุน/ชิ้น</th>
                                                <th class="num" style="width: 14%">ราคาขายตั้งต้น</th>
                                                <th style="width: 8%"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="itemsBody"></tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="card-footer d-flex justify-content-end gap-2">
                                <a class="btn btn-outline-secondary" href="<?= BACKEND_URL ?>/pages/stock/addStock.php">ไปหน้าสินค้าและสต๊อก</a>
                                <button type="submit" class="btn btn-success">ยืนยันการรับของเข้าสต๊อก</button>
                            </div>
                        </div>
                    </form>

                    <template id="rowTemplate">
                        <tr>
                            <td>
                                <select class="form-select" name="product_id[]" onchange="onProductChange(this.closest('tr'))" required>
                                    <option value="" disabled selected>-- เลือกสินค้า --</option>
                                    <?php foreach ($products as $p): ?>
                                        <option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                            <td>
                                <select class="form-select" name="variant_id[]">
                                    <option value="0" selected>(เลือกสินค้าเพื่อแสดงตัวเลือก)</option>
                                </select>
                            </td>
                            <td class="num">
                                <input type="number" min="1" step="1" name="qty[]" class="form-control num" placeholder="0" required>
                            </td>
                            <td class="num">
                                <input type="number" min="0" step="0.01" name="cost_price[]" class="form-control num" placeholder="0.00" required>
                            </td>
                            <td class="num">
                                <input type="number" min="0" step="0.01" name="sell_price[]" class="form-control num" placeholder="(ไม่บังคับ)">
                            </td>
                            <td class="text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeRow(this)">ลบ</button>
                            </td>
                        </tr>
                    </template>

                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('formCreateProduct').addEventListener('submit', async (e) => {
            e.preventDefault();

            const form = e.target;
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.disabled = true;

            try {
                const fd = new FormData(form);

                const res = await fetch('<?= BACKEND_URL ?>/api/stock/create_product_inline.php', {
                    method: 'POST',
                    body: fd
                });

                const data = await res.json();
                if (!data.ok) {
                    alert('สร้างสินค้าไม่สำเร็จ: ' + (data.error || 'unknown'));
                    return;
                }

                const pid = Number(data.product.id);
                const pname = String(data.product.name || 'สินค้าใหม่');
                const vid = Number(data.variant.id);

                addProductOptionToAllSelects(pid, pname);

                if (!VARIANTS_MAP[pid]) VARIANTS_MAP[pid] = [];
                VARIANTS_MAP[pid] = [{
                    id: vid,
                    name: data.variant.variant_name || 'มาตรฐาน'
                }];

                const tbody = document.getElementById('itemsBody');
                const row = ACTIVE_ROW || (tbody.querySelector('tr:last-child') || tbody.querySelector('tr'));
                if (row) setRowProductVariant(row, pid, vid);

                form.reset();
                const modalEl = document.getElementById('modalCreateProduct');
                bootstrap.Modal.getOrCreateInstance(modalEl).hide();

                alert('สร้างสินค้าเรียบร้อย และเลือกให้ในรายการล่าสุดแล้ว');
            } finally {
                submitBtn.disabled = false;
            }
        });
    </script>

</body>

</html>