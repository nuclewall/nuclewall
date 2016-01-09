<?php
/*
	Copyright 2013 Ogün Açık
	
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

require_once("guiconfig.inc");
require_once("pfsense-utils.inc");
require_once("functions.inc");
require_once("/usr/local/www/widgets/include/interfaces.inc");

		$i = 0; 
		$ifdescrs = get_configured_interface_with_descr();
?>

 <table width="100%" border="0" cellspacing="0" cellpadding="0">
	<?php 
	foreach ($ifdescrs as $ifdescr => $ifname) {
		$ifinfo = get_interface_info($ifdescr);
	?>
	<tr> 
	<td class="vncellt" width="40%">
		<img src="./themes/nuclewall/images/icons/icon_cablenic.gif"/>
		<strong><u>
		<span onClick="location.href='/interfaces.php?if=<?=$ifdescr; ?>'" style="cursor:pointer">
	<?=htmlspecialchars($ifname);?></span></u></strong>
	<?php 
		if ($ifinfo['dhcplink']) 
			echo "&nbsp;&nbsp;&nbsp;(DHCP)";
	?>
	</td>
	<td width="60%"  class="listr">
		 <?php if($ifinfo['status'] == "up" || $ifinfo['status'] == "associated") { ?> 
				<table>
					<tr>
						<td>
							<div id="<?php echo $ifname;?>-up" style="display:inline" ><img src="./themes/nuclewall/images/icons/icon_interface_up.gif" title="<?=$ifname;?> is up" /></div>
						</td>
						<td>
							<div id="<?php echo $ifname;?>-down" style="display:none" ><img src="./themes/nuclewall/images/icons/icon_interface_down.gif" title="<?=$ifname;?> is down" /></div>
						</td>
						<td>
							<div id="<?php echo $ifname;?>-block" style="display:none" ><img src="./themes/nuclewall/images/icons/icon_block.gif" title="<?=$ifname;?> is disabled" /></div>
						</td>
			<? } else if ($ifinfo['status'] == "no carrier") { ?>
				<table>
					<tr>
						<td>
							<div id="<?php echo $ifname;?>-down" style="display:inline" ><img src="./themes/nuclewall/images/icons/icon_interface_down.gif" title="<?=$ifname;?> is down" /></div>
						</td>
						<td>
							<div id="<?php echo $ifname;?>-block" style="display:none" ><img src="./themes/nuclewall/images/icons/icon_block.gif" title="<?=$ifname;?> is disabled" /></div>
						</td>
						<td>
							<div id="<?php echo $ifname;?>-up" style="display:none" ><img src="./themes/nuclewall/images/icons/icon_interface_up.gif" title="<?=$ifname;?> is up" /></div>
						</td>
			<? }  else if ($ifinfo['status'] == "down") { ?>
				<table>
					<tr>
						<td>
							<div id="<?php echo $ifname;?>-block" style="display:inline" ><img src="./themes/nuclewall/images/icons/icon_block.gif" title="<?=$ifname;?> is disabled" /></div>
						</td>
						<td>
							<div id="<?php echo $ifname;?>-up" style="display:none" ><img src="./themes/nuclewall/images/icons/icon_interface_up.gif" title="<?=$ifname;?> is up" /></div>
						</td>
						<td>
							<div id="<?php echo $ifname;?>-down" style="display:none" ><img src="./themes/nuclewall/images/icons/icon_interface_down.gif" title="<?=$ifname;?> is down" /></div>
						</td>
			<? } else { ?><?=htmlspecialchars($ifinfo['status']); }?>
					<td>
						<span class="label label-success" id="<?php echo $ifname;?>-ip" style="display:inline"><?=htmlspecialchars($ifinfo['ipaddr']);?> </span>
					</td>
					<td>
						<span class="label" id="<?php echo $ifname;?>-media" style="display:inline"><?=htmlspecialchars($ifinfo['media']);?></span>
					</td>
				</tr>
			</table>
	  </td></tr><?php 
}
?> 
</table>
