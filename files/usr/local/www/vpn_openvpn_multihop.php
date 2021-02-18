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


require_once("guiconfig.inc");
require_once("openvpn.inc");
require_once("service-utils.inc");

require_once("/usr/local/pkg/openvpn-client-multihop.inc");


global $g, $config, $mode, $input_errors;

if(!is_array($config['installedpackages']['openvpn-multihop'])){
	$config['installedpackages']['openvpn-multihop']=array();
}

if(!is_array($config['installedpackages']['openvpn-multihop']['item'])){
	$config['installedpackages']['openvpn-multihop']['item']=array();
}

// default openvpn mode 
$mode = "client";

// our config array in confix.xml - debug: use viconfig
$a_client = &$config['installedpackages']['openvpn-multihop']['item'];

// openvpn-client array 
$c_client = &$config['openvpn']['openvpn-client'];

// returns all enabled ovpn clients 
$a_active = openvpn_get_active_clients();

// LIST 
// XXX Maybe find cleaner way to do that

// Get all the VPNID's that are already in multihop list
if(empty($a_client)) {
	foreach($a_active as $client ) {
		$vpnid=$client['vpnid'];
		$settings = openvpn_get_settings($mode, $vpnid);
		$a_value[]=$settings['description'];
		$a_id['vpnid'][]=$settings['vpnid'];
	}
} else {
foreach($a_client as $client){
		$a_isconf[]=$client['vpnid'];
	}
// Get all the VPNID's of active clients
foreach($a_active as $client){
		$a_isactive[]=$client['vpnid'];
	}

// Return the values 
$a_isconf_val=array_values($a_isconf);
$a_isactive_val=array_values($a_isactive);

// Return the diff of both 
$a_select=array_diff($a_isactive_val,$a_isconf_val);

// Build select list by "is active" and "not in list"
if(!empty($a_select)) {
	foreach($a_select as $vpnid) {
		$settings = openvpn_get_settings($mode, $vpnid);
		$a_value[]=$settings['description'];
		$a_id['vpnid'][]=$settings['vpnid'];
	}
} else {
	$a_value[]='empty';
}
}


$act = $_REQUEST['act'];

if ($act == "new") {
	//Nothing here yet
}


if ($_POST['save']) {

if ($_POST['start'] == $_POST['exit']) {
	$input_errors[]="Do not use identical clients";	
} else { 

if(isset($_POST['start'])) {
	$id['start']=$_POST['start'];
}	
	if(isset($_POST['exit'])) {
		$id['exit']=$_POST['exit'];
	} 
		if(isset($_POST['norouting'])) {
			$norouting = $_POST['norouting'];
		}
			if(isset($_POST['start'])) {
				//$vpnid = $a_select[$id['start']]['vpnid'];
				$vpnid = $a_id['vpnid'][$id['start']];
				$settings = openvpn_get_settings($mode,$vpnid);
				$settings['route_no_exec'] = "yes";
				$server = server_addr($a_id['vpnid'][$id['exit']]);
				$settings['custom_options'] .=  "\nroute-up \"/usr/local/etc/openvpn-multihop/addroute.sh {$server}\"\n";
				$c_client[$id['start']] = $settings;
				
		}
	

if(isset($_POST['exit'])) {
	if(count($a_client) >= 2) {
		// Get the current exit tunnel and change settings
		$cur_exit = end($a_client);
		$cur_vpnid = $cur_exit['vpnid'];
		$vpnid = $a_id['vpnid'][$id['exit']];
		$settings = openvpn_get_settings($mode,$cur_vpnid);
	
		$settings['route_no_exec'] = "yes";
		
		// set route-up command with ip of the new exit
		$server = server_addr($vpnid);
		$settings['custom_options'] .=  "\nroute-up \"/usr/local/etc/openvpn-multihop/addroute.sh {$server}\"\n";
	
		// get the correct index of the openvpn-client array in config.xml
		// so we can write the new settings to it
		$cur_index = array_search($cur_vpnid,array_column($c_client,'vpnid'));
	
		// save the new settings 
		$c_client[$cur_index] = $settings;
		
		// Add NEW exit
		$settings = openvpn_get_settings($mode,$vpnid);
	
			if ($norouting == "yes") {
				unset($settings['route_no_exec']);
			} else {
				$settings['route_no_exec'] = "yes";
			}
	
		$index = array_search($vpnid,array_column($c_client,'vpnid'));
		
		$c_client[$index] = $settings;
		
		$savemsg="New client added";
	
		$applymsg="Click the Apply button to stop all current clients and to restart with new configuration";
	}

// default, just 2 tunnels
$vpnid = $a_id['vpnid'][$id['exit']];

$settings = openvpn_get_settings($mode,$vpnid);

// In case one does not want to default route everything to the tunnel
if ($norouting == "yes") {
	unset($settings['route_no_exec']);
} else {
	$settings['route_no_exec'] = "yes";
}

$index = array_search($vpnid,array_column($c_client,'vpnid'));

$c_client[$index] = $settings;

foreach($id as $add=> $new) {
	$ent=array();
	$ent['name']=$a_value[$new];
	$ent['vpnid']=$a_id['vpnid'][$new];
	$ent['mgmt'] = "client{$a_id['vpnid'][$new]}";
	if(isset($_POST['keepalive'])) {
	$ent['keepalive'] = $_POST['keepalive'];
	}
	$a_client[] = $ent;
	//log_error("Mulithop: New client added to configuration");
}
	write_config("Written");

	//log_error("Mulithop: New list created");

	if(!$savemsg) {
	$savemsg="New Multihop list created" ;

	if(!isset($applymsg)){
		$applymsg="The Changes must be applied for them to take effect.";
		}
	}

	}
	}
}
if ($act == "del") {
	// Get the array and loop over it, use vpnid to get correct
	// openvpn-client 
	multihop_stop($a_client,$g);
	
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
			//log_error("Mulithop: routing options removed");
			break;
		}
	$idx++;
	}
}
	foreach ($a_client as $del) {
	unset($config['installedpackages']['openvpn-multihop']['item']);
	}
	$config['installedpackages']['openvpn-multihop']['item']=array();
	$a_client = &$config['installedpackages']['openvpn-multihop']['item'];
	write_config("Mulithop: list deleted ");
	//log_error("Mulithop: list deleted");

	$warnmsg="Multihop: Configuration deleted";

}

if ($act == "stop") {
	multihop_stop($a_client,$g);
	$warnmsg="All tunnels down";
}

if ($act == "start") {
	if (!empty($ret = multihop_start($a_client,$g))) {
	$warnmsg="{$ret}";
	} else {
	$savemsg ="All tunnels started";
	}
}

if($_POST['apply']) {
	if (!empty($ret = multihop_start($a_client,$g))) {
	$warnmsg="{$ret}";
	} else {
	$savemsg ="All tunnels started";
	}
}

$pgtitle = array("OpenVPN", "Mulithop");

include("head.inc");

if (!$savemsg) {
	$savemsg = "";
}

if ($input_errors) {
	print_input_errors($input_errors,);
}

if ($warnmsg) {
	print_info_box($warnmsg, 'danger');

}
if ($savemsg) {
	print_info_box($savemsg, 'success');

}

if ($applymsg) {
	print_apply_box(gettext($applymsg));
}

$tab_array = array();
$tab_array[] = array(gettext("Server"), false, "vpn_openvpn_server.php");
$tab_array[] = array(gettext("Client"), false, "vpn_openvpn_client.php");
$tab_array[] = array(gettext("Client Specific Overrides"), false, "vpn_openvpn_csc.php");
$tab_array[] = array(gettext("Wizards"), false, "wizard.php?xml=openvpn_wizard.xml");
add_package_tabs("OpenVPN", $tab_array);
display_top_tabs($tab_array);

if ($act=="new"):

if ($a_value[0] == "empty") {
	$warnmsg="No more OpenVPN Clients available";
	print_info_box($warnmsg, 'warning');
} else  {
$form = new Form();

$section = new Form_Section('Add Client');

if(!empty($a_client)) { 
	$section->addInput(new Form_Select(
		'exit', //Name
		'New Exit', // Frontend Text
		$a_value['description'],
		$a_value
		))->setHelp('This Client will be added to the list of tunnels and act as the  new exit');
	$form->add($section);

	$section->addInput(new Form_Checkbox(
		'norouting',
		'Set Routing',
		'Add default route',
		'true'	
		))->setHelp('Uncheck if you *do not* want to set default route to exit tunnel.');
		
	$section->addInput(new Form_Checkbox(
		'keepalive',
		'Enable Keepalive',
		'Keepalive',
		'true'	
		))->setHelp('Uncheck to disable Restart in sequence, if any of the tunnels apear to be down');

} else {
	$section->addInput(new Form_Select(
		'start',
		'Start', 
		$a_value['description'],
		$a_value
		))->setHelp('This client will be the first tunnel');

	$section->addInput(new Form_Select(
		'exit', 
		'Exit', 
		$a_value['description'],
		$a_value
		))->setHelp('This client will the exit tunnel');
	$form->add($section);

	$section->addInput(new Form_Checkbox(
		'norouting',
		'Set Routing',
		'Add default route',
		'true'	
		))->setHelp('Uncheck if you *do not* want to set default route to exit tunnel.');

	$section->addInput(new Form_Checkbox(
		'keepalive',
		'Enable Keepalive',
		'Keepalive',
		'true'	
		))->setHelp('Uncheck to disable Restart in sequence, if any of the tunnels apear to be down');
	} 
}
endif;

print($form);

?>


<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext('Chain')?></h2></div>
		<div class="panel-body table-responsive">
		<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap table-rowdblclickedit" data-sortable>
			<thead>
				<tr>
					<!-- <th><?//=gettext("VPN ID")?></th> -->
					<th><?=gettext("Start No.")?></th>
					<th><?=gettext("Name")?></th>
					<th><?=gettext("Status"); ?></th>
				</tr>
			</thead>

			<tbody>
<?php
	$i = 1;
	foreach ($a_client as $value):
?>
				<tr>
					<td>
						<i class="text fa fa-arrow-down"></i>
						<?=htmlspecialchars($i)?>
					</td>
					<td>
						<?=htmlspecialchars($value['name'])?>
					</td>
					<td>
						<?= (get_status($value,$g)) ? '<i class="fa fa-check-circle icon-embed-btn" style="color:green"></i>' 
						: '<i class="fa fa-times-circle icon-embed-btn" style="color:red"></i>'; ?>
					</td>
				</tr>
<?php
		$i++;
	endforeach;
?>
			</tbody>
		</table>
	</div>
</div>
<form action="vpn_openvpn_multihop.php" method="post">
<nav class="action-buttons">
		<button class="btn btn-success btn-sm" type="submit" name="act" value="new">
			<i class="fa fa-plus icon-embed-btn"> </i>
			Add
		</button>
		<button class="btn btn-success btn-sm" type="submit" name="act" value="start">
			<i class="fa fa-play-circle icon-embed-btn"> </i>
			Start
		</button>
<!--
		<button class="btn btn-success btn-sm" type="submit" name="act" value="autostart">
			<i class="fa fa-retweet icon-embed-btn"> </i>
			Autorestart
		</button>
-->
		<button class="btn btn-danger btn-sm" type="submit" name="act" value="stop">
		<i class="text-danger fa fa-times-circle icon-embed-btn"></i>
			Stop
		</button>
		<button class="btn btn-danger btn-sm" type="submit" name="act" value="del">
		<i class="text-danger fa fa-trash icon-embed-btn"></i>
			Delete
		</button>
</nav>
		</br>
		</br>
</form>
<div class="alert alert-info clearfix" role="alert">
	<div class="pull-left"><strong>Quick Guide</strong>
	<br>This Multihop package cascades any OpenVPN clients. The OpenVPN connection always goes via the last hop.
	<br>Make sure that NAT on VPN Interfaces is configured properly.
	<br>Check for <a href="https://github.com/ddowse/pfSense-pkg-openvpn-multihop">Details</a> 
	</div>
</div>

<?php include("foot.inc");?>
