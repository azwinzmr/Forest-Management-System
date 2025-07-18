<?php

$currentPage = basename($_SERVER['PHP_SELF']); // Gets the current script filename (e.g., 'simulate.php', 'stand_tables.php')

// For regime-specific links, we need to check the 'regime' GET parameter
$current_regime = isset($_GET['regime']) ? (int)$_GET['regime'] : null;

?>

<div class="sidebar">
    <h3>Tools & Analyses</h3>
    <ul>
        <li><a href="simulate.php" class="<?php echo ($currentPage == 'simulate.php' ? 'active' : ''); ?>">Run New Simulation</a></li>
        <li><a href="regime_analysis.php" class="<?php echo ($currentPage == 'regime_analysis.php' ? 'active' : ''); ?>">Cutting Regime Analysis</a></li>

    <h3>Stand Tables</h3>
        <li><a href="stand_tables.php?regime=45" class="<?php echo ($currentPage == 'stand_tables.php' && $current_regime == 45 ? 'active' : ''); ?>">Regime 45cm</a></li>
        <li><a href="stand_tables.php?regime=50" class="<?php echo ($currentPage == 'stand_tables.php' && $current_regime == 50 ? 'active' : ''); ?>">Regime 50cm</a></li>
        <li><a href="stand_tables.php?regime=55" class="<?php echo ($currentPage == 'stand_tables.php' && $current_regime == 55 ? 'active' : ''); ?>">Regime 55cm</a></li>
        <li><a href="stand_tables.php?regime=60" class="<?php echo ($currentPage == 'stand_tables.php' && $current_regime == 60 ? 'active' : ''); ?>">Regime 60cm</a></li>


    <h3>Final Analysis</h3>
        <li><a href="final_output.php" class="<?php echo ($currentPage == 'final_comparative_output.php' ? 'active' : ''); ?>">Final Output Table</a></li>
        <li><a href="regime_charts.php" class="<?php echo ($currentPage == 'regime_charts.php' ? 'active' : ''); ?>">Regime Charts</a></li>
    </ul>
</div>