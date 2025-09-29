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

// Traitement des actions (update/delete)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'update') {
            $selectedName = $_POST['selectedName'];
            $last_name = $_POST['last_name'];
            $first_name = $_POST['first_name'];
            $mail = $_POST['mail'];
            $phone_number = $_POST['phone_number'];
            $depot = $_POST['depot'];
            $partner_name = $_POST['partner_name'];
            $partner_email = $_POST['partner_email'];

            $query = "UPDATE account SET last_name=?, first_name=?, mail=?, phone_number=?, depot=?, partner_name=?, partner_email=? WHERE CONCAT(last_name, ' ', first_name) = ?";
            $stmt = mysqli_prepare($connexion, $query);
            mysqli_stmt_bind_param($stmt, "ssssssss", $last_name, $first_name, $mail, $phone_number, $depot, $partner_name, $partner_email, $selectedName);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Account updated successfully!";
                $messageType = "success";
            } else {
                $message = "Error updating account.";
                $messageType = "error";
            }
        } elseif ($_POST['action'] == 'delete') {
            $selectedName = $_POST['selectedName'];
            $query = "DELETE FROM account WHERE CONCAT(last_name, ' ', first_name) = ?";
            $stmt = mysqli_prepare($connexion, $query);
            mysqli_stmt_bind_param($stmt, "s", $selectedName);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Account deleted successfully!";
                $messageType = "success";
            } else {
                $message = "Error deleting account.";
                $messageType = "error";
            }
        }
    }
}

// AJAX pour récupérer les détails d'un compte
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_details' && isset($_GET['name'])) {
    $selectedName = htmlspecialchars($_GET['name']);
    $query = "SELECT * FROM account WHERE CONCAT(last_name, ' ', first_name) = ?";
    $stmt = mysqli_prepare($connexion, $query);
    mysqli_stmt_bind_param($stmt, "s", $selectedName);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        echo json_encode($row);
    } else {
        echo json_encode(['error' => 'Account not found']);
    }
    exit();
}

// Récupération de tous les comptes pour la liste - triés par nom de famille (exclusion du compte admin)
$query = "SELECT last_name, first_name FROM account WHERE LOWER(username) != 'airmagique' ORDER BY last_name ASC, first_name ASC";
$result = mysqli_query($connexion, $query);
$accounts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $accounts[] = $row['last_name'] . " " . $row['first_name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Accounts - inv.ctiai.com</title>
    
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
            max-width: 1000px;
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

        .search-section {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            margin-bottom: 20px;
        }

        .search-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 8px;
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

        /* Custom Dropdown */
        .custom-dropdown {
            position: relative;
            width: 100%;
        }

        .dropdown-input {
            width: 100%;
            padding: 12px 45px 12px 16px;
            border: 1px solid #ced4da;
            border-radius: 8px;
            background: #f8f9fa;
            font-size: 14px;
            color: #495057;
            transition: all 0.2s ease;
            font-family: inherit;
            cursor: pointer;
        }

        .dropdown-input:focus {
            outline: none;
            border-color: #eb2226;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(235, 34, 38, 0.1);
        }

        .dropdown-arrow {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            pointer-events: none;
            transition: transform 0.3s ease;
            color: #6c757d;
            font-size: 12px;
        }

        .dropdown-arrow.open {
            transform: translateY(-50%) rotate(180deg);
        }

        .dropdown-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ced4da;
            border-top: none;
            border-radius: 0 0 8px 8px;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .dropdown-list.show {
            display: block;
        }

        .dropdown-item {
            padding: 12px 16px;
            cursor: pointer;
            border-bottom: 1px solid #f8f9fa;
            transition: background-color 0.2s ease;
            font-size: 14px;
        }

        .dropdown-item:hover {
            background-color: #f8f9fa;
        }

        .dropdown-item:last-child {
            border-bottom: none;
        }

        .dropdown-item.hidden {
            display: none;
        }

        .no-results {
            padding: 12px 16px;
            color: #6c757d;
            font-style: italic;
            font-size: 14px;
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

        input[type="text"], input[type="email"], select {
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

        input[type="text"]:focus, input[type="email"]:focus, select:focus {
            outline: none;
            border-color: #eb2226;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(235, 34, 38, 0.1);
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

        .btn-danger {
            background: #dc3545;
            color: #ffffff;
        }

        .btn-danger:hover {
            background: #c82333;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(220, 53, 69, 0.3);
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

        .btn-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .details-section {
            display: none;
        }

        .details-section.visible {
            display: block;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
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

        .details-section {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            margin-bottom: 20px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .search-section {
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

            .btn-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                margin: 5px 0;
            }

            /* Réorganisation des boutons sur mobile */
            .btn-primary {
                order: 1; /* Update Account en premier */
            }

            .btn-danger {
                order: 2; /* Delete Account en deuxième */
            }

            .btn-secondary {
                order: 3; /* Back en dernier */
            }
        }

        @media (max-width: 480px) {
            .search-section {
                padding: 15px;
            }

            .back-arrow {
                font-size: 20px;
                margin-top: -7px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img src="../image/airmagique_logo.png" alt="Logo" class="logo">
            <h1 class="page-title">Edit Account</h1>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <a href="../adminmenu/adminmenu.php" class="back-arrow" title="Back to admin menu">Back to Admin Menu</a>

        <div class="search-section">
            <div class="search-header">
                <label for="accountSelect">Select Account:</label>
            </div>
            <div class="form-group">
                <div class="custom-dropdown">
                    <input type="text" id="accountSelect" class="dropdown-input" placeholder="Click to select or type to search..." readonly>
                    <span class="dropdown-arrow">▼</span>
                    <div class="dropdown-list" id="dropdownList">
                        <?php foreach ($accounts as $account): ?>
                            <div class="dropdown-item" data-value="<?php echo htmlspecialchars($account); ?>">
                                <?php echo htmlspecialchars($account); ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="details-section" id="detailsSection">
            <h3 style="margin-bottom: 20px; color: #2c3e50;">Account Details</h3>
            <form id="updateForm" method="POST">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="selectedName" id="selectedName">
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="last_name">Last Name:</label>
                        <input type="text" name="last_name" id="last_name" required>
                    </div>
                    <div class="form-group">
                        <label for="first_name">First Name:</label>
                        <input type="text" name="first_name" id="first_name" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" class="readonly-field" readonly>
                    </div>
                    <div class="form-group">
                        <label for="idacount">Account ID:</label>
                        <input type="text" id="idacount" class="readonly-field" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="mail">Email:</label>
                        <input type="email" name="mail" id="mail" required>
                    </div>
                    <div class="form-group">
                        <label for="phone_number">Phone Number:</label>
                        <input type="text" name="phone_number" id="phone_number">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="depot">Depot:</label>
                        <input type="text" name="depot" id="depot">
                    </div>
                    <div class="form-group">
                        <label for="partner_name">Partner Name:</label>
                        <input type="text" name="partner_name" id="partner_name">
                    </div>
                </div>

                <div class="form-group">
                    <label for="partner_email">Partner Email:</label>
                    <input type="email" name="partner_email" id="partner_email">
                </div>

                <div class="btn-group">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i>
                        Update Account
                    </button>
                    <button type="button" id="deleteBtn" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i>
                        Delete Account
                    </button>
                </div>
            </form>

            <form id="deleteForm" method="POST" style="display: none;">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="selectedName" id="deleteSelectedName">
            </form>
        </div>
    </div>

    <script>
        const accountSelect = document.getElementById('accountSelect');
        const dropdownList = document.getElementById('dropdownList');
        const dropdownArrow = document.querySelector('.dropdown-arrow');
        const detailsSection = document.getElementById('detailsSection');
        const updateForm = document.getElementById('updateForm');
        const deleteForm = document.getElementById('deleteForm');
        const deleteBtn = document.getElementById('deleteBtn');
        const accounts = <?php echo json_encode($accounts); ?>;

        // Toggle dropdown
        accountSelect.addEventListener('click', function() {
            const isOpen = dropdownList.classList.contains('show');
            if (isOpen) {
                closeDropdown();
            } else {
                openDropdown();
            }
        });

        // Handle typing for search
        accountSelect.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filterDropdownItems(searchTerm);
            if (!dropdownList.classList.contains('show')) {
                openDropdown();
            }
        });

        // Click outside to close
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.custom-dropdown')) {
                closeDropdown();
            }
        });

        // Handle item selection
        dropdownList.addEventListener('click', function(e) {
            if (e.target.classList.contains('dropdown-item')) {
                const selectedValue = e.target.getAttribute('data-value');
                accountSelect.value = selectedValue;
                closeDropdown();
                loadAccountDetails(selectedValue);
            }
        });

        function openDropdown() {
            dropdownList.classList.add('show');
            dropdownArrow.classList.add('open');
            accountSelect.removeAttribute('readonly');
            accountSelect.focus();
        }

        function closeDropdown() {
            dropdownList.classList.remove('show');
            dropdownArrow.classList.remove('open');
            accountSelect.setAttribute('readonly', true);
        }

        function filterDropdownItems(searchTerm) {
            const items = dropdownList.querySelectorAll('.dropdown-item');
            let hasResults = false;

            items.forEach(item => {
                const text = item.textContent.toLowerCase();
                if (text.includes(searchTerm)) {
                    item.classList.remove('hidden');
                    hasResults = true;
                } else {
                    item.classList.add('hidden');
                }
            });

            // Remove existing no-results message
            const existingNoResults = dropdownList.querySelector('.no-results');
            if (existingNoResults) {
                existingNoResults.remove();
            }

            // Add no-results message if needed
            if (!hasResults && searchTerm) {
                const noResults = document.createElement('div');
                noResults.className = 'no-results';
                noResults.textContent = 'No accounts found';
                dropdownList.appendChild(noResults);
            }
        }

        function loadAccountDetails(name) {
            fetch(`?ajax=get_details&name=${encodeURIComponent(name)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Account not found');
                        return;
                    }
                    
                    // Fill the form
                    document.getElementById('selectedName').value = name;
                    document.getElementById('deleteSelectedName').value = name;
                    document.getElementById('last_name').value = data.last_name || '';
                    document.getElementById('first_name').value = data.first_name || '';
                    document.getElementById('username').value = data.username || '';
                    document.getElementById('idacount').value = data.idacount || '';
                    document.getElementById('mail').value = data.mail || '';
                    document.getElementById('phone_number').value = data.phone_number || '';
                    document.getElementById('depot').value = data.depot || '';
                    document.getElementById('partner_name').value = data.partner_name || '';
                    document.getElementById('partner_email').value = data.partner_email || '';
                    
                    detailsSection.classList.add('visible');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading account details');
                });
        }

        deleteBtn.addEventListener('click', function() {
            if (confirm('Are you sure you want to delete this account? This action cannot be undone.')) {
                deleteForm.submit();
            }
        });
    </script>
</body>
</html>