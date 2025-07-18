<?php
// index.php - Dashboard: Overall Statistics and Tree Details

require_once 'header.php'; // Includes database connection and HTML head/nav

// --- Pagination and Filtering Setup ---
$limit = 50; // Number of records per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$filterStatus = isset($_GET['status']) ? $_GET['status'] : '';
$filterBlockI = isset($_GET['block_i']) && is_numeric($_GET['block_i']) ? (int)$_GET['block_i'] : '';
$filterBlockJ = isset($_GET['block_j']) && is_numeric($_GET['block_j']) ? (int)$_GET['block_j'] : '';

// --- Fetch Summary Statistics ---
$totalTrees = 0;
$totalVolume = 0.0;
$totalProd = 0.0;
$totalProd30 = 0.0;
$treesCut = 0;
$treesVictim = 0;
$treesKeep = 0;
$treesStand = 0;

$sqlSummary = "SELECT
                    COUNT(tree_id) AS total_trees,
                    SUM(volume) AS total_volume_current,
                    SUM(CASE WHEN status IN ('cut', 'victim') THEN prod ELSE 0 END) AS total_prod,
                    SUM(CASE WHEN prod30 IS NOT NULL AND prod30 <> '-' THEN prod30 ELSE 0 END) AS total_prod30,
                    SUM(CASE WHEN status = 'cut' THEN 1 ELSE 0 END) AS trees_cut,
                    SUM(CASE WHEN status = 'victim' THEN 1 ELSE 0 END) AS trees_victim,
                    SUM(CASE WHEN status = 'keep' THEN 1 ELSE 0 END) AS trees_keep,
                    SUM(CASE WHEN status = 'stand' THEN 1 ELSE 0 END) AS trees_stand
                FROM trees";

$resultSummary = mysqli_query($link, $sqlSummary);

if ($resultSummary && mysqli_num_rows($resultSummary) > 0) {
    $summaryData = mysqli_fetch_assoc($resultSummary);
    $totalTrees = $summaryData['total_trees'];
    $totalVolume = round($summaryData['total_volume_current'], 2);
    $totalProd = round($summaryData['total_prod'], 2);
    $totalProd30 = round($summaryData['total_prod30'], 2);
    $treesCut = $summaryData['trees_cut'];
    $treesVictim = $summaryData['trees_victim'];
    $treesKeep = $summaryData['trees_keep'];
    $treesStand = $summaryData['trees_stand'];
} else {
    $totalTrees = 0;
}


// --- Build dynamic SQL for Tree Listing with Filters and Pagination ---
$whereClauses = [];
$params = [];
$paramTypes = "";

if (!empty($filterStatus)) {
    $whereClauses[] = "status = ?";
    $params[] = $filterStatus;
    $paramTypes .= "s";
}
if (!empty($filterBlockI)) {
    $whereClauses[] = "block_i = ?";
    $params[] = $filterBlockI;
    $paramTypes .= "i";
}
if (!empty($filterBlockJ)) {
    $whereClauses[] = "block_j = ?";
    $params[] = $filterBlockJ;
    $paramTypes .= "i";
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = " WHERE " . implode(" AND ", $whereClauses);
}

// Get total number of records for pagination
$sqlCount = "SELECT COUNT(tree_id) AS total_filtered_trees FROM trees" . $whereSql;
$stmtCount = mysqli_prepare($link, $sqlCount);

if ($stmtCount) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmtCount, $paramTypes, ...$params);
    }
    mysqli_stmt_execute($stmtCount);
    $resultCount = mysqli_stmt_get_result($stmtCount);
    $totalFilteredTrees = mysqli_fetch_assoc($resultCount)['total_filtered_trees'];
    mysqli_stmt_close($stmtCount);
} else {
    $totalFilteredTrees = 0;
}

$totalPages = ceil($totalFilteredTrees / $limit);


// Fetch trees for current page with filters and pagination
$sampleTrees = [];
$sqlTrees = "SELECT * FROM trees" . $whereSql . " ORDER BY tree_id ASC LIMIT ? OFFSET ?";
$stmtTrees = mysqli_prepare($link, $sqlTrees);

if ($stmtTrees) {
    $paramsForTrees = $params;
    $paramTypesForTrees = $paramTypes;

    $paramsForTrees[] = $limit;
    $paramsForTrees[] = $offset;
    $paramTypesForTrees .= "ii";

    if (count($paramsForTrees) != strlen($paramTypesForTrees)) {
        // Log error if needed
    } else {
        mysqli_stmt_bind_param($stmtTrees, $paramTypesForTrees, ...$paramsForTrees);
        mysqli_stmt_execute($stmtTrees);
        $resultTrees = mysqli_stmt_get_result($stmtTrees);

        while ($row = mysqli_fetch_assoc($resultTrees)) {
            $sampleTrees[] = $row;
        }
    }
    mysqli_stmt_close($stmtTrees);
} else {
    // Log error if needed
}
?>

<div class="page-wrapper">
    <?php require_once 'sidebar_menu.php'; // Include the sidebar ?>
    <div class="main-content">
        <h1>Forest Simulation Dashboard</h1>

        <h2>Overall Statistics</h2>
        <div class="summary-grid">
            <div class="summary-card">
                <h3>Total Trees</h3>
                <p><?php echo number_format($totalTrees); ?></p>
            </div>
            <div class="summary-card">
                <h3>Total Production (m³)</h3>
                <p><?php echo number_format($totalProd, 2); ?></p>
            </div>
            <div class="summary-card">
                <h3>Trees Marked 'Cut'</h3>
                <p><?php echo number_format($treesCut); ?></p>
            </div>
            <div class="summary-card">
                <h3>Trees 'Victim'</h3>
                <p><?php echo number_format($treesVictim); ?></p>
            </div>
            <div class="summary-card">
                <h3>Trees 'Keep'</h3>
                <p><?php echo number_format($treesKeep); ?></p>
            </div>
            <div class="summary-card">
                <h3>Trees 'Stand'</h3>
                <p><?php echo number_format($treesStand); ?></p>
            </div>
        </div>

        <hr>

        <h2>Tree Details</h2>

        <div class="filter-form">
            <form action="index.php" method="get">
                <label for="status">Filter by Status:</label>
                <select name="status" id="status">
                    <option value="">All</option>
                    <option value="cut" <?php echo ($filterStatus == 'cut' ? 'selected' : ''); ?>>Cut</option>
                    <option value="victim" <?php echo ($filterStatus == 'victim' ? 'selected' : ''); ?>>Victim</option>
                    <option value="keep" <?php echo ($filterStatus == 'keep' ? 'selected' : ''); ?>>Keep</option>
                    <option value="stand" <?php echo ($filterStatus == 'stand' ? 'selected' : ''); ?>>Stand</option>
                </select>

                <label for="block_i">Block I:</label>
                <input type="number" name="block_i" id="block_i" min="1" max="10" value="<?php echo htmlspecialchars($filterBlockI); ?>">

                <label for="block_j">Block J:</label>
                <input type="number" name="block_j" id="block_j" min="1" max="10" value="<?php echo htmlspecialchars($filterBlockJ); ?>">

                <button type="submit">Apply Filter</button>
                <button type="button" onclick="window.location.href='index.php'">Clear Filters</button>
            </form>
        </div>

        <?php if (!empty($sampleTrees)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Tree ID</th>
                        <th>Block</th>
                        <th>Coords (X,Y)</th>
                        <th>Species</th>
                        <th>Group</th>
                        <th>Diameter (cm)</th>
                        <th>Height (m)</th>
                        <th>Volume (m³)</th>
                        <th>Fell Crit.</th>
                        <th>Status</th>
                        <th>Cut Angle</th>
                        <th>Damage Stem (%)</th>
                        <th>Damage Crown (%)</th>
                        <th>Production (m³)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sampleTrees as $tree): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($tree['tree_id']); ?></td>
                        <td><?php echo htmlspecialchars($tree['block_i'] . ',' . $tree['block_j']); ?></td>
                        <td><?php echo htmlspecialchars($tree['coord_x'] . ',' . $tree['coord_y']); ?></td>
                        <td><?php echo htmlspecialchars($tree['species_name']); ?></td>
                        <td><?php echo htmlspecialchars($tree['species_group']); ?></td>
                        <td><?php echo htmlspecialchars(sprintf("%.2f", $tree['diameter'])); ?></td>
                        <td><?php echo htmlspecialchars(sprintf("%.2f", $tree['height'])); ?></td>
                        <td><?php echo htmlspecialchars(sprintf("%.4f", $tree['volume'])); ?></td>
                        <td><?php echo htmlspecialchars($tree['felling_criteria']); ?></td>
                        <td><?php echo htmlspecialchars($tree['status']); ?></td>
                        <td><?php echo htmlspecialchars($tree['cut_angle'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($tree['damage_stem'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($tree['damage_crown'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($tree['prod'] ?? '-'); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="pagination">
                <?php
                $baseUrl = "index.php?";
                $currentFilters = [];
                if (!empty($filterStatus)) $currentFilters['status'] = $filterStatus;
                if (!empty($filterBlockI)) $currentFilters['block_i'] = $filterBlockI;
                if (!empty($filterBlockJ)) $currentFilters['block_j'] = $filterBlockJ;

                foreach ($currentFilters as $key => $value) {
                    $baseUrl .= urlencode($key) . '=' . urlencode($value) . '&';
                }

                if ($page > 1) {
                    echo '<a href="' . $baseUrl . 'page=' . ($page - 1) . '">Previous</a>';
                }

                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);

                for ($p = $startPage; $p <= $endPage; $p++) {
                    if ($p == $page) {
                        echo '<span class="current-page">' . $p . '</span>';
                    } else {
                        echo '<a href="' . $baseUrl . 'page=' . $p . '">' . $p . '</a>';
                    }
                }

                if ($page < $totalPages) {
                    echo '<a href="' . $baseUrl . 'page=' . ($page + 1) . '">Next</a>';
                }
                ?>
            </div>
        <?php else: ?>
            <p>No tree data found for the current filters.</p>
        <?php endif; ?>
    </div></div><?php require_once 'footer.php'; // Includes the closing HTML tags and footer ?>