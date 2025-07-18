<?php
// populate_stand_tables.php - Aggregates data from simulation_results_XX into regime_stand_table_data

set_time_limit(0); // Unlimited time for execution
ini_set('memory_limit', '1024M'); // Increase memory limit to 1GB

// === Database Connection ===
require_once __DIR__ . '/db_connection.php';
$conn = getDbConnection();

// Output buffering for live feedback
ob_implicit_flush(true);
ob_end_clean(); // Clean any previous output buffers
echo "<!DOCTYPE html><html><head><title>Populating Stand Tables...</title></head><body>";
echo "<h2>Populating Stand Tables Data...</h2>";
echo str_repeat(' ', 4096); // Send enough data to ensure browser flushes

// --- Define regimes and their corresponding simulation result tables ---
$regimes = [
    45 => 'simulation_results_45',
    50 => 'simulation_results_50',
    55 => 'simulation_results_55',
    60 => 'simulation_results_60',
    65 => 'simulation_results_65'
];

// --- Fetch Diameter Classes ---
$diameterClasses = [];
$sqlDiameterClasses = "SELECT id, class_name, min_diameter, max_diameter FROM diameter_classes ORDER BY id ASC";
$resultDiameterClasses = $conn->query($sqlDiameterClasses);
if ($resultDiameterClasses) {
    while ($row = $resultDiameterClasses->fetch_assoc()) {
        $diameterClasses[$row['id']] = $row; // Store by ID for easy lookup
    }
    $resultDiameterClasses->free();
} else {
    echo "<p class='error'>Error fetching diameter classes: " . $conn->error . "</p>";
    $conn->close();
    exit;
}

// --- Species Group Mapping ---
$speciesGroupMapping = [];
$sqlSpeciesGroupNames = "SELECT species_group, speciesgroup_name FROM species_group_names ORDER BY species_group ASC";
$resultSpeciesGroupNames = $conn->query($sqlSpeciesGroupNames);
if ($resultSpeciesGroupNames) {
    while ($row = $resultSpeciesGroupNames->fetch_assoc()) {
        $speciesGroupMapping[$row['species_group']] = $row['speciesgroup_name'];
    }
    $resultSpeciesGroupNames->free();
} else {
    echo "<p class='warning'>Warning: Could not fetch species group names. Using default names.</p>";
    $speciesGroupMapping = [
        1 => 'Mersawa', 2 => 'Keruing', 3 => 'Dip Commercial',
        4 => 'Dip Non Commercial', 5 => 'NonDip Commercial',
        6 => 'NonDip Non Commercial', 7 => 'Others'
    ];
}


// --- Clear existing data for all regimes in regime_stand_table_data ---
if ($conn->query("TRUNCATE TABLE regime_stand_table_data") === TRUE) {
    echo "<p>Existing data in `regime_stand_table_data` cleared.</p>";
} else {
    echo "<p class='error'>Error truncating `regime_stand_table_data`: " . $conn->error . "</p>";
    $conn->close();
    exit;
}

$insertStmt = $conn->prepare("INSERT INTO regime_stand_table_data (regime_threshold, table_type, species_group_id, diameter_class_id, num_trees, volume_m3) VALUES (?, ?, ?, ?, ?, ?)");
if (!$insertStmt) {
    echo "<p class='error'>Failed to prepare insert statement: " . $conn->error . "</p>";
    $conn->close();
    exit;
}


foreach ($regimes as $threshold => $simResultsTable) {
    echo "<h3>Processing Regime: {$threshold}cm DBH Threshold</h3>";

    // Check if the simulation results table exists
    $tableExistsResult = $conn->query("SHOW TABLES LIKE '{$simResultsTable}'");
    if (!$tableExistsResult || $tableExistsResult->num_rows == 0) {
        echo "<p class='warning'>Warning: Simulation results table `{$simResultsTable}` not found. Skipping this regime.</p>";
        continue;
    }
    $tableExistsResult->free();


    // --- Calculate Production Stand Table (Cut + Victim) ---
    $sqlProd = "
        SELECT
            t.species_group AS species_group_id,
            dc.id AS diameter_class_id,
            COUNT(t.tree_id) AS num_trees,
            SUM(t.prod) AS total_volume
        FROM
            `{$simResultsTable}` t
        JOIN
            diameter_classes dc ON t.diameter BETWEEN dc.min_diameter AND dc.max_diameter
        WHERE
            t.status IN ('cut', 'victim')
        GROUP BY
            t.species_group, dc.id
        ORDER BY
            t.species_group, dc.id;
    ";
    processAndInsertData($conn, $insertStmt, $sqlProd, $threshold, 'production');


    // --- Calculate Damage Stand Table (Victim Volume from Damage Stem) ---
    $sqlDamage = "
        SELECT
            t.species_group AS species_group_id,
            dc.id AS diameter_class_id,
            COUNT(t.tree_id) AS num_trees,
            SUM(t.damage_stem) AS total_volume
        FROM
            `{$simResultsTable}` t
        JOIN
            diameter_classes dc ON t.diameter BETWEEN dc.min_diameter AND dc.max_diameter
        WHERE
            t.status = 'victim' AND t.damage_stem IS NOT NULL AND t.damage_stem > 0
        GROUP BY
            t.species_group, dc.id
        ORDER BY
            t.species_group, dc.id;
    ";
    processAndInsertData($conn, $insertStmt, $sqlDamage, $threshold, 'damage');


    // --- Calculate Remainder Stand Table (Keep + Stand Volume) ---
    $sqlRemain = "
        SELECT
            t.species_group AS species_group_id,
            dc.id AS diameter_class_id,
            COUNT(t.tree_id) AS num_trees,
            SUM(t.volume) AS total_volume
        FROM
            `{$simResultsTable}` t
        JOIN
            diameter_classes dc ON t.diameter BETWEEN dc.min_diameter AND dc.max_diameter
        WHERE
            t.status = 'keep' OR t.status = 'stand' OR t.status IS NULL
        GROUP BY
            t.species_group, dc.id
        ORDER BY
            t.species_group, dc.id;
    ";
    processAndInsertData($conn, $insertStmt, $sqlRemain, $threshold, 'remainder');

    // --- Calculate 30-Year General Projection Stand Table (Volume30) ---
    $sqlGeneral30 = "
        SELECT
            t.species_group AS species_group_id,
            dc.id AS diameter_class_id,
            COUNT(t.tree_id) AS num_trees,
            SUM(t.volume30) AS total_volume
        FROM
            `{$simResultsTable}` t
        JOIN
            diameter_classes dc ON t.diameter BETWEEN dc.min_diameter AND dc.max_diameter
        WHERE
            t.volume30 IS NOT NULL AND t.volume30 > 0
        GROUP BY
            t.species_group, dc.id
        ORDER BY
            t.species_group, dc.id;
    ";
    processAndInsertData($conn, $insertStmt, $sqlGeneral30, $threshold, 'general_30yr');


    // --- Calculate 30-Year Production Projection Stand Table (Prod30) ---
    $sqlProd30 = "
        SELECT
            t.species_group AS species_group_id,
            dc.id AS diameter_class_id,
            COUNT(t.tree_id) AS num_trees,
            SUM(t.prod30) AS total_volume
        FROM
            `{$simResultsTable}` t
        JOIN
            diameter_classes dc ON t.diameter BETWEEN dc.min_diameter AND dc.max_diameter
        WHERE
            t.prod30 IS NOT NULL AND t.prod30 > 0
        GROUP BY
            t.species_group, dc.id
        ORDER BY
            t.species_group, dc.id;
    ";
    processAndInsertData($conn, $insertStmt, $sqlProd30, $threshold, 'prod_30yr');

    echo "<p>--- Regime {$threshold}cm DBH Processed ---</p>";
}

$insertStmt->close();
$conn->close();

echo "<h2>✅ All Stand Tables Populated Successfully!</h2>";
echo "</body></html>";


function processAndInsertData($conn, $insertStmt, $sql, $regimeThreshold, $tableType) {
    echo "<p>Processing {$tableType} data for {$regimeThreshold}...</p>";
    $result = $conn->query($sql);
    if ($result) {
        if ($result->num_rows > 0) {
            // Store num_rows BEFORE freeing the result
            $rowCount = $result->num_rows; // <-- ADD THIS LINE

            while ($row = $result->fetch_assoc()) {
                $speciesGroupId = $row['species_group_id'];
                $diameterClassId = $row['diameter_class_id'];
                $numTrees = $row['num_trees'];
                $volumeM3 = $row['total_volume'];

                // In populate_stand_tables.php, around line 214:
                $insertStmt->bind_param("isiidd", $regimeThreshold, $tableType, $speciesGroupId, $diameterClassId, $numTrees, $volumeM3);
                if (!$insertStmt->execute()) {
                    error_log("Insert error for {$tableType} data (Regime {$regimeThreshold}): " . $insertStmt->error);
                    echo "<p class='error'>Insert error for {$tableType} data (Regime {$regimeThreshold}): " . $insertStmt->error . "</p>";
                }
            }
            $result->free(); // Free the result set
            echo "<p>Inserted {$rowCount} rows for {$tableType}.</p>"; // <-- USE $rowCount HERE
        } else {
            echo "<p>No data found for {$tableType} for regime {$regimeThreshold}.</p>";
        }
    } else {
        error_log("Error fetching {$tableType} data for regime {$regimeThreshold}: " . $conn->error);
        echo "<p class='error'>Error fetching {$tableType} data for regime {$regimeThreshold}: " . $conn->error . "</p>";
    }
}
?>