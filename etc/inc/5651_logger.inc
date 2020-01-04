<?php
/*
	5651_logger.inc

	Copyright (C) 2013-2020 Ogün AÇIK
	All rights reserved.

*/

function remove_duplicate($array, $field)
{
	foreach ($array as $sub)
		$cmp[] = $sub[$field];

	$unique = array_unique(array_reverse($cmp,true));

	foreach ($unique as $k => $rien)
		$new[] = $array[$k];

	return $new;
}

function format_ipaddr($ip)
{
	$len = strlen($ip);

	if($len == 8)
		return $ip . "\t\t\t\t";

	else if($len == 9 or $len == 10)
		return $ip . "\t\t\t";

	else if($len == 11 or $len == 12)
		return $ip . "\t\t";

	else if($len>=13 and $len <= 15)
		return $ip . "\t";

	else return $ip;
}

function smbfileInit($hostname, $username, $password, $folder)
{
    $conf = <<<EOF
#!/bin/sh

# log_sender
# Copyright (C) 2013-2020 Ogün AÇIK
# All rights reserved.

LOG_DIR="/var/5651/logs"
SENT_DIR="\$LOG_DIR/sent"

TODAY=`date "+%d%m%Y"`

mkdir -p \$SENT_DIR
mkdir -p \$SENT_DIR/\$TODAY

cd \$LOG_DIR

for F in *.txt; do
    test -f "\$F" || exit
	if timelimit -t 2 smbclient //$hostname/$folder -A /etc/.smbpass -c "put \$F"; then
	    mv \$F \$SENT_DIR/\$TODAY/
	    /usr/local/bin/custom_logger 5651 ""\$F" dosyası //$hostname/$folder Windows paylaşımına gönderildi."
	else
	    /usr/local/bin/custom_logger 5651 "HATA: //$hostname/$folder ile baglantı kurulamadı."
	fi;
done

EOF;


    $conf_creds = <<<EOF
username = $username
password = $password

EOF;

    $conf_connection_tester = <<<EOF
timelimit -t 2 smbclient //$hostname/$folder -A /etc/.smbpass -c ls

EOF;

	$smbfile = '/usr/local/bin/log_sender';
	file_put_contents($smbfile, $conf);
	exec("chmod 500 $smbfile");

	$credsfile = '/etc/.smbpass';
	file_put_contents($credsfile, $conf_creds);
	exec("chmod 400 $credsfile");

	$conntestfile = '/usr/local/bin/smb_conn_test';
	file_put_contents($conntestfile, $conf_connection_tester);
	exec("chmod 500 $conntestfile");

}

?>
