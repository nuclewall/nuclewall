<?php
/*
	firewall_aliases.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.

	Copyright (C) 2004 Scott Ullrich
	All rights reserved.

	originially part of m0n0wall (http://m0n0.ch/wall)
	Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>.
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

require_once('guiconfig.inc');
require_once('functions.inc');
require_once('filter.inc');
require_once('shaper.inc');

if (!is_array($config['aliases']['alias']))
	$config['aliases']['alias'] = array();

$a_aliases = &$config['aliases']['alias'];

if ($_POST)
{
	$pconfig = $_POST;

	if ($_POST['apply'])
	{
		$retval = 0;
		$retval = filter_configure();

		if(stristr($retval, "error") <> true)
		    $savemsg = get_std_save_message($retval);
		else
		    $savemsg = $retval;
		if ($retval == 0)
			clear_subsystem_dirty('aliases');
	}
}

if ($_GET['act'] == "del")
{
	if ($a_aliases[$_GET['id']])
	{
		$is_alias_referenced = false;
		$referenced_by = false;
		$alias_name = $a_aliases[$_GET['id']]['name'];
		// Firewall rules
		find_alias_reference(array('filter', 'rule'), array('source', 'address'), $alias_name, $is_alias_referenced, $referenced_by);
		find_alias_reference(array('filter', 'rule'), array('destination', 'address'), $alias_name, $is_alias_referenced, $referenced_by);
		find_alias_reference(array('filter', 'rule'), array('source', 'port'), $alias_name, $is_alias_referenced, $referenced_by);
		find_alias_reference(array('filter', 'rule'), array('destination', 'port'), $alias_name, $is_alias_referenced, $referenced_by);
		// NAT Rules
		find_alias_reference(array('nat', 'rule'), array('source', 'address'), $alias_name, $is_alias_referenced, $referenced_by);
		find_alias_reference(array('nat', 'rule'), array('source', 'port'), $alias_name, $is_alias_referenced, $referenced_by);
		find_alias_reference(array('nat', 'rule'), array('destination', 'address'), $alias_name, $is_alias_referenced, $referenced_by);
		find_alias_reference(array('nat', 'rule'), array('destination', 'port'), $alias_name, $is_alias_referenced, $referenced_by);
		find_alias_reference(array('nat', 'rule'), array('target'), $alias_name, $is_alias_referenced, $referenced_by);
		find_alias_reference(array('nat', 'rule'), array('local-port'), $alias_name, $is_alias_referenced, $referenced_by);
		// NAT 1:1 Rules
		//find_alias_reference(array('nat', 'onetoone'), array('external'), $alias_name, $is_alias_referenced, $referenced_by);
		//find_alias_reference(array('nat', 'onetoone'), array('source', 'address'), $alias_name, $is_alias_referenced, $referenced_by);
		find_alias_reference(array('nat', 'onetoone'), array('destination', 'address'), $alias_name, $is_alias_referenced, $referenced_by);
		// NAT Outbound Rules
		find_alias_reference(array('nat', 'advancedoutbound', 'rule'), array('source', 'network'), $alias_name, $is_alias_referenced, $referenced_by);
		find_alias_reference(array('nat', 'advancedoutbound', 'rule'), array('sourceport'), $alias_name, $is_alias_referenced, $referenced_by);
		find_alias_reference(array('nat', 'advancedoutbound', 'rule'), array('destination', 'address'), $alias_name, $is_alias_referenced, $referenced_by);
		find_alias_reference(array('nat', 'advancedoutbound', 'rule'), array('dstport'), $alias_name, $is_alias_referenced, $referenced_by);
		find_alias_reference(array('nat', 'advancedoutbound', 'rule'), array('target'), $alias_name, $is_alias_referenced, $referenced_by);
		// Alias in an alias
		find_alias_reference(array('aliases', 'alias'), array('address'), $alias_name, $is_alias_referenced, $referenced_by);
		if($is_alias_referenced == true) {
			$savemsg = sprintf("Cannot delete alias. Currently in use by '%s'.", $referenced_by);
		} else {
			unset($a_aliases[$_GET['id']]);
			write_config("An alias deleted");
			filter_configure();
			mark_subsystem_dirty('aliases');
			header("Location: firewall_aliases.php");
			exit;
		}
	}
}

function find_alias_reference($section, $field, $origname, &$is_alias_referenced, &$referenced_by)
{
	global $config;
	if(!$origname || $is_alias_referenced)
		return;

	$sectionref = &$config;
	foreach($section as $sectionname) {
		if(is_array($sectionref) && isset($sectionref[$sectionname]))
			$sectionref = &$sectionref[$sectionname];
		else
			return;
	}

	if(is_array($sectionref)) {
		foreach($sectionref as $itemkey => $item) {
			$fieldfound = true;
			$fieldref = &$sectionref[$itemkey];
			foreach($field as $fieldname) {
				if(is_array($fieldref) && isset($fieldref[$fieldname]))
					$fieldref = &$fieldref[$fieldname];
				else {
					$fieldfound = false;
					break;
				}
			}
			if($fieldfound && $fieldref == $origname) {
				$is_alias_referenced = true;
				if(is_array($item))
					$referenced_by = $item['descr'];
				break;
			}
		}
	}
}

$pgtitle = array('FIREWALL ', 'ALIASES');

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<form action="firewall_aliases.php" method="post">
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (is_subsystem_dirty('aliases')): ?><p>
<?php print_info_box_np('The alias list has been changed' . '<br>' . 'You must apply the changes in order for them to take effect.', true);?>
<?php endif; ?>
<?php pfSense_handle_custom_code("/usr/local/pkg/firewall_aliases/pre_table"); ?>
<table cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<table width="100%" class="grids sortable">
							<tr>
								<td class="head">Name</td>
								<td class="head">Values</td>
								<td class="head">Description</td>
								<td class="head"></td>
							</tr>
							<?php $i = 0; foreach ($a_aliases as $alias): ?>
							<tr>
								<td class="cell" ondblclick="document.location='firewall_aliases_edit.php?id=<?=$i;?>';">
									<?=htmlspecialchars($alias['name']);?>
								</td>
								<td class="cell" ondblclick="document.location='firewall_aliases_edit.php?id=<?=$i;?>';">
									<?php
									if ($alias["url"]) {
										echo $alias["url"] . '<br>';
									}
									if ($alias["aliasurl"]) {
										echo $alias["aliasurl"] . '<br>';
									}
									$tmpaddr = explode(' ', $alias['address']);
									$addresses = implode(", ", array_slice($tmpaddr, 0, 10));
									echo $addresses;

									if(count($tmpaddr) > 10)
									{
										echo "...";
									}
									?>
								</td>
								<td class="cell description" ondblclick="document.location='firewall_aliases_edit.php?id=<?=$i;?>';">
									<?=htmlspecialchars($alias['descr']);?>
								</td>
								<td class="cell tools">
									<a title="Edit" href="firewall_aliases_edit.php?id=<?=$i;?>">
										<i class="icon-edit"></i>
									</a>
									<a title="Delete" href="firewall_aliases.php?act=del&id=<?=$i;?>" onclick="return confirm('Do you really want to delete this alias? All elements that still use it will become invalid.')">
										<i class="icon-trash"></i>
									</a>
								</td>
							</tr>
							<?php $i++; endforeach; ?>
							<tr>
								<td class="cell" colspan="3">
								<td class="cell tools">
									<a title="Add alias" href="firewall_aliases_edit.php">
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
