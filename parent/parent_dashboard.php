<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/child_account.php';

$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'parent') {
    header("Location: ../account/login.php");
    exit();
}

$parent_id   = $_SESSION['id'];
$parent_name = $_SESSION['fullname'] ?? 'Parent';

$success_msg = '';
$error_msg   = '';
$child_credentials = null; // set only right after a successful "Create Child Account"

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {

    if ($_POST['action'] === 'link_child') {
        $identifier = trim($_POST['child_identifier'] ?? '');
        if ($identifier !== '') {
            try {
                // Matches on: their real fullname, a real email (older
                // self-registered accounts), OR just the username part of a
                // parent-generated login (e.g. typing "sam123" matches
                // "sam123@kids.safekidsspace.local" without the parent
                // needing to know/type that whole generated string).
                $as_generated_email = child_username_to_email($identifier);
                $s = $pdo->prepare("SELECT id, fullname FROM users WHERE (email = ? OR email = ? OR fullname = ?) AND role = 'student' LIMIT 1");
                $s->execute([$identifier, $as_generated_email, $identifier]);
                $found = $s->fetch();
                if ($found) {
                    $chk = $pdo->prepare("SELECT id FROM parent_monitoring WHERE parent_id = ? AND child_id = ?");
                    $chk->execute([$parent_id, $found['id']]);
                    if ($chk->fetch()) {
                        $error_msg = "⚠️ This child is already linked to your account.";
                    } else {
                        $ins = $pdo->prepare("INSERT INTO parent_monitoring (parent_id, child_id) VALUES (?, ?)");
                        $ins->execute([$parent_id, $found['id']]);
                        $success_msg = "✅ Child <strong>" . htmlspecialchars($found['fullname']) . "</strong> linked successfully!";
                    }
                } else {
                    $error_msg = "❌ No student found with that name or email.";
                }
            } catch (PDOException $e) {
                $error_msg = "Database error: " . $e->getMessage();
            }
        } else {
            $error_msg = "Please enter a child name or email.";
        }
    }

    if ($_POST['action'] === 'unlink_child') {
        $child_id = intval($_POST['child_id'] ?? 0);
        if ($child_id) {
            try {
                $del = $pdo->prepare("DELETE FROM parent_monitoring WHERE parent_id = ? AND child_id = ?");
                $del->execute([$parent_id, $child_id]);
                $success_msg = "🔓 Child unlinked from your account.";
            } catch (PDOException $e) {
                $error_msg = "Error: " . $e->getMessage();
            }
        }
    }

    if ($_POST['action'] === 'create_child_account') {
        $child_fullname = trim($_POST['child_fullname'] ?? '');
        $child_username = trim($_POST['child_username'] ?? '');
        $child_password = trim($_POST['child_password'] ?? '');

        if ($child_fullname === '') {
            $error_msg = "Please enter your child's name.";
        } elseif (($fmt_err = child_username_format_error($child_username)) !== null) {
            $error_msg = $fmt_err;
        } elseif (($fmt_err = child_password_format_error($child_password)) !== null) {
            $error_msg = $fmt_err;
        } else {
            $child_email = child_username_to_email($child_username);
            try {
                $pdo->beginTransaction();

                $check = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1 FOR UPDATE");
                $check->execute([$child_email]);
                if ($check->fetch()) {
                    $pdo->rollBack();
                    $error_msg = "That username is already taken — please choose another.";
                } else {
                    $hashed = password_hash($child_password, PASSWORD_BCRYPT);
                    $ins_user = $pdo->prepare(
                        "INSERT INTO users (fullname, email, password, role, profile_pic) VALUES (?, ?, ?, 'student', 'images/gg.png')"
                    );
                    $ins_user->execute([$child_fullname, $child_email, $hashed]);
                    $new_child_id = intval($pdo->lastInsertId());

                    $ins_link = $pdo->prepare("INSERT INTO parent_monitoring (parent_id, child_id) VALUES (?, ?)");
                    $ins_link->execute([$parent_id, $new_child_id]);

                    $pdo->commit();

                    // Shown once, right now, in a "save these" modal — never
                    // re-derivable afterwards since only the hash is stored.
                    $child_credentials = [
                        'fullname' => $child_fullname,
                        'username' => $child_username,
                        'login_id' => $child_email,
                        'password' => $child_password,
                    ];

                    notify_admins(
                        $pdo,
                        "New child account created",
                        ($_SESSION['fullname'] ?? 'A parent') . " created a child account for " . htmlspecialchars($child_fullname) . ".",
                        "admin/admin_users.php",
                        "fa-solid fa-child"
                    );
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $error_msg = "Error creating account: " . $e->getMessage();
            }
        }
    }
}

// ── Fetch linked children ─────────────────────────────────────────────────
$linked_children = [];
try {
    $s = $pdo->prepare("
        SELECT u.id, u.fullname, u.email, u.profile_pic, u.created_at,
               pm.last_watched_video, pm.last_action, pm.updated_at AS last_seen
        FROM parent_monitoring pm
        JOIN users u ON u.id = pm.child_id
        WHERE pm.parent_id = ?
    ");
    $s->execute([$parent_id]);
    $linked_children = $s->fetchAll();
} catch (PDOException $e) {
    $linked_children = [];
}

$selected_child_id = intval($_GET['child_id'] ?? ($linked_children[0]['id'] ?? 0));
$child_qs = $selected_child_id ? ('?child_id=' . $selected_child_id) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Parent Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700;800&family=Orbitron:wght@500;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/layout.css">
    <link rel="stylesheet" href="../assets/parent.css">
</head>
<body>

<?php include __DIR__ . '/../includes/parent_navbar.php'; ?>

<div class="adc-overlay" id="adcOverlay">
    <div class="adc-modal">
        <div class="adc-icon" id="adcIcon"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <h3 class="adc-title" id="adcTitle">Are you sure?</h3>
        <p class="adc-message" id="adcMessage"></p>
        <div class="adc-actions">
            <button type="button" class="adc-btn adc-btn-cancel" id="adcCancelBtn">Cancel</button>
            <button type="button" class="adc-btn adc-btn-confirm" id="adcConfirmBtn">Yes, Unlink</button>
        </div>
    </div>
</div>

<div class="main-content pd-wrap">

    <div class="pd-hero">
        <div class="pd-hero-left">
            <img
                src="<?= !empty($_SESSION['profile_pic']) ? '../' . htmlspecialchars($_SESSION['profile_pic']) : '../assets/images/default-avatar.png' ?>"
                class="pd-hero-avatar"
                alt="Profile Photo">
            <div>
                <h1 class="pd-hero-title">Welcome, <?= htmlspecialchars($parent_name) ?></h1>
                <p class="pd-hero-sub">Family Guardian Console — link, monitor &amp; manage your children</p>
                <span class="pd-hero-badge"><i class="fa-solid fa-user-shield"></i> Parent Account</span>
            </div>
        </div>
    </div>

    <?php if ($success_msg): ?>
        <div class="pd-alert pd-alert-success"><i class="fa-solid fa-circle-check"></i> <?= $success_msg ?></div>
    <?php endif; ?>
    <?php if ($error_msg): ?>
        <div class="pd-alert pd-alert-error"><i class="fa-solid fa-circle-exclamation"></i> <?= $error_msg ?></div>
    <?php endif; ?>

    <p class="pd-section-title"><i class="fa-solid fa-link" style="color:#7c3aed"></i> Link a Child Account</p>

    <div class="pd-link-card mb-4">
        <div style="display:flex; align-items:flex-end; gap:14px; flex-wrap:wrap;">
            <div style="flex:1; min-width:240px;">
                <label style="font-size:.8rem; color:#94a3b8; display:block; margin-bottom:8px;">
                    <i class="fa-solid fa-magnifying-glass me-1" style="color:#7c3aed"></i>
                    Already created by another parent? Link to the same child account
                </label>
                <form method="POST" style="display:flex; gap:10px; flex-wrap:wrap;">
                    <input type="hidden" name="action" value="link_child">
                    <input type="text" name="child_identifier" class="pd-input" style="flex:1; min-width:200px;"
                           placeholder="e.g. Sam Khan (full name)  or  sam123 (username)" required>
                    <button type="submit" class="pd-btn pd-btn-primary">
                        <i class="fa-solid fa-user-plus me-2"></i>Link Child
                    </button>
                </form>
            </div>
        </div>
        <p style="font-size:.77rem; color:#475569; margin-top:12px; margin-bottom:0;">
            <i class="fa-solid fa-circle-info me-1"></i>
            For a child who already has an account — enter their full name, or the username the other parent set for them.
        </p>
        <hr style="border-color:rgba(255,255,255,.08); margin:18px 0;">
        <div style="display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap;">
            <p style="font-size:.8rem; color:#94a3b8; margin:0;">
                <i class="fa-solid fa-wand-magic-sparkles me-1" style="color:#facc15"></i>
                Don't have a child account for them yet? Create one right here — no separate sign-up needed.
            </p>
            <button type="button" class="pd-btn" style="background:rgba(250,204,21,.12); border:1px solid rgba(250,204,21,.35); color:#facc15;" onclick="ccaOpen()">
                <i class="fa-solid fa-child"></i> Create Child Account
            </button>
        </div>
    </div>

    <!-- ═══════════════ CREATE CHILD ACCOUNT MODAL ═══════════════ -->
    <div class="popup-overlay enroll-popup-overlay" id="ccaOverlay" style="display:none;">
        <div class="popup-card enroll-popup-card" style="max-width:420px;">
            <div class="popup-header enroll-popup-header" style="padding-bottom:8px;">
                <div class="enroll-popup-icon"><i class="fa-solid fa-child"></i></div>
                <h2>Create Child Account</h2>
                <p style="opacity:.7; font-size:13px; margin:0;">You'll set their login yourself — no email needed.</p>
            </div>

            <form method="POST" id="ccaForm" style="padding:4px 24px 8px; display:flex; flex-direction:column; gap:12px;">
                <input type="hidden" name="action" value="create_child_account">

                <div>
                    <label class="pd-form-label">Child's Name</label>
                    <input type="text" name="child_fullname" id="ccaFullname" class="pd-input" style="width:100%;" placeholder="e.g. Sam" required autocomplete="off" value="<?= htmlspecialchars($child_fullname ?? '') ?>">
                </div>

                <div>
                    <label class="pd-form-label">Username <span style="opacity:.5; font-weight:400;">(they'll log in with this)</span></label>
                    <input type="text" name="child_username" id="ccaUsername" class="pd-input" style="width:100%;" placeholder="e.g. sam123" required autocomplete="off" maxlength="20" value="<?= htmlspecialchars($child_username ?? '') ?>">
                    <div id="ccaUsernameStatus" style="font-size:.75rem; margin-top:5px; min-height:16px;"></div>
                </div>

                <div>
                    <label class="pd-form-label">Password</label>
                    <div class="sks-password-wrap">
                        <input type="password" name="child_password" id="ccaPassword" class="pd-input" style="width:100%;" placeholder="At least 4 characters" required minlength="4">
                        <button type="button" class="sks-password-toggle" id="ccaPasswordToggle" aria-label="Show password">
                            <i class="fa-solid fa-eye" id="ccaPasswordIcon"></i>
                        </button>
                    </div>
                    <div style="font-size:.72rem; color:#64748b; margin-top:5px;">Keep it simple — something your child can type easily.</div>
                </div>

                <button type="submit" id="ccaSubmitBtn" class="popup-continue-btn" style="width:100%; margin-top:6px;">
                    <i class="fa-solid fa-user-plus"></i> Create Account
                </button>
            </form>

            <div class="popup-footer enroll-popup-footer" style="padding-top:8px;">
                <button type="button" class="enroll-popup-cancel-btn" style="flex:1;" onclick="ccaClose()">Cancel</button>
            </div>
        </div>
    </div>

    <?php if ($child_credentials): ?>
    <!-- ═══════════════ CREDENTIALS — SHOWN ONCE, RIGHT AFTER CREATION ═══════════════ -->
    <div class="popup-overlay enroll-popup-overlay" id="ccaCredsOverlay" style="display:flex;">
        <div class="popup-card enroll-popup-card" style="max-width:420px;">
            <div class="popup-header enroll-popup-header">
                <div class="enroll-popup-icon" style="background:rgba(52,211,153,.12); border-color:#34d399; color:#34d399;"><i class="fa-solid fa-circle-check"></i></div>
                <h2>Account Created!</h2>
                <p class="enroll-popup-text">
                    <?= htmlspecialchars($child_credentials['fullname']) ?>'s login is ready. Save these now — the password won't be shown again.
                </p>
            </div>
            <div style="padding:0 24px 8px; display:flex; flex-direction:column; gap:10px;">
                <div class="pd-input" style="width:100%; display:flex; justify-content:space-between; align-items:center; gap:10px;">
                    <span style="font-size:.72rem; color:#64748b;">LOGIN ID</span>
                    <strong style="font-family:'Orbitron',sans-serif; font-size:.85rem; color:#e2e8f0; word-break:break-all;"><?= htmlspecialchars($child_credentials['login_id']) ?></strong>
                </div>
                <div class="pd-input" style="width:100%; display:flex; justify-content:space-between; align-items:center; gap:10px;">
                    <span style="font-size:.72rem; color:#64748b;">PASSWORD</span>
                    <strong style="font-family:'Orbitron',sans-serif; font-size:.85rem; color:#facc15;"><?= htmlspecialchars($child_credentials['password']) ?></strong>
                </div>
            </div>
            <div class="popup-footer enroll-popup-footer">
                <button type="button" class="enroll-popup-confirm-btn" style="flex:1;" onclick="document.getElementById('ccaCredsOverlay').style.display='none';">
                    I've Saved It — Done
                </button>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($linked_children)): ?>
        <div class="pd-no-children">
            <i class="fa-solid fa-child-reaching"></i>
            <h3>No Children Linked Yet</h3>
            <p>Use the form above to link your child's account and start monitoring their activity.</p>
        </div>
    <?php else: ?>

        <p class="pd-section-title"><i class="fa-solid fa-users" style="color:#38bdf8"></i> Your Children</p>
        <div class="pd-children-tabs">
            <?php foreach ($linked_children as $child): ?>
                <?php
                    $cavatar = !empty($child['profile_pic']) ? '../' . htmlspecialchars($child['profile_pic']) : 'https://cdn-icons-png.flaticon.com/512/4333/4333609.png';
                    $isActive = ($child['id'] == $selected_child_id);
                ?>
                <a href="parent_dashboard.php?child_id=<?= $child['id'] ?>" class="pd-child-tab <?= $isActive ? 'active' : '' ?>">
                    <img src="<?= $cavatar ?>" alt="<?= htmlspecialchars($child['fullname']) ?>">
                    <?= htmlspecialchars($child['fullname']) ?>
                    <form method="POST" class="pd-unlink-form ad-confirm-form" data-confirm-msg="Unlink <?= htmlspecialchars($child['fullname']) ?> from your account? You can always link them again later.">
                        <input type="hidden" name="action" value="unlink_child">
                        <input type="hidden" name="child_id" value="<?= $child['id'] ?>">
                        <button type="submit" class="pd-unlink-btn" title="Unlink child">
                            <i class="fa-solid fa-xmark"></i>
                        </button>
                    </form>
                </a>
            <?php endforeach; ?>
        </div>

        <?php if ($selected_child_id): ?>
            <p class="pd-section-title" style="margin-top:36px;"><i class="fa-solid fa-grip" style="color:#facc15"></i> Manage</p>

            <div class="pd-programs-grid" style="grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));">

                <a href="parent_activity.php<?= $child_qs ?>" class="pd-card" style="text-decoration:none; color:inherit; display:block; cursor:pointer;">
                    <div class="pd-card-header" style="color:#38bdf8; border-color:rgba(56,189,248,.15);">
                        <i class="fa-solid fa-chart-line"></i> Activity &amp; Progress
                    </div>
                    <p style="color:#94a3b8; font-size:.85rem; margin:0;">See what your child watched, learned, and earned.</p>
                </a>

                <a href="parent_programs.php<?= $child_qs ?>" class="pd-card" style="text-decoration:none; color:inherit; display:block; cursor:pointer;">
                    <div class="pd-card-header" style="color:#7c3aed; border-color:rgba(124,58,237,.15);">
                        <i class="fa-solid fa-graduation-cap"></i> Learning Programs
                    </div>
                    <p style="color:#94a3b8; font-size:.85rem; margin:0;">Enroll your child into paid learning programs.</p>
                </a>

                <a href="parent_payments.php<?= $child_qs ?>" class="pd-card" style="text-decoration:none; color:inherit; display:block; cursor:pointer;">
                    <div class="pd-card-header" style="color:#facc15; border-color:rgba(250,204,21,.15);">
                        <i class="fa-solid fa-receipt"></i> Payment History
                    </div>
                    <p style="color:#94a3b8; font-size:.85rem; margin:0;">Track every payment you've submitted for review.</p>
                </a>

                <a href="parent_orders.php<?= $child_qs ?>" class="pd-card" style="text-decoration:none; color:inherit; display:block; cursor:pointer;">
                    <div class="pd-card-header" style="color:#34d399; border-color:rgba(52,211,153,.15);">
                        <i class="fa-solid fa-box-open"></i> Orders
                    </div>
                    <p style="color:#94a3b8; font-size:.85rem; margin:0;">Store orders — yours and your child's.</p>
                </a>

            </div>
        <?php endif; ?>

    <?php endif; ?>

</div><!-- /.main-content -->

<script>
document.querySelectorAll('.pd-alert').forEach(el => {
    setTimeout(() => {
        el.style.transition = 'opacity .4s ease';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    }, 5000);
});

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

// ── Create Child Account modal ──────────────────────────────────
(function(){
    const overlay      = document.getElementById('ccaOverlay');
    const fullnameEl    = document.getElementById('ccaFullname');
    const usernameEl    = document.getElementById('ccaUsername');
    const statusEl      = document.getElementById('ccaUsernameStatus');
    const submitBtn     = document.getElementById('ccaSubmitBtn');
    const passwordEl    = document.getElementById('ccaPassword');
    const passToggle    = document.getElementById('ccaPasswordToggle');
    const passIcon      = document.getElementById('ccaPasswordIcon');

    window.ccaOpen = function () { overlay.style.display = 'flex'; fullnameEl.focus(); };
    window.ccaClose = function () { overlay.style.display = 'none'; };

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) window.ccaClose();
    });

    passToggle.addEventListener('click', function () {
        const isHidden = passwordEl.type === 'password';
        passwordEl.type = isHidden ? 'text' : 'password';
        passIcon.classList.toggle('fa-eye', !isHidden);
        passIcon.classList.toggle('fa-eye-slash', isHidden);
    });

    // Auto-suggest a username from the name field, but stop touching it
    // the moment the parent types into the username box themselves.
    let usernameEditedByUser = usernameEl.value.trim() !== '';
    usernameEl.addEventListener('input', function () { usernameEditedByUser = true; });
    fullnameEl.addEventListener('input', function () {
        if (usernameEditedByUser) return;
        const slug = fullnameEl.value.toLowerCase().replace(/[^a-z0-9]+/g, '').slice(0, 20);
        usernameEl.value = slug;
        checkUsername(slug);
    });

    let usernameIsAvailable = false;
    let debounceTimer = null;
    usernameEl.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const val = usernameEl.value.trim();
        debounceTimer = setTimeout(() => checkUsername(val), 400);
    });

    function setSubmitEnabled(enabled) {
        submitBtn.disabled = !enabled;
        submitBtn.style.opacity = enabled ? '1' : '.55';
        submitBtn.style.cursor = enabled ? 'pointer' : 'not-allowed';
    }

    function checkUsername(val) {
        usernameIsAvailable = false;
        setSubmitEnabled(false);
        if (!val) { statusEl.textContent = ''; return; }
        statusEl.textContent = 'Checking…';
        statusEl.style.color = '#64748b';

        fetch('check_child_username.php?username=' + encodeURIComponent(val))
            .then(r => r.json())
            .then(res => {
                if (usernameEl.value.trim() !== val) return; // stale response, a newer keystroke already fired
                if (!res.success) {
                    statusEl.textContent = res.error || 'Could not check username right now.';
                    statusEl.style.color = '#f87171';
                    return;
                }
                if (res.available) {
                    statusEl.textContent = '✓ Available';
                    statusEl.style.color = '#34d399';
                    usernameIsAvailable = true;
                    setSubmitEnabled(true);
                } else {
                    statusEl.textContent = res.reason || 'That username is taken.';
                    statusEl.style.color = '#f87171';
                }
            })
            .catch(() => {
                statusEl.textContent = 'Could not check username right now.';
                statusEl.style.color = '#f87171';
            });
    }

    // If the username field was pre-filled (e.g. re-showing the form after a
    // server-side validation error), run the check once immediately.
    if (usernameEl.value.trim() !== '') checkUsername(usernameEl.value.trim());
    setSubmitEnabled(false);

    document.getElementById('ccaForm').addEventListener('submit', function (e) {
        if (!usernameIsAvailable) {
            e.preventDefault();
            checkUsername(usernameEl.value.trim());
        }
    });

    // Re-open the modal automatically if the server rejected the last
    // submission (e.g. someone grabbed the username in the meantime),
    // so the parent sees the error in context instead of just the top banner.
    <?php if ($error_msg && ($_SERVER['REQUEST_METHOD'] === 'POST') && (($_POST['action'] ?? '') === 'create_child_account')): ?>
    window.ccaOpen();
    <?php endif; ?>

    // Opened via the navbar's "Create Child Account" link (works from any
    // page on the site, since the actual form only lives here).
    <?php if (($_GET['open'] ?? '') === 'create_child'): ?>
    window.ccaOpen();
    <?php endif; ?>
})();
</script>
</body>
</html>