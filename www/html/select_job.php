<?php
session_start();
include '/var/includes/race.php';
include '/var/includes/job.php';
include '/var/includes/appearance.php';
include '/var/includes/size.php';
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

// Retrieve session data for job selection
$raceId = $_SESSION['new_character']['race'] ?? null;
$appearanceId = $_SESSION['new_character']['appearance'] ?? null;
$sizeId = $_SESSION['new_character']['size'] ?? null;

if (!$raceId || !$appearanceId || !$sizeId) {
    header("Location: select_race.php");
    exit();
}

// Handle job selection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['job_id'])) {
    $_SESSION['new_character']['job'] = $_POST['job_id']; // Store selected job in session
    header("Location: select_nation.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Job Selection</title>
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
            margin-top: -50px; 
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

        .job-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            text-align: center;
            width: 90%;
            max-width: 900px;
        }

        .job-item {
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

        .job-item:hover {
            transform: scale(1.05);
        }

        .job-item img {
            width: 150px;
            height: auto;
            border-radius: 4px;
            margin-top: 10px;
        }

        .job-item p {
            margin: 0;
            font-size: 1em;
            color: <?= TEXT_COLOUR ?>;
        }
    </style>
</head>
<body>
    <a href="logout.php" class="logout-link">Logout</a>

    <div class="main-container">
        <div class="title-container">Job Selection</div>

        <form method="POST">
            <div class="job-grid">
                <?php foreach ($jobs as $jobID => $job): ?>
                    <?php if ($jobID >= 1 && $jobID <= 6): ?>
                        <button type="submit" name="job_id" value="<?= htmlspecialchars($jobID) ?>" class="job-item">
                            <p><?= htmlspecialchars($job['full_name']) ?></p>
                            <img src="/images/jobs/<?= htmlspecialchars(strtolower($job['abbreviation'])) ?>.jpg" alt="<?= htmlspecialchars($job['full_name']) ?>">
                        </button>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </form>
    </div>
</body>
</html>
