<?php

/*
    system_usermanager.php
    part of m0n0wall (http://m0n0.ch/wall)

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.

	Copyright (C) 2008 Shrew Soft Inc.
    All rights reserved.

    Copyright (C) 2005 Paul Taylor <paultaylor@winn-dixie.com>.
    All rights reserved.

    Copyright (C) 2003-2005 Manuel Kasper <mk@neon1.net>.
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

require('guiconfig.inc');

function print_privs($p, $plist)
{
	foreach($p as $user_priv)
	{
		if(key_exists($user_priv , $plist))
		{
		echo "<span class=\"\">{$plist[$user_priv]['name']}</span> - ";
		}
	}
}

$pgtitle = array('SYSTEM ', 'USER MANAGEMENT');

$id = $_GET['id'];

if (isset($_POST['id']))
	$id = $_POST['id'];

if (!is_array($config['system']['user']))
	$config['system']['user'] = array();

$a_user = &$config['system']['user'];

if (isset($id) && $a_user[$id])
{
	$pconfig['usernamefld'] = $a_user[$id]['name'];
	$pconfig['uid'] = $a_user[$id]['uid'];
	$pconfig['authorizedkeys'] = base64_decode($a_user[$id]['authorizedkeys']);
	$pconfig['priv'] = $a_user[$id]['priv'];
}

if ($_GET['act'] == 'deluser')
{
	if (!$a_user[$id])
	{
		header("system_usermanager.php");
		exit;
	}

	if($a_user[$id]['uid'] == '0')
	$input_errors[] = "System user can't be deleted.";

	else
	{
		local_user_del($a_user[$id]);
		$userdeleted = $a_user[$id]['name'];
		unset($a_user[$id]);
		write_config();
		$savemsg = "User {$userdeleted} deleted successfully.";
	}
}

else if ($_GET['act'] == "new")
{
	$pconfig['utype'] = "user";
	$pconfig['lifetime'] = 3650;
}

if ($_POST)
{
	unset($input_errors);
	$pconfig = $_POST;

	$privs = array();

	$admin_privs = array('user-shell-access', 'page-all');

	$privs[] = 'page-essential';

	if($_POST['user-shell-access'])
		$privs[] = $_POST['user-shell-access'];

	if($_POST['hotspot-menu'])
		$privs[] = $_POST['hotspot-menu'];

	if($_POST['system-menu'])
		$privs[] = $_POST['system-menu'];

	if($_POST['network-menu'])
		$privs[] = $_POST['network-menu'];

	if($_POST['firewall-menu'])
		$privs[] = $_POST['firewall-menu'];

	if($_POST['services-menu'])
		$privs[] = $_POST['services-menu'];

	if($_POST['status-menu'])
		$privs[] = $_POST['status-menu'];

	if($_POST['tools-menu'])
		$privs[] = $_POST['tools-menu'];

	if (isset($id) && ($a_user[$id]))
	{
		$reqdfields = explode(" ", 'usernamefld');
		$reqdfieldsn = array('Kullanıcı Adı');
	}
	else
	{
		$reqdfields = explode(" ", 'usernamefld passwordfld1');
		$reqdfieldsn = array('Username', 'Password');
	}

	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if (preg_match("/[^a-zA-Z0-9\.\-_]/", $_POST['usernamefld']))
		$input_errors[] = "The username contains invalid characters.";

	if (strlen($_POST['usernamefld']) > 16)
		$input_errors[] = "The username is longer than 16 characters.";

	if (($_POST['passwordfld1']) && ($_POST['passwordfld1'] != $_POST['passwordfld2']))
		$input_errors[] = "The passwords do not match.";

	if (isset($id) && $a_user[$id])
		$oldusername = $a_user[$id]['name'];
	else
		$oldusername = "";

	if (!$input_errors)
	{
		foreach ($a_user as $userent)
		{
			if ($userent['name'] == $_POST['usernamefld'] && $oldusername != $_POST['usernamefld'])
			{
			$input_errors[] = "Another entry with the same username already exists.";
				break;
			}
		}
	}

	if (!$input_errors)
	{
		$system_users = explode("\n", file_get_contents("/etc/passwd"));
		foreach ($system_users as $s_user)
		{
			$ent = explode(":", $s_user);
			if ($ent[0] == $_POST['usernamefld'] && $oldusername != $_POST['usernamefld'])
			{
				$input_errors[] = "That username is reserved by the system.";
				break;
			}
		}
	}

	if(!$input_errors)
	{
		$userent = array();

		if(isset($id) && $a_user[$id])
		{
			$userent = $a_user[$id];

			if($_POST['usernamefld'] != $_POST['oldusername'])
			{
				$_SERVER['REMOTE_USER'] = $_POST['usernamefld'];
				local_user_del($userent);
			}

			if($userent['uid'] == '0')
			{
				$userent['scope'] = 'system';
				$userent['name'] = 'admin';
				$userent['priv'] = $admin_privs;
			}
			else
			{
				$userent['scope'] = 'user';
				$userent['name'] = $_POST['usernamefld'];
				$userent['priv'] = $privs;
			}

			if ($_POST['passwordfld1'])
				local_user_set_password($userent, $_POST['passwordfld1']);

			$userent['authorizedkeys'] = base64_encode($_POST['authorizedkeys']);

			$a_user[$id] = $userent;
		}
		else
		{
			$userent['scope'] = 'user';
			$userent['name'] = $_POST['usernamefld'];
			$userent['priv'] = $privs;

			local_user_set_password($userent, $_POST['passwordfld1']);

			$userent['authorizedkeys'] = base64_encode($_POST['authorizedkeys']);
			$userent['uid'] = $config['system']['nextuid']++;
			$a_user[] = $userent;
		}

		local_user_set($userent);
		write_config();
		header("system_usermanager.php");
	}
}

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>

<table cellpadding='0' cellspacing='0'>
	<tr>
		<td>
		<?php if ($_GET['act'] == "new" || $_GET['act'] == "edit" || $input_errors) : ?>
			<form action="system_usermanager.php" method="post" name="iform" id="iform">
				<table class="tabcont" cellpadding="0" cellspacing="0">
					<tr>
						<td valign="top" class="vncell">Username</td>
						<td class="vtable">
							<input required name="usernamefld" type="text" id="usernamefld" value="<?=htmlspecialchars($pconfig['usernamefld']);?>"/>
							<input name="oldusername" type="hidden" id="oldusername" value="<?=htmlspecialchars($pconfig['usernamefld']);?>" />
						</td>
					</tr>
					<tr>
						<td valign="top" class="vncell">Password</td>
						<td class="vtable">
							<input name="passwordfld1" type="password" id="passwordfld1" size="20" value="" />
						</td>
					</tr>
					<tr>
						<td valign="top" class="vncell">Password confirmation</td>
						<td class="vtable">
							<input name="passwordfld2" type="password" id="passwordfld2" size="20" value="" />
						</td>
					</tr>
					<?php if($pconfig['uid'] != '0'): ?>
					<tr>
						<td valign="top" class="vncell">Permissions</td>
						<td class="vtable">
							<table cellspacing="0" cellpadding="6">
								<tr>
									<td>
										<label>
											<input <?php if(in_array('user-shell-access', $pconfig['priv'])) echo 'checked'; ?> value="user-shell-access" name="user-shell-access" type="checkbox">SSH Login
										</label>
									</td>
									<td>
										<label>
											<input <?php if(in_array('page-hotspot-menu', $pconfig['priv'])) echo 'checked'; ?> value="page-hotspot-menu" name="hotspot-menu" type="checkbox">HOTSPOT menu
										</label>
									</td>
								</tr>
								<tr>
									<td>
										<label>
											<input <?php if(in_array('page-system-menu', $pconfig['priv'])) echo 'checked'; ?> value="page-system-menu" name="system-menu" type="checkbox">SYSTEM menu
										</label>
									</td>
									<td>
										<label>
											<input <?php if(in_array('page-network-menu', $pconfig['priv'])) echo 'checked'; ?> value="page-network-menu" name="network-menu" type="checkbox">INTERFACES menu
										</label>
									</td>
								</tr>
								<tr>
									<td>
										<label>
											<input <?php if(in_array('page-firewall-menu', $pconfig['priv'])) echo 'checked'; ?> value="page-firewall-menu" name="firewall-menu" type="checkbox">FIREWALL menu
											</label>
									</td>
									<td>
										<label>
											<input <?php if(in_array('page-services-menu', $pconfig['priv'])) echo 'checked'; ?> value="page-services-menu" name="services-menu" type="checkbox">SERVICES menu
										</label>
									</td>
								</tr>
								<tr>
									<td>
										<label>
											<input <?php if(in_array('page-status-menu', $pconfig['priv'])) echo 'checked'; ?> value="page-status-menu" name="status-menu" type="checkbox">STATUS menu
										</label>
									</td>
									<td>
										<label>
											<input <?php if(in_array('page-tools-menu', $pconfig['priv'])) echo 'checked'; ?> value="page-tools-menu" name="tools-menu" type="checkbox">TOOLS menu
										</label>
									</td>
								</tr>
							</table>
						</td>
					</tr>
					<?php endif; ?>
					<tr>
						<td valign="top" class="vncell">Authorized Keys</td>
						<td class="vtable">
							<textarea name="authorizedkeys" id="authorizedkeys"><?=htmlspecialchars($pconfig['authorizedkeys']);?></textarea>
							<p>
							Paste an authorized keys file here.
						</p>
						</td>
					</tr>
					<tr>
						<td class="vncell"></td>
						<td class="vtable">
							<input id="submit" name="save" type="submit" class="btn btn-inverse" value="Save" />
							<a class="btn btn-link" href="system_usermanager.php">System Users</a>
							<?php if (isset($id) && $a_user[$id]): ?>
							<input name="id" type="hidden" value="<?=$id;?>" />
							<?php endif;?>
						</td>
					</tr>
				</table>
			</form>
			<?php else: ?>
				<table class="tabcont" cellpadding="0" cellspacing="0">
					<tr>
						<td>
							<table class="grids">
								<tr>
									<td class="head">Username</td>
									<td class="head">Permissions</td>
									<td class="head"></td>
								</tr>
									<?php
										$i = 0;
										foreach($a_user as $userent):
									?>
								<tr>
									<td class="cell users">
										<a class="btn-link" title="Edit" href="system_usermanager.php?act=edit&id=<?=$i;?>">
											<?=htmlspecialchars($userent['name']);?>
										</a>
									</td>
									<td class="cell description">
											<?php print_privs($userent['priv'], $priv_list); ?>
									</td>
									<td class="cell tools">
										<a title="Edit" href="system_usermanager.php?act=edit&id=<?=$i;?>">
											<i class="icon-edit"></i>
										</a>
										<?php if($userent['scope'] != "system"): ?>
										<a title="Delete" href="system_usermanager.php?act=deluser&id=<?=$i;?>" onclick="return confirm('Are you sure you want to delete this user?')">
											<i class="icon-trash"></i>
										</a>
										<?php endif; ?>
									</td>
								</tr>
								<?php
									$i++;
									endforeach;
								?>
								<tr>
									<td class="cell" colspan="2"></td>
									<td class="cell tools">
										<a href="system_usermanager.php?act=new">
											<i class="icon-plus"></i>
										</a>
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			<?php endif; ?>
		</td>
	</tr>
</table>
</div>
</body>
</html>
