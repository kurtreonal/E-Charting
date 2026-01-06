// Analytics Dashboard JavaScript with Chart Filtering
// Enhanced version with dropdown filters

// Initialize charts when page loads
document.addEventListener('DOMContentLoaded', function() {
    initializeTabs();
    initializeCharts();
    initializeDropdowns();
    initializeAnimations();
});

let admissionsChartInstance = null;
let statusChartInstance = null;

// ============================================
// DASHBOARD TAB SWITCHING
// ============================================

function initializeTabs() {
    const dashboardTabs = document.querySelectorAll('.dashboard-tab');
    const dashboardSections = document.querySelectorAll('.dashboard-section');

    dashboardTabs.forEach(tab => {
        tab.addEventListener('click', function() {
            const sectionId = this.getAttribute('data-section');

            // Remove active class from all tabs and sections
            dashboardTabs.forEach(t => t.classList.remove('active'));
            dashboardSections.forEach(section => {
                section.style.display = 'none';
            });

            // Add active class to clicked tab
            this.classList.add('active');

            // Show corresponding section
            const targetSection = document.getElementById(sectionId);
            if (targetSection) {
                targetSection.style.display = 'block';

                // Reinitialize charts if switching to those sections
                // This ensures charts render properly when made visible
                if (sectionId === 'overview') {
                    setTimeout(() => {
                        if (admissionsChartInstance) admissionsChartInstance.resize();
                        if (statusChartInstance) statusChartInstance.resize();
                    }, 100);
                }
            }
        });
    });
}

// ============================================
// CHART INITIALIZATION
// ============================================

function initializeCharts() {
    // Patient Admissions Trend Chart
    const admissionsCtx = document.getElementById('admissionsChart');
    if (admissionsCtx) {
        admissionsChartInstance = new Chart(admissionsCtx, {
            type: 'line',
            data: {
                labels: admissionMonths,
                datasets: [{
                    label: 'Patient Admissions',
                    data: admissionCounts,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#667eea',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                        labels: {
                            font: {
                                family: "'Hero New', sans-serif",
                                size: 12
                            },
                            padding: 20
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            family: "'Cormorant Garamond', serif",
                            size: 14
                        },
                        bodyFont: {
                            family: "'Hero New', sans-serif",
                            size: 13
                        },
                        padding: 12,
                        displayColors: false,
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' admissions';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: {
                                family: "'Hero New', sans-serif"
                            }
                        },
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: "'Hero New', sans-serif"
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Patient Status Distribution Chart
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx) {
        statusChartInstance = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: statusLabels,
                datasets: [{
                    data: statusCounts,
                    backgroundColor: statusColors,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                family: "'Hero New', sans-serif",
                                size: 12
                            },
                            padding: 15,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleFont: {
                            family: "'Cormorant Garamond', serif",
                            size: 14
                        },
                        bodyFont: {
                            family: "'Hero New', sans-serif",
                            size: 13
                        },
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    // Age Distribution Chart
    const ageCtx = document.getElementById('ageChart');
    if (ageCtx) {
        new Chart(ageCtx, {
            type: 'bar',
            data: {
                labels: ['0-18', '19-30', '31-45', '46-60', '61-75', '76+'],
                datasets: [{
                    label: 'Patients',
                    data: [12, 28, 35, 42, 30, 15],
                    backgroundColor: [
                        'rgba(102, 126, 234, 0.8)',
                        'rgba(118, 75, 162, 0.8)',
                        'rgba(247, 147, 251, 0.8)',
                        'rgba(245, 87, 108, 0.8)',
                        'rgba(74, 172, 254, 0.8)',
                        'rgba(0, 242, 254, 0.8)'
                    ],
                    borderRadius: 5,
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 10,
                            font: {
                                family: "'Hero New', sans-serif"
                            }
                        }
                    },
                    x: {
                        ticks: {
                            font: {
                                family: "'Hero New', sans-serif"
                            }
                        },
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    }

    // Gender Distribution Chart
    const genderCtx = document.getElementById('genderChart');
    if (genderCtx) {
        new Chart(genderCtx, {
            type: 'pie',
            data: {
                labels: ['Male', 'Female', 'Other'],
                datasets: [{
                    data: [55, 42, 3],
                    backgroundColor: [
                        '#667eea',
                        '#f5576c',
                        '#00f2fe'
                    ],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: {
                                family: "'Hero New', sans-serif",
                                size: 12
                            },
                            padding: 15,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        callbacks: {
                            label: function(context) {
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
    }
}

// ============================================
// DROPDOWN FUNCTIONALITY
// ============================================

function initializeDropdowns() {
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function(e) {
            e.stopPropagation();

            // Close other dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(menu => {
                if (menu !== this.nextElementSibling) {
                    menu.classList.remove('show');
                }
            });

            // Toggle this dropdown
            const menu = this.nextElementSibling;
            menu.classList.toggle('show');
        });
    });

    // Close dropdowns when clicking outside
    document.addEventListener('click', function() {
        document.querySelectorAll('.dropdown-menu').forEach(menu => {
            menu.classList.remove('show');
        });
    });

    // Prevent dropdown from closing when clicking inside
    document.querySelectorAll('.dropdown-menu').forEach(menu => {
        menu.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    });

    // Handle admission trend period filter
    const admissionMenu = document.getElementById('admissionsMenu');
    if (admissionMenu) {
        admissionMenu.querySelectorAll('.dropdown-item[data-period]').forEach(item => {
            item.addEventListener('click', function() {
                const period = this.getAttribute('data-period');

                // Update active state
                admissionMenu.querySelectorAll('.dropdown-item').forEach(i => i.classList.remove('active'));
                this.classList.add('active');

                // Update chart (you would fetch new data here)
                updateAdmissionChart(period);

                // Close dropdown
                admissionMenu.classList.remove('show');
            });
        });
    }

    // Handle status distribution filter
    const statusMenu = document.getElementById('statusMenu');
    if (statusMenu) {
        statusMenu.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateStatusChart();
            });
        });
    }

    // Handle export actions
    document.querySelectorAll('.dropdown-item[data-action="export"]').forEach(item => {
        item.addEventListener('click', function() {
            const chartName = this.closest('.dropdown-menu').id.replace('Menu', '');
            exportChartData(chartName);
        });
    });
}

// ============================================
// CHART UPDATE FUNCTIONS
// ============================================

function updateAdmissionChart(period) {
    // In a real application, you would fetch data from the server here
    // For now, we'll just show a notification
    console.log('Updating admission chart for period:', period);

    // Example: You would make an AJAX call here
    // fetch(`get-admission-data.php?period=${period}`)
    //     .then(response => response.json())
    //     .then(data => {
    //         admissionsChartInstance.data.labels = data.months;
    //         admissionsChartInstance.data.datasets[0].data = data.counts;
    //         admissionsChartInstance.update();
    //     });

    showNotification(`Chart updated to show last ${period} months`, 'info');
}

function updateStatusChart() {
    const statusMenu = document.getElementById('statusMenu');
    if (!statusMenu || !statusChartInstance) return;

    // Get selected statuses
    const selectedStatuses = [];
    const checkboxes = statusMenu.querySelectorAll('input[type="checkbox"]:checked');
    checkboxes.forEach(cb => {
        selectedStatuses.push(cb.getAttribute('data-status'));
    });

    // Filter data
    const filteredLabels = [];
    const filteredData = [];
    const filteredColors = [];

    statusLabels.forEach((label, index) => {
        const statusKey = label.toLowerCase().replace(' ', '-');
        if (selectedStatuses.includes(statusKey)) {
            filteredLabels.push(label);
            filteredData.push(statusCounts[index]);
            filteredColors.push(statusColors[index]);
        }
    });

    // Update chart
    statusChartInstance.data.labels = filteredLabels;
    statusChartInstance.data.datasets[0].data = filteredData;
    statusChartInstance.data.datasets[0].backgroundColor = filteredColors;
    statusChartInstance.update();
}

function exportChartData(chartName) {
    console.log('Exporting data for chart:', chartName);
    showNotification('Chart data exported successfully', 'success');

    // In a real application, you would generate and download a CSV/Excel file here
}

// ============================================
// ANIMATIONS
// ============================================

function initializeAnimations() {
    // Animate KPI counters
    const kpiValues = document.querySelectorAll('.kpi-value[data-count]');
    kpiValues.forEach(element => {
        const target = parseInt(element.getAttribute('data-count'));
        animateCounter(element, 0, target, 1000);
    });

    // Fade in animations
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, { threshold: 0.1 });

    document.querySelectorAll('[data-animate]').forEach(el => {
        el.style.opacity = '0';
        el.style.transform = 'translateY(20px)';
        el.style.transition = 'all 0.6s ease';
        observer.observe(el);
    });
}

function animateCounter(element, start, end, duration) {
    const range = end - start;
    const increment = range / (duration / 16); // 60fps
    let current = start;

    const timer = setInterval(() => {
        current += increment;
        if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
            current = end;
            clearInterval(timer);
        }
        element.textContent = Math.floor(current);
    }, 16);
}

// ============================================
// NOTIFICATIONS
// ============================================

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
    `;

    // Style the notification
    notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: ${type === 'success' ? '#28a745' : type === 'error' ? '#dc3545' : '#17a2b8'};
        color: white;
        padding: 15px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        display: flex;
        align-items: center;
        gap: 10px;
        font-family: 'Hero New', sans-serif;
        z-index: 10000;
        animation: slideIn 0.3s ease;
    `;

    document.body.appendChild(notification);

    // Auto remove after 3 seconds
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Add animation keyframes
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(400px);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }

    @keyframes slideOut {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(400px);
            opacity: 0;
        }
    }

    .dropdown {
        position: relative;
    }

    .dropdown-menu {
        position: absolute;
        top: 100%;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        min-width: 180px;
        padding: 0.5rem 0;
        z-index: 1000;
        display: none;
        margin-top: 5px;
    }

    .dropdown-menu.show {
        display: block;
        animation: fadeIn 0.2s ease;
    }

    .dropdown-item {
        display: block;
        width: 100%;
        padding: 0.75rem 1rem;
        border: none;
        background: none;
        text-align: left;
        font-family: 'Hero New', sans-serif;
        font-size: 0.9rem;
        color: #333;
        cursor: pointer;
        transition: background 0.2s;
    }

    .dropdown-item:hover {
        background: #f8f9fa;
    }

    .dropdown-item.active {
        background: #667eea;
        color: white;
    }

    .dropdown-divider {
        height: 1px;
        background: #e9ecef;
        margin: 0.5rem 0;
    }

    .filter-options label {
        display: flex;
        align-items: center;
        gap: 8px;
        cursor: pointer;
    }

    .filter-options input[type="checkbox"] {
        cursor: pointer;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
document.head.appendChild(style);

