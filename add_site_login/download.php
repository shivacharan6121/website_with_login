<?php
session_start();
require_once 'auth.php';
requireLogin();
require_once 'db_config.php';

// Fetch data from database
$sql = "SELECT * FROM part ORDER BY Nomenclature";
$result = $conn->query($sql);

$data = array();
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles.css">
    <title>Download Records</title>
    <style>
        /* Your existing CSS styles */
        body {
            font-family: Arial, sans-serif;
            background-color: #151b27;
            margin: 0;
            padding: 0;
            color: white;
        }
        
        .header {
            background-color: none;
            padding: 15px 0;
            display: flex;
            align-items: center;
            justify-content: center;
            
        }
        
        .header h1 {
            margin: 0;
            color: white;
        }
        
        .header-gif {
            height: 50px;
            margin-right: 10px;
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
            margin-bottom: 20px;
        }
        
        .download-message {
            background-color: #1e283a;
            padding: 30px;
            border-radius: 8px;
            margin: 20px 0;
            text-align: center;
        }
        
        .file-path {
            background-color: #151b27;
            padding: 15px;
            border-radius: 4px;
            margin: 20px auto;
            max-width: 600px;
            word-break: break-all;
            color: #9fef00;
            font-weight: bold;
        }
        
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: #9fef00;
            color: black;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            margin-top: 20px;
            border: none;
            cursor: pointer;
            font-size: 16px;
        }
        
        .btn:hover {
            background-color: #8cd600;
        }
        
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: white;
            position: relative;
        }
        
        .alert-success {
            background-color: #9fef00;
            color: black;
        }

        .status-message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
            color: white;
            position: relative;
            text-align: center;
            font-weight: bold;
            font-size: 16px;
        }
        .status-message.success {
            background-color: #28a745;
            color: black;
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
        <h2>Database Export</h2>
        
        <div class="download-message">
            <div class="alert alert-success">
                The database records have been successfully exported to CSV format.
            </div>
            
            <div class="file-path">
                File: connector_records_'.date('Y-m-d').'.csv
            </div>
            <a href="view.php" class="btn">Return to View Page</a>
        </div>
    </div>
    
    <script>
        // Convert data to CSV
        function arrayToCSV(data) {
            const headers = ['Serial No', 'Nomenclature', 'Make', 'Total Qty', 'Used Qty', 'Available Qty'];
            let csv = headers.join(',') + '\n';
            
            data.forEach((row, index) => {
                csv += [
                    index + 1,
                    `"${row.Nomenclature.replace(/"/g, '""')}"`,
                    `"${row.make.replace(/"/g, '""')}"`,
                    row.quantity,
                    row.usedqty,
                    row.availableqty
                ].join(',') + '\n';
            });
            
            return csv;
        }
        
        // Download CSV
        function downloadCSV() {
            const data = <?php echo json_encode($data); ?>;
            const csv = arrayToCSV(data);
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const url = URL.createObjectURL(blob);
            
            // Create download link
            const link = document.createElement('a');
            link.href = url;
            link.download = 'connector_records_<?php echo date('Y-m-d'); ?>.csv';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            
            // Show confirmation
            document.getElementById('statusMessage').className = 'status-message success';
            document.getElementById('statusMessage').textContent = 'File downloaded successfully!';
        }
        
        // Trigger download on page load
        window.onload = downloadCSV;
    </script>
</body>
</html>