<?php
/* $Id$ */
/*
	system_gateway_groups_edit.php

	Copyright (C) 2013-2015 Ogün AÇIK
	All rights reserved.

	Copyright (C) 2010 Seth Mos <seth.mos@dds.nl>.
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

if (!is_array($config['gateways']['gateway_group']))
	$config['gateways']['gateway_group'] = array();

$a_gateway_groups = &$config['gateways']['gateway_group'];
$a_gateways = return_gateways_array();

$categories = array('down' => "Member Down",
                'downloss' => "Packet Loss",
                'downlatency' => "High Latency",
                'downlosslatency' => "Packet Loss or High Latency");

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($_GET['dup'])) {
	$id = $_GET['dup'];
}

if (isset($id) && $a_gateway_groups[$id]) {
	$pconfig['name'] = $a_gateway_groups[$id]['name'];
	$pconfig['item'] = &$a_gateway_groups[$id]['item'];
	$pconfig['descr'] = base64_decode($a_gateway_groups[$id]['descr']);
	$pconfig['trigger'] = $a_gateway_groups[$id]['trigger'];
}

if (isset($_GET['dup']))
	unset($id);

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	$reqdfields = explode(" ", "name");
	$reqdfieldsn = explode(",", "Name");

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if (! isset($_POST['name'])) {
		$input_errors[] = 'Geçerli bir ağ geçidi grubu adı belirtilmelidir.';
	}
	if (! is_validaliasname($_POST['name'])) {
		$input_errors[] = 'Grup adı geçersiz karakterler içeriyor.';
	}

	if (isset($_POST['name'])) {
		/* check for overlaps */
		if(is_array($a_gateway_groups)) {
			foreach ($a_gateway_groups as $gateway_group) {
				if (isset($id) && ($a_gateway_groups[$id]) && ($a_gateway_groups[$id] === $gateway_group))
					continue;

				if ($gateway_group['name'] == $_POST['name']) {
					$input_errors[] = sprintf('"%s" adında bir ağ geçidi grubu zaten bulunmaktadır.', $_POST['name']);
					break;
				}
			}
		}
	}

	$pconfig['item'] = array();
	foreach($a_gateways as $gwname => $gateway) {
		if($_POST[$gwname] > 0) {
			$pconfig['item'][] = "{$gwname}|{$_POST[$gwname]}";
		}

		if ($_POST['name'] == $gwname)
			$input_errors[] = sprintf('Ağ geçidi grup adı "%s" ağ geçidi adıyla aynı olamaz.', $_POST['name']);

	}
	if(count($pconfig['item']) == 0)
		$input_errors[] = "Herhangi bir ağ geçidinin önceliği belirtilmedi.";

	if (!$input_errors) {
		$gateway_group = array();
		$gateway_group['name'] = $_POST['name'];
		$gateway_group['item'] = $pconfig['item'];
		$gateway_group['trigger'] = $_POST['trigger'];
		$gateway_group['descr'] = base64_encode($_POST['descr']);

		if (isset($id) && $a_gateway_groups[$id])
			$a_gateway_groups[$id] = $gateway_group;
		else
			$a_gateway_groups[] = $gateway_group;

		mark_subsystem_dirty('staticroutes');

		write_config();

		header("Location: system_gateway_groups.php");
		exit;
	}
}

$pgtitle = array('SİSTEM', 'AĞ GEÇİDİ GRUPLARI' , 'GRUP DÜZENLE');

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include("fbegin.inc"); ?>

<?php if ($input_errors) print_input_errors($input_errors); ?>
<form action="system_gateway_groups_edit.php" method="post" name="iform" id="iform">
<table border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td colspan="2" valign="top" class="listtopic">AĞ GEÇİDİ GRUBU DÜZENLE</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Grup Adı</td>
					<td class="vtable">
						<input name="name" type="text" id="name" value="<?=htmlspecialchars($pconfig['name']);?>">
						<br> Grup adı girin.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Ağ Geçidi Önceliği</td>
					<td class="vtable">
						<?php
							foreach($a_gateways as $gwname => $gateway) {
								$selected = array();
								$interface = $gateway['interface'];
								foreach((array)$pconfig['item'] as $item) {
									$itemsplit = explode("|", $item);
									if($itemsplit[0] == $gwname) {
										$selected[$itemsplit[1]] = "selected";
										break;
									} else {
										$selected[0] = "selected";
									}
								}
								echo "<select name='{$gwname}' id='{$gwname}'>";
								echo "<option value='0' $selected[0] >" . "Yok" . "</option>";
								echo "<option value='1' $selected[1] >" . "Katman 1" . "</option>";
								echo "<option value='2' $selected[2] >" . "Katman 2" . "</option>";
								echo "<option value='3' $selected[3] >" . "Katman 3" . "</option>";
								echo "<option value='4' $selected[4] >" . "Katman 4" . "</option>";
								echo "<option value='5' $selected[5] >" . "Katman 5" . "</option>";
								echo "</select> <b>{$gateway['name']} - " . base64_decode($gateway['descr']) ."</b><br />";
							}
						?>

					Yük dengeleme ve değiştirme gibi olaylarda hangi ağ geçidine öncelik tanınacağını belirler.

					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Olay Tetikleyici</td>
					<td class="vtable">
						<select name='trigger' id='trigger'>
							<?php
								foreach ($categories as $category => $categoryd) {
										echo "<option value=\"$category\"";
										if ($category == $pconfig['trigger']) echo " selected";
									echo ">" . htmlspecialchars($categoryd) . "</option>\n";
								}
							?>
						</select>
						<br>Yük dengeleme, ağ geçidi değiştirme gibi olayların hangi
						durumda tetikleneceğini belirtin.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Açıklama</td>
					<td class="vtable">
						<input name="descr" type="text" id="descr" value="<?=htmlspecialchars($pconfig['descr']);?>">
						<br>İsteğe bağlı bir açıklama girebilirsiniz.
					</td>
				</tr>
				<tr>
					<td class="vncell"></td>
					<td class="vtable">
						<input name="Submit" type="submit" class="btn btn-inverse" value="Kaydet">
						<input type="button" value="İptal" class="btn btn-default"  onclick="history.back()">
						<?php if (isset($id) && $a_gateway_groups[$id]): ?>
						<input name="id" type="hidden" value="<?=htmlspecialchars($id);?>">
						<?php endif; ?>
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
