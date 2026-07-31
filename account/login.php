<?php
session_start();
require_once __DIR__ . '/../includes/db.php';

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (!empty($email) && !empty($password)) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user && password_verify($password, $user['password'])) {

                if (($user['account_status'] ?? 'active') === 'deactivated') {
                    $error_message = "This account has been deactivated. Please contact support if you'd like it reactivated.";
                } else {

                // Initialize session variables
                $_SESSION['id'] = $user['id'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['username'] = strstr($user['email'], '@', true); 
                $_SESSION['profile_pic'] = !empty($user['profile_pic']) ? $user['profile_pic'] : 'images/gg.png';

                // ROLE-BASED REDIRECTION LOGIC
                $role = strtolower(trim($user['role']));
                $redirect_target = $_POST['redirect'] ?? ($_GET['redirect'] ?? '');

                if ($redirect_target === 'store') {
                    // Came from the store's checkout button — send them back to finish their order.
                    header("Location: ../store/store.php");
                } elseif ($role === 'admin') {
    header("Location: ../admin/admin_dashboard.php");
} elseif ($role === 'teacher') {
    header("Location: ../teacher/teacher_dashboard.php");
} elseif ($role === 'parent') {
    header("Location: ../parent/parent_dashboard.php");
} else {
    header("Location: ../index.php"); // catches 'student', 'child', or anything else
}
                exit();
                }
            } else {
                $error_message = "Invalid Email or Password.";
            }
        } catch (Exception $e) {
            $error_message = "Database System Fault: " . $e->getMessage();
        }
    } else {
        $error_message = "Please enter your email and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

    <meta charset="UTF-8">
    <title>Sign In - SafeKids Space</title>
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
    <h2 class="auth-title">Welcome Back</h2>
    <?php if(!empty($error_message)): ?>
        <div class="alert alert-danger text-center" style="border-radius: 10px;"><?= htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    <form action="login.php" method="POST">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_GET['redirect'] ?? '') ?>">
        <div class="mb-3">
            <label class="form-label">Email Address</label>
            <input type="email" name="email" class="form-control" placeholder="enter your email" required>
        </div>
        <div class="mb-4">
            <label class="form-label">Password</label>
            <div class="sks-password-wrap">
                <input type="password" name="password" id="sksLoginPassword" class="form-control" placeholder="••••••••" required>
                <button type="button" class="sks-password-toggle" id="sksLoginPasswordToggle" aria-label="Show password">
                    <i class="fa-solid fa-eye" id="sksLoginPasswordIcon"></i>
                </button>
            </div>
        </div>
        <button type="submit" class="btn-auth">Sign In</button>
    </form>
    <div class="auth-switch">
        Don't have an account yet? <a href="register.php">Sign Up</a>
    </div>
</div>
<script>
document.getElementById('sksLoginPasswordToggle').addEventListener('click', function () {
    const input = document.getElementById('sksLoginPassword');
    const icon = document.getElementById('sksLoginPasswordIcon');
    const isHidden = input.type === 'password';
    input.type = isHidden ? 'text' : 'password';
    icon.classList.toggle('fa-eye', !isHidden);
    icon.classList.toggle('fa-eye-slash', isHidden);
});
</script>
</body>
</html>