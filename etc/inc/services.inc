<?php
/*
	services.inc

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.
*/

function services_dhcpd_configure()
{
	global $config, $g;

	if($g['services_dhcp_server_enable'] == false)
		return;

	if(is_process_running("dhcpd"))
		mwexec("killall dhcpd", true);

	if (!is_dhcp_server_enabled())
		return 0;

	$fd = fopen("{$g['tmp_path']}/dhcpd.sh", 'w');
	$status = `mount | grep "{$g['dhcpd_chroot_path']}/dev"`;
	fwrite($fd, "mkdir -p {$g['dhcpd_chroot_path']}\n");
	fwrite($fd, "mkdir -p {$g['dhcpd_chroot_path']}/dev\n");
	fwrite($fd, "mkdir -p {$g['dhcpd_chroot_path']}/etc\n");
	fwrite($fd, "mkdir -p {$g['dhcpd_chroot_path']}/usr/local/sbin\n");
	fwrite($fd, "mkdir -p {$g['dhcpd_chroot_path']}/var/db\n");
	fwrite($fd, "mkdir -p {$g['dhcpd_chroot_path']}/var/run\n");
	fwrite($fd, "mkdir -p {$g['dhcpd_chroot_path']}/usr\n");
	fwrite($fd, "mkdir -p {$g['dhcpd_chroot_path']}/lib\n");
	fwrite($fd, "mkdir -p {$g['dhcpd_chroot_path']}/run\n");
	fwrite($fd, "chown -R dhcpd:_dhcp {$g['dhcpd_chroot_path']}/*\n");
	fwrite($fd, "cp /lib/libc.so.* {$g['dhcpd_chroot_path']}/lib/\n");
	fwrite($fd, "cp /usr/local/sbin/dhcpd {$g['dhcpd_chroot_path']}/usr/local/sbin/\n");
	fwrite($fd, "chmod a+rx {$g['dhcpd_chroot_path']}/usr/local/sbin/dhcpd\n");
	if(!trim($status))
		fwrite($fd, "mount -t devfs devfs {$g['dhcpd_chroot_path']}/dev\n");
	fclose($fd);
	mwexec("/bin/sh {$g['tmp_path']}/dhcpd.sh");

	if ($g['booting'])
	{
		if ($g['platform'] != "pfSense")
		{
			if (file_exists("{$g['cf_conf_path']}/dhcpleases.tgz"))
			{
				$dhcprestore = "";
				$dhcpreturn = "";
				exec("cd /;LANG=C /usr/bin/tar -xzf {$g['cf_conf_path']}/dhcpleases.tgz 2>&1", $dhcprestore, $dhcpreturn);
				$dhcprestore = implode(" ", $dhcprestore);

				if($dhcpreturn <> 0)
				{
					log_error("DHCP leases restore failed exited with $dhcpreturn, the error is: $dhcprestore\n");
				}
			}
		}
	}

	$syscfg = $config['system'];
	$dhcpdcfg = $config['dhcpd'];
	$Iflist = get_configured_interface_list();

	if ($g['booting'])
		echo "DHCP sunucusu baþlatýlýyor...";
	else
		sleep(1);

	$fd = fopen("{$g['dhcpd_chroot_path']}/etc/dhcpd.conf", 'w');
	if (!$fd) {
		printf("Error: cannot open dhcpd.conf in services_dhcpd_configure().\n");
		return 1;
	}

	$ddns_zones = array();

	$dhcpdconf = <<<EOD

option domain-name "{$syscfg['domain']}";
option ldap-server code 95 = text;
option domain-search-list code 119 = text;
default-lease-time 7200;
max-lease-time 86400;
log-facility local7;
one-lease-per-client true;
deny duplicates;
ping-check true;

EOD;

	if(!isset($dhcpifconf['disableauthoritative']))
		$dhcpdconf .= "authoritative;\n";

	if(isset($dhcpifconf['alwaysbroadcast']))
		$dhcpdconf .= "always-broadcast on\n";

	$dhcpdifs = array();

	$dhcpnum = 0;
	foreach ($dhcpdcfg as $dhcpif => $dhcpifconf)
	{
		interfaces_staticarp_configure($dhcpif);

		if (!isset($dhcpifconf['enable']))
			continue;
	}

	$dhcpnum = 0;

	foreach ($dhcpdcfg as $dhcpif => $dhcpifconf)
	{
		$newzone = array();
		$ifcfg = $config['interfaces'][$dhcpif];

		if (!isset($dhcpifconf['enable']) || !isset($Iflist[$dhcpif]))
			continue;
		$ifcfgip = get_interface_ip($dhcpif);
		$ifcfgsn = get_interface_subnet($dhcpif);
		$subnet = gen_subnet($ifcfgip, $ifcfgsn);
		$subnetmask = gen_subnet_mask($ifcfgsn);

		if (!is_ipaddr($subnet))
			continue;

		$dnscfg = "";

		if ($dhcpifconf['domain'])
		{
			$dnscfg .= "	option domain-name \"{$dhcpifconf['domain']}\";\n";
		}

		if (is_array($dhcpifconf['dnsserver']) && ($dhcpifconf['dnsserver'][0]))
		{
			$dnscfg .= "	option domain-name-servers " . join(",", $dhcpifconf['dnsserver']) . ";";
			if ($newzone['domain-name'])
				$newzone['dns-servers'] = $dhcpifconf['dnsserver'];
		}
		else if (isset($config['dnsmasq']['enable']))
		{
			$dnscfg .= "	option domain-name-servers {$ifcfgip};";
			if ($newzone['domain-name'] && is_array($syscfg['dnsserver']) && ($syscfg['dnsserver'][0]))
				$newzone['dns-servers'] = $syscfg['dnsserver'];
		}
		else if (is_array($syscfg['dnsserver']) && ($syscfg['dnsserver'][0]))
		{
			$dnscfg .= "	option domain-name-servers " . join(",", $syscfg['dnsserver']) . ";";
			if ($newzone['domain-name'])
				$newzone['dns-servers'] = $syscfg['dnsserver'];
		}

		$dhcpdconf .= "subnet {$subnet} netmask {$subnetmask} {\n";
		$dhcpdconf .= "	pool {\n";

		/* is failover dns setup? */
		if (is_array($dhcpifconf['dnsserver']) && $dhcpifconf['dnsserver'][0] <> "")
		{
			$dhcpdconf .= "		option domain-name-servers {$dhcpifconf['dnsserver'][0]}";
			if($dhcpifconf['dnsserver'][1] <> "")
				$dhcpdconf .= ",{$dhcpifconf['dnsserver'][1]}";
			$dhcpdconf .= ";\n";
		}

		if (isset($dhcpifconf['denyunknown']))
		   $dhcpdconf .= "		deny unknown-clients;\n";

		if ($dhcpifconf['gateway'])
			$routers = $dhcpifconf['gateway'];
		else
			$routers = $ifcfgip;

		$dhcpdconf .= <<<EOD
		range {$dhcpifconf['range']['from']} {$dhcpifconf['range']['to']};
	}
	option routers {$routers};
$dnscfg

EOD;

		$dhcpdconf .= "	default-lease-time 9000;\n";
		$dhcpdconf .= "\n";
		$dhcpdconf .= <<<EOD
}

EOD;
		/* add static mappings */
		if (is_array($dhcpifconf['staticmap']))
		{
			$i = 0;
			foreach ($dhcpifconf['staticmap'] as $sm)
			{
				$dhcpdconf .= <<<EOD
host s_{$dhcpif}_{$i} {
	hardware ethernet {$sm['mac']};

EOD;
				if ($sm['ipaddr'])
					$dhcpdconf .= "	fixed-address {$sm['ipaddr']};\n";

				if ($sm['hostname'])
				{
					$dhhostname = str_replace(" ", "_", $sm['hostname']);
					$dhhostname = str_replace(".", "_", $dhhostname);
					$dhcpdconf .= "	option host-name \"{$dhhostname}\";\n";
				}

				if ($sm['netbootfile'])
					$dhcpdconf .= "	filename \"{$sm['netbootfile']}\";\n";

				$dhcpdconf .= "}\n";
				$i++;
			}
		}

		$dhcpdifs[] = get_real_interface($dhcpif);

		if ($newzone['domain-name'])
			$ddns_zones[] = $newzone;
	}

	fwrite($fd, $dhcpdconf);
	fclose($fd);

	touch("{$g['dhcpd_chroot_path']}/var/db/dhcpd.leases");
	touch("{$g['varrun_path']}/dhcpd.pid");

	mwexec("/usr/local/sbin/dhcpd -user dhcpd -group _dhcp -chroot {$g['dhcpd_chroot_path']} -cf /etc/dhcpd.conf " .
		join(" ", $dhcpdifs));

	if ($g['booting'])
	{
		echo "Tamam.\n";
	}

	return 0;
}


function services_dnsmasq_configure()
{
	global $config, $g;
	$return = 0;

	if(isset($config['system']['developerspew']))
	{
		$mt = microtime();
		echo "services_dnsmasq_configure() being called $mt\n";
	}

	if (file_exists("{$g['varrun_path']}/dnsmasq.pid"))
		sigkillbypid("{$g['varrun_path']}/dnsmasq.pid", "TERM");

	if (isset($config['dnsmasq']['enable'])) {

		if ($g['booting'])
			echo "DNS yönlendirici baþlatýlýyor...";
		else
			sleep(1);

		if(system_hosts_generate()!=0)
			$return = 1;

		$args = "";

		if (isset($config['dnsmasq']['regdhcp'])) {
			$args .= " --dhcp-hostsfile={$g['varetc_path']}/hosts ";
		}

		if (isset($config['dnsmasq']['domainoverrides']) && is_array($config['dnsmasq']['domainoverrides']))
		{
			foreach($config['dnsmasq']['domainoverrides'] as $override) {
			        $args .= ' --server=/' . $override['domain'] . '/' . $override['ip'];
			}
		}

		if (isset($config['dnsmasq']['domainoverrides']) && is_array($config['dnsmasq']['domainoverrides']))
		{
			if(!isset($config['system']['webgui']['nodnsrebindcheck'])) {
				foreach($config['dnsmasq']['domainoverrides'] as $override) {
					$args .= ' --rebind-domain-ok=/' . $override['domain'] . '/ ';
				}
			}
		}

		if(!isset($config['system']['webgui']['nodnsrebindcheck']))
			$dns_rebind = "--rebind-localhost-ok --stop-dns-rebind";

		if ($config['dnsmasq']['custom_options']) {
			foreach (preg_split('/\s+/', $config['dnsmasq']['custom_options']) as $c)
				$args .= " --$c";
		}

		mwexec_bg("/usr/local/sbin/dnsmasq --local-ttl 1 --all-servers {$dns_rebind} --dns-forward-max=5000 --cache-size=10000 {$args}");

		if ($g['booting'])
			echo "Tamam.\n";
	}

	if (!$g['booting'])
	{
		if(services_dhcpd_configure()!=0)
			$return = 1;
	}

	return $return;
}


function services_dnsupdate_process($int = "")
{
	global $config, $g;
	if(isset($config['system']['developerspew']))
	{
		$mt = microtime();
		echo "services_dnsupdate_process() being called $mt\n";
	}

	if (is_array($config['dnsupdates']['dnsupdate']))
	{
		foreach ($config['dnsupdates']['dnsupdate'] as $i => $dnsupdate)
		{
			if (!isset($dnsupdate['enable']))
				continue;
			if (!empty($int) && $int != $dnsupdate['interface'])
				continue;

			$if = get_real_interface($dnsupdate['interface']);
			$wanip = get_interface_ip($dnsupdate['interface']);
			if ($wanip)
			{
				$keyname = $dnsupdate['keyname'];

				if (substr($keyname, -1) != ".")
					$keyname .= ".";

				$hostname = $dnsupdate['host'];

				if (substr($hostname, -1) != ".")
					$hostname .= ".";

				$fd = fopen("{$g['varetc_path']}/K{$i}{$keyname}+157+00000.private", "w");
				$privkey = <<<EOD
Private-key-format: v1.2
Algorithm: 157 (HMAC)
Key: {$dnsupdate['keydata']}

EOD;
				fwrite($fd, $privkey);
				fclose($fd);

				/* write public key file */
				if ($dnsupdate['keytype'] == "zone")
				{
					$flags = 257;
					$proto = 3;
				}
				else if ($dnsupdate['keytype'] == "host")
				{
					$flags = 513;
					$proto = 3;
				}
				else if ($dnsupdate['keytype'] == "user")
				{
					$flags = 0;
					$proto = 2;
				}

				$fd = fopen("{$g['varetc_path']}/K{$i}{$keyname}+157+00000.key", "w");
				fwrite($fd, "{$keyname} IN KEY {$flags} {$proto} 157 {$dnsupdate['keydata']}\n");
				fclose($fd);

				/* generate update instructions */
				$upinst = "";
				if (!empty($dnsupdate['server']))
					$upinst .= "server {$dnsupdate['server']}\n";
				$upinst .= "update delete {$dnsupdate['host']} A\n";
				$upinst .= "update add {$dnsupdate['host']} {$dnsupdate['ttl']} A {$wanip}\n";
				$upinst .= "\n";	/* mind that trailing newline! */

				$fd = fopen("{$g['varetc_path']}/nsupdatecmds{$i}", "w");
				fwrite($fd, $upinst);
				fclose($fd);

				/* invoke nsupdate */
				$cmd = "/usr/bin/nsupdate -k {$g['varetc_path']}/K{$i}{$keyname}+157+00000.key";
				if (isset($dnsupdate['usetcp']))
					$cmd .= " -v";
				$cmd .= " {$g['varetc_path']}/nsupdatecmds{$i}";

				mwexec_bg($cmd);
			}
		}
	}

	return 0;
}

/* configure cron service */
function configure_cron()
{
	global $g, $config;

	/* preserve existing crontab entries */
	$crontab_contents = file("/etc/crontab", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	for ($i = 0; $i < count($crontab_contents); $i++)
	{
		$cron_item =& $crontab_contents[$i];
		if (strpos($cron_item, "# Nuclewall specific crontab entries") !== false)
		{
			array_splice($crontab_contents, $i - 1);
			break;
		}
	}
	$crontab_contents = implode("\n", $crontab_contents) . "\n";

	if (is_array($config['cron']['item']))
	{
		$crontab_contents .= "#\n";
		$crontab_contents .= "# Nuclewall specific crontab entries\n";
		$crontab_contents .= "# Created: " . date("F j, Y, g:i a") . "\n";
		$crontab_contents .= "#\n";

		foreach ($config['cron']['item'] as $item)
		{
			$crontab_contents .= "\n{$item['minute']}\t";
			$crontab_contents .= "{$item['hour']}\t";
			$crontab_contents .= "{$item['mday']}\t";
			$crontab_contents .= "{$item['month']}\t";
			$crontab_contents .= "{$item['wday']}\t";
			$crontab_contents .= "{$item['who']}\t";
			$crontab_contents .= "{$item['command']}";
		}

		$crontab_contents .= "\n#\n";
		$crontab_contents .= "# If possible do not add items to this file manually.\n";
		$crontab_contents .= "# If you do so, this file must be terminated with a blank line (e.g. new line)\n";
		$crontab_contents .= "#\n\n";
	}

	/* please maintain the newline at the end of file */
	file_put_contents("/etc/crontab", $crontab_contents);

	/* do a HUP kill to force sync changes */
	exec('/bin/pkill -HUP cron');
}


function install_cron_job($command, $active=false, $minute="0", $hour="*", $monthday="*", $month="*", $weekday="*", $who="root")
{
	global $config, $g;

	$is_installed = false;

	if(!$config['cron']['item'])
		return;

	$x=0;
	foreach($config['cron']['item'] as $item)
	{
		if(strstr($item['command'], $command))
		{
			$is_installed = true;
			break;
		}
		$x++;
	}

	if($active)
	{
		$cron_item = array();
		$cron_item['minute'] = $minute;
		$cron_item['hour'] = $hour;
		$cron_item['mday'] = $monthday;
		$cron_item['month'] = $month;
		$cron_item['wday'] = $weekday;
		$cron_item['who'] = $who;
		$cron_item['command'] = $command;

		if(!$is_installed)
		{
			$config['cron']['item'][] = $cron_item;
			write_config();
		}
		else
		{
			$config['cron']['item'][$x] = $cron_item;
			write_config();
		}
	}

	else
	{
		if(($is_installed == true) && ($x > 0))
		{
			unset($config['cron']['item'][$x]);
			write_config();
		}
	}
	configure_cron();
}
?>
