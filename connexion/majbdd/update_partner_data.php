<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    include "../../config.php";
    $connexion = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);

    if (!$connexion) {
        die("Connection failed: " . mysqli_connect_error());
    }

    $field = $_POST["field"];
    $newValue = $_POST["value"];
    $partner_id = $_POST["partner_id"];

    // Récupérer l'ancienne valeur de l'équipement pour le partenaire actuel
    $queryOldValue = "SELECT `$field` FROM `partner` WHERE `partner_id` = $partner_id";
    $resultOldValue = mysqli_query($connexion, $queryOldValue);
    $row = mysqli_fetch_assoc($resultOldValue);
    $oldValue = $row[$field];

    // Mettre à jour la valeur de l'équipement pour le partenaire actuel
    $queryUpdate = "UPDATE `partner` SET `$field` = '$newValue' WHERE `partner_id` = $partner_id";
    $resultUpdate = mysqli_query($connexion, $queryUpdate);

    if ($resultUpdate) {
        echo "Data updated successfully";


        $difference = $newValue - $oldValue;


        if ($partner_id != 50) {

            if ($difference > 0) {
                $other_queryUpdate = "UPDATE `partner` SET `$field` = `$field` - $difference WHERE `partner_id` = 50";
                $other_resultUpdate = mysqli_query($connexion, $other_queryUpdate);

                if ($other_resultUpdate) {
                    echo "Data updated for partner 50 successfully";
                } else {
                    echo "Error updating data for partner 50: " . mysqli_error($connexion);
                }
            }
        }
    } else {
        echo "Error updating data: " . mysqli_error($connexion);
    }

    mysqli_close($connexion);
}
?>
