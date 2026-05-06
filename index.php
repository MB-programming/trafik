<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
requireLogin();

$db        = getDB();
$geminiKey = (AI_PROVIDER === 'gemini') ? GEMINI_API_KEY : '';
$hasAI     = AI_PROVIDER !== '' && $geminiKey !== '';
$tab       = $_GET['tab'] ?? 'products';
$query     = trim($_GET['q'] ?? '');
$catFilter = $_GET['cat'] ?? '';

$allCats  = $db->query('SELECT * FROM categories ORDER BY sort,id')->fetchAll();
$catNames = array_column($allCats,'name');
if ($catFilter && !in_array($catFilter,$catNames)) $catFilter='';

$params=[]; $sql='SELECT * FROM products WHERE 1=1';
if ($query) { $sql.=' AND (name LIKE ? OR barcode LIKE ?)'; $params[]="%$query%"; $params[]="%$query%"; }
if ($catFilter) { $sql.=' AND category=?'; $params[]=$catFilter; }
$sql.=' ORDER BY name';
$st=$db->prepare($sql); $st->execute($params);
$products=$st->fetchAll();

$counts=[];
foreach($allCats as $c) $counts[$c['name']]=(int)$db->query("SELECT COUNT(*) FROM products WHERE category='".addslashes($c['name'])."'")->fetchColumn();
$total=(int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn();

// helper: render FA icon safely
function faIcon(string $cls, string $extra=''): string {
    $cls = preg_replace('/[^a-z0-9\-]/','',$cls);
    $cls = preg_replace('/^fa-/','',$cls); // strip prefix if already present
    return '<i class="fa-solid fa-'.$cls.'"'.($extra?" $extra":'').'></i>';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>لوحة التحكم - ترافيك</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
  <style>
    :root{--primary:#2563eb;--primary-dark:#1d4ed8;--danger:#dc2626;--success:#16a34a;--bg:#f1f5f9;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b}
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',Tahoma,sans-serif;background:var(--bg);color:var(--text)}
    header{background:var(--primary);color:#fff;padding:11px 14px;display:flex;align-items:center;gap:9px;position:sticky;top:0;z-index:20;box-shadow:0 2px 8px rgba(0,0,0,.2);flex-wrap:wrap}
    header h1{font-size:1rem;flex:1;display:flex;align-items:center;gap:7px}
    .hbtn{background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.3);padding:6px 11px;border-radius:8px;font-size:.8rem;font-weight:600;cursor:pointer;text-decoration:none;white-space:nowrap;display:inline-flex;align-items:center;gap:5px}
    .hbtn:hover{background:rgba(255,255,255,.28)}
    .hbtn.hi{background:#fff;color:var(--primary);border-color:#fff}
    .container{max-width:980px;margin:0 auto;padding:16px 13px}
    .tabs{display:flex;background:var(--card);border:1px solid var(--border);border-radius:10px;overflow:hidden;margin-bottom:18px}
    .tab-btn{flex:1;padding:11px;border:none;background:none;font-size:.88rem;font-weight:600;cursor:pointer;color:var(--muted);border-bottom:3px solid transparent;transition:.15s;display:flex;align-items:center;justify-content:center;gap:6px}
    .tab-btn.active{color:var(--primary);border-bottom-color:var(--primary);background:#eff6ff}
    .tab-pane{display:none}.tab-pane.active{display:block}
    .card{background:var(--card);border:1px solid var(--border);border-radius:12px;padding:16px;margin-bottom:16px}
    .card h2{font-size:.9rem;color:var(--muted);margin-bottom:12px;font-weight:600}
    .form-row{display:flex;gap:7px;flex-wrap:wrap}
    .form-row input,.form-row select{flex:1;min-width:110px;padding:9px 11px;border:1px solid var(--border);border-radius:8px;font-size:.92rem;background:#fff;color:var(--text)}
    .form-row input:focus,.form-row select:focus{outline:2px solid var(--primary);border-color:transparent}
    .img-preview{width:52px;height:52px;border-radius:8px;object-fit:cover;border:1px solid var(--border);background:#f8fafc;display:none}
    .btn{padding:9px 14px;border:none;border-radius:8px;font-size:.87rem;font-weight:600;cursor:pointer;white-space:nowrap;display:inline-flex;align-items:center;gap:5px}
    .btn-p{background:var(--primary);color:#fff}.btn-p:hover{background:var(--primary-dark)}
    .btn-d{background:var(--danger);color:#fff;font-size:.78rem;padding:5px 9px}
    .btn-e{background:#f59e0b;color:#fff;font-size:.78rem;padding:5px 9px}
    .btn-c{background:var(--border);color:var(--text);text-decoration:none}
    .cat-tabs{display:flex;gap:6px;flex-wrap:wrap;margin-bottom:14px}
    .cat-tab{padding:5px 12px;border-radius:16px;font-size:.8rem;font-weight:600;text-decoration:none;background:#e2e8f0;color:var(--text);display:inline-flex;align-items:center;gap:5px}
    .cat-tab.active{background:var(--primary);color:#fff}
    .cat-tab:hover:not(.active){filter:brightness(.92)}
    .count{background:rgba(0,0,0,.12);border-radius:10px;padding:1px 6px;font-size:.72rem}
    .toolbar{display:flex;gap:7px;margin-bottom:14px;flex-wrap:wrap}
    .toolbar input{flex:1;min-width:160px;padding:9px 12px;border:1px solid var(--border);border-radius:8px;font-size:.92rem}
    .tbl-wrap{overflow-x:auto;border-radius:10px;box-shadow:0 1px 4px rgba(0,0,0,.05)}
    table{width:100%;border-collapse:collapse;background:var(--card)}
    thead{background:var(--primary);color:#fff}
    th,td{padding:9px 11px;text-align:right;font-size:.84rem}
    th{font-weight:600}
    tbody tr:nth-child(even){background:#f8fafc}
    tbody tr:hover{background:#eff6ff}
    td.price{font-weight:700;color:var(--success)}
    td.bc{font-family:monospace;color:var(--muted);font-size:.8rem}
    .acts{display:flex;gap:5px;justify-content:flex-end}
    .badge{display:inline-flex;align-items:center;gap:4px;padding:2px 9px;border-radius:10px;font-size:.72rem;font-weight:700;background:#eff6ff;color:var(--primary)}
    .pimg{width:36px;height:36px;border-radius:6px;object-fit:cover;background:#f1f5f9;border:1px solid var(--border)}
    .no-img{width:36px;height:36px;border-radius:6px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;color:#cbd5e1}

    /* ── category cards ── */
    .cats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(175px,1fr));gap:10px}
    .cat-card{background:var(--card);border:1px solid var(--border);border-radius:10px;padding:13px;display:flex;align-items:center;gap:10px}
    .cat-icon-wrap{width:44px;height:44px;border-radius:10px;background:#eff6ff;color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0}
    .cat-info{flex:1;min-width:0}
    .cat-name{font-weight:700;font-size:.93rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .cat-count{font-size:.76rem;color:var(--muted)}
    .cat-acts{display:flex;gap:5px;flex-shrink:0}

    /* ── icon picker ── */
    .icon-picker{display:flex;flex-wrap:wrap;gap:6px;margin-top:10px}
    .ip-btn{
      width:40px;height:40px;border-radius:8px;border:2px solid var(--border);
      background:#f8fafc;cursor:pointer;display:flex;align-items:center;justify-content:center;
      font-size:1rem;color:var(--muted);transition:.12s;
    }
    .ip-btn:hover{border-color:var(--primary);color:var(--primary);background:#eff6ff}
    .ip-btn.selected{border-color:var(--primary);background:#eff6ff;color:var(--primary)}
    .picked-preview{
      width:44px;height:44px;border-radius:9px;background:#eff6ff;
      color:var(--primary);display:flex;align-items:center;justify-content:center;
      font-size:1.3rem;border:2px solid #bfdbfe;flex-shrink:0;
    }

    .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:100;align-items:center;justify-content:center}
    .overlay.open{display:flex}
    .modal{background:var(--card);border-radius:14px;padding:20px;width:min(440px,95vw);box-shadow:0 8px 30px rgba(0,0,0,.2);max-height:90vh;overflow-y:auto}
    .modal h2{margin-bottom:13px;font-size:1rem;display:flex;align-items:center;gap:7px}
    .modal .form-row{flex-direction:column}
    .modal-acts{display:flex;gap:8px;margin-top:13px;justify-content:flex-end}
    .empty{text-align:center;padding:36px;color:var(--muted)}
    .toast{display:none;position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:#1e293b;color:#f1f5f9;padding:10px 20px;border-radius:10px;font-size:.87rem;z-index:200;box-shadow:0 4px 16px rgba(0,0,0,.3);white-space:nowrap}

    /* ── AI camera capture ── */
    .ai-cam-modal{background:#0f172a;border-radius:16px;padding:16px;width:min(380px,95vw);box-shadow:0 10px 40px rgba(0,0,0,.4)}
    .ai-cam-hdr{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
    .ai-cam-hdr h3{color:#f1f5f9;font-size:.95rem;display:flex;align-items:center;gap:7px}
    #aiCamVideo{width:100%;border-radius:10px;background:#000;display:block;max-height:300px;object-fit:cover}
    #aiCamCanvas{display:none}
    .ai-cam-actions{display:flex;gap:8px;margin-top:10px}
    .btn-capture{
      flex:1;padding:12px;background:#2563eb;color:#fff;border:none;
      border-radius:10px;font-size:.9rem;font-weight:700;cursor:pointer;
      display:flex;align-items:center;justify-content:center;gap:7px;
    }
    .btn-capture:active{transform:scale(.96)}
    .btn-capture.thinking{background:#374151;cursor:not-allowed}
    .btn-cam-close{
      padding:12px 16px;background:rgba(255,255,255,.1);color:#fff;border:none;
      border-radius:10px;font-size:.9rem;cursor:pointer;
    }
    .ai-status{color:#94a3b8;font-size:.78rem;text-align:center;margin-top:7px;min-height:1.2em}
    .btn-ai-id{
      padding:7px 11px;border:1.5px dashed #3b82f6;background:#eff6ff;color:#2563eb;
      border-radius:8px;font-size:.8rem;font-weight:600;cursor:pointer;
      display:inline-flex;align-items:center;gap:5px;white-space:nowrap;
      transition:.12s;flex-shrink:0;
    }
    .btn-ai-id:hover{background:#dbeafe}
    .btn-ai-id:disabled{opacity:.45;cursor:not-allowed}
  </style>
</head>
<body>
<header>
  <h1><i class="fa-solid fa-gear"></i> لوحة التحكم</h1>
  <a href="pos.php"     class="hbtn hi"><i class="fa-solid fa-store"></i> POS</a>
  <a href="history.php" class="hbtn"><i class="fa-solid fa-clock-rotate-left"></i> السجل</a>
  <a href="scanner.php" class="hbtn"><i class="fa-solid fa-camera"></i> سكانر</a>
  <a href="logout.php"  class="hbtn"><i class="fa-solid fa-right-from-bracket"></i> خروج</a>
</header>

<div class="container">
  <div class="tabs">
    <button class="tab-btn <?= $tab==='products'?'active':'' ?>" onclick="switchTab('products')">
      <i class="fa-solid fa-box"></i> المنتجات
    </button>
    <button class="tab-btn <?= $tab==='categories'?'active':'' ?>" onclick="switchTab('categories')">
      <i class="fa-solid fa-tags"></i> الأقسام
    </button>
  </div>

  <!-- ══ PRODUCTS TAB ══ -->
  <div class="tab-pane <?= $tab==='products'?'active':'' ?>" id="tab-products">
    <div class="card">
      <h2><i class="fa-solid fa-plus-circle"></i> إضافة منتج جديد</h2>
      <form id="addForm" onsubmit="addProduct(event)" enctype="multipart/form-data">
        <div class="form-row" style="margin-bottom:8px">
          <input type="text"   name="barcode" id="addBarcode" placeholder="باركود"/>
          <input type="text"   name="name"    id="addName"    placeholder="اسم المنتج" required/>
          <input type="number" name="price"   id="addPrice"   placeholder="السعر" step="0.01" min="0" required/>
          <select name="category" id="addCat">
            <?php foreach($allCats as $c): ?>
            <option value="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option>
            <?php endforeach ?>
          </select>
        </div>
        <div class="form-row" style="align-items:center;gap:10px">
          <button type="button" onclick="openCamCapture('add')"
            style="display:inline-flex;align-items:center;gap:6px;padding:8px 12px;border:1px dashed var(--border);border-radius:8px;background:#f8fafc;color:var(--muted);font-size:.82rem;cursor:pointer">
            <i class="fa-solid fa-camera"></i> تصوير
          </button>
          <label style="font-size:.82rem;color:var(--muted);cursor:pointer;display:inline-flex;align-items:center;gap:6px;border:1px dashed var(--border);padding:8px 12px;border-radius:8px;background:#f8fafc">
            <i class="fa-solid fa-image"></i> اختيار صورة
            <input type="file" name="image" id="addImgFile" accept="image/*" style="display:none" onchange="previewImg(this,'addPreview')"/>
          </label>
          <img id="addPreview" class="img-preview"/>
          <button class="btn btn-p" type="submit" style="margin-right:auto">
            <i class="fa-solid fa-plus"></i> إضافة
          </button>
        </div>
      </form>
    </div>

    <!-- Category filter tabs -->
    <div class="cat-tabs">
      <a href="?tab=products<?= $query?'&q='.urlencode($query):'' ?>" class="cat-tab <?= !$catFilter?'active':'' ?>">
        <i class="fa-solid fa-border-all"></i> الكل <span class="count"><?= $total ?></span>
      </a>
      <?php foreach($allCats as $c): ?>
      <a href="?tab=products&cat=<?= urlencode($c['name']).(($query)?'&q='.urlencode($query):'') ?>"
         class="cat-tab <?= $catFilter===$c['name']?'active':'' ?>">
        <?= faIcon($c['icon']) ?> <?= htmlspecialchars($c['name']) ?>
        <span class="count"><?= $counts[$c['name']]??0 ?></span>
      </a>
      <?php endforeach ?>
    </div>

    <!-- Search -->
    <form class="toolbar" method="get">
      <input type="hidden" name="tab" value="products"/>
      <?php if($catFilter): ?><input type="hidden" name="cat" value="<?= htmlspecialchars($catFilter) ?>"/><?php endif ?>
      <input type="text" name="q" placeholder="بحث بالاسم أو الباركود..." value="<?= htmlspecialchars($query) ?>"/>
      <button class="btn btn-p" type="submit"><i class="fa-solid fa-magnifying-glass"></i> بحث</button>
      <?php if($query): ?><a href="?tab=products<?= $catFilter?'&cat='.urlencode($catFilter):'' ?>" class="btn btn-c"><i class="fa-solid fa-xmark"></i></a><?php endif ?>
    </form>

    <?php if($products): ?>
    <div class="tbl-wrap">
      <table>
        <thead><tr><th>صورة</th><th>باركود</th><th>اسم المنتج</th><th>القسم</th><th>السعر</th><th>إجراءات</th></tr></thead>
        <tbody>
          <?php foreach($products as $p): ?>
          <tr id="row-<?= $p['id'] ?>">
            <td>
              <?php if($p['image_path']): ?>
              <img class="pimg" src="<?= htmlspecialchars($p['image_path']) ?>"/>
              <?php else: ?>
              <div class="no-img"><i class="fa-solid fa-box"></i></div>
              <?php endif ?>
            </td>
            <td class="bc"><?= htmlspecialchars($p['barcode']) ?></td>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td>
              <?php $cat=array_filter($allCats,fn($c)=>$c['name']===$p['category']); $cat=reset($cat); ?>
              <span class="badge">
                <?= $cat ? faIcon($cat['icon']) : '' ?>
                <?= htmlspecialchars($p['category']) ?>
              </span>
            </td>
            <td class="price"><?= number_format($p['price'],2) ?> <?= htmlspecialchars(defined('CURRENCY_LABEL')?CURRENCY_LABEL:'ج') ?></td>
            <td><div class="acts">
              <button class="btn btn-e" onclick="openEdit(<?= htmlspecialchars(json_encode($p,JSON_UNESCAPED_UNICODE)) ?>)">
                <i class="fa-solid fa-pen"></i> تعديل
              </button>
              <button class="btn btn-d" onclick="delProduct(<?= $p['id'] ?>,<?= json_encode($p['name'],JSON_UNESCAPED_UNICODE) ?>)">
                <i class="fa-solid fa-trash-can"></i>
              </button>
            </div></td>
          </tr>
          <?php endforeach ?>
        </tbody>
      </table>
    </div>
    <?php else: ?>
    <div class="empty"><i class="fa-solid fa-box-open" style="font-size:2rem;display:block;margin-bottom:8px;opacity:.3"></i>لا توجد منتجات<?= $query?' لـ "'.$query.'"':'' ?></div>
    <?php endif ?>
  </div>

  <!-- ══ CATEGORIES TAB ══ -->
  <div class="tab-pane <?= $tab==='categories'?'active':'' ?>" id="tab-categories">
    <div class="card">
      <h2><i class="fa-solid fa-plus-circle"></i> إضافة قسم جديد</h2>
      <div style="display:flex;gap:10px;align-items:flex-start;flex-wrap:wrap">
        <div class="picked-preview" id="pickedPreview"><i class="fa-solid fa-tag"></i></div>
        <div style="flex:1;min-width:200px">
          <div class="form-row" style="margin-bottom:8px">
            <input type="hidden" id="newCatIcon" value="tag"/>
            <input type="text"   id="newCatName" placeholder="اسم القسم (مثال: خمور، ريدبول، بيبسي...)" required/>
            <button class="btn btn-p" onclick="addCategory()"><i class="fa-solid fa-plus"></i> إضافة قسم</button>
          </div>
          <!-- Icon picker -->
          <div class="icon-picker" id="iconPicker">
            <?php
            $icons=[
              'tag','basket-shopping','bottle-water','smoking','box-open',
              'wine-bottle','beer-mug-empty','whiskey-glass','mug-hot',
              'bread-slice','burger','pizza-slice','ice-cream','fish',
              'candy','jar','apple-whole','carrot','egg','cookie',
              'truck','store','star','heart','bolt','fire',
            ];
            foreach($icons as $ic): ?>
            <button type="button" class="ip-btn <?= $ic==='tag'?'selected':'' ?>"
                    data-icon="<?= $ic ?>" onclick="pickIcon(this,'<?= $ic ?>')"
                    title="<?= $ic ?>">
              <i class="fa-solid fa-<?= $ic ?>"></i>
            </button>
            <?php endforeach ?>
          </div>
        </div>
      </div>
    </div>

    <div class="cats-grid" id="catsGrid">
      <?php foreach($allCats as $c): ?>
      <div class="cat-card" id="cat-<?= $c['id'] ?>">
        <div class="cat-icon-wrap"><?= faIcon($c['icon']) ?></div>
        <div class="cat-info">
          <div class="cat-name"><?= htmlspecialchars($c['name']) ?></div>
          <div class="cat-count"><?= $counts[$c['name']]??0 ?> منتج</div>
        </div>
        <div class="cat-acts">
          <button class="btn btn-e" onclick="editCategory(<?= $c['id'] ?>,<?= json_encode($c['name'],JSON_UNESCAPED_UNICODE) ?>,'<?= htmlspecialchars($c['icon']) ?>')">
            <i class="fa-solid fa-pen"></i>
          </button>
          <button class="btn btn-d" onclick="delCategory(<?= $c['id'] ?>,<?= json_encode($c['name'],JSON_UNESCAPED_UNICODE) ?>)">
            <i class="fa-solid fa-trash-can"></i>
          </button>
        </div>
      </div>
      <?php endforeach ?>
    </div>
  </div>
</div>

<!-- Edit product modal -->
<div class="overlay" id="editModal">
  <div class="modal">
    <h2><i class="fa-solid fa-pen"></i> تعديل المنتج</h2>
    <div class="form-row">
      <input type="text"   id="eBarcode" placeholder="باركود"/>
      <input type="text"   id="eName"    placeholder="اسم المنتج"/>
      <input type="number" id="ePrice"   placeholder="السعر" step="0.01" min="0"/>
      <select id="eCat">
        <?php foreach($allCats as $c): ?><option value="<?= htmlspecialchars($c['name']) ?>"><?= htmlspecialchars($c['name']) ?></option><?php endforeach ?>
      </select>
    </div>
    <div style="margin-top:10px;display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      <img id="eImgPreview" class="img-preview" style="display:block"/>
      <button type="button" onclick="openCamCapture('edit')"
        style="display:inline-flex;align-items:center;gap:5px;padding:7px 11px;border:1px dashed #93c5fd;border-radius:8px;background:#eff6ff;color:var(--primary);font-size:.82rem;cursor:pointer">
        <i class="fa-solid fa-camera"></i> تصوير
      </button>
      <label style="font-size:.82rem;cursor:pointer;color:var(--primary);display:inline-flex;align-items:center;gap:5px;border:1px dashed #93c5fd;padding:7px 11px;border-radius:8px">
        <i class="fa-solid fa-image"></i> اختيار صورة
        <input type="file" id="eImgFile" accept="image/*" style="display:none" onchange="previewImg(this,'eImgPreview')"/>
      </label>
    </div>
    <div class="modal-acts">
      <button class="btn btn-c" onclick="closeModal('editModal')"><i class="fa-solid fa-xmark"></i> إلغاء</button>
      <button class="btn btn-p" onclick="saveEdit()"><i class="fa-solid fa-check"></i> حفظ</button>
    </div>
  </div>
</div>

<!-- Edit category modal -->
<div class="overlay" id="editCatModal">
  <div class="modal">
    <h2><i class="fa-solid fa-pen"></i> تعديل القسم</h2>
    <div style="display:flex;gap:10px;align-items:flex-start">
      <div class="picked-preview" id="editCatPreview"><i class="fa-solid fa-tag"></i></div>
      <div style="flex:1">
        <input type="hidden" id="eCatIcon" value="tag"/>
        <input type="text"   id="eCatName" placeholder="اسم القسم" style="width:100%;padding:9px 11px;border:1px solid var(--border);border-radius:8px;font-size:.92rem;margin-bottom:8px"/>
        <div class="icon-picker" id="editIconPicker">
          <?php foreach($icons as $ic): ?>
          <button type="button" class="ip-btn" data-icon="<?= $ic ?>"
                  onclick="pickIconEdit(this,'<?= $ic ?>')" title="<?= $ic ?>">
            <i class="fa-solid fa-<?= $ic ?>"></i>
          </button>
          <?php endforeach ?>
        </div>
      </div>
    </div>
    <div class="modal-acts">
      <button class="btn btn-c" onclick="closeModal('editCatModal')"><i class="fa-solid fa-xmark"></i> إلغاء</button>
      <button class="btn btn-p" onclick="saveCatEdit()"><i class="fa-solid fa-check"></i> حفظ</button>
    </div>
  </div>
</div>

<!-- Camera Capture modal -->
<div class="overlay" id="aiCamOverlay">
  <div class="ai-cam-modal">
    <div class="ai-cam-hdr">
      <h3><i class="fa-solid fa-camera" style="color:#60a5fa"></i> تصوير المنتج</h3>
    </div>
    <video id="aiCamVideo" autoplay playsinline muted></video>
    <canvas id="aiCamCanvas"></canvas>
    <div class="ai-status" id="aiCamStatus">وجّه الكاميرا على المنتج واضغط تصوير</div>
    <div class="ai-cam-actions">
      <button class="btn-capture" id="btnCapture" onclick="capturePhoto()">
        <i class="fa-solid fa-camera"></i> تصوير
      </button>
      <button class="btn-cam-close" onclick="closeAiCapture()">
        <i class="fa-solid fa-xmark"></i>
      </button>
    </div>
  </div>
</div>

<div class="toast" id="toast"></div>

<script>
let editId=null, editCatId=null;

/* ── AI Camera Capture ── */
let aiCamStream=null, aiCamTarget='add'; // target: 'add' or 'edit'

async function openCamCapture(target){
  aiCamTarget=target;
  document.getElementById('aiCamStatus').textContent='وجّه الكاميرا على المنتج واضغط تصوير';
  document.getElementById('btnCapture').disabled=false;
  document.getElementById('btnCapture').innerHTML='<i class="fa-solid fa-camera"></i> تصوير';
  document.getElementById('aiCamOverlay').classList.add('open');
  try{
    aiCamStream=await navigator.mediaDevices.getUserMedia({
      video:{facingMode:{ideal:'environment'},width:{ideal:1280}}
    });
    document.getElementById('aiCamVideo').srcObject=aiCamStream;
  }catch(e){
    document.getElementById('aiCamStatus').textContent='تعذّر فتح الكاميرا';
  }
}

function closeAiCapture(){
  if(aiCamStream){aiCamStream.getTracks().forEach(t=>t.stop());aiCamStream=null;}
  document.getElementById('aiCamOverlay').classList.remove('open');
}

function capturePhoto(){
  const video=document.getElementById('aiCamVideo');
  const canvas=document.getElementById('aiCamCanvas');
  const status=document.getElementById('aiCamStatus');
  if(!aiCamStream||video.readyState<2){status.textContent='الكاميرا لم تُفعَّل بعد';return}

  canvas.width=video.videoWidth; canvas.height=video.videoHeight;
  canvas.getContext('2d').drawImage(video,0,0);

  const isAdd=aiCamTarget==='add';
  canvas.toBlob(blob=>{
    if(!blob)return;
    const file=new File([blob],'product.jpg',{type:'image/jpeg'});
    const dt=new DataTransfer(); dt.items.add(file);
    document.getElementById(isAdd?'addImgFile':'eImgFile').files=dt.files;
    const prev=document.getElementById(isAdd?'addPreview':'eImgPreview');
    prev.src=canvas.toDataURL('image/jpeg');
    prev.style.display='block';
  },'image/jpeg',0.9);

  closeAiCapture();
}

document.getElementById('aiCamOverlay').addEventListener('click',function(e){if(e.target===this)closeAiCapture()});

function switchTab(t){
  document.querySelectorAll('.tab-pane').forEach(p=>p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b=>b.classList.remove('active'));
  document.getElementById('tab-'+t).classList.add('active');
  event.currentTarget.classList.add('active');
  history.replaceState(null,'','?tab='+t);
}

function showToast(m,ok=true){
  const t=document.getElementById('toast');
  t.innerHTML=(ok?'<i class="fa-solid fa-circle-check"></i> ':'<i class="fa-solid fa-circle-xmark"></i> ')+m;
  t.style.cssText='display:block;position:fixed;bottom:20px;left:50%;transform:translateX(-50%);background:'+(ok?'#166534':'#991b1b')+';color:#f1f5f9;padding:10px 20px;border-radius:10px;font-size:.87rem;z-index:200;box-shadow:0 4px 16px rgba(0,0,0,.3);white-space:nowrap';
  clearTimeout(t._t);t._t=setTimeout(()=>t.style.display='none',2200);
}
function closeModal(id){document.getElementById(id).classList.remove('open')}
document.querySelectorAll('.overlay').forEach(o=>o.addEventListener('click',function(e){if(e.target===this)this.classList.remove('open')}));

/* ── Icon picker ── */
function pickIcon(btn,icon){
  document.querySelectorAll('#iconPicker .ip-btn').forEach(b=>b.classList.remove('selected'));
  btn.classList.add('selected');
  document.getElementById('newCatIcon').value=icon;
  document.getElementById('pickedPreview').innerHTML=`<i class="fa-solid fa-${icon}"></i>`;
}
function pickIconEdit(btn,icon){
  document.querySelectorAll('#editIconPicker .ip-btn').forEach(b=>b.classList.remove('selected'));
  btn.classList.add('selected');
  document.getElementById('eCatIcon').value=icon;
  document.getElementById('editCatPreview').innerHTML=`<i class="fa-solid fa-${icon}"></i>`;
}

function previewImg(input,previewId){
  const img=document.getElementById(previewId);
  if(input.files&&input.files[0]){const r=new FileReader();r.onload=e=>{img.src=e.target.result;img.style.display='block'};r.readAsDataURL(input.files[0])}
}

/* ── Products ── */
async function addProduct(e){
  e.preventDefault();
  const fd=new FormData(e.target);fd.append('action','add');
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
  if(p.image_path){img.src=p.image_path;img.style.display='block'}else img.style.display='none';
  document.getElementById('editModal').classList.add('open');
}
async function saveEdit(){
  const fd=new FormData();
  fd.append('action','edit');fd.append('id',editId);
  fd.append('barcode',document.getElementById('eBarcode').value);
  fd.append('name',document.getElementById('eName').value);
  fd.append('price',document.getElementById('ePrice').value);
  fd.append('category',document.getElementById('eCat').value);
  const f=document.getElementById('eImgFile');
  if(f.files[0])fd.append('image',f.files[0]);
  const res=await fetch('api.php',{method:'POST',body:fd});
  const d=await res.json();
  if(d.ok){closeModal('editModal');showToast('تم التعديل ✓');setTimeout(()=>location.reload(),500)}
  else showToast('خطأ',false);
}
async function delProduct(id,name){
  if(!confirm('حذف '+name+'؟'))return;
  const fd=new FormData();fd.append('action','delete');fd.append('id',id);
  await fetch('api.php',{method:'POST',body:fd});
  document.getElementById('row-'+id)?.remove();
  showToast('تم الحذف');
}

/* ── Categories ── */
async function addCategory(){
  const name=document.getElementById('newCatName').value.trim();
  const icon=document.getElementById('newCatIcon').value||'tag';
  if(!name)return;
  const fd=new FormData();fd.append('action','add_category');fd.append('name',name);fd.append('icon',icon);
  const res=await fetch('api.php',{method:'POST',body:fd});
  const d=await res.json();
  if(d.ok){showToast('تمت الإضافة ✓');setTimeout(()=>location.reload(),500)}
  else showToast(d.error||'خطأ',false);
}
function editCategory(id,name,icon){
  editCatId=id;
  document.getElementById('eCatName').value=name;
  document.getElementById('eCatIcon').value=icon;
  document.getElementById('editCatPreview').innerHTML=`<i class="fa-solid fa-${icon}"></i>`;
  document.querySelectorAll('#editIconPicker .ip-btn').forEach(b=>{
    b.classList.toggle('selected',b.dataset.icon===icon);
  });
  document.getElementById('editCatModal').classList.add('open');
}
async function saveCatEdit(){
  const fd=new FormData();
  fd.append('action','edit_category');fd.append('id',editCatId);
  fd.append('name',document.getElementById('eCatName').value);
  fd.append('icon',document.getElementById('eCatIcon').value);
  const res=await fetch('api.php',{method:'POST',body:fd});
  const d=await res.json();
  if(d.ok){closeModal('editCatModal');showToast('تم التعديل ✓');setTimeout(()=>location.reload(),500)}
  else showToast('خطأ',false);
}
async function delCategory(id,name){
  if(!confirm('حذف قسم "'+name+'"؟'))return;
  const fd=new FormData();fd.append('action','delete_category');fd.append('id',id);
  await fetch('api.php',{method:'POST',body:fd});
  document.getElementById('cat-'+id)?.remove();
  showToast('تم الحذف');
}
if(sessionStorage.getItem('fs')==='1') document.documentElement.requestFullscreen().catch(()=>{});
</script>
</body>
</html>
