<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>POS - ترافيك</title>
  <style>
    :root{
      --primary:#2563eb;--primary-dark:#1d4ed8;
      --success:#16a34a;--danger:#dc2626;--warning:#d97706;
      --bg:#f1f5f9;--card:#fff;--border:#e2e8f0;
      --text:#0f172a;--muted:#64748b;
    }
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',Tahoma,sans-serif;background:var(--bg);color:var(--text);min-height:100dvh;display:flex;flex-direction:column}

    /* ── Header ── */
    header{
      background:var(--primary);color:#fff;
      padding:12px 15px;display:flex;align-items:center;gap:10px;
      position:sticky;top:0;z-index:30;box-shadow:0 2px 8px rgba(0,0,0,.2);
    }
    header a{color:rgba(255,255,255,.8);text-decoration:none;font-size:1.3rem}
    header h1{font-size:1.05rem;flex:1}
    .hbtn{background:rgba(255,255,255,.18);color:#fff;border:1px solid rgba(255,255,255,.3);padding:6px 12px;border-radius:8px;font-size:.82rem;font-weight:600;cursor:pointer;text-decoration:none}
    .hbtn:hover{background:rgba(255,255,255,.28)}

    /* ── Layout ── */
    .layout{display:flex;flex:1;gap:0;max-width:1100px;margin:0 auto;width:100%;padding:14px;gap:14px;flex-wrap:wrap}

    /* ── Left: search + results ── */
    .left{flex:1;min-width:280px;display:flex;flex-direction:column;gap:12px}

    .search-box{
      background:var(--card);border:1px solid var(--border);border-radius:12px;padding:13px;
      display:flex;flex-direction:column;gap:8px;
    }
    .search-box label{font-size:.82rem;color:var(--muted);font-weight:600}
    .search-row{display:flex;gap:7px}
    .search-row input{
      flex:1;padding:10px 12px;border:1px solid var(--border);border-radius:8px;font-size:1rem;
    }
    .search-row input:focus{outline:2px solid var(--primary);border-color:transparent}
    .btn{padding:9px 14px;border:none;border-radius:8px;font-size:.9rem;font-weight:600;cursor:pointer}
    .btn-p{background:var(--primary);color:#fff}
    .btn-p:hover{background:var(--primary-dark)}
    .btn-scan{background:#0891b2;color:#fff}
    .btn-scan:hover{background:#0e7490}

    /* product results grid */
    .prod-grid{display:flex;flex-direction:column;gap:7px;max-height:55vh;overflow-y:auto}
    .prod-card{
      background:var(--card);border:1px solid var(--border);border-radius:10px;
      padding:11px 13px;display:flex;align-items:center;gap:10px;
      cursor:pointer;transition:.12s;
    }
    .prod-card:hover{border-color:var(--primary);background:#eff6ff}
    .prod-card:active{transform:scale(.98)}
    .pc-info{flex:1}
    .pc-name{font-weight:600;font-size:.95rem}
    .pc-bc{font-size:.75rem;color:var(--muted);font-family:monospace}
    .pc-cat{font-size:.72rem}
    .badge{display:inline-block;padding:1px 8px;border-radius:10px;font-weight:700}
    .badge-غذائية{background:#dcfce7;color:#15803d}
    .badge-سجاير{background:#fef3c7;color:#b45309}
    .badge-مشروبات{background:#dbeafe;color:#1d4ed8}
    .badge-منوعات{background:#ede9fe;color:#6d28d9}
    .pc-price{font-size:1.1rem;font-weight:800;color:var(--success);white-space:nowrap}
    .pc-add{background:var(--primary);color:#fff;border:none;border-radius:8px;padding:7px 13px;font-weight:700;cursor:pointer;font-size:.9rem}
    .pc-add:hover{background:var(--primary-dark)}

    .no-results{text-align:center;padding:30px;color:var(--muted);font-size:.9rem}

    /* ── Right: cart ── */
    .right{width:320px;min-width:280px;display:flex;flex-direction:column;gap:12px}
    @media(max-width:680px){.right{width:100%}}

    .cart-box{
      background:var(--card);border:1px solid var(--border);border-radius:12px;
      display:flex;flex-direction:column;overflow:hidden;
    }
    .cart-header{
      padding:11px 14px;background:#f8fafc;border-bottom:1px solid var(--border);
      display:flex;justify-content:space-between;align-items:center;
    }
    .cart-header h2{font-size:.95rem;font-weight:700}
    .cart-count{background:var(--primary);color:#fff;border-radius:20px;padding:1px 9px;font-size:.78rem;font-weight:700}
    .cart-items{flex:1;overflow-y:auto;max-height:42vh;padding:8px}
    .cart-empty{text-align:center;padding:30px;color:var(--muted);font-size:.9rem}

    .ci{
      display:flex;align-items:center;gap:8px;
      padding:8px 6px;border-bottom:1px solid #f1f5f9;
    }
    .ci:last-child{border-bottom:none}
    .ci-name{flex:1;font-size:.88rem;font-weight:600;line-height:1.3}
    .ci-price{font-size:.82rem;color:var(--muted)}
    .qty-ctrl{display:flex;align-items:center;gap:4px}
    .qbtn{width:26px;height:26px;border:1px solid var(--border);border-radius:6px;background:#f8fafc;cursor:pointer;font-size:1rem;font-weight:700;display:flex;align-items:center;justify-content:center}
    .qbtn:hover{background:#e2e8f0}
    .qty-val{font-size:.9rem;font-weight:700;min-width:20px;text-align:center}
    .ci-total{font-weight:800;color:var(--success);font-size:.92rem;white-space:nowrap;min-width:50px;text-align:left}
    .ci-del{color:var(--danger);border:none;background:none;cursor:pointer;font-size:1.1rem;padding:2px 5px}
    .ci-del:hover{opacity:.7}

    /* cart footer */
    .cart-footer{padding:12px 14px;border-top:1px solid var(--border)}
    .cart-notes{width:100%;padding:7px 10px;border:1px solid var(--border);border-radius:8px;font-size:.88rem;resize:none;margin-bottom:10px;font-family:inherit}
    .cart-notes:focus{outline:2px solid var(--primary);border-color:transparent}
    .total-row{display:flex;justify-content:space-between;align-items:center;margin-bottom:10px}
    .total-label{font-size:.88rem;color:var(--muted)}
    .total-val{font-size:1.6rem;font-weight:900;color:var(--success)}
    .total-cur{font-size:.9rem;color:var(--success)}
    .btn-checkout{
      width:100%;padding:13px;background:var(--success);color:#fff;
      border:none;border-radius:10px;font-size:1.05rem;font-weight:800;
      cursor:pointer;transition:.15s;
    }
    .btn-checkout:hover{background:#15803d}
    .btn-checkout:disabled{background:#94a3b8;cursor:not-allowed}
    .btn-clear{width:100%;padding:8px;background:none;border:1px solid var(--border);border-radius:8px;color:var(--muted);font-size:.85rem;cursor:pointer;margin-top:7px}
    .btn-clear:hover{background:#fee2e2;border-color:var(--danger);color:var(--danger)}

    /* ── Receipt modal ── */
    .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:100;align-items:center;justify-content:center}
    .overlay.open{display:flex}
    .receipt{
      background:var(--card);border-radius:16px;padding:24px;
      width:min(400px,95vw);box-shadow:0 10px 40px rgba(0,0,0,.25);
      max-height:90vh;overflow-y:auto;
    }
    .receipt-header{text-align:center;margin-bottom:16px}
    .receipt-header h2{font-size:1.2rem;margin-bottom:4px}
    .receipt-id{font-size:.8rem;color:var(--muted)}
    .receipt-items{border-top:1px dashed var(--border);border-bottom:1px dashed var(--border);padding:10px 0;margin-bottom:12px}
    .ri{display:flex;justify-content:space-between;padding:4px 0;font-size:.88rem}
    .ri-name{flex:1}
    .ri-qty{color:var(--muted);padding:0 8px}
    .ri-total{font-weight:700}
    .receipt-total{display:flex;justify-content:space-between;font-size:1.1rem;font-weight:800;margin-bottom:16px}
    .receipt-actions{display:flex;gap:8px}
    .btn-new{flex:1;padding:11px;background:var(--primary);color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer;font-size:.95rem}
    .btn-print{flex:1;padding:11px;background:#f1f5f9;color:var(--text);border:1px solid var(--border);border-radius:8px;font-weight:700;cursor:pointer;font-size:.95rem}

    /* ── Scanner mini-modal ── */
    .scan-modal{background:var(--card);border-radius:14px;padding:16px;width:min(360px,95vw);box-shadow:0 8px 30px rgba(0,0,0,.25)}
    .scan-modal h3{margin-bottom:10px;font-size:1rem}
    #scanVideo{width:100%;border-radius:8px;background:#000;display:block}
    #scanCanvas{display:none}
    .scan-close{margin-top:10px;width:100%;padding:9px;background:#f1f5f9;border:1px solid var(--border);border-radius:8px;cursor:pointer;font-weight:600}

    .toast{
      display:none;position:fixed;bottom:20px;left:50%;transform:translateX(-50%);
      background:#1e293b;color:#f1f5f9;padding:10px 22px;border-radius:10px;
      font-size:.9rem;z-index:200;box-shadow:0 4px 16px rgba(0,0,0,.3);white-space:nowrap;
    }
  </style>
</head>
<body>

<header>
  <a href="index.php">←</a>
  <h1>🏪 نقطة البيع</h1>
  <a href="history.php" class="hbtn">📋 السجل</a>
</header>

<div class="layout">

  <!-- Left: search -->
  <div class="left">
    <div class="search-box">
      <label>ابحث بالاسم أو الباركود</label>
      <div class="search-row">
        <input type="text" id="searchIn" placeholder="اسم المنتج أو الباركود..." autofocus
               oninput="searchProducts(this.value)" onkeydown="if(event.key==='Enter')addFirstResult()"/>
        <button class="btn btn-scan" onclick="openScanner()">📷</button>
      </div>
    </div>

    <div class="prod-grid" id="prodGrid">
      <div class="no-results">ابحث عن منتج أو امسح باركود لإضافته للسلة</div>
    </div>
  </div>

  <!-- Right: cart -->
  <div class="right">
    <div class="cart-box">
      <div class="cart-header">
        <h2>🛒 السلة</h2>
        <span class="cart-count" id="cartCount">0</span>
      </div>
      <div class="cart-items" id="cartItems">
        <div class="cart-empty">السلة فاضية</div>
      </div>
      <div class="cart-footer">
        <textarea class="cart-notes" id="cartNotes" rows="2" placeholder="ملاحظات (اختياري)..."></textarea>
        <div class="total-row">
          <span class="total-label">الإجمالي</span>
          <div><span class="total-val" id="totalVal">0.00</span><span class="total-cur"> ج</span></div>
        </div>
        <button class="btn-checkout" id="checkoutBtn" onclick="checkout()" disabled>✓ تأكيد البيع</button>
        <button class="btn-clear" onclick="clearCart()">🗑 إفراغ السلة</button>
      </div>
    </div>
  </div>

</div>

<!-- Receipt modal -->
<div class="overlay" id="receiptModal">
  <div class="receipt" id="receiptContent"></div>
</div>

<!-- Scanner modal -->
<div class="overlay" id="scannerModal">
  <div class="scan-modal">
    <h3>📷 امسح الباركود</h3>
    <video id="scanVideo" autoplay playsinline muted></video>
    <canvas id="scanCanvas"></canvas>
    <button class="scan-close" onclick="closeScanner()">إغلاق</button>
  </div>
</div>

<div class="toast" id="toast"></div>

<script src="https://unpkg.com/@zxing/library@0.21.3/umd/index.min.js"></script>
<script>
let cart = [];
let searchTimer = null;
let scanStream  = null;
let scanRAF     = null;

/* ── Toast ── */
function showToast(msg, ok=true){
  const t=document.getElementById('toast');
  t.textContent=msg; t.style.background=ok?'#166534':'#991b1b';
  t.style.display='block'; setTimeout(()=>t.style.display='none',2200);
}

/* ── Search ── */
function searchProducts(q){
  clearTimeout(searchTimer);
  if(!q.trim()){document.getElementById('prodGrid').innerHTML='<div class="no-results">ابحث عن منتج أو امسح باركود لإضافته للسلة</div>';return}
  searchTimer=setTimeout(async()=>{
    const r=await fetch(`api.php?action=search&q=${encodeURIComponent(q)}`);
    const d=await r.json();
    renderResults(d.results||[]);
  },250);
}

function renderResults(products){
  const g=document.getElementById('prodGrid');
  if(!products.length){g.innerHTML='<div class="no-results">لا توجد نتائج</div>';return}
  g.innerHTML=products.map(p=>`
    <div class="prod-card" onclick="addToCart(${JSON.stringify(p).replace(/"/g,'&quot;')})">
      <div class="pc-info">
        <div class="pc-name">${esc(p.name)}</div>
        <div class="pc-bc">${esc(p.barcode)}</div>
        <span class="badge badge-${esc(p.category)} pc-cat">${esc(p.category)}</span>
      </div>
      <div class="pc-price">${parseFloat(p.price).toFixed(2)} ج</div>
      <button class="pc-add" onclick="event.stopPropagation();addToCart(${JSON.stringify(p).replace(/"/g,'&quot;')})">+</button>
    </div>`).join('');
}

function addFirstResult(){
  const first=document.querySelector('.prod-card');
  if(first) first.click();
}

/* ── Cart ── */
function addToCart(p){
  const existing=cart.find(i=>i.barcode===p.barcode);
  if(existing){existing.qty++}
  else{cart.push({...p, qty:1})}
  renderCart();
  showToast('تمت الإضافة: '+p.name);
}

function removeFromCart(barcode){cart=cart.filter(i=>i.barcode!==barcode);renderCart()}

function changeQty(barcode, delta){
  const item=cart.find(i=>i.barcode===barcode);
  if(!item) return;
  item.qty+=delta;
  if(item.qty<=0) removeFromCart(barcode);
  else renderCart();
}

function renderCart(){
  const el=document.getElementById('cartItems');
  const countEl=document.getElementById('cartCount');
  const totalEl=document.getElementById('totalVal');
  const btn=document.getElementById('checkoutBtn');

  const total=cart.reduce((s,i)=>s+i.price*i.qty,0);
  const count=cart.reduce((s,i)=>s+i.qty,0);

  countEl.textContent=count;
  totalEl.textContent=total.toFixed(2);
  btn.disabled=cart.length===0;

  if(!cart.length){el.innerHTML='<div class="cart-empty">السلة فاضية</div>';return}
  el.innerHTML=cart.map(i=>`
    <div class="ci">
      <div class="pc-info" style="flex:1">
        <div class="ci-name">${esc(i.name)}</div>
        <div class="ci-price">${parseFloat(i.price).toFixed(2)} ج × ${i.qty}</div>
      </div>
      <div class="qty-ctrl">
        <button class="qbtn" onclick="changeQty('${esc(i.barcode)}',-1)">−</button>
        <span class="qty-val">${i.qty}</span>
        <button class="qbtn" onclick="changeQty('${esc(i.barcode)}',1)">+</button>
      </div>
      <div class="ci-total">${(i.price*i.qty).toFixed(2)} ج</div>
      <button class="ci-del" onclick="removeFromCart('${esc(i.barcode)}')">✕</button>
    </div>`).join('');
}

function clearCart(){if(!cart.length)return;if(!confirm('إفراغ السلة؟'))return;cart=[];renderCart()}

/* ── Checkout ── */
async function checkout(){
  if(!cart.length)return;
  const notes=document.getElementById('cartNotes').value;
  const res=await fetch('api.php?action=create_order',{
    method:'POST',headers:{'Content-Type':'application/json'},
    body:JSON.stringify({items:cart,notes})
  });
  const data=await res.json();
  if(!data.ok){showToast('خطأ في الحفظ',false);return}
  showReceipt(data.order_id, data.total, cart, notes);
  cart=[];renderCart();
  document.getElementById('cartNotes').value='';
}

function showReceipt(id, total, items, notes){
  const modal=document.getElementById('receiptModal');
  const content=document.getElementById('receiptContent');
  content.innerHTML=`
    <div class="receipt-header">
      <div style="font-size:2rem">✅</div>
      <h2>تم البيع بنجاح!</h2>
      <div class="receipt-id">رقم الفاتورة: #${id} &nbsp;|&nbsp; ${new Date().toLocaleString('ar-EG')}</div>
    </div>
    <div class="receipt-items">
      ${items.map(i=>`
        <div class="ri">
          <span class="ri-name">${esc(i.name)}</span>
          <span class="ri-qty">× ${i.qty}</span>
          <span class="ri-total">${(i.price*i.qty).toFixed(2)} ج</span>
        </div>`).join('')}
    </div>
    ${notes?`<p style="font-size:.82rem;color:var(--muted);margin-bottom:10px">📝 ${esc(notes)}</p>`:''}
    <div class="receipt-total"><span>الإجمالي</span><span>${parseFloat(total).toFixed(2)} ج</span></div>
    <div class="receipt-actions">
      <button class="btn-print" onclick="window.print()">🖨 طباعة</button>
      <button class="btn-new" onclick="document.getElementById('receiptModal').classList.remove('open')">بيع جديد</button>
    </div>`;
  modal.classList.add('open');
}

/* ── Barcode scanner ── */
async function openScanner(){
  document.getElementById('scannerModal').classList.add('open');
  try{
    scanStream=await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'},width:{ideal:1280}}});
    const v=document.getElementById('scanVideo');
    v.srcObject=scanStream;
    const hints=new Map([[ZXing.DecodeHintType.POSSIBLE_FORMATS,[ZXing.BarcodeFormat.EAN_13,ZXing.BarcodeFormat.EAN_8,ZXing.BarcodeFormat.CODE_128,ZXing.BarcodeFormat.CODE_39,ZXing.BarcodeFormat.UPC_A,ZXing.BarcodeFormat.UPC_E]],[ZXing.DecodeHintType.TRY_HARDER,true]]);
    const reader=new ZXing.BrowserMultiFormatReader(hints);
    const c=document.getElementById('scanCanvas');
    const ctx=c.getContext('2d');
    let lastBC=null;
    function tick(){
      if(!scanStream){return}
      if(v.readyState>=2){
        c.width=v.videoWidth;c.height=v.videoHeight;ctx.drawImage(v,0,0);
        try{
          const img=ctx.getImageData(0,0,c.width,c.height);
          const lum=new ZXing.RGBLuminanceSource(img.data,c.width,c.height);
          const bmp=new ZXing.BinaryBitmap(new ZXing.HybridBinarizer(lum));
          const r=reader.decodeBitmap(bmp);
          if(r&&r.getText()!==lastBC){
            lastBC=r.getText();
            lookupAndAdd(lastBC);
          }
        }catch(_){}
      }
      scanRAF=requestAnimationFrame(tick);
    }
    v.addEventListener('loadedmetadata',()=>{scanRAF=requestAnimationFrame(tick)});
  }catch(e){showToast('تعذّر فتح الكاميرا',false);closeScanner()}
}

function closeScanner(){
  cancelAnimationFrame(scanRAF);
  if(scanStream){scanStream.getTracks().forEach(t=>t.stop());scanStream=null}
  document.getElementById('scannerModal').classList.remove('open');
}

async function lookupAndAdd(barcode){
  const r=await fetch(`api.php?action=lookup&barcode=${encodeURIComponent(barcode)}`);
  const d=await r.json();
  if(d.found){addToCart(d);closeScanner();document.getElementById('searchIn').value='';}
  else{showToast('الباركود غير موجود: '+barcode,false)}
}

function esc(s){return String(s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}

document.getElementById('scannerModal').addEventListener('click',function(e){if(e.target===this)closeScanner()});
document.getElementById('receiptModal').addEventListener('click',function(e){if(e.target===this)this.classList.remove('open')});
</script>
</body>
</html>
