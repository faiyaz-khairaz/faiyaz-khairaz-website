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
// Bots are given the normal "thank you" reply, so they don't learn they were blocked,
// but nothing is emailed. Real visitors never see any of this.
function silently_drop() {
    respond(true, 'Thank you. Your enquiry has been sent, and I will get back to you shortly.');
}

// 1. Honeypot: a hidden field real people never fill in
if (!empty($_POST['website'])) {
    silently_drop();
}
// 2. The page's script fills in the time the form was opened. Spam scripts that post
//    straight to this file don't run that script, so a missing value means a bot.
$started = isset($_POST['started']) ? (int)$_POST['started'] : 0;
$now_ms  = (int)round(microtime(true) * 1000);
if ($started <= 0 || $started > $now_ms + 60000) {
    silently_drop();
}
// 3. Time check: nobody fills in this form in under 4 seconds
if ($now_ms - $started < 4000) {
    respond(false, 'That was very quick. Please wait a moment and send again.', 429);
}
// 4. Per-visitor rate limit by IP address: at most 3 enquiries an hour,
//    plus a cap of 40 enquiries a day for the whole site
function limit_file($name) {
    $dir = rtrim(sys_get_temp_dir(), '/\\') . '/fk-enquiry-limits';
    if (!is_dir($dir)) { @mkdir($dir, 0700, true); }
    return $dir . '/' . $name;
}
function recent_hits($file, $window) {
    $hits = [];
    if (is_file($file)) {
        foreach (explode("\n", (string)@file_get_contents($file)) as $t) {
            if ($t !== '' && (time() - (int)$t) < $window) { $hits[] = (int)$t; }
        }
    }
    return $hits;
}
$ip       = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
$ip_file  = limit_file('ip-' . hash('sha256', $ip));
$day_file = limit_file('site-' . date('Y-m-d'));
$ip_hits  = recent_hits($ip_file, 3600);
$day_hits = recent_hits($day_file, 86400);
if (count($ip_hits) >= 3) {
    respond(false, 'You have sent several enquiries already. Please WhatsApp +91 98190 06132 if it is urgent.', 429);
}
if (count($day_hits) >= 40) {
    respond(false, 'The form is busy right now. Please email connect@faiyazkhairaz.com or WhatsApp +91 98190 06132.', 429);
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

// ---------- Content checks for typical spam ----------
$all_text  = implode(' ', [$name, $company, $designation, $location, $message]);
$link_rx   = '~(https?://|www\.|\[url|<a\s|\b[a-z0-9-]+\.(com|net|org|info|biz|xyz|top|ru|cn|io|co|online|site|shop|store|click|link)\b(/|\s|$))~i';
$is_spam   = false;
// a. Links in the name, designation or location fields, or a web address in the company field
//    (a company name like "Naukri.com" is still allowed)
if (preg_match($link_rx, $name . ' ' . $designation . ' ' . $location)) { $is_spam = true; }
if (preg_match('~(https?://|www\.)~i', $company)) { $is_spam = true; }
// b. More than one link in the message
if (preg_match_all('~(https?://|www\.)~i', $message) > 1) { $is_spam = true; }
// c. Writing systems not used by the site's visitors (Cyrillic, Chinese, Japanese, Korean)
if (preg_match('/[\x{0400}-\x{04FF}\x{3040}-\x{30FF}\x{4E00}-\x{9FFF}\x{AC00}-\x{D7AF}]/u', $all_text)) { $is_spam = true; }
// d. Common spam phrases (SEO, marketing, crypto, loans and similar sales pitches)
$phrases = [
    'seo service', 'seo expert', 'search engine optimi', 'backlink', 'guest post', 'link building',
    'first page of google', 'rank your website', 'ranking on google', 'increase your traffic', 'website traffic',
    'web design service', 'website redesign', 'app development service', 'digital marketing service',
    'lead generation service', 'b2b database', 'buy followers', 'cryptocurrency', 'bitcoin',
    'investment opportunity', 'casino', 'loan offer', 'viagra', 'porn', 'unsubscribe',
    'virtual assistant service', 'chatbot for your website',
    'search index', 'search results', 'submit your site', 'submit your website', 'indexsubmit',
    'google index', 'get indexed', 'appear in google', 'domain expir', 'domain renewal',
];
$lower = mb_strtolower($all_text);
foreach ($phrases as $p) {
    if (mb_strpos($lower, $p) !== false) { $is_spam = true; break; }
}
// e. Random-looking names such as "hJkLqWeRtYz" that spam bots generate
foreach ([$name, $company] as $v) {
    $compact = preg_replace('/\s+/', '', $v);
    if (mb_strlen($compact) >= 8 && preg_match_all('/[a-z][A-Z]/', $compact) >= 3) { $is_spam = true; }
}
// g. "Search index / SEO submission" scams that mention this website by name
//    (real enquiries are about training, not about faiyazkhairaz.com itself)
if (mb_strpos($lower, 'faiyazkhairaz') !== false && preg_match('/google|search|index|submit|seo|rank|traffic|domain|listing/', $lower)) { $is_spam = true; }
// h. Email address that imitates this website (e.g. domains@search-faiyazkhairaz.com)
//    or uses the website's own domain
if (stripos($email, 'faiyazkhairaz') !== false) { $is_spam = true; }
// i. Web addresses written without "http" (e.g. indexsubmit.pro): more than one in the message
$bare_rx = '~\b[a-z0-9-]{2,}\.(com|net|org|info|biz|xyz|top|ru|cn|io|co|online|site|shop|store|click|link|pro|app|live|agency|digital|tech|ai|in)\b~i';
$own_domain = strtolower((string)substr(strrchr($email, '@'), 1));
$domains = [];
if (preg_match_all($bare_rx, $message, $m)) {
    foreach ($m[0] as $d) {
        $d = strtolower($d);
        if ($d !== 'faiyazkhairaz.com' && $d !== $own_domain) { $domains[$d] = true; }
    }
}
if (count($domains) > 1) { $is_spam = true; }
// j. Every training topic ticked, including "Other": bots tick every box
if (count($topics) >= 8) { $is_spam = true; }
// f. Name and company identical and long (bots often repeat one string in every field)
if (mb_strlen($name) > 3 && mb_strtolower($name) === mb_strtolower($company) && $designation === '' && $message === '') { $is_spam = true; }
if ($is_spam) {
    silently_drop();
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

// Count this enquiry towards the rate limits
$ip_hits[] = time();  $day_hits[] = time();
@file_put_contents($ip_file, implode("\n", $ip_hits), LOCK_EX);
@file_put_contents($day_file, implode("\n", $day_hits), LOCK_EX);

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
