<?php
// ============================================================
// search_suggestions.php
// Powers the navbar's live search dropdown.
//
// Two ways a query can match:
//  1) CONTAINS match — the typed text appears anywhere in a
//     title (not just at the start), e.g. "video" matches
//     "Learning Video Basics".
//  2) CATEGORY keyword match — if the typed text looks like a
//     content-type word (e.g. "video", "book", "quiz", "store"),
//     that whole category's top items are shown even if no
//     individual title contains the word, e.g. typing "video"
//     surfaces the Videos section generally.
// Results are combined across content types and sorted A→Z.
// ============================================================

header('Content-Type: application/json');
include __DIR__ . '/../includes/db.php';

$q = trim($_GET['q'] ?? '');
$results = [];

if (strlen($q) >= 2) {
    $contains = '%' . $q . '%';
    $qlower   = strtolower($q);

    // Keywords that mean "show me this whole category"
    $category_keywords = [
        'video'   => ['video', 'videos'],
        'program' => ['program', 'programs', 'course', 'courses', 'learning', 'class', 'classes', 'quiz', 'quizzes'],
        'book'    => ['book', 'books', 'library', 'read', 'reading'],
        'product' => ['product', 'products', 'store', 'shop', 'shopping', 'toy', 'toys'],
    ];

    $category_matched = [];
    foreach ($category_keywords as $type => $keywords) {
        foreach ($keywords as $kw) {
            // Match if the typed text is the start of the keyword ("vid" -> "video")
            // or the keyword appears within the typed text.
            if (strpos($kw, $qlower) === 0 || strpos($qlower, $kw) !== false) {
                $category_matched[$type] = true;
                break;
            }
        }
    }

    // ── Videos ──────────────────────────────────────────────
    if (!empty($category_matched['video'])) {
        $stmt = $pdo->query("SELECT id, title FROM videos ORDER BY title ASC LIMIT 5");
    } else {
        $stmt = $pdo->prepare("SELECT id, title FROM videos WHERE title LIKE :q ORDER BY title ASC LIMIT 5");
        $stmt->execute([':q' => $contains]);
    }
    foreach ($stmt->fetchAll() as $row) {
        $results[] = ['title' => $row['title'], 'link' => 'videos.php?id=' . $row['id'], 'icon' => 'fa-solid fa-video'];
    }

    // ── Learning Programs ───────────────────────────────────
    if (!empty($category_matched['program'])) {
        $stmt = $pdo->query("SELECT title FROM programs WHERE status = 'active' ORDER BY title ASC LIMIT 5");
    } else {
        $stmt = $pdo->prepare("SELECT title FROM programs WHERE status = 'active' AND (title LIKE :q1 OR subjects LIKE :q2) ORDER BY title ASC LIMIT 5");
        $stmt->execute([':q1' => $contains, ':q2' => $contains]);
    }
    foreach ($stmt->fetchAll() as $row) {
        $results[] = ['title' => $row['title'], 'link' => 'learning.php', 'icon' => 'fa-solid fa-brain'];
    }

    // ── Library Books ───────────────────────────────────────
    if (!empty($category_matched['book'])) {
        $stmt = $pdo->query("SELECT title, pdf_url FROM books ORDER BY title ASC LIMIT 5");
    } else {
        $stmt = $pdo->prepare("SELECT title, pdf_url FROM books WHERE title LIKE :q1 OR author LIKE :q2 ORDER BY title ASC LIMIT 5");
        $stmt->execute([':q1' => $contains, ':q2' => $contains]);
    }
    foreach ($stmt->fetchAll() as $row) {
        $results[] = ['title' => $row['title'], 'link' => 'pdf/' . rawurlencode($row['pdf_url']), 'icon' => 'fa-solid fa-book-open'];
    }

    // ── Store Products ──────────────────────────────────────
    if (!empty($category_matched['product'])) {
        $stmt = $pdo->query("SELECT title FROM store_products WHERE is_active = 1 ORDER BY title ASC LIMIT 5");
    } else {
        $stmt = $pdo->prepare("SELECT title FROM store_products WHERE is_active = 1 AND title LIKE :q ORDER BY title ASC LIMIT 5");
        $stmt->execute([':q' => $contains]);
    }
    foreach ($stmt->fetchAll() as $row) {
        $results[] = ['title' => $row['title'], 'link' => 'store/store.php', 'icon' => 'fa-solid fa-store'];
    }

    // Combine and sort every matched item alphabetically (A→Z) by title
    usort($results, function ($a, $b) {
        return strcasecmp($a['title'], $b['title']);
    });

    $results = array_slice($results, 0, 8);
}

echo json_encode($results);