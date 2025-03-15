<?php
$data = json_decode(file_get_contents('php://input'), true);
$folder = $data['folder'];
$subdirectory = $data['subdirectory'];
$subdirectoryPath = "$folder/$subdirectory";

if (file_exists($subdirectoryPath)) {
    echo "Subdirectory already exists.";
    exit;
}

if (mkdir($subdirectoryPath)) {
    echo "Subdirectory created successfully.";
} else {
    echo "Error creating subdirectory.";
}
?>