<?php
session_start();
require '/var/includes/functions.php';
include '/var/includes/race.php';
include '/var/includes/job.php';
include '/var/includes/appearance.php';
include '/var/includes/slot.php';
//include '/var/includes/container.php';
include '/var/includes/nation.php';

if (!isset($_SESSION['selected_charid'])) {
    header("Location: dashboard.php");
    exit();
}

$charid = $_SESSION['selected_charid'];

// Fetch character details
$character = null;
$currentCharacters = getCharactersForAccount($_SESSION['user_id']);
foreach ($currentCharacters as $char) {
    if ($char['charid'] == $charid) {
        $character = $char;
        break;
    }
}

if (!$character) {
    header("Location: dashboard.php");
    exit();
}

// Fetch equipped items for the character
$equipment = [];
foreach ($slotMap as $slotId => $slotName) {
    $equipment[$slotId] = getEquippedItem($conn, $charid, $slotId);
}

// Fetch nation name and rank
$nationName = getNationNameByCharId($conn, $character['charid']);
$nationId = array_search($nationName, array_column($nations, 'name')); 
$nationRank = getNationRank($conn, $charid, $nationId);

// Preload images
$raceKey = htmlspecialchars($races[$character['race']]['key']);
$faceKey = htmlspecialchars($appearanceMap[$character['face']]);
$imagePath = "/images/races/$raceKey/$faceKey.jpg";

$gilImage = "https://static.ffxiah.com/images/icon/65535.png";
$itemImages = [];
foreach ($slotMap as $slotId => $slotName) {
    $item = $equipment[$slotId];
    if ($item && $item['itemId'] && $item['itemId'] != 65535) {
        $itemData = fetchItemData($item['itemId'], $conn);
        $itemImages[] = $itemData['image'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Character Equipment</title>
    <link rel="preload" href="<?= $imagePath ?>" as="image">
    <link rel="preload" href="<?= $gilImage ?>" as="image">
    <?php foreach ($itemImages as $itemImage): ?>
        <link rel="preload" href="<?= htmlspecialchars($itemImage) ?>" as="image">
    <?php endforeach; ?>
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
            margin-top: 86px;
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
            margin-top: -115px;
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
            justify-content: center;
            align-items: center;
            flex-direction: column;
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
            margin-bottom: 20px;
            position: relative;
            height: 260px;
            overflow: visible;
        }

        .character-item img {
            position: relative;
            top: -30px;
            display: block;
            width: 100%;
            height: auto;
            border-radius: 4px;
        }

        .grid-container {
            display: flex;
            justify-content: center;
            width: 100%;
            margin-top: 0px;
            align-items: center;
            text-align: center;
        }
        .equipment-grid {
            display: grid;
            grid-template-columns: repeat(4, 50px);
            gap: 5px;
            margin: 0 auto;
        }
        .equipment-slot {
            width: 50px;
            height: 50px;
            border: 2px solid <?= BORDER_COLOUR ?>;
            background-color: <?= BOX_BACKGROUND_COLOUR ?>;
            border-radius: 4px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        .equipment-slot img {
            width: 32px;
            height: 32px;
            object-fit: contain;
            margin: auto;
            display: block;
        }
        .equipment-slot p {
            margin: 0;
            font-size: 10px;
            color: <?= TEXT_COLOUR ?>;
            position: absolute;
            bottom: 2px;
            text-align: center;
            width: 100%;
        }
    </style>
</head>
<body>
    <a href="logout.php" class="logout-link">Logout</a>
    <div class="main-content">
        <div class="dashboard-title">Character Equipment</div>

        <div class="character-container">
            <div class="character-item">
                <strong style="position: relative; top: -5px;"><?= htmlspecialchars($character['charname']) ?></strong>
                <p style="position: relative; top: -15px;"><?= htmlspecialchars($character['job_level']) ?></p>
                <p style="position: relative; top: -25px;">
                    Nation: <?= htmlspecialchars($nationName) ?> [<?= $nationRank ?? 'Unknown' ?>]
                </p>
                <img src="<?= $imagePath ?>" alt="Character Image">
                <div style="display: flex; align-items: center; margin-top: 10px; justify-content: center;">
                    <img src="<?= $gilImage ?>" alt="Gil" style="width: 24px; height: 24px; margin-right: 8px; position: relative; top: -30px;">
                    <span style="font-size: 1.2em; font-weight: bold; color: <?= TEXT_COLOUR ?>; position: relative; top: -30px;">
                        <?= number_format(getCharacterGil($conn, $charid)) ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="grid-container">
            <div class="equipment-grid">
                <?php
                foreach ($slotMap as $slotId => $slotName):
                ?>
                    <div class="equipment-slot">
                        <?php
                        $item = $equipment[$slotId];
                        $image = null;
                        $hoverText = $slotName;

                        if ($item && $item['itemId'] && $item['itemId'] != 65535) {
                            $itemData = fetchItemData($item['itemId'], $conn);
                            $image = $itemData['image'];
                            $hoverText = $itemData['name'];
                        }
                        ?>
                        <?php if ($image): ?>
                            <a href="https://www.ffxiah.com/item/<?= htmlspecialchars($item['itemId']) ?>" target="_blank">
                                <img src="<?= htmlspecialchars($image) ?>" alt="<?= htmlspecialchars($slotName) ?>" title="<?= htmlspecialchars($hoverText) ?>">
                            </a>
                        <?php endif; ?>
                        <p><?= htmlspecialchars($slotName) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</body>
</html>
