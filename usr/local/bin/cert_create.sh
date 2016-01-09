#!/bin/sh

# Decode and include the encoded config file
base64 --decode /usr/local/openssl/scripts/openssl_config > /tmp/d_config
. /tmp/d_config

C_DIR=`pwd`

mkdir /tmp/certs
cd /tmp/certs

# Create Private Key for TSA
$N_BIN genrsa -passout pass:$N_TSAPASS -aes256 -out tsakey.pem $N_BIT >/var/log/cert_create.log 2>&1

# Create Certificate Request
$N_BIN req -passin pass:$N_TSAPASS -subj "$N_SUBJ_CERT" -days $N_DAY -sha512 -new -key tsakey.pem -out tsareq.csr >>/var/log/cert_create.log 2>&1

# Create TSA Certificate
yes | $N_BIN ca -passin pass:$N_CAPASS -config $N_CONF_F -in tsareq.csr -out tsacert.pem >>/var/log/cert_create.log 2>&1

# Copy to CA dir
mv tsacert.pem $N_CA_P/
mv tsakey.pem $N_CA_P/private/

# Make files more secure
chmod 400 $N_CA_P/tsacert.pem
chmod 400 $N_CA_P/private/tsakey.pem
chflags schg $N_CA_P/tsacert.pem
chflags schg $N_CA_P/private/tsakey.pem

# Remove temp files
rm -rf /tmp/certs

# Remove the decoded config file
rm /tmp/d_config
