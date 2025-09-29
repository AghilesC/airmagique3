<?php 
require('tcpdf.php'); 
include "../../config.php"; 
include_once "../logincheck.php";  

// Handle AJAX delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['draft_id'])) {
    header('Content-Type: application/json');
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit;
    }

    $draftId = intval($_POST['draft_id']);
    $userId = $_SESSION['user_id'];

    $connexion = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);
    if (!$connexion) {
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }

    try {
        // Get user's idacount for security check
        $checkUser = "SELECT idacount FROM account WHERE LOWER(username) = LOWER(?)";
        $stmt = mysqli_prepare($connexion, $checkUser);
        mysqli_stmt_bind_param($stmt, "s", $userId);
        mysqli_stmt_execute($stmt);
        $userResult = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($userResult) === 0) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        $userRow = $userResult->fetch_assoc();
        $idacount = $userRow['idacount'];

        // Verify that the draft belongs to this user before deleting
        $verifyQuery = "SELECT id FROM draft WHERE id = ? AND idacount = ?";
        $verifyStmt = mysqli_prepare($connexion, $verifyQuery);
        mysqli_stmt_bind_param($verifyStmt, "ii", $draftId, $idacount);
        mysqli_stmt_execute($verifyStmt);
        $verifyResult = mysqli_stmt_get_result($verifyStmt);

        if (mysqli_num_rows($verifyResult) === 0) {
            echo json_encode(['success' => false, 'message' => 'Draft not found or access denied']);
            exit;
        }

        // Delete the draft
        $deleteQuery = "DELETE FROM draft WHERE id = ? AND idacount = ?";
        $deleteStmt = mysqli_prepare($connexion, $deleteQuery);
        mysqli_stmt_bind_param($deleteStmt, "ii", $draftId, $idacount);
        
        if (mysqli_stmt_execute($deleteStmt)) {
            if (mysqli_stmt_affected_rows($deleteStmt) > 0) {
                echo json_encode(['success' => true, 'message' => 'Draft deleted successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'No draft was deleted']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Error executing delete query']);
        }

        // Close statements
        mysqli_stmt_close($stmt);
        mysqli_stmt_close($verifyStmt);
        mysqli_stmt_close($deleteStmt);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    } finally {
        mysqli_close($connexion);
    }
    exit;
}

$connexion = mysqli_connect($dbhost, $dbuser, $dbpwd, $dbname);  

if (!$connexion) {     
    die("Database connection failed: " . mysqli_connect_error()); 
}  

// Retrieve user ID
$userId = $_SESSION['user_id']; // Ensure session is active  

// Retrieve idacount using username
$checkUser = "SELECT idacount FROM account WHERE LOWER(username) = LOWER('$userId')"; 
$userResult = $connexion->query($checkUser);  

if (!$userResult || $userResult->num_rows === 0) {     
    die("Error: No associated drafts found for this user."); 
}  

$row = $userResult->fetch_assoc(); 
$idacount = $row['idacount']; // Store retrieved idacount  

// Fetch drafts for this user
$query = "SELECT id, glpi_ticket_id FROM draft WHERE idacount = $idacount"; 
$result = mysqli_query($connexion, $query); 
if (!$result) {     
    die("Error retrieving data: " . mysqli_error($connexion)); 
} 
?> 

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Draft Selection - inv.ctiai.com</title>
    
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

        .main-section {
            background: #ffffff;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15), 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e9ecef;
            margin-bottom: 20px;
        }

        .section-title {
            color: #2c3e50;
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #eb2226;
            font-size: 24px;
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

        .btn-secondary {
            background: #6c757d;
            color: #ffffff;
        }

        .btn-secondary:hover {
            background: #5a6268;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
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

        .btn-group {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .no-drafts {
            text-align: center;
            color: #6c757d;
            font-style: italic;
            padding: 40px 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .no-drafts i {
            font-size: 48px;
            margin-bottom: 15px;
            color: #ced4da;
        }

        .result-section {
            display: none;
            margin-top: 20px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
            text-align: center;
        }

        .draft-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .draft-actions .btn {
            min-width: 150px;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            body {
                padding: 15px;
            }

            .container {
                padding: 10px;
            }

            .main-section {
                padding: 20px;
            }

            .btn-group {
                flex-direction: column;
            }

            .btn {
                width: 100%;
                margin: 5px 0;
            }

            .page-title {
                font-size: 24px;
            }
        }

        @media (max-width: 480px) {
            .main-section {
                padding: 15px;
            }

            .page-title {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <img src="../image/airmagique_logo.png" alt="Logo" class="logo">
            <h1 class="page-title">Draft Selection</h1>
        </div>

        <a href="../techmenu/techmenu.php" class="back-arrow" title="Back to menu">Back to Menu</a>

        <div class="main-section">
            <h2 class="section-title">
                <i class="fas fa-file-alt"></i>
                Select a Draft
            </h2>

            <?php if (mysqli_num_rows($result) > 0): ?>
                <form id="procForm">
                    <div class="form-group">
                        <label for="draft">Choose a draft to continue:</label>
                        <select id="draft" required>
                            <option value="">-- Select a draft --</option>
                            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                <option value="<?= htmlspecialchars($row['id'], ENT_QUOTES) ?>">
                                    Draft <?= htmlspecialchars($row['id'] . " - GLPI #" . $row['glpi_ticket_id'], ENT_QUOTES) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                </form>

                <!-- Result Container -->
                <div class="result-section" id="result">
                    <p>What would you like to do with this draft?</p>
                    <div class="draft-actions">
                        <button type="button" class="btn btn-primary" id="editBtn">
                            <i class="fas fa-edit"></i>
                            Edit Draft
                        </button>
                        <button type="button" class="btn btn-danger" id="deleteBtn">
                            <i class="fas fa-trash"></i>
                            Delete Draft
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="no-drafts">
                    <i class="fas fa-file-times"></i>
                    <h3>No Drafts Found</h3>
                    <p>You don't have any saved drafts yet.</p>
                    <p>Start creating a new work order to save drafts.</p>
                </div>
            <?php endif; ?>

            <div class="btn-group">
                <a href="../TCPDF/confirmationpdf.php" class="btn btn-secondary">
                    <i class="fas fa-plus"></i>
                    New Work Order
                </a>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const draftDropdown = document.getElementById('draft');
            const resultSection = document.getElementById('result');
            const editBtn = document.getElementById('editBtn');
            const deleteBtn = document.getElementById('deleteBtn');
            let selectedDraftId = null;

            // Handle draft selection
            if (draftDropdown) {
                draftDropdown.addEventListener('change', function () {
                    selectedDraftId = this.value;
                    if (selectedDraftId) {
                        // Show result section with action buttons
                        resultSection.style.display = 'block';
                    } else {
                        resultSection.style.display = 'none';
                    }
                });
            }

            // Handle edit button click
            if (editBtn) {
                editBtn.addEventListener('click', function() {
                    if (selectedDraftId) {
                        window.location.href = `confirmationpdf2.php?draft_id=${selectedDraftId}`;
                    }
                });
            }

            // Handle delete button click
            if (deleteBtn) {
                deleteBtn.addEventListener('click', function() {
                    if (selectedDraftId) {
                        if (confirm('Are you sure you want to delete this draft? This action cannot be undone.')) {
                            // Send delete request to the same file
                            fetch(window.location.pathname, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                },
                                body: `draft_id=${selectedDraftId}`
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    alert('Draft deleted successfully!');
                                    // Remove the option from dropdown
                                    const optionToRemove = draftDropdown.querySelector(`option[value="${selectedDraftId}"]`);
                                    if (optionToRemove) {
                                        optionToRemove.remove();
                                    }
                                    // Reset dropdown and hide result section
                                    draftDropdown.value = '';
                                    resultSection.style.display = 'none';
                                    selectedDraftId = null;
                                    
                                    // Check if no more drafts
                                    if (draftDropdown.options.length <= 1) {
                                        location.reload(); // Reload to show "no drafts" message
                                    }
                                } else {
                                    alert('Error deleting draft: ' + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('An error occurred while deleting the draft.');
                            });
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>

<?php 
// Close database connection
mysqli_close($connexion); 
?>