<?php
/*
	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.
*/

require_once('guiconfig.inc');

$pgtitle = array('SİSTEM ', 'PAROLA DEĞİŞTİR');

if (isset($_POST['save']))
{
	unset($input_errors);

	$reqdfields = explode(" ", "passwordfld1");
	$reqdfieldsn = array('Parola');
	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if ($_POST['passwordfld1'] != $_POST['passwordfld2'])
		$input_errors[] = 'Parolalar eşleşmiyor';

	if (!$input_errors)
	{
		$config['system']['user'][$userindex[$HTTP_SERVER_VARS['AUTH_USER']]]['password'] = crypt($_POST['passwordfld1']);
		local_user_set($config['system']['user'][$userindex[$HTTP_SERVER_VARS['AUTH_USER']]]);

		write_config();

		$savemsg = 'Parolanız başarıyla değiştirildi';
	}
}

if (!session_id())
	session_start();

$islocal = false;
foreach($config['system']['user'] as $user)
	if($user['name'] == $_SESSION['Username'])
		$islocal = true;
session_commit();

?>


<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php
if ($input_errors)
	print_input_errors($input_errors);
if ($savemsg)
	print_info_box($savemsg);

if ($islocal == false)
{
	echo 'Başka bir kullanıcının parolasını değiştiremezsiniz';
	exit;
}
?>
<table cellpadding='0' cellspacing='0'>
	<tr>
		<td>
			<form action="system_usermanager_passwordmg.php" method="post" name="iform" id="iform">
				<table class="tabcont" cellpadding="0" cellspacing="0">
					<tr>
						<td colspan="2" valign="top" class="listtopic"><?=$HTTP_SERVER_VARS['AUTH_USER']?> kullanıcısının parolasını değiştir</td>
					</tr>
					<tr>
						<td valign="top" class="vncell">Yeni Parola</td>
						<td class="vtable">
								<input name="passwordfld1" type="password" id="passwordfld1" size="20" />
						</td>
					</tr>
					<tr>
						<td valign="top" class="vncell">Parola Tekrarı</td>
						<td class="vtable">
							<input name="passwordfld2" type="password" id="passwordfld2" size="20" />
						</td>
					</tr>
					<tr>
						<td class="vncell"></td>
						<td class="vtable">
							<input name="save" type="submit" class="btn btn-inverse" value="Kaydet" />
						</td>
					</tr>
				</table>
			</form>
		</td>
	</tr>
</table>
</div>
</body>
</html>
