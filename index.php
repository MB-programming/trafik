<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
requireLogin();

$db       = getDB();
$tab      = $_GET['tab'] ?? 'products';
$query    = trim($_GET['q'] ?? '');
$catFilter= $_GET['cat'] ?? '';

// categories list for filter + forms
$allCats  = $db->query('SELECT * FROM categories ORDER BY sort,id')->fetchAll();
$catNames = array_column($allCats,'name');
if ($catFilter && !in_array($catFilter,$catNames)) $catFilter='';

// products query
$params=[]; $sql='SELECT * FROM products WHERE 1=1';
if ($query) { $sql.=' AND (name LIKE ? OR barcode LIKE ?)'; $params[]="%$query%"; $params[]="%$query%"; }
if ($catFilter) { $sql.=' AND category=?'; $params[]=$catFilter; }
$sql.=' ORDER BY name';
$st=$db->prepare($sql); $st->execute($params);
$products=$st->fetchAll();

$counts=[];
foreach($allCats as $c) $counts[$c['name']]=(int)$db->query("SELECT COUNT(*) FROM products WHERE category='{$c['name']}'")->fetchColumn();
$total=(int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>لوحة التحكم - ترافيك</title>
  <style>
    :root{--primary:#2563eb;--primary-dark:#1d4ed8;--danger:#dc2626;--success:#16a34a;--bg:#f1f5f9;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b}
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',Tahoma,sans-serif;background:var(--bg);color:var(--text)}
    header{background:var(--primary);color:#fff;padding:12px 15px;display:flex;align-items:center;gap:10px;position:sticky;top:0;z-index:20;box-shadow:0 2px 8px rgba(0,0,0,.2);flex-wrap:wrap}
    header h1{font-size:1.05rem;flex:1}
    .hbtn{background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.3);padding:6px 11px;border-radius:8px;font-size:.8rem;font-weight:600;cursor:pointer;text-decoration:none;white-space:nowrap}
    .hbtn:hover{background:rgba(255,255,255,.28)}
    .hbtn.hi{background:#fff;color:var(--primary);border-color:#fff}
    .container{max-width:980px;margin:0 auto;padding:16px 13px}

    /* tabs */
    .tabs{display:flex;gap:0;background:var(--card);border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:18px}
    .tab-btn{flex:1;padding:11px;border:none;background:none;font-size:.9rem;font-weight:600;cursor:pointer;color:var(--muted);border-bottom:3px solid transparent;transition:.15s}
    .tab-btn.active{color:var(--primary);border-bottom-color:var(--primary);background:#eff6ff}

    .tab-pane{display:none} .tab-pane.active{display:block}

    /* card */
    .card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:16px;margin-bottom:16px}
    .card h2{font-size:.92rem;color:var(--muted);margin-bottom:11px;font-weight:600}

    /* form row */
    .form-row{display:flex;gap:7px;flex-wrap:wrap}
    .form-row input,.form-row select,.form-row textarea{flex:1;min-width:110px;padding:9px 11px;border:1px solid var(--border);border-radius:8px;font-size:.93rem;background:#fff;color:var(--text)}
    .form-row input:focus,.form-row select:focus{outline:2px solid var(--primary);border-color:transparent}
    .img-preview{width:54px;height:54px;border-radius:8px;object-fit:cover;border:1px solid var(--border);background:#f8fafc;display:none}

    /* buttons */
    .btn{padding:9px 14px;border:none;border-radius:8px;font-size:.88rem;font-weight:600;cursor:pointer;white-space:nowrap}
    .btn-p{background:var(--primary);color:#fff} .btn-p:hover{background:var(--primary-dark)}
    .btn-d{background:var(--danger);color:#fff;font-size:.78rem;padding:5px 10px}
    .btn-e{background:#f59e0b;color:#fff;font-size:.78rem;padding:5px 10px}
    .btn-c{background:var(--border);color:var(--text);text-decoration:none;display:inline-flex;align-items:center}

    /* category tabs filter */
    .cat-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px}
    .cat-tab{padding:5px 13px;border-radius:16px;font-size:.82rem;font-weight:600;text-decoration:none;background:#e2e8f0;color:var(--text);display:flex;align-items:center;gap:4px}
    .cat-tab.active{background:var(--primary);color:#fff}
    .cat-tab:hover:not(.active){filter:brightness(.92)}
    .count{background:rgba(0,0,0,.12);border-radius:10px;padding:1px 6px;font-size:.72rem}

    /* toolbar */
    .toolbar{display:flex;gap:7px;margin-bottom:14px;flex-wrap:wrap}
    .toolbar input{flex:1;min-width:160px;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:.93rem}

    /* table */
    .tbl-wrap{overflow-x:auto;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.05)}
    table{width:100%;border-collapse:collapse;background:var(--card)}
    thead{background:var(--primary);color:#fff}
    th,td{padding:9px 11px;text-align:right;font-size:.85rem}
    th{font-weight:600}
    tbody tr:nth-child(even){background:#f8fafc}
    tbody tr:hover{background:#eff6ff}
    td.price{font-weight:700;color:var(--success)}
    td.bc{font-family:monospace;color:var(--muted);font-size:.8rem}
    .acts{display:flex;gap:5px;justify-content:flex-end}

    .badge{display:inline-block;padding:2px 8px;border-radius:10px;font-size:.72rem;font-weight:700}
    .pimg{width:36px;height:36px;border-radius:6px;object-fit:cover;background:#f1f5f9;border:1px solid var(--border)}
    .no-img{width:36px;height:36px;border-radius:6px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:1.1rem}

    /* categories grid */
    .cats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:10px}
    .cat-card{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:13px;display:flex;align-items:center;gap:10px}
    .cat-icon{font-size:1.8rem;line-height:1}
    .cat-info{flex:1}
    .cat-name{font-weight:700;font-size:.95rem}
    .cat-count{font-size:.78rem;color:var(--muted)}
    .cat-acts{display:flex;gap:5px}

    /* modal */
    .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:100;align-items:center;justify-content:center}
    .overlay.open{display:flex}
    .modal{background:var(--card);border-radius:14px;padding:20px;width:min(440px,95vw);box-shadow:0 8px 30px rgba(0,0,0,.2);max-height:90vh;overflow-y:auto}
    .modal h2{margin-bottom:13px;font-size:1rem}
    .modal .form-row{flex-direction:column}
    .modal-acts{display:flex;gap:8px;margin-top:13px;justify-content:flex-end}

    .empty{text-align:center;padding:36px;color:var(--muted)}
    .toast{display:none;position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:#1e293b;color:#f1f5f9;padding:10px 20px;border-radius:10px;font-size:.88rem;z-index:200;box-shadow:0 4px 16px rgba(0,0,0,.3);white-space:nowrap}
  </style>
</head>
<body>
<header>
  <h1>⚙️ لوحة التحكم</h1>
  <a href="pos.php"     class="hbtn hi">🏪 POS</a>
  <a href="history.php" class="hbtn">📋 السجل</a>
  <a href="scanner.php" class="hbtn">📷 سكانر</a>
  <a href="logout.php"  class="hbtn">🚪 خروج</a>
</header>

<div class="container">
  <div class="tabs">
    <button class="tab-btn <?= $tab==='products'?'active':'' ?>" onclick="switchTab('products')">📦 المنتجات</button>
    <button class="tab-btn <?= $tab==='categories'?'active':'' ?>" onclick="switchTab('categories')">🏷️ الأقسام</button>
  </div>

  <!-- ══ PRODUCTS TAB ══ -->
  <div class="tab-pane <?= $tab==='products'?'active':'' ?>" id="tab-products">

    <!-- Add product -->
    <div class="card">
      <h2>إضافة منتج جديد</h2>
      <form id="addForm" onsubmit="addProduct(event)" enctype="multipart/form-data">
        <div class="form-row" style="margin-bottom:8px">
          <input type="text"   name="barcode"  placeholder="باركود" required/>
          <input type="text"   name="name"     placeholder="اسم المنتج" required/>
          <input type="number" name="price"    placeholder="السعر (ج)" step="0.01" min="0" required/>
          <select name="category">
            <?php foreach($allCats as $c): ?><option value="<?= htmlspecialchars($c['name']) ?>"><?= $c['icon'].' '.$c['name'] ?></option><?php endforeach ?>
          </select>
        </div>
        <div class="form-row" style="align-items:center">
          <label style="font-size:.82rem;color:var(--muted);cursor:pointer;display:flex;align-items:center;gap:6px">
            📷 صورة المنتج
            <input type="file" name="image" accept="image/*" style="display:none" onchange="previewImg(this,'addPreview')"/>
          </label>
          <img id="addPreview" class="img-preview"/>
          <button class="btn btn-p" type="submit" style="margin-right:auto">+ إضافة</button>
        </div>
      </form>
    </div>

    <!-- Filter tabs -->
    <div class="cat-tabs">
      <a href="?tab=products<?= $query?'&q='.urlencode($query):'' ?>" class="cat-tab <?= !$catFilter?'active':'' ?>">الكل <span class="count"><?= $total ?></span></a>
      <?php foreach($allCats as $c): ?>
      <a href="?tab=products&cat=<?= urlencode($c['name']).(($query)?'&q='.urlencode($query):'') ?>"
         class="cat-tab <?= $catFilter===$c['name']?'active':'' ?>"><?= $c['icon'].' '.$c['name'] ?> <span class="count"><?= $counts[$c['name']]??0 ?></span></a>
      <?php endforeach ?>
    </div>

    <!-- Search -->
    <form class="toolbar" method="get">
      <input type="hidden" name="tab" value="products"/>
      <?php if($catFilter): ?><input type="hidden" name="cat" value="<?= htmlspecialchars($catFilter) ?>"/><?php endif ?>
      <input type="text" name="q" placeholder="بحث بالاسم أو الباركود..." value="<?= htmlspecialchars($query) ?>"/>
      <button class="btn btn-p" type="submit">بحث</button>
      <?php if($query): ?><a href="?tab=products<?= $catFilter?'&cat='.urlencode($catFilter):'' ?>" class="btn btn-c">✕</a><?php endif ?>
    </form>

    <!-- Products table -->
    <?php if($products): ?>
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>صورة</th><th>باركود</th><th>اسم المنتج</th><th>القسم</th><th>السعر</th><th>إجراءات</th></tr></thead>
        <tbody>
          <?php foreach($products as $p): ?>
          <tr id="row-<?= $p['id'] ?>">
            <td><?php if($p['image_path']): ?><img class="pimg" src="<?= htmlspecialchars($p['image_path']) ?>"/><?php else: ?><div class="no-img">📦</div><?php endif ?></td>
            <td class="bc"><?= htmlspecialchars($p['barcode']) ?></td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><span class="badge" style="background:#eff6ff;color:var(--primary)"><?= htmlspecialchars($p['category']) ?></span></td>
            <td class="price"><?= number_format($p['price'],2) ?> ج</td>
            <td><div class="acts">
              <button class="btn btn-e" onclick="openEdit(<?= htmlspecialchars(json_encode($p, JSON_UNESCAPED_UNICODE)) ?>)">تعديل</button>
              <button class="btn btn-d" onclick="delProduct(<?= $p['id'] ?>,<?= json_encode($p['name'],JSON_UNESCAPED_UNICODE) ?>)">حذف</button>
            </div></td>
          </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="empty">لا توجد منتجات<?= $query?' لـ "'.$query.'"':'' ?></div>
    <?php endif ?>
  </div>

  <!-- ══ CATEGORIES TAB ══ -->
  <div class="tab-pane <?= $tab==='categories'?'active':'' ?>" id="tab-categories">
    <div class="card">
      <h2>إضافة قسم جديد</h2>
      <div class="form-row">
        <input type="text" id="newCatIcon" placeholder="أيقونة (إيموجي)" value="📦" style="max-width:90px"/>
        <input type="text" id="newCatName" placeholder="اسم القسم (مثال: خمور، ريدبول، بيبسي...)" required/>
        <button class="btn btn-p" onclick="addCategory()">+ إضافة قسم</button>
      </div>
    </div>

    <div class="cats-grid" id="catsGrid">
      <?php foreach($allCats as $c): ?>
      <div class="cat-card" id="cat-<?= $c['id'] ?>">
        <div class="cat-icon"><?= $c['icon'] ?></div>
        <div class="cat-info">
          <div class="cat-name"><?= htmlspecialchars($c['name']) ?></div>
          <div class="cat-count"><?= $counts[$c['name']]??0 ?> منتج</div>
        </div>
        <div class="cat-acts">
          <button class="btn btn-e" onclick="editCategory(<?= $c['id'] ?>,<?= json_encode($c['name'],JSON_UNESCAPED_UNICODE) ?>,'<?= $c['icon'] ?>')">✏️</button>
          <button class="btn btn-d" onclick="delCategory(<?= $c['id'] ?>,<?= json_encode($c['name'],JSON_UNESCAPED_UNICODE) ?>)">🗑</button>
        </div>
      </div>
      <?php endforeach ?>
    </div>
  </div>
</div>

<!-- Edit product modal -->
<div class="overlay" id="editModal">
  <div class="modal">
    <h2>تعديل المنتج</h2>
    <div class="form-row">
      <input type="text"   id="eBarcode"  placeholder="باركود"/>
      <input type="text"   id="eName"     placeholder="اسم المنتج"/>
      <input type="number" id="ePrice"    placeholder="السعر" step="0.01" min="0"/>
      <select id="eCat">
        <?php foreach($allCats as $c): ?><option value="<?= htmlspecialchars($c['name']) ?>"><?= $c['icon'].' '.$c['name'] ?></option><?php endforeach ?>
      </select>
    </div>
    <div style="margin-top:10px;display:flex;align-items:center;gap:10px">
      <img id="eImgPreview" class="img-preview" style="display:block"/>
      <label style="font-size:.82rem;cursor:pointer;color:var(--primary)">
        📷 تغيير الصورة
        <input type="file" id="eImgFile" accept="image/*" style="display:none" onchange="previewImg(this,'eImgPreview')"/>
      </label>
    </div>
    <div class="modal-acts">
      <button class="btn btn-c" onclick="closeModal('editModal')">إلغاء</button>
      <button class="btn btn-p" onclick="saveEdit()">حفظ</button>
    </div>
  </div>
</div>

<!-- Edit category modal -->
<div class="overlay" id="editCatModal">
  <div class="modal">
    <h2>تعديل القسم</h2>
    <div class="form-row">
      <input type="text" id="eCatIcon" placeholder="أيقونة" style="max-width:90px"/>
      <input type="text" id="eCatName" placeholder="اسم القسم"/>
    </div>
    <div class="modal-acts">
      <button class="btn btn-c" onclick="closeModal('editCatModal')">إلغاء</button>
      <button class="btn btn-p" onclick="saveCatEdit()">حفظ</button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
let editId=null, editCatId=null;

function switchTab(t){
  document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById('tab-'+t).classList.add('active');
  event.currentTarget.classList.add('active');
  history.replaceState(null,'','?tab='+t);
}

function showToast(m,ok=true){
  const t=document.getElementById('toast');
  t.textContent=m; t.style.background=ok?'#166534':'#991b1b';
  t.style.display='block'; setTimeout(()=>t.style.display='none',2200);
}
function closeModal(id){document.getElementById(id).classList.remove('open')}
document.querySelectorAll('.overlay').forEach(o=>o.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open')}));

function previewImg(input,previewId){
  const img=document.getElementById(previewId);
  if(input.files&&input.files[0]){const r=new FileReader();r.onload=e=>{img.src=e.target.result;img.style.display='block'};r.readAsDataURL(input.files[0])}
}

/* ── Products ── */
async function addProduct(e){
  e.preventDefault();
  const fd=new FormData(e.target); fd.append('action','add');
  const res=await fetch('api.php',{method:'POST',body:fd});
  const d=await res.json();
  if(d.ok){showToast('تمت الإضافة ✓');setTimeout(()=>location.reload(),500)}
  else showToast('خطأ: '+(d.error||''),false);
}

function openEdit(p){
  editId=p.id;
  document.getElementById('eBarcode').value=p.barcode;
  document.getElementById('eName').value=p.name;
  document.getElementById('ePrice').value=p.price;
  document.getElementById('eCat').value=p.category;
  const img=document.getElementById('eImgPreview');
  if(p.image_path){img.src=p.image_path;img.style.display='block'}else{img.style.display='none'}
  document.getElementById('editModal').classList.add('open');
}

async function saveEdit(){
  const fd=new FormData();
  fd.append('action','edit'); fd.append('id',editId);
  fd.append('barcode',document.getElementById('eBarcode').value);
  fd.append('name',document.getElementById('eName').value);
  fd.append('price',document.getElementById('ePrice').value);
  fd.append('category',document.getElementById('eCat').value);
  const f=document.getElementById('eImgFile');
  if(f.files[0]) fd.append('image',f.files[0]);
  const res=await fetch('api.php',{method:'POST',body:fd});
  const d=await res.json();
  if(d.ok){closeModal('editModal');showToast('تم التعديل ✓');setTimeout(()=>location.reload(),500)}
  else showToast('خطأ',false);
}

async function delProduct(id,name){
  if(!confirm('حذف '+name+'؟'))return;
  const fd=new FormData(); fd.append('action','delete'); fd.append('id',id);
  await fetch('api.php',{method:'POST',body:fd});
  document.getElementById('row-'+id)?.remove();
  showToast('تم الحذف');
}

/* ── Categories ── */
async function addCategory(){
  const name=document.getElementById('newCatName').value.trim();
  const icon=document.getElementById('newCatIcon').value.trim()||'📦';
  if(!name)return;
  const fd=new FormData(); fd.append('action','add_category'); fd.append('name',name); fd.append('icon',icon);
  const res=await fetch('api.php',{method:'POST',body:fd});
  const d=await res.json();
  if(d.ok){showToast('تمت الإضافة ✓');setTimeout(()=>location.reload(),500)}
  else showToast(d.error||'خطأ',false);
}

function editCategory(id,name,icon){
  editCatId=id;
  document.getElementById('eCatName').value=name;
  document.getElementById('eCatIcon').value=icon;
  document.getElementById('editCatModal').classList.add('open');
}

async function saveCatEdit(){
  const fd=new FormData();
  fd.append('action','edit_category'); fd.append('id',editCatId);
  fd.append('name',document.getElementById('eCatName').value);
  fd.append('icon',document.getElementById('eCatIcon').value);
  const res=await fetch('api.php',{method:'POST',body:fd});
  const d=await res.json();
  if(d.ok){closeModal('editCatModal');showToast('تم التعديل ✓');setTimeout(()=>location.reload(),500)}
  else showToast('خطأ',false);
}

async function delCategory(id,name){
  if(!confirm('حذف قسم "'+name+'"؟ المنتجات هتتنقل لـ منوعات'))return;
  const fd=new FormData(); fd.append('action','delete_category'); fd.append('id',id);
  await fetch('api.php',{method:'POST',body:fd});
  document.getElementById('cat-'+id)?.remove();
  showToast('تم الحذف');
}
</script>
</body>
</html>
