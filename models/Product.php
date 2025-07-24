<?php
require_once __DIR__ . '/../config/database.php';

class Product
{
    private $conn;

    public function __construct()
    {
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

    public function getAllProducts()
    {
        $query = "SELECT p.*, i.quantity 
              FROM products p
              LEFT JOIN inventory i ON p.barcode = i.code";

        $result = $this->conn->query($query);

        if (!$result) {
            error_log("MySQL Error in getAllProducts(): " . $this->conn->error);
            return [];
        }

        return $result->fetch_all(MYSQLI_ASSOC);
    }


    public function getProductByBarcode($barcode, $includeQuantity = false)
    {
        if ($includeQuantity) {
            $query = "SELECT p.*, i.quantity 
                  FROM products p
                  LEFT JOIN inventory i ON p.barcode = i.code
                  WHERE p.barcode = ?";
        } else {
            $query = "SELECT * FROM products WHERE barcode = ?";
        }

        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            // Log and return if SQL prepare failed
            error_log("SQL Prepare Failed: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("s", $barcode);
        $stmt->execute();

        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }


    public function updateInventory($code, $quantity)
    {
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
    public function updateAllOfficialRates($newRate)
    {
        $query = "UPDATE products SET official_rate = ?";
        $stmt = $this->conn->prepare($query);

        if (!$stmt) {
            error_log("SQL Prepare Failed: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("d", $newRate);
        return $stmt->execute();
    }
    public function updateProduct($barcode, $updateData)
    {
        // Validate input
        if (empty($barcode) || empty($updateData)) {
            return false;
        }

        // Prepare the update query
        $fields = [];
        $types = '';
        $values = [];

        foreach ($updateData as $field => $value) {
            // Only allow updating specific fields
            if (in_array($field, ['name', 'price', 'official_rate', 'image'])) {
                $fields[] = "$field = ?";

                // Determine parameter type
                if (is_int($value)) {
                    $types .= 'i';
                } elseif (is_float($value)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }

                $values[] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $query = "UPDATE products SET " . implode(', ', $fields) . " WHERE barcode = ?";
        $types .= 's'; // For barcode parameter
        $values[] = $barcode;

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("SQL Prepare Failed: " . $this->conn->error);
            return false;
        }

        $stmt->bind_param($types, ...$values);
        return $stmt->execute();
    }
    public function deleteProduct($productId)
    {
        $stmt = $this->conn->prepare("DELETE FROM products WHERE id = ?");
        if (!$stmt) {
            error_log("SQL Prepare Failed in deleteProduct(): " . $this->conn->error);
            return false;
        }

        $stmt->bind_param("i", $productId); // "i" stands for integer
        return $stmt->execute();
    }

    public function createProduct($productData)
    {
        // Validate required fields
        $requiredFields = ['name', 'barcode', 'price', 'official_rate'];
        foreach ($requiredFields as $field) {
            if (!isset($productData[$field]) || $productData[$field] === '') {
                return [
                    'success' => false,
                    'error' => "Missing required field: $field",
                    'status' => 400
                ];
            }
        }

        // Check for duplicate barcode
        $checkQuery = "SELECT id FROM products WHERE barcode = ?";
        $checkStmt = $this->conn->prepare($checkQuery);
        if (!$checkStmt) {
            error_log("Prepare failed: " . $this->conn->error);
            return [
                'success' => false,
                'error' => "Database error",
                'status' => 500
            ];
        }

        $checkStmt->bind_param("s", $productData['barcode']);
        $checkStmt->execute();
        $exists = $checkStmt->get_result()->fetch_assoc();
        $checkStmt->close();

        if ($exists) {
            return [
                'success' => false,
                'error' => "Product with this barcode already exists",
                'status' => 409
            ];
        }

        // Prepare the insert query - image is optional
        $query = "INSERT INTO products (name, barcode, price, official_rate" .
            (isset($productData['image']) ? ", image" : "") .
            ") VALUES (?, ?, ?" .
            (isset($productData['image']) ? ", ?, ?" : ", ?") . ")";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            error_log("SQL Prepare Failed: " . $this->conn->error);
            return [
                'success' => false,
                'error' => "Database error",
                'status' => 500
            ];
        }

        // Bind parameters based on whether image exists
        if (isset($productData['image'])) {
            $stmt->bind_param(
                "ssdds",
                $productData['name'],
                $productData['barcode'],
                $productData['price'],
                $productData['official_rate'],
                $productData['image']
            );
        } else {
            $stmt->bind_param(
                "ssdd",
                $productData['name'],
                $productData['barcode'],
                $productData['price'],
                $productData['official_rate']
            );
        }

        $success = $stmt->execute();
        $stmt->close();

        if ($success) {
            return [
                'success' => true,
                'message' => 'Product created successfully',
                'product_id' => $this->conn->insert_id,
                'status' => 201
            ];
        } else {
            error_log("Insert failed: " . $this->conn->error);
            return [
                'success' => false,
                'error' => "Failed to create product",
                'status' => 500
            ];
        }
    }

}