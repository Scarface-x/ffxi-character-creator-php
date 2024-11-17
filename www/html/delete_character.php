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
    header("Location: dashboard.php");
    exit();
}

$charid = (int)$_POST['charid'];

// Attempt to delete the character
$result = disassociateCharacter($accid, $charid);

header("Location: dashboard.php");
exit();
?>
