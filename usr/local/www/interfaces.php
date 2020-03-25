<?php
/* $Id$ */
/*
	interfaces.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.

	Copyright (C) 2004-2008 Scott Ullrich
	Copyright (C) 2006 Daniel S. Haischt.
	Copyright (C) 2008-2010 Ermal Luçi
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

require_once('guiconfig.inc');
require_once('functions.inc');
require_once('captiveportal.inc');
require_once('filter.inc');
require_once('shaper.inc');
require_once('xmlparse_attr.inc');

$ifdescrs = get_configured_interface_with_descr(false, true);

$if = "wan";
if ($_REQUEST['if'])
	$if = $_REQUEST['if'];

if (empty($ifdescrs[$if])) {
	Header("Location: interfaces.php");
	exit;
}

function remove_bad_chars($string) {
	return preg_replace('/[^a-z_0-9]/i','',$string);
}

if (!is_array($config['gateways']['gateway_item']))
	$config['gateways']['gateway_item'] = array();
$a_gateways = &$config['gateways']['gateway_item'];

$wancfg = &$config['interfaces'][$if];
// Populate page descr if it does not exist.
if ($if == "wan" && !$wancfg['descr'])
	$wancfg['descr'] = "WAN";
else if ($if == "lan" && !$wancfg['descr'])
	$wancfg['descr'] = "LAN";

$pconfig['dhcphostname'] = $wancfg['dhcphostname'];
$pconfig['alias-address'] = $wancfg['alias-address'];
$pconfig['alias-subnet'] = $wancfg['alias-subnet'];
$pconfig['dhcp_plus'] = isset($wancfg['dhcp_plus']);
$pconfig['descr'] = remove_bad_chars($wancfg['descr']);
$pconfig['enable'] = isset($wancfg['enable']);

if (is_array($config['aliases']['alias'])) {
	foreach($config['aliases']['alias'] as $alias) {
		if($alias['name'] == $wancfg['descr']) {
			$input_errors[] = sprintf("%s isminde bir takma ad zaten mevcut.", $wancfg['descr']);
		}
	}
}

switch($wancfg['ipaddr']) {
	case "dhcp":
		$pconfig['type'] = "dhcp";
		break;
	default:
		if(is_ipaddr($wancfg['ipaddr']))
		{
			$pconfig['type'] = "static";
			$pconfig['ipaddr'] = $wancfg['ipaddr'];
			$pconfig['subnet'] = $wancfg['subnet'];
			$pconfig['gateway'] = $wancfg['gateway'];
		}
		else
			$pconfig['type'] = "none";
		break;
}

$pconfig['blockpriv'] = isset($wancfg['blockpriv']);
$pconfig['blockbogons'] = isset($wancfg['blockbogons']);
$pconfig['mtu'] = $wancfg['mtu'];
$pconfig['mss'] = $wancfg['mss'];

if ($_POST['apply']) {
	unset($input_errors);
	if (!is_subsystem_dirty('interfaces'))
		$intput_errors[] = "Değişiklikleri zaten uyguladınız.";
	else {
		unlink_if_exists("{$g['tmp_path']}/config.cache");
		clear_subsystem_dirty('interfaces');

		if (file_exists("{$g['tmp_path']}/.interfaces.apply")) {
			$toapplylist = unserialize(file_get_contents("{$g['tmp_path']}/.interfaces.apply"));
			foreach ($toapplylist as $ifapply) {
				if (isset($config['interfaces'][$ifapply]['enable']))
					interface_reconfigure($ifapply, true);
				else
					interface_bring_down($ifapply);
			}
		}
		/* sync filter configuration */
		setup_gateways_monitor();

		clear_subsystem_dirty('staticroutes');

		filter_configure();

	}
	@unlink("{$g['tmp_path']}/.interfaces.apply");
	header("Location: interfaces.php?if={$if}");
	exit;
}
else if ($_POST && $_POST['enable'] != "yes")
{
	unset($wancfg['enable']);
	write_config();
	mark_subsystem_dirty('interfaces');
	if (file_exists("{$g['tmp_path']}/.interfaces.apply"))
		$toapplylist = unserialize(file_get_contents("{$g['tmp_path']}/.interfaces.apply"));
	else
		$toapplylist = array();
	$toapplylist[$if] = $if;
	file_put_contents("{$g['tmp_path']}/.interfaces.apply", serialize($toapplylist));
	header("Location: interfaces.php?if={$if}");
	exit;
}
else if ($_POST)
{
	unset($input_errors);
	$pconfig = $_POST;
	$_POST['descr'] = remove_bad_chars($_POST['descr']);

	if (empty($_POST['pppoe-reset-type']))
	{
		unset($_POST['pppoe_pr_type']);
		unset($_POST['pppoe_resethour']);
		unset($_POST['pppoe_resetminute']);
		unset($_POST['pppoe_resetdate']);
		unset($_POST['pppoe_pr_preset_val']);
	}

	foreach ($ifdescrs as $ifent => $ifdescr)
	{
		if ($if != $ifent && $ifdescr == $_POST['descr'])
		{
			$input_errors[] = "Belirtilen isimde bir ağ arayüzü zaten mevcut";
			break;
		}
	}

	if (isset($config['dhcpd']) && isset($config['dhcpd'][$if]['enable']) && $_POST['type'] != "static")
		$input_errors[] = "Bu ağ arayüzünde bir DHCP sunucu mevcut ve DHCP hizmeti sadece sabit IP ile kullanılabilir. Arayüz ayarlarını değiştirmek için önce DHCP sunucu ayarlarını değiştirmelisiniz.";

	switch(strtolower($_POST['type']))
	{
		case "static":
			$reqdfields = explode(" ", "ipaddr subnet gateway");
			$reqdfieldsn = array("IP adresi", "Alt ağ bit sayısı", "Ağ geçidi");
			do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
			break;
		case "dhcp":
			break;
	}

	if (($_POST['ipaddr'] && !is_ipaddr($_POST['ipaddr'])))
		$input_errors[] = "Geçerli bir IP adresi belirtilmelidir.";
	if (($_POST['subnet'] && !is_numeric($_POST['subnet'])))
		$input_errors[] = "Geçerli bir alt ağ bit sayısı belirtilmelidir.";
	if (($_POST['alias-address'] && !is_ipaddr($_POST['alias-address'])))
		$input_errors[] = "Geçerli bir yedek IP adresi belirtilmelidir.";
	if (($_POST['alias-subnet'] && !is_numeric($_POST['alias-subnet'])))
		$input_errors[] = "Geçerli bir yede IP adresi alt ağ bit sayısı belirtilmelidir.";
	if ($_POST['gateway'] != "none")
	{
		$match = false;
		foreach($a_gateways as $gateway)
		{
			if(in_array($_POST['gateway'], $gateway))
			{
				$match = true;
			}
		}

		if(!$match)
		{
			$input_errors[] = "Geçerli bir ağ geçidi belirtilmelidir.";
		}
	}

	if ($_POST['mtu'] && ($_POST['mtu'] < 576))
		$input_errors[] = "MTU 576 byte'tan büyük olmalıdır.";


	if (!$input_errors)
	{
		if ($wancfg['ipaddr'] != $_POST['type'])
		{
			 if ($wancfg['ipaddr'] == "dhcp")
			 {
				$pid = find_dhclient_process($wancfg['if']);
				if($pid)
					posix_kill($pid, SIGTERM);
			}
		}

		$wancfg['descr'] = remove_bad_chars($_POST['descr']);
		$wancfg['enable'] =  $_POST['enable']  == "yes" ? true : false;

		if(!empty($a_gateways))
		{
			$gateway_item = array();
			$skip = false;

			foreach($a_gateways as $item)
			{
				if(($item['interface'] == "$if") && ($item['gateway'] == "dynamic"))
				{
					$skip = true;
				}
			}

			if($skip == false)
			{
				$gateway_item['gateway'] = "dynamic";
				$gateway_item['descr'] = base64_encode("Dinamik" . $if . "Ağ Geçidi");
				$gateway_item['name'] = "GW_" . strtoupper($if);
				$gateway_item['interface'] = "{$if}";
			}
			else
				unset($gateway_item);
		}

		switch($_POST['type'])
		{
			case "static":
				$wancfg['ipaddr'] = $_POST['ipaddr'];
				$wancfg['subnet'] = $_POST['subnet'];

				if ($_POST['gateway'] != "none")
				{
					$wancfg['gateway'] = $_POST['gateway'];
				}

				break;
			case "dhcp":
				$wancfg['ipaddr'] = "dhcp";
				$wancfg['dhcphostname'] = $_POST['dhcphostname'];
				$wancfg['alias-address'] = $_POST['alias-address'];
				$wancfg['alias-subnet'] = $_POST['alias-subnet'];
				$wancfg['dhcp_plus'] = $_POST['dhcp_plus'] == "yes" ? true : false;

				if($gateway_item)
				{
					$a_gateways[] = $gateway_item;
				}
				break;

			case "none":
				break;
		}

		if($_POST['blockpriv'] == "yes")
			$wancfg['blockpriv'] = true;
		else
			unset($wancfg['blockpriv']);

		if($_POST['blockbogons'] == "yes")
			$wancfg['blockbogons'] = true;
		else
			unset($wancfg['blockbogons']);

		if (empty($_POST['mtu']))
			unset($wancfg['mtu']);
		else
			$wancfg['mtu'] = $_POST['mtu'];

		if (empty($_POST['mss']))
			unset($wancfg['mss']);
		else
			$wancfg['mss'] = $_POST['mss'];

		if (empty($_POST['mediaopt']))
		{
			unset($wancfg['media']);
			unset($wancfg['mediaopt']);
		}
		else
		{
			$mediaopts = explode(' ', $_POST['mediaopt']);
			if ($mediaopts[0] != ''){ $wancfg['media'] = $mediaopts[0]; }
			if ($mediaopts[1] != ''){ $wancfg['mediaopt'] = $mediaopts[1]; }
			else { unset($wancfg['mediaopt']); }
		}

		write_config();

		if (file_exists("{$g['tmp_path']}/.interfaces.apply"))
			$toapplylist = unserialize(file_get_contents("{$g['tmp_path']}/.interfaces.apply"));
		else
			$toapplylist = array();
		$toapplylist[$if] = $if;
		file_put_contents("{$g['tmp_path']}/.interfaces.apply", serialize($toapplylist));

		mark_subsystem_dirty('interfaces');
		configure_cron();

		header("Location: interfaces.php?if={$if}");
		exit;
	}

}

$mediaopts_list = array();
$intrealname = $config['interfaces'][$if]['if'];
exec("/sbin/ifconfig -m $intrealname | grep \"media \"", $mediaopts);

foreach ($mediaopts as $mediaopt)
{
	preg_match("/media (.*)/", $mediaopt, $matches);

 	if (preg_match("/(.*) mediaopt (.*)/", $matches[1], $matches1))
 		array_push($mediaopts_list, $matches1[1] . " " . $matches1[2]);
	else
		array_push($mediaopts_list, $matches[1]);
}

$pgtitle = array('AĞ ARAYÜZLERİ', $pconfig['descr']);

include('head.inc');
$types = array("none" => "Hiçbiri", "static" => "Sabit", "dhcp" => "DHCP");

?>

<script type="text/javascript" src="/javascript/numericupdown/js/numericupdown.js"></script>
<link href="/javascript/numericupdown/css/numericupdown.css" rel="stylesheet" type="text/css" />
</head>
<body>
<?php include('fbegin.inc'); ?>

<form action="interfaces.php" method="post" name="iform" id="iform">
	<?php if ($input_errors) print_input_errors($input_errors); ?>
	<?php if (is_subsystem_dirty('interfaces')): ?>
	<?php print_info_box_np(sprintf("%s ayarları değiştirildi." ,$wancfg['descr']) . '<br>' .  'Değişikliklerin etkili olabilmesi için uygulayın.', true);?>
	<?php endif; ?>
	<?php if ($savemsg) print_info_box($savemsg); ?>

<table cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<table cellpadding="0" cellspacing="0">
							<tr>
								<td colspan="2" valign="top" class="listtopic">GENEL AYARLAR</td>
							</tr>
							<tr>
								<td valign="top" class="vncell">Aktif</td>
								<td class="vtable">
									<input name="enable" type="checkbox" value="yes" <?php if ($pconfig['enable'] == true) echo "checked"; ?>>
									Ağ arayüzünü aktifleştirmek için işaretleyin
								</td>
							</tr>
						</table>
						<table cellpadding="0" cellspacing="0">
							<tr>
								<td valign="top" class="vncell">İsim</td>
								<td class="vtable">
									<input name="descr" type="text" id="descr" value="<?=htmlspecialchars($pconfig['descr']);?>">
									<br>Ağ arayüzü için bir isim girin
								</td>
							</tr>
							<tr>
								<td valign="middle" class="vncell">Tür</td>
								<td class="vtable">
									<select name="type" id="type">
									<?php
										foreach ($types as $key => $opt) {
											echo "<option";
											if ($key == $pconfig['type'])
												echo " selected";
											echo " value=\"{$key}\" >" . htmlspecialchars($opt);
											echo "</option>";
										}
									?>
									</select>
								</td>
							</tr>

							<tr>
								<td valign="top" class="vncell">MTU</td>
								<td class="vtable">
									<input name="mtu" type="text" id="mtu" value="<?=htmlspecialchars($pconfig['mtu']);?>">
									<br>
									Boş bırakırsanız, ağ kartınızın varsayılan MTU değeri kullanılır.<br>
									Bu değer yaklaşık olarak 1500 byte'tır ve donanımdan donanıma değişebilir.
								</td>
							</tr>
							<tbody style="display: none;" name="static" id="static">
								<tr>
									<td colspan="2" class="listtopic">SABİT İP AYARLARI</td>
								</tr>
								<tr>
									<td valign="top" class="vncell">IP Adresi</td>
									<td class="vtable">
										<input name="ipaddr" type="text" id="ipaddr" value="<?=htmlspecialchars($pconfig['ipaddr']);?>">
										/
										<select name="subnet" id="subnet">
											<?php
											for ($i = 32; $i > 0; $i--) {
												if($i <> 31) {
													echo "<option value=\"{$i}\" ";
													if ($i == $pconfig['subnet']) echo "selected";
													echo ">" . $i . "</option>";
												}
											}
											?>
										</select>
									</td>
								</tr>
								<tr>
									<td valign="top" class="vncell">Ağ Geçitleri</td>
									<td class="vtable">
										<select name="gateway" class="formselect" id="gateway">
											<option value="none" selected>Hiçbiri</option>
												<?php
												if(count($a_gateways) > 0) {
													foreach ($a_gateways as $gateway) {
														if($gateway['interface'] == $if) {
												?>
														<option value="<?=$gateway['name'];?>" <?php if ($gateway['name'] == $pconfig['gateway']) echo "selected"; ?>>
															<?=htmlspecialchars($gateway['name']) . " - " . htmlspecialchars($gateway['gateway']);?>
														</option>
												<?php
														}
													}
												}
												?>
										</select>
									</td>
								</tr>
							</tbody>
							<tbody style="display: none;" name="dhcp" id="dhcp">
								<tr>
									<td colspan="2" valign="top" class="listtopic">DHCP İSTEMCİ AYARLARI</td>
								</tr>
								<tr>
									<td valign="top" class="vncell">Sunucu Adı</td>
									<td class="vtable">
										<input name="dhcphostname" type="text" id="dhcphostname" value="<?=htmlspecialchars($pconfig['dhcphostname']);?>">
										<br>
										Bazı internet servis sağlayıcıları bu veriyi DHCP istemcinin kimliğini onaylamak için kullanır.
									</td>
								</tr>
								<tr>
									<td valign="top" class="vncell">Yedek IP Adresi</td>
									<td class="vtable">
										<input name="alias-address" type="text" id="alias-address" value="<?=htmlspecialchars($pconfig['alias-address']);?>">
										<select name="alias-subnet" id="alias-subnet">
											<?php
											for ($i = 32; $i > 0; $i--)
											{
												if($i <> 31)
												{
													echo "<option value=\"{$i}\" ";
													if ($i == $pconfig['alias-subnet']) echo "selected";
													echo ">" . $i . "</option>";
												}
											}
											?>
										</select>
										<br>
										DHCP sunucunun verdiği IP adresi dışında, ağ kartınıza başka bir IP adresi daha girebilirsiniz.
										Not: Yanlış bir IP adresi girmek bağlantı sorunlarına yol açabilir.
									</td>
								</tr>
							</tbody>

							<tr>
								<td colspan="2" valign="top" class="listtopic">ÖZEL AĞLAR</td>
							</tr>
							<tr>
								<td valign="middle" class="vncell"></td>
								<td class="vtable">
									<a name="rfc1918"></a>
									<input name="blockpriv" type="checkbox" id="blockpriv" value="yes" <?php if ($pconfig['blockpriv']) echo "checked"; ?>>
									<strong>Özel ağları engelle</strong><br>
									İşaretlendiği zaman, RFC 1918 (10/8, 172.16/12, 192.168/16) tarafından özel ağlar için
									ayrılmış olan adresleri ve loopback (127.0.0.1) adreslerini engeller.
									NUCLEWALL'un web arayüzüne WAN IP adresinden ulaşmak istiyorsanız engellemeyi kaldırmalısınız.
								</td>
							</tr>
							<tr>
								<td valign="middle" class="vncell"></td>
								<td class="vtable">
									<input name="blockbogons" type="checkbox" id="blockbogons" value="yes" <?php if ($pconfig['blockbogons']) echo "checked"; ?>>
									<strong>Ayrılmış(Bogon) adresleri engelle</strong><br>
									IANA tarafından onaylanmış ve ayrılmış olan IP adreslerini engeller.
								</td>
							</tr>
							<tr>
								<td class="vncell"></td>
								<td class="vtable">
									<input id="save" name="Submit" type="submit" class="btn btn-inverse" value="Kaydet">
									<input id="cancel" type="button" class="btn btn-default" value="Iptal" onclick="history.back()">
									<input name="if" type="hidden" id="if" value="<?=$if;?>">
									<?php if ($wancfg['if'] == $a_ppps[$pppid]['if']) : ?>
									<input name="ppp_port" type="hidden" value="<?=htmlspecialchars($pconfig['port']);?>">
									<?php endif; ?>
									<input name="ptpid" type="hidden" value="<?=htmlspecialchars($pconfig['ptpid']);?>">
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>
<script type="text/javascript">
	var gatewayip;
	var name;
	function addOption(selectbox,text,value)
	{
		var optn = document.createElement("OPTION");
		optn.text = text;
		optn.value = value;
		selectbox.options.add(optn);
		selectbox.selectedIndex = (selectbox.options.length-1);
	}

	function report_failure()
	{
		alert("Sorry, we could not create your gateway at this time.");
		hide_add_gateway();
	}

	function save_callback(transport)
	{
		var response = transport.responseText;

		if(response)
		{
			document.getElementById("addgateway").style.display = 'none';
			hide_add_gateway();
			addOption($('gateway'), name, name);
			// Auto submit form?
			document.iform.submit();
		}
		else
			report_failure();
	}

	function table_bind()
	{
		var eth = jQuery("#type").val();

		if(eth == "static")
		{
			jQuery("#static").show();
			jQuery("#dhcp").hide();
		}

		else if(eth == "dhcp")
		{
			jQuery("#static").hide();
			jQuery("#dhcp").show();
		}

		else
		{
			jQuery("#static").hide();
			jQuery("#dhcp").hide();
		}
	}

	jQuery(document).ready(table_bind);

	jQuery("#type").change(table_bind);

</script>
</div>
</body>
</html>
