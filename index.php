<?php
$base = ''; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/includes/db.php';

// ─────────────────────────────────────────────────────────────
// LIVE CLASSES — feature flag
// The full backend (admin permissions + teacher scheduling) is
// live and storing real data in the database. Flip this to true
// once you're ready to show real live classes to students.
// ─────────────────────────────────────────────────────────────
$live_classes_enabled = false;

$live_classes_feed = [];
if ($live_classes_enabled) {
    try {
        $live_feed_stmt = $pdo->query(
            "SELECT lc.id, lc.class_title, lc.subject_tag, lc.meeting_link, lc.scheduled_time, lc.status,
                    u.fullname AS teacher_name, u.profile_pic AS teacher_pic
             FROM live_classes lc
             JOIN users u ON u.id = lc.teacher_id
             WHERE lc.status IN ('Live', 'Scheduled')
             ORDER BY (lc.status = 'Live') DESC, lc.scheduled_time ASC
             LIMIT 4"
        );
        $live_classes_feed = $live_feed_stmt->fetchAll();
    } catch (PDOException $e) {
        $live_classes_feed = [];
    }
}

// ─────────────────────────────────────────────────────────────
// LIVE CLASSES WAITLIST — "Notify Me" button on the Coming Soon card
// ─────────────────────────────────────────────────────────────
if (!$live_classes_enabled && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'join_live_waitlist') {
    if (!isset($_SESSION['id'])) {
        header("Location: account/login.php");
        exit();
    }
    try {
        $join_stmt = $pdo->prepare("INSERT IGNORE INTO live_class_waitlist (user_id) VALUES (?)");
        $join_stmt->execute([$_SESSION['id']]);
    } catch (PDOException $e) {
        // table may not exist yet if migration hasn't run — fail silently
    }
    header("Location: index.php#liveClasses");
    exit();
}

$live_waitlist_count  = 0;
$already_on_waitlist  = false;
if (!$live_classes_enabled && isset($_SESSION['id'])) {
    try {
        $count_stmt = $pdo->query("SELECT COUNT(*) AS total FROM live_class_waitlist");
        $live_waitlist_count = $count_stmt->fetch()['total'] ?? 0;

        $check_stmt = $pdo->prepare("SELECT id FROM live_class_waitlist WHERE user_id = ? LIMIT 1");
        $check_stmt->execute([$_SESSION['id']]);
        $already_on_waitlist = (bool) $check_stmt->fetch();
    } catch (PDOException $e) {
        $live_waitlist_count = 0;
        $already_on_waitlist = false;
    }
} elseif (!$live_classes_enabled) {
    try {
        $count_stmt = $pdo->query("SELECT COUNT(*) AS total FROM live_class_waitlist");
        $live_waitlist_count = $count_stmt->fetch()['total'] ?? 0;
    } catch (PDOException $e) {
        $live_waitlist_count = 0;
    }
}



// ─────────────────────────────────────────────────────────────
// LEARNING ZONE — real programs + approved video counts,
// pulled straight from the same tables learning.php uses.
// ─────────────────────────────────────────────────────────────
$learning_zone_programs = [];
try {
    $lz_stmt = $pdo->query(
        "SELECT p.id, p.title, p.slug, p.icon, p.age_range,
                COUNT(pv.id) AS video_count
         FROM programs p
         LEFT JOIN program_videos pv ON pv.program_id = p.id AND pv.status = 'approved'
         WHERE p.status = 'active'
         GROUP BY p.id
         ORDER BY p.id ASC"
    );
    $learning_zone_programs = $lz_stmt->fetchAll();
} catch (PDOException $e) {
    $learning_zone_programs = [];
}

// ─────────────────────────────────────────────────────────────
// RECOMMENDED FOR YOU — latest 3 videos from the admin-managed
// `videos` table (same table admin_videos.php and videos.php use).
// ─────────────────────────────────────────────────────────────
$recommended_videos = [];
$rv_colors = ['#38bdf8', '#7c3aed', '#ec4899', '#22c55e', '#f97316'];
$rv_icons  = ['fa-solid fa-rocket', 'fa-solid fa-book', 'fa-solid fa-star', 'fa-solid fa-play', 'fa-solid fa-video'];
try {
    $rv_stmt = $pdo->query(
        "SELECT id, title, video_url, video_type, thumbnail_url
         FROM videos
         WHERE is_featured = 1
         ORDER BY id DESC
         LIMIT 3"
    );
    $recommended_videos = $rv_stmt->fetchAll();

    // Fallback: if admin hasn't manually featured any videos yet, show the latest 3
    // so the homepage section isn't empty.
    if (empty($recommended_videos)) {
        $rv_stmt2 = $pdo->query(
            "SELECT id, title, video_url, video_type, thumbnail_url
             FROM videos
             ORDER BY id DESC
             LIMIT 3"
        );
        $recommended_videos = $rv_stmt2->fetchAll();
    }
} catch (PDOException $e) {
    $recommended_videos = [];
}

// Free library book count — used in the "Paid Programs vs Free Library" promo strip
$home_library_book_count = 0;
try {
    $home_lib_stmt = $pdo->query("SELECT COUNT(*) AS total FROM books");
    $home_library_book_count = $home_lib_stmt->fetch()['total'] ?? 0;
} catch (PDOException $e) {
    $home_library_book_count = 0;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace - Space Theme</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700&family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" />
</head>
<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>


    <!-- ===================== MAIN CONTAINER ===================== -->
    <div class="container">
        <main class="main-content" id="content">

            <!-- ===================== INTERACTIVE CAROUSEL ===================== -->
            <div class="carousel-container">
               <div class="carousel-slides" id="carouselSlides">
   <div class="slide" style="background-image: url('images/banner1.png');">
        <div class="slide-content">
            <span class="slide-tag">📚 Digital Library</span>
            <h1>Read Magical Stories & Books</h1>
            <p>Dive into hundreds of space adventures, science tales, and educational e-books — curated just for young explorers.</p>
            <a href="library/library.php" class="slide-btn slide-btn-primary"><i class="fa-solid fa-book-open"></i> Open Library</a>
        </div>
    </div>
  <div class="slide" style="background-image: url('images/banner2.png');">
        <div class="slide-content">
            <span class="slide-tag">🎬 Video Zone</span>
            <h1>Watch & Learn with Fun Videos</h1>
            <p>Explore science, maths, space, and coding through safe, kid-friendly animated and live-action videos.</p>
            <a href="videos.php" class="slide-btn slide-btn-primary"><i class="fa-solid fa-circle-play"></i> Watch Now</a>
        </div>
    </div>
   <div class="slide" style="background-image: url('images/banner3.png');">
        <div class="slide-content">
            <span class="slide-tag">🛒 Astro Kids Store</span>
            <h1>Shop Cool Learning Kits & Toys</h1>
            <p>Browse STEM robotics kits, puzzle sets, telescopes, and rocket models — perfect gifts for curious young minds.</p>
            <a href="store/store.php" class="slide-btn slide-btn-primary"><i class="fa-solid fa-basket-shopping"></i> Shop Now</a>
        </div>
    </div>
</div>
<div class="carousel-btn prev" onclick="moveSlide(-1)"><i class="fa-solid fa-chevron-left"></i></div>
<div class="carousel-btn next" onclick="moveSlide(1)"><i class="fa-solid fa-chevron-right"></i></div>
<div class="carousel-dots">
    <span class="dot active" onclick="currentSlide(0)"></span>
    <span class="dot" onclick="currentSlide(1)"></span>
    <span class="dot" onclick="currentSlide(2)"></span>
</div>
                <div class="carousel-btn prev" onclick="moveSlide(-1)"><i class="fa-solid fa-chevron-left"></i></div>
                <div class="carousel-btn next" onclick="moveSlide(1)"><i class="fa-solid fa-chevron-right"></i></div>
                <div class="carousel-dots">
                    <span class="dot active" onclick="currentSlide(0)"></span>
                    <span class="dot" onclick="currentSlide(1)"></span>
                    <span class="dot" onclick="currentSlide(2)"></span>
                </div>
            </div>

            <!-- THEMED DAILY DISCOVERY -->
            <div class="fact-box" id="dailyFactBox">
                <div class="fact-icon" id="dailyFactIcon">🧠</div>
                <div class="fact-text-container">
                    <div class="fact-tag" id="dailyFactTag">Loading...</div>
                    <p class="fact-title" id="dailyFactText">Gathering space coordinates...</p>
                </div>
            </div>

            <!-- RECOMMENDED FOR YOU -->
            <div class="section-title">
                <h2>Recommended for You</h2>
                <a href="videos.php">View all</a>
            </div>

            <div class="video-grid">
                <?php if (empty($recommended_videos)): ?>
                    <p style="color:#94a3b8;">No videos available yet.</p>
                <?php else: ?>
                <?php foreach ($recommended_videos as $rv_i => $rv): ?>
                <div class="video-card">
                    <div class="thumbnail">
                        <?php if (($rv['video_type'] ?? 'youtube') === 'file'): ?>
                            <video width="100%" height="100%" src="<?= htmlspecialchars($rv['video_url']) ?>" controls
                                poster="<?= htmlspecialchars($rv['thumbnail_url'] ?: 'images/banner.png') ?>"
                                style="background:#000;"></video>
                        <?php else: ?>
                            <iframe width="100%" height="100%" src="<?= htmlspecialchars($rv['video_url']) ?>"
                                title="<?= htmlspecialchars($rv['title']) ?>" frameborder="0"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen></iframe>
                        <?php endif; ?>
                    </div>
                    <div class="video-info">
                        <div class="channel" style="background:<?= $rv_colors[$rv_i % count($rv_colors)] ?>;">
                            <i class="<?= $rv_icons[$rv_i % count($rv_icons)] ?>"></i>
                        </div>
                        <div class="video-text">
                            <h3><?= htmlspecialchars($rv['title']) ?></h3>
                            <p>SafeKids Space</p>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- LIVE SPACE CLASSES -->
            <div class="section-title" style="margin-top: 50px;" id="liveClasses">
                <h2>Live Interactive Camps</h2>
                <?php if ($live_classes_enabled): ?>
                    <span style="font-size: 14px; color: #ef4444; font-family:'Orbitron',sans-serif; display:flex; align-items:center; gap:6px;">
                        <span style="display:inline-block; width:10px; height:10px; border-radius:50%; background:#ef4444; animation:pulse 1.5s infinite;"></span>
                        LIVE NOW
                    </span>
                <?php endif; ?>
            </div>

            <?php if (!$live_classes_enabled): ?>
                <!-- COMING SOON — HERO BANNER -->
                <div class="lcs-hero">
                    <div class="lcs-hero-left">
                        <span class="lcs-hero-pill"><i class="fa-solid fa-tower-broadcast"></i> COMING SOON</span>
                        <h3>Live Classes Are<br>Launching Soon!</h3>
                        <p>Our verified teachers are getting ready to host live, interactive sessions right here. Be the first to know when the doors open.</p>

                        <div class="lcs-feature-row">
                            <div class="lcs-feature-chip">
                                <span class="lcs-feature-icon lcs-feature-icon-purple"><i class="fa-solid fa-users"></i></span>
                                <div>
                                    <h5>Interactive Learning</h5>
                                    <span>Learn, ask &amp; grow together</span>
                                </div>
                            </div>
                            <div class="lcs-feature-chip">
                                <span class="lcs-feature-icon lcs-feature-icon-blue"><i class="fa-solid fa-shield-halved"></i></span>
                                <div>
                                    <h5>Verified Teachers</h5>
                                    <span>Trusted educators you can count on</span>
                                </div>
                            </div>
                        </div>

                        <?php if ($already_on_waitlist): ?>
                            <button class="lcs-notify-btn lcs-notify-btn-done" disabled>
                                <i class="fa-solid fa-circle-check"></i>
                                <span>You're on the list!</span>
                            </button>
                        <?php elseif (!isset($_SESSION['id'])): ?>
                            <a href="<?= $base ?>account/login.php" class="lcs-notify-btn" style="text-decoration:none;">
                                <i class="fa-solid fa-bell"></i>
                                <span>
                                    Be the first to know!
                                    <small>Log in to join the waitlist and get notified.</small>
                                </span>
                                <i class="fa-solid fa-chevron-right lcs-notify-arrow"></i>
                            </a>
                        <?php else: ?>
                            <form action="" method="POST">
                                <input type="hidden" name="action" value="join_live_waitlist">
                                <button type="submit" class="lcs-notify-btn">
                                    <i class="fa-solid fa-bell"></i>
                                    <span>
                                        Be the first to know!
                                        <small>Join the waitlist and get notified.</small>
                                    </span>
                                    <i class="fa-solid fa-chevron-right lcs-notify-arrow"></i>
                                </button>
                            </form>
                        <?php endif; ?>

                        <div class="lcs-waitlist-count">
                            <i class="fa-solid fa-user-astronaut"></i>
                            <?= number_format($live_waitlist_count) ?> explorer<?= $live_waitlist_count === 1 ? '' : 's' ?> already waiting
                        </div>
                    </div>

                </div>
            <?php elseif (!empty($live_classes_feed)): ?>
                <div class="live-grid">
                    <?php
                    $subject_theme = [
                        'science'  => ['grad' => 'linear-gradient(135deg,#0ea5e9,#22d3ee)', 'icon' => 'fa-flask'],
                        'math'     => ['grad' => 'linear-gradient(135deg,#7c3aed,#a78bfa)', 'icon' => 'fa-calculator'],
                        'english'  => ['grad' => 'linear-gradient(135deg,#f472b6,#ec4899)', 'icon' => 'fa-language'],
                        'art'      => ['grad' => 'linear-gradient(135deg,#f59e0b,#f97316)', 'icon' => 'fa-palette'],
                        'coding'   => ['grad' => 'linear-gradient(135deg,#22c55e,#16a34a)', 'icon' => 'fa-code'],
                    ];
                    foreach ($live_classes_feed as $lc):
                        $is_live = ($lc['status'] === 'Live');
                        $avatar  = !empty($lc['teacher_pic']) ? htmlspecialchars($lc['teacher_pic']) : 'images/gg.png';
                        $subject_key = strtolower($lc['subject_tag']);
                        $theme = null;
                        foreach ($subject_theme as $key => $val) {
                            if (strpos($subject_key, $key) !== false) { $theme = $val; break; }
                        }
                        if (!$theme) { $theme = ['grad' => 'linear-gradient(135deg,#7c3aed,#ec4899)', 'icon' => 'fa-satellite-dish']; }
                    ?>
                        <div class="live-card">
                            <div class="live-card-banner" style="background: <?= $theme['grad'] ?>;">
                                <i class="fa-solid <?= $theme['icon'] ?> live-card-banner-icon"></i>
                                <span class="live-badge"><i class="fa-solid fa-circle"></i> <?= $is_live ? 'Live' : 'Upcoming' ?></span>
                            </div>
                            <div class="live-card-body">
                                <div class="teacher-info">
                                    <img src="<?= $avatar ?>" alt="Teacher" class="teacher-avatar">
                                    <div class="teacher-name">
                                        <h4><?= htmlspecialchars($lc['teacher_name']) ?></h4>
                                        <span>Verified <?= htmlspecialchars($lc['subject_tag']) ?> Teacher</span>
                                    </div>
                                </div>
                                <h3><?= htmlspecialchars($lc['class_title']) ?></h3>
                                <div class="live-meta">
                                    <span>
                                        <i class="fa-regular fa-clock"></i>
                                        <?= $is_live ? 'Live Now' : 'Starts at ' . date('h:i A', strtotime($lc['scheduled_time'])) ?>
                                    </span>
                                </div>
                                <?php if ($is_live): ?>
                                    <a href="<?= htmlspecialchars($lc['meeting_link']) ?>" target="_blank" class="join-live-btn" style="display:block; text-align:center; text-decoration:none;">
                                        <i class="fa-solid fa-right-to-bracket"></i> Join Class Session
                                    </a>
                                <?php else: ?>
                                    <button class="join-live-btn" disabled style="opacity:.6; cursor:not-allowed;">
                                        <i class="fa-regular fa-clock"></i> Starts Soon
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="live-coming-soon">
                    <div class="live-coming-soon-icon"><i class="fa-solid fa-tower-broadcast"></i></div>
                    <h3>No Live Classes Right Now</h3>
                    <p>Check back soon — our teachers regularly host new live sessions.</p>
                </div>
            <?php endif; ?>

            <!-- LEARNING ZONE -->
            <div class="section-title">
                <h2>Learning Zone</h2>
                <a href="learning.php">View all</a>
            </div>
<!-- Paid Programs vs Free Library promo -->
            <div class="home-promo-strip">
                <div class="home-promo-card home-promo-paid">
                    <div class="home-promo-icon"><i class="fa-solid fa-graduation-cap"></i></div>
                    <div class="home-promo-text">
                        <h4>We Offer Paid Learning Programs</h4>
                        <p>Structured videos, quizzes &amp; live classes across 4 age-based programs.</p>
                    </div>
                    <a href="learning.php" class="home-promo-cta">
                        <i class="fa-solid fa-rocket"></i> View Programs
                    </a>
                </div>

                <div class="home-promo-card home-promo-free">
                    <div class="home-promo-icon"><i class="fa-solid fa-book-open"></i></div>
                    <div class="home-promo-text">
                        <h4>Plus, a 100% Free Library</h4>
                        <p><span class="home-promo-count"><?= number_format($home_library_book_count) ?> books</span> to read anytime — free, no subscription needed.</p>
                    </div>
                    <a href="library/library.php" class="home-promo-cta">
                        <i class="fa-solid fa-book"></i> Browse Library
                    </a>
                </div>
            </div>
            <div class="learning-grid">
                <?php if (!empty($learning_zone_programs)): ?>
                    <?php foreach ($learning_zone_programs as $prog): ?>
                        <a href="learning.php#program-<?= intval($prog['id']) ?>" class="learning-card" style="text-decoration:none;">
                            <i class="fa-solid <?= htmlspecialchars($prog['icon'] ?: 'fa-graduation-cap') ?>"></i>
                            <h3><?= htmlspecialchars($prog['title']) ?></h3>
                            <p>For <?= htmlspecialchars($prog['age_range']) ?> yrs</p>
                        </a>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="color:#64748b;">No learning programs available yet — check back soon!</p>
                <?php endif; ?>
            </div>

            

            <!-- KIDS STORE PREVIEW -->
            <div class="section-title">
                <h2>Astro Kids Store</h2>
                <a href="store/store.php">View all Toys</a>
            </div>

            <div class="store-grid">
                <a href="store/store.php" style="text-decoration: none; color: inherit;">
    <div class="product-card">
        <img src="images/3D Solar System Model Kit.jpeg" alt="Toy System" class="product-img">
        <h3>3D Solar System Model Kit</h3>
        <div class="product-points"><i class="fa-solid fa-star"></i> Rs. 2000</div>
        <button class="buy-btn" style="pointer-events: none;">
            <i class="fa-solid fa-cart-shopping"></i> VIEW IN SHOP
        </button>
    </div>
</a>
                <a href="store/store.php" style="text-decoration: none; color: inherit;">
    <div class="product-card">
        <img src="images/Mini Astronaut DIY Telescope.jpeg" alt="Robot Kit" class="product-img">
        <h3>Mini Astronaut DIY Telescope</h3>
        <div class="product-points"><i class="fa-solid fa-star"></i> Rs. 2,100</div>
        <button class="buy-btn" style="pointer-events: none;">
            <i class="fa-solid fa-cart-shopping"></i> VIEW IN SHOP
        </button>
    </div>
</a>

<a href="store/store.php" style="text-decoration: none; color: inherit;">
    <div class="product-card">
        <img src="images/Wooden Rocket Puzzle blocks.jpeg" alt="Building blocks" class="product-img">
        <h3>Wooden Rocket Puzzle blocks</h3>
        <div class="product-points"><i class="fa-solid fa-star"></i> Rs. 600</div>
        <button class="buy-btn" style="pointer-events: none;">
            <i class="fa-solid fa-cart-shopping"></i> VIEW IN SHOP
        </button>
    </div>
</a>
            </div>

            <!-- PARENT & TEACHER CTA -->
            <div class="cta-banner">
                <div class="cta-content">
                    <h2>Parent & Teacher Command Centers</h2>
                    <p>Access customized control tools. Parents can monitor active screen limits and track educational milestone rewards. Teachers can organize live classes after profile verification checks.</p>
                    <div class="cta-buttons">
                        <a href="parent/parent_dashboard.php" class="cta-btn-parent"><i class="fa-solid fa-user-shield"></i> Open Parent Dashboard</a>
                        <a href="careers.php" class="cta-btn-teacher"><i class="fa-solid fa-school"></i> Join as Certified Teacher</a>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script>
        /* CAROUSEL */
        let currentSlideIndex = 0;
        const slidesContainer = document.getElementById('carouselSlides');
        const dots = document.querySelectorAll('.carousel-dots .dot');
        const totalSlides = 3;
        let slideInterval;

        function updateCarousel() {
            slidesContainer.style.transform = `translateX(-${currentSlideIndex * 33.333}%)`;
            dots.forEach((dot, idx) => {
                dot.classList.toggle('active', idx === currentSlideIndex);
            });
        }
        function moveSlide(direction) {
            currentSlideIndex = (currentSlideIndex + direction + totalSlides) % totalSlides;
            updateCarousel();
            resetAutoSlide();
        }
        function currentSlide(index) {
            currentSlideIndex = index;
            updateCarousel();
            resetAutoSlide();
        }
        function startAutoSlide() {
            slideInterval = setInterval(() => moveSlide(1), 6000);
        }
        function resetAutoSlide() {
            clearInterval(slideInterval);
            startAutoSlide();
        }

        /* DAILY FACT */
        const themedFacts = [
            { world: "space",   tag: "🚀 Space Discovery",  icon: "🪐", text: "Did you know? One day on <strong>Venus</strong> is longer than a whole year on Venus!" },
            { world: "animals", tag: "🐾 Animal Discovery", icon: "🦦", text: "Did you know? <strong>Sea otters</strong> have a special pouch of skin under their arms to store their favorite rock!" },
            { world: "earth",   tag: "🌍 Earth Discovery",  icon: "⚡", text: "Did you know? A single bolt of <strong>lightning</strong> contains enough energy to toast 100,000 slices of bread!" },
            { world: "math",    tag: "➗ Math Discovery",   icon: "🔢", text: "Did you know? The number <strong>'four'</strong> is the only number in English with the same number of letters as its value!" },
            { world: "space",   tag: "🚀 Space Discovery",  icon: "☄️", text: "Did you know? Footprints left by astronauts on the <strong>Moon</strong> will last for millions of years!" },
            { world: "animals", tag: "🐾 Animal Discovery", icon: "🐙", text: "Did you know? An <strong>octopus</strong> has three hearts, nine brains, and blue blood!" }
        ];

        function initDailyFact() {
            const today   = new Date();
            const dateNum = today.getFullYear() * 10000 + (today.getMonth() + 1) * 100 + today.getDate();
            const fact    = themedFacts[dateNum % themedFacts.length];
            document.getElementById("dailyFactBox").className = "fact-box " + fact.world + "-theme";
            document.getElementById("dailyFactIcon").innerText  = fact.icon;
            document.getElementById("dailyFactTag").innerText   = fact.tag;
            document.getElementById("dailyFactText").innerHTML  = fact.text;
        }

        window.addEventListener("DOMContentLoaded", () => {
            initDailyFact();
            startAutoSlide();
        });
    </script>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>