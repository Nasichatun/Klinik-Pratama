<?php
// ============================================================
// config.php — Konfigurasi Database
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'klinik_pratama');
define('DB_USER', 'root');       // ganti sesuai user MySQL Anda
define('DB_PASS', '');           // ganti sesuai password MySQL Anda
define('DB_CHAR', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHAR;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    }
    return $pdo;
}
