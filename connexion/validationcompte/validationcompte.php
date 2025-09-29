<?php 
session_start();

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

// Handle account status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["action"])) {
    if ($_POST["action"] == "update_status") {
        $accountName = $_POST["account"];
        $status = $_POST["status"];

        $query = "UPDATE account SET validation = ? WHERE CONCAT(last_name, ' ', first_name) = ?";
        $stmt = mysqli_prepare($connexion, $query);
        mysqli_stmt_bind_param($stmt, "ss", $status, $accountName);
        
        if (mysqli_stmt_execute($stmt)) {
            if ($status === "activated") {
                $message = "Account activated successfully!";
            } else {
                $message = "Account deactivated successfully!";
            }
            $messageType = "success";
        } else {
            $message = "Error updating account status.";
            $messageType = "error";
        }
    } elseif ($_POST["action"] == "activate_new") {
        $accountName = $_POST["account"];
        
        // Update both validation and isNew columns
        $query = "UPDATE account SET validation = 'activated', isNew = 'no' WHERE CONCAT(last_name, ' ', first_name) = ?";
        $stmt = mysqli_prepare($connexion, $query);
        mysqli_stmt_bind_param($stmt, "s", $accountName);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "New account activated successfully!";
            $messageType = "success";
        } else {
            $message = "Error activating new account.";
            $messageType = "error";
        }
    } elseif ($_POST["action"] == "reject_new") {
        $accountName = $_POST["account"];
        
        // Update isNew to 'no' but keep validation as 'deactivated'
        $query = "UPDATE account SET isNew = 'no' WHERE CONCAT(last_name, ' ', first_name) = ?";
        $stmt = mysqli_prepare($connexion, $query);
        mysqli_stmt_bind_param($stmt, "s", $accountName);
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "New account rejected successfully!";
            $messageType = "success";
        } else {
            $message = "Error rejecting new account.";
            $messageType = "error";
        }
    }
}

// AJAX endpoint for getting account status
if (isset($_GET['ajax']) && $_GET['ajax'] == 'get_status' && isset($_GET['account'])) {
    $selectedAccount = htmlspecialchars($_GET['account']);
    $query = "SELECT validation FROM account WHERE CONCAT(last_name, ' ', first_name) = ?";
    $stmt = mysqli_prepare($connexion, $query);
    mysqli_stmt_bind_param($stmt, "s", $selectedAccount);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if(mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $status = $row['validation'] == 'activated' ? 'activated' : 'deactivated';
        echo json_encode(['status' => $status]);
    } else {
        echo json_encode(['error' => 'Account not found']);
    }
    exit();
}

// Get all accounts for the dropdown (excluding airmagique)
$query = "SELECT last_name, first_name FROM account WHERE LOWER(username) != 'airmagique' ORDER BY last_name ASC, first_name ASC";
$result = mysqli_query($connexion, $query);
$accounts = [];
while ($row = mysqli_fetch_assoc($result)) {
    $accounts[] = $row['last_name'] . " " . $row['first_name'];
}

// Get new accounts that need activation (excluding airmagique)
$newAccountsQuery = "SELECT last_name, first_name FROM account WHERE isNew = 'yes' AND LOWER(username) != 'airmagique' ORDER BY last_name ASC, first_name ASC";
$newAccountsResult = mysqli_query($connexion, $newAccountsQuery);
$newAccounts = [];
while ($row = mysqli_fetch_assoc($newAccountsResult)) {
    $newAccounts[] = $row['last_name'] . " " . $row['first_name'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Validation - inv.ctiai.com</title>
    
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
            max-width: 800px;
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

        .main-section {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            margin-bottom: 20px;
        }

        .back-arrow {
            text-decoration: none;
            color: #e82226;
            font-size: 24px;
            font-weight: bold;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .back-arrow:hover {
            color: #d11e21;
            transform: translateX(-3px);
        }

        .back-arrow::before {
            content: '←';
            margin-right: 8px;
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

        select {
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

        select:focus {
            outline: none;
            border-color: #e82226;
            background: #ffffff;
            box-shadow: 0 0 0 3px rgba(232, 34, 38, 0.1);
        }

        .status-section {
            display: none;
            margin-top: 20px;
            padding: 20px;
            border-radius: 8px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
        }

        .status-section.visible {
            display: block;
        }

        .status-info {
            text-align: center;
            margin-bottom: 20px;
        }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 14px;
        }

        .status-activated {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .status-deactivated {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .action-section {
            text-align: center;
            margin-top: 20px;
        }

        .action-question {
            margin-bottom: 15px;
            font-weight: 500;
            color: #2c3e50;
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

        .btn-success {
            background: #28a745;
            color: #ffffff;
        }

        .btn-success:hover {
            background: #218838;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
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

        .btn-primary {
            background: #e82226;
            color: #ffffff;
        }

        .btn-primary:hover {
            background: #d11e21;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(232, 34, 38, 0.3);
        }

        .new-accounts-section {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            margin-bottom: 20px;
        }

        .new-accounts-title {
            color: #2c3e50;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .new-accounts-title i {
            color: #e82226;
            font-size: 20px;
        }

        .new-account-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 15px 20px;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            margin-bottom: 10px;
            transition: all 0.3s ease;
        }

        .new-account-item:hover {
            background: #e9ecef;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .new-account-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .new-account-checkmark {
            color: #ffc107;
            font-size: 18px;
        }

        .new-account-name {
            font-weight: 600;
            color: #2c3e50;
        }

        .new-account-status {
            color: #6c757d;
            font-size: 14px;
            font-style: italic;
        }

        .new-account-actions {
            display: flex;
            gap: 10px;
        }

        .btn-activate {
            background: #28a745;
            color: #ffffff;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.3s ease;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-activate:hover {
            background: #218838;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
        }

        .btn-activate i {
            font-size: 14px;
        }

        .btn-reject {
            background: #dc3545;
            color: #ffffff;
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            font-size: 12px;
            transition: all 0.3s ease;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-reject:hover {
            background: #c82333;
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
        }

        .btn-reject i {
            font-size: 14px;
        }

        .no-new-accounts {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .navigation-section {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .main-section, .new-accounts-section {
                padding: 20px;
            }

            .navigation-section {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                margin: 5px 0;
            }

            .page-title {
                font-size: 24px;
            }

            .new-account-item {
                flex-direction: column;
                align-items: flex-start;
                padding: 15px;
                gap: 12px;
            }

            .new-account-info {
                width: 100%;
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            .new-account-name {
                font-size: 16px;
            }

            .new-account-status {
                font-size: 13px;
            }

            .new-account-actions {
                width: 100%;
                justify-content: flex-start;
            }

            .btn-activate {
                padding: 10px 20px;
                font-size: 14px;
                width: auto;
            }
        }

        @media (max-width: 480px) {
            .main-section, .new-accounts-section {
                padding: 15px;
            }

            .page-title {
                font-size: 20px;
            }

            .new-account-item {
                padding: 12px;
            }

            .new-accounts-title {
                font-size: 16px;
            }

            .btn-activate {
                width: 100%;
                padding: 12px;
                font-size: 14px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img src="../image/airmagique_logo.png" alt="Logo" class="logo">
            <h1 class="page-title">Account Validation</h1>
        </div>

        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <a href="../adminmenu/adminmenu.php" class="back-arrow" title="Back to admin menu">Back to Admin Menu</a>

        <div class="new-accounts-section">
            <h2 class="new-accounts-title">
                <i class="fas fa-user-plus"></i>
                New accounts to activate:
            </h2>
            
            <?php if (count($newAccounts) > 0): ?>
                <?php foreach ($newAccounts as $account): ?>
                    <div class="new-account-item">
                        <div class="new-account-info">
                            <i class="fas fa-clock new-account-checkmark"></i>
                            <span class="new-account-name"><?php echo htmlspecialchars($account); ?></span>
                            <span class="new-account-status">Validation pending</span>
                        </div>
                        <div class="new-account-actions">
                            <button type="button" class="btn-activate" onclick="activateNewAccount('<?php echo htmlspecialchars($account); ?>')">
                                <i class="fas fa-check"></i>
                                Activate
                            </button>
                            <button type="button" class="btn-reject" onclick="rejectNewAccount('<?php echo htmlspecialchars($account); ?>')">
                                <i class="fas fa-times"></i>
                                Reject
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="no-new-accounts">
                    No new accounts waiting for activation
                </div>
            <?php endif; ?>
        </div>

        <div class="main-section">
            <div class="form-group">
                <label for="accountSelect">Select Account to Check Status:</label>
                <select id="accountSelect">
                    <option value="">-- Select an account --</option>
                    <?php foreach ($accounts as $account): ?>
                        <option value="<?php echo htmlspecialchars($account); ?>">
                            <?php echo htmlspecialchars($account); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="status-section" id="statusSection">
                <div class="status-info">
                    <span class="status-badge" id="statusBadge"></span>
                </div>
                
                <div class="action-section" id="actionSection">
                    <!-- Action buttons will be inserted here by JavaScript -->
                </div>
            </div>

            <form id="updateForm" method="POST" style="display: none;">
                <input type="hidden" name="action" value="update_status">
                <input type="hidden" name="account" id="selectedAccount">
                <input type="hidden" name="status" id="newStatus">
            </form>

            <form id="activateNewForm" method="POST" style="display: none;">
                <input type="hidden" name="action" value="activate_new">
                <input type="hidden" name="account" id="newAccountName">
            </form>

            <form id="rejectNewForm" method="POST" style="display: none;">
                <input type="hidden" name="action" value="reject_new">
                <input type="hidden" name="account" id="rejectAccountName">
            </form>
        </div>

        <div class="navigation-section" style="display: none;">
            <a href="../adminmenu/adminmenu.php" class="btn btn-secondary">Admin Menu</a>
            <a href="?logout" class="btn btn-primary">Logout</a>
        </div>
    </div>

    <script>
        const accountSelect = document.getElementById('accountSelect');
        const statusSection = document.getElementById('statusSection');
        const statusBadge = document.getElementById('statusBadge');
        const actionSection = document.getElementById('actionSection');
        const updateForm = document.getElementById('updateForm');
        const selectedAccountInput = document.getElementById('selectedAccount');
        const newStatusInput = document.getElementById('newStatus');

        accountSelect.addEventListener('change', function() {
            const selectedAccount = this.value;
            
            if (selectedAccount) {
                loadAccountStatus(selectedAccount);
            } else {
                statusSection.classList.remove('visible');
            }
        });

        function loadAccountStatus(accountName) {
            fetch(`?ajax=get_status&account=${encodeURIComponent(accountName)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        alert('Account not found');
                        return;
                    }
                    
                    // Update status badge
                    statusBadge.textContent = data.status === 'activated' ? 'Account Activated' : 'Account Deactivated';
                    statusBadge.className = 'status-badge ' + (data.status === 'activated' ? 'status-activated' : 'status-deactivated');
                    
                    // Update action section
                    actionSection.innerHTML = '';
                    
                    const questionDiv = document.createElement('div');
                    questionDiv.className = 'action-question';
                    
                    if (data.status === 'activated') {
                        questionDiv.textContent = 'Do you want to deactivate this account?';
                        const deactivateBtn = document.createElement('button');
                        deactivateBtn.innerHTML = '<i class="fas fa-user-times"></i> Deactivate Account';
                        deactivateBtn.className = 'btn btn-danger';
                        deactivateBtn.onclick = () => updateAccountStatus(accountName, 'deactivated');
                        actionSection.appendChild(questionDiv);
                        actionSection.appendChild(deactivateBtn);
                    } else {
                        questionDiv.textContent = 'Do you want to activate this account?';
                        const activateBtn = document.createElement('button');
                        activateBtn.innerHTML = '<i class="fas fa-user-check"></i> Activate Account';
                        activateBtn.className = 'btn btn-success';
                        activateBtn.onclick = () => updateAccountStatus(accountName, 'activated');
                        actionSection.appendChild(questionDiv);
                        actionSection.appendChild(activateBtn);
                    }
                    
                    statusSection.classList.add('visible');
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading account status');
                });
        }

        function updateAccountStatus(accountName, status) {
            if (confirm('Are you sure you want to change the account status?')) {
                selectedAccountInput.value = accountName;
                newStatusInput.value = status;
                updateForm.submit();
            }
        }

        function activateNewAccount(accountName) {
            if (confirm('Are you sure you want to activate this new account?')) {
                document.getElementById('newAccountName').value = accountName;
                document.getElementById('activateNewForm').submit();
            }
        }

        function rejectNewAccount(accountName) {
            if (confirm('Are you sure you want to reject this new account? The account will remain deactivated.')) {
                document.getElementById('rejectAccountName').value = accountName;
                document.getElementById('rejectNewForm').submit();
            }
        }
    </script>
</body>
</html>