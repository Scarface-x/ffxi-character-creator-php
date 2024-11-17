<?php
require '/var/includes/db_functions.php';

function getGenderByRace($raceId) {
    // Define male and female race IDs
    $maleRaces = [1, 3, 5, 8];
    $femaleRaces = [2, 4, 6, 7];

    // Check if the race ID is in the male races array
    if (in_array($raceId, $maleRaces)) {
        return 'male';
    }

    // Check if the race ID is in the female races array
    if (in_array($raceId, $femaleRaces)) {
        return 'female';
    }

    // If it doesn't match either, return null
    return null;
}

// Validate character name format
function validateCharacterName($name) {
	return preg_match('/^[a-zA-Z]{3,16}$/', $name);
}

function fetchItemData($itemId, $conn) {
    // Fetch the item name from the database
    $name = getItemDetails($conn, $itemId);

    // Replace underscores with spaces and capitalize the first letter of each word
    $name = ucwords(str_replace('_', ' ', $name));

    // Construct the image URL
    $imageUrl = "https://static.ffxiah.com/images/icon/{$itemId}.png";

    return [
        'name' => $name,
        'image' => $imageUrl,
    ];
}

// Define valid ID ranges
$validRaces = range(1, 8);
$validJobs = range(1, 6);
$validNations = range(0, 2);
$validAppearances = range(0, 15);
$validSizes = range(0, 2);
?>
