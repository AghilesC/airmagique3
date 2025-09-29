<?php
// By AGHILES CHAOUCHE 2025 ©
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$error_message = '';
$success_message = '';
$username_error = '';
$email_error = '';
$password_error = '';

// Traitement de l'inscription - VERSION BASIQUE
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["submit"])) {
    $identifiant = $_POST["identifiant"];
    $rawPassword = $_POST["mdp"];
    $confirmPassword = $_POST["confirmer_mdp"];
    $phone_number = $_POST["phone_number"];
    $first_name = ucfirst($_POST["first_name"]);
    $last_name = ucfirst($_POST["last_name"]);
    $mail = $_POST["mail"];

    // Vérification des mots de passe
    if ($rawPassword !== $confirmPassword) {
        $password_error = "Passwords do not match.";
    } else {
        $hashedPassword = password_hash($rawPassword, PASSWORD_DEFAULT);

        // Connexion simple comme votre version originale
        include_once('../../config.php');
        
        $connexion = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);
        if (!$connexion) {
            die("La connexion à la base de données a échoué : " . mysqli_connect_error());
        }

        // Vérifier si l'identifiant existe déjà
        $checkQuery = "SELECT username FROM account WHERE LOWER(username) = LOWER(?)";
        $checkStmt = mysqli_prepare($connexion, $checkQuery);
        
        if ($checkStmt) {
            mysqli_stmt_bind_param($checkStmt, "s", $identifiant);
            mysqli_stmt_execute($checkStmt);
            $checkResult = mysqli_stmt_get_result($checkStmt);
            
            if (mysqli_num_rows($checkResult) > 0) {
                $username_error = "This username is already taken.";
                mysqli_stmt_close($checkStmt);
            } else {
                mysqli_stmt_close($checkStmt);
                
                // Vérifier si l'email existe déjà
                $checkEmailQuery = "SELECT mail FROM account WHERE LOWER(mail) = LOWER(?)";
                $checkEmailStmt = mysqli_prepare($connexion, $checkEmailQuery);
                
                if ($checkEmailStmt) {
                    mysqli_stmt_bind_param($checkEmailStmt, "s", $mail);
                    mysqli_stmt_execute($checkEmailStmt);
                    $checkEmailResult = mysqli_stmt_get_result($checkEmailStmt);
                    
                    if (mysqli_num_rows($checkEmailResult) > 0) {
                        $email_error = "This email is already registered.";
                        mysqli_stmt_close($checkEmailStmt);
                    } else {
                        mysqli_stmt_close($checkEmailStmt);
                        
                        // Génération de l'ID
                        $result = mysqli_query($connexion, "SELECT MAX(idacount) as maxId FROM account");
                        $row = mysqli_fetch_assoc($result);
                        $nextId = $row["maxId"] + 1;

                        // Insérer la valeur dans la base de données avec validation = "deactivated" et isNew = "yes" par défaut
                        $validation = "deactivated"; // Nouveau compte désactivé par défaut
                        $isNew = "yes"; // Marquer comme nouveau compte
                        $query = "INSERT INTO account (idacount, username, pwd, first_name, last_name, mail, phone_number, validation, isNew) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $stmt = mysqli_prepare($connexion, $query);

                        if ($stmt) {
                            mysqli_stmt_bind_param($stmt, "sssssssss", $nextId, $identifiant, $hashedPassword, $first_name, $last_name, $mail, $phone_number, $validation, $isNew);

                            if (mysqli_stmt_execute($stmt)) {
                                $success_message = "Account created successfully! Please wait for admin approval.";
                                mysqli_stmt_close($stmt);
                            } else {
                                $error_message = "Registration failed: " . mysqli_error($connexion);
                                mysqli_stmt_close($stmt);
                            }
                        } else {
                            $error_message = "Prepare failed: " . mysqli_error($connexion);
                        }
                    }
                } else {
                    $error_message = "Email check query failed: " . mysqli_error($connexion);
                }
            }
        } else {
            $error_message = "Username check query failed: " . mysqli_error($connexion);
        }

        mysqli_close($connexion);
    }
}
?>

<!DOCTYPE html>
<html lang="en"> <!-- By AGHILES CHAOUCHE 2025 ©-->

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign up - Air Magique</title>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
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
            max-width: 600px;
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

        .login-container {
            background: #ffffff;
            padding: 50px 40px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
        }

        h1 {
            text-align: center;
            color: #2c3e50;
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 40px;
            letter-spacing: -0.5px;
        }

        .form-group {
            margin-bottom: 24px;
            position: relative;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 24px;
        }

        .form-row .form-group {
            margin-bottom: 0;
        }

        .username-group {
            margin-bottom: 24px;
        }

        .password-section {
            margin-bottom: 24px;
        }

        .password-row {
            margin-bottom: 8px;
        }

        .phone-email-row {
            margin-bottom: 24px;
        }

        .phone-email-row .form-group {
            margin-bottom: 0;
        }

        label {
            display: block;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 15px;
        }

        label .required {
            color: #eb2226;
            margin-left: 2px;
        }

        input[type="text"],
        input[type="password"],
        input[type="email"],
        input[type="tel"] {
            width: 100%;
            padding: 16px 18px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            background: #f8f9fa;
            font-size: 16px;
            color: #495057;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        input[type="text"]::placeholder,
        input[type="password"]::placeholder,
        input[type="email"]::placeholder,
        input[type="tel"]::placeholder {
            color: #adb5bd;
        }

        input[type="text"]:focus,
        input[type="password"]:focus,
        input[type="email"]:focus,
        input[type="tel"]:focus {
            outline: none;
            border-color: #0095e8;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(0, 149, 232, 0.1);
        }

        button {
            width: 100%;
            padding: 16px;
            background: #eb2226;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            margin-top: 20px;
            font-family: inherit;
            letter-spacing: 0.3px;
        }

        button:hover {
            background: #d11e21;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(235, 34, 38, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        .global-error {
            color: #eb2226;
            font-size: 14px;
            margin-bottom: 20px;
            padding: 12px 16px;
            background: rgba(235, 34, 38, 0.05);
            border: 1px solid rgba(235, 34, 38, 0.2);
            border-radius: 6px;
            display: flex;
            align-items: center;
        }

        .global-error::before {
            content: '⚠';
            margin-right: 8px;
            font-size: 16px;
        }

        .success-message {
            color: #28a745;
            font-size: 14px;
            margin-bottom: 20px;
            padding: 12px 16px;
            background: rgba(40, 167, 69, 0.05);
            border: 1px solid rgba(40, 167, 69, 0.2);
            border-radius: 6px;
            display: flex;
            align-items: center;
        }

        .success-message::before {
            content: '✓';
            margin-right: 8px;
            font-size: 16px;
        }

        .field-error {
            color: #eb2226;
            font-size: 13px;
            margin-top: 8px;
            padding: 8px 12px;
            background: rgba(235, 34, 38, 0.05);
            border: 1px solid rgba(235, 34, 38, 0.2);
            border-radius: 6px;
            display: flex;
            align-items: center;
        }

        .field-error::before {
            content: '⚠';
            margin-right: 8px;
            font-size: 14px;
        }

        #password-match-error {
            margin-top: 0;
            margin-bottom: 15px;
        }

        /* Loading state */
        .loading {
            position: relative;
            pointer-events: none;
            opacity: 0.7;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 18px;
            height: 18px;
            margin: -9px 0 0 -9px;
            border: 2px solid transparent;
            border-top: 2px solid #ffffff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }
            
            .container {
                padding: 10px;
            }
            
            .login-container {
                padding: 40px 25px;
                border-radius: 8px;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            h1 {
                font-size: 28px;
                margin-bottom: 35px;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            input[type="text"],
            input[type="password"],
            input[type="email"],
            input[type="tel"] {
                padding: 14px 16px;
                font-size: 16px;
            }
            
            button {
                padding: 14px;
                font-size: 15px;
            }

            .page-title {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 30px 20px;
            }
            
            h1 {
                font-size: 24px;
                margin-bottom: 30px;
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
            <h1 class="page-title">Sign up</h1>
        </div>

        <?php if (!empty($error_message)): ?>
            <div class="global-error"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success_message)): ?>
            <div class="success-message"><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></div>
            <script>
                setTimeout(function() {
                    window.location.href = '../index.php';
                }, 3000);
            </script>
        <?php endif; ?>

        <a href="../index.php" class="back-arrow" title="Back to sign in">Back to Sign In</a>

        <div class="login-container">
            <form id="login-form" method="POST" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First name <span class="required">*</span></label>
                        <input type="text" 
                               id="first_name" 
                               name="first_name" 
                               placeholder="Your First Name" 
                               value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                               required 
                               minlength="2">
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last name <span class="required">*</span></label>
                        <input type="text" 
                               id="last_name" 
                               name="last_name" 
                               placeholder="Your Last Name" 
                               value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                               required 
                               minlength="2">
                    </div>
                </div>

                <div class="form-group username-group">
                    <label for="identifiant">Username <span class="required">*</span></label>
                    <input type="text" 
                           id="identifiant" 
                           name="identifiant" 
                           placeholder="Your Username" 
                           value="<?php echo isset($_POST['identifiant']) ? htmlspecialchars($_POST['identifiant']) : ''; ?>"
                           required 
                           minlength="5">
                    <?php if (!empty($username_error)): ?>
                        <div class="field-error"><?php echo htmlspecialchars($username_error); ?></div>
                    <?php endif; ?>
                </div>

                <div class="password-section">
                    <div class="form-row password-row">
                        <div class="form-group">
                            <label for="mot-de-passe">Password <span class="required">*</span></label>
                            <input type="password" 
                                   id="mot-de-passe" 
                                   name="mdp" 
                                   placeholder="Your Password" 
                                   required 
                                   minlength="7">
                        </div>
                        <div class="form-group">
                            <label for="confirmer-mot-de-passe">Confirm Password <span class="required">*</span></label>
                            <input type="password" 
                                   id="confirmer-mot-de-passe" 
                                   name="confirmer_mdp" 
                                   placeholder="Confirm Password" 
                                   required>
                        </div>
                    </div>
                    <?php if (!empty($password_error)): ?>
                        <div class="field-error" id="password-match-error"><?php echo htmlspecialchars($password_error); ?></div>
                    <?php endif; ?>
                </div>

                <div class="form-row phone-email-row">
                    <div class="form-group">
                        <label for="phone_number">Phone <span class="required">*</span></label>
                        <input type="tel" 
                               id="phone_number" 
                               name="phone_number" 
                               placeholder="Phone Number" 
                               value="<?php echo isset($_POST['phone_number']) ? htmlspecialchars($_POST['phone_number']) : ''; ?>"
                               required 
                               minlength="10" 
                               maxlength="20">
                    </div>
                    <div class="form-group">
                        <label for="mail">Email <span class="required">*</span></label>
                        <input type="email" 
                               id="mail" 
                               name="mail" 
                               placeholder="Email Address" 
                               value="<?php echo isset($_POST['mail']) ? htmlspecialchars($_POST['mail']) : ''; ?>"
                               required>
                        <?php if (!empty($email_error)): ?>
                            <div class="field-error"><?php echo htmlspecialchars($email_error); ?></div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="form-group">
                    <button type="submit" name="submit" id="submit-btn">Create Account</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(document).ready(function () {
            $('#login-form').submit(function (event) {
                var password = $('#mot-de-passe').val();
                var confirmPassword = $('#confirmer-mot-de-passe').val();
                var submitBtn = $('#submit-btn');

                // Clear any existing error messages
                $('.field-error-js').remove();

                if (password !== confirmPassword) {
                    // Create error message for password mismatch
                    var errorMessage = $('<div class="field-error field-error-js">⚠ Passwords do not match.</div>');
                    $('.password-section').append(errorMessage);
                    $('#confirmer-mot-de-passe').focus();
                    event.preventDefault();
                } else {
                    // Add loading state
                    submitBtn.addClass('loading');
                    submitBtn.text('Creating Account...');
                }
            });

            // Real-time password confirmation check
            $('#confirmer-mot-de-passe').on('blur', function() {
                var password = $('#mot-de-passe').val();
                var confirmPassword = $(this).val();
                
                // Clear existing JS error messages
                $('.field-error-js').remove();
                
                if (confirmPassword !== '' && password !== confirmPassword) {
                    var errorMessage = $('<div class="field-error field-error-js">⚠ Passwords do not match.</div>');
                    $('.password-section').append(errorMessage);
                }
            });

            // Clear error when typing in password fields
            $('#mot-de-passe, #confirmer-mot-de-passe').on('input', function() {
                $('.field-error-js').remove();
            });
        });
    </script>
</body>
</html>