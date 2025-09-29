<?php
session_start();
include_once "permissions.php";
include "../config.php";

if (isset($_POST['envoi'])) {
    $identifiant = $_POST['identifiant'];
    $mdp = $_POST['mdp'];

    // Connexion à la base de données
    $connexion = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);

    if (!$connexion) {
        die("La connexion à la base de données a échoué : " . mysqli_connect_error());
    }

    $query = "SELECT * FROM account WHERE LOWER(username)=LOWER(?)";
    $stmt = mysqli_prepare($connexion, $query);

    // Vérifiez si la préparation de la requête a réussi
    if ($stmt === false) {
        die("Erreur de préparation de la requête : " . mysqli_error($connexion));
    }

    mysqli_stmt_bind_param($stmt, "s", $identifiant);
    mysqli_stmt_execute($stmt);

    // Vérifiez si la requête a été exécutée correctement
    if (!mysqli_stmt_execute($stmt)) {
        die("Erreur d'exécution de la requête : " . mysqli_stmt_error($stmt));
    }

    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        if (password_verify($mdp, $row['pwd'])) {
            // Stocker l'idacount (ID numérique) au lieu du username
            $_SESSION['user_id'] = $row['idacount'];
            $_SESSION['username'] = $identifiant;
            $_SESSION['first_name'] = $row['first_name'];
            $_SESSION['last_name'] = $row['last_name'];
            $_SESSION['mail'] = $row['mail'];
            $_SESSION['depot'] = $row['depot'];
            $_SESSION['partner_email'] = $row['partner_email'];

            // Cookie avec l'ID numérique
            setcookie("user_id", $row['idacount'], time() + 3600, "/");

            // Redirection en fonction des permissions
            // Vérification spéciale pour airmagique + permission admin
            if (strtolower($row['username']) == "airmagique" && checkAdminPermission($row['idacount'])) {
                header("Location: ./adminmenu/adminmenu.php");
            } else {
                header("Location: ./techmenu/techmenu.php");
            }

            exit();
        } else {
            $error_message = "Identifiant ou mot de passe incorrect";
        }
    } else {
        $error_message = "Identifiant ou mot de passe incorrect";
    }

    // Affiche le message d'erreur via JavaScript
    echo "<script>
            alert('$error_message');
            window.location.href = './index.php';
          </script>";

    mysqli_stmt_close($stmt);
    mysqli_close($connexion);
}
?>