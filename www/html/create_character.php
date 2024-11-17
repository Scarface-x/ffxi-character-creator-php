<?php
session_start();
require '/var/includes/functions.php'; 
include '/var/includes/job.php';
include '/var/includes/race.php';
include '/var/includes/size.php';
include '/var/includes/nation.php';
include '/var/includes/appearance.php';

// Prevent caching of this page
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");

// Ensure the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Extract user and session data
$accountId = $_SESSION['user_id'];
$maxCharacters = getMaxCharacters($accountId);
$characterCount = getCharacterCount($accountId);

// Define valid ID ranges
$validRaces = range(1, 8);
$validJobs = range(1, 6);
$validNations = range(0, 2);
$validAppearances = range(0, 15);
$validSizes = range(0, 2);

$error = null;

// Check max character limit
if ($characterCount >= $maxCharacters) {
    $error = "You have reached the maximum number of characters allowed.";
}

// Validate session data for character creation
if (!isset($_SESSION['new_character']['race'], $_SESSION['new_character']['appearance'], 
          $_SESSION['new_character']['job'], $_SESSION['new_character']['nation'], 
          $_SESSION['new_character']['size'])) {
    $error = "Incomplete character creation data. Please restart the process.";
} else {
    // Extract and validate each selection
    $raceId = $_SESSION['new_character']['race'];
    $appearanceId = $_SESSION['new_character']['appearance'];
    $jobId = $_SESSION['new_character']['job'];
    $nationId = $_SESSION['new_character']['nation'];
    $sizeId = $_SESSION['new_character']['size'];

    if (
        !in_array($raceId, $validRaces) ||
        !in_array($appearanceId, $validAppearances) ||
        !in_array($jobId, $validJobs) ||
        !in_array($nationId, $validNations) ||
        !in_array($sizeId, $validSizes)
    ) {
        $error = "Invalid character data provided. Please verify your selections.";
    }
}

// Only proceed if no errors
if (!$error) {
    // Extract details for display
    $race = $races[$raceId];
    $raceName = $race['name'];
    $appearanceText = $appearanceMap[$appearanceId];
    $job = $jobs[$jobId];
    $nation = $nations[$nationId];
    $sizeText = $sizes[$sizeId]['size'];

    $nameError = null;

    // Handle form submission
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        $charname = ucfirst(strtolower(trim($_POST['charname'])));

        if (!validateCharacterName($charname)) {
            $nameError = "Invalid character name. Only letters allowed, between 3 and 16 characters.";
        } elseif (isCharacterNameTaken($charname)) {
            $nameError = "A character with that name already exists.";
        } else {
            $charid = getNextCharacterId();
            $conn->begin_transaction();
            try {
                insertCharacter($charid, $accountId, $charname, $nationId, $nation['zones'][0]);
                insertCharacterLook($charid, $appearanceId, $raceId, $sizeId);
                insertCharacterStats($charid, $jobId);

                $conn->commit();

                // Clear session data for new character creation
                unset($_SESSION['new_character']);

                // Redirect to dashboard after successful creation
                header("Location: dashboard.php?success=character_created");
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $nameError = "Error creating character: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Name Your Character</title>
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
            margin-top: -12px;
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
            line-height: 40px;
            font-size: 1.5em;
            font-weight: bold;
            height: 40px;
            width: fit-content;
        }

        .error-message {
            color: <?= ERROR_COLOUR ?>;
            font-size: 1em;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .selection-info {
            width: 180px;
            margin: 0 auto 20px;
            padding: 10px;
            border: 2px solid <?= BORDER_COLOUR ?>;
            border-radius: 8px;
            background-color: <?= BOX_BACKGROUND_COLOUR ?>;
            text-align: center;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .selection-info img {
            width: 100%;
            height: auto;
            border-radius: 4px;
            margin-top: 10px;
        }

        .selection-info p {
            color: <?= TEXT_COLOUR ?>;
            margin: 5px 0;
            word-break: break-all;
        }

        form {
            margin-top: 0px;
            position: relative; 
        }

        input[type="text"] {
            width: 184px;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 4px;
            border: 1px solid <?= BORDER_COLOUR ?>;
            font-size: 1em;
            color: <?= TEXT_COLOUR ?>;
            background-color: <?= BOX_BACKGROUND_COLOUR ?>;
            text-align: center;
        }

        input[type="text"]::placeholder {
            color: <?= PLACEHOLDER_COLOUR ?>;
        }

        button[type="submit"] {
            width: calc(50% + 27px);
            padding: 10px;
            margin-top: 10px;
            background-color: <?= BUTTON_COLOUR ?>;
            color: <?= BUTTON_TEXT_COLOUR ?>;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
            font-size: 1em;
        }

        button[type="submit"]:hover {
            background-color: <?= BUTTON_HOVER_COLOUR ?>;
        }

        .name-exists-error {
            position: absolute;
            top: 120px;
            left: 50%;
            transform: translateX(-50%);
            color: <?= ERROR_COLOUR ?>;
            font-size: 1em;
            font-weight: bold;
            text-align: center;
            width: 100%;
        }
    </style>
</head>
<body>
    <a href="logout.php" class="logout-link">Logout</a>

    <div class="main-container">
        <div class="title-container">Name Your Character</div>

        <?php if ($error): ?>
            <div class="error-message"><?= htmlspecialchars($error) ?></div>
        <?php else: ?>
            <div class="selection-info">
                <p>Race: <?= htmlspecialchars($raceName) ?></p>
                <p>Size: <?= htmlspecialchars($sizeText) ?></p>
                <p>Job: <?= htmlspecialchars($job['full_name']) ?></p>
                <p>Nation: <?= htmlspecialchars($nation['name']) ?></p>
                <img src="/images/races/<?= htmlspecialchars($race['key']) ?>/<?= htmlspecialchars($appearanceText) ?>.jpg" alt="Selected Appearance">
            </div>

            <form method="POST">
                <input type="text" name="charname" maxlength="16" placeholder="Enter character name" required>
                <button type="submit">Create Character</button>
                <?php if ($nameError): ?>
                    <div class="name-exists-error"><?= htmlspecialchars($nameError) ?></div>
                <?php endif; ?>
            </form>
        <?php endif; ?>
    </div>
</body>
</html>
