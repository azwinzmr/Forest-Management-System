<?php
// functions.php - Helper functions for the forest application

/**
 * Loads species data from a CSV file.
 * Assumes the CSV has the following headers:
 * species_id,species_code,local_name,species_group,roy_class,is_dipterocarp
 *
 * @param string $filePath The path to the species CSV file.
 * @return array An array of species data, or an empty array on failure.
 */
function loadSpeciesData($filePath) {
    $speciesData = [];
    if (!file_exists($filePath)) {
        echo "Error: Species CSV file not found at " . $filePath . "\n";
        return $speciesData;
    }

    if (($handle = fopen($filePath, "r")) !== FALSE) {
        $header = fgetcsv($handle); // Read the header row

        // Find the column indices for the required fields
        $colMap = [];
        foreach ($header as $index => $colName) {
            $colMap[trim($colName)] = $index;
        }

        // Check if all required columns exist
        $requiredColumns = ['species_id', 'species_code', 'local_name', 'species_group', 'roy_class', 'is_dipterocarp'];
        foreach ($requiredColumns as $reqCol) {
            if (!isset($colMap[$reqCol])) {
                echo "Error: Missing required column '" . $reqCol . "' in species CSV.\n";
                fclose($handle);
                return [];
            }
        }

        while (($data = fgetcsv($handle)) !== FALSE) {
            // Map data to associative array using header names
            $speciesEntry = [
                'species_id' => $data[$colMap['species_id']],
                'species_code' => $data[$colMap['species_code']],
                'local_name' => $data[$colMap['local_name']],
                'species_group' => (int)$data[$colMap['species_group']], // Cast to int
                'roy_class' => $data[$colMap['roy_class']],
                'is_dipterocarp' => (int)$data[$colMap['is_dipterocarp']] // Cast to int (0 or 1)
            ];
            $speciesData[] = $speciesEntry;
        }
        fclose($handle);
    } else {
        echo "Error: Could not open species CSV file at " . $filePath . "\n";
    }
    return $speciesData;
}


/**
 * Helper function to get species names by species group.
 * @param array $allSpeciesData The full array of species data.
 * @return array An associative array where keys are species_group IDs and values are arrays of species names.
 */
function getSpeciesNamesByGroup($allSpeciesData) {
    $speciesNamesByGroup = [];
    foreach ($allSpeciesData as $species) {
        $group = $species['species_group'];
        if (!isset($speciesNamesByGroup[$group])) {
            $speciesNamesByGroup[$group] = [];
        }
        $speciesNamesByGroup[$group][] = $species['local_name'];
    }
    return $speciesNamesByGroup;
}

/**
 * Helper function to get is_dipterocarp status by species name.
 * @param array $allSpeciesData The full array of species data.
 * @return array An associative array where keys are species names and values are their is_dipterocarp status.
 */
function getIsDipterocarpStatusBySpecies($allSpeciesData) {
    $dipterocarpStatus = [];
    foreach ($allSpeciesData as $species) {
        $dipterocarpStatus[$species['local_name']] = $species['is_dipterocarp'];
    }
    return $dipterocarpStatus;
}

/**
 * Defines the tree distribution based on the provided table.
 * Keys are species_group_name, values are arrays of diameter classes and their counts.
 * The 'Total' for each group is implied by summing the diameter class counts.
 */
function getTreeDistributionTable() {
    return [
        'Group 1' => ['speciesgroup_name' => 'mersawa', '5-15' => 15, '15-30' => 12, '30-45' => 4, '45-60' => 2, '60+' => 2, 'Total' => 44],
        'Group 2' => ['speciesgroup_name' => 'keruing', '5-15' => 21, '15-30' => 18, '30-45' => 6, '45-60' => 4, '60+' => 4, 'Total' => 53],
        'Group 3' => ['speciesgroup_name' => 'Dip commercial', '5-15' => 21, '15-30' => 18, '30-45' => 6, '45-60' => 4, '60+' => 4, 'Total' => 53],
        'Group 4' => ['speciesgroup_name' => 'Dip Non Commercial', '5-15' => 30, '15-30' => 27, '30-45' => 9, '45-60' => 5, '60+' => 3, 'Total' => 74],
        'Group 5' => ['speciesgroup_name' => 'NonDip commercial', '5-15' => 30, '15-30' => 27, '30-45' => 9, '45-60' => 4, '60+' => 4, 'Total' => 74],
        'Group 6' => ['speciesgroup_name' => 'NonDip Non Commercial', '5-15' => 39, '15-30' => 36, '30-45' => 12, '45-60' => 7, '60+' => 4, 'Total' => 98],
        'Group 7' => ['speciesgroup_name' => 'Others', '5-15' => 44, '15-30' => 42, '30-45' => 14, '45-60' => 9, '60+' => 4, 'Total' => 113],
    ];
}

/**
 * Maps species group IDs to their names for easier lookup.
 * @return array
 */
function getSpeciesGroupMap() {
    return [
        1 => 'mersawa',
        2 => 'keruing',
        3 => 'Dip commercial',
        4 => 'Dip Non Commercial',
        5 => 'NonDip commercial',
        6 => 'NonDip Non Commercial',
        7 => 'Others'
    ];
}

/**
 * Generates a random diameter within a given diameter class range.
 * Diameter at breast height (DBH) in cm.
 *
 * @param string $diameterClass E.g., '5-15', '15-30', '60+'.
 * @return float Random diameter in cm.
 */
function generateRandomDiameter($diameterClass) {
    switch ($diameterClass) {
        case '5-15':
            return rand(500, 1500) / 100; // 5.00 to 15.00 cm
        case '15-30':
            return rand(1501, 3000) / 100; // 15.01 to 30.00 cm
        case '30-45':
            return rand(3001, 4500) / 100; // 30.01 to 45.00 cm
        case '45-60':
            return rand(4501, 6000) / 100; // 45.01 to 60.00 cm
        case '60+':
            return rand(6001, 10000) / 100; // 60.01 to 100.00 cm (arbitrary upper limit for 60+)
        default:
            return 0; // Should not happen
    }
}

/**
 * Generates a suitable height for a given diameter (DBH in cm).
 * This is a simplified estimation. You might want to refine this with actual forest growth models.
 *
 * @param float $diameter DBH in cm.
 * @return float Height in meters.
 */
function generateSuitableHeight($diameter) {
    if ($diameter < 15) {
        return round(rand(500, 1000) / 100 + ($diameter * 0.5), 2); // 5-10m + 0.5m/cm
    } elseif ($diameter < 30) {
        return round(rand(1000, 1500) / 100 + ($diameter * 0.6), 2); // 10-15m + 0.6m/cm
    } elseif ($diameter < 45) {
        return round(rand(1500, 2000) / 100 + ($diameter * 0.7), 2); // 15-20m + 0.7m/cm
    } elseif ($diameter < 60) {
        return round(rand(2000, 2500) / 100 + ($diameter * 0.8), 2); // 20-25m + 0.8m/cm
    } else { // 60+
        return round(rand(2500, 3500) / 100 + ($diameter * 0.9), 2); // 25-35m + 0.9m/cm
    }
}

/**
 * Generates all trees for all 10x10 blocks based on the distribution table.
 *
 * @param array $speciesNamesByGroup Array of species names grouped by species group ID.
 * @param array $isDipterocarpStatusBySpecies Array mapping species name to is_dipterocarp status.
 * @return array An array of generated tree data.
 */
function generateForestTrees(array $speciesNamesByGroup, array $isDipterocarpStatusBySpecies) {
    $allGeneratedTrees = [];
    $treeDistribution = getTreeDistributionTable();
    $speciesGroupMap = getSpeciesGroupMap();
    $globalTreeCounter = 0; // Initialize a global counter for unique tree_id

    // Iterate through 10x10 blocks
    for ($i = 1; $i <= 10; $i++) { // block_i
        for ($j = 1; $j <= 10; $j++) { // block_j
            // Prepare a flattened list of (species_group_name, diameter_class) pairs
            // based on the counts in the distribution table. This ensures we generate the exact number.
            $treeTemplates = [];
            foreach ($treeDistribution as $groupNameKey => $groupData) {
                $speciesGroupName = $groupData['speciesgroup_name'];
                $speciesGroupId = array_search($speciesGroupName, $speciesGroupMap); // Get group ID from name

                foreach (['5-15', '15-30', '30-45', '45-60', '60+'] as $dClass) {
                    $count = $groupData[$dClass];
                    for ($k = 0; $k < $count; $k++) {
                        $treeTemplates[] = [
                            'species_group_name' => $speciesGroupName,
                            'species_group_id' => $speciesGroupId,
                            'diameter_class' => $dClass
                        ];
                    }
                }
            }

            // Shuffle the templates to randomize the order of generation within the block
            shuffle($treeTemplates);

            foreach ($treeTemplates as $template) {
                $globalTreeCounter++; // Increment the counter for each tree

                $speciesGroupName = $template['species_group_name'];
                $speciesGroupId = $template['species_group_id'];
                $diameterClass = $template['diameter_class'];

                // Get available species for this group
                $availableSpecies = $speciesNamesByGroup[$speciesGroupId] ?? [];
                if (empty($availableSpecies)) {
                    $speciesName = 'UNKNOWN_SPECIES';
                    $isDipterocarp = 0;
                } else {
                    // Randomly select a species from the available list
                    $randomIndex = array_rand($availableSpecies);
                    $speciesName = $availableSpecies[$randomIndex];
                    $isDipterocarp = $isDipterocarpStatusBySpecies[$speciesName] ?? 0;
                }

                $coordX = rand(1, 100); // Random X coordinate within block (assuming 100x100 unit block)
                $coordY = rand(1, 100); // Random Y coordinate within block

                $diameterCm = generateRandomDiameter($diameterClass); // In cm
                $height = generateSuitableHeight($diameterCm); // In meters

                // Construct unique tree_id: TBxBvCxCv_N (e.g., T01014045_12345)
                $treeId = sprintf("T%02d%02d%02d%02d_%05d", $i, $j, $coordX, $coordY, $globalTreeCounter);

                $allGeneratedTrees[] = [
                    'tree_id' => $treeId,
                    'block_i' => $i,
                    'block_j' => $j,
                    'coord_x' => $coordX,
                    'coord_y' => $coordY,
                    'species_name' => $speciesName,
                    'species_group' => $speciesGroupId,
                    'diameter' => $diameterCm,
                    'diameter_class' => $diameterClass,
                    'height' => $height,
                    'is_dipterocarp' => $isDipterocarp,
                    'volume' => 0.0, // Placeholder, will be calculated later
                    'felling_criteria' => '', // Placeholder
                    'status' => '', // Placeholder
                    'cut_angle' => null, // Placeholder
                    'damage_stem' => null, // Placeholder
                    'damage_crown' => null, // Placeholder
                    'prod' => null, // Placeholder
                    'diameter30' => null, // Placeholder
                    'volume30' => null, // Placeholder
                    'prod30' => null // Placeholder
                ];
            }
        }
    }
    return $allGeneratedTrees;
}

/**
 * Calculates the volume of a tree based on its diameter, height, and dipterocarp status.
 * Uses 'Evergreen Forests' formulas.
 * D (Diameter) is in meters for these formulas. Height (L) is in meters.
 *
 * @param float $diameterM Diameter at breast height (DBH) in meters.
 * @param float $heightM Stem Height (L) in meters.
 * @param bool $isDipterocarp True if the tree is Dipterocarp, false otherwise.
 * @return float Calculated volume in cubic meters.
 */
function calculateTreeVolume($diameterM, $heightM, $isDipterocarp) {
    $D = $diameterM;
    $L = $heightM;
    $volume = 0.0;

    if ($isDipterocarp) {
        if ($D < 0.15) { // <15cm diameter
            $volume = 0.022 + 3.4 * pow($D, 2);
        } elseif ($D >= 0.15) { // 15cm+ diameter
            // Assume the L-dependent formula is for 45cm+ or specific 'larger trees' case
            if ($D >= 0.45) { // For D >= 45cm, use the L-dependent formula
                 $volume = 0.015 + 2.137 * pow($D, 2) + 0.513 * pow($D, 2) * $L;
            } else { // For 15cm <= D < 45cm
                $volume = -0.0971 + 9.503 * pow($D, 2);
            }
        }
    } else { // Non-Dipterocarp
        if ($D < 0.30) { // <30cm diameter
            $volume = 0.03 + 2.8 * pow($D, 2);
        } elseif ($D >= 0.30) { // 30cm+ diameter
            // Assume the L-dependent formula is for 45cm+ or specific 'larger trees' case
            if ($D >= 0.45) { // For D >= 45cm, use the L-dependent formula
                 $volume = -0.0023 + 2.942 * pow($D, 2) + 0.262 * pow($D, 2) * $L;
            } else { // For 30cm <= D < 45cm
                $volume = -0.331 + 6.694 * pow($D, 2);
            }
        }
    }
    return max(0.0, round($volume, 4)); // Ensure volume is not negative and round to 4 decimal places
}

/**
 * Determines if a tree meets the felling criteria.
 * Default criteria: Species Group 1,2,3,5 and Diameter > 45cm dbh.
 *
 * @param int $speciesGroup The species group ID.
 * @param float $diameterCm The tree's diameter in cm.
 * @param float $fellingDiameterThresholdCm The diameter threshold for felling in cm (default 45).
 * @return string 'met' or 'not met'.
 */
function determineFellingCriteria($speciesGroup, $diameterCm, $fellingDiameterThresholdCm = 45.0) {
    $fellingGroups = [1, 2, 3, 5]; // Species Group 1,2,3,5
    if (in_array($speciesGroup, $fellingGroups) && $diameterCm > $fellingDiameterThresholdCm) {
        return 'met';
    } else {
        return 'not met';
    }
}

/**
 * Determines the initial status of a tree based on felling criteria.
 * This status will be updated later by damage simulation.
 * @param string $fellingCriteria 'met' or 'not met'.
 * @return string 'cut' or 'stand'.
 */
function getInitialTreeStatus($fellingCriteria) {
    if ($fellingCriteria == 'met') {
        return 'cut';
    } else {
        return 'stand';
    }
}

/**
 * Calculates if a target tree (x,y) is damaged by a cutting tree (X0, Y0) with StemHeight and CutAngle.
 *
 * @param float $targetX X-coordinate of the tree being checked for damage.
 * @param float $targetY Y-coordinate of the tree being checked for damage.
 * @param float $cutterX0 X-coordinate of the cutting tree.
 * @param float $cutterY0 Y-coordinate of the cutting tree.
 * @param float $cutterStemHeight Stem height of the cutting tree in meters.
 * @param float $cutAngleDegrees Cutting angle in degrees (0-360).
 * @return array An associative array indicating damage type: ['stem' => bool, 'crown' => bool]
 */
function isTreeDamaged($targetX, $targetY, $cutterX0, $cutterY0, $cutterStemHeight, $cutAngleDegrees) {
    $damage = ['stem' => false, 'crown' => false];

    // Scale stem height for damage zone calculation (1m height = 1 coordinate unit for damage spread)
    $scaledStemHeight = round($cutterStemHeight);
    // Define the damage zone extension
    $damageExtend = $scaledStemHeight + 5; // As per your description (StemHeight + 5)

    // Damage based on quadrants
    if ($cutAngleDegrees >= 0 && $cutAngleDegrees < 90) { // Quadrant 1 (Top-Right)
        if ($targetX > $cutterX0 && $targetX < ($cutterX0 + $damageExtend) &&
            $targetY > $cutterY0 && $targetY < ($cutterY0 + $damageExtend)) {
            $damage['stem'] = true;
        }
    } elseif ($cutAngleDegrees >= 90 && $cutAngleDegrees < 180) { // Quadrant 2 (Bottom-Right)
        if ($targetX > $cutterX0 && $targetX < ($cutterX0 + $damageExtend) &&
            $targetY < $cutterY0 && $targetY > ($cutterY0 - $damageExtend)) {
            $damage['stem'] = true;
        }
    } elseif ($cutAngleDegrees >= 180 && $cutAngleDegrees < 270) { // Quadrant 3 (Bottom-Left)
        if ($targetX < $cutterX0 && $targetX > ($cutterX0 - $damageExtend) &&
            $targetY < $cutterY0 && $targetY > ($cutterY0 - $damageExtend)) {
            $damage['stem'] = true;
        }
    } elseif ($cutAngleDegrees >= 270 && $cutAngleDegrees <= 360) { // Quadrant 4 (Top-Left)
        if ($targetX < $cutterX0 && $targetX > ($cutterX0 - $damageExtend) &&
            $targetY > $cutterY0 && $targetY < ($cutterY0 + $damageExtend)) {
            $damage['stem'] = true;
        }
    }

    // Crown damage: if it's within a certain radius and not stem-damaged
    // A broader radius for crown damage
    $distance = sqrt(pow(($targetX - $cutterX0), 2) + pow(($targetY - $cutterY0), 2));
    if ($distance > 0 && $distance <= 15 && !$damage['stem']) { // Within 15 units radius, but not the cutter itself and not stem damaged
        $damage['crown'] = true;
    }

    return $damage;
}

/**
 * Simulates cutting operations and updates tree statuses based on damage.
 * This is the most performance-intensive part due to nested loops.
 *
 * @param array $allTrees The array of all generated trees.
 * @return array The updated array of trees with status, damage_stem, damage_crown, and cut_angle.
 */
function simulateForestDamage(array $allTrees) {
    $trees = $allTrees; // Create a mutable copy to work with

    // Separate trees into those to be cut (source of damage)
    $treesToCut = array_filter($trees, function($tree) {
        return $tree['status'] === 'cut';
    });

    foreach ($treesToCut as $cutterTree) {
        // Assign a random cut_angle for the tree being cut (in degrees)
        $cutAngleDegrees = rand(1, 360);
        // Find the actual tree in the main array by reference to update its cut_angle
        foreach ($trees as &$treeRef) {
            if ($treeRef['tree_id'] === $cutterTree['tree_id']) {
                $treeRef['cut_angle'] = $cutAngleDegrees;
                break;
            }
        }
        unset($treeRef); // Unset the reference

        $cutterX0 = $cutterTree['coord_x'];
        $cutterY0 = $cutterTree['coord_y'];
        $cutterStemHeight = $cutterTree['height']; // Height in meters

        // Iterate through ALL other trees in the same block to check for damage
        foreach ($trees as &$targetTree) {
            // Only check if targetTree is in the same block AND is not the cutter tree itself
            // AND is not already 'cut' (as cut trees are the source of damage)
            if ($targetTree['block_i'] === $cutterTree['block_i'] &&
                $targetTree['block_j'] === $cutterTree['block_j'] &&
                $targetTree['tree_id'] !== $cutterTree['tree_id'] &&
                $targetTree['status'] !== 'cut') { // Don't re-damage already cut trees

                $targetX = $targetTree['coord_x'];
                $targetY = $targetTree['coord_y'];

                $damageResult = isTreeDamaged($targetX, $targetY, $cutterX0, $cutterY0, $cutterStemHeight, $cutAngleDegrees);

                if ($damageResult['stem']) {
                    $targetTree['status'] = 'victim';
                    $targetTree['damage_stem'] = 100.00; // 100% volume affected by fatal damage
                    $targetTree['damage_crown'] = '-'; // Mark as '-' as per requirement
                } elseif ($damageResult['crown']) {
                    if ($targetTree['status'] !== 'victim') { // Don't override stem damage with crown damage
                        $targetTree['status'] = 'keep';
                        $targetTree['damage_crown'] = rand(1000, 5000) / 100; // 10.00% to 50.00%
                        $targetTree['damage_stem'] = '-'; // Mark as '-'
                    }
                } else {
                    // No damage, ensure damage fields are marked as '-' if they haven't been set by another cutter
                    if ($targetTree['status'] !== 'victim' && $targetTree['status'] !== 'keep') {
                        $targetTree['damage_stem'] = '-';
                        $targetTree['damage_crown'] = '-';
                    }
                }
            }
        }
        unset($targetTree); // Unset the reference
    }

    return $trees;
}

/**
 * Calculates production (prod) values based on tree status.
 *
 * @param array $trees The array of tree data.
 * @return array The updated array of trees with 'prod' values.
 */
function calculateProduction(array $trees) {
    foreach ($trees as &$tree) {
        if ($tree['status'] === 'cut') {
            $tree['prod'] = round($tree['volume'], 4); // Production is full volume for cut trees
        } elseif ($tree['status'] === 'victim') {
            $tree['prod'] = round($tree['volume'], 4); // Production is full volume for victim trees (100% damaged)
        } else {
            $tree['prod'] = '-'; // Not applicable for 'stand' and 'keep' trees
        }
    }
    return $trees;
}

/**
 * Applies diameter increment based on diameter class for projection.
 *
 * @param float $currentDiameterCm Current diameter in cm.
 * @return float Incremented diameter in cm.
 */
function applyDiameterIncrement($currentDiameterCm) {
    // Determine diameter class for the increment rate
    if ($currentDiameterCm >= 5 && $currentDiameterCm < 15) {
        $increment = 0.4;
    } elseif ($currentDiameterCm >= 15 && $currentDiameterCm < 30) {
        $increment = 0.6;
    } elseif ($currentDiameterCm >= 30 && $currentDiameterCm < 45) {
        $increment = 0.5;
    } elseif ($currentDiameterCm >= 45 && $currentDiameterCm < 60) {
        $increment = 0.5;
    } elseif ($currentDiameterCm >= 60) {
        $increment = 0.7;
    } else {
        $increment = 0; // For diameters < 5cm or invalid
    }
    return $currentDiameterCm + $increment;
}

/**
 * Calculates 30-year projections for diameter, volume, and production.
 * Applies only to trees with 'stand' or 'keep' status.
 *
 * @param array $trees The array of tree data.
 * @param float $fellingDiameterThresholdCm The diameter threshold to apply for prod30 calculation.
 * @return array The updated array of trees with diameter30, volume30, and prod30.
 */
function calculate30YearProjections(array $trees, $fellingDiameterThresholdCm = 45.0) {
    foreach ($trees as &$tree) {
        if ($tree['status'] === 'stand' || $tree['status'] === 'keep') {
            $projectedDiameterCm = $tree['diameter']; // Start with current diameter

            // Iteratively calculate diameter for 30 years
            for ($year = 1; $year <= 30; $year++) {
                $projectedDiameterCm = applyDiameterIncrement($projectedDiameterCm);
            }
            $tree['diameter30'] = round($projectedDiameterCm, 2);

            // Calculate volume30 based on diameter30
            $projectedDiameterM = $tree['diameter30'] / 100;
            // Recalculate suitable height for the new projected diameter
            $projectedHeight = generateSuitableHeight($tree['diameter30']);
            $tree['volume30'] = calculateTreeVolume($projectedDiameterM, $projectedHeight, (bool)$tree['is_dipterocarp']);

            // Production 30: If it *would* meet felling criteria after 30 years.
            if (determineFellingCriteria($tree['species_group'], $tree['diameter30'], $fellingDiameterThresholdCm) == 'met') {
                $tree['prod30'] = round($tree['volume30'], 4);
            } else {
                $tree['prod30'] = '-'; // Does not meet felling criteria after 30 years
            }

        } else {
            // For 'cut' or 'victim' trees, projection values are not applicable
            $tree['diameter30'] = '-';
            $tree['volume30'] = '-';
            $tree['prod30'] = '-';
        }
    }
    return $trees;
}


/**
 * The main function to run the entire forest simulation and calculations.
 *
 * @param array $allSpeciesData The loaded species data.
 * @param float $fellingDiameterThresholdCm The diameter threshold for felling in cm.
 * @return array The complete array of processed tree data.
 */
function runFullForestSimulation(array $allSpeciesData, $fellingDiameterThresholdCm = 45.0) {
    // 1. Prepare species lookup data
    $speciesNamesByGroup = getSpeciesNamesByGroup($allSpeciesData);
    $isDipterocarpStatusBySpecies = getIsDipterocarpStatusBySpecies($allSpeciesData);

    // 2. Generate initial tree data
    echo "Generating initial forest trees... (This will take a moment)<br>";
    $generatedTrees = generateForestTrees($speciesNamesByGroup, $isDipterocarpStatusBySpecies);
    echo "Initial tree generation complete. Total trees: " . count($generatedTrees) . "<br>";

    // 3. Calculate initial volume and felling criteria, set initial status
    echo "Calculating initial volume, felling criteria, and status...<br>";
    $treesWithInitialCalcs = [];
    foreach ($generatedTrees as $tree) {
        $diameterM = $tree['diameter'] / 100;
        $tree['volume'] = calculateTreeVolume($diameterM, $tree['height'], (bool)$tree['is_dipterocarp']);
        $tree['felling_criteria'] = determineFellingCriteria($tree['species_group'], $tree['diameter'], $fellingDiameterThresholdCm);
        $tree['status'] = getInitialTreeStatus($tree['felling_criteria']);
        $treesWithInitialCalcs[] = $tree;
    }
    echo "Initial calculations complete.<br>";

    // 4. Simulate damage to update status, damage_stem, damage_crown
    echo "Simulating forest damage from cutting operations... (This is the most compute-intensive part)<br>";
    $treesAfterDamage = simulateForestDamage($treesWithInitialCalcs);
    echo "Damage simulation complete.<br>";

    // 5. Calculate production ('prod') based on final status
    echo "Calculating production values...<br>";
    $treesWithProduction = calculateProduction($treesAfterDamage);
    echo "Production calculation complete.<br>";

    // 6. Calculate 30-year projections
    echo "Calculating 30-year projections...<br>";
    $finalTreesData = calculate30YearProjections($treesWithProduction, $fellingDiameterThresholdCm);
    echo "30-year projections complete.<br>";

    return $finalTreesData;
}

?>