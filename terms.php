<?php
$base = ''; // used by includes/navbar.php, footer.php etc for depth-correct links
?>

<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/includes/favicon.php'; ?>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Privacy Policy | Safe Kids Space</title>

<link rel="stylesheet" href="assets/layout.css">

<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet"
href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>
<?php include __DIR__ . '/includes/navbar.php'; ?>
<div class="main-content policy">
<div class="policy-page">

    <div class="policy-hero">

        <div class="policy-icon">
            <i class="fas fa-user-shield"></i>
        </div>

        <h1 class="policy-title">
            Safe Kids Space Safety Log
        </h1>

        <p class="policy-subtitle">
            At Safe Kids Space, protecting the privacy and safety of our young
            explorers is one of our highest priorities. This policy explains
            how information is collected, used, and protected while children
            enjoy our educational games, quizzes, and learning activities.
        </p>

    </div>

    <div class="policy-card">
        <h2>
            <i class="fas fa-database"></i>
          <span>Information We Collect</span>
        </h2>

        <p>
            Safe Kids Space may collect limited information needed to provide
            educational services and improve learning experiences. This may
            include quiz scores, achievement badges, completed activities,
            progress records, and basic technical information such as browser
            type, device type, and screen size. We only collect information
            necessary to operate and improve our platform.
        </p>
    </div>

    <div class="policy-card">
        <h2>
            <i class="fas fa-chart-line"></i>
          <span>How We Use Information</span>
        </h2>

        <p>
            Information helps us personalize learning experiences, save game
            progress, award badges, improve educational content, identify
            technical issues, and maintain a secure environment. Our goal is
            to make learning engaging, rewarding, and enjoyable for every
            child using Safe Kids Space.
        </p>
    </div>

    <div class="policy-card">
        <h2>
            <i class="fas fa-child-reaching"></i>
           <span>Children's Privacy</span> 
        </h2>

        <p>
            Safe Kids Space is designed specifically for children and families.
            We strive to minimize data collection and avoid gathering sensitive
            personal information. Parents and guardians are encouraged to
            supervise children's online activities and participate in their
            educational journey whenever possible.
        </p>
    </div>

    <div class="policy-card" id="cookies">
        <h2>
            <i class="fas fa-cookie-bite"></i>
          <span>Cookies & Local Storage</span> 
        </h2>

        <p>
            We may use cookies or browser storage technologies to remember user
            preferences, save learning progress, keep users signed in, and
            improve overall website functionality. These technologies help
            provide a smoother and more personalized learning experience.
        </p>
    </div>

    <div class="policy-card">
        <h2>
            <i class="fas fa-lock"></i>
           <span>Data Security</span>
        </h2>

        <p>
            We take reasonable measures to protect information against
            unauthorized access, misuse, disclosure, alteration, or loss.
            While no online system can guarantee complete security, Safe Kids
            Space continuously works to maintain a safe and secure educational
            environment.
        </p>
    </div>

    <div class="policy-card">
        <h2>
            <i class="fas fa-globe"></i>
          <span>Third-Party Services</span> 
        </h2>

        <p>
            We may use trusted third-party services for analytics, hosting,
            performance monitoring, and educational tools. These services help
            us improve the platform while maintaining appropriate privacy and
            security standards.
        </p>
    </div>

    <div class="policy-card">
        <h2>
            <i class="fas fa-sync-alt"></i>
         <span>Policy Updates</span> 
        </h2>

        <p>
            Safe Kids Space may update this Privacy Policy from time to time to
            reflect improvements, new features, or legal requirements. Any
            updates will be published on this page, and continued use of the
            platform indicates acceptance of the revised policy.
        </p>
    </div>

    <div class="policy-card">
        <h2>
            <i class="fas fa-envelope"></i>
           <span>Contact Us</span>
        </h2>

        <p>
            If you have questions about this Privacy Policy or how information
            is handled, please contact the Safe Kids Space team. We are always
            happy to help parents, teachers, and young learners.
        </p>
    </div>

    <div class="btn-wrap">
        <a href="index.php" class="home-btn">
            <i class="fas fa-home"></i>
            Return to Home Base
        </a>
    </div>


</div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>