<?php require_once __DIR__.'/config.php'; ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"/>
  <title>Fast Scan - ترافيك</title>
  <style>
    *{box-sizing:border-box;margin:0;padding:0}
    html,body{width:100%;height:100%;background:#000;font-family:'Segoe UI',Tahoma,sans-serif;overflow:hidden}

    #video{position:fixed;inset:0;width:100%;height:100%;object-fit:cover;z-index:0}
    #canvas{display:none}

    /* top bar */
    #topBar{
      position:fixed;top:0;left:0;right:0;z-index:10;
      display:flex;align-items:center;gap:10px;
      padding:env(safe-area-inset-top,10px) 14px 10px;
      background:linear-gradient(to bottom,rgba(0,0,0,.75),transparent);
    }
    #topBar a{color:#fff;text-decoration:none;font-size:1.3rem;padding:5px}
    #topBar h1{color:#fff;font-size:1rem;font-weight:700;flex:1;text-shadow:0 1px 4px rgba(0,0,0,.6)}
    #scanToggle{
      background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.35);
      color:#fff;padding:6px 13px;border-radius:20px;font-size:.8rem;font-weight:700;cursor:pointer;
    }
    #scanToggle.on{background:rgba(34,197,94,.25);border-color:rgba(34,197,94,.5)}

    /* scan ring */
    #ring{
      position:fixed;top:50%;left:50%;
      transform:translate(-50%,-55%);
      width:190px;height:190px;
      border:3px solid rgba(34,211,238,.7);border-radius:50%;
      pointer-events:none;z-index:5;
    }
    #ring.scanning{animation:rp 1.6s ease-in-out infinite}
    #ring.found{border-color:#4ade80;box-shadow:0 0 0 5px rgba(74,222,128,.2);animation:none}
    @keyframes rp{0%,100%{opacity:.6;transform:translate(-50%,-55%) scale(1)}50%{opacity:1;transform:translate(-50%,-55%) scale(1.06)}}

    /* status label */
    #statusLbl{
      position:fixed;top:50%;left:50%;
      transform:translate(-50%,52px);
      color:rgba(255,255,255,.85);font-size:.8rem;font-weight:600;
      text-align:center;text-shadow:0 1px 4px rgba(0,0,0,.8);
      z-index:5;pointer-events:none;transition:opacity .3s;
    }

    /* progress bar */
    #pb{position:fixed;bottom:0;left:0;height:3px;background:#22d3ee;width:0%;z-index:10;transition:width linear}

    /* result overlay */
    #overlay{
      position:fixed;bottom:0;left:0;right:0;z-index:20;
      padding:0 0 env(safe-area-inset-bottom,0);
      transform:translateY(100%);
      transition:transform .35s cubic-bezier(.22,1,.36,1);
    }
    #overlay.show{transform:translateY(0)}

    .rcard{
      margin:0 11px 11px;border-radius:18px;padding:16px 18px;
      backdrop-filter:blur(18px);-webkit-backdrop-filter:blur(18px);
    }
    .rcard.ok{background:rgba(15,23,42,.88);border:1px solid rgba(74,222,128,.35)}
    .rcard.no{background:rgba(15,23,42,.88);border:1px solid rgba(217,119,6,.4)}

    .rc-top{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
    .rc-name{font-size:1.2rem;font-weight:700;color:#f1f5f9;line-height:1.3;flex:1}
    .rc-price{font-size:2.1rem;font-weight:900;color:#4ade80;line-height:1;white-space:nowrap}
    .rc-cur{font-size:.95rem;color:#4ade80}
    .rc-bot{display:flex;align-items:center;gap:8px;margin-top:9px;flex-wrap:wrap}
    .cpill{padding:3px 11px;border-radius:12px;font-size:.78rem;font-weight:700}
    .cp-غذائية{background:rgba(22,163,74,.25);color:#86efac;border:1px solid rgba(22,163,74,.4)}
    .cp-سجاير{background:rgba(180,83,9,.25);color:#fcd34d;border:1px solid rgba(180,83,9,.4)}
    .cp-مشروبات{background:rgba(37,99,235,.25);color:#93c5fd;border:1px solid rgba(37,99,235,.4)}
    .cp-منوعات{background:rgba(124,58,237,.25);color:#c4b5fd;border:1px solid rgba(124,58,237,.4)}
    .rc-ai{font-size:.75rem;color:#64748b}
    .rc-warn{color:#fbbf24;font-size:.97rem;font-weight:600}
    .rc-sub{color:#64748b;font-size:.8rem;margin-top:5px}
    .rc-sub a{color:#60a5fa}

    /* no key notice */
    #noKey{
      position:fixed;top:65px;left:13px;right:13px;
      background:rgba(220,38,38,.85);color:#fff;border-radius:10px;
      padding:11px 14px;font-size:.85rem;z-index:30;text-align:center;
      backdrop-filter:blur(8px);
    }
  </style>
</head>
<body>

<video id="video" autoplay playsinline muted></video>
<canvas id="canvas"></canvas>

<div id="topBar">
  <a href="index.php">←</a>
  <h1>⚡ Fast Scan</h1>
  <button id="scanToggle" onclick="toggleScan()">إيقاف</button>
</div>

<div id="ring" class="scanning"></div>
<div id="statusLbl">جارٍ المسح...</div>
<div id="pb"></div>

<div id="overlay">
  <div class="rcard ok" id="rcard"></div>
</div>

<?php if(!ANTHROPIC_API_KEY): ?>
<div id="noKey">
  ⚠️ افتح <code>config.php</code> وضع مفتاح <code>ANTHROPIC_API_KEY</code>
</div>
<?php endif ?>

<script>
const video   = document.getElementById('video');
const canvas  = document.getElementById('canvas');
const ring    = document.getElementById('ring');
const lbl     = document.getElementById('statusLbl');
const overlay = document.getElementById('overlay');
const rcard   = document.getElementById('rcard');
const pb      = document.getElementById('pb');
const toggle  = document.getElementById('scanToggle');

const INTERVAL = 3000;
let scanning = true, timer = null;

function esc(s){return String(s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}

function showFound(d){
  ring.className='found'; lbl.style.opacity='0';
  rcard.className='rcard ok';
  rcard.innerHTML=`
    <div class="rc-top">
      <div class="rc-name">${esc(d.name)}</div>
      <div><span class="rc-price">${parseFloat(d.price).toFixed(2)}</span><span class="rc-cur"> ج</span></div>
    </div>
    <div class="rc-bot">
      <span class="cpill cp-${esc(d.category)}">${esc(d.category)}</span>
      ${d.identified_as?`<span class="rc-ai">AI: ${esc(d.identified_as)}</span>`:''}
    </div>`;
  overlay.classList.add('show');
}

function showNotFound(d){
  ring.className='scanning'; lbl.style.opacity='1'; lbl.textContent='لم يُعثر على المنتج';
  rcard.className='rcard no';
  rcard.innerHTML=`
    <div class="rc-warn">⚠️ المنتج غير موجود في القاعدة</div>
    ${d.identified_as?`<div class="rc-sub">AI قال: ${esc(d.identified_as)}</div>`:''}
    <div class="rc-sub"><a href="index.php">أضفه هنا ←</a></div>`;
  overlay.classList.add('show');
  setTimeout(()=>{overlay.classList.remove('show');ring.className='scanning';lbl.textContent='جارٍ المسح...';lbl.style.opacity='1'},3000);
}

function startPB(ms){
  pb.style.transition='none'; pb.style.width='0%';
  void pb.offsetWidth;
  pb.style.transition=`width ${ms}ms linear`; pb.style.width='100%';
}

async function scanFrame(){
  if(!scanning||video.readyState<2) return;
  lbl.style.opacity='1'; lbl.textContent='⏳ جارٍ التعرف...';
  ring.className='scanning';
  canvas.width=video.videoWidth; canvas.height=video.videoHeight;
  canvas.getContext('2d').drawImage(video,0,0);
  const b64=canvas.toDataURL('image/jpeg',.75).split(',')[1];
  try{
    const r=await fetch('api.php?action=identify',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({image:b64})});
    const d=await r.json();
    if(d.error==='no_api_key'){lbl.textContent='مفتاح API غير مضبوط';return}
    if(d.found) showFound(d); else showNotFound(d);
  }catch(e){lbl.textContent='خطأ في الاتصال'}
}

function scheduleNext(){
  if(!scanning) return;
  startPB(INTERVAL);
  timer=setTimeout(async()=>{await scanFrame();scheduleNext()},INTERVAL);
}

function toggleScan(){
  scanning=!scanning;
  if(scanning){
    toggle.textContent='إيقاف'; toggle.classList.add('on');
    ring.className='scanning'; lbl.style.opacity='1'; lbl.textContent='جارٍ المسح...';
    overlay.classList.remove('show');
    scheduleNext();
  } else {
    clearTimeout(timer);
    toggle.textContent='تشغيل'; toggle.classList.remove('on');
    ring.style.animation='none'; lbl.textContent='متوقف'; pb.style.width='0%';
  }
}

async function startCamera(){
  try{
    const s=await navigator.mediaDevices.getUserMedia({video:{facingMode:{ideal:'environment'},width:{ideal:1280}}});
    video.srcObject=s;
    video.addEventListener('loadedmetadata',()=>{toggle.classList.add('on');scheduleNext()});
  }catch(e){lbl.textContent='تعذّر فتح الكاميرا';ring.style.display='none'}
}

startCamera();
</script>
</body>
</html>
