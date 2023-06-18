// Load environment variables from .env file
// require('dotenv').config();
const DATABASE_URL = process.env.DATABASE_URL || 'mongodb://mongodb:27017/ipam';

// Import necessary dependencies
const express = require('express');
const bodyParser = require('body-parser');
const mongoose = require('mongoose');
const ip = require('ip');

// Initialize express app
const app = express();

// Connect to MongoDB
mongoose.connect(process.env.DATABASE_URL, { useNewUrlParser: true, useUnifiedTopology: true });
const db = mongoose.connection;
db.on('error', (error) => console.error(error)); // Log any connection errors
db.once('open', () => console.log('Connected to Database')); // Log successful database connection

// Middleware configuration
app.use(express.json());
app.use(bodyParser.urlencoded({ extended: true }));
app.set('view engine', 'ejs');
app.use(express.static('public')); // Serve static files from the 'public' directory


// Define the MongoDB schema for IP Addresses
const IPAddressSchema = new mongoose.Schema({
    address: String,
    description: String,
    hostName: String,
    os: String,
});

// Define the MongoDB schema for NetworkInfo
const NetworkSchema = new mongoose.Schema({
    cidr: String,
    name: String,
    color: String,
  });

// Create a model from the IPAddressSchema
const IPAddress = mongoose.model('IPAddress', IPAddressSchema);

// Create a model from the NetworkInfo Schema
const Network = mongoose.model('Network', NetworkSchema);

// Define route handler for GET requests to the root route
app.get('/', async (req, res) => {
  let ipAddresses = await IPAddress.find();
  let networks = await Network.find();
  let usedAddresses = ipAddresses.map(ipAddress => ipAddress.address); // Define usedAddresses array
  let unusedAddresses = []; // Initialize unusedAddresses array

    // Sort IP addresses
    ipAddresses.sort((a, b) => ip.toLong(a.address) - ip.toLong(b.address));
    // console.log(networks); // Add this line to log the networks array

    // Add network and color information to each IP address
    ipAddresses = ipAddresses.map(ipAddress => {
        const network = networks.find(network => ip.cidrSubnet(network.cidr).contains(ipAddress.address));
        return {
            ...ipAddress.toObject(),  // Convert the Mongoose document to a plain JavaScript object
            network: network ? network.name : null,
            color: network ? network.color : 'white'
        };
    });

    // Populate the unusedAddresses array
    networks.forEach(network => {
      const subnet = ip.cidrSubnet(network.cidr);

      for (let i = ip.toLong(subnet.firstAddress); i <= ip.toLong(subnet.lastAddress); i++) {
        const address = ip.fromLong(i);

        if (!usedAddresses.includes(address)) {
          unusedAddresses.push(address);
        }
      }
    });

  // Render the 'index' view with the sorted IP addresses, networks, and unusedAddresses
  res.render('index', { ipAddresses: ipAddresses, networks: networks, unusedAddresses: unusedAddresses });
});


// Define route handler for POST requests to the root route
app.post('/', async (req, res) => {
    // Create a new IPAddress from the request body
    const ipAddress = new IPAddress({
        address: req.body.address,
        description: req.body.description,
        hostName: req.body.hostName,
        os: req.body.os
    });

    // Save the new IPAddress to the database
    await ipAddress.save();

    // Redirect the client to the root route
    res.redirect('/');
});

// Route for POST - networks
app.post('/networks', async (req, res) => {
  const { name, cidr, color } = req.body;
  
  try {
    const network = new Network({ name, cidr, color });
    await network.save();
    res.redirect('/networks');
  } catch (error) {
    console.error(error);
    res.status(500).send('Internal Server Error');
  }
});



// Route for GET - networks
app.get('/networks', async (req, res) => {
  const networks = await Network.find();
  const ipAddresses = await IPAddress.find();
  const usedAddresses = ipAddresses.map(ipAddress => ipAddress.address);
  const unusedAddresses = [];

  networks.forEach(network => {
    const subnet = ip.cidrSubnet(network.cidr);

    for (let i = ip.toLong(subnet.firstAddress); i <= ip.toLong(subnet.lastAddress); i++) {
      const address = ip.fromLong(i);

      if (!usedAddresses.includes(address)) {
        unusedAddresses.push(address);
      }
    }
  });

  res.render('networks', { networks: networks, unusedAddresses: unusedAddresses });
});

  
// Define route handler for GET requests to the /unused route
app.get('/unused', async (req, res) => {
  const networks = await Network.find();
  const ipAddresses = await IPAddress.find();
  const usedAddresses = ipAddresses.map(ipAddress => ipAddress.address);
  const unusedAddresses = [];

  networks.forEach(network => {
    const subnet = ip.cidrSubnet(network.cidr);

    for (let i = ip.toLong(subnet.firstAddress); i <= ip.toLong(subnet.lastAddress); i++) {
      const address = ip.fromLong(i);

      if (!usedAddresses.includes(address)) {
        unusedAddresses.push({address, network: network.name, color: network.color});
      }
    }
  });

  res.render('unused', { addresses: unusedAddresses });
});

// Define route handler for GET requests to the '/import' route
app.get('/import', (req, res) => {
    res.render('import'); // Render the 'import' view
});

// Define route handler for POST requests to the '/import' route
app.post('/import', async (req, res) => {
  const nmapOutput = req.body.nmapOutput;
  const lines = nmapOutput.split('\n');

  const ipAddresses = await IPAddress.find();

  for (let i = 0; i < lines.length; i++) {
    const line = lines[i];
    const match = line.match(/Nmap scan report for (.*) \((.*)\)/);

    if (match) {
      const hostName = match[1];
      const ipAddress = match[2];

      const existingHost = ipAddresses.find((ip) => ip.address === ipAddress);

      if (existingHost) {
        // Update existing host
        existingHost.hostName = hostName;
        existingHost.description = hostName;
        await existingHost.save();
      } else {
        // Create new host
        const newHost = new IPAddress({
          address: ipAddress,
          description: hostName,
          hostName: hostName
          // Add other fields as necessary
        });
        await newHost.save();
      }
    }
  }

  res.redirect('/');
});

// Define route handler for PATCH requests to update IP Address fields
app.patch('/ip/:id', async (req, res) => {
  const id = req.params.id;
  const { description, os } = req.body;

  try {
    const ipAddress = await IPAddress.findById(id);

    if (!ipAddress) {
      return res.status(404).json({ error: 'IP Address not found' });
    }

    if (description !== undefined) {
      ipAddress.description = description;
    }

    if (os !== undefined) {
      ipAddress.os = os;
    }

    await ipAddress.save();

    res.sendStatus(200);
  } catch (error) {
    console.error(error);
    res.status(500).json({ error: 'Internal Server Error' });
  }
});


// Define the port the server will listen on
const PORT = process.env.PORT || 3000;

// Start the server
app.listen(PORT, () => console.log(`Server running on port ${PORT}`));
