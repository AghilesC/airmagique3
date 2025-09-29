<?php
// get_store_info.php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventaire";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $storeNumber = $_POST['storeNumber'];

    $sql = "SELECT address, city, postal_code, phone_number FROM store WHERE store_number = '$storeNumber'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $storeInfo = array(
            'address' => $row['address'],
            'city' => $row['city'],
            'postal_code' => $row['postal_code'],
            'phone_number' => $row['phone_number']
        );
        echo json_encode($storeInfo);
    } else {
        echo json_encode(array());
    }

    $conn->close();
}
?>
