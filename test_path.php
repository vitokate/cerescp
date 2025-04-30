<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Path Test</h1>";

// Show current directory
echo "Current Directory: " . getcwd() . "<br>";
echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
echo "Script Filename: " . $_SERVER['SCRIPT_FILENAME'] . "<br>";
echo "Request URI: " . $_SERVER['REQUEST_URI'] . "<br>";

// List files in current directory
echo "<h2>Files in current directory:</h2>";
$files = scandir('.');
foreach ($files as $file) {
    if ($file != '.' && $file != '..') {
        echo $file . "<br>";
    }
}

// Check if index.php exists
echo "<h2>File Check:</h2>";
echo "index.php exists: " . (file_exists('index.php') ? 'Yes' : 'No') . "<br>";
echo "config.php exists: " . (file_exists('config.php') ? 'Yes' : 'No') . "<br>";
?> 