<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$dbPath = $_ENV['DB_PATH'];
if (strpos($dbPath, '/') !== 0) {
    $dbPath = __DIR__ . '/../' . $dbPath;
}
$db = new PDO("sqlite:$dbPath");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "Ejecutando migraciones...\n";

$sql = file_get_contents(__DIR__ . '/init.sql');
$db->exec($sql);

echo "Migraciones completadas exitosamente.\n";
