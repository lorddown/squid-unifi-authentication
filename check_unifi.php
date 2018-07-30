#!/usr/bin/php-cgi -q
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

$cpzone = "UNIFI";

require_once('config.php');
require_once('client.php');
require_once('functions.php');
/**
 * site id and MAC address of AP to query
 */
$site_id = 'default';

$db="/var/db/captiveportal_unifi.db";

$banco = captiveportal_opendb();


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

$logged_used_checktime = microtime();
//$data_list_guests  = $unifi_connection->list_guests(168);

//print_r(json_encode($data_list_clients, JSON_PRETTY_PRINT));

$consulta=count($data_list_clients);

if($consulta==0)
    {
    exec("sqlite3 -init 10000  {$db} \"DELETE from captiveportal \"", $ip);
    }


foreach($data_list_clients as $lista_clients)
{
if(isset($lista_clients->_id))
   {
   if($lista_clients->is_guest)
     {
        $_id      = $lista_clients->_id;
        if(isset($lista_clients->_id)!="")
            {
            if(isset($lista_clients->ip))
            {
                $ip = $lista_clients->ip;
            }
            else
            {
                continue;
            }
            }
            foreach($data_list_guests as $lista_guests)
                {
                if(isset($lista_guests->expired))
                {
                if($lista_guests->expired == false)
                    {
                    if(isset($lista_guests->user_id))
                        {
                        if($lista_guests->user_id == $_id)
                           {

                                $authorized_by = $lista_guests->authorized_by;

                                if(isset($lista_guests->mac))
                                    {
                                    $mac = $lista_guests->mac;
                                    }
                                else
                                    {
                                    $mac = "";
                                    }

                                if($authorized_by=='voucher')
                                {
                                   $username =  $authorized_by.$lista_guests->voucher_code;
                                }
                                elseif($authorized_by=='radius')
                                {
//                                   $username =  $lista_guests->radius_username."-".$authorized_by."-".$mac;
                                   $username =  $lista_guests->radius_username."-".$authorized_by;
                                }
                                elseif($authorized_by=='api')
                                {
//                                   $username =  $lista_guests->radius_username."-".$authorized_by."-".$mac;
                                   $username =  $lista_guests->radius_username."-".$authorized_by;
                                }
                                else
                                {
                                   $username =  "unknown_user";
                                }

                                if(isset($lista_guests->hostname))
                                {
                                   $hostname = $lista_guests->hostname;
                                }
                                else
                                {
                                   $hostname = "";
                                }

                                //echo $ip ." - ". $authorized_by ." - ". $username ." - ". $mac ." - ". $_id ." - ". $hostname ."\n";

                                exec("sqlite3 -init 10000 {$db} \"INSERT INTO captiveportal (ip,guest_id,authorized_by,username,logged_user) VALUES ('{$ip}','{$_id}','{$authorized_by}','{$username}','{$logged_used_checktime}')\"", $ip);
                                exec("sqlite3 -init 10000 {$db} \"DELETE FROM captiveportal WHERE logged_user !='{$logged_used_checktime}'\"", $ip);
                           }
                        }
                    }
                }
            }

     }
    }
}



sleep(30);
endwhile;
?>
