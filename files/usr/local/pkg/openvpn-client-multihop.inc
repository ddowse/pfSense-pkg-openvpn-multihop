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


require_once("openvpn.inc");
require_once("service-utils.inc");

function multihop_stop(&$a_client) {
	foreach ($a_client as $stop) {
		$extras['vpnmode'] = "client";
		$extras['id'] = $stop['vpnid'];
		service_control_stop("openvpn", $extras);
		log_error("Mulithop: All Clients stopped");
		}
}

function multihop_start(&$a_client){
		foreach ($a_client as $start) {
		$extras['vpnmode'] = "client";
		$extras['id'] = $start['vpnid'];
		service_control_start("openvpn", $extras);
		// XXX - Check pfSense source code for a function
		// that allows to get easy way to connection success 
		// information for now just wait.. and hope for the best. 
		sleep(2);
		log_error("Mulithop: Client started");
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
