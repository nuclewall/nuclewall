<?php

$priv_list = array();

$priv_list['user-shell-access'] = array();
$priv_list['user-shell-access']['name']  = 'SSH';

$priv_list['page-all'] = array();
$priv_list['page-all']['name'] = 'ALL PAGES';
$priv_list['page-all']['match'] = array();
$priv_list['page-all']['match'][] = '*';

$priv_list['page-essential'] = array();
$priv_list['page-essential']['name'] = 'ESSENTIAL PAGES';
$priv_list['page-essential']['match'] = array();
$priv_list['page-essential']['match'][] = 'index.php*';
$priv_list['page-essential']['match'][] = 'getstats.php*';
$priv_list['page-essential']['match'][] = 'system_usermanager_passwordmg.php*';

$priv_list['page-hotspot-menu'] = array();
$priv_list['page-hotspot-menu']['name'] = 'HOTSPOT';
$priv_list['page-hotspot-menu']['match'] = array();
$priv_list['page-hotspot-menu']['match'][] = 'hotspot_status.php*';
$priv_list['page-hotspot-menu']['match'][] = 'hotspot_blocklist.php*';
$priv_list['page-hotspot-menu']['match'][] = 'hotspot_blocklist_edit.php*';
$priv_list['page-hotspot-menu']['match'][] = 'hotspot_datasources.php*';
$priv_list['page-hotspot-menu']['match'][] = 'hotspot_datasources_mssql.php*';
$priv_list['page-hotspot-menu']['match'][] = 'hotspot_datasources_mysql.php*';
$priv_list['page-hotspot-menu']['match'][] = 'hotspot_datasources_pgsql.php*';
$priv_list['page-hotspot-menu']['match'][] = 'hotspot_form_settings.php*';
$priv_list['page-hotspot-menu']['match'][] = 'hotspot_logs.php*';
$priv_list['page-hotspot-menu']['match'][] = 'hotspot_mac_edit.php*';
$priv_list['page-hotspot-menu']['match'][] = 'hotspot_macs.php*';
$priv_list['page-hotspot-menu']['match'][] = 'hotspot_settings.php*';
$priv_list['page-hotspot-menu']['match'][] = 'hotspot_user_edit.php*';
$priv_list['page-hotspot-menu']['match'][] = 'hotspot_users.php*';

$priv_list['page-system-menu'] = array();
$priv_list['page-system-menu']['name'] = 'SYSTEM';
$priv_list['page-system-menu']['match'] = array();
$priv_list['page-system-menu']['match'][] = 'system.php*';
$priv_list['page-system-menu']['match'][] = 'system_advanced_admin.php*';
$priv_list['page-system-menu']['match'][] = 'system_gateways.php*';
$priv_list['page-system-menu']['match'][] = 'system_gateways_edit.php*';
$priv_list['page-system-menu']['match'][] = 'system_routes.php*';
$priv_list['page-system-menu']['match'][] = 'system_routes_edit.php*';
$priv_list['page-system-menu']['match'][] = 'system_gateway_groups.php*';
$priv_list['page-system-menu']['match'][] = 'system_gateway_groups_edit.php*';

$priv_list['page-network-menu'] = array();
$priv_list['page-network-menu']['name'] = 'INTERFACES';
$priv_list['page-network-menu']['match'] = array();
$priv_list['page-network-menu']['match'][] = 'interfaces.php*';
$priv_list['page-network-menu']['match'][] = 'interfaces_assign.php*';

$priv_list['page-firewall-menu'] = array();
$priv_list['page-firewall-menu']['name'] = 'FIREWALL';
$priv_list['page-firewall-menu']['match'] = array();
$priv_list['page-firewall-menu']['match'][] = 'firewall_aliases.php*';
$priv_list['page-firewall-menu']['match'][] = 'firewall_aliases_edit.php*';
$priv_list['page-firewall-menu']['match'][] = 'firewall_rules.php*';
$priv_list['page-firewall-menu']['match'][] = 'firewall_rules_edit.php*';
$priv_list['page-firewall-menu']['match'][] = 'firewall_schedule.php*';
$priv_list['page-firewall-menu']['match'][] = 'firewall_schedule_edit.php*';
$priv_list['page-firewall-menu']['match'][] = 'firewall_shaper_vinterface.php*';

$priv_list['page-services-menu'] = array();
$priv_list['page-services-menu']['name'] = 'SERVICES';
$priv_list['page-services-menu']['match'] = array();
$priv_list['page-services-menu']['match'][] = 'services_5651_logging.php*';
$priv_list['page-services-menu']['match'][] = 'services_5651_signeds.php*';
$priv_list['page-services-menu']['match'][] = 'diag_logs_timestamp.php*';
$priv_list['page-services-menu']['match'][] = 'signed_files.php*';
$priv_list['page-services-menu']['match'][] = 'samba_status.php*';
$priv_list['page-services-menu']['match'][] = 'services_dnsmasq.php*';
$priv_list['page-services-menu']['match'][] = 'services_dnsmasq_hosts.php*';
$priv_list['page-services-menu']['match'][] = 'services_dnsmasq_edit.php*';
$priv_list['page-services-menu']['match'][] = 'services_dhcp.php*';
$priv_list['page-services-menu']['match'][] = 'services_dhcp_edit.php*';
$priv_list['page-services-menu']['match'][] = 'pkg_edit.php*';
$priv_list['page-services-menu']['match'][] = 'pkg.php*';

$priv_list['page-status-menu'] = array();
$priv_list['page-status-menu']['name'] = 'STATUS';
$priv_list['page-status-menu']['match'] = array();
$priv_list['page-status-menu']['match'] = 'status_gateways.php*';
$priv_list['page-status-menu']['match'] = 'status_gateway_groups.php*';
$priv_list['page-status-menu']['match'] = 'status_dhcp_leases.php*';
$priv_list['page-status-menu']['match'] = 'diag_arp.php*';
$priv_list['page-status-menu']['match'] = 'status_services.php*';
$priv_list['page-status-menu']['match'] = 'diag_routes.php*';
$priv_list['page-status-menu']['match'] = 'diag_logs.php*';
$priv_list['page-status-menu']['match'] = 'diag_logs_filter.php*';
$priv_list['page-status-menu']['match'] = 'diag_logs_dhcp.php*';
$priv_list['page-status-menu']['match'] = 'diag_logs_mysql.php*';
$priv_list['page-status-menu']['match'] = 'diag_logs_radius.php*';
$priv_list['page-status-menu']['match'] = 'diag_logs_settings.php*';
$priv_list['page-status-menu']['match'] = 'status_graph.php*';
$priv_list['page-status-menu']['match'] = 'bandwidth_by_ip.php*';
$priv_list['page-status-menu']['match'] = 'ifstats.php*';
$priv_list['page-status-menu']['match'] = 'graph.php*';

$priv_list['page-tools-menu'] = array();
$priv_list['page-tools-menu']['name'] = 'TOOLS';
$priv_list['page-tools-menu']['match'] = array();
$priv_list['page-tools-menu']['match'] = 'diag_backup.php*';
$priv_list['page-tools-menu']['match'] = 'diag_confbak.php*';
$priv_list['page-tools-menu']['match'] = 'diag_defaults.php*';

$priv_rmvd = array();

?>
