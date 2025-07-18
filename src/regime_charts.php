<?php

require_once 'header.php'; // Includes database connection and HTML head/nav
?>

<div class="container">
    <div class="chart-container">
        <h2>Production Charts (Year 0 vs. Year 30 by Species Group)</h2>
        <div id="productionLoadingMessage" style="text-align: center; padding: 20px;">
            Loading production chart data... Please wait.
        </div>
        <div id="productionChartsContainer">
            {{-- Canvases for production charts will be dynamically added here --}}
        </div>
        <div id="productionErrorMessage" style="color: red; text-align: center; padding: 20px; display: none;">
            {{-- Error message will be inserted here --}}
        </div>
    </div>

    <hr style="margin: 40px auto; border: 0; border-top: 1px solid #ccc;"> <div class="chart-container">
        <h2>Remainder Volume Charts (Year 0 vs Year 30 by Species Group)</h2>
        <div id="remainderLoadingMessage" style="text-align: center; padding: 20px;">
            Loading remainder chart data... Please wait.
        </div>
        <div id="remainderChartsContainer">
            {{-- Canvases for individual remainder charts (one per regime) will be dynamically added here --}}
        </div>
        <div id="remainderErrorMessage" style="color: red; text-align: center; padding: 20px; display: none;">
            {{-- Error message will be inserted here --}}
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Select elements for the Production Charts
    const productionLoadingMessage = document.getElementById('productionLoadingMessage');
    const productionChartsContainer = document.getElementById('productionChartsContainer'); // NEW: Container for multiple production charts
    const productionErrorMessage = document.getElementById('productionErrorMessage');

    // Select elements for the Remainder Charts (these remain largely the same)
    const remainderLoadingMessage = document.getElementById('remainderLoadingMessage');
    const remainderChartsContainer = document.getElementById('remainderChartsContainer');
    const remainderErrorMessage = document.getElementById('remainderErrorMessage');

    // Show loading messages initially for both sections
    productionLoadingMessage.style.display = 'block';
    productionChartsContainer.innerHTML = ''; // Clear any previous dynamic content
    productionErrorMessage.style.display = 'none';

    remainderLoadingMessage.style.display = 'block';
    remainderChartsContainer.innerHTML = ''; // Clear any previous dynamic content
    remainderErrorMessage.style.display = 'none';


    // Fetch all chart data from the PHP endpoint (regime_chart_data.php)
    fetch('regime_chart_data.php')
        .then(response => {
            if (!response.ok) {
                // If HTTP-status is not 2xx, throw an error
                return response.json().then(errorData => {
                    throw new Error(errorData.error || `HTTP error! Status: ${response.status}`);
                });
            }
            return response.json();
        })
        .then(data => {
            // Check for a general error from the PHP script
            if (data.error) {
                throw new Error(data.error);
            }

            // --- Render Production Charts (faceted by regime and species group) ---
            productionLoadingMessage.style.display = 'none';
            // Get the regimes data from the new structure
            const productionRegimes = Object.keys(data.production_chart_data_by_regime);

            if (productionRegimes.length === 0) {
                productionErrorMessage.style.display = 'block';
                productionErrorMessage.textContent = "No production chart data available. Please ensure data is present for production volumes for Year 0 and Year 30.";
            } else {
                productionRegimes.forEach(regime => {
                    const regimeData = data.production_chart_data_by_regime[regime];
                    const chartId = `production_chart_regime_${regime}`; // Unique ID for each canvas

                    // Create a container div for each chart (for better styling and structure)
                    const chartDiv = document.createElement('div');
                    chartDiv.style.width = '100%';
                    chartDiv.style.maxWidth = '800px'; // Adjust max-width as needed
                    chartDiv.style.margin = '20px auto'; // Center and add margin
                    chartDiv.style.height = '400px'; // Set a fixed height for each chart container
                    
                    chartDiv.innerHTML = `<h3 style="margin-top: 20px; text-align: center;"></h3><canvas id="${chartId}"></canvas>`;
                    productionChartsContainer.appendChild(chartDiv);

                    new Chart(document.getElementById(chartId).getContext('2d'), {
                        type: 'bar',
                        data: regimeData, // Use the specific regime data
                        options: {
                            responsive: true,
                            maintainAspectRatio: false, // Crucial for using container height
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                title: {
                                    display: true,
                                    text: `Production Volume by Species Group for ${regime}cm DBH Regime`
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': ' + context.parsed.y.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' m³';
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Species Group'
                                    },
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Volume (m³)'
                                    }
                                }
                            }
                        },
                    });
                });
            }

            // --- Render Remainder Charts (faceted by regime) ---
            // This part remains similar to your original code, ensuring it still works.
            remainderLoadingMessage.style.display = 'none';
            const remainderRegimes = Object.keys(data.remainder_chart_data_by_regime);

            if (remainderRegimes.length === 0) {
                remainderErrorMessage.style.display = 'block';
                remainderErrorMessage.textContent = "No remainder chart data available. Please ensure data is present for remainder volumes.";
            } else {
                remainderRegimes.forEach(regime => {
                    const regimeData = data.remainder_chart_data_by_regime[regime];
                    const chartId = `remainder_chart_regime_${regime}`; // Unique ID for each canvas

                    // Create a container div for each chart (for better styling and structure)
                    const chartDiv = document.createElement('div');
                    chartDiv.style.width = '100%';
                    chartDiv.style.maxWidth = '800px'; // Adjust max-width as needed
                    chartDiv.style.margin = '20px auto'; // Center and add margin
                    chartDiv.style.height = '400px'; // Set a fixed height for each chart container
                    
                    chartDiv.innerHTML = `<h3 style="margin-top: 20px; text-align: center;"></h3><canvas id="${chartId}"></canvas>`;
                    remainderChartsContainer.appendChild(chartDiv);

                    new Chart(document.getElementById(chartId).getContext('2d'), {
                        type: 'bar',
                        data: regimeData,
                        options: {
                            responsive: true,
                            maintainAspectRatio: false, // Crucial for using container height
                            plugins: {
                                legend: {
                                    position: 'top',
                                },
                                title: {
                                    display: true,
                                    text: `Remainder Volume by Species Group for ${regime}cm DBH Regime`
                                },
                                tooltip: {
                                    callbacks: {
                                        label: function(context) {
                                            return context.dataset.label + ': ' + context.parsed.y.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) + ' m³';
                                        }
                                    }
                                }
                            },
                            scales: {
                                x: {
                                    title: {
                                        display: true,
                                        text: 'Species Group'
                                    },
                                    grid: {
                                        display: false
                                    }
                                },
                                y: {
                                    beginAtZero: true,
                                    title: {
                                        display: true,
                                        text: 'Volume (m³)'
                                    }
                                }
                            }
                        },
                    });
                });
            }
        })
        .catch(error => {
            console.error('Error fetching chart data:', error);
            // Display errors for both sections if the initial fetch fails
            productionLoadingMessage.style.display = 'none';
            productionErrorMessage.style.display = 'block';
            productionErrorMessage.textContent = `Could not load production chart data: ${error.message}. Please ensure the database has data and PHP script path is correct.`;

            remainderLoadingMessage.style.display = 'none';
            remainderErrorMessage.style.display = 'block';
            remainderErrorMessage.textContent = `Could not load remainder chart data: ${error.message}. Please ensure the database has data and PHP script path is correct.`;
        });
});
</script>

<style>
/* Optional: Add some CSS for the main chart containers */
.chart-container {
    width: 90%;
    max-width: 1000px;
    height: auto; /* Allow height to adjust based on content */
    margin: 20px auto;
    position: relative;
    background-color: #fff;
    padding: 20px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    border-radius: 8px;
}
/* Ensure canvas elements can grow/shrink within their parents */
.chart-container canvas {
    max-height: 450px; /* Max height for individual charts */
    width: 100% !important; /* Force canvas to take full width of its parent */
    height: auto !important; /* Maintain aspect ratio or adjust by parent height */
}
/* Specific styling for the inner containers for each faceted chart */
#productionChartsContainer > div,
#remainderChartsContainer > div {
    /* Styles for the dynamically created chart divs */
    width: 100%;
    max-width: 800px; /* Can be adjusted */
    margin: 20px auto;
    height: 400px; /* Fixed height for consistency */
}
</style>

<?php require_once 'footer.php'; ?>