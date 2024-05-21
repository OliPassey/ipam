<?php
require_once 'vendor/autoload.php';

$config = json_decode(file_get_contents('config.json'), true);
$mongo = new MongoDB\Client($config['mongodb_url']);
$db = $mongo->selectDatabase($config['mongodb_db']);
$collection = $db->selectCollection('ipaddresses');

$id = $_POST['id'] ?? null;
if ($id) {
    $result = $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
    echo json_encode(['success' => $result->getDeletedCount() == 1]);
} else {
    echo json_encode(['success' => false]);
}
?>
