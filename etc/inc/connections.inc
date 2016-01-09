<?php
/*
	connections.inc
	
	Copyright (C) 2013-2015 Ogün AÇIK
	All rights reserved.
*/

function MSSQLConnect($hostname, $port, $username, $password, $database)
{
	$freetds_file = "/usr/local/etc/freetds.conf";
	
	$freetds = <<<EOF
[global]
	tds version = 8.0
	initial block size = 4096
	text size = 64512
	
[SQLSERVER]
	connect timeout = 2
    host=$hostname
    port=$port

EOF;

	file_put_contents($freetds_file, $freetds);

	try
	{
		$connection = new PDO ("dblib:host=SQLSERVER;dbname=$database", $username, $password);
		return $connection;
	}
	catch(PDOException $e)
	{
		return null;
	}
}

function MySQLConnect($hostname, $port, $username, $password, $database)
{
	try
	{
		$connection = new PDO("mysql:host=$hostname;port=$port;dbname=$database", $username, $password, array(PDO::ATTR_TIMEOUT => "2"));
		return $connection;
	}
	catch(PDOException $e)
	{
		return null;
	}
}

function PgSQLConnect($hostname, $port, $username, $password, $database)
{
	try
	{
		$connection = new PDO("pgsql:host=$hostname;port=$port;dbname=$database", $username, $password, array(PDO::ATTR_TIMEOUT => "2"));
		return $connection;
	}
	catch(PDOException $e)
	{
		return null;
	}
}
 
function checkUser($connection, $table, $field_username, $field_password, $val_username, $val_password)
{
	$query = $connection->prepare("
		SELECT * FROM $table 
		WHERE $field_username = :username AND $field_password = :password
	");

	$query->bindParam(':username', $val_username);
	$query->bindParam(':password', $val_password);
	
	$query->execute();
	$found = $query->fetch(PDO::FETCH_ASSOC);
	
	return $found;
}

function checkData($connection, $table, $field_username, $field_password)
{
	$query = $connection->prepare("
		SELECT $field_username, $field_password FROM $table
	");
	
	$query->execute();
	$found = $query->fetch(PDO::FETCH_ASSOC);
	
	return $found;
}

?>