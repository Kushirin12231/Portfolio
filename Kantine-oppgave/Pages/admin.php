<?php
session_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require __DIR__ . '../connectdb.php';



// CSRF token generation
if (!isset($_SESSION['csrf_tokens'])) {
    $_SESSION['csrf_tokens'] = [];
    for ($i = 1; $i <= 5; $i++) {
        $_SESSION['csrf_tokens']["form_{$i}"] = bin2hex(random_bytes(32));
    }
}

// Directory for uploads
$upload_dir = __DIR__ . '/../Pictures/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

$items_added = 0;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['forms']) && is_array($_POST['forms'])) {
        foreach ($_POST['forms'] as $form_id => $data) {
            // Verify CSRF token
            if (empty($data['csrf_token']) || $data['csrf_token'] !== $_SESSION['csrf_tokens'][$form_id]) {
                error_log("CSRF token mismatch for form $form_id");
                continue;
            }

            // Validate form fields
            $name = $data['name'] ?? null;
            $description = $data['description'] ?? null;
            $allergies = $data['allergies'] ?? null;
            $price = $data['price'] ?? null;
            $image_field = "image_$form_id";

            if (empty($name) || empty($description) || empty($price)) {
                error_log("Form $form_id has empty required fields");
                continue;
            }

            // Handle file upload if file exists
            $image_url = null;
            if (!empty($_FILES[$image_field]['name'])) {
                $target_file = $upload_dir . basename($_FILES[$image_field]['name']);
                $image_url = "/Kantine-Oppgave/Pictures/" . basename($_FILES[$image_field]['name']);

                if (!move_uploaded_file($_FILES[$image_field]['tmp_name'], $target_file)) {
                    error_log("File upload failed for form $form_id");
                    continue;
                }
            }

            // Save to database
            try {
                $stmt = $conn->prepare("INSERT INTO food (name, description, allergies, price, image_url) VALUES (:name, :description, :allergies, :price, :image_url)");
                $stmt->bindParam(':name', $name);
                $stmt->bindParam(':description', $description);
                $stmt->bindParam(':allergies', $allergies);
                $stmt->bindParam(':price', $price);
                $stmt->bindParam(':image_url', $image_url);
                $stmt->execute();
                $items_added++;
            } catch (PDOException $e) {
                error_log("Database error for form $form_id: " . $e->getMessage());
            }
        }

        $success_message = $items_added > 0 ? "$items_added food item(s) have been added successfully!" : "No food items were added.";
    } else {
        error_log("No forms data submitted.");
    }
}


$is_logged_in = isset($_SESSION['user_id']) || isset($_COOKIE['user_id']);
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kantine - Add Food</title>
    <link rel="stylesheet" href="/Kantine-Oppgave/local.css">
    <script src="/Kantine-Oppgave/script.js" defer></script>
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
    <main>
    <?php if (!empty($success_message)): ?>
        <p class="success"><?= htmlspecialchars($success_message) ?></p>
    <?php endif; ?>
    <?php if (!empty($error_message)): ?>
        <p class="error"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>

    <div class="forms-container">
    <?php for ($i = 1; $i <= 5; $i++): ?>
        <form method="POST" enctype="multipart/form-data" id="food-form-<?= $i ?>" class="Forms">
            <legend hidden>Food Item <?= $i ?></legend>
            <input type="hidden" name="forms[form_<?= $i ?>][csrf_token]" value="<?= $_SESSION['csrf_tokens']["form_{$i}"] ?>">

            <label for="name_<?= $i ?>">Food Name:</label>
            <input type="text" id="name_<?= $i ?>" name="forms[form_<?= $i ?>][name]" required>

            <label for="description_<?= $i ?>">Description:</label>
            <textarea class="No" id="description_<?= $i ?>" name="forms[form_<?= $i ?>][description]" required></textarea>

            <label for="allergies_<?= $i ?>">Allergies:</label>
            <textarea class="No" id="allergies_<?= $i ?>" name="forms[form_<?= $i ?>][allergies]" required></textarea>

            <label for="price_<?= $i ?>">Price:</label>
            <input type="number" id="price_<?= $i ?>" name="forms[form_<?= $i ?>][price]" required>

            <label for="image_<?= $i ?>">Upload Image:</label>
            <input type="file" id="image_<?= $i ?>" name="image_form_<?= $i ?>" accept="image/*">

            <button type="submit" class="submit-button">Add food</button>
        </form>
    <?php endfor; ?>
</div>


</main>
<footer>
    <p>&copy; 2024 Kantine Management</p>
</footer>
</body>
</html>
