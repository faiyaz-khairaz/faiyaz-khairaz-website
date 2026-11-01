<?php
/*
 * calendar-feed.php
 *
 * Reads Faiyaz's public "Corporate Training's" Google Calendar on this server
 * and returns the booked dates as JSON for availability/index.html.
 *
 * Visitors' browsers only talk to faiyazkhairaz.com, never to Google, so the
 * calendar still shows on office networks that block Google.
 *
 * Needs: the calendar set to "Make available to public" in Google Calendar.
 * Changes in Google Calendar show here within CACHE_MINUTES.
 */

$ICS_URL       = 'https://calendar.google.com/calendar/ical/1q6n3rqcsvnal63nr944qm4htc%40group.calendar.google.com/public/basic.ics';
$SHOW_TITLES   = true;  // false = show every booking as "Booked" instead of its title
$CACHE_MINUTES = 5;     // how long a copy of the calendar is reused before fetching again
$MONTHS_AHEAD  = 6;     // how many months after the current one to include

date_default_timezone_set('Asia/Kolkata');
$TZ = new DateTimeZone('Asia/Kolkata');

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');

function out($data) {
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function fetch_ics($url) {
    $agent = 'faiyazkhairaz.com availability page';
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_CONNECTTIMEOUT => 8,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_USERAGENT      => $agent,
        ]);
        $body = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $code !== 200) return null;
    } else {
        $ctx  = stream_context_create(['http' => ['timeout' => 15, 'header' => "User-Agent: $agent\r\n"]]);
        $body = @file_get_contents($url, false, $ctx);
        if ($body === false) return null;
    }
    return strpos($body, 'BEGIN:VCALENDAR') !== false ? $body : null;
}

/* ---------- Get the calendar (fresh, cached, or stale copy) ---------- */

$cacheFile = rtrim(sys_get_temp_dir(), '/\\') . '/fk-availability-' . md5($ICS_URL) . '.ics';
$stale = false;
$ics = null;

if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $CACHE_MINUTES * 60) {
    $ics = @file_get_contents($cacheFile);
}
if (!$ics) {
    $ics = fetch_ics($ICS_URL);
    if ($ics) {
        @file_put_contents($cacheFile, $ics, LOCK_EX);
    } elseif (is_file($cacheFile)) {
        $ics = @file_get_contents($cacheFile);
        $stale = true;
    }
}
if (!$ics) {
    http_response_code(503);
    out(['ok' => false, 'message' => 'The calendar is not available right now.']);
}
$updated = is_file($cacheFile) ? filemtime($cacheFile) : time();

/* ---------- Parse ---------- */

function parse_prop($line) {
    $len = strlen($line);
    $quoted = false;
    for ($i = 0; $i < $len; $i++) {
        $c = $line[$i];
        if ($c === '"') $quoted = !$quoted;
        elseif ($c === ':' && !$quoted) break;
    }
    if ($i >= $len) return null;
    $parts  = explode(';', substr($line, 0, $i));
    $name   = strtoupper(array_shift($parts));
    $params = [];
    foreach ($parts as $p) {
        $kv = explode('=', $p, 2);
        if (count($kv) === 2) $params[strtoupper($kv[0])] = trim($kv[1], '"');
    }
    return [$name, $params, substr($line, $i + 1)];
}

function ics_date($value, $params, $TZ) {
    $value = trim($value);
    $isDate = (isset($params['VALUE']) && strtoupper($params['VALUE']) === 'DATE') || preg_match('/^\d{8}$/', $value);
    if ($isDate) {
        $d = DateTime::createFromFormat('!Ymd', substr($value, 0, 8), $TZ);
        return $d ? [$d, true] : null;
    }
    if (substr($value, -1) === 'Z') {
        $d = DateTime::createFromFormat('!Ymd\THis', substr($value, 0, 15), new DateTimeZone('UTC'));
    } else {
        $tz = $TZ;
        if (!empty($params['TZID'])) {
            try { $tz = new DateTimeZone($params['TZID']); } catch (Exception $e) { $tz = $TZ; }
        }
        $d = DateTime::createFromFormat('!Ymd\THis', substr($value, 0, 15), $tz);
    }
    if (!$d) return null;
    $d->setTimezone($TZ);
    return [$d, false];
}

function ics_text($s) {
    $s = str_replace(['\\n', '\\N'], ' ', $s);
    $s = str_replace(['\\,', '\\;', '\\\\'], [',', ';', '\\'], $s);
    $s = trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $s));
    return function_exists('mb_substr') ? mb_substr($s, 0, 80) : substr($s, 0, 80);
}

function duration_seconds($v) {
    if (!preg_match('/^([+-])?P(?:(\d+)W)?(?:(\d+)D)?(?:T(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?)?$/', trim($v), $m)) return 0;
    $s = ((int)($m[2] ?? 0)) * 604800 + ((int)($m[3] ?? 0)) * 86400 + ((int)($m[4] ?? 0)) * 3600
       + ((int)($m[5] ?? 0)) * 60 + ((int)($m[6] ?? 0));
    return ($m[1] ?? '') === '-' ? -$s : $s;
}

$text  = preg_replace("/\n[ \t]/", '', str_replace(["\r\n", "\r"], "\n", $ics));
$lines = explode("\n", $text);

$events = [];
$cur = null;
foreach ($lines as $line) {
    if ($line === 'BEGIN:VEVENT') { $cur = ['EXDATE' => []]; continue; }
    if ($line === 'END:VEVENT')   { if ($cur) $events[] = $cur; $cur = null; continue; }
    if ($cur === null) continue;
    $p = parse_prop($line);
    if (!$p) continue;
    [$name, $params, $value] = $p;
    if ($name === 'EXDATE') {
        foreach (explode(',', $value) as $v) $cur['EXDATE'][] = [$v, $params];
    } elseif (in_array($name, ['SUMMARY', 'DTSTART', 'DTEND', 'DURATION', 'RRULE', 'RECURRENCE-ID', 'STATUS', 'UID'], true)) {
        $cur[$name] = [$value, $params];
    }
}

/* ---------- Window ---------- */

$winStart = new DateTime('first day of this month 00:00:00', $TZ);
$winEnd   = (clone $winStart)->modify('+' . ($MONTHS_AHEAD + 1) . ' months')->modify('-1 second');

/* ---------- Recurring events ---------- */

function expand($start, $rule, $winEnd) {
    $r = [];
    foreach (explode(';', $rule) as $part) {
        $kv = explode('=', $part, 2);
        if (count($kv) === 2) $r[strtoupper($kv[0])] = strtoupper($kv[1]);
    }
    $freq     = $r['FREQ'] ?? '';
    $interval = max(1, (int)($r['INTERVAL'] ?? 1));
    $count    = isset($r['COUNT']) ? (int)$r['COUNT'] : PHP_INT_MAX;
    $until    = null;
    if (!empty($r['UNTIL'])) {
        $u = ics_date($r['UNTIL'], [], $start->getTimezone());
        if ($u) { $until = $u[0]; if ($u[1]) $until->setTime(23, 59, 59); }
    }
    $stop  = ($until && $until < $winEnd) ? $until : $winEnd;
    $days  = ['MO' => 1, 'TU' => 2, 'WE' => 3, 'TH' => 4, 'FR' => 5, 'SA' => 6, 'SU' => 7];
    $byday = !empty($r['BYDAY']) ? explode(',', $r['BYDAY']) : [];
    $out = [];
    $n = 0;
    $h = (int)$start->format('H'); $i = (int)$start->format('i'); $s = (int)$start->format('s');

    for ($step = 0; $step < 5000; $step++) {
        $batch = [];
        if ($freq === 'DAILY') {
            $batch[] = (clone $start)->modify('+' . ($step * $interval) . ' days');
        } elseif ($freq === 'WEEKLY') {
            $weekStart = (clone $start)->modify('-' . ((int)$start->format('N') - 1) . ' days')->modify('+' . ($step * $interval) . ' weeks');
            $list = $byday ?: [array_search((int)$start->format('N'), $days)];
            $nums = [];
            foreach ($list as $d) { $d = substr($d, -2); if (isset($days[$d])) $nums[] = $days[$d]; }
            sort($nums);
            foreach ($nums as $num) $batch[] = (clone $weekStart)->modify('+' . ($num - 1) . ' days');
        } elseif ($freq === 'MONTHLY' || $freq === 'YEARLY') {
            $months = $freq === 'MONTHLY' ? $step * $interval : $step * $interval * 12;
            $base = (clone $start)->setDate((int)$start->format('Y'), (int)$start->format('n'), 1)->modify("+$months months");
            $y = (int)$base->format('Y'); $mo = (int)$base->format('n');
            if ($byday && preg_match('/^([+-]?\d)?(MO|TU|WE|TH|FR|SA|SU)$/', $byday[0], $mm)) {
                $nth = $mm[1] === '' ? 1 : (int)$mm[1];
                $want = $days[$mm[2]];
                $cand = [];
                $dim = cal_days($y, $mo);
                for ($d = 1; $d <= $dim; $d++) {
                    if ((int)date('N', mktime(12, 0, 0, $mo, $d, $y)) === $want) $cand[] = $d;
                }
                $pick = $nth > 0 ? ($cand[$nth - 1] ?? null) : ($cand[count($cand) + $nth] ?? null);
                if ($pick) $batch[] = (clone $base)->setDate($y, $mo, $pick);
            } else {
                $dom = (int)$start->format('j');
                if (checkdate($mo, $dom, $y)) $batch[] = (clone $base)->setDate($y, $mo, $dom);
            }
        } else {
            return [$start];
        }
        foreach ($batch as $d) {
            $d->setTime($h, $i, $s);
            if ($d < $start) continue;
            if ($d > $stop || $n >= $count) return $out;
            $out[] = $d;
            $n++;
        }
        if (!$batch && $step > 2400) break;
    }
    return $out;
}

function cal_days($y, $m) { return (int)date('t', mktime(12, 0, 0, $m, 1, $y)); }

/* ---------- Build occurrences ---------- */

$overridden = [];
foreach ($events as $e) {
    if (isset($e['RECURRENCE-ID'], $e['UID'])) {
        $rid = ics_date($e['RECURRENCE-ID'][0], $e['RECURRENCE-ID'][1], $TZ);
        if ($rid) $overridden[$e['UID'][0] . '|' . $rid[0]->format('Ymd His')] = true;
    }
}

$result = [];
foreach ($events as $e) {
    if (!isset($e['DTSTART'])) continue;
    if (isset($e['STATUS']) && strtoupper(trim($e['STATUS'][0])) === 'CANCELLED') continue;
    $st = ics_date($e['DTSTART'][0], $e['DTSTART'][1], $TZ);
    if (!$st) continue;
    [$start, $allDay] = $st;

    if (isset($e['DTEND']) && ($en = ics_date($e['DTEND'][0], $e['DTEND'][1], $TZ))) {
        $len = $en[0]->getTimestamp() - $start->getTimestamp();
    } elseif (isset($e['DURATION'])) {
        $len = duration_seconds($e['DURATION'][0]);
    } else {
        $len = $allDay ? 86400 : 0;
    }
    if ($len < 0) $len = 0;

    $title = ($SHOW_TITLES && isset($e['SUMMARY'])) ? ics_text($e['SUMMARY'][0]) : '';
    if ($title === '') $title = 'Booked';

    $starts = isset($e['RRULE']) && !isset($e['RECURRENCE-ID']) ? expand($start, $e['RRULE'][0], $winEnd) : [$start];

    $ex = [];
    foreach ($e['EXDATE'] as [$v, $prm]) {
        $x = ics_date($v, $prm, $TZ);
        if ($x) { $ex[$x[0]->format('Ymd His')] = true; $ex[$x[0]->format('Ymd')] = $x[1]; }
    }
    $uid = $e['UID'][0] ?? '';

    foreach ($starts as $s) {
        $key = $s->format('Ymd His');
        if (isset($e['RRULE']) && !isset($e['RECURRENCE-ID'])) {
            if (isset($ex[$key]) || (!empty($ex[$s->format('Ymd')]))) continue;
            if ($uid !== '' && isset($overridden[$uid . '|' . $key])) continue;
        }
        $end = (clone $s)->modify('+' . $len . ' seconds');
        if ($allDay) {
            $lastDay = (clone $end)->modify('-1 day');
            if ($lastDay < $s) $lastDay = clone $s;
            $time = null;
        } else {
            $lastDay = $len > 0 ? (clone $end)->modify('-1 second') : clone $s;
            $time = $len > 0 ? $s->format('g:i a') . ' – ' . $end->format('g:i a') : $s->format('g:i a');
        }
        if ($lastDay < $winStart || $s > $winEnd) continue;
        $result[] = [
            'start' => $s->format('Y-m-d'),
            'end'   => $lastDay->format('Y-m-d'),
            'time'  => $time,
            'title' => $title,
        ];
    }
}

usort($result, function ($a, $b) { return strcmp($a['start'] . ($a['time'] ?? ''), $b['start'] . ($b['time'] ?? '')); });

out([
    'ok'      => true,
    'updated' => date(DATE_ATOM, $updated),
    'stale'   => $stale,
    'from'    => $winStart->format('Y-m-d'),
    'until'   => $winEnd->format('Y-m-d'),
    'events'  => $result,
]);
