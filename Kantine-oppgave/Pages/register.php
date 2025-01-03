<?php
// Start session
session_start();

if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password

    // Database connection
    require 'pages/connectdb.php';

    try {
        $stmt = $conn->prepare("INSERT INTO users (username, password) VALUES (:username, :password)");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':password', $password);
        $stmt->execute();
        header("Location: index.php"); // Redirect to homepage after registration
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
