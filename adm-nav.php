<?php
include 'connection.php';
if (session_status() === PHP_SESSION_NONE) session_start();

$selected_patient_id = null;
if (!empty($_GET['patient_id'])) {
    $selected_patient_id = (int) $_GET['patient_id'];
} elseif (!empty($_POST['patient_id'])) {
    $selected_patient_id = (int) $_POST['patient_id'];
} elseif (!empty($_SESSION['selected_patient_id'])) {
    $selected_patient_id = (int) $_SESSION['selected_patient_id'];
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
        <a href="#" class="nav-item">Profile</a>

        <div class="nav-item notification-wrapper">
            <button type="button" id="notificationToggle" class="notification-btn" aria-expanded="false" aria-controls="notificationBox">
                <img src="./Assets/notification.svg" alt="Notifications" class="notification-icon">
                <span id="notifDot" class="notif-dot" aria-hidden="true"></span>
            </button>

            <div id="notificationBox" class="notification-box" aria-hidden="true">
                <div class="notification-inner">
                    <div id="notificationList">
                        <div class="notification-item">
                            <div class="notification-item-text">Loading notifications...</div>
                        </div>
                    </div>

                    <div id="notificationFooter" class="notification-footer" style="display:none;">
                        <button id="markAllBtn" class="mark-all-btn">Mark all as read</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const toggle = document.getElementById('notificationToggle');
    const box = document.getElementById('notificationBox');
    const list = document.getElementById('notificationList');
    const dot = document.getElementById('notifDot');
    const footer = document.getElementById('notificationFooter');
    const markAllBtn = document.getElementById('markAllBtn');
    const selectedPatientId = <?php echo $selected_patient_id !== null ? (int)$selected_patient_id : 'null'; ?>;

    if (!toggle) return;

    function render(data) {
        list.innerHTML = '';
        if (!data || !Array.isArray(data.notifications) || data.notifications.length === 0) {
            list.innerHTML = '<div class="notification-item"><div class="notification-item-text">No new notifications</div></div>';
            dot.style.display = 'none';
            footer.style.display = 'none';
            return;
        }

        data.notifications.forEach(function(n){
            const isUnread = Number(n.is_read) === 0;
            const isConfirmed = Number(n.is_confirmed) === 1;
            const isAppointment = n.appointment_id && Number(n.appointment_id) > 0;

            const item = document.createElement('div');
            item.className = 'notification-item';
            if (isUnread) item.classList.add('unread');
            item.style.fontWeight = isUnread ? '700' : '400';

            const text = document.createElement('div');
            text.className = 'notification-item-text';
            text.textContent = (n.title ? n.title + ': ' : '') + (n.message || '');

            const meta = document.createElement('div');
            meta.className = 'notification-meta';
            meta.style.display = 'flex';
            meta.style.justifyContent = 'space-between';
            meta.style.fontSize = '0.85em';
            meta.style.color = '#666';
            meta.style.marginTop = '5px';

            const left = document.createElement('div');
            try {
                left.textContent = new Date(n.created_date).toLocaleString();
            } catch (e) {
                left.textContent = n.created_date || '';
            }

            const right = document.createElement('div');

            //show appointment status label (nurse view)
            if (isAppointment) {
                const span = document.createElement('span');
                span.style.fontWeight = 'bold';
                span.style.fontSize = '13px';

                if (n.message_status === 'declined') {
                    span.className = 'declined-label';
                    span.style.color = '#e74c3c';
                    span.textContent = 'Declined';
                } else if (isConfirmed) {
                    span.className = 'confirmed-label';
                    span.style.color = '#2ecc71';
                    span.textContent = 'Confirmed';
                } else {
                    span.className = 'pending-label';
                    span.style.color = '#f39c12';
                    span.textContent = 'Pending Patient Confirmation';
                }
                right.appendChild(span);
            }

            meta.appendChild(left);
            meta.appendChild(right);

            item.appendChild(text);
            item.appendChild(meta);
            list.appendChild(item);
        });

        // show footer/dot if unread
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
            list.innerHTML = '<div class="notification-item"><div class="notification-item-text">Loading notifications...</div></div>';
        }
        try {
            let url = 'fetch_notifications.php';
            if (selectedPatientId !== null) {
                url += '?patient_id=' + encodeURIComponent(selectedPatientId);
            }

            const res = await fetch(url, { credentials: 'same-origin' });
            const data = await res.json();
            if (data && data.success) {
                render(data);
            } else {
                list.innerHTML = '<div class="notification-error" style="padding:10px;color:red;">Unable to load notifications.</div>';
                dot.style.display = 'none';
                footer.style.display = 'none';
            }
        } catch (err) {
            list.innerHTML = '<div class="notification-error" style="padding:10px;color:red;">Server error.</div>';
            dot.style.display = 'none';
            footer.style.display = 'none';
        }
    }

    function openBox() {
        fetchNotifications(true);
        box.classList.add('open');
        box.style.display = 'block';
        toggle.setAttribute('aria-expanded','true');
        box.setAttribute('aria-hidden','false');
    }
    function closeBox() {
        box.classList.remove('open');
        box.style.display = 'none';
        toggle.setAttribute('aria-expanded','false');
        box.setAttribute('aria-hidden','true');
    }

    toggle.addEventListener('click', function(e){
        e.preventDefault();
        if (box.style.display === 'block' || box.classList.contains('open')) {
            closeBox();
        } else {
            openBox();
        }
    });

    // click outside closes
    document.addEventListener('click', function(e){
        if (!e.target.closest('.notification-wrapper') && !e.target.closest('#notificationBox') && !e.target.closest('#notificationToggle')) {
            closeBox();
        }
    });

    // mark all read
    markAllBtn.addEventListener('click', async function(e){
        markAllBtn.disabled = true;
        markAllBtn.textContent = 'Reading...';
        try {
            let url = 'clear_notification.php';
            const body = { mark_all_read: 1 };
            if (selectedPatientId !== null) body.patient_id = selectedPatientId;

            const res = await fetch(url, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify(body)
            });
            const data = await res.json();
            if (data && data.success) {
                fetchNotifications();
            } else {
                alert('Error marking read.');
            }
        } catch (err) {
            alert('Server error.');
        } finally {
            markAllBtn.disabled = false;
            markAllBtn.textContent = 'Mark all as read';
        }
    });

    // initial fetch
    fetchNotifications(false);

    setInterval(function(){
        let url = 'fetch_notifications.php';
        if (selectedPatientId !== null) url += '?patient_id=' + encodeURIComponent(selectedPatientId);
        fetch(url, { credentials: 'same-origin' })
            .then(r => r.json())
            .then(d => {
                if (d && d.unread_count && d.unread_count > 0) dot.style.display = 'block';
                else dot.style.display = 'none';
            })
            .catch(()=>{});
    }, 60000);
});
</script>
</body>
</html>