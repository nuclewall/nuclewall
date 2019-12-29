<?php
/*
	diag_logs_settings.php

	Copyright (C) 2013-2015 Ogün AÇIK
	All rights reserved.

	Copyright (C) 2004-2009 Scott Ullrich
	All rights reserved.

	originially part of m0n0wall (http://m0n0.ch/wall)
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

require_once('guiconfig.inc');
require_once('functions.inc');
require_once('filter.inc');
require_once('shaper.inc');

$pconfig['reverse'] = isset($config['syslog']['reverse']);
$pconfig['nentries'] = $config['syslog']['nentries'];

if (!$pconfig['nentries'])
	$pconfig['nentries'] = 50;

if ($_POST)
{
	unset($input_errors);
	$pconfig = $_POST;

	if (($_POST['nentries'] < 5) || ($_POST['nentries'] > 2000))
		$input_errors[] = "Gösterilecek kayıt sayısı 5 ile 2000 arasında olmalıdır.";

	if (!$input_errors)
	{
		$config['syslog']['reverse'] = $_POST['reverse'] ? true : false;
		$config['syslog']['nentries'] = (int)$_POST['nentries'];

		write_config();

		$retval = 0;
		$retval = system_syslogd_start();
		if ($oldnologdefaultblock !== isset($config['syslog']['nologdefaultblock']))
			$retval |= filter_configure();

		$savemsg = get_std_save_message($retval);
	}
}

$pgtitle = array('OLAY GÜNLÜKLERİ' ,'AYARLAR');

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<form action="diag_logs_settings.php" method="post" name="iform" id="iform">
<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<table border="0" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<?php
				$tab_array = array();
				$tab_array[] = array('Sistem', false, 'diag_logs.php');
				$tab_array[] = array('Güvenlik Duvarı', false, 'diag_logs_filter.php');
				$tab_array[] = array('DHCP', false, 'diag_logs_dhcp.php');
				$tab_array[] = array('MySQL', false, 'diag_logs_mysql.php');
				$tab_array[] = array('FreeRADIUS', false, 'diag_logs_radius.php');
				$tab_array[] = array('Ayarlar', true, 'diag_logs_settings.php');
				display_top_tabs($tab_array);
			?>
		</td>
	</tr>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td class="vncell" valign="top">Son Kayıtlar Üstte</td>
					<td class="vtable">
						<input name="reverse" type="checkbox" id="reverse" value="yes" <?php if ($pconfig['reverse']) echo "checked"; ?>>
							Günlük kayıtları yeniden eskiye doğru gösterilir.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Gösterilecek Kayıt Sayısı</td>
					<td class="vtable">
						<input name="nentries" id="nentries" type="text" value="<?=htmlspecialchars($pconfig['nentries']);?>">
					</td>
				</tr>
				<tr>
                    <td class="vncell"></td>
                    <td class="vtable">
						<input name="Submit" type="submit" class="btn btn-inverse" value="Kaydet" onclick="enable_change(true)">
                    </td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>
</div>
</body>
</html>
