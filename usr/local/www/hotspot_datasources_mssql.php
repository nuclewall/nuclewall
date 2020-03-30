<?php
/*
	hotspot_datasources_mssql.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.
*/

require('guiconfig.inc');

$pgtitle = array('HOTSPOT ', 'HARİCİ VERİ KAYNAKLARI ', ' MICROSOFT SQL SERVER');

if (!is_array($config['datasources']['sqlserver']))
{
	$config['datasources']['sqlserver'] = array();
}

$pconfig['hostname'] = base64_decode($config['datasources']['sqlserver']['hostname']);
$pconfig['port'] = base64_decode($config['datasources']['sqlserver']['port']);
$pconfig['dbusername'] = htmlspecialchars(base64_decode($config['datasources']['sqlserver']['dbusername']));
$pconfig['dbpassword'] = htmlspecialchars(base64_decode($config['datasources']['sqlserver']['dbpassword']));
$pconfig['database_name'] = htmlspecialchars(base64_decode($config['datasources']['sqlserver']['database_name']));
$pconfig['table_name'] = htmlspecialchars(base64_decode($config['datasources']['sqlserver']['table_name']));
$pconfig['username_field'] = htmlspecialchars(base64_decode($config['datasources']['sqlserver']['username_field']));
$pconfig['password_field'] = htmlspecialchars(base64_decode($config['datasources']['sqlserver']['password_field']));

$datasource = $config['datasources']['external'];

if ($_POST)
{
	unset($input_errors);
	$pconfig = $_POST;

	if($datasource == "sqlserver")
	{
		$input_errors[] = "Microsoft SQL Server veri kaynağı şu anda kullanımda olduğu için değişiklik yapılamıyor.
		Değişiklik yapabilmek için, 'Harici Veri Kaynakları' sayfasındaki 'Aktif Veri Kaynağı' seçeneğini 'Hiçbiri' olarak değiştirin.";
	}

	if (!empty($_POST['hostname']) && !is_hostname($_POST['hostname']))
	{
		$input_errors[] = 'Geçerli bir sunucu adı girmelisiniz.';
	}

	if (!is_port($_POST['port']))
	{
		$input_errors[] = 'Geçerli bir port numarası girmelisiniz.';
	}

	if (!$input_errors)
	{
		$config['datasources']['sqlserver']['hostname'] = base64_encode($_POST['hostname']);
		$config['datasources']['sqlserver']['port'] = base64_encode($_POST['port']);
		$config['datasources']['sqlserver']['dbusername'] = base64_encode($_POST['dbusername']);
		$config['datasources']['sqlserver']['dbpassword'] = base64_encode($_POST['dbpassword']);
		$config['datasources']['sqlserver']['database_name'] = base64_encode($_POST['database_name']);
		$config['datasources']['sqlserver']['table_name'] = base64_encode($_POST['table_name']);
		$config['datasources']['sqlserver']['username_field'] = base64_encode($_POST['username_field']);
		$config['datasources']['sqlserver']['password_field'] = base64_encode($_POST['password_field']);

		write_config("(Hotspot) SQL Server harici veri kaynagi yapilandirildi");

		$savemsg = 'Değişiklikler başarıyla kaydedildi.';

	}
}

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>


<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<form action='hotspot_datasources_mssql.php' method='post' enctype='multipart/form-data' name='iform' id='iform'>
<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td colspan="2" class="listtopic">Microsoft SQL Server VERİTABANI BAĞLANTI AYARLARI</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Sunucu Adı</td>
					<td class="vtable">
						<input value="<?=$pconfig['hostname'];?>" name="hostname" type="text" id="hostname" tabindex="1" maxlength="40"><br>
						SQL Server veritabanının kurulu olduğu sunucunun adını(hostname), tam adını(fqdn) veya IP adresini girin.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Port Numarası</td>
					<td class="vtable">
						<input value="<?=$pconfig['port'];?>" name="port" type="number" id="port" max="65535" min="1" step="1" tabindex="2"><br>
						SQL Server veritabanının hizmet verdiği port numarasını girin. Varsayılan port numarası <b>1433</b>'dir.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Kullanıcı Adı</td>
					<td class="vtable">
						<input value="<?=$pconfig['dbusername'];?>" name="dbusername" type="text" id="dbusername" tabindex="3" maxlength="128"><br>
						Veritabanı kullanıcı adını girin.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Parola</td>
					<td class="vtable">
						<input value="<?=$pconfig['dbpassword'];?>" name="dbpassword" type="password" id="dbpassword" tabindex="4" maxlength="128"><br>
						Veritabanı parolasını girin.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Veritabanı Adı</td>
					<td class="vtable">
						<input value="<?=$pconfig['database_name'];?>" name="database_name" type="text" id="database_name" tabindex="5" maxlength="128"><br>
						Kullanıcı sorgulamasının yapılacağı veritabanı adını girin.
					</td>
				</tr>
				<tr>
					<td colspan="2" class="listtopic">VERİ KAYNAĞI AYARLARI</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Tablo Adı</td>
					<td class="vtable">
						<input value="<?=$pconfig['table_name'];?>" name="table_name" type="text" id="table_name" tabindex="6" maxlength="128"><br>
						Kullanıcı sorgulamasının yapılacağı tablo adını girin.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Kullanıcı Adı Alanı</td>
					<td class="vtable">
						<input value="<?=$pconfig['username_field'];?>" name="username_field" type="text" id="username_field" tabindex="7" maxlength="128"><br>
						Hotspot kullanıcı sorgulamasında kullanıcı adı olarak sorgulanmasını istediğiniz tablo alanının adını girin.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Parola Alanı</td>
					<td class="vtable">
						<input value="<?=$pconfig['password_field'];?>"  name="password_field" type="text" id="password_field" tabindex="8"><br>
						Hotspot kullanıcı sorgulamasında parola olarak sorgulanmasını istediğiniz tablo alanının adını girin.
					</td>
				</tr>
				<tr>
					<td class="vncell"></td>
					<td class="vtable">
						<input tabindex="9" name="Submit" type="submit" class="btn btn-inverse" value="Kaydet">
						<a tabindex="10" href="hotspot_datasources.php" class="btn">Geri</a>
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
