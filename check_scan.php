<?php

$scanDir = 'scans/';
$scanFiles = glob($scanDir . 'output_*.txt');

if (!empty($scanFiles)) {
    // Sort files by timestamp, newest first
    usort($scanFiles, function($a, $b) {
        return filemtime($b) - filemtime($a);
    });

    // Read the most recent file
    $latestScanFile = $scanFiles[0];
    $scanContents = file_get_contents($latestScanFile);

    // Convert newlines to <br> tags for browser display
    echo nl2br($scanContents);
} else {
    echo "No scan results found.";
}
?>
