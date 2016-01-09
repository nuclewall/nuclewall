<?php
require_once('globals.inc');
require_once('config.inc');
require_once('util.inc');
require_once('pfsense-utils.inc');
require_once('filter.inc');
require_once('service-utils.inc');
require_once('squidguard_configurator.inc');

sg_init(convert_pfxml_to_sgxml());

function squidguard_validate_default($post, $input_errors)
{
	squidguard_validate_acl($post, &$input_errors);
}


function squidguard_validate_acl($post, $input_errors)
{
	$pass_up = array();
	$deny_up = array();
	$pass_up_val = '';
	$pass_over = array();
	$deny_over = array();
	$pass_over_val = '';
	$id = get_item_id();

	$name = trim($post[F_NAME]);
	$description = trim($post['description']);

	if(!empty($name))
	{
		check_name_format($name, &$input_errors);

		if (!sg_check_unique_name(F_ACLS, $name))
			$input_errors[] = "'$name' isimli bir kural zaten var.";

		$sgx = array();
		$sgx[F_NAME] = $post[F_NAME];
		$sgx['source'] = trim($post['source']);

		if (strlen($sgx['source']) > 150)
				$input_errors[] = "Çok sayıda kaynak ip adresi, ağ adresi veya alan adı girdiniz.";

		sg_check_src($sgx, &$input_errors);

		if(!empty($description))
		{
			if (strlen($description) > 70)
				$input_errors[] = "'$description' boyutu 70 karakteri geçmemelidir.";
		}
	
	}

	foreach ($post as $key => $val) {
		if (substr_count($key, 'uptime_') != 0) {
			$name = str_replace('uptime_', '', $key);
			if ($name) {
				switch($val) {
					case "allow": $pass_up_val .= " $name";  break;
					case "white": $pass_up_val .= " ^$name"; break;
					case "deny" : $pass_up_val .= " !$name"; break;
				}
			}
		}
		elseif (substr_count($key, 'overtime_') != 0) {
			$name = str_replace('overtime_', '', $key);
			if ($name) {
				switch($val) {
					case "allow": $pass_over_val .= " $name";  break;
					case "white": $pass_over_val .= " ^$name"; break;
					case "deny" : $pass_over_val .= " !$name"; break;
				}
			}
		}
	}

	if ((substr_count($pass_up_val, 'all') == 0)) {
		 $pass_up_val .= ' !all';
	}

	if (!empty($pass_over_val) && (substr_count($pass_over_val, 'all') == 0)) {
		 $pass_over_val .= ' !all';
	}

	if (empty($pass_over_val))
		 $post['dest'] = "$pass_up_val";
	else $post['dest'] = "$pass_up_val [$pass_over_val]";
}


function squidguard_resync() {
 	sg_init(convert_pfxml_to_sgxml());
 	sg_reconfigure();
}


function squidguard_resync_acl() {
	global $config;

	$conf = $config['installedpackages']['squidguardacl']['config'];
	$id = isset($_POST['id']) ? $_POST['id'] : $_GET['id'];

	if (($id !== '') and is_array($conf)) {
		$src_new = array();

		foreach ($conf as $key => $src) {
			$order = $src[F_ORDER];
			$n_key = is_numeric($order) ? sprintf("%04d%04d", $order, $key) : sprintf("%04d%04d", $key, 9999);
			unset($src[F_ORDER]);
			$src_new[$n_key] = $src;
		}

		ksort($src_new);
		reset($src_new);

		$src_new = array_values($src_new);

		unset ($config['installedpackages']['squidguardacl']['config']);
		$config['installedpackages']['squidguardacl']['config'] = $src_new;
		write_config('Update squidguardacl config');

		sg_init(convert_pfxml_to_sgxml());
	}
}

function get_pkgconf_items_list($pkg_gui_name, $fieldname) {
	global $config;
	$res = '';

	$conf = $config['installedpackages'][$pkg_gui_name]['config'];
	if (is_array($conf))
		foreach($conf as $cf) $res[] = $cf[$fieldname];

	return $res;
}

function get_sgconf_items_list($data_group, $fieldname) {
	global $squidguard_config;
	$res = '';

	$conf = $squidguard_config[$data_group]['item'];
	if (is_array($conf))
		foreach($conf as $cf) $res[] = $cf[$fieldname];

	return $res;
}

function squidguard_before_form_acl($pkg, $is_acl=true) {
	global $g;
	global $squidguard_config;

	$current_id	= '';
	$sources	  = '';
	$source_items = '';
	$destinations = '';
	$dest_items	= '';
	$acls_up	  = '';
	$acls_over	= '';

	$current_id = isset($_POST['id']) ? $_POST['id'] : $_GET['id'];
	$current_id = ($current_id) ? $current_id : 0;

	$source_items = get_sgconf_items_list(F_SOURCES, 'name');
	$i=0;
	foreach($pkg['fields']['field'] as $field) {
		if ($field['fieldname'] == 'source') {
			$fld = &$pkg['fields']['field'][$i];
			if (is_array($source_items)) {
				foreach($source_items as $nm)
					$fld['options']['option'][] = array('name'=>$nm, 'value'=>$nm);
			}
		}

		if (is_array($source_items) && $field['fieldname'] == 'order') {
			$fld = &$pkg['fields']['field'][$i];
			foreach($source_items as $nmkey => $nm)
				$fld['options']['option'][] = array('name'=>$nm, 'value'=>$nmkey);
				$fld['options']['option'][] = array('name'=>'--- Last ---', 'value'=>'9999');
				$fld['options']['option'][] = array('name'=>'-----', 'value'=>'');
		}
		$i++;
	}

	$acl_dest = '';
	$acl_overdest = '';


	if ($pkg['name'] !== 'squidguarddefault') {
		 $acl_dest	 = $squidguard_config[F_ACLS]['item'][$current_id][F_DESTINATIONNAME];
		 $acl_overdest = $squidguard_config[F_ACLS]['item'][$current_id][F_OVERDESTINATIONNAME];
	}
	else $acl_dest = $squidguard_config[F_DEFAULT][F_DESTINATIONNAME];


	if ($acl_dest) {
		$acl_dest = str_replace('none', '!all', $acl_dest);
		$pss = explode(' ', $acl_dest);

		foreach($pss as $val) {
			$name = $val;
			$name = str_replace('!', '', $name);
			$name = str_replace('^', '', $name);
			if (!empty($val)) {
				switch($val[0]) {
					case '!': $acls_up[$name] = 'deny';  break;
					case '^': $acls_up[$name] = 'white'; break;
					default : $acls_up[$name] = 'allow'; break;
				}
			}
		}
	}

	if ($acl_overdest) {
		$acl_overdest = str_replace('none', '!all', $acl_overdest);

		$pss = explode(' ', $acl_overdest);
		foreach($pss as $val) {
			$name = $val;
			$name = str_replace('!', '', $name);
			$name = str_replace('^', '', $name);
			if (!empty($val)) {
				switch($val[0]) {
					case '!': $acls_over[$name] = 'deny';  break;
					case '^': $acls_over[$name] = 'white'; break;
					default : $acls_over[$name] = 'allow'; break;
				}
			}
		}
	}

	if ($squidguard_config[F_DESTINATIONS]) {
		foreach($squidguard_config[F_DESTINATIONS]['item'] as $dst) {
			$dest_items[] = array ('name'=>$dst[F_NAME],
									'upt_value'=>$acls_up[$dst[F_NAME]],
									'ovt_value'=>$acls_over[$dst[F_NAME]],
									'description'=>$dst['description']);
		}
	}

	if ($squidguard_config[F_BLACKLISTENABLED] === 'on') {
		$blk_entries = sg_entries_blacklist();
		if (!empty($blk_entries)) {
			foreach($blk_entries as $dst) {
				$dest_items[] = array ('name'=>$dst,
										'upt_value'=>$acls_up[$dst],
										'ovt_value'=>$acls_over[$dst],
										'description'=>'');
			}
		}
	}

	$dest_items[] = array('name'=>FLT_DEFAULT_ALL,
						  'upt_value'=>$acls_up[FLT_DEFAULT_ALL],
						  'ovt_value'=>$acls_over[FLT_DEFAULT_ALL],
						  'description'=>'Varsayılan erişim');

	$i=0;
	foreach($pkg['fields']['field'] as $field) {
		if (($field['fieldname'] === 'dest')) {
			$fld = &$pkg['fields']['field'][$i];
			$fld['description'] .= make_grid_controls('', $dest_items, $is_acl);
		}
		$i++;
	}

}


function make_select_options($val) {
	$sel = "selected=\"selected\"";
	
	switch($val) {
		case "allow": $opts['A'] = $sel; break;
		case "deny" : $opts['D'] = $sel; break;
		default:	  $opts['F'] = $sel; break;
	}

	unset($sel);
	return $opts;
}


function make_grid_controls($type, $items) {
	$res = '';
	$tbl = '';
	$total_rows = count($items);
	
	if ($total_rows%2 == 0)
		$center = $total_rows/2;
	else
		$center = ($total_rows + 1) / 2;
		
	for ($i=0; $i<$center; $i++)
	{
		$class = ($i%2 == 0) ? "cell" : "cell dark";
		$left[$i] = $items[$i];
		$right[$i] = $items[$i + $center];
		$name_l = trim($left[$i]['name']);
		$name_r = trim($right[$i]['name']);
		
		$opts_l = make_select_options($left[$i]['upt_value']);
		$opts_r = make_select_options($right[$i]['upt_value']);
		
		$tbl .= "<tr>\n";
		
		$tnm_l = 'uptime_' . $name_l;
		
		
		$tbl .= "\t<td class=\"$class\">$name_l</td>\n";
		
		if ($name_l) 
		{
			$tbl .= "\t<td class=\"$class\">\n\t<select class=\"select\" id=$tnm_l name=\"$tnm_l\">\n";
		
			if ($name_l !== 'all') {
				$tbl .= "\t\t<option value=none  name=\"----\"  {$opts_l['F']}></option>\n";
				$tbl .= "\t\t<option value=deny  name=\"deny\"  {$opts_l['D']}>Engelle</option>\n";
				$tbl .= "\t\t<option value=allow name=\"allow\" {$opts_l['A']}>İzin ver</option>\n";
				$tbl .= "\t</select>\n";
			}
			else {
				$tbl .= "\t<option value=allow name=\"allow\" {$opts_l['A']}>İzin ver</option>\n";
				$tbl .= "\t<option value=deny  name=\"deny\"  {$opts_l['D']}>Engelle </option>\n";
				$tbl .= "</select>\n";
			}
		}

		$tbl .= "\t</td>\n";
		$tnm_r = 'uptime_' . $name_r;
		$tbl .= "\t<td class=\"$class\">$name_r</td>\n";

		if($name_r)
		{
			$tbl .= "\t<td class=\"$class\">\n\t<select class=\"select\" id=$tnm_r name=\"$tnm_r\">\n";

			if ($name_r !== 'all') {
				$tbl .= "\t\t<option value=none  name=\"----\"  {$opts_r['F']}></option>\n";
				$tbl .= "\t\t<option value=deny  name=\"deny\"  {$opts_r['D']}>Engelle</option>\n";
				$tbl .= "\t\t<option value=allow name=\"allow\" {$opts_r['A']}>İzin ver</option>\n";
				$tbl .= "\t</select>\n";
			}
			else {
				$tbl .= "\t<option value=allow name=\"allow\" {$opts_r['A']}>İzin ver</option>\n";
				$tbl .= "\t<option value=deny  name=\"deny\"  {$opts_r['D']}>Engelle </option>\n";
				$tbl .= "</select>\n";
			}
		}

		$tbl .= "</tr>\n";
	}

	if (!empty($tbl)) {
		$res .= "\n<table class=\"grids\">\n\t<tbody>\n\t\t<tr>\n\t\t\t<th class=\"head\">Kategori</th>\n\t\t\t<th class=\"head\">Erişim</th>
		\n\t\t\t<th class=\"head\">Kategori</th>\n\t\t\t<th class=\"head\">Erişim</th>$tbl\n\t</tbody>\n</table>";
	}

	return $res;
}


function sg_check_unique_name($module_id, $name, $log='') {
	$res = true;
	$id = (isset($_GET['id'])) ? $_GET['id'] : $_POST['id'];

	$name_list = get_sgconf_items_list($module_id, 'name');
	$name_val = (is_array($name_list)) ? array_count_values($name_list) : array();
	$count_names = $name_val[$name];

	if ($count_names === 1) {
		$nm_key = array_search($name, $name_list);
		if ($id >= count($name_list)) { $res = false; }
		elseif ($nm_key && (intval($id) !== intval($nm_key))) { $res = false; }
	}
	elseif($count_names > 1) $res = false;

	return $res;
}


function squidGuard_print_javascript() {
	$javascript = '';

	$xml = ($_GET["xml"] !== '') ? $_GET["xml"] : $_POST["xml"];

	if ($xml === "squidguard_default.xml") {
		$javascript .= "\n<script language='javascript'>";
		$javascript .= "\n<!--";
		$javascript .= "\n	document.iform.dest.disabled=1;";
		$javascript .= "\n//-->";
		$javascript .= "\n</script>";
	}

	if ($xml === "squidguard_acl.xml") {
		$javascript .= "\n<script language='javascript'>";
		$javascript .= "\n<!--";
		$javascript .= "\n	document.iform.dest.disabled=1;";
		$javascript .= "\n//-->";
		$javascript .= "\n</script>";

	}
	print($javascript);
}


function convert_pfxml_to_sgxml() {
	capability_update_source();

	global $config;
	$sgxml = array();
	$pfxml = $config['installedpackages']['squidguardgeneral']['config'][0];

	$sgxml[F_LOGDIR] = '/var/squidGuard/log';
	$sgxml[F_DBHOME] = '/var/db/squidGuard';
	$sgxml[F_BINPATH] = '/usr/local/bin';
	$sgxml[F_WORKDIR] = '/usr/local/etc/squidGuard';
	$sgxml[F_SGCONF_XML] = '/usr/local/etc/squidGuard' . '/squidguard_conf.xml';
	$sgxml[F_ENABLED] = $pfxml['squidguard_enable'];
	$sgxml[F_BLACKLISTENABLED] = $pfxml['blacklist'];
	$sgxml[F_BLACKLISTURL]	 = $pfxml[F_BLACKLISTURL];
	$sgxml[F_SOURCES] = convert_pfxml_to_sgxml_source($config);
	$sgxml[F_ACLS] = convert_pfxml_to_sgxml_acl($config);
	$sgxml[F_DEFAULT] = convert_pfxml_to_sgxml_default($config);

	$sgxml[F_ENABLELOG]	= 'on';
	$sgxml[F_ENABLEGUILOG] = 'off';
	$sgxml[F_LOGROTATION]  = 'on';

	$lanip = $config['interfaces']['lan']['ipaddr'];
	$sgxml[F_CURRENT_LAN_IP] = $lanip;

	$squidxml = $config['installedpackages']['squid']['config'][0];
	if($squidxml['transparent_proxy'] == 'on') {
		$guiport = $config['system']['webgui']['port'];
		$guiprotocol = $config['system']['webgui']['protocol'];

		$sgxml[F_SQUID_TRANSPARENT_MODE] = 'on';
		$sgxml[F_CURRENT_GUI_PORT]  = $guiport;
		$sgxml[F_CURRENT_GUI_PROTO] = $guiprotocol;
	} else {
		unset($sgxml[F_SQUID_TRANSPARENT_MODE]);
		unset($sgxml[F_CURRENT_GUI_PORT]);
		unset($sgxml[F_CURRENT_GUI_PROTO]);
	}

	$cfg_xml = dump_xml_config($sgxml, F_SQUIDGUARD);
	file_put_contents($sgxml[F_SGCONF_XML], $cfg_xml);

	return $sgxml;
}


function convert_pfxml_to_sgxml_source($pfconfig) {
	$sgxml = array();
	$pfxml = $pfconfig['installedpackages']['squidguardacl']['config'];
	if (is_array($pfxml)) {
		foreach($pfxml as $pfx) {
			$sgx = array();
			$sgx[F_NAME] = $pfx['name'];
			$sgx['source'] = $pfx['source'];
			$sgx[F_LOG] = $pfx[F_ENABLELOG];
			$sgx['description'] = $pfx['description'];
			$sgxml[F_ITEM][] = $sgx;
		 }
	}
	return $sgxml;
}


function convert_pfxml_to_sgxml_acl($pfconfig) {
	$sgxml = array();

	$pfxml = $pfconfig['installedpackages']['squidguardacl']['config'];
	if (is_array($pfxml)) {
		foreach($pfxml as $pfx) {
			$sgx = array();
			$sgx[F_NAME]  = $pfx[F_NAME];
			$sgx['description']	= $pfx['description'];
			$sgx[F_DISABLED] = $pfx[F_DISABLED];
			$sgx[F_TIMENAME] = $pfx['time'];
			$sgx['redirect'] = $pfx['redirect'];
			$sgx[F_RMOD] = isset($pfx[F_RMOD]) ? $pfx[F_RMOD] : RMOD_NONE;
			$sgx[F_REWRITENAME]	= $pfx['rewrite'];
			$sgx[F_LOG] = $pfx[F_ENABLELOG];
			$sgx[F_NOTALLOWINGIP] = $pfx[F_NOTALLOWINGIP];
			$sgx[F_ORDER] = $pfx[F_ORDER];

			$sgx['overredirect']	= $pfx['redirect'];
			$sgx[F_OVERREWRITENAME] = $pfx['overrewrite'];

			if (strpos($pfx['dest'], '[') === false) {
				$sgx[F_DESTINATIONNAME]	 = trim($pfx['dest']);
				$sgx[F_OVERDESTINATIONNAME] = '';
			}
			else {
				$sgx[F_DESTINATIONNAME]	 = trim( substr($pfx['dest'], 0, strpos($pfx['dest'], '[')) );
				$sgx[F_OVERDESTINATIONNAME] = trim( strstr($pfx['dest'], '[') );
				$sgx[F_OVERDESTINATIONNAME] = trim( str_replace(']', '', $sgx[F_OVERDESTINATIONNAME]) );
				$sgx[F_OVERDESTINATIONNAME] = trim( str_replace('[', '', $sgx[F_OVERDESTINATIONNAME]) );
			}

			$sgx[F_DESTINATIONNAME]	 = str_replace("!all", "none", $sgx[F_DESTINATIONNAME]);
			$sgx[F_OVERDESTINATIONNAME] = str_replace("!all", "none", $sgx[F_OVERDESTINATIONNAME]);

			if (!$sgx[F_DESTINATIONNAME])	 $sgx[F_DESTINATIONNAME] = "none";
			if (!$sgx[F_OVERDESTINATIONNAME]) $sgx[F_OVERDESTINATIONNAME] = "none";

			if ($pfx['safesearch'] === 'on') {
				$sgx[F_REWRITENAME] = 'safesearch';
				$sgx[F_OVERREWRITENAME] = 'safesearch';
			}

			$sgxml[F_ITEM][] = $sgx;
		}
	}
	return $sgxml;
}

function convert_pfxml_to_sgxml_default($pfconfig) {
	$pfxml = $pfconfig['installedpackages']['squidguarddefault']['config'];

	$pfx = $pfxml[0];
	$sgx = array();
	$sgx[F_NAME]		= 'default';
	$sgx['description'] = '';
	$sgx[F_DISABLED]	= '';
	$sgx[F_TIMENAME]	= $pfx['time'];
	$sgx[F_RMOD]		= isset($pfx[F_RMOD]) ? $pfx[F_RMOD] : RMOD_INT_ERRORPAGE;
	$sgx['redirect']	= $pfx['redirect'];
	$sgx[F_REWRITENAME] = $pfx['rewrite'];
	$sgx[F_LOG]		 = $pfx[F_ENABLELOG];
	$sgx[F_NOTALLOWINGIP] = $pfx[F_NOTALLOWINGIP];

	if (strpos($pfx['dest'], '[') === false)
		 $sgx[F_DESTINATIONNAME] = trim($pfx['dest']);
	else $sgx[F_DESTINATIONNAME] = trim( substr($pfx['dest'], 0, strpos($pfx['dest'], '[')) );

	$sgx[F_DESTINATIONNAME] = str_replace("!all", "none", $sgx[F_DESTINATIONNAME]);

	if (!$sgx[F_DESTINATIONNAME]) $sgx[F_DESTINATIONNAME] = "none";

	if ($pfx['safesearch'] === 'on') {
		$sgx[F_REWRITENAME] = 'safesearch';
	}

	return $sgx;
}


function capability_update_source() {
	global $config;
	$conf_changed = false;

	if (isset($config['installedpackages']['squidguardacl']['config'])) {
		$tconf = &$config['installedpackages']['squidguardacl']['config'];
		foreach($tconf as $key => $cfg) {
			if (isset($cfg['iplist'])) {
				$tconf[$key]['source'] .= " " . $cfg['iplist'];
				unset($tconf[$key]['iplist']);
				$conf_changed = true;
			}
			if (isset($cfg[F_DOMAINS])) {
				$tconf[$key]['source'] .= " " . $cfg[F_DOMAINS];
				unset($tconf[$key][F_DOMAINS]);
				$conf_changed = true;
			}
		}

		if ($conf_changed) write_config('Convert old ver. squidguard config.');
	}
}

function get_item_id()
{
	return  isset($_GET['id']) ? $_GET['id'] : $_POST['id'];
}

?>
