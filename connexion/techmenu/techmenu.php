<?php
include_once "../logincheck.php";

$userId = $_SESSION['user_id'];
$userFullName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
?>

<!DOCTYPE html>
<html lang="en"> <!-- By AGHILES CHAOUCHE 2025 ©-->

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tech Menu - inv.ctiai.com</title>
    
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

            display: flex;               /* Centrage */
            justify-content: center;     /* Horizontal */
            align-items: center;         /* Vertical */
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

        .tech-badge {
            display: inline-block;
            background: linear-gradient(135deg, #0090e0, #007bb5);
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            margin-top: 10px;
            box-shadow: 0 2px 8px rgba(0, 144, 224, 0.3);
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
            background: #0090e0;
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
        }

        .menu-item-description {
            color: #6c757d;
            font-size: 14px;
            line-height: 1.5;
        }

        .menu-item-icon {
            width: 24px;
            height: 24px;
            background: #0090e0;
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            flex-shrink: 0;
            font-size: 12px;
        }

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
            <div class="tech-badge">Technician</div>
        </div>

        <!-- Menu Grid -->
        <div class="menu-grid">
            <a href="../TCPDF/confirmationpdf.php" class="menu-item">
                <div class="menu-item-icon"><i class="fas fa-clipboard-list"></i></div>
                <div class="menu-item-title">Work Order</div>
                <div class="menu-item-description">Generate and send work orders</div>
            </a>

            <a href="../calendar/calendartech.php" class="menu-item">
                <div class="menu-item-icon"><i class="fas fa-calendar-alt"></i></div>
                <div class="menu-item-title">Calendar</div>
                <div class="menu-item-description">View your intervention schedules</div>
            </a>

            <a href="../parameter/parameter.php" class="menu-item">
                <div class="menu-item-icon"><i class="fas fa-cog"></i></div>
                <div class="menu-item-title">Settings</div>
                <div class="menu-item-description">Configure system parameters</div>
            </a>
        </div>

        <!-- Logout Section -->
        <div class="logout-section">
            <a href="?logout" class="logout-button">Logout</a>
        </div>
    </div>
</body>

</html>