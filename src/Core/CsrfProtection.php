<?php

declare(strict_types=1);

namespace App\Core;

/**
 * CsrfProtection — Double-submit token pattern for AJAX + form-based requests.
 *
 * Usage:
 *   $csrf = new CsrfProtection();
 *   $token = $csrf->getToken();          // embed in meta tag / hidden field
 *   $csrf->validate($requestToken);      // throws on mismatch
 */
class CsrfProtection
{
    private const TOKEN_LENGTH = 32;
    private const SESSION_KEY  = '_csrf_token';
    private const HEADER_NAME  = 'X-CSRF-Token';

    public function __construct()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            throw new \RuntimeException('Session must be started before using CSRF protection');
        }
    }

    /**
     * Get or generate the CSRF token for this session.
     */
    public function getToken(): string
    {
        if (empty($_SESSION[self::SESSION_KEY])) {
            $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        }
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Regenerate the token (call after login/privilege change).
     */
    public function regenerate(): string
    {
        $_SESSION[self::SESSION_KEY] = bin2hex(random_bytes(self::TOKEN_LENGTH));
        return $_SESSION[self::SESSION_KEY];
    }

    /**
     * Validate a submitted token against the session token.
     * Checks: POST body `_csrf_token`, or Header `X-CSRF-Token`.
     *
     * @throws \RuntimeException on invalid/missing token
     */
    public function validate(?string $token = null): void
    {
        if ($token === null) {
            // Try header first, then POST body
            $token = $_SERVER['HTTP_' . str_replace('-', '_', strtoupper(self::HEADER_NAME))] ?? null;
            if ($token === null) {
                $token = $_POST['_csrf_token'] ?? null;
            }
            if ($token === null) {
                // Try JSON body
                $input = json_decode(file_get_contents('php://input') ?: '', true);
                $token = $input['_csrf_token'] ?? null;
            }
        }

        $sessionToken = $_SESSION[self::SESSION_KEY] ?? null;

        if (empty($token) || empty($sessionToken) || !hash_equals($sessionToken, $token)) {
            throw new \RuntimeException('CSRF token validation failed');
        }
    }

    /**
     * Generate HTML meta tag for embedding in page <head>.
     */
    public function metaTag(): string
    {
        $token = htmlspecialchars($this->getToken(), ENT_QUOTES, 'UTF-8');
        return '<meta name="csrf-token" content="' . $token . '">';
    }

    /**
     * Generate hidden input field for forms.
     */
    public function hiddenField(): string
    {
        $token = htmlspecialchars($this->getToken(), ENT_QUOTES, 'UTF-8');
        return '<input type="hidden" name="_csrf_token" value="' . $token . '">';
    }
}
