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
$system_menu[] = array("Genel Ayarlar", "/system.php");
$system_menu[] = array("Eri�im Ayarlar�", "/system_advanced_admin.php");
$system_menu[] = array("A� Ge�itleri", "/system_gateways.php");
if (!isAllowedPage("system_usermanager.php*"))
	$system_menu[] = array("Kullan�c� Y�netimi", "/system_usermanager_passwordmg.php");
else
	$system_menu[] = array("Kullan�c� Y�netimi", "/system_usermanager.php");

// Interfaces
$interfaces_menu = array();
if (!isset($config['system']['webgui']['noassigninterfaces']))
	$interfaces_menu[] = array("�li�kilendir", "/interfaces_assign.php");
$opts = get_configured_interface_with_descr(false, true);
foreach ($opts as $oif => $odescr)
	if (!isset($config['interfaces'][$oif]['ovpn']))
		$interfaces_menu[] = array(htmlspecialchars($odescr), "/interfaces.php?if={$oif}");

// Firewall
$firewall_menu = array();
$firewall_menu[] = array("Takma Adlar", "/firewall_aliases.php");
$firewall_menu[] = array("Kurallar", "/firewall_rules.php");
$firewall_menu[] = array("Zamanlamalar", "/firewall_schedule.php");
$firewall_menu[] = array("H�z S�n�rlay�c�lar", "/firewall_shaper_vinterface.php");

// Services
$services_menu = array();
$services_menu[] = array("Hotspot", "/hotspot_settings.php");
$services_menu[] = array("5651 Kay�t Tutma", "/services_5651_logging.php");
$services_menu[] = array("DNS ��z�mleyici", "/services_dnsmasq.php");
$services_menu[] = array("DHCP Sunucu", "/services_dhcp.php");


// Status
$status_menu = array();
$status_menu[] = array("A� Ge�itleri", "/status_gateways.php");
$status_menu[] = array("DHCP Da��t�mlar�", "/status_dhcp_leases.php");
$status_menu[] = array("ARP Tablosu", "/diag_arp.php");
$status_menu[] = array("Servisler", "/status_services.php");
$status_menu[] = array("Y�nlendirmeler", "/diag_routes.php");
$status_menu[] = array("Olay G�nl�kleri", "/diag_logs.php");
$status_menu[] = array("A� Trafi�i", "/status_graph.php?if=wan");
$status_menu[] = array("Kaynak Kullan�m�", "/diag_system_activity.php");


// Diagnostics
$diagnostics_menu = array();
$diagnostics_menu[] = array("Yedek Al / Y�kle", "/diag_backup.php");
$diagnostics_menu[] = array("Ayarlar� S�f�rla", "/diag_defaults.php");

?>

<div id="header">
	<div style="position:absolute;left:865px">
	    <div class="btn-toolbar">
			<div class="btn-group">
				<a title="Nuclewall'u kapat" href="halt.php">
					<button class="btn btn-mini btn-inverse">
						<i class="icon-off icon-white"></i>
					</button>
				</a>
				<a title="Nuclewall'u yeniden ba�lat" href="reboot.php">
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
					<li><a href="index.php?logout">Oturumu kapat</a></li>
					<li><a href="system_usermanager_passwordmg.php">Parola de�i�tir</a></li>
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
				<div>S�STEM</div>
				<ul>
				<?php
					output_menu($system_menu);
				?>
				</ul>
			</li>
			<li>
				<div>A� KARTLARI</div>
				<ul>
				<?php
					output_menu($interfaces_menu);
				?>
				</ul>
			</li>
			<li>
				<div>G�VENL�K DUVARI</div>
				<ul>
				<?php
					output_menu($firewall_menu);
				?>
				</ul>
			</li>
			<li>
				<div>SERV�SLER</div>
				<ul>
				<?
					output_menu($services_menu);
				?>
				</ul>
			</li>
			<li>
				<div>DURUM</div>
				<ul>
				<?php
					output_menu($status_menu);
				?>
				</ul>
			</li>
			<li>
				<div>ARA�LAR</div>
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
