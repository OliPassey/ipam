<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Network Scan</title>
    <link rel="stylesheet" href="style.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
    var scanInterval; // Holds the interval for checking scan updates

    function startScan() {
        document.getElementById('scanOutput').innerHTML = '<p>Starting scan...</p>'; // Show initial message

        // Start the scan
        $.ajax({
            url: 'start_scan.php', 
            success: function() {
                document.getElementById('scanOutput').innerHTML = '<p>Scan started. Gathering data...</p>';
                scanInterval = setInterval(checkScan, 3000); // Check every 3 seconds
            },
            error: function() {
                document.getElementById('scanOutput').innerHTML = '<p>Error starting scan. Please try again.</p>';
            }
        });
    }

    function checkScan() {
        // Check for scan updates
        $.ajax({
            url: 'check_scan.php', 
            success: function(result) {
                if (result) {
                    document.getElementById('scanOutput').innerHTML = result;
                }
            },
            error: function() {
                document.getElementById('scanOutput').innerHTML = '<p>Error retrieving scan results. Please check the server status.</p>';
                clearInterval(scanInterval); // Stop polling if there's an error
            }
        });
    }

    function stopScan() {
        clearInterval(scanInterval); // Allow stopping the scan manually
        document.getElementById('scanOutput').innerHTML += '<p>Scan stopped.</p>';
    }
    </script>
</head>
<body>
    <button onclick="startScan()">Start Scan</button>
    <button onclick="stopScan()">Stop Scan</button>
    <div id="scanOutput"></div>
</body>
</html>
