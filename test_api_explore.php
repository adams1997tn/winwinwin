<?php
$base = 'https://sb2frontend-altenar2.biahostod.com/api/Sportsbook';
$params = 'timezoneOffset=-60&langId=8&skinName=altenar2&configId=12&culture=en-GB&countryCode=NG&deviceType=Desktop&numformat=en';

$endpoints = [
    'GetByLeague' => '&leagueId=18&index=0&cnt=3',
    'GetByDate' => '&sportIds=66&champIds=0&index=0&cnt=3',
    'GetTopLeagues' => '&sportId=66',
    'GetCompetitions' => '&sportId=66',
    'GetLeagues' => '&sportId=1',
    'GetSportLeagues' => '&sportId=1',
    'GetByCompetition' => '&competitionId=18&index=0&cnt=3',
    'GetEvents' => '&sportIds=66&leagueIds=0&index=0&cnt=3',
    'GetSportEvents' => '&sportId=1&index=0&cnt=3',
];

foreach ($endpoints as $name => $extra) {
    $url = "$base/$name?$params$extra";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)'],
        CURLOPT_TIMEOUT => 15,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $isJson = str_starts_with(trim($resp), '{') || str_starts_with(trim($resp), '[');
    $len = strlen($resp);
    echo "$name (HTTP $code) len=$len json=" . ($isJson ? 'YES' : 'NO') . "\n";
    if ($isJson && $len > 50) {
        echo '  ' . substr($resp, 0, 300) . "\n\n";
    }
}
