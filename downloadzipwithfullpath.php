<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the root directory and files from the request
$rootDir = 'files'; // Change this to your root directory
$files = json_decode(file_get_contents('php://input'), true)['files'] ?? []; // Get the files array

if (empty($files)) {
    die('No files selected for download.');
}

// Create a ZIP archive
$zip = new ZipArchive();
$zipFileName = 'files_with_path.zip';
$zipFilePath = sys_get_temp_dir() . '/' . $zipFileName;

if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
    foreach ($files as $file) {
        $filePath = $file; // Use the full relative path
        if (file_exists($filePath)) {
            // Add the file to the ZIP archive with its full relative path
            $relativePath = substr($filePath, strlen($rootDir) + 1); // Remove the root directory from the path
            $zip->addFile($filePath, $relativePath);
        } else {
            die('File not found: ' . $filePath);
        }
    }
    $zip->close();

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $zipFileName . '"');
    header('Content-Length: ' . filesize($zipFilePath));
    readfile($zipFilePath);
    unlink($zipFilePath); // Delete the temporary ZIP file
    exit;
} else {
    die('Failed to create ZIP archive.');
}
?>