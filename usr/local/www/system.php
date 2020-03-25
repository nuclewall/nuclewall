<?php
/*
	system.php

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
	$reqdfieldsn = array("Hostname", "Domain");

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if ($_POST['hostname'] && !is_hostname($_POST['hostname']))
		$input_errors[] = "The hostname may only contain the characters a-z, 0-9 and '-'.";

	if ($_POST['domain'] && !is_domain($_POST['domain']))
		$input_errors[] = "The domain may only contain the characters a-z, 0-9, '-' and '.'.";

	if(($_POST['dns1'] && !is_ipaddr($_POST['dns1'])) || ($_POST['dns2'] && !is_ipaddr($_POST['dns2'])))
		$input_errors[] = "A valid IP address must be specified for the primary/secondary DNS server.";

	if(($_POST['dns3'] && !is_ipaddr($_POST['dns3'])) || ($_POST['dns4'] && !is_ipaddr($_POST['dns4'])))
		$input_errors[] = "A valid IP address must be specified for the third/fourth DNS server.";

	if($_POST['webguiport'] && (!is_numericint($_POST['webguiport']) || ($_POST['webguiport'] < 1) || ($_POST['webguiport'] > 65535)))
		$input_errors[] = "A valid TCP/IP port must be specified for the webConfigurator port.";

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
						$input_errors[] = "You can not assign a gateway to DNS '{$_POST[$dnsitem]}' server which is on a directly connected network.";
				}
			}
		}
	}

	foreach(explode(' ', $_POST['timeservers']) as $ts)
	{
		if (!is_domain($ts))
			$input_errors[] = "A NTP Time Server name may only contain the characters a-z, 0-9, '-' and '.'.";
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

$pgtitle = array('SYSTEM', 'GENERAL SETTINGS');

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
					<td colspan="2" class="listtopic">SYSTEM</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Disable Firewall</td>
					<td class="vtable">
						<label>
							<input name="disablefilter" type="checkbox" id="disablefilter" value="yes" <?php if (isset($config['system']['disablefilter'])) echo "checked"; ?> />
							Disable all packet filtering.
							Note: This converts NUCLEWALL into a routing only platform.<br>
				            Note: This will also turn off NAT.
						</label>
					</td>

				</tr>
				<tr>
					<td valign="top" class="vncell">Hostname</td>
					<td class="vtable"> <input name="hostname" type="text" id="hostname" value="<?=htmlspecialchars($pconfig['hostname']);?>">
						<br>Name of the host, without domain part. Example: <em>nuclewall</em>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Domain</td>
					<td class="vtable"> <input name="domain" type="text" id="domain" value="<?=htmlspecialchars($pconfig['domain']);?>">
						<br>Do not use 'local' as a domain name.
						It will cause local hosts running mDNS (avahi, bonjour, etc.) to be unable to resolve local hosts not running mDNS.
						Example: <em>mycorp.com, home, office, myschool.com</em>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">DNS servers</td>
					<td class="vtable">
						<table>
							<tr>
								<td><b>DNS Server</b></td>
								<?php if ($multiwan): ?>
								<td><b>Use gateway</b></td>
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
						Enter IP addresses to by used by the system for DNS resolution.
					    These are also used for the DHCP service, DNS forwarder and for PPTP VPN clients.
						<br>
						<?php if($multiwan): ?>
						In addition, optionally select the gateway for each DNS server.
						When using multiple WAN connections there should be at least one unique DNS server per gateway.
						<br>
						<?php endif; ?>
						<br>
						<input name="dnsallowoverride" type="checkbox" id="dnsallowoverride" value="yes" <?php if ($pconfig['dnsallowoverride']) echo "checked"; ?>>
						<b>
						  Allow DNS server list to be overridden by DHCP/PPP on WAN.
						</b>
						<br>
						    If this option is set, NUCLEWALL will
							use DNS servers assigned by a DHCP/PPP server on WAN
							for its own purposes (including the DNS forwarder).
							However, they will not be assigned to DHCP and PPTP VPN clients.
						<br>
						<br>
						<input name="dnslocalhost" type="checkbox" id="dnslocalhost" value="yes" <?php if ($pconfig['dnslocalhost']) echo "checked"; ?> />
						<b>
						    Do not use the DNS Forwarder as a DNS server for the firewall
						</b>
						<br>
						By default localhost (127.0.0.1) will be used as the first DNS server where the DNS forwarder is enabled,
						so system can use the DNS forwarder to perform lookups.
						Checking this box omits localhost from the list of DNS servers.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Timezone</td>
					<td class="vtable">
						<select name="timezone" id="timezone">
							<?php foreach ($timezonelist as $value): ?>
							<option value="<?=htmlspecialchars($value);?>" <?php if ($value == $pconfig['timezone']) echo "selected"; ?>>
								<?=htmlspecialchars($value);?>
							</option>
							<?php endforeach; ?>
						</select>
						<br>Select the location closest to you
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">NTP time server</td>
					<td class="vtable">
						<input name="timeservers" type="text" id="timeservers" size="40" value="<?=htmlspecialchars($pconfig['timeservers']);?>">
						<br>
						Use a space to separate multiple hosts.
						Remember to set up at least one DNS server if you enter a host name here.
					</td>
				</tr>
				<tr>
					<td class="vncell"></td>
					<td class="vtable">
						<input name="Submit" type="submit" class="btn btn-inverse" value="Save">
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
