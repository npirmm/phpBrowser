<?php
$data = json_decode(file_get_contents('php://input'), true);
$folder = $data['folder'];
$directories = $data['directories'];

foreach ($directories as $directory) {
    $directoryPath = "$folder/$directory";

    if (!is_dir($directoryPath)) {
        echo "Directory '$directory' does not exist.";
        exit;
    }

    // Check if the directory is empty
    if (count(scandir($directoryPath)) > 2) { // 2 for . and ..
        echo "Directory '$directory' is not empty.";
        exit;
    }

    if (!rmdir($directoryPath)) {
        echo "Error deleting directory '$directory'.";
        exit;
    }
}

echo "Selected directories deleted successfully.";
?>