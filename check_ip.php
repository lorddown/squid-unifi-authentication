#!/usr/bin/php-cgi -q
<?php

$db="/var/db/captiveportal_unifi.db";

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

        $status = squid_check_ip($db, $check_ip);

        if (isset($status)) {
                fwrite(STDOUT, "OK user={$status}\n");
        } else {
                fwrite(STDOUT, "ERR");
        }
}
function squid_check_ip($db, $check_ip) {
        exec("sqlite3 -init 10000 {$db} \"SELECT ip FROM captiveportal WHERE ip='{$check_ip}'\"", $ip);
        if ($check_ip == $ip[0]) {
                exec("sqlite3 -init 10000 {$db} \"SELECT username FROM captiveportal WHERE ip='{$check_ip}'\"", $user);
                return $user[0];
        }
}
