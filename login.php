<?php
session_start();

// Define your credentials (for demonstration)
$valid_user = ''; //Assign a login username here
$valid_password = ''; //Assign a login password here
                // Else, another thing can be done if it's being set up for large scale, to connect it with a database and fetch from there

// Handle login via browser prompt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    if ($username === $valid_user && $password === $valid_password) {
        $_SESSION['loggedin'] = true;
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Invalid login details']);
    }
    exit;
}

// Redirect to index if already logged in
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin']) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetShare - Login</title>
</head>
<body>
    <script>
        function authenticate() {
            const username = prompt("Enter username:");
            const password = prompt("Enter password:");

            if (username && password) {
                fetch('login.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        'username': username,
                        'password': password
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        window.location.href = 'index.php'; // Redirect to index page on successful login
                    } else {
                        alert(data.message); // Show error message if login fails
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An unexpected error occurred.');
                });
            }
        }

        window.onload = authenticate;
    </script>
</body>
</html>
