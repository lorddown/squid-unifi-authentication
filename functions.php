<?php
/*
 * captiveportal.inc
 *
 * part of pfSense (https://www.pfsense.org)
 * Copyright (c) 2004-2018 Rubicon Communications, LLC (Netgate)
 * All rights reserved.
 *
 * originally part of m0n0wall (http://m0n0.ch/wall)
 * Copyright (c) 2003-2006 Manuel Kasper <mk@neon1.net>.
 * All rights reserved.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */
/* include all configuration functions */
/*require_once("config.inc");
require_once("functions.inc");
require_once("filter.inc");
require_once("radius_accounting.inc");
require_once("radius_authentication.inc");
require_once("voucher.inc");
*/


/* log simple messages to syslog */
function captiveportal_syslog($message) {
	global $cpzone;
	$message = trim($message);
	$message = "Zone: {$cpzone} - {$message}";
	openlog("logportalauth", LOG_PID, LOG_LOCAL4);
	// Log it
	syslog(LOG_INFO, $message);
	closelog();
}


function captiveportal_opendb() {
	global $cpzone;
        
        //$db_path = "{$g['vardb_path']}/captiveportal{$cpzone}.db";
	$db_path = "/var/db/captiveportal_unifi.db";
/*	$createquery = "CREATE TABLE IF NOT EXISTS captiveportal (" .
				"allow_time INTEGER, pipeno INTEGER, ip TEXT, mac TEXT, username TEXT, " .
				"sessionid TEXT, bpassword TEXT, session_timeout INTEGER, idle_timeout INTEGER, " .
				"session_terminate_time INTEGER, interim_interval INTEGER, traffic_quota INTEGER, " .
				"radiusctx TEXT); " .
			"CREATE UNIQUE INDEX IF NOT EXISTS idx_active ON captiveportal (sessionid, username); " .
			"CREATE INDEX IF NOT EXISTS user ON captiveportal (username); " .
			"CREATE INDEX IF NOT EXISTS ip ON captiveportal (ip); " .
			"CREATE INDEX IF NOT EXISTS starttime ON captiveportal (allow_time)";
*/        
	$createquery = 	"CREATE TABLE IF NOT EXISTS captiveportal (" .
				"ip TEXT, guest_id TEXT, authorized_by TEXT, username TEXT, logged_user TEXT); " .
			"CREATE INDEX IF NOT EXISTS user ON captiveportal (username);" .
                        "CREATE INDEX IF NOT EXISTS logged_user ON captiveportal (logged_user);" .
			"CREATE INDEX IF NOT EXISTS ip ON captiveportal (ip)";        
        
        
        
        
        
	try {
		$DB = new SQLite3($db_path);
		$DB->busyTimeout(60000);
	} catch (Exception $e) {
		captiveportal_syslog("Could not open {$db_path} as an sqlite database for {$cpzone}. Error message: " . $e->getMessage() . " -- Trying again.");
		unlink_if_exists($db_path);
		try {
			$DB = new SQLite3($db_path);
			$DB->busyTimeout(60000);
		} catch (Exception $e) {
			captiveportal_syslog("Still could not open {$db_path} as an sqlite database for {$cpzone}. Error message: " . $e->getMessage() . " -- Remove the database file manually and ensure there is enough free space.");
			return;
		}
	}
	if (!$DB) {
		captiveportal_syslog("Could not open {$db_path} as an sqlite database for {$cpzone}. Error message: {$DB->lastErrorMsg()}. Trying again.");
		unlink_if_exists($db_path);
		$DB = new SQLite3($db_path);
		$DB->busyTimeout(60000);
		if (!$DB) {
			captiveportal_syslog("Still could not open {$db_path} as an sqlite database for {$cpzone}. Error message: {$DB->lastErrorMsg()}. Remove the database file manually and ensure there is enough free space.");
			return;
		}
	}
	if (! $DB->exec($createquery)) {
		captiveportal_syslog("Error during table {$cpzone} creation. Error message: {$DB->lastErrorMsg()}. Resetting and trying again.");
		/* If unable to initialize the database, reset and try again. */
		$DB->close();
		unset($DB);
		unlink_if_exists($db_path);
		$DB = new SQLite3($db_path);
		$DB->busyTimeout(60000);
		if ($DB->exec($createquery)) {
			captiveportal_syslog("Successfully reinitialized tables for {$cpzone} -- database has been reset.");
			/*if (!is_numericint($cpzoneid)) {
				if (is_array($config['captiveportal'])) {
					foreach ($config['captiveportal'] as $cpkey => $cp) {
						if ($cpzone == $cpkey) {
							$cpzoneid = $cp['zoneid'];
						}
					}
				}
			}
			if (is_numericint($cpzoneid)) {
				$table_names = captiveportal_get_ipfw_table_names();
				foreach ($table_names as $table_name) {
					mwexec("/sbin/ipfw table {$table_name} flush");
				}
				captiveportal_syslog("Flushed tables for {$cpzone} after database reset.");
			}
		*/} else {
			captiveportal_syslog("Still unable to create tables for {$cpzone}. Error message: {$DB->lastErrorMsg()}. Remove the database file manually and try again.");
		}
	}
	return $DB;
}

/* read captive portal DB into array */
function captiveportal_read_db($query = "") {
	$cpdb = array();
	$DB = captiveportal_opendb();
	if ($DB) {
		$response = $DB->query("SELECT * FROM captiveportal {$query}");
		if ($response != FALSE) {
			while ($row = $response->fetchArray()) {
				$cpdb[] = $row;
			}
		}
		$DB->close();
	}
	return $cpdb;
}
function captiveportal_remove_entries($remove) {
	if (!is_array($remove) || empty($remove)) {
		return;
	}
	$query = "DELETE FROM captiveportal WHERE sessionid in (";
	foreach ($remove as $idx => $unindex) {
		$query .= "'{$unindex}'";
		if ($idx < (count($remove) - 1)) {
			$query .= ",";
		}
	}
	$query .= ")";
	captiveportal_write_db($query);
}
/* write captive portal DB */
function captiveportal_write_db($queries) {
	//global $g;
	if (is_array($queries)) {
		$query = implode(";", $queries);
	} else {
		$query = $queries;
	}
	$DB = captiveportal_opendb();
	if ($DB) {
		$DB->exec("BEGIN TRANSACTION");
		$result = $DB->exec($query);
		if (!$result) {
			captiveportal_syslog("Trying to modify DB returned error: {$DB->lastErrorMsg()}");
		} else {
			$DB->exec("END TRANSACTION");
		}
		$DB->close();
		return $result;
	} else {
		return true;
	}
}

    '00'
?>
