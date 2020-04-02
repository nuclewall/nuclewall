<?php
/*
	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.

	Copyright (C) 2010 Ermal Luci
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
*/

require_once("config.gui.inc");

$security_passed = true;

unset($security_passed);

$groupindex = index_groups();
$userindex = index_users();

function index_groups()
{
	global $g, $config, $groupindex;

	$groupindex = array();

	if (is_array($config['system']['group']))
	{
		$i = 0;
		foreach($config['system']['group'] as $groupent)
		{
			$groupindex[$groupent['name']] = $i;
			$i++;
		}
	}

	return ($groupindex);
}

function index_users()
{
	global $g, $config;

	if (is_array($config['system']['user']))
	{
		$i = 0;
		foreach($config['system']['user'] as $userent)
		{
			$userindex[$userent['name']] = $i;
			$i++;
		}
	}
	return ($userindex);
}

function & getUserEntry($name)
{
	global $config, $userindex;

	if (isset($userindex[$name]))
		return $config['system']['user'][$userindex[$name]];
}

function & getUserEntryByUID($uid)
{
	global $config;

	if (is_array($config['system']['user']))
		foreach ($config['system']['user'] as & $user)
			if ($user['uid'] == $uid)
				return $user;

	return false;
}

function & getGroupEntry($name)
{
	global $config, $groupindex;
	if (isset($groupindex[$name]))
		return $config['system']['group'][$groupindex[$name]];
}

function & getGroupEntryByGID($gid)
{
	global $config;

	if (is_array($config['system']['group']))
		foreach ($config['system']['group'] as & $group)
			if ($group['gid'] == $gid)
				return $group;

	return false;
}

function get_user_privileges(& $user)
{
	$privs = $user['priv'];

	if (!is_array($privs))
		$privs = array();

	$names = local_user_get_groups($user, true);

	foreach ($names as $name)
	{
		$group = getGroupEntry($name);
		if (is_array($group['priv']))
			$privs = array_merge( $privs, $group['priv']);
	}

	return $privs;
}

function userHasPrivilege($userent, $privid = false)
{
        if (!$privid || !is_array($userent))
                return false;

        $privs = get_user_privileges($userent);

        if (!is_array($privs))
                return false;

        if (!in_array($privid, $privs))
                return false;

        return true;
}

function local_backed($username, $passwd)
{
	$user = getUserEntry($username);
	if (!$user)
		return false;

	if (is_account_disabled($username) || is_account_expired($username))
		return false;

	if ($user['password'])
	{
		$passwd = crypt($passwd, $user['password']);
		if ($passwd == $user['password'])
			return true;
	}

	if ($user['md5-hash'])
	{
		$passwd = md5($passwd);
		if ($passwd == $user['md5-hash'])
			return true;
	}
	return false;
}

function local_sync_accounts()
{
	global $config;

	$fd = popen("/usr/sbin/pw usershow -a", "r");
	if ($fd)
	{
		while (!feof($fd))
		{
			$line = explode(":",fgets($fd));
			if (((!strncmp($line[0], "_", 1)) || ($line[2] < 2000) || ($line[2] > 65000)) && ($line[0] != "admin"))
				continue;
			$cmd = "/usr/sbin/pw userdel -n '{$line[0]}'";

			mwexec($cmd);
		}
		pclose($fd);
	}

	$gids = array();
	$fd = popen("/usr/sbin/pw groupshow -a", "r");
	if ($fd)
	{
		while (!feof($fd))
		{
			$line = explode(":",fgets($fd));
			if (!strncmp($line[0], "_", 1))
				continue;
			if ($line[2] < 2000)
				continue;
			if ($line[2] > 65000)
				continue;
			$cmd = "/usr/sbin/pw groupdel {$line[2]}";
			mwexec($cmd);
		}
		pclose($fd);
	}

	$allgrp = getGroupEntryByGID(1998);
	local_group_set($allgrp, true);

	if (is_array($config['system']['user']))
		foreach ($config['system']['user'] as $user)
			local_user_set($user);

	if (is_array($config['system']['group']))
		foreach ($config['system']['group'] as $group)
			local_group_set($group);
}

function local_user_set(& $user)
{
	global $g;

	if (empty($user['password']))
	{
		log_error("There is an error in your settings. Password is missing for {$user['name']}");
		return;
	}

	$home_base = "/home/";
	$user_uid = $user['uid'];
	$user_name = $user['name'];
	$user_home = "{$home_base}{$user_name}";
	$user_shell = "/etc/rc.initial.utf";
	$user_group = "nobody";

	if (!is_dir($home_base))
		mkdir($home_base, 0755);

	$lock_account = false;

	if (userHasPrivilege($user, "user-shell-access") || userHasPrivilege($user, "page-all"))
	{
		$user_shell = "/bin/tcsh";
	}
	else
	{
		$user_shell = "/sbin/nologin";
	}

	if ((is_account_disabled($user_name) || is_account_expired($user_name)) && ($user_uid != 0))
	{
		$user_shell = "/sbin/nologin";
		$lock_account = true;
	}

	if ($user_uid == 0)
	{
		$cmd = "/usr/sbin/pw usermod -q -n root -s /bin/sh -H 0";
		$fd = popen($cmd, "w");
		fwrite($fd, $user['password']);
		pclose($fd);
		$user_group = "wheel";
		$user_home = "/root";
		$user_shell = "/etc/rc.initial.utf";
	}

	$fd = popen("/usr/sbin/pw usershow -n {$user_name} 2>&1", "r");
	$pwread = fgets($fd);
	pclose($fd);
	$userattrs = explode(":", trim($pwread));

	if (($userattrs[0] != $user['name']) || (!strncmp($pwread, "pw:", 3)))
		$user_op = "useradd -m -k /etc/skel -o";
	else
		$user_op = "usermod";

	$comment = str_replace(array(":", "!", "@"), " ", $user['descr']);
	$cmd = "/usr/sbin/pw {$user_op} -q -u {$user_uid} -n {$user_name}".
			" -g {$user_group} -s {$user_shell} -d {$user_home}".
			" -c ".escapeshellarg($comment)." -H 0 2>&1";

	$fd = popen($cmd, "w");
	fwrite($fd, $user['password']);
	pclose($fd);

	if (!is_dir($user_home))
	{
		mkdir($user_home, 0700);
		mwexec("/bin/cp /root/.* {$home_base}/", true);
	}

	chown($user_home, $user_name);
	chgrp($user_home, $user_group);

	if($user['authorizedkeys'])
	{
		if (!is_dir("{$user_home}/.ssh"))
		{
			mkdir("{$user_home}/.ssh", 0700);
			chown("{$user_home}/.ssh", $user_name);
		}

		$keys = base64_decode($user['authorizedkeys']);
		file_put_contents("{$user_home}/.ssh/authorized_keys", $keys);
		chown("{$user_home}/.ssh/authorized_keys", $user_name);
	}

	else
		unlink_if_exists("{$user_home}/.ssh/authorized_keys");

	$un = $lock_account ? "" : "un";
	exec("/usr/sbin/pw {$un}lock {$user_name} -q");
}

function local_user_del($user)
{
	local_user_set_groups($user);

	if ($user['uid'] != 0)
		$rmhome = "-r";

	$fd = popen("/usr/sbin/pw usershow -n {$user['name']} 2>&1", "r");
	$pwread = fgets($fd);
	pclose($fd);
	$userattrs = explode(":", trim($pwread));

	if ($userattrs[0] != $user['name'])
	{
		return;
	}

	$cmd = "/usr/sbin/pw userdel -n {$user['name']} {$rmhome}";
	mwexec($cmd);

	local_group_del_user($user);
}

function local_user_set_password(& $user, $password)
{
	$user['password'] = crypt($password);
	$user['md5-hash'] = md5($password);

	$astr = (string) $password;
	$ustr = '';
	for ($i = 0; $i < strlen($astr); $i++)
	{
		$a = ord($astr{$i}) << 8;
		$ustr.= sprintf("%X", $a);
	}

	$user['nt-hash'] = bin2hex(mhash(MHASH_MD4, $ustr));
}

function local_user_get_groups($user, $all = false)
{
	global $config;

	$groups = array();
	if (!is_array($config['system']['group']))
		return $groups;

	foreach ($config['system']['group'] as $group)
		if ( $all || ( !$all && ($group['name'] != "all")))
			if (is_array($group['member']))
				if (in_array($user['uid'], $group['member']))
					$groups[] = $group['name'];

	if ( $all )
		$groups[] = "all";

	sort($groups);
	return $groups;
}

function local_user_set_group_admin($user)
{
	global $config, $groupindex;

	if (!is_array($config['system']['group']))
		return;

	$cur_groups = local_user_get_groups($user, true);
	$mod_groups = array();

	if (!is_array($new_groups))
		$new_groups = array();

	if (!is_array($cur_groups))
		$cur_groups = array();

		$group = & $config['system']['group'][$groupindex['admins']];
		$group['member'][] = $user['uid'];
		$mod_groups[] = $group;
	unset($group);

	foreach ($mod_groups as $group)
		local_group_set($group);
}

function local_user_set_groups($user, $new_groups = NULL )
{
	global $config, $groupindex;

	if (!is_array($config['system']['group']))
		return;

	$cur_groups = local_user_get_groups($user, true);
	$mod_groups = array();

	if (!is_array($new_groups))
		$new_groups = array();

	if (!is_array($cur_groups))
		$cur_groups = array();

	foreach ($new_groups as $groupname)
	{
		if (in_array($groupname,$cur_groups))
			continue;
		$group = & $config['system']['group'][$groupindex[$groupname]];
		$group['member'][] = $user['uid'];
		$mod_groups[] = $group;
	}
	unset($group);

	foreach ($cur_groups as $groupname)
	{
		if (in_array($groupname,$new_groups))
			continue;
		if (!isset($config['system']['group'][$groupindex[$groupname]]))
			continue;
		$group = & $config['system']['group'][$groupindex[$groupname]];
		if (is_array($group['member']))
		{
			$index = array_search($user['uid'], $group['member']);
			array_splice($group['member'], $index, 1);
			$mod_groups[] = $group;
		}
	}
	unset($group);

	foreach ($mod_groups as $group)
		local_group_set($group);
}

function local_group_del_user($user)
{
	global $config;

	if (!is_array($config['system']['group']))
		return;

	foreach ($config['system']['group'] as $group)
	{
		if(is_array($group['member']))
		{
			foreach ($group['member'] as $idx => $uid)
			{
				if ($user['uid'] == $uid)
					unset($config['system']['group']['member'][$idx]);
			}
		}
	}
}

function local_group_set($group, $reset = false)
{
	$group_name = $group['name'];
	$group_gid = $group['gid'];
	$group_members = "''";
	if (!$reset && !empty($group['member']) && count($group['member']) > 0)
		$group_members = implode(",",$group['member']);

	$fd = popen("/usr/sbin/pw groupshow {$group_name} 2>&1", "r");
	$pwread = fgets($fd);
	pclose($fd);

	if (!strncmp($pwread, "pw:", 3))
		$group_op = "groupadd";
	else
		$group_op = "groupmod";

	$cmd = "/usr/sbin/pw {$group_op} {$group_name} -g {$group_gid} -M {$group_members} 2>&1";

	mwexec($cmd);
}

function local_group_del($group)
{
	$cmd = "/usr/sbin/pw groupdel {$group['name']}";
	mwexec($cmd);
}

function get_user_expiration_date($username)
{
	$user = getUserEntry($username);
	if ($user['expires'])
		return $user['expires'];
}

function is_account_expired($username)
{
	$expirydate = get_user_expiration_date($username);
	if ($expirydate)
	{
		if (strtotime("-1 day") > strtotime(date("m/d/Y",strtotime($expirydate))))
			return true;
	}

	return false;
}

function is_account_disabled($username)
{
	$user = getUserEntry($username);
	if (isset($user['disabled']))
		return true;

	return false;
}

function authenticate_user($username, $password)
{
	return local_backed($username, $password);
}

function session_auth()
{
	global $HTTP_SERVER_VARS, $config, $_SESSION, $page;

	if($config['system']['webgui']['protocol'] == "https")
	{
		$currentCookieParams = session_get_cookie_params();
		session_set_cookie_params(
			$currentCookieParams["lifetime"],
			$currentCookieParams["path"],
			NULL,
			true,
			true
		);
	}

	if (!session_id())
		session_start();

	if (isset($_POST['login']) && !empty($_POST['usernamefld']) && !empty($_POST['passwordfld']))
	{
		if (authenticate_user($_POST['usernamefld'], $_POST['passwordfld']))
		{
			$_SESSION['Logged_In'] = "True";
			$_SESSION['Username'] = $_POST['usernamefld'];
			$_SESSION['last_access'] = time();
			if(! isset($config['system']['webgui']['quietlogin'])) {
				log_auth("User '{$_POST['usernamefld']}' logged in from ({$_SERVER['REMOTE_ADDR']})");
			}
			$HTTP_SERVER_VARS['AUTH_USER'] = $_SESSION['Username'];
			if (isset($_POST['postafterlogin']))
				return true;
			else
			{
				if (empty($page))
					$page = "/";
				header("Location: {$page}");
			}
			exit;
		}
		else
		{
			$_SESSION['Login_Error'] = "Username or Password incorrect";
			log_auth("Authentication error for '{$_POST['usernamefld']}' from {$_SERVER['REMOTE_ADDR']}");
		}
	}

	if (empty($_SESSION['Logged_In']))
		return false;

	if (!isset($config['system']['webgui']['session_timeout']))
	{
		if ($_SESSION['last_access'] < (time() - 14400))
		{
			$_GET['logout'] = true;
			$_SESSION['Logout'] = true;
		} else
			$_SESSION['last_access'] = time();
	}
	else
	{
		if ($_SESSION['last_access'] < (time() - ($config['system']['webgui']['session_timeout'] * 60)))
		{
			$_GET['logout'] = true;
			$_SESSION['Logout'] = true;
		}
	}

	if (isset($_GET['logout']))
	{
		if ($_SESSION['Logout'])
			log_error("Session timed out for user '{$_SESSION['Username']}' from: {$_SERVER['REMOTE_ADDR']}");
		else
			log_error("User logged out for user '{$_SESSION['Username']}' from: {$_SERVER['REMOTE_ADDR']}");

		$_SESSION = array();

		if (isset($_COOKIE[session_name()]))
			setcookie(session_name(), '', time()-42000, '/');

		session_destroy();

		$scriptName = split("/", $_SERVER["SCRIPT_FILENAME"]);
		$scriptElms = count($scriptName);
		$scriptName = $scriptName[$scriptElms-1];

		Header("Location: {$scriptName}");

		return false;
	}

	if ($_GET['disable_ajax'])
		$_SESSION['NO_AJAX'] = "True";


	if ($_GET['enable_ajax'])
		unset($_SESSION['NO_AJAX']);

	$HTTP_SERVER_VARS['AUTH_USER'] = $_SESSION['Username'];
	return true;
}

?>
