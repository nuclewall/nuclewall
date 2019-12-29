<?php
/*
	diag_logs_auth.php

	Copyright (C) 2013-2015 Ogün AÇIK
	All rights reserved.

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

require('guiconfig.inc');

setlocale(LC_ALL, 'tr_TR.ISO8859-9');

$portal_logfile = "{$g['varlog_path']}/portalauth.log";

$nentries = $config['syslog']['nentries'];
if (!$nentries)
	$nentries = 50;

if ($_GET['act'] == 'del')
	clear_log_file($portal_logfile);

if ($_POST['filtertext'])
	$filtertext = htmlspecialchars($_POST['filtertext']);

if ($filtertext)
	$filtertextmeta="?filtertext=$filtertext";

$pgtitle = array('HOTSPOT ', 'OTURUM HAREKETLERÝ');

?>

<?php include('headansi.inc'); ?>
</head>
<body>
<?php include('fbeginansi.inc'); ?>

<table border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<?php
				$tab_array = array();
				$tab_array[] = array('Aktif Oturumlar', false, 'hotspot_status.php');
				$tab_array[] = array('Yerel Kullanýcýlar', false, 'hotspot_users.php');
				$tab_array[] = array('Özel Ýzinli MAC Adresleri', false, 'hotspot_macs.php');
				$tab_array[] = array('Engellenmiþ MAC Adresleri', false, 'hotspot_blocklist.php');
				$tab_array[] = array('Oturum Hareketleri', true, 'hotspot_logs.php');
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
							<a onclick="return confirm('Tüm hotspot olay günlüklerini silmek istediðinizden emin misiniz?.')" class="btn" href="hotspot_logs.php?act=del">
							<i class="icon-trash"></i>Sil</a>
						</div>

						<form class="form-search" id="clearform" name="clearform" action="hotspot_logs.php" method="post">
							<input style="height:20px" type="text" id="filtertext" name="filtertext" value="<?=$filtertext;?>" class="input-medium">
							<button id="filtersubmit" name="filtersubmit" type="submit" class="btn"><i class="icon-search"></i>Ara</button>
						</form>

						<table class="grids" width="100%">
							<tr>
								<td class="head">
									Tarih
								</td>
								<td class="head">
									Eylem
								</td>
							</tr>
								<?php
									if($filtertext)
										dump_clog_auth($portal_logfile, $nentries, array("$filtertext"));
									else
										dump_clog_auth($portal_logfile, $nentries);
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
