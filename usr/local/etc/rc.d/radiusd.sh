#!/bin/sh
# This file was automatically generated
# by the pfSense service handler.

rc_start() {
    /sbin/ldconfig -m /usr/local/lib/mysql
	/usr/local/etc/rc.d/radiusd onestart
}

rc_stop() {
	/usr/local/etc/rc.d/radiusd onestop
}

case $1 in
	start)
		rc_start
		;;
	stop)
		rc_stop
		;;
	restart)
		rc_stop
		rc_start
		;;
esac

