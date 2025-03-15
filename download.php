<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if this is a single-file download (via GET) or multi-file download (via POST)
if (isset($_GET['file'])) {
    // Single file download
    $filePath = $_GET['file'];
    if (empty($filePath)) {
        die('No file selected for download.');
    }

    if (file_exists($filePath)) {
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($filePath) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    } else {
        die('File not found: ' . $filePath);
    }
} else {
    // Multi-file download (ZIP)
    $rootDir = 'files'; // Change this to your root directory
    $files = json_decode(file_get_contents('php://input'), true)['files'] ?? []; // Get the files array

    if (empty($files)) {
        die('No files selected for download.');
    }

    // Create a ZIP archive
    $zip = new ZipArchive();
    $zipFileName = 'files.zip';
    $zipFilePath = sys_get_temp_dir() . '/' . $zipFileName;

    if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
        foreach ($files as $file) {
            $filePath = $file; // Use the full relative path
            if (file_exists($filePath)) {
                // Add the file to the ZIP archive with its base name only (no path)
                $zip->addFile($filePath, basename($filePath));
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
}
?>