<?php
/*
	system.php

	Copyright (C) 2013-2015 Ogün AÇIK
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
require_once('functions.inc');
require_once('filter.inc');
require_once('shaper.inc');

$pconfig['hostname'] = $config['system']['hostname'];
$pconfig['disablefilter'] = $config['system']['disablefilter'];
$pconfig['domain'] = $config['system']['domain'];
list($pconfig['dns1'],$pconfig['dns2'],$pconfig['dns3'],$pconfig['dns4']) = $config['system']['dnsserver'];

$pconfig['dns1gwint'] = $config['system']['dns1gwint'];
$pconfig['dns2gwint'] = $config['system']['dns2gwint'];
$pconfig['dns3gwint'] = $config['system']['dns3gwint'];
$pconfig['dns4gwint'] = $config['system']['dns4gwint'];

$pconfig['dnsallowoverride'] = isset($config['system']['dnsallowoverride']);
$pconfig['timezone'] = $config['system']['timezone'];
$pconfig['timeupdateinterval'] = $config['system']['time-update-interval'];
$pconfig['timeservers'] = $config['system']['timeservers'];

$pconfig['dnslocalhost'] = isset($config['system']['dnslocalhost']);

if (!isset($pconfig['timeupdateinterval']))
	$pconfig['timeupdateinterval'] = 300;
if (!$pconfig['timezone'])
	$pconfig['timezone'] = "Etc/UTC";
if (!$pconfig['timeservers'])
	$pconfig['timeservers'] = "pool.ntp.org";

$changecount = 0;

function is_timezone($elt)
{
	return !preg_match("/\/$/", $elt);
}

if($pconfig['timezone'] <> $_POST['timezone'])
{
	require_once('functions.inc');
	$pid = `ps awwwux | grep -v "grep" | grep "tcpdump -v -l -n -e -ttt -i pflog0"  | awk '{ print $2 }'`;
	if($pid) {
		mwexec("/bin/kill $pid");
		usleep(1000);
	}
	filter_pflog_start();
}

exec('/usr/bin/tar -tzf /usr/share/zoneinfo.tgz', $timezonelist);
$timezonelist = array_filter($timezonelist, 'is_timezone');
sort($timezonelist);

$multiwan = false;
$interfaces = get_configured_interface_list();

foreach($interfaces as $interface)
{
	if(interface_has_gateway($interface))
		$multiwan = true;
}

if ($_POST)
{
	$changecount++;

	unset($input_errors);
	$pconfig = $_POST;

	$reqdfields = split(" ", "hostname domain");
	$reqdfieldsn = array("Sunucu Adı", "Alan Adı");

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if ($_POST['hostname'] && !is_hostname($_POST['hostname']))
		$input_errors[] = "Sunucu adı sadece a-z, 0-9 ve '-' karakterlerinden oluşabilir.";

	if ($_POST['domain'] && !is_domain($_POST['domain']))
		$input_errors[] = "Alan adı sadece a-z, 0-9, '-' ve '.' karakterlerinden oluşabilir.";

	if(($_POST['dns1'] && !is_ipaddr($_POST['dns1'])) || ($_POST['dns2'] && !is_ipaddr($_POST['dns2'])))
		$input_errors[] = "1. ve 2. DNS sunucuları için geçerli bir ip adresi belirtilmelidir.";

	if(($_POST['dns3'] && !is_ipaddr($_POST['dns3'])) || ($_POST['dns4'] && !is_ipaddr($_POST['dns4'])))
		$input_errors[] = "3. ve 4. DNS sunucuları için geçerli bir ip adresi belirtilmelidir.";

	if($_POST['webguiport'] && (!is_numericint($_POST['webguiport']) || ($_POST['webguiport'] < 1) || ($_POST['webguiport'] > 65535)))
		$input_errors[] = "Web arayüzü için geçerli bir TCP/IP portu belirtilmelidir.";

	$direct_networks_list = explode(" ", filter_get_direct_networks_list());

	for ($dnscounter=1; $dnscounter<5; $dnscounter++)
	{
		$dnsitem = "dns{$dnscounter}";
		$dnsgwitem = "dns{$dnscounter}gwint";
		if ($_POST[$dnsgwitem])
		{
			if(interface_has_gateway($_POST[$dnsgwitem]))
			{
				foreach($direct_networks_list as $direct_network)
				{
					if(ip_in_subnet($_POST[$dnsitem], $direct_network))
						$input_errors[] = "'{$_POST[$dnsitem]}' için ağ içindeki bir adresi ağ geçidi olarak belirtemezsiniz";
				}
			}
		}
	}

	foreach(explode(' ', $_POST['timeservers']) as $ts)
	{
		if (!is_domain($ts))
			$input_errors[] = "Bir NTP zaman sunucusu sadece a-z, 0-9, '-' ve '.' karakterlerinden oluşabilir.";
	}

	if (!$input_errors)
	{
		update_if_changed("hostname", $config['system']['hostname'], strtolower($_POST['hostname']));
		update_if_changed("domain", $config['system']['domain'], strtolower($_POST['domain']));

		update_if_changed("timezone", $config['system']['timezone'], $_POST['timezone']);
		update_if_changed("NTP servers", $config['system']['timeservers'], strtolower($_POST['timeservers']));
		update_if_changed("NTP update interval", $config['system']['time-update-interval'], $_POST['timeupdateinterval']);

		unset($config['system']['dnsserver']);
		if ($_POST['dns1'])
			$config['system']['dnsserver'][] = $_POST['dns1'];
		if ($_POST['dns2'])
			$config['system']['dnsserver'][] = $_POST['dns2'];
		if ($_POST['dns3'])
			$config['system']['dnsserver'][] = $_POST['dns3'];
		if ($_POST['dns4'])
			$config['system']['dnsserver'][] = $_POST['dns4'];

		$olddnsallowoverride = $config['system']['dnsallowoverride'];

		unset($config['system']['dnsallowoverride']);
		$config['system']['dnsallowoverride'] = $_POST['dnsallowoverride'] ? true : false;

		if($_POST['dnslocalhost'] == "yes")
			$config['system']['dnslocalhost'] = true;
		else
			unset($config['system']['dnslocalhost']);

		if($_POST['dns1gwint'])
			$config['system']['dns1gwint'] = $pconfig['dns1gwint'];
		else
			unset($config['system']['dns1gwint']);

		if($_POST['dns2gwint'])
			$config['system']['dns2gwint'] = $pconfig['dns2gwint'];
		else
			unset($config['system']['dns2gwint']);

		if($_POST['dns3gwint'])
			$config['system']['dns3gwint'] = $pconfig['dns3gwint'];
		else
			unset($config['system']['dns3gwint']);

		if($_POST['dns4gwint'])
			$config['system']['dns4gwint'] = $pconfig['dns4gwint'];
		else
			unset($config['system']['dns4gwint']);

		if($_POST['disablefilter'] == "yes")
			$config['system']['disablefilter'] = "enabled";
		else
			unset($config['system']['disablefilter']);

		if ($changecount > 0)
			write_config();

		$retval = 0;
		$retval = system_hostname_configure();
		$retval |= system_hosts_generate();
		$retval |= system_resolvconf_generate();
		$retval |= services_dnsmasq_configure();
		$retval |= system_timezone_configure();
		$retval |= system_ntp_configure();

		if ($olddnsallowoverride != $config['system']['dnsallowoverride'])
			$retval |= send_event("service reload dns");

		$retval |= filter_configure();

		$savemsg = get_std_save_message($retval);
	}
}

$pgtitle = array('SİSTEM', 'GENEL AYARLAR');

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php
if ($input_errors)
	print_input_errors($input_errors);
if ($savemsg)
	print_info_box($savemsg);
?>

<form action="system.php" method="post">
<table cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td colspan="2" class="listtopic">SİSTEM</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Güvenlik Duvarını<br> Kapat</td>
					<td class="vtable">
						<label>
							<input name="disablefilter" type="checkbox" id="disablefilter" value="yes" <?php if (isset($config['system']['disablefilter'])) echo "checked"; ?> />
							Güvenlik duvarını <b>devre dışı</b> bırakmak için işaretleyin.
						</label>
					</td>

				</tr>
				<tr>
					<td valign="top" class="vncell">Sunucu Adı</td>
					<td class="vtable"> <input name="hostname" type="text" id="hostname" value="<?=htmlspecialchars($pconfig['hostname']);?>">
						<br>Bir sunucu adı girin. Örnek: <em>nuclewall</em>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Alan adı</td>
					<td class="vtable"> <input name="domain" type="text" id="domain" value="<?=htmlspecialchars($pconfig['domain']);?>">
						<br>Bir alan adı girin. Örnek: <em>sirketim.com, ev, ofis, okulum.com</em>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">DNS Sunucu Adresleri</td>
					<td class="vtable">
						<table>
							<tr>
								<td><b>Sunucu Adresi</b></td>
								<?php if ($multiwan): ?>
								<td><b>Ağ Geçidi</b></td>
								<?php endif; ?>
							</tr>
							<?php
								for ($dnscounter=1; $dnscounter<5; $dnscounter++):
									$fldname="dns{$dnscounter}gwint";
							?>
							<tr>
								<td>
									<input name="dns<?php echo $dnscounter;?>" type="text" id="dns<?php echo $dnscounter;?>" size="20" value="<?php echo $pconfig['dns'.$dnscounter];?>">
								</td>
								<td>
									<?php if ($multiwan): ?>
									<select name='<?=$fldname;?>'>
										<?php
											$interface = "none";
											$dnsgw = "dns{$dnscounter}gwint";

											if($pconfig[$dnsgw] == $interface)
												$selected = "selected";
											else
												$selected = "";

											echo "<option value='$interface' $selected>". ucwords($interface) ."</option>\n";
											foreach($interfaces as $interface)
											{
												if(interface_has_gateway($interface))
												{
													if($pconfig[$dnsgw] == $interface)
														$selected = "selected";
													else
														$selected = "";

													$friendly_interface = convert_friendly_interface_to_friendly_descr($interface);
													echo "<option value='$interface' $selected>". ucwords($friendly_interface) ."</option>\n";
												}
											}
										?>
									</select>
								<?php endif; ?>
								</td>
							</tr>
							<?php endfor; ?>
						</table>
						<br>
						Sistemin alan adı çözümlemede kullanacağı DNS adreslerini girebilirsiniz. "DHCPD" ve "DNS Forwarder"
						servisleri de aynı DNS sucunuları kullanacaktır.
						<br>
						<?php if($multiwan): ?>
						Ayrıca, isteğe bağlı olarak her DNS sunucu için ağ geçidi belirleyebilirsiniz.
						<br>
						<?php endif; ?>
						<br>
						<input name="dnsallowoverride" type="checkbox" id="dnsallowoverride" value="yes" <?php if ($pconfig['dnsallowoverride']) echo "checked"; ?>>
						<b>
							DNS sunucuların WAN tarafındaki DHCP/PPP sunucular tarafından değiştirilmesine izin ver.
						</b>
						<br>
						Bu ayar seçili olduğunda, sistem WAN tarafındaki DHCP sunucusunun belirlediği DNS sunucuları kullanacaktır.
						Böylece üst tarafta gireceğiniz DNS sunucular etkisiz olacaktır.
						<br>
						<br>
						<input name="dnslocalhost" type="checkbox" id="dnslocalhost" value="yes" <?php if ($pconfig['dnslocalhost']) echo "checked"; ?> />
						<b>
							Yerel (127.0.0.1) DNS sunucusunu kullanma
						</b>
						<br>
						Bu ayar seçili olduğunda,  varsayılan olarak ilk DNS sunucu olan localhost(127.0.0.1) kullanılmayacaktır.
						Bunun yerine "DNS Forwarder" servisinin çözümlemesi kullanılacaktır. (Aktifse)
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Zaman Dilimi</td>
					<td class="vtable">
						<select name="timezone" id="timezone">
							<?php foreach ($timezonelist as $value): ?>
							<option value="<?=htmlspecialchars($value);?>" <?php if ($value == $pconfig['timezone']) echo "selected"; ?>>
								<?=htmlspecialchars($value);?>
							</option>
							<?php endforeach; ?>
						</select>
						<br>Size en yakın bölgeyi seçin
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">NTP Zaman Sunucu Adresi</td>
					<td class="vtable">
						<input name="timeservers" type="text" id="timeservers" size="40" value="<?=htmlspecialchars($pconfig['timeservers']);?>">
						<br>NTP sunucularını aralarında boşluk bırakarak girebilirsiniz. Sunucu ismi giriyorsanız DNS çözümleyicinizin çalıştığından emin olun.
					</td>
				</tr>
				<tr>
					<td class="vncell"></td>
					<td class="vtable">
						<input name="Submit" type="submit" class="btn btn-inverse" value="Kaydet">
					</td>
				</tr>
			</table>
		<td>
	</tr>
</table>
</form>
</div>
</body>
</html>
