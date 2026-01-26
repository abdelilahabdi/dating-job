<?php

namespace Src\Models;

use Src\Core\BaseModel;

class CompanyModel extends BaseModel
{
    protected $table = 'companies';

    public function getStatistics(): array
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM {$this->table}");
        $stmt->execute();
        return ['companies_count' => $stmt->fetch()['count']];
    }

    public function getAll(): array
    {
        $sql = "SELECT * FROM {$this->table} ORDER BY name ASC";
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function create($data): int
    {
        $sql = "INSERT INTO {$this->table} (name, sector, location, email, phone, avatar) 
                VALUES (:name, :sector, :location, :email, :phone, :avatar)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'name' => $data['name'],
            'sector' => $data['sector'],
            'location' => $data['location'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'avatar' => $data['avatar'] ?? $this->generateAvatar($data['name'])
        ]);
        
        return (int)$this->pdo->lastInsertId();
    }

    public function update($id, array $data): bool
    {
        $fields = [];
        $values = ['id' => $id];
        
        foreach ($data as $key => $value) {
            if ($key !== 'id' && $value !== null) {
                $fields[] = "$key = :$key";
                $values[$key] = $value;
            }
        }
        
        if (empty($fields)) {
            return false;
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($values);
    }

    public function delete($id): bool
    {
        // Vérifier si l'entreprise a des annonces
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM job_offers WHERE company_id = :id");
        $stmt->execute(['id' => $id]);
        $jobCount = $stmt->fetch()['count'];
        
        if ($jobCount > 0) {
            return false; // Ne pas supprimer si des annonces existent
        }
        
        return parent::delete($id);
    }

    public function emailExists($email, $excludeId = null): bool
    {
        $sql = "SELECT id FROM {$this->table} WHERE email = :email";
        $params = ['email' => $email];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetch() !== false;
    }

    private function generateAvatar(string $companyName): string
    {
        // Générer un avatar basé sur les initiales
        $words = explode(' ', $companyName);
        $initials = '';
        
        foreach ($words as $word) {
            $initials .= strtoupper(substr($word, 0, 1));
        }
        
        return substr($initials, 0, 2);
    }
}
