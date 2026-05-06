<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
requireLogin();
$db       = getDB();
$cats     = $db->query('SELECT * FROM categories ORDER BY sort,id')->fetchAll();
$currency = defined('CURRENCY_LABEL') ? CURRENCY_LABEL : 'ج';
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
      --success:#16a34a; --success-dark:#15803d;
      --danger:#dc2626;
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

    .prod-img-wrap{
      position:relative;
      padding-top:100%;
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

    /* badge */
    .prod-badge{
      position:absolute;top:7px;left:7px;
      background:var(--success);color:#fff;
      border-radius:20px;padding:0 4px 0 0;
      font-size:.78rem;font-weight:800;
      display:none;box-shadow:0 2px 6px rgba(0,0,0,.25);
      align-items:center;gap:0;overflow:hidden;
    }
    .prod-card.in-cart .prod-badge{display:flex}
    .badge-minus{
      background:rgba(0,0,0,.2);border:none;color:#fff;
      padding:4px 8px;font-size:.95rem;line-height:1;
      cursor:pointer;font-weight:900;flex-shrink:0;
    }
    .badge-minus:active{background:rgba(0,0,0,.4)}
    .badge-qty{padding:0 8px;font-size:.82rem}

    .prod-ripple{
      position:absolute;inset:0;
      background:rgba(37,99,235,.12);
      opacity:0;transition:opacity .15s;
      pointer-events:none;
    }
    .prod-card:active .prod-ripple{opacity:1}

    .prod-info{padding:8px 10px 10px}
    .prod-name{font-size:.82rem;font-weight:700;line-height:1.3;margin-bottom:5px;color:var(--text)}
    .prod-price-row{display:flex;align-items:baseline;gap:3px}
    .prod-price{font-size:1.05rem;font-weight:900;color:var(--success)}
    .prod-cur{font-size:.72rem;color:var(--success);font-weight:600}

    .no-results{grid-column:1/-1;text-align:center;padding:50px 20px;color:var(--muted)}
    .no-results i{font-size:2.5rem;margin-bottom:10px;display:block;opacity:.4}

    /* ── Pay bar (always visible) ── */
    .pay-bar{
      background:#0f172a;color:#fff;
      padding:12px 14px;
      display:flex;align-items:center;gap:10px;
      flex-shrink:0;
      box-shadow:0 -3px 15px rgba(0,0,0,.2);
    }
    .pb-info{flex:1;display:flex;align-items:center;gap:10px}
    .pb-count{
      background:rgba(255,255,255,.12);
      border-radius:20px;padding:5px 13px;
      font-size:.83rem;font-weight:700;
      display:flex;align-items:center;gap:6px;
      min-width:70px;justify-content:center;
    }
    .pb-total-wrap{display:flex;align-items:baseline;gap:4px}
    .pb-total{font-size:1.55rem;font-weight:900;color:#4ade80}
    .pb-cur{font-size:.82rem;color:#86efac}
    .btn-pay{
      background:var(--success);color:#fff;
      border:none;border-radius:12px;
      padding:13px 24px;
      font-size:1rem;font-weight:800;
      cursor:pointer;
      display:flex;align-items:center;gap:8px;
      transition:.15s;
      flex-shrink:0;
    }
    .btn-pay:hover:not(:disabled){background:var(--success-dark)}
    .btn-pay:active:not(:disabled){transform:scale(.96)}
    .btn-pay:disabled{background:#374151;color:#6b7280;cursor:not-allowed}

    /* ── Scanner modal ── */
    .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:100;align-items:center;justify-content:center;padding:12px}
    .overlay.open{display:flex}
    .scan-modal{background:var(--card);border-radius:16px;padding:15px;width:min(340px,95vw);box-shadow:0 10px 35px rgba(0,0,0,.25)}
    .scan-modal-hdr{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
    .scan-modal-hdr h3{font-size:.97rem;font-weight:700;display:flex;align-items:center;gap:7px}
    #scanV{width:100%;border-radius:10px;background:#000;display:block;max-height:280px;object-fit:cover}
    #scanC{display:none}
    .btn-close-scan{background:#f1f5f9;border:none;border-radius:8px;padding:8px 16px;cursor:pointer;font-size:.85rem;font-weight:600;margin-top:9px;width:100%;display:flex;align-items:center;justify-content:center;gap:6px}

    /* ── Toast ── */
    .toast{
      position:fixed;bottom:100px;left:50%;transform:translateX(-50%);
      color:#f1f5f9;padding:10px 20px;border-radius:12px;
      font-size:.9rem;z-index:200;box-shadow:0 4px 20px rgba(0,0,0,.35);
      white-space:nowrap;display:none;align-items:center;gap:8px;
      font-weight:600;
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
  <a href="calc.php"    class="icon-btn" title="كالكوليتور"><i class="fa-solid fa-calculator"></i></a>
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

<!-- Pay bar -->
<div class="pay-bar">
  <div class="pb-info">
    <div class="pb-count">
      <i class="fa-solid fa-bag-shopping" style="font-size:.75rem"></i>
      <span id="pbQty">0</span>
    </div>
    <div class="pb-total-wrap">
      <span class="pb-total" id="pbTotal">0.00</span>
      <span class="pb-cur"><?= htmlspecialchars($currency) ?></span>
    </div>
  </div>
  <button class="btn-pay" id="btnPay" onclick="pay()" disabled>
    <i class="fa-solid fa-check"></i> ادفع
  </button>
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
const CURRENCY = <?= json_encode($currency) ?>;
let cart=[], currentCat='', allProducts=[], searchTimer=null, scanStream=null;

/* ── Helpers ── */
function esc(s){return String(s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}

function showToast(msg, ok=true){
  const t=document.getElementById('toast');
  t.innerHTML=`<i class="fa-solid ${ok?'fa-circle-check':'fa-circle-xmark'}"></i> ${msg}`;
  t.style.cssText='display:flex;position:fixed;bottom:100px;left:50%;transform:translateX(-50%);'
    +'background:'+(ok?'#166534':'#991b1b')+';color:#f1f5f9;padding:10px 20px;border-radius:12px;'
    +'font-size:.9rem;z-index:200;box-shadow:0 4px 20px rgba(0,0,0,.35);white-space:nowrap;'
    +'align-items:center;gap:8px;font-weight:600;';
  clearTimeout(t._t);
  t._t=setTimeout(()=>t.style.display='none', ok?1800:2500);
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
    const pJson=JSON.stringify(p).replace(/"/g,'&quot;');
    return `
      <div class="prod-card ${qty>0?'in-cart':''}" id="pc-${p.id}"
           onclick="addToCart(${pJson})">
        <div class="prod-img-wrap">
          ${imgHtml}
          <div class="prod-badge" id="cb-${p.id}">
            <button class="badge-minus" onclick="event.stopPropagation();decCart(${pJson})">−</button>
            <span class="badge-qty">${qty}</span>
          </div>
          <div class="prod-ripple"></div>
        </div>
        <div class="prod-info">
          <div class="prod-name">${esc(p.name)}</div>
          <div class="prod-price-row">
            <span class="prod-price">${parseFloat(p.price).toFixed(2)}</span>
            <span class="prod-cur">${esc(CURRENCY)}</span>
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
  updateBar();
  refreshCardBadge(p);
}

function decCart(p){
  const item=cart.find(i=>i.barcode===p.barcode);
  if(!item)return;
  item.qty--;
  if(item.qty<=0) cart=cart.filter(i=>i.barcode!==p.barcode);
  updateBar();
  refreshCardBadge(p);
}

function refreshCardBadge(p){
  const card=document.getElementById('pc-'+p.id);
  const badge=document.getElementById('cb-'+p.id);
  if(!card||!badge)return;
  const item=cart.find(i=>i.barcode===p.barcode);
  if(item&&item.qty>0){
    card.classList.add('in-cart');
    badge.querySelector('.badge-qty').textContent=item.qty;
  } else {
    card.classList.remove('in-cart');
  }
}

function updateBar(){
  const total=cart.reduce((s,i)=>s+i.price*i.qty,0);
  const count=cart.reduce((s,i)=>s+i.qty,0);
  document.getElementById('pbQty').textContent=count;
  document.getElementById('pbTotal').textContent=total.toFixed(2);
  document.getElementById('btnPay').disabled=!cart.length;
}

/* ── Pay ── */
async function pay(){
  if(!cart.length)return;
  const btn=document.getElementById('btnPay');
  btn.disabled=true;
  try{
    const res=await fetch('api.php?action=create_order',{
      method:'POST',headers:{'Content-Type':'application/json'},
      body:JSON.stringify({items:cart,notes:''})
    });
    const d=await res.json();
    if(!d.ok){showToast('خطأ في الحفظ',false);btn.disabled=false;return}
    /* instant reset */
    cart=[];
    allProducts.forEach(p=>refreshCardBadge(p));
    updateBar();
    showToast('تم التسجيل ✓');
  }catch(e){
    showToast('خطأ في الاتصال',false);
    btn.disabled=false;
  }
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

document.getElementById('scanOverlay').addEventListener('click',function(e){if(e.target===this)closeScanner()});

loadProducts('','');
</script>
</body>
</html>
