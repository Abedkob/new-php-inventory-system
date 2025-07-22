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
        $rand_access = rand(100000, 999999); // Generate a 6-digit random number

        $stmt = $this->conn->prepare("INSERT INTO user (username, password, rand_access) VALUES (?, ?, ?)");
        $stmt->bind_param("ssi", $username, $hashedPassword, $rand_access);

        if ($stmt->execute()) {
            return $rand_access;
        }
        return false;
    }

    public function findByUsername($username) {
        $query = "SELECT id, username, password, rand_access FROM user WHERE username = ?";
        
        $stmt = $this->conn->prepare($query);
        if ($stmt === false) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param("s", $username);
        $stmt->execute();
        
        return $stmt->get_result()->fetch_assoc();
    }

    public function updateRandAccess($userId) {
        $rand_access = rand(100000, 999999); // Generate a new 6-digit random number
        $stmt = $this->conn->prepare("UPDATE user SET rand_access = ? WHERE id = ?");
        $stmt->bind_param("ii", $rand_access, $userId);
        if ($stmt->execute()) {
            return $rand_access;
        }
        return false;
    }

    public function findByRandAccess($rand_access) {
        $stmt = $this->conn->prepare("SELECT id, username FROM user WHERE rand_access = ?");
        $stmt->bind_param("i", $rand_access);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }
}