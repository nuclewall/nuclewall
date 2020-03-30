<?php
/*
	interfaces_assign.php
	part of m0n0wall (http://m0n0.ch/wall)
	Written by Jim McBeath based on existing m0n0wall files

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.

	Copyright (C) 2003-2005 Manuel Kasper <mk@neon1.net>.
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

$pgtitle = array('INTERFACS ', 'ASSIGN');

require('guiconfig.inc');
require('functions.inc');
require('filter.inc');
require('shaper.inc');
require('captiveportal.inc');

$portlist = get_interface_list();

if ($_POST['apply'])
{
	if (file_exists("/var/run/interface_mismatch_reboot_needed"))
	{
		system_reboot();
		$rebootingnow = true;
	}
	else
	{
		write_config();
		$retval = 0;
		$retval = filter_configure();
		$savemsg = get_std_save_message($retval);

		if (stristr($retval, "error") <> true)
			$savemsg = get_std_save_message($retval);
		else
			$savemsg = $retval;
	}

}
else if ($_POST)
{
	unset($input_errors);

	$portifmap = array();
	foreach ($portlist as $portname => $portinfo)
		$portifmap[$portname] = array();

	foreach ($_POST as $ifname => $ifport)
	{
		if (($ifname == 'lan') || ($ifname == 'wan') || (substr($ifname, 0, 3) == 'opt'))
			$portifmap[$ifport][] = strtoupper($ifname);
	}

	foreach ($portifmap as $portname => $ifnames)
	{
		if (count($ifnames) > 1) {
			$errstr = sprintf('Port %1$s '.
				' was assigned to %2$s' .
				' interfaces:', $portname, count($ifnames));

			foreach ($portifmap[$portname] as $ifn)
				$errstr .= " " . $ifn;

			$input_errors[] = $errstr;
		}
	}

	if (!$input_errors)
	{
		foreach ($_POST as $ifname => $ifport)
		{
			if (($ifname == 'lan') || ($ifname == 'wan') ||
				(substr($ifname, 0, 3) == 'opt')) {

				if (!is_array($ifport))
				{
					$reloadif = false;
					if (!empty($config['interfaces'][$ifname]['if']) && $config['interfaces'][$ifname]['if'] <> $ifport)
					{
						interface_bring_down($ifname);
						$reloadif = true;
					}
					$config['interfaces'][$ifname]['if'] = $ifport;
					if (isset($portlist[$ifport]['isppp']))
						$config['interfaces'][$ifname]['ipaddr'] = $portlist[$ifport]['type'];

					if (!isset($config['interfaces'][$ifname]['descr']))
						$config['interfaces'][$ifname]['descr'] = strtoupper($ifname);

					if ($reloadif == true) {
						interface_configure($ifname, true);
					}
				}
			}
		}
		write_config();
	}
}

if ($_GET['act'] == "del")
{
	$id = $_GET['id'];

	if (link_interface_to_group($id))
		$input_errors[] = "The interface is part of a group. Please remove it from the group to continue.";
	else
	{
		unset($config['interfaces'][$id]['enable']);
		$realid = get_real_interface($id);
		interface_bring_down($id);

		unset($config['interfaces'][$id]);

		if (is_array($config['dhcpd']) && is_array($config['dhcpd'][$id]))
		{
			unset($config['dhcpd'][$id]);
			services_dhcpd_configure();
		}

		if (count($config['filter']['rule']) > 0)
		{
			foreach ($config['filter']['rule'] as $x => $rule)
			{
				if($rule['interface'] == $id)
					unset($config['filter']['rule'][$x]);
			}
       	}

		if(is_array($config['nat']['advancedoutbound']) && count($config['nat']['advancedoutbound']['rule']) > 0)
		{
        	foreach ($config['nat']['advancedoutbound']['rule'] as $x => $rule)
			{
				if($rule['interface'] == $id)
					unset($config['nat']['advancedoutbound']['rule'][$x]['interface']);
        	}
		}

		if(is_array($config['nat']['rule']) && count($config['nat']['rule']) > 0)
		{
			foreach ($config['nat']['rule'] as $x => $rule)
			{
				if($rule['interface'] == $id)
					unset($config['nat']['rule'][$x]['interface']);
			}
        }

		write_config("An interface assignment deleted");

		if($config['interfaces']['lan'] && $config['dhcpd']['wan'])
		{
			unset($config['dhcpd']['wan']);
		}

		link_interface_to_vlans($realid, "update");

		$savemsg = "Interface has been deleted.";
	}
}

if ($_GET['act'] == "add" && (count($config['interfaces']) < count($portlist)))
{
	if(!$config['interfaces']['lan'])
	{
		$newifname = "lan";
		$descr = "LAN";
		$config['interfaces'][$newifname] = array();
		$config['interfaces'][$newifname]['descr'] = $descr;
	}
	else
	{
		for ($i = 1; $i <= count($config['interfaces']); $i++)
		{
			if (!$config['interfaces']["opt{$i}"])
				break;
		}
		$newifname = 'opt' . $i;
		$descr = "OPT" . $i;
		$config['interfaces'][$newifname] = array();
		$config['interfaces'][$newifname]['descr'] = $descr;
	}

	uksort($config['interfaces'], "compare_interface_friendly_names");

	foreach ($portlist as $portname => $portinfo)
	{
		$portused = false;
		foreach ($config['interfaces'] as $ifname => $ifdata)
		{
			if ($ifdata['if'] == $portname) {
				$portused = true;
				break;
			}
		}

		if (!$portused)
		{
			$config['interfaces'][$newifname]['if'] = $portname;
			break;
		}
	}

	mwexec("/bin/rm -f /tmp/config.cache");
	write_config("An interface assignment added");

	$savemsg = "Interface has been added.";

} else if ($_GET['act'] == "add")
	$input_errors[] = "No more interfaces available to be assigned.";

include('head.inc');

if(file_exists("/var/run/interface_mismatch_reboot_needed"))
	if ($_POST)
	{
		if($rebootingnow)
			$savemsg = "NUCLEWALL is now rebooting. Please wait.";
		else
			$savemsg = "Reboot is needed. Please apply the settings in order to reboot.";
	}
	else
		$savemsg = "Interface mismatch detected. Please resolve the mismatch and click Save.";
?>


</head>
<body>
<?php include('fbegin.inc'); ?>

<form action="interfaces_assign.php" method="post" name="iform" id="iform">

<?php if (file_exists("/tmp/reload_interfaces")): ?><p>
	<?php print_info_box_np("The interface configuration has been changed.</br>You must apply the changes in order for them to take effect.", true);?>
<?php elseif($savemsg): ?>
	<?php print_info_box($savemsg); ?>
<?php endif; ?>

<?php pfSense_handle_custom_code("/usr/local/pkg/interfaces_assign/pre_input_errors"); ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>

<table class="tabcont" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<table width="100%" class="grids">
				<tr>
					<td class="head">Interface</td>
					<td class="head">Network Port</td>
					<td class="head"></td>
				</tr>
					<?php foreach ($config['interfaces'] as $ifname => $iface):
						if ($iface['descr'])
							$ifdescr = $iface['descr'];
						else
							$ifdescr = strtoupper($ifname);
						?>
				<tr>
					<td class="times if"><?=$ifdescr;?></td>
					<td class="times if">
						<select name="<?=$ifname;?>" id="<?=$ifname;?>">
						<?php foreach ($portlist as $portname => $portinfo): ?>
							<option  value="<?=$portname;?>"  <?php if ($portname == $iface['if']) echo " selected";?>>
							<?php
								echo htmlspecialchars($portname . " (" . $portinfo['mac'] . ")");?>
							</option>
							<?php endforeach; ?>
						</select>
					</td>
					<td class="cell tools">
						<?php if ($ifname != 'wan'): ?>
						<a title="Delete" href="interfaces_assign.php?act=del&id=<?=$ifname;?>" onclick="return confirm('Do you really want to delete this interface?')">
							<i class="icon-trash"></i>
						</a>
						<?php endif; ?>
					</td>
				</tr>
					<?php endforeach; ?>
					<?php if (count($config['interfaces']) < count($portlist)): ?>
				<tr>
					<td class="cell" colspan="2"></td>
					<td class="cell tools">
						<a title="Add" href="interfaces_assign.php?act=add">
							<i class="icon-plus"></i>
						</a>
					</td>
				</tr>
				<?php endif; ?>
			</table>
		</td>
	</tr>
	<tr>
		<td>
			<input name="Submit" type="submit" class="btn btn-inverse" value="Save">
		</td>
	</tr>
</table>
</form>
</div>
</body>
</html>
