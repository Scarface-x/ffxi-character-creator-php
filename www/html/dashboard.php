<?php
session_start();
require '/var/includes/db_functions.php';
include '/var/includes/race.php';
include '/var/includes/job.php';
include '/var/includes/appearance.php';
include '/var/includes/nation.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$accid = $_SESSION['user_id'];
$maxCharacters = getMaxCharacters($accid);
$currentCharacters = getCharactersForAccount($accid);
$currentCharacterCount = count($currentCharacters);

// Handle character selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['selected_charid'])) {
    $_SESSION['selected_charid'] = $_POST['selected_charid'];
    header("Location: equipment.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Dashboard</title>
    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            text-align: center;
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            background-color: <?= BACKGROUND_COLOUR ?>;
            color: <?= TEXT_COLOUR ?>;
        }

        .hidden {
            display: none;
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
            position: fixed;
            top: 20px;
            right: 20px;
            text-decoration: none;
            font-weight: bold;
            color: <?= BORDER_COLOUR ?>;
        }

        .main-content {
            margin-top: -110px;
            width: 100%;
            max-width: 800px;
        }

        .dashboard-title {
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

        .character-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            list-style-type: none;
            padding: 0;
            justify-content: center;
            margin-top: 0px;
        }

        .character-item {
            text-align: center;
            width: 170px;
            border: 2px solid <?= BORDER_COLOUR ?>;
            padding: 15px;
            border-radius: 8px;
            background-color: <?= BOX_BACKGROUND_COLOUR ?>;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            color: <?= TEXT_COLOUR ?>;
            position: relative;
            height: 295px;
            overflow: visible;
        }

        .character-item img {
            position: relative;
            top: 15px;
            display: block;
            width: 100%;
            height: auto;
            border-radius: 4px;
        }

        .character-item button {
            width: 100%;
            padding: 6px 10px;
            background-color: <?= BUTTON_COLOUR ?>;
            color: <?= BUTTON_TEXT_COLOUR ?>;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1em;
            display: block;
            position: relative;
            top: 10px;
        }

        .character-item button:hover {
            background-color: <?= BUTTON_HOVER_COLOUR ?>;
        }

        .create-character-btn {
            padding: 6px 20px;
            background-color: <?= BUTTON_COLOUR ?>;
            color: <?= BUTTON_TEXT_COLOUR ?>;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1em;
            text-decoration: none;
            display: inline-block;
        }

        .create-character-btn:hover {
            background-color: <?= BUTTON_HOVER_COLOUR ?>;
        }

        .character-item strong {
            position: relative;
            top: -5px;
        }

        .character-item p {
            margin: 0;
        }

        .error-message {
            color: <?= ERROR_COLOUR ?>;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="preloader" id="preloader">Loading...</div>

    <a href="logout.php" class="logout-link">Logout</a>
    <div class="main-content" id="main-content">
        <div class="dashboard-title">Active Characters</div>

        <?php if ($currentCharacterCount > 0): ?>
            <ul class="character-container">
                <?php foreach ($currentCharacters as $character): ?>
                    <?php
                    $raceKey = htmlspecialchars($races[$character['race']]['key']);
                    $faceKey = htmlspecialchars($appearanceMap[$character['face']]);
                    $imagePath = "/images/races/$raceKey/$faceKey.jpg";

                    // Fetch nation and rank information
                    $nationName = getNationNameByCharId($conn, $character['charid']);
                    $nationId = array_search($nationName, array_column($nations, 'name'));
                    $nationRank = getNationRank($conn, $character['charid'], $nationId);

                    // Fetch gil amount for character
                    $gilAmount = getCharacterGil($conn, $character['charid']);
                    $formattedGil = number_format($gilAmount);
                    $gilImage = "https://static.ffxiah.com/images/icon/65535.png";
                    ?>
                    <li class="character-item">
                        <form action="" method="POST">
                            <input type="hidden" name="selected_charid" value="<?= htmlspecialchars($character['charid']) ?>">
                            <button type="submit" style="all: unset; cursor: pointer;">
                                <strong><?= htmlspecialchars($character['charname']) ?></strong>
                                <p><?= htmlspecialchars($character['job_level']) ?></p>
                                <p style="position: relative; top: 5px;">
                                    Nation: <?= htmlspecialchars($nationName) ?> [<?= $nationRank ?? 'Unknown' ?>]
                                </p>
                                <img src="<?= $imagePath ?>" alt="Character Image">
                            </button>
                        </form>
                        <!-- Gil Display -->
                        <div style="display: flex; align-items: center; justify-content: center; margin-top: 10px;">
                            <img src="<?= $gilImage ?>" alt="Gil" style="width: 24px; height: 24px; margin-right: 8px;">
                            <span style="font-size: 1.2em; font-weight: bold; color: <?= TEXT_COLOUR ?>; position: relative; top: 15px;">
                                <?= $formattedGil ?>
                            </span>
                        </div>

                        <form action="delete_character.php" method="POST">
                            <input type="hidden" name="charid" value="<?= htmlspecialchars($character['charid']) ?>">
                            <button type="submit" onclick="return confirm('Are you sure you want to delete this character?');" style="margin-top: 10px;">Delete</button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>You have no characters yet.</p>
        <?php endif; ?>

        <?php if ($currentCharacterCount < $maxCharacters): ?>
            <a href="select_race.php" class="create-character-btn">Create Character</a>
        <?php else: ?>
            <p class="error-message">You have reached the maximum number of characters (<?= $maxCharacters ?>).</p>
        <?php endif; ?>
    </div>

    <script>
        const preloader = document.getElementById('preloader');
        const mainContent = document.getElementById('main-content');
        const images = document.querySelectorAll('.character-item img');

        let loadedImages = 0;

        images.forEach(img => {
            img.onload = () => {
                loadedImages++;
                if (loadedImages === images.length) {
                    preloader.style.display = 'none';
                    mainContent.style.display = 'block';
                }
            };
            img.onerror = () => {
                loadedImages++;
                if (loadedImages === images.length) {
                    preloader.style.display = 'none';
                    mainContent.style.display = 'block';
                }
            };
        });

        if (images.length === 0) {
            preloader.style.display = 'none';
            mainContent.style.display = 'block';
        }
    </script>
</body>
</html>
