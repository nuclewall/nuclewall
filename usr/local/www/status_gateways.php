<?php
/* $Id$ */
/*
	status_gateways.php

	Copyright (C) 2013-2015 Ogün AÇIK
	All rights reserved.

	Copyright (C) 2010 Seth Mos <seth.mos@dds.nl>.
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

$a_gateways = return_gateways_array();
$gateways_status = array();
$gateways_status = return_gateways_status(true);

$pgtitle = array('DURUM', 'AĞ GEÇİTLERİ');
?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<table cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<?php
				$tab_array = array();
				$tab_array[] = array('Ağ Geçitleri', true, 'status_gateways.php');
				$tab_array[] = array('Ağ Geçidi Grupları', false, 'status_gateway_groups.php');
				display_top_tabs($tab_array);
			?>
		</td>
	</tr>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<table class="grids">
							<tr>
								<td class="head">İsim</td>
								<td class="head">Ağ Geçidi</td>
								<td class="head">İzleme IP'si</td>
								<td class="head">RTT</td>
								<td class="head">Kayıp</td>
								<td class="head">Durum</td>
								<td class="head">Açıklama</td>
							</tr>
							<?php foreach ($a_gateways as $gname => $gateway) { ?>
							<tr>
								<td class="cell">
									<?=$gateway['name'];?>
								</td>
								<td class="cell">
									<?php echo lookup_gateway_ip_by_name($gname);?>
								</td>
								<td class="cell">
									<?php
										if ($gateways_status[$gname])
											echo $gateways_status[$gname]['monitorip'];
										else
											echo $gateway['monitorip'];
									?>
								</td>
								<td class="cell gw">
									<?php
										if ($gateways_status[$gname])
											echo $gateways_status[$gname]['delay'];
										else
											echo "Veri alınıyor...";
									?>
									<?php $counter++; ?>
								</td>
								<td class="cell gw">
									<?php
										if ($gateways_status[$gname])
											echo $gateways_status[$gname]['loss'];
										else
											echo "Veri alınıyor...";
									?>
									<?php $counter++; ?>
								</td>
								<td class="cell">
									<?php
									if ($gateways_status[$gname])
									{
										$status = $gateways_status[$gname];
										if (stristr($status['status'], "down")) {
											$online = "Çevrimdışı";
											$bgcolor = "";
										} elseif (stristr($status['status'], "loss")) {
											$online = "Uyarı: Paket kaybı";
											$bgcolor = "label-warning";
										} elseif (stristr($status['status'], "delay")) {
											$online = "Uyarı: Gecikme";
											$bgcolor = "label-warning";
										} elseif ($status['status'] == "none") {
											$online = "Aktif";
											$bgcolor = "label-success";
										}
									}
									else if (isset($gateway['monitor_disable'])) {
											$online = "Aktif";
											$bgcolor = "label-success";
										} else {
											$online = "Veril alınıyor...";
											$bgcolor = "label-success";
										}
									echo	"<span class=\"label $bgcolor\">$online</span>";

									$lastchange = $gateway['lastcheck'];
									if(!empty($lastchange)) {
										$lastchange = explode(" ", $lastchange);
										array_shift($lastchange);
										array_shift($lastchange);
										$lastchange = implode(" ", $lastchange);
										printf("Son kontrol: %s", $lastchange);
									}
											?>
								</td>
								<td class="cell description gw"><?=base64_decode($gateway['descr']); ?></td>
							</tr>
							<?php } ?>
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
