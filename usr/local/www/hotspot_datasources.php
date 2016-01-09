<?php
/*
	hotspot_datasources.php
	
	Copyright (C) 2013-2015 Ogün AÇIK
	All rights reserved.
*/

require('guiconfig.inc');
require_once('connections.inc');
require('datasources.inc');

$pgtitle = array('HOTSPOT ', 'HARİCİ VERİ KAYNAKLARI');

$pconfig['datasource'] = $config['datasources']['external'];

$mysql_hostname = base64_decode($config['datasources']['mysql']['hostname']);
$mysql_port = base64_decode($config['datasources']['mysql']['port']);
$mysql_username = base64_decode($config['datasources']['mysql']['dbusername']);
$mysql_password = base64_decode($config['datasources']['mysql']['dbpassword']);
$mysql_db = base64_decode($config['datasources']['mysql']['database_name']);
$mysql_table = base64_decode($config['datasources']['mysql']['table_name']);
$mysql_uname_field = base64_decode($config['datasources']['mysql']['username_field']);
$mysql_pass_field = base64_decode($config['datasources']['mysql']['password_field']);

$pgsql_hostname = base64_decode($config['datasources']['pgsql']['hostname']);
$pgsql_port = base64_decode($config['datasources']['pgsql']['port']);
$pgsql_username = base64_decode($config['datasources']['pgsql']['dbusername']);
$pgsql_password = base64_decode($config['datasources']['pgsql']['dbpassword']);
$pgsql_db = base64_decode($config['datasources']['pgsql']['database_name']);
$pgsql_table = base64_decode($config['datasources']['pgsql']['table_name']);
$pgsql_uname_field = base64_decode($config['datasources']['pgsql']['username_field']);
$pgsql_pass_field = base64_decode($config['datasources']['pgsql']['password_field']);

$sqlserver_hostname = base64_decode($config['datasources']['sqlserver']['hostname']);
$sqlserver_port = base64_decode($config['datasources']['sqlserver']['port']);
$sqlserver_username = base64_decode($config['datasources']['sqlserver']['dbusername']);
$sqlserver_password = base64_decode($config['datasources']['sqlserver']['dbpassword']);
$sqlserver_db = base64_decode($config['datasources']['sqlserver']['database_name']);
$sqlserver_table = base64_decode($config['datasources']['sqlserver']['table_name']);
$sqlserver_uname_field = base64_decode($config['datasources']['sqlserver']['username_field']);
$sqlserver_pass_field = base64_decode($config['datasources']['sqlserver']['password_field']);

if(!empty($mysql_hostname) && !empty($mysql_port) && !empty($mysql_username) && !empty($mysql_password) && !empty($mysql_db))
{
	$mysql_connection = MySQLConnect($mysql_hostname, $mysql_port, $mysql_username, $mysql_password, $mysql_db);
	
	if($mysql_connection)
	{
		$mysql_connection_status = "Bağlandı";
		$mysql_connection_class = "label label-success";
		
		if(!empty($mysql_table) && !empty($mysql_uname_field) && !empty($mysql_pass_field))
		{
			$mysql_available = true;
			
			$mysql_data = checkData($mysql_connection, $mysql_table, $mysql_uname_field, $mysql_pass_field);
			
			if($mysql_data)
			{
				$mysql_data_status = "Kullanılabilir veri var";
				$mysql_data_class = "label label-success";
			}
			
			else
			{
				$mysql_data_status = "Veri bulunamadı";
				$mysql_data_class = "label label-important";
			}
		}
		
		else
		{
			$mysql_data_status = "Yapılandırılmadı";
			$mysql_data_class = "label";
		}
	}
	
	else
	{
		$mysql_connection_status = "Bağlantı yok";
		$mysql_connection_class = "label label-important";
		$mysql_data_status = "Bağlantı yok";
		$mysql_data_class = "label label-important";
	}
}

else
{
	$mysql_connection_status = "Yapılandırılmadı";
	$mysql_connection_class = "label";
	$mysql_data_status = "Bağlantı yok";
	$mysql_data_class = "label label-important";
}

if(!empty($pgsql_hostname) && !empty($pgsql_port) && !empty($pgsql_username) && !empty($pgsql_password) && !empty($pgsql_db))
{
	$pgsql_connection = PgSQLConnect($pgsql_hostname, $pgsql_port, $pgsql_username, $pgsql_password, $pgsql_db);
	
	if($pgsql_connection)
	{
		$pgsql_connection_status = "Bağlandı";
		$pgsql_connection_class = "label label-success";
		
		if(!empty($pgsql_table) && !empty($pgsql_uname_field) && !empty($pgsql_pass_field))
		{
			$pgsql_available = true;
			
			$pgsql_data = checkData($pgsql_connection, $pgsql_table, $pgsql_uname_field, $pgsql_pass_field);
			
			if($pgsql_data)
			{
				$pgsql_data_status = "Kullanılabilir veri var";
				$pgsql_data_class = "label label-success";
			}
			
			else
			{
				$pgsql_data_status = "Veri bulunamadı";
				$pgsql_data_class = "label label-important";
			}
		}
		
		else
		{
			$pgsql_data_status = "Yapılandırılmadı";
			$pgsql_data_class = "label";
		}
	}
	
	else
	{
		$pgsql_connection_status = "Bağlantı yok";
		$pgsql_connection_class = "label label-important";
		$pgsql_data_status = "Bağlantı yok";
		$pgsql_data_class = "label label-important";
	}
}

else
{
	$pgsql_connection_status = "Yapılandırılmadı";
	$pgsql_connection_class = "label";
	$pgsql_data_status = "Bağlantı yok";
	$pgsql_data_class = "label label-important";
}

if(!empty($sqlserver_hostname) && !empty($sqlserver_port) && !empty($sqlserver_username) && !empty($sqlserver_password) && !empty($sqlserver_db))
{
	$sqlserver_connection = MSSQLConnect($sqlserver_hostname, $sqlserver_port, $sqlserver_username, $sqlserver_password, $sqlserver_db);
	
	if($sqlserver_connection)
	{
		$sqlserver_connection_status = "Bağlandı";
		$sqlserver_connection_class = "label label-success";
		
		if(!empty($sqlserver_table) && !empty($sqlserver_uname_field) && !empty($sqlserver_pass_field))
		{
			$sqlserver_available = true;
			
			$sqlserver_data = checkData($sqlserver_connection, $sqlserver_table, $sqlserver_uname_field, $sqlserver_pass_field);
			
			if($sqlserver_data)
			{
				$sqlserver_data_status = "Kullanılabilir veri var";
				$sqlserver_data_class = "label label-success";
			}
			
			else
			{
				$sqlserver_data_status = "Veri bulunamadı";
				$sqlserver_data_class = "label label-important";
			}
		}
		
		else
		{
			$sqlserver_data_status = "Yapılandırılmadı";
			$sqlserver_data_class = "label";
		}
	}
	
	else
	{
		$sqlserver_connection_status = "Bağlantı yok";
		$sqlserver_connection_class = "label label-important";
		$sqlserver_data_status = "Bağlantı yok";
		$sqlserver_data_class = "label label-important";
	}
}

else
{
	$sqlserver_connection_status = "Yapılandırılmadı";
	$sqlserver_connection_class = "label";
	$sqlserver_data_status = "Bağlantı yok";
	$sqlserver_data_class = "label label-important";
}


if ($_POST)
{
	$pconfig = $_POST;

	$config['datasources']['external'] = $_POST['datasource'];
	
	write_config();
	
	if($_POST['datasource'] == 'mysql' and $mysql_available)
	{
		 MySQLConnectionFile($mysql_hostname, $mysql_port, $mysql_username, $mysql_password, $mysql_db, $mysql_table, $mysql_uname_field, $mysql_pass_field);
	}
	
	else if($_POST['datasource'] == 'postgres' and $pgsql_available)
	{
		 PgSQLConnectionFile($pgsql_hostname, $pgsql_port, $pgsql_username, $pgsql_password, $pgsql_db, $pgsql_table, $pgsql_uname_field, $pgsql_pass_field);
	}
	
	else if($_POST['datasource'] == 'sqlserver' and $sqlserver_available)
	{
		 SqlServerConnectionFile($sqlserver_hostname, $sqlserver_port, $sqlserver_username, $sqlserver_password, $sqlserver_db, $sqlserver_table, $sqlserver_uname_field, $sqlserver_pass_field);
	}
	
	else
	{
		$external_file = '/etc/inc/external.inc';
		file_put_contents($external_file, '');
	}
	
	$savemsg = 'Değişiklikler başarıyla kaydedildi.';
}

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php if ($savemsg) print_info_box($savemsg); ?>

<form action='hotspot_datasources.php' method='post' enctype='multipart/form-data' name='iform' id='iform'>
<table border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td class="tabnavtbl">
			<?php
				$tab_array = array();
				$tab_array[] = array('Genel Ayarlar', false, 'hotspot_settings.php');
				$tab_array[] = array('Harici Veri Kaynakları', true, 'hotspot_datasources.php');
				$tab_array[] = array('Kullanıcı Karşılama Sayfası', false, 'hotspot_form_settings.php');
				display_top_tabs($tab_array, true);
			?>
		</td>
	</tr>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td valign="top" class="vncell">Aktif Veri Kaynağı</td>
					<td class="vtable">
						Yerel + 
						<select name="datasource" required id="datasource">
							<option <?php if($pconfig['datasource'] == "none") echo "selected"; ?> value="none">Hiçbiri</option>	
						<?php if($mysql_available): ?>
							<option <?php if($pconfig['datasource'] == "mysql") echo "selected"; ?> value="mysql">MySQL</option>
						<?php endif; ?>
						<?php if($pgsql_available): ?>
							<option <?php if($pconfig['datasource'] == "postgres") echo "selected"; ?> value="postgres">PostgreSQL</option>
						<?php endif; ?>
						<?php if($sqlserver_available): ?>
							<option <?php if($pconfig['datasource'] == "sqlserver") echo "selected"; ?> value="sqlserver">Microsoft SQL Server</option>
						<?php endif; ?>
						</select>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Veri Kaynakları</td>
					<td class="vtable">
						<table class="grids data">
							<tr>
								<td class="head">Veri Kaynağı</td>
								<td class="head">Bağlantı Durumu</td>
								<td class="head">Veri Kullanılabilirliği</td>
							</tr>
							<tr>
								<td id="usr" class="cell data">
									<a href="hotspot_datasources_mysql.php" class="btn btn-link">MySQL</a>
								</td>
								<td class="cell description dt"><span class="<?php echo $mysql_connection_class; ?>"><?php echo $mysql_connection_status; ?></span></td>
								<td class="cell description dt"><span class="<?php echo $mysql_data_class; ?>"><?php echo $mysql_data_status; ?></span></td>
							</tr>
							<tr>
								<td id="usr" class="cell data">
									<a href="hotspot_datasources_pgsql.php" class="btn btn-link">PostgreSQL</a>
								</td>
								<td class="cell description dt"><span class="<?php echo $pgsql_connection_class; ?>"><?php echo $pgsql_connection_status; ?></td>
								<td class="cell description dt"><span class="<?php echo $pgsql_data_class; ?>"><?php echo $pgsql_data_status; ?></td>
							</tr>
							<tr>
								<td id="usr" class="cell data">
									<a href="hotspot_datasources_mssql.php" class="btn btn-link">Microsoft SQL Server</a>
								</td>
								<td class="cell description dt"><span class="<?php echo $sqlserver_connection_class; ?>"><?php echo $sqlserver_connection_status; ?></td>
								<td class="cell description dt"><span class="<?php echo $sqlserver_data_class; ?>"><?php echo $sqlserver_data_status; ?></td>
							</tr>
						</table>
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
