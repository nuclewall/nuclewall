<?php
/*
	services_5651_logging.php

	Copyright (C) 2013-2020 Ogün Açık
	All rights reserved.
*/

require('config.inc');
require('guiconfig.inc');
require('5651_logger.inc');

$pgtitle = array('SERVICES', ' 5651 LAW LOGGING');

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
		$input_errors[] = "Hostname must be a valid hostname.";
	}

		if(!empty($_POST['sign_hour']) && !check_hour($_POST['sign_hour']))
	{
		$input_errors[] = "Time must be in HH:MM format.";
	}

	if($_POST['sign_type'] == 'remote')
	{
		$reqdfields = split(" ", "smbhostname smbusername smbpassword smbfolder");
		$reqdfieldsn = array("Hostname", "Username", "Password", "Share Folder");
		do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);
	}

	if(strlen($_POST['smbusername']) > 128)
		$input_errors[] = "Username is longer than 128 characters.";
	if(strlen($_POST['smbpassword']) > 128)
		$input_errors[] = "Password is longer than 128 characters.";
	if(strlen($_POST['smbfolder']) > 128)
		$input_errors[] = "Share Folder is longer than 128 characters.";

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
			install_cron_job('/usr/local/bin/dhcp_logger', true, '58', '*', '*', '*', '*', 'root');

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
					install_cron_job('/usr/local/bin/log_signer', true,  '59', '*', '*', '*', '*', 'root');
				}

				install_cron_job('/usr/local/bin/log_sender', false,  '*', '*', '*', '*', '*', 'root');
			}

			else if($_POST['sign_type'] == 'remote')
			{
				install_cron_job('/usr/local/bin/log_signer', false,  '*', '*', '*', '*', '*', 'root');

				smbfileInit($_POST['smbhostname'], $_POST['smbusername'], $_POST['smbpassword'], $_POST['smbfolder']);

				install_cron_job('/usr/local/bin/log_sender', true,  '*/30', '*', '*', '*', '*', 'root');
			}
		}

		else
		{
			install_cron_job('/usr/local/bin/dhcp_logger', false, '*', '*', '*', '*', '*', 'root');
			install_cron_job('/usr/local/bin/log_signer', false,  '*', '*', '*', '*', '*', 'root');
			install_cron_job('/usr/local/bin/log_sender', false,  '*', '*', '*', '*', '*', 'root');
		}

		$savemsg = 'Saved successfully.';
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
				$tab_array[] = array('General Settings', true, 'services_5651_logging.php');
				$tab_array[] = array('Signed Files', false, 'services_5651_signeds.php');
				$tab_array[] = array('Logs', false, 'diag_logs_timestamp.php');
				display_top_tabs($tab_array, true);
			?>
		</td>
	</tr>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td valign="top" class="vncell">Enabled</td>
					<td class="vtable">
						<label>
						<input name="enable" type="checkbox" value="yes" <?php if ($pconfig['enable']) echo "checked"; ?>>
						Enable service
						</label>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Signing Option</td>
					<td class="vtable">
						<div class="form-inline">
							<div class="controls-row">
								<label class="radio inline">
									<input <?php if($pconfig['sign_type'] == 'local') echo 'checked'; ?> name="sign_type" type="radio" value="local"/>
									Local (via OpenSSL)
								</label>
								<label class="radio inline">
									<input <?php if($pconfig['sign_type'] == 'remote') echo 'checked'; ?> name="sign_type" type="radio" value="remote"/>
									Windows connection (via IP Log Signer)
								</label>
							</div>
						</div>
						<p>
							<b>WARNING: </b>Local signing is completely experimental.
						</p>
						<p>
							According to 5651 law, logs must be signed by
							<a class="btn-link" target="_blank" href="https://www.btk.gov.tr/ip-log-imzalayici">IP Log Signer</a>
						</p>
					</td>
				</tr>
				<tbody id="local_sign">
				<tr>
					<td colspan="2" class="listtopic">LOCAL SIGNING SETTINGS</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Signing Frequency</td>
					<td class="vtable">
						<div>
							<div class="controls-row">
								<label class="radio">
									<input <?php if($pconfig['sign_time'] == 'customhour') echo 'checked'; ?> name="sign_time" type="radio" value="customhour"/>
									<input value="<?php if($pconfig['sign_time'] == 'customhour') echo $pconfig['sign_hour']; ?>" name="sign_hour" type="time" id="sign_hour" style="width:100px;">
									At a specific time
									<p>
									Enter a time in '21:45' format.<br>
									<b>NOTE: </b>Default time is 12:30.
									</p>
								</label>
								<label class="radio">
									<input <?php if($pconfig['sign_time'] == 'onehour') echo 'checked'; ?> name="sign_time" type="radio" value="onehour"/>
									Every 1 hour
								</label>
							</div>
						</div>
					</td>
				</tr>
				</tbody>
				<tbody id="smbclient_form">
				<tr>
					<td colspan="2" class="listtopic">WINDOWS CONNECTION SETTINGS</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Connection Status</td>
					<td id="connstatus" class="vtable">
					<span class="label">Checking...</span>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Windows Server</td>
					<td class="vtable">
						<input value="<?=$pconfig['smbhostname'];?>" name="smbhostname" type="text" id="smbhostname" tabindex="1" maxlength="40"><br>
						Windows server NetBIOS name or IP address which logs are sent to.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Username</td>
					<td class="vtable">
						<input value="<?=$pconfig['smbusername'];?>" name="smbusername" type="text" id="smbusername" tabindex="3" maxlength="128"><br>
						Windows username. Ensure shared folder is permitted to this user.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Password</td>
					<td class="vtable">
						<input value="<?=$pconfig['smbpassword'];?>" name="smbpassword" type="password" id="smbpassword" tabindex="4" maxlength="128"><br>
						Windows user password
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Share Folder</td>
					<td class="vtable">
						<input value="<?=$pconfig['smbfolder'];?>" name="smbfolder" type="text" id="smbfolder" tabindex="5" maxlength="128"><br>
						Enter shared folder name on Windows server
					</td>
				</tr>
				</tbody>
				<tr>
					<td class="vncell"></td>
					<td class="vtable">
						<input name="Submit" type="submit" class="btn btn-inverse" value="Save">
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

	/* Gather samba connection status */
    jQuery( document ).ready(function() {

	jQuery.ajax({
	method: "POST",
	url: "ajax_handlers/samba_status.php",
	data: { act: "getstatus"}
	    }).done(function( msg )
	    {
		  jQuery("#connstatus").html(msg);
	    });
    });
</script>
</body>
</html>
