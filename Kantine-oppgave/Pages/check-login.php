<?php
session_start();

if (!isset($_SESSION['user_id']) && !isset($_COOKIE['user_id'])) {
    echo "<script>alert('Error: You are not logged in');</script>";
    echo "<a href='login.php'>Log in here</a>";
    exit();
} else {
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = $_COOKIE['user_id'];
    }
}
?>
