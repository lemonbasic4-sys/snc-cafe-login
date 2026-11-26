<?php
declare(strict_types=1);
function snc_get_db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $host = getenv('SNC_DB_HOST') ?: '127.0.0.1';
    $port = getenv('SNC_DB_PORT') ?: '3306';
    $name = getenv('SNC_DB_NAME') ?: 'snc_cafe_db';
    $user = getenv('SNC_DB_USER') ?: 'root';
    $pass = getenv('SNC_DB_PASS');
    $pass = $pass === false ? '' : $pass;

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4', $host, $port, $name);

    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    return $pdo;
}
