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
# Copyright (C) 2013-2015 Ogün AÇIK
# All rights reserved.

LOG_DIR="/var/5651/logs"
SENT_DIR="\$LOG_DIR/sent"

TODAY=`date "+%d%m%Y"`

mkdir -p \$SENT_DIR
mkdir -p \$SENT_DIR/\$TODAY

cd \$LOG_DIR

for F in *.txt; do
    test -f "\$F" || exit
	if smbclient //$hostname/$folder -U $username%$password -c "put \$F"; then
	    mv \$F \$SENT_DIR/\$TODAY/
	    logger "5651: \$F dosyasi Windows paylasimina gonderildi."
	else
	    logger "5651: Windows paylasimi ile baglanti kurulamadi."
	fi;
done

EOF;

	$smbfile = '/usr/local/bin/log_sender';
	file_put_contents($smbfile, $conf);
	exec("chmod 100 $smbfile");
}

?>
