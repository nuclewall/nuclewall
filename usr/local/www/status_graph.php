<?php
/*
	status_graph.php
	Part of pfSense
	Copyright (C) 2004 Scott Ullrich
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
	OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
	SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
	INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
	CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
	ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
	POSSIBILITY OF SUCH DAMAGE.
*/

require('guiconfig.inc');

if ($_POST['width'])
	$width = $_POST['width'];
else
	$width = "400";

if ($_POST['height'])
	$height = $_POST['height'];
else
	$height = "200";

// Get configured interface list
$ifdescrs = get_configured_interface_with_descr();

if ($_GET['if']) {
	$curif = $_GET['if'];
	$found = false;
	foreach($ifdescrs as $descr => $ifdescr)
		if($descr == $curif) $found = true;
	if(!$found) {
		Header("Location: status_graph.php");
		exit;
	}
} else {
	$curif = "wan";
}

$pgtitle = array('STATUS ', 'NETWORK TRAFFIC');

include('head.inc');

?>

<script src="/javascript/scriptaculous/prototype.js" type="text/javascript"></script>
<script src="/javascript/scriptaculous/scriptaculous.js" type="text/javascript"></script>
<script language="javascript" type="text/javascript">

function updateBandwidth(){
    var hostinterface = "<?php echo htmlspecialchars($curif); ?>";
    bandwidthAjax(hostinterface);
}

function bandwidthAjax(hostinterface) {
	uri = "bandwidth_by_ip.php?if=" + hostinterface;
	var opt = {
	    // Use GET
	    method: 'get',
	    asynchronous: true,
	    // Handle 404
	    on404: function(t) {
	        alert('Error 404: location "' + t.statusText + '" was not found.');
	    },
	    // Handle other errors
	    onFailure: function(t) {
	        alert('Error ' + t.status + ' -- ' + t.statusText);
	    },
		onSuccess: function(t) {
			updateBandwidthHosts(t.responseText);
	    }
	}
	new Ajax.Request(uri, opt);
}

function updateBandwidthHosts(data){
    var hosts_split = data.split("|");
    d = document;
    //parse top ten bandwidth abuser hosts
    for (var y=0; y<10; y++){
        if (hosts_split[y] != "" && hosts_split[y] != "no info"){
            if (hosts_split[y]) {
                hostinfo = hosts_split[y].split(";");

                //update host ip info
                var HostIpID = "hostip" + y;
                var hostip = d.getElementById(HostIpID);
                hostip.innerHTML = hostinfo[0];

                //update bandwidth inbound to host
                var hostbandwidthInID = "bandwidthin" + y;
                var hostbandwidthin = d.getElementById(hostbandwidthInID);
                hostbandwidthin.innerHTML = hostinfo[1] + " Bits/sn";

                //update bandwidth outbound from host
                var hostbandwidthOutID = "bandwidthout" + y;
                var hostbandwidthOut = d.getElementById(hostbandwidthOutID);
                hostbandwidthOut.innerHTML = hostinfo[2] + " Bits/sn";

                //make the row appear if hidden
                var rowid = "host" + y;
                textlink = d.getElementById(rowid);
                if (textlink.style.display == "none"){
                     //hide rows that contain no data
                     Effect.Appear(rowid, {duration:1});
                }
            }
        }
        else
        {
            var rowid = "host" + y;
            textlink = d.getElementById(rowid);
            if (textlink.style.display != "none"){
                //hide rows that contain no data
                Effect.Fade(rowid, {duration:2});
            }
        }
    }

    setTimeout('updateBandwidth()', 1000);
}

</script>
</head>
<body>
<?php include('fbegin.inc'); ?>
<table cellspacing="0" cellpadding="0">
	<tr>
		<td>
			<table class="tabcont" style="padding-right:2px;" cellspacing="0" cellpadding="0">
				<tr>
					<td>
						<table cellspacing="0" cellpadding="0">
							<tr>
								<td valign="top">
									<form name="form1" action="status_graph.php" method="get">
										Arayüz:
										<select name="if" style="z-index: -10;" onchange="document.form1.submit()">
											<?php
											foreach ($ifdescrs as $ifn => $ifd) {
												echo "<option value=\"$ifn\"";
												if ($ifn == $curif) echo " selected";
												echo ">" . htmlspecialchars($ifd) . "</option>\n";
											}
											?>
										</select>
									</form>
									<embed src="graph.php?ifnum=<?=htmlspecialchars($curif);?>&ifname=<?=rawurlencode($ifdescrs[htmlspecialchars($curif)]);?>" type="image/svg+xml" width="<?=$width;?>" height="<?=$height;?>" />
								</td>
								<td style="padding-left:10px;padding-top:45px;" valign="top">
									<table class="grids" cellspacing="0" cellpadding="0">
										<tr>
											<td class="head">IP Adresi</td>
											<td class="head">Gelen</td>
											<td class="head">Giden</td>
									   </tr>
									   <tr id="host0" style="display:none">
											<td id="hostip0" class="cell">
											</td>
											<td id="bandwidthin0" class="cell">
											</td>
											<td id="bandwidthout0" class="cell">
											</td>
									   </tr>
									   <tr id="host1" style="display:none">
											<td id="hostip1" class="cell">
											</td>
											<td id="bandwidthin1" class="cell">
											</td>
											<td id="bandwidthout1" class="cell">
											</td>
									   </tr>
									   <tr id="host2" style="display:none">
											<td id="hostip2" class="cell">
											</td>
											<td id="bandwidthin2" class="cell">
											</td>
											<td id="bandwidthout2" class="cell">
											</td>
									   </tr>
									   <tr id="host3" style="display:none">
											<td id="hostip3" class="cell">
											</td>
											<td id="bandwidthin3" class="cell">
											</td>
											<td id="bandwidthout3" class="cell">
											</td>
									   </tr>
									   <tr id="host4" style="display:none">
											<td id="hostip4" class="cell">
											</td>
											<td id="bandwidthin4" class="cell">
											</td>
											<td id="bandwidthout4" class="cell">
											</td>
									   </tr>
									   <tr id="host5" style="display:none">
											<td id="hostip5" class="cell">
											</td>
											<td id="bandwidthin5" class="cell">
											</td>
											<td id="bandwidthout5" class="cell">
											</td>
									   </tr>
									   <tr id="host6" style="display:none">
											<td id="hostip6" class="cell">
											</td>
											<td id="bandwidthin6" class="cell">
											</td>
											<td id="bandwidthout6" class="cell">
											</td>
									   </tr>
									   <tr id="host7" style="display:none">
											<td id="hostip7" class="cell">
											</td>
											<td id="bandwidthin7" class="cell">
											</td>
											<td id="bandwidthout7" class="cell">
											</td>
									   </tr>
									   <tr id="host8" style="display:none">
											<td id="hostip8" class="cell">
											</td>
											<td id="bandwidthin8" class="cell">
											</td>
											<td id="bandwidthout8" class="cell">
											</td>
									   </tr>
									   <tr id="host9" style="display:none">
											<td id="hostip9" class="cell">
											</td>
											<td id="bandwidthin9" class="cell">
											</td>
											<td id="bandwidthout9" class="cell">
											</td>
									   </tr>
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
window.onload = function(in_event)
	{
        updateBandwidth();
    }

</script>
</body>
</html>
