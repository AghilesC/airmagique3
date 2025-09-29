<?php
session_start();
include "../../config.php";
include_once "../logincheck.php";

// Database connection
$connexion = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);
if (!$connexion) {
    die("Connection failed: " . mysqli_connect_error());
}

$message = "";
$messageType = "";
$userId = $_SESSION['user_id'];

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mail = $_POST['mail'];
    $phone_number = $_POST['phone_number'];
    $newPwd = $_POST['new_pwd'];
    $confirmPwd = $_POST['confirm_pwd'];

    // Validate password change
    $passwordUpdate = false;
    if (!empty($newPwd)) {
        if ($newPwd === $confirmPwd) {
            $hashedPwd = password_hash($newPwd, PASSWORD_DEFAULT);
            $passwordUpdate = true;
        } else {
            $message = "Passwords do not match.";
            $messageType = "error";
        }
    }

    // Update database if no password error
    if ($messageType !== "error") {
        if ($passwordUpdate) {
            $query = "UPDATE account SET mail=?, phone_number=?, pwd=? WHERE LOWER(username) = LOWER(?)";
            $stmt = mysqli_prepare($connexion, $query);
            mysqli_stmt_bind_param($stmt, "ssss", $mail, $phone_number, $hashedPwd, $userId);
        } else {
            $query = "UPDATE account SET mail=?, phone_number=? WHERE LOWER(username) = LOWER(?)";
            $stmt = mysqli_prepare($connexion, $query);
            mysqli_stmt_bind_param($stmt, "sss", $mail, $phone_number, $userId);
        }

        if (mysqli_stmt_execute($stmt)) {
            $message = "Settings updated successfully!";
            $messageType = "success";
        } else {
            $message = "Error updating settings.";
            $messageType = "error";
        }
        mysqli_stmt_close($stmt);
    }
}

// Fetch user information
$query = "SELECT * FROM account WHERE LOWER(username) = LOWER(?)";
$stmt = mysqli_prepare($connexion, $query);
mysqli_stmt_bind_param($stmt, "s", $userId);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user = mysqli_fetch_assoc($result);

if (!$user) {
    die("User not found.");
}

mysqli_stmt_close($stmt);
mysqli_close($connexion);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - inv.ctiai.com</title>
    
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            color: #333333;
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
        }

        .logo {
            max-width: 200px;
            max-height: 120px;
            margin-bottom: 15px;
            border-radius: 8px;
        }

        .page-title {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .back-arrow {
            text-decoration: none;
            color: #eb2226;
            font-size: 24px;
            font-weight: bold;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            margin-bottom: 30px;
        }

        .back-arrow:hover {
            color: #d11e21;
            transform: translateX(-3px);
        }

        .back-arrow::before {
            content: '←';
            margin-right: 8px;
        }

        .settings-section {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            margin-bottom: 20px;
        }

        .section-title {
            color: #2c3e50;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 14px;
        }

        input[type="text"], input[type="email"], input[type="password"], input[type="tel"] {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            background: #f8f9fa;
            font-size: 14px;
            color: #495057;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        input[type="text"]:focus, input[type="email"]:focus, input[type="password"]:focus, input[type="tel"]:focus {
            outline: none;
            border-color: #eb2226;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(235, 34, 38, 0.1);
        }

        .readonly-field {
            background: #e9ecef;
            cursor: not-allowed;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-align: center;
            margin: 5px;
            justify-content: center;
        }

        .btn i {
            font-size: 16px;
        }

        .btn-primary {
            background: #eb2226;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #d11e21;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(235, 34, 38, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: #ffffff;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }

        .btn-danger {
            background: #dc3545;
            color: #ffffff;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .btn-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .info-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 30px;
        }

        .info-title {
            color: #2c3e50;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .info-item {
            margin-bottom: 10px;
            font-size: 14px;
        }

        .info-label {
            font-weight: 600;
            color: #6c757d;
            display: inline-block;
            width: 150px;
        }

        .password-note {
            font-size: 12px;
            color: #6c757d;
            margin-top: 5px;
            font-style: italic;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .settings-section {
                padding: 20px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                margin: 5px 0;
            }

            .info-section {
                padding: 15px;
            }

            .info-label {
                width: 120px;
                font-size: 13px;
            }

            .page-title {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .settings-section {
                padding: 15px;
            }

            .page-title {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img src="../image/airmagique_logo.png" alt="Logo" class="logo">
            <h1 class="page-title">Settings</h1>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <a href="../techmenu/techmenu.php" class="back-arrow" title="Back to menu">Back to Menu</a>

        <div class="settings-section">
            <h2 class="section-title">Account Settings</h2>

            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label for="mail">Email Address:</label>
                        <input type="email" name="mail" id="mail" value="<?php echo htmlspecialchars($user['mail']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Phone Number:</label>
                        <input type="tel" name="phone_number" id="phone_number" value="<?php echo htmlspecialchars($user['phone_number']); ?>" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="new_pwd">New Password:</label>
                        <input type="password" name="new_pwd" id="new_pwd" placeholder="Enter new password (optional)">
                        <div class="password-note">Leave blank to keep current password</div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_pwd">Confirm New Password:</label>
                        <input type="password" name="confirm_pwd" id="confirm_pwd" placeholder="Confirm new password">
                    </div>
                </div>

                <div class="info-section">
                    <h3 class="info-title">Account Information</h3>
                    <div class="info-item">
                        <span class="info-label">Full Name:</span>
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Username:</span>
                        <?php echo htmlspecialchars($user['username']); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Account ID:</span>
                        <?php echo htmlspecialchars($user['idacount']); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Depot:</span>
                        <?php echo htmlspecialchars($user['depot'] ?? 'Not assigned'); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Partner Name:</span>
                        <?php echo htmlspecialchars($user['partner_name'] ?? 'None'); ?>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Partner Email:</span>
                        <?php echo htmlspecialchars($user['partner_email'] ?? 'None'); ?>
                    </div>
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Password confirmation validation
        document.getElementById('confirm_pwd').addEventListener('blur', function() {
            const newPwd = document.getElementById('new_pwd').value;
            const confirmPwd = this.value;
            
            if (newPwd && confirmPwd && newPwd !== confirmPwd) {
                this.style.borderColor = '#eb2226';
                this.setCustomValidity('Passwords do not match');
            } else {
                this.style.borderColor = '#ced4da';
                this.setCustomValidity('');
            }
        });

        // Clear validation when typing
        document.getElementById('new_pwd').addEventListener('input', function() {
            document.getElementById('confirm_pwd').setCustomValidity('');
            document.getElementById('confirm_pwd').style.borderColor = '#ced4da';
        }); 
    </script>
</body>
</html>