<?php
/**
 * Sport2 Configuration
 */
return [
    'db' => [
        'host'    => '127.0.0.1',
        'dbname'  => 'sport2',
        'user'    => 'root',
        'pass'    => '',
        'charset' => 'utf8mb4',
    ],

    'altenar' => [
        'base_url'    => 'https://sb2frontend-altenar2.biahostod.com/api/Sportsbook',
        'params'      => [
            'timezoneOffset' => '-60',
            'langId'         => '8',
            'skinName'       => 'altenar2',
            'configId'       => '12',
            'culture'        => 'en-GB',
            'countryCode'    => 'NG',
            'deviceType'     => 'Desktop',
            'numformat'      => 'en',
        ],
        'timeout'     => 30,
        'retry_count' => 3,
        'retry_delay' => 2,  // seconds
        'batch_size'  => 50, // events per request
    ],

    'sync' => [
        'lock_file'        => __DIR__ . '/../logs/sync.lock',
        'log_file'         => __DIR__ . '/../logs/sync.log',
        'stale_hours'      => 24,     // remove events older than this
        'max_duration'     => 300,    // max sync run in seconds
    ],

    'app' => [
        'name'     => 'Sport2',
        'timezone' => 'Europe/Berlin',
        'debug'    => true,
    ],
];
