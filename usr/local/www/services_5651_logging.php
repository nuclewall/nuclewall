<?php
/*
	services_5651_logging.php
	
	Copyright (C) 2013-2015 Ogün AÇIK
	All rights reserved.
*/

require('guiconfig.inc');
require('5651_logger.inc');

$pgtitle = array('SERVİSLER', ' 5651 SAYILI YASAYA GÖRE KAYIT TUTMA SERVİSİ');

if (!is_array($config['digitalsign']))
{
	$config['digitalsign'] = array();
}

$pconfig['enable'] = isset($config['digitalsign']['enable']);
$pconfig['sign_type'] = $config['digitalsign']['sign_type'];
$pconfig['sign_time'] = $config['digitalsign']['sign_time'];
$pconfig['sign_hour'] = $config['digitalsign']['sign_hour'];

$pconfig['smbhostname'] = base64_decode($config['digitalsign']['smbhostname']);
$pconfig['smbusername'] = base64_decode($config['digitalsign']['smbusername']);
$pconfig['smbpassword'] = base64_decode($config['digitalsign']['smbpassword']);
$pconfig['smbfolder'] = base64_decode($config['digitalsign']['smbfolder']);

if ($_POST)
{
    unset($input_errors);
	$pconfig = $_POST;
	
	if(!empty($_POST['smbhostname']) && !is_hostname($_POST['smbhostname']))
	{
		$input_errors[] = 'Geçerli bir sunucu adı girmelisiniz.';
	}
	
		if(!empty($_POST['sign_hour']) && !check_hour($_POST['sign_hour']))
	{
		$input_errors[] = 'Geçerli bir saat girmelisiniz. HH:MM formatında bir saat girin.';
	}
	
	if($_POST['sign_type'] == 'remote')
	{
		$reqdfields = split(" ", "smbhostname smbusername smbpassword smbfolder");
		$reqdfieldsn = array("Sunucu Adı", "Kullanıcı Adı", "Parola", "Klasör Adı");
		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	}
	
	if(strlen($_POST['smbusername']) > 128)
		$input_errors[] = 'Kullanıcı adı 128 karakteri geçmemelidir.';
	if(strlen($_POST['smbpassword']) > 128)
		$input_errors[] = 'Parola 128 karakteri geçmemelidir.';
	if(strlen($_POST['smbfolder']) > 128)
		$input_errors[] = 'Klasör Adı 128 karakteri geçmemelidir.';
	
	if (!$input_errors)
	{
		$config['digitalsign']['enable'] = $_POST['enable'] ? true : false;
		$config['digitalsign']['sign_type'] = $_POST['sign_type'];
		$config['digitalsign']['sign_time'] = $_POST['sign_time'];
		
		if(!empty($_POST['sign_hour']))
			$hour = $_POST['sign_hour'];
		else
			$hour = '12:30';
		
		$config['digitalsign']['sign_hour'] = $hour;
		
		$config['digitalsign']['smbhostname'] = base64_encode($_POST['smbhostname']);
		$config['digitalsign']['smbusername'] = base64_encode($_POST['smbusername']);
		$config['digitalsign']['smbpassword'] = base64_encode($_POST['smbpassword']);
		$config['digitalsign']['smbfolder'] = base64_encode($_POST['smbfolder']);
		
		write_config();
		
		if(isset($_POST['enable']))
		{
			install_cron_job('/usr/local/bin/dhcp_logger', true, '0', '*/2', '*', '*', '*', 'root');
			
			if($_POST['sign_type'] == 'local')
			{
				if($_POST['sign_time'] == 'customhour')
				{
					if(!empty($_POST['sign_hour']))
						$hour = $_POST['sign_hour'];
					else
						$hour = '12:30';
					
					$t = explode(':', $hour);
					
					install_cron_job('/usr/local/bin/log_signer', true,  $t[1], $t[0], '*', '*', '*', 'root');
				}
				
				else if($_POST['sign_time'] == 'onehour')
				{					
					install_cron_job('/usr/local/bin/log_signer', true,  '0', '*', '*', '*', '*', 'root');
				}
				
				install_cron_job('/usr/local/bin/log_sender', false,  '*', '*', '*', '*', '*', 'root');
			}
			
			else if($_POST['sign_type'] == 'remote')
			{
				install_cron_job('/usr/local/bin/log_signer', false,  '*', '*', '*', '*', '*', 'root');
				
				smbfileInit($_POST['smbhostname'], $_POST['smbusername'], $_POST['smbpassword'], $_POST['smbfolder']);
				
				install_cron_job('/usr/local/bin/log_sender', true,  '0', '*/4', '*', '*', '*', 'root');
			}
		}
		
		else
		{
			install_cron_job('/usr/local/bin/dhcp_logger', false, '*', '*', '*', '*', '*', 'root');
			install_cron_job('/usr/local/bin/log_signer', false,  '*', '*', '*', '*', '*', 'root');
			install_cron_job('/usr/local/bin/log_sender', false,  '*', '*', '*', '*', '*', 'root');
		}

		$savemsg = 'Ayarlar başarıyla kaydedildi.';
	}
}

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>
 
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>

<form action='services_5651_logging.php' method='post' enctype='multipart/form-data' name='iform' id='iform'>
<table cellpadding='0' cellspacing='0'>
	<tr>
		<td class='tabnavtbl'>
			<?php
				$tab_array = array();
				$tab_array[] = array('Genel Ayarlar', true, 'services_5651_logging.php');
				$tab_array[] = array('İmzalanmış Dosyalar', false, 'services_5651_signeds.php');
				display_top_tabs($tab_array, true);
			?>
		</td>
	</tr>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td valign="top" class="vncell">Aktif</td>
					<td class="vtable">
						<label>
						<input name="enable" type="checkbox" value="yes" <?php if ($pconfig['enable']) echo "checked"; ?>>
						Servisi aktifleştirmek için işaretleyin.
						</label>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">İmzalama Yöntemi</td>
					<td class="vtable">
						<div class="form-inline">
							<div class="controls-row">
								<label class="radio inline">
									<input <?php if($pconfig['sign_type'] == 'local') echo 'checked'; ?> name="sign_type" type="radio" value="local"/>
									Yerel (OpenSSL ile)
								</label>
								<label class="radio inline">
									<input <?php if($pconfig['sign_type'] == 'remote') echo 'checked'; ?> name="sign_type" type="radio" value="remote"/>
									Windows bağlantısı (IP Log İmzalayıcı ile)
								</label>
							</div>
						</div>
						<p>
							<b>NOT: </b>Yerel imzalama seçeneği tamamen deneysel olup Bilgi Teknolojileri ve İletişim Kurumu tarafından bildirilen bir imzalama
							seçeneği değildir.
						</p>
						<p>
							Bilgi Teknolojileri ve İletişim Kurumu'ndan bildirildiği üzere, kayıtların
							<a class="btn-link" target="_blank" href="https://www.btk.gov.tr/ip-log-imzalayici">IP Log İmzalayıcı</a>
							tarafından imzalanması önerilir.
						</p>
					</td>
				</tr>
				<tbody id="local_sign">
				<tr>
					<td colspan="2" class="listtopic">YEREL İMZALAMA SEÇENEKLERİ</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">İmzalama Sıklığı</td>
					<td class="vtable">
						<div>
							<div class="controls-row">
								<label class="radio">
									<input <?php if($pconfig['sign_time'] == 'customhour') echo 'checked'; ?> name="sign_time" type="radio" value="customhour"/>
									<input value="<?php if($pconfig['sign_time'] == 'customhour') echo $pconfig['sign_hour']; ?>" name="sign_hour" type="time" id="sign_hour" style="width:100px;">
									Belirtilen Saatte
									<p>
									'21:45' formatında bir saat girin.<br>
									<b>NOT: </b>Boş bırakıldığında varsayılan değer 12:30'tur.
									</p>
								</label>
								<label class="radio">
									<input <?php if($pconfig['sign_time'] == 'onehour') echo 'checked'; ?> name="sign_time" type="radio" value="onehour"/>
									1 Saat Aralıklarla
								</label>
							</div>
						</div>
					</td>
				</tr>
				</tbody>
				<tbody id="smbclient_form">
				<tr>
					<td colspan="2" class="listtopic">WINDOWS BAĞLANTI AYARLARI</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Sunucu Adı</td>
					<td class="vtable">
						<input value="<?=$pconfig['smbhostname'];?>" name="smbhostname" type="text" id="smbhostname" tabindex="1" maxlength="40"><br>
						Kayıtların gönderileceği Windows kurulu bilgisayarın adını veya IP adresini girin.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Kullanıcı Adı</td>
					<td class="vtable">
						<input value="<?=$pconfig['smbusername'];?>" name="smbusername" type="text" id="smbusername" tabindex="3" maxlength="128"><br>
						Veritabanı kullanıcı adını girin.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Parola</td>
					<td class="vtable">
						<input value="<?=$pconfig['smbpassword'];?>" name="smbpassword" type="password" id="smbpassword" tabindex="4" maxlength="128"><br>
						Veritabanı parolasını girin.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Klasör Adı</td>
					<td class="vtable">
						<input value="<?=$pconfig['smbfolder'];?>" name="smbfolder" type="text" id="smbfolder" tabindex="5" maxlength="128"><br>
						Windows bilgisayardaki paylaşım klasörünün adını girin.
					</td>
				</tr>
				</tbody>
				<tr>
					<td class="vncell"></td>
					<td class="vtable">
						<input name="Submit" type="submit" class="btn btn-inverse" value="Kaydet">
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>
</div>
<script>	
	function togglesCustomPages()
	{
		var page = jQuery("input[name='sign_type']:checked").val();
		
		if(page == "local")
		{
			jQuery("#local_sign").show();
			jQuery("#smbclient_form").hide();
		}
		
		else if(page == "remote")
		{
			jQuery("#local_sign").hide();
			jQuery("#smbclient_form").show();
		}
	}

	jQuery(document).ready(function()
	{
		togglesCustomPages();
	});
		
	jQuery("input[name='sign_type']").on('change', function()
	{
		togglesCustomPages();
	});
</script>
</body>
</html>
