<?php 
include "../logincheck.php";
include_once "../permissions.php";


if (!isset($_SESSION['user_id']) || strtolower($_SESSION['user_id']) !== "ctiai") {

    header("Location: ../index.php");
    exit();
}
?>


<!DOCTYPE html>
<html lang="en"> <!-- By AGHILES CHAOUCHE 2023 ©-->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventaire - inv.ctiai.com</title>
    <link rel="stylesheet" href="destructionrma.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">


    
</head>