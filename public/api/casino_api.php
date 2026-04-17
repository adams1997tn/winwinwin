<?php
/**
 * Casino API — Frontend endpoints
 *
 * GET  ?action=lobby           → Fetch game list from Bet4Wins (or demo catalog)
 * POST ?action=launch          → Get game launch URL
 * GET  ?action=favorites       → Get user's favorite games
 * POST ?action=toggle_favorite → Add/remove a favorite
 */
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 2) . '/bootstrap.php';

use App\Casino\CasinoManager;

$action = $_GET['action'] ?? '';

try {
    $casino = new CasinoManager($config);

    switch ($action) {

        case 'lobby':
            // Try Bet4Wins API first (skip in dev mode)
            $games = null;
            if (!$casino->isDevMode()) {
                $games = $casino->fetchGameLobby();
            }

            // Fall back to local DB catalog
            if ($games === null) {
                $games = $casino->getGamesFromDB();
            }

            // Last resort: seed demo catalog on first run
            if (empty($games) && $casino->getGameCount() === 0) {
                $games = getDemoCatalog();
            }

            // Add local placeholder path for games without thumbnails
            $placeholder = 'assets/images/games/placeholder.svg';
            foreach ($games as &$g) {
                if (empty($g['thumbnail'])) {
                    $g['thumbnail'] = $placeholder;
                }
            }
            unset($g);

            echo json_encode([
                'success'  => true,
                'games'    => $games,
                'dev_mode' => $casino->isDevMode(),
            ]);
            break;

        case 'launch':
            if (empty($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Please log in to play']);
                exit;
            }

            $input    = json_decode(file_get_contents('php://input'), true);
            $gameId   = trim($input['game_id'] ?? '');
            $demoMode = (bool) ($input['demo'] ?? false);

            if (!$gameId) {
                echo json_encode(['success' => false, 'error' => 'game_id is required']);
                exit;
            }

            // Validate game exists in our catalog
            $stmt = $casino->getDb()->prepare('SELECT game_id FROM casino_games WHERE game_id = ? AND is_active = 1');
            $stmt->execute([$gameId]);
            if (!$stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Game not found or unavailable']);
                exit;
            }

            // DEV MODE: return mock/demo URL directly
            if ($casino->isDevMode() || $demoMode) {
                $mock = $casino->getMockLaunchResponse($gameId, (int) $_SESSION['user_id']);
                echo json_encode([
                    'success'  => true,
                    'game_url' => $mock['game_url'],
                    'mode'     => 'demo',
                ]);
                break;
            }

            // PRODUCTION: call real API
            $isMobile = (bool) ($input['mobile'] ?? false);
            $gameUrl  = $casino->getGameLaunchUrl(
                (int) $_SESSION['user_id'],
                $_SESSION['username'],
                $gameId,
                $isMobile
            );

            // Validate URL before sending to frontend
            if ($gameUrl && filter_var($gameUrl, FILTER_VALIDATE_URL)) {
                echo json_encode(['success' => true, 'game_url' => $gameUrl, 'mode' => 'real']);
            } else {
                echo json_encode([
                    'success' => false,
                    'error'   => 'Game currently unavailable. Please try again later.',
                ]);
            }
            break;

        case 'favorites':
            if (empty($_SESSION['user_id'])) {
                echo json_encode(['success' => true, 'favorites' => []]);
                exit;
            }
            $favs = $casino->getFavorites((int) $_SESSION['user_id']);
            echo json_encode(['success' => true, 'favorites' => $favs]);
            break;

        case 'toggle_favorite':
            if (empty($_SESSION['user_id'])) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Please log in']);
                exit;
            }

            $input     = json_decode(file_get_contents('php://input'), true);
            $gameId    = $input['game_id'] ?? '';
            $gameName  = $input['game_name'] ?? '';
            $provider  = $input['provider'] ?? null;
            $thumbnail = $input['thumbnail'] ?? null;

            if (!$gameId || !$gameName) {
                echo json_encode(['success' => false, 'error' => 'game_id and game_name are required']);
                exit;
            }

            $result = $casino->toggleFavorite((int) $_SESSION['user_id'], $gameId, $gameName, $provider, $thumbnail);
            echo json_encode(['success' => true, 'result' => $result]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Unknown action']);
    }

} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}

/* ═══════════════════════════════════════════════
   DEMO GAME CATALOG
   Provides a rich game library for development/testing
   ═══════════════════════════════════════════════ */
function getDemoCatalog(): array
{
    $slotGames = [
        // Pragmatic Play
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
        // NetEnt
        ['Starburst', 'NetEnt'],
        ['Gonzo\'s Quest', 'NetEnt'],
        ['Twin Spin', 'NetEnt'],
        ['Dead or Alive 2', 'NetEnt'],
        ['Divine Fortune', 'NetEnt'],
        ['Narcos', 'NetEnt'],
        // Play'n GO
        ['Book of Dead', 'Play\'n GO'],
        ['Reactoonz 2', 'Play\'n GO'],
        ['Fire Joker', 'Play\'n GO'],
        ['Moon Princess', 'Play\'n GO'],
        ['Rise of Olympus', 'Play\'n GO'],
        ['Legacy of Dead', 'Play\'n GO'],
        ['Rich Wilde and the Tome of Madness', 'Play\'n GO'],
        ['Gemix', 'Play\'n GO'],
        // Microgaming
        ['Immortal Romance', 'Microgaming'],
        ['Thunderstruck II', 'Microgaming'],
        ['Mega Moolah', 'Microgaming'],
        ['Break da Bank Again', 'Microgaming'],
        ['Avalon II', 'Microgaming'],
        ['Game of Thrones', 'Microgaming'],
        // Red Tiger
        ['Gonzo\'s Quest Megaways', 'Red Tiger'],
        ['Piggy Riches Megaways', 'Red Tiger'],
        ['Dragons Fire Megaways', 'Red Tiger'],
        ['Mystery Reels', 'Red Tiger'],
        ['Valhalla Gold', 'Red Tiger'],
        // Hacksaw Gaming
        ['Wanted Dead or a Wild', 'Hacksaw Gaming'],
        ['Chaos Crew', 'Hacksaw Gaming'],
        ['Itero', 'Hacksaw Gaming'],
        ['Hand of Anubis', 'Hacksaw Gaming'],
        ['Le Bandit', 'Hacksaw Gaming'],
        // Push Gaming
        ['Razor Shark', 'Push Gaming'],
        ['Jammin\' Jars 2', 'Push Gaming'],
        ['Fat Rabbit', 'Push Gaming'],
        ['Big Bamboo', 'Push Gaming'],
        ['Joker Troupe', 'Push Gaming'],
        // Nolimit City
        ['Mental', 'Nolimit City'],
        ['San Quentin', 'Nolimit City'],
        ['Tombstone RIP', 'Nolimit City'],
        ['Misery Mining', 'Nolimit City'],
        ['Fire in the Hole', 'Nolimit City'],
        ['Punk Rocker', 'Nolimit City'],
        // Relax Gaming
        ['Money Train 3', 'Relax Gaming'],
        ['Temple Tumble 2', 'Relax Gaming'],
        ['Dream Drop Jackpot', 'Relax Gaming'],
        ['TNT Tumble', 'Relax Gaming'],
        ['Snake Arena', 'Relax Gaming'],
        // ELK Studios
        ['Kaiju', 'ELK Studios'],
        ['Wild Toro', 'ELK Studios'],
        ['Cygnus', 'ELK Studios'],
        ['Ecuador Gold', 'ELK Studios'],
        // Thunderkick
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
        'Mega Moolah', 'Starburst', 'Monopoly Live', 'Starlight Princess'
    ];

    $games = [];
    $id    = 1;

    // Build slots
    foreach ($slotGames as $idx => [$name, $provider]) {
        $games[] = buildGame($id++, $name, $provider, 'slots', $idx < 10, $hotGames);
    }

    // Build live
    foreach ($liveGames as $idx => [$name, $provider]) {
        $games[] = buildGame($id++, $name, $provider, 'live', $idx < 5, $hotGames);
    }

    // Build table
    foreach ($tableGames as $idx => [$name, $provider]) {
        $games[] = buildGame($id++, $name, $provider, 'table', $idx < 3, $hotGames);
    }

    // Build virtual
    foreach ($virtualGames as $idx => [$name, $provider]) {
        $games[] = buildGame($id++, $name, $provider, 'virtual', $idx < 3, $hotGames);
    }

    // Shuffle for variety
    shuffle($games);

    // Re-index IDs after shuffle
    foreach ($games as $i => &$g) {
        $g['id'] = 'game_' . ($i + 1);
    }

    return $games;
}

function buildGame(int $id, string $name, string $provider, string $category, bool $isNew, array $hotGames): array
{
    // Different thumbnail colors per category
    $colors = [
        'slots'   => ['1a1f2b', 'f0b90b'],
        'live'    => ['0f2922', '22c55e'],
        'table'   => ['1a1028', '7c3aed'],
        'virtual' => ['1a1028', '3b82f6'],
    ];
    $c = $colors[$category] ?? $colors['slots'];

    return [
        'id'        => 'game_' . $id,
        'name'      => $name,
        'provider'  => $provider,
        'category'  => $category,
        'thumbnail' => "https://placehold.co/400x280/{$c[0]}/{$c[1]}?text=" . urlencode($name),
        'has_demo'  => $category !== 'live',
        'is_new'    => $isNew,
        'is_hot'    => in_array($name, $hotGames, true),
    ];
}
