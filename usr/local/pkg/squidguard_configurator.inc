<?php
require_once('squid.inc');

ini_set('max_execution_time', '3600');
ini_set('max_input_time', '3600');
ini_set('memory_limit', '100M');

define('FILES_DB_HEADER', '');
define('REDIRECTOR_OPTIONS_REM', '# squidGuard options');
define('REDIRECTOR_PROGRAM_OPT', 'redirect_program');
define('REDIRECT_BYPASS_OPT', 'redirector_bypass');
define('REDIRECT_CHILDREN_OPT', 'redirect_children');
define('REDIRECTOR_PROCESS_COUNT', '3');
define('REDIRECT_BASE_URL', '/sgerror.php');
define('REDIRECT_URL_ARGS', '&a=%a&n=%n&i=%i&s=%s&t=%t&u=%u');
define('SQUID_CONFIGFILE', '/usr/local/etc/squid/squid.conf');
define('TMP_DIR', '/var/tmp');
define('SQUIDGUARD_CONFIGFILE', '/squidGuard.conf');
define('SQUIDGUARD_CONFLOGFILE', '/sg_configurator.log');
define('SQUIDGUARD_LOGFILE', 'block.log');
define('SQUIDGUARD_CONFBASE', '/usr/local/etc/squid');
define('SQUIDGUARD_WORKDIR', '/usr/local/etc/squidGuard');
define('SQUIDGUARD_BINPATH', '/usr/local/bin');
define('SQUIDGUARD_TMP', '/tmp/squidGuard');
define('SQUIDGUARD_VAR', '/var/squidGuard');
define('SQUIDGUARD_STATE', '/squidGuard.state');
define('SQUIDGUARD_REBUILD', '/squidGuard.rebuild');
define('SQUIDGUARD_CONFXML', '/squidguard_conf.xml');
define('SQUIDGUARD_DBHOME', '/var/db/squidGuard');
define('SQUIDGUARD_DBHOME_BLK', SQUIDGUARD_DBHOME);
define('SQUIDGUARD_DBSAMPLE', '/var/db/nuclewall_blacklist');
define('SQUIDGUARD_LOGDIR', '/var/squidGuard/log');
define('SQUIDGUARD_WEBGUI_LOG', '/squidguard_gui.log');
define('SQUIDGUARD_WEBGUI_HISTORY_LOG', '/squidguard_gui_history.log');
define('SQUIDGUARD_SCR_LOGROTATE', '/usr/local/etc/rc.d/squidGuard_logrotate');
define('SQUIDGUARD_DB_BLACKLIST', '/bl');
define('SQUIDGUARD_DB_USER', '/usr');
define('SQUIDGUARD_BL_UNPACK', '/unpack');
define('SQUIDGUARD_BL_DB', '/db');
define('SQUIDGUARD_BLK_ENTRIES', '/blacklist.files');
define('SQUIDGUARD_BLK_FILELIST', '/blacklist.files');
define('SQUIDGUARD_BLK_FILELISTPATH', SQUIDGUARD_WORKDIR . SQUIDGUARD_BLK_FILELIST);
define('BLACKLIST_ARCHIVE', '/blacklists.tar');
define('SCR_NAME_BLKUPDATE', '/tmp/squidGuard_blacklist_update.sh');
define('DB_REBUILD_SH', '/tmp/squidGuard_db_rebuild.sh');
define('DB_REBUILD_CONF', '/tmp/squidGuard_db_rebuild.conf');
define('DB_REBUILD_BLK_CONF', '/squidGuard_blk_rebuild.conf');
define('BLK_TEMP', '/tmp/sg_blk');
define('SG_BLK_ARC', '/arcdb');
define('SG_INFO_FILE', '/var/squidGuard/sg_db_upd.inf');
define('SG_UPDATE_TARFILE', '/tmp/squidguard_blacklist.tar');
define('SG_UPDATE_TMPFILE', '/tmp/squidguard_download.tmp');
define('SG_UPDATE_LOGFILE', '/tmp/squidguard_download.log');
define('SG_UPDATE_STATFILE', '/tmp/squidguard_download.stat');
define('RMOD_NONE', 'rmod_none');
define('RMOD_INT_ERRORPAGE', 'rmod_int');
define('RMOD_INT_BLANKPAGE', 'rmod_int_bpg');
define('RMOD_INT_BLANKIMG', 'rmod_int_bim');
define('RMOD_INT_SIZELIMIT', 'rmod_int_szl');
define('RMOD_EXT_ERR', 'rmod_ext_err');
define('RMOD_EXT_RDR', 'rmod_ext_rdr');
define('RMOD_EXT_MOVED', 'rmod_ext_mov');
define('RMOD_EXT_FOUND', 'rmod_ext_fnd');
define('SQUIDGUARD_INFO', 2);
define('SQUIDGUARD_WARNING', 1);
define('SQUIDGUARD_ERROR', 0);
define('ACL_WARNING_ABSENSE_PASS', "!WARNING! Absence PASS 'all' or 'none' added as 'none'");
define('SQUIDGUARD_GUILOG_LEVEL', SQUIDGUARD_INFO);
define('SQUIDGUARD_GUILOG_MAXCOUNT', 500);
define('SQUIDGUARD_GUILOG_ENABLE', true);
define('SQUIDGUARD_LOG_ENABLE', true);
define('FLT_DEFAULT_ALL', 'all');
define('FLT_NOTALLOWIP', '!in-addr');
define('OWNER_NAME', 'proxy');
define('DEBUG_ON', 'true');
define('FLT_AD', 'ads');
define('FLT_AGGRESSIVE', 'aggressive');
define('FLT_AUDIOVIDEO', 'audio-video');
define('FLT_DRUGGS', 'druggs');
define('FLT_GAMBLING', 'gambling');
define('FLT_HACKING', 'hacking');
define('FLT_MAIL', 'mail');
define('FLT_PORN', 'porn');
define('FLT_PROXY', 'proxy');
define('FLT_VIOLENCE', 'viol');
define('FLT_WAREZ', 'warez');
define('F_SQUIDGUARD', 'squidGuard');
define('F_LOGDIR', 'logdir');
define('F_DBHOME', 'dbhome');
define('F_WORKDIR', 'workdir');
define('F_BINPATH', 'binpath');
define('F_PROCCESSCOUNT', 'process_count');
define('F_SQUIDCONFIGFILE', 'squid_configfile');
define('F_ENABLED', 'enabled');
define('F_SGCONF_XML', 'sgxml_file');
define('F_ITEM', 'item');
define('F_TIMES', 'times');
define('F_SOURCES', 'sources');
define('F_DESTINATIONS', 'destinations');
define('F_REWRITES', 'rewrites');
define('F_ACLS', 'acls');
define('F_DEFAULT', 'default');
define('F_NAME', 'name');
define('F_IP', 'ip');
define('F_URLS', 'urls');
define('F_DOMAINS', 'domains');
define('F_TARGETURL', 'targeturl');
define('F_REPLACETO', 'replaceto');
define('F_LOG', 'log');
define('F_DISABLED', 'disabled');
define('F_TIMENAME', 'timename');
define('F_DESTINATIONNAME', 'destname');
define('F_MODE', 'mode');
define('F_REWRITENAME', 'rewritename');
define('F_OVERDESTINATIONNAME', 'overdestname');
define('F_OVERREWRITENAME', 'overrewritename');
define('F_TIMEDAYS', 'timedays');
define('F_RMOD', 'redirect_mode');
define('F_NOTALLOWINGIP', 'notallowingip');
define('F_USERNAME', 'username');
define('F_ORDER', 'order');
define('F_ENABLELOG', 'enablelog');
define('F_ENABLEGUILOG', 'enableguilog');
define('F_LOGROTATION', 'logrotation');
define('F_ADV_BLANKIMG', 'adv_blankimg');
define('F_SQUID_TRANSPARENT_MODE', 'squid_transparent_mode');
define('F_CURRENT_LAN_IP', 'current_lan_ip');
define('F_CURRENT_GUI_PORT', 'current_gui_port');
define('F_CURRENT_GUI_PROTO', 'current_gui_protocol');
define('F_BLACKLISTENABLED', 'blacklist_enabled');
define('F_BLACKLISTURL', 'blacklist_url');

$squidguard_config = array();

sg_init();

function sg_init($init = '')
{
	global $squidguard_config;

	$squidguard_config = array();
	if(empty($init) or !is_array($init) ) {
		$squidguard_config[F_LOGDIR]		  = SQUIDGUARD_LOGDIR;
		$squidguard_config[F_DBHOME]		  = SQUIDGUARD_DBHOME;
		$squidguard_config[F_WORKDIR]		 = SQUIDGUARD_WORKDIR;
		$squidguard_config[F_BINPATH]		 = SQUIDGUARD_BINPATH;
		$squidguard_config[F_SQUIDCONFIGFILE] = SQUID_CONFIGFILE;
		$squidguard_config[F_PROCCESSCOUNT]   = REDIRECTOR_PROCESS_COUNT;
	} else {
		foreach($init as $key => $in)
			$squidguard_config[$key] = $in;
	}

	return $squidguard_config;
}

function sg_load_configxml($filename)
{
	global $squidguard_config;

	sg_init();
	if (file_exists($filename)) {
		$xmlconf = file_get_contents($filename);

		if (!empty($xmlconf)) {
			$squidguard_config = $xmlconf[F_SQUIDGUARD];
			sg_addlog("sg_load_configxml", "Success update from '$filename'.", SQUIDGUARD_INFO);
		} else
			sg_addlog("sg_load_configxml", "File '$filename' is empty.", SQUIDGUARD_ERROR);
	} else
			sg_addlog("sg_load_configxml", "File '$filename' does not exists.", SQUIDGUARD_ERROR);
}

function sg_save_configxml($filename)
{
	global $squidguard_config;
	file_put_contents($filename, dump_xml_config($squidguard_config, F_SQUIDGUARD));
}

function sg_reconfigure()
{
	global $squidguard_config;
	$conf_file = SQUIDGUARD_LOGDIR . SQUIDGUARD_CONFIGFILE;

	sg_check_system();

	$conf = sg_create_config();
	if ($conf) {
		if ($squidguard_config[F_WORKDIR])
			$conf_file = $squidguard_config[F_WORKDIR] . SQUIDGUARD_CONFIGFILE;
		file_put_contents($conf_file, $conf);
		file_put_contents('/usr/local/etc/squid' . SQUIDGUARD_CONFIGFILE, $conf);
		set_file_access($squidguard_config[F_WORKDIR], OWNER_NAME, 0755);
	}

	squid_reconfigure();
}

function squid_reconfigure($remove_only = '')
{
	global $config;
	global $squidguard_config;
	$conf = '';
	$cust_opt = $config['installedpackages']['squid']['config'][0]['custom_options'];

	if (!empty($cust_opt)) {
		$conf = explode(";", $cust_opt);
		foreach ($conf as $key => $c_opt) {
			$t_opt = ltrim($c_opt);
			if ((strpos($t_opt, REDIRECTOR_PROGRAM_OPT) === 0) or
				(strpos($t_opt, REDIRECT_BYPASS_OPT)	=== 0) or
				(strpos($t_opt, REDIRECT_CHILDREN_OPT)  === 0))
				 unset($conf[$key]);
		}
	}

	if ($config['installedpackages']['squid']['config'][0]['squidguard_enabled'] == 'on')
	{
		$redirector_path = $squidguard_config[F_BINPATH] . '/squidGuard';
		$redirector_conf = $squidguard_config[F_WORKDIR] . SQUIDGUARD_CONFIGFILE;

		$conf[] = REDIRECTOR_PROGRAM_OPT . " $redirector_path -c $redirector_conf";
		$conf[] = REDIRECT_BYPASS_OPT	. " on";
		$conf[] = REDIRECT_CHILDREN_OPT  . " " . REDIRECTOR_PROCESS_COUNT;
	}

	if (is_array($conf)) $conf = implode(";", $conf);

	$config['installedpackages']['squid']['config'][0]['custom_options'] = $conf;
	squid_resync();
}

function sg_check_system()
{
	global $squidguard_config;

	$work_dir = $squidguard_config[F_WORKDIR];
	if (!empty($work_dir)) {
		if (!file_exists($work_dir)) {
			mwexec("mkdir -p $work_dir");
			set_file_access($work_dir, OWNER_NAME, 0755);
		}
	}

	$log_dir = $squidguard_config[F_LOGDIR];
	if (!empty($log_dir)) {
		if (!file_exists($log_dir)) {
			mwexec("mkdir -p $log_dir");
		}
		set_file_access($log_dir, OWNER_NAME, 0755);
	}

	$db_dir = $squidguard_config[F_DBHOME];
	if (!empty($db_dir)) {
		if (!file_exists($db_dir)) {
			mwexec("mkdir -p $db_dir");
		}
		set_file_access($db_dir, OWNER_NAME, 0755);
	}

	if (file_exists(SQUIDGUARD_SCR_LOGROTATE)) unlink(SQUIDGUARD_SCR_LOGROTATE);
	if ($squidguard_config[F_LOGROTATION] == 'on') {
		file_put_contents(SQUIDGUARD_SCR_LOGROTATE, sg_script_logrotate());
		set_file_access  (SQUIDGUARD_SCR_LOGROTATE, OWNER_NAME, 0755);
	}
}

function squidguard_rebuild_db($tag, $rdb_dbhome, $rdb_itemslist)
{
	global $squidguard_config;

	$dbhome	= $rdb_dbhome;
	$logdir	= $squidguard_config[F_LOGDIR];
	$workdir   = $squidguard_config[F_WORKDIR];
	$conf_path = "{$workdir}/squidGuard_{$tag}rebuild.conf";

	sg_addlog("squidguard_rebuild_db", "Begin with path '$dbhome'.", SQUIDGUARD_INFO);

	$dbitems = array();
	if ($rdb_itemslist) {
		foreach ($rdb_itemslist as $it) {
			$dbitems[str_replace('/', '_', $it)] = $it; # replace path to name
		}
	}
	file_put_contents($conf_path, sg_create_simple_config($dbhome, $dbitems));
	set_file_access($conf_path, OWNER_NAME, 0750);
	sg_addlog("squidguard_rebuild_db", "Create rebuild config '$conf_path'.", SQUIDGUARD_INFO);

	mwexec_bg("/usr/bin/nice -n20 " . SQUIDGUARD_BINPATH . "/squidGuard -c $conf_path -C all");

	while (exec("ps -auxwwww | grep 'squidGuard -c .* -C all' | grep -v grep | awk '{print $2}' | wc -l | awk '{ print $1 }'") > 0) {
		sleep (10);
	}
	set_file_access($dbhome, OWNER_NAME, 0755);
	sg_addlog("squidguard_rebuild_db", "Start rebuild DB.", SQUIDGUARD_INFO);
}

function sg_addlog($module, $log, $level = 0)
{
	global $squidguard_config;

	if ( SQUIDGUARD_GUILOG_ENABLE === false || $squidguard_config[F_ENABLEGUILOG] != 'on' ) return;

	if ($level > SQUIDGUARD_GUILOG_LEVEL) return;

	if ($module) $module = "[$module]";

	$leveltext = "";
	switch($level) {
		case SQUIDGUARD_INFO:	$leveltext = "";	break;
		case SQUIDGUARD_WARNING: $leveltext = "Warning"; break;
		default:				 $leveltext = "Error";   break;
	}

	$logfile = '';
	$logfile = SQUIDGUARD_LOGDIR . SQUIDGUARD_CONFLOGFILE;
	$log_content = array();

	setlocale(LC_TIME, '');
	$dt = date("d.m.Y H:i:s");

	if (!empty($squidguard_config)) {
		if (file_exists($squidguard_config[F_LOGDIR]))
		$logfile = $squidguard_config[F_LOGDIR] . SQUIDGUARD_CONFLOGFILE;
	} else
		$log_content[] = "$dt : " . "[sg_addlog] Error: squidguard_config is empty";

	$tmplog = '';
	if (file_exists($logfile))
		$tmplog = file_get_contents($logfile);
	$log_content = explode("\n", $tmplog);
	unset($tmplog);

	$log_content[] = "$dt : $module $leveltext $log";
	if (count($log_content) > SQUIDGUARD_GUILOG_MAXCOUNT)
		array_splice($log_content, 0, SQUIDGUARD_GUILOG_MAXCOUNT - count($log_content));

	file_put_contents($logfile, implode("\n", $log_content));
}

function sg_getlog($last_entries_count)
{
	global $squidguard_config;
	$log_content = '';
	$logfile = SQUIDGUARD_LOGDIR . SQUIDGUARD_CONFLOGFILE;

	if (!empty($squidguard_config) && file_exists($squidguard_config[F_LOGDIR]))
		$logfile = $squidguard_config[F_LOGDIR] . SQUIDGUARD_CONFLOGFILE;

	if (file_exists($logfile)) {
		$log_content = explode("\n", file_get_contents($logfile));
		if (count($log_content) > $last_entries_count)
			array_splice($log_content, 0, $last_entries_count - count($log_content));

		$log_content[0] = $logfile;
		$log_content = implode("\n", $log_content);
	}

	return $log_content;
}

function sg_create_config()
{
	global $squidguard_config;
	$sgconf = array();
	$sg_tag = new TSgTag;
	$error_res = '';
	$temp_str = '';

	if(!is_array($squidguard_config) || empty($squidguard_config))
		return sg_create_simple_config('', '', "Error! Check squidGuard configuration data." . " (sg_create_config: [1]).");

	if (!sg_check_config_data(&$error_res))
		return sg_create_simple_config('', '', "Error! Check squidGuard configuration data." . " (sg_create_config: [2]).");

	$sgconf[] = "logdir {$squidguard_config[F_LOGDIR]}";
	$sgconf[] = "dbhome {$squidguard_config[F_DBHOME]}";

	if ($squidguard_config[F_SOURCES]) {
		$temp_str = '';
		foreach($squidguard_config[F_SOURCES][F_ITEM] as $src) {
			$sg_tag->clear();
			$sg_tag->set("src", $src[F_NAME], "", $src['description']);

			$tsrc = explode(" ", trim($src['source']));
			foreach($tsrc as $sr) {
					$sr = trim($sr);
					if	 (empty($sr))		   continue;
					if	 (is_ipaddr_valid($sr)) $sg_tag->items[] = "ip	 $sr";
					elseif (is_domain_valid($sr)) $sg_tag->items[] = "domain $sr";
					elseif (is_username($sr))	 $sg_tag->items[] = "user   " . str_replace("'", "", $sr);
			}

			if ($squidguard_config[F_ENABLELOG] == 'on' ) {
				if ($src[F_LOG]) $sg_tag->items[] = "log " . SQUIDGUARD_LOGFILE;
			}

			$sgconf[] = "";
			$sgconf[] = $sg_tag->tag_text();

			$temp_str .= " " . $src[F_NAME];
		}
		$temp_str = !empty($temp_str) ? $temp_str : "Nothing.";
	}

	$db_entries = sg_entries_blacklist();
	if (($squidguard_config[F_BLACKLISTENABLED] === 'on') and $db_entries) {
		$log_entr_added = '';
		$log_entr_ignored = '';
		sg_addlog("sg_create_config", "Add blacklist entries", SQUIDGUARD_INFO);
		foreach($db_entries as $key => $ent) {
			$ent_state = array();
			$file_dms  = "{$squidguard_config[F_DBHOME]}/$ent/domains";
			$file_urls = "{$squidguard_config[F_DBHOME]}/$ent/urls";
			$file_expr = "{$squidguard_config[F_DBHOME]}/$ent/expressions";

			if (file_exists($file_dms)) {
				$ent_state['exists'] = 'on';
				$ent_state[F_DOMAINS] = 'on';
			}
			if (file_exists($file_urls)) {
				$ent_state['exists'] = 'on';
				$ent_state[F_URLS] = 'on';
			}
			if (file_exists($file_expr)) {
				$ent_state['exists'] = 'on';
				$ent_state['expressions'] = 'on';
			}

			if ($ent_state['exists']) {
				$sg_tag->clear();
				$sg_tag->set("dest", $ent, "", "");

				if ($ent_state[F_DOMAINS])	 $sg_tag->items[] = "domainlist $ent/domains";
				if ($ent_state['expressions']) $sg_tag->items[] = "expressionlist $ent/expressions";
				if ($ent_state[F_URLS])		$sg_tag->items[] = "urllist $ent/urls";

		  $adv_pos = strpos($ent,'_adv');
		  $ads_pos = strpos($ent, '_ads');
		  if ( ($ads_pos > 0 || $adv_pos > 0) && $squidguard_config[F_ADV_BLANKIMG] == 'on')
				 $sg_tag->items[] = "redirect " . sg_redirector_base_url($dst['redirect'], RMOD_INT_BLANKIMG);

				if ($squidguard_config[F_ENABLELOG] == 'on' ) {
					$sg_tag->items[] = "log ". SQUIDGUARD_LOGFILE;
				}

				$sgconf[] = "";
				$sgconf[] = $sg_tag->tag_text();

				$log_entr_added .= " $ent;";
			} else {
				$sgconf[] = "\t# Config ERROR: Destination '$ent' not found in DB";
				$log_entr_ignored .= " $ent;";
			}
		}
	}

	$sg_tag->clear();
	$sg_tag->set("acl", "", "", "");
	if ($squidguard_config[F_ACLS]) {
		$temp_str = '';
		$log_entr_added = '';
		foreach($squidguard_config[F_ACLS][F_ITEM] as $acl) {
			if (!$acl[F_DISABLED]) {
				$sg_acltag = new TSgTag;
				$sg_acltag->set($acl[F_NAME], "", $acl[F_TIMENAME], $acl['description']);

				if ($squidguard_config[F_BLACKLISTENABLED] !== 'on') {
					acl_remove_blacklist_items(&$acl[F_DESTINATIONNAME]);
					acl_remove_blacklist_items(&$acl[F_OVERDESTINATIONNAME]);
				}

				if ($acl[F_NOTALLOWINGIP]) {
					$acl[F_DESTINATIONNAME]	 = "!in-addr {$acl[F_DESTINATIONNAME]}";
					$acl[F_OVERDESTINATIONNAME] = "!in-addr {$acl[F_OVERDESTINATIONNAME]}";
				}

				$acl[F_DESTINATIONNAME]	 = sg_aclpass_reorder($acl[F_DESTINATIONNAME]);
				$acl[F_OVERDESTINATIONNAME] = sg_aclpass_reorder($acl[F_OVERDESTINATIONNAME]);

				$sg_acltag->items[] = "pass {$acl[F_DESTINATIONNAME]}";
				if ($acl[F_RMOD] != RMOD_NONE)
				$sg_acltag->items[] = "redirect " . sg_redirector_base_url($acl['redirect'], $acl[F_RMOD]);
				if ($acl[F_REWRITENAME])
					$sg_acltag->items[] = "rewrite {$acl[F_REWRITENAME]}";
				if ($squidguard_config[F_ENABLELOG] == 'on' ) {
					if ($acl[F_LOG])
						$sg_acltag->items[] = "log " . SQUIDGUARD_LOGFILE;
				}

				if ($acl[F_TIMENAME]) {
					$sg_acltag->items[] = "} else {";
					$sg_acltag->items[] = "pass {$acl[F_OVERDESTINATIONNAME]}";
					if ($acl[F_REDIRECMODE] !== RMOD_NONE)
						$sg_acltag->items[] = "redirect " . sg_redirector_base_url($acl['overredirect'], $acl[F_RMOD]);
					if ($acl[F_OVERREWRITENAME])
						$sg_acltag->items[] = "rewrite {$acl[F_OVERREWRITENAME]}";
					if ($squidguard_config[F_ENABLELOG] == 'on' ) {
						if ($acl[F_LOG])
							$sg_acltag->items[] = "log " . SQUIDGUARD_LOGFILE;
					}
				}
				$sg_tag->items[] = $sg_acltag;
			}
			$log_entr_added .= " {$acl[F_NAME]};";
		}

		$log_entr_added = !empty($log_entr_added) ? $log_entr_added : "Nothing.";
		sg_addlog("sg_create_config", "Add ACL's: $log_entr_added", SQUIDGUARD_INFO);
	}

	$sg_tag_def = new TSgTag;
	$sg_tag_def->set("default", "", "", "");
	$def = $squidguard_config[F_DEFAULT];
	sg_addlog("sg_create_config", "Add Default", SQUIDGUARD_INFO);
	if ($def) {
		$temp_str = '';

		if ($squidguard_config[F_BLACKLISTENABLED] !== 'on')
			acl_remove_blacklist_items(&$def[F_DESTINATIONNAME]);

		if ($def[F_NOTALLOWINGIP])
			$def[F_DESTINATIONNAME] = "!in-addr " . $def[F_DESTINATIONNAME];

		$def[F_DESTINATIONNAME] = sg_aclpass_reorder($def[F_DESTINATIONNAME]);

		$sg_tag_def->items[] = "pass {$def[F_DESTINATIONNAME]}";
		if ($def[F_RMOD] !== RMOD_NONE)
			$sg_tag_def->items[] = "redirect " .  sg_redirector_base_url($def['redirect'], $def[F_RMOD]);
		if ($def[F_REWRITENAME])
			$sg_tag_def->items[] = "rewrite {$def[F_REWRITENAME]}";
		if ($squidguard_config[F_ENABLELOG] == 'on' ) {
			if ($def[F_LOG])
				$sg_tag_def->items[] = "log " . SQUIDGUARD_LOGFILE;
		}
	}
	else {
		$msg =  "ACL 'default' is empty, will use default 'block all'";
		$sg_tag_def->items[] = "# $msg";
		$sg_tag_def->items[] = "pass none";
		$sg_tag_def->items[] = "redirect " . sg_redirector_base_url('', RMOD_INT_ERRORPAGE);
		sg_addlog("sg_create_config", "$msg.", SQUIDGUARD_ERROR);
	}

	$sg_tag->items[] = $sg_tag_def; # add 'default' ACL object
	$sgconf[] = "";
	$sgconf[] = $sg_tag->tag_text();
	foreach ($sgconf as $key => $val) $sgconf[$key] = ltrim($sgconf[$key], "\n");
	return implode("\n", $sgconf);
}

function sg_create_simple_config($blk_dbhome, $blk_destlist, $redirect_to = "404")
{
	global $squidguard_config;
	$sgconf = array();
	$logdir = $squidguard_config[F_LOGDIR];
	$dbhome = $blk_dbhome ? $blk_dbhome : $squidguard_config[F_DBHOME];

	sg_addlog("sg_create_simple_config", "Begin with dbhome='$dbhome'.", SQUIDGUARD_INFO);

	$sgconf[] = "logdir $logdir";
	$sgconf[] = "dbhome $dbhome";
	$sgconf[] = "";

	if (is_array($blk_destlist)) {
		foreach($blk_destlist as $dst => $dpath) {
			$tmp_s = array();

			if (file_exists("$dbhome/$dpath/domains"))	 $tmp_s[] = "\t domainlist $dpath/domains";
			if (file_exists("$dbhome/$dpath/urls"))		$tmp_s[] = "\t urllist $dpath/urls";
			if (file_exists("$dbhome/$dpath/expressions")) $tmp_s[] = "\t expressionlist $dpath/expressions";

			if (!empty($tmp_s)) {
				$tmp_s = implode("\n", $tmp_s);
				$sgconf[] = "dest $dst {\n $tmp_s \n}\n";
				sg_addlog("sg_create_simple_config", "Added item '$dst' = '$dbhome/$dpath'.", SQUIDGUARD_INFO);
			} else
				sg_addlog("sg_create_simple_config", "Ignored empty item '$dst' = '$dbhome/$dpath'.", SQUIDGUARD_WARNING);
		}
	}

	$sgconf[] = "acl {\n\t default {\n\t\t pass all ";
	$sgconf[] = "\t\t redirect " . sg_redirector_base_url($redirect_to, RMOD_INT_ERRORPAGE); # use sgerror only!
	$sgconf[] = "\t } \n}";

	foreach ($sgconf as $key => $val) $sgconf[$key] = ltrim($sgconf[$key], "\n");

	return implode("\n", $sgconf);
}

function sg_redirector_base_url($rdr_info, $redirect_mode)
{
	global $squidguard_config;
	$rdr_path = '';

	$guiip = (!empty($squidguard_config[F_CURRENT_LAN_IP])) ? $squidguard_config[F_CURRENT_LAN_IP] : '127.0.0.1';
	$guiport = (!empty($squidguard_config[F_CURRENT_GUI_PORT])) ? $squidguard_config[F_CURRENT_GUI_PORT] : '80';
	$rdr_path = "http://$guiip:$guiport" . REDIRECT_BASE_URL;

	$errmsg = '';
	if (!sg_check_redirect($redirect_mode, $rdr_info, &$errmsg)) {
		$redirect_mode = RMOD_INT_ERRORPAGE;
		$rdr_info = "Bad redirect settings. $errmsg Check you configuration.";
		sg_addlog("sg_redirector_base_url", "$errmsg", SQUIDGUARD_ERROR);
	}

	switch($redirect_mode)
	{
		case RMOD_EXT_ERR:		 $rdr_path = "$rdr_info" . REDIRECT_URL_ARGS; break;
		case RMOD_EXT_RDR:		 $rdr_path = "$rdr_info"; break;
		case RMOD_EXT_MOVED:	   $rdr_path = "301:$rdr_info"; break;
		case RMOD_EXT_FOUND:	   $rdr_path = "302:$rdr_info"; break;
		case RMOD_INT_BLANKPAGE:   $rdr_path .= "?url=blank&msg=" . rawurlencode($rdr_info) . REDIRECT_URL_ARGS; break;
		case RMOD_INT_BLANKIMG:	$rdr_path .= "?url=blank_img&msg=" . rawurlencode($rdr_info) . REDIRECT_URL_ARGS; break;
		case RMOD_INT_SIZELIMIT:   $rdr_path .= "?url=maxlen_$rdr_info" . REDIRECT_URL_ARGS; break;
		case RMOD_INT_ERRORPAGE:
		default:				   $rdr_path .= "?url=" . rawurlencode("403 $rdr_info") . REDIRECT_URL_ARGS; break;
	}

	sg_addlog("sg_redirector_base_url", "Select redirector base url ($rdr_path)", SQUIDGUARD_INFO);
	return $rdr_path;
}

function sg_aclpass_reorder($pass)
{
	$ar_pass = explode(" ", $pass);

	if (is_array($ar_pass)) {
		$pass_end = '';
		$pass_fst = array();
		$pass_sec = array();
		$pass_lst = array();
		foreach ($ar_pass as $val) {
			$tk = trim($val);
			if ($tk === 'all' or $tk === 'none')
				$pass_end   = $val;
			elseif (strpos($tk, "^") !== false)
				$pass_fst[] = str_replace('^', '', $val);
			elseif (strpos($tk, "!") !== false)
				$pass_sec[] = $val;
			else
					$pass_lst[] = $val;
		}
		$ar_pass = array_merge($pass_fst, $pass_sec, $pass_lst);
		$ar_pass[] = $pass_end;
	}
	return implode(" ", $ar_pass);
}

function sg_check_config_data ($input_errors)
{
	global $squidguard_config;
	$elog = array();
	$sources = sg_list_itemsfield($squidguard_config[F_SOURCES], F_NAME);
	$acls = array();

	# --- Sources ---
	if ($squidguard_config[F_SOURCES]) {
		$key_src = array_count_values($sources);
		foreach($squidguard_config[F_SOURCES][F_ITEM] as $src) {
			$src_name = $src[F_NAME];
			$err_s = '';
			if (!check_name_format($src_name, &$err_s))
				$elog[] = "(A1) ACL '$src_name'error: $err_s";

			sg_check_src($src, $elog);
		}
	}

	if ($squidguard_config[F_BLACKLISTENABLED]) {
		$blk_entries_file = SQUIDGUARD_BLK_FILELISTPATH;
		if (file_exists($blk_entries_file)) {
			$blk_entr = explode("\n", file_get_contents($blk_entries_file));
			foreach($blk_entr as $entr) {
				if ($entr) {
					$destinations[] = $entr;
					$dbfile = $squidguard_config[F_DBHOME] . "/$entr";
					if (!file_exists($dbfile))
						$elog[] = "(B1) BLACKLIST '$entr' error: file '$dbfile' not found";
				}
			}
		}
	}

	$key_sources = array_count_values($sources);

	if ($squidguard_config[F_ACLS]) {
		$acls = array();
		foreach($squidguard_config[F_ACLS][F_ITEM] as $acl) {
			if ($acls[F_DISABLED]) continue;

			$acl_name = $acl[F_NAME];

			if ($acl_name and !$key_sources[$acl_name])
				$elog[] = "(A1) ACL '$acl_name' error: acl name '$acl_name' not found";

			$acls[] = $acl_name;
			$key_acls = array_count_values($acls);
			if ($key_acls[$acl_name] > 1)
				$elog[] = "(A2) ACL '$acl_name' error: duplicate acl name '$acl_name'";
		}
	}
	return empty($elog);
}

function set_file_access($dir, $owner, $mod)
{
	$mod = sprintf("%o", $mod);
	if (!file_exists($dir)) return;
	mwexec("chown -R -v $owner $dir");
	mwexec("chgrp -R -v $owner $dir");
	mwexec("chmod -R -v $mod $dir");
}

function scan_dir($dir)
{
	$files = array();
	if (file_exists($dir)) {
		$dh = opendir($dir);
		while (false !== ($filename = readdir($dh))) {
			# skip '.' and '..' names
			if (($filename !== '.') and ($filename !== '..')) $files[] = $filename;
		}
		sort($files);
	}
	return $files;
}

function sg_list_itemsfield($xml_items, $fld_name)
{
	$ls = array();
	if (is_array($xml_items[F_ITEM]))
		foreach($xml_items[F_ITEM] as $it) {
			$ls[] = $it[$fld_name];
		}
	return $ls;
}

if(!function_exists("is_url"))
{
	function is_url($url)
	{
		if (empty($url))				return false;
		if (preg_match("/^(http|https):\/\//i", $url))	return true;
		if (strstr("blank", $url))	  return true;
		if (strstr("blank_img", $url))  return true;
		if (preg_match("/^((30[1235]{1})|(40[0-9]{1})|(41[0-7]{1})|(50[0-5]{1}))/i", $url)) return true;
		return false;
	}
}

function is_dest_url($url)
{
	$fmt  = "[a-zA-Z0-9_-]";

	if (empty($url)) return false;
	if (preg_match("/^(($fmt){1,}\.){1,}($fmt){2,}(\/(.[^\*][^ ])*)/i", $url)) return true;
	return false;
}

function is_masksubnet($subnet)
{
	if (!is_string($subnet))
		return false;

	list($ip,$msk) = explode('/', $subnet);
	if (!is_ipaddr($ip) || !is_ipaddr($msk))
		return false;

	return true;
}

function is_iprange_sg($ip_range) {
	if (!is_string($ip_range))  return false;

	list($ip1,$ip2) = explode('-', $ip_range);
	if (!is_ipaddr($ip1) || !is_ipaddr($ip2)) return false;

	if (ipcmp(ip2, ip1) === -1) return false;

	return true;
}

function is_ipaddr_valid($val)
{
	return is_string($val) && (is_ipaddr($val) || is_masksubnet($val) || is_subnet($val) || is_iprange_sg($val));
}

function is_domain_valid($domain)
{
	$dm_fmt = "([a-z0-9\-]{1,})";
	$dm_fmt = "/^(($dm_fmt{1,}\.){1,}$dm_fmt{2,})+$/i";
	return is_string($domain) && preg_match($dm_fmt, trim($domain));
}

function is_username($username)
{
	$unm_fmt = "/^\'[a-zA-Z_0-9\.\-]{1,}\'$/i";
	return is_string($username) && preg_match($unm_fmt, trim($username));
}

function check_name_format ($name, $input_errors)
{
	$elog = array();
	$val = trim($name);

	if ((strlen($val) < 2) || (strlen($val) > 16))
		$elog[] = "'$val' boyutu 2 ile 16 arasında arasında olmalıdır.";

	if (!preg_match("/^([a-zA-Z]{1})([a-zA-Z_0-9\-]+)$/i", $val))
		$elog[] = "'$name' geçersiz bir isim. Geçerli karakterler: ['a-Z', '_', '0-9', '-']. İlk karakter harf olmalıdır.";

	if (!empty($elog)) {
			$input_errors = (is_array($input_errors)) ? array_merge($input_errors, $elog) : implode("\n", $elog);
	}

	return empty($elog);
}

function sg_check_redirect($rdr_mode, $rdr_info, $err_msg)
{
	$res = true;
	switch($rdr_mode) {
		case RMOD_EXT_ERR: case RMOD_EXT_RDR: case RMOD_EXT_MOVED: case RMOD_EXT_FOUND:
			$res = is_url($rdr_info);
			if (!$res) $err_msg = "Valid URL expected, but '$rdr_info' found.";
			break;
		case RMOD_INT_SIZELIMIT:
			$res = is_numeric($rdr_path);
			if (!$res) $err_msg = "Valid number value expected, but '$rdr_info' found.";
			break;
		case RMOD_INT_BLANKPAGE: case RMOD_INT_BLANKIMG: case RMOD_INT_ERRORPAGE:
		default:
			 $res = true; break;
	}
	return $res;
}

function sg_check_src($sgx, $input_errors)
{
	$elog = array();
	$src = (isset($sgx['source'])) ? $sgx['source'] : $sgx[F_IP] . " " . $sgx[F_DOMAINS];
	$src = explode(' ', $src);
	foreach ($src as $s_item) {
		if ($s_item) {
			if (!is_ipaddr_valid($s_item) and !is_domain_valid($s_item))
				$elog[] = "'$s_item' geçerli bir ip adresi veya alan adı değil'.";
		}
	}

	if (!empty($elog)) {
			$input_errors = (is_array($input_errors)) ? array_merge($input_errors, $elog) : implode("\n", $elog);
	}

	return empty($elog);
}

function is_blacklist_update_started()
{
	return exec("ps auxw | grep \"[s]quidGuard_blk_rebuild\" | awk '{print $2}' | wc -l | awk '{ print $1 }'");
}

function str_packspaces($str)
{
	while(strpos($str, '  ')) $str = str_replace('  ', ' ', $str);
}

function array_packitems($arval)
{
	if (is_array($arval)) {
		$arval = array_map("trim", $arval);
		$arval = array_diff($arval, array(' ', ''));
		$arval = array_unique($arval);
		$arval = array_values($arval);
	}
	return $arval;
}

function acl_remove_blacklist_items($items)
{
	$db_entries = sg_entries_blacklist();
	if (!is_array($db_entries))
		return;
	$tdb_entries = array();
	foreach ($db_entries as $ent) {
		$tdb_entries[] = $ent;
		$tdb_entries[] = "!$ent";
		$tdb_entries[] = "^$ent";
	}
	$db_entries = $tdb_entries;
	unset($tdb_entries);

	$items = explode(" ", $items);
	$items = implode(" ", array_diff($items, $db_entries));
	return $items;
}

function sg_script_logrotate()
{
	$lines = 1000; # SG logfile truncate lines count

	global $squidguard_config;
	$sglogname = $squidguard_config[F_LOGDIR] . "/" . SQUIDGUARD_LOGFILE;
	$res =
<<<EOD
#!/bin/sh
#
tail -{$lines} {$sglogname} > {$sglogname}.0
tail -{$lines} {$sglogname}.0 > {$sglogname}
rm -f {$sglogname}.0
EOD;
	return $res;
}

function squidguard_setup_cron($task_key, $options, $on_off)
{
	global $config;
	$cron_item = array();

	if (is_array($options)) {
		$cron_item['minute'] = $options[0];
		$cron_item['hour'] =  $options[1];
		$cron_item['mday'] = $options[2];
		$cron_item['month'] = $options[3];
		$cron_item['wday'] = $options[4];
		$cron_item['who'] = ($options[5]) ? $options[5] : 'nobody';
		$cron_item['command'] = $options[6];
	}

	if (!empty($task_key)) {
		$flag_cron_upd = false;
		if (is_array($config['cron']['item'])) {
			foreach($config['cron']['item'] as $key => $val) {
				if (strpos($config['cron']['item'][$key]['command'], $task_key) !== false) {
					unset($config['cron']['item'][$key]);
					$flag_cron_upd = true;
					break;
				}
			}
		}

		if (($on_off === true) and !empty($cron_item)) {
			$config['cron']['item'][] = $cron_item;
			$flag_cron_upd = true;
		}

		if ($flag_cron_upd === true) {
			write_config("Installed cron task '$task_key' for 'squidGuard' package");
			configure_cron();
		}
	}
	else {
		return;
	}
}

function squidguard_ramdisk($enable)
{
	$ramsize = 300;

	if (file_exists("/dev/md15")) {
		mwexec("umount -f " . SQUIDGUARD_TMP);
		mwexec("sleep 1");
		mwexec("mdconfig -d -u 15");
	}

	if ($enable === true) {
		mwexec("/sbin/mdmfs -s {$ramsize}M md15 " . SQUIDGUARD_TMP);
		mwexec("chmod 1777 " . SQUIDGUARD_TMP);
	}
}

function squidguard_update_log($msg, $new="")
{
	$to = $new ? ">" : ">>";
	mwexec("echo $msg $to " . SG_UPDATE_STATFILE);
}

function squidguard_blacklist_update_start($url_filename)
{
	if (squidguard_blacklist_update_IsStarted()) squidguard_blacklist_update_cancel();

	if (file_exists(SCR_NAME_BLKUPDATE)) unlink(SCR_NAME_BLKUPDATE);

	file_put_contents(SCR_NAME_BLKUPDATE, squidguard_script_blacklistupdate($url_filename, ""));
	set_file_access  (SCR_NAME_BLKUPDATE, OWNER_NAME, 0755);

	mwexec_bg(SCR_NAME_BLKUPDATE);
}

function squidguard_blacklist_update_cancel()
{
	mwexec("kill `ps auxwwww | grep '" . SCR_NAME_BLKUPDATE . "' | grep -v 'grep' | awk '{print $2}'`");
	mwexec("kill `ps auxwwww | grep 'squidGuard -c .* -C all' | grep -v 'grep' | awk '{print $2}'`");
	squidguard_ramdisk(false);
}

function squidguard_blacklist_update_clearlog()
{
	file_put_contents(SG_UPDATE_STATFILE, "");
}

function squidguard_blacklist_update_IsStarted()
{
	return exec("ps auxwwww | grep '" . SCR_NAME_BLKUPDATE . "' | grep -v 'grep' | awk '{print $2}' | wc -l | awk '{ print $1 }'");
}

function sg_reconfigure_blacklist($source_filename)
{
	global $squidguard_config;
	$sf = trim($source_filename);
	$sf_contents = '';

	sg_check_system();

	if ($sf[0] === "/") {
		sg_addlog("sg_reconfigure_blacklist", "Update from file '$sf'.", SQUIDGUARD_INFO);
		squidguard_update_log("Copy archive from file '$sf'");
		if (file_exists($sf)) {
			$sf_contents = file_get_contents($sf);
		} else {
			sg_addlog("sg_reconfigure_blacklist", "File '$sf' not found.", SQUIDGUARD_ERROR);
			squidguard_update_log("File '$sf' not found.");
			return;
		}
	}

	else {
		$sf_contents = sg_uploadfile_from_url($sf);
	}

	if (empty($sf_contents)) {
		sg_addlog("sg_reconfigure_blacklist", "Bad content from '$sf'. Terminate.", SQUIDGUARD_ERROR);
		squidguard_update_log("Bad content from '$sf'. Terminate.");
		return;
	}

	file_put_contents(SG_UPDATE_TARFILE, $sf_contents);

	sg_update_blacklist(SG_UPDATE_TARFILE);
}

function sg_update_blacklist($from_file)
{
	global $squidguard_config;
	$dbhome  = SQUIDGUARD_DBHOME;
	$workdir = SQUIDGUARD_WORKDIR;
	$tmp_unpack_dir = SQUIDGUARD_TMP . SQUIDGUARD_BL_UNPACK;
	$arc_db_dir	 = SQUIDGUARD_TMP . SG_BLK_ARC;
	$conf_path	  = SQUIDGUARD_VAR . DB_REBUILD_BLK_CONF;
	$blklist_file   = SQUIDGUARD_BLK_FILELISTPATH;

	sg_addlog("sg_update_blacklist", "Begin with '$from_file'.", SQUIDGUARD_INFO);

	if (file_exists($from_file)) {
		if (file_exists($squidguard_config[F_DBHOME]))  $dbhome  = $squidguard_config[F_DBHOME];
		if (file_exists($squidguard_config[F_WORKDIR])) $workdir = $squidguard_config[F_WORKDIR];

		if (file_exists($tmp_unpack_dir)) mwexec("rm -R $tmp_unpack_dir");
		if (file_exists($arc_db_dir))	 mwexec("rm -R $arc_db_dir");
		squidguard_ramdisk(false);

		squidguard_ramdisk(true);
		mwexec("mkdir -p -m 0755 $tmp_unpack_dir");
		mwexec("mkdir -p -m 0755 $arc_db_dir");

		squidguard_update_log("Unpack archive");
		mwexec("tar zxvf $from_file -C $tmp_unpack_dir");
		set_file_access($tmp_unpack_dir, OWNER_NAME, 0755);
		sg_addlog("sg_update_blacklist", "Unpack uploaded file '$from_file' -> '$tmp_unpack_dir'.", SQUIDGUARD_INFO);

		squidguard_update_log("Scan blacklist categories.");
		if (file_exists($tmp_unpack_dir)) {
			$blk_items = array();
			$blk_list  = array();
			scan_blacklist_cat($tmp_unpack_dir, "blk", & $blk_items);

			foreach ($blk_items as $key => $val) {
				$current_dbpath = "$arc_db_dir/$key";
				if (count($val)) {
					$blk_list[$key] = $key;

					if (file_exists($current_dbpath))
						mwexec("rm -R $current_dbpath");
					mwexec("mv -f {$val['path']}/ $current_dbpath");
					sg_addlog("sg_update_blacklist", "Move {$val['path']}/ -> $current_dbpath.", SQUIDGUARD_INFO);
				}
			}
			set_file_access($arc_db_dir, OWNER_NAME, 0755);

			if (count($blk_items)) {
				# save to temp DB
				$cont = implode("\n", array_keys($blk_items));

				# temp blacklist files
				$blklist_file = $arc_db_dir . SQUIDGUARD_BLK_FILELIST;
				file_put_contents($blklist_file, $cont);
				set_file_access  ($blklist_file, OWNER_NAME, 0755);

				# system blacklist files
				$blklist_file = SQUIDGUARD_BLK_FILELISTPATH;
				file_put_contents($blklist_file, $cont);
				set_file_access  ($blklist_file, OWNER_NAME, 0755);

				sg_addlog("sg_update_blacklist", "Create DB entries list '$blklist_file'.", SQUIDGUARD_INFO);
				squidguard_update_log("Found " . count($blk_items) . " items.");
			}

			squidguard_update_log("Start rebuild DB.");
			squidguard_rebuild_db("blk_", $arc_db_dir, $blk_list);

			squidguard_update_log("Copy DB to workdir.");
			$blklist_file = $arc_db_dir . SQUIDGUARD_BLK_FILELIST;
			mwexec("cp -R -p $arc_db_dir/ $dbhome");
			mwexec("cp -f -p $blklist_file " . SQUIDGUARD_WORKDIR);
			set_file_access($dbhome, OWNER_NAME, 0755);

			squidguard_update_log("Reconfigure Squid proxy.");
			mwexec("/usr/local/sbin/squid -k reconfigure");

			squidguard_update_log("Blacklist update complete.");

		}
		squidguard_ramdisk(false);
	}
	else sg_addlog("sg_update_blacklist", "File $from_file not found.", SQUIDGUARD_ERROR);
}

function sg_entries_blacklist()
{
	$contents = '';

	$fl = SQUIDGUARD_BLK_FILELISTPATH;
	if (file_exists($fl))
		$contents = explode("\n", file_get_contents($fl));

	return $contents;
}

function sg_uploadfile_from_url($url_file)
{
	$err = 0;
	$download_tmpfile = SG_UPDATE_TMPFILE; #"/tmp/squidguard_download.tmp";
	$download_logfile = SG_UPDATE_LOGFILE; #"/tmp/squidguard_download.log";

	# open destination file
	$s = "Download archive '$url_file'" . ( $proxy ? " via proxy'$proxy'" : "" );
	sg_addlog("sg_uploadfile_from_url", $s, SQUIDGUARD_INFO);
	squidguard_update_log( $s );

	# open temp and log files for curl
	$ftmp = fopen($download_tmpfile, "w"); # download result file
	$flog = fopen($download_logfile, "w"); # download log file

	$result = '';
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url_file);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_NOPROGRESS, 0);
	curl_setopt($ch, CURLOPT_FILE,   $ftmp);
	curl_setopt($ch, CURLOPT_STDERR, $flog);

	curl_exec ($ch);
	$err = curl_errno($ch);
	if ($err)
		 squidguard_update_log( "Download error: " . curl_error($ch) );
	else squidguard_update_log( "Download complete" );
	curl_close ($ch);

	fclose($ftmp);
	fclose($flog);

	if (!$err &&  file_exists( $download_tmpfile ))
		$result = file_get_contents( $download_tmpfile );
	return $result;
}

function squidguard_blacklist_restore_arcdb()
{
	global $squidguard_config;
	$dbhome		   = $squidguard_config[F_DBHOME] ? $squidguard_config[F_DBHOME] : SQUIDGUARD_DBHOME;
	$blklist_file	 = SQUIDGUARD_BLK_FILELISTPATH;
	$arc_db_dir	   = SQUIDGUARD_DBSAMPLE;

	squidguard_update_log("Restore default blacklist DB.", "new");

	if (file_exists($arc_db_dir))
	{
		mwexec("cp -R -p $arc_db_dir/ $dbhome");
		set_file_access($dbhome, OWNER_NAME, 0755);
		sg_addlog("squidguard_blacklist_restore_arcdb", "Restore blacklist archive from '$arc_db_dir'.", SQUIDGUARD_INFO);

		$blklist = "";
		$files   = scan_dir("$arc_db_dir/");
		if ($files) $blklist = implode("\n", $files);
		file_put_contents($blklist_file, $blklist);
		set_file_access($blklist_file, OWNER_NAME, 0755);

		squidguard_rebuild_db("arc_", $dbhome, $files);

		if (is_process_running('squid'))
		  mwexec("/usr/local/sbin/squid -k reconfigure");
	}
}

function scan_blacklist_cat($curdir, $key_name, $cat_array)
{
	if (file_exists($curdir) and is_dir($curdir)) {
		$blk_entry = array();
		$files = scan_dir($curdir);

		foreach($files as $fls) {
			$fls_file = "$curdir/$fls";

			if (($fls != ".") and ($fls != "..")) {
				if (is_file($fls_file)) {

					 # add files path
					 switch(strtolower($fls)) {
						 case "domains":
							 $blk_entry["domains"] = $fls_file;
							 $blk_entry["path"]	= $curdir;
							 break;
						 case "urls":
							 $blk_entry["urls"] = $fls_file;
							 $blk_entry["path"]	= $curdir;
							 break;
						 case "expressions":
							 $blk_entry["expressions"] = $fls_file;
							 $blk_entry["path"]	= $curdir;
							 break;
					 }
				 }
				 elseif (is_dir($fls_file)) {
					 $fls_key = $key_name . "_" . $fls;

					 # recursive call
					 scan_blacklist_cat($fls_file, $fls_key, & $cat_array);
				 }
			}
		}

		if (count($blk_entry))
			$cat_array[$key_name] = $blk_entry;
	}
}

function squidguard_script_blacklistupdate($fname, $opt)
{
	$sh[] = "#!/usr/local/bin/php -f";
	$sh[] = "<?php";
	$sh[] = "	\$incl = \"/usr/local/pkg/squidguard_configurator.inc\";";
	$sh[] = "	if (file_exists(\$incl)) {";
	$sh[] = "		require_once(\$incl);";
	$sh[] = "		sg_reconfigure_blacklist( \"{$fname}\", \"{$opt}\" );";
	$sh[] = "	}";
	$sh[] = "	exit;";
	$sh[] = "?>";
	return implode ("\n", $sh);
}

class TSgTag
{
	var $tag;
	var $name;
	var $time;
	var $items;
	var $desc;

	function __construct() {
		$this->clear();
	}

	function clear() {
		$this->tag	= '';
		$this->name   = '';
		$this->time   = '';
		$this->items  = array();
		$this->desc   = '';
	}

	function set($tag, $name, $time, $desc) {
		$this->tag	= $tag;
		$this->name   = $name;
		$this->time   = $time;
		$this->desc   = $desc;
	}

	function tag_text($offset = 0) {
		$str = array();
		$off = str_repeat("\t", $offset);

		$str[] = $off . "# {$this->desc}";
		if (empty($this->time))
			 $str[] = $off . "{$this->tag} {$this->name} {";
		else $str[] = $off . "{$this->tag} {$this->name} within {$this->time} {";

		foreach($this->items as $it)
		{
			if (is_a($it, "TSgTag"))
				$str[] = $off . $it->tag_text($offset + 1);
			else $str[] = $off . "\t{$it}";
		}

		$str[] = $off . "}";
		return implode("\n", $str);
	}
}

function squid_init()
{
	global $config;

	$conf = $config['installedpackages']['squid']['config'][0];

	if ($conf['squid_enabled'] == 'on' and $conf['squidguard_enabled'] == 'on')
		squidguard_resync();

	else if ($conf['squid_enabled'] == 'on' and !$conf['squidguard_enabled'] == 'on')
		squid_resync();

	else
	{
		if(is_process_running('squid'))
		{
			mwexec(SQUID_LOCALBASE . "/sbin/squid -k kill");
		}
	}
}

?>
