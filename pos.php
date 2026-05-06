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
  <title>POS - ترافيك</title>
  <style>
    :root{--primary:#2563eb;--primary-dark:#1d4ed8;--success:#16a34a;--danger:#dc2626;--bg:#f1f5f9;--card:#fff;--border:#e2e8f0;--text:#0f172a;--muted:#64748b}
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',Tahoma,sans-serif;background:var(--bg);color:var(--text);height:100dvh;display:flex;flex-direction:column;overflow:hidden}

    /* ── Header ── */
    header{background:var(--primary);color:#fff;padding:10px 13px;display:flex;align-items:center;gap:9px;box-shadow:0 2px 8px rgba(0,0,0,.2);flex-shrink:0}
    header a{color:rgba(255,255,255,.8);text-decoration:none;font-size:1.2rem}
    header h1{font-size:1rem;flex:1}
    .hbtn{background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.3);padding:5px 10px;border-radius:7px;font-size:.78rem;font-weight:600;cursor:pointer;text-decoration:none}
    .hbtn:hover{background:rgba(255,255,255,.28)}

    /* ── Search bar ── */
    .searchbar{background:#fff;border-bottom:1px solid var(--border);padding:9px 12px;display:flex;gap:7px;flex-shrink:0}
    .searchbar input{flex:1;padding:9px 12px;border:2px solid var(--border);border-radius:9px;font-size:.97rem}
    .searchbar input:focus{border-color:var(--primary);outline:none}
    .btn-scan-mini{padding:9px 13px;background:#0891b2;color:#fff;border:none;border-radius:9px;font-size:1rem;cursor:pointer}

    /* ── Category tabs ── */
    .cat-strip{display:flex;gap:6px;padding:9px 12px;overflow-x:auto;background:#fff;border-bottom:1px solid var(--border);flex-shrink:0;-webkit-overflow-scrolling:touch}
    .cat-strip::-webkit-scrollbar{display:none}
    .ctab{padding:6px 14px;border-radius:20px;font-size:.82rem;font-weight:700;border:2px solid var(--border);background:#f8fafc;color:var(--muted);cursor:pointer;white-space:nowrap;transition:.12s}
    .ctab.active{background:var(--primary);color:#fff;border-color:var(--primary)}
    .ctab:hover:not(.active){background:#eff6ff;border-color:#bfdbfe;color:var(--primary)}

    /* ── Product grid ── */
    .prod-area{flex:1;overflow-y:auto;padding:11px;-webkit-overflow-scrolling:touch}
    .prod-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px}

    .prod-card{
      background:var(--card);border:2px solid var(--border);border-radius:14px;
      overflow:hidden;cursor:pointer;transition:.12s;position:relative;
      display:flex;flex-direction:column;
    }
    .prod-card:hover{border-color:var(--primary);box-shadow:0 4px 16px rgba(37,99,235,.15)}
    .prod-card:active{transform:scale(.96)}
    .prod-card.in-cart{border-color:#16a34a}

    .prod-img{width:100%;aspect-ratio:1;object-fit:cover;background:#f1f5f9}
    .prod-noimg{width:100%;aspect-ratio:1;background:linear-gradient(135deg,#f1f5f9,#e2e8f0);display:flex;align-items:center;justify-content:center;font-size:2.4rem}
    .prod-body{padding:8px;flex:1;display:flex;flex-direction:column;justify-content:space-between}
    .prod-name{font-size:.82rem;font-weight:700;line-height:1.3;margin-bottom:4px}
    .prod-price{font-size:1rem;font-weight:900;color:var(--success)}
    .prod-currency{font-size:.72rem;color:var(--success)}
    .cart-badge{
      position:absolute;top:6px;right:6px;
      background:var(--primary);color:#fff;border-radius:12px;
      padding:2px 7px;font-size:.72rem;font-weight:800;
      display:none;
    }
    .prod-card.in-cart .cart-badge{display:block}

    .no-results{grid-column:1/-1;text-align:center;padding:50px;color:var(--muted)}

    /* ── Cart bottom bar ── */
    .cart-bar{
      background:var(--primary);color:#fff;padding:12px 16px;
      display:flex;align-items:center;justify-content:space-between;
      cursor:pointer;flex-shrink:0;
      box-shadow:0 -3px 15px rgba(0,0,0,.15);
    }
    .cart-bar-left{display:flex;align-items:center;gap:8px}
    .cart-qty-badge{background:rgba(255,255,255,.25);border-radius:20px;padding:3px 10px;font-size:.85rem;font-weight:700}
    .cart-bar-total{font-size:1.2rem;font-weight:900}
    .cart-bar-cur{font-size:.8rem;opacity:.8}
    .cart-bar-arrow{font-size:1.2rem;opacity:.8;transition:.2s}
    .cart-bar-arrow.up{transform:rotate(180deg)}

    /* ── Cart sheet ── */
    .cart-sheet{
      position:fixed;inset:0;z-index:50;
      display:flex;flex-direction:column;justify-content:flex-end;
      pointer-events:none;
    }
    .cart-backdrop{position:absolute;inset:0;background:rgba(0,0,0,.45);opacity:0;transition:opacity .3s;pointer-events:none}
    .cart-panel{
      background:var(--card);border-radius:20px 20px 0 0;
      max-height:82dvh;display:flex;flex-direction:column;
      transform:translateY(100%);transition:transform .35s cubic-bezier(.22,1,.36,1);
      pointer-events:none;
    }
    .cart-sheet.open .cart-backdrop{opacity:1;pointer-events:all}
    .cart-sheet.open .cart-panel{transform:translateY(0);pointer-events:all}
    .cart-sheet.open{pointer-events:all}

    .cart-handle{width:40px;height:4px;background:#e2e8f0;border-radius:2px;margin:12px auto 0}
    .cart-header{padding:12px 16px;display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid var(--border)}
    .cart-header h2{font-size:1rem;font-weight:700}
    .cart-items{flex:1;overflow-y:auto;padding:8px 12px}
    .cart-empty-msg{text-align:center;padding:30px;color:var(--muted)}

    .ci{display:flex;align-items:center;gap:8px;padding:9px 0;border-bottom:1px solid #f1f5f9}
    .ci:last-child{border-bottom:none}
    .ci-thumb{width:40px;height:40px;border-radius:8px;object-fit:cover;background:#f1f5f9;border:1px solid var(--border);flex-shrink:0}
    .ci-thumb-ph{width:40px;height:40px;border-radius:8px;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-size:1.2rem;flex-shrink:0}
    .ci-info{flex:1;min-width:0}
    .ci-name{font-size:.88rem;font-weight:700;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
    .ci-sub{font-size:.75rem;color:var(--muted)}
    .qty-ctrl{display:flex;align-items:center;gap:5px}
    .qbtn{width:28px;height:28px;border:1.5px solid var(--border);border-radius:7px;background:#f8fafc;cursor:pointer;font-size:1rem;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0}
    .qbtn:hover{background:#e2e8f0}
    .qty-n{font-size:.9rem;font-weight:800;min-width:22px;text-align:center}
    .ci-total{font-weight:800;color:var(--success);font-size:.92rem;white-space:nowrap;min-width:52px;text-align:left}
    .ci-del{color:var(--danger);border:none;background:none;cursor:pointer;font-size:1.1rem;padding:3px;flex-shrink:0}

    .cart-footer{padding:12px 16px;border-top:1px solid var(--border)}
    .cart-notes{width:100%;padding:8px 11px;border:1px solid var(--border);border-radius:8px;font-size:.88rem;resize:none;font-family:inherit;margin-bottom:10px}
    .cart-notes:focus{outline:2px solid var(--primary);border-color:transparent}
    .total-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:11px}
    .total-lbl{font-size:.88rem;color:var(--muted)}
    .total-amt{font-size:1.6rem;font-weight:900;color:var(--success)}
    .btn-checkout{width:100%;padding:13px;background:var(--success);color:#fff;border:none;border-radius:11px;font-size:1.05rem;font-weight:800;cursor:pointer}
    .btn-checkout:hover{background:#15803d}
    .btn-checkout:disabled{background:#94a3b8;cursor:not-allowed}
    .btn-clear{width:100%;margin-top:7px;padding:8px;background:none;border:1px solid var(--border);border-radius:8px;color:var(--muted);font-size:.82rem;cursor:pointer}
    .btn-clear:hover{background:#fee2e2;border-color:var(--danger);color:var(--danger)}

    /* ── Receipt modal ── */
    .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:100;align-items:center;justify-content:center}
    .overlay.open{display:flex}
    .receipt{background:var(--card);border-radius:16px;padding:22px;width:min(390px,95vw);box-shadow:0 10px 40px rgba(0,0,0,.25);max-height:90dvh;overflow-y:auto}
    .receipt-hd{text-align:center;margin-bottom:14px}
    .receipt-hd h2{font-size:1.15rem;margin-top:6px}
    .receipt-hd small{color:var(--muted);font-size:.78rem}
    .r-items{border-top:1px dashed var(--border);border-bottom:1px dashed var(--border);padding:10px 0;margin-bottom:10px}
    .ri{display:flex;justify-content:space-between;padding:4px 0;font-size:.88rem}
    .ri-n{flex:1} .ri-q{color:var(--muted);padding:0 8px} .ri-t{font-weight:700}
    .r-total{display:flex;justify-content:space-between;font-size:1.1rem;font-weight:900;margin-bottom:14px}
    .r-acts{display:flex;gap:8px}
    .btn-new{flex:1;padding:11px;background:var(--primary);color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer}
    .btn-print{flex:1;padding:11px;background:#f1f5f9;color:var(--text);border:1px solid var(--border);border-radius:8px;font-weight:700;cursor:pointer}

    /* ── Scanner mini modal ── */
    .scan-modal{background:var(--card);border-radius:14px;padding:14px;width:min(340px,95vw)}
    .scan-modal h3{margin-bottom:9px;font-size:.97rem}
    #scanV{width:100%;border-radius:8px;background:#000;display:block;max-height:280px;object-fit:cover}
    #scanC{display:none}
    .scan-close{margin-top:9px;width:100%;padding:9px;background:#f1f5f9;border:1px solid var(--border);border-radius:8px;cursor:pointer;font-weight:600}

    .toast{display:none;position:fixed;bottom:90px;left:50%;transform:translateX(-50%);background:#1e293b;color:#f1f5f9;padding:9px 20px;border-radius:10px;font-size:.88rem;z-index:200;box-shadow:0 4px 16px rgba(0,0,0,.3);white-space:nowrap}
  </style>
</head>
<body>

<header>
  <a href="index.php">⚙️</a>
  <h1>🏪 نقطة البيع</h1>
  <a href="history.php" class="hbtn">📋 السجل</a>
  <a href="logout.php"  class="hbtn">🚪</a>
</header>

<!-- Search -->
<div class="searchbar">
  <input type="text" id="searchIn" placeholder="ابحث بالاسم أو الباركود..." oninput="search(this.value)" onkeydown="if(event.key==='Enter')addFirst()"/>
  <button class="btn-scan-mini" onclick="openScanner()" title="مسح باركود">📷</button>
</div>

<!-- Category tabs -->
<div class="cat-strip">
  <button class="ctab active" data-cat="" onclick="setcat(this,'')">الكل</button>
  <?php foreach($cats as $c): ?>
  <button class="ctab" data-cat="<?= htmlspecialchars($c['name']) ?>" onclick="setcat(this,<?= json_encode($c['name'],JSON_UNESCAPED_UNICODE) ?>)"><?= $c['icon'].' '.$c['name'] ?></button>
  <?php endforeach ?>
</div>

<!-- Product grid -->
<div class="prod-area">
  <div class="prod-grid" id="prodGrid">
    <div class="no-results" style="grid-column:1/-1;padding:40px;color:var(--muted)">⏳ جارٍ التحميل...</div>
  </div>
</div>

<!-- Cart bottom bar -->
<div class="cart-bar" id="cartBar" onclick="toggleCart()">
  <div class="cart-bar-left">
    <span>🛒</span>
    <span class="cart-qty-badge" id="barQty">0 قطعة</span>
  </div>
  <div style="display:flex;align-items:center;gap:8px">
    <span class="cart-bar-total" id="barTotal">0.00</span>
    <span class="cart-bar-cur">ج</span>
    <span class="cart-bar-arrow" id="barArrow">▲</span>
  </div>
</div>

<!-- Cart sheet -->
<div class="cart-sheet" id="cartSheet">
  <div class="cart-backdrop" onclick="toggleCart()"></div>
  <div class="cart-panel">
    <div class="cart-handle"></div>
    <div class="cart-header">
      <h2>🛒 السلة</h2>
      <button style="background:none;border:none;font-size:1.3rem;cursor:pointer;color:var(--muted)" onclick="toggleCart()">✕</button>
    </div>
    <div class="cart-items" id="cartItems"><div class="cart-empty-msg">السلة فاضية</div></div>
    <div class="cart-footer">
      <textarea class="cart-notes" id="cartNotes" rows="2" placeholder="ملاحظات (اختياري)..."></textarea>
      <div class="total-row">
        <span class="total-lbl">الإجمالي</span>
        <div><span class="total-amt" id="totalAmt">0.00</span> ج</div>
      </div>
      <button class="btn-checkout" id="checkoutBtn" onclick="checkout()" disabled>✓ تأكيد البيع</button>
      <button class="btn-clear" onclick="clearCart()">🗑 إفراغ السلة</button>
    </div>
  </div>
</div>

<!-- Receipt -->
<div class="overlay" id="receiptOverlay">
  <div class="receipt" id="receiptContent"></div>
</div>

<!-- Scanner -->
<div class="overlay" id="scanOverlay">
  <div class="scan-modal">
    <h3>📷 امسح الباركود</h3>
    <video id="scanV" autoplay playsinline muted></video>
    <canvas id="scanC"></canvas>
    <button class="scan-close" onclick="closeScanner()">إغلاق</button>
  </div>
</div>

<div class="toast" id="toast"></div>

<script src="https://unpkg.com/@zxing/library@0.21.3/umd/index.min.js"></script>
<script>
let cart=[], currentCat='', scanStream=null, allProducts=[], searchTimer=null;

/* ── Helpers ── */
function esc(s){return String(s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}
function showToast(m,ok=true){const t=document.getElementById('toast');t.textContent=m;t.style.background=ok?'#166534':'#991b1b';t.style.display='block';setTimeout(()=>t.style.display='none',2200)}

/* ── Load products ── */
async function loadProducts(q='',cat=''){
  const url='api.php?action=search&q='+encodeURIComponent(q)+'&cat='+encodeURIComponent(cat);
  const r=await fetch(url); const d=await r.json();
  allProducts=d.results||[];
  renderGrid(allProducts);
}

function renderGrid(products){
  const g=document.getElementById('prodGrid');
  if(!products.length){g.innerHTML='<div class="no-results">لا توجد منتجات</div>';return}
  g.innerHTML=products.map(p=>{
    const inCart=cart.find(i=>i.barcode===p.barcode);
    const qty=inCart?inCart.qty:0;
    return `<div class="prod-card ${qty>0?'in-cart':''}" id="pc-${p.id}" onclick="addToCart(${JSON.stringify(p).replace(/"/g,'&quot;')})">
      <span class="cart-badge" id="cb-${p.id}">${qty}</span>
      ${p.image_path?`<img class="prod-img" src="${esc(p.image_path)}" loading="lazy"/>`:`<div class="prod-noimg">📦</div>`}
      <div class="prod-body">
        <div class="prod-name">${esc(p.name)}</div>
        <div><span class="prod-price">${parseFloat(p.price).toFixed(2)}</span><span class="prod-currency"> ج</span></div>
      </div>
    </div>`;
  }).join('');
}

/* ── Search ── */
function search(q){
  clearTimeout(searchTimer);
  searchTimer=setTimeout(()=>loadProducts(q,currentCat),220);
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
  if(ex)ex.qty++; else cart.push({...p,qty:1});
  renderCart(); updateCardBadge(p); showToast(p.name+' ✓');
}

function updateCardBadge(p){
  const card=document.getElementById('pc-'+p.id);
  const badge=document.getElementById('cb-'+p.id);
  if(!card||!badge)return;
  const item=cart.find(i=>i.barcode===p.barcode);
  if(item&&item.qty>0){card.classList.add('in-cart');badge.textContent=item.qty}
  else{card.classList.remove('in-cart')}
}

function changeQty(barcode,delta){
  const item=cart.find(i=>i.barcode===barcode);
  if(!item)return;
  item.qty+=delta;
  if(item.qty<=0){cart=cart.filter(i=>i.barcode!==barcode)}
  renderCart();
  const prod=allProducts.find(p=>p.barcode===barcode);
  if(prod)updateCardBadge(prod);
}

function removeItem(barcode){
  const prod=allProducts.find(p=>p.barcode===barcode);
  cart=cart.filter(i=>i.barcode!==barcode);
  renderCart();
  if(prod)updateCardBadge(prod);
}

function clearCart(){
  if(!cart.length)return;
  if(!confirm('إفراغ السلة؟'))return;
  cart=[]; renderCart();
  allProducts.forEach(p=>updateCardBadge(p));
}

function renderCart(){
  const el=document.getElementById('cartItems');
  const total=cart.reduce((s,i)=>s+i.price*i.qty,0);
  const count=cart.reduce((s,i)=>s+i.qty,0);
  document.getElementById('barQty').textContent=count+' قطعة';
  document.getElementById('barTotal').textContent=total.toFixed(2);
  document.getElementById('totalAmt').textContent=total.toFixed(2);
  document.getElementById('checkoutBtn').disabled=cart.length===0;

  if(!cart.length){el.innerHTML='<div class="cart-empty-msg">السلة فاضية</div>';return}
  el.innerHTML=cart.map(i=>`
    <div class="ci">
      ${i.image_path?`<img class="ci-thumb" src="${esc(i.image_path)}"/>`:`<div class="ci-thumb-ph">📦</div>`}
      <div class="ci-info">
        <div class="ci-name">${esc(i.name)}</div>
        <div class="ci-sub">${parseFloat(i.price).toFixed(2)} ج × ${i.qty}</div>
      </div>
      <div class="qty-ctrl">
        <button class="qbtn" onclick="changeQty('${esc(i.barcode)}',-1)">−</button>
        <span class="qty-n">${i.qty}</span>
        <button class="qbtn" onclick="changeQty('${esc(i.barcode)}',1)">+</button>
      </div>
      <div class="ci-total">${(i.price*i.qty).toFixed(2)} ج</div>
      <button class="ci-del" onclick="removeItem('${esc(i.barcode)}')">✕</button>
    </div>`).join('');
}

/* ── Cart sheet toggle ── */
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
  const res=await fetch('api.php?action=create_order',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({items:cart,notes})});
  const d=await res.json();
  if(!d.ok){showToast('خطأ في الحفظ',false);return}
  const savedCart=[...cart];
  cart=[]; renderCart();
  document.getElementById('cartNotes').value='';
  document.getElementById('cartSheet').classList.remove('open');
  allProducts.forEach(p=>updateCardBadge(p));
  showReceipt(d.order_id,d.total,savedCart,notes);
}

function showReceipt(id,total,items,notes){
  document.getElementById('receiptContent').innerHTML=`
    <div class="receipt-hd"><div style="font-size:2.5rem">✅</div><h2>تم البيع!</h2><small>فاتورة #${id} — ${new Date().toLocaleString('ar-EG')}</small></div>
    <div class="r-items">${items.map(i=>`<div class="ri"><span class="ri-n">${esc(i.name)}</span><span class="ri-q">×${i.qty}</span><span class="ri-t">${(i.price*i.qty).toFixed(2)} ج</span></div>`).join('')}</div>
    ${notes?`<p style="font-size:.8rem;color:var(--muted);margin-bottom:8px">📝 ${esc(notes)}</p>`:''}
    <div class="r-total"><span>الإجمالي</span><span>${parseFloat(total).toFixed(2)} ج</span></div>
    <div class="r-acts">
      <button class="btn-print" onclick="window.print()">🖨 طباعة</button>
      <button class="btn-new" onclick="document.getElementById('receiptOverlay').classList.remove('open')">بيع جديد</button>
    </div>`;
  document.getElementById('receiptOverlay').classList.add('open');
}

/* ── Barcode scanner ── */
async function openScanner(){
  document.getElementById('scanOverlay').classList.add('open');
  try{
    scanStream=await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'},width:{ideal:1280}}});
    const v=document.getElementById('scanV'); v.srcObject=scanStream;
    const hints=new Map([[ZXing.DecodeHintType.POSSIBLE_FORMATS,[ZXing.BarcodeFormat.EAN_13,ZXing.BarcodeFormat.EAN_8,ZXing.BarcodeFormat.CODE_128,ZXing.BarcodeFormat.CODE_39,ZXing.BarcodeFormat.UPC_A,ZXing.BarcodeFormat.UPC_E]],[ZXing.DecodeHintType.TRY_HARDER,true]]);
    const reader=new ZXing.BrowserMultiFormatReader(hints);
    const c=document.getElementById('scanC'); const ctx=c.getContext('2d');
    let lastBC=null;
    function tick(){
      if(!scanStream)return;
      if(v.readyState>=2){c.width=v.videoWidth;c.height=v.videoHeight;ctx.drawImage(v,0,0);
        try{const img=ctx.getImageData(0,0,c.width,c.height);const lum=new ZXing.RGBLuminanceSource(img.data,c.width,c.height);const bmp=new ZXing.BinaryBitmap(new ZXing.HybridBinarizer(lum));const r=reader.decodeBitmap(bmp);
          if(r&&r.getText()!==lastBC){lastBC=r.getText();lookupAndAdd(lastBC)}}catch(_){}
      }
      requestAnimationFrame(tick);
    }
    v.addEventListener('loadedmetadata',()=>requestAnimationFrame(tick));
  }catch(e){showToast('تعذّر فتح الكاميرا',false);closeScanner()}
}

function closeScanner(){if(scanStream){scanStream.getTracks().forEach(t=>t.stop());scanStream=null}document.getElementById('scanOverlay').classList.remove('open')}

async function lookupAndAdd(bc){
  const r=await fetch('api.php?action=lookup&barcode='+encodeURIComponent(bc));
  const d=await r.json();
  if(d.found){addToCart(d);closeScanner()}
  else showToast('الباركود غير موجود',false);
}

document.getElementById('scanOverlay').addEventListener('click',function(e){if(e.target===this)closeScanner()});
document.getElementById('receiptOverlay').addEventListener('click',function(e){if(e.target===this)this.classList.remove('open')});

// init
loadProducts('','');
</script>
</body>
</html>
