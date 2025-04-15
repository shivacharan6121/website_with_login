<?php
session_start();
require_once 'auth.php';
requireLogin();
require_once 'db_config.php';

// Handle search functionality
$search_query = '';
$searching = false; // Flag to check if searching
$total_records = 0;

if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_query = $conn->real_escape_string($_GET['search']);
    $searching = true;
}

// Fetch matching parts when searching, otherwise return an empty result
$sql = "SELECT * FROM part WHERE Nomenclature LIKE '$search_query%'";

$result = $conn->query($sql);

if (!$result) {
    die("Query failed: " . $conn->error);
}

// Get total number of records
$total_records = $result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles.css">
    <title>View Parts</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #151b27;
            margin: 0;
            padding: 0;
        }

        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background-color: #1a2332;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: white;
        }

        
        .search-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-bar {
            display: flex;
        }

        .search-bar input[type="text"] {
            width: 300px;
            padding: 10px;
            border-radius: 4px;
            outline: none;
            font-size: 16px;
            font-weight: bolder;
            background-color: #1e283a;
            color: white;
        }

        .search-bar button {
            padding: 10px 20px;
            background-color: #9fef00;
            color: black;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bolder;
            font-size: 16px;
            margin-left: 10px;
        }

        .search-bar button:hover {
            background-color: #9fef00;
        }

        .record-count {
            color: #9fef00;
            font-weight: bold;
            font-size: 16px;
            padding: 10px;
            background-color: #1e283a;
            border-radius: 4px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table th, table td {
            padding: 12px;
            text-align: left;
            border-collapse: collapse;
            color: white;
        }

        table th {
            background-color: #9fef00;
            color: black;
        }

        table tr:hover {
            background-color: #1e283a;
        }

        .no-results {
            text-align: center;
            color: #dc3545;
            font-size: 18px;
            margin-top: 20px;
        }
    </style>
</head>
<body>
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
    <div class="container">
        <h2>LIST OF CONNECTORS: EO-SAAW/RCI</h2><br>

        <!-- Search Bar and Record Count -->
        <div class="search-container">
            <div class="search-bar">
                <form action="view.php" method="GET">
                    <input type="text" name="search" placeholder="Search by Part No" value="<?php echo htmlspecialchars($search_query); ?>">
                    <button type="submit">Search</button>
                </form>
            </div>
            <div class="record-count">
                <?php 
                if ($searching) {
                    echo "Found: $total_records record".($total_records != 1 ? 's' : '');
                } else {
                    echo "Total: $total_records record".($total_records != 1 ? 's' : '');
                }
                ?>
            </div>
        </div>

        <!-- Display Records -->
        <?php if ($result->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Serial No</th>
                        <th>Nomenclature</th>
                        <th>Make</th>
                        <th>Total Qty</th>
                        <th>Used Qty</th>
                        <th>Available Qty</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $serial_no = 1;
                    while ($row = $result->fetch_assoc()): ?>
                        <tr>
                            <td><?php echo $serial_no++; ?></td>
                            <td><?php echo htmlspecialchars($row['Nomenclature']); ?></td>
                            <td><?php echo htmlspecialchars($row['make']); ?></td>
                            <td><?php echo htmlspecialchars($row['quantity']); ?></td>
                            <td><?php echo htmlspecialchars($row['usedqty']); ?></td>
                            <td><?php echo htmlspecialchars($row['availableqty']); ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-results">No matching records found.</div>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
$conn->close();
?>
