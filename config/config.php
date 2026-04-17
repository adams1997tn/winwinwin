<?php
/**
 * Application Configuration
 */
return [
    // ─── Development Mode ────────────────────────────
    // Set to true for localhost / offline testing.
    // Demo games launch without API calls, callbacks are mocked.
    // Set to false ONLY when you have real Bet4Wins credentials.
    'dev_mode'  => true,

    // Base URL of the application. Used to build callback URLs dynamically.
    // Using LocalTunnel:  lt --port 80
    // Update this each time you get a new tunnel URL.
    'base_url'  => 'http://localhost/bet/public',

    'db' => [
        'host'     => '127.0.0.1',
        'port'     => 3306,
        'dbname'   => 'sportsbook',
        'username' => 'root',
        'password' => '',
        'charset'  => 'utf8mb4',
    ],

    'api' => [
        'base_url'     => 'https://sf-betting-api.sft-spb.com/prematch',
        'query_string' => 'json&l=fr&public=fzs_EUR',
        'endpoints' => [
            'main'       => 'https://sf-betting-api.sft-spb.com/prematch/main?json&l=fr&public=fzs_EUR',
        ],
        'headers' => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'Accept: application/json, text/plain, */*',
            'Accept-Language: fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7',
            'Referer: https://www.example-betting.com/',
            'Origin: https://www.example-betting.com',
        ],
        'timeout'  => 30,
    ],

    'betting' => [
        'margin' => 0.05, // 5% margin reduction on API odds
    ],

    'casino' => [
        'secret_key'   => '6cdf05aaf2688ba4712bc980f8f5c019', // Provided by Bet4Wins
        'api_base'     => 'https://api.bet4wins.com/v1',   // Bet4Wins API base URL
        'operator_id'  => 'd63d25bccee97df2b68aae075b327365',
        'api_key'      => '654b178d4af1690b26617d8ad87a5124',
        'currency'     => 'EUR',
        'lobby_url'    => 'https://api.bet4wins.com/v1/games',
        'launch_url'   => 'https://api.bet4wins.com/v1/game/launch',
        // callback_url is now built dynamically from base_url (see CasinoManager)
        'callback_url' => null,
        'live_providers' => ['Evolution Gaming', 'Pragmatic Live', 'Ezugi', 'Vivo Gaming', 'SA Gaming'],
    ],
];
