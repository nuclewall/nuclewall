<?php
/*
 *
 * Copyright (C) 2013-2015 Ogün Açýk
 * Copyright (C) 2004-2007 Scott Ullrich (sullrich@gmail.com)
 * All rights reserved.
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * 1. Redistributions of source code must retain the above copyright notice,
 * this list of conditions and the following disclaimer.
 *
 * 2. Redistributions in binary form must reproduce the above copyright
 * notice, this list of conditions and the following disclaimer in the
 * documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
 * OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * RISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 */

function have_natpfruleint_access($if)
{
	$security_url = "firewall_nat_edit.php?if=". strtolower($if);
	if(isAllowedPage($security_url, $allowed))
		return true;
	return false;
}

function have_ruleint_access($if)
{
	$security_url = "firewall_rules.php?if=". strtolower($if);
	if(isAllowedPage($security_url))
		return true;
	return false;
}

function does_url_exist($url)
{
	$fd = fopen("$url","r");
	if($fd) {
		fclose($fd);
   		return true;
	} else {
        return false;
	}
}

function is_private_ip($iptocheck)
{
	$isprivate = false;
	$ip_private_list=array(
		"10.0.0.0/8",
		"172.16.0.0/12",
		"192.168.0.0/16",
	);
	foreach($ip_private_list as $private)
	{
		if(ip_in_subnet($iptocheck,$private)==true)
			$isprivate = true;
	}
	return $isprivate;
}

function get_tmp_file()
{
	global $g;
	return "{$g['tmp_path']}/tmp-" . time();
}

function get_dns_servers()
{
	$dns_servers = array();
	$dns_s = file("/etc/resolv.conf", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach($dns_s as $dns) {
		$matches = "";
		if (preg_match("/nameserver (.*)/", $dns, $matches))
			$dns_servers[] = $matches[1];
	}
	return array_unique($dns_servers);
}

function enable_hardware_offloading($interface)
{
	global $g, $config;

	if(isset($config['system']['do_not_use_nic_microcode']))
		return;

	$int = get_real_interface($interface);
	if(empty($int))
		return;
	$int_family = preg_split("/[0-9]+/", $int);
	$supported_ints = array('fxp');

	if (in_array($int_family, $supported_ints))
	{
		if(does_interface_exist($int))
			pfSense_interface_flags($int, IFF_LINK0);
	}

	return;
}

function interface_supports_polling($iface)
{
	$opts = pfSense_get_interface_addresses($iface);
	if (is_array($opts) && isset($opts['caps']['polling']))
		return true;

	return false;
}

function is_alias_inuse($alias)
{
	global $g, $config;

	if($alias == "") return false;

	if(is_array($config['filter']['rule']))

		foreach($config['filter']['rule'] as $rule)
		{
			if($rule['source']['address'])
				if($rule['source']['address'] == $alias)
					return true;
			if($rule['destination']['address'])
				if($rule['destination']['address'] == $alias)
					return true;
		}

	if(is_array($config['nat']['rule']))
		foreach($config['nat']['rule'] as $rule)
		{
			if($rule['target'] && $rule['target'] == $alias)
				return true;
			if($rule['source']['address'] && $rule['source']['address'] == $alias)
				return true;
			if($rule['destination']['address'] && $rule['destination']['address'] == $alias)
				return true;
		}
	return false;
}

function is_schedule_inuse($schedule)
{
	global $g, $config;

	if($schedule == "") return false;
	if(is_array($config['filter']['rule']))
		foreach($config['filter']['rule'] as $rule) {
			if($rule['sched'] == $schedule)
				return true;
		}
	return false;
}

function setup_microcode()
{
	$ifs = get_interface_arr();

	foreach($ifs as $if)
		enable_hardware_offloading($if);
}

function convert_ip_to_network_format($ip, $subnet)
{
	$ipsplit = split('[.]', $ip);
	$string = $ipsplit[0] . "." . $ipsplit[1] . "." . $ipsplit[2] . ".0/" . $subnet;
	return $string;
}

function get_filename_from_url($url)
{
	return basename($url);
}

function get_dir($dir)
{
	$dir_array = array();
	$d = dir($dir);
	while (false !== ($entry = $d->read())) {
		array_push($dir_array, $entry);
	}
	$d->close();
	return $dir_array;
}

function backup_config_section($section)
{
	global $config;
	$new_section = &$config[$section];
	/* generate configuration XML */
	$xmlconfig = dump_xml_config($new_section, $section);
	$xmlconfig = str_replace("<?xml version=\"1.0\"?>", "", $xmlconfig);
	return $xmlconfig;
}

function restore_config_section($section, $new_contents)
{
	global $config, $g;
	$fout = fopen("{$g['tmp_path']}/tmpxml","w");
	fwrite($fout, $new_contents);
	fclose($fout);
	$section_xml = parse_xml_config($g['tmp_path'] . "/tmpxml", $section);
	if ($section_xml != -1)
		$config[$section] = &$section_xml;
	@unlink($g['tmp_path'] . "/tmpxml");
	if(file_exists("{$g['tmp_path']}/config.cache"))
		unlink("{$g['tmp_path']}/config.cache");
	write_config();
	disable_security_checks();
	return;
}

function merge_config_section($section, $new_contents)
{
	global $config;
	$fname = get_tmp_filename();
	$fout = fopen($fname, "w");
	fwrite($fout, $new_contents);
	fclose($fout);
	$section_xml = parse_xml_config($fname, $section);
	$config[$section] = $section_xml;
	unlink($fname);
	write_config();
	disable_security_checks();
	return;
}

function get_disk_info()
{
	$diskout = "";
	exec("/bin/df -h | /usr/bin/grep -w '/' | /usr/bin/awk '{ print $2, $3, $4, $5 }'", $diskout);
	return explode(' ', $diskout[0]);
}

function strncpy(&$dst, $src, $length)
{
	if (strlen($src) > $length) {
		$dst = substr($src, 0, $length);
	} else {
		$dst = $src;
	}
}

function reload_interfaces_sync()
{
	global $config, $g;

	if($g['debug'])
		log_error("reload_interfaces_sync() is starting.");

	/* parse config.xml again */
	$config = parse_config(true);

	/* enable routing */
	system_routing_enable();
	if($g['debug'])
		log_error("Enabling system routing");

	if($g['debug'])
		log_error("Cleaning up Interfaces");

	interfaces_configure();
}

function reload_all()
{
	send_event("service reload all");
}

function reload_interfaces()
{
	send_event("interface all reload");
}

function reload_all_sync()
{
	global $config, $g;

	$g['booting'] = false;

	/* parse config.xml again */
	$config = parse_config(true);

	/* set up our timezone */
	system_timezone_configure();

	/* set up our hostname */
	system_hostname_configure();

	/* make hosts file */
	system_hosts_generate();

	/* generate resolv.conf */
	system_resolvconf_generate();

	/* enable routing */
	system_routing_enable();

	/* set up interfaces */
	interfaces_configure();

	/* start dyndns service */
	services_dyndns_configure();

	/* configure cron service */
	configure_cron();

	/* start the NTP client */
	system_ntp_configure();

	/* sync pw database */
	unlink_if_exists("/etc/spwd.db.tmp");
	mwexec("/usr/sbin/pwd_mkdb -d /etc/ /etc/master.passwd");

	/* restart sshd */
	send_event("service restart sshd");

	/* restart webConfigurator if needed */
	send_event("service restart webgui");
}

function auto_login()
{
	global $config;

	if(isset($config['system']['disableconsolemenu']))
		$status = false;
	else
		$status = true;

	$gettytab = file_get_contents("/etc/gettytab");
	$getty_split = split("\n", $gettytab);
	$getty_update_needed = false;
	$getty_search_str = ":ht:np:sp#115200";
	$getty_al_str = ":al=root:";
	$getty_al_search_str = $getty_search_str . $getty_al_str;
	/* Check if gettytab is already OK, if so then do not rewrite it. */
	foreach($getty_split as $gs) {
		if(stristr($gs, $getty_search_str)) {
			if($status == true) {
				if(!stristr($gs, $getty_al_search_str)) {
					$getty_update_needed = true;
				}
			} else {
				if(stristr($gs, $getty_al_search_str)) {
					$getty_update_needed = true;
				}
			}
		}
	}

	if (!$getty_update_needed)
		return;

	$fd = false;
	$tries = 0;

	while (!$fd && $tries < 100)
	{
		$fd = fopen("/etc/gettytab", "w");
		$tries++;

	}

	if(!$fd)
	{
		if ($status)
			log_error("Enabling auto login was not possible.");
		else
			log_error("Disabling auto login was not possible.");
		return;
	}

	foreach($getty_split as $gs)
	{
		if(stristr($gs, $getty_search_str)) {
			if($status == true) {
				fwrite($fd, "	".$getty_al_search_str."\n");
			} else {
				fwrite($fd, "	".$getty_search_str."\n");
			}
		} else {
			fwrite($fd, "{$gs}\n");
		}
	}
	fclose($fd);

	if ($status)
		log_error("Enabled console auto login, console menu is NOT password protected.");
	else
		log_error("Disabled console auto login, console menu is password protected.");

}

function setup_serial_port()
{
	global $g, $config;
	/* serial console - write out /boot.config */
	if(file_exists("/boot.config"))
		$boot_config = file_get_contents("/boot.config");
	else
		$boot_config = "";

	if($g['platform'] <> "cdrom") {
		$boot_config_split = split("\n", $boot_config);
		$fd = fopen("/boot.config","w");
		if($fd) {
			foreach($boot_config_split as $bcs) {
				if(stristr($bcs, "-D")) {
					/* DONT WRITE OUT, WE'LL DO IT LATER */
				} else {
					if($bcs <> "")
						fwrite($fd, "{$bcs}\n");
				}
			}
			if(isset($config['system']['enableserial'])) {
				fwrite($fd, "-D");
			}
			fclose($fd);
		}
		/* serial console - write out /boot/loader.conf */
		$boot_config = file_get_contents("/boot/loader.conf");
		$boot_config_split = explode("\n", $boot_config);
		if(count($boot_config_split) > 0) {
			$new_boot_config = array();
			// Loop through and only add lines that are not empty, and which
			//  do not contain a console directive.
			foreach($boot_config_split as $bcs)
				if(!empty($bcs) && (stripos($bcs, "console") === false))
					$new_boot_config[] = $bcs;

			if(isset($config['system']['enableserial']))
				$new_boot_config[] = 'console="comconsole"';
			file_put_contents("/boot/loader.conf", implode("\n", $new_boot_config));
		}
	}
	$ttys = file_get_contents("/etc/ttys");
	$ttys_split = split("\n", $ttys);
	$fd = fopen("/etc/ttys", "w");
	foreach($ttys_split as $tty) {
		if(stristr($tty, "ttyd0") or stristr($tty, "ttyu0")) {
			if(isset($config['system']['enableserial'])) {
				fwrite($fd, "ttyu0	\"/usr/libexec/getty bootupcli\"	cons25	on	secure\n");
			} else {
				fwrite($fd, "ttyu0	\"/usr/libexec/getty bootupcli\"	cons25	off	secure\n");
			}
		} else {
			fwrite($fd, $tty . "\n");
		}
	}
	fclose($fd);
	auto_login();

	return;
}

function print_value_list($list, $count = 10, $separator = ",")
{
	$list = implode($separator, array_slice($list, 0, $count));
	if(count($list) < $count) {
		$list .= ".";
	} else {
		$list .= "...";
	}
	return $list;
}

function is_dhcp_server_enabled()
{
	global $config;

	$dhcpdenable = false;

	if (!is_array($config['dhcpd']))
		return false;

	$Iflist = get_configured_interface_list();

	foreach ($config['dhcpd'] as $dhcpif => $dhcpifconf) {
		if (isset($dhcpifconf['enable']) && isset($Iflist[$dhcpif])) {
			$dhcpdenable = true;
			break;
		}
	}

	return $dhcpdenable;
}

function get_interface_info($ifdescr)
{
	global $config, $g;

	$ifinfo = array();
	if (empty($config['interfaces'][$ifdescr]))
		return;
	$ifinfo['hwif'] = $config['interfaces'][$ifdescr]['if'];
	$ifinfo['if'] = get_real_interface($ifdescr);

	$chkif = $ifinfo['if'];
	$ifinfotmp = pfSense_get_interface_addresses($chkif);
	$ifinfo['status'] = $ifinfotmp['status'];
	if (empty($ifinfo['status']))
                $ifinfo['status'] = "down";
	$ifinfo['macaddr'] = $ifinfotmp['macaddr'];
	$ifinfo['ipaddr'] = $ifinfotmp['ipaddr'];
	$ifinfo['subnet'] = $ifinfotmp['subnet'];
	if (isset($ifinfotmp['link0']))
		$link0 = "down";
	$ifinfotmp = pfSense_get_interface_stats($chkif);
        $ifinfo['inpkts'] = $ifinfotmp['inpkts'];
        $ifinfo['outpkts'] = $ifinfotmp['outpkts'];
        $ifinfo['inerrs'] = $ifinfotmp['inerrs'];
        $ifinfo['outerrs'] = $ifinfotmp['outerrs'];
        $ifinfo['collisions'] = $ifinfotmp['collisions'];

	/* Use pfctl for non wrapping 64 bit counters */
	/* Pass */
	exec("/sbin/pfctl -vvsI -i {$chkif}", $pfctlstats);
	$pf_in4_pass = preg_split("/ +/ ", $pfctlstats[3]);
	$pf_out4_pass = preg_split("/ +/", $pfctlstats[5]);
	$in4_pass = $pf_in4_pass[5];
	$out4_pass = $pf_out4_pass[5];
	$in4_pass_packets = $pf_in4_pass[3];
	$out4_pass_packets = $pf_out4_pass[3];
	$ifinfo['inbytespass'] = $in4_pass;
	$ifinfo['outbytespass'] = $out4_pass;
	$ifinfo['inpktspass'] = $in4_pass_packets;
	$ifinfo['outpktspass'] = $out4_pass_packets;

	/* Block */
	$pf_in4_block = preg_split("/ +/", $pfctlstats[4]);
	$pf_out4_block = preg_split("/ +/", $pfctlstats[6]);
	$in4_block = $pf_in4_block[5];
	$out4_block = $pf_out4_block[5];
	$in4_block_packets = $pf_in4_block[3];
	$out4_block_packets = $pf_out4_block[3];
	$ifinfo['inbytesblock'] = $in4_block;
	$ifinfo['outbytesblock'] = $out4_block;
	$ifinfo['inpktsblock'] = $in4_block_packets;
	$ifinfo['outpktsblock'] = $out4_block_packets;

	$ifinfo['inbytes'] = $in4_pass + $in4_block;
	$ifinfo['outbytes'] = $out4_pass + $out4_block;
	$ifinfo['inpkts'] = $in4_pass_packets + $in4_block_packets;
	$ifinfo['outpkts'] = $out4_pass_packets + $out4_block_packets;

	$ifconfiginfo = "";
	$link_type = $config['interfaces'][$ifdescr]['ipaddr'];
	switch ($link_type) {
	 /* DHCP? -> see if dhclient is up */
	case "dhcp":
	case "carpdev-dhcp":
		/* see if dhclient is up */
		if (find_dhclient_process($ifinfo['if']) <> "")
			$ifinfo['dhcplink'] = "up";
		else
			$ifinfo['dhcplink'] = "down";

		break;

	default:
		break;
	}

	if ($ifinfo['status'] == "up")
	{
		unset($ifconfiginfo);
		exec("/sbin/ifconfig " . $ifinfo['if'], $ifconfiginfo);
		$matches = "";
		foreach ($ifconfiginfo as $ici) {

			if (preg_match("/media: .*? \((.*?)\)/", $ici, $matches))
			{
				$ifinfo['media'] = $matches[1];
			}

			if (preg_match("/status: (.*)$/", $ici, $matches)) {
				if ($matches[1] != "active")
					$ifinfo['status'] = $matches[1];
				if($ifinfo['status'] == "running")
					$ifinfo['status'] = "up";
			}
			if (preg_match("/channel (\S*)/", $ici, $matches)) {
				$ifinfo['channel'] = $matches[1];
			}
			if (preg_match("/ssid (\".*?\"|\S*)/", $ici, $matches)) {
				if ($matches[1][0] == '"')
					$ifinfo['ssid'] = substr($matches[1], 1, -1);
				else
					$ifinfo['ssid'] = $matches[1];
			}
			if (preg_match("/laggproto (.*)$/", $ici, $matches)) {
				$ifinfo['laggproto'] = $matches[1];
			}
			if (preg_match("/laggport: (.*)$/", $ici, $matches)) {
				$ifinfo['laggport'][] = $matches[1];
			}
		}

		if (interface_has_gateway($ifdescr))
			$ifinfo['gateway'] = get_interface_gateway($ifdescr);
	}

	$bridge = "";
	$bridge = link_interface_to_bridge($ifdescr);
	if($bridge) {
		$bridge_text = `/sbin/ifconfig {$bridge}`;
		if(stristr($bridge_text, "blocking") <> false) {
			$ifinfo['bridge'] = "<b><font color='red'>blocking</font></b> - check for ethernet loops";
			$ifinfo['bridgeint'] = $bridge;
		} else if(stristr($bridge_text, "learning") <> false) {
			$ifinfo['bridge'] = "learning";
			$ifinfo['bridgeint'] = $bridge;
		} else if(stristr($bridge_text, "forwarding") <> false) {
			$ifinfo['bridge'] = "forwarding";
			$ifinfo['bridgeint'] = $bridge;
		}
	}

	return $ifinfo;
}

function get_cpu_speed()
{
	 return exec("sysctl hw.clockrate | awk '{ print $2 }'");
}

function is_fqdn($fqdn)
{
	$hostname = false;
	if(preg_match("/[-A-Z0-9\.]+\.[-A-Z0-9\.]+/i", $fqdn)) {
		$hostname = true;
	}
	if(preg_match("/\.\./", $fqdn)) {
		$hostname = false;
	}
	if(preg_match("/^\./i", $fqdn)) {
		$hostname = false;
	}
	if(preg_match("/\//i", $fqdn)) {
		$hostname = false;
	}
	return($hostname);
}

function pfsense_default_state_size()
{
  $memory = get_memory();
  $avail = $memory[0];
  $max_states = (int) ($avail/10)*1000;
  return $max_states;
}

function pfsense_default_tables_size()
{
	$current = `pfctl -sm | grep ^tables | awk '{print $4};'`;
	return $current;
}

function pfsense_default_table_entries_size()
{
	$current = `pfctl -sm | grep table-entries | awk '{print $4};'`;
	return $current;
}

function isvm()
{
	$virtualenvs = array("vmware", "parallels", "qemu", "bochs", "plex86");
	$bios_product = trim(`/bin/kenv | /usr/bin/awk -F= '/smbios.system.product/ {print $2}'`);
	foreach ($virtualenvs as $virtualenv)
		if (stripos($bios_product, $virtualenv) !== false)
		return true;

	return false;
}

function update_status($status)
{
	global $pkg_interface;

	if($pkg_interface != "console")
	{
		echo "\n<script type=\"text/javascript\">this.document.forms[0].status.value=\"" . $status . "\";</script>";
	}
	else
	{
		echo $status . "\n";
	}
	ob_flush();
}

function update_alias_names_upon_change($section, $field, $new_alias_name, $origname)
{
	global $g, $config, $pconfig, $debug;
	if(!$origname)
		return;

	$sectionref = &$config;
	foreach($section as $sectionname)
	{
		if(is_array($sectionref) && isset($sectionref[$sectionname]))
			$sectionref = &$sectionref[$sectionname];
		else
			return;
	}

	if($debug) $fd = fopen("{$g['tmp_path']}/print_r", "a");
	if($debug) fwrite($fd, print_r($pconfig, true));

	if(is_array($sectionref)) {
		foreach($sectionref as $itemkey => $item) {
			if($debug) fwrite($fd, "$itemkey\n");

			$fieldfound = true;
			$fieldref = &$sectionref[$itemkey];
			foreach($field as $fieldname) {
				if(is_array($fieldref) && isset($fieldref[$fieldname]))
					$fieldref = &$fieldref[$fieldname];
				else {
					$fieldfound = false;
					break;
				}
			}
			if($fieldfound && $fieldref == $origname) {
				if($debug) fwrite($fd, "Setting old alias value $origname to $new_alias_name\n");
				$fieldref = $new_alias_name;
			}
		}
	}

	if($debug) fclose($fd);

}

function update_alias_url_data()
{
	global $config, $g;

	$lockkey = lock('config');
	if (is_array($config['aliases']['alias']))
	{
		foreach ($config['aliases']['alias'] as $x => $alias)
		{
			if (empty($alias['aliasurl']))
				continue;

			$isfirst = 0;
			$temp_filename = tempnam("{$g['tmp_path']}/", "alias_import");
			unlink($temp_filename);
			$fda = fopen("{$g['tmp_path']}/tmpfetch","w");
			fwrite($fda, "/usr/bin/fetch -q -o \"{$temp_filename}/aliases\" \"" . $config['aliases']['alias'][$x]['aliasurl'] . "\"");
			fclose($fda);
			mwexec("/bin/mkdir -p {$temp_filename}");
			mwexec("/usr/bin/fetch -q -o \"{$temp_filename}/aliases\" \"" . $config['aliases']['alias'][$x]['aliasurl'] . "\"");
			if(stristr($alias['aliasurl'], ".tgz"))
				process_alias_tgz($temp_filename);
			else if(stristr($alias['aliasurl'], ".zip"))
				process_alias_unzip($temp_filename);
			if(file_exists("{$temp_filename}/aliases")) {
				$file_contents = file_get_contents("{$temp_filename}/aliases");
				$file_contents = str_replace("#", "\n#", $file_contents);
				$file_contents_split = split("\n", $file_contents);
				foreach($file_contents_split as $fc) {
					$tmp = trim($fc);
					if(stristr($fc, "#")) {
						$tmp_split = split("#", $tmp);
						$tmp = trim($tmp_split[0]);
					}
					if(trim($tmp) <> "") {
						if($isfirst == 1)
							$address .= " ";
						$address .= $tmp;
						$isfirst = 1;
					}
				}
				if($isfirst > 0) {
					$config['aliases']['alias'][$x]['address'] = $address;
					$updated = true;
				}
				mwexec("/bin/rm -rf {$temp_filename}");
			}
		}
	}
	if($updated)
		write_config("URL tablosu(Takma Ad) guncellendi");
	unlock($lockkey);
}

function process_alias_unzip($temp_filename)
{
	if(!file_exists("/usr/local/bin/unzip"))
		return;
	mwexec("/bin/mv {$temp_filename}/aliases {$temp_filename}/aliases.zip");
	mwexec("/usr/local/bin/unzip {$temp_filename}/aliases.tgz -d {$temp_filename}/aliases/");
	unlink("{$temp_filename}/aliases.zip");
	$files_to_process = return_dir_as_array("{$temp_filename}/");
	$fd = fopen("{$temp_filename}/aliases", "w");
	foreach($files_to_process as $f2p) {
		$file_contents = file_get_contents($f2p);
		fwrite($fd, $file_contents);
		unlink($f2p);
	}
	fclose($fd);
}

function process_alias_tgz($temp_filename)
{
	if(!file_exists("/usr/bin/tar"))
		return;
	mwexec("/bin/mv {$temp_filename}/aliases {$temp_filename}/aliases.tgz");
	mwexec("/usr/bin/tar xzf {$temp_filename}/aliases.tgz -C {$temp_filename}/aliases/");
	unlink("{$temp_filename}/aliases.tgz");
	$files_to_process = return_dir_as_array("{$temp_filename}/");
	$fd = fopen("{$temp_filename}/aliases", "w");
	foreach($files_to_process as $f2p) {
		$file_contents = file_get_contents($f2p);
		fwrite($fd, $file_contents);
		unlink($f2p);
	}
	fclose($fd);
}

function process_alias_urltable($name, $url, $freq, $forceupdate=false)
{
	$urltable_prefix = "/var/db/aliastables/";
	$urltable_filename = $urltable_prefix . $name . ".txt";

	if (!file_exists($urltable_prefix))
	{
		mkdir($urltable_prefix);
	}
	elseif (!is_dir($urltable_prefix))
	{
		unlink($urltable_prefix);
		mkdir($urltable_prefix);
	}

	if (!file_exists($urltable_filename) || ((time() - filemtime($urltable_filename)) > ($freq * 86400)) || $forceupdate)
	{
		unlink_if_exists($urltable_filename . ".tmp");
		mwexec("/usr/bin/fetch -q -o " . escapeshellarg($urltable_filename . ".tmp") . " " . escapeshellarg($url));
		if (file_exists($urltable_filename . ".tmp"))
		{
			mwexec("/usr/bin/grep -v '^#' " . escapeshellarg($urltable_filename . ".tmp") . " > " . escapeshellarg($urltable_filename));
			unlink_if_exists($urltable_filename . ".tmp");
		}
		else
			mwexec("/usr/bin/touch {$urltable_filename}");
		return true;
	}
	else
		return -1;
}

function get_real_slice_from_glabel($label)
{
	$label = escapeshellarg($label);
	return trim(`/sbin/glabel list | /usr/bin/grep -B2 ufs/{$label} | /usr/bin/head -n 1 | /usr/bin/cut -f3 -d' '`);
}

function get_include_contents($filename)
{
    if (is_file($filename))
	{
        ob_start();
        include $filename;
        $contents = ob_get_contents();
        ob_end_clean();
        return $contents;
    }
    return false;
}

function filter_rules_sort()
{
	global $config;

	for ($i = 0; isset($config['filter']['rule'][$i]); $i++)
		$config['filter']['rule'][$i]['seq'] = $i;

	usort($config['filter']['rule'], "filter_rules_compare");

	for ($i = 0; isset($config['filter']['rule'][$i]); $i++)
		unset($config['filter']['rule'][$i]['seq']);
}

function filter_rules_compare($a, $b)
{
	if (isset($a['floating']) && isset($b['floating']))
		return $a['seq'] - $b['seq'];
	else if (isset($a['floating']))
		return -1;
	else if (isset($b['floating']))
		return 1;
	else if ($a['interface'] == $b['interface'])
		return $a['seq'] - $b['seq'];
	else
		return compare_interface_friendly_names($a['interface'], $b['interface']);
}

function load_mac_manufacturer_table()
{
	$macs = false;
	if (file_exists("/usr/local/share/nmap/nmap-mac-prefixes"))
		$macs=file("/usr/local/share/nmap/nmap-mac-prefixes");
	if ($macs)
	{
		foreach ($macs as $line)
		{
			if (preg_match('/([0-9A-Fa-f]{6}) (.*)$/', $line, $matches))
			{
				$mac_man["$matches[1]"]=$matches[2];
			}
		}
 		return $mac_man;
	}
	else
		return -1;
}

function pfSense_handle_custom_code($src_dir)
{
	if(is_dir("$src_dir"))
	{
		$cf = glob($src_dir . "/*.inc");
		foreach($cf as $nf)
		{
			if($nf == "." || $nf == "..")
				continue;
			include("$nf");
		}
	}
}

?>
