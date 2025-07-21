<?php
require_once __DIR__ . '/../config/database.php';

class Product {
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

    public function getAllProducts() {
        $query = "SELECT p.*, i.quantity 
                  FROM products p
                  LEFT JOIN inventory i ON p.code = i.code";
        $result = $this->conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }

    public function getProductByBarcode($barcode, $includeQuantity = false) {
        if ($includeQuantity) {
            $query = "SELECT p.*, i.quantity 
                      FROM products p
                      LEFT JOIN inventory i ON p.code = i.code
                      WHERE p.barcode = ?";
        } else {
            $query = "SELECT * FROM products WHERE barcode = ?";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $barcode);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    public function updateInventory($code, $quantity) {
        // Check if inventory record exists
        $checkQuery = "SELECT id FROM inventory WHERE code = ?";
        $checkStmt = $this->conn->prepare($checkQuery);
        $checkStmt->bind_param("s", $code);
        $checkStmt->execute();
        $exists = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($exists) {
            // Update existing inventory
            $query = "UPDATE inventory SET quantity = ? WHERE code = ?";
        } else {
            // Insert new inventory record
            $query = "INSERT INTO inventory (code, quantity) VALUES (?, ?)";
        }

        $stmt = $this->conn->prepare($query);
        if ($exists) {
            $stmt->bind_param("is", $quantity, $code);
        } else {
            $stmt->bind_param("si", $code, $quantity);
        }

        return $stmt->execute();
    }
}