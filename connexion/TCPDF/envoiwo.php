<?php
require '../phpspreadsheet/vendor/autoload.php';
include_once "../logincheck.php";
$userId = $_SESSION['user_id'];
$userFullName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (isset($_GET['pdf']) && isset($_GET['glpi_ticket_id']) && isset($_GET['emailtech'])) {
    $pdfFileName = $_GET['pdf'];
    $glpiTicketID = $_GET['glpi_ticket_id'];
    $emailtech = $_GET['emailtech'];  
    $emailpartner = $_GET['emailpartner'];  
    $clientName = isset($_GET['client_name']) ? $_GET['client_name'] : '';
    $nbCustomer = isset($_GET['nb_customer']) ? $_GET['nb_customer'] : '';

    $pdfFileNameWithGLPI = "wo-$glpiTicketID.pdf";

    // Configuration SMTP pour email personnel (exemple avec Gmail)
    $smtpHost = 'smtp.gmail.com';
    $smtpUsername = 'aghilesaction@gmail.com'; // Remplacez par votre email
    $smtpPassword = 'ohbp lhml mrwa bcqu'; // Mot de passe d'application Gmail
    $smtpPort = 587;

    // Email de destination (votre autre email personnel)
    $toEmail = 'aghilesaction@gmail.com'; // Remplacez par votre email de destination

    $subject = "WO $glpiTicketID - $clientName - $nbCustomer - $userFullName";
    
    $message = "Bonjour,\n\nWO #$glpiTicketID\nClient : $clientName\nLocalisation : $nbCustomer\nFermé par : $userFullName.\n\nCeci est un test du système de bon de travail.\n\nMerci et bonne journée !";

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUsername;
        $mail->Password = $smtpPassword;
        $mail->SMTPSecure = 'tls';
        $mail->Port = $smtpPort;

        $mail->setFrom($smtpUsername, 'WO System Test');
        $mail->addAddress($toEmail);
        
        // Optionnel : ajouter en copie vos emails de test
        // $mail->addCC($emailtech);
        // $mail->addCC($emailpartner);

        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->addAttachment($pdfFileName, $pdfFileNameWithGLPI);

        $mail->CharSet = 'UTF-8';

        $mail->send();
       
        header("Location: ../confirmation/confirmation.php");
        exit();
        
    } catch (Exception $e) {
        echo 'Erreur lors de l\'envoi de l\'email : ' . $mail->ErrorInfo;
    }
} else {
    echo 'Paramètres manquants pour l\'envoi de l\'email.';
}
?>

/* 
CONFIGURATION ALTERNATIVE POUR OUTLOOK/HOTMAIL :
$smtpHost = 'smtp-mail.outlook.com';
$smtpPort = 587;

CONFIGURATION ALTERNATIVE POUR YAHOO :
$smtpHost = 'smtp.mail.yahoo.com';
$smtpPort = 587;

NOTES IMPORTANTES :
1. Pour Gmail : Activez l'authentification à 2 facteurs et créez un mot de passe d'application
2. Pour Outlook : Utilisez vos identifiants normaux
3. Remplacez les emails par vos vrais emails personnels
4. Testez d'abord sans pièce jointe pour vérifier la connexion SMTP
*/