<?php
/*
	util.inc
	part of the pfSense project (http://www.pfsense.com)

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.

	originally part of m0n0wall (http://m0n0.ch/wall)
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

function killbypid($pidfile)
{
	return sigkillbypid($pidfile, "TERM");
}

function isvalidpid($pid)
{
	$output = "";
	exec("/bin/pgrep -nF {$pid}", $output, $retval);

	return (intval($retval) == 0);
}

function is_process_running($process)
{
	$output = "";
	exec("/bin/pgrep -anx {$process}", $output, $retval);
	return (intval($retval) == 0);
}

function isvalidproc($proc) {
	return is_process_running($proc);
}

function sigkillbypid($pidfile, $sig)
{
	if (file_exists($pidfile))
		return mwexec("/bin/pkill -{$sig} -F {$pidfile}", true);

	return 0;
}

function sigkillbyname($procname, $sig)
{
	if(isvalidproc($procname))
		return mwexec("/usr/bin/killall -{$sig} " . escapeshellarg($procname), true);
}

function killbyname($procname)
{
	if(isvalidproc($procname))
		mwexec("/usr/bin/killall " . escapeshellarg($procname));
}

function is_subsystem_dirty($subsystem = "")
{
	global $g;

	if ($subsystem == "")
		return false;

	if (file_exists("{$g['varrun_path']}/{$subsystem}.dirty"))
		return true;

	return false;
}

function mark_subsystem_dirty($subsystem = "")
{
	global $g;

	if (!file_put_contents("{$g['varrun_path']}/{$subsystem}.dirty", "DIRTY"))
		log_error("WARNING: Could not mark subsystem: {$subsytem} dirty");
}

function clear_subsystem_dirty($subsystem = "")
{
	global $g;

	@unlink("{$g['varrun_path']}/{$subsystem}.dirty");
}

function config_lock()
{
	return;
}

function config_unlock()
{
	return;
}

function lock($lock, $op = LOCK_SH)
{
	global $g, $cfglckkeyconsumers;
	if (!$lock)
		die("WARNING: You must give a name as parameter to lock() function.");
	if (!file_exists("{$g['tmp_path']}/{$lock}.lock")) {
		@touch("{$g['tmp_path']}/{$lock}.lock");
		@chmod("{$g['tmp_path']}/{$lock}.lock", 0666);
	}
	$cfglckkeyconsumers++;
	if ($fp = fopen("{$g['tmp_path']}/{$lock}.lock", "w"))
	{
		if (flock($fp, $op))
			return $fp;
		else
			fclose($fp);
	}
}

function unlock($cfglckkey = 0)
{
	global $g, $cfglckkeyconsumers;
	flock($cfglckkey, LOCK_UN);
	fclose($cfglckkey);
	return;
}

function send_event($cmd)
{
	global $g;

	if(!isset($g['event_address']))
		$g['event_address'] = "unix:///var/run/check_reload_status";

	$try = 0;
	while ($try < 3)
	{
		$fd = @fsockopen($g['event_address']);
		if ($fd) {
			fwrite($fd, $cmd);
			$resp = fread($fd, 4096);
			if ($resp != "OK\n")
				log_error("send_event: sent {$cmd} got {$resp}");
			fclose($fd);
			$try = 3;
		} else if (!is_process_running("check_reload_status"))
			mwexec_bg("/usr/bin/nice -n20 /usr/local/sbin/check_reload_status");
		$try++;
	}
}

function send_multiple_events($cmds)
{
	global $g;

	if(!isset($g['event_address']))
		$g['event_address'] = "unix:///var/run/check_reload_status";

	if (!is_array($cmds))
		return;

	foreach ($cmds as $cmd)
		send_event($cmd);
}

function refcount_init($reference)
{
	$shmid = @shmop_open($reference, "c", 0644, 10);
	@shmop_write($shmid, 0, 0);
	@shmop_close($shmid);
}

function refcount_reference($reference)
{
	try {
		$shmid = @shmop_open($reference, "w", 0, 0);
		if (!$shmid) {
			refcount_init($reference);
			$shmid = @shmop_open($reference, "w", 0, 0);
		}
		$shm_data = @shmop_read($shmid, 0, 10);
		if (intval($shm_data) < 0)
			$shm_data = 0;
		$shm_data = intval($shm_data) + 1;
		@shmop_write($shmid, $shm_data, 0);
		@shmop_close($shmid);
	} catch (Exception $e) {
		log_error($e->getMessage());
	}

	return $shm_data;
}

function is_module_loaded($module_name)
{
	$running = `/sbin/kldstat | grep {$module_name} | /usr/bin/grep -v grep | /usr/bin/wc -l`;
	if (intval($running) >= 1)
		return true;
	else
		return false;
}

function gen_subnet($ipaddr, $bits)
{
	if (!is_ipaddr($ipaddr) || !is_numeric($bits))
		return "";

	return long2ip(ip2long($ipaddr) & gen_subnet_mask_long($bits));
}

function gen_subnet_max($ipaddr, $bits) {
	if (!is_ipaddr($ipaddr) || !is_numeric($bits))
		return "";

	return long2ip32(ip2long($ipaddr) | ~gen_subnet_mask_long($bits));
}

function gen_subnet_mask_long($bits)
{
	$sm = 0;
	for ($i = 0; $i < $bits; $i++)
	{
		$sm >>= 1;
		$sm |= 0x80000000;
	}
	return $sm;
}

function gen_subnet_mask($bits)
{
	return long2ip(gen_subnet_mask_long($bits));
}

function long2ip32($ip)
{
	return long2ip($ip & 0xFFFFFFFF);
}

function ip2long32($ip)
{
	return ( ip2long($ip) & 0xFFFFFFFF );
}

function ip2ulong($ip)
{
	return sprintf("%u", ip2long32($ip));
}

function ip_range_size($startip, $endip)
{
	if (is_ipaddr($startip) && is_ipaddr($endip))
	{
		return abs(ip2ulong($startip) - ip2ulong($endip)) + 1;
	}
	return -1;
}

function find_smallest_cidr($number)
{
	$smallest = 1;
	for ($b=32; $b > 0; $b--) {
		$smallest = ($number <= pow(2,$b)) ? $b : $smallest;
	}
	return (32-$smallest);
}

function ip_before($ip)
{
	return long2ip32(ip2long($ip)-1);
}

function ip_after($ip)
{
	return long2ip32(ip2long($ip)+1);
}

function ip_less_than($ip1, $ip2)
{
	return ip2ulong($ip1) < ip2ulong($ip2);
}

function ip_greater_than($ip1, $ip2)
{
	return ip2ulong($ip1) > ip2ulong($ip2);
}

function ip_range_to_subnet_array($startip, $endip)
{
	if (!is_ipaddr($startip) || !is_ipaddr($endip)) {
		return array();
	}

	$rangesubnets = array();

	$cidr = find_smallest_cidr(ip_range_size($startip, $endip));

	for ($cidr; $cidr <= 32; $cidr++)
	{
		$targetsub_min = gen_subnet($startip, $cidr);
		$targetsub_max = gen_subnet_max($startip, $cidr);

		if (($targetsub_min == $startip) && ($targetsub_max == $endip))
		{
			return array("{$startip}/{$cidr}");
		}

		if (($targetsub_min == $startip) && ip_less_than($targetsub_max, $endip))
		{
			break;
		}

		if (ip_greater_than($targetsub_min, $startip) && ($targetsub_max == $endip))
		{
			break;
		}

		if (ip_greater_than($targetsub_min, $startip) && ip_less_than($targetsub_max, $endip))
		{
			break;
		}
	}

	if ($startip != $targetsub_min)
	{
		$rangesubnets = array_merge($rangesubnets, ip_range_to_subnet_array($startip, ip_before($targetsub_min)));
	}

	$rangesubnets[] = "{$targetsub_min}/{$cidr}";

	if ($endip != $targetsub_max)
	{
		$rangesubnets = array_merge($rangesubnets, ip_range_to_subnet_array(ip_after($targetsub_max), $endip));
	}
	return $rangesubnets;
}

function is_iprange($range)
{
	if (substr_count($range, '-') != 1)
	{
		return false;
	}

	list($ip1, $ip2) = explode ('-', $range);
	return (is_ipaddr($ip1) && is_ipaddr($ip2));
}

function is_numericint($arg)
{
	return (preg_match("/[^0-9]/", $arg) ? false : true);
}


function is_ipaddr($ipaddr)
{
	if (!is_string($ipaddr))
		return false;

	$ip_long = ip2long($ipaddr);
	$ip_reverse = long2ip32($ip_long);

	if ($ipaddr == $ip_reverse)
		return true;
	else
		return false;
}

function is_ipaddroralias($ipaddr)
{
	global $config;

	if (is_alias($ipaddr))
	{
		if (is_array($config['aliases']['alias']))
		{
			foreach ($config['aliases']['alias'] as $alias)
			{
				if ($alias['name'] == $ipaddr && $alias['type'] != "port")
					return true;
			}
		}
		return false;
	}

	else
		return is_ipaddr($ipaddr);
}


function is_subnet($subnet)
{
	if (!is_string($subnet))
		return false;

	list($hp,$np) = explode('/', $subnet);

	if (!is_ipaddr($hp))
		return false;

	if (!is_numeric($np) || ($np < 1) || ($np > 32))
		return false;

	return true;
}

function is_subnetoralias($subnet)
{
	global $aliastable;

	if (isset($aliastable[$subnet]) && is_subnet($aliastable[$subnet]))
		return true;
	else
		return is_subnet($subnet);
}

function is_hostname($hostname)
{
	if (!is_string($hostname))
		return false;

	if (preg_match('/^(?:(?:[a-z0-9_]|[a-z0-9_][a-z0-9_\-]*[a-z0-9_])\.)*(?:[a-z0-9_]|[a-z0-9_][a-z0-9_\-]*[a-z0-9_])$/i', $hostname))
		return true;
	else
		return false;
}

function is_domain($domain)
{
	if (!is_string($domain))
		return false;

	if (preg_match('/^(?:(?:[a-z0-9]|[a-z0-9][a-z0-9\-]*[a-z0-9])\.)*(?:[a-z0-9]|[a-z0-9][a-z0-9\-]*[a-z0-9])$/i', $domain))
		return true;
	else
		return false;
}

function is_mac($macaddr)
{
	return preg_match('/^[0-9A-F]{2}(?:[-][0-9A-F]{2}){5}$/i', $macaddr) == 1 ? true : false;
}

function is_macaddr($macaddr) {
	return preg_match('/^[0-9A-F]{2}(?:[:][0-9A-F]{2}){5}$/i', $macaddr) == 1 ? true : false;
}

function is_validaliasname($name)
{
	$reserved = array("port", "pass");
	if (in_array($name, $reserved, true))
		return;
	if (!preg_match("/[^a-zA-Z0-9_]/", $name) && (strlen($name) < 32))
		return true;
	else
		return false;
}

function is_port($port)
{
	$tmpports = explode(":", $port);
	foreach($tmpports as $tmpport) {
		if (getservbyname($tmpport, "tcp") || getservbyname($tmpport, "udp"))
			continue;
		if (!ctype_digit($tmpport))
			return false;
		else if ((intval($tmpport) < 1) || (intval($tmpport) > 65535))
			return false;
	}
	return true;
}

function is_portrange($portrange)
{
	$ports = explode(":", $portrange);

	if(count($ports) == 2 && is_port($ports[0]) && is_port($ports[1]))
		return true;
	else
		return false;
}

function is_portoralias($port)
{
	global $config;

	if (is_alias($port))
	{
		if (is_array($config['aliases']['alias']))
		{
			foreach ($config['aliases']['alias'] as $alias)
			{
				if ($alias['name'] == $port && $alias['type'] == "port")
					return true;
				}
			}
			return false;
	}
	else
		return is_port($port);
}

function is_valid_shaperbw($val)
{
	return (preg_match("/^(\d+(?:\.\d+)?)([MKG]?b|%)$/", $val));
}


function get_configured_ip_aliases_list()
{
	global $config;

	$alias_list=array();

	if(is_array($config['virtualip']['vip'])) {
		$viparr = &$config['virtualip']['vip'];
		foreach ($viparr as $vip) {
			if ($vip['mode']=="ipalias") {
				$alias_list[$vip['subnet']] = $vip['interface'];
			}
		}
	}

	return $alias_list;
}

function compare_interface_friendly_names($a, $b)
{
	if ($a == $b)
		return 0;
	else if ($a == 'wan')
		return -1;
	else if ($b == 'wan')
		return 1;
	else if ($a == 'lan')
		return -1;
	else if ($b == 'lan')
		return 1;

	return strnatcmp($a, $b);
}

function get_configured_interface_list($only_opt = false, $withdisabled = false) {
	global $config;

	$iflist = array();

	if(!is_array($config['interfaces']))
		$config = parse_config(true);

	/* if list */
	foreach($config['interfaces'] as $if => $ifdetail) {
		if ($only_opt && ($if == "wan" || $if == "lan"))
			continue;
		if (isset($ifdetail['enable']) || $withdisabled == true)
			$iflist[$if] = $if;
	}

	return $iflist;
}

function get_configured_interface_list_by_realif($only_opt = false, $withdisabled = false)
{
	global $config;

	$iflist = array();

	foreach($config['interfaces'] as $if => $ifdetail)
	{
		if ($only_opt && ($if == "wan" || $if == "lan"))
			continue;

		if (isset($ifdetail['enable']) || $withdisabled == true)
		{
			$tmpif = get_real_interface($if);
			if (!empty($tmpif))
				$iflist[$tmpif] = $if;
		}
	}

	return $iflist;
}


function get_configured_interface_with_descr($only_opt = false, $withdisabled = false) {
	global $config;

	$iflist = array();

	if(is_array($config['interfaces']))
	{
		foreach($config['interfaces'] as $if => $ifdetail) {
			if ($only_opt && ($if == "wan" || $if == "lan"))
				continue;
			if (isset($ifdetail['enable']) || $withdisabled == true) {
				if(empty($ifdetail['descr']))
					$iflist[$if] = strtoupper($if);
				else
					$iflist[$if] = strtoupper($ifdetail['descr']);
			}
		}
	}

	return $iflist;
}


function get_configured_ip_addresses()
{
	require_once("interfaces.inc");
	$ip_array = array();
	$interfaces = get_configured_interface_list();
	if(is_array($interfaces)) {
		foreach($interfaces as $int) {
			$ipaddr = get_interface_ip($int);
			$ip_array[$int] = $ipaddr;
		}
	}

	return $ip_array;
}

function get_interface_list($mode = "active", $keyby = "physical", $vfaces = "")
{
	global $config;
	$upints = array();
        if(!$vfaces) {
		$vfaces = array (
				'bridge',
				'ppp',
				'pppoe',
				'pptp',
				'l2tp',
				'sl',
				'gif',
				'gre',
				'faith',
				'lo',
				'ng',
				'_vlan',
				'_wlan',
				'pflog',
				'plip',
				'pfsync',
				'enc',
				'tun',
				'carp',
				'lagg',
				'vip',
				'ipfw'
		);
	}
	switch($mode) {
	case "active":
                $upints = pfSense_interface_listget(IFF_UP);
        	break;
	case "media":
		$intlist = pfSense_interface_listget();
                $ifconfig = "";
                exec("/sbin/ifconfig -a", $ifconfig);
                $regexp = '/(' . implode('|', $intlist) . '):\s/';
                $ifstatus = preg_grep('/status:/', $ifconfig);
		foreach($ifstatus as $status)
		{
			$int = array_shift($intlist);
			if(stristr($status, "active")) $upints[] = $int;
		}
		break;
	default:
		$upints = pfSense_interface_listget();
		break;
	}
        $linkinfo = "";
        exec("/usr/bin/netstat -inW -f link | awk '{ print $1, $4 }'", $linkinfo);
        array_shift($linkinfo);
	$ipinfo = "";
	exec("/usr/bin/netstat -inW -f inet | awk '{ print $1, $4 }'", $ipinfo);
	array_shift($ipinfo);
	foreach($linkinfo as $link) {
		$friendly = "";
		$alink = explode(" ", $link);
		$ifname = rtrim(trim($alink[0]), '*');
		if (!in_array(array_shift(preg_split('/\d/', $ifname)), $vfaces) &&
			!stristr($ifname, "_vlan") && !stristr($ifname, "_wlan")) {
			$toput = array(
					"mac" => trim($alink[1]),
					"up" => in_array($ifname, $upints)
				);
			foreach($ipinfo as $ip) {
				$aip = explode(" ", $ip);
				if($aip[0] == $ifname) {
					$toput['ipaddr'] = $aip[1];
				}
			}
			if (is_array($config['interfaces'])) {
				foreach($config['interfaces'] as $name => $int)
					if($int['if'] == $ifname) $friendly = $name;
			}
			switch($keyby) {
			case "physical":
				if($friendly != "") {
					$toput['friendly'] = $friendly;
				}
				$dmesg_arr = array();
				exec("/sbin/dmesg |grep $ifname | head -n1", $dmesg_arr);
				preg_match_all("/<(.*?)>/i", $dmesg_arr[0], $dmesg);
				$toput['dmesg'] = $dmesg[1][0];
				$iflist[$ifname] = $toput;
				break;
			case "ppp":

			case "friendly":
				if($friendly != "") {
					$toput['if'] = $ifname;
					$iflist[$friendly] = $toput;
				}
				break;
			}
		}
	}
	return $iflist;
}

function log_error($error)
{
	$page = $_SERVER['SCRIPT_NAME'];
	syslog(LOG_WARNING, "$page: $error");
	return;
}

function log_auth($error)
{
	$page = $_SERVER['SCRIPT_NAME'];
	syslog(LOG_AUTH, "$page: $error");
	return;
}

function exec_command($command)
{
	$output = array();
	exec($command . ' 2>&1 ', $output);
	return(implode("\n", $output));
}


function mwexec($command, $mute = false)
{
	$oarr = array();
	$retval = 0;
	$garbage = exec("$command 2>&1", $oarr, $retval);

	if(isset($config['system']['developerspew']))
		$mute = false;
	if(($retval <> 0) && ($mute === false))
	{
		$output = implode(" ", $oarr);
		log_error("The command '$command' returned exit code '$retval', the output was '$output' ");
	}
	return $retval;
}

function mwexec_bg($command)
{
	exec("nohup $command > /dev/null 2>&1 &");
}

function unlink_if_exists($fn)
{
	$to_do = glob($fn);
	if(is_array($to_do)) {
		foreach($to_do as $filename)
			@unlink($filename);
	} else {
		@unlink($fn);
	}
}

function alias_make_table($config)
{
	global $aliastable;

	$aliastable = array();

	if (is_array($config['aliases']['alias'])) {
		foreach ($config['aliases']['alias'] as $alias) {
			if ($alias['name'])
				$aliastable[$alias['name']] = $alias['address'];
		}
	}
}

function is_alias($name)
{
	global $aliastable;

	return isset($aliastable[$name]);
}

function alias_expand($name)
{
	global $aliastable;

	if (isset($aliastable[$name]))
		return "\${$name}";
	else if (is_ipaddr($name) || is_subnet($name) || is_port($name))
		return "{$name}";
	else
		return null;
}

function alias_expand_urltable($name)
{
	global $config;
	$urltable_prefix = "/var/db/aliastables/";
	$urltable_filename = $urltable_prefix . $name . ".txt";

	foreach ($config['aliases']['alias'] as $alias) {
		if (($alias['type'] == 'urltable') && ($alias['name'] == $name))
		{
			if (is_URL($alias["url"]) && file_exists($urltable_filename) && filesize($urltable_filename))
				return $urltable_filename;
			else if (process_alias_urltable($name, $alias["url"], 0, true))
				return $urltable_filename;
		}
	}
	return null;
}

function check_subnets_overlap($subnet1, $bits1, $subnet2, $bits2)
{
	if (!is_numeric($bits1))
		$bits1 = 32;
	if (!is_numeric($bits2))
		$bits2 = 32;

	if ($bits1 < $bits2)
		$relbits = $bits1;
	else
		$relbits = $bits2;

	$sn1 = gen_subnet_mask_long($relbits) & ip2long($subnet1);
	$sn2 = gen_subnet_mask_long($relbits) & ip2long($subnet2);

	if ($sn1 == $sn2)
		return true;
	else
		return false;
}

function ipcmp($a, $b)
{
	if (ip_less_than($a, $b))
		return -1;
	else if (ip_greater_than($a, $b))
		return 1;
	else
		return 0;
}

function ip_in_subnet($addr,$subnet)
{
	list($ip, $mask) = explode('/', $subnet);
	$mask = (0xffffffff << (32 - $mask)) & 0xffffffff;
	return ((ip2long($addr) & $mask) == (ip2long($ip) & $mask));
}

function verify_digital_signature($fname)
{
	global $g;

	if(!file_exists("/usr/local/sbin/gzsig"))
		return 4;

	return mwexec("/usr/local/sbin/gzsig verify {$g['etc_path']}/pubkey.pem < " . escapeshellarg($fname));
}

function arp_get_mac_by_ip($ip)
{
	mwexec("/sbin/ping -c 1 -t 1 {$ip}", true);
	$arpoutput = "";
	exec("/usr/sbin/arp -n {$ip}", $arpoutput);

	if ($arpoutput[0]) {
		$arpi = explode(" ", $arpoutput[0]);
		$macaddr = $arpi[3];
		if (is_macaddr($macaddr))
			return $macaddr;
		else
			return false;
	}

	return false;
}

function xml_safe_fieldname($fieldname)
{
	$replace = array('/', '-', ' ', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')',
			 '_', '+', '=', '{', '}', '[', ']', '|', '/', '<', '>', '?',
			 ':', ',', '.', '\'', '\\'
		);
	return strtolower(str_replace($replace, "", $fieldname));
}

function mac_format($clientmac)
{
    global $config;

    $mac = explode(":", $clientmac);
    $mac_format = $config['captiveportal']['radmac_format'] ? $config['captiveportal']['radmac_format'] : false;

    switch($mac_format) {
        case 'singledash':
		return "$mac[0]$mac[1]$mac[2]-$mac[3]$mac[4]$mac[5]";

        case 'ietf':
		return "$mac[0]-$mac[1]-$mac[2]-$mac[3]-$mac[4]-$mac[5]";

        case 'cisco':
		return "$mac[0]$mac[1].$mac[2]$mac[3].$mac[4]$mac[5]";

        case 'unformatted':
		return "$mac[0]$mac[1]$mac[2]$mac[3]$mac[4]$mac[5]";

        default:
		return $clientmac;
    }
}

function resolve_retry($hostname, $retries = 5)
{
	if (is_ipaddr($hostname))
		return $hostname;

	for ($i = 0; $i < $retries; $i++) {
		$ip = gethostbyname($hostname);

		if ($ip && $ip != $hostname) {
			/* success */
			return $ip;
		}

		sleep(1);
	}

	return false;
}

function format_bytes($bytes)
{
	if ($bytes >= 1073741824) {
		return sprintf("%.2f GB", $bytes/1073741824);
	} else if ($bytes >= 1048576) {
		return sprintf("%.2f MB", $bytes/1048576);
	} else if ($bytes >= 1024) {
		return sprintf("%.0f KB", $bytes/1024);
	} else {
		return sprintf("%d bytes", $bytes);
	}
}

function update_filter_reload_status($text)
{
	global $g;

	file_put_contents("{$g['varrun_path']}/filter_reload_status", $text);
}

function return_dir_as_array($dir)
{
	$dir_array = array();
	if (is_dir($dir)) {
		if ($dh = opendir($dir)) {
			while (($file = readdir($dh)) !== false) {
				$canadd = 0;
				if($file == ".") $canadd = 1;
				if($file == "..") $canadd = 1;
				if($canadd == 0)
					array_push($dir_array, $file);
			}
			closedir($dh);
		}
	}
	return $dir_array;
}

function run_plugins($directory)
{
	global $config, $g;
	$files = return_dir_as_array($directory);
	if (is_array($files)) {
		foreach ($files as $file) {
			if (stristr($file, ".sh") == true)
				mwexec($directory . $file . " start");
			else if (!is_dir($directory . "/" . $file) && stristr($file,".inc"))
				require_once($directory . "/" . $file);
		}
	}
}

function safe_mkdir($path, $mode=0755)
{
	global $g;

	if (!is_file($path) && !is_dir($path)) {
		return @mkdir($path, $mode, true);
	} else {
		return false;
	}
}

function make_dirs($path, $mode = 0755)
{
	$base = '';
	foreach (explode('/', $path) as $dir) {
		$base .= "/$dir";
		if (!is_dir($base)) {
			if (!@mkdir($base, $mode))
				return false;
		}
	}
	return true;
}

function get_sysctl($names)
{
	if (empty($names))
		return array();

	if (is_array($names)) {
		$name_list = array();
		foreach ($names as $name) {
			$name_list[] = escapeshellarg($name);
		}
	} else
		$name_list = array(escapeshellarg($names));

	exec("/sbin/sysctl -i " . implode(" ", $name_list), $output);
	$values = array();
	foreach ($output as $line) {
		$line = explode(": ", $line, 2);
		if (count($line) == 2)
			$values[$line[0]] = $line[1];
	}

	return $values;
}

function set_sysctl($values)
{
	if (empty($values))
		return array();

	$value_list = array();
	foreach ($values as $key => $value)
	{
		$value_list[] = escapeshellarg($key) . "=" . escapeshellarg($value);
	}

	exec("/sbin/sysctl -i " . implode(" ", $value_list), $output, $success);

	if ($success <> 0 && count($value_list) > 1) {
		foreach ($value_list as $value) {
			exec("/sbin/sysctl -i " . $value, $output);
		}
	}

	$ret = array();
	foreach ($output as $line) {
		$line = explode(": ", $line, 2);
		if (count($line) == 2)
			$ret[$line[0]] = true;
	}

	return $ret;
}

function get_memory()
{
	$matches = "";
	if(file_exists("/var/log/dmesg.boot"))
		$mem = `cat /var/log/dmesg.boot | grep memory`;
	else
		$mem = `dmesg -a | grep memory`;
	if (preg_match_all("/avail memory.* \((.*)MB\)/", $mem, $matches))
		return array($matches[1][0], $matches[1][0]);
	if(!$real && !$avail) {
		$real = trim(`sysctl hw.physmem | cut -d' ' -f2`);
		$avail = trim(`sysctl hw.realmem | cut -d' ' -f2`);
		/* convert from bytes to megabytes */
		return array(($real/1048576),($avail/1048576));
	}
}

function mute_kernel_msgs()
{
	global $config;
	if($config['system']['enableserial'])
		return;
	exec("/sbin/conscontrol mute on");
}

function unmute_kernel_msgs()
{
	global $config;
	exec("/sbin/conscontrol mute off");
}

function start_devd()
{
	global $g;

	exec("/sbin/devd");
	sleep(1);
}

function is_interface_vlan_mismatch()
{
	global $config, $g;

	if (is_array($config['vlans']['vlan'])) {
		foreach ($config['vlans']['vlan'] as $vlan) {
			if (does_interface_exist($vlan['if']) == false)
				return true;
		}
	}

	return false;
}

function is_interface_mismatch()
{
	global $config, $g;

	$do_assign = false;
	$i = 0;
	if (is_array($config['interfaces'])) {
		foreach ($config['interfaces'] as $ifname => $ifcfg) {
			if (preg_match("/^enc|^cua|^tun|^l2tp|^pptp|^ppp|^ovpn|^gif|^gre|^lagg|^bridge|vlan|_wlan/i", $ifcfg['if'])) {
				// Do not check these interfaces.
				$i++;
				continue;
			}
			else if (does_interface_exist($ifcfg['if']) == false) {
				$do_assign = true;
			} else
				$i++;
		}
	}

	if ($g['minimum_nic_count'] > $i) {
		$do_assign = true;
	} else if (file_exists("{$g['tmp_path']}/assign_complete"))
	$do_assign = false;

	return $do_assign;
}

function timeout($timer = 9)
{
	while(!isset($key)) {
		if ($timer >= 9) { echo chr(8) . chr(8) . ($timer==9 ? chr(32) : null)  . "{$timer}";  }
		else { echo chr(8). "{$timer}"; }
		`/bin/stty -icanon min 0 time 25`;
		$key = trim(`KEY=\`dd count=1 2>/dev/null\`; echo \$KEY`);
		`/bin/stty icanon`;
		if ($key == '')
			unset($key);
		$timer--;
		if ($timer == 0)
			break;
	}
	return $key;
}

function msort($array, $id="id", $sort_ascending=true)
{
	$temp_array = array();
	while(count($array)>0) {
		$lowest_id = 0;
		$index=0;
		foreach ($array as $item) {
			if (isset($item[$id])) {
				if ($array[$lowest_id][$id]) {
					if (strtolower($item[$id]) < strtolower($array[$lowest_id][$id])) {
						$lowest_id = $index;
					}
				}
			}
			$index++;
		}
		$temp_array[] = $array[$lowest_id];
		$array = array_merge(array_slice($array, 0,$lowest_id), array_slice($array, $lowest_id+1));
	}
	if ($sort_ascending) {
		return $temp_array;
	} else {
    	return array_reverse($temp_array);
	}
}

function color($color = "0m")
{
	global $TERM;
	if(!$TERM)
		$TERM=`/usr/bin/env | grep color`;
	if(!$TERM)
		$TERM=`/usr/bin/env | grep cons25`;
	if($TERM) {
		$ESCAPE=chr(27);
		switch ($color) {
			case "black":
				return "{$ESCAPE}[30m";
			case "red":
				return "{$ESCAPE}[31m";
			case "green":
				return "{$ESCAPE}[32m";
			case "yellow":
				return "{$ESCAPE}[33m";
			case "blue":
				return "{$ESCAPE}[34m";
			case "magenta":
				return "{$ESCAPE}[35m";
			case "cyan":
				return "{$ESCAPE}[36m";
			case "white":
				return "{$ESCAPE}[37m";
			case "default":
				return "{$ESCAPE}[39m";
		}
		return "{$ESCAPE}[{$color}";
	}
}

function is_URL($url)
{
	$match = preg_match("'\b(([\w-]+://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))'", $url);
	if($match)
		return true;
	return false;
}

function is_file_included($file = "")
{
	$files = get_included_files();
	if (in_array($file, $files))
		return true;

	return false;
}

function get_staticroutes($returnsubnetsonly = false)
{
	global $config;
	require_once('filter.inc');
	$allstaticroutes = array();
	$allsubnets = array();

	if (!is_array($config['staticroutes']['route']))
		return array();

	foreach ($config['staticroutes']['route'] as $route)
	{
		if (is_alias($route['network']))
		{
			$subnets = filter_expand_alias_array($route['network']);
			foreach ($subnets as $net)
			{
				if (is_ipaddr($net))
					$net .= "/32";
				if (!is_subnet($net))
					continue;
				$temproute = $route;
				$temproute['network'] = $net;
				$allstaticroutes[] = $temproute;
				$allsubnets[] = $net;
			}
		}
		elseif (is_subnet($route['network']))
		{
			$allstaticroutes[] = $route;
			$allsubnets[] = $route['network'];
		}
	}

	if ($returnsubnetsonly)
	{
		return $allsubnets;
	} else {
		return $allstaticroutes;
	}
}

function check_hour($h)
{
	return preg_match("/(2[0-3]|[01][0-9]):([0-5][0-9])/", $h);
}

?>
