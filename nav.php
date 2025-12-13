<?php

if (session_status() === PHP_SESSION_NONE) {
    session_name('patient_session');
    session_start();
}

$is_logged_in = !empty($_SESSION['patient_id']);
$patient_id = $is_logged_in ? (int)$_SESSION['patient_id'] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="./Styles/nav.css">
    <script src="./Javascript/javascript.js" defer></script>
</head>
<body>
    <nav class="navbar">
        <a href="./landingpage.php">
            <div class="nav-logo">
                <img src="./Assets/logo.svg" alt="Hospital Logo" class="logo">
            </div>
        </a>

        <div class="hamburger">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </div>

        <div class="nav-links">
            <a href="./landingpage.php" class="nav-item">Home</a>
            <a href="#" class="nav-item">Services</a>
            <a href="#" class="nav-item">About Us</a>
            <a href="#" class="nav-item">Contact Us</a>

            <?php if ($is_logged_in): ?>
                <a href="./patient-profile.php" class="nav-item">Profile</a>
                <div class="nav-item notification-wrapper" style="position:relative;border:none;padding:0;">
                    <button id="patientNotifToggle"
                            aria-expanded="false"
                            aria-controls="patientNotifBox"
                            style="background:transparent;border:0;padding:0;cursor:pointer;position:relative;">
                        <img src="./Assets/notification.svg" alt="Notifications" class="notification-icon">
                        <span id="patientNotifDot"
                              style="position:absolute;top:0;right:0;width:8px;height:8px;background:#e74c3c;border-radius:50%;display:none;"></span>
                    </button>

                    <div id="patientNotifBox"
                         class="notification-box"
                         aria-hidden="true"
                         style="position:absolute;top:calc(100% + 8px);right:0;width:490px;max-width:90vw;background-color:#d8cfc5;border:1px solid rgba(66,63,62,0.08);border-radius:8px;box-shadow:0 8px 20px rgba(0,0,0,0.06);padding:6px;font-size:13px;z-index:1500;display:none;">

                        <div class="notification-inner"
                             style="padding:10px;font-size:13px;color:var(--primary,#423f3e);background-color:#d8cfc5;box-shadow:0 6px 18px rgba(0,0,0,0.08);max-height:30rem;overflow-y:auto;scrollbar-width:none;-ms-overflow-style:none;">

                            <div id="patientNotifList">
                                <div class="notification-item">
                                    <div class="notification-item-text">Loading notifications...</div>
                                </div>
                            </div>

                            <div id="patientNotifFooter"
                                 style="padding-top:8px;border-top:1px solid #eee;text-align:right;display:none;">
                                <button id="patientMarkAllRead"
                                        style="font-size:12px;padding:6px 8px;border-radius:6px;border:1px solid rgba(0,0,0,0.06);background:#f3f3f3;cursor:pointer;">
                                    Mark all as read
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <a href="./login.php" class="nav-item">Login</a>
            <?php endif; ?>
        </div>
    </nav>

<?php if ($is_logged_in): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggle = document.getElementById('patientNotifToggle');
    const box = document.getElementById('patientNotifBox');
    const list = document.getElementById('patientNotifList');
    const dot = document.getElementById('patientNotifDot');
    const footer = document.getElementById('patientNotifFooter');
    const markAllBtn = document.getElementById('patientMarkAllRead');

    if (!toggle) return;
    function render(data) {
        list.innerHTML = '';

        if (!data || !Array.isArray(data.notifications) || data.notifications.length === 0) {
            list.innerHTML = '<div class="notification-item"><div class="notification-item-text">No new notifications</div></div>';
            dot.style.display = 'none';
            footer.style.display = 'none';
            return;
        }

        data.notifications.forEach(function(n) {
            const isUnread = Number(n.is_read) === 0;
            const isConfirmed = Number(n.is_confirmed) === 1;
            const isDeclined = n.message_status === 'declined';
            const isAppointment = n.appointment_id && Number(n.appointment_id) > 0;
            const isMedication = n.medication_id && Number(n.medication_id) > 0;

            const item = document.createElement('div');
            item.className = 'notification-item';
            item.style.fontWeight = isUnread ? '700' : '400';
            item.style.marginBottom = '10px';
            item.style.paddingBottom = '10px';
            item.style.borderBottom = '1px solid rgba(0,0,0,0.05)';

            const text = document.createElement('div');
            text.className = 'notification-item-text';
            text.textContent = (n.title ? n.title + ': ' : '') + (n.message || '');

            const meta = document.createElement('div');
            meta.style.fontSize = '12px';
            meta.style.color = 'rgba(66,63,62,0.6)';
            meta.style.marginTop = '6px';
            meta.style.display = 'flex';
            meta.style.justifyContent = 'space-between';
            meta.style.alignItems = 'center';

            const left = document.createElement('div');
            try {
                left.textContent = new Date(n.created_date).toLocaleString();
            } catch (e) {
                left.textContent = n.created_date || '';
            }

            const right = document.createElement('div');

            if (isAppointment && isDeclined) {
                const span = document.createElement('span');
                span.style.color = '#e74c3c';
                span.style.fontWeight = '700';
                span.textContent = 'Declined';
                right.appendChild(span);
            } else if (isAppointment && !isConfirmed) {
                const confirmBtn = document.createElement('button');
                confirmBtn.type = 'button';
                confirmBtn.className = 'confirm-btn';
                confirmBtn.dataset.id = n.notification_id;
                confirmBtn.textContent = 'Confirm';
                confirmBtn.style.cssText = 'font-size:12px;padding:4px 8px;border-radius:4px;background:#3498db;color:#fff;border:none;cursor:pointer;margin-right:4px;';
                right.appendChild(confirmBtn);

                const declineBtn = document.createElement('button');
                declineBtn.type = 'button';
                declineBtn.className = 'decline-btn';
                declineBtn.dataset.id = n.notification_id;
                declineBtn.textContent = 'Decline';
                declineBtn.style.cssText = 'font-size:12px;padding:4px 8px;border-radius:4px;background:#e74c3c;color:#fff;border:none;cursor:pointer;';
                right.appendChild(declineBtn);
            } else if (isAppointment && isConfirmed) {
                const span = document.createElement('span');
                span.style.color = '#2ecc71';
                span.style.fontWeight = '700';
                span.textContent = 'Confirmed';
                right.appendChild(span);
            }
            if (isMedication && isUnread) {
                const markReadBtn = document.createElement('button');
                markReadBtn.type = 'button';
                markReadBtn.className = 'mark-read-btn';
                markReadBtn.dataset.id = n.notification_id;
                markReadBtn.textContent = 'Mark as Taken';
                markReadBtn.style.cssText = 'font-size:12px;padding:4px 8px;border-radius:4px;background:#27ae60;color:#fff;border:none;cursor:pointer;';
                right.appendChild(markReadBtn);
            }

            meta.appendChild(left);
            meta.appendChild(right);

            item.appendChild(text);
            item.appendChild(meta);
            list.appendChild(item);
        });

        if (data.unread_count && data.unread_count > 0) {
            footer.style.display = 'block';
            dot.style.display = 'block';
        } else {
            footer.style.display = 'none';
            dot.style.display = 'none';
        }
    }
    async function fetchNotifications(showLoader = true) {
        if (showLoader) {
            list.innerHTML = '<div class="notification-item"><div class="notification-item-text">Loading...</div></div>';
        }

        try {
            const res = await fetch('fetch_notifications.php', { credentials: 'same-origin' });
            const data = await res.json();

            if (data && data.success) {
                render(data);
            } else {
                list.innerHTML = '<div style="color:#e74c3c;padding:8px;">Unable to load notifications.</div>';
                dot.style.display = 'none';
            }
        } catch (err) {
            list.innerHTML = '<div style="color:#e74c3c;padding:8px;">Server error.</div>';
            dot.style.display = 'none';
        }
    }
    function openBox() {
        fetchNotifications(true);
        box.style.display = 'block';
        toggle.setAttribute('aria-expanded', 'true');
    }

    function closeBox() {
        box.style.display = 'none';
        toggle.setAttribute('aria-expanded', 'false');
    }
    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        if (box.style.display === 'block') {
            closeBox();
        } else {
            openBox();
        }
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('.notification-wrapper')) {
            closeBox();
        }
    });

    list.addEventListener('click', async function(e) {
        const target = e.target;
        if (target.matches('.confirm-btn')) {
            const btn = target;
            const notifId = btn.dataset.id;
            btn.disabled = true;
            btn.textContent = '...';

            try {
                const res = await fetch('confirm_notification.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notification_id: notifId })
                });

                const data = await res.json();

                if (data && data.success) {
                    fetchNotifications(false);
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                    btn.disabled = false;
                    btn.textContent = 'Confirm';
                }
            } catch (err) {
                alert('Server error while confirming');
                btn.disabled = false;
                btn.textContent = 'Confirm';
            }
        }
        if (target.matches('.decline-btn')) {
            if (!confirm('Decline this appointment?')) return;

            const btn = target;
            const notifId = btn.dataset.id;
            btn.disabled = true;
            btn.textContent = '...';

            try {
                const res = await fetch('decline_notification.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ notification_id: notifId })
                });

                const data = await res.json();

                if (data && data.success) {
                    fetchNotifications(false);
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                    btn.disabled = false;
                    btn.textContent = 'Decline';
                }
            } catch (err) {
                alert('Server error while declining');
                btn.disabled = false;
                btn.textContent = 'Decline';
            }
        }
        if (target.matches('.mark-read-btn')) {
            const btn = target;
            const notifId = btn.dataset.id;
            btn.disabled = true;
            btn.textContent = '...';

            try {
                const res = await fetch('clear_notification.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        notification_id: notifId,
                        mark_read: 1
                    })
                });

                const data = await res.json();

                if (data && data.success) {
                    fetchNotifications(false);
                } else {
                    alert('Error: ' + (data.error || 'Unknown error'));
                    btn.disabled = false;
                    btn.textContent = 'Mark as Taken';
                }
            } catch (err) {
                alert('Server error');
                btn.disabled = false;
                btn.textContent = 'Mark as Taken';
            }
        }
    });
    markAllBtn.addEventListener('click', async function() {
        markAllBtn.disabled = true;
        markAllBtn.textContent = 'Reading...';

        try {
            const res = await fetch('clear_notification.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ mark_all_read: 1 })
            });

            const data = await res.json();

            if (data && data.success) {
                fetchNotifications(false);
            } else {
                alert('Error marking as read');
            }
        } catch (err) {
            alert('Server error');
        } finally {
            markAllBtn.disabled = false;
            markAllBtn.textContent = 'Mark all as read';
        }
    });

    fetchNotifications(false);

    //poll every 60 seconds
    setInterval(function() {
        fetch('fetch_notifications.php', { credentials: 'same-origin' })
            .then(r => r.json())
            .then(d => {
                if (d && d.unread_count && d.unread_count > 0) {
                    dot.style.display = 'block';
                } else {
                    dot.style.display = 'none';
                }
            })
            .catch(() => {});
    }, 60000);
});
</script>
<?php endif; ?>

<script>
// Hamburger menu (always active)
document.addEventListener('DOMContentLoaded', () => {
    const hamburger = document.querySelector('.hamburger');
    const navLinks = document.querySelector('.nav-links');

    if (hamburger && navLinks) {
        hamburger.addEventListener('click', () => {
            hamburger.classList.toggle('active');
            navLinks.classList.toggle('active');
        });

        document.querySelectorAll('.nav-item').forEach(n => {
            n.addEventListener('click', () => {
                hamburger.classList.remove('active');
                navLinks.classList.remove('active');
            });
        });
    }
});
</script>

</body>
</html>