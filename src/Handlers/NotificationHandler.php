<?php
/**
 * NotificationHandler
 * 
 * Handles notifications for employees
 */

namespace App\Handlers;

use App\Core\Database;

class NotificationHandler
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get notifications for user (by user_id or en_no)
     */
    public function getNotifications($userId = null, $enNo = null)
    {
        if ($userId !== null) {
            return $this->db->fetchAll(
                "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC",
                [$userId]
            );
        } elseif ($enNo !== null) {
            return $this->db->fetchAll(
                "SELECT * FROM notifications WHERE en_no = ? ORDER BY created_at DESC",
                [$enNo]
            );
        }
        return [];
    }

    /**
     * Send notification to user
     */
    public function sendNotification($message, $userId = null, $enNo = null)
    {
        return $this->db->query(
            "INSERT INTO notifications (user_id, en_no, message) VALUES (?, ?, ?)",
            [$userId, $enNo, $message]
        );
    }

    /**
     * Send notification to all users with specific role
     */
    public function sendNotificationToRole($message, $role)
    {
        $users = $this->db->fetchAll(
            "SELECT id FROM users WHERE role = ?",
            [$role]
        );

        foreach ($users as $user) {
            $this->sendNotification($message, $user['id'], null);
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead($id, $userId = null, $enNo = null)
    {
        if ($userId !== null) {
            $this->db->query(
                "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?",
                [$id, $userId]
            );
        } elseif ($enNo !== null) {
            $this->db->query(
                "UPDATE notifications SET is_read = 1 WHERE id = ? AND en_no = ?",
                [$id, $enNo]
            );
        }
    }

    /**
     * Get unread count
     */
    public function getUnreadCount($userId = null, $enNo = null)
    {
        if ($userId !== null) {
            return $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0",
                [$userId]
            )['count'];
        } elseif ($enNo !== null) {
            return $this->db->fetchOne(
                "SELECT COUNT(*) as count FROM notifications WHERE en_no = ? AND is_read = 0",
                [$enNo]
            )['count'];
        }
        return 0;
    }
}