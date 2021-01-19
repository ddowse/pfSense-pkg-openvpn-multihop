<?php
require_once("guiconfig.inc");
require_once("openvpn.inc");
require_once("pfsense-utils.inc");
require_once("pkg-utils.inc");
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
	$name=$_POST['name'];
	$ent=array();
	$ent['name']=$a_client_active[$name]['name'];
	$ent['id']=$a_client_active[$name]['vpnid'];
	$a_client[] = $ent;
	write_config("Written");
	log_error("Mulithop: New Client configuration added to the List");
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
		'name', //Name
		'*Client', //Description Asterik Is Inderline
		$pconfig['name'],
		$a_client_select
		))->setHelp('This Client will be added to the list.');

	$section->addInput(new Form_Checkbox(
		'autoconf',
		'Autoconf',
		'Add Autoconf',
		'false'	
		));
	$form->addGlobal(new Form_Input(
		'act',
		null,
		'hidden',
		$act
		));

	$form->addGlobal(new Form_Input(
		'id',
		null,
		'hidden',
		$id
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
					<th><?=gettext("Layer")?></th>
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
	<a href="vpn_openvpn_multihop.php?act=start" class="btn btn-sm btn-success">
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
