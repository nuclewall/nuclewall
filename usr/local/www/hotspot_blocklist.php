<?php
/*
	hotspot_blocklist.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.
*/

require('guiconfig.inc');
require('captiveportal.inc');
require('local_connection.inc');

$pgtitle = array('HOTSPOT ', 'BLOCKED MAC ADDRESSES');

if ($connection)
{
	if (($_GET['act'] == 'del') && is_mac($_GET['mac']))
	{
		$mac_addr = $_GET['mac'];

		$findMac = $pdo->prepare("
			SELECT mac_addr FROM blocklist
			WHERE mac_addr = :mac
		");

		$findMac->bindParam(':mac', $mac_addr);
		$findMac->execute();
		$macExists = $findMac->fetch(PDO::FETCH_ASSOC);

		if($macExists)
		{
			/* Delete from blocklist table */
			$delMac = $pdo->prepare("
			DELETE FROM blocklist
			WHERE mac_addr = :mac");

			$delMac->bindParam(':mac', $mac_addr);
			$delMac->execute();

			$savemsg = "Unblocked '$mac_addr'.";
		}

		else
		{
			$input_errors[] = "Unable to find MAC Address '$mac_addr'.";
		}
	}

	/* Get MAC list */
	$statement = $pdo->prepare("
		SELECT mac_addr, description,
		DATE_FORMAT(registration,'%d-%m-%Y %H:%i:%s') AS date
		FROM blocklist
	");
	$statement->execute();
}

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php if ($input_errors) print_input_errors($input_errors); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>

<table cellpadding="0" cellspacing="0">
	<tr>
		<td class="tabnavtbl">
			<?php
				$tab_array = array();
				$tab_array[] = array('Sessions', false, 'hotspot_status.php');
				$tab_array[] = array('Local Users', false, 'hotspot_users.php');
				$tab_array[] = array('Allowed MAC Addresses', false, 'hotspot_macs.php');
				$tab_array[] = array('Blocked MAC Addresses', true, 'hotspot_blocklist.php');
				$tab_array[] = array('Audit Logs', false, 'hotspot_logs.php');
				display_top_tabs($tab_array, true);
			?>
		</td>
	</tr>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<?php if ($connection): ?>
							<div style="margin-right: 10px;" class="pull-left">
								<a class="btn" href="hotspot_blocklist_edit.php?act=new"><i class="icon-ban-circle"></i>New</a>
							</div>

							<div class="controls">
								<div class="input-prepend">
								  <span class="add-on"><i class="icon-search"></i></span>
								  <input id="search" placeholder="Search MAC Address..." class="input-medium" style="height:20px" type="text">
								</div>
							</div>

						<table class="grids sortable">
							<tr>
								<td class="head users">MAC Address</td>
								<td class="head users">Last Change</td>
								<td class="head users">Description</td>
								<td class="head users"></td>
							</tr>
								<?php while (($result = $statement->fetch(PDO::FETCH_ASSOC)) !== false): ?>
							<tr>
								<td id="usr" class="cell macs"><a href="hotspot_blocklist_edit.php?act=edit&mac=<?=$result['mac_addr'];?>" class="btn-link"><?=$result['mac_addr'];?></a></td>
								<td class="cell date"><?=$result['date'];?></td>

								<td class="cell description"><?=$result['description'];?></td>
								<td class="cell tools">
									<a title="Edit" href="hotspot_blocklist_edit.php?act=edit&mac=<?=$result['mac_addr'];?>">
										<i class="icon-edit"></i>
									</a>
									<a title="Unblock" href="hotspot_blocklist.php?act=del&mac=<?=$result['mac_addr'];?>" onclick="return confirm('Do you want to unblock this MAC Address?')">
										<i class="icon-trash"></i>
									</a>
								</td>
							</tr>
								<?php endwhile; ?>
						</table>
						<?php endif; ?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</div>
<script type="text/javascript">

jQuery("#search").on("keyup", function() {
    var value = jQuery(this).val();

    jQuery("table.grids tr").each(function(index) {
        if (index !== 0) {

            $row = jQuery(this);
            var id = $row.find("td#usr:first").text();

            if (id.indexOf(value) !== 0) {
                $row.hide();
            }
            else {
                $row.show();
            }
        }
    });
});

jQuery(document).keyup(function(e) {
	if (e.keyCode == 27)
	{
		jQuery("table.grids tr").each(function(index){
		$row = jQuery(this);
		jQuery("#search").val("");
		$row.show();});
	}
});
</script>
</body>
</html>
