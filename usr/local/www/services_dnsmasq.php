<?php
/* $Id$ */
/*
	services_dnsmasq.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.

	Copyright (C) 2003-2004 Bob Zoller <bob@kludgebox.com> and Manuel Kasper <mk@neon1.net>.
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
require_once('functions.inc');
require_once('filter.inc');
require_once('shaper.inc');

$pconfig['enable'] = isset($config['dnsmasq']['enable']);
$pconfig['regdhcp'] = isset($config['dnsmasq']['regdhcp']);
$pconfig['regdhcpstatic'] = isset($config['dnsmasq']['regdhcpstatic']);

if ($_POST)
{

	$pconfig = $_POST;
	unset($input_errors);

	$config['dnsmasq']['enable'] = ($_POST['enable']) ? true : false;
	$config['dnsmasq']['regdhcp'] = ($_POST['regdhcp']) ? true : false;
	$config['dnsmasq']['regdhcpstatic'] = ($_POST['regdhcpstatic']) ? true : false;

	if (!$input_errors)
	{
		write_config();

		$retval = 0;
		$retval = services_dnsmasq_configure();
		$savemsg = get_std_save_message($retval);

		filter_configure();

		if ($retval == 0)
			clear_subsystem_dirty('hosts');
	}
}

$pgtitle = array('SERVICES', 'DNS Forwarder', 'SETTINGS');

include('head.inc');

?>

<script language="JavaScript">
<!--
function enable_change(enable_over) {
	var endis;
	endis = !(document.iform.enable.checked || enable_over);
	document.iform.regdhcp.disabled = endis;
	document.iform.regdhcpstatic.disabled = endis;
}
//-->
</script>
</head>
<body>
<?php include('fbegin.inc'); ?>

<form action="services_dnsmasq.php" method="post" name="iform" id="iform">
<?php if (is_subsystem_dirty('hosts')): ?><p>
<?php print_info_box_np("The DNS forwarder configuration has been changed.<br>You must apply the changes in order for them to take effect.", true);?>
<?php endif; ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>

<table cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<?php
				$tab_array = array();
				$tab_array[0] = array("Settings", true, "services_dnsmasq.php");
				$tab_array[1] = array("Static DNS Records", false, "services_dnsmasq_hosts.php");
				display_top_tabs($tab_array);
			?>
		</td>
	</tr>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td valign="top" class="vncell">Enable</td>
					<td class="vtable">
						<input name="enable" type="checkbox" id="enable" value="yes" <?php if ($pconfig['enable'] == "yes") echo "checked";?> onClick="enable_change(false)">
						Enable DNS forwarder.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">DHCP Registration</td>
					<td class="vtable"><p>
						<input name="regdhcp" type="checkbox" id="regdhcp" value="yes" <?php if ($pconfig['regdhcp'] == "yes") echo "checked";?>>
						Register DHCP leases in DNS forwarder.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Static DHCP</td>
					<td class="vtable">
						<input name="regdhcpstatic" type="checkbox" id="regdhcpstatic" value="yes" <?php if ($pconfig['regdhcpstatic'] == "yes") echo "checked";?>>
						Register DHCP static mappings in DNS forwarder
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell"></td>
					<td class="vtable">
						<input name="submit" type="submit" class="btn btn-inverse" value="Save" onclick="enable_change(true)">
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>
<script language="JavaScript">
<!--
enable_change(false);
//-->
</script>
</div>
</body>
</html>
