<?php
require_once __DIR__.'/auth.php';
requireLogin();
require_once __DIR__.'/config.php';
require_once __DIR__.'/db.php';

$format = $_GET['format'] ?? 'pdf';   // 'pdf' | 'csv'
$from   = $_GET['from']   ?? '';
$to     = $_GET['to']     ?? '';
$cur    = defined('CURRENCY_LABEL') ? CURRENCY_LABEL : '€';

$db  = getDB();
$sql = 'SELECT * FROM orders WHERE 1=1';
$p   = [];
if($from){ $sql.=' AND DATE(created_at)>=?'; $p[]=$from; }
if($to)  { $sql.=' AND DATE(created_at)<=?'; $p[]=$to;   }
$sql.=' ORDER BY created_at DESC';
$st=$db->prepare($sql); $st->execute($p);
$orders=$st->fetchAll();

$orderIds=array_column($orders,'id');
$items=[];
if($orderIds){
    $in=implode(',',array_fill(0,count($orderIds),'?'));
    $st=$db->prepare("SELECT * FROM order_items WHERE order_id IN ($in) ORDER BY order_id");
    $st->execute($orderIds);
    foreach($st->fetchAll() as $it) $items[$it['order_id']][]=$it;
}

$totalRevenue=array_sum(array_column($orders,'total'));
$totalQty=0;
foreach($items as $its) foreach($its as $it) $totalQty+=$it['qty'];

$dateLabel = ($from||$to)
    ? trim(($from?'من '.$from:'').' '.($to?'إلى '.$to:''))
    : 'كل المبيعات';

/* ══════════════════════════════════════════════════════ CSV / Excel ══ */
if($format==='csv'){
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="sales-'.date('Y-m-d').'.csv"');
    echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel
    echo "رقم الفاتورة,التاريخ,المنتج,الكمية,السعر,الإجمالي الجزئي,إجمالي الفاتورة\n";
    foreach($orders as $o){
        $oItems=$items[$o['id']]??[];
        if(!$oItems){
            echo $o['id'].','.$o['created_at'].',,,,,'.$o['total']."\n";
        } else {
            foreach($oItems as $i=>$it){
                $id   = $i===0 ? $o['id']         : '';
                $date = $i===0 ? $o['created_at']  : '';
                $tot  = $i===0 ? $o['total']       : '';
                $sub  = round($it['price']*$it['qty'],2);
                echo implode(',',[$id,$date,'"'.$it['name'].'"',$it['qty'],$it['price'],$sub,$tot])."\n";
            }
        }
    }
    exit;
}

/* ══════════════════════════════════════════════════════ PDF (print HTML) ══ */
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8"/>
  <title>تقرير المبيعات</title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',Tahoma,Arial,sans-serif;color:#0f172a;font-size:13px;padding:20px}
    h1{font-size:1.3rem;margin-bottom:4px;display:flex;align-items:center;gap:8px}
    .meta{color:#64748b;font-size:.82rem;margin-bottom:16px;display:flex;gap:18px;flex-wrap:wrap}
    .summary{display:flex;gap:16px;margin-bottom:20px;flex-wrap:wrap}
    .s-card{border:1px solid #e2e8f0;border-radius:8px;padding:10px 16px;min-width:140px}
    .s-lbl{font-size:.72rem;color:#64748b;margin-bottom:3px}
    .s-val{font-size:1.2rem;font-weight:800;color:#16a34a}
    .s-val.blue{color:#2563eb}
    table{width:100%;border-collapse:collapse;margin-bottom:24px}
    thead{background:#1e3a5f;color:#fff}
    th{padding:8px 10px;font-size:.78rem;font-weight:600;text-align:right}
    td{padding:7px 10px;font-size:.82rem;border-bottom:1px solid #f1f5f9;text-align:right}
    tr:nth-child(even) td{background:#f8fafc}
    .order-group td{background:#eff6ff!important;font-weight:600;color:#1e40af}
    .total-row td{background:#f0fdf4!important;font-weight:800;color:#15803d;border-top:2px solid #16a34a}
    .no-print{margin-top:20px;display:flex;gap:10px;justify-content:center}
    .btn{padding:10px 24px;border:none;border-radius:8px;font-size:.9rem;font-weight:700;cursor:pointer}
    .btn-print{background:#2563eb;color:#fff}
    .btn-close{background:#f1f5f9;color:#0f172a;border:1px solid #e2e8f0}
    @media print{
      .no-print{display:none!important}
      body{padding:10px}
      a{text-decoration:none;color:inherit}
    }
  </style>
</head>
<body>

<h1>&#128202; تقرير المبيعات</h1>
<div class="meta">
  <span>&#128197; <?= htmlspecialchars($dateLabel) ?></span>
  <span>&#128336; طُبع: <?= date('Y-m-d H:i') ?></span>
</div>

<div class="summary">
  <div class="s-card">
    <div class="s-lbl">عدد الفواتير</div>
    <div class="s-val blue"><?= count($orders) ?></div>
  </div>
  <div class="s-card">
    <div class="s-lbl">إجمالي القطع</div>
    <div class="s-val blue"><?= number_format($totalQty) ?></div>
  </div>
  <div class="s-card">
    <div class="s-lbl">إجمالي الإيرادات</div>
    <div class="s-val"><?= number_format($totalRevenue,2) ?> <?= htmlspecialchars($cur) ?></div>
  </div>
</div>

<?php if($orders): ?>
<table>
  <thead>
    <tr>
      <th>#</th>
      <th>التاريخ</th>
      <th>المنتج</th>
      <th>الكمية</th>
      <th>السعر</th>
      <th>جزئي</th>
      <th>إجمالي الفاتورة</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach($orders as $o):
    $oItems=$items[$o['id']]??[];
    if(!$oItems):
  ?>
    <tr class="order-group">
      <td><?= $o['id'] ?></td>
      <td><?= $o['created_at'] ?></td>
      <td colspan="4" style="color:#64748b">— لا توجد تفاصيل —</td>
      <td><?= number_format($o['total'],2) ?> <?= htmlspecialchars($cur) ?></td>
    </tr>
  <?php else:
    foreach($oItems as $i=>$it):
      $sub=round($it['price']*$it['qty'],2);
  ?>
    <?php if($i===0): ?>
    <tr class="order-group">
      <td rowspan="<?= count($oItems) ?>"><?= $o['id'] ?></td>
      <td rowspan="<?= count($oItems) ?>"><?= $o['created_at'] ?></td>
      <td><?= htmlspecialchars($it['name']) ?></td>
      <td><?= $it['qty'] ?></td>
      <td><?= number_format($it['price'],2) ?></td>
      <td><?= number_format($sub,2) ?></td>
      <td rowspan="<?= count($oItems) ?>" style="font-weight:800;color:#15803d"><?= number_format($o['total'],2) ?> <?= htmlspecialchars($cur) ?></td>
    </tr>
    <?php else: ?>
    <tr>
      <td><?= htmlspecialchars($it['name']) ?></td>
      <td><?= $it['qty'] ?></td>
      <td><?= number_format($it['price'],2) ?></td>
      <td><?= number_format($sub,2) ?></td>
    </tr>
    <?php endif ?>
  <?php endforeach; endif ?>
  <?php endforeach ?>
  <tr class="total-row">
    <td colspan="5" style="text-align:left">الإجمالي الكلي</td>
    <td><?= number_format($totalQty) ?> قطعة</td>
    <td><?= number_format($totalRevenue,2) ?> <?= htmlspecialchars($cur) ?></td>
  </tr>
  </tbody>
</table>
<?php else: ?>
<p style="color:#64748b;text-align:center;padding:40px">لا توجد مبيعات في هذه الفترة</p>
<?php endif ?>

<div class="no-print">
  <button class="btn btn-print" onclick="window.print()">&#128438; طباعة / حفظ PDF</button>
  <button class="btn btn-close" onclick="window.close()">&#10005; إغلاق</button>
</div>

<script>
// auto-open print dialog after a moment
window.addEventListener('load',()=>setTimeout(()=>window.print(),400));
</script>
</body>
</html>
