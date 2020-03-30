<?php
/* $Id$ */
/*
	system_routes.php
	part of m0n0wall (http://m0n0.ch/wall)

	Copyright (C) 2013-2020 Ogun Acik
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

require('guiconfig.inc');
require_once('functions.inc');
require_once('filter.inc');
require_once('shaper.inc');

if (!is_array($config['staticroutes']['route']))
	$config['staticroutes']['route'] = array();

$a_routes = &$config['staticroutes']['route'];
$a_gateways = return_gateways_array(true);

if ($_POST)
{
	$pconfig = $_POST;

	if ($_POST['apply'])
	{
		$retval = 0;

		if (file_exists("{$g['tmp_path']}/.system_routes.apply"))
		{
			$toapplylist = unserialize(file_get_contents("{$g['tmp_path']}/.system_routes.apply"));

			foreach ($toapplylist as $toapply)
				mwexec("{$toapply}");

			@unlink("{$g['tmp_path']}/.system_routes.apply");
		}

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
	if ($a_routes[$_GET['id']])
	{
		mwexec("/sbin/route delete " . escapeshellarg($a_routes[$_GET['id']]['network']));
		unset($a_routes[$_GET['id']]);
		write_config("Bir sabit yonlendirme silindi");
		header('Location: system_routes.php');
		exit;
	}
}

$pgtitle = array('SİSTEM', 'SABİT YÖNLENDİRMELER');

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<form action="system_routes.php" method="post">
<input type="hidden" name="y1" value="1">
	<?php if ($savemsg) print_info_box($savemsg); ?>
	<?php if (is_subsystem_dirty('staticroutes')): ?><p>
	<?php print_info_box_np("Sabit yönlendirme ayarları değiştirildi.<br>Değişikliklerin etkili olabilmesi için uygulamalısınız.", true);?><br>
	<?php endif; ?>
<table cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<?php
				$tab_array = array();
				$tab_array[0] = array("Ağ Geçitleri", false, "system_gateways.php");
				$tab_array[1] = array("Yönlendirmeler", true, "system_routes.php");
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
						<table class="grids sortable">
							<tr>
								<td class="head">Ağ</td>
								<td class="head">Ağ Geçidi</td>
								<td class="head">Ethernet Kartı</td>
								<td class="head">Açıklama</td>
								<td class="head"></td>
							</tr>
							<tr>
								<?php $i = 0; foreach ($a_routes as $route): ?>
								<td class="cell" ondblclick="document.location='system_routes_edit.php?id=<?=$i;?>';">
									<?=strtolower($route['network']);?>
								</td>
								<td class="cell" ondblclick="document.location='system_routes_edit.php?id=<?=$i;?>';">
									<?php
										echo htmlentities($a_gateways[$route['gateway']]['name']) . " - " . htmlentities($a_gateways[$route['gateway']]['gateway']);
									?>
								</td>
								<td class="cell" ondblclick="document.location='system_routes_edit.php?id=<?=$i;?>';">
									<?php
										echo convert_friendly_interface_to_friendly_descr($a_gateways[$route['gateway']]['friendlyiface']) . " ";
									?>
								</td>
								<td class="cell description" ondblclick="document.location='system_routes_edit.php?id=<?=$i;?>';">
									<?=htmlspecialchars(base64_decode($route['descr']));?>&nbsp;
								</td>
								<td class="cell tools">
									<a title="Düzenle" href="system_routes_edit.php?dup=<?=$i;?>">
										<i class="icon-edit"></i>
									</a>
									<a title="Sil" href="system_routes.php?act=del&id=<?=$i;?>" onclick="return confirm('Silmek istediğinize emin misiniz?')">
										<i class="icon-trash"></i>
									</a>
								</td>
							</tr>
							<?php $i++; endforeach; ?>
							<tr>
								<td class="cell" colspan="4">
								</td>
								<td class="cell tools">
									<a title="Ekle" href="system_routes_edit.php">
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
<div class="alert alert-warning">
	<b>NOT :</b>
	NUCLEWALL tarafından kullanılan herhangi bir ağ için sabit yönlendirme girmeyiniz.
	<br>Sabit yönlendirmeler, VARSAYILAN AĞ GEÇİDİ ile ulaşamadığınız ağlar içindir.
</div>
</div>
</body>
</html>
