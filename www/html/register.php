<?php
session_start();
require '/var/includes/db_functions.php';

$error_message = ""; // Initialize error message variable

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Retrieve and sanitize input
    $login = htmlspecialchars($conn->real_escape_string($_POST['login']));
    $password = $_POST['password'];
    $email = htmlspecialchars($conn->real_escape_string($_POST['email']));

    // Define password pattern for validation
    $passwordPattern = '/^[\w!@#$%^&*()\-_=+[\]{}|;:",.<>?`~]{8,64}$/';

    // Validate inputs
    if (!preg_match('/^[a-zA-Z0-9]{3,64}$/', $login)) {
        $error_message = "Username must be alphanumeric and between 3-64 characters.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } elseif (!preg_match($passwordPattern, $password)) {
        $error_message = "Password must be between 8 and 64 characters, contain no spaces, and include allowed special characters.";
    } else {
        // Check if username already exists
        if (usernameExists($conn, $login)) {
            $error_message = "Username is already taken. Please choose another.";
        }

        // Check if email already exists
        if (emailExists($conn, $email)) {
            $error_message = "An account with this email already exists.";
        }

        if (empty($error_message)) {
            // Hash the password
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            // Determine the next available ID
            $nextId = getNextUserId($conn);

            // Register the new user
            if (registerUser($conn, $nextId, $login, $passwordHash, $email)) {
                // Store the success message in session
                $_SESSION['success_message'] = "Account '{$login}' has been created. You can now log in.";
                header("Location: login.php");
                exit();
            } else {
                $error_message = "An error occurred. Please try again.";
            }
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Register</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: <?= BACKGROUND_COLOUR ?>; 
            color: <?= TEXT_COLOUR ?>;
        }

        .center-container {
            text-align: center;
        }

        .register-container {
            background-color: <?= BOX_BACKGROUND_COLOUR ?>; 
            border: 2px solid <?= BORDER_COLOUR ?>; 
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 260px;
            margin: 0 auto;
        }

        h2 {
            margin-top: -10px;
            margin-bottom: 20px;
            color: <?= TEXT_COLOUR ?>;
            font-size: 24px;
        }

        label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            font-weight: bold;
            color: <?= TEXT_COLOUR ?>; 
        }

        input[type="text"],
        input[type="password"],
        input[type="email"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid <?= BORDER_COLOUR ?>;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
            background-color: <?= BACKGROUND_COLOUR ?>; 
            color: <?= TEXT_COLOUR ?>; 
        }

        button[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: <?= BUTTON_COLOUR ?>; 
            color: <?= BUTTON_TEXT_COLOUR ?>; 
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        button[type="submit"]:hover {
            background-color: <?= BUTTON_HOVER_COLOUR ?>; 
            color: <?= BUTTON_TEXT_COLOUR ?>; 
        }

        .nav-link {
            margin-top: 15px;
            color: <?= BORDER_COLOUR ?>; 
            text-decoration: none;
            font-weight: bold;
            position: relative; 
            top: 10px;
        }

        .nav-link:hover {
            text-decoration: underline;
            color: <?= BUTTON_HOVER_COLOUR ?>; 
        }

        .error-message {
            margin-top: 15px;
            color: <?= ERROR_COLOUR ?>; 
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="center-container">
        <div class="register-container">
            <h2>Register</h2>
            <form action="register.php" method="POST">
                <label for="username">Username:</label>
                <input type="text" name="login" id="username" maxlength="64" placeholder="Enter your username" required>

                <label for="password">Password:</label>
                <input type="password" name="password" id="password" maxlength="64" placeholder="Enter your password" required>

                <label for="email">Email:</label>
                <input type="email" name="email" id="email" placeholder="Enter your email" required>

                <button type="submit">Register</button>
            </form>
        </div>

        <!-- Login link moved down by 10px -->
        <a href="login.php" class="nav-link">Login to an account</a>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
