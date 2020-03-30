<?php
/*
	mysql.inc

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.
*/

function mysql_init()
{
	$platform = trim(file_get_contents('/etc/platform'));
	$initFile = '/etc/nuclewall/mysql_init';
	$mysqlDir = '/var/db/mysql';

	if(file_exists($initFile) and ($platform == 'pfSense'))
	{
		if(is_dir('/var/db/mysql'))
			exec("rm -rf /var/db/mysql");

		initFiles();
		exec('/etc/mysql/mysql.sh');
	}
}

function initFiles()
{
	$pfx = '/etc/mysql';
    $adminSql = "$pfx/admin.sql";
	$schemaSql = "$pfx/schema.sql";
	$mysqlSecret = "$pfx/mysql.sec";
	$sqlConf = '/usr/local/etc/raddb/sql.conf';
	$mysqlStarter = "$pfx/mysql.sh";
	$mysqlLocalConn = '/etc/inc/local.inc';
	$clientsConf = '/usr/local/etc/raddb/clients.conf';

	$rootPass = md5(uniqid() . 'root');
	$radiusPass = md5(uniqid() . 'radius');
	$radclientPass = md5(uniqid() . 'radclient');

	$_adminSql = adminSqlInit($radiusPass);
	$_schemaSql = schemaSqlInit();
	$_mysqlLocalConn = mysqlLocalConnInit($radiusPass);
	$_mysqlSecret = mysqlSecretInit($rootPass, $radiusPass);
	$_sqlConf = sqlConfInit($radiusPass);
	$_mysqlStarter = mysqlStarterInit($rootPass);
	$_clientsConf = radiusClientsInit($radclientPass);

	file_put_contents($adminSql, $_adminSql);
	file_put_contents($schemaSql, $_schemaSql);
	file_put_contents($mysqlSecret, $_mysqlSecret);
	file_put_contents($sqlConf, $_sqlConf);
	file_put_contents($mysqlStarter, $_mysqlStarter);
	file_put_contents($mysqlLocalConn, $_mysqlLocalConn);
	file_put_contents($clientsConf, $_clientsConf);

	chmod($mysqlStarter, 0755);
	chmod($sqlConf, 0640);
}

function adminSqlInit($pass)
{

	$conf = <<<EOF
CREATE USER 'radius'@'localhost';
SET PASSWORD FOR 'radius'@'localhost' = PASSWORD('$pass');

GRANT SELECT ON radius.* TO 'radius'@'localhost';
GRANT ALL on radius.radacct TO 'radius'@'localhost';
GRANT ALL on radius.radpostauth TO 'radius'@'localhost';
GRANT ALL on radius.radcheck TO 'radius'@'localhost';
GRANT ALL on radius.radreply TO 'radius'@'localhost';
GRANT ALL on radius.blocklist TO  'radius'@'localhost';

EOF;

	return $conf;
}

function mysqlSecretInit($root_p, $rad_p)
{

	$conf = <<<EOF
root	$root_p
radius	$rad_p
EOF;

	return $conf;
}

function sqlConfInit($rad_p)
{

	$conf = <<<EOF
sql {
	database = "mysql"
	driver = "rlm_sql_\${database}"
	server = "localhost"
	port = 3306
	login = "radius"
	password = "$rad_p"
	radius_db = "radius"
	acct_table1 = "radacct"
	acct_table2 = "radacct"
	postauth_table = "radpostauth"
	authcheck_table = "radcheck"
	authreply_table = "radreply"
	groupcheck_table = "radgroupcheck"
	groupreply_table = "radgroupreply"
	usergroup_table = "radusergroup"
	read_groups = no
	deletestalesessions = yes
	sqltrace = no
	sqltracefile = \${logdir}/sqltrace.sql
	num_sql_socks = 5
	connect_failure_retry_delay = 5
	lifetime = 0
	max_queries = 0
	readclients = no
	nas_table = "nas"
	\$INCLUDE sql/\${database}/dialup.conf
}
EOF;

	return $conf;
}

function mysqlStarterInit($root_p)
{
	$pfx = '/etc/mysql';

    $conf = <<<EOF
#!/bin/sh
export BIN_P="/usr/local/bin"
/usr/local/etc/rc.d/mysql-server.sh start && logger 'MySQL started.'

\$BIN_P/mysqladmin -u root password '$root_p'
\$BIN_P/mysql -u root -p'$root_p' -e "CREATE DATABASE radius"
\$BIN_P/mysql -u root -p'$root_p' radius < $pfx/admin.sql
\$BIN_P/mysql -u root -p'$root_p' radius < $pfx/schema.sql

/bin/rm -f $pfx/admin.sql $pfx/schema.sql $pfx/mysql.sh $pfx/init /etc/nuclewall/mysql_init
EOF;

	return $conf;
}

function schemaSqlInit()
{
	$conf = <<<EOF
CREATE TABLE radacct (
  radacctid bigint(21) NOT NULL auto_increment,
  acctsessionid varchar(64) NOT NULL default '',
  acctuniqueid varchar(32) NOT NULL default '',
  username varchar(64) NOT NULL default '',
  groupname varchar(64) NOT NULL default '',
  realm varchar(64) default '',
  nasipaddress varchar(15) NOT NULL default '',
  nasportid varchar(15) default NULL,
  nasporttype varchar(32) default NULL,
  acctstarttime datetime NULL default NULL,
  acctstoptime datetime NULL default NULL,
  acctsessiontime int(12) default NULL,
  acctauthentic varchar(32) default NULL,
  connectinfo_start varchar(50) default NULL,
  connectinfo_stop varchar(50) default NULL,
  acctinputoctets bigint(20) default NULL,
  acctoutputoctets bigint(20) default NULL,
  calledstationid varchar(50) NOT NULL default '',
  callingstationid varchar(50) NOT NULL default '',
  acctterminatecause varchar(32) NOT NULL default '',
  servicetype varchar(32) default NULL,
  framedprotocol varchar(32) default NULL,
  framedipaddress varchar(15) NOT NULL default '',
  acctstartdelay int(12) default NULL,
  acctstopdelay int(12) default NULL,
  xascendsessionsvrkey varchar(10) default NULL,
  PRIMARY KEY  (radacctid),
  KEY username (username),
  KEY framedipaddress (framedipaddress),
  KEY acctsessionid (acctsessionid),
  KEY acctsessiontime (acctsessiontime),
  KEY acctuniqueid (acctuniqueid),
  KEY acctstarttime (acctstarttime),
  KEY acctstoptime (acctstoptime),
  KEY nasipaddress (nasipaddress)
) ;

CREATE TABLE radcheck (
  id int(11) unsigned NOT NULL auto_increment,
  username varchar(64) NOT NULL default '',
  attribute varchar(64)  NOT NULL default '',
  op char(2) NOT NULL DEFAULT '==',
  value varchar(253) NOT NULL default '',
  registration TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  description varchar(60),
  PRIMARY KEY  (id),
  KEY username (username(32))
) ;

CREATE TABLE radgroupcheck (
  id int(11) unsigned NOT NULL auto_increment,
  groupname varchar(64) NOT NULL default '',
  attribute varchar(64)  NOT NULL default '',
  op char(2) NOT NULL DEFAULT '==',
  value varchar(253)  NOT NULL default '',
  PRIMARY KEY  (id),
  KEY groupname (groupname(32))
) ;

CREATE TABLE radgroupreply (
  id int(11) unsigned NOT NULL auto_increment,
  groupname varchar(64) NOT NULL default '',
  attribute varchar(64)  NOT NULL default '',
  op char(2) NOT NULL DEFAULT '=',
  value varchar(253)  NOT NULL default '',
  PRIMARY KEY  (id),
  KEY groupname (groupname(32))
) ;

CREATE TABLE radreply (
  id int(11) unsigned NOT NULL auto_increment,
  username varchar(64) NOT NULL default '',
  attribute varchar(64) NOT NULL default '',
  op char(2) NOT NULL DEFAULT '=',
  value varchar(253) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY username (username(32))
) ;

CREATE TABLE radusergroup (
  username varchar(64) NOT NULL default '',
  groupname varchar(64) NOT NULL default '',
  priority int(11) NOT NULL default '1',
  KEY username (username(32))
) ;

CREATE TABLE radpostauth (
  id int(11) NOT NULL auto_increment,
  username varchar(64) NOT NULL default '',
  pass varchar(64) NOT NULL default '',
  reply varchar(32) NOT NULL default '',
  authdate timestamp NOT NULL,
  PRIMARY KEY  (id)
) ;

CREATE TABLE blocklist (
  id int(11) NOT NULL auto_increment,
  mac_addr varchar(17) NOT NULL default '',
  registration TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  description varchar(60),
  PRIMARY KEY  (id)
) ;

EOF;

	return $conf;
}

function mysqlLocalConnInit($pass)
{
	$conf = <<<EOF
<?php
\$settings = array(
    'host' => '127.0.0.1',
    'port' => '3306',
    'name' => 'radius',
    'username' => 'radius',
    'password' => '{$pass}',
);
?>
EOF;

	return $conf;
}

function radiusClientsInit($pass)
{
	global $config;
	$config = parse_config(true);

	$conf = <<<EOF
client "captiveportal" {
	ipaddr = 127.0.0.1
	secret = $pass
	shortname = captiveportal
}
EOF;

	$config['captiveportal']['radiuskey'] = $pass;
	$config['captiveportal']['radmac_secret'] = $pass;
	write_config("MySQL Server initialized");

	return $conf;
}

?>
