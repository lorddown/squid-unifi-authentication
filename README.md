# squid-unifi-authentication


System that proves an authentication method for squid proxy.

Collects connected users as guests in the UNIFI controller and their respective IP addresses by creating a database (SQLITE3).

Such a database will be used by a squid helper (based on the squid "check_ip.php" PFSENSE helper)

Still in development...

# Files

- check_unifi.php - Create a SQLITE database with ONLINE GUESTS connected users in UNIFI CONTROLLER.

- check_ip.php - File responsible for check a DATABASE SQLITE db in /var/db/  - Code BASED from pfsense/FreeBSD-ports

- config.php - configuration file for client.php
- client.php - API from  " Art-of-WiFi:"



## Need help or have suggestions?

There is still work to be done to add functionality and improve the usability of this class, so all suggestions/comments are welcome. Please use the github [issue] to share your ideas/questions.

## Contribute

If you would like to contribute code (improvements), please open an issue and include your code there or else create a pull request.

## Credits

This project is based on the work done by the following developers:


- Art-of-WiFi: https://github.com/Art-of-WiFi/UniFi-API-client/
- pfsense/FreeBSD-ports: https://github.com/pfsense/FreeBSD-ports/blob/ad410f27d1c5babd9f800d2150b0c1edefb841e5/www/pfSense-pkg-squid/files/usr/local/bin/check_ip.php

## Important Disclaimer

Many of the functions in this API client class are not officially supported by UBNT and as such, may not be supported in future versions of the UniFi Controller API.
