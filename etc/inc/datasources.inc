<?php
/*
	datasources.inc

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.
*/

function MySQLConnectionFile($hostname, $port, $username, $password, $database, $table, $uname_field, $pass_field)
{
	$external_file = '/etc/inc/external.inc';

	$conf = <<<EOF
<?php
\$settings = array(
    'table_name' => '{$table}',
	'username_field' => '{$uname_field}',
    'password_field' => '{$pass_field}'
);

\$external_connection = MySQLConnect("{$hostname}", "{$port}", "{$username}", "{$password}", "{$database}");

?>
EOF;

	file_put_contents($external_file, $conf);
}

function PgSQLConnectionFile($hostname, $port, $username, $password, $database, $table, $uname_field, $pass_field)
{
	$external_file = '/etc/inc/external.inc';

	$conf = <<<EOF
<?php
\$settings = array(
    'table_name' => '{$table}',
	'username_field' => '{$uname_field}',
    'password_field' => '{$pass_field}'
);

\$external_connection = PgSQLConnect("{$hostname}", "{$port}", "{$username}", "{$password}", "{$database}");

?>
EOF;

	file_put_contents($external_file, $conf);
}

function SqlServerConnectionFile($hostname, $port, $username, $password, $database, $table, $uname_field, $pass_field)
{
	$external_file = '/etc/inc/external.inc';

	$conf = <<<EOF
<?php
\$settings = array(
    'table_name' => '{$table}',
	'username_field' => '{$uname_field}',
    'password_field' => '{$pass_field}'
);

\$external_connection = MSSQLConnect("{$hostname}", "{$port}", "{$username}", "{$password}", "{$database}");

?>
EOF;

	file_put_contents($external_file, $conf);
}

?>
