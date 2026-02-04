<?php

namespace Src\Controllers;

use Src\Core\BaseController;
use Src\Core\Session;
use Src\Models\JobOfferModel;
use Src\Models\CompanyModel;
use Src\Models\StudentModel;
use Src\Models\JobApplicationModel;
use Exception;

class AdminController extends BaseController
{
    private JobOfferModel $jobOfferModel;
    private CompanyModel $companyModel;
    private StudentModel $studentModel;
    private JobApplicationModel $jobApplicationModel;

    public function __construct()
    {
        parent::__construct();
        $this->jobOfferModel = new JobOfferModel();
        $this->companyModel = new CompanyModel();
        $this->studentModel = new StudentModel();
        $this->jobApplicationModel = new JobApplicationModel();
    }

    public function dashboard(): void
    {
        // Vérifier si l'utilisateur est admin
        $user = Session::get('user');
        if (!$user || $user['role'] !== 'ADMIN') {
            $this->redirect('/login');
            return;
        }

        // Récupérer les statistiques
        $jobStats = $this->jobOfferModel->getStatistics();
        $companyStats = $this->companyModel->getStatistics();
        $students = $this->studentModel->getAll();

        $recentOffers = $this->jobOfferModel->getRecentOffers(3);
        $archivedOffers = $this->jobOfferModel->getArchivedOffers();
        $companies = $this->companyModel->getAll();
        $companiesWithOffers = $this->jobOfferModel->getCompaniesWithOffers();
        $contractTypes = $this->jobOfferModel->getContractTypes();

        $stats = array_merge($jobStats, $companyStats, [
            'students_count' => count($students)
        ]);

        $this->render('admin/dashboard.twig', [
            'stats' => $stats,
            'recentOffers' => $recentOffers,
            'archivedOffers' => $archivedOffers,
            'companies' => $companies,
            'companiesWithOffers' => $companiesWithOffers,
            'contractTypes' => $contractTypes,
            'students' => array_slice($students, 0, 5) // 5 derniers étudiants
        ]);
    }

    public function createJobOffer(): void
    {
        $user = Session::get('user');
        if (!$user || $user['role'] !== 'ADMIN') {
            $this->redirect('/login');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'title' => $_POST['title'] ?? '',
                'company_id' => $_POST['company_id'] ?? '',
                'contract_type' => $_POST['contract_type'] ?? '',
                'location' => $_POST['location'] ?? '',
                'description' => $_POST['description'] ?? '',
                'skills' => $_POST['skills'] ?? ''
            ];

            if (empty($data['title']) || empty($data['company_id']) || empty($data['contract_type'])) {
                Session::flash('errors', ['Tous les champs obligatoires doivent être remplis']);
                $this->redirect('/admin/dashboard');
                return;
            }

            try {
                $this->jobOfferModel->create($data);
                Session::flash('success', 'Annonce créée avec succès');
                $this->redirect('/admin/dashboard');
            } catch (Exception $e) {
                Session::flash('errors', ['Erreur lors de la création: ' . $e->getMessage()]);
                $this->redirect('/admin/dashboard');
            }
        }
    }

    public function createCompany(): void
    {
        $user = Session::get('user');
        if (!$user || $user['role'] !== 'ADMIN') {
            $this->redirect('/login');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'name' => $_POST['name'] ?? '',
                'sector' => $_POST['sector'] ?? '',
                'location' => $_POST['location'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? ''
            ];

            // Validation
            if (empty($data['name']) || empty($data['sector']) || empty($data['email'])) {
                Session::flash('errors', ['Les champs nom, secteur et email sont obligatoires']);
                $this->redirect('/admin/dashboard');
                return;
            }

            // Vérifier unicité email
            if ($this->companyModel->emailExists($data['email'])) {
                Session::flash('errors', ['Cet email est déjà utilisé']);
                $this->redirect('/admin/dashboard');
                return;
            }

            try {
                $this->companyModel->create($data);
                Session::flash('success', 'Entreprise créée avec succès');
                $this->redirect('/admin/dashboard');
            } catch (Exception $e) {
                Session::flash('errors', ['Erreur lors de la création: ' . $e->getMessage()]);
                $this->redirect('/admin/dashboard');
            }
        }
    }

    public function archiveJobOffer(): void
    {
        $user = Session::get('user');
        if (!$user || $user['role'] !== 'ADMIN') {
            $this->redirect('/login');
            return;
        }

        $id = $_POST['id'] ?? 0;
        if ($id > 0) {
            $result = $this->jobOfferModel->archive($id);
            if ($result) {
                Session::flash('success', 'Annonce archivée avec succès');
            } else {
                Session::flash('errors', ['Erreur lors de l\'archivage']);
            }
        }

        $this->redirect('/admin/dashboard');
    }

    public function restoreJobOffer(): void
    {
        $user = Session::get('user');
        if (!$user || $user['role'] !== 'ADMIN') {
            $this->redirect('/login');
            return;
        }

        $id = $_POST['id'] ?? 0;
        if ($id > 0) {
            $result = $this->jobOfferModel->restore($id);
            if ($result) {
                Session::flash('success', 'Annonce restaurée avec succès');
            } else {
                Session::flash('errors', ['Erreur lors de la restauration']);
            }
        }

        $this->redirect('/admin/dashboard');
    }

    public function updateCompany(): void
    {
        $user = Session::get('user');
        if (!$user || $user['role'] !== 'ADMIN') {
            $this->redirect('/login');
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id = $_POST['id'] ?? 0;
            $data = [
                'name' => $_POST['name'] ?? '',
                'sector' => $_POST['sector'] ?? '',
                'location' => $_POST['location'] ?? '',
                'email' => $_POST['email'] ?? '',
                'phone' => $_POST['phone'] ?? null
            ];

            // Validation
            if (empty($data['name']) || empty($data['sector']) || empty($data['email'])) {
                Session::flash('errors', ['Les champs nom, secteur et email sont obligatoires']);
                $this->redirect('/admin/dashboard');
                return;
            }

            if ($this->companyModel->emailExists($data['email'], $id)) {
                Session::flash('errors', ['Cet email est déjà utilisé par une autre entreprise']);
                $this->redirect('/admin/dashboard');
                return;
            }

            try {
                $this->companyModel->update($id, $data);
                Session::flash('success', 'Entreprise mise à jour avec succès');
                $this->redirect('/admin/dashboard');
            } catch (Exception $e) {
                Session::flash('errors', ['Erreur lors de la mise à jour: ' . $e->getMessage()]);
                $this->redirect('/admin/dashboard');
            }
        }
    }

    public function searchOffers(): void
    {
        $user = Session::get('user');
        if (!$user || $user['role'] !== 'ADMIN') {
            http_response_code(401);
            echo json_encode(['error' => 'Non autorisé']);
            return;
        }

        $searchTerm = $_GET['search'] ?? '';
        $companyId = $_GET['company_id'] ?? '';
        $contractType = $_GET['contract_type'] ?? '';

        $offers = $this->jobOfferModel->searchOffers($searchTerm, $companyId, $contractType);

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'offers' => $offers,
            'count' => count($offers)
        ]);
    }

    public function deleteCompany(): void
    {
        $user = Session::get('user');
        if (!$user || $user['role'] !== 'ADMIN') {
            $this->redirect('/login');
            return;
        }

        $id = $_POST['id'] ?? 0;
        if ($id > 0) {
            $result = $this->companyModel->delete($id);

            if ($result) {
                Session::flash('success', 'Entreprise supprimée avec succès');
            } else {
                Session::flash('errors', ['Impossible de supprimer cette entreprise. Des annonces y sont probablement associées.']);
            }
        }

        $this->redirect('/admin/dashboard');
    }

    public function jobApplications(string $id): void
    {
        $user = Session::get('user');
        if (!$user || $user['role'] !== 'ADMIN') {
            $this->redirect('/login');
            return;
        }

        $jobOfferId = (int) $id;
        if ($jobOfferId <= 0) {
            Session::flash('errors', ['Offre invalide']);
            $this->redirect('/admin/dashboard');
            return;
        }

        $jobOffer = $this->jobOfferModel->findActiveWithCompanyById($jobOfferId);
        if (!$jobOffer) {
            Session::flash('errors', ['Offre introuvable']);
            $this->redirect('/admin/dashboard');
            return;
        }

        $applications = $this->jobApplicationModel->getApplicationsByJobOffer($jobOfferId);

        $this->render('admin/job_applications', [
            'jobOffer' => $jobOffer,
            'applications' => $applications,
        ]);
    }

    public function studentDetails(): void
    {
        $user = Session::get('user');
        if (!$user || $user['role'] !== 'ADMIN') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Non autorisé']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Méthode non autorisée']);
            return;
        }

        $studentId = (int) ($_GET['student_id'] ?? 0);
        if ($studentId <= 0) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'ID étudiant invalide']);
            return;
        }

        $studentDetails = $this->jobApplicationModel->getStudentFullDetails($studentId);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['student' => $studentDetails]);
    }

    public function updateApplicationStatus(): void
    {
        $user = Session::get('user');
        if (!$user || $user['role'] !== 'ADMIN') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Non autorisé']);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Méthode non autorisée']);
            return;
        }

        $applicationId = (int) ($_POST['application_id'] ?? 0);
        $status = $_POST['status'] ?? '';

        if ($applicationId <= 0 || empty($status)) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'error' => 'Données invalides']);
            return;
        }

        $success = $this->jobApplicationModel->updateApplicationStatus($applicationId, $status);

        header('Content-Type: application/json; charset=utf-8');
        if ($success) {
            echo json_encode(['success' => true, 'message' => 'Statut mis à jour avec succès']);
        } else {
            $error = $this->jobApplicationModel->getLastError();
            echo json_encode(['success' => false, 'error' => $error ?: 'Erreur lors de la mise à jour']);
        }
    }
}
