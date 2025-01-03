<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require __DIR__ . '../connectdb.php';

// Handle logout functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: index.php");
    exit;
}

// Handle login functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = $user['admin'] == 1;

            header("Location: index.php");
            exit();
        } else {
            $login_error = "Invalid username or password!";
        }
    } catch (PDOException $e) {
        $login_error = "Error: " . $e->getMessage();
    }
}

// Handle registration functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_POST['email'];

    if (empty($email)) {
        $register_error = "Email is required!";
    } else {
        try {
            $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                $register_error = "Username or Email already taken!";
            } else {
                $stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (:username, :password, :email)");
                $stmt->bindParam(':username', $username);
                $stmt->bindParam(':password', $password);
                $stmt->bindParam(':email', $email);
                $stmt->execute();

                $_SESSION['user_id'] = $conn->lastInsertId();
                $_SESSION['username'] = $username;
                $_SESSION['is_admin'] = false;

                header("Location: index.php");
                exit();
            }
        } catch (PDOException $e) {
            $register_error = "Error: " . $e->getMessage();
        }
    }
}

// Fetch food items
try {
    $sql = "SELECT ID, allergies, price, name, description, picture_path, image_url FROM food ORDER BY created_at DESC LIMIT 5";
    $stmt = $conn->query($sql);
    $food_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (isset($_SESSION['user_id'])) {
        $stmt = $conn->prepare("SELECT admin FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        $_SESSION['is_admin'] = ($user['admin'] == 1);
    } else {
        $_SESSION['is_admin'] = false;
    }
} catch (PDOException $e) {
    die("Error: Could not connect. " . $e->getMessage());
}

$is_logged_in = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kantine - Home</title>
    <link rel="stylesheet" href="/Kantine-Oppgave/local.css">
    <script src="/Kantine-Oppgave/script.js"></script>
</head>
<body class="maten-for-dagen-page">
<header class="header">
    <div class="container">
        <div class="logo">
            <a href="/Kantine-Oppgave/index.php">
                <img src="/Kantine-Oppgave/Pictures/ostfold-fylkeskommune-logo.png" class="logo-image" alt="Logo">
            </a>
        </div>
        <nav class="nav">
            <a href="/Kantine-Oppgave/Pages/Maten-For-Dagen">Maten for Dagen</a>
            <a href="#reviews">Reviews</a>
        </nav>
        <div class="auth">
            <?php if ($is_logged_in): ?>
                <div class="user-dropdown">
                    <button class="dropdown-button">Account ▼</button>
                    <div class="dropdown-content">
                        <a href="/Kantine-Oppgave/Pages/profile.php">Profile</a>
                        <?php if ($_SESSION['is_admin']): ?>
                            <a href="/Kantine-Oppgave/Pages/admin.php">Admin Panel</a>
                        <?php endif; ?>
                        <form action="index.php" method="post" style="display: inline;">
                            <button type="submit" name="logout" class="logout-button">Log Out</button>
                        </form>
                    </div>
                </div>
            <?php else: ?>
                <button class="login-button" onclick="showLoginModal()">Log In</button>
            <?php endif; ?>
        </div>
    </div>
</header>

<div id="loginModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <?php if (!empty($login_error)): ?>
            <p class="error-message"><?php echo htmlspecialchars($login_error); ?></p>
        <?php endif; ?>
        <form action="index.php" method="POST">
            <label for="username">Username:</label><br>
            <input type="text" id="username" name="username" required><br>
            <label for="password">Password:</label><br>
            <input type="password" id="password" name="password" required><br><br>
            <button type="submit" name="login">Log In</button>
        </form>
        <p>Don't have an account? <a href="#" onclick="showRegisterForm()">Register here</a></p>
    </div>
</div>


    <main>
        <section class="food-for-the-day">
            <div class="flex-box">
                <?php foreach ($food_items as $item): ?>
                    <div class="food-item">
                        <img src="<?php echo htmlspecialchars($item['image_url'] ?: $item['picture_path']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="food-image">
                        <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                        <p><?php echo htmlspecialchars($item['description']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </main>
    <footer class="footer">
        <div class="footer-container">
            <!-- Footer Content Section -->
            <div class="footer-links">
                <ul>
                    <li><a href="#about-us">About Us</a></li>
                    <li><a href="#contact">Contact</a></li>
                    <li><a href="#privacy-policy">Privacy Policy</a></li>
                    <li><a href="#terms-of-service">Terms of Service</a></li>
                </ul>
            </div>

            <!-- Social Media Links -->
            <div class="social-links">
                <a href="https://facebook.com" class="social-icon">Facebook</a>
                <a href="https://twitter.com" class="social-icon">Twitter</a>
                <a href="https://instagram.com" class="social-icon">Instagram</a>
            </div>

            <!-- Copyright Notice -->
            <div class="copyright">
                <p>&copy; 2024 Erik R. Midtun. All rights reserved.</p>
            </div>
        </div>
    </footer>


</section>
</body>
</html>
