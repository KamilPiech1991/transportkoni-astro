<?php
/**
 * Prosty handler formularzy dla hostingu z PHP (np. cPanel / home.pl / LH).
 * Zastępuje WPForms (Kontakt, Szkolenie) i Contact Form 7 (Zamów transport).
 * Ustaw adresy poniżej. Na GitHub Pages PHP nie działa — tam formularze
 * wymagają zewnętrznej usługi (np. Formspree / Web3Forms).
 */
header('Content-Type: application/json; charset=utf-8');

$recipients = [
    'kontakt'         => 'biuro@transportkoni-24.pl',
    'szkolenie'       => 'biuro@transportkoni-24.pl',
    'zamow-transport' => 'spedycja.laczenia@transportkoni-24.pl',
];
$from = 'no-reply@transportkoni-24.pl';

function reply($ok, $msg) { echo json_encode(['ok' => $ok, 'message' => $msg]); exit; }

if ($_SERVER['REQUEST_METHOD'] !== 'POST') reply(false, 'Nieprawidłowe żądanie.');
if (!empty($_POST['website'])) reply(true, 'Dziękujemy za wiadomość. Została wysłana.'); // honeypot

$form = $_POST['_form'] ?? 'kontakt';
$to = $recipients[$form] ?? $recipients['kontakt'];

$lines = [];
$replyTo = null;
foreach ($_POST as $k => $v) {
    if (in_array($k, ['_form', 'website'], true)) continue;
    if (is_array($v)) $v = implode(' ', array_map('strval', $v));
    $v = trim(strip_tags((string)$v));
    if ($v === '') continue;
    if (!$replyTo && filter_var($v, FILTER_VALIDATE_EMAIL)) $replyTo = $v;
    $lines[] = ucfirst(str_replace(['_', '-'], ' ', $k)) . ': ' . $v;
}
if (!$replyTo) reply(false, 'Podaj poprawny adres e-mail.');

$subject = '=?UTF-8?B?' . base64_encode('transportkoni-24.pl – formularz: ' . $form) . '?=';
$headers = "From: Transportkoni-24.pl <$from>\r\nReply-To: $replyTo\r\nContent-Type: text/plain; charset=UTF-8\r\n";

if (mail($to, $subject, implode("\n", $lines), $headers)) {
    reply(true, 'Dziękujemy za wiadomość. Została wysłana.');
}
reply(false, 'Wystąpił błąd podczas wysyłania wiadomości. Spróbuj ponownie później.');
