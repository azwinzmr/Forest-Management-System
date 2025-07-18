<?php
set_time_limit(0); // Unlimited time for execution
ini_set('memory_limit', '1024M'); // Increase memory limit to 1GB (adjust as needed)

// === Database Connection ===
require_once __DIR__ . '/db_connection.php'; // Use the central connection function
$conn = getDbConnection();

// Optional: Show loading message (this will only display at the very beginning)
echo "<!DOCTYPE html><html><head><title>Processing Regimes...</title></head><body>";
echo "<h2 style='color:#2d6a4f;'>Processing all regimes for all trees, please wait...</h2>";
// Flush output buffer to send content to browser immediately
ob_implicit_flush(true);
ob_end_clean(); // Clean any previous output buffers
echo str_repeat(' ', 4096); // Send enough data to ensure browser flushes

// === Define regimes and their corresponding NEW table names (simulation_results_XX) ===
$regimes = [
    45 => 'simulation_results_45',
    50 => 'simulation_results_50',
    55 => 'simulation_results_55',
    60 => 'simulation_results_60',
    65 => 'simulation_results_65'
];

// === Prepare SQL for fetching trees ===
$sql_trees = "SELECT tree_id, species_group, diameter, volume, prod, volume30, prod30 FROM trees ORDER BY tree_id ASC";
$result_trees = $conn->query($sql_trees);

if (!$result_trees) {
    die("Error fetching trees from 'trees' table: " . $conn->error);
}

// Store all trees in memory for efficient processing (be cautious with very large datasets)
$trees_data = [];
while ($row = $result_trees->fetch_assoc()) {
    $trees_data[] = $row;
}
$result_trees->free();
echo "<p>Fetched " . count($trees_data) . " trees from 'trees' table.</p>";

// === Loop through each regime and apply rules ===
foreach ($regimes as $threshold => $table) {
    echo "<h3>Processing threshold: {$threshold}cm DBH for table `{$table}`</h3>";

    // Drop table if it exists to ensure clean data for each run
    $conn->query("DROP TABLE IF EXISTS `{$table}`");

    // Create the new simulation results table with all necessary columns
    $sql_create_table = "
        CREATE TABLE `{$table}` (
            tree_id VARCHAR(50) PRIMARY KEY,
            species_group INT,
            diameter DECIMAL(10,4),
            volume DECIMAL(10,4),
            prod DECIMAL(10,4),
            volume30 DECIMAL(10,4),
            prod30 DECIMAL(10,4),
            cut_angle DECIMAL(10,4) NULL,
            fall_angle DECIMAL(10,4) NULL,
            fall_quarter VARCHAR(10) NULL,
            status VARCHAR(10) NULL,
            damage_stem DECIMAL(10,4) NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";
    if ($conn->query($sql_create_table) === TRUE) {
        echo "<p>Table `{$table}` created successfully.</p>";
    } else {
        error_log("Error creating table `{$table}`: " . $conn->error);
        echo "<p class='error'>Error creating table `{$table}`: " . $conn->error . "</p>";
        continue; // Skip to next regime if table creation fails
    }

    // Prepare INSERT statement for the new regime table
    $stmt = $conn->prepare(
        "INSERT INTO `{$table}` (
            tree_id, species_group, diameter, volume, prod, volume30, prod30,
            cut_angle, fall_angle, fall_quarter, status, damage_stem
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        error_log("Prepare failed for table `{$table}`: " . $conn->error);
        echo "<p class='error'>Prepare failed for table `{$table}`: " . $conn->error . "</p>";
        continue;
    }

    foreach ($trees_data as $tree) {
        $tree_id = $tree['tree_id'];
        $group = $tree['species_group'];
        $diameter = $tree['diameter'];
        $volume = $tree['volume'];
        $prod = $tree['prod'];
        $volume30 = $tree['volume30'];
        $prod30 = $tree['prod30'];

        // Apply cutting rules
        $can_cut = in_array($group, [1, 2, 3, 5]) && $diameter >= $threshold;
        $status = $can_cut ? 'cut' : 'keep'; // Changed to lowercase 'cut'/'keep'

        $cut_angle = null;
        $fall_angle = null;
        $fall_quarter = null;
        $damage_stem = null;

        // Generate cut/fall angles and fall direction
        if ($status === 'cut') {
            $cut_angle = rand(0, 359);
            $fall_angle = ($cut_angle + rand(-30, 30) + 360) % 360;
            $fall_quarter = get_fall_quarter($fall_angle);

            // Simulate 'victim' status for some trees based on damage rules (simplified example)
            if (rand(0, 100) < 10) { // Example: 10% chance to be a victim
                 $status = 'victim';
                 $damage_stem = $volume * 0.2; // Example: 20% of volume is damage
            }
        } else {
            $cut_angle = null;
            $fall_angle = null;
            $fall_quarter = null;
            $damage_stem = null;
        }

        // Bind parameters and execute
        // CORRECTED: 12 variables, 12 types (s d d d d d d d s s d)
        // In year0_regime.php, around line 126:
$stmt->bind_param(
    "siddddddsssd", // CORRECTED TYPE STRING: 12 characters for 12 variables
    $tree_id, $group, $diameter, $volume, $prod, $volume30, $prod30,
    $cut_angle, $fall_angle, $fall_quarter, $status, $damage_stem
);
        if (!$stmt->execute()) {
            error_log("Insert error in $table for tree $tree_id: " . $stmt->error);
            echo "<p class='error'>Insert error in $table for tree $tree_id: " . $stmt->error . "</p>";
        }
    }
    $stmt->close(); // Close statement after each table's inserts
    echo "<p>Processed " . count($trees_data) . " trees for regime {$threshold}cm DBH.</p>";
}

// Function to determine fall quarter
function get_fall_quarter($angle) {
    if ($angle >= 0 && $angle < 90) return 'Q1';
    else if ($angle >= 90 && $angle < 180) return 'Q2';
    else if ($angle >= 180 && $angle < 270) return 'Q3';
    else return 'Q4';
}

$conn->close();

echo "<h2>✅ All Regimes Processed Successfully!</h2>";
echo "</body></html>";
?>