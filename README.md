# squid-unifi-authentication
 System that proves an authentication method for squid proxy.
System that proves an authentication method for squid proxy.

Collects connected users as guests in the UNIFI controller and their respective IP addresses by creating a database (SQLITE3).

Such a database will be used by a squid helper (based on the squid "check_ip.php" PFSENSE helper)
