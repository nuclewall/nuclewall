<?php
/*
	captiveportal.inc
	part of pfSense (http://www.pfSense.org)
	Copyright (C) 2004-2011 Scott Ullrich <sullrich@gmail.com>
	Copyright (C) 2009 Ermal Luçi <ermal.luci@gmail.com>
	Copyright (C) 2003-2006 Manuel Kasper <mk@neon1.net>.

	originally part of m0n0wall (http://m0n0.ch/wall)
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

	This version of captiveportal.inc has been modified by Rob Parker
	<rob.parker@keycom.co.uk> to include changes for per-user bandwidth management
	via returned RADIUS attributes. This page has been modified to delete any
	added rules which may have been created by other per-user code (index.php, etc).
	These changes are (c) 2004 Keycom PLC.
*/

require_once('config.inc');
require_once('functions.inc');
require_once('filter.inc');
require_once('radius.inc');
require_once('Mobile_Detect.php');


function captiveportal_configure()
{
	global $config, $g;

	$captiveportallck = lock('captiveportal', LOCK_EX);
	
	if (isset($config['captiveportal']['enable']))
	{
		if ($g['booting'])
			echo "HOTSPOT baþlatýlýyor... ";

		killbypid("{$g['varrun_path']}/lighty-CaptivePortal.pid");

		unlink_if_exists("{$g['vardb_path']}/captiveportal.db");
		unlink_if_exists("{$g['vardb_path']}/captiveportal_mac.db");
		unlink_if_exists("{$g['vardb_path']}/captiveportal_ip.db");
		unlink_if_exists("{$g['vardb_path']}/captiveportal_radius.db");

		touch("{$g['vardb_path']}/captiveportal.db");

		killbypid("{$g['varrun_path']}/cp_prunedb.pid");
		
		captiveportal_init_rules(true);
		
		captiveportal_radius_stop_all();

		$croninterval = $config['captiveportal']['croninterval'] ? $config['captiveportal']['croninterval'] : 60;

		if ((!is_numeric($croninterval)) || ($croninterval < 10))
			$croninterval = 60;

		captiveportal_write_elements();

		captiveportal_init_webgui();

		if(file_exists("{$g['varrun_path']}/cp_prunedb.pid"))
			killbypid("{$g['varrun_path']}/cp_prunedb.pid");

		mwexec("/usr/local/bin/minicron $croninterval {$g['varrun_path']}/cp_prunedb.pid " .
			"/etc/rc.prunecaptiveportal");

		captiveportal_init_radius_servers();

		if ($g['booting'])
			echo "Tamam\n";
	}
	
	else
	{
		killbypid("{$g['varrun_path']}/lighty-CaptivePortal.pid");
		killbypid("{$g['varrun_path']}/lighty-CaptivePortal-SSL.pid");
		killbypid("{$g['varrun_path']}/cp_prunedb.pid");

		captiveportal_radius_stop_all();

		mwexec("/sbin/sysctl net.link.ether.ipfw=0");

		if (is_module_loaded("ipfw.ko"))		
			mwexec("/sbin/kldunload ipfw.ko");
		$listifs = get_configured_interface_list_by_realif();
		foreach ($listifs as $listrealif => $listif) {
			if (!empty($listrealif)) {
				if (does_interface_exist($listrealif)) {
					pfSense_interface_flags($listrealif, -IFF_IPFW_FILTER);
					$carpif = link_ip_to_carp_interface(find_interface_ip($listrealif));
					if (!empty($carpif)) {
						$carpsif = explode(" ", $carpif);
						foreach ($carpsif as $cpcarp)
							pfSense_interface_flags($cpcarp, -IFF_IPFW_FILTER);
					}
				}
			}
		}
	}

	unlock($captiveportallck);
	
	return 0;
}

function captiveportal_init_webgui()
{
	global $g, $config;

	 if (!isset($config['captiveportal']['enable']))
		return;
			
	system_generate_lighty_config("{$g['varetc_path']}/lighty-CaptivePortal.conf","", "", "lighty-CaptivePortal.pid", "8000", "/usr/local/captiveportal/","cert-portal.pem", 1, true);

	@unlink("{$g['varrun_path']}/lighty-CaptivePortal.pid");
	
	$res = mwexec("/usr/local/sbin/lighttpd -f {$g['varetc_path']}/lighty-CaptivePortal.conf");
}

function captiveportal_init_rules($reinit = false)
{
	global $config, $g;

	if (!isset($config['captiveportal']['enable']))
		return;

	$cpips = array();
	$ifaces = get_configured_interface_list();
	
	foreach ($ifaces as $kiface => $kiface2)
	{
		$tmpif = get_real_interface($kiface);
		pfSense_interface_flags($tmpif, -IFF_IPFW_FILTER);
	}
	
	$cpinterfaces = explode(",", $config['captiveportal']['interface']);
	$firsttime = 0;
	
	foreach ($cpinterfaces as $cpifgrp)
	{
		if (!isset($ifaces[$cpifgrp]))
			continue;
		$tmpif = get_real_interface($cpifgrp);
		
		if (!empty($tmpif))
		{
			if ($firsttime > 0)
				$cpinterface .= " or ";
			$cpinterface .= "via {$tmpif}";
			$firsttime = 1;
			$cpipm = get_interface_ip($cpifgrp);
			
			if (is_ipaddr($cpipm))
			{
				$carpif = link_ip_to_carp_interface($cpipm);
				if (!empty($carpif))
				{
					$carpsif = explode(" ", $carpif);
					foreach ($carpsif as $cpcarp)
					{
						pfSense_interface_flags($cpcarp, IFF_IPFW_FILTER);
						$carpip = find_interface_ip($cpcarp);
						if (is_ipaddr($carpip))
							$cpips[] = $carpip;
					}
				}
				$cpips[] = $cpipm;
				pfSense_interface_flags($tmpif, IFF_IPFW_FILTER);
			}
		}
	}
	
	if (count($cpips) > 0)
	{
		$cpactive = true;
		$cpinterface = "{ {$cpinterface} } ";
	}
	
	else
		return false;

	if ($reinit == false)
		$captiveportallck = lock('captiveportal');

	captiveportal_init_ipfw_ruleno();

	filter_load_ipfw();

	if (!is_module_loaded("dummynet.ko"))
		mwexec("/sbin/kldload dummynet");

	$cprules =	"add 65291 set 1 allow pfsync from any to any\n";
	$cprules .= "add 65292 set 1 allow carp from any to any\n";

	$cprules .= <<<EOD
# add 65300 set 1 skipto 65534 all from any to any not layer2
# layer 2: pass ARP
add 65301 set 1 pass layer2 mac-type arp
# pfsense requires for WPA
add 65302 set 1 pass layer2 mac-type 0x888e
add 65303 set 1 pass layer2 mac-type 0x88c7

# PPP Over Ethernet Discovery Stage
add 65304 set 1 pass layer2 mac-type 0x8863
# PPP Over Ethernet Session Stage
add 65305 set 1 pass layer2 mac-type 0x8864

# layer 2: block anything else non-IP
add 65307 set 1 deny layer2 not mac-type ip

EOD;

	$rulenum = 65310;
	$ipcount = 0;
	$ips = "";
	foreach ($cpips as $cpip)
	{
		if($ipcount == 0)
		{
			$ips = "{$cpip} ";
		}
		else
		{
			$ips .= "or {$cpip} ";
		}
		$ipcount++;
	}
	$ips = "{ 255.255.255.255 or {$ips} }";
	$cprules .= "add {$rulenum} set 1 pass ip from any to {$ips} in\n";
	$rulenum++;
	$cprules .= "add {$rulenum} set 1 pass ip from {$ips} to any out\n";
	$rulenum++;
	$cprules .= "add {$rulenum} set 1 pass icmp from {$ips} to any out icmptype 0\n";
	$rulenum++;
	$cprules .= "add {$rulenum} set 1 pass icmp from any to {$ips} in icmptype 8 \n";
	$rulenum++;
	/* Allowed ips */
	$cprules .= "add {$rulenum} allow ip from table(3) to any in\n";
	$rulenum++;
	$cprules .= "add {$rulenum} allow ip from any to table(4) out\n";
	$rulenum++;
	$cprules .= "add {$rulenum} pipe tablearg ip from table(5) to any in\n";
	$rulenum++;
	$cprules .= "add {$rulenum} pipe tablearg ip from any to table(6) out\n";
	$rulenum++;
	$cprules .= "add {$rulenum} allow ip from any to table(7) in\n";
	$rulenum++;
	$cprules .= "add {$rulenum} allow ip from table(8) to any out\n";
	$rulenum++;
	$cprules .= "add {$rulenum} pipe tablearg ip from any to table(9) in\n";
	$rulenum++;
	$cprules .= "add {$rulenum} pipe tablearg ip from table(10) to any out\n";
	$rulenum++;

	if (isset($config['captiveportal']['peruserbw']))
	{
		$cprules .= "add {$rulenum} set 1 pipe tablearg ip from table(1) to any in\n";
		$rulenum++;
		$cprules .= "add {$rulenum} set 1 pipe tablearg ip from any to table(2) out\n";
		$rulenum++;
	}
	else
	{
		$cprules .= "add {$rulenum} set 1 allow ip from table(1) to any in\n";
		$rulenum++;
		$cprules .= "add {$rulenum} set 1 allow ip from any to table(2) out\n";
		$rulenum++;
	}
	
	   $cprules .= <<<EOD

# redirect non-authenticated clients to captive portal
add 65531 set 1 fwd 127.0.0.1,8000 tcp from any to any in
# let the responses from the captive portal web server back out
add 65532 set 1 pass tcp from any to any out
# block everything else
add 65533 set 1 deny all from any to any
# pass everything else on layer2
add 65534 set 1 pass all from any to any layer2

EOD;

	$cprules .= captiveportal_passthrumac_configure(true);
	$cprules .= "\n";

	$cprules .= captiveportal_allowedip_configure();

	$cprules .= captiveportal_allowedhostname_configure();
	
	if ($reinit == true)
		$cprules = "table all flush\nflush\n{$cprules}";
	else
	{
		$tmprules = "table 3 flush\n";
		$tmprules .= "table 4 flush\n";
		$tmprules .= "table 5 flush\n";
		$tmprules .= "table 6 flush\n";
		$tmprules .= "table 7 flush\n";
		$tmprules .= "table 8 flush\n";
		$tmprules .= "table 9 flush\n";
		$tmprules .= "table 10 flush\n";
		$tmprules .= "flush\n";
		$cprules = "{$tmprules}\n{$cprules}";
	}

	file_put_contents("{$g['tmp_path']}/ipfw.cp.rules", $cprules);
	mwexec("/sbin/ipfw -q {$g['tmp_path']}/ipfw.cp.rules", true);

	if ($reinit == false)
		unlock($captiveportallck);

	mwexec("/sbin/sysctl net.link.ether.ipfw=1");
	mwexec("/sbin/sysctl net.inet.ip.fw.one_pass=1");

	return $cprules;
}

function captiveportal_prune_old()
{
	global $g, $config;

	if (empty($config['captiveportal']['timeout']) ||
	!is_numeric($config['captiveportal']['timeout']))
		$timeout = 0;
	else
		$timeout = $config['captiveportal']['timeout'] * 60;

	if (empty($config['captiveportal']['idletimeout']) ||
	!is_numeric($config['captiveportal']['idletimeout']))
		$idletimeout = 0;
	else
		$idletimeout = $config['captiveportal']['idletimeout'] * 60;

	if (!$timeout && !$idletimeout)
		return;

	$radiusservers = captiveportal_get_radius_servers();

	$cpdb = captiveportal_read_db();

	$unsetindexes = array();

	$pruning_time = time();
	$stop_time = $pruning_time;
	foreach ($cpdb as $cpentry)
	{

		$timedout = false;
		$term_cause = 1;

		if ($timeout)
		{
			if (($pruning_time - $cpentry[0]) >= $timeout)
			{
				$timedout = true;
				$term_cause = 5;
			}
		}

		if (!$timedout && !empty($cpentry[9]))
		{
			if ($pruning_time >= $cpentry[9])
			{
				$timedout = true;
				$term_cause = 5;
			}
		}

		$uidletimeout = (is_numeric($cpentry[8])) ? $cpentry[8] : $idletimeout;
		
		if (!$timedout && $uidletimeout > 0)
		{
			$lastact = captiveportal_get_last_activity($cpentry[2]);
			$lastact = $lastact ? $lastact : $cpentry[0];
			if ($lastact && (($pruning_time - $lastact) >= $uidletimeout))
			{
				$timedout = true;
				$term_cause = 4;
				$stop_time = $lastact;
			}
		}

		if ($timedout)
		{
			captiveportal_disconnect($cpentry, $radiusservers,$term_cause,$stop_time);
			captiveportal_logportalauth($cpentry[4], $cpentry[3], $cpentry[2], "TIMEOUT");
			$unsetindexes[] = $cpentry[5];
		}

		if (!$timedout && !empty($radiusservers))
		{			
			RADIUS_ACCOUNTING_STOP($cpentry[1],
				$cpentry[4],
				$cpentry[5],
				$cpentry[0],
				$radiusservers,
				$cpentry[2],
				$cpentry[3],
				10);
			exec("/sbin/ipfw table 1 entryzerostats {$cpentry[2]}");
			exec("/sbin/ipfw table 2 entryzerostats {$cpentry[2]}");
			RADIUS_ACCOUNTING_START($cpentry[1],
				$cpentry[4],
				$cpentry[5],
				$radiusservers,
				$cpentry[2],
				$cpentry[3]);
			
			}

			if (isset($config['captiveportal']['reauthenticate']))
			{
				$auth_list = RADIUS_AUTHENTICATION($cpentry[4],
					base64_decode($cpentry[6]),
					$radiusservers,
					$cpentry[2],
					$cpentry[3],
					$cpentry[1]);
					
				if ($auth_list['auth_val'] == 3)
				{
					captiveportal_disconnect($cpentry, $radiusservers, 17);
					captiveportal_logportalauth($cpentry[4], $cpentry[3], $cpentry[2], "RADIUS_DISCONNECT", $auth_list['reply_message']);
					$unsetindexes[] = $cpentry[5];
				}
				
				else if ($auth_list['auth_val'] == 2)
					captiveportal_reapply_attributes($cpentry, $auth_list);
			}
		
	}

	if (!empty($unsetindexes))
		captiveportal_write_db($cpdb, false, $unsetindexes);
}

function captiveportal_disconnect($dbent, $radiusservers,$term_cause = 1,$stop_time = null)
{
	global $g, $config;

	$stop_time = (empty($stop_time)) ? time() : $stop_time;

	if (!empty($radiusservers))
	{
		RADIUS_ACCOUNTING_STOP($dbent[1],
			$dbent[4],
			$dbent[5],
			$dbent[0],
			$radiusservers,
			$dbent[2],
			$dbent[3],
			$term_cause,
			false,
			$stop_time);
	}
	
	if (is_ipaddr($dbent[2]))
	{
		mwexec("/sbin/ipfw table 1 delete {$dbent[2]}");
		mwexec("/sbin/ipfw table 2 delete {$dbent[2]}");
		mwexec("pfctl -k {$dbent[2]}");
		mwexec("pfctl -K {$dbent[2]}");
	}

	if (isset($config['captiveportal']['peruserbw']))
	{
		mwexec("/sbin/ipfw pipe " . ($dbent[1]+20000) . " delete");
		mwexec("/sbin/ipfw pipe " . ($dbent[1]+20001) . " delete");
	}
	
	captiveportal_free_ipfw_ruleno($dbent[1]);
}

function captiveportal_disconnect_client($sessionid, $term_cause = 1, $logoutReason = "LOGOUT")
{
	global $g, $config;

	$radiusservers = captiveportal_get_radius_servers();
	$unsetindex = array();

	$cpdb = captiveportal_read_db();

	if (isset($cpdb[$sessionid]))
	{
		$cpentry = $cpdb[$sessionid];
		$unsetindex[] = $sessionid;
		
		captiveportal_write_db($cpdb, false, $unsetindex);

		captiveportal_disconnect($cpentry, $radiusservers, $term_cause);
		captiveportal_logportalauth($cpentry[4], $cpentry[3], $cpentry[2], 'OTURUM KAPATILDI');
	}		
}

function captiveportal_radius_stop_all()
{
	global $config;
	$radiusservers = captiveportal_get_radius_servers();
	if (!empty($radiusservers))
	{
		$cpdb = captiveportal_read_db();
		foreach ($cpdb as $cpentry)
		{
			RADIUS_ACCOUNTING_STOP($cpentry[1],
				$cpentry[4],
				$cpentry[5],
				$cpentry[0],
				$radiusservers,
				$cpentry[2],
				$cpentry[3],
				7);
		}
	}
}

function captiveportal_passthrumac_configure_entry($macent)
{
	$rules = "";
	$enBwup = isset($macent['bw_up']);
	$enBwdown = isset($macent['bw_down']);
	$actionup = "allow";
	$actiondown = "allow";

	$ruleno = captiveportal_get_next_ipfw_ruleno(2000, 49899, true);

	if($enBwup)
	{
		$bw_up = $ruleno + 20000;
		$rules .= "pipe {$bw_up} config bw {$macent['bw_up']}Kbit/s queue 100\n";
		$actionup = "pipe {$bw_up}";
	}
	
	if($enBwdown)
	{
		$bw_down = $ruleno + 20001;
		$rules .= "pipe {$bw_down} config bw {$macent['bw_down']}Kbit/s queue 100\n";
		$actiondown = "pipe {$bw_down}";
	}
	
	$rules .= "add {$ruleno} {$actiondown} ip from any to any MAC {$macent['mac']} any\n";
	$ruleno++;
	$rules .= "add {$ruleno} {$actionup} ip from any to any MAC any {$macent['mac']}\n";

	return $rules;
}

function captiveportal_passthrumac_configure($lock = false)
{
	global $config, $g;
	$rules = "";

	if (is_array($config['captiveportal']['passthrumac']))
	{
		$macdb = array();
		foreach ($config['captiveportal']['passthrumac'] as $macent)
		{
			$rules .= captiveportal_passthrumac_configure_entry($macent);
			$macdb[$macent['mac']]['active']  = true;

		}
	}

	return $rules;
}

function captiveportal_passthrumac_findbyname($username)
{
	global $config;

	if (is_array($config['captiveportal']['passthrumac'])) {
		foreach ($config['captiveportal']['passthrumac'] as $macent)
		{
			if ($macent['username'] == $username)
				return $macent;
		}
	}
	return NULL;
}

function captiveportal_allowedip_configure_entry($ipent)
{

	if($ipent['ip']) 	
		$ipaddress = $ipent['ip'];

	if($ipent['hostname'])
	{
		$ipaddress = gethostbyname($ipent['hostname']);
		if(!is_ipaddr($ipaddress)) 
			return;
	}

	$rules = "";
	$enBwup = intval($ipent['bw_up']);
	$enBwdown = intval($ipent['bw_down']);
	$bw_up = "";
	$bw_down = "";
	$tablein = array();
	$tableout = array();

	if (intval($enBwup) > 0 or intval($enBwdown) > 0)
		$ruleno = captiveportal_get_next_ipfw_ruleno(2000, 49899, true);
	else
		$ruleno = captiveportal_get_next_ipfw_ruleno(2000, 49899, false);

	if ($ipent['dir'] == "from") {
		if ($enBwup)
			$tablein[] = 5;
		else
			$tablein[] = 3;
		if ($enBwdown)
			$tableout[] = 6;
		else
			$tableout[] = 4;
	} else if ($ipent['dir'] == "to") {
		if ($enBwup)
			$tablein[] = 9;
		else
			$tablein[] = 7;
		if ($enBwdown)
			$tableout[] = 10;
		else
			$tableout[] = 8;
	} else if ($ipent['dir'] == "both") {
		if ($enBwup) {
			$tablein[] = 5;
			$tablein[] = 9;
		} else {
			$tablein[] = 3;
			$tablein[] = 7;
		}
		if ($enBwdown) {
			$tableout[] = 6;
			$tableout[] = 10;
		} else {
			$tableout[] = 4;
			$tableout[] = 8;
		}
	}
	if ($enBwup) {
		$bw_up = $ruleno + 20000;
		$rules .= "pipe {$bw_up} config bw {$ipent['bw_up']}Kbit/s queue 100\n";
	}
	$subnet = "";
	if (!empty($ipent['sn']))
		$subnet = "/{$ipent['sn']}";
	foreach ($tablein as $table)
		$rules .= "table {$table} add {$ipaddress}{$subnet} {$bw_up}\n";
	if ($enBwdown) {
		$bw_down = $ruleno + 20001;
		$rules .= "pipe {$bw_down} config bw {$ipent['bw_down']}Kbit/s queue 100\n";
	}
	foreach ($tableout as $table)
		$rules .= "table {$table} add {$ipaddress}{$subnet} {$bw_down}\n";

	return $rules;
}


function setup_dnsfilter_entries()
{
	global $g, $config;

	$cp_filterdns_filename = "{$g['varetc_path']}/filterdns-captiveportal.conf";
	$cp_filterdns_conf = "";
	
	if (is_array($config['captiveportal']['allowedhostname']))
	{
		foreach ($config['captiveportal']['allowedhostname'] as $hostnameent)
		{
			$cp_filterdns_conf .= "ipfw {$hostnameent['hostname']} 3\n";
			$cp_filterdns_conf .= "ipfw {$hostnameent['hostname']} 4\n";
			$cp_filterdns_conf .= "ipfw {$hostnameent['hostname']} 7\n";
			$cp_filterdns_conf .= "ipfw {$hostnameent['hostname']} 8\n";
		}
	}
	
	file_put_contents($cp_filterdns_filename, $cp_filterdns_conf);
	
	if (file_exists("{$g['tmp_path']}/filterdns-cpah.pid"))
	{
		if (isvalidpid("{$g['tmp_path']}/filterdns-cpah.pid"))
			sigkillbypid("{$g['tmp_path']}/filterdns-cpah.pid", "HUP");
		else
		{
			killbypid("{$g['tmp_path']}/filterdns-cpah.pid");
			mwexec("/usr/local/sbin/filterdns -p {$g['tmp_path']}/filterdns-cpah.pid -i 300 -c {$cp_filterdns_filename} -d 1");
		}
	}
}

function captiveportal_allowedhostname_configure()
{
	global $config, $g;

	$rules = "\n# captiveportal_allowedhostname_configure()\n";
	setup_dnsfilter_entries();
	if (is_array($config['captiveportal']['allowedhostname'])) {
		foreach ($config['captiveportal']['allowedhostname'] as $hostnameent) 
			$rules .= captiveportal_allowedip_configure_entry($hostnameent);
	}
	return $rules;
}

function captiveportal_allowedip_configure()
{
	global $config, $g;

	$rules = "";
	if (is_array($config['captiveportal']['allowedip'])) {
		foreach ($config['captiveportal']['allowedip'] as $ipent) 
			$rules .= captiveportal_allowedip_configure_entry($ipent);
	}

	return $rules;
}

function captiveportal_get_last_activity($ip)
{
	$ipfwoutput = "";

	exec("/sbin/ipfw table 1 entrystats {$ip} 2>/dev/null", $ipfwoutput);
	if ($ipfwoutput[0]) {
		$ri = explode(" ", $ipfwoutput[0]);
		if ($ri[4])
			return $ri[4];
	}

	return 0;
}

function captiveportal_init_radius_servers()
{
	global $config, $g;

	if ($config['captiveportal']['radiusip'] && (!isset($config['captiveportal']['auth_method']) ||
		($config['captiveportal']['auth_method'] == "radius"))) {
		$radiusip = $config['captiveportal']['radiusip'];
		$radiusip2 = ($config['captiveportal']['radiusip2']) ? $config['captiveportal']['radiusip2'] : null;

		if ($config['captiveportal']['radiusport'])
			$radiusport = $config['captiveportal']['radiusport'];
		else
			$radiusport = 1812;
		if ($config['captiveportal']['radiusacctport'])
			$radiusacctport = $config['captiveportal']['radiusacctport'];
		else
			$radiusacctport = 1813;
		if ($config['captiveportal']['radiusport2'])
			$radiusport2 = $config['captiveportal']['radiusport2'];
		else
			$radiusport2 = 1812;
		$radiuskey = $config['captiveportal']['radiuskey'];
		$radiuskey2 = ($config['captiveportal']['radiuskey2']) ? $config['captiveportal']['radiuskey2'] : null;

		$cprdsrvlck = lock('captiveportalradius', LOCK_EX);
		$fd = @fopen("{$g['vardb_path']}/captiveportal_radius.db", "w");
		if (!$fd) {
			captiveportal_syslog("Error: cannot open radius DB file in captiveportal_configure().\n");
			unlock($cprdsrvlck);
			return 1;
		} else if (isset($radiusip2, $radiuskey2))
			fwrite($fd,$radiusip . "," . $radiusport . "," . $radiusacctport . "," . $radiuskey . "\n"
			. $radiusip2 . "," . $radiusport2 . "," . $radiusacctport . "," . $radiuskey2);
		else
			fwrite($fd,$radiusip . "," . $radiusport . "," . $radiusacctport . "," . $radiuskey);
		fclose($fd);
		unlock($cprdsrvlck);
	}
}

function captiveportal_get_radius_servers()
{
	global $g;

	$cprdsrvlck = lock('captiveportalradius');
	if (file_exists("{$g['vardb_path']}/captiveportal_radius.db"))
	{
		$radiusservers = array();
		$cpradiusdb = file("{$g['vardb_path']}/captiveportal_radius.db", 
		FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if ($cpradiusdb) {
			foreach($cpradiusdb as $cpradiusentry) {
				$line = trim($cpradiusentry);
				if ($line) {
					$radsrv = array();
					list($radsrv['ipaddr'],$radsrv['port'],$radsrv['acctport'],$radsrv['key']) = explode(",",$line);
					$radiusservers[] = $radsrv;
				}
			}
		}
		unlock($cprdsrvlck);
		return $radiusservers;
	}

	unlock($cprdsrvlck);
	return false;
}

function captiveportal_logportalauth($user,$mac,$ip,$status, $message = null)
{
	if (!$message)
		$message = "$status: $user, $mac, $ip";
	else {
		$message = trim($message);
		$message = "$status: $user, $mac, $ip, $message";
	}
	captiveportal_syslog($message);
}

function captiveportal_syslog($message)
{
	define_syslog_variables();
	$message = trim($message);
	openlog("logportalauth", LOG_PID, LOG_LOCAL4);
	// Log it
	syslog(LOG_INFO, $message);
	closelog();
}

function radius($username, $password, $clientip, $clientmac, $type)
{
	global $g, $config;

	$ruleno = captiveportal_get_next_ipfw_ruleno();

	if (is_null($ruleno))
	{
		$auth_list = array();
		$auth_list['auth_val'] = 1;
		$auth_list['error'] = 'Sistemin oturum sayýsý kapasitesi aþýldý.';
		return $auth_list;
	}

	$radiusservers = captiveportal_get_radius_servers();

	$auth_list = RADIUS_AUTHENTICATION($username, $password, $radiusservers, $clientip, $clientmac, $ruleno);

	if ($auth_list['auth_val'] == 2)
	{
		captiveportal_logportalauth($username, $clientmac, $clientip, $type);
		$sessionid = portal_allow($clientip, $clientmac, $username, $password, $auth_list, $ruleno, 'label-info');
	}

	return $auth_list;
}

function captiveportal_read_db($locked = false, $index = 5)
{
	global $g;

	$cpdb = array();

	if ($locked == false)
		$cpdblck = lock('captiveportaldb');
	$fd = @fopen("{$g['vardb_path']}/captiveportal.db", "r");
	
	if ($fd)
	{
		while (!feof($fd)) {
			$line = trim(fgets($fd));
			if ($line) {
				$cpe = explode(",", $line);
				$cpdb[$cpe[$index]] = $cpe;
			}
		}
		fclose($fd);
	}
	if ($locked == false)
		unlock($cpdblck);
	return $cpdb;
}

function captiveportal_write_db($cpdb, $locked = false, $remove = false)
{
	global $g;

	if ($locked == false)
		$cpdblck = lock('captiveportaldb', LOCK_EX);

	if (is_array($remove)) {
		if (!empty($remove)) {
			$cpdb = captiveportal_read_db(true);
			foreach ($remove as $key) {
				if (is_array($key))
					log_error("Captive portal Array passed as unset index: " . print_r($key, true));
				else
					unset($cpdb[$key]);
			}
		} else {
			if ($locked == false)
				unlock($cpdblck);
			return;
		}
	}
	$fd = @fopen("{$g['vardb_path']}/captiveportal.db", "w");
	if ($fd) {
		foreach ($cpdb as $cpent) {
			fwrite($fd, join(",", $cpent) . "\n");
		}
		fclose($fd);
	}
	if ($locked == false)
		unlock($cpdblck);
}

function captiveportal_write_elements()
{
	global $g, $config;
	
	if (is_dir($g['captiveportal_element_path'])) {
		$dh = opendir($g['captiveportal_element_path']);
		while (($file = readdir($dh)) !== false) {
			if ($file != "." && $file != "..")
				unlink($g['captiveportal_element_path'] . "/" . $file);
		}
		closedir($dh);
	} else {
		@mkdir($g['captiveportal_element_path']);
	}

	if (is_array($config['captiveportal']['element'])) {
		foreach ($config['captiveportal']['element'] as $data)
		{
			$fd = @fopen($g['captiveportal_element_path'] . '/' . $data['name'], "wb");
			if (!$fd)
			{
				printf("Error: cannot open '{$data['name']}' in captiveportal_write_elements().\n");
				return 1;
			}
			
			$decoded = base64_decode($data['content']);
			fwrite($fd,$decoded);
			fclose($fd);
			unlink_if_exists("{$g['captiveportal_path']}/{$data['name']}");
			unlink_if_exists("{$g['captiveportal_path']}/{$data['name']}");
			mwexec("cd {$g['captiveportal_path']}/ && ln -s {$g['captiveportal_element_path']}/{$data['name']} {$data['name']}");
		}
	}
	
	return 0;
}

function captiveportal_init_ipfw_ruleno($rulenos_start = 2000, $rulenos_range_max = 49899)
{
	global $g;

	@unlink("{$g['vardb_path']}/captiveportal.rules");
	$rules = array_pad(array(), $rulenos_range_max - $rulenos_start, false);
	file_put_contents("{$g['vardb_path']}/captiveportal.rules", serialize($rules));
}

function captiveportal_get_next_ipfw_ruleno($rulenos_start = 2000, $rulenos_range_max = 49899, $usebw = false)
{
	global $config, $g;

	if(!isset($config['captiveportal']['enable']))
		return NULL;

	$cpruleslck = lock('captiveportalrules', LOCK_EX);
	$ruleno = 0;
	if (file_exists("{$g['vardb_path']}/captiveportal.rules"))
	{
		$rules = unserialize(file_get_contents("{$g['vardb_path']}/captiveportal.rules"));
		for ($ridx = 2; $ridx < ($rulenos_range_max - $rulenos_start); $ridx++)
		{
			if ($rules[$ridx])
			{

				if (isset($config['captiveportal']['peruserbw']) || $usebw == true)
					$ridx++;
				continue;
			}
			$ruleno = $ridx;
			$rules[$ridx] = "used";
			if (isset($config['captiveportal']['peruserbw']) || $usebw == true)
				$rules[++$ridx] = "used";
			break;
		}
	}
	else
	{
		$rules = array_pad(array(), $rulenos_range_max - $rulenos_start, false);
		$rules[2] = "used";
		$ruleno = 2;
	}
	
	file_put_contents("{$g['vardb_path']}/captiveportal.rules", serialize($rules));
	unlock($cpruleslck);
	return $ruleno;
}

function captiveportal_free_ipfw_ruleno($ruleno, $usedbw = false)
{
	global $config, $g;

	if(!isset($config['captiveportal']['enable']))
		return NULL;

	$cpruleslck = lock('captiveportalrules', LOCK_EX);
	if (file_exists("{$g['vardb_path']}/captiveportal.rules"))
	{
		$rules = unserialize(file_get_contents("{$g['vardb_path']}/captiveportal.rules"));
		$rules[$ruleno] = false;
		if (isset($config['captiveportal']['peruserbw']) || $usedbw == true)
			$rules[++$ruleno] = false;
		file_put_contents("{$g['vardb_path']}/captiveportal.rules", serialize($rules));
	}
	unlock($cpruleslck);
}

function captiveportal_get_ipfw_passthru_ruleno($value)
{
	global $config, $g;

	if(!isset($config['captiveportal']['enable']))
		return NULL;

	$cpruleslck = lock('captiveportalrules', LOCK_EX);
	if (file_exists("{$g['vardb_path']}/captiveportal.rules"))
	{
		$rules = unserialize(file_get_contents("{$g['vardb_path']}/captiveportal.rules"));
		$ruleno = intval(`/sbin/ipfw show | /usr/bin/grep {$value} |  /usr/bin/grep -v grep | /usr/bin/cut -d " " -f 1 | /usr/bin/head -n 1`);
		if ($rules[$ruleno]) {
			unlock($cpruleslck);
			return $ruleno;
		}
	}

	unlock($cpruleslck);
	return NULL;
}


function getVolume($ip)
{
	$volume = array();

	$volume['input_pkts'] = $volume['input_bytes'] = $volume['output_pkts'] = $volume['output_bytes'] = 0 ;

	$ipfwin = "";
	$ipfwout = "";
	$matchesin = "";
	$matchesout = "";
	exec("/sbin/ipfw table 1 entrystats {$ip}", $ipfwin);
	if ($ipfwin[0]) {
		$ipfwin = split(" ", $ipfwin[0]);
		$volume['input_pkts'] = $ipfwin[2];
		$volume['input_bytes'] = $ipfwin[3];
	}

	exec("/sbin/ipfw table 2 entrystats {$ip}", $ipfwout);
	if ($ipfwout[0]) {
		$ipfwout = split(" ", $ipfwout[0]);
		$volume['output_pkts'] = $ipfwout[2];
		$volume['output_bytes'] = $ipfwout[3];
	}

	return $volume;
}


function getNasID()
{
	$nasId = "";
	exec("/bin/hostname", $nasId);
	if(!$nasId[0])
		$nasId[0] = "{$g['product_name']}";
	return $nasId[0];
}


function getNasIP()
{
	global $config;

	if (empty($config['captiveportal']['radiussrcip_attribute'])) {
			$nasIp = get_interface_ip();
	} else {
		if (is_ipaddr($config['captiveportal']['radiussrcip_attribute']))
			$nasIp = $config['captiveportal']['radiussrcip_attribute'];
		else
			$nasIp = get_interface_ip($config['captiveportal']['radiussrcip_attribute']);
	}
		
	if(!is_ipaddr($nasIp))
		$nasIp = "0.0.0.0";

	return $nasIp;
}

function portal_ip_from_client_ip($cliip) {
	global $config;

	$interfaces = explode(",", $config['captiveportal']['interface']);
	foreach ($interfaces as $cpif) {
		$ip = get_interface_ip($cpif);
		$sn = get_interface_subnet($cpif);
		if (ip_in_subnet($cliip, "{$ip}/{$sn}"))
			return $ip;
	}

	$iface = exec_command("/sbin/route -n get {$cliip} | /usr/bin/awk '/interface/ { print \$2; };'");
	$iface = trim($iface, "\n");
	if (!empty($iface)) {
		$ip = find_interface_ip($iface);
		if (is_ipaddr($ip))
			return $ip;
	}

	$ip = $_SERVER['SERVER_ADDR'];
	if (is_ipaddr($ip))
		return $ip;

	return false;
}


function portal_reply_page($redirurl, $type = null, $message = null, $clientmac = null, $clientip = null)
{
	global $g, $config;
	$detect = new Mobile_Detect;
	
	if( $detect->isMobile() && !$detect->isTablet() )
	{
		$login_page = '/usr/local/captiveportal/pages/hotspot_mobile.html';
		$error_page = '/usr/local/captiveportal/pages/hotspot_mobile_err.html';
	}
	
	else
	{
		$login_page = '/usr/local/captiveportal/pages/hotspot.html';
		$error_page = '/usr/local/captiveportal/pages/hotspot_err.html';
	}
	
	$ifip = portal_ip_from_client_ip($clientip);
	
	if (!$ifip)
		$ourhostname = $config['system']['hostname'] . ":8000";
	else
		$ourhostname = "{$ifip}:8000";
	
	if ($type == 'redir')
	{
		header("Location: {$redirurl}");
		return;
	}
	
	else if ($type == "login")
	{
		$htmltext = get_include_contents($login_page);
		$htmltext = str_replace("#PORTAL_ACTION#", "http://{$ourhostname}/", $htmltext);
		$htmltext = str_replace("#PORTAL_REDIRURL#", htmlspecialchars($redirurl), $htmltext);
	}
	
	else
	{
		$htmltext = get_include_contents($error_page);
		$htmltext = str_replace("#PORTAL_ACTION#", "http://{$ourhostname}/", $htmltext);
		$htmltext = str_replace("#PORTAL_REDIRURL#", htmlspecialchars($redirurl), $htmltext);
		$htmltext = str_replace("#PORTAL_MESSAGE#", htmlspecialchars($message), $htmltext);
	}
	
    echo $htmltext;
}

function portal_mac_radius($clientmac,$clientip)
{
    global $config ;

    $radmac_secret = $config['captiveportal']['radmac_secret'];

    $username = mac_format($clientmac);
    $auth_list = radius($username, $radmac_secret, $clientip, $clientmac, 'IZINLI MAC ADRESI');
    if ($auth_list['auth_val'] == 2)
        return TRUE;
    if (!empty($auth_list['url_redirection']))
	portal_reply_page($auth_list['url_redirection'], 'redir');

    return FALSE;
}

function captiveportal_reapply_attributes($cpentry, $attributes)
{
	global $config, $g;

	$peruserbw = isset($config['captiveportal']['peruserbw']);

	$bw_up = isset($attributes['bw_up']) ? round(intval($attributes['bw_up'])/1000, 2) : $config['captiveportal']['bwdefaultup'];
	$bw_down = isset($attributes['bw_down']) ? round(intval($attributes['bw_down'])/1000, 2) : $config['captiveportal']['bwdefaultdn'];
	$bw_up_pipeno = $cpentry[1]+20000;
	$bw_down_pipeno = $cpentry[1]+20001;
	$commands = "";

	if ($peruserbw && !empty($bw_up) && is_numeric($bw_up))
	{
		$commands .= "pipe {$bw_up_pipeno} config bw {$bw_up}Kbit/s queue 100\n";

		if (!isset($config['captiveportal']['nomacfilter'])) {
			$commands .= "table 1 del {$cpentry[2]} mac {$cpentry[3]}\n";
			$commands .= "table 1 add {$cpentry[2]} mac {$cpentry[3]} {$bw_up_pipeno}\n";
		} else {
			$commands .= "table 1 del {$cpentry[2]}\n";
			$commands .= "table 1 add {$cpentry[2]} {$bw_up_pipeno}\n";
		}
	}
	if ($peruserbw && !empty($bw_down) && is_numeric($bw_down)) {
		$commands .= "pipe {$bw_down_pipeno} config bw {$bw_down}Kbit/s queue 100\n";

		if (!isset($config['captiveportal']['nomacfilter'])) {
			$commands .= "table 2 del {$cpentry[2]} mac {$cpentry[3]}\n";
			$commands .= "table 2 add {$cpentry[2]} mac {$cpentry[3]} {$bw_down_pipeno}\n";
		} else {
			$commands .= "table 2 del {$cpentry[2]}\n";
			$commands .= "table 2 add {$cpentry[2]} {$bw_down_pipeno}\n";
		}
	}

	if (!empty($commands)) {
		@file_put_contents("{$g['tmp_path']}/reattribute.rule.tmp", $commands);
		mwexec("/sbin/ipfw -q {$g['tmp_path']}/reattribute.rule.tmp");
	}

	unset($bw_up_pipeno, $bw_Down_pipeno, $bw_up, $bw_down);
}

function portal_allow($clientip,$clientmac,$username,$password = null, $attributes = null, $ruleno = null, $datasource) 
{
	global $redirurl, $g, $config, $type, $passthrumac, $_POST;

	if ($ruleno == null)
		$ruleno = captiveportal_get_next_ipfw_ruleno();

	if (is_null($ruleno))
	{
		portal_reply_page($redirurl, "error", "Sistemin oturum sayýsý kapasitesi aþýldý");
		log_error("UYARI! Sistemin oturum sayýsý kapasitesi aþýldý.");
		exit;
	}

	if (!is_array($attributes))
		$attributes = array();

	$radiusservers = captiveportal_get_radius_servers();
	$cpdblck = lock('captiveportaldb', LOCK_EX);
	unset($sessionid);
	$cpdb = captiveportal_read_db(true);
	$writecfg = false;
	
	$allow_time = time();

	foreach ($cpdb as $sid => $cpentry)
	{
		if($cpentry[2] == $clientip)
		{
			if ($cpentry[3] == $clientmac)
				captiveportal_logportalauth($cpentry[4],$cpentry[3],$cpentry[2],"CONCURRENT LOGIN - REUSING OLD SESSION");
			else
				captiveportal_logportalauth($cpentry[4],$cpentry[3],$cpentry[2],"CONCURRENT LOGIN - REUSING IP {$cpentry[2]} WITH DIFFERENT MAC ADDRESS {$cpentry[3]}");
			$sessionid = $sid;
			break;
		}
	}

	if (!isset($sessionid))
	{
		$tod = gettimeofday();
		$sessionid = substr(md5(mt_rand() . $tod['sec'] . $tod['usec'] . $clientip . $clientmac), 0, 16);

		mwexec("/sbin/ipfw table 1 add {$clientip} mac {$clientmac}");
		mwexec("/sbin/ipfw table 2 add {$clientip} mac {$clientmac}");

		$bpassword = base64_encode($password);
		$cpdb[] = array($allow_time, $ruleno, $clientip, $clientmac, $username, $sessionid, $bpassword, 
		$attributes['session_timeout'], $attributes['idle_timeout'], $attributes['session_terminate_time'], $datasource);

		captiveportal_write_db($cpdb, true);
		unlock($cpdblck);
		
		if (!empty($radiusservers))
		{
			$acct_val = RADIUS_ACCOUNTING_START($ruleno, $username, $sessionid, $radiusservers, $clientip, $clientmac);
			if ($acct_val == 1)
				captiveportal_logportalauth($username, $clientmac, $clientip, $type, "FreeRADIUS sunucusuna eriþilirken hata oluþtu.");
		}
	}
	else
		unlock($cpdblck);

	if ($writecfg == true)
		write_config();

	if (!empty($attributes['url_redirection']))
		$my_redirurl = $attributes['url_redirection'];
	else
		$my_redirurl = $redirurl;

	header("Location: " . $my_redirurl);

	return $sessionid;
}

function portal_consume_passthrough_credit($clientmac)
{
	global $config;

	if (!empty($config['captiveportal']['freelogins_count']) && is_numeric($config['captiveportal']['freelogins_count']))
		$freeloginscount = $config['captiveportal']['freelogins_count'];
	else
		return false;

	if (!empty($config['captiveportal']['freelogins_resettimeout']) && is_numeric($config['captiveportal']['freelogins_resettimeout']))
		$resettimeout = $config['captiveportal']['freelogins_resettimeout'];
	else
		return false;

	if ($freeloginscount < 1 || $resettimeout <= 0 || !$clientmac)
		return false;

	$updatetimeouts = isset($config['captiveportal']['freelogins_updatetimeouts']);

	$usedmacs = captiveportal_read_usedmacs_db();

	$currenttime = time();
	$found = false;
	foreach ($usedmacs as $key => $usedmac) {
		$usedmac = explode(",", $usedmac);

		if ($usedmac[1] == $clientmac) {
			if ($usedmac[0] + ($resettimeout * 3600) > $currenttime) {
				if ($usedmac[2] < 1) {
					if ($updatetimeouts) {
						$usedmac[0] = $currenttime;
						unset($usedmacs[$key]);
						$usedmacs[] = implode(",", $usedmac);
						captiveportal_write_usedmacs_db($usedmacs);
					}

					return false;
				} else {
					$usedmac[2] -= 1;
					$usedmacs[$key] = implode(",", $usedmac);
				}

				$found = true;
			} else
				unset($usedmacs[$key]);

			break;
		} else if ($usedmac[0] + ($resettimeout * 3600) <= $currenttime)
				unset($usedmacs[$key]);
	}

	if (!$found) {
		$usedmac = array($currenttime, $clientmac, $freeloginscount - 1);
		$usedmacs[] = implode(",", $usedmac);
	}

	captiveportal_write_usedmacs_db($usedmacs);
	return true;
}

function captiveportal_read_usedmacs_db()
{
	global $g;

	$cpumaclck = lock('captiveusedmacs');
	if (file_exists("{$g['vardb_path']}/captiveportal_usedmacs.db"))
	{
		$usedmacs = file("{$g['vardb_path']}/captiveportal_usedmacs.db", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		if (!$usedmacs)
			$usedmacs = array();
	} else
		$usedmacs = array();

	unlock($cpumaclck);
	return $usedmacs;
}

function captiveportal_write_usedmacs_db($usedmacs)
{
	global $g;
	$cpumaclck = lock('captiveusedmacs', LOCK_EX);
	@file_put_contents("{$g['vardb_path']}/captiveportal_usedmacs.db", implode("\n", $usedmacs));
	unlock($cpumaclck);
}

?>
