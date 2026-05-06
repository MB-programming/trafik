<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>سكانر - ترافيك</title>
  <style>
    :root{--primary:#2563eb;--bg:#0f172a;--card:#1e293b;--border:#334155;--text:#f1f5f9;--muted:#94a3b8}
    *{box-sizing:border-box;margin:0;padding:0}
    body{font-family:'Segoe UI',Tahoma,sans-serif;background:var(--bg);color:var(--text);min-height:100dvh;display:flex;flex-direction:column}

    header{background:var(--card);border-bottom:1px solid var(--border);padding:12px 15px;display:flex;align-items:center;gap:11px}
    header a{color:var(--muted);text-decoration:none;font-size:1.3rem}
    header h1{font-size:1rem;flex:1}
    .mode-toggle{display:flex;background:var(--bg);border-radius:8px;overflow:hidden;border:1px solid var(--border)}
    .mbtn{padding:6px 13px;font-size:.8rem;font-weight:600;border:none;background:transparent;color:var(--muted);cursor:pointer}
    .mbtn.active{background:var(--primary);color:#fff}

    .cam-wrap{position:relative;width:100%;max-width:640px;margin:0 auto;background:#000;overflow:hidden;flex:1}
    #video{width:100%;height:100%;object-fit:cover;display:block}
    #canvas{display:none}

    /* barcode mode */
    .scan-line{position:absolute;left:8%;right:8%;height:3px;background:linear-gradient(90deg,transparent,#22d3ee,transparent);border-radius:2px;animation:sl 2s ease-in-out infinite;box-shadow:0 0 10px #22d3ee88}
    @keyframes sl{0%,100%{top:20%}50%{top:78%}}
    .corners{position:absolute;inset:0;pointer-events:none}
    .c{position:absolute;width:26px;height:26px;border-color:#22d3ee;border-style:solid}
    .c.tl{top:18%;left:7%;border-width:3px 0 0 3px;border-radius:4px 0 0 0}
    .c.tr{top:18%;right:7%;border-width:3px 3px 0 0;border-radius:0 4px 0 0}
    .c.bl{bottom:18%;left:7%;border-width:0 0 3px 3px;border-radius:0 0 0 4px}
    .c.br{bottom:18%;right:7%;border-width:0 3px 3px 0;border-radius:0 0 4px 0}

    /* image mode */
    .crosshair{position:absolute;inset:0;display:flex;align-items:center;justify-content:center;pointer-events:none}
    .ring{width:160px;height:160px;border:3px solid rgba(34,211,238,.8);border-radius:50%;box-shadow:0 0 0 9999px rgba(0,0,0,.35);animation:rp 2s ease-in-out infinite}
    @keyframes rp{0%,100%{transform:scale(1)}50%{transform:scale(1.06)}}

    .cap-btn{position:absolute;bottom:16px;left:50%;transform:translateX(-50%);width:62px;height:62px;border-radius:50%;background:#fff;border:4px solid rgba(255,255,255,.4);cursor:pointer;display:none;box-shadow:0 4px 18px rgba(0,0,0,.4)}
    .cap-btn:active{transform:translateX(-50%) scale(.92)}
    .cap-inner{width:100%;height:100%;border-radius:50%;background:#fff;transition:.1s}
    .cap-btn:active .cap-inner{background:#dbeafe}

    .proc{position:absolute;bottom:18px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.7);color:#22d3ee;padding:8px 18px;border-radius:20px;font-size:.85rem;display:none;white-space:nowrap}
    .cam-st{position:absolute;top:10px;right:10px;background:rgba(0,0,0,.55);color:#e2e8f0;font-size:.73rem;padding:3px 9px;border-radius:20px}

    /* result */
    .result{background:var(--card);border-top:1px solid var(--border);padding:16px;min-height:145px;display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:6px}
    .r-idle{color:var(--muted);font-size:.95rem}
    .r-price{font-size:2.5rem;font-weight:900;color:#4ade80;line-height:1}
    .r-cur{font-size:1.2rem;color:#4ade80;font-weight:700}
    .r-name{font-size:1.1rem;font-weight:600}
    .r-bc{font-size:.8rem;color:var(--muted);font-family:monospace}
    .r-warn{color:#d97706}
    .r-ai{font-size:.75rem;color:var(--muted)}
    .cpill{display:inline-block;padding:3px 11px;border-radius:12px;font-size:.78rem;font-weight:700}
    .cp-غذائية{background:#166534;color:#bbf7d0}
    .cp-سجاير{background:#78350f;color:#fde68a}
    .cp-مشروبات{background:#1e3a8a;color:#bfdbfe}
    .cp-منوعات{background:#4c1d95;color:#ddd6fe}
    .pop{animation:pop .3s ease}
    @keyframes pop{0%{transform:scale(.8);opacity:.4}70%{transform:scale(1.06)}100%{transform:scale(1);opacity:1}}

    /* manual */
    .manual{background:var(--card);border-top:1px solid var(--border);padding:10px 13px;display:flex;gap:7px}
    .manual input{flex:1;padding:9px 11px;border-radius:8px;border:1px solid var(--border);background:var(--bg);color:var(--text);font-size:.95rem}
    .manual input:focus{outline:2px solid var(--primary);border-color:transparent}
    .btn-lk{padding:9px 15px;background:var(--primary);color:#fff;border:none;border-radius:8px;font-weight:700;cursor:pointer}
  </style>
</head>
<body>

<header>
  <a href="index.php">←</a>
  <h1>📷 سكانر الأسعار</h1>
  <div class="mode-toggle">
    <button class="mbtn active" id="btnBC" onclick="setMode('barcode')">باركود</button>
    <button class="mbtn"        id="btnIM" onclick="setMode('image')">صورة AI</button>
  </div>
</header>

<div class="cam-wrap">
  <video id="video" autoplay playsinline muted></video>
  <canvas id="canvas"></canvas>

  <div id="bcUI">
    <div class="corners"><div class="c tl"></div><div class="c tr"></div><div class="c bl"></div><div class="c br"></div></div>
    <div class="scan-line"></div>
  </div>

  <div class="crosshair" id="imUI" style="display:none"><div class="ring"></div></div>
  <button class="cap-btn" id="capBtn" onclick="captureAI()"><div class="cap-inner"></div></button>
  <div class="proc" id="proc">⏳ جارٍ التعرف...</div>
  <div class="cam-st" id="camSt">جارٍ التحميل...</div>
</div>

<div class="result" id="result"><p class="r-idle">وجّه الكاميرا على باركود المنتج</p></div>

<div class="manual">
  <input type="text" id="manIn" placeholder="أو أدخل الباركود يدويًا..." inputmode="numeric"/>
  <button class="btn-lk" onclick="manLookup()">بحث</button>
</div>

<script src="https://unpkg.com/@zxing/library@0.21.3/umd/index.min.js"></script>
<script>
const video  = document.getElementById('video');
const canvas = document.getElementById('canvas');
const result = document.getElementById('result');
const proc   = document.getElementById('proc');
const capBtn = document.getElementById('capBtn');

let mode = 'barcode', lastBC = null, bcCD = false;

function esc(s){return String(s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}

function showResult(data){
  result.classList.remove('pop'); void result.offsetWidth; result.classList.add('pop');
  if(data.found){
    result.innerHTML=`
      <div class="r-name">${esc(data.name)}</div>
      <div><span class="r-price">${parseFloat(data.price).toFixed(2)}</span><span class="r-cur"> ج</span></div>
      <span class="cpill cp-${esc(data.category)}">${esc(data.category)}</span>
      ${data.barcode?`<div class="r-bc">🔍 ${esc(data.barcode)}</div>`:''}
      ${data.identified_as?`<div class="r-ai">AI: ${esc(data.identified_as)}</div>`:''}`;
  } else {
    result.innerHTML=`
      <p class="r-warn">⚠️ المنتج غير موجود</p>
      ${data.identified_as?`<p class="r-ai">AI قال: ${esc(data.identified_as)}</p>`:''}
      ${data.barcode?`<p class="r-bc">${esc(data.barcode)}</p>`:''}
      <p style="font-size:.8rem;margin-top:5px"><a href="index.php" style="color:#60a5fa">أضفه هنا ←</a></p>`;
  }
}

async function lookupBC(bc){
  if(bcCD||bc===lastBC) return;
  bcCD=true; lastBC=bc;
  const r=await fetch(`api.php?action=lookup&barcode=${encodeURIComponent(bc)}`);
  showResult(await r.json());
  setTimeout(()=>{bcCD=false},3000);
}

async function captureAI(){
  capBtn.style.display='none'; proc.style.display='block';
  result.innerHTML='<p class="r-idle">⏳ جارٍ التعرف...</p>';
  canvas.width=video.videoWidth; canvas.height=video.videoHeight;
  canvas.getContext('2d').drawImage(video,0,0);
  const b64=canvas.toDataURL('image/jpeg',.8).split(',')[1];
  try{
    const r=await fetch('api.php?action=identify',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({image:b64})});
    const d=await r.json();
    if(d.error==='no_api_key'){result.innerHTML='<p class="r-warn">⚠️ ANTHROPIC_API_KEY غير مضبوط في config.php</p>'}
    else showResult(d);
  }catch(e){result.innerHTML='<p class="r-warn">خطأ، حاول مرة أخرى</p>'}
  proc.style.display='none'; capBtn.style.display='block';
}

async function manLookup(){
  const v=document.getElementById('manIn').value.trim();
  if(!v) return;
  lastBC=null;
  const r=await fetch(`api.php?action=lookup&barcode=${encodeURIComponent(v)}`);
  showResult(await r.json());
}
document.getElementById('manIn').addEventListener('keydown',e=>{if(e.key==='Enter')manLookup()});

function setMode(m){
  mode=m;
  document.getElementById('btnBC').classList.toggle('active',m==='barcode');
  document.getElementById('btnIM').classList.toggle('active',m==='image');
  document.getElementById('bcUI').style.display=m==='barcode'?'block':'none';
  document.getElementById('imUI').style.display=m==='image'?'flex':'none';
  capBtn.style.display=m==='image'?'block':'none';
  document.getElementById('manIn').placeholder=m==='barcode'?'أو أدخل الباركود يدويًا...':'اسم المنتج للبحث...';
  lastBC=null;
  result.innerHTML=`<p class="r-idle">${m==='barcode'?'وجّه الكاميرا على باركود المنتج':'اضغط الزر لالتقاط صورة المنتج'}</p>`;
}

async function startCamera(){
  try{
    const s=await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'},width:{ideal:1280}}});
    video.srcObject=s;
    document.getElementById('camSt').textContent='الكاميرا شغالة ✓';
    startBarcodeDecoder();
  }catch(e){
    document.getElementById('camSt').textContent='تعذّر فتح الكاميرا';
    result.innerHTML='<p class="r-warn">⚠️ اسمح بالكاميرا أو استخدم الإدخال اليدوي</p>';
  }
}

function startBarcodeDecoder(){
  const hints=new Map([[ZXing.DecodeHintType.POSSIBLE_FORMATS,[ZXing.BarcodeFormat.EAN_13,ZXing.BarcodeFormat.EAN_8,ZXing.BarcodeFormat.CODE_128,ZXing.BarcodeFormat.CODE_39,ZXing.BarcodeFormat.UPC_A,ZXing.BarcodeFormat.UPC_E,ZXing.BarcodeFormat.QR_CODE]],[ZXing.DecodeHintType.TRY_HARDER,true]]);
  const reader=new ZXing.BrowserMultiFormatReader(hints);
  const ctx=canvas.getContext('2d');
  function tick(){
    if(mode!=='barcode'){requestAnimationFrame(tick);return}
    if(video.readyState>=2){
      canvas.width=video.videoWidth;canvas.height=video.videoHeight;
      ctx.drawImage(video,0,0);
      try{
        const img=ctx.getImageData(0,0,canvas.width,canvas.height);
        const lum=new ZXing.RGBLuminanceSource(img.data,canvas.width,canvas.height);
        const bmp=new ZXing.BinaryBitmap(new ZXing.HybridBinarizer(lum));
        const r=reader.decodeBitmap(bmp);
        if(r) lookupBC(r.getText());
      }catch(_){}
    }
    requestAnimationFrame(tick);
  }
  video.addEventListener('loadedmetadata',()=>requestAnimationFrame(tick));
}

startCamera();
</script>
</body>
</html>
