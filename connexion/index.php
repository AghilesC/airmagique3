<?php
// By AGHILES CHAOUCHE 2025 ©
// Démarrage sécurisé de la session
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
session_start();

// Régénération de l'ID de session pour éviter la fixation de session
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// Headers de sécurité
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Content-Security-Policy: default-src \'self\'; script-src \'self\' \'unsafe-inline\'; style-src \'self\' \'unsafe-inline\'; img-src \'self\' data:; font-src \'self\';');

// Protection contre le brute force - Limitation des tentatives
$max_attempts = 5;
$lockout_time = 900; // 15 minutes en secondes

function getRealIpAddr() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    return $_SERVER['REMOTE_ADDR'];
}

$user_ip = getRealIpAddr();
$current_time = time();

// Nettoyage des anciennes tentatives
if (isset($_SESSION['login_attempts'])) {
    $_SESSION['login_attempts'] = array_filter($_SESSION['login_attempts'], function($attempt) use ($current_time, $lockout_time) {
        return ($current_time - $attempt) < $lockout_time;
    });
}

// Vérification si l'IP est bloquée
if (isset($_SESSION['login_attempts']) && count($_SESSION['login_attempts']) >= $max_attempts) {
    $error_message = "Too many failed attempts. Please try again in 15 minutes.";
    $account_locked = true;
} else {
    $account_locked = false;
}

// Génération du token CSRF
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error_message = '';
$success_message = '';

// Traitement de la connexion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$account_locked) {
    // Vérification du token CSRF
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        $error_message = "Invalid security token. Please try again.";
    } else {
        // Validation et sanitisation des entrées
        $username = isset($_POST['identifiant']) ? trim($_POST['identifiant']) : '';
        $password = isset($_POST['mdp']) ? $_POST['mdp'] : '';
        
        // Validation stricte
        if (empty($username) || empty($password)) {
            $error_message = "Please fill in all fields.";
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $error_message = "Invalid username format.";
        } elseif (strlen($password) < 7 || strlen($password) > 255) {
            $error_message = "Invalid password format.";
        } elseif (!preg_match('/^[a-zA-Z0-9_\-\.@]+$/', $username)) {
            $error_message = "Username contains invalid characters.";
        } else {
            // Connexion sécurisée à la base de données
            try {
                include_once('../config.php');
                include_once('permissions.php');
                
                // Configuration PDO sécurisée
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_FOUND_ROWS => true
                ];
                
                $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname;charset=utf8mb4", $dbuser, $dbpwd, $options);
                
                // Requête préparée pour éviter l'injection SQL - inclure la colonne validation
                $stmt = $pdo->prepare("SELECT idacount, username, pwd, first_name, last_name, mail, phone_number, depot, partner_email, validation FROM account WHERE LOWER(username) = LOWER(?) LIMIT 1");
                $stmt->execute([$username]);
                $user = $stmt->fetch();
                
                if ($user) {
                    // Vérifier d'abord si le compte est activé
                    if ($user['validation'] === 'deactivated') {
                        $error_message = "Your account is pending approval. Please contact an administrator or wait for your account to be validated.";
                    } elseif (password_verify($password, $user['pwd'])) {
                        // Connexion réussie
                        
                        // Régénération de l'ID de session après connexion
                        session_regenerate_id(true);
                        
                        // CORRECTION: Stockage sécurisé avec idacount
                        $_SESSION['user_id'] = $user['idacount'];
                        $_SESSION['username'] = htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8');
                        $_SESSION['first_name'] = htmlspecialchars($user['first_name'], ENT_QUOTES, 'UTF-8');
                        $_SESSION['last_name'] = htmlspecialchars($user['last_name'], ENT_QUOTES, 'UTF-8');
                        $_SESSION['mail'] = htmlspecialchars($user['mail'], ENT_QUOTES, 'UTF-8');
                        $_SESSION['depot'] = htmlspecialchars($user['depot'] ?? '', ENT_QUOTES, 'UTF-8');
                        $_SESSION['partner_email'] = htmlspecialchars($user['partner_email'] ?? '', ENT_QUOTES, 'UTF-8');
                        $_SESSION['logged_in'] = true;
                        $_SESSION['login_time'] = time();
                        $_SESSION['last_activity'] = time();
                        
                        // CORRECTION: Cookie sécurisé avec idacount
                        setcookie("user_id", $user['idacount'], time() + 3600, "/", "", false, true);
                        
                        // Nettoyage des tentatives de connexion
                        unset($_SESSION['login_attempts']);
                        
                        // CORRECTION: Redirection en fonction des permissions avec idacount
                        if (checkAdminPermission($user['idacount'])) {
                            header("Location: ./adminmenu/adminmenu.php");
                        } else {
                            header("Location: ./techmenu/techmenu.php");
                        }
                        exit();
                    } else {
                        // Mot de passe incorrect
                        $error_message = "Invalid username or password.";
                    }
                } else {
                    $error_message = "Invalid username or password.";
                }
                
                // Enregistrement de la tentative de connexion par IP
                if (!empty($error_message)) {
                    if (!isset($_SESSION['login_attempts'])) {
                        $_SESSION['login_attempts'] = [];
                    }
                    $_SESSION['login_attempts'][] = $current_time;
                }
                
            } catch (PDOException $e) {
                error_log("Database error: " . $e->getMessage());
                $error_message = "Database error: " . $e->getMessage(); // DEBUG TEMPORAIRE
            } catch (Exception $e) {
                error_log("Login error: " . $e->getMessage());
                $error_message = "Login error: " . $e->getMessage(); // DEBUG TEMPORAIRE
            }
        }
    }
    
    // Régénération du token CSRF après chaque tentative
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Nettoyage silencieux des sessions expirées
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $session_timeout = 3600;
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > $session_timeout) {
        // Session expirée - nettoyage silencieux
        session_unset();
        session_destroy();
        session_start();
        // Régénération du token CSRF pour la nouvelle session
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    } else {
        // Session encore valide - redirection
        $_SESSION['last_activity'] = time();
        include_once('permissions.php');
        if (isset($_SESSION['user_id']) && checkAdminPermission($_SESSION['user_id'])) {
            header("Location: ./adminmenu/adminmenu.php");
        } else {
            header("Location: ./techmenu/techmenu.php");
        }
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en"> <!-- By AGHILES CHAOUCHE 2025 ©-->

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - inv.ctiai.com</title>
    <meta name="robots" content="noindex, nofollow">
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
            display: flex;
            align-items: flex-start;
            justify-content: center;
            padding: 20px 20px 20px 20px;
            color: #333333;
            line-height: 1.6;
        }

        .logo {
            display: block;
            max-width: 200px;
            max-height: 120px;
            margin: 0 auto 30px auto;
            border-radius: 8px;
        }

        .container {
            width: 100%;
            max-width: 450px;
            position: relative;
            margin-top: 20px;
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

        label {
            display: block;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 15px;
        }

        input[type="text"],
        input[type="password"] {
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
        input[type="password"]::placeholder {
            color: #adb5bd;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
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
            margin-bottom: 20px;
            font-family: inherit;
            letter-spacing: 0.3px;
        }

        button:hover:not(:disabled) {
            background: #d11e21;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(235, 34, 38, 0.3);
        }

        button:active {
            transform: translateY(0);
        }

        button:disabled {
            background: #adb5bd;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .inscription {
            display: block;
            width: 100%;
            padding: 14px 16px;
            background: transparent;
            color: #eb2226;
            border: 2px solid #eb2226;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            text-align: center;
            text-decoration: none;
            transition: all 0.3s ease;
            font-family: inherit;
            letter-spacing: 0.3px;
        }

        .inscription:hover {
            background: #eb2226;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(235, 34, 38, 0.3);
        }

        .inscription:active {
            transform: translateY(0);
        }

        .error-message {
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

        .error-message::before {
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

        .security-info {
            margin-top: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 6px;
            font-size: 12px;
            color: #6c757d;
            text-align: center;
        }

        /* Focus visible pour accessibilité */
        input:focus-visible,
        button:focus-visible,
        .inscription:focus-visible {
            outline: 2px solid #0095e8;
            outline-offset: 2px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px 15px 15px 15px;
                align-items: flex-start;
            }
            
            .container {
                max-width: 100%;
                margin-top: 10px;
            }
            
            .login-container {
                padding: 40px 25px;
                border-radius: 8px;
            }
            
            h1 {
                font-size: 28px;
                margin-bottom: 35px;
            }
            
            .form-group {
                margin-bottom: 20px;
            }
            
            input[type="text"],
            input[type="password"] {
                padding: 14px 16px;
                font-size: 16px;
            }
            
            button {
                padding: 14px;
                font-size: 15px;
                margin-bottom: 15px;
            }
            
            .inscription {
                padding: 12px 16px;
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            body {
                padding: 10px 15px 15px 15px;
            }
            
            .container {
                margin-top: 5px;
            }
            
            .login-container {
                padding: 30px 20px;
                margin: 0 auto;
            }
            
            h1 {
                font-size: 24px;
                margin-bottom: 30px;
            }
        }

        @media (max-height: 700px) and (orientation: landscape) {
            body {
                padding: 10px;
                align-items: flex-start;
            }
            
            .container {
                margin-top: 5px;
            }
            
            .login-container {
                padding: 25px;
                margin: 10px 0;
            }
            
            h1 {
                margin-bottom: 25px;
                font-size: 24px;
            }
            
            .form-group {
                margin-bottom: 18px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="login-container">
            <img src="./image/airmagique_logo.png" alt="Air Magique Logo" class="logo">
            <h1>Login</h1>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo htmlspecialchars($error_message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message"><?php echo htmlspecialchars($success_message, ENT_QUOTES, 'UTF-8'); ?></div>
            <?php endif; ?>
            
            <form id="login-form" method="POST" action="" autocomplete="on">
                <!-- Token CSRF caché -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                
                <div class="form-group">
                    <label for="identifiant">Username</label>
                    <input type="text" 
                           id="identifiant" 
                           name="identifiant" 
                           placeholder="Enter your username" 
                           maxlength="50"
                           pattern="[a-zA-Z0-9_\-\.@]{3,50}"
                           autocomplete="username"
                           value="<?php echo isset($_POST['identifiant']) ? htmlspecialchars($_POST['identifiant'], ENT_QUOTES, 'UTF-8') : ''; ?>"
                           <?php echo $account_locked ? 'disabled' : 'required'; ?>>
                </div>
                
                <div class="form-group">
                    <label for="mot-de-passe">Password</label>
                    <input type="password" 
                           id="mot-de-passe" 
                           name="mdp" 
                           placeholder="Enter your password" 
                           maxlength="255"
                           minlength="7"
                           autocomplete="current-password"
                           <?php echo $account_locked ? 'disabled' : 'required'; ?>>
                </div>
                
                <button type="submit" 
                        name="envoi" 
                        id="login-btn"
                        <?php echo $account_locked ? 'disabled' : ''; ?>>
                    <?php echo $account_locked ? 'Account Locked' : 'Log in'; ?>
                </button>
                
                <a href="./inscription/inscription.php" class="inscription">Sign up</a>
            </form>
            
            <div class="security-info">
                <strong>Security Notice:</strong> This system is protected by advanced security measures. 
                All login attempts are monitored and logged.
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('login-form');
            const loginBtn = document.getElementById('login-btn');
            const usernameInput = document.getElementById('identifiant');
            const passwordInput = document.getElementById('mot-de-passe');

            // Protection contre l'auto-soumission
            let formSubmitted = false;

            loginForm.addEventListener('submit', function(event) {
                if (formSubmitted) {
                    event.preventDefault();
                    return false;
                }
                
                // Validation côté client
                const username = usernameInput.value.trim();
                const password = passwordInput.value;
                
                if (username.length < 3 || username.length > 50) {
                    alert('Username must be between 3 and 50 characters.');
                    event.preventDefault();
                    return false;
                }
                
                if (password.length < 7 || password.length > 255) {
                    alert('Password must be between 7 and 255 characters.');
                    event.preventDefault();
                    return false;
                }
                
                if (!/^[a-zA-Z0-9_\-\.@]+$/.test(username)) {
                    alert('Username contains invalid characters.');
                    event.preventDefault();
                    return false;
                }
                
                formSubmitted = true;
                loginBtn.classList.add('loading');
                loginBtn.textContent = 'Logging in...';
                loginBtn.disabled = true;
            });

            // Validation en temps réel
            usernameInput.addEventListener('input', function() {
                const username = this.value;
                if (username.length > 0 && !/^[a-zA-Z0-9_\-\.@]*$/.test(username)) {
                    this.setCustomValidity('Username can only contain letters, numbers, underscore, dash, dot, and @');
                } else {
                    this.setCustomValidity('');
                }
            });

            // Protection contre le copier-coller de scripts
            const inputs = document.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('paste', function(e) {
                    const pasteData = e.clipboardData.getData('text');
                    if (/<script|javascript:|data:|vbscript:/i.test(pasteData)) {
                        e.preventDefault();
                        alert('Invalid content detected and blocked.');
                    }
                });
                
                input.addEventListener('blur', function() {
                    const isValid = this.checkValidity();
                    if (!isValid && input.value !== '') {
                        input.style.borderColor = '#eb2226';
                    } else {
                        input.style.borderColor = '#ced4da';
                    }
                });
            });

            // Auto-logout après inactivité (côté client)
            let inactivityTimer;
            const inactivityTime = 30 * 60 * 1000; // 30 minutes

            function resetInactivityTimer() {
                clearTimeout(inactivityTimer);
                inactivityTimer = setTimeout(function() {
                    alert('You will be logged out due to inactivity.');
                    window.location.href = './logout.php';
                }, inactivityTime);
            }

            document.addEventListener('mousemove', resetInactivityTimer);
            document.addEventListener('keypress', resetInactivityTimer);
            resetInactivityTimer();
        });
    </script>
</body>
</html>