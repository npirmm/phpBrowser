<?php
$data = json_decode(file_get_contents('php://input'), true);
$folder = $data['folder'];
$oldFile = $data['oldFile'];
$newFile = $data['newFile'];

$oldFilePath = "$folder/$oldFile";
$newFilePath = "$folder/$newFile";

// Check if the old file exists
if (!file_exists($oldFilePath)) {
    echo "File '$oldFile' does not exist.";
    exit;
}

// Check if the new file already exists
if (file_exists($newFilePath)) {
    echo "A file with the name '$newFile' already exists.";
    exit;
}

// Attempt to rename the file
if (rename($oldFilePath, $newFilePath)) {
    echo "File renamed successfully.";
} else {
    echo "Error renaming file.";
}
?> 