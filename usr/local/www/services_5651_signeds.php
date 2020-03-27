<?php
/*
	services_5651_signeds.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.

*/

require('config.inc');
require('guiconfig.inc');

$pgtitle = array('5651: SIGNED FILES(LOCAL)');

$today = date('dmY');
?>

<?php include('head.inc'); ?>
<link rel="stylesheet" href="themes/nuclewall/bootstrap/css/bootstrap-datetimepicker.css" media="all"/>
<script type="text/javascript" src="themes/nuclewall/bootstrap/js/bootstrap-datetimepicker.min.js"></script>
<script type="text/javascript" src="themes/nuclewall/bootstrap/js/bootstrap-datetimepicker.tr.js"></script>
</head>
<body>
<?php include('fbegin.inc'); ?>

<table cellpadding="0" cellspacing="0">
	<tr>
		<td class='tabnavtbl'>
			<?php
				$tab_array = array();
				$tab_array[] = array('General Settings', false, 'services_5651_logging.php');
				$tab_array[] = array('Signed Files', true, 'services_5651_signeds.php');
				$tab_array[] = array('Logs', false, 'diag_logs_timestamp.php');
				display_top_tabs($tab_array, true);
			?>
		</td>
	</tr>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<table cellpadding="0" cellspacing="0" width="100%">
							<tr>
								<td valign="top">
									<input type="hidden" id="date_i" value="<?=$today?>" />
									<div id="datetimepicker" data-date-format="dd-mm-yyyy" data-link-field="date_r" data-link-format="ddmmyyyy">
										<input type="hidden" id="date_r" value="" />
									</div>
								</td>
								<td valign="top">
									<table class="grids sortable">
										<thead>
											<tr>
												<td class="head">File</td>
												<td class="head">Creation Time</td>
												<td class="head">Signed Time</td>
												<td class="head"></td>
											</tr>
										</thead>
										<tbody id="files"></tbody>
										<div style="display:none" id="sign_alert" class="alert"></div>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</div>
<script type="text/javascript">
jQuery('#datetimepicker').datetimepicker({
    language:  'en',
    weekStart: 1,
    todayBtn:  1,
	todayHighlight: 1,
	startView: 2,
	minView: 2,
	forceParse: 0
});

jQuery( document ).ready(function() {
	var date_i = jQuery('#date_i').val();

	jQuery.ajax({
	method: "POST",
	url: "ajax_handlers/signed_files.php",
	data: { act: "list", date: date_i}
	}).done(function( msg )
	{
		jQuery("#files").html(msg);
		jQuery("#sign_alert").hide();
	});
});

jQuery("#datetimepicker").on('changeDate', function()
{
	var date_r = jQuery('#date_r').val();

	jQuery.ajax({
	method: "POST",
	url: "ajax_handlers/signed_files.php",
	data: { act: "list", date: date_r}
	}).done(function( msg )
	{
		jQuery("#files").html(msg);
		jQuery("#sign_alert").hide();
	});
});

jQuery("#files").on("click", "a", function()
{
	var dt = jQuery(this).attr("name");
	var s = dt.split('-');
	var filename = 'dhcp-' + s[1];

	jQuery.ajax({
	method: "POST",
	url: "ajax_handlers/signed_files.php",
	data: { act: "checksign", f: dt}
})
  .done(function(msg)
  {
	if(msg == 'Verification: OK')
	{
		jQuery("#sign_alert").text(filename + " file is verified.");
		jQuery("#sign_alert").removeClass("alert-error");
		jQuery("#sign_alert").addClass("alert-success");
	}
	else if(msg == 'Verification: FAILED')
	{
		jQuery("#sign_alert").text(filename + " file is changed or corrupted. Not verified.");
		jQuery("#sign_alert").removeClass("alert-success");
		jQuery("#sign_alert").addClass("alert-error");
	}
	jQuery("#sign_alert").show();
  });
});

</script>
</body>
</html>
