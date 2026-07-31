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
    <title>SafeKidsSpace — Choose a Quiz</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/layout.css">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="container">
<main class="main-content" id="content">
  <!-- ===== BANNER — reuses index.php's exact .carousel-container / .slide
         component from layout.css, rendered here as a single static slide
         (no dots/arrows needed since there's only one).
         TO CHANGE THE PICTURE: replace '../images/quizbanner.png' below with
         your own image's filename (upload it into your images/ folder first). ===== -->
    <div class="carousel-container" style="margin-bottom: 40px;">
        <div class="carousel-slides" style="width: 100%;">
            <div class="slide" style="width: 100%; background-image: url('../images/quizbanner.png');">
                <div class="slide-content">
                    <span class="slide-tag">🎯 PLAY &amp; LEARN</span>
                    <h1>Choose a Quiz</h1>
                    <p>Test your knowledge, improve your skills &amp; earn badges across 6 fun categories.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="qc-grid">
        <a href="quiz_play.php?category=iq" class="qc-card iq">
            <img src="../images/geobanner.png" alt="IQ Quiz">
            <div class="qc-card-title">IQ Quiz</div>
        </a>

        <a href="quiz_play.php?category=geography" class="qc-card geography">
            <img src="../images/geo2banner.png" alt="Geography Quiz">
            <div class="qc-card-title">Geography Quiz</div>
        </a>

        <a href="quiz_play.php?category=science" class="qc-card science">
            <img src="../images/single.png" alt="Science Quiz">
            <div class="qc-card-title">Science Quiz</div>
        </a>

        <a href="quiz_play.php?category=english" class="qc-card english">
            <img src="../images/engbanner.png" alt="English Quiz">
            <div class="qc-card-title">English Quiz</div>
        </a>

        <a href="quiz_play.php?category=generalknowledge" class="qc-card gk">
            <img src="../images/quiz.png" alt="General Knowledge Quiz">
            <div class="qc-card-title">General Knowledge Quiz</div>
        </a>

        <a href="quiz_play.php?category=coding" class="qc-card coding">
            <img src="../images/coding.png" alt="Coding Quiz">
            <div class="qc-card-title">Coding Quiz</div>
        </a>
    </div>

    <div class="qc-lb-section">
        <h2 class="qc-lb-title">🏆 Leaderboard</h2>
        <div class="qc-lb-tabs">
            <button class="qc-lb-tab active" data-category="iq">IQ</button>
            <button class="qc-lb-tab" data-category="geography">Geography</button>
            <button class="qc-lb-tab" data-category="science">Science</button>
            <button class="qc-lb-tab" data-category="english">English</button>
            <button class="qc-lb-tab" data-category="generalknowledge">General Knowledge</button>
            <button class="qc-lb-tab" data-category="coding">Coding</button>
        </div>

        <div id="qcMyRank" class="qc-my-rank">Loading your rank…</div>

        <table class="qc-lb-table">
            <thead>
                <tr><th>#</th><th>Player</th><th>Score</th></tr>
            </thead>
            <tbody id="qcLeaderboardBody">
                <tr><td colspan="3">Loading…</td></tr>
            </tbody>
        </table>
    </div>

</main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script>
async function loadCategoryLeaderboard(category) {
    const rankBox = document.getElementById('qcMyRank');
    const tbody   = document.getElementById('qcLeaderboardBody');

    rankBox.textContent = 'Loading your rank…';
    tbody.innerHTML = '<tr><td colspan="3">Loading…</td></tr>';

    try {
        const [scoresRes, rankRes] = await Promise.all([
            fetch('quiz_api.php?get_scores=1&category=' + encodeURIComponent(category)),
            fetch('quiz_api.php?get_rank=1&category=' + encodeURIComponent(category))
        ]);
        const scores = await scoresRes.json();
        const rankData = await rankRes.json();

        tbody.innerHTML = scores.length
            ? scores.map((row, i) => `<tr><td>#${i + 1}</td><td>${row.username}</td><td>${row.score}</td></tr>`).join('')
            : '<tr><td colspan="3">No scores yet — be the first to play!</td></tr>';

        rankBox.textContent = rankData.rank
            ? `Your Rank: #${rankData.rank} of ${rankData.total} (Best Score: ${rankData.best_score})`
            : `You haven't played this quiz yet — give it a try!`;
    } catch (e) {
        rankBox.textContent = '';
        tbody.innerHTML = '<tr><td colspan="3">Could not load leaderboard right now.</td></tr>';
    }
}

document.querySelectorAll('.qc-lb-tab').forEach(tab => {
    tab.addEventListener('click', function () {
        document.querySelectorAll('.qc-lb-tab').forEach(t => t.classList.remove('active'));
        this.classList.add('active');
        loadCategoryLeaderboard(this.dataset.category);
    });
});

loadCategoryLeaderboard('iq');
</script>
</body>
</html>