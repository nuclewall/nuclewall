<?php
/*
	hotspot_datasources_mysql.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.
*/

require('guiconfig.inc');

$pgtitle = array('HOTSPOT ', 'EXTERNAL DATA SOURCES ', 'MYSQL');

if (!is_array($config['datasources']['mysql']))
{
	$config['datasources']['mysql'] = array();
}

$pconfig['hostname'] = base64_decode($config['datasources']['mysql']['hostname']);
$pconfig['port'] = base64_decode($config['datasources']['mysql']['port']);
$pconfig['dbusername'] = htmlspecialchars(base64_decode($config['datasources']['mysql']['dbusername']));
$pconfig['dbpassword'] = htmlspecialchars(base64_decode($config['datasources']['mysql']['dbpassword']));
$pconfig['database_name'] = htmlspecialchars(base64_decode($config['datasources']['mysql']['database_name']));
$pconfig['table_name'] = htmlspecialchars(base64_decode($config['datasources']['mysql']['table_name']));
$pconfig['username_field'] = htmlspecialchars(base64_decode($config['datasources']['mysql']['username_field']));
$pconfig['password_field'] = htmlspecialchars(base64_decode($config['datasources']['mysql']['password_field']));

$datasource = $config['datasources']['external'];

if ($_POST)
{
	unset($input_errors);
	$pconfig = $_POST;

	if($datasource == "mysql")
	{
		$input_errors[] = "MySQL data source is in use now.
		Disable it from 'External Data Sources' page to change parameters.";
	}

	if (!empty($_POST['hostname']) && !is_hostname($_POST['hostname']))
	{
		$input_errors[] = 'A valid hostname must be specified.';
	}

	if (!is_port($_POST['port']))
	{
		$input_errors[] = 'A valid port number must be specified.';
	}

	if (!$input_errors)
	{
		$config['datasources']['mysql']['hostname'] = base64_encode($_POST['hostname']);
		$config['datasources']['mysql']['port'] = base64_encode($_POST['port']);
		$config['datasources']['mysql']['dbusername'] = base64_encode($_POST['dbusername']);
		$config['datasources']['mysql']['dbpassword'] = base64_encode($_POST['dbpassword']);
		$config['datasources']['mysql']['database_name'] = base64_encode($_POST['database_name']);
		$config['datasources']['mysql']['table_name'] = base64_encode($_POST['table_name']);
		$config['datasources']['mysql']['username_field'] = base64_encode($_POST['username_field']);
		$config['datasources']['mysql']['password_field'] = base64_encode($_POST['password_field']);

		write_config("(Hotspot) MySQL external data source configured");

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
<form action='hotspot_datasources_mysql.php' method='post' enctype='multipart/form-data' name='iform' id='iform'>
<table border='0' cellpadding='0' cellspacing='0'>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td colspan="2" class="listtopic">MySQL CONNECTION SETTINGS</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Hostname</td>
					<td class="vtable">
						<input value="<?=$pconfig['hostname'];?>" name="hostname" type="text" id="hostname" tabindex="1" maxlength="40"><br>
						Hostname or IP address of MySQL instance.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Port Number</td>
					<td class="vtable">
						<input value="<?=$pconfig['port'];?>" name="port" type="number" id="port" max="65535" min="1" step="1" tabindex="2"><br>
						Port number of MySQL instance. Default is <b>3306</b>'dir.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Username</td>
					<td class="vtable">
						<input value="<?=$pconfig['dbusername'];?>" name="dbusername" type="text" id="dbusername" tabindex="3" maxlength="128">
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Password</td>
					<td class="vtable">
						<input value="<?=$pconfig['dbpassword'];?>" name="dbpassword" type="password" id="dbpassword" tabindex="4" maxlength="128">
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Database Name</td>
					<td class="vtable">
						<input value="<?=$pconfig['database_name'];?>" name="database_name" type="text" id="database_name" tabindex="5" maxlength="128">
					</td>
				</tr>
				<tr>
					<td colspan="2" class="listtopic">DATA SOURCE SETTINGS</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Table Name</td>
					<td class="vtable">
						<input value="<?=$pconfig['table_name'];?>" name="table_name" type="text" id="table_name" tabindex="6" maxlength="128"><br>
						Table which hotspot users will be queried.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Username Field</td>
					<td class="vtable">
						<input value="<?=$pconfig['username_field'];?>" name="username_field" type="text" id="username_field" tabindex="7" maxlength="128"><br>
						Column name of table which maps to hotspot username field.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Password Field</td>
					<td class="vtable">
						<input value="<?=$pconfig['password_field'];?>"  name="password_field" type="text" id="password_field" tabindex="8"><br>
						Column name of table which maps to hotspot password field.
					</td>
				</tr>
				<tr>
					<td class="vncell"></td>
					<td class="vtable">
						<input tabindex="9" name="Submit" type="submit" class="btn btn-inverse" value="Save">
						<a tabindex="10" href="hotspot_datasources.php" class="btn">Back</a>
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
