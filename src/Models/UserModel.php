<?php

namespace Src\Models;

use Src\Core\BaseModel;
use Src\Core\Security;

class UserModel extends BaseModel
{
    protected $table = 'users';

    public function create($data): int
    {
        $sql = "INSERT INTO {$this->table} (email, password_hash, role) VALUES (:email, :password_hash, :role)";
        
        $hashedPassword = Security::hashPassword($data['password']);

        $role = $data['role'] ?? 'STUDENT';
        if ($role !== 'ADMIN' && $role !== 'STUDENT') {
            $role = 'STUDENT';
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'email' => $data['email'],
            'password_hash' => $hashedPassword,
            'role' => $role
        ]);
        
        return (int)$this->pdo->lastInsertId();
    }

    public function findByEmail($email): mixed
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['email' => $email]);
        
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function findById($id): mixed
    {
        $sql = "SELECT * FROM {$this->table} WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function verifyPassword($password, $hash): bool
    {
        return Security::verifyPassword($password, $hash);
    }

    public function emailExists($email): bool
    {
        return $this->findByEmail($email) !== null;
    }
}
