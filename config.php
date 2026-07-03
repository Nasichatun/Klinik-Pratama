<?php
// ============================================================
// config.php — Konfigurasi Koneksi Database
// Edit bagian ini sesuai server Anda (XAMPP / Laragon / hosting)
// ============================================================

define('DB_HOST', 'localhost');
define('DB_NAME', 'klinik_pratama');
define('DB_USER', 'root');   // ganti sesuai username MySQL Anda
define('DB_PASS', '');       // ganti sesuai password MySQL Anda
define('DB_CHAR', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHAR);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
