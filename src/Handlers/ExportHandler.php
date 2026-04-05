<?php
/**
 * ExportHandler
 * 
 * Handles export of attendance records to CSV
 */

namespace App\Handlers;

use App\Core\Database;

class ExportHandler
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Export attendance records to CSV
     */
    public function export($en_no, $month)
    {
        // Validate parameters
        if (!is_numeric($en_no) || empty($month)) {
            return ['status' => 'error', 'message' => 'Invalid parameters.'];
        }

        // Fetch employee details
        $employee = $this->db->fetchOne(
            "SELECT name FROM employees WHERE en_no = ?",
            [$en_no]
        );

        if (!$employee) {
            return ['status' => 'error', 'message' => 'Employee not found.'];
        }

        // Fetch attendance logs for the month
        $logs = $this->db->fetchAll(
            "SELECT record_datetime, in_out, mode 
             FROM attendance_logs 
             WHERE en_no = ? AND DATE_FORMAT(record_datetime, '%Y-%m') = ?
             ORDER BY record_datetime ASC",
            [$en_no, $month]
        );

        // Generate CSV
        $this->generateCSV($employee['name'], $en_no, $month, $logs);
    }

    /**
     * Generate and download CSV file
     */
    private function generateCSV($empName, $en_no, $month, $logs)
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $empName);
        $filename = 'Attendance_' . $safeName . '_' . $month . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Title rows
        fputcsv($output, ['Attendance Report', $empName, 'Month:', $month]);
        fputcsv($output, []);

        // Column headers
        fputcsv($output, ['Date', 'Time', 'Type (IN/OUT)', 'Mode']);
        fputcsv($output, []);

        // Data rows
        foreach ($logs as $log) {
            $dateTime = strtotime($log['record_datetime']);
            $date = date('Y-m-d', $dateTime);
            $time = date('H:i:s', $dateTime);
            $type = $log['in_out'] == 0 ? 'Duty On' : 'Duty Off';

            // Force date/time as text so Excel doesn't render #### when column width is small
            // $dateText = '="' . $date . '"';
            // $timeText = '="' . $time . '"';

            // fputcsv($output, [$dateText, $timeText, $type, $log['mode']]);
            

            fputcsv($output, [$date, $time, $type, $log['mode']]);
        }

        fclose($output);
        exit;
    }

    /**
     * Get available employees for export
     */
    public function getEmployees()
    {
        return $this->db->fetchAll(
            "SELECT en_no, name FROM employees ORDER BY name ASC"
        );
    }
}
