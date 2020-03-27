<?php
/*
	hotspot_form_settings.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.
*/

require('guiconfig.inc');
require('hotspot.inc');

$pgtitle = array('SERVICES ', 'HOTSPOT ', 'USER WELCOME PAGE');

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
	$reqdfieldsn = array("Company Name", "Page Text");

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if(strlen($_POST['company']) > 25)
		$input_errors[] = 'Company name must be shorter than 25 characters.';

    if (is_uploaded_file($_FILES['logo']['tmp_name']))
	{
		if(!getimagesize($_FILES['logo']['tmp_name']))
			$input_errors[] = "'{$_FILES['logo']['name']}' is not a image file.";

		$size = filesize($_FILES['logo']['tmp_name']);

		if($size > 524288)
			$input_errors[] = 'Logo file size must be maximum 512 KB.';
	}

	$tr_enabled = isset($_POST['tr_enabled']);
	$en_enabled = isset($_POST['en_enabled']);
	$de_enabled = isset($_POST['de_enabled']);
	$ru_enabled = isset($_POST['ru_enabled']);

	if($_POST['page_type'] == "custom")
	{
		if(!$tr_enabled and !$en_enabled and !$de_enabled and !$ru_enabled)
			$input_errors[] = 'You have to enabled at least one language to use custom page texts.';

		$default_enabled = $_POST['default_lang'] . "_enabled";

		if(!${$default_enabled})
			$input_errors[] = 'The language you set default is not activated.';
	}

	if($tr_enabled)
	{
		$reqdfields = split(" ", "tr_title tr_uname tr_password tr_button");
		$reqdfieldsn = array("Form title(Turkish)", "Username field text(Turkish)", "Password field text(Turkish)", "Login button text(Turkish)");
		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	}

	if(strlen($_POST['tr_title']) > 40)
		$input_errors[] = 'Form title must be shorter than 40 characters.(Turkish)';
	if(strlen($_POST['tr_uname']) > 20)
		$input_errors[] = 'Username field text must be shorter than 20 characters.(Turkish)';
	if(strlen($_POST['tr_password']) > 20)
		$input_errors[] = 'Password field text must be shorter than 20 characters.(Turkish)';
	if(strlen($_POST['tr_button']) > 15)
		$input_errors[] = 'Login button text must be shorter than 15 characters.(Turkish)';

	if($en_enabled)
	{
		$reqdfields = split(" ", "en_title en_uname en_password en_button");
		$reqdfieldsn = array("Form title(English)", "Username field text(English)", "Password field text(English)", "Login button text(English)");
		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	}

	if(strlen($_POST['en_title']) > 40)
		$input_errors[] = 'Form title must be shorter than 40 characters.(English)';
	if(strlen($_POST['en_uname']) > 20)
		$input_errors[] = 'Username field text must be shorter than 20 characters.(English)';
	if(strlen($_POST['en_password']) > 20)
		$input_errors[] = 'Password field text must be shorter than 20 characters.(English)';
	if(strlen($_POST['en_button']) > 15)
		$input_errors[] = 'Login button text must be shorter than 15 characters.(English)';

	if($de_enabled)
	{
		$reqdfields = split(" ", "de_title de_uname de_password de_button");
		$reqdfieldsn = array("Form title(German)", "Username field text(German)", "Password field text(German)", "Login button text(German)");
		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	}

	if(strlen($_POST['de_title']) > 40)
		$input_errors[] = 'Form title must be shorter than 40 characters.(German)';
	if(strlen($_POST['de_uname']) > 20)
		$input_errors[] = 'Username field text must be shorter than 20 characters.(German)';
	if(strlen($_POST['de_password']) > 20)
		$input_errors[] = 'Password field text must be shorter than 20 characters.(German)';
	if(strlen($_POST['de_button']) > 15)
		$input_errors[] = 'Login button text must be shorter than 15 characters.(German)';

	if($ru_enabled)
	{
		$reqdfields = split(" ", "ru_title ru_uname ru_password ru_button");
		$reqdfieldsn = array("Form title(Russian)", "Username field text(Russian)", "Password field text(Russian)", "Login button text(Russian)");
		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	}

	if(strlen($_POST['ru_title']) > 40)
		$input_errors[] = 'Form title must be shorter than 40 characters.(Russian)';
	if(strlen($_POST['ru_uname']) > 20)
		$input_errors[] = 'Username field text must be shorter than 20 characters.(Russian)';
	if(strlen($_POST['ru_password']) > 20)
		$input_errors[] = 'Password field text must be shorter than 20 characters.(Russian)';
	if(strlen($_POST['ru_button']) > 15)
		$input_errors[] = 'Login button text must be shorter than 15 characters.(Russian)';

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
			$input_errors[] = 'Unable to create pages.';
		}

		initHtmlFiles();

		$savemsg = 'The changes have been applied successfully.';
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
				$tab_array[] = array('General Settings', false, 'hotspot_settings.php');
				$tab_array[] = array('External Data Sources', false, 'hotspot_datasources.php');
				$tab_array[] = array('User Welcome Page', true, 'hotspot_form_settings.php');
				display_top_tabs($tab_array, true);
			?>
		</td>
	</tr>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td valign="top" class="vncell">Show Pages</td>
					<td class="vtable">
						<a target="_blank" class="btn btn-link" href="hotspot.html">PC and Tablet</a>
						<a target="_blank" class="btn btn-link" href="hotspot_mobile.html">Mobile</a>
					</td>
				</tr>
				<tr>
					<td colspan="2" class="listtopic">GENERAL SETTINGS</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Default Language</td>
					<td class="vtable">
						<select id="default_lang" name="default_lang">
							<option <?php if($pconfig['default_lang'] == "tr") echo "selected"; ?> value="tr">Turkish</option>
							<option <?php if($pconfig['default_lang'] == "en") echo "selected"; ?> value="en">English</option>
							<option <?php if($pconfig['default_lang'] == "de") echo "selected"; ?> value="de">German</option>
							<option <?php if($pconfig['default_lang'] == "ru") echo "selected"; ?> value="ru">Russian</option>
						</select>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Logo<br>For PCs</td>
					<td class="vtable">
					<img width="90px" height="75px" class="thumbnail" src="pages/img/captiveportal-nuclewall?cache=<?php echo filemtime($pageTarget);?>">
						<input style="margin-top:10px;" name="logo" type="file" id="logo">
						<p>
						<b>Note: </b>Logo file size must be maximum 512 KB.<br>
							Recommended image resolution is 120x100 pixels.
						</p>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Company Name<br>(For mobile devices)</td>
					<td class="vtable">
						<input value="<?=$pconfig['company'];?>" name="company" type="text" required id="company" maxlength="25" width="100">
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Page Texts</td>
					<td class="vtable">
						<div class="form-inline">
							<div class="controls-row">
								<label class="radio inline">
									<input <?php if($pconfig['page_type'] == "default") echo "checked"; ?> name="page_type" type="radio" value="default"/>
									Default
								</label>
								<label class="radio inline">
									<input <?php if($pconfig['page_type'] == "custom") echo "checked"; ?> name="page_type" type="radio" value="custom"/>
									Custom
								</label>
							</div>
						</div>
					</td>
				</tr>
				<tbody id="custom_pages">
				<tr>
					<td colspan="2" class="listtopic">CUSTOM PAGE TEXTS</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Language</td>
					<td class="vtable">
						<div class="form-inline">
							<div class="controls-row">
								<label class="radio inline">
									<input checked name="lang" type="radio" value="tr"/>
									Turkish
								</label>
								<label class="radio inline">
									<input name="lang" type="radio" value="en"/>
									English
								</label>
								<label class="radio inline">
									<input name="lang" type="radio" value="de"/>
									German
								</label>
								<label class="radio inline">
									<input name="lang" type="radio" value="ru"/>
									Russian
								</label>
							</div>
						</div>
					</td>
				</tr>
				<tr name="row" id="text_tr">
					<td valign="top" class="vncell">Turkish</td>
					<td class="vtable">
						<table>
							<tr>
								<td valign="top">Enabled</td>
								<td>
								<input <?php if($pconfig['tr_enabled']) echo "checked"; ?> name="tr_enabled" type="checkbox">
								</td>
							</tr>
							<tr>
								<td valign="top">Form title</td>
								<td>
									<input value="<?=$pconfig['tr_title']; ?>" placeholder="Ex. 'İnternet erişimi için giriş yapın'." name="tr_title" type="text" id="tr_title" style="width:290px;" maxlength="40">

								</td>
							</tr>
							<tr>
								<td valign="top">Username field text</td>
								<td>
									<input value="<?=$pconfig['tr_uname']; ?>" placeholder="Ex. 'Username', 'Oda No'." name="tr_uname" type="text" id="tr_uname" maxlength="20">
								</td>
							</tr>
							<tr>
								<td valign="top">Password field text</td>
								<td>
									<input value="<?=$pconfig['tr_password']; ?>" placeholder="Ex. 'Password', 'TC Kimlik No'." name="tr_password" type="text" id="tr_password" maxlength="20">
								</td>
							</tr>
							<tr>
								<td valign="top">Login button text</td>
								<td>
									<input value="<?=$pconfig['tr_button']; ?>" placeholder="Ex. 'Giriş', 'Oturum Aç'." name="tr_button" type="text" id="tr_button" maxlength="20">
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr name="row" id="text_en">
					<td valign="top" class="vncell">English</td>
					<td class="vtable">
						<table>
							<tr>
								<td valign="top">Enabled</td>
								<td>
								<input <?php if($pconfig['en_enabled']) echo "checked"; ?> name="en_enabled" type="checkbox">
								</td>
							</tr>
							<tr>
								<td valign="top">Form title</td>
								<td>
									<input value="<?=$pconfig['en_title']; ?>" placeholder="Ex. 'Log in to access the Internet'." name="en_title" type="text" id="en_title" style="width:290px;" maxlength="40">
								</td>
							</tr>
							<tr>
								<td valign="top">Username field text</td>
								<td>
									<input value="<?=$pconfig['en_uname']; ?>" placeholder="Ex. 'Username', 'Room Number'." name="en_uname" type="text" id="en_uname" maxlength="20">
								</td>
							</tr>
							<tr>
								<td valign="top">Password field text</td>
								<td>
									<input value="<?=$pconfig['en_password']; ?>" placeholder="Ex. 'Password', 'Passport Number'." name="en_password" type="text" id="en_password" maxlength="20">
								</td>
							</tr>
							<tr>
								<td valign="top">Login button text</td>
								<td>
									<input value="<?=$pconfig['en_button']; ?>" placeholder="'Log In'." name="en_button" type="text" id="en_button" maxlength="20">
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr name="row" id="text_de">
					<td valign="top" class="vncell">German</td>
					<td class="vtable">
						<table>
							<tr>
								<td valign="top">Enabled</td>
								<td>
								<input <?php if($pconfig['de_enabled']) echo "checked"; ?> name="de_enabled" type="checkbox">
								</td>
							</tr>
							<tr>
								<td valign="top">Form title</td>
								<td>
									<input value="<?=$pconfig['de_title']; ?>" placeholder="Ex. 'Anmelden um auf das Internet zuzugreifen'." name="de_title" type="text" id="de_title" style="width:290px;" maxlength="40">
								</td>
							</tr>
							<tr>
								<td valign="top">Username field text</td>
								<td>
									<input value="<?=$pconfig['de_uname']; ?>" placeholder="Ex. 'Benutzername'." name="de_uname" type="text" id="de_uname" maxlength="20">
								</td>
							</tr>
							<tr>
								<td valign="top">Password field text</td>
								<td>
									<input value="<?=$pconfig['de_password']; ?>"  placeholder="Ex. 'Passwort'." name="de_password" type="text" id="de_password" maxlength="20">
								</td>
							</tr>
							<tr>
								<td valign="top">Login button text</td>
								<td>
									<input value="<?=$pconfig['de_button']; ?>" placeholder="Ex. 'Anmelden'." name="de_button" type="text" id="de_button" maxlength="20">
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr name="row" id="text_ru">
					<td valign="top" class="vncell">Russian</td>
					<td class="vtable">
						<table>
							<tr>
								<td valign="top">Enabled</td>
								<td>
								<input <?php if($pconfig['ru_enabled']) echo "checked"; ?> name="ru_enabled" type="checkbox">
								</td>
							</tr>
							<tr>
								<td valign="top">Form Title</td>
								<td>
									<input value="<?=$pconfig['ru_title']; ?>" placeholder="Ex. 'Логин для доступа в Интернет'." name="ru_title" type="text" id="ru_title" style="width:290px;" maxlength="40">
								</td>
							</tr>
							<tr>
								<td valign="top">Username field text</td>
								<td>
									<input value="<?=$pconfig['ru_uname']; ?>"  placeholder="Ex. 'Имя пользователя'." name="ru_uname" type="text" id="ru_uname" maxlength="20">
								</td>
							</tr>
							<tr>
								<td valign="top">Password field text</td>
								<td>
									<input value="<?=$pconfig['ru_password']; ?>"  placeholder="Ex. 'Пароль'." name="ru_password" type="text" id="ru_password" maxlength="20">
								</td>
							</tr>
							<tr>
								<td valign="top">Login button text</td>
								<td>
									<input value="<?=$pconfig['ru_button']; ?>"  placeholder="Ex.'Вход'." name="ru_button" type="text" id="ru_button" maxlength="20">
								</td>
							</tr>
						</table>
					</td>
				</tr>
				</tbody>
				<tr>
					<td class="vncell"></td>
					<td class="vtable">
						<input name="Submit" type="submit" class="btn btn-inverse" value="Save">
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
