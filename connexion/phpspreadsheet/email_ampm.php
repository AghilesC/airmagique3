<?php
include "../../config.php";
require 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$conn = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);

if ($conn->connect_error) {
    die("Échec de la connexion à la base de données : " . $conn->connect_error);
}

$query = "SELECT * FROM history WHERE partner_id BETWEEN 1 AND 27";
$result = $conn->query($query);

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

$header = ["ID Partner", "Address", "Equipment", "Tech Name", "GLPI"];
$sheet->fromArray([$header], null, 'A1');

$styleHeader = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
    'fill' => [
        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
        'startColor' => ['rgb' => '3a63bb'],
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
        ],
    ],
];

$sheet->getStyle('A1:E1')->applyFromArray($styleHeader);

$row = 2;
while ($row_data = $result->fetch_assoc()) {
    $data = [
        $row_data['partner_id'],
        $row_data['address'],
        $row_data['equipment'],
        $row_data['tech_name'],
        $row_data['ticket_id']
    ];

    $sheet->fromArray($data, null, 'A' . $row);

    $styleData = [
        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
        'borders' => [
            'allBorders' => [
                'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
            ],
        ],
    ];

    if ($row % 2 == 0) {
        $styleData['fill'] = [
            'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'e2ac18'],
        ];
    }

    // Appliquer le style à toutes les colonnes de A à E pour cette ligne
    $sheet->getStyle('A' . $row . ':E' . $row)->applyFromArray($styleData);

    $row++;
}

$sheet->setCellValue('F1', date('Y-m-d'));

$dateStyle = [
    'font' => [
        'name' => 'Arial',
        'size' => 15,
        'color' => ['rgb' => '3a63bb'],
        'bold' => true,
    ],
];

$sheet->getStyle('F1')->applyFromArray($dateStyle);

foreach (range('A', 'E') as $column) {
    $sheet->getColumnDimension($column)->setAutoSize(true);
}

$filename = tempnam(sys_get_temp_dir(), 'excel');
$writer = new Xlsx($spreadsheet);
$writer->save($filename);

$mail = new PHPMailer();

$mail->isSMTP();
$mail->Host = 'smtp.office365.com';
$mail->Port = 587;
$mail->SMTPAuth = true;
$mail->Username = 'rapport-inventaire@ctiai.com';
$mail->Password = 'XiLeXTi2kKhtqyCD9VdV';
$mail->setFrom('rapport-inventaire@ctiai.com', 'Inventory AMPM - CTIAI');
$mail->addAddress('shashank.suri@ampmservice.com');
$mail->Subject = "Daily inventory report " . date("Y/m/d") . "";
$mail->Body = "Good morning,

Please find the inventory report for " . date("Y/m/d") . " in attachment.

Thank you and have a nice day ! 😀";

$mail->CharSet = 'UTF-8';

$todayDate = date("d-m-Y");
$excelFileName = 'inventory_report_' . $todayDate . '.xlsx';

$mail->addAttachment($filename, $excelFileName);

if ($mail->send()) {
    echo "E-mail envoyé avec succès.";
} else {
    echo "Erreur lors de l'envoi de l'e-mail : " . $mail->ErrorInfo;
}

unlink($filename);

$conn->close();
?>
