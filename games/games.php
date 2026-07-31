<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

if (session_status() === PHP_SESSION_NONE) { session_start(); }
$current_page = 'games.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Safe Kids Space - Galaxy Games</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&family=Orbitron:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../assets/layout.css">
<link rel="stylesheet" href="../assets/games.css">


</head>

<body class="games-page">

<?php require_once __DIR__ . '/../includes/navbar.php'; ?>

<div class="container">
<div class="main-content mb-5">

<!-- ===== HERO CAROUSEL (same component as index.php — reuses global .carousel-* classes from assets/layout.css, no new CSS needed) ===== -->
<div class="carousel-container">
    <div class="carousel-slides" id="gamesCarouselSlides">
        <div class="slide" style="background-image: url('../images/trace1.png');">
            <div class="slide-content">
                <span class="slide-tag"><i class="fa-solid fa-magnifying-glass"></i> Word Search</span>
                <h1>Find Hidden Words in the Galaxy</h1>
                <p>Sweep the star chart, build vocabulary, and beat the clock with exciting themed word puzzles.</p>
                <a href="wordgame.php" class="slide-btn slide-btn-primary"><i class="fa-solid fa-play"></i> Play Game</a>
            </div>
        </div>
        <div class="slide" style="background-image: url('../images/trace1.png');">
            <div class="slide-content">
                <span class="slide-tag"><i class="fa-solid fa-pencil"></i> Letter Tracing</span>
                <h1>Trace Your Way Through the Alphabet</h1>
                <p>Practice writing letters and words with fun tracing activities designed for early learners.</p>
                <a href="taceletter.php" class="slide-btn slide-btn-primary"><i class="fa-solid fa-pen"></i> Start Tracing</a>
            </div>
        </div>
        <div class="slide" style="background-image: url('../images/trace1.png');">
            <div class="slide-content">
                <span class="slide-tag"><i class="fa-solid fa-bolt"></i> Math Match</span>
                <h1>Match Math Questions with Answers</h1>
                <p>Explore different levels of the game and win exciting titles through your moves.</p>
                <a href="arcade.php" class="slide-btn slide-btn-primary"><i class="fa-solid fa-bolt"></i> Start Challenge</a>
            </div>
        </div>
    </div>
    <div class="carousel-btn prev" onclick="moveGamesSlide(-1)"><i class="fa-solid fa-chevron-left"></i></div>
    <div class="carousel-btn next" onclick="moveGamesSlide(1)"><i class="fa-solid fa-chevron-right"></i></div>
    <div class="carousel-dots" id="gamesCarouselDots">
        <span class="dot active" onclick="currentGamesSlide(0)"></span>
        <span class="dot" onclick="currentGamesSlide(1)"></span>
        <span class="dot" onclick="currentGamesSlide(2)"></span>
    </div>
</div>

<!-- ===== CARDS ===== -->
<section id="games">

    <div class="gg-section-title">
        <h2>Choose Your Adventure</h2>
        <p>Tap a game and start learning through play</p>
    </div>

    <div class="grid">

        <article class="gg-card word">
            <div class="gg-card-img-wrap">
                <img src="../images/wordsearch.png" alt="Word Search" >
            </div>
            <div class="gg-card-body">
                <div class="icon">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </div>
                <div class="gg-card-text">
                    <h3>Word Search</h3>
                    <p class="gg-card-desc">
                        Find hidden words, build vocabulary, and train your brain with exciting
                        puzzles and themed challenges.
                    </p>
                    <a href="wordgame.php" class="play">
                        <i class="fa-solid fa-play"></i>
                        Play Game
                    </a>
                </div>
            </div>
        </article>

        <article class="gg-card trace">
            <div class="gg-card-img-wrap">
                <img src="../images/banner10.png" alt="Letter Tracing">
            </div>
            <div class="gg-card-body">
                <div class="icon">
                    <i class="fa-solid fa-pencil"></i>
                </div>
                <div class="gg-card-text">
                    <h3>Letter Tracing</h3>
                    <p class="gg-card-desc">
                        Practice writing letters and words with fun tracing activities designed
                        for early learners and young writers.
                    </p>
                    <a href="taceletter.php" class="play">
                        <i class="fa-solid fa-pen"></i>
                        Start Tracing
                    </a>
                </div>
            </div>
        </article>

        <article class="gg-card difference">
            <div class="gg-card-img-wrap">
                <img src="../images/banner9.png" alt="Math Match">
            </div>
            <div class="gg-card-body">
                <div class="icon">
                    <i class="fa-solid fa-eye"></i>
                </div>
                <div class="gg-card-text">
                    <h3>Math Match</h3>
                    <p class="gg-card-desc">
                        Match math questions with their answers explore different levels of the game & win exiting titles through your moves.
                    </p>
                    <a href="arcade.php" class="play">
                        <i class="fa-solid fa-bolt"></i>
                        Start Challenge
                    </a>
                </div>
            </div>
        </article>

    </div>
</section>

<!-- ===== VIDEO ===== -->
<section id="video">

    <div class="gg-section-title">
        <h2>Featured Game Video</h2>
    </div>

    <div class="video-wrap">
        <div class="video-head">
            <div class="dots">
                <span class="gg-dot red"></span>
                <span class="gg-dot yellow"></span>
                <span class="gg-dot green"></span>
            </div>
            <strong>Galaxy Games Preview</strong>
        </div>

        <div style="padding-top:14px;">
            <div class="gg-video-card">
                <video autoplay muted loop playsinline poster="../images/banner.png">
    <source src="../video/kidsvideo.mp4" type="video/mp4">
</video>
            </div>
        </div>
    </div>

</section>



</div><!-- /.main-content -->
</div><!-- /.container -->

<script>
/* HERO CAROUSEL — same behaviour as index.php's carousel. Unique names
   (gamesSlideIndex, moveGamesSlide, etc.) so this can never clash with
   index.php's own carousel script if both were ever loaded together. */
let gamesSlideIndex = 0;
const gamesSlidesEl = document.getElementById('gamesCarouselSlides');
const gamesDots = document.querySelectorAll('#gamesCarouselDots .dot');
const gamesTotalSlides = 3;
let gamesSlideInterval;

function updateGamesCarousel() {
    gamesSlidesEl.style.transform = `translateX(-${gamesSlideIndex * 33.333}%)`;
    gamesDots.forEach((dot, idx) => dot.classList.toggle('active', idx === gamesSlideIndex));
}
function moveGamesSlide(direction) {
    gamesSlideIndex = (gamesSlideIndex + direction + gamesTotalSlides) % gamesTotalSlides;
    updateGamesCarousel();
    resetGamesAutoSlide();
}
function currentGamesSlide(index) {
    gamesSlideIndex = index;
    updateGamesCarousel();
    resetGamesAutoSlide();
}
function startGamesAutoSlide() {
    gamesSlideInterval = setInterval(() => moveGamesSlide(1), 6000);
}
function resetGamesAutoSlide() {
    clearInterval(gamesSlideInterval);
    startGamesAutoSlide();
}
window.addEventListener('DOMContentLoaded', startGamesAutoSlide);
</script>

<?PHP require_once __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>