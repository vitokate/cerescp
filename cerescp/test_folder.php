<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Folder Test</h1>";

// Show current directory
echo "Current Directory: " . getcwd() . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";

// Check if we can access parent directory
echo "<h2>Parent Directory Access:</h2>";
echo "Can access parent: " . (is_readable('../') ? 'Yes' : 'No') . "<br>";
echo "Parent directory: " . realpath('../') . "<br>";

// Check permissions
echo "<h2>Permissions:</h2>";
echo "Current directory permissions: " . substr(sprintf('%o', fileperms('.')), -4) . "<br>";
echo "Current directory is readable: " . (is_readable('.') ? 'Yes' : 'No') . "<br>";
echo "Current directory is writable: " . (is_writable('.') ? 'Yes' : 'No') . "<br>";

// List files in current directory
echo "<h2>Files in current directory:</h2>";
$files = scandir('.');
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo $file . " - " . substr(sprintf('%o', fileperms($file)), -4) . "<br>";
    }
}
?> 