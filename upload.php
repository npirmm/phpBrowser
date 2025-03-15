<?php
$currentDir = $_POST['folder'] ?? 'files'; // Get the current directory from the request
$uploadDir = $currentDir . '/';

if (!empty($_FILES['files']['name'][0])) {
    foreach ($_FILES['files']['tmp_name'] as $key => $tmpName) {
        $fileName = basename($_FILES['files']['name'][$key]);
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($tmpName, $filePath)) {
            echo "File '$fileName' uploaded successfully.\n";
        } else {
            echo "Failed to upload '$fileName'.\n";
        }
    }
} else {
    echo "No files selected for upload.";
}
?>