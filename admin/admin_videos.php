<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/../includes/db.php';

// Admin only
$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'admin' && $user_role !== 'administrator') {
    header("Location: ../index.php");
    exit();
}

$video_success = '';
$video_error   = '';

// ── Helper: validate + move an uploaded file, return filename / null / false ──
// Returns: string filename on success, null if no file was chosen, false if invalid.
function av_save_upload($file, string $dest_dir, array $allowed_ext, int $max_bytes, string $prefix) {
    if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext, true) || $file['size'] > $max_bytes) {
        return false;
    }
    if (!is_dir($dest_dir)) {
        mkdir($dest_dir, 0755, true);
    }
    $safe_name = $prefix . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dest_dir . $safe_name)) {
        return false;
    }
    return $safe_name;
}

// ── Helper: delete a locally stored video/thumbnail file from disk ───────
// Only deletes files that live inside our own uploads/ folder (relative path,
// e.g. "uploads/videos/vid_123.mp4"). Remote URLs (YouTube links/thumbnails,
// starting with http:// or https://) are left untouched since there is
// nothing on our server to delete for those.
function av_delete_local_file(string $relative_path) {
    if ($relative_path === '' || preg_match('/^https?:\/\//i', $relative_path)) {
        return;
    }
    $full_path = __DIR__ . '/../' . ltrim($relative_path, '/');
    if (is_file($full_path)) {
        @unlink($full_path);
    }
}

// ── Add Video ───────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_add_video'])) {
    $title    = trim($_POST['title']    ?? '');
    $duration = trim($_POST['duration'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $source   = ($_POST['source'] ?? 'youtube') === 'file' ? 'file' : 'youtube';

    if ($source === 'youtube') {
        $clean_url = getEmbedUrl($_POST['video_url'] ?? '');
        $thumbnail = trim($_POST['thumbnail'] ?? '');

        if ($title === '' || $clean_url === '' || $category === '') {
            $video_error = "Title, video URL, and category are required.";
        } else {
            try {
                $pdo->prepare(
                    "INSERT INTO videos (title, video_url, video_type, duration, thumbnail_url, category)
                     VALUES (?, ?, 'youtube', ?, ?, ?)"
                )->execute([$title, $clean_url, $duration, $thumbnail, $category]);
                $video_success = "Video published successfully!";
                notify_role($pdo, 'student', "🎬 New video!", "\"$title\" just got added to Videos!", "videos.php", "fa-solid fa-video");
            } catch (PDOException $e) {
                $video_error = "Database error: " . htmlspecialchars($e->getMessage());
            }
        }
    } else {
        // ── File upload (video + cover image) ──────────────────
        $video_result = av_save_upload($_FILES['video_file']     ?? null, __DIR__ . '/../uploads/videos/',         ['mp4','webm','ogg','mov'], 300 * 1024 * 1024, 'vid');
        $thumb_result = av_save_upload($_FILES['thumbnail_file'] ?? null, __DIR__ . '/../uploads/video_thumbnails/', ['jpg','jpeg','png','webp','gif'], 5 * 1024 * 1024, 'thumb');

        if ($title === '' || $category === '') {
            $video_error = "Title and category are required.";
        } elseif ($video_result === null) {
            $video_error = "Please choose a video file to upload.";
        } elseif ($video_result === false) {
            $video_error = "Video must be MP4/WEBM/OGG/MOV and under 300MB.";
        } elseif ($thumb_result === null) {
            $video_error = "Please choose a cover image for the video.";
        } elseif ($thumb_result === false) {
            $video_error = "Cover image must be JPG/PNG/WEBP/GIF and under 5MB.";
        } else {
            try {
                $pdo->prepare(
                    "INSERT INTO videos (title, video_url, video_type, duration, thumbnail_url, category)
                     VALUES (?, ?, 'file', ?, ?, ?)"
                )->execute([$title, 'uploads/videos/' . $video_result, $duration, 'uploads/video_thumbnails/' . $thumb_result, $category]);
                $video_success = "Video uploaded and published successfully!";
                notify_role($pdo, 'student', "🎬 New video!", "\"$title\" just got added to Videos!", "videos.php", "fa-solid fa-video");
            } catch (PDOException $e) {
                $video_error = "Database error: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// ── Edit Video ──────────────────────────────────────────────
$is_edit_submit = isset($_POST['_edit_video']);
$is_toggle_submit = isset($_POST['_toggle_featured']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_edit_submit) {
    $edit_id      = intval($_POST['video_id'] ?? 0);
    $title        = trim($_POST['title'] ?? '');
    $duration     = trim($_POST['duration'] ?? '');
    $category     = trim($_POST['category'] ?? '');
    $source       = ($_POST['source'] ?? 'youtube') === 'file' ? 'file' : 'youtube';
    $current_url  = trim($_POST['current_video_url'] ?? '');
    $current_thumb= trim($_POST['current_thumbnail'] ?? '');

    if ($edit_id <= 0 || $title === '' || $category === '') {
        $video_error = "Title and category are required.";
    } elseif ($source === 'youtube') {
        $clean_url = getEmbedUrl($_POST['video_url'] ?? '');
        $thumbnail = trim($_POST['thumbnail'] ?? '');

        if ($clean_url === '') {
            $video_error = "Video URL is required.";
        } else {
            try {
                $pdo->prepare(
                    "UPDATE videos SET title = ?, video_url = ?, video_type = 'youtube', duration = ?, thumbnail_url = ?, category = ? WHERE id = ?"
                )->execute([$title, $clean_url, $duration, $thumbnail, $category, $edit_id]);

                // Switching to a YouTube embed — any old locally-uploaded video/thumbnail
                // files are no longer referenced anywhere, so remove them from disk.
                av_delete_local_file($current_url);
                av_delete_local_file($current_thumb);

                $video_success = "✏️ Video updated successfully!";
            } catch (PDOException $e) {
                $video_error = "Database error: " . htmlspecialchars($e->getMessage());
            }
        }
    } else {
        // File source — new files are optional, keep existing ones if not replaced
        $video_result = av_save_upload($_FILES['video_file']     ?? null, __DIR__ . '/../uploads/videos/',         ['mp4','webm','ogg','mov'], 300 * 1024 * 1024, 'vid');
        $thumb_result = av_save_upload($_FILES['thumbnail_file'] ?? null, __DIR__ . '/../uploads/video_thumbnails/', ['jpg','jpeg','png','webp','gif'], 5 * 1024 * 1024, 'thumb');

        $final_url   = ($video_result === null) ? $current_url   : 'uploads/videos/' . $video_result;
        $final_thumb = ($thumb_result === null) ? $current_thumb : 'uploads/video_thumbnails/' . $thumb_result;

        if ($video_result === false) {
            $video_error = "Video must be MP4/WEBM/OGG/MOV and under 300MB.";
        } elseif ($thumb_result === false) {
            $video_error = "Cover image must be JPG/PNG/WEBP/GIF and under 5MB.";
        } elseif ($final_url === '' || $final_thumb === '') {
            $video_error = "A video file and cover image are required.";
        } else {
            try {
                $pdo->prepare(
                    "UPDATE videos SET title = ?, video_url = ?, video_type = 'file', duration = ?, thumbnail_url = ?, category = ? WHERE id = ?"
                )->execute([$title, $final_url, $duration, $final_thumb, $category, $edit_id]);

                // If a new video file / thumbnail was actually uploaded, the old one
                // on disk is now orphaned (no longer referenced) — clean it up.
                if ($video_result !== null) {
                    av_delete_local_file($current_url);
                }
                if ($thumb_result !== null) {
                    av_delete_local_file($current_thumb);
                }

                $video_success = "✏️ Video updated successfully!";
            } catch (PDOException $e) {
                $video_error = "Database error: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// ── Delete Video ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_delete_video'])) {
    $del_id = intval($_POST['video_id'] ?? 0);
    if ($del_id > 0) {
        try {
            // Grab the file paths BEFORE deleting the row, so we know what to remove from disk.
            $stmt = $pdo->prepare("SELECT video_url, thumbnail_url FROM videos WHERE id = ?");
            $stmt->execute([$del_id]);
            $row = $stmt->fetch();

            $pdo->prepare("DELETE FROM videos WHERE id = ?")->execute([$del_id]);

            if ($row) {
                av_delete_local_file($row['video_url'] ?? '');
                av_delete_local_file($row['thumbnail_url'] ?? '');
            }

            $video_success = "🗑️ Video deleted successfully.";
        } catch (PDOException $e) {
            $video_error = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Toggle Featured on Homepage (max 3 at a time) ─────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_toggle_featured'])) {
    $toggle_id = intval($_POST['video_id'] ?? 0);
    if ($toggle_id > 0) {
        try {
            $stmt = $pdo->prepare("SELECT is_featured FROM videos WHERE id = ?");
            $stmt->execute([$toggle_id]);
            $currently_featured = (bool) $stmt->fetchColumn();

            if ($currently_featured) {
                $pdo->prepare("UPDATE videos SET is_featured = 0 WHERE id = ?")->execute([$toggle_id]);
                $video_success = "Removed from Homepage.";
            } else {
                $featured_count = (int) $pdo->query("SELECT COUNT(*) FROM videos WHERE is_featured = 1")->fetchColumn();
                if ($featured_count >= 3) {
                    $video_error = "You can only feature 3 videos on the Homepage at a time. Please un-feature one first.";
                } else {
                    $pdo->prepare("UPDATE videos SET is_featured = 1 WHERE id = ?")->execute([$toggle_id]);
                    $video_success = "Added to Homepage.";
                }
            }
        } catch (PDOException $e) {
            $video_error = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Fetch all videos ────────────────────────────────────────
try {
    $all_videos = $pdo->query("SELECT * FROM videos ORDER BY id DESC")->fetchAll();
} catch (PDOException $e) {
    $all_videos = [];
}

$featured_count = 0;
foreach ($all_videos as $__v) {
    if (!empty($__v['is_featured'])) { $featured_count++; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Manage Videos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/layout.css">
    <link rel="stylesheet" href="../assets/admin.css">
</head>
<body>

<?php include __DIR__ . '/../includes/admin_navbar.php'; ?>

<!-- ══════════════════════════════════════════════════════════════
     ADD VIDEO MODAL POPUP
══════════════════════════════════════════════════════════════ -->
<div class="avm-overlay" id="addVideoOverlay">
    <div class="avm-modal" role="dialog" aria-modal="true" aria-labelledby="avm-title">
        <div class="avm-header">
            <h2 class="avm-title" id="avm-title"><i class="fa-solid fa-video"></i> Upload Educational Video</h2>
            <button class="avm-close-btn" id="avmCloseBtn" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="admin_videos.php" method="POST" id="addVideoForm" enctype="multipart/form-data">
            <input type="hidden" name="_add_video" value="1">
            <input type="hidden" name="source" id="avm_source" value="youtube">

            <div class="avm-source-toggle">
                <button type="button" class="avm-source-btn active" id="avmSourceYoutubeBtn" onclick="avmSetSource('youtube')">
                    <i class="fa-brands fa-youtube"></i> YouTube URL
                </button>
                <button type="button" class="avm-source-btn" id="avmSourceFileBtn" onclick="avmSetSource('file')">
                    <i class="fa-solid fa-upload"></i> Upload File
                </button>
            </div>

            <div class="apm-group">
                <label class="apm-label" for="avm_title">Video Title <span style="color:#f87171;">*</span></label>
                <input type="text" id="avm_title" name="title" class="apm-input"
                       placeholder="e.g., The Solar System Explained" required maxlength="255">
            </div>

            <!-- YouTube fields -->
            <div id="avmYoutubeFields">
                <div class="apm-group">
                    <label class="apm-label" for="avm_url">YouTube / Video URL <span style="color:#f87171;">*</span></label>
                    <input type="url" id="avm_url" name="video_url" class="apm-input"
                           placeholder="https://www.youtube.com/watch?v=..."
                           oninput="avmAutoThumb(this.value)">
                </div>
                <div class="apm-group">
                    <label class="apm-label" for="avm_thumb">Thumbnail URL</label>
                    <input type="url" id="avm_thumb" name="thumbnail" class="apm-input"
                           placeholder="Auto-filled from YouTube, or paste custom URL"
                           oninput="avmShowThumb(this.value)">
                    <img id="avmThumbPreview" class="avm-thumb-preview" src="" alt="Preview">
                </div>
            </div>

            <!-- File-upload fields -->
            <div id="avmFileFields" style="display:none;">
                <div class="apm-group">
                    <label class="apm-label" for="avm_video_file">Video File <span style="color:#f87171;">*</span></label>
                    <input type="file" id="avm_video_file" name="video_file" class="apm-input"
                           accept="video/mp4,video/webm,video/ogg,video/quicktime">
                    <p style="color:#64748b; font-size:.75rem; margin-top:4px;">MP4, WEBM, OGG, or MOV — max 300MB.</p>
                </div>
                <div class="apm-group">
                    <label class="apm-label" for="avm_thumb_file">Cover Image <span style="color:#f87171;">*</span></label>
                    <input type="file" id="avm_thumb_file" name="thumbnail_file" class="apm-input"
                           accept="image/png,image/jpeg,image/webp,image/gif" onchange="avmShowThumbFile(this)">
                    <img id="avmThumbFilePreview" class="avm-thumb-preview" src="" alt="Preview" style="display:none;">
                </div>
            </div>

            <div class="apm-two-col">
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="avm_dur">Duration</label>
                    <input type="text" id="avm_dur" name="duration" class="apm-input" placeholder="e.g., 10:30" maxlength="10">
                </div>
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="avm_cat">Category <span style="color:#f87171;">*</span></label>
                    <select id="avm_cat" name="category" class="apm-select" required>
                        <option value="">-- Select --</option>
                        <option value="Science">Science</option>
                        <option value="Math">Math</option>
                        <option value="English">English</option>
                        <option value="Coding">Coding</option>
                        <option value="Arts & Crafts">Arts &amp; Crafts</option>
                        <option value="Nature">Nature</option>
                        <option value="History">History</option>
                        <option value="Space Studies">Space Studies</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="avm-submit-btn" id="avmSubmitBtn">
                <span class="abm-spinner" id="avmSpinner"></span>
                <i class="fa-solid fa-circle-plus" id="avmBtnIcon"></i>
                <span id="avmBtnText">Publish to Site</span>
            </button>
        </form>
    </div>
</div><!-- /.avm-overlay -->

<!-- ══════════════════════════════════════════════════════════════
     EDIT VIDEO MODAL POPUP (single reusable modal, filled via JS)
══════════════════════════════════════════════════════════════ -->
<div class="avm-overlay" id="editVideoOverlay">
    <div class="avm-modal" role="dialog" aria-modal="true" aria-labelledby="evm-title">
        <div class="avm-header">
            <h2 class="avm-title" id="evm-title"><i class="fa-solid fa-pen"></i> Edit Video</h2>
            <button class="avm-close-btn" id="evmCloseBtn" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form action="admin_videos.php" method="POST" id="editVideoForm" enctype="multipart/form-data">
            <input type="hidden" name="_edit_video" value="1">
            <input type="hidden" name="video_id" id="evm_id">
            <input type="hidden" name="source" id="evm_source" value="youtube">
            <input type="hidden" name="current_video_url" id="evm_current_url">
            <input type="hidden" name="current_thumbnail" id="evm_current_thumb">

            <div class="avm-source-toggle">
                <button type="button" class="avm-source-btn active" id="evmSourceYoutubeBtn" onclick="evmSetSource('youtube')">
                    <i class="fa-brands fa-youtube"></i> YouTube URL
                </button>
                <button type="button" class="avm-source-btn" id="evmSourceFileBtn" onclick="evmSetSource('file')">
                    <i class="fa-solid fa-upload"></i> Upload File
                </button>
            </div>

            <div class="apm-group">
                <label class="apm-label" for="evm_title">Video Title <span style="color:#f87171;">*</span></label>
                <input type="text" id="evm_title" name="title" class="apm-input" required maxlength="255">
            </div>

            <!-- YouTube fields -->
            <div id="evmYoutubeFields">
                <div class="apm-group">
                    <label class="apm-label" for="evm_url">YouTube / Video URL <span style="color:#f87171;">*</span></label>
                    <input type="url" id="evm_url" name="video_url" class="apm-input" oninput="evmAutoThumb(this.value)">
                </div>
                <div class="apm-group">
                    <label class="apm-label" for="evm_thumb">Thumbnail URL</label>
                    <input type="url" id="evm_thumb" name="thumbnail" class="apm-input" oninput="evmShowThumb(this.value)">
                    <img id="evmThumbPreview" class="avm-thumb-preview" src="" alt="Preview">
                </div>
            </div>

            <!-- File-upload fields -->
            <div id="evmFileFields" style="display:none;">
                <div class="apm-group">
                    <label class="apm-label" for="evm_video_file">Video File <span style="color:#64748b; font-weight:400; font-size:.78rem;">(leave blank to keep current)</span></label>
                    <input type="file" id="evm_video_file" name="video_file" class="apm-input" accept="video/mp4,video/webm,video/ogg,video/quicktime">
                </div>
                <div class="apm-group">
                    <label class="apm-label" for="evm_thumb_file">Cover Image <span style="color:#64748b; font-weight:400; font-size:.78rem;">(leave blank to keep current)</span></label>
                    <input type="file" id="evm_thumb_file" name="thumbnail_file" class="apm-input" accept="image/png,image/jpeg,image/webp,image/gif" onchange="evmShowThumbFile(this)">
                    <img id="evmThumbFilePreview" class="avm-thumb-preview" src="" alt="Preview">
                </div>
            </div>

            <div class="apm-two-col">
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="evm_dur">Duration</label>
                    <input type="text" id="evm_dur" name="duration" class="apm-input" maxlength="10">
                </div>
                <div class="apm-group" style="margin-bottom:0;">
                    <label class="apm-label" for="evm_cat">Category <span style="color:#f87171;">*</span></label>
                    <select id="evm_cat" name="category" class="apm-select" required>
                        <option value="">-- Select --</option>
                        <option value="Science">Science</option>
                        <option value="Math">Math</option>
                        <option value="English">English</option>
                        <option value="Coding">Coding</option>
                        <option value="Arts & Crafts">Arts &amp; Crafts</option>
                        <option value="Nature">Nature</option>
                        <option value="History">History</option>
                        <option value="Space Studies">Space Studies</option>
                    </select>
                </div>
            </div>

            <button type="submit" class="avm-submit-btn" id="evmSubmitBtn">
                <span class="abm-spinner" id="evmSpinner"></span>
                <i class="fa-solid fa-floppy-disk" id="evmBtnIcon"></i>
                <span id="evmBtnText">Save Changes</span>
            </button>
        </form>
    </div>
</div><!-- /.editVideoOverlay -->

<!-- Custom confirmation modal (replaces native confirm()) -->
<div class="adc-overlay" id="adcOverlay">
    <div class="adc-modal">
        <div class="adc-icon" id="adcIcon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h3 class="adc-title" id="adcTitle">Are you sure?</h3>
        <p class="adc-message" id="adcMessage"></p>
        <div class="adc-actions">
            <button type="button" class="adc-btn adc-btn-cancel" id="adcCancelBtn">Cancel</button>
            <button type="button" class="adc-btn adc-btn-confirm" id="adcConfirmBtn">Yes, Confirm</button>
        </div>
    </div>
</div>

<div class="main-content ad-wrap">

    <?php if ($video_success): ?>
        <div class="ad-flash ad-flash-success" id="adFlash">
            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($video_success) ?>
        </div>
    <?php endif; ?>
    <?php if ($video_error): ?>
        <div class="ad-flash ad-flash-error" id="adFlash">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= $video_error ?>
        </div>
    <?php endif; ?>

    <div class="ad-hero">
        <div class="ad-hero-left">
            <div class="ad-hero-icon"><i class="fa-solid fa-video"></i></div>
            <div>
                <h1 class="ad-hero-title">Manage Videos</h1>
                <p class="ad-hero-sub">Add, edit, and remove videos shown on the public Videos page</p>
                <span class="ad-hero-badge"><i class="fa-solid fa-circle-check"></i> <?= count($all_videos) ?> Total Videos</span>
            </div>
        </div>
        <div class="ad-hero-right">
            <button type="button" class="ad-back-btn" id="openAddVideoBtn" style="border:none; cursor:pointer;">
                <i class="fa-solid fa-circle-plus"></i> Add Video
            </button>
        </div>
    </div>

    <p class="ad-section-title"><i class="fa-solid fa-list"></i> All Videos</p>

    <?php if (empty($all_videos)): ?>
        <div class="ad-empty">
            <i class="fa-solid fa-video-slash"></i>
            <p>No videos yet. Click "Add Video" to publish your first one.</p>
        </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="ad-table">
            <thead>
                <tr>
                    <th>Thumbnail</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Duration</th>
                    <th>Views</th>
                    <th>Added</th>
                    <th>Homepage</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_videos as $v): ?>
                <tr>
                    <td>
                        <?php
                            $thumb_src = $v['thumbnail_url'] ?? '';
                            if ($thumb_src === '') {
                                $thumb_src = '../images/banner.png';
                            } elseif (!preg_match('/^https?:\/\//i', $thumb_src)) {
                                $thumb_src = '../' . $thumb_src; // local uploaded file — resolve relative to admin/
                            }
                        ?>
                        <img src="<?= htmlspecialchars($thumb_src) ?>"
                             style="width:80px;height:48px;object-fit:cover;border-radius:8px;" alt="">
                    </td>
                    <td style="font-weight:700;"><?= htmlspecialchars($v['title']) ?></td>
                    <td><?= htmlspecialchars($v['category'] ?? '—') ?></td>
                    <td><?= htmlspecialchars($v['duration'] ?? '—') ?></td>
                    <td><?= (int)($v['views'] ?? 0) ?></td>
                    <td><?= htmlspecialchars(date('d M Y', strtotime($v['created_at']))) ?></td>
                    <td style="text-align:center;">
                        <?php $limit_reached = ($featured_count >= 3 && empty($v['is_featured'])); ?>
                        <form action="admin_videos.php" method="POST" style="display:inline;">
                            <input type="hidden" name="_toggle_featured" value="1">
                            <input type="hidden" name="video_id" value="<?= (int)$v['id'] ?>">
                            <button type="submit" <?= $limit_reached ? 'disabled' : '' ?>
                                title="<?= $limit_reached ? 'Maximum 3 videos already featured — un-feature one first' : (!empty($v['is_featured']) ? 'Remove from Homepage' : 'Show on Homepage') ?>"
                                style="background:none; border:none; font-size:1.1rem;
                                       cursor:<?= $limit_reached ? 'not-allowed' : 'pointer' ?>;
                                       opacity:<?= $limit_reached ? '0.35' : '1' ?>;
                                       color:<?= !empty($v['is_featured']) ? '#facc15' : '#475569' ?>;">
                                <i class="<?= !empty($v['is_featured']) ? 'fa-solid' : 'fa-regular' ?> fa-star"></i>
                            </button>
                        </form>
                    </td>
                    <td style="white-space:nowrap;">
                        <a href="../videos.php?id=<?= (int)$v['id'] ?>" target="_blank" class="ad-back-btn" style="padding:6px 10px; font-size:.8rem;">
                            <i class="fa-solid fa-eye"></i>
                        </a>
                        <button type="button" class="ad-back-btn adm-edit-video-btn" style="padding:6px 10px; font-size:.8rem;"
                            data-id="<?= (int)$v['id'] ?>"
                            data-title="<?= htmlspecialchars($v['title'], ENT_QUOTES) ?>"
                            data-url="<?= htmlspecialchars($v['video_url'], ENT_QUOTES) ?>"
                            data-duration="<?= htmlspecialchars($v['duration'] ?? '', ENT_QUOTES) ?>"
                            data-category="<?= htmlspecialchars($v['category'] ?? '', ENT_QUOTES) ?>"
                            data-thumbnail="<?= htmlspecialchars($v['thumbnail_url'] ?? '', ENT_QUOTES) ?>"
                            data-type="<?= htmlspecialchars($v['video_type'] ?? 'youtube', ENT_QUOTES) ?>">
                            <i class="fa-solid fa-pen"></i>
                        </button>
                        <form action="admin_videos.php" method="POST" style="display:inline;" class="ad-confirm-form" data-confirm-msg="Delete this video permanently? This cannot be undone.">
                            <input type="hidden" name="_delete_video" value="1">
                            <input type="hidden" name="video_id" value="<?= (int)$v['id'] ?>">
                            <button type="submit" class="ad-back-btn" style="padding:6px 10px; font-size:.8rem; background:rgba(248,113,113,.12); color:#f87171; border-color:rgba(248,113,113,.3);">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div><!-- /.main-content -->

<script>
/* ── Add Video Modal ──────────────────────────────────────── */
const avmOverlay  = document.getElementById('addVideoOverlay');
const avmOpenBtn  = document.getElementById('openAddVideoBtn');
const avmCloseBtn = document.getElementById('avmCloseBtn');
const avmForm     = document.getElementById('addVideoForm');

function avmOpen()  { avmOverlay.classList.add('open');    document.body.style.overflow = 'hidden'; }
function avmClose() { avmOverlay.classList.remove('open'); document.body.style.overflow = ''; }

avmOpenBtn.addEventListener('click', avmOpen);
avmCloseBtn.addEventListener('click', avmClose);
avmOverlay.addEventListener('click', e => { if (e.target === avmOverlay) avmClose(); });
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { avmClose(); evmClose(); }
});

function avmAutoThumb(url) {
    const match = url.match(/(?:v=|youtu\.be\/)([A-Za-z0-9_-]{11})/);
    if (match) {
        const thumb = 'https://img.youtube.com/vi/' + match[1] + '/hqdefault.jpg';
        const inp = document.getElementById('avm_thumb');
        if (!inp.value) { inp.value = thumb; avmShowThumb(thumb); }
    }
}
function avmShowThumb(url) {
    const img = document.getElementById('avmThumbPreview');
    if (url && url.startsWith('http')) {
        img.src = url; img.style.display = 'block';
        img.onerror = () => { img.style.display = 'none'; };
    } else { img.style.display = 'none'; }
}
function avmShowThumbFile(input) {
    const img = document.getElementById('avmThumbFilePreview');
    if (input.files && input.files[0]) {
        img.src = URL.createObjectURL(input.files[0]);
        img.style.display = 'block';
    } else { img.style.display = 'none'; }
}
function avmSetSource(source) {
    document.getElementById('avm_source').value = source;
    const isFile = source === 'file';
    document.getElementById('avmYoutubeFields').style.display = isFile ? 'none' : 'block';
    document.getElementById('avmFileFields').style.display    = isFile ? 'block' : 'none';
    document.getElementById('avmSourceYoutubeBtn').classList.toggle('active', !isFile);
    document.getElementById('avmSourceFileBtn').classList.toggle('active', isFile);
    document.getElementById('avm_url').required        = !isFile;
    document.getElementById('avm_video_file').required = isFile;
    document.getElementById('avm_thumb_file').required = isFile;
}
avmForm.addEventListener('submit', function() {
    document.getElementById('avmSpinner').style.display  = 'inline-block';
    document.getElementById('avmBtnIcon').style.display  = 'none';
    document.getElementById('avmBtnText').textContent    = 'Publishing…';
    document.getElementById('avmSubmitBtn').disabled     = true;
});
<?php if ($video_error && !$is_edit_submit && !$is_toggle_submit): ?> avmOpen(); <?php endif; ?>

/* ── Edit Video Modal ─────────────────────────────────────── */
const evmOverlay  = document.getElementById('editVideoOverlay');
const evmCloseBtn = document.getElementById('evmCloseBtn');
const evmForm     = document.getElementById('editVideoForm');

function evmOpen()  { evmOverlay.classList.add('open');    document.body.style.overflow = 'hidden'; }
function evmClose() { evmOverlay.classList.remove('open'); document.body.style.overflow = ''; }

evmCloseBtn.addEventListener('click', evmClose);
evmOverlay.addEventListener('click', e => { if (e.target === evmOverlay) evmClose(); });

function evmAutoThumb(url) {
    const match = url.match(/(?:v=|youtu\.be\/|embed\/)([A-Za-z0-9_-]{11})/);
    if (match) {
        const thumb = 'https://img.youtube.com/vi/' + match[1] + '/hqdefault.jpg';
        document.getElementById('evm_thumb').value = thumb;
        evmShowThumb(thumb);
    }
}
function evmShowThumb(url) {
    const img = document.getElementById('evmThumbPreview');
    if (url && url.startsWith('http')) {
        img.src = url; img.style.display = 'block';
        img.onerror = () => { img.style.display = 'none'; };
    } else { img.style.display = 'none'; }
}
function evmShowThumbFile(input) {
    const img = document.getElementById('evmThumbFilePreview');
    if (input.files && input.files[0]) {
        img.src = URL.createObjectURL(input.files[0]);
        img.style.display = 'block';
    }
}
function evmSetSource(source) {
    document.getElementById('evm_source').value = source;
    const isFile = source === 'file';
    document.getElementById('evmYoutubeFields').style.display = isFile ? 'none' : 'block';
    document.getElementById('evmFileFields').style.display    = isFile ? 'block' : 'none';
    document.getElementById('evmSourceYoutubeBtn').classList.toggle('active', !isFile);
    document.getElementById('evmSourceFileBtn').classList.toggle('active', isFile);
}

document.querySelectorAll('.adm-edit-video-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
        document.getElementById('evm_id').value       = btn.dataset.id;
        document.getElementById('evm_title').value    = btn.dataset.title;
        document.getElementById('evm_url').value      = btn.dataset.url;
        document.getElementById('evm_dur').value      = btn.dataset.duration;
        document.getElementById('evm_cat').value      = btn.dataset.category;
        document.getElementById('evm_thumb').value    = btn.dataset.thumbnail;
        document.getElementById('evm_current_url').value   = btn.dataset.url;
        document.getElementById('evm_current_thumb').value = btn.dataset.thumbnail;

        const videoType = btn.dataset.type === 'file' ? 'file' : 'youtube';
        evmSetSource(videoType);

        if (videoType === 'youtube') {
            evmShowThumb(btn.dataset.thumbnail);
        } else {
            const img = document.getElementById('evmThumbFilePreview');
            const thumbPath = btn.dataset.thumbnail;
            img.src = thumbPath.startsWith('http') ? thumbPath : '../' + thumbPath;
            img.style.display = 'block';
            img.onerror = () => { img.style.display = 'none'; };
        }
        evmOpen();
    });
});

evmForm.addEventListener('submit', function() {
    document.getElementById('evmSpinner').style.display = 'inline-block';
    document.getElementById('evmBtnIcon').style.display = 'none';
    document.getElementById('evmBtnText').textContent   = 'Saving…';
    document.getElementById('evmSubmitBtn').disabled    = true;
});
<?php if ($video_error && $is_edit_submit): ?> evmOpen(); <?php endif; ?>

/* ── Custom confirmation modal ───────────────────────────── */
(function(){
    const adcOverlay    = document.getElementById('adcOverlay');
    const adcMessage    = document.getElementById('adcMessage');
    const adcConfirmBtn = document.getElementById('adcConfirmBtn');
    const adcCancelBtn  = document.getElementById('adcCancelBtn');
    let adcPendingForm  = null;

    document.querySelectorAll('form.ad-confirm-form').forEach(function(form){
        form.addEventListener('submit', function(e){
            e.preventDefault();
            adcPendingForm = form;
            adcMessage.textContent = form.getAttribute('data-confirm-msg') || 'Are you sure you want to continue?';
            adcOverlay.classList.add('open');
        });
    });
    adcConfirmBtn.addEventListener('click', function(){
        adcOverlay.classList.remove('open');
        if (adcPendingForm) { adcPendingForm.submit(); }
    });
    adcCancelBtn.addEventListener('click', function(){
        adcOverlay.classList.remove('open');
        adcPendingForm = null;
    });
    adcOverlay.addEventListener('click', function(e){
        if (e.target === adcOverlay) {
            adcOverlay.classList.remove('open');
            adcPendingForm = null;
        }
    });
})();

/* Auto-dismiss flash alerts */
document.querySelectorAll('#adFlash').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity .4s ease';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    }, 6000);
});
</script>
</body>
</html>