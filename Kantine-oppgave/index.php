<?php
// Start session
session_start();

require ('pages/connectdb.php');


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
        // Check if username exists in the database
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            // Verify the password
            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['is_admin'] = $user['admin'] == 1;
                header("Location: index.php");
                exit();
            } else {
                echo "Invalid username or password!";
            }
        } else {
            echo "Invalid username or password!";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Handle registration functionality
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT); // Hash the password
    $email = $_POST['email'];

    // Validate email field
    if (empty($email)) {
        echo "Email is required!";
        exit;
    }

    // Check if username or email already exists
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username OR email = :email");
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            echo "Username or Email already taken!";
        } else {
            // Insert the new user into the users table
            $stmt = $conn->prepare("INSERT INTO users (username, password, email) VALUES (:username, :password, :email)");
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':password', $password);
            $stmt->bindParam(':email', $email);
            $stmt->execute();

            // Fetch the user ID after insertion to log them in immediately
            $user_id = $conn->lastInsertId();

            // Set session variables for logged-in user
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['is_admin'] = false;  // Set to false by default; you can adjust this based on your logic

            // Redirect to the homepage or a specific page
            header("Location: index.php");  // You can change this to the page you want to redirect after registration
            exit();
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}


// Database connection and other queries (unchanged)
try {
    $conn = new PDO("mysql:host=$host;dbname=$db", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = "SELECT ID, allergies, stars, price, name, description, image_url FROM food ORDER BY created_at DESC LIMIT 5";
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

$is_logged_in = isset($_SESSION['user_id']) || isset($_COOKIE['user_id']);

// Fetch food items from the database
$sql = "SELECT ID, allergies, price, name, description, picture_path, image_url FROM food ORDER BY created_at DESC LIMIT 5";
$stmt = $conn->query($sql);
$food_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>


<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>My Website</title>
  <link rel="stylesheet" href="/Kantine-Oppgave/local.css">
  <script src="/Kantine-Oppgave/script.js"></script>
</head>

<body>
    <!-- Header Section -->
    <header class="header">
        <div class="container">
            <!-- Logo Section -->
            <div class="logo">
                <a href="/Kantine-Oppgave/index.php">
                    <img src="/Kantine-Oppgave/Pictures/ostfold-fylkeskommune-logo.png" class="logo-image">
                </a>
            </div>
      
            <!-- Navigation Menu -->
            <nav class="nav">
                <a href="/Kantine-Oppgave/Pages/Maten-For-Dagen.php">Maten for Dagen</a>
                <a href="#reviews">Reviews</a>
            </nav>
      
            <!-- Login or Dropdown -->
            <?php if ($is_logged_in): ?>
                <div class="user-dropdown">
                    <button class="dropdown-button">Account ▼</button>
                    <div class="dropdown-content">
                        <a href="profile.php">Profile</a>
                        <?php if ($_SESSION['is_admin']): ?>
                            <a href="/Kantine-Oppgave/Pages/admin.php">Admin Panel</a> <!-- Show only if user is admin -->
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
    </header>

    <!-- Modal Popup -->
    <div id="loginModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <div id="loginForm">
                <h2>Log In</h2>
                <form action="/Kantine-Oppgave/index.php" method="POST">
                    <label for="username">Username:</label><br>
                    <input type="text" id="username" name="username" required><br>
                    <label for="password">Password:</label><br>
                    <input type="password" id="password" name="password" required><br><br>
                    <button type="submit" name="login">Log In</button>
                </form>
                <p>Don't have an account? <a href="#" onclick="showRegisterForm()">Register here</a></p>
            </div>

            <div id="registerForm" style="display: none;">
                <h2>Register</h2>
                <form action="index.php" method="POST">
                    <label for="new_username">Username:</label><br>
                    <input type="text" id="new_username" name="username" required><br>
                    <label for="new_password">Password:</label><br>
                    <input type="password" id="new_password" name="password" required><br>
                    <label for="email">E-mail:</label><br>
                    <input type="email" id="email" name="email" required><br><br> 
                    <button type="submit" name="register">Register</button>
                </form>
                <p>Already have an account? <a href="#" onclick="showLoginForm()">Log In here</a></p>
            </div>
        </div>
    </div>

    <!-- Main Content Section -->
    <section>
        <div class="flex-box">
            <div class="picture-header">
                <img src="/Kantine-Oppgave/Pictures/Picture1.jpg" alt="Picture 1">
            </div>
            <div class="picture-header">
                <img src="/Kantine-Oppgave/Pictures/Picture1-.jpg" alt="Picture 2">
            </div>
        </div>
    </section>

    <!-- Divider Section -->
    <section class="border"></section>

    <section>
        <div class="flex-box-right">
            <!-- Left Content (Text) -->
            <div class="flex-down">
                <h2>Velkommen til kantina hos Glemmen</h2>
                <p class="box">
                    Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer quis tortor vitae ex ultricies 
                    ullamcorper ut et erat. Integer vel commodo nisi. Aliquam scelerisque 
                    gravida ipsum sit amet pretium. 
                    Nam lobortis, ipsum sed mollis vulputate, lectus tortor posuere lorem, 
                    et tincidunt dui odio sed dolor. Donec posuere cursus
                    libero non suscipit. Nullam sed interdum magna. Ut neque metus, commodo sed dignissim eu, commodo nec nulla.
                </p>
            </div>
            <!-- Right Content (Image) -->
            <div>
                <img src="/Kantine-Oppgave/Pictures/Glemmen+vgs.jpg" alt="Glemmen vgs" class="picture-right">
            </div>
        </div>
    </section>

    <section class="border"></section>

    <!-- Food for the Day Section -->
    <section class="food-for-the-day">
    <div class="flex-box">
        <?php foreach ($food_items as $item): ?>
            <div class="food-item">
                <?php
                $image_src = $item['image_url'] ?: $item['picture_path']; // Use image_url if available, otherwise picture_path
                ?>
                <img src="<?php echo htmlspecialchars($image_src); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="food-image">
                <h3><?php echo htmlspecialchars($item['name']); ?></h3>
                <p><?php echo htmlspecialchars($item['description']); ?></p>
            </div>
        <?php endforeach; ?>
    </div>
</section>

    
    <section class="footer">
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
</section>

</body>
</html>

