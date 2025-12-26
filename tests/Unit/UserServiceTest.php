<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\UserService;
use App\Repositories\UserRepository;
use Exception;

class UserServiceTest extends TestCase
{
    private $userRepositoryMock;
    private UserService $userService;

    protected function setUp(): void
    {
        $this->userRepositoryMock = $this->createMock(UserRepository::class);
        $this->userService = new UserService($this->userRepositoryMock, ['expiration' => 3600, 'secret' => 'test']);
    }

    public function testRegisterSuccess()
    {
        $data = [
            'name' => 'Juan Perez',
            'email' => 'juan@example.com',
            'password' => 'password123'
        ];

        $this->userRepositoryMock->method('findByEmail')->willReturn(null);
        $this->userRepositoryMock->method('create')->willReturn(1);

        $result = $this->userService->register($data);

        $this->assertEquals(1, $result['id']);
        $this->assertEquals('Juan Perez', $result['name']);
    }

    public function testRegisterDuplicateEmailThrowsException()
    {
        $data = [
            'name' => 'Juan Perez',
            'email' => 'juan@example.com',
            'password' => 'password123'
        ];

        $this->userRepositoryMock->method('findByEmail')->willReturn(['id' => 1]);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("El email ya está registrado.");

        $this->userService->register($data);
    }

    public function testLoginSuccess()
    {
        $email = 'juan@example.com';
        $password = 'password123';
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        $this->userRepositoryMock->method('findByEmail')->willReturn([
            'id' => 1,
            'name' => 'Juan Perez',
            'email' => $email,
            'password' => $hashedPassword
        ]);

        $result = $this->userService->login($email, $password);

        $this->assertArrayHasKey('token', $result);
        $this->assertEquals(1, $result['user']['id']);
    }
}
