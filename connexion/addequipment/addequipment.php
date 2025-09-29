<?php
include "../logincheck.php";
include "../../config.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

// Vérifier si la valeur glpi_ticket_id est définie dans la session
if (isset($_SESSION['glpi_ticket_id'])) {
    // Récupérer la valeur de glpi_ticket_id depuis la session
    $glpi_ticket_id = $_SESSION['glpi_ticket_id'];
} else {
    // Si la valeur n'est pas définie dans la session, initialiser à une chaîne vide
    $glpi_ticket_id = "";
}

// Vérifiez si les valeurs de la liste multiple sont présentes dans la variable de session
if(isset($_SESSION['equipement_selected'])) {
    $equipement_selected = $_SESSION['equipement_selected'];
} else {
    $equipement_selected = array(); // Définissez une valeur par défaut au cas où aucune valeur n'est stockée
}

$userFullName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];
$depotID = $_SESSION['depot'];

$conn =  mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);


if ($conn->connect_error) {
    die("Échec de la connexion à la base de données : " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {


    $selectedEquipment = $_POST['equipement'];

    $ticketID = $_POST['ticket_id'];


    $partnerQuery = "SELECT * FROM partner WHERE partner_id = '$depotID'";
    $partnerResult = $conn->query($partnerQuery);

    if ($partnerResult->num_rows > 0) {

        $partnerRow = $partnerResult->fetch_assoc();
        $partnerAddress = $partnerRow['address'];

       
        $equipmentColumns = [
            "pos" => "pos",
            "thermal_printer" => "thermal_printer",
            "epc" => "epc",
            "Scanner" => "scanner",
            "ups" => "ups",
            "Cash drawer" => "cash_drawer",
            "site_controller" => "site_controller",
            "fuel_controller" => "fuel_controller",
            "hub_8_port" => "hub_8_port",
            "pinpad_cable" => "pinpad_cable",
            "scanner_cable" => "scanner_cable",
            "cash_drawer_cable" => "cash_drawer_cable",
            "server_pro" => "server_pro",
            "server_std" => "server_std",
            "server_ssd" => "server_ssd",
            "pdu" => "pdu",
            "cisco_1121" => "cisco_1121",
            "bracket_router_cisco_1121" => "bracket_router_cisco_1121",
            "UPS1000" => "UPS1000",
            "cisco_9200_24t" => "cisco_9200_24t",
            "cisco_9200_48t" => "cisco_9200_48t",
            "viptela" => "viptela",
            "aruba" => "aruba",
            "switch_48_port" => "switch_48_port",
            "switch_24_port" => "switch_24_port",
            "bopc_hp" => "bopc_hp",
            "bopc_dell" => "bopc_dell",
            "bopc_pagnian" => "bopc_pagnian",
            "dp_to_hdmi" => "dp_to_hdmi",
            "lcd_monitor" =>  "lcd_monitor",
            "lexmark" => "lexmark",
            "display_19" => "display_19",
            "display_7" => "display_7",
            "lift_cpu" => "lift_cpu",
            "lift_power_bar" => "lift_power_bar",
            "dual_usb_6f" => "dual_usb_6f",
            "dual_usb_15f" => "dual_usb_15f",
            "adapter_rj45_splitter" => "adapter_rj45_splitter",
            "rj12_rj45_scanner" => "rj12_rj45_scanner",
            "rj12_coupler" => "rj12_coupler",
            "rj45_lift_cpu" => "rj45_lift_cpu",
            "rj12_rj45_pole_display" => "rj12_rj45_pole_display",
            "radiant_scanner_cable" => "radiant_scanner_cable",
            "dvi_vga" => "dvi_vga",
            "mount_pole_24i" => "mount_pole_24i",
            "mount_arm_pole" => "mount_arm_pole",
            "mount_flat_panel_pole" => "mount_flat_panel_pole",
            "mount_grommet" => "mount_grommet",
            "mount_homeplate" => "mount_homeplate",
            "scanner_db9_rj45" => "scanner_db9_rj45",
            "virtual_journal_db9_rj45" => "virtual_journal_db9_rj45",
            "pos_db9_rj45" => "pos_db9_rj45",
            "scanner_db9_db25" => "scanner_db9_db25"


        ];

            // Vérifiez si des équipements sont sélectionnés
    if (isset($_POST['equipement']) && !empty($_POST['equipement'])) {
        // Parcourez chaque équipement sélectionné
        foreach ($_POST['equipement'] as $selectedEquipment) {
            // Effectuez la soustraction de 1 uniquement si l'équipement est valide et la quantité est supérieure à -999
            if (array_key_exists($selectedEquipment, $equipmentColumns) && $partnerRow[$equipmentColumns[$selectedEquipment]] >= -999) {
                $newQuantity = $partnerRow[$equipmentColumns[$selectedEquipment]] - 1;
                $updateQuery = "UPDATE partner SET " . $equipmentColumns[$selectedEquipment] . " = $newQuantity WHERE partner_id = $depotID";

                if ($conn->query($updateQuery) === TRUE) {
                    $currentDateTime = date("Y-m-d H:i:s");
                    $insertQuery = $conn->prepare("INSERT INTO history (partner_id, tech_name, ticket_id, equipment, date, address) VALUES (?, ?, ?, ?, ?, ?)");
                    $insertQuery->bind_param("isssss", $depotID, $userFullName, $ticketID, $selectedEquipment, $currentDateTime, $partnerAddress);

                    if ($insertQuery->execute()) {
                        // Continuez le traitement ou redirigez comme nécessaire
                    }
                }
            }
        }
        // Redirection vers confirmation.php après avoir traité tous les équipements sélectionnés
        header("Location: ../confirmation/confirmation.php");
        exit; // Assurez-vous de terminer l'exécution du script après la redirection
    }
}
            }
        
    







$conn->close();


// Supprimez la valeur de la session pour éviter de l'afficher à nouveau
unset($_SESSION['glpi_ticket_id']);
// Supprimez la valeur de la session pour éviter de l'afficher à nouveau
unset($_SESSION['equipement_selected']);

?>

<!DOCTYPE html>
<html lang="en">  <!-- By AGHILES CHAOUCHE 2023 ©-->
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory update - inv.ctiai.com</title>
    <link rel="stylesheet" href="addequipment.css">
</head>
<body>
    <img src="../image/ctiai_logo.png" alt="Logo" class="logo">
    <div class="form-container">
        <h1>Inventory Update / MAJ Inventaire</h1>
        <form id="my-form" action="" method="POST" onsubmit="return confirmSubmission()">
        <div class="form-group">
        <input type="text" id="ticket_id" name="ticket_id" value="<?php echo $glpi_ticket_id; ?>" required pattern="\d{10,}" title="The ticket ID must contain at least 10 digits minimum/Le ticket ID doit comporter au moins 10 chiffres minimum" placeholder="Enter Ticket ID">
<span class="error-message" style="color: red;"></span>
            </div>

           

            <div class="form-group">
                <label for="equipment">Equipment used / Equipement utilisé:</label>
                <select id="equipment" name="equipement[]" required multiple>
                    <optgroup label="POS">
                        <option value="pos" <?php if(in_array('pos', $equipement_selected)) echo 'selected'; ?>>POS</option>
                        <option value="thermal_printer" <?php if(in_array('thermal_printer', $equipement_selected)) echo 'selected'; ?>>Thermal printer</option>
                        <option value="epc" <?php if(in_array('epc', $equipement_selected)) echo 'selected'; ?>>EPC</option>
                        <option value="Scanner" <?php if(in_array('Scanner', $equipement_selected)) echo 'selected'; ?>>Scanner</option>
                        <option value="ups" <?php if(in_array('ups', $equipement_selected)) echo 'selected'; ?>>UPS</option>
                        <option value="Cash drawer" <?php if(in_array('Cash drawer', $equipement_selected)) echo 'selected'; ?>>Cash drawer</option>
                        <option value="site_controller" <?php if(in_array('site_controller', $equipement_selected)) echo 'selected'; ?>>Site Controller</option>
                        <option value="fuel_controller" <?php if(in_array('fuel_controller', $equipement_selected)) echo 'selected'; ?>>Fuel Controller</option>
                        <option value="hub_8_port" <?php if(in_array('hub_8_port', $equipement_selected)) echo 'selected'; ?>>Network Hub 8 Port</option>
                        <option value="pinpad_cable" <?php if(in_array('pinpad_cable', $equipement_selected)) echo 'selected'; ?>>Pinpad cable (Serial to RJ11)</option>
                        <option value="scanner_cable" <?php if(in_array('scanner_cable', $equipement_selected)) echo 'selected'; ?>>Scanner cable USB</option>
                        <option value="cash_drawer_cable" <?php if(in_array('cash_drawer_cable', $equipement_selected)) echo 'selected'; ?>>Cash drawer cable</option>
                        <option value="server_pro" <?php if(in_array('server_pro', $equipement_selected)) echo 'selected'; ?>>vStore Server PRO</option>
                        <option value="server_std" <?php if(in_array('server_std', $equipement_selected)) echo 'selected'; ?>>vStore Server STD</option>
                        <option value="server_ssd" <?php if(in_array('server_ssd', $equipement_selected)) echo 'selected'; ?>>vStore Server SSD</option>
                        <option value="pdu" <?php if(in_array('pdu', $equipement_selected)) echo 'selected'; ?>>PDU</option>
                        </optgroup>


                    <optgroup label="Network">
                        <option value="cisco_1121" <?php if(in_array('cisco_1121', $equipement_selected)) echo 'selected'; ?>>Cisco 1121</option>
                        <option value="bracket_router_cisco_1121" <?php if(in_array('bracket_router_cisco_1121', $equipement_selected)) echo 'selected'; ?>>Bracket router Cisco 1121</option>
                        <option value="UPS1000" <?php if(in_array('UPS1000', $equipement_selected)) echo 'selected'; ?>>UPS 1000</option>
                        <option value="cisco_9200_24t" <?php if(in_array('cisco_9200_24t', $equipement_selected)) echo 'selected'; ?>>Cisco 9200-24T</option>
                        <option value="cisco_9200_48t" <?php if(in_array('cisco_9200_48t', $equipement_selected)) echo 'selected'; ?>>Cisco 9200-48T</option>
                        <option value="viptela" <?php if(in_array('viptela', $equipement_selected)) echo 'selected'; ?>>Cisco Viptela</option>
                        <option value="aruba" <?php if(in_array('aruba', $equipement_selected)) echo 'selected'; ?>>Aruba</option>
                        <option value="switch_48_port" <?php if(in_array('switch_48_port', $equipement_selected)) echo 'selected'; ?>>Cisco 2960x switch 48 port</option>
                        <option value="switch_24_port" <?php if(in_array('switch_24_port', $equipement_selected)) echo 'selected'; ?>>Cisco 2960x switch 24 port</option>
                    </optgroup>

                    <optgroup label="BOPC">
                        <option value="bopc_hp" <?php if(in_array('bopc_hp', $equipement_selected)) echo 'selected'; ?>>BOPC HP</option>
                        <option value="bopc_dell" <?php if(in_array('bopc_dell', $equipement_selected)) echo 'selected'; ?>>BOPC DELL</option>
                        <option value="bopc_pagnian" <?php if(in_array('bopc_pagnian', $equipement_selected)) echo 'selected'; ?>>BOPC Pagnian</option>
                        <option value="dp_to_hdmi" <?php if(in_array('dp_to_hdmi', $equipement_selected)) echo 'selected'; ?>>DP to HDMI</option>
                        <option value="lcd_monitor" <?php if(in_array('lcd_monitor', $equipement_selected)) echo 'selected'; ?>>LCD Monitor</option>
                        <option value="lexmark" <?php if(in_array('lexmark', $equipement_selected)) echo 'selected'; ?>>Lexmark MX331</option>
                    </optgroup>

                    <optgroup label="Lift Core System Kit">
                        <option value="display_19" <?php if(in_array('display_19', $equipement_selected)) echo 'selected'; ?>>Display, 19-Inch</option>
                        <option value="display_7" <?php if(in_array('display_7', $equipement_selected)) echo 'selected'; ?>>Display, 7-Inch</option>
                        <option value="lift_cpu" <?php if(in_array('lift_cpu', $equipement_selected)) echo 'selected'; ?>>Lift CPU</option>
                        <option value="lift_power_bar" <?php if(in_array('lift_power_bar', $equipement_selected)) echo 'selected'; ?>>Lift Power bar</option>
                        <option value="dual_usb_6f" <?php if(in_array('dual_usb_6f', $equipement_selected)) echo 'selected'; ?>>Cable, 6-foot dual USB</option>
                        <option value="dual_usb_15f" <?php if(in_array('dual_usb_15f', $equipement_selected)) echo 'selected'; ?>>Cable, 15-foot dual USB</option>
                    </optgroup>

                    <optgroup label="Lift Radiant POS">
                        <option value="adapter_rj45_splitter" <?php if(in_array('adapter_rj45_splitter', $equipement_selected)) echo 'selected'; ?>>Adapter, RJ45 splitter for scanner</option>
                        <option value="rj12_rj45_scanner" <?php if(in_array('rj12_rj45_scanner', $equipement_selected)) echo 'selected'; ?>>Cable, 3-foot RJ12/RJ45 for scanner</option>
                        <option value="rj12_coupler" <?php if(in_array('rj12_coupler', $equipement_selected)) echo 'selected'; ?>>RJ12 coupler</option>
                        <option value="rj45_lift_cpu" <?php if(in_array('rj45_lift_cpu', $equipement_selected)) echo 'selected'; ?>>Cable, 6-foot RJ45 Adapter/Lift CPU</option>
                        <option value="rj12_rj45_pole_display" <?php if(in_array('rj12_rj45_pole_display', $equipement_selected)) echo 'selected'; ?>>Cable, 3-foot RJ12/RJ45 for pole display</option>
                        <option value="radiant_scanner_cable" <?php if(in_array('radiant_scanner_cable', $equipement_selected)) echo 'selected'; ?>>Radiant Serial Scanner cable</option>
                        <option value="dvi_vga" <?php if(in_array('dvi_vga', $equipement_selected)) echo 'selected'; ?>>DVI to VGA</option>
                    </optgroup>

                    <optgroup label="Lift Metal Hardware">
                        <option value="mount_pole_24i" <?php if(in_array('mount_pole_24i', $equipement_selected)) echo 'selected'; ?>>Mount, 24-inch pole</option>
                        <option value="mount_arm_pole" <?php if(in_array('mount_arm_pole', $equipement_selected)) echo 'selected'; ?>>Mount, flat panel articulating arm pole clamp</option>
                        <option value="mount_flat_panel_pole" <?php if(in_array('mount_flat_panel_pole', $equipement_selected)) echo 'selected'; ?>>Mount, flat-panel pole clamps</option>
                        <option value="mount_grommet" <?php if(in_array('mount_grommet', $equipement_selected)) echo 'selected'; ?>>Mount, Grommet base</option>
                        <option value="mount_homeplate" <?php if(in_array('mount_homeplate', $equipement_selected)) echo 'selected'; ?>>Mount, Homeplate base</option>
                    </optgroup>

                    <optgroup label="Bulloch Cable Kit">
                        <option value="scanner_db9_rj45" <?php if(in_array('scanner_db9_rj45', $equipement_selected)) echo 'selected'; ?>>Scanner DB9 to RJ45 Adapter</option>
                        <option value="virtual_journal_db9_rj45" <?php if(in_array('virtual_journal_db9_rj45', $equipement_selected)) echo 'selected'; ?>>Virtual Journal DB9 to RJ45 Adapter</option>
                        <option value="pos_db9_rj45" <?php if(in_array('pos_db9_rj45', $equipement_selected)) echo 'selected'; ?>>POS DB9 to RJ45 Adapter</option>
                        <option value="scanner_db9_db25" <?php if(in_array('scanner_db9_db25', $equipement_selected)) echo 'selected'; ?>>Scanner DB9 to DB25r</option>
                    </optgroup>


                </select>
                <span class="error-message" style="color: red;"></span>
            </div>



            <button type="submit" id="submitBtn">Submit / Soumettre</button>




    </div>

    <div class="form-group">
                <input type="text" id="tech_name" name="tech_name" value="<?php echo $userFullName; ?>" readonly onfocus="this.blur()" placeholder="Enter first name + last name">
                <span class="error-message" style="color: red;"></span>
            </div>

            <input type="hidden" id="depot" name="depot" value="<?php echo $depotID; ?>">

            <div class="form-group">
                <select id="partner_id" name="partner"  required>
<option value="">Select partner / Sélectionnez un partenaire</option>



                <optgroup label="ALBERTA">
                    <option value="7">Calgary</option>
                    <option value="9">Edmonton</option>
                    <option value="10">Grande Prairie</option>
                    <option value="8">Lethbridge</option>
                    <option value="11">Medicine Hat</option>
                    <option value="12">Red Deer</option>
                </optgroup>


                <optgroup label="ATLANTIC">
                    <option value="43">Antigonish</option>
                    <option value="45">Corner brook</option>
                    <option value="46">Charlottetown</option>
                    <option value="40">Fredericton</option>
                    <option value="47">Grand Falls Windsor</option>
                    <option value="42">Halifax</option>
                    <option value="39">Moncton</option>
                    <option value="41">Saint john</option>
                    <option value="44">Saint John's</option>
                    <option value="38">Tracadie</option>
                    <option value="48">Yarmouth</option>
                </optgroup>


                <optgroup label="BRITISH COLUMBIA">
                    <option value="5">Kamloops</option>
                    <option value="1">Kelowna</option>
                    <option value="4">Nanaimo</option>
                    <option value="3">POCO</option>
                    <option value="2">Prince George</option>
                    <option value="6">Victoria</option>
                </optgroup>


                <optgroup label="MANITOBA">
                    <option value="17">Winniepeg</option>
                </optgroup>



                <optgroup label="ONTARIO">
                    <option value="24">Hamilton</option>
                    <option value="25">Kitchener</option>
                    <option value="27">Mississauga</option>
                    <option value="18">North Bay</option>
                    <option value="26">Ottawa</option>
                    <option value="19">Sault Ste Marie</option>
                    <option value="20">Sudbury</option>
                    <option value="21">Timmins</option>
                    <option value="22">Thunder Bay</option>
                    <option value="23">Windsor</option>
                </optgroup>


                <optgroup label="QUEBEC">
                    <option value="37">Baie comeau</option>
                    <option value="32">Chicoutimi</option>
                    <option value="35">Dolbeau-Mistassini</option>
                    <option value="33">Gaspé</option>
                    <option value="28">Gatineau</option>
                    <option value="36">Malbaie</option>
                    <option value="30">Rivière-du-loup</option>
                    <option value="34">Sept-Îles</option>
                    <option value="31">Sherbrooke</option>
                    <option value="29">Trois-rivières</option>
                </optgroup>

                <optgroup label="SASKATCHEWAN">
                    <option value="13">Prince Albert</option>
                    <option value="14">Regina</option>
                    <option value="15">Saskatoon</option>
                    <option value="16">Yorkton</option>
                </optgroup>


                <optgroup label="MAIN DEPOT">
                    <option value="50">Montréal</option>
                    <option value="49">Québec</option>
                    <option value="999">TEST</option>
                </optgroup>

                <optgroup label="NO DEPOT">
                    <option value="800">NO DEPOT</option>
                </optgroup>

                </select>
                <span class="error-message" style="color: red;"></span>
            </div>

            </form>
            <a href='../techmenu/techmenu.php'; class="button2">Menu</button>
    <script>
        var formSubmitted = false;

        function confirmSubmission() {
            if (formSubmitted) {

                return false;
            }


            var myForm = document.getElementById("my-form");
            if (!myForm.checkValidity()) {

                var errorMessages = document.getElementsByClassName("error-message");
                for (var i = 0; i < errorMessages.length; i++) {
                    var input = errorMessages[i].previousElementSibling;
                    if (!input.checkValidity()) {
                        errorMessages[i].textContent = input.validationMessage;
                    } else {
                        errorMessages[i].textContent = "";
                    }
                }
                return false;
            }


            formSubmitted = true;

            var submitButton = document.getElementById("submitBtn");
            submitButton.innerHTML = "Submitting...";


            setTimeout(function() {
                submitButton.disabled = true;
            }, 100);


            return true;
        }

        document.addEventListener("DOMContentLoaded", function() {
        var depotValue = "<?php echo $depotID; ?>";
        var selectElement = document.getElementById("partner_id");
        var options = selectElement.options;
        for (var i = 0; i < options.length; i++) {
            if (options[i].value === depotValue) {
                selectElement.selectedIndex = i;
                selectElement.disabled = false; // Désactiver la liste déroulante
                break;
            }
        }
    });


        
    </script>

</body>
</html>
