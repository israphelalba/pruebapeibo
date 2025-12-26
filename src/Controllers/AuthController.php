<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\UserService;

class AuthController
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function register(array $params = []): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $user = $this->userService->register($data);
            $this->jsonResponse([
                'mensaje' => 'Usuario registrado exitosamente',
                'datos' => $user
            ], 201);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function login(array $params = []): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $result = $this->userService->login($data['email'] ?? '', $data['password'] ?? '');
            $this->jsonResponse($result);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 401);
        }
    }

    public function update(array $params = [], ?int $userId = null): void
    {
        $data = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $user = $this->userService->update($userId, $data);
            $this->jsonResponse([
                'mensaje' => 'Perfil actualizado exitosamente',
                'datos' => $user
            ]);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    public function delete(array $params = [], ?int $userId = null): void
    {
        try {
            $this->userService->deleteAccount($userId);
            $this->jsonResponse(['mensaje' => 'Cuenta eliminada exitosamente']);
        } catch (\Exception $e) {
            $this->jsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    private function jsonResponse(array $data, int $status = 200): void
    {
        header('Content-Type: application/json');
        http_response_code($status);
        echo json_encode($data);
    }
}
