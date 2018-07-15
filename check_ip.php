#!/usr/local/bin/php-cgi -q
<?php

## Code based on check_ip PFSENSE.

require_once('config.php');
$site_id = 'default';

error_reporting(0);

// stdin loop
if (!defined(STDIN)) {
	define("STDIN", fopen("php://stdin", "r"));
}
if (!defined(STDOUT)) {
	define("STDOUT", fopen('php://stdout', 'w'));
}
while (!feof(STDIN)) {

	$check_ip = trim(fgets(STDIN));
/*
// trecho de codigo que recriar os usuario online a cada 30 segundos

	$db="/var/db/captiveportal_unifi.db";
		$end_time = microtime(true);
		$duracao = $end_time-$start_time;
	$start_time = microtime(true);

*/

	$status = squid_check_ip($db, $check_ip);

	if (isset($status)) {
		fwrite(STDOUT, "OK user={$status}\n");
	} else {
		fwrite(STDOUT, "ERR\n");
	}
}
function squid_check_ip($db, $check_ip) {
	exec("sqlite3 {$db} \"SELECT ip FROM captiveportal WHERE ip='{$check_ip}'\"", $ip);
	if ($check_ip == $ip[0]) {
		exec("sqlite3 {$db} \"SELECT username FROM captiveportal WHERE ip='{$check_ip}'\"", $user);
		return $user[0];
	}
	//unifi_check($check_ip);
}

?>
