<?php
/* $Id$ */
/*
    diag_cpu_activity.php
	
	Copyright (C) 2013-2015 Ogün AÇIK
	All rights reserved.

    Copyright (C) 2008-2009 Scott Ullrich
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

$pfSversion = str_replace("\n", "", file_get_contents("/etc/version"));

$pgtitle = 'DURUM: SİSTEM KAYNAK KULLANIMI';

if($_REQUEST['getactivity']) {
	$text = `/usr/bin/top -HS`;
	echo $text;
	exit;
}
?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<script type="text/javascript">
	function getcpuactivity() {
		scroll(0,0);
		var url = "/diag_system_activity.php";
		var pars = 'getactivity=yes';
		var myAjax = new Ajax.Request(
			url,
			{
				method: 'post',
				parameters: pars,
				onComplete: activitycallback
			});
	}
	function activitycallback(transport) {
		$('cpuactivitydiv').innerHTML = '<font face="Arial"><font size="3"><b><pre style="text-align:left;">' + transport.responseText  + '</pre></font>';
		setTimeout('getcpuactivity()', 2500);		
	}
	setTimeout('getcpuactivity()', 1000);	
</script>
<div id='maincontent'>
<?php
	if(strstr($pfSversion, "1.2")) 
		echo "<p class=\"pgtitle\">{$pgtitle}</p>";
	if($savemsg) {
		echo "<div id='savemsg'>";
		print_info_box($savemsg);
		echo "</div>";	
	}
	if ($input_errors)
		print_input_errors($input_errors);
?>
<table border="0" cellpadding="0" cellspacing="0">  
	<tr>
		<td>
			<table id="backuptable" class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td>
					<center>
						<table>
							<tr>
								<td>
									<div name='cpuactivitydiv' id='cpuactivitydiv'>
										Veriler alınıyor...
									</div>
								</td>
							</tr>
						</table>
					</center>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</div>
</div>
</body>
</html>
