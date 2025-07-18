<?php
require_once 'header.php'; // Includes common header, HTML head/nav
require_once __DIR__ . '/db_connection.php'; // Ensure db connection is available

// Establish connection using the centralized function
$conn = getDbConnection();

$cuttingRegimes = [45, 50, 55, 60]; // Define available cutting regimes
$selectedRegime = ''; // Variable to hold the currently selected cutting regime

// Handle display selection from the dropdown
if (isset($_POST['select_regime']) && in_array($_POST['select_regime'], $cuttingRegimes)) {
    $selectedRegime = $_POST['select_regime'];
} else if (isset($_GET['regime']) && in_array($_GET['regime'], $cuttingRegimes)) {
    // Fallback for direct links from dashboard, if any exist that still uses GET
    $selectedRegime = $_GET['regime'];
}

// --- Fetch Diameter Classes ---
$diameterClasses = [];
$sqlDiameterClasses = "SELECT id, class_name, min_diameter, max_diameter FROM diameter_classes ORDER BY id ASC";
$resultDiameterClasses = $conn->query($sqlDiameterClasses);

if ($resultDiameterClasses) {
    while ($row = $resultDiameterClasses->fetch_assoc()) {
        $diameterClasses[] = $row;
    }
    $resultDiameterClasses->free();
} else {
    error_log("Error fetching diameter classes: " . $conn->error . ". Please ensure 'diameter_classes' table exists and is populated.");
    $diameterClasses = []; // Ensure it's empty to prevent errors in loops
}

// NEW: Use all fetched diameter classes for display by default
$filteredDiameterClasses = $diameterClasses;


// --- Fetch Species Groups ---
$speciesGroupsForTable = [];
$speciesGroupMapping = []; // This maps species_group_id => species_group_name

// *** REVERTED QUERY: Using 'species_group' and 'speciesgroup_name' as per your table structure ***
$sqlSpeciesGroupNames = "SELECT species_group, speciesgroup_name FROM species_group_names ORDER BY species_group ASC"; // Line 46
$resultSpeciesGroupNames = $conn->query($sqlSpeciesGroupNames);

if ($resultSpeciesGroupNames) {
    while ($row = $resultSpeciesGroupNames->fetch_assoc()) {
        // Use 'species_group' and 'speciesgroup_name' as column names from the query
        $groupId = $row['species_group'] ?? null;
        $groupName = $row['speciesgroup_name'] ?? null;

        if ($groupId !== null && $groupName !== null) {
            $speciesGroupMapping[$groupId] = $groupName;
        } else {
            error_log("Warning: Invalid row fetched from species_group_names table (missing species_group or speciesgroup_name columns). Row: " . json_encode($row));
        }
    }
    $resultSpeciesGroupNames->free();
} else {
    // Fallback if table is empty or doesn't exist or query failed
    $speciesGroupMapping = [
        1 => 'Mersawa', 2 => 'Keruing', 3 => 'Dip Commercial',
        4 => 'Dip Non Commercial', 5 => 'NonDip Commercial',
        6 => 'NonDip Non Commercial', 7 => 'Others'
    ];
    error_log("Warning: Could not fetch species group names from 'species_group_names' table. Using default names. Please check table schema (expected 'species_group', 'speciesgroup_name' columns). " . $conn->error);
}

// Populate $allKnownSpeciesGroups based on the $speciesGroupMapping
// This loop ensures each entry has 'id' and 'name' keys
$allKnownSpeciesGroups = []; // Correct variable name
foreach ($speciesGroupMapping as $id => $name) {
    $allKnownSpeciesGroups[] = ['id' => (int)$id, 'name' => $name]; // Ensure ID is integer
}


// --- Function to fetch pre-calculated data from 'regime_stand_table_data' ---
// Modified to apply species group filter for 'prod_30yr' type
function fetchPrecalculatedData($db_connection, $regimeThreshold, $tableType, $allowedProd30SpeciesGroups = []) {
    $data = [];
    $sql = "
        SELECT
            species_group_id,
            diameter_class_id,
            num_trees,
            volume_m3
        FROM
            regime_stand_table_data
        WHERE
            regime_threshold = ? AND table_type = ?
        ORDER BY
            species_group_id, diameter_class_id;
    ";
    $stmt = $db_connection->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("is", $regimeThreshold, $tableType);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $sg_id = (int)$row['species_group_id']; // Cast to int for comparison
            $dc_id = (int)$row['diameter_class_id'];
            $num_trees = (float)$row['num_trees'];
            $volume_m3 = (float)$row['volume_m3'];

            // Apply species group filter ONLY for 'prod_30yr' data type
            if ($tableType === 'prod_30yr' && !empty($allowedProd30SpeciesGroups) && !in_array($sg_id, $allowedProd30SpeciesGroups)) {
                // If species group is not in allowed list, zero out its contribution to prod_30yr
                $num_trees = 0;
                $volume_m3 = 0.0;
            }

            if (!isset($data[$sg_id])) {
                $data[$sg_id] = [];
            }
            // Sum volumes if multiple entries for same sg_id, dc_id from the query result
            $data[$sg_id][$dc_id]['no'] = ($data[$sg_id][$dc_id]['no'] ?? 0) + $num_trees;
            $data[$sg_id][$dc_id]['vol'] = ($data[$sg_id][$dc_id]['vol'] ?? 0.0) + $volume_m3;
        }
        $stmt->close();
    } else {
        error_log("Error preparing statement for fetching {$tableType} data: " . $db_connection->error);
    }
    return $data;
}

// --- Function to generate a single stand table HTML ---
function generateStandTable($title, $data, $speciesGroupsForTable, $diameterClasses)
{
    echo "<h2>" . htmlspecialchars($title) . "</h2>";
    // Check if data is truly empty after considering species groups and diameter classes
    $hasDataToDisplay = false;
    foreach($speciesGroupsForTable as $sg) {
        // Ensure $sg['id'] and $sg['name'] exist before proceeding
        if (!isset($sg['id']) || !isset($sg['name'])) {
            error_log("Skipping malformed species group entry in generateStandTable: " . json_encode($sg));
            continue; // Skip this entry if it doesn't have 'id' or 'name'
        }
        foreach($diameterClasses as $dc) {
            if (isset($data[$sg['id']][$dc['id']]) && (($data[$sg['id']][$dc['id']]['no'] ?? 0) > 0 || ($data[$sg['id']][$dc['id']]['vol'] ?? 0) > 0)) {
                $hasDataToDisplay = true;
                break 2; // Break both loops
            }
        }
    }

    if (!$hasDataToDisplay || empty($speciesGroupsForTable) || empty($diameterClasses)) {
        echo "<p class='message-info'>No data or configuration available for " . htmlspecialchars($title) . ". This could mean no trees matched the criteria for this table, or a database setup issue, or diameters are out of defined ranges. Make sure `populate_stand_tables.php` has been run for this regime and that your species group names are correctly set up (expected 'species_group' and 'speciesgroup_name' columns in `species_group_names` table).</p>";
        return;
    }

    echo "<div class='table-responsive stand-table-container'>";
    echo "<table class='stand-table'>";
    echo "<thead><tr>";
    echo "<th>Species Group</th>";
    foreach ($diameterClasses as $dc) {
        echo "<th colspan='2'>" . htmlspecialchars($dc['class_name']) . "</th>";
    }
    echo "<th colspan='2'>Total</th>";
    echo "</tr>";
    echo "<tr><th></th>";
    foreach ($diameterClasses as $dc) {
        echo "<th>No</th><th>Vol (m³)</th>";
    }
    echo "<th>No</th><th>Vol (m³)</th>";
    echo "</tr></thead>";
    echo "<tbody>";

    $overallTotalNo = 0;
    $overallTotalVol = 0.0;
    $columnTotals = [];

    foreach ($diameterClasses as $dc) {
        $columnTotals[$dc['id']]['no'] = 0;
        $columnTotals[$dc['id']]['vol'] = 0.0;
    }

    foreach ($speciesGroupsForTable as $sg) {
        // Double-check existence again to be safe (should be fine now if $allKnownSpeciesGroups is built correctly)
        if (!isset($sg['id']) || !isset($sg['name'])) {
            continue; // Skip malformed entries
        }
        echo "<tr>";
        echo "<td>" . htmlspecialchars($sg['name']) . "</td>";
        $rowTotalNo = 0;
        $rowTotalVol = 0.0;

        foreach ($diameterClasses as $dc) {
            $count = $data[$sg['id']][$dc['id']]['no'] ?? 0;
            $volume = $data[$sg['id']][$dc['id']]['vol'] ?? 0.0;

            echo "<td>" . number_format($count) . "</td>";
            echo "<td>" . number_format($volume, 2) . "</td>";

            $rowTotalNo += $count;
            $rowTotalVol += $volume;

            $columnTotals[$dc['id']]['no'] += $count;
            $columnTotals[$dc['id']]['vol'] += $volume;
        }
        echo "<td>" . number_format($rowTotalNo) . "</td>";
        echo "<td>" . number_format($rowTotalVol, 2) . "</td>";
        echo "</tr>";

        $overallTotalNo += $rowTotalNo;
        $overallTotalVol += $rowTotalVol;
    }

    echo "<tr class='total-row'>";
    echo "<td>Total</td>";
    foreach ($diameterClasses as $dc) {
        echo "<td>" . number_format($columnTotals[$dc['id']]['no']) . "</td>";
        echo "<td>" . number_format($columnTotals[$dc['id']]['vol'], 2) . "</td>";
    }
    echo "<td>" . number_format($overallTotalNo) . "</td>";
    echo "<td>" . number_format($overallTotalVol, 2) . "</td>";
    echo "</tr>";

    echo "</tbody>";
    echo "</table>";
    echo "</div>";
}


// Initialize data arrays for stand tables
$prodData = [];
$damageData = [];
$remainData = [];
$general30Data = [];
$prod30Data = [];

// Define the species groups for which Prod30 applies (1, 2, 3, 5)
$prod30ApplicableSpeciesGroups = [1, 2, 3, 5];


// Only attempt to fetch data if a regime is selected
if ($selectedRegime) {
    // --- Fetch Pre-calculated Data for all 5 Stand Tables ---
    $prodData = fetchPrecalculatedData($conn, $selectedRegime, 'production');
    $damageData = fetchPrecalculatedData($conn, $selectedRegime, 'damage');
    $remainData = fetchPrecalculatedData($conn, $selectedRegime, 'remainder');
    $general30Data = fetchPrecalculatedData($conn, $selectedRegime, 'general_30yr');
    // Pass the specific species groups for prod_30yr
    $prod30Data = fetchPrecalculatedData($conn, $selectedRegime, 'prod_30yr', $prod30ApplicableSpeciesGroups);

    // Filter species groups to only show those that have data in any of the tables
    $speciesGroupsWithData = [];
    $allDataMerged = []; // Used to track which species groups actually have non-zero data
    
    // Iterate through all datasets to find which species groups have data
    foreach ([$prodData, $damageData, $remainData, $general30Data, $prod30Data] as $dataset) {
        foreach ($dataset as $sgId => $dcData) {
            foreach ($dcData as $dcId => $values) {
                if (($values['no'] ?? 0) > 0 || ($values['vol'] ?? 0.0) > 0) {
                    $allDataMerged[$sgId] = true;
                    break; // No need to check other diameter classes for this species group
                }
            }
        }
    }
    
    $presentSpeciesGroupIds = array_keys($allDataMerged);

    // Corrected variable name: $allKnownSpeciesGroups
    foreach ($allKnownSpeciesGroups as $sg) {
        if (in_array($sg['id'], $presentSpeciesGroupIds)) {
            $speciesGroupsForTable[] = $sg;
        }
    }
}

$conn->close();
?>

<section class="home-content">
    <h1>Stand Tables <?php echo $selectedRegime ? "for " . htmlspecialchars($selectedRegime) . "cm DBH Regime" : ""; ?></h1>

    <div class="simulation-controls">
        <form method="POST" class="display-selection-form">
            <label for="regime_select">Select Simulation Regime:</label>
            <select name="select_regime" id="regime_select" onchange="this.form.submit()">
                <option value="">-- Select Regime --</option>
                <?php foreach ($cuttingRegimes as $regime): ?>
                    <option value="<?php echo $regime; ?>" <?php echo ($selectedRegime == $regime) ? 'selected' : ''; ?>>
                        <?php echo $regime; ?>cm DBH
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <?php if (!$selectedRegime): ?>
        <p class='message-info'>Please select a cutting regime from the dropdown above to view the Stand Tables.</p>
    <?php elseif ($selectedRegime && !empty($filteredDiameterClasses) && !empty($speciesGroupsForTable)): ?>
        <?php

        generateStandTable("Production Stand Table (Cut + Victim)", $prodData, $speciesGroupsForTable, $filteredDiameterClasses);
        generateStandTable("Damage Stand Table (Victim Volume from Damage Stem)", $damageData, $speciesGroupsForTable, $filteredDiameterClasses);
        generateStandTable("Remainder Stand Table (Keep + Stand Volume)", $remainData, $speciesGroupsForTable, $filteredDiameterClasses);
        generateStandTable("30-Year General Projection Stand Table (Volume30)", $general30Data, $speciesGroupsForTable, $filteredDiameterClasses);
        generateStandTable("30-Year Production Projection Stand Table (Prod30)", $prod30Data, $speciesGroupsForTable, $filteredDiameterClasses);
        ?>
    <?php else: // Selected regime but no data or configuration ?>
        <p class='message-info'>Cannot generate stand tables for <?php echo htmlspecialchars($selectedRegime); ?>cm DBH. This could mean a simulation has not been run, or there is no pre-calculated data for the selected regime matching the table criteria (e.g., specific tree statuses or non-NULL values). Ensure diameter classes are configured, species groups are present in the simulation data, and `populate_stand_tables.php` has been run successfully for this regime, and check your `species_group_names` table columns are `species_group` and `speciesgroup_name`.</p>
    <?php endif; ?>
</section>

<?php require_once 'footer.php'; ?>