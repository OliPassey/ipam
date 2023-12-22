<?php

$configFile = 'scan_config.json';
$config = json_decode(file_get_contents($configFile), true);
$cidrRanges = $config['network_scan_ranges'] ?? [];

if (empty($cidrRanges)) {
    die('No CIDR ranges found in configuration.');
}

function isValidCIDR($cidr) {
    $pattern = '/^([0-9]{1,3}\.){3}[0-9]{1,3}\/[0-9]{1,2}$/';
    return preg_match($pattern, $cidr);
}

foreach ($cidrRanges as $range) {
    if (!isValidCIDR($range)) {
        echo "Invalid CIDR range: $range<br>";
        continue;
    }

    $sanitizedRange = escapeshellarg($range);
    $timestamp = date('Ymd_His');
    $command = "nmap -T4 -F -oN scans/output_$timestamp.txt $sanitizedRange";

    // Debugging: Capture output and error
    $output = [];
    $return_var = null;
    exec($command . ' 2>&1', $output, $return_var);

    // Output results for debugging
    echo "Command: $command<br>";
    echo "Return Var: $return_var<br>";
    echo "Output:<br><pre>" . implode("\n", $output) . "</pre><br>";
}

echo "All scans are completed.";
?>
