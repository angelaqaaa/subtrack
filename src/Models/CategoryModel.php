<?php

class CategoryModel {
    private $pdo;

    public function __construct($database_connection) {
        $this->pdo = $database_connection;
    }

    /**
     * Get all categories for a user (custom + default)
     */
    public function getUserCategories($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT id, name, color, icon, is_active, 'custom' as type
            FROM custom_categories
            WHERE user_id = ? AND is_active = TRUE
            ORDER BY name ASC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create a new custom category
     */
    public function createCategory($user_id, $name, $color = '#6c757d', $icon = 'fas fa-tag') {
        $stmt = $this->pdo->prepare("
            INSERT INTO custom_categories (user_id, name, color, icon)
            VALUES (?, ?, ?, ?)
        ");

        try {
            $stmt->execute([$user_id, trim($name), $color, $icon]);
            return $this->pdo->lastInsertId();
        } catch (PDOException $e) {
            // Handle duplicate category name
            if ($e->getCode() == 23000) {
                return false; // Duplicate category
            }
            throw $e;
        }
    }

    /**
     * Update an existing category
     */
    public function updateCategory($category_id, $user_id, $name, $color = null, $icon = null) {
        $sql = "UPDATE custom_categories SET name = ?";
        $params = [trim($name)];

        if ($color !== null) {
            $sql .= ", color = ?";
            $params[] = $color;
        }

        if ($icon !== null) {
            $sql .= ", icon = ?";
            $params[] = $icon;
        }

        $sql .= ", updated_at = NOW() WHERE id = ? AND user_id = ?";
        $params[] = $category_id;
        $params[] = $user_id;

        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Soft delete a category (mark as inactive)
     */
    public function deleteCategory($category_id, $user_id) {
        // First check if category is used by ANY subscriptions (active or inactive)
        $stmt = $this->pdo->prepare("
            SELECT COUNT(*) as usage_count
            FROM subscriptions s
            JOIN custom_categories c ON s.category = c.name
            WHERE c.id = ? AND c.user_id = ?
        ");
        $stmt->execute([$category_id, $user_id]);
        $result = $stmt->fetch();

        if ($result['usage_count'] > 0) {
            return ['success' => false, 'message' => 'Cannot delete category that has associated subscriptions. Please reassign or delete the subscriptions first.'];
        }

        // Soft delete
        $stmt = $this->pdo->prepare("
            UPDATE custom_categories
            SET is_active = FALSE, updated_at = NOW()
            WHERE id = ? AND user_id = ?
        ");

        if ($stmt->execute([$category_id, $user_id])) {
            return ['success' => true, 'message' => 'Category deleted successfully'];
        }

        return ['success' => false, 'message' => 'Failed to delete category'];
    }

    /**
     * Get category by ID for specific user
     */
    public function getCategoryById($category_id, $user_id) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM custom_categories
            WHERE id = ? AND user_id = ? AND is_active = TRUE
        ");
        $stmt->execute([$category_id, $user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get category usage statistics
     */
    public function getCategoryUsage($user_id) {
        $stmt = $this->pdo->prepare("
            SELECT
                c.name,
                c.color,
                COUNT(s.id) as subscription_count,
                SUM(CASE
                    WHEN s.billing_cycle = 'monthly' THEN s.cost
                    WHEN s.billing_cycle = 'yearly' THEN s.cost/12
                    WHEN s.billing_cycle = 'weekly' THEN s.cost * 52/12
                    ELSE s.cost
                END) as monthly_total
            FROM custom_categories c
            LEFT JOIN subscriptions s ON c.name = s.category AND s.user_id = c.user_id AND s.is_active = TRUE
            WHERE c.user_id = ? AND c.is_active = TRUE
            GROUP BY c.id, c.name, c.color
            ORDER BY monthly_total DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Validate category data
     */
    public function validateCategory($name, $color = null, $icon = null) {
        $errors = [];

        if (empty(trim($name))) {
            $errors['name'] = 'Category name is required';
        } elseif (strlen(trim($name)) > 100) {
            $errors['name'] = 'Category name must be 100 characters or less';
        }

        if ($color && !preg_match('/^#[a-fA-F0-9]{6}$/', $color)) {
            $errors['color'] = 'Invalid color format (use #RRGGBB)';
        }

        if ($icon && !preg_match('/^fas? fa-[\w-]+$/', $icon)) {
            $errors['icon'] = 'Invalid icon format (use FontAwesome classes)';
        }

        return $errors;
    }
}
?>