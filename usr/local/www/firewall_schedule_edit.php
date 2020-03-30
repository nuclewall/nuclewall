<?php
/*
	firewall_schedule_edit.php

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

function schedulecmp($a, $b) {
	return strcmp($a['name'], $b['name']);
}

function schedule_sort(){
        global $g, $config;

        if (!is_array($config['schedules']['schedule']))
                return;

        usort($config['schedules']['schedule'], "schedulecmp");
}

require('guiconfig.inc');
require_once('functions.inc');
require_once('filter.inc');
require_once('shaper.inc');


$pgtitle = array('FIREWALL ','SCHEDULES ' ,'EDIT');

$starttimehr = 00;
$starttimemin = 00;

$stoptimehr = 23;
$stoptimemin = 59;

$dayArray = array ('Mon', 'Tues', 'Wed', 'Thur', 'Fri', 'Sat', 'Sun');
$monthArray = array ('January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December');

if (!is_array($config['schedules']['schedule']))
	$config['schedules']['schedule'] = array();

$a_schedules = &$config['schedules']['schedule'];


$id = $_GET['id'];
if (isset($_POST['id']))
	$id = $_POST['id'];

if (isset($id) && $a_schedules[$id]) {
	$pconfig['name'] = $a_schedules[$id]['name'];
	$pconfig['descr'] = $a_schedules[$id]['descr'];
	$pconfig['timerange'] = $a_schedules[$id]['timerange'];
	$pconfig['schedlabel'] = $a_schedules[$id]['schedlabel'];
	$getSchedule = true;
}

if ($_POST) {

	if(strtolower($_POST['name']) == "lan")
		$input_errors[] = "Schedule may not be named LAN.";
	if(strtolower($_POST['name']) == "wan")
		$input_errors[] = "Schedule may not be named WAN.";
	if(strtolower($_POST['name']) == "")
		$input_errors[] = "Schedule name cannot be blank.";

	$x = is_validaliasname($_POST['name']);
	if (!isset($x)) {
		$input_errors[] = "Reserved word used for schedule name.";
	} else {
		if (is_validaliasname($_POST['name']) == false)
			$input_errors[] = "The schedule name may only consist of the characters a-z, A-Z, 0-9.";
	}

	foreach ($a_schedules as $schedule) {
		if (isset($id) && ($a_schedules[$id]) && ($a_schedules[$id] === $schedule))
			continue;

		if ($schedule['name'] == $_POST['name']) {
			$input_errors[] = "A Schedule with this name already exists.";
			break;
		}
	}
	$schedule = array();

	$schedule['name'] = $_POST['name'];
	$schedule['descr'] = $_POST['descr'];

	$timerangeFound = false;
	for ($x=0; $x<99; $x++){
		if($_POST['schedule' . $x]) {
			$timerangeFound = true;
			$timeparts = array();
			$firstprint = false;
			$timestr = $_POST['schedule' . $x];
			$timehourstr = $_POST['starttime' . $x];
			$timehourstr .= "-";
			$timehourstr .= $_POST['stoptime' . $x];
			$timedescrstr = htmlentities($_POST['timedescr' . $x], ENT_QUOTES, 'UTF-8');
			$dashpos = strpos($timestr, '-');
			if ($dashpos === false)
			{
				$timeparts['position'] = $timestr;
			}
			else
			{
				$tempindarray = array();
				$monthstr = "";
				$daystr = "";
				$tempindarray = explode(",", $timestr);
				foreach ($tempindarray as $currentselection)
				{
					if ($currentselection){
						if ($firstprint)
						{
							$monthstr .= ",";
							$daystr .= ",";
						}
						$tempstr = "";
						$monthpos = strpos($currentselection, "m");
						$daypos = strpos($currentselection, "d");
						$monthstr .= substr($currentselection, $monthpos+1, $daypos-$monthpos-1);
						$daystr .=  substr($currentselection, $daypos+1);
						$firstprint = true;
					}
				}
				$timeparts['month'] = $monthstr;
				$timeparts['day'] = $daystr;
			}
			$timeparts['hour'] = $timehourstr;
			$timeparts['rangedescr'] = $timedescrstr;
			$schedule['timerange'][$x] = $timeparts;
		}
	}

	if (!$timerangeFound)
		$input_errors[] = "The schedule must have at least one time range configured.";

	if (!$input_errors) {

		if (!empty($pconfig['schedlabel']))
			$schedule['schedlabel'] = $pconfig['schedlabel'];
		else
			$schedule['schedlabel'] = uniqid();

		if (isset($id) && $a_schedules[$id]){
			$a_schedules[$id] = $schedule;
		}
		else{
			$a_schedules[] = $schedule;
		}
		schedule_sort();
		write_config("A schedule added");

		filter_configure();

		header("Location: firewall_schedule.php");
		exit;

	}

	else
	{
		if (!$_POST['schedule0'])
			$getSchedule = false;
		else
			$getSchedule = true;

		$pconfig['name'] = $schedule['name'];
		$pconfig['descr'] = $schedule['descr'];
		$pconfig['timerange'] = $schedule['timerange'];
	}

}
include('head.inc');


$jscriptstr = <<<EOD
<script type="text/javascript">

var daysSelected = "";
var month_array = ['January','February','March','April','May','June','July','August','September','October','November','December'];
var day_array = ['Mon','Tues','Wed','Thur','Fri','Sat','Sun'];
var schCounter = 0;



function repeatExistingDays(){
	var tempstr, tempstrdaypos, week, daypos, dayposdone = "";

	var dayarray = daysSelected.split(",");
	for (i=0; i<=dayarray.length; i++){
		tempstr = dayarray[i];
		tempstrdaypos = tempstr.search("p");
		week = tempstr.substring(1,tempstrdaypos);
		week = parseInt(week);
		dashpos = tempstr.search("-");
		daypos = tempstr.substring(tempstrdaypos+1, dashpos);
		daypos = parseInt(daypos);

		daydone = dayposdone.search(daypos);
		tempstr = 'w' + week + 'p' + daypos;
		daycell = eval('document.getElementById(tempstr)');
		if (daydone == "-1"){
			if (daycell.style.backgroundColor == "lightcoral")
				daytogglerepeating(week,daypos,true);
			else
				daytogglerepeating(week,daypos,false);
			dayposdone += daypos + ",";
		}
	}
}

function daytogglerepeating(week,daypos,bExists){
	var tempstr, daycell, dayoriginal = "";
	for (j=1; j<=53; j++)
	{
		tempstr = 'w' + j + 'p' + daypos;
		daycell = eval('document.getElementById(tempstr)');
		dayoriginalpos =  daysSelected.indexOf(tempstr);

		//if bExists set to true, means cell is already select it
		//unselect it and remove original day from daysSelected string

		if (daycell != null)
		{
			if (bExists){
				daycell.style.backgroundColor = "WHITE";
			}
			else
			{
				daycell.style.backgroundColor = "lightcoral";
			}

			if (dayoriginalpos != "-1")
			{
				dayoriginalend = daysSelected.indexOf(',', dayoriginalpos);
				tempstr = daysSelected.substring(dayoriginalpos, dayoriginalend+1);
				daysSelected = daysSelected.replace(tempstr, "");

			}
		}
	}
}

function daytoggle(id) {
	var runrepeat, tempstr = "";
	var bFoundValid = false;

	iddashpos = id.search("-");
	var tempstrdaypos = id.search("p");
	var week = id.substring(1,tempstrdaypos);
	week = parseInt(week);

	if (iddashpos == "-1")
	{
		idmod = id;
		runrepeat = true;
		var daypos = id.substr(tempstrdaypos+1);
	}
	else
	{
		idmod = id.substring(0,iddashpos);
		var daypos = id.substring(tempstrdaypos+1,iddashpos);
	}

	daypos = parseInt(daypos);

	while (!bFoundValid){
		var daycell = document.getElementById(idmod);

		if (daycell != null){
			if (daycell.style.backgroundColor == "RED"){
				daycell.style.backgroundColor = "WHITE";
				str = id + ",";
				daysSelected = daysSelected.replace(str, "");
			}
			else if (daycell.style.backgroundColor == "lightcoral")
			{
				daytogglerepeating(week,daypos,true);
			}
			else //color is white cell
			{
				if (!runrepeat)
				{
					daycell.style.backgroundColor = "RED";
				}
				else
				{
					daycell.style.backgroundColor = "lightcoral";
					daytogglerepeating(week,daypos,false);
				}
				daysSelected += id + ",";
			}
			bFoundValid = true;
		}
		else
		{
			//we found an invalid cell when column was clicked, move up to the next week
			week++;
			tempstr = "w" + week + "p" + daypos;
			idmod = tempstr;
		}
	}
}

function update_month(){
	var indexNum = document.forms[0].monthsel.selectedIndex;
	var selected = document.forms[0].monthsel.options[indexNum].text;

	for (i=0; i<=11; i++){
		option = document.forms[0].monthsel.options[i].text;
		document.popupMonthLayer = eval('document.getElementById (option)');

		if(selected == option) {
			document.popupMonthLayer.style.display="block";
		}
		else
			document.popupMonthLayer.style.display="none";
	}
}

function checkForRanges(){
	if (daysSelected != "")
	{
		alert("You have not saved the specified time range. Please click 'Add Time' button to save the time range.");
		return false;
	}
	else
	{
		return true;
	}
}

function processEntries(){
	var tempstr, starttimehour, starttimemin, stoptimehour, stoptimemin, errors = "";
	var passedValidiation = true;

	//get time specified
	starttimehour = parseInt(document.getElementById("starttimehour").value);
	starttimemin = parseInt(document.getElementById("starttimemin").value);
	stoptimehour = parseInt(document.getElementById("stoptimehour").value);
	stoptimemin = parseInt(document.getElementById("stoptimemin").value);


	//do time checks
	if (starttimehour > stoptimehour)
	{
		errors = "Error: Start Hour cannot be greater than Stop Hour.";
		passedValidiation = false;

	}
	else if (starttimehour == stoptimehour)
	{
		if (starttimemin > stoptimemin){
			errors = "Error: Start Minute cannot be greater than Stop Minute.";
			passedValidiation = false;
		}
	}

	if (passedValidiation){
		addTimeRange();
	}
	else {
		if (errors != "")
			alert(errors);
	}
}

function addTimeRange(){
	var tempdayarray = daysSelected.split(",");
	var tempstr, tempFriendlyDay, starttimehour, starttimemin, stoptimehour, nrtempFriendlyTime, rtempFriendlyTime, nrtempID, rtempID = "";
	var stoptimemin, timeRange, tempstrdaypos, week, daypos, day, month, dashpos, nrtempTime, rtempTime, monthstr, daystr = "";
	rtempFriendlyTime = "";
	nrtempFriendlyTime = "";
	nrtempID = "";
	rtempID = "";
	nrtempTime = "";
	rtempTime = "";
	tempdayarray.sort();
	rtempFriendlyDay = "";
	monthstr = "";
	daystr = "";

	var findCurrentCounter;
	for (u=0; u<99; u++){
		findCurrentCounter = document.getElementById("schedule" + u);
		if (!findCurrentCounter)
		{
			schCounter = u;
			break;
		}
	}

	if (daysSelected != ""){
		//get days selected
		for (i=0; i<tempdayarray.length; i++)
		{
			tempstr = tempdayarray[i];
			if (tempstr != "")
			{
				tempstrdaypos = tempstr.search("p");
				week = tempstr.substring(1,tempstrdaypos);
				week = parseInt(week);
				dashpos = tempstr.search("-");

				if (dashpos != "-1")
				{
					var nonrepeatingfound = true;
					daypos = tempstr.substring(tempstrdaypos+1, dashpos);
					daypos = parseInt(daypos);
					monthpos = tempstr.search("m");
					tempstrdaypos = tempstr.search("d");
					month = tempstr.substring(monthpos+1, tempstrdaypos);
					month = parseInt(month);
					day = tempstr.substring(tempstrdaypos+1);
					day = parseInt(day);
					monthstr += month + ",";
					daystr += day + ",";
					nrtempID += tempstr + ",";
				}
				else
				{
					var repeatingfound = true;
					daypos = tempstr.substr(tempstrdaypos+1);
					daypos = parseInt(daypos);
					rtempFriendlyDay += daypos + ",";
					rtempID += daypos + ",";
				}
			}
		}

		//code below spits out friendly look format for nonrepeating schedules
		var foundEnd = false;
		var firstDayFound = false;
		var firstprint = false;
		var tempFriendlyMonthArray = monthstr.split(",");
		var tempFriendlyDayArray = daystr.split(",");
		var currentDay, firstDay, nextDay, currentMonth, nextMonth, firstDay, firstMonth = "";
		for (k=0; k<tempFriendlyMonthArray.length; k++){
			tempstr = tempFriendlyMonthArray[k];
			if (tempstr != ""){
				if (!firstDayFound)
				{
					firstDay = tempFriendlyDayArray[k];
					firstDay = parseInt(firstDay);
					firstMonth = tempFriendlyMonthArray[k];
					firstMonth = parseInt(firstMonth);
					firstDayFound = true;
				}
				currentDay = tempFriendlyDayArray[k];
				currentDay = parseInt(currentDay);
				//get next day
				nextDay = tempFriendlyDayArray[k+1];
				nextDay = parseInt(nextDay);
				//get next month

				currentDay++;
				if ((currentDay != nextDay) || (tempFriendlyMonthArray[k] != tempFriendlyMonthArray[k+1])){
					if (firstprint)
						nrtempFriendlyTime += ", ";
					currentDay--;
					if (currentDay != firstDay)
						nrtempFriendlyTime += month_array[firstMonth-1] + " " + firstDay + "-" + currentDay;
					else
						nrtempFriendlyTime += month_array[firstMonth-1] + " " + currentDay;
					firstDayFound = false;
					firstprint = true;
				}
			}
		}

		//code below spits out friendly look format for repeating schedules
		foundEnd = false;
		firstDayFound = false;
		firstprint = false;
		tempFriendlyDayArray = rtempFriendlyDay.split(",");
		tempFriendlyDayArray.sort();
		currentDay, firstDay, nextDay = "";
		for (k=0; k<tempFriendlyDayArray.length; k++){
			tempstr = tempFriendlyDayArray[k];
			if (tempstr != ""){
				if (!firstDayFound)
				{
					firstDay = tempFriendlyDayArray[k];
					firstDay = parseInt(firstDay);
					firstDayFound = true;
				}
				currentDay = tempFriendlyDayArray[k];
				currentDay = parseInt(currentDay);
				//get next day
				nextDay = tempFriendlyDayArray[k+1];
				nextDay = parseInt(nextDay);
				currentDay++;
				if (currentDay != nextDay){
					if (firstprint)
						rtempFriendlyTime += ", ";
					currentDay--;
					if (currentDay != firstDay)
						rtempFriendlyTime += day_array[firstDay-1] + " - " + day_array[currentDay-1];
					else
						rtempFriendlyTime += day_array[firstDay-1];
					firstDayFound = false;
					firstprint = true;
				}
			}
		}

		//sort the tempID
		var tempsortArray = rtempID.split(",");
		var isFirstdone = false;
		tempsortArray.sort();
		//clear tempID
		rtempID = "";
		for (t=0; t<tempsortArray.length; t++)
		{
			if (tempsortArray[t] != ""){
				if (!isFirstdone){
					rtempID += tempsortArray[t];
					isFirstdone = true;
				}
				else
					rtempID += "," + tempsortArray[t];
			}
		}


		//get time specified
		starttimehour =  document.getElementById("starttimehour").value
		starttimemin = document.getElementById("starttimemin").value;
		stoptimehour = document.getElementById("stoptimehour").value;
		stoptimemin = document.getElementById("stoptimemin").value;

		timeRange = "||" + starttimehour + ":";
		timeRange += starttimemin + "-";
		timeRange += stoptimehour + ":";
		timeRange += stoptimemin;

		//get description for time range
		var tempdescr = document.getElementById("timerangedescr").value

		if (nonrepeatingfound){
			nrtempTime += nrtempID;
			//add time ranges
			nrtempTime += timeRange;
			//add description
			nrtempTime += "||" + tempdescr;
			insertElements(nrtempFriendlyTime, starttimehour, starttimemin, stoptimehour, stoptimemin, tempdescr, nrtempTime, nrtempID);
		}

		if (repeatingfound){
			rtempTime += rtempID;
			//add time ranges
			rtempTime += timeRange;
			//add description
			rtempTime += "||" + tempdescr;
			insertElements(rtempFriendlyTime, starttimehour, starttimemin, stoptimehour, stoptimemin, tempdescr, rtempTime, rtempID);
		}

	}
	else
	{
		//no days were selected, alert user
		alert ("You must select at least 1 day before adding time");
	}
}

function insertElements(tempFriendlyTime, starttimehour, starttimemin, stoptimehour, stoptimemin, tempdescr, tempTime, tempID){

		//add it to the schedule list
		d = document;
		tbody = d.getElementById("scheduletable").getElementsByTagName("tbody").item(0);
		tr = d.createElement("tr");
		td = d.createElement("td");
		td.innerHTML= tempFriendlyTime;
		td.className = "times day";
		tr.appendChild(td);

		td = d.createElement("td");
		td.innerHTML = "<input type='hidden' name='starttime" + schCounter + "' id='starttime" + schCounter + "' value='" + starttimehour + ":" + starttimemin + "'>";
		td.innerHTML += "<input type='hidden' name='stoptime" + schCounter + "' id='stoptime" + schCounter + "' value='" + stoptimehour + ":" + stoptimemin + "'>";
		td.innerHTML += starttimehour + ":" + starttimemin + " - " + stoptimehour + ":" + stoptimemin;
		td.className = "times hour";
		tr.appendChild(td);

		td = d.createElement("td");
		td.innerHTML="<input type='hidden' name='timedescr" + schCounter + "' id='timedescr" + schCounter + "' value='" + tempdescr + "'>";
		td.innerHTML += tempdescr;
		td.className = "cell description";
		tr.appendChild(td);

		td = d.createElement("td");
		td.innerHTML = "<a onclick='editRow(\"" + tempTime + "\",this); return false;' href='#'><i class=\"icon-edit\"></i></a>";
		td.innerHTML += "<a onclick='removeRow(this); return false;' href='#'><i class=\"icon-trash\"></i></a>";
		td.innerHTML += "<input type='hidden' id='schedule" + schCounter + "' name='schedule" + schCounter + "' value='" + tempID + "'>";
		td.className = "cell tools";
		tr.appendChild(td);
		tbody.appendChild(tr);

		schCounter++;

		//reset calendar and time and descr
		clearCalendar();
		clearTime();
		clearDescr();
}


function clearCalendar(){
	var tempstr, daycell = "";
	//clear days selected
	daysSelected = "";
	//loop through all 52 weeks
	for (j=1; j<=53; j++)
	{
		//loop through all 7 days
		for (k=1; k<8; k++){
			tempstr = 'w' + j + 'p' + k;
			daycell = eval('document.getElementById(tempstr)');
			if (daycell != null){
				daycell.style.backgroundColor = "WHITE";
			}
		}
	}
}

function clearTime(){
	document.getElementById("starttimehour").value = $starttimehr;
	document.getElementById("starttimemin").value = $starttimemin;
	document.getElementById("stoptimehour").value = $stoptimehr;
	document.getElementById("stoptimemin").value = $stoptimemin;
}

function clearDescr(){
	document.getElementById("timerangedescr").value = "";
}

function editRow(incTime, el) {
	var check = checkForRanges();

	if (check){

		//reset calendar and time
		clearCalendar();
		clearTime();

		var starttimehour, descr, days, tempstr, starttimemin, hours, stoptimehour, stoptimemin = "";

		tempArray = incTime.split ("||");

		days = tempArray[0];
		hours = tempArray[1];
		descr = tempArray[2];

		var tempdayArray = days.split(",");
		var temphourArray = hours.split("-");
		tempstr = temphourArray[0];
		var temphourArray2 = tempstr.split(":");

		document.getElementById("starttimehour").value = temphourArray2[0];
		document.getElementById("starttimemin").value = temphourArray2[1];

		tempstr = temphourArray[1];
		temphourArray2 = tempstr.split(":");

		document.getElementById("stoptimehour").value = temphourArray2[0];
		document.getElementById("stoptimemin").value = temphourArray2[1];

		document.getElementById("timerangedescr").value = descr;

		//toggle the appropriate days
		for (i=0; i<tempdayArray.length; i++)
		{
			if (tempdayArray[i]){
				var tempweekstr = tempdayArray[i];
				dashpos = tempweekstr.search("-");

				if (dashpos == "-1")
				{
					tempstr = "w2p" + tempdayArray[i];
				}
				else
				{
					tempstr = tempdayArray[i];
				}
				daytoggle(tempstr);
			}
		}
		removeRownoprompt(el);
	}
}

function removeRownoprompt(el) {
    var cel;
    while (el && el.nodeName.toLowerCase() != "tr")
	    el = el.parentNode;

    if (el && el.parentNode) {
	cel = el.getElementsByTagName("td").item(0);
	el.parentNode.removeChild(el);
    }
}


function removeRow(el) {
	var check = confirm ("Do you really want to delete this time range?");
	if (check){
	    var cel;
	    while (el && el.nodeName.toLowerCase() != "tr")
		    el = el.parentNode;

	    if (el && el.parentNode) {
		cel = el.getElementsByTagName("td").item(0);
		el.parentNode.removeChild(el);
	    }
	}
}

</script>
EOD;
?>
<style>
select {
margin-right: 5px;
margin-left: 1px;
}
</style>
</head>
<body onload="<?= $jsevents["body"]["onload"] ?>">

<?php include('fbegin.inc');	echo $jscriptstr; ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>
<div id="inputerrors"></div>

<form action="firewall_schedule_edit.php" method="post" name="iform" id="iform">
<table cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td colspan="2" valign="top" class="listtopic">EDIT SCHEDULE</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Schedule Name</td>
					<td class="vtable">
						<?php if(is_schedule_inuse($pconfig['name']) == true): ?>
							<input name="name" type="hidden" id="name"  value="<?=htmlspecialchars($pconfig['name']);?>" />
						<?php echo $pconfig['name']; ?>
							<p><b>Note: </b>This schedule is in use so the name may not be modified!.</p>
						<?php else: ?>
							<input name="name" type="text" id="name" maxlength="40" value="<?=htmlspecialchars($pconfig['name']);?>"><br>
							The name of the schedule may only consist of the characters a-z, A-Z and 0-9.
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Description</td>
					<td class="vtable">
						<input name="descr" type="text" id="descr" maxlength="40" value="<?=htmlspecialchars($pconfig['descr']);?>"><br>
						You may enter a description here for your reference.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Month</td>
					<td class="vtable">
						<select name="monthsel" id="monthsel" onchange="update_month();">
							<?php
							$monthcounter = date("n");
							$monthlimit = $monthcounter + 12;
							$yearcounter = date("Y");
							for ($k=0; $k<12; $k++){?>
								<option value="<?php echo $monthcounter;?>"><?php echo strftime("%B %Y", mktime(0, 0, 0, date($monthcounter), 1, date($yearcounter)));?></option>
							<?php
							if ($monthcounter == 12)
								{
									$monthcounter = 1;
									$yearcounter++;
								}
								else
								{
									$monthcounter++;
								}
							} ?>
						</select>
						<?php
						$firstmonth = TRUE;
						$monthcounter = date("n");
						$yearcounter = date("Y");
						for ($k=0; $k<12; $k++){
							$firstdayofmonth = date("w", mktime(0, 0, 0, date($monthcounter), 1, date($yearcounter)));
							if ($firstdayofmonth == 0)
								$firstdayofmonth = 7;

							$daycounter = 1;
							//number of day in month
							$numberofdays = date("t", mktime(0, 0, 0, date($monthcounter), 1, date($yearcounter)));
							$firstdayprinted = FALSE;
							$lasttr = FALSE;
							$positioncounter = 1;
							?>
								<div id="<?php echo strftime("%B %Y", mktime(0, 0, 0, date($monthcounter), 1, date($yearcounter)));?>" z-index:-1000; style="position:relative; display:<?php if($firstmonth)echo "block";else echo "none";?>">
									<table class="grids" id="calTable">
										<tr>
											<td colspan="7" align="center"><b>
												<?php echo strftime("%B %Y", mktime(0, 0, 0, date($monthcounter), 1, date($yearcounter)));?></b>
											</td>
										</tr>
										<tr>
											<td align=center class="head" style="cursor: pointer;" onClick="daytoggle('w1p1');"><u><b>Mon</b></u></td>
											<td align=center class="head" style="cursor: pointer;" onClick="daytoggle('w1p2');"><u><b>Tue</b></u></td>
											<td align=center class="head" style="cursor: pointer;" onClick="daytoggle('w1p3');"><u><b>Wed</b></u></td>
											<td align=center class="head" style="cursor: pointer;" onClick="daytoggle('w1p4');"><u><b>Thu</b></u></td>
											<td align=center class="head" style="cursor: pointer;" onClick="daytoggle('w1p5');"><u><b>Fri</b></u></td>
											<td align=center class="head" style="cursor: pointer;" onClick="daytoggle('w1p6');"><u><b>Sat</b></u></td>
											<td align=center class="head" style="cursor: pointer;" onClick="daytoggle('w1p7');"><u><b>Sun</b></u></td>
										</tr>
										<?php
										$firstmonth = FALSE;
										while ($daycounter<=$numberofdays){
											$weekcounter =  date("W", mktime(0, 0, 0, date($monthcounter), date($daycounter), date($yearcounter)));
											$weekcounter = ltrim($weekcounter, "0");
											if ($positioncounter == 1)
											{
												echo "<tr>";
											}
											if ($firstdayofmonth == $positioncounter){?>
												<td align=center style="cursor: pointer;" class="wall" id="w<?=$weekcounter;?>p<?=$positioncounter;?>" onClick="daytoggle('w<?=$weekcounter;?>p<?=$positioncounter;?>-m<?=$monthcounter;?>d<?=$daycounter;?>');">
												<?php echo $daycounter;
												$daycounter++;
												$firstdayprinted = TRUE;
												echo "</td>";
											}
											elseif ($firstdayprinted == TRUE && $daycounter <= $numberofdays){?>
												<td align=center style="cursor: pointer;" class="wall" id="w<?=$weekcounter;?>p<?=$positioncounter;?>" onClick="daytoggle('w<?=$weekcounter;?>p<?=$positioncounter;?>-m<?=$monthcounter;?>d<?=$daycounter;?>');">
												<?php echo $daycounter;
												$daycounter++;
												echo "</td>";
											}
											else
											{
												echo "<td align=center class=\"wall\"></td>";
											}

											if ($positioncounter ==7){
												$positioncounter = 1;
												echo "</tr>";
											}
											else{
												$positioncounter++;
											}

										}//end while loop?>
									</table>
								</div>
						<?php

							if ($monthcounter == 12)
							{
								$monthcounter = 1;
								$yearcounter++;
							}
							else
							{
								$monthcounter++;
							}
						} //end for loop
						?>
						Click individual date to select that date only. <br>
						Click the appropriate weekday header to select all occurences of that weekday.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Time</td>
					<td class="vtable">
						<table width="100%" class="grids" cellspacing=2>
							<tr>
								<td class="head">Start Time</td>
								<td class="head">Stop Time</td>
							</tr>
							<tr>
								<td class="times">
									Hour
									<select name="starttimehour" id="starttimehour">
										<?php
											for ($i=0; $i<24; $i++)
											{
												echo "<option value=\"$i\">";
												echo $i;
												echo "</option>";
											}
										?>
									</select>
									Min.
									<select name="starttimemin" id="starttimemin">
										<option value="00">00</option>
										<option value="15">15</option>
										<option value="30">30</option>
										<option value="45">45</option>
										<option value="59">59</option>
									</select>
								</td>
								<td class="times">
									Hour
									<select name="stoptimehour" id="stoptimehour">
									<?php
											for ($i=0; $i<24; $i++)
											{
												if ($i==23)
													$selected = "selected";
												else
													$selected = "";

												echo "<option value=\"$i\" $selected>";
												echo $i;
												echo "</option>";
											}
										?>
									</select>
									Min.
									<select name="stoptimemin" id="stoptimemin">
										<option value="00">00</option>
										<option value="15">15</option>
										<option value="30">30</option>
										<option value="45">45</option>
										<option value="59" SELECTED>59</option>
									</select>
								</td>
							</tr>
						</table>
						Select the time range for the day(s) selected on the Month(s) above. A full day is 0:00-23:59.
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell">Time Range Description</td>
					<td class="vtable">
						<input name="timerangedescr" type="text" id="timerangedescr" maxlength="40">
						<br>You may enter a description here for your reference.
					</td>
				</tr>
				<tr>
					<td class="vncell" valign="top"></td>
					<td class="vtable">
						<input type="button" value="Add Time"  class="btn btn-inverse btn-mini"  onclick="javascript:processEntries();">
						<input type="button" value="Clear Selection" class="btn btn-mini" onclick="javascript:clearCalendar(); clearTime(); clearDescr();">
					</td>
				</tr>
				<tr>
					<td colspan="2" valign="top" class="listtopic">SCHEDULES</td>
				</tr>
				<tr>
					<td colspan="2" class="vtable">
						<table width="100%" class="grids" id="scheduletable">
							<tr>
								<td class="head">Day(s)</td>
								<td class="head">Time</td>
								<td class="head">Description</td>
								<td class="head" colspan="2"></td>
							</tr>
							<?php
							if ($getSchedule){
								$counter = 0;

								foreach($pconfig['timerange'] as $timerange) {
									$tempFriendlyTime = "";
									$tempID = "";
									if ($timerange){
										$dayFriendly = "";
										$tempFriendlyTime = "";
										$timedescr = $timerange['rangedescr'];

										//get hours
										$temptimerange = $timerange['hour'];
										$temptimeseparator = strrpos($temptimerange, "-");

										$starttime = substr ($temptimerange, 0, $temptimeseparator);
										$stoptime = substr ($temptimerange, $temptimeseparator+1);
										$currentDay = "";
										$firstDay = "";
										$nextDay = "";
										$foundEnd = false;
										$firstDayFound = false;
										$firstPrint = false;
										$firstprint2 = false;

										if ($timerange['month']){
											$tempmontharray = explode(",", $timerange['month']);
											$tempdayarray = explode(",",$timerange['day']);
											$arraycounter = 0;
											foreach ($tempmontharray as $monthtmp){
												$month = $tempmontharray[$arraycounter];
												$day = $tempdayarray[$arraycounter];
												$daypos = date("w", mktime(0, 0, 0, date($month), date($day), date("Y")));
												//if sunday, set position to 7 to get correct week number. This is due to php limitations on ISO-8601. When we move to php5.1 we can change this.
												if ($daypos == 0){
													$daypos = 7;
												}
												$weeknumber = date("W", mktime(0, 0, 0, date($month), date($day), date("Y")));
												$weeknumber = ltrim($weeknumber, "0");

												if ($firstPrint)
												{
													$tempID .= ",";
												}
												$tempID .= "w" . $weeknumber . "p" . $daypos . "-m" .  $month . "d" . $day;
												$firstPrint = true;

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
													if ($firstprint2)
														$tempFriendlyTime .= ", ";
													$currentDay--;
													if ($currentDay != $firstDay)
														$tempFriendlyTime .= $monthArray[$firstmonth-1] . " " . $firstDay . " - " . $currentDay ;
													else
														$tempFriendlyTime .=  $monthArray[$month-1] . " " . $day;
													$firstDayFound = false;
													$firstprint2 = true;
												}
												$arraycounter++;
											}

										}
										else
										{
											$dayFriendly = $timerange['position'];
											$tempID = $dayFriendly;
										}

										$tempTime = $tempID . "||" . $starttime . "-" . $stoptime . "||" . $timedescr;

										$foundEnd = false;
										$firstDayFound = false;
										$firstprint = false;
										$tempFriendlyDayArray = explode(",", $dayFriendly);
										$currentDay = "";
										$firstDay = "";
										$nextDay = "";
										$i = 0;
										if (!$timerange['month']){
											foreach ($tempFriendlyDayArray as $day){
												if ($day != ""){
													if (!$firstDayFound)
													{
														$firstDay = $tempFriendlyDayArray[$i];
														$firstDayFound = true;
													}
													$currentDay =$tempFriendlyDayArray[$i];
													//get next day
													$nextDay = $tempFriendlyDayArray[$i+1];
													$currentDay++;
													if ($currentDay != $nextDay){
														if ($firstprint)
															$tempFriendlyTime .= ", ";
														$currentDay--;
														if ($currentDay != $firstDay)
															$tempFriendlyTime .= $dayArray[$firstDay-1] . " - " . $dayArray[$currentDay-1];
														else
															$tempFriendlyTime .= $dayArray[$firstDay-1];
														$firstDayFound = false;
														$firstprint = true;
													}
													$i++;
												}
											}
										}

								?>
							<tr>
								<td class="times day">
									<?php echo $tempFriendlyTime; ?>
								</td>
								<td class="times hour">
									<?=$starttime; ?> - <?=$stoptime; ?>
									<input type="hidden" name="starttime<?=$counter; ?>" id="starttime<?=$counter; ?>" value="<?=$starttime; ?>">
									<input type="hidden" name="stoptime<?=$counter; ?>" id="stoptime<?=$counter; ?>" value="<?=$stoptime; ?>">
								</td>
								<td class="cell description">
									<?=$timedescr; ?>
									<input type="hidden" name="timedescr<?=$counter; ?>" id="timedescr<?=$counter; ?>" value="<?=$timedescr; ?>">
								</td>
								<td class="cell tools">
									<a onclick='editRow("<?php echo $tempTime; ?>",this); return false;' href='#'>
										<i class="icon-edit"></i>
									</a>
									<a onclick='removeRow(this); return false;' href='#'>
										<i class="icon-trash"></i>
									</a>
									<input type='hidden' id='schedule<?php echo $counter; ?>' name='schedule<?php echo $counter; ?>' value='<?php echo $tempID; ?>'>
								</td>

							</tr>
								<?php
							$counter++;
								}
								}
							}
							?>
						</table>
					</td>
				</tr>
				<tr>
					<td valign="top" class="vncell"></td>
					<td class="vtable">
						<input id="submit" name="submit" type="submit" onclick="return checkForRanges();" class="btn btn-inverse" value="Save" />
						<input id="cancelbutton" name="cancelbutton" type="button" class="btn btn-default" value="Cancel" onclick="history.back()" />
						<?php if (isset($id) && $a_schedules[$id]): ?>
						<input name="id" type="hidden" value="<?=htmlspecialchars($id);?>" />
						<?php endif; ?>
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
