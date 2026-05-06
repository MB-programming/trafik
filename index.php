<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

$query   = trim($_GET['q']   ?? '');
$catFilter = $_GET['cat'] ?? '';
if (!in_array($catFilter, CATEGORIES)) $catFilter = '';

$db     = getDB();
$params = [];
$sql    = 'SELECT * FROM products WHERE 1=1';
if ($query) {
    $sql .= ' AND (name LIKE ? OR barcode LIKE ?)';
    $params[] = "%$query%";
    $params[] = "%$query%";
}
if ($catFilter) {
    $sql .= ' AND category = ?';
    $params[] = $catFilter;
}
$sql .= ' ORDER BY name';

$st = $db->prepare($sql);
$st->execute($params);
$products = $st->fetchAll();

// counts per category
$counts = [];
foreach (CATEGORIES as $c) {
    $counts[$c] = (int)$db->query("SELECT COUNT(*) FROM products WHERE category='$c'")->fetchColumn();
}
$total = (int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn();

$catIcon  = ['غذائية'=>'🥫','سجاير'=>'🚬','مشروبات'=>'🧃','منوعات'=>'📦'];
$catClass = ['غذائية'=>'food','سجاير'=>'cig','مشروبات'=>'drink','منوعات'=>'misc'];
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>ترافيك — المنتجات</title>
  <style>
    :root{
      --primary:#2563eb;--primary-dark:#1d4ed8;
      --danger:#dc2626;--success:#16a34a;
      --bg:#f1f5f9;--card:#fff;--border:#e2e8f0;
      --text:#0f172a;--muted:#64748b;
    }
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',Tahoma,sans-serif;background:var(--bg);color:var(--text)}

    header{
      background:var(--primary);color:#fff;
      padding:13px 16px;
      display:flex;justify-content:space-between;align-items:center;
      position:sticky;top:0;z-index:20;
      box-shadow:0 2px 8px rgba(0,0,0,.2);
      gap:10px;flex-wrap:wrap;
    }
    header h1{font-size:1.1rem}
    .hbtns{display:flex;gap:8px}
    .hbtn{
      background:rgba(255,255,255,.18);color:#fff;
      border:1px solid rgba(255,255,255,.35);
      padding:7px 13px;border-radius:8px;
      font-weight:600;font-size:.85rem;
      cursor:pointer;text-decoration:none;white-space:nowrap;
    }
    .hbtn:hover{background:rgba(255,255,255,.3)}
    .hbtn.hi{background:#fff;color:var(--primary);border-color:#fff}
    .hbtn.hi:hover{background:#dbeafe}

    .container{max-width:960px;margin:0 auto;padding:16px 14px}

    /* add form */
    .card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:16px;margin-bottom:18px}
    .card h2{font-size:.92rem;color:var(--muted);margin-bottom:11px}
    .form-row{display:flex;gap:8px;flex-wrap:wrap}
    .form-row input,.form-row select{
      flex:1;min-width:110px;
      padding:9px 11px;border:1px solid var(--border);
      border-radius:8px;font-size:.95rem;background:#fff;color:var(--text);
    }
    .form-row input:focus,.form-row select:focus{outline:2px solid var(--primary);border-color:transparent}

    /* category tabs */
    .cat-tabs{display:flex;gap:7px;flex-wrap:wrap;margin-bottom:16px}
    .cat-tab{
      padding:6px 14px;border-radius:20px;font-size:.85rem;font-weight:600;
      text-decoration:none;border:2px solid transparent;transition:.15s;
      display:flex;align-items:center;gap:4px;
    }
    .count{background:rgba(0,0,0,.12);border-radius:10px;padding:1px 7px;font-size:.75rem}
    .cat-tab.all{background:#e2e8f0;color:var(--text)}
    .cat-tab.all.active{background:var(--text);color:#fff}
    .cat-tab.food{background:#dcfce7;color:#15803d}
    .cat-tab.food.active{background:#16a34a;color:#fff}
    .cat-tab.cig{background:#fef3c7;color:#b45309}
    .cat-tab.cig.active{background:#b45309;color:#fff}
    .cat-tab.drink{background:#dbeafe;color:#1d4ed8}
    .cat-tab.drink.active{background:#2563eb;color:#fff}
    .cat-tab.misc{background:#ede9fe;color:#6d28d9}
    .cat-tab.misc.active{background:#7c3aed;color:#fff}
    .cat-tab:hover{filter:brightness(.92)}

    /* toolbar */
    .toolbar{display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap}
    .toolbar input{flex:1;min-width:160px;padding:9px 13px;border:1px solid var(--border);border-radius:8px;font-size:.95rem}
    .btn{padding:9px 15px;border:none;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer;white-space:nowrap}
    .btn-p{background:var(--primary);color:#fff}
    .btn-p:hover{background:var(--primary-dark)}
    .btn-c{background:var(--border);color:var(--text);text-decoration:none;display:inline-flex;align-items:center}
    .btn-d{background:var(--danger);color:#fff;font-size:.8rem;padding:5px 10px}
    .btn-e{background:#f59e0b;color:#fff;font-size:.8rem;padding:5px 10px}

    /* table */
    .table-wrap{overflow-x:auto;border-radius:12px;box-shadow:0 1px 4px rgba(0,0,0,.06)}
    table{width:100%;border-collapse:collapse;background:var(--card)}
    thead{background:var(--primary);color:#fff}
    th,td{padding:10px 12px;text-align:right}
    th{font-size:.85rem;font-weight:600}
    tbody tr:nth-child(even){background:#f8fafc}
    tbody tr:hover{background:#eff6ff}
    td.price{font-weight:700;color:var(--success)}
    td.bc{font-family:monospace;color:var(--muted);font-size:.82rem}
    .acts{display:flex;gap:5px;justify-content:flex-end}

    .badge{display:inline-block;padding:2px 10px;border-radius:12px;font-size:.75rem;font-weight:700}
    .badge-غذائية{background:#dcfce7;color:#15803d}
    .badge-سجاير{background:#fef3c7;color:#b45309}
    .badge-مشروبات{background:#dbeafe;color:#1d4ed8}
    .badge-منوعات{background:#ede9fe;color:#6d28d9}

    .empty{text-align:center;padding:40px;color:var(--muted)}

    /* modal */
    .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:100;align-items:center;justify-content:center}
    .overlay.open{display:flex}
    .modal{background:var(--card);border-radius:14px;padding:20px;width:min(430px,95vw);box-shadow:0 8px 30px rgba(0,0,0,.2)}
    .modal h2{margin-bottom:13px;font-size:1rem}
    .modal .form-row{flex-direction:column}
    .modal-acts{display:flex;gap:9px;margin-top:13px;justify-content:flex-end}

    .toast{
      display:none;position:fixed;bottom:20px;left:50%;transform:translateX(-50%);
      background:#1e293b;color:#f1f5f9;padding:10px 22px;
      border-radius:10px;font-size:.9rem;z-index:200;
      box-shadow:0 4px 16px rgba(0,0,0,.3);
    }
  </style>
</head>
<body>

<header>
  <h1>🛒 ترافيك</h1>
  <div class="hbtns">
    <a href="scanner.php" class="hbtn">📷 باركود</a>
    <a href="fastscan.php" class="hbtn hi">⚡ Fast Scan</a>
  </div>
</header>

<div class="container">

  <!-- Add form -->
  <div class="card">
    <h2>إضافة منتج جديد</h2>
    <form class="form-row" id="addForm" onsubmit="addProduct(event)">
      <input type="text"   name="barcode"  placeholder="باركود"         required/>
      <input type="text"   name="name"     placeholder="اسم المنتج"     required/>
      <input type="number" name="price"    placeholder="السعر (ج)"      step="0.01" min="0" required/>
      <select name="category">
        <?php foreach(CATEGORIES as $c): ?>
        <option value="<?= $c ?>"><?= $c ?></option>
        <?php endforeach ?>
      </select>
      <button class="btn btn-p" type="submit">+ إضافة</button>
    </form>
  </div>

  <!-- Category tabs -->
  <div class="cat-tabs">
    <?php
      $base = '?';
      if ($query) $base .= 'q=' . urlencode($query) . '&';
    ?>
    <a href="<?= $base ?>cat=" class="cat-tab all <?= !$catFilter?'active':'' ?>">
      الكل <span class="count"><?= $total ?></span>
    </a>
    <?php foreach(CATEGORIES as $c): ?>
    <a href="<?= $base ?>cat=<?= urlencode($c) ?>"
       class="cat-tab <?= $catClass[$c] ?> <?= $catFilter===$c?'active':'' ?>">
      <?= $catIcon[$c] ?> <?= $c ?> <span class="count"><?= $counts[$c] ?></span>
    </a>
    <?php endforeach ?>
  </div>

  <!-- Search -->
  <form class="toolbar" method="get">
    <?php if($catFilter): ?><input type="hidden" name="cat" value="<?= htmlspecialchars($catFilter) ?>"/><?php endif ?>
    <input type="text" name="q" placeholder="بحث باسم أو باركود..." value="<?= htmlspecialchars($query) ?>"/>
    <button class="btn btn-p" type="submit">بحث</button>
    <?php if($query): ?>
    <a href="?<?= $catFilter?'cat='.urlencode($catFilter):'' ?>" class="btn btn-c">✕</a>
    <?php endif ?>
  </form>

  <!-- Table -->
  <?php if($products): ?>
  <div class="table-wrap">
    <table id="prodTable">
      <thead>
        <tr><th>#</th><th>باركود</th><th>اسم المنتج</th><th>القسم</th><th>السعر</th><th>إجراءات</th></tr>
      </thead>
      <tbody>
        <?php foreach($products as $i=>$p): ?>
        <tr id="row-<?= $p['id'] ?>">
          <td><?= $i+1 ?></td>
          <td class="bc"><?= htmlspecialchars($p['barcode']) ?></td>
          <td><?= htmlspecialchars($p['name']) ?></td>
          <td><span class="badge badge-<?= $p['category'] ?>"><?= $p['category'] ?></span></td>
          <td class="price"><?= number_format($p['price'],2) ?> ج</td>
          <td>
            <div class="acts">
              <button class="btn btn-e"
                onclick="openEdit(<?= $p['id'] ?>,'<?= addslashes($p['barcode']) ?>',<?= json_encode($p['name'],JSON_UNESCAPED_UNICODE) ?>,<?= $p['price'] ?>,'<?= $p['category'] ?>')">
                تعديل
              </button>
              <button class="btn btn-d" onclick="delProduct(<?= $p['id'] ?>,<?= json_encode($p['name'],JSON_UNESCAPED_UNICODE) ?>)">حذف</button>
            </div>
          </td>
        </tr>
        <?php endforeach ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
  <div class="empty">
    <?php if($query): ?>لا توجد نتائج لـ "<?= htmlspecialchars($query) ?>"
    <?php elseif($catFilter): ?>لا توجد منتجات في قسم <?= $catFilter ?> بعد
    <?php else: ?>لا توجد منتجات — أضف من الأعلى<?php endif ?>
  </div>
  <?php endif ?>

</div>

<!-- Edit modal -->
<div class="overlay" id="editModal">
  <div class="modal">
    <h2>تعديل المنتج</h2>
    <div class="form-row" id="editFields">
      <input type="text"   id="eBarcode"  placeholder="باركود"/>
      <input type="text"   id="eName"     placeholder="اسم المنتج"/>
      <input type="number" id="ePrice"    placeholder="السعر" step="0.01" min="0"/>
      <select id="eCat">
        <?php foreach(CATEGORIES as $c): ?>
        <option value="<?= $c ?>"><?= $c ?></option>
        <?php endforeach ?>
      </select>
    </div>
    <div class="modal-acts">
      <button class="btn btn-c" onclick="closeEdit()">إلغاء</button>
      <button class="btn btn-p" onclick="saveEdit()">حفظ</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
let editId = null;

/* ── Toast ── */
function showToast(msg, ok=true) {
  const t = document.getElementById('toast');
  t.textContent = msg;
  t.style.background = ok ? '#166534' : '#991b1b';
  t.style.display = 'block';
  setTimeout(() => t.style.display='none', 2500);
}

/* ── Add product ── */
async function addProduct(e) {
  e.preventDefault();
  const fd = new FormData(e.target);
  fd.append('action','add');
  const res  = await fetch('api.php', {method:'POST',body:fd});
  const data = await res.json();
  if (data.ok) { showToast('تمت الإضافة ✓'); setTimeout(()=>location.reload(),600); }
  else showToast('خطأ: ' + (data.error||''), false);
}

/* ── Delete ── */
async function delProduct(id, name) {
  if (!confirm('حذف ' + name + '؟')) return;
  const fd = new FormData();
  fd.append('action','delete'); fd.append('id', id);
  await fetch('api.php', {method:'POST',body:fd});
  document.getElementById('row-'+id)?.remove();
  showToast('تم الحذف');
}

/* ── Edit modal ── */
function openEdit(id,barcode,name,price,cat) {
  editId = id;
  document.getElementById('eBarcode').value = barcode;
  document.getElementById('eName').value    = name;
  document.getElementById('ePrice').value   = price;
  document.getElementById('eCat').value     = cat;
  document.getElementById('editModal').classList.add('open');
}
function closeEdit() { document.getElementById('editModal').classList.remove('open'); }
async function saveEdit() {
  const fd = new FormData();
  fd.append('action','edit');
  fd.append('id',      editId);
  fd.append('barcode', document.getElementById('eBarcode').value);
  fd.append('name',    document.getElementById('eName').value);
  fd.append('price',   document.getElementById('ePrice').value);
  fd.append('category',document.getElementById('eCat').value);
  const res  = await fetch('api.php',{method:'POST',body:fd});
  const data = await res.json();
  if (data.ok) { closeEdit(); showToast('تم التعديل ✓'); setTimeout(()=>location.reload(),600); }
  else showToast('خطأ في التعديل', false);
}
document.getElementById('editModal').addEventListener('click',function(e){if(e.target===this)closeEdit()});
</script>
</body>
</html>
