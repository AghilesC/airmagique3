<?php
session_start();
include "../../config.php";
include_once "../logincheck.php";

// Database connection
$connexion = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);
if (!$connexion) {
    die("Connection failed: " . mysqli_connect_error());
}

$userId = $_SESSION['user_id'];

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$userFullName = $_SESSION['first_name'] . ' ' . $_SESSION['last_name'];

try {
    mysqli_set_charset($connexion, "utf8");
} catch (Exception $e) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Database connection error']);
        exit();
    }
    die("Connection error: " . $e->getMessage());
}

// Vérifier que les tables existent
$checkAccount = mysqli_query($connexion, "SHOW TABLES LIKE 'account'");
$checkInterventions = mysqli_query($connexion, "SHOW TABLES LIKE 'interventions'");

if (mysqli_num_rows($checkAccount) == 0 || mysqli_num_rows($checkInterventions) == 0) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Required tables do not exist']);
        exit();
    }
    die("Error: Required calendar tables do not exist.");
}

// Gestion des requêtes AJAX - LECTURE SEULE pour technicien
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
                         WHERE i.technician_id = ?";
                
                $stmt = mysqli_prepare($connexion, $query);
                if (!$stmt) {
                    throw new Exception("Query preparation error: " . mysqli_error($connexion));
                }
                
                mysqli_stmt_bind_param($stmt, "i", $userId);
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
                
            default:
                throw new Exception("Action not recognized");
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Calendar - inv.ctiai.com</title>
    
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

        .tech-badge {
            display: inline-block;
            background: linear-gradient(135deg, #0090e0, #007bb5);
            color: white;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 12px;
            font-weight: 600;
            box-shadow: 0 2px 8px rgba(0, 144, 224, 0.3);
        }

        .back-button {
            text-decoration: none;
            color: #0090e0;
            font-size: 24px;
            font-weight: bold;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            margin-bottom: 30px;
        }

        .back-button:hover {
            color: #007bb5;
            transform: translateX(-3px);
        }

        .back-button::before {
            content: '←';
            margin-right: 8px;
        }

        .info-banner {
            background: linear-gradient(135deg, #e3f2fd, #bbdefb);
            border: 1px solid #2196f3;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
            color: #1565c0;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
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
            background-color: #0090e0 !important;
            border-color: #0090e0 !important;
            font-weight: 600 !important;
            font-size: 13px !important;
            padding: 8px 16px !important;
            border-radius: 8px !important;
            transition: all 0.3s ease !important;
        }

        .fc-button-primary:hover {
            background-color: #007bb5 !important;
            border-color: #007bb5 !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 12px rgba(0, 144, 224, 0.3) !important;
        }

        .fc-button-primary:disabled {
            background-color: #e9ecef !important;
            border-color: #e9ecef !important;
            transform: none !important;
            box-shadow: none !important;
        }

        .fc-button-primary:focus {
            box-shadow: 0 0 0 3px rgba(0, 144, 224, 0.2) !important;
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
            cursor: pointer !important;
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
            margin-bottom: 8px;
            font-size: 14px;
            padding: 5px 0;
        }

        .info-label {
            font-weight: 600;
            color: #6c757d;
        }

        .info-value {
            color: #2c3e50;
            text-align: right;
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
            justify-content: center;
            background: #f8f9fa;
            border-radius: 0 0 12px 12px;
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

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
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

            .calendar-container {
                padding: 15px;
            }
        }

        @media (max-width: 480px) {
            .modal-content {
                width: 95%;
                margin: 10% auto;
            }

            .modal-body {
                padding: 15px;
            }

            .modal-header,
            .modal-footer {
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header Section -->
        <div class="header">
            <img src="../image/airmagique_logo.png" alt="AIR MAGIQUE Logo" class="logo">
            <h1 class="page-title">My Calendar</h1>
            <p class="page-subtitle">View your assigned interventions</p>
            <div class="tech-badge">Technician: <?php echo htmlspecialchars($userFullName, ENT_QUOTES, 'UTF-8'); ?></div>
        </div>

        <!-- Back Button -->
        <a href="../techmenu/techmenu.php" class="back-button">
            Back to Tech Menu
        </a>

        <!-- Info Banner -->
        <div class="info-banner">
            <i class="fas fa-info-circle"></i>
            <span>This calendar shows only your assigned interventions. Contact your administrator to make changes.</span>
        </div>

        <!-- Calendar Container -->
        <div class="calendar-container">
            <div id="calendar"></div>
        </div>
    </div>

    <!-- Modal pour visualiser l'intervention -->
    <div id="viewModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title" id="modalTitle">Intervention Details</h2>
                <button class="close" onclick="closeViewModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="info-section">
                    <h4><i class="fas fa-info-circle"></i> General Information</h4>
                    <div class="info-row">
                        <span class="info-label">Title:</span>
                        <span class="info-value" id="view_title"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Description:</span>
                        <span class="info-value" id="view_description"></span>
                    </div>
                </div>

                <div class="info-section">
                    <h4><i class="fas fa-user"></i> Client Information</h4>
                    <div class="info-row">
                        <span class="info-label">Name:</span>
                        <span class="info-value" id="view_client_name"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Address:</span>
                        <span class="info-value" id="view_client_address"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Phone:</span>
                        <span class="info-value" id="view_client_phone"></span>
                    </div>
                </div>

                <div class="info-section">
                    <h4><i class="fas fa-calendar"></i> Schedule</h4>
                    <div class="info-row">
                        <span class="info-label">Start:</span>
                        <span class="info-value" id="view_start"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">End:</span>
                        <span class="info-value" id="view_end"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Status:</span>
                        <span class="info-value" id="view_status"></span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Priority:</span>
                        <span class="info-value" id="view_priority"></span>
                    </div>
                </div>

                <div class="info-section" id="notes_section" style="display:none;">
                    <h4><i class="fas fa-sticky-note"></i> Notes</h4>
                    <p id="view_notes" style="color: #2c3e50; margin: 0;"></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeViewModal()">
                    <i class="fas fa-times"></i>
                    Close
                </button>
            </div>
        </div>
    </div>

    <!-- FullCalendar JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/6.1.8/index.global.min.js"></script>

    <script>
        let calendar;

        document.addEventListener('DOMContentLoaded', function() {
            initializeCalendar();
        });

        function getPriorityIcon(priority) {
            const icons = {
                'low': 'fas fa-check-circle',
                'medium': 'fas fa-exclamation-circle',
                'high': 'fas fa-exclamation-triangle',
                'urgent': 'fas fa-fire'
            };
            return icons[priority] || 'fas fa-info-circle';
        }

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
                editable: false,
                droppable: false,
                selectable: false,
                selectMirror: false,
                dayMaxEvents: true,
                weekends: true,
                
                events: function(fetchInfo, successCallback, failureCallback) {
                    loadEvents(fetchInfo.startStr, fetchInfo.endStr, successCallback, failureCallback);
                },

                eventClick: function(info) {
                    openViewModal(info.event);
                },

                eventDisplay: 'block',
                eventTextColor: '#ffffff',

                eventDidMount: function(info) {
                    const priority = info.event.extendedProps.priority;
                    if (priority) {
                        const iconClass = getPriorityIcon(priority);
                        const icon = document.createElement('i');
                        icon.className = `priority-icon ${iconClass}`;
                        info.el.classList.add(`priority-${priority}`);
                        const content = info.el.querySelector('.fc-event-main') || info.el.querySelector('.fc-event-title-container') || info.el;
                        content.style.position = 'relative';
                        content.appendChild(icon);
                    }
                }
            });

            calendar.render();
        }

        function loadEvents(start, end, successCallback, failureCallback) {
            fetch('', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=get_events&start=${start}&end=${end}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success === false) {
                    throw new Error(data.error || 'Unknown error');
                }
                successCallback(data);
            })
            .catch(error => {
                console.error('Error loading interventions:', error);
                failureCallback(error);
            });
        }

        function openViewModal(event) {
            const props = event.extendedProps;
            
            document.getElementById('modalTitle').textContent = event.title;
            document.getElementById('view_title').textContent = event.title;
            document.getElementById('view_description').textContent = props.description || 'N/A';
            document.getElementById('view_client_name').textContent = props.client_name || 'N/A';
            document.getElementById('view_client_address').textContent = props.client_address || 'N/A';
            document.getElementById('view_client_phone').textContent = props.client_phone || 'N/A';
            document.getElementById('view_start').textContent = new Date(event.start).toLocaleString('en-US');
            document.getElementById('view_end').textContent = new Date(event.end).toLocaleString('en-US');
            
            const statusBadge = `<span class="status-badge status-${props.status}">${getStatusText(props.status)}</span>`;
            document.getElementById('view_status').innerHTML = statusBadge;
            
            const priorityBadge = `<span class="priority-badge priority-${props.priority}">${getPriorityText(props.priority)}</span>`;
            document.getElementById('view_priority').innerHTML = priorityBadge;
            
            if (props.notes) {
                document.getElementById('notes_section').style.display = 'block';
                document.getElementById('view_notes').textContent = props.notes;
            } else {
                document.getElementById('notes_section').style.display = 'none';
            }
            
            document.getElementById('viewModal').classList.add('show');
        }

        function closeViewModal() {
            document.getElementById('viewModal').classList.remove('show');
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

        window.addEventListener('click', function(event) {
            const modal = document.getElementById('viewModal');
            if (event.target === modal) {
                closeViewModal();
            }
        });

        document.addEventListener('keydown', function(event) {
            if (event.key === 'Escape') {
                closeViewModal();
            }
        });
    </script>
</body>
</html>