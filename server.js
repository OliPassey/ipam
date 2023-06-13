// Load environment variables from .env file
require('dotenv').config();

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
    let ipAddresses = await IPAddress.find(); // Get all IP Addresses from the database
    let networks = await Network.find(); // Get all networks from the database

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

    // Render the 'index' view with the sorted IP addresses and networks
    res.render('index', { ipAddresses: ipAddresses, networks: networks });
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

// Define route handler for GET requests to the /network route
app.get('/networks', async (req, res) => {
    const networks = await Network.find();
    res.render('networks', { networks: networks });
  });
  
  app.post('/networks', async (req, res) => {
    const network = new Network({
      cidr: req.body.cidr,
      name: req.body.name,
      color: req.body.color
    });
    await network.save();
    res.redirect('/networks');
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
    // Extract the NMAP output from the request body
    const nmapOutput = req.body.nmapOutput;
    
    // Split the NMAP output into lines
    const lines = nmapOutput.split('\n');
    
    // Extract host information from the first line of the NMAP output
    const hostInfo = lines[0].split(' ');
    const hostName = hostInfo[4];
    const ipAddress = hostInfo[5].slice(1, -2);  // remove parentheses and trailing character

    // Create a new IPAddress from the extracted host information
    const newHost = new IPAddress({
      address: ipAddress,
      description: hostName,
      hostName: hostName
      // Add other fields as necessary
    });
    
    // Save the new IPAddress to the database
    await newHost.save();
    
    // Redirect the client to the root route
    res.redirect('/');
});

// Define the port the server will listen on
const PORT = process.env.PORT || 3000;

// Start the server
app.listen(PORT, () => console.log(`Server running on port ${PORT}`));
