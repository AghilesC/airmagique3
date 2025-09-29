<?php
// Désactiver l'affichage des erreurs pour les requêtes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    ini_set('display_errors', 0);
    error_reporting(0);
}

include_once "../permissions.php";
include_once "../logincheck.php";

// Vérifier si l'utilisateur est connecté et a la permission d'accéder au calendrier
if (!isset($_SESSION['user_id']) || !checkAdminPermission($_SESSION['user_id'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Accès non autorisé']);
        exit();
    }
    header("Location: ../index.php");
    exit();
}

$userId = $_SESSION['user_id'];
$userFullName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

// Connexion à la base de données
include "../../config.php";

try {
    $connexion = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);
    
    if (!$connexion) {
        throw new Exception("Erreur de connexion : " . mysqli_connect_error());
    }
    
    // Définir le charset
    mysqli_set_charset($connexion, "utf8");
    
} catch (Exception $e) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Erreur de connexion à la base de données']);
        exit();
    }
    die("Erreur de connexion : " . $e->getMessage());
}

// Vérifier que les tables existent
$checkAccount = mysqli_query($connexion, "SHOW TABLES LIKE 'account'");
$checkInterventions = mysqli_query($connexion, "SHOW TABLES LIKE 'interventions'");

if (mysqli_num_rows($checkAccount) == 0 || mysqli_num_rows($checkInterventions) == 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Les tables nécessaires n\'existent pas']);
        exit();
    }
    die("Erreur: Les tables du calendrier n'existent pas. Veuillez exécuter les scripts SQL de création d'abord.");
}

// Gestion des requêtes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_POST['action']) {
            case 'get_events':
                $query = "SELECT i.*, 
                                CONCAT(a.first_name, ' ', a.last_name) as technician_name,
                                a.idacount as technician_id_ref,
                                a.depot as technician_depot,
                                a.mail as technician_email,
                                a.phone_number as technician_phone
                         FROM interventions i 
                         LEFT JOIN account a ON i.technician_id = a.idacount 
                         WHERE i.start_datetime >= ? AND i.start_datetime <= ?";
                
                $stmt = mysqli_prepare($connexion, $query);
                if (!$stmt) {
                    throw new Exception("Erreur de préparation de la requête: " . mysqli_error($connexion));
                }
                
                $start = $_POST['start'] ?? date('Y-m-01');
                $end = $_POST['end'] ?? date('Y-m-t');
                mysqli_stmt_bind_param($stmt, "ss", $start, $end);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                
                $events = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $statusColors = [
                        'scheduled' => '#3788d8',
                        'in_progress' => '#ffc107',
                        'completed' => '#28a745',
                        'cancelled' => '#dc3545'
                    ];
                    
                    // Couleurs basées sur la priorité si pas de technicien assigné
                    $priorityColors = [
                        'low' => '#6c757d',
                        'medium' => '#17a2b8',
                        'high' => '#fd7e14',
                        'urgent' => '#dc3545'
                    ];
                    
                    $backgroundColor = $priorityColors[$row['priority']] ?? $statusColors[$row['status']];
                    
                    $events[] = [
                        'id' => $row['id'],
                        'title' => $row['title'],
                        'start' => $row['start_datetime'],
                        'end' => $row['end_datetime'],
                        'backgroundColor' => $backgroundColor,
                        'borderColor' => $backgroundColor,
                        'extendedProps' => [
                            'description' => $row['description'],
                            'client_name' => $row['client_name'],
                            'client_address' => $row['client_address'],
                            'client_phone' => $row['client_phone'],
                            'technician_id' => $row['technician_id'],
                            'technician_name' => $row['technician_name'],
                            'technician_depot' => $row['technician_depot'],
                            'technician_email' => $row['technician_email'],
                            'technician_phone' => $row['technician_phone'],
                            'status' => $row['status'],
                            'priority' => $row['priority'],
                            'notes' => $row['notes']
                        ]
                    ];
                }
                echo json_encode($events);
                break;
                
            case 'get_technicians':
                // VERSION DEBUG : Voir tous les comptes avec leurs valeurs de validation
                $query = "SELECT idacount as id, 
                                CONCAT(first_name, ' ', last_name) as name,
                                first_name,
                                last_name,
                                mail as email,
                                phone_number,
                                depot,
                                validation
                         FROM account 
                         ORDER BY first_name, last_name";
                
                $result = mysqli_query($connexion, $query);
                if (!$result) {
                    throw new Exception("Erreur de requête: " . mysqli_error($connexion));
                }
                
                $technicians = [];
                while ($row = mysqli_fetch_assoc($result)) {
                    $technicians[] = [
                        'id' => $row['id'],
                        'name' => $row['name'],
                        'first_name' => $row['first_name'],
                        'last_name' => $row['last_name'],
                        'email' => $row['email'],
                        'phone_number' => $row['phone_number'],
                        'depot' => $row['depot'],
                        'validation' => $row['validation']
                    ];
                }
                
                // Debug: log du nombre de techniciens trouvés
                error_log("Nombre de techniciens trouvés: " . count($technicians));
                
                echo json_encode($technicians);
                break;
                
            case 'save_intervention':
                $title = mysqli_real_escape_string($connexion, $_POST['title'] ?? '');
                $description = mysqli_real_escape_string($connexion, $_POST['description'] ?? '');
                $client_name = mysqli_real_escape_string($connexion, $_POST['client_name'] ?? '');
                $client_address = mysqli_real_escape_string($connexion, $_POST['client_address'] ?? '');
                $client_phone = mysqli_real_escape_string($connexion, $_POST['client_phone'] ?? '');
                $technician_id = !empty($_POST['technician_id']) ? intval($_POST['technician_id']) : null;
                $start_datetime = $_POST['start_datetime'] ?? '';
                $end_datetime = $_POST['end_datetime'] ?? '';
                $status = $_POST['status'] ?? 'scheduled';
                $priority = $_POST['priority'] ?? 'medium';
                $notes = mysqli_real_escape_string($connexion, $_POST['notes'] ?? '');
                
                // Validation des champs requis
                if (empty($title) || empty($start_datetime) || empty($end_datetime)) {
                    throw new Exception("Les champs titre, date de début et date de fin sont requis");
                }
                
                // Validation du technician_id s'il est fourni - doit exister dans account.idacount
                if (!empty($technician_id)) {
                    $checkTechnician = mysqli_prepare($connexion, "SELECT idacount FROM account WHERE idacount = ?");
                    if (!$checkTechnician) {
                        throw new Exception("Erreur de préparation de la requête de validation");
                    }
                    mysqli_stmt_bind_param($checkTechnician, "i", $technician_id);
                    mysqli_stmt_execute($checkTechnician);
                    $techResult = mysqli_stmt_get_result($checkTechnician);
                    
                    if (mysqli_num_rows($techResult) == 0) {
                        throw new Exception("Technicien non trouvé dans la table account");
                    }
                }
                
                if (isset($_POST['intervention_id']) && !empty($_POST['intervention_id'])) {
                    // Mise à jour - technician_id fait référence à account.idacount
                    $query = "UPDATE interventions SET 
                                title=?, description=?, client_name=?, client_address=?, 
                                client_phone=?, technician_id=?, start_datetime=?, end_datetime=?, 
                                status=?, priority=?, notes=? 
                              WHERE id=?";
                    $stmt = mysqli_prepare($connexion, $query);
                    if (!$stmt) {
                        throw new Exception("Erreur de préparation de la requête de mise à jour");
                    }
                    mysqli_stmt_bind_param($stmt, "sssssisssssi", 
                        $title, $description, $client_name, $client_address, 
                        $client_phone, $technician_id, $start_datetime, $end_datetime, 
                        $status, $priority, $notes, $_POST['intervention_id']);
                } else {
                    // Nouvelle intervention - technician_id fait référence à account.idacount
                    $query = "INSERT INTO interventions 
                             (title, description, client_name, client_address, client_phone, 
                              technician_id, start_datetime, end_datetime, status, priority, notes, created_by) 
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $stmt = mysqli_prepare($connexion, $query);
                    if (!$stmt) {
                        throw new Exception("Erreur de préparation de la requête d'insertion");
                    }
                    mysqli_stmt_bind_param($stmt, "sssssisssssi", 
                        $title, $description, $client_name, $client_address, $client_phone, 
                        $technician_id, $start_datetime, $end_datetime, $status, $priority, $notes, $userId);
                }
                
                if (mysqli_stmt_execute($stmt)) {
                    echo json_encode(['success' => true]);
                } else {
                    throw new Exception("Erreur lors de l'exécution de la requête: " . mysqli_error($connexion));
                }
                break;
                
            case 'delete_intervention':
                if (empty($_POST['intervention_id'])) {
                    throw new Exception("ID d'intervention manquant");
                }
                
                $intervention_id = intval($_POST['intervention_id']);
                $query = "DELETE FROM interventions WHERE id = ?";
                $stmt = mysqli_prepare($connexion, $query);
                if (!$stmt) {
                    throw new Exception("Erreur de préparation de la requête de suppression");
                }
                mysqli_stmt_bind_param($stmt, "i", $intervention_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    echo json_encode(['success' => true]);
                } else {
                    throw new Exception("Erreur lors de la suppression: " . mysqli_error($connexion));
                }
                break;
                
            case 'update_event_time':
                if (empty($_POST['intervention_id']) || empty($_POST['start_datetime']) || empty($_POST['end_datetime'])) {
                    throw new Exception("Paramètres manquants pour la mise à jour");
                }
                
                $intervention_id = intval($_POST['intervention_id']);
                $start_datetime = $_POST['start_datetime'];
                $end_datetime = $_POST['end_datetime'];
                
                $query = "UPDATE interventions SET start_datetime=?, end_datetime=? WHERE id=?";
                $stmt = mysqli_prepare($connexion, $query);
                if (!$stmt) {
                    throw new Exception("Erreur de préparation de la requête de mise à jour d'horaire");
                }
                mysqli_stmt_bind_param($stmt, "ssi", $start_datetime, $end_datetime, $intervention_id);
                
                if (mysqli_stmt_execute($stmt)) {
                    echo json_encode(['success' => true]);
                } else {
                    throw new Exception("Erreur lors de la mise à jour: " . mysqli_error($connexion));
                }
                break;
                
            default:
                throw new Exception("Action non reconnue");
        }
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    
    mysqli_close($connexion);
    exit();
}

mysqli_close($connexion);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar Scheduler - inv.ctiai.com</title>
    
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- FullCalendar CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.8/index.global.min.css" rel="stylesheet">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f8f9fa;
            color: #333333;
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 1400px;
            width: 100%;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
        }

        .logo {
            max-width: 200px;
            max-height: 120px;
            margin-bottom: 10px;
            border-radius: 8px;
        }

        .page-title {
            color: #2c3e50;
            font-size: 32px;
            font-weight: 600;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .page-subtitle {
            color: #6c757d;
            font-size: 16px;
            margin-bottom: 15px;
        }

        .admin-badge {
            display: inline-block;
            background: linear-gradient(135deg, #e82226, #c91e21);
            color: white;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(232, 34, 38, 0.3);
        }

        .toolbar {
            background: #ffffff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .toolbar-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            transition: all 0.3s ease;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-primary {
            background: #e82226;
            color: white;
        }

        .btn-primary:hover {
            background: #c91e21;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(232, 34, 38, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
        }

        .btn-outline {
            background: transparent;
            color: #e82226;
            border: 2px solid #e82226;
        }

        .btn-outline:hover {
            background: #e82226;
            color: white;
        }

        .calendar-container {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            margin-bottom: 20px;
        }

        #calendar {
            max-width: 100%;
        }

        /* Styles pour les icônes de priorité */
        .priority-icon {
            position: absolute;
            top: 2px;
            right: 2px;
            font-size: 10px;
            z-index: 10;
            color: white;
            text-shadow: 0 1px 2px rgba(0,0,0,0.5);
        }

        .priority-low .priority-icon {
            color: #28a745;
        }

        .priority-medium .priority-icon {
            color: #ffc107;
        }

        .priority-high .priority-icon {
            color: #fd7e14;
        }

        .priority-urgent .priority-icon {
            color: #dc3545;
            animation: pulse 1.5s infinite;
        }

        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }

        /* Styles pour FullCalendar */
        .fc-theme-standard .fc-scrollgrid {
            border: 1px solid #e9ecef;
        }

        .fc-theme-standard td, .fc-theme-standard th {
            border-color: #e9ecef;
        }

        .fc-button-primary {
            background-color: #e82226 !important;
            border-color: #e82226 !important;
            font-weight: 600 !important;
            font-size: 13px !important;
            padding: 8px 16px !important;
            border-radius: 8px !important;
            transition: all 0.3s ease !important;
        }

        .fc-button-primary:hover {
            background-color: #c91e21 !important;
            border-color: #c91e21 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 12px rgba(232, 34, 38, 0.3) !important;
        }

        .fc-button-primary:disabled {
            background-color: #e9ecef !important;
            border-color: #e9ecef !important;
            transform: none !important;
            box-shadow: none !important;
        }

        .fc-button-primary:focus {
            box-shadow: 0 0 0 3px rgba(232, 34, 38, 0.2) !important;
        }

        /* Style spécial pour les boutons de vue (Month, Week, Day) */
        .fc-button-group .fc-button {
            margin: 0 2px !important;
            border-radius: 8px !important;
            font-weight: 600 !important;
            letter-spacing: 0.5px !important;
            text-transform: uppercase !important;
            font-size: 12px !important;
            padding: 10px 18px !important;
        }

        .fc-button-group {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1) !important;
            border-radius: 10px !important;
            overflow: hidden !important;
            background: white !important;
            padding: 4px !important;
        }

        /* Styles pour les boutons de navigation */
        .fc-prev-button, .fc-next-button, .fc-today-button {
            border-radius: 8px !important;
            font-weight: 600 !important;
            padding: 10px 16px !important;
            margin: 0 3px !important;
        }

        .fc-today-button {
            background-color: #17a2b8 !important;
            border-color: #17a2b8 !important;
        }

        .fc-today-button:hover {
            background-color: #138496 !important;
            border-color: #138496 !important;
        }

        .fc-event {
            border-radius: 6px;
            font-weight: 500;
            font-size: 12px;
            position: relative;
        }

        /* Responsive design pour les boutons du calendrier */
        @media (max-width: 768px) {
            .fc-header-toolbar {
                flex-direction: column !important;
                gap: 15px !important;
            }

            .fc-toolbar-chunk {
                display: flex !important;
                justify-content: center !important;
                align-items: center !important;
            }

            .fc-button-group .fc-button {
                padding: 12px 20px !important;
                font-size: 14px !important;
                min-width: 80px !important;
            }

            .fc-prev-button, .fc-next-button, .fc-today-button {
                padding: 12px 18px !important;
                font-size: 14px !important;
            }

            .fc-toolbar-title {
                font-size: 20px !important;
                margin: 10px 0 !important;
                text-align: center !important;
            }
        }

        @media (max-width: 480px) {
            .fc-button-group .fc-button {
                padding: 10px 12px !important;
                font-size: 11px !important;
                min-width: 60px !important;
            }

            .fc-prev-button, .fc-next-button, .fc-today-button {
                padding: 10px 12px !important;
                font-size: 12px !important;
            }

            .fc-toolbar-title {
                font-size: 18px !important;
            }

            .fc-button-group {
                padding: 2px !important;
            }
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s ease;
        }

        .modal.show {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background-color: #ffffff;
            margin: 5% auto;
            padding: 0;
            border-radius: 12px;
            width: 90%;
            max-width: 600px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            padding: 20px 25px;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: #f8f9fa;
            border-radius: 12px 12px 0 0;
        }

        .modal-title {
            color: #2c3e50;
            font-size: 20px;
            font-weight: 600;
            margin: 0;
        }

        .close {
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            color: #6c757d;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: all 0.3s ease;
        }

        .close:hover {
            background: #e9ecef;
            color: #dc3545;
        }

        .modal-body {
            padding: 25px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            color: #2c3e50;
            font-size: 14px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
            background: #ffffff;
        }

        .form-control:focus {
            outline: none;
            border-color: #e82226;
            box-shadow: 0 0 0 3px rgba(232, 34, 38, 0.1);
        }

        .form-control.is-invalid {
            border-color: #dc3545;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        select.form-control {
            cursor: pointer;
        }

        textarea.form-control {
            resize: vertical;
            min-height: 80px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-scheduled { background: #cce5ff; color: #0066cc; }
        .status-in_progress { background: #fff3cd; color: #856404; }
        .status-completed { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }

        .priority-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .priority-low { background: #d4edda; color: #155724; }
        .priority-medium { background: #fff3cd; color: #856404; }
        .priority-high { background: #ffeeba; color: #996600; }
        .priority-urgent { background: #f8d7da; color: #721c24; }

        .modal-footer {
            padding: 20px 25px;
            border-top: 1px solid #e9ecef;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            background: #f8f9fa;
            border-radius: 0 0 12px 12px;
        }

        .info-section {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }

        .info-section h4 {
            color: #2c3e50;
            font-size: 16px;
            margin-bottom: 10px;
            font-weight: 600;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
            font-size: 14px;
        }

        .info-label {
            font-weight: 600;
            color: #6c757d;
        }

        .info-value {
            color: #2c3e50;
        }

        .back-button {
            text-decoration: none;
            color: #eb2226;
            font-size: 24px;
            font-weight: bold;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            margin-bottom: 30px;
        }

        .back-button:hover {
            color: #d11e21;
            transform: translateX(-3px);
        }

        .back-button::before {
            content: '←';
            margin-right: 8px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }

            .container {
                padding: 10px;
            }

            .header {
                padding: 15px;
            }

            .page-title {
                font-size: 24px;
            }

            .toolbar {
                flex-direction: column;
                align-items: stretch;
            }

            .toolbar-left {
                justify-content: center;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .modal-content {
                width: 95%;
                margin: 10% auto;
            }

            .btn {
                padding: 12px 16px;
                width: 100%;
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .calendar-container {
                padding: 15px;
            }

            .modal-body {
                padding: 15px;
            }

            .modal-header,
            .modal-footer {
                padding: 15px;
            }
        }

        /* Loading Spinner */
        .spinner {
            border: 3px solid #f3f3f3;
            border-top: 3px solid #e82226;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            animation: spin 1s linear infinite;
            margin-right: 8px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .loading {
            opacity: 0.7;
            pointer-events: none;
        }

        /* Tooltip */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip .tooltiptext {
            visibility: hidden;
            width: 120px;
            background-color: #2c3e50;
            color: #fff;
            text-align: center;
            border-radius: 6px;
            padding: 5px;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -60px;
            font-size: 12px;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .tooltip:hover .tooltiptext {
            visibility: visible;
            opacity: 1;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border: 1px solid transparent;
            border-radius: 8px;
        }

        .alert-error {
            color: #721c24;
            background-color: #f8d7da;
            border-color: #f5c6cb;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <img src="../image/airmagique_logo.png" alt="AIR MAGIQUE Logo" class="logo">
            <h1 class="page-title">Intervention Scheduler</h1>
            <p class="page-subtitle">Manage and schedule your technician interventions</p>
            <div class="admin-badge">Administrator: <?php echo htmlspecialchars($userFullName, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <!-- Back Button -->
        <a href="../adminmenu/adminmenu.php" class="back-button">
            Back to Admin Menu
        </a>

        <!-- Toolbar -->
        <div class="toolbar">
            <div class="toolbar-left">
                <button class="btn btn-primary" onclick="openInterventionModal()">
                    <i class="fas fa-plus"></i>
                    New Intervention
                </button>
                <button class="btn btn-outline" onclick="refreshCalendar()">
                    <i class="fas fa-sync-alt"></i>
                    Refresh
                </button>
            </div>
        </div>

        <!-- Calendar Container -->
        <div class="calendar-container">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Modal pour les interventions -->
    <div id="interventionModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">New Intervention</h2>
                <button class="close" onclick="closeInterventionModal()">&times;</button>
            </div>
            <div class="modal-body">
                <form id="interventionForm">
                    <input type="hidden" id="interventionId" name="intervention_id">
                    
                    <div class="form-group">
                        <label for="title">Intervention Title *</label>
                        <input type="text" id="title" name="title" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" class="form-control" rows="3"></textarea>
                    </div>

                    <div class="info-section">
                        <h4><i class="fas fa-user"></i> Client Information</h4>
                        <div class="form-group">
                            <label for="client_name">Client Name</label>
                            <input type="text" id="client_name" name="client_name" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="client_address">Address</label>
                            <textarea id="client_address" name="client_address" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="form-group">
                            <label for="client_phone">Phone</label>
                            <input type="tel" id="client_phone" name="client_phone" class="form-control">
                        </div>
                    </div>

                    <div class="info-section">
                        <h4><i class="fas fa-calendar"></i> Scheduling</h4>
                        <div class="form-group">
                            <label for="technician_id">Assigned Technician (from account table)</label>
                            <select id="technician_id" name="technician_id" class="form-control">
                                <option value="">Select a technician...</option>
                            </select>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="start_datetime">Start Date & Time *</label>
                                <input type="datetime-local" id="start_datetime" name="start_datetime" class="form-control" required>
                            </div>
                            <div class="form-group">
                                <label for="end_datetime">End Date & Time *</label>
                                <input type="datetime-local" id="end_datetime" name="end_datetime" class="form-control" required>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <label for="status">Status</label>
                                <select id="status" name="status" class="form-control">
                                    <option value="scheduled">Scheduled</option>
                                    <option value="in_progress">In Progress</option>
                                    <option value="completed">Completed</option>
                                    <option value="cancelled">Cancelled</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="priority">Priority</label>
                                <select id="priority" name="priority" class="form-control">
                                    <option value="low">Low</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes">Additional Notes</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeInterventionModal()">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="deleteIntervention()" id="deleteBtn" style="display:none;">
                    <i class="fas fa-trash"></i>
                    Delete
                </button>
                <button type="button" class="btn btn-primary" onclick="saveIntervention()" id="saveBtn">
                    <i class="fas fa-save"></i>
                    Save
                </button>
            </div>
        </div>
    </div>

    <!-- FullCalendar JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.8/index.global.min.js"></script>

    <script>
        let calendar;
        let technicians = [];
        let currentEditingEvent = null;

        // Initialisation au chargement de la page
        document.addEventListener('DOMContentLoaded', function() {
            loadTechnicians();
            initializeCalendar();
        });

        // Charger la liste des techniciens depuis la table account
        function loadTechnicians() {
            console.log('Starting technician loading...');
            
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_technicians'
            })
            .then(response => {
                console.log('Response received:', response.status, response.statusText);
                if (!response.ok) {
                    throw new Error('Network error: ' + response.status);
                }
                return response.text();
            })
            .then(text => {
                console.log('Raw text received:', text);
                try {
                    const data = JSON.parse(text);
                    if (data.success === false) {
                        throw new Error(data.error || 'Unknown error');
                    }
                    technicians = data;
                    console.log('Technicians loaded from account:', technicians);
                    console.log('Number of technicians:', technicians.length);
                    populateTechnicianSelect();
                } catch (e) {
                    console.error('JSON parsing error:', e);
                    console.error('Text received:', text);
                    throw new Error('Non-JSON response received from server');
                }
            })
            .catch(error => {
                console.error('Error loading technicians:', error);
                showNotification('Error loading technicians: ' + error.message, 'error');
            });
        }

        // Remplir le select des techniciens avec les données de la table account
        function populateTechnicianSelect() {
            const select = document.getElementById('technician_id');
            select.innerHTML = '<option value="">Select a technician...</option>';
            
            technicians.forEach(tech => {
                const option = document.createElement('option');
                option.value = tech.id; // idacount de la table account
                option.textContent = tech.name; // Just name and surname
                option.dataset.email = tech.email || '';
                option.dataset.phone = tech.phone_number || '';
                select.appendChild(option);
            });
        }

        // Fonction pour obtenir l'icône de priorité
        function getPriorityIcon(priority) {
            const icons = {
                'low': 'fas fa-check-circle',
                'medium': 'fas fa-exclamation-circle',
                'high': 'fas fa-exclamation-triangle',
                'urgent': 'fas fa-fire'
            };
            return icons[priority] || 'fas fa-info-circle';
        }

        // Initialiser le calendrier
        function initializeCalendar() {
            const calendarEl = document.getElementById('calendar');
            
            calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'en',
                firstDay: 1,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                buttonText: {
                    today: 'Today',
                    month: 'Month',
                    week: 'Week',
                    day: 'Day'
                },
                height: 'auto',
                editable: true,
                droppable: true,
                selectable: true,
                selectMirror: true,
                dayMaxEvents: true,
                weekends: true,
                
                // Charger les événements
                events: function(fetchInfo, successCallback, failureCallback) {
                    loadEvents(fetchInfo.startStr, fetchInfo.endStr, successCallback, failureCallback);
                },

                // Clic sur un événement
                eventClick: function(info) {
                    openInterventionModal(info.event);
                },

                // Sélection d'une plage de temps
                select: function(info) {
                    openInterventionModal(null, info.startStr, info.endStr);
                },

                // Déplacement d'un événement
                eventDrop: function(info) {
                    updateEventTime(info.event);
                },

                // Redimensionnement d'un événement
                eventResize: function(info) {
                    updateEventTime(info.event);
                },

                // Style des événements
                eventDisplay: 'block',
                eventTextColor: '#ffffff',

                // Ajouter l'icône de priorité après le rendu
                eventDidMount: function(info) {
                    const priority = info.event.extendedProps.priority;
                    if (priority) {
                        const iconClass = getPriorityIcon(priority);
                        const icon = document.createElement('i');
                        icon.className = `priority-icon ${iconClass}`;
                        
                        // Ajouter la classe de priorité pour le style
                        info.el.classList.add(`priority-${priority}`);
                        
                        // Trouver l'élément de contenu et ajouter l'icône
                        const content = info.el.querySelector('.fc-event-main') || info.el.querySelector('.fc-event-title-container') || info.el;
                        content.style.position = 'relative';
                        content.appendChild(icon);
                    }
                },
                
                // Tooltip au survol
                eventMouseEnter: function(info) {
                    const event = info.event;
                    const props = event.extendedProps;
                    
                    const tooltip = document.createElement('div');
                    tooltip.className = 'tooltip-event';
                    tooltip.style.cssText = `
                        position: absolute;
                        background: #2c3e50;
                        color: white;
                        padding: 10px;
                        border-radius: 6px;
                        font-size: 12px;
                        z-index: 1000;
                        max-width: 250px;
                        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
                    `;
                    
                    tooltip.innerHTML = `
                        <strong>${event.title}</strong><br>
                        ${props.client_name ? `Client: ${props.client_name}<br>` : ''}
                        ${props.technician_name ? `Technician: ${props.technician_name} (${props.technician_depot || 'No depot'})<br>` : 'No technician assigned<br>'}
                        Start: ${new Date(event.start).toLocaleString('en-US')}<br>
                        End: ${new Date(event.end).toLocaleString('en-US')}<br>
                        Status: ${getStatusText(props.status)}<br>
                        Priority: ${getPriorityText(props.priority)}
                    `;
                    
                    document.body.appendChild(tooltip);
                    
                    const moveTooltip = (e) => {
                        tooltip.style.left = (e.pageX + 10) + 'px';
                        tooltip.style.top = (e.pageY + 10) + 'px';
                    };
                    
                    info.el.addEventListener('mousemove', moveTooltip);
                    info.el.tooltip = tooltip;
                    info.el.moveTooltip = moveTooltip;
                },

                eventMouseLeave: function(info) {
                    if (info.el.tooltip) {
                        document.body.removeChild(info.el.tooltip);
                        info.el.removeEventListener('mousemove', info.el.moveTooltip);
                    }
                }
            });

            calendar.render();
        }

        // Charger les événements depuis la base de données avec jointure sur account
        function loadEvents(start, end, successCallback, failureCallback) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_events&start=${start}&end=${end}`
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network error: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success === false) {
                    throw new Error(data.error || 'Unknown error');
                }
                console.log('Events loaded:', data);
                successCallback(data);
            })
            .catch(error => {
                console.error('Error loading events:', error);
                showNotification('Error loading events: ' + error.message, 'error');
                failureCallback(error);
            });
        }

        // Ouvrir le modal d'intervention
        function openInterventionModal(event = null, startStr = null, endStr = null) {
            const modal = document.getElementById('interventionModal');
            const form = document.getElementById('interventionForm');
            const title = document.getElementById('modalTitle');
            const deleteBtn = document.getElementById('deleteBtn');
            
            // Reset form complètement
            form.reset();
            currentEditingEvent = event;
            
            // Nettoyer tous les champs
            document.getElementById('interventionId').value = '';
            document.getElementById('title').value = '';
            document.getElementById('description').value = '';
            document.getElementById('client_name').value = '';
            document.getElementById('client_address').value = '';
            document.getElementById('client_phone').value = '';
            document.getElementById('technician_id').value = '';
            document.getElementById('status').value = 'scheduled';
            document.getElementById('priority').value = 'medium';
            document.getElementById('notes').value = '';
            
            if (event) {
                // Édition d'un événement existant
                title.textContent = 'Edit Intervention';
                deleteBtn.style.display = 'inline-block';
                
                const props = event.extendedProps;
                document.getElementById('interventionId').value = event.id;
                document.getElementById('title').value = event.title || '';
                document.getElementById('description').value = props.description || '';
                document.getElementById('client_name').value = props.client_name || '';
                document.getElementById('client_address').value = props.client_address || '';
                document.getElementById('client_phone').value = props.client_phone || '';
                document.getElementById('technician_id').value = props.technician_id || '';
                document.getElementById('start_datetime').value = formatDateTimeLocal(event.start);
                document.getElementById('end_datetime').value = formatDateTimeLocal(event.end);
                document.getElementById('status').value = props.status || 'scheduled';
                document.getElementById('priority').value = props.priority || 'medium';
                document.getElementById('notes').value = props.notes || '';
                
                console.log('Editing intervention - technician_id:', props.technician_id);
            } else {
                // Nouvelle intervention
                title.textContent = 'New Intervention';
                deleteBtn.style.display = 'none';
                
                if (startStr && endStr) {
                    document.getElementById('start_datetime').value = formatDateTimeLocal(new Date(startStr));
                    document.getElementById('end_datetime').value = formatDateTimeLocal(new Date(endStr));
                } else {
                    // Défaut: maintenant + 1 heure
                    const now = new Date();
                    const later = new Date(now.getTime() + 60 * 60 * 1000);
                    document.getElementById('start_datetime').value = formatDateTimeLocal(now);
                    document.getElementById('end_datetime').value = formatDateTimeLocal(later);
                }
            }
            
            modal.classList.add('show');
            // Focus sur le premier champ
            setTimeout(() => {
                document.getElementById('title').focus();
            }, 100);
        }

        // Fermer le modal d'intervention
        function closeInterventionModal() {
            const modal = document.getElementById('interventionModal');
            modal.classList.remove('show');
            currentEditingEvent = null;
        }

        // Sauvegarder l'intervention (technician_id = account.idacount)
        function saveIntervention() {
            const form = document.getElementById('interventionForm');
            const saveBtn = document.getElementById('saveBtn');
            
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }
            
            // Validation des dates
            const startDate = new Date(document.getElementById('start_datetime').value);
            const endDate = new Date(document.getElementById('end_datetime').value);
            
            if (endDate <= startDate) {
                showNotification('End date must be after start date', 'error');
                return;
            }
            
            // Afficher le spinner
            saveBtn.innerHTML = '<div class="spinner"></div>Saving...';
            saveBtn.disabled = true;
            
            const formData = new FormData(form);
            formData.append('action', 'save_intervention');
            
            console.log('Saving - selected technician_id:', formData.get('technician_id'));
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    closeInterventionModal();
                    // Force le rafraîchissement complet du calendrier
                    setTimeout(() => {
                        calendar.refetchEvents();
                        showNotification('Intervention saved successfully', 'success');
                    }, 100);
                } else {
                    showNotification('Error saving: ' + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error saving: ' + error.message, 'error');
            })
            .finally(() => {
                saveBtn.innerHTML = '<i class="fas fa-save"></i>Save';
                saveBtn.disabled = false;
            });
        }

        // Supprimer l'intervention
        function deleteIntervention() {
            if (!currentEditingEvent || !confirm('Are you sure you want to delete this intervention?')) {
                return;
            }
            
            const deleteBtn = document.getElementById('deleteBtn');
            deleteBtn.innerHTML = '<div class="spinner"></div>Deleting...';
            deleteBtn.disabled = true;
            
            const formData = new FormData();
            formData.append('action', 'delete_intervention');
            formData.append('intervention_id', currentEditingEvent.id);
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network error: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    closeInterventionModal();
                    // Force le rafraîchissement complet du calendrier
                    setTimeout(() => {
                        calendar.refetchEvents();
                        showNotification('Intervention deleted successfully', 'success');
                    }, 100);
                } else {
                    showNotification('Error deleting: ' + (data.error || 'Unknown error'), 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error deleting: ' + error.message, 'error');
            })
            .finally(() => {
                deleteBtn.innerHTML = '<i class="fas fa-trash"></i>Delete';
                deleteBtn.disabled = false;
            });
        }

        // Mettre à jour l'heure d'un événement après déplacement/redimensionnement
        function updateEventTime(event) {
            const formData = new FormData();
            formData.append('action', 'update_event_time');
            formData.append('intervention_id', event.id);
            formData.append('start_datetime', formatDateTimeForDB(event.start));
            formData.append('end_datetime', formatDateTimeForDB(event.end));
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    // Forcer le rechargement complet des données pour éviter les problèmes de cache
                    setTimeout(() => {
                        calendar.refetchEvents();
                        showNotification('Intervention updated', 'success');
                    }, 100);
                } else {
                    showNotification('Error updating: ' + (data.error || 'Unknown error'), 'error');
                    // Recharger en cas d'erreur pour revenir à l'état précédent
                    calendar.refetchEvents();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error updating: ' + error.message, 'error');
                // Recharger en cas d'erreur
                calendar.refetchEvents();
            });
        }

        // Actualiser le calendrier
        function refreshCalendar() {
            calendar.refetchEvents();
            loadTechnicians(); // Recharger aussi les techniciens
            showNotification('Calendar refreshed', 'info');
        }

        // Exporter le calendrier (fonctionnalité basique)
        function exportCalendar() {
            showNotification('Export functionality under development', 'info');
        }

        // Fonctions utilitaires
        function formatDateTimeLocal(date) {
            if (!date) return '';
            const d = new Date(date);
            d.setMinutes(d.getMinutes() - d.getTimezoneOffset());
            return d.toISOString().slice(0, 16);
        }

        function formatDateTimeForDB(date) {
            if (!date) return '';
            return new Date(date).toISOString().slice(0, 19).replace('T', ' ');
        }

        function getStatusText(status) {
            const statusTexts = {
                'scheduled': 'Scheduled',
                'in_progress': 'In Progress',
                'completed': 'Completed',
                'cancelled': 'Cancelled'
            };
            return statusTexts[status] || status;
        }

        function getPriorityText(priority) {
            const priorityTexts = {
                'low': 'Low',
                'medium': 'Medium',
                'high': 'High',
                'urgent': 'Urgent'
            };
            return priorityTexts[priority] || priority;
        }

        // Système de notifications
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px 20px;
                border-radius: 8px;
                color: white;
                font-weight: 600;
                z-index: 2000;
                animation: slideInRight 0.3s ease;
                max-width: 300px;
                word-wrap: break-word;
            `;
            
            const colors = {
                'success': '#28a745',
                'error': '#dc3545',
                'warning': '#ffc107',
                'info': '#17a2b8'
            };
            
            notification.style.backgroundColor = colors[type] || colors.info;
            notification.textContent = message;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 3000);
        }

        // Gestion des clics en dehors du modal
        window.addEventListener('click', function(event) {
            const modal = document.getElementById('interventionModal');
            if (event.target === modal) {
                closeInterventionModal();
            }
        });

        // Gestion de la touche Échap
        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeInterventionModal();
            }
        });

        // Animation CSS pour les notifications
        const style = document.createElement('style');
        style.textContent = `
            @keyframes slideInRight {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            
            @keyframes slideOutRight {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>