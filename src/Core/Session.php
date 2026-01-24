<?php

namespace Src\Core;

class Session
{
    private function __construct()
    {
    }

    public static function getSessionInstance(): array
    {
        self::start();
        return $_SESSION;
    }


    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_only_cookies', 1);

            session_start();
        }
    }
    public static function set(string $key, $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }
    public static function get(string $key, $default = null)
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    public static function all(): array
    {
        self::start();
        return $_SESSION;
    }
    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }
    public static function unset(string $key): void
    {
        self::start();
        if (isset($_SESSION[$key])) {
            unset($_SESSION[$key]);
        }
    }
    public static function destroy(): void
    {
        if (session_status() !== PHP_SESSION_NONE) {
            session_unset();
            session_destroy();

            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(
                    session_name(),
                    '',
                    time() - 42000,
                    $params["path"],
                    $params["domain"],
                    $params["secure"],
                    $params["httponly"]
                );
            }
        }
    }

    public static function regenerate(): void
    {
        self::start();
        session_regenerate_id(true);
    }

    public static function flash(string $key, $value = null)
    {
        self::start();
        
        if ($value === null) {
            // Récupérer et supprimer la valeur flash
            if (strpos($key, '.') !== false) {
                $parts = explode('.', $key);
                $flashKey = $parts[0];
                $subKey = $parts[1] ?? null;
                
                if ($subKey && isset($_SESSION['_flash'][$flashKey][$subKey])) {
                    $value = $_SESSION['_flash'][$flashKey][$subKey];
                    unset($_SESSION['_flash'][$flashKey][$subKey]);
                    return $value;
                }
            }
            
            if (isset($_SESSION['_flash'][$key])) {
                $value = $_SESSION['_flash'][$key];
                unset($_SESSION['_flash'][$key]);
                return $value;
            }
            
            return null;
        }
        
        // Définir la valeur flash
        if (strpos($key, '.') !== false) {
            $parts = explode('.', $key);
            $flashKey = $parts[0];
            $subKey = $parts[1] ?? null;
            
            if ($subKey) {
                $_SESSION['_flash'][$flashKey][$subKey] = $value;
            } else {
                $_SESSION['_flash'][$flashKey] = $value;
            }
        } else {
            $_SESSION['_flash'][$key] = $value;
        }
    }
}
