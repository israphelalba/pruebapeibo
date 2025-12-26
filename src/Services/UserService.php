<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UserRepository;
use Firebase\JWT\JWT;
use Exception;

/**
 * Servicio para la gestión de usuarios y autenticación.
 */
class UserService
{
    private UserRepository $repository;
    private array $jwtSettings;

    /**
     * Constructor con inyección de dependencias.
     */
    public function __construct(UserRepository $repository, array $jwtSettings)
    {
        $this->repository = $repository;
        $this->jwtSettings = $jwtSettings;
    }

    public function register(array $data): array
    {
        // Validaciones básicas
        if (empty($data['email']) || empty($data['password']) || empty($data['name'])) {
            throw new Exception("Nombre, email y contraseña son obligatorios.");
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Formato de email inválido.");
        }

        if ($this->repository->findByEmail($data['email'])) {
            throw new Exception("El email ya está registrado.");
        }

        $userId = $this->repository->create($data);
        return ['id' => $userId, 'name' => $data['name'], 'email' => $data['email']];
    }

    public function login(string $email, string $password): array
    {
        $user = $this->repository->findByEmail($email);

        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception("Credenciales inválidas.");
        }

        $token = $this->generateToken((int)$user['id']);

        return [
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ]
        ];
    }

    public function update(int $id, array $data): array
    {
        $user = $this->repository->findById($id);
        if (!$user) {
            throw new Exception("Usuario no encontrado.");
        }

        // Validar email si cambió
        if (isset($data['email']) && $data['email'] !== $user['email']) {
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception("Formato de email inválido.");
            }
            if ($this->repository->findByEmail($data['email'])) {
                throw new Exception("El email ya está registrado por otro usuario.");
            }
        }

        $updatedData = [
            'name' => $data['name'] ?? $user['name'],
            'email' => $data['email'] ?? $user['email'],
            'password' => $data['password'] ?? null
        ];

        $this->repository->update($id, $updatedData);

        return [
            'id' => $id,
            'name' => $updatedData['name'],
            'email' => $updatedData['email']
        ];
    }

    public function deleteAccount(int $id): void
    {
        $user = $this->repository->findById($id);
        if (!$user) {
            throw new Exception("Usuario no encontrado.");
        }
        $this->repository->delete($id);
    }

    private function generateToken(int $userId): string
    {
        $now = time();
        $payload = [
            'iat' => $now,
            'exp' => $now + $this->jwtSettings['expiration'],
            'sub' => $userId
        ];

        return JWT::encode($payload, $this->jwtSettings['secret'], 'HS256');
    }
}
