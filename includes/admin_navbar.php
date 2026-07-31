<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Includes folder ke andar db.php ka sahi path
include_once __DIR__ . '/db.php';
if (!isset($base)) { $base = ''; } // safety fallback if caller forgot to set $base

// Sidebar open/closed state, read server-side from a cookie so the page
// renders with the CORRECT state from the very first byte — no waiting
// on JavaScript to fix it after the fact (which is what caused the
// visible "jhatka" on slower-loading pages).
$sks_sidebar_open = isset($_COOKIE['sksSidebarOpen']) && $_COOKIE['sksSidebarOpen'] === 'true';

// --- PROFILE IMAGE UPLOAD LOGIC (Error Protection ke sath) ---
// Isme verify ho raha hai ke POST request ho aur sach mein koi file bheji gayi ho
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['new_pfp']) && $_FILES['new_pfp']['error'] !== UPLOAD_ERR_NO_FILE) {
    if (isset($_SESSION['id'])) {
        $user_id = $_SESSION['id'];
        $file = $_FILES['new_pfp'];

        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileSize = $file['size'];
        $fileError = $file['error'];

        $fileExt = explode('.', $fileName);
        $fileActualExt = strtolower(end($fileExt));
        $allowed = array('jpg', 'jpeg', 'png', 'webp');

        if (in_array($fileActualExt, $allowed)) {
            if ($fileError === 0) {
                if ($fileSize < 5000000) { // 5MB Limit
                    
                    $uploadDir   = 'images/profile_pic/';                 // web-relative (stored in DB / rendered in browser)
                    $uploadDirFs = __DIR__ . '/../' . $uploadDir;            // real filesystem path (independent of caller depth)
                    if (!is_dir($uploadDirFs)) {
                        mkdir($uploadDirFs, 0755, true);
                    }

                    // Unique secure name creation
                    $newFileName = "profile_" . $user_id . "_" . time() . "." . $fileActualExt;
                    $fileDestination   = $uploadDir . $newFileName;   // web-relative, stored in DB
                    $fileDestinationFs = $uploadDirFs . $newFileName; // real path, used to actually write the file

                    if (move_uploaded_file($fileTmpName, $fileDestinationFs)) {
                        try {
                            // Fetch the currently stored picture path BEFORE overwriting it
                            $oldPicStmt = $pdo->prepare("SELECT profile_pic FROM users WHERE id = :id");
                            $oldPicStmt->execute([':id' => $user_id]);
                            $oldPic = $oldPicStmt->fetchColumn();

                            // Database update
                            $sql = "UPDATE users SET profile_pic = :profile_pic WHERE id = :id";
                            $stmt = $pdo->prepare($sql);
                            $stmt->execute([
                                ':profile_pic' => $fileDestination,
                                ':id' => $user_id
                            ]);

                            // Remove the OLD picture from disk, but only if it was a real
                            // custom upload inside images/profile_pic/ (never delete the
                            // default logo or any external/role-based avatar URL).
                            if (
                                !empty($oldPic) &&
                                strpos($oldPic, 'images/profile_pic/') === 0 &&
                                $oldPic !== $fileDestination &&
                                file_exists(__DIR__ . '/../' . $oldPic)
                            ) {
                                @unlink(__DIR__ . '/../' . $oldPic);
                            }
                            
                            // Instant session refresh for display
                            $_SESSION['profile_pic'] = $fileDestination;
                            
                            // Kisi bhi page se hit ho, usi active page par reload karega safely
                            echo "<script>window.location.href = '" . $_SERVER['REQUEST_URI'] . "';</script>";
exit();
                        } catch (PDOException $e) {
                            echo "<script>alert('Database Error: " . $e->getMessage() . "');</script>";
                        }
                    }
                } else {
                    echo "<script>alert('File size too large! Max 5MB allowed.');</script>";
                }
            }
        } else {
            echo "<script>alert('Invalid format! Only JPG, JPEG, PNG, & WEBP allowed.');</script>";
        }
    }
}

$current_page = basename($_SERVER['PHP_SELF']);

function isActive($page){
    global $current_page;
    return $current_page === $page ? 'active' : '';
}

// --- REAL NOTIFICATIONS (from database) ---
// NOTE: broadcast notifications (user_id IS NULL) share one `is_read` flag
// across everyone. That's a simple trade-off for now — if you later want
// each user to have their own read/unread state on broadcast messages,
// that needs a separate `notification_reads (notification_id, user_id)`
// table. Ask and I can add it.
$sks_notifications = [];
$sks_unread_count = 0;
$sks_my_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';

if (isset($_SESSION['id'])) {
    $notifStmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = :uid OR (user_id IS NULL AND (target_role = :role OR target_role IS NULL)) ORDER BY created_at DESC LIMIT 10");
    $notifStmt->execute([':uid' => $_SESSION['id'], ':role' => $sks_my_role]);
    $sks_notifications = $notifStmt->fetchAll();

    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE (user_id = :uid OR (user_id IS NULL AND (target_role = :role OR target_role IS NULL))) AND is_read = 0");
    $countStmt->execute([':uid' => $_SESSION['id'], ':role' => $sks_my_role]);
    $sks_unread_count = (int) $countStmt->fetchColumn();
} else {
    $notifStmt = $pdo->query("SELECT * FROM notifications WHERE user_id IS NULL AND target_role IS NULL ORDER BY created_at DESC LIMIT 10");
    $sks_notifications = $notifStmt->fetchAll();
}

function sks_time_ago($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}

// --- Deactivation request status (for the Settings modal; not applicable to admins) ---
$sks_deactivation_pending = false;
if (isset($_SESSION['id']) && strtolower(trim($_SESSION['role'] ?? '')) !== 'admin' && strtolower(trim($_SESSION['role'] ?? '')) !== 'administrator') {
    $pendingCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM deactivation_requests WHERE user_id = :uid AND status = 'pending'");
    $pendingCheckStmt->execute([':uid' => $_SESSION['id']]);
    $sks_deactivation_pending = ((int) $pendingCheckStmt->fetchColumn()) > 0;
}
?>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700&family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="<?= $base ?>assets/layout.css">

<style>
/* =========================================================
   SETTINGS MODAL (sks- prefix = unique, no clash with layout.css)
   Visual language matches .yt-account-dropdown: dark glass panel,
   blue neon border/glow, Orbitron headings, Baloo 2 body text.
========================================================= */
.sks-modal-overlay {
    display: none;
    position: fixed;
    top: 0; left: 0;
    width: 100%; height: 100%;
    background: rgba(2, 6, 23, 0.75);
    backdrop-filter: blur(6px);
    z-index: 1000000;
    align-items: center;
    justify-content: center;
}
.sks-modal-overlay.show { display: flex; }

.sks-modal {
    position: relative;
    width: 100%;
    max-width: 460px;
    max-height: 88vh;
    overflow-y: auto;
    background: linear-gradient(135deg, rgba(10, 11, 28, 0.98) 0%, rgba(17, 19, 41, 0.99) 100%);
    backdrop-filter: blur(25px) saturate(1.8);
    border: 1px solid rgba(56, 189, 248, 0.45);
    border-radius: 20px;
    padding: 30px;
    box-shadow: 0 25px 60px rgba(0, 0, 0, 0.85), 0 0 35px rgba(56, 189, 248, 0.25);
    box-sizing: border-box;
    animation: sksModalIn 0.22s cubic-bezier(0.175, 0.885, 0.32, 1.1);
}
@keyframes sksModalIn {
    from { opacity: 0; transform: translateY(10px) scale(0.97); }
    to { opacity: 1; transform: translateY(0) scale(1); }
}

.sks-modal-close {
    position: absolute;
    top: 16px; right: 16px;
    width: 34px; height: 34px;
    border-radius: 50%;
    border: 1px solid rgba(255,255,255,0.15);
    background: rgba(255,255,255,0.05);
    color: #94a3b8;
    cursor: pointer;
    transition: 0.2s;
}
.sks-modal-close:hover { color: #38bdf8; border-color: #38bdf8; background: rgba(56,189,248,0.1); }

.sks-modal-title {
    font-family: 'Orbitron', sans-serif;
    color: #38bdf8;
    font-size: 1.3rem;
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0 0 22px;
    padding-bottom: 18px;
    border-bottom: 1px solid rgba(56, 189, 248, 0.2);
}

.sks-modal-section { margin-bottom: 10px; }
.sks-modal-section-title {
    color: #e2e8f0;
    font-size: 1rem;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 14px;
}
.sks-modal-section-title i { color: #38bdf8; }

.sks-form-group { margin-bottom: 14px; }
.sks-form-label {
    display: block;
    color: #94a3b8;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 6px;
}
.sks-form-input {
    width: 100%;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.1);
    color: #fff;
    border-radius: 10px;
    padding: 10px 14px;
    font-size: 0.95rem;
    box-sizing: border-box;
    transition: 0.2s;
}
.sks-form-input:focus {
    outline: none;
    background: rgba(255, 255, 255, 0.05);
    border-color: #38bdf8;
    box-shadow: 0 0 8px rgba(56, 189, 248, 0.3);
}
.sks-password-wrap { position: relative; }
.sks-password-wrap .sks-form-input { padding-right: 42px; }
.sks-password-toggle {
    position: absolute;
    right: 10px;
    top: 50%;
    transform: translateY(-50%);
    background: none;
    border: none;
    color: #94a3b8;
    cursor: pointer;
    font-size: 0.9rem;
    padding: 6px;
    line-height: 1;
}
.sks-password-toggle:hover { color: #38bdf8; }

.sks-btn-primary {
    width: 100%;
    background: #38bdf8;
    color: #0d0f14;
    font-weight: 700;
    border-radius: 10px;
    padding: 12px;
    border: none;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: 0.3s;
}
.sks-btn-primary:hover { background: #0ea5e9; box-shadow: 0 0 15px rgba(56, 189, 248, 0.4); }
.sks-btn-primary:disabled { opacity: 0.6; cursor: not-allowed; }

.sks-modal-divider {
    height: 1px;
    background: rgba(56, 189, 248, 0.15);
    margin: 22px 0;
}

.sks-danger-zone {
    background: rgba(239, 68, 68, 0.05);
    border: 1px solid rgba(239, 68, 68, 0.25);
    border-radius: 14px;
    padding: 18px;
}
.sks-danger-title { color: #ff6b7a; }
.sks-danger-title i { color: #ff6b7a; }
.sks-danger-text {
    color: #94a3b8;
    font-size: 0.85rem;
    line-height: 1.6;
    margin-bottom: 16px;
}

.sks-btn-danger {
    width: 100%;
    background: transparent;
    color: #ff6b7a;
    font-weight: 700;
    border-radius: 10px;
    padding: 12px;
    border: 1px solid #ff4a5a;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    transition: 0.3s;
}
.sks-btn-danger:hover { background: #ff4a5a; color: #fff; box-shadow: 0 0 15px rgba(255, 74, 90, 0.4); }
.sks-btn-danger:disabled { opacity: 0.6; cursor: not-allowed; }

.sks-modal-msg { font-size: 0.85rem; margin: -6px 0 12px; min-height: 1em; }

@media (max-width: 480px) {
    .sks-modal { padding: 22px; max-width: 92%; }
}

/* Override just for the notification bell's unread COUNT badge.
   layout.css's .notification-badge is a plain 10px dot with no room
   for text — this combined-class rule (higher specificity, so it
   doesn't touch .notification-badge anywhere else) enlarges it just
   enough to show a number, keeping everything else about it the same. */
.notification-badge.sks-notif-count {
    width: 18px !important;
    height: 18px !important;
    font-size: 0.65rem !important;
    font-weight: 700 !important;
    color: #fff !important;
    line-height: 18px !important;
    text-align: center !important;
}

/* Live search suggestions dropdown */
.sks-search-suggestions {
    display: none;
    position: absolute;
    top: calc(100% + 10px);
    left: 0;
    width: 100%;
    background: rgba(10, 11, 28, 0.98);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(56, 189, 248, 0.3);
    border-radius: 18px;
    max-height: 420px;
    overflow-y: auto;
    z-index: 999999;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.65);
    padding: 10px;
}
.sks-search-suggestions.show { display: block; }
.sks-suggestion-item {
    display: flex;
    align-items: center;
    gap: 14px;
    padding: 14px 16px;
    color: #f1f5f9;
    text-decoration: none;
    font-size: 1.05rem;
    font-weight: 600;
    border-radius: 12px;
    font-family: 'Baloo 2', sans-serif;
    transition: background .15s ease, transform .15s ease;
}
.sks-suggestion-item + .sks-suggestion-item { margin-top: 3px; }
.sks-suggestion-item:hover { background: rgba(56, 189, 248, 0.14); transform: translateX(3px); }
.sks-suggestion-item i {
    width: 42px;
    height: 42px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(56, 189, 248, 0.15);
    color: #38bdf8;
    border-radius: 12px;
    font-size: 1.1rem;
}
.sks-suggestion-empty {
    padding: 40px 20px;
    color: #64748b;
    font-size: 1.05rem;
    font-weight: 600;
    text-align: center;
    font-family: 'Baloo 2', sans-serif;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px;
}
.sks-suggestion-empty i { font-size: 1.8rem; color: #334155; }
.sks-search-suggestions::-webkit-scrollbar { width: 5px; }
.sks-search-suggestions::-webkit-scrollbar-track { background: transparent; }
.sks-search-suggestions::-webkit-scrollbar-thumb {
    background: rgba(56, 189, 248, 0.35);
    border-radius: 10px;
}
.sks-search-suggestions::-webkit-scrollbar-thumb:hover { background: rgba(56, 189, 248, 0.55); }
.sks-search-suggestions { scrollbar-width: thin; scrollbar-color: rgba(56, 189, 248, 0.35) transparent; }
</style>

<div id="sk-navbar">
    <header>
        <div class="left-header">
            <i class="fa-solid fa-bars menu"></i>
            <div class="logo">
    <a href="<?= $base ?>index.php" style="text-decoration: none; display: flex; align-items: center; gap: 15px;">
        <div class="logo-icon">
            <img src="<?= $base ?>images/gg.png" alt="Logo">
        </div>
        <div class="logo-text">
            <h2>SafeKids<span>Space</span></h2>
        </div>
    </a>
</div>
        </div>
        
        <form action="<?= $base ?>search/search_results.php" method="GET" class="search-box" style="margin: 0; padding: 0; display: flex; position:relative;" id="sksSearchForm" autocomplete="off">
            <input type="text" name="q" id="sksSearchInput" placeholder="Search videos, lessons, and fun..." required value="<?php echo isset($_GET['q']) ? htmlspecialchars($_GET['q']) : ''; ?>">
            <button type="button" id="sksSearchClearBtn" class="sks-search-clear" aria-label="Clear search" style="display:none;"><i class="fa-solid fa-xmark"></i></button>
            <button type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
            <div class="sks-search-suggestions" id="sksSearchSuggestions"></div>
        </form>
        
        <div class="right-header" style="display:flex; align-items:center; gap:16px;">
            
            <div class="notification-wrapper">
                <div class="notification-bell" id="notifBellBtn" title="Notifications">
                    <i class="fa-regular fa-bell"></i>
                    <span class="notification-badge sks-notif-count" id="notifBadge" style="<?= $sks_unread_count > 0 ? '' : 'display:none;'; ?>">
                        <?= $sks_unread_count > 9 ? '9+' : $sks_unread_count; ?>
                    </span>
                </div>
                
                <div class="notification-panel" id="notifDropPanel">
                    <div class="panel-header">
                        <h5>Notifications</h5>
                        <span class="mark-read-btn" id="clearNotifBtn">Clear All</span>
                    </div>
                    <div class="panel-body">
                        <?php if (empty($sks_notifications)): ?>
                            <div style="padding: 24px; text-align: center; color: #64748b; font-size: 0.8rem; font-family:'Baloo 2';">No new notifications</div>
                        <?php else: ?>
                            <?php foreach ($sks_notifications as $n): ?>
                            <a href="<?= $n['link'] ? $base . htmlspecialchars($n['link']) : '#'; ?>" class="notif-item notif-db-item" data-id="<?= $n['id']; ?>">
                                <div class="notif-icon"><i class="<?= htmlspecialchars($n['icon'] ?: 'fa-solid fa-bell'); ?>"></i></div>
                                <div class="notif-content">
                                    <h6><?= htmlspecialchars($n['title']); ?></h6>
                                    <p><?= htmlspecialchars($n['message']); ?></p>
                                    <small style="color:#64748b; font-size:0.7rem;"><?= sks_time_ago($n['created_at']); ?></small>
                                </div>
                            </a>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (isset($_SESSION['id'])): ?>
                
                <div class="yt-profile-wrapper">
                    <button class="yt-profile-trigger-btn" id="ytProfileBtn" title="Account Options">
                        <?php 
                            // Resolves primary avatar fallback sequence mapping cleanly
                            $active_avatar = "images/gg.png";
                            if (!empty($_SESSION['profile_pic']) && file_exists(__DIR__ . '/../' . $_SESSION['profile_pic'])) {
                                $active_avatar = $_SESSION['profile_pic'];
                            } elseif (!empty($_SESSION['role'])) {
                                if ($_SESSION['role'] === 'parent') { $active_avatar = "https://cdn-icons-png.flaticon.com/512/4333/4333609.png"; }
                                elseif ($_SESSION['role'] === 'teacher') { $active_avatar = "https://cdn-icons-png.flaticon.com/512/1995/1995539.png"; }
                                elseif ($_SESSION['role'] === 'admin') { $active_avatar = "https://cdn-icons-png.flaticon.com/512/2206/2206368.png"; }
                            }
                        ?>
                        <img src="<?= htmlspecialchars(preg_match('#^https?://#i', $active_avatar) ? $active_avatar : $base . $active_avatar); ?>" class="yt-avatar-frame" alt="User Profile Menu">
                    </button>
                    
                    <div class="yt-account-dropdown" id="ytProfileDropdown">
                        <div class="yt-dropdown-identity-section">
                            <img src="<?= htmlspecialchars(preg_match('#^https?://#i', $active_avatar) ? $active_avatar : $base . $active_avatar); ?>" alt="Profile Focus">
                            <div class="yt-identity-details">
                                <span class="yt-display-name"><?= htmlspecialchars($_SESSION['fullname'] ?? 'Space Cadet'); ?></span>
                                <span class="yt-display-username">@<?= htmlspecialchars($_SESSION['username'] ?? 'user'); ?></span>
                                <span class="yt-role-badge"><?= htmlspecialchars($_SESSION['role'] ?? 'guest'); ?></span>
                            </div>
                        </div>
                        
                        <button type="button" class="yt-dropdown-item-link" id="pfpUploadTrigger">
                            <i class="fa-solid fa-camera-rotate"></i> Change Profile Picture
                        </button>

                        <button type="button" class="yt-dropdown-item-link" id="sksSettingsTrigger">
                            <i class="fa-solid fa-gear"></i> Settings
                        </button>
                        
  <?php 
                            // 1. Setup default target path and sanitize role data strings
                            $dash_target = "index.php";
                            $user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';

                            // 2. Set the destination path securely based on sanitized data
                            if ($user_role === 'admin' || $user_role === 'administrator') { 
                                $dash_target = "admin/admin_dashboard.php"; 
                            } elseif ($user_role === 'parent') { 
                                $dash_target = "parent/parent_dashboard.php"; 
                            } elseif ($user_role === 'teacher') { 
                                $dash_target = "teacher/teacher_dashboard.php"; 
                            }
                        ?>
                        
                        <?php if ($user_role === 'admin' || $user_role === 'administrator'): ?>
                            <a href="<?= $base . htmlspecialchars($dash_target); ?>" class="yt-dropdown-item-link">
                                <i class="fa-solid fa-gauge-high"></i> Management Panel
                            </a>
                        <?php endif; ?>
                        <div class="yt-dropdown-divider"></div>
                        
                        <a href="<?= $base ?>account/logout.php" class="yt-dropdown-item-link" style="color: #ef4444;">
                            <i class="fa-solid fa-right-from-bracket" style="color: #ef4444;"></i> Sign Out
                        </a>
                    </div>
                </div>

             <form id="pfpAsynchForm" action="" method="POST" enctype="multipart/form-data" style="display:none; margin:0; padding:0;">
                    <input type="file" name="new_pfp" id="hiddenPfpFileInput" accept="image/png, image/jpeg, image/jpg, image/webp">
                </form>

            <?php else: ?>
                <a href="<?= $base ?>account/login.php" class="signin-btn" style="text-decoration:none; text-align:center; margin:0;">Sign In</a>
            <?php endif; ?>
        </div>
    </header>

    <?php if ($sks_sidebar_open): ?>
    <style>:root { --sidebar-width: 260px; }</style>
    <?php endif; ?>
    <style id="sksNoTransitionOnLoad">
        .main-content, .galaxy-footer { transition: none !important; }
    </style>
    <aside class="sidebar<?= $sks_sidebar_open ? '' : ' closed' ?>" id="sidebar">
    <style id="sksTopLoaderStyle">
        #sksTopLoader {
            position: fixed; top: 0; left: 0; height: 3px; width: 0; z-index: 99999;
            background: linear-gradient(90deg, #38bdf8, #a855f7);
            box-shadow: 0 0 8px rgba(56,189,248,.6);
            transition: width 0.4s ease, opacity 0.3s ease; opacity: 0;
        }
    </style>
    <script>
    // Let the very first paint happen with transitions switched off, then
    // turn them back on — so real clicks later still animate smoothly.
    requestAnimationFrame(function(){
        requestAnimationFrame(function(){
            var s = document.getElementById('sksNoTransitionOnLoad');
            if (s) s.remove();
        });
    });

    // Thin top progress bar — shows the moment you click an internal link
    // or submit a form, so slower pages feel responsive instead of just
    // going blank white while the server prepares the next page.
    (function(){
        var bar = document.createElement('div');
        bar.id = 'sksTopLoader';
        document.body.appendChild(bar);

        function startLoader() {
            bar.style.opacity = '1';
            bar.style.width = '15%';
            setTimeout(function(){ bar.style.width = '45%'; }, 100);
            setTimeout(function(){ bar.style.width = '70%'; }, 400);
            setTimeout(function(){ bar.style.width = '88%'; }, 900);
        }

        document.addEventListener('click', function(e){
            var link = e.target.closest('a');
            if (!link) return;
            if (link.target && link.target !== '_self') return;
            if (link.hasAttribute('download')) return;
            var href = link.getAttribute('href');
            if (!href || href.charAt(0) === '#' || href.indexOf('javascript:') === 0 || href.indexOf('mailto:') === 0 || href.indexOf('tel:') === 0) return;
            try {
                var url = new URL(href, window.location.href);
                if (url.origin !== window.location.origin) return;
            } catch (e) { return; }
            startLoader();
        });

        document.addEventListener('submit', function(){
            startLoader();
        });

        // Reset instantly if the page is restored from the back/forward cache
        window.addEventListener('pageshow', function(){
            bar.style.transition = 'none';
            bar.style.width = '0';
            bar.style.opacity = '0';
        });
    })();
    </script>
        <ul>
            <li class="<?= ($current_page == 'admin_dashboard.php') ? 'active' : ''; ?>">
                <a href="<?= $base ?>admin/admin_dashboard.php"><i class="fa-solid fa-gauge-high"></i><span>Dashboard</span></a>
            </li>
            <li class="<?= ($current_page == 'admin_users.php') ? 'active' : ''; ?>">
                <a href="<?= $base ?>admin/admin_users.php"><i class="fa-solid fa-users"></i><span>Users</span></a>
            </li>
        </ul>
        <hr>
        <h3>Content</h3>
        <ul>
            <li class="<?= ($current_page == 'admin_videos.php') ? 'active' : ''; ?>">
                <a href="<?= $base ?>admin/admin_videos.php"><i class="fa-solid fa-video"></i><span>Videos</span></a>
            </li>
            <li class="<?= ($current_page == 'admin_books.php') ? 'active' : ''; ?>">
                <a href="<?= $base ?>admin/admin_books.php"><i class="fa-solid fa-book-open"></i><span>Books</span></a>
            </li>
            <li class="<?= ($current_page == 'admin_products.php') ? 'active' : ''; ?>">
                <a href="<?= $base ?>admin/admin_products.php"><i class="fa-solid fa-store"></i><span>Store Products</span></a>
            </li>
            <li class="<?= ($current_page == 'admin_orders.php') ? 'active' : ''; ?>">
                <a href="<?= $base ?>admin/admin_orders.php"><i class="fa-solid fa-cart-shopping"></i><span>Store Orders</span></a>
            </li>
            <li class="<?= ($current_page == 'admin_fun_quiz.php') ? 'active' : ''; ?>">
                <a href="<?= $base ?>admin/admin_fun_quiz.php"><i class="fa-solid fa-face-laugh-wink"></i><span>Fun Quiz</span></a>
            </li>
        </ul>
        <hr>
        <h3>Programs</h3>
        <ul>
            <li class="<?= ($current_page == 'admin_manage_programs.php') ? 'active' : ''; ?>">
                <a href="<?= $base ?>admin/admin_manage_programs.php"><i class="fa-solid fa-layer-group"></i><span>Manage Programs</span></a>
            </li>
            <li class="<?= ($current_page == 'admin_program_videos.php') ? 'active' : ''; ?>">
                <a href="<?= $base ?>admin/admin_program_videos.php"><i class="fa-solid fa-graduation-cap"></i><span>Program Videos</span></a>
            </li>
            <li class="<?= ($current_page == 'admin_quizzes.php') ? 'active' : ''; ?>">
                <a href="<?= $base ?>admin/admin_quizzes.php"><i class="fa-solid fa-circle-question"></i><span>Assigned Quiz</span></a>
            </li>
            <li class="<?= ($current_page == 'admin_live_classes.php') ? 'active' : ''; ?>">
                <a href="<?= $base ?>admin/admin_live_classes.php"><i class="fa-solid fa-satellite-dish"></i><span>Live Classes</span></a>
            </li>
        </ul>
        <hr>
        <h3>Teachers</h3>
        <ul>
            <li class="<?= ($current_page == 'admin_add_teacher.php') ? 'active' : ''; ?>">
                <a href="<?= $base ?>admin/admin_add_teacher.php"><i class="fa-solid fa-user-plus"></i><span>Add Teacher</span></a>
            </li>
            <li class="<?= ($current_page == 'admin_career_applications.php') ? 'active' : ''; ?>">
                <a href="<?= $base ?>admin/admin_career_applications.php"><i class="fa-solid fa-briefcase"></i><span>Career Applications</span></a>
            </li>
        </ul>
        <hr>
        <h3>Parents</h3>
        <ul>
            <li class="<?= ($current_page == 'admin_payments.php') ? 'active' : ''; ?>">
                <a href="<?= $base ?>admin/admin_payments.php"><i class="fa-solid fa-money-check-dollar"></i><span>Payments</span></a>
            </li>
            <li>
                <a href="<?= $base ?>index.php"><i class="fa-solid fa-arrow-left"></i><span>Back to Website</span></a>
            </li>
        </ul>
    </aside>
    <div class="sidebar-backdrop" id="sidebar-backdrop"></div>
</div>

<?php if (isset($_SESSION['id'])): ?>
<div class="sks-modal-overlay" id="sksSettingsOverlay">
    <div class="sks-modal">
        <button type="button" class="sks-modal-close" id="sksSettingsClose">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <h3 class="sks-modal-title"><i class="fa-solid fa-gear"></i> Account Settings</h3>

        <!-- Change Password -->
        <div class="sks-modal-section">
            <h4 class="sks-modal-section-title"><i class="fa-solid fa-lock"></i> Change Password</h4>
            <form id="sksPasswordForm">
                <div class="sks-form-group">
                    <label class="sks-form-label">Current Password</label>
                    <div class="sks-password-wrap">
                        <input type="password" name="current_password" id="sksCurrentPassword" class="sks-form-input" required>
                        <button type="button" class="sks-password-toggle" data-target="sksCurrentPassword" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>
                <div class="sks-form-group">
                    <label class="sks-form-label">New Password</label>
                    <div class="sks-password-wrap">
                        <input type="password" name="new_password" id="sksNewPassword" class="sks-form-input" required minlength="6">
                        <button type="button" class="sks-password-toggle" data-target="sksNewPassword" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>
                <div class="sks-form-group">
                    <label class="sks-form-label">Confirm New Password</label>
                    <div class="sks-password-wrap">
                        <input type="password" name="confirm_password" id="sksConfirmPassword" class="sks-form-input" required minlength="6">
                        <button type="button" class="sks-password-toggle" data-target="sksConfirmPassword" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                    </div>
                </div>
                <p class="sks-modal-msg" id="sksPasswordMsg"></p>
                <button type="submit" class="sks-btn-primary" id="sksPasswordBtn">
                    <i class="fa-solid fa-floppy-disk"></i> Update Password
                </button>
            </form>
        </div>

        <?php
        $sks_current_role = strtolower(trim($_SESSION['role'] ?? ''));
        if ($sks_current_role !== 'admin' && $sks_current_role !== 'administrator'):
        ?>
        <div class="sks-modal-divider"></div>

        <!-- Deactivate Account (not shown for admins) -->
        <div class="sks-modal-section sks-danger-zone">
            <h4 class="sks-modal-section-title sks-danger-title"><i class="fa-solid fa-triangle-exclamation"></i> Deactivate Account</h4>

            <?php if ($sks_deactivation_pending): ?>
                <p class="sks-danger-text">
                    <i class="fa-solid fa-hourglass-half"></i> Your deactivation request has been submitted and is waiting for admin review. You can keep using your account until then.
                </p>
            <?php else: ?>
                <p class="sks-danger-text">Deactivating your account will submit a request for admin approval. Once approved, you won't be able to sign in until an admin reactivates it.</p>
                <form id="sksDeleteForm">
                    <div class="sks-form-group">
                        <label class="sks-form-label">Enter your password to confirm</label>
                        <div class="sks-password-wrap">
                            <input type="password" name="confirm_delete_password" id="sksDeletePassword" class="sks-form-input" required>
                            <button type="button" class="sks-password-toggle" data-target="sksDeletePassword" aria-label="Show password"><i class="fa-solid fa-eye"></i></button>
                        </div>
                    </div>
                    <p class="sks-modal-msg" id="sksDeleteMsg"></p>
                    <button type="submit" class="sks-btn-danger" id="sksDeleteBtn">
                        <i class="fa-solid fa-user-slash"></i> Request Account Deactivation
                    </button>
                </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
document.addEventListener("DOMContentLoaded", function(){
    const menu = document.querySelector('#sk-navbar .menu');
    const sidebar = document.querySelector('#sidebar');
    if (sidebar) {
        const activeSidebarItem = sidebar.querySelector('li.active');
        if (activeSidebarItem) {
            activeSidebarItem.scrollIntoView({ block: 'nearest' });
        }
    }
    const backdrop = document.querySelector('#sidebar-backdrop');
    
    const notifBellBtn = document.getElementById('notifBellBtn');
    const notifDropPanel = document.getElementById('notifDropPanel');
    const clearNotifBtn = document.getElementById('clearNotifBtn');
    const notifBadge = document.getElementById('notifBadge');
    
    const ytProfileBtn = document.getElementById('ytProfileBtn');
    const ytProfileDropdown = document.getElementById('ytProfileDropdown');
    const pfpUploadTrigger = document.getElementById('pfpUploadTrigger');
    const hiddenPfpFileInput = document.getElementById('hiddenPfpFileInput');
    const pfpAsynchForm = document.getElementById('pfpAsynchForm');

    const sksSettingsTrigger = document.getElementById('sksSettingsTrigger');
    const sksSettingsOverlay = document.getElementById('sksSettingsOverlay');
    const sksSettingsClose = document.getElementById('sksSettingsClose');
    const sksPasswordForm = document.getElementById('sksPasswordForm');
    const sksDeleteForm = document.getElementById('sksDeleteForm');
    const sksSearchInput = document.getElementById('sksSearchInput');
    const sksSearchSuggestions = document.getElementById('sksSearchSuggestions');
    
    const root = document.documentElement;

    // Show/hide toggle for every password field in the settings modal
    document.querySelectorAll('.sks-password-toggle').forEach(function(btn){
        btn.addEventListener('click', function(){
            const input = document.getElementById(btn.dataset.target);
            const icon = btn.querySelector('i');
            if (!input) return;
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });

    function syncSidebarVariables() {
        if (window.innerWidth <= 768) {
            root.style.setProperty('--sidebar-width', '0px');
        } else {
            if (sidebar.classList.contains('closed')) {
                root.style.setProperty('--sidebar-width', '90px');
            } else {
                root.style.setProperty('--sidebar-width', '260px');
            }
        }
    }

    if(menu && sidebar){
        menu.addEventListener('click', (e) => {
            e.stopPropagation();
            if(window.innerWidth <= 768){
                sidebar.classList.toggle('mobile-show');
                backdrop.classList.toggle('show');
            } else {
                sidebar.classList.toggle('closed');
                const isOpen = !sidebar.classList.contains('closed');
                document.cookie = 'sksSidebarOpen=' + isOpen + '; path=/; max-age=31536000; SameSite=Lax';
            }
            syncSidebarVariables();
        });
    }

    /* ================= NOTIFICATION CONTROL INTERACTION ================= */
    if (notifBellBtn && notifDropPanel) {
        notifBellBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            // Close the account profile panel if it is currently expanded
            if(ytProfileDropdown) ytProfileDropdown.classList.remove('show');
            
            notifDropPanel.classList.toggle('show');
            notifBellBtn.classList.toggle('active');
        });
    }

    if (clearNotifBtn) {
        clearNotifBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            fetch('<?= $base ?>account/notifications_mark_read.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=clear_all'
            }).catch(() => {});

            const body = notifDropPanel.querySelector('.panel-body');
            if(body) {
                body.innerHTML = '<div style="padding: 24px; text-align: center; color: #64748b; font-size: 0.8rem; font-family:\'Baloo 2\';">No new notifications</div>';
            }
            if(notifBadge) notifBadge.style.display = 'none';
        });
    }

    // Mark an individual notification as read (without blocking navigation)
    document.querySelectorAll('.notif-db-item').forEach(item => {
        item.addEventListener('click', () => {
            const id = item.getAttribute('data-id');
            if (navigator.sendBeacon) {
                const data = new Blob(['action=mark_one&id=' + encodeURIComponent(id)], { type: 'application/x-www-form-urlencoded' });
                navigator.sendBeacon('<?= $base ?>account/notifications_mark_read.php', data);
            }
        });
    });

    /* ================= YOUTUBE PROFILE INTERACTION RULES ================= */
    if (ytProfileBtn && ytProfileDropdown) {
        ytProfileBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            // Close the notification overlay panel if it is currently expanded
            if(notifDropPanel) {
                notifDropPanel.classList.remove('show');
                notifBellBtn.classList.remove('active');
            }
            
            ytProfileDropdown.classList.toggle('show');
        });
    }

    if (pfpUploadTrigger && hiddenPfpFileInput) {
        pfpUploadTrigger.addEventListener('click', (e) => {
            e.stopPropagation();
            hiddenPfpFileInput.click();
        });
    }

    if (hiddenPfpFileInput && pfpAsynchForm) {
        hiddenPfpFileInput.addEventListener('change', () => {
            if(hiddenPfpFileInput.value !== "") {
                pfpAsynchForm.submit();
            }
        });
    }

    /* ================= SETTINGS MODAL ================= */
    if (sksSettingsTrigger && sksSettingsOverlay) {
        sksSettingsTrigger.addEventListener('click', (e) => {
            e.stopPropagation();
            if (ytProfileDropdown) ytProfileDropdown.classList.remove('show');
            sksSettingsOverlay.classList.add('show');
        });
    }

    if (sksSettingsClose && sksSettingsOverlay) {
        sksSettingsClose.addEventListener('click', () => {
            sksSettingsOverlay.classList.remove('show');
        });
    }

    if (sksSettingsOverlay) {
        sksSettingsOverlay.addEventListener('click', (e) => {
            if (e.target === sksSettingsOverlay) {
                sksSettingsOverlay.classList.remove('show');
            }
        });
        // Prevent clicks inside the modal card from bubbling to the overlay/document
        const sksModalCard = sksSettingsOverlay.querySelector('.sks-modal');
        if (sksModalCard) {
            sksModalCard.addEventListener('click', (e) => e.stopPropagation());
        }
    }

    /* ================= CHANGE PASSWORD ================= */
    if (sksPasswordForm) {
        sksPasswordForm.addEventListener('submit', function(e){
            e.preventDefault();

            const btn = document.getElementById('sksPasswordBtn');
            const msg = document.getElementById('sksPasswordMsg');
            const current_password = document.getElementById('sksCurrentPassword').value;
            const new_password = document.getElementById('sksNewPassword').value;
            const confirm_password = document.getElementById('sksConfirmPassword').value;

            if (new_password !== confirm_password) {
                msg.textContent = 'New passwords do not match.';
                msg.style.color = '#ff6b7a';
                return;
            }

            btn.disabled = true;
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Updating...';
            msg.textContent = '';

            fetch('<?= $base ?>account/change_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ current_password, new_password, confirm_password })
            })
            .then(res => res.json())
            .then(data => {
                msg.textContent = data.message;
                msg.style.color = data.success ? '#34d399' : '#ff6b7a';
                if (data.success) {
                    sksPasswordForm.reset();
                    msg.textContent += ' Redirecting you to sign in again...';
                    setTimeout(() => { window.location.href = data.redirect || 'login.php'; }, 1600);
                }
            })
            .catch(() => {
                msg.textContent = 'Something went wrong. Please try again.';
                msg.style.color = '#ff6b7a';
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    }

    /* ================= DEACTIVATE ACCOUNT ================= */
    if (sksDeleteForm) {
        sksDeleteForm.addEventListener('submit', function(e){
            e.preventDefault();

            if (!confirm('Submit a deactivation request? Once an admin approves it, you will not be able to sign in until reactivated.')) {
                return;
            }

            const btn = document.getElementById('sksDeleteBtn');
            const msg = document.getElementById('sksDeleteMsg');
            const confirm_delete_password = document.getElementById('sksDeletePassword').value;

            btn.disabled = true;
            const originalText = btn.innerHTML;
            btn.innerHTML = 'Submitting...';
            msg.textContent = '';

            fetch('<?= $base ?>account/deactivate_account.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: new URLSearchParams({ confirm_delete_password })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    msg.textContent = data.message;
                    msg.style.color = '#34d399';
                    setTimeout(() => { window.location.reload(); }, 1400);
                } else {
                    msg.textContent = data.message;
                    msg.style.color = '#ff6b7a';
                    btn.disabled = false;
                    btn.innerHTML = originalText;
                }
            })
            .catch(() => {
                msg.textContent = 'Something went wrong. Please try again.';
                msg.style.color = '#ff6b7a';
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });
    }

    // Unified Global Context Clicks Closing All Navigation Popover Windows Layouts
    document.addEventListener('click', () => {
        if (notifDropPanel) notifDropPanel.classList.remove('show');
        if (notifBellBtn) notifBellBtn.classList.remove('active');
        if (ytProfileDropdown) ytProfileDropdown.classList.remove('show');
        if (sksSearchSuggestions) sksSearchSuggestions.classList.remove('show');
    });

    /* ================= SEARCH CLEAR (X) BUTTON ================= */
    const sksSearchClearBtn = document.getElementById('sksSearchClearBtn');
    if (sksSearchInput && sksSearchClearBtn) {
        const sksToggleClearBtn = () => {
            sksSearchClearBtn.style.display = sksSearchInput.value.length > 0 ? 'flex' : 'none';
        };
        sksToggleClearBtn();

        sksSearchInput.addEventListener('input', sksToggleClearBtn);

        sksSearchClearBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            sksSearchInput.value = '';
            sksToggleClearBtn();
            if (sksSearchSuggestions) {
                sksSearchSuggestions.classList.remove('show');
                sksSearchSuggestions.innerHTML = '';
            }
            sksSearchInput.focus();
        });
    }

    /* ================= LIVE SEARCH SUGGESTIONS ================= */
    if (sksSearchInput && sksSearchSuggestions) {
        let sksSearchDebounce;
        const sksEscapeHtml = (str) => {
            const div = document.createElement('div');
            div.textContent = str;
            return div.innerHTML;
        };

        sksSearchInput.addEventListener('input', () => {
            clearTimeout(sksSearchDebounce);
            const q = sksSearchInput.value.trim();

            if (q.length < 2) {
                sksSearchSuggestions.classList.remove('show');
                sksSearchSuggestions.innerHTML = '';
                return;
            }

            sksSearchDebounce = setTimeout(() => {
                fetch('<?= $base ?>search/search_suggestions.php?q=' + encodeURIComponent(q))
                    .then(res => res.json())
                    .then(items => {
                        if (!items.length) {
                            sksSearchSuggestions.innerHTML = '<div class="sks-suggestion-empty"><i class="fa-solid fa-magnifying-glass"></i>No matches found</div>';
                        } else {
                            sksSearchSuggestions.innerHTML = items.map(item =>
                                `<a href="<?= $base ?>${item.link}" class="sks-suggestion-item"><i class="${item.icon}"></i> ${sksEscapeHtml(item.title)}</a>`
                            ).join('');
                        }
                        sksSearchSuggestions.classList.add('show');
                    })
                    .catch(() => { sksSearchSuggestions.classList.remove('show'); });
            }, 250);
        });

        sksSearchInput.addEventListener('click', (e) => e.stopPropagation());
        sksSearchSuggestions.addEventListener('click', (e) => e.stopPropagation());
    }

    if(backdrop){
        backdrop.addEventListener('click', () => {
            sidebar.classList.remove('mobile-show');
            backdrop.classList.remove('show');
            syncSidebarVariables();
        });
    }

    window.addEventListener('resize', syncSidebarVariables);
    syncSidebarVariables();
});
</script>