<?php
// regime_chart_data.php
header('Content-Type: application/json');
require_once 'db_connection.php'; // Your database connection file. Make sure this path is correct.

$conn = getDbConnection();

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

$response = [
    'production_chart_data_by_regime' => [], // Now a faceted structure
    'remainder_chart_data_by_regime' => []
];

// Define your regimes here. These should match the regime_thresholds in your database.
$regimes = [45, 50, 55, 60, 65];

try {
    // Fetch all species groups and their names once for consistent ordering and labels
    $speciesGroupMap = []; // species_group_id => speciesgroup_name
    $speciesGroupNamesOrdered = []; // ordered list of names for chart labels
    $sqlSpeciesGroups = "SELECT species_group, speciesgroup_name FROM species_group_names ORDER BY speciesgroup_name ASC";
    $resultSpeciesGroups = $conn->query($sqlSpeciesGroups);
    if ($resultSpeciesGroups) {
        while ($row = $resultSpeciesGroups->fetch_assoc()) {
            $speciesGroupMap[$row['species_group']] = $row['speciesgroup_name'];
            $speciesGroupNamesOrdered[] = $row['speciesgroup_name'];
        }
        $resultSpeciesGroups->free();
    } else {
        throw new Exception("Error fetching species groups: " . $conn->error);
    }


    // --- Data for Production Charts (Year 0 vs Year 30 by Species Group, faceted by regime) ---
    foreach ($regimes as $threshold) {
        // Initialize volumes for all species groups to 0 to ensure all labels are present
        $prodY0Volumes = array_fill_keys(array_keys($speciesGroupMap), 0);
        $prodY30Volumes = array_fill_keys(array_keys($speciesGroupMap), 0);

        // Fetch Production for Year 0 by Species Group for the current regime
        $sqlProdYear0 = "
            SELECT
                species_group_id,
                SUM(volume_m3) AS total_volume
            FROM
                regime_stand_table_data
            WHERE
                regime_threshold = ? AND table_type = 'production'
            GROUP BY
                species_group_id;
        ";
        $stmt_prod0 = $conn->prepare($sqlProdYear0);
        if ($stmt_prod0) {
            $stmt_prod0->bind_param("i", $threshold);
            $stmt_prod0->execute();
            $result_prod0 = $stmt_prod0->get_result();
            while ($row = $result_prod0->fetch_assoc()) {
                $prodY0Volumes[$row['species_group_id']] = (float)$row['total_volume'];
            }
            $stmt_prod0->close();
        } else {
            throw new Exception("Error preparing statement for production year 0: " . $conn->error);
        }

        // Fetch Production for Year 30 by Species Group for the current regime
        $sqlProdYear30 = "
            SELECT
                species_group_id,
                SUM(volume_m3) AS total_volume
            FROM
                regime_stand_table_data
            WHERE
                regime_threshold = ? AND table_type = 'prod_30yr'
            GROUP BY
                species_group_id;
        ";
        $stmt_prod30 = $conn->prepare($sqlProdYear30);
        if ($stmt_prod30) {
            $stmt_prod30->bind_param("i", $threshold);
            $stmt_prod30->execute();
            $result_prod30 = $stmt_prod30->get_result();
            while ($row = $result_prod30->fetch_assoc()) {
                $prodY30Volumes[$row['species_group_id']] = (float)$row['total_volume'];
            }
            $stmt_prod30->close();
        } else {
            throw new Exception("Error preparing statement for production year 30: " . $conn->error);
        }

        // Arrange data for Chart.js in the ordered sequence of species names
        $prodDataY0 = [];
        $prodDataY30 = [];
        foreach ($speciesGroupNamesOrdered as $name) {
            // Find the ID for the current species name
            $id = array_search($name, $speciesGroupMap);
            // Add the volume, defaulting to 0 if not found (e.g., no data for this group)
            $prodDataY0[] = $prodY0Volumes[$id] ?? 0;
            $prodDataY30[] = $prodY30Volumes[$id] ?? 0;
        }

        $response['production_chart_data_by_regime'][$threshold] = [
            'labels' => $speciesGroupNamesOrdered, // Labels are now species group names
            'datasets' => [
                [
                    'label' => 'Year 0 Production (m³)',
                    'data' => $prodDataY0,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.7)', // Teal
                    'borderColor' => 'rgba(75, 192, 192, 1)',
                    'borderWidth' => 1
                ],
                [
                    'label' => 'Year 30 Projection (m³)',
                    'data' => $prodDataY30,
                    'backgroundColor' => 'rgba(153, 102, 255, 0.7)', // Purple
                    'borderColor' => 'rgba(153, 102, 255, 1)',
                    'borderWidth' => 1
                ]
            ]
        ];
    }

    // --- Data for Remainder Volume Charts (Year 0 vs Year 30 by Species Group, faceted by regime) ---
    // This section remains largely the same as it was already faceted and by species group.
    foreach ($regimes as $threshold) {
        // Reuse $speciesGroupMap and $speciesGroupNamesOrdered from above for consistency
        $remainderY0Volumes = array_fill_keys(array_keys($speciesGroupMap), 0);
        $remainderY30Volumes = array_fill_keys(array_keys($speciesGroupMap), 0);

        // Fetch Remainder for Year 0 (table_type = 'remainder')
        $sqlRemainderYear0 = "
            SELECT
                species_group_id,
                SUM(volume_m3) AS total_volume
            FROM
                regime_stand_table_data
            WHERE
                regime_threshold = ? AND table_type = 'remainder'
            GROUP BY
                species_group_id;
        ";
        $stmt0 = $conn->prepare($sqlRemainderYear0);
        if ($stmt0) {
            $stmt0->bind_param("i", $threshold);
            $stmt0->execute();
            $result0 = $stmt0->get_result();
            while ($row = $result0->fetch_assoc()) {
                $remainderY0Volumes[$row['species_group_id']] = (float)$row['total_volume'];
            }
            $stmt0->close();
        } else {
             throw new Exception("Error preparing statement for remainder year 0: " . $conn->error);
        }

        // Fetch Remainder for Year 30 (table_type = 'general_30yr')
        $sqlRemainderYear30 = "
            SELECT
                species_group_id,
                SUM(volume_m3) AS total_volume
            FROM
                regime_stand_table_data
            WHERE
                regime_threshold = ? AND table_type = 'general_30yr'
            GROUP BY
                species_group_id;
        ";
        $stmt30 = $conn->prepare($sqlRemainderYear30);
        if ($stmt30) {
            $stmt30->bind_param("i", $threshold);
            $stmt30->execute();
            $result30 = $stmt30->get_result();
            while ($row = $result30->fetch_assoc()) {
                $remainderY30Volumes[$row['species_group_id']] = (float)$row['total_volume'];
            }
            $stmt30->close();
        } else {
             throw new Exception("Error preparing statement for remainder year 30: " . $conn->error);
        }

        // Arrange data for Chart.js in the ordered sequence of species names
        $dataY0 = [];
        $dataY30 = [];
        foreach ($speciesGroupNamesOrdered as $name) {
            $id = array_search($name, $speciesGroupMap); // Find ID from name
            $dataY0[] = $remainderY0Volumes[$id] ?? 0;
            $dataY30[] = $remainderY30Volumes[$id] ?? 0;
        }

        $response['remainder_chart_data_by_regime'][$threshold] = [
            'labels' => $speciesGroupNamesOrdered,
            'datasets' => [
                [
                    'label' => 'Year 0 Remainder (m³)',
                    'data' => $dataY0,
                    'backgroundColor' => 'rgba(255, 99, 132, 0.7)', // Red
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 1
                ],
                [
                    'label' => 'Year 30 Remainder (m³)',
                    'data' => $dataY30,
                    'backgroundColor' => 'rgba(54, 162, 235, 0.7)', // Blue
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1
                ]
            ]
        ];
    }

} catch (Exception $e) {
    $response = ['error' => 'Error fetching data: ' . $e->getMessage()];
} finally {
    if ($conn) {
        $conn->close();
    }
}

echo json_encode($response);
?>