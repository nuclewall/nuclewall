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
		$input_errors[] = "Yerel MySQL sunucusuna bağlanılamadı.";
		$input_errors[] = $e->getMessage();
	}
}

else
{
	$input_errors[] = "Yerel MySQL sunucu çalışmıyor. 'mysqld' servisinin çalıştığından emin olun.";
}