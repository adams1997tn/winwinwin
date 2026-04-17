<?php
/**
 * Cron: Bet Settlement
 * Run every 10-15 minutes via crontab.
 *
 * Resolves finished matches and settles pending bets.
 */

require_once dirname(__DIR__) . '/bootstrap.php';

use App\Core\Database;
use App\Core\Logger;
use App\Settlement\SettlementEngine;

$logger = new Logger(BASE_PATH . '/logs');
$logger->info('=== Settlement cron started ===');

try {
    $db     = Database::getInstance($config['db'])->getPdo();
    $engine = new SettlementEngine($db, $config);
    $result = $engine->run();

    $logger->info(sprintf(
        'Settlement done. Matches resolved: %d, Bets settled: %d (Won: %d, Lost: %d), Payouts: €%.2f, Time: %dms',
        $result['matches_resolved'],
        $result['bets_settled'],
        $result['bets_won'],
        $result['bets_lost'],
        $result['total_payouts'],
        $result['execution_time_ms']
    ));

    if (!empty($result['errors'])) {
        foreach ($result['errors'] as $err) {
            $logger->error('Settlement error: ' . $err);
        }
    }

} catch (\Throwable $e) {
    $logger->error('Settlement cron failed: ' . $e->getMessage());
}

$logger->info('=== Settlement cron completed ===');
