<?php
require 'db_connect.php';

function fetchUserByLogin($conn, $login) {
    $stmt = $conn->prepare("SELECT id, password FROM accounts WHERE login = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($userId, $passwordHash);
        $stmt->fetch();
        return ['id' => $userId, 'password' => $passwordHash];
    }
    return null;
}

function closeDbConnection($conn) {
    $conn->close();
}

function usernameExists($conn, $login) {
    $stmt = $conn->prepare("SELECT id FROM accounts WHERE login = ?");
    $stmt->bind_param("s", $login);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

function emailExists($conn, $email) {
    $stmt = $conn->prepare("SELECT id FROM accounts WHERE current_email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();
    $exists = $stmt->num_rows > 0;
    $stmt->close();
    return $exists;
}

function getNextUserId($conn) {
    $result = $conn->query("SELECT MAX(id) AS max_id FROM accounts");
    $row = $result->fetch_assoc();
    return ($row['max_id'] ? $row['max_id'] : 999) + 1;
}

function registerUser($conn, $id, $login, $passwordHash, $email) {
    $stmt = $conn->prepare("INSERT INTO accounts (id, login, password, current_email, registration_email, timecreate, timelastmodify) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
    $stmt->bind_param("issss", $id, $login, $passwordHash, $email, $email);
    $success = $stmt->execute();
    $stmt->close();
    return $success;
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Function to get the character limit from `content_ids` based on the account ID
function getMaxCharacters($accountId) {
    global $conn;
    $stmt = $conn->prepare("SELECT content_ids FROM accounts WHERE id = ?");
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $stmt->bind_result($maxCharacters);
    $stmt->fetch();
    $stmt->close();
    return $maxCharacters;
}

// Function to count current characters for a given account ID
function getCharacterCount($accountId) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) AS character_count FROM chars WHERE accid = ?");
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $stmt->bind_result($characterCount);
    $stmt->fetch();
    $stmt->close();
    return $characterCount;
}

// Function to check if a character name is already taken
function isCharacterNameTaken($charname) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM chars WHERE charname = ? AND accid >= 1000");
    $stmt->bind_param("s", $charname);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count > 0;
}

// Function to get the next available character ID
function getNextCharacterId() {
    global $conn;
    $result = $conn->query("SELECT MAX(charid) AS max_charid FROM chars");
    $row = $result->fetch_assoc();
    return ($row['max_charid'] ?? 0) + 1;
}

// Function to insert a new character into the `chars` table
function insertCharacter($charid, $accountId, $charname, $nationId, $startZone, $gmlevel = 0) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO chars (charid, accid, charname, nation, pos_zone, gmlevel) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iisiii", $charid, $accountId, $charname, $nationId, $startZone, $gmlevel);
    $stmt->execute();
    $stmt->close();
}

// Function to insert into `char_look` table
function insertCharacterLook($charid, $appearanceId, $raceId, $size = 1) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO char_look (charid, face, race, size, head, body, hands, legs, feet, main, sub, ranged) VALUES (?, ?, ?, ?, 0, 0, 0, 0, 0, 0, 0, 0)");
    $stmt->bind_param("iiii", $charid, $appearanceId, $raceId, $size);
    $stmt->execute();
    $stmt->close();
}

// Function to insert into `char_stats` table
function insertCharacterStats($charid, $mjob) {
    global $conn;
    $stmt = $conn->prepare("INSERT INTO char_stats (charid, mjob) VALUES (?, ?)");
    $stmt->bind_param("ii", $charid, $mjob);
    $stmt->execute();
    $stmt->close();
}

function getCharactersForAccount($accountId) {
    global $conn, $raceFolderMap, $faceMap, $jobs;
    $stmt = $conn->prepare("
        SELECT chars.charid, chars.charname, char_look.race, char_look.face, 
               char_stats.mjob, char_stats.sjob, char_stats.mlvl AS mjob_lvl, char_stats.slvl AS sjob_lvl
        FROM chars
        JOIN char_look ON chars.charid = char_look.charid
        JOIN char_stats ON chars.charid = char_stats.charid
        WHERE chars.accid = ?
    ");
    $stmt->bind_param("i", $accountId);
    $stmt->execute();
    $result = $stmt->get_result();
    $characters = [];

    while ($row = $result->fetch_assoc()) {
        // Map race ID to folder and face ID to image filename
        $race_folder = $raceFolderMap[$row['race']] ?? 'default_race';
        $face_file = $faceMap[$row['face']] ?? 'default_face';
        $image_path = "/images/races/{$race_folder}/{$face_file}.jpg";

        // Format job levels
        $mjob_abbr = $jobs[$row['mjob']]['abbreviation'] ?? 'UNK';
        $sjob_abbr = $row['sjob'] > 0 ? $jobs[$row['sjob']]['abbreviation'] : null;
        $job_level = $sjob_abbr ? "{$mjob_abbr} {$row['mjob_lvl']} / {$sjob_abbr} {$row['sjob_lvl']}" : "{$mjob_abbr} {$row['mjob_lvl']}";

        // Append character data
        $row['image_path'] = $image_path;
        $row['job_level'] = $job_level;
        $characters[] = $row;
    }
    $stmt->close();
    return $characters;
}

function getNationNameByCharId($conn, $charid) {
    global $nations; // Import the global $nations array

    $stmt = $conn->prepare("
        SELECT nation
        FROM chars
        WHERE charid = ?
    ");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $charid);
    $stmt->execute();
    $stmt->bind_result($nationId);
    $stmt->fetch();
    $stmt->close();

    // Use the $nations map to get the nation name
    return $nations[$nationId]['name'] ?? "Unknown Nation";
}

function getNationRank($conn, $charId, $nationId) {
    // Map the nationId to the appropriate column in the database
    $nationRankColumn = [
        0 => 'rank_sandoria',
        1 => 'rank_bastok',
        2 => 'rank_windurst'
    ];
    
    // Check if the nationId is valid
    if (!isset($nationRankColumn[$nationId])) {
        throw new Exception("Invalid nationId provided: $nationId");
    }

    // Prepare the SQL query
    $columnName = $nationRankColumn[$nationId];
    $stmt = $conn->prepare("SELECT $columnName FROM char_profile WHERE charid = ?");
    
    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    // Bind the character ID to the query
    $stmt->bind_param("i", $charId);

    // Execute the query
    $stmt->execute();

    // Fetch the result
    $stmt->bind_result($rank);
    if ($stmt->fetch()) {
        $stmt->close();
        return $rank; // Return the rank for the specified nation
    }

    // Close the statement and return a default value if no data is found
    $stmt->close();
    return null;
}


function disassociateCharacter($accountId, $charId) {
    global $conn;

    // Check if the character belongs to the logged-in account
    $stmt = $conn->prepare("SELECT charid FROM chars WHERE charid = ? AND accid = ?");
    $stmt->bind_param("ii", $charId, $accountId);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $stmt->close();
        return "Character not found or does not belong to your account.";
    }
    $stmt->close();

    // Disassociate the character by setting accid to 0
    $stmt = $conn->prepare("UPDATE chars SET accid = 0, original_accid = ? WHERE charid = ?");
    $stmt->bind_param("ii", $accountId, $charId);
    
    if ($stmt->execute()) {
        $stmt->close();
        return "success";
    } else {
        $stmt->close();
        return "Error disassociating character. Please try again.";
    }
}

function getEquippedItem($conn, $charid, $equipslotid) {
    $stmt = $conn->prepare("
        SELECT ce.equipslotid, ci.itemId, ci.location
        FROM char_equip ce
        LEFT JOIN char_inventory ci
            ON ce.slotid = ci.slot AND ce.charid = ci.charid
        WHERE ce.charid = ? AND ce.equipslotid = ?
    ");
    $stmt->bind_param("ii", $charid, $equipslotid);
    $stmt->execute();
    $stmt->bind_result($equipslotidResult, $itemId, $location);
    $result = null;

    if ($stmt->fetch()) {
        $result = [
            'equipslotid' => $equipslotidResult,
            'itemId' => $itemId,
            'location' => $location,
        ];
    }
    $stmt->close();
    return $result;
}

function getItemDetails($conn, $itemId) {
    $stmt = $conn->prepare("SELECT name FROM item_equipment WHERE itemId = ?");
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("i", $itemId);
    $stmt->execute();
    $stmt->bind_result($name);
    $stmt->fetch();
    $stmt->close();

    return $name ?: "Unknown Item"; // Return "Unknown Item" if no name is found
}

function getCharacterGil($conn, $charId) {
    // Prepare the SQL statement
    $stmt = $conn->prepare("SELECT quantity FROM char_inventory WHERE charid = ? AND itemid = 65535");
    
    // Bind the `charId` parameter
    $stmt->bind_param("i", $charId);
    
    // Execute the query
    $stmt->execute();
    
    // Bind the result to a variable
    $stmt->bind_result($quantity);
    
    // Fetch the result
    $gilAmount = null;
    if ($stmt->fetch()) {
        $gilAmount = $quantity;
    }
    
    // Close the statement
    $stmt->close();
    
    // Return the gil amount or 0 if not found
    return $gilAmount !== null ? $gilAmount : 0;
}

?>