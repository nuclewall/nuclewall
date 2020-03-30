<?php

function encrypted_configxml()
{
	global $g, $config;

	if (!file_exists($g['conf_path'] . "/config.xml"))
		return;

	if (!$g['booting'])
		return;

	$configtxt = file_get_contents($g['conf_path'] . "/config.xml");

	if(tagfile_deformat($configtxt, $configtxt, "config.xml"))
	{
		$fp = fopen('php://stdin', 'r');
		$data = "";
		echo "\n\n*** Encrypted config.xml detected ***\n";
		while($data == "") {
			echo "\nEnter the password to decrypt config.xml: ";
			$decrypt_password = chop(fgets($fp));
			$data = decrypt_data($configtxt, $decrypt_password);
			if(!strstr($data, "<pfsense>"))
				$data = "";
			if($data) {
				$fd = fopen($g['conf_path'] . "/config.xml.tmp", "w");
				fwrite($fd, $data);
				fclose($fd);
				exec("/bin/mv {$g['conf_path']}/config.xml.tmp {$g['conf_path']}/config.xml");
				echo "\nConfig.xml unlocked.\n";
				fclose($fp);
			} else {
				echo "\nInvalid password entered.  Please try again.\n";
			}
		}
	}
}

function parse_config($parse = false)
{
	global $g, $config_parsed, $config_extra;

	$lockkey = lock('config');
	$config_parsed = false;

	if (!file_exists("{$g['conf_path']}/config.xml") || filesize("{$g['conf_path']}/config.xml") == 0) {
		$last_backup = discover_last_backup();
		if($last_backup)
		{
			log_error("No config.xml found, attempting last known config restore.");
			restore_backup("{$g['conf_path']}/backup/{$last_backup}");
		}
		else
		{
			unlock($lockkey);
			die("Config.xml is corrupted and is 0 bytes.  Could not restore a previous backup.");
		}
	}

	if($g['booting'])
		echo ".";

	encrypted_configxml();

	if(!$parse)
	{
		if (file_exists($g['tmp_path'] . '/config.cache'))
		{
			$config = unserialize(file_get_contents($g['tmp_path'] . '/config.cache'));
			if (is_null($config))
				$parse = true;
		} else
			$parse = true;
	}

	if ($parse == true) {
		if(!file_exists($g['conf_path'] . "/config.xml")) {
			if($g['booting'])
				echo ".";
			log_error("No config.xml found, attempting last known config restore.");
			$last_backup = discover_last_backup();
			if ($last_backup)
				restore_backup("/cf/conf/backup/{$last_backup}");
			else {
				log_error("Could not restore config.xml.");
				unlock($lockkey);
				die("Config.xml is corrupted and is 0 bytes.  Could not restore a previous backup.");
			}
		}
		$config = parse_xml_config($g['conf_path'] . '/config.xml', array($g['xml_rootobj'], 'pfsense'));
		if($config == -1) {
			$last_backup = discover_last_backup();
			if ($last_backup)
				restore_backup("/cf/conf/backup/{$last_backup}");
			else {
				log_error("Could not restore config.xml.");
				unlock($lockkey);
				die("Config.xml is corrupted and is 0 bytes.  Could not restore a previous backup.");
			}
		}
		generate_config_cache($config);
	}

	$config_parsed = true;
	unlock($lockkey);

	alias_make_table($config);

	return $config;
}

function generate_config_cache($config)
{
	global $g, $config_extra;

	$configcache = fopen($g['tmp_path'] . '/config.cache', "w");
	fwrite($configcache, serialize($config));
	fclose($configcache);
	unset($configcache);
	/* Used for config.extra.xml */
	if(file_exists($g['tmp_path'] . '/config.extra.cache') && $config_extra) {
		$configcacheextra = fopen($g['tmp_path'] . '/config.extra.cache', "w");
		fwrite($configcacheextra, serialize($config_extra));
		fclose($configcacheextra);
		unset($configcacheextra);
	}
}

function discover_last_backup()
{
	$backups = split("\n", `cd /cf/conf/backup && ls -ltr *.xml | awk '{print \$9}'`);
	$last_backup = "";
        foreach($backups as $backup)
        	if($backup)
	        	$last_backup = $backup;

	return $last_backup;
}

function restore_backup($file)
{
	global $g;

	if (file_exists($file))
	{
		unlink_if_exists("{$g['tmp_path']}/config.cache");
		copy("$file","/cf/conf/config.xml");
		disable_security_checks();
		log_error("{$g['product_name']} $file ayar dosyasi yeniden yukleniyor...");
	}
}

function parse_config_bootup()
{
	global $config, $g;

	if($g['booting'])
		echo ".";

	$lockkey = lock('config');
	if (!file_exists("{$g['conf_path']}/config.xml"))
	{
		if ($g['booting'])
		{
			if (strstr($g['platform'], "cdrom"))
			{
				echo "Ayarlar sifirlaniyor\n";
				reset_factory_defaults(true);
				if (!file_exists("{$g['conf_path']}/config.xml"))
				{
					echo "Ayar dosyasi bulunamadi. Varsayilan ayar yukleniyor...\n";
				}
			}
			else
			{
				$last_backup = discover_last_backup();
				if($last_backup)
				{
					restore_backup("/cf/conf/backup/{$last_backup}");
				}

				if(!file_exists("{$g['conf_path']}/config.xml"))
				{
					unlock($lockkey);
					mwexec("/sbin/halt");
					exit;
				}
			}
		}
		else
		{
			unlock($lockkey);
			exit(0);
		}
	}

	if (filesize("{$g['conf_path']}/config.xml") == 0)
	{
		$last_backup = discover_last_backup();
		if($last_backup) {
			log_error("No config.xml found, attempting last known config restore.");
			restore_backup("{$g['conf_path']}/backup/{$last_backup}");
		} else {
			unlock($lockkey);
			die("Config.xml is corrupted and is 0 bytes.  Could not restore a previous backup.");
		}
	}
	unlock($lockkey);
	parse_config(true);

	if ((float)$config['version'] > (float)$g['latest_config']) {
		echo <<<EOD


*******************************************************************************
* UYARI!                                                                    *
* The current configuration has been created with a newer version of {$g['product_name']}  *
* than this one! This can lead to serious misbehavior and even security       *
* holes! You are urged to either upgrade to a newer version of {$g['product_name']} or     *
* revert to the default configuration immediately!                            *
*******************************************************************************


EOD;
		}

	alias_make_table($config);
}

function convert_config()
{
	global $config, $g;
	$now = date("H:i:s");
	log_error("Start Configuration upgrade at $now, set execution timeout to 15 minutes");

	$cron_item_count = count($config['cron']['item']);

	for($x=0; $x<$cron_item_count; $x++)
	{
		if(stristr($config['cron']['item'][$x]['command'], "rc.update_bogons.sh"))
		{
			if($config['cron']['item'][$x]['hour'] == "*" )
			{
		        $config['cron']['item'][$x]['hour'] = "3";
		 		write_config();
		 	}
		}
	}

	if ($config['version'] == $g['latest_config'])
		return;

	$prev_version = $config['version'];

	include_once('auth.inc');

	while ($config['version'] < $g['latest_config'])
	{
		$cur = $config['version'] * 10;
		$next = $cur + 1;
		$migration_function = sprintf('upgrade_%03d_to_%03d', $cur, $next);
		if (function_exists($migration_function))
			$migration_function();
		$migration_function = "{$migration_function}_custom";
		if (function_exists($migration_function))
			$migration_function();
		$config['version'] = sprintf('%.1f', $next / 10);
	}

	$now = date("H:i:s");
	log_error("Ended Configuration upgrade at $now");

	if ($prev_version != $config['version'])
	write_config("Config dosyasi donusturuldu");
}

function safe_write_file($file, $content, $force_binary)
{
	$tmp_file = $file . "." . getmypid();
	$write_mode = $force_binary ? "wb" : "w";

	$fd = fopen($tmp_file, $write_mode);

	if (!$fd)
		return false;

	if (!fwrite($fd, $content))
	{
		fclose($fd);
		return false;
	}

	fflush($fd);
	fclose($fd);

	if (!rename($tmp_file, $file))
	{
		@unlink($tmp_file);
		return false;
	}

	pfSense_sync();

	return true;
}

function write_config($desc='Bilinmeyen', $backup = true)
{
	global $config, $g;

	$username = empty($_SESSION["Username"]) ? "(system)" : $_SESSION['Username'];

	if (!empty($_SERVER['REMOTE_ADDR']))
		$username .= '@' . $_SERVER['REMOTE_ADDR'];

	if($backup)
		backup_config();

	if (!is_array($config['revision']))
		$config['revision'] = array();

	if (time() > mktime(0, 0, 0, 9, 1, 2004))
		$config['revision']['time'] = time();

	if ($desc == 'Bilinmeyen')
		$desc = "{$_SERVER['SCRIPT_NAME']} bilinmeyen bir degisiklik yapildi.";

	$config['revision']['description'] = "{$username}: " . $desc;
	$config['revision']['username'] = $username;

	$lockkey = lock('config', LOCK_EX);

	$xmlconfig = dump_xml_config($config, $g['xml_rootobj']);

	if (!safe_write_file("{$g['cf_conf_path']}/config.xml", $xmlconfig, false))
	{
		log_error("UYARI: Ayar dosyasi acilamiyor.");
		unlock($lockkey);
		return -1;
	}

	cleanup_backupcache(30, true);

	$config = parse_xml_config("{$g['conf_path']}/config.xml", $g['xml_rootobj']);
	if ($config == -1)
	{
		copy("{$g['conf_path']}/config.xml", "{$g['conf_path']}/config.xml.bad");
		$last_backup = discover_last_backup();
		if ($last_backup)
		{
			restore_backup("/cf/conf/backup/{$last_backup}");
			$config = parse_xml_config("{$g['conf_path']}/config.xml", $g['xml_rootobj']);
			if ($g['booting'])
			{
				echo "\n\n ************** UYARI **************";
				echo "\n\n Ayar dosyasi hatali oldugu icin bir onceki ayar dosyasi yuklendi. \n";
				echo "\n Hatali ayar dosyasi{$g['conf_path']}/config.xml.bad olarak kaydedildi.\n\n";
			}
		}
		else
			log_error("config.xml. ayar dosyasi geri yuklenemedi.");
	}
	else
		generate_config_cache($config);

	unlock($lockkey);

	return $config;
}

function reset_factory_defaults($lock = false)
{
	global $g;

	if (!$lock)
		$lockkey = lock('config', LOCK_EX);

	safe_mkdir("{$g['cf_conf_path']}");

	$dh = opendir($g['conf_path']);

	while ($filename = readdir($dh))
	{
		if (($filename != ".") && ($filename != ".."))
		{
			unlink_if_exists($g['conf_path'] . "/" . $filename);
		}
	}

	closedir($dh);

	copy("{$g['conf_default_path']}/config.xml", "{$g['conf_path']}/config.xml");

	disable_security_checks();

	touch('/etc/nuclewall/password_init');
	touch("/conf/trigger_initial_wizard");

	if (!$lock)
		unlock($lockkey);

	return 0;
}

function config_restore($conffile)
{
	global $config, $g;

	if (!file_exists($conffile))
		return 1;

	backup_config();

	$lockkey = lock('config', LOCK_EX);

	unlink_if_exists("{$g['tmp_path']}/config.cache");
	copy($conffile, "{$g['cf_conf_path']}/config.xml");

	disable_security_checks();

	unlock($lockkey);

	$config = parse_config(true);

	write_config();

	return 0;
}

function config_install($conffile)
{
	global $config, $g;

	if (!file_exists($conffile))
		return 1;

	if (!config_validate("{$conffile}"))
		return 1;

	$lockkey = lock('config', LOCK_EX);

	copy($conffile, "{$g['conf_path']}/config.xml");

	disable_security_checks();

	if(file_exists("{$g['tmp_path']}/config.cache"))
		unlink("{$g['tmp_path']}/config.cache");

	unlock($lockkey);

    return 0;
}

function disable_security_checks()
{
	global $g;
	touch("{$g['tmp_path']}/disable_security_checks");
}

function restore_security_checks()
{
	global $g;
	unlink_if_exists("{$g['tmp_path']}/disable_security_checks");
}

function security_checks_disabled()
{
	global $g;
	return file_exists("{$g['tmp_path']}/disable_security_checks");
}

function config_validate($conffile)
{
	global $g, $xmlerr;

	$xml_parser = xml_parser_create();

	if(!($fp = fopen($conffile, "r")))
	{
		$xmlerr = "XML error: unable to open file";
		return false;
	}

	while ($data = fread($fp, 4096))
	{
		if (!xml_parse($xml_parser, $data, feof($fp)))
		{
			$xmlerr = sprintf("%s at line %d",
				xml_error_string(xml_get_error_code($xml_parser)),
				xml_get_current_line_number($xml_parser));
			return false;
		}
	}
	xml_parser_free($xml_parser);

	fclose($fp);

	return true;
}

function cleanup_backupcache($revisions = 30, $lock = false)
{
	global $g;
	$i = false;

	if (!$lock)
		$lockkey = lock('config');

	$backups = get_backups();
	if ($backups)
	{
		$baktimes = $backups['versions'];
		unset($backups['versions']);
	}
	else
	{
		$backups = array();
		$baktimes = array();
	}

	$newbaks = array();
	$bakfiles = glob($g['cf_conf_path'] . "/backup/config-*");
	$tocache = array();

	foreach($bakfiles as $backup)
	{
		if(filesize($backup) == 0)
		{
			unlink($backup);
			continue;
		}

		$tocheck = array_shift(explode('.', array_pop(explode('-', $backup))));

		if(!in_array($tocheck, $baktimes))
		{
			$i = true;
			$newxml = parse_xml_config($backup, array($g['xml_rootobj'], 'pfsense'));
			if($newxml == "-1")
			{
				log_error("The backup cache file $backup is corrupted.  Unlinking.");
				unlink($backup);
				log_error("The backup cache file $backup is corrupted.  Unlinking.");
				continue;
			}
			if($newxml['revision']['description'] == "")
				$newxml['revision']['description'] = "Unknown";
			$tocache[$tocheck] = array('description' => $newxml['revision']['description']);
		}
	}
	foreach($backups as $checkbak) {
		if(count(preg_grep('/' . $checkbak['time'] . '/i', $bakfiles)) != 0) {
			$newbaks[] = $checkbak;
		} else {
			$i = true;
			if($g['booting']) print " " . $tocheck . "r";
		}
	}
	foreach($newbaks as $todo) $tocache[$todo['time']] = array('description' => $todo['description']);
	if(is_int($revisions) and (count($tocache) > $revisions)) {
		$toslice = array_slice(array_keys($tocache), 0, $revisions);
		foreach($toslice as $sliced)
			$newcache[$sliced] = $tocache[$sliced];
		foreach($tocache as $version => $versioninfo) {
			if(!in_array($version, array_keys($newcache))) {
				unlink_if_exists($g['conf_path'] . '/backup/config-' . $version . '.xml');
			}
		}
		$tocache = $newcache;
	}
	$bakout = fopen($g['cf_conf_path'] . '/backup/backup.cache', "w");
	fwrite($bakout, serialize($tocache));
	fclose($bakout);

	if (!$lock)
		unlock($lockkey);
}

function get_backups()
{
	global $g;
	if(file_exists("{$g['cf_conf_path']}/backup/backup.cache"))
	{
		$confvers = unserialize(file_get_contents("{$g['cf_conf_path']}/backup/backup.cache"));
		$bakvers = array_keys($confvers);
		$toreturn = array();
		sort($bakvers);
		// 	$bakvers = array_reverse($bakvers);
		foreach(array_reverse($bakvers) as $bakver)
			$toreturn[] = array('time' => $bakver, 'description' => $confvers[$bakver]['description']);
	} else {
		return false;
	}
	$toreturn['versions'] = $bakvers;
	return $toreturn;
}

function backup_config()
{
	global $config, $g;

	if($g['platform'] == "cdrom")
		return;
	safe_mkdir("{$g['cf_conf_path']}/backup");

    if($config['revision']['time'] == "")
	{
            $baktime = 0;
    }
	else
	{
            $baktime = $config['revision']['time'];
    }

	if($config['revision']['description'] == "")
	{
		$bakdesc = "Unknown";
    }
	else
	{
		$bakdesc = $config['revision']['description'];
    }

    copy($g['cf_conf_path'] . '/config.xml', $g['cf_conf_path'] . '/backup/config-' . $baktime . '.xml');
    if(file_exists($g['cf_conf_path'] . '/backup/backup.cache')) {
            $backupcache = unserialize(file_get_contents($g['cf_conf_path'] . '/backup/backup.cache'));
    } else {
            $backupcache = array();
    }
    $backupcache[$baktime] = array('description' => $bakdesc);
    $bakout = fopen($g['cf_conf_path'] . '/backup/backup.cache', "w");
    fwrite($bakout, serialize($backupcache));
    fclose($bakout);

	return true;
}

function set_device_perms()
	{
	$devices = array(
		'pf' => array(	'user'	=> 'root',
			'group'	=> 'proxy',
			'mode'	=> 0660),
		);

	foreach ($devices as $name => $attr)
	{
		$path = "/dev/$name";
		if (file_exists($path)) {
			chown($path, $attr['user']);
			chgrp($path, $attr['group']);
			chmod($path, $attr['mode']);
		}
	}
}

?>
