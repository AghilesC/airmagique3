<?php 
include "../../config.php";
include_once "../logincheck.php";
include_once "../permissions.php";

// Vérifier si l'utilisateur est connecté et s'il a les autorisations nécessaires
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_id']) !== "ctiai") {
    // Rediriger vers une page d'erreur ou une autre destination appropriée
    header("Location: ../index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal des évènements - inv.ctiai.com</title>
    <style>
        table {
            border-collapse: collapse;
            width: 33%;
        }
        th, td {
            border: 1px solid black;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
    </style>
</head>
<body>
    <h1>Journal des évènements</h1>
    <table>
        <tr>
            <th>Date et heure</th>
            <th>ID du partenaire</th>
            <th>Modification</th>
            <a href="./majbdd.php" name="retour" id="retour" class="retour" >Revenir à la base de donnée</a>


        </tr>
        <?php
            // Connexion à la base de données
            $connexion = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);
            
            // Vérification de la connexion
            if (!$connexion) {
                die("La connexion à la base de données a échoué: " . mysqli_connect_error());
            }
            
            // Requête SQL pour récupérer toutes les lignes de la table changelog
            // Requête SQL pour récupérer toutes les lignes de la table changelog, triées par date_heure décroissante
            $sql = "SELECT * FROM changelog ORDER BY date_time DESC";

            $resultat = mysqli_query($connexion, $sql);
            
            // Vérification s'il y a des résultats
            if (mysqli_num_rows($resultat) > 0) {
                // Affichage des données
                while ($ligne = mysqli_fetch_assoc($resultat)) {
                    echo "<tr>";
                    echo "<td>" . $ligne['date_time'] . "</td>";
                    echo "<td>" . $ligne['partner_id'] . "</td>";
                    echo "<td>" . $ligne['details'] . "</td>";
                    // Ajoutez d'autres colonnes au besoin en utilisant $ligne['nom_colonne']
                    echo "</tr>";
                }
            } else {
                echo "<tr><td colspan='3'>Aucun enregistrement trouvé</td></tr>";
            }
            
            // Fermeture de la connexion à la base de données
            mysqli_close($connexion);
        ?>
    </table>
</body>
</html>
