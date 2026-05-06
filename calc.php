<?php
require_once __DIR__.'/config.php';
require_once __DIR__.'/auth.php';
requireLogin();
$cur = defined('CURRENCY_LABEL') ? CURRENCY_LABEL : 'ج';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"/>
  <title>كالكوليتور — ترافيك</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
  <style>
    *{box-sizing:border-box;margin:0;padding:0;-webkit-tap-highlight-color:transparent;user-select:none}
    html,body{width:100%;height:100%;font-family:'Segoe UI',Tahoma,sans-serif;background:#0f172a;color:#f1f5f9;overflow:hidden}
    body{display:flex;flex-direction:column}

    /* header */
    .hdr{
      display:flex;align-items:center;gap:10px;
      padding:env(safe-area-inset-top,10px) 14px 10px;
      background:rgba(255,255,255,.04);border-bottom:1px solid rgba(255,255,255,.07);
      flex-shrink:0;
    }
    .back-btn{
      width:36px;height:36px;border-radius:9px;
      background:rgba(255,255,255,.1);border:none;color:#f1f5f9;
      display:flex;align-items:center;justify-content:center;
      font-size:.9rem;cursor:pointer;text-decoration:none;
    }
    .hdr-title{font-size:.95rem;font-weight:700;flex:1;display:flex;align-items:center;gap:7px;color:#f1f5f9}
    .btn-reset{
      background:rgba(239,68,68,.2);border:1px solid rgba(239,68,68,.4);
      color:#fca5a5;padding:7px 14px;border-radius:10px;
      font-size:.82rem;font-weight:700;cursor:pointer;
      display:flex;align-items:center;gap:6px;
    }
    .btn-reset:active{background:rgba(239,68,68,.35)}

    /* display */
    .display{
      flex-shrink:0;padding:18px 20px 12px;
      background:rgba(255,255,255,.03);
      border-bottom:1px solid rgba(255,255,255,.07);
    }
    .disp-total-lbl{font-size:.72rem;color:#475569;letter-spacing:.05em;text-transform:uppercase;margin-bottom:4px}
    .disp-total{font-size:3rem;font-weight:900;color:#4ade80;line-height:1;display:flex;align-items:baseline;gap:8px}
    .disp-cur{font-size:1.1rem;color:#86efac}
    .disp-entry{
      margin-top:10px;padding:10px 14px;
      background:rgba(255,255,255,.05);border-radius:10px;
      display:flex;align-items:center;justify-content:space-between;gap:10px;
    }
    .disp-input{font-size:1.6rem;font-weight:700;color:#f1f5f9;flex:1;min-width:0;text-align:right}
    .disp-op{
      font-size:1.4rem;font-weight:900;
      width:38px;height:38px;border-radius:9px;
      display:flex;align-items:center;justify-content:center;flex-shrink:0;
    }
    .disp-op.plus{background:rgba(34,197,94,.2);color:#4ade80}
    .disp-op.minus{background:rgba(239,68,68,.2);color:#fca5a5}
    .disp-op.none{background:transparent;color:transparent}

    /* history strip */
    .hist-strip{
      flex-shrink:0;height:34px;overflow-x:auto;overflow-y:hidden;
      display:flex;align-items:center;gap:6px;padding:0 16px;
      background:rgba(255,255,255,.02);border-bottom:1px solid rgba(255,255,255,.05);
      white-space:nowrap;
    }
    .hist-strip::-webkit-scrollbar{display:none}
    .hist-item{font-size:.72rem;color:#475569;flex-shrink:0}
    .hist-item.pos{color:#4ade80}
    .hist-item.neg{color:#f87171}

    /* numpad */
    .numpad{
      flex:1;display:grid;
      grid-template-columns:repeat(3,1fr);
      grid-template-rows:repeat(5,1fr);
      gap:8px;padding:10px;
    }
    .key{
      background:rgba(255,255,255,.07);
      border:none;border-radius:14px;
      color:#f1f5f9;font-size:1.5rem;font-weight:700;
      cursor:pointer;display:flex;align-items:center;justify-content:center;
      transition:transform .08s,background .1s;
    }
    .key:active{transform:scale(.91);background:rgba(255,255,255,.16)}
    .key.op-plus{background:rgba(34,197,94,.25);color:#4ade80;font-size:1.8rem}
    .key.op-plus:active{background:rgba(34,197,94,.4)}
    .key.op-minus{background:rgba(239,68,68,.25);color:#f87171;font-size:1.8rem}
    .key.op-minus:active{background:rgba(239,68,68,.4)}
    .key.key-del{background:rgba(255,255,255,.05);color:#94a3b8;font-size:1.2rem}
    .key.key-dot{color:#94a3b8}
    .key.key-eq{
      background:#2563eb;color:#fff;font-size:1.3rem;
      grid-row:span 1;
    }
    .key.key-eq:active{background:#1d4ed8}
  </style>
</head>
<body>

<div class="hdr">
  <button class="btn-reset" onclick="reset()" title="ريسيت">
    <i class="fa-solid fa-rotate-left"></i> ريسيت
  </button>
  <div class="hdr-title" style="justify-content:center">
    <i class="fa-solid fa-calculator"></i> كالكوليتور
  </div>
  <button class="back-btn" onclick="toggleFS()" id="fsBtn" title="ملء الشاشة"><i class="fa-solid fa-expand" id="fsIcon"></i></button>
  <a href="pos.php" class="back-btn" title="POS"><i class="fa-solid fa-store"></i></a>
</div>

<!-- Display -->
<div class="display">
  <div class="disp-total-lbl">الإجمالي</div>
  <div class="disp-total">
    <span id="dispTotal">0.00</span>
    <span class="disp-cur"><?= htmlspecialchars($cur) ?></span>
  </div>
  <div class="disp-entry">
    <div class="disp-op none" id="dispOp"></div>
    <div class="disp-input" id="dispInput">0</div>
  </div>
</div>

<!-- History strip -->
<div class="hist-strip" id="histStrip">
  <span class="hist-item" style="color:#334155">لا توجد عمليات بعد</span>
</div>

<!-- Numpad -->
<div class="numpad">
  <!-- row 1 -->
  <button class="key" onclick="digit('7')">7</button>
  <button class="key" onclick="digit('8')">8</button>
  <button class="key" onclick="digit('9')">9</button>
  <!-- row 2 -->
  <button class="key" onclick="digit('4')">4</button>
  <button class="key" onclick="digit('5')">5</button>
  <button class="key" onclick="digit('6')">6</button>
  <!-- row 3 -->
  <button class="key" onclick="digit('1')">1</button>
  <button class="key" onclick="digit('2')">2</button>
  <button class="key" onclick="digit('3')">3</button>
  <!-- row 4 -->
  <button class="key key-dot" onclick="dot()">.</button>
  <button class="key" onclick="digit('0')">0</button>
  <button class="key key-del" onclick="del()"><i class="fa-solid fa-delete-left"></i></button>
  <!-- row 5: + − = -->
  <button class="key op-plus"  onclick="op('+')">+</button>
  <button class="key op-minus" onclick="op('−')">−</button>
  <button class="key key-eq"   onclick="commit()"><i class="fa-solid fa-equals"></i></button>
</div>

<script>
const CURRENCY = <?= json_encode($cur) ?>;
let total   = 0;      // running total
let input   = '0';    // current input string
let pending = null;   // '+' or '−' (pending operation)
let history = [];     // array of {op, val}

function fmt(n){ return parseFloat(n).toFixed(2); }

function updateDisplay(){
  document.getElementById('dispTotal').textContent = fmt(total);
  document.getElementById('dispInput').textContent = input;

  const opEl = document.getElementById('dispOp');
  if(pending){
    opEl.textContent = pending;
    opEl.className = 'disp-op ' + (pending==='+'?'plus':'minus');
  } else {
    opEl.textContent=''; opEl.className='disp-op none';
  }
}

function updateHistory(){
  const strip = document.getElementById('histStrip');
  if(!history.length){
    strip.innerHTML='<span class="hist-item" style="color:#334155">لا توجد عمليات بعد</span>';
    return;
  }
  strip.innerHTML = history.map(h=>`<span class="hist-item ${h.op==='+'?'pos':'neg'}">${h.op}${fmt(h.val)}</span>`).join('<span class="hist-item" style="color:#334155;padding:0 2px">·</span>');
  strip.scrollLeft = strip.scrollWidth;
}

function digit(d){
  if(input==='0'&&d!=='.') input=d;
  else if(input.length < 12) input+=d;
  updateDisplay();
}
function dot(){
  if(!input.includes('.')){ input+='.'; updateDisplay(); }
}
function del(){
  input = input.length>1 ? input.slice(0,-1) : '0';
  updateDisplay();
}

function op(o){
  // commit current input first if there's a pending op
  if(pending) commit();
  pending = o;
  updateDisplay();
}

function commit(){
  const val = parseFloat(input)||0;
  if(pending==='+'){
    history.push({op:'+',val});
    total += val;
  } else if(pending==='−'){
    history.push({op:'−',val});
    total -= val;
  } else {
    // no pending op: treat as + (first entry)
    history.push({op:'+',val});
    total += val;
  }
  pending = null;
  input   = '0';
  updateDisplay();
  updateHistory();
}

function reset(){
  total=0; input='0'; pending=null; history=[];
  updateDisplay(); updateHistory();
}

/* ── Fullscreen ── */
function toggleFS(){
  if(!document.fullscreenElement) document.documentElement.requestFullscreen();
  else document.exitFullscreen();
}
document.addEventListener('fullscreenchange',()=>{
  document.getElementById('fsIcon').className=document.fullscreenElement?'fa-solid fa-compress':'fa-solid fa-expand';
});

updateDisplay();
</script>
</body>
</html>
