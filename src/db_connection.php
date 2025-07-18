<?php
function getDbConnection() {
    // === Database Connection Details ===
    $servername = "localhost";
    $username = "root";
    $password = ""; // Your database password
    $dbname = "forest_app";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        // Log the error for debugging, but don't expose sensitive info to user
        error_log("Database connection failed: " . $conn->connect_error);
        die("Connection to database failed. Please try again later.");
    }

    return $conn;
}
?>