document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('monthlyCasesChart').getContext('2d');
    const chartCanvas = document.getElementById('monthlyCasesChart');

    // Initial data
    const initialLabels = JSON.parse(chartCanvas.dataset.labels || '[]');
    const initialData = JSON.parse(chartCanvas.dataset.values || '[]');

    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(124, 58, 237, 0.7)');
    gradient.addColorStop(1, 'rgba(124, 58, 237, 0.1)');

    // Create Chart Instance
    window.monthlyCasesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: initialLabels,
            datasets: [] // Will be populated by fetch
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'left',
                    labels: {
                        usePointStyle: true,
                        pointStyle: 'circle',
                        padding: 15,
                        font: { size: 11, weight: '500' },
                        color: '#64748b'
                    }
                },
                tooltip: {
                    backgroundColor: '#1E293B',
                    padding: 12,
                    titleFont: { size: 14, weight: '600' },
                    bodyFont: { size: 13 },
                    cornerRadius: 10,
                    displayColors: true
                }
            },
            scales: {
                y: {
                    stacked: true,
                    beginAtZero: true,
                    grid: {
                        drawBorder: false,
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        stepSize: 1,
                        color: '#94A3B8'
                    }
                },
                x: {
                    stacked: true,
                    grid: { display: false },
                    ticks: {
                        color: '#94A3B8',
                        font: { weight: '500' },
                        autoSkip: false,
                        maxRotation: 45,
                        minRotation: 45
                    }
                }
            }
        }
    });

    // Handle Filter Clicks
    document.querySelectorAll('.dropdown-item[data-period]').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();
            const period = this.dataset.period;
            const text = this.textContent;

            // Update UI
            document.querySelectorAll('.dropdown-item').forEach(el => el.classList.remove('active'));
            this.classList.add('active');
            document.getElementById('chartFilterDropdown').textContent = text;

            // Update Title based on selection
            const titleEl = document.getElementById('chartTitle');
            if (period === '1day') {
                titleEl.textContent = 'Hourly Cases';
            } else if (period === '1week' || period === '1month') {
                titleEl.textContent = 'Daily Cases';
            } else {
                titleEl.textContent = 'Monthly Cases';
            }

            // Fetch Data
            fetchChartData(period);
        });
    });

    function fetchChartData(range) {
        const loadingEl = document.getElementById('chartLoading');
        const noDataEl = document.getElementById('noDataMessage');
        const chartCanvas = document.getElementById('monthlyCasesChart');
        const totalContainer = document.getElementById('chartTotalContainer');
        const totalValueEl = document.getElementById('chartTotalValue');

        const colors = {
            'Academic': '#60A5FA', // Soft Blue
            'Disciplinary': '#F87171', // Soft Red
            'Hostel': '#34D399', // Soft Green
            'Library': '#FBBF24', // Soft Amber
            'Other': '#A78BFA' // Soft Purple
        };

        // Show loading
        if (loadingEl) loadingEl.style.display = 'block';
        if (noDataEl) noDataEl.style.display = 'none';
        chartCanvas.style.opacity = '0.3';

        fetch(`../student/get_cases_by_filter.php?range=${range}`)
            .then(response => response.json())
            .then(data => {
                if (loadingEl) loadingEl.style.display = 'none';
                chartCanvas.style.opacity = '1';

                if (data.error) {
                    console.error('Error:', data.error);
                    return;
                }

                const chart = window.monthlyCasesChart;

                // Update Labels
                chart.data.labels = data.labels;

                // Update Datasets with colors
                chart.data.datasets = data.datasets.map(ds => {
                    const baseDs = {
                        ...ds,
                        borderRadius: ds.type === 'line' ? 0 : 4,
                        barThickness: ds.type === 'line' ? undefined : (range === '1day' ? 30 : (range === '1week' ? 60 : (range === '1month' ? 40 : (range === 'all' ? 35 : 45))))
                    };
                    if (ds.type === 'line') {
                        baseDs.order = 0;
                    } else {
                        baseDs.backgroundColor = colors[ds.label] || '#94a3b8';
                        baseDs.borderColor = 'transparent';
                        baseDs.borderWidth = 0;
                        baseDs.order = 1;
                    }
                    return baseDs;
                });

                // Hide the "Total Trend" from the legend
                chart.options.plugins.legend.labels = chart.options.plugins.legend.labels || {};
                chart.options.plugins.legend.labels.filter = (item) => item.text !== 'Total Trend';

                // Handle Empty Data
                const hasData = data.datasets.some(ds => ds.data.some(val => val > 0));
                if (!hasData) {
                    if (noDataEl) noDataEl.style.display = 'block';
                    chartCanvas.style.visibility = 'hidden';
                } else {
                    if (noDataEl) noDataEl.style.display = 'none';
                    chartCanvas.style.visibility = 'visible';
                }

                // Update Total
                if (totalContainer && totalValueEl) {
                    totalValueEl.textContent = data.total;
                    totalContainer.style.display = 'flex';
                }

                chart.update();
            })
            .catch(error => {
                console.error('Error fetching data:', error);
                if (loadingEl) loadingEl.style.display = 'none';
                chartCanvas.style.opacity = '1';
            });
    }

    // Load initial data total (optional, but good for consistency)
    setTimeout(() => fetchChartData('6months'), 500);
});
