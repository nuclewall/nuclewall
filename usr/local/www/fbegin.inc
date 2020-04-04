<script src="javascript/sorttable.js"></script>

<?php
require_once('globals.inc');
require_once('functions.inc');

$uri_split = "";
preg_match("/\/(.*)\?(.*)/", $_SERVER["REQUEST_URI"], $uri_split);

if (empty($uri_split[0])) {
	$pagename = ltrim($_SERVER["REQUEST_URI"], '/');
} else {
	$pagename = $uri_split[1];
}

if (empty($pagename)) {
	$pagename = "index.php";
}

function output_menu($arrayitem, $target="")
{
	$targetinfo = '';
	foreach($arrayitem as $item)
	{
		if($target)
			$targetinfo = "target='{$target}'";
		echo '<li><a ' . $targetinfo . ' href="' . $item[1] . '" class="navlnk">' . $item[0] . '</a></li>' . "\n";
	}
}

// System
$system_menu = array();
$system_menu[] = array("General Settings", "/system.php");
$system_menu[] = array("Access Settings", "/system_advanced_admin.php");
$system_menu[] = array("Gateways", "/system_gateways.php");
if (!isAllowedPage("system_usermanager.php*"))
	$system_menu[] = array("User Management", "/system_usermanager_passwordmg.php");
else
	$system_menu[] = array("User Management", "/system_usermanager.php");

// Interfaces
$interfaces_menu = array();
if (!isset($config['system']['webgui']['noassigninterfaces']))
	$interfaces_menu[] = array("Assign", "/interfaces_assign.php");
$opts = get_configured_interface_with_descr(false, true);
foreach ($opts as $oif => $odescr)
	if (!isset($config['interfaces'][$oif]['ovpn']))
		$interfaces_menu[] = array(htmlspecialchars($odescr), "/interfaces.php?if={$oif}");

// Firewall
$firewall_menu = array();
$firewall_menu[] = array("Aliases", "/firewall_aliases.php");
$firewall_menu[] = array("Rules", "/firewall_rules.php");
$firewall_menu[] = array("Schedules", "/firewall_schedule.php");
$firewall_menu[] = array("Limiters", "/firewall_shaper_vinterface.php");

// Services
$services_menu = array();
$services_menu[] = array("Hotspot", "/hotspot_settings.php");
$services_menu[] = array("5651 Logging", "/services_5651_logging.php");
$services_menu[] = array("DNS Forwarder", "/services_dnsmasq.php");
$services_menu[] = array("DHCP Server", "/services_dhcp.php");

// Status
$status_menu = array();
$status_menu[] = array("Gateways", "/status_gateways.php");
$status_menu[] = array("DHCP Leases", "/status_dhcp_leases.php");
$status_menu[] = array("ARP Table", "/diag_arp.php");
$status_menu[] = array("Services", "/status_services.php");
$status_menu[] = array("Routes", "/diag_routes.php");
$status_menu[] = array("Logs", "/diag_logs.php");
$status_menu[] = array("Network Traffic", "/status_graph.php?if=wan");
$status_menu[] = array("System Activity", "/diag_system_activity.php");

// Diagnostics
$diagnostics_menu = array();
$diagnostics_menu[] = array("Backup/Restore", "/diag_backup.php");
$diagnostics_menu[] = array("Factory Reset", "/diag_defaults.php");

?>

<div id="header">
	<div style="position:absolute;left:865px">
	    <div class="btn-toolbar">
			<div class="btn-group">
				<a title="Poweroff" href="halt.php">
					<button class="btn btn-mini btn-inverse">
						<i class="icon-off icon-white"></i>
					</button>
				</a>
				<a title="Reboot" href="reboot.php">
					<button class="btn btn-mini btn-inverse">
						<i class="icon-repeat icon-white"></i>
					</button>
				</a>
			</div>
			<div class="btn-group">
				<a class="btn dropdown-toggle btn-mini btn-success" data-toggle="dropdown" href="#">
					<?=$HTTP_SERVER_VARS['AUTH_USER']?>
					<span class="caret"></span>
				</a>
				<ul class="dropdown-menu">
					<li><a href="index.php?logout">Logout</a></li>
					<li><a href="system_usermanager_passwordmg.php">Change Password</a></li>
				</ul>
			</div>
		</div>
	</div>
	<a href="/index.php" id="status-link">
		<img src="/themes/nuclewall/images/transparent.gif" border="0">
	</a>
</div>

<div class="title">
    <span class="pgtitle"><?=strtoupper(genhtmltitle($pgtitle));?></span>
</div>

<div id="content">
	<div id="navigation" style="z-index:1000">

		<ul id="menu">
			<li>
				<div><a style="color:white" href="hotspot_status.php">HOTSPOT</a></div>
			</li>
			<li>
				<div>SYSTEM</div>
				<ul>
				<?php
					output_menu($system_menu);
				?>
				</ul>
			</li>
			<li>
				<div>INTERFACES</div>
				<ul>
				<?php
					output_menu($interfaces_menu);
				?>
				</ul>
			</li>
			<li>
				<div>FIREWALL</div>
				<ul>
				<?php
					output_menu($firewall_menu);
				?>
				</ul>
			</li>
			<li>
				<div>SERVICES</div>
				<ul>
				<?
					output_menu($services_menu);
				?>
				</ul>
			</li>
			<li>
				<div>STATUS</div>
				<ul>
				<?php
					output_menu($status_menu);
				?>
				</ul>
			</li>
			<li>
				<div>TOOLS</div>
				<ul id="diag">
				<?
					output_menu($diagnostics_menu);
				?>
				</ul>
			</li>
		</ul>
	</div>
	<script>
		if(Prototype.Browser.Gecko){
			$(navigation).setStyle({top: '14px'});
		}
		else if(Prototype.Browser.WebKit){
			$(navigation).setStyle({top: '16px'});
		}
		else {}
	</script>