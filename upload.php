<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}

$target_dir = "uploads/";
$uploadOk = 1;
$response = array();

// Database connection
$servername = "localhost";
$username = "root"; // Change if necessary
$password = ""; // Change if necessary
$dbname = ""; // Change to your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    $response['error'] = "Connection failed: " . $conn->connect_error;
    echo json_encode($response);
    exit;
}

// Loop through each file
foreach ($_FILES["fileToUpload"]["name"] as $key => $filename) {
    $target_file = $target_dir . basename($filename);
    $fileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $uploadOk = 1;

    // Check if file already exists
    if (file_exists($target_file)) {
        $response['messages'][] = "Sorry, file " . htmlspecialchars($filename) . " already exists.";
        $uploadOk = 0;
    }

    // Allow certain file formats (optional)
    $allowed_types = array("jpg", "png", "jpeg", "gif", "pdf", "doc", "docx", "txt", "mp4", "zip", "rar", "py");
    if (!in_array($fileType, $allowed_types)) {
        $response['messages'][] = "Sorry, only allowed file formats are JPG, JPEG, PNG, GIF, PDF, DOC, DOCX, TXT, MP4, ZIP, and RAR for file " . htmlspecialchars($filename) . ".";
        $uploadOk = 0;
    }

    // Check if $uploadOk is set to 0 by an error
    if ($uploadOk == 0) {
        $response['messages'][] = "Sorry, your file " . htmlspecialchars($filename) . " was not uploaded.";
    } else {
        // Move the uploaded file
        if (move_uploaded_file($_FILES["fileToUpload"]["tmp_name"][$key], $target_file)) {
            $upload_time = date("Y-m-d H:i:s");

            // Insert file info into the database
            $stmt = $conn->prepare("INSERT INTO files (filename, upload_time) VALUES (?, ?)");
            if ($stmt) {
                $stmt->bind_param("ss", $target_file, $upload_time);
                $stmt->execute();
                $stmt->close();
                $response['messages'][] = "The file " . htmlspecialchars($filename) . " has been uploaded.";
            } else {
                $response['messages'][] = "Error preparing statement: " . $conn->error;
            }
        } else {
            $response['messages'][] = "Sorry, there was an error uploading your file " . htmlspecialchars($filename) . ".";
        }
    }
}

$conn->close();
echo json_encode($response);
?>
