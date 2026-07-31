<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/../includes/db.php';

$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'teacher') {
    header("Location: ../account/login.php");
    exit();
}

$teacher_id   = $_SESSION['id'];
$teacher_name = $_SESSION['fullname'] ?? 'Teacher';

$success_message = '';
$error_message   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Upload a program video (goes in as 'pending' until admin approves)
    if (isset($_POST['action']) && $_POST['action'] === 'upload_program_video') {
        $pv_program_id = intval($_POST['program_id'] ?? 0);
        $pv_title      = trim($_POST['video_title'] ?? '');
        $pv_order      = intval($_POST['order_index'] ?? 0);
        $pv_source     = ($_POST['video_source'] ?? 'youtube') === 'file' ? 'file' : 'youtube';

        $pv_valid = $pv_program_id > 0 && !empty($pv_title) && $pv_order >= 1 && $pv_order <= 10;

        // Security: teacher can only upload to a program they've been assigned by admin
        if ($pv_valid) {
            $assign_check = $pdo->prepare("SELECT COUNT(*) FROM teacher_program_assignments WHERE teacher_id = ? AND program_id = ?");
            $assign_check->execute([$teacher_id, $pv_program_id]);
            if ($assign_check->fetchColumn() == 0) {
                $pv_valid = false;
                $error_message = "You haven't been assigned to this program by an admin.";
            }
        }

        if (!$pv_valid) {
            $error_message = $error_message ?: "Please fill in all video fields (slot must be 1–10).";
        } elseif ($pv_source === 'youtube') {
            $pv_url_raw = trim($_POST['video_url'] ?? '');
            if (empty($pv_url_raw)) {
                $error_message = "Please paste a YouTube link.";
            } else {
                try {
                    $pv_clean_url = getEmbedUrl($pv_url_raw);
                    $stmt = $pdo->prepare(
                        "INSERT INTO program_videos (program_id, teacher_id, title, video_url, video_type, order_index, status)
                         VALUES (?, ?, ?, ?, 'youtube', ?, 'pending')"
                    );
                    $stmt->execute([$pv_program_id, $teacher_id, $pv_title, $pv_clean_url, $pv_order]);
                    $success_message = "🎬 Video submitted! It'll appear to students once an admin approves it.";
                    notify_admins(
                        $pdo,
                        "New video submitted",
                        "$teacher_name submitted \"$pv_title\" for review.",
                        "admin/admin_program_videos.php",
                        "fa-solid fa-clapperboard"
                    );
                } catch (PDOException $e) {
                    $error_message = "Database Error: Unable to submit video. " . $e->getMessage();
                }
            }
        } else {
            // ── File upload ──────────────────────────────────────────
            if (empty($_FILES['video_file']) || $_FILES['video_file']['error'] !== UPLOAD_ERR_OK) {
                $error_message = "Please choose a video file to upload.";
            } else {
                $file = $_FILES['video_file'];
                $allowed_ext  = ['mp4', 'webm', 'ogg', 'mov'];
                $max_bytes    = 300 * 1024 * 1024; // 300MB
                $ext          = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

                if (!in_array($ext, $allowed_ext)) {
                    $error_message = "Unsupported file type. Allowed: " . implode(', ', $allowed_ext);
                } elseif ($file['size'] > $max_bytes) {
                    $error_message = "File is too large. Max size is 300MB.";
                } else {
                    $upload_dir = __DIR__ . '/../uploads/program_videos/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    $safe_name  = 'pv_' . $teacher_id . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    $dest_path  = $upload_dir . $safe_name;
                    // Stored unprefixed (relative to project root) so it stays playable from learning.php,
                    // which lives at the project root and uses this value directly as a <video> src.
                    $rel_path   = 'uploads/program_videos/' . $safe_name;

                    if (move_uploaded_file($file['tmp_name'], $dest_path)) {
                        try {
                            $stmt = $pdo->prepare(
                                "INSERT INTO program_videos (program_id, teacher_id, title, video_url, video_type, order_index, status)
                                 VALUES (?, ?, ?, ?, 'file', ?, 'pending')"
                            );
                            $stmt->execute([$pv_program_id, $teacher_id, $pv_title, $rel_path, $pv_order]);
                            $success_message = "🎬 Video uploaded! It'll appear to students once an admin approves it.";
                            notify_admins(
                                $pdo,
                                "New video submitted",
                                "$teacher_name submitted \"$pv_title\" for review.",
                                "admin/admin_program_videos.php",
                                "fa-solid fa-clapperboard"
                            );
                        } catch (PDOException $e) {
                            $error_message = "Database Error: Unable to submit video. " . $e->getMessage();
                        }
                    } else {
                        $error_message = "Something went wrong saving the file. Please try again.";
                    }
                }
            }
        }
    }

    // Delete a submitted program video — teachers may only delete their own
    // videos that are NOT yet approved (pending/rejected).
    if (isset($_POST['action']) && $_POST['action'] === 'delete_program_video') {
        $del_video_id = intval($_POST['video_id'] ?? 0);
        if ($del_video_id > 0) {
            try {
                $own_stmt = $pdo->prepare("SELECT * FROM program_videos WHERE id = ? AND teacher_id = ?");
                $own_stmt->execute([$del_video_id, $teacher_id]);
                $video_row = $own_stmt->fetch();

                if (!$video_row) {
                    $error_message = "Video not found or it doesn't belong to you.";
                } elseif ($video_row['status'] === 'approved') {
                    $error_message = "🔒 This video is live and approved — ask an admin to remove it.";
                } else {
                    $del_stmt = $pdo->prepare("DELETE FROM program_videos WHERE id = ? AND teacher_id = ?");
                    $del_stmt->execute([$del_video_id, $teacher_id]);

                    if ($video_row['video_type'] === 'file') {
                        $file_path = __DIR__ . '/../' . $video_row['video_url'];
                        if (is_file($file_path)) {
                            unlink($file_path);
                        }
                    }
                    $success_message = "🗑️ Video deleted.";
                }
            } catch (PDOException $e) {
                $error_message = "Database Error: " . $e->getMessage();
            }
        }
    }
}

// Fetch active programs this teacher has been assigned to (for the video-upload dropdown)
try {
    $programs_stmt = $pdo->prepare(
        "SELECT p.id, p.title
           FROM programs p
           JOIN teacher_program_assignments tpa ON tpa.program_id = p.id
          WHERE p.status = 'active' AND tpa.teacher_id = ?
          ORDER BY p.id ASC"
    );
    $programs_stmt->execute([$teacher_id]);
    $programs_list = $programs_stmt->fetchAll();
} catch (PDOException $e) {
    $programs_list = [];
}

// Fetch this teacher's submitted program videos (any status)
try {
    $my_videos_stmt = $pdo->prepare(
        "SELECT pv.id, pv.title, pv.order_index, pv.status, pv.created_at, pv.program_id, p.title AS program_title
         FROM program_videos pv
         JOIN programs p ON p.id = pv.program_id
         WHERE pv.teacher_id = ?
         ORDER BY pv.created_at DESC
         LIMIT 30"
    );
    $my_videos_stmt->execute([$teacher_id]);
    $my_program_videos = $my_videos_stmt->fetchAll();
} catch (PDOException $e) {
    $my_program_videos = [];
}

// Distinct programs represented in this teacher's video list — filter only shows if 2+
$video_filter_programs = [];
foreach ($my_program_videos as $mv) {
    $video_filter_programs[$mv['program_id']] = $mv['program_title'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Program Videos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/layout.css">
    <link rel="stylesheet" href="../assets/teacher.css">
</head>
<body>

<?php include __DIR__ . '/../includes/teacher_navbar.php'; ?>

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

<div class="main-content td-wrap">

    <div class="td-hero">
        <div class="td-hero-left">
            <img
                src="<?= !empty($_SESSION['profile_pic']) ? '../' . htmlspecialchars($_SESSION['profile_pic']) : '../assets/images/default-avatar.png' ?>"
                class="td-hero-avatar"
                alt="Profile Photo">
            <div>
                <h1 class="td-hero-title">Program Video Library</h1>
                <p class="td-hero-sub">Submit videos for your programs — admin reviews before they go live</p>
                <span class="td-hero-badge"><i class="fa-solid fa-graduation-cap"></i> Instructor</span>
            </div>
        </div>
        <div class="td-hero-right">
            <a href="teacher_dashboard.php" class="td-btn" style="background:rgba(255,255,255,.08); border:1px solid rgba(255,255,255,.15); text-decoration:none;">
                <i class="fa-solid fa-gauge-high"></i> Dashboard
            </a>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="td-alert td-alert-success">
            <i class="fa-solid fa-circle-check"></i>
            <?= $success_message ?>
        </div>
    <?php endif; ?>
    <?php if ($error_message): ?>
        <div class="td-alert td-alert-error">
            <i class="fa-solid fa-circle-exclamation"></i>
            <?= $error_message ?>
        </div>
    <?php endif; ?>

    <p class="td-section-title"><i class="fa-solid fa-clapperboard"></i> Submit a Video</p>

    <div class="td-card" style="margin-bottom:36px;">
        <?php if (!empty($programs_list)): ?>
            <form action="teacher_program_videos.php" method="POST" enctype="multipart/form-data" id="pvUploadForm">
                <input type="hidden" name="action" value="upload_program_video">

                <div class="td-form-group" style="margin-bottom:16px;">
                    <label class="td-form-label">Video Source</label>
                    <div style="display:flex; gap:18px; margin-top:4px;">
                        <label style="display:flex; align-items:center; gap:6px; font-size:.85rem; color:#cbd5e1; cursor:pointer;">
                            <input type="radio" name="video_source" value="youtube" checked onchange="pvToggleSource(this.value)"> YouTube Link
                        </label>
                        <label style="display:flex; align-items:center; gap:6px; font-size:.85rem; color:#cbd5e1; cursor:pointer;">
                            <input type="radio" name="video_source" value="file" onchange="pvToggleSource(this.value)"> Upload File
                        </label>
                    </div>
                </div>

                <div class="td-form-grid" style="grid-template-columns: repeat(4, 1fr); margin-bottom:0;">
                    <div class="td-form-group">
                        <label class="td-form-label">Program</label>
                        <select name="program_id" class="td-select" required>
                            <option value="">Select a program…</option>
                            <?php foreach ($programs_list as $prog): ?>
                                <option value="<?= intval($prog['id']) ?>"><?= htmlspecialchars($prog['title']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="td-form-group">
                        <label class="td-form-label">Video Title</label>
                        <input type="text" name="video_title" class="td-input" placeholder="e.g., Learning the Alphabet" required>
                    </div>
                    <div class="td-form-group" id="pvUrlGroup">
                        <label class="td-form-label">Video URL</label>
                        <input type="url" name="video_url" class="td-input" placeholder="https://youtube.com/watch?v=...">
                    </div>
                    <div class="td-form-group" id="pvFileGroup" style="display:none;">
                        <label class="td-form-label">Video File <span style="color:#64748b; font-weight:400;">(mp4, webm, ogg, mov — max 300MB)</span></label>
                        <input type="file" name="video_file" class="td-input" accept="video/mp4,video/webm,video/ogg,video/quicktime">
                    </div>
                    <div class="td-form-group">
                        <label class="td-form-label">Slot (1–10)</label>
                        <select name="order_index" class="td-select" required>
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?= $i ?>"><?= $i ?><?= $i === 1 ? ' — Free Preview' : '' ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </div>
                <button type="submit" class="td-btn td-btn-green" style="margin-top:16px;" id="pvSubmitBtn">
                    <i class="fa-solid fa-upload"></i> Submit for Admin Approval
                </button>
            </form>
        <?php else: ?>
            <div class="td-empty">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <p>You haven't been assigned to any program yet. Ask an admin to assign you one.</p>
            </div>
        <?php endif; ?>
    </div>

    <p class="td-section-title"><i class="fa-solid fa-list-check"></i> My Submitted Videos</p>

    <?php if (count($video_filter_programs) > 1): ?>
        <div style="margin-bottom:14px; max-width:280px;">
            <label class="td-form-label">Filter by Program</label>
            <select id="videoProgramFilter" class="td-select">
                <option value="0">All Programs</option>
                <?php foreach ($video_filter_programs as $pid => $ptitle): ?>
                    <option value="<?= (int)$pid ?>"><?= htmlspecialchars($ptitle) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php endif; ?>

    <div class="td-card" style="margin-bottom:36px;">
        <?php if (!empty($my_program_videos)): ?>
            <?php foreach ($my_program_videos as $mv): ?>
                <div class="td-class-strip" data-program-id="<?= (int)$mv['program_id'] ?>">
                    <div class="td-class-strip-left">
                        <div class="td-class-icon"><i class="fa-solid fa-film"></i></div>
                        <div>
                            <p class="td-class-title"><?= htmlspecialchars($mv['title']) ?></p>
                            <span class="td-class-meta">
                                <?= htmlspecialchars($mv['program_title']) ?> &nbsp;•&nbsp; Slot <?= intval($mv['order_index']) ?>
                                &nbsp;•&nbsp;
                                <span class="td-status-pill td-status-<?= htmlspecialchars($mv['status']) ?>"><?= ucfirst($mv['status']) ?></span>
                            </span>
                        </div>
                    </div>
                    <?php if ($mv['status'] === 'approved'): ?>
                        <span title="Live videos can only be removed by an admin" style="color:#64748b; font-size:.75rem; white-space:nowrap;">
                            <i class="fa-solid fa-lock"></i> Locked
                        </span>
                    <?php else: ?>
                        <form action="teacher_program_videos.php" method="POST" class="ad-confirm-form" data-confirm-msg="Delete this video? This cannot be undone." style="margin:0;">
                            <input type="hidden" name="action" value="delete_program_video">
                            <input type="hidden" name="video_id" value="<?= intval($mv['id']) ?>">
                            <button type="submit" class="td-btn-icon-delete" title="Delete video">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="td-empty">
                <i class="fa-solid fa-film"></i>
                <p>You haven't submitted any videos yet.</p>
            </div>
        <?php endif; ?>
    </div>

</div><!-- /.main-content -->

<script>
function pvToggleSource(source) {
    const urlGroup  = document.getElementById('pvUrlGroup');
    const fileGroup = document.getElementById('pvFileGroup');
    const urlInput  = urlGroup.querySelector('input');
    const fileInput = fileGroup.querySelector('input');

    if (source === 'file') {
        urlGroup.style.display  = 'none';
        fileGroup.style.display = '';
        urlInput.required  = false;
        fileInput.required = true;
    } else {
        urlGroup.style.display  = '';
        fileGroup.style.display = 'none';
        urlInput.required  = true;
        fileInput.required = false;
    }
}

const pvForm = document.getElementById('pvUploadForm');
if (pvForm) {
    pvForm.addEventListener('submit', function () {
        const btn = document.getElementById('pvSubmitBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Uploading…';
    });
}

document.querySelectorAll('.td-alert').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity .4s ease';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    }, 5000);
});

/* ── Instant program filter for "My Submitted Videos" ─────── */
(function(){
    const filterSelect = document.getElementById('videoProgramFilter');
    if (!filterSelect) return;
    const rows = document.querySelectorAll('.td-class-strip[data-program-id]');

    filterSelect.addEventListener('change', function(){
        const selected = filterSelect.value;
        rows.forEach(function(row){
            row.style.display = (selected === '0' || row.dataset.programId === selected) ? '' : 'none';
        });
    });
})();

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
</script>
</body>
</html>