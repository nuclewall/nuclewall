<?php
/*
	diag_defaults.php
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

require('guiconfig.inc');

$pgtitle = array('ARAÇLAR', 'AYARLARI SIFIRLA');

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php if ($_POST['Submit'] == "Onayla"):
	print_info_box("NUCLEWALL şimdi fabrika ayarlarına dönderilip yeniden başlatılacak. Bu işlem birkaç dakika sürebilir."); ?>
	<pre>
	<?php
		reset_factory_defaults();
		system_reboot();
	?>
	</pre>
<?php else: ?>

<form action="diag_defaults.php" method="post">
<table border="0" cellspacing="0" cellpadding="0">
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>

					<td colspan="2" class="vncell">
						<div class="alert alert-block alert-error fade in">
						<h4 class="alert-heading">Ayarları sıfırla?</h4>
							<p>
								Onaylarsanız, NUCLEWALL fabrika ayarlarına döndürülüp yeniden başlatılacak.
							</p>
							<p>
								NOT: HOTSPOT verileri ve olay günlükleri silinmeyecek.
							</p>
							<p>

							</p>
						</div>

					</td>
				</tr>
				<tr>
					<td class="vncell"></td>
					<td class="vtable" align="right">
						<input name="Submit" type="submit" class="btn btn-inverse" value="Onayla">
						<a href="index.php" class="btn">Vazgeç</a>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>
<?php endif; ?>
</div>
</body>
</html>
