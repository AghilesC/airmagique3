if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    $partnerId = $_POST['partner'];
    $selectedEquipment = $_POST['equipement'];
    $ticketID = $_POST['glpi_ticket_id'];



    $partnerQuery = "SELECT * FROM partner WHERE partner_id = '$partnerId'";
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
            "Router" => "router",
            "aruba" => "aruba",
            "switch_48_port" => "switch_48_port",
            "switch_24_port" => "switch_24_port",
            "bopc_hp" => "bopc_hp",
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

        if (array_key_exists($selectedEquipment, $equipmentColumns) && $partnerRow[$equipmentColumns[$selectedEquipment]] >= -999) {
            $newQuantity = $partnerRow[$equipmentColumns[$selectedEquipment]] - 1;
            $updateQuery = "UPDATE partner SET " . $equipmentColumns[$selectedEquipment] . " = $newQuantity WHERE partner_id = $partnerId";

        
            if ($conn->query($updateQuery) === TRUE) {

                $currentDateTime = date("Y-m-d H:i:s");

                $insertQuery = $conn->prepare("INSERT INTO history (partner_id, tech_name, ticket_id, equipment, date, address) VALUES (?, ?, ?, ?, ?, ?)");
                $insertQuery->bind_param("isssss", $partnerId, $userFullName, $ticketID, $selectedEquipment, $currentDateTime, $partnerAddress);

                if ($insertQuery->execute()) {
                    header('Location: ../confirmation/confirmation.php');
                    exit;
                }
            }
        }
    }
}