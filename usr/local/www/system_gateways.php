<?php
/* $Id$ */
/*
	system_gateways.php

	Copyright (C) 2015 Ogün AÇIK

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
require_once('functions.inc');
require_once('filter.inc');
require_once('shaper.inc');

$a_gateways = return_gateways_array(true);
$a_gateways_arr = array();

foreach ($a_gateways as $gw)
	$a_gateways_arr[] = $gw;

$a_gateways = $a_gateways_arr;

if (!is_array($config['gateways']['gateway_item']))
        $config['gateways']['gateway_item'] = array();

$a_gateway_item = &$config['gateways']['gateway_item'];

if ($_POST)
{
	$pconfig = $_POST;

	if ($_POST['apply'])
	{
		$retval = 0;
		$retval = system_routing_configure();
		$retval |= filter_configure();
		setup_gateways_monitor();

		$savemsg = get_std_save_message($retval);
		if ($retval == 0)
			clear_subsystem_dirty('staticroutes');
	}
}

if ($_GET['act'] == "del")
{
	if ($a_gateways[$_GET['id']])
	{
		$realid = $a_gateways[$_GET['id']]['attribute'];
		$remove = true;

		if(is_array($config['gateways']['gateway_group']))
		{
			foreach ($config['gateways']['gateway_group'] as $group)
			{
				foreach ($group['item'] as $item) {
					$items = explode("|", $item);
					if ($items[0] == $a_gateways[$_GET['id']]['name']) {
						$input_errors[] = "Bu ağ geçidi '{$group['name']}' ağ geçidi grubunda kullanıldığından dolayı silinemez.";
						$remove = false;
						break;
					}

				}
			}
		}

		if(is_array($config['staticroutes']['route']))
		{
			foreach ($config['staticroutes']['route'] as $route)
			{
				if ($route['gateway'] == $a_gateways[$_GET['id']]['name'])
				{
					$input_errors[] = "Bu ağ geçidi '{$route['network']}' sabit yönlendirmelerinde kullanıldığı için silinemez.";
						$remove = false;
					break;
				}
			}
		}

		if($remove == true)
		{
			if ($config['interfaces'][$a_gateways[$_GET['id']]['friendlyiface']]['gateway'] == $a_gateways[$_GET['id']]['name'])
				unset($config['interfaces'][$a_gateways[$_GET['id']]['friendlyiface']]['gateway']);

			unset($a_gateway_item[$realid]);
			write_config();
			mark_subsystem_dirty('staticroutes');
			header("Location: system_gateways.php");
			exit;
		}
	}
}

$pgtitle = array('SİSTEM', 'AĞ GEÇİTLERİ');

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php if ($input_errors) print_input_errors($input_errors); ?>
<form action="system_gateways.php" method="post">
<input type="hidden" name="y1" value="1">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (is_subsystem_dirty('staticroutes')): ?><p>
<?php print_info_box_np('Ağ geçidi ayarları değiştirildi.' . '<br>' . 'Değişikliklerin etkili olabilmesi için uygulamalısınız.', true);?><br>
<?php endif; ?>
<table border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<?php
				$tab_array = array();
				$tab_array[0] = array("Ağ Geçitleri", true, "system_gateways.php");
				$tab_array[1] = array("Yönlendirmeler", false, "system_routes.php");
				$tab_array[2] = array("Gruplar", false, "system_gateway_groups.php");
				display_top_tabs($tab_array);
			?>
		</td>
	</tr>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<table class="grids">
							<tr>
								<td class="head">Ad</td>
								<td class="head">Ethernet Kartı</td>
								<td class="head">Ağ Geçidi</td>
								<td class="head">Takip IP'si</td>
								<td class="head">Açıklama</td>
								<td class="head"></td>
							</tr>
									  <?php $i = 0; foreach ($a_gateways as $gateway): ?>
							<tr>
								<td class="cell" ondblclick="document.location='system_gateways_edit.php?id=<?=$i;?>';">
								<?php
									echo $gateway['name'];
									if(isset($gateway['defaultgw']))
								?>
								</td>
								<td class="cell" ondblclick="document.location='system_gateways_edit.php?id=<?=$i;?>';">
								<?php
									echo htmlspecialchars(convert_friendly_interface_to_friendly_descr($gateway['friendlyiface']));
								?>
								</td>
								<td class="cell" ondblclick="document.location='system_gateways_edit.php?id=<?=$i;?>';">
								<?php
									echo $gateway['gateway'] . " ";
								?>
								</td>
								<td class="cell" ondblclick="document.location='system_gateways_edit.php?id=<?=$i;?>';">
								<?php
									echo htmlspecialchars($gateway['monitor']) . " ";
								?>
								</td>
								<?php if (is_numeric($gateway['attribute'])) : ?>
								<td class="cell description" ondblclick="document.location='system_gateways_edit.php?id=<?=$i;?>';">
								<?php else : ?>
								<td class="cell description" ondblclick="document.location='system_gateways_edit.php?id=<?=$i;?>';">
								<?php endif; ?>
											<?=htmlspecialchars(base64_decode($gateway['descr']));?>
								</td>

								<td class="cell tools">
										<a title="Düzenle" href="system_gateways_edit.php?dup=<?=$i;?>"><i class="icon-edit"></i></a>
										<?php
										if (is_numeric($gateway['attribute'])) : ?>
											<a title="Sil" href="system_gateways.php?act=del&id=<?=$i;?>" onclick="return confirm('<?=gettext("Silmek istediğinize emin misiniz?"); ?>')">
												<i class="icon-trash"></i>
											</a>
										<?php endif; ?>
								</td>
							</tr>
								  <?php $i++; endforeach; ?>
							<tr>
								<td class="cell" colspan="5">
								</td>
								<td class="cell tools">
										<a title="Ekle" href="system_gateways_edit.php">
											<i class="icon-plus"></i>
										</a>
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
</div>
</body>
</html>
