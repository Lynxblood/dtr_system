<?php
/**
 * SubmissionHandler
 * 
 * Handles employee submissions of signed PDFs and HR approvals
 */

namespace App\Handlers;

use App\Core\Database;

class SubmissionHandler
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Submit signed PDF
     */
    public function submit($en_no, $month, $file)
    {
        // Upload file
        $uploadDir = __DIR__ . '/../../uploads/submissions/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $timestamp = time();
        $filename = pathinfo($file['name'], PATHINFO_FILENAME);
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        $filePath = $uploadDir . $en_no . '_' . $month . '_' . $timestamp . '_' . $safeName . ($extension ? '.' . $extension : '');

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            return ['status' => 'error', 'message' => 'File upload failed.'];
        }

        $existing = $this->db->fetchOne(
            "SELECT id FROM employee_submissions WHERE en_no = ? AND month = ?",
            [$en_no, $month]
        );

        if ($existing) {
            $this->db->query(
                "UPDATE employee_submissions SET file_path = ?, status = 'pending', submitted_at = NOW(), approved_at = NULL, remarks = NULL WHERE id = ?",
                [$filePath, $existing['id']]
            );
        } else {
            $this->db->query(
                "INSERT INTO employee_submissions (en_no, month, file_path) VALUES (?, ?, ?)",
                [$en_no, $month, $filePath]
            );
        }

        // Notify HR
        $this->notifyHR($en_no, $month);

        return ['status' => 'success', 'message' => 'Submitted successfully.'];
    }

    /**
     * Get submissions for HR review
     */
    public function getSubmissions()
    {
        return $this->db->fetchAll(
            "SELECT s.*, e.name FROM employee_submissions s JOIN employees e ON s.en_no = e.en_no ORDER BY s.submitted_at DESC"
        );
    }

    /**
     * Get single submission by ID
     */
    public function getSubmission($id)
    {
        return $this->db->fetchOne(
            "SELECT s.*, e.name FROM employee_submissions s JOIN employees e ON s.en_no = e.en_no WHERE s.id = ?",
            [$id]
        );
    }

    /**
     * Get attendance logs for a submission
     */
    public function getAttendanceBySubmission($id)
    {
        $submission = $this->getSubmission($id);
        if (!$submission) {
            return [];
        }

        return $this->db->fetchAll(
            "SELECT record_datetime, in_out, mode FROM attendance_logs WHERE en_no = ? AND DATE_FORMAT(record_datetime, '%Y-%m') = ? ORDER BY record_datetime ASC",
            [$submission['en_no'], $submission['month']]
        );
    }

    /**
     * Approve or reject submission
     */
    public function review($id, $status, $remarks = null)
    {
        $this->db->query(
            "UPDATE employee_submissions SET status = ?, approved_at = NOW(), remarks = ? WHERE id = ?",
            [$status, $remarks, $id]
        );

        // Get submission details
        $submission = $this->db->fetchOne(
            "SELECT en_no, month FROM employee_submissions WHERE id = ?",
            [$id]
        );

        // Notify employee
        $message = $status === 'approved' ? 'Your submission for ' . $submission['month'] . ' has been approved.' : 'Your submission for ' . $submission['month'] . ' has been rejected. Remarks: ' . $remarks;
        $this->notifyEmployee($submission['en_no'], $message);
    }

    /**
     * Get submissions for employee
     */
    public function getEmployeeSubmissions($en_no)
    {
        return $this->db->fetchAll(
            "SELECT * FROM employee_submissions WHERE en_no = ? ORDER BY submitted_at DESC",
            [$en_no]
        );
    }

    private function notifyHR($en_no, $month)
    {
        // For now, just log or email. Assume HR gets notified via dashboard.
    }

    private function notifyEmployee($en_no, $message)
    {
        $this->db->query(
            "INSERT INTO notifications (en_no, message) VALUES (?, ?)",
            [$en_no, $message]
        );
    }
}