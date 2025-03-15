<?php
$folder = $_GET['folder'];
$files = scandir($folder);
$files = array_diff($files, ['.', '..']); // Remove . and ..
echo json_encode(array_values($files));
?>