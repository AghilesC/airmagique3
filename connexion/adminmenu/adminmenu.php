<?php
include_once "../permissions.php";
include_once "../logincheck.php";

// Vérifier si l'utilisateur est connecté et a la permission d'accéder à adminmenu.php
if (!isset($_SESSION['user_id']) || !checkAdminPermission($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];
$userFullName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

// Compter les nouveaux comptes en attente de validation
include "../../config.php";
$connexion = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);
if ($connexion) {
    $query = "SELECT COUNT(*) as pending_count FROM account WHERE isNew = 'yes'";
    $result = mysqli_query($connexion, $query);
    $row = mysqli_fetch_assoc($result);
    $pendingCount = $row['pending_count'];
    mysqli_close($connexion);
} else {
    $pendingCount = 0;
}
?>

<!DOCTYPE html>
<html lang="en"> <!-- By AGHILES CHAOUCHE 2025 ©-->

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Menu - inv.ctiai.com</title>
    
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
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .container {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 40px;
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
        }

        .logo {
            max-width: 350px;
            max-height: 200px;
            margin-bottom: 15px;
            border-radius: 8px;
        }

        .welcome-title {
            color: #2c3e50;
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 10px;
            letter-spacing: -0.5px;
        }

        .welcome-subtitle {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 500;
            margin-bottom: 10px;
        }

        .admin-badge {
            display: inline-block;
            background: linear-gradient(135deg, #e82226, #c91e21);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 10px;
            box-shadow: 0 2px 8px rgba(232, 34, 38, 0.3);
        }

        .menu-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .menu-item {
            background: #ffffff;
            border-radius: 12px;
            padding: 25px;
            text-decoration: none;
            color: inherit;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .menu-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: #e82226;
            transition: width 0.3s ease;
        }

        .menu-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.2), 0 4px 16px rgba(0, 0, 0, 0.1);
        }

        .menu-item:hover::before {
            width: 8px;
        }

        .menu-item-title {
            color: #2c3e50;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            position: relative;
        }

        .title-with-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .menu-item-description {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.5;
        }

        .menu-item-icon {
            width: 24px;
            height: 24px;
            background: #e82226;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            flex-shrink: 0;
            font-size: 12px;
        }

        /* Badge de notification */
        .notification-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
            animation: pulse 2s infinite;
            margin-left: 8px;
            flex-shrink: 0;
        }

        .notification-badge.zero {
            display: none;
        }

        @keyframes pulse {
            0% {
                transform: scale(1);
                box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
            }
            50% {
                transform: scale(1.1);
                box-shadow: 0 4px 16px rgba(220, 53, 69, 0.6);
            }
            100% {
                transform: scale(1);
                box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
            }
        }

        /* Icônes spéciales pour les fonctions admin */
        .icon-download { background: #e82226; }
        .icon-users { background: #e82226; }
        .icon-database { background: #e82226; color: white; }
        .icon-edit { background: #e82226; }
        .icon-calendar { background: #e82226; }

        .logout-section {
            text-align: center;
            margin-top: 40px;
        }

        .logout-button {
            display: inline-block;
            padding: 16px 32px;
            background: #dc3545;
            color: #ffffff;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-family: inherit;
            letter-spacing: 0.3px;
        }

        .logout-button:hover {
            background: #c82333;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
        }

        .logout-button:active {
            transform: translateY(0);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .container {
                padding: 10px;
            }

            .header {
                padding: 20px 15px;
                margin-bottom: 30px;
            }

            .logo {
                max-width: 200px;
                max-height: 120px;
            }

            .welcome-title {
                font-size: 26px;
                margin-bottom: 8px;
            }

            .welcome-subtitle {
                font-size: 20px;
            }

            .menu-grid {
                grid-template-columns: 1fr;
                gap: 15px;
                margin-bottom: 25px;
            }

            .menu-item {
                padding: 20px;
            }

            .menu-item-title {
                font-size: 18px;
            }

            .menu-item-description {
                font-size: 13px;
            }

            .logout-button {
                padding: 14px 28px;
                font-size: 15px;
            }

            .notification-badge {
                width: 18px;
                height: 18px;
                font-size: 10px;
                margin-left: 6px;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 15px 10px;
            }

            .welcome-title {
                font-size: 22px;
            }

            .welcome-subtitle {
                font-size: 18px;
            }

            .menu-item {
                padding: 15px;
            }

            .menu-item-title {
                font-size: 16px;
                gap: 8px;
            }

            .menu-item-icon {
                width: 20px;
                height: 20px;
            }

            .notification-badge {
                width: 18px;
                height: 18px;
                font-size: 10px;
                top: -5px;
                right: -5px;
            }
        }

        @media (max-height: 600px) and (orientation: landscape) {
            body {
                padding: 10px;
            }

            .container {
                padding: 10px;
            }

            .header {
                padding: 15px;
                margin-bottom: 20px;
            }

            .logo {
                max-width: 150px;
                max-height: 90px;
                margin-bottom: 10px;
            }

            .welcome-title {
                font-size: 24px;
                margin-bottom: 5px;
            }

            .welcome-subtitle {
                font-size: 18px;
            }

            .menu-item {
                padding: 15px;
            }

            .logout-section {
                margin-top: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <img src="../image/airmagique_logo.png" alt="AIR MAGIQUE Logo" class="logo">
            <h2 class="welcome-subtitle"><?php echo htmlspecialchars($userFullName, ENT_QUOTES, 'UTF-8'); ?></h2>
            <div class="admin-badge">Administrator</div>
        </div>

        <!-- Menu Grid -->
        <div class="menu-grid">
            <a href="../validationcompte/validationcompte.php" class="menu-item">
                <div class="menu-item-icon icon-users"><i class="fas fa-user-check"></i></div>
                <div class="menu-item-title">
                    <span class="title-with-badge">
                        Account Management
                        <span class="notification-badge <?php echo $pendingCount == 0 ? 'zero' : ''; ?>" id="pendingBadge">
                            <?php echo $pendingCount; ?>
                        </span>
                    </span>
                </div>
                <div class="menu-item-description">Activate / Deactivate user accounts</div>
            </a>

            <a href="../modifaccount/modifaccount.php" class="menu-item">
                <div class="menu-item-icon icon-edit"><i class="fas fa-user-edit"></i></div>
                <div class="menu-item-title">Edit Accounts</div>
                <div class="menu-item-description">Modify existing user accounts</div>
            </a>

            <a href="../calendar/calendar.php" class="menu-item">
                <div class="menu-item-icon icon-calendar"><i class="fas fa-calendar-alt"></i></div>
                <div class="menu-item-title">Calendar</div>
                <div class="menu-item-description">Manage events and scheduling</div>
            </a>
        </div>

        <!-- Logout Section -->
        <div class="logout-section">
            <a href="?logout" class="logout-button">Logout</a>
        </div>
    </div>

    <script>
        // Fonction pour actualiser le compteur de comptes en attente
        function updatePendingCount() {
            fetch('get_pending_count.php')
                .then(response => response.json())
                .then(data => {
                    const badge = document.getElementById('pendingBadge');
                    badge.textContent = data.count;
                    
                    if (data.count == 0) {
                        badge.classList.add('zero');
                    } else {
                        badge.classList.remove('zero');
                    }
                })
                .catch(error => {
                    console.error('Error updating pending count:', error);
                });
        }

        // Actualiser le compteur toutes les 30 secondes
        setInterval(updatePendingCount, 30000);

        // Actualiser aussi quand la page redevient visible
        document.addEventListener('visibilitychange', function() {
            if (!document.hidden) {
                updatePendingCount();
            }
        });
    </script>
</body>

</html>