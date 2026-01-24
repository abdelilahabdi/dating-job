<?php

namespace Src\Models;

use Src\Core\BaseModel;
use PDO;

class JobApplicationModel extends BaseModel
{
    protected $table = 'job_applications';
    private ?string $lastError = null;

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function hasApplied(int $studentId, int $jobOfferId): bool
    {
        $this->lastError = null;
        try {
            $sql = "SELECT 1 FROM {$this->table} WHERE student_id = :student_id AND job_offer_id = :job_offer_id LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'student_id' => $studentId,
                'job_offer_id' => $jobOfferId,
            ]);

            return (bool) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function apply(int $studentId, int $jobOfferId, array $applicationData = []): bool
    {
        $this->lastError = null;
        $sql = "INSERT INTO {$this->table} (student_id, job_offer_id, cover_letter, phone, availability_date, expected_salary) 
                VALUES (:student_id, :job_offer_id, :cover_letter, :phone, :availability_date, :expected_salary)";
        $stmt = $this->pdo->prepare($sql);

        try {
            return $stmt->execute([
                'student_id' => $studentId,
                'job_offer_id' => $jobOfferId,
                'cover_letter' => $applicationData['cover_letter'] ?? null,
                'phone' => $applicationData['phone'] ?? null,
                'availability_date' => $applicationData['availability_date'] ?? null,
                'expected_salary' => $applicationData['expected_salary'] ?? null,
            ]);
        } catch (\PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function countByStudent(int $studentId): int
    {
        $this->lastError = null;
        try {
            $sql = "SELECT COUNT(*) FROM {$this->table} WHERE student_id = :student_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['student_id' => $studentId]);
            return (int) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            $this->lastError = $e->getMessage();
            return 0;
        }
    }

    public function hasAnyApplication(int $studentId): bool
    {
        $this->lastError = null;
        try {
            $sql = "SELECT 1 FROM {$this->table} WHERE student_id = :student_id LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['student_id' => $studentId]);
            return (bool) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function hasAcceptedApplication(int $studentId): bool
    {
        $this->lastError = null;
        try {
            $sql = "SELECT 1 FROM {$this->table} WHERE student_id = :student_id AND status = 'ACCEPTED' LIMIT 1";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['student_id' => $studentId]);
            return (bool) $stmt->fetchColumn();
        } catch (\PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function getStudentApplications(int $studentId): array
    {
        $this->lastError = null;
        try {
            $sql = "SELECT ja.*, jo.title as job_title, jo.location as job_location, 
                           c.name as company_name, c.email as company_email, c.phone as company_phone
                    FROM {$this->table} ja
                    JOIN job_offers jo ON ja.job_offer_id = jo.id
                    JOIN companies c ON jo.company_id = c.id
                    WHERE ja.student_id = :student_id
                    ORDER BY ja.applied_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['student_id' => $studentId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    public function getApplicationsByJobOffer(int $jobOfferId): array
    {
        $this->lastError = null;
        try {
            $sql = "SELECT ja.*, u.email as student_email, u.created_at as student_created_at,
                           s.promotion, s.specialization
                    FROM {$this->table} ja
                    JOIN users u ON ja.student_id = u.id
                    LEFT JOIN students s ON u.id = s.user_id
                    WHERE ja.job_offer_id = :job_offer_id
                    ORDER BY ja.applied_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['job_offer_id' => $jobOfferId]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    public function getStudentFullDetails(int $studentId): array
    {
        $this->lastError = null;
        try {
            $sql = "SELECT u.id, u.email, u.created_at,
                           s.promotion, s.specialization
                    FROM users u
                    LEFT JOIN students s ON u.id = s.user_id
                    WHERE u.id = :student_id AND u.role = 'STUDENT'";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['student_id' => $studentId]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$student) {
                return [];
            }

            // Récupérer toutes les candidatures de cet étudiant
            $sql = "SELECT ja.*, jo.title as job_title, c.name as company_name
                    FROM {$this->table} ja
                    JOIN job_offers jo ON ja.job_offer_id = jo.id
                    JOIN companies c ON jo.company_id = c.id
                    WHERE ja.student_id = :student_id
                    ORDER BY ja.applied_at DESC";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['student_id' => $studentId]);
            $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $student['applications'] = $applications;
            return $student;
        } catch (\PDOException $e) {
            $this->lastError = $e->getMessage();
            return [];
        }
    }

    public function updateApplicationStatus(int $applicationId, string $status): bool
    {
        $this->lastError = null;
        
        // Valider le statut
        if (!in_array($status, ['PENDING', 'ACCEPTED', 'REJECTED'])) {
            $this->lastError = 'Statut invalide';
            return false;
        }

        try {
            $sql = "UPDATE {$this->table} SET status = :status WHERE id = :application_id";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                'status' => $status,
                'application_id' => $applicationId,
            ]);
        } catch (\PDOException $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    public function findApplicationById(int $applicationId): ?array
    {
        $this->lastError = null;
        try {
            $sql = "SELECT ja.*, jo.title as job_title, c.name as company_name
                    FROM {$this->table} ja
                    JOIN job_offers jo ON ja.job_offer_id = jo.id
                    JOIN companies c ON jo.company_id = c.id
                    WHERE ja.id = :application_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['application_id' => $applicationId]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (\PDOException $e) {
            $this->lastError = $e->getMessage();
            return null;
        }
    }
}
