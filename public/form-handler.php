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
 *
 * Odporność na ustawienia hostingu: ostrzeżenia PHP nie trafiają do odpowiedzi
 * (zawsze czysty JSON), a każdy błąd ma kod (np. MAIL) widoczny pod formularzem.
 */
ini_set('display_errors', '0');
ob_start();
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

$recipients = [
    'kontakt'         => 'biuro@transportkoni-24.pl',
    'szkolenie'       => 'biuro@transportkoni-24.pl',
    'zamow-transport' => 'spedycja.laczenia@transportkoni-24.pl',
];
// Nadawca wiadomości. Puste = adres odbiorcy danego formularza — większość hostingów wysyła
// tylko z istniejącej skrzynki w domenie (adres typu no-reply@ bez skrzynki bywa odrzucany).
$from = '';

const MAX_BODY_BYTES = 20000;   // cały formularz
const MAX_FIELDS     = 60;
const MAX_VALUE_LEN  = 5000;    // jedno pole
const RATE_LIMIT     = 5;       // wysyłek z jednego IP…
const RATE_WINDOW    = 600;     // …na 10 minut

const MSG_OK    = 'Dziękujemy za wiadomość. Została wysłana.';
const MSG_FAIL  = 'Wystąpił błąd podczas wysyłania wiadomości. Spróbuj ponownie później lub zadzwoń: 519 717 868.';

$replied = false;
function reply($ok, $msg, $code = 200) {
    global $replied;
    $replied = true;
    while (ob_get_level()) ob_end_clean();          // bez ostrzeżeń PHP przed JSON-em
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'message' => $msg], JSON_UNESCAPED_UNICODE);
    exit;
}
function fail($errCode, $detail = '') {
    error_log("form-handler [$errCode] $detail");
    reply(false, MSG_FAIL . " (kod: $errCode)", 500);
}
// błąd krytyczny PHP (np. brak funkcji na serwerze) — też jako JSON z kodem
register_shutdown_function(function () {
    global $replied;
    if ($replied) return;
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        fail('PHP', $e['message'] . ' @' . $e['line']);
    }
});

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    reply(false, 'Nieprawidłowe żądanie.', 405);
}

// tylko z własnej strony (Origin/Referer muszą wskazywać na ten sam host; www. bez znaczenia)
$normHost = function ($h) { return preg_replace('/^www\./', '', strtolower(preg_replace('/:\d+$/', '', (string)$h))); };
$host = $normHost($_SERVER['HTTP_HOST'] ?? '');
$source = $_SERVER['HTTP_ORIGIN'] ?? '';
if ($source === '' || $source === 'null') $source = $_SERVER['HTTP_REFERER'] ?? '';
$sourceHost = $normHost(parse_url($source, PHP_URL_HOST));
if ($host === '' || $sourceHost === '' || $sourceHost !== $host) {
    reply(false, 'Nieprawidłowe żądanie (kod: ORIGIN). Odśwież stronę i spróbuj ponownie.', 403);
}

if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > MAX_BODY_BYTES || count($_POST) > MAX_FIELDS) {
    reply(false, 'Formularz jest zbyt duży.', 413);
}

if (!empty($_POST['website'])) reply(true, MSG_OK); // honeypot

$form = $_POST['_form'] ?? '';
if (!is_string($form) || !isset($recipients[$form])) reply(false, 'Nieprawidłowe żądanie (kod: FORM).', 400);
$to = $recipients[$form];
$sender = $from !== '' ? $from : $to;

// limit wysyłek z jednego adresu IP. Katalog tymczasowy serwera, a gdy jest niedostępny
// (open_basedir) — ukryty katalog obok skryptu (.htaccess blokuje pliki z kropką).
// Gdy nigdzie nie da się zapisać, limit jest pomijany zamiast blokować formularz.
function rate_dir() {
    $dirs = [sys_get_temp_dir(), __DIR__ . '/.form-limits'];
    foreach ($dirs as $d) {
        if ($d === '' || $d === false) continue;
        if (!@is_dir($d)) @mkdir($d, 0700, true);
        if (@is_dir($d) && @is_writable($d)) return $d;
    }
    return null;
}
$ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
$rateDir = rate_dir();
$rateFile = $rateDir ? $rateDir . '/tk24_form_' . hash('sha256', $ip . __FILE__) : null;
$now = time();
$hits = [];
if ($rateFile && @is_readable($rateFile)) {
    $hits = array_filter(array_map('intval', explode(',', (string)@file_get_contents($rateFile))),
        function ($t) use ($now) { return $t > $now - RATE_WINDOW; });
}
if (count($hits) >= RATE_LIMIT) reply(false, 'Zbyt wiele wiadomości. Spróbuj ponownie za kilka minut.', 429);

function clean_text($v) {
    if (is_array($v)) $v = implode(', ', array_filter(array_map('clean_text', $v), 'strlen'));
    $v = trim(strip_tags((string)$v));
    $c = preg_replace('/[^\P{C}\n\t]/u', '', $v);    // bez znaków sterujących
    $v = $c === null ? preg_replace('/[\x00-\x08\x0B-\x1F\x7F]/', '', $v) : $c; // null = błędne UTF-8
    return function_exists('mb_substr') ? mb_substr($v, 0, MAX_VALUE_LEN, 'UTF-8') : substr($v, 0, MAX_VALUE_LEN);
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

if (!function_exists('mail')) fail('NOMAIL', 'mail() wyłączone na serwerze');

$subject = '=?UTF-8?B?' . base64_encode('transportkoni-24.pl – formularz: ' . $form) . '?=';
$fromName = '=?UTF-8?B?' . base64_encode('Formularz transportkoni-24.pl') . '?=';
$headers = implode("\r\n", [
    "From: $fromName <$sender>",
    "Reply-To: $replyTo",
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'Content-Transfer-Encoding: base64',
    'X-Mailer: transportkoni-24.pl',
]);
$body = chunk_split(base64_encode(implode("\r\n", $lines) . "\r\n\r\n-- \r\nWysłano z formularza na stronie transportkoni-24.pl"));

$hits[] = $now;
if ($rateFile) @file_put_contents($rateFile, implode(',', $hits), LOCK_EX);

// najpierw z adresem zwrotnym koperty (-f) — część serwerów odrzuca pocztę bez niego,
// a gdy hosting nie pozwala go ustawić, druga próba bez -f
error_clear_last();
$sent = @mail($to, $subject, $body, $headers, '-f' . $sender);
if (!$sent) $sent = @mail($to, $subject, $body, $headers);
if ($sent) reply(true, MSG_OK);

$err = error_get_last();
fail('MAIL', $err ? $err['message'] : 'mail() zwróciło false');
