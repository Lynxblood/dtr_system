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
     * Export attendance records to CSV (for admin/HR)
     */
    public function exportCSV($en_no, $month)
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
     * Export attendance records to PDF (for employees)
     */
    public function exportPDF($en_no, $month)
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

        // Generate PDF
        $this->generatePDF($employee['name'], $en_no, $month, $logs);
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
     * Generate and download PDF file
     * Note: Requires FPDF library at src/vendor/fpdf/fpdf.php
     * If FPDF is not available, generates a text file that can be saved as PDF.
     */
    private function generatePDF($empName, $en_no, $month, $logs)
    {
        $fpdfPath = __DIR__ . '/../vendor/fpdf/fpdf.php';
        if (!file_exists($fpdfPath)) {
            // Fallback: generate a simple text file that can be saved as PDF
            $this->generateTextAsPDF($empName, $en_no, $month, $logs);
            return;
        }

        require_once $fpdfPath;

        $pdf = new \FPDF();
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 16);
        $pdf->Cell(0, 10, 'Attendance Report', 0, 1, 'C');
        $pdf->SetFont('Arial', '', 12);
        $pdf->Cell(0, 10, 'Employee: ' . $empName . ' (ID: ' . $en_no . ')', 0, 1);
        $pdf->Cell(0, 10, 'Month: ' . $month, 0, 1);
        $pdf->Ln(10);

        // Table headers
        $pdf->SetFont('Arial', 'B', 10);
        $pdf->Cell(30, 10, 'Date', 1);
        $pdf->Cell(30, 10, 'Time', 1);
        $pdf->Cell(40, 10, 'Type (IN/OUT)', 1);
        $pdf->Cell(30, 10, 'Mode', 1);
        $pdf->Ln();

        // Data rows
        $pdf->SetFont('Arial', '', 10);
        foreach ($logs as $log) {
            $dateTime = strtotime($log['record_datetime']);
            $date = date('Y-m-d', $dateTime);
            $time = date('H:i:s', $dateTime);
            $type = $log['in_out'] == 0 ? 'Duty On' : 'Duty Off';

            $pdf->Cell(30, 10, $date, 1);
            $pdf->Cell(30, 10, $time, 1);
            $pdf->Cell(40, 10, $type, 1);
            $pdf->Cell(30, 10, $log['mode'], 1);
            $pdf->Ln();
        }

        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $empName);
        $filename = 'Attendance_' . $safeName . '_' . $month . '.pdf';

        $pdf->Output('D', $filename);
        exit;
    }

    /**
     * Generate and download a text file as fallback (can be saved as PDF)
     */
    private function generateTextAsPDF($empName, $en_no, $month, $logs)
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', $empName);
        $filename = 'Attendance_' . $safeName . '_' . $month . '.txt';

        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo "Attendance Report\n";
        echo "=================\n\n";
        echo "Employee: $empName (ID: $en_no)\n";
        echo "Month: $month\n\n";
        echo "Date\t\tTime\t\tType\t\tMode\n";
        echo "----\t\t----\t\t----\t\t----\n";

        foreach ($logs as $log) {
            $dateTime = strtotime($log['record_datetime']);
            $date = date('Y-m-d', $dateTime);
            $time = date('H:i:s', $dateTime);
            $type = $log['in_out'] == 0 ? 'Duty On' : 'Duty Off';

            echo "$date\t$time\t$type\t{$log['mode']}\n";
        }

        echo "\n\nNote: This is a text file. To convert to PDF, open in a word processor and save as PDF.\n";
        echo "FPDF library is required for direct PDF generation. Download from https://www.fpdf.org/\n";

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
