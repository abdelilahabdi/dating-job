<?php

namespace Src\Models;

use Src\Core\BaseModel;
use PDO;

class JobOfferModel extends BaseModel
{
    protected $table = 'job_offers';

    public function getStatistics(): array
    {
        $stats = [];
        
        // Annonces actives
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE deleted = false");
        $stmt->execute();
        $stats['active_offers'] = $stmt->fetch()['count'];
        
        // Annonces archivées
        $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM {$this->table} WHERE deleted = true");
        $stmt->execute();
        $stats['archived_offers'] = $stmt->fetch()['count'];
        
        return $stats;
    }

    public function getRecentOffers($limit = 3): array
    {
        $sql = "SELECT jo.*, c.name as company_name 
                FROM {$this->table} jo 
                JOIN companies c ON jo.company_id = c.id 
                WHERE jo.deleted = false 
                ORDER BY jo.created_at DESC 
                LIMIT :limit";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }

    public function getAllActive(): array
    {
        $sql = "SELECT jo.*, c.name as company_name 
                FROM {$this->table} jo 
                JOIN companies c ON jo.company_id = c.id 
                WHERE jo.deleted = false 
                ORDER BY jo.created_at DESC";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function findActiveWithCompanyById(int $id): ?array
    {
        $sql = "SELECT jo.*, 
                       c.name as company_name,
                       c.sector as company_sector,
                       c.location as company_location,
                       c.email as company_email,
                       c.phone as company_phone,
                       c.avatar as company_avatar
                FROM {$this->table} jo
                JOIN companies c ON jo.company_id = c.id
                WHERE jo.id = :id AND jo.deleted = false
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        $offer = $stmt->fetch();
        return $offer ?: null;
    }

    public function isActive(int $id): bool
    {
        $sql = "SELECT 1 FROM {$this->table} WHERE id = :id AND deleted = false LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);

        return (bool) $stmt->fetchColumn();
    }

    public function create($data): int
    {
        $sql = "INSERT INTO {$this->table} (title, company_id, contract_type, location, image, description, skills) 
                VALUES (:title, :company_id, :contract_type, :location, :image, :description, :skills)";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'title' => $data['title'],
            'company_id' => $data['company_id'],
            'contract_type' => $data['contract_type'],
            'location' => $data['location'],
            'image' => $data['image'] ?? null,
            'description' => $data['description'],
            'skills' => $data['skills'] ?? null
        ]);
        
        return (int)$this->pdo->lastInsertId();
    }

    public function archive($id): bool
    {
        $sql = "UPDATE {$this->table} SET deleted = true, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }

    public function getArchivedOffers(): array
    {
        $sql = "SELECT jo.*, c.name as company_name 
                FROM {$this->table} jo 
                JOIN companies c ON jo.company_id = c.id 
                WHERE jo.deleted = true 
                ORDER BY jo.created_at DESC";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function searchOffers($searchTerm = '', $companyId = '', $contractType = ''): array
    {
        $sql = "SELECT jo.*, c.name as company_name 
                FROM {$this->table} jo 
                JOIN companies c ON jo.company_id = c.id 
                WHERE jo.deleted = false";
        
        $params = [];
        
        // Ajouter les conditions de recherche
        if (!empty($searchTerm)) {
            $sql .= " AND (jo.title LIKE :search OR jo.description LIKE :search OR c.name LIKE :search)";
            $params['search'] = '%' . $searchTerm . '%';
        }
        
        if (!empty($companyId)) {
            $sql .= " AND jo.company_id = :company_id";
            $params['company_id'] = $companyId;
        }
        
        if (!empty($contractType)) {
            $sql .= " AND jo.contract_type = :contract_type";
            $params['contract_type'] = $contractType;
        }
        
        $sql .= " ORDER BY jo.created_at DESC";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }

    public function getCompaniesWithOffers(): array
    {
        $sql = "SELECT DISTINCT c.id, c.name 
                FROM companies c 
                INNER JOIN job_offers jo ON c.id = jo.company_id 
                WHERE jo.deleted = false 
                ORDER BY c.name";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll();
    }

    public function getContractTypes(): array
    {
        $sql = "SELECT DISTINCT contract_type 
                FROM {$this->table} 
                WHERE deleted = false 
                ORDER BY contract_type";
        
        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function restore($id): bool
    {
        $sql = "UPDATE {$this->table} SET deleted = false, updated_at = CURRENT_TIMESTAMP WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute(['id' => $id]);
    }
}
