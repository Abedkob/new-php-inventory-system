<?php
require_once __DIR__ . '/../config/database.php';

class User {
    private $conn;

    public function __construct() {
        $config = require __DIR__ . '/../config/database.php';
        $this->conn = new mysqli(
            $config['host'],
            $config['username'],
            $config['password'],
            $config['database']
        );

        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    public function create($username, $password) {
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $token = bin2hex(random_bytes(32));

        $stmt = $this->conn->prepare("INSERT INTO user (username, password, token) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashedPassword, $token);

        if ($stmt->execute()) {
            return $token;
        }
        return false;
    }

public function findByUsername($username) {
    $query = "SELECT id, username, password, token FROM user WHERE username = ?";
    
    // Check if prepare() succeeds
    $stmt = $this->conn->prepare($query);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . $this->conn->error);
    }

    // Bind parameters and execute
    $stmt->bind_param("s", $username);
    $stmt->execute();
    
    return $stmt->get_result()->fetch_assoc();
}

    public function updateToken($userId, $token) {
        $stmt = $this->conn->prepare("UPDATE user SET token = ? WHERE id = ?");
        $stmt->bind_param("si", $token, $userId);
        return $stmt->execute();
    }

    public function findByToken($token) {
        $stmt = $this->conn->prepare("SELECT id, username FROM user WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}