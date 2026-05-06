<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
requireLogin();
$db   = getDB();
$cats = $db->query('SELECT * FROM categories ORDER BY sort,id')->fetchAll();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>POS — ترافيك</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
  <style>
    :root{
      --primary:#2563eb; --primary-dark:#1d4ed8;
      --success:#16a34a; --danger:#dc2626;
      --bg:#f0f4f8; --card:#fff;
      --border:#e2e8f0; --text:#0f172a; --muted:#64748b;
      --radius:14px;
    }
    *{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent}
    body{font-family:'Segoe UI',Tahoma,sans-serif;background:var(--bg);color:var(--text);height:100dvh;display:flex;flex-direction:column;overflow:hidden}

    /* ── Header ── */
    .hdr{
      background:var(--primary);color:#fff;
      padding:10px 14px;
      display:flex;align-items:center;gap:9px;
      box-shadow:0 2px 10px rgba(0,0,0,.25);
      flex-shrink:0;
    }
    .hdr-title{font-size:1rem;font-weight:700;flex:1}
    .icon-btn{
      width:36px;height:36px;border-radius:9px;
      background:rgba(255,255,255,.18);border:none;color:#fff;
      display:flex;align-items:center;justify-content:center;
      font-size:.95rem;cursor:pointer;text-decoration:none;
      transition:.15s;
    }
    .icon-btn:hover{background:rgba(255,255,255,.3)}

    /* ── Search ── */
    .searchbar{
      padding:9px 12px;background:var(--card);
      border-bottom:1px solid var(--border);
      display:flex;gap:8px;flex-shrink:0;
    }
    .search-wrap{flex:1;position:relative}
    .search-wrap i{position:absolute;right:11px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:.9rem;pointer-events:none}
    .search-wrap input{
      width:100%;padding:9px 34px 9px 11px;
      border:2px solid var(--border);border-radius:10px;
      font-size:.95rem;transition:.15s;background:#f8fafc;
    }
    .search-wrap input:focus{border-color:var(--primary);background:#fff;outline:none}
    .scan-btn{
      width:42px;height:42px;background:var(--primary);color:#fff;
      border:none;border-radius:10px;font-size:1rem;cursor:pointer;
      display:flex;align-items:center;justify-content:center;flex-shrink:0;
    }
    .scan-btn:hover{background:var(--primary-dark)}
    .scan-btn:active{transform:scale(.93)}

    /* ── Category strip ── */
    .cat-strip{
      display:flex;gap:6px;padding:8px 12px;
      overflow-x:auto;background:var(--card);
      border-bottom:1px solid var(--border);flex-shrink:0;
      -webkit-overflow-scrolling:touch;
    }
    .cat-strip::-webkit-scrollbar{display:none}
    .ctab{
      display:flex;align-items:center;gap:5px;
      padding:6px 13px;border-radius:20px;
      font-size:.8rem;font-weight:700;white-space:nowrap;
      border:2px solid var(--border);background:#f8fafc;color:var(--muted);
      cursor:pointer;transition:.12s;
    }
    .ctab i{font-size:.75rem}
    .ctab.active{background:var(--primary);color:#fff;border-color:var(--primary)}
    .ctab:hover:not(.active){border-color:#93c5fd;color:var(--primary);background:#eff6ff}

    /* ── Product grid ── */
    .prod-area{flex:1;overflow-y:auto;padding:10px;-webkit-overflow-scrolling:touch}
    .prod-grid{
      display:grid;
      grid-template-columns:repeat(auto-fill,minmax(150px,1fr));
      gap:10px;
    }
    @media(max-width:400px){.prod-grid{grid-template-columns:repeat(2,1fr)}}

    .prod-card{
      background:var(--card);border-radius:var(--radius);
      overflow:hidden;cursor:pointer;
      border:2px solid var(--border);
      transition:transform .12s,border-color .12s,box-shadow .12s;
      position:relative;display:flex;flex-direction:column;
      user-select:none;
    }
    .prod-card:active{transform:scale(.94)}
    .prod-card.in-cart{border-color:var(--success);box-shadow:0 0 0 3px rgba(22,163,74,.15)}

    /* product image */
    .prod-img-wrap{
      position:relative;
      padding-top:100%; /* square */
      background:linear-gradient(135deg,#f1f5f9,#e8eef4);
      overflow:hidden;
    }
    .prod-img{
      position:absolute;inset:0;
      width:100%;height:100%;object-fit:cover;
      transition:transform .2s;
    }
    .prod-card:hover .prod-img{transform:scale(1.05)}
    .prod-noimg{
      position:absolute;inset:0;
      display:flex;align-items:center;justify-content:center;
      font-size:2.8rem;color:#cbd5e1;
    }

    /* cart badge on card */
    .prod-badge{
      position:absolute;top:7px;left:7px;
      background:var(--success);color:#fff;
      border-radius:20px;padding:3px 9px;
      font-size:.75rem;font-weight:800;
      display:none;box-shadow:0 2px 6px rgba(0,0,0,.2);
    }
    .prod-card.in-cart .prod-badge{display:flex;align-items:center;gap:4px}

    /* add ripple overlay */
    .prod-ripple{
      position:absolute;inset:0;
      background:rgba(37,99,235,.12);
      opacity:0;transition:opacity .15s;
      pointer-events:none;
    }
    .prod-card:active .prod-ripple{opacity:1}

    /* product info */
    .prod-info{padding:8px 10px 10px}
    .prod-name{font-size:.82rem;font-weight:700;line-height:1.3;margin-bottom:5px;color:var(--text)}
    .prod-price-row{display:flex;align-items:baseline;gap:3px}
    .prod-price{font-size:1.05rem;font-weight:900;color:var(--success)}
    .prod-cur{font-size:.72rem;color:var(--success);font-weight:600}

    .no-results{grid-column:1/-1;text-align:center;padding:50px 20px;color:var(--muted)}
    .no-results i{font-size:2.5rem;margin-bottom:10px;display:block;opacity:.4}

    /* ── Cart bar ── */
    .cart-bar{
      background:var(--primary);color:#fff;
      padding:12px 16px;
      display:flex;align-items:center;justify-content:space-between;
      cursor:pointer;flex-shrink:0;
      box-shadow:0 -3px 15px rgba(0,0,0,.15);
    }
    .cart-bar:active{background:var(--primary-dark)}
    .cb-left{display:flex;align-items:center;gap:10px}
    .cb-count{
      background:rgba(255,255,255,.22);
      border-radius:20px;padding:4px 12px;
      font-size:.85rem;font-weight:700;
      display:flex;align-items:center;gap:6px;
    }
    .cb-total{font-size:1.25rem;font-weight:900}
    .cb-cur{font-size:.8rem;opacity:.75}
    .cb-arrow{font-size:.85rem;opacity:.75;transition:.25s}
    .cb-arrow.up{transform:rotate(180deg)}

    /* ── Cart sheet ── */
    .cart-sheet{position:fixed;inset:0;z-index:50;pointer-events:none;display:flex;flex-direction:column;justify-content:flex-end}
    .cart-bd{position:absolute;inset:0;background:rgba(0,0,0,.45);opacity:0;transition:opacity .3s;pointer-events:none}
    .cart-panel{
      background:var(--card);border-radius:22px 22px 0 0;
      max-height:85dvh;display:flex;flex-direction:column;
      transform:translateY(100%);transition:transform .35s cubic-bezier(.22,1,.36,1);
      pointer-events:none;
    }
    .cart-sheet.open .cart-bd{opacity:1;pointer-events:all}
    .cart-sheet.open .cart-panel{transform:translateY(0);pointer-events:all}
    .cart-sheet.open{pointer-events:all}

    .cart-handle{width:44px;height:4px;background:#e2e8f0;border-radius:2px;margin:12px auto 0}
    .cart-hdr{padding:12px 16px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border)}
    .cart-hdr h2{font-size:1rem;font-weight:700;display:flex;align-items:center;gap:7px}
    .close-btn{width:32px;height:32px;border:none;background:#f1f5f9;border-radius:8px;cursor:pointer;display:flex;align-items:center;justify-content:center;color:var(--muted)}
    .close-btn:hover{background:#e2e8f0}

    .cart-list{flex:1;overflow-y:auto;padding:8px 12px}
    .cart-empty{text-align:center;padding:36px;color:var(--muted)}
    .cart-empty i{font-size:2.4rem;display:block;margin-bottom:8px;opacity:.35}

    .ci{display:flex;align-items:center;gap:9px;padding:9px 0;border-bottom:1px solid #f1f5f9}
    .ci:last-child{border-bottom:none}
    .ci-img{width:44px;height:44px;border-radius:9px;object-fit:cover;background:#f1f5f9;border:1px solid var(--border);flex-shrink:0}
    .ci-ph{width:44px;height:44px;border-radius:9px;background:#f1f5f9;border:1px solid var(--border);display:flex;align-items:center;justify-content:center;color:#cbd5e1;flex-shrink:0}
    .ci-body{flex:1;min-width:0}
    .ci-name{font-size:.87rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .ci-price{font-size:.75rem;color:var(--muted)}
    .qty-ctrl{display:flex;align-items:center;gap:5px;flex-shrink:0}
    .qbtn{
      width:29px;height:29px;border-radius:8px;
      border:1.5px solid var(--border);background:#f8fafc;
      display:flex;align-items:center;justify-content:center;
      cursor:pointer;font-size:.75rem;color:var(--text);
      transition:.12s;
    }
    .qbtn:hover{background:var(--primary);color:#fff;border-color:var(--primary)}
    .qbtn:active{transform:scale(.88)}
    .qty-n{font-size:.9rem;font-weight:800;min-width:22px;text-align:center}
    .ci-total{font-weight:800;color:var(--success);font-size:.9rem;white-space:nowrap;min-width:52px;text-align:left;flex-shrink:0}
    .ci-del{border:none;background:none;cursor:pointer;color:#fca5a5;font-size:.85rem;padding:4px;flex-shrink:0}
    .ci-del:hover{color:var(--danger)}

    .cart-foot{padding:12px 16px;border-top:1px solid var(--border)}
    .cart-notes{width:100%;padding:8px 11px;border:1.5px solid var(--border);border-radius:9px;font-size:.88rem;resize:none;font-family:inherit;margin-bottom:10px;background:#f8fafc}
    .cart-notes:focus{outline:2px solid var(--primary);border-color:transparent;background:#fff}
    .total-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:11px}
    .total-lbl{font-size:.88rem;color:var(--muted);display:flex;align-items:center;gap:5px}
    .total-amt{font-size:1.65rem;font-weight:900;color:var(--success)}
    .btn-checkout{
      width:100%;padding:14px;background:var(--success);color:#fff;
      border:none;border-radius:11px;font-size:1rem;font-weight:800;
      cursor:pointer;display:flex;align-items:center;justify-content:center;gap:8px;
      transition:.15s;
    }
    .btn-checkout:hover{background:#15803d}
    .btn-checkout:active{transform:scale(.98)}
    .btn-checkout:disabled{background:#94a3b8;cursor:not-allowed}
    .btn-clear{
      width:100%;margin-top:7px;padding:8px;background:none;
      border:1px solid var(--border);border-radius:9px;
      color:var(--muted);font-size:.82rem;cursor:pointer;
      display:flex;align-items:center;justify-content:center;gap:6px;
    }
    .btn-clear:hover{background:#fee2e2;border-color:var(--danger);color:var(--danger)}

    /* ── Receipt ── */
    .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:100;align-items:center;justify-content:center;padding:12px}
    .overlay.open{display:flex}
    .receipt{background:var(--card);border-radius:18px;padding:22px;width:min(400px,100%);box-shadow:0 12px 40px rgba(0,0,0,.25);max-height:90dvh;overflow-y:auto}
    .rcp-hd{text-align:center;margin-bottom:14px}
    .rcp-icon{font-size:3rem;color:var(--success);margin-bottom:6px}
    .rcp-hd h2{font-size:1.1rem;font-weight:800}
    .rcp-hd small{color:var(--muted);font-size:.78rem}
    .r-items{border-top:1px dashed var(--border);border-bottom:1px dashed var(--border);padding:10px 0;margin-bottom:10px}
    .ri{display:flex;justify-content:space-between;padding:4px 0;font-size:.87rem}
    .ri-n{flex:1} .ri-q{color:var(--muted);padding:0 8px} .ri-t{font-weight:700}
    .r-total{display:flex;justify-content:space-between;font-size:1.1rem;font-weight:900;margin-bottom:14px}
    .r-acts{display:flex;gap:8px}
    .btn-new{flex:1;padding:11px;background:var(--primary);color:#fff;border:none;border-radius:9px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px}
    .btn-print-r{flex:1;padding:11px;background:#f1f5f9;color:var(--text);border:1px solid var(--border);border-radius:9px;font-weight:700;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:6px}

    /* ── Scanner modal ── */
    .scan-modal{background:var(--card);border-radius:16px;padding:15px;width:min(340px,95vw);box-shadow:0 10px 35px rgba(0,0,0,.25)}
    .scan-modal-hdr{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
    .scan-modal-hdr h3{font-size:.97rem;font-weight:700;display:flex;align-items:center;gap:7px}
    #scanV{width:100%;border-radius:10px;background:#000;display:block;max-height:280px;object-fit:cover}
    #scanC{display:none}
    .btn-close-scan{background:#f1f5f9;border:none;border-radius:8px;padding:8px 16px;cursor:pointer;font-size:.85rem;font-weight:600;margin-top:9px;width:100%;display:flex;align-items:center;justify-content:center;gap:6px}

    .toast{
      display:none;position:fixed;bottom:90px;left:50%;transform:translateX(-50%);
      background:#1e293b;color:#f1f5f9;padding:9px 18px;border-radius:10px;
      font-size:.87rem;z-index:200;box-shadow:0 4px 16px rgba(0,0,0,.3);
      white-space:nowrap;display:none;align-items:center;gap:7px;
    }

    @media print{
      body>*:not(#receiptOverlay){display:none}
      .overlay{display:flex!important;position:static;background:none;padding:0}
      .receipt{box-shadow:none;padding:0}
      .r-acts{display:none}
    }
  </style>
</head>
<body>

<!-- Header -->
<div class="hdr">
  <a href="index.php" class="icon-btn" title="لوحة التحكم"><i class="fa-solid fa-gear"></i></a>
  <span class="hdr-title"><i class="fa-solid fa-store"></i> نقطة البيع</span>
  <a href="history.php" class="icon-btn" title="سجل المبيعات"><i class="fa-solid fa-clock-rotate-left"></i></a>
  <a href="fastscan.php" class="icon-btn" title="Fast Scan"><i class="fa-solid fa-bolt"></i></a>
  <a href="logout.php"  class="icon-btn" title="خروج"><i class="fa-solid fa-right-from-bracket"></i></a>
</div>

<!-- Search -->
<div class="searchbar">
  <div class="search-wrap">
    <i class="fa-solid fa-magnifying-glass"></i>
    <input type="text" id="searchIn" placeholder="ابحث بالاسم أو الباركود..."
           oninput="search(this.value)" onkeydown="if(event.key==='Enter')addFirst()"/>
  </div>
  <button class="scan-btn" onclick="openScanner()" title="مسح باركود">
    <i class="fa-solid fa-camera"></i>
  </button>
</div>

<!-- Category tabs -->
<div class="cat-strip">
  <button class="ctab active" data-cat="" onclick="setcat(this,'')">
    <i class="fa-solid fa-border-all"></i> الكل
  </button>
  <?php foreach($cats as $c): ?>
  <button class="ctab" data-cat="<?= htmlspecialchars($c['name']) ?>"
          onclick="setcat(this,<?= json_encode($c['name'],JSON_UNESCAPED_UNICODE) ?>)">
    <i class="fa-solid <?= htmlspecialchars($c['icon']) ?>"></i>
    <?= htmlspecialchars($c['name']) ?>
  </button>
  <?php endforeach ?>
</div>

<!-- Products -->
<div class="prod-area">
  <div class="prod-grid" id="prodGrid">
    <div class="no-results"><i class="fa-solid fa-spinner fa-spin"></i>جارٍ التحميل...</div>
  </div>
</div>

<!-- Cart bar -->
<div class="cart-bar" id="cartBar" onclick="toggleCart()">
  <div class="cb-left">
    <i class="fa-solid fa-cart-shopping"></i>
    <span class="cb-count">
      <i class="fa-solid fa-layer-group" style="font-size:.7rem"></i>
      <span id="barQty">0</span> قطعة
    </span>
  </div>
  <div style="display:flex;align-items:center;gap:8px">
    <span class="cb-total" id="barTotal">0.00</span>
    <span class="cb-cur">ج</span>
    <i class="fa-solid fa-chevron-up cb-arrow" id="barArrow"></i>
  </div>
</div>

<!-- Cart sheet -->
<div class="cart-sheet" id="cartSheet">
  <div class="cart-bd" onclick="toggleCart()"></div>
  <div class="cart-panel">
    <div class="cart-handle"></div>
    <div class="cart-hdr">
      <h2><i class="fa-solid fa-cart-shopping"></i> السلة</h2>
      <button class="close-btn" onclick="toggleCart()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="cart-list" id="cartList">
      <div class="cart-empty">
        <i class="fa-solid fa-cart-shopping"></i>
        السلة فاضية
      </div>
    </div>
    <div class="cart-foot">
      <textarea class="cart-notes" id="cartNotes" rows="2" placeholder="ملاحظات (اختياري)..."></textarea>
      <div class="total-row">
        <span class="total-lbl"><i class="fa-solid fa-receipt"></i> الإجمالي</span>
        <div><span class="total-amt" id="totalAmt">0.00</span> ج</div>
      </div>
      <button class="btn-checkout" id="checkoutBtn" onclick="checkout()" disabled>
        <i class="fa-solid fa-check"></i> تأكيد البيع
      </button>
      <button class="btn-clear" onclick="clearCart()">
        <i class="fa-solid fa-trash-can"></i> إفراغ السلة
      </button>
    </div>
  </div>
</div>

<!-- Receipt overlay -->
<div class="overlay" id="receiptOverlay">
  <div class="receipt" id="receiptContent"></div>
</div>

<!-- Scanner overlay -->
<div class="overlay" id="scanOverlay">
  <div class="scan-modal">
    <div class="scan-modal-hdr">
      <h3><i class="fa-solid fa-camera"></i> امسح الباركود</h3>
    </div>
    <video id="scanV" autoplay playsinline muted></video>
    <canvas id="scanC"></canvas>
    <button class="btn-close-scan" onclick="closeScanner()">
      <i class="fa-solid fa-xmark"></i> إغلاق
    </button>
  </div>
</div>

<div class="toast" id="toast"></div>

<script src="https://unpkg.com/@zxing/library@0.21.3/umd/index.min.js"></script>
<script>
let cart=[], currentCat='', allProducts=[], searchTimer=null, scanStream=null;

/* ── Helpers ── */
function esc(s){return String(s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}

function showToast(msg, ok=true){
  const t=document.getElementById('toast');
  t.innerHTML=`<i class="fa-solid ${ok?'fa-circle-check':'fa-circle-xmark'}"></i> ${msg}`;
  t.style.cssText='display:flex;position:fixed;bottom:90px;left:50%;transform:translateX(-50%);background:'+(ok?'#166534':'#991b1b')+';color:#f1f5f9;padding:9px 18px;border-radius:10px;font-size:.87rem;z-index:200;box-shadow:0 4px 16px rgba(0,0,0,.3);white-space:nowrap;align-items:center;gap:7px;';
  clearTimeout(t._t);
  t._t=setTimeout(()=>t.style.display='none',2200);
}

/* ── Load & render products ── */
async function loadProducts(q='', cat=''){
  const url='api.php?action=search&q='+encodeURIComponent(q)+'&cat='+encodeURIComponent(cat);
  const r=await fetch(url); const d=await r.json();
  allProducts=d.results||[];
  renderGrid(allProducts);
}

function renderGrid(products){
  const g=document.getElementById('prodGrid');
  if(!products.length){
    g.innerHTML='<div class="no-results"><i class="fa-solid fa-box-open"></i><br>لا توجد منتجات</div>';
    return;
  }
  g.innerHTML=products.map(p=>{
    const item=cart.find(i=>i.barcode===p.barcode);
    const qty=item?item.qty:0;
    const imgHtml=p.image_path
      ?`<img class="prod-img" src="${esc(p.image_path)}" loading="lazy" alt="${esc(p.name)}"/>`
      :`<div class="prod-noimg"><i class="fa-solid fa-box"></i></div>`;
    return `
      <div class="prod-card ${qty>0?'in-cart':''}" id="pc-${p.id}"
           onclick="addToCart(${JSON.stringify(p).replace(/"/g,'&quot;')})">
        <div class="prod-img-wrap">
          ${imgHtml}
          <div class="prod-badge" id="cb-${p.id}">
            <i class="fa-solid fa-check" style="font-size:.6rem"></i> ${qty}
          </div>
          <div class="prod-ripple"></div>
        </div>
        <div class="prod-info">
          <div class="prod-name">${esc(p.name)}</div>
          <div class="prod-price-row">
            <span class="prod-price">${parseFloat(p.price).toFixed(2)}</span>
            <span class="prod-cur">ج</span>
          </div>
        </div>
      </div>`;
  }).join('');
}

/* ── Search & filter ── */
function search(q){
  clearTimeout(searchTimer);
  searchTimer=setTimeout(()=>loadProducts(q,currentCat),200);
}
function addFirst(){const f=document.querySelector('.prod-card');if(f)f.click()}
function setcat(btn,cat){
  currentCat=cat;
  document.querySelectorAll('.ctab').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  loadProducts(document.getElementById('searchIn').value,cat);
}

/* ── Cart ── */
function addToCart(p){
  const ex=cart.find(i=>i.barcode===p.barcode);
  if(ex) ex.qty++; else cart.push({...p,qty:1});
  renderCart();
  refreshCardBadge(p);
  showToast(p.name);
}

function refreshCardBadge(p){
  const card=document.getElementById('pc-'+p.id);
  const badge=document.getElementById('cb-'+p.id);
  if(!card||!badge)return;
  const item=cart.find(i=>i.barcode===p.barcode);
  if(item&&item.qty>0){
    card.classList.add('in-cart');
    badge.innerHTML=`<i class="fa-solid fa-check" style="font-size:.6rem"></i> ${item.qty}`;
  } else {
    card.classList.remove('in-cart');
  }
}

function changeQty(barcode,delta){
  const item=cart.find(i=>i.barcode===barcode);
  if(!item)return;
  item.qty+=delta;
  if(item.qty<=0) cart=cart.filter(i=>i.barcode!==barcode);
  renderCart();
  const p=allProducts.find(x=>x.barcode===barcode);
  if(p)refreshCardBadge(p);
}

function removeItem(barcode){
  const p=allProducts.find(x=>x.barcode===barcode);
  cart=cart.filter(i=>i.barcode!==barcode);
  renderCart();
  if(p)refreshCardBadge(p);
}

function clearCart(){
  if(!cart.length)return;
  if(!confirm('إفراغ السلة؟'))return;
  cart=[];
  renderCart();
  allProducts.forEach(p=>refreshCardBadge(p));
}

function renderCart(){
  const total=cart.reduce((s,i)=>s+i.price*i.qty,0);
  const count=cart.reduce((s,i)=>s+i.qty,0);
  document.getElementById('barQty').textContent=count;
  document.getElementById('barTotal').textContent=total.toFixed(2);
  document.getElementById('totalAmt').textContent=total.toFixed(2);
  document.getElementById('checkoutBtn').disabled=!cart.length;

  const el=document.getElementById('cartList');
  if(!cart.length){
    el.innerHTML='<div class="cart-empty"><i class="fa-solid fa-cart-shopping"></i>السلة فاضية</div>';
    return;
  }
  el.innerHTML=cart.map(i=>`
    <div class="ci">
      ${i.image_path
        ?`<img class="ci-img" src="${esc(i.image_path)}" alt="${esc(i.name)}"/>`
        :`<div class="ci-ph"><i class="fa-solid fa-box"></i></div>`}
      <div class="ci-body">
        <div class="ci-name">${esc(i.name)}</div>
        <div class="ci-price">${parseFloat(i.price).toFixed(2)} ج × ${i.qty}</div>
      </div>
      <div class="qty-ctrl">
        <button class="qbtn" onclick="changeQty('${esc(i.barcode)}',-1)"><i class="fa-solid fa-minus"></i></button>
        <span class="qty-n">${i.qty}</span>
        <button class="qbtn" onclick="changeQty('${esc(i.barcode)}',1)"><i class="fa-solid fa-plus"></i></button>
      </div>
      <div class="ci-total">${(i.price*i.qty).toFixed(2)} ج</div>
      <button class="ci-del" onclick="removeItem('${esc(i.barcode)}')"><i class="fa-solid fa-xmark"></i></button>
    </div>`).join('');
}

/* ── Cart toggle ── */
function toggleCart(){
  const s=document.getElementById('cartSheet');
  const a=document.getElementById('barArrow');
  const open=s.classList.toggle('open');
  a.classList.toggle('up',open);
}

/* ── Checkout ── */
async function checkout(){
  if(!cart.length)return;
  const notes=document.getElementById('cartNotes').value;
  const res=await fetch('api.php?action=create_order',{
    method:'POST',headers:{'Content-Type':'application/json'},
    body:JSON.stringify({items:cart,notes})
  });
  const d=await res.json();
  if(!d.ok){showToast('خطأ في الحفظ',false);return}
  const saved=[...cart];
  cart=[];renderCart();
  document.getElementById('cartNotes').value='';
  document.getElementById('cartSheet').classList.remove('open');
  document.getElementById('barArrow').classList.remove('up');
  allProducts.forEach(p=>refreshCardBadge(p));
  showReceipt(d.order_id,d.total,saved,notes);
}

function showReceipt(id,total,items,notes){
  document.getElementById('receiptContent').innerHTML=`
    <div class="rcp-hd">
      <div class="rcp-icon"><i class="fa-solid fa-circle-check"></i></div>
      <h2>تم البيع!</h2>
      <small><i class="fa-solid fa-hashtag"></i>${id} — ${new Date().toLocaleString('ar-EG')}</small>
    </div>
    <div class="r-items">
      ${items.map(i=>`<div class="ri">
        <span class="ri-n">${esc(i.name)}</span>
        <span class="ri-q">×${i.qty}</span>
        <span class="ri-t">${(i.price*i.qty).toFixed(2)} ج</span>
      </div>`).join('')}
    </div>
    ${notes?`<p style="font-size:.8rem;color:var(--muted);margin-bottom:9px"><i class="fa-solid fa-note-sticky"></i> ${esc(notes)}</p>`:''}
    <div class="r-total"><span>الإجمالي</span><span>${parseFloat(total).toFixed(2)} ج</span></div>
    <div class="r-acts">
      <button class="btn-print-r" onclick="window.print()"><i class="fa-solid fa-print"></i> طباعة</button>
      <button class="btn-new" onclick="document.getElementById('receiptOverlay').classList.remove('open')"><i class="fa-solid fa-plus"></i> بيع جديد</button>
    </div>`;
  document.getElementById('receiptOverlay').classList.add('open');
}

/* ── Barcode scanner ── */
async function openScanner(){
  document.getElementById('scanOverlay').classList.add('open');
  try{
    scanStream=await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'},width:{ideal:1280}}});
    const v=document.getElementById('scanV'); v.srcObject=scanStream;
    const hints=new Map([
      [ZXing.DecodeHintType.POSSIBLE_FORMATS,[ZXing.BarcodeFormat.EAN_13,ZXing.BarcodeFormat.EAN_8,ZXing.BarcodeFormat.CODE_128,ZXing.BarcodeFormat.CODE_39,ZXing.BarcodeFormat.UPC_A,ZXing.BarcodeFormat.UPC_E]],
      [ZXing.DecodeHintType.TRY_HARDER,true]
    ]);
    const reader=new ZXing.BrowserMultiFormatReader(hints);
    const c=document.getElementById('scanC'); const ctx=c.getContext('2d');
    let lastBC=null;
    function tick(){
      if(!scanStream)return;
      if(v.readyState>=2){
        c.width=v.videoWidth;c.height=v.videoHeight;ctx.drawImage(v,0,0);
        try{
          const img=ctx.getImageData(0,0,c.width,c.height);
          const lum=new ZXing.RGBLuminanceSource(img.data,c.width,c.height);
          const bmp=new ZXing.BinaryBitmap(new ZXing.HybridBinarizer(lum));
          const r=reader.decodeBitmap(bmp);
          if(r&&r.getText()!==lastBC){lastBC=r.getText();lookupAndAdd(lastBC)}
        }catch(_){}
      }
      requestAnimationFrame(tick);
    }
    v.addEventListener('loadedmetadata',()=>requestAnimationFrame(tick));
  }catch(e){showToast('تعذّر فتح الكاميرا',false);closeScanner()}
}

function closeScanner(){
  if(scanStream){scanStream.getTracks().forEach(t=>t.stop());scanStream=null}
  document.getElementById('scanOverlay').classList.remove('open');
}

async function lookupAndAdd(bc){
  const r=await fetch('api.php?action=lookup&barcode='+encodeURIComponent(bc));
  const d=await r.json();
  if(d.found){addToCart(d);closeScanner()}
  else showToast('الباركود غير موجود',false);
}

/* ── Event listeners ── */
document.getElementById('scanOverlay').addEventListener('click',function(e){if(e.target===this)closeScanner()});
document.getElementById('receiptOverlay').addEventListener('click',function(e){if(e.target===this)this.classList.remove('open')});

// init
loadProducts('','');
</script>
</body>
</html>
