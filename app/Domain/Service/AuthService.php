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
        // TODO: check that a user with same username does not exist, create new user and persist

        //check if username is availible, and if it is, it saves

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
    
        
        

        
        // TODO: make sure password is not stored in plain, and proper PHP functions are used for that

        // TODO: here is a sample code to start with
        

    

    public function attempt(string $username, string $password): bool
    {
        $user = $this->users->findByUsername($username);
        if ($user === null) {
            throw new \RuntimeException('User not found');
        }

        if (!password_verify($password, $user->passwordHash)) {
            throw new \RuntimeException('Password does not match');
        }

        return true;
    }
    
}
