<?php

namespace App\Scraper;

use App\Core\Database;
use App\Core\Logger;
use PDO;

class OddsScraper
{
    private PDO $db;
    private Logger $logger;
    private array $config;
    private float $margin;

    /** Maps API market IDs to readable names */
    private const MARKET_MAP = [
        14    => ['name' => '1X2',              'outcomes' => ['1' => 'Home', '2' => 'Draw', '3' => 'Away']],
        20560 => ['name' => 'Double Chance',    'outcomes' => ['1' => '1X',   '2' => '12',   '3' => 'X2']],
        2211  => ['name' => 'Over/Under 2.5',   'outcomes' => ['1' => 'Over 2.5', '2' => 'Under 2.5']],
        20562 => ['name' => 'Both Teams Score', 'outcomes' => ['1' => 'Yes',  '2' => 'No']],
    ];

    /** Maps API outcome keys to display keys */
    private const OUTCOME_KEY_MAP = [
        14    => ['1' => '1', '2' => 'X', '3' => '2'],
        20560 => ['1' => '1X', '2' => '12', '3' => 'X2'],
        2211  => ['1' => 'Over 2.5', '2' => 'Under 2.5'],
        20562 => ['1' => 'GG', '2' => 'NG'],
    ];

    /** Time windows to try per sport (8h = max, 3h = fallback) */
    private const TIME_WINDOWS = [8, 3];

    /** How many sports to fetch per batch before a small pause */
    private const BATCH_SIZE = 5;

    /** Microseconds to sleep between batches (500ms) */
    private const BATCH_DELAY_US = 500000;

    public function __construct(array $config, Logger $logger)
    {
        $this->db     = Database::getInstance($config['db'])->getPdo();
        $this->logger = $logger;
        $this->config = $config;
        $this->margin = $config['betting']['margin'] ?? 0.05;
    }

    // -----------------------------------------------------------
    // PUBLIC API
    // -----------------------------------------------------------

    /**
     * Scrape the main schedules endpoint to populate sports, countries, leagues.
     * Returns the list of sport API IDs found in the schedule tree.
     */
    public function scrapeSchedules(): array
    {
        $start = microtime(true);
        $this->logger->info('Starting schedule scrape...');

        $json = $this->fetchEndpoint('main');
        if ($json === null) {
            return $this->logResult('main', 0, 0, 'Failed to fetch main endpoint', $start);
        }

        $schedules = $json['schedules'] ?? [];
        $sportsProcessed = 0;
        $sportApiIds = [];

        foreach ($schedules as $sportName => $sportData) {
            $sportApiId = $sportData['id'] ?? null;
            if (!$sportApiId) continue;

            $sportApiIds[] = $sportApiId;
            $sportId = $this->upsertSport($sportApiId, $sportName, $sportData['order'] ?? 0);

            $countries = $sportData['sub'] ?? [];
            foreach ($countries as $countryName => $countryData) {
                $countryApiId = $countryData['idPal'] ?? null;
                if (!$countryApiId) continue;

                $countryId = $this->upsertCountry($countryApiId, $sportId, $countryName, $countryData['order'] ?? 0);

                $leagues = $countryData['sub'] ?? [];
                foreach ($leagues as $leagueName => $leagueData) {
                    $leagueApiId = $leagueData['idMan'] ?? null;
                    if (!$leagueApiId || $leagueName === '') continue;

                    $this->upsertLeague($leagueApiId, $sportId, $countryId, $leagueName, $leagueData['code'] ?? null, $leagueData['order'] ?? 0);
                }
            }
            $sportsProcessed++;
        }

        $this->logger->info("Schedules done. Sports processed: {$sportsProcessed}");
        $result = $this->logResult('main', $sportsProcessed, 0, null, $start);
        $result['sport_api_ids'] = array_unique($sportApiIds);
        return $result;
    }

    /**
     * Deep scrape: loop through EVERY sport and fetch all matches via lastminute/{sport_id}/8/.
     * This replaces the old single-endpoint scrapeOdds().
     */
    public function scrapeAllSports(array $sportApiIds): array
    {
        $start = microtime(true);
        $this->logger->info('Starting deep scrape for ' . count($sportApiIds) . ' sports...');

        $totalMatches = 0;
        $totalOdds    = 0;
        $seenEventKeys = [];
        $errors       = [];
        $baseUrl      = $this->config['api']['base_url'];
        $queryString  = $this->config['api']['query_string'];

        $batches = array_chunk($sportApiIds, self::BATCH_SIZE);

        foreach ($batches as $batchIndex => $batch) {
            if ($batchIndex > 0) {
                usleep(self::BATCH_DELAY_US);
            }

            foreach ($batch as $sportApiId) {
                $json = null;

                // Try each time window — use whichever returns the most data
                foreach (self::TIME_WINDOWS as $window) {
                    $url = "{$baseUrl}/lastminute/{$sportApiId}/{$window}/?{$queryString}";
                    $data = $this->fetchUrl($url);
                    if ($data !== null) {
                        // Count events in this response
                        $count = 0;
                        foreach ($data['events'] ?? [] as $g => $gd) {
                            $count += count($gd['eventi'] ?? []);
                        }
                        if ($count > 0) {
                            $json = $data;
                            break; // Use first window that returns data (8h is tried first)
                        }
                    }
                }

                if ($json === null) continue;

                $eventsGroups = $json['events'] ?? [];

                $this->db->beginTransaction();
                try {
                    foreach ($eventsGroups as $groupName => $groupData) {
                        $gSportApiId  = $groupData['sport'] ?? $sportApiId;
                        $sportDesc    = $groupData['desc_sport'] ?? "Sport {$gSportApiId}";
                        $countryApiId = $groupData['palinsesto'] ?? null;
                        $countryDesc  = $groupData['desc_palinsesto'] ?? '';

                        $sportId = $this->upsertSport($gSportApiId, $sportDesc, 0);

                        $countryId = null;
                        if ($countryApiId) {
                            $countryId = $this->upsertCountry($countryApiId, $sportId, $countryDesc, 0);
                        }

                        $events = $groupData['eventi'] ?? [];
                        foreach ($events as $eventKey => $event) {
                            $matchId = $this->upsertMatch($eventKey, $event, $sportId, $countryId);
                            if ($matchId === null) continue;

                            $seenEventKeys[] = $eventKey;
                            $totalMatches++;

                            $quotes = $event['quote'] ?? [];
                            foreach ($quotes as $marketIdStr => $outcomes) {
                                $marketId = (int) $marketIdStr;
                                if (!isset(self::MARKET_MAP[$marketId])) continue;

                                $marketInfo = self::MARKET_MAP[$marketId];
                                $keyMap     = self::OUTCOME_KEY_MAP[$marketId] ?? [];

                                foreach ($outcomes as $outcomeApiKey => $rawValue) {
                                    $displayKey   = $keyMap[$outcomeApiKey] ?? $outcomeApiKey;
                                    $outcomeLabel = $marketInfo['outcomes'][$outcomeApiKey] ?? $displayKey;

                                    $originalOdds = $this->parseOdds($rawValue);
                                    $adjustedOdds = $this->applyMargin($originalOdds);

                                    $this->upsertOdds($matchId, $marketId, $marketInfo['name'], $displayKey, $outcomeLabel, $adjustedOdds, $originalOdds);
                                    $totalOdds++;
                                }
                            }
                        }
                    }
                    $this->db->commit();
                } catch (\Exception $e) {
                    $this->db->rollBack();
                    $msg = "Sport {$sportApiId} failed: " . $e->getMessage();
                    $this->logger->error($msg);
                    $errors[] = $msg;
                }
            }

            $this->logger->info("Batch " . ($batchIndex + 1) . "/" . count($batches) . " done. Running total: {$totalMatches} matches, {$totalOdds} odds");
        }

        // Sync: deactivate matches not seen in this sweep (still active, start_time in future)
        $deactivated = $this->deactivateUnseen($seenEventKeys);

        $errorStr = empty($errors) ? null : implode('; ', $errors);
        $this->logger->info("Deep scrape complete. Matches: {$totalMatches}, Odds: {$totalOdds}, Deactivated: {$deactivated}");

        $result = $this->logResult('deep_scrape', $totalMatches, $totalOdds, $errorStr, $start);
        $result['deactivated'] = $deactivated;
        $result['seen_events'] = count($seenEventKeys);
        return $result;
    }

    /**
     * Combined scrape: schedules tree then deep sport-by-sport sweep.
     */
    public function scrapeAll(): void
    {
        $schedResult = $this->scrapeSchedules();
        $sportApiIds = $schedResult['sport_api_ids'] ?? [];

        if (empty($sportApiIds)) {
            $this->logger->error('No sport IDs found from schedules — aborting deep scrape');
            return;
        }

        $this->scrapeAllSports($sportApiIds);
        $this->deactivateExpiredMatches();
    }

    // -----------------------------------------------------------
    // HTTP LAYER
    // -----------------------------------------------------------

    private function fetchEndpoint(string $key): ?array
    {
        $url = $this->config['api']['endpoints'][$key] ?? null;
        if (!$url) {
            $this->logger->error("Unknown endpoint key: {$key}");
            return null;
        }
        return $this->fetchUrl($url);
    }

    private function fetchUrl(string $url): ?array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => $this->config['api']['timeout'] ?? 30,
            CURLOPT_HTTPHEADER     => $this->config['api']['headers'] ?? [],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_ENCODING       => 'gzip, deflate',
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            $this->logger->error("HTTP {$httpCode} for {$url}: {$error}");
            return null;
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->logger->error("JSON decode error for {$url}: " . json_last_error_msg());
            return null;
        }

        return $data;
    }

    // -----------------------------------------------------------
    // UPSERT METHODS
    // -----------------------------------------------------------

    private function upsertSport(int $apiId, string $name, int $order): int
    {
        $slug = $this->slugify($name);

        $stmt = $this->db->prepare(
            'INSERT INTO sports (api_id, name, slug, sort_order)
             VALUES (:api_id, :name, :slug, :sort_order)
             ON DUPLICATE KEY UPDATE name = VALUES(name), sort_order = VALUES(sort_order)'
        );
        $stmt->execute(['api_id' => $apiId, 'name' => $name, 'slug' => $slug, 'sort_order' => $order]);

        $stmt = $this->db->prepare('SELECT id FROM sports WHERE api_id = ?');
        $stmt->execute([$apiId]);
        return (int) $stmt->fetchColumn();
    }

    private function upsertCountry(int $apiId, int $sportId, string $name, int $order): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO countries (api_id, sport_id, name, sort_order)
             VALUES (:api_id, :sport_id, :name, :sort_order)
             ON DUPLICATE KEY UPDATE name = VALUES(name), sort_order = VALUES(sort_order)'
        );
        $stmt->execute(['api_id' => $apiId, 'sport_id' => $sportId, 'name' => $name, 'sort_order' => $order]);

        $stmt = $this->db->prepare('SELECT id FROM countries WHERE sport_id = ? AND api_id = ?');
        $stmt->execute([$sportId, $apiId]);
        return (int) $stmt->fetchColumn();
    }

    private function upsertLeague(int $apiId, int $sportId, int $countryId, string $name, ?string $code, int $order): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO leagues (api_id, sport_id, country_id, name, code, sort_order)
             VALUES (:api_id, :sport_id, :country_id, :name, :code, :sort_order)
             ON DUPLICATE KEY UPDATE name = VALUES(name), code = VALUES(code), sort_order = VALUES(sort_order)'
        );
        $stmt->execute([
            'api_id'     => $apiId,
            'sport_id'   => $sportId,
            'country_id' => $countryId,
            'name'       => $name,
            'code'       => $code,
            'sort_order' => $order,
        ]);

        $stmt = $this->db->prepare('SELECT id FROM leagues WHERE api_id = ?');
        $stmt->execute([$apiId]);
        return (int) $stmt->fetchColumn();
    }

    private function upsertMatch(string $eventKey, array $event, int $sportId, ?int $countryId): ?int
    {
        $home       = $event['home'] ?? '';
        $away       = $event['away'] ?? '';
        $dateTs     = $event['date_ts'] ?? 0;
        $apiCode    = $event['code'] ?? null;
        $active     = ($event['active'] ?? false) ? 1 : 0;
        $homeCode   = $event['teamcode']['home'] ?? null;
        $awayCode   = $event['teamcode']['away'] ?? null;
        $tournamentId = $event['tournament_id'] ?? null;

        if (empty($home) || empty($away) || $dateTs === 0) {
            return null;
        }

        // Resolve league
        $leagueId = null;
        if ($tournamentId) {
            $stmt = $this->db->prepare('SELECT id FROM leagues WHERE api_id = ?');
            $stmt->execute([$tournamentId]);
            $leagueId = $stmt->fetchColumn() ?: null;
        }

        $startTime = date('Y-m-d H:i:s', $dateTs);

        $stmt = $this->db->prepare(
            'INSERT INTO `matches` (api_event_key, api_code, sport_id, country_id, league_id,
                home_team, away_team, home_team_code, away_team_code, start_time, start_timestamp, active)
             VALUES (:key, :code, :sport, :country, :league, :home, :away, :hcode, :acode, :start, :ts, :active)
             ON DUPLICATE KEY UPDATE
                api_code = VALUES(api_code),
                home_team = VALUES(home_team),
                away_team = VALUES(away_team),
                start_time = VALUES(start_time),
                start_timestamp = VALUES(start_timestamp),
                active = VALUES(active),
                league_id = COALESCE(VALUES(league_id), league_id),
                updated_at = NOW()'
        );
        $stmt->execute([
            'key'     => $eventKey,
            'code'    => $apiCode,
            'sport'   => $sportId,
            'country' => $countryId,
            'league'  => $leagueId,
            'home'    => $home,
            'away'    => $away,
            'hcode'   => $homeCode,
            'acode'   => $awayCode,
            'start'   => $startTime,
            'ts'      => $dateTs,
            'active'  => $active,
        ]);

        $stmt = $this->db->prepare('SELECT id FROM `matches` WHERE api_event_key = ?');
        $stmt->execute([$eventKey]);
        return (int) $stmt->fetchColumn() ?: null;
    }

    private function upsertOdds(int $matchId, int $marketId, string $marketName, string $outcomeKey, string $outcomeLabel, ?float $value, ?float $original): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO odds (match_id, market_id, market_name, outcome_key, outcome_label, value, original_value)
             VALUES (:mid, :mktid, :mktname, :okey, :olabel, :val, :orig)
             ON DUPLICATE KEY UPDATE
                value = VALUES(value),
                original_value = VALUES(original_value),
                outcome_label = VALUES(outcome_label),
                updated_at = NOW()'
        );
        $stmt->execute([
            'mid'     => $matchId,
            'mktid'   => $marketId,
            'mktname' => $marketName,
            'okey'    => $outcomeKey,
            'olabel'  => $outcomeLabel,
            'val'     => $value,
            'orig'    => $original,
        ]);
    }

    // -----------------------------------------------------------
    // ODDS PROCESSING
    // -----------------------------------------------------------

    private function parseOdds($raw): ?float
    {
        if ($raw === '-' || $raw === null || $raw === '') {
            return null;
        }
        $val = (float) $raw;
        return $val > 0 ? $val : null;
    }

    private function applyMargin(?float $odds): ?float
    {
        if ($odds === null || $odds <= 1.0) {
            return $odds;
        }
        $adjusted = 1 + ($odds - 1) * (1 - $this->margin);
        return round($adjusted, 2);
    }

    // -----------------------------------------------------------
    // MAINTENANCE / SYNC
    // -----------------------------------------------------------

    /**
     * Deactivate active future matches whose api_event_key was NOT seen in the latest sweep.
     */
    private function deactivateUnseen(array $seenEventKeys): int
    {
        if (empty($seenEventKeys)) return 0;

        // Build placeholders for the IN clause
        $placeholders = implode(',', array_fill(0, count($seenEventKeys), '?'));

        $stmt = $this->db->prepare(
            "UPDATE `matches`
             SET active = 0, updated_at = NOW()
             WHERE active = 1
               AND start_time > NOW()
               AND api_event_key NOT IN ({$placeholders})"
        );
        $stmt->execute($seenEventKeys);
        $count = $stmt->rowCount();

        if ($count > 0) {
            $this->logger->info("Sync: deactivated {$count} matches no longer in API feed.");
        }
        return $count;
    }

    /**
     * Deactivate matches whose start_time has passed.
     */
    public function deactivateExpiredMatches(): int
    {
        $stmt = $this->db->prepare('UPDATE `matches` SET active = 0 WHERE active = 1 AND start_time < NOW()');
        $stmt->execute();
        $count = $stmt->rowCount();
        if ($count > 0) {
            $this->logger->info("Deactivated {$count} expired matches.");
        }
        return $count;
    }

    // -----------------------------------------------------------
    // HELPERS
    // -----------------------------------------------------------

    private function slugify(string $text): string
    {
        $text = transliterator_transliterate('Any-Latin; Latin-ASCII;', $text) ?: $text;
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    private function logResult(string $endpoint, int $matches, int $odds, ?string $error, float $startTime): array
    {
        $ms = (int) ((microtime(true) - $startTime) * 1000);

        $stmt = $this->db->prepare(
            'INSERT INTO scraper_log (endpoint, matches_processed, odds_updated, errors, execution_time_ms)
             VALUES (?, ?, ?, ?, ?)'
        );
        $stmt->execute([$endpoint, $matches, $odds, $error, $ms]);

        return [
            'endpoint' => $endpoint,
            'matches'  => $matches,
            'odds'     => $odds,
            'error'    => $error,
            'time_ms'  => $ms,
        ];
    }
}
