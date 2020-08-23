<?php
    function remove_numbers($string)
	{
	    $nums = array("1", "2", "3", "4", "5", "6", "7", "8", "9", "0", " ");
	    $string = str_replace($nums, '', $string);
	    return $string;
    }

	global $g;
    $g = array(
	    "base_packages" => "siproxd",
	    "event_address" => "unix:///var/run/check_reload_status",
	    "factory_shipped_username" => "admin",
	    "factory_shipped_password" => "nuclewall",
	    "upload_path" => "/root",
	    "dhcpd_chroot_path" => "/var/dhcpd",
	    "varrun_path" => "/var/run",
	    "varetc_path" => "/var/etc",
	    "vardb_path" => "/var/db",
	    "varlog_path" => "/var/log",
	    "etc_path" => "/etc",
	    "tmp_path" => "/tmp",
	    "conf_path" => "/conf",
	    "ftmp_path" => "/ftmp",
	    "conf_default_path" => "/conf.default",
	    "cf_path" => "/cf",
	    "cf_conf_path" => "/cf/conf",
	    "www_path" => "/usr/local/www",
	    "xml_rootobj" => "nuclewall",
	    "admin_group" => "admins",
	    "product_name" => "NUCLEWALL",
	    "product_copyright_url" => "https://nuclewall.site",
	    "debug" => false,
	    "latest_config" => "8.0",
	    "nopkg_platforms" => array("cdrom"),
	    "minimum_ram_warning" => "101",
	    "minimum_ram_warning_text" => "128 MB",
	    "minimum_nic_count" => "1",
	    "minimum_nic_count_text" => "*AT LEAST* 1",
	    "wan_interface_name" => "wan",
	    "nopccard_platforms" => array("wrap", "net48xx"),
	    "xmlrpcbaseurl" => "",
	    "captiveportal_path" => "/usr/local/captiveportal",
	    "captiveportal_element_path" => "/var/db/cpelements",
	    "captiveportal_element_sizelimit" => 1048576,
	    "xmlrpcpath" => "",
	    "embeddedbootupslice" => "/dev/ad0a",
	    "services_dhcp_server_enable" => true,
	    "theme" => "nuclewall"
    );

    /* IP TOS flags */
    $iptos = array("lowdelay", "throughput", "reliability");

    /* TCP flags */
    $tcpflags = array("syn", "ack", "fin", "rst", "psh", "urg");

    /* Default sysctls */
    $sysctls = array("net.inet.ip.portrange.first" => "1024",
	    "net.inet.tcp.blackhole" => "2",
	    "net.inet.udp.blackhole" => "1",
	    "net.inet.ip.random_id" => "1",
	    "net.inet.tcp.drop_synfin" => "1",
	    "net.inet.ip.redirect" => "1",
	    "net.inet6.ip6.redirect" => "1",
	    "net.inet.tcp.syncookies" => "1",
	    "net.inet.tcp.recvspace" => "65228",
	    "net.inet.tcp.sendspace" => "65228",
	    "net.inet.ip.fastforwarding" => "0",
	    "net.inet.tcp.delayed_ack" => "0",
	    "net.inet.udp.maxdgram" => "57344",
	    "net.link.bridge.pfil_onlyip" => "0",
	    "net.link.bridge.pfil_member" => "1",
	    "net.link.bridge.pfil_bridge" => "0",
	    "net.link.tap.user_open" => "1",
	    "kern.randompid" => "347",
	    "net.inet.ip.intr_queue_maxlen" => "1000",
	    "hw.syscons.kbd_reboot" => "0",
	    "net.inet.tcp.inflight.enable" => "1",
	    "net.inet.tcp.log_debug" => "0",
	    "net.inet.tcp.tso" => "1",
	    "net.inet.icmp.icmplim" => "0",
	    "vfs.read_max" => "128",
	    "kern.ipc.maxsockbuf" => "4262144",
	    "debug.pfftpproxy" => "0",
		"net.inet.ip.process_options" => "0"
    );

	$config_parsed = false;
?>
