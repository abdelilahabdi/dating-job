<?php

namespace Src\Core;

class Security
{
    /*
     * Les méthodes de hashage et verfication de mots de passes
     */
    public static function hashPassword($password)
    {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verifyPassword($password, $hashedPassword)
    {
        return password_verify($password, $hashedPassword);
    }

    /*
     * Les méthodes pour générer et verifier CSRF tokens
     */

    public static function generateCSRFToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function verifyCSRFToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (
            empty($token) ||
            empty($_SESSION['csrf_token']) ||
            !hash_equals($_SESSION['csrf_token'], $token)
        ) {
            return false;
        }

        return true;
    }

    public static function invalidateCSRFToken()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        unset($_SESSION['csrf_token']);
    }

    /*
     * méthode de sanitisation des inputs.
     */

    public static function sanitize(mixed $data): array|string
    {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }

        $data = htmlspecialchars((string) $data);
        $data = htmlentities((string) $data);
        $data = strip_tags((string) $data, true);
        $data = stripcslashes((string) $data);
        $data = trim((string) $data);
        return $data;

    }
}