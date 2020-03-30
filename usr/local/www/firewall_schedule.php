<?php
/*
	firewall_schedule.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.

	Copyright (C) 2004 Scott Ullrich
	All rights reserved.

	originially part of m0n0wall (http://m0n0.ch/wall)
	Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>.
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	   this list of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this list of conditions and the following disclaimer in the
	   documentation and/or other materials provided with the distribution.

	THIS SOFTWARE IS PROVIDED ``AS IS'' AND ANY EXPRESS OR IMPLIED WARRANTIES,
	INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
	AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
	AUTHOR BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY,
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/


$dayArray = array ('Mon', 'Tues', 'Wed', 'Thur', 'Fri', 'Sat', 'Sun');
$monthArray = array ('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

require('guiconfig.inc');
require('filter.inc');
require('shaper.inc');

$pgtitle = array('FIREWALL ', 'SCHEDULES');

if (!is_array($config['schedules']['schedule']))
	$config['schedules']['schedule'] = array();

$a_schedules = &$config['schedules']['schedule'];


if ($_GET['act'] == "del") {
	if ($a_schedules[$_GET['id']]) {
		/* make sure rule is not being referenced by any nat or filter rules */
		$is_schedule_referenced = false;
		$referenced_by = false;
		$schedule_name = $a_schedules[$_GET['id']]['name'];

		if(is_array($config['filter']['rule'])) {
			foreach($config['filter']['rule'] as $rule) {
				//check for this later once this is established
				if ($rule['sched'] == $schedule_name){
					$referenced_by = $rule['descr'];
					$is_schedule_referenced = true;
					break;
				}
			}
		}

		if($is_schedule_referenced == true) {
			$savemsg = sprintf("Cannot delete Schedule. Currently in use by '%s'.", $referenced_by);
		} else {
			unset($a_schedules[$_GET['id']]);
			write_config();
			header("Location: firewall_schedule.php");
			exit;
		}
	}
}

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php if ($savemsg) print_info_box($savemsg); ?>
<form action="firewall_schedule.php" method="post">
<table class="tabcont" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<table width="100%" class="grids">
				<tr>
					<td class="head">Name</td>
					<td class="head">Time Range(s)</td>
					<td class="head">Description</td>
					<td class="head"></td>
				</tr>
				<?php $i = 0; foreach ($a_schedules as $schedule): ?>
				<tr>
					<td class="cell" ondblclick="document.location='firewall_schedule_edit.php?id=<?=$i;?>';">
						<?=htmlspecialchars($schedule['name']);?>
						<?php
						$schedstatus = filter_get_time_based_rule_status($schedule);
						 if ($schedstatus) { ?>
							<img src="./themes/nuclewall/images/icons/icon_frmfld_time.png" title="Schedule is currently active" >
						 <?php } ?>

					</td>
					<td class="cell" style="min-width:300px;" ondblclick="document.location='firewall_schedule_edit.php?id=<?=$i;?>';">
						<table class="grids times">
						<?php

							foreach($schedule['timerange'] as $timerange) {
									$tempFriendlyTime = "";
									$tempID = "";
									$firstprint = false;
									if ($timerange){
										$dayFriendly = "";
										$tempFriendlyTime = "";

										//get hours
										$temptimerange = $timerange['hour'];
										$temptimeseparator = strrpos($temptimerange, "-");

										$starttime = substr ($temptimerange, 0, $temptimeseparator);
										$stoptime = substr ($temptimerange, $temptimeseparator+1);

										if ($timerange['month']){
											$tempmontharray = explode(",", $timerange['month']);
											$tempdayarray = explode(",",$timerange['day']);
											$arraycounter = 0;
											$firstDayFound = false;
											$firstPrint = false;
											foreach ($tempmontharray as $monthtmp){
												$month = $tempmontharray[$arraycounter];
												$day = $tempdayarray[$arraycounter];

												if (!$firstDayFound)
												{
													$firstDay = $day;
													$firstmonth = $month;
													$firstDayFound = true;
												}

												$currentDay = $day;
												$nextDay = $tempdayarray[$arraycounter+1];
												$currentDay++;
												if (($currentDay != $nextDay) || ($tempmontharray[$arraycounter] != $tempmontharray[$arraycounter+1])){
													if ($firstPrint)
														$dayFriendly .= "<br>";
													$currentDay--;
													if ($currentDay != $firstDay)
														$dayFriendly .= $monthArray[$firstmonth-1] . " " . $firstDay . " - " . $currentDay ;
													else
														$dayFriendly .=  $monthArray[$month-1] . " " . $day;
													$firstDayFound = false;
													$firstPrint = true;
												}
												$arraycounter++;
											}
										}
										else
										{
											$tempdayFriendly = $timerange['position'];
											$firstDayFound = false;
											$tempFriendlyDayArray = explode(",", $tempdayFriendly);
											$currentDay = "";
											$firstDay = "";
											$nextDay = "";
											$counter = 0;
											foreach ($tempFriendlyDayArray as $day){
												if ($day != ""){
													if (!$firstDayFound)
													{
														$firstDay = $tempFriendlyDayArray[$counter];
														$firstDayFound = true;
													}
													$currentDay =$tempFriendlyDayArray[$counter];
													//get next day
													$nextDay = $tempFriendlyDayArray[$counter+1];
													$currentDay++;
													if ($currentDay != $nextDay){
														if ($firstprint)
															$dayFriendly .= "<br>";
														$currentDay--;
														if ($currentDay != $firstDay)
															$dayFriendly .= $dayArray[$firstDay-1] . " - " . $dayArray[$currentDay-1];
														else
															$dayFriendly .= $dayArray[$firstDay-1];
														$firstDayFound = false;
														$firstprint = true;
													}
													$counter++;
												}
											}
										}
										$timeFriendly = $starttime . "-" . $stoptime;
										$description = $timerange['rangedescr'];

							?>
							<tr>
								<td class="times"><?=$dayFriendly;?></td>
								<td class="times"><?=$timeFriendly;?></td>
								<td class="times"><?=$description;?></td>
							</tr><?php } }?>
						</table>
					</td>
					<td class="cell description" ondblclick="document.location='firewall_schedule_edit.php?id=<?=$i;?>';">
						<?=htmlspecialchars($schedule['descr']);?>
					</td>
					<td valign="middle" class="cell tools" style="width:20px; max-width:20px;">
						<a title="Edit" href="firewall_schedule_edit.php?id=<?=$i;?>">
							<i class="icon-edit"></i>
						</a>
					   <a title="Delete" href="firewall_schedule.php?act=del&id=<?=$i;?>" onclick="return confirm('Do you really want to delete this schedule?')">
							<i class="icon-trash"></i>
						</a>
					</td>
				</tr>
				<?php $i++; endforeach; ?>
				<tr>
					<td class="cell" colspan="3"></td>
					<td class="cell tools">
						<a title="Add Schedule" href="firewall_schedule_edit.php">
							<i class="icon-plus"></i>
						</a>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</form>
</div>
</body>
</html>
