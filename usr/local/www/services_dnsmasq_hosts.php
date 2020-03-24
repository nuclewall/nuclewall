<?php
/*
	services_dnsmasq_hosts.php

	Copyright (C) 2013-2015 Ogün AÇIK
	All rights reserved.
*/

require('guiconfig.inc');
require_once('functions.inc');
require_once('filter.inc');
require_once('shaper.inc');

if (!is_array($config['dnsmasq']['hosts']))
	$config['dnsmasq']['hosts'] = array();

$a_hosts = &$config['dnsmasq']['hosts'];

if ($_POST)
{
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

if ($_GET['act'] == "del") {
	if ($_GET['type'] == 'host')
	{
		if ($a_hosts[$_GET['id']])
		{
			unset($a_hosts[$_GET['id']]);
			write_config();
			mark_subsystem_dirty('hosts');
			header("Location: services_dnsmasq_hosts.php");
			exit;
		}
	}
}

$pgtitle = array('SERVICES ', 'DNS FORWARDER', 'STATIC DNS RECORDS');

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<form action="services_dnsmasq_hosts.php" method="post" name="iform" id="iform">
<?php if (is_subsystem_dirty('hosts')): ?><p>
<?php print_info_box_np("The DNS forwarder configuration has been changed.<br>You must apply the changes in order for them to take effect.", true);?>
<?php endif; ?>
<table cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<?php
				$tab_array = array();
				$tab_array[0] = array("Settings", false, "services_dnsmasq.php");
				$tab_array[1] = array("Static DNS Records", true, "services_dnsmasq_hosts.php");
				display_top_tabs($tab_array);
			?>
		</td>
	</tr>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<table class="grids sortable">
							<tr>
								<td class="head">Hostname</td>
								<td class="head">Domain</td>
								<td class="head">IP Address</td>
								<td class="head">Description</td>
								<td class="head">
								</td>
							</tr>
							<?php $i = 0; foreach ($a_hosts as $hostent): ?>
							<tr>
								<td class="cell" ondblclick="document.location='services_dnsmasq_edit.php?id=<?=$i;?>';">
									<?=strtolower($hostent['host']);?>
								</td>
								<td class="cell" ondblclick="document.location='services_dnsmasq_edit.php?id=<?=$i;?>';">
									<?=strtolower($hostent['domain']);?>
								</td>
								<td class="cell" ondblclick="document.location='services_dnsmasq_edit.php?id=<?=$i;?>';">
									<?=$hostent['ip'];?>
								</td>
								<td class="cell description" ondblclick="document.location='services_dnsmasq_edit.php?id=<?=$i;?>';">
									<?=htmlspecialchars(base64_decode($hostent['descr']));?>
								</td>
								<td class="cell tools">
									<a title="Edit" href="services_dnsmasq_edit.php?id=<?=$i;?>">
										<i class="icon-edit"></i>
									</a>
									<a title="Delete" href="services_dnsmasq_hosts.php?type=host&act=del&id=<?=$i;?>" onclick="return confirm('Do you really want to delete this host?')">
										<i class="icon-trash"></i>
									</a>
								</td>
							</tr>
							<?php $i++; endforeach; ?>
							<tr>
								<td class="cell" colspan="4"></td>
								<td class="cell tools">
									<a title="New host record" href="services_dnsmasq_edit.php">
										<i class="icon-plus"></i>
									</a>
								</td>
							</tr>
						</table>
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
