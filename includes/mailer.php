<?php
/**
 * mailer.php — shared email sender (PHPMailer + real SMTP)
 * ─────────────────────────────────────────────────────────────
 * PHP's built-in mail() (used previously in career/apply.php) needs a
 * properly configured local mail server, which localhost/most shared
 * hosts don't have out of the box — emails silently never arrive.
 * This uses PHPMailer talking to a real SMTP account instead, which
 * works the same on localhost and in production.
 *
 * ⚠️ SETUP REQUIRED — fill in real credentials below before this will
 * actually send anything:
 *   1. SMTP_USERNAME — a real Gmail address (or your SMTP provider's).
 *   2. SMTP_PASSWORD — for Gmail, this must be a 16-character "App
 *      Password" (Google Account → Security → 2-Step Verification →
 *      App Passwords). Your normal Gmail login password will NOT work.
 *   3. If you use a different provider (Outlook, a hosting provider's
 *      SMTP, SendGrid, etc.) update SMTP_HOST / SMTP_PORT to match.
 *
 * Usage:
 *   require_once __DIR__ . '/mailer.php';
 *   send_email('someone@example.com', 'Subject', '<p>HTML body</p>');
 * ─────────────────────────────────────────────────────────────
 */

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

// ── SMTP CONFIG — fill these in ─────────────────────────────────
if (!defined('SMTP_HOST'))     define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_PORT'))     define('SMTP_PORT', 587);
if (!defined('SMTP_USERNAME')) define('SMTP_USERNAME', 'safekidssspace@gmail.com');   // TODO: fill in
if (!defined('SMTP_PASSWORD')) define('SMTP_PASSWORD', 'tleq avlr utwg abpo'); // TODO: fill in
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', 'SafeKidsSpace');

/**
 * Sends one email. Returns true on success, false on failure — never
 * throws, so a mail outage can never take down a page that calls this
 * (registration, career applications, etc. all keep working either way).
 *
 * @param string $to_email
 * @param string $subject
 * @param string $body_html
 * @param string $reply_to_email  optional — set for things like career
 *                                 applications, where you may want to
 *                                 reply straight to the applicant.
 * @param string $attachment_path optional — absolute path to a file on
 *                                 disk (e.g. a submitted CV) to attach.
 * @param string $attachment_name optional — display filename for the
 *                                 attachment; defaults to the real filename.
 */
$GLOBALS['LAST_MAIL_ERROR'] = '';

function send_email(string $to_email, string $subject, string $body_html, string $reply_to_email = '', string $attachment_path = '', string $attachment_name = ''): bool {
    $mail = new PHPMailer(true);
    $GLOBALS['LAST_MAIL_ERROR'] = '';
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USERNAME;
        $mail->Password   = SMTP_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        $mail->setFrom(SMTP_USERNAME, SMTP_FROM_NAME);
        $mail->addAddress($to_email);
        if ($reply_to_email !== '') {
            $mail->addReplyTo($reply_to_email);
        }
        if ($attachment_path !== '' && is_file($attachment_path)) {
            $mail->addAttachment($attachment_path, $attachment_name !== '' ? $attachment_name : basename($attachment_path));
        }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body_html;
        $mail->AltBody  = trim(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $body_html)));

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        error_log('send_email() failed for ' . $to_email . ': ' . $mail->ErrorInfo);
        $GLOBALS['LAST_MAIL_ERROR'] = $mail->ErrorInfo;
        return false;
    }
}