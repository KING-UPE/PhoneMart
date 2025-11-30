document.addEventListener('DOMContentLoaded', function() {
    // Sales Chart
    const salesCtx = document.getElementById('salesChart').getContext('2d');
    
    // Prepare category datasets
    const categoryColors = {
        'Phones': 'rgba(67, 97, 238, 0.8)',
        'Tablets': 'rgba(76, 201, 240, 0.8)',
        'Accessories': 'rgba(248, 150, 30, 0.8)',
        'Wearables': 'rgba(255, 44, 79, 0.8)',
        'Others': 'rgba(106, 176, 76, 0.8)'
    };
    
    const categoryDatasets = [];
    for (const [category, values] of Object.entries(chartData.sales.categories)) {
        categoryDatasets.push({
            label: category,
            data: values,
            backgroundColor: categoryColors[category] || 'rgba(165, 105, 189, 0.8)',
            borderColor: 'rgba(255, 255, 255, 0.8)',
            borderWidth: 1,
            stack: 'stack'
        });
    }
    
    const salesChart = new Chart(salesCtx, {
        type: 'bar',
        data: {
            labels: chartData.sales.labels,
            datasets: [
                ...categoryDatasets
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: LKR ${context.raw.toLocaleString()}`;
                        },
                        footer: function(context) {
                            const total = context.reduce((sum, item) => sum + item.raw, 0);
                            return `Total: LKR ${total.toLocaleString()}`;
                        }
                    }
                },
                title: {
                    display: true,
                    text: 'Sales by Category'
                }
            },
            scales: {
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Revenue (LKR)'
                    },
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000) {
                                return 'LKR ' + (value / 1000000).toFixed(1) + 'M';
                            } else if (value >= 1000) {
                                return 'LKR ' + (value / 1000).toFixed(0) + 'K';
                            }
                            return 'LKR ' + value;
                        },
                        beginAtZero: true
                    },
                    stacked: true
                },
                x: {
                    stacked: true
                }
            },
            interaction: {
                intersect: false,
                mode: 'index',
            },
            onClick: (e, activeEls) => {
                if (activeEls.length > 0) {
                    const clickedDatasetIndex = activeEls[0].datasetIndex;
                    const clickedIndex = activeEls[0].index;
                    const value = salesChart.data.datasets[clickedDatasetIndex].data[clickedIndex];
                    const label = salesChart.data.labels[clickedIndex];
                    const category = salesChart.data.datasets[clickedDatasetIndex].label;
                    
                    console.log(`Revenue for ${category} in ${label}: LKR ${value.toLocaleString()}`);
                }
            }
        }
    });

    // Revenue Chart (Doughnut)
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(revenueCtx, {
        type: 'doughnut',
        data: {
            labels: chartData.revenue.labels,
            datasets: [{
                data: chartData.revenue.data,
                backgroundColor: [
                    'rgba(67, 97, 238, 0.8)',
                    'rgba(76, 201, 240, 0.8)',
                    'rgba(248, 150, 30, 0.8)',
                    'rgba(255, 44, 79, 0.8)',
                    'rgba(106, 176, 76, 0.8)'
                ],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((context.raw / total) * 100);
                            return `${context.label}: LKR ${context.raw.toLocaleString()} (${percentage}%)`;
                        }
                    }
                },
                title: {
                    display: true,
                    text: 'Revenue by Category'
                }
            },
            cutout: '70%'
        }
    });

    // Sales period filter
    document.getElementById('salesPeriod').addEventListener('change', function() {
        const months = parseInt(this.value);
        fetchSalesData(months);
    });

    // Function to fetch sales data for selected period
    async function fetchSalesData(months) {
        try {
            const response = await fetch(`dashboard.php?get_sales_data=1&months=${months}`);
            const data = await response.json();
            
            if (data.success) {
                // Update chart data
                salesChart.data.labels = data.labels;
                
                // Clear existing datasets except the first one (which is our main dataset)
                salesChart.data.datasets = [];
                
                // Add new category datasets
                for (const [category, values] of Object.entries(data.categories)) {
                    salesChart.data.datasets.push({
                        label: category,
                        data: values,
                        backgroundColor: categoryColors[category] || 'rgba(165, 105, 189, 0.8)',
                        borderColor: 'rgba(255, 255, 255, 0.8)',
                        borderWidth: 1,
                        stack: 'stack'
                    });
                }
                
                salesChart.update();
            }
        } catch (error) {
            console.error('Error fetching sales data:', error);
        }
    }
});