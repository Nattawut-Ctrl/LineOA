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

// ---------------- ดึง Log จากฐานข้อมูล ----------------
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

/**
 * แปลง 1 log → array สำหรับแสดงใน section
 * ถ้าเป็น update แต่ไม่มี field สำคัญเปลี่ยน จะคืน null (ไม่ต้องแสดง)
 */
function buildHistoryEntry(array $log): ?array
{
    $action = $log['action'];
    $table  = $log['table_name'];
    $time   = date('H:i', strtotime($log['created_at']));
    $actor  = $log['user_id'] ? ('Admin#' . $log['user_id']) : 'System';

    $detail         = '';
    $productContext = '';

    $old = !empty($log['old_data']) ? json_decode($log['old_data'], true) : null;
    $new = !empty($log['new_data']) ? json_decode($log['new_data'], true) : null;

    if ($action === 'update' && (!is_array($old) || !is_array($new))) {
        return null;
    }

    // ---------------- context ชื่อสินค้า / ตัวเลือก ----------------
    if ($table === 'products') {
        $productName    = $log['product_name'] ?: ('สินค้าถูกลบแล้ว (ID:' . $log['record_id'] . ')');
        $productContext = "สินค้า: {$productName}";

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
        $productContext = "สินค้า: {$parentName} (ตัวเลือก: {$variantName})";

        if ($action === 'insert') {
            $detail = 'เพิ่มตัวเลือกสินค้าใหม่';
        } elseif ($action === 'delete') {
            $detail = 'ลบตัวเลือกสินค้า';
        } elseif ($action === 'update') {
            $detail = 'แก้ไขตัวเลือกสินค้า';
        }
    }

    // ---------------- diff เฉพาะกรณี update ----------------
    if ($action === 'update' && is_array($old) && is_array($new)) {

        // products: ไม่สนใจ price/stock
        $fieldsOfInterestProducts = [
            'name'        => 'ชื่อ',
            'category'    => 'หมวดหมู่',
            'unit'        => 'หน่วย',
            'description' => 'คำอธิบาย',
        ];

        // variants: สนใจ price/stock ด้วย
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

        // ถ้าเป็น update แต่ไม่มี field สำคัญเปลี่ยนเลย → ไม่ต้องแสดง
        if (empty($changes)) {
            return null;
        }

        $detail .= ' (' . implode(', ', $changes) . ')';
    }

    if ($detail === '') {
        $detail = "ดำเนินการ ({$action} บน {$table})";
    }

    return [
        'time'    => $time,
        'actor'   => $actor,
        'detail'  => $detail,
        'context' => $productContext,
        'action'  => $action,
        'table'   => $table,
    ];
}

// ---------------- จัดกลุ่มตามวันที่ + batch (เวลา+user) ----------------
$groupedByDate = [];
foreach ($logs as $log) {
    $date = date('Y-m-d', strtotime($log['created_at']));
    if (!isset($groupedByDate[$date])) {
        $groupedByDate[$date] = [];
    }
    $groupedByDate[$date][] = $log;
}

// ---------------- CSS เล็กน้อย ----------------
?>
<style>
    .history-date-title {
        font-size: 0.85rem;
        letter-spacing: .03em;
        text-transform: uppercase;
        color: #6c757d;
    }

    .history-batch-header {
        font-size: 0.8rem;
        font-weight: 600;
        color: #495057;
    }

    .history-batch-list li {
        font-size: 0.8rem;
        color: #6c757d;
    }

    .history-batch-separator hr {
        border-color: #dee2e6;
        margin: .5rem 0;
    }

    .history-batch-box {
        border: 1px solid #dee2e6;
        border-radius: .5rem;
        padding: .75rem 1rem;
        background: #ffffff;
        margin-bottom: 1rem;
        box-shadow: 0 1px 2px rgba(0, 0, 0, .05);
    }

    .history-date-title {
        margin-top: 1rem;
    }
</style>
<?php

// ---------------- แสดงผล HTML ----------------
if (empty($groupedByDate)) {
    echo '<div class="text-center py-4 text-muted">ไม่พบประวัติการแก้ไข/ลบ ในระบบ</div>';
} else {
    foreach ($groupedByDate as $date => $logsOfDate):
        $displayDate = $date;
        if ($date === date('Y-m-d')) {
            $displayDate = 'วันนี้';
        } elseif ($date === date('Y-m-d', strtotime('-1 day'))) {
            $displayDate = 'เมื่อวาน';
        }
?>
        <div class="mb-3">
            <div class="history-date-title mb-2">[<?= htmlspecialchars($displayDate) ?>]</div>

            <?php
            // แบ่งเป็น batch ตามเวลา(นาที) + user
            $batches = [];
            foreach ($logsOfDate as $log) {
                $entry = buildHistoryEntry($log);
                if ($entry === null) {
                    continue;
                }

                $timeKey  = $entry['time'];                       // 20:07
                $userKey  = $log['user_id'] ?? 'system';          // 1 หรือ system
                $batchKey = $timeKey . '|' . $userKey;

                if (!isset($batches[$batchKey])) {
                    $batches[$batchKey] = [
                        'time'    => $entry['time'],
                        'actor'   => $entry['actor'],
                        'items'   => [],
                    ];
                }
                $batches[$batchKey]['items'][] = $entry;
            }

            $firstBatch = true;
            foreach ($batches as $batch):
                if (!$firstBatch): ?>
                    <div class="history-batch-separator">
                        <hr>
                    </div>
                <?php endif;
                $firstBatch = false;
                ?>
                <div class="history-batch-box">
                    <div class="history-batch-header mb-1">
                        เวลา <?= htmlspecialchars($batch['time']) ?> — <?= htmlspecialchars($batch['actor']) ?>
                    </div>
                    <ul class="history-batch-list mb-1 ps-3">
                        <?php foreach ($batch['items'] as $item): ?>
                            <li>
                                <?= htmlspecialchars($item['detail']) ?>
                                <span class="text-muted">
                                    (<?= htmlspecialchars($item['context']) ?>)
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
<?php
    endforeach;
}
?>