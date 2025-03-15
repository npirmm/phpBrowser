<?php
$data = json_decode(file_get_contents('php://input'), true);
$folder = $data['folder'];
$file = $data['file'];
$content = $data['content'];
$filePath = "$folder/$file";

if (file_put_contents($filePath, $content)) {
    echo "File saved successfully.";
} else {
    echo "Error saving file.";
}
?>