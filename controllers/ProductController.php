<?php
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../utils/Response.php';

class ProductController
{
    private $productModel;
    private $userModel;

    public function __construct()
    {
        $this->productModel = new Product();
        $this->userModel = new User();
    }

    public function getAllProducts($rand_access)
    {
        // Verify user rand_access
        $user = $this->userModel->findByRandAccess($rand_access);
        if (!$user) {
            Response::error('Unauthorized', 401);
        }

        $products = $this->productModel->getAllProducts();
        Response::json($products);
    }

    public function getProductByBarcode($barcode, $includeQuantity = false, $rand_access = null)
    {
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

    public function updateInventory($code, $quantity, $rand_access)
    {
        // Verify user rand_access
        $user = $this->userModel->findByRandAccess($rand_access);
        if (!$user) {
            Response::error('Unauthorized', 401);
        }

        if (!is_numeric($quantity) || $quantity < 0) {
            Response::error('Invalid quantity', 400);
        }

        $success = $this->productModel->updateInventory($code, (int) $quantity);
        if ($success) {
            Response::json(['message' => 'Inventory updated successfully']);
        } else {
            Response::error('Failed to update inventory', 500);
        }
    }


    public function updateAllOfficialRates($newRate, $rand_access)
    {
        // Verify user rand_access
        $user = $this->userModel->findByRandAccess($rand_access);
        if (!$user) {
            Response::error('Unauthorized', 401);
        }

        if (!is_numeric($newRate) || $newRate <= 0) {
            Response::error('Invalid official rate value', 400);
        }

        $success = $this->productModel->updateAllOfficialRates((float) $newRate);
        if ($success) {
            Response::json(['message' => 'All official rates updated successfully']);
        } else {
            Response::error('Failed to update official rates', 500);
        }
    }
    public function updateProduct($barcode, $updateData, $rand_access)
    {
        // Verify user rand_access
        $user = $this->userModel->findByRandAccess($rand_access);
        if (!$user) {
            Response::error('Unauthorized', 401);
        }

        // Validate the product exists
        $product = $this->productModel->getProductByBarcode($barcode);
        if (!$product) {
            Response::error('Product not found', 404);
        }

        // Validate update data
        if (empty($updateData) || !is_array($updateData)) {
            Response::error('Invalid update data', 400);
        }

        // Convert numeric fields
        if (isset($updateData['price'])) {
            $updateData['price'] = (float) $updateData['price'];
        }
        if (isset($updateData['official_rate'])) {
            $updateData['official_rate'] = (float) $updateData['official_rate'];
        }

        // Perform the update
        $success = $this->productModel->updateProduct($barcode, $updateData);
        if ($success) {
            Response::json([
                'success' => true,
                'message' => 'Product updated successfully',
                'product' => $this->productModel->getProductByBarcode($barcode)
            ]);
        } else {
            Response::error('Failed to update product', 500);
        }
    }

    public function deleteProduct($productId, $rand_access)
    {
        $user = $this->userModel->findByRandAccess($rand_access);
        if (!$user) {
            Response::error('Unauthorized', 401);
        }

        if (empty($productId)) {
            Response::error('Product ID is required', 400);
        }

        $success = $this->productModel->deleteProduct($productId);
        if ($success) {
            Response::json(['message' => 'Product deleted successfully']);
        } else {
            Response::error('Failed to delete product', 500);
        }
    }
    public function createProduct($productData, $rand_access)
{
    // Verify user rand_access
    $user = $this->userModel->findByRandAccess($rand_access);
    if (!$user) {
        Response::error('Unauthorized', 401);
    }

    // Validate required fields
    $requiredFields = ['name', 'barcode', 'price', 'official_rate'];
    foreach ($requiredFields as $field) {
        if (!isset($productData[$field])) {
            Response::error("Missing required field: $field", 400);
        }
    }

    // Convert numeric fields
    $productData['price'] = (float) $productData['price'];
    $productData['official_rate'] = (float) $productData['official_rate'];

    // Check if product with this barcode already exists
    $existingProduct = $this->productModel->getProductByBarcode($productData['barcode']);
    if ($existingProduct) {
        Response::error('Product with this barcode already exists', 400);
    }

    // Create the product
    $success = $this->productModel->createProduct($productData);
    if ($success) {
        Response::json([
            'success' => true,
            'message' => 'Product created successfully',
            'product' => $this->productModel->getProductByBarcode($productData['barcode'])
        ], 201); // 201 Created status code
    } else {
        Response::error('Failed to create product', 500);
    }
}
}