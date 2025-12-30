<?php
    include 'connection.php';
    include_once 'includes/notification.php';

    if (session_status() === PHP_SESSION_NONE) {
        session_name('nurse_session');
        session_start();
    }

    $selected_patient_id = null;
    if (!empty($_GET['patient_id'])) {
        $selected_patient_id = (int) $_GET['patient_id'];
    } elseif (!empty($_POST['patient_id'])) {
        $selected_patient_id = (int) $_POST['patient_id'];
    } elseif (!empty($_SESSION['selected_patient_id'])) {
        $selected_patient_id = (int) $_SESSION['selected_patient_id'];
    }

    // Fetch notifications directly in PHP
    $notifications = [];
    $unread_count = 0;

    if (!empty($_SESSION['nurse_id'])) {
        $nurse_id = (int)$_SESSION['nurse_id'];

        // Generate medication notifications if viewing specific patient
        if ($selected_patient_id !== null) {
            generate_medication_notifications_for_patient($con, $selected_patient_id, 5);
        }

        // Fetch notifications
        $notifications = fetch_notifications_for_nurse($con, $nurse_id, $selected_patient_id, 50);
        $unread_count = count_unread_notifications($con, $nurse_id, 'nurse', $selected_patient_id);

        // Format notification messages
        foreach ($notifications as &$notif) {
            $notif['is_read'] = (int)$notif['is_read'];
            $notif['is_confirmed'] = (int)$notif['is_confirmed'];
            $notif['appointment_id'] = $notif['appointment_id'] ? (int)$notif['appointment_id'] : null;
            $notif['medication_id'] = $notif['medication_id'] ? (int)$notif['medication_id'] : null;

            // Format appointment messages
            if ($notif['appointment_id'] && !empty($notif['scheduled_date'])) {
                $datetime = date("F j, Y \a\\t g:i A", strtotime($notif['scheduled_date']));
                $notif['message'] = "Patient appointment on $datetime";
                $notif['title'] = 'Appointment';
            }

            // Format medication messages
            if ($notif['medication_id']) {
                if (empty($notif['message']) || strpos($notif['message'], 'Time to take') === false) {
                    $med_stmt = $con->prepare("SELECT medication_name, dose FROM medication WHERE medication_id = ? LIMIT 1");
                    if ($med_stmt) {
                        $med_stmt->bind_param('i', $notif['medication_id']);
                        $med_stmt->execute();
                        $med_result = $med_stmt->get_result();

                        if ($med_row = $med_result->fetch_assoc()) {
                            $scheduled = !empty($notif['scheduled_date']) ? $notif['scheduled_date'] : $notif['created_date'];
                            $time_str = date("g:i A", strtotime($scheduled));
                            $notif['message'] = "Time to take {$med_row['medication_name']} ({$med_row['dose']}) at {$time_str}";
                        }

                        $med_stmt->close();
                    }
                }

                if (empty($notif['title'])) {
                    $notif['title'] = 'Medication Reminder';
                }
            }
        }
        unset($notif);
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8" />
        <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
        <link rel="stylesheet" href="./Styles/adm-nav.css" />
        <script src="./Javascript/javascript.js" defer></script>
    </head>
    <body>
    <nav class="navbar">
        <a href="./adm-patient-list.php" class="nav-logo-link">
            <div class="nav-logo">
                <img src="./Assets/logo.svg" alt="Hospital Logo" class="logo">
            </div>
        </a>

        <div class="hamburger" aria-label="menu toggle" role="button" tabindex="0">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </div>

        <div class="nav-links">
            <a href="./adm-patient-list.php" class="nav-item">Patient</a>
            <a href="./analytics-dashboard.php" class="nav-item">Analytics</a>
            <a href="#" class="nav-item">Profile</a>

            <div class="nav-item notification-wrapper">
                <button type="button" id="notificationToggle" class="notification-btn" aria-expanded="false" aria-controls="notificationBox">
                    <img src="./Assets/notification.svg" alt="Notifications" class="notification-icon">
                    <?php if ($unread_count > 0): ?>
                    <span id="notifDot" class="notif-dot" aria-hidden="true"></span>
                    <?php endif; ?>
                </button>

                <div id="notificationBox" class="notification-box" aria-hidden="true" style="display: none;">
                    <div class="notification-inner">
                        <div id="notificationList">
                            <?php if (empty($notifications)): ?>
                            <div class="notification-item">
                                <div class="notification-item-text">No new notifications</div>
                            </div>
                            <?php else: ?>
                                <?php foreach ($notifications as $n): ?>
                                    <?php
                                    $isUnread = (int)$n['is_read'] === 0;
                                    $isConfirmed = (int)$n['is_confirmed'] === 1;
                                    $isAppointment = !empty($n['appointment_id']) && (int)$n['appointment_id'] > 0;
                                    ?>
                                    <div class="notification-item <?php echo $isUnread ? 'unread' : ''; ?>"
                                         style="font-weight: <?php echo $isUnread ? '700' : '400'; ?>;">
                                        <div class="notification-item-text">
                                            <?php echo htmlspecialchars(($n['title'] ? $n['title'] . ': ' : '') . ($n['message'] ?? '')); ?>
                                        </div>
                                        <div class="notification-meta" style="display: flex; justify-content: space-between; font-size: 0.85em; color: #666; margin-top: 5px;">
                                            <div>
                                                <?php
                                                try {
                                                    echo htmlspecialchars(date('M j, Y g:i A', strtotime($n['created_date'])));
                                                } catch (Exception $e) {
                                                    echo htmlspecialchars($n['created_date'] ?? '');
                                                }
                                                ?>
                                            </div>
                                            <div>
                                                <?php if ($isAppointment): ?>
                                                    <?php if ($n['message_status'] === 'declined'): ?>
                                                        <span class="declined-label" style="font-weight: bold; font-size: 13px; color: #e74c3c;">Declined</span>
                                                    <?php elseif ($isConfirmed): ?>
                                                        <span class="confirmed-label" style="font-weight: bold; font-size: 13px; color: #2ecc71;">Confirmed</span>
                                                    <?php else: ?>
                                                        <span class="pending-label" style="font-weight: bold; font-size: 13px; color: #f39c12;">Pending Patient Confirmation</span>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>

                        <?php if ($unread_count > 0): ?>
                        <div id="notificationFooter" class="notification-footer">
                            <form method="POST" action="clear_notification.php" id="markAllForm" style="margin: 0;">
                                <input type="hidden" name="mark_all_read" value="1">
                                <?php if ($selected_patient_id !== null): ?>
                                <input type="hidden" name="patient_id" value="<?php echo $selected_patient_id; ?>">
                                <?php endif; ?>
                                <button type="submit" id="markAllBtn" class="mark-all-btn">Mark all as read</button>
                            </form>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <script>
    // Simple notification toggle - works on any page
    (function() {
        const toggle = document.getElementById('notificationToggle');
        const box = document.getElementById('notificationBox');

        if (!toggle || !box) return;

        function openBox() {
            box.style.display = 'block';
            box.classList.add('open');
            toggle.setAttribute('aria-expanded', 'true');
            box.setAttribute('aria-hidden', 'false');
        }

        function closeBox() {
            box.style.display = 'none';
            box.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
            box.setAttribute('aria-hidden', 'true');
        }

        // Toggle on button click
        toggle.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            if (box.style.display === 'block') {
                closeBox();
            } else {
                openBox();
            }
        });

        // Close when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.notification-wrapper')) {
                closeBox();
            }
        });

        // Handle mark all as read form
        const markAllForm = document.getElementById('markAllForm');
        if (markAllForm) {
            markAllForm.addEventListener('submit', function(e) {
                e.preventDefault();

                const markAllBtn = document.getElementById('markAllBtn');
                if (markAllBtn) {
                    markAllBtn.disabled = true;
                    markAllBtn.textContent = 'Reading...';
                }

                const formData = new FormData(this);
                const data = {};
                formData.forEach((value, key) => {
                    data[key] = value;
                });

                fetch('clear_notification.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify(data)
                })
                .then(r => r.json())
                .then(result => {
                    if (result && result.success) {
                        // Reload page to show updated notifications
                        window.location.reload();
                    } else {
                        alert('Error marking as read.');
                        if (markAllBtn) {
                            markAllBtn.disabled = false;
                            markAllBtn.textContent = 'Mark all as read';
                        }
                    }
                })
                .catch(err => {
                    console.error('Error:', err);
                    alert('Server error.');
                    if (markAllBtn) {
                        markAllBtn.disabled = false;
                        markAllBtn.textContent = 'Mark all as read';
                    }
                });
            });
        }
    })();
    </script>
    </body>
    </html>