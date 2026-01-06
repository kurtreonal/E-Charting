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
    initializeClickableStatus();
});

// ========================================
// CLICKABLE STATUS BADGES
// ========================================

function initializeClickableStatus() {
    const statusBadges = document.querySelectorAll('.status-badge:not([data-status-initialized])');

    statusBadges.forEach(badge => {
        const status = badge.textContent.trim().toLowerCase();

        // Don't make deceased status clickable
        if (status === 'deceased') {
            badge.style.cursor = 'not-allowed';
            badge.style.opacity = '0.6';
            badge.setAttribute('data-status-initialized', 'true');
            return;
        }

        // Make other statuses clickable
        badge.style.cursor = 'pointer';
        badge.title = getStatusClickHint(status);

        // Add click handler
        badge.addEventListener('click', function(e) {
            e.preventDefault();
            handleStatusClick(this);
        });

        // Add hover effect
        badge.addEventListener('mouseenter', function() {
            if (status !== 'deceased') {
                this.style.transform = 'scale(1.05)';
                this.style.transition = 'transform 0.2s ease';
            }
        });

        badge.addEventListener('mouseleave', function() {
            this.style.transform = 'scale(1)';
        });

        // Mark as initialized to prevent duplicate event listeners
        badge.setAttribute('data-status-initialized', 'true');
    });
}

function getStatusClickHint(status) {
    switch(status) {
        case 'in-patient':
            return 'Click to discharge patient';
        case 'active':
            return 'Click to discharge patient';
        case 'out-patient':
            return 'Click to reactivate patient';
        case 'deceased':
            return 'Status cannot be changed';
        default:
            return 'Click to change status';
    }
}

function handleStatusClick(badge) {
    const row = badge.closest('tr');
    const patientId = getPatientIdFromRow(row);

    // Extract status from badge class name first (more reliable)
    let currentStatus = '';
    const badgeClass = badge.className;

    if (badgeClass.includes('inpatient')) {
        currentStatus = 'in-patient';
    } else if (badgeClass.includes('outpatient')) {
        currentStatus = 'out-patient';
    } else if (badgeClass.includes('status-active')) {
        currentStatus = 'active';
    } else if (badgeClass.includes('deceased')) {
        showNotification('Cannot change status for deceased patients', 'error');
        return;
    }

    // Fallback: extract status from badge text content
    if (!currentStatus) {
        const statusText = badge.textContent.trim().toLowerCase();

        if (statusText.includes('in-patient') || statusText.includes('inpatient')) {
            currentStatus = 'in-patient';
        } else if (statusText.includes('out-patient') || statusText.includes('outpatient')) {
            currentStatus = 'out-patient';
        } else if (statusText === 'active') {
            currentStatus = 'active';
        } else if (statusText === 'deceased') {
            showNotification('Cannot change status for deceased patients', 'error');
            return;
        }
    }

    if (!currentStatus) {
        console.error('Could not determine status. Badge class:', badgeClass, 'Badge text:', badge.textContent);
        showNotification('Error: Could not determine patient status', 'error');
        return;
    }

    // Store original badge state
    const originalText = badge.textContent;
    const originalClass = badge.className;

    // Show loading state
    badge.textContent = 'Updating...';
    badge.style.opacity = '0.6';
    badge.style.pointerEvents = 'none';

    // Send AJAX request
    fetch('update-patient-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            patient_id: patientId,
            current_status: currentStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update badge with new status
            updateStatusBadge(badge, data.new_status, data.previous_status);

            // Update row data attribute
            const newStatusClass = data.new_status.replace('-', '');
            row.setAttribute('data-status', newStatusClass);

            // Show success notification
            showNotification(`${data.patient_name}: ${data.message}`, 'success');

            // Re-enable click
            badge.style.pointerEvents = 'auto';
        } else {
            // Revert on error
            badge.textContent = originalText;
            badge.className = originalClass;
            badge.style.opacity = '1';
            badge.style.pointerEvents = 'auto';

            showNotification(data.error || 'Failed to update status', 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);

        // Revert on error
        badge.textContent = originalText;
        badge.className = originalClass;
        badge.style.opacity = '1';
        badge.style.pointerEvents = 'auto';

        showNotification('Network error. Please try again.', 'error');
    });
}

function getPatientIdFromRow(row) {
    // Get patient ID from the "Admit Patient" link
    const admitLink = row.querySelector('a[href*="admit-patient.php"]');
    if (admitLink) {
        const href = admitLink.getAttribute('href');
        const match = href.match(/patient_id=(\d+)/);
        if (match) {
            return parseInt(match[1]);
        }
    }
    return 0;
}

function updateStatusBadge(badge, newStatus, previousStatus) {
    // Store previous status in data attribute for potential revert
    badge.setAttribute('data-previous-status', previousStatus);

    // Update badge text
    const statusText = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
    badge.textContent = statusText;

    // Update badge class
    const newClass = newStatus.replace('-', '');
    badge.className = `status-badge status-${newClass}`;

    // Reset opacity and update tooltip
    badge.style.opacity = '1';
    badge.title = getStatusClickHint(newStatus);

    // Add update animation
    badge.style.animation = 'statusUpdate 0.5s ease';
    setTimeout(() => {
        badge.style.animation = '';
    }, 500);
}

// Add status update animation style
const statusAnimationStyle = document.createElement('style');
statusAnimationStyle.textContent = `
    @keyframes statusUpdate {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); background-color: rgba(76, 175, 80, 0.3); }
        100% { transform: scale(1); }
    }

    .status-badge {
        transition: transform 0.2s ease, opacity 0.3s ease;
    }
`;
document.head.appendChild(statusAnimationStyle);

// ========================================
// FILTER FUNCTIONALITY
// ========================================

function initializeFilters() {
    const applyFiltersBtn = document.querySelector('.apply-filters-btn');

    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', function() {
            applyFiltersWithReload();
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

    // Initialize clickable status badges
    initializeClickableStatus();
}

// ========================================
// CLICKABLE STATUS BADGES
// ========================================

let undoTimeout = null;
let lastStatusChange = null;





function showUndoNotification(patientId, oldStatus, newStatus) {
    // Clear existing undo timeout
    if (undoTimeout) {
        clearTimeout(undoTimeout);
    }

    const notification = document.createElement('div');
    notification.className = 'undo-notification';
    notification.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        background: #155724;
        color: white;
        border: 2px solid #0f4419;
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 4px 4px 0px rgba(0, 0, 0, 0.3);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        font-weight: 600;
        z-index: 9999;
        display: flex;
        align-items: center;
        gap: 1rem;
        animation: slideInRight 0.4s ease;
    `;

    notification.innerHTML = `
        <i class="fas fa-check-circle"></i>
        <span>Status changed to ${newStatus}</span>
        <button class="undo-btn" style="
            background: white;
            color: #155724;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 5px;
            cursor: pointer;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            font-weight: 600;
        ">
            <i class="fas fa-undo"></i> Undo
        </button>
    `;

    document.body.appendChild(notification);

    // Undo button handler
    notification.querySelector('.undo-btn').addEventListener('click', () => {
        undoStatusChange(patientId, oldStatus);
        notification.remove();
    });

    // Auto-remove after 5 seconds
    undoTimeout = setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.4s ease';
        setTimeout(() => notification.remove(), 400);
        lastStatusChange = null;
    }, 5000);
}

function undoStatusChange(patientId, oldStatus) {
    if (!lastStatusChange || lastStatusChange.patientId !== patientId) return;

    const badge = lastStatusChange.badge;
    badge.textContent = 'Reverting...';
    badge.style.opacity = '0.7';

    fetch('update-patient-status.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            patient_id: patientId,
            new_status: oldStatus
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            badge.textContent = oldStatus;
            badge.className = 'status-badge status-' + oldStatus.replace('-', '');
            badge.style.opacity = '1';
            showNotification('Status reverted successfully', 'success');
            lastStatusChange = null;
        } else {
            badge.style.opacity = '1';
            showNotification('Failed to revert status', 'error');
        }
    })
    .catch(error => {
        badge.style.opacity = '1';
        showNotification('Error reverting status', 'error');
        console.error('Error:', error);
    });
}

function applyFiltersWithReload() {
    const dateRange = document.getElementById('dateRangeFilter').value;
    const instructed = document.getElementById('instructedFilter').value;

    // Get checked patient statuses
    const statusCheckboxes = document.querySelectorAll('.checkbox-group input[type="checkbox"]:checked');
    const statuses = Array.from(statusCheckboxes).map(cb => cb.value);

    // Show loading animation
    const applyBtn = document.querySelector('.apply-filters-btn');
    applyBtn.textContent = 'Applying Filters...';
    applyBtn.disabled = true;
    applyBtn.style.opacity = '0.7';

    // Build URL with query parameters
    const params = new URLSearchParams();
    params.set('date_range', dateRange);
    if (instructed) {
        params.set('instructed', instructed);
    }
    // Add patient statuses
    if (statuses.length > 0 && !statuses.includes('all')) {
        params.set('statuses', statuses.join(','));
    }

    // Add a slight delay for animation effect
    setTimeout(() => {
        window.location.href = 'adm-patient-list.php?' + params.toString();
    }, 300);
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

    let bgColor, textColor, borderColor;
    switch(type) {
        case 'success':
            bgColor = '#d4edda';
            textColor = '#155724';
            borderColor = '#155724';
            break;
        case 'error':
            bgColor = '#f8d7da';
            textColor = '#721c24';
            borderColor = '#721c24';
            break;
        default:
            bgColor = '#d1ecf1';
            textColor = '#0c5460';
            borderColor = '#0c5460';
    }

    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        background: ${bgColor};
        color: ${textColor};
        border: 2px solid ${borderColor};
        padding: 1rem 1.5rem;
        border-radius: 8px;
        box-shadow: 4px 4px 0px rgba(0, 0, 0, 0.2);
        font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
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

