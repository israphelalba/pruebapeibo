<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Router;
use App\Controllers\AuthController;
use App\Controllers\TaskController;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Configurar el Router
$router = new Router();

// Definir Rutas
// Auth
$router->add('POST', '/auth/register', [AuthController::class, 'register']);
$router->add('POST', '/auth/login', [AuthController::class, 'login']);
$router->add('PUT', '/auth/profile', [AuthController::class, 'update'], true);
$router->add('DELETE', '/auth/profile', [AuthController::class, 'delete'], true);

// Tasks (Protegidas)
$router->add('GET', '/tasks', [TaskController::class, 'list'], true);
$router->add('POST', '/tasks', [TaskController::class, 'create'], true);
$router->add('GET', '/tasks/{id}', [TaskController::class, 'get'], true);
$router->add('PUT', '/tasks/{id}', [TaskController::class, 'update'], true);
$router->add('DELETE', '/tasks/{id}', [TaskController::class, 'delete'], true);

// Despachar la petición
$router->dispatch();
