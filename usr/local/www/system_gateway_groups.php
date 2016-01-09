<?php
/* $Id$ */
/*
	system_gateway_groups.php

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
require_once('functions.inc');
require_once('filter.inc');
require_once('shaper.inc');

if (!is_array($config['gateways']['gateway_group']))
	$config['gateways']['gateway_group'] = array();

$a_gateway_groups = &$config['gateways']['gateway_group'];
$a_gateways = &$config['gateways']['gateway_item'];

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
	if ($a_gateway_groups[$_GET['id']])
	{
		foreach ($config['filter']['rule'] as $idx => $rule)
		{
			if ($rule['gateway'] == $a_gateway_groups[$_GET['id']]['name'])
				unset($config['filter']['rule'][$idx]['gateway']);
		}

		unset($a_gateway_groups[$_GET['id']]);
		write_config();
		mark_subsystem_dirty('staticroutes');
		header("Location: system_gateway_groups.php");
		exit;
	}
}

$pgtitle = array('SİSTEM', 'AĞ GEÇİDİ GRUPLARI');

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<form action="system_gateway_groups.php" method="post">
<input type="hidden" name="y1" value="1">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (is_subsystem_dirty('staticroutes')): ?><p>
<?php print_info_box_np("Ağ geçidi grubu ayarları değiştirildi.<br>Değişikliklerin etkili olabilmesi için uygulamalısınız.", true);?>
<?php endif; ?>
<table border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<?php
				$tab_array = array();
				$tab_array[0] = array("Ağ Geçitleri", false, "system_gateways.php");
				$tab_array[1] = array("Yönlendirmeler", false, "system_routes.php");
				$tab_array[2] = array("Gruplar", true, "system_gateway_groups.php");
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
								<td class="head">Grup Adı</td>
								<td class="head">Ağ Geçidi</td>
								<td class="head">Öncelik</td>
								<td class="head">Açıklama</td>
								<td class="head"></td>
			
							</tr>
									  <?php $i = 0; foreach ($a_gateway_groups as $gateway_group): ?>
								<tr>
									<td class="cell" ondblclick="document.location='system_gateway_groups_edit.php?id=<?=$i;?>';">
											<?php echo $gateway_group['name']; ?>
									</td>
									<td class="cell" ondblclick="document.location='system_gateway_groups_edit.php?id=<?=$i;?>';">
										<?php
										foreach($gateway_group['item'] as $item) {
											$itemsplit = explode("|", $item);
											echo htmlspecialchars(strtoupper($itemsplit[0])) . "<br/>\n";
										}
										?>
									</td>
									<td class="cell" ondblclick="document.location='system_gateway_groups_edit.php?id=<?=$i;?>';">
									<?php
										foreach($gateway_group['item'] as $item) {
											$itemsplit = explode("|", $item);
											echo "Tier ". htmlspecialchars($itemsplit[1]) . "<br/>\n";
										}
										?>
									</td>
									<td class="cell description" ondblclick="document.location='system_gateway_groups_edit.php?id=<?=$i;?>';">
										<?=htmlspecialchars(base64_decode($gateway_group['descr']));?>
									</td>
									<td valign="middle" class="cell tools">
										<a href="system_gateway_groups_edit.php?dup=<?=$i;?>">
											<i class="icon-edit"></i>
										</a>
										<a href="system_gateway_groups.php?act=del&id=<?=$i;?>" onclick="return confirm('Silmek istediğinize emin misiniz?')">
											<i class="icon-trash"></i>
										</a>

									</td>
								</tr>
									  <?php $i++; endforeach; ?>
								<tr>
									<td class="cell" colspan="4"></td>
									<td class="cell tools">
											<a title="Ekle" href="system_gateway_groups_edit.php"><i class="icon-plus"></i></a>
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
