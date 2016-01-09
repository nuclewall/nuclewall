<?php
/*
	system.widget.php
	
	Copyright (C) 2013-2015 Ogün AÇIK
	All rights reserved.
	
	Copyright 2007 Scott Dale
	Part of pfSense widgets (www.pfsense.com)
	originally based on m0n0wall (http://m0n0.ch/wall)

	Copyright (C) 2004-2005 T. Lechat <dev@lechat.org>, Manuel Kasper <mk@neon1.net>
	and Jonathan Watt <jwatt@jwatt.org>.
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

require_once('functions.inc');
require_once('guiconfig.inc');
include_once("includes/functions.inc.php");
setlocale(LC_ALL, 'tr_TR.UTF-8');
?>
<table width="100%" cellspacing="0" cellpadding="0">
	<tbody>
		<tr>
			<td class="vncellt">Tam sunucu adı</td>
			<td class="listr"><span class="label label-info"><?php echo $config['system']['hostname'] . '.' . $config['system']['domain']; ?></span></td>
		</tr>
		<tr>
			<td valign="top" class="vncellt">Sürüm</td>
			<td class="listr">
				<?php readfile("/etc/version"); ?>
				(<?php echo php_uname("m"); ?>)
			</td>
		</tr>
		<tr>
			<td class="vncellt">İşlemci mimarisi</td>
			<td class="listr">
			<?php 
				$cpumodel = "";
				exec("/sbin/sysctl -n hw.model", $cpumodel);
				$cpumodel = implode(" ", $cpumodel);
				echo (htmlspecialchars($cpumodel));

				$cpufreqs = "";
				exec("/sbin/sysctl -n dev.cpu.0.freq_levels", $cpufreqs);
				$cpufreqs = explode(" ", trim($cpufreqs[0]));
				$maxfreq = explode("/", $cpufreqs[0]);
				$maxfreq = $maxfreq[0];
				$curfreq = "";
				exec("/sbin/sysctl -n dev.cpu.0.freq", $curfreq);
				$curfreq = trim($curfreq[0]);
				if ($curfreq != $maxfreq)
					echo "<br/>Current: {$curfreq} MHz, Max: {$maxfreq} MHz";
			?>
			</td>
		</tr>
		<tr>
			<td class="vncellt">Açık kalma süresi</td>
			<td class="listr">
			<?= htmlspecialchars(get_uptime()); ?>
			</td>
		</tr>
		<tr>
             <td class="vncellt">DNS adresleri</td>
             <td class="listr">
					<?php
						$dns_servers = get_dns_servers();
						$breaker = 1;
						foreach($dns_servers as $dns) {
							$br = '';
							if(($breaker % 2) == 0)
								$br = '<br>';
								
							echo "<span style='margin: 2px;' class='label label-success'>{$dns}</span>{$br}";
							$breaker++;
						}
					?>
			</td>
		</tr>
        <tr>
            <td class="vncellt">Tarih</td>
            <td class="listr">
                <div id="time">
					<?= strftime("%T", time()); ?>
				</div>
				 <div id="datetime">
					<?= strftime("%e %B %Y %A", time()); ?>
				</div>
            </td>
        </tr>				
		<?php if ($config['revision']): ?>
		<tr>
			<td class="vncellt">Son değişiklik</td>
			<td class="listr"><?= strftime("%T <br> %e %B %Y %A", intval($config['revision']['time']));?></td>
		</tr>
		<?php endif; ?>
		<tr>
			<td class="vncellt">İşlemci kullanımı</td>
			<td class="listr">
			<?php $cpuUsage = cpu_usage(); ?>
			<div class="progress-container">
				<div id="cpubar" class="progress custom progress-danger progress-striped active">
					<div id="cpuwidtha" class="bar" style="width: <?= $cpuUsage; ?>%;"></div>
					<span style="visibility: hidden;" id="cpu_usage"></span> 
				</div>
			</div>
			</td>
		</tr>
		<tr>
			<td class="vncellt">RAM kullanımı</td>
			<td class="listr">
			<?php $memUsage = mem_usage(); ?>
			<div class="progress-container">
				<div id="membar" class="progress custom progress-striped active">
					<div id="memwidtha" class="bar" style="width: <?= $memUsage; ?>%;"></div>
				</div>
			</div>
			</td>
		</tr>
		<tr>
			<td class="vncellt">Disk kullanımı</td>
			<td class="listr">
			<?php $diskusage = disk_usage(); ?>
			<div class="progress-container">
				<div title="<?= $diskusage; ?>%" class="progress custom progress-warning progress-striped active">
					<div class="bar" style="width: <?= $diskusage; ?>%;"></div>				
				</div>
			</div>
			</td>
		</tr>
	</tbody>
</table>
<script type="text/javascript">
	function getstatus() {
		scroll(0,0);
		var url = "/widgets/widgets/system.widget.php";
		var pars = 'getupdatestatus=yes';
		var myAjax = new Ajax.Request(
			url,
			{
				method: 'get',
				parameters: pars,
				onComplete: activitycallback
			});
	}
	function activitycallback(transport) {
		$('updatestatus').innerHTML = transport.responseText;
	}
	function swapuname() {
		$('uname').innerHTML="<?php echo php_uname("a"); ?>";
	}
	setTimeout('getstatus()', 4000);
	
</script>
