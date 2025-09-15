<?php

require_once __DIR__ . '/../Models/CategoryModel.php';
require_once __DIR__ . '/../Utils/AuditLogger.php';
require_once __DIR__ . '/../Config/csrf.php';

class CategoryController {
    private $categoryModel;
    private $auditLogger;
    private $csrfHandler;
    private $pdo;

    public function __construct($database_connection) {
        $this->pdo = $database_connection;
        $this->categoryModel = new CategoryModel($database_connection);
        $this->auditLogger = new AuditLogger($database_connection);
        $this->csrfHandler = new CSRFHandler();
    }

    /**
     * Display category management page
     */
    public function index() {
        // Check authentication
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            header("location: auth.php?action=login");
            exit;
        }

        $user_id = $_SESSION["id"];

        // Get user categories and usage stats
        $categories = $this->categoryModel->getUserCategories($user_id);
        $usage_stats = $this->categoryModel->getCategoryUsage($user_id);

        // Generate CSRF token
        $csrf_token = $this->csrfHandler->generateToken();

        // Prepare data for view
        $view_data = [
            'categories' => $categories,
            'usage_stats' => $usage_stats,
            'csrf_token' => $csrf_token,
            'page_title' => 'Manage Categories'
        ];

        // Load view
        $this->loadView('category_management', $view_data);
    }

    /**
     * Create new category
     */
    public function createCategory() {
        header('Content-Type: application/json');

        // Check authentication
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        if($_SERVER["REQUEST_METHOD"] !== "POST") {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            exit;
        }

        // Verify CSRF token
        if(!$this->csrfHandler->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
            exit;
        }

        $user_id = $_SESSION["id"];
        $name = trim($_POST["name"] ?? '');
        $color = $_POST["color"] ?? '#6c757d';
        $icon = $_POST["icon"] ?? 'fas fa-tag';

        // Validate input
        $errors = $this->categoryModel->validateCategory($name, $color, $icon);
        if (!empty($errors)) {
            echo json_encode(['status' => 'validation_error', 'errors' => $errors]);
            exit;
        }

        // Create category
        $category_id = $this->categoryModel->createCategory($user_id, $name, $color, $icon);

        if ($category_id) {
            // Log the activity
            $this->auditLogger->logActivity(
                $user_id,
                'category_created',
                'category',
                $category_id,
                [
                    'name' => $name,
                    'color' => $color,
                    'icon' => $icon
                ]
            );

            echo json_encode([
                'status' => 'success',
                'message' => 'Category created successfully',
                'category' => [
                    'id' => $category_id,
                    'name' => $name,
                    'color' => $color,
                    'icon' => $icon
                ]
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Category name already exists']);
        }
    }

    /**
     * Update existing category
     */
    public function updateCategory() {
        header('Content-Type: application/json');

        // Check authentication
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        if($_SERVER["REQUEST_METHOD"] !== "POST") {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request method']);
            exit;
        }

        // Verify CSRF token
        if(!$this->csrfHandler->validateToken($_POST['csrf_token'] ?? '')) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid CSRF token']);
            exit;
        }

        $user_id = $_SESSION["id"];
        $category_id = $_POST["category_id"] ?? null;
        $name = trim($_POST["name"] ?? '');
        $color = $_POST["color"] ?? null;
        $icon = $_POST["icon"] ?? null;

        if (!$category_id) {
            echo json_encode(['status' => 'error', 'message' => 'Category ID is required']);
            exit;
        }

        // Validate input
        $errors = $this->categoryModel->validateCategory($name, $color, $icon);
        if (!empty($errors)) {
            echo json_encode(['status' => 'validation_error', 'errors' => $errors]);
            exit;
        }

        // Update category
        $success = $this->categoryModel->updateCategory($category_id, $user_id, $name, $color, $icon);

        if ($success) {
            // Log the activity
            $this->auditLogger->logActivity(
                $user_id,
                'category_updated',
                'category',
                $category_id,
                [
                    'name' => $name,
                    'color' => $color,
                    'icon' => $icon
                ]
            );

            echo json_encode([
                'status' => 'success',
                'message' => 'Category updated successfully'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update category or category not found']);
        }
    }

    /**
     * Delete category
     */
    public function deleteCategory() {
        header('Content-Type: application/json');

        // Check authentication
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $category_id = $_GET["id"] ?? null;
        $user_id = $_SESSION["id"];

        if (!$category_id) {
            echo json_encode(['status' => 'error', 'message' => 'Category ID is required']);
            exit;
        }

        // Get category info for logging
        $category = $this->categoryModel->getCategoryById($category_id, $user_id);
        if (!$category) {
            echo json_encode(['status' => 'error', 'message' => 'Category not found']);
            exit;
        }

        // Delete category
        $result = $this->categoryModel->deleteCategory($category_id, $user_id);

        if ($result['success']) {
            // Log the activity
            $this->auditLogger->logActivity(
                $user_id,
                'category_deleted',
                'category',
                $category_id,
                [
                    'name' => $category['name'],
                    'color' => $category['color']
                ]
            );
        }

        echo json_encode($result);
    }

    /**
     * Get categories for AJAX requests (for forms)
     */
    public function getCategories() {
        header('Content-Type: application/json');

        // Check authentication
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
            exit;
        }

        $user_id = $_SESSION["id"];
        $categories = $this->categoryModel->getUserCategories($user_id);

        echo json_encode([
            'status' => 'success',
            'categories' => $categories
        ]);
    }

    /**
     * Load view file
     */
    private function loadView($view_name, $data = []) {
        // Extract data to variables
        extract($data);
        // Include header
        include __DIR__ . '/../Views/layouts/header.php';
        // Include view (map view names to correct locations)
        switch($view_name) {
            case 'category_management':
                include __DIR__ . '/../Views/categories/management.php';
                break;
            default:
                include __DIR__ . "/../Views/categories/{$view_name}.php";
                break;
        }
        // Include footer
        include __DIR__ . '/../Views/layouts/footer.php';
    }
}
?>