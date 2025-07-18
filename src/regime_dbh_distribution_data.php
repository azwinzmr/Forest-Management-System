<?php
// regime_dbh_distribution_data.php
header('Content-Type: application/json');
require_once 'db_connection.php'; // Your database connection file

$conn = getDbConnection();

if (!$conn) {
    echo json_encode(['error' => 'Database connection failed.']);
    exit;
}

$response = [
    'diameter_distribution_chart_data_by_regime' => []
];

$regimes = [45, 50, 55, 60, 65];

// Define a consistent set of DBH classes
$dbhClasses = [
    '0-10', '10-20', '20-30', '30-40', '40-50', '50-60', '60-70', '70-80', '80-90', '90-100', '100+'
];

try {
    foreach ($regimes as $threshold) {
        $dbhCountsY0 = array_fill_keys($dbhClasses, 0);
        $dbhCountsY30 = array_fill_keys($dbhClasses, 0);

        // Fetch Year 0 DBH Distribution
        $sqlDbhY0 = "
            SELECT
                dbh_class_cm,
                SUM(number_of_stems_ha) AS total_stems
            FROM
                regime_stand_table_data
            WHERE
                regime_threshold = ? AND year = 0 AND dbh_class_cm IS NOT NULL
            GROUP BY
                dbh_class_cm
            ORDER BY
                dbh_class_cm;
        ";
        $stmt_dbh0 = $conn->prepare($sqlDbhY0);
        if ($stmt_dbh0) {
            $stmt_dbh0->bind_param("i", $threshold);
            $stmt_dbh0->execute();
            $result_dbh0 = $stmt_dbh0->get_result();
            while ($row = $result_dbh0->fetch_assoc()) {
                if (in_array($row['dbh_class_cm'], $dbhClasses)) {
                    $dbhCountsY0[$row['dbh_class_cm']] = (float)$row['total_stems'];
                }
            }
            $stmt_dbh0->close();
        } else {
            throw new Exception("Error preparing statement for DBH distribution year 0: " . $conn->error);
        }

        // Fetch Year 30 DBH Distribution
        $sqlDbhY30 = "
            SELECT
                dbh_class_cm,
                SUM(number_of_stems_ha) AS total_stems
            FROM
                regime_stand_table_data
            WHERE
                regime_threshold = ? AND year = 30 AND dbh_class_cm IS NOT NULL
            GROUP BY
                dbh_class_cm
            ORDER BY
                dbh_class_cm;
        ";
        $stmt_dbh30 = $conn->prepare($sqlDbhY30);
        if ($stmt_dbh30) {
            $stmt_dbh30->bind_param("i", $threshold);
            $stmt_dbh30->execute();
            $result_dbh30 = $stmt_dbh30->get_result();
            while ($row = $result_dbh30->fetch_assoc()) {
                if (in_array($row['dbh_class_cm'], $dbhClasses)) {
                    $dbhCountsY30[$row['dbh_class_cm']] = (float)$row['total_stems'];
                }
            }
            $stmt_dbh30->close();
        } else {
            throw new Exception("Error preparing statement for DBH distribution year 30: " . $conn->error);
        }

        $dataDbhY0 = [];
        $dataDbhY30 = [];
        foreach ($dbhClasses as $class) {
            $dataDbhY0[] = $dbhCountsY0[$class];
            $dataDbhY30[] = $dbhCountsY30[$class];
        }

        $response['diameter_distribution_chart_data_by_regime'][$threshold] = [
            'labels' => $dbhClasses,
            'datasets' => [
                [
                    'label' => 'Year 0 Stems (N/Ha)',
                    'data' => $dataDbhY0,
                    'backgroundColor' => 'rgba(255, 205, 86, 0.7)', // Yellow/Gold
                    'borderColor' => 'rgba(255, 205, 86, 1)',
                    'borderWidth' => 1
                ],
                [
                    'label' => 'Year 30 Stems (N/Ha)',
                    'data' => $dataDbhY30,
                    'backgroundColor' => 'rgba(75, 192, 192, 0.7)', // Teal
                    'borderColor' => 'rgba(75, 192, 192, 1)',
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