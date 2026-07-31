<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links
 include __DIR__ . '/../includes/navbar.php'; ?>
<?php
$current_page = 'wordgame.php';
?> 


<!DOCTYPE html>
<html lang="en">

<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>


<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Safe Kids Space | Word Search</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">

<!-- Bootstrap -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.6.0/css/all.min.css">

<link rel="stylesheet" href="../assets/layout.css">
<link rel="stylesheet" href="../assets/games.css">


</head>

<body class="wordgame-page">

<div class="container">
<div id="mainContent">

<div class="game-wrapper">

<section class="hero" id="heroBanner">
  <div class="hero-copy">
    <span class="hero-eyebrow"><i class="fa-solid fa-satellite"></i> Learning Zone · Games</span>
    <h1>Explore the <span>Galaxy Word Search</span></h1>
    <p>Sweep the star chart, find every hidden word, and beat the clock before your mission timer runs out.</p>
    <div class="hero-stats">
      <div>
        <i class="fa-solid fa-shield-heart"></i>
        <div><strong>Kid-Safe</strong><span>No ads, no chat</span></div>
      </div>
      <div>
        <i class="fa-solid fa-stopwatch"></i>
        <div><strong>5 Minutes</strong><span>Per mission</span></div>
      </div>
      <div>
        <i class="fa-solid fa-star"></i>
        <div><strong>+10 pts</strong><span>Per word found</span></div>
      </div>
    </div>
  </div>

</section>

<header>

<div class="title">
<div class="badge"><i class="fa-solid fa-puzzle-piece"></i></div>
<div>
<h1>Word Search</h1>
<p>Find every hidden word before the timer ends by dragging.</p>
</div>
</div>

<div class="header-buttons">

<button id="newGame">
<i class="fa-solid fa-rotate"></i>
New Game
</button>

<button id="hint">
<i class="fa-solid fa-lightbulb"></i>
Hint
</button>

</div>

</header>

<svg width="0" height="0" style="position:absolute">
  <defs>
    <linearGradient id="timerGradient" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#b79bff"/>
      <stop offset="50%" stop-color="#3fe0c5"/>
      <stop offset="100%" stop-color="#ffcf56"/>
    </linearGradient>
  </defs>
</svg>

<section class="dashboard">

  <div class="card card--timer" id="timerCard">
    <span class="rivet tl"></span><span class="rivet tr"></span><span class="rivet bl"></span><span class="rivet br"></span>
    <h3><i class="fa-solid fa-stopwatch"></i> Timer</h3>
    <div class="timer-ring-wrap">
      <svg class="timer-ring" viewBox="0 0 120 120">
        <circle class="ring-track" cx="60" cy="60" r="52"></circle>
        <circle class="ring-progress" id="ringProgress" cx="60" cy="60" r="52"></circle>
      </svg>
      <span id="timer">05:00</span>
    </div>
  </div>

<div class="card card--score" id="scoreCard">
  <span class="rivet tl"></span><span class="rivet tr"></span><span class="rivet bl"></span><span class="rivet br"></span>
  <h3><i class="fa-solid fa-star"></i> Score</h3>
  <div class="score-value-wrap">
    <span id="score">0</span>
    <div class="score-pop" id="scorePop"></div>
  </div>
</div>

  <div class="card card--found">
    <span class="rivet tl"></span><span class="rivet tr"></span><span class="rivet bl"></span><span class="rivet br"></span>
    <h3><i class="fa-solid fa-check"></i> Found</h3>
    <span><span id="foundCount">0</span>/<span id="totalWords">10</span></span>
    <div class="found-dots" id="foundDots"></div>
  </div>

</section>

<div class="game-layout">

<aside class="game-sidebar">

<div class="sidebar-card">
<h2><i class="fa-solid fa-list-check"></i> Words</h2>
<ul id="wordList"></ul>
</div>

<div class="sidebar-card">

    <div class="progress-header">
        <h2><i class="fa-solid fa-trophy"></i> Progress</h2>
        <span id="progressText">0%</span>
    </div>

    <div class="progress-bar">
        <div id="progressFill"></div>
        <div class="progress-rocket" id="progressRocket"><i class="fa-solid fa-rocket"></i></div>
    </div>

</div>

</aside>

<main class="game-area">
<div id="grid"></div>
</main>

</div>

<div class="page-caption">
Safe Kids Space • Galaxy Word Search
</div>

</div>

<!-- WIN MODAL -->
<div class="modal" id="winModal">
<div class="modal-content" id="winModalContent">
<i class="fa-solid fa-medal win-icon"></i>
<h2>Congratulations!</h2>
<p>You found every word!</p>
<button id="playAgain">Play Again</button>
</div>
</div>

<!-- TIME'S UP MODAL -->
<div class="modal" id="timeUpModal">
<div class="modal-content" id="timeUpModalContent">
<div class="timeup-icon-wrap">
  <i class="fa-solid fa-hourglass-end timeup-icon"></i>
</div>
<h2>Time's Up!</h2>
<p class="timeup-summary-text">Mission clock ran out. Here's your report:</p>
<div class="timeup-stats">
  <div class="timeup-stat stat-found">
    <span class="stat-val" id="timeUpFound">0</span>
    <span class="stat-label">Found</span>
  </div>
  <div class="timeup-stat stat-missed">
    <span class="stat-val" id="timeUpMissed">0</span>
    <span class="stat-label">Missed</span>
  </div>
  <div class="timeup-stat stat-score">
    <span class="stat-val" id="timeUpScore">0</span>
    <span class="stat-label">Score</span>
  </div>
  <div class="timeup-stat stat-time">
    <span class="stat-val" id="timeUpTime">5:00</span>
    <span class="stat-label">Used</span>
  </div>
</div>
<button id="tryAgain">Restart Game</button>
</div>
</div>

<script>

const GRID_SIZE = 15;

/* ---- Hero banner dot controls (single-slide, kept for visual consistency with site nav) ---- */
(function initHeroControls(){
  const dots = document.querySelectorAll("#heroBanner .hero-dots .dot");
  if (!dots.length) return;

  dots.forEach((dot, i) => dot.addEventListener("click", () => {
    dots.forEach((d, idx) => d.classList.toggle("active", idx === i));
  }));
})();

const FALLBACK_WORDS = {
  Space: ["STAR", "MOON", "ORBIT", "COMET", "PLANET", "ROCKET", "GALAXY", "NEBULA", "ASTEROID", "SATURN"]
};

const grid = document.getElementById("grid");
const wordListEl = document.getElementById("wordList");
const totalWordsEl = document.getElementById("totalWords");
const foundCountEl = document.getElementById("foundCount");
const scoreEl = document.getElementById("score");
const progressFill = document.getElementById("progressFill");
const progressText = document.getElementById("progressText");
const progressRocket = document.getElementById("progressRocket");
const timerEl = document.getElementById("timer");
const winModal = document.getElementById("winModal");
const winModalContent = document.getElementById("winModalContent");
const timeUpModal = document.getElementById("timeUpModal");
const timeUpFoundEl = document.getElementById("timeUpFound");
const timeUpMissedEl = document.getElementById("timeUpMissed");
const timeUpScoreEl = document.getElementById("timeUpScore");
const timeUpTimeEl = document.getElementById("timeUpTime");

let gridData = [];
let placedWords = [];
let words = [];
let score = 0;
let foundWordsCount = 0;

let isSelecting = false;
let startCell = null;
let currentCell = null;

let timeLeft = 300;
let timerInterval = null;
let gameActive = true;

window.addEventListener("load", async () => {
  const saved = await loadGameProgress();
  if (saved && Array.isArray(saved.placedWords) && saved.placedWords.length > 0 && saved.timeLeft > 0) {
    restoreGame(saved);
  } else {
    loadWords("Space");
    startTimer();
  }
});

document.getElementById("newGame").addEventListener("click", () => {
  loadWords("Space");
  startTimer();
});

document.getElementById("playAgain").addEventListener("click", () => {
  winModal.classList.remove("active");
  clearConfetti();
  loadWords("Space");
  startTimer();
});

document.getElementById("hint").addEventListener("click", giveHint);

document.getElementById("tryAgain").addEventListener("click", () => {
  timeUpModal.classList.remove("active");
  loadWords("Space");
  startTimer();
});

async function loadWords(category) {
  try {
    const response = await fetch("../playgame-api/get_words.php?action=getWords&category=" + category);
    const data = await response.json();
    if (Array.isArray(data) && data.length > 0) {
      words = data;
    } else {
      throw new Error("Empty or invalid word list from server");
    }
  } catch (error) {
    console.warn("Falling back to local word list:", error);
    words = FALLBACK_WORDS[category] || FALLBACK_WORDS.Space;
  }

  words = words.map(w => w.toUpperCase()).filter(w => w.length <= GRID_SIZE);

  resetState();
  generatePuzzle();
  displayWordList();
  drawGrid();
  updateProgress();
  saveGameProgress();
}

function resetState() {
  score = 0;
  foundWordsCount = 0;
  scoreEl.textContent = "0";
  gameActive = true;
  placedWords = [];
}

function createEmptyGrid() {
  gridData = [];
  for (let r = 0; r < GRID_SIZE; r++) {
    gridData[r] = new Array(GRID_SIZE).fill("");
  }
}

function generatePuzzle() {
  createEmptyGrid();
  placedWords = [];

  const sorted = [...words].sort((a, b) => b.length - a.length);
  sorted.forEach(word => placeWord(word));

  fillEmptyCells();
}

const DIRECTIONS = [
  { dr: 0, dc: 1 },
  { dr: 1, dc: 0 },
  { dr: 1, dc: 1 },
  { dr: -1, dc: 1 }
];

function placeWord(word) {
  let attempts = 0;
  let placed = false;

  while (!placed && attempts < 300) {
    attempts++;

    const dirIndex = Math.floor(Math.random() * DIRECTIONS.length);
    const { dr, dc } = DIRECTIONS[dirIndex];

    const row = Math.floor(Math.random() * GRID_SIZE);
    const col = Math.floor(Math.random() * GRID_SIZE);

    if (canPlaceWord(word, row, col, dr, dc)) {
      const cells = insertWord(word, row, col, dr, dc);
      placedWords.push({ word, cells, found: false });
      placed = true;
    }
  }

  if (!placed) {
    console.warn(`Could not place word: ${word}`);
  }
}

function canPlaceWord(word, row, col, dr, dc) {
  for (let i = 0; i < word.length; i++) {
    const r = row + dr * i;
    const c = col + dc * i;

    if (r < 0 || r >= GRID_SIZE || c < 0 || c >= GRID_SIZE) return false;

    const existing = gridData[r][c];
    if (existing !== "" && existing !== word[i]) return false;
  }
  return true;
}

function insertWord(word, row, col, dr, dc) {
  const cells = [];
  for (let i = 0; i < word.length; i++) {
    const r = row + dr * i;
    const c = col + dc * i;
    gridData[r][c] = word[i];
    cells.push({ row: r, col: c });
  }
  return cells;
}

function fillEmptyCells() {
  const alphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
  for (let r = 0; r < GRID_SIZE; r++) {
    for (let c = 0; c < GRID_SIZE; c++) {
      if (gridData[r][c] === "") {
        gridData[r][c] = alphabet[Math.floor(Math.random() * alphabet.length)];
      }
    }
  }
}

function drawGrid() {
  grid.innerHTML = "";
  for (let r = 0; r < GRID_SIZE; r++) {
    for (let c = 0; c < GRID_SIZE; c++) {
      const cell = document.createElement("div");
      cell.className = "cell";
      cell.dataset.row = r;
      cell.dataset.col = c;
      cell.textContent = gridData[r][c];
      grid.appendChild(cell);
    }
  }

  grid.addEventListener("mousedown", handleStart);
  grid.addEventListener("mousemove", handleMove);
  document.addEventListener("mouseup", handleEnd);

  grid.addEventListener("touchstart", handleTouchStart, { passive: false });
  grid.addEventListener("touchmove", handleTouchMove, { passive: false });
  document.addEventListener("touchend", handleEnd);
}

function displayWordList() {
  wordListEl.innerHTML = "";
  totalWordsEl.textContent = words.length;
  foundCountEl.textContent = "0";

  words.forEach(word => {
    const li = document.createElement("li");
    li.textContent = word;
    li.id = "word-" + word;
    wordListEl.appendChild(li);
  });
}

function cellFromEvent(e) {
  const target = e.target.closest ? e.target.closest(".cell") : null;
  return target && target.classList.contains("cell") ? target : null;
}

function handleStart(e) {
  if (!gameActive) return;
  const cell = cellFromEvent(e);
  if (!cell) return;

  isSelecting = true;
  startCell = cell;
  currentCell = cell;
  highlightPath();
}

function handleMove(e) {
  if (!isSelecting) return;
  const cell = cellFromEvent(e);
  if (!cell) return;

  currentCell = cell;
  highlightPath();
}

function handleTouchStart(e) {
  if (!gameActive) return;
  const touch = e.touches[0];
  const el = document.elementFromPoint(touch.clientX, touch.clientY);
  if (!el || !el.classList.contains("cell")) return;

  e.preventDefault();
  isSelecting = true;
  startCell = el;
  currentCell = el;
  highlightPath();
}

function handleTouchMove(e) {
  if (!isSelecting) return;
  const touch = e.touches[0];
  const el = document.elementFromPoint(touch.clientX, touch.clientY);
  if (!el || !el.classList.contains("cell")) return;

  e.preventDefault();
  currentCell = el;
  highlightPath();
}

function handleEnd() {
  if (!isSelecting) return;
  isSelecting = false;
  validateSelection();
}

function getLinePath() {
  if (!startCell || !currentCell) return [];

  const r1 = parseInt(startCell.dataset.row);
  const c1 = parseInt(startCell.dataset.col);
  const r2 = parseInt(currentCell.dataset.row);
  const c2 = parseInt(currentCell.dataset.col);

  const dr = r2 - r1;
  const dc = c2 - c1;

  const isHorizontal = dr === 0 && dc !== 0;
  const isVertical = dc === 0 && dr !== 0;
  const isDiagonal = Math.abs(dr) === Math.abs(dc) && dr !== 0;

  if (!isHorizontal && !isVertical && !isDiagonal) {
    return r1 === r2 && c1 === c2 ? [{ row: r1, col: c1 }] : [];
  }

  const steps = Math.max(Math.abs(dr), Math.abs(dc));
  const stepR = dr === 0 ? 0 : dr / Math.abs(dr);
  const stepC = dc === 0 ? 0 : dc / Math.abs(dc);

  const path = [];
  for (let i = 0; i <= steps; i++) {
    path.push({ row: r1 + stepR * i, col: c1 + stepC * i });
  }
  return path;
}

function highlightPath() {
  clearSelectedHighlight();
  const path = getLinePath();
  path.forEach(({ row, col }) => {
    const cellEl = grid.children[row * GRID_SIZE + col];
    if (cellEl) cellEl.classList.add("selected");
  });
}

function clearSelectedHighlight() {
  grid.querySelectorAll(".cell.selected").forEach(c => c.classList.remove("selected"));
}

function validateSelection() {
  const path = getLinePath();
  clearSelectedHighlight();

  if (path.length < 2) return;

  const selectedWord = path.map(({ row, col }) => gridData[row][col]).join("");
  const reversedWord = selectedWord.split("").reverse().join("");

  const match = placedWords.find(pw =>
    !pw.found && (pw.word === selectedWord || pw.word === reversedWord) &&
    cellsMatch(pw.cells, path)
  );

  if (match) {
    markWordFound(match, path);
  }
}

function cellsMatch(wordCells, path) {
  if (wordCells.length !== path.length) return false;
  const forward = wordCells.every((cell, i) => cell.row === path[i].row && cell.col === path[i].col);
  const backward = wordCells.every((cell, i) => cell.row === path[path.length - 1 - i].row && cell.col === path[path.length - 1 - i].col);
  return forward || backward;
}

function markWordFound(match, path) {
  match.found = true;
  foundWordsCount++;
  score += 10;
  scoreEl.textContent = score;
  foundCountEl.textContent = foundWordsCount;

  scoreEl.classList.add("bump");
  setTimeout(() => scoreEl.classList.remove("bump"), 300);

  const pop = document.getElementById("scorePop");
  if (pop) {
    pop.textContent = "+10";
    pop.classList.remove("show");
    void pop.offsetWidth;
    pop.classList.add("show");
  }

  path.forEach(({ row, col }) => {
    const cellEl = grid.children[row * GRID_SIZE + col];
    if (cellEl) cellEl.classList.add("found");
  });

  const li = document.getElementById("word-" + match.word);
  if (li) li.classList.add("found");

  updateFoundDots();
  updateProgress();
  saveGameProgress();

  if (foundWordsCount === placedWords.length) {
    gameWon();
  }
}

function updateFoundDots() {
  const wrap = document.getElementById("foundDots");
  if (!wrap) return;
  wrap.innerHTML = "";
  placedWords.forEach(pw => {
    const dot = document.createElement("span");
    dot.className = "found-dot" + (pw.found ? " filled" : "");
    wrap.appendChild(dot);
  });
}

function updateProgress() {
  const total = placedWords.length || words.length || 1;
  const percent = Math.round((foundWordsCount / total) * 100);
  progressFill.style.width = percent + "%";
  progressText.textContent = percent + "%";
  if (progressRocket) progressRocket.style.left = percent + "%";
}

function giveHint() {
  if (!gameActive) return;

  const unfound = placedWords.filter(pw => !pw.found);
  if (unfound.length === 0) return;

  const target = unfound[Math.floor(Math.random() * unfound.length)];
  const firstCell = target.cells[0];
  const cellEl = grid.children[firstCell.row * GRID_SIZE + firstCell.col];

  if (cellEl) {
    cellEl.classList.add("hint");
    setTimeout(() => cellEl.classList.remove("hint"), 1200);
  }

  score = Math.max(0, score - 2);
  scoreEl.textContent = score;
}

function startTimer(resumeFrom) {
  clearInterval(timerInterval);
  timeLeft = (typeof resumeFrom === "number" && resumeFrom > 0) ? resumeFrom : 300;
  gameActive = true;
  updateTimerDisplay();

  timerInterval = setInterval(() => {
    timeLeft--;
    updateTimerDisplay();

    if (timeLeft <= 0) {
      clearInterval(timerInterval);
      gameActive = false;
      timeUp();
    }
  }, 1000);
}

const RING_CIRC = 2 * Math.PI * 52;

function updateTimerDisplay() {
  const min = String(Math.floor(timeLeft / 60)).padStart(2, "0");
  const sec = String(timeLeft % 60).padStart(2, "0");
  timerEl.textContent = `${min}:${sec}`;

  const ringProgress = document.getElementById("ringProgress");
  const timerCard = document.getElementById("timerCard");
  const fraction = timeLeft / 300;

  if (ringProgress) ringProgress.style.strokeDashoffset = RING_CIRC * (1 - fraction);
  if (timerCard) timerCard.classList.toggle("urgent", timeLeft <= 30);
}

function timeUp() {
  showTimeUpModal();
}

function showTimeUpModal() {
  const total = placedWords.length;
  const found = foundWordsCount;
  const missed = total - found;

  if (timeUpFoundEl) timeUpFoundEl.textContent = found;
  if (timeUpMissedEl) timeUpMissedEl.textContent = missed;
  if (timeUpScoreEl) timeUpScoreEl.textContent = score;
  if (timeUpTimeEl) timeUpTimeEl.textContent = "5:00";

  if (timeUpModal) timeUpModal.classList.add("active");
}

function gameWon() {
  gameActive = false;
  clearInterval(timerInterval);
  winModal.classList.add("active");
  spawnConfetti();
  saveGameProgress();
}

const CONFETTI_COLORS = ["#ff7a68", "#3fe0c5", "#ffcf56", "#b79bff"];

function spawnConfetti() {
  clearConfetti();
  for (let i = 0; i < 26; i++) {
    const piece = document.createElement("span");
    piece.className = "confetti-piece";
    piece.style.left = Math.random() * 100 + "%";
    piece.style.background = CONFETTI_COLORS[i % CONFETTI_COLORS.length];
    piece.style.animationDuration = (1.4 + Math.random() * 1.2) + "s";
    piece.style.animationDelay = (Math.random() * 0.4) + "s";
    winModalContent.appendChild(piece);
  }
}

function clearConfetti() {
  winModalContent.querySelectorAll(".confetti-piece").forEach(p => p.remove());
}

const GAME_KEY_WORDSEARCH = "wordsearch";

function buildWordGameStateBody() {
  return new URLSearchParams({
    game: GAME_KEY_WORDSEARCH,
    state: JSON.stringify({ gridData, placedWords, words, score, foundWordsCount, timeLeft })
  });
}

function saveGameProgress() {
  try {
    fetch("../playgame-api/save_game_progress.php", {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded" },
      body: buildWordGameStateBody()
    }).catch(() => {});
  } catch (error) {
    console.warn("Could not save progress (not logged in or backend unavailable):", error);
  }
}

async function loadGameProgress() {
  try {
    const response = await fetch("../playgame-api/get_game_progress.php?game=" + GAME_KEY_WORDSEARCH);
    const data = await response.json();
    if (data.success && data.state) return data.state;
  } catch (error) {
    console.warn("Could not load progress:", error);
  }
  return null;
}

function restoreGame(state) {
  gridData = state.gridData;
  placedWords = state.placedWords;
  words = state.words;
  score = state.score || 0;
  foundWordsCount = state.foundWordsCount || 0;
  gameActive = true;

  scoreEl.textContent = score;
  drawGrid();
  displayWordList();

  placedWords.forEach(pw => {
    if (!pw.found) return;
    const li = document.getElementById("word-" + pw.word);
    if (li) li.classList.add("found");
    pw.cells.forEach(({ row, col }) => {
      const cellEl = grid.children[row * GRID_SIZE + col];
      if (cellEl) cellEl.classList.add("found");
    });
  });

  foundCountEl.textContent = foundWordsCount;
  updateFoundDots();
  updateProgress();
  startTimer(state.timeLeft);
}

// Autosave every 15s while the game is active, and once more right before the tab closes.
setInterval(() => {
  if (gameActive) saveGameProgress();
}, 15000);

window.addEventListener("beforeunload", () => {
  if (!gameActive) return;
  const body = buildWordGameStateBody();
  if (navigator.sendBeacon) {
    navigator.sendBeacon("../playgame-api/save_game_progress.php", body);
  } else {
    saveGameProgress();
  }
});
</script>
</div>
</div><!-- /.container -->

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>

</html>