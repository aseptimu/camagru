#!/bin/sh
set -e

cat <<EOF > /etc/msmtprc
defaults
auth           on
tls            on
tls_starttls   on
tls_trust_file /etc/ssl/certs/ca-certificates.crt
logfile        /var/log/msmtp.log

account default
host      smtp.gmail.com
port      587
user      asolovey16234@gmail.com
passwordeval "echo zjdlmzvrnvvqozis"
from      asolovey16234@gmail.com

EOF

chown www-data:www-data /etc/msmtprc
chmod 600 /etc/msmtprc

touch /var/log/msmtp.log
chown www-data:www-data /var/log/msmtp.log
chmod 600 /var/log/msmtp.log

exec "$@"
