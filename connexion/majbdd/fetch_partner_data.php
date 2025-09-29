<?php
include "../../config.php";
$connexion = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);

// Récupérer tous les partenaires sauf TOTAL CANADA (partner_id = 999)
$query = "SELECT * FROM `partner` WHERE partner_id != 999";
$result = mysqli_query($connexion, $query);

$totals = []; // tableau pour stocker les totaux par colonne

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr data-partner-id='{$row['partner_id']}'>";
        echo "<td>{$row['partner_id']}</td>";
        echo "<td>{$row['city']}</td>";
        echo "<td>{$row['address']}</td>";

        // Liste des champs numériques à afficher (comme dans ton code)
        $fields = [
            'pos', 'thermal_printer', 'epc', 'scanner', 'ups', 'cash_drawer', 'site_controller',
            'fuel_controller', 'hub_8_port', 'pinpad_cable', 'scanner_cable', 'cash_drawer_cable',
            'server_pro', 'server_std', 'ipad', 'pdu', 'cisco_1121', 'bracket_router_cisco_1121',
            'UPS1000', 'cisco_9200_24t', 'cisco_9200_48t', 'viptela', 'aruba', 'switch_48_port',
            'switch_24_port', 'bopc_hp', 'bopc_dell', 'bopc_pagnian', 'dp_to_hdmi', 'lcd_monitor',
            'lexmark', 'display_19', 'display_7', 'lift_cpu', 'lift_power_bar', 'dual_usb_6f',
            'dual_usb_15f', 'adapter_rj45_splitter', 'rj12_rj45_scanner', 'rj12_coupler',
            'rj45_lift_cpu', 'rj12_rj45_pole_display', 'radiant_scanner_cable', 'dvi_vga',
            'mount_pole_24i', 'mount_arm_pole', 'mount_flat_panel_pole', 'mount_grommet',
            'mount_homeplate', 'scanner_db9_rj45', 'virtual_journal_db9_rj45', 'pos_db9_rj45',
            'scanner_db9_db25'
        ];

        foreach ($fields as $field) {
            $value = (int)$row[$field];
            echo "<td><input type='number' value='$value' onchange='updateData(this, \"$field\", {$row['partner_id']})'></td>";

            // Calcul des totaux
            if (!isset($totals[$field])) {
                $totals[$field] = 0;
            }
            $totals[$field] += $value;
        }
        echo "</tr>";
    }

    // Afficher la ligne TOTAL CANADA en lecture seule (sans input)
    echo "<tr data-partner-id='999' style='background-color:#f0f0f0; font-weight:bold;'>";
    echo "<td>999</td>";
    echo "<td>TOTAL CANADA</td>";
    echo "<td></td>"; // adresse vide ou autre info

    foreach ($fields as $field) {
        $totalValue = $totals[$field] ?? 0;
        echo "<td>$totalValue</td>";
    }
    echo "</tr>";
}
mysqli_close($connexion);
?>
