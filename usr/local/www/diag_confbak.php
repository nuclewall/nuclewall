<?php
/* $Id$ */
/*
    diag_confbak.php

	Copyright (C) 2013-2015 Ogün AÇIK
	All rights reserved.

    Copyright (C) 2010 Jim Pingle
	Copyright (C) 2005 Colin Smith
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

if($_GET['newver'] != "") {
	$confvers = unserialize(file_get_contents($g['cf_conf_path'] . '/backup/backup.cache'));
	if(config_restore($g['conf_path'] . '/backup/config-' . $_GET['newver'] . '.xml') == 0)

	$savemsg = sprintf('%1$s tarihli "%2$s" açıklamalı yedeğe geri alındı.', date("H:i:s d-m-Y", $_GET['newver']), $confvers[$_GET['newver']]['description']);
	else
		$savemsg = 'Seçilen ayara geri alınamadı.';
}

if($_GET['rmver'] != "") {
	$confvers = unserialize(file_get_contents($g['cf_conf_path'] . '/backup/backup.cache'));
	unlink_if_exists($g['conf_path'] . '/backup/config-' . $_GET['rmver'] . '.xml');
	$savemsg = sprintf('%1$s tarihli "%2$s" açıklamalı yedek silindi.', date("H:i:s d-m-Y", $_GET['rmver']),$confvers[$_GET['rmver']]['description']);
}

if($_GET['getcfg'] != "") {
	$file = $g['conf_path'] . '/backup/config-' . $_GET['getcfg'] . '.xml';

	$exp_name = urlencode("config-{$config['system']['hostname']}.{$config['system']['domain']}-{$_GET['getcfg']}.xml");
	$exp_data = file_get_contents($file);
	$exp_size = strlen($exp_data);

	header("Content-Type: application/octet-stream");
	header("Content-Disposition: attachment; filename={$exp_name}");
	header("Content-Length: $exp_size");
	echo $exp_data;
	exit;
}

if (($_GET['diff'] == 'Fark') && isset($_GET['oldtime']) && isset($_GET['newtime'])
      && is_numeric($_GET['oldtime']) && (is_numeric($_GET['newtime']) || ($_GET['newtime'] == 'current'))) {
	$diff = "";
	$oldfile = $g['conf_path'] . '/backup/config-' . $_GET['oldtime'] . '.xml';
	$oldtime = $_GET['oldtime'];
	if ($_GET['newtime'] == 'current') {
		$newfile = $g['conf_path'] . '/config.xml';
		$newtime = $config['revision']['time'];
	} else {
		$newfile = $g['conf_path'] . '/backup/config-' . $_GET['newtime'] . '.xml';
		$newtime = $_GET['newtime'];
	}
	if (file_exists($oldfile) && file_exists($newfile)) {
		exec("/usr/bin/diff -u " . escapeshellarg($oldfile) . " " . escapeshellarg($newfile), $diff);
	}
}

cleanup_backupcache();
$confvers = get_backups();
unset($confvers['versions']);

$pgtitle = array('ARAÇLAR', 'DEĞİŞİKLİK GEÇMİŞİ');

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php

if($savemsg)
	print_info_box($savemsg);
?>
<?php if ($diff) { ?>
<table class="tabcont" cellspacing="0">
	<tr>
		<td>
			<div class="alert">
				<b><?php echo date("H:i:s d-m-Y", $oldtime); ?></b> ile <b><?php echo date("H:i:s d-m-Y", $newtime); ?></b> tarihi arasındaki değişiklik farkı.
			</div>
		</td>
	</tr>
	<?php foreach ($diff as $line) {
		switch (substr($line, 0, 1)) {
			case "+":
				$color = "#caffd3";
				break;
			case "-":
				$color = "#ffe8e8";
				break;
			case "@":
				$color = "#a0a0a0";
				break;
			default:
				$color = "";
		}
		?>
	<tr>
		<td valign="middle" bgcolor="<?php echo $color; ?>" style="white-space: pre-wrap;">
			<?php echo htmlentities($line);?>
		</td>
	</tr>
	<?php } ?>
</table>
<br><br>
<?php } ?>

<form action="diag_confbak.php" method="GET">
<table cellpadding="0" cellspacing="0">
	<tr>
		<td>
		<?php
			$tab_array = array();
			$tab_array[0] = array("Değişiklik Geçmişi", true, "diag_confbak.php");
			$tab_array[1] = array("Yedekle / Geri Al", false, "diag_backup.php");
			display_top_tabs($tab_array);
		?>
		</td>
	</tr>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<table width="100%" class="grids">
							<?php if (is_array($confvers)): ?>
							<tr>
								<td class="head">
								</td>
								<td class="head">
									Tarih
								</td>
								<td colspan="2" class="head">
									Değişiklik
								</td>
							</tr>

							<tr>
								<td class="wall">
									<input type="radio" name="newtime" value="current">
								</td>
								<td class="cell blue">
									<?= date("H:i:s d-m-Y", $config['revision']['time']) ?>
								</td>
								<td class="cell">
									<?= $config['revision']['description'] ?>
								</td class="wall toolc">
								<td colspan="2">
									<center><span class="label label-success">Aktif</span><center>
								</td>
							</tr>
								<?php
									$c = 0;
									foreach($confvers as $version):
										if($version['time'] != 0)
											$date = date("H:i:s d-m-Y", $version['time']);
										else
											$date = 'Bilinmiyor';
										$desc = $version['description'];
								?>
							<tr>
								<td class="wall">
									<input type="radio" name="oldtime" value="<?php echo $version['time'];?>">
									<?php if ($c < (count($confvers) - 1)) { ?>
									<input type="radio" name="newtime" value="<?php echo $version['time'];?>">
									<?php } else { ?>
									<?php } $c++; ?>
								</td>
								<td class="cell blue">
									<?= $date ?>
								</td>
								<td class="cell">
									<?= $desc ?>
								</td>
								<td class="wall toolc">
									<a href="diag_confbak.php?newver=<?=$version['time'];?>" onclick="return confirm('Bu ayarı geri yükle?')">
										<i title="Bu ayarı geri yükle" class="icon-step-backward"></i>
									</a>
									<a href="diag_confbak.php?rmver=<?=$version['time'];?>" onclick="return confirm('Bu ayarı sil?')">
										<i title="Bu ayarı sil" class="icon-trash"></i>
									</a>
									<a href="diag_confbak.php?getcfg=<?=$version['time'];?>">
										<i title="Bu ayarı indir" class="icon-download-alt"></i>
									</a>
								</td>
							</tr>
							<?php endforeach; ?>
							<tr>
								<td class="wall">
									<input title="Seçili ayarların farkını göster" class="btn btn-mini" type="submit" name="diff" value="Fark">
								</td>
								<td class="cell" colspan="4">
								</td>
							</tr>
							<?php else: ?>
							<tr>
								<td>
									<?php print_info_box("Yedek bulunamadı."); ?>
								</td>
							</tr>
							<?php endif; ?>
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
