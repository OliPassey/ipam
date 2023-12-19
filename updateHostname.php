<?php
require_once 'vendor/autoload.php'; // Load Composer dependencies

// Load configuration
$config = json_decode(file_get_contents('config.json'), true);

// Database connection
$mongo = new MongoDB\Client($config['mongodb_url']);
$db = $mongo->selectDatabase($config['mongodb_db']);
$ipAddressCollection = $db->selectCollection('ipaddresses');

// Handle the POST request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $hostname = $_POST['hostname'];

    // Convert the string ID to MongoDB ObjectId
    $objectId = new MongoDB\BSON\ObjectId($id);

    // Update the document
    $ipAddressCollection->updateOne(
        ['_id' => $objectId],
        ['$set' => ['hostName' => $hostname]]
    );
}
?>
