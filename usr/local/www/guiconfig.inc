<?php
/*
	guiconfig.inc

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.

	by Scott Ullrich, Copyright 2004, All rights reserved.
	originally based on of m0n0wall (http://m0n0.ch/wall)

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

setlocale(LC_ALL, 'tr_TR.UTF-8');

if (!$omit_nocacheheaders)
{
	header("Expires: 0");
	header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
	header("Cache-Control: no-store, no-cache, must-revalidate");
	header("Cache-Control: post-check=0, pre-check=0", false);
	header("Pragma: no-cache");
}

require_once('authgui.inc');

/* parse the configuration and include all configuration functions */
require_once('functions.inc');

/* used by progress bar */
$lastseen = "-1";

$navlevelsep = ": ";	/* navigation level separator string */
$mandfldhtml = "";		/* display this before mandatory input fields */
$mandfldhtmlspc = "";	/* same as above, but with spacing */

/* Some ajax scripts still need access to GUI */
if(!$ignorefirmwarelock) {
	if (is_subsystem_dirty('firmwarelock')) {
		if (!$d_isfwfile) {
			header("Location: system_firmware.php");
			exit;
		} else {
			return;
		}
	}
}

$firewall_rules_dscp_types = array("af11",
				"af12",
				"af13",
				"af21",
				"af22",
				"af23",
				"af31",
				"af32",
				"af33",
				"af41",
				"af42",
				"af43",
				"EF",
				"1-64",
				"0x10",
				"0x04-0xfc");

$auth_server_types = array(
	'ldap' => "LDAP",
	'radius' => "Radius");

$ldap_urltypes = array(
	'TCP - Standard' => 389,
	'SSL - Encrypted' => 636);

$ldap_scopes = array(
	'one' => "One Level",
	'subtree' => "Entire Subtree");

$ldap_protvers = array(
	2,
	3);

$ldap_templates = array(

	'open' => array(
				'desc' => "OpenLDAP",
				'attr_user' => "cn",
				'attr_group' => "cn",
				'attr_member' => "member"),

	'msad' => array(
				'desc' => "Microsoft AD",
				'attr_user' => "samAccountName",
				'attr_group' => "cn",
				'attr_member' => "memberOf"),

	'edir' => array(
				'desc' => "Novell eDirectory",
				'attr_user' => "cn",
				'attr_group' => "cn",
				'attr_member' => "uniqueMember"));

$radius_srvcs = array(
	'both' => "Authentication and Accounting",
	'auth' => "Authentication",
	'acct' => "Accounting");

$netbios_nodetypes = array(
	'0' => "none",
	'1' => "b-node",
	'2' => "p-node",
	'4' => "m-node",
	'5' => "h-node");

/* some well knows ports */
$wkports = array(
	5999 => "CVSup",
	53 => "DNS",
	21 => "FTP",
	3000 => "HBCI",
	80 => "HTTP",
	443 => "HTTPS",
	5190 => "ICQ",
	113 => "IDENT/AUTH",
	143 => "IMAP",
	993 => "IMAP/S",
	4500 => "IPsec NAT-T",
	500 => "ISAKMP",
	1701 => "L2TP",
	389 => "LDAP",
	1755 => "MMS/TCP",
	7000 => "MMS/UDP",
	445 => "MS DS",
	3389 => "MS RDP",
	1512 => "MS WINS",
	1863 => "MSN",
	119 => "NNTP",
	123 => "NTP",
	138 => "NetBIOS-DGM",
	137 => "NetBIOS-NS",
	139 => "NetBIOS-SSN",
	1194 => "OpenVPN",
	110 => "POP3",
	995 => "POP3/S",
	1723 => "PPTP",
	1812 => "RADIUS",
	1813 => "RADIUS accounting",
	5004 => "RTP",
	5060 => "SIP",
	25 => "SMTP",
	465 => "SMTP/S",
	161 => "SNMP",
	162 => "SNMP-Trap",
	22 => "SSH",
	3478 => "STUN",
	3544 => "Teredo",
	23 => "Telnet",
	69 => "TFTP",
	5900 => "VNC");

/* TCP flags */
$tcpflags = array("fin", "syn", "rst", "psh", "ack", "urg");

$specialnets = array("pptp" => "PPTP clients", "pppoe" => "PPPoE clients", "l2tp" => "L2TP clients");

$spiflist = get_configured_interface_with_descr(false, true);
foreach ($spiflist as $ifgui => $ifdesc) {
	$specialnets[$ifgui] = $ifdesc . " net";
	$specialnets[$ifgui . 'ip'] = $ifdesc . " address";
}

$medias = array("auto" => "autoselect", "100full" => "100BASE-TX full-duplex",
	"100half" => "100BASE-TX half-duplex", "10full" => "10BASE-T full-duplex",
	"10half" => "10BASE-T half-duplex");

$wlan_modes = array("bss" => "Infrastructure (BSS)", "adhoc" => "Ad-hoc (IBSS)",
	"hostap" => "Access Point");

/* platforms that support firmware updating */
$fwupplatforms = array('pfSense', 'net45xx', 'net48xx', 'generic-pc');

function do_input_validation($postdata, $reqdfields, $reqdfieldsn, $input_errors)
{
	foreach ($postdata as $pn => $pd)
	{
		if (is_string($pd) && preg_match("/[\\x00-\\x08\\x0b\\x0c\\x0e-\\x1f]/", $pd))
		{
			$input_errors[] = "'$pn' alanı geçersiz karakterler içeriyor.";
		}
	}

	for ($i = 0; $i < count($reqdfields); $i++)
	{
		if ($_POST[$reqdfields[$i]] == "" && $_REQUEST[$reqdfields[$i]] == "")
		{
			$input_errors[] = "'$reqdfieldsn[$i]' alanı gereklidir.";
		}
	}
}

function print_input_errors($input_errors)
{
	print <<<EOF
	<div class="alert alert-error">
	<button type="button" class="close" data-dismiss="alert">&times;</button>
EOF;
		foreach ($input_errors as $ierr)
		{
			echo htmlspecialchars($ierr) . "<br>";
		}

	print <<<EOF2
	</div>
EOF2;

}

function verify_gzip_file($fname)
{

    $returnvar = mwexec("/usr/bin/gzip -t " . escapeshellarg($fname));
	if ($returnvar != 0)
		return 0;
	else
		return 1;
}

function print_info_box_np($msg, $save=false) {

	if($save) {
		$savebutton = "<input name=\"apply\" type=\"submit\" class=\"btn btn-danger btn-right\" id=\"apply\" value=\"Uygula\">";
		if($_POST['if'])
			$savebutton .= "<input type='hidden' name='if' value='" . htmlspecialchars($_POST['if']) . "'>";
		$alert= 'alert-danger';
	}

	if(!$savebutton) {
		$alert= 'alert-success';
		$savebutton = '';
		$closebutton= '<button type="button" class="close" data-dismiss="alert">×</button>';
	}

	echo <<<EOFnp
<div class="alert {$alert}">
	{$closebutton}
	<span>{$msg}</span>
	{$savebutton}
</div>

EOFnp;

}

function print_info_box_np_undo($msg, $name="apply",$value="Uygula", $undo)
{

	if(stristr($msg, "apply") != false || stristr($msg, "save") != false || stristr($msg, "create") != false) {
		$savebutton = " <input type=\"button\" value=\"Undo\" onClick=\"document.location='{$undo}'\">";
		$savebutton .= " <input name=\"{$name}\" type=\"submit\" class=\"btn btn-danger\" id=\"${name}\" value=\"{$value}\">";
		if($_POST['if'])
			$savebutton .= "<input type='hidden' name='if' value='" . htmlspecialchars($_POST['if']) . "'>";
		$alert= 'alert-warning';
	}

	if(!$savebutton) {
		$alert= 'alert-success';
		$savebutton = '';
		$closebutton= '<button type="button" class="close" data-dismiss="alert">×</button>';
	}

	echo <<<EOFnp
<div class="alert {$alert}">
	{$closebutton}
	<span>{$msg}</span>
	{$savebutton}
	{$undobutton}
</div>


EOFnp;

}

function print_info_box($msg)
{
	print_info_box_np($msg);
}

function get_std_save_message($ok)
{
	global $d_sysrebootreqd_path;
	$filter_related = false;
	$filter_pages = array("nat", "filter");
	$to_return = "Değişiklikler başarıyla uygulandı.";
	foreach($filter_pages as $fp)
		if(stristr($_SERVER['SCRIPT_FILENAME'], $fp))
			$filter_related = true;
	return $to_return;
}

function pprint_address($adr)
{
	global $specialnets;

	if (isset($adr['any'])) {
		$padr = "*";
	} else if ($adr['network']) {
		$padr = $specialnets[$adr['network']];
	} else {
		$padr = $adr['address'];
	}

	if (isset($adr['not']))
		$padr = "! " . $padr;

	return $padr;
}

function pprint_port($port)
{
	global $wkports;

	$pport = "";

	if (!$port)
		return "*";
	else {
		$srcport = explode("-", $port);
		if ((!$srcport[1]) || ($srcport[0] == $srcport[1])) {
			$pport = $srcport[0];
			if ($wkports[$srcport[0]]) {
				$pport .= " (" . $wkports[$srcport[0]] . ")";
			}
		} else
			$pport .= $srcport[0] . " - " . $srcport[1];
	}

	return $pport;
}

function firewall_check_for_advanced_options(&$item)
{
        $item_set = "";
        if($item['max'])
                $item_set .= "max {$item['max']} ";
        if($item['max-src-nodes'])
                $item_set .= "max-src-nodes {$item['max-src-nodes']} ";
        if($item['max-src-conn'])
                $item_set .= "max-src-conn {$item['max-src-conn']} ";
        if($item['max-src-states'])
                $item_set .= "max-src-states {$item['max-src-states']} ";
        if($item['statetype'] != "keep state" && $item['statetype'] != "")
                $item_set .= "statetype {$item['statetype']} ";
        if($item['statetimeout'])
                $item_set .= "statetimeout {$item['statetimeout']} ";
        if($item['nosync'])
                $item_set .= "nosync ";
        if($item['max-src-conn-rate'])
                $item_set .= "max-src-conn-rate {$item['max-src-conn-rate']} ";
        if($item['max-src-conn-rates'])
                $item_set .= "max-src-conn-rates {$item['max-src-conn-rates']} ";
        if($item['gateway'])
                $item_set .= "gateway {$item['gateway']} ";
        if($item['dnpipe'])
                $item_set .= "limiter {$item['dnpipe']} ";
        if($item['pdnpipe'])
                $item_set .= "limiter {$item['pdnpipe']} ";
        if($item['l7container'])
                $item_set .= "layer7 {$item['l7container']} ";
        if($item['tag'])
                $item_set .= "tag {$item['tag']} ";
        if($item['tagged'])
                $item_set .= "tagged {$item['tagged']} ";
        if(isset($item['allowopts']))
                $item_set .= "allowopts ";
        if(isset($item['disablereplyto']))
                $item_set .= "disable reply-to ";
        if($item['tcpflags_any'] || $item['tcpflags1'] || $item['tcpflags2'])
                $item_set .= "tcpflags set";

        return $item_set;
}

function gentitle($title)
{
	global $navlevelsep;
	if(!is_array($title))
		return $title;
	else
		return join($navlevelsep, $title);
}

function genhtmltitle($title)
{
        global $config;
        return gentitle($title);
}

/* update the changedesc and changecount(er) variables */
function update_changedesc($update)
{
	global $changedesc;
	global $changecount;

	$changedesc .= " {$update}";
	$changecount++;
}

function clear_log_file($logfile = "/var/log/system.log")
{
	global $config, $g;
	exec("/usr/bin/killall syslogd");
	if(isset($config['system']['disablesyslogclog'])) {
		unlink($logfile);
		touch($logfile);
	} else {
		if(isset($config['system']['usefifolog']))
			exec("/usr/sbin/fifolog_create -s 511488 {$logfile}");
		else
			exec("/usr/sbin/clog -i -s 511488 {$logfile}");
	}
	system_syslogd_start();
}

function dump_clog($logfile, $tail, $grepfor = "")
{
	global $g, $config;
	$sor = isset($config['syslog']['reverse']) ? "-r" : "";
	$logarr = "";
	$grepline = "  ";

	if(is_array($grepfor))
	{
		foreach($grepfor as $agrep)
			$grepline .= " | grep \"$agrep\"";
	}

	if(is_array($grepinvert))
	{
		foreach($grepinvert as $agrep)
			$grepline .= " | grep -v \"$agrep\"";
	}

	if(file_exists($logfile) && filesize($logfile) == 0)
	{
		$logarr = array("Log file started.");
	}
	else
	{
		exec("/usr/sbin/clog {$logfile}{$grepline}| grep -v \"CLOG\" | grep -v \"\033\" | /usr/bin/tail {$sor} -n {$tail} | /usr/bin/uniq", $logarr);
	}

	foreach ($logarr as $logent)
	{
		$logent = preg_split("/\s+/", $logent, 6);
		echo "<tr>\n";

		$dt = strtotime(join(' ', array_slice($logent, 0, 3)));
		$entry_date_time =  strftime("%T - %e %B", $dt);
		$entry_text = ($logent[3] == $config['system']['hostname']) ? "" : $logent[3] . " ";
		$entry_text .= htmlspecialchars($logent[4] . " " . $logent[5]);

		echo "<td class=\"cell logd\">{$entry_date_time}</td>\n";
		echo "<td class=\"cell logm\">{$entry_text}</td>\n";
		echo "</tr>\n";
	}
}

function dump_timestamp_logs($logfile, $tail, $grepfor = "")
{
	global $g, $config;
	$sor = isset($config['syslog']['reverse']) ? "-r" : "";
	$logarr = "";
	$grepline = "  ";

	if(is_array($grepfor))
	{
		foreach($grepfor as $agrep)
			$grepline .= " | grep \"$agrep\"";
	}

	if(is_array($grepinvert))
	{
		foreach($grepinvert as $agrep)
			$grepline .= " | grep -v \"$agrep\"";
	}

	if(file_exists($logfile) && filesize($logfile) == 0)
	{
		$logarr = array("Log file started.");
	}
	else
	{
		exec("/bin/cat {$logfile}{$grepline}| grep . | /usr/bin/tail {$sor} -n {$tail} | /usr/bin/uniq", $logarr);
	}

	foreach ($logarr as $logent)
	{
		$logent = preg_split("/\s+/", $logent, 6);
		echo "<tr>\n";

		$dt = strtotime(join(' ', array_slice($logent, 0, 3)));
		$entry_date_time =  strftime("%T - %e %B", $dt);
		$entry_text = ($logent[3] == $config['system']['hostname']) ? "" : $logent[3] . " ";
		$entry_text .= htmlspecialchars($logent[4] . " " . $logent[5]);

		echo "<td class=\"cell logd\">{$entry_date_time}</td>\n";
		echo "<td class=\"cell logm\">{$entry_text}</td>\n";
		echo "</tr>\n";
	}
}

function dump_clog_auth($logfile, $tail, $grepfor = "")
{
	global $g, $config;
	$sor = isset($config['syslog']['reverse']) ? "-r" : "";
	$logarr = "";
	$grepline = "  ";

	if(is_array($grepfor))
	{
		foreach($grepfor as $agrep)
			$grepline .= " | grep \"$agrep\"";
	}

	if(is_array($grepinvert))
	{
		foreach($grepinvert as $agrep)
			$grepline .= " | grep -v \"$agrep\"";
	}

	if(file_exists($logfile) && filesize($logfile) == 0)
	{
		$logarr = array("Log file started.");
	}
	else
	{
		exec("/usr/sbin/clog {$logfile}{$grepline}| grep \"logportalauth\" | /usr/bin/tail {$sor} -n {$tail} | /usr/bin/uniq", $logarr);

	}

	foreach ($logarr as $logent)
	{
		$logent = preg_split("/\s+/", $logent, 6);
		echo "<tr>\n";

		$dt = strtotime(join(' ', array_slice($logent, 0, 3)));
		$entry_date_time =  strftime("%T - %e %B", $dt);
		$entry_text = ($logent[3] == $config['system']['hostname']) ? "" : $logent[3] . " ";
		$entry_text .= htmlspecialchars($logent[4] . " " . $logent[5]);

		echo "<td class=\"cell logd\">{$entry_date_time}</td>\n";
		echo "<td class=\"cell logm\">{$entry_text}</td>\n";
		echo "</tr>\n";
	}
}

function dump_mysql($logfile, $tail, $grepfor = "")
{
	global $g, $config;
	$sor = isset($config['syslog']['reverse']) ? "-r" : "";
	$logarr = "";
	$grepline = "  ";

	if(is_array($grepfor))
	{
		foreach($grepfor as $agrep)
			$grepline .= " | grep \"$agrep\"";
	}

	if(is_array($grepinvert))
	{
		foreach($grepinvert as $agrep)
			$grepline .= " | grep -v \"$agrep\"";
	}

	exec("/bin/cat {$logfile}{$grepline}| grep . | /usr/bin/tail {$sor} -n {$tail} | /usr/bin/uniq > /tmp/mysql.log");

	$log = fopen("/tmp/mysql.log", "r");

	if ($log)
	{
		while (($line = fgets($log)) !== false)
		{
			$message = '';
			$datetime = '';
			$has_date = preg_match("#^[0-9]{6}(.*)$#i", $line);

			if($has_date)
			{
				$line_a = split(' {1,2}', $line);
				$date = $line_a[0];
				$time = $line_a[1];

				$year = '20' . substr($date, 0, 2);
				$month = substr($date, 2, 2);
				$day = substr($date, 4, 2);

				$fulltime = $day . '-' . $month . '-' . $year . ' ' . $time;
				$datetime = strftime("%T - %e %B", strtotime($fulltime));

				unset($line_a[0]);
				unset($line_a[1]);

				$message = implode(' ', $line_a);
			}

			else
			{
				$message = $line;
			}

			echo "<tr>\n";
			echo "<td class=\"cell logd\">{$datetime}</td>\n";
			echo "<td class=\"cell logm\">{$message}</td>\n";
			echo "</tr>\n";
		}

		fclose($log);

	}

	else
	{
		log_error('Log dosyası açılamadı');
	}
}

function dump_radius($logfile, $tail, $grepfor = "")
{
	global $g, $config;
	$sor = isset($config['syslog']['reverse']) ? "-r" : "";
	$logarr = "";
	$grepline = "  ";

	if(is_array($grepfor))
	{
		foreach($grepfor as $agrep)
			$grepline .= " | grep \"$agrep\"";
	}

	if(is_array($grepinvert))
	{
		foreach($grepinvert as $agrep)
			$grepline .= " | grep -v \"$agrep\"";
	}

	exec("/bin/cat {$logfile}{$grepline}| grep . | /usr/bin/tail {$sor} -n {$tail} | /usr/bin/uniq > /tmp/radius.log");

	$log = fopen("/tmp/radius.log", "r");

	if ($log)
	{
		while (($line = fgets($log)) !== false)
		{

			$line_a = split(' :', $line);
			$date = $line_a[0];

			$message = $line_a[1];
			$datetime = strftime("%T - %e %B", strtotime($date));

			echo "<tr>\n";
			echo "<td class=\"cell logd\">{$datetime}</td>\n";
			echo "<td class=\"cell logm\">{$message}</td>\n";
			echo "</tr>\n";
		}
		fclose($log);
	}

	else
	{
		log_error('Log dosyası açılamadı');
	}
}

function return_clog($logfile, $tail, $withorig = true, $grepfor = "", $grepinvert = "", $grepreverse = false) {
	global $g, $config;
	$sor = (isset($config['syslog']['reverse']) || $grepreverse) ? "-r" : "";
	$logarr = "";
	$grepline = "  ";
	if(is_array($grepfor))
		foreach($grepfor as $agrep)
			$grepline .= " | grep \"$agrep\"";
	if(is_array($grepinvert))
		foreach($grepinvert as $agrep)
			$grepline .= " | grep -v \"$agrep\"";
	if($config['system']['disablesyslogclog']) {
		exec("cat {$logfile}{$grepline} | /usr/bin/tail {$sor} -n {$tail}", $logarr);
	} else {
		if(isset($config['system']['usefifolog'])) {
			exec("/usr/sbin/fifolog_reader {$logfile}{$grepline} | /usr/bin/tail {$sor} -n {$tail}", $logarr);
		} else {
			exec("/usr/sbin/clog {$logfile}{$grepline}| grep -v \"CLOG\" | grep -v \"\033\" | /usr/bin/tail {$sor} -n {$tail}", $logarr);
		}
	}
	return($logarr);
}

function update_if_changed($varname, & $orig, $new)
{
	if (is_array($orig) && is_array($new)) {
		$a_diff = array_diff($orig, $new);
		foreach ($a_diff as $diff) {
			update_changedesc("removed {$varname}: \"{$diff}\"");
		}
		$a_diff = array_diff($new, $orig);
		foreach ($a_diff as $diff) {
			update_changedesc("added {$varname}: \"{$diff}\"");
		}
		$orig = $new;
		return true;

	} else {
		if ($orig != $new) {
			update_changedesc("{$varname}: \"{$orig}\" -> \"{$new}\"");
			$orig = $new;
			return true;
		}
	}
	return false;
}

function address_to_pconfig($adr, &$padr, &$pmask, &$pnot, &$pbeginport, &$pendport)
{
	if (isset($adr['any']))
		$padr = "any";
	else if ($adr['network'])
		$padr = $adr['network'];
	else if ($adr['address']) {
		list($padr, $pmask) = explode("/", $adr['address']);
		if (!$pmask)
			$pmask = 32;
	}

	if (isset($adr['not']))
		$pnot = 1;
	else
		$pnot = 0;

	if ($adr['port'])
	{
                list($pbeginport, $pendport) = explode("-", $adr['port']);
                if (!$pendport)
                        $pendport = $pbeginport;
	}
	else if (!is_alias($pbeginport) && !is_alias($pendport))
	{
		$pbeginport = "any";
		$pendport = "any";
	}
}

function pconfig_to_address(&$adr, $padr, $pmask, $pnot=false, $pbeginport=0, $pendport=0)
{
        $adr = array();

        if ($padr == "any")
                $adr['any'] = true;
        else if (is_specialnet($padr))
                $adr['network'] = $padr;
        else {
                $adr['address'] = $padr;
                if ($pmask != 32)
                        $adr['address'] .= "/" . $pmask;
        }

        if ($pnot)
                $adr['not'] = true;
        else
                unset($adr['not']);

        if (($pbeginport != 0) && ($pbeginport != "any"))
		{
                if ($pbeginport != $pendport)
                        $adr['port'] = $pbeginport . "-" . $pendport;
                else
                        $adr['port'] = $pbeginport;
        }

        if(is_alias($pbeginport))
		{
                $adr['port'] = $pbeginport;
        }
}

function is_specialnet($net)
{
	global $specialsrcdst;

	if(!$net)
		return false;

	if (in_array($net, $specialsrcdst))
		return true;
	else
		return false;
}

function display_top_tabs(& $tab_array, $no_drop_down = false)
{
        global $HTTP_SERVER_VARS;
        global $config;
        global $g;
		global $tab_array_indent;
		global $tab_array_space;
		global $tab_array_char_limit;

		if ($tab_array_indent == '')
		{
			$tab_array_indent = 0;
		}

		if ($tab_array_space == '')
		{
			$tab_array_space = 1;
		}

		if ($tab_array_char_limit == '')
		{
			$tab_array_char_limit = 120;
		}

        $tab_temp = array ();
        foreach ($tab_array as $ta)
                if(isAllowedPage($ta[2]))
                        $tab_temp[] = $ta;

        unset ($tab_array);
        $tab_array = & $tab_temp;

        $tab_active_bg   = "#EEEEEE";
        $tab_inactive_bg = "#777777";
        $nifty_tabs_corners = "#FFF";
        $font_color = "white";

        $tabcharcount = 0;
        foreach ($tab_array as $ta)
                $tabcharcount = $tabcharcount + strlen($ta[0]);

		if($no_drop_down == true)
		{
			$tabcharcount = 0;
			unset($tab_array_char_limit);
		}

		echo "<div class=\"newtabmenu\" style=\"margin:{$tab_array_space}px {$tab_array_indent}px; width:775px;\">\n";
		echo "<!-- Tabbed bar code-->\n";
		echo "<ul class=\"newtabmenu\">\n";
		$tabscounter = 0;
		foreach ($tab_array as $ta)
		{
			if ($ta[1] == true) {
					echo "  <li class=\"newtabmenu_active\"><a href=\"{$ta[2]}\"><span>{$ta[0]}</span></a></li>\n";
			} else {
					echo "  <li><a href=\"{$ta[2]}\"><span>{$ta[0]}</span></a></li>\n";
			}
			$tabscounter++;
		}
		echo "</ul>\n</div>\n";

}

function rule_popup($src,$srcport,$dst,$dstport)
{
	global $config;
	$aliases_array = array();
	if($config['aliases']['alias'] <> "" and is_array($config['aliases']['alias']))
	{
		$span_begin = "";
            $alias_src_span_begin = "";
            $alias_src_span_end = "";
            $alias_src_port_span_begin = "";
            $alias_src_port_span_end = "";
            $alias_dst_span_begin = "";
            $alias_dst_span_end = "";
            $alias_dst_port_span_begin = "";
            $alias_dst_port_span_end = "";
            $alias_content_text = "";
        foreach($config['aliases']['alias'] as $alias_name)
        {
            	$alias_addresses = explode (" ", $alias_name['address']);
            	$alias_details = explode ("||", $alias_name['detail']);
            	$alias_objects_with_details = "";
            	$counter = 0;
		if ($alias_name['url']) {
			$alias_objects_with_details .= $alias_name['url'] . "<br/>";
		}
            	foreach($alias_addresses as $alias_ports_address)
            	{
                    	$alias_objects_with_details .= $alias_addresses[$counter];
                    	$alias_detail_default = strpos ($alias_details[$counter], "Entry added");
                    	if ($alias_details[$counter] != "" && $alias_detail_default === False){
                            	$alias_objects_with_details .=" - " . base64_decode($alias_details[$counter]);
                    	}
                    	$alias_objects_with_details .= "<br>";
                    	$counter++;
            	}
            	//max character length for caption field
            	$maxlength = 60;

            	$alias_descr_substr = base64_decode($alias_name['descr']);
            	$alias_content_text = htmlspecialchars($alias_objects_with_details);
            	$alias_caption = htmlspecialchars($alias_descr_substr);
            	$strlength = strlen ($alias_caption);
            	if ($strlength >= $maxlength)
                    	$alias_caption = substr($alias_caption, 0, $maxlength) . "...";

		$alias_caption_escaped = str_replace("'", "\'", $alias_caption);
		$alias_content_escaped = str_replace("'", "\'", $alias_content_text);
		$span_begin = "<span onmouseover=\"domTT_activate(this, event, 'content', '<p>$alias_caption_escaped</p><p>$alias_content_escaped</p>', 'trail', true, 'delay', 0, 'fade', 'both', 'fadeMax', 93, 'styleClass', 'niceTitle');\" onmouseout=\"this.style.color = ''; domTT_mouseout(this, event);\"><U>";

            	if ($alias_name['name'] == $src)
                    	$alias_src_span_begin = $span_begin;
            	if ($alias_name['name'] == $srcport)
                    	$alias_src_port_span_begin = $span_begin;
            	if ($alias_name['name'] == $dst)
                    	$alias_dst_span_begin = $span_begin;
            	if ($alias_name['name'] == $dstport)
                    	$alias_dst_port_span_begin = $span_begin;
        }
        $descriptions = array ();
        $descriptions['src'] = $alias_src_span_begin;
        $descriptions['srcport'] = $alias_src_port_span_begin;
        $descriptions['dst'] = $alias_dst_span_begin;
        $descriptions['dstport'] = $alias_dst_port_span_begin;

        return $descriptions;
  	}
}

?>
