<?php

namespace Src\Controllers;

use Src\Core\BaseController;
use Src\Core\Session;
use Src\Core\Security;
use Src\Models\JobOfferModel;
use Src\Models\JobApplicationModel;

class StudentController extends BaseController
{
    private JobOfferModel $jobOfferModel;
    private JobApplicationModel $jobApplicationModel;

    public function __construct()
    {
        parent::__construct();
        $this->jobOfferModel = new JobOfferModel();
        $this->jobApplicationModel = new JobApplicationModel();
    }

    private function requireStudent(): ?array
    {
        $user = Session::get('user');
        if (!$user || ($user['role'] ?? null) !== 'STUDENT') {
            $this->redirect('/login');
            return null;
        }
        return $user;
    }

    public function jobs(): void
    {
        $user = $this->requireStudent();
        if (!$user) {
            return;
        }

        $search = $_GET['q'] ?? '';
        $companyId = $_GET['company_id'] ?? '';
        $contractType = $_GET['contract_type'] ?? '';

        $offers = $this->jobOfferModel->searchOffers($search, $companyId, $contractType);
        $hasAcceptedApplication = $this->jobApplicationModel->hasAcceptedApplication((int) $user['id']);

        $offers = array_map(static function ($offer) {
            return [
                'id' => $offer['id'] ?? null,
                'title' => $offer['title'] ?? '',
                'company_name' => $offer['company_name'] ?? '',
                'contract_type' => $offer['contract_type'] ?? '',
                'location' => $offer['location'] ?? '',
                'created_at' => $offer['created_at'] ?? '',
            ];
        }, $offers);

        $this->render('student/jobs', [
            'offers' => $offers,
            'hasAcceptedApplication' => $hasAcceptedApplication,
            'companiesWithOffers' => $this->jobOfferModel->getCompaniesWithOffers(),
            'contractTypes' => $this->jobOfferModel->getContractTypes(),
            'filters' => [
                'q' => $search,
                'company_id' => $companyId,
                'contract_type' => $contractType,
            ],
        ]);
    }

    public function jobDetails(string $id): void
    {
        $user = $this->requireStudent();
        if (!$user) {
            return;
        }

        $offerId = (int) $id;
        if ($offerId <= 0) {
            Session::flash('errors', ['Offre introuvable']);
            $this->redirect('/student/jobs');
            return;
        }

        $offer = $this->jobOfferModel->findActiveWithCompanyById($offerId);
        if (!$offer) {
            Session::flash('errors', ['Offre introuvable ou archivée']);
            $this->redirect('/student/jobs');
            return;
        }

        $hasApplied = $this->jobApplicationModel->hasApplied((int) $user['id'], $offerId);
        $hasAcceptedApplication = $this->jobApplicationModel->hasAcceptedApplication((int) $user['id']);

        $this->render('student/job_details', [
            'offer' => $offer,
            'hasApplied' => $hasApplied,
            'hasAcceptedApplication' => $hasAcceptedApplication,
        ]);
    }

    public function apply(): void
    {
        $user = $this->requireStudent();
        if (!$user) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/student/jobs');
            return;
        }

        if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            Session::flash('errors', ['Token CSRF invalide']);
            $this->redirect('/student/jobs');
            return;
        }

        $jobOfferId = (int) ($_POST['job_offer_id'] ?? 0);
        if ($jobOfferId <= 0 || !$this->jobOfferModel->isActive($jobOfferId)) {
            Session::flash('errors', ['Offre introuvable ou archivée']);
            $this->redirect('/student/jobs');
            return;
        }

        $studentId = (int) $user['id'];

        if ($this->jobApplicationModel->hasApplied($studentId, $jobOfferId)) {
            Session::flash('errors', ['Vous avez déjà postulé à cette offre']);
            $this->redirect('/student/jobs/' . $jobOfferId);
            return;
        }

        if ($this->jobApplicationModel->hasAcceptedApplication($studentId)) {
            Session::flash('errors', ['Vous avez déjà une candidature acceptée. Vous ne pouvez plus postuler à d\'autres offres.']);
            $this->redirect('/student/jobs/' . $jobOfferId);
            return;
        }

        // Valider les données du formulaire
        $coverLetter = trim($_POST['cover_letter'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $availabilityDate = $_POST['availability_date'] ?? '';
        $expectedSalary = trim($_POST['expected_salary'] ?? '');

        if (empty($coverLetter)) {
            Session::flash('errors', ['La lettre de motivation est obligatoire']);
            $this->redirect('/student/jobs/' . $jobOfferId);
            return;
        }

        $applicationData = [
            'cover_letter' => $coverLetter,
            'phone' => $phone ?: null,
            'availability_date' => $availabilityDate ?: null,
            'expected_salary' => $expectedSalary ?: null,
        ];

        $ok = $this->jobApplicationModel->apply($studentId, $jobOfferId, $applicationData);
        if ($ok) {
            Session::flash('success', 'Candidature envoyée avec succès');
        } else {
            $details = $this->jobApplicationModel->getLastError();
            if ($details && (strpos($details, "doesn't exist") !== false || strpos($details, 'Base table or view not found') !== false)) {
                Session::flash('errors', ['Table job_applications manquante dans la base de données. Exécute le SQL de création de table, puis réessaie.']);
            } elseif ($details) {
                Session::flash('errors', ["Erreur lors de l'envoi de la candidature: {$details}"]);
            } else {
                Session::flash('errors', ['Erreur lors de l\'envoi de la candidature']);
            }
        }

        $this->redirect('/student/jobs/' . $jobOfferId);
    }

    public function myApplications(): void
    {
        $user = $this->requireStudent();
        if (!$user) {
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => 'Méthode non autorisée']);
            return;
        }

        $applications = $this->jobApplicationModel->getStudentApplications((int) $user['id']);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['applications' => $applications]);
    }

    public function searchJobs(): void
    {
        $user = $this->requireStudent();
        if (!$user) {
            return;
        }

        $search = $_GET['q'] ?? '';
        $companyId = $_GET['company_id'] ?? '';
        $contractType = $_GET['contract_type'] ?? '';

        $offers = $this->jobOfferModel->searchOffers($search, $companyId, $contractType);

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'offers' => $offers,
        ]);
    }
}
