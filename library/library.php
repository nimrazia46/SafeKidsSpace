<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Library</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/layout.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;500;600;700;800;900&family=Baloo+2:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>


<div class="container">
<div id="lib-page">

    <!-- ── Banner — single slide, same markup as index.php carousel ── -->
    <div class="carousel-container" style="margin-bottom:36px;">
        <div class="slide" style="width:100%; background-image:url('../images/banner1.png');">
            <div class="slide-content">
                <span class="slide-tag">📚 Digital Library</span>
                <h1>Read Magical Stories &amp; Books</h1>
                <p>Discover books, guides, and learning resources curated for every young learner.</p>
                <a href="#lib-content" class="slide-btn slide-btn-primary">
                    <i class="bi bi-book-half"></i> Browse Books
                </a>
            </div>
        </div>
        <span class="lib-banner-sparkle">✦</span>
    </div>

    <!-- ── Category Nav ──────────────────────────────────── -->
    <nav class="lib-cat-nav" id="lib-cat-nav" aria-label="Book categories">

        <button class="lib-cat-btn lib-active lib-all" onclick="libUpdateView('All', this)">
            <div class="lib-cat-icon lib-all"><i class="bi bi-grid-fill"></i></div>
            All Books
        </button>

        <button class="lib-cat-btn lib-trending" onclick="libUpdateView('Trending', this)">
            <div class="lib-cat-icon lib-trending"><i class="bi bi-fire"></i></div>
            Trending
        </button>

        <button class="lib-cat-btn lib-featured" onclick="libUpdateView('Featured', this)">
            <div class="lib-cat-icon lib-featured"><i class="bi bi-stars"></i></div>
            Featured
        </button>

        <button class="lib-cat-btn lib-new" onclick="libUpdateView('New', this)">
            <div class="lib-cat-icon lib-new"><i class="bi bi-rocket-takeoff-fill"></i></div>
            New Arrivals
        </button>

        <button class="lib-cat-btn lib-staff" onclick="libUpdateView('Staff Picks', this)">
            <div class="lib-cat-icon lib-staff"><i class="bi bi-award-fill"></i></div>
            Staff Picks
        </button>

    </nav>

    <!-- ── Content area ──────────────────────────────────── -->
    <div id="lib-content"></div>

</div><!-- /#lib-page -->
</div><!-- /.container -->

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
/* ──────────────────────────────────────────────────────────
   LIBRARY JS  (all vars/funcs prefixed with lib to avoid
   collision with any other scripts that layout.css pages load)
────────────────────────────────────────────────────────── */
let libData = [];

const LIB_SECTIONS = ['Trending', 'Featured', 'New', 'Staff Picks'];

const LIB_SECTION_META = {
    'Trending'   : { cls: 'lib-sec-trending', icon: 'bi-fire',               title: '🔥 Trending'    },
    'Featured'   : { cls: 'lib-sec-featured', icon: 'bi-stars',              title: '⭐ Featured'    },
    'New'        : { cls: 'lib-sec-new',      icon: 'bi-rocket-takeoff-fill', title: '🚀 New Arrivals' },
    'Staff Picks': { cls: 'lib-sec-staff',    icon: 'bi-award-fill',          title: '🏆 Staff Picks'  },
};

/* ── Load books from server ──────────────────────────────── */
async function libLoad() {
    const container = document.getElementById('lib-content');

    // Show skeleton cards while loading
    container.innerHTML = `
        <div class="row g-4 mb-5">
            ${[1,2,3,4].map(() => `
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div style="border-radius:22px;overflow:hidden;background:rgba(15,23,42,.88);border:1px solid rgba(255,255,255,.06);">
                        <div class="lib-skeleton" style="height:210px;"></div>
                        <div style="padding:16px;">
                            <div class="lib-skeleton mb-2" style="height:18px;width:75%;border-radius:8px;"></div>
                            <div class="lib-skeleton mb-3" style="height:13px;width:45%;border-radius:8px;"></div>
                            <div class="lib-skeleton" style="height:42px;border-radius:13px;"></div>
                        </div>
                    </div>
                </div>
            `).join('')}
        </div>`;

    try {
        const res = await fetch('get_books.php');
        libData = await res.json();
        libUpdateView('All', document.querySelector('.lib-cat-btn'));
    } catch (err) {
        container.innerHTML = `
            <div class="lib-empty">
                <i class="bi bi-wifi-off"></i>
                <p>Could not load the library. Please refresh.</p>
            </div>`;
    }
}

/* ── Render section/all view ─────────────────────────────── */
function libUpdateView(section, el) {
    // Active pill
    document.querySelectorAll('.lib-cat-btn').forEach(b => b.classList.remove('lib-active'));
    if (el) el.classList.add('lib-active');

    const container = document.getElementById('lib-content');
    const sections  = section === 'All' ? LIB_SECTIONS : [section];

    const html = sections.map(sec => {
        const meta  = LIB_SECTION_META[sec];
        const items = libData.filter(b => b.section === sec);
        if (!items.length) return '';

        return `
            <div class="${meta.cls} mb-5">
                <h3 class="lib-section-title">${meta.title}</h3>
                <div class="row g-4">
                    ${items.map((book, i) => `
                        <div class="col-lg-3 col-md-4 col-sm-6 lib-animate" style="animation-delay:${i * 55}ms;">
                            <div class="lib-card">
                                <div class="lib-card-img-wrap">
                                    <img src="../images/${libEsc(book.img_url)}"
                                         alt="${libEsc(book.title)}"
                                         loading="lazy"
                                         onerror="this.src='../images/placeholder.png'">
                                </div>
                                <div class="lib-card-body">
                                    <h5 class="lib-card-title">${libEsc(book.title)}</h5>
                                    <p class="lib-card-author">${libEsc(book.author)}</p>
                                    <a href="../pdf/${libEsc(book.pdf_url)}"
                                       target="_blank"
                                       rel="noopener"
                                       class="lib-read-btn">
                                        <i class="bi bi-book-half me-1"></i> Read Now
                                    </a>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>`;
    }).join('');

    container.innerHTML = html || `
        <div class="lib-empty">
            <i class="bi bi-journal-x"></i>
            <p>No books found in this section yet.</p>
        </div>`;
}

/* ── XSS-safe escape ─────────────────────────────────────── */
function libEsc(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g,'&amp;')
        .replace(/</g,'&lt;')
        .replace(/>/g,'&gt;')
        .replace(/"/g,'&quot;');
}

document.addEventListener('DOMContentLoaded', libLoad);
</script>
</body>
</html>