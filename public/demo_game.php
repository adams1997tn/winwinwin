<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demo Game</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', Arial, sans-serif;
            background: linear-gradient(135deg, #0d0f14 0%, #161a24 50%, #1a1f2b 100%);
            color: #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            overflow: hidden;
        }
        .demo-container {
            text-align: center;
            max-width: 500px;
            padding: 40px;
        }
        .demo-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(240, 185, 11, 0.12);
            border: 1px solid rgba(240, 185, 11, 0.3);
            color: #f0b90b;
            padding: 6px 18px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 30px;
        }
        .demo-badge .dot {
            width: 8px; height: 8px;
            background: #f0b90b;
            border-radius: 50%;
            animation: pulse 1.5s ease-in-out infinite;
        }
        @keyframes pulse { 0%,100% { opacity:1; } 50% { opacity:0.3; } }
        .demo-icon {
            width: 80px; height: 80px;
            background: linear-gradient(135deg, #f0b90b, #fcd34d);
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 24px;
            font-size: 36px;
        }
        .game-name {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 8px;
            color: #f8fafc;
        }
        .game-meta {
            font-size: 0.85rem;
            color: #94a3b8;
            margin-bottom: 30px;
        }
        .demo-info {
            background: rgba(30, 41, 59, 0.5);
            border: 1px solid rgba(51, 65, 85, 0.5);
            border-radius: 12px;
            padding: 20px;
            font-size: 0.85rem;
            color: #94a3b8;
            line-height: 1.6;
        }
        .demo-info strong { color: #f0b90b; }
        .demo-slots {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin: 30px 0;
        }
        .slot-reel {
            width: 70px; height: 70px;
            background: #1e293b;
            border: 2px solid #334155;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            animation: slotSpin 2s ease-in-out infinite;
        }
        .slot-reel:nth-child(2) { animation-delay: 0.3s; }
        .slot-reel:nth-child(3) { animation-delay: 0.6s; }
        @keyframes slotSpin {
            0%,80%,100% { transform: translateY(0); }
            40% { transform: translateY(-8px); }
        }
    </style>
</head>
<body>
<?php
    $gameId  = htmlspecialchars($_GET['game_id'] ?? 'unknown', ENT_QUOTES, 'UTF-8');
    $player  = (int) ($_GET['player'] ?? 0);
    $mode    = htmlspecialchars($_GET['mode'] ?? 'demo', ENT_QUOTES, 'UTF-8');

    // Lookup game name from DB if possible
    $gameName = str_replace('_', ' ', preg_replace('/^(slots|live|table|virtual)_game_/', '', $gameId));
    $gameName = ucwords($gameName);

    try {
        require_once dirname(__DIR__) . '/bootstrap.php';
        $pdo  = App\Core\Database::getInstance($config['db'])->getPdo();
        $stmt = $pdo->prepare('SELECT name, provider, category FROM casino_games WHERE game_id = ?');
        $stmt->execute([$gameId]);
        $row  = $stmt->fetch();
        if ($row) {
            $gameName = htmlspecialchars($row['name'], ENT_QUOTES, 'UTF-8');
            $provider = htmlspecialchars($row['provider'], ENT_QUOTES, 'UTF-8');
            $category = $row['category'];
        }
    } catch (Exception $e) {
        // Silent fail — just use the derived name
    }

    $emojis = ['🍒', '💎', '7️⃣', '🎰', '⭐', '🃏', '🎲', '👑'];
    shuffle($emojis);
?>
    <div class="demo-container">
        <div class="demo-badge">
            <span class="dot"></span> Demo Mode
        </div>

        <div class="demo-icon">🎰</div>

        <div class="game-name"><?= $gameName ?></div>
        <div class="game-meta">
            <?= $provider ?? 'Casino Provider' ?> &bull; <?= ucfirst($category ?? 'slots') ?>
            &bull; Player #<?= $player ?>
        </div>

        <div class="demo-slots">
            <div class="slot-reel"><?= $emojis[0] ?></div>
            <div class="slot-reel"><?= $emojis[1] ?></div>
            <div class="slot-reel"><?= $emojis[2] ?></div>
        </div>

        <div class="demo-info">
            <strong>Development Mode</strong><br>
            This is a demo placeholder. The real game will load here when
            connected to the Bet4Wins API on a live server.<br><br>
            Game ID: <code><?= $gameId ?></code><br>
            Token: <code><?= htmlspecialchars($_GET['token'] ?? '', ENT_QUOTES, 'UTF-8') ?></code>
        </div>
    </div>
</body>
</html>
