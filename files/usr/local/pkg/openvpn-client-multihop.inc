<?php
/*
* vpn_openvpn_multihop.php
*
* Copyright (c) 2021 Daniel Dowse, (https://daemonbytes.net)
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


require_once("globals.inc");
require_once("openvpn.inc");
require_once("service-utils.inc");

global $g, $a_client;

// Returns the key of ['server_addr'] this is the to be used
// to set the route-up command
function server_addr(&$vpnid) {
		$settings = openvpn_get_settings($mode,$vpnid);
		$server = $settings['server_addr'];
		return $server;	
}

// Returns the connection status via OpenVPN Management
function get_status(&$client,&$g) {
	$socket = "unix://{$g['openvpn_base']}/{$client['mgmt']}/sock";
	$status = openvpn_get_client_status($client, $socket);
	if($status['status'] != "up" ) {
		return 0;
	} else {
		return 1;
	}
}


// Kills background process and removes PID file
function multihop_killkeepalive() {

$pidfile="/var/run/multihop.pid";

if(file_exists($pidfile)) {
	$oldpid=file_get_contents($pidfile);
	log_error("Multihop: Killing old Process {$oldpid}");
	posix_kill($oldpid,9);	
	unlink($pidfile);
	log_error("Multihop: deleted {$pidfile}");
	}
}

// Stops all clients - called by multihop_keepalive()
function multihop_keepstop(&$a_client,&$g) {

foreach (array_reverse($a_client) as $client) {

	$extras['vpnmode'] = "client";
	$extras['id'] = $client['vpnid'];
	service_control_stop("openvpn", $extras);
	sleep(1);
	log_error("Mulithop: {$client['name']} stopped");
	}
log_error("Mulithop: all clients stopped");
}

// Stops all clients and background process 
// called by $_POST['stop'] in vpn_openvpn_multihop.php
function multihop_stop(&$a_client,&$g) {

multihop_killkeepalive();
	
	foreach (array_reverse($a_client) as $client) {
	
		$extras['vpnmode'] = "client";
		$extras['id'] = $client['vpnid'];
		service_control_stop("openvpn", $extras);
		log_error("Mulithop: {$client['name']} stopped");
	}
log_error("Mulithop: all clients stopped");
}

// Start all clients from $a_client[] 
function multihop_start(&$a_client,&$g){

	multihop_killkeepalive();

	foreach ($a_client as $client) {
		$extras['vpnmode'] = "client";
		$extras['id'] = $client['vpnid'];

		// Make sure that the tunnel is down
		service_control_stop("openvpn", $extras);
		// pfsense reconfigure
		sleep(5);

		log_error("Multihop: Starting {$client['name']}");
		service_control_start("openvpn", $extras);

		// TODO: Add a WebGUI method to adjust
		$timeout = 6;

		while(!get_status($client,$g) &&  $timeout >= 0 ) {
			sleep(1);
			$timeout--;
		}

		if(!$timeout) {
			log_error("Multihop: Error starting {$client['name']}"); 
			multihop_stop($a_client,$g);
			return "Couldn't start client: {$client['name']}. Check your OpenVPN logfile.";
		} 

	log_error("Multihop: {$client['name']} up");	
	}

log_error("Multihop: all clients up");

	if(array_key_exists("keepalive",$client)) {
		multihop_keepalive($a_client,$g);		
	} else {
		log_error("Multihop: No Keepalive set");	
	}
}

function multihop_keepstart(&$a_client,&$g){

	multihop_keepstop($a_client,$g);		

	foreach ($a_client as $client) {
		$extras['vpnmode'] = "client";
		$extras['id'] = $client['vpnid'];

		//service_control_stop("openvpn", $extras);
		sleep(5);
		service_control_start("openvpn", $extras);
	}

multihop_keepalive($a_client,$g);		
}

function multihop_keepalive(&$a_client,&$g){

$pid = pcntl_fork();
$pidfile="/var/run/multihop.pid";
if(file_exists($pidfile)) {
	$oldpid=file_get_contents($pidfile);
	posix_kill($oldpid,9);	
	log_error("Multihop: Killing old Process {$oldpid}");
	}

	if($pid == -1){
		log_error("Multihop: ERROR - could not daemonize process");
		return 1; //error
	} else if($pid) {
		return 0;
	} else {
		$child_pid = getmypid();
		file_put_contents($pidfile, "{$child_pid}");
		log_error("Multihop: Keepalive started PID {$child_pid}");
		while(file_exists($pidfile)) {
			foreach($a_client as $client) {
				if(!get_status($client,$g)) {
					$notice = "Multihop: {$client['name']} down, restarting Cascade";
					log_error("Multihop: {$client['name']} down");	
					multihop_keepstart($a_client,$g);
				}
			sleep(3);
			}
		}
	}
return 0; //success
}

function multihop_deinstall() {
	multihop_stop($a_client,$g);

	global $c_client, $a_client, $config;

if(is_array($config['installedpackages']['openvpn-multihop']['item'])){
		$a_client = &$config['installedpackages']['openvpn-multihop']['item'];
		$c_client = &$config['openvpn']['openvpn-client'];
	
		foreach($a_client as $item) {
			$vpnid = $item['vpnid'];
			$settings = openvpn_get_settings($mode,$vpnid);
			
			// Get custom_options and split it into an array
			// so we can parse it an find route-up line and 
			// remove it eventually 
	
			$l_custom = $settings['custom_options'];
			$a_custom = explode("\n",$l_custom);
			
			$idx=0;
	
			foreach($a_custom as $parse ){
				if (preg_match("/route-up/",$parse)) {
				$index = array_search($vpnid,array_column($c_client,'vpnid'));
				unset($a_custom[$idx]);
				$l_custom = implode("\n",$a_custom);
				$settings['custom_options'] = $l_custom;
				unset($settings['route_no_exec']);
				$c_client[$index] = $settings;
				log_error("Mulithop: Route CMD deleted");
				break;
			}
		$idx++;
		}
	}
	unset($config['installedpackages']['openvpn-multihop']);
	write_config("Mulithop: Package configuration removed ");
	print "\nMulithop: Package configuration removed\n";
}

unset($config['installedpackages']['openvpn-multihop']);
write_config("Mulithop: Package configuration removed ");
print "\nMulithop: Package configuration removed\n";
}
?>
