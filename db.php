<?php
// Database connection script
try {
    $host = "aws-0-ap-southeast-1.pooler.supabase.com";
    $port = "6543";
    $dbname = "postgres";
    $user = "postgres.zrojghohuxmqajubmjiq";
    $password = "pyc20085@school.pyc.edu.hk";

    // Create a PDO instance for PostgreSQL
    $db = new PDO("pgsql:host=$host;port=$port;dbname=$dbname;", $user, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // Set error mode to exception
} catch (PDOException $e) {
    // If connection fails, display the error
    echo 'Database connection fails: ' . $e->getMessage() . '<br />';
    exit;
}
?>