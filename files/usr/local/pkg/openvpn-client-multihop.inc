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

global $g;

function multihop_stop(&$a_client,&$g) {
	foreach (array_reverse($a_client) as $client) {
		$extras['vpnmode'] = "client";
		$extras['id'] = $client['vpnid'];

		service_control_stop("openvpn", $extras);

		/*
		$socket = "unix://{$g['openvpn_base']}/{$client['mgmt']}/sock";
		$status = openvpn_get_client_status($client, $socket);

		
		if(!empty($status['status'])) {
			service_control_stop("openvpn", $extras);
			while($status['status'] != "down") {
				$status = openvpn_get_client_status($client, $socket);
				sleep(1);
			}	
		}
		 */
		/*
		if(!empty($status['status']))	{
			for ($i=0;$i<3;$i++) {
				$status = openvpn_get_client_status($client, $socket);
				$client['status'] = $status['status']; 
				if($client['status'] == "down") { break; }
					if ($i == 5) {
						return $client['name'];	
					}
			}
		*/
//		return "Nothing to do!";
		log_error("Mulithop: All Clients stopped");
		}
	}

function multihop_start(&$a_client,&$g){
		foreach ($a_client as $client) {
		$extras['vpnmode'] = "client";
		$extras['id'] = $client['vpnid'];

		$socket = "unix://{$g['openvpn_base']}/{$client['mgmt']}/sock";
		$status = openvpn_get_client_status($client, $socket);

		service_control_start("openvpn", $extras);



		$timeout = 10;	
		while($status['status'] != "up" && $timeout != 0) {
			$status = openvpn_get_client_status($client, $socket);
			sleep(1);
			$timeout--;
			log_error("Mulithop: Client {$status['status']} started");
		}	
		
		if($timeout == 0 ) {
		multihop_stop($a_client,$g);
		return "Couldn't start Tunnel: {$client['name']}. Check your OpenVPN logfile.";	
		log_error("Mulithop: Client {$client['name']} not started");
		exit;
		}
	/*
		print_r($status);
		exit;
		for ($i=0;$i<5;$i++) {
			sleep(1);
			$status = openvpn_get_client_status($client, $socket);
			$client['status'] = $status['status']; 
			if($client['status'] == "up") { break; }
				if ($i == 5) {
					return $client['name'];	
					break;
				}
			}
	 */
		
		log_error("Mulithop: Client {$client['name']} started");
		}
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
