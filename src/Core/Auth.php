<?php
/**
 * Authentication Class
 * 
 * Handles user authentication and session management
 */

namespace App\Core;

class Auth
{
    private static $db;

    public function __construct()
    {
        self::$db = Database::getInstance();
    }

    /**
     * Check if user is logged in
     */
    public static function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    /**
     * Check if user needs to change password
     */
    public static function needsPasswordChange()
    {
        return isset($_SESSION['force_password_change']);
    }

    /**
     * Check if user has required role
     */
    public static function hasRole($roles)
    {
        if (!isset($_SESSION['role'])) {
            return false;
        }
        
        if (is_string($roles)) {
            $roles = [$roles];
        }
        
        return in_array($_SESSION['role'], $roles, true);
    }

    /**
     * Get current user ID
     */
    public static function getUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Get current user info
     */
    public static function getUser()
    {
        return [
            'id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'role' => $_SESSION['role'] ?? null,
            'en_no' => $_SESSION['en_no'] ?? null,
        ];
    }

    /**
     * Get current user role
     */
    public static function getRole()
    {
        return $_SESSION['role'] ?? null;
    }

    /**
     * Get employee number
     */
    public static function getEnNo()
    {
        return $_SESSION['en_no'] ?? null;
    }

    /**
     * Verify login credentials
     */
    public function verify($username, $password)
    {
        try {
            $stmt = self::$db->query(
                "SELECT id, username, password, role, en_no, requires_password_change FROM users WHERE username = ?",
                [$username]
            );
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['en_no'] = $user['en_no'];

                if ($user['requires_password_change'] == 1) {
                    $_SESSION['force_password_change'] = true;
                }

                return $user;
            }

            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Change password
     */
    public function changePassword($userId, $newPassword)
    {
        if (strlen($newPassword) < MIN_PASSWORD_LENGTH) {
            return ['success' => false, 'message' => 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters long.'];
        }

        if ($newPassword === DEFAULT_PASSWORD) {
            return ['success' => false, 'message' => 'You cannot use the default password.'];
        }

        try {
            $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
            self::$db->query(
                "UPDATE users SET password = ?, requires_password_change = 0 WHERE id = ?",
                [$hashed, $userId]
            );

            unset($_SESSION['force_password_change']);
            return ['success' => true, 'message' => 'Password changed successfully.'];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

    /**
     * Logout user
     */
    public static function logout()
    {
        $_SESSION = [];
        session_destroy();
        return true;
    }

    /**
     * Require login
     */
    public static function requireLogin()
    {
        if (!self::isLoggedIn()) {
            header("Location: " . APP_URL . "/public/login.php");
            exit;
        }
    }

    /**
     * Require role
     */
    public static function requireRole($roles)
    {
        if (!self::hasRole($roles)) {
            header("HTTP/1.1 403 Forbidden");
            exit('Access denied');
        }
    }

    /**
     * Require password change before accessing app
     */
    public static function requirePasswordChange()
    {
        if (self::isLoggedIn() && self::needsPasswordChange()) {
            header("Location: " . APP_URL . "/public/change-password.php");
            exit;
        }
    }
}
