<?php
/*
	system.inc
	part of m0n0wall (http://m0n0.ch/wall)

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


function get_default_sysctl_value($id)
{
	global $sysctls;

	if (isset($sysctls[$id]))
		return $sysctls[$id];
}

function activate_sysctls()
{
	global $config, $g;
	exec("/sbin/sysctl net.enc.out.ipsec_bpf_mask=0x00000001");
	exec("/sbin/sysctl net.enc.out.ipsec_filter_mask=0x00000001");
	exec("/sbin/sysctl net.enc.in.ipsec_bpf_mask=0x00000002");
	exec("/sbin/sysctl net.enc.in.ipsec_filter_mask=0x00000002");

	if(is_array($config['sysctl'])) {
		foreach($config['sysctl']['item'] as $tunable) {
			if($tunable['value'] == "default") {
				$value = get_default_sysctl_value($tunable['tunable']);
				mwexec("/sbin/sysctl " . $tunable['tunable'] . "=\"" . $value .  "\"");
			} else {
				mwexec("/sbin/sysctl " . $tunable['tunable'] . "=\"" . $tunable['value'] .  "\"");
			}
		}
	}
}

function system_resolvconf_generate($dynupdate = false)
{
	global $config, $g;

	$syscfg = $config['system'];

	// Do not create blank domain lines, it breaks tools like dig.
	if($syscfg['domain'])
		$resolvconf = "domain {$syscfg['domain']}\n";

	if (isset($config['dnsmasq']['enable']) && !isset($config['system']['dnslocalhost']))
		$resolvconf .= "nameserver 127.0.0.1\n";

	if (isset($syscfg['dnsallowoverride'])) {
		/* get dynamically assigned DNS servers (if any) */
		$ns = array_unique(get_searchdomains());
		foreach($ns as $searchserver) {
			if($searchserver)
				$resolvconf .= "search {$searchserver}\n";
		}
		$ns = array_unique(get_nameservers());
		foreach($ns as $nameserver) {
			if($nameserver)
				$resolvconf .= "nameserver $nameserver\n";
		}
	}
	if (is_array($syscfg['dnsserver'])) {
		foreach ($syscfg['dnsserver'] as $ns) {
			if ($ns)
				$resolvconf .= "nameserver $ns\n";
		}
	}

	$dnslock = lock('resolvconf', LOCK_EX);

	$fd = fopen("{$g['varetc_path']}/resolv.conf", "w");
	if (!$fd) {
		printf("Error: cannot open resolv.conf in system_resolvconf_generate().\n");
		unlock($dnslock);
		return 1;
	}

	fwrite($fd, $resolvconf);
	fclose($fd);

	if (!$g['booting']) {
		/* restart dhcpd (nameservers may have changed) */
		if (!$dynupdate)
			services_dhcpd_configure();
	}

	/* setup static routes for DNS servers. */
	for ($dnscounter=1; $dnscounter<5; $dnscounter++) {
		/* setup static routes for dns servers */
		$dnsgw = "dns{$dnscounter}gwint";
		if (isset($config['system'][$dnsgw])) {
			$interface = $config['system'][$dnsgw];
			if (($interface <> "") && ($interface <> "none")) {
				$gatewayip = get_interface_gateway($interface);
				if (is_ipaddr($gatewayip)) {
					/* dns server array starts at 0 */
					$dnscountermo = $dnscounter - 1;
					mwexec("route change -host " . $syscfg['dnsserver'][$dnscountermo] . " {$gatewayip}");
				}
			}
		}
	}

	unlock($dnslock);

	return 0;
}

function get_searchdomains()
{
	global $config, $g;

	$master_list = array();

	$search_list = glob("/var/etc/searchdomain_*");
	if (is_array($search_list)) {
		foreach($search_list as $fdns)
		{
			$contents = file($fdns, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			if (!is_array($contents))
				continue;
			foreach ($contents as $dns)
			{
				if(is_hostname($dns))
					$master_list[] = $dns;
			}
		}
	}

	return $master_list;
}

function get_nameservers()
{
	global $config, $g;
	$master_list = array();

	$dns_lists = glob("/var/etc/nameserver_*");
	if (is_array($dns_lists))
	{
		foreach($dns_lists as $fdns)
		{
			$contents = file($fdns, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
			if (!is_array($contents))
				continue;
			foreach ($contents as $dns)
			{
				if(is_ipaddr($dns))
					$master_list[] = $dns;
			}
		}
	}

	if(file_exists("/var/etc/nameservers.conf"))
	{
		$dns_s = file("/var/etc/nameservers.conf", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if(is_array($dns_s)) {
			foreach($dns_s as $dns)
				if (is_ipaddr($dns))
					$master_list[] = $dns;
		}
	}

	return $master_list;
}

function system_hosts_generate()
{
	global $config, $g;

	$syscfg = $config['system'];
	$dnsmasqcfg = $config['dnsmasq'];

	if (!is_array($dnsmasqcfg['hosts']))
	{
		$dnsmasqcfg['hosts'] = array();
	}

	$hostscfg = $dnsmasqcfg['hosts'];

	$hosts = "127.0.0.1	localhost localhost.{$syscfg['domain']}\n";
	$lhosts = "";
	$dhosts = "";

	if ($config['interfaces']['lan'])
	{
		$cfgip = get_interface_ip("lan");
		if (is_ipaddr($cfgip))
			$hosts .= "{$cfgip}	{$syscfg['hostname']}.{$syscfg['domain']} {$syscfg['hostname']}\n";
	}
	else
	{
		$sysiflist = get_configured_interface_list();
		foreach ($sysiflist as $sysif) {
			if (!interface_has_gateway($sysif))
			{
				$cfgip = get_interface_ip($sysif);
				if (is_ipaddr($cfgip)) {
					$hosts .= "{$cfgip}	{$syscfg['hostname']}.{$syscfg['domain']} {$syscfg['hostname']}\n";
					break;
				}
			}
		}
	}

	foreach ($hostscfg as $host)
	{
		if ($host['host'])
			$lhosts .= "{$host['ip']}	{$host['host']}.{$host['domain']} {$host['host']}\n";
		else
			$lhosts .= "{$host['ip']}	{$host['domain']}\n";
	}

	if (isset($dnsmasqcfg['regdhcpstatic']) && is_array($config['dhcpd']))
	{
		foreach ($config['dhcpd'] as $dhcpif => $dhcpifconf)
			if(is_array($dhcpifconf['staticmap']) && isset($dhcpifconf['enable']))
					foreach ($dhcpifconf['staticmap'] as $host)
						if ($host['ipaddr'] && $host['hostname'])
							$dhosts .= "{$host['ipaddr']}	{$host['hostname']}.{$syscfg['domain']} {$host['hostname']}\n";
	}

	if (isset($dnsmasqcfg['dhcpfirst']))
		$hosts .= $dhosts . $lhosts;
	else
		$hosts .= $lhosts . $dhosts;

	if (file_exists("{$g['varrun_path']}/dhcpleases.pid")) {
		sigkillbypid("{$g['varrun_path']}/dhcpleases.pid", "TERM");
                @unlink("{$g['varrun_path']}/dhcpleases.pid");
	}
	$fd = fopen("{$g['varetc_path']}/hosts", "w");
	if (!$fd) {
		log_error("Error: cannot open hosts file in system_hosts_generate().\n");
		return 1;
	}
	fwrite($fd, $hosts);
	fclose($fd);

	system_dhcpleases_configure();

	return 0;
}

function system_dhcpleases_configure()
{
	global $config, $g;

	if (isset($config['dnsmasq']['regdhcp']))
	{
		@touch("{$g['dhcpd_chroot_path']}/var/db/dhcpd.leases");
		if (file_exists("{$g['varrun_path']}/dhcpleases.pid"))
				sigkillbypid("{$g['varrun_path']}/dhcpleases.pid", "HUP");
		else
			mwexec("/usr/local/sbin/dhcpleases -l {$g['dhcpd_chroot_path']}/var/db/dhcpd.leases -d {$config['system']['domain']} -p {$g['varrun_path']}/dnsmasq.pid -h {$g['varetc_path']}/hosts");
	}
	else
	{
		sigkillbypid("{$g['varrun_path']}/dhcpleases.pid", "TERM");
		@unlink("{$g['varrun_path']}/dhcpleases.pid");
	}
}

function system_hostname_configure()
{
	global $config, $g;

	$syscfg = $config['system'];

	$status = mwexec("/bin/hostname " .
		escapeshellarg("{$syscfg['hostname']}.{$syscfg['domain']}"));

	mwexec("/etc/rc.d/hostid start");

	return $status;
}

function system_routing_configure($interface = "")
{
	global $config, $g;

	$gatewayip = "";
	$interfacegw = "";
	$foundgw = false;

	if (is_array($config['gateways']['gateway_item']))
	{
		mwexec("/bin/rm {$g['tmp_path']}/*_defaultgw", true);
		foreach	($config['gateways']['gateway_item'] as $gateway)
		{
			if (isset($gateway['defaultgw']))
			{
				if(strstr($gateway['gateway'], ":"))
					break;
				if ($gateway['gateway'] == "dynamic")
					$gateway['gateway'] = get_interface_gateway($gateway['interface']);
				$gatewayip = $gateway['gateway'];
				$interfacegw = $gateway['interface'];
				if (!empty($interfacegw)) {
					$defaultif = get_real_interface($gateway['interface']);
					if ($defaultif)
						@file_put_contents("{$g['tmp_path']}/{$defaultif}_defaultgw", $gatewayip);
				}
				$foundgw = true;
				break;
			}
		}
	}

	if ($foundgw == false)
	{
		$defaultif = get_real_interface("wan");
		$interfacegw = "wan";
		$gatewayip = get_interface_gateway("wan");
		@touch("{$g['tmp_path']}/{$defaultif}_defaultgw");
	}

	$dont_add_route = false;

	if ($dont_add_route == false ) {
		if (!empty($interface) && $interface != $interfacegw)
			;
		else if (($interfacegw <> "bgpd") && (is_ipaddr($gatewayip))) {
			log_error("ROUTING: setting default route to $gatewayip");
			mwexec("/sbin/route change -inet default " . escapeshellarg($gatewayip));
		}
	}

	$static_routes = get_staticroutes();
	if (count($static_routes)) {
		$gateways_arr = return_gateways_array();

		foreach ($static_routes as $rtent) {
			$gatewayip = "";
			if (empty($gateways_arr[$rtent['gateway']])) {
				log_error("Static Routes: Gateway IP could not be found for {$rtent['network']}");
				continue;
			}
			$gateway = $gateways_arr[$rtent['gateway']];
			if (!empty($interface) && $interface != $gateway['friendlyiface'])
				continue;
			$gatewayip = $gateway['gateway'];
			$interfacegw = $gateway['interface'];

			if (is_ipaddr($gatewayip)) {
				mwexec("/sbin/route change -inet " . escapeshellarg($rtent['network']) .
					" " . escapeshellarg($gatewayip));
			} else if (!empty($interfacegw)) {
				mwexec("/sbin/route change -inet " . escapeshellarg($rtent['network']) .
					" -iface " . escapeshellarg($interfacegw));
			}
		}
	}

	return 0;
}

function system_routing_enable()
{
	global $config, $g;

	return mwexec("/sbin/sysctl net.inet.ip.forwarding=1");
}

function system_syslogd_get_remote_servers($syslogcfg, $facility = "*.*")
{
	$facility .= " ".
	$remote_servers = "";
	$pad_to  = 56;
	$padding = ceil(($pad_to - strlen($facility))/8)+1;
	if($syslogcfg['remoteserver'])
		$remote_servers .= "{$facility}" . str_repeat("\t", $padding) . "@{$syslogcfg['remoteserver']}\n";
	if($syslogcfg['remoteserver2'])
		$remote_servers .= "{$facility}" . str_repeat("\t", $padding) . "@{$syslogcfg['remoteserver2']}\n";
	if($syslogcfg['remoteserver3'])
		$remote_servers .= "{$facility}" . str_repeat("\t", $padding) . "@{$syslogcfg['remoteserver3']}\n";
	return $remote_servers;
}

function system_syslogd_start()
{
	global $config, $g;

	mwexec("/etc/rc.d/hostid start");

	$syslogcfg = $config['syslog'];

	if ($g['booting'])
		echo "Starting Syslog...";
	else
		killbypid("{$g['varrun_path']}/syslog.pid");

	if(is_process_running("syslogd"))
		mwexec('/bin/pkill syslogd');
	if(is_process_running("fifolog_writer"))
		mwexec('/bin/pkill fifolog_writer');

	$fifolog_create = "/usr/sbin/fifolog_create -s ";
	$fifolog_log = "|/usr/sbin/fifolog_writer ";
	$clog_create = "/usr/sbin/clog -i -s ";
	$clog_log = "%";

	if(isset($config['system']['usefifolog'])) {
		$log_directive = $fifolog_log;
		$log_create_directive = $fifolog_create;
	} else { // Defaults to CLOG
		$log_directive = $clog_log;
		$log_create_directive = $clog_create;
	}

	if (isset($syslogcfg)) {
		$separatelogfacilities = array('relayd');
		if($config['installedpackages']['package']) {
			foreach($config['installedpackages']['package'] as $package) {
				if($package['logging']) {
					array_push($separatelogfacilities, $package['logging']['facilityname']);
					mwexec("{$log_create_directive} 10240 {$g['varlog_path']}/{$package['logging']['logfilename']}");
					$syslogconf .= "!{$package['logging']['facilityname']}\n*.*\t\t\t\t\t\t {$log_directive}{$g['varlog_path']}/{$package['logging']['logfilename']}\n";
				}
			}
		}
		$facilitylist = implode(',', array_unique($separatelogfacilities));
		/* write syslog.conf */
		$fd = fopen("{$g['varetc_path']}/syslog.conf", "w");

		if (!$fd) {
			printf("Error: cannot open syslog.conf in system_syslogd_start().\n");
			return 1;
		}

		$syslogconf = "";
		$syslogconf .= "!relayd\n";
		if (!isset($syslogcfg['disablelocallogging']))
			$syslogconf .= "*.* 								{$log_directive}{$g['varlog_path']}/relayd.log\n";
		if (isset($syslogcfg['relayd']))
			$syslogconf .= system_syslogd_get_remote_servers($syslogcfg, "*.*");

		$syslogconf .= "!-{$facilitylist}\n";
		if (!isset($syslogcfg['disablelocallogging']))
			$syslogconf .= <<<EOD
local0.*							{$log_directive}{$g['varlog_path']}/filter.log
local4.*							{$log_directive}{$g['varlog_path']}/portalauth.log
local7.*							{$log_directive}{$g['varlog_path']}/dhcpd.log
*.notice;kern.debug;lpr.info;mail.crit;				{$log_directive}{$g['varlog_path']}/system.log
news.err;local0.none;local3.none;local4.none;			{$log_directive}{$g['varlog_path']}/system.log
local7.none							{$log_directive}{$g['varlog_path']}/system.log
security.*							{$log_directive}{$g['varlog_path']}/system.log
auth.info;authpriv.info;daemon.info				{$log_directive}{$g['varlog_path']}/system.log
auth.info;authpriv.info 					|exec /usr/local/sbin/sshlockout_pf 15
*.emerg								*

EOD;
		if (isset($syslogcfg['filter']))
			$syslogconf .= system_syslogd_get_remote_servers($syslogcfg, "local0.*");
		if (isset($syslogcfg['portalauth']))
			$syslogconf .= system_syslogd_get_remote_servers($syslogcfg, "local4.*");
		if (isset($syslogcfg['dhcp']))
			$syslogconf .= system_syslogd_get_remote_servers($syslogcfg, "local7.*");
		if (isset($syslogcfg['system'])) {
			$syslogconf .= system_syslogd_get_remote_servers($syslogcfg, "*.notice;kern.debug;lpr.info;mail.crit;");
			$syslogconf .= system_syslogd_get_remote_servers($syslogcfg, "news.err;local0.none;local3.none;local7.none");
			$syslogconf .= system_syslogd_get_remote_servers($syslogcfg, "security.*");
			$syslogconf .= system_syslogd_get_remote_servers($syslogcfg, "auth.info;authpriv.info;daemon.info");
			$syslogconf .= system_syslogd_get_remote_servers($syslogcfg, "*.emerg");
		}
		if (isset($syslogcfg['logall'])) {
			// Make everything mean everything, including facilities excluded above.
			$syslogconf .= "!*\n";
			$syslogconf .= system_syslogd_get_remote_servers($syslogcfg, "*.*");
		}

		if (isset($syslogcfg['zmqserver'])) {
				$syslogconf .= <<<EOD
*.*								^{$syslogcfg['zmqserver']}

EOD;
		}
		fwrite($fd, $syslogconf);
		fclose($fd);

		// Ensure that the log directory exists
		if(!is_dir("{$g['dhcpd_chroot_path']}/var/run"))
			exec("/bin/mkdir -p {$g['dhcpd_chroot_path']}/var/run");

		// Are we logging to a least one remote server ?
		if(strpos($syslogconf, "@") != false)
			$retval = mwexec_bg("/usr/sbin/syslogd -s -c -c -l /var/dhcpd/var/run/log -f {$g['varetc_path']}/syslog.conf");
		else {
			$retval = mwexec_bg("/usr/sbin/syslogd -s -c -c -l /var/dhcpd/var/run/log -f {$g['varetc_path']}/syslog.conf");
		}

	} else {
		$retval = mwexec_bg("/usr/sbin/syslogd -c -c -l /var/dhcpd/var/run/log");
	}

	if ($g['booting'])
		echo "Done.\n";

	return $retval;
}

function system_webgui_start()
{
	global $config, $g;

	if ($g['booting'])
		echo "Starting Web UI...";

	killbypid("{$g['varrun_path']}/lighty-webConfigurator.pid");

	sleep(1);

	chdir($g['www_path']);

	$portarg = "80";
	$crt = "";
	$key = "";

	if (isset($config['system']['webgui']['port']) && $config['system']['webgui']['port'] != '')
		$portarg = "{$config['system']['webgui']['port']}";

	if ($config['system']['webgui']['protocol'] == "https")
	{
		$cert = $config['cert'][0];

		$crt = base64_decode($cert['crt']);
		$key = base64_decode($cert['prv']);

		if(!$config['system']['webgui']['port'])
			$portarg = "443";
	}

	$max_procs = ($config['system']['webgui']['max_procs']) ? $config['system']['webgui']['max_procs'] : 2;
	system_generate_lighty_config("{$g['varetc_path']}/lighty-webConfigurator.conf", $crt, $key, "lighty-webConfigurator.pid", $portarg, "/usr/local/www/", "cert.pem", $max_procs);

	$res = mwexec("/usr/local/sbin/lighttpd -f {$g['varetc_path']}/lighty-webConfigurator.conf");

	if ($g['booting'])
	{
		if ($res == 0)
			echo "Done.\n";
		else
			echo "Error!\n";
	}

	return $res;
}

function system_generate_lighty_config($filename, $cert, $key, $pid_file, $port = 80, $document_root = "/usr/local/www/", $cert_location = "cert.pem", $max_procs = 1, $captive_portal = false)
{
	global $config, $g;

	if(!is_dir("{$g['tmp_path']}/lighttpdcompress"))
		mkdir("{$g['tmp_path']}/lighttpdcompress");

	if($captive_portal == true)
	{
		$captiveportal = ",\"mod_rewrite\"";
		$captive_portal_rewrite = "url.rewrite-once = ( \"(.*captiveportal.*)\" => \"$1\", \"(.*)\" => \"/index.php?redirurl=$1\" )\n";
		$maxprocperip = $config['captiveportal']['maxprocperip'];

		if ($maxprocperip and $maxprocperip > 0)
		{
			$captive_portal_mod_evasive = "evasive.max-conns-per-ip = {$maxprocperip}";
			$captiveportal .= ",\"mod_evasive\"";
		}
		else
			$captive_portal_mod_evasive = "";

		$server_upload_dirs = "server.upload-dirs = ( \"{$g['tmp_path']}/captiveportal/\" )\n";
		exec("mkdir -p {$g['tmp_path']}/captiveportal");
		exec("chmod a-w {$g['tmp_path']}/captiveportal");
		$server_max_request_size = "server.max-request-size    = 384";
		$cgi_config = "";
	}

	else
	{
		$captiveportal = "";
		$captiveportal = ",\"mod_cgi\"";
		$captive_portal_rewrite = "";
		$captive_portal_mod_evasive = "";
		$server_upload_dirs = "server.upload-dirs = ( \"{$g['upload_path']}/\", \"{$g['tmp_path']}/\", \"/var/\" )\n";
		$server_max_request_size = "server.max-request-size    = 2097152";
		$cgi_config = "cgi.assign = ( \".cgi\" => \"\" )";
	}

	if($port <> "")
		$lighty_port = $port;
	else
		$lighty_port = "80";

	$memory = get_memory();
	$avail = $memory[0];

	if ($captive_portal == true)
	{
		if ($avail <= 135)
			$max_procs = 1;
		else if ($avail > 135 and $avail < 256)
		{
			$max_procs += 1;
		}
		else if ($avail > 255 and $avail < 384)
		{
			$max_procs += 2;
		}
		else if ($avail > 383)
		{
			$max_procs += 3;
		}
	}
	else if ($avail > 135 && $max_procs < 2)
		$max_procs = 2;

	if ($captive_portal == true)
	{
		if ($max_procs > 1)
			$max_php_children = intval($max_procs/2);
		else
			$max_php_children = 1;

		$bin_environment =  <<<EOC
			"bin-environment" => (
				"PHP_FCGI_CHILDREN" => "5",
				"PHP_FCGI_MAX_REQUESTS" => "10000"
			),
EOC;

	}
	else
	{
		$bin_environment =  <<<EOC
			"bin-environment" => (
				"PHP_FCGI_CHILDREN" => "5",
				"PHP_FCGI_MAX_REQUESTS" => "500"
			),
EOC;
	}

	if ($captive_portal !== false)
		$fast_cgi_path = "{$g['tmp_path']}/php-fastcgi-CP.socket";
	else
		$fast_cgi_path = "{$g['tmp_path']}/php-fastcgi.socket";

	$fastcgi_config = <<<EOD
#### fastcgi module
## read fastcgi.txt for more info
fastcgi.server = ( ".php" =>
	( "localhost" =>
		(
			"socket" => "{$fast_cgi_path}",
			"max-procs" => {$max_procs},
{$bin_environment}
			"bin-path" => "/usr/local/bin/php"
		)
	)
)

EOD;

	$lighty_config = "";
	$lighty_config .= <<<EOD
#
# lighttpd configuration file
#
# use a it as base for lighttpd 1.0.0 and above
#
############ Options you really have to take care of ####################

## FreeBSD!
server.event-handler	= "freebsd-kqueue"
server.network-backend 	= "writev"

## modules to load
server.modules              =   ( "mod_access", "mod_accesslog", "mod_expire", "mod_compress", "mod_redirect"
			{$captiveportal},"mod_fastcgi"
		)

server.max-keep-alive-requests = 15
server.max-keep-alive-idle = 30

## a static document-root, for virtual-hosting take look at the
## server.virtual-* options
server.document-root        = "{$document_root}"
{$captive_portal_rewrite}

# Maximum idle time with nothing being written (php downloading)
server.max-write-idle = 999

## where to send error-messages to
server.errorlog-use-syslog="enable"

# files to check for if .../ is requested
server.indexfiles           = ( "index.php", "index.html",
                                "index.htm", "default.htm" )

# mimetype mapping
mimetype.assign             = (
  ".pdf"          =>      "application/pdf",
  ".sig"          =>      "application/pgp-signature",
  ".spl"          =>      "application/futuresplash",
  ".class"        =>      "application/octet-stream",
  ".ps"           =>      "application/postscript",
  ".torrent"      =>      "application/x-bittorrent",
  ".dvi"          =>      "application/x-dvi",
  ".gz"           =>      "application/x-gzip",
  ".pac"          =>      "application/x-ns-proxy-autoconfig",
  ".swf"          =>      "application/x-shockwave-flash",
  ".tar.gz"       =>      "application/x-tgz",
  ".tgz"          =>      "application/x-tgz",
  ".tar"          =>      "application/x-tar",
  ".zip"          =>      "application/zip",
  ".mp3"          =>      "audio/mpeg",
  ".m3u"          =>      "audio/x-mpegurl",
  ".wma"          =>      "audio/x-ms-wma",
  ".wax"          =>      "audio/x-ms-wax",
  ".ogg"          =>      "audio/x-wav",
  ".wav"          =>      "audio/x-wav",
  ".gif"          =>      "image/gif",
  ".jpg"          =>      "image/jpeg",
  ".jpeg"         =>      "image/jpeg",
  ".png"          =>      "image/png",
  ".xbm"          =>      "image/x-xbitmap",
  ".xpm"          =>      "image/x-xpixmap",
  ".xwd"          =>      "image/x-xwindowdump",
  ".css"          =>      "text/css",
  ".html"         =>      "text/html",
  ".htm"          =>      "text/html",
  ".js"           =>      "text/javascript",
  ".asc"          =>      "text/plain",
  ".c"            =>      "text/plain",
  ".conf"         =>      "text/plain",
  ".text"         =>      "text/plain",
  ".txt"          =>      "text/plain",
  ".dtd"          =>      "text/xml",
  ".xml"          =>      "text/xml",
  ".mpeg"         =>      "video/mpeg",
  ".mpg"          =>      "video/mpeg",
  ".mov"          =>      "video/quicktime",
  ".qt"           =>      "video/quicktime",
  ".avi"          =>      "video/x-msvideo",
  ".asf"          =>      "video/x-ms-asf",
  ".asx"          =>      "video/x-ms-asf",
  ".wmv"          =>      "video/x-ms-wmv",
  ".bz2"          =>      "application/x-bzip",
  ".tbz"          =>      "application/x-bzip-compressed-tar",
  ".tar.bz2"      =>      "application/x-bzip-compressed-tar"
 )

# Use the "Content-Type" extended attribute to obtain mime type if possible
#mimetypes.use-xattr        = "enable"

## deny access the file-extensions
#
# ~    is for backupfiles from vi, emacs, joe, ...
# .inc is often used for code includes which should in general not be part
#      of the document-root
url.access-deny             = ( "~", ".inc" )

######### Options that are good to be but not neccesary to be changed #######

## bind to port (default: 80)
server.port                = {$lighty_port}

## error-handler for status 404
#server.error-handler-404   = "/error-handler.html"
#server.error-handler-404   = "/error-handler.php"

## to help the rc.scripts
server.pid-file            = "/var/run/{$pid_file}"

## virtual directory listings
server.dir-listing         = "disable"

## enable debugging
debug.log-request-header   = "disable"
debug.log-response-header  = "disable"
debug.log-request-handling = "disable"
debug.log-file-not-found   = "disable"

# gzip compression
compress.cache-dir = "{$g['tmp_path']}/lighttpdcompress/"
compress.filetype  = ("text/plain","text/css", "text/xml", "text/javascript" )

{$server_upload_dirs}

{$server_max_request_size}

{$fastcgi_config}

{$cgi_config}

{$captive_portal_mod_evasive}

expire.url = (
				"" => "access 50 hours",
        )

EOD;

	$cert = str_replace("\r", "", $cert);
	$key = str_replace("\r", "", $key);

	$cert = str_replace("\n\n", "\n", $cert);
	$key = str_replace("\n\n", "\n", $key);

	if($cert <> "" and $key <> "")
	{
		$fd = fopen("{$g['varetc_path']}/{$cert_location}", "w");

		if (!$fd)
		{
			printf("Error: cannot open cert.pem in system_webgui_start().\n");
			return 1;
		}

		chmod("{$g['varetc_path']}/{$cert_location}", 0600);
		fwrite($fd, $cert);
		fwrite($fd, "\n");
		fwrite($fd, $key);
		fclose($fd);

		$lighty_config .= "\n";
		$lighty_config .= "## ssl configuration\n";
		$lighty_config .= "ssl.engine = \"enable\"\n";
		$lighty_config .= "ssl.pemfile = \"{$g['varetc_path']}/{$cert_location}\"\n\n";

		$lighty_config .= "ssl.use-sslv2 = \"disable\"\n";
		$lighty_config .= "ssl.cipher-list = \"DHE-RSA-CAMELLIA256-SHA:DHE-DSS-CAMELLIA256-SHA:CAMELLIA256-SHA:DHE-DSS-AES256-SHA:AES256-SHA:DHE-RSA-CAMELLIA128-SHA:DHE-DSS-CAMELLIA128-SHA:CAMELLIA128-SHA:DHE-RSA-AES128-SHA:DHE-DSS-AES128-SHA:AES128-SHA:RC4-SHA:RC4-MD5:!aNULL:!eNULL:!3DES:@STRENGTH\"\n";

	}

	if ($captive_portal == false && $config['system']['webgui']['protocol'] == "https" && !isset($config['system']['webgui']['disablehttpredirect']))
	{
		if($lighty_port != "443")
			$redirectport = ":{$lighty_port}";
		$lighty_config .= <<<EOD
\$SERVER["socket"] == ":80" {
	\$HTTP["host"] =~ "(.*)" {
		url.redirect = ( "^/(.*)" => "https://%1{$redirectport}/$1" )
	}
}
EOD;
	}

	$fd = fopen("{$filename}", "w");

	if (!$fd)
	{
		printf("Error: cannot open {$filename} in system_generate_lighty_config().\n");
		return 1;
	}

	fwrite($fd, $lighty_config);
	fclose($fd);

	return 0;
}

function system_timezone_configure()
{
	global $config, $g;

	$syscfg = $config['system'];

	if ($g['booting'])
		echo "\nSetting system date and time...";

	$timezone = $syscfg['timezone'];
	if (!$timezone)
		$timezone = "Etc/UTC";

	exec("LANG=C /usr/bin/tar xzfO /usr/share/zoneinfo.tgz " .
		escapeshellarg($timezone) . " > /etc/localtime");

	mwexec("sync");

	if ($g['booting'])
		echo "Done.\n";
}

function system_ntp_configure($start_ntpd=true)
{
	global $config, $g;
	$driftfile = "/var/db/ntpd.drift";

	$ntpcfg = "# \n";
	$ntpcfg .= "# pfSense ntp configuration file \n";
	$ntpcfg .= "# \n\n";
	$ntpcfg .= "tinker panic 0 \n";

	foreach (explode(' ', $config['system']['timeservers']) as $ts)
		$ntpcfg .= "server {$ts} iburst maxpoll 9\n";

	$ntpcfg .= "driftfile {$driftfile}\n";

	$interfaces = array();

	if (is_array($interfaces) && count($interfaces))
	{
		$ntpcfg .= "interface ignore all\n";
		foreach ($interfaces as $interface) {
			if (!is_ipaddr($interface)) {
				$interface = get_real_interface($interface);
			}
			$ntpcfg .= "interface listen {$interface}\n";
		}
	}

	/* open configuration for wrting or bail */
	$fd = fopen("{$g['varetc_path']}/ntpd.conf","w");

	if(!$fd)
	{
		log_error("Couldn't open {$g['varetc_path']}/ntpd.conf.");
		return;
	}
	fwrite($fd, $ntpcfg);

	/* slurp! */
	fclose($fd);

	/* At bootup we just want to write out the config. */
	if (!$start_ntpd)
		return;

	/* if ntpd is running, kill it */
	while(is_process_running("ntpd"))
		killbyname("ntpd");

	/* if /var/empty does not exist, create it */
	if(!is_dir("/var/empty"))
		exec("/bin/mkdir -p /var/empty && chmod ug+rw /var/empty/.");

	/* start ntpd, allow a large initial skew, and use /var/etc/ntpd.conf - run through tcsh to work around a PHP shell exec bug. */
	exec("echo /usr/local/bin/ntpd -g -c {$g['varetc_path']}/ntpd.conf | tcsh");

	// Note that we are starting up
	log_error("Starting NTPD...");
	return;
}

function sync_system_time()
{
	global $config, $g;

	if ($g['booting'])
		echo "Syncing system time before startup...";

	foreach (explode(' ', $config['system']['timeservers']) as $ts)
	{
		mwexec("/usr/sbin/ntpdate -s $ts");
	}

	if ($g['booting'])
		echo "Done.\n";

}

function system_halt()
{
	global $g;

	system_reboot_cleanup();

	mwexec("/usr/bin/nohup /etc/rc.halt > /dev/null 2>&1 &");
}

function system_reboot()
{
	global $g;

	system_reboot_cleanup();

	mwexec("nohup /etc/rc.reboot > /dev/null 2>&1 &");
}

function system_reboot_sync()
{
	global $g;

	system_reboot_cleanup();

	mwexec("/etc/rc.reboot > /dev/null 2>&1");
}

function system_reboot_cleanup()
{
	mwexec("/usr/local/bin/beep.sh stop");
	require_once("captiveportal.inc");
	captiveportal_radius_stop_all();
}

function system_do_shell_commands($early = 0)
{
	global $config, $g;

	if ($early)
		$cmdn = "earlyshellcmd";
	else
		$cmdn = "shellcmd";

	if (is_array($config['system'][$cmdn]))
	{
		foreach ($config['system'][$cmdn] as $cmd)
		{
			exec($cmd);
		}

	}
	elseif($config['system'][$cmdn] <> "")
	{
		exec($config['system'][$cmdn]);
	}
}

function system_console_configure()
{
	global $config, $g;

	if (isset($config['system']['disableconsolemenu']))
	{
		touch("{$g['varetc_path']}/disableconsole");
	} else {
		unlink_if_exists("{$g['varetc_path']}/disableconsole");
	}
}

function system_dmesg_save()
{
	global $g;
	$dmesg = '';
	exec("/sbin/dmesg", $dmesg);

	$lastcpline = 0;

	for ($i = 0; $i < count($dmesg); $i++) {
		if (strstr($dmesg[$i], "Copyright (c) 1992-"))
			$lastcpline = $i;
	}

	$fd = fopen("{$g['varlog_path']}/dmesg.boot", "w");
	if (!$fd) {
		printf("Error: cannot open dmesg.boot in system_dmesg_save().\n");
		return 1;
	}

	for ($i = $lastcpline; $i < count($dmesg); $i++)
		fwrite($fd, $dmesg[$i] . "\n");

	fclose($fd);

	return 0;
}

function system_setup_sysctl()
{
	global $config;
	activate_sysctls();

	if (isset($config['system']['sharednet']))
	{
		system_disable_arp_wrong_if();
	}
}

function system_disable_arp_wrong_if()
{
	global $config;

	mwexec("/sbin/sysctl -n net.link.ether.inet.log_arp_wrong_iface=0");
	mwexec("/sbin/sysctl -n net.link.ether.inet.log_arp_movements=0");
}

function system_enable_arp_wrong_if()
{
	global $config;

	mwexec("/sbin/sysctl -n net.link.ether.inet.log_arp_wrong_iface=1");
	mwexec("/sbin/sysctl -n net.link.ether.inet.log_arp_movements=1");
}

function system_get_dmesg_boot()
{
	global $g;

	return file_get_contents("{$g['varlog_path']}/dmesg.boot");
}
?>
