#!/bin/sh

# Decode and include the encoded config file
base64 --decode /usr/local/openssl/scripts/openssl_config > /tmp/d_config
. /tmp/d_config

N_FILE=$1

if [ -f $N_FILE ]; then

	if [ -f $N_FILE.imza ]; then
	    # Verify with the token file
		$N_BIN ts -verify -data $N_FILE -in $N_FILE.imza -token_in -CAfile $N_CA_P/cacert.pem -untrusted $N_CA_P/tsacert.pem
		
		# Verify with the signed file
		#$N_BIN ts -verify -data $N_FILE -in $N_FILE.tsr -CAfile $N_CA_P/cacert.pem -untrusted $N_CA_P/tsacert.pem
	
		# Verify with the tsa request file
		#$N_BIN ts -verify -queryfile $N_FILE.tsq -in $N_FILE.tsr -CAfile $N_CA_P/cacert.pem -untrusted $N_CA_P/tsacert.pem
	else
		echo "NO TOKEN"
	fi
else
    echo "NO FILE"
fi

# Remove the decoded config file
rm /tmp/d_config
