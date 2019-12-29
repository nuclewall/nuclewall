<?php
/* $Id$ */
/*
	services_dhcp_edit.php

	Copyright (C) 2013-2015 Ogün AÇIK
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

function staticmapcmp($a, $b) {
        return ipcmp($a['ipaddr'], $b['ipaddr']);
}

function staticmaps_sort($ifgui) {
        global $g, $config;

        usort($config['dhcpd'][$ifgui]['staticmap'], "staticmapcmp");
}

require_once('globals.inc');

if(!$g['services_dhcp_server_enable']) {
	Header("Location: /");
	exit;
}

require("guiconfig.inc");

$if = $_GET['if'];
if ($_POST['if'])
	$if = $_POST['if'];

if (!$if) {
	header("Location: services_dhcp.php");
	exit;
}

if (!is_array($config['dhcpd'][$if]['staticmap'])) {
	$config['dhcpd'][$if]['staticmap'] = array();
}

$a_maps = &$config['dhcpd'][$if]['staticmap'];
$ifcfgip = get_interface_ip($if);
$ifcfgsn = get_interface_subnet($if);
$ifcfgdescr = convert_friendly_interface_to_friendly_descr($if);

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_maps[$id]) {
        $pconfig['mac'] = $a_maps[$id]['mac'];
		$pconfig['hostname'] = $a_maps[$id]['hostname'];
        $pconfig['ipaddr'] = $a_maps[$id]['ipaddr'];
        $pconfig['descr'] = base64_decode($a_maps[$id]['descr']);
} else {
        $pconfig['mac'] = $_GET['mac'];
		$pconfig['hostname'] = $_GET['hostname'];
        $pconfig['descr'] = $_GET['descr'];
}

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "mac");
	$reqdfieldsn = array("MAC Adresi");

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	$_POST['mac'] = strtolower(str_replace("-", ":", $_POST['mac']));

	if ($_POST['hostname']) {
		preg_match("/\-\$/", $_POST['hostname'], $matches);
		if($matches)
			$input_errors[] = "Sunucu adı RFC952'ye göre '-' ile bitemez.";
		if (!is_hostname($_POST['hostname'])) {
			$input_errors[] = "Sunucu adı sadece A-Z, 0-9 ve '-' karakterlerini içerebilir.";
		} else {
			if (strpos($_POST['hostname'],'.')) {
				$input_errors[] = "Sunucu adı geçerli, fakat alan adı kısmını girmemelisiniz";
			}
		}
	}
	if (($_POST['ipaddr'] && !is_ipaddr($_POST['ipaddr']))) {
		$input_errors[] = "Geçerli bir IP adresi girilmelidir.";
	}
	if (($_POST['mac'] && !is_macaddr($_POST['mac']))) {
		$input_errors[] = "Geçerli bir MAC adresi girilmelidir.";
	}

	foreach ($a_maps as $mapent) {
		if (isset($id) && ($a_maps[$id]) && ($a_maps[$id] === $mapent))
			continue;

		if ((($mapent['hostname'] == $_POST['hostname']) && $mapent['hostname'])  || ($mapent['mac'] == $_POST['mac'])) {
			$input_errors[] = "Bu sunucu adı, IP adresi veya MAC adresi zaten kayıtlı.";
			break;
		}
	}


	if ($_POST['ipaddr']) {
		$dynsubnet_start = ip2ulong($config['dhcpd'][$if]['range']['from']);
		$dynsubnet_end = ip2ulong($config['dhcpd'][$if]['range']['to']);
		if ((ip2ulong($_POST['ipaddr']) >= $dynsubnet_start) &&
			(ip2ulong($_POST['ipaddr']) <= $dynsubnet_end)) {
			$input_errors[] = "Sabit IP adresi otomatik dağıtım aralığında olmamalıdır.";
		}

		$lansubnet_start = ip2ulong(long2ip32(ip2long($ifcfgip) & gen_subnet_mask_long($ifcfgsn)));
		$lansubnet_end = ip2ulong(long2ip32(ip2long($ifcfgip) | (~gen_subnet_mask_long($ifcfgsn))));
		if ((ip2ulong($_POST['ipaddr']) < $lansubnet_start) ||
			(ip2ulong($_POST['ipaddr']) > $lansubnet_end)) {
			$input_errors[] = sprintf("IP adresi %s ağında bulunmalıdır.",$ifcfgdescr);
		}
	}

	if (!$input_errors) {
		$mapent = array();
		$mapent['mac'] = $_POST['mac'];
		$mapent['ipaddr'] = $_POST['ipaddr'];
		$mapent['hostname'] = $_POST['hostname'];
		$mapent['descr'] = base64_encode($_POST['descr']);

		if (isset($id) && $a_maps[$id])
			$a_maps[$id] = $mapent;
		else
			$a_maps[] = $mapent;
		staticmaps_sort($if);

		write_config();

		if(isset($config['dhcpd'][$if]['enable'])) {
			mark_subsystem_dirty('staticmaps');
			if (isset($config['dnsmasq']['regdhcpstatic']))
				mark_subsystem_dirty('hosts');
		}

		header("Location: services_dhcp.php?if={$if}");
		exit;
	}
}

$pgtitle = array('SERVİSLER', 'DHCP', 'SABİT IP ADRESİ DÜZENLE');
$statusurl = "status_dhcp_leases.php";
$logurl = "diag_logs_dhcp.php";

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php if ($input_errors) print_input_errors($input_errors); ?>
<form action="services_dhcp_edit.php" method="post" name="iform" id="iform">
<table border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td colspan="2" valign="top" class="listtopic">SABİT IP ADRESİ DÜZENLE</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">MAC Adresi</td>
					<td class="vtable">
						<input name="mac" type="text" id="mac" value="<?=htmlspecialchars($pconfig['mac']);?>">
						<br>Sabit IP adresi verilecek istemcinin MAC adresini <em>xx:xx:xx:xx:xx:xx</em> biçiminde girin.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">IP Adresi</td>
					<td class="vtable">
						<input name="ipaddr" type="text" id="ipaddr" value="<?=htmlspecialchars($pconfig['ipaddr']);?>">
						<br>
						<b>NOT: </b>Bu alan boş bırakılırsa otomatik IP dağıtım aralığından rastgele bir IP adresi seçilecektir.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Sunucu Adı</td>
					<td class="vtable">
						<input name="hostname" type="text" id="hostname" value="<?=htmlspecialchars($pconfig['hostname']);?>">
						<br>Alan adı kısmı olmadan bir sunucu adı girin.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Açıklama</td>
					<td class="vtable">
						<input name="descr" type="text" id="descr" size="40" value="<?=htmlspecialchars($pconfig['descr']);?>">
						<br>İsteğe bağlı bir açıklama girebilirsiniz.
					</td>
				</tr>
				<tr>
					<td class="vncell"></td>
					<td class="vtable">
						<input name="Submit" type="submit" class="btn btn-inverse" value="Kaydet">
						<a class="btn" href="services_dhcp.php">İptal</a>
						<?php if (isset($id) && $a_maps[$id]): ?>
						<input name="id" type="hidden" value="<?=htmlspecialchars($id);?>">
						<?php endif; ?>
						<input name="if" type="hidden" value="<?=htmlspecialchars($if);?>">
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>
</div>
</body>
</html>
