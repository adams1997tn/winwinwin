<?php

declare(strict_types=1);

namespace App\Admin;

use PDO;

/**
 * PaymentManager — Dynamic payment methods (JSON field_schema) + deposit request processing.
 *
 * field_schema example stored in payment_methods.field_schema JSON column:
 * [
 *   {"name":"sender_name","label":"Sender Name","type":"text","required":true},
 *   {"name":"auth_number","label":"Auth Number","type":"text","required":true},
 *   {"name":"sender_phone","label":"Phone","type":"text","required":false,"placeholder":"Optional"}
 * ]
 *
 * When a user submits a deposit, their answers are stored as JSON in deposit_requests.submitted_data:
 * {"sender_name":"John Doe","auth_number":"ABC123","sender_phone":"555-0100"}
 */
class PaymentManager
{
    private PDO $db;
    private WalletManager $wallet;

    public function __construct(PDO $db, ?WalletManager $wallet = null)
    {
        $this->db = $db;
        $this->wallet = $wallet ?? new WalletManager($db);
    }

    // ═══════════════════════════════════════════
    //  PAYMENT METHODS  (with JSON field_schema)
    // ═══════════════════════════════════════════

    /**
     * Create a new payment method with optional dynamic field schema.
     */
    public function createPaymentMethod(int $agentId, array $data): array
    {
        $fieldSchema = $this->sanitizeFieldSchema($data['field_schema'] ?? []);

        $stmt = $this->db->prepare("
            INSERT INTO payment_methods
                (agent_id, name, type, logo_url, account_info, instructions, min_amount, max_amount, field_schema, display_order, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $agentId,
            trim($data['name']),
            $data['type'] ?? 'other',
            $data['logo_url'] ?? null,
            $data['account_info'] ?? '',
            $data['instructions'] ?? null,
            round((float)($data['min_amount'] ?? 10), 3),
            round((float)($data['max_amount'] ?? 10000), 3),
            json_encode($fieldSchema, JSON_UNESCAPED_UNICODE),
            (int)($data['display_order'] ?? 0),
            $data['status'] ?? 'active',
        ]);

        return $this->getPaymentMethod((int)$this->db->lastInsertId());
    }

    /**
     * Update an existing payment method (owner-scoped).
     */
    public function updatePaymentMethod(int $methodId, int $agentId, array $data): array
    {
        $method = $this->getPaymentMethod($methodId);
        if (!$method || (int)$method['agent_id'] !== $agentId) {
            throw new \RuntimeException('Payment method not found or access denied');
        }

        $fields = [];
        $params = [];
        $allowed = ['name', 'type', 'logo_url', 'account_info', 'instructions', 'min_amount', 'max_amount', 'display_order', 'status'];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                $fields[] = "`{$field}` = ?";
                $params[] = $data[$field];
            }
        }

        // Handle field_schema separately (needs JSON encoding + sanitization)
        if (array_key_exists('field_schema', $data)) {
            $fields[] = "`field_schema` = ?";
            $params[] = json_encode($this->sanitizeFieldSchema($data['field_schema'] ?? []), JSON_UNESCAPED_UNICODE);
        }

        if (empty($fields)) {
            return $method;
        }

        $params[] = $methodId;
        $this->db->prepare("UPDATE payment_methods SET " . implode(', ', $fields) . " WHERE id = ?")->execute($params);

        return $this->getPaymentMethod($methodId);
    }

    /**
     * Get a single payment method by ID (decodes field_schema JSON).
     */
    public function getPaymentMethod(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM payment_methods WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $row['field_schema'] = json_decode($row['field_schema'] ?? '[]', true) ?: [];
        return $row;
    }

    /**
     * Get all payment methods for an agent.
     */
    public function getAgentPaymentMethods(int $agentId, ?string $status = null): array
    {
        $sql = "SELECT * FROM payment_methods WHERE agent_id = ?";
        $params = [$agentId];

        if ($status) {
            $sql .= " AND status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY display_order ASC, created_at DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['field_schema'] = json_decode($r['field_schema'] ?? '[]', true) ?: [];
        }
        return $rows;
    }

    /**
     * Get all active payment methods (for user deposit page).
     */
    public function getActivePaymentMethods(): array
    {
        $stmt = $this->db->query("
            SELECT pm.*, u.username AS agent_name, u.agent_code
            FROM payment_methods pm
            JOIN users u ON u.id = pm.agent_id
            WHERE pm.status = 'active'
              AND u.agent_status = 'active'
              AND u.banned = 0
            ORDER BY pm.display_order ASC, pm.name ASC
        ");
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['field_schema'] = json_decode($r['field_schema'] ?? '[]', true) ?: [];
        }
        return $rows;
    }

    /**
     * Soft-delete a payment method.
     */
    public function deletePaymentMethod(int $methodId, int $agentId): bool
    {
        $stmt = $this->db->prepare("UPDATE payment_methods SET status = 'inactive' WHERE id = ? AND agent_id = ?");
        $stmt->execute([$methodId, $agentId]);
        return $stmt->rowCount() > 0;
    }

    // ═══════════════════════════════════════════
    //  DYNAMIC FIELD VALIDATION
    // ═══════════════════════════════════════════

    /**
     * Validate user-submitted data against the method's field_schema.
     *
     * @return array Sanitized submitted data (only known fields kept)
     * @throws \InvalidArgumentException on missing required fields
     */
    public function validateSubmittedData(array $fieldSchema, array $submittedData): array
    {
        $clean = [];

        foreach ($fieldSchema as $field) {
            $name  = $field['name'] ?? '';
            $label = $field['label'] ?? $name;
            $required = !empty($field['required']);
            $value = trim((string)($submittedData[$name] ?? ''));

            if ($required && $value === '') {
                throw new \InvalidArgumentException("'{$label}' is required");
            }

            if ($value !== '') {
                // Enforce max length to prevent abuse
                $clean[$name] = mb_substr($value, 0, 500);
            }
        }

        return $clean;
    }

    // ═══════════════════════════════════════════
    //  DEPOSIT REQUESTS  (with submitted_data JSON)
    // ═══════════════════════════════════════════

    /**
     * Create a deposit request with dynamic field data.
     */
    public function createDepositRequest(int $userId, array $data): array
    {
        $method = $this->getPaymentMethod((int)$data['method_id']);
        if (!$method || $method['status'] !== 'active') {
            throw new \InvalidArgumentException('Payment method is not available');
        }

        $amount = round((float)$data['amount'], 3);
        if ($amount < (float)$method['min_amount'] || $amount > (float)$method['max_amount']) {
            throw new \InvalidArgumentException(
                "Amount must be between {$method['min_amount']} and {$method['max_amount']}"
            );
        }

        $transactionCode = trim($data['transaction_code'] ?? '');
        if (empty($transactionCode)) {
            throw new \InvalidArgumentException('Transaction code is required');
        }

        // Duplicate check
        $dup = $this->db->prepare("SELECT id FROM deposit_requests WHERE transaction_code = ?");
        $dup->execute([$transactionCode]);
        if ($dup->fetch()) {
            throw new \InvalidArgumentException('This transaction code has already been used');
        }

        // Validate dynamic fields against schema
        $submittedData = [];
        if (!empty($method['field_schema'])) {
            $submittedData = $this->validateSubmittedData(
                $method['field_schema'],
                $data['submitted_data'] ?? []
            );
        }

        $stmt = $this->db->prepare("
            INSERT INTO deposit_requests (user_id, agent_id, method_id, amount, transaction_code, screenshot_path, submitted_data, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        $stmt->execute([
            $userId,
            (int)$method['agent_id'],
            (int)$method['id'],
            $amount,
            $transactionCode,
            $data['screenshot_path'] ?? null,
            json_encode($submittedData, JSON_UNESCAPED_UNICODE),
        ]);

        return $this->getDepositRequest((int)$this->db->lastInsertId());
    }

    /**
     * Get a single deposit request (decodes submitted_data).
     */
    public function getDepositRequest(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT dr.*, u.username AS user_name, pm.name AS method_name,
                   pm.field_schema AS method_field_schema,
                   a.username AS agent_name
            FROM deposit_requests dr
            JOIN users u ON u.id = dr.user_id
            JOIN payment_methods pm ON pm.id = dr.method_id
            JOIN users a ON a.id = dr.agent_id
            WHERE dr.id = ?
        ");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        if (!$row) return null;
        $row['submitted_data'] = json_decode($row['submitted_data'] ?? '{}', true) ?: [];
        $row['method_field_schema'] = json_decode($row['method_field_schema'] ?? '[]', true) ?: [];
        return $row;
    }

    /**
     * Get deposit requests for an agent (pending queue + history).
     */
    public function getAgentDepositRequests(int $agentId, ?string $status = null, int $limit = 50, int $offset = 0): array
    {
        $sql = "
            SELECT dr.*, u.username AS user_name, pm.name AS method_name,
                   dr.submitted_data
            FROM deposit_requests dr
            JOIN users u ON u.id = dr.user_id
            JOIN payment_methods pm ON pm.id = dr.method_id
            WHERE dr.agent_id = ?
        ";
        $params = [$agentId];

        if ($status) {
            $sql .= " AND dr.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY dr.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['submitted_data'] = json_decode($r['submitted_data'] ?? '{}', true) ?: [];
        }
        return $rows;
    }

    /**
     * Get deposit requests for a user (their history).
     */
    public function getUserDepositRequests(int $userId, int $limit = 50): array
    {
        $stmt = $this->db->prepare("
            SELECT dr.*, pm.name AS method_name, a.username AS agent_name,
                   dr.submitted_data
            FROM deposit_requests dr
            JOIN payment_methods pm ON pm.id = dr.method_id
            JOIN users a ON a.id = dr.agent_id
            WHERE dr.user_id = ?
            ORDER BY dr.created_at DESC
            LIMIT ?
        ");
        $stmt->execute([$userId, $limit]);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['submitted_data'] = json_decode($r['submitted_data'] ?? '{}', true) ?: [];
        }
        return $rows;
    }

    /**
     * Get ALL deposit requests (super admin).
     */
    public function getAllDepositRequests(?string $status = null, int $limit = 100, int $offset = 0): array
    {
        $sql = "
            SELECT dr.*, u.username AS user_name, pm.name AS method_name,
                   a.username AS agent_name, dr.submitted_data
            FROM deposit_requests dr
            JOIN users u ON u.id = dr.user_id
            JOIN payment_methods pm ON pm.id = dr.method_id
            JOIN users a ON a.id = dr.agent_id
        ";
        $params = [];

        if ($status) {
            $sql .= " WHERE dr.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY dr.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        foreach ($rows as &$r) {
            $r['submitted_data'] = json_decode($r['submitted_data'] ?? '{}', true) ?: [];
        }
        return $rows;
    }

    // ═══════════════════════════════════════════
    //  APPROVE / REJECT  (uses WalletManager)
    // ═══════════════════════════════════════════

    /**
     * Approve a deposit — delegates balance mutation to WalletManager for full ledger trail.
     */
    public function approveDeposit(int $requestId, int $processedBy): array
    {
        $request = $this->getDepositRequest($requestId);
        if (!$request) {
            throw new \RuntimeException('Deposit request not found');
        }
        if ($request['status'] !== 'pending') {
            throw new \RuntimeException('This request has already been processed');
        }

        $this->db->beginTransaction();
        try {
            // Credit via WalletManager (handles locking, ledger, transaction)
            $this->wallet->credit(
                (int)$request['user_id'],
                (float)$request['amount'],
                'deposit',
                $requestId,
                "Deposit approved: {$request['method_name']} (Code: {$request['transaction_code']})",
                $processedBy,
                'agent_deposit',
                "Deposit via {$request['method_name']} (Code: {$request['transaction_code']})"
            );

            // Mark approved
            $this->db->prepare("
                UPDATE deposit_requests SET status = 'approved', processed_by = ?, processed_at = NOW() WHERE id = ?
            ")->execute([$processedBy, $requestId]);

            $this->db->commit();
            return $this->getDepositRequest($requestId);
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /**
     * Reject a deposit request.
     */
    public function rejectDeposit(int $requestId, int $processedBy, string $reason = ''): array
    {
        $request = $this->getDepositRequest($requestId);
        if (!$request) {
            throw new \RuntimeException('Deposit request not found');
        }
        if ($request['status'] !== 'pending') {
            throw new \RuntimeException('This request has already been processed');
        }

        $this->db->prepare("
            UPDATE deposit_requests
            SET status = 'rejected', rejection_reason = ?, processed_by = ?, processed_at = NOW()
            WHERE id = ?
        ")->execute([$reason ?: null, $processedBy, $requestId]);

        return $this->getDepositRequest($requestId);
    }

    // ═══════════════════════════════════════════
    //  STATISTICS & CHARTS
    // ═══════════════════════════════════════════

    public function getAgentDepositStats(int $agentId): array
    {
        $stmt = $this->db->prepare("
            SELECT
                COUNT(*) AS total_requests,
                COALESCE(SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END), 0) AS pending_count,
                COALESCE(SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END), 0) AS approved_count,
                COALESCE(SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END), 0) AS rejected_count,
                COALESCE(SUM(CASE WHEN status='approved' THEN amount ELSE 0 END), 0) AS total_approved_amount,
                COALESCE(SUM(CASE WHEN status='pending' THEN amount ELSE 0 END), 0) AS total_pending_amount
            FROM deposit_requests
            WHERE agent_id = ?
        ");
        $stmt->execute([$agentId]);
        return $stmt->fetch();
    }

    public function getGlobalDepositStats(): array
    {
        $stmt = $this->db->query("
            SELECT
                COUNT(*) AS total_requests,
                COALESCE(SUM(CASE WHEN status='pending' THEN 1 ELSE 0 END), 0) AS pending_count,
                COALESCE(SUM(CASE WHEN status='approved' THEN 1 ELSE 0 END), 0) AS approved_count,
                COALESCE(SUM(CASE WHEN status='rejected' THEN 1 ELSE 0 END), 0) AS rejected_count,
                COALESCE(SUM(CASE WHEN status='approved' THEN amount ELSE 0 END), 0) AS total_approved_amount
            FROM deposit_requests
        ");
        return $stmt->fetch();
    }

    public function getDepositChartData(int $days = 7, ?int $agentId = null): array
    {
        $sql = "
            SELECT DATE(created_at) AS day,
                   COALESCE(SUM(CASE WHEN status='approved' THEN amount ELSE 0 END), 0) AS approved,
                   COALESCE(SUM(CASE WHEN status='rejected' THEN amount ELSE 0 END), 0) AS rejected,
                   COUNT(*) AS total
            FROM deposit_requests
            WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
        ";
        $params = [$days - 1];

        if ($agentId) {
            $sql .= " AND agent_id = ?";
            $params[] = $agentId;
        }

        $sql .= " GROUP BY DATE(created_at) ORDER BY day";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        $labels = [];
        $approved = [];
        $rejected = [];
        $counts = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $day = date('Y-m-d', strtotime("-{$i} days"));
            $labels[] = date('D d', strtotime("-{$i} days"));
            $found = false;
            foreach ($rows as $row) {
                if ($row['day'] === $day) {
                    $approved[] = (float)$row['approved'];
                    $rejected[] = (float)$row['rejected'];
                    $counts[] = (int)$row['total'];
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $approved[] = 0;
                $rejected[] = 0;
                $counts[] = 0;
            }
        }

        return compact('labels', 'approved', 'rejected', 'counts');
    }

    // ═══════════════════════════════════════════
    //  SCREENSHOT UPLOAD
    // ═══════════════════════════════════════════

    public function handleScreenshotUpload(array $file, string $uploadDir): string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException('File upload failed');
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            throw new \InvalidArgumentException('File too large. Maximum 5MB allowed');
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        if (!in_array($mimeType, $allowedTypes, true)) {
            throw new \InvalidArgumentException('Invalid file type. Only JPEG, PNG, WebP, and GIF are allowed');
        }

        $imageInfo = @getimagesize($file['tmp_name']);
        if ($imageInfo === false) {
            throw new \InvalidArgumentException('Uploaded file is not a valid image');
        }

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ext = match ($mimeType) {
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            default      => 'jpg',
        };
        $filename = date('Ymd_His') . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $destPath = $uploadDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            throw new \RuntimeException('Failed to save uploaded file');
        }

        return $filename;
    }

    // ═══════════════════════════════════════════
    //  FIELD SCHEMA HELPERS
    // ═══════════════════════════════════════════

    /**
     * Sanitize and validate a field_schema array.
     * Strips unknown keys, enforces allowed field types.
     */
    private function sanitizeFieldSchema(array|string $schema): array
    {
        if (is_string($schema)) {
            $schema = json_decode($schema, true) ?: [];
        }

        $allowedTypes = ['text', 'number', 'email', 'tel', 'textarea', 'select'];
        $clean = [];

        foreach ($schema as $field) {
            if (!is_array($field) || empty($field['name']) || empty($field['label'])) {
                continue;
            }

            $entry = [
                'name'     => preg_replace('/[^a-zA-Z0-9_]/', '', $field['name']),
                'label'    => mb_substr(trim($field['label']), 0, 100),
                'type'     => in_array($field['type'] ?? 'text', $allowedTypes) ? $field['type'] : 'text',
                'required' => !empty($field['required']),
            ];

            if (isset($field['placeholder'])) {
                $entry['placeholder'] = mb_substr(trim($field['placeholder']), 0, 200);
            }
            if (isset($field['options']) && is_array($field['options'])) {
                $entry['options'] = array_slice(array_map(fn($o) => mb_substr(trim((string)$o), 0, 100), $field['options']), 0, 50);
            }

            $clean[] = $entry;
        }

        return $clean;
    }
}
