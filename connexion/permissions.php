<?php
// Fonction pour vérifier si l'utilisateur a la permission d'accéder à l'administration
function checkAdminPermission($identifiant) {
    $identifiant = strtolower($identifiant); // Convertir l'identifiant en minuscules
    if ($identifiant == "airmagique") {
        return true; // L'utilisateur ctiai a toujours la permission d'accéder à l'administration
    } else {
        return false; // Les autres utilisateurs n'ont pas la permission d'accéder à l'administration
    }
}


?>
