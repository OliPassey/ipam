<?php
require_once 'vendor/autoload.php'; // Load Composer dependencies

// Load configuration
$config = json_decode(file_get_contents('config.json'), true);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mongo = new MongoDB\Client($config['mongodb_url']);
    $db = $mongo->selectDatabase($config['mongodb_db']);
    $ipAddressCollection = $db->selectCollection('ipaddresses');

    // Check and process NMAP output
    if (isset($_POST['nmapOutput']) && !empty($_POST['nmapOutput'])) {
        $nmapOutput = $_POST['nmapOutput'];

        // Initialize variables
        $currentIp = '';
        $macAddress = '';
        $openPorts = [];

        // Process NMAP output
        $lines = explode("\n", $nmapOutput);

        foreach ($lines as $line) {
            // Match IP address and possible hostname
            if (preg_match('/Nmap scan report for ([\w.-]+)(?: \(([\d.]+)\))?/', $line, $matches)) {
                if ($currentIp && $macAddress) { // Update/Create record for the previous IP before starting a new one
                    updateOrCreateRecord($ipAddressCollection, $currentIp, $macAddress, $openPorts);
                    $macAddress = ''; // Reset MAC address for new record
                    $openPorts = [];  // Reset open ports for new record
                }

                $currentIp = isset($matches[2]) ? $matches[2] : $matches[1];
                $fullHostName = isset($matches[2]) ? $matches[1] : null;
                $hostName = explode('.', $fullHostName)[0];
            }

            // Match MAC address
            if (preg_match('/MAC Address: ([\w:]+)/', $line, $macMatch)) {
                $macAddress = $macMatch[1];
            }

            // Match open ports
            if (preg_match('/(\d+)\/tcp\s+open\s+(.*)/', $line, $portMatch)) {
                $openPorts[] = ['port' => $portMatch[1], 'service' => $portMatch[2]];
            }
        }

        // Handle the last IP address in the output
        if ($currentIp && $macAddress) {
            updateOrCreateRecord($ipAddressCollection, $currentIp, $macAddress, $openPorts);
        }
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
                updateOrCreateRecordCSV($ipAddressCollection, $csvRecord);
            }
        }
    }

    header('Location: index.php'); // Redirect back to the main page
    exit;
}


function updateOrCreateRecord($collection, $ip, $mac, $ports) {
    $currentDate = new MongoDB\BSON\UTCDateTime(); // Current date and time
    $existing = $collection->findOne(['address' => $ip]);

    if ($existing) {
        // Update existing record
        $updateData = ['lastSeen' => $currentDate, 'macAddress' => $mac, 'openPorts' => $ports];
        $collection->updateOne(['_id' => $existing->_id], ['$set' => $updateData]);
    } else {
        // Insert new record
        $data = ['address' => $ip, 'macAddress' => $mac, 'openPorts' => $ports, 'createDate' => $currentDate, 'lastSeen' => $currentDate];
        $collection->insertOne($data);
    }
}

function updateOrCreateRecordCSV($collection, $data) {
    $currentDate = new MongoDB\BSON\UTCDateTime(); // Current date and time
    $existing = $collection->findOne(['address' => $data['address']]);

    if ($existing) {
        // Update existing record
        $data['lastSeen'] = $currentDate;
        $collection->updateOne(['_id' => $existing->_id], ['$set' => $data]);
    } else {
        // Insert new record
        $data['createDate'] = $currentDate;
        $data['lastSeen'] = $currentDate;
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
            <!-- Form for NMAP Output -->
            <form action="import.php" method="post">
                <textarea name="nmapOutput" rows="20" cols="80" placeholder="Paste NMAP Output Here"></textarea><br>
                <button type="submit" name="submitNmap">Import NMAP Data</button>
            </form>
            <br><br> <!-- Spacing between the two forms -->

            <!-- Form for CSV Input -->
            <form action="import.php" method="post">
                <textarea name="csvInput" rows="10" cols="80" placeholder="Paste CSV Data Here"></textarea><br>
                <button type="submit" name="submitCsv">Import CSV Data</button>
            </form>
        </div>
    </div>
</body>
</html>

