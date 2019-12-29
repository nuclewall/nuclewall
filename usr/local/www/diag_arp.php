<?php
/*
	diag_arp.php

	Copyright (C) 2013-2015 Ogün AÇIK
	All rights reserved.

	part of the pfSense project	(http://www.pfsense.org)
	Copyright (C) 2004-2009 Scott Ullrich <sullrich@gmail.com>

	originally part of m0n0wall (http://m0n0.ch/wall)
	Copyright (C) 2005 Paul Taylor (paultaylor@winndixie.com) and Manuel Kasper <mk@neon1.net>.
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

@ini_set('zlib.output_compression', 0);
@ini_set('implicit_flush', 1);

require('guiconfig.inc');

function leasecmp($a, $b) {
	return strcmp($a[$_GET['order']], $b[$_GET['order']]);
}

function adjust_gmt($dt) {
	$ts = strtotime($dt . " GMT");
	return strftime("%Y/%m/%d %H:%M:%S", $ts);
}

function remove_duplicate($array, $field) {
	foreach ($array as $sub)
		$cmp[] = $sub[$field];
	$unique = array_unique($cmp);
	foreach ($unique as $k => $rien)
		$new[] = $array[$k];
	return $new;
}

$awk = "/usr/bin/awk";

$leasesfile = "{$g['dhcpd_chroot_path']}/var/db/dhcpd.leases";

$cleanpattern = "'{ gsub(\"#.*\", \"\");} { gsub(\";\", \"\"); print;}'";

$splitpattern = "'BEGIN { RS=\"}\";} {for (i=1; i<=NF; i++) printf \"%s \", \$i; printf \"}\\n\";}'";

exec("cat {$leasesfile} | {$awk} {$cleanpattern} | {$awk} {$splitpattern}", $leases_content);
$leases_count = count($leases_content);

$pools = array();
$leases = array();
$i = 0;
$l = 0;
$p = 0;
while($i < $leases_count) {
	$data = explode(" ", $leases_content[$i]);
	$f = 0;
	$fcount = count($data);
	if($fcount < 20) {
		$i++;
		continue;
	}
	while($f < $fcount) {
		switch($data[$f]) {
			case "failover":
				$pools[$p]['name'] = $data[$f+2];
				$pools[$p]['mystate'] = $data[$f+7];
				$pools[$p]['peerstate'] = $data[$f+14];
				$pools[$p]['mydate'] = $data[$f+10];
				$pools[$p]['mydate'] .= " " . $data[$f+11];
				$pools[$p]['peerdate'] = $data[$f+17];
				$pools[$p]['peerdate'] .= " " . $data[$f+18];
				$p++;
				$i++;
				continue 3;
			case "lease":
				$leases[$l]['ip'] = $data[$f+1];
				$leases[$l]['type'] = "dynamic";
				$f = $f+2;
				break;
			case "starts":
				$leases[$l]['start'] = $data[$f+2];
				$leases[$l]['start'] .= " " . $data[$f+3];
				$f = $f+3;
				break;
			case "ends":
				$leases[$l]['end'] = $data[$f+2];
				$leases[$l]['end'] .= " " . $data[$f+3];
				$f = $f+3;
				break;
			case "tstp":
				$f = $f+3;
				break;
			case "tsfp":
				$f = $f+3;
				break;
			case "atsfp":
				$f = $f+3;
				break;
			case "cltt":
				$f = $f+3;
				break;
			case "binding":
				switch($data[$f+2]) {
					case "active":
						$leases[$l]['act'] = "active";
						break;
					case "free":
						$leases[$l]['act'] = "expired";
						$leases[$l]['online'] = "offline";
						break;
					case "backup":
						$leases[$l]['act'] = "reserved";
						$leases[$l]['online'] = "offline";
						break;
				}
				$f = $f+1;
				break;
			case "next":
				$f = $f+3;
				break;
			case "rewind":
				$f = $f+3;
				break;
			case "hardware":
				$leases[$l]['mac'] = $data[$f+2];
				if($leases[$l]['act'] == "active") {
					$online = exec("/usr/sbin/arp -an |/usr/bin/awk '/{$leases[$l]['ip']}/ {print}'|wc -l");
					if ($online == 1) {
						$leases[$l]['online'] = 'online';
					} else {
						$leases[$l]['online'] = 'offline';
					}
				}
				$f = $f+2;
				break;
			case "client-hostname":
				if($data[$f+1] <> "") {
					$leases[$l]['hostname'] = preg_replace('/"/','',$data[$f+1]);
				} else {
					$hostname = gethostbyaddr($leases[$l]['ip']);
					if($hostname <> "") {
						$leases[$l]['hostname'] = $hostname;
					}
				}
				$f = $f+1;
				break;
			case "uid":
				$f = $f+1;
				break;
		}
		$f++;
	}
	$l++;
	$i++;
}

if(count($leases) > 0) {
	$leases = remove_duplicate($leases,"ip");
}

if(count($pools) > 0) {
	$pools = remove_duplicate($pools,"name");
	asort($pools);
}

$dhcpmac = array();
$dhcpip = array();

foreach ($leases as $value) {
	$dhcpmac[$value['mac']] = $value['hostname'];
	$dhcpip[$value['ip']] = $value['hostname'];
}

exec("/usr/sbin/arp -an",$rawdata);

$i = 0;

$ifdescrs = get_configured_interface_with_descr();

foreach ($ifdescrs as $key => $interface) {
	$thisif = convert_friendly_interface_to_real_interface_name($key);
	if (!empty($thisif))
		$hwif[$thisif] = $interface;
}

$data = array();
foreach ($rawdata as $line) {
	$elements = explode(' ',$line);

	if ($elements[3] != "(incomplete)") {
		$arpent = array();
		$arpent['ip'] = trim(str_replace(array('(',')'),'',$elements[1]));
		$arpent['mac'] = trim($elements[3]);
		$arpent['interface'] = trim($elements[5]);
		$data[] = $arpent;
	}
}

function _getHostName($mac,$ip) {
	global $dhcpmac, $dhcpip;

	if ($dhcpmac[$mac])
		return $dhcpmac[$mac];
	else if ($dhcpip[$ip])
		return $dhcpip[$ip];
	else{
		exec("host -W 1 $ip", $output);
		if (preg_match('/.*pointer ([A-Za-z0-9.-]+)\..*/',$output[0],$matches)) {
			if ($matches[1] <> $ip)
				return $matches[1];
		}
	}
	return "";
}

$pgtitle = array('DURUM', 'ARP TABLOSU');

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php
for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
ob_implicit_flush(1);

$dnsavailable=1;
$dns = trim(_getHostName("", "8.8.8.8"));
if ($dns == ""){
	$dns = trim(_getHostName("", "8.8.4.4"));
	if ($dns == "") $dnsavailable =0;
}

foreach ($data as &$entry)
{
	if ($dnsavailable)
		$dns = trim(_getHostName($entry['mac'], $entry['ip']));
	else
		$dns="";

	if(trim($dns))
		$entry['dnsresolve'] = "$dns";
	else
		$entry['dnsresolve'] = "Z_ ";
}

$data = msort($data, "dnsresolve");
$mac_man = load_mac_manufacturer_table();
?>
<table border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<table class="grids sortable" width="100%">
							<tr>
								<td class="head">IP Adresi</td>
								<td class="head">MAC Adresi</td>
								<td class="head">Sunucu Adı</td>
								<td class="head">Arayüz</td>
							</tr>
							<?php foreach ($data as $entry): ?>
								<tr>
									<td class="cell"><?=$entry['ip'];?></td>
									<td class="cell">

										<?php
										$mac = str_replace(':', '-', $entry['mac']);
										$mac_hi = strtoupper($mac[0] . $mac[1] . $mac[3] . $mac[4] . $mac[6] . $mac[7]);

										if(isset($mac_man[$mac_hi]))
											$mac = str_replace(':', '-', $mac_man[$mac_hi]);
										?>
									<a title="HOTSPOT Özel izinli MAC adresleri listesine ekle" class="btn-link" href="hotspot_mac_edit.php?act=new&mac=<?=$mac?>"><?=$mac?></a>
									</td>
									<td class="cell">
										<?php
										echo str_replace("Z_ ", "", $entry['dnsresolve']);
										?>
									</td>
									<td class="cell"><?=$hwif[$entry['interface']];?></td>
								</tr>
							<?php endforeach; ?>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</div>
</body>
</html>