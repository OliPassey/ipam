<img src="https://github.com/OliPassey/ipam/assets/7745805/9aef48c4-7ed2-4644-9b76-0285144361dc" width="250px">  

# i-pam  
 Personal use IPAM project that is probably of no-use to anyone. Also learning I do not like JavaScript.

## Description
 A simple MongoDB backed, JS webapp to keep track of IP usage in your network. You can now paste in a simple NMAP output and it'll import the lot, or if you already have content it'll update where it finds existing IPs in the database and create new reecords when it doesn't. If you add in your network information, it'll colour code your subnets in the main table and list unused addresses in each.  
 The network info is currently printing incorrectly - working on a fix.  
 Editing "Description" & "OS" is now available, but you must hit save at the bottom to save changes - working on saving when you click away from a field.

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
http://ip:3000/import will let you paste in an NMAP output. Basic scans only at the moment)

## Screenshots
Front-End  
![image](https://github.com/OliPassey/ipam/assets/7745805/e470094e-3a9c-4a90-9313-258c9f3c025e)
Imagine white text in an excel grid with all the text centred up nicely. Totally great...

Back-end (Networks Page)  
![image](https://github.com/OliPassey/ipam/assets/7745805/85c7beaa-64a6-4bef-a98a-fb2f972282ff)

Back-end (Imports Page)
![image](https://github.com/OliPassey/ipam/assets/7745805/f205185e-328d-4825-82cd-6604d03acb76)
