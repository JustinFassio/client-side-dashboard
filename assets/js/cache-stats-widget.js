/**
 * Cache Stats Widget JavaScript
 */
(function() {
    let hitRateChart = null;
    let responseTimeChart = null;

    /**
     * Initialize the widget.
     */
    function init() {
        if (!window.athleteDashboardCacheStats) {
            console.error('Cache stats data not found');
            return;
        }

        createCharts();
        setupRefreshButton();
    }

    /**
     * Create the charts using Chart.js
     */
    function createCharts() {
        const stats = window.athleteDashboardCacheStats.stats;
        const commonOptions = {
            responsive: true,
            maintainAspectRatio: false,
            animation: {
                duration: 750,
                easing: 'easeInOutQuart'
            },
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.1)'
                    }
                },
                x: {
                    grid: {
                        display: false
                    }
                }
            }
        };

        // Hit Rate Chart
        const hitRateCtx = document.getElementById('cacheHitRateChart').getContext('2d');
        hitRateChart = new Chart(hitRateCtx, {
            type: 'line',
            data: {
                labels: stats.labels,
                datasets: [{
                    label: 'Hit Rate %',
                    data: stats.hitRates,
                    borderColor: '#2271b1',
                    backgroundColor: 'rgba(34, 113, 177, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    ...commonOptions.scales,
                    y: {
                        ...commonOptions.scales.y,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Hit Rate %'
                        }
                    }
                }
            }
        });

        // Response Time Chart
        const responseTimeCtx = document.getElementById('responseTimeChart').getContext('2d');
        responseTimeChart = new Chart(responseTimeCtx, {
            type: 'line',
            data: {
                labels: stats.labels,
                datasets: [{
                    label: 'Response Time (ms)',
                    data: stats.responseTimes,
                    borderColor: '#674399',
                    backgroundColor: 'rgba(103, 67, 153, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                ...commonOptions,
                scales: {
                    ...commonOptions.scales,
                    y: {
                        ...commonOptions.scales.y,
                        title: {
                            display: true,
                            text: 'Response Time (ms)'
                        }
                    }
                }
            }
        });
    }

    /**
     * Set up refresh button functionality
     */
    function setupRefreshButton() {
        const refreshButton = document.getElementById('refreshCacheStats');
        if (!refreshButton) return;

        refreshButton.addEventListener('click', async () => {
            refreshButton.disabled = true;
            refreshButton.textContent = 'Refreshing...';

            try {
                const response = await fetch('/wp-admin/admin-ajax.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'refresh_cache_stats',
                        nonce: window.athleteDashboardCacheStats.nonce
                    })
                });

                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const data = await response.json();
                if (data.success) {
                    updateCharts(data.data);
                    updateSummary(data.data.current);
                }
            } catch (error) {
                console.error('Error refreshing stats:', error);
            } finally {
                refreshButton.disabled = false;
                refreshButton.textContent = 'Refresh Stats';
            }
        });
    }

    /**
     * Update charts with new data
     */
    function updateCharts(data) {
        if (hitRateChart) {
            hitRateChart.data.labels = data.labels;
            hitRateChart.data.datasets[0].data = data.hitRates;
            hitRateChart.update();
        }

        if (responseTimeChart) {
            responseTimeChart.data.labels = data.labels;
            responseTimeChart.data.datasets[0].data = data.responseTimes;
            responseTimeChart.update();
        }
    }

    /**
     * Update summary statistics
     */
    function updateSummary(stats) {
        const hitRateEl = document.querySelector('.stat-box:nth-child(1) .stat-value');
        const responseTimeEl = document.querySelector('.stat-box:nth-child(2) .stat-value');
        const memoryUsageEl = document.querySelector('.stat-box:nth-child(3) .stat-value');
        const lastUpdatedEl = document.querySelector('.cache-stats-footer .description');

        if (hitRateEl) {
            const hitRate = (stats.hit_rate * 100).toFixed(1);
            hitRateEl.textContent = `${hitRate}%`;
            hitRateEl.className = `stat-value ${hitRate >= 80 ? 'good' : (hitRate >= 60 ? 'warning' : 'poor')}`;
        }

        if (responseTimeEl) {
            const responseTime = stats.avg_response_time.toFixed(1);
            responseTimeEl.textContent = `${responseTime}ms`;
            responseTimeEl.className = `stat-value ${responseTime <= 200 ? 'good' : (responseTime <= 500 ? 'warning' : 'poor')}`;
        }

        if (memoryUsageEl) {
            memoryUsageEl.textContent = stats.memory_usage;
        }

        if (lastUpdatedEl) {
            lastUpdatedEl.textContent = `Last updated: just now`;
        }
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})(); 