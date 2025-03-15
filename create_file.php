<?php
$data = json_decode(file_get_contents('php://input'), true);
$folder = $data['folder'];
$file = $data['file'];
$filePath = "$folder/$file";

// Check if the folder exists
if (!is_dir($folder)) {
    echo "Folder does not exist.";
    exit;
}

// Check if the file already exists
if (file_exists($filePath)) {
    echo "File already exists.";
    exit;
}

// Attempt to create the file
if (file_put_contents($filePath, '') !== false) {
    echo "File created successfully.";
} else {
    echo "Error creating file.";
}
?>