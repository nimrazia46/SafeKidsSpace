<?php
$base = '../';

session_start();
require_once __DIR__ . '/../includes/db.php';

$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'admin' && $user_role !== 'administrator') {
    header("Location: ../index.php");
    exit();
}

$current_page = 'admin_career_applications.php';

// Update an application's status (shortlist / reject / hire)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    require_once __DIR__ . '/../includes/mailer.php';

    $app_id     = (int)($_POST['app_id'] ?? 0);
    $new_status = $_POST['new_status'] ?? 'pending';
    if ($app_id && in_array($new_status, ['pending', 'shortlisted', 'rejected', 'hired'])) {
        $app_stmt = $pdo->prepare("SELECT full_name, email, program_title FROM career_applications WHERE id = ?");
        $app_stmt->execute([$app_id]);
        $app_row = $app_stmt->fetch();

        $pdo->prepare("UPDATE career_applications SET status = ? WHERE id = ?")->execute([$new_status, $app_id]);

        // Notify the applicant by email — skip for 'pending' (that's the default
        // state, not an action worth emailing about).
        if ($app_row && in_array($new_status, ['shortlisted', 'rejected', 'hired'])) {
            $name     = $app_row['full_name'];
            $position = $app_row['program_title'] ?: 'the teaching position';

            switch ($new_status) {
                case 'shortlisted':
                    $subject = "You've Been Shortlisted - SafeKidsSpace";
                    $body = "Hi " . htmlspecialchars($name) . ",<br><br>"
                          . "Good news! You've been shortlisted for <strong>" . htmlspecialchars($position) . "</strong> at SafeKidsSpace.<br>"
                          . "Our hiring team will reach out shortly to discuss next steps (interview, demo class, etc.).<br><br>"
                          . "Thanks for your patience,<br>- SafeKidsSpace Hiring Team";
                    break;
                case 'rejected':
                    $subject = "Update on Your Application - SafeKidsSpace";
                    $body = "Hi " . htmlspecialchars($name) . ",<br><br>"
                          . "Thank you for applying for <strong>" . htmlspecialchars($position) . "</strong> at SafeKidsSpace and for taking the time to share your experience with us.<br>"
                          . "After careful review, we've decided to move forward with other candidates for this particular role. This isn't a reflection of your qualifications — we'd genuinely encourage you to apply again for future openings.<br><br>"
                          . "Wishing you the best,<br>- SafeKidsSpace Hiring Team";
                    break;
                case 'hired':
                default:
                    $subject = "Congratulations - Welcome to SafeKidsSpace!";
                    $body = "Hi " . htmlspecialchars($name) . ",<br><br>"
                          . "Congratulations! We're excited to offer you the role for <strong>" . htmlspecialchars($position) . "</strong> at SafeKidsSpace.<br>"
                          . "Our team will contact you shortly with onboarding details and next steps.<br><br>"
                          . "Welcome aboard,<br>- SafeKidsSpace Hiring Team";
                    break;
            }

            send_email($app_row['email'], $subject, $body);
        }
    }
    header("Location: admin_career_applications.php");
    exit();
}

$applications = $pdo->query("SELECT * FROM career_applications ORDER BY applied_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin - Teacher Applications</title>
<link rel="stylesheet" href="../assets/layout.css">
<link rel="stylesheet" href="../assets/admin.css">
<link rel="stylesheet" href="../assets/career.css">
</head>
<body>
<?php include __DIR__ . '/../includes/admin_navbar.php'; ?>
<div class="container-fluid px-4">
<main class="main-content" id="content">

<h1 class="aca-title">Teacher Applications (<?= count($applications) ?>)</h1>

<div class="aca-table-wrap">
<table class="aca-table">
<tr>
  <th>Applied</th><th>Name</th><th>Contact</th><th>Applied For</th><th>Education</th><th>Subjects</th><th>Experience</th><th>CV</th><th>Status</th>
</tr>
<?php foreach ($applications as $a): ?>
<tr>
  <td><?= date('d M Y', strtotime($a['applied_at'])) ?></td>
  <td><?= htmlspecialchars($a['full_name']) ?></td>
  <td><?= htmlspecialchars($a['email']) ?><br><?= htmlspecialchars($a['mobile_number']) ?></td>
  <td><?= htmlspecialchars($a['program_title'] ?: 'General') ?></td>
  <td><?= htmlspecialchars($a['education_level']) ?> - <?= htmlspecialchars($a['institution']) ?></td>
  <td><?= htmlspecialchars($a['subjects_taught'] ?? '') ?></td>
  <td><?= htmlspecialchars($a['experience_level'] ?? '') ?><br><small style="color:#94a3b8;"><?= htmlspecialchars(ucfirst(str_replace('-', ' ', $a['preferred_mode'] ?? ''))) ?></small></td>
  <td><a class="cv-link" href="../<?= htmlspecialchars($a['cv_path']) ?>" target="_blank">View CV</a></td>
  <td>
    <span class="aca-badge <?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span><br>
    <form method="POST" style="margin-top:6px;display:inline-flex;gap:6px;">
      <input type="hidden" name="app_id" value="<?= $a['id'] ?>">
      <select name="new_status" onchange="this.form.submit()">
        <option value="pending" <?= $a['status']=='pending'?'selected':'' ?>>Pending</option>
        <option value="shortlisted" <?= $a['status']=='shortlisted'?'selected':'' ?>>Shortlisted</option>
        <option value="rejected" <?= $a['status']=='rejected'?'selected':'' ?>>Rejected</option>
        <option value="hired" <?= $a['status']=='hired'?'selected':'' ?>>Hired</option>
      </select>
      <input type="hidden" name="update_status" value="1">
    </form>
  </td>
</tr>
<?php endforeach; ?>
</table>
</div>

</main>
</div>
</body>
</html>