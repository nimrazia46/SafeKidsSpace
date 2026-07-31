<?php
/**
 * payment_form.php — shared manual-payment form
 * ─────────────────────────────────────────────────────────────
 * Single source of truth for the JazzCash / EasyPaisa / Bank Transfer /
 * Other manual payment form. Used in two places so it never has to be
 * kept in sync by hand again:
 *
 *   1. parent/parent_programs.php     — included directly, PHP-rendered,
 *                                        one copy per program card.
 *   2. parent/get_enrollment_status.php — rendered into an output buffer
 *                                        and sent back as JSON
 *                                        (`payment_form_html`), which
 *                                        learning.php's AJAX enroll/pay
 *                                        popup injects via innerHTML.
 *
 * Expects these variables set by the caller BEFORE including this file:
 *
 *   $payment_form_action        string  form "action" URL, relative to
 *                                        wherever the HTML ends up living
 *                                        (e.g. '' when parent_programs.php
 *                                        includes itself, or
 *                                        'parent_programs.php' when this
 *                                        is rendered for learning.php,
 *                                        which is one folder up).
 *   $payment_enrollment_id      int     enrollments.id this payment is for.
 *   $payment_button_amount_label string (optional) e.g. "Rs.500 " to show
 *                                        the amount in the submit button.
 *                                        Leave unset/empty to omit it.
 * ─────────────────────────────────────────────────────────────
 */

$payment_button_amount_label = $payment_button_amount_label ?? '';
?>
<form method="POST" action="<?= htmlspecialchars($payment_form_action) ?>" class="payment-popup-form">
    <input type="hidden" name="action" value="submit_payment">
    <input type="hidden" name="enrollment_id" value="<?= intval($payment_enrollment_id) ?>">
    <select name="method" class="pd-select payment-popup-select" required>
        <option value="jazzcash">JazzCash</option>
        <option value="easypaisa">EasyPaisa</option>
        <option value="bank_transfer">Bank Transfer</option>
        <option value="other">Other</option>
    </select>
    <input type="text" name="reference_note" class="pd-input payment-popup-input" placeholder="Transaction ID / reference" required>
    <button type="submit" class="popup-continue-btn payment-popup-submit-btn">
        <i class="fa-solid fa-paper-plane"></i> Submit <?= htmlspecialchars($payment_button_amount_label) ?>Payment for Review
    </button>
</form>
