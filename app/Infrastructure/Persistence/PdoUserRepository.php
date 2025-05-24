<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Entity\User;
use App\Domain\Repository\UserRepositoryInterface;
use DateTimeImmutable;
use Exception;
use PDO;

class PdoUserRepository implements UserRepositoryInterface
{
    public function __construct(
        private readonly PDO $pdo,
    ) {}

    /**
     * @throws Exception
     */
    public function find(mixed $id): ?User
    {
        $query = 'SELECT * FROM users WHERE id = :id';
        $statement = $this->pdo->prepare($query);
        $statement->execute(['id' => $id]);
        $data = $statement->fetch();
        if (false === $data) {
            return null;
        }

        return new User(
            $data['id'],
            $data['username'],
            $data['password_hash'],
            new DateTimeImmutable($data['created_at']),
        );
    }

    public function findByUsername(string $username): ?User
    {
       $query = 'SELECT * from users where username = :username';
       $stmt = $this->pdo->prepare($query);
       $stmt->execute(['username'=> $username]);
       $result= $stmt->fetch();
       if($result === false)
       {
            return null;
       }

          return new User(
            $result['id'],
            $result['username'],
            $result['password_hash'],
            new DateTimeImmutable($result['created_at']),
        );
    }

    public function save(User $user): void
    {
        $query = 'INSERT into users (username, password_hash, created_at) values (:username, :password_hash, :created_at)';
        $stmt = $this->pdo->prepare($query);

    $stmt->execute([ 'username' => $user->username,
        'password_hash' => $user->passwordHash,
        'created_at' => $user -> createdAt->format('Y-m-d H:i:s'),
    ]);

    $user->id=(int)$this->pdo->lastInsertId();
    }

    
}