<?php
include "../../config.php";
$connexion = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);

$query = "SELECT city, partner_id FROM `partner` ORDER BY city ASC"; // Tri par ordre alphabétique croissant
$result = mysqli_query($connexion, $query);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $city = $row['city'];
        $partner_id = $row['partner_id'];

        // Ajouter une option pour le partenaire dans la liste déroulante
        echo "<option value='$partner_id'>$city</option>";
    }
}
mysqli_close($connexion);
?>
