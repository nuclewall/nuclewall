<?php
/*
	squid.inc

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.

	Copyright (C) 2006-2009 Scott Ullrich
	Copyright (C) 2006 Fernando Lemos
	Copyright (C) 2008 Martin Fuchs
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

require_once('squidguard.inc');

define('SQUID_LOCALBASE','/usr/local');
define('SQUID_CONFBASE',SQUID_LOCALBASE . '/etc/squid');
define('SQUID_BASE', '/var/squid/');
define('SQUID_ACLDIR', '/var/squid/acl');
define('SQUID_PASSWD', '/var/etc/squid.passwd');

$valid_acls = array();

function squid_get_real_interface_address($iface)
{
	global $config;

	$iface = convert_friendly_interface_to_real_interface_name($iface);
	$line = trim(shell_exec("ifconfig $iface | grep inet | grep -v inet6"));
	list($dummy, $ip, $dummy2, $netmask) = explode(" ", $line);

	return array($ip, long2ip(hexdec($netmask)));
}

function squid_chown_recursive($dir, $user, $group)
{
	chown($dir, $user);
	chgrp($dir, $group);
	$handle = opendir($dir) ;
	while (($item = readdir($handle)) !== false) {
		if (($item != ".") && ($item != "..")) {
			$path = "$dir/$item";
			if (is_dir($path) && (basename($dir) != "cache"))
				squid_chown_recursive($path, $user, $group);
			elseif (is_file($path)) {
				chown($path, $user);
				chgrp($path, $group);
			}
		}
	}
}

function squid_dash_z()
{
	global $config;
	$settings = $config['installedpackages']['squidcache']['config'][0];

	$cachedir =($settings['harddisk_cache_location'] ? $settings['harddisk_cache_location'] : '/var/squid/cache');

	if(!is_dir($cachedir.'/')) {
		make_dirs($cachedir);
		mwexec("/usr/sbin/chown -R proxy:proxy $cachedir");
	}

	if(!is_dir($cachedir.'/00/')) {
		while (is_process_running('squid')) {
			mwexec(SQUID_LOCALBASE . "/sbin/squid -k kill");
			sleep(1);
		}

		mwexec("/usr/sbin/chown -R proxy:proxy $cachedir");
		mwexec(SQUID_LOCALBASE . "/sbin/squid -z");
	}

	if(file_exists("/var/squid/cache/swap.state")) {
		chown("/var/squid/cache/swap.state", "proxy");
		chgrp("/var/squid/cache/swap.state", "proxy");
		exec("chmod a+rw /var/squid/cache/swap.state");
	}

}

function squid_is_valid_acl($acl)
{
	global $valid_acls;
	if(!is_array($valid_acls))
		return;
	return in_array($acl, $valid_acls);
}

function squid_before_form_general($pkg)
{
	$values = get_dir(SQUID_CONFBASE . '/errors/');
	array_shift($values);
	array_shift($values);
	$name = array();
	foreach ($values as $value)
		$names[] = implode(" ", explode("_", $value));

	$i = 0;
	foreach ($pkg['fields']['field'] as $field) {
		if ($field['fieldname'] == 'error_language')
			break;
		$i++;
	}
	$field = &$pkg['fields']['field'][$i];

	for ($i = 0; $i < count($values); $i++)
		$field['options']['option'][] = array('name' => $names[$i], 'value' => $values[$i]);
}

function squid_validate_general($post, $input_errors)
{
	global $config;
	$settings = $config['installedpackages']['squid']['config'][0];
	$port = ($settings['proxy_port'] ? $settings['proxy_port'] : 3128);
	$port = $post['proxy_port'] ? $post['proxy_port'] : $port;

	if (substr($post['log_dir'], -1, 1) == '/')
		$input_errors[] = "You may not end log location with an / mark";

	if ($post['log_dir']{0} != '/')
		$input_errors[] = "You must start log location with a / mark.";

	if (strlen($post['log_dir']) <= 3)
		$input_errors[] = "That is not a valid log location dir.";

	if (!$post['squid_enabled'] and $post['squidguard_enabled'])
		$input_errors[] = "You have to enable Proxy Server to use Proxy Web Filter.";

	$log_rotate = trim($post['log_rotate']);

	if (!empty($log_rotate) && (!is_numeric($log_rotate) or ($log_rotate < 1)))
		$input_errors[] = "You must enter a valid number of days in the 'Log rotate' field";

	foreach (array('defined_ip_proxy_off') as $hosts) {
		foreach (explode(";", $post[$hosts]) as $host) {
			$host = trim($host);
			if (!empty($host) && !is_ipaddr($host) && !is_alias($host) && !is_hostname($host) && !is_subnet($host))
				$input_errors[] = "The entry '$host' is not a valid IP address, hostname, or alias";
		}
	}
	foreach (array('defined_ip_proxy_off_dest') as $hosts) {
		foreach (explode(";", $post[$hosts]) as $host) {
			$host = trim($host);
			if (!empty($host) && !is_ipaddr($host) && !is_alias($host) && !is_hostname($host) && !is_subnet($host))
				$input_errors[] = "The entry '$host' is not a valid IP address, hostname, or alias";
		}
	}
}

function squid_validate_cache($post, $input_errors)
{
	$num_fields = array('harddisk_cache_size' => "Harddisk Cache Size",
				'memory_cache_size' => "Memory Cache Size",
				'maximum_object_size' => "Maximum Object Size",
				'maximum_objsize_in_mem' => "Maximum Object Size in RAM"
	);

	foreach ($num_fields as $field => $name)
	{
		$value = trim($post[$field]);
		if (!is_numeric($value) || ($value < 0))
			$input_errors[] = "You must enter a valid value for '$field'";
	}

	$value = trim($post['minimum_object_size']);
	if (!is_numeric($value) || ($value < 0))
		$input_errors[] = "You must enter a valid value for 'Minimum Object Size'";

	if (substr($post['harddisk_cache_location'], -1, 1) == '/')
		$input_errors[] = "You may not end log location with an / mark";

	if ($post['harddisk_cache_location']{0} != '/')
		$input_errors[] = "You must start log location with a / mark.";
	if (strlen($post['harddisk_cache_location']) <= 3)
		$input_errors[] = "Harddisk Cache Location is not valid.";

	squid_dash_z();
}

function squid_install_cron($should_install)
{
	global $config, $g;
	if($g['booting']==true)
		return;
	$rotate_is_installed = false;
	$swapstate_is_installed = false;
	if(!$config['cron']['item'])
		return;
	$settings = $config['installedpackages']['squidcache']['config'][0];

	$x=0;
	$rotate_job_id=-1;
	$swapstate_job_id=-1;

	foreach($config['cron']['item'] as $item) {
		if(strstr($item['task_name'], "squid_rotate_logs")) {
			$rotate_job_id = $x;
		} elseif(strstr($item['task_name'], "squid_check_swapstate")) {
			$swapstate_job_id = $x;
		}
		$x++;
	}
	$need_write = false;
	switch($should_install)
	{
		case true:
			$cachedir =($settings['harddisk_cache_location'] ? $settings['harddisk_cache_location'] : '/var/squid/cache');
			if($rotate_job_id < 0) {
				$cron_item = array();
				$cron_item['task_name'] = "squid_rotate_logs";
				$cron_item['minute'] = "0";
				$cron_item['hour'] = "0";
				$cron_item['mday'] = "*";
				$cron_item['month'] = "*";
				$cron_item['wday'] = "*";
				$cron_item['who'] = "root";
				$cron_item['command'] = "/bin/rm {$cachedir}/swap.state; " . SQUID_LOCALBASE . "/sbin/squid -k rotate";
				$config['cron']['item'][] = $cron_item;
				$need_write = true;
			}
			if($swapstate_job_id < 0)
			{
				$cron_item = array();
				$cron_item['task_name'] = "squid_check_swapstate";
				$cron_item['minute'] = "*/15";
				$cron_item['hour'] = "*";
				$cron_item['mday'] = "*";
				$cron_item['month'] = "*";
				$cron_item['wday'] = "*";
				$cron_item['who'] = "root";
				$cron_item['command'] = "/usr/local/pkg/swapstate_check.php";
				$config['cron']['item'][] = $cron_item;
				$need_write = true;
			}
			if ($need_write) {
				parse_config(true);
				write_config("Squid cron jobs added");
			}
		break;
		case false:
			if($rotate_job_id >= 0) {
				unset($config['cron']['item'][$rotate_job_id]);
				$need_write = true;
			}
			if($swapstate_job_id >= 0) {
				unset($config['cron']['item'][$swapstate_job_id]);
				$need_write = true;
			}
			if ($need_write) {
				parse_config(true);
				write_config("Squid cron jobs removed");
			}
		break;
	}
	configure_cron();
}

function squid_resync_general()
{
	global $g, $config, $valid_acls;

	$settings = $config['installedpackages']['squid']['config'][0];
	$conf = "# This file is generated by NUCLEWALL\n";

	$port = ($settings['proxy_port'] ? $settings['proxy_port'] : 3128);
	$ifaces = ($settings['active_interface'] ? $settings['active_interface'] : 'lan');
	$real_ifaces = array();

	foreach (explode(",", $ifaces) as $i => $iface)
	{
		$real_ifaces[] = squid_get_real_interface_address($iface);
		if($real_ifaces[$i][0]) {
			$conf .= "http_port {$real_ifaces[$i][0]}:$port\n";
		}
	}

	$conf .= "http_port 127.0.0.1:" . $settings['proxy_port'] . " transparent\n";

	$icp_port = ($settings['icp_port'] ? $settings['icp_port'] : 0);

	$pidfile = "{$g['varrun_path']}/squid.pid";
	$language = ($settings['error_language'] ? $settings['error_language'] : 'English');
	$errordir = SQUID_CONFBASE . '/errors/' . $language;
	$icondir = SQUID_CONFBASE . '/icons';
	$hostname = ($settings['visible_hostname'] ? $settings['visible_hostname'] : 'nuclewall');
	$email = ($settings['admin_email'] ? $settings['admin_email'] : 'admin@nuclewall');

	$logdir = ($settings['log_dir'] ? $settings['log_dir'] : '/var/squid/logs');

	$logdir_cache = $logdir . '/cache.log';
	$logdir_access = ($settings['log_enabled'] == 'on' ? $logdir . '/access.log' : '/dev/null');

	$conf .= <<<EOD
icp_port $icp_port

pid_filename $pidfile
cache_effective_user proxy
cache_effective_group proxy
error_directory $errordir
icon_directory $icondir
visible_hostname $hostname
cache_mgr $email
access_log $logdir_access
cache_log $logdir_cache
cache_store_log none

EOD;

	$rotate = empty($settings['log_rotate']) ? 0 : $settings['log_rotate'];
	$conf .= "logfile_rotate {$rotate}\n";
	squid_install_cron(true);

	$conf .= <<<EOD
shutdown_lifetime 3 seconds

EOD;

	$src = '';

	foreach ($real_ifaces as $iface)
	{
		list($ip, $mask) = $iface;
		$ip = long2ip(ip2long($ip) & ip2long($mask));
		$src .= " $ip/$mask";
	}

	$conf .= "# Allow local network(s) on interface(s)\n";
	$conf .= "acl localnet src $src\n";
	$valid_acls[] = 'localnet';

	if ($settings['disable_xforward']) $conf .= "forwarded_for off\n";
	if ($settings['disable_via']) $conf .= "via off\n";
	if ($settings['disable_squidversion']) $conf .= "httpd_suppress_version_string on\n";
    $conf .= "uri_whitespace strip\n";

	if(!empty($settings['dns_nameservers'])) {
	  $altdns = explode(";", ($settings['dns_nameservers']));
    $conf .= "dns_nameservers ";
	 foreach ($altdns as $dnssrv) {
    $conf .= $dnssrv." ";
	 }
   }
  return $conf;
}


function squid_resync_cache()
{
	global $config, $g;

	$settings = $config['installedpackages']['squidcache']['config'][0];

	$cachedir =($settings['harddisk_cache_location'] ? $settings['harddisk_cache_location'] : '/var/squid/cache');
	$disk_cache_size = ($settings['harddisk_cache_size'] ? $settings['harddisk_cache_size'] : 4096);
	$level1 = ($settings['level1_subdirs'] ? $settings['level1_subdirs'] : 16);
	$memory_cache_size = ($settings['memory_cache_size'] ? $settings['memory_cache_size'] : 256);
	$max_objsize = ($settings['maximum_object_size'] ? $settings['maximum_object_size'] : 10);
	$min_objsize = ($settings['minimum_object_size'] ? $settings['minimum_object_size'] : 0);
	$max_objsize_in_mem = ($settings['maximum_objsize_in_mem'] ? $settings['maximum_objsize_in_mem'] : 2048);

	$cache_policy = 'heap LFUDA';
	$memory_policy = 'heap GDSF';
	$offline_mode = 'off';
	$disk_cache_system = 'ufs';

	$disk_cache_opts = "{$disk_cache_system} {$cachedir} {$disk_cache_size} {$level1} 256";

	$conf = <<<EOD
cache_mem $memory_cache_size MB
maximum_object_size_in_memory $max_objsize_in_mem KB
memory_replacement_policy $memory_policy
cache_replacement_policy $cache_policy
cache_dir $disk_cache_opts
minimum_object_size $min_objsize KB
maximum_object_size $max_objsize KB
offline_mode $offline_mode

EOD;

	$conf .= "cache_swap_low 90\n";
	$conf .= "cache_swap_high 95\n";

	return $conf;
}

function squid_resync_nac()
{
	global $config, $valid_acls;

  $port = ($settings['proxy_port'] ? $settings['proxy_port'] : 3128);
  $settings = $config['installedpackages']['squidnac']['config'][0];
	$webgui_port = $config['system']['webgui']['port'];
	$addtl_ports = $settings['addtl_ports'];
	$addtl_sslports = $settings['addtl_sslports'];

	$conf = <<<EOD

# Setup some default acls
acl all src 0.0.0.0/0.0.0.0
acl localhost src 127.0.0.1/255.255.255.255
acl safeports port 21 70 80 210 280 443 488 563 591 631 777 901 $webgui_port $port 1025-65535 $addtl_ports
acl sslports port 443 563 $webgui_port $addtl_sslports
acl manager proto cache_object
acl purge method PURGE
acl connect method CONNECT
acl dynamic urlpath_regex cgi-bin \?

EOD;

	$allowed_subnets = explode("\n", base64_decode($settings['allowed_subnets']));
	$allowed = "";
	foreach ($allowed_subnets as $subnet) {
		if(!empty($subnet)) {
			$subnet = trim($subnet);
			$allowed .= "$subnet ";
		}
	}
	if (!empty($allowed)) {
		$conf .= "acl allowed_subnets src $allowed\n";
		$valid_acls[] = 'allowed_subnets';
	}

	$options = array(	'unrestricted_hosts' => 'src',
				'banned_hosts' => 'src',
				'whitelist' => 'dstdom_regex -i',
				'blacklist' => 'dstdom_regex -i',
	);
	foreach ($options as $option => $directive) {
		$contents = base64_decode($settings[$option]);
		if (!empty($contents)) {
			file_put_contents(SQUID_ACLDIR . "/$option.acl", $contents);
			$conf .= "acl $option $directive \"" . SQUID_ACLDIR . "/$option.acl\"\n";
			$valid_acls[] = $option;
		}
		elseif (file_exists(SQUID_ACLDIR . "/$option.acl")) {
      unlink(SQUID_ACLDIR . "/$option.acl");
    }
	}

	$conf .= <<<EOD
cache deny dynamic
http_access allow manager localhost

EOD;

	if(!empty($settings['ext_cachemanager'])) {
	  $extmgr = explode(";", ($settings['ext_cachemanager']));
    $count = 1;
    $conf .= "\n# Allow external cache managers\n";
	 foreach ($extmgr as $mgr) {
    $conf .= "acl ext_manager_".$count." src ";
    $conf .= $mgr." ";
    $conf .= "\n";
    $conf .= "http_access allow manager ext_manager_".$count."\n";
    $count += 1;
	 }}

  $conf .= <<<EOD

http_access deny manager
http_access allow purge localhost
http_access deny purge
http_access deny !safeports
http_access deny CONNECT !sslports

# Always allow localhost connections
http_access allow localhost

EOD;

	return $conf;
}

function squid_resync_auth()
{
	global $config, $valid_acls;
	$settingsconfig = $config['installedpackages']['squid']['config'][0];
	$conf = '';

	if (!empty($config['installedpackages']['squid']['config'][0]['custom_options'])) {
		$custopts = explode(";", ($config['installedpackages']['squid']['config'][0]['custom_options']));
		$conf .= "# Custom options\n";

		foreach ($custopts as $custopt) {
			$conf .= $custopt."\n";
		}
	}

	$conf .="# Setup allowed acls\n";
	$allowed = array('allowed_subnets');

	$conf .= "# Allow local network(s) on interface(s)\n";
	$allowed[] = "localnet";

	$allowed = array_filter($allowed, 'squid_is_valid_acl');

	foreach ($allowed as $acl)
		$conf .= "http_access allow $acl\n";

	$conf .= "# Default block all to be sure\n";
	$conf .= "http_access deny all\n";

	return $conf;
}

function squid_resync()
{
	global $config;
	$conf = squid_resync_general() . "\n";
	$conf .= squid_resync_cache() . "\n";
	$conf .= squid_resync_nac() . "\n";
	$conf .= squid_resync_auth();

	foreach (array(SQUID_CONFBASE, SQUID_ACLDIR, SQUID_BASE) as $dir) {
		make_dirs($dir);
		squid_chown_recursive($dir, 'proxy', 'proxy');
	}

	file_put_contents(SQUID_CONFBASE . '/squid.conf', $conf);

	$log_dir = $config['installedpackages']['squid']['config'][0]['log_dir'].'/';

	if(!is_dir($log_dir)) {
		make_dirs($log_dir);
		squid_chown_recursive($log_dir, 'proxy', 'proxy');
	}

	squid_dash_z();

	mwexec(SQUID_LOCALBASE . "/sbin/squid -D");
	sleep(1);


	log_error("Synchronizing settings.");
	mwexec(SQUID_LOCALBASE . "/sbin/squid -k reconfigure");

	filter_configure();
}

function squid_generate_rules($type)
{
	global $config;

	$squid_conf = $config['installedpackages']['squid']['config'][0];

	if (!is_process_running('squid')) {
		return;
	}

	$ifaces = explode(",", $squid_conf['active_interface']);
	$ifaces = array_map('convert_friendly_interface_to_real_interface_name', $ifaces);
	$port = ($squid_conf['proxy_port'] ? $squid_conf['proxy_port'] : 3128);

	$fw_aliases = filter_generate_aliases();
	if(strstr($fw_aliases, "pptp ="))
		$PPTP_ALIAS = "\$pptp";
	else
		$PPTP_ALIAS = "\$PPTP";
	if(strstr($fw_aliases, "PPPoE ="))
		$PPPOE_ALIAS = "\$PPPoE";
	else
		$PPPOE_ALIAS = "\$pppoe";

	switch($type) {
		case 'nat':
			$rules .= "\n# Setup Squid proxy redirect\n";
			if ($squid_conf['private_subnet_proxy_off'] == 'on') {
				foreach ($ifaces as $iface) {
					$rules .= "no rdr on $iface proto tcp from any to { 192.168.0.0/16, 172.16.0.0/12, 10.0.0.0/8 } port 80\n";
				}
			}
			if (!empty($squid_conf['defined_ip_proxy_off'])) {
				$defined_ip_proxy_off = explode(";", $squid_conf['defined_ip_proxy_off']);
				$exempt_ip = "";
				foreach ($defined_ip_proxy_off as $ip_proxy_off) {
					if(!empty($ip_proxy_off)) {
						$ip_proxy_off = trim($ip_proxy_off);
						if (is_alias($ip_proxy_off))
							$ip_proxy_off = '$'.$ip_proxy_off;
						$exempt_ip .= ", $ip_proxy_off";
					}
				}
				$exempt_ip = substr($exempt_ip,2);
				foreach ($ifaces as $iface) {
					$rules .= "no rdr on $iface proto tcp from { $exempt_ip } to any port 80\n";
				}
			}
			if (!empty($squid_conf['defined_ip_proxy_off_dest'])) {
				$defined_ip_proxy_off_dest = explode(";", $squid_conf['defined_ip_proxy_off_dest']);
				$exempt_dest = "";
				foreach ($defined_ip_proxy_off_dest as $ip_proxy_off_dest) {
					if(!empty($ip_proxy_off_dest)) {
						$ip_proxy_off_dest = trim($ip_proxy_off_dest);
						if (is_alias($ip_proxy_off_dest))
							$ip_proxy_off_dest = '$'.$ip_proxy_off_dest;
						$exempt_dest .= ", $ip_proxy_off_dest";
					}
				}
				$exempt_dest = substr($exempt_dest,2);
				foreach ($ifaces as $iface) {
					$rules .= "no rdr on $iface proto tcp from any to { $exempt_dest } port 80\n";
				}
			}
			foreach ($ifaces as $iface)
			{
				$rules .= "rdr on $iface proto tcp from any to !($iface) port 80 -> 127.0.0.1 port " . $squid_conf['proxy_port'] . "\n";
			}
			$rules .= "\n";
			break;
		case 'filter':
		case 'rule':
			foreach ($ifaces as $iface) {
				$rules .= "# Setup squid pass rules for proxy\n";
				$rules .= "pass in quick on $iface proto tcp from any to !($iface) port 80 flags S/SA keep state\n";
				$rules .= "pass in quick on $iface proto tcp from any to !($iface) port $port flags S/SA keep state\n";
				$rules .= "\n";
			};
			if($config['pppoe']['mode'] == "server" && $config['pppoe']['localip']) {
				$rules .= "pass in quick on $PPPOE_ALIAS proto tcp from any to !127.0.0.1 port $port flags S/SA keep state\n";
			}
			if($config['pptpd']['mode'] == "server" && $config['pptpd']['localip']) {
				$rules .= "pass in quick on $PPTP_ALIAS proto tcp from any to !127.0.0.1 port $port flags S/SA keep state\n";
			}
			break;
		default:
			break;
	}

	return $rules;
}

?>
