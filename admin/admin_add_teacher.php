<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/../includes/db.php';

$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'admin' && $user_role !== 'administrator') {
    header("Location: ../index.php");
    exit();
}

$page_error   = '';
$new_teacher_email    = '';
$new_teacher_password = '';

// ── Generate a random, readable-enough password ─────────────────
function generate_teacher_password(int $length = 10): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789!@#$%';
    $password = '';
    $max = strlen($chars) - 1;
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[random_int(0, $max)];
    }
    return $password;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_add_teacher'])) {
    $t_fullname = trim($_POST['fullname'] ?? '');
    $t_email    = trim($_POST['email'] ?? '');

    if ($t_fullname === '' || $t_email === '') {
        $page_error = "Please enter both name and email.";
    } elseif (!filter_var($t_email, FILTER_VALIDATE_EMAIL)) {
        $page_error = "Please enter a valid email address.";
    } else {
        try {
            $check = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
            $check->execute([$t_email]);
            if ($check->rowCount() > 0) {
                $page_error = "This email is already registered.";
            } else {
                $generated_password = generate_teacher_password();
                $hashed = password_hash($generated_password, PASSWORD_BCRYPT);

                $ins = $pdo->prepare(
                    "INSERT INTO users (fullname, email, password, role, profile_pic) VALUES (?, ?, ?, 'teacher', 'images/gg.png')"
                );
                $ins->execute([$t_fullname, $t_email, $hashed]);

                $new_teacher_email    = $t_email;
                $new_teacher_password = $generated_password;
            }
        } catch (PDOException $e) {
            $page_error = "Database Error: " . $e->getMessage();
        }
    }
}

// ── Reset an existing teacher's password (from the View popup) ─────
// Passwords are one-way hashed in the DB, so the OLD password can never
// be shown again — this generates a brand new one instead, the same way
// account creation does, and displays it once so the admin can share it.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_reset_password'])) {
    $teacher_id = (int)($_POST['teacher_id'] ?? 0);
    try {
        $t_stmt = $pdo->prepare("SELECT id, fullname, email FROM users WHERE id = ? AND LOWER(role) = 'teacher' LIMIT 1");
        $t_stmt->execute([$teacher_id]);
        $t_row = $t_stmt->fetch();

        if (!$t_row) {
            $page_error = "Teacher not found.";
        } else {
            $generated_password = generate_teacher_password();
            $hashed = password_hash($generated_password, PASSWORD_BCRYPT);

            $upd = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $upd->execute([$hashed, $teacher_id]);

            $new_teacher_email    = $t_row['email'];
            $new_teacher_password = $generated_password;
        }
    } catch (PDOException $e) {
        $page_error = "Database Error: " . $e->getMessage();
    }
}

// ── Fetch existing teachers + their assigned programs ──────────
try {
    $teachers = $pdo->query(
        "SELECT u.id, u.fullname, u.email, u.account_status, u.created_at,
                (SELECT GROUP_CONCAT(p.title SEPARATOR ', ')
                   FROM teacher_program_assignments tpa
                   JOIN programs p ON p.id = tpa.program_id
                  WHERE tpa.teacher_id = u.id) AS assigned_programs
         FROM users u
         WHERE LOWER(u.role) = 'teacher'
         ORDER BY u.created_at DESC"
    )->fetchAll();
} catch (PDOException $e) {
    $teachers = [];
}

$current_page = 'admin_add_teacher.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SafeKidsSpace — Add Teacher</title>
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

<div class="main-content ad-wrap">

    <div class="ad-hero">
        <div class="ad-hero-left">
            <div class="ad-hero-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
            <div>
                <h1 class="ad-hero-title">Add Teacher</h1>
                <p class="ad-hero-sub">Teacher accounts are created here only — public sign-up no longer offers this role</p>
            </div>
        </div>
        <div class="ad-hero-right">
            <a href="admin_dashboard.php" class="ad-back-btn"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
        </div>
    </div>

    <?php if ($page_error): ?>
        <div class="ad-flash ad-flash-error"><i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($page_error) ?></div>
    <?php endif; ?>

    <?php if ($new_teacher_password): ?>
        <div class="ad-flash ad-flash-success">
            <i class="fa-solid fa-circle-check"></i> Done — credentials are shown in the popup.
        </div>
    <?php endif; ?>

    <!-- ── Add Teacher Form ────────────────────────────────────── -->
    <p class="ad-section-title"><i class="fa-solid fa-user-plus"></i> New Teacher Account</p>
    <div class="ad-card">
        <form action="admin_add_teacher.php" method="POST">
            <input type="hidden" name="_add_teacher" value="1">
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:14px;">
                <div>
                    <label class="apm-label">Full Name</label>
                    <input type="text" name="fullname" class="apm-input" placeholder="e.g. Ayesha Khan" required>
                </div>
                <div>
                    <label class="apm-label">Email Address</label>
                    <input type="email" name="email" class="apm-input" placeholder="teacher@example.com" required>
                </div>
            </div>
            <p style="color:#64748b; font-size:.78rem; margin-top:10px;">
                <i class="fa-solid fa-circle-info"></i> A secure password will be generated automatically and shown once above.
            </p>
            <button type="submit" class="ad-live-toggle-btn ad-live-toggle-grant" style="margin-top:6px;">
                <i class="fa-solid fa-user-plus"></i> Create Teacher Account
            </button>
        </form>
    </div>

    <!-- ── Existing Teachers ───────────────────────────────────── -->
    <p class="ad-section-title" style="margin-top:36px;"><i class="fa-solid fa-users"></i> All Teachers</p>
    <div class="ad-card">
        <div style="overflow-x:auto;">
            <table class="ad-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Assigned Programs</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($teachers)): ?>
                        <tr><td colspan="6" style="text-align:center; color:#64748b; padding:20px;">No teacher accounts yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($teachers as $t): ?>
                            <tr>
                                <td><?= htmlspecialchars($t['fullname']) ?></td>
                                <td><?= htmlspecialchars($t['email']) ?></td>
                                <td><?= $t['assigned_programs'] ? htmlspecialchars($t['assigned_programs']) : '<span style="color:#64748b;">Not assigned yet</span>' ?></td>
                                <td>
                                    <span class="ad-permission-pill <?= $t['account_status'] === 'active' ? 'ad-permission-approved' : 'ad-permission-pending' ?>">
                                        <?= htmlspecialchars(ucfirst($t['account_status'])) ?>
                                    </span>
                                </td>
                                <td><?= date('M j, Y', strtotime($t['created_at'])) ?></td>
                                <td>
                                    <button type="button" class="ad-back-btn" style="padding:4px 12px;"
                                        onclick="openTeacherModal(<?= (int)$t['id'] ?>, '<?= htmlspecialchars(addslashes($t['fullname'])) ?>', '<?= htmlspecialchars(addslashes($t['email'])) ?>', '<?= htmlspecialchars(addslashes(ucfirst($t['account_status']))) ?>')">
                                        <i class="fa-solid fa-eye"></i> View
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    <p style="color:#64748b; font-size:.8rem; margin-top:10px;">
        To assign programs to a teacher, go to <a href="admin_manage_programs.php" style="color:#38bdf8;">Manage Programs</a>.
    </p>

</div>

<!-- ── Credentials Popup (shown after Create / Reset Password) ─── -->
<?php if ($new_teacher_password): ?>
<div id="credsModalOverlay" style="display:flex; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:1100; align-items:center; justify-content:center;">
    <div style="background:#0f172a; border:1px solid #1e293b; border-radius:14px; padding:24px; width:100%; max-width:420px;">
        <h5 style="color:#e2e8f0; margin-bottom:6px;"><i class="fa-solid fa-circle-check" style="color:#22c55e;"></i> Login Credentials</h5>
        <p style="color:#64748b; font-size:.8rem; margin-bottom:16px;">Copy and share these securely — the password won't be shown again after you close this.</p>

        <div style="display:flex; flex-direction:column; gap:10px; background:rgba(0,0,0,.2); padding:14px 16px; border-radius:10px; margin-bottom:16px;">
            <div style="color:#cbd5e1;"><strong>Email:</strong> <span id="cm_email"><?= htmlspecialchars($new_teacher_email) ?></span></div>
            <div style="color:#cbd5e1;"><strong>Password:</strong> <code id="cm_password" style="color:#facc15; font-size:1rem;"><?= htmlspecialchars($new_teacher_password) ?></code></div>
        </div>

        <button type="button" id="cm_copy_btn" class="ad-live-toggle-btn ad-live-toggle-grant" style="width:100%;">
            <i class="fa-solid fa-copy"></i> Copy Email + Password
        </button>
        <button type="button" onclick="document.getElementById('credsModalOverlay').remove()" class="ad-back-btn" style="width:100%; margin-top:10px; justify-content:center;">
            Close
        </button>
    </div>
</div>
<script>
document.getElementById('cm_copy_btn').addEventListener('click', function() {
    const email = document.getElementById('cm_email').textContent;
    const password = document.getElementById('cm_password').textContent;
    navigator.clipboard.writeText('Email: ' + email + '\nPassword: ' + password);
    this.innerHTML = '<i class="fa-solid fa-check"></i> Copied!';
});
</script>
<?php endif; ?>

<!-- ── Teacher Details / Reset Password Popup ─────────────────── -->
<div id="teacherModalOverlay" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,.6); z-index:1000; align-items:center; justify-content:center;">
    <div style="background:#0f172a; border:1px solid #1e293b; border-radius:14px; padding:24px; width:100%; max-width:420px;">
        <h5 style="color:#e2e8f0; margin-bottom:16px;"><i class="fa-solid fa-user-tie"></i> Teacher Details</h5>

        <div style="display:flex; flex-direction:column; gap:8px; color:#cbd5e1; font-size:.9rem; margin-bottom:18px;">
            <div><strong>Name:</strong> <span id="tm_name"></span></div>
            <div><strong>Email:</strong> <span id="tm_email"></span></div>
            <div><strong>Status:</strong> <span id="tm_status"></span></div>
        </div>

        <p style="color:#64748b; font-size:.78rem; margin-bottom:10px;">
            <i class="fa-solid fa-circle-info"></i> The original password can't be shown again (it's securely encrypted).
            Generate a new one below to share with the teacher instead.
        </p>

        <form method="POST" onsubmit="return confirm('This will invalidate their current password. Continue?');">
            <input type="hidden" name="_reset_password" value="1">
            <input type="hidden" name="teacher_id" id="tm_teacher_id" value="">
            <button type="submit" class="ad-live-toggle-btn ad-live-toggle-grant" style="width:100%;">
                <i class="fa-solid fa-key"></i> Generate New Password
            </button>
        </form>

        <button type="button" onclick="closeTeacherModal()" class="ad-back-btn" style="width:100%; margin-top:10px; justify-content:center;">
            Close
        </button>
    </div>
</div>

<script>
function openTeacherModal(id, name, email, status) {
    document.getElementById('tm_teacher_id').value = id;
    document.getElementById('tm_name').textContent = name;
    document.getElementById('tm_email').textContent = email;
    document.getElementById('tm_status').textContent = status;
    document.getElementById('teacherModalOverlay').style.display = 'flex';
}
function closeTeacherModal() {
    document.getElementById('teacherModalOverlay').style.display = 'none';
}
</script>

<script>
document.querySelectorAll('.ad-flash').forEach(el => {
    if (el.querySelector('code')) return; // don't auto-dismiss the credentials box
    setTimeout(() => {
        el.style.transition = 'opacity .4s ease';
        el.style.opacity = '0';
        setTimeout(() => el.remove(), 400);
    }, 6000);
});
</script>
</body>
</html>