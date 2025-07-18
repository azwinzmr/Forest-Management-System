<?php
// regime_analysis.php - Displays the Cutting Regime Analysis (Year 0) table

require_once 'header.php'; // Includes database connection and HTML head/nav

// --- Fetch data for Cutting Regime Analysis (Year 0) ---
$regimes_to_analyze = [
    45 => 'regime_45',
    50 => 'regime_50',
    55 => 'regime_55',
    60 => 'regime_60'
];

$regime_results = [];

foreach ($regimes_to_analyze as $threshold => $table_name) {
    // Check if the table exists before querying
    $table_exists_query = "SHOW TABLES LIKE '{$table_name}'";
    $table_exists_result = mysqli_query($link, $table_exists_query);

    if ($table_exists_result && mysqli_num_rows($table_exists_result) > 0) {
        // Fetch total 'Cut' trees for the current regime
        $sql_cut_count = "SELECT COUNT(*) AS cut_trees FROM {$table_name} WHERE status = 'Cut'";
        $result_cut = mysqli_query($link, $sql_cut_count);

        if ($result_cut && $row_cut = mysqli_fetch_assoc($result_cut)) {
            $regime_results[$threshold]['cut_trees'] = $row_cut['cut_trees'];
        } else {
            $regime_results[$threshold]['cut_trees'] = "N/A"; // Handle error or no data
            error_log("Error fetching cut count for {$table_name}: " . mysqli_error($link));
        }

        // Fetch total 'Keep' trees for the current regime
        $sql_keep_count = "SELECT COUNT(*) AS keep_trees FROM {$table_name} WHERE status = 'Keep'";
        $result_keep = mysqli_query($link, $sql_keep_count);
        if ($result_keep && $row_keep = mysqli_fetch_assoc($result_keep)) {
            $regime_results[$threshold]['keep_trees'] = $row_keep['keep_trees'];
        } else {
            $regime_results[$threshold]['keep_trees'] = "N/A"; // Handle error or no data
            error_log("Error fetching keep count for {$table_name}: " . mysqli_error($link));
        }
    } else {
        $regime_results[$threshold]['cut_trees'] = "Table Missing";
        $regime_results[$threshold]['keep_trees'] = "Table Missing";
        // No need to log for every regime if it's expected that some might not exist
        // error_log("Table {$table_name} does not exist for regime analysis.");
    }
}
?>

<div class="page-wrapper">
    <?php require_once 'sidebar_menu.php'; // Include the sidebar ?>
    <div class="main-content">
        <h1>Cutting Regime Analysis (Year 0)</h1>
        <p>This table summarizes the number of trees designated 'Cut' or 'Keep' under different felling diameter thresholds.</p>
        <p><em>To see this data, ensure `simulate.php` has been run first, followed by `year0_regime.php` to populate these regime-specific tables.</em></p>

        <?php if (!empty($regime_results)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Felling Diameter Threshold (cm)</th>
                        <th>Trees Marked 'Cut'</th>
                        <th>Trees Marked 'Keep'</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($regime_results as $threshold => $data): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($threshold); ?></td>
                        <td><?php echo htmlspecialchars(number_format($data['cut_trees'])); ?></td>
                        <td><?php echo htmlspecialchars(number_format($data['keep_trees'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No regime analysis data available. Please ensure `year0_regime.php` has been run after `simulate.php` to populate the regime tables, and that the tables exist.</p>
        <?php endif; ?>
    </div></div><?php require_once 'footer.php'; // Includes the closing HTML tags and footer ?>