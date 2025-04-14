<?php
/**
 * Fix Column Names Script
 * This script fixes column name mismatches in the database
 */

// Set proper error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Fixing Column Names</h1>";

// Database configuration
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'scholar_db';

try {
    // Connect to database
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    // Check if the column already exists
    $result = $conn->query("SHOW COLUMNS FROM return_service_activities LIKE 'proof_document'");
    $exists = ($result->num_rows > 0);
    
    if (!$exists) {
        // Add the missing column
        $sql = "ALTER TABLE return_service_activities ADD COLUMN proof_document VARCHAR(255) NULL";
        if ($conn->query($sql) === TRUE) {
            echo "<p>Column 'proof_document' added to return_service_activities table.</p>";
            
            // Now copy data from proof_file to proof_document if proof_file exists
            $check_proof_file = $conn->query("SHOW COLUMNS FROM return_service_activities LIKE 'proof_file'");
            $proof_file_exists = ($check_proof_file->num_rows > 0);
            
            if ($proof_file_exists) {
                $update_sql = "UPDATE return_service_activities SET proof_document = proof_file WHERE proof_file IS NOT NULL";
                if ($conn->query($update_sql) === TRUE) {
                    echo "<p>Data copied from 'proof_file' to 'proof_document' successfully.</p>";
                } else {
                    echo "<p>Warning: Could not copy data: " . $conn->error . "</p>";
                }
            }
        } else {
            echo "<p>Warning: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>Column 'proof_document' already exists in return_service_activities table.</p>";
    }
    
    // Fix other potential column issues
    $columns_to_check = [
        [
            "table" => "student_concerns",
            "columns" => [
                ["from" => "message", "to" => "details", "type" => "TEXT NOT NULL"]
            ]
        ],
        [
            "table" => "return_service_activities",
            "columns" => [
                ["from" => "description", "to" => "activity_description", "type" => "TEXT NULL"]
            ]
        ]
    ];
    
    foreach ($columns_to_check as $table_info) {
        $table = $table_info["table"];
        
        // Check if table exists
        $table_check = $conn->query("SHOW TABLES LIKE '$table'");
        if ($table_check->num_rows == 0) {
            echo "<p>Table '$table' does not exist, skipping column checks.</p>";
            continue;
        }
        
        foreach ($table_info["columns"] as $column_info) {
            $from = $column_info["from"];
            $to = $column_info["to"];
            $type = $column_info["type"];
            
            // Check if 'to' column exists
            $to_exists = $conn->query("SHOW COLUMNS FROM $table LIKE '$to'")->num_rows > 0;
            $from_exists = $conn->query("SHOW COLUMNS FROM $table LIKE '$from'")->num_rows > 0;
            
            if (!$to_exists && $from_exists) {
                // Add the missing column
                $sql = "ALTER TABLE $table ADD COLUMN $to $type";
                if ($conn->query($sql) === TRUE) {
                    echo "<p>Column '$to' added to $table table.</p>";
                    
                    // Copy data from old column to new column
                    $update_sql = "UPDATE $table SET $to = $from";
                    if ($conn->query($update_sql) === TRUE) {
                        echo "<p>Data copied from '$from' to '$to' successfully.</p>";
                    } else {
                        echo "<p>Warning: Could not copy data: " . $conn->error . "</p>";
                    }
                } else {
                    echo "<p>Warning: " . $conn->error . "</p>";
                }
            } elseif (!$to_exists && !$from_exists) {
                // Neither column exists, add the 'to' column
                $sql = "ALTER TABLE $table ADD COLUMN $to $type";
                if ($conn->query($sql) === TRUE) {
                    echo "<p>Column '$to' added to $table table.</p>";
                } else {
                    echo "<p>Warning: " . $conn->error . "</p>";
                }
            } elseif ($to_exists) {
                echo "<p>Column '$to' already exists in $table table.</p>";
            }
        }
    }
    
    // Add 'school' column to users table
    $school_col_check = $conn->query("SHOW COLUMNS FROM users LIKE 'school'");
    $school_exists = ($school_col_check->num_rows > 0);
    
    if (!$school_exists) {
        $sql = "ALTER TABLE users ADD COLUMN school VARCHAR(255) NULL";
        if ($conn->query($sql) === TRUE) {
            echo "<p>Column 'school' added to users table.</p>";
        } else {
            echo "<p>Warning: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>Column 'school' already exists in users table.</p>";
    }
    
    echo "<h2>Database Update Complete!</h2>";
    echo "<p>Column names have been fixed.</p>";
    echo "<p><a href='admin/students_submission.php'>Go to Admin Submissions Page</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?> 