#!/bin/sh

# Decode and include the encoded config file
base64 --decode /usr/local/openssl/scripts/openssl_config > /tmp/d_config
. /tmp/d_config

N_FILE=$1

if [ -f $N_FILE ]; then
    # Create TSA request
    $N_BIN ts -query -data $N_FILE -no_nonce -out $N_FILE.tsq >>/var/log/file_signs 2>&1
	
	# Create TSA Token
	$N_BIN ts -config $N_CONF_F -passin pass:$N_TSAPASS -reply -queryfile $N_FILE.tsq -out $N_FILE.imza -token_out >>/var/log/file_signs 2>&1

    # Create TSA Response
    #$N_BIN ts -config $N_CONF_F -passin pass:$N_TSAPASS -reply -queryfile $N_FILE.tsq -out $N_FILE.tsr
	
	if [ -f $N_FILE.imza ]; then
		# Make files more secure
		chmod 400 $N_FILE
		chmod 400 $N_FILE.imza
		#chmod 400 $N_FILE.tsr
		#chflags schg $N_FILE
		#chflags schg $N_FILE.tsr
		#chflags schg $N_FILE.imza
		rm -f $N_FILE.tsq
		echo "OK"
	else
		echo "FAILED"
	fi
	
else
    echo "NO FILE"
fi

# Remove the decoded config file
rm /tmp/d_config
