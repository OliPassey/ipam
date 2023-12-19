<?php
require_once 'vendor/autoload.php'; // Load Composer dependencies

// func to calculate unused addresses within known subnets
function calculateIPRange($cidr) {
    list($baseIP, $netmask) = explode('/', $cidr, 2);
    $netmask = ~(pow(2, (32 - $netmask)) - 1);
    $ipStart = long2ip(ip2long($baseIP) & $netmask);
    $ipEnd = long2ip(ip2long($ipStart) + ~$netmask);
    return [$ipStart, $ipEnd];
}

// convert IPs into long format for sorting in asc order in main list
function ipToLong($ip) {
    return sprintf('%u', ip2long($ip));
}

// does an IP exist in a cidr block function
function ip_in_subnet($ip, $cidr) {
    list($subnet, $mask) = explode('/', $cidr);
    $ip = ip2long($ip);
    $subnet = ip2long($subnet);
    $mask = -1 << (32 - $mask);
    return ($ip & $mask) == $subnet;
}

// set a default colour
$defaultColor = '#ffffff';
$color = '#222222';

// Load configuration
$config = json_decode(file_get_contents('config.json'), true);

// Database connection
$mongo = new MongoDB\Client($config['mongodb_url']);
$db = $mongo->selectDatabase($config['mongodb_db']);
$ipAddressCollection = $db->selectCollection('ipaddresses');
$networkCollection = $db->selectCollection('networks');

// Fetch all IP addresses and networks
$ipAddresses = $ipAddressCollection->find()->toArray();
$networks = $networkCollection->find()->toArray();

// Sort IP addresses
usort($ipAddresses, function($a, $b) {
    return ipToLong($a['address']) - ipToLong($b['address']);
});

// Map network CIDRs to colors
$networkColors = [];
foreach ($networks as $network) {
    $networkColors[$network['cidr']] = $network['color'];
}

// Calculate subnet for each IP address
foreach ($ipAddresses as &$ipAddress) {
    foreach ($networks as $network) {
        if (ip_in_subnet($ipAddress['address'], $network['cidr'])) {
            $ipAddress['networkColor'] = $networkColors[$network['cidr']];
            break;
        } else {
            $ipAddress['networkColor'] = 'defaultColor'; // Default color if not in any subnet
        }
    }
}

// Calculate unused IPs for each subnet
$unusedIPs = [];
foreach ($networks as $network) {
    list($ipStart, $ipEnd) = calculateIPRange($network['cidr']);
    $range = range(ip2long($ipStart), ip2long($ipEnd));
    $usedIPs = array_map('ip2long', array_column($ipAddresses, 'address'));
    $unusedIPs[$network['cidr']] = array_diff($range, $usedIPs);
    $unusedIPs[$network['cidr']] = array_map('long2ip', $unusedIPs[$network['cidr']]);
}

// extract and format open ports 
function formatOpenPorts($openPorts) {
    // Convert BSONArray to a native PHP array
    $openPortsArray = json_decode(json_encode($openPorts), true);

    $portList = array_map(function ($portInfo) {
        return $portInfo['port'];
    }, $openPortsArray);

    return implode(", ", $portList);
}


?>

<!-- HTML Output -->
<!DOCTYPE html>
<html>
<head>
    <title>IP Address Management</title>
    <link rel="stylesheet" href="style.css">
    <script>
        function toggleSubnet(subnetId) {
            var x = document.getElementById(subnetId);
            if (x.style.display === "none") {
                x.style.display = "block";
            } else {
                x.style.display = "none";
            }
        }
        function editHostname(id, element) {
            var hostname = prompt("Enter new hostname:", element.innerText);
            if (hostname !== null && hostname !== '') {
                // Post data to a PHP script
                fetch('updateHostname.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'id=' + id + '&hostname=' + encodeURIComponent(hostname)
                }).then(response => {
                    if (response.ok) {
                        element.innerText = hostname;
                    } else {
                        alert('Error updating hostname');
                    }
                });
            }
        }
    </script>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>I-PAM v0.1</h1>
            <img src="pam.png" width="250px"><br>

            <a href="import.php">Import NMAP Output</a><br>
            <a href="networks.php">Manage Subnets / Networks</a>
        </div><br>
        <div class="content">
        <table>
            <tr>
                <th>IP Address</th>
                <th>Hostname</th>
                <th>MAC Address</th>
                <th>Open Ports</th>
            </tr>
            <?php foreach ($ipAddresses as $ipAddress): ?>
                <tr style="background-color: <?php echo htmlspecialchars($ipAddress['networkColor'] ?? 'defaultColor'); ?>;">
                    <td><?php echo htmlspecialchars($ipAddress['address']); ?></td>
                    <td onclick="editHostname('<?php echo $ipAddress['_id']; ?>', this)">
                        <?php echo htmlspecialchars(isset($ipAddress['hostName']) && $ipAddress['hostName'] !== '' ? $ipAddress['hostName'] : 'unknown'); ?>
                    </td>
                    <td><?php echo htmlspecialchars($ipAddress['macAddress'] ?? 'N/A'); ?></td>
                    <td><?php echo formatOpenPorts($ipAddress['openPorts'] ?? []); ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        </div>
        <div class="network-info">
            <h2>Network Information</h2>
            <?php foreach ($networks as $network): ?>
                <div style="background-color: <?php echo htmlspecialchars($network['color']); ?>; padding: 10px; margin-bottom: 10px;">
                    <p onclick="toggleSubnet('<?php echo htmlspecialchars($network['_id']); ?>')" style="cursor: pointer;">
                        <strong><?php echo htmlspecialchars($network['name']); ?>:</strong> <?php echo htmlspecialchars($network['cidr']); ?>
                    </p>
                    <div id="<?php echo htmlspecialchars($network['_id']); ?>" style="display: none;">
                        <strong>Unused Addresses:</strong>
                        <ul>
                            <?php foreach ($unusedIPs[$network['cidr']] as $unusedIP): ?>
                                <li><?php echo $unusedIP; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
