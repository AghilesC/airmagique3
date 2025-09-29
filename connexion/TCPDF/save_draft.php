<?php
session_start(); // Ensure sessions are active
require('tcpdf.php');
include "../../config.php";
include_once "../logincheck.php";

// Debugging - Check session user_id
error_log("Debug: Session user_id = " . ($_SESSION['user_id'] ?? 'NOT SET'));

if (!isset($_SESSION['user_id']) || empty($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User ID is missing from session. Please log in again."]);
    exit;
}

$userId = $_SESSION['user_id']; // Username stored in session

// Create database connection
$conn = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);

// Check database connection
if (!$conn) {
    echo json_encode(["success" => false, "message" => "Database connection failed: " . mysqli_connect_error()]);
    exit;
}

// Retrieve idacount using username
$checkUser = "SELECT idacount FROM account WHERE LOWER(username) = LOWER('$userId')";
$userResult = $conn->query($checkUser);

if ($userResult->num_rows === 0) {
    echo json_encode(["success" => false, "message" => "Invalid user. Cannot save draft."]);
    exit;
}

$row = $userResult->fetch_assoc();
$idacount = $row['idacount']; // Store retrieved idacount

// Set JSON response header
header('Content-Type: application/json');

// Retrieve and sanitize form data
$glpi_ticket_id = $conn->real_escape_string($_POST['glpi_ticket_id']);
if (empty($glpi_ticket_id)) {
    echo json_encode(["success" => false, "message" => "GLPI Ticket ID is required."]);
    exit;
}

$client_name = $conn->real_escape_string($_POST['client_name'] ?? '');
$nb_customer = $conn->real_escape_string($_POST['nb_customer'] ?? '');
$contact_name = $conn->real_escape_string($_POST['contact_name'] ?? '');
$work_address = $conn->real_escape_string($_POST['work_address'] ?? '');
$phone_number = $conn->real_escape_string($_POST['phone_number'] ?? '');
$date = $conn->real_escape_string($_POST['date'] ?? null);
$depart_bureau_time = $conn->real_escape_string($_POST['depart_bureau_time'] ?? null);
$arrive_site_time = $conn->real_escape_string($_POST['arrive_site_time'] ?? null);
$depart_site_time = $conn->real_escape_string($_POST['depart_site_time'] ?? null);
$arrive_bureau_time = $conn->real_escape_string($_POST['arrive_bureau_time'] ?? null);
$km = $conn->real_escape_string($_POST['km'] ?? 0);
$description = $conn->real_escape_string($_POST['description'] ?? '');
$equipment = isset($_POST['equipement']) ? implode(',', $_POST['equipement']) : '';

// Insert data into `draft` table
$sql = "INSERT INTO draft (idacount, glpi_ticket_id, client_name, nb_customer, contact_name, 
        work_address, phone_number, date, depart_bureau_time, arrive_site_time, depart_site_time, 
        arrive_bureau_time, km, description, equipment) 
        VALUES ('$idacount', '$glpi_ticket_id', '$client_name', '$nb_customer', '$contact_name', 
        '$work_address', '$phone_number', '$date', '$depart_bureau_time', '$arrive_site_time', 
        '$depart_site_time', '$arrive_bureau_time', '$km', '$description', '$equipment')";

// Execute query and return response
if ($conn->query($sql) === TRUE) {
    echo json_encode(["success" => true, "message" => "Draft saved successfully!"]);
} else {
    echo json_encode(["success" => false, "message" => "Error saving draft: " . $conn->error]);
}

// Close connection
$conn->close();
?>
