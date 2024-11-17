<?php
session_start();
require '/var/includes/db_functions.php'; 

if (!isset($_SESSION['failed_attempts'])) {
    $_SESSION['failed_attempts'] = 0;
}

if (!isset($_SESSION['last_attempt'])) {
    $_SESSION['last_attempt'] = 0;
}

$error_message = '';

// Retrieve success message from session
$success_message = "";
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']); // Clear the message after displaying
}

// Rate limiting: Block login attempts after 3 failed tries within 30 seconds
if ($_SESSION['failed_attempts'] >= 3 && time() - $_SESSION['last_attempt'] < 30) {
    $error_message = "Too many failed attempts. Account locked for 30 seconds.";
} else if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $login = $conn->real_escape_string($_POST['login']);
    $password = $_POST['password'];

    // Validate inputs
    if (!preg_match('/^[a-zA-Z0-9]{3,64}$/', $login)) {
        $error_message = "Invalid username format.";
    } else if (strlen($password) < 8 || strlen($password) > 64) {
        $error_message = "Password must be between 8 and 64 characters.";
    } else {
        // Fetch the user's information using the new function
        $user = fetchUserByLogin($conn, $login);

        if ($user) {
            // Verify the password
            if (password_verify($password, $user['password'])) {
                session_regenerate_id(true); // Prevent session fixation attacks
                $_SESSION['user_id'] = $user['id'];

                // Reset failed attempts after a successful login
                $_SESSION['failed_attempts'] = 0;

                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $error_message = "Invalid login credentials.";
            }
        } else {
            $error_message = "Invalid login credentials.";
        }

        // Increment failed attempts
        $_SESSION['failed_attempts']++;
        $_SESSION['last_attempt'] = time();
    }
}

closeDbConnection($conn); // Close DB connection
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Login</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: <?php echo BACKGROUND_COLOUR; ?>;
            color: <?php echo TEXT_COLOUR; ?>;
        }

        .center-container {
            text-align: center;
            position: relative;
        }

        .success-message {
            position: absolute;
            top: -80px; 
            left: 50%;
            transform: translateX(-50%);
            color: <?php echo BUTTON_COLOUR; ?>;
            font-weight: bold;
            white-space: nowrap;
            overflow: hidden; 
            text-overflow: ellipsis; 
        }

        .login-container {
            background-color: <?php echo BOX_BACKGROUND_COLOUR; ?>;
            border: 2px solid <?php echo BORDER_COLOUR; ?>;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            width: 260px;
        }

        h2 {
            margin-top: -10px;
            margin-bottom: 20px;
            font-size: 24px;
            color: <?php echo TEXT_COLOUR; ?>;
        }

        label {
            display: block;
            text-align: left;
            margin-bottom: 5px;
            font-weight: bold;
            color: <?php echo TEXT_COLOUR; ?>;
        }

        input[type="text"],
        input[type="password"] {
            width: calc(100% - 16px); 
            padding: 8px;
            margin-bottom: 15px;
            border: 1px solid <?php echo BORDER_COLOUR; ?>;
            border-radius: 4px;
            font-size: 14px;
            background-color: <?php echo BACKGROUND_COLOUR; ?>;
            color: <?php echo TEXT_COLOUR; ?>;
        }

        button[type="submit"] {
            width: 100%;
            padding: 10px;
            background-color: <?php echo BUTTON_COLOUR; ?>;
            color: <?php echo BUTTON_TEXT_COLOUR; ?>;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
        }

        button[type="submit"]:hover {
            background-color: <?php echo BUTTON_HOVER_COLOUR; ?>;
            color: <?php echo BUTTON_TEXT_COLOUR; ?>;
        }

        .nav-link {
            margin-top: 15px; 
            position: relative; 
            top: 10px; /* Move down by 10px */
            color: <?php echo BORDER_COLOUR; ?>;
            text-decoration: none;
            font-weight: bold;
        }

        .nav-link:hover {
            text-decoration: underline;
            color: <?php echo BUTTON_HOVER_COLOUR; ?>;
        }

        .error-message {
            margin-top: 15px;
            color: <?php echo ERROR_COLOUR; ?>;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="center-container">
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?= htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <div class="login-container">
            <h2>Login</h2>
            <form action="login.php" method="POST">
                <label for="username">Username:</label>
                <input type="text" name="login" id="username" maxlength="64" placeholder="Enter your username" required>

                <label for="password">Password:</label>
                <input type="password" name="password" id="password" maxlength="64" placeholder="Enter your password" required>

                <button type="submit">Login</button>
            </form>
        </div>

        <a href="register.php" class="nav-link">Register an account</a>

        <?php if (!empty($error_message)): ?>
            <div class="error-message"><?= htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
    </div>
</body>
</html>
