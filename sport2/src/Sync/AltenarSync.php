<?php
namespace Sport2\Sync;

use PDO;
use Sport2\Core\AltenarClient;
use Sport2\Core\Database;
use Sport2\Core\Logger;

class AltenarSync
{
    private PDO $db;
    private AltenarClient $client;
    private Logger $logger;
    private array $stats;

    public function __construct(AltenarClient $client, Logger $logger)
    {
        $this->db     = Database::get();
        $this->client = $client;
        $this->logger = $logger;
        $this->resetStats();
    }

    private function resetStats(): void
    {
        $this->stats = ['events' => 0, 'markets' => 0, 'selections' => 0];
    }

    // ─── FULL SYNC ──────────────────────────────────────────────────

    public function syncAll(): array
    {
        $this->resetStats();
        $logId = $this->logSyncStart('full');
        $start = microtime(true);

        try {
            $this->syncSports();
            $this->syncLiveEvents();
            $this->syncUpcomingEvents();
            $duration = (int)((microtime(true) - $start) * 1000);
            $this->logSyncEnd($logId, 'success', $duration);
            $this->logger->info('Full sync completed', $this->stats + ['duration_ms' => $duration]);
        } catch (\Throwable $e) {
            $duration = (int)((microtime(true) - $start) * 1000);
            $this->logSyncEnd($logId, 'failed', $duration, $e->getMessage());
            $this->logger->error('Full sync failed', ['error' => $e->getMessage()]);
        }

        return $this->stats;
    }

    // ─── SPORTS ─────────────────────────────────────────────────────

    public function syncSports(): int
    {
        $data = $this->client->getAllSports();
        if (!$data) return 0;

        $items = $data['Result'] ?? $data;
        if (!is_array($items)) return 0;

        $stmt = $this->db->prepare('
            INSERT INTO sports (id, name, slug, iso, sport_type_id, event_count, has_live, has_stream, sort_order, synced_at)
            VALUES (:id, :name, :slug, :iso, :stid, :ec, :hl, :hs, :so, NOW())
            ON DUPLICATE KEY UPDATE
                name=VALUES(name), event_count=VALUES(event_count), has_live=VALUES(has_live),
                has_stream=VALUES(has_stream), sort_order=VALUES(sort_order), synced_at=NOW()
        ');

        $count = 0;
        foreach ($items as $s) {
            $stmt->execute([
                ':id'   => (int)$s['Id'],
                ':name' => trim($s['Name']),
                ':slug' => $this->slugify($s['Name']),
                ':iso'  => $s['ISO'] ?? null,
                ':stid' => $s['SportTypeId'] ?? null,
                ':ec'   => (int)($s['EventCount'] ?? 0),
                ':hl'   => !empty($s['HasLiveEvents']) ? 1 : 0,
                ':hs'   => !empty($s['HasLiveStream']) ? 1 : 0,
                ':so'   => (int)($s['SortOrder'] ?? 0),
            ]);
            $count++;
        }

        $this->logger->info("Synced {$count} sports");
        return $count;
    }

    // ─── LIVE EVENTS ────────────────────────────────────────────────

    public function syncLiveEvents(): int
    {
        $logId = $this->logSyncStart('live');
        $start = microtime(true);
        $this->resetStats();

        try {
            $data = $this->client->getLiveEvents(0);
            if (!$data || empty($data['Result']['Items'])) {
                $this->logSyncEnd($logId, 'success', 0);
                return 0;
            }

            $this->db->beginTransaction();

            // Mark all currently-live events as not-live first, so ended matches get cleared
            $this->db->exec('UPDATE events SET is_live = 0 WHERE is_live = 1');

            foreach ($data['Result']['Items'] as $sport) {
                $sportId = (int)$sport['Id'];
                $this->processSportChampionships($sportId, $sport['Items'] ?? [], true);
            }

            $this->db->commit();
            $duration = (int)((microtime(true) - $start) * 1000);
            $this->logSyncEnd($logId, 'success', $duration);
            $this->logger->info('Live sync done', $this->stats + ['duration_ms' => $duration]);
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $duration = (int)((microtime(true) - $start) * 1000);
            $this->logSyncEnd($logId, 'failed', $duration, $e->getMessage());
            $this->logger->error('Live sync failed', ['error' => $e->getMessage()]);
        }

        return $this->stats['events'];
    }

    // ─── UPCOMING EVENTS ────────────────────────────────────────────

    public function syncUpcomingEvents(): int
    {
        $logId = $this->logSyncStart('upcoming');
        $start = microtime(true);
        $this->resetStats();

        try {
            $batchSize = sport2_config()['altenar']['batch_size'];

            // GetUpcoming requires a specific sportId — iterate all active sports
            $sportsStmt = $this->db->query('SELECT id FROM sports WHERE is_active = 1 AND event_count > 0 ORDER BY sort_order');
            $sportIds = $sportsStmt->fetchAll(\PDO::FETCH_COLUMN);

            if (empty($sportIds)) {
                // Fallback: sync sports first
                $this->syncSports();
                $sportIds = $this->db->query('SELECT id FROM sports WHERE is_active = 1 AND event_count > 0')->fetchAll(\PDO::FETCH_COLUMN);
            }

            foreach ($sportIds as $sportId) {
                $data = $this->client->getUpcoming((int)$sportId, $batchSize, 0);
                if (!$data || empty($data['Result']['Items'])) continue;

                $this->db->beginTransaction();

                foreach ($data['Result']['Items'] as $sport) {
                    $sid = (int)$sport['Id'];
                    if (!empty($sport['Events'])) {
                        $this->processEventsArray($sid, $sport['Events'], false);
                    }
                    if (!empty($sport['Items'])) {
                        $this->processSportChampionships($sid, $sport['Items'], false);
                    }
                }

                $this->db->commit();
            }

            $duration = (int)((microtime(true) - $start) * 1000);
            $this->logSyncEnd($logId, 'success', $duration);
            $this->logger->info('Upcoming sync done', $this->stats + ['duration_ms' => $duration]);
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            $duration = (int)((microtime(true) - $start) * 1000);
            $this->logSyncEnd($logId, 'failed', $duration, $e->getMessage());
            $this->logger->error('Upcoming sync failed', ['error' => $e->getMessage()]);
        }

        return $this->stats['events'];
    }

    // ─── PROCESS CHAMPIONSHIPS (for live events) ───────────────────

    private function processSportChampionships(int $sportId, array $championships, bool $isLive): void
    {
        foreach ($championships as $champ) {
            $leagueId = (int)$champ['Id'];
            $this->upsertLeague($leagueId, $champ['Name'] ?? '', $sportId, $champ['ISO'] ?? null, (int)($champ['Order'] ?? 0));

            if (!empty($champ['Events'])) {
                $this->processEventsArray($sportId, $champ['Events'], $isLive, $leagueId);
            }
        }
    }

    // ─── PROCESS EVENTS ARRAY ───────────────────────────────────────

    private function processEventsArray(int $sportId, array $events, bool $isLive, ?int $defaultLeagueId = null): int
    {
        $stmtEvent = $this->db->prepare('
            INSERT INTO events (id, ext_id, event_code, sport_id, league_id, category_id, category_name,
                name, home_team, away_team, home_logo, away_logo, competitors, event_date, event_type,
                is_live, is_live_stream, is_parlay, live_score, live_time, status, selections_count, synced_at)
            VALUES (:id, :ext, :code, :sid, :lid, :cid, :cname,
                :name, :home, :away, :hlogo, :alogo, :comp, :edate, :etype,
                :live, :stream, :parlay, :score, :ltime, :status, :selcnt, NOW())
            ON DUPLICATE KEY UPDATE
                name=VALUES(name), home_team=VALUES(home_team), away_team=VALUES(away_team),
                home_logo=VALUES(home_logo), away_logo=VALUES(away_logo), competitors=VALUES(competitors),
                event_date=VALUES(event_date), is_live=VALUES(is_live), is_live_stream=VALUES(is_live_stream),
                live_score=VALUES(live_score), live_time=VALUES(live_time), status=VALUES(status),
                selections_count=VALUES(selections_count), synced_at=NOW()
        ');

        $count = 0;
        foreach ($events as $evt) {
            $eventId  = (int)($evt['Id'] ?? $evt['DbId'] ?? 0);
            if ($eventId <= 0) continue;

            $leagueId = (int)($evt['ChampId'] ?? $defaultLeagueId ?? 0);
            $catId    = $evt['CategoryId'] ?? null;

            // Ensure league exists
            if ($leagueId > 0 && !empty($evt['ChampName'])) {
                $this->upsertLeague($leagueId, $evt['ChampName'], $sportId, $evt['ISO'] ?? null);
            }

            // Ensure category exists
            if ($catId) {
                $this->upsertCategory((int)$catId, $evt['CategoryName'] ?? '', $sportId);
            }

            // Extract competitors
            $competitors = $evt['Competitors'] ?? [];
            $home = $competitors[0]['Name'] ?? null;
            $away = $competitors[1]['Name'] ?? null;
            $homeLogo = $competitors[0]['Logo'] ?? null;
            $awayLogo = $competitors[1]['Logo'] ?? null;

            // Parse event date (API format varies)
            $eventDate = $this->parseDate($evt['EventDate'] ?? null);

            $stmtEvent->execute([
                ':id'     => $eventId,
                ':ext'    => $evt['ExtId'] ?? null,
                ':code'   => $evt['EventCode'] ?? null,
                ':sid'    => $sportId,
                ':lid'    => $leagueId,
                ':cid'    => $catId,
                ':cname'  => $evt['CategoryName'] ?? null,
                ':name'   => trim($evt['Name'] ?? ($home . ' vs ' . $away)),
                ':home'   => $home ? trim($home) : null,
                ':away'   => $away ? trim($away) : null,
                ':hlogo'  => $homeLogo,
                ':alogo'  => $awayLogo,
                ':comp'   => !empty($competitors) ? json_encode($competitors) : null,
                ':edate'  => $eventDate,
                ':etype'  => (int)($evt['EventType'] ?? 0),
                ':live'   => $isLive ? 1 : (!empty($evt['IsLiveEvent']) ? 1 : 0),
                ':stream' => !empty($evt['IsLiveStream']) ? 1 : 0,
                ':parlay' => !empty($evt['IsParlay']) ? 1 : 0,
                ':score'  => $evt['LiveScore'] ?? null,
                ':ltime'  => $evt['LiveCurrentTime'] ?? null,
                ':status' => 1,  // Always store as active (1) if API returned it
                ':selcnt' => (int)($evt['SelectionsCount'] ?? 0),
            ]);

            $this->stats['events']++;
            $count++;

            // Process markets/selections
            if (!empty($evt['Items'])) {
                $this->processMarkets($eventId, $evt['Items']);
            }
        }

        return $count;
    }

    // ─── PROCESS MARKETS ────────────────────────────────────────────

    private function processMarkets(int $eventId, array $markets): void
    {
        $stmtMarket = $this->db->prepare('
            INSERT INTO markets (id, event_id, market_type_id, name, short_name, special_value,
                column_count, template, status, sort_order, synced_at)
            VALUES (:id, :eid, :mtid, :name, :sname, :sv, :cc, :tpl, :status, :so, NOW())
            ON DUPLICATE KEY UPDATE
                name=VALUES(name), status=VALUES(status), synced_at=NOW()
        ');

        foreach ($markets as $mkt) {
            $mktTypeId  = (int)($mkt['MarketTypeId'] ?? $mkt['Id'] ?? 0);
            $specialVal = $mkt['SpecialOddsValue'] ?? null;
            $marketId   = $eventId . '_' . $mktTypeId . '_' . ($specialVal ?? '0');

            $stmtMarket->execute([
                ':id'     => $marketId,
                ':eid'    => $eventId,
                ':mtid'   => $mktTypeId,
                ':name'   => trim($mkt['Name'] ?? ''),
                ':sname'  => $mkt['ShortName'] ?? null,
                ':sv'     => $specialVal,
                ':cc'     => (int)($mkt['ColumnCount'] ?? 2),
                ':tpl'    => $mkt['Template'] ?? null,
                ':status' => (int)($mkt['Status'] ?? 1),
                ':so'     => (int)($mkt['SportMarketId'] ?? 0),
            ]);
            $this->stats['markets']++;

            // Process selections
            if (!empty($mkt['Items'])) {
                $this->processSelections($eventId, $marketId, $mkt['Items']);
            }
        }
    }

    // ─── PROCESS SELECTIONS (ODDS) ─────────────────────────────────

    private function processSelections(int $eventId, string $marketId, array $selections): void
    {
        $stmtSel = $this->db->prepare('
            INSERT INTO selections (id, market_id, event_id, name, outcome_id, specifier,
                odds, previous_odds, odds_direction, is_active, column_num, selection_type_id, result, synced_at)
            VALUES (:id, :mid, :eid, :name, :oid, :spec,
                :odds, NULL, 0, :active, :col, :stid, :result, NOW())
            ON DUPLICATE KEY UPDATE
                previous_odds = IF(odds != VALUES(odds), odds, previous_odds),
                odds_direction = CASE
                    WHEN VALUES(odds) > odds THEN 1
                    WHEN VALUES(odds) < odds THEN -1
                    ELSE 0
                END,
                odds = VALUES(odds),
                market_id = VALUES(market_id),
                event_id = VALUES(event_id),
                is_active = VALUES(is_active),
                name = VALUES(name),
                result = VALUES(result),
                synced_at = NOW()
        ');

        foreach ($selections as $sel) {
            $selId = $sel['outcome_odds_id'] ?? $sel['Id'] ?? null;
            if (!$selId) continue;

            $stmtSel->execute([
                ':id'     => (string)$selId,
                ':mid'    => $marketId,
                ':eid'    => $eventId,
                ':name'   => trim($sel['Name'] ?? $sel['name'] ?? ''),
                ':oid'    => $sel['outcome_id'] ?? null,
                ':spec'   => $sel['specifier'] ?? null,
                ':odds'   => (float)($sel['Price'] ?? $sel['odds'] ?? 1.0),
                ':active' => !empty($sel['IsActive']) ? 1 : 0,
                ':col'    => (int)($sel['ColumnNum'] ?? 1),
                ':stid'   => $sel['SelectionTypeId'] ?? null,
                ':result' => (int)($sel['Result'] ?? 0),
            ]);
            $this->stats['selections']++;
        }
    }

    // ─── UPSERT HELPERS ─────────────────────────────────────────────

    private function upsertLeague(int $id, string $name, int $sportId, ?string $iso = null, int $order = 0): void
    {
        static $cache = [];
        $key = $id;
        if (isset($cache[$key])) return;
        $cache[$key] = true;

        $this->db->prepare('
            INSERT INTO leagues (id, name, sport_id, iso, sort_order, synced_at)
            VALUES (:id, :name, :sid, :iso, :ord, NOW())
            ON DUPLICATE KEY UPDATE name=VALUES(name), synced_at=NOW()
        ')->execute([':id' => $id, ':name' => trim($name), ':sid' => $sportId, ':iso' => $iso, ':ord' => $order]);
    }

    private function upsertCategory(int $id, string $name, int $sportId): void
    {
        static $cache = [];
        if (isset($cache[$id])) return;
        $cache[$id] = true;

        $this->db->prepare('
            INSERT INTO categories (id, name, sport_id, synced_at)
            VALUES (:id, :name, :sid, NOW())
            ON DUPLICATE KEY UPDATE name=VALUES(name), synced_at=NOW()
        ')->execute([':id' => $id, ':name' => trim($name), ':sid' => $sportId]);
    }

    // ─── UTILITIES ──────────────────────────────────────────────────

    private function parseDate($value): ?string
    {
        if (!$value) return null;

        // Handle .NET JSON date format: /Date(1234567890000)/
        if (is_string($value) && preg_match('/\/Date\((\d+)\)\//', $value, $m)) {
            return date('Y-m-d H:i:s', (int)($m[1] / 1000));
        }

        // Handle ISO date or timestamp
        if (is_numeric($value)) {
            return date('Y-m-d H:i:s', (int)$value);
        }

        $ts = strtotime($value);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }

    private function slugify(string $text): string
    {
        $text = strtolower(trim($text));
        $text = preg_replace('/[^a-z0-9]+/', '-', $text);
        return trim($text, '-');
    }

    // ─── SYNC LOGGING ───────────────────────────────────────────────

    private function logSyncStart(string $type): int
    {
        $stmt = $this->db->prepare('INSERT INTO sync_log (sync_type, started_at, status) VALUES (:t, NOW(), "running")');
        $stmt->execute([':t' => $type]);
        return (int)$this->db->lastInsertId();
    }

    private function logSyncEnd(int $id, string $status, int $durationMs, ?string $error = null): void
    {
        $this->db->prepare('
            UPDATE sync_log SET finished_at=NOW(), status=:s, events_synced=:e, markets_synced=:m,
                selections_synced=:sl, error_message=:err, duration_ms=:d WHERE id=:id
        ')->execute([
            ':s' => $status, ':e' => $this->stats['events'], ':m' => $this->stats['markets'],
            ':sl' => $this->stats['selections'], ':err' => $error, ':d' => $durationMs, ':id' => $id,
        ]);
    }
}
