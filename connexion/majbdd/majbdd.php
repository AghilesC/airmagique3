<?php 
include_once "../logincheck.php";
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
    <link rel="stylesheet" href="majbdd.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <img src="../image/inventory1.png" alt="Logo" class="logo">

    
</head>
<body>

    <div class="menu-toggle" onclick="toggleMenu()">
    <div class="menu-icon" id="menu-icon">
    </div>
</div>
    <div id="menu-toggle" class="menu-toggle">
    <i class="fas fa-bars"></i>

    
</div>
<div id="menu" class="menu">
    <ul>
    <a href="majbdd.php"><li>Inventaire</li></a>
    <a href="destructionrma.php"><li>Destruction/RMA</li></a>
    <a href="journalevenement.php"><li>Journal d'évènement</li></a>
    <a href="../adminmenu/adminmenu.php"><li>Retour au menu</li></a>
    <a href="?logout"><li>Déconnexion</li></a>
    </ul>
</div>




        <select id="partner-select" class="partner-select" onchange="showPartnerDetails(this.value)">
            <option value="">Sélectionner un partenaire</option>
            <option value="all">Afficher tout le tableau</option>
            <?php include 'fetch_partner_names.php'; ?>
        </select><br><br>


        
    <table id="partner-table">
    <tr class="sticky-header">
            <th>Partner ID</th>
            <th>City</th>
            <th>Address</th>
            <th>POS</th>
            <th>Thermal printer</th>
            <th>EPC</th>
            <th>Scanner</th>
            <th>UPS</th>
            <th>Cash Drawer</th>
            <th>Site Controller</th>
            <th>Fuel Controller</th>
            <th>Hub 8 Port</th>
            <th>Pinpad Cable</th>
            <th>Scanner Cable</th>
            <th>Cash Drawer Cable</th>
            <th>Server Pro</th>
            <th>Server Std</th>
            <th>iPad</th>
            <th>PDU</th>
            <th>Cisco 1121</th>
            <th>Bracket Cisco 1121</th>
            <th>UPS1000</th>
            <th>Cisco 9200 24T</th>
            <th>Cisco 9200 48T</th>
            <th>Viptela</th>
            <th>Aruba</th>
            <th>Switch 48 Port</th>
            <th>Switch 24 Port</th>
            <th>BOPC HP</th>
            <th>BOPC Dell</th>
            <th>BOPC Pagnian</th>
            <th>DP to HDMI</th>
            <th>LCD Monitor</th>
            <th>Lexmark</th>
            <th>Display 19</th>
            <th>Display 7</th>
            <th>Lift CPU</th>
            <th>Lift Power Bar</th>
            <th>Dual USB 6F</th>
            <th>Dual USB 15F</th>
            <th>Adapter RJ45 Splitter</th>
            <th>RJ12 RJ45 Scanner</th>
            <th>RJ12 Coupler</th>
            <th>RJ45 Lift CPU</th>
            <th>RJ12 RJ45 Pole Display</th>
            <th>Radiant Scanner Cable</th>
            <th>DVI VGA</th>
            <th>Mount Pole 24I</th>
            <th>Mount Arm Pole</th>
            <th>Mount Flat Panel Pole</th>
            <th>Mount Grommet</th>
            <th>Mount Homeplate</th>
            <th>Scanner DB9 RJ45</th>
            <th>Virtual Journal DB9 RJ45</th>
            <th>POS DB9 RJ45</th>
            <th>Scanner DB9 DB25</th>

            
        </tr>
 
        <?php include 'fetch_partner_data.php'; ?>
    </table>
    <script>



        
        function updateData(element, field, partnerId) {
            let value = element.value; 
            let xhr = new XMLHttpRequest();
            xhr.open("POST", "update_partner_data.php", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onreadystatechange = function () {
                if (xhr.readyState === 4 && xhr.status === 200) {
                    console.log(xhr.responseText);
                }
            };
            xhr.send(`field=${field}&value=${value}&partner_id=${partnerId}`);
        }

        function showPartnerDetails(partnerId) {

    var rows = document.querySelectorAll("#partner-table tr:not(:first-child)");
    rows.forEach(function(row) {
        row.style.display = "none";
    });

    if (partnerId === 'all') {
        rows.forEach(function(row) {
            row.style.display = ""; 
        });
    } else {

        var selectedRow = document.querySelector("#partner-table tr[data-partner-id='" + partnerId + "']");
        if (selectedRow) {
            selectedRow.style.display = "";
        }
    }
}




        // Fonction pour colorier les cellules en fonction de leur valeur
    function colorCellsByValue() {
        var cells = document.querySelectorAll("#partner-table td input[type='number']");
        cells.forEach(function(cell) {
            var value = parseFloat(cell.value);
            if (value <= 0) {
                // Valeur nulle ou négative : coloration en rouge
                cell.parentNode.classList.add('negative-value');
                cell.parentNode.classList.remove('low-value');
                cell.parentNode.classList.remove('high-value');
            } else if (value === 1 || value === 2) {
                // Valeur égale à 1 ou 2 : coloration en jaune
                cell.parentNode.classList.add('low-value');
                cell.parentNode.classList.remove('high-value');
            } else if (value > 2) {
                // Valeur supérieure à 2 : coloration en vert clair
                cell.parentNode.classList.add('high-value');
                cell.parentNode.classList.remove('low-value');
            } else {
                // Autres valeurs : retrait des classes de couleur si présentes
                cell.parentNode.classList.remove('negative-value');
                cell.parentNode.classList.remove('low-value');
                cell.parentNode.classList.remove('high-value');
            }
        });
    }

    // Appel initial pour colorier les cellules lors du chargement de la page
    colorCellsByValue();






// Sélectionner tous les champs de nombre dans les cellules du tableau
var numberInputs = document.querySelectorAll("#partner-table td input[type='number']");

// Ajouter un écouteur d'événement à chaque champ de nombre
numberInputs.forEach(function(input) {
    input.addEventListener('input', function() {
        var value = parseFloat(input.value);
        var cell = input.parentNode; // Obtenir la cellule parente du champ de nombre

        // Retirer toutes les classes de couleur
        cell.classList.remove('negative-value', 'low-value', 'high-value');

        // Appliquer la classe de couleur en fonction de la valeur saisie
        if (value <= 0) {
            cell.classList.add('negative-value');
        } else if (value === 1 || value === 2) {
            cell.classList.add('low-value');
        } else if (value > 2) {
            cell.classList.add('high-value');
        }
    });
});


document.addEventListener('DOMContentLoaded', function() {
    var rows = document.querySelectorAll("#partner-table tr:not(:first-child)");

    // Parcourir chaque ligne pour ajouter un écouteur d'événement 'click'
    rows.forEach(function(row) {
        // Ajouter un écouteur d'événement 'click' à chaque ligne
        row.addEventListener('click', function(event) {
            // Vérifier si la cellule cliquée est dans une colonne cliquable (partner_id, city ou address)
            var clickableColumns = [0, 1, 2]; // Indices des colonnes partner_id, city et address (commençant à partir de 0)

            // Récupérer l'indice de la colonne de la cellule cliquée
            var columnIndex = event.target.cellIndex;

            // Vérifier si l'indice de la colonne fait partie des colonnes cliquables
            if (clickableColumns.includes(columnIndex)) {
                // Désélectionner toutes les lignes sauf la ligne actuellement sélectionnée
                rows.forEach(function(r) {
                    if (r !== row) {
                        r.classList.remove('selected');
                    }
                });

                // Basculer la classe 'selected' sur la ligne actuellement cliquée
                row.classList.toggle('selected');
            }
        });
    });
});







document.addEventListener('DOMContentLoaded', function() {
    var selectableColumns = document.querySelectorAll("#partner-table th:nth-child(n+4):nth-child(-n+59)");
    var selectedColumn = null; // Variable pour stocker la colonne actuellement sélectionnée

    // Gestionnaire d'événements 'click' pour chaque en-tête de colonne
    selectableColumns.forEach(function(column) {
        column.addEventListener('click', function() {
            // Supprimer la classe 'selected-column' de la colonne précédemment sélectionnée et de ses cellules correspondantes
            if (selectedColumn) {
                selectedColumn.classList.remove('selected-column');
                var columnIndex = Array.from(selectedColumn.parentNode.children).indexOf(selectedColumn);
                var rows = document.querySelectorAll("#partner-table tr");
                rows.forEach(function(row) {
                    var cell = row.children[columnIndex];
                    if (cell) {
                        cell.classList.remove('selected-column');
                    }
                });
            }

            // Si la colonne cliquée n'était pas déjà sélectionnée, la sélectionner
            if (selectedColumn !== column) {
                column.classList.add('selected-column');
                selectedColumn = column; // Mettre à jour la colonne actuellement sélectionnée
                var columnIndex = Array.from(column.parentNode.children).indexOf(column);
                var rows = document.querySelectorAll("#partner-table tr");
                rows.forEach(function(row) {
                    var cell = row.children[columnIndex];
                    if (cell) {
                        cell.classList.add('selected-column');
                    }
                });
            } else {
                selectedColumn = null; // Désélectionner la colonne si elle était déjà sélectionnée
            }
        });
    });
});



document.addEventListener('DOMContentLoaded', function() {
    var menuToggle = document.getElementById('menu-toggle');
    var menu = document.getElementById('menu');
    var menuOpen = false;

    // Fonction pour ouvrir ou fermer le menu
    function toggleMenu() {
        if (!menuOpen) {
            menu.style.display = 'block';
            menuOpen = true;
        } else {
            menu.style.display = 'none';
            menuOpen = false;
        }
    }

    // Ajouter un écouteur d'événement 'click' au bouton de bascule du menu
    menuToggle.addEventListener('click', function(event) {
        event.stopPropagation(); // Empêcher la propagation du clic pour ne pas déclencher le gestionnaire de clic du document
        toggleMenu();
    });

    // Ajouter un écouteur d'événement 'click' au document entier
    document.addEventListener('click', function(event) {
        // Vérifier si le menu est ouvert et si le clic n'est pas à l'intérieur du menu ou du bouton de bascule du menu
        if (menuOpen && !menu.contains(event.target) && event.target !== menuToggle) {
            menu.style.display = 'none'; // Fermer le menu
            menuOpen = false;
        }
    });
});





document.addEventListener('DOMContentLoaded', function() {
    var menu = document.querySelector('.menu');
    var menuToggle = document.querySelector('.menu-toggle');

    // Fonction pour gérer le scroll et rendre le menu sticky
    function handleScroll() {
        if (window.pageYOffset > 0) {
            menu.classList.add('sticky'); // Ajoute la classe 'sticky' lorsque l'utilisateur fait défiler vers le bas
        } else {
            menu.classList.remove('sticky'); // Supprime la classe 'sticky' lorsque l'utilisateur est en haut de la page
        }
    }

    // Ajoute un écouteur d'événement pour le scroll de la fenêtre
    window.addEventListener('scroll', handleScroll);

    // Ajoute un écouteur d'événement pour le clic sur le menu toggle
    menuToggle.addEventListener('click', function() {
        // Assurez-vous de retirer la classe 'sticky' lorsque le menu est ouvert
        menu.classList.remove('sticky');
    });
});


function toggleMenu() {
    var menuIcon = document.getElementById('menu-icon');
    menuIcon.classList.toggle('open'); // Ajoute ou supprime la classe 'open' sur le menu-icon
}


document.addEventListener('DOMContentLoaded', function() {
            // Afficher progressivement le contenu après un court délai
            setTimeout(function() {
                document.querySelector('body').style.opacity = '1'; // Faire apparaître le contenu en modifiant l'opacité
                document.querySelector('.content').style.display = 'block'; // Afficher le contenu
            }, 500); // Délai de 500 millisecondes (0.5 secondes)
        });




    </script>
</body>
</html>

