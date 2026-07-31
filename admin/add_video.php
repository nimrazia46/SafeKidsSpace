<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/../includes/db.php';

// Strict Security: admins only
$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'admin' && $user_role !== 'administrator') {
    header("Location: ../index.php");
    exit();
}

$video_success = '';
$video_error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $clean_url = getEmbedUrl($_POST['video_url'] ?? '');

    try {
        $stmt = $pdo->prepare(
            "INSERT INTO videos (title, video_url, duration, thumbnail_url, category)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $_POST['title']     ?? '',
            $clean_url,
            $_POST['duration']  ?? '',
            $_POST['thumbnail'] ?? '',
            $_POST['category']  ?? '',
        ]);
        $video_success = "Video published successfully! <a href='../videos.php' style='color:#34d399;text-decoration:underline;'>View Page →</a>";
    } catch (PDOException $e) {
        $video_error = "Database error: " . htmlspecialchars($e->getMessage());
    }
}

// Category suggestions (reuse if you have a video_categories table, else hardcoded)
$video_categories = ['Science', 'Math', 'English', 'Arts & Crafts', 'Nature', 'Coding', 'History', 'Languages'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Upload Educational Video</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/layout.css">

</head>
<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="main-content avp-wrap">

    <!-- ── Hero banner ───────────────────────────────────────── -->
    <div class="avp-hero">
        <div class="avp-hero-left">
            <div class="avp-hero-icon">
                <i class="fa-solid fa-circle-play"></i>
            </div>
            <div>
                <h1 class="avp-hero-title">Upload Educational Video</h1>
                <p class="avp-hero-sub">Add a new video to the SafeKidsSpace content library.</p>
                <span class="avp-hero-badge">
                    <i class="fa-solid fa-shield-halved"></i> Admin Content Panel
                </span>
            </div>
        </div>
        <div>
            <a href="admin_dashboard.php" class="avp-back-btn">
                <i class="fa-solid fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- ── Flash messages ────────────────────────────────────── -->
    <?php if ($video_success): ?>
        <div class="avp-flash avp-flash-success" id="adFlash">
            <i class="fa-solid fa-circle-check"></i>
            <?= $video_success ?>
        </div>
    <?php endif; ?>
    <?php if ($video_error): ?>
        <div class="avp-flash avp-flash-error" id="adFlash">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <?= $video_error ?>
        </div>
    <?php endif; ?>

    <!-- ── Form card ──────────────────────────────────────────── -->
    <div class="avp-card">

        <div class="avp-header">
            <h2 class="avp-video-title">
                <i class="fa-solid fa-video"></i> New Video
            </h2>
            <a href="../videos.php" style="font-family:'Orbitron',sans-serif;font-size:.68rem;letter-spacing:.5px;color:#64748b;text-decoration:none;text-transform:uppercase;transition:color .2s;" onmouseover="this.style.color='#38bdf8'" onmouseout="this.style.color='#64748b'">
                <i class="fa-solid fa-film"></i> View All Videos
            </a>
        </div>

        <form method="POST" id="addVideoForm">

            <!-- Title -->
            <div class="avp-group">
                <label class="avp-label" for="avm_title">
                    Video Title <span style="color:#f87171;">*</span>
                </label>
                <input type="text" id="avm_title" name="title" class="avp-input"
                       placeholder="e.g., The Solar System Explained" required maxlength="255">
            </div>

            <!-- YouTube URL -->
            <div class="avp-group">
                <label class="avp-label" for="avm_url">
                    YouTube / Video URL <span style="color:#f87171;">*</span>
                </label>
                <input type="url" id="avm_url" name="video_url" class="avp-input"
                       placeholder="https://www.youtube.com/watch?v=..." required
                       oninput="autoFillThumb(this.value)">
            </div>

            <!-- Duration + Category -->
            <div class="avp-two-col">
                <div class="avp-group" style="margin-bottom:0;">
                    <label class="avp-label" for="avm_duration">Duration</label>
                    <input type="text" id="avm_duration" name="duration" class="avp-input"
                           placeholder="e.g., 10:30" maxlength="10">
                </div>
                <div class="avp-group" style="margin-bottom:0;">
                    <label class="avp-label" for="avm_cat">
                        Category <span style="color:#f87171;">*</span>
                    </label>
                    <select id="avm_cat" name="category" class="avp-select" required>
                        <option value="">-- Select --</option>
                        <?php foreach ($video_categories as $cat): ?>
                            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Category quick-chips -->
            <div class="avp-badge-hints" style="margin-top:10px; margin-bottom:18px;">
                <?php foreach ($video_categories as $cat): ?>
                    <span class="avp-hint-chip"
                          onclick="document.getElementById('avm_cat').value='<?= htmlspecialchars($cat) ?>'">
                        <?= htmlspecialchars($cat) ?>
                    </span>
                <?php endforeach; ?>
            </div>

            <!-- Thumbnail URL + auto-preview -->
            <div class="avp-group">
                <label class="avp-label" for="avm_thumb">Thumbnail Image URL</label>
                <input type="url" id="avm_thumb" name="thumbnail" class="avp-input"
                       placeholder="Auto-filled from YouTube, or paste a custom URL"
                       oninput="showThumbPreview(this.value)">
                <img id="avmThumbPreview" class="avp-thumb-preview" src="" alt="Thumbnail preview">
            </div>

            <!-- Publish toggle -->
            <div class="avp-group">
                <div class="avp-toggle-row">
                    <div>
                        <label for="avm_publish" style="font-weight:700;">Publish immediately</label><br>
                        <small>Video will appear on the site as soon as it's saved</small>
                    </div>
                    <label class="avp-switch">
                        <input type="checkbox" name="is_published" id="avm_publish" value="1" checked>
                        <span class="avp-slider"></span>
                    </label>
                </div>
            </div>

            <!-- Submit -->
            <button type="submit" class="avp-submit-btn" id="avmSubmitBtn">
                <span class="avp-spinner" id="avmSpinner"></span>
                <i class="fa-solid fa-circle-plus" id="avmBtnIcon"></i>
                <span id="avmBtnText">Publish to Site</span>
            </button>

        </form>
    </div><!-- /.avp-card -->

</div><!-- /.avp-wrap -->

<?php include __DIR__ . '/../includes/footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
/* ── Auto-fill YouTube thumbnail ───────────────────────── */
function autoFillThumb(url) {
    const match = url.match(/(?:v=|youtu\.be\/)([A-Za-z0-9_-]{11})/);
    if (match) {
        const thumb = 'https://img.youtube.com/vi/' + match[1] + '/hqdefault.jpg';
        const thumbInput = document.getElementById('avm_thumb');
        if (!thumbInput.value) {           // only auto-fill if empty
            thumbInput.value = thumb;
            showThumbPreview(thumb);
        }
    }
}

/* ── Show thumbnail preview ─────────────────────────────── */
function showThumbPreview(url) {
    const img = document.getElementById('avmThumbPreview');
    if (url && url.startsWith('http')) {
        img.src = url;
        img.style.display = 'block';
        img.onerror = () => { img.style.display = 'none'; };
    } else {
        img.style.display = 'none';
    }
}

/* ── Submit loading state ───────────────────────────────── */
document.getElementById('addVideoForm').addEventListener('submit', function () {
    document.getElementById('avmSpinner').style.display  = 'inline-block';
    document.getElementById('avmBtnIcon').style.display  = 'none';
    document.getElementById('avmBtnText').textContent    = 'Publishing…';
    document.getElementById('avmSubmitBtn').disabled     = true;
});

/* ── Auto-dismiss flash alerts ──────────────────────────── */
document.querySelectorAll('#adFlash').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity .4s ease';
        el.style.opacity    = '0';
        setTimeout(() => el.remove(), 400);
    }, 6000);
});
</script>
</body>
</html>