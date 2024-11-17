<?php
session_start();
include '/var/includes/race.php';
include '/var/includes/appearance.php';
include '/var/includes/db_functions.php';

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user ID and check character creation limit
$accountId = $_SESSION['user_id'];
$maxCharacters = getMaxCharacters($accountId);
$characterCount = getCharacterCount($accountId);
$limitReached = $characterCount >= $maxCharacters;

// Show error if character limit is reached
if ($limitReached) {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Error</title>
        <style>
            body {
                background-color: <?= BACKGROUND_COLOUR ?>;
                color: <?= TEXT_COLOUR ?>;
                display: flex;
                justify-content: center;
                align-items: center;
                text-align: center;
                font-family: Arial, sans-serif;
                height: 100vh;
                margin: 0;
            }
            .error-container {
                border: 2px solid <?= ERROR_COLOUR ?>;
                background-color: <?= BOX_BACKGROUND_COLOUR ?>;
                padding: 20px;
                border-radius: 8px;
                max-width: 500px;
                width: 80%;
                color: <?= ERROR_COLOUR ?>;
                font-weight: bold;
            }
        </style>
    </head>
    <body>
        <div class="error-container">
            <strong>You have reached the character creation limit!</strong><br><br>
            Please return to <a href="dashboard.php" style="color: <?= LINK_COLOUR ?>; text-decoration: underline;">Active Characters</a>
        </div>
    </body>
    </html>
    <?php
    exit();
}

// Handle appearance selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['appearance_id'])) {
    $_SESSION['new_character']['appearance'] = $_POST['appearance_id']; // Store the selected appearance in session
    header("Location: select_size.php");
    exit();
}

// Race data for appearance selection
$raceId = $_SESSION['new_character']['race'] ?? null;
$race = $races[$raceId] ?? null;

if (!$race) {
    header("Location: select_race.php");
    exit();
}

$raceDirectory = $race['key'];
$raceName = $race['name'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Appearance Selection</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: flex-start; 
            flex-direction: column;
            text-align: center;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background-color: <?= BACKGROUND_COLOUR ?>;
            color: <?= TEXT_COLOUR ?>;
            position: relative;
        }

        .logout-link {
            position: absolute;
            top: 20px;
            right: 20px;
            color: <?= BORDER_COLOUR ?>;
            text-decoration: none;
            font-weight: bold;
        }

        .main-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin-top: 74px; 
            margin-bottom: 50px;
        }

        .title-container {
            display: inline-block;
            border: 2px solid <?= BORDER_COLOUR ?>;
            padding: 0 20px;
            border-radius: 8px;
            background-color: <?= BOX_BACKGROUND_COLOUR ?>;
            color: <?= TEXT_COLOUR ?>;
            margin-bottom: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            text-align: center;
            line-height: 40px;
            font-size: 1.5em;
            font-weight: bold;
            height: 40px;
        }

        .appearance-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            text-align: center;
            width: 90%;
            max-width: 900px;
        }

        .appearance-item {
            width: 170px;
            padding: 15px;
            border: 2px solid <?= BORDER_COLOUR ?>;
            border-radius: 8px;
            background-color: <?= BOX_BACKGROUND_COLOUR ?>;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            cursor: pointer;
            transition: transform 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .appearance-item:hover {
            transform: scale(1.05);
        }

        .appearance-item img {
            width: 150px;
            height: auto;
            border-radius: 4px;
            margin-top: 10px;
        }

        .appearance-item p {
            margin: 0;
            font-size: 1em;
            color: <?= TEXT_COLOUR ?>;
        }
    </style>
</head>
<body>
    <a href="logout.php" class="logout-link">Logout</a>

    <div class="main-container">
        <div class="title-container"><?= htmlspecialchars($raceName); ?> Appearance Selection</div>

        <form method="POST">
            <div class="appearance-grid">
                <?php foreach ($appearanceMap as $id => $text): ?>
                    <?php
                    $imagePath = "/images/races/$raceDirectory/$text.jpg";
                    ?>
                    <button type="submit" name="appearance_id" value="<?= htmlspecialchars($id) ?>" class="appearance-item">
                        <p>Face <?= htmlspecialchars($text) ?></p>
                        <img src="<?= $imagePath ?>" alt="Face <?= htmlspecialchars($text) ?>" loading="lazy">
                    </button>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
</body>
</html>
