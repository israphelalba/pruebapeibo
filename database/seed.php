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

echo "Sembrando datos...\n";

// Crear usuario de prueba
$password = password_hash('password123', PASSWORD_BCRYPT);
$db->exec("INSERT OR IGNORE INTO users (name, email, password) VALUES ('Usuario Test', 'test@example.com', '$password')");

$userId = $db->lastInsertId() ?: 1;

// Crear tareas de prueba
$tasks = [
    ['user_id' => $userId, 'title' => 'Tarea 1', 'description' => 'Desc 1', 'status' => 'pendiente'],
    ['user_id' => $userId, 'title' => 'Tarea 2', 'description' => 'Desc 2', 'status' => 'en progreso'],
    ['user_id' => $userId, 'title' => 'Tarea 3', 'description' => 'Desc 3', 'status' => 'completada'],
    ['user_id' => $userId, 'title' => 'Tarea 4', 'description' => 'Desc 4', 'status' => 'pendiente'],
    ['user_id' => $userId, 'title' => 'Tarea 5', 'description' => 'Desc 5', 'status' => 'pendiente'],
];

foreach ($tasks as $task) {
    $stmt = $db->prepare("INSERT INTO tasks (user_id, title, description, status) VALUES (:u, :t, :d, :s)");
    $stmt->execute([
        ':u' => $task['user_id'],
        ':t' => $task['title'],
        ':d' => $task['description'],
        ':s' => $task['status']
    ]);
}

echo "Seeds completados exitosamente.\n";
