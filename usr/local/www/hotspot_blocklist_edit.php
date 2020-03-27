<?php
/*
	hotspot_blocklist_edit.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.
*/

require('guiconfig.inc');
require('captiveportal.inc');
require('local_connection.inc');

$pgtitle = array('HOTSPOT ', 'BLOCK MAC ADDRESS');

/* Get active captiveportal sessions */
if (file_exists("{$g['vardb_path']}/captiveportal.db"))
{
	$captiveportallck = lock('captiveportaldb');
	$cpcontents = file("{$g['vardb_path']}/captiveportal.db", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	unlock($captiveportallck);
}

if($connection)
{
	if (($_GET['act'] == 'new') && is_mac($_GET['mac']))
	{
		$macFound['mac'] = $_GET['mac'];
	}

	if (($_GET['act'] == 'edit') && is_mac($_GET['mac']))
	{
		$mac_addr = $_GET['mac'];

		$getInfo = $pdo->prepare("
			SELECT mac_addr AS mac, description
			FROM blocklist
			WHERE mac_addr = :mac
		");

		$getInfo->bindParam(':mac', $mac_addr);
		$getInfo->execute();
		$macFound = $getInfo->fetch(PDO::FETCH_ASSOC);

		if(!$macFound)
		{
			$input_errors[] = "Unable to find MAC Address '$mac_addr'.";
		}
	}

	if($_POST)
	{
		unset($input_errors);

		$macError = false;
		$currentmac = $_POST['currentmac'];

		$mac_addr = $_POST['mac_addr'];
		$description = htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8');

		/* Check user whether if logged in captiveportal */
		if($cpcontents)
		{
			foreach ($cpcontents as $cpcontent)
			{
				$cpent = explode(",", $cpcontent);

				$mac_dash = str_replace(':', '-', $cpent[3]);

				if($mac_dash == $mac_addr )
				{
					$usession = $cpent[5];
					$ufound = true;
					break;
				}
			}

			/* Logout the user from captiveportal */
			if($ufound)
				captiveportal_disconnect_client($usession);
		}

		if(!is_mac($mac_addr))
		{
			$input_errors[] = "'$mac_addr' is not a valid MAC Address.";
			$macError = true;
		}

		if(strlen($description) > 60)
		$input_errors[] = 'Description must be shorter than 60 characters.';

		if(!$macError)
		{
			/* Check if MAC exists */
			$checkMac = $pdo->prepare("
				SELECT mac_addr
				FROM blocklist
				WHERE mac_addr = :mac
			");

			$checkMac->bindParam(':mac', $mac_addr);
			$checkMac->execute();
			$macFound = $checkMac->fetch(PDO::FETCH_ASSOC);
		}

		if($macFound && $macFound['mac_addr'] != $currentmac)
		{
			$input_errors[] = "MAC Address '$mac_addr' already blocked.";
		}

		if(!$input_errors)
		{
			/* If editing an user */
			if(!empty($currentmac))
			{
				$updateMac = $pdo->prepare("
					UPDATE blocklist
					SET mac_addr = :mac,
					description = :description
					WHERE mac_addr = :currentmac
				");

				$updateMac->bindParam(':mac', $mac_addr);
				$updateMac->bindParam(':currentmac', $currentmac);
				$updateMac->bindParam(':description', $description);

				$macUpdated = $updateMac->execute();

				if($macUpdated)
				{
					/* Delete from radacct table */
					$delacct = $pdo->prepare("
						DELETE FROM radacct
						WHERE username = :mac
					");

					$delacct->bindParam(':mac', $currentmac);
					$db = $delacct->execute();

					header('Location: hotspot_blocklist.php');
				}
				else
				{
					$input_errors[] = 'Unable to block MAC Address.';
				}
			}
			else
			{
				$createMac = $pdo->prepare("
					INSERT INTO
					blocklist(mac_addr, description)
					VALUES(:mac, :description)
				");

				$createMac->bindParam(':mac', $mac_addr);
				$createMac->bindParam(':description', $description);

				$macCreated = $createMac->execute();

				if($macCreated)
				{
					$savemsg = "'$mac_addr' is blocked.";
				}
				else
				{
					$input_errors[] = "Unable to block '$mac_addr'.";
				}
			}
		}
	}
}
?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php if($input_errors) print_input_errors($input_errors); ?>
<?php if($savemsg) print_info_box($savemsg); ?>
<form action="hotspot_blocklist_edit.php" method="post" name="user_form" id="user_form">
			<table class="tabcont"  cellpadding="0" cellspacing="0">
			<tr>
				<td colspan="2" valign="top" class="listtopic">BLOCK MAC ADDRESS</td>
			</tr>
			<tr>
				<td valign="top" class="vncell">MAC Address</td>
				<td class="vtable">
					<input value="<?=$macFound['mac'];?>" class="span3" name="mac_addr"  type="text" required pattern="([0-9A-Fa-f]{2}[-]){5}([0-9A-Fa-f]{2})"  id="mac_addr" form="user_form" tabindex="1" maxlength="20">
					<br><i>Valid MAC Address format: '01-23-45-67-89-ab'</i>
					<input value="<?=$mac_addr;?>" name="currentmac"  type="hidden" pattern="([0-9A-Fa-f]{2}[-]){5}([0-9A-Fa-f]{2})"  id="mac_addr" form="user_form">
				</td>
			</tr>
			<tr>
				<td valign="top" class="vncell">Description</td>
				<td class="vtable">
					<textarea class="span3" name="description" maxlength="60" id="description" form="user_form" tabindex="2"><?=$macFound['description'];?></textarea>
				</td>
			</tr>
			<tr>
				<td class="vncell"></td>
				<td class="vtable">
					<input class="btn btn-danger" name="button" type="submit" id="button" form="user_form" tabindex="3" value="Block">
					<a tabindex="4" href="hotspot_blocklist.php" class="btn btn-link">Blocked MAC Addresses</a>
				</td>
			</tr>
		</table>
</form>
</div>
</body>
</html>
