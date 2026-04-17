<?php

declare(strict_types=1);

namespace App\Admin;

use PDO;

/**
 * AgentManager — Agent CRUD, scoped agent-user hierarchy, bet monitoring, role management.
 *
 * Hierarchy: Users register with an agent_code → users.referred_by_agent = agent.id
 * Agents only see their own referred users. Super admin sees everything.
 */
class AgentManager
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    // ═══════════════════════════════════════════
    //  AGENT CRUD (Super Admin operations)
    // ═══════════════════════════════════════════

    public function createAgent(array $data, int $createdBy): array
    {
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        $phone    = trim($data['phone'] ?? '');

        if (strlen($username) < 3 || strlen($username) > 50) {
            throw new \InvalidArgumentException('Username must be 3-50 characters');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            throw new \InvalidArgumentException('Username can only contain letters, numbers, underscores');
        }
        if (strlen($password) < 6) {
            throw new \InvalidArgumentException('Password must be at least 6 characters');
        }

        $check = $this->db->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetch()) {
            throw new \InvalidArgumentException('Username already taken');
        }

        $agentCode = $this->generateAgentCode();
        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $stmt = $this->db->prepare("
            INSERT INTO users (username, password_hash, phone, agent_code, agent_status, balance, role, created_by)
            VALUES (?, ?, ?, ?, 'active', 0.000, 'agent', ?)
        ");
        $stmt->execute([$username, $hash, $phone ?: null, $agentCode, $createdBy]);

        return $this->getAgent((int)$this->db->lastInsertId());
    }

    public function updateAgent(int $agentId, array $data): array
    {
        $agent = $this->getAgent($agentId);
        if (!$agent) {
            throw new \RuntimeException('Agent not found');
        }

        $fields = [];
        $params = [];

        if (isset($data['phone'])) {
            $fields[] = "phone = ?";
            $params[] = $data['phone'] ?: null;
        }
        if (isset($data['agent_status']) && in_array($data['agent_status'], ['active', 'suspended'])) {
            $fields[] = "agent_status = ?";
            $params[] = $data['agent_status'];
        }
        if (!empty($data['password']) && strlen($data['password']) >= 6) {
            $fields[] = "password_hash = ?";
            $params[] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
        }

        if (empty($fields)) return $agent;

        $params[] = $agentId;
        $this->db->prepare("UPDATE users SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);

        return $this->getAgent($agentId);
    }

    public function getAgent(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT id, username, phone, agent_code, agent_status, balance, role, banned, created_at, created_by
            FROM users
            WHERE id = ? AND role = 'agent'
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function getAllAgents(): array
    {
        $stmt = $this->db->query("
            SELECT u.id, u.username, u.phone, u.agent_code, u.agent_status, u.balance,
                   u.banned, u.created_at,
                   (SELECT COUNT(*) FROM users r WHERE r.referred_by_agent = u.id) AS user_count,
                   (SELECT COUNT(*) FROM payment_methods pm WHERE pm.agent_id = u.id) AS method_count,
                   (SELECT COUNT(*) FROM deposit_requests dr WHERE dr.agent_id = u.id) AS total_requests,
                   (SELECT COUNT(*) FROM deposit_requests dr WHERE dr.agent_id = u.id AND dr.status = 'pending') AS pending_requests,
                   (SELECT COALESCE(SUM(dr.amount), 0) FROM deposit_requests dr WHERE dr.agent_id = u.id AND dr.status = 'approved') AS total_approved
            FROM users u
            WHERE u.role = 'agent'
            ORDER BY u.created_at DESC
        ");
        return $stmt->fetchAll();
    }

    public function toggleAgentBan(int $agentId, bool $banned): bool
    {
        $agent = $this->getAgent($agentId);
        if (!$agent) {
            throw new \RuntimeException('Agent not found');
        }

        $this->db->prepare("UPDATE users SET banned = ? WHERE id = ? AND role = 'agent'")
            ->execute([$banned ? 1 : 0, $agentId]);

        return true;
    }

    public function setAgentStatus(int $agentId, string $status): bool
    {
        if (!in_array($status, ['active', 'suspended'])) {
            throw new \InvalidArgumentException('Invalid status');
        }

        $this->db->prepare("UPDATE users SET agent_status = ? WHERE id = ? AND role = 'agent'")
            ->execute([$status, $agentId]);

        return true;
    }

    // ═══════════════════════════════════════════
    //  USER CREATION (Super Admin)
    // ═══════════════════════════════════════════

    public function createUser(array $data, int $createdBy): array
    {
        $username = trim($data['username'] ?? '');
        $password = $data['password'] ?? '';
        $phone    = trim($data['phone'] ?? '');
        $role     = $data['role'] ?? 'user';
        $agentId  = (int)($data['referred_by_agent'] ?? 0);

        if (strlen($username) < 3 || strlen($username) > 50) {
            throw new \InvalidArgumentException('Username must be 3-50 characters');
        }
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            throw new \InvalidArgumentException('Username can only contain letters, numbers, underscores');
        }
        if (strlen($password) < 6) {
            throw new \InvalidArgumentException('Password must be at least 6 characters');
        }
        if (!in_array($role, ['user', 'agent', 'admin'])) {
            throw new \InvalidArgumentException('Invalid role');
        }

        $check = $this->db->prepare("SELECT id FROM users WHERE username = ?");
        $check->execute([$username]);
        if ($check->fetch()) {
            throw new \InvalidArgumentException('Username already taken');
        }

        $hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

        $agentCode = null;
        $agentStatus = null;
        if ($role === 'agent') {
            $agentCode = $this->generateAgentCode();
            $agentStatus = 'active';
        }

        $stmt = $this->db->prepare("
            INSERT INTO users (username, password_hash, phone, agent_code, agent_status, balance, role, created_by, referred_by_agent, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 0.000, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $username,
            $hash,
            $phone ?: null,
            $agentCode,
            $agentStatus,
            $role,
            $createdBy,
            $agentId ?: null
        ]);

        $newId = (int)$this->db->lastInsertId();

        // Return the created user
        $stmt = $this->db->prepare("
            SELECT id, username, phone, balance, role, agent_code, agent_status, banned, created_at
            FROM users WHERE id = ?
        ");
        $stmt->execute([$newId]);
        return $stmt->fetch();
    }

    // ═══════════════════════════════════════════
    //  SCOPED USER HIERARCHY
    // ═══════════════════════════════════════════

    /**
     * Get users referred by a specific agent (agent's own users).
     */
    public function getAgentUsers(int $agentId, int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare("
            SELECT u.id, u.username, u.balance, u.role, u.banned, u.created_at,
                   (SELECT COUNT(*) FROM bets b WHERE b.user_id = u.id) AS bet_count,
                   (SELECT COALESCE(SUM(b.stake), 0) FROM bets b WHERE b.user_id = u.id) AS total_wagered,
                   (SELECT COALESCE(SUM(dr.amount), 0) FROM deposit_requests dr WHERE dr.user_id = u.id AND dr.status = 'approved') AS total_deposits
            FROM users u
            WHERE u.referred_by_agent = ?
            ORDER BY u.id DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$agentId, $limit, $offset]);
        return $stmt->fetchAll();
    }

    /**
     * Count users referred by a specific agent.
     */
    public function getAgentUserCount(int $agentId): int
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM users WHERE referred_by_agent = ?");
        $stmt->execute([$agentId]);
        return (int)$stmt->fetchColumn();
    }

    // ═══════════════════════════════════════════
    //  USER / ROLE MANAGEMENT (Super Admin)
    // ═══════════════════════════════════════════

    public function getAllUsers(int $limit = 100, int $offset = 0): array
    {
        $stmt = $this->db->prepare("
            SELECT u.id, u.username, u.balance, u.role, u.banned, u.created_at,
                   u.referred_by_agent,
                   COALESCE(a.username, 'House') AS agent_name,
                   (SELECT COUNT(*) FROM bets b WHERE b.user_id = u.id) AS bet_count,
                   (SELECT COALESCE(SUM(b.stake), 0) FROM bets b WHERE b.user_id = u.id) AS total_wagered,
                   (SELECT COALESCE(SUM(dr.amount), 0) FROM deposit_requests dr WHERE dr.user_id = u.id AND dr.status = 'approved') AS total_deposits
            FROM users u
            LEFT JOIN users a ON a.id = u.referred_by_agent
            ORDER BY u.id DESC
            LIMIT ? OFFSET ?
        ");
        $stmt->execute([$limit, $offset]);
        return $stmt->fetchAll();
    }

    public function changeUserRole(int $userId, string $newRole, int $changedBy): bool
    {
        $validRoles = ['user', 'agent', 'admin', 'super_admin'];
        if (!in_array($newRole, $validRoles)) {
            throw new \InvalidArgumentException('Invalid role');
        }

        $user = $this->db->prepare("SELECT id, role FROM users WHERE id = ?");
        $user->execute([$userId]);
        $row = $user->fetch();

        if (!$row) {
            throw new \RuntimeException('User not found');
        }
        if ($userId === $changedBy) {
            throw new \RuntimeException('Cannot change your own role');
        }

        $this->db->prepare("UPDATE users SET role = ? WHERE id = ?")->execute([$newRole, $userId]);

        if ($newRole === 'agent' && !$this->getAgentCode($userId)) {
            $code = $this->generateAgentCode();
            $this->db->prepare("UPDATE users SET agent_code = ?, agent_status = 'active' WHERE id = ?")
                ->execute([$code, $userId]);
        }

        return true;
    }

    // ═══════════════════════════════════════════
    //  BET MONITORING
    // ═══════════════════════════════════════════

    /**
     * Get bets for an agent's users (scoped view).
     */
    public function getAgentBets(int $agentId, ?string $status = null, int $limit = 100, int $offset = 0): array
    {
        $sql = "
            SELECT b.*, u.username AS user_name
            FROM bets b
            JOIN users u ON u.id = b.user_id
            WHERE u.referred_by_agent = ?
        ";
        $params = [$agentId];

        if ($status) {
            $sql .= " AND b.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY b.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get all bets (super admin — global view).
     */
    public function getAllBets(?string $status = null, int $limit = 100, int $offset = 0): array
    {
        $sql = "
            SELECT b.*, u.username AS user_name,
                   COALESCE(a.username, 'House') AS agent_name
            FROM bets b
            JOIN users u ON u.id = b.user_id
            LEFT JOIN users a ON a.id = u.referred_by_agent
        ";
        $params = [];

        if ($status) {
            $sql .= " WHERE b.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY b.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Void a bet (cancel — refund stake to user).
     */
    public function voidBet(int $betId, int $adminId, WalletManager $wallet): array
    {
        $stmt = $this->db->prepare("SELECT b.*, u.username FROM bets b JOIN users u ON u.id = b.user_id WHERE b.id = ?");
        $stmt->execute([$betId]);
        $bet = $stmt->fetch();

        if (!$bet) {
            throw new \RuntimeException('Bet not found');
        }
        if ($bet['status'] !== 'pending') {
            throw new \RuntimeException('Only pending bets can be voided');
        }

        $this->db->beginTransaction();
        try {
            // Refund stake
            $wallet->credit(
                (int)$bet['user_id'],
                (float)$bet['stake'],
                'bet_void',
                $betId,
                "Bet #{$betId} voided — stake refunded",
                $adminId,
                'refund',
                "Void bet #{$betId} for {$bet['username']}"
            );

            $this->db->prepare("UPDATE bets SET status = 'void', settled_at = NOW() WHERE id = ?")
                ->execute([$betId]);

            $this->db->commit();

            $stmt = $this->db->prepare("SELECT b.*, u.username AS user_name FROM bets b JOIN users u ON u.id = b.user_id WHERE b.id = ?");
            $stmt->execute([$betId]);
            return $stmt->fetch();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    // ═══════════════════════════════════════════
    //  GLOBAL STATS (Super Admin)
    // ═══════════════════════════════════════════

    public function getGlobalStats(): array
    {
        $stmt = $this->db->query("
            SELECT
                (SELECT COUNT(*) FROM users WHERE role = 'user') AS total_users,
                (SELECT COUNT(*) FROM users WHERE role = 'agent') AS total_agents,
                (SELECT COALESCE(SUM(balance), 0) FROM users WHERE role = 'user') AS total_user_balance,
                (SELECT COALESCE(SUM(balance), 0) FROM users WHERE role = 'agent') AS total_agent_balance,
                (SELECT COUNT(*) FROM bets) AS total_bets,
                (SELECT COALESCE(SUM(stake), 0) FROM bets) AS total_wagered,
                (SELECT COUNT(*) FROM bets WHERE status = 'pending') AS pending_bets,
                (SELECT COUNT(*) FROM deposit_requests WHERE status = 'pending') AS pending_deposits,
                (SELECT COUNT(*) FROM withdrawal_requests WHERE status = 'pending') AS pending_withdrawals,
                (SELECT COALESCE(SUM(amount), 0) FROM deposit_requests WHERE status = 'approved') AS total_deposits_approved,
                (SELECT COALESCE(SUM(amount), 0) FROM withdrawal_requests WHERE status = 'approved') AS total_withdrawals_approved
        ");
        return $stmt->fetch();
    }

    /**
     * Stats for a specific agent's scope.
     */
    public function getAgentScopedStats(int $agentId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                (SELECT COUNT(*) FROM users WHERE referred_by_agent = ?) AS user_count,
                (SELECT COALESCE(SUM(balance), 0) FROM users WHERE referred_by_agent = ?) AS total_user_balance,
                (SELECT COUNT(*) FROM bets b JOIN users u ON u.id = b.user_id WHERE u.referred_by_agent = ?) AS total_bets,
                (SELECT COALESCE(SUM(b.stake), 0) FROM bets b JOIN users u ON u.id = b.user_id WHERE u.referred_by_agent = ?) AS total_wagered,
                (SELECT COUNT(*) FROM bets b JOIN users u ON u.id = b.user_id WHERE u.referred_by_agent = ? AND b.status = 'pending') AS pending_bets
        ");
        $stmt->execute([$agentId, $agentId, $agentId, $agentId, $agentId]);
        return $stmt->fetch();
    }

    // ═══════════════════════════════════════════
    //  HELPERS
    // ═══════════════════════════════════════════

    private function generateAgentCode(): string
    {
        do {
            $code = 'AGT' . str_pad((string)random_int(100, 99999), 5, '0', STR_PAD_LEFT);
            $check = $this->db->prepare("SELECT id FROM users WHERE agent_code = ?");
            $check->execute([$code]);
        } while ($check->fetch());

        return $code;
    }

    private function getAgentCode(int $userId): ?string
    {
        $stmt = $this->db->prepare("SELECT agent_code FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $row = $stmt->fetch();
        return $row ? $row['agent_code'] : null;
    }
}
