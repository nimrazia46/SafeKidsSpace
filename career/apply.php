<?php
$base = '../'; // this file lives in /career/, one level below site root
session_start();
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/mailer.php';

$current_page = 'career_apply.php';

$program_id    = isset($_GET['program_id']) ? (int)$_GET['program_id'] : (isset($_POST['program_id']) ? (int)$_POST['program_id'] : 0);
$program_title = null;

if ($program_id > 0) {
    $pstmt = $pdo->prepare("SELECT title FROM programs WHERE id = ?");
    $pstmt->execute([$program_id]);
    $program_title = $pstmt->fetchColumn() ?: null;
    if (!$program_title) $program_id = 0; // invalid id, fall back to general application
}

$success_msg = '';
$error_msg   = '';

// If the applicant is logged into their account, auto-fill name/email
// from their profile (still editable — they may want to apply with a
// different contact email than the one they signed up with).
$current_user_fullname = '';
$current_user_email    = '';
if (isset($_SESSION['id'])) {
    try {
        $u_stmt = $pdo->prepare("SELECT fullname, email FROM users WHERE id = ?");
        $u_stmt->execute([$_SESSION['id']]);
        $u_row = $u_stmt->fetch();
        $current_user_fullname = $u_row['fullname'] ?? '';
        $current_user_email    = $u_row['email'] ?? '';
    } catch (Exception $e) {
        // fail silently — form just won't be pre-filled
    }
}

// Pre-fill mobile number from their most recent career application, if
// any — career_applications has no user_id (guests can apply too), so
// we match on their account email instead. Still editable.
$current_user_last_mobile = '';
if ($current_user_email !== '') {
    try {
        $m_stmt = $pdo->prepare(
            "SELECT mobile_number FROM career_applications
             WHERE email = ? AND mobile_number IS NOT NULL AND mobile_number != ''
             ORDER BY id DESC LIMIT 1"
        );
        $m_stmt->execute([$current_user_email]);
        $current_user_last_mobile = $m_stmt->fetchColumn() ?: '';
    } catch (Exception $e) {
        // fail silently — field just won't be pre-filled
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_application'])) {

    $full_name        = trim($_POST['full_name'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $mobile_number    = trim($_POST['mobile_number'] ?? '');

    $education_level  = trim($_POST['education_level'] ?? '');
    $institution      = trim($_POST['institution'] ?? '');
    $subjects_taught  = trim($_POST['subjects_taught'] ?? '');
    $experience_level = trim($_POST['experience_level'] ?? '');
    $preferred_mode   = trim($_POST['preferred_mode'] ?? '');
    $why_teach        = trim($_POST['why_teach'] ?? '');

    // Honeypot: a hidden field real applicants never see or fill in.
    // If it comes back non-empty, it was a bot -> silently pretend success and skip real processing.
    $is_bot = !empty($_POST['sks_confirm_ref'] ?? '');

    $valid_experience_levels = ['fresh', '1-2', '3-5', '5+'];
    $valid_modes             = ['online', 'on-campus', 'both'];

    if ($is_bot) {
        $success_msg = "🎉 Application submitted! We'll review your CV and get in touch if it's a good fit.";
    } elseif ($full_name === '' || $email === '' || $mobile_number === '') {
        $error_msg = "Please fill in your name, email and mobile number.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_msg = "Please enter a valid email address.";
    } elseif (!preg_match('/^03\d{2}-?\d{7}$/', $mobile_number)) {
        $error_msg = "Please enter a valid mobile number in the format 03XX-XXXXXXX.";
    } elseif ($education_level === '' || $institution === '') {
        $error_msg = "Please tell us your highest education and institution.";
    } elseif ($subjects_taught === '') {
        $error_msg = "Please tell us which subjects or grade levels you'd like to teach.";
    } elseif (!in_array($experience_level, $valid_experience_levels, true)) {
        $error_msg = "Please select your teaching experience.";
    } elseif (!in_array($preferred_mode, $valid_modes, true)) {
        $error_msg = "Please select your preferred teaching mode.";
    } elseif (!isset($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
        $error_msg = "Please upload your CV (PDF, DOC or DOCX).";
    } else {
        $allowed_ext = ['pdf', 'doc', 'docx'];
        $ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $allowed_ext)) {
            $error_msg = "CV must be a PDF, DOC or DOCX file.";
        } elseif ($_FILES['cv']['size'] > 5 * 1024 * 1024) {
            $error_msg = "CV file is too large (max 5MB).";
        } else {
            // Duplicate guard: same email + same program within the last 2 days -> don't insert again
            $dup_stmt = $pdo->prepare(
                "SELECT id FROM career_applications
                 WHERE email = ? AND (program_id <=> ?) AND applied_at > (NOW() - INTERVAL 2 DAY)"
            );
            $dup_stmt->execute([$email, $program_id ?: null]);

            if ($dup_stmt->fetch()) {
                $error_msg = "You've already applied for this position recently. We'll be in touch soon — no need to resubmit.";
            } else {
                $upload_dir = __DIR__ . '/uploads/cv/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $safe_name = 'cv_' . preg_replace('/[^a-zA-Z0-9]/', '', $full_name) . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $dest      = $upload_dir . $safe_name;

                if (move_uploaded_file($_FILES['cv']['tmp_name'], $dest)) {
                    $rel_path = 'career/uploads/cv/' . $safe_name; // relative to SITE ROOT so admin panel can link to it directly

                    try {
                        $stmt = $pdo->prepare(
                            "INSERT INTO career_applications
                             (program_id, program_title, full_name, email, mobile_number,
                              education_level, institution, subjects_taught, experience_level, preferred_mode, why_teach, cv_path)
                             VALUES (?,?,?,?,?, ?,?,?,?,?,?,?)"
                        );
                        $stmt->execute([
                            $program_id ?: null, $program_title, $full_name, $email, $mobile_number,
                            $education_level, $institution, $subjects_taught, $experience_level, $preferred_mode, $why_teach, $rel_path
                        ]);

                        // Let the admin team know inside the site (bell icon on admin dashboard)
                        notify_admins(
                            $pdo,
                            "New teacher application",
                            "$full_name applied" . ($program_title ? " for \"$program_title\"" : " (general application)") . ".",
                            "admin/admin_career_applications.php",
                            "fa-solid fa-chalkboard-user"
                        );

                        // Email a copy to the hiring inbox, plus a confirmation to the
                        // applicant — via PHPMailer/real SMTP (see includes/mailer.php).
                        $hiring_body = "New application received:<br><br>"
                                 . "Name: " . htmlspecialchars($full_name) . "<br>"
                                 . "Email: " . htmlspecialchars($email) . "<br>"
                                 . "Mobile: " . htmlspecialchars($mobile_number) . "<br>"
                                 . "Applying for: " . htmlspecialchars($program_title ?: 'General Application') . "<br>"
                                 . "Education: " . htmlspecialchars($education_level) . ", " . htmlspecialchars($institution) . "<br>"
                                 . "Subjects/Grade level: " . htmlspecialchars($subjects_taught) . "<br>"
                                 . "Experience: " . htmlspecialchars($experience_level) . "<br>"
                                 . "Preferred mode: " . htmlspecialchars($preferred_mode) . "<br><br>"
                                 . "View in admin panel: admin/admin_career_applications.php";
                        send_email(
                            "safekidsspace@gmail.com",
                            "New Teacher Application" . ($program_title ? " - $program_title" : ""),
                            $hiring_body,
                            $email,   // reply-to the applicant directly
                            $dest,    // attach the CV file itself
                            $safe_name
                        );

                        // Confirmation email to the applicant themselves
                        $applicant_body = "Hi " . htmlspecialchars($full_name) . ",<br><br>"
                                 . "Thanks for applying" . ($program_title ? " for \"" . htmlspecialchars($program_title) . "\"" : "") . " at SafeKidsSpace.<br>"
                                 . "Our team will review your CV and reach out if it's a good fit.<br><br>"
                                 . "- SafeKidsSpace Hiring Team";
                        send_email($email, "We've received your application - SafeKidsSpace", $applicant_body);

                        $success_msg = "🎉 Application submitted! We'll review your CV and get in touch if it's a good fit.";
                    } catch (PDOException $e) {
                        $error_msg = "Database error: " . htmlspecialchars($e->getMessage());
                    }
                } else {
                    $error_msg = "Something went wrong uploading your CV. Please try again.";
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Apply - SafeKidsSpace Careers</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&family=Orbitron:wght@700;900&family=Space+Grotesk:wght@600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="../assets/layout.css">
<link rel="stylesheet" href="../assets/career.css">
</head>
<body>

<?php include __DIR__ . '/../includes/navbar.php'; ?>
<div class="global-starfield"></div>

<div class="container-fluid px-4">
  <main class="main-content" id="content">
    <div class="apply-wrap">
      <a href="../careers.php" class="apply-close-btn" title="Back to Careers"><i class="fa-solid fa-xmark"></i></a>
      <h1>Teacher Application</h1>
      <p class="apply-sub">
        Applying for: <strong><?= htmlspecialchars($program_title ?: 'General Teacher Application') ?></strong>
      </p>

      <?php if ($success_msg): ?><div class="apply-alert ok"><?= $success_msg ?></div><?php endif; ?>
      <?php if ($error_msg): ?><div class="apply-alert err"><?= htmlspecialchars($error_msg) ?></div><?php endif; ?>

      <?php if (!$success_msg): ?>
      <form method="POST" enctype="multipart/form-data" novalidate id="applyForm">
        <input type="hidden" name="program_id" value="<?= (int)$program_id ?>">
        <input type="text" name="sks_confirm_ref" value="" style="position:absolute;left:-9999px;top:-9999px;" tabindex="-1" autocomplete="off" aria-hidden="true">

        <div class="apply-section-title">Contact Details</div>
        <div class="apply-grid">
          <div class="apply-field"><label>Your full name</label><input type="text" name="full_name" placeholder="Enter your full name" value="<?= htmlspecialchars($current_user_fullname) ?>" required data-error="Please enter your full name."></div>
          <div class="apply-field"><label>Email address</label><input type="email" name="email" placeholder="What's your email" value="<?= htmlspecialchars($current_user_email) ?>" required data-error="Please enter a valid email address."></div>
          <div class="apply-field"><label>Mobile number</label><input type="text" name="mobile_number" placeholder="03XX-XXXXXXX" pattern="03\d{2}-?\d{7}" maxlength="12" inputmode="numeric" value="<?= htmlspecialchars($current_user_last_mobile) ?>" required data-error="Please enter a valid mobile number in the format 03XX-XXXXXXX."></div>
        </div>

        <div class="apply-section-title">Teaching Background</div>
        <div class="apply-grid">
          <div class="apply-field"><label>Highest education</label><input type="text" name="education_level" placeholder="e.g. Bachelors in Education" required data-error="Please enter your highest education."></div>
          <div class="apply-field"><label>Institution</label><input type="text" name="institution" placeholder="Your university" required data-error="Please enter your institution."></div>
          <div class="apply-field">
            <label>Teaching experience</label>
            <select name="experience_level" required data-error="Please select your teaching experience.">
              <option value="">-- Select --</option>
              <option value="fresh">Fresh / No experience yet</option>
              <option value="1-2">1–2 years</option>
              <option value="3-5">3–5 years</option>
              <option value="5+">5+ years</option>
            </select>
          </div>
          <div class="apply-field">
            <label>Preferred teaching mode</label>
            <select name="preferred_mode" required data-error="Please select your preferred teaching mode.">
              <option value="">-- Select --</option>
              <option value="online">Online</option>
              <option value="on-campus">On-campus</option>
              <option value="both">Either / Both</option>
            </select>
          </div>
        </div>
        <div class="apply-field" style="margin-top:18px;">
          <label>Subjects / grade levels you'd like to teach</label>
          <input type="text" name="subjects_taught" placeholder="e.g. Math and Science, grades 3-6" required data-error="Please tell us which subjects or grade levels you'd like to teach.">
        </div>

        <div class="apply-section-title">Anything else? <span style="color:#64748b;font-weight:400;font-size:12px;">(optional)</span></div>
        <div class="apply-field">
          <label>Why would you like to teach with SafeKidsSpace?</label>
          <textarea name="why_teach" rows="3" placeholder="A few lines is enough" style="width:100%;padding:10px 12px;border-radius:10px;border:1px solid rgba(255,255,255,.15);background:rgba(2,6,23,.6);color:#fff;font-size:14px;font-family:inherit;resize:vertical;"></textarea>
        </div>

        <div class="apply-section-title">Upload CV</div>
        <div class="apply-upload">
          <strong>Drop your file here or</strong>
          <input type="file" name="cv" accept=".pdf,.doc,.docx" required data-error="Please upload your CV (PDF, DOC or DOCX).">
          <div class="apply-hint">Supported formats: PDF, DOC, DOCX (max 5MB)</div>
        </div>

        <button type="submit" name="submit_application" class="apply-submit-btn">Submit <i class="fa-solid fa-arrow-right"></i></button>
      </form>

      <script>
      (function () {
        const form = document.getElementById('applyForm');
        if (!form) return;

        // Auto-insert the dash after the first 4 digits as the person types
        // their mobile number, e.g. typing "0301..." becomes "0301-..." automatically.
        const mobileInput = form.querySelector('input[name="mobile_number"]');
        if (mobileInput) {
          mobileInput.addEventListener('input', function () {
            let digits = this.value.replace(/[^0-9]/g, '').slice(0, 11);
            this.value = digits.length > 4 ? digits.slice(0, 4) + '-' + digits.slice(4) : digits;
            validateField(this);
          });
        }

        // Build/refresh a styled red error message under a single field,
        // instead of the browser's default grey validation bubble.
        function validateField(field) {
          const wrap = field.closest('.apply-field');
          if (!wrap) return true;

          let errorEl = wrap.querySelector('.field-error');
          if (!errorEl) {
            errorEl = document.createElement('span');
            errorEl.className = 'field-error';
            field.insertAdjacentElement('afterend', errorEl);
          }

          if (field.checkValidity()) {
            wrap.classList.remove('has-error');
            errorEl.textContent = '';
            return true;
          } else {
            wrap.classList.add('has-error');
            errorEl.textContent = field.getAttribute('data-error') || field.validationMessage;
            return false;
          }
        }

        // Validate every required/patterned field as the person moves on (on blur/change),
        // so mistakes are flagged immediately rather than only at the end.
        form.querySelectorAll('input[required], select[required], input[pattern]').forEach(function (field) {
          field.addEventListener('blur', function () { validateField(field); });
          field.addEventListener('change', function () { validateField(field); });
        });

        // Final check on submit: validate everything, block submission if anything
        // is invalid, and scroll/focus the first problem field.
        form.addEventListener('submit', function (e) {
          let firstInvalid = null;
          form.querySelectorAll('input[required], select[required], input[pattern]').forEach(function (field) {
            const ok = validateField(field);
            if (!ok && !firstInvalid) firstInvalid = field;
          });
          if (firstInvalid) {
            e.preventDefault();
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            firstInvalid.focus();
          }
        });
      })();
      </script>
      <?php endif; ?>
    </div>
  </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>