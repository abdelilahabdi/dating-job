<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Src\Controllers\AuthController;
use Src\Controllers\AdminController;
use Src\Controllers\StudentController;
use Src\Core\Router;

$router = Router::getRouter();

// Auth routes
$router->get('/login', [AuthController::class, 'showLoginPage']);
$router->post('/login', [AuthController::class, 'login']);

$router->get('/register', [AuthController::class, 'showRegisterPage']);
$router->post('/register', [AuthController::class, 'register']);

$router->post('/logout', [AuthController::class, 'logout']);

// Admin routes
$router->get('/admin/dashboard', [AdminController::class, 'dashboard']);
$router->get('/admin/search', [AdminController::class, 'searchOffers']);
$router->get('/admin/job-applications/{id}', [AdminController::class, 'jobApplications']);
$router->get('/admin/student-details', [AdminController::class, 'studentDetails']);
$router->post('/admin/update-application-status', [AdminController::class, 'updateApplicationStatus']);
$router->post('/admin/job-offer/create', [AdminController::class, 'createJobOffer']);
$router->post('/admin/company/create', [AdminController::class, 'createCompany']);
$router->post('/admin/company/update', [AdminController::class, 'updateCompany']);
$router->post('/admin/company/delete', [AdminController::class, 'deleteCompany']);
$router->post('/admin/job-offer/archive', [AdminController::class, 'archiveJobOffer']);
$router->post('/admin/job-offer/restore', [AdminController::class, 'restoreJobOffer']);

// Student routes
$router->get('/student/jobs', [StudentController::class, 'jobs']);
$router->get('/student/jobs/search', [StudentController::class, 'searchJobs']);
$router->get('/student/jobs/{id}', [StudentController::class, 'jobDetails']);
$router->get('/student/my-applications', [StudentController::class, 'myApplications']);
$router->post('/student/apply', [StudentController::class, 'apply']);

$router->dispatch();