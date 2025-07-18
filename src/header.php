<?php
// header.php - Common header, navigation, and database connection for the Forest Simulation app

require_once 'config.php'; // Database connection

// You can add common PHP functions or data fetching here if needed across multiple pages

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ForestFlow: Forest Management System </title>
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="header-nav">
        <div class="logo">ForestFlow</div>
        <nav>
            <ul>
            <li><a href="home.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'home.php' ? 'active' : ''); ?>">Homepage</a></li>
            <li><a href="index.php" class="<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''); ?>">Dashboard</a></li>
            <?php // Other top-level links if needed, or if sidebar is not always visible ?>
        </ul>
        </nav>
    </div>
    <div class="container">
        ```
