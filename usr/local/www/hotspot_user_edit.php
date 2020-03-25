<?php
/*
	hotspot_user_edit.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.
*/

require('guiconfig.inc');
require('captiveportal.inc');
require('local_connection.inc');

$pgtitle = array('HOTSPOT ', 'KULLANICI DÜZENLE');

/* Get active captiveportal sessions */
if (file_exists("{$g['vardb_path']}/captiveportal.db"))
{
	$captiveportallck = lock('captiveportaldb');
	$cpcontents = file("{$g['vardb_path']}/captiveportal.db", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	unlock($captiveportallck);
}

if($connection)
{
	if (($_GET['act'] == 'edit') && (isset($_GET['uname'])) && (strlen($_GET['uname']) <= 12))
	{
		$username = $_GET['uname'];

		$getInfo = $pdo->prepare("
			SELECT username, value, description
			FROM radcheck
			WHERE username = :username AND attribute = 'Password'
		");

		$getInfo->bindParam(':username', $username);
		$getInfo->execute();
		$user = $getInfo->fetch(PDO::FETCH_ASSOC);

		if($user)
		{
			/* Get user's group count */
			$getCount = $pdo->prepare("
				SELECT value
				FROM radcheck
				WHERE username = :username AND attribute = 'Simultaneous-Use'
			");

			$getCount->bindParam(':username', $username);
			$getCount->execute();
			$count = $getCount->fetch(PDO::FETCH_ASSOC);
		}
		else
		{
			$input_errors[] = "'$username' kullanıcısı bulunamadı.";
		}
	}

	if($_POST)
	{
		$user = $_POST;
		unset($input_errors);

		$unameError = false;
		$currentuser = $_POST['currentuser'];

		$username = $_POST['username'];
		$usercount = $_POST['usercount'];
		$usercount_num = intval($_POST['usercount']);
		$password = $_POST['password'];
		$password_again = $_POST['password_again'];
		$description = htmlspecialchars($_POST['description'], ENT_QUOTES, 'UTF-8');

		if (empty($currentuser) or $_GET['act'] == 'new')
		{
			$reqdfields = explode(" ", "username password password_again usercount");
			$reqdfieldsn = array('Kullanıcı Adı', 'Parola', 'Parola Tekrarı', 'Kullanıcı Sayısı');
		}
		else
		{
			$reqdfields = explode(" ", "username", "usercount");
			$reqdfieldsn = array('Kullanıcı Adı', 'Kullanıcı Sayısı');
		}

		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

		if(preg_match('/[^a-zA-Z0-9_.-]/', $username))
		{
			$input_errors[] = "Kullanıcı adı sadece 'a-z', 'A-Z', '0-9', '_', '.', '-' karakterlerinden oluşabilir.";
			$unameError = true;
		}

		if(strlen($username) > 12 || strlen($username) < 3)
		{
			$input_errors[] = "Kullanıcı adı uzunluğu 3-12 karakter arasında olmalıdır.";
			$unameError = true;
		}

		if($usercount_num > 500 || $usercount_num < 1)
			$input_errors[] = "Grup sayısı 1-500 aralığında bir değer olmalıdır.";

		if(!empty($password) and !empty($password_again))
		{
			if(preg_match('/[^a-zA-Z0-9_.-@<>!]/', $password))
				$input_errors[] = "Parola 'a-z', 'A-Z', '0-9', '_', '.', '-', '<', '>', '@', '!' karakterlerinden oluşabilir.";

			if(strlen($password) > 15 || strlen($password) < 6)
				$input_errors[] = 'Parola uzunluğu 6-15 karakter arasında olmalıdır.';

			if($password != $password_again)
				$input_errors[] = 'Parolalar uyuşmuyor.';
		}

		if(strlen($description) > 60)
			$input_errors[] = 'Açıklama uzunluğu 60 karakteri geçmemelidir.';

		if(!$unameError)
		{
			/* Check if user exists */
			$checkUser = $pdo->prepare("
				SELECT username
				FROM radcheck
				WHERE username = :username AND attribute = 'Password'
			");

			$checkUser->bindParam(':username', $username);
			$checkUser->execute();
			$userFound = $checkUser->fetch(PDO::FETCH_ASSOC);
		}

		if($userFound && $userFound['username'] != $currentuser)
		{
			$input_errors[] = "'$username' adında bir kullanıcı zaten var.";
		}

		if(!$input_errors)
		{
			/* If editing an user */
			if(!empty($currentuser))
			{
				if(!empty($password) and !empty($password_again))
				{
					$updateUser = $pdo->prepare("
						UPDATE radcheck
						SET username = :username,
						value = :password,
						description = :description
						WHERE username = :currentuser AND attribute = 'Password'
					");

					$updateUser->bindParam(':username', $username);
					$updateUser->bindParam(':currentuser', $currentuser);
					$updateUser->bindParam(':password', $password);
					$updateUser->bindParam(':description', $description);
				}
				else
				{
					$updateUser = $pdo->prepare("
						UPDATE radcheck
						SET username = :username,
						description = :description
						WHERE username = :currentuser AND attribute = 'Password'
					");

					$updateUser->bindParam(':username', $username);
					$updateUser->bindParam(':currentuser', $currentuser);
					$updateUser->bindParam(':description', $description);
				}

				$updateCount = $pdo->prepare("
					UPDATE radcheck
					SET username = :username,
					value = :usercount
					WHERE username = :currentuser AND attribute = 'Simultaneous-Use'
				");

				$updateCount->bindParam(':username', $username);
				$updateCount->bindParam(':usercount', $usercount);
				$updateCount->bindParam(':currentuser', $currentuser);

				$userUpdated = $updateUser->execute();
				$countUpdated = $updateCount->execute();

				if($userUpdated && $countUpdated)
				{
					/* Check user whether if logged in captiveportal */
					if($cpcontents)
					{
						foreach ($cpcontents as $cpcontent)
						{
							$cpent = explode(",", $cpcontent);

							if($cpent[4] == $currentuser)
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
						WHERE username = :username
					");

					$delacct->bindParam(':username', $currentuser);
					$db = $delacct->execute();

					header('Location: hotspot_users.php');
				}
				else
				{
					$input_errors[] = 'Kullanıcı güncellenemedi.';
				}
			}

			/* Create new user */
			else
			{
				$createUser = $pdo->prepare("
					INSERT INTO
					radcheck(username, attribute, op, value, description)
					VALUES(:username, 'Password', ':=', :password, :description)
				");

				$createUser->bindParam(':username', $username);
				$createUser->bindParam(':password', $password);
				$createUser->bindParam(':description', $description);

				$createUserCount = $pdo->prepare("
					INSERT INTO
					radcheck(username, attribute, op, value)
					VALUES(:username, 'Simultaneous-Use', ':=', :usercount)
				");

				$createUserCount->bindParam(':username', $username);
				$createUserCount->bindParam(':usercount', $usercount);

				$userCreated = $createUser->execute();
				$countCreated = $createUserCount->execute();

				if($userCreated && $countCreated)
				{
					$savemsg = "'$username' kullanıcısı başarıyla oluşturuldu.";
				}
				else
				{
					$input_errors[] = "'$username' kullanıcısı oluşturulamadı.";
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

<form action="hotspot_user_edit.php" method="post" name="user_form" id="user_form">
			<table class="tabcont" cellpadding="0" cellspacing="0">
			<tr>
				<td colspan="2" valign="top" class="listtopic">YEREL KULLANICI DÜZENLE</td>
			</tr>
			<tr>
				<td valign="top" class="vncell">Hesap Adı</td>
				<td class="vtable">
					<input value="<?=$user['username'];?>" class="span3" name="username"  type="text" required pattern="[a-zA-Z0-9_.-]{3,12}"  id="username" form="user_form" tabindex="1" maxlength="12">
					<input value="<?=$_GET['uname'];?>" name="currentuser"  type="hidden" pattern="[a-zA-Z0-9_.-]{3,12}"  id="currentuser">
				</td>
			</tr>
			<tr>
				<td valign="top" class="vncell">Kullanıcı Sayısı</td>
				<td class="vtable">
					<input value="<?php if($count['value']) echo $count['value']; else echo '1';?>" class="span1" name="usercount" type="number"  required id="usercount" form="user_form" max="500" min="1" step="1" tabindex="2">
				</td>
			</tr>
			<tr>
				<td valign="top" class="vncell">Parola</td>
				<td class="vtable">
					<input <?php if($_GET['act'] == 'new') echo 'required';?> name="password" type="password" pattern="[a-zA-Z0-9_.-@<>!]{6,15}" id="password" form="user_form" tabindex="3" maxlength="15">
				</td>
			</tr>
			<tr>
				<td valign="top" class="vncell">Parola Tekrarı</td>
				<td class="vtable">
					<input <?php if($_GET['act'] == 'new') echo 'required';?> name="password_again" type="password" pattern="[a-zA-Z0-9_.-@<>!]{6,20}" id="password_again" form="user_form" tabindex="4" maxlength="20">
				</td>
			</tr>
			<tr>
				<td valign="top" class="vncell">Açıklama</td>
				<td class="vtable">
					<textarea class="span3" name="description" maxlength="60" id="description" form="user_form" tabindex="5"><?=$user['description'];?></textarea>
				</td>
			</tr>
			<tr>
				<td class="vncell"></td>
				<td class="vtable">
					<input class="btn btn-inverse" name="button" type="submit" id="button" form="user_form" tabindex="6" value="Kaydet">
					<a tabindex="7" href="hotspot_users.php" class="btn btn-link">Yerel Kullanıcılar</a>
				</td>
			</tr>
		</table>
</form>
</div>
</body>
</html>
