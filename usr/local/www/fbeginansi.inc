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

if (($pagename == 'pkg.php') || ($pagename == 'pkg_edit.php'))
{
	$param_split = explode('&', $uri_split[2]);
	foreach ($param_split as $param) {
		if (substr($param, 0, 4) == "xml=") {
			$xmlfile = explode('=', $param);
			$pagename = $xmlfile[1];
		}
	}
}

function return_ext_menu($section) {
	global $config;
	$htmltext = "";
	$extarray = array();
	if($config['installedpackages']['menu'] <> "") {
		foreach($config['installedpackages']['menu'] as $menuitem) {
			if($menuitem['section'] != $section) continue;
			if($menuitem['url'] <> "") {
			 	$addresswithport = getenv("HTTP_HOST");
				$colonpos = strpos($addresswithport, ":");
				if ($colonpos !== False){
					//my url is actually just the IP address of the pfsense box
					$myurl = substr($addresswithport, 0, $colonpos);
				} else {
					$myurl = $addresswithport;
				}
				$description = str_replace('$myurl', $myurl, $menuitem['url']);
			} else {
				$description = '/pkg.php?xml=' . $menuitem['configfile'];
			}
			$extarray[] = array($menuitem['name'], $description);
		}
	}
	return $extarray;
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
$system_menu[] = array("Eriþim Ayarlarý", "/system_advanced_admin.php");
$system_menu[] = array("Að Geçitleri", "/system_gateways.php");
if (!isAllowedPage("system_usermanager.php*"))
	$system_menu[] = array("Kullanýcý Yönetimi", "/system_usermanager_passwordmg.php");
else
	$system_menu[] = array("Kullanýcý Yönetimi", "/system_usermanager.php");
$system_menu = array_merge($system_menu, return_ext_menu("System"));

// Interfaces
$interfaces_menu = array();
if (!isset($config['system']['webgui']['noassigninterfaces']))
	$interfaces_menu[] = array("Ýliþkilendir", "/interfaces_assign.php");
$opts = get_configured_interface_with_descr(false, true);
foreach ($opts as $oif => $odescr)
	if (!isset($config['interfaces'][$oif]['ovpn']))
		$interfaces_menu[] = array(htmlspecialchars($odescr), "/interfaces.php?if={$oif}");
$interfaces_menu = msort(array_merge($interfaces_menu, return_ext_menu("Interfaces")),0);

// Firewall
$firewall_menu = array();
$firewall_menu[] = array("Takma Adlar", "/firewall_aliases.php");
$firewall_menu[] = array("Kurallar", "/firewall_rules.php");
$firewall_menu[] = array("Zamanlamalar", "/firewall_schedule.php");
$firewall_menu[] = array("Hýz Sýnýrlayýcýlar", "/firewall_shaper_vinterface.php");

// Services
$services_menu = array();
$services_menu[] = array("Hotspot", "/hotspot_settings.php");
$services_menu[] = array("5651 Kayýt Tutma", "/services_5651_logging.php");
$services_menu[] = array("DNS Çözümleyici", "/services_dnsmasq.php");
$services_menu[] = array("DHCP Sunucu", "/services_dhcp.php");
$services_menu = array_merge($services_menu, return_ext_menu("Services"));


// Status
$status_menu = array();
$status_menu[] = array("Að Geçitleri", "/status_gateways.php");
$status_menu[] = array("DHCP Daðýtýmlarý", "/status_dhcp_leases.php");
$status_menu[] = array("ARP Tablosu", "/diag_arp.php");
$status_menu[] = array("Servisler", "/status_services.php");
$status_menu[] = array("Yönlendirmeler", "/diag_routes.php");
$status_menu[] = array("Olay Günlükleri", "/diag_logs.php");
$status_menu[] = array("Að Trafiði", "/status_graph.php?if=wan");
$status_menu[] = array("Kaynak Kullanýmý", "/diag_system_activity.php");


// Diagnostics
$diagnostics_menu = array();
$diagnostics_menu[] = array("Yedek Al / Yükle", "/diag_backup.php");
$diagnostics_menu[] = array("Ayarlarý Sýfýrla", "/diag_defaults.php");

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
				<a title="Nuclewall'u yeniden baþlat" href="reboot.php">
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
					<li><a href="system_usermanager_passwordmg.php">Parola deðiþtir</a></li>
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
				<div>SÝSTEM</div>
				<ul>
				<?php
					output_menu($system_menu);
				?>
				</ul>
			</li>
			<li>
				<div>AÐ KARTLARI</div>
				<ul>
				<?php
					output_menu($interfaces_menu);
				?>
				</ul>
			</li>
			<li>
				<div>GÜVENLÝK DUVARI</div>
				<ul>
				<?php
					output_menu($firewall_menu);
				?>
				</ul>
			</li>
			<li>
				<div>SERVÝSLER</div>
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
				<div>ARAÇLAR</div>
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
