<?php
$configFile = 'scan_config.json';

// Read the existing config
$config = json_decode(file_get_contents($configFile), true);
if (!$config) {
    $config = [];
}

// Add a new CIDR range
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cidrRange'])) {
    $cidrRange = $_POST['cidrRange'];
    $config['network_scan_ranges'][] = $cidrRange;
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    echo '<p>CIDR range added successfully.</p>';
}

// Remove a CIDR range
if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['remove'])) {
    $index = $_GET['remove'];
    array_splice($config['network_scan_ranges'], $index, 1);
    file_put_contents($configFile, json_encode($config, JSON_PRETTY_PRINT));
    header('Location: scan_config.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Network Scan Configuration</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Define Network to Scan</h1>
    <form action="scan_config.php" method="post">
        <label for="cidrRange">Enter CIDR Range:</label><br>
        <input type="text" id="cidrRange" name="cidrRange" placeholder="e.g., 192.168.1.0/24" required><br><br>
        <input type="submit" value="Add CIDR Range">
    </form>

    <h2>Current CIDR Ranges</h2>
    <ul>
        <?php foreach ($config['network_scan_ranges'] as $index => $range): ?>
            <li>
                <?php echo htmlspecialchars($range); ?>
                <a href="scan_config.php?remove=<?php echo $index; ?>">Remove</a>
            </li>
        <?php endforeach; ?>
    </ul>
    <p><button onclick="window.location.href='index.php';">Home</button></p>
</body>
</html>
