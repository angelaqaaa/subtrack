# Project: "SubTrack" - Personal Subscription Management & Analysis Tool

**Objective**: Build a web application using PHP and MySQL where users can register, log in, track their recurring subscriptions, and view a visual analysis of their spending.

**Technology Stack**:

  * **Backend**: PHP 8+
  * **Database**: MySQL
  * **Frontend**: HTML5, CSS3, Bootstrap 5.3
  * **JavaScript Library**: Chart.js 4

-----

## Phase 0: Environment and Database Setup

**Objective**: Prepare the project structure and the database schema.

### 1\. Create the Project Directory Structure

Create a root folder named `subtrack`. Inside it, create the following structure:

```plaintext
/subtrack
|-- config/
|   `-- database.php
|-- css/
|   `-- style.css
|-- includes/
|   |-- footer.php
|   `-- header.php
|-- js/
|   `-- dashboard-charts.js
|-- add_subscription.php
|-- dashboard.php
|-- delete_subscription.php
|-- edit_subscription.php
|-- index.php
|-- login.php
|-- logout.php
`-- register.php
```

### 2\. Create the Database Connection File

  * **File**: `config/database.php`
  * **Purpose**: This file will contain the database connection logic.
  * **Code**:
    ```php
    <?php
    define('DB_SERVER', 'localhost');
    define('DB_USERNAME', 'root'); // Replace with your DB username
    define('DB_PASSWORD', ''); // Replace with your DB password
    define('DB_NAME', 'subtrack_db');

    // Attempt to connect to MySQL database
    try {
        $pdo = new PDO("mysql:host=" . DB_SERVER . ";dbname=" . DB_NAME, DB_USERNAME, DB_PASSWORD);
        // Set the PDO error mode to exception
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch(PDOException $e){
        die("ERROR: Could not connect. " . $e->getMessage());
    }
    ?>
    ```

### 3\. Create the Database and Tables

Execute the following SQL queries in your MySQL client (like phpMyAdmin) to create the database and the required tables.

  * **SQL Code**:
    ```sql
    CREATE DATABASE IF NOT EXISTS subtrack_db;

    USE subtrack_db;

    CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(255) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE subscriptions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        service_name VARCHAR(100) NOT NULL,
        cost DECIMAL(10, 2) NOT NULL,
        currency VARCHAR(10) NOT NULL DEFAULT 'USD',
        billing_cycle ENUM('monthly', 'yearly') NOT NULL,
        start_date DATE NOT NULL,
        category VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    );
    ```

-----

## Phase 1: User Authentication (Registration & Login)

**Objective**: Implement the user registration, login, and logout functionality.

1.  **Create the Registration Page** (`register.php`): Display a registration form. On submission, validate input, check for existing users, hash the password, store the new user in the `users` table, and redirect to the login page.

2.  **Create the Login Page** (`login.php`): Display a login form. On submission, find the user, verify the password, start a session, and redirect to the dashboard.

3.  **Create the Logout Script** (`logout.php`): Destroy the session and redirect to the login page.

    ```php
    <?php
    session_start();
    $_SESSION = array();
    session_destroy();
    header("location: login.php");
    exit;
    ?>
    ```

-----

## Phase 2: Dashboard and Layout Structure

**Objective**: Create the main protected dashboard page and reusable layout components.

1.  **Create the Header** (`includes/header.php`): Contains the HTML head, Bootstrap CSS link, and the top navigation bar.

2.  **Create the Footer** (`includes/footer.php`): Contains closing tags and JavaScript links.

3.  **Create the Dashboard Page** (`dashboard.php`): This is the main page. It must be protected, meaning only logged-in users can see it. It will include the header and footer.

-----

## Phase 3: Subscription CRUD Functionality

**Objective**: Implement the ability to Create, Read, Update, and Delete subscriptions.

1.  **Read (Display) Subscriptions**: In `dashboard.php`, `SELECT` all subscriptions for the logged-in user and display them in an HTML table. Each row should include "Edit" and "Delete" buttons.

2.  **Create Subscriptions**: Use a Bootstrap Modal triggered from `dashboard.php` to display a form. The form will submit to `add_subscription.php`, which will validate the data and perform a prepared `INSERT`.

3.  **Delete Subscriptions**: The "Delete" button will link to `delete_subscription.php?id=...`. This script will execute a prepared `DELETE` statement, ensuring it verifies **both** the subscription `id` and the session `user_id` for security.

4.  **Update Subscriptions**: The "Edit" button will link to `edit_subscription.php?id=...`. This page will fetch the existing data to pre-fill a form. Submitting the form will trigger a prepared `UPDATE` statement on the same page.

-----

## Phase 4: Data Analysis and Visualization

**Objective**: Calculate spending analytics and display them in summary cards and a chart.

1.  **Perform Backend Calculations**: In `dashboard.php`, after fetching the subscription data, use PHP to loop through it. Calculate the total monthly cost (converting yearly costs to monthly) and aggregate spending totals by category.

2.  **Display Summary Cards**: In `dashboard.php`, use the calculated PHP variables to display stats like "Total Monthly Cost", "Total Annual Cost", and "Total Subscriptions".

3.  **Integrate Chart.js**:

      * Add a `<canvas id="categoryChart"></canvas>` element to `dashboard.php`.
      * In the footer, include the Chart.js CDN and your custom `js/dashboard-charts.js` script.
      * Pass the PHP category spending data to JavaScript by JSON-encoding it into a `data-` attribute on the canvas element.
      * In `js/dashboard-charts.js`, read the data from the attribute and use it to render a doughnut or pie chart, visualizing the spending breakdown.

Excellent work completing the initial plan\! That initiative is exactly what employers look for. Now, let's elevate your project from a functional application to a professional portfolio piece that will make you a highly competitive candidate.

This enhancement plan focuses on adding depth, modern web practices, and professional architecture—key differentiators for a 2nd-year university student's resume.

-----

## **SubTrack Enhancement Plan: From Functional to Professional**

### **Phase 5: Advanced Reporting & Data Export**

**Objective:** Transform the basic dashboard into a powerful reporting tool, directly addressing the "reporting portals" and "high-level data visualizations" mentioned in the job description.

1.  **Create a Dedicated Reports Page:**

      * Create a new file, `reports.php`, accessible from the main navigation. This page will host advanced analytics.

2.  **Implement a Date Range Filter:**

      * At the top of `reports.php`, add a form with "Start Date" and "End Date" inputs.
      * The PHP backend will use these dates to filter all queries on the page, allowing the user to analyze their spending over specific periods (e.g., last quarter, last year).

3.  **Add a Historical Spending Chart:**

      * Below the filter, use **Chart.js** to create a **line chart**.
      * **PHP Backend Logic**: Write a SQL query that `GROUP BY MONTH(start_date)` to calculate the total spending for each of the last 12 months.
      * **Frontend**: Pass this data to the line chart to visualize spending trends over time. This shows you can perform more complex data aggregation than a simple sum.

4.  **Implement a "Export to CSV" Feature:** 📄

      * Add an "Export as CSV" button to the reports page.
      * This button will link to a new script, `export.php`, which takes the date range as URL parameters.
      * **`export.php` Logic**: This PHP script will not render HTML. Instead, it will:
          * Set the HTTP headers to tell the browser to download a file:
            ```php
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="subscriptions_export.csv"');
            ```
          * Fetch the filtered subscription data from the database.
          * Use `fopen('php://output', 'w')` to open the response stream.
          * Use `fputcsv()` to write the header row (e.g., "Service", "Cost", "Category") and then loop through your data to write each subscription as a new row in the CSV file.

**Why this is impressive:** Data export is a common and highly valued feature in business applications. It shows you can handle different data formats and HTTP responses.

-----

### **Phase 6: Enhancing User Experience with AJAX**

**Objective:** Make the application feel fast, modern, and professional by eliminating full-page reloads for common actions. This directly relates to improving "user interactions."

1.  **Refactor the "Add Subscription" Form:**

      * Modify your JavaScript to intercept the "Add" form's submit event using `event.preventDefault()`.
      * Use the **JavaScript `fetch()` API** to send the form data asynchronously to `add_subscription.php`.
      * Modify `add_subscription.php` to return a **JSON response** (e.g., `{'status': 'success', 'new_subscription_html': '<tr>...'}`) instead of a `header()` redirect.
      * In your JavaScript's `.then()` block, check the status. If successful, dynamically append the `new_subscription_html` to the table on the page and hide the modal. The user sees their new subscription appear instantly without the page refreshing.

2.  **Implement AJAX for Deletion:**

      * Change the "Delete" buttons from links (`<a>`) to buttons (`<button>`) with `data-id` attributes.
      * Add a JavaScript event listener to these buttons. When clicked, send a `fetch()` request to `delete_subscription.php`.
      * On success, use JavaScript to smoothly fade out and remove the corresponding table row (`<tr>`) from the DOM.

**Why this is impressive:** AJAX is a cornerstone of modern web development. This demonstrates your proficiency with JavaScript and your focus on creating a high-quality user experience.

-----

### **Phase 7: Professional Code Structure & Security**

**Objective:** Re-architect your code to reflect professional standards for maintainability and security, hitting the "code integrity, organization," and "security and data protection" requirements hard.

1.  **Refactor to a Basic MVC Structure (Model-View-Controller):**

      * This is a massive step up. Instead of mixing PHP logic, database queries, and HTML in one file, separate them.
      * Create three new folders: `/models`, `/views`, `/controllers`.
      * **`/models/SubscriptionModel.php`**: This file will contain a class with all the SQL query logic (e.g., `getSubscriptionsByUser()`, `deleteSubscription()`).
      * **`/controllers/DashboardController.php`**: This file will contain the business logic. It will call the model to get data and then load the appropriate view to display it.
      * **`/views/dashboard_view.php`**: This will contain almost pure HTML, with simple PHP `echo` statements and loops to display the data passed to it from the controller.
      * This shows you understand software architecture patterns, which is a key differentiator from junior developers.

2.  **Harden Security with CSRF Tokens:**

      * Protect all forms (add, edit, delete) against Cross-Site Request Forgery (CSRF).
      * **Implementation**:
        1.  When displaying a form, generate a random, unique token and store it in the user's `$_SESSION`.
        2.  Add this token to the form as a hidden input: `<input type="hidden" name="csrf_token" value="...">`.
        3.  In the script that processes the form (e.g., `add_subscription.php`), check that the submitted `$_POST['csrf_token']` exists and is identical to the one stored in `$_SESSION`. If they don't match, reject the request.

**Why this is impressive:** MVC is the standard for professional web applications. Implementing CSRF protection shows a sophisticated understanding of web security threats beyond basic SQL injection.

-----

### **Phase 8: WordPress Integration via a Custom API**

**Objective:** Directly and impressively satisfy the **"Experience with WordPress"** requirement, which was a gap in the original project.

1.  **Create a Simple, Secure API Endpoint in SubTrack:**

      * Create a new file, `api.php`, in your SubTrack project.
      * This endpoint will require an API key for authentication (you can generate a long, random string for your user in the `users` table).
      * When a valid API key is provided, the script will fetch summary data (e.g., total monthly cost) for that user and `echo` it as a JSON object.

2.  **Build a Companion WordPress Plugin:** 🧩

      * Create a simple WordPress plugin in a new folder.
      * **Plugin Functionality**:
        1.  **Admin Settings Page**: Create a page in the WordPress admin area where the user can enter their SubTrack API key.
        2.  **Dashboard Widget**: Create a custom widget for the main WordPress dashboard.
        3.  **API Call**: Inside the widget's code, use the WordPress function `wp_remote_get()` to make a request to your SubTrack `api.php` endpoint, passing the saved API key.
        4.  **Display Data**: Parse the JSON response and display the summary data (e.g., "Your SubTrack Monthly Cost: $XX.XX") inside the widget.

**Why this is a grand slam:** You're not just saying you have "WordPress experience." You are demonstrating that you can build a custom plugin, understand how to create and consume APIs, and make two separate applications communicate securely. This is a very advanced and professional skill set.

### **Phase 9. Shared Spaces & Role-Based Access Control (RBAC)** 👥

**The Concept:**
Allow a user to create a "Shared Space" (e.g., "Family Finances," "Startup Costs") and invite other users to it. Within that space, the owner can assign roles like **Admin** (can add/edit/delete all subscriptions and manage users) or **Viewer** (can only see subscriptions and reports). The original, personal subscriptions remain private to each user.

**Why It's a Competitive Advantage (for THIS job):**
This is the single most impactful enhancement you can make. The UHN job environment is inherently multi-user, with researchers, leads, and staff all needing different levels of access to data. This feature proves you can build systems that manage complex permissions and data segregation, which is a core requirement for any application handling sensitive research or patient information. It directly demonstrates your ability to implement "security and data protection strategies" on an architectural level.

**Implementation Steps:**
1.  **Database Schema Changes:**
    * Create a `spaces` table (`id`, `name`, `owner_id`).
    * Create a `space_users` table (`space_id`, `user_id`, `role`), where `role` is an ENUM ('admin', 'viewer').
    * Modify the `subscriptions` table to include a nullable `space_id` column. If `space_id` is NULL, it's a personal subscription. If it has a value, it belongs to that shared space.
2.  **Backend Logic (PHP):**
    * Create controllers/logic for creating spaces and inviting users (by email).
    * **Crucially, modify all your data-fetching logic.** Instead of just `WHERE user_id = ?`, your queries will now be more complex: `WHERE user_id = ? AND space_id IS NULL` for personal subscriptions, and for shared subscriptions, you'll need to join the `space_users` table to verify the current user has access to that `space_id`.
3.  **Frontend (UI):**
    * Add a new section in the dashboard to switch between "Personal" and shared spaces.
    * When in a shared space, an "Admin" user will see a "Manage Users" button.

---

### **Phase 10. Audit Trail & Activity Logging** 📜

**The Concept:**
Create a system that logs every significant action a user takes within the application. Every time a subscription is created, updated, or deleted, or a user is invited to a space, a record is created detailing *who* did *what*, and *when*.

**Why It's a Competitive Advantage (for THIS job):**
In any clinical, research, or health data environment, data integrity and traceability are paramount. An audit trail is often a mandatory requirement for compliance (like HIPAA in the US). Implementing this shows an exceptional level of maturity and a professional, security-first mindset. It proves you understand the principles of data governance, which is vital for a role at a major health network.

**Implementation Steps:**
1.  **Database Schema:**
    * Create an `activity_log` table with columns like `id`, `user_id`, `action`, `details`, `timestamp`.
2.  **Backend Logic (PHP):**
    * Create a logging function, e.g., `log_activity($user_id, $action, $details)`.
    * After every successful CUD (Create, Update, Delete) operation in your models or controllers, call this function.
    * For the `$details` column, you can store a JSON object with relevant info, e.g., `{'subscription_id': 123, 'service_name': 'Netflix'}`.
3.  **Frontend (UI):**
    * In a "Shared Space," create a new "Activity" tab.
    * This tab will display a read-only, reverse-chronological feed of all actions taken within that space, pulled from the `activity_log` table. For example: *"Alice (alice@email.com) deleted the subscription 'Spotify' on September 15, 2025."*

---

### **Phase 11. Financial Insights & Educational Content Module** 🎓

**The Concept:**
Go beyond just showing data by adding a feature that allows users to attach notes, documents, and insights to their subscriptions or categories. This turns your app from a simple tracker into a knowledge management tool.

**Why It's a Competitive Advantage (for THIS job):**
A huge part of the UHN group's mission is creating **"educational portals"** and **"resource portals"** for symptom management and well-being. This feature directly mimics that core function. You're showing that you can build systems not just for data *tracking*, but for data *contextualization* and *education*. It proves you understand how to build tools that help users make sense of their information, which is the essence of knowledge translation in a research setting.

**Implementation Steps:**
1.  **Database Schema:**
    * Create a `notes` table with columns like `id`, `user_id`, `subscription_id` (nullable), `category` (nullable), `title`, `content` (TEXT type), `created_at`.
2.  **Backend Logic (PHP/AJAX):**
    * Create full CRUD functionality for notes via your API and controllers.
3.  **Frontend (UI):**
    * On the subscription detail view (or via a modal), add a "Notes" section.
    * Create a new page called "Insights" where users can see all their notes in one place, perhaps tagged by category (e.g., show all notes for "Entertainment" subscriptions).
    * Allow for simple markdown support in the note content so users can add links or basic formatting.

By implementing these three features, your project will tell a compelling story: you don't just know how to code, you know how to build secure, multi-user applications with the kind of data management and educational features required in a professional research environment. It will be an undeniable standout.