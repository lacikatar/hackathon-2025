<?php

declare(strict_types=1);

namespace App\Domain\Service;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;

class AuthService
{
    public function __construct(
        private readonly UserRepositoryInterface $users,
    ) {}

    public function register(string $username, string $password): User
    {
        
       $available= $this ->users->findByUsername($username);
        if($available !== null)
        {
            throw new \RuntimeException('Username is already taken');
        }
        $hashed_passw=password_hash($password,PASSWORD_DEFAULT);
        $user = new User(null, $username, $hashed_passw, new \DateTimeImmutable());
        $this->users->save($user);
        
        
        return $user;
    }
    

    public function attempt(string $username, string $password): bool
    {
        $user = $this->users->findByUsername($username);
        if ($user === null) {
            throw new \RuntimeException('User not found');
        }

        if (!password_verify($password, $user->passwordHash)) {
            throw new \RuntimeException('Password does not match');
        }

        // Set session data
        $_SESSION['user_id'] = $user->id;
        $_SESSION['username'] = $user->username;
        
        // Regenerate session ID for security
        session_regenerate_id(true);

        return true;
    }

    public function logout(): void
    {
        // Clear all session data
        $_SESSION = [];
        
        // Destroy the session cookie
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time() - 3600, '/');
        }
        
        // Destroy the session
        session_destroy();
    }
}
