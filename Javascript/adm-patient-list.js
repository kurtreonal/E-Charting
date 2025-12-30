// ========================================
// ADM-PATIENT-LIST.JS - RENOVATED
// Patient Outcomes & Metrics Scripts
// ========================================

// Wait for DOM to load
document.addEventListener('DOMContentLoaded', function() {
    initializeAnimations();
    initializeCounters();
    initializeCharts();
    initializeFilters();
});

// ========================================
// FILTER FUNCTIONALITY
// ========================================

function initializeFilters() {
    const applyFiltersBtn = document.querySelector('.apply-filters-btn');

    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', function() {
            applyFilters();
        });
    }

    // Quick action buttons
    const actionBtns = document.querySelectorAll('.action-btn');
    actionBtns.forEach(btn => {
        if (!btn.getAttribute('onclick')) {
            btn.addEventListener('click', function() {
                const action = this.textContent.trim();
                showNotification(action + ' feature coming soon!', 'info');
            });
        }
    });
}

function applyFilters() {
    const statusCheckboxes = document.querySelectorAll('.checkbox-group input[type="checkbox"]');
    const checkedStatuses = [];

    statusCheckboxes.forEach(checkbox => {
        if (checkbox.checked) {
            checkedStatuses.push(checkbox.value);
        }
    });

    const rows = document.querySelectorAll('#patientTableBody tr');
    let visibleCount = 0;

    rows.forEach(row => {
        const status = row.getAttribute('data-status');

        if (checkedStatuses.includes('all') || checkedStatuses.includes(status) || checkedStatuses.length === 0) {
            row.style.display = '';
            visibleCount++;
        } else {
            row.style.display = 'none';
        }
    });

    showNotification(`Showing ${visibleCount} patient(s)`, 'success');
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${type === 'success' ? '#d4edda' : '#d1ecf1'};
        color: ${type === 'success' ? '#155724' : '#0c5460'};
        border: 2px solid ${type === 'success' ? '#155724' : '#0c5460'};
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 4px 4px 0px rgba(0, 0, 0, 0.2);
        font-family: 'Cormorant Garamond', serif;
        font-weight: 600;
        z-index: 9999;
        animation: slideInRight 0.4s ease;
    `;
    notification.textContent = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.4s ease';
        setTimeout(() => notification.remove(), 400);
    }, 3000);
}

const filterAnimationStyle = document.createElement('style');
filterAnimationStyle.textContent = `
    @keyframes slideInRight {
        from { transform: translateX(400px); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(400px); opacity: 0; }
    }
`;
document.head.appendChild(filterAnimationStyle);

// ========================================
// ANIMATIONS
// ========================================

function initializeAnimations() {
    // Intersection Observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };

    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.opacity = '1';
                entry.target.style.transform = 'translateY(0)';
            }
        });
    }, observerOptions);

    // Observe all animated elements
    const animatedElements = document.querySelectorAll('[data-animate]');
    animatedElements.forEach(element => {
        observer.observe(element);
    });
}

// ========================================
// COUNTER ANIMATIONS
// ========================================

function initializeCounters() {
    const counters = document.querySelectorAll('.metric-value .value[data-count]');

    counters.forEach(counter => {
        const target = parseFloat(counter.getAttribute('data-count'));
        const duration = 2000; // 2 seconds
        const increment = target / (duration / 16); // 60 FPS
        let current = 0;

        const updateCounter = () => {
            current += increment;
            if (current < target) {
                // Handle decimal numbers
                if (target % 1 !== 0) {
                    counter.textContent = current.toFixed(1);
                } else {
                    counter.textContent = Math.floor(current);
                }
                requestAnimationFrame(updateCounter);
            } else {
                counter.textContent = target % 1 !== 0 ? target.toFixed(1) : target;
            }
        };

        updateCounter();
    });
}

// ========================================
// CHART INITIALIZATION
// ========================================

function initializeCharts() {
    initAgeChart();
    initGenderChart();
}

// Age Distribution Chart
function initAgeChart() {
    const ctx = document.getElementById('ageChart');
    if (!ctx) return;

    // Use data passed from PHP
    const data = typeof ageDistributionData !== 'undefined' ? ageDistributionData : [12, 25, 38, 42, 28, 15];

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['0-18', '19-30', '31-45', '46-60', '61-75', '76+'],
            datasets: [{
                label: 'Number of Patients',
                data: data,
                backgroundColor: [
                    'rgba(102, 126, 234, 0.8)',
                    'rgba(118, 75, 162, 0.8)',
                    'rgba(33, 150, 243, 0.8)',
                    'rgba(76, 175, 80, 0.8)',
                    'rgba(255, 152, 0, 0.8)',
                    'rgba(244, 67, 54, 0.8)'
                ],
                borderColor: [
                    'rgba(102, 126, 234, 1)',
                    'rgba(118, 75, 162, 1)',
                    'rgba(33, 150, 243, 1)',
                    'rgba(76, 175, 80, 1)',
                    'rgba(255, 152, 0, 1)',
                    'rgba(244, 67, 54, 1)'
                ],
                borderWidth: 2,
                borderRadius: 6
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
                    padding: 12,
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 13
                    },
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            return 'Patients: ' + context.parsed.y;
                        }
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: {
                        color: 'rgba(0, 0, 0, 0.05)'
                    },
                    ticks: {
                        font: {
                            size: 12
                        },
                        stepSize: Math.max(1, Math.ceil(Math.max(...data) / 10))
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            size: 12
                        }
                    }
                }
            },
            animation: {
                duration: 2000,
                easing: 'easeInOutQuart'
            }
        }
    });
}

// Gender Distribution Chart
function initGenderChart() {
    const ctx = document.getElementById('genderChart');
    if (!ctx) return;

    // Use data passed from PHP
    const data = typeof genderDistributionData !== 'undefined' ? genderDistributionData : [85, 72, 3];

    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Male', 'Female', 'Other'],
            datasets: [{
                data: data,
                backgroundColor: [
                    'rgba(33, 150, 243, 0.8)',
                    'rgba(233, 30, 99, 0.8)',
                    'rgba(156, 39, 176, 0.8)'
                ],
                borderColor: [
                    'rgba(33, 150, 243, 1)',
                    'rgba(233, 30, 99, 1)',
                    'rgba(156, 39, 176, 1)'
                ],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 20,
                        font: {
                            size: 13
                        },
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0, 0, 0, 0.8)',
                    padding: 12,
                    cornerRadius: 8,
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.parsed;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return label + ': ' + value + ' (' + percentage + '%)';
                        }
                    }
                }
            },
            animation: {
                duration: 2000,
                easing: 'easeInOutQuart',
                animateRotate: true,
                animateScale: true
            }
        }
    });
}

// ========================================
// UTILITY FUNCTIONS
// ========================================

// Smooth scroll for hash links
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
        const href = this.getAttribute('href');
        if (href !== '#') {
            e.preventDefault();
            const target = document.querySelector(href);
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        }
    });
});

// Console log for debugging
console.log('Patient List page initialized successfully');