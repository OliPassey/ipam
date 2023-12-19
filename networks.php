<!DOCTYPE html>
<html>
<head>
  <title>Networks</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <div class="container">
    <div class="header">
      <h1>Networks Management</h1>
      <a href="index.php">Home</a> <br> <a href="import.php">Import NMAP Output</a>
    </div><br>
    <div class="content">
      <!-- Form for adding a new network -->
      <form action="networks.php" method="post">
        <label for="cidr">CIDR:</label><br>
        <input type="text" id="cidr" name="cidr" required><br>
        <label for="name">Name:</label><br>
        <input type="text" id="name" name="name" required><br>
        <label for="color">Color:</label><br>
        <input type="color" id="color" name="color" required><br>
        <input type="submit" value="Add Network">
      </form>

  <?php
  require_once 'vendor/autoload.php'; // Load Composer dependencies
  $config = json_decode(file_get_contents('config.json'), true);
  $mongo = new MongoDB\Client($config['mongodb_url']);
  $db = $mongo->selectDatabase($config['mongodb_db']);
  $networkCollection = $db->selectCollection('networks');

  // Handle form submission for adding a new network
  if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $cidr = $_POST['cidr'];
    $name = $_POST['name'];
    $color = $_POST['color'];

    $networkCollection->insertOne(['cidr' => $cidr, 'name' => $name, 'color' => $color]);
    header('Location: networks.php'); // Redirect to prevent form resubmission
    exit;
  }

    // Handle network deletion
    if (isset($_GET['delete'])) {
    $networkId = new MongoDB\BSON\ObjectId($_GET['delete']);
    $networkCollection->deleteOne(['_id' => $networkId]);
    header('Location: networks.php');
    exit;
    }

    // Fetch all networks
    $networks = $networkCollection->find()->toArray();
    ?>

    <!-- List of existing networks -->
    <h2>Existing Networks</h2>
    <table>
    <tr>
        <th>CIDR</th>
        <th>Name</th>
        <th>Color</th>
        <th>Action</th>
    </tr>
    <?php foreach ($networks as $network): ?>
        <tr>
        <td><?php echo htmlspecialchars($network['cidr']); ?></td>
        <td><?php echo htmlspecialchars($network['name']); ?></td>
        <td style="background-color:<?php echo htmlspecialchars($network['color']); ?>;"></td>
        <td>
            <a href="networks.php?delete=<?php echo htmlspecialchars($network['_id']); ?>" onclick="return confirm('Are you sure?');">Delete</a>
        </td>
        </tr>
    <?php endforeach; ?>
    </table>
</div>
</div>
</body>
</html>