<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/../includes/db.php';

$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'admin' && $user_role !== 'administrator') {
    header("Location: ../index.php");
    exit();
}

// ── Approve / Reject a pending program video ────────────────────
$program_video_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_review_program_video'])) {
    $review_video_id = intval($_POST['video_id'] ?? 0);
    $review_decision  = ($_POST['decision'] ?? '') === 'approve' ? 'approved' : 'rejected';
    if ($review_video_id > 0) {
        try {
            $vinfo_stmt = $pdo->prepare("SELECT title, teacher_id, program_id FROM program_videos WHERE id = ?");
            $vinfo_stmt->execute([$review_video_id]);
            $vinfo = $vinfo_stmt->fetch();

            $pdo->prepare(
                "UPDATE program_videos SET status = ?, approved_by = ?, approved_at = NOW() WHERE id = ?"
            )->execute([$review_decision, $_SESSION['id'], $review_video_id]);
            $program_video_message = $review_decision === 'approved'
                ? "✅ Video approved — it's now visible to enrolled students."
                : "🚫 Video rejected.";

            if ($vinfo) {
                if ($review_decision === 'approved') {
                    notify_user(
                        $pdo, $vinfo['teacher_id'],
                        "Video approved",
                        "Your video \"{$vinfo['title']}\" was approved and is now live.",
                        "teacher/teacher_program_videos.php",
                        "fa-solid fa-circle-check"
                    );

                    $prog_title_stmt = $pdo->prepare("SELECT title FROM programs WHERE id = ?");
                    $prog_title_stmt->execute([$vinfo['program_id']]);
                    $prog_title = $prog_title_stmt->fetchColumn();

                    $enrolled_stmt = $pdo->prepare("SELECT DISTINCT child_id FROM enrollments WHERE program_id = ? AND status = 'active'");
                    $enrolled_stmt->execute([$vinfo['program_id']]);
                    foreach ($enrolled_stmt->fetchAll() as $enr_row) {
                        notify_user(
                            $pdo, $enr_row['child_id'],
                            "New video available!",
                            "A new video was just added to \"$prog_title\": {$vinfo['title']}",
                            "learning.php",
                            "fa-solid fa-clapperboard"
                        );
                    }
                } else {
                    notify_user(
                        $pdo, $vinfo['teacher_id'],
                        "Video rejected",
                        "Your video \"{$vinfo['title']}\" was rejected. You can edit and resubmit it.",
                        "teacher/teacher_program_videos.php",
                        "fa-solid fa-circle-xmark"
                    );
                }
            }
        } catch (PDOException $e) {
            $program_video_message = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Admin: edit a program video's details ─────────────────────────
$is_edit_video_submit = isset($_POST['_edit_program_video']);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $is_edit_video_submit) {
    $edit_video_id    = intval($_POST['video_id'] ?? 0);
    $edit_title       = trim($_POST['title'] ?? '');
    $edit_order_index = intval($_POST['order_index'] ?? 0);
    $edit_url_raw     = trim($_POST['video_url'] ?? '');

    if ($edit_video_id <= 0 || $edit_title === '' || $edit_order_index < 1 || $edit_order_index > 10) {
        $program_video_message = "Please fill in title and a valid slot (1–10).";
    } else {
        try {
            $find_stmt = $pdo->prepare("SELECT video_type, video_url FROM program_videos WHERE id = ?");
            $find_stmt->execute([$edit_video_id]);
            $existing_pv = $find_stmt->fetch();
            $video_type  = $existing_pv['video_type'] ?? 'youtube';

            if ($video_type === 'youtube') {
                // ── YouTube video: URL is required and typed by the admin ──
                if ($edit_url_raw === '') {
                    $program_video_message = "Please fill in the video URL.";
                } else {
                    $clean_url = getEmbedUrl($edit_url_raw);
                    $pdo->prepare(
                        "UPDATE program_videos SET title = ?, video_url = ?, order_index = ? WHERE id = ?"
                    )->execute([$edit_title, $clean_url, $edit_order_index, $edit_video_id]);
                    $program_video_message = "✏️ Video updated successfully!";
                }
            } else {
                // ── Uploaded-file video: storage path isn't hand-typed. Admin can
                // optionally choose a new file here to replace the current one. ──
                $new_rel_path = null;

                if (!empty($_FILES['edit_video_file']) && $_FILES['edit_video_file']['error'] === UPLOAD_ERR_OK) {
                    $file        = $_FILES['edit_video_file'];
                    $allowed_ext = ['mp4', 'webm', 'ogg', 'mov'];
                    $max_bytes   = 300 * 1024 * 1024;
                    $ext         = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                    if (!in_array($ext, $allowed_ext, true)) {
                        $program_video_message = "Unsupported file type. Use MP4, WEBM, OGG, or MOV.";
                    } elseif ($file['size'] > $max_bytes) {
                        $program_video_message = "File is too large. Max size is 300MB.";
                    } else {
                        $upload_dir = __DIR__ . '/../uploads/program_videos/';
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        $safe_name = 'pv_admin_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                        $dest_path = $upload_dir . $safe_name;
                        if (move_uploaded_file($file['tmp_name'], $dest_path)) {
                            $new_rel_path = 'uploads/program_videos/' . $safe_name;
                        } else {
                            $program_video_message = "Something went wrong saving the new video file.";
                        }
                    }
                }

                if ($program_video_message === '') {
                    $final_url = $new_rel_path ?? ($existing_pv['video_url'] ?? '');

                    $pdo->prepare(
                        "UPDATE program_videos SET title = ?, video_url = ?, order_index = ? WHERE id = ?"
                    )->execute([$edit_title, $final_url, $edit_order_index, $edit_video_id]);

                    // A new file was uploaded — the old one is now orphaned, remove it from disk.
                    if ($new_rel_path !== null && !empty($existing_pv['video_url'])) {
                        $old_path = __DIR__ . '/../' . $existing_pv['video_url'];
                        if (is_file($old_path)) {
                            @unlink($old_path);
                        }
                    }

                    $program_video_message = "✏️ Video updated successfully!";
                }
            }
        } catch (PDOException $e) {
            $program_video_message = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Admin: delete ANY program video, regardless of status ────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_delete_program_video'])) {
    $del_video_id = intval($_POST['video_id'] ?? 0);
    if ($del_video_id > 0) {
        try {
            $find_stmt = $pdo->prepare("SELECT video_type, video_url FROM program_videos WHERE id = ?");
            $find_stmt->execute([$del_video_id]);
            $video_row = $find_stmt->fetch();

            if ($video_row) {
                $pdo->prepare("DELETE FROM program_videos WHERE id = ?")->execute([$del_video_id]);
                if ($video_row['video_type'] === 'file') {
                    $file_path = __DIR__ . '/../' . $video_row['video_url'];
                    if (is_file($file_path)) {
                        unlink($file_path);
                    }
                }
                $program_video_message = "🗑️ Video deleted.";
            } else {
                $program_video_message = "Video not found.";
            }
        } catch (PDOException $e) {
            $program_video_message = "Database error: " . htmlspecialchars($e->getMessage());
        }
    }
}

// ── Admin: add a video directly (auto-approved, skips the queue) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_admin_add_program_video'])) {
    $aav_program_id = intval($_POST['program_id'] ?? 0);
    $aav_teacher_id = intval($_POST['teacher_id'] ?? 0);
    $aav_title      = trim($_POST['video_title'] ?? '');
    $aav_order      = intval($_POST['order_index'] ?? 0);
    $aav_source     = ($_POST['video_source'] ?? 'youtube') === 'file' ? 'file' : 'youtube';

    if ($aav_program_id > 0 && $aav_teacher_id > 0 && !empty($aav_title) && $aav_order >= 1 && $aav_order <= 10) {
        if ($aav_source === 'youtube') {
            $aav_url_raw = trim($_POST['video_url'] ?? '');
            if (empty($aav_url_raw)) {
                $program_video_message = "Please paste a YouTube link.";
            } else {
                try {
                    $aav_clean_url = getEmbedUrl($aav_url_raw);
                    $pdo->prepare(
                        "INSERT INTO program_videos (program_id, teacher_id, title, video_url, video_type, order_index, status, approved_by, approved_at)
                         VALUES (?, ?, ?, ?, 'youtube', ?, 'approved', ?, NOW())"
                    )->execute([$aav_program_id, $aav_teacher_id, $aav_title, $aav_clean_url, $aav_order, $_SESSION['id']]);
                    $program_video_message = "✅ Video added and published.";
                } catch (PDOException $e) {
                    $program_video_message = "Database error: " . htmlspecialchars($e->getMessage());
                }
            }
        } else {
            if (empty($_FILES['video_file']) || $_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
                $program_video_message = "Please choose a video file to upload.";
            } else {
                $file = $_FILES['video_file'];
                $allowed_ext = ['mp4', 'webm', 'ogg', 'mov'];
                $max_bytes   = 300 * 1024 * 1024;
                $ext         = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if (!in_array($ext, $allowed_ext)) {
                    $program_video_message = "Unsupported file type.";
                } elseif ($file['size'] > $max_bytes) {
                    $program_video_message = "File is too large. Max size is 300MB.";
                } else {
                    $upload_dir = __DIR__ . '/../uploads/program_videos/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $safe_name = 'pv_admin_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $dest_path = $upload_dir . $safe_name;
                    // Stored unprefixed (relative to project root) so it stays playable from learning.php,
                    // which lives at the project root and uses this value directly as a <video> src.
                    $rel_path  = 'uploads/program_videos/' . $safe_name;

                    if (move_uploaded_file($file['tmp_name'], $dest_path)) {
                        try {
                            $pdo->prepare(
                                "INSERT INTO program_videos (program_id, teacher_id, title, video_url, video_type, order_index, status, approved_by, approved_at)
                                 VALUES (?, ?, ?, ?, 'file', ?, 'approved', ?, NOW())"
                            )->execute([$aav_program_id, $aav_teacher_id, $aav_title, $rel_path, $aav_order, $_SESSION['id']]);
                            $program_video_message = "✅ Video uploaded and published.";
                        } catch (PDOException $e) {
                            $program_video_message = "Database error: " . htmlspecialchars($e->getMessage());
                        }
                    } else {
                        $program_video_message = "Something went wrong saving the file.";
                    }
                }
            }
        }
    } else {
        $program_video_message = "Please fill in all fields (slot must be 1–10).";
    }
}

// ── Filters: teacher / program (GET params) ─────────────────────
$filter_teacher_id = intval($_GET['teacher_id'] ?? 0);
$filter_program_id = intval($_GET['program_id'] ?? 0);

// ── Fetch ALL program videos (any status) for the management table ─
try {
    $video_filter_sql = "SELECT pv.id, pv.title, pv.video_url, pv.video_type, pv.order_index, pv.status, pv.created_at,
                pv.teacher_id, pv.program_id,
                p.title AS program_title, u.fullname AS teacher_name
         FROM program_videos pv
         JOIN programs p ON p.id = pv.program_id
         JOIN users u ON u.id = pv.teacher_id
         WHERE 1=1";
    $video_filter_params = [];
    if ($filter_teacher_id > 0) {
        $video_filter_sql .= " AND pv.teacher_id = ?";
        $video_filter_params[] = $filter_teacher_id;
    }
    if ($filter_program_id > 0) {
        $video_filter_sql .= " AND pv.program_id = ?";
        $video_filter_params[] = $filter_program_id;
    }
    $video_filter_sql .= " ORDER BY (pv.status = 'pending') DESC, pv.created_at DESC";

    $pv_stmt = $pdo->prepare($video_filter_sql);
    $pv_stmt->execute($video_filter_params);
    $pending_program_videos = $pv_stmt->fetchAll();
} catch (PDOException $e) {
    $pending_program_videos = [];
}

// ── Render the submitted-videos table rows (reused by both the normal
//    page load and the AJAX filter endpoint below) ───────────────────
function render_pv_rows($pending_program_videos) {
    ob_start();
    if (!empty($pending_program_videos)):
        foreach ($pending_program_videos as $pv):
            ?>
            <tr>
                <td style="font-weight:700; color:#f8fafc;">
                    <?= htmlspecialchars($pv['title']) ?>
                    <?php if (intval($pv['order_index']) === 1): ?>
                        <span class="ad-permission-pill ad-permission-approved" style="margin-left:6px;">FREE VIDEO</span>
                    <?php endif; ?>
                    <span class="ad-permission-pill ad-permission-pending" style="margin-left:6px;">
                        <i class="fa-solid <?= $pv['video_type'] === 'file' ? 'fa-file-video' : 'fa-brands fa-youtube' ?>"></i>
                        <?= $pv['video_type'] === 'file' ? 'Uploaded File' : 'YouTube' ?>
                    </span>
                    <div style="color:#64748b; font-size:.78rem; font-weight:400; margin-top:2px;">
                        <a href="<?= preg_match('#^https?://#i', $pv['video_url']) ? htmlspecialchars($pv['video_url']) : '../' . htmlspecialchars($pv['video_url']) ?>" target="_blank" style="color:#38bdf8; text-decoration:underline;">Preview link →</a>
                    </div>
                </td>
                <td style="color:#cbd5e1; font-size:.88rem;"><?= htmlspecialchars($pv['program_title']) ?></td>
                <td style="color:#cbd5e1; font-size:.88rem;"><?= htmlspecialchars($pv['teacher_name']) ?></td>
                <td>
                    <span class="ad-permission-pill ad-permission-<?= htmlspecialchars($pv['status']) ?>">
                        <?= ucfirst($pv['status']) ?>
                    </span>
                </td>
                <td style="text-align:right; white-space:nowrap;">
                    <?php if ($pv['status'] === 'pending'): ?>
                        <form action="admin_program_videos.php" method="POST" style="display:inline;">
                            <input type="hidden" name="_review_program_video" value="1">
                            <input type="hidden" name="video_id" value="<?= intval($pv['id']) ?>">
                            <input type="hidden" name="decision" value="approve">
                            <button type="submit" class="ad-live-toggle-btn ad-live-toggle-grant">
                                <i class="fa-solid fa-check"></i> Approve
                            </button>
                        </form>
                        <form action="admin_program_videos.php" method="POST" style="display:inline;">
                            <input type="hidden" name="_review_program_video" value="1">
                            <input type="hidden" name="video_id" value="<?= intval($pv['id']) ?>">
                            <input type="hidden" name="decision" value="reject">
                            <button type="submit" class="ad-live-toggle-btn ad-live-toggle-revoke">
                                <i class="fa-solid fa-xmark"></i> Reject
                            </button>
                        </form>
                    <?php endif; ?>
                    <button type="button" class="ad-live-toggle-btn adm-edit-pv-btn" style="background:rgba(56,189,248,.12); color:#38bdf8; border-color:rgba(56,189,248,.3);"
                        data-id="<?= intval($pv['id']) ?>"
                        data-title="<?= htmlspecialchars($pv['title'], ENT_QUOTES) ?>"
                        data-url="<?= htmlspecialchars($pv['video_url'], ENT_QUOTES) ?>"
                        data-type="<?= htmlspecialchars($pv['video_type'], ENT_QUOTES) ?>"
                        data-order="<?= intval($pv['order_index']) ?>">
                        <i class="fa-solid fa-pen"></i> Edit
                    </button>
                    <form action="admin_program_videos.php" method="POST" style="display:inline;" class="ad-confirm-form" data-confirm-msg="Delete this video permanently? This cannot be undone.">
                        <input type="hidden" name="_delete_program_video" value="1">
                        <input type="hidden" name="video_id" value="<?= intval($pv['id']) ?>">
                        <button type="submit" class="ad-live-toggle-btn ad-live-toggle-revoke">
                            <i class="fa-solid fa-trash"></i> Delete
                        </button>
                    </form>
                </td>
            </tr>
            <?php
        endforeach;
    else:
        ?>
        <tr>
            <td colspan="5">
                <div class="ad-empty">
                    <i class="fa-solid fa-circle-check"></i>
                    <p>No videos submitted yet.</p>
                </div>
            </td>
        </tr>
        <?php
    endif;
    return ob_get_clean();
}

// ── AJAX filter endpoint: returns just the table rows as JSON so the
//    filters can refresh the table without reloading the whole page ──
if (isset($_GET['ajax']) && $_GET['ajax'] === '1') {
    header('Content-Type: application/json');
    echo json_encode([
        'pending' => count(array_filter($pending_program_videos, fn($v) => $v['status'] === 'pending')),
        'rows'    => render_pv_rows($pending_program_videos),
    ]);
    exit;
}

// ── Fetch teacher list (for the admin add-video form) ─────────────
try {
    $video_teachers_list = $pdo->query("SELECT id, fullname FROM users WHERE LOWER(role) = 'teacher' ORDER BY fullname ASC")->fetchAll();
} catch (PDOException $e) {
    $video_teachers_list = [];
}


// ── Fetch the 4 programs with live counts (videos + enrollments) ──
try {
    $programs_overview = $pdo->query(
        "SELECT p.id, p.title, p.age_range, p.subjects, p.monthly_price, p.icon, p.status,
                (SELECT COUNT(*) FROM program_videos pv WHERE pv.program_id = p.id AND pv.status = 'approved') AS approved_video_count,
                (SELECT COUNT(*) FROM program_videos pv WHERE pv.program_id = p.id AND pv.status = 'pending')  AS pending_video_count,
                (SELECT COUNT(*) FROM enrollments e WHERE e.program_id = p.id)                                  AS enrolled_count,
                (SELECT COUNT(*) FROM enrollments e WHERE e.program_id = p.id AND e.status = 'active')          AS active_count
         FROM programs p
         ORDER BY p.id ASC"
    )->fetchAll();
} catch (PDOException $e) {
    $programs_overview = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Manage Program Videos</title>
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

<!-- ══════════════ EDIT VIDEO MODAL (single reusable modal, filled via JS) ══════════════ -->
<div class="apm-overlay" id="editVideoOverlay">
    <div class="apm-modal" role="dialog" aria-modal="true" aria-labelledby="epv-title">
        <div class="apm-header">
            <h2 class="apm-title" id="epv-title"><i class="fa-solid fa-pen"></i> Edit Program Video</h2>
            <button class="apm-close-btn" id="epvCloseBtn" aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form action="admin_program_videos.php" method="POST" enctype="multipart/form-data" id="editVideoForm">
            <input type="hidden" name="_edit_program_video" value="1">
            <input type="hidden" name="video_id" id="epv_id">

            <div class="apm-group">
                <label class="apm-label" for="epv_title">Video Title <span style="color:#f87171;">*</span></label>
                <input type="text" id="epv_title" name="title" class="apm-input" required maxlength="255">
            </div>

            <div class="apm-group" id="epvUrlGroup">
                <label class="apm-label" for="epv_url">Video URL <span style="color:#f87171;" id="epvUrlStar">*</span></label>
                <input type="text" id="epv_url" name="video_url" class="apm-input">
            </div>
            <div class="apm-group" id="epvFileGroup" style="display:none;">
                <label class="apm-label" for="epv_video_file">Replace Video File <span style="color:#64748b; font-weight:400; font-size:.78rem;">(leave blank to keep current)</span></label>
                <input type="file" id="epv_video_file" name="edit_video_file" class="apm-input" accept="video/mp4,video/webm,video/ogg,video/quicktime">
                <p id="epvFileNote" style="color:#94a3b8; font-size:.78rem; margin:6px 0 0;">
                    <i class="fa-solid fa-circle-info"></i> This video's storage path can't be typed manually — upload a new file here only if you want to replace it. The old file is deleted automatically.
                </p>
            </div>

            <div class="apm-group">
                <label class="apm-label" for="epv_order">Slot (1–10) <span style="color:#f87171;">*</span></label>
                <select id="epv_order" name="order_index" class="apm-select" required>
                    <?php for ($i = 1; $i <= 10; $i++): ?>
                        <option value="<?= $i ?>"><?= $i ?><?= $i === 1 ? ' — Free Preview' : '' ?></option>
                    <?php endfor; ?>
                </select>
            </div>

            <button type="submit" class="apm-submit-btn" id="epvSubmitBtn">
                <span class="apm-spinner" id="epvSpinner"></span>
                <i class="fa-solid fa-floppy-disk" id="epvBtnIcon"></i>
                <span id="epvBtnText">Save Changes</span>
            </button>
        </form>
    </div>
</div><!-- /.editVideoOverlay -->

<div class="main-content ad-wrap">

    <div class="ad-hero">
        <div class="ad-hero-left">
            <div class="ad-hero-icon"><i class="fa-solid fa-graduation-cap"></i></div>
            <div>
                <h1 class="ad-hero-title">Program Videos</h1>
                <p class="ad-hero-sub">Review, add, and manage teacher-submitted program videos</p>
            </div>
        </div>
        <div class="ad-hero-right">
            <a href="admin_dashboard.php" class="ad-back-btn"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        </div>
    </div>

    <?php if ($program_video_message): ?>
        <div class="ad-flash ad-flash-success" id="adFlash1">
            <i class="fa-solid fa-circle-check"></i> <?= $program_video_message ?>
        </div>
    <?php endif; ?>

    <!-- Add Video Directly -->
    <p class="ad-section-title" style="margin-top:36px;"><i class="fa-solid fa-circle-plus"></i> Add Video Directly</p>
    <div class="ad-card">
        <?php if (!empty($video_teachers_list) && !empty($programs_overview)): ?>
            <form action="admin_program_videos.php" method="POST" enctype="multipart/form-data" id="aavForm">
                <input type="hidden" name="_admin_add_program_video" value="1">
                <div style="display:flex; gap:14px; margin-bottom:12px;">
                    <label style="display:flex; align-items:center; gap:6px; font-size:.85rem; color:#cbd5e1; cursor:pointer;">
                        <input type="radio" name="video_source" value="youtube" checked onchange="aavToggleSource(this.value)"> YouTube Link
                    </label>
                    <label style="display:flex; align-items:center; gap:6px; font-size:.85rem; color:#cbd5e1; cursor:pointer;">
                        <input type="radio" name="video_source" value="file" onchange="aavToggleSource(this.value)"> Upload File
                    </label>
                </div>
                <div style="display:grid; grid-template-columns: repeat(5, 1fr); gap:14px;">
                    <select name="program_id" class="apm-select" required>
                        <option value="">Program…</option>
                        <?php foreach ($programs_overview as $prog): ?>
                            <option value="<?= intval($prog['id']) ?>"><?= htmlspecialchars($prog['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <select name="teacher_id" class="apm-select" required>
                        <option value="">Credit to teacher…</option>
                        <?php foreach ($video_teachers_list as $t): ?>
                            <option value="<?= intval($t['id']) ?>"><?= htmlspecialchars($t['fullname']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="video_title" class="apm-input" placeholder="Video title" required>
                    <div id="aavUrlGroup">
                        <input type="url" name="video_url" class="apm-input" placeholder="https://youtube.com/watch?v=...">
                    </div>
                    <div id="aavFileGroup" style="display:none;">
                        <input type="file" name="video_file" class="apm-input" accept="video/mp4,video/webm,video/ogg,video/quicktime">
                    </div>
                    <select name="order_index" class="apm-select" required>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?= $i ?>">Slot <?= $i ?><?= $i === 1 ? ' (Free)' : '' ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="submit" class="ad-live-toggle-btn ad-live-toggle-grant" style="margin-top:14px;">
                    <i class="fa-solid fa-upload"></i> Add & Publish
                </button>
            </form>
        <?php else: ?>
            <div class="ad-empty">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <p>Need at least one teacher account and one program before adding a video here.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Program Video Management -->
    <p class="ad-section-title" style="margin-top:36px;"><i class="fa-solid fa-clapperboard"></i> Program Video Management</p>

    <form method="GET" action="admin_program_videos.php" id="pvFilterForm" style="display:flex; gap:12px; flex-wrap:wrap; align-items:end; margin-bottom:16px;">
        <div>
            <label class="apm-label">Filter by Teacher</label>
            <select name="teacher_id" id="pvTeacherFilter" class="apm-select">
                <option value="0">All Teachers</option>
                <?php foreach ($video_teachers_list as $t): ?>
                    <option value="<?= (int)$t['id'] ?>" <?= $filter_teacher_id === (int)$t['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($t['fullname']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="apm-label">Filter by Program</label>
            <select name="program_id" id="pvProgramFilter" class="apm-select">
                <option value="0">All Programs</option>
                <?php foreach ($programs_overview as $prog): ?>
                    <option value="<?= (int)$prog['id'] ?>" <?= $filter_program_id === (int)$prog['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($prog['title']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <a href="admin_program_videos.php" id="pvClearFiltersLink" class="ad-back-btn" style="<?= ($filter_teacher_id > 0 || $filter_program_id > 0) ? '' : 'display:none;' ?>">
            <i class="fa-solid fa-xmark"></i> Clear Filters
        </a>
    </form>

    <div class="ad-card">
        <div class="ad-card-header" style="color:#facc15; border-color:rgba(250,204,21,.15);">
            <span><i class="fa-solid fa-clapperboard"></i> Submitted Videos</span>
            <span class="ad-sync-badge" id="pvPendingBadge">
                <?= count(array_filter($pending_program_videos, fn($v) => $v['status'] === 'pending')) ?> pending
            </span>
        </div>
        <div style="overflow-x:auto;">
            <table class="ad-table">
                <thead>
                    <tr>
                        <th>Video</th>
                        <th>Program</th>
                        <th>Teacher</th>
                        <th>Status</th>
                        <th style="text-align:right;">Action</th>
                    </tr>
                </thead>
                <tbody id="pvTbody">
                    <?= render_pv_rows($pending_program_videos) ?>
                </tbody>
            </table>
        </div>
    </div>

</div><!-- /.main-content -->

<script>
function aavToggleSource(source) {
    const urlGroup  = document.getElementById('aavUrlGroup');
    const fileGroup = document.getElementById('aavFileGroup');
    if (source === 'file') {
        urlGroup.style.display  = 'none';
        fileGroup.style.display = '';
    } else {
        urlGroup.style.display  = '';
        fileGroup.style.display = 'none';
    }
}

/* ── Edit Video Modal ─────────────────────────────────────── */
const editVideoOverlay  = document.getElementById('editVideoOverlay');
const epvCloseBtn       = document.getElementById('epvCloseBtn');
const editVideoForm     = document.getElementById('editVideoForm');

function epvOpen()  { editVideoOverlay.classList.add('open');    document.body.style.overflow = 'hidden'; }
function epvClose() { editVideoOverlay.classList.remove('open'); document.body.style.overflow = ''; }

epvCloseBtn.addEventListener('click', epvClose);
editVideoOverlay.addEventListener('click', e => { if (e.target === editVideoOverlay) epvClose(); });
document.addEventListener('keydown', function(e){ if (e.key === 'Escape') epvClose(); });

document.getElementById('pvTbody').addEventListener('click', function(e){
    const btn = e.target.closest('.adm-edit-pv-btn');
    if (!btn) return;

    document.getElementById('epv_id').value    = btn.dataset.id;
    document.getElementById('epv_title').value = btn.dataset.title;
    document.getElementById('epv_url').value   = btn.dataset.url;
    document.getElementById('epv_order').value = btn.dataset.order;

    const isFile = btn.dataset.type === 'file';
    document.getElementById('epvUrlGroup').style.display  = isFile ? 'none' : 'block';
    document.getElementById('epvFileGroup').style.display = isFile ? 'block' : 'none';
    document.getElementById('epvUrlStar').style.display   = isFile ? 'none' : 'inline';
    document.getElementById('epv_video_file').value       = ''; // clear any previously chosen file

    epvOpen();
});

editVideoForm.addEventListener('submit', function(){
    document.getElementById('epvSpinner').style.display = 'inline-block';
    document.getElementById('epvBtnIcon').style.display  = 'none';
    document.getElementById('epvBtnText').textContent    = 'Saving…';
    document.getElementById('epvSubmitBtn').disabled     = true;
});
<?php if ($program_video_message && $is_edit_video_submit): ?> epvOpen(); <?php endif; ?>

(function(){
    const adcOverlay    = document.getElementById('adcOverlay');
    const adcMessage    = document.getElementById('adcMessage');
    const adcConfirmBtn = document.getElementById('adcConfirmBtn');
    const adcCancelBtn  = document.getElementById('adcCancelBtn');
    let adcPendingForm  = null;

    document.addEventListener('submit', function(e){
        const form = e.target.closest('form.ad-confirm-form');
        if (!form) return;
        e.preventDefault();
        adcPendingForm = form;
        adcMessage.textContent = form.getAttribute('data-confirm-msg') || 'Are you sure you want to continue?';
        adcOverlay.classList.add('open');
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

document.querySelectorAll('.ad-flash').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity .4s ease';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    }, 6000);
});

/* ── Smooth AJAX filtering for the Submitted Videos table ─────── */
(function(){
    const teacherSelect = document.getElementById('pvTeacherFilter');
    const programSelect = document.getElementById('pvProgramFilter');
    const clearLink      = document.getElementById('pvClearFiltersLink');
    const tbody           = document.getElementById('pvTbody');
    const badge            = document.getElementById('pvPendingBadge');

    function pvApplyFilters() {
        const params = new URLSearchParams();
        params.set('teacher_id', teacherSelect.value);
        params.set('program_id', programSelect.value);

        tbody.style.opacity = '0.4';

        fetch('admin_program_videos.php?' + params.toString() + '&ajax=1', {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
            .then(r => r.json())
            .then(data => {
                tbody.innerHTML = data.rows;
                tbody.style.opacity = '1';
                badge.textContent = data.pending + ' pending';
                clearLink.style.display = (teacherSelect.value !== '0' || programSelect.value !== '0') ? '' : 'none';
                history.pushState(null, '', 'admin_program_videos.php?' + params.toString());
            })
            .catch(() => {
                tbody.style.opacity = '1';
            });
    }

    teacherSelect.addEventListener('change', pvApplyFilters);
    programSelect.addEventListener('change', pvApplyFilters);

    clearLink.addEventListener('click', function(e){
        e.preventDefault();
        teacherSelect.value = '0';
        programSelect.value = '0';
        pvApplyFilters();
    });
})();
</script>
</body>
</html>