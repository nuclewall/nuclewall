<?php
/*
	openssl.inc
	
	Copyright (C) 2013-2015 Ogün Açýk
	All rights reserved.
*/

function openSSLConfigInit()
{
	$ca_pass = md5(uniqid() . 'nuclewall-ca');
	$tsa_pass = md5(uniqid() . 'nuclewall-tsa');
	$temp_config = '/usr/local/openssl/scripts/temp_config';
	$org_config = '/usr/local/openssl/scripts/openssl_config';

	$conf = <<<EOF
# Config Path
export N_CONF_P="/usr/local/openssl"
# OpenSSL Config File
export N_CONF_F="\$N_CONF_P/openssl.cnf"
# CA Path
export N_CA_P="\$N_CONF_P/ca"
#Scripts Path
export N_SCRIPTS_P="\$N_CONF_P/scripts"
# Binary 
export N_BIN="/usr/local/bin/openssl"
# Days
export N_DAY="3650"
# Bit
export N_BIT="4096"
# CA Passphare
export N_CAPASS="$ca_pass"
# TSA Private Key Passphare
export N_TSAPASS="$tsa_pass"
# Subj for CA
export N_SUBJ_CA="/C=TR/ST=./L=Istanbul/O=NUCLEWALL/OU=NUCLEWALL_PROJECT/CN=nuclewall-ca/emailAddress=info@nuclewall.com"
# Subj for Cert
export N_SUBJ_CERT="/C=TR/ST=./L=Istanbul/O=NUCLEWALL/OU=NUCLEWALL_PROJECT/CN=nuclewall_tsa_cert/emailAddress=info@nuclewall.com"
# Files to be signed
export LOGS_DIR="/var/db/5651"


EOF;

	file_put_contents($temp_config, $conf);
	system("base64 --encode $temp_config > $org_config && rm -f $temp_config");
	system("chmod 400 $org_config && chflags schg $org_config");
}

function openssl_init()
{
	$platform = trim(file_get_contents('/etc/platform'));
	$initFile = '/etc/nuclewall/ssl_init';
	
	if(file_exists($initFile) and ($platform == 'pfSense'))  
	{
		echo "OpenSSL sertifikalarý oluþturuluyor...";
		openSSLConfigInit();
		system('ca_create.sh && cert_create.sh');
		
		if(file_exists("/usr/local/openssl/ca/cacert.pem") and
		file_exists("/usr/local/openssl/ca/tsacert.pem") and
		file_exists("/usr/local/openssl/ca/private/cakey.pem") and
		file_exists("/usr/local/openssl/ca/private/tsakey.pem"))
		{
			echo "Tamam.\n";
			system("rm -f $initFile");
		}
		
		else
			echo "Hata oluþtu.\n";
	}
}

?>
