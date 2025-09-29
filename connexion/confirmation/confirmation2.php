<?php
session_start(); // Ensure session is active

include_once "../logincheck.php";
include "../../config.php"; // Database connection

$userId = $_SESSION['user_id'];
$userFullName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

$message = "";

// Retrieve draft_id from session or GET
if (isset($_SESSION['draft_id'])) {
    $draft_id = intval($_SESSION['draft_id']);
} elseif (isset($_GET['draft_id']) && !empty($_GET['draft_id'])) {
    $draft_id = intval($_GET['draft_id']);
} else {
    $draft_id = null; 
}

// Delete the draft if `draft_id` exists
if ($draft_id) {
    $conn = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);

    if (!$conn) {
        die("❌ Database connection failed: " . mysqli_connect_error());
    }

    $deleteQuery = "DELETE FROM draft WHERE id = ?";
    $stmt = $conn->prepare($deleteQuery);
    $stmt->bind_param("i", $draft_id);

    if ($stmt->execute()) {
        $message = "✅ Draft deleted successfully.";
        unset($_SESSION['draft_id']); // Remove draft_id from session
    } else {
        $message = "❌ Error deleting draft: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    $message = "❌ No draft found to delete.";
}
?>

<!DOCTYPE html>
<html lang="en"> <!-- By AGHILES CHAOUCHE 2023 ©-->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="confirmationtest2.css">
    <title>Confirmation - inv.ctiai.com</title>
</head>
<body>
    <div class="confirmation-container">
        <img src="../image/ctiai_logo.png" alt="Votre Logo" class="logo">
        <h1 class="confirmation-message">Thank you / Merci </h1>
        <h1 class="confirmation-message2"><?php echo htmlspecialchars($userFullName); ?></h1>

        <!-- Display deletion message -->
        <p class="confirmation-status"><?php echo $message; ?></p>

        <a href="../formulaire/formulaire.php" class="link-button1">Add used equipment / Rajouter équipement utilisé</a>
        <a href="../techmenu/techmenu.php" class="link-button3">Go to main page / Aller sur la page principale</a>
        <a href="?logout" class="link-button">Logout / Déconnexion</a>
        <img src="../image/robot1.gif" alt="Image animée" class="animation" loop>
    </div>
</body>
</html>
