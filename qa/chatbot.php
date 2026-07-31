<?php
/**
 * SafeKidsSpace - CosmoBot chatbot engine
 * Matches a user's free-text message against the site-wide FAQ knowledge
 * base (faqs + faq_categories) using weighted keyword scoring, so it can
 * answer questions about ANY section of the website (account, learning,
 * store, games, library, videos, live classes, programs, parent
 * dashboard, careers, safety, achievements ... anything in the faqs table).
 */

header('Content-Type: application/json');

require __DIR__ . '/../includes/db.php'; // provides $pdo

$rawMessage = trim($_POST['message'] ?? '');

if ($rawMessage === '') {
    echo json_encode(['reply' => "I didn't catch that — could you type your question again?"]);
    exit;
}

// ---------------------------------------------------------------
// 1. Normalize & tokenize the user's message
// ---------------------------------------------------------------
function normalize(string $text): string {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text); // strip punctuation
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}

$stopwords = [
    'the','a','an','is','are','am','do','does','did','can','could','will',
    'would','should','i','me','my','you','your','it','its','to','of','for',
    'in','on','at','and','or','how','what','when','where','why','which',
    'this','that','be','have','has','had','was','were','with','about','if',
    'about','so','please','tell','need','want'
];

function tokenize(string $normalized, array $stopwords): array {
    $words = explode(' ', $normalized);
    $words = array_filter($words, function ($w) use ($stopwords) {
        return strlen($w) >= 3 && !in_array($w, $stopwords, true);
    });
    return array_values(array_unique($words));
}

// Small synonym map so casual phrasing still matches site vocabulary
$synonyms = [
    'buy'        => 'order',
    'purchase'   => 'order',
    'shop'       => 'store',
    'shopping'   => 'store',
    'cost'       => 'price',
    'sign up'    => 'register',
    'signup'     => 'register',
    'login'      => 'log',
    'signin'     => 'log',
    'game'       => 'games',
    'play'       => 'games',
    'book'       => 'library',
    'books'      => 'library',
    'read'       => 'library',
    'class'      => 'classes',
    'teacher'    => 'careers',
    'job'        => 'careers',
    'hire'       => 'careers',
    'kid'        => 'child',
    'kids'       => 'child',
    'parent'     => 'dashboard',
];

$normalizedMsg = normalize($rawMessage);
foreach ($synonyms as $from => $to) {
    $normalizedMsg = preg_replace('/\b' . preg_quote($from, '/') . '\b/', $to, $normalizedMsg);
}
$userWords = tokenize($normalizedMsg, $stopwords);

// ---------------------------------------------------------------
// 2. Greeting / small-talk shortcuts
// ---------------------------------------------------------------
$greetings = ['hi', 'hello', 'hey', 'salam', 'assalam'];
if (in_array($normalizedMsg, $greetings, true) || (count($userWords) === 0 && $normalizedMsg !== '')) {
    echo json_encode([
        'reply' => "Hello, Explorer! 🚀 Ask me anything about your account, learning programs, games, the store, library, videos, live classes, or your parent dashboard."
    ]);
    exit;
}

if (empty($userWords)) {
    echo json_encode(['reply' => "Could you rephrase that with a bit more detail? I want to make sure I give you the right answer."]);
    exit;
}

// ---------------------------------------------------------------
// 3. Load the full site-wide FAQ knowledge base
// ---------------------------------------------------------------
$stmt = $pdo->query("
    SELECT f.question, f.answer, c.category_name
    FROM faqs f
    LEFT JOIN faq_categories c ON c.id = f.category_id
    WHERE f.status = 1
");
$faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ---------------------------------------------------------------
// 4. Weighted scoring
//    - exact whole-word match in the question: full weight
//    - substring match (partial word): half weight
//    - category-name match: small bonus
//    - score normalized against question length so short, precise
//      questions aren't drowned out by long ones
// ---------------------------------------------------------------
$best = null;
$bestScore = 0.0;

foreach ($faqs as $faq) {
    $questionNorm = normalize($faq['question']);
    $questionWords = tokenize($questionNorm, $stopwords);
    $categoryNorm = normalize($faq['category_name'] ?? '');

    if (empty($questionWords)) continue;

    $rawScore = 0.0;
    foreach ($userWords as $word) {
        if (in_array($word, $questionWords, true)) {
            $rawScore += 1.0; // exact word hit
        } elseif (strpos($questionNorm, $word) !== false) {
            $rawScore += 0.5; // partial/substring hit
        }
        if ($categoryNorm !== '' && strpos($categoryNorm, $word) !== false) {
            $rawScore += 0.25; // category relevance bonus
        }
    }

    // Normalize: reward matching a larger share of the question's own words
    $overlapRatio = $rawScore / max(count($questionWords), count($userWords));
    $finalScore = $rawScore + $overlapRatio;

    if ($finalScore > $bestScore) {
        $bestScore = $finalScore;
        $best = $faq;
    }
}

// ---------------------------------------------------------------
// 5. Respond — require a minimum confidence before answering
// ---------------------------------------------------------------
$CONFIDENCE_THRESHOLD = 1.0;

if ($best !== null && $bestScore >= $CONFIDENCE_THRESHOLD) {
    echo json_encode(['reply' => $best['answer']]);
} else {
    echo json_encode([
        'reply' => "I couldn't find an exact answer to that. You can browse the FAQ topics above (Account, Learning, Store, Games, Library, Videos, Programs, Parent Dashboard, Careers) or contact our Mission Control support team for help."
    ]);
}