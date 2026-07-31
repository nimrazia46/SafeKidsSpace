<?php
$base = '../'; // used by includes/navbar.php, footer.php etc for depth-correct links

session_start();
include __DIR__ . '/../includes/db.php';

$q = trim($_GET['q'] ?? '');
$results = [
    'videos' => [],
    'programs' => [],
    'books' => [],
    'products' => [],
];
$total = 0;

if ($q !== '') {
    $like = '%' . $q . '%';

    $stmt = $pdo->prepare("SELECT id, title, thumbnail_url, category FROM videos WHERE title LIKE :q1 ORDER BY title ASC LIMIT 8");
    $stmt->execute([':q1' => $like]);
    $results['videos'] = $stmt->fetchAll();

    // NOTE: learning.php actually runs on the `programs` table (not `courses`,
    // which isn't queried anywhere in the app), so we search programs instead.
    $stmt = $pdo->prepare("SELECT id, title, age_range, subjects, icon FROM programs WHERE status = 'active' AND (title LIKE :q1 OR subjects LIKE :q2) ORDER BY title ASC LIMIT 8");
    $stmt->execute([':q1' => $like, ':q2' => $like]);
    $results['programs'] = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT id, title, author, img_url, pdf_url FROM books WHERE title LIKE :q1 OR author LIKE :q2 ORDER BY title ASC LIMIT 8");
    $stmt->execute([':q1' => $like, ':q2' => $like]);
    $results['books'] = $stmt->fetchAll();

    $stmt = $pdo->prepare("SELECT id, title, description, image_path, price FROM store_products WHERE (title LIKE :q1 OR description LIKE :q2) AND is_active = 1 ORDER BY title ASC LIMIT 8");
    $stmt->execute([':q1' => $like, ':q2' => $like]);
    $results['products'] = $stmt->fetchAll();

    $total = count($results['videos']) + count($results['programs']) + count($results['books']) + count($results['products']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php include __DIR__ . '/../includes/favicon.php'; ?>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Search Results | SafeKidsSpace</title>
<link rel="stylesheet" href="../assets/layout.css">
<link href="https://fonts.googleapis.com/css2?family=Baloo+2:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="../assets/layout.css">
</head>
<body>
<?php include __DIR__ . '/../includes/navbar.php'; ?>

<div class="main-content">
    <div class="sr-wrapper">
        <h1 class="sr-heading"><i class="fa-solid fa-magnifying-glass"></i> Search Results</h1>

        <?php if ($q === ''): ?>
            <p class="sr-subheading">Type something in the search bar above to explore videos, lessons, books, and the store.</p>
        <?php else: ?>
            <p class="sr-subheading"><?= $total; ?> result<?= $total !== 1 ? 's' : ''; ?> found for <span>"<?= htmlspecialchars($q); ?>"</span></p>
        <?php endif; ?>

        <?php if ($q !== '' && $total === 0): ?>
            <div class="sr-empty">
                <i class="fa-solid fa-satellite-dish"></i>
                <p>No results found across the galaxy for "<?= htmlspecialchars($q); ?>". Try a different word!</p>
            </div>
        <?php endif; ?>

        <?php if (!empty($results['videos'])): ?>
        <div class="sr-section">
            <h2 class="sr-section-title"><i class="fa-solid fa-video"></i> Videos</h2>
            <div class="sr-grid">
                <?php foreach ($results['videos'] as $v): ?>
                <a href="videos.php?id=<?= $v['id']; ?>" class="sr-card">
                    <img src="<?= htmlspecialchars($v['thumbnail_url'] ?: '../images/banner.png'); ?>" alt="">
                    <div class="sr-card-body">
                        <div class="sr-card-title"><?= htmlspecialchars($v['title']); ?></div>
                        <div class="sr-card-meta"><?= htmlspecialchars($v['category'] ?? 'Video'); ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($results['programs'])): ?>
        <div class="sr-section">
            <h2 class="sr-section-title"><i class="fa-solid fa-brain"></i> Learning Programs</h2>
            <div class="sr-grid">
                <?php foreach ($results['programs'] as $p): ?>
                <a href="../learning.php" class="sr-card">
                    <div class="sr-card-body" style="text-align:center; padding:24px 14px;">
                        <i class="<?= htmlspecialchars($p['icon'] ?: 'fa-solid fa-graduation-cap'); ?>" style="font-size:1.8rem; color:#38bdf8; margin-bottom:10px; display:block;"></i>
                        <div class="sr-card-title"><?= htmlspecialchars($p['title']); ?></div>
                        <div class="sr-card-meta">Ages <?= htmlspecialchars($p['age_range']); ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($results['books'])): ?>
        <div class="sr-section">
            <h2 class="sr-section-title"><i class="fa-solid fa-book-open"></i> Library</h2>
            <div class="sr-grid">
                <?php foreach ($results['books'] as $b): ?>
                <a href="../pdf/<?= htmlspecialchars($b['pdf_url']); ?>" target="_blank" class="sr-card">
                    <img src="../images/<?= htmlspecialchars($b['img_url'] ?: 'banner.png'); ?>" alt="">
                    <div class="sr-card-body">
                        <div class="sr-card-title"><?= htmlspecialchars($b['title']); ?></div>
                        <div class="sr-card-meta"><?= htmlspecialchars($b['author'] ?? ''); ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if (!empty($results['products'])): ?>
        <div class="sr-section">
            <h2 class="sr-section-title"><i class="fa-solid fa-store"></i> Kids Store</h2>
            <div class="sr-grid">
                <?php foreach ($results['products'] as $p): ?>
                <a href="../store/store.php" class="sr-card">
                    <img src="<?= '../' . htmlspecialchars($p['image_path']); ?>" alt="">
                    <div class="sr-card-body">
                        <div class="sr-card-title"><?= htmlspecialchars($p['title']); ?></div>
                        <div class="sr-card-price">$<?= number_format($p['price'], 2); ?></div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>