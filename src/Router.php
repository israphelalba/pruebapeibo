<?php

declare(strict_types=1);

namespace App;

class Router
{
    private array $routes = [];

    public function add(string $method, string $path, $handler, bool $protected = false): void
    {
        $this->routes[] = [
            'method' => $method,
            'path' => $path,
            'handler' => $handler,
            'protected' => $protected
        ];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        foreach ($this->routes as $route) {
            $pattern = preg_replace('/\{([a-zA-Z0-9_]+)\}/', '(?P<$1>[^/]+)', $route['path']);
            $pattern = "#^" . $pattern . "$#";

            if ($method === $route['method'] && preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                
                // Ejecutar el handler
                $this->execute($route, $params);
                return;
            }
        }

        $this->jsonResponse(['error' => 'Ruta no encontrada'], 404);
    }

    private function execute(array $route, array $params): void
    {
        $handler = $route['handler'];
        
        // Simulación de middleware JWT
        $userId = null;
        if ($route['protected']) {
            $userId = $this->validateJwt();
            if (!$userId) return;
        }

        if (is_array($handler)) {
            [$controllerClass, $method] = $handler;
            
            // Inyección manual de dependencias
            $controller = (new Core())->getController($controllerClass);
            
            try {
                $controller->$method($params, $userId);
            } catch (\Exception $e) {
                $this->jsonResponse(['error' => $e->getMessage()], 400);
            }
        }
    }

    private function validateJwt(): ?int
    {
        $headers = getallheaders();
        $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';

        if (!preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            $this->jsonResponse(['error' => 'No autorizado. Token ausente.'], 401);
            return null;
        }

        try {
            $secret = $_ENV['JWT_SECRET'];
            $decoded = \Firebase\JWT\JWT::decode($matches[1], new \Firebase\JWT\Key($secret, 'HS256'));
            return (int)$decoded->sub;
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => 'Token inválido'], 401);
            return null;
        }
    }

    private function jsonResponse(array $data, int $status = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
    }
}

/**
 * Clase para manejar la creación de objetos y dependencias de forma manual.
 */
class Core
{
    private ?\PDO $db = null;

    public function getDb(): \PDO
    {
        if ($this->db === null) {
            $this->db = new \PDO("sqlite:" . $_ENV['DB_PATH']);
            $this->db->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $this->db->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
            // Protección extra: desactivar emulación de sentencias preparadas
            $this->db->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        }
        return $this->db;
    }

    public function getController(string $class)
    {
        $db = $this->getDb();
        if ($class === \App\Controllers\AuthController::class) {
            $repo = new \App\Repositories\UserRepository($db);
            $service = new \App\Services\UserService($repo, [
                'secret' => $_ENV['JWT_SECRET'],
                'expiration' => (int)$_ENV['JWT_EXPIRATION']
            ]);
            return new \App\Controllers\AuthController($service);
        }

        if ($class === \App\Controllers\TaskController::class) {
            $repo = new \App\Repositories\TaskRepository($db);
            $service = new \App\Services\TaskService($repo);
            return new \App\Controllers\TaskController($service);
        }
        
        return null;
    }
}

// Función auxiliar para obtener headers si no existe getallheaders (en algunos entornos)
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}
