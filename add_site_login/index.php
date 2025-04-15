<?php
session_start();
require_once 'auth.php';
requireLogin();
require_once 'db_config.php';

// Set the current form based on the query parameter or session
$current_form = isset($_GET['form']) ? $_GET['form'] : (isset($_SESSION['current_form']) ? $_SESSION['current_form'] : 'add-part');

$show_confirmation = false; // Flag to control confirmation box
$message = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['form_type'])) {
        $form_type = $_POST['form_type'];
        $current_form = $_POST['current_form'];

        // Store form data in session
        $_SESSION['form_type'] = $form_type;
        $_SESSION['current_form'] = $current_form;

        switch ($form_type) {
            case 'add_part':
                $part_no = $conn->real_escape_string($_POST['part_name']);
                $make = $conn->real_escape_string($_POST['make']);
                $quantity = (int)$_POST['conn_count'];

                // Check if part already exists
                $check_sql = "SELECT quantity FROM part WHERE Nomenclature = '$part_no' AND make = '$make'";
                $result = $conn->query($check_sql);

                if ($result->num_rows > 0) {
                    $_SESSION['alert'] = [
                        'type' => 'error',
                        'message' => "Entered part number: <strong>$part_no</strong> from <strong>$make</strong> already exists!"
                    ];
                } else {
                    $_SESSION['Nomenclature'] = $part_no;
                    $_SESSION['make'] = $make;
                    $_SESSION['quantity'] = $quantity;
                    $message = "Are you sure you want to add the part number <strong>$part_no</strong> from <strong>$make</strong> with <strong>$quantity</strong> connectors?";
                    $show_confirmation = true;
                }
                break;

            case 'add_conn':
                $part_no = $conn->real_escape_string($_POST['conn_name']);
                $add_make = $conn->real_escape_string($_POST['add-make']);
                $add_quantity = (int)$_POST['pin_count'];

                // Check if part doesnot exist
                $check_sql = "SELECT quantity FROM part WHERE Nomenclature = '$part_no' AND make = '$add_make'";
                $result = $conn->query($check_sql);

                if ($result->num_rows == 0) {
                    $_SESSION['alert'] = [
                        'type' => 'warning',
                        'message' => "Part number <strong>$part_no</strong> from <strong>$add_make</strong> does not exist! Please add the part first."
                    ];
                }
                if ($result->num_rows > 0) {
                    if ($add_quantity <= 0) {
                        $_SESSION['alert'] = [
                            'type' => 'error',
                            'message' => "Invalid quantity added. Please enter a positive number."
                        ];
                    } 
                    else {
                        $_SESSION['Nomenclature'] = $part_no;
                        $_SESSION['make'] = $add_make;
                        $_SESSION['quantity'] = $add_quantity;
                        $message = "Are you sure you want to add <strong>$add_quantity</strong> connectors to part number <strong>$part_no</strong> from <strong>$add_make</strong>?";
                        $show_confirmation = true;
                    }
                }    
                break;

            case 'required_conn':
                $part_no = $conn->real_escape_string($_POST['part_name']);
                $req_make = $conn->real_escape_string($_POST['req-make']);
                $remove_quantity = (int)$_POST['req_conn'];
                $issue_name = $conn->real_escape_string($_POST['issue_name']);
                $designation = $conn->real_escape_string($_POST['add-desg']);

                // Check if part doesnot exist
                $check_sql = "SELECT quantity, availableqty FROM part WHERE Nomenclature = '$part_no' AND make = '$req_make'";
                $result = $conn->query($check_sql);

                if ($result->num_rows == 0) {
                    $_SESSION['alert'] = [
                        'type' => 'warning',
                        'message' => "Part number <strong>$part_no</strong> from <strong>$req_make</strong> does not exist! Please add the part first."
                    ];
                }
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $current_quantity = $row['quantity'];
                    $current_availableqty = $row['availableqty'];
                    $current_usedqty = $row['usedqty'];
                    // Validate quantities
                    if ($remove_quantity <= 0) {
                        $_SESSION['alert'] = [
                            'type' => 'error',
                            'message' => "Invalid quantity requested. Please enter a positive number."
                        ];
                    }
                    elseif ($remove_quantity > $current_availableqty) {
                        $_SESSION['alert'] = [
                            'type' => 'warning',
                            'message' => "Cannot issue <strong>$remove_quantity</strong> connectors of part number <strong>$part_no</strong> from <strong>$req_make</strong>.<br>"
                          . "Available quantity: <strong>$current_availableqty</strong><br>"
                          . "Requested quantity exceeds available stock."
                        ];
                    } else {
                        $_SESSION['Nomenclature'] = $part_no;
                        $_SESSION['make'] = $req_make;
                        $_SESSION['remove_quantity'] = $remove_quantity;
                        $_SESSION['issue_name'] = $issue_name;
                        $_SESSION['designation'] = $designation;
                        $message = "Are you sure you want to issue <strong>$remove_quantity</strong> connectors of part number <strong>$part_no</strong> from <strong>$req_make</strong> to <strong>$issue_name ($designation)</strong>?";
                        $show_confirmation = true;
                    }
                }
                break;
        }
    }

    if (isset($_POST['confirm'])) {
        // Perform database operations after confirmation
        $form_type = $_SESSION['form_type'];
        $part_no = $_SESSION['Nomenclature'];
        $make = $_SESSION['make'];
        $issue_name = $_SESSION['issue_name'];
        $designation = $_SESSION['designation'];
        $remove_quantity = $_SESSION['remove_quantity'];

        switch ($form_type) {
            case 'add_part':
                $quantity = $_SESSION['quantity'];
                $usedqty = 0;
                $availableqty = $quantity;
                $sql = "INSERT INTO part (Nomenclature, make, quantity, usedqty, availableqty) VALUES ('$part_no', '$make', $quantity, $usedqty, $availableqty)";
                if ($conn->query($sql) === TRUE) {
                    $_SESSION['alert'] = [
                        'type' => 'success',
                        'message' => "New part number <strong>$part_no</strong> added successfully from <strong>$make</strong> with <strong>$quantity</strong> connectors!"
                    ];
                } else {
                    $_SESSION['alert'] = [
                        'type' => 'error',
                        'message' => "Error adding part: " . $conn->error
                    ];
                }
                break;

            case 'add_conn':
                $add_quantity = $_SESSION['quantity'];
                $check_sql = "SELECT quantity, availableqty, usedqty FROM part WHERE Nomenclature = '$part_no' AND make = '$make'";
                $result = $conn->query($check_sql);
                
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $current_quantity = $row['quantity'];
                    $current_availableqty = $row['availableqty'];
                    $new_quantity = $current_quantity + $add_quantity;
                    $availablequty = $current_availableqty + $add_quantity;
                    $usedqty = $row['usedqty'];
                    $sql = "UPDATE part SET quantity = $new_quantity, availableqty = $availablequty WHERE Nomenclature = '$part_no' AND make = '$make'";
                    if ($conn->query($sql) === TRUE) {
                        $_SESSION['alert'] = [
                            'type' => 'success',
                            'message' => "Added <strong>$add_quantity</strong> connectors to part number: <strong>$part_no</strong> from <strong>$make</strong><br>Total quantity: <strong>$new_quantity</strong><br>Used quantity: <strong>$usedqty</strong><br>Available quantity: <strong>$availablequty</strong>"
                        ];
                    } else {
                        $_SESSION['alert'] = [
                            'type' => 'error',
                            'message' => "Error adding connectors: " . $conn->error
                        ];
                    }
                } 
                break;
            

            case 'required_conn':
                $remove_quantity = $_SESSION['remove_quantity'];
                $issue_name = $_SESSION['issue_name'];
                $designation = $_SESSION['designation'];
                $check_sql = "SELECT quantity, availableqty, usedqty FROM part WHERE Nomenclature = '$part_no' AND make = '$make'";
                $result = $conn->query($check_sql);
                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    $current_quantity = $row['quantity'];
                    $current_availableqty = $row['availableqty'];
                    $current_usedqty = $row['usedqty'];
                    $available_quantity = $current_availableqty - $remove_quantity;
                    $usedqty = $current_usedqty + $remove_quantity;
                    $total_quantity = $current_quantity;

                    // Double-check we won't get negative values
                    if ($available_quantity >= 0 && $usedqty <= $total_quantity) {
                    $sql = "UPDATE part SET availableqty = $available_quantity, usedqty = $usedqty WHERE Nomenclature = '$part_no' AND make = '$make'";
                    
                    if ($conn->query($sql) === TRUE) {
                        // Insert into issue table
                        $issue_sql = "INSERT INTO issue (Nomenclature, make, issuedqty, person_name, designation, timestamp) 
                        VALUES ('$part_no', '$make', $remove_quantity, '$issue_name', '$designation', NOW())";

                            if ($conn->query($issue_sql) === TRUE) {
                            $_SESSION['alert'] = [
                                'type' => 'success',
                                'message' => "Issued <strong>$remove_quantity</strong> connectors of part number: <strong>$part_no</strong> from <strong>$make</strong><br>"
                                      . "Total quantity: <strong>$total_quantity</strong><br>"
                                      . "Used quantity: <strong>$usedqty</strong><br>"
                                      . "Available quantity: <strong>$available_quantity</strong><br>"
                                      . "Issued to: <strong>$issue_name ($designation)</strong>"
                            ];
                        } else {
                            $_SESSION['alert'] = [
                                'type' => 'error',
                                'message' => "Error removing connectors: " . $conn->error
                            ];
                        }
                    } else {
                        $_SESSION['alert'] = [
                            'type' => 'error',
                            'message' => "Error removing connectors: " . $conn->error
                        ];
                    }
                }else {
                    $_SESSION['alert'] = [
                        'type' => 'error',
                        'message' => "System error: Invalid quantity calculation detected."
                    ];
                }
            }
                break;
        }

        // Clear session data
        unset($_SESSION['form_type'], $_SESSION['Nomenclature'], $_SESSION['make'], $_SESSION['quantity'], $_SESSION['add_quantity'], $_SESSION['remove_quantity'], $_SESSION['issue_name'], $_SESSION['designation']);

        // Redirect to the current form
        header("Location: index.php?form=$current_form");
        exit();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>StoresManagement System</title>
    <link rel="stylesheet" href="css/styles.css">
    <script src="javascript/script.js"></script>
    <style>
        /* Confirmation Box Styles */
        .confirmation-box {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            width: 800px;
            max-width: 95%;
            padding: 30px;
            transform: translate(-50%, -50%);
            background-color: #1e283a;
            color: white;
            border-radius: 8px;
            box-shadow: 0 0 20px rgba(0,0,0,0.5);
            text-align: center;
            z-index: 1001;
            border: none;
        }

        .confirmation-box p {
            margin-bottom: 25px;
            font-size: 1rem;
            line-height: 1.6;
            color: #ffffff;
        }

        .confirmation-box-gif {
            width: 60px;
            height: 60px;
            margin: 0 auto 20px;
            display: block;
        }

        .confirmation-box-buttons {
            display: flex;
            justify-content: center;
            gap: 70px;
            margin-top: 5px;
        }

        .confirmation-box button {
            padding: 6px 15px;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 15px;
        }

        .confirmation-box button.confirm {
            background-color: green;
            color: white;
        }

        .confirmation-box button.confirm:hover {
            background-color: #228B22;
            transform: translateY(-2px);
        }

        .confirmation-box button.cancel {
            background-color: #f44336;
            color: white;
        }

        .confirmation-box button.cancel:hover {
            background-color: #e53935;
            transform: translateY(-2px);
        }

        /* Alert Box Styles */
        .alert-box {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            width: 800px;
            max-width: 95%;
            padding: 10px;
            transform: translate(-50%, -50%);
            background-color: #1e283a;
            color: white;
            border-radius: 8px;
            box-shadow: 0 0 25px rgba(0,0,0,0.3);
            text-align: center;
            z-index: 1001;
            border-top: 5px solid;
        }

        .alert-box p {
            margin-bottom: 15px;
            font-size: 1rem;
            line-height: 1.6;
        }

        .alert-gif {
            width: 60px;
            height: 60px;
            display: block;
            margin: 0 auto 20px;
        }

        .alert-ok-btn {
            padding: 12px 40px;
            background-color: #2196F3;
            color: white;
            border: none;
            border-radius: 6px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 1rem;
            margin-top: 5px;
        }

        .alert-ok-btn:hover {
            background-color: #0d8bf2;
            transform: translateY(-2px);
        }

        /* Alert Type Specific Styles */
        .alert-success {
            border-color: #4CAF50;
        }

        .alert-error {
            border-color: #f44336;
        }

        .alert-warning {
            border-color: #ff9800;
        }

        /* Overlay Style */
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(3px);
            z-index: 1000;
        }
    </style>

</head>
<body>
    <div class="background-container">
        <div class="background-image"></div>
    </div>
    <div class="header">
        <img src="images/aircraft.gif" alt="Logo" class="header-gif">&nbsp;
        <h1>EO-SAAW Stores Management System</h1>
    </div>

    <div class="menu-bar">
        <a href="index.php"><img src="images/icons8-home-30.png" class="menu-icon" alt="Home">Home</a>
        <a href="download.php"><img src="images/icons8-download-24.png" class="menu-icon" alt="Download">Download</a>
        <a href="view.php"><img src="images/icons8-view-30.png" class="menu-icon" alt="View">View</a>
        <a href="issue.php"><img src="images/icons8-view-30.png" class="menu-icon" alt="Issue">Issue</a>
        <a href="#"><img src="images/icons8-user-30.png" class="menu-icon" alt="Login"><?php echo htmlspecialchars(getUsername()); ?></a>
        <a href="logout.php">Logout</a>
    </div>

    <?php if (isset($_SESSION['alert'])): ?>
    <div class="overlay" style="display: block;"></div>
    <div class="alert-box alert-<?php echo $_SESSION['alert']['type']; ?>" style="display: block;">
        <?php if ($_SESSION['alert']['type'] == 'success'): ?>
            <img src="images/verified.gif" alt="Success" class="alert-gif">
        <?php elseif ($_SESSION['alert']['type'] == 'error'): ?>
            <img src="images/alarm (1).gif" alt="Error" class="alert-gif">
        <?php elseif ($_SESSION['alert']['type'] == 'warning'): ?>
            <img src="images/alarm.gif" alt="Warning" class="alert-gif">
        <?php endif; ?>
        <p><?php echo $_SESSION['alert']['message']; ?></p>
        <button type="button" class="alert-ok-btn">OK</button>
    </div>
    <?php unset($_SESSION['alert']); endif; ?>
    <div class="container">
        <div class="toggle-buttons">
            <button class="toggle-btn <?php echo $current_form == 'add-part' ? 'active' : ''; ?>" onclick="showForm('add-part')">Add New Part</button>
            <button class="toggle-btn <?php echo $current_form == 'add-conn' ? 'active' : ''; ?>" onclick="showForm('add-conn')">Add Connectors</button>
            <button class="toggle-btn <?php echo $current_form == 'required-conn' ? 'active' : ''; ?>" onclick="showForm('required-conn')">Connectors Required</button>
        </div>

        <!-- Add Part Form -->
        <form id="add-part" class="form-section <?php echo $current_form == 'add-part' ? 'active' : ''; ?>" action="index.php" method="POST">
            <input type="hidden" name="form_type" value="add_part">
            <input type="hidden" name="current_form" value="add-part">
            <div class="form-group">
                <label for="part-name">Enter New Part No:</label>
                <input type="text" id="part-name" name="part_name" placeholder="Enter new part number" required>
            </div>
            <div class="form-group">
                <label for="make">Make:</label>
                <select id="make" name="make" required>
                    <option value="">Select Make</option>
                    <option value="Amphenol">Amphenol</option>
                    <option value="Glenair">Glenair</option>
                    <option value="SOURIAU">Souriau</option>
                    <option value="ITT Cannon">ITT Cannon</option>
                </select>
            </div>
            <div class="form-group">
                <label for="part-conn">Enter No. of Conn:</label>
                <input type="number" id="part-conn" name="conn_count" placeholder="Enter number of connectors" required>
            </div>
            <button type="submit" class="submit-btn">Add New Part</button>
        </form>

        <!-- Add Connectors Form -->
        <form id="add-conn" class="form-section <?php echo $current_form == 'add-conn' ? 'active' : ''; ?>" action="index.php" method="POST">
            <input type="hidden" name="form_type" value="add_conn">
            <input type="hidden" name="current_form" value="add-conn">
            <div class="form-group">
                <label for="conn-name">Enter Part No:</label>
                <input type="text" id="conn-name" name="conn_name" placeholder="Enter existing part number" required>
            </div>
            <div class="form-group">
                <label for="add-make">Make:</label>
                <select id="add-make" name="add-make" required>
                    <option value="">Select Make</option>
                    <option value="Amphenol">Amphenol</option>
                    <option value="Glenair">Glenair</option>
                    <option value="SOURIAU">Souriau</option>
                    <option value="ITT Cannon">ITT Cannon</option>
                </select>
            </div>
            <div class="form-group">
                <label for="pin-count">Enter No. of Additional Conn:</label>
                <input type="number" id="pin-count" name="pin_count" placeholder="Enter number of additional connectors" required>
            </div>
            <button type="submit" class="submit-btn">Add Connectors</button>
        </form>

        <!-- Required Connectors Form -->
        <form id="required-conn" class="form-section <?php echo $current_form == 'required-conn' ? 'active' : ''; ?>" action="index.php" method="POST">
            <input type="hidden" name="form_type" value="required_conn">
            <input type="hidden" name="current_form" value="required-conn">
            <div class="form-group">
                <label for="req-part">Enter Part No:</label>
                <input type="text" id="req-part" name="part_name" placeholder="Enter existing part number" required>
            </div>
            <div class="form-group">
                <label for="req-make">Make:</label>
                <select id="req-make" name="req-make" required>
                    <option value="">Select Make</option>
                    <option value="Amphenol">Amphenol</option>
                    <option value="Glenair">Glenair</option>
                    <option value="SOURIAU">Souriau</option>
                    <option value="ITT Cannon">ITT Cannon</option>
                </select>
            </div>
            <div class="form-group">
                <label for="req-conn">Enter No. of Required Conn:</label>
                <input type="number" id="req-conn" name="req_conn" placeholder="Enter number of required connectors" required>
            </div>
            <div class="form-group">
                <label for="issue_name">Issue To:</label>
                <input type="text" id="issue_name" name="issue_name" placeholder="Enter name of person to issue connectors" required>
            </div>
            <div class="form-group">
                <label for="add-desg">Designation:</label>
                <select id="add-desg" name="add-desg" required>
                    <option value="">Select Designation</option>
                    <option value="Contract Engineer">Contract Engineer</option>
                    <option value="Scientist">Scientist</option>
                    <option value="Staff">Staff</option>
                </select>
            </div>
            <button type="submit" class="submit-btn">Connectors Required</button>
        </form>
    </div>

    <div class="overlay" style="display: <?php echo $show_confirmation ? 'block' : 'none'; ?>;"></div>
    <div class="confirmation-box" style="display: <?php echo $show_confirmation ? 'block' : 'none'; ?>;">
        <p><?php echo $message; ?></p>
        <form action="index.php" method="POST">
            <button type="submit" name="confirm" class="confirm">YES</button>
            <button type="button" name="cancel" class="cancel" onclick="window.location.href='index.php?form=<?php echo $current_form; ?>'">NO</button>
        </form>
    </div>
    <script>
        // Handle OK button click for alert boxes
        document.addEventListener('DOMContentLoaded', function() {
            const okButton = document.querySelector('.alert-ok-btn');
            if (okButton) {
                okButton.addEventListener('click', function() {
                    document.querySelector('.alert-box').style.display = 'none';
                    document.querySelector('.overlay').style.display = 'none';
                });
            }
        });
    </script>

    <script src="script.js"></script>
</body>
</html>