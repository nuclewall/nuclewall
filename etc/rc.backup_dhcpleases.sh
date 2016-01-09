#!/bin/sh

if [ -d "/var/dhcpd/var/db" ]; then
	cd / && tar -czf /cf/conf/dhcpleases.tgz -C / var/dhcpd/var/db/
fi
