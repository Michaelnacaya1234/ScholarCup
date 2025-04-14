<?php
/**
 * Run Database Update Script
 * 
 * This script executes the database_update.sql file to create missing tables
 * including student_current_status which is causing the error.
 */

// Include database connection
require_once 'database.php';

// Function to execute SQL file
function executeSQLFile($conn, $file) {
    $success = true;
    $error = '';
    
    if (file_exists($file)) {
        $sql = file_get_contents($file);
        
        // Split SQL by semicolon
        $queries = explode(';', $sql);
        
        foreach ($queries as $query) {
            $query = trim($query);
            
            if (!empty($query)) {
                $result = $conn->query($query);
                
                if (!$result) {
                    $success = false;
                    $error .= "Error executing query: " . $conn->error . "<br>";
                }
            }
        }
    } else {
        $success = false;
        $error = "SQL file not found: $file";
    }
    
    return ['success' => $success, 'error' => $error];
}

// Execute the database update SQL file
$result = executeSQLFile($db, 'database_update.sql');

// Display result
echo "<h1>Database Update Results</h1>";

if ($result['success']) {
    echo "<div style='color: green; padding: 10px; border: 1px solid green; margin: 10px 0;'>";
    echo "<p><strong>Success!</strong> The database has been updated successfully.</p>";
    echo "<p>The missing tables have been created, including 'student_current_status'.</p>";
    echo "</div>";
} else {
    echo "<div style='color: red; padding: 10px; border: 1px solid red; margin: 10px 0;'>";
    echo "<p><strong>Error:</strong> Failed to update the database.</p>";
    echo "<p>" . $result['error'] . "</p>";
    echo "</div>";
}

echo "<p><a href='index.php'>Return to homepage</a></p>";
?>