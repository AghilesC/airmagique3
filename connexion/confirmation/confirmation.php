<?php
include_once "../logincheck.php";
include "../../config.php";

// Définir le fuseau horaire de Montréal
date_default_timezone_set('America/Montreal');

$userId = $_SESSION['user_id'];
$userFullName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

// Récupérer le nombre de work orders complétés
$completedWorkOrders = 0;
$connexion = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);
if ($connexion) {
    $query = "SELECT completed_wo FROM account WHERE LOWER(username) = LOWER(?)";
    $stmt = mysqli_prepare($connexion, $query);
    mysqli_stmt_bind_param($stmt, "s", $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        $completedWorkOrders = intval($row['completed_wo']);
    }
    
    mysqli_stmt_close($stmt);
    mysqli_close($connexion);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation - inv.ctiai.com</title>
    
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

        .confirmation-section {
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .background-logo {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.05;
            max-width: 400px;
            max-height: 400px;
            z-index: 1;
            pointer-events: none;
        }

        .content-wrapper {
            position: relative;
            z-index: 2;
        }

        .animated-robot {
            width: 160px;
            height: 120px;
            margin: 0 auto 15px;
            opacity: 0.8;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-10px);
            }
        }

        .confirmation-title {
            color: #2c3e50;
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .user-name {
            color: #eb2226;
            font-size: 24px;
            font-weight: 500;
            margin-bottom: 15px;
        }

        .confirmation-message {
            color: #6c757d;
            font-size: 16px;
            margin-bottom: 25px;
            line-height: 1.6;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 15px;
            max-width: 300px;
            margin: 30px auto 0;
        }

        .btn {
            padding: 16px 32px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            font-family: inherit;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            text-align: center;
            min-height: 56px;
            letter-spacing: 0.3px;
        }

        .btn i {
            font-size: 18px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #eb2226, #d11e21);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(235, 34, 38, 0.3);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, #d11e21, #bb1a1d);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(235, 34, 38, 0.4);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #6c757d, #5a6268);
            color: #ffffff;
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, #5a6268, #495057);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(108, 117, 125, 0.4);
        }

        .stats-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 25px;
            border: 1px solid #e9ecef;
        }

        .stats-title {
            color: #2c3e50;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            text-align: center;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 20px;
        }

        .stat-item {
            text-align: center;
            padding: 12px;
            background: white;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .stat-number {
            color: #eb2226;
            font-size: 24px;
            font-weight: 700;
            display: block;
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6c757d;
            font-size: 12px;
            font-weight: 500;
            line-height: 1.3;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .container {
                padding: 10px;
            }

            .confirmation-section {
                padding: 15px 20px;
            }

            .confirmation-title {
                font-size: 28px;
            }

            .user-name {
                font-size: 20px;
            }

            .confirmation-message {
                font-size: 15px;
                margin-bottom: 30px;
            }

            .btn {
                padding: 14px 28px;
                font-size: 15px;
                min-height: 52px;
            }

            .success-icon {
                width: 70px;
                height: 70px;
                margin-bottom: 25px;
            }

            .success-icon i {
                font-size: 32px;
            }

            .animated-robot {
                width: 130px;
                height: 100px;
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
            }

            .stat-item {
                padding: 12px;
            }

            .stat-number {
                font-size: 20px;
            }

            .page-title {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .confirmation-section {
                padding: 12px 15px;
            }

            .confirmation-title {
                font-size: 24px;
                margin-bottom: 12px;
            }

            .user-name {
                font-size: 18px;
                margin-bottom: 20px;
            }

            .confirmation-message {
                font-size: 14px;
                margin-bottom: 25px;
            }

            .btn {
                padding: 12px 24px;
                font-size: 14px;
                min-height: 48px;
            }

            .success-icon {
                width: 60px;
                height: 60px;
                margin-bottom: 20px;
            }

            .success-icon i {
                font-size: 28px;
            }

            .stats-section {
                padding: 20px;
                margin-top: 30px;
            }

            .stats-title {
                font-size: 16px;
                margin-bottom: 15px;
            }

            .stat-item {
                padding: 10px;
            }

            .stat-number {
                font-size: 18px;
            }

            .stat-label {
                font-size: 11px;
            }

            .page-title {
                font-size: 20px;
            }
        }

        @media (max-height: 600px) and (orientation: landscape) {
            body {
                padding: 10px;
            }

            .confirmation-section {
                padding: 20px;
            }

            .success-icon {
                width: 50px;
                height: 50px;
                margin-bottom: 15px;
            }

            .success-icon i {
                font-size: 24px;
            }

            .confirmation-title {
                font-size: 22px;
                margin-bottom: 10px;
            }

            .user-name {
                font-size: 16px;
                margin-bottom: 15px;
            }

            .animated-robot {
                width: 80px;
                height: 80px;
                margin-bottom: 15px;
            }

            .stats-section {
                padding: 15px;
                margin-top: 20px;
            }

            .stats-title {
                font-size: 16px;
                margin-bottom: 10px;
            }

            .stat-item {
                padding: 8px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <a href="../techmenu/techmenu.php" class="back-arrow" title="Back to menu">Back to Menu</a>

        <div class="confirmation-section">
            <!-- Background Logo -->
            <img src="../image/ctiai_logo.png" alt="Background Logo" class="background-logo">
            
            <div class="content-wrapper">
                <!-- Animated Robot -->
                <img src="../image/Clim.gif" alt="Success Animation" class="animated-robot">

                <!-- Confirmation Messages -->
                <h1 class="confirmation-title">Thank You</h1>
                <h2 class="user-name"><?php echo htmlspecialchars($userFullName, ENT_QUOTES, 'UTF-8'); ?></h2>
                
                <p class="confirmation-message">
                    Your work order has been successfully submitted and processed. 
                    The document has been generated and sent to the appropriate parties.
                </p>

                <!-- Session Summary -->
                <div class="stats-section">
                    <div class="stats-title">Session Summary</div>
                    <div class="stats-grid">
                        <div class="stat-item">
                            <span class="stat-number"><?php echo $completedWorkOrders; ?></span>
                            <div class="stat-label">Work Order<br>Completed</div>
                        </div>
                        <div class="stat-item">
                            <span class="stat-number"><?php echo date('M d'); ?></span>
                            <div class="stat-label">Date<br>Submitted</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Animation d'entrée pour les éléments
        document.addEventListener('DOMContentLoaded', function() {
            // Animation pour les boutons
            const buttons = document.querySelectorAll('.btn');
            buttons.forEach((button, index) => {
                button.style.opacity = '0';
                button.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    button.style.transition = 'all 0.6s ease';
                    button.style.opacity = '1';
                    button.style.transform = 'translateY(0)';
                }, 100 * (index + 1));
            });

            // Animation pour la section stats
            setTimeout(() => {
                const statsSection = document.querySelector('.stats-section');
                statsSection.style.opacity = '0';
                statsSection.style.transform = 'translateY(20px)';
                statsSection.style.transition = 'all 0.6s ease';
                
                setTimeout(() => {
                    statsSection.style.opacity = '1';
                    statsSection.style.transform = 'translateY(0)';
                }, 100);
            }, 800);
        });

        // Effet de confetti simplifié
        function createConfetti() {
            const colors = ['#eb2226', '#28a745', '#ffc107', '#17a2b8'];
            const confettiCount = 30;
            
            for (let i = 0; i < confettiCount; i++) {
                const confetti = document.createElement('div');
                confetti.style.position = 'fixed';
                confetti.style.left = Math.random() * 100 + 'vw';
                confetti.style.top = '-10px';
                confetti.style.width = '8px';
                confetti.style.height = '8px';
                confetti.style.backgroundColor = colors[Math.floor(Math.random() * colors.length)];
                confetti.style.borderRadius = '50%';
                confetti.style.pointerEvents = 'none';
                confetti.style.zIndex = '9999';
                confetti.style.animation = `fall ${Math.random() * 3 + 2}s linear forwards`;
                
                document.body.appendChild(confetti);
                
                setTimeout(() => {
                    confetti.remove();
                }, 5000);
            }
        }

        // CSS pour l'animation de chute
        const style = document.createElement('style');
        style.textContent = `
            @keyframes fall {
                to {
                    transform: translateY(100vh) rotate(360deg);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);

        // Déclencher les confettis après 1 seconde
        setTimeout(createConfetti, 1000);
    </script>
</body>
</html>