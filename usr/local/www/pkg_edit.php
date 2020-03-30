<?php
/* $Id$ */
/*
	pkg_edit.php
	Copyright (C) 2004-2010 Scott Ullrich <sullrich@gmail.com>
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

ini_set('max_execution_time', '0');

require('guiconfig.inc');
require_once('functions.inc');
require_once('filter.inc');
require_once('shaper.inc');

function pfSenseHeader($location) { header("Location: " . $location); }

function gentitle_pkg($pgname) {
	global $pfSense_config;
	return $pfSense_config['system']['hostname'] . "." . $pfSense_config['system']['domain'] . " - " . $pgname;
}

$xml = htmlspecialchars($_GET['xml']);
if($_POST['xml']) $xml = htmlspecialchars($_POST['xml']);

if($xml == "") {
			print_info_box_np("ERROR: Couldn't find file.");
			die;
} else {
			$pkg = parse_xml_config_pkg("/usr/local/pkg/" . $xml, "packagegui");
}

if($pkg['include_file'] <> "") {
	require_once($pkg['include_file']);
}

if (!isset($pkg['adddeleteeditpagefields']))
	$only_edit = true;
else
	$only_edit = false;

$package_name = $pkg['menu'][0]['name'];
$section	  = $pkg['menu'][0]['section'];
$config_path  = $pkg['configpath'];
$name		 = $pkg['name'];
$title		= $pkg['title'];
$pgtitle	  = $title;

$id = $_GET['id'];
if (isset($_POST['id']))
	$id = htmlspecialchars($_POST['id']);

if(!$id && !$_POST)
	$id = "0";

if(!is_numeric($id)) {
	Header("Location: /");
	exit;
}

if($pkg['custom_php_global_functions'] <> "")
		eval($pkg['custom_php_global_functions']);

if(!is_array($config['installedpackages'][xml_safe_fieldname($pkg['name'])]['config']))
	$config['installedpackages'][xml_safe_fieldname($pkg['name'])]['config'] = array();

if ((count($config['installedpackages'][xml_safe_fieldname($pkg['name'])]['config']) > 0)
	&& ($config['installedpackages'][xml_safe_fieldname($pkg['name'])]['config'][0] == ""))
	array_shift($config['installedpackages'][xml_safe_fieldname($pkg['name'])]['config']);

$a_pkg = &$config['installedpackages'][xml_safe_fieldname($pkg['name'])]['config'];

if($_GET['savemsg'] <> "")
	$savemsg = htmlspecialchars($_GET['savemsg']);

if($pkg['custom_php_command_before_form'] <> "")
	eval($pkg['custom_php_command_before_form']);

if ($_POST) {

	if($_POST['act'] == "del") {
		if($pkg['custom_delete_php_command']) {
			if($pkg['custom_php_command_before_form'] <> "")
				eval($pkg['custom_php_command_before_form']);
			eval($pkg['custom_delete_php_command']);
		}
		write_config();

		if($pkg['custom_php_resync_config_command'] <> "") {
			if($pkg['custom_php_command_before_form'] <> "")
				eval($pkg['custom_php_command_before_form']);
			eval($pkg['custom_php_resync_config_command']);
		}
	} else {
		if($pkg['custom_add_php_command']) {
			if($pkg['donotsave'] <> "" or $pkg['preoutput'] <> "") {
			?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>
<?php
			}
			if($pkg['preoutput']) echo "<pre>";
			eval($pkg['custom_add_php_command']);
			if($pkg['preoutput']) echo "</pre>";
		}
	}

	if($pkg['donotsave'] <> "") exit;

	$firstfield = "";
	$rows = 0;

	$input_errors = array();
	$reqfields = array();
	$reqfieldsn = array();
	foreach ($pkg['fields']['field'] as $field) {
		if (($field['type'] == 'input') && isset($field['required'])) {
			if($field['fieldname'])
				$reqfields[] = $field['fieldname'];
			if($field['fielddescr'])
				$reqfieldsn[] = $field['fielddescr'];
		}
	}
	do_input_validation($_POST, $reqfields, $reqfieldsn, &$input_errors);

	if ($pkg['custom_php_validation_command'])
		eval($pkg['custom_php_validation_command']);

	if (!$input_errors) {
		$pkgarr = array();
		foreach ($pkg['fields']['field'] as $fields) {
			if($fields['type'] == "sorting")
				continue;
			if($fields['type'] == "listtopic")
				continue;
			if($fields['type'] == "rowhelper") {
				for($x=0; $x<99; $x++) {
					foreach($fields['rowhelper']['rowhelperfield'] as $rowhelperfield) {
						if($firstfield == "")  {
						  $firstfield = $rowhelperfield['fieldname'];
						} else {
						  if($firstfield == $rowhelperfield['fieldname']) $rows++;
						}
						$fieldname = str_replace("\\", "", $rowhelperfield['fieldname']);
						$comd = "\$value = \$_POST['" . $fieldname . $x . "'];";
						eval($comd);
						if($value <> "") {
							$comd = "\$pkgarr['row'][" . $x . "]['" . $fieldname . "'] = \"" . $value . "\";";
							eval($comd);
						}
					}
				}
			}

			else {
				$fieldname  = $fields['fieldname'];
				if ($fieldname == "interface_array") {
					$fieldvalue = $_POST[$fieldname];
				} elseif (is_array($_POST[$fieldname])) {
					$fieldvalue = implode(',', $_POST[$fieldname]);
				} else {
					$fieldvalue = trim($_POST[$fieldname]);
					if ($fields['encoding'] == 'base64')
						$fieldvalue = base64_encode($fieldvalue);
				}
				if($fieldname)
					$pkgarr[$fieldname] = $fieldvalue;
			}
		}

		if (isset($id) && $a_pkg[$id])
			$a_pkg[$id] = $pkgarr;
		else
			$a_pkg[] = $pkgarr;

		write_config("Proxy Server configured");
		if($pkg['custom_add_php_command_late'] <> "") {
			eval($pkg['custom_add_php_command_late']);
		}

		if (isset($pkg['filter_rules_needed']))
			filter_configure();

		if($pkg['custom_php_resync_config_command'] <> "") {
			eval($pkg['custom_php_resync_config_command']);
		}

		parse_package_templates();

		if($pkg['start_command'] <> "")
			exec($pkg['start_command'] . ">/dev/null 2&>1");

		if($pkg['restart_command'] <> "")
			exec($pkg['restart_command'] . ">/dev/null 2&>1");

		if($pkg['aftersaveredirect'] <> "") {
			pfSenseHeader($pkg['aftersaveredirect']);
		} elseif(!$pkg['adddeleteeditpagefields']) {
			pfSenseHeader("pkg_edit.php?xml={$xml}&id=0");
		} elseif(!$pkg['preoutput']) {
			pfSenseHeader("pkg.php?xml=" . $xml);
		}
		exit;
	} else {
		$get_from_post = true;
	}

}

if($pkg['title'] <> "") {
	$edit = ($only_edit ? '' : ': ' . 'Edit');
	$title = $pkg['title'] . $edit;
}
else
	$title = "Package Editor";

$pgtitle = $title;
include("head.inc");

if ($pkg['custom_php_after_head_command'])
	eval($pkg['custom_php_after_head_command']);

?>

<body>
<script type="text/javascript" language="javascript" src="/javascript/row_helper_dynamic.js"> </script>

<?php include("fbegin.inc"); ?>
<?php if (!empty($input_errors)) print_input_errors($input_errors); ?>
<form name="iform" action="pkg_edit.php" method="post">
<input type="hidden" name="xml" value="<?= $xml ?>">
<?php if ($savemsg) print_info_box($savemsg); ?>
<table width="100%" cellpadding="0" cellspacing="0">
<?php
if ($pkg['tabs'] <> "") {
	$tab_array = array();
	foreach($pkg['tabs']['tab'] as $tab) {
		if($tab['tab_level'])
			$tab_level = $tab['tab_level'];
		else
			$tab_level = 1;
		if(isset($tab['active'])) {
			$active = true;
		} else {
			$active = false;
		}
		if(isset($tab['no_drop_down']))
			$no_drop_down = true;
		$urltmp = "";
		if($tab['url'] <> "") $urltmp = $tab['url'];
		if($tab['xml'] <> "") $urltmp = "pkg_edit.php?xml=" . $tab['xml'];

 		$addresswithport = getenv("HTTP_HOST");
		$colonpos = strpos($addresswithport, ":");
		if ($colonpos !== False) {
			$myurl = substr($addresswithport, 0, $colonpos);
		} else {
			$myurl = $addresswithport;
		}
		$url = str_replace('$myurl', $myurl, $urltmp);

		$tab_array[$tab_level][] = array(
						$tab['text'],
						$active,
						$url
					);
		}

	ksort($tab_array);
	foreach($tab_array as $tab) {
		echo '<tr><td>';
		display_top_tabs($tab, $no_drop_down);
		echo '</td></tr>';
	}
}
?>
<tr><td><div id="mainarea"><table class="tabcont"  cellpadding="6" cellspacing="0">
<?php
	$cols = 0;

	foreach ($pkg['fields']['field'] as $pkga) {
		if ($pkga['type'] == "sorting")
			continue;

		if ($pkga['type'] == "listtopic") {
			if(isset($pkga['advancedfield']) && $adv_enabled) {
				$advanced .= "<td></td>";
				$advanced .= "<tr><td colspan=\"2\" class=\"listtopic\">" . $pkga['name'] . "<br></td></tr>\n";
			} else {
				echo "<td>&nbsp;</td>";
				echo "<tr><td colspan=\"2\" class=\"listtopic\">" . $pkga['name'] . "<br></td></tr>\n";
			}
			continue;
		}

		if(!$pkga['combinefieldsend']) {
			if(isset($pkga['advancedfield']) && $adv_enabled)
				$advanced .= "<tr valign=\"top\">";
			else
		  		echo "<tr valign=\"top\">";
		}

		$size = "";

		if(!$pkga['dontdisplayname']) {
			if(isset($pkga['advancedfield']) && $adv_enabled) {
				$advanced .= "<td class=\"vncell\">";
				$advanced .= fixup_string($pkga['fielddescr']);
				$advanced .= "</td>";
			} else {
				echo "<td class=\"vncell\">";
				echo fixup_string($pkga['fielddescr']);
				echo "</td>";
			}
		}

		if(!$pkga['dontcombinecells'])
			if(isset($pkga['advancedfield']) && $adv_enabled)
				$advanced .= "<td class=\"vtable\">";
			else
				echo "<td class=\"vtable\">";
		$fieldname = $pkga['fieldname'];

		if ($get_from_post)
		{
			$value = $_POST[$fieldname];
			if (is_array($value)) $value = implode(',', $value);
		}

		else
		{
			if (isset($id) && $a_pkg[$id])
				$value = $a_pkg[$id][$fieldname];
			else
				$value = $pkga['default_value'];
		}

		if($pkga['type'] == "input")
		{
			if($pkga['class']) $class = " class='" . $pkga['class'] . "' ";
				echo "<input type='text' " . $class . " id='" . $pkga['fieldname'] . "' name='" . $pkga['fieldname'] . "' value='" . $value . "'>\n";
			echo "<br>" . fixup_string($pkga['description']) . "\n";
		}

		else if($pkga['type'] == "password")
		{
			if($pkga['size']) $size = " size='" . $pkga['size'] . "' ";
				echo "<input " . $size . " id='" . $pkga['fieldname'] . "' type='password' " . $size . " name='" . $pkga['fieldname'] . "' value='" . $value . "'>\n";
			echo "<br>" . fixup_string($pkga['description']) . "\n";
		}

		else if($pkga['type'] == "select")
		{
			$fieldname = $pkga['fieldname'];

			if (isset($pkga['multiple']))
			{
	  			$multiple = 'multiple="multiple"';
				$items = explode(',', $value);
				$fieldname .= "[]";
			}
			else
			{
				$multiple = '';
				$items = array($value);
			}

			$size = (isset($pkga['size']) ? "size=\"{$pkga['size']}\"" : '');
			$onchange = (isset($pkga['onchange']) ? "onchange=\"{$pkga['onchange']}\"" : '');

				echo "<select id='" . $pkga['fieldname'] . "' $multiple $size $onchange id=\"$fieldname\" name=\"$fieldname\">\n";
				foreach ($pkga['options']['option'] as $opt) {
					$selected = '';
					if (in_array($opt['value'], $items)) $selected = 'selected="selected"';
					echo "\t<option name=\"{$opt['name']}\" value=\"{$opt['value']}\" $selected>{$opt['name']}</option>\n";
				}
				echo "</select>\n<br />\n" . fixup_string($pkga['description']) . "\n";

		}

		else if($pkga['type'] == "select_source") {
			$fieldname = $pkga['fieldname'];
			if (isset($pkga['multiple'])) {
				$multiple = 'multiple="multiple"';
				$items = explode(',', $value);
				$fieldname .= "[]";
			} else {
				$multiple = '';
				$items = array($value);
			}
			$size = (isset($pkga['size']) ? "size=\"{$pkga['size']}\"" : '');
			$onchange = (isset($pkga['onchange']) ? "onchange=\"{$pkga['onchange']}\"" : '');

			if(isset($pkga['advancedfield']) && $adv_enabled) {
				$js_array[] = $pkga['fieldname'];
				$advanced .= display_advanced_field($pkga['fieldname']);
				$advanced .= "<select id='" . $pkga['fieldname'] . "' $multiple $size $onchange id=\"{$fieldname}\" name=\"{$fieldname}\">\n";
				$advanced .= "</div>\n";
			} else {
				echo "<select id='" . $pkga['fieldname'] . "' $multiple $size $onchange id=\"$fieldname\" name=\"$fieldname\">\n";
			}
			$source_url = $pkga['source'];
			eval("\$pkg_source_txt = &$source_url;");
			foreach ($pkg_source_txt as $opt) {
				$selected = '';
				if($pkga['source_name']) {
					$source_name = $opt[$pkga['source_name']];
				} else {
					$source_name = $opt[$pkga['name']];
				}
				if($pkga['source_value']) {
					$source_value = $opt[$pkga['source_value']];
				} else {
					$source_value = $opt[$pkga['value']];
				}
				if (in_array($source_value, $items)) $selected = 'selected="selected"';
					if(isset($pkga['advancedfield']) && $adv_enabled)
						$advanced .= "\t<option name=\"{$source_name}\" value=\"{$source_value}\" $selected>{$source_name}</option>\n";
					else
						echo "\t<option name=\"{$source_name}\" value=\"{$source_value}\" $selected>{$source_name}</option>\n";
			}

			if(isset($pkga['advancedfield']) && $adv_enabled)
				$advanced .= "</select>\n<br />\n" . fixup_string($pkga['description']) . "\n";
			else
				echo "</select>\n<br />\n" . fixup_string($pkga['description']) . "\n";
		}

		else if($pkga['type'] == "vpn_selection") {
			if(isset($pkga['advancedfield']) && $adv_enabled) {
				$js_array[] = $pkga['fieldname'];
				$advanced .= display_advanced_field($pkga['fieldname']);
				$advanced .= "<select id='" . $pkga['fieldname'] . "' name='" . $vpn['name'] . "'>\n";
				foreach ($config['ipsec']['phase1'] as $vpn) {
					$advanced .= "\t<option value=\"" . $vpn['descr'] . "\">" . $vpn['descr'] . "</option>\n";
				}
				$advanced .= "</select>\n";
				$advanced .= "<br>" . fixup_string($pkga['description']) . "\n";
				$advanced .= "</div>\n";
			} else {
				echo "<select id='" . $pkga['fieldname'] . "' name='" . $vpn['name'] . "'>\n";
				foreach ($config['ipsec']['phase1'] as $vpn) {
					echo "\t<option value=\"" . $vpn['descr'] . "\">" . $vpn['descr'] . "</option>\n";
				}
				echo "</select>\n";
				echo "<br>" . fixup_string($pkga['description']) . "\n";
			}
		}

		else if($pkga['type'] == "checkbox") {
			$checkboxchecked = "";
			if($value == "on") $checkboxchecked = " CHECKED";

			if (isset($pkga['enablefields']) || isset($pkga['checkenablefields']))
				$onclick = ' onclick="javascript:enablechange();"';
			echo "<input id='" . $pkga['fieldname'] . "' type='checkbox' name='" . $pkga['fieldname'] . "'" . $checkboxchecked . $onclick . ">\n";
			echo fixup_string($pkga['description']) . "\n";

		}

		else if($pkga['type'] == "textarea")
		{
			if($pkga['rows']) $rows = " rows='" . $pkga['rows'] . "' ";
			if($pkga['cols']) $cols = " cols='" . $pkga['cols'] . "' ";
			if($pkga['wrap'] == "off")
				$wrap = 'wrap="off" style="white-space:nowrap;"';
			else $wrap = '';

			if (($pkga['encoding'] == 'base64') && !$get_from_post && !empty($value)) $value = base64_decode($value);
				echo "<textarea " . $rows . $cols . " name='" . $pkga['fieldname'] . "' ".$wrap.">" . $value . "</textarea>\n";
			echo "<br>" . fixup_string($pkga['description']) . "\n";
		}

		else if($pkga['type'] == "interfaces_selection")
		{
			$size = ($pkga['size'] ? "size=\"{$pkga['size']}\"" : '');
			$multiple = '';
			$fieldname = $pkga['fieldname'];
			if (isset($pkga['multiple'])) {
				$fieldname .= '[]';
				$multiple = 'multiple';
			}
			if(isset($pkga['advancedfield']) && $adv_enabled) {
				$js_array[] = $pkga['fieldname'];
				$advanced .= display_advanced_field($pkga['fieldname']);
				$advanced .= "<select id='" . $pkga['fieldname'] . "' name=\"$fieldname\" $size $multiple>\n";
			} else {
				echo "<select id='" . $pkga['fieldname'] . "' name=\"$fieldname\" $size $multiple>\n";
			}
			$ifaces = get_configured_interface_with_descr();
			$additional_ifaces = $pkga['add_to_interfaces_selection'];
			if (!empty($additional_ifaces))
				$ifaces = array_merge($ifaces, explode(',', $additional_ifaces));
			if(is_array($value))
				$values = $value;
			else
				$values  =  explode(',',  $value);
			$ifaces["lo0"] = "loopback";
			foreach($ifaces as $ifname => $iface)
			{
				$selected = (in_array($ifname, $values) ? 'selected' : '');
				if(isset($pkga['advancedfield']) && $adv_enabled)
					$advanced .= "<option value=\"$ifname\" $selected>$iface</option>\n";
				else
					echo "<option value=\"$ifname\" $selected>$iface</option>\n";
			}

			echo "</select>\n<br />" . fixup_string($pkga['description']) . "\n";
		}

		else if($pkga['type'] == "radio")
			echo "<input type='radio' id='" . $pkga['fieldname'] . "' name='" . $pkga['fieldname'] . "' value='" . $value . "'>";

		else if($pkga['type'] == "rowhelper")
		{

		?>
			<script type="text/javascript" language='javascript'>
			<!--
			<?php
				$rowcounter = 0;
				$fieldcounter = 0;
				foreach($pkga['rowhelper']['rowhelperfield'] as $rowhelper) {
					echo "rowname[" . $fieldcounter . "] = \"" . $rowhelper['fieldname'] . "\";\n";
					echo "rowtype[" . $fieldcounter . "] = \"" . $rowhelper['type'] . "\";\n";
					$fieldcounter++;
				}
			?>
			-->
			</script>
			<table name="maintable" id="maintable">
			<tr>
			<?php
				foreach($pkga['rowhelper']['rowhelperfield'] as $rowhelper) {
				  echo "<td><b>" . fixup_string($rowhelper['fielddescr']) . "</td>\n";
				}
				echo "</tr>";

				echo "<tr>";
				  // XXX: traverse saved fields, add back needed rows.
				echo "</tr>";

				echo "<tr>\n";
				$rowcounter = 0;
				$trc = 0;
				if(isset($a_pkg[$id]['row'])) {
					foreach($a_pkg[$id]['row'] as $row) {
					/*
					 * loop through saved data for record if it exists, populating rowhelper
					 */
						foreach($pkga['rowhelper']['rowhelperfield'] as $rowhelper) {
							unset($value);
							if($rowhelper['value'] <> "") $value = $rowhelper['value'];
							$fieldname = $rowhelper['fieldname'];
							// if user is editing a record, load in the data.
							if (isset($id) && $a_pkg[$id]) {
								$value = $row[$fieldname];
							}
							$options = "";
							$type = $rowhelper['type'];
							$fieldname = $rowhelper['fieldname'];
							if($type == "option")
								$options = &$rowhelper['options']['option'];
							if($rowhelper['size'])
								$size = $rowhelper['size'];
							else if ($pkga['size'])
								$size = $pkga['size'];
							else
								$size = "8";
							display_row($rowcounter, $value, $fieldname, $type, $rowhelper, $size);
							// javascript helpers for row_helper_dynamic.js
							echo "</td>\n";
							echo "<script language=\"JavaScript\">\n";
							echo "<!--\n";
							echo "newrow[" . $trc . "] = \"" . $text . "\";\n";
							echo "-->\n";
							echo "</script>\n";
							$text = "";
							$trc++;
						}

						$rowcounter++;
						echo "<td>";
						echo "<a onclick=\"removeRow(this); return false;\" href=\"#\"><img border=\"0\" src=\"./themes/".$g['theme']."/images/icons/icon_x.gif\" /></a>";
						echo "</td>\n";
						echo "</tr>\n";
					}
				}
				if($trc == 0) {

					foreach($pkga['rowhelper']['rowhelperfield'] as $rowhelper) {
						if($rowhelper['value'] <> "") $value = $rowhelper['value'];
						$fieldname = $rowhelper['fieldname'];
						$options = "";
						$type = $rowhelper['type'];
						$fieldname = $rowhelper['fieldname'];
						if($type == "option") $options = &$rowhelper['options']['option'];
						if($rowhelper['size'] <> "")
							$size = $rowhelper['size'];
						else if ($pkga['size'])
							$size = $pkga['size'];
						else
							$size = 8;
						display_row($rowcounter, $value, $fieldname, $type, $rowhelper, $size);
						// javascript helpers for row_helper_dynamic.js
						echo "</td>\n";
						echo "<script language=\"JavaScript\">\n";
						echo "<!--\n";
						echo "newrow[" . $trc . "] = \"" . $text . "\";\n";
						echo "-->\n";
						echo "</script>\n";
						$text = "";
						$trc++;
					}

					$rowcounter++;
					echo "<td>";
					echo "<a onclick=\"removeRow(this); return false;\" href=\"#\"><img border=\"0\" src=\"./themes/".$g['theme']."/images/icons/icon_x.gif\" /></a>";
					echo "</td>\n";
					echo "</tr>\n";
				}
			?>
			</table>

		<?php if ($pkga['size']): ?>
			<br><a onClick="javascript:addRowTo('maintable', <?=$pkga['size'];?>); return false;" href="#"><img border="0" src="./themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif"></a>
		<?php else: ?>
			<br><a onClick="javascript:addRowTo('maintable'); return false;" href="#"><img border="0" src="./themes/<?= $g['theme']; ?>/images/icons/icon_plus.gif"></a>
		<?php endif; ?>
		<br><?php if($pkga['description'] != "") echo $pkga['description']; ?>
		<script language="JavaScript">
		<!--
		field_counter_js = <?= $fieldcounter ?>;
		rows = <?= $rowcounter ?>;
		totalrows = <?php echo $rowcounter; ?>;
		loaded = <?php echo $rowcounter; ?>;
		//typesel_change();
		//-->
		</script>

		<?php
		  }
		  if($pkga['typehint']) echo " " . $pkga['typehint'];
		 ?>

	  <?php
	  if(!$pkga['combinefieldsbegin']) echo "</td></tr>";
	  $i++;
  }
 ?>

  <tr>
	<td class="vncell"></td>
	<td class="vtable">
<?php
if($pkg['note'] != "")
	echo "<p><span class=\"red\"><strong>" . "NOT". ":</strong></span> {$pkg['note']}</p>";
	  echo "<input name=\"id\" type=\"hidden\" value=\"$id\">";
?>
	  <input name="Submit" class="btn btn-inverse" type="submit" value="Save">
<?php if (!$only_edit): ?>
	  <input type="button" class="btn" value="Cancel" onclick="history.back()">
<?php endif; ?>
	</td>
  </tr>
<?php if (isset($advanced)) {
				echo $advanced;
?>
	<tr>
		<td class="vncell"></td>
		<td class="vtable">
			<input class="btn btn-success" name="Submit" type="submit" value="Save">
			<?php if (!$only_edit): ?>
				<input class="btn" type="button" value="Cancel" onclick="history.back()">
			<?php endif; ?>
		</td>
	</tr>
<?php
		}
?>
</table>
</div></tr>
</table>
</form>

<?php if ($pkg['custom_php_after_form_command']) eval($pkg['custom_php_after_form_command']); ?>

</div>
</body>
</html>

<?php
function display_row($trc, $value, $fieldname, $type, $rowhelper, $size) {
	global $text, $config;
	echo "<td>\n";
	if($type == "input") {
		echo "<input size='" . $size . "' name='" . $fieldname . $trc . "' id='" . $fieldname . $trc . "' value='" . $value . "'>\n";
	} else if($type == "checkbox") {
		if($value)
			echo "<input size='" . $size . "' type='checkbox' id='" . $fieldname . $trc . "' name='" . $fieldname . $trc . "' value='ON' CHECKED>\n";
		else
			echo "<input size='" . $size . "' type='checkbox' id='" . $fieldname . $trc . "' name='" . $fieldname . $trc . "' value='ON'>\n";
	} else if($type == "text") {
		echo "<input size='" . $size . "' type='text' id='" . $fieldname . $trc . "' name='" . $fieldname . $trc . "' value='" . $value . "'>\n";
	} else if($type == "password") {
		echo "<input size='" . $size . "' type='password' id='" . $fieldname . $trc . "' name='" . $fieldname . $trc . "' value='" . $value . "'>\n";
	} else if($type == "textarea") {
		echo "<textarea rows='2' cols='12' id='" . $fieldname . $trc . "' name='" . $fieldname . $trc . "'>" . $value . "</textarea>\n";
	} else if($type == "select") {
		echo "<select id='" . $fieldname . $trc . "' name='" . $fieldname . $trc . "'>\n";
		foreach($rowhelper['options']['option'] as $rowopt) {
			$selected = "";
			if($rowopt['value'] == $value) $selected = " SELECTED";
			$text .= "<option value='" . $rowopt['value'] . "'" . $selected . ">" . $rowopt['name'] . "</option>";
			echo "<option value='" . $rowopt['value'] . "'" . $selected . ">" . $rowopt['name'] . "</option>\n";
		}
		echo "</select>\n";
	} else if($type == "select_source") {
		echo "<select id='" . $fieldname . $trc . "' name='" . $fieldname . $trc . "'>\n";
		$source_url = $rowhelper['source'];
		eval("\$pkg_source_txt = &$source_url;");
		foreach($pkg_source_txt as $opt) {
			$selected = "";
			if($rowhelper['source_name']) {
				$source_name = $opt[$rowhelper['source_name']];
				} else {
				$source_name = $opt[$rowhelper['name']];
				}
		  	if($rowhelper['source_value']) {
				$source_value = $opt[$rowhelper['source_value']];
		  	} else {
				$source_value = $opt[$rowhelper['value']];
		  	}
			if($source_value == $value)
				$selected = " SELECTED";
			$text .= "<option value='" . $source_value . "'" . $selected . ">" . $source_name . "</option>";
			echo "<option value='" . $source_value . "'" . $selected . ">" . $source_name . "</option>\n";
		}
		echo "</select>\n";
	}
}

function fixup_string($string) {
	global $config;
	$https = "";
	$port = $config['system']['webguiport'];
	if($port <> "443" and $port <> "80")
		$urlport = ":" . $port;
	else
		$urlport = "";

	if($config['system']['webgui']['protocol'] == "https") $https = "s";
	$myurl = "http" . $https . "://" . getenv("HTTP_HOST") . $urlport;
	$newstring = str_replace("\$myurl", $myurl, $string);
	$string = $newstring;
	$curwanip = get_interface_ip();
	$newstring = str_replace("\$wanip", $curwanip, $string);
	$string = $newstring;
	$lancfg = $config['interfaces']['lan'];
	$lanip = $lancfg['ipaddr'];
	$newstring = str_replace("\$lanip", $lanip, $string);
	$string = $newstring;
	return $newstring;
}

function parse_package_templates() {
	global $pkg, $config;
	$rows = 0;
	if($pkg['templates']['template'] <> "")
		foreach($pkg['templates']['template'] as $pkg_template_row) {
		$filename = $pkg_template_row['filename'];
		$template_text = $pkg_template_row['templatecontents'];
		$firstfield = "";
		foreach ($pkg['fields']['field'] as $fields) {
			if($fields['type'] == "rowhelper") {
								$row_helper_total_rows = 0;
				for($x=0; $x<99; $x++) {
					foreach($fields['rowhelper']['rowhelperfield'] as $rowhelperfield) {
						if($firstfield == "")  {
						  $firstfield = $rowhelperfield['fieldname'];
						} else {
						  if($firstfield == $rowhelperfield['fieldname']) $rows++;
						}
						$comd = "\$value = \$_POST['" . $rowhelperfield['fieldname'] . $x . "'];";
						$value = "";
						eval($comd);
						if($value <> "") {
						} else {
							$row_helper_total_rows = $rows;
							break;
						}
					}
				}
			}
		}

		$template_text = str_replace("$domain_total_rows", $row_helper_total_rows, $template_text);

		foreach ($pkg['fields']['field'] as $fields) {
			if($fields['type'] == "rowhelper") {
				for($x=0; $x<99; $x++) {
					$row_helper_data = "";
					$isfirst = 0;
					foreach($fields['rowhelper']['rowhelperfield'] as $rowhelperfield) {
						if($firstfield == "")  {
						  $firstfield = $rowhelperfield['fieldname'];
						} else {
						  if($firstfield == $rowhelperfield['fieldname']) $rows++;
						}
						$comd = "\$value = \$_POST['" . $rowhelperfield['fieldname'] . $x . "'];";
						eval($comd);
						if($value <> "") {
							if($isfirst == 1) $row_helper_data .= "  " ;
							$row_helper_data .= $value;
							$isfirst = 1;
						}
						$sep = "";
						ereg($rowhelperfield['fieldname'] . "_fieldvalue\[(.*)\]", $template_text, $sep);
						foreach ($sep as $se) $seperator = $se;
						if($seperator <> "") {
							$row_helper_data = ereg_replace("  ", $seperator, $row_helper_data);
							$template_text = ereg_replace("\[" . $seperator . "\]", "", $template_text);
						}
						$template_text = str_replace($rowhelperfield['fieldname'] . "_fieldvalue", $row_helper_data, $template_text);
					}
				}
			} else {
				$fieldname  = $fields['fieldname'];
				$fieldvalue = $_POST[$fieldname];
				$template_text = str_replace($fieldname . "_fieldvalue", $fieldvalue, $template_text);
			}
		}

		$template_text = str_replace("\\n", "\n", $template_text);

		$fout = fopen($filename,"w");
		fwrite($fout, $template_text);
		fclose($fout);
		}
}

function display_advanced_field($fieldname) {
	$div = "<div id='showadv_" . $fieldname . "'>\n";
	$div .= "<input type='button' onClick='show_" . $fieldname . "()' value='" . gettext("Advanced") . "'></input> - " . gettext("Show advanced option") ."</div>\n";
	$div .= "<div id='show_" . $fieldname . "' style='display:none'>\n";
	return $div;
}

?>
