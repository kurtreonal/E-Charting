<?php
echo "<!DOCTYPE html><html><head><title>Clear Sessions</title></head><body>";
echo "<h2>Clearing All Sessions...</h2>";
echo "<pre>";

//list of all session cookie names
$session_names = ['patient_session', 'nurse_session', 'admin_session', 'PHPSESSID'];

//clear each session cookie
foreach ($session_names as $name) {
    if (isset($_COOKIE[$name])) {
        setcookie($name, '', time() - 3600, '/');
        setcookie($name, '', time() - 3600, '/', $_SERVER['HTTP_HOST']);
        echo "✓ Cleared cookie: $name\n";
    }
}

//destroy each session type
try {
    session_name('patient_session');
    @session_start();
    session_destroy();
    echo "✓ Destroyed patient_session\n";
} catch (Exception $e) {
    echo "○ patient_session: " . $e->getMessage() . "\n";
}

try {
    session_name('nurse_session');
    @session_start();
    session_destroy();
    echo "✓ Destroyed nurse_session\n";
} catch (Exception $e) {
    echo "○ nurse_session: " . $e->getMessage() . "\n";
}

try {
    @session_start();
    session_destroy();
    echo "✓ Destroyed default session\n";
} catch (Exception $e) {
    echo "○ default session: " . $e->getMessage() . "\n";
}

echo "</pre>";
echo "<hr>";
echo "<h3>All sessions cleared!</h3>";
echo "<p><strong>Next steps:</strong></p>";
echo "<ol>";
echo "<li>Close this browser window completely</li>";
echo "<li>Open a NEW incognito/private window</li>";
echo "<li><a href='login.php'>Go to Patient Login</a> or <a href='nurse-login.php'>Go to Nurse Login</a></li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='debugger.php'>Check current sessions</a></p>";
echo "</body></html>";
?>
