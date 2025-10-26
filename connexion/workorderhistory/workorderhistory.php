<?php  
include "../../config.php";
include_once "../logincheck.php";
include_once "../permissions.php";

if (!isset($_SESSION['user_id']) || !checkAdminPermission($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$connexion = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);
if (!$connexion) {
    die("Connection failed: " . mysqli_connect_error());
}

$message = "";
$messageType = "";

// Ici vous pouvez ajouter la logique pour récupérer les bons de travail
// Par exemple :
// $query = "SELECT * FROM work_orders ORDER BY date_created DESC";
// $result = mysqli_query($connexion, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Work Order History - inv.ctiai.com</title>
    
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f8f9fa;
            min-height: 100vh;
            color: #333333;
            line-height: 1.6;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 30px;
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
        }

        .logo {
            max-width: 200px;
            max-height: 120px;
            margin-bottom: 15px;
            border-radius: 8px;
        }

        .page-title {
            color: #2c3e50;
            font-size: 28px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        /* Flèche de retour simple */
        .back-arrow {
            text-decoration: none;
            color: #eb2226;
            font-size: 24px;
            font-weight: bold;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            margin-bottom: 30px;
        }

        .back-arrow:hover {
            color: #d11e21;
            transform: translateX(-3px);
        }

        .back-arrow::before {
            content: '←';
            margin-right: 8px;
        }

        .content-section {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            margin-bottom: 20px;
        }

        .section-title {
            color: #2c3e50;
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            color: #eb2226;
            font-size: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 14px;
        }

        input[type="text"], input[type="email"], input[type="date"], select, textarea {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            background: #f8f9fa;
            font-size: 14px;
            color: #495057;
            transition: all 0.2s ease;
            font-family: inherit;
        }

        input[type="text"]:focus, input[type="email"]:focus, input[type="date"]:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #eb2226;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(235, 34, 38, 0.1);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
            font-family: inherit;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-align: center;
            margin: 5px;
            justify-content: center;
        }

        .btn i {
            font-size: 16px;
        }

        .btn-primary {
            background: #eb2226;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #d11e21;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(235, 34, 38, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: #ffffff;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
        }

        .btn-success {
            background: #28a745;
            color: #ffffff;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
        }

        .btn-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-row.three-cols {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .readonly-field {
            background: #e9ecef;
            cursor: not-allowed;
        }

        .message {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 600;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .message.info {
            background: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            text-align: center;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
        }

        .stat-number {
            font-size: 32px;
            font-weight: 700;
            color: #eb2226;
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 14px;
            color: #6c757d;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-section {
            background: #ffffff;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
            border: 1px solid #e9ecef;
            margin-bottom: 20px;
        }

        .filter-title {
            color: #2c3e50;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .filter-title i {
            color: #eb2226;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .content-section {
                padding: 20px;
            }

            .back-arrow {
                font-size: 22px;
                margin-top: -4px;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .form-row.three-cols {
                grid-template-columns: 1fr;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                margin: 5px 0;
            }

            .stats-grid {
                grid-template-columns: 1fr;
                gap: 15px;
            }
        }

        @media (max-width: 480px) {
            .content-section {
                padding: 15px;
            }

            .back-arrow {
                font-size: 20px;
                margin-top: -7px;
            }

            .section-title {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img src="../image/airmagique_logo.png" alt="Logo" class="logo">
            <h1 class="page-title">Work Order History</h1>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <a href="../adminmenu/adminmenu.php" class="back-arrow" title="Back to admin menu">Back to Admin Menu</a>

        <!-- Statistics Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-number">0</div>
                <div class="stat-label">Total Work Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">0</div>
                <div class="stat-label">Pending Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">0</div>
                <div class="stat-label">Completed Orders</div>
            </div>
            <div class="stat-card">
                <div class="stat-number">0</div>
                <div class="stat-label">This Month</div>
            </div>
        </div>

        <!-- Filters Section -->
        <div class="filter-section">
            <h3 class="filter-title">
                <i class="fas fa-filter"></i>
                Filter Work Orders
            </h3>
            <div class="form-row three-cols">
                <div class="form-group">
                    <label for="dateFrom">Date From:</label>
                    <input type="date" id="dateFrom" name="dateFrom">
                </div>
                <div class="form-group">
                    <label for="dateTo">Date To:</label>
                    <input type="date" id="dateTo" name="dateTo">
                </div>
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status">
                        <option value="">All Status</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="technician">Technician:</label>
                    <select id="technician" name="technician">
                        <option value="">All Technicians</option>
                        <!-- Options will be populated from database -->
                    </select>
                </div>
                <div class="form-group">
                    <label for="search">Search:</label>
                    <input type="text" id="search" name="search" placeholder="Search work orders...">
                </div>
            </div>
            <div class="btn-group">
                <button type="button" class="btn btn-primary">
                    <i class="fas fa-search"></i>
                    Search
                </button>
                <button type="button" class="btn btn-secondary">
                    <i class="fas fa-undo"></i>
                    Reset Filters
                </button>
            </div>
        </div>

        <!-- Work Orders List Section -->
        <div class="content-section">
            <h3 class="section-title">
                <i class="fas fa-clipboard-list"></i>
                Work Orders List
            </h3>
            
            <div class="message info">
                <i class="fas fa-info-circle"></i>
                No work orders found. The work order history system is ready to be implemented.
            </div>

            <!-- Future table/list will go here -->
            <!-- 
            <div class="table-container">
                <table class="work-orders-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Customer</th>
                            <th>Technician</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        // Work orders will be displayed here
                    </tbody>
                </table>
            </div>
            -->

            <div class="btn-group">
                <button type="button" class="btn btn-success">
                    <i class="fas fa-plus"></i>
                    New Work Order
                </button>
                <button type="button" class="btn btn-primary">
                    <i class="fas fa-download"></i>
                    Export to PDF
                </button>
                <button type="button" class="btn btn-secondary">
                    <i class="fas fa-file-excel"></i>
                    Export to Excel
                </button>
            </div>
        </div>
    </div>

    <script>
        // Placeholder JavaScript for future functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Work Order History page loaded');
            
            // Add event listeners for buttons when functionality is implemented
            // Example:
            // document.querySelector('.btn-primary').addEventListener('click', searchWorkOrders);
            // document.querySelector('.btn-secondary').addEventListener('click', resetFilters);
        });

        function searchWorkOrders() {
            // Implementation for searching work orders
            console.log('Search work orders functionality to be implemented');
        }

        function resetFilters() {
            // Implementation for resetting filters
            console.log('Reset filters functionality to be implemented');
        }

        function exportToPDF() {
            // Implementation for PDF export
            console.log('PDF export functionality to be implemented');
        }

        function exportToExcel() {
            // Implementation for Excel export
            console.log('Excel export functionality to be implemented');
        }
    </script>
</body>
</html>