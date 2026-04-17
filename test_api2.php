<?php
$base = 'https://sb2frontend-altenar2.biahostod.com/api/Sportsbook';
$params = 'timezoneOffset=-60&langId=8&skinName=altenar2&configId=12&culture=en-GB&countryCode=NG&deviceType=Desktop&numformat=en';

function fetchApi($url) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)'],
        CURLOPT_TIMEOUT => 20,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $resp];
}

// GetEvents with various param combos
$combos = [
    'sportIds=1&cnt=2&index=0',
    'sportIds=1&champIds=0&cnt=2&index=0',
    'sportIds=1&leagueIds=0&cnt=2&index=0',
    'sportIds=1&champIds=0&leagueIds=0&cnt=2&index=0',
    'sportId=1&cnt=2&index=0',
];

foreach ($combos as $extra) {
    $url = "$base/GetEvents?$params&$extra";
    $r = fetchApi($url);
    echo "GetEvents($extra) HTTP {$r['code']} len=" . strlen($r['body']) . "\n";
    $data = json_decode($r['body'], true);
    if ($data) echo "  Response: " . substr(json_encode($data), 0, 300) . "\n\n";
}

// Also try the working GetAllSports to confirm it still works
$r = fetchApi("$base/GetAllSports?$params&sportIds=0&regionIds=0&index=0&cnt=3&sortBy=1");
echo "\nGetAllSports HTTP {$r['code']} len=" . strlen($r['body']) . "\n";
$data = json_decode($r['body'], true);
if ($data && isset($data['Result'])) {
    echo "  Sports count: " . count($data['Result']) . "\n";
    echo "  First: " . json_encode($data['Result'][0]) . "\n";
}

// Try to get events/competitions nested under a sport
echo "\n=== Trying sport tree navigation ===\n";
$sportId = 66; // Football sport type ID
$r = fetchApi("$base/GetEvents?$params&sportIds=$sportId&cnt=3&index=0&champIds=0&leagueIds=0");
echo "GetEvents(sport=66) HTTP {$r['code']} len=" . strlen($r['body']) . "\n";
if (strlen($r['body']) > 50) echo "  " . substr($r['body'], 0, 400) . "\n";

// Try with configId variation
$r = fetchApi("$base/GetEvents?timezoneOffset=-60&langId=8&skinName=altenar2&configId=12&culture=en-GB&countryCode=NG&deviceType=Desktop&numformat=en&sportIds=66&champIds=0&leagueIds=0&index=0&cnt=3&sortBy=1&flags=1");
echo "\nGetEvents(flags=1) HTTP {$r['code']} len=" . strlen($r['body']) . "\n";
if (strlen($r['body']) > 50) echo "  " . substr($r['body'], 0, 400) . "\n";
