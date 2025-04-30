<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once 'config.php';

try {
    // Test Ragnarok database connection
    $rag_conn = new mysqli($CONFIG['rag_serv'], $CONFIG['rag_user'], $CONFIG['rag_pass'], $CONFIG['rag_db']);
    if ($rag_conn->connect_error) {
        die("Ragnarok DB Connection failed: " . $rag_conn->connect_error);
    }
    echo "Ragnarok DB Connection successful!<br>";

    // Test CP database connection
    $cp_conn = new mysqli($CONFIG['cp_serv'], $CONFIG['cp_user'], $CONFIG['cp_pass'], $CONFIG['cp_db']);
    if ($cp_conn->connect_error) {
        die("CP DB Connection failed: " . $cp_conn->connect_error);
    }
    echo "CP DB Connection successful!<br>";

    // Test session
    session_start();
    echo "Session started successfully!<br>";

    // Test basic PHP functionality
    echo "PHP is working correctly!<br>";
    echo "Current PHP version: " . phpversion() . "<br>";

} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?> 