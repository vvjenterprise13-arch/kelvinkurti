<?php

// Link to your database connection file
include('database/connection.php');

// Load website settings from the database
$settings = [];
if ($conn) {
    $result = mysqli_query($conn, "SELECT setting_key, setting_value FROM settings");
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en-IN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?><?php echo htmlspecialchars($settings['brand_name'] ?? 'Website'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .policy-container { background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .policy-header { background-color: #172337; color: white; padding: 1.25rem; border-top-left-radius: 8px; border-top-right-radius: 8px;}
        .policy-body { padding: 1.5rem; line-height: 1.7; }
        .policy-body h3 { margin-top: 1.5rem; margin-bottom: 0.75rem; color: #172337; }
        .breadcrumb a { text-decoration: none; color: #0d6efd; }
        .main-header { background-color: #fff; border-bottom: 1px solid #dee2e6; }
    </style>
</head>
<body>

