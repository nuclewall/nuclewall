<?php
/*
	hotspot_settings.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.
*/

require('guiconfig.inc');
require('functions.inc');
require('filter.inc');
require('shaper.inc');
require('captiveportal.inc');
require('service-utils.inc');

$pgtitle = array('SERVİSLER', 'HOTSPOT ', ' GENEL AYARLAR');

if (!is_array($config['captiveportal']))
{
	$config['captiveportal'] = array();
	$config['captiveportal']['page'] = array();
	$config['captiveportal']['timeout'] = 300;
}

$pconfig['cinterface'] = $config['captiveportal']['interface'];
$pconfig['timeout'] = $config['captiveportal']['timeout'];
$pconfig['idletimeout'] = $config['captiveportal']['idletimeout'];
$pconfig['enable'] = isset($config['captiveportal']['enable']);
$pconfig['allow_dns'] = isset($config['captiveportal']['allow_dns']);

if ($_POST)
{
	unset($input_errors);
	$pconfig = $_POST;

	if ($_POST['enable'])
	{
		$reqdfields = explode(' ', 'cinterface');
		$reqdfieldsn = array("Arayüz");

		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

		if(!is_process_running("mysqld"))
		{
			start_mysql();
		}
		start_radius();
	}

	else
	{
		stop_radius();
	}

	if ($_POST['timeout'] && (!is_numeric($_POST['timeout']) || ($_POST['timeout'] < 1)))
	{
		$input_errors[] = 'Aktif oturum süresi 1 dakikadan az olamaz.';
	}

	if ($_POST['idletimeout'] && (!is_numeric($_POST['idletimeout']) || ($_POST['idletimeout'] < 1)))
	{
		$input_errors[] = 'Aktif oturum süresi 1 dakikadan az olamaz.';
	}

	if (!$input_errors)
	{
		if (is_array($_POST['cinterface']))
			$config['captiveportal']['interface'] = implode(',', $_POST['cinterface']);

		$config['captiveportal']['timeout'] = $_POST['timeout'];
		$config['captiveportal']['idletimeout'] = $_POST['idletimeout'];
		$config['captiveportal']['enable'] = $_POST['enable'] ? true : false;
		$config['captiveportal']['allow_dns'] = $_POST['allow_dns'] ? true : false;

		write_config();

		$retval = 0;
		$retval = captiveportal_configure();

		$savemsg = get_std_save_message($retval);

		if (is_array($_POST['cinterface']))
			$pconfig['cinterface'] = implode(",", $_POST['cinterface']);

		filter_configure();
	}
}

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<form action='hotspot_settings.php' method='post' enctype='multipart/form-data' name='iform' id='iform'>
<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
		<td class='tabnavtbl'>
			<?php
				$tab_array = array();
				$tab_array[] = array('Genel Ayarlar', true, 'hotspot_settings.php');
				$tab_array[] = array('Harici Veri Kaynakları', false, 'hotspot_datasources.php');
				$tab_array[] = array('Kullanıcı Karşılama Sayfası', false, 'hotspot_form_settings.php');
				display_top_tabs($tab_array, true);
			?>
		</td>
	</tr>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td valign="top" class="vncell">Aktif</td>
					<td class="vtable">
						<label>
						<input name="enable" type="checkbox" value="yes" <?php if ($pconfig['enable']) echo "checked"; ?>>
						Hotspot'u aktifleştirmek için işaretleyin.
						</label>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Arayüz</td>
					<td class="vtable">
						<select name="cinterface[]" multiple="true" size="<?php echo count($config['interfaces']); ?>" id="cinterface">
						  <?php
						  $interfaces = get_configured_interface_with_descr();
						  $cselected = explode(",", $pconfig['cinterface']);
						  foreach ($interfaces as $iface => $ifacename): ?>
							  <option value="<?=$iface;?>" <?php if (in_array($iface, $cselected)) echo "selected"; ?>>
							  <?=htmlspecialchars($ifacename);?>
							  </option>
						  <?php endforeach; ?>
						</select><br>
						Hotspot'un aktif olacağı arayüzü seçin. Çoklu seçim yapabilirsiniz.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Aktif Oturum Süresi<br>(dakika)</td>
					<td class="vtable">
						<input name="idletimeout" type="text"  id="idletimeout" size="6" value="<?=htmlspecialchars($pconfig['idletimeout']);?>">
						<p>Oturum açmış olan hotspot kullanıcıların oturumu, burada belirtilen süre geçtikten sonra kapatılacaktır.
						Yeniden oturum açıp kullanmaya devam edebilirler.</p>
						<b>Not: </b>Aktif oturum süresi kullanmak istemiyorsanız bu alanı boş bırakın.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Oturum Süresi<br>(dakika)</td>
					<td class="vtable">
						<input name="timeout" type="text"  id="timeout" size="6" value="<?=htmlspecialchars($pconfig['timeout']);?>">
						<p>Burada belirtilen zaman dolduğunda, aktif oturum süresine bakılmaksızın tüm kullanıcıların oturumu kapatılacaktır.
						Yeniden oturum açıp kullanmaya devam edebilirler.</p>
						<b>Not: </b>Oturum süresi kullanmak istemiyorsanız bu alanı boş bırakın.
						<br>Fakat, aktif oturum süresi kullanmıyorsanız bu alanı kullanmanız önerilir.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">DNS trafiği</td>
					<td class="vtable">
						<label>
						<input name="allow_dns" type="checkbox" value="yes" <?php if ($pconfig['allow_dns']) echo "checked"; ?>>
						Harici DNS trafiğine izin ver.
						<p>Hotspot'a oturum açmamış kullanıcıların harici DNS sunucularına erişmesine izin verir.
						İzin verilmezse, harici DNS sunucu kullanan kullanıcılar HOTSPOT oturum açma sayfasına otomatik olarak yönlendirilmezler.
						</p>
						</label>
					</td>
				</tr>
				<tr>
					<td class="vncell"></td>
					<td class="vtable">
						<input name="Submit" type="submit" class="btn btn-inverse" value="Kaydet">
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
