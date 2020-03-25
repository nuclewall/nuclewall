<?php
/* $Id$ */
/*
	status_dhcp_leases.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.

	Copyright (C) 2004-2009 Scott Ullrich
	All rights reserved.

	originially part of m0n0wall (http://m0n0.ch/wall)
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

$pgtitle = array('STATUS ', 'DHCP LEASES');

$leasesfile = "{$g['dhcpd_chroot_path']}/var/db/dhcpd.leases";

if (($_GET['deleteip']) && (is_ipaddr($_GET['deleteip'])))
{
	killbyname("dhcpd");

	$leases_contents = explode("\n", file_get_contents($leasesfile));
	$newleases_contents = array();
	$i=0;
	while ($i < count($leases_contents)) {
		if ($leases_contents[$i] == "lease {$_GET['deleteip']} {")
		{
			do
			{
				$i++;
			} while ($leases_contents[$i] != "}");
		}
		else
		{
			$newleases_contents[] = $leases_contents[$i];
		}
		$i++;
	}

	$fd = fopen($leasesfile, 'w');
	fwrite($fd, implode("\n", $newleases_contents));
	fclose($fd);

	services_dhcpd_configure();
	header("Location: status_dhcp_leases.php");
}

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php
function leasecmp($a, $b)
{
        return strcmp($a[$_GET['order']], $b[$_GET['order']]);
}

function adjust_gmt($dt)
{
        $ts = strtotime($dt . " GMT");
        return strftime("%d-%m-%Y %H:%M", $ts);
}

function remove_duplicate($array, $field)
{
  foreach ($array as $sub)
   $cmp[] = $sub[$field];
  $unique = array_unique(array_reverse($cmp,true));
  foreach ($unique as $k => $rien)
   $new[] = $array[$k];
  return $new;
}

$awk = "/usr/bin/awk";
$cleanpattern = "'{ gsub(\"#.*\", \"\");} { gsub(\";\", \"\"); print;}'";

$splitpattern = "'BEGIN { RS=\"}\";} {for (i=1; i<=NF; i++) printf \"%s \", \$i; printf \"}\\n\";}'";

exec("/bin/cat {$leasesfile} | {$awk} {$cleanpattern} | {$awk} {$splitpattern}", $leases_content);
$leases_count = count($leases_content);
exec("/usr/sbin/arp -an", $rawdata);
$arpdata = array();
foreach ($rawdata as $line) {
	$elements = explode(' ',$line);
	if ($elements[3] != "(incomplete)") {
		$arpent = array();
		$arpent['ip'] = trim(str_replace(array('(',')'),'',$elements[1]));
	$arpdata[] = $arpent['ip'];
	}
}

$leases = array();
$i = 0;
$l = 0;
$p = 0;

while($i < $leases_count)
{
	$data = explode(" ", $leases_content[$i]);

	$f = 0;
	$fcount = count($data);

	if($fcount < 20)
	{
		$i++;
		continue;
	}
	while($f < $fcount)
	{
		switch($data[$f])
		{
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
				switch($data[$f+2])
				{
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

				if (in_array($leases[$l]['ip'], $arpdata))
				{
					$leases[$l]['online'] = 'online';
					$leases[$l]['class'] = "label-success";
				}
				else
				{
					$leases[$l]['online'] = 'offline';
					$leases[$l]['class'] = "";
				}

				$f = $f+2;
				break;
			case "client-hostname":
				if($data[$f+1] <> "")
				{
					$leases[$l]['hostname'] = preg_replace('/"/','',$data[$f+1]);
				}
				else
				{
					$hostname = gethostbyaddr($leases[$l]['ip']);
					if($hostname <> "")
					{
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

if(count($leases) > 0)
{
	$leases = remove_duplicate($leases,"ip");
}

foreach($config['interfaces'] as $ifname => $ifarr)
{
	if (is_array($config['dhcpd'][$ifname]) &&
		is_array($config['dhcpd'][$ifname]['staticmap']))
		{
		foreach($config['dhcpd'][$ifname]['staticmap'] as $static)
		{
			$slease = array();
			$slease['ip'] = $static['ipaddr'];
			$slease['type'] = "Static";
			$slease['mac'] = $static['mac'];
			$slease['start'] = "";
			$slease['end'] = "";
			$slease['hostname'] = htmlentities($static['hostname']);
			$slease['act'] = "Static";
			$online = exec("/usr/sbin/arp -an |/usr/bin/grep {$slease['mac']}| /usr/bin/wc -l|/usr/bin/awk '{print $1;}'");
			if ($online == 1)
			{
				$slease['online'] = 'online';
				$slease['class'] = "label-success";
			}
			else
			{
				$slease['online'] = 'offline';
				$slease['class'] = "";
			}
			$leases[] = $slease;
		}
	}
}

if ($_GET['order'])
	usort($leases, "leasecmp");
?>
<table cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<table class="grids sortable">
							<tr>
								<td class="head">IP Address</td>
								<td class="head">MAC Address</td>
								<td class="head">Hostname</td>
								<td class="head">Start</td>
								<td class="head">End</td>
								<td class="head">Status</td>
								<td class="head">Lease Type</td>
								<td class="head"></td>
							</tr>
							<?php
							$mac_man = load_mac_manufacturer_table();

							foreach ($leases as $data)
							{
								$lip = ip2ulong($data['ip']);

								if ($data['act'] == "Static")
								{
									foreach ($config['dhcpd'] as $dhcpif => $dhcpifconf)
									{
										if(is_array($dhcpifconf['staticmap']))
										{
											foreach ($dhcpifconf['staticmap'] as $staticent)
											{
												if ($data['ip'] == $staticent['ipaddr'])
												{
													$data['if'] = $dhcpif;
													break;
												}
											}
										}

										if ($data['if'] != "")
											break;
									}
								}

								else
								{
									foreach ($config['dhcpd'] as $dhcpif => $dhcpifconf)
									{
										if (($lip >= ip2ulong($dhcpifconf['range']['from'])) && ($lip <= ip2ulong($dhcpifconf['range']['to'])))
										{
											$data['if'] = $dhcpif;
											break;
										}
									}
								}

								echo "<tr>\n";
								echo "<td class=\"cell dhcpip\">{$data['ip']}</td>\n";

								$mac = $data['mac'];
								$mac_hi = strtoupper($mac[0] . $mac[1] . $mac[3] . $mac[4] . $mac[6] . $mac[7]);
								$mac_url =  str_replace(':', '-', $data['mac']);

								if(isset($mac_man[$mac_hi]))
								{
									echo "<td title=\"Add to Hotspot allowed MAC addresses list\" class=\"cell dhcpmac\"><a class=\"btn-link\" href=\"hotspot_mac_edit.php?act=new&mac={$mac_url}\">{$mac}<br>{$mac_man[$mac_hi]}</a></td>\n";
								}
								else
								{
									echo "<td title=\"Add to Hotspot allowed MAC addresses list\" class=\"cell dhcpmac\"><a class=\"btn-link\" href=\"hotspot_mac_edit.php?act=new&mac={$mac_url}\">{$data['mac']}</a></td>\n";
								}

								echo "<td class=\"cell dhcphostname\">" . htmlentities($data['hostname']) . "</td>\n";

								if ($data['type'] != "Static")
								{
									echo "<td class=\"cell dhcpdate\">" . adjust_gmt($data['start']) . "</td>\n";
									echo "<td class=\"cell dhcpdate\">" . adjust_gmt($data['end']) . "</td>\n";
								}

								else
								{
									echo "<td class=\"cell\">None</td>\n";
									echo "<td class=\"cell\">none</td>\n";
								}

								echo "<td class=\"cell dhcpstat\"><span class=\"label {$data['class']}\">{$data['online']}</span></td>\n";
								echo "<td class=\"cell dhcptype\">{$data['act']}</td>\n";
								echo "<td class=\"cell tools dhcp\">";

								if ($data['type'] == "dynamic")
								{
									echo "<a title=\"Make this release static\" href=\"services_dhcp_edit.php?if={$data['if']}&mac={$data['mac']}&hostname={$data['hostname']}\">";
									echo "<i class=\"icon-plus\"></i></a>\n";
								}

								if (($data['type'] == "dynamic") && ($data['online'] != 'online'))
								{
									echo "<a title=\"Delete this lease\" href=\"status_dhcp_leases.php?deleteip={$data['ip']} \">";
									echo "<i class=\"icon-trash\"></i></a>\n";
								}
								echo "</td></tr>\n";
							}

							?>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<?php if($leases == 0): ?>
<b>No leases file found. Is the DHCP server active.</b>
<?php endif; ?>
</div>
</body>
</html>
