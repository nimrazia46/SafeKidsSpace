<?php
$base = ''; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
require_once __DIR__ . '/includes/db.php';

$current_page = 'careers.php';

// Pull the live list of programs/courses so applicants can see exactly what
// they'd be teaching. Add "WHERE status = 'active'" filtering (already there)
// so paused/old programs never show up as open positions.
$programs = $pdo->query("SELECT * FROM programs WHERE status = 'active' ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/favicon.php'; ?>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Careers - Teach with SafeKidsSpace</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&family=Orbitron:wght@700;900&family=Space+Grotesk:wght@600;700&family=Syne:wght@700;800&display=swap" rel="stylesheet">

<link rel="stylesheet" href="assets/layout.css">
<link rel="stylesheet" href="assets/career.css">

</head>
<body>

<?php include __DIR__ . '/includes/navbar.php'; ?>

<div class="global-starfield"></div>

<div class="container-fluid px-4">
  <main class="main-content" id="content">

    <div class="career-banner" style="background-image: url('images/teaching.png');">
      <div class="career-banner-content">
        <span class="career-banner-tag"><i class="fa-solid fa-tower-broadcast"></i> We're Hiring</span>
        <h1>Teach on SafeKidsSpace</h1>
        <p>Join our galaxy of certified teachers. Browse the programs below, pick the one that fits your subject and experience, and apply in a few minutes — no account needed.</p>
      </div>
    </div>

    <div class="career-grid">
      <?php if ($programs && count($programs) > 0): ?>
        <?php foreach ($programs as $p):
          $subjects_list = array_filter(array_map('trim', explode(',', $p['subjects'])));
          $summary_subjects = implode(', ', $subjects_list);
        ?>
          <div class="career-card">
            <div class="career-card-top">
              <div class="career-card-icon"><i class="fa-solid <?= htmlspecialchars($p['icon'] ?: 'fa-graduation-cap') ?>"></i></div>
              <h3><?= htmlspecialchars($p['title']) ?> Teacher</h3>
            </div>

            <div class="cc-row">
              <b>Job Summary</b>
              To plan and deliver engaging <?= htmlspecialchars($p['title']) ?> lessons for kids aged <?= htmlspecialchars($p['age_range']) ?>, covering <?= htmlspecialchars($summary_subjects) ?>, in line with SafeKidsSpace's child-safe teaching standards.
            </div>

            <div class="cc-row">
              <b>Age Group</b>
              <?= htmlspecialchars($p['age_range']) ?>
            </div>

            <div class="cc-row">
              <b>Subjects Covered</b>
              <div class="cc-tags">
                <?php foreach ($subjects_list as $subj): ?>
                  <span class="cc-tag"><?= htmlspecialchars($subj) ?></span>
                <?php endforeach; ?>
              </div>
            </div>

            <div class="cc-row">
              <b>Minimum Education</b>
              Bachelors (relevant subject preferred)
            </div>

            <div class="cc-row">
              <b>Location</b>
              Remote / Online — Pan Pakistan
            </div>

            <a class="career-apply-btn" href="career/apply.php?program_id=<?= (int)$p['id'] ?>">
              <i class="fa-solid fa-paper-plane"></i> Apply Now
            </a>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="career-empty">No open teaching positions right now — please check back soon.</div>
      <?php endif; ?>

      <!-- General application card, for teachers who don't see their exact subject above -->
      <div class="career-card">
        <div class="career-card-top">
          <div class="career-card-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
          <h3>General Teacher Application</h3>
        </div>
        <div class="cc-row">
          <b>Job Summary</b>
          Don't see your subject listed? Submit a general application and our team will match you to the right program.
        </div>
        <div class="cc-row"><b>Minimum Education</b> Bachelors</div>
        <div class="cc-row"><b>Location</b> Remote / Online — Pan Pakistan</div>
        <a class="career-apply-btn" href="career/apply.php">
          <i class="fa-solid fa-paper-plane"></i> Apply Now
        </a>
      </div>
    </div>

  </main>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>