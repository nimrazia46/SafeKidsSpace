<?php
/**
 * get_enrollment_status.php
 * ─────────────────────────────────────────────────────────────
 * Used by the "Enroll Now" popup on learning.php (and can be reused
 * anywhere else) to fetch the LIVE, real status of one child in one
 * program — instead of any page trying to pre-compute/cache it.
 *
 * GET  ?child_id=&program_id=            → just reads current status
 * POST action=ensure_enrollment          → creates the trial row if
 *                                          none exists yet, then returns
 *                                          the same status shape (used
 *                                          by the "Pay Now — skip trial"
 *                                          button, since a payment row
 *                                          needs an enrollment_id to
 *                                          attach to).
 *
 * Response shape:
 * {
 *   success: true,
 *   status: "not_enrolled" | "trial" | "trial_expired" | "pending" | "active",
 *   enrollment_id: int|null,
 *   monthly_price: number,
 *   program_title: string,
 *   expires_at: "YYYY-MM-DD"|null,
 *   trial_days_left: int|null,
 *   payment_form_html: string (only present for "trial"/"trial_expired" —
 *                              ready-to-inject manual payment form HTML,
 *                              rendered from includes/payment_form.php)
 * }
 * ─────────────────────────────────────────────────────────────
 */

session_start();
require_once __DIR__ . '/../includes/db.php';
header('Content-Type: application/json');

// Never let a stray PHP notice/warning/deprecation leak HTML into the
// response — that would corrupt the JSON and make the popup show a
// generic "Something went wrong" error on the frontend.
ini_set('display_errors', '0');
error_reporting(0);

if (!defined('TRIAL_DAYS')) {
    define('TRIAL_DAYS', 7);
}

$user_role = isset($_SESSION['role']) ? strtolower(trim($_SESSION['role'])) : '';
if ($user_role !== 'parent') {
    echo json_encode(['success' => false, 'error' => 'Please log in as a parent.']);
    exit;
}
$parent_id = $_SESSION['id'];

$is_post = $_SERVER['REQUEST_METHOD'] === 'POST';
$child_id   = intval($is_post ? ($_POST['child_id'] ?? 0)   : ($_GET['child_id'] ?? 0));
$program_id = intval($is_post ? ($_POST['program_id'] ?? 0) : ($_GET['program_id'] ?? 0));

if (!$child_id || !$program_id) {
    echo json_encode(['success' => false, 'error' => 'Missing child or program.']);
    exit;
}

// Ownership check — this child must actually be linked to this parent.
$own = $pdo->prepare("SELECT id FROM parent_monitoring WHERE parent_id = ? AND child_id = ?");
$own->execute([$parent_id, $child_id]);
if (!$own->fetch()) {
    echo json_encode(['success' => false, 'error' => "That child isn't linked to your account."]);
    exit;
}

$prog_stmt = $pdo->prepare("SELECT title, monthly_price FROM programs WHERE id = ?");
$prog_stmt->execute([$program_id]);
$program = $prog_stmt->fetch();
if (!$program) {
    echo json_encode(['success' => false, 'error' => 'Program not found.']);
    exit;
}

try {
    // ── POST ensure_enrollment: create the trial row if it doesn't exist yet ──
    if ($is_post && ($_POST['action'] ?? '') === 'ensure_enrollment') {
        $check = $pdo->prepare("SELECT id FROM enrollments WHERE child_id = ? AND program_id = ?");
        $check->execute([$child_id, $program_id]);
        if (!$check->fetch()) {
            $ins = $pdo->prepare("INSERT INTO enrollments (parent_id, child_id, program_id, status) VALUES (?, ?, ?, 'trial')");
            $ins->execute([$parent_id, $child_id, $program_id]);
        }
    }

    // ── Read the current enrollment row (fresh, after any insert above) ──
    $enr_stmt = $pdo->prepare("SELECT * FROM enrollments WHERE child_id = ? AND program_id = ?");
    $enr_stmt->execute([$child_id, $program_id]);
    $enr = $enr_stmt->fetch();

    $result = [
        'success'         => true,
        'status'          => 'not_enrolled',
        'enrollment_id'   => null,
        'monthly_price'   => floatval($program['monthly_price']),
        'program_title'   => $program['title'],
        'expires_at'      => null,
        'trial_days_left' => null,
    ];

    if ($enr) {
        $result['enrollment_id'] = intval($enr['id']);

        if ($enr['status'] === 'active') {
            $still_active = empty($enr['expires_at']) || $enr['expires_at'] >= date('Y-m-d');
            $result['status']     = $still_active ? 'active' : 'trial_expired'; // treat lapsed subscriptions like an expired trial (needs payment)
            $result['expires_at'] = $enr['expires_at'];
        } elseif ($enr['status'] === 'trial') {
            // Any payment already pending review for this enrollment?
            $pend = $pdo->prepare("SELECT id FROM payments WHERE enrollment_id = ? AND status = 'pending'");
            $pend->execute([$enr['id']]);
            if ($pend->fetch()) {
                $result['status'] = 'pending';
            } else {
                // Compare DATE ONLY (not exact time) so a server/DB timezone
                // offset of a few hours can never push this a day off in
                // either direction.
                $started_date = substr($enr['started_at'] ?: date('Y-m-d H:i:s'), 0, 10);
                $days_used = (int) floor((strtotime(date('Y-m-d')) - strtotime($started_date)) / 86400);
                $days_used = max(0, $days_used); // never negative, even with clock drift
                $days_left = TRIAL_DAYS - $days_used;
                if ($days_left <= 0) {
                    $result['status'] = 'trial_expired';
                } else {
                    $result['status']          = 'trial';
                    $result['trial_days_left'] = intval($days_left);
                }
            }
        } else { // 'expired'
            $result['status'] = 'trial_expired';
        }

        // Both 'trial' and 'trial_expired' need the manual payment form —
        // render it here with the SAME partial parent_programs.php uses,
        // so learning.php's popup can never drift out of sync with it again.
        //
        // NOTE: this HTML is injected into learning.php, which lives at the
        // site ROOT (not inside parent/) — so the form's action must include
        // the "parent/" prefix, unlike parent_programs.php's own copy which
        // submits to itself with an empty action.
        if (in_array($result['status'], ['trial', 'trial_expired'], true)) {
            $payment_form_action    = 'parent/parent_programs.php';
            $payment_enrollment_id  = $result['enrollment_id'];
            ob_start();
            include __DIR__ . '/../includes/payment_form.php';
            $result['payment_form_html'] = ob_get_clean();
        }
    }

    echo json_encode($result);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Server error: ' . $e->getMessage()]);
}