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

global $config;

if(!is_array($config['installedpackages']['openpvn-multihop'])){
	$config['installedpackages']['openpvn-multihop']=array();
}

if(!is_array($config['installedpackages']['openpvn-multihop']['item'])){
	$config['installedpackages']['openpvn-multihop']['item']=array();
}

$a_client = &$config['installedpackages']['openpvn-multihop']['item'];

$a_client_active = openvpn_get_active_clients();

$a_client_select=array();
	
foreach($a_client_active as $descr) {
	$a_client_select[]=$descr['name'];
}

$act = $_REQUEST['act'];

if ($act == "new") {
	//Nothing here yet
}

if ($_POST['save']) {

		$id[]=$_POST['start'];
		$id[]=$_POST['middle'];
		$id[]=$_POST['exit'];

		$autoconf=$_POST['autoconf'];

		if ($autoconf == "yes") {
			// Do not exec route commando since we add our own and set the right routing
			$set_noroute_start = &$config['openvpn']['openvpn-client'][$id[0]]['route_no_exec'];
			$set_noroute_middle = &$config['openvpn']['openvpn-client'][$id[1]]['route_no_exec'];


			$set_noroute_start = "yes";
			$set_noroute_middle = "yes";
			unset($config['openvpn']['openvpn-client'][$id[2]]['route_no_exec']);
	
			// Get the Server IP for the route-up command
			$server_middle = &$config['openvpn']['openvpn-client'][$id[1]]['server_addr'];
			$server_exit = &$config['openvpn']['openvpn-client'][$id[2]]['server_addr'];
	
			$start_routecmd =  "\nroute-up \"/usr/local/etc/openvpn-multihop/addroute.sh {$server_middle}\"\n";
			$middle_routecmd = "\nroute-up \"/usr/local/etc/openvpn-multihop/addroute.sh {$server_exit}\"\n";
			
			$conf_start = &$config['openvpn']['openvpn-client'][$id[0]]['custom_options']; 
			$conf_middle = &$config['openvpn']['openvpn-client'][$id[1]]['custom_options'];
	
			/// XXX - I dont think this is working. 
			if(!preg_match('/{$start_routecmd}/',$conf_start)) { 
					$conf_start .= $start_routecmd;
			}

			if(!preg_match('/{$middle_routecmd}/',$conf_middle)) {
					$conf_middle .= $middle_routecmd;
			}
		}

		foreach($id as $tunnel => $member) {
			$ent=array();
			$ent['name']=$a_client_active[$member]['name'];
			$ent['id']=$a_client_active[$member]['vpnid'];

			$a_client[] = $ent;
			write_config("Written");
			$a_client = &$config['installedpackages']['openpvn-multihop']['item'];
			log_error("Mulithop: New Client configuration added to the List");
		}

	log_error("Mulithop:New List created");
	header("Location: vpn_openvpn_multihop.php");
	exit;
}

if ($act == "del") {
	unset($config['installedpackages']['openpvn-multihop']);
	write_config("Mulithop: List deleted ");
	log_error("Mulithop: List deleted");
	print_info_box('Success');
	header("Location: vpn_openvpn_multihop.php");
	exit;
}

if ($act == "stop") {
		foreach ($a_client as $stop) {
		$extras['vpnmode'] = "client";
		$extras['id'] = $stop['id'];
		service_control_stop("openvpn", $extras);
		log_error("Mulithop: All Clients stopped");
	}

	print_info_box('Success');
	header("Location: vpn_openvpn_multihop.php");
	exit;
}

if ($act == "start") {
		foreach ($a_client as $start) {
		$extras['vpnmode'] = "client";
		$extras['id'] = $start['id'];
		service_control_start("openvpn", $extras);
		sleep(3);
		log_error("Mulithop: Client started");
	}

	log_error("Mulithop: All Clients started");
}

if ($act == "autorestart") {
		foreach (array_reverse($a_client) as $start) {
		$extras['vpnmode'] = "client";
		$extras['id'] = $start['id'];
		service_control_start("openvpn", $extras);
		sleep(3);
		log_error("Mulithop: Client started");
	}

	log_error("Mulithop: All Clients started");

}

$pgtitle = array("OpenVPN", "Client Mulithop");

include("head.inc");


if (!$savemsg) {
	$savemsg = "";
}

if ($input_errors) {
	print_input_errors($input_errors);
}

if ($savemsg) {
	print_info_box($savemsg, 'success');
}


$tab_array = array();
$tab_array[] = array(gettext("Server"), false, "vpn_openvpn_server.php");
$tab_array[] = array(gettext("Client"), false, "vpn_openvpn_client.php");
$tab_array[] = array(gettext("Client Specific Overrides"), false, "vpn_openvpn_csc.php");
$tab_array[] = array(gettext("Wizards"), false, "wizard.php?xml=openvpn_wizard.xml");
add_package_tabs("OpenVPN", $tab_array);
display_top_tabs($tab_array);

if ($act=="new"):
	$form = new Form();

	$section = new Form_Section('Add Client');

	$section->addInput(new Form_Select(
		'start', //Name
		'*Start', //Description Asterik Is Inderline
		$a_client_select['name'],
		$a_client_select,
		))->setHelp('This Client will be used as the first tunnel.');

	$section->addInput(new Form_Select(
		'middle', //Name
		'*Middle', //Description Asterik Is Inderline
		$a_client_select['name'],
		$a_client_select
		))->setHelp('This Client will be used as the second/center tunnel.');

	$section->addInput(new Form_Select(
		'exit', //Name
		'*Exit', //Description Asterik Is Inderline
		$a_client_select['name'],
		$a_client_select
		))->setHelp('This Client will be used as the exit tunnel.');

	$section->addInput(new Form_Checkbox(
		'autoconf',
		'Autoconfigure',
		'Add Routing Options',
		'false'	
		))->setHelp('This is somewhat mendatory. If you do not have put route-up commands in the custom-options of the clients configuration you will want this to be checked');

	$form->addGlobal(new Form_Input(
		'act',
		null,
		'hidden',
		$act
		));


$form->add($section);
endif;

print($form);
//END PHP
print_info_box(gettext("DISCLAIMER: DEVELOPMENT VERSION - Last added Client will be the EXIT to the Internet ((YOU))->1->2-3->((INET))"  ));
?>

<div class="panel panel-default">
	<div class="panel-heading"><h2 class="panel-title"><?=gettext('OpenVPN Client Ordering')?></h2></div>
		<div class="panel-body table-responsive">
		<table class="table table-striped table-hover table-condensed sortable-theme-bootstrap table-rowdblclickedit" data-sortable>
			<thead>
				<tr>
					<th><?=gettext("VPN ID")?></th>
					<th><?=gettext("Description")?></th>
					<th><?=gettext("Status"); ?></th>
				</tr>
			</thead>

			<tbody>
<?php
	$i = 0;
	foreach ($a_client as $t_client):
?>
				<tr>
					<td>
						<?=htmlspecialchars($t_client['id'])?>
					</td>
					<td>
						<?=htmlspecialchars($t_client['name'])?>
					</td>
					<td>
						<?php $ssvc = find_service_by_openvpn_vpnid($t_client['id']); ?>
						<?= get_service_status_icon($ssvc, false, true); ?>
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
<nav class="action-buttons">
	<a href="vpn_openvpn_multihop.php?act=new" class="btn btn-primary btn-sm btn-success">
		<i class="fa fa-plus icon-embed-btn"></i>
		<?=gettext("Add")?>
	</a>
</nav>

<nav class="action-buttons">
	<a href="vpn_openvpn_multihop.php?act=start" class="btn btn-sm btn-success">
		<i class="fa fa-play-circle icon-embed-btn"></i>
		<?=gettext("Start")?>
	</a>
	<a href="vpn_openvpn_multihop.php?act=autorestart" class="btn btn-sm btn-success">
		<i class="fa fa-play-circle icon-embed-btn"></i>
		<?=gettext("Autorestart")?>
	</a>
	<a href="vpn_openvpn_multihop.php?act=stop" class="btn btn-sm btn-success">
		<i class="text-danger fa fa-times-circle icon-embed-btn"></i>
		<?=gettext("Stop")?>
	</a>
	<a href="vpn_openvpn_multihop.php?act=del" class="btn btn-danger btn-sm">
		<i class="text-danger fa fa-trash icon-embed-btn"></i>
		<?=gettext("Delete")?>
	</a>
</nav>

<?php include("foot.inc");?>
