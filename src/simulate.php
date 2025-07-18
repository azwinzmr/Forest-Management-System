<?php
// simulate.php - Main script to run the forest simulation and populate the database

ini_set('max_execution_time', 1800); // Set max execution time to 30 minutes (1800 seconds)
set_time_limit(1800); // Also set via set_time_limit
error_reporting(E_ALL); // Report all errors
ini_set('display_errors', 1); // Display errors for debugging

require_once 'config.php';    // Database connection
require_once 'functions.php'; // All simulation and calculation functions

echo "<h1>Forest Ecosystem Simulation</h1>";

// Get the desired tree count from the GET parameter, default to a reasonable number if not provided
$desiredTreeCount = isset($_GET['tree_count']) ? intval($_GET['tree_count']) : 50000;
if ($desiredTreeCount <= 0) {
    $desiredTreeCount = 50000; // Ensure it's a positive number
}

echo "<p>Starting simulation and database population for " . number_format($desiredTreeCount) . " trees. This may take a while...</p>";
echo "<hr>";

// 1. Load species data
$speciesFilePath = __DIR__ . '/species.csv';
$allSpeciesData = loadSpeciesData($speciesFilePath);

if (empty($allSpeciesData)) {
    die("Error: Could not load species data. Simulation aborted.");
}

// 2. Run the full simulation, passing the desired tree count
$fellingDiameterThreshold = 45.0; // Default felling criteria diameter
$finalTreesData = runFullForestSimulation($allSpeciesData, $fellingDiameterThreshold, $desiredTreeCount); // Pass desiredTreeCount

echo "<hr>";
echo "<h2>Database Insertion</h2>";

// 3. Prepare for database insertion
$stmt = null; // Initialize statement variable
$insertSuccessCount = 0;
$insertErrorCount = 0;

if (!empty($finalTreesData)) {
    // Clear existing data from the trees table before inserting new data
    // This ensures you always have a fresh simulation result
    echo "<p>Clearing existing tree data from the database...</p>";
    if (mysqli_query($link, "TRUNCATE TABLE trees")) {
        echo "<p>Previous tree data cleared successfully.</p>";
    } else {
        echo "<p class='error'>Error clearing previous tree data: " . mysqli_error($link) . "</p>";
    }

    // Prepare an INSERT statement
    $sql = "INSERT INTO trees (tree_id, block_i, block_j, coord_x, coord_y, species_name, species_group, diameter, diameter_class, height, is_dipterocarp, volume, felling_criteria, status, cut_angle, damage_stem, damage_crown, prod, diameter30, volume30, prod30) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    if ($stmt = mysqli_prepare($link, $sql)) {
        // Bind parameters to the prepared statement
        // s: string, i: integer, d: double (float)
        mysqli_stmt_bind_param($stmt, "siiiisidddisssssddddd",
            $tree_id, $block_i, $block_j, $coord_x, $coord_y, $species_name, $species_group,
            $diameter, $diameter_class, $height, $is_dipterocarp, $volume, $felling_criteria,
            $status, $cut_angle, $damage_stem, $damage_crown, $prod, $diameter30, $volume30, $prod30
        );

        // Loop through each tree and insert into the database
        foreach ($finalTreesData as $tree) {
            $tree_id = $tree['tree_id'];
            $block_i = $tree['block_i'];
            $block_j = $tree['block_j'];
            $coord_x = $tree['coord_x'];
            $coord_y = $tree['coord_y'];
            $species_name = $tree['species_name'];
            $species_group = $tree['species_group'];
            $diameter = $tree['diameter'];
            $diameter_class = $tree['diameter_class'];
            $height = $tree['height'];
            $is_dipterocarp = $tree['is_dipterocarp'];
            $volume = $tree['volume'];
            $felling_criteria = $tree['felling_criteria'];
            $status = $tree['status'];

            // Handle nullable fields and '-' placeholders
            $cut_angle = ($tree['cut_angle'] === '-') ? null : $tree['cut_angle'];
            $damage_stem = ($tree['damage_stem'] === '-') ? null : $tree['damage_stem'];
            $damage_crown = ($tree['damage_crown'] === '-') ? null : $tree['damage_crown'];
            $prod = ($tree['prod'] === '-') ? null : $tree['prod'];
            $diameter30 = ($tree['diameter30'] === '-') ? null : $tree['diameter30'];
            $volume30 = ($tree['volume30'] === '-') ? null : $tree['volume30'];
            $prod30 = ($tree['prod30'] === '-') ? null : $tree['prod30'];

            if (mysqli_stmt_execute($stmt)) {
                $insertSuccessCount++;
            } else {
                echo "Error inserting tree " . $tree_id . ": " . mysqli_stmt_error($stmt) . "<br>";
                $insertErrorCount++;
            }
        }
        mysqli_stmt_close($stmt);
        echo "Database insertion complete.<br>";
        echo "Successfully inserted " . $insertSuccessCount . " trees.<br>";
        echo "Failed to insert " . $insertErrorCount . " trees.<br>";
    } else {
        echo "ERROR: Could not prepare statement. " . mysqli_error($link) . "<br>";
    }
} else {
    echo "No tree data to insert into the database.<br>";
}

// Close database connection
mysqli_close($link);

echo "<p>Simulation and database population finished. <a href='index.php'>Go to Dashboard</a></p>";
?>