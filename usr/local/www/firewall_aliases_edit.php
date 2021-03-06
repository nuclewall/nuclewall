<?php
/* $Id$ */
/*
	firewall_aliases_edit.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.

	Copyright (C) 2004 Scott Ullrich
	Copyright (C) 2009 Ermal Luçi
	Copyright (C) 2010 Jim Pingle
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
setlocale(LC_ALL, 'tr_TR.UTF-8');
$reserved_keywords = array("all", "pass", "block", "out", "queue", "max", "min", "pptp", "pppoe", "L2TP", "OpenVPN", "IPsec");

require('guiconfig.inc');
require_once('functions.inc');
require_once('filter.inc');
require_once('shaper.inc');

$pgtitle = array('GÜVENLİK DUVARI', 'TAKMA ADLAR' ,'DÜZENLE');

$reserved_ifs = get_configured_interface_list(false, true);
$reserved_keywords = array_merge($reserved_keywords, $reserved_ifs);

if (!is_array($config['aliases']['alias']))
	$config['aliases']['alias'] = array();
$a_aliases = &$config['aliases']['alias'];

if($_POST)
	$origname = $_POST['origname'];

function alias_same_type($name, $type) {
	global $config;

	foreach ($config['aliases']['alias'] as $alias) {
		if ($name == $alias['name']) {
			if (in_array($type, array("host", "network")) &&
				in_array($alias['type'], array("host", "network")))
				return true;
			if ($type  == $alias['type'])
				return true;
			else
				return false;
		}
	}
	return true;
}

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_aliases[$id]) {
	$original_alias_name = $a_aliases[$id]['name'];
	$pconfig['name'] = $a_aliases[$id]['name'];
	$pconfig['detail'] = $a_aliases[$id]['detail'];
	$pconfig['address'] = $a_aliases[$id]['address'];
	$pconfig['type'] = $a_aliases[$id]['type'];
	$pconfig['descr'] = html_entity_decode(base64_decode($a_aliases[$id]['descr']));

	$iflist = get_configured_interface_with_descr(false, true);
	foreach ($iflist as $if => $ifdesc)
		if($ifdesc == $pconfig['descr'])
			$input_errors[] = sprintf("%s adında bir ağ arayüzü zaten mevcut.", $pconfig['descr']);

	if($a_aliases[$id]['type'] == "urltable") {
		$pconfig['address'] = $a_aliases[$id]['url'];
		$pconfig['updatefreq'] = $a_aliases[$id]['updatefreq'];
	}
	if($a_aliases[$id]['aliasurl'] <> "") {
		$pconfig['type'] = "url";
		if(is_array($a_aliases[$id]['aliasurl'])) {
			$isfirst = 0;
			$pconfig['address'] = "";
			foreach($a_aliases[$id]['aliasurl'] as $aa) {
				if($isfirst == 1)
					$pconfig['address'] .= " ";
				$isfirst = 1;
				$pconfig['address'] .= $aa;
			}
		} else {
			$pconfig['address'] = $a_aliases[$id]['aliasurl'];
		}
	}
}

if ($_POST) {
	unset($input_errors);

	$reqdfields = explode(" ", "name");
	$reqdfieldsn = array("İsim");

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	$x = is_validaliasname($_POST['name']);
	if (!isset($x)) {
		$input_errors[] = "Ayrılmış bir isim takma ad olarak kullanılamaz.";
	} else if ($_POST['type'] == "port" && (getservbyname($_POST['name'], "tcp") || getservbyname($_POST['name'], "udp"))) {
		$input_errors[] = "Ayrılmış bir isim takma ad olarak kullanılamaz.";
	} else {
		if (is_validaliasname($_POST['name']) == false)
			$input_errors[] = "Takma adlar 32 karakterden az olmalı ve \"a-z, A-Z, 0-9, _\" karakterlerinden oluşmalıdır.";
	}
	if (empty($a_aliases[$id])) {
		foreach ($a_aliases as $alias) {
			if ($alias['name'] == $_POST['name']) {
				$input_errors[] = "Aynı isimde bir takma ad zaten mevcut.";
				break;
			}
		}
	}

	foreach($reserved_keywords as $rk)
		if($rk == $_POST['name'])
			$input_errors[] = sprintf("%s anahtar kelimesi takma ad olarak kullanılamaz.", $rk);

	foreach($config['interfaces'] as $interface) {
		if($interface['descr'] == $_POST['name']) {
			$input_errors[] = "Aynı isimde bir ağ arayüzü zaten mevcut.";
			break;
		}
	}

	$alias = array();
	$address = array();
	$final_address_details = array();
	$alias['name'] = $_POST['name'];

	if ($_POST['type'] == "urltable") {
		$address = "";
		$isfirst = 0;

		if ($_POST['address0']) {

			$isfirst = 0;
			$address = "";
			$alias['url'] = $_POST['address0'];
			$alias['updatefreq'] = $_POST['address_subnet0'] ? $_POST['address_subnet0'] : 7;
			if (!is_URL($alias['url']) || empty($alias['url'])) {
				$input_errors[] = "Geçerli bir URL girmelisiniz.";
				$dont_update = true;
			} elseif (! process_alias_urltable($alias['name'], $alias['url'], 0, true)) {
				$input_errors[] = "Kullanılabilir bir URL tablosu bulunamadı.";
				$dont_update = true;
			}
		}
	} elseif($_POST['type'] == "url") {
		$isfirst = 0;
		$address_count = 2;

		for($x=0; isset($_POST['address'. $x]); $x++) {
			if($_POST['address' . $x]) {
				$isfirst = 0;
				$temp_filename = tempnam("{$g['tmp_path']}/", "alias_import");
				unlink($temp_filename);
				$fda = fopen("{$g['tmp_path']}/tmpfetch","w");
				fwrite($fda, "/usr/bin/fetch -q -o \"{$temp_filename}/aliases\" \"" . $_POST['address' . $x] . "\"");
				fclose($fda);
				mwexec("/bin/mkdir -p {$temp_filename}");
				mwexec("/usr/bin/fetch -q -o \"{$temp_filename}/aliases\" \"" . $_POST['address' . $x] . "\"");
				if(stristr($_POST['address' . $x], ".tgz"))
					process_alias_tgz($temp_filename);
				if(file_exists("{$temp_filename}/aliases")) {
					$file_contents = file_get_contents("{$temp_filename}/aliases");
					$file_contents = str_replace("#", "\n#", $file_contents);
					$file_contents_split = split("\n", $file_contents);
					foreach($file_contents_split as $fc) {
						if ($address_count >= 3000)
							break;
						$tmp = trim($fc);
						if(stristr($fc, "#")) {
							$tmp_split = split("#", $tmp);
							$tmp = trim($tmp_split[0]);
						}
						$tmp = trim($tmp);
						if(!empty($tmp) && (is_ipaddr($tmp) || is_subnet($tmp))) {
							$address[] = $tmp;
							$isfirst = 1;
							$address_count++;
						}
					}
					if($isfirst == 0) {
						$input_errors[] = "Geçerli bir URL belirtmelisiniz. Kullanılabilir bir veri bulunamadı.";
						$dont_update = true;
						break;
					}
					$alias['aliasurl'][] = $_POST['address' . $x];
					mwexec("/bin/rm -rf {$temp_filename}");
				} else {
					$input_errors[] = "Geçerli bir URL belirtmelisiniz.";
					$dont_update = true;
					break;
				}
			}
		}
	} else {
		$wrongaliases = "";
		for($x=0; $x<4999; $x++) {
			if($_POST["address{$x}"] <> "") {
				if (is_alias($_POST["address{$x}"])) {
					if (!alias_same_type($_POST["address{$x}"], $_POST['type']))
						$wrongaliases .= " " . $_POST["address{$x}"];
				} else if ($_POST['type'] == "port") {
					if (!is_port($_POST["address{$x}"]))
						$input_errors[] = $_POST["address{$x}"] . ' geçerli bir port veya takma ad değil.';
				} else if ($_POST['type'] == "host" || $_POST['type'] == "network") {
					if (!is_ipaddr($_POST["address{$x}"])
					 && !is_hostname($_POST["address{$x}"])
					 && !is_iprange($_POST["address{$x}"]))
						$input_errors[] = sprintf('%1$s geçerli bir %2$s takma ad değil.', $_POST["address{$x}"], $_POST['type']);
				}
				if (is_iprange($_POST["address{$x}"])) {
					list($startip, $endip) = explode('-', $_POST["address{$x}"]);
					$rangesubnets = ip_range_to_subnet_array($startip, $endip);
					$address = array_merge($address, $rangesubnets);
				} else {
					$tmpaddress = $_POST["address{$x}"];
					if(is_ipaddr($_POST["address{$x}"]) && $_POST["address_subnet{$x}"] <> "")
						$tmpaddress .= "/" . $_POST["address_subnet{$x}"];
					$address[] = $tmpaddress;
				}
				if ($_POST["detail{$x}"] <> "")
					$final_address_details[] = base64_encode($_POST["detail{$x}"]);
				else
					$final_address_details[] = base64_encode(sprintf("%s tarihinde eklendi.", strftime("%T - %d.%m.%Y", time())));
			}
		}
		if ($wrongaliases <> "")
			$input_errors[] = sprintf('%s takma adları aynı türde olmadıklarından dolayı birleştirilemedi.', $wrongaliases);
	}

	pfSense_handle_custom_code("/usr/local/pkg/firewall_aliases_edit/input_validation");

	if (!$input_errors) {
		$alias['address'] = is_array($address) ? implode(" ", $address) : $address;
		$alias['descr'] = base64_encode($_POST['descr']);
		$alias['type'] = $_POST['type'];
		$alias['detail'] = implode("||", $final_address_details);

		if ($_POST['name'] <> $_POST['origname']) {
			update_alias_names_upon_change(array('filter', 'rule'), array('source', 'address'), $_POST['name'], $origname);
			update_alias_names_upon_change(array('filter', 'rule'), array('destination', 'address'), $_POST['name'], $origname);
			update_alias_names_upon_change(array('filter', 'rule'), array('source', 'port'), $_POST['name'], $origname);
			update_alias_names_upon_change(array('filter', 'rule'), array('destination', 'port'), $_POST['name'], $origname);
			// NAT Rules
			update_alias_names_upon_change(array('nat', 'rule'), array('source', 'address'), $_POST['name'], $origname);
			update_alias_names_upon_change(array('nat', 'rule'), array('source', 'port'), $_POST['name'], $origname);
			update_alias_names_upon_change(array('nat', 'rule'), array('destination', 'address'), $_POST['name'], $origname);
			update_alias_names_upon_change(array('nat', 'rule'), array('destination', 'port'), $_POST['name'], $origname);
			update_alias_names_upon_change(array('nat', 'rule'), array('target'), $_POST['name'], $origname);
			update_alias_names_upon_change(array('nat', 'rule'), array('local-port'), $_POST['name'], $origname);
			// NAT 1:1 Rules
			update_alias_names_upon_change(array('nat', 'onetoone'), array('destination', 'address'), $_POST['name'], $origname);
			// NAT Outbound Rules
			update_alias_names_upon_change(array('nat', 'advancedoutbound', 'rule'), array('source', 'network'), $_POST['name'], $origname);
			update_alias_names_upon_change(array('nat', 'advancedoutbound', 'rule'), array('sourceport'), $_POST['name'], $origname);
			update_alias_names_upon_change(array('nat', 'advancedoutbound', 'rule'), array('destination', 'address'), $_POST['name'], $origname);
			update_alias_names_upon_change(array('nat', 'advancedoutbound', 'rule'), array('dstport'), $_POST['name'], $origname);
			update_alias_names_upon_change(array('nat', 'advancedoutbound', 'rule'), array('target'), $_POST['name'], $origname);
			// Alias in an alias
			update_alias_names_upon_change(array('aliases', 'alias'), array('address'), $_POST['name'], $origname);
		}

		pfSense_handle_custom_code("/usr/local/pkg/firewall_aliases_edit/pre_write_config");

		if (isset($id) && $a_aliases[$id]) {
			if ($a_aliases[$id]['name'] <> $alias['name']) {
				foreach ($a_aliases as $aliasid => $aliasd) {
					if ($aliasd['address'] <> "") {
						$tmpdirty = false;
						$tmpaddr = explode(" ", $aliasd['address']);
						foreach ($tmpaddr as $tmpidx => $tmpalias) {
							if ($tmpalias == $a_aliases[$id]['name']) {
								$tmpaddr[$tmpidx] = $alias['name'];
								$tmpdirty = true;
							}
						}
						if ($tmpdirty == true)
							$a_aliases[$aliasid]['address'] = implode(" ", $tmpaddr);
					}
				}
			}
			$a_aliases[$id] = $alias;
		} else
			$a_aliases[] = $alias;

		mark_subsystem_dirty('aliases');

		$a_aliases = msort($a_aliases, "name");

		write_config("Bir Takma Ad yapilandirildi");

		header("Location: firewall_aliases.php");
		exit;
	}
	else
	{
		$pconfig['name'] = $_POST['name'];
		$pconfig['descr'] = $_POST['descr'];
		$pconfig['address'] = implode(" ", $address);
		$pconfig['type'] = $_POST['type'];
		$pconfig['detail'] = implode("||", $final_address_details);
	}
}

include('head.inc');

$jscriptstr = <<<EOD

<script type="text/javascript">

var objAlias = new Array(4999);
function typesel_change() {
	switch (document.iform.type.selectedIndex) {
		case 0:	/* host */
			var cmd;

			newrows = totalrows;
			for(i=0; i<newrows; i++) {
				comd = 'document.iform.address_subnet' + i + '.disabled = 1;';
				eval(comd);
				comd = 'document.iform.address_subnet' + i + '.value = "";';
				eval(comd);
			}
			break;
		case 1:	/* network */
			var cmd;

			newrows = totalrows;
			for(i=0; i<newrows; i++) {
				comd = 'document.iform.address_subnet' + i + '.disabled = 0;';
				eval(comd);
			}
			break;
		case 2:	/* port */
			var cmd;

			newrows = totalrows;
			for(i=0; i<newrows; i++) {
				comd = 'document.iform.address_subnet' + i + '.disabled = 1;';
				eval(comd);
				comd = 'document.iform.address_subnet' + i + '.value = "32";';
				eval(comd);
			}
			break;
		case 3:	/* url */
			var cmd;
			newrows = totalrows;
			for(i=0; i<newrows; i++) {
				comd = 'document.iform.address_subnet' + i + '.disabled = 1;';
				eval(comd);
			}
			break;

		case 4:	/* urltable */
			var cmd;
			newrows = totalrows;
			for(i=0; i<newrows; i++) {
				comd = 'document.iform.address_subnet' + i + '.disabled = 0;';
				eval(comd);
			}
			break;
	}
}

function add_alias_control() {
	var name = "address" + (totalrows - 1);
	obj = document.getElementById(name);
	obj.setAttribute('autocomplete', 'off');
	objAlias[totalrows - 1] = new AutoSuggestControl(obj, new StateSuggestions(addressarray));
}
EOD;

$network_str = "Ağ";
$networks_str = "Ağlar";
$cidr_str = "CIDR";
$description_str = "Açıklama";
$hosts_str = "IP Adresi";
$ip_str = "IP";
$ports_str = "Portlar";
$port_str = "Port";
$url_str = "URL";
$urltable_str = "URL Tablosu";
$update_freq_str = "Güncelleme aralığı";

$networks_help = "Ağlar CIDR formatında belirtilir. Her girdi için gerekli CIDR'i seçin. Tek bir IP için /32, 255.255.255.0 alt ağ maskesi için /24 gibi. Ayrıca 192.168.1.1-192.168.1.254 gibi bir IP aralığı da girebilirsiniz.";
$hosts_help = "İstediğiniz kadar IP adresi veya sunucu adı girebilirsiniz. Sunucu adları tam tanımlanmış alan adı (FQDN) şeklinde girilmelidir. DNS çözümlemesinden birden fazla IP adresi dönerse, hepsi kullanılır.";
$ports_help = "İstediğiniz kadar port girebilirsiniz. 80:88 gibi port aralıkları da girebilirsiniz.";
$url_help = "İstediğiniz kadar düz metin olarak IP adresi listesi içeren URL girebilirsiniz. Girdiğiniz URL'deki dosya indirilecek ve içindeki IP adresleri takma ad olarak eklenecektir. Girdiğiniz URL'deki IP adresi sayısı 3000'i geçmemelidir.";
$urltable_help = "Çok sayıda IP adresi içeren tek bir URL girebilirsiniz. Girdiğiniz URL 30.000'e kadar IP adresi içerebilir.";

$jscriptstr .= <<<EOD

function update_box_type() {
	var indexNum = document.forms[0].type.selectedIndex;
	var selected = document.forms[0].type.options[indexNum].text;
	if(selected == '{$network_str}') {
		document.getElementById ("addressnetworkport").firstChild.data = "{$networks_str}";
		document.getElementById ("onecolumn").firstChild.data = "{$network_str}";
		document.getElementById ("twocolumn").firstChild.data = "{$cidr_str}";
		document.getElementById ("threecolumn").firstChild.data = "{$description_str}";
		document.getElementById ("itemhelp").firstChild.data = "{$networks_help}";
		document.getElementById ("addrowbutton").style.display = 'block';
	} else if(selected == '{$hosts_str}') {
		document.getElementById ("addressnetworkport").firstChild.data = "{$hosts_str}";
		document.getElementById ("onecolumn").firstChild.data = "{$ip_str}";
		document.getElementById ("twocolumn").firstChild.data = "";
		document.getElementById ("threecolumn").firstChild.data = "{$description_str}";
		document.getElementById ("itemhelp").firstChild.data = "{$hosts_help}";
		document.getElementById ("addrowbutton").style.display = 'block';
	} else if(selected == '{$port_str}') {
		document.getElementById ("addressnetworkport").firstChild.data = "{$ports_str}";
		document.getElementById ("onecolumn").firstChild.data = "{$port_str}";
		document.getElementById ("twocolumn").firstChild.data = "";
		document.getElementById ("threecolumn").firstChild.data = "{$description_str}";
		document.getElementById ("itemhelp").firstChild.data = "{$ports_help}";
		document.getElementById ("addrowbutton").style.display = 'block';
	} else if(selected == '{$url_str}') {
		document.getElementById ("addressnetworkport").firstChild.data = "{$url_str}";
		document.getElementById ("onecolumn").firstChild.data = "{$url_str}";
		document.getElementById ("twocolumn").firstChild.data = "";
		document.getElementById ("threecolumn").firstChild.data = "{$description_str}";
		document.getElementById ("itemhelp").firstChild.data = "{$url_help}";
		document.getElementById ("addrowbutton").style.display = 'block';
	} else if(selected == '{$urltable_str}') {
		if ((typeof(totalrows) == "undefined") || (totalrows < 1)) {
			typesel_change();
			add_alias_control(this);
		}
		document.getElementById ("addressnetworkport").firstChild.data = "{$url_str}";
		document.getElementById ("onecolumn").firstChild.data = "{$url_str}";
		document.getElementById ("twocolumn").firstChild.data = "{$update_freq_str}";
		document.getElementById ("threecolumn").firstChild.data = "Açıklama";

		document.getElementById ("itemhelp").firstChild.data = "{$urltable_help}";
		document.getElementById ("addrowbutton").style.display = 'none';
	}
}
</script>

EOD;

?>

</head>
<body onload="<?= $jsevents["body"]["onload"] ?>">
<?php
	include('fbegin.inc');
	echo $jscriptstr;
?>

<script type="text/javascript" src="/javascript/row_helper.js">
</script>
<script type="text/javascript" src="/javascript/autosuggest.js">
</script>
<script type="text/javascript" src="/javascript/suggestions.js">
</script>

<input type='hidden' name='address_type' value='textbox' />
<input type='hidden' name='address_subnet_type' value='select' />

<script type="text/javascript">
	rowname[0] = "address";
	rowtype[0] = "textbox";
	rowsize[0] = "30";

	rowname[1] = "address_subnet";
	rowtype[1] = "select";
	rowsize[1] = "1";

	rowname[2] = "detail";
	rowtype[2] = "textbox";
	rowsize[2] = "50";
</script>

<?php pfSense_handle_custom_code("/usr/local/pkg/firewall_aliases_edit/pre_input_errors"); ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<div id="inputerrors"></div>

<form action="firewall_aliases_edit.php" method="post" name="iform" id="iform">
<table cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td colspan="2" valign="top" class="listtopic">TAKMA AD DÜZENLE</td>
				</tr>
				<tr>
					<td class="vncell" valign="top">İsim</td>
					<td class="vtable">
						<input name="origname" type="hidden" id="origname" value="<?=htmlspecialchars($pconfig['name']);?>" />
						<input name="name" type="text" id="name" value="<?=htmlspecialchars($pconfig['name']);?>" />
						<?php if (isset($id) && $a_aliases[$id]): ?>
						<input name="id" type="hidden" value="<?=htmlspecialchars($id);?>" />
						<?php endif; ?>
						<br>
						<span class="vexpl">Takma adlar sadece a-z, A-Z, 0-9 ve _ karakterlerinden oluşabilir.</span>
					</td>
				</tr>
				<?php pfSense_handle_custom_code("/usr/local/pkg/firewall_aliases_edit/after_first_tr"); ?>
				<tr>
					<td valign="top" class="vncell">Açıklama</td>
					<td class="vtable">
						<input name="descr" type="text" id="descr" value="<?=htmlspecialchars($pconfig['descr']);?>" />
						<br>
						<span class="vexpl">Takma ad için bir açıklama girebilirsiniz.</span>
					</td>
				</tr>
				<tr>
					<td class="vncell" valign="top">Tür</td>
					<td class="vtable">
						<select name="type" id="type" onchange="update_box_type(); typesel_change();">
							<option value="host" <?php if ($pconfig['type'] == "host") echo "selected"; ?>>IP Adresi</option>
							<option value="network" <?php if ($pconfig['type'] == "network") echo "selected"; ?>>Ağ</option>
							<option value="port" <?php if ($pconfig['type'] == "port") echo "selected"; ?>>Port</option>
							<option value="url" <?php if ($pconfig['type'] == "url") echo "selected"; ?>>URL</option>
							<option value="urltable" <?php if ($pconfig['type'] == "urltable") echo "selected"; ?>>URL Tablosu</option>
						</select>
					</td>
				</tr>
				<tr>
					<td class="vncell" valign="top">
						<div id="addressnetworkport">IP Adresleri</div>
					</td>
					<td class="vtable">
						<table id="maintable">
							<tbody>
								<tr>
									<td colspan="4">
										<div style="font-size:13px;" id="itemhelp" class="alert alert-warning">
										</div>
									</td>
								</tr>
								<tr>
									<td><div id="onecolumn">.</div></td>
									<td><div id="twocolumn">.</div></td>
									<td><div id="threecolumn">.</div></td>
								</tr>

								<?php
								$counter = 0;
								$address = $pconfig['address'];
								if ($address <> "") {
									$item = explode(" ", $address);
									$item3 = explode("||", $pconfig['detail']);
									foreach($item as $ww) {
										$address = $item[$counter];
										$address_subnet = "";
										$item2 = explode("/", $address);
										foreach($item2 as $current) {
											if($item2[1] <> "") {
												$address = $item2[0];
												$address_subnet = $item2[1];
											}

										}
										$item4 = base64_decode($item3[$counter]);
										$tracker = $counter;
								?>

								<tr>
									<td>
										<input autocomplete="off" name="address<?php echo $tracker; ?>" type="text" id="address<?php echo $tracker; ?>" size="30" value="<?=htmlspecialchars($address);?>" />
									</td>
									<td>
										<select name="address_subnet<?php echo $tracker; ?>" id="address_subnet<?php echo $tracker; ?>">
											<option></option>
											<?php for ($i = 32; $i >= 1; $i--): ?>
											<option value="<?=$i;?>" <?php if (($i == $address_subnet) || ($i == $pconfig['updatefreq'])) echo "selected"; ?>><?=$i;?></option>
											<?php endfor; ?>
										</select>
									</td>
									<td>
										<input name="detail<?php echo $tracker; ?>" type="text" id="detail<?php echo $tracker; ?>" value="<?=$item4;?>" />
									</td>
									<td>
										<a title="Sil" onclick="removeRow(this); return false;" href="#">
											<i class="icon-trash"></i>
										</a>
									</td>
								</tr>
									<?php
										$counter++;
											}
										}
									?>
							</tbody>
						</table>
						<div id="addrowbutton">
							<a onclick="javascript:addRowTo('maintable'); typesel_change(); add_alias_control(this); return false;" href="#">
								<i title="Ekle" class="icon-plus"></i>
							</a>
						</div>
					</td>
				</tr>
				<tr>
					<td class="vncell"></td>
					<td class="vtable">
						<input id="submit" name="submit" type="submit" class="btn btn-inverse" value="Kaydet" />
						<a href="firewall_aliases.php">
							<input id="cancelbutton" name="cancelbutton" type="button" class="btn btn-default" value="İptal"/>
						</a>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>

<script type="text/javascript">
	field_counter_js = 3;
	rows = 1;
	totalrows = <?php echo $counter; ?>;
	loaded = <?php echo $counter; ?>;
	typesel_change();
	update_box_type();

<?php
	$isfirst = 0;
	$aliases = "";
	$addrisfirst = 0;
	$aliasesaddr = "";
	if(isset($config['aliases']['alias']) && is_array($config['aliases']['alias']))
			foreach($config['aliases']['alias'] as $alias_name) {
		if ($pconfig['name'] <> "" && $pconfig['name'] == $alias_name['name'])
			continue;
		if($addrisfirst == 1) $aliasesaddr .= ",";
		$aliasesaddr .= "'" . $alias_name['name'] . "'";
		$addrisfirst = 1;
			}
?>

var addressarray=new Array(<?php echo $aliasesaddr; ?>);

function createAutoSuggest() {
<?php
	for ($jv = 0; $jv < $counter; $jv++)
		echo "objAlias[{$jv}] = new AutoSuggestControl(document.getElementById(\"address{$jv}\"), new StateSuggestions(addressarray));\n";
?>
}

setTimeout("createAutoSuggest();", 500);

</script>

</div>
</body>
</html>
