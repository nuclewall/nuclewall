<?php
/*
	Copyright (C) 2013-2015 Ogün AÇIK
	All rights reserved.

	Copyright (C) 2004 Scott Ullrich
	All rights reserved.

	part of m0n0wall as reboot.php (http://m0n0.ch/wall)
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
require('functions.inc');
require('captiveportal.inc');

if ($_POST['Submit'] == 'Hayır') {
	header("Location: index.php");
	exit;
}

$pgtitle = array('ARAÇLAR', "NUCLEWALL'U KAPAT");

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php if ($_POST['Submit'] == 'Evet'): ?>
<div class="alert alert-danger">
	<button type="button" class="close" data-dismiss="alert">×</button>
	<span style="font-weight:bold; margin-right:20px;">Şimdi kapatılıyor...</span>
	<?php system_halt(); ?>
</div>
<?php else: ?>
<form action="halt.php" method="post">
<div class="alert alert-danger">
	<button type="button" class="close" data-dismiss="alert">×</button>
	<span style="margin-right:340px;">Kapatma işlemini onaylıyor musunuz?</span>
	<input name="Submit" type="submit" class="btn btn-inverse" value="Evet">
	<input name="Submit" type="submit" class="btn" value="Hayır">
 </div>
</form>
<?php endif; ?>
</div>
</body>
</html>
