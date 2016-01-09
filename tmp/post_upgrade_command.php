#!/usr/local/bin/php -f 
<?php

	require_once("globals.inc");
	require_once("config.inc");
	require_once("functions.inc");

	if(file_exists("/usr/local/bin/git") && isset($config['system']['gitsync']['synconupgrade'])) {
		if(!empty($config['system']['gitsync']['repositoryurl']))
			exec("cd /home/nuclewall/nucle_repo/nucle_repo && git config remote.origin.url " . escapeshellarg($config['system']['gitsync']['repositoryurl']));
		if(!empty($config['system']['gitsync']['branch']))
			system("pfSsh.php playback gitsync " . escapeshellarg($config['system']['gitsync']['branch']) . " --upgrading");
	}

	setup_serial_port();
		
	$files_to_process = split("\n", file_get_contents("/etc/pfSense.obsoletedfiles"));
	foreach($files_to_process as $filename) 
		if(file_exists($filename)) 
			exec("/bin/rm -f $filename");

?>
