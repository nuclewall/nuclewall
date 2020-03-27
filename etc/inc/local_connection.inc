<?php
require('util.inc');
require('local.inc');

if(is_process_running('mysqld'))
{
	try
	{
		$pdo = new PDO(
			sprintf(
				'mysql:host=%s;dbname=%s;port=%s',
				$settings['host'],
				$settings['name'],
				$settings['port']
			),
			$settings['username'],
			$settings['password']
		);

		$connection = true;
	}
	catch (PDOException $e)
	{
		$input_errors[] = "Unable to connect local MySQL Server.";
		$input_errors[] = $e->getMessage();
	}
}

else
{
	$input_errors[] = "Local MySQL Server is not running. Ensure 'mysqld' service is running.";
}
