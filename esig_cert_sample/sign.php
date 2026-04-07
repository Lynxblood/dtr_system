<?php

require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/database.php';

use setasign\Fpdi\Tcpdf\Fpdi;

$pdo = getDb();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die('Invalid request.');
}

$certificateId = isset($_POST['certificate_id']) ? intval($_POST['certificate_id']) : 0;
$styleId = isset($_POST['style_id']) ? intval($_POST['style_id']) : 0;
$name = trim($_POST['sign_name'] ?? '');
$location = trim($_POST['sign_location'] ?? '');
$date = trim($_POST['sign_date'] ?? date('Y-m-d'));
$uniqueNumber = trim($_POST['sign_unique'] ?? '');
$includeName = isset($_POST['include_name']);
$includeLocation = isset($_POST['include_location']);
$includeDate = isset($_POST['include_date']);
$includeUnique = isset($_POST['include_unique']);
$certPassword = $_POST['cert_pass'] ?? '';

if ($styleId > 0) {
    $style = fetchStyle($pdo, $styleId);
    if (!$style) {
        die('Signature style not found.');
    }
    if ($certificateId <= 0) {
        $certificateId = intval($style['certificate_id']);
    }
}

$fontFamily = trim($_POST['font_family'] ?? 'dejavusans');
$fontSize = intval($_POST['font_size'] ?? 9);
$fontStyle = trim($_POST['font_style'] ?? '');

if ($certificateId <= 0) {
    die('Please select a saved certificate before signing.');
}

$certificate = fetchCertificate($pdo, $certificateId);
if (!$certificate) {
    die('Certificate record not found.');
}

$pdfFile = $_FILES['pdf'] ?? null;
if (empty($pdfFile) || $pdfFile['error'] !== UPLOAD_ERR_OK) {
    die('Please upload a valid PDF file.');
}

$uploadFolder = __DIR__ . '/uploads/pdfs';
if (!is_dir($uploadFolder)) {
    mkdir($uploadFolder, 0777, true);
}

$pdfPath = $uploadFolder . '/' . time() . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($pdfFile['name']));
if (!move_uploaded_file($pdfFile['tmp_name'], $pdfPath)) {
    die('Unable to save uploaded PDF.');
}

if (!file_exists($pdfPath) || filesize($pdfPath) === 0) {
    die('Uploaded PDF is empty or missing.');
}

$content = file_get_contents($pdfPath, false, null, 0, 5);
if (strpos($content, '%PDF-') !== 0) {
    die('Uploaded file is not a valid PDF.');
}

$certPath = __DIR__ . '/' . $certificate['filename'];
if (!file_exists($certPath)) {
    die('Stored certificate file not found.');
}

$p12 = file_get_contents($certPath);
if (!openssl_pkcs12_read($p12, $certs, $certPassword)) {
    die('Invalid certificate or password.');
}

$cert = $certs['cert'];
$pkey = $certs['pkey'];

$pdf = new Fpdi();
$pageCount = $pdf->setSourceFile($pdfPath);

for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
    $tplId = $pdf->importPage($pageNo);
    $pdf->AddPage();
    $pdf->useTemplate($tplId);

    if ($pageNo === $pageCount) {
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
        if ($includeName && $name !== '') {
            $lines[] = 'Name: ' . $name;
        }
        if ($includeLocation && $location !== '') {
            $lines[] = 'Location: ' . $location;
        }
        if ($includeDate && $date !== '') {
            $lines[] = 'Date: ' . $date;
        }
        if ($includeUnique && $uniqueNumber !== '') {
            $lines[] = 'ID: ' . $uniqueNumber;
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

$pdf->setSignature($cert, $pkey, $certPassword, '', 2, [
    'Name' => $name ?: 'Signed document',
    'Location' => $location ?: 'Not specified',
    'Reason' => 'Document signed with certificate',
], 'A');

$signedFolder = __DIR__ . '/uploads/signed';
if (!is_dir($signedFolder)) {
    mkdir($signedFolder, 0777, true);
}

$timestamp = time();
$outputName = 'signed_' . $timestamp . '.pdf';
$outputFile = $signedFolder . '/' . $outputName;
$pdf->Output($outputFile, 'F');

$linkFile = 'uploads/signed/' . $outputName;

echo "✅ PDF Signed Successfully!<br>";
echo "<a href='" . htmlspecialchars($linkFile) . "' target='_blank'>Download Signed PDF</a>";