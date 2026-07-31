<?php
$base = ''; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/includes/db.php';

// SafeKidsSpace requires sign-in to watch — also needed so we know
// WHICH child is watching, for the parent-monitoring activity log below.
if (!isset($_SESSION['id'])) {
    header("Location: account/login.php");
    exit();
}

try {
    $stmt = $pdo->query("SELECT id, title, video_url, video_type, thumbnail_url, duration, views FROM videos ORDER BY id DESC");
    $all_videos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $all_videos = [];
}

$video_id = isset($_GET['id']) ? (int)$_GET['id'] : null;
$active_video = null;

if ($video_id) {
    foreach ($all_videos as $v) {
        if ($v['id'] == $video_id) { $active_video = $v; break; }
    }
}
if (!$active_video && !empty($all_videos)) { $active_video = $all_videos[0]; }

// ── Increment view count for the video actually being watched ──────────
if ($active_video) {
    try {
        $vl_view_upd = $pdo->prepare("UPDATE videos SET views = views + 1 WHERE id = ?");
        $vl_view_upd->execute([$active_video['id']]);
        // Keep the number shown on this page in sync with what we just saved.
        $active_video['views'] = (int)($active_video['views'] ?? 0) + 1;
    } catch (PDOException $e) {
        // Don't block playback if the count fails to update.
    }
}

// ── Log this watch for parent monitoring (Free Video Library) ──────────
// Mirrors the same pattern used in learning.php for paid program videos,
// so both video pages show up side-by-side in the parent's activity feed.
$vl_user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($active_video && in_array($vl_user_role, ['student', 'child'], true)) {
    try {
        $vl_action_text = "Watched video: " . $active_video['title'] . " (Video Library)";

        $vl_upd_mon = $pdo->prepare(
            "UPDATE parent_monitoring SET last_watched_video = ?, last_action = ? WHERE child_id = ?"
        );
        $vl_upd_mon->execute([$active_video['title'], $vl_action_text, $_SESSION['id']]);

        $vl_ins_log = $pdo->prepare(
            "INSERT INTO kid_activity_logs (child_id, activity_name, activity_type, points_earned, duration_minutes)
             VALUES (?, ?, 'video', 5, 0)"
        );
        $vl_ins_log->execute([$_SESSION['id'], $vl_action_text]);
    } catch (PDOException $e) {
        // Don't block video playback just because logging failed.
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <title>Watch Videos - SafeKidsSpace</title>
    <link rel="stylesheet" href="assets/layout.css">
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>
    <main class="main-content">
        <div class="video-page-grid">
            <section>
                <div class="player-container">
                    <?php if ($active_video): ?>
                       <?php if (($active_video['video_type'] ?? 'youtube') === 'file'): ?>
                       <video
    width="100%"
    height="100%"
    src="<?= htmlspecialchars($active_video['video_url']); ?>"
    controls
    autoplay
    style="background:#000;">
</video>
                       <?php else: ?>
                       <iframe 
    width="100%" 
    height="100%" 
    src="<?= htmlspecialchars($active_video['video_url']); ?>" 
    frameborder="0" 
    allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" 
    referrerpolicy="strict-origin-when-cross-origin" 
    allowfullscreen>
</iframe>
                       <?php endif; ?>
                    <?php else: ?>
                        <p style="padding:20px; color:#fff;">No videos available.</p>
                    <?php endif; ?>
                </div>
                <h2 style="margin-top:20px; color:#fff;"><?= htmlspecialchars($active_video['title'] ?? 'Select a video'); ?></h2>
            </section>

            <aside class="sidebar-list">
                <h4 style="margin-bottom:20px; color:#fff;">Up Next</h4>
                <?php foreach ($all_videos as $vid): ?>
                    <a href="videos.php?id=<?= $vid['id']; ?>" style="display:flex; gap:12px; margin-bottom:15px; text-decoration:none; color:#fff;">
                        <?php
                            $thumb_url = $vid['thumbnail_url'] ?? '';
                            if ($thumb_url === '') {
                                $thumb_url = 'images/banner.png';
                            }
                        ?>
                        <img src="<?= htmlspecialchars($thumb_url) ?>" style="width:120px; height:70px; object-fit:cover; border-radius:8px; flex-shrink:0;">
                        <div>
                            <div style="font-weight:600; font-size:0.9rem;"><?= htmlspecialchars($vid['title']); ?></div>
                            <div style="font-size:0.75rem; color:#94a3b8;"><?= htmlspecialchars($vid['duration']); ?></div>
                        </div>
                    </a>
                <?php endforeach; ?>
            </aside>
        </div>
    </main>
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>