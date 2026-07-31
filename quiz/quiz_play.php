<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
if (!isset($_SESSION['id'])) {
    header("Location: ../account/login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
 <link rel="stylesheet" href="../assets/layout.css">
 <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700&family=Orbitron:wght@500;700&display=swap"
        rel="stylesheet">

    <!-- FONT AWESOME -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
   <link rel="stylesheet" href="../assets/layout.css">
    <style>
        :root { --primary: #1a237e; --accent: #38bdf8; --hint: #facc15; --success: #34d399; --bg: #05070a; }

/* Quiz page just content wrapper */
.quiz-page {

    width: 100%;
}

.quiz-container {
    width: 100%;
    padding: 8px;
    box-sizing: border-box;
}
.quiz-body,
.stats-card {
    transition: transform 0.3s ease, width 0.3s ease;
}

.stats-card,
.quiz-body,
.badges-footer {
    width: 100%;
}
.stats-card {
    display: grid;
    grid-template-columns: repeat(4, 1fr);

    padding: 20px;
    border-radius: 26px;

    background: rgba(15, 23, 42, .88);

    color: white;

    position: relative;
    z-index: 5;

    margin-top: 24px;
    text-align:center;
    box-shadow: 0 4px 20px rgba(0,0,0,.25);
    border: 1px solid rgba(255,255,255,0.08);
}


.stats-card strong {
    color: #38bdf8;
    font-size: 0.9rem;
}

.stats-card span {
    font-size: 1.2rem;
    font-weight: bold;
}


.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 18px 40px rgba(0,0,0,0.2);
}


/* Make the timer stand out */
#timer {
    color: var(--hint);
    font-weight: bold;
    font-size: 1.2rem;
}
/* Add this to your style block */
.quiz-body {
min-height:400px;
 
    background: rgba(15, 23, 42, .88);

    color: white;
    border: 1px solid rgba(255,255,255,0.08);
    padding: 40px;
    border-radius: 26px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.25);
    margin-top: 20px;
        display: flex;
    flex-direction: column;
    gap: 20px;
}

#question {
    font-size: 1.5rem;
    color: #ffffff; /* Dark grey for readability on white background */
    margin-bottom: 25px;
    line-height: 1.4;
    text-align: center;
}
        
#options {
    display: flex;
    flex-direction: column;
    gap: 12px;
    width: 100%;
}
 .option-btn {
    width: 100%;
    padding: 16px 18px;
    border-radius: 16px;
    background: rgba(255,255,255,0.08);
    color: white;
    border: 1px solid rgba(255,255,255,0.15);

    font-size: 1.05rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.25s ease;
    box-shadow: 0 4px 10px rgba(0,0,0,0.05);
}
.option-btn:hover {
    transform: translateY(-3px);

    background: rgba(56, 189, 248, 0.15);
    border-color: rgba(56, 189, 248, 0.5);

    box-shadow: 0 0 20px rgba(56, 189, 248, 0.25);
    color: white;
}

.option-btn:active {
    transform: scale(0.98);
}
.correct {
    background: rgba(52, 211, 153, 0.16) !important;
    border-color: #34d399 !important;
    color: #6ee7b7 !important;
    box-shadow: 0 0 15px rgba(52, 211, 153, 0.25);
}

.wrong {
    background: rgba(248, 113, 113, 0.16) !important;
    border-color: #f87171 !important;
    color: #fca5a5 !important;
}

        .qz-controls { display: flex; justify-content: space-between; align-items: center; margin-top: 30px; }
    .qz-controls .qz-btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:8px;

    padding:14px 32px;
    border-radius:999px;
    font-size:1rem;
    font-weight:700;

    box-shadow:0 8px 20px rgba(0,0,0,.25);
}
  .qz-controls .qz-btn:hover{
    transform:translateY(-3px);
    box-shadow:0 12px 25px rgba(0,0,0,.35);
}
     .qz-controls .hint-btn { border: 2px solid var(--hint); color: var(--hint); background: white; animation: pulse 2s infinite; }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.05); } 100% { transform: scale(1); } }
        
        /* Footer Badges */
        .badges-footer { display: flex; gap: 20px; margin-top: 20px; }
        .badge-grid { display: flex; justify-content: space-between; margin-top: 15px; }/* Badge Styles */
.badge-item{
    display:flex;
    flex-direction:column;
    align-items:center;
    gap:10px;
    text-align:center;
}

.badge-name{
    font-size:0.9rem;
    font-weight:600;
    color:white;
}

.badge-item.unlocked { 
    opacity: 1; 
    filter: grayscale(0%); 
    transform: scale(1.1); 
}

/* Icon Styles */
.badge-icon{
    width:72px;
    height:72px;
       font-size:32px;
    color:#38bdf8;
    filter: drop-shadow(0 0 10px rgba(56,189,248,.7));
    border-radius:22px;

    background:linear-gradient(
        135deg,
        rgba(56,189,248,.25),
        rgba(255,255,255,.08)
    );

    border:1px solid rgba(255,255,255,.15);

    display:flex;
    align-items:center;
    justify-content:center;

    box-shadow:
        0 10px 20px rgba(0,0,0,.25),
        inset 0 1px 1px rgba(255,255,255,.15);

    transition:all .3s ease;
}

/* hover pop effect */
.badge-icon:hover {
    transform: scale(1.15);
    box-shadow: 0 12px 25px rgba(0,0,0,0.25);
}

/* click / active pop animation */
.badge-icon:active {
    animation: pop 0.25s ease;
}

@keyframes pop {
    0%   { transform: scale(1); }
    50%  { transform: scale(1.3); }
    100% { transform: scale(1); }
}
.badge-item.unlocked .badge-icon i{
    color: #34d399;
    text-shadow: 0 0 20px rgba(102,187,106,.8);
}
.footer-right {
    position: relative;
    width: 220px;
    height: 220px; /* Set a height to keep it a perfect square */
    border-radius: 24px;
    overflow: hidden; /* This clips the image to the rounded corners */
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 16px rgba(0,0,0,0.2);
}

.bg-img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover; /* This makes the image fill the area perfectly */
    z-index: 0;
}

.qz-content {
    position: relative;
    z-index: 1;
    background: rgba(0, 0, 0, 0.4); /* Darkens the image so text is readable */
    width: 100%;
    height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
    text-shadow: 0 2px 4px rgba(0,0,0,0.5);
}

.footer-right:hover {
    transform: translateY(-5px); /* Adds a snappy interactive feel */
}
.badges-left {
    flex: 1;
    padding: 25px;
    border-radius: 22px;

    background: rgba(15, 23, 42, .88);

    color: white;

    border: 1px solid rgba(255,255,255,0.08);
    box-shadow: 0 4px 20px rgba(0,0,0,.25);
}
.badge-banner-img {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    border: 3px solid rgba(255, 255, 255, 0.2);
    object-fit: cover; /* Ensures image doesn't stretch/distort */
    background: #fff;
}

.badge-modal{
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.8);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.badge-modal-content{
    width: 340px;
    padding: 30px;
    text-align: center;
    border-radius: 26px;

    background: rgba(15, 23, 42, .95);

    color: white;

    border: 1px solid rgba(56,189,248,.25);

    box-shadow:
        0 0 40px rgba(56,189,248,.25),
        0 15px 40px rgba(0,0,0,.5);

    animation: pop .4s ease;
}

#badgeIcon{
    display: flex;
    justify-content: center;
    margin-bottom: 15px;
}

#badgeIcon .badge-icon{
    width: 90px;
    height: 90px;
    font-size: 40px;
    transform: scale(1.2);
}

#badgeIcon .badge-icon i{
    color:#34d399 !important;
    text-shadow:0 0 20px rgba(102,187,106,.8);
}

#badgeName{
    font-size:1.5rem;
    font-weight:700;
    color:#34d399;
    margin:10px 0;
    font-family:'Orbitron',sans-serif;
}
.badge-modal-content h2{
    margin: 10px 0;
    color: #38bdf8;
    font-family: 'Orbitron', sans-serif;
}

.badge-modal-content button{
    margin-top: 15px;
    padding: 12px 28px;
    border: none;
    border-radius: 999px;
    cursor: pointer;
    color: white;
    font-weight: bold;

    background: linear-gradient(
        135deg,
        #38bdf8,
        #7c3aed
    );
}
.footer-right p {
    margin: 0;
    font-size: 0.9rem;
    font-weight: 600;
    letter-spacing: 0.5px;
}
/* Dark overlay to keep text readable */
.footer-right::before {
    content: "";
    position: absolute;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0, 0, 128, 0.7); /* Deep blue tint overlay */
    z-index: 0;
}


.badge-item.unlocked { 
    opacity: 1; 
    filter: grayscale(0%); 
    animation: pop 0.4s ease-out; /* Makes unlocking feel rewarding */
}

@media (max-width:768px){
    .badges-footer{
        flex-direction:column;
    }

    .stats-card{
        grid-template-columns:repeat(2,1fr);
    }
}

/* Replace your table styling with this */

#leaderboard-table {
    width: 100%;
    border-collapse: collapse;
    
}

#leaderboard-table td {
    padding: 12px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}


#leaderboard-table td:first-child {
    text-align: left;
}

#leaderboard-table td:last-child {
    text-align: right;
}
.final-score{
    text-align: center;
    font-family: 'Orbitron', sans-serif;
    font-size: 2rem;
    color: #38bdf8;
    margin: 10px 0 25px;
    text-shadow: 0 0 15px rgba(56,189,248,.5);
}

.submit-score-card{
    max-width: 450px;
    margin: 30px auto 0;
    padding: 25px;

    background: linear-gradient(
        135deg,
        rgba(255,255,255,0.05),
        rgba(255,255,255,0.02)
    );

    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 24px;

    text-align: center;

    box-shadow:
        0 0 25px rgba(56,189,248,.15),
        0 10px 25px rgba(0,0,0,.25);
}

.submit-score-card h3{
    margin-bottom: 20px;
    color: #fff;
    font-family: 'Orbitron', sans-serif;
}

.score-input{
    width: 100%;
    padding: 14px 18px;
    margin-bottom: 15px;

    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.15);

    background: rgba(255,255,255,0.08);
    color: #fff;
    font-size: 1rem;
    outline: none;
}

.score-input::placeholder{
    color: rgba(255,255,255,0.55);
}

.score-input:focus{
    border-color: #38bdf8;
    box-shadow: 0 0 15px rgba(56,189,248,.3);
}

.submit-btn{
    width: 100%;
    padding: 14px;

    border: none;
    border-radius: 999px;

    background: linear-gradient(
        135deg,
        #38bdf8,
        #1a73e8
    );

    color: white;
    font-size: 1rem;
    font-weight: 700;

    cursor: pointer;
    transition: all .3s ease;

    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;

    box-shadow:
        0 8px 20px rgba(56,189,248,.3);
}

.submit-btn:hover{
    transform: translateY(-3px);
    box-shadow:
        0 12px 25px rgba(56,189,248,.45);
}

.submit-btn:active{
    transform: scale(.98);
}

#modalMessage{
    font-size:1.1rem;
    line-height:1.6;
    margin-top:15px;
    color:white;
}
    </style>
</head>
<body>
    <div class="container">
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<main class="main-content quiz-page">
<div class="quiz-container">
    <!-- ===== BANNER — reuses index.php's exact .carousel-container / .slide
         component from layout.css (single static slide, no dots/arrows).
         Background image + title are swapped per-category by the JS below. ===== -->
    <div class="carousel-container" style="margin-bottom: 30px;">
        <div class="carousel-slides" style="width: 100%;">
            <div class="slide" id="quizSlide" style="width: 100%;">
                <div class="slide-content">
                    <span class="slide-tag">🎯 PLAY &amp; LEARN</span>
                    <h1 id="quizSlideTitle">IQ QUIZ</h1>
                    <p id="quizSlideDesc">Answer carefully, beat the clock, and earn your badge!</p>
                </div>
            </div>
        </div>
    </div>
    <div class="stats-card">
        <div><strong>Q</strong><br><span id="q-num">1/1</span></div>
        <div><strong>Score</strong><br><span id="score">0</span></div>
        <div><strong>Streak</strong><br><span id="streak">0</span></div>
<div><strong>Time</strong><br><span id="timer">4:00</span></div>
    </div>

    <div class="quiz-body" id="quiz-section">
        <h2 id="question">Loading...</h2>
        <div id="options"></div>
        <div class="qz-controls">
            <button class="qz-btn"  style="background: linear-gradient(135deg, #38bdf8, #7c3aed); color:white;" onclick="navigate(-1)">&#10094; Prev</button>
            <button class="qz-btn hint-btn" onclick="useHint()">💡 HINT</button>
            <button class="qz-btn" style="background: linear-gradient(135deg, #38bdf8, #7c3aed); color:white;" onclick="navigate(1)">Next  &#10095;</button>
        </div>
    </div>
    
<div id="leaderboard-section" class="quiz-body" style="display:none; margin-top:20px;">
    <h3>Top Scores</h3>
    <h2 class="final-score">
    🎯 Final Score: <span id="final-score">0</span>
</h2>
    <table id="leaderboard-table" style="width:100%; text-align:center;"></table>
   <div class="submit-score-card">
    <h3> Submit Your Score</h3>

    <p style="color:#94a3b8; margin:0 0 12px;">
        Playing as <strong style="color:#38bdf8;"><?= htmlspecialchars($_SESSION['fullname'] ?? 'Player') ?></strong>
    </p>

    <button class="submit-btn" onclick="submitScore()">
        <i class="fas fa-paper-plane"></i>
        Submit Score
    </button>
</div>
</div>

    <div class="badges-footer">
        <div class="badges-left">
            <div style="
            font-family: 'Orbitron', sans-serif;
font-weight:bold; color:var(--hint); font-size: 1.2rem; text-align: center;">EARN BADGES</div>
            <div class="badge-grid" id="badge-container">
    <div class="badge-item locked" id="badge-1"><div class="badge-icon"><i class="fas fa-seedling"></i> </div>Starter</div>
    <div class="badge-item locked" id="badge-2"><div class="badge-icon"><i class="fas fa-gem"></i> </div>Rising</div>
    <div class="badge-item locked" id="badge-3"><div class="badge-icon"><i class="fas fa-graduation-cap"></i></div>Smart</div>
    <div class="badge-item locked" id="badge-4"><div class="badge-icon"><i class="fas fa-trophy"></i></div>Genius</div>
    <div class="badge-item locked" id="badge-5"><div class="badge-icon"><i class="fas fa-rocket"></i> </div>Pro</div>
</div>
        </div>
     <div class="footer-right">
    <img src="../images/badgeimg.png" alt="Keep Learning" class="bg-img">
</div>
    </div>
</div>

<div id="badgeModal" class="badge-modal" style="display:none;">
    <div class="badge-modal-content">
      <div id="badgeIcon"></div>
        <h2>Badge Unlocked!</h2>

      <h3 id="badgeName"></h3>
<p id="badgeText"></p>
        <button onclick="closeBadgeModal()">Awesome!</button>
    </div>
</div>


<div id="quizModal" class="badge-modal" style="display:none;">
    <div class="badge-modal-content">
        <h2 id="modalTitle">Hint</h2>
        <p id="modalMessage"></p>
        <button onclick="closeQuizModal()">OK</button>
    </div>
</div>

<div id="timeUpModal" class="badge-modal" style="display:none;">
    <div class="badge-modal-content">
        <h2>⏰ Time's Up!</h2>
        <p>Your final score:</p>
        <h1 id="timeUpScore" style="color:#38bdf8;"></h1>
        <button onclick="showEndScreenFromModal()">
            View Results
        </button>
    </div>
</div>
</main>
<script>

    // Add this to your <script> section to load data on startup
let quizData = [];
let state = { currentQ: 0, score: 0, streak: 0 };
let timeLeft = 240; // 30 seconds per question
let timerInterval;
let answered = false;
const category =
    new URLSearchParams(window.location.search)
    .get('category') || 'iq';
async function init() {
    try {
        const res = await fetch(`quiz_api.php?get_questions=1&category=${category}`);

        if (!res.ok) throw new Error("Network error");

        const text = await res.text();
        console.log("RAW:", text);

        quizData = JSON.parse(text);   // 🔥 FIX HERE

        console.log("PARSED:", quizData);

        render();
        startTimer();

    } catch (e) {
        console.error(e);
        document.getElementById('question').innerText =
            "Failed to load quiz.";
    }
}

const banners = {
    iq: "../images/geobanner.png",
    geography: "../images/geo2banner.png",
    science: "../images/single.png",
    english: "../images/engbanner.png",
    generalknowledge: "../images/quiz.png",
    coding : "../images/coding.png"
};

const titles = {
    iq: "IQ QUIZ",
    geography: "GEOGRAPHY QUIZ",
    science: "SCIENCE QUIZ",
    english: "ENGLISH QUIZ",
    generalknowledge: "GENERAL KNOWLEDGE QUIZ",
    coding: "CODING QUIZ"
};

// Change banner image — layout.css's .slide::before already applies the
// same dark gradient overlay index.php's carousel uses, so we only need
// to set the plain background photo here (no extra gradients needed).
document.getElementById('quizSlide').style.backgroundImage = `url(${banners[category]})`;

// Change title
document.getElementById('quizSlideTitle').innerText =
    titles[category] || "QUIZ";


init();
function render() {

    if (!quizData || quizData.length === 0) {
        document.getElementById('question').innerText =
            "No questions found.";
        return;
    }

    const data = quizData[state.currentQ];

    document.getElementById('question').innerText =
        data.question;

    document.getElementById('q-num').innerText =
        `${state.currentQ + 1}/${quizData.length}`;

    document.getElementById('score').innerText =
        state.score;

    document.getElementById('streak').innerText =
        state.streak;

    const optionsDiv = document.getElementById('options');
    optionsDiv.innerHTML = '';

    const options = [
        data.option1,
        data.option2,
        data.option3,
        data.option4
    ];

    options.forEach((opt, idx) => {

        const btn = document.createElement('button');
        btn.className = 'option-btn';
        btn.innerText = opt;

        btn.onclick = () => {

            answered = true;

            const allBtns =
                optionsDiv.querySelectorAll('.option-btn');

            allBtns.forEach(b => b.disabled = true);

           const correctIndex =
    Number(data.correct_option) - 1;

if (isNaN(correctIndex) || correctIndex < 0 || correctIndex > 3) {
    console.error("Invalid correct answer:", data);
    return;
}
            if (idx === correctIndex) {

                btn.classList.add('correct');
                state.score += 10;
                state.streak++;

            } else {

                btn.classList.add('wrong');

                if (allBtns[correctIndex]) {
                    allBtns[correctIndex]
                        .classList.add('correct');
                }

                state.streak = 0;
            }

            document.getElementById('score').innerText =
                state.score;

            document.getElementById('streak').innerText =
                state.streak;

            updateBadges(state.score);
        };

        optionsDiv.appendChild(btn);
    });
}
function navigate(step) {

    if (step === 1 && !answered) {
        openQuizModal(
            "⚠ Question Not Answered",
            "Please answer the question before moving to the next one."
        );
        return;
    }

    if (step === 1 && state.currentQ === quizData.length - 1) {
        showEndScreen();
        return;
    }

    answered = false;

    const nextQ = state.currentQ + step;

    if (nextQ >= 0 && nextQ < quizData.length) {
        state.currentQ = nextQ;

        document.querySelector('.quiz-body').style.display = 'flex';
        document.getElementById('leaderboard-section').style.display = 'none';

        render();
    }
}

function showEndScreen() {
    clearInterval(timerInterval);
    document.getElementById('quizModal').style.display = 'none';
document.getElementById('quiz-section').style.display = 'none';
    document.getElementById('leaderboard-section').style.display = 'block';

    document.getElementById('final-score').textContent = state.score;

    loadLeaderboard();
}

    // 2. SUBMIT TO PHP
 async function submitScore() {
    const res = await fetch('quiz_api.php', {
        method: 'POST',
        body: new URLSearchParams({'score': state.score, 'category': category})
    });
    const data = await res.json();
    if (data.status === 'success') {
        openQuizModal("✅ Score Submitted", "Your score has been saved to the leaderboard!");
    } else {
        openQuizModal("⚠ Submission Failed", data.message || "Could not submit score. Please make sure you're signed in.");
    }
    loadLeaderboard();
}
 // 3. FETCH FROM PHP
async function loadLeaderboard() {
    // You MUST include the parameter 'get_scores=1' here, 
    // otherwise the PHP script might return the wrong data or nothing at all.
    const res = await fetch('quiz_api.php?get_scores=1&category=' + encodeURIComponent(category));
    const data = await res.json();
    const table = document.getElementById('leaderboard-table');
    table.innerHTML = data.map(row => `<tr><td>${row.username}</td><td>${row.score}</td></tr>`).join('');
}
function updateBadges(score) {
    const requirements = [10, 50, 100, 200, 500];

    requirements.forEach((req, index) => {
        const badgeId = `badge-${index + 1}`;
        const badge = document.getElementById(badgeId);

        if (badge && score >= req && !badge.classList.contains('unlocked')) {
            badge.classList.remove('locked');
            badge.classList.add('unlocked');

            showBadgeModal(badge);
        }
    });
}

function showBadgeModal(badge){

    document.getElementById('badgeName').innerText =
        badge.innerText.trim();

    document.getElementById('badgeText').innerText =
        "New achievement unlocked!";

    document.getElementById('badgeIcon').innerHTML =
        badge.querySelector('.badge-icon').outerHTML;

    document.getElementById('badgeModal').style.display = 'flex';
    
}


function closeBadgeModal() {
    document.getElementById('badgeModal').style.display = 'none';
}

function showTimeUpModal() {
    document.getElementById('timeUpScore').innerText = state.score;
    document.getElementById('timeUpModal').style.display = 'flex';
}

function showEndScreenFromModal() {
    document.getElementById('timeUpModal').style.display = 'none';
    showEndScreen();
}


function useHint() {
    const data = quizData[state.currentQ];

    if (data && data.hint) {
        openQuizModal("💡 Hint", data.hint);

        state.score = Math.max(0, state.score - 5);
        document.getElementById('score').innerText = state.score;
    } else {
        openQuizModal("Hint", "No hint available for this question.");
    }
}

function openQuizModal(title, message) {
    document.getElementById('modalTitle').innerText = title;
    document.getElementById('modalMessage').innerHTML = message;
    document.getElementById('quizModal').style.display = 'flex';
}

function closeQuizModal() {
    document.getElementById('quizModal').style.display = 'none';
}
function startTimer() {
    clearInterval(timerInterval);

    timeLeft = 240; // 4 minutes
    const timerDisplay = document.getElementById('timer');

    timerDisplay.style.color = "#facc15";
    timerDisplay.style.animation = "none";

    timerInterval = setInterval(() => {
        timeLeft--;

        const m = Math.floor(timeLeft / 60);
        const s = timeLeft % 60;

        timerDisplay.innerText =
            `${m}:${s.toString().padStart(2, '0')}`;

        // Warning when 30 seconds left
        if (timeLeft <= 30) {
            timerDisplay.style.color = "#ff5252";
            timerDisplay.style.animation = "pulse 1s infinite";
        }
if (timeLeft <= 0) {
    clearInterval(timerInterval);

    document.getElementById('modalTitle').innerText =
        "⏰ Time's Up!";

    document.getElementById('modalMessage').innerHTML =
        `Your final score is <b>${state.score}</b> points.<br><br>
         <button class="submit-btn" onclick="showEndScreen()">
            View Results
         </button>`;

    document.getElementById('quizModal').style.display = 'flex';
}

    }, 1000);
}

</script>

</div> 
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>