<?php
/* $Id$ */
/*
	diag_backup.php

	Copyright (C) 2013-2015 Ogün AÇIK
	All rights reserved.

	Copyright (C) 2004-2009 Scott Ullrich
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

ini_set('max_execution_time', '0');
ini_set('max_input_time', '0');

/* omit no-cache headers because it confuses IE with file downloads */
$omit_nocacheheaders = true;
$nocsrf = true;
require('guiconfig.inc');
require_once('functions.inc');
require_once('filter.inc');
require_once('shaper.inc');


if ($_POST['apply']) {
        ob_flush();
        flush();
		clear_subsystem_dirty("restore");
        exit;
}

if ($_POST) {
	unset($input_errors);
	if (stristr($_POST['Submit'], "Yükle"))
		$mode = "restore";
	else if (stristr($_POST['Submit'], "Reinstall"))
		$mode = "reinstallpackages";
	else if (stristr($_POST['Submit'], "Clear Package Lock"))
		$mode = "clearpackagelock";
	else if (stristr($_POST['Submit'], "Yedek Al"))
		$mode = "download";
	else if (stristr($_POST['Submit'], "Restore version"))
		$mode = "restore_ver";

	if ($_POST["nopackages"] <> "")
		$options = "nopackages";

	if ($_POST["ver"] <> "")
		$ver2restore = $_POST["ver"];

	if ($mode) {

		if ($mode == "download") {

			if (!$input_errors) {

				$host = "{$config['system']['hostname']}.{$config['system']['domain']}";
				$name = "config-{$host}-".date("YmdHis").".xml";
				$data = "";

				if($options == "nopackages") {
					if(!$_POST['backuparea']) {
						$data = file_get_contents("{$g['conf_path']}/config.xml");
					} else {
						$data = backup_config_section($_POST['backuparea']);
						$name = "{$_POST['backuparea']}-{$name}";
					}
					$sfn = "{$g['tmp_path']}/config.xml.nopkg";
					file_put_contents($sfn, $data);
					exec("sed '/<installedpackages>/,/<\/installedpackages>/d' {$sfn} > {$sfn}-new");
					$data = file_get_contents($sfn . "-new");
				} else {
					if(!$_POST['backuparea']) {
						$data = file_get_contents("{$g['conf_path']}/config.xml");
					} else {
						$data = backup_config_section($_POST['backuparea']);
						$name = "{$_POST['backuparea']}-{$name}";
					}
				}

				$size = strlen($data);
				header("Content-Type: application/octet-stream");
				header("Content-Disposition: attachment; filename={$name}");
				header("Content-Length: $size");
				if (isset($_SERVER['HTTPS'])) {
					header('Pragma: ');
					header('Cache-Control: ');
				} else {
					header("Pragma: private");
					header("Cache-Control: private, must-revalidate");
				}
				echo $data;

				exit;
			}
		}

		if ($mode == "restore") {

			if (!$input_errors) {

				if (is_uploaded_file($_FILES['conffile']['tmp_name'])) {

					/* read the file contents */
					$data = file_get_contents($_FILES['conffile']['tmp_name']);
					if(!$data) {
						log_error(sprintf("Uyarı: %s dosyası okunamıyor.", $_FILES['conffile']['tmp_name']));
						return 1;
					}

					if($_POST['restorearea']) {
						/* restore a specific area of the configuration */
						if(!stristr($data, $_POST['restorearea'])) {
							$input_errors[] = "XML etiketinde hata var.";
						} else {
							restore_config_section($_POST['restorearea'], $data);
							filter_configure();
							$savemsg = "Ayar dosyası yüklendi. NUCLEWALL şimdi yeniden başlatılacak.";
						}
					} else {
						if(!stristr($data, "<" . $g['xml_rootobj'] . ">")) {
							$input_errors[] = sprintf("%s xml etiketi bulunamadı.", $g['xml_rootobj']);
						} else {
							/* restore the entire configuration */
							file_put_contents($_FILES['conffile']['tmp_name'], $data);
							if (config_install($_FILES['conffile']['tmp_name']) == 0) {
								/* this will be picked up by /index.php */
								mark_subsystem_dirty("restore");
								touch("/conf/needs_package_sync");
								/* remove cache, we will force a config reboot */
								if(file_exists("{$g['tmp_path']}/config.cache"))
									unlink("{$g['tmp_path']}/config.cache");
								$config = parse_config(true);

								if(isset($config['captiveportal']['enable'])) {
									/* for some reason ipfw doesn't init correctly except on bootup sequence */
									mark_subsystem_dirty("restore");
								}
								setup_serial_port();
								if(is_interface_mismatch() == true) {
									touch("/var/run/interface_mismatch_reboot_needed");
									clear_subsystem_dirty("restore");
									convert_config();
									header("Location: interfaces_assign.php");
									exit;
								}
								if (is_interface_vlan_mismatch() == true) {
									touch("/var/run/interface_mismatch_reboot_needed");
									clear_subsystem_dirty("restore");
									convert_config();
									header("Location: interfaces_assign.php");
									exit;
								}
							} else {
								$input_errors[] = "Ayar dosyasındaki ayarlar yüklenemedi.";
							}
						}
					}
				} else {
					$input_errors[] = "Ayar dosyası yüklenemedi (dosya seçilmedi).";
				}
			}
		}
	}
}

$pgtitle = array('ARAÇLAR', 'YEDEK AL / GERİ YÜKLE');

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (is_subsystem_dirty('restore')): ?><p>
<form action="reboot.php" method="post">
<input name="Submit" type="hidden" value="Yes">
<?php print_info_box("Ayar dosyası yüklendi. NUCLEWALL şimdi yeniden başlatılacak.");?>
</form>
<?php endif; ?>
<form action="diag_backup.php" method="post" name="iform" enctype="multipart/form-data">
<table border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td>
		<?php
			$tab_array = array();
			$tab_array[0] = array("Değişiklik Geçmişi", false, "diag_confbak.php");
			$tab_array[1] = array("Yedekle / Geri Al", true, "diag_backup.php");
			display_top_tabs($tab_array);
		?>
		</td>
	</tr>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td colspan="2" class="listtopic">YEDEK AL</td>
				</tr>
				<tr>
					<td class="vncell">
						<p><input name="Submit" type="submit" class="btn btn-inverse" id="download" value="Yedek Al"></p>
					</td>
				</tr>
                <tr>
					<td colspan="2" class="listtopic">YEDEK DOSYASINI YÜKLE</td>
				</tr>
				<tr>
					<td class="vncell">
						<input name="conffile" class="btn-mini" type="file" id="conffile" size="40">
						<input name="Submit" type="submit" class="btn btn-inverse" id="restore" value="Yükle">
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>
<div class="alert">
	<b>NOT : </b>Yedek dosyası yüklenirse NUCLEWALL yeniden başlatılacaktır.
</div>
</div>
</body>
</html>
<?php
if (is_subsystem_dirty('restore'))
	system_reboot();
?>
