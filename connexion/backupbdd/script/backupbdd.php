<?php
include "../../../config.php";
$connexion = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);


if (!$connexion) {
    die("La connexion à la base de données a échoué : " . mysqli_connect_error());
}


$directory = '../';


$filename = $directory . 'backup_' . date('Y-m-d_H-i-s') . '.sql';


$file = fopen($filename, 'w');


function writeToFile($file, $content) {
    fwrite($file, $content . PHP_EOL); 
}


$tables_result = mysqli_query($connexion, "SHOW TABLES");
$tables = mysqli_fetch_all($tables_result);
foreach ($tables as $table) {
    $table_name = $table[0];
    writeToFile($file, "-- Structure de la table $table_name");
    

    $structure_result = mysqli_query($connexion, "SHOW CREATE TABLE $table_name");
    $structure = mysqli_fetch_assoc($structure_result);
    writeToFile($file, $structure['Create Table'] . ';');
    
    writeToFile($file, "-- Contenu de la table $table_name");

    $data_result = mysqli_query($connexion, "SELECT * FROM $table_name");
    while ($row = mysqli_fetch_assoc($data_result)) {

        $columns = implode(', ', array_map(function($col) {
            return "`$col`";
        }, array_keys($row)));
        $values = implode(', ', array_map(function($val) {
            return "'" . addslashes($val) . "'";
        }, $row));
        writeToFile($file, "INSERT INTO `$table_name` ($columns) VALUES ($values);");
    }
    writeToFile($file, ''); 
}


fclose($file);

echo "La sauvegarde a été créée avec succès dans $filename";
?>
