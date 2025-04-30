<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Information</h1>";

// Check if config.php exists and is readable
echo "<h2>File System Check</h2>";
echo "config.php exists: " . (file_exists('config.php') ? 'Yes' : 'No') . "<br>";
echo "config.php is readable: " . (is_readable('config.php') ? 'Yes' : 'No') . "<br>";

// Try to include config.php
echo "<h2>Configuration Check</h2>";
try {
    include_once 'config.php';
    echo "config.php included successfully<br>";
    
    // Check if required variables are set
    $required_vars = ['rag_serv', 'rag_user', 'rag_pass', 'rag_db', 'cp_serv', 'cp_user', 'cp_pass', 'cp_db'];
    foreach ($required_vars as $var) {
        echo "$var is set: " . (isset($CONFIG[$var]) ? 'Yes' : 'No') . "<br>";
    }
} catch (Exception $e) {
    echo "Error including config.php: " . $e->getMessage() . "<br>";
}

// Check database connection
echo "<h2>Database Connection Test</h2>";
try {
    $conn = new mysqli($CONFIG['rag_serv'], $CONFIG['rag_user'], $CONFIG['rag_pass'], $CONFIG['rag_db']);
    if ($conn->connect_error) {
        echo "Ragnarok DB Connection failed: " . $conn->connect_error . "<br>";
    } else {
        echo "Ragnarok DB Connection successful!<br>";
        $conn->close();
    }
} catch (Exception $e) {
    echo "Error connecting to Ragnarok DB: " . $e->getMessage() . "<br>";
}

// Check session
echo "<h2>Session Test</h2>";
try {
    session_start();
    echo "Session started successfully<br>";
    echo "Session ID: " . session_id() . "<br>";
} catch (Exception $e) {
    echo "Error starting session: " . $e->getMessage() . "<br>";
}

// Check PHP configuration
echo "<h2>PHP Configuration</h2>";
echo "PHP Version: " . phpversion() . "<br>";
echo "Memory Limit: " . ini_get('memory_limit') . "<br>";
echo "Max Execution Time: " . ini_get('max_execution_time') . "<br>";
echo "Display Errors: " . ini_get('display_errors') . "<br>";
echo "Error Reporting: " . ini_get('error_reporting') . "<br>";

// Check directory permissions
echo "<h2>Directory Permissions</h2>";
echo "Current directory: " . getcwd() . "<br>";
echo "Directory is writable: " . (is_writable('.') ? 'Yes' : 'No') . "<br>";
echo "Directory permissions: " . substr(sprintf('%o', fileperms('.')), -4) . "<br>";
?> 