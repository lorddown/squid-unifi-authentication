#!/usr/local/bin/php-cgi -q
<?php
set_time_limit(0);

/**
 * PHP API usage example
 *
 * contributed by: Art of WiFi
 * description: example basic PHP script to fetch an Access Point's scanning state/results
 */
/**
 * using the composer autoloader
 */
/**
 * include the config file (place your credentials etc. there if not already present)
 * see the config.template.php file for an example
 */
require_once('config.php');
require_once('client.php');
/**
 * site id and MAC address of AP to query
 */
$site_id = 'default';
//

//$ap_mac  = '<enter MAC address of Access Point to check>';

/**
 * initialize the UniFi API connection class and log in to the controller and do our thing
 * spectrum_scan_state()
 */
$unifi_connection  = new UniFi_API\Client($controlleruser, $controllerpassword, $controllerurl, $site_id, $controllerversion);
$set_debug_mode    = $unifi_connection->set_debug($debug);
$loginresults      = $unifi_connection->login();

while(true):

$data_list_clients = $unifi_connection->list_clients();
$data_list_guests  = $unifi_connection->list_guests();

//$data_list_guests  = $unifi_connection->list_guests(168);

/**
 * provide feedback in json format
 */

//$list_guests = json_decode($data, false);
//echo json_encode($data, JSON_PRETTY_PRINT);
//print_r($data);

	$createquery = 	"CREATE TABLE IF NOT EXISTS captiveportal (" .
				"ip TEXT, guest_id TEXT, authorized_by TEXT, username TEXT); " .
			"CREATE INDEX IF NOT EXISTS user ON captiveportal (username);" .
			"CREATE INDEX IF NOT EXISTS ip ON captiveportal (ip)";

//print_r($data_list_clients);

$db="/var/db/captiveportal_unifi.db";

/*
// https://www.devdungeon.com/content/how-use-sqlite3-php

// Create an in-memory database
$memory_db = new PDO('sqlite::memory:');

// Creating a table
$db->exec(
"CREATE TABLE IF NOT EXISTS myTable (
    id INTEGER PRIMARY KEY, 
    title TEXT, 
    value TEXT)"
);
*/

foreach($data_list_clients as $lista_clients)
{
if(isset($lista_clients->_id))
        {
	$_id      = $lista_clients->_id;
	$ip	  = $lista_clients->ip;

	foreach($data_list_guests as $lista_guests)
	{
	if($lista_guests->expired == false)
	{
	if($lista_guests->user_id == $_id)
	   {

		$authorized_by = $lista_guests->authorized_by;


		if($authorized_by=='voucher')
		{
		   $username =  $authorized_by.$lista_guests->voucher_code;
		}
		elseif($authorized_by=='radius')
		{
	 	   $username =  $authorized_by.$lista_guests->radius_username;
		}

		$mac = $lista_guests->mac;

		if(isset($lista_guests->hostname))
		{
		   $hostname = $lista_guests->hostname;
		}
		else
		{
		   $hostname = " ";
		}

		echo $ip ." - ". $authorized_by ." - ". $username ." - ". $mac ." - ". $_id ." - ". $hostname ."\n";

exec("sqlite3 {$db} \"INSERT INTO captiveportal (ip,guest_id,authorized_by,username) VALUES ('{$ip}','{$_id}','{$authorized_by}','{$username}')\"", $ip);
	   }
	}
	}
}
}
sleep(10);
echo "1";
endwhile;
?>
