<?php
require_once __DIR__ . '/auth.php';
requireLogin();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
$cur = defined('CURRENCY_LABEL') ? CURRENCY_LABEL : 'ج';

$db = getDB();

// pagination
$page     = max(1,(int)($_GET['page']??1));
$perPage  = 20;
$offset   = ($page-1)*$perPage;
$total_orders = (int)$db->query('SELECT COUNT(*) FROM orders')->fetchColumn();
$totalPages   = max(1,(int)ceil($total_orders/$perPage));

$orders = $db->prepare('SELECT * FROM orders ORDER BY created_at DESC LIMIT ? OFFSET ?');
$orders->execute([$perPage,$offset]);
$orders = $orders->fetchAll();

// fetch items for all orders on this page
$orderIds = array_column($orders,'id');
$items    = [];
if($orderIds){
    $in  = implode(',', array_fill(0,count($orderIds),'?'));
    $st  = $db->prepare("SELECT * FROM order_items WHERE order_id IN ($in)");
    $st->execute($orderIds);
    foreach($st->fetchAll() as $it) $items[$it['order_id']][] = $it;
}

// stats
$stats = $db->query('SELECT COUNT(*) as cnt, COALESCE(SUM(total),0) as revenue FROM orders')->fetch();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>سجل المبيعات - ترافيك</title>
  <style>
    :root{
      --primary:#2563eb;--primary-dark:#1d4ed8;
      --success:#16a34a;--danger:#dc2626;
      --bg:#f1f5f9;--card:#fff;--border:#e2e8f0;
      --text:#0f172a;--muted:#64748b;
    }
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',Tahoma,sans-serif;background:var(--bg);color:var(--text)}

    header{
      background:var(--primary);color:#fff;padding:12px 15px;
      display:flex;align-items:center;gap:10px;
      position:sticky;top:0;z-index:20;box-shadow:0 2px 8px rgba(0,0,0,.2);
    }
    header a{color:rgba(255,255,255,.8);text-decoration:none;font-size:1.3rem}
    header h1{font-size:1.05rem;flex:1}
    .hbtn{background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.3);padding:6px 12px;border-radius:8px;font-size:.82rem;font-weight:600;cursor:pointer;text-decoration:none}

    .container{max-width:900px;margin:0 auto;padding:16px 14px}

    /* stats */
    .stats{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:20px}
    .stat-card{
      flex:1;min-width:130px;background:var(--card);border:1px solid var(--border);
      border-radius:12px;padding:14px 16px;
    }
    .stat-label{font-size:.78rem;color:var(--muted);margin-bottom:4px}
    .stat-val{font-size:1.5rem;font-weight:900;color:var(--primary)}
    .stat-val.green{color:var(--success)}

    /* order cards */
    .order-card{
      background:var(--card);border:1px solid var(--border);border-radius:12px;
      margin-bottom:12px;overflow:hidden;
    }
    .order-head{
      display:flex;align-items:center;gap:10px;padding:12px 14px;
      cursor:pointer;user-select:none;
    }
    .order-head:hover{background:#f8fafc}
    .order-id{font-size:.8rem;color:var(--muted);font-family:monospace;white-space:nowrap}
    .order-date{flex:1;font-size:.85rem;color:var(--muted)}
    .order-total{font-size:1.05rem;font-weight:800;color:var(--success);white-space:nowrap}
    .order-items-count{font-size:.78rem;color:var(--muted);background:#f1f5f9;padding:2px 8px;border-radius:10px}
    .arrow{color:var(--muted);transition:.2s;font-size:1rem}
    .arrow.open{transform:rotate(180deg)}
    .btn-del{background:none;border:1px solid #fca5a5;color:var(--danger);border-radius:6px;padding:4px 10px;font-size:.78rem;cursor:pointer}
    .btn-del:hover{background:#fee2e2}

    .order-body{display:none;border-top:1px solid var(--border);padding:10px 14px}
    .order-body.open{display:block}
    .oi{display:flex;justify-content:space-between;padding:5px 0;font-size:.88rem;border-bottom:1px solid #f8fafc}
    .oi:last-child{border-bottom:none}
    .oi-name{flex:1}
    .oi-qty{color:var(--muted);padding:0 10px}
    .oi-price{color:var(--muted);font-size:.8rem;padding:0 6px}
    .oi-total{font-weight:700}
    .order-notes{font-size:.82rem;color:var(--muted);margin-top:8px;padding-top:8px;border-top:1px dashed var(--border)}

    /* pagination */
    .pagination{display:flex;gap:6px;justify-content:center;margin-top:20px;flex-wrap:wrap}
    .plink{
      padding:7px 14px;border-radius:8px;text-decoration:none;font-size:.88rem;font-weight:600;
      background:var(--card);border:1px solid var(--border);color:var(--text);
    }
    .plink.active{background:var(--primary);color:#fff;border-color:var(--primary)}
    .plink:hover:not(.active){background:#f1f5f9}

    .empty{text-align:center;padding:50px;color:var(--muted)}
  </style>
</head>
<body>

<header>
  <a href="index.php">←</a>
  <h1>📋 سجل المبيعات</h1>
  <a href="pos.php" class="hbtn">🏪 POS</a>
</header>

<div class="container">

  <!-- Stats -->
  <div class="stats">
    <div class="stat-card">
      <div class="stat-label">إجمالي الفواتير</div>
      <div class="stat-val"><?= number_format($stats['cnt']) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">إجمالي المبيعات</div>
      <div class="stat-val green"><?= number_format($stats['revenue'],2) ?> <?= htmlspecialchars($cur) ?></div>
    </div>
    <div class="stat-card">
      <div class="stat-label">الصفحة</div>
      <div class="stat-val"><?= $page ?> / <?= $totalPages ?></div>
    </div>
  </div>

  <!-- Orders -->
  <?php if($orders): ?>
  <?php foreach($orders as $o):
    $oItems = $items[$o['id']] ?? [];
    $itemCount = array_sum(array_column($oItems,'qty'));
  ?>
  <div class="order-card">
    <div class="order-head" onclick="toggle(<?= $o['id'] ?>)">
      <span class="order-id">#<?= $o['id'] ?></span>
      <span class="order-date">🕒 <?= $o['created_at'] ?></span>
      <span class="order-items-count"><?= $itemCount ?> قطعة</span>
      <span class="order-total"><?= number_format($o['total'],2) ?> <?= htmlspecialchars($cur) ?></span>
      <span class="arrow" id="arr-<?= $o['id'] ?>">▼</span>
      <button class="btn-del" onclick="event.stopPropagation();delOrder(<?= $o['id'] ?>)">حذف</button>
    </div>
    <div class="order-body" id="body-<?= $o['id'] ?>">
      <?php foreach($oItems as $it): ?>
      <div class="oi">
        <span class="oi-name"><?= htmlspecialchars($it['name']) ?></span>
        <span class="oi-qty">× <?= $it['qty'] ?></span>
        <span class="oi-price"><?= number_format($it['price'],2) ?> <?= htmlspecialchars($cur) ?></span>
        <span class="oi-total"><?= number_format($it['price']*$it['qty'],2) ?> <?= htmlspecialchars($cur) ?></span>
      </div>
      <?php endforeach ?>
      <?php if($o['notes']): ?>
      <div class="order-notes">📝 <?= htmlspecialchars($o['notes']) ?></div>
      <?php endif ?>
    </div>
  </div>
  <?php endforeach ?>

  <!-- Pagination -->
  <?php if($totalPages>1): ?>
  <div class="pagination">
    <?php if($page>1): ?><a href="?page=<?=$page-1?>" class="plink">السابق</a><?php endif ?>
    <?php for($p=max(1,$page-2);$p<=min($totalPages,$page+2);$p++): ?>
    <a href="?page=<?=$p?>" class="plink <?=$p===$page?'active':''?>"><?=$p?></a>
    <?php endfor ?>
    <?php if($page<$totalPages): ?><a href="?page=<?=$page+1?>" class="plink">التالي</a><?php endif ?>
  </div>
  <?php endif ?>

  <?php else: ?>
  <div class="empty">لا توجد مبيعات بعد — ابدأ من <a href="pos.php" style="color:var(--primary)">نقطة البيع</a></div>
  <?php endif ?>

</div>

<script>
function toggle(id){
  const body=document.getElementById('body-'+id);
  const arr=document.getElementById('arr-'+id);
  const open=body.classList.toggle('open');
  arr.classList.toggle('open',open);
}
async function delOrder(id){
  if(!confirm('حذف الفاتورة #'+id+'؟')) return;
  const fd=new FormData(); fd.append('action','delete_order'); fd.append('id',id);
  await fetch('api.php',{method:'POST',body:fd});
  document.querySelector(`[onclick="toggle(${id})"]`)?.closest('.order-card')?.remove();
}
if(sessionStorage.getItem('fs')==='1') document.documentElement.requestFullscreen().catch(()=>{});
</script>
</body>
</html>
