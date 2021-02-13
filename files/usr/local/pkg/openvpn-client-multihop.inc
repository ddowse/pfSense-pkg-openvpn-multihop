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


function multihop_stop(&$a_client,&$g) {
	
	$pidfile="/var/run/multihop.pid";
	$oldpid=file_get_contents($pidfile);
	if(file_exists($pidfile)) {
	posix_kill($oldpid,9);	
	log_error("Multihop: Killing old Process {$oldpid}");
	}

	foreach (array_reverse($a_client) as $client) {
		
		$extras['vpnmode'] = "client";
		$extras['id'] = $client['vpnid'];
		service_control_stop("openvpn", $extras);
		log_error("Mulithop: {$client['name']} stopped");
		}
		log_error("Mulithop: all clients stopped");
	}

function multihop_start(&$a_client,&$g){
//			multihop_stop($a_client,$g);
			foreach ($a_client as $client) {
			unset($up);	
			$extras['vpnmode'] = "client";
			$extras['id'] = $client['vpnid'];

			// Connect to OpenVPN Management Socket and get current status	
			$socket = "unix://{$g['openvpn_base']}/{$client['mgmt']}/sock";
			$status = openvpn_get_client_status($client, $socket);
			
			service_control_stop("openvpn", $extras);

			sleep(5);

			log_error("Starting {$client['name']}");
			service_control_start("openvpn", $extras);
	
			for($timeout=0;$timeout <= 5; $timeout++) {
			log_error($timeout);	
			$status = openvpn_get_client_status($client, $socket);
			sleep(1);
				if($status['status'] == "up") { 
					log_error("Mulithop: client {$client['name']} started"); 
					$up=1;
					break;
				}
			}
			if(isset($up)) { 
				continue; 
			} else {
			log_error("Error starting {$client['name']}"); 
			multihop_stop($a_client,$g);
			return "Couldn't start client: {$client['name']}. Check your OpenVPN logfile.";
			break;	
			}
			}
			log_error("Multihop: all clients up");
			if(array_key_exists("keepalive",$client)) {
			log_error("Multihop: Keepalive");
			multihop_keepalive($a_client,$g);		
			} else {
			log_error("Multihop: No Keepalive");	
			}
}


function multihop_keepalive(&$a_client,&$g){

	$pid = pcntl_fork();
	$stat=1;
	$pidfile="/var/run/multihop.pid";
	$oldpid=file_get_contents($pidfile);
	if(file_exists($pidfile)) {
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
			while($stat) {
				foreach($a_client as $client) {
					if(get_status($client,$g)) {
						log_error("Multihop: {$client['name']} up");	
					} else {
						log_error("Multihop: {$client['name']} down");	
						$stat=0;
					}
				sleep(5);
				}
			}
			$notice = "Multihop: {$client['name']} down, restarting Cascade";
			file_notice( $id, $notice, $category = "OpenVPN", $url = "",  $priority = 1, $local_only = true );
			log_error("Multihop: While loop");	
			multihop_start($a_client,$g);
		}
	return 0; //success
}
function multihop_deinstall() {
	global $c_client, $a_client, $config;

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

?>
