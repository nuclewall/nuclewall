<?php
/*
	Copyright (C) 2013-2015 Ogün AÇIK
	All rights reserved.

    Copyright (C) 2004, 2005 Scott Ullrich
    All rights reserved.

    Originally part of m0n0wall (http://m0n0.ch/wall)
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
    oR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
    SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
    INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
    CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
    ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
    POSSIBILITY OF SUCH DAMAGE.
*/

ini_set('output_buffering','true');
ob_start(null, "1000");

require_once('functions.inc');
require_once('guiconfig.inc');

$directory = "/usr/local/www/widgets/widgets/";
$dirhandle  = opendir($directory);
$filename = '';
$widgetnames = array();
$widgetfiles = array();
$widgetlist = array();

while (false !== ($filename = readdir($dirhandle))) {
	$periodpos = strpos($filename, ".");
	$widgetname = substr($filename, 0, $periodpos);
	$widgetnames[] = $widgetname;
	if ($widgetname != "sistem")
		$widgetfiles[] = $filename;
}

if (!is_array($config['widgets'])) {
	$config['widgets'] = array();
}

	if ($_POST && $_POST['submit']) {
		$config['widgets']['sequence'] = $_POST['sequence'];

		foreach ($widgetnames as $widget){
			if ($_POST[$widget . '-config']){
				$config['widgets'][$widget . '-config'] = $_POST[$widget . '-config'];
			}
		}

		write_config();
		header("Location: index.php");
		exit;
	}

	require_once('includes/functions.inc.php');

	if(file_exists("/usr/sbin/swapinfo")) {
		$swapinfo = `/usr/sbin/swapinfo`;
		if(stristr($swapinfo,'%') == true) $showswap=true;
	}

	unset($hwcrypto);
	$fd = @fopen("{$g['varlog_path']}/dmesg.boot", "r");
	if ($fd) {
		while (!feof($fd)) {
			$dmesgl = fgets($fd);
			if (preg_match("/^hifn.: (.*?),/", $dmesgl, $matches) or preg_match("/.*(VIA Padlock)/", $dmesgl, $matches) or preg_match("/^safe.: (\w.*)/", $dmesgl, $matches) or preg_match("/^ubsec.: (.*?),/", $dmesgl, $matches) or preg_match("/^padlock.: <(.*?)>,/", $dmesgl, $matches) or preg_match("/^glxsb.: (.*?),/", $dmesgl, $matches)) {
				$hwcrypto = $matches[1];
				break;
			}
		}
		fclose($fd);
	}

if ($config['widgets'] && $config['widgets']['sequence'] != "") {
	$pconfig['sequence'] = $config['widgets']['sequence'];

	$widgetlist = $pconfig['sequence'];
	$colpos = array();
	$savedwidgetfiles = array();
	$widgetname = "";
	$widgetlist = explode(",",$widgetlist);

	foreach ($widgetlist as $widget){
		$dashpos = strpos($widget, "-");
		$widgetname = substr($widget, 0, $dashpos);
		$colposition = strpos($widget, ":");
		$displayposition = strrpos($widget, ":");
		$colpos[] = substr($widget,$colposition + 1, $displayposition - $colposition-1);
		$displayarray[] = substr($widget,$displayposition + 1);
		$savedwidgetfiles[] = $widgetname . ".widget.php";
	}

    foreach ($widgetfiles as $defaultwidgets){
         if (!in_array($defaultwidgets, $savedwidgetfiles)){
             $savedwidgetfiles[] = $defaultwidgets;
         }
     }

	foreach ($widgetnames as $widget){
        if ($config['widgets'][$widget . '-config']){
            $pconfig[$widget . '-config'] = $config['widgets'][$widget . '-config'];
        }
    }

	$widgetlist = $savedwidgetfiles;
}

else $widgetlist = $widgetfiles;

$phpincludefiles = array();
$directory = "/usr/local/www/widgets/include/";
$dirhandle  = opendir($directory);
$filename = "";
while (false !== ($filename = readdir($dirhandle))) {
	$phpincludefiles[] = $filename;
}
foreach($phpincludefiles as $includename) {
	if(!stristr($includename, ".inc"))
		continue;
	include($directory . $includename);
}

$jscriptstr = <<<EOD
<script language="javascript" type="text/javascript">

function configureWidget(selectedDiv){
	selectIntLink = selectedDiv + "-settings";
	d = document;
	textlink = d.getElementById(selectIntLink);
	if (textlink.style.display == "none")
		Effect.BlindDown(selectIntLink, {duration:1});
	else
		Effect.BlindUp(selectIntLink, {duration:1});
}

function showWidget(selectedDiv,swapButtons){
    Effect.BlindDown(selectedDiv, {duration:1});
    showSave();
	d = document;
    if (swapButtons){
	    selectIntLink = selectedDiv + "-min";
		textlink = d.getElementById(selectIntLink);
		textlink.style.display = "inline";


	    selectIntLink = selectedDiv + "-open";
		textlink = d.getElementById(selectIntLink);
		textlink.style.display = "none";

    }
	selectIntLink = selectedDiv + "-container-input";
	textlink = d.getElementById(selectIntLink);
	textlink.value = "show";

}

function minimizeWidget(selectedDiv,swapButtons){
	//fade element
    Effect.BlindUp(selectedDiv, {duration:1});
    showSave();
	d = document;
    if (swapButtons){
	    selectIntLink = selectedDiv + "-open";
		textlink = d.getElementById(selectIntLink);
		textlink.style.display = "inline";

	    selectIntLink = selectedDiv + "-min";
		textlink = d.getElementById(selectIntLink);
		textlink.style.display = "none";
    }
	selectIntLink = selectedDiv + "-container-input";
	textlink = d.getElementById(selectIntLink);
	textlink.value = "hide";

}

function showSave(){
	d = document;
	selectIntLink = "submit";
	textlink = d.getElementById(selectIntLink);
	textlink.style.display = "inline";
}

function updatePref(){
	var widgets = document.getElementsByClassName('widgetdiv');
	var widgetSequence = "";
	var firstprint = false;
	d = document;
	for (i=0; i<widgets.length; i++){
		if (firstprint)
			widgetSequence += ",";
		var widget = widgets[i].id;
		widgetSequence += widget + ":" + widgets[i].parentNode.id + ":";
		widget = widget + "-input";
		textlink = d.getElementById(widget).value;
		widgetSequence += textlink;
		firstprint = true;
	}
	selectLink = "sequence";
	textlink = d.getElementById(selectLink);
	textlink.value = widgetSequence;
	return true;
}

function hideAllWidgets(){
		Effect.Fade('niftyOutter', {to: 0.2});
}

function showAllWidgets(){
		Effect.Fade('niftyOutter', {to: 1.0});
}

</script>
EOD;

$pgtitle = array('SİSTEM', 'GENEL DURUM');

?>

<?php include('head.inc'); ?>

<script type="text/javascript" src="javascript/domTT/domLib.js"></script>
<script type="text/javascript" src="javascript/domTT/domTT.js"></script>
<script type="text/javascript" src="javascript/domTT/behaviour.js"></script>
<script type="text/javascript" src="javascript/domTT/fadomatic.js"></script>
</head>
<body>

<script language="javascript" type="text/javascript">
columns = ['col1','col2'];
</script>

<?php
include('fbegin.inc');
echo $jscriptstr;
?>

<form action="index.php" method="post">
<input type="hidden" value="" name="sequence" id="sequence">
<input id="submit" name="submit" type="submit" style="display:none" onclick="return updatePref();" class="btn btn-inverse" value="Kaydet" />
</form>
<div>
	<?php
	$totalwidgets = count($widgetfiles);
	$halftotal = $totalwidgets / 2 - 2;
	$widgetcounter = 0;
	$directory = "/usr/local/www/widgets/widgets/";
	$printed = false;
	$firstprint = false;
	?>
	<div id="col1" style="float:left;width:49%;padding-bottom:40px">
	<?php

	foreach($widgetlist as $widget) {
		if(!stristr($widget, "widget.php"))
					continue;
		$periodpos = strpos($widget, ".");
		$widgetname = substr($widget, 0, $periodpos);

		switch($widgetname) {
			case 'system': $title = 'Sistem'; break;
			case 'services_status': $title = 'Servisler'; break;
			case 'interface_statistics': $title = 'Paket İstatistikleri'; break;
			case 'interfaces': $title = 'Ağ Arayüzleri'; break;
			case 'gateways': $title = 'Ağ Geçitleri'; break;
		}

		if ($config['widgets'] && $pconfig['sequence'] != ""){
			switch($displayarray[$widgetcounter]){
				case "show":
					$divdisplay = "block";
					$display = "block";
					$inputdisplay = "show";
					$showWidget = "none";
					$mindiv = "inline";
					break;
				case "hide":
					$divdisplay = "block";
					$display = "none";
					$inputdisplay = "hide";
					$showWidget = "inline";
					$mindiv = "none";
					break;
				case "close":
					$divdisplay = "none";
					$display = "block";
					$inputdisplay = "close";
					$showWidget = "none";
					$mindiv = "inline";
					break;
				default:
					$divdisplay = "none";
					$display = "block";
					$inputdisplay = "none";
					$showWidget = "none";
					$mindiv = "inline";
					break;
			}
		} else {
			if ($firstprint == false){
				$divdisplay = "block";
				$display = "block";
				$inputdisplay = "show";
				$showWidget = "none";
				$mindiv = "inline";
				$firstprint = true;
			} else {
				switch ($widget) {
					case "interfaces.widget.php":
					case "traffic_graphs.widget.php":
						$divdisplay = "block";
						$display = "block";
						$inputdisplay = "show";
						$showWidget = "none";
						$mindiv = "inline";
						break;
					default:
						$divdisplay = "none";
						$display = "block";
						$inputdisplay = "close";
						$showWidget = "none";
						$mindiv = "inline";
						break;
				}
			}
		}

		if ($config['widgets'] && $pconfig['sequence'] != ""){
			if ($colpos[$widgetcounter] == "col2" && $printed == false)
			{
				$printed = true;
				?>
				</div>
				<div id="col2" style="float:right;width:49%;padding-bottom:40px">
				<?php
			}
		}
		else if ($widgetcounter >= $halftotal && $printed == false){
			$printed = true;
			?>
			</div>
			<div id="col2" style="float:right;width:49%;padding-bottom:40px">
			<?php
		}

		?>
		<div style="clear:both;"></div>
		<div  id="<?php echo $widgetname;?>-container" class="widgetdiv" style="display:<?php echo $divdisplay; ?>;">
			<input type="hidden" value="<?php echo $inputdisplay;?>" id="<?php echo $widgetname;?>-container-input" name="<?php echo $widgetname;?>-container-input">
			<div id="<?php echo $widgetname;?>-topic" class="widgetheader" style="cursor:move">
				<div style="float:left;">
					<?php echo $title;?>
				</div>
				<div align="right" style="float:right;">
					<div id="<?php echo $widgetname;?>-configure" onclick='return configureWidget("<?php echo $widgetname;?>")' style="display:none; cursor:pointer" ><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_configure.gif" /></div>
					<div id="<?php echo $widgetname;?>-open" onclick='return showWidget("<?php echo $widgetname;?>",true)' style="display:<?php echo $showWidget;?>; cursor:pointer" ><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_open.gif" /></div>
					<div id="<?php echo $widgetname;?>-min" onclick='return minimizeWidget("<?php echo $widgetname;?>",true)' style="display:<?php echo $mindiv;?>; cursor:pointer" ><img src="./themes/<?= $g['theme']; ?>/images/icons/icon_minus.gif"/></div>
				</div>
				<div style="clear:both;"></div>
			</div>
			<?php if ($divdisplay != "block") { ?>
			<div id="<?php echo $widgetname;?>-loader" style="display:<?php echo $display; ?>;">
			</div> <?php } if ($divdisplay != "block") $display = none; ?>
			<div id="<?php echo $widgetname;?>" style="display:<?php echo $display; ?>;">
				<?php
					if ($divdisplay == "block")
					{
						include($directory . $widget);
					}
				 ?>
			</div>
			<div style="clear:both;"></div>
		</div>
		<?php
	$widgetcounter++;
}
	?>
		</div>
	<div style="clear:both;"></div>
</div>

</div>

<script type="text/javascript">
	document.observe('dom:loaded', function(in_event)
	{
			Sortable.create("col1", {tag:'div',dropOnEmpty:true,containment:columns,handle:'widgetheader',constraint:false,only:'widgetdiv',onChange:showSave});
			Sortable.create("col2", {tag:'div',dropOnEmpty:true,containment:columns,handle:'widgetheader',constraint:false,only:'widgetdiv',onChange:showSave});
	});
</script>
</body>
</html>
