![image](https://github.com/OliPassey/ipam/assets/7745805/6e29200c-620e-4eba-865f-7b5205e8f16a)

# i-pam  
Personal use IPAM project that is probably of no-use to anyone. It works, but you should probably use ANYTHING else you can find. See Security Disclaimer at the bottom of this page.

## Description
A simple MongoDB backed, php webapp to keep track of IP usage in your network. You have the option to import an NMAP Quick Scan output manually or add a subnet to scan and it'll handle most things for you. 

## Installation
Docker
```bash
docker pull olipassey/ipam:latest
docker run -p 3000:80 -v c:\path\to\config.json:/var/www/html/config.json olipassey/ipam
```

## Usage
Once running hit http://ip:3000 
You will need to setup an external cron to call http://your-domain/start_scan.php once an hour or however often you want to scan. Beyond that, everything else is automated in terms of imports. 
Stale records are identified but must be manually purged. 
Hostnames are not always detected, clicking on Unknown in-line allows you to manually update a record. 
Clicking on a subnet summary on the right will show all available IPs within that subnet. 

## Security Disclaimer 
This should not be placed on the public internet, or used in a secure environment. It is for home-lab use, has not been pen-tested and almost certainly has horrendous bugs in. 
