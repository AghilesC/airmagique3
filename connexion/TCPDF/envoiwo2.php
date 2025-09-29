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
    $draft_id = isset($_GET['draft_id']) ? $_GET['draft_id'] : '';
    $pdfFileNameWithGLPI = "wo-$glpiTicketID.pdf";

    // $smtpHost = 'smtp.office365.com';
    // $smtpUsername = 'wo-automatic@ctiai.com';
    // $smtpPassword = '4MX39kMHMjnQvQCVvHFH';
    // $smtpPort = 587;

    // $toEmail = 'service@ctiai.com';

    $subject = "WO $glpiTicketID - $clientName - $nbCustomer - $userFullName";
    
    $message = "Bonjour,\n\nWO #$glpiTicketID\nClient : $clientName\nLocalisation : $nbCustomer\nFermé par : $userFullName.\n\nMerci et bonne journée !";

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUsername;
        $mail->Password = $smtpPassword;
        $mail->SMTPSecure = 'tls';
        $mail->Port = $smtpPort;

        $mail->setFrom($smtpUsername, 'WO Automatic');
        $mail->addAddress($toEmail);
        

        $mail->addCC($emailtech);
        $mail->addCC($emailpartner);


        $mail->Subject = $subject;
        $mail->Body = $message;

        $mail->addAttachment($pdfFileName, $pdfFileNameWithGLPI);

        $mail->CharSet = 'UTF-8';

        $mail->send();
       

        header("Location: ../confirmation/confirmation2.php?draft_id=" . $draft_id);
        exit();
        
        echo '';
        
    } catch (Exception $e) {
        echo 'Erreur lors de l\'envoi de l\'email : ' . $mail->ErrorInfo;
    }
} else {
    echo '';
}
?>