<?php
/*
	diag_logs_mysql.php

	Copyright (C) 2013-2015 Ogün AÇIK
	All rights reserved.
*/

require('guiconfig.inc');

$mysql_logfile = '/var/db/mysql/' . $config['system']['hostname'] . '.' . $config['system']['domain'] . '.err';

$nentries = $config['syslog']['nentries'];
if (!$nentries)
	$nentries = 50;

if ($_GET['act'] == 'del') {
	exec("rm $mysql_logfile");
}

if ($_POST['filtertext'])
	$filtertext = htmlspecialchars($_POST['filtertext']);

if ($filtertext)
	$filtertextmeta="?filtertext=$filtertext";

$pgtitle = array('OLAY GÜNLÜKLERİ' , 'MYSQL SUNUCU');

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<table border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<?php
				$tab_array = array();
				$tab_array[] = array('Sistem', false, 'diag_logs.php');
				$tab_array[] = array('Güvenlik Duvarı', false, 'diag_logs_filter.php');
				$tab_array[] = array('DHCP', false, 'diag_logs_dhcp.php');
				$tab_array[] = array('MySQL', true, 'diag_logs_mysql.php');
				$tab_array[] = array('FreeRADIUS', false, 'diag_logs_radius.php');
				$tab_array[] = array('Ayarlar', false, 'diag_logs_settings.php');
				display_top_tabs($tab_array);
			?>
		</td>
  </tr>
  <tr>
    <td>
		<table class="tabcont" cellspacing="0" cellpadding="0">
			<tr>
				<td>
					<div style="margin-right: 10px;" class="pull-left">
						<a onclick="return confirm('MySQL sunucusunun olay günlüklerini silmek istediğinizden emin misiniz?.')" class="btn" href="diag_logs_mysql.php?act=del">
						<i class="icon-trash"></i>Sil</a>
					</div>

					<form class="form-search" id="clearform" name="clearform" action="diag_logs_mysql.php" method="post">
						<input style="height:20px" type="text" id="filtertext" name="filtertext" value="<?=$filtertext;?>" class="input-medium">
						<button id="filtersubmit" name="filtersubmit" type="submit" class="btn"><i class="icon-search"></i>Ara</button>
					</form>

					<table class="grids" width="100%">
						<tr>
							<td class="head">
								Tarih
							</td>
							<td class="head">
								Mesaj
							</td>
						</tr>
						<?php
							if($filtertext)
								dump_mysql($mysql_logfile, $nentries, array("$filtertext"));
							else
								dump_mysql($mysql_logfile, $nentries);
						?>
						</table>
					</td>
				</tr>
		</table>
	</td>
  </tr>
</table>
</div>
</body>
</html>
