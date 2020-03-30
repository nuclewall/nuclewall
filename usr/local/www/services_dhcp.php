<?php
/*
	services_dhcp.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.

	Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>.
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/

require('guiconfig.inc');

if(!$g['services_dhcp_server_enable'])
{
	Header("Location: /");
	exit;
}

function dhcp_clean_leases()
{
	global $g, $config;
	$leasesfile = "{$g['dhcpd_chroot_path']}/var/db/dhcpd.leases";
	if (!file_exists($leasesfile))
		return;

	$staticmacs = array();
	foreach($config['interfaces'] as $ifname => $ifarr)
		if (is_array($config['dhcpd'][$ifname]['staticmap']))
			foreach($config['dhcpd'][$ifname]['staticmap'] as $static)
				$staticmacs[] = $static['mac'];

	$leases_contents = explode("\n", file_get_contents($leasesfile));
	$newleases_contents = array();
	$i=0;
	while ($i < count($leases_contents)) {

		if (substr($leases_contents[$i], 0, 6) == "lease ") {
			$templease = array();
			$thismac = "";

			do {
				if (substr($leases_contents[$i], 0, 20) == "  hardware ethernet ")
					$thismac = substr($leases_contents[$i], 20, 17);
				$templease[] = $leases_contents[$i];
				$i++;
			} while ($leases_contents[$i-1] != "}");

			if (! in_array($thismac, $staticmacs))
				$newleases_contents = array_merge($newleases_contents, $templease);
		} else {

			$newleases_contents[] = $leases_contents[$i];
			$i++;
		}
	}

	$fd = fopen($leasesfile, 'w');
	fwrite($fd, implode("\n", $newleases_contents));
	fclose($fd);
}

$if = $_GET['if'];
if ($_POST['if'])
	$if = $_POST['if'];

$iflist = get_configured_interface_with_descr();

if (!$if || !isset($iflist[$if]))
{
	foreach ($iflist as $ifent => $ifname)
	{
		$oc = $config['interfaces'][$ifent];
		if ((is_array($config['dhcpd'][$ifent]) && !isset($config['dhcpd'][$ifent]['enable']) && (!is_ipaddr($oc['ipaddr']))) ||
			(!is_array($config['dhcpd'][$ifent]) && (!is_ipaddr($oc['ipaddr']))))
			continue;
		$if = $ifent;
		break;
	}
}

if (is_array($config['dhcpd'][$if]))
{
	if (is_array($config['dhcpd'][$if]['range']))
	{
		$pconfig['range_from'] = $config['dhcpd'][$if]['range']['from'];
		$pconfig['range_to'] = $config['dhcpd'][$if]['range']['to'];
	}

	$pconfig['gateway'] = $config['dhcpd'][$if]['gateway'];
	$pconfig['domain'] = $config['dhcpd'][$if]['domain'];
	list($pconfig['dns1'],$pconfig['dns2']) = $config['dhcpd'][$if]['dnsserver'];
	$pconfig['enable'] = isset($config['dhcpd'][$if]['enable']);
	$pconfig['denyunknown'] = isset($config['dhcpd'][$if]['denyunknown']);
	$pconfig['netmask'] = $config['dhcpd'][$if]['netmask'];

	if (!is_array($config['dhcpd'][$if]['staticmap']))
		$config['dhcpd'][$if]['staticmap'] = array();

	$a_maps = &$config['dhcpd'][$if]['staticmap'];
}

$ifcfgip = $config['interfaces'][$if]['ipaddr'];
$ifcfgsn = $config['interfaces'][$if]['subnet'];

$dhcrelay_enabled = false;
$dhcrelaycfg = $config['dhcrelay'];

if(is_array($dhcrelaycfg))
{
	foreach ($dhcrelaycfg as $dhcrelayif => $dhcrelayifconf)
	{
		if (isset($dhcrelayifconf['enable']) && isset($iflist[$dhcrelayif]) &&
			(!link_interface_to_bridge($dhcrelayif)))
			$dhcrelay_enabled = true;
	}
}

function is_inrange($test, $start, $end)
{
	if ( (ip2ulong($test) < ip2ulong($end)) && (ip2ulong($test) > ip2ulong($start)) )
		return true;
	else
		return false;
}

if ($_POST)
{
	unset($input_errors);
	$pconfig = $_POST;

	if ($_POST['enable']) {
		$reqdfields = explode(" ", "range_from range_to");
		$reqdfieldsn = array("Mevcut IP Adresi Dağıtım Aralığı(Başlangıç)", "Mevcut IP Adresi Dağıtım Aralığı(Bitiş)");

		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

		if (($_POST['range_from'] && !is_ipaddr($_POST['range_from'])))
			$input_errors[] = "Geçerli bir IP adresi dağıtım aralığı(başlangıç) girilmelidir.";
		if (($_POST['range_to'] && !is_ipaddr($_POST['range_to'])))
			$input_errors[] = "Geçerli bir IP adresi dağıtım aralığı(bitiş) girilmelidir.";
		if (($_POST['gateway'] && !is_ipaddr($_POST['gateway'])))
			$input_errors[] = "Ağ geçidi için geçerli bir IP adresi girilmelidir";
		$parent_ip = get_interface_ip($_POST['if']);
		if (is_ipaddr($parent_ip) && $_POST['gateway']) {
			$parent_sn = get_interface_subnet($_POST['if']);
			if(!ip_in_subnet($_POST['gateway'], gen_subnet($parent_ip, $parent_sn) . "/" . $parent_sn) && !ip_in_interface_alias_subnet($_POST['if'], $_POST['gateway']))
				$input_errors[] = sprintf("%s ağ geçidi seçilen arayüze ait bir IP adresi değil.", $_POST['gateway']);
		}
		if (($_POST['dns1'] && !is_ipaddr($_POST['dns1'])) || ($_POST['dns2'] && !is_ipaddr($_POST['dns2'])))
			$input_errors[] = "Geçerli DNS sunucu adresleri girilmelidir.";

		if (($_POST['domain'] && !is_domain($_POST['domain'])))
			$input_errors[] = "Geçerli bir alan adı girilmelidir";


		if(gen_subnet($ifcfgip, $ifcfgsn) == $_POST['range_from'])
			$input_errors[] = "Ağ adresini IP adresi dağıtımı başlangıç adresi olarak kullanamazsınız.";
		if(gen_subnet_max($ifcfgip, $ifcfgsn) == $_POST['range_to'])
			$input_errors[] = "Ağ adresini IP adresi dağıtımı bitiş adresi olarak kullanamazsınız.";

		$noip = false;
		if(is_array($a_maps))
			foreach ($a_maps as $map)
				if (empty($map['ipaddr']))
					$noip = true;

		if (!$input_errors)
		{
			$subnet_start = ip2ulong(long2ip32(ip2long($ifcfgip) & gen_subnet_mask_long($ifcfgsn)));
			$subnet_end = ip2ulong(long2ip32(ip2long($ifcfgip) | (~gen_subnet_mask_long($ifcfgsn))));

			if ((ip2ulong($_POST['range_from']) < $subnet_start) || (ip2ulong($_POST['range_from']) > $subnet_end) ||
			    (ip2ulong($_POST['range_to']) < $subnet_start) || (ip2ulong($_POST['range_to']) > $subnet_end)) {
				$input_errors[] = "Belirtilen dağıtım aralığı mevcut ağın dışında.";
			}

			if (ip2ulong($_POST['range_from']) > ip2ulong($_POST['range_to']))
				$input_errors[] = "Dağıtım aralığı hatalı (Başlangıç adresi bitiş adresinden daha büyük).";

			$dynsubnet_start = ip2ulong($_POST['range_from']);
			$dynsubnet_end = ip2ulong($_POST['range_to']);
			if (is_array($a_maps)) {
				foreach ($a_maps as $map) {
					if (empty($map['ipaddr']))
						continue;
					if ((ip2ulong($map['ipaddr']) > $dynsubnet_start) &&
						(ip2ulong($map['ipaddr']) < $dynsubnet_end)) {
						$input_errors[] = "Otomatik dağıtım aralığı sabit IP dağıtımlarıyla çakışmamalıdır";
						break;
					}
				}
			}
		}
	}

	if (!$input_errors)
	{
		if (!is_array($config['dhcpd'][$if]))
			$config['dhcpd'][$if] = array();
		if (!is_array($config['dhcpd'][$if]['range']))
			$config['dhcpd'][$if]['range'] = array();

		$config['dhcpd'][$if]['range']['from'] = $_POST['range_from'];
		$config['dhcpd'][$if]['range']['to'] = $_POST['range_to'];
		$config['dhcpd'][$if]['netmask'] = $_POST['netmask'];

		unset($config['dhcpd'][$if]['dnsserver']);
		if ($_POST['dns1'])
			$config['dhcpd'][$if]['dnsserver'][] = $_POST['dns1'];
		if ($_POST['dns2'])
			$config['dhcpd'][$if]['dnsserver'][] = $_POST['dns2'];

		$config['dhcpd'][$if]['gateway'] = $_POST['gateway'];
		$config['dhcpd'][$if]['domain'] = $_POST['domain'];
		$config['dhcpd'][$if]['denyunknown'] = ($_POST['denyunknown']) ? true : false;
		$config['dhcpd'][$if]['enable'] = ($_POST['enable']) ? true : false;

		write_config();

		$retval = 0;
		$retvaldhcp = 0;
		$retvaldns = 0;

		killbyname("dhcpd");
		dhcp_clean_leases();

		if (isset($config['dnsmasq']['regdhcpstatic']))
		{
			$retvaldns = services_dnsmasq_configure();
			if ($retvaldns == 0) {
				clear_subsystem_dirty('hosts');
				clear_subsystem_dirty('staticmaps');
			}
		}
		else
		{
			$retvaldhcp = services_dhcpd_configure();
			if ($retvaldhcp == 0)
				clear_subsystem_dirty('staticmaps');
		}

		if($retvaldhcp == 1 || $retvaldns == 1)
			$retval = 1;
		$savemsg = get_std_save_message($retval);
	}
}

if ($_GET['act'] == 'del')
{
	if ($a_maps[$_GET['id']])
	{
		unset($a_maps[$_GET['id']]);
		write_config();
		if(isset($config['dhcpd'][$if]['enable']))
		{
			mark_subsystem_dirty('staticmaps');
			if (isset($config['dnsmasq']['regdhcpstatic']))
				mark_subsystem_dirty('hosts');
		}
		header("Location: services_dhcp.php?if={$if}");
		exit;
	}
}

$pgtitle = array('SERVİSLER', 'DHCP SUNUCU');

include('head.inc');

?>

<script type="text/javascript" src="/javascript/row_helper.js">
</script>

<script type="text/javascript" language="JavaScript">
	function enable_change(enable_over) {
		var endis;
		endis = !(document.iform.enable.checked || enable_over);
		document.iform.range_from.disabled = endis;
		document.iform.range_to.disabled = endis;
		document.iform.dns1.disabled = endis;
		document.iform.dns2.disabled = endis;
		document.iform.gateway.disabled = endis;
		document.iform.domain.disabled = endis;
		document.iform.denyunknown.disabled = endis;
	}

</script>
</head>
<body>
<?php include('fbegin.inc'); ?>

<form action="services_dhcp.php" method="post" name="iform" id="iform">
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (is_subsystem_dirty('staticmaps')): ?><p>
<?php print_info_box_np("Sabit IP adresi dağıtımları değiştirildi.<br>Değişikliklerin etkili olabilmesi için uygulamalısınız.", true);?>
<?php endif; ?>
<table cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<?php
				$tab_array = array();
				$tabscounter = 0;
				$i = 0;
				foreach ($iflist as $ifent => $ifname) {
					$oc = $config['interfaces'][$ifent];
					if ((is_array($config['dhcpd'][$ifent]) && !isset($config['dhcpd'][$ifent]['enable']) && (!is_ipaddr($oc['ipaddr']))) ||
						(!is_array($config['dhcpd'][$ifent]) && (!is_ipaddr($oc['ipaddr']))))
						continue;
					if ($ifent == $if)
						$active = true;
					else
						$active = false;
					$tab_array[] = array($ifname, $active, "services_dhcp.php?if={$ifent}");
					$tabscounter++;
				}
				if ($tabscounter == 0) {
					echo "</td></tr></table></form>";
					echo "</div>";
					echo "</body>";
					echo "</html>";
					exit;
				}
				display_top_tabs($tab_array);
			?>
		</td>
	</tr>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td class="listtopic" colspan="2">SABİT IP ADRESİ DAĞITIMLARI</td>
				<tr>
				<tr>
					<td colspan="2">
						<table width="100%" cellpadding="0" cellspacing="0" class="grids sortable">
							<tr>
								<td class="head">MAC Adresi</td>
								<td class="head">IP Adresi</td>
								<td class="head">Sunucu Adı</td>
								<td class="head">Açıklama</td>
								<td class="head"></td>
							</tr>
									<?php if(is_array($a_maps)): ?>
									<?php $i = 0; foreach ($a_maps as $mapent): ?>
									<?php if($mapent['mac'] <> "" or $mapent['ipaddr'] <> ""): ?>
							<tr>
								<td class="cell" ondblclick="document.location='services_dhcp_edit.php?if=<?=htmlspecialchars($if);?>&id=<?=$i;?>';">
									<?=htmlspecialchars($mapent['mac']);?>
								</td>
								<td class="cell" ondblclick="document.location='services_dhcp_edit.php?if=<?=htmlspecialchars($if);?>&id=<?=$i;?>';">
									<?=htmlspecialchars($mapent['ipaddr']);?>
								</td>
								<td class="cell" ondblclick="document.location='services_dhcp_edit.php?if=<?=htmlspecialchars($if);?>&id=<?=$i;?>';">
									<?=htmlspecialchars($mapent['hostname']);?>
								</td>
								<td class="cell description" ondblclick="document.location='services_dhcp_edit.php?if=<?=htmlspecialchars($if);?>&id=<?=$i;?>';">
									<?=htmlspecialchars(base64_decode($mapent['descr']));?>
								</td>
								<td class="cell tools">
									<a title="Düzenle" href="services_dhcp_edit.php?if=<?=htmlspecialchars($if);?>&id=<?=$i;?>">
										<i class="icon-edit"></i>
									</a>
									<a title="Sil" href="services_dhcp.php?if=<?=htmlspecialchars($if);?>&act=del&id=<?=$i;?>" onclick="return confirm('Silmek istediğinizden emin misiniz?')">
										<i class="icon-trash"></i>
									</a>
								</td>
							</tr>
									<?php endif; ?>
									<?php $i++; endforeach; ?>
									<?php endif; ?>
							<tr>
								<td class="cell" colspan="4"></td>
								<td class="cell tools">
									<a title="Ekle" href="services_dhcp_edit.php?if=<?=htmlspecialchars($if);?>">
										<i class="icon-plus"></i>
									</a>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td class="listtopic" colspan="2">DHCP SUNUCU AYARLARI</td>
				<tr>
				<tr>
					<td valign="top" class="vncell">Aktif et</td>
					<td class="vtable">
						<input name="enable" type="checkbox" value="yes" <?php if ($pconfig['enable']) echo "checked"; ?> onClick="enable_change(false)">
						<br>DHCP sunucusunu <b><?=htmlspecialchars($iflist[$if]);?></b> arayüzünde etkinleştirmek için işaretleyin.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Otomatik IP Adresi Dağıtımını Kapat</td>
					<td class="vtable">
						<input name="denyunknown" id="denyunknown" type="checkbox" value="yes" <?php if ($pconfig['denyunknown']) echo "checked"; ?>>
						<br>İşaretlendiğinde, sadece sabit IP adresi verilen istemciler DHCP sunucuyu kullanabilirler.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Alt Ağ</td>
					<td class="vtable">
						<?=gen_subnet($ifcfgip, $ifcfgsn);?>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Alt Ağ Maskesi</td>
					<td class="vtable">
						<?=gen_subnet_mask($ifcfgsn);?>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Kullanılabilir IP Dağıtım Aralığı</td>
					<td class="vtable">
					<?php
						$range_from = ip2long(long2ip32(ip2long($ifcfgip) & gen_subnet_mask_long($ifcfgsn)));
						$range_from++;
						echo long2ip32($range_from);
					?>
					-
					<?php
						$range_to = ip2long(long2ip32(ip2long($ifcfgip) | (~gen_subnet_mask_long($ifcfgsn))));
						$range_to--;
						echo long2ip32($range_to);
					?>
					</td>
				</tr>
				<tr>
				<td valign="top" class="vncell">Otomatik IP Adresi Dağıtım Aralığı</td>
				<td class="vtable">
					<input name="range_from" type="text" id="range_from" value="<?=htmlspecialchars($pconfig['range_from']);?>">
					&nbsp;-&nbsp; <input name="range_to" type="text" id="range_to" value="<?=htmlspecialchars($pconfig['range_to']);?>">
				</td>
				</tr>
				<tr>
				<td valign="top" class="vncell">DNS Sunucu Adresleri</td>
				<td class="vtable">
					<input name="dns1" type="text" id="dns1" value="<?=htmlspecialchars($pconfig['dns1']);?>"><br>
					<input name="dns2" type="text" id="dns2" value="<?=htmlspecialchars($pconfig['dns2']);?>"><br>
						Sistemin varsayılan DNS adreslerini kullanmak için boş bırakın.<br>
						<b>NOT: </b>Sistem DNS adresleri "Sistem->Genel Ayarlar" sayfasından ayarlanır.
				</td>
				</tr>
				<tr>
				<td valign="top" class="vncell">Ağ Geçidi</td>
				<td class="vtable">
					<input name="gateway" type="text" id="gateway" value="<?=htmlspecialchars($pconfig['gateway']);?>">
				</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Alan Adı</td>
					<td class="vtable">
						<input name="domain" type="text" id="domain" value="<?=htmlspecialchars($pconfig['domain']);?>">
					 </td>
				</tr>
				<tr>
					<td class="vncell"></td>
					<td class="vtable">
						<input name="if" type="hidden" value="<?=htmlspecialchars($if);?>">
						<input name="Submit" type="submit" class="btn btn-inverse" value="Kaydet" onclick="enable_change(true)">
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>
<script language="JavaScript">
<!--
enable_change(false);
//-->
</script>
</div>
</body>
</html>
