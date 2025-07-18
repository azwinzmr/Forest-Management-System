# Forest-Management-System
This PHP Forest Simulation models stands, calculates tree volumes and 30-year growth. It simulates logging, assessing timber production and damage. The system helps evaluate management regimes and their long-term impacts.
Here's what you can include in your `README.md` file for your Forest Simulation and Management System, structured for clarity and impact:

-----

# Forest Simulation and Management System 

A PHP-based system to model forest stands, simulate logging, calculate timber production, and assess environmental impacts over 30 years.

-----

## Overview

This project provides a comprehensive simulation framework for forest management. It allows users to:

  * Generate a realistic forest environment with various tree species, sizes, and locations.
  * Define and apply felling criteria for timber harvesting.
  * Simulate the immediate damage caused to surrounding trees by logging operations.
  * Calculate current and projected timber production volumes.
  * Project forest growth (diameter and volume) for unharvested trees over a 30-year period.
  * Evaluate different management regimes based on their economic output and ecological impact (damage assessment).

-----

## Key Features

  * **Tree Generation:** Procedural generation of individual trees across a 10x10 block forest, based on predefined species distribution and diameter classes.
  * **Dynamic Tree Attributes:** Calculation of tree height and volume based on diameter and species type (Dipterocarp/Non-Dipterocarp).
  * **Felling Criteria Application:** Identification of trees meeting specific felling criteria (species group, diameter threshold).
  * **Logging Damage Simulation:** Realistic simulation of stem and crown damage to adjacent trees caused by felling operations, considering fall angle and tree dimensions.
  * **Production Calculation:** Quantification of timber production from cut and fatally damaged (victim) trees.
  * **30-Year Projections:** Forecasting of tree diameter, volume, and potential future production for remaining "stand" and "keep" trees.
  * **Database Integration:** Stores and manages tree data and simulation results in a MySQL database.

-----

## Technologies Used

  * **PHP:** Core programming language for simulation logic and database interaction.
  * **MySQL:** Database for storing tree data, regime configurations, and simulation outputs.
  * **HTML/CSS:** (Implicit if accessed via web browser for display, as seen in `echo` statements).

-----

## Setup and Installation

To get this project up and running locally, you'll need a web server environment with PHP and MySQL (like XAMPP, WAMP, or MAMP).

1.  **Clone the Repository:**
    ```bash
    git clone https://github.com/YourUsername/YourForestProject.git
    cd YourForestProject
    ```
2.  **Database Setup:**
      * Create a MySQL database (e.g., `species`).
      * Import your initial schema (tables `trees`, `regime_45`, `regime_50`, `regime_55`, `regime_60`, `regime_damage_log`). You'll likely have `.sql` files for this.
      * Update database connection details in your PHP files (e.g., `db_connection.php` or directly in `identify_victim.php` if it's inline).
3.  **Place Files:**
      * Move the project files into your web server's document root (e.g., `htdocs` for XAMPP).
4.  **Species Data:**
      * Ensure you have a `species.csv` file in the expected location (as configured in your `main.php` or `generate_trees.php` script if you use one).
5.  **Run the Simulation:**
      * Access the main script (`main.php` or `identify_victim.php` depending on your entry point) via your web browser (e.g., `http://localhost/YourForestProject/identify_victim.php`).

-----

## Usage and Simulation Flow

The simulation typically follows these steps:

1.  **Initial Forest Generation:** Trees are generated based on distribution rules.
2.  **Initial Status Assignment:** Trees are classified as 'cut' or 'stand' based on felling criteria.
3.  **Damage Simulation:** For each 'cut' tree, damage to surrounding 'stand' or 'keep' trees is calculated and their statuses are updated to 'victim' or 'keep - damage crown'.
4.  **Production Calculation:** 'Cut' and 'victim' trees are assigned production values based on their volume.
5.  **30-Year Projection:** 'Stand' and 'keep' trees have their growth projected, and their future production potential is assessed.
6.  **Database Update:** All calculated statuses and data are persisted in the database for analysis.

-----

## Contributing

Contributions are welcome\! If you have suggestions for improvements, new features, or bug fixes, please open an issue or submit a pull request.

-----

## License

This project is open-source and available under the [MIT License](https://www.google.com/search?q=LICENSE).

-----
