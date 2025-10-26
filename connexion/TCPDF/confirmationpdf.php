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
    
    // Récupérer les équipements et leurs prix
    $equipmentNames = $_POST['equipment_name'] ?? array();
    $equipmentPrices = $_POST['equipment_price'] ?? array();
    
    $equipmentList = array();
    foreach ($equipmentNames as $index => $name) {
        if (!empty($name)) {
            $price = isset($equipmentPrices[$index]) ? floatval($equipmentPrices[$index]) : 0;
            $equipmentList[] = array(
                'name' => $name,
                'price' => $price
            );
        }
    }

    // Récupération données formulaire
    $formData = [
        'client_name' => $_POST['client_name'] ?? '',
        'sold_to_address' => $_POST['sold_to_address'] ?? '',
        'ship_to_name' => $_POST['ship_to_name'] ?? '',
        'ship_to_address' => $_POST['ship_to_address'] ?? '',
        'contact_name' => $_POST['contact_name'] ?? '',
        'work_address' => $_POST['work_address'] ?? '',
        'phone_number' => $_POST['phone_number'] ?? '',
        'description' => $_POST['description'] ?? '',
        'recommendation' => $_POST['recommendation'] ?? '',
        'emailtech' => $_POST['emailtech'] ?? '',
        'emailpartner' => $_POST['emailpartner'] ?? '',
        'equipment_list' => $equipmentList,
        'work_date' => $_POST['date'] ?? '',
        'km' => $_POST['km'] ?? '',
        'signatureImageData' => $_POST['signature'] ?? '',
        'signatureImageData2' => $_POST['signature2'] ?? '',
        'appel_service' => floatval($_POST['appel_service'] ?? 0),
        'main_oeuvre' => floatval($_POST['main_oeuvre'] ?? 0)
    ];

    // Calculs de facturation
    $appelService = $formData['appel_service'];
    $mainOeuvre = $formData['main_oeuvre'];
    $sousTotal = $appelService + $mainOeuvre;
    $tps = $sousTotal * 0.05;
    $tvq = $sousTotal * 0.09975;
    $total = $sousTotal + $tps + $tvq;

    // Traitement des heures
    $times = [
        'depart_bureau' => $_POST['depart_bureau_time'] ?? '',
        'arrive_site' => $_POST['arrive_site_time'] ?? '',
        'depart_site' => $_POST['depart_site_time'] ?? '',
        'arrive_bureau' => $_POST['arrive_bureau_time'] ?? ''
    ];

    // Fonction pour convertir en format 24h et calculer
    function convertTo24HourFormat($timeStr) {
        if (empty($timeStr)) return "00:00";
        // Si déjà au format HH:MM
        if (preg_match('/^\d{2}:\d{2}$/', $timeStr)) {
            return $timeStr;
        }
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
    $pdf->Text(2, 30, '6900 Bul. Décarie #3280, Côte Saint-Luc, Québec H3X 2T8 info@airmagique.com. Tél: 514 865 8585');

    // =============================================
    // TABLEAU DATE ET NUMÉRO 
    // =============================================
    $pdf->SetFont('helvetica', 'B', 7.5);

    // TABLEAU DATE (à gauche) - Position de départ
    $pdf->SetXY(125, 8);
    $pdf->SetFillColor(200, 200, 200);

    // Première ligne JOUR/MOIS/ANNÉE
    $pdf->Cell(15, 4, 'JOUR', 1, 0, 'C', true);
    $pdf->Cell(15, 4, 'MOIS', 1, 0, 'C', true);
    $pdf->Cell(15, 4, 'ANNÉE', 1, 0, 'C', true);

    // TABLEAU NUMÉRO (à droite) - MÊME ligne Y=8
    $pdf->SetXY(175, 8);
    $pdf->SetFont('helvetica', 'B', 8);
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
    $pdf->SetFont('helvetica', 'B', 7.5);
    $pdf->SetXY(2, 35);

    // Première ligne: VENDU À / SOLD TO | EXPÉDIÉ À / SHIP TO | EMAIL
    $pdf->SetFillColor(200, 200, 200);
    $pdf->Cell(70, 4.5, 'VENDU À / SOLD TO', 1, 0, 'L', true);
    $pdf->Cell(70, 4.5, 'EXPÉDIÉ À / SHIP TO', 1, 0, 'L', true);
    $pdf->Cell(70, 4.5, 'COURRIEL / EMAIL', 1, 1, 'L', true);

    // Deuxième ligne: Remplir avec les données du formulaire
    $pdf->SetFont('helvetica', '', 6.5);
    $pdf->Cell(70, 4.5, $formData['client_name'], 1, 0, 'L');
    $pdf->Cell(70, 4.5, $formData['ship_to_name'], 1, 0, 'L');
    $pdf->Cell(70, 4.5, $formData['emailtech'], 1, 1, 'L');

    // Troisième ligne: ADRESSE + TÉLÉPHONE
    $pdf->SetFont('helvetica', 'B', 7.5);
    $pdf->SetFillColor(200, 200, 200);
    $pdf->Cell(70, 4.5, 'ADRESSE / ADDRESS', 1, 0, 'L', true);
    $pdf->Cell(70, 4.5, 'ADRESSE / ADDRESS', 1, 0, 'L', true);
    $pdf->Cell(70, 4.5, 'TÉLÉPHONE / PHONE NUMBER', 1, 1, 'L', true);

    // DEUX CELLULES SÉPARÉES pour les adresses + téléphone
    $pdf->SetFont('helvetica', '', 6.5);
    $addressLines = explode(',', $formData['sold_to_address']);
    $shortAddress = implode(',', array_slice($addressLines, 0, 3));
    
    $addressLines2 = explode(',', $formData['ship_to_address']);
    $shortAddress2 = implode(',', array_slice($addressLines2, 0, 3));
    
    $pdf->Cell(70, 4.5, $shortAddress, 1, 0, 'L');
    $pdf->Cell(70, 4.5, $shortAddress2, 1, 0, 'L');
    $pdf->Cell(70, 4.5, $formData['phone_number'], 1, 1, 'L');

    // =============================================
    // SECTION RAPPORT DU TECHNICIEN - GRANDE ZONE
    // =============================================
    $rapportY = $pdf->GetY() + 3;
    $pdf->SetY($rapportY);
    
    $rapportWidth = 140.25;
    $rapportX = 2; // Aligné à gauche avec la marge
    
    $pdf->SetX($rapportX);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(200, 200, 200);
    $pdf->Cell($rapportWidth, 6, 'RAPPORT DU TECHNICIEN / TECHNICIAN REPORT', 1, 1, 'C', true);

    // Fonction pour limiter les sauts de ligne
    function limitLineBreaks($text, $maxBreaks = 7) {
        // Remplacer les multiples sauts de ligne par un seul
        $text = preg_replace('/\n{2,}/', "\n", $text);
        
        // Compter les sauts de ligne
        $lineBreaks = substr_count($text, "\n");
        
        // Si on dépasse le maximum, on retire les sauts de ligne en trop
        if ($lineBreaks > $maxBreaks) {
            $lines = explode("\n", $text);
            $lines = array_slice($lines, 0, $maxBreaks + 1);
            $text = implode("\n", $lines);
        }
        
        return $text;
    }

    // Construire le texte avec description + recommendation
    $reportText = limitLineBreaks($formData['description'], 7);
    
    // Grande zone pour la description du rapport
    $pdf->SetX($rapportX);
    $pdf->SetFont('helvetica', '', 6.5);
    $pdf->MultiCell($rapportWidth, 50, $reportText, 1, 'L', false, 1);
    
    // Si recommendation existe, l'ajouter en gras souligné
    if (!empty($formData['recommendation'])) {
        // Sauvegarder la position Y actuelle
        $currentY = $pdf->GetY();
        
        // Retour en arrière pour ajouter la recommendation dans le même cadre
        $pdf->SetY($rapportY + 6); // Juste après le titre
        
        // Calculer la hauteur disponible pour la description
        $descriptionHeight = 25; // Moitié de la hauteur totale (50/2)
        
        // Ajouter la description
        $pdf->SetX($rapportX);
        $pdf->SetFont('helvetica', '', 6.5);
        $pdf->MultiCell($rapportWidth, $descriptionHeight, $reportText, 0, 'L', false, 1);
        
        // Ajouter RECOMMENDATION en gras souligné
        $pdf->SetX($rapportX + 2);
        $pdf->SetFont('helvetica', 'BU', 7);
        $pdf->Cell($rapportWidth - 4, 5, 'RECOMMENDATION:', 0, 1, 'L');
        
        // Ajouter le texte de la recommendation
        $pdf->SetX($rapportX + 2);
        $pdf->SetFont('helvetica', '', 6.5);
        $recommendationText = limitLineBreaks($formData['recommendation'], 3);
        $pdf->MultiCell($rapportWidth - 4, 20, $recommendationText, 0, 'L', false, 1);
    }

    // =============================================
    // SECTION MATÉRIEL UTILISÉ - EN DESSOUS
    // =============================================
    $materielY = $pdf->GetY() + 3;
    $pdf->SetXY($rapportX, $materielY);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetFillColor(200, 200, 200);
    $pdf->Cell($rapportWidth, 6, 'MATÉRIEL UTILISÉ / EQUIPMENT USED', 1, 1, 'C', true);
    
    // Construire le texte pour le matériel utilisé
    $equipmentText = '';
    foreach ($formData['equipment_list'] as $equipment) {
        $equipmentText .= $equipment['name'] . ' - ' . number_format($equipment['price'], 2, '.', ' ') . ' $' . "\n";
    }
    
    // Grande zone pour le matériel utilisé
    $pdf->SetX($rapportX);
    $pdf->SetFont('helvetica', '', 6.5);
    $pdf->MultiCell($rapportWidth, 50, $equipmentText, 1, 'L', false, 1);

    // =============================================
    // SECTION HEURES DE TRAVAIL
    // =============================================
    $pdf->SetY($pdf->GetY() + 2);
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetFillColor(200, 200, 200);

    $heureWidth = $rapportWidth / 4; // Diviser la largeur en 4 colonnes
    $pdf->SetX($rapportX);

    // En-têtes des colonnes de temps
    $pdf->Cell($heureWidth, 4, 'DÉPART BUREAU', 1, 0, 'C', true);
    $pdf->Cell($heureWidth, 4, 'ARRIVÉE SITE', 1, 0, 'C', true);
    $pdf->Cell($heureWidth, 4, 'DÉPART SITE', 1, 0, 'C', true);
    $pdf->Cell($heureWidth, 4, 'RETOUR BUREAU', 1, 1, 'C', true);

    $pdf->SetX($rapportX);
    $pdf->Cell($heureWidth, 4, 'OFFICE DEPARTURE', 1, 0, 'C', true);
    $pdf->Cell($heureWidth, 4, 'SITE ARRIVAL', 1, 0, 'C', true);
    $pdf->Cell($heureWidth, 4, 'SITE DEPARTURE', 1, 0, 'C', true);
    $pdf->Cell($heureWidth, 4, 'OFFICE RETURN', 1, 1, 'C', true);

    // Heures
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetX($rapportX);
    $pdf->Cell($heureWidth, 6, $times['depart_bureau'], 1, 0, 'C');
    $pdf->Cell($heureWidth, 6, $times['arrive_site'], 1, 0, 'C');
    $pdf->Cell($heureWidth, 6, $times['depart_site'], 1, 0, 'C');
    $pdf->Cell($heureWidth, 6, $times['arrive_bureau'], 1, 1, 'C');

    // =============================================
    // SECTION TOTAUX
    // =============================================
    $pdf->SetY($pdf->GetY() + 2);
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetFillColor(200, 200, 200);
    
    $totalWidth = $rapportWidth / 3; // Diviser en 3 colonnes
    $pdf->SetX($rapportX);
    
    $pdf->Cell($totalWidth, 4, 'TEMPS TOTAL / TOTAL TIME', 1, 0, 'C', true);
    $pdf->Cell($totalWidth, 4, 'TEMPS SUR SITE / TIME ON SITE', 1, 0, 'C', true);
    $pdf->Cell($totalWidth, 4, 'KILOMÉTRAGE / MILEAGE', 1, 1, 'C', true);

    $pdf->SetFont('helvetica', 'B', 8);
    $totalTimeStr = sprintf("%02d:%02d", $total_hours, $total_minutes);
    $siteTimeStr = sprintf("%02d:%02d", $site_hours, $site_minutes);
    
    $pdf->SetX($rapportX);
    $pdf->Cell($totalWidth, 6, $totalTimeStr, 1, 0, 'C');
    $pdf->Cell($totalWidth, 6, $siteTimeStr, 1, 0, 'C');
    $pdf->Cell($totalWidth, 6, $formData['km'] . ' KM', 1, 1, 'C');

    // =============================================
    // SECTION TECHNICIEN SUPPLÉMENTAIRE
    // =============================================
    $pdf->SetY($pdf->GetY() + 2);
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetFillColor(200, 200, 200);
    
    $pdf->SetX($rapportX);
    $pdf->Cell($rapportWidth, 4, 'TECHNICIEN SUPPLÉMENTAIRE / ADDITIONAL TECHNICIAN', 1, 1, 'C', true);
    
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetX($rapportX);
    $pdf->Cell($rapportWidth, 6, '', 1, 1, 'L');

// =============================================
// NUMÉROS TPS/TVQ ET CHARGE MINIMUM
// =============================================
$pdf->SetY($pdf->GetY() + 2);
$tpsY = $pdf->GetY();

// TPS et TVQ à gauche
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetX(2);
$pdf->Cell(70, 5, 'TPS - 721416477RT0001', 0, 1, 'L');
$pdf->SetX(2);
$pdf->Cell(70, 5, 'TVQ - 122726353STQ0001', 0, 1, 'L');



$pdf->SetY($tpsY);
$pdf->SetX(75);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetFillColor(255, 255, 255);
$pdf->MultiCell(65, 5, "CHARGE MINIMUM DE SERVICE 1 HEURE\nMINIMUM SERVICE CHARGE 1 HOUR", 1, 'C', true);

// TEXTE DE GARANTIE - FRANÇAIS ET ANGLAIS
$garantieY = $pdf->GetY() + 5;

// TEXTE FRANÇAIS À GAUCHE
$pdf->SetY($garantieY);
$pdf->SetX(2);
$pdf->SetFont('helvetica', '', 6);

$garantieText = "Je soussigné constate les travaux dûment accomplis. Il est convenu que tous les matériaux énoncés ci-haut demeureront la propriété de Air magique jusqu'à l'acquittement de cette facture. Suite au service et matériels exclusifs du fournisseur, la garantie persistera. La garantie sur les matériaux utilisés ne sera nullement liée à la main-d'œuvre. Major ou toute garantie de 90 jours. CONDITIONS : PAYABLE SUR RÉCEPTION. Je m'engage à payer des intérêts pour frais d'administration au taux de 2% par mois (24% par année). Passé 30 jours, toutes sommes impayées ou portions non réclamées de ce pas mise (24% par mois). Tous les honoraires et autres dépenses légales seront à mon compte sont confiés à un avocat pour perception.";

$pdf->MultiCell(85, 3, $garantieText, 0, 'J');
$frenchTextEndY = $pdf->GetY();

// TEXTE ANGLAIS À DROITE
$pdf->SetY($garantieY);
$pdf->SetX(128);
$pdf->SetFont('helvetica', '', 6);

$garantieTextEN = "The undersigned acknowledges the work being completed. It is understood that all the above-mentioned materials will remain the property of Air magique until paid in full. Guarantee is subject to service and maintenance by the supplier. Guarantee on used materials will be the one agreed upon by the manufacturer. Guarantee for labour: 90 days. TERMS: PAYABLE UPON RECEIPT. I agree to pay 2% interest per month (24% annually) for administration fee on the unpaid balance. I understand that I must pay legal fees equal to 20% of the balance due on my account or collection claim account is turned over to a lawyer for collection.";

$pdf->MultiCell(85, 3, $garantieTextEN, 0, 'J');
$englishTextEndY = $pdf->GetY();

// NUMÉRO RBQ AU MILIEU
$pdf->SetY($garantieY + 0);
$pdf->SetX(90);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->Cell(35, 5, 'Lic. RBQ #5784-5737-01', 0, 0, 'C');

// LOGO
$logoY = $garantieY + 6;
$logoX = 95;
$logoWidth = 25;
$logoHeight = 9;

$pdf->Image('../image/CMMTQ_logo.png', $logoX, $logoY, $logoWidth, $logoHeight, 'PNG');

// TEXTES ROUGES
$pdf->SetY($logoY + $logoHeight + 2);
$pdf->SetX(90);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->SetTextColor(235, 34, 38);
$pdf->Cell(35, 4, 'PAYABLE SUR RÉCEPTION', 0, 1, 'C');

$pdf->SetX(90);
$pdf->Cell(35, 4, 'TO BE PAID ON RECEIPT', 0, 1, 'C');
$pdf->SetTextColor(0, 0, 0);

// SIGNATURES
// SIGNATURE TECHNICIEN
$pdf->SetY($frenchTextEndY + 3);
$pdf->SetX(2);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell(85, 4, 'SIGNATURE DU TECHNICIEN / TECHNICIAN\'S SIGNATURE', 0, 1, 'L');

if (!empty($formData['signatureImageData'])) {
    $pdf->SetX(2);
    $signatureData = $formData['signatureImageData'];
    $signatureData = str_replace('data:image/png;base64,', '', $signatureData);
    $signatureData = base64_decode($signatureData);
    
    $tempSignature = tempnam(sys_get_temp_dir(), 'sig');
    file_put_contents($tempSignature, $signatureData);
    
    $pdf->Image($tempSignature, 12, $pdf->GetY(), 40, 15, 'PNG');
    unlink($tempSignature);
}

// SIGNATURE CLIENT
$pdf->SetY($englishTextEndY + 3);
$pdf->SetX(128);
$pdf->SetFont('helvetica', 'B', 7);
$pdf->Cell(85, 4, 'SIGNATURE DU CLIENT / CUSTOMER\'S SIGNATURE', 0, 1, 'L');

if (!empty($formData['signatureImageData2'])) {
    $pdf->SetX(128);
    $signatureData2 = $formData['signatureImageData2'];
    $signatureData2 = str_replace('data:image/png;base64,', '', $signatureData2);
    $signatureData2 = base64_decode($signatureData2);
    
    $tempSignature2 = tempnam(sys_get_temp_dir(), 'sig2');
    file_put_contents($tempSignature2, $signatureData2);
    
    $pdf->Image($tempSignature2, 138, $pdf->GetY(), 40, 15, 'PNG');
    unlink($tempSignature2);
}

// CHECKBOXES
$checkboxX = 142.5;
$checkboxY = 56;
$checkboxWidth = 5;
$labelWidth = 42;
$separatorWidth = 0.5;
$valueWidth = 22.5;

$pdf->SetY($checkboxY);

$selectedCategories = $_POST['categories'] ?? array();

$sections = [
    'TEMPS MATÉRIEL',
    'CONTRAT DE SERVICE',
    'STATIONNEMENT / PARKING',
    'AUTOCOLLANT / STICKER',
    'TRAVAIL COMPLÉTÉ',
    'FRAIS ENVIRONNEMENTAL',
    'ÉQUIPEMENT DÉSHYDRATATION',
    'ÉQUIPEMENT RÉCUPÉRATION',
    'S. OXYGÈNE ACÉTYLÈNE',
    'S. PROPANE',
    'ÉQUIPEMENT À PRESSION',
    'FOURNITURE D\'ATELIER',
    'FOURNITURE ÉLECTRIQUE',
    'FOURNITURE DE PLOMBERIE',
    'TRANSPORT ET 1ER 30 MIN',
    'INSTRUMENTATION'
];

foreach ($sections as $section) {
    $pdf->SetX($checkboxX);
    $pdf->SetFont('helvetica', 'B', 7);
    $pdf->SetFillColor(200, 200, 200);

    $isChecked = in_array($section, $selectedCategories);
    $checkMark = $isChecked ? 'X' : '';

    $pdf->Cell($checkboxWidth, 7, $checkMark, 1, 0, 'C', true);
    $pdf->Cell($labelWidth, 7, $section, 'LTB', 0, 'L', true);
    
    $pdf->SetFillColor(0, 0, 0);
    $pdf->Cell($separatorWidth, 7, '', 'TB', 0, 'C', true);
    
    $dollarWidth = $pdf->GetStringWidth('$');
    $dotWidth = $pdf->GetStringWidth('.');
    $availableSpace = $valueWidth - $dollarWidth - 2;
    $numDots = floor($availableSpace / $dotWidth);
    $dotsString = str_repeat('.', max(0, $numDots)) . '$';
    
    $pdf->SetFillColor(255, 255, 255);
    $pdf->Cell($valueWidth, 7, $dotsString, 'RTB', 1, 'L', true);
    
    $pdf->SetFillColor(200, 200, 200);
}

// SECTION FACTURATION
$pdf->SetY($checkboxY + (count($sections) * 7) + 1);
$pdf->SetFont('helvetica', 'B', 7);

// APPEL DE SERVICE
$pdf->SetX($checkboxX);
$pdf->SetFillColor(160, 160, 160);
$pdf->Cell(($labelWidth + $checkboxWidth), 7, 'APPEL DE SERVICE', 'LTB', 0, 'L', true);
$pdf->SetFillColor(0, 0, 0);
$pdf->Cell($separatorWidth, 7, '', 'TB', 0, 'C', true);
$pdf->SetFillColor(255, 255, 255);

if ($appelService == 0) {
    $dotWidth = $pdf->GetStringWidth('.');
    $dollarWidth = $pdf->GetStringWidth('$');
    $availableSpace = $valueWidth - $dollarWidth - 2;
    $numDots = floor($availableSpace / $dotWidth);
    $displayValue = str_repeat('.', max(0, $numDots)) . '$';
} else {
    $valueStr = number_format($appelService, 2, '.', ' ');
    $dollarWidth = $pdf->GetStringWidth('$');
    $valueStrWidth = $pdf->GetStringWidth($valueStr);
    $dotWidth = $pdf->GetStringWidth('.');
    $availableSpace = $valueWidth - $valueStrWidth - $dollarWidth - 3;
    $numDots = floor($availableSpace / $dotWidth);
    $displayValue = str_repeat('.', max(0, $numDots)) . $valueStr . ' $';
}
$pdf->Cell($valueWidth, 7, $displayValue, 'RTB', 1, 'L', true);

// MAIN D'OEUVRE
$pdf->SetX($checkboxX);
$pdf->SetFillColor(160, 160, 160);
$pdf->Cell(($labelWidth + $checkboxWidth), 7, 'MAIN D\'OEUVRE', 'LTB', 0, 'L', true);
$pdf->SetFillColor(0, 0, 0);
$pdf->Cell($separatorWidth, 7, '', 'TB', 0, 'C', true);
$pdf->SetFillColor(255, 255, 255);

if ($mainOeuvre == 0) {
    $dotWidth = $pdf->GetStringWidth('.');
    $dollarWidth = $pdf->GetStringWidth('$');
    $availableSpace = $valueWidth - $dollarWidth - 2;
    $numDots = floor($availableSpace / $dotWidth);
    $displayValue = str_repeat('.', max(0, $numDots)) . '$';
} else {
    $valueStr = number_format($mainOeuvre, 2, '.', ' ');
    $dollarWidth = $pdf->GetStringWidth('$');
    $valueStrWidth = $pdf->GetStringWidth($valueStr);
    $dotWidth = $pdf->GetStringWidth('.');
    $availableSpace = $valueWidth - $valueStrWidth - $dollarWidth - 3;
    $numDots = floor($availableSpace / $dotWidth);
    $displayValue = str_repeat('.', max(0, $numDots)) . $valueStr . ' $';
}
$pdf->Cell($valueWidth, 7, $displayValue, 'RTB', 1, 'L', true);

// SOUS TOTAL
$pdf->SetX($checkboxX);
$pdf->SetFillColor(160, 160, 160);
$pdf->Cell(($labelWidth + $checkboxWidth), 7, 'SOUS TOTAL', 'LTB', 0, 'L', true);
$pdf->SetFillColor(0, 0, 0);
$pdf->Cell($separatorWidth, 7, '', 'TB', 0, 'C', true);
$pdf->SetFillColor(255, 255, 255);

if ($sousTotal == 0) {
    $dotWidth = $pdf->GetStringWidth('.');
    $dollarWidth = $pdf->GetStringWidth('$');
    $availableSpace = $valueWidth - $dollarWidth - 2;
    $numDots = floor($availableSpace / $dotWidth);
    $displayValue = str_repeat('.', max(0, $numDots)) . '$';
} else {
    $valueStr = number_format($sousTotal, 2, '.', ' ');
    $dollarWidth = $pdf->GetStringWidth('$');
    $valueStrWidth = $pdf->GetStringWidth($valueStr);
    $dotWidth = $pdf->GetStringWidth('.');
    $availableSpace = $valueWidth - $valueStrWidth - $dollarWidth - 3;
    $numDots = floor($availableSpace / $dotWidth);
    $displayValue = str_repeat('.', max(0, $numDots)) . $valueStr . ' $';
}
$pdf->Cell($valueWidth, 7, $displayValue, 'RTB', 1, 'L', true);

// TPS
$pdf->SetX($checkboxX);
$pdf->SetFillColor(160, 160, 160);
$pdf->Cell(($labelWidth + $checkboxWidth), 7, 'TPS (5%)', 'LTB', 0, 'L', true);
$pdf->SetFillColor(0, 0, 0);
$pdf->Cell($separatorWidth, 7, '', 'TB', 0, 'C', true);
$pdf->SetFillColor(255, 255, 255);

if ($tps == 0) {
    $dotWidth = $pdf->GetStringWidth('.');
    $dollarWidth = $pdf->GetStringWidth('$');
    $availableSpace = $valueWidth - $dollarWidth - 2;
    $numDots = floor($availableSpace / $dotWidth);
    $displayValue = str_repeat('.', max(0, $numDots)) . '$';
} else {
    $valueStr = number_format($tps, 2, '.', ' ');
    $dollarWidth = $pdf->GetStringWidth('$');
    $valueStrWidth = $pdf->GetStringWidth($valueStr);
    $dotWidth = $pdf->GetStringWidth('.');
    $availableSpace = $valueWidth - $valueStrWidth - $dollarWidth - 3;
    $numDots = floor($availableSpace / $dotWidth);
    $displayValue = str_repeat('.', max(0, $numDots)) . $valueStr . ' $';
}
$pdf->Cell($valueWidth, 7, $displayValue, 'RTB', 1, 'L', true);

// TVQ
$pdf->SetX($checkboxX);
$pdf->SetFillColor(160, 160, 160);
$pdf->Cell(($labelWidth + $checkboxWidth), 7, 'TVQ (9.975%)', 'LTB', 0, 'L', true);
$pdf->SetFillColor(0, 0, 0);
$pdf->Cell($separatorWidth, 7, '', 'TB', 0, 'C', true);
$pdf->SetFillColor(255, 255, 255);

if ($tvq == 0) {
    $dotWidth = $pdf->GetStringWidth('.');
    $dollarWidth = $pdf->GetStringWidth('$');
    $availableSpace = $valueWidth - $dollarWidth - 2;
    $numDots = floor($availableSpace / $dotWidth);
    $displayValue = str_repeat('.', max(0, $numDots)) . '$';
} else {
    $valueStr = number_format($tvq, 2, '.', ' ');
    $dollarWidth = $pdf->GetStringWidth('$');
    $valueStrWidth = $pdf->GetStringWidth($valueStr);
    $dotWidth = $pdf->GetStringWidth('.');
    $availableSpace = $valueWidth - $valueStrWidth - $dollarWidth - 3;
    $numDots = floor($availableSpace / $dotWidth);
    $displayValue = str_repeat('.', max(0, $numDots)) . $valueStr . ' $';
}
$pdf->Cell($valueWidth, 7, $displayValue, 'RTB', 1, 'L', true);

// TOTAL
$pdf->SetX($checkboxX);
$pdf->SetFont('helvetica', 'B', 8);
$pdf->SetFillColor(235, 34, 38);
$pdf->SetTextColor(255, 255, 255);
$pdf->Cell(($labelWidth + $checkboxWidth), 7, 'TOTAL', 'LTB', 0, 'L', true);
$pdf->SetFillColor(0, 0, 0);
$pdf->Cell($separatorWidth, 7, '', 'TB', 0, 'C', true);
$pdf->SetFillColor(255, 255, 255);
$pdf->SetTextColor(0, 0, 0);

if ($total == 0) {
    $dotWidth = $pdf->GetStringWidth('.');
    $dollarWidth = $pdf->GetStringWidth('$');
    $availableSpace = $valueWidth - $dollarWidth - 2;
    $numDots = floor($availableSpace / $dotWidth);
    $displayValue = str_repeat('.', max(0, $numDots)) . '$';
} else {
    $valueStr = number_format($total, 2, '.', ' ');
    $dollarWidth = $pdf->GetStringWidth('$');
    $valueStrWidth = $pdf->GetStringWidth($valueStr);
    $dotWidth = $pdf->GetStringWidth('.');
    $availableSpace = $valueWidth - $valueStrWidth - $dollarWidth - 3;
    $numDots = floor($availableSpace / $dotWidth);
    $displayValue = str_repeat('.', max(0, $numDots)) . $valueStr . ' $';
}
$pdf->Cell($valueWidth, 7, $displayValue, 'RTB', 1, 'L', true);

    // =============================================
    // TRAITEMENT DES IMAGES UPLOADÉES
    // =============================================
    if (!empty($_FILES['uploaded_files']['tmp_name'])) {
        foreach ($_FILES['uploaded_files']['tmp_name'] as $key => $tmp_name) {
            if (empty($tmp_name)) continue;
            
            $uploadDir = 'uploads/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
            
            $fileName = $_FILES['uploaded_files']['name'][$key];
            $imagePath = $uploadDir . basename($fileName);
            
            // Déplacer le fichier uploadé
            if (move_uploaded_file($tmp_name, $imagePath)) {
                if (file_exists($imagePath)) {
                    // Détecter le type d'image automatiquement
                    $imageInfo = getimagesize($imagePath);
                    $imageType = '';
                    
                    if ($imageInfo !== false) {
                        switch ($imageInfo[2]) {
                            case IMAGETYPE_JPEG:
                                $imageType = 'JPEG';
                                break;
                            case IMAGETYPE_PNG:
                                $imageType = 'PNG';
                                break;
                            case IMAGETYPE_GIF:
                                $imageType = 'GIF';
                                break;
                            default:
                                // Type non supporté, passer au suivant
                                unlink($imagePath);
                                continue 2;
                        }
                        
                        // Ajouter une page et insérer l'image
                        $pdf->AddPage();
                        $pdf->Image($imagePath, ($pdf->getPageWidth() - 150) / 2, 40, 150, 0, $imageType);
                    }
                    
                    // Supprimer le fichier temporaire
                    unlink($imagePath);
                }
            }
        }
    }

    // =============================================
    // MISE À JOUR BASE DE DONNÉES - COMMENTÉ
    // =============================================
    
    /*
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
    */

    // Incrémenter le compteur de work orders complétés
    $conn = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);
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
            width: 100%;
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
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
            width: 100%;
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
        input[type="time"],
        input[type="date"],
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
        input[type="time"]:focus,
        input[type="date"]:focus,
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

        /* Style pour les erreurs */
        .error-message {
            color: #dc3545;
            font-size: 13px;
            margin-top: 5px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        input.error {
            border-color: #dc3545;
            background: #fff5f5;
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

        input[type="checkbox"] {
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
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .signature-canvas {
            border: 2px solid #ced4da;
            border-radius: 8px;
            margin: 10px 0;
            background: white;
            width: 100%;
            max-width: 100%;
            height: 150px;
            display: block;
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
            width: 100%;
        }

        .btn-group .btn {
            min-width: 200px;
            margin: 5px;
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

        .billing-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            margin: 20px 0;
        }

        .billing-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #dee2e6;
            gap: 10px;
            flex-wrap: wrap;
        }

        .billing-row:last-child {
            border-bottom: none;
            background: #eb2226;
            color: white;
            margin-top: 10px;
            padding: 12px;
            border-radius: 4px;
            font-weight: bold;
        }

        .billing-label {
            font-weight: 600;
        }

        .billing-value {
            font-family: 'Courier New', monospace;
            font-size: 16px;
        }

        /* Styles pour les équipements */
        .equipment-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            margin: 20px 0;
        }

        .equipment-row {
            display: grid;
            grid-template-columns: minmax(220px, 2fr) minmax(140px, 1fr) auto;
            gap: 10px;
            margin-bottom: 10px;
            align-items: end;
        }

        .equipment-row > div:last-child {
            display: flex;
            align-items: center;
            justify-content: flex-start;
        }

        .btn-add {
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            margin-top: 10px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-add:hover {
            background: #218838;
        }

        .btn-remove {
            background: #dc3545;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
        }

        .btn-remove:hover {
            background: #c82333;
        }

        /* Style pour les inputs de temps */
        input[type="time"] {
            cursor: pointer;
        }

        /* Styles pour la prévisualisation des images */
        .image-preview-container {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 15px;
            width: 100%;
        }

        .image-preview-item {
            position: relative;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            overflow: hidden;
            background: #f8f9fa;
            transition: all 0.3s ease;
        }

        .image-preview-item:hover {
            border-color: #eb2226;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .image-preview-item img {
            width: 100%;
            height: 150px;
            object-fit: cover;
            display: block;
        }

        .image-remove-btn {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #dc3545;
            color: white;
            border: none;
            border-radius: 50%;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .image-remove-btn:hover {
            background: #c82333;
            transform: scale(1.1);
        }

        .image-name {
            padding: 8px;
            font-size: 12px;
            color: #6c757d;
            text-align: center;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Responsive Design */
        @media (max-width: 992px) {
            body {
                padding: 15px;
            }

            .container {
                padding: 15px;
            }

            .header {
                padding: 24px;
            }

            .form-container {
                padding: 24px;
            }

            .equipment-row {
                grid-template-columns: minmax(220px, 1fr) minmax(140px, 1fr);
            }

            .btn-group {
                justify-content: center;
            }
        }

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

            .equipment-row {
                grid-template-columns: 1fr;
                align-items: stretch;
            }

            .equipment-row > div:last-child {
                justify-content: flex-start;
            }

            .btn-group {
                flex-direction: column;
                align-items: stretch;
            }

            .btn {
                width: 100%;
            }

            .signature-section {
                padding: 16px;
            }

            .signature-canvas {
                height: 140px;
            }

            .billing-section {
                padding: 15px;
            }

            .image-preview-container {
                grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
            }

            .image-preview-item img {
                height: 120px;
            }

            .image-name {
                font-size: 11px;
            }
        }

        @media (max-width: 480px) {
            .header {
                padding: 16px;
            }

            .page-title {
                font-size: 22px;
            }

            .form-container {
                padding: 16px;
            }

            .signature-canvas {
                height: 130px;
            }

            .btn {
                font-size: 13px;
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
                        <label for="technician_name">Technician Name:</label>
                        <input type="text" name="name_technician" value="<?php echo $userFullName; ?>" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="client_name">Sold To:</label>
                        <input type="text" name="client_name" id="client_name" required placeholder="Required">
                    </div>
                    <div class="form-group">
                        <label for="sold_to_address">Address:</label>
                        <input type="text" name="sold_to_address" id="sold_to_address" required placeholder="Required">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="ship_to_name">Ship To:</label>
                        <input type="text" name="ship_to_name" id="ship_to_name" required placeholder="Required">
                    </div>
                    <div class="form-group">
                        <label for="ship_to_address">Address:</label>
                        <input type="text" name="ship_to_address" id="ship_to_address" required placeholder="Required">
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
                        <input type="date" id="date" name="date" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="depart_bureau_time">Office Departure:</label>
                        <input type="time" id="depart_bureau_time" name="depart_bureau_time" required>
                        <div class="error-message" id="error_depart_bureau">⚠️ Invalid time</div>
                    </div>
                    <div class="form-group">
                        <label for="arrive_site_time">Arrived on Site:</label>
                        <input type="time" id="arrive_site_time" name="arrive_site_time" required>
                        <div class="error-message" id="error_arrive_site">⚠️ Must be after office departure</div>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="depart_site_time">Site Departure:</label>
                        <input type="time" id="depart_site_time" name="depart_site_time" required>
                        <div class="error-message" id="error_depart_site">⚠️ Must be after site arrival</div>
                    </div>
                    <div class="form-group">
                        <label for="arrive_bureau_time">Arrived Office:</label>
                        <input type="time" id="arrive_bureau_time" name="arrive_bureau_time" required>
                        <div class="error-message" id="error_arrive_bureau">⚠️ Must be after site departure</div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="km">Kilometers Traveled:</label>
                    <input type="number" name="km" step="1" min="0" required placeholder="Required">
                </div>

                <div class="form-group">
                    <label for="description">Description:</label>
                    <textarea name="description" rows="6" maxlength="1100" required placeholder="Describe the work performed..."></textarea>
                </div>

                <div class="form-group">
                    <label for="recommendation">Recommendation:</label>
                    <select name="recommendation" id="recommendation">
                        <option value="">No recommendation</option>
                        <option value="Test1">Test1</option>
                        <option value="Test2">Test2</option>
                        <option value="Test3">Test3</option>
                    </select>
                </div>

                <!-- SECTION EQUIPMENT -->
                <div class="equipment-section">
                    <h3 style="margin-bottom: 15px; color: #2c3e50;">Equipment Used</h3>
                    <div id="equipment-container">
                        <div class="equipment-row">
                            <div class="form-group">
                                <label>Equipment Name:</label>
                                <input type="text" name="equipment_name[]" placeholder="Enter equipment name">
                            </div>
                            <div class="form-group">
                                <label>Price ($):</label>
                                <input type="number" name="equipment_price[]" step="0.01" min="0" placeholder="0.00">
                            </div>
                            <div>
                                <button type="button" class="btn-remove" onclick="removeEquipment(this)" style="visibility: hidden;">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-add" onclick="addEquipment()">
                        <i class="fas fa-plus"></i>
                        Add Equipment
                    </button>
                </div>

                <div class="form-group">
                    <label>Work Categories:</label>
                    <div style="display: grid; gap: 12px; margin-top: 10px;">
                        <div class="radio-item">
                            <input type="checkbox" name="categories[]" value="TEMPS MATÉRIEL" id="cat1">
                            <label for="cat1">Temps matériel</label>
                        </div>
                        <div class="radio-item">
                            <input type="checkbox" name="categories[]" value="CONTRAT DE SERVICE" id="cat2">
                            <label for="cat2">Contrat de service</label>
                        </div>
                        <div class="radio-item">
                            <input type="checkbox" name="categories[]" value="STATIONNEMENT / PARKING" id="cat3">
                            <label for="cat3">Stationnement / Parking</label>
                        </div>
                        <div class="radio-item">
                            <input type="checkbox" name="categories[]" value="AUTOCOLLANT / STICKER" id="cat4">
                            <label for="cat4">Autocollant / Sticker</label>
                        </div>
                        <div class="radio-item">
                            <input type="checkbox" name="categories[]" value="TRAVAIL COMPLÉTÉ" id="cat5">
                            <label for="cat5">Travail complété</label>
                        </div>
                        <div class="radio-item">
                            <input type="checkbox" name="categories[]" value="FRAIS ENVIRONNEMENTAL" id="cat6">
                            <label for="cat6">Frais environnemental</label>
                        </div>
                        <div class="radio-item">
                            <input type="checkbox" name="categories[]" value="ÉQUIPEMENT DÉSHYDRATATION" id="cat7">
                            <label for="cat7">Équipement déshydratation</label>
                        </div>
                        <div class="radio-item">
                            <input type="checkbox" name="categories[]" value="ÉQUIPEMENT RÉCUPÉRATION" id="cat8">
                            <label for="cat8">Équipement récupération</label>
                        </div>
                        <div class="radio-item">
                            <input type="checkbox" name="categories[]" value="S. OXYGÈNE ACÉTYLÈNE" id="cat9">
                            <label for="cat9">S. Oxygène acétylène</label>
                        </div>
                        <div class="radio-item">
                            <input type="checkbox" name="categories[]" value="S. PROPANE" id="cat10">
                            <label for="cat10">S. Propane</label>
                        </div>
                        <div class="radio-item">
                            <input type="checkbox" name="categories[]" value="ÉQUIPEMENT À PRESSION" id="cat11">
                            <label for="cat11">Équipement à pression</label>
                        </div>
                        <div class="radio-item">
                            <input type="checkbox" name="categories[]" value="FOURNITURE D'ATELIER" id="cat12">
                            <label for="cat12">Fourniture d'atelier</label>
                        </div>
                        <div class="radio-item">
                            <input type="checkbox" name="categories[]" value="FOURNITURE ÉLECTRIQUE" id="cat13">
                            <label for="cat13">Fourniture électrique</label>
                        </div>
                        <div class="radio-item">
                            <input type="checkbox" name="categories[]" value="FOURNITURE DE PLOMBERIE" id="cat14">
                            <label for="cat14">Fourniture de plomberie</label>
                        </div>
                        <div class="radio-item">
                            <input type="checkbox" name="categories[]" value="TRANSPORT ET 1ER 30 MIN" id="cat15">
                            <label for="cat15">Transport et 1er 30 min</label>
                        </div>
                        <div class="radio-item">
                            <input type="checkbox" name="categories[]" value="INSTRUMENTATION" id="cat16">
                            <label for="cat16">Instrumentation</label>
                        </div>
                    </div>
                </div>

                <!-- SECTION FACTURATION -->
                <div class="billing-section">
                    <h3 style="margin-bottom: 15px; color: #2c3e50;">Billing Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="appel_service">Service Call ($):</label>
                            <input type="number" name="appel_service" id="appel_service" step="0.01" min="0" value="0" placeholder="0.00">
                        </div>
                        <div class="form-group">
                            <label for="main_oeuvre">Labor ($):</label>
                            <input type="number" name="main_oeuvre" id="main_oeuvre" step="0.01" min="0" value="0" placeholder="0.00">
                        </div>
                    </div>

                    <div class="billing-row">
                        <span class="billing-label">Subtotal:</span>
                        <span class="billing-value" id="sous_total_display">0.00 $</span>
                    </div>
                    <div class="billing-row">
                        <span class="billing-label">GST (5%):</span>
                        <span class="billing-value" id="tps_display">0.00 $</span>
                    </div>
                    <div class="billing-row">
                        <span class="billing-label">QST (9.975%):</span>
                        <span class="billing-value" id="tvq_display">0.00 $</span>
                    </div>
                    <div class="billing-row">
                        <span class="billing-label">TOTAL:</span>
                        <span class="billing-value" id="total_display">0.00 $</span>
                    </div>
                </div>

                <div class="signature-section">
                    <label for="signature">Technician Signature:</label>
                    <canvas id="signatureCanvas" class="signature-canvas"></canvas>
                    <input type="hidden" name="signature" id="signatureInput">
                    <button type="button" onclick="clearSignature()" class="btn btn-neutral">
                        <i class="fas fa-eraser"></i>
                        Clear
                    </button>
                </div>

                <div class="signature-section">
                    <label for="signature2">Client Signature:</label>
                    <canvas id="signatureCanvas2" class="signature-canvas"></canvas>
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
                        <input type="file" name="uploaded_files[]" multiple id="upload_files" accept="image/jpeg,image/jpg,image/png">
                        <label for="upload_files" class="file-input-label">
                            <i class="fas fa-cloud-upload-alt"></i>
                            Choose files... (JPG, JPEG, PNG)
                        </label>
                    </div>
                    <div id="image-preview-container" class="image-preview-container"></div>
                </div>

                <div class="btn-group">
                    <input type="submit" value="Send Work Order" class="btn btn-primary" style="background: #eb2226; border: none;">
                </div>
            </form>
        </div>
    </div>

<script>
// Gestion des équipements
let equipmentCount = 1;
const maxEquipment = 15;

function addEquipment() {
    if (equipmentCount >= maxEquipment) {
        alert('Maximum 15 equipment allowed');
        return;
    }
    
    equipmentCount++;
    const container = document.getElementById('equipment-container');
    const newRow = document.createElement('div');
    newRow.className = 'equipment-row';
    newRow.innerHTML = `
        <div class="form-group">
            <label>Equipment Name:</label>
            <input type="text" name="equipment_name[]" placeholder="Enter equipment name">
        </div>
        <div class="form-group">
            <label>Price ($):</label>
            <input type="number" name="equipment_price[]" step="0.01" min="0" placeholder="0.00">
        </div>
        <div>
            <button type="button" class="btn-remove" onclick="removeEquipment(this)">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    `;
    container.appendChild(newRow);
}

function removeEquipment(button) {
    if (equipmentCount <= 1) {
        alert('At least one equipment is required');
        return;
    }
    
    button.closest('.equipment-row').remove();
    equipmentCount--;
}

// Gestion de la prévisualisation des images
let selectedFiles = [];

document.getElementById('upload_files').addEventListener('change', function(e) {
    const files = Array.from(e.target.files);
    selectedFiles = [...selectedFiles, ...files];
    displayImagePreviews();
});

function displayImagePreviews() {
    const container = document.getElementById('image-preview-container');
    container.innerHTML = '';
    
    selectedFiles.forEach((file, index) => {
        const reader = new FileReader();
        
        reader.onload = function(e) {
            const previewItem = document.createElement('div');
            previewItem.className = 'image-preview-item';
            previewItem.innerHTML = `
                <img src="${e.target.result}" alt="${file.name}">
                <button type="button" class="image-remove-btn" onclick="removeImage(${index})" title="Remove image">
                    ×
                </button>
                <div class="image-name" title="${file.name}">${file.name}</div>
            `;
            container.appendChild(previewItem);
        };
        
        reader.readAsDataURL(file);
    });
    
    // Mettre à jour l'input file avec les fichiers restants
    updateFileInput();
}

function removeImage(index) {
    selectedFiles.splice(index, 1);
    displayImagePreviews();
}

function updateFileInput() {
    const dt = new DataTransfer();
    selectedFiles.forEach(file => dt.items.add(file));
    document.getElementById('upload_files').files = dt.files;
}

// VALIDATION DES HEURES
function validateTimes() {
    const departBureau = document.getElementById('depart_bureau_time').value;
    const arriveSite = document.getElementById('arrive_site_time').value;
    const departSite = document.getElementById('depart_site_time').value;
    const arriveBureau = document.getElementById('arrive_bureau_time').value;
    
    // Réinitialiser les erreurs
    document.querySelectorAll('.error-message').forEach(el => el.classList.remove('show'));
    document.querySelectorAll('input[type="time"]').forEach(el => el.classList.remove('error'));
    
    let isValid = true;
    
    // Vérifier que toutes les heures sont remplies
    if (!departBureau || !arriveSite || !departSite || !arriveBureau) {
        return false;
    }
    
    // Convertir en minutes pour comparaison facile
    function timeToMinutes(time) {
        const [hours, minutes] = time.split(':').map(Number);
        return hours * 60 + minutes;
    }
    
    const departBureauMin = timeToMinutes(departBureau);
    const arriveSiteMin = timeToMinutes(arriveSite);
    const departSiteMin = timeToMinutes(departSite);
    const arriveBureauMin = timeToMinutes(arriveBureau);
    
    // Validation 1: Arrivée site doit être après départ bureau
    if (arriveSiteMin <= departBureauMin) {
        document.getElementById('arrive_site_time').classList.add('error');
        document.getElementById('error_arrive_site').classList.add('show');
        isValid = false;
    }
    
    // Validation 2: Départ site doit être après arrivée site
    if (departSiteMin <= arriveSiteMin) {
        document.getElementById('depart_site_time').classList.add('error');
        document.getElementById('error_depart_site').classList.add('show');
        isValid = false;
    }
    
    // Validation 3: Retour bureau doit être après départ site
    if (arriveBureauMin <= departSiteMin) {
        document.getElementById('arrive_bureau_time').classList.add('error');
        document.getElementById('error_arrive_bureau').classList.add('show');
        isValid = false;
    }
    
    return isValid;
}

// Validation en temps réel
document.getElementById('depart_bureau_time').addEventListener('change', validateTimes);
document.getElementById('arrive_site_time').addEventListener('change', validateTimes);
document.getElementById('depart_site_time').addEventListener('change', validateTimes);
document.getElementById('arrive_bureau_time').addEventListener('change', validateTimes);

// Validation avant soumission
document.getElementById('loginform').addEventListener('submit', function(e) {
    if (!validateTimes()) {
        e.preventDefault();
        alert('❌ The times entered are not logical. Please verify:\n\n' +
              '1. Site arrival must be AFTER office departure\n' +
              '2. Site departure must be AFTER site arrival\n' +
              '3. Office return must be AFTER site departure');
        
        // Scroll vers la première erreur
        document.querySelector('.error').scrollIntoView({ behavior: 'smooth', block: 'center' });
        return false;
    }
});

// Calcul automatique de la facturation
function calculateBilling() {
    const appelService = parseFloat(document.getElementById('appel_service').value) || 0;
    const mainOeuvre = parseFloat(document.getElementById('main_oeuvre').value) || 0;
    
    const sousTotal = appelService + mainOeuvre;
    const tps = sousTotal * 0.05;
    const tvq = sousTotal * 0.09975;
    const total = sousTotal + tps + tvq;
    
    document.getElementById('sous_total_display').textContent = sousTotal.toFixed(2) + ' $';
    document.getElementById('tps_display').textContent = tps.toFixed(2) + ' $';
    document.getElementById('tvq_display').textContent = tvq.toFixed(2) + ' $';
    document.getElementById('total_display').textContent = total.toFixed(2) + ' $';
}

document.getElementById('appel_service').addEventListener('input', calculateBilling);
document.getElementById('main_oeuvre').addEventListener('input', calculateBilling);

// Fonction pour redimensionner correctement le canvas
function resizeCanvas(canvas) {
    const ratio = Math.max(window.devicePixelRatio || 1, 1);
    canvas.width = canvas.offsetWidth * ratio;
    canvas.height = canvas.offsetHeight * ratio;
    canvas.getContext("2d").scale(ratio, ratio);
}

// Initialiser les canvas
const canvas1 = document.getElementById('signatureCanvas');
const canvas2 = document.getElementById('signatureCanvas2');

resizeCanvas(canvas1);
resizeCanvas(canvas2);

// Signature Pads
var signaturePad1 = new SignaturePad(canvas1);
var signaturePad2 = new SignaturePad(canvas2);

// Redimensionner au changement de taille de fenêtre
window.addEventListener('resize', function() {
    resizeCanvas(canvas1);
    resizeCanvas(canvas2);
    signaturePad1.clear();
    signaturePad2.clear();
});

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

// Définir la date d'aujourd'hui par défaut
document.getElementById('date').valueAsDate = new Date();

</script>
</body>
</html>