/**
 * Tulima Solar Admin - Charts
 * Chart initialization and management using Chart.js
 */

// Chart instances storage
const charts = {};

/**
 * Initialize a line chart
 */
function initLineChart(canvasId, data, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top',
                align: 'end',
                labels: {
                    usePointStyle: true,
                    padding: 20
                }
            },
            tooltip: {
                backgroundColor: '#1E293B',
                titleColor: '#F1F5F9',
                bodyColor: '#94A3B8',
                borderColor: '#E2E8F0',
                borderWidth: 1,
                padding: 12,
                displayColors: false,
                callbacks: {
                    label: function(context) {
                        if (context.dataset.label) {
                            return context.dataset.label + ': ' + context.parsed.y;
                        }
                        return context.parsed.y;
                    }
                }
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#94A3B8',
                    font: {
                        size: 12
                    }
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: '#E2E8F0'
                },
                ticks: {
                    color: '#94A3B8',
                    font: {
                        size: 12
                    }
                }
            }
        },
        interaction: {
            intersect: false,
            mode: 'index'
        }
    };
    
    const mergedOptions = { ...defaultOptions, ...options };
    
    charts[canvasId] = new Chart(ctx, {
        type: 'line',
        data: data,
        options: mergedOptions
    });
}

/**
 * Initialize an area chart (line with fill)
 */
function initAreaChart(canvasId, data, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    
    // Add fill to datasets
    const enhancedData = {
        ...data,
        datasets: data.datasets.map(dataset => ({
            ...dataset,
            fill: true,
            backgroundColor: dataset.backgroundColor || 'rgba(102, 0, 153, 0.1)',
            tension: 0.4
        }))
    };
    
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            tooltip: {
                backgroundColor: '#1E293B',
                titleColor: '#F1F5F9',
                bodyColor: '#94A3B8',
                borderColor: '#E2E8F0',
                borderWidth: 1
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#94A3B8'
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: '#E2E8F0'
                },
                ticks: {
                    color: '#94A3B8'
                }
            }
        }
    };
    
    const mergedOptions = { ...defaultOptions, ...options };
    
    charts[canvasId] = new Chart(ctx, {
        type: 'line',
        data: enhancedData,
        options: mergedOptions
    });
}

/**
 * Initialize a bar chart
 */
function initBarChart(canvasId, data, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            },
            tooltip: {
                backgroundColor: '#1E293B',
                titleColor: '#F1F5F9',
                bodyColor: '#94A3B8',
                borderColor: '#E2E8F0',
                borderWidth: 1
            }
        },
        scales: {
            x: {
                grid: {
                    display: false
                },
                ticks: {
                    color: '#94A3B8'
                }
            },
            y: {
                beginAtZero: true,
                grid: {
                    color: '#E2E8F0'
                },
                ticks: {
                    color: '#94A3B8'
                }
            }
        }
    };
    
    const mergedOptions = { ...defaultOptions, ...options };
    
    charts[canvasId] = new Chart(ctx, {
        type: 'bar',
        data: data,
        options: mergedOptions
    });
}

/**
 * Initialize a donut/pie chart
 */
function initDonutChart(canvasId, data, options = {}) {
    const ctx = document.getElementById(canvasId);
    if (!ctx) return;
    
    const defaultOptions = {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
            legend: {
                display: true,
                position: 'right',
                labels: {
                    padding: 20,
                    usePointStyle: true
                }
            },
            tooltip: {
                backgroundColor: '#1E293B',
                titleColor: '#F1F5F9',
                bodyColor: '#94A3B8',
                borderColor: '#E2E8F0',
                borderWidth: 1
            }
        }
    };
    
    const mergedOptions = { ...defaultOptions, ...options };
    
    charts[canvasId] = new Chart(ctx, {
        type: 'doughnut',
        data: data,
        options: mergedOptions
    });
}

/**
 * Destroy a chart
 */
function destroyChart(canvasId) {
    if (charts[canvasId]) {
        charts[canvasId].destroy();
        delete charts[canvasId];
    }
}

/**
 * Destroy all charts
 */
function destroyAllCharts() {
    Object.keys(charts).forEach(canvasId => {
        destroyChart(canvasId);
    });
}

/**
 * Update chart data
 */
function updateChart(canvasId, newData) {
    if (charts[canvasId]) {
        charts[canvasId].data = newData;
        charts[canvasId].update();
    }
}

/**
 * Format number with commas
 */
function formatNumber(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

/**
 * Format percentage
 */
function formatPercentage(num) {
    return num.toFixed(1) + '%';
}

/**
 * Get chart colors based on theme
 */
function getChartColors() {
    const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
    
    if (isDark) {
        return {
            primary: '#8B5CF6',
            success: '#10B981',
            warning: '#F59E0B',
            danger: '#EF4444',
            info: '#3B82F6',
            grid: '#334155',
            text: '#F1F5F9',
            textMuted: '#94A3B8'
        };
    }
    
    return {
        primary: '#660099',
        success: '#10B981',
        warning: '#F59E0B',
        danger: '#EF4444',
        info: '#3B82F6',
        grid: '#E2E8F0',
        text: '#1E293B',
        textMuted: '#94A3B8'
    };
}

/**
 * Load analytics data and render charts
 */
async function loadAnalyticsCharts() {
    try {
        // Load overview stats
        const overviewResponse = await fetch('api/analytics.php?action=overview');
        const overview = await overviewResponse.json();
        
        updateStatCards(overview);
        
        // Load traffic trends
        const trafficResponse = await fetch('api/analytics.php?action=traffic-trends&days=30');
        const trafficData = await trafficResponse.json();
        
        renderTrafficChart(trafficData);
        
        // Load top pages
        const pagesResponse = await fetch('api/analytics.php?action=top-pages&limit=10');
        const pagesData = await pagesResponse.json();
        
        renderTopPagesTable(pagesData);
        
        // Load contact analytics
        const contactResponse = await fetch('api/analytics.php?action=contact-analytics');
        const contactData = await contactResponse.json();
        
        renderContactAnalytics(contactData);
        
    } catch (error) {
        console.error('Error loading analytics:', error);
        showChartError();
    }
}

/**
 * Update stat cards with overview data
 */
function updateStatCards(overview) {
    // Update page views
    updateStatCard('page-views', overview.totalViews, overview.viewsChange);
    
    // Update unique visitors
    updateStatCard('unique-visitors', overview.uniqueVisitors, overview.visitorsChange);
    
    // Update contact submissions
    updateStatCard('contact-submissions', overview.totalSubmissions);
    
    // Update unread submissions
    updateStatCard('unread-submissions', overview.unreadSubmissions);
}

/**
 * Update a single stat card
 */
function updateStatCard(cardId, value, change = null) {
    const card = document.getElementById(cardId);
    if (!card) return;
    
    const valueElement = card.querySelector('.stat-card__value');
    if (valueElement) {
        valueElement.textContent = formatNumber(value);
    }
    
    if (change !== null) {
        const trendElement = card.querySelector('.stat-card__trend-value');
        const trendIcon = card.querySelector('.stat-card__trend-icon');
        
        if (trendElement && trendIcon) {
            trendElement.textContent = formatPercentage(Math.abs(change));
            
            if (change > 0) {
                card.classList.add('stat-card--trend-up');
                card.classList.remove('stat-card--trend-down');
                trendIcon.innerHTML = '↑';
            } else if (change < 0) {
                card.classList.add('stat-card--trend-down');
                card.classList.remove('stat-card--trend-up');
                trendIcon.innerHTML = '↓';
            } else {
                card.classList.remove('stat-card--trend-up', 'stat-card--trend-down');
                trendIcon.innerHTML = '−';
            }
        }
    }
}

/**
 * Render traffic trend chart
 */
function renderTrafficChart(data) {
    const labels = data.map(d => {
        const date = new Date(d.date);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
    
    const viewsData = data.map(d => d.views);
    const visitorsData = data.map(d => d.unique_visitors);
    
    const colors = getChartColors();
    
    const chartData = {
        labels: labels,
        datasets: [
            {
                label: 'Page Views',
                data: viewsData,
                borderColor: colors.primary,
                backgroundColor: 'rgba(102, 0, 153, 0.1)',
                fill: true,
                tension: 0.4
            },
            {
                label: 'Unique Visitors',
                data: visitorsData,
                borderColor: colors.success,
                backgroundColor: 'rgba(16, 185, 129, 0.1)',
                fill: true,
                tension: 0.4
            }
        ]
    };
    
    const options = {
        scales: {
            y: {
                grid: {
                    color: colors.grid
                },
                ticks: {
                    color: colors.textMuted
                }
            },
            x: {
                grid: {
                    color: colors.grid
                },
                ticks: {
                    color: colors.textMuted
                }
            }
        },
        plugins: {
            legend: {
                labels: {
                    color: colors.text
                }
            }
        }
    };
    
    initAreaChart('traffic-chart', chartData, options);
}

/**
 * Render top pages table
 */
function renderTopPagesTable(pages) {
    const tableBody = document.getElementById('top-pages-body');
    if (!tableBody) return;
    
    tableBody.innerHTML = pages.map((page, index) => `
        <tr>
            <td>${index + 1}</td>
            <td>
                <a href="/${page.page_slug}" target="_blank" class="data-table__link">
                    ${page.page_title || page.page_slug}
                </a>
            </td>
            <td class="data-table__number">${formatNumber(page.views)}</td>
            <td class="data-table__number">${formatNumber(page.unique_visitors)}</td>
            <td class="data-table__number">${page.avg_time_on_page ? Math.round(page.avg_time_on_page) + 's' : 'N/A'}</td>
            <td class="data-table__number ${getTrendClass(page.bounce_rate)}">
                ${formatPercentage(page.bounce_rate)}
            </td>
        </tr>
    `).join('');
}

/**
 * Get trend class based on value
 */
function getTrendClass(value) {
    if (value > 50) return 'data-table__trend-down';
    if (value < 30) return 'data-table__trend-up';
    return 'data-table__trend-neutral';
}

/**
 * Render contact analytics
 */
function renderContactAnalytics(data) {
    // Update contact stats
    updateStatCard('total-submissions', data.total);
    updateStatCard('response-rate', data.responseRate);
    updateStatCard('avg-response-time', Math.round(data.avgResponseTime) + 'h');
    
    // Render submissions trend chart
    renderSubmissionsTrend(data.trend);
    
    // Render peak hours
    renderPeakHours(data.peakHours);
}

/**
 * Render submissions trend chart
 */
function renderSubmissionsTrend(data) {
    const labels = data.map(d => {
        const date = new Date(d.date);
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    });
    
    const submissionsData = data.map(d => d.count);
    
    const colors = getChartColors();
    
    const chartData = {
        labels: labels,
        datasets: [{
            label: 'Submissions',
            data: submissionsData,
            borderColor: colors.primary,
            backgroundColor: 'rgba(102, 0, 153, 0.5)',
            fill: true
        }]
    };
    
    const options = {
        scales: {
            y: {
                beginAtZero: true,
                grid: {
                    color: colors.grid
                },
                ticks: {
                    color: colors.textMuted,
                    stepSize: 1
                }
            },
            x: {
                grid: {
                    color: colors.grid
                },
                ticks: {
                    color: colors.textMuted
                }
            }
        }
    };
    
    initBarChart('submissions-chart', chartData, options);
}

/**
 * Render peak hours
 */
function renderPeakHours(peakHours) {
    const container = document.getElementById('peak-hours-container');
    if (!container) return;
    
    const maxCount = Math.max(...peakHours.map(h => h.count));
    
    container.innerHTML = peakHours.map(hour => {
        const percentage = (hour.count / maxCount) * 100;
        return `
            <div class="progress-chart__item">
                <div class="progress-chart__label">
                    <span>${hour.hour}:00</span>
                    <span class="progress-chart__value">${hour.count}</span>
                </div>
                <div class="progress-chart__bar">
                    <div class="progress-chart__fill" style="width: ${percentage}%"></div>
                </div>
            </div>
        `;
    }).join('');
}

/**
 * Show chart error message
 */
function showChartError() {
    const containers = document.querySelectorAll('.chart-container');
    containers.forEach(container => {
        container.innerHTML = `
            <div class="chart-empty">
                <svg class="chart-empty__icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="8" x2="12" y2="12"/>
                    <line x1="12" y1="16" x2="12.01" y2="16"/>
                </svg>
                <h3 class="chart-empty__title">Unable to load data</h3>
                <p class="chart-empty__description">Please refresh the page or try again later.</p>
            </div>
        `;
    });
}

/**
 * Refresh all charts
 */
function refreshCharts() {
    showLoadingState();
    loadAnalyticsCharts();
}

/**
 * Show loading state
 */
function showLoadingState() {
    const containers = document.querySelectorAll('.chart-container');
    containers.forEach(container => {
        container.innerHTML = `
            <div class="chart-loading">
                <div class="chart-loading__spinner"></div>
            </div>
        `;
    });
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Check if we're on a page with charts
    if (document.querySelector('.chart-container')) {
        loadAnalyticsCharts();
    }
});

// Handle theme changes
const observer = new MutationObserver(function(mutations) {
    mutations.forEach(function(mutation) {
        if (mutation.attributeName === 'data-theme') {
            // Reload charts with new colors
            loadAnalyticsCharts();
        }
    });
});

observer.observe(document.documentElement, {
    attributes: true,
    attributeFilter: ['data-theme']
});
