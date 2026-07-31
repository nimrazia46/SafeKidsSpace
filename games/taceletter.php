<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();

// This page requires a logged-in user so progress can be tied to their account.
// Adjust the redirect target to match your actual login page.
if (!isset($_SESSION['id'])) {
    header('Location: ../account/login.php');
    exit;
}
?>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<?php
$current_page = 'taceletter.php';
?> 
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Chalk Trail | A-Z Letter Tracing</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="../assets/layout.css">
<link rel="stylesheet" href="../assets/games.css">

</head>
<body class="taceletter-page">



<!-- Everything below is scoped inside .ctp so its resets/positioning
     never touch the navbar's own markup or styles -->
<div class="container">
<div class="ctp">

  <!-- Achievement / unlock popup — shown whenever a NEW badge unlocks
       OR a new chalk color unlocks. Accent color + content are set by JS. -->
  <div class="badge-popup-overlay" id="badgePopupOverlay">
    <div class="badge-popup-card" id="badgePopupCard">
      <div class="badge-popup-icon" id="badgePopupIcon"><i class="fa-solid fa-star"></i></div>
      <div class="badge-popup-label" id="badgePopupLabel">Badge Unlocked!</div>
      <div class="badge-popup-name" id="badgePopupName">Badge Name</div>
      <div class="badge-popup-desc" id="badgePopupDesc">Description</div>
      <div class="badge-popup-swatch" id="badgePopupSwatch"></div>
    </div>
  </div>

  <!-- Clear-progress confirmation — same visual family as the achievement popup -->
  <div class="badge-popup-overlay" id="clearConfirmOverlay">
    <div class="badge-popup-card confirm-popup-card" id="clearConfirmCard">
      <div class="badge-popup-icon"><i class="fa-solid fa-trash-can"></i></div>
      <div class="badge-popup-label">Clear Progress?</div>
      <div class="badge-popup-name">This can't be undone</div>
      <div class="badge-popup-desc">All traced letters, badges, and unlocked chalk colors will be reset. Are you sure?</div>
      <div class="confirm-popup-actions">
        <button class="confirm-cancel-btn" onclick="closeClearConfirm()">Cancel</button>
        <button class="confirm-clear-btn" onclick="clearProgress()">Yes, clear it</button>
      </div>
    </div>
  </div>

  <div class="wrap mt-4">

    <section class="hero-banner">
      <div class="banner-badge"><i class="fa-solid fa-meteor"></i> Cosmic Alphabet Quest</div>
      <h1>Trace your way through the <em>galaxy</em>, one letter at a time</h1>
      <p>26 letters, 26 tiny constellations. Follow the chalk-dust trail and light one up with every trace.</p>
    </section>

    <div class="ct-topbar">
      <div class="brand">Chalk <span>Trail</span></div>
      <div class="progress-pill"><b id="letterCountLabel">1</b> / 26 letters traced today: <b id="tracedCount">0</b></div>
    </div>

    <div class="letter-strip" id="letterStrip"></div>

    <div class="studio">

      <!-- SIDE PANEL -->
      <aside class="side-panel">

        <div class="card">
          <h4>Letter Case</h4>
          <div class="case-toggle">
            <button class="case-btn active" id="upperBtn" onclick="setCase('upper')">Aa</button>
            <button class="case-btn" id="lowerBtn" onclick="setCase('lower')">aa</button>
          </div>
        </div>

        <div class="card">
          <h4>Chalk Color</h4>
          <div class="crayon-row" id="crayonRow"></div>
          <p class="crayon-hint" id="crayonHint">Trace more letters to unlock new chalk colors!</p>
        </div>

        <div class="card">
          <h4>Chalk Thickness</h4>
          <div class="size-row">
            <i class="fa-solid fa-minus" style="color:var(--chalk-dim); font-size:11px;"></i>
            <input type="range" id="sizeSlider" min="4" max="22" value="10">
            <i class="fa-solid fa-plus" style="color:var(--chalk-dim); font-size:11px;"></i>
          </div>
        </div>

        <div class="card tip-card">
          <div class="tip-head"><i class="fa-solid fa-lightbulb"></i>Tracing Tip</div>
          <p id="tipText">Start right on the glowing dot. Follow the dashed path slowly — you don't have to stay perfectly on the line!</p>
        </div>

      </aside>

      <!-- CANVAS -->
      <section class="canvas-area">
        <div class="viewport" id="previewViewport">
          <div class="viewport-label">Letter Preview</div>
          <canvas id="previewCanvas"></canvas>
        </div>

        <div class="divider-btn"><i class="fa-solid fa-chevron-left"></i><i class="fa-solid fa-chevron-right"></i></div>

        <div class="viewport" id="canvasViewport">
          <div class="viewport-label">Your Canvas</div>
          <canvas id="guideCanvas"></canvas>
          <canvas id="drawCanvas"></canvas>
          <div class="spark-layer" id="sparkLayer"></div>

          <div class="toolbar">
            <button class="tool-btn active" id="brushToolBtn" onclick="setTool('brush')" title="Chalk"><i class="fa-solid fa-paintbrush"></i></button>
            <button class="tool-btn" id="eraserToolBtn" onclick="setTool('eraser')" title="Eraser"><i class="fa-solid fa-eraser"></i></button>
            <button class="tool-btn" onclick="undoStroke()" title="Undo"><i class="fa-solid fa-rotate-left"></i></button>
            <button class="tool-btn" onclick="clearDrawing()" title="Clear"><i class="fa-solid fa-trash-can"></i></button>
          </div>
        </div>
      </section>

    </div>

    <div class="ct-actions">
      <button class="nav-btn btn-ghost" onclick="stepLetter(-1)"><i class="fa-solid fa-chevron-left"></i> Previous Letter</button>
      <button class="nav-btn btn-done" id="traceDoneBtn" onclick="markTraced()" disabled title="Trace the letter on your canvas first"><i class="fa-solid fa-star"></i> I traced it!</button>
      <button class="nav-btn btn-primary" onclick="stepLetter(1)">Next Letter <i class="fa-solid fa-chevron-right"></i></button>
      <div class="trace-hint" id="traceHint"><i class="fa-solid fa-hand-pointer"></i>Draw over the letter on your canvas to unlock this button</div>
    </div>

    <!-- BADGES — progress panel on the LEFT, badge grid on the RIGHT,
         both inside the same page container as the tracing tool.
         Everything here is earned by actually tracing letters above. -->
    <section class="badges-section">
      <div class="badges-header">
        <h2><i class="fa-solid fa-meteor"></i> Tracing Badges</h2>
        <div class="badges-count"><b id="statTotalBadges">0</b> / <span id="statTotalPossible">0</span> unlocked</div>
      </div>

      <div class="badges-layout">

        <!-- LEFT: overall progress -->
        <aside class="progress-panel card">
          <h4>
            Your Progress
            <button class="progress-reset-btn" onclick="openClearConfirm()" title="Clear all progress">
              <i class="fa-solid fa-trash-can"></i>
            </button>
          </h4>

          <div class="progress-ring-wrap">
            <svg class="progress-ring" viewBox="0 0 120 120">
              <circle class="progress-ring-track" cx="60" cy="60" r="52"></circle>
              <circle class="progress-ring-bar" id="progressRingBar" cx="60" cy="60" r="52"></circle>
            </svg>
            <div class="progress-ring-label">
              <span id="progressRingPercent">0%</span>
              <small>traced</small>
            </div>
          </div>

          <div class="progress-stat-row">
            <div class="progress-stat">
              <span class="progress-stat-num" id="statLettersTraced">0</span>
              <span class="progress-stat-label">Letters Traced</span>
            </div>
            <div class="progress-stat">
              <span class="progress-stat-num" id="statBadgesUnlocked">0</span>
              <span class="progress-stat-label">Badges Earned</span>
            </div>
          </div>

          <div class="progress-mini-list" id="progressMiniList"></div>
        </aside>

        <!-- RIGHT: badges -->
        <div class="badge-grid" id="badgeGrid"></div>

      </div>
    </section>

  </div>

</div>
</div><!-- /.container -->

<script>
const ALPHABET = "ABCDEFGHIJKLMNOPQRSTUVWXYZ".split('');
let currentIndex = 0;
let currentCase = 'upper';
let activeColor = '#f7f6f1';
let brushSize = 10;
let toolMode = 'brush';
let tracedLetters = new Set();
let tracedUpper = new Set(); // indices traced while in uppercase mode
let tracedLower = new Set(); // indices traced while in lowercase mode

// Tracks whether the child has actually drawn a real stroke on the
// CURRENT letter — the "I traced it!" button stays disabled until this
// is true, so it can't be clicked without tracing.
let letterHasBeenDrawn = false;

const guideCanvas = document.getElementById('guideCanvas');
const drawCanvas = document.getElementById('drawCanvas');
const previewCanvas = document.getElementById('previewCanvas');
const guideCtx = guideCanvas.getContext('2d');
const drawCtx = drawCanvas.getContext('2d');
const previewCtx = previewCanvas.getContext('2d');
const sparkLayer = document.getElementById('sparkLayer');
const traceDoneBtn = document.getElementById('traceDoneBtn');
const traceHint = document.getElementById('traceHint');

let undoStack = [];
let painting = false;
let strokePoints = [];

const TIPS = [
  "Start right on the glowing dot. Follow the dashed path slowly — you don't have to stay perfectly on the line!",
  "Curvy letters like C and S like a slow, round hand. Take your time on the curves.",
  "Tall letters get a strong straight line first, then add the smaller parts.",
  "If a letter has two strokes, lift your chalk and start the second stroke fresh.",
  "Small wobbles are totally fine — tracing is about the shape, not perfection!"
];

function buildLetterStrip(){
  const strip = document.getElementById('letterStrip');
  strip.innerHTML = '';
  ALPHABET.forEach((L, i) => {
    const chip = document.createElement('button');
    chip.className = 'letter-chip';
    chip.textContent = L;
    chip.onclick = () => { currentIndex = i; refreshAll(); };
    strip.appendChild(chip);
  });
}

function sizeCanvases(){
  [guideCanvas, drawCanvas, previewCanvas].forEach(c => {
    c.width = c.offsetWidth;
    c.height = c.offsetHeight;
  });
}

function drawPreviewLetter(){
  const w = previewCanvas.width, h = previewCanvas.height;
  previewCtx.clearRect(0,0,w,h);

  const letter = currentCase === 'upper' ? ALPHABET[currentIndex] : ALPHABET[currentIndex].toLowerCase();
  const fontSize = Math.min(w,h) * 0.72;

  previewCtx.font = `700 ${fontSize}px Fredoka, sans-serif`;
  previewCtx.textAlign = 'center';
  previewCtx.textBaseline = 'middle';

  const cx = w/2, cy = h/2 + fontSize*0.04;

  previewCtx.save();
  previewCtx.shadowColor = 'rgba(236,72,153,0.85)';
  previewCtx.shadowBlur = 22;
  previewCtx.lineWidth = 5;
  previewCtx.strokeStyle = 'rgba(247,246,241,0.95)';
  previewCtx.strokeText(letter, cx, cy);
  previewCtx.restore();

  previewCtx.shadowBlur = 0;
  previewCtx.lineWidth = 2;
  previewCtx.strokeStyle = 'rgba(124,58,237,0.9)';
  previewCtx.strokeText(letter, cx, cy);

  previewCtx.fillStyle = 'rgba(255,255,255,0.8)';
  const sparklePositions = [[0.18,0.22],[0.84,0.16],[0.14,0.8],[0.88,0.78]];
  sparklePositions.forEach(([px,py]) => {
    previewCtx.beginPath();
    previewCtx.arc(w*px, h*py, 1.6, 0, Math.PI*2);
    previewCtx.fill();
  });
}

function drawGuideLetter(){
  const w = guideCanvas.width, h = guideCanvas.height;
  guideCtx.clearRect(0,0,w,h);

  const letter = currentCase === 'upper' ? ALPHABET[currentIndex] : ALPHABET[currentIndex].toLowerCase();
  const fontSize = Math.min(w,h) * 0.72;

  guideCtx.font = `700 ${fontSize}px Fredoka, sans-serif`;
  guideCtx.textAlign = 'center';
  guideCtx.textBaseline = 'middle';

  const cx = w/2, cy = h/2 + fontSize*0.04;

  guideCtx.fillStyle = 'rgba(247,246,241,0.05)';
  guideCtx.fillText(letter, cx, cy);

  guideCtx.setLineDash([10,9]);
  guideCtx.lineWidth = 3;
  guideCtx.strokeStyle = 'rgba(255,210,63,0.55)';
  guideCtx.strokeText(letter, cx, cy);
  guideCtx.setLineDash([]);
}

function refreshAll(){
  buildLetterStrip();
  markActiveChip();
  document.getElementById('letterCountLabel').textContent = currentIndex + 1;
  document.getElementById('tipText').textContent = TIPS[currentIndex % TIPS.length];
  clearDrawing(false);
  drawGuideLetter();
  drawPreviewLetter();
  scrollChipIntoView();
  setLetterDrawn(false); // new letter card = must trace again before "I traced it!" enables
}

function markActiveChip(){
  const chips = document.querySelectorAll('.letter-chip');
  chips.forEach((chip, i) => {
    chip.classList.toggle('active', i === currentIndex);
    chip.classList.toggle('done', tracedLetters.has(i));
  });
}

function scrollChipIntoView(){
  const chips = document.querySelectorAll('.letter-chip');
  if(chips[currentIndex]) chips[currentIndex].scrollIntoView({ behavior:'smooth', inline:'center', block:'nearest' });
}

function setCase(mode){
  currentCase = mode;
  document.getElementById('upperBtn').classList.toggle('active', mode === 'upper');
  document.getElementById('lowerBtn').classList.toggle('active', mode === 'lower');
  drawGuideLetter();
  drawPreviewLetter();
}

function setColor(hex){
  activeColor = hex;
  setTool('brush');
  renderCrayons();
}

function setTool(mode){
  toolMode = mode;
  document.getElementById('brushToolBtn').classList.toggle('active', mode === 'brush');
  document.getElementById('eraserToolBtn').classList.toggle('active', mode === 'eraser');
}

document.getElementById('sizeSlider').addEventListener('input', e => {
  brushSize = parseInt(e.target.value, 10);
});

function getPos(evt){
  const rect = drawCanvas.getBoundingClientRect();
  const t = evt.touches ? evt.touches[0] : evt;
  return { x: t.clientX - rect.left, y: t.clientY - rect.top };
}

function startPaint(evt){
  painting = true;
  const p = getPos(evt);
  strokePoints = [p];
  drawCtx.beginPath();
  drawCtx.moveTo(p.x, p.y);
  drawCtx.lineCap = 'round';
  drawCtx.lineJoin = 'round';
}

function movePaint(evt){
  if(!painting) return;
  evt.preventDefault();
  const p = getPos(evt);
  strokePoints.push(p);

  if(toolMode === 'eraser'){
    drawCtx.globalCompositeOperation = 'destination-out';
    drawCtx.shadowBlur = 0;
    drawCtx.lineWidth = brushSize * 2.2;
  } else {
    drawCtx.globalCompositeOperation = 'source-over';
    drawCtx.strokeStyle = activeColor;
    drawCtx.shadowColor = activeColor;
    drawCtx.shadowBlur = brushSize * 0.25; // soft chalk-dust edge
    drawCtx.lineWidth = brushSize;
  }

  const n = strokePoints.length;
  if(n < 3){
    // not enough points yet for a smooth curve — draw a plain segment
    drawCtx.beginPath();
    drawCtx.moveTo(strokePoints[0].x, strokePoints[0].y);
    drawCtx.lineTo(p.x, p.y);
    drawCtx.stroke();
    return;
  }

  // Smooth chalk stroke: draw a quadratic curve through the midpoints of
  // consecutive points, using the middle point as the curve's control
  // point. This rounds off the jagged corners a plain lineTo-per-move
  // produces and reads as a single continuous chalk stroke.
  const p0 = strokePoints[n - 3];
  const p1 = strokePoints[n - 2];
  const midStart = { x: (p0.x + p1.x) / 2, y: (p0.y + p1.y) / 2 };
  const midEnd = { x: (p1.x + p.x) / 2, y: (p1.y + p.y) / 2 };

  drawCtx.beginPath();
  drawCtx.moveTo(midStart.x, midStart.y);
  drawCtx.quadraticCurveTo(p1.x, p1.y, midEnd.x, midEnd.y);
  drawCtx.stroke();
}

function endPaint(){
  if(painting){
    painting = false;
    // Only a real brush stroke of a few points counts as "tracing" —
    // a single accidental tap (1-2 points) shouldn't unlock the button.
    if(toolMode === 'brush' && strokePoints.length > 3){
      setLetterDrawn(true);
    }
    strokePoints = [];
    cacheState();
  }
}

function setLetterDrawn(state){
  letterHasBeenDrawn = state;
  if(traceDoneBtn){
    traceDoneBtn.disabled = !state;
    traceDoneBtn.title = state ? 'Mark this letter as traced' : 'Trace the letter on your canvas first';
  }
  if(traceHint){
    traceHint.style.display = state ? 'none' : 'block';
  }
}

function cacheState(){
  if(undoStack.length >= 20) undoStack.shift();
  undoStack.push(drawCanvas.toDataURL());
}

function undoStroke(){
  if(undoStack.length > 1){
    undoStack.pop();
    const img = new Image();
    img.src = undoStack[undoStack.length - 1];
    img.onload = () => {
      drawCtx.clearRect(0,0,drawCanvas.width, drawCanvas.height);
      drawCtx.globalCompositeOperation = 'source-over';
      drawCtx.drawImage(img, 0, 0);
    };
    // If undo wiped the canvas back to blank, require tracing again.
    if(undoStack.length <= 1) setLetterDrawn(false);
  } else {
    clearDrawing();
  }
}

function clearDrawing(recache = true){
  drawCtx.clearRect(0,0,drawCanvas.width, drawCanvas.height);
  undoStack = [];
  if(recache){
    cacheState();
    setLetterDrawn(false); // cleared the canvas — must trace again
  }
}

function stepLetter(dir){
  currentIndex = Math.min(Math.max(currentIndex + dir, 0), ALPHABET.length - 1);
  refreshAll();
}

function markTraced(){
    if(!letterHasBeenDrawn) return; // safety net — button should already be disabled

    tracedLetters.add(currentIndex);

    if(currentCase === 'upper'){
        tracedUpper.add(currentIndex);
    } else {
        tracedLower.add(currentIndex);
    }

    document.getElementById("tracedCount").textContent=tracedLetters.size;

    markActiveChip();

    burstSparks();

    queueTracedPopup(ALPHABET[currentIndex]);

    renderBadges(); // keep every tracing badge in sync with real progress

    // Persist this trace to the logged-in user's account (backend reads
    // the user id from the PHP session — nothing sent from the client).
    fetch("../playgame-api/save_progress.php",{

        method:"POST",

        headers:{
            "Content-Type":"application/x-www-form-urlencoded"
        },

        body:
        "letter="+ALPHABET[currentIndex]+
        "&case="+currentCase

    })

    .then(r=>r.json())

    .then(data=>{

        if(!data || data.success !== true){
            console.error("Save failed:", data && data.error);
        }

    })

    .catch(err=>{
        console.error("Could not reach save_progress.php:", err);
    });

}

// Loads this user's previously-traced letters from the database so
// progress survives across visits/devices, as long as they're logged in.
async function loadProgress(){
  try{
    const res = await fetch('../playgame-api/get_progress.php');
    const data = await res.json();

    if(!res.ok || !data || data.success !== true){
      console.error('Could not load saved progress:', data && data.error);
      return;
    }

    (data.traced || []).forEach(row => {
      const idx = ALPHABET.indexOf(String(row.letter).toUpperCase());
      if(idx === -1) return;

      tracedLetters.add(idx);
      if(row.case_mode === 'upper') tracedUpper.add(idx);
      else if(row.case_mode === 'lower') tracedLower.add(idx);
    });
  } catch(err){
    console.error('Could not reach get_progress.php:', err);
  }
}

function queueTracedPopup(letter){
  badgePopupQueue.push({
    type: 'trace',
    accent: '#38bdf8',
    icon: 'fa-solid fa-star',
    label: 'Nice Tracing!',
    name: `You traced "${letter}"!`,
    desc: 'Great job — keep going through the alphabet.'
  });
  if(!badgePopupShowing) showNextBadgePopup();
}

function burstSparks(){
  const icons = ['✨','⭐','🌟'];
  for(let i=0;i<10;i++){
    const s = document.createElement('div');
    s.className = 'spark';
    s.textContent = icons[Math.floor(Math.random()*icons.length)];
    s.style.left = (40 + Math.random()*60) + '%';
    s.style.bottom = (10 + Math.random()*20) + '%';
    s.style.animationDelay = (Math.random()*200) + 'ms';
    sparkLayer.appendChild(s);
    setTimeout(() => s.remove(), 1200);
  }
}

function attachHandlers(){
  drawCanvas.addEventListener('mousedown', startPaint);
  drawCanvas.addEventListener('mousemove', movePaint);
  window.addEventListener('mouseup', endPaint);
  drawCanvas.addEventListener('touchstart', startPaint, { passive:true });
  drawCanvas.addEventListener('touchmove', movePaint, { passive:false });
  window.addEventListener('touchend', endPaint);
}

/*==========================================================
    BADGES — every badge lives in ONE grid, uses a Font Awesome
    icon (no broken image paths), and is earned purely from real
    actions on the tracing board above. Nothing here is hardcoded
    fake progress — computeDynamicBadgeState() reads the live
    tracedLetters / tracedUpper / tracedLower sets.
==========================================================*/

// One accent color per rarity tier — reused for the badge card's
// progress bar / unlocked pill AND for the achievement popup, so a
// badge's color story is consistent everywhere it shows up.
const RARITY_COLORS = {
  common: '#4ade80',
  rare: '#38bdf8',
  epic: '#a78bfa',
  legendary: '#facc15'
};

// A few badges get their own signature color instead of the flat rarity
// color — right now just First Trace, since it's a kid's very first win
// and deserves something more fun than plain grey/green.
const BADGE_ACCENT_OVERRIDES = {
  'first-trace': '#71fb71'
};

function badgeAccentColor(b){
  return BADGE_ACCENT_OVERRIDES[b.id] || RARITY_COLORS[b.rarity] || '#4ade80';
}

const BADGES = [
  {
    id: 'first-trace',
    name: 'First Trace',
    desc: 'Trace your very first letter on the Chalk Trail board.',
    icon: 'fa-solid fa-pen-nib',
    rarity: 'common',
    dynamic: 'first-trace'
  },
  {
    id: 'high-five',
    name: 'High Five',
    desc: 'Trace 5 different letters.',
    icon: 'fa-solid fa-hands-clapping',
    rarity: 'rare',
    dynamic: 'high-five'
  },
  {
    id: 'halfway-hero',
    name: 'Halfway Hero',
    desc: 'Trace 13 letters — halfway through the alphabet!',
    icon: 'fa-solid fa-star-half-stroke',
    rarity: 'epic',
    dynamic: 'halfway-hero'
  },
  {
    id: 'alphabet-champion',
    name: 'Alphabet Champion',
    desc: 'Trace all 26 letters, A to Z.',
    icon: 'fa-solid fa-crown',
    rarity: 'legendary',
    dynamic: 'alphabet-champion'
  },
  {
    id: 'uppercase-ace',
    name: 'Uppercase Ace',
    desc: 'Trace 5 letters in Aa uppercase mode.',
    icon: 'fa-solid fa-arrow-up-a-z',
    rarity: 'rare',
    dynamic: 'uppercase-ace'
  },
  {
    id: 'lowercase-legend',
    name: 'Lowercase Legend',
    desc: 'Trace 5 letters in aa lowercase mode.',
    icon: 'fa-solid fa-arrow-down-a-z',
    rarity: 'rare',
    dynamic: 'lowercase-legend'
  }
];

// Every badge is dynamic — its unlocked/progress state is derived live
// from what's actually been traced, not stored on the badge object.
function computeDynamicBadgeState(dynamicId){
  const tracedCount = tracedLetters.size;
  switch(dynamicId){
    case 'first-trace':
      return { unlocked: tracedCount > 0, progress: tracedCount > 0 ? 100 : 0 };
    case 'high-five': {
      const goal = 5;
      return { unlocked: tracedCount >= goal, progress: Math.min(100, Math.round((tracedCount / goal) * 100)) };
    }
    case 'halfway-hero': {
      const goal = 13;
      return { unlocked: tracedCount >= goal, progress: Math.min(100, Math.round((tracedCount / goal) * 100)) };
    }
    case 'alphabet-champion': {
      const goal = ALPHABET.length;
      return { unlocked: tracedCount >= goal, progress: Math.min(100, Math.round((tracedCount / goal) * 100)) };
    }
    case 'uppercase-ace': {
      const goal = 5;
      return { unlocked: tracedUpper.size >= goal, progress: Math.min(100, Math.round((tracedUpper.size / goal) * 100)) };
    }
    case 'lowercase-legend': {
      const goal = 5;
      return { unlocked: tracedLower.size >= goal, progress: Math.min(100, Math.round((tracedLower.size / goal) * 100)) };
    }
    default:
      return { unlocked: false, progress: 0 };
  }
}

/*==========================================================
    CHALK UNLOCK SYSTEM — 5 colors are free from the start;
    3 more unlock as real tracing badges above are achieved.
    Uses the same computeDynamicBadgeState() as the source of
    truth, so a color and its matching badge always agree.
==========================================================*/
const CRAYONS = [
  { hex: '#f7f6f1', unlock: null, label: 'Chalk White' },
  { hex: '#2606f0', unlock: null, label: 'Electric Blue' },
  { hex: '#ff6b6b', unlock: null, label: 'Comet Red' },
  { hex: '#4ecdc4', unlock: null, label: 'Aqua Teal' },
  { hex: '#c084fc', unlock: null, label: 'Nebula Purple' },
  { hex: '#38bdf8', unlock: 'high-five', label: 'Sky Blue' },
  { hex: '#f472b6', unlock: 'halfway-hero', label: 'Star Pink' },
  { hex: '#facc15', unlock: 'alphabet-champion', label: 'Galaxy Gold' }
];

function isCrayonUnlocked(crayon){
  if(!crayon.unlock) return true;
  return computeDynamicBadgeState(crayon.unlock).unlocked;
}

// Seeded with the free colors already available at page load so we only
// ever pop a "new chalk unlocked" toast for a GENUINE new unlock, never
// for the starting palette.
let previouslyUnlockedCrayonHexes = new Set();
let chalkPopupsArmed = false;

function renderCrayons(){
  const row = document.getElementById('crayonRow');
  const hint = document.getElementById('crayonHint');
  if(!row) return;

  row.innerHTML = CRAYONS.map(c => {
    const unlocked = isCrayonUnlocked(c);
    const title = unlocked ? c.label : `Locked — ${c.label} (unlock: ${BADGES.find(b => b.dynamic === c.unlock)?.name || ''})`;
    if(unlocked){
      const isActive = activeColor === c.hex;
      return `<div class="crayon ${isActive ? 'active' : ''}" style="background:${c.hex};" onclick="setColor('${c.hex}')" title="${title}"></div>`;
    }
    return `<div class="crayon locked" style="background:${c.hex};" title="${title}"><i class="fa-solid fa-lock"></i></div>`;
  }).join('');

  // Detect newly-unlocked chalk colors and queue a popup for each.
  CRAYONS.forEach(c => {
    const unlocked = isCrayonUnlocked(c);
    if(unlocked && !previouslyUnlockedCrayonHexes.has(c.hex)){
      previouslyUnlockedCrayonHexes.add(c.hex);
      if(chalkPopupsArmed){
        badgePopupQueue.push({
          type: 'chalk',
          accent: c.hex,
          icon: 'fa-solid fa-palette',
          label: 'New Chalk Unlocked!',
          name: c.label,
          desc: 'A brand new chalk color is ready to use — pick it from the Chalk Color panel!'
        });
      }
    }
  });
  if(chalkPopupsArmed && !badgePopupShowing) showNextBadgePopup();

  const lockedLeft = CRAYONS.filter(c => !isCrayonUnlocked(c)).length;
  if(hint){
    hint.textContent = lockedLeft === 0
      ? 'All chalk colors unlocked! 🎉'
      : `${lockedLeft} more color${lockedLeft > 1 ? 's' : ''} to unlock — keep tracing!`;
  }
}

function escapeHtml(str){
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

/* Tracks which badges were already unlocked, so we only pop the toast
   for genuinely NEW achievements (not every re-render). */
let previouslyUnlockedBadgeIds = new Set();
let badgePopupQueue = [];
let badgePopupShowing = false;

function renderBadges(){
  const grid = document.getElementById('badgeGrid');
  const totalEl = document.getElementById('statTotalBadges');
  const possibleEl = document.getElementById('statTotalPossible');
  if(!grid) return;

  const states = BADGES.map(b => computeDynamicBadgeState(b.dynamic));

  grid.innerHTML = BADGES.map((b, i) => {
    const state = states[i];
    const accentColor = badgeAccentColor(b);
    return `
    <div class="badge-card ${b.rarity} badge-${b.id} ${state.unlocked ? '' : 'is-locked'}" style="--rarity-color:${accentColor};">
      <div class="badge-icon"><i class="${b.icon}"></i></div>
      <h3>${escapeHtml(b.name)}</h3>
      <p>${escapeHtml(b.desc)}</p>
      <span class="badge-status ${state.unlocked ? 'unlocked' : 'locked'}">${state.unlocked ? '<i class="fa-solid fa-pen"></i> Achieved' : '<i class="fa-solid fa-lock"></i> Locked'}</span>
      <div class="badge-progress"><div style="width:${state.progress}%;"></div></div>
    </div>
  `;
  }).join('');

  const unlockedCount = states.filter(s => s.unlocked).length;
  if(totalEl) totalEl.textContent = unlockedCount;
  if(possibleEl) possibleEl.textContent = BADGES.length;

  // Detect newly-unlocked badges and queue a popup (colored to the
  // badge's rarity) for each.
  BADGES.forEach((b, i) => {
    if(states[i].unlocked && !previouslyUnlockedBadgeIds.has(b.id)){
      previouslyUnlockedBadgeIds.add(b.id);
      badgePopupQueue.push({
        type: 'badge',
        accent: badgeAccentColor(b),
        icon: b.icon,
        label: 'Badge Unlocked!',
        name: b.name,
        desc: b.desc
      });
    }
  });
  if(!badgePopupShowing) showNextBadgePopup();

  renderProgressPanel(states, unlockedCount);
  renderCrayons(); // new colors may have just unlocked alongside a badge
}

function renderProgressPanel(states, unlockedCount){
  const lettersTraced = tracedLetters.size;
  const totalLetters = ALPHABET.length;
  const pct = Math.round((lettersTraced / totalLetters) * 100);

  // circular ring
  const ringBar = document.getElementById('progressRingBar');
  const circumference = 326.7; // 2 * PI * 52
  if(ringBar){
    const offset = circumference - (pct / 100) * circumference;
    ringBar.style.strokeDashoffset = offset;
    ringBar.style.stroke = pct >= 100 ? '#4ade80' : 'var(--sky)';
  }
  const percentEl = document.getElementById('progressRingPercent');
  if(percentEl) percentEl.textContent = pct + '%';

  const lettersEl = document.getElementById('statLettersTraced');
  if(lettersEl) lettersEl.textContent = lettersTraced + ' / ' + totalLetters;

  const badgesEl = document.getElementById('statBadgesUnlocked');
  if(badgesEl) badgesEl.textContent = unlockedCount + ' / ' + BADGES.length;

  // per-badge mini progress — clicking one scrolls to its card on the right
  const list = document.getElementById('progressMiniList');
  if(list){
    list.innerHTML = BADGES.map((b, i) => {
      const state = states[i];
      return `
      <div class="progress-mini-item ${state.unlocked ? 'done' : ''}" onclick="scrollToBadgeCard(${i})">
        <div class="progress-mini-top">
          <span><i class="fa-solid ${state.unlocked ? 'fa-circle-check' : 'fa-circle'}"></i>${escapeHtml(b.name)}</span>
          <span>${state.progress}%</span>
        </div>
        <div class="progress-mini-bar"><div style="width:${state.progress}%;"></div></div>
      </div>
    `;
    }).join('');
  }
}

function scrollToBadgeCard(index){
  const cards = document.querySelectorAll('#badgeGrid .badge-card');
  const card = cards[index];
  if(card){
    card.scrollIntoView({ behavior: 'smooth', block: 'center' });
    card.style.transition = 'box-shadow 0.3s ease';
    card.style.boxShadow = '0 0 0 3px rgba(56,189,248,0.5), 0 16px 32px rgba(56,189,248,0.25)';
    setTimeout(() => { card.style.boxShadow = ''; }, 900);
  }
}

function showNextBadgePopup(){
  if(badgePopupQueue.length === 0){
    badgePopupShowing = false;
    return;
  }
  badgePopupShowing = true;
  const item = badgePopupQueue.shift();

  const overlay = document.getElementById('badgePopupOverlay');
  const card = document.getElementById('badgePopupCard');
  const iconEl = document.getElementById('badgePopupIcon');
  const labelEl = document.getElementById('badgePopupLabel');
  const nameEl = document.getElementById('badgePopupName');
  const descEl = document.getElementById('badgePopupDesc');
  const swatchEl = document.getElementById('badgePopupSwatch');
  if(!overlay || !card) return;

  card.style.setProperty('--popup-accent', item.accent || '#4ade80');
  card.classList.toggle('is-chalk', item.type === 'chalk');

  iconEl.innerHTML = `<i class="${item.icon}"></i>`;
  labelEl.textContent = item.label || 'Unlocked!';
  nameEl.textContent = item.name;
  descEl.textContent = item.desc;
  if(swatchEl) swatchEl.style.background = item.accent || '';

  overlay.classList.add('show');
  burstSparks(); // reuse the existing sparkle effect for extra celebration

  setTimeout(() => {
    overlay.classList.remove('show');
    setTimeout(showNextBadgePopup, 300); // let the fade-out finish, then show next
  }, 2200);
}

/*==========================================================
    CLEAR PROGRESS — dustbin icon in the progress panel opens
    a themed confirm popup (same visual family as the badge
    popup), and on confirm wipes both the server-side rows and
    all local in-memory state, then shows a green "cleared" toast.
==========================================================*/
function openClearConfirm(){
  const overlay = document.getElementById('clearConfirmOverlay');
  if(overlay) overlay.classList.add('show');
}

function closeClearConfirm(){
  const overlay = document.getElementById('clearConfirmOverlay');
  if(overlay) overlay.classList.remove('show');
}

async function clearProgress(){
  const overlay = document.getElementById('clearConfirmOverlay');
  const clearBtn = overlay ? overlay.querySelector('.confirm-clear-btn') : null;
  if(clearBtn){
    clearBtn.disabled = true;
    clearBtn.textContent = 'Clearing…';
  }

  try{
    const res = await fetch('../playgame-api/clear_progress.php', { method: 'POST' });
    const data = await res.json();

    if(!res.ok || !data || data.success !== true){
      console.error('Could not clear progress:', data && data.error);
      if(clearBtn){
        clearBtn.disabled = false;
        clearBtn.textContent = 'Yes, clear it';
      }
      alert("Something went wrong clearing your progress. Please try again.");
      return;
    }

    // Reset all local state
    tracedLetters.clear();
    tracedUpper.clear();
    tracedLower.clear();
    previouslyUnlockedBadgeIds.clear();
    previouslyUnlockedCrayonHexes.clear();

    // Re-seed the free starting chalk colors so they don't re-trigger unlock popups
    CRAYONS.forEach(c => {
      if(!c.unlock) previouslyUnlockedCrayonHexes.add(c.hex);
    });

    // If active color was unlocked via a badge, fall back to default now that it's re-locked
    const activeCrayon = CRAYONS.find(c => c.hex === activeColor);
    if(activeCrayon && !isCrayonUnlocked(activeCrayon)){
      activeColor = CRAYONS[0].hex;
    }

    document.getElementById("tracedCount").textContent = tracedLetters.size;

    refreshAll();
    renderBadges();

    closeClearConfirm();
    if(clearBtn){
      clearBtn.disabled = false;
      clearBtn.textContent = 'Yes, clear it';
    }

    // Reuse the achievement popup, tinted green, as a "cleared" confirmation toast
    badgePopupQueue.push({
      type: 'clear',
      accent: '#4ade80',
      icon: 'fa-solid fa-check',
      label: 'All Clear!',
      name: 'Progress reset',
      desc: 'Your tracing board is fresh — start whenever you\'re ready.'
    });
    if(!badgePopupShowing) showNextBadgePopup();

  } catch(err){
    console.error('Could not reach clear_progress.php:', err);
    if(clearBtn){
      clearBtn.disabled = false;
      clearBtn.textContent = 'Yes, clear it';
    }
    alert("Could not reach the server. Please check your connection and try again.");
  }
}

async function init(){
  sizeCanvases();
  attachHandlers();

  // Pull this user's saved progress from the database BEFORE the first
  // render, so returning users see their traced letters/badges/chalk
  // colors immediately instead of starting from zero.
  await loadProgress();

  refreshAll();
  document.getElementById('tracedCount').textContent = tracedLetters.size;

  // Seed the "already unlocked" trackers from the restored progress so
  // the achievement popup doesn't replay every badge/chalk unlock the
  // user already earned in a previous session.
  BADGES.forEach(b => {
    if(computeDynamicBadgeState(b.dynamic).unlocked){
      previouslyUnlockedBadgeIds.add(b.id);
    }
  });
  CRAYONS.forEach(c => {
    if(isCrayonUnlocked(c)){
      previouslyUnlockedCrayonHexes.add(c.hex);
    }
  });

  // let the player dismiss the achievement popup early by tapping it
  const overlay = document.getElementById('badgePopupOverlay');
  if(overlay){
    overlay.addEventListener('click', () => {
      overlay.classList.remove('show');
      setTimeout(showNextBadgePopup, 300);
    });
  }

  // dismiss the clear-progress confirm by tapping its backdrop
  const clearOverlay = document.getElementById('clearConfirmOverlay');
  if(clearOverlay){
    clearOverlay.addEventListener('click', (e) => {
      if(e.target.id === 'clearConfirmOverlay') closeClearConfirm();
    });
  }

  renderBadges(); // also renders progress panel + crayons on first load
  chalkPopupsArmed = true; // from here on, newly-unlocked colors get a popup
}

window.addEventListener('load', init);
window.addEventListener('resize', () => { sizeCanvases(); drawGuideLetter(); drawPreviewLetter(); });

</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>