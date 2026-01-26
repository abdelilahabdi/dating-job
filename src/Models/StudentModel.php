<?php

namespace Src\Models;

use Src\Core\BaseModel;

class StudentModel extends BaseModel
{
    protected $table = 'students';

    public function create($data): int
    {
        $sql = "INSERT INTO {$this->table} (user_id, promotion, specialization) VALUES (:user_id, :promotion, :specialization)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'user_id' => $data['user_id'],
            'promotion' => $data['promotion'],
            'specialization' => $data['specialization']
        ]);
        
        return (int)$this->pdo->lastInsertId();
    }

    public function getAll(): array
    {
        $sql = "SELECT s.*, u.email, u.created_at 
                FROM {$this->table} s 
                JOIN users u ON s.user_id = u.id 
                WHERE u.role = 'STUDENT'
                ORDER BY u.created_at DESC";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function findByUserId($userId): ?array
    {
        $sql = "SELECT s.*, u.email, u.role 
                FROM {$this->table} s 
                JOIN users u ON s.user_id = u.id 
                WHERE s.user_id = :user_id";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['user_id' => $userId]);
        
        $student = $stmt->fetch();
        return $student ?: null;
    }
}
