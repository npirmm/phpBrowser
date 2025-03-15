<?php
$folder = $_GET['folder'];
$file = $_GET['file'];
$filePath = "$folder/$file";
if (file_exists($filePath)) {
    echo file_get_contents($filePath);
} else {
    echo "File not found.";
}
?>