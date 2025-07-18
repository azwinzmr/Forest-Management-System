<?php
// config.php - Database connection settings

// Database credentials
define('DB_SERVER', 'localhost'); // Your database server
define('DB_USERNAME', 'root');    // Your database username (default for XAMPP)
define('DB_PASSWORD', '');        // Your database password (default for XAMPP is empty)
define('DB_NAME', 'forest_app');  // The name of your database

/* Attempt to connect to MySQL database */
$link = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Check connection
if($link === false){
    die("ERROR: Could not connect. " . mysqli_connect_error());
} else {
    // Optional: You can remove this 'echo' in production
    // echo "Database connected successfully!";
}

?>