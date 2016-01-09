<?php
require_once('connections.inc');

$oracle_connection = OracleConnect("192.168.1.50", "1521", "acik", "pascal");
if($oracle_connection)
{
	$oracle = checkUser($oracle_connection, "users", "username", "password", "acik", "123");
	echo "ORACLE: ";
	print_r($oracle);
}


$mysql_connection = MySQLConnect("192.168.1.50", "3306", "root", "pascal", "testdb");
if($mysql_connection)
{
	$mysql = checkUser($mysql_connection, "users", "username", "password", "acik", "123");
	echo "MySQL: ";
	print_r($mysql);
}


$mssql_connection = MSSQLConnect("192.168.1.20", "1433", "oacik", "pascal", "testdb");
if($mssql_connection)
{
	$mssql = checkUser($mssql_connection, "users", "username", "password", "hello", "world");
	echo "MSSQL: ";
	print_r($mssql);
}


$pgsql_connection = PgSQLConnect("192.168.1.50", "5432", "postgres", "pascal", "testdb");
if($pgsql_connection)
{
	$pgsql = checkUser($pgsql_connection, "users", "username", "password", "acik", "123");
	echo "PgSQL: ";
	print_r($pgsql);
}

?>
