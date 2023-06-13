# ipam
 Personal use IPAM project that is probably of no-use to anyone. Also learning I do not like JavaScript.

## Description
 A simple MongoDB backed, JS webapp to keep track of IP usage in your network. It is currently just a data-entry tool, it does no scanning or importing. 
 I am playing with importing NMAP output or retrieving DHCP information from PFSense but I promise nothing, ultimately I need a list of IPs in use and got a bit carried away. 🤷‍♂️ Also working on identifying and printing unused addresses.

## Installation
Docker
```bash
docker pull olipassey/ipam:latest
docker run -d -p 3000:3000 -e DATABASE_URL=mongodb://10.0.0.0:27017/ipam -e PORT=3000 --name ipam olipassey/ipam:latest
```
Environment Variables must be declared as above (update the IP of your MongoDB Instance, and PORT is the HTTP Port (not mongo)  

## Usage
Once running hit http://ip:3000/  
http://ip:3000/networks is available for noting Networks or Subnets by CIDR range, these can be colour coded for quick reference.  

## Screenshots
Front-End  
![image](https://github.com/OliPassey/ipam/assets/7745805/4c8ebc11-01b9-4b27-896d-c9526734cf93)  
Imagine white text in an excel grid with all the text centred up nicely. Totally great...

Back-end (Networks Page)  
![image](https://github.com/OliPassey/ipam/assets/7745805/376f9780-83dd-4ef0-898d-b1cc36b65bdd)
