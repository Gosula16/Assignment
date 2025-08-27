<?php
/**
 * Database Initialization Script
 * Run this file once to set up the database tables
 */

require_once 'config/database.php';

try {
    $database = new Database();
    $database->initializeTables();
    
    echo "Database tables created successfully!\n";
    echo "You can now use the AI Resume Builder application.\n";
    echo "Visit index.php to get started.\n";
    
} catch (Exception $e) {
    echo "Error initializing database: " . $e->getMessage() . "\n";
}
?>