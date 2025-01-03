<?php
// Start session
session_start();

// Assuming form is submitted
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Database connection
    require 'pages/connectdb.php';

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            // Successful login, set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['admin'] == 1; // Check if admin
            header("Location: index.php"); // Redirect to homepage
        } else {
            echo "Invalid username or password!";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
