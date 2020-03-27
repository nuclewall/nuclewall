<?php
/* $Id$ */
/*
	services_dnsmasq_edit.php

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


function hostcmp($a, $b) {
	return strcasecmp($a['host'], $b['host']);
}

function hosts_sort() {
        global $g, $config;

        if (!is_array($config['dnsmasq']['hosts']))
                return;

        usort($config['dnsmasq']['hosts'], "hostcmp");
}

require("guiconfig.inc");

if (!is_array($config['dnsmasq']['hosts']))
	$config['dnsmasq']['hosts'] = array();

$a_hosts = &$config['dnsmasq']['hosts'];

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_hosts[$id]) {
	$pconfig['host'] = $a_hosts[$id]['host'];
	$pconfig['domain'] = $a_hosts[$id]['domain'];
	$pconfig['ip'] = $a_hosts[$id]['ip'];
	$pconfig['descr'] = base64_decode($a_hosts[$id]['descr']);
}

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "domain ip");
	$reqdfieldsn = array("Domain", "IP Address");

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if (($_POST['host'] && !is_hostname($_POST['host'])))
		$input_errors[] = "The hostname can only contain the characters A-Z, 0-9 and '-'.";

	if (($_POST['domain'] && !is_domain($_POST['domain'])))
		$input_errors[] = "A valid domain must be specified.";

	if (($_POST['ip'] && !is_ipaddr($_POST['ip'])))
		$input_errors[] = "A valid IP address must be specified.";

	/* check for overlaps */
	foreach ($a_hosts as $hostent) {
		if (isset($id) && ($a_hosts[$id]) && ($a_hosts[$id] === $hostent))
			continue;

		if (($hostent['host'] == $_POST['host']) && ($hostent['domain'] == $_POST['domain'])) {
			$input_errors[] = "This host/domain already exists.";
			break;
		}
	}

	if (!$input_errors) {
		$hostent = array();
		$hostent['host'] = $_POST['host'];
		$hostent['domain'] = $_POST['domain'];
		$hostent['ip'] = $_POST['ip'];
		$hostent['descr'] = base64_encode($_POST['descr']);

		if (isset($id) && $a_hosts[$id])
			$a_hosts[$id] = $hostent;
		else
			$a_hosts[] = $hostent;
		hosts_sort();

		mark_subsystem_dirty('hosts');

		write_config();

		header("Location: services_dnsmasq_hosts.php");
		exit;
	}
}

$pgtitle = array('SERVICES ', 'DNS FORWARDER', 'EDIT DNS RECORD');

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php if ($input_errors) print_input_errors($input_errors); ?>
<form action="services_dnsmasq_edit.php" method="post" name="iform" id="iform">
<table cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td colspan="2" valign="top" class="listtopic">EDIT DNS RECORD</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Host</td>
					<td class="vtable">
						<input name="host" type="text" class="formfld" id="host" size="40" value="<?=htmlspecialchars($pconfig['host']);?>">
						<br>Name of the host, without domain part<br>
						Example: <em>myhost</em>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Domain</td>
					<td class="vtable">
						<input name="domain" type="text" id="domain" value="<?=htmlspecialchars($pconfig['domain']);?>">
						<br>Domain of the host<br>
						Example: <em>example.com</em>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">IP Address</td>
					<td class="vtable">
						<input name="ip" type="text" id="ip" value="<?=htmlspecialchars($pconfig['ip']);?>">
						<br>IP address of the host<br>
						Example: <em>192.168.100.100</em>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Description</td>
					<td class="vtable">
						<input name="descr" type="text" id="descr" value="<?=htmlspecialchars($pconfig['descr']);?>">
						<br>You may enter a description here for your reference
					</td>
				</tr>
				<tr>
				<td class="vncell"></td>
				<td class="vtable">
					<input name="Submit" type="submit" class="btn btn-inverse" value="Save">
					<input class="btn" type="button" value="Cancel" onclick="history.back()">
					<?php if (isset($id) && $a_hosts[$id]): ?>
					<input name="id" type="hidden" value="<?=htmlspecialchars($id);?>">
					<?php endif; ?>
				</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>
</div>
</body>
</html>
