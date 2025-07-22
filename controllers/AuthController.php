<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Response.php';
require_once __DIR__ . '/../utils/Validator.php';

class AuthController {
    private $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    public function register() {
        $data = json_decode(file_get_contents('php://input'), true);

        $error = Validator::validateRegister($data);
        if ($error) {
            Response::error($error);
        }

        $existingUser = $this->userModel->findByUsername($data['username']);
        if ($existingUser) {
            Response::error('Username already exists');
        }

        $rand_access = $this->userModel->create($data['username'], $data['password']);
        if (!$rand_access) {
            Response::error('Registration failed');
        }

        Response::json([
            'message' => 'Registration successful',
            'rand_access' => $rand_access
        ], 201);
    }

    public function login() {
        $data = json_decode(file_get_contents('php://input'), true);

        $error = Validator::validateLogin($data);
        if ($error) {
            Response::error($error);
        }

        $user = $this->userModel->findByUsername($data['username']);
        if (!$user || !password_verify($data['password'], $user['password'])) {
            Response::error('Invalid credentials');
        }

        // Generate new rand_access on login
        $new_rand_access = $this->userModel->updateRandAccess($user['id']);

        Response::json([
            'message' => 'Login successful',
            'rand_access' => $new_rand_access,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username']
            ]
        ]);
    }

    public function validateRandAccess() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($data['rand_access'])) {
            Response::error('rand_access required in request body', 401);
        }

        $rand_access = $data['rand_access'];
        $user = $this->userModel->findByRandAccess($rand_access);
        if (!$user) {
            Response::error('Invalid rand_access', 401);
        }

        Response::json([
            'valid' => true,
            'user' => $user
        ]);
    }
}