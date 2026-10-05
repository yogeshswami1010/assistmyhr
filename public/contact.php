<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');

function respond(int $status, bool $success, string $message): void {
    http_response_code($status);
    echo json_encode(['success' => $success, 'message' => $message]);
    exit;
}
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Allow: POST');
    respond(405, false, 'Please submit the contact form.');
}
if ((int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 20000) {
    respond(413, false, 'Your message is too large. Please shorten it.');
}
$raw = file_get_contents('php://input', false, null, 0, 20001);
if ($raw === false || strlen($raw) > 20000) {
    respond(413, false, 'Your message is too large. Please shorten it.');
}
$data = json_decode($raw, true);
if (!is_array($data)) {
    respond(400, false, 'Invalid request. Please try again.');
}
$limits = ['name' => 150, 'email' => 254, 'phone' => 60, 'company' => 200, 'topic' => 60, 'message' => 10000, 'website' => 200];
$fields = [];
foreach ($limits as $key => $limit) {
    $value = $data[$key] ?? '';
    if (!is_string($value) || strlen($value) > $limit || strpos($value, "\0") !== false) {
        respond(422, false, 'Please check your form fields and message length.');
    }
    $fields[$key] = trim($value);
}
if ($fields['website'] !== '') {
    respond(422, false, 'Unable to submit this enquiry.');
}
if ($fields['name'] === '' || $fields['message'] === '' ||
    !filter_var($fields['email'], FILTER_VALIDATE_EMAIL) ||
    preg_match('/[\r\n]/', $fields['email'])) {
    respond(422, false, 'Please enter your name, a valid email address, and a message.');
}
$topics = ['Live demo', 'Pricing', 'AI CV Search', 'Temp Staffing', 'Something else'];
if (!in_array($fields['topic'], $topics, true)) {
    respond(422, false, 'Please select a valid enquiry topic.');
}

// Limit attempts to 3 per IP per 10 minutes; store only a hash and counters.
$key = hash('sha256', __DIR__ . '|' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
$ratePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'assistmyhr-' . $key . '.json';
$handle = @fopen($ratePath, 'c+');
if (!$handle || !flock($handle, LOCK_EX)) {
    if ($handle) fclose($handle);
    respond(503, false, 'The contact service is temporarily unavailable. Please email assistmyday@gmail.com.');
}
$rate = json_decode(stream_get_contents($handle), true);
$now = time();
if (!is_array($rate) || $now - (int) ($rate['start'] ?? 0) >= 600) {
    $rate = ['start' => $now, 'count' => 0];
}
if ((int) $rate['count'] >= 3) {
    flock($handle, LOCK_UN);
    fclose($handle);
    header('Retry-After: ' . max(1, 600 - ($now - (int) $rate['start'])));
    respond(429, false, 'Too many attempts. Please wait a few minutes or email assistmyday@gmail.com.');
}
$rate['count']++;
rewind($handle);
ftruncate($handle, 0);
$saved = fwrite($handle, json_encode($rate));
fflush($handle);
flock($handle, LOCK_UN);
fclose($handle);
if ($saved === false) respond(503, false, 'The contact service is temporarily unavailable.');

$config = require __DIR__ . '/contact-config.php';
$to = $config['to'] ?? '';
$from = $config['from'] ?? '';
if ($from === '') {
    $domain = strtolower($_SERVER['SERVER_NAME'] ?? '');
    if (strpos($domain, '.') === false || filter_var($domain, FILTER_VALIDATE_IP) ||
        !filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
        respond(503, false, 'Email delivery needs hosting configuration. Please email assistmyday@gmail.com.');
    }
    $from = 'website@' . $domain;
}
foreach ([$to, $from] as $address) {
    if (!is_string($address) || !filter_var($address, FILTER_VALIDATE_EMAIL) || preg_match('/[\r\n]/', $address)) {
        respond(503, false, 'Email delivery needs hosting configuration. Please email assistmyday@gmail.com.');
    }
}
$body = "New AssistMyHR website enquiry\n\n";
foreach (['name' => 'Full name', 'email' => 'Email', 'phone' => 'Phone', 'company' => 'Company', 'topic' => 'Interested in'] as $key => $label) {
    $body .= $label . ': ' . ($fields[$key] ?: '-') . "\n";
}
$body .= "\nMessage:\n" . $fields['message'] . "\n";
$headers = [
    'From' => 'AssistMyHR Website <' . $from . '>',
    'Reply-To' => $fields['email'],
    'MIME-Version' => '1.0',
    'Content-Type' => 'text/plain; charset=UTF-8',
    'Content-Transfer-Encoding' => 'base64',
];
// Fixed subject and recipient: client input cannot add recipients or headers.
try {
    $accepted = function_exists('mail') && @mail($to, 'New AssistMyHR website enquiry', chunk_split(base64_encode($body)), $headers);
} catch (Throwable $exception) {
    $accepted = false;
}
if (!$accepted) {
    error_log('AssistMyHR: mail transport did not accept the enquiry.');
    respond(503, false, 'Unable to send right now. Your message is still in the form. Please try again or email assistmyday@gmail.com.');
}
respond(200, true, 'Thank you! Your enquiry has been submitted. Our team will get back to you soon.');
