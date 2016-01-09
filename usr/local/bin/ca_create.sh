#!/bin/sh

# Decode and include the encoded config file
base64 --decode /usr/local/openssl/scripts/openssl_config > /tmp/d_config
. /tmp/d_config

mkdir /tmp/certs
cd /tmp/certs

# Create private and public key for CA
($N_BIN req -config $N_CONF_F -passout pass:$N_CAPASS -subj "$N_SUBJ_CA" -days $N_DAY -x509 -sha512 -newkey rsa:$N_BIT -out cacert.pem -outform PEM) >/var/log/ca_create.log 2>&1

# Copy to CA
mv cacert.pem $N_CA_P/
mv privkey.pem $N_CA_P/private/cakey.pem

# Make files more secure
chmod 400 $N_CA_P/cacert.pem
chmod 400 $N_CA_P/private/cakey.pem
chflags schg $N_CA_P/cacert.pem
chflags schg $N_CA_P/private/cakey.pem

# Remove temp files
rm -rf /tmp/certs

# Remove the decoded config file
rm /tmp/d_config
