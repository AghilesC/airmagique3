<?php
include "../../config.php";

$conn = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);


if ($conn->connect_error) {
    die("Échec de la connexion à la base de données : " . $conn->connect_error);
}


$currentDay = date("N");


$currentHour = date("H");

$currentMinute = date("i");



$truncateQuery = "TRUNCATE TABLE history";
if ($conn->query($truncateQuery) === TRUE) {
    echo "La table 'history' a été réinitialisée avec succès.";
} else {
    echo "Erreur lors de la réinitialisation de la table 'history' : " . $conn->error;
}


$conn->close();
?>


