#!/bin/sh
# This file was automatically generated
# by the pfSense service handler.

rc_start() {
	if [ -z "`ps auxw | grep "[s]quid -D"|awk '{print $2}'`" ];then
	/usr/local/sbin/squid -D
fi

}

rc_stop() {
	/usr/local/sbin/squid -k shutdown
# Just to be sure...
sleep 5
killall -9 squid 2>/dev/null
killall pinger 2>/dev/null

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

