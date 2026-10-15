<?php
/**
 * Prosty handler formularzy dla hostingu z PHP (np. cPanel / home.pl / LH).
 * Zastępuje WPForms (Kontakt, Szkolenie) i Contact Form 7 (Zamów transport).
 * Ustaw adresy poniżej. Na GitHub Pages PHP nie działa — tam formularze
 * wymagają zewnętrznej usługi (np. Formspree / Web3Forms).
 *
 * Zabezpieczenia: tylko POST z tej samej domeny, lista dozwolonych formularzy,
 * honeypot, limit rozmiaru i liczby pól, limit wysyłek z jednego IP,
 * adres Reply-To walidowany (brak wstrzykiwania nagłówków), treść jako plain text.
 */
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

$recipients = [
    'kontakt'         => 'biuro@transportkoni-24.pl',
    'szkolenie'       => 'biuro@transportkoni-24.pl',
    'zamow-transport' => 'spedycja.laczenia@transportkoni-24.pl',
];
$from = 'no-reply@transportkoni-24.pl';

const MAX_BODY_BYTES = 20000;   // cały formularz
const MAX_FIELDS     = 60;
const MAX_VALUE_LEN  = 5000;    // jedno pole
const RATE_LIMIT     = 5;       // wysyłek z jednego IP…
const RATE_WINDOW    = 600;     // …na 10 minut

function reply($ok, $msg, $code = 200) {
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    reply(false, 'Nieprawidłowe żądanie.', 405);
}

// tylko z własnej strony (Origin/Referer muszą wskazywać na ten sam host)
$host = strtolower(preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'] ?? ''));
$source = $_SERVER['HTTP_ORIGIN'] ?? ($_SERVER['HTTP_REFERER'] ?? '');
$sourceHost = strtolower((string)parse_url($source, PHP_URL_HOST));
if ($host === '' || $sourceHost === '' || $sourceHost !== $host) {
    reply(false, 'Nieprawidłowe żądanie.', 403);
}

if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > MAX_BODY_BYTES || count($_POST) > MAX_FIELDS) {
    reply(false, 'Formularz jest zbyt duży.', 413);
}

if (!empty($_POST['website'])) reply(true, 'Dziękujemy za wiadomość. Została wysłana.'); // honeypot

$form = $_POST['_form'] ?? '';
if (!is_string($form) || !isset($recipients[$form])) reply(false, 'Nieprawidłowe żądanie.', 400);
$to = $recipients[$form];

// limit wysyłek z jednego adresu IP (pliki w katalogu tymczasowym serwera)
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rateFile = sys_get_temp_dir() . '/tk24_form_' . hash('sha256', $ip . __FILE__);
$now = time();
$hits = [];
if (is_readable($rateFile)) {
    $hits = array_filter(array_map('intval', explode(',', (string)file_get_contents($rateFile))),
        function ($t) use ($now) { return $t > $now - RATE_WINDOW; });
}
if (count($hits) >= RATE_LIMIT) reply(false, 'Zbyt wiele wiadomości. Spróbuj ponownie za kilka minut.', 429);

function clean_text($v) {
    if (is_array($v)) $v = implode(' ', array_map('clean_text', $v));
    $v = trim(strip_tags((string)$v));
    $v = preg_replace('/[^\P{C}\n\t]/u', '', $v);   // bez znaków sterujących
    return mb_substr((string)$v, 0, MAX_VALUE_LEN);
}

$lines = [];
$replyTo = null;
foreach ($_POST as $k => $v) {
    if (in_array($k, ['_form', 'website'], true)) continue;
    $key = preg_replace('/[^a-z0-9_\-]/i', '', (string)$k);
    if ($key === '') continue;
    $v = clean_text($v);
    if ($v === '') continue;
    if (!$replyTo && filter_var($v, FILTER_VALIDATE_EMAIL)) $replyTo = $v;
    $lines[] = ucfirst(str_replace(['_', '-'], ' ', $key)) . ': ' . $v;
}
if (!$replyTo) reply(false, 'Podaj poprawny adres e-mail.', 422);

$subject = '=?UTF-8?B?' . base64_encode('transportkoni-24.pl – formularz: ' . $form) . '?=';
$headers = "From: Transportkoni-24.pl <$from>\r\nReply-To: $replyTo\r\nContent-Type: text/plain; charset=UTF-8\r\n";

$hits[] = $now;
@file_put_contents($rateFile, implode(',', $hits), LOCK_EX);

if (mail($to, $subject, implode("\n", $lines), $headers)) {
    reply(true, 'Dziękujemy za wiadomość. Została wysłana.');
}
reply(false, 'Wystąpił błąd podczas wysyłania wiadomości. Spróbuj ponownie później.', 500);
