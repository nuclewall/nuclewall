<?php
/*
	system_advanced_admin.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.

	Copyright (C) 2005-2010 Scott Ullrich
	Copyright (C) 2008 Shrew Soft Inc

	originally part of m0n0wall (http://m0n0.ch/wall)
	Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>.
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
require_once('functions.inc');
require_once('filter.inc');
require_once('shaper.inc');

$pconfig['webguiproto'] = $config['system']['webgui']['protocol'];
$pconfig['webguiport'] = $config['system']['webgui']['port'];
$pconfig['max_procs'] = ($config['system']['webgui']['max_procs']) ? $config['system']['webgui']['max_procs'] : 2;
$pconfig['ssl-certref'] = $config['system']['webgui']['ssl-certref'];
$pconfig['noantilockout'] = isset($config['system']['webgui']['noantilockout']);
$pconfig['nodnsrebindcheck'] = isset($config['system']['webgui']['nodnsrebindcheck']);
$pconfig['nohttpreferercheck'] = isset($config['system']['webgui']['nohttpreferercheck']);
$pconfig['noautocomplete'] = isset($config['system']['webgui']['noautocomplete']);
$pconfig['althostnames'] = $config['system']['webgui']['althostnames'];
$pconfig['enableserial'] = $config['system']['enableserial'];
$pconfig['enablesshd'] = $config['system']['enablesshd'];
$pconfig['sshport'] = $config['system']['ssh']['port'];
$pconfig['sshdkeyonly'] = isset($config['system']['ssh']['sshdkeyonly']);
$pconfig['quietlogin'] = isset($config['system']['webgui']['quietlogin']);

$a_cert =& $config['cert'];

$certs_available = false;
if (is_array($a_cert) && count($a_cert))
  $certs_available = true;

if (!$pconfig['webguiproto'] || !$certs_available)
  $pconfig['webguiproto'] = "http";

if ($_POST) {

  unset($input_errors);
  $pconfig = $_POST;

  if ($_POST['webguiport'])
    if(!is_port($_POST['webguiport']))
      $input_errors[] = 'You must specify a valid port number for Web UI.';

  if ($_POST['max_procs'])
    if(!is_numeric($_POST['max_procs']) || ($_POST['max_procs'] < 1) || ($_POST['max_procs'] > 500))
      $input_errors[] = 'Max Processes must be a number 1 or greater.';

  if ($_POST['sshport'])
    if(!is_port($_POST['sshport']))
      $input_errors[] = 'You must specify a valid port number for SSH.';

  if($_POST['sshdkeyonly'] == "yes")
    $config['system']['ssh']['sshdkeyonly'] = "enabled";
  else if (isset($config['system']['ssh']['sshdkeyonly']))
    unset($config['system']['ssh']['sshdkeyonly']);

  ob_flush();
  flush();

  if (!$input_errors) {

    if (update_if_changed("webgui protocol", $config['system']['webgui']['protocol'], $_POST['webguiproto']))
      $restart_webgui = true;
    if (update_if_changed("webgui port", $config['system']['webgui']['port'], $_POST['webguiport']))
      $restart_webgui = true;
    if (update_if_changed("webgui certificate", $config['system']['webgui']['ssl-certref'], $_POST['ssl-certref']))
      $restart_webgui = true;
    if (update_if_changed("webgui max processes", $config['system']['webgui']['max_procs'], $_POST['max_procs']))
      $restart_webgui = true;

    if ($_POST['quietlogin'] == "yes") {
      $config['system']['webgui']['quietlogin'] = true;
    } else {
      unset($config['system']['webgui']['quietlogin']);
    }

	auto_login();

    if ($_POST['noantilockout'] == "yes")
      $config['system']['webgui']['noantilockout'] = true;
    else
      unset($config['system']['webgui']['noantilockout']);

    if ($_POST['enableserial'] == "yes")
      $config['system']['enableserial'] = true;
    else
      unset($config['system']['enableserial']);

    if ($_POST['nodnsrebindcheck'] == "yes")
      $config['system']['webgui']['nodnsrebindcheck'] = true;
    else
      unset($config['system']['webgui']['nodnsrebindcheck']);

    if ($_POST['nohttpreferercheck'] == "yes")
      $config['system']['webgui']['nohttpreferercheck'] = true;
    else
      unset($config['system']['webgui']['nohttpreferercheck']);

    if ($_POST['noautocomplete'] == "yes")
      $config['system']['webgui']['noautocomplete'] = true;
    else
      unset($config['system']['webgui']['noautocomplete']);

    if ($_POST['althostnames'])
      $config['system']['webgui']['althostnames'] = $_POST['althostnames'];
    else
      unset($config['system']['webgui']['althostnames']);

    $sshd_enabled = $config['system']['enablesshd'];
    if($_POST['enablesshd'])
      $config['system']['enablesshd'] = "enabled";
    else
      unset($config['system']['enablesshd']);

    $sshd_keyonly = isset($config['system']['sshdkeyonly']);
    if ($_POST['sshdkeyonly'])
      $config['system']['sshdkeyonly'] = true;
    else
      unset($config['system']['sshdkeyonly']);

    $sshd_port = $config['system']['ssh']['port'];

	if ($_POST['sshport'])
	{
      $config['system']['ssh']['port'] = $_POST['sshport'];
	  exec("echo {$_POST['sshport']} >/var/run/sshport");
	}

	else if (isset($config['system']['ssh']['port']))
	{
      unset($config['system']['ssh']['port']);
	  exec("echo 22 >/var/run/sshport");
	}

	else
	{
		exec("echo 22 >/var/run/sshport");
	}

	if (($sshd_enabled != $config['system']['enablesshd']) ||
      ($sshd_keyonly != $config['system']['sshdkeyonly']) ||
      ($sshd_port != $config['system']['ssh']['port']))
      $restart_sshd = true;

    if ($restart_webgui)
	{
      global $_SERVER;
      list($host) = explode(":", $_SERVER['HTTP_HOST']);
      $prot = $config['system']['webgui']['protocol'];
      $port = $config['system']['webgui']['port'];
      if ($port)
        $url = "{$prot}://{$host}:{$port}/system_advanced_admin.php";
      else
        $url = "{$prot}://{$host}/system_advanced_admin.php";
    }

    write_config("System access settings changed");

	$retval = filter_configure();
	$savemsg = get_std_save_message($retval);

    setup_serial_port();
    services_dnsmasq_configure();
  }
}

$pgtitle = array('SYSTEM', 'ACCESS SETTINGS');

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<script language="JavaScript">
function prot_change()
{
  if (document.iform.https_proto.checked)
    document.getElementById("ssl_opts").style.display="";
  else
    document.getElementById("ssl_opts").style.display="none";
}
</script>
<?php
  if ($input_errors)
    print_input_errors($input_errors);
  if ($savemsg)
    print_info_box($savemsg);
?>
<form action="system_advanced_admin.php" method="post" name="iform" id="iform">
<table cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td colspan="2" class="listtopic">WEB UI</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Protocol</td>
					<td class="vtable">
						<?php
						if ($pconfig['webguiproto'] == "http")
							$http_chk = "checked";
						if ($pconfig['webguiproto'] == "https")
							$https_chk = "checked";
						if (!$certs_available)
							$https_disabled = "disabled";
						?>
						<div class="form-inline">
							<div class="controls-row">
								<label class="radio inline">
									<input name="webguiproto" id="http_proto" type="radio" value="http" <?=$http_chk;?> onClick="prot_change()">HTTP
								</label>
								<label class="radio inline">
									<input name="webguiproto" id="https_proto" type="radio" value="https" <?=$https_chk;?> <?=$https_disabled;?> onClick="prot_change()">HTTPS
								</label>
							</div>
						</div>
						<?php if (!$certs_available): ?>
						<br>
						No Certificates have been defined. You must
						<a href="system_certmanager.php">Create or Import</a>
						a Certificate before SSL can be enabled
						<?php endif; ?>
					</td>
				</tr>
				<tr id="ssl_opts">
					<td valign="top" class="vncell">SSL Certificate</td>
					<td class="vtable">
						<select name="ssl-certref" id="ssl-certref">
						<?php
						if ($certs_available) {
							foreach($a_cert as $cert):
							$selected = "";
							if ($pconfig['ssl-certref'] == $cert['refid'])
								$selected = "selected";
						?>
						<option value="<?=$cert['refid'];?>" <?=$selected;?>><?=$cert['descr'];?></option>
						<?php endforeach; } ?>
						</select>
					</td>
				</tr>
				<tr>
				<td valign="top" class="vncell">TCP Port</td>
				<td class="vtable">
					<input class="input-small" name="webguiport" type="text" id="webguiport" size="5" value="<?=htmlspecialchars($config['system']['webgui']['port']);?>">
					<br>
					 Enter a custom port number for the Web UI
					 above if you want to override the default (80 for HTTP, 443
					 for HTTPS). Changes will take effect immediately after save.
				</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Max Processes</td>
					<td class="vtable">
						<input class="input-small" name="max_procs" type="text" id="max_procs" size="5" value="<?=htmlspecialchars($pconfig['max_procs']);?>">
						<br>
						Enter the number of webConfigurator processes you
						want to run. This defaults to 2. Increasing this will allow more
						users/browsers to access the GUI concurrently.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Anti-lockout</td>
					<td class="vtable">
						<?php
						if($config['interfaces']['lan'])
							$lockout_interface = "LAN";
						else
							$lockout_interface = "WAN";
						?>
						<input name="noantilockout" type="checkbox" id="noantilockout" value="yes" <?php if ($pconfig['noantilockout']) echo "checked"; ?> />
						<b>Disable Web UI anti-lockout rule</b>
						<br>
						<?php printf("When this is unchecked, access to the webConfigurator " .
								"on the %s interface is always permitted, regardless of the user-defined firewall " .
								"rule set. Check this box to disable this automatically added rule, so access " .
								"to the webConfigurator is controlled by the user-defined firewall rules " .
								"(ensure you have a firewall rule in place that allows you in, or you will " .
								"lock yourself out!)", $lockout_interface); ?>
						<br><em> Hint: the &quot;Set interface(s) IP address&quot; option in the console menu resets this setting as well.</em>
					</td>
				</tr>
				<tr>
					<td colspan="2" class="list"></td>
				</tr>
				<tr>
					<td colspan="2" valign="top" class="listtopic">SECURE SHELL</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Secure Shell Server</td>
					<td class="vtable">
						<input name="enablesshd" type="checkbox" id="enablesshd" value="yes" <?php if (isset($pconfig['enablesshd'])) echo "checked"; ?> />
						<b>Enable Secure Shell</b>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Authentication Method</td>
					<td class="vtable">
						<input name="sshdkeyonly" type="checkbox" id="sshdkeyonly" value="yes" <?php if ($pconfig['sshdkeyonly']) echo "checked"; ?> />
						<b>Disable password login for Secure Shell (RSA key only)</b>
						<br>
						When enabled, authorized keys need to be configured for each
						<a href="system_usermanager.php">user</a>
						that has been granted secure shell access.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">SSH Port</td>
					<td class="vtable">
					<input class="input-small" name="sshport" type="text" id="sshport" value="<?php echo $pconfig['sshport']; ?>" />
					<br>
					 Note: Leave this blank for the default of 22.
					</td>
				</tr>
				<tr>
					<td class="vncell"></td>
					<td class="vtable">
						<input name="Submit" type="submit" class="btn btn-inverse" value="Save" />
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>
<script language="JavaScript" type="text/javascript">
<!--
  prot_change();
//-->
</script>

</div>
<?php
  if ($restart_webgui)
    echo "<meta http-equiv=\"refresh\" content=\"20;url={$url}\">";
?>
</body>
</html>

<?php

if ($restart_sshd)
{
  killbyname("sshd");

  if ($config['system']['enablesshd']) {
    send_event("service restart sshd");
  }
}

if ($restart_webgui)
{
  ob_flush();
  flush();
  send_event("service restart webgui");
}

?>
