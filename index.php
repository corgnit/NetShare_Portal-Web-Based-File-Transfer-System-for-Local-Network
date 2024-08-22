<?php
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['loggedin'])) {
    header('Location: login.php');
    exit;
}

// Database connection
$servername = "localhost";
$username = "root"; // Change if necessary
$password = ""; // Change if necessary
$dbname = ""; // Change to your database name

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Directory where files are stored
$uploadDir = 'uploads/';

// Fetch files from database
$sql = "SELECT id, filename, upload_time FROM files ORDER BY upload_time DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $filePath = $uploadDir . basename($row['filename']);
        // Check if the file exists in the directory
        if (!file_exists($filePath)) {
            // Move the entry to missing_files table
            if ($stmt = $conn->prepare("INSERT INTO missing_files (filename, upload_time) VALUES (?, ?)")) {
                $stmt->bind_param("ss", $row['filename'], $row['upload_time']);
                $stmt->execute();
                $stmt->close();
            } else {
                echo "Error preparing statement: " . $conn->error;
            }

            // Remove the entry from files table
            if ($stmt = $conn->prepare("DELETE FROM files WHERE id = ?")) {
                $stmt->bind_param("i", $row['id']);
                $stmt->execute();
                $stmt->close();
            } else {
                echo "Error preparing statement: " . $conn->error;
            }
        }
    }
} else {
    echo "";
}

// Fetch updated files from database
$sql = "SELECT filename, upload_time FROM files ORDER BY upload_time DESC";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>NetShare</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
</head>
<body class="bg-white-100 font-sans leading-normal tracking-normal">
    <div class="container mx-auto p-8">
        <!-- Header -->
        <header class="mb-8 flex justify-between items-center">
            <h1 class="text-5xl font-extrabold text-gray-900">NetShare</h1>
            <a href="logout.php" class="text-red-500 hover:text-red-600 transition duration-150">
                <span class="material-symbols-outlined text-2xl">logout</span>
            </a>
        </header>

        <!-- Available Files Section -->
        <section class="mb-12">
            <h2 class="text-3xl font-semibold text-gray-800 mb-6">Available Files</h2>
            <div class="overflow-x-auto bg-white rounded-lg shadow-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-3 px-6 text-left text-gray-600">Filename</th>
                            <th class="py-3 px-6 text-left text-gray-600">Upload Time</th>
                            <th class="py-3 px-6 text-left text-gray-600">Action</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while($row = $result->fetch_assoc()): ?>
                                <?php
                                $filePath = $uploadDir . basename($row['filename']);
                                // Check if the file exists in the directory
                                if (file_exists($filePath)): ?>
                                    <tr>
                                        <td class="py-4 px-6 text-gray-800"><?php echo htmlspecialchars($row['filename']); ?></td>
                                        <td class="py-4 px-6 text-gray-600"><?php echo htmlspecialchars($row['upload_time']); ?></td>
                                        <td class="py-4 px-6">
                                            <a href="<?php echo htmlspecialchars($filePath); ?>" class="text-blue-500 hover:text-blue-700 transition duration-150" download>
                                                <span class="material-symbols-outlined text-xl">download</span>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="py-4 px-6 text-center text-gray-600">No files available</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>

 <!-- Upload Form Section -->
<section>
    <h2 class="text-3xl font-semibold text-gray-800 mb-6">Upload a New File</h2>
    <form id="uploadForm" action="upload.php" method="post" enctype="multipart/form-data" class="bg-white border border-gray-200 rounded-lg shadow-lg p-6">
        <div id="dropArea" class="border-2 border-dashed border-gray-300 rounded-lg p-4 mb-4 text-center cursor-pointer">
            <span class="text-gray-600">Drag and drop files here or click to select</span>
            <input type="file" name="fileToUpload[]" id="fileToUpload" class="hidden" multiple>
        </div>
        <ul id="fileList" class="mb-4 text-gray-700 list-disc pl-5"></ul>
        <button type="submit" class="bg-blue-500 text-white py-2 px-4 rounded-lg hover:bg-blue-600 transition duration-150">Upload</button>
    </form>
</section>

<script>
    document.getElementById('uploadForm').addEventListener('submit', function(event) {
        event.preventDefault(); // Prevent the default form submission
        
        const formData = new FormData(this);
        fetch('upload.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.messages) {
                data.messages.forEach(message => {
                    alert(message); // Show each message in an alert box
                });
            } else if (data.error) {
                alert(data.error); // Show error if there's a connection issue
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An unexpected error occurred.');
        });
    });

                // Handle drag-and-drop events
                const dropArea = document.getElementById('dropArea');
            const fileInput = document.getElementById('fileToUpload');
            const fileList = document.getElementById('fileList');

            // Handle file selection
            function handleFiles(files) {
                fileList.innerHTML = ''; // Clear the file list
                for (const file of files) {
                    const listItem = document.createElement('li');
                    listItem.textContent = file.name;
                    fileList.appendChild(listItem);
                }
            }

            dropArea.addEventListener('click', () => fileInput.click());

            dropArea.addEventListener('dragover', (event) => {
                event.preventDefault();
                dropArea.classList.add('bg-gray-100');
            });

            dropArea.addEventListener('dragleave', () => {
                dropArea.classList.remove('bg-gray-100');
            });

            dropArea.addEventListener('drop', (event) => {
                event.preventDefault();
                dropArea.classList.remove('bg-gray-100');
                const files = event.dataTransfer.files;
                fileInput.files = files; // Assign dropped files to the input
                handleFiles(files); // Show the list of files
            });

            // Handle file input change
            fileInput.addEventListener('change', () => {
                handleFiles(fileInput.files); // Show the list of files
            });
</script>

    </div>
</body>
</html>

<?php $conn->close(); ?>


