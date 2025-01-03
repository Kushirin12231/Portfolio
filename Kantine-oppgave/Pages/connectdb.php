<?php
// Database connection details
$host = 'localhost';
$db = 'kantine-oppgave';
$user = 'Kantine-Oppgave';
$pass = '1234'; // Default MAMP password.

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

try {
    // Establish a single database connection
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Uncomment for debugging connection success (remove in production)
    // echo "Database connection successful.";
} catch (PDOException $e) {
    // Don't expose sensitive details; show user-friendly error messages
    die("Error: Could not connect to the database. Please try again later.");
}

// Optional: If you intend to fetch food items, include a query here
// Note: Ensure that the `created_at` column exists in your `food` table

try {
    $sql = "SELECT ID, allergies, stars, price, name, description, image_url 
            FROM food 
            ORDER BY created_at DESC 
            LIMIT 5";
    $stmt = $conn->query($sql);
    $food_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle query errors gracefully
    echo "Error: Unable to fetch food items. " . $e->getMessage();
}
?>
