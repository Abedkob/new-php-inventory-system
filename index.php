<?php
require_once __DIR__ . '/controllers/AuthController.php';
require_once __DIR__ . '/controllers/ProductController.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log requests for debugging
file_put_contents('debug.log', print_r([
    'REQUEST_URI' => $_SERVER['REQUEST_URI'],
    'REQUEST_METHOD' => $_SERVER['REQUEST_METHOD'],
    'TIME' => date('Y-m-d H:i:s')
], true), FILE_APPEND);

// Set headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, PUT, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Content-Type: application/json");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Initialize controllers
$authController = new AuthController();
$productController = new ProductController();

// Get request URI and method
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$basePath = '/api_auth';
$requestUri = str_replace($basePath, '', $requestUri);
$method = $_SERVER['REQUEST_METHOD'];

// Get Authorization header
$data = json_decode(file_get_contents('php://input'), true);
$rand_access = $data['rand_access'] ?? null;



// Route requests
switch ($requestUri) {
    // Authentication endpoints
   case '/register':
    if ($method === 'POST') {
        $authController->register();
    } else {
        Response::error('Method not allowed', 405);
    }
    break;
    
case '/login':
    if ($method === 'POST') {
        $authController->login();
    } else {
        Response::error('Method not allowed', 405);
    }
    break;
    
case '/validate-access':
    if ($method === 'POST') {  
        $authController->validateRandAccess();
    } else {
        Response::error('Method not allowed', 405);
    }
    break;
        
    // Product endpoints
    case '/products':
        if ($method === 'POST') {
            $productController->getAllProducts($rand_access);
        } else {
            Response::error('Method not allowed', 405);
        }
        break;
        
    case '/products/by-barcode':
        if ($method === 'GET') {
            $barcode = $_GET['barcode'] ?? null;
            if (!$barcode) {
                Response::error('Barcode parameter is required', 400);
            }
            $productController->getProductByBarcode($barcode, false);
        } else {
            Response::error('Method not allowed', 405);
        }
        break;
        
   case '/products/by-barcode-with-quantity':
    if ($method === 'GET') {
        $barcode = $_GET['barcode'] ?? null;
        $rand_access = $_GET['rand_access'] ?? null; // Get from query params
        
        if (!$barcode) {
            Response::error('Barcode parameter is required', 400);
        }
        if (!$rand_access) {
            Response::error('rand_access parameter is required', 400);
        }
        
        $productController->getProductByBarcode($barcode, true, $rand_access);
    } else {
        Response::error('Method not allowed', 405);
    }
    break;
        
   case '/inventory/update':
    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (empty($data['code']) || !isset($data['quantity'])) {
            Response::error('Code and quantity are required', 400);
        }
        if (!isset($data['rand_access'])) {
            Response::error('rand_access is required', 400);
        }
        $productController->updateInventory($data['code'], $data['quantity'], $data['rand_access']);
    } else {
        Response::error('Method not allowed', 405);
    }
    break;

default:
    Response::error('Endpoint not found', 404);
    break;
}