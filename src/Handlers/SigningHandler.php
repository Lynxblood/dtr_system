<?php
namespace App\Handlers;

require_once __DIR__ . '/../vendor/autoload.php';

/**
 * SigningHandler
 *
 * Handles electronic signature functionality for PDFs
 */

use App\Core\Database;
use setasign\Fpdi\Tcpdf\Fpdi;

class SigningHandler
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    /**
     * Get all certificates
     */
    public function getCertificates()
    {
        return $this->db->fetchAll('SELECT * FROM certificates ORDER BY created_at DESC');
    }

    /**
     * Get certificate by ID
     */
    public function getCertificate($id)
    {
        return $this->db->fetchOne('SELECT * FROM certificates WHERE id = ?', [$id]);
    }

    /**
     * Add new certificate
     */
    public function addCertificate($title, $file)
    {
        $uploadDir = __DIR__ . '/../../uploads/certs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($file['name']));
        $destination = $uploadDir . $safeName;

        if (!move_uploaded_file($file['tmp_name'], $destination)) {
            return ['status' => 'error', 'message' => 'Unable to move the uploaded certificate file.'];
        }

        $this->db->query(
            'INSERT INTO certificates (title, filename, created_at) VALUES (?, ?, ?)',
            [$title, 'uploads/certs/' . $safeName, date('c')]
        );

        return ['status' => 'success', 'message' => 'Certificate saved successfully.'];
    }

    /**
     * Get all signature styles
     */
    public function getSignatureStyles()
    {
        return $this->db->fetchAll(
            'SELECT s.*, c.title AS certificate_title FROM signature_styles s LEFT JOIN certificates c ON s.certificate_id = c.id ORDER BY s.created_at DESC'
        );
    }

    /**
     * Get signature style by ID
     */
    public function getSignatureStyle($id)
    {
        return $this->db->fetchOne('SELECT * FROM signature_styles WHERE id = ?', [$id]);
    }

    /**
     * Add new signature style
     */
    public function addSignatureStyle($data)
    {
        $this->db->query(
            'INSERT INTO signature_styles (title, certificate_id, font_family, font_size, font_style, show_name, show_location, show_date, show_unique, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
            [
                $data['title'],
                $data['certificate_id'],
                $data['font_family'] ?? 'dejavusans',
                $data['font_size'] ?? 9,
                $data['font_style'] ?? '',
                isset($data['show_name']) ? 1 : 0,
                isset($data['show_location']) ? 1 : 0,
                isset($data['show_date']) ? 1 : 0,
                isset($data['show_unique']) ? 1 : 0,
                date('c')
            ]
        );

        return ['status' => 'success', 'message' => 'Signature style saved successfully.'];
    }

    /**
     * Sign a PDF with certificate
     */
    public function signPDF($pdfFile, $certificateId, $styleId, $signData, $certPassword)
    {
        $certificate = $this->getCertificate($certificateId);
        if (!$certificate) {
            return ['status' => 'error', 'message' => 'Certificate not found.'];
        }

        $style = null;
        if ($styleId > 0) {
            $style = $this->getSignatureStyle($styleId);
            if (!$style) {
                return ['status' => 'error', 'message' => 'Signature style not found.'];
            }
        }

        // Upload PDF
        $uploadDir = __DIR__ . '/../../uploads/pdfs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $pdfPath = $uploadDir . time() . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($pdfFile['name']));
        if (!move_uploaded_file($pdfFile['tmp_name'], $pdfPath)) {
            return ['status' => 'error', 'message' => 'Unable to save uploaded PDF.'];
        }

        // Validate PDF
        if (!file_exists($pdfPath) || filesize($pdfPath) === 0) {
            return ['status' => 'error', 'message' => 'Uploaded PDF is empty or missing.'];
        }

        $content = file_get_contents($pdfPath, false, null, 0, 5);
        if (strpos($content, '%PDF-') !== 0) {
            return ['status' => 'error', 'message' => 'Uploaded file is not a valid PDF.'];
        }

        // Load certificate
        $certPath = __DIR__ . '/../../' . $certificate['filename'];
        if (!file_exists($certPath)) {
            return ['status' => 'error', 'message' => 'Certificate file not found.'];
        }

        $p12 = file_get_contents($certPath);
        if (!openssl_pkcs12_read($p12, $certs, $certPassword)) {
            return ['status' => 'error', 'message' => 'Invalid certificate or password.'];
        }

        $cert = $certs['cert'];
        $pkey = $certs['pkey'];

        // Sign PDF
        $signedPath = $this->performSigning($pdfPath, $cert, $pkey, $certPassword, $signData, $style);

        return ['status' => 'success', 'signed_path' => $signedPath];
    }

    private function performSigning($pdfPath, $cert, $pkey, $certPassword, $signData, $style = null)
    {
        $pdf = new Fpdi();
        $pageCount = $pdf->setSourceFile($pdfPath);

        for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
            $tplId = $pdf->importPage($pageNo);
            $pdf->AddPage();
            $pdf->useTemplate($tplId);

            if ($pageNo === $pageCount) {
                // Add signature on last page
                $this->addSignatureToPDF($pdf, $signData, $style);
            }
        }

        $name = $signData['name'] ?: 'Signed document';
        $location = $signData['location'] ?: 'Not specified';

        $pdf->setSignature($cert, $pkey, $certPassword, '', 2, [
            'Name' => $name,
            'Location' => $location,
            'Reason' => 'Document signed with certificate',
        ], 'A');

        // Create signed file path
        $signedDir = __DIR__ . '/../../uploads/signed/';
        if (!is_dir($signedDir)) {
            mkdir($signedDir, 0755, true);
        }

        $signedPath = $signedDir . 'signed_' . time() . '_' . basename($pdfPath);
        $pdf->Output($signedPath, 'F');

        return $signedPath;
    }

    private function addSignatureToPDF($pdf, $signData, $style = null)
    {
        $fontFamily = $style ? $style['font_family'] : ($signData['font_family'] ?? 'dejavusans');
        $fontSize = $style ? $style['font_size'] : ($signData['font_size'] ?? 9);
        $fontStyle = $style ? $style['font_style'] : ($signData['font_style'] ?? '');

        $marginMm = 25.4;
        $sigWidthMm = 45.8;
        $sigHeightMm = 21.2;
        $pageHeight = $pdf->getPageHeight();
        $sigX = $marginMm;
        $sigY = $pageHeight - $marginMm - $sigHeightMm;

        // Draw rectangle border first
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.5);
        $pdf->Rect($sigX, $sigY, $sigWidthMm, $sigHeightMm, 'D');

        // Set font and colors for text
        $pdf->SetFont($fontFamily, $fontStyle, $fontSize);
        $pdf->SetTextColor(0, 0, 0);

        // Build signature text lines
        $lines = [];
        if (($style && $style['show_name']) || (!$style && isset($signData['include_name']) && $signData['include_name'])) {
            $name = $signData['name'] ?? '';
            if ($name !== '') {
                $lines[] = 'Name: ' . $name;
            }
        }
        if (($style && $style['show_location']) || (!$style && isset($signData['include_location']) && $signData['include_location'])) {
            $location = $signData['location'] ?? '';
            if ($location !== '') {
                $lines[] = 'Location: ' . $location;
            }
        }
        if (($style && $style['show_date']) || (!$style && isset($signData['include_date']) && $signData['include_date'])) {
            $date = $signData['date'] ?? date('Y-m-d');
            if ($date !== '') {
                $lines[] = 'Date: ' . $date;
            }
        }
        if (($style && $style['show_unique']) || (!$style && isset($signData['include_unique']) && $signData['include_unique'])) {
            $unique = $signData['unique'] ?? '';
            if ($unique !== '') {
                $lines[] = 'ID: ' . $unique;
            }
        }
        if (empty($lines)) {
            $lines[] = 'Digitally signed';
        }

        // Calculate line height and total height needed
        $lineHeight = 4;
        $padding = 1.5;
        $contentX = $sigX + $padding;
        $contentY = $sigY + $padding;
        $contentWidth = $sigWidthMm - (2 * $padding);

        // Write each line with proper spacing
        foreach ($lines as $line) {
            $pdf->SetXY($contentX, $contentY);
            $pdf->MultiCell($contentWidth, $lineHeight, $line, 0, 'L', false, 1, '', '', true, 0, false, true, $lineHeight, 'T', false);
            $contentY += $lineHeight + 1;
        }

        $pdf->setSignatureAppearance($sigX, $sigY, $sigWidthMm, $sigHeightMm);
    }
}