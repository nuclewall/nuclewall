<?php
/*
    status_services.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.
*/

require_once('guiconfig.inc');
require_once('service-utils.inc');

$pgtitle = array('DURUM ', 'SERVİSLER');

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<table cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<table class="grids sortable">
							<tr>
								<td class="head">Servis Adı</td>
								<td class="head">Açıklama</td>
								<td class="head">Durum</td>
							</tr>
							<tr>
								<?php
									$cprunning = is_pid_running("{$g['varrun_path']}/lighty-CaptivePortal.pid");
								?>
								<td class="cell">
									hotspot
								</td>
								<td class="cell description sv">
									Hotspot servisi
								</td>
								<td class="cell status">
								<?php if($cprunning) : ?>
									<span title="Çalışıyor" class="label service blue">
										<i class="icon-play icon-white"></i>
									</span>
								<?php else: ?>
									<span title="Durduruldu" class="label service red">
										<i class="icon-stop icon-white"></i>
									</span>
								<?php endif; ?>
								</td>
							</tr>
							<tr>
								<?php
									$radiusdrunning = is_process_running("radiusd");
								?>
								<td class="cell">
									radiusd
								</td>
								<td class="cell description sv">
									FreeRADIUS sunucusu (hotspot alt servisi)
								</td>
								<td class="cell status">
								<?php if($radiusdrunning) : ?>
									<span title="Çalışıyor" class="label service blue">
										<i class="icon-play icon-white"></i>
									</span>
								<?php else: ?>
									<span title="Durduruldu" class="label service red">
										<i class="icon-stop icon-white"></i>
									</span>
								<?php endif; ?>
								</td>
							</tr>
							<tr>
								<?php
									$mysqldrunning = is_process_running("mysqld");
								?>
								<td class="cell">
									mysqld
								</td>
								<td class="cell description sv">
									MySQL sunucusu (hotspot alt servisi)
								</td>
								<td class="cell status">
								<?php if($mysqldrunning) : ?>
									<span title="Çalışıyor" class="label service blue">
										<i class="icon-play icon-white"></i>
									</span>
								<?php else: ?>
									<span title="Durduruldu" class="label service red">
										<i class="icon-stop icon-white"></i>
									</span>
								<?php endif; ?>
								</td>
							</tr>
							<tr>
								<?php
									$squidrunning = is_process_running("squid");
								?>
								<td class="cell">
									squid
								</td>
								<td class="cell description sv">
									Squid HTTP proxy sunucusu
								</td>
								<td class="cell status">
								<?php if($squidrunning) : ?>
									<span title="Çalışıyor" class="label service blue">
										<i class="icon-play icon-white"></i>
									</span>
								<?php else: ?>
									<span title="Durduruldu" class="label service red">
										<i class="icon-stop icon-white"></i>
									</span>
								<?php endif; ?>
								</td>
							</tr>
							<tr>
								<?php
									$squidgrunning = is_process_running("squidGuard");
								?>
								<td class="cell">
									squidGuard
								</td>
								<td class="cell description sv">
									SquidGuard web filtreleme (squid alt servisi)
								</td>
								<td class="cell status">
								<?php if($squidgrunning) : ?>
									<span title="Çalışıyor" class="label service blue">
										<i class="icon-play icon-white"></i>
									</span>
								<?php else: ?>
									<span title="Durduruldu" class="label service red">
										<i class="icon-stop icon-white"></i>
									</span>
								<?php endif; ?>
								</td>
							</tr>
							<tr>
								<?php
									$dhcpdrunning = is_process_running('dhcpd');
								?>
								<td class="cell">
									dhcpd
								</td>
								<td class="cell description sv">
									DHCP otomatik IP adresi dağıtım servisi
								</td>
								<td class="cell status">
								<?php if($dhcpdrunning) : ?>
									<span title="Çalışıyor" class="label service blue">
										<i class="icon-play icon-white"></i>
									</span>
								<?php else: ?>
									<span title="Durduruldu" class="label service red">
										<i class="icon-stop icon-white"></i>
									</span>
								<?php endif; ?>
								</td>
							</tr>
							<tr>
								<?php
									$dnsmasqrunning = is_process_running("dnsmasq");
								?>
								<td class="cell">
									dnsmasq
								</td>
								<td class="cell description sv">
									DNS çözümleme servisi
								</td>
								<td class="cell status">
								<?php if($dnsmasqrunning) : ?>
									<span title="Çalışıyor" class="label service blue">
										<i class="icon-play icon-white"></i>
									</span>
								<?php else: ?>
									<span title="Durduruldu" class="label service red">
										<i class="icon-stop icon-white"></i>
									</span>
								<?php endif; ?>
								</td>
							</tr>
							<tr>
								<?php
									$sshdrunning = is_process_running("sshd");
								?>
								<td class="cell">
									sshd
								</td>
								<td class="cell description sv">
									SSH (Güvenli Kabuk) sunucusu
								</td>
								<td class="cell status">
								<?php if($sshdrunning) : ?>
									<span title="Çalışıyor" class="label service blue">
										<i class="icon-play icon-white"></i>
									</span>
								<?php else: ?>
									<span title="Durduruldu" class="label service red">
										<i class="icon-stop icon-white"></i>
									</span>
								<?php endif; ?>
								</td>
							</tr>
							<tr>
								<?php
									$ntpdrunning = is_process_running("ntpd");
								?>
								<td class="cell">
									ntpd
								</td>
								<td class="cell description sv">
									NTP zaman eşitleme servisi
								</td>
								<td class="cell status">
								<?php if($ntpdrunning) : ?>
									<span title="Çalışıyor" class="label service blue">
										<i class="icon-play icon-white"></i>
									</span>
								<?php else: ?>
									<span title="Durduruldu" class="label service red">
										<i class="icon-stop icon-white"></i>
									</span>
								<?php endif; ?>
								</td>
							</tr>
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
