<?php
/**
 * Sync Casino Game Images — Downloads thumbnails to local storage.
 *
 * Usage:  php scripts/sync_casino_images.php [--force]
 *
 * Options:
 *   --force   Re-download all images even if they already exist locally.
 */
require_once dirname(__DIR__) . '/bootstrap.php';

use App\Casino\CasinoManager;

$casino = new CasinoManager($config);
$force  = in_array('--force', $argv ?? [], true);

echo "=== Casino Image Sync ===" . PHP_EOL;

// Create target directory
$imgDir = dirname(__DIR__) . '/public/assets/images/games';
if (!is_dir($imgDir)) {
    mkdir($imgDir, 0755, true);
    echo "Created directory: {$imgDir}" . PHP_EOL;
}

// Get games that need image download
if ($force) {
    $pdo   = \App\Core\Database::getInstance($config['db'])->getPdo();
    $games = $pdo->query('SELECT game_id, thumbnail_url FROM casino_games WHERE thumbnail_url IS NOT NULL AND is_active = 1')->fetchAll();
} else {
    $games = $casino->getGamesNeedingImages();
}

$total      = count($games);
$downloaded = 0;
$failed     = 0;
$skipped    = 0;

echo "Games to process: {$total}" . PHP_EOL;

foreach ($games as $i => $game) {
    $num = $i + 1;
    $gameId = $game['game_id'];
    $url    = $game['thumbnail_url'];

    echo "[{$num}/{$total}] {$gameId} ... ";

    if (!$url) {
        echo "SKIP (no URL)" . PHP_EOL;
        $skipped++;
        continue;
    }

    $result = $casino->downloadGameImage($gameId, $url);

    if ($result) {
        echo "OK -> {$result}" . PHP_EOL;
        $downloaded++;
    } else {
        echo "FAILED" . PHP_EOL;
        $failed++;
    }
}

echo PHP_EOL;
echo "=== Summary ===" . PHP_EOL;
echo "Downloaded: {$downloaded}" . PHP_EOL;
echo "Failed:     {$failed}" . PHP_EOL;
echo "Skipped:    {$skipped}" . PHP_EOL;
echo "Done!" . PHP_EOL;
