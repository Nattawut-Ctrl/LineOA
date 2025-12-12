<?php
// ajax_load_all_product_history.php
session_start();

date_default_timezone_set('Asia/Bangkok');

require_once __DIR__ . '/../../config.php';
require_once UTILS_PATH . '/db_with_log.php';
require_once UTILS_PATH . '/admin_guard.php';

require_admin();
$conn = connectDBWithLog();

header('Content-Type: text/html; charset=utf-8');

$sql = "
    SELECT 
        l.action, 
        l.table_name, 
        l.record_id, 
        l.query_text, 
        l.old_data,
        l.new_data,
        l.created_at,
        l.user_id,
        CASE WHEN l.user_id IS NULL THEN 'System' ELSE CONCAT('Admin#', l.user_id) END AS actor_name,
        
        p.name AS product_name, 
        pv.variant_name AS variant_name, 
        p_variant.name AS parent_product_name 
        
    FROM logs l
    LEFT JOIN products p 
        ON l.table_name = 'products' AND l.record_id = p.id
    LEFT JOIN product_variants pv 
        ON l.table_name = 'product_variants' AND l.record_id = pv.id
    LEFT JOIN products p_variant 
        ON pv.product_id = p_variant.id

    WHERE 
        l.status = 'success' AND
        l.action IN ('insert', 'update', 'delete') AND
        (l.table_name = 'products' OR l.table_name = 'product_variants') AND
        l.record_id > 0
    ORDER BY l.created_at DESC
    LIMIT 100
";

$res = db_query($conn, $sql);

$logs = [];
if ($res) {
    while ($row = $res->fetch_assoc()) {
        $logs[] = $row;
    }
}

function getDisplayAction(array $log): string
{
    $action = $log['action'];
    $table  = $log['table_name'];
    $time   = date('H:i', strtotime($log['created_at']));
    $actor  = $log['user_id'] ? 'Admin#' . $log['user_id'] : 'System';

    $detail         = '';
    $productContext = '';

    // decode JSON จาก logs.old_data / logs.new_data
    $old = !empty($log['old_data']) ? json_decode($log['old_data'], true) : null;
    $new = !empty($log['new_data']) ? json_decode($log['new_data'], true) : null;

    // ---------------- context ชื่อสินค้า / ตัวเลือก ----------------
    if ($table === 'products') {
        $productName    = $log['product_name'] ?: ('สินค้าถูกลบแล้ว (ID:' . $log['record_id'] . ')');
        $productContext = "สินค้า: **{$productName}**";

        if ($action === 'insert') {
            $detail = 'เพิ่มสินค้าใหม่';
        } elseif ($action === 'delete') {
            $detail = 'ลบสินค้า (ทั้งรายการ)';
        } elseif ($action === 'update') {
            $detail = 'แก้ไขข้อมูลหลัก';
        }
    } elseif ($table === 'product_variants') {
        $parentName  = $log['parent_product_name'] ?: 'ไม่ทราบสินค้าหลัก';
        $variantName = $log['variant_name'] ?: ('ตัวเลือกถูกลบแล้ว (ID:' . $log['record_id'] . ')');
        $productContext = "สินค้า: **{$parentName}** (ตัวเลือก: **{$variantName}**)";

        if ($action === 'insert') {
            $detail = 'เพิ่มตัวเลือกสินค้าใหม่';
        } elseif ($action === 'delete') {
            $detail = 'ลบตัวเลือกสินค้า';
        } elseif ($action === 'update') {
            $detail = 'แก้ไขตัวเลือกสินค้า';
        }
    }

    // ---------------- สร้าง diff old → new เฉพาะตอน update ----------------
    if ($action === 'update' && is_array($old) && is_array($new)) {

        // products: ไม่เอา price/stock แล้ว
        $fieldsOfInterestProducts = [
            'name'        => 'ชื่อ',
            'category'    => 'หมวดหมู่',
            'unit'        => 'หน่วย',
            'description' => 'คำอธิบาย',
        ];

        // variants: ยังเอา price/stock ได้
        $fieldsOfInterestVariants = [
            'variant_name'   => 'ชื่อตัวเลือก',
            'sku'            => 'SKU',
            'price'          => 'ราคา',
            'stock'          => 'สต็อก',
            'reserved_stock' => 'สต็อกจอง',
        ];

        $fields  = ($table === 'products') ? $fieldsOfInterestProducts : $fieldsOfInterestVariants;
        $changes = [];

        foreach ($fields as $field => $label) {
            $oldVal = $old[$field] ?? null;
            $newVal = $new[$field] ?? null;

            // ถ้าไม่เปลี่ยนค่า ข้าม
            if ($oldVal == $newVal) {
                continue;
            }

            if ($field === 'price') {
                $oldFmt = ($oldVal === null || $oldVal === '') ? '-' : number_format((float)$oldVal, 2);
                $newFmt = ($newVal === null || $newVal === '') ? '-' : number_format((float)$newVal, 2);
            } elseif (in_array($field, ['stock', 'reserved_stock'], true)) {
                $oldFmt = ($oldVal === null || $oldVal === '') ? '-' : (string)$oldVal;
                $newFmt = ($newVal === null || $newVal === '') ? '-' : (string)$newVal;
            } else {
                $oldFmt = ($oldVal === null || $oldVal === '') ? '-' : (string)$oldVal;
                $newFmt = ($newVal === null || $newVal === '') ? '-' : (string)$newVal;
            }

            $changes[] = "{$label}: {$oldFmt} → {$newFmt}";
        }

        // ถ้าเป็น UPDATE แต่ไม่มี field ที่เราสนใจเปลี่ยนเลย → ไม่แสดง log แถวนี้
        if (empty($changes)) {
            return '';
        }

        // มี changes → ต่อท้าย detail
        $detail .= ' (' . implode(', ', $changes) . ')';
    }

    // fallback กรณี insert/delete
    if (empty($detail)) {
        $detail = "ดำเนินการ ({$action} บน {$table})";
    }

    return "• {$time} **{$actor}** {$detail}, {$productContext}";
}

// จัดกลุ่มตามวันที่
$groupedLogs = [];
foreach ($logs as $log) {
    $date = date('Y-m-d', strtotime($log['created_at']));
    if (!isset($groupedLogs[$date])) {
        $groupedLogs[$date] = [];
    }
    $groupedLogs[$date][] = $log;
}

// แสดงผล HTML
if (empty($groupedLogs)) {
    echo '<div class="text-center py-4 text-muted">ไม่พบประวัติการแก้ไข/ลบ ในระบบ</div>';
} else {
    foreach ($groupedLogs as $date => $logsOfDate):
        // แปลงวันที่ให้อ่านง่าย
        $displayDate = $date;
        if ($date === date('Y-m-d')) {
            $displayDate = 'วันนี้';
        } elseif ($date === date('Y-m-d', strtotime('-1 day'))) {
            $displayDate = 'เมื่อวาน';
        }
?>
        <div class="mb-3">
            <h6 class="fw-semibold mb-2">[<?= htmlspecialchars($displayDate) ?>]</h6>
            <ul class="list-unstyled ps-3">
                <?php
                $prevBatchKey = null;

                foreach ($logsOfDate as $log):
                    $text = getDisplayAction($log);
                    if ($text === '') {
                        continue; // ข้าม log ที่เราไม่ต้องการแสดง
                    }

                    // batch key = เวลาแบบนาที + user เดียวกัน ให้ถือว่าชุดเดียวกัน
                    $timeKey  = date('H:i', strtotime($log['created_at']));
                    $userKey  = $log['user_id'] ?? 'system';
                    $batchKey = $timeKey . '|' . $userKey;

                    // ถ้าเปลี่ยน batch → แสดงเส้นคั่น
                    if ($prevBatchKey !== null && $batchKey !== $prevBatchKey): ?>
                        <li>
                            <hr class="my-2">
                        </li>
                    <?php
                    endif;

                    $prevBatchKey = $batchKey;
                    ?>
                    <li class="mb-1 small text-muted">
                        <?= $text ?>
                    </li>
                <?php
                endforeach;
                ?>
            </ul>
        </div>
<?php
    endforeach;
}
?>