<?php
$connexion = mysqli_connect("localhost", "root", "", "inventaire");

if (!$connexion) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['new_password']) && isset($_POST['username'])) {
        $new_password = mysqli_real_escape_string($connexion, $_POST['new_password']);
        $username = mysqli_real_escape_string($connexion, $_POST['username']);

        // Retrieve the current password from the database using the username
        $query = "SELECT pwd FROM account WHERE username = '$username'";
        $result = mysqli_query($connexion, $query);

        if ($result && mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_assoc($result);
            $current_password = $row['pwd'];

            // Update the password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_query = "UPDATE account SET pwd = '$hashed_password' WHERE username = '$username'";
            $update_result = mysqli_query($connexion, $update_query);

            if ($update_result) {
                echo "Password updated successfully!";
            } else {
                echo "Error updating password: " . mysqli_error($connexion);
            }
        } else {
            echo "Error fetching current password: " . mysqli_error($connexion);
        }
    } else {
        echo "New password and username are required.";
    }
}

mysqli_close($connexion);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings Menu</title>
</head>
<body>
    <form method="post" action="settingsmenu.php">
        <label for="new_password">New Password:</label>
        <input type="password" name="new_password" required>

        <label for="username">Username:</label>
        <input type="text" name="username" required>

        <button type="submit">Update Password</button>
    </form>
</body>
</html>
