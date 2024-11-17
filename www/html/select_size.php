<?php
session_start();
include '/var/includes/race.php';
include '/var/includes/size.php'; 
include '/var/includes/functions.php';

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
                background-color: <?= BOX_BACKGROUND_COLOUR ?>;
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

// Retrieve session data (no checks for validity)
$raceId = $_SESSION['new_character']['race'] ?? null;
$appearanceId = $_SESSION['new_character']['appearance'] ?? null;

// Determine gender based on race (assuming `getGenderByRace` handles null gracefully)
$gender = getGenderByRace($raceId);

// Update the sizes map dynamically to assign the correct images
foreach ($sizes as $key => &$size) {
    $size['image'] = $gender === 'male' ? $size['male_image'] : $size['female_image'];
}
unset($size); // Unset reference to avoid unexpected behavior

// Handle size selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['size_id'])) {
    $_SESSION['new_character']['size'] = $_POST['size_id']; // Store selected size in session
    header("Location: select_job.php"); // Redirect to the next step
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Size</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: flex-start; /* Align items at the top */
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

        .preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            font-size: 1.5em;
            color: white;
            font-weight: bold;
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
            display: none; /* Hidden until images are preloaded */
            flex-direction: column;
            align-items: center;
            justify-content: center;
            width: 100%;
            margin-top: -120px; 
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
            text-align: center;
            font-size: 1.5em;
            font-weight: bold;
            height: 40px;
            line-height: 40px;
            width: fit-content;
        }

        .size-grid {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center; 
        }

        .size-item {
            border: 2px solid <?= BORDER_COLOUR ?>;
            border-radius: 8px;
            padding: 10px;
            background-color: <?= BOX_BACKGROUND_COLOUR ?>;
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
            width: 120px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); 
        }

        .size-item:hover {
            transform: scale(1.05); 
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.4); 
        }

        .size-item p {
            margin: 0 0 10px;
            color: <?= TEXT_COLOUR ?>;
            font-size: 1em;
            text-align: center;
        }

        .size-item img {
            width: 100%;
            height: auto;
            border-radius: 4px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <div class="preloader" id="preloader">Loading...</div>

    <a href="logout.php" class="logout-link">Logout</a>

    <div class="main-container">
        <div class="title-container">Select Size</div>

        <form method="POST">
            <div class="size-grid">
                <?php foreach ($sizes as $sizeId => $sizeData): ?>
                    <button type="submit" name="size_id" value="<?= htmlspecialchars($sizeId) ?>" class="size-item">
                        <p><?= htmlspecialchars($sizeData['size']); ?></p>
                        <img src="images/size/<?= htmlspecialchars($sizeData['image']); ?>" alt="<?= htmlspecialchars($sizeData['size']); ?>">
                    </button>
                <?php endforeach; ?>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const preloader = document.getElementById('preloader');
            const mainContainer = document.querySelector('.main-container');
            const images = document.querySelectorAll('.size-item img');

            let loadedImages = 0;

            const checkImagesLoaded = () => {
                if (loadedImages === images.length) {
                    preloader.style.display = 'none';
                    mainContainer.style.display = 'flex';
                }
            };

            if (images.length === 0) {
                preloader.style.display = 'none';
                mainContainer.style.display = 'flex';
                return;
            }

            images.forEach((img) => {
                img.onload = () => {
                    loadedImages++;
                    checkImagesLoaded();
                };
                img.onerror = () => {
                    loadedImages++;
                    checkImagesLoaded();
                };

                if (img.complete) {
                    loadedImages++;
                    checkImagesLoaded();
                }
            });
        });
    </script>
</body>
</html>
