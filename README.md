![image](https://github.com/OliPassey/ipam/assets/7745805/ade0bcf9-322a-4d36-9e91-8f41217832c0)

# i-pam  
Personal use IPAM project that is probably of no-use to anyone. 

## Description
 A simple MongoDB backed, php webapp to keep track of IP usage in your network. You can now paste in a simple NMAP output and it'll import the lot, or if you already have content it'll update where it finds existing IPs in the database and create new reecords when it doesn't. If you add in your network information, it'll colour code your subnets in the main table and list unused addresses in each.  

## Installation
Docker
```bash
docker pull olipassey/ipam:latest
docker run -p 3000:80 -v c:\path\to\config.json:/var/www/html/config.json olipassey/ipam
```

## Usage
Once running hit http://ip:3000/  



