<?php
require_once 'vendor/autoload.php'; // Load Composer dependencies

// Load configuration
$config = json_decode(file_get_contents('config.json'), true);

// MongoDB setup
$mongo = new MongoDB\Client($config['mongodb_url']);
$db = $mongo->selectDatabase($config['mongodb_db']);
$ipAddressCollection = $db->selectCollection('ipaddresses');

// Function to clean up old scans
function cleanupOldScans($directory, $excludeFile) {
    if ($handle = opendir($directory)) {
        while (false !== ($file = readdir($handle))) {
            if ($file !== $excludeFile && $file !== "." && $file !== "..") {
                $filePath = $directory . '/' . $file;
                if (is_file($filePath)) {
                    unlink($filePath); // Delete the file
                }
            }
        }
        closedir($handle);
    }
}

// Find the latest NMAP output file
$scanDir = 'scans/';
$scanFiles = glob($scanDir . 'output_*.txt');
if (empty($scanFiles)) {
    die('No scan files found.');
}
usort($scanFiles, function($a, $b) {
    return filemtime($b) - filemtime($a);
});
$latestScanFile = $scanFiles[0];

// Read and process the latest NMAP output
$nmapOutput = file_get_contents($latestScanFile);
$lines = explode("\n", $nmapOutput);
$currentIp = '';
$macAddress = '';
$openPorts = [];

foreach ($lines as $line) {
    echo "Processing line: $line\n<br>";

    if (preg_match('/Nmap scan report for ([\w.-]+)(?: \(([\d.]+)\))?/', $line, $matches)) {
        if ($currentIp) {
            echo "Updating/Creating record for IP: $currentIp\n<br>";
            updateOrCreateRecord($ipAddressCollection, $currentIp, $macAddress, $openPorts);
            $openPorts = [];
            $macAddress = ''; // Reset MAC address after updating/creating the record
        }
        $currentIp = isset($matches[2]) ? $matches[2] : $matches[1];
    }

    if (preg_match('/MAC Address: ([\w:]+)/', $line, $macMatch)) {
        echo "MAC Address found: " . $macMatch[1] . "\n<br>";
        $macAddress = $macMatch[1];
    }

    if (preg_match('/(\d+)\/tcp\s+open\s+(.*)/', $line, $portMatch)) {
        $openPorts[] = ['port' => $portMatch[1], 'service' => $portMatch[2]];
    }
}

// Handle the last IP address in the output
if ($currentIp) {
    echo "Final update/creation for IP: $currentIp, $macAddress\n<br>";
    updateOrCreateRecord($ipAddressCollection, $currentIp, $macAddress, $openPorts);
}

// Clean up old scans but retain the latest one
cleanupOldScans($scanDir, basename($latestScanFile));

function updateOrCreateRecord($collection, $ip, $mac, $ports) {
    $currentDate = new MongoDB\BSON\UTCDateTime(); // Current date and time
    $existing = $collection->findOne(['address' => $ip]);

    if ($existing) {
        echo "Updating record for IP: $ip\n<br>";
        $updateData = ['lastSeen' => $currentDate, 'openPorts' => $ports];

        // Update MAC address only if it's available
        if ($mac) {
            $updateData['macAddress'] = $mac;
        }

        $collection->updateOne(['_id' => $existing->_id], ['$set' => $updateData]);
    } else {
        echo "Creating new record for IP: $ip\n<br>";
        $data = [
            'address' => $ip, 
            'openPorts' => $ports, 
            'createDate' => $currentDate, 
            'lastSeen' => $currentDate
        ];

        // Add MAC address to the new record only if it's available
        if ($mac) {
            $data['macAddress'] = $mac;
        }

        $collection->insertOne($data);
    }
}

echo "Auto-import completed.\n";
?>
