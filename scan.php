<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Network Scan</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    function startScan() {
        // Start the scan
        $.ajax({url: 'start_scan.php', success: function() {
            // Poll for updates
            setInterval(checkScan, 3000); // Check every 3 seconds
        }});
    }

    function checkScan() {
        // Check for scan updates
        $.ajax({url: 'check_scan.php', success: function(result) {
            // Update the page with scan results
            document.getElementById('scanOutput').innerHTML = result;
        }});
    }
    </script>
</head>
<body>
    <button onclick="startScan()">Start Scan</button>
    <div id="scanOutput"></div>
</body>
</html>
