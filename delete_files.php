<?php
$data = json_decode(file_get_contents('php://input'), true);
$folder = $data['folder'];
$files = $data['files'];

foreach ($files as $file) {
    $filePath = "$folder/$file";
    if (file_exists($filePath)) {
        unlink($filePath);
    }
}

echo "Selected files deleted successfully.";
?>