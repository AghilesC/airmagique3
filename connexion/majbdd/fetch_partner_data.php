<?php
include "../../config.php";
$connexion = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);

$query = "SELECT * FROM `partner`";
$result = mysqli_query($connexion, $query);

if (mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        echo "<tr data-partner-id='{$row['partner_id']}'>";
        echo "<td>{$row['partner_id']}</td>";
        echo "<td>{$row['city']}</td>";
        echo "<td>{$row['address']}</td>";
        echo "<td><input type='number' value='{$row['pos']}' onchange='updateData(this, \"pos\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['thermal_printer']}' onchange='updateData(this, \"thermal_printer\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['epc']}' onchange='updateData(this, \"epc\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['scanner']}' onchange='updateData(this, \"scanner\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['ups']}' onchange='updateData(this, \"ups\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['cash_drawer']}' onchange='updateData(this, \"cash_drawer\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['site_controller']}' onchange='updateData(this, \"site_controller\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['fuel_controller']}' onchange='updateData(this, \"fuel_controller\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['hub_8_port']}' onchange='updateData(this, \"hub_8_port\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['pinpad_cable']}' onchange='updateData(this, \"pinpad_cable\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['scanner_cable']}' onchange='updateData(this, \"scanner_cable\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['cash_drawer_cable']}' onchange='updateData(this, \"cash_drawer_cable\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['server_pro']}' onchange='updateData(this, \"server_pro\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['server_std']}' onchange='updateData(this, \"server_std\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['ipad_9']}' onchange='updateData(this, \"ipad_9\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['ipad_8']}' onchange='updateData(this, \"ipad_8\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['ipad_7']}' onchange='updateData(this, \"ipad_7\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['ipad_charger']}' onchange='updateData(this, \"ipad_charger\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['pdu']}' onchange='updateData(this, \"pdu\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['cisco_1121']}' onchange='updateData(this, \"cisco_1121\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['bracket_router_cisco_1121']}' onchange='updateData(this, \"bracket_router_cisco_1121\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['UPS1000']}' onchange='updateData(this, \"UPS1000\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['cisco_9200_24t']}' onchange='updateData(this, \"cisco_9200_24t\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['cisco_9200_48t']}' onchange='updateData(this, \"cisco_9200_48t\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['viptela']}' onchange='updateData(this, \"viptela\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['aruba']}' onchange='updateData(this, \"aruba\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['juniper']}' onchange='updateData(this, \"juniper\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['switch_48_port']}' onchange='updateData(this, \"switch_48_port\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['switch_24_port']}' onchange='updateData(this, \"switch_24_port\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['bopc_hp_8g']}' onchange='updateData(this, \"bopc_hp_8g\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['bopc_hp_9g']}' onchange='updateData(this, \"bopc_hp_9g\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['bopc_dell']}' onchange='updateData(this, \"bopc_dell\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['bopc_pagnian']}' onchange='updateData(this, \"bopc_pagnian\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['dp_to_hdmi']}' onchange='updateData(this, \"dp_to_hdmi\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['lcd_monitor']}' onchange='updateData(this, \"lcd_monitor\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['lexmark']}' onchange='updateData(this, \"lexmark\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['display_19']}' onchange='updateData(this, \"display_19\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['display_7']}' onchange='updateData(this, \"display_7\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['lift_cpu']}' onchange='updateData(this, \"lift_cpu\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['lift_power_bar']}' onchange='updateData(this, \"lift_power_bar\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['dual_usb_6f']}' onchange='updateData(this, \"dual_usb_6f\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['dual_usb_15f']}' onchange='updateData(this, \"dual_usb_15f\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['adapter_rj45_splitter']}' onchange='updateData(this, \"adapter_rj45_splitter\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['rj12_rj45_scanner']}' onchange='updateData(this, \"rj12_rj45_scanner\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['rj12_coupler']}' onchange='updateData(this, \"rj12_coupler\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['rj45_lift_cpu']}' onchange='updateData(this, \"rj45_lift_cpu\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['rj12_rj45_pole_display']}' onchange='updateData(this, \"rj12_rj45_pole_display\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['radiant_scanner_cable']}' onchange='updateData(this, \"radiant_scanner_cable\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['dvi_vga']}' onchange='updateData(this, \"dvi_vga\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['mount_pole_24i']}' onchange='updateData(this, \"mount_pole_24i\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['mount_arm_pole']}' onchange='updateData(this, \"mount_arm_pole\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['mount_flat_panel_pole']}' onchange='updateData(this, \"mount_flat_panel_pole\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['mount_grommet']}' onchange='updateData(this, \"mount_grommet\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['mount_homeplate']}' onchange='updateData(this, \"mount_homeplate\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['scanner_db9_rj45']}' onchange='updateData(this, \"scanner_db9_rj45\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['virtual_journal_db9_rj45']}' onchange='updateData(this, \"virtual_journal_db9_rj45\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['pos_db9_rj45']}' onchange='updateData(this, \"pos_db9_rj45\", {$row['partner_id']})'></td>";
        echo "<td><input type='number' value='{$row['scanner_db9_db25']}' onchange='updateData(this, \"scanner_db9_db25\", {$row['partner_id']})'></td>";




        echo "</tr>";
    }
}
mysqli_close($connexion);
?>
