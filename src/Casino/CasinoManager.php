<?php

namespace App\Casino;

use App\Core\Database;
use PDO;

class CasinoManager
{
    private PDO $db;
    private array $config;
    private bool $devMode;
    private string $baseUrl;

    public function __construct(array $appConfig)
    {
        $this->db      = Database::getInstance($appConfig['db'])->getPdo();
        $this->config  = $appConfig['casino'];
        $this->devMode = !empty($appConfig['dev_mode']);
        $this->baseUrl = rtrim($appConfig['base_url'] ?? 'http://localhost', '/');

        // Build callback_url dynamically from base_url
        if (empty($this->config['callback_url'])) {
            $this->config['callback_url'] = $this->baseUrl . '/api/casino_callback.php';
        }
    }

    /**
     * Check if running in development / demo mode.
     */
    public function isDevMode(): bool
    {
        return $this->devMode;
    }

    /**
     * Expose PDO for direct queries in the API layer.
     */
    public function getDb(): PDO
    {
        return $this->db;
    }

    // ───────────────────────────────────────────────────
    // CALLBACK ACTIONS (called by Bet4Wins)
    // ───────────────────────────────────────────────────

    /**
     * Return player's current balance.
     */
    public function getBalance(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT balance FROM users WHERE id = ?');
        $stmt->execute([$userId]);
        $row = $stmt->fetch();

        if (!$row) {
            return ['status' => 'error', 'error_code' => 'PLAYER_NOT_FOUND', 'message' => 'Player not found'];
        }

        return [
            'status'   => 'ok',
            'balance'  => (float) $row['balance'],
            'currency' => $this->config['currency'],
        ];
    }

    /**
     * Debit (bet): deduct amount from player's balance.
     */
    public function debit(int $userId, string $transactionId, float $amount, ?string $roundId, ?string $gameId, ?array $rawRequest): array
    {
        // Idempotency: check if this transaction was already processed
        $existing = $this->findTransaction($transactionId);
        if ($existing) {
            if ($existing['status'] === 'success') {
                return [
                    'status'   => 'ok',
                    'balance'  => (float) $existing['balance_after'],
                    'currency' => $this->config['currency'],
                    'transaction_id' => $transactionId,
                ];
            }
            return ['status' => 'error', 'error_code' => 'DUPLICATE_TRANSACTION', 'message' => 'Transaction already processed'];
        }

        $this->db->beginTransaction();
        try {
            // Lock user row
            $stmt = $this->db->prepare('SELECT id, balance, banned FROM users WHERE id = ? FOR UPDATE');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                $this->db->rollBack();
                return ['status' => 'error', 'error_code' => 'PLAYER_NOT_FOUND', 'message' => 'Player not found'];
            }

            if ($user['banned']) {
                $this->db->rollBack();
                return ['status' => 'error', 'error_code' => 'PLAYER_BLOCKED', 'message' => 'Player account is blocked'];
            }

            $currentBalance = (float) $user['balance'];

            if ($currentBalance < $amount) {
                // Log the failed attempt
                $this->logCasinoTransaction($userId, 'debit', $transactionId, $roundId, $gameId, $amount, $currentBalance, $currentBalance, 'failed', 'Insufficient funds', $rawRequest);
                $this->db->commit();
                return ['status' => 'error', 'error_code' => 'INSUFFICIENT_FUNDS', 'balance' => $currentBalance, 'currency' => $this->config['currency']];
            }

            $newBalance = round($currentBalance - $amount, 2);

            // Deduct balance
            $stmt = $this->db->prepare('UPDATE users SET balance = ? WHERE id = ?');
            $stmt->execute([$newBalance, $userId]);

            // Log to main transactions table
            $stmt = $this->db->prepare(
                'INSERT INTO transactions (user_id, type, amount, balance_after, description)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$userId, 'casino_debit', -$amount, $newBalance, "Casino bet: game={$gameId}, round={$roundId}, txn={$transactionId}"]);

            // Log to casino_transactions
            $this->logCasinoTransaction($userId, 'debit', $transactionId, $roundId, $gameId, $amount, $currentBalance, $newBalance, 'success', null, $rawRequest);

            $this->db->commit();

            return [
                'status'         => 'ok',
                'balance'        => $newBalance,
                'currency'       => $this->config['currency'],
                'transaction_id' => $transactionId,
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'error_code' => 'INTERNAL_ERROR', 'message' => 'Server error'];
        }
    }

    /**
     * Credit (win): add amount to player's balance.
     */
    public function credit(int $userId, string $transactionId, float $amount, ?string $roundId, ?string $gameId, ?array $rawRequest): array
    {
        // Idempotency
        $existing = $this->findTransaction($transactionId);
        if ($existing) {
            if ($existing['status'] === 'success') {
                return [
                    'status'   => 'ok',
                    'balance'  => (float) $existing['balance_after'],
                    'currency' => $this->config['currency'],
                    'transaction_id' => $transactionId,
                ];
            }
            return ['status' => 'error', 'error_code' => 'DUPLICATE_TRANSACTION', 'message' => 'Transaction already processed'];
        }

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT id, balance FROM users WHERE id = ? FOR UPDATE');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                $this->db->rollBack();
                return ['status' => 'error', 'error_code' => 'PLAYER_NOT_FOUND', 'message' => 'Player not found'];
            }

            $currentBalance = (float) $user['balance'];
            $newBalance = round($currentBalance + $amount, 2);

            $stmt = $this->db->prepare('UPDATE users SET balance = ? WHERE id = ?');
            $stmt->execute([$newBalance, $userId]);

            $stmt = $this->db->prepare(
                'INSERT INTO transactions (user_id, type, amount, balance_after, description)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$userId, 'casino_credit', $amount, $newBalance, "Casino win: game={$gameId}, round={$roundId}, txn={$transactionId}"]);

            $this->logCasinoTransaction($userId, 'credit', $transactionId, $roundId, $gameId, $amount, $currentBalance, $newBalance, 'success', null, $rawRequest);

            $this->db->commit();

            return [
                'status'         => 'ok',
                'balance'        => $newBalance,
                'currency'       => $this->config['currency'],
                'transaction_id' => $transactionId,
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'error_code' => 'INTERNAL_ERROR', 'message' => 'Server error'];
        }
    }

    /**
     * Rollback: reverse a previous debit transaction.
     */
    public function rollback(int $userId, string $transactionId, string $originalTransactionId, ?string $roundId, ?string $gameId, ?array $rawRequest): array
    {
        // Idempotency: check if rollback was already done
        $existing = $this->findTransaction($transactionId);
        if ($existing && $existing['status'] === 'success') {
            return [
                'status'   => 'ok',
                'balance'  => (float) $existing['balance_after'],
                'currency' => $this->config['currency'],
                'transaction_id' => $transactionId,
            ];
        }

        // Find the original debit
        $original = $this->findTransaction($originalTransactionId);
        if (!$original || $original['action'] !== 'debit' || $original['status'] !== 'success') {
            return ['status' => 'error', 'error_code' => 'TRANSACTION_NOT_FOUND', 'message' => 'Original transaction not found or not a debit'];
        }

        $refundAmount = (float) $original['amount'];

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare('SELECT id, balance FROM users WHERE id = ? FOR UPDATE');
            $stmt->execute([$userId]);
            $user = $stmt->fetch();

            if (!$user) {
                $this->db->rollBack();
                return ['status' => 'error', 'error_code' => 'PLAYER_NOT_FOUND', 'message' => 'Player not found'];
            }

            $currentBalance = (float) $user['balance'];
            $newBalance = round($currentBalance + $refundAmount, 2);

            $stmt = $this->db->prepare('UPDATE users SET balance = ? WHERE id = ?');
            $stmt->execute([$newBalance, $userId]);

            $stmt = $this->db->prepare(
                'INSERT INTO transactions (user_id, type, amount, balance_after, description)
                 VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$userId, 'casino_rollback', $refundAmount, $newBalance, "Casino rollback: original_txn={$originalTransactionId}, round={$roundId}"]);

            $this->logCasinoTransaction($userId, 'rollback', $transactionId, $roundId, $gameId, $refundAmount, $currentBalance, $newBalance, 'success', null, $rawRequest);

            $this->db->commit();

            return [
                'status'         => 'ok',
                'balance'        => $newBalance,
                'currency'       => $this->config['currency'],
                'transaction_id' => $transactionId,
            ];
        } catch (\Exception $e) {
            $this->db->rollBack();
            return ['status' => 'error', 'error_code' => 'INTERNAL_ERROR', 'message' => 'Server error'];
        }
    }

    // ───────────────────────────────────────────────────
    // HASH VALIDATION
    // ───────────────────────────────────────────────────

    /**
     * Validate the request hash sent by Bet4Wins.
     * Expected: SHA256(player_id + transaction_id + amount + secret_key)
     */
    public function validateHash(array $params, string $receivedHash): bool
    {
        $playerId      = $params['player_id'] ?? '';
        $transactionId = $params['transaction_id'] ?? '';
        $amount        = $params['amount'] ?? '';

        $payload = $playerId . $transactionId . $amount . $this->config['secret_key'];
        $expectedHash = hash('sha256', $payload);

        return hash_equals($expectedHash, $receivedHash);
    }

    // ───────────────────────────────────────────────────
    // GAME LOBBY & LAUNCH (outbound to Bet4Wins)
    // ───────────────────────────────────────────────────

    /**
     * Fetch the full game list from Bet4Wins API.
     */
    public function fetchGameLobby(): ?array
    {
        $url = $this->config['lobby_url'] . '?' . http_build_query([
            'operator_id' => $this->config['operator_id'],
            'api_key'     => $this->config['api_key'],
            'currency'    => $this->config['currency'],
        ]);

        return $this->httpGet($url);
    }

    /**
     * Get a game launch URL for a specific player.
     * Generates a unique session token and logs all API errors.
     * In DEV_MODE: returns a local demo page URL (skips API).
     */
    public function getGameLaunchUrl(int $userId, string $username, string $gameId, bool $isMobile = false): ?string
    {
        // ── DEV MODE: return local demo page, skip external API entirely ──
        if ($this->devMode) {
            return $this->getDemoLaunchUrl($gameId, $userId);
        }

        // ── PRODUCTION: call Bet4Wins API ──
        $sessionToken = bin2hex(random_bytes(16));

        $params = [
            'operator_id'  => $this->config['operator_id'],
            'api_key'      => $this->config['api_key'],
            'player_id'    => (string) $userId,
            'player_name'  => $username,
            'token'        => $sessionToken,
            'game_id'      => $gameId,
            'currency'     => $this->config['currency'],
            'language'     => 'en',
            'platform'     => $isMobile ? 'mobile' : 'desktop',
            'lobby_url'    => $this->baseUrl . '/casino.php',
            'callback_url' => $this->config['callback_url'],
        ];

        $hash = hash('sha256', $userId . $gameId . $sessionToken . $this->config['secret_key']);
        $params['hash'] = $hash;

        try {
            $response = $this->httpPost($this->config['launch_url'], $params);

            // Mock on 403 / null in DEV_MODE (should not reach here, but safety net)
            if ($response === null) {
                $this->logCasinoError('launch', $gameId, $userId, 'API returned null or HTTP error', $params);
                return null;
            }

            if (!empty($response['error']) || !empty($response['error_code'])) {
                $errMsg = $response['error'] ?? $response['error_code'] ?? 'Unknown API error';
                $this->logCasinoError('launch', $gameId, $userId, $errMsg, $response);
                return null;
            }

            $gameUrl = $response['game_url'] ?? $response['url'] ?? null;

            if (!$gameUrl || !filter_var($gameUrl, FILTER_VALIDATE_URL)) {
                $this->logCasinoError('launch', $gameId, $userId, 'Invalid or empty game_url in response', $response);
                return null;
            }

            return $gameUrl;

        } catch (\Exception $e) {
            $this->logCasinoError('launch', $gameId, $userId, 'Exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return null;
        }
    }

    /**
     * Generate a local demo game launch URL (DEV_MODE only).
     * Returns a URL to the built-in demo player page.
     */
    private function getDemoLaunchUrl(string $gameId, int $userId): string
    {
        $token = bin2hex(random_bytes(8));
        return $this->baseUrl . '/demo_game.php?' . http_build_query([
            'game_id' => $gameId,
            'player'  => $userId,
            'token'   => $token,
            'mode'    => 'demo',
        ]);
    }

    /**
     * Mock a successful API response.
     * Used when the real API returns 403 / connection error and DEV_MODE is on.
     */
    public function getMockLaunchResponse(string $gameId, int $userId): array
    {
        return [
            'success'  => true,
            'game_url' => $this->getDemoLaunchUrl($gameId, $userId),
            'mode'     => 'demo',
            'message'  => 'Mock response — DEV_MODE active',
        ];
    }

    /**
     * Write structured error to casino_error.log
     */
    private function logCasinoError(string $action, ?string $gameId, ?int $userId, string $message, $context = null): void
    {
        $logFile = dirname(__DIR__, 2) . '/logs/casino_error.log';
        $logDir  = dirname($logFile);
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action'    => $action,
            'game_id'   => $gameId,
            'user_id'   => $userId,
            'message'   => $message,
            'context'   => $context,
        ];

        file_put_contents($logFile, json_encode($entry, JSON_UNESCAPED_SLASHES) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    // ───────────────────────────────────────────────────
    // FAVORITES
    // ───────────────────────────────────────────────────

    public function getFavorites(int $userId): array
    {
        $stmt = $this->db->prepare('SELECT game_id, game_name, provider, thumbnail FROM casino_favorites WHERE user_id = ? ORDER BY created_at DESC');
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }

    public function toggleFavorite(int $userId, string $gameId, string $gameName, ?string $provider, ?string $thumbnail): array
    {
        $stmt = $this->db->prepare('SELECT id FROM casino_favorites WHERE user_id = ? AND game_id = ?');
        $stmt->execute([$userId, $gameId]);

        if ($stmt->fetch()) {
            $this->db->prepare('DELETE FROM casino_favorites WHERE user_id = ? AND game_id = ?')
                ->execute([$userId, $gameId]);
            return ['action' => 'removed'];
        }

        $this->db->prepare(
            'INSERT INTO casino_favorites (user_id, game_id, game_name, provider, thumbnail) VALUES (?, ?, ?, ?, ?)'
        )->execute([$userId, $gameId, $gameName, $provider, $thumbnail]);

        return ['action' => 'added'];
    }

    // ───────────────────────────────────────────────────
    // GAME CATALOG (local DB)
    // ───────────────────────────────────────────────────

    /**
     * Read games from the local casino_games table.
     * Returns thumbnail as local image_path when available, else original URL.
     */
    public function getGamesFromDB(?string $category = null): array
    {
        $sql = 'SELECT game_id AS id, name, provider, category,
                       COALESCE(image_path, thumbnail_url) AS thumbnail,
                       has_demo, is_new, is_hot
                FROM casino_games
                WHERE is_active = 1';
        $params = [];

        if ($category) {
            $sql .= ' AND category = ?';
            $params[] = $category;
        }

        $sql .= ' ORDER BY sort_order ASC, id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return array_map(function ($row) {
            $row['has_demo'] = (bool) $row['has_demo'];
            $row['is_new']   = (bool) $row['is_new'];
            $row['is_hot']   = (bool) $row['is_hot'];
            return $row;
        }, $stmt->fetchAll());
    }

    /**
     * Upsert a game into the casino_games table.
     */
    public function upsertGame(array $game): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO casino_games (game_id, name, provider, category, thumbnail_url, has_demo, is_new, is_hot, sort_order)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE
                 name          = VALUES(name),
                 provider      = VALUES(provider),
                 category      = VALUES(category),
                 thumbnail_url = VALUES(thumbnail_url),
                 has_demo      = VALUES(has_demo),
                 is_new        = VALUES(is_new),
                 is_hot        = VALUES(is_hot),
                 sort_order    = VALUES(sort_order)'
        );
        $stmt->execute([
            $game['id'],
            $game['name'],
            $game['provider'],
            $game['category'],
            $game['thumbnail_url'] ?? null,
            $game['has_demo'] ? 1 : 0,
            $game['is_new']  ? 1 : 0,
            $game['is_hot']  ? 1 : 0,
            $game['sort_order'] ?? 0,
        ]);
    }

    /**
     * Download a game thumbnail to local storage.
     * Returns the relative path on success, null on failure.
     */
    public function downloadGameImage(string $gameId, string $url): ?string
    {
        $dir = dirname(__DIR__, 2) . '/public/assets/images/games';
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        // Determine extension from URL or default to png
        $ext = 'png';
        $path = parse_url($url, PHP_URL_PATH);
        if ($path && preg_match('/\.(jpe?g|png|webp|gif|svg)/i', $path, $m)) {
            $ext = strtolower($m[1]);
        }

        $filename = preg_replace('/[^a-zA-Z0-9_-]/', '_', $gameId) . '.' . $ext;
        $filepath = $dir . '/' . $filename;
        $localUrl = 'assets/images/games/' . $filename;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $data = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || !$data || strlen($data) < 100) {
            return null;
        }

        if (file_put_contents($filepath, $data) === false) {
            return null;
        }

        // Update DB with local path + content hash
        $hash = hash('sha256', $data);
        $stmt = $this->db->prepare(
            'UPDATE casino_games SET image_path = ?, image_hash = ? WHERE game_id = ?'
        );
        $stmt->execute([$localUrl, $hash, $gameId]);

        return $localUrl;
    }

    /**
     * Get all games that need image download.
     */
    public function getGamesNeedingImages(): array
    {
        return $this->db->query(
            'SELECT game_id, thumbnail_url FROM casino_games WHERE image_path IS NULL AND thumbnail_url IS NOT NULL AND is_active = 1'
        )->fetchAll();
    }

    /**
     * Get count of games in DB.
     */
    public function getGameCount(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM casino_games')->fetchColumn();
    }

    // ───────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ───────────────────────────────────────────────────

    private function findTransaction(string $transactionId): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM casino_transactions WHERE transaction_id = ?');
        $stmt->execute([$transactionId]);
        return $stmt->fetch() ?: null;
    }

    private function logCasinoTransaction(int $userId, string $action, string $transactionId, ?string $roundId, ?string $gameId, float $amount, float $balBefore, float $balAfter, string $status, ?string $error, ?array $rawRequest): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO casino_transactions (user_id, action, transaction_id, round_id, game_id, amount, balance_before, balance_after, currency, status, error_message, raw_request)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $userId,
            $action,
            $transactionId,
            $roundId,
            $gameId,
            $amount,
            $balBefore,
            $balAfter,
            $this->config['currency'],
            $status,
            $error,
            $rawRequest ? json_encode($rawRequest) : null,
        ]);
    }

    private function httpGet(string $url): ?array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $resp = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code !== 200 || !$resp) return null;
        return json_decode($resp, true);
    }

    private function httpPost(string $url, array $data): ?array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $resp    = curl_exec($ch);
        $code    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            $this->logCasinoError('httpPost', null, null, "cURL error: {$curlErr}", ['url' => $url, 'http_code' => $code]);
            return null;
        }

        // Log blocked / failed responses
        if ($code === 403) {
            $this->logCasinoError('httpPost', null, null, "403 Forbidden (IP not whitelisted?) from {$url}", ['response_body' => mb_substr($resp ?: '', 0, 500)]);
            return null;
        }

        if ($code !== 200 || !$resp) {
            $this->logCasinoError('httpPost', null, null, "HTTP {$code} from {$url}", ['response_body' => mb_substr($resp ?: '', 0, 500)]);
            return null;
        }

        return json_decode($resp, true);
    }
}
