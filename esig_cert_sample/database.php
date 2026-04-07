<?php

function getDb()
{
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $name = getenv('DB_NAME') ?: 'esig_cert_sample';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') ?: '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host={$host};dbname={$name};charset={$charset}";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    initDb($pdo);
    return $pdo;
}

function initDb(PDO $pdo)
{
    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS certificates (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            filename VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );

    $pdo->exec(
        "CREATE TABLE IF NOT EXISTS signature_styles (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            certificate_id INT NOT NULL,
            font_family VARCHAR(100) NOT NULL DEFAULT 'dejavusans',
            font_size INT NOT NULL DEFAULT 9,
            font_style VARCHAR(10) NOT NULL DEFAULT '',
            show_name TINYINT(1) NOT NULL DEFAULT 1,
            show_location TINYINT(1) NOT NULL DEFAULT 1,
            show_date TINYINT(1) NOT NULL DEFAULT 1,
            show_unique TINYINT(1) NOT NULL DEFAULT 1,
            created_at DATETIME NOT NULL,
            FOREIGN KEY (certificate_id) REFERENCES certificates(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;"
    );
}

function fetchCertificates(PDO $pdo)
{
    $stmt = $pdo->query('SELECT * FROM certificates ORDER BY created_at DESC');
    return $stmt->fetchAll();
}

function fetchStyles(PDO $pdo)
{
    $stmt = $pdo->query('SELECT s.*, c.title AS certificate_title FROM signature_styles s LEFT JOIN certificates c ON s.certificate_id = c.id ORDER BY s.created_at DESC');
    return $stmt->fetchAll();
}

function fetchCertificate(PDO $pdo, int $id)
{
    $stmt = $pdo->prepare('SELECT * FROM certificates WHERE id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function fetchStyle(PDO $pdo, int $id)
{
    $stmt = $pdo->prepare('SELECT * FROM signature_styles WHERE id = :id');
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}
