<?php

require_once __DIR__ . '/../Models/InsightsModel.php';
require_once __DIR__ . '/../Utils/AuditLogger.php';
require_once __DIR__ . '/../Config/csrf.php';

class InsightsController {
    private $insightsModel;
    private $auditLogger;
    private $csrfHandler;
    private $pdo;

    public function __construct($database_connection) {
        $this->pdo = $database_connection;
        $this->insightsModel = new InsightsModel($database_connection);
        $this->auditLogger = new AuditLogger($database_connection);
        $this->csrfHandler = new CSRFHandler();
    }

    /**
     * Display insights dashboard
     */
    public function dashboard() {
        // Check authentication
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            header("location: public/auth/login.php");
            exit;
        }

        $user_id = $_SESSION["id"];

        // Generate fresh insights
        $this->insightsModel->generateInsights($user_id);

        // Get insights and educational content
        $insights = $this->insightsModel->getUserInsights($user_id);
        $featured_content = $this->insightsModel->getEducationalContent(null, true, 3);
        $spending_goals = $this->insightsModel->getUserSpendingGoals($user_id);
        $achievements = $this->insightsModel->getUserAchievements($user_id);

        // Generate CSRF token
        $csrf_token = $this->csrfHandler->generateToken();

        // Prepare data for view
        $view_data = [
            'insights' => $insights,
            'featured_content' => $featured_content,
            'spending_goals' => $spending_goals,
            'achievements' => $achievements,
            'csrf_token' => $csrf_token,
            'page_title' => 'Financial Insights'
        ];

        // Load view
        $this->loadView('insights_dashboard', $view_data);
    }

    /**
     * Display educational content library
     */
    public function educationLibrary() {
        // Check authentication
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            header("location: public/auth/login.php");
            exit;
        }

        $user_id = $_SESSION["id"];
        $category = $_GET['category'] ?? null;

        // Get content
        $content = $this->insightsModel->getEducationalContent($category, false, 20);

        // Get categories for filter
        $categories = [
            'budgeting' => 'Budgeting',
            'saving_tips' => 'Saving Tips',
            'subscription_management' => 'Subscription Management',
            'financial_planning' => 'Financial Planning'
        ];

        // Generate CSRF token
        $csrf_token = $this->csrfHandler->generateToken();

        // Prepare data for view
        $view_data = [
            'content' => $content,
            'categories' => $categories,
            'selected_category' => $category,
            'csrf_token' => $csrf_token,
            'page_title' => 'Education Center'
        ];

        // Load view
        $this->loadView('education_library', $view_data);
    }

    /**
     * Display single educational content
     */
    public function viewEducationalContent($slug) {
        // Check authentication
        if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
            header("location: public/auth/login.php");
            exit;
        }

        $user_id = $_SESSION["id"];

        // Get content
        $content = $this->insightsModel->getEducationalContentBySlug($slug);
        if (!$content) {
            header("location: insights.php?action=education&error=content_not_found");
            exit;
        }

        // Track user started reading
        $this->insightsModel->updateEducationalProgress($user_id, $content['id'], 'started');

        // Get related content
        $related_content = $this->insightsModel->getEducationalContent($content['category'], false, 3);

        // Generate CSRF token
        $csrf_token = $this->csrfHandler->generateToken();

        // Prepare data for view
        $view_data = [
            'content' => $content,
            'related_content' => $related_content,
            'csrf_token' => $csrf_token,
            'page_title' => $content['title']
        ];

        // Load view
        $this->loadView('education_content', $view_data);
    }

    /**
     * Handle insight actions (dismiss, apply)
     */
    public function handleInsightAction() {
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
        $insight_id = $_POST["insight_id"] ?? null;
        $action = $_POST["action"] ?? null;

        if (!$insight_id || !$action) {
            echo json_encode(['status' => 'error', 'message' => 'Missing parameters']);
            exit;
        }

        $success = false;
        $message = '';

        // First verify the insight exists and belongs to the user
        $stmt = $this->pdo->prepare("SELECT id, status FROM insights WHERE id = ? AND user_id = ?");
        $stmt->execute([$insight_id, $user_id]);
        $insight = $stmt->fetch();

        if (!$insight) {
            echo json_encode(['status' => 'error', 'message' => 'Insight not found or access denied']);
            exit;
        }

        switch ($action) {
            case 'dismiss':
                $success = $this->insightsModel->dismissInsight($insight_id, $user_id);
                $message = $success ? 'Insight dismissed' : 'Failed to dismiss insight';

                if ($success) {
                    try {
                        $this->auditLogger->logActivity(
                            $user_id,
                            'insight_dismissed',
                            'insight',
                            $insight_id,
                            ['action' => 'dismiss', 'previous_status' => $insight['status']]
                        );
                    } catch (Exception $e) {
                        // Log audit error but don't fail the request
                        error_log("Audit logging failed for insight dismiss: " . $e->getMessage());
                    }
                }
                break;

            case 'apply':
                $success = $this->insightsModel->applyInsight($insight_id, $user_id);
                $message = $success ? 'Insight marked as applied' : 'Failed to apply insight';

                if ($success) {
                    try {
                        $this->auditLogger->logActivity(
                            $user_id,
                            'insight_applied',
                            'insight',
                            $insight_id,
                            ['action' => 'apply', 'previous_status' => $insight['status']]
                        );
                    } catch (Exception $e) {
                        // Log audit error but don't fail the request
                        error_log("Audit logging failed for insight apply: " . $e->getMessage());
                    }
                }
                break;

            default:
                echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
                exit;
        }

        echo json_encode([
            'status' => $success ? 'success' : 'error',
            'message' => $message
        ]);
    }

    /**
     * Create spending goal
     */
    public function createSpendingGoal() {
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
        $category = trim($_POST["category"] ?? '');
        $monthly_limit = floatval($_POST["monthly_limit"] ?? 0);

        if (empty($category) || $monthly_limit <= 0) {
            echo json_encode(['status' => 'error', 'message' => 'Category and valid monthly limit are required']);
            exit;
        }

        $success = $this->insightsModel->createSpendingGoal($user_id, $category, $monthly_limit);

        if ($success) {
            $this->auditLogger->logActivity(
                $user_id,
                'spending_goal_created',
                'goal',
                null,
                [
                    'category' => $category,
                    'monthly_limit' => $monthly_limit
                ]
            );

            echo json_encode([
                'status' => 'success',
                'message' => 'Spending goal created successfully'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to create spending goal']);
        }
    }

    /**
     * Mark educational content as completed
     */
    public function markContentCompleted() {
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
        $content_id = $_POST["content_id"] ?? null;

        if (!$content_id) {
            echo json_encode(['status' => 'error', 'message' => 'Content ID is required']);
            exit;
        }

        $success = $this->insightsModel->updateEducationalProgress($user_id, $content_id, 'completed', 100);

        if ($success) {
            // Award achievement if first completion
            $achievements = $this->insightsModel->getUserAchievements($user_id);
            $has_achievement = false;
            foreach ($achievements as $achievement) {
                if ($achievement['achievement_type'] === 'first_subscription') {
                    $has_achievement = true;
                    break;
                }
            }

            if (!$has_achievement) {
                $this->insightsModel->awardAchievement(
                    $user_id,
                    'first_subscription',
                    'Learning Starter',
                    'Completed your first educational article!',
                    ['content_id' => $content_id]
                );
            }

            $this->auditLogger->logActivity(
                $user_id,
                'education_completed',
                'education',
                $content_id,
                ['progress' => 100]
            );

            echo json_encode([
                'status' => 'success',
                'message' => 'Content marked as completed'
            ]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Failed to update progress']);
        }
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
            case 'insights_dashboard':
                include __DIR__ . '/../Views/dashboard/insights.php';
                break;
            case 'education_library':
                include __DIR__ . '/../Views/education/library.php';
                break;
            case 'education_content':
                include __DIR__ . '/../Views/education/content.php';
                break;
            default:
                include __DIR__ . "/../Views/education/{$view_name}.php";
                break;
        }
        // Include footer
        include __DIR__ . '/../Views/layouts/footer.php';
    }
}
?>