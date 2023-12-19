<?php
require_once 'vendor/autoload.php'; // Load Composer dependencies

// Load configuration
$config = json_decode(file_get_contents('config.json'), true);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nmapOutput = $_POST['nmapOutput'];

    $mongo = new MongoDB\Client($config['mongodb_url']);
    $db = $mongo->selectDatabase($config['mongodb_db']);
    $ipAddressCollection = $db->selectCollection('ipaddresses');

    // Initialize variables
    $currentIp = '';
    $macAddress = '';
    $openPorts = [];

    // Process NMAP output
    $lines = explode("\n", $nmapOutput);

    foreach ($lines as $line) {
        // Existing NMAP processing logic...
    }

    // Handle the last IP address in the NMAP output
    if ($currentIp && $macAddress) {
        updateOrCreateRecord($ipAddressCollection, $currentIp, $macAddress, $openPorts);
    }

    // Check and process CSV input
    if (isset($_POST['csvInput']) && !empty($_POST['csvInput'])) {
        $csvLines = explode("\n", $_POST['csvInput']);
        foreach ($csvLines as $line) {
            $csvData = explode("\t", $line); // Split by tab character
            if (count($csvData) >= 3) {
                $ip = $csvData[0];
                $mac = $csvData[1];
                $hostName = $csvData[2];
                $description = count($csvData) >= 4 ? $csvData[3] : '';

                // Process each CSV row here
                $csvRecord = [
                    'address' => $ip,
                    'macAddress' => $mac,
                    'hostName' => $hostName,
                    'description' => $description
                ];

                // Use your logic to insert/update this data into the database
                // This could be a modified version of your updateOrCreateRecord function
                updateOrCreateRecordCSV($ipAddressCollection, $csvRecord);
            }
        }
    }

    header('Location: index.php'); // Redirect back to the main page
    exit;
}

// You might need to modify this function to handle CSV data
function updateOrCreateRecordCSV($collection, $data) {
    $existing = $collection->findOne(['address' => $data['address']]);
    if ($existing) {
        // Update existing record
        $collection->updateOne(['_id' => $existing->_id], ['$set' => $data]);
    } else {
        // Insert new record
        $collection->insertOne($data);
    }
}


function updateOrCreateRecord($collection, $ip, $mac, $ports) {
    $existing = $collection->findOne(['address' => $ip]);
    $data = ['address' => $ip, 'macAddress' => $mac, 'openPorts' => $ports];
    if ($existing) {
        // Update existing record
        $collection->updateOne(['_id' => $existing->_id], ['$set' => $data]);
    } else {
        // Insert new record
        $collection->insertOne($data);
    }
}

// HTML layout for import form
?>
<!DOCTYPE html>
<html>
<head>
    <title>Import NMAP Output</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <div class="content">
            <form action="import.php" method="post">
                <textarea name="nmapOutput" rows="20" cols="80" placeholder="Paste NMAP Output Here"></textarea><br>
                <button type="submit">Import NMAP Data</button>
                <br><br> <!-- Spacing between the two textareas -->
                <textarea name="csvInput" rows="10" cols="80" placeholder="Paste CSV Data Here"></textarea><br>
                <button type="submit">Import CSV Data</button>
            </form>

        </div>
    </div>
</body>
</html>
