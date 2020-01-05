<?php
/*
	hotspot_users.php

	Copyright (C) 2013-2015 Ogün AÇIK
	All rights reserved.
*/

require('guiconfig.inc');
require('captiveportal.inc');
require('local_connection.inc');

$pgtitle = array('HOTSPOT ', 'YEREL KULLANICILAR');

/* Get active captiveportal sessions */
if (file_exists("{$g['vardb_path']}/captiveportal.db"))
{
	$captiveportallck = lock('captiveportaldb');
	$cpcontents = file("{$g['vardb_path']}/captiveportal.db", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	unlock($captiveportallck);
}

if ($connection)
{
	if (($_GET['act'] == 'del') && (isset($_GET['uname'])) && (strlen($_GET['uname']) <= 12))
	{
		$username = $_GET['uname'];

		$findUser = $pdo->prepare("
			SELECT username FROM radcheck
			WHERE username = :username
		");

		$findUser->bindParam(':username', $username);
		$findUser->execute();
		$userExists = $findUser->fetch(PDO::FETCH_ASSOC);

		if($userExists)
		{
			/* Delete from radcheck table */
			$deluser = $pdo->prepare("
				DELETE FROM radcheck
				WHERE username = :username
			");

			$deluser->bindParam(':username', $username);
			$deluser->execute();

			/* Check user whether if logged in captiveportal */
			if(isset($cpcontents))
			{
				foreach ($cpcontents as $cpcontent)
				{
					$cpent = explode(",", $cpcontent);

					if($cpent[4] == $username)
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

			$delacct->bindParam(':username', $username);
			$delacct->execute();

			$savemsg = "'$username' kullanıcısı silindi.";
		}
		else
		{
			$input_errors[] = "'$username' kullanıcısı bulunamadı.";
		}
	}

	/* Get user list */
	$statement = $pdo->prepare("
		SELECT username, description,
		DATE_FORMAT(registration,'%d-%m-%Y %H:%i:%s') AS date,
		GROUP_CONCAT(value) AS contents,
		GROUP_CONCAT(attribute) AS options
		FROM radcheck
		WHERE attribute <> 'Auth-Type ' AND value <> 'Accept'
		GROUP BY username
	");
	$statement->execute();
}

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>

<table cellpadding="0" cellspacing="0">
	<tr>
		<td class="tabnavtbl">
			<?php
				$tab_array = array();
				$tab_array[] = array('Aktif Oturumlar', false, 'hotspot_status.php');
				$tab_array[] = array('Yerel Kullanıcılar', true, 'hotspot_users.php');
				$tab_array[] = array('Özel İzinli MAC Adresleri', false, 'hotspot_macs.php');
				$tab_array[] = array('Engellenmiş MAC Adresleri', false, 'hotspot_blocklist.php');
				$tab_array[] = array('Oturum Hareketleri', false, 'hotspot_logs.php');
				display_top_tabs($tab_array, true);
			?>
		</td>
	</tr>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<?php if ($connection): ?>
							<div style="margin-right: 10px;" class="pull-left">
								<a class="btn" href="hotspot_user_edit.php?act=new"><i class="icon-user"></i>Yeni</a>
							</div>
							<div class="controls">
								<div class="input-prepend">
								  <span class="add-on"><i class="icon-search"></i></span>
								  <input id="search" placeholder="Kullanıcı ara..." class="input-medium" style="height:20px" type="text">
								</div>
							</div>
						<table class="grids sortable">
							<tr>
								<td class="head users">Hesap Adı</td>
								<td class="head users">Son Değişiklik</td>
								<td class="head users">Kullanıcı Türü</td>
								<td class="head users">Açıklama</td>
								<td class="head users"></td>
							</tr>
								<?php while (($result = $statement->fetch(PDO::FETCH_ASSOC)) !== false): ?>
								<?php list($password, $simulaneous_use) = split(',', $result['contents']); ?>
							<tr>
								<td id="usr" class="cell users"><a href="hotspot_user_edit.php?act=edit&uname=<?=$result['username'];?>" class="btn-link"><?=$result['username'];?></a></td>
								<td class="cell date"><?=$result['date'];?></td>
								<td class="cell count">
									<?php
										if($simulaneous_use > 1)
											echo "<span class='badge label-info'>" . "Grup ($simulaneous_use)" . "</span>";
										else if($simulaneous_use == 1)
											echo "<span class='badge'>" . "Tekil" . "</span>";
								        else
											echo "Belirtilmedi";
									?>
								</td>
								<td class="cell description"><?=$result['description'];?></td>
								<td class="cell tools">
									<a title="Düzenle" href="hotspot_user_edit.php?act=edit&uname=<?=$result['username'];?>">
										<i class="icon-edit"></i>
									</a>
									<a title="Sil" href="hotspot_users.php?act=del&uname=<?=$result['username'];?>" onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?.')">
										<i class="icon-trash"></i>
									</a>
								</td>
							</tr>
								<?php endwhile; ?>
						</table>
						<?php endif; ?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</div>
<script type="text/javascript">

jQuery("#search").on("keyup", function() {
    var value = jQuery(this).val();

    jQuery("table.grids tr").each(function(index) {
        if (index !== 0) {

            $row = jQuery(this);
            var id = $row.find("td#usr:first").text();

            if (id.indexOf(value) !== 0) {
                $row.hide();
            }
            else {
                $row.show();
            }
        }
    });
});

jQuery(document).keyup(function(e) {
	if (e.keyCode == 27)
	{
		jQuery("table.grids tr").each(function(index){
		$row = jQuery(this);
		jQuery("#search").val("");
		$row.show();});
	}
});
</script>
</body>
</html>
