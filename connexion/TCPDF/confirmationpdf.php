<?php
require('tcpdf.php');
include "../../config.php";
include_once "../logincheck.php";

class MYPDF extends TCPDF {
    public function Header() {}
    public function Footer() {}
}

// Variables de session consolidées
$mail = $_SESSION['mail'];
$partner_email = $_SESSION['partner_email'];
$userId = $_SESSION['user_id'];
$userFullName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$depotID = $_SESSION['depot'];

// Traitement du formulaire
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if(isset($_POST['glpi_ticket_id'])) {
        $_SESSION['glpi_ticket_id'] = $_POST['glpi_ticket_id'];
    }
    
    // Redirection si aucun équipement sélectionné
    if (isset($_POST['equipement']) && empty($_POST['equipement'])) {
        header('Location: ../confirmation/confirmation.php');
        exit(); 
    }
    $_SESSION['equipement_selected'] = $_POST['equipement'] ?? array();

    // Récupération données formulaire
    $formData = [
        'client_name' => $_POST['client_name'] ?? '',
        'contact_name' => $_POST['contact_name'] ?? '',
        'work_address' => $_POST['work_address'] ?? '',
        'phone_number' => $_POST['phone_number'] ?? '',
        'billingOption' => $_POST['billing_option'] ?? '',
        'description' => $_POST['description'] ?? '',
        'emailtech' => $_POST['emailtech'] ?? '',
        'emailpartner' => $_POST['emailpartner'] ?? '',
        'equipement' => $_POST['equipement'] ?? array(),
        'equipment_brand' => $_POST['equipment_brand'] ?? '',
        'work_date' => $_POST['date'] ?? '',
        'km' => $_POST['km'] ?? '',
        'signatureImageData' => $_POST['signature'] ?? '',
        'signatureImageData2' => $_POST['signature2'] ?? ''
    ];

    $equipementText = implode(', ', $formData['equipement']);

    // Traitement des heures
    $times = [
        'depart_bureau' => $_POST['depart_bureau_time'] ?? '',
        'arrive_site' => $_POST['arrive_site_time'] ?? '',
        'depart_site' => $_POST['depart_site_time'] ?? '',
        'arrive_bureau' => $_POST['arrive_bureau_time'] ?? ''
    ];

    // Fonction pour convertir en format 24h et calculer
    function convertTo24HourFormat($timeStr) {
        return date("H:i", strtotime($timeStr));
    }

    $times_24h = array_map('convertTo24HourFormat', $times);
    $time_seconds = array_map('strtotime', $times_24h);
    
    $total_time_sec = $time_seconds['arrive_bureau'] - $time_seconds['depart_bureau'];
    $site_time_sec = $time_seconds['depart_site'] - $time_seconds['arrive_site'];
    
    $total_hours = floor($total_time_sec / 3600);
    $total_minutes = floor(($total_time_sec % 3600) / 60);
    $site_hours = floor($site_time_sec / 3600);
    $site_minutes = floor(($site_time_sec % 3600) / 60);

    // =============================================
    // GÉNÉRATION DU PDF AVEC TEMPLATE AIR MAGIQUE
    // =============================================

    // Créer le PDF en format lettre avec la classe MYPDF
    $pdf = new MYPDF('P', 'mm', 'LETTER', true, 'UTF-8', false);
    $pdf->SetCreator('Air Magique');
    $pdf->SetAuthor('Air Magique');
    $pdf->SetTitle('Bon de travail');
    $pdf->SetMargins(2, 2, 2);
    $pdf->SetAutoPageBreak(false);
    $pdf->AddPage();

    // Configuration
    $pdf->SetDrawColor(0, 0, 0);
    $pdf->SetLineWidth(0.15);
    $pdf->SetFillColor(235, 235, 235);

    // =============================================
    // LOGO AIR MAGIQUE
    // =============================================
    $pdf->Image('../image/airmagique_logo.png', 12, 8, 80, 18, 'PNG');

    // =============================================
    // ADRESSE SOUS LE LOGO
    // =============================================
    $pdf->SetFont('helvetica', '', 8.5);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Text(12, 30, '6900 Bul. Décarie #3280, Côte Saint-Luc, Québec H3X 2T8 info@airmagique.com. Tél: 514 865 8585');

    // =============================================
    // TABLEAU DATE ET NUMÉRO 
    // =============================================
    $pdf->SetFont('helvetica', '', 7.5);

    // TABLEAU DATE (à gauche) - Position de départ
    $pdf->SetXY(125, 8);

    // Première ligne JOUR/MOIS/ANNÉE
    $pdf->Cell(15, 4, 'JOUR', 1, 0, 'C', true);
    $pdf->Cell(15, 4, 'MOIS', 1, 0, 'C', true);
    $pdf->Cell(15, 4, 'ANNÉE', 1, 0, 'C', true);

    // TABLEAU NUMÉRO (à droite) - MÊME ligne Y=8
    $pdf->SetXY(175, 8);
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(35, 4, 'NUMÉRO / NUMBER', 1, 0, 'C', true);

    // Deuxième ligne DAY/MONTH/YEAR
    $pdf->SetXY(125, 12);
    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->Cell(15, 4, 'DAY', 1, 0, 'C', true);
    $pdf->Cell(15, 4, 'MONTH', 1, 0, 'C', true);
    $pdf->Cell(15, 4, 'YEAR', 1, 0, 'C', true);

    // Numéro GLPI en rouge - MÊME ligne Y=12
    $pdf->SetXY(175, 12);
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->SetTextColor(200, 0, 0);
    $pdf->Cell(35, 10, $_POST['glpi_ticket_id'] ?? '', 1, 0, 'C');
    $pdf->SetTextColor(0, 0, 0);

    // Ligne avec la date du formulaire
    $pdf->SetXY(125, 16);
    $pdf->SetFont('helvetica', '', 7.5);
    
    // Extraire jour, mois, année de la date du formulaire
    $workDate = $formData['work_date'];
    if (!empty($workDate)) {
        $dateArray = explode('-', $workDate);
        $year = $dateArray[0] ?? '';
        $month = $dateArray[1] ?? '';
        $day = $dateArray[2] ?? '';
    } else {
        $year = $month = $day = '';
    }
    
    $pdf->Cell(15, 6, $day, 1, 0, 'C');
    $pdf->Cell(15, 6, $month, 1, 0, 'C');
    $pdf->Cell(15, 6, $year, 1, 0, 'C');

    // =============================================
    // SECTION CLIENT - REMPLIR AVEC LES DONNÉES DU FORMULAIRE
    // =============================================
    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->SetXY(2, 35);

    // Première ligne: VENDU À / SOLD TO | EXPÉDIÉ À / SHIP TO | EMAIL
    $pdf->Cell(70, 4.5, 'VENDU À / SOLD TO', 1, 0, 'L', true);
    $pdf->Cell(70, 4.5, 'EXPÉDIÉ À / SHIP TO', 1, 0, 'L', true);
    $pdf->Cell(70, 4.5, 'COURRIEL / EMAIL', 1, 1, 'L', true);

    // Deuxième ligne: Remplir avec les données du formulaire
    $pdf->SetFont('helvetica', '', 6.5);
    $pdf->Cell(70, 4.5, $formData['client_name'], 1, 0, 'L');
    $pdf->Cell(70, 4.5, $formData['client_name'], 1, 0, 'L');
    $pdf->Cell(70, 4.5, $formData['emailtech'], 1, 1, 'L');

    // Troisième ligne: ADRESSE + TÉLÉPHONE
    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->Cell(70, 4.5, 'ADRESSE / ADDRESS', 1, 0, 'L', true);
    $pdf->Cell(70, 4.5, 'ADRESSE / ADDRESS', 1, 0, 'L', true);
    $pdf->Cell(70, 4.5, 'TÉLÉPHONE / PHONE NUMBER', 1, 1, 'L', true);

    // DEUX CELLULES SÉPARÉES pour les adresses + téléphone
    $pdf->SetFont('helvetica', '', 6.5);
    $addressLines = explode(',', $formData['work_address']);
    $shortAddress = implode(',', array_slice($addressLines, 0, 3)); // Limiter à 3 parties pour tenir dans la cellule
    
    $pdf->Cell(70, 4.5, $shortAddress, 1, 0, 'L');  // Première cellule d'adresse
    $pdf->Cell(70, 4.5, $shortAddress, 1, 0, 'L');  // Deuxième cellule d'adresse
    $pdf->Cell(70, 4.5, $formData['phone_number'], 1, 1, 'L');

    // =============================================
    // SECTION ÉQUIPEMENTS AVEC CHECKBOXES
    // =============================================
    $pdf->SetY($pdf->GetY() + 3);
    $pdf->SetFont('helvetica', '', 6);

    // Liste complète des équipements possibles
    $allEquipments = [
        'pos' => 'POS',
        'thermal_printer' => 'Imprimante Thermique',
        'epc' => 'EPC',
        'Scanner' => 'Scanner',
        'ups' => 'UPS',
        'Cash drawer' => 'Tiroir-caisse',
        'site_controller' => 'Contrôleur Site',
        'fuel_controller' => 'Contrôleur Carburant',
        'hub_8_port' => 'Hub 8 Ports',
        'server_pro' => 'Serveur Pro',
        'server_std' => 'Serveur Std',
        'cisco_1121' => 'Cisco 1121',
        'switch_48_port' => 'Switch 48P',
        'switch_24_port' => 'Switch 24P',
        'bopc_hp' => 'BOPC HP',
        'bopc_dell' => 'BOPC Dell',
        'lcd_monitor' => 'Moniteur LCD',
        'lexmark' => 'Lexmark'
    ];

    // Affichage des équipements en grille avec checkboxes
    $currentX = 2;
    $currentY = $pdf->GetY();
    $itemsPerRow = 4;
    $itemCount = 0;

    foreach($allEquipments as $key => $equipment) {
        $pdf->SetXY($currentX, $currentY);
        
        // Checkbox (petit carré)
        $pdf->Rect($currentX, $currentY, 3, 3);
        
        // Si cet équipement est sélectionné, marquer la checkbox
        if(in_array($key, $formData['equipement']) || in_array($equipment, $formData['equipement'])) {
            $pdf->SetFont('helvetica', 'B', 8);
            $pdf->SetXY($currentX + 0.5, $currentY - 0.5);
            $pdf->Cell(3, 3, 'X', 0, 0, 'C');
        }
        
        // Texte de l'équipement
        $pdf->SetFont('helvetica', '', 5.5);
        $pdf->SetXY($currentX + 4, $currentY + 0.8);
        $pdf->Cell(48, 3, $equipment, 0, 0, 'L');
        
        $itemCount++;
        if($itemCount % $itemsPerRow == 0) {
            $currentX = 2;
            $currentY += 5;
        } else {
            $currentX += 52;
        }
    }

    // =============================================
    // SECTION MARQUE D'ÉQUIPEMENT
    // =============================================
    $pdf->SetY($currentY + 5);
    $pdf->SetFont('helvetica', '', 7.5);
    $pdf->SetFillColor(235, 235, 235);
    $pdf->Cell(210, 4.5, 'MARQUE D\'ÉQUIPEMENT / BRAND OF EQUIPMENT', 1, 1, 'L', true);

    $pdf->SetFont('helvetica', '', 6.5);
    $pdf->Cell(105, 4.5, $formData['equipment_brand'], 1, 0, 'L');
    $pdf->Cell(105, 4.5, 'Contact: ' . $formData['contact_name'], 1, 1, 'L');

    // =============================================
    // SECTION RAPPORT DU TECHNICIEN
    // =============================================
    $pdf->SetY($pdf->GetY() + 3);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(200, 200, 200);
    $pdf->Cell(210, 5, 'RAPPORT DU TECHNICIEN / TECHNICIAN REPORT', 1, 1, 'C', true);

    // Section description du travail
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetFillColor(235, 235, 235);
    $pdf->Cell(210, 4, 'DESCRIPTION DU TRAVAIL / WORK DESCRIPTION', 1, 1, 'L', true);

    // Contenu de la description
    $pdf->SetFont('helvetica', '', 6.5);
    $pdf->MultiCell(210, 4, $formData['description'], 1, 'L', false, 1);

    // =============================================
    // SECTION HEURES DE TRAVAIL
    // =============================================
    $pdf->SetY($pdf->GetY() + 2);
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetFillColor(235, 235, 235);

    // En-têtes des colonnes de temps
    $pdf->Cell(52.5, 4, 'DÉPART BUREAU', 1, 0, 'C', true);
    $pdf->Cell(52.5, 4, 'ARRIVÉE SITE', 1, 0, 'C', true);
    $pdf->Cell(52.5, 4, 'DÉPART SITE', 1, 0, 'C', true);
    $pdf->Cell(52.5, 4, 'RETOUR BUREAU', 1, 1, 'C', true);

    $pdf->Cell(52.5, 4, 'OFFICE DEPARTURE', 1, 0, 'C', true);
    $pdf->Cell(52.5, 4, 'SITE ARRIVAL', 1, 0, 'C', true);
    $pdf->Cell(52.5, 4, 'SITE DEPARTURE', 1, 0, 'C', true);
    $pdf->Cell(52.5, 4, 'OFFICE RETURN', 1, 1, 'C', true);

    // Heures
    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(52.5, 6, $times['depart_bureau'], 1, 0, 'C');
    $pdf->Cell(52.5, 6, $times['arrive_site'], 1, 0, 'C');
    $pdf->Cell(52.5, 6, $times['depart_site'], 1, 0, 'C');
    $pdf->Cell(52.5, 6, $times['arrive_bureau'], 1, 1, 'C');

    // =============================================
    // SECTION TOTAUX
    // =============================================
    $pdf->SetY($pdf->GetY() + 2);
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetFillColor(235, 235, 235);
    $pdf->Cell(105, 4, 'TEMPS TOTAL / TOTAL TIME', 1, 0, 'C', true);
    $pdf->Cell(105, 4, 'TEMPS SUR SITE / TIME ON SITE', 1, 1, 'C', true);

    $pdf->SetFont('helvetica', 'B', 8);
    $totalTimeStr = sprintf("%02d:%02d", $total_hours, $total_minutes);
    $siteTimeStr = sprintf("%02d:%02d", $site_hours, $site_minutes);
    $pdf->Cell(105, 6, $totalTimeStr, 1, 0, 'C');
    $pdf->Cell(105, 6, $siteTimeStr, 1, 1, 'C');

    // Section kilomètres et option facturation
    $pdf->SetY($pdf->GetY() + 1);
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetFillColor(235, 235, 235);
    $pdf->Cell(105, 4, 'KILOMÉTRAGE / MILEAGE', 1, 0, 'C', true);
    $pdf->Cell(105, 4, 'OPTION FACTURATION / BILLING', 1, 1, 'C', true);

    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(105, 6, $formData['km'] . ' KM', 1, 0, 'C');
    $pdf->Cell(105, 6, $formData['billingOption'], 1, 1, 'C');

    // =============================================
    // SECTION TECHNICIEN
    // =============================================
    $pdf->SetY($pdf->GetY() + 1);
    $pdf->SetFont('helvetica', '', 7);
    $pdf->SetFillColor(235, 235, 235);
    $pdf->Cell(210, 4, 'TECHNICIEN / TECHNICIAN', 1, 1, 'C', true);

    $pdf->SetFont('helvetica', '', 8);
    $pdf->Cell(210, 6, $userFullName, 1, 1, 'C');

    // =============================================
    // SECTION SIGNATURES
    // =============================================
    $pdf->SetY($pdf->GetY() + 5);
    
    $signatureY = $pdf->GetY();
    
    // Signature Technicien
    if (!empty($formData['signatureImageData'])) {
        try {
            $signatureImage = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $formData['signatureImageData']));
            if ($signatureImage && (extension_loaded('gd') || extension_loaded('imagick'))) {
                $pdf->Image('@' . $signatureImage, 20, $signatureY, 60, 20);
            }
        } catch (Exception $e) {
            // En cas d'erreur, on affiche juste le cadre
        }
    }
    
    $pdf->SetXY(20, $signatureY + 20);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(60, 8, 'Signature Technicien', 1, 0, 'C', true);
    $pdf->SetXY(20, $signatureY + 28);
    $pdf->Cell(60, 5, $userFullName, 1, 0, 'C');

    // Signature Client
    if (!empty($formData['signatureImageData2'])) {
        try {
            $signatureImage2 = base64_decode(preg_replace('#^data:image/\w+;base64,#i', '', $formData['signatureImageData2']));
            if ($signatureImage2 && (extension_loaded('gd') || extension_loaded('imagick'))) {
                $pdf->Image('@' . $signatureImage2, 130, $signatureY, 60, 20);
            }
        } catch (Exception $e) {
            // En cas d'erreur, on affiche juste le cadre
        }
    }
    
    $pdf->SetXY(130, $signatureY + 20);
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->Cell(60, 8, 'Signature Client', 1, 0, 'C', true);
    $pdf->SetXY(130, $signatureY + 28);
    $pdf->Cell(60, 5, $formData['contact_name'], 1, 0, 'C');

    // =============================================
    // TRAITEMENT DES IMAGES UPLOADÉES
    // =============================================
    if (!empty($_FILES['uploaded_files']['tmp_name'])) {
        foreach ($_FILES['uploaded_files']['tmp_name'] as $key => $tmp_name) {
            if (empty($tmp_name)) continue;
            
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $imagePath = $uploadDir . basename($_FILES['uploaded_files']['name'][$key]);
            move_uploaded_file($tmp_name, $imagePath);
            
            if (file_exists($imagePath)) {
                $pdf->AddPage();
                $pdf->Image($imagePath, ($pdf->getPageWidth() - 150) / 2, 40, 150, 0, 'JPEG');
                unlink($imagePath);
            }
        }
    }

    // =============================================
    // MISE À JOUR BASE DE DONNÉES
    // =============================================
    
    $conn = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);
    if (!$conn->connect_error && isset($_POST['equipement']) && !empty($_POST['equipement'])) {
        $partnerQuery = "SELECT * FROM partner WHERE partner_id = '$depotID'";
        $partnerResult = $conn->query($partnerQuery);
        
        if ($partnerResult->num_rows > 0) {
            $partnerRow = $partnerResult->fetch_assoc();
            $equipmentColumns = [
                "pos" => "pos", "thermal_printer" => "thermal_printer", "epc" => "epc", "Scanner" => "scanner",
                "ups" => "ups", "Cash drawer" => "cash_drawer", "site_controller" => "site_controller",
                "fuel_controller" => "fuel_controller", "hub_8_port" => "hub_8_port", "pinpad_cable" => "pinpad_cable",
                "scanner_cable" => "scanner_cable", "cash_drawer_cable" => "cash_drawer_cable", "server_pro" => "server_pro",
                "server_std" => "server_std", "pdu" => "pdu", "cisco_1121" => "cisco_1121",
                "bracket_router_cisco_1121" => "bracket_router_cisco_1121", "UPS1000" => "UPS1000",
                "cisco_9200_24t" => "cisco_9200_24t", "cisco_9200_48t" => "cisco_9200_48t", "viptela" => "viptela",
                "aruba" => "aruba", "switch_48_port" => "switch_48_port", "switch_24_port" => "switch_24_port",
                "bopc_hp" => "bopc_hp", "bopc_dell" => "bopc_dell", "bopc_pagnian" => "bopc_pagnian",
                "dp_to_hdmi" => "dp_to_hdmi", "lcd_monitor" => "lcd_monitor", "lexmark" => "lexmark",
                "display_19" => "display_19", "display_7" => "display_7", "lift_cpu" => "lift_cpu",
                "lift_power_bar" => "lift_power_bar", "dual_usb_6f" => "dual_usb_6f", "dual_usb_15f" => "dual_usb_15f",
                "adapter_rj45_splitter" => "adapter_rj45_splitter", "rj12_rj45_scanner" => "rj12_rj45_scanner",
                "rj12_coupler" => "rj12_coupler", "rj45_lift_cpu" => "rj45_lift_cpu",
                "rj12_rj45_pole_display" => "rj12_rj45_pole_display", "radiant_scanner_cable" => "radiant_scanner_cable",
                "dvi_vga" => "dvi_vga", "mount_pole_24i" => "mount_pole_24i", "mount_arm_pole" => "mount_arm_pole",
                "mount_flat_panel_pole" => "mount_flat_panel_pole", "mount_grommet" => "mount_grommet",
                "mount_homeplate" => "mount_homeplate", "scanner_db9_rj45" => "scanner_db9_rj45",
                "virtual_journal_db9_rj45" => "virtual_journal_db9_rj45", "pos_db9_rj45" => "pos_db9_rj45",
                "scanner_db9_db25" => "scanner_db9_db25"
            ];

            foreach ($_POST['equipement'] as $selectedEquipment) {
                if (array_key_exists($selectedEquipment, $equipmentColumns) && $partnerRow[$equipmentColumns[$selectedEquipment]] >= -999) {
                    $newQuantity = $partnerRow[$equipmentColumns[$selectedEquipment]] - 1;
                    $updateQuery = "UPDATE partner SET " . $equipmentColumns[$selectedEquipment] . " = $newQuantity WHERE partner_id = $depotID";
                    
                    if ($conn->query($updateQuery) === TRUE) {
                        $insertQuery = $conn->prepare("INSERT INTO history (partner_id, tech_name, ticket_id, equipment, date, address) VALUES (?, ?, ?, ?, ?, ?)");
                        $insertQuery->bind_param("isssss", $depotID, $userFullName, $_SESSION['glpi_ticket_id'], $selectedEquipment, date("Y-m-d H:i:s"), $partnerRow['address']);
                        $insertQuery->execute();
                    }
                }
            }
        }
    }

    // Incrémenter le compteur de work orders complétés
    if ($conn && !$conn->connect_error) {
        $incrementStmt = $conn->prepare("UPDATE account SET completed_wo = completed_wo + 1 WHERE LOWER(username) = LOWER(?)");
        if ($incrementStmt) {
            $incrementStmt->bind_param("s", $userId);
            if ($incrementStmt->execute()) {
                error_log("Work order counter incremented for user: " . $userId);
            } else {
                error_log("Failed to increment work order counter for user: " . $userId . " - Error: " . $incrementStmt->error);
            }
            $incrementStmt->close();
        } else {
            error_log("Failed to prepare increment statement: " . $conn->error);
        }
    }

    // Fermer la connexion
    if ($conn) {
        $conn->close();
    }

    // Affichage direct du PDF
    $pdf->Output('workorder_airmagique.pdf', 'I');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Order - inv.ctiai.com</title>
    
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- External Libraries -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/signature_pad/1.5.3/signature_pad.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
    <link rel="stylesheet" href="//code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
    
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
            max-width: 900px;
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

        .form-container {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
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

        input[type="text"],
        input[type="email"],
        input[type="number"],
        select,
        textarea {
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

        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="number"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: #eb2226;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(235, 34, 38, 0.1);
        }

        input[readonly] {
            background: #e9ecef;
            cursor: not-allowed;
        }

        .radio-group {
            display: flex;
            gap: 20px;
            margin-top: 8px;
        }

        .radio-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        input[type="radio"] {
            width: 16px;
            height: 16px;
            accent-color: #eb2226;
        }

        .signature-section {
            margin: 30px 0;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .signature-canvas {
            border: 2px solid #ced4da;
            border-radius: 8px;
            margin: 10px 0;
            background: white;
            width: 100%;
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
            background: transparent;
            color: #eb2226;
            border: 2px solid #eb2226;
        }

        .btn-secondary:hover {
            background: #eb2226;
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(235, 34, 38, 0.3);
        }

        .btn-neutral {
            background: #6c757d;
            color: #ffffff;
        }

        .btn-neutral:hover {
            background: #5a6268;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }

        .btn-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn-group .btn {
            min-width: 200px;
        }

        .file-input-wrapper {
            position: relative;
            display: inline-block;
            cursor: pointer;
            width: 100%;
        }

        .file-input-wrapper input[type="file"] {
            opacity: 0;
            position: absolute;
            z-index: -1;
        }

        .file-input-label {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 20px;
            background: #f8f9fa;
            border: 2px dashed #ced4da;
            border-radius: 8px;
            color: #6c757d;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
            width: 100%;
            justify-content: center;
        }

        .file-input-label:hover {
            border-color: #eb2226;
            color: #eb2226;
            background: rgba(235, 34, 38, 0.05);
        }

        .file-input-label i {
            font-size: 18px;
        }

        select[multiple] {
            min-height: 120px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .container {
                padding: 10px;
            }

            .form-container {
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

            .signature-canvas {
                height: 120px;
            }
        }

        @media (max-width: 480px) {
            .page-title {
                font-size: 24px;
            }

            .form-container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <img src="../image/airmagique_logo.png" alt="Logo" class="logo">
            <h1 class="page-title">Work Order Form</h1>
        </div>

        <a href="../techmenu/techmenu.php" class="back-arrow" title="Back to menu">Back to Menu</a>

        <div class="form-container">
            <form method="post" action="" enctype="multipart/form-data" id="loginform">
                <div class="form-row">
                    <div class="form-group">
                        <label for="glpi_ticket_id">GLPI #/PO #:</label>
                        <input type="text" id="glpi_ticket_id" name="glpi_ticket_id" pattern="\d{10,}" title="The number must contain at least 10 digits minimum" minlength="10" required placeholder="Required">
                    </div>
                    <div class="form-group">
                        <label for="client_name">Company Name:</label>
                        <select id="client_name" name="client_name" required>
                            <option value="" disabled selected>Choose company</option>
                            <option value="Circle K/Couche-Tard">Circle K/Couche-Tard</option>
                            <option value="CICNET">CICNET</option>
                            <option value="Client divers">Client divers</option>
                            <option value="Forget">Forget</option>
                            <option value="GoCo">GoCo</option>
                            <option value="Simons">Simons</option>
                            <option value="TFI">TFI</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nb_customer">Store #:</label>
                        <input type="text" name="nb_customer" id="nb_customer">
                    </div>
                    <div class="form-group">
                        <label for="technician_name">Technician Name:</label>
                        <input type="text" name="name_technician" value="<?php echo $userFullName; ?>" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="contact_name">Contact Name:</label>
                        <input type="text" name="contact_name" required placeholder="Required">
                    </div>
                    <div class="form-group">
                        <label for="work_address">Work Address:</label>
                        <input type="text" name="work_address" id="work_address">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="phone_number">Client Phone:</label>
                        <input type="text" name="phone_number" id="phone_number">
                    </div>
                    <div class="form-group">
                        <label for="date">Work Date:</label>
                        <input type="text" id="date" name="date" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="depart_bureau_time">Office Departure:</label>
                        <input type="text" id="depart_bureau_time" name="depart_bureau_time" required placeholder="Required">
                    </div>
                    <div class="form-group">
                        <label for="arrive_site_time">Arrived on Site:</label>
                        <input type="text" id="arrive_site_time" name="arrive_site_time" required placeholder="Required">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="depart_site_time">Site Departure:</label>
                        <input type="text" id="depart_site_time" name="depart_site_time" required placeholder="Required">
                    </div>
                    <div class="form-group">
                        <label for="arrive_bureau_time">Arrived Office:</label>
                        <input type="text" id="arrive_bureau_time" name="arrive_bureau_time" required placeholder="Required">
                    </div>
                </div>

                <div class="form-group">
                    <label for="km">Kilometers Traveled:</label>
                    <input type="number" name="km" step="1" min="0" required placeholder="Required">
                </div>

                <div class="form-group">
                    <label>Billing Option:</label>
                    <div class="radio-group">
                        <div class="radio-item">
                            <input type="radio" name="billing_option" value="Under contract" id="under_contract" required>
                            <label for="under_contract">Under Contract</label>
                        </div>
                        <div class="radio-item">
                            <input type="radio" name="billing_option" value="Billable" id="billable" required>
                            <label for="billable">Billable</label>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea name="description" rows="6" maxlength="1100" required placeholder="Describe the work performed..."></textarea>
                </div>

                <div class="form-group">
                    <label for="equipment_brand">Equipment Brand:</label>
                    <input type="text" name="equipment_brand" id="equipment_brand" placeholder="Enter equipment brand/model...">
                </div>

                <div class="form-group">
                    <label for="equipment">Equipment Used:</label>
                    <select id="equipment" name="equipement[]" multiple required>
                        <option value="">No equipment used</option>
                    </select>
                </div>

                <div class="signature-section">
                    <label for="signature">Technician Signature:</label>
                    <canvas id="signatureCanvas" width="300" height="150" class="signature-canvas"></canvas>
                    <input type="hidden" name="signature" id="signatureInput">
                    <button type="button" onclick="clearSignature()" class="btn btn-neutral">
                        <i class="fas fa-eraser"></i>
                        Clear
                    </button>
                </div>

                <div class="signature-section">
                    <label for="signature2">Client Signature:</label>
                    <canvas id="signatureCanvas2" width="300" height="150" class="signature-canvas"></canvas>
                    <input type="hidden" name="signature2" id="signatureInput2">
                    <button type="button" onclick="clearSignature('signaturePad2')" class="btn btn-neutral">
                        <i class="fas fa-eraser"></i>
                        Clear
                    </button>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="emailtech">Technician Email:</label>
                        <input type="email" name="emailtech" value="<?php echo $mail; ?>" required readonly>
                    </div>
                    <div class="form-group">
                        <label for="emailpartner">Partner Email:</label>
                        <input type="email" name="emailpartner" value="<?php echo $partner_email; ?>" required readonly>
                    </div>
                </div>

                <div class="form-group">
                    <label for="upload_files">Upload Files:</label>
                    <div class="file-input-wrapper">
                        <input type="file" name="uploaded_files[]" multiple id="upload_files">
                        <label for="upload_files" class="file-input-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            Choose files... (JPG, JPEG)
                        </label>
                    </div>
                </div>

                <div class="btn-group">
                    <input type="button" id="draft-button" value="Save Draft" class="btn btn-secondary">
                    <input type="submit" value="Send Work Order" class="btn btn-primary" style="background: #eb2226; border: none;">
                </div>
            </form>
        </div>
    </div>

<script>
$(function() {
    var availableOptions = <?php
        $conn = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);
        $result = $conn->query("SELECT store_number FROM store");
        $options = array();
        while ($row = $result->fetch_assoc()) {
            $options[] = $row['store_number'];
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
            }
        });
    }

    $("#client_name").change(function() {
        var selectedValue = $(this).val();
        if (!["TFI", "CICNET", "GoCo", "Simons", "Forget"].includes(selectedValue)) {
            $("#nb_customer").autocomplete({
                source: availableOptions,
                select: function(event, ui) { fetchStoreInfo(ui.item.value); }
            });
        }
    });
});

// Signature Pads
var signaturePad1 = new SignaturePad(document.getElementById('signatureCanvas'));
var signaturePad2 = new SignaturePad(document.getElementById('signatureCanvas2'));

document.querySelector('form').addEventListener('submit', function (event) {
    document.getElementById('signatureInput').value = signaturePad1.toDataURL();
    document.getElementById('signatureInput2').value = signaturePad2.toDataURL();
});

function clearSignature(padName) {
    if (padName === 'signaturePad2') {
        signaturePad2.clear();
    } else {
        signaturePad1.clear();
    }
}

// Time picker configuration
flatpickr("#depart_bureau_time", {
    enableTime: true, noCalendar: true, dateFormat: "h:i K", hour_12: true,
    onChange: function(selectedDates, dateStr) {
        var newTime = new Date();
        newTime.setHours(parseInt(dateStr.split(":")[0], 10), parseInt(dateStr.split(":")[1], 10) + 5);
        document.getElementById("arrive_site_time").value = newTime.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit', hour12: true});
    }
});

flatpickr("#arrive_site_time", {
    enableTime: true, noCalendar: true, dateFormat: "h:i K", hour_12: true,
    onChange: function(selectedDates) {
        var newTime = new Date(selectedDates[0]);
        newTime.setMinutes(newTime.getMinutes() + 5);
        document.getElementById("depart_site_time").value = newTime.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit', hour12: true});
    }
});

flatpickr("#depart_site_time", {
    enableTime: true, noCalendar: true, dateFormat: "h:i K", hour_12: true,
    onChange: function(selectedDates, dateStr) {
        var newTime = new Date();
        newTime.setHours(parseInt(dateStr.split(":")[0], 10), parseInt(dateStr.split(":")[1], 10) + 5);
        document.getElementById("arrive_bureau_time").value = newTime.toLocaleTimeString([], {hour: '2-digit', minute: '2-digit', hour12: true});
    }
});

flatpickr("#arrive_bureau_time", { enableTime: true, noCalendar: true, dateFormat: "h:i K", hour_12: true });
flatpickr("#date", { enableTime: false, dateFormat: "Y-m-d", defaultDate: "today" });

// Draft button
document.getElementById("draft-button").addEventListener("click", function () {
    const form = document.getElementById("loginform");
    const ticketInput = document.getElementById("glpi_ticket_id");
    
    if (!ticketInput.value.trim()) {
        alert("GLPI Ticket ID is required.");
        return;
    }
    
    form.querySelectorAll("input, select, textarea").forEach(input => {
        if (input.name !== "glpi_ticket_id") input.removeAttribute("required");
    });
    
    form.action = "save_draft.php";
    fetch(form.action, { method: "POST", body: new FormData(form) })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Draft saved successfully!");
            window.location.href = "../techmenu/techmenu.php";
        } else {
            alert("Error: " + data.message);
        }
    })
    .catch(error => {
        alert("An error occurred. Please try again.");
        console.error("Error:", error);
    });
});
</script>
</body>
</html>