<?php
/*
	services_status.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.

    services_status.php
    Copyright (C) 2004, 2005 Scott Ullrich
    All rights reserved.

    services_status.widget.php
    Copyright (C) 2007 Sam Wenham

    Redistribution and use in source and binary forms, with or without
    modification, are permitted provided that the following conditions are met:

    1. Redistributions of source code must retain the above copyright notice,
       this list of conditions and the following disclaimer.

    2. Redistributions in binary form must reproduce the above copyright
       notice, this list of conditions and the following disclaimer in the
       documentation and/or other materials provided with the distribution.

    THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
    INClUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
    AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
    AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
    OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
    POSSIBILITY OF SUCH DAMAGE.
*/

require_once('guiconfig.inc');
require_once('captiveportal.inc');
require_once('service-utils.inc');
require_once('/usr/local/www/widgets/include/services_status.inc');

?>

<table class="grids sortable">
	<tr>
		<td class="listlr"><b>Servis Adı</b></td>
		<td class="listr"><b>Açıklama</b></td>
		<td class="listr"><b>Durum</b></td>
	</tr>
	<tr>
		<?php
			$cprunning = is_pid_running("{$g['varrun_path']}/lighty-CaptivePortal.pid");
		?>
		<td class="listlr">
			hotspot
		</td>
		<td class="listr">
			Hotspot servisi
		</td>
		<td class="listr center">
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
		<td class="listlr">
			radiusd
		</td>
		<td class="listr">
			FreeRADIUS sunucusu
		</td>
		<td class="listr center">
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
		<td class="listlr">
			mysqld
		</td>
		<td class="listr">
			MySQL sunucusu
		</td>
		<td class="listr center">
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
			$dhcpdrunning = is_process_running("dhcpd");
		?>
		<td class="listlr">
			dhcpd
		</td>
		<td class="listr">
			DHCP sunucusu
		</td>
		<td class="listr center">
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
		<td class="listlr">
			dnsmasq
		</td>
		<td class="listr">
			DNS çözümleme servisi
		</td>
		<td class="listr center">
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
		<td class="listlr">
			sshd
		</td>
		<td class="listr">
			SSH sunucusu
		</td>
		<td class="listr center">
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
		<td class="listlr">
			ntpd
		</td>
		<td class="listr">
			NTP zaman eşitleme servisi
		</td>
		<td class="listr center">
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
