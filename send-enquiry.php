<?php
/*
 * Enquiry form handler for faiyazkhairaz.com
 * Sends each website enquiry to the three addresses below using the
 * web host's own mail system (PHP mail). No third-party service is used.
 *
 * Requirements on the host:
 *  - PHP enabled (standard on Hostinger, GoDaddy, Bluehost and other cPanel hosting)
 *  - The mailbox connect@faiyazkhairaz.com created in the hosting panel
 */

// ---------- Settings ----------
$recipients = [
    'connect@faiyazkhairaz.com',
    'faiyaz@compufield.net',
    'corporates.training@gmail.com',
];
$from_address = 'connect@faiyazkhairaz.com';   // must be a mailbox on this domain
$from_name    = 'faiyazkhairaz.com Enquiry';
$send_acknowledgement = true;                   // short "thank you" email to the person who enquired
// --------------------------------

date_default_timezone_set('Asia/Kolkata');
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function respond($ok, $message, $code = 200) {
    http_response_code($code);
    echo json_encode(['ok' => $ok, 'message' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Please use the enquiry form on the website.', 405);
}

// Clean a single-line field: trim, strip tags, remove line breaks (blocks header injection), limit length
function line($key, $max = 200) {
    $v = isset($_POST[$key]) ? (string)$_POST[$key] : '';
    $v = strip_tags($v);
    $v = preg_replace('/[\r\n\t]+/', ' ', $v);
    $v = preg_replace('/\s{2,}/', ' ', $v);
    return mb_substr(trim($v), 0, $max);
}
// Clean a multi-line field
function block($key, $max = 3000) {
    $v = isset($_POST[$key]) ? (string)$_POST[$key] : '';
    $v = strip_tags($v);
    $v = str_replace("\r", '', $v);
    return mb_substr(trim($v), 0, $max);
}

// ---------- Spam protection ----------
// 1. Honeypot: a hidden field real people never fill in
if (!empty($_POST['website'])) {
    respond(true, 'Thank you. Your enquiry has been sent.');
}
// 2. Time check: forms submitted less than 3 seconds after the page loaded are almost always bots
$started = isset($_POST['started']) ? (int)$_POST['started'] : 0;
if ($started > 0 && (time() * 1000 - $started) < 3000) {
    respond(false, 'That was very quick. Please wait a moment and send again.', 429);
}
// 3. Simple rate limit: one enquiry per 30 seconds from the same visitor
session_start();
if (!empty($_SESSION['last_enquiry']) && time() - $_SESSION['last_enquiry'] < 30) {
    respond(false, 'Your enquiry was just sent. Please wait a few seconds before sending another.', 429);
}

// ---------- Read the form ----------
$name        = line('name', 120);
$company     = line('company', 160);
$designation = line('designation', 120);
$email       = line('email', 160);
$phone       = line('phone', 40);
$location    = line('location', 120);
$topics      = isset($_POST['topics']) && is_array($_POST['topics'])
                 ? array_slice(array_map(function ($t) { return mb_substr(preg_replace('/[\r\n]+/', ' ', strip_tags((string)$t)), 0, 60); }, $_POST['topics']), 0, 12)
                 : [];
$team_size   = line('team_size', 40);
$format      = line('format', 80);
$timeline    = line('timeline', 80);
$message     = block('message', 3000);

// ---------- Validate ----------
$errors = [];
if ($name === '')    { $errors[] = 'your name'; }
if ($company === '') { $errors[] = 'your company'; }
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errors[] = 'a valid email address'; }
if ($phone !== '' && !preg_match('/^[0-9 +()\-.]{6,40}$/', $phone)) { $errors[] = 'a valid phone number'; }
if ($errors) {
    respond(false, 'Please add ' . implode(', ', $errors) . '.', 422);
}

// ---------- Build the email ----------
$topics_text = $topics ? implode(', ', $topics) : 'Not specified';
$subject_raw = 'New training enquiry: ' . $company . ($topics ? ' (' . implode(', ', array_slice($topics, 0, 3)) . ')' : '');
$subject     = '=?UTF-8?B?' . base64_encode($subject_raw) . '?=';

$body  = "New enquiry from faiyazkhairaz.com\n";
$body .= "==================================\n\n";
$body .= "Name:         $name\n";
$body .= "Company:      $company\n";
$body .= "Designation:  " . ($designation !== '' ? $designation : '-') . "\n";
$body .= "Email:        $email\n";
$body .= "Phone:        " . ($phone !== '' ? $phone : '-') . "\n";
$body .= "Location:     " . ($location !== '' ? $location : '-') . "\n\n";
$body .= "Topics:       $topics_text\n";
$body .= "Team size:    " . ($team_size !== '' ? $team_size : '-') . "\n";
$body .= "Format:       " . ($format !== '' ? $format : '-') . "\n";
$body .= "Timeline:     " . ($timeline !== '' ? $timeline : '-') . "\n\n";
$body .= "Message:\n" . ($message !== '' ? $message : '-') . "\n\n";
$body .= "----------------------------------\n";
$body .= "Sent: " . date('d M Y, h:i A') . " IST\n";
$body .= "Reply to this email to answer $name directly.\n";

$headers  = "From: " . '=?UTF-8?B?' . base64_encode($from_name) . '?=' . " <$from_address>\r\n";
$headers .= "Reply-To: $name <$email>\r\n";
$headers .= "MIME-Version: 1.0\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
$headers .= "Content-Transfer-Encoding: 8bit\r\n";
$headers .= "X-Mailer: PHP/" . phpversion();

$sent = mail(implode(', ', $recipients), $subject, $body, $headers, '-f' . $from_address);

if (!$sent) {
    respond(false, 'Sorry, the enquiry could not be sent right now. Please email connect@faiyazkhairaz.com or WhatsApp +91 98190 06132.', 500);
}

$_SESSION['last_enquiry'] = time();

// ---------- Optional acknowledgement to the visitor ----------
if ($send_acknowledgement) {
    $ack_subject = '=?UTF-8?B?' . base64_encode('Thank you for your training enquiry') . '?=';
    $ack  = "Dear $name,\n\n";
    $ack .= "Thank you for your enquiry about training for $company. I have received your details and will get back to you shortly with next steps.\n\n";
    $ack .= "If it is urgent, you can also reach me on WhatsApp at +91 98190 06132.\n\n";
    $ack .= "Warm regards,\n";
    $ack .= "Faiyaz M Khairaz\n";
    $ack .= "Microsoft Certified Trainer | Freelance AI Trainer\n";
    $ack .= "https://faiyazkhairaz.com\n";
    $ack_headers  = "From: " . '=?UTF-8?B?' . base64_encode('Faiyaz M Khairaz') . '?=' . " <$from_address>\r\n";
    $ack_headers .= "Reply-To: $from_address\r\n";
    $ack_headers .= "MIME-Version: 1.0\r\n";
    $ack_headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    @mail($email, $ack_subject, $ack, $ack_headers, '-f' . $from_address);
}

respond(true, 'Thank you. Your enquiry has been sent, and I will get back to you shortly.');
