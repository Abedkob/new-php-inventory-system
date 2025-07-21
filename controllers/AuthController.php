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

        $token = $this->userModel->create($data['username'], $data['password']);
        if (!$token) {
            Response::error('Registration failed');
        }

        Response::json([
            'message' => 'Registration successful',
            'token' => $token
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

        // Generate new token on login
        $newToken = bin2hex(random_bytes(32));
        $this->userModel->updateToken($user['id'], $newToken);

        Response::json([
            'message' => 'Login successful',
            'token' => $newToken,
            'user' => [
                'id' => $user['id'],
                'username' => $user['username']
            ]
        ]);
    }

    public function validateToken() {
        $token = $headers['Authorization'] ?? null;
        $headers = getallheaders();

        if (!$token) {
            Response::error('Authorization token required', 401);
        }

        // Remove 'Bearer ' prefix if present
        $token = str_replace('Bearer ', '', $token);

        $user = $this->userModel->findByToken($token);
        if (!$user) {
            Response::error('Invalid token', 401);
        }

        Response::json([
            'valid' => true,
            'user' => $user
        ]);
    }
}