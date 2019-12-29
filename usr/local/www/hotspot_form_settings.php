<?php
/*
	hotspot_form_settings.php

	Copyright (C) 2013-2015 Ogün AÇIK
	All rights reserved.
*/

require('guiconfig.inc');
require('hotspot.inc');

$pgtitle = array('SERVİSLER', ' HOTSPOT ', ' KULLANICI KARŞILAMA SAYFASI');

if (!is_array($config['hotspot']))
{
	$config['hotspot'] = array();
	$config['hotspot']['tr'] = array();
	$config['hotspot']['en'] = array();
	$config['hotspot']['de'] = array();
	$config['hotspot']['ru'] = array();
}

$pconfig['default_lang'] = $config['hotspot']['default_lang'];
$pconfig['company'] = htmlspecialchars(base64_decode($config['hotspot']['company']));
$pconfig['page_type'] = $config['hotspot']['page_type'];

$pconfig['tr_enabled'] = isset($config['hotspot']['tr']['enabled']);
$pconfig['en_enabled'] = isset($config['hotspot']['en']['enabled']);
$pconfig['de_enabled'] = isset($config['hotspot']['de']['enabled']);
$pconfig['ru_enabled'] = isset($config['hotspot']['ru']['enabled']);

$pconfig['tr_title'] = htmlspecialchars(base64_decode($config['hotspot']['tr']['title']));
$pconfig['en_title'] = htmlspecialchars(base64_decode($config['hotspot']['en']['title']));
$pconfig['de_title'] = htmlspecialchars(base64_decode($config['hotspot']['de']['title']));
$pconfig['ru_title'] = htmlspecialchars(base64_decode($config['hotspot']['ru']['title']));

$pconfig['tr_uname'] = htmlspecialchars(base64_decode($config['hotspot']['tr']['uname']));
$pconfig['en_uname'] = htmlspecialchars(base64_decode($config['hotspot']['en']['uname']));
$pconfig['de_uname'] = htmlspecialchars(base64_decode($config['hotspot']['de']['uname']));
$pconfig['ru_uname'] = htmlspecialchars(base64_decode($config['hotspot']['ru']['uname']));

$pconfig['tr_password'] = htmlspecialchars(base64_decode($config['hotspot']['tr']['password']));
$pconfig['en_password'] = htmlspecialchars(base64_decode($config['hotspot']['en']['password']));
$pconfig['de_password'] = htmlspecialchars(base64_decode($config['hotspot']['de']['password']));
$pconfig['ru_password'] = htmlspecialchars(base64_decode($config['hotspot']['ru']['password']));

$pconfig['tr_button'] = htmlspecialchars(base64_decode($config['hotspot']['tr']['button']));
$pconfig['en_button'] = htmlspecialchars(base64_decode($config['hotspot']['en']['button']));
$pconfig['de_button'] = htmlspecialchars(base64_decode($config['hotspot']['de']['button']));
$pconfig['ru_button'] = htmlspecialchars(base64_decode($config['hotspot']['ru']['button']));

$imageTarget = "/usr/local/captiveportal/pages/img/captiveportal-nuclewall";
$pageTarget = "/usr/local/www/pages/img/captiveportal-nuclewall";

if ($_POST)
{
    unset($input_errors);

	$pconfig = $_POST;

	$reqdfields = split(" ", "company page_type");
	$reqdfieldsn = array("İşletme Adı", "Sayfa Metinleri");

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if(strlen($_POST['company']) > 25)
		$input_errors[] = 'İşletme adı en fazla 25 karakter olabilir.';

    if (is_uploaded_file($_FILES['logo']['tmp_name']))
	{
		if(!getimagesize($_FILES['logo']['tmp_name']))
			$input_errors[] = "'{$_FILES['logo']['name']}' dosyası bir görüntü dosyası değil.";

		$size = filesize($_FILES['logo']['tmp_name']);

		if($size > 524288)
			$input_errors[] = 'Logo boyutu en fazla 512 KB olabilir.';
	}

	$tr_enabled = isset($_POST['tr_enabled']);
	$en_enabled = isset($_POST['en_enabled']);
	$de_enabled = isset($_POST['de_enabled']);
	$ru_enabled = isset($_POST['ru_enabled']);

	if($_POST['page_type'] == "custom")
	{
		if(!$tr_enabled and !$en_enabled and !$de_enabled and !$ru_enabled)
			$input_errors[] = 'Özel sayfa metinlerinde en az bir dil aktif edilmelidir.';

		$default_enabled = $_POST['default_lang'] . "_enabled";

		if(!${$default_enabled})
			$input_errors[] = 'Varsayılan olarak seçilen dil aktif edilmemiş.';
	}

	if($tr_enabled)
	{
		$reqdfields = split(" ", "tr_title tr_uname tr_password tr_button");
		$reqdfieldsn = array("Form başlığı(Türkçe)", "Kullanıcı adı alanı metni(Türkçe)", "Parola alanı metni(Türkçe)", "Giriş butonu metni(Türkçe)");
		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	}

	if(strlen($_POST['tr_title']) > 40)
		$input_errors[] = 'Form başlığı en fazla 40 karakter olabilir.(Türkçe)';
	if(strlen($_POST['tr_uname']) > 20)
		$input_errors[] = 'Kullanıcı adı alanı metni en fazla 20 karakter olabilir.(Türkçe)';
	if(strlen($_POST['tr_password']) > 20)
		$input_errors[] = 'Parola alanı metni en fazla 20 karakter olabilir.(Türkçe)';
	if(strlen($_POST['tr_button']) > 15)
		$input_errors[] = 'Giriş butonu metni en fazla 15 karakter olabilir.(Türkçe)';

	if($en_enabled)
	{
		$reqdfields = split(" ", "en_title en_uname en_password en_button");
		$reqdfieldsn = array("Form başlığı(İngilizce)", "Kullanıcı adı alanı metni(İngilizce)", "Parola alanı metni(İngilizce)", "Giriş butonu metni(İngilizce)");
		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	}

	if(strlen($_POST['en_title']) > 40)
		$input_errors[] = 'Form başlığı en fazla 40 karakter olabilir.(İngilizce)';
	if(strlen($_POST['en_uname']) > 20)
		$input_errors[] = 'Kullanıcı adı alanı metni en fazla 20 karakter olabilir.(İngilizce)';
	if(strlen($_POST['en_password']) > 20)
		$input_errors[] = 'Parola alanı metni en fazla 20 karakter olabilir.(İngilizce)';
	if(strlen($_POST['en_button']) > 15)
		$input_errors[] = 'Giriş butonu metni en fazla 15 karakter olabilir.(İngilizce)';

	if($de_enabled)
	{
		$reqdfields = split(" ", "de_title de_uname de_password de_button");
		$reqdfieldsn = array("Form başlığı(Almanca)", "Kullanıcı adı alanı metni(Almanca)", "Parola alanı metni(Almanca)", "Giriş butonu metni(Almanca)");
		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	}

	if(strlen($_POST['de_title']) > 40)
		$input_errors[] = 'Form başlığı en fazla 40 karakter olabilir.(Almanca)';
	if(strlen($_POST['de_uname']) > 20)
		$input_errors[] = 'Kullanıcı adı alanı metni en fazla 20 karakter olabilir.(Almanca)';
	if(strlen($_POST['de_password']) > 20)
		$input_errors[] = 'Parola alanı metni en fazla 20 karakter olabilir.(Almanca)';
	if(strlen($_POST['de_button']) > 15)
		$input_errors[] = 'Giriş butonu metni en fazla 15 karakter olabilir.(Almanca)';

	if($ru_enabled)
	{
		$reqdfields = split(" ", "ru_title ru_uname ru_password ru_button");
		$reqdfieldsn = array("Form başlığı(Rusça)", "Kullanıcı adı alanı metni(Rusça)", "Parola alanı metni(Rusça)", "Giriş butonu metni(Rusça)");
		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	}

	if(strlen($_POST['ru_title']) > 40)
		$input_errors[] = 'Form başlığı en fazla 40 karakter olabilir.(Rusça)';
	if(strlen($_POST['ru_uname']) > 20)
		$input_errors[] = 'Kullanıcı adı alanı metni en fazla 20 karakter olabilir.(Rusça)';
	if(strlen($_POST['ru_password']) > 20)
		$input_errors[] = 'Parola alanı metni en fazla 20 karakter olabilir.(Rusça)';
	if(strlen($_POST['ru_button']) > 15)
		$input_errors[] = 'Giriş butonu metni en fazla 15 karakter olabilir.(Rusça)';

	if (!$input_errors)
	{
		move_uploaded_file($_FILES["logo"]["tmp_name"], $imageTarget);
		copy($imageTarget, $pageTarget);

		$config['hotspot']['company'] = base64_encode($_POST['company']);
		$config['hotspot']['page_type'] = $_POST['page_type'];
		$config['hotspot']['default_lang'] = $_POST['default_lang'];

		$config['hotspot']['tr']['enabled'] = $_POST['tr_enabled'] ? true : false;
		$config['hotspot']['tr']['title'] = base64_encode($_POST['tr_title']);
		$config['hotspot']['tr']['uname'] = base64_encode($_POST['tr_uname']);
		$config['hotspot']['tr']['password'] = base64_encode($_POST['tr_password']);
		$config['hotspot']['tr']['button'] = base64_encode($_POST['tr_button']);

		$config['hotspot']['en']['enabled'] = $_POST['en_enabled'] ? true : false;
		$config['hotspot']['en']['title'] = base64_encode($_POST['en_title']);
		$config['hotspot']['en']['uname'] = base64_encode($_POST['en_uname']);
		$config['hotspot']['en']['password'] = base64_encode($_POST['en_password']);
		$config['hotspot']['en']['button'] = base64_encode($_POST['en_button']);

		$config['hotspot']['de']['enabled'] = $_POST['de_enabled'] ? true : false;
		$config['hotspot']['de']['title'] = base64_encode($_POST['de_title']);
		$config['hotspot']['de']['uname'] = base64_encode($_POST['de_uname']);
		$config['hotspot']['de']['password'] = base64_encode($_POST['de_password']);
		$config['hotspot']['de']['button'] = base64_encode($_POST['de_button']);

		$config['hotspot']['ru']['enabled'] = $_POST['ru_enabled'] ? true : false;
		$config['hotspot']['ru']['title'] = base64_encode($_POST['ru_title']);
		$config['hotspot']['ru']['uname'] = base64_encode($_POST['ru_uname']);
		$config['hotspot']['ru']['password'] = base64_encode($_POST['ru_password']);
		$config['hotspot']['ru']['button'] = base64_encode($_POST['ru_button']);

		write_config();

		if($_POST['page_type'] == "default")
		{
			createDefaultJS($_POST['default_lang']);
			createDefaultMobileJS($_POST['default_lang'], $_POST['company']);
		}

		else if($_POST['page_type'] == "custom")
		{
			createCustomJS($_POST['default_lang'], $config['hotspot']);
			createCustomMobileJS($_POST['default_lang'], $_POST['company'], $config['hotspot']);
		}

		else
		{
			$input_errors[] = 'Sayfalar oluşturulamadı';
		}

		initHtmlFiles();

		$savemsg = 'Ayarlar başarıyla kaydedildi.';
	}
}

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<form action='hotspot_form_settings.php' method='post' enctype='multipart/form-data' name='iform' id='iform'>
<table cellpadding='0' cellspacing='0'>
	<tr>
		<td class='tabnavtbl'>
			<?php
				$tab_array = array();
				$tab_array[] = array('Genel Ayarlar', false, 'hotspot_settings.php');
				$tab_array[] = array('Harici Veri Kaynakları', false, 'hotspot_datasources.php');
				$tab_array[] = array('Kullanıcı Karşılama Sayfası', true, 'hotspot_form_settings.php');
				display_top_tabs($tab_array, true);
			?>
		</td>
	</tr>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td valign="top" class="vncell">Sayfaları göster</td>
					<td class="vtable">
						<a target="_blank" class="btn btn-link" href="hotspot.html">PC ve Tablet</a>
						<a target="_blank" class="btn btn-link" href="hotspot_mobile.html">Mobil Cihaz</a>
					</td>
				</tr>
				<tr>
					<td colspan="2" class="listtopic">GENEL AYARLAR</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Varsayılan Dil</td>
					<td class="vtable">
						<select id="default_lang" name="default_lang">
							<option <?php if($pconfig['default_lang'] == "tr") echo "selected"; ?> value="tr">Türkçe</option>
							<option <?php if($pconfig['default_lang'] == "en") echo "selected"; ?> value="en">İngilizce</option>
							<option <?php if($pconfig['default_lang'] == "de") echo "selected"; ?> value="de">Almanca</option>
							<option <?php if($pconfig['default_lang'] == "ru") echo "selected"; ?> value="ru">Rusça</option>
						</select>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Logo<br>(PC'ler için)</td>
					<td class="vtable">
					<img width="90px" height="75px" class="thumbnail" src="pages/img/captiveportal-nuclewall?cache=<?php echo filemtime($pageTarget);?>">
						<input style="margin-top:10px;" name="logo" type="file" id="logo">
						<p>
						<b>Not: </b>Logo dosyası boyutu en fazla 512 KB olabilir.<br>
							Tavsiye edilen görüntü boyutu 120x100 pikseldir.
						</p>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">İşletme Adı<br>(Mobil cihazlar İçin)</td>
					<td class="vtable">
						<input value="<?=$pconfig['company'];?>" name="company" type="text" required id="company" maxlength="25" width="100">
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Sayfa Metinleri</td>
					<td class="vtable">
						<div class="form-inline">
							<div class="controls-row">
								<label class="radio inline">
									<input <?php if($pconfig['page_type'] == "default") echo "checked"; ?> name="page_type" type="radio" value="default"/>
									Varsayılan
								</label>
								<label class="radio inline">
									<input <?php if($pconfig['page_type'] == "custom") echo "checked"; ?> name="page_type" type="radio" value="custom"/>
									Özel
								</label>
							</div>
						</div>
					</td>
				</tr>
				<tbody id="custom_pages">
				<tr>
					<td colspan="2" class="listtopic">ÖZEL SAYFA METİNLERİ</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Dil</td>
					<td class="vtable">
						<div class="form-inline">
							<div class="controls-row">
								<label class="radio inline">
									<input checked name="lang" type="radio" value="tr"/>
									Türkçe
								</label>
								<label class="radio inline">
									<input name="lang" type="radio" value="en"/>
									İngilizce
								</label>
								<label class="radio inline">
									<input name="lang" type="radio" value="de"/>
									Almanca
								</label>
								<label class="radio inline">
									<input name="lang" type="radio" value="ru"/>
									Rusça
								</label>
							</div>
						</div>
					</td>
				</tr>
				<tr name="row" id="text_tr">
					<td valign="top" class="vncell">Türkçe</td>
					<td class="vtable">
						<table>
							<tr>
								<td valign="top">Aktif</td>
								<td>
								<input <?php if($pconfig['tr_enabled']) echo "checked"; ?> name="tr_enabled" type="checkbox">
								</td>
							</tr>
							<tr>
								<td valign="top">Form başlığı</td>
								<td>
									<input value="<?=$pconfig['tr_title']; ?>" placeholder="'İnternet erişimi için giriş yapın' gibi." name="tr_title" type="text" id="tr_title" style="width:290px;" maxlength="40">

								</td>
							</tr>
							<tr>
								<td valign="top">Kullanıcı adı alanı metni</td>
								<td>
									<input value="<?=$pconfig['tr_uname']; ?>" placeholder="'Kullanıcı Adı', 'Oda No' gibi." name="tr_uname" type="text" id="tr_uname" maxlength="20">
								</td>
							</tr>
							<tr>
								<td valign="top">Parola alanı metni</td>
								<td>
									<input value="<?=$pconfig['tr_password']; ?>" placeholder="'Parola', 'TC Kimlik No' gibi." name="tr_password" type="text" id="tr_password" maxlength="20">
								</td>
							</tr>
							<tr>
								<td valign="top">Giriş butonu metni</td>
								<td>
									<input value="<?=$pconfig['tr_button']; ?>" placeholder="'Giriş', 'Oturum Aç' gibi." name="tr_button" type="text" id="tr_button" maxlength="20">
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr name="row" id="text_en">
					<td valign="top" class="vncell">İngilizce</td>
					<td class="vtable">
						<table>
							<tr>
								<td valign="top">Aktif</td>
								<td>
								<input <?php if($pconfig['en_enabled']) echo "checked"; ?> name="en_enabled" type="checkbox">
								</td>
							</tr>
							<tr>
								<td valign="top">Form başlığı</td>
								<td>
									<input value="<?=$pconfig['en_title']; ?>" placeholder="'Log in to access the Internet' gibi." name="en_title" type="text" id="en_title" style="width:290px;" maxlength="40">
								</td>
							</tr>
							<tr>
								<td valign="top">Kullanıcı adı alanı metni</td>
								<td>
									<input value="<?=$pconfig['en_uname']; ?>" placeholder="'Username', 'Room Number' gibi." name="en_uname" type="text" id="en_uname" maxlength="20">
								</td>
							</tr>
							<tr>
								<td valign="top">Parola alanı metni</td>
								<td>
									<input value="<?=$pconfig['en_password']; ?>" placeholder="'Password', 'Passport Number' gibi." name="en_password" type="text" id="en_password" maxlength="20">
								</td>
							</tr>
							<tr>
								<td valign="top">Giriş butonu metni</td>
								<td>
									<input value="<?=$pconfig['en_button']; ?>" placeholder="'Log In' gibi." name="en_button" type="text" id="en_button" maxlength="20">
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr name="row" id="text_de">
					<td valign="top" class="vncell">Almanca</td>
					<td class="vtable">
						<table>
							<tr>
								<td valign="top">Aktif</td>
								<td>
								<input <?php if($pconfig['de_enabled']) echo "checked"; ?> name="de_enabled" type="checkbox">
								</td>
							</tr>
							<tr>
								<td valign="top">Form başlığı</td>
								<td>
									<input value="<?=$pconfig['de_title']; ?>" placeholder="'Anmelden um auf das Internet zuzugreifen' gibi." name="de_title" type="text" id="de_title" style="width:290px;" maxlength="40">
								</td>
							</tr>
							<tr>
								<td valign="top">Kullanıcı adı alanı metni</td>
								<td>
									<input value="<?=$pconfig['de_uname']; ?>" placeholder="'Benutzername' gibi." name="de_uname" type="text" id="de_uname" maxlength="20">
								</td>
							</tr>
							<tr>
								<td valign="top">Parola alanı metni</td>
								<td>
									<input value="<?=$pconfig['de_password']; ?>"  placeholder="'Passwort' gibi." name="de_password" type="text" id="de_password" maxlength="20">
								</td>
							</tr>
							<tr>
								<td valign="top">Giriş butonu metni</td>
								<td>
									<input value="<?=$pconfig['de_button']; ?>" placeholder="'Anmelden' gibi." name="de_button" type="text" id="de_button" maxlength="20">
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr name="row" id="text_ru">
					<td valign="top" class="vncell">Rusça</td>
					<td class="vtable">
						<table>
							<tr>
								<td valign="top">Aktif</td>
								<td>
								<input <?php if($pconfig['ru_enabled']) echo "checked"; ?> name="ru_enabled" type="checkbox">
								</td>
							</tr>
							<tr>
								<td valign="top">Form başlığı</td>
								<td>
									<input value="<?=$pconfig['ru_title']; ?>" placeholder="' Логин для доступа в Интернет' gibi." name="ru_title" type="text" id="ru_title" style="width:290px;" maxlength="40">
								</td>
							</tr>
							<tr>
								<td valign="top">Kullanıcı adı alanı metni</td>
								<td>
									<input value="<?=$pconfig['ru_uname']; ?>"  placeholder="'Имя пользователя' gibi." name="ru_uname" type="text" id="ru_uname" maxlength="20">
								</td>
							</tr>
							<tr>
								<td valign="top">Parola alanı metni</td>
								<td>
									<input value="<?=$pconfig['ru_password']; ?>"  placeholder="'Пароль' gibi." name="ru_password" type="text" id="ru_password" maxlength="20">
								</td>
							</tr>
							<tr>
								<td valign="top">Giriş butonu metni</td>
								<td>
									<input value="<?=$pconfig['ru_button']; ?>"  placeholder="'Вход' gibi." name="ru_button" type="text" id="ru_button" maxlength="20">
								</td>
							</tr>
						</table>
					</td>
				</tr>
				</tbody>
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
<script>
	function toggleForm()
	{
		var lang = jQuery("input[name='lang']:checked").val();
		jQuery("tr[name='row']").hide();
		jQuery("#text_"+lang).show();
	}

	function togglesCustomPages()
	{
		var page = jQuery("input[name='page_type']:checked").val();

		if(page == "custom")
		{
			jQuery("#custom_pages").show();
		}

		else
		{
			jQuery("#custom_pages").hide();
		}
	}

	jQuery(document).ready(function() {
		togglesCustomPages();
		toggleForm();
	});

	jQuery("input[name='lang']").on('change', function() {
		toggleForm();
	});

	jQuery("input[name='page_type']").on('change', function() {
		togglesCustomPages();
	});
</script>
</body>
</html>
