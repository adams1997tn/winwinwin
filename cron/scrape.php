<?php
/**
 * Cron Runner - Execute via:
 *   php cron/scrape.php
 *
 * Crontab entry (every 5 minutes):
 *   *​/5 * * * * /usr/bin/php /path/to/bet/cron/scrape.php >> /path/to/bet/logs/cron.log 2>&1
 *
 * On Windows (Task Scheduler or Laragon cron):
 *   *​/5 * * * * C:\laragon\bin\php\php-8.2\php.exe C:\laragon\www\bet\cron\scrape.php
 */

define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/src/Core/Database.php';
require_once BASE_PATH . '/src/Core/Logger.php';
require_once BASE_PATH . '/src/Scraper/OddsScraper.php';

use App\Core\Database;
use App\Core\Logger;
use App\Scraper\OddsScraper;

// Load config
$config = require BASE_PATH . '/config/config.php';

// Init logger
$logger = new Logger(BASE_PATH . '/logs');

$logger->info('=== Cron scrape started ===');

try {
    // Init DB singleton
    Database::getInstance($config['db']);

    // Run scraper
    $scraper = new OddsScraper($config, $logger);
    $scraper->scrapeAll();

    $logger->info('=== Cron scrape completed ===');
} catch (\Throwable $e) {
    $logger->error('Fatal: ' . $e->getMessage());
    echo 'FATAL: ' . $e->getMessage() . PHP_EOL;
    exit(1);
}
