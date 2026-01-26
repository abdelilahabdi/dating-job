<?php

namespace Src\Controllers;

use Src\Core\BaseController;
use Src\Core\Session;
use Src\Core\Security;
use Src\Core\Validator;
use Src\Models\UserModel;
use Src\Models\StudentModel;
use Exception;

class AuthController extends BaseController
{
    private UserModel $userModel;
    private StudentModel $studentModel;

    public function __construct()
    {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->studentModel = new StudentModel();
    }

    public function showLoginPage(): void
    {
        // Si déjà connecté, rediriger selon le rôle
        $user = Session::get('user');
        if ($user) {
            $this->redirectBasedOnRole();
            return;
        }

        $this->render('auth/login', [
            'csrf_token' => Security::generateCSRFToken()
        ]);
    }

    public function showRegisterPage(): void
    {
        // Si déjà connecté, rediriger selon le rôle
        $user = Session::get('user');
        if ($user) {
            $this->redirectBasedOnRole();
            return;
        }

        $this->render('auth/register', [
            'csrf_token' => Security::generateCSRFToken()
        ]);
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/login');
            return;
        }

        // Vérifier CSRF
        if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            Session::flash('errors', ['Token CSRF invalide']);
            $this->redirect('/login');
            return;
        }

        // Récupérer et nettoyer les données
        $data = Security::sanitize($_POST);

        if (isset($data['role'])) {
            unset($data['role']);
        }

        // Validation
        $validator = new Validator();
        $isValid = $validator->validate($data, [
            'email' => 'required|email',
            'password' => 'required|min:6',
        ]);

        if (!$isValid) {
            Session::flash('errors', $validator->getErrors());
            Session::flash('old', $data);
            $this->redirect('/login');
            return;
        }

        // Vérifier l'utilisateur
        $user = $this->userModel->findByEmail($data['email']);
        if (!$user || !$this->userModel->verifyPassword($data['password'], $user['password_hash'])) {
            Session::flash('errors', ['Email ou mot de passe incorrect']);
            $this->redirect('/login');
            return;
        }

        // Connexion réussie
        Session::regenerate();
        Session::set('user', $user);

        $this->redirectBasedOnRole();
    }

    public function register(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/register');
            return;
        }

        // Vérifier CSRF
        if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            Session::flash('errors', ['Token CSRF invalide']);
            $this->redirect('/register');
            return;
        }

        // Récupérer et nettoyer les données
        $data = Security::sanitize($_POST);

        // Validation
        $validator = new Validator();
        $isValid = $validator->validate($data, [
            'email' => 'required|email',
            'password' => 'required|min:8',
            'password_confirm' => 'required|same:password',
            'promotion' => 'required',
            'specialization' => 'required',
        ]);

        $errors = $validator->getErrors();
        if (!empty($data['password'])) {
            $errors = array_merge($errors, Validator::validatePassword((string) $data['password']));
        }

        if (!$isValid || !empty($errors)) {
            Session::flash('errors', $errors);
            Session::flash('old', $data);
            $this->redirect('/register');
            return;
        }

        // Vérifier si l'email existe déjà
        if ($this->userModel->emailExists($data['email'])) {
            Session::flash('errors', ['Cet email est déjà utilisé']);
            Session::flash('old', $data);
            $this->redirect('/register');
            return;
        }

        try {
            // Créer l'utilisateur
            $userData = [
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'STUDENT'
            ];

            $userId = $this->userModel->create($userData);

            // Créer l'étudiant
            $studentData = [
                'user_id' => $userId,
                'promotion' => $data['promotion'],
                'specialization' => $data['specialization']
            ];

            $this->studentModel->create($studentData);

            // Connexion automatique
            $user = $this->userModel->findById($userId);
            Session::regenerate();
            Session::set('user', $user);

            $this->redirectBasedOnRole();

        } catch (Exception $e) {
            Session::flash('errors', ['Erreur lors de l\'inscription: ' . $e->getMessage()]);
            $this->redirect('/register');
        }
    }

    public function logout(): void
    {
        // Invalider le token CSRF
        Security::invalidateCSRFToken();

        // Détruire la session
        Session::destroy();

        $this->redirect('/login');
    }

    private function redirectBasedOnRole(): void
    {
        $user = Session::get('user');
        
        if ($user['role'] === 'ADMIN') {
            $this->redirect('/admin/dashboard');
        } else {
            $this->redirect('/student/jobs');
        }
    }
}
