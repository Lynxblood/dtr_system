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
     * Submit signed PDF by employee
     */
    public function submitByEmployee($en_no, $month, $file)
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

        if (is_uploaded_file($file['tmp_name'])) {
            if (!move_uploaded_file($file['tmp_name'], $filePath)) {
                return ['status' => 'error', 'message' => 'File upload failed.'];
            }
        } else {
            if (!copy($file['tmp_name'], $filePath)) {
                return ['status' => 'error', 'message' => 'File copy failed.'];
            }
        }

        $existing = $this->db->fetchOne(
            "SELECT id FROM employee_submissions WHERE en_no = ? AND month = ?",
            [$en_no, $month]
        );

        if ($existing) {
            $this->db->query(
                "UPDATE employee_submissions SET file_path = ?, status = 'employee_signed', employee_signed_at = NOW() WHERE id = ?",
                [$filePath, $existing['id']]
            );
        } else {
            $this->db->query(
                "INSERT INTO employee_submissions (en_no, month, file_path, status, employee_signed_at) VALUES (?, ?, ?, 'employee_signed', NOW())",
                [$en_no, $month, $filePath]
            );
        }

        // Auto-submit to head
        $this->submitToHead($en_no, $month);

        return ['status' => 'success', 'message' => 'Submitted successfully to your head.'];
    }

    /**
     * Submit to head (automatic after employee signs)
     */
    private function submitToHead($en_no, $month)
    {
        $this->db->query(
            "UPDATE employee_submissions SET status = 'submitted_to_head' WHERE en_no = ? AND month = ?",
            [$en_no, $month]
        );

        // Notify head
        $this->notifyHead($en_no, $month);
    }

    /**
     * Head signs and submits to president
     */
    public function submitByHead($submissionId, $signedFile)
    {
        $uploadDir = __DIR__ . '/../../uploads/submissions/';
        $timestamp = time();
        $filename = pathinfo($signedFile['name'], PATHINFO_FILENAME);
        $extension = pathinfo($signedFile['name'], PATHINFO_EXTENSION);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        $filePath = $uploadDir . 'head_' . $timestamp . '_' . $safeName . ($extension ? '.' . $extension : '');

        if (is_uploaded_file($signedFile['tmp_name'])) {
            if (!move_uploaded_file($signedFile['tmp_name'], $filePath)) {
                return ['status' => 'error', 'message' => 'File upload failed.'];
            }
        } else {
            if (!copy($signedFile['tmp_name'], $filePath)) {
                return ['status' => 'error', 'message' => 'File copy failed.'];
            }
        }

        $this->db->query(
            "UPDATE employee_submissions SET file_path = ?, status = 'head_signed', head_signed_at = NOW() WHERE id = ?",
            [$filePath, $submissionId]
        );

        // Auto-submit to president
        $this->submitToPresident($submissionId);

        return ['status' => 'success', 'message' => 'Submitted successfully to president.'];
    }

    /**
     * Submit to president (automatic after head signs)
     */
    private function submitToPresident($submissionId)
    {
        $this->db->query(
            "UPDATE employee_submissions SET status = 'submitted_to_president' WHERE id = ?",
            [$submissionId]
        );

        // Notify president
        $this->notifyPresident($submissionId);
    }

    /**
     * President signs and submits to HR
     */
    public function submitByPresident($submissionId, $signedFile)
    {
        $uploadDir = __DIR__ . '/../../uploads/submissions/';
        $timestamp = time();
        $filename = pathinfo($signedFile['name'], PATHINFO_FILENAME);
        $extension = pathinfo($signedFile['name'], PATHINFO_EXTENSION);
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $filename);
        $filePath = $uploadDir . 'president_' . $timestamp . '_' . $safeName . ($extension ? '.' . $extension : '');

        if (is_uploaded_file($signedFile['tmp_name'])) {
            if (!move_uploaded_file($signedFile['tmp_name'], $filePath)) {
                return ['status' => 'error', 'message' => 'File upload failed.'];
            }
        } else {
            if (!copy($signedFile['tmp_name'], $filePath)) {
                return ['status' => 'error', 'message' => 'File copy failed.'];
            }
        }

        $this->db->query(
            "UPDATE employee_submissions SET file_path = ?, status = 'president_signed', president_signed_at = NOW() WHERE id = ?",
            [$filePath, $submissionId]
        );

        // Auto-submit to HR
        $this->submitToHR($submissionId);

        return ['status' => 'success', 'message' => 'Submitted successfully to HR.'];
    }

    /**
     * Submit to HR (automatic after president signs)
     */
    private function submitToHR($submissionId)
    {
        $this->db->query(
            "UPDATE employee_submissions SET status = 'submitted_to_hr' WHERE id = ?",
            [$submissionId]
        );

        // Notify HR
        $this->notifyHR($submissionId);
    }

    /**
     * Get submissions for HR review
     */
    public function getSubmissionsForHR()
    {
        return $this->db->fetchAll(
            "SELECT s.*, e.name FROM employee_submissions s JOIN employees e ON s.en_no = e.en_no WHERE s.status = 'submitted_to_hr' ORDER BY s.submitted_at DESC"
        );
    }

    /**
     * Get submissions for head review
     */
    public function getSubmissionsForHead($headUserId)
    {
        return $this->db->fetchAll(
            "SELECT s.*, e.name FROM employee_submissions s JOIN employees e ON s.en_no = e.en_no WHERE e.head_of_employee = ? AND s.status = 'submitted_to_head' ORDER BY s.submitted_at DESC",
            [$headUserId]
        );
    }

    /**
     * Get submissions for president review
     */
    public function getSubmissionsForPresident()
    {
        return $this->db->fetchAll(
            "SELECT s.*, e.name FROM employee_submissions s JOIN employees e ON s.en_no = e.en_no WHERE s.status = 'submitted_to_president' ORDER BY s.submitted_at DESC"
        );
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
     * Approve or reject submission (HR only)
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

    private function notifyHR($submissionId)
    {
        // Notify all HR users
        $hrUsers = $this->db->fetchAll("SELECT id FROM users WHERE role = 'hr'");
        foreach ($hrUsers as $hr) {
            $this->db->query(
                "INSERT INTO notifications (user_id, message) VALUES (?, ?)",
                [$hr['id'], 'New submission ready for review.']
            );
        }
    }

    private function notifyHead($en_no, $month)
    {
        // Get employee's head
        $employee = $this->db->fetchOne("SELECT head_of_employee FROM employees WHERE en_no = ?", [$en_no]);
        if ($employee && $employee['head_of_employee']) {
            $this->db->query(
                "INSERT INTO notifications (user_id, message) VALUES (?, ?)",
                [$employee['head_of_employee'], 'New submission from employee ' . $en_no . ' for ' . $month . ' ready for review.']
            );
        }
    }

    private function notifyPresident($submissionId)
    {
        // Notify all president users
        $presidents = $this->db->fetchAll("SELECT id FROM users WHERE role = 'president'");
        foreach ($presidents as $president) {
            $this->db->query(
                "INSERT INTO notifications (user_id, message) VALUES (?, ?)",
                [$president['id'], 'New submission ready for presidential review.']
            );
        }
    }

    private function notifyEmployee($en_no, $message)
    {
        $this->db->query(
            "INSERT INTO notifications (en_no, message) VALUES (?, ?)",
            [$en_no, $message]
        );
    }
}