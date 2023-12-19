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
        // Match hostname and IP address
        if (preg_match('/Nmap scan report for ([\w.-]+)(?: \(([\d.]+)\))?/', $line, $matches)) {
            $fullHostName = isset($matches[2]) ? $matches[1] : null;
            $ipAddress = isset($matches[2]) ? $matches[2] : $matches[1];
    
            // Extract hostname up to the first dot
            $hostName = explode('.', $fullHostName)[0];
    
            // Check if the IP address already exists
            $existing = $ipAddressCollection->findOne(['address' => $ipAddress]);
            if ($existing) {
                // Update existing record
                $updateData = ['address' => $ipAddress, 'hostName' => $hostName];
                $ipAddressCollection->updateOne(['_id' => $existing->_id], ['$set' => $updateData]);
            } else {
                // Insert new record
                $newData = ['address' => $ipAddress, 'hostName' => $hostName];
                $ipAddressCollection->insertOne($newData);
            }
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

    header('Location: index.php'); // Redirect back to the main page
    exit;
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
            <h1>Import NMAP Output</h1>
            <form action="import.php" method="post">
                <textarea name="nmapOutput" rows="20" cols="80" placeholder="Paste NMAP Output Here"></textarea><br>
                <button type="submit">Import</button>
            </form>
        </div>
    </div>
</body>
</html>
