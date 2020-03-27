<?php
/*
	system_routes_edit.php
	part of m0n0wall (http://m0n0.ch/wall)

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.

	Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>.
	Copyright (C) 2010 Scott Ullrich
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

function staticroutecmp($a, $b) {
	return strcmp($a['network'], $b['network']);
}

function staticroutes_sort() {
        global $g, $config;

        if (!is_array($config['staticroutes']['route']))
                return;

        usort($config['staticroutes']['route'], "staticroutecmp");
}

require('guiconfig.inc');

if (!is_array($config['staticroutes']['route']))
	$config['staticroutes']['route'] = array();

$a_routes = &$config['staticroutes']['route'];
$a_gateways = return_gateways_array(true);

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($_GET['dup'])) {
	$id = $_GET['dup'];
}

if (isset($id) && $a_routes[$id]) {
	list($pconfig['network'],$pconfig['network_subnet']) =
		explode('/', $a_routes[$id]['network']);
	$pconfig['gateway'] = $a_routes[$id]['gateway'];
	$pconfig['descr'] = base64_decode($a_routes[$id]['descr']);
}

if (isset($_GET['dup']))
	unset($id);

if ($_POST) {

	unset($input_errors);
	$pconfig = $_POST;

	$reqdfields = explode(" ", "network network_subnet gateway");
	$reqdfieldsn = explode(",",
			"Destination network" . "," .
			"Destination network bit count" . "," .
			"Gateway");

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if (($_POST['network'] && !is_ipaddr($_POST['network']) && !is_alias($_POST['network']))) {
		$input_errors[] = "A valid destination network must be specified.";
	}
	if (($_POST['network_subnet'] && !is_numeric($_POST['network_subnet']))) {
		$input_errors[] = "A valid destination network bit count must be specified.";
	}
	if ($_POST['gateway']) {
		if (!isset($a_gateways[$_POST['gateway']]))
			$input_errors[] = "A valid gateway must be specified.";
	}

	$current_targets = get_staticroutes(true);
	$new_targets = array();
	if (is_ipaddr($_POST['network'])) {
		$osn = gen_subnet($_POST['network'], $_POST['network_subnet']) . "/" . $_POST['network_subnet'];
		$new_targets[] = $osn;
	} elseif (is_alias($_POST['network'])) {
		$osn = $_POST['network'];
		foreach (filter_expand_alias_array($_POST['network']) as $tgt) {
			if (is_ipaddr($tgt))
				$tgt .= "/32";
			if (!is_subnet($tgt))
				continue;
			$new_targets[] = $tgt;
		}
	}

	if (!isset($id))
		$id = count($a_routes);
	$oroute = $a_routes[$id];
	if (!empty($oroute)) {
		$old_targets = array();
		if (is_alias($oroute['network'])) {
			foreach (filter_expand_alias_array($oroute['network']) as $tgt) {
				if (is_ipaddr($tgt))
					$tgt .= "/32";
				if (!is_subnet($tgt))
					continue;
				$old_targets[] = $tgt;
			}
		} else {
			$old_targets[] = $oroute['network'];
		}
	}

	$overlaps = array_intersect($current_targets, $new_targets);

	if(isset($old_targets))
		$overlaps = array_diff($overlaps, $old_targets);

	if (count($overlaps)) {
		$input_errors[] = "A route to this destination network already exists." . ": " . implode(", ", $overlaps);
	}

	if (!$input_errors) {
		$route = array();
		$route['network'] = $osn;
		$route['gateway'] = $_POST['gateway'];
		$route['descr'] = base64_encode($_POST['descr']);

		if (file_exists("{$g['tmp_path']}/.system_routes.apply"))
			$toapplylist = unserialize(file_get_contents("{$g['tmp_path']}/.system_routes.apply"));
		else
			$toapplylist = array();
		$a_routes[$id] = $route;

		if (!empty($oroute)) {
			$delete_targets = array_diff($old_targets, $new_targets);
			if (count($delete_targets))
				foreach ($delete_targets as $dts)
					$toapplylist[] = "/sbin/route delete {$dts}";
		}
		file_put_contents("{$g['tmp_path']}/.system_routes.apply", serialize($toapplylist));
		staticroutes_sort();

		mark_subsystem_dirty('staticroutes');

		write_config();

		header("Location: system_routes.php");
		exit;
	}
}

$pgtitle = array('SYSTEM', 'STATIC ROUTES', 'EDIT ROUTE');

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php if ($input_errors) print_input_errors($input_errors); ?>
<form action="system_routes_edit.php" method="post" name="iform" id="iform">
<table cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td colspan="2" valign="top" class="listtopic">EDIT STATIC ROUTE</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Hedef Ağ</td>
					<td class="vtable">
					<input name="network" type="text" id="network" value="<?=htmlspecialchars($pconfig['network']);?>">
				/
					<select name="network_subnet" id="network_subnet">
						<?php for ($i = 32; $i >= 1; $i--): ?>
						<option value="<?=$i;?>" <?php if ($i == $pconfig['network_subnet']) echo "selected"; ?>>
						<?=$i;?>
						</option>
						<?php endfor; ?>
					</select>
					<br>Destination network for this static route
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Gateway</td>
					<td class="vtable">
					<select name="gateway" id="gateway">
					<?php
						foreach ($a_gateways as $gateway) {
										echo "<option value='{$gateway['name']}' ";
							if ($gateway['name'] == $pconfig['gateway'])
								echo "selected";
										echo ">" . htmlspecialchars($gateway['name']) . " - " . htmlspecialchars($gateway['gateway']) . "</option>\n";
						}
					?>
					</select> <br>
					Choose which gateway this route applies to
				</tr>
					<tr>
				<td valign="top" class="vncell">Description</td>
				<td class="vtable">
				<input name="descr" type="text" id="descr" value="<?=htmlspecialchars($pconfig['descr']);?>">
				<br> <span>You may enter a description here for your reference (not parsed).</span></td>
				</tr>
				<tr>
				<td class="vncell"></td>
				<td class="vtable">
				<input class="btn btn-inverse" id="save" name="Submit" type="submit"  value="Save">
				<input class="btn btn-default" id="cancel" type="button" value="İptal" onclick="history.back()">
				<?php if (isset($id) && $a_routes[$id]): ?>
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
