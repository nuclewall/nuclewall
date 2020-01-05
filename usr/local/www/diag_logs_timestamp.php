<?php
/*
	diag_logs_timestamp.php

	Copyright (C) 2013-2020 Ogün AÇIK
	All rights reserved.
*/

require('guiconfig.inc');

$timestamp_logfile = '/var/log/5651.log';

$nentries = $config['syslog']['nentries'];
if (!$nentries)
	$nentries = 50;

if ($_GET['act'] == 'del') {
	exec("rm $timestamp_logfile");
}

if ($_POST['filtertext'])
	$filtertext = htmlspecialchars($_POST['filtertext']);

if ($filtertext)
	$filtertextmeta="?filtertext=$filtertext";

$pgtitle = array('5651' , 'KAYIT VE İMZALAMA HAREKETLERİ');

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
				$tab_array[] = array('Genel Ayarlar', false, 'services_5651_logging.php');
				$tab_array[] = array('İmzalanmış Dosyalar', false, 'services_5651_signeds.php');
				$tab_array[] = array('Hareketler', true, 'diag_logs_timestamp.php');
				display_top_tabs($tab_array, true);
			?>
		</td>
  </tr>
  <tr>
    <td>
		<table class="tabcont" cellspacing="0" cellpadding="0">
			<tr>
				<td>
					<div style="margin-right: 10px;" class="pull-left">
						<a onclick="return confirm('5651 hareket kayıtlarını silmek istediğinizden emin misiniz?.')" class="btn" href="diag_logs_timestamp.php?act=del">
						<i class="icon-trash"></i>Sil</a>
					</div>

					<form class="form-search" id="clearform" name="clearform" action="diag_logs_timestamp.php" method="post">
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
								dump_timestamp_logs($timestamp_logfile, $nentries, array("$filtertext"));
							else
								dump_timestamp_logs($timestamp_logfile, $nentries);
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
