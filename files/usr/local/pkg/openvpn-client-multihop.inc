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
	foreach (array_reverse($a_client) as $client) {
		
		$extras['vpnmode'] = "client";
		$extras['id'] = $client['vpnid'];
		// We could write some code here to check if the tunnels are really
		// up or not. For not just asume they are. 
		service_control_stop("openvpn", $extras);
		log_error("Mulithop: {$client['name']} stopped");
		}
		log_error("Mulithop: all clients stopped");
	}

function multihop_start(&$a_client,&$g){
			multihop_stop($a_client,$g);
			foreach ($a_client as $client) {
	
			$extras['vpnmode'] = "client";
			$extras['id'] = $client['vpnid'];

			// Connect to OpenVPN Management Socket and get current status	
			$socket = "unix://{$g['openvpn_base']}/{$client['mgmt']}/sock";
			$status = openvpn_get_client_status($client, $socket);

			sleep(5);
			service_control_start("openvpn", $extras);
	
			$timeout = 10;	
	
			while($status['status'] != "up" && $timeout != 0) {
				// Repeat Statuscheck 
				$status = openvpn_get_client_status($client, $socket);

				sleep(1);

				$timeout--;
			}	
				if($timeout == 0 ) {
					multihop_stop($a_client,$g);
					log_error("Mulithop: ERROR - client {$client['name']} timed out to start");
					return "Couldn't start client: {$client['name']}. Check your OpenVPN logfile.";	
					exit;
				}	
				
		log_error("Mulithop: client {$client['name']} started");

		// While testing it has been proven that it is better to wait a couple
		// of seconds before starting the next tunnel
	}
log_error("Multihop: all clients up");
}

function multihop_autostart(&$a_client){
		foreach ($a_client as $start) {
		$extras['vpnmode'] = "client";
		$extras['id'] = $start['vpnid'];
		service_control_start("openvpn", $extras);
		// XXX - Check pfSense source code for a function
		// that allows to get easy way to connection success 
		// information for now just wait.. and hope for the best. 
		sleep(2);
		log_error("Mulithop: Client started with Autorestart");
	}
	
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
