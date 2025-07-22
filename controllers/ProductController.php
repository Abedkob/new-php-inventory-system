<?php
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Response.php';

class ProductController {
    private $productModel;
    private $userModel;

    public function __construct() {
        $this->productModel = new Product();
        $this->userModel = new User();
    }

    public function getAllProducts($rand_access) {
    // Verify user rand_access
    $user = $this->userModel->findByRandAccess($rand_access);
    if (!$user) {
        Response::error('Unauthorized', 401);
    }

    $products = $this->productModel->getAllProducts();
    Response::json($products);
}

public function getProductByBarcode($barcode, $includeQuantity = false, $rand_access = null) {
    if ($includeQuantity && !$rand_access) {
        Response::error('Unauthorized', 401);
    }

    if ($includeQuantity) {
        // Verify user rand_access
        $user = $this->userModel->findByRandAccess($rand_access);
        if (!$user) {
            Response::error('Unauthorized', 401);
        }
    }

    $product = $this->productModel->getProductByBarcode($barcode, $includeQuantity);
    if (!$product) {
        Response::error('Product not found', 404);
    }

    Response::json($product);
}

public function updateInventory($code, $quantity, $rand_access) {
    // Verify user rand_access
    $user = $this->userModel->findByRandAccess($rand_access);
    if (!$user) {
        Response::error('Unauthorized', 401);
    }

    if (!is_numeric($quantity) || $quantity < 0) {
        Response::error('Invalid quantity', 400);
    }

    $success = $this->productModel->updateInventory($code, (int)$quantity);
    if ($success) {
        Response::json(['message' => 'Inventory updated successfully']);
    } else {
        Response::error('Failed to update inventory', 500);
    }
}
}