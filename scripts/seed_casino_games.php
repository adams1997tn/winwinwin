<?php
/**
 * Seed the casino_games table with the demo game catalog.
 *
 * Usage:  php scripts/seed_casino_games.php
 */
require_once dirname(__DIR__) . '/bootstrap.php';

use App\Casino\CasinoManager;

$casino = new CasinoManager($config);

echo "=== Casino Games Seeder ===" . PHP_EOL;

$existing = $casino->getGameCount();
echo "Existing games in DB: {$existing}" . PHP_EOL;

// ── Demo catalog data ──────────────────────────────────
$slotGames = [
    ['Sweet Bonanza', 'Pragmatic Play'],
    ['Gates of Olympus', 'Pragmatic Play'],
    ['Sugar Rush', 'Pragmatic Play'],
    ['The Dog House Megaways', 'Pragmatic Play'],
    ['Big Bass Bonanza', 'Pragmatic Play'],
    ['Starlight Princess', 'Pragmatic Play'],
    ['Fruit Party', 'Pragmatic Play'],
    ['Wolf Gold', 'Pragmatic Play'],
    ['Gates of Olympus 1000', 'Pragmatic Play'],
    ['Sweet Bonanza 1000', 'Pragmatic Play'],
    ['Floating Dragon', 'Pragmatic Play'],
    ['Zeus vs Hades', 'Pragmatic Play'],
    ['Starburst', 'NetEnt'],
    ['Gonzo\'s Quest', 'NetEnt'],
    ['Twin Spin', 'NetEnt'],
    ['Dead or Alive 2', 'NetEnt'],
    ['Divine Fortune', 'NetEnt'],
    ['Narcos', 'NetEnt'],
    ['Book of Dead', 'Play\'n GO'],
    ['Reactoonz 2', 'Play\'n GO'],
    ['Fire Joker', 'Play\'n GO'],
    ['Moon Princess', 'Play\'n GO'],
    ['Rise of Olympus', 'Play\'n GO'],
    ['Legacy of Dead', 'Play\'n GO'],
    ['Rich Wilde and the Tome of Madness', 'Play\'n GO'],
    ['Gemix', 'Play\'n GO'],
    ['Immortal Romance', 'Microgaming'],
    ['Thunderstruck II', 'Microgaming'],
    ['Mega Moolah', 'Microgaming'],
    ['Break da Bank Again', 'Microgaming'],
    ['Avalon II', 'Microgaming'],
    ['Game of Thrones', 'Microgaming'],
    ['Gonzo\'s Quest Megaways', 'Red Tiger'],
    ['Piggy Riches Megaways', 'Red Tiger'],
    ['Dragons Fire Megaways', 'Red Tiger'],
    ['Mystery Reels', 'Red Tiger'],
    ['Valhalla Gold', 'Red Tiger'],
    ['Wanted Dead or a Wild', 'Hacksaw Gaming'],
    ['Chaos Crew', 'Hacksaw Gaming'],
    ['Itero', 'Hacksaw Gaming'],
    ['Hand of Anubis', 'Hacksaw Gaming'],
    ['Le Bandit', 'Hacksaw Gaming'],
    ['Razor Shark', 'Push Gaming'],
    ['Jammin\' Jars 2', 'Push Gaming'],
    ['Fat Rabbit', 'Push Gaming'],
    ['Big Bamboo', 'Push Gaming'],
    ['Joker Troupe', 'Push Gaming'],
    ['Mental', 'Nolimit City'],
    ['San Quentin', 'Nolimit City'],
    ['Tombstone RIP', 'Nolimit City'],
    ['Misery Mining', 'Nolimit City'],
    ['Fire in the Hole', 'Nolimit City'],
    ['Punk Rocker', 'Nolimit City'],
    ['Money Train 3', 'Relax Gaming'],
    ['Temple Tumble 2', 'Relax Gaming'],
    ['Dream Drop Jackpot', 'Relax Gaming'],
    ['TNT Tumble', 'Relax Gaming'],
    ['Snake Arena', 'Relax Gaming'],
    ['Kaiju', 'ELK Studios'],
    ['Wild Toro', 'ELK Studios'],
    ['Cygnus', 'ELK Studios'],
    ['Ecuador Gold', 'ELK Studios'],
    ['Flame Busters', 'Thunderkick'],
    ['1429 Uncharted Seas', 'Thunderkick'],
    ['Esqueleto Explosivo 2', 'Thunderkick'],
    ['Beat the Beast: Griffin', 'Thunderkick'],
];

$liveGames = [
    ['Lightning Roulette', 'Evolution Gaming'],
    ['Crazy Time', 'Evolution Gaming'],
    ['Mega Ball', 'Evolution Gaming'],
    ['Dream Catcher', 'Evolution Gaming'],
    ['XXXtreme Lightning Roulette', 'Evolution Gaming'],
    ['Gold Bar Roulette', 'Evolution Gaming'],
    ['Immersive Roulette', 'Evolution Gaming'],
    ['Quantum Blackjack', 'Evolution Gaming'],
    ['Free Bet Blackjack', 'Evolution Gaming'],
    ['Infinite Blackjack', 'Evolution Gaming'],
    ['Lightning Blackjack', 'Evolution Gaming'],
    ['Sweet Bonanza CandyLand', 'Evolution Gaming'],
    ['Monopoly Live', 'Evolution Gaming'],
    ['Gonzo\'s Treasure Hunt', 'Evolution Gaming'],
    ['Cash or Crash', 'Evolution Gaming'],
    ['Blackjack VIP', 'Pragmatic Live'],
    ['Speed Baccarat', 'Pragmatic Live'],
    ['Mega Roulette', 'Pragmatic Live'],
    ['PowerUp Roulette', 'Pragmatic Live'],
    ['Boom City', 'Pragmatic Live'],
    ['Dragon Tiger', 'Ezugi'],
    ['Casino Hold\'em', 'Ezugi'],
    ['Live Monopoly', 'Ezugi'],
    ['Speed Roulette', 'Ezugi'],
    ['Andar Bahar', 'Ezugi'],
];

$tableGames = [
    ['European Roulette', 'NetEnt'],
    ['American Roulette', 'Microgaming'],
    ['French Roulette', 'Play\'n GO'],
    ['Classic Blackjack', 'Microgaming'],
    ['Multihand Blackjack', 'Play\'n GO'],
    ['Blackjack Switch', 'NetEnt'],
    ['Baccarat Pro', 'NetEnt'],
    ['Punto Banco', 'Microgaming'],
    ['Casino Hold\'em', 'Play\'n GO'],
    ['Three Card Poker', 'Microgaming'],
    ['Pai Gow Poker', 'NetEnt'],
    ['Caribbean Stud', 'Play\'n GO'],
    ['Texas Hold\'em Bonus', 'Microgaming'],
    ['Craps', 'NetEnt'],
    ['Red Dog', 'Microgaming'],
];

$virtualGames = [
    ['Virtual Football League', 'Kiron Interactive'],
    ['Virtual Horse Racing', 'Kiron Interactive'],
    ['Virtual Greyhounds', 'Kiron Interactive'],
    ['Virtual Tennis Open', 'Kiron Interactive'],
    ['Virtual Basketball', 'Kiron Interactive'],
    ['Virtual Motor Racing', 'Betradar'],
    ['Virtual Cricket', 'Betradar'],
    ['Virtual Baseball', 'Betradar'],
    ['Virtual Soccer Cup', 'Golden Race'],
    ['Virtual Cycling', 'Golden Race'],
    ['Virtual Speedway', 'Golden Race'],
];

$hotGames = [
    'Sweet Bonanza', 'Gates of Olympus', 'Crazy Time', 'Lightning Roulette',
    'Book of Dead', 'Big Bass Bonanza', 'Wanted Dead or a Wild', 'Money Train 3',
    'Mega Moolah', 'Starburst', 'Monopoly Live', 'Starlight Princess',
];

// Thumbnail color scheme per category
$colors = [
    'slots'   => ['1a1f2b', 'f0b90b'],
    'live'    => ['0f2922', '22c55e'],
    'table'   => ['1a1028', '7c3aed'],
    'virtual' => ['1a1028', '3b82f6'],
];

$sortOrder = 0;
$upserted  = 0;

$datasets = [
    ['slots',   $slotGames,   10],
    ['live',    $liveGames,    5],
    ['table',   $tableGames,   3],
    ['virtual', $virtualGames, 3],
];

foreach ($datasets as [$category, $games, $newThreshold]) {
    $c = $colors[$category];
    foreach ($games as $idx => [$name, $provider]) {
        $gameId = 'game_' . str_replace(' ', '_', strtolower(preg_replace('/[^a-zA-Z0-9 ]/', '', $name)));

        // Ensure unique game_id within category
        $gameId = $category . '_' . $gameId;

        $casino->upsertGame([
            'id'            => $gameId,
            'name'          => $name,
            'provider'      => $provider,
            'category'      => $category,
            'thumbnail_url' => "https://placehold.co/400x280/{$c[0]}/{$c[1]}?text=" . urlencode($name),
            'has_demo'      => $category !== 'live',
            'is_new'        => $idx < $newThreshold,
            'is_hot'        => in_array($name, $hotGames, true),
            'sort_order'    => ++$sortOrder,
        ]);
        $upserted++;
    }
}

$total = $casino->getGameCount();
echo "Upserted: {$upserted} games" . PHP_EOL;
echo "Total games in DB: {$total}" . PHP_EOL;
echo "Done!" . PHP_EOL;
