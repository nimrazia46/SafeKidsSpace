<?php
/**
 * register.php — Parent sign-up with email OTP verification
 * ─────────────────────────────────────────────────────────────
 * Two-step flow, kept entirely in $_SESSION (no DB schema changes):
 *   Step 1 (action=register):   validate form, generate a 6-digit code,
 *                                 email it, stash the pending signup in
 *                                 $_SESSION['reg_pending'].
 *   Step 2 (action=verify_otp): check the code the parent typed back in.
 *                                 Only on a correct match does the users
 *                                 row actually get created.
 * A resend option (action=resend_otp) and a "use a different email"
 * escape hatch (action=restart) are included.
 * ─────────────────────────────────────────────────────────────
 */

session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mailer.php';

const REG_OTP_TTL_SECONDS  = 600; // 10 minutes
const REG_OTP_MAX_ATTEMPTS = 5;

$error_message   = '';
$success_message = '';

function reg_send_otp_email(string $to_email, string $fullname, string $otp): bool {
    $body = "Hi " . htmlspecialchars($fullname) . ",<br><br>"
          . "Your SafeKidsSpace verification code is:<br><br>"
          . "<div style='font-size:28px; font-weight:700; letter-spacing:4px;'>" . htmlspecialchars($otp) . "</div><br>"
          . "This code expires in 10 minutes. If you didn't request this, you can ignore this email.<br><br>"
          . "- SafeKidsSpace Team";
    return send_email($to_email, "Your SafeKidsSpace verification code", $body);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'register';

    // ── Step 1: submit name/email/password → send OTP ──────────────
    if ($action === 'register') {
        $fullname = trim($_POST['fullname'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($fullname) || empty($email) || empty($password)) {
            $error_message = "Please complete all fields to sign up.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Please enter a valid email address (e.g. name@example.com).";
        } else {
            try {
                $check = $pdo->prepare("SELECT id FROM users WHERE email = :e LIMIT 1");
                $check->execute(['e' => $email]);

                if ($check->rowCount() > 0) {
                    $error_message = "This email address is already registered.";
                } else {
                    $otp = strval(random_int(100000, 999999));

                    $_SESSION['reg_pending'] = [
                        'fullname'      => $fullname,
                        'email'         => $email,
                        'password_hash' => password_hash($password, PASSWORD_BCRYPT),
                        'otp'           => $otp,
                        'otp_expires'   => time() + REG_OTP_TTL_SECONDS,
                        'attempts'      => 0,
                    ];

                    if (!reg_send_otp_email($email, $fullname, $otp)) {
                        // TEMP DEBUG: shows the real SMTP error on screen so we can see
                        // exactly why the email failed. REMOVE the "DEBUG:" line before
                        // going live — it should never be shown to real users.
                        $error_message = "Couldn't send the verification email — please check the SMTP setup in includes/mailer.php, then try again."
                            . " DEBUG: " . htmlspecialchars($GLOBALS['LAST_MAIL_ERROR'] ?? 'unknown error');
                        unset($_SESSION['reg_pending']);
                    }
                }
            } catch (Exception $e) {
                $error_message = "Registration Fault: " . $e->getMessage();
            }
        }
    }

    // ── Step 2: submit the 6-digit code → actually create the account ──
    if ($action === 'verify_otp') {
        $pending = $_SESSION['reg_pending'] ?? null;
        $entered = trim($_POST['otp'] ?? '');

        if (!$pending) {
            $error_message = "Your session expired — please start again.";
        } elseif (time() > $pending['otp_expires']) {
            $error_message = "That code expired. Please request a new one.";
        } elseif ($pending['attempts'] >= REG_OTP_MAX_ATTEMPTS) {
            unset($_SESSION['reg_pending']);
            $error_message = "Too many incorrect attempts. Please start again.";
        } elseif ($entered === '' || $entered !== $pending['otp']) {
            $_SESSION['reg_pending']['attempts'] = $pending['attempts'] + 1;
            $left = REG_OTP_MAX_ATTEMPTS - $_SESSION['reg_pending']['attempts'];
            $error_message = "Incorrect code. " . $left . " attempt(s) left.";
        } else {
            try {
                $insert = $pdo->prepare("INSERT INTO users (fullname, email, password, role, profile_pic) VALUES (:fullname, :email, :password, 'parent', :pfp)");
                $insert->execute([
                    'fullname' => $pending['fullname'],
                    'email'    => $pending['email'],
                    'password' => $pending['password_hash'],
                    'pfp'      => 'images/gg.png',
                ]);

                notify_admins(
                    $pdo,
                    "New user registered",
                    htmlspecialchars($pending['fullname']) . " signed up as a parent.",
                    "admin/admin_users.php",
                    "fa-solid fa-user-plus"
                );

                unset($_SESSION['reg_pending']);
                $success_message = "Email verified — account created! Redirecting to login...";
                header("refresh:2; url=login.php");
            } catch (Exception $e) {
                $error_message = "Registration Fault: " . $e->getMessage();
            }
        }
    }

    // ── Resend the code ─────────────────────────────────────────────
    if ($action === 'resend_otp') {
        $pending = $_SESSION['reg_pending'] ?? null;
        if (!$pending) {
            $error_message = "Your session expired — please start again.";
        } else {
            $otp = strval(random_int(100000, 999999));
            $_SESSION['reg_pending']['otp']         = $otp;
            $_SESSION['reg_pending']['otp_expires']  = time() + REG_OTP_TTL_SECONDS;
            $_SESSION['reg_pending']['attempts']     = 0;

            if (reg_send_otp_email($pending['email'], $pending['fullname'], $otp)) {
                $success_message = "A new code was sent to " . htmlspecialchars($pending['email']) . ".";
            } else {
                $error_message = "Couldn't resend the email right now — please try again shortly.";
            }
        }
    }

    // ── Start over with a different email ───────────────────────────
    if ($action === 'restart') {
        unset($_SESSION['reg_pending']);
    }
}

$awaiting_otp = isset($_SESSION['reg_pending']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <title>Sign Up - SafeKids Space</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700&family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="../assets/layout.css">
</head>
<body class="sks-auth-page">
<a href="../index.php" class="sks-auth-brand" style="text-decoration:none;">
    <div class="sks-auth-brand-icon">
        <img src="../images/gg.png" alt="Logo">
    </div>
    <div class="sks-auth-brand-text">
        <h2>SafeKids<span>Space</span></h2>
    </div>
</a>
<div class="auth-card">

    <?php if(!empty($error_message)): ?>
        <div class="alert alert-danger text-center" style="border-radius: 10px;"><?= htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    <?php if(!empty($success_message)): ?>
        <div class="alert alert-success text-center" style="border-radius: 10px;"><?= htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <?php if (!$awaiting_otp): ?>

        <h2 class="auth-title">Create Account</h2>
        <form action="register.php" method="POST">
            <input type="hidden" name="action" value="register">
            <div class="mb-3">
                <label class="form-label">Full Name</label>
                <input type="text" name="fullname" class="form-control" placeholder="John Doe" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Email Address</label>
                <input type="email" name="email" class="form-control" placeholder="name@example.com" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Password</label>
                <div class="sks-password-wrap">
                    <input type="password" name="password" id="sksRegPassword" class="form-control" placeholder="••••••••" required>
                    <button type="button" class="sks-password-toggle" id="sksRegPasswordToggle" aria-label="Show password">
                        <i class="fa-solid fa-eye" id="sksRegPasswordIcon"></i>
                    </button>
                </div>
            </div>
            <p class="auth-hint" style="font-size:.8rem; color:#94a3b8; text-align:center; margin-bottom:0;">
                <i class="fa-solid fa-circle-info me-1"></i>
                This creates a <strong>Parent</strong> account. You can add your child's account from your dashboard after signing up.
            </p>
            <button type="submit" class="btn-auth">Sign Up</button>
        </form>
        <div class="auth-switch">
            Already have an account? <a href="login.php">Sign In</a>
        </div>

    <?php else: ?>

        <h2 class="auth-title">Verify Your Email</h2>
        <p style="font-size:.85rem; color:#94a3b8; text-align:center; margin-bottom:20px;">
            We sent a 6-digit code to <strong><?= htmlspecialchars($_SESSION['reg_pending']['email']) ?></strong>.
            Enter it below to finish creating your account.
        </p>
        <form action="register.php" method="POST">
            <input type="hidden" name="action" value="verify_otp">
            <div class="mb-3">
                <label class="form-label">Verification Code</label>
                <input type="text" name="otp" class="form-control" placeholder="123456" maxlength="6" pattern="\d{6}" inputmode="numeric" style="text-align:center; letter-spacing:6px; font-size:1.3rem;" required autofocus>
            </div>
            <button type="submit" class="btn-auth">Verify &amp; Create Account</button>
        </form>
        <div class="auth-switch" style="display:flex; justify-content:space-between; gap:10px;">
            <form action="register.php" method="POST" style="margin:0;">
                <input type="hidden" name="action" value="resend_otp">
                <button type="submit" style="background:none; border:none; color:#38bdf8; cursor:pointer; font-size:.85rem;">Resend code</button>
            </form>
            <form action="register.php" method="POST" style="margin:0;">
                <input type="hidden" name="action" value="restart">
                <button type="submit" style="background:none; border:none; color:#94a3b8; cursor:pointer; font-size:.85rem;">Use a different email</button>
            </form>
        </div>

    <?php endif; ?>

</div>
<script>
const sksRegPasswordToggle = document.getElementById('sksRegPasswordToggle');
if (sksRegPasswordToggle) {
    sksRegPasswordToggle.addEventListener('click', function () {
        const input = document.getElementById('sksRegPassword');
        const icon = document.getElementById('sksRegPasswordIcon');
        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        icon.classList.toggle('fa-eye', !isHidden);
        icon.classList.toggle('fa-eye-slash', isHidden);
    });
}
</script>
</body>
</html>