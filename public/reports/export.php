<?php
session_start();

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: /public/auth/login.php");
    exit;
}

require_once "../../src/Config/database.php";

// Get date range from URL parameters
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-12 months'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Validate date format
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
    die('Invalid date format');
}

// Fetch filtered subscriptions for export
$sql = "SELECT
    service_name,
    cost,
    currency,
    billing_cycle,
    start_date,
    category,
    created_at,
    CASE
        WHEN billing_cycle = 'monthly' THEN cost
        ELSE ROUND(cost / 12, 2)
    END as monthly_equivalent,
    CASE
        WHEN billing_cycle = 'yearly' THEN cost
        ELSE ROUND(cost * 12, 2)
    END as annual_equivalent
FROM subscriptions
WHERE user_id = ? AND start_date BETWEEN ? AND ?
ORDER BY start_date DESC";

$subscriptions = [];
$total_monthly = 0;
$total_annual = 0;

if($stmt = $pdo->prepare($sql)){
    $stmt->bindParam(1, $_SESSION["id"], PDO::PARAM_INT);
    $stmt->bindParam(2, $start_date, PDO::PARAM_STR);
    $stmt->bindParam(3, $end_date, PDO::PARAM_STR);

    if($stmt->execute()){
        $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate totals
        foreach($subscriptions as $sub){
            $total_monthly += $sub['monthly_equivalent'];
            $total_annual += $sub['annual_equivalent'];
        }
    }
    unset($stmt);
}

// Set CSV headers
$filename = 'subtrack_export_' . $start_date . '_to_' . $end_date . '.csv';
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');

// Open output stream
$output = fopen('php://output', 'w');

// Add BOM for UTF-8 (helps with Excel compatibility)
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write header information
fputcsv($output, ['SubTrack Subscription Export Report'], ',', '"', '\\');
fputcsv($output, ['Generated on: ' . date('Y-m-d H:i:s')], ',', '"', '\\');
fputcsv($output, ['Date Range: ' . $start_date . ' to ' . $end_date], ',', '"', '\\');
fputcsv($output, ['User: ' . $_SESSION['username']], ',', '"', '\\');
fputcsv($output, [], ',', '"', '\\'); // Empty row

// Write summary
fputcsv($output, ['SUMMARY'], ',', '"', '\\');
fputcsv($output, ['Total Subscriptions', count($subscriptions)], ',', '"', '\\');
fputcsv($output, ['Total Monthly Cost', '$' . number_format($total_monthly, 2)], ',', '"', '\\');
fputcsv($output, ['Total Annual Cost', '$' . number_format($total_annual, 2)], ',', '"', '\\');
if(count($subscriptions) > 0) {
    fputcsv($output, ['Average Monthly Cost per Service', '$' . number_format($total_monthly / count($subscriptions), 2)], ',', '"', '\\');
}
fputcsv($output, [], ',', '"', '\\'); // Empty row

// Write detailed data header
fputcsv($output, [
    'Service Name',
    'Original Cost',
    'Currency',
    'Billing Cycle',
    'Category',
    'Start Date',
    'Created Date',
    'Monthly Equivalent',
    'Annual Equivalent'
], ',', '"', '\\');

// Write subscription data
foreach($subscriptions as $subscription) {
    fputcsv($output, [
        $subscription['service_name'],
        number_format($subscription['cost'], 2),
        $subscription['currency'],
        ucfirst($subscription['billing_cycle']),
        $subscription['category'] ?: 'Other',
        $subscription['start_date'],
        date('Y-m-d', strtotime($subscription['created_at'])),
        '$' . number_format($subscription['monthly_equivalent'], 2),
        '$' . number_format($subscription['annual_equivalent'], 2)
    ], ',', '"', '\\');
}

// Add totals row
fputcsv($output, [], ',', '"', '\\'); // Empty row
fputcsv($output, [
    'TOTALS',
    '', '', '', '', '', '',
    '$' . number_format($total_monthly, 2),
    '$' . number_format($total_annual, 2)
], ',', '"', '\\');

// Category breakdown
$category_totals = [];
foreach($subscriptions as $sub) {
    $category = $sub['category'] ?: 'Other';
    if(!isset($category_totals[$category])) {
        $category_totals[$category] = 0;
    }
    $category_totals[$category] += $sub['monthly_equivalent'];
}

if(!empty($category_totals)) {
    fputcsv($output, [], ',', '"', '\\'); // Empty row
    fputcsv($output, ['CATEGORY BREAKDOWN (Monthly Equivalent)'], ',', '"', '\\');
    foreach($category_totals as $category => $total) {
        $percentage = ($total / $total_monthly) * 100;
        fputcsv($output, [
            $category,
            '$' . number_format($total, 2),
            number_format($percentage, 1) . '%'
        ], ',', '"', '\\');
    }
}

fclose($output);
exit;
?>