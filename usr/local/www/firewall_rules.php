<?php
/*
	firewall_rules.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.

	part of pfSense (http://www.pfsense.com)
        Copyright (C) 2005 Scott Ullrich (sullrich@gmail.com)

	originally part of m0n0wall (http://m0n0.ch/wall)
	Copyright (C) 2003-2004 Manuel Kasper <mk@neon1.net>.
	All rights reserved.

	Redistribution and use in source and binary forms, with or without
	modification, are permitted provided that the following conditions are met:

	1. Redistributions of source code must retain the above copyright notice,
	   this wall of conditions and the following disclaimer.

	2. Redistributions in binary form must reproduce the above copyright
	   notice, this wall of conditions and the following disclaimer in the
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

require('guiconfig.inc');
require_once('functions.inc');
require_once('filter.inc');
require_once('shaper.inc');

$pgtitle = array('FIREWALL', 'RULES');

if (!is_array($config['filter']['rule']))
{
	$config['filter']['rule'] = array();
}

filter_rules_sort();
$a_filter = &$config['filter']['rule'];

$if = $_GET['if'];
if ($_POST['if'])
	$if = $_POST['if'];

$ifdescs = get_configured_interface_with_descr();

// Drag and drop reordering
if($_REQUEST['dragdroporder'])
{
	$a_filter_before = array();
	$a_filter_order = array();
	$a_filter_order_tmp = array();
	$a_filter_after = array();
	$found = false;
	$drag_order = $_REQUEST['dragtable'];

	for ($i = 0; isset($a_filter[$i]); $i++)
	{
		if(( $_REQUEST['if'] == "FloatingRules" && isset($a_filter[$i]['floating']) ) || ( $a_filter[$i]['interface'] == $_REQUEST['if'] && !isset($a_filter[$i]['floating']) )) {
			$a_filter_order_tmp[] = $a_filter[$i];
			$found = true;
		} else if (!$found)
			$a_filter_before[] = $a_filter[$i];
		else
			$a_filter_after[] = $a_filter[$i];
	}

	for ($i = 0; $i<count($drag_order); $i++)
		$a_filter_order[] = $a_filter_order_tmp[$drag_order[$i]];

	if(count($a_filter_order) < count($a_filter_order_tmp))
	{
		for ($i = 0; $i<count($a_filter_order_tmp); $i++)
			if(!in_array($i, $drag_order))
				$a_filter_order[] = $a_filter_order_tmp[$i];
	}

	$config['filter']['rule'] = array_merge($a_filter_before, $a_filter_order, $a_filter_after);

	$config = write_config();

	mark_subsystem_dirty('filter');
	$undo = array();

	foreach($_REQUEST['dragtable'] as $dt)
		$undo[] = "";

	$counter = 0;

	foreach($_REQUEST['dragtable'] as $dt)
	{
		$undo[$dt] = $counter;
		$counter++;
	}

	foreach($undo as $dt)
		$undotxt .= "&dragtable[]={$dt}";
	Header("Location: firewall_rules.php?if=" . $_REQUEST['if'] . "&undodrag=true" . $undotxt);
	exit;
}

$icmptypes = array(
	"" => "any",
	"echoreq" => "Echo request",
	"echorep" => "Echo reply",
	"unreach" => "Destination unreachable",
	"squench" => "Source quench",
	"redir" => "Redirect",
	"althost" => "Alternate Host",
	"routeradv" => "Router advertisement",
	"routersol" => "Router solicitation",
	"timex" => "Time exceeded",
	"paramprob" => "Invalid IP header",
	"timereq" => "Timestamp",
	"timerep" => "Timestamp reply",
	"inforeq" => "Information request",
	"inforep" => "Information reply",
	"maskreq" => "Address mask request",
	"maskrep" => "Address mask reply"
);

if (is_array($config['ifgroups']['ifgroupentry']))
	foreach($config['ifgroups']['ifgroupentry'] as $ifgen)
		if (have_ruleint_access($ifgen['ifname']))
			$iflist[$ifgen['ifname']] = $ifgen['ifname'];

foreach ($ifdescs as $ifent => $ifdesc)
	if(have_ruleint_access($ifent))
		$iflist[$ifent] = $ifdesc;

pfSense_handle_custom_code("/usr/local/pkg/firewall_rules/interfaces_override");

if (!$if || !isset($iflist[$if]))
{
	if ("any" == $if)
                $if = "FloatingRules";
        else if ("FloatingRules" != $if)
                $if = "wan";
}

if ($_POST)
{
	$pconfig = $_POST;

	if ($_POST['apply'])
	{
		$retval = 0;
		$retval = filter_configure();

		clear_subsystem_dirty('filter');

		pfSense_handle_custom_code("/usr/local/pkg/firewall_rules/apply");

		$savemsg = 'The settings have been applied. The firewall rules are now reloading in the background.';
	}
}

if ($_GET['act'] == "del")
{
	if ($a_filter[$_GET['id']])
	{
		unset($a_filter[$_GET['id']]);

		write_config("A firewall rule deleted");

		mark_subsystem_dirty('filter');
		header("Location: firewall_rules.php?if=" . htmlspecialchars($if));
		exit;
	}
}


if($_REQUEST['savemsg'])
	$savemsg = htmlentities($_REQUEST['savemsg']);

if (isset($_POST['del_x']))
{
	if (is_array($_POST['rule']) && count($_POST['rule']))
	{
		foreach ($_POST['rule'] as $rulei)
		{
			unset($a_filter[$rulei]);
		}

		write_config();
		mark_subsystem_dirty('filter');
		header("Location: firewall_rules.php?if=" . htmlspecialchars($if));
		exit;
	}
}

else if ($_GET['act'] == "toggle")
{
	if ($a_filter[$_GET['id']])
	{
		if(isset($a_filter[$_GET['id']]['disabled']))
			unset($a_filter[$_GET['id']]['disabled']);
		else
			$a_filter[$_GET['id']]['disabled'] = true;

			write_config();
		mark_subsystem_dirty('filter');
		header("Location: firewall_rules.php?if=" . htmlspecialchars($if));
		exit;
	}
}

else
{
	unset($movebtn);
	foreach ($_POST as $pn => $pd)
	{
		if (preg_match("/move_(\d+)_x/", $pn, $matches))
		{
			$movebtn = $matches[1];
			break;
		}
	}

	if(isset($movebtn) && is_array($_POST['rule']) && count($_POST['rule']))
	{
		$a_filter_new = array();

		for ($i = 0; $i < $movebtn; $i++)
		{
			if (!in_array($i, $_POST['rule']))
				$a_filter_new[] = $a_filter[$i];
		}

		for ($i = 0; $i < count($a_filter); $i++)
		{
			if ($i == $movebtn)
				continue;
			if (in_array($i, $_POST['rule']))
				$a_filter_new[] = $a_filter[$i];
		}

		if ($movebtn < count($a_filter))
			$a_filter_new[] = $a_filter[$movebtn];

		for ($i = $movebtn+1; $i < count($a_filter); $i++)
		{
			if (!in_array($i, $_POST['rule']))
				$a_filter_new[] = $a_filter[$i];
		}

		$a_filter = $a_filter_new;
		write_config("A firewall rule moved");
		mark_subsystem_dirty('filter');
		header("Location: firewall_rules.php?if=" . htmlspecialchars($if));
		exit;
	}
}

include('head.inc');

echo "<script type=\"text/javascript\" language=\"javascript\" src=\"/javascript/domTT/domLib.js\"></script>";
echo "<script type=\"text/javascript\" language=\"javascript\" src=\"/javascript/domTT/domTT.js\"></script>";
echo "<script type=\"text/javascript\" language=\"javascript\" src=\"/javascript/domTT/behaviour.js\"></script>";
echo "<script type=\"text/javascript\" language=\"javascript\" src=\"/javascript/domTT/fadomatic.js\"></script>";
?>

<link rel="stylesheet" href="/javascript/chosen/chosen.css" />
</head>
<body>
<script src="javascript/chosen/chosen.proto.js" type="text/javascript"></script>

<?php include('fbegin.inc'); ?>

<form action="firewall_rules.php" method="post">
<script type="text/javascript" language="javascript" src="/javascript/row_toggle.js"></script>
<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (is_subsystem_dirty('filter')): ?>
<?php
	print_info_box_np("The firewall rule configuration has been changed.<br>You must apply the changes in order for them to take effect.", true);
?>
<br>
<?php endif; ?>

<?php
	pfSense_handle_custom_code("/usr/local/pkg/firewall_rules/before_table");
?>
<table cellpadding="0" cellspacing="0">
	<tr>
		<td class="tabnavtbl">
			<?php
				$tab_array = array();
				if ("FloatingRules" == $if)
					$active = true;
				else
					$active = false;

				$tab_array[] = array("Floating", $active, "firewall_rules.php?if=FloatingRules");
				$tabscounter = 0; $i = 0; foreach ($iflist as $ifent => $ifname) {
					if ($ifent == $if)
						$active = true;
					else
						$active = false;
					$tab_array[] = array($ifname, $active, "firewall_rules.php?if={$ifent}");
				}
				display_top_tabs($tab_array);
			?>
		</td>
	</tr>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<table width="100%" class="grids">
						<?php
							pfSense_handle_custom_code("/usr/local/pkg/firewall_rules/before_first_tr");
						?>
							<tr id="frheader">
								<td class="headw"></td>
								<td class="headw"></td>
								<td class="headw">ID</td>
								<?php
									pfSense_handle_custom_code("/usr/local/pkg/firewall_rules/pre_id_tablehead");
								?>
								<td class="headw">Proto</td>
								<td class="headw">Source</td>
								<td class="headw">Port</td>
								<td class="headw">Destination</td>
								<td class="headw">Port</td>
								<td class="headw">Gateway</td>
								<td class="headw">Schedule</td>
								<?php
									pfSense_handle_custom_code("/usr/local/pkg/firewall_rules/pre_desc_tablehead");
								?>
								<td class="headw">Description</td>
								<td class="hwall">
									<?php
										$nrules = 0;
										for ($i = 0; isset($a_filter[$i]); $i++) {
											$filterent = $a_filter[$i];
											if ($filterent['interface'] != $if && !isset($filterent['floating']))
												continue;
											if (isset($filterent['floating']) && "FloatingRules" != $if)
												continue;
											$nrules++;
										}
									?>
									<?php //if ($nrules == 0): ?>
											<!--<i class="icon-trash icon-white"></i>-->
									<?php //endif; ?>
										<a title="Add rule to top" href="firewall_rules_edit.php?if=<?=htmlspecialchars($if);?>&after=-1">
											<i class="icon-plus icon-white"></i>
										</a>
								</td>
							</tr>

							<?php   // Show the anti-lockout rule if it's enabled, and we are on LAN with an if count > 1, or WAN with an if count of 1.
								if (!isset($config['system']['webgui']['noantilockout']) &&
									(((count($config['interfaces']) > 1) && ($if == 'lan'))
									|| ((count($config['interfaces']) == 1) && ($if == 'wan')))):

									$alports = implode(', ', filter_get_antilockout_ports(true));
							?>

							<tr id="antilockout">
							<td class="wall"></td>
								<td class="wall">
									<img src="./themes/nuclewall/images/icons/icon_pass.gif">
								</td>
								<td class="wall"></td>
								<?php
									pfSense_handle_custom_code("/usr/local/pkg/firewall_rules/pre_id_tr_antilockout");
								?>
								<td class="wall">*</td>
								<td class="wall">*</td>
								<td class="wall">*</td>
								<td class="wall"><?=$iflist[$if];?> Address</td>
								<td class="wall"><?= $alports ?></td>
								<td class="wall">*</td>
								<td class="wall">*</td>
								<td class="wall description">Anti-Lockout Rule</td>
								<td class="wall tools">
									<a title="Edit" href="system_advanced_admin.php">
										<i class="icon-edit"></i>
									</a>
								</td>
							</tr>

							<?php endif; ?>
							<?php if (isset($config['interfaces'][$if]['blockpriv'])): ?>

							<tr id="frrfc1918">
								<td class="wall"></td>
								<td class="wall">
									<img src="./themes/nuclewall/images/icons/icon_block.gif">
								</td>
								<td class="wall"></td>
								<td class="wall">*</td>
								<td class="wall">RFC 1918 networks</td>
								<td class="wall">*</td>
								<td class="wall">*</td>
								<td class="wall">*</td>
								<td class="wall">*</td>
								<td class="wall">*</td>
								<td class="wall description">Block private networks</td>
								<td class="wall tools">
									<a href="interfaces.php?if=<?=htmlspecialchars($if)?>#rfc1918">
										<i class="icon-edit"></i>
									</a>
								</td>
							</tr>

							<?php endif; ?>
							<?php if (isset($config['interfaces'][$if]['blockbogons'])): ?>

							<tr id="frrfc1918">
								<td class="wall"></td>
								<td class="wall">
									<img src="./themes/nuclewall/images/icons/icon_block.gif">
								</td>
								<td class="wall" ></td>
								<td class="wall" >*</td>
								<td class="wall" >Reserved/not assigned by IANA</td>
								<td class="wall" >*</td>
								<td class="wall" >*</td>
								<td class="wall" >*</td>
								<td class="wall" >*</td>
								<td class="wall" >*</td>
								<td class="wall description">Block bogon networks</td>
								<td class="wall tools">
									<a title="Edit" href="interfaces.php?if=<?=htmlspecialchars($if)?>#rfc1918">
										<i class="icon-edit"></i>
									</a>
								</td>
							</tr>
							<?php endif; ?>



							<tbody id="dragtable">
								<?php $nrules = 0; for ($i = 0; isset($a_filter[$i]); $i++):
									pfSense_handle_custom_code("/usr/local/pkg/firewall_rules/row_start");
									$filterent = $a_filter[$i];
									if ($filterent['interface'] != $if && !isset($filterent['floating']))
										continue;
									if (isset($filterent['floating']) && "FloatingRules" != $if)
										continue;
									$isadvset = firewall_check_for_advanced_options($filterent);
									if($isadvset)
										$advanced_set = "<img src=\"./themes/nuclewall/images/icons/icon_advanced.gif\" title=\"" . "advanced settings applied" . ": {$isadvset}\">";
									else
										$advanced_set = "";
								?>
								<tr id="fr<?=$nrules;?>">
									<td class="wall">
										<input type="checkbox" id="frc<?=$nrules;?>" name="rule[]" value="<?=$i;?>" onClick="fr_bgcolor('<?=$nrules;?>')">
										<?php echo $advanced_set; ?>
									</td>
									<td class="wall">
										<?php if ($filterent['type'] == "block")
												$iconfn = "block";
											else if ($filterent['type'] == "reject") {
												$iconfn = "reject";
											} else
												$iconfn = "pass";
											if (isset($filterent['disabled'])) {
												$textss = "<span class=\"gray\">";
												$textse = "</span>";
												$iconfn .= "_d";
											} else {
												$textss = $textse = "";
											}
										?>
										<a href="?if=<?=htmlspecialchars($if);?>&act=toggle&id=<?=$i;?>">
											<img src="./themes/nuclewall/images/icons/icon_<?=$iconfn;?>.gif">
										</a>
											<?php if (isset($filterent['log'])):
												$iconfnlog = "log_s";
												if (isset($filterent['disabled']))
													$iconfnlog .= "_d";
											?>

										<?php endif; ?>
									</td>
										<?php
											$alias_src_span_begin = "";
											$alias_src_port_span_begin = "";
											$alias_dst_span_begin = "";
											$alias_dst_port_span_begin = "";

											$alias_popup = rule_popup($filterent['source']['address'],pprint_port($filterent['source']['port']),$filterent['destination']['address'],pprint_port($filterent['destination']['port']));

											$alias_src_span_begin = $alias_popup["src"];

											$alias_src_port_span_begin = $alias_popup["srcport"];

											$alias_dst_span_begin = $alias_popup["dst"];

											$alias_dst_port_span_begin = $alias_popup["dstport"];

											//build Schedule popup box
											$a_schedules = &$config['schedules']['schedule'];
											$schedule_span_begin = "";
											$schedule_span_end = "";
											$sched_caption_escaped = "";
											$sched_content = "";
											$schedstatus = false;
											$dayArray = array ('Mon','Tues','Wed','Thur','Fri','Sat','Sun');
											$monthArray = array ('January','February','March','April','May','June','July','August','September','October','November','December');
											if($config['schedules']['schedule'] <> "" and is_array($config['schedules']['schedule'])) {
												foreach ($a_schedules as $schedule)
												{
													if ($schedule['name'] == $filterent['sched'] ){
														$schedstatus = filter_get_time_based_rule_status($schedule);

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
																				$dayFriendly .= ", ";
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
																					$dayFriendly .= ", ";
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
																$timeFriendly = $starttime . " - " . $stoptime;
																$description = $timerange['rangedescr'];
																$sched_content .= $dayFriendly . "; " . $timeFriendly . "<br>";
															}
														}
														$sched_caption_escaped = str_replace("'", "\'", $schedule['descr']);
														$schedule_span_begin = "<span onmouseover=\"domTT_activate(this, event, 'content', '<p>{$sched_caption_escaped}</p><p>{$sched_content}</p>', 'trail', true, 'delay', 0, 'fade', 'both', 'fadeMax', 93, 'styleClass', 'niceTitle');\" onmouseout=\"this.style.color = ''; domTT_mouseout(this, event);\"><U>";
														$schedule_span_end = "</U></span>";
													}
												}
											}
											$printicon = false;
											$alttext = "";
										?>
									<td class="wall" onClick="fr_toggle(<?=$nrules;?>)" id="frd<?=$nrules;?>" ondblclick="document.location='firewall_rules_edit.php?id=<?=$i;?>';">
										<?=$textss;?>
										<?php if (isset($filterent['id'])) echo $filterent['id']; else echo ""; ?>
										<?=$textse;?>
									</td>
										<?php
											pfSense_handle_custom_code("/usr/local/pkg/firewall_rules/pre_id_tr");
										?>
									<td class="wall" onClick="fr_toggle(<?=$nrules;?>)" id="frd<?=$nrules;?>" ondblclick="document.location='firewall_rules_edit.php?id=<?=$i;?>';">
										<?=$textss;?>
										<?php
											if (isset($filterent['protocol'])) {
												echo strtoupper($filterent['protocol']);
												if (strtoupper($filterent['protocol']) == "ICMP" && !empty($filterent['icmptype'])) {
													echo ' <span title="ICMP type: ' . $icmptypes[$filterent['icmptype']] . '"><u>';
													echo $filterent['icmptype'];
													echo '</u></span>';
												}
											} else echo "*";
										?>
										<?=$textse;?>
									</td>
									<td class="wall" onClick="fr_toggle(<?=$nrules;?>)" id="frd<?=$nrules;?>" ondblclick="document.location='firewall_rules_edit.php?id=<?=$i;?>';">
										<?=$textss;?><?php echo $alias_src_span_begin;?><?php echo htmlspecialchars(pprint_address($filterent['source']));?><?php echo $alias_src_span_end;?>
										<?=$textse;?>
									</td>
									<td class="wall" onClick="fr_toggle(<?=$nrules;?>)" id="frd<?=$nrules;?>" ondblclick="document.location='firewall_rules_edit.php?id=<?=$i;?>';">
										<?=$textss;?><?php echo $alias_src_port_span_begin;?><?php echo htmlspecialchars(pprint_port($filterent['source']['port'])); ?><?php echo $alias_src_port_span_end;?>
										<?=$textse;?>
									</td>
									<td class="wall" onClick="fr_toggle(<?=$nrules;?>)" id="frd<?=$nrules;?>" ondblclick="document.location='firewall_rules_edit.php?id=<?=$i;?>';">
										<?=$textss;?><?php echo $alias_dst_span_begin;?><?php echo htmlspecialchars(pprint_address($filterent['destination'])); ?><?php echo $alias_dst_span_end;?>
										<?=$textse;?>
									</td>
									<td class="wall" onClick="fr_toggle(<?=$nrules;?>)" id="frd<?=$nrules;?>" ondblclick="document.location='firewall_rules_edit.php?id=<?=$i;?>';">
										<?=$textss;?><?php echo $alias_dst_port_span_begin;?><?php echo htmlspecialchars(pprint_port($filterent['destination']['port'])); ?><?php echo $alias_dst_port_span_end;?>
										<?=$textse;?>
									</td>
									<td class="wall" onClick="fr_toggle(<?=$nrules;?>)" id="frd<?=$nrules;?>" ondblclick="document.location='firewall_rules_edit.php?id=<?=$i;?>';">
										<?=$textss;?><?php if (isset($config['interfaces'][$filterent['gateway']]['descr'])) echo htmlspecialchars($config['interfaces'][$filterent['gateway']]['descr']); else  echo htmlspecialchars(pprint_port($filterent['gateway'])); ?>
										<?=$textse;?>
									</td>
									<td class="wall" onClick="fr_toggle(<?=$nrules;?>)" id="frd<?=$nrules;?>" ondblclick="document.location='firewall_rules_edit.php?id=<?=$i;?>';">
										<?php if ($printicon) { ?>
											<?php } ?>
											<?=$textss;?>
											<?php echo $schedule_span_begin;?>
											<?=htmlspecialchars($filterent['sched']);?>
											<?php echo $schedule_span_end; ?>
											<?=$textse;?>
									</td>
										<?php
											pfSense_handle_custom_code("/usr/local/pkg/firewall_rules/pre_descr_tr");
										?>
									<td class="wall description" onClick="fr_toggle(<?=$nrules;?>)" ondblclick="document.location='firewall_rules_edit.php?id=<?=$i;?>';">
										<?=$textss;?><?=htmlspecialchars($filterent['descr']);?><?=$textse;?>
									</td>
									<td class="wall tools" style="padding:4px;">
										<input style=" margin-top: 5px;" name="move_<?=$i;?>" type="image" src="./themes/nuclewall/images/icons/icon_top.png" title="Move the selected rule top of this rule" onMouseOver="fr_insline(<?=$nrules;?>, true)" onMouseOut="fr_insline(<?=$nrules;?>, false)">

										<a title="Delete" href="firewall_rules.php?act=del&if=<?=htmlspecialchars($if);?>&id=<?=$i;?>">
											<i class="icon-trash" style="margin-top: -2px; padding-left: 0px;" onclick="return confirm('Do you really want to delete this rule?')"></i>
										</a>
										<a title="Add new rules based this rule" href="firewall_rules_edit.php?dup=<?=$i;?>">
											<i class="icon-plus" style="margin-top: -2px;"></i>
										</a>
										<a title="Edit" href="firewall_rules_edit.php?id=<?=$i;?>">
											<i class="icon-edit" style="margin-top: -2px;"></i>
										</a>
									</td>
								</tr>
								<?php $nrules++; endfor; ?>

								<?php if ($nrules == 0): ?>
								<tr>
									<td class="wall" colspan="13" style="text-align: left;">
										<span class="gray" style="margin-left: 30px;">
										    No rules are currently defined for this interface.
											All incoming connections on this interface will be blocked until you add pass rules.
											Click <i class="icon-plus"></i> to add a new rule.
										</span>
									</td>
								</tr>
							<?php endif; ?>

							</tbody>
							<tr id="fr<?=$nrules;?>">
								<?php
									pfSense_handle_custom_code("/usr/local/pkg/firewall_rules/pre_id_tr_belowtable");
								?>
								<td class="wall" colspan="11"></td>
								<td class="wall tools" style="padding-bottom: 4px;">
									<?php if ($nrules != 0): ?>
										<input name="move_<?=$i;?>" type="image" src="./themes/nuclewall/images/icons/icon_top.png" title="Move selected rules to end" onMouseOver="fr_insline(<?=$nrules;?>, true)" onMouseOut="fr_insline(<?=$nrules;?>, false)">
										<input name="del" type="image" src="./themes/nuclewall/images/icons/icon_x.png" title="Delete selected rules" onclick="return confirm('Do you really want to delete the selected rules?')">
									<?php endif; ?>
									<a title="Add rule" href="firewall_rules_edit.php?if=<?=htmlspecialchars($if);?>">
										<i class="icon-plus" style="margin-top: -2px;"></i>
									</a>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<input type="hidden" name="if" value="<?=htmlspecialchars($if);?>">
</form>
</div>
</body>
</html>
