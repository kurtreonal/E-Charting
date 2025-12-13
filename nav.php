<?php
// nav.php
include 'connection.php';
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
            <a href="./patient-profile.php" class="nav-item">Profile</a>

            <!-- Patient notification wrapper -->
            <div class="nav-item notification-wrapper" style="position:relative;border:none;padding:0;">
                <button id="patientNotifToggle" aria-expanded="false" aria-controls="patientNotifBox" style="background:transparent;border:0;padding:0;cursor:pointer;position:relative;">
                    <img src="./Assets/notification.svg" alt="Notifications" class="notification-icon">
                    <span id="patientNotifDot" style="position:absolute; top:0px; right:0px; width:8px; height:8px; background:#e74c3c; border-radius:50%; display:none;"></span>
                </button>

                <div id="patientNotifBox" class="notification-box" aria-hidden="true" style="
                        position: absolute;
                        top: calc(100% + 8px);
                        right: 0;
                        width: 490px;
                        max-width: 90vw;
                        background-color: #d8cfc5;
                        border: 1px solid rgba(66, 63, 62, 0.08);
                        border-radius: 8px;
                        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
                        padding: 6px;
                        font-size: 13px;
                        z-index: 1500;
                    ">
                    <div class="notification-inner" style="padding:10px;font-size:13px;color:var(--primary,#423f3e);background-color: #d8cfc5;box-shadow:0 6px 18px rgba(0,0,0,0.08);max-height: 30rem;overflow-y: scroll;-ms-overflow-style: none; /* IE and Edge */scrollbar-width: none; /* Firefox */">
                        <div id="patientNotifList">
                            <div class="scrollable-container">
                                <div class="notification-item">
                                    <div class="notification-item-text">Loading notifications...</div>
                                </div>
                            </div>
                        </div>
                        <div id="patientNotifFooter" style="padding-top:8px;border-top:1px solid #eee; text-align:right; display:none;">
                            <button id="patientMarkAllRead" style="font-size:12px;padding:6px 8px;border-radius:6px;border:1px solid rgba(0,0,0,0.06);background:#f3f3f3;cursor:pointer;">Mark all as read</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </nav>

<script>
document.addEventListener('DOMContentLoaded', function(){
    // quick loader for jQuery if you prefer to rely on it - but we will use fetch API here (no external dependency)
    const toggle = document.getElementById('patientNotifToggle');
    const box = document.getElementById('patientNotifBox');
    const list = document.getElementById('patientNotifList');
    const dot = document.getElementById('patientNotifDot');
    const footer = document.getElementById('patientNotifFooter');
    const markAllBtn = document.getElementById('patientMarkAllRead');

    if (!toggle) return;

    // render notifications into list element
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
            const isDeclined = n.message_status === 'declined';
            const isAppointment = n.appointment_id && Number(n.appointment_id) > 0;


            const item = document.createElement('div');
            item.className = 'notification-item';
            item.style.fontWeight = isUnread ? '700' : '400';
            item.style.marginBottom = '10px';
            item.style.paddingBottom = '10px';
            item.style.textAlign = 'left';

            const text = document.createElement('div');
            text.className = 'notification-item-text';
            text.style.fontWeight = 'inherit';
            text.textContent = (n.title ? n.title + ': ' : '') + (n.message || '');

            const meta = document.createElement('div');
            meta.className = 'notification-meta';
            meta.style.fontSize = '12px';
            meta.style.color = 'rgba(66,63,62,0.6)';
            meta.style.marginTop = '6px';
            meta.style.display = 'flex';
            meta.style.justifyContent = 'space-between';
            meta.style.alignItems = 'center';

            const left = document.createElement('div');
            left.textContent = new Date(n.created_date).toLocaleString();

            const right = document.createElement('div');

                if (isAppointment && isDeclined) {
                const span = document.createElement('span');
                span.style.color = '#e74c3c';
                span.style.fontWeight = '700';
                span.textContent = 'Declined';
                right.appendChild(span);
            } else if (isAppointment && !isConfirmed) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'confirm-btn';
                btn.dataset.id = n.notification_id;
                btn.textContent = 'Confirm';
                btn.style.fontSize = '12px';
                btn.style.padding = '4px 6px';
                btn.style.borderRadius = '4px';
                btn.style.background = '#3498db';
                btn.style.color = '#fff';
                btn.style.border = 'none';
                btn.style.cursor = 'pointer';
                right.appendChild(btn);

                const decl = document.createElement('button');
                decl.type = 'button';
                decl.className = 'decline-btn';
                decl.dataset.id = n.notification_id;
                decl.textContent = 'Decline';
                decl.style.fontSize = '12px';
                decl.style.padding = '4px 6px';
                decl.style.borderRadius = '4px';
                decl.style.background = '#e74c3c';
                decl.style.color = '#fff';
                decl.style.border = 'none';
                decl.style.cursor = 'pointer';
                decl.style.marginLeft = '8px';
                right.appendChild(decl);
            } else if (isAppointment && isConfirmed) {
                const span = document.createElement('span');
                span.style.color = '#2ecc71';
                span.style.fontWeight = '700';
                span.textContent = 'Confirmed';
                right.appendChild(span);
            }

            meta.appendChild(left);
            meta.appendChild(right);

            item.appendChild(text);
            item.appendChild(meta);
            list.appendChild(item);
        });

        // show mark all read if any unread
        if (data.unread_count && data.unread_count > 0) {
            footer.style.display = 'block';
            dot.style.display = 'block';
        } else {
            footer.style.display = 'none';
            dot.style.display = 'none';
        }
    }

    // fetch function
    async function fetchNotifications(showLoader = true) {
        if (showLoader) {
            list.innerHTML = '<div class="notification-item"><div class="notification-item-text">Loading notifications...</div></div>';
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

    // open/close box
    function openBox() {
        fetchNotifications(true);
        box.style.display = 'block';
        toggle.setAttribute('aria-expanded','true');
    }
    function closeBox() {
        box.style.display = 'none';
        toggle.setAttribute('aria-expanded','false');
    }

    toggle.addEventListener('click', function(e){
        e.preventDefault();
        if (box.style.display === 'block') closeBox(); else openBox();
    });

    // click outside closes
    document.addEventListener('click', function(e){
        if (!e.target.closest('.notification-wrapper') && !e.target.closest('#patientNotifBox') && !e.target.closest('#patientNotifToggle')) {
            closeBox();
        }
    });
    // delegate confirm + decline buttons
    list.addEventListener('click', async function(e){
        // Confirm
        if (e.target && e.target.matches('.confirm-btn')) {
            const btn = e.target;
            const notifId = btn.dataset.id;
            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = '...';
            try {
                const res = await fetch('confirm_notification.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ notification_id: notifId })
                });
                const data = await res.json();
                if (data && data.success) {
                    fetchNotifications(false);
                } else {
                    alert('Error confirming: ' + (data.error || 'Unknown'));
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            } catch (err) {
                alert('Server error while confirming.');
                btn.disabled = false;
                btn.textContent = originalText;
            }
        }

        // Decline
        if (e.target && e.target.matches('.decline-btn')) {
            const btn = e.target;
            const notifId = btn.dataset.id;
            if (!confirm('Decline this appointment? This action cannot be undone.')) return;
            btn.disabled = true;
            const originalText = btn.textContent;
            btn.textContent = '...';
try {
                const res = await fetch('decline_notification.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {'Content-Type':'application/json'},
                    body: JSON.stringify({ notification_id: notifId })
                });

                // --- ADDED DEBUGGING LOGS HERE ---
                if (!res.ok) {
                    console.error('HTTP Error Status:', res.status);
                    const errorText = await res.text();
                    console.error('Raw Server Response:', errorText);
                    throw new Error('Server returned non-OK status: ' + res.status);
                }
                // --- END DEBUGGING LOGS ---

                const data = await res.json();

                if (data && data.success) {
                    // Success path
                    console.log('Decline successful:', data); // Log success response
                    fetchNotifications(false);
                } else {
                    // Failure path (Success: false)
                    const errMsg = data.error || 'Unknown server error (success: false)';
                    console.error('Server error response:', errMsg); // Log the specific error
                    alert('Error declining: ' + errMsg);
                    btn.disabled = false;
                    btn.textContent = originalText;
                }
            } catch (err) {
                console.error('Fetch or JSON parse error:', err); // Log the technical error
                alert('Server error while declining. Check console for details.');
                btn.disabled = false;
                btn.textContent = originalText;
            }
        }
    });


    // mark all read
    markAllBtn.addEventListener('click', async function(e){
        markAllBtn.disabled = true;
        markAllBtn.textContent = 'Reading...';
        try {
            const res = await fetch('clear_notification.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({ mark_all_read: 1 })
            });
            const data = await res.json();
            if (data && data.success) {
                fetchNotifications();
            } else {
                alert('Error marking read.');
                markAllBtn.disabled = false;
                markAllBtn.textContent = 'Mark all as read';
            }
        } catch (err) {
            alert('Server error.');
            markAllBtn.disabled = false;
            markAllBtn.textContent = 'Mark all as read';
        }
    });

    box.style.display = 'none'; // hide initially
    toggle.setAttribute('aria-expanded', 'false');

    // initial check (no loader)
    fetchNotifications(false);

    // optional: poll every 60s to update dot/unread count
    setInterval(function(){ fetch('fetch_notifications.php', { credentials: 'same-origin' })
    .then(res => res.json())
    .then(d => {
        if (d && d.unread_count && d.unread_count > 0) dot.style.display='block';
        else dot.style.display='none';
    }).catch(()=>{}); }, 60000);
});
</script>

    <script>
        // hamburger behavior (unchanged)
        document.addEventListener('DOMContentLoaded', () => {
            const hamburger = document.querySelector('.hamburger');
            const navLinks = document.querySelector('.nav-links');

            hamburger.addEventListener('click', () => {
                hamburger.classList.toggle('active');
                navLinks.classList.toggle('active');
            });

            document.querySelectorAll('.nav-item').forEach(n => n.addEventListener('click', () => {
                hamburger.classList.remove('active');
                navLinks.classList.remove('active');
            }));
        });
    </script>
</body>
</html>
