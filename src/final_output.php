<?php
// final_output.php - Displays the final aggregated simulation results by Species Group

require_once 'header.php'; // Includes database connection and HTML head/nav
require_once __DIR__ . '/db_connection.php'; // Ensure db connection is available

$conn = null; // Initialize connection variable
try {
    $conn = getDbConnection(); // Get the database connection
    if (!$conn) {
        throw new Exception("Database connection failed.");
    }

    // --- Query to get aggregated data by Species Group ---
    // This query directly sums values from the 'trees' table based on their status
    $sql = "
        SELECT
            t.species_group,
            SUM(CASE WHEN t.status IN ('cut', 'victim') THEN t.prod ELSE 0 END) AS total_production_current,
            SUM(COALESCE(NULLIF(REPLACE(t.prod30, '-', ''), ''), 0)) AS total_projection_30yr,
            SUM(CASE WHEN t.status = 'victim' THEN t.volume ELSE 0 END) AS total_damage_volume,
            SUM(COALESCE(NULLIF(REPLACE(t.volume30, '-', ''), ''), 0)) AS total_remainder_volume_30yr
        FROM
            trees t
        GROUP BY
            t.species_group
        ORDER BY
            t.species_group;
    ";

    $result = $conn->query($sql);

    if ($result === false) {
        throw new Exception("Error executing query: " . $conn->error);
    }

?>

<div class="container">
    <h2>Final Table Output</h2>

    <?php if ($result->num_rows > 0): ?>
    <div class="table-responsive">
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>Species Group</th>
                    <th>Total Year-0 Production (m³)</th>
                    <th>Total Year-30 Production (m³)</th>
                    <th>Total Damage Volume (m³)</th>
                    <th>Total Year-30 Remainder Volume (m³)</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['species_group']); ?></td>
                    <td><?php echo number_format($row['total_production_current'], 2); ?></td>
                    <td><?php echo number_format($row['total_projection_30yr'], 2); ?></td>
                    <td><?php echo number_format($row['total_damage_volume'], 2); ?></td>
                    <td><?php echo number_format($row['total_remainder_volume_30yr'], 2); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
    <?php else: ?>
        <p>No aggregated data found. Please run the simulation and regime analysis first.</p>
    <?php endif; ?>

</div>

<?php
} catch (Exception $e) {
    error_log("Error in final_output.php: " . $e->getMessage());
    echo "<div class='container alert alert-danger'>Error: " . htmlspecialchars($e->getMessage()) . "</div>";
} finally {
    if ($conn) {
        $conn->close();
    }
}

require_once 'footer.php';
?>