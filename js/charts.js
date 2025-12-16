/**
 * charts.js - Chart.js utilities for IoT System
 */

class IoTCharts {
    constructor() {
        this.colors = {
            primary: '#4361ee',
            secondary: '#3a0ca3',
            success: '#4cc9f0',
            warning: '#f8961e',
            danger: '#f72585',
            info: '#7209b7',
            light: '#f8f9fa',
            dark: '#212529'
        };
        
        this.chartDefaults = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        font: {
                            family: "'Segoe UI', Tahoma, Geneva, Verdana, sans-serif"
                        }
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 13
                    },
                    padding: 12,
                    cornerRadius: 6
                }
            }
        };
    }
    
    /**
     * Create color distribution chart (doughnut/pie)
     */
    createColorDistribution(canvasId, data) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: data.colors,
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                ...this.chartDefaults,
                cutout: '65%',
                plugins: {
                    ...this.chartDefaults.plugins,
                    tooltip: {
                        ...this.chartDefaults.plugins.tooltip,
                        callbacks: {
                            label: function(context) {
                                return `${context.label}: ${context.raw} sản phẩm (${context.parsed}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        return chart;
    }
    
    /**
     * Create production trend chart (line/bar)
     */
    createProductionTrend(canvasId, data, type = 'line') {
        const ctx = document.getElementById(canvasId).getContext('2d');
        
        const chart = new Chart(ctx, {
            type: type,
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Sản phẩm',
                    data: data.values,
                    borderColor: this.colors.primary,
                    backgroundColor: this.addAlpha(this.colors.primary, 0.1),
                    borderWidth: 3,
                    fill: type === 'line' || type === 'area',
                    tension: 0.4,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                ...this.chartDefaults,
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            borderDash: [5, 5]
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        },
                        title: {
                            display: true,
                            text: 'Số lượng sản phẩm',
                            font: {
                                size: 14,
                                weight: 'bold'
                            }
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                }
            }
        });
        
        return chart;
    }
    
    /**
     * Create confidence distribution chart
     */
    createConfidenceDistribution(canvasId, data) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        
        const chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Số lượng',
                    data: data.values,
                    backgroundColor: [
                        this.addAlpha(this.colors.success, 0.7),
                        this.addAlpha(this.colors.warning, 0.7),
                        this.addAlpha(this.colors.danger, 0.7)
                    ],
                    borderColor: [
                        this.colors.success,
                        this.colors.warning,
                        this.colors.danger
                    ],
                    borderWidth: 2,
                    borderRadius: 6
                }]
            },
            options: {
                ...this.chartDefaults,
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            font: {
                                size: 13
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            font: {
                                size: 12
                            }
                        },
                        title: {
                            display: true,
                            text: 'Số lượng sản phẩm',
                            font: {
                                size: 13
                            }
                        }
                    }
                }
            }
        });
        
        return chart;
    }
    
    /**
     * Create real-time production chart
     */
    createRealTimeChart(canvasId, data) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [
                    {
                        label: 'Sản phẩm',
                        data: data.production,
                        borderColor: this.colors.primary,
                        backgroundColor: this.addAlpha(this.colors.primary, 0.1),
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4,
                        yAxisID: 'y'
                    },
                    {
                        label: 'Độ tin cậy TB',
                        data: data.confidence,
                        borderColor: this.colors.success,
                        backgroundColor: this.addAlpha(this.colors.success, 0.1),
                        borderWidth: 2,
                        fill: false,
                        tension: 0.4,
                        yAxisID: 'y1'
                    }
                ]
            },
            options: {
                ...this.chartDefaults,
                scales: {
                    x: {
                        type: 'realtime',
                        realtime: {
                            duration: 20000,
                            refresh: 1000,
                            delay: 2000,
                            onRefresh: function(chart) {
                                // This function will be called periodically
                                // In production, fetch new data from API
                                chart.data.datasets.forEach(function(dataset) {
                                    dataset.data.push({
                                        x: Date.now(),
                                        y: Math.random() * 100
                                    });
                                });
                            }
                        }
                    },
                    y: {
                        type: 'linear',
                        display: true,
                        position: 'left'
                    },
                    y1: {
                        type: 'linear',
                        display: true,
                        position: 'right',
                        min: 0,
                        max: 100,
                        grid: {
                            drawOnChartArea: false
                        }
                    }
                },
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    ...this.chartDefaults.plugins,
                    streaming: {
                        duration: 20000
                    }
                }
            }
        });
        
        return chart;
    }
    
    /**
     * Create multi-line comparison chart
     */
    createComparisonChart(canvasId, data) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        
        const datasets = [];
        const colors = [this.colors.primary, this.colors.success, this.colors.warning, this.colors.info];
        
        data.lines.forEach((line, index) => {
            datasets.push({
                label: line.name,
                data: line.values,
                borderColor: colors[index % colors.length],
                backgroundColor: this.addAlpha(colors[index % colors.length], 0.1),
                borderWidth: 2,
                fill: false,
                tension: 0.4
            });
        });
        
        const chart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: datasets
            },
            options: {
                ...this.chartDefaults,
                scales: {
                    x: {
                        grid: {
                            display: false
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            borderDash: [5, 5]
                        }
                    }
                }
            }
        });
        
        return chart;
    }
    
    /**
     * Create gauge chart for single metric
     */
    createGaugeChart(canvasId, value, max = 100, label = '') {
        const ctx = document.getElementById(canvasId).getContext('2d');
        
        const data = {
            datasets: [{
                data: [value, max - value],
                backgroundColor: [
                    this.getConfidenceColor(value, max),
                    '#e9ecef'
                ],
                borderWidth: 0,
                circumference: 180,
                rotation: 270
            }]
        };
        
        const chart = new Chart(ctx, {
            type: 'doughnut',
            data: data,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                circumference: 180,
                rotation: 270,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: false
                    }
                }
            },
            plugins: [{
                id: 'gaugeCenter',
                afterDraw(chart) {
                    const { ctx, chartArea: { width, height } } = chart;
                    ctx.save();
                    
                    const text = `${Math.round((value / max) * 100)}%`;
                    const text2 = label;
                    
                    ctx.font = 'bold 2rem "Segoe UI"';
                    ctx.fillStyle = this.colors.dark;
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText(text, width / 2, height / 2 + 20);
                    
                    ctx.font = '1rem "Segoe UI"';
                    ctx.fillStyle = this.colors.dark;
                    ctx.fillText(text2, width / 2, height / 2 - 20);
                    
                    ctx.restore();
                }.bind(this)
            }]
        });
        
        return chart;
    }
    
    /**
     * Update chart with new data
     */
    updateChart(chart, newData) {
        if (chart && newData) {
            chart.data.labels = newData.labels || chart.data.labels;
            
            if (Array.isArray(newData.datasets)) {
                chart.data.datasets = newData.datasets;
            } else if (newData.values) {
                chart.data.datasets.forEach((dataset, index) => {
                    dataset.data = newData.values[index] || dataset.data;
                });
            }
            
            chart.update('none');
        }
    }
    
    /**
     * Add data point to real-time chart
     */
    addDataPoint(chart, label, value, datasetIndex = 0) {
        if (chart) {
            chart.data.labels.push(label);
            chart.data.datasets[datasetIndex].data.push(value);
            
            // Keep only last 50 data points
            if (chart.data.labels.length > 50) {
                chart.data.labels.shift();
                chart.data.datasets.forEach(dataset => {
                    dataset.data.shift();
                });
            }
            
            chart.update('none');
        }
    }
    
    /**
     * Export chart as image
     */
    exportChart(chart, filename = 'chart.png') {
        if (chart) {
            const link = document.createElement('a');
            link.download = filename;
            link.href = chart.toBase64Image();
            link.click();
        }
    }
    
    /**
     * Get confidence-based color
     */
    getConfidenceColor(value, max = 100) {
        const percentage = (value / max) * 100;
        
        if (percentage >= 95) return this.colors.success;
        if (percentage >= 90) return this.colors.warning;
        return this.colors.danger;
    }
    
    /**
     * Add alpha to hex color
     */
    addAlpha(color, alpha) {
        if (color.startsWith('#')) {
            const r = parseInt(color.slice(1, 3), 16);
            const g = parseInt(color.slice(3, 5), 16);
            const b = parseInt(color.slice(5, 7), 16);
            
            return `rgba(${r}, ${g}, ${b}, ${alpha})`;
        }
        
        return color;
    }
    
    /**
     * Generate random data for testing
     */
    generateTestData(type = 'line', count = 10) {
        const labels = [];
        const data = [];
        
        for (let i = 0; i < count; i++) {
            if (type === 'time') {
                const date = new Date();
                date.setHours(date.getHours() - (count - i - 1));
                labels.push(date.toLocaleTimeString('vi-VN', { hour: '2-digit', minute: '2-digit' }));
            } else {
                labels.push(`Item ${i + 1}`);
            }
            
            data.push(Math.floor(Math.random() * 100) + 20);
        }
        
        return { labels, data };
    }
    
    /**
     * Initialize all charts on page
     */
    initializePageCharts() {
        // Auto-initialize charts with data attributes
        document.querySelectorAll('[data-chart]').forEach(element => {
            const chartType = element.getAttribute('data-chart');
            const chartData = element.getAttribute('data-chart-data');
            
            if (chartData) {
                try {
                    const data = JSON.parse(chartData);
                    
                    switch (chartType) {
                        case 'color-distribution':
                            this.createColorDistribution(element.id, data);
                            break;
                        case 'production-trend':
                            this.createProductionTrend(element.id, data);
                            break;
                        case 'confidence-distribution':
                            this.createConfidenceDistribution(element.id, data);
                            break;
                        case 'gauge':
                            this.createGaugeChart(element.id, data.value, data.max, data.label);
                            break;
                    }
                } catch (e) {
                    console.error('Error parsing chart data:', e);
                }
            }
        });
    }
    
    /**
     * Subscribe to real-time updates via WebSocket or SSE
     */
    subscribeToUpdates(chart, endpoint, interval = 5000) {
        setInterval(() => {
            fetch(endpoint)
                .then(response => response.json())
                .then(data => {
                    this.updateChart(chart, data);
                })
                .catch(error => {
                    console.error('Failed to fetch chart updates:', error);
                });
        }, interval);
    }
}

// Initialize global instance
const iotCharts = new IoTCharts();

// Auto-initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    iotCharts.initializePageCharts();
    
    // Initialize tooltips
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
});

// Export for module usage
if (typeof module !== 'undefined' && module.exports) {
    module.exports = IoTCharts;
}