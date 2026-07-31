<?php
/**
 * child_account.php
 * ─────────────────────────────────────────────────────────────
 * Shared helpers for parent-created child accounts. Used by:
 *   - parent/check_child_username.php  (live availability check)
 *   - parent/parent_dashboard.php      (actually creating the account)
 *
 * Child accounts don't have a real email address. The platform's
 * login (account/login.php) and everything else authenticates by
 * users.email — so instead of changing that shared logic, we give
 * every child account a generated, internal-only "email" of the form
 * "<username>@" . CHILD_ACCOUNT_DOMAIN. It looks like an email (so it
 * satisfies the existing column/lookup), but nothing is ever sent to
 * it — the parent just shares the full string with their child as
 * their login name.
 * ─────────────────────────────────────────────────────────────
 */

if (!defined('CHILD_ACCOUNT_DOMAIN')) {
    define('CHILD_ACCOUNT_DOMAIN', 'kids.safekidsspace.local');
}
if (!defined('CHILD_USERNAME_MIN_LEN')) {
    define('CHILD_USERNAME_MIN_LEN', 3);
}
if (!defined('CHILD_USERNAME_MAX_LEN')) {
    define('CHILD_USERNAME_MAX_LEN', 20);
}
if (!defined('CHILD_PASSWORD_MIN_LEN')) {
    define('CHILD_PASSWORD_MIN_LEN', 4); // kept short & simple on purpose — these are kids' passwords
}

/**
 * Turns "Sam" into a friendly starting suggestion like "sam".
 * The parent can freely edit it afterwards.
 */
function child_suggest_username(string $fullname): string {
    $slug = strtolower(trim($fullname));
    $slug = preg_replace('/[^a-z0-9]+/', '', $slug);
    $slug = substr($slug, 0, CHILD_USERNAME_MAX_LEN);
    if (strlen($slug) < CHILD_USERNAME_MIN_LEN) {
        $slug = str_pad($slug, CHILD_USERNAME_MIN_LEN, '0');
    }
    return $slug;
}

/**
 * Returns null if the username format is fine, or a human-readable
 * reason string if it isn't (no DB lookup here — just shape rules).
 */
function child_username_format_error(string $username): ?string {
    if ($username === '') {
        return 'Please enter a username.';
    }
    if (strlen($username) < CHILD_USERNAME_MIN_LEN) {
        return 'Username must be at least ' . CHILD_USERNAME_MIN_LEN . ' characters.';
    }
    if (strlen($username) > CHILD_USERNAME_MAX_LEN) {
        return 'Username must be ' . CHILD_USERNAME_MAX_LEN . ' characters or fewer.';
    }
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        return 'Only letters, numbers, and underscores are allowed.';
    }
    return null;
}

function child_password_format_error(string $password): ?string {
    if (strlen($password) < CHILD_PASSWORD_MIN_LEN) {
        return 'Password must be at least ' . CHILD_PASSWORD_MIN_LEN . ' characters.';
    }
    return null;
}

function child_username_to_email(string $username): string {
    return strtolower($username) . '@' . CHILD_ACCOUNT_DOMAIN;
}
