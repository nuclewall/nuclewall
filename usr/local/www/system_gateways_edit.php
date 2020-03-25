<?php
/*
	system_gateways_edit.php

	Copyright (C) 2013-2020 Ogun Acik
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

$a_gateways = return_gateways_array(true);
$a_gateways_arr = array();
foreach($a_gateways as $gw) {
	$a_gateways_arr[] = $gw;
}
$a_gateways = $a_gateways_arr;

if (!is_array($config['gateways']['gateway_item']))
        $config['gateways']['gateway_item'] = array();

$a_gateway_item = &$config['gateways']['gateway_item'];

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($_GET['dup'])) {
	$id = $_GET['dup'];
}

if (isset($id) && $a_gateways[$id]) {
	$pconfig = array();
	$pconfig['name'] = $a_gateways[$id]['name'];
	$pconfig['interface'] = $a_gateways[$id]['interface'];
	$pconfig['friendlyiface'] = $a_gateways[$id]['friendlyiface'];
	if (isset($a_gateways[$id]['dynamic']))
		$pconfig['dynamic'] = true;
	$pconfig['gateway'] = $a_gateways[$id]['gateway'];
	$pconfig['defaultgw'] = isset($a_gateways[$id]['defaultgw']);
	$pconfig['monitor'] = $a_gateways[$id]['monitor'];
	$pconfig['monitor_disable'] = isset($a_gateways[$id]['monitor_disable']);
	$pconfig['descr'] = base64_decode($a_gateways[$id]['descr']);
	$pconfig['attribute'] = $a_gateways[$id]['attribute'];
}

if (isset($_GET['dup'])) {
	unset($id);
	unset($pconfig['attribute']);
}

if ($_POST) {

	unset($input_errors);

	$reqdfields = explode(" ", "name interface");
	$reqdfieldsn = array("Name", "Interface");

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if (! isset($_POST['name']))
	{
		$input_errors[] = "A valid gateway name must be specified.";
	}

	if (! is_validaliasname($_POST['name']))
	{
		$input_errors[] = "The gateway name must not contain invalid characters.";
	}

	if (($_POST['gateway'] && (!is_ipaddr($_POST['gateway'])) && ($_POST['attribute'] != "system")) && ($_POST['gateway'] != "dynamic"))
	{
		$input_errors[] = "A valid gateway IP address must be specified.";
	}

	if ($_POST['gateway'] && (is_ipaddr($_POST['gateway'])))
	{
		if (!empty($config['interfaces'][$_POST['interface']]['ipaddr']))
		{
			if (is_ipaddr($config['interfaces'][$_POST['interface']]['ipaddr']) && (empty($_POST['gateway']) || $_POST['gateway'] == "dynamic"))
				$input_errors[] = "Dynamic gateway values cannot be specified for interfaces with a static ip configuration.";
		}

		$parent_ip = get_interface_ip($_POST['interface']);

		if (is_ipaddr($parent_ip))
		{
			$parent_sn = get_interface_subnet($_POST['interface']);
			if(!ip_in_subnet($_POST['gateway'], gen_subnet($parent_ip, $parent_sn) . "/" . $parent_sn) && !ip_in_interface_alias_subnet($_POST['interface'], $_POST['gateway'])) {
				$input_errors[] = sprintf("The gateway address %s does not lie within the chosen interface's subnet.", $_POST['gateway']);
			}
		}
	}

	if (($_POST['monitor'] <> "") && !is_ipaddr($_POST['monitor']) && $_POST['monitor'] != "dynamic")
	{
		$input_errors[] = "A valid monitor IP address must be specified.";
	}

	if (isset($_POST['name']))
	{
		foreach ($a_gateways as $gateway)
		{
			if (isset($id) && ($a_gateways[$id]) && ($a_gateways[$id] === $gateway))
			{
				if ($gateway['name'] != $_POST['name'])
					$input_errors[] = "Changing name on a gateway is not allowed.";
				continue;
			}

			if($_POST['name'] <> "")
			{
				if (($gateway['name'] <> "") && ($_POST['name'] == $gateway['name']) && ($gateway['attribute'] != "system")) {
					$input_errors[] = sprintf("The gateway name %s already exists.", $_POST['name']);
					break;
				}
			}

			if(is_ipaddr($_POST['gateway']))
			{
				if (($gateway['gateway'] <> "") && ($_POST['gateway'] == $gateway['gateway']) && ($gateway['attribute'] != "system")) {
					$input_errors[] = sprintf("The gateway IP address %s already exists.", $_POST['gateway']);
					break;
				}
			}

			if(is_ipaddr($_POST['monitor']))
			{
				if (($gateway['monitor'] <> "") && ($_POST['monitor'] == $gateway['monitor']) && ($gateway['attribute'] != "system")) {
					$input_errors[] = sprintf("The monitor IP address  %s is already in use. You must choose a different monitor IP.", $_POST['monitor']);
					break;
				}
			}
		}
	}

	if (!$input_errors)
	{
		$reloadif = "";
		$gateway = array();

		if (empty($_POST['interface']))
			$gateway['interface'] = $pconfig['friendlyiface'];
		else
			$gateway['interface'] = $_POST['interface'];
		if (is_ipaddr($_POST['gateway']))
			$gateway['gateway'] = $_POST['gateway'];
		else
			$gateway['gateway'] = "dynamic";
		$gateway['name'] = $_POST['name'];
		$gateway['descr'] = base64_encode($_POST['descr']);
		if ($_POST['monitor_disable'] == "yes")
			$gateway['monitor_disable'] = true;
		else if (is_ipaddr($_POST['monitor']))
			$gateway['monitor'] = $_POST['monitor'];

		if ($_POST['defaultgw'] == "yes" || $_POST['defaultgw'] == "on") {
			$i = 0;
			foreach($a_gateway_item as $gw) {
				unset($config['gateways']['gateway_item'][$i]['defaultgw']);
				if ($gw['interface'] != $_POST['interface'] && $gw['defaultgw'])
					$reloadif = $gw['interface'];
				$i++;
			}
			$gateway['defaultgw'] = true;
		}


		if (isset($id) && $a_gateway_item[$id])
			$a_gateway_item[$id] = $gateway;
		else
			$a_gateway_item[] = $gateway;

		mark_subsystem_dirty('staticroutes');

		write_config();

		if (!empty($reloadif))
			send_event("interface reconfigure {$reloadif}");

		header("Location: system_gateways.php");
		exit;
	}
	else
	{
		$pconfig = $_POST;
		if (empty($_POST['friendlyiface']))
			$pconfig['friendlyiface'] = $_POST['interface'];
	}
}


$pgtitle = array('SYSTEM', 'GATEWEAYS', 'EDIT GATEWAY');

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include("fbegin.inc"); ?>


<script language="JavaScript">
function monitor_change() {
        document.iform.monitor.disabled = document.iform.monitor_disable.checked;
}
</script>
<?php if ($input_errors) print_input_errors($input_errors); ?>
            <form action="system_gateways_edit.php" method="post" name="iform" id="iform">
	<?php

	/* If this is a system gateway we need this var */
	if(($pconfig['attribute'] == "system") || is_numeric($pconfig['attribute'])) {
		echo "<input type='hidden' name='attribute' id='attribute' value='{$pconfig['attribute']}' >\n";
	}
	echo "<input type='hidden' name='friendlyiface' id='friendlyiface' value='{$pconfig['friendlyiface']}' >\n";
	?>
              <table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td colspan="2" valign="top" class="listtopic">EDIT GATEWAY</td>
				</tr>
                <tr>
                  <td valign="top" class="vncell">Interface</td>
                  <td class="vtable">
		 	<select name='interface'>

			<?php
				$interfaces = get_configured_interface_with_descr(false, true);
				foreach ($interfaces as $iface => $ifacename) {
				echo "<option value=\"{$iface}\"";
				if ($iface == $pconfig['friendlyiface'])
					echo " selected";
				echo ">" . htmlspecialchars($ifacename) . "</option>";
				}

				?>
                    </select> <br>
                    Choose which interface this gateway applies to.
					</td>
                </tr>
                <tr>
                  <td valign="top" class="vncell">Name</td>
                  <td class="vtable">
                    <input name="name" type="text" id="name" value="<?=htmlspecialchars($pconfig['name']);?>">
                    <br>Gateway name</td>
                </tr>
		<tr>
                  <td valign="top" class="vncell">Gateway</td>
                  <td class="vtable">
                    <input name="gateway" type="text" class="host" id="gateway" value="<?php if ($pconfig['dynamic']) echo "dynamic"; else echo $pconfig['gateway']; ?>"/>
                    <br>Gateway IP address</td>
                </tr>
		<tr>
		  <td valign="top" class="vncell">Default Gateway</td>
		  <td class="vtable">
			<input name="defaultgw" type="checkbox" id="defaultgw" value="yes" <?php if ($pconfig['defaultgw'] == true) echo "checked"; ?> />
			<b>Default Gateway</b><br>
			This will select the above gateway as the default gateway.
		  </td>
		</tr>
		<tr>
		  <td valign="top" class="vncell">Disable Gateway Monitoring</td>
		  <td class="vtable">
			<input name="monitor_disable" type="checkbox" id="monitor_disable" value="yes" <?php if ($pconfig['monitor_disable'] == true) echo "checked"; ?> onClick="monitor_change()" />
			<b>Disable Gateway Monitoring</b><br>
			This will consider this gateway as always being up.
		  </td>
		</tr>
		<tr>
		  <td valign="top" class="vncell">Monitor IP</td>
		  <td class="vtable">
			<?php
				if ($pconfig['gateway'] == $pconfig['monitor'])
					$monitor = "";
				else
					$monitor = htmlspecialchars($pconfig['monitor']);
			?>
			<input name="monitor" type="text" id="monitor" value="<?php echo $monitor; ?>" /><br />
			 Enter an alternative address here to be used to monitor the link. This is used for the
			 quality RRD graphs as well as the load balancer entries. Use this if the gateway does not respond
			 to ICMP echo requests. (pings)

		  </td>
		</tr>
		<tr>
			<td valign="top" class="vncell">Description</td>
			<td class="vtable">
				<input name="descr" type="text" id="descr" value="<?=htmlspecialchars($pconfig['descr']);?>">
				<br>You may enter a description here for your reference.
			</td>
		</tr>
		<tr>
			<td class="vncell"></td>
			<td class="vtable">
				<input name="Submit" type="submit" class="btn btn-inverse" value="Save">
				<input type="button" value="İptal" class="btn btn-default"  onclick="history.back()">
				<?php if (isset($id) && $a_gateways[$id]): ?>
				<input name="id" type="hidden" value="<?=htmlspecialchars($id);?>">
				<?php endif; ?>
			</td>
	</tr>
</table>
</form>
</div>
<script language="JavaScript">
monitor_change();
</script>
</body>
</html>
