<?php
/*
		Copyright (C) 2013 Ogün AÇIK
		All rights reserved.

		Copyright (C) 2008 Shrew Soft Inc
		All rights reserved.

		Copyright (C) 2007, 2008 Scott Ullrich <sullrich@gmail.com>
		All rights reserved.

        Copyright (C) 2005-2006 Bill Marquette <bill.marquette@gmail.com>
        All rights reserved.

        Copyright (C) 2006 Paul Taylor <paultaylor@winn-dixie.com>.
        All rights reserved.

        Copyright (C) 2003-2006 Manuel Kasper <mk@neon1.net>.
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

		DISABLE_PHP_LINT_CHECKING
*/

require_once("priv.defs.inc");


function get_priv_files($directory)
{
	$dir_array = array();
	if(!is_dir($directory))
		return;
	if ($dh = opendir($directory))
	{
		while (($file = readdir($dh)) !== false) {
			$canadd = 0;
			if($file == ".")
				$canadd = 1;
			if($file == "..")
				$canadd = 1;
			if($canadd == 0)
				array_push($dir_array, $file);
		}
		closedir($dh);
	}
	if(!is_array($dir_array))
		return;
	return $dir_array;
}

if(is_array($priv_list))
	sort_privs($priv_list);

function cmp_privkeys($a, $b)
{
	$auser = strncmp("user-", $a, 5);
	$buser = strncmp("user-", $b, 5);
	if($auser != $buser)
		return $auser - $buser;

	return strcasecmp($a, $b);
}

function sort_privs(& $privs)
{
	uksort($privs, "cmp_privkeys");
}

function cmp_page_matches($page, & $matches, $fullwc = true)
{
	if (!is_array($matches))
		return false;

	$test = strpos($page, "/");
	if ($test !== false && $test == 0)
		$page = substr($page, 1);

	foreach ($matches as $match)
	{
		if (!$fullwc && !strcmp($match ,"*"))
			continue;

		$wcpos = strpos($match, "*");

		if ($wcpos === false)
			$result = strcmp($page, $match);
		else
		{
			$match = "/" . str_replace(array(".", "*"), array("\.", ".*"), $match) . "/";
			$result = !preg_match($match, $page);
		}

		if (!$result)
			return true;
	}
	return false;
}

function map_page_privname($page)
{
	global $priv_list;

	foreach ($priv_list as $pname => $pdata)
	{
		if (strncmp($pname, "page-", 5))
			continue;
		$fullwc = false;
		if (!strcasecmp($page,"any")||!strcmp($page,"*"))
			$fullwc = true;
		if (cmp_page_matches($page, $pdata['match'], $fullwc))
			return $pname;
	}

	return false;
}

function get_user_privdesc(& $user)
{
	global $priv_list;

	$privs = array();

	$user_privs = $user['priv'];
	if (!is_array($user_privs))
		$user_privs = array();

	$names = local_user_get_groups($user, true);

	foreach ($names as $name)
	{
		$group = getGroupEntry($name);
		$group_privs = $group['priv'];
		if (!is_array($group_privs))
			continue;
		foreach ($group_privs as $pname)
		{
			if (in_array($pname,$user_privs))
				continue;
			if (!$priv_list[$pname])
				continue;
			$priv = $priv_list[$pname];
			$priv['group'] = $group['name'];
			$privs[] = $priv;
		}
	}

	foreach ($user_privs as $pname)
		if($priv_list[$pname])
			$privs[] = $priv_list[$pname];

	return $privs;
}

function isAllowed($username, $page)
{
	global $_SESSION;

	if (!isset($username))
		return false;

	$user = getUserEntry($username);
	if (isset($user))
		if (isset($user['uid']))
			if ($user['uid']==0)
				return true;

	if (cmp_page_matches($page, $_SESSION['page-match']))
		return true;

	return false;
}

function isAllowedPage($page)
{
	global $_SESSION;

	$username = $_SESSION['Username'];

	if (!isset($username))
		return false;

	$user = getUserEntry($username);
	if (isset($user))
		if (isset($user['uid']))
			if ($user['uid']==0)
				return true;

	if (cmp_page_matches($page, $_SESSION['page-match']))
		return true;

	return false;
}

function getPrivPages(& $entry, & $allowed_pages)
{
	global $priv_list;

	if (!is_array($entry['priv']))
		return;

	foreach ($entry['priv'] as $pname)
	{
		if (strncmp($pname, "page-", 5))
			continue;
		$priv = &$priv_list[$pname];
		if (!is_array($priv))
			continue;
		$matches = &$priv['match'];
		if (!is_array($matches))
			continue;
		foreach ($matches as $match)
			$allowed_pages[] = $match;
	}
}

function getAllowedPages($username)
{
	global $config, $_SESSION;

	$allowed_pages = array();
	$allowed_groups = array();

	$local_user = getUserEntry($username);
	getPrivPages($local_user, $allowed_pages);

	if ($local_user)
		$allowed_groups = local_user_get_groups($local_user);

	if (is_array($config['system']['group']) && is_array($allowed_groups))
		foreach ($config['system']['group'] as $group)
			if (in_array($group['name'], $allowed_groups))
				getPrivPages($group, $allowed_pages);

	$_SESSION['page-match'] = $allowed_pages;

	return $allowed_pages;
}

function sort_user_privs($privs)
{
	$priority_privs = array("page-dashboard-all", "page-system-login/logout");

	$fprivs = array_intersect($privs, $priority_privs);
	$sprivs  = array_diff($privs, $priority_privs);

	return array_merge($fprivs, $sprivs);
}

?>
