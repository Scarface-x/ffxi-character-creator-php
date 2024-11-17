<?php
session_start();
require '/var/includes/db_functions.php';

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$accid = $_SESSION['user_id'];

// Check if character ID is provided
if (!isset($_POST['charid'])) {
    $_SESSION['error_message'] = "No character ID provided.";
    header("Location: dashboard.php");
    exit();
}

$charid = (int)$_POST['charid'];

// Attempt to delete the character
$result = disassociateCharacter($accid, $charid);

// Set error or success message in the session
if ($result !== "success") {
    $_SESSION['error_message'] = $result; // Pass the error message from `disassociateCharacter`
} else {
    $_SESSION['success_message'] = "Character successfully deleted.";
}

// Redirect to the dashboard
header("Location: dashboard.php");
exit();

?>
