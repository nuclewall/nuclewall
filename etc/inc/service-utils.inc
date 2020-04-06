<?php
/*
	service-utils.inc

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.
*/

define("RCFILEPREFIX", "/usr/local/etc/rc.d/");

function start_mysql()
{
	$rcfile_fullname = RCFILEPREFIX .  'mysql-server.sh';
	if(file_exists($rcfile_fullname))
	{
		mwexec_bg("{$rcfile_fullname} start && logger 'MySQL started.'");
		return;
	}
}

function start_radius()
{
	$rcfile_fullname = RCFILEPREFIX .  'radiusd.sh';
	if(file_exists($rcfile_fullname))
	{
		mwexec_bg("{$rcfile_fullname} start && logger 'FreeRADIUS server started.'");
		return;
	}
}

function stop_mysql()
{
	if(is_process_running("mysqld"))
		mwexec("/usr/bin/killall mysqld");
}

function stop_radius()
{
	if(is_process_running("radiusd"))
		mwexec("/usr/bin/killall radiusd");
}

function is_pid_running($pidfile)
{
	if (!file_exists($pidfile))
		return false;
	return isvalidpid($pidfile);
}

function is_dhcp_running($interface)
{
	$status = find_dhclient_process($interface);
	if($status <> "")
		return true;
	return false;
}

?>
