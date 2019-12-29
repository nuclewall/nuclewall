<?php
/*
	diag_logs_filter.php

	Copyright (C) 2013-2015 Ogün AÇIK
	All rights reserved.

	part of pfSense
	Copyright (C) 2004-2009 Scott Ullrich
	originally based on m0n0wall (http://m0n0.ch/wall)

	Copyright (C) 2003-2009 Manuel Kasper <mk@neon1.net>,
	Jim Pingle jim@pingle.org
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
require_once('filter_log.inc');

if($_GET['getrulenum'] or $_POST['getrulenum'])
{
	if($_GET['getrulenum'])
		$rulenum = $_GET['getrulenum'];
	if($_POST['getrulenum'])
		$rulenum = $_POST['getrulenum'];
	list($rulenum, $type) = explode(',', $rulenum);
	$rule = find_rule_by_number($rulenum, $type);
	echo 'Bu işlemi tetikleyen kural' . ":\n\n{$rule}";
	exit;
}

if($_GET['dnsip'] or $_POST['dnsip'])
{
	if($_GET['dnsip'])
		$dnsip = $_GET['dnsip'];
	if($_POST['dnsip'])
		$dnsip = $_POST['dnsip'];
	$host = get_reverse_dns($dnsip);
	if ($host == $ip) {
		$host = "PTR kaydı yok";
	}
	echo "IP: {$dnsip}\nSunucu Adı: {$host}";
	exit;
}

$filtertext = "";
if($_GET['filtertext'] or $_POST['filtertext']) {
	if($_GET['filtertext'])
		$filtertext = htmlspecialchars($_GET['filtertext']);
	if($_POST['filtertext'])
		$filtertext = htmlspecialchars($_POST['filtertext']);
}

$filter_logfile = "{$g['varlog_path']}/filter.log";

$nentries = $config['syslog']['nentries'];
if (!$nentries)
	$nentries = 50;

if ($_GET['act'] == 'del')
	clear_log_file($filter_logfile);

$pgtitle = array('OLAY GÜNLÜKLERİ' , 'GÜVENLİK DUVARI');

?>

<?php include('head.inc'); ?>
</head>
<body>
<script src="/javascript/filter_log.js" type="text/javascript"></script>
<?php include('fbegin.inc'); ?>

<table border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<?php
				$tab_array = array();
				$tab_array[] = array('Sistem', false, 'diag_logs.php');
				$tab_array[] = array('Güvenlik Duvarı', true, 'diag_logs_filter.php');
				$tab_array[] = array('DHCP', false, 'diag_logs_dhcp.php');
				$tab_array[] = array('MySQL', false, 'diag_logs_mysql.php');
				$tab_array[] = array('FreeRADIUS', false, 'diag_logs_radius.php');
				$tab_array[] = array('Ayarlar', false, 'diag_logs_settings.php');
				display_top_tabs($tab_array);
			?>
		</td>
	</tr>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<div style="margin-right: 10px;" class="pull-left">
							<a onclick="return confirm('Tüm güvenlik duvarı günlüklerini silmek istediğinizden emin misiniz?.')" class="btn" href="diag_logs_filter.php?act=del">
							<i class="icon-trash"></i>Sil</a>
						</div>

						<form class="form-search" id="clearform" name="clearform" action="diag_logs_filter.php" method="post">
							<input style="height:20px" type="text" id="filtertext" name="filtertext" value="<?=$filtertext;?>" class="input-medium">
							<button id="filtersubmit" name="filtersubmit" type="submit" class="btn"><i class="icon-search"></i>Ara</button>
						</form>
					</td>
				</tr>
				<?php
					$filterlog = conv_log_filter($filter_logfile, $nentries, $nentries + 100, $filtertext);
				?>
				<tr>
					<td>
						<table class="grids">
							<tr>
								<td class="head">İşlem</td>
								<td class="head">Tarih</td>
								<td class="head">Arayüz</td>
								<td class="head">Kaynak</td>
								<td class="head">Hedef</td>
								<td class="head">Protokol</td>
							</tr>
							<?php foreach ($filterlog as $filterent): ?>
							<tr>
								<td class="wall">
									<a href="#" onClick="javascript:getURL('diag_logs_filter.php?getrulenum=<?php echo "{$filterent['rulenum']},{$filterent['act']}"; ?>', outputrule);">
										<img border="0" src="<?php echo find_action_image($filterent['act']);?>" width="11" height="11" align="absmiddle" alt="<?php echo $filterent['act'];?>" title="<?php echo $filterent['act'];?>" />
									</a>
									<?php if ($filterent['count']) echo $filterent['count'];?>
								</td>
								<td class="cell logd">
									<?php
										$date = strtotime($filterent['time']);
										$entry_date_time =  strftime("%T -  %e %B", $date);
									?>
									<?php echo htmlspecialchars($entry_date_time);?>
								</td>
								<td class="cell center">
									<?php echo htmlspecialchars($filterent['interface']);?>
								</td>
								  <?php
								  $int = strtolower($filterent['interface']);
								  $proto = strtolower($filterent['proto']);

								  $srcstr = $filterent['srcip'] . get_port_with_service($filterent['srcport'], $proto);
								  $dststr = $filterent['dstip'] . get_port_with_service($filterent['dstport'], $proto);
								  ?>
								<td class="cell sd">
									<?php echo $srcstr;?>
								</td>
								<td class="cell sd">
									<?php echo $dststr;?>
								</td>
								<?php
								if ($filterent['proto'] == "TCP")
									$filterent['proto'] .= ":{$filterent['tcpflags']}";
								?>
								<td class="cell center">
									<?php echo htmlspecialchars($filterent['proto']);?>
								</td>
							</tr>
							<?php endforeach; ?>
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
