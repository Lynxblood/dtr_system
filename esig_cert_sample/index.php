<?php
require_once __DIR__ . '/database.php';

$pdo = getDb();
$certificates = fetchCertificates($pdo);
$styles = fetchStyles($pdo);
$errors = [];
$successMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['action']) && $_POST['action'] === 'add_certificate') {
        $title = trim($_POST['certificate_title'] ?? '');
        $certFile = $_FILES['certificate_file'] ?? null;

        if ($title === '') {
            $errors[] = 'Certificate title is required.';
        }

        if (empty($certFile) || $certFile['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Please upload a valid .p12 certificate file.';
        } elseif (strtolower(pathinfo($certFile['name'], PATHINFO_EXTENSION)) !== 'p12') {
            $errors[] = 'Only .p12 certificate files are supported.';
        }

        if (empty($errors)) {
            $certFolder = __DIR__ . '/uploads/certs';
            if (!is_dir($certFolder)) {
                mkdir($certFolder, 0777, true);
            }

            $safeName = time() . '_' . preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', basename($certFile['name']));
            $destination = $certFolder . '/' . $safeName;

            if (!move_uploaded_file($certFile['tmp_name'], $destination)) {
                $errors[] = 'Unable to move the uploaded certificate file.';
            } else {
                $stmt = $pdo->prepare('INSERT INTO certificates (title, filename, created_at) VALUES (:title, :filename, :created_at)');
                $stmt->execute([
                    ':title' => $title,
                    ':filename' => 'uploads/certs/' . $safeName,
                    ':created_at' => date('c'),
                ]);

                $successMessage = 'Certificate saved successfully.';
                $certificates = fetchCertificates($pdo);
            }
        }
    }

    if (!empty($_POST['action']) && $_POST['action'] === 'add_style') {
        $title = trim($_POST['style_title'] ?? '');
        $certificateId = intval($_POST['style_certificate_id'] ?? 0);
        $fontFamily = trim($_POST['font_family'] ?? 'dejavusans');
        $fontSize = intval($_POST['font_size'] ?? 9);
        $fontStyle = trim($_POST['font_style'] ?? '');
        $showName = isset($_POST['include_name']) ? 1 : 0;
        $showLocation = isset($_POST['include_location']) ? 1 : 0;
        $showDate = isset($_POST['include_date']) ? 1 : 0;
        $showUnique = isset($_POST['include_unique']) ? 1 : 0;

        if ($title === '') {
            $errors[] = 'Signature style name is required.';
        }

        if ($certificateId <= 0) {
            $errors[] = 'Please choose a certificate to attach to this style.';
        }

        if ($fontSize < 6 || $fontSize > 24) {
            $fontSize = 9;
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare('INSERT INTO signature_styles (title, certificate_id, font_family, font_size, font_style, show_name, show_location, show_date, show_unique, created_at) VALUES (:title, :certificate_id, :font_family, :font_size, :font_style, :show_name, :show_location, :show_date, :show_unique, :created_at)');
            $stmt->execute([
                ':title' => $title,
                ':certificate_id' => $certificateId,
                ':font_family' => $fontFamily,
                ':font_size' => $fontSize,
                ':font_style' => $fontStyle,
                ':show_name' => $showName,
                ':show_location' => $showLocation,
                ':show_date' => $showDate,
                ':show_unique' => $showUnique,
                ':created_at' => date('c'),
            ]);

            $successMessage = 'Signature style saved successfully.';
            $styles = fetchStyles($pdo);
        }
    }
}

function checked($condition)
{
    return $condition ? 'checked' : '';
}

function selected($value, $expected)
{
    return $value === $expected ? 'selected' : '';
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PDF Signature Builder</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 24px; }
        .grid { display: grid; gap: 18px; grid-template-columns: 320px 1fr; }
        .card { padding: 18px; border: 1px solid #ccc; border-radius: 8px; background: #fafafa; }
        label { display: block; margin-top: 10px; }
        input[type="text"], input[type="number"], select, input[type="password"], input[type="file"] { width: 100%; padding: 8px; margin-top: 4px; box-sizing: border-box; }
        button { margin-top: 14px; padding: 10px 18px; background: #2f7bed; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #235ebf; }
        .message { padding: 10px 14px; border-radius: 6px; margin-bottom: 16px; }
        .message.error { background: #ffe6e6; border: 1px solid #ffb3b3; }
        .message.success { background: #e8ffe8; border: 1px solid #8cd08c; }
        .list { margin-top: 10px; font-size: 14px; }
        .list li { margin-bottom: 8px; }
        .small { font-size: 13px; color: #555; }
        fieldset { border: 1px solid #ddd; padding: 14px; border-radius: 8px; margin-top: 12px; }
    </style>
</head>
<body>
    <h1>PDF Signature Builder</h1>

    <?php if (!empty($errors)): ?>
        <div class="message error">
            <strong>Fix these issues:</strong>
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <?php if ($successMessage): ?>
        <div class="message success"><?php echo htmlspecialchars($successMessage); ?></div>
    <?php endif; ?>

    <div class="grid">
        <div class="card">
            <h2>Add Certificate</h2>
            <form method="POST" enctype="multipart/form-data">
                <input type="hidden" name="action" value="add_certificate">
                <label>Certificate title</label>
                <input type="text" name="certificate_title" placeholder="Office signing cert" required>

                <label>Upload .p12 file</label>
                <input type="file" name="certificate_file" accept=".p12" required>

                <button type="submit">Save Certificate</button>
            </form>

            <?php if (!empty($certificates)): ?>
                <div class="list">
                    <h3>Saved certificates</h3>
                    <ul>
                        <?php foreach ($certificates as $cert): ?>
                            <li><strong><?php echo htmlspecialchars($cert['title']); ?></strong><br><span class="small"><?php echo htmlspecialchars($cert['filename']); ?></span></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <h2>Create Signature Style</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add_style">
                <label>Style name</label>
                <input type="text" name="style_title" placeholder="Default sign style" required>

                <label>Certificate</label>
                <select name="style_certificate_id" required>
                    <option value="">Choose certificate</option>
                    <?php foreach ($certificates as $cert): ?>
                        <option value="<?php echo $cert['id']; ?>"><?php echo htmlspecialchars($cert['title']); ?></option>
                    <?php endforeach; ?>
                </select>

                <fieldset>
                    <legend>Include fields</legend>
                    <label><input type="checkbox" name="include_name" checked> Name</label>
                    <label><input type="checkbox" name="include_location" checked> Location</label>
                    <label><input type="checkbox" name="include_date" checked> Date</label>
                    <label><input type="checkbox" name="include_unique" checked> Unique number</label>
                </fieldset>

                <fieldset>
                    <legend>Design options</legend>
                    <label>Font family</label>
                    <select name="font_family">
                        <option value="dejavusans">DejaVu Sans</option>
                        <option value="helvetica">Helvetica</option>
                        <option value="courier">Courier</option>
                    </select>

                    <label>Font size</label>
                    <input type="number" name="font_size" value="9" min="6" max="24">

                    <label>Font style</label>
                    <select name="font_style">
                        <option value="">Normal</option>
                        <option value="B">Bold</option>
                        <option value="I">Italic</option>
                        <option value="BI">Bold Italic</option>
                    </select>
                </fieldset>

                <button type="submit">Save Style</button>
            </form>

            <?php if (!empty($styles)): ?>
                <div class="list">
                    <h3>Saved signature styles</h3>
                    <ul>
                        <?php foreach ($styles as $style): ?>
                            <li>
                                <strong><?php echo htmlspecialchars($style['title']); ?></strong> <span class="small">(<?php echo htmlspecialchars($style['certificate_title'] ?? 'No cert'); ?>)</span><br>
                                <span class="small">
                                    <?php echo $style['show_name'] ? 'Name ' : ''; ?>
                                    <?php echo $style['show_location'] ? 'Location ' : ''; ?>
                                    <?php echo $style['show_date'] ? 'Date ' : ''; ?>
                                    <?php echo $style['show_unique'] ? 'Unique # ' : ''; ?>
                                </span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card" style="margin-top: 20px;">
        <h2>Upload PDF and Sign</h2>
        <form action="sign.php" method="POST" enctype="multipart/form-data">
            <label>PDF file</label>
            <input type="file" name="pdf" accept="application/pdf" required>

            <label>Choose signature style</label>
            <select name="style_id" id="style_id">
                <option value="">-- Optional: select saved style --</option>
                <?php foreach ($styles as $style): ?>
                    <option value="<?php echo $style['id']; ?>" data-cert-id="<?php echo $style['certificate_id']; ?>" data-show-name="<?php echo $style['show_name']; ?>" data-show-location="<?php echo $style['show_location']; ?>" data-show-date="<?php echo $style['show_date']; ?>" data-show-unique="<?php echo $style['show_unique']; ?>" data-font-family="<?php echo htmlspecialchars($style['font_family']); ?>" data-font-size="<?php echo $style['font_size']; ?>" data-font-style="<?php echo htmlspecialchars($style['font_style']); ?>">
                        <?php echo htmlspecialchars($style['title']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label>Certificate</label>
            <select name="certificate_id" id="certificate_id" required>
                <option value="">Choose certificate</option>
                <?php foreach ($certificates as $cert): ?>
                    <option value="<?php echo $cert['id']; ?>"><?php echo htmlspecialchars($cert['title']); ?></option>
                <?php endforeach; ?>
            </select>

            <fieldset>
                <legend>Fields to include on signature</legend>
                <label><input type="checkbox" name="include_name" id="include_name" checked> Name</label>
                <label><input type="checkbox" name="include_location" id="include_location" checked> Location</label>
                <label><input type="checkbox" name="include_date" id="include_date" checked> Date</label>
                <label><input type="checkbox" name="include_unique" id="include_unique" checked> Unique number</label>
            </fieldset>

            <fieldset>
                <legend>Design options</legend>
                <label>Font family</label>
                <select name="font_family" id="font_family">
                    <option value="dejavusans">DejaVu Sans</option>
                    <option value="helvetica">Helvetica</option>
                    <option value="courier">Courier</option>
                </select>

                <label>Font size</label>
                <input type="number" name="font_size" id="font_size" value="9" min="6" max="24">

                <label>Font style</label>
                <select name="font_style" id="font_style">
                    <option value="">Normal</option>
                    <option value="B">Bold</option>
                    <option value="I">Italic</option>
                    <option value="BI">Bold Italic</option>
                </select>
            </fieldset>

            <label>Name</label>
            <input type="text" name="sign_name" placeholder="John Doe">

            <label>Location</label>
            <input type="text" name="sign_location" placeholder="City, Country">

            <label>Date</label>
            <input type="text" name="sign_date" value="<?php echo date('Y-m-d'); ?>">

            <label>Unique number</label>
            <input type="text" name="sign_unique" placeholder="INV-2026-001">

            <label>Certificate password</label>
            <input type="password" name="cert_pass" required>

            <button type="submit">Sign PDF</button>
        </form>
    </div>

    <script>
        const styleSelect = document.getElementById('style_id');
        const certSelect = document.getElementById('certificate_id');

        styleSelect?.addEventListener('change', function () {
            const selected = this.options[this.selectedIndex];
            if (!selected || !selected.value) {
                return;
            }

            const certId = selected.getAttribute('data-cert-id');
            const showName = selected.getAttribute('data-show-name') === '1';
            const showLocation = selected.getAttribute('data-show-location') === '1';
            const showDate = selected.getAttribute('data-show-date') === '1';
            const showUnique = selected.getAttribute('data-show-unique') === '1';
            const fontFamily = selected.getAttribute('data-font-family');
            const fontSize = selected.getAttribute('data-font-size');
            const fontStyle = selected.getAttribute('data-font-style');

            if (certId) {
                certSelect.value = certId;
            }

            document.getElementById('include_name').checked = showName;
            document.getElementById('include_location').checked = showLocation;
            document.getElementById('include_date').checked = showDate;
            document.getElementById('include_unique').checked = showUnique;

            const fontFamilyInput = document.querySelector('select[name="font_family"]');
            if (fontFamilyInput && fontFamily) {
                fontFamilyInput.value = fontFamily;
            }

            const fontSizeInput = document.querySelector('input[name="font_size"]');
            if (fontSizeInput && fontSize) {
                fontSizeInput.value = fontSize;
            }

            const fontStyleInput = document.querySelector('select[name="font_style"]');
            if (fontStyleInput && fontStyle) {
                fontStyleInput.value = fontStyle;
            }
        });
    </script>
</body>
</html>
