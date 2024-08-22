<?php
session_start();

// End the session and redirect to login page
if (isset($_SESSION['loggedin'])) {
    session_unset();
    session_destroy();
}

header('Location: login.php');
exit;
?>
