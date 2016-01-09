<?php
/*
	hotspot_mac_edit.php
	
	Copyright (C) 2013-2015 Ogün AÇIK
	All rights reserved.
*/

require('guiconfig.inc');
require('captiveportal.inc');
require('local_connection.inc');

$pgtitle = array('HOTSPOT ', 'ÖZEL İZİNLİ MAC ADRESİ DÜZENLE');

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
			SELECT username AS mac, value, description
			FROM radcheck
			WHERE username = :mac AND attribute = 'Auth-Type'
		");
		
		$getInfo->bindParam(':mac', $mac_addr);
		$getInfo->execute();
		$macFound = $getInfo->fetch(PDO::FETCH_ASSOC);
			
		if(!$macFound)
		{
			$input_errors[] = "'$mac_addr' MAC adresi bulunamadı.";
		}
	}

	if($_POST)
	{
		unset($input_errors);
		
		$macError = false;
		$currentmac = $_POST['currentmac'];
		
		$mac_addr = $_POST['mac_addr'];
		$description = htmlspecialchars($_POST['description']);
				
		if(!is_mac($mac_addr)) 
		{
			$input_errors[] = "'$mac_addr' geçerli bir MAC adresi değil.";
			$macError = true;
		}

		if(strlen($description) > 60)
			$input_errors[] = 'Açıklama uzunluğu 60 karakteri geçmemelidir.';

		if(!$macError)
		{
			/* Check if MAC exists */
			$checkMac = $pdo->prepare("
				SELECT username
				FROM radcheck
				WHERE username = :mac AND attribute = 'Auth-Type'
			");

			$checkMac->bindParam(':mac', $mac_addr);
			$checkMac->execute();
			$macFound = $checkMac->fetch(PDO::FETCH_ASSOC);
		}

		if($macFound && $macFound['username'] != $currentmac)
		{
			$input_errors[] = "'$mac_addr' MAC adresi zaten kayıtlı.";
		}

		if(!$input_errors)
		{
			/* If editing an user */
			if(!empty($currentmac))
			{				
				$updateMac = $pdo->prepare("
					UPDATE radcheck
					SET username = :mac,
					description = :description
					WHERE username = :currentmac AND attribute = 'Auth-Type'
				");

				$updateMac->bindParam(':mac', $mac_addr);
				$updateMac->bindParam(':currentmac', $currentmac);
				$updateMac->bindParam(':description', $description);

				$macUpdated = $updateMac->execute();

				if($macUpdated)
				{
					/* Check user whether if logged in captiveportal */
					if($cpcontents)
					{
						foreach ($cpcontents as $cpcontent)
						{
							$cpent = explode(",", $cpcontent);
							
							if($cpent[4] == $currentmac)
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

					/* Delete from radacct table */
					$delacct = $pdo->prepare("
						DELETE FROM radacct
						WHERE username = :mac
					");

					$delacct->bindParam(':mac', $currentmac);
					$db = $delacct->execute();

					header('Location: hotspot_macs.php');
				}
				else
				{
					$input_errors[] = 'MAC adresi güncellenemedi.';
				}
			}

			/* Create new MAC address */
			else
			{
				$createMac = $pdo->prepare("
					INSERT INTO 
					radcheck(username, attribute, op, value, description)
					VALUES(:mac, 'Auth-Type', ':=', 'Accept', :description)
				");
				
				$createMac->bindParam(':mac', $mac_addr);
				$createMac->bindParam(':description', $description);
				
				$macCreated = $createMac->execute();
				
				if($macCreated)
				{
					$savemsg = "'$mac_addr' MAC adresi izinli geçiş listesine eklendi.";
				}
				else 
				{
					$input_errors[] = "'$mac_addr' MAC adresi izinli geçiş listesine eklenemedi.";
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
<form action="hotspot_mac_edit.php" method="post" name="user_form" id="user_form">
			<table class="tabcont"  cellpadding="0" cellspacing="0">
			<tr>
				<td colspan="2" valign="top" class="listtopic">MAC ADRESİ DÜZENLE</td>
			</tr>
			<tr>
				<td valign="top" class="vncell">MAC Adresi</td>
				<td class="vtable">
					<input value="<?=$macFound['mac'];?>" class="span3" name="mac_addr"  type="text" required pattern="([0-9A-Fa-f]{2}[-]){5}([0-9A-Fa-f]{2})"  id="mac_addr" form="user_form" tabindex="1" maxlength="20">
					<br><i>MAC adresi '01-23-45-67-89-ab' formatında olmalıdır.</i>
					<input value="<?=$mac_addr;?>" name="currentmac"  type="hidden" pattern="([0-9A-Fa-f]{2}[-]){5}([0-9A-Fa-f]{2})"  id="mac_addr" form="user_form">
				</td>
			</tr>
			<tr>
				<td valign="top" class="vncell">Açıklama</td>
				<td class="vtable">
					<textarea class="span3" name="description" maxlength="60" id="description" form="user_form" tabindex="2"><?=$macFound['description'];?></textarea>
				</td>
			</tr>
			<tr>
				<td class="vncell"></td>
				<td class="vtable">
					<input class="btn btn-success" name="button" type="submit" id="button" form="user_form" tabindex="3" value="İzin Ver">
					<a tabindex="4" href="hotspot_macs.php" class="btn btn-link">Özel İzinli MAC Adresleri</a>
				</td>
			</tr>
		</table>
</form>
</div>
</body>
</html>
