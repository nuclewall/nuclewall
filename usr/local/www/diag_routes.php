<?php
/*
	diag_routes.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.
*/
include('guiconfig.inc');

$pgtitle = array('STATUS ', 'ROUTES');

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<table cellspacing="0" cellpadding="0">
	<tr>
		<td>
			<table class="tabcont" cellspacing="0" cellpadding="0">
				<tr>
					<td>
						<table class="grids sortable">
							<tr>
								<td class="head">
									Destination
								</td>
								<td class="head">
									Gateway
								</td>
								<td class="head">
									Flags
								</td>
								<td class="head">
								    Refs
								</td>
								<td class="head">
									Use
								</td>
								<td class="head">
									MTU
								</td>
								<td class="head">
									Interface
								</td>
							</tr>
							<?php
								$netstat = "/usr/bin/netstat -nrW -f inet | sed 1,4d > /tmp/routes";
								exec($netstat);

								$routes = fopen("/tmp/routes", "r");

								if ($routes)
								{
									while (($line = fgets($routes)) !== false)
									{
										$fields = split("[ ]{1,}", $line);
										echo "<tr>\n";
										echo "<td class=\"cell\">{$fields[0]}</td>\n";
										echo "<td class=\"cell\">{$fields[1]}</td>\n";
										echo "<td class=\"cell\">{$fields[2]}</td>\n";
										echo "<td class=\"cell\">{$fields[3]}</td>\n";
										echo "<td class=\"cell\">{$fields[4]}</td>\n";
										echo "<td class=\"cell\">{$fields[5]}</td>\n";
										echo "<td class=\"cell\">{$fields[6]}</td>\n";
										echo "</tr>\n";
									}

									fclose($routes);
								}

								else
								{
									log_error("Can't load routes");
								}
							?>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</div>
</body>
