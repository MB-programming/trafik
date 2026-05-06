<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/auth.php';
require_once __DIR__.'/db.php';
requireLogin();

$geminiKey = (AI_PROVIDER === 'gemini') ? GEMINI_API_KEY : '';
$hasAI     = AI_PROVIDER !== '' && $geminiKey !== '';
$currency  = defined('CURRENCY_NAME') ? CURRENCY_NAME : 'جنيه';
$curLabel  = defined('CURRENCY_LABEL') ? CURRENCY_LABEL : 'ج';

// load all products from local DB and pass to JS
$db       = getDB();
$products = $db->query('SELECT id, name, price, category, image_path FROM products ORDER BY name')
               ->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"/>
  <title>Fast Scan — ترافيك</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
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
      background:linear-gradient(to bottom,rgba(0,0,0,.85),transparent);
    }
    .top-icon-btn{
      width:36px;height:36px;border-radius:9px;
      background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.25);
      color:#fff;display:flex;align-items:center;justify-content:center;
      font-size:.88rem;text-decoration:none;cursor:pointer;
    }
    #topTitle{color:#fff;font-size:.95rem;font-weight:700;flex:1;text-shadow:0 1px 4px rgba(0,0,0,.6);display:flex;align-items:center;gap:7px}
    #scanToggle{
      background:rgba(255,255,255,.15);border:1px solid rgba(255,255,255,.3);
      color:#fff;padding:6px 13px;border-radius:20px;font-size:.78rem;font-weight:700;cursor:pointer;
    }
    #scanToggle.on{background:rgba(34,197,94,.3);border-color:rgba(34,197,94,.5)}

    /* product count badge */
    #prodCount{
      background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.2);
      color:rgba(255,255,255,.75);padding:4px 10px;border-radius:20px;
      font-size:.72rem;display:flex;align-items:center;gap:5px;
    }

    /* scan ring */
    #ring{
      position:fixed;top:50%;left:50%;
      transform:translate(-50%,-55%);
      width:200px;height:200px;
      border:3px solid rgba(34,211,238,.7);border-radius:50%;
      pointer-events:none;z-index:5;
    }
    #ring.scanning{animation:rp 1.6s ease-in-out infinite}
    #ring.found{border-color:#4ade80;box-shadow:0 0 0 6px rgba(74,222,128,.2);animation:none}
    #ring.thinking{border-color:rgba(251,191,36,.7);animation:rp 0.8s ease-in-out infinite}
    #ring.noai{border-color:rgba(251,191,36,.6);animation:none}
    @keyframes rp{0%,100%{opacity:.6;transform:translate(-50%,-55%) scale(1)}50%{opacity:1;transform:translate(-50%,-55%) scale(1.06)}}

    #ringIcon{
      position:fixed;top:50%;left:50%;
      transform:translate(-50%,-55%);
      color:rgba(255,255,255,.5);font-size:1.8rem;
      pointer-events:none;z-index:5;
      display:flex;align-items:center;justify-content:center;
    }

    #statusLbl{
      position:fixed;top:50%;left:50%;
      transform:translate(-50%,62px);
      color:rgba(255,255,255,.85);font-size:.82rem;font-weight:600;
      text-align:center;text-shadow:0 1px 4px rgba(0,0,0,.8);
      z-index:5;pointer-events:none;
      display:flex;align-items:center;justify-content:center;gap:6px;
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
      backdrop-filter:blur(20px);-webkit-backdrop-filter:blur(20px);
    }
    .rcard.ok{background:rgba(15,23,42,.92);border:1px solid rgba(74,222,128,.45)}
    .rcard.no{background:rgba(15,23,42,.92);border:1px solid rgba(217,119,6,.4)}

    .rc-top{display:flex;justify-content:space-between;align-items:flex-start;gap:12px}
    .rc-left{display:flex;align-items:flex-start;gap:12px;flex:1;min-width:0}
    .rc-thumb{
      width:60px;height:60px;border-radius:10px;object-fit:cover;
      flex-shrink:0;background:rgba(255,255,255,.08);
      border:1px solid rgba(255,255,255,.1);
    }
    .rc-thumb-ph{
      width:60px;height:60px;border-radius:10px;
      background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);
      display:flex;align-items:center;justify-content:center;
      color:rgba(255,255,255,.3);font-size:1.5rem;flex-shrink:0;
    }
    .rc-meta{flex:1;min-width:0}
    .rc-name{font-size:1.1rem;font-weight:700;color:#f1f5f9;line-height:1.3}
    .rc-cat{font-size:.75rem;color:#64748b;margin-top:3px;display:flex;align-items:center;gap:4px}
    .rc-price-wrap{text-align:left;flex-shrink:0}
    .rc-price{font-size:2.4rem;font-weight:900;color:#4ade80;line-height:1}
    .rc-cur{font-size:.88rem;color:#4ade80;font-weight:700}
    .rc-ai{font-size:.7rem;color:#475569;margin-top:8px;display:flex;align-items:center;gap:4px}
    .rc-warn{color:#fbbf24;font-size:.97rem;font-weight:600;display:flex;align-items:center;gap:7px}
    .rc-sub{color:#64748b;font-size:.8rem;margin-top:5px;display:flex;align-items:center;gap:5px}
    .rc-sub a{color:#60a5fa}

    /* no AI bar */
    #noAiBar{
      position:fixed;top:65px;left:12px;right:12px;
      background:rgba(234,179,8,.9);color:#1c1917;
      border-radius:10px;padding:10px 14px;
      font-size:.83rem;font-weight:600;z-index:30;
      backdrop-filter:blur(8px);
      display:flex;align-items:center;gap:8px;
    }

    /* empty DB warning */
    #emptyBar{
      position:fixed;top:65px;left:12px;right:12px;
      background:rgba(239,68,68,.85);color:#fff;
      border-radius:10px;padding:10px 14px;
      font-size:.83rem;font-weight:600;z-index:30;
      backdrop-filter:blur(8px);
      display:flex;align-items:center;gap:8px;
    }
  </style>
</head>
<body>

<video id="video" autoplay playsinline muted></video>
<canvas id="canvas"></canvas>

<div id="topBar">
  <a href="pos.php" class="top-icon-btn"><i class="fa-solid fa-arrow-right"></i></a>
  <div id="topTitle"><i class="fa-solid fa-bolt"></i> Fast Scan</div>
  <div id="prodCount"><i class="fa-solid fa-database"></i> <span id="prodCountN"><?= count($products) ?></span> منتج</div>
  <button id="scanToggle" onclick="toggleScan()">إيقاف</button>
</div>

<div id="ring" class="<?= $hasAI ? 'scanning' : 'noai' ?>"></div>
<div id="ringIcon"><i class="fa-solid <?= $hasAI ? 'fa-camera' : 'fa-ban' ?>"></i></div>
<div id="statusLbl">
  <?php if(!$hasAI): ?>
  <i class="fa-solid fa-ban"></i> يحتاج Gemini API
  <?php elseif(count($products) === 0): ?>
  <i class="fa-solid fa-database"></i> لا توجد منتجات في القاعدة
  <?php else: ?>
  <i class="fa-solid fa-spinner fa-spin"></i> وجّه الكاميرا على المنتج...
  <?php endif ?>
</div>

<div id="pb"></div>

<div id="overlay">
  <div class="rcard ok" id="rcard"></div>
</div>

<?php if(!$hasAI): ?>
<div id="noAiBar">
  <i class="fa-solid fa-triangle-exclamation"></i>
  Fast Scan يحتاج Gemini API — افتح <strong>config.php</strong> وضع المفتاح
</div>
<?php elseif(count($products) === 0): ?>
<div id="emptyBar">
  <i class="fa-solid fa-box-open"></i>
  لا توجد منتجات في قاعدة البيانات — أضف منتجات أولاً من <strong>لوحة التحكم</strong>
</div>
<?php endif ?>

<script>
// ── Data from PHP ────────────────────────────────────────────────────────────
const GEMINI_KEY = <?= json_encode($geminiKey) ?>;
const HAS_AI     = <?= $hasAI ? 'true' : 'false' ?>;
const INTERVAL   = 4000;

// full product catalogue from our local DB — Gemini picks from this list only
const PRODUCTS = <?= json_encode(array_values($products), JSON_UNESCAPED_UNICODE) ?>;

const video    = document.getElementById('video');
const canvas   = document.getElementById('canvas');
const ring     = document.getElementById('ring');
const lbl      = document.getElementById('statusLbl');
const overlay  = document.getElementById('overlay');
const rcard    = document.getElementById('rcard');
const pb       = document.getElementById('pb');
const toggle   = document.getElementById('scanToggle');
const ringIcon = document.getElementById('ringIcon');

let scanning = true, timer = null;

/* ── Helpers ── */
function esc(s){return String(s||'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]))}

function setLbl(icon, txt){
  lbl.innerHTML=`<i class="fa-solid ${icon}"></i> ${txt}`;
}

/* ── Render result card ── */
function showFound(product, aiLabel){
  ring.className='found';
  ringIcon.innerHTML='<i class="fa-solid fa-circle-check" style="color:#4ade80"></i>';
  lbl.style.opacity='0';
  rcard.className='rcard ok';

  const imgHtml = product.image_path
    ? `<img class="rc-thumb" src="${esc(product.image_path)}" alt="${esc(product.name)}"/>`
    : `<div class="rc-thumb-ph"><i class="fa-solid fa-box"></i></div>`;

  rcard.innerHTML=`
    <div class="rc-top">
      <div class="rc-left">
        ${imgHtml}
        <div class="rc-meta">
          <div class="rc-name">${esc(product.name)}</div>
          <div class="rc-cat"><i class="fa-solid fa-tag"></i> ${esc(product.category||'')}</div>
        </div>
      </div>
      <div class="rc-price-wrap">
        <div class="rc-price">${parseFloat(product.price).toFixed(2)}</div>
        <div class="rc-cur"><?= htmlspecialchars($curLabel) ?></div>
      </div>
    </div>
    ${aiLabel!==product.name?`<div class="rc-ai"><i class="fa-solid fa-robot"></i> AI رصد: ${esc(aiLabel)}</div>`:''}`;
  overlay.classList.add('show');
}

function showNotFound(aiLabel){
  ring.className='scanning';
  ringIcon.innerHTML='<i class="fa-solid fa-camera"></i>';
  lbl.style.opacity='1';
  setLbl('fa-circle-xmark','المنتج مش في قاعدتك');
  rcard.className='rcard no';
  rcard.innerHTML=`
    <div class="rc-warn"><i class="fa-solid fa-triangle-exclamation"></i> غير موجود في قاعدة البيانات</div>
    ${aiLabel?`<div class="rc-sub"><i class="fa-solid fa-robot"></i> AI شاف: ${esc(aiLabel)}</div>`:''}
    <div class="rc-sub"><i class="fa-solid fa-plus-circle"></i> <a href="index.php">أضفه من لوحة التحكم</a></div>`;
  overlay.classList.add('show');
  setTimeout(()=>{
    overlay.classList.remove('show');
    ring.className='scanning';
    lbl.style.opacity='1';
    setLbl('fa-spinner fa-spin','وجّه الكاميرا على المنتج...');
  }, 3500);
}

/* ── Progress bar ── */
function startPB(ms){
  pb.style.transition='none';pb.style.width='0%';
  void pb.offsetWidth;
  pb.style.transition=`width ${ms}ms linear`;pb.style.width='100%';
}

/* ── Gemini: pick from OUR product list ── */
async function askGemini(imageBase64){
  if(!PRODUCTS.length) return null;

  // build a numbered list of our product names for Gemini
  const list = PRODUCTS.map((p,i)=>`${i+1}. ${p.name}`).join('\n');

  const prompt =
    `أمامك صورة لمنتج.\n` +
    `من القائمة التالية فقط، ما هو المنتج الذي يظهر في الصورة؟\n\n` +
    `القائمة:\n${list}\n\n` +
    `أجب برقم المنتج فقط (مثال: 3) إذا تعرفت عليه، أو أجب بالرقم 0 إذا لم يكن المنتج في القائمة.` +
    ` لا تكتب أي شيء آخر غير الرقم.`;

  const url = `https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=${GEMINI_KEY}`;
  const payload = {
    contents:[{parts:[
      {inline_data:{mime_type:'image/jpeg',data:imageBase64}},
      {text: prompt}
    ]}],
    generationConfig:{maxOutputTokens:10,temperature:0.1}
  };
  const r = await fetch(url,{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify(payload)});
  const d = await r.json();
  const raw = d.candidates?.[0]?.content?.parts?.[0]?.text?.trim() || '0';
  const idx = parseInt(raw, 10);
  if(isNaN(idx)||idx===0) return null;
  return PRODUCTS[idx-1] || null; // 1-based index
}

/* ── Main scan frame ── */
async function scanFrame(){
  if(!scanning || video.readyState < 2) return;
  if(!HAS_AI){setLbl('fa-ban','يحتاج Gemini API');return}
  if(!PRODUCTS.length){setLbl('fa-database','لا توجد منتجات في القاعدة');return}

  ring.className='thinking';
  ringIcon.innerHTML='<i class="fa-solid fa-spinner fa-spin" style="color:#fbbf24"></i>';
  lbl.style.opacity='1';
  setLbl('fa-spinner fa-spin','جارٍ التعرف...');

  canvas.width=video.videoWidth; canvas.height=video.videoHeight;
  canvas.getContext('2d').drawImage(video,0,0);
  const b64 = canvas.toDataURL('image/jpeg',.75).split(',')[1];

  try{
    const product = await askGemini(b64);
    if(product){
      showFound(product, product.name);
    } else {
      showNotFound('');
    }
  }catch(e){
    ring.className='scanning';
    ringIcon.innerHTML='<i class="fa-solid fa-camera"></i>';
    setLbl('fa-wifi','خطأ في الاتصال');
  }
}

function scheduleNext(){
  if(!scanning)return;
  startPB(INTERVAL);
  timer=setTimeout(async()=>{await scanFrame();scheduleNext()},INTERVAL);
}

function toggleScan(){
  scanning=!scanning;
  if(scanning){
    toggle.textContent='إيقاف'; toggle.classList.add('on');
    ring.className='scanning'; lbl.style.opacity='1';
    setLbl('fa-spinner fa-spin','وجّه الكاميرا على المنتج...');
    overlay.classList.remove('show');
    scheduleNext();
  }else{
    clearTimeout(timer);
    toggle.textContent='تشغيل'; toggle.classList.remove('on');
    ring.style.animation='none';
    setLbl('fa-pause','متوقف');
    pb.style.width='0%';
  }
}

/* ── Camera ── */
async function startCamera(){
  try{
    const s=await navigator.mediaDevices.getUserMedia({
      video:{facingMode:{ideal:'environment'},width:{ideal:1280}}
    });
    video.srcObject=s;
    video.addEventListener('loadedmetadata',()=>{
      toggle.classList.add('on');
      scheduleNext();
    });
  }catch(e){
    setLbl('fa-video-slash','تعذّر فتح الكاميرا');
    ring.style.display='none';
  }
}

startCamera();
</script>
</body>
</html>
