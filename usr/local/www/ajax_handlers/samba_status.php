<?php
/*
	samba_status.php

	Copyright (C) 2013-2020 Ogün AÇIK
    All rights reserved.

    # AJAX backend for smbclient connection status

*/

require('guiconfig.inc');


if(($_POST['act'] == 'getstatus'))
{

    $smb_conn_tester = '/usr/local/bin/smb_conn_test';
    $smb_conn_status =  1;

    if (file_exists($smb_conn_tester)) {
        exec("/usr/local/bin/smb_conn_test", $_, $smb_conn_status);
    }

	if ($smb_conn_status == 0) {
	    $html = <<<EOF
		<span class="label label-success">Bağlandı</span>
EOF;
	}

	else {
	    $html = <<<EOF
		<span class="label label-important">Bağlantı yok</span>
EOF;
	}

    echo $html;
}

else
	echo "No request";

?>
