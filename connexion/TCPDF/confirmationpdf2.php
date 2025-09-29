<?php
require('tcpdf.php');
include "../../config.php";

include_once "../logincheck.php";
$mail = $_SESSION['mail'];
$partner_email = $_SESSION['partner_email'];
$userId = $_SESSION['user_id'];
$userFullName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];


// Ensure `draft_id` is passed via URL
if (isset($_GET['draft_id']) && !empty($_GET['draft_id'])) {
    $draft_id = intval($_GET['draft_id']); 
} else {
    die("❌ No draft selected. Please go back and choose a draft.");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if(isset($_POST['glpi_ticket_id'])) {

        $_SESSION['glpi_ticket_id'] = $_POST['glpi_ticket_id'];
    }
    
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $draft_id = isset($_POST['draft_id']) ? intval($_POST['draft_id']) : 0;

    if (isset($_POST['equipement']) && empty($_POST['equipement'])) {
        
            header("Location: ../confirmation/confirmation2.php?draft_id=" . $draft_id);
            exit();
            
    } else {
        $_SESSION['equipement_selected'] = $_POST['equipement'];
    }
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $pdf = new TCPDF();
    $pdf->AddPage();


    $logoPath = '../image/ctiai_logo.png';
    $pdf->Image($logoPath, 10, 10, 25, 0, 'PNG');


    $pdf->SetXY($pdf->getPageWidth() - 50, 12);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'GLPI #:', 0, 1);


    $pdf->SetXY($pdf->getPageWidth() - 50, 17);
    $pdf->SetFont('helvetica', '', 9);
    $glpiTicketID = isset($_POST['glpi_ticket_id']) ? $_POST['glpi_ticket_id'] : '';
    $pdf->Cell(0, 10, $glpiTicketID, 0, 1);


    $pdf->SetXY($pdf->getPageWidth() - 50, 22);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 10, 'Store/Magasin #:', 0, 1);


    $pdf->SetXY($pdf->getPageWidth() - 50, 27);
    $pdf->SetFont('helvetica', '', 9);
    $glpiTicketID = isset($_POST['nb_customer']) ? $_POST['nb_customer'] : '';
    $pdf->Cell(0, 10, $glpiTicketID, 0, 1);





    $client_name = isset($_POST['client_name']) ? $_POST['client_name'] : '';
    $contact_name = isset($_POST['contact_name']) ? $_POST['contact_name'] : '';
    $work_address = isset($_POST['work_address']) ? $_POST['work_address'] : '';
    $phone_number = isset($_POST['phone_number']) ? $_POST['phone_number'] : '';
    $technician_name = isset($_POST['technician_name']) ? $_POST['technician_name'] : '';
    $billingOption = isset($_POST['billing_option']) ? $_POST['billing_option'] : '';
    $description = isset($_POST['description']) ? $_POST['description'] : '';
    $signatureImageData = isset($_POST['signature']) ? $_POST['signature'] : '';
    $signatureImageData2 = isset($_POST['signature2']) ? $_POST['signature2'] : '';
    $emailtech = isset($_POST['emailtech']) ? $_POST['emailtech'] : ''; 
    $emailpartner = isset($_POST['emailpartner']) ? $_POST['emailpartner'] : ''; 
    $equipement = isset($_POST['equipement']) ? $_POST['equipement'] : array();
    $work_date = isset($_POST['date']) ? $_POST['date'] : '';

    // Convertir les équipements sélectionnés en une chaîne séparée par des virgules
    $equipementText = implode(', ', $equipement);
    



 


    $depart_bureau_time = isset($_POST['depart_bureau_time']) ? $_POST['depart_bureau_time'] : '';
    $arrive_site_time = isset($_POST['arrive_site_time']) ? $_POST['arrive_site_time'] : '';
    $depart_site_time = isset($_POST['depart_site_time']) ? $_POST['depart_site_time'] : '';
    $arrive_bureau_time = isset($_POST['arrive_bureau_time']) ? $_POST['arrive_bureau_time'] : '';
    


list($depart_bureau_hour, $depart_bureau_minute, $depart_bureau_period) = sscanf($depart_bureau_time, "%d:%d %s");
list($arrive_site_hour, $arrive_site_minute, $arrive_site_period) = sscanf($arrive_site_time, "%d:%d %s");
list($depart_site_hour, $depart_site_minute, $depart_site_period) = sscanf($depart_site_time, "%d:%d %s");
list($arrive_bureau_hour, $arrive_bureau_minute, $arrive_bureau_period) = sscanf($arrive_bureau_time, "%d:%d %s");





    $km = isset($_POST['km']) ? $_POST['km'] : '';


    $signatureImageData = isset($_POST['signature']) ? $_POST['signature'] : '';




    $pdf->SetFont('aealarabiya', 'B', 10.5);
    $pdf->SetTextColor(0, 0, 0);

    $pdf->SetY(12);
    $pdf->SetX(35);
    

    $pdf->SetFont('aealarabiya', '', 12);

    $pdf->Cell(0, 7, "2294, 32e avenue, Lachine", 0, 1);

    $pdf->SetY($pdf->GetY() - 1);
    $pdf->SetX(35);

    $pdf->Cell(0, 7, "QC, H8T 3H4", 0, 1);

    $pdf->SetY($pdf->GetY() - 2);
    $pdf->SetX(35);

    $pdf->Cell(0, 10, "(514)-587 8300", 0, 1);

    $pdf->SetY($pdf->GetY() - 4);
    $pdf->SetX(35);

    $pdf->Cell(0, 10, "www.ctiai.com", 0, 1);



    $pdf->SetY($pdf->GetY() + 5);

    $pdf->SetFont('times', 'B', 25);
    $pdf->Cell(0, 10, 'Work order/Bon de travail', 0, 1, 'C');

    $pdf->SetY($pdf->GetY() + 7);




    $logoPath = '../image/ctiai_logo_invisible2.png'; 
    $logoWidth = 170; 
    $logoHeight = 170; 
    $opacity = 0.3; 

    $pdf->Image($logoPath, ($pdf->getPageWidth() - $logoWidth) / 2, ($pdf->getPageHeight() - $logoHeight) / 2, $logoWidth, $logoHeight, 'PNG', '', '', 0, false, 'C', false);
    $pdf->SetAlpha(1); 


$maxLabelWidth = max(
    $pdf->GetStringWidth('Work order/Bon de travail'),
    $pdf->GetStringWidth('Work order/Bon de travail'),

);


$pdf->SetFont('dejavusans', 'B', 11);

$pdf->SetFont('dejavusans', 'B', 11);
$labelWidth = $pdf->GetStringWidth('Name technician/Nom technicien : ');
$pdf->Cell($labelWidth, 10, 'Name technician/Nom technicien : ', 0, 0);
$pdf->SetFont('dejavusansi', '', 11);
$pdf->Cell(0, 10, $userFullName, 0, 1);

    $pdf->SetY($pdf->GetY() - 5 );
    

$pdf->SetFont('dejavusans', 'B', 11);
$labelWidth = $pdf->GetStringWidth('Contact name/Nom contact : ');
$pdf->Cell($labelWidth, 10, 'Contact name/Nom contact : ', 0, 0);
$pdf->SetFont('dejavusansi', '', 11);
$pdf->Cell(0, 10, $contact_name, 0, 1);

    $pdf->SetFont('aealarabiya', 'B', 10.5);


    $pdf->SetFont('dejavusans', 'B', 9);
    $pdf->Cell($maxLabelWidth, 10, 'Customer company name/Nom compagnie du client:  ', 1, 0);
    $pdf->SetFont('dejavusansi', '', 9);
    $pdf->Cell(0, 10, $client_name, 1, 1);
    
    $pdf->SetFont('dejavusans', 'B', 9);
    $pdf->Cell($maxLabelWidth, 10, 'Work address/Adresse des travaux  ', 1, 0);
    $pdf->SetFont('dejavusansi', '', 8);
    $pdf->Cell(0, 10, $work_address, 1, 1);
    
    $pdf->SetFont('dejavusans', 'B', 9);
    $pdf->Cell($maxLabelWidth, 10, 'Customer phone number/Téléphone client  ', 1, 0);
    $pdf->SetFont('dejavusansi', '', 9);
    $pdf->Cell(0, 10, $phone_number, 1, 1);
    
// Assurez-vous que la date est correctement formatée et non vide


$pdf->SetFont('dejavusans', 'B', 9);
$pdf->Cell($maxLabelWidth, 10, 'Work date/Date travaux ', 1, 0);
$pdf->SetFont('dejavusansi', '', 9);
$pdf->Cell(0, 10, $work_date, 1, 1);


    
    $pdf->SetFont('dejavusans', 'B', 9);
    $pdf->Cell($maxLabelWidth, 10, 'Office departure/Départ bureau  ', 1, 0);
    $pdf->SetFont('dejavusansi', '', 9);
    $pdf->Cell(0, 10, sprintf("%02d:%02d %s", $depart_bureau_hour, $depart_bureau_minute, $depart_bureau_period), 1, 1);
    
    $pdf->SetFont('dejavusans', 'B', 9);
    $pdf->Cell($maxLabelWidth, 10, 'Arrived on site/Arrivé sur site  ', 1, 0);
    $pdf->SetFont('dejavusansi', '', 9);
    $pdf->Cell(0, 10, sprintf("%02d:%02d %s", $arrive_site_hour, $arrive_site_minute, $arrive_site_period), 1, 1);
    
    $pdf->SetFont('dejavusans', 'B', 9);
    $pdf->Cell($maxLabelWidth, 10, 'Site departure/Départ du site  ', 1, 0);
    $pdf->SetFont('dejavusansi', '', 9);
    $pdf->Cell(0, 10, sprintf("%02d:%02d %s", $depart_site_hour, $depart_site_minute, $depart_site_period), 1, 1);
    
    $pdf->SetFont('dejavusans', 'B', 9);
    $pdf->Cell($maxLabelWidth, 10, 'Arrived office/Arrivé bureau  ', 1, 0);
    $pdf->SetFont('dejavusansi', '', 9);
    $pdf->Cell(0, 10, sprintf("%02d:%02d %s", $arrive_bureau_hour, $arrive_bureau_minute, $arrive_bureau_period), 1, 1);
    
    
    $pdf->SetFont('dejavusans', 'B', 9);
    $pdf->Cell($maxLabelWidth, 10, 'Kilometer traveled/Kilomètre parcouru  ', 1, 0);
    $pdf->SetFont('dejavusansi', '', 9);
    $pdf->Cell(0, 10, $km, 1, 1);


$pdf->SetFont('dejavusans', 'B', 9);
$labelWidthBillingOption = $pdf->GetStringWidth('Billing option/Option de facturation : ');
$pdf->Cell($labelWidthBillingOption, 10, 'Billing option/Option de facturation : ', 0, 0);
$pdf->SetFont('dejavusansi', '', 9);
$pdf->Cell(0, 10, $billingOption, 0, 1);





    $pdf->SetFont('aealarabiya', 'B', 10);
    $pdf->SetY($pdf->GetY() + -3);


    $descriptionWrapped = wordwrap($description, 120, "\n", true);
    $descriptionLines = explode("\n", $descriptionWrapped);
    $limitedDescription = implode("\n", array_slice($descriptionLines, 0, 14));
    
    $pdf->SetFont('dejavusans', 'BU', 10.5);
    $pdf->Cell(0, 10, 'Description :', 0, 1);
    $pdf->Ln(-2); 
    

    

    $pdf->SetFont('aealarabiya', '', 10.5);
    $pdf->MultiCell(0, 10, $limitedDescription, 0, 'L');
    $pdf->SetY($pdf->GetY() + 1);
    
    // Vérifier si la description comporte plus de 14 lignes
    $remainingDescription = implode("\n", array_slice($descriptionLines, 14));
    
    if (!empty($remainingDescription)) {
        // Commencer une nouvelle page et afficher le reste de la description
        $pdf->AddPage();
        $pdf->SetY($pdf->GetY() + 3);
        $pdf->SetFont('dejavusans', 'BU', 10.5);
        $pdf->Cell(0, 10, 'Description (continued/suite) :', 0, 1);
        $pdf->Ln(-2); 
        $pdf->SetFont('aealarabiya', '', 10.5);
        $pdf->MultiCell(0, 10, $remainingDescription, 0, 'L');
    }
    
    $pdf->SetFont('dejavusans', 'B', 9);
    $labelWidtheEquipmentUsed = $pdf->GetStringWidth('Equipment used / Equipement utilisé : ');
    $pdf->Cell($labelWidtheEquipmentUsed, 10, 'Equipment used / Equipement utilisé : ', 0, 0);
    $pdf->SetFont('dejavusansi', '', 9);
// Déplacez le point d'insertion du texte horizontalement pour l'aligner avec le texte précédent
$pdf->SetX($pdf->GetX() + $labelWidtheEquipmentUsed + -69); // Ajustez le décalage selon vos besoins

// Utilisez MultiCell pour afficher le texte des équipements sélectionnés
$pdf->MultiCell(0, 10, $equipementText, 0, 'L');










if (!empty($signatureImageData)) {



    $signatureImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $signatureImageData));


    $x = 40; 
    $y = 255;
    $width = 40; 
    $height = 20; 


    $pdf->Image('@' . $signatureImage, $x, $y, $width, $height);


        $pdf->SetXY($x, $y - 8); 
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell($width, 8, 'Signature : ' . $userFullName . '', 0, 1, 'C');
        
    

    
}



if (!empty($signatureImageData2)) {
    $signatureImage2 = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $signatureImageData2));

    $x2 = 110; 
    $y2 = 255; 
    $width2 = 40; 
    $height2 = 20; 
    $pdf->Image('@' . $signatureImage2, $x2, $y2, $width2, $height2);
}
 


    $pdf->SetXY($x2, $y2 - 8); 
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell($width2, 8, 'Signature : ' . $contact_name . '', 0, 1, 'C');


// Fonction pour compresser et redimensionner les images
function compressImage($source, $destination, $quality, $maxWidth, $maxHeight) {
    $info = getimagesize($source);

    if ($info['mime'] == 'image/jpeg') {
        $image = imagecreatefromjpeg($source);
    } elseif ($info['mime'] == 'image/png') {
        $image = imagecreatefrompng($source);
    } else {
        return false; // Format non supporté
    }

    // Obtenir dimensions originales
    $width = imagesx($image);
    $height = imagesy($image);

    // Calculer le ratio de redimensionnement
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = floor($width * $ratio);
    $newHeight = floor($height * $ratio);

    // Créer une nouvelle image redimensionnée
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

    // Sauvegarde en JPEG
    imagejpeg($newImage, $destination, $quality);

    // Nettoyage
    imagedestroy($image);
    imagedestroy($newImage);

    return $destination;
}

// Vérifier si des fichiers ont été envoyés
if (!empty($_FILES['uploaded_files']['tmp_name'])) {
    foreach ($_FILES['uploaded_files']['tmp_name'] as $key => $tmp_name) {
        $originalImage = $_FILES['uploaded_files']['name'][$key];
        $uploadDir = 'uploads/';

        // Assurez-vous que le dossier existe
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $imagePath = $uploadDir . basename($originalImage);
        $compressedImagePath = $uploadDir . 'compressed_' . basename($originalImage);

        // Déplacer l'image originale vers le serveur
        move_uploaded_file($tmp_name, $imagePath);

        if (file_exists($imagePath) && is_readable($imagePath)) {
            // Compression et redimensionnement (qualité 50, max 1000x800 px)
            compressImage($imagePath, $compressedImagePath, 50, 1000, 800);

            // ✅ AJOUT D'UNE NOUVELLE PAGE POUR CHAQUE IMAGE
            $pdf->AddPage();

            // Positionnement centré
            $imageWidth = 150; // Largeur plus grande
            $imageHeight = 0;  // Hauteur ajustée automatiquement
            $x = ($pdf->getPageWidth() - $imageWidth) / 2;
            $y = 40; // Ajuste pour éviter d’être trop haut

            // Insérer l'image compressée sur une nouvelle page
            $pdf->Image($compressedImagePath, $x, $y, $imageWidth, $imageHeight, 'JPEG');

            // Supprimer les fichiers temporaires
            unlink($imagePath);
            unlink($compressedImagePath);
        }
    }
}



// Vérifier si la valeur glpi_ticket_id est définie dans la session
if (isset($_SESSION['glpi_ticket_id'])) {
    // Récupérer la valeur de glpi_ticket_id depuis la session
    $glpi_ticket_id = $_SESSION['glpi_ticket_id'];
} else {
    // Si la valeur n'est pas définie dans la session, initialiser à une chaîne vide
    $glpi_ticket_id = "";
}

// Vérifiez si les valeurs de la liste multiple sont présentes dans la variable de session
if(isset($_SESSION['equipement_selected'])) {
    $equipement_selected = $_SESSION['equipement_selected'];
} else {
    $equipement_selected = array(); // Définissez une valeur par défaut au cas où aucune valeur n'est stockée
}

$userFullName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$depotID = $_SESSION['depot'];

 $conn =  mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);


if ($conn->connect_error) {
    die("Échec de la connexion à la base de données : " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    $selectedEquipment = $_POST['equipement'];

    $glpiticketID = $_POST['ticket_id'];


    $partnerQuery = "SELECT * FROM partner WHERE partner_id = '$depotID'";
    $partnerResult = $conn->query($partnerQuery);

    if ($partnerResult->num_rows > 0) {

        $partnerRow = $partnerResult->fetch_assoc();
        $partnerAddress = $partnerRow['address'];

       
        $equipmentColumns = [
            "pos" => "pos",
            "thermal_printer" => "thermal_printer",
            "epc" => "epc",
            "Scanner" => "scanner",
            "ups" => "ups",
            "Cash drawer" => "cash_drawer",
            "site_controller" => "site_controller",
            "fuel_controller" => "fuel_controller",
            "hub_8_port" => "hub_8_port",
            "pinpad_cable" => "pinpad_cable",
            "scanner_cable" => "scanner_cable",
            "cash_drawer_cable" => "cash_drawer_cable",
            "server_pro" => "server_pro",
            "server_std" => "server_std",
            // "server_ssd" => "server_ssd",
            "pdu" => "pdu",
            "cisco_1121" => "cisco_1121",
            "bracket_router_cisco_1121" => "bracket_router_cisco_1121",
            "UPS1000" => "UPS1000",
            "cisco_9200_24t" => "cisco_9200_24t",
            "cisco_9200_48t" => "cisco_9200_48t",
            "viptela" => "viptela",
            "aruba" => "aruba",
            "switch_48_port" => "switch_48_port",
            "switch_24_port" => "switch_24_port",
            "bopc_hp" => "bopc_hp",
            "bopc_dell" => "bopc_dell",
            "bopc_pagnian" => "bopc_pagnian",
            "dp_to_hdmi" => "dp_to_hdmi",
            "lcd_monitor" =>  "lcd_monitor",
            "lexmark" => "lexmark",
            "display_19" => "display_19",
            "display_7" => "display_7",
            "lift_cpu" => "lift_cpu",
            "lift_power_bar" => "lift_power_bar",
            "dual_usb_6f" => "dual_usb_6f",
            "dual_usb_15f" => "dual_usb_15f",
            "adapter_rj45_splitter" => "adapter_rj45_splitter",
            "rj12_rj45_scanner" => "rj12_rj45_scanner",
            "rj12_coupler" => "rj12_coupler",
            "rj45_lift_cpu" => "rj45_lift_cpu",
            "rj12_rj45_pole_display" => "rj12_rj45_pole_display",
            "radiant_scanner_cable" => "radiant_scanner_cable",
            "dvi_vga" => "dvi_vga",
            "mount_pole_24i" => "mount_pole_24i",
            "mount_arm_pole" => "mount_arm_pole",
            "mount_flat_panel_pole" => "mount_flat_panel_pole",
            "mount_grommet" => "mount_grommet",
            "mount_homeplate" => "mount_homeplate",
            "scanner_db9_rj45" => "scanner_db9_rj45",
            "virtual_journal_db9_rj45" => "virtual_journal_db9_rj45",
            "pos_db9_rj45" => "pos_db9_rj45",
            "scanner_db9_db25" => "scanner_db9_db25"


        ];

            // Vérifiez si des équipements sont sélectionnés
    if (isset($_POST['equipement']) && !empty($_POST['equipement'])) {
        // Parcourez chaque équipement sélectionné
        foreach ($_POST['equipement'] as $selectedEquipment) {
            // Effectuez la soustraction de 1 uniquement si l'équipement est valide et la quantité est supérieure à -999
            if (array_key_exists($selectedEquipment, $equipmentColumns) && $partnerRow[$equipmentColumns[$selectedEquipment]] >= -999) {
                $newQuantity = $partnerRow[$equipmentColumns[$selectedEquipment]] - 1;
                $updateQuery = "UPDATE partner SET " . $equipmentColumns[$selectedEquipment] . " = $newQuantity WHERE partner_id = $depotID";

                if ($conn->query($updateQuery) === TRUE) {
                    $currentDateTime = date("Y-m-d H:i:s");
                    $insertQuery = $conn->prepare("INSERT INTO history (partner_id, tech_name, ticket_id, equipment, date, address) VALUES (?, ?, ?, ?, ?, ?)");
                    $insertQuery->bind_param("isssss", $depotID, $userFullName, $glpi_ticket_id, $selectedEquipment, $currentDateTime, $partnerAddress);

                    if ($insertQuery->execute()) {
                        // Continuez le traitement ou redirigez comme nécessaire
                    }
                }
            }
        }

    $pdfFileName = tempnam(sys_get_temp_dir(), 'workorder_');
    $pdf->Output($pdfFileName, 'F');


    $glpiTicketID = isset($_POST['glpi_ticket_id']) ? $_POST['glpi_ticket_id'] : '';
    $nbCustomer = isset($_POST['nb_customer']) ? $_POST['nb_customer'] : '';
    $clientName = isset($_POST['client_name']) ? $_POST['client_name'] : '';
    $draft_id = isset($_POST['draft_id']) ? intval($_POST['draft_id']) : 0;

    // Ajout du paramètre client_name à la chaîne de requête
    header("Location: envoiwo2.php?draft_id=$draft_id&pdf=$pdfFileName&glpi_ticket_id=$glpiTicketID&emailtech=$emailtech&emailpartner=$emailpartner&client_name=$clientName&nb_customer=$nbCustomer");
    exit;

    
}
}
}
}
//add this part for confirmationpdf2 then remove it (3)
//transfaring the draft table inputs from draft page(2)

if (isset($_GET['draft_id'])) {
    $draft_id =  intval($_GET['draft_id']);
    

    // Fetch draft data
    try {
        $pdo = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpwd);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    } catch (PDOException $e) {
        echo "Connection failed: " . $e->getMessage();
        exit;
    }
    
    $stmt = $pdo->prepare('SELECT * FROM draft WHERE id = :id');
    $stmt->execute(['id' => $draft_id]);
    $draft = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($draft) {
        // Populate form fields with draft data
        $glpi_ticket_id = $draft['glpi_ticket_id'];
        $client_name = $draft['client_name'];
        $nb_customer = $draft['nb_customer'];
        $contact_name = $draft['contact_name'];
        $work_address = $draft['work_address'];
        $phone_number = $draft['phone_number'];
        $date = $draft['date'];
        $depart_bureau_time = $draft['depart_bureau_time'];
        $arrive_site_time = $draft['arrive_site_time'];
        $depart_site_time = $draft['depart_site_time'];
        $arrive_bureau_time = $draft['arrive_bureau_time'];
        $km = $draft['km'];
        $description = $draft['description'];
        $equipment = $draft['equipment'];
        
    } else {
        echo "No draft found with ID: $draft_id";
        exit;
    }
} else {
    echo "No draft ID provided.";
    exit;
}
//(3)
?>
<!--(2)-->



<!DOCTYPE html>
<html lang="en"> <!-- By AGHILES CHAOUCHE 2023 ©
updated by wassim 2024 ©--> 
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>WO Online - inv.ctiai.com</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/1.5.3/signature_pad.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>


    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    
<!--add then remove php echoes -->
    <link rel="stylesheet" type="text/css" href="confirmationpdf.css">
</head>
<body>
<img src="../image/woo_logo.png" alt="Logo">
<form method="post" action="" enctype="multipart/form-data" id="loginform">


<label for="glpi_ticket_id">GLPI #/PO #:</label>
<input type="text" name="glpi_ticket_id" value="<?php echo htmlspecialchars($glpi_ticket_id); ?>" pattern="\d{10,}" title="The number must contain at least 10 digits minimum/Le numéro doit contenir au moins 10 chiffres minimum" minlength="10" required placeholder="required / requis">

        
        <label for="client_name">Nom de la compagnie du client :</label>
        <select id="client_name" required name="client_name" >
    <option value=" <?php echo  htmlspecialchars($client_name); ?>"  disabled selected>Choose company client/Choisir la compagnie client</option>
    <option value="Circle K/Couche-Tard" <?php if (isset($client_name) && $client_name === 'Circle K/Couche-Tard') echo $client_name; ?>>Circle K/Couche-Tard</option>
    <option value="CICNET" <?php if (isset($client_name) && $client_name === 'CICNET') echo $client_name; ?>>CICNET</option>
    <option value="Client divers" <?php if (isset($client_name) && $client_name === 'Client divers') echo $client_name; ?>>Client divers</option>
    <option value="Forget" <?php if (isset($client_name) && $client_name === 'Forget') echo $client_name; ?>>Forget</option>
    <option value="GoCo" <?php if (isset($client_name) && $client_name === 'GoCo') echo $client_name; ?>>GoCo</option>
    <option value="Simons" <?php if (isset($client_name) && $client_name === 'Simons') echo $client_name; ?>>Simons</option>
    <option value="TFI" <?php if (isset($client_name) && $client_name === 'TFI') echo $client_name; ?>>TFI</option>
</select>

        <br>
        <label for="nb_customer">Store/Magasin #:</label>
        <input type="text" name="nb_customer" id="nb_customer" value="<?php echo htmlspecialchars($nb_customer); ?>">


        

        <label for="technician_name">Name technician/Nom technicien: </label>
        <input type="text" name="name_technician" value="<?php echo $userFullName; ?>" readonly onfocus="this.blur()"><br>


        <label for="contact_name">Contact name/Nom du contact:</label>
        <input type="text" name="contact_name" required value=" <?php echo  htmlspecialchars($contact_name); ?>"  placeholder="required / requis"><br>

        <label for="work_address">Adresse des travaux :</label>
        <input type="text" name="work_address" id="work_address" value="<?php echo  htmlspecialchars($work_address); ?>" ><br>

        <label for="phone_number">Numéro de téléphone du client :</label>
         <input type="text" name="phone_number" id="phone_number" value="<?php echo  htmlspecialchars($phone_number); ?>"><br>

         <label for="date">Work date/Date travaux:</label>
<input type="text" id="date" name="date" required class="" value="<?php echo  htmlspecialchars($date); ?>" >




<label for="depart_bureau">Office departure/Départ bureau: </label>
<input type="text" id="depart_bureau_time" required name="depart_bureau_time" value="<?php echo  htmlspecialchars($depart_bureau_time); ?>"  placeholder="required / requis"><br>



<label for="arrive_site">Arrived on site/Arrivé sur site: </label>
<input type="text" id="arrive_site_time" required name="arrive_site_time" value="<?php echo  htmlspecialchars($arrive_site_time); ?>" placeholder="required / requis"><br>



<label for="depart_site">Site departure/Départ du site: </label>
<input type="text" id="depart_site_time" required name="depart_site_time" value="<?php echo  htmlspecialchars($depart_site_time); ?>"  placeholder="required / requis"><br>



<label for="arrive_bureau">Arrived office/Arrivé bureau: </label>
<input type="text" id="arrive_bureau_time" required name="arrive_bureau_time" value="<?php echo  htmlspecialchars($arrive_bureau_time); ?>"  placeholder="required / requis"><br>




        <label for="km">Kilometer traveled/Kilomètre parcouru:</label>
        <input type="number" name="km" required step="1" min="0" value="<?php echo  htmlspecialchars($km); ?>"  placeholder="required / requis"><br>


        <label for="under_contract">Under contract/Sous contrat:</label>
        <input type="radio" name="billing_option" required value="Under contract/Sous contrat" ><br>


        <label for="billable">Billable/Facturable:</label>
        <input type="radio" name="billing_option" required value="Billable/Facturable" ><br>


        <label for="description">Description:</label>
        <textarea name="description" required rows="10" cols="43" maxlength="1100" ><?php echo htmlspecialchars($description); ?></textarea>

        <div id="dynamic-dropdown-container">
        <label for="equipment">Equipment used / Equipement utilisé:</label>
        <select id="equipment" name="equipement[]" required multiple >
            <option value="">No equipment used/Pas d'équipment utilisé</option>
            <optgroup label="POS">
                <option value="pos">POS</option>
                <option value="thermal_printer">Thermal printer</option>
                <option value="epc">EPC</option>
                <option value="Scanner">Scanner</option>
                <option value="ups">UPS</option>
                <option value="Cash drawer">Cash drawer</option>
                <option value="site_controller">Site Controller</option>
                <option value="fuel_controller">Fuel Controller</option>
                <option value="hub_8_port">Network Hub 8 Port</option>
                <option value="pinpad_cable">Pinpad cable (Serial to RJ11)</option>
                <option value="scanner_cable">Scanner cable USB</option>
                <option value="cash_drawer_cable">Cash drawer cable</option>
                <option value="server_pro">vStore Server PRO</option>
                <option value="server_std">vStore Server STD</option>
                <!-- <option value="server_ssd">vStore Server SSD</option> -->
                <option value="pdu">PDU</option>
            </optgroup>

            <optgroup label="Network">
                <option value="cisco_1121">Cisco 1121</option>
                <option value="bracket_router_cisco_1121">Bracket router Cisco 1121</option>
                <option value="UPS1000">UPS1000</option>
                <option value="cisco_9200_24t">Cisco 9200-24T</option>
                <option value="cisco_9200_48t">Cisco 9200-48T</option>
                <option value="viptela">Cisco Viptela</option>
                <option value="aruba">Aruba</option>
                <option value="switch_48_port">Cisco 2960x switch 48 port</option>
                <option value="switch_24_port">Cisco 2960x switch 24 port</option>
            </optgroup>

            <optgroup label="BOPC">
                <option value="bopc_hp">BOPC HP</option>
                <option value="bopc_dell">BOPC DELL</option>
                <option value="bopc_pagnian">BOPC Pagnian</option>
                <option value="dp_to_hdmi">DP to HDMI</option>
                <option value="lcd_monitor">LCD Monitor</option>
                <option value="lexmark">Lexmark MX331</option>
            </optgroup>

            <optgroup label="Lift Core System Kit">
                <option value="display_19">Display, 19-Inch</option>
                <option value="display_7">Display, 7-Inch</option>
                <option value="lift_cpu">Lift CPU</option>
                <option value="lift_power_bar">Lift Power bar</option>
                <option value="dual_usb_6f">Cable, 6-foot dual USB</option>
                <option value="dual_usb_15f">Cable, 15-foot dual USB</option>
            </optgroup>

            <optgroup label="Lift Radiant POS">
                <option value="adapter_rj45_splitter">Adapter, RJ45 splitter for scanner</option>
                <option value="rj12_rj45_scanner">Cable, 3-foot RJ12/RJ45 for scanner</option>
                <option value="rj12_coupler">RJ12 coupler</option>
                <option value="rj45_lift_cpu">Cable, 6-foot RJ45 Adapter/Lift CPU</option>
                <option value="rj12_rj45_pole_display">Cable, 3-foot RJ12/RJ45 for pole display</option>
                <option value="radiant_scanner_cable">Radiant Serial Scanner cable</option>
                <option value="dvi_vga">DVI to VGA</option>
            </optgroup>

            <optgroup label="Lift Metal Hardware">
                <option value="mount_pole_24i">Mount, 24-inch pole</option>
                <option value="mount_arm_pole">Mount, flat panel articulating arm pole clamp</option>
                <option value="mount_flat_panel_pole">Mount, flat-panel pole clamps</option>
                <option value="mount_grommet">Mount, Grommet base</option>
                <option value="mount_homeplate">Mount, Homeplate base</option>
            </optgroup>

            <optgroup label="Bulloch Cable Kit">
                <option value="scanner_db9_rj45">Scanner DB9 to RJ45 Adapter</option>
                <option value="virtual_journal_db9_rj45">Virtual Journal DB9 to RJ45 Adapter</option>
                <option value="pos_db9_rj45">POS DB9 to RJ45 Adapter</option>
                <option value="scanner_db9_db25">Scanner DB9 to DB25r</option>
            </optgroup>
        </select>
    </div><br>




        <label for="signature">Technician signature:</label>
        <canvas id="signatureCanvas" width="300" height="150" style="border:1px solid #000;"> </canvas>
        <input type="hidden" name="signature" id="signatureInput" >
        <br>

        <button type="button" onclick="clearSignature()">Clear / Effacer</button><br><br>


        <label for="signature2">Client Signature:</label>
        <canvas id="signatureCanvas2" width="300" height="150" style="border:1px solid #000;"></canvas>
        <input type="hidden" name="signature2" id="signatureInput2">
        <br>

        <button type="button" onclick="clearSignature('signaturePad2')">Clear / Effacer</button><br><br>

        
        <label for="emailtech">Technician email/Email du technicien (For WO copy/Pour copie BdT): </label> 
        <input type="email" name="emailtech" required value="<?php echo $mail; ?>"  placeholder="required / requis" readonly> <br>

        <label for="emailpartner">Email partner/Email du partenaire (For WO copy/Pour copie BdT): </label> 
        <input type="email" name="emailpartner" required value="<?php echo $partner_email; ?>"  placeholder="required / requis" readonly> <br>
    <input type="hidden" name="draft_id" value="<?php echo htmlspecialchars($_GET['draft_id'] ?? ''); ?>">



    <label for="upload_files">Upload files/Téléverser des fichiers (JPG, JPEG):</label>
    <input type="file" name="uploaded_files[]" multiple><br>
    <input type="hidden" name="draft_id" value="<?php echo htmlspecialchars($_GET['draft_id'] ?? ''); ?>">

        <input type="submit" value="Send your WO / Envoyer votre BdT">

        <button type="button" onclick="window.location.href='../techmenu/techmenu.php';" class="mainpage-button">Go to main page / Aller sur la page principale</button>

        <script>
            

$(function() {

            var availableOptions = <?php
                include "../../config.php";

                $conn = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);

                if ($conn->connect_error) {
                    die("Connection failed: " . $conn->connect_error);
                }

                $sql = "SELECT store_number FROM store";
                $result = $conn->query($sql);

                $options = array();
                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $options[] = $row['store_number'];
                    }
                }

                $conn->close();

                echo json_encode($options);
            ?>;


            function fetchStoreInfo(storeNumber) {
                $.ajax({
                    url: 'get_store_info.php', 
                    type: 'POST',
                    data: { storeNumber: storeNumber },
                    success: function(response) {
                        var storeInfo = JSON.parse(response);
                        $("#work_address").val(storeInfo.address + ', ' + storeInfo.city + ', ' + storeInfo.postal_code);
                        $("#phone_number").val(storeInfo.phone_number);
                    },
                    error: function(xhr, status, error) {
                        console.error(xhr.responseText);
                    }
                });
            }


            $("#client_name").change(function() {
                var selectedValue = $(this).val();

                if (selectedValue !== "TFI" && selectedValue !== "CICNET" && selectedValue !== "GoCo" && selectedValue !== "Simons" && selectedValue !== "Forget") {

                    $("#nb_customer").autocomplete({
                        source: availableOptions,
                        select: function(event, ui) {
                            fetchStoreInfo(ui.item.value);
                        }
                    });
                } else {

                   // $("#nb_customer").autocomplete("destroy");
                }
            });
        });

var canvas1 = document.getElementById('signatureCanvas');
var signaturePad1 = new SignaturePad(canvas1);

var canvas2 = document.getElementById('signatureCanvas2');
var signaturePad2 = new SignaturePad(canvas2);


document.querySelector('form').addEventListener('submit', function (event) {
    var signatureData1 = signaturePad1.toDataURL();
    var signatureData2 = signaturePad2.toDataURL();

    document.getElementById('signatureInput').value = signatureData1;
    document.getElementById('signatureInput2').value = signatureData2;
});


function clearSignature(padName) {
    if (padName === 'signaturePad2') {
        signaturePad2.clear();
    } else {
        signaturePad1.clear();
    }
}

flatpickr(".flatpickr-input", {
    enableTime: false,
    noCalendar: true,
    dateFormat: "h:i K",
    hour_12: true,
    mobile: true, 
});

flatpickr("#depart_bureau_time", {
    enableTime: true,
    noCalendar: true,
    dateFormat: "h:i K",
    hour_12: true,
    onChange: function(selectedDates, dateStr, instance) {

        var selectedTime = dateStr;


        var newTime = new Date();
        newTime.setHours(parseInt(selectedTime.split(":")[0], 10), parseInt(selectedTime.split(":")[1], 10) + 5);


        var formattedTime = newTime.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit', hour12: true});


        document.getElementById("arrive_site_time").value = formattedTime;
    }
});

flatpickr("#arrive_site_time", {
    enableTime: true,
    noCalendar: true,
    dateFormat: "h:i K",
    hour_12: true,
    minuteIncrement: 5,
    onOpen: function(selectedDates, dateStr, instance) {

        var arriveSiteTime = document.getElementById("arrive_site_time").value;


        if (arriveSiteTime) {

            instance.setDate(arriveSiteTime, true, 'h:i K');
        }
    },
    onChange: function(selectedDates, dateStr, instance) {

        var newTime = new Date(selectedDates[0]);
        newTime.setMinutes(newTime.getMinutes() + 5);
        var formattedTime = newTime.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit', hour12: true});
        document.getElementById("depart_site_time").value = formattedTime;
    }
});


flatpickr("#depart_site_time", {
    enableTime: true,
    noCalendar: true,
    dateFormat: "h:i K",
    hour_12: true,
    onChange: function(selectedDates, dateStr, instance) {

        var selectedTime = dateStr;


        var newTime = new Date();
        newTime.setHours(parseInt(selectedTime.split(":")[0], 10), parseInt(selectedTime.split(":")[1], 10) + 5);


        var formattedTime = newTime.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit', hour12: true});


        document.getElementById("arrive_bureau_time").value = formattedTime;
    },
    onOpen: function(selectedDates, dateStr, instance) {

        var departSiteTime = document.getElementById("depart_site_time").value;


        if (departSiteTime) {

            instance.setDate(departSiteTime, true, 'h:i K');
        }
    }
});



flatpickr("#arrive_bureau_time", {
    enableTime: true,
    noCalendar: true,
    dateFormat: "h:i K",
    hour_12: true,
    onChange: function(selectedDates, dateStr, instance) {

        document.getElementById("arrive_bureau_time").value = dateStr;
    },
    onOpen: function(selectedDates, dateStr, instance) {

        var arriveBureauTime = document.getElementById("arrive_bureau_time").value;


        if (arriveBureauTime) {

            instance.setDate(arriveBureauTime, true, 'h:i K');
        }
    }

    
});

flatpickr("#date", {
    enableTime: false,
    dateFormat: "Y-m-d",
    defaultDate: "today", 
});

document.querySelector('form').addEventListener('submit', function (event) {
    var departBureauTime = document.getElementById("depart_bureau_time").value;
    var arriveSiteTime = document.getElementById("arrive_site_time").value;
    var departSiteTime = document.getElementById("depart_site_time").value;
    var arriveBureauTime = document.getElementById("arrive_bureau_time").value;

    if (departBureauTime === '' || arriveSiteTime === '' || departSiteTime === '' || arriveBureauTime === '') {
        alert('Please fill in all "required" fields before submitting the form / Veuillez remplir tous les champs de temps avant de soumettre le formulaire.');
        event.preventDefault(); // Empêche l'envoi du formulaire s'il manque des champs obligatoires.
    }
});

</script>


    </form>
</body>
</html>
