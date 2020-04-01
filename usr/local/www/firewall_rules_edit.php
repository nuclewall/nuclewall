<?php
/* $Id$ */
/*
	firewall_rules_edit.php

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
require('filter.inc');
require('shaper.inc');

$specialsrcdst = explode(" ", "any");
$ifdisp = get_configured_interface_with_descr();
foreach ($ifdisp as $kif => $kdescr) {
	$specialsrcdst[] = "{$kif}";
	$specialsrcdst[] = "{$kif}ip";
}

if (!is_array($config['filter']['rule'])) {
	$config['filter']['rule'] = array();
}
filter_rules_sort();
$a_filter = &$config['filter']['rule'];

$id = $_GET['id'];
if (is_numeric($_POST['id']))
	$id = $_POST['id'];

$after = $_GET['after'];

if (isset($_POST['after']))
	$after = $_POST['after'];

if (isset($_GET['dup'])) {
	$id = $_GET['dup'];
	$after = $_GET['dup'];
}

if (isset($id) && $a_filter[$id]) {
	$pconfig['interface'] = $a_filter[$id]['interface'];

	if (isset($a_filter[$id]['id']))
		$pconfig['ruleid'] = $a_filter[$id]['id'];

	if (!isset($a_filter[$id]['type']))
		$pconfig['type'] = "pass";
	else
		$pconfig['type'] = $a_filter[$id]['type'];

	if (isset($a_filter[$id]['floating']) || $if == "FloatingRules") {
		$pconfig['floating'] = $a_filter[$id]['floating'];
		if (isset($a_filter[$id]['interface']) && $a_filter[$id]['interface'] <> "")
			$pconfig['interface'] = $a_filter[$id]['interface'];
	}

	if (isset($a_filter['floating']))
		$pconfig['floating'] = "yes";

	if (isset($a_filter[$id]['direction']))
                $pconfig['direction'] = $a_filter[$id]['direction'];

	if (isset($a_filter[$id]['protocol']))
		$pconfig['proto'] = $a_filter[$id]['protocol'];
	else
		$pconfig['proto'] = "any";

	if ($a_filter[$id]['protocol'] == "icmp")
		$pconfig['icmptype'] = $a_filter[$id]['icmptype'];

	address_to_pconfig($a_filter[$id]['source'], $pconfig['src'],
		$pconfig['srcmask'], $pconfig['srcnot'],
		$pconfig['srcbeginport'], $pconfig['srcendport']);

	if($a_filter[$id]['os'] <> "")
		$pconfig['os'] = $a_filter[$id]['os'];

	address_to_pconfig($a_filter[$id]['destination'], $pconfig['dst'],
		$pconfig['dstmask'], $pconfig['dstnot'],
		$pconfig['dstbeginport'], $pconfig['dstendport']);

	$pconfig['disabled'] = isset($a_filter[$id]['disabled']);
	$pconfig['log'] = isset($a_filter[$id]['log']);
	$pconfig['descr'] = $a_filter[$id]['descr'];

	if (isset($a_filter[$id]['tcpflags_any']))
		$pconfig['tcpflags_any'] = true;
	else {
		if (isset($a_filter[$id]['tcpflags1']) && $a_filter[$id]['tcpflags1'] <> "")
			$pconfig['tcpflags1'] = $a_filter[$id]['tcpflags1'];
		if (isset($a_filter[$id]['tcpflags2']) && $a_filter[$id]['tcpflags2'] <> "")
			$pconfig['tcpflags2'] = $a_filter[$id]['tcpflags2'];
	}

	if (isset($a_filter[$id]['quick']) && $a_filter[$id]['quick'])
		$pconfig['quick'] = $a_filter[$id]['quick'];
	if (isset($a_filter[$id]['allowopts']))

	/* Multi-WAN next-hop support */
	$pconfig['gateway'] = $a_filter[$id]['gateway'];

	/* Shaper support */
	$pconfig['dnpipe'] = $a_filter[$id]['dnpipe'];
	$pconfig['pdnpipe'] = $a_filter[$id]['pdnpipe'];

	//schedule support
	$pconfig['sched'] = $a_filter[$id]['sched'];

} else {
	/* defaults */
	if ($_GET['if'])
		$pconfig['interface'] = $_GET['if'];
	$pconfig['type'] = "pass";
	$pconfig['src'] = "any";
	$pconfig['dst'] = "any";
}
/* Allow the FlotingRules to work */
$if = $pconfig['interface'];

if (isset($_GET['dup']))
	unset($id);

if ($_POST) {

	if ($_POST['type'] == "reject" && $_POST['proto'] <> "tcp")
		$input_errors[] = "Reject type rules only works when the protocol is set to TCP.";

	if (($_POST['proto'] != "tcp") && ($_POST['proto'] != "udp") && ($_POST['proto'] != "tcp/udp")) {
		$_POST['srcbeginport'] = 0;
		$_POST['srcendport'] = 0;
		$_POST['dstbeginport'] = 0;
		$_POST['dstendport'] = 0;
	} else {

		if ($_POST['srcbeginport_cust'] && !$_POST['srcbeginport'])
			$_POST['srcbeginport'] = $_POST['srcbeginport_cust'];
		if ($_POST['srcendport_cust'] && !$_POST['srcendport'])
			$_POST['srcendport'] = $_POST['srcendport_cust'];

		if ($_POST['srcbeginport'] == "any") {
			$_POST['srcbeginport'] = 0;
			$_POST['srcendport'] = 0;
		} else {
			if (!$_POST['srcendport'])
				$_POST['srcendport'] = $_POST['srcbeginport'];
		}
		if ($_POST['srcendport'] == "any")
			$_POST['srcendport'] = $_POST['srcbeginport'];

		if ($_POST['dstbeginport_cust'] && !$_POST['dstbeginport'])
			$_POST['dstbeginport'] = $_POST['dstbeginport_cust'];
		if ($_POST['dstendport_cust'] && !$_POST['dstendport'])
			$_POST['dstendport'] = $_POST['dstendport_cust'];

		if ($_POST['dstbeginport'] == "any") {
			$_POST['dstbeginport'] = 0;
			$_POST['dstendport'] = 0;
		} else {
			if (!$_POST['dstendport'])
				$_POST['dstendport'] = $_POST['dstbeginport'];
		}
		if ($_POST['dstendport'] == "any")
			$_POST['dstendport'] = $_POST['dstbeginport'];
	}

	if (is_specialnet($_POST['srctype'])) {
		$_POST['src'] = $_POST['srctype'];
		$_POST['srcmask'] = 0;
	} else if ($_POST['srctype'] == "single") {
		$_POST['srcmask'] = 32;
	}
	if (is_specialnet($_POST['dsttype'])) {
		$_POST['dst'] = $_POST['dsttype'];
		$_POST['dstmask'] = 0;
	}  else if ($_POST['dsttype'] == "single") {
		$_POST['dstmask'] = 32;
	}

	unset($input_errors);
	$pconfig = $_POST;

	/* input validation */
	$reqdfields = explode(" ", "type proto");
	$reqdfields[] = "src";
	$reqdfields[] = "dst";

	$reqdfieldsn = explode(",", "Type,Protocol");
	$reqdfieldsn[] = "Source";
	$reqdfieldsn[] = "Destination";


	if (!(is_specialnet($_POST['srctype']) || ($_POST['srctype'] == "single"))) {
		$reqdfields[] = "srcmask";
		$reqdfieldsn[] = "Source bit count";
	}

	if (!(is_specialnet($_POST['dsttype']) || ($_POST['dsttype'] == "single"))) {
		$reqdfields[] = "dstmask";
		$reqdfieldsn[] = "Destination bit count";
	}


	do_input_validation($_POST, $reqdfields, $reqdfieldsn, &$input_errors);

	if (!$_POST['srcbeginport']) {
		$_POST['srcbeginport'] = 0;
		$_POST['srcendport'] = 0;
	}
	if (!$_POST['dstbeginport']) {
		$_POST['dstbeginport'] = 0;
		$_POST['dstendport'] = 0;
	}

	if ($_POST['srcbeginport'] && !is_portoralias($_POST['srcbeginport']))
                $input_errors[] = sprintf("%s is not a valid start source port. It must be a port alias or integer between 1 and 65535.",$_POST['srcbeginposrt']);
        if ($_POST['srcendport'] && !is_portoralias($_POST['srcendport']))
                $input_errors[] = sprintf("%s is not a valid end source port. It must be a port alias or integer between 1 and 65535.", $_POST['srcendport']);
        if ($_POST['dstbeginport'] && !is_portoralias($_POST['dstbeginport']))
                $input_errors[] = sprintf("%s is not a valid start destination port. It must be a port alias or integer between 1 and 65535.", $_POST['dstbeginport']);
        if ($_POST['dstendport'] && !is_portoralias($_POST['dstendport']))
                $input_errors[] = sprintf("%s is not a valid end destination port. It must be a port alias or integer between 1 and 65535.", $_POST['dstendport']);
	if ( !$_POST['srcbeginport_cust'] && $_POST['srcendport_cust'])
		if (is_alias($_POST['srcendport_cust']))
			$input_errors[] = 'If you put port alias in Source port range to: field you must put the same port alias in from: field';
	if ( $_POST['srcbeginport_cust'] && $_POST['srcendport_cust']){
		if (is_alias($_POST['srcendport_cust']) && is_alias($_POST['srcendport_cust']) && $_POST['srcbeginport_cust'] != $_POST['srcendport_cust'])
			$input_errors[] = 'The same port alias must be used in Source port range from: and to: fields';
		if ((is_alias($_POST['srcbeginport_cust']) && (!is_alias($_POST['srcendport_cust']) && $_POST['srcendport_cust']!='')) ||
		    ((!is_alias($_POST['srcbeginport_cust']) && $_POST['srcbeginport_cust']!='') && is_alias($_POST['srcendport_cust'])))
			$input_errors[] = 'You cannot specify numbers and port aliases at the same time in Source port range from: and to: field';
	}
	if ( !$_POST['dstbeginport_cust'] && $_POST['dstendport_cust'])
		if (is_alias($_POST['dstendport_cust']))
			$input_errors[] = 'If you put port alias in Destination port range to: field you must put the same port alias in from: field.';
	if ( $_POST['dstbeginport_cust'] && $_POST['dstendport_cust']){
		if (is_alias($_POST['dstendport_cust']) && is_alias($_POST['dstendport_cust']) && $_POST['dstbeginport_cust'] != $_POST['dstendport_cust'])
			$input_errors[] = 'The same port alias must be used in Destination port range from: and to: fields.';
		if ((is_alias($_POST['dstbeginport_cust']) && (!is_alias($_POST['dstendport_cust']) && $_POST['dstendport_cust']!='')) ||
		    ((!is_alias($_POST['dstbeginport_cust']) && $_POST['dstbeginport_cust']!='') && is_alias($_POST['dstendport_cust'])))
			$input_errors[] = 'You cannot specify numbers and port aliases at the same time in Destination port range from: and to: field.';
	}

	/* if user enters an alias and selects "network" then disallow. */
	if($_POST['srctype'] == "network") {
		if(is_alias($_POST['src']))
			$input_errors[] = "You must specify single host or alias for alias entries.";
	}
	if($_POST['dsttype'] == "network") {
		if(is_alias($_POST['dst']))
			$input_errors[] = "You must specify single host or alias for alias entries.";
	}

	if (!is_specialnet($_POST['srctype'])) {
		if (($_POST['src'] && !is_ipaddroralias($_POST['src']))) {
			$input_errors[] = sprintf("%s is not a valid source IP address or alias." ,$_POST['src']);
		}
		if (($_POST['srcmask'] && !is_numericint($_POST['srcmask']))) {
			$input_errors[] = "A valid source bit count must be specified.";
		}
	}
	if (!is_specialnet($_POST['dsttype'])) {
		if (($_POST['dst'] && !is_ipaddroralias($_POST['dst']))) {
			$input_errors[] = sprintf("%s is not a valid destination IP address or alias." ,$_POST['dst']);
		}
		if (($_POST['dstmask'] && !is_numericint($_POST['dstmask']))) {
			$input_errors[] = "A valid destination bit count must be specified.";
		}
	}

	if ($_POST['srcbeginport'] > $_POST['srcendport']) {
		/* swap */
		$tmp = $_POST['srcendport'];
		$_POST['srcendport'] = $_POST['srcbeginport'];
		$_POST['srcbeginport'] = $tmp;
	}
	if ($_POST['dstbeginport'] > $_POST['dstendport']) {
		/* swap */
		$tmp = $_POST['dstendport'];
		$_POST['dstendport'] = $_POST['dstbeginport'];
		$_POST['dstbeginport'] = $tmp;
	}
	if ($_POST['os'])
		if( $_POST['proto'] != "tcp" )
			$input_errors[] = "OS detection is only valid with protocol tcp.";

	if (isset($_POST['floating']) && $_POST['pdnpipe'] != "none" && (empty($_POST['direction']) || $_POST['direction'] == "any"))
		$input_errors[] = "You can not use limiters in Floating rules without choosing a direction.";
	if (isset($_POST['floating']) && $_POST['gateway'] != "" && (empty($_POST['direction']) || $_POST['direction'] == "any"))
		$input_errors[] = "You can not use gateways in Floating rules without choosing a direction..";
	if ($_POST['pdnpipe'] && $_POST['pdnpipe'] != "none") {
		if ($_POST['dnpipe'] == "none" )
			$input_errors[] = "You must select a limiter for the In direction before selecting one for Out too.";
		else if ($_POST['pdnpipe'] == $_POST['dnpipe'])
			$input_errors[] = "In and Out Queue cannot be the same.";
		else if ($pdnpipe[0] == "?" && $dnpipe[0] <> "?")
			$input_errors[] = "You cannot select one queue and one virtual interface for IN and Out. both must be from the same type.";
		else if ($dnpipe[0] == "?" && $pdnpipe[0] <> "?")
			$input_errors[] = "You cannot select one queue and one virtual interface for IN and Out. both must be from the same type.";
		if ($_POST['direction'] == "out" && empty($_POST['gateway']))
			$input_errors[] = "You must select a gateway.";
	}
	if( !empty($_POST['ruleid']) && !ctype_digit($_POST['ruleid']))
		$input_errors[] = "ID must be an integer.";
	if($_POST['l7container'] && $_POST['l7container'] != "none") {
		if(!($_POST['proto'] == "tcp" || $_POST['proto'] == "udp" || $_POST['proto'] == "tcp/udp"))
			$input_errors[] = "You can only select a layer7 container for TCP and/or UDP protocols.";
		if ($_POST['type'] <> "pass")
			$input_errors[] = "You can only select a layer7 container for Pass type rules.";

	}

	if (!$_POST['tcpflags_any']) {
		$settcpflags = array();
		$outoftcpflags = array();
		foreach ($tcpflags as $tcpflag) {
			if ($_POST['tcpflags1_' . $tcpflag] == "on")
				$settcpflags[] = $tcpflag;
			if ($_POST['tcpflags2_' . $tcpflag] == "on")
				$outoftcpflags[] = $tcpflag;
		}
		if (empty($outoftcpflags) && !empty($settcpflags))
			$input_errors[] = "If you specify TCP flags that should be set you should specify out of which flags as well.";
	}

	// Allow extending of the firewall edit page and include custom input validation
	pfSense_handle_custom_code("/usr/local/pkg/firewall_rules/input_validation");

	if (!$input_errors) {
		$filterent = array();
		$filterent['id'] = $_POST['ruleid']>0?$_POST['ruleid']:'';
		$filterent['type'] = $_POST['type'];
		if (isset($_POST['interface'] ))
			$filterent['interface'] = $_POST['interface'];

		if ($_POST['tcpflags_any']) {
			$filterent['tcpflags_any'] = true;
		} else {
			$settcpflags = array();
			$outoftcpflags = array();
			foreach ($tcpflags as $tcpflag) {
				if ($_POST['tcpflags1_' . $tcpflag] == "on")
					$settcpflags[] = $tcpflag;
				if ($_POST['tcpflags2_' . $tcpflag] == "on")
					$outoftcpflags[] = $tcpflag;
			}
			if (!empty($outoftcpflags)) {
				$filterent['tcpflags2'] = join(",", $outoftcpflags);
				if (!empty($settcpflags))
					$filterent['tcpflags1'] = join(",", $settcpflags);
			}
		}

		if ($if == "FloatingRules" || isset($_POST['floating'])) {
			$filterent['direction'] = $_POST['direction'];
			if (isset($_POST['quick']) && $_POST['quick'] <> "")
				$filterent['quick'] = $_POST['quick'];
			$filterent['floating'] = "yes";
			if (isset($_POST['interface']) && count($_POST['interface']) > 0)  {
				$filterent['interface'] = implode(",", $_POST['interface']);
			}
		}

		/* Advanced options */
		$filterent['os'] = $_POST['os'];

		/* Nosync directive - do not xmlrpc sync this item */

		if ($_POST['proto'] != "any")
			$filterent['protocol'] = $_POST['proto'];
		else
			unset($filterent['protocol']);

		if ($_POST['proto'] == "icmp" && $_POST['icmptype'])
			$filterent['icmptype'] = $_POST['icmptype'];
		else
			unset($filterent['icmptype']);

		pconfig_to_address($filterent['source'], $_POST['src'],
			$_POST['srcmask'], $_POST['srcnot'],
			$_POST['srcbeginport'], $_POST['srcendport']);

		pconfig_to_address($filterent['destination'], $_POST['dst'],
			$_POST['dstmask'], $_POST['dstnot'],
			$_POST['dstbeginport'], $_POST['dstendport']);

		if ($_POST['disabled'])
			$filterent['disabled'] = true;
		else
			unset($filterent['disabled']);

		if ($_POST['log'])
			$filterent['log'] = true;
		else
			unset($filterent['log']);
		strncpy($filterent['descr'], $_POST['descr'], 52);

		if ($_POST['gateway'] != "") {
			$filterent['gateway'] = $_POST['gateway'];
		}

		if (isset($_POST['dnpipe']) && $_POST['dnpipe'] != "none") {
			$filterent['dnpipe'] = $_POST['dnpipe'];
			if (isset($_POST['pdnpipe']) && $_POST['pdnpipe'] != "none")
				$filterent['pdnpipe'] = $_POST['pdnpipe'];
		}

		if ($_POST['sched'] != "") {
			$filterent['sched'] = $_POST['sched'];
		}

		// Allow extending of the firewall edit page and include custom input validation
		pfSense_handle_custom_code("/usr/local/pkg/firewall_rules/pre_write_config");

		if (isset($id) && $a_filter[$id])
			$a_filter[$id] = $filterent;
		else {
			if (is_numeric($after))
				array_splice($a_filter, $after+1, 0, array($filterent));
			else
				$a_filter[] = $filterent;
		}

		filter_rules_sort();

		write_config("A firewall rule configured");
		mark_subsystem_dirty('filter');

		if (isset($_POST['floating']))
			header("Location: firewall_rules.php?if=FloatingRules");
		else
			header("Location: firewall_rules.php?if=" . htmlspecialchars($_POST['interface']));
		exit;
	}
}

read_dummynet_config();
$dnqlist =& get_unique_dnqueue_list();

$pgtitle = array('FIREWALL ', 'RULES', 'EDIT');
?>

<?php include('head.inc'); ?>
<link rel="stylesheet" href="javascript/chosen/chosen.css" />
</head>
<body>
<script src="javascript/chosen/chosen.proto.js" type="text/javascript"></script>
<?php include('fbegin.inc'); ?>

<?php pfSense_handle_custom_code("/usr/local/pkg/firewall_rules/pre_input_errors"); ?>
<?php if ($input_errors) print_input_errors($input_errors); ?>

<form action="firewall_rules_edit.php" method="post" name="iform" id="iform">
<input type='hidden' name="ruleid" value="<?=(isset($pconfig['ruleid'])&&$pconfig['ruleid']>0)?htmlspecialchars($pconfig['ruleid']):''?>">
	<table class="tabcont" cellpadding="0" cellspacing="0">
		<tr>
			<td colspan="2" class="listtopic">EDIT RULE</td>
		</tr>
		<?php
			pfSense_handle_custom_code("/usr/local/pkg/firewall_rules/htmlphpearly");
		?>
		<tr>
			<td valign="top" class="vncell">Action</td>
			<td class="vtable">
				<select name="type">
					<?php $types = explode(" ", "Pass Block Reject"); foreach ($types as $type): ?>
					<option value="<?=strtolower($type);?>" <?php if (strtolower($type) == strtolower($pconfig['type'])) echo "selected"; ?>>
					<?=htmlspecialchars($type);?>
					</option>
					<?php endforeach; ?>
					<?php if ($if == "FloatingRules" || isset($pconfig['floating'])): ?>
					<option value="match" <?php if ("match" == strtolower($pconfig['type'])) echo "selected"; ?>>Queue</option>
					<?php endif; ?>
				</select>
				<br>
				<span>
				     Choose what to do with packets that match the criteria specified below.<br>
					<b>Hint:</b> The difference between block and reject is that with reject, a packet (TCP RST or ICMP port unreachable for UDP) is returned to the sender, whereas with block the packet is dropped silently.<br>
					In either case, the original packet is discarded.
				</span>
			</td>
		</tr>
		<tr>
			<td valign="top" class="vncell">Disabled</td>
			<td class="vtable">
				<input name="disabled" type="checkbox" id="disabled" value="yes" <?php if ($pconfig['disabled']) echo "checked"; ?>>
				<strong>Disable this rule</strong><br>
				<span>Set this option to disable this rule without removing it from the list.</span>
			</td>
		</tr>

		<?php if ($if == "FloatingRules" || isset($pconfig['floating'])): ?>

		<tr>
			<td valign="top" class="vncell">Quick</td>
			<td class="vtable">
				<input name="quick" type="checkbox" id="quick" value="yes" <?php if ($pconfig['quick']) echo "checked=\"checked\""; ?> />
				<b>Apply the action immediately on match.</b><br>
				<span>Set this option if you need to apply this action to traffic that matches this rule immediately.</span>
			</td>
			</tr>

		<?php endif; ?>
		<?php $edit_disabled = ""; ?>

		<tr>
			<td valign="top" class="vncell">Interface</td>
			<td class="vtable">
				<?php if ($if == "FloatingRules" || isset($pconfig['floating'])): ?>
				<select name="interface[]" multiple="true" size="3" <?=$edit_disabled;?>>
				<?php else: ?>
				<select name="interface" <?=$edit_disabled;?>>

				<?php
					endif;
					/* add group interfaces */
					if (is_array($config['ifgroups']['ifgroupentry']))
						foreach($config['ifgroups']['ifgroupentry'] as $ifgen)
							if (have_ruleint_access($ifgen['ifname']))
								$interfaces[$ifgen['ifname']] = $ifgen['ifname'];
					$ifdescs = get_configured_interface_with_descr();
					// Allow extending of the firewall edit page and include custom input validation
					pfSense_handle_custom_code("/usr/local/pkg/firewall_rules/pre_interfaces_edit");

					foreach ($ifdescs as $ifent => $ifdesc)
						if(have_ruleint_access($ifent))
							$interfaces[$ifent] = $ifdesc;

						if (is_array($pconfig['interface']))
							$pconfig['interface'] = implode(",", $pconfig['interface']);

						$selected_interfaces = explode(",", $pconfig['interface']);
						foreach ($interfaces as $iface => $ifacename): ?>
							<option value="<?=$iface;?>" <?php if ($pconfig['interface'] <> "" && ( strcasecmp($pconfig['interface'], $iface) == 0 || in_array($iface, $selected_interfaces) )) echo "selected"; ?>><?=$ifacename?></option>
				<?php endforeach; ?>

				</select>
				<br>
				Choose on which interface packets must come in to match this rule.
			</td>
		</tr>

		<?php if ($if == "FloatingRules" || isset($pconfig['floating'])): ?>

		<tr>
			<td valign="top" class="vncell">Direction</td>
			<td class="vtable">
				<select name="direction">
					<?php	$directions = array('any','in','out');
					foreach ($directions as $direction): ?>
					<option value="<?=$direction;?>"
					<?php if ($direction == $pconfig['direction']): ?>
						selected="selected"
					<?php endif; ?>
					><?=$direction;?></option>
					<?php endforeach; ?>
				</select>
				<input type="hidden" id="floating" name="floating" value="floating">
			</td>
		<tr>
		<?php endif; ?>

		<tr>
			<td valign="top" class="vncell">Protocol</td>
			<td class="vtable">
				<select <?=$edit_disabled;?> name="proto" onchange="proto_change()">
				<?php
				$protocols = explode(" ", "TCP UDP TCP/UDP ICMP ESP AH GRE IGMP OSPF any carp pfsync");
				foreach ($protocols as $proto): ?>
					<option value="<?=strtolower($proto);?>" <?php if (strtolower($proto) == $pconfig['proto']) echo "selected"; ?>><?=htmlspecialchars($proto);?></option>
				<?php	endforeach; ?>
				</select>
				<br>
				 Choose which IP protocol this rule should match.<br>
				<b>Hint:</b> In most cases, you should specify <em>TCP</em>.
			</td>
		</tr>

		<tr id="icmpbox" name="icmpbox">
			<td valign="top" class="vncell">ICMP Type</td>
			<td class="vtable">
				<select <?=$edit_disabled;?> name="icmptype">
				<?php
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

					foreach ($icmptypes as $icmptype => $descr): ?>
						<option value="<?=$icmptype;?>" <?php if ($icmptype == $pconfig['icmptype']) echo "selected"; ?>>
						<?=htmlspecialchars($descr);?></option>
				<?php endforeach; ?>
				</select>
				<br>
				If you selected ICMP for the protocol above, you may specify an ICMP type here.
			</td>
		</tr>

		<tr>
			<td valign="top" class="vncell">Source</td>
			<td class="vtable">
				<input <?=$edit_disabled;?> name="srcnot" type="checkbox" id="srcnot" value="yes" <?php if ($pconfig['srcnot']) echo "checked"; ?>>
				<b>not</b>
				<p>Use this option to invert the sense of the match.</p>

				<table id="inline1" cellspacing="0" cellpadding="0">
					<tr>
						<td>Type</td>
						<td style="padding-left:5px;">
							<select <?=$edit_disabled;?> name="srctype" onChange="typesel_change()">
								<?php
								$sel = is_specialnet($pconfig['src']); ?>
								<option value="any"     <?php if ($pconfig['src'] == "any") { echo "selected"; } ?>>any</option>
								<option value="single"  <?php if (($pconfig['srcmask'] == 32) && !$sel) { echo "selected"; $sel = 1; } ?>>Single host or alias</option>
								<option value="network" <?php if (!$sel) echo "selected"; ?>>Network</option>
								<?php if(have_ruleint_access("pptp")): ?>
								<option value="pptp"    <?php if ($pconfig['src'] == "pptp") { echo "selected"; } ?>>PPTP clients</option>
								<?php endif; ?>
								<?php if(have_ruleint_access("pppoe")): ?>
								<option value="pppoe"   <?php if ($pconfig['src'] == "pppoe") { echo "selected"; } ?>>PPPoE clients</option>
								<?php endif; ?>
								<?php if(have_ruleint_access("l2tp")): ?>
								<option value="l2tp"   <?php if ($pconfig['src'] == "l2tp") { echo "selected"; } ?>>L2TP clients</option>
								<?php endif; ?>
								<?php
								foreach ($ifdisp as $ifent => $ifdesc): ?>
								<?php if(have_ruleint_access($ifent)): ?>
								<option value="<?=$ifent;?>" <?php if ($pconfig['src'] == $ifent) { echo "selected"; } ?>><?=htmlspecialchars($ifdesc);?> subnet</option>
								<option value="<?=$ifent;?>ip"<?php if ($pconfig['src'] ==  $ifent . "ip") { echo "selected"; } ?>>
									<?=$ifdesc?> address
								</option>
								<?php endif; ?>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<td>Address</td>
						<td style="padding:5px;">
							<input <?=$edit_disabled;?> autocomplete='off' name="src" type="text" id="src" size="20" value="<?php if (!is_specialnet($pconfig['src'])) echo htmlspecialchars($pconfig['src']);?>"> /
							<select <?=$edit_disabled;?> name="srcmask" id="srcmask">
							<?php for ($i = 31; $i > 0; $i--): ?>
								<option value="<?=$i;?>" <?php if ($i == $pconfig['srcmask']) echo "selected"; ?>><?=$i;?></option>
							<?php endfor; ?>
							</select>
						</td>
					</tr>
				</table><!-- inline1 end -->

				<div id="showadvancedboxspr">
					<input style="margin:0;padding:0;" <?=$edit_disabled;?> type="button" class="btn btn-link" onClick="show_source_port_range()" value="Advanced">
				</div>

			</td>
		</tr>

		<tr style="display:none" id="sprtable" name="sprtable">
			<td valign="top" class="vncell">Source port range</td>
			<td class="vtable">
				<table id="inline2" cellspacing="0" cellpadding="0">
					<tr>
						<td>from:</td>
						<td style="padding:5px;">
							<select <?=$edit_disabled;?> name="srcbeginport" onchange="src_rep_change();ext_change()">
								<option value="">other</option>
								<option value="any" <?php $bfound = 0; if ($pconfig['srcbeginport'] == "any") { echo "selected"; $bfound = 1; } ?>>any</option>
									<?php foreach ($wkports as $wkport => $wkportdesc): ?>
								<option value="<?=$wkport;?>"
									<?php if ($wkport == $pconfig['srcbeginport']) { echo "selected"; $bfound = 1; } ?>><?=htmlspecialchars($wkportdesc);?>
								</option>
								<?php endforeach; ?>
							</select>
							<input <?=$edit_disabled;?> autocomplete='off' name="srcbeginport_cust" id="srcbeginport_cust" type="text" size="5" value="<?php if (!$bfound && $pconfig['srcbeginport']) echo htmlspecialchars($pconfig['srcbeginport']); ?>">
						</td>
					</tr>
					<tr>
						<td >to:</td>
						<td style="padding:5px;">
							<select <?=$edit_disabled;?> name="srcendport" onchange="ext_change()">
								<option value="">other</option>
								<option value="any" <?php $bfound = 0; if ($pconfig['srcendport'] == "any") { echo "selected"; $bfound = 1; } ?>>any</option>
								<?php foreach ($wkports as $wkport => $wkportdesc): ?>
									<option value="<?=$wkport;?>" <?php if ($wkport == $pconfig['srcendport']) { echo "selected"; $bfound = 1; } ?>><?=htmlspecialchars($wkportdesc);?></option>
								<?php endforeach; ?>
							</select>
							<input <?=$edit_disabled;?> autocomplete='off' name="srcendport_cust" id="srcendport_cust" type="text" size="5" value="<?php if (!$bfound && $pconfig['srcendport']) echo htmlspecialchars($pconfig['srcendport']); ?>">
						</td>
					</tr>
				</table><!-- inline2 end -->
				Specify the source port or port range for this rule.<br>
				This is usually random and almost never equal to the destination port range.
			</td>
		</tr>

		<tr>
			<td valign="top" class="vncell">Destination</td>
			<td class="vtable">
				<input <?=$edit_disabled;?> name="dstnot" type="checkbox" id="dstnot" value="yes" <?php if ($pconfig['dstnot']) echo "checked"; ?>>
				<b>not</b>
				<p>Use this option to invert the sense of the match.</p>

				<table id="inline3" cellspacing="0" cellpadding="0">
					<tr>
						<td>Type</td>
						<td style="padding:5px;">
							<select <?=$edit_disabled;?> name="dsttype" onChange="typesel_change()">
								<?php
								$sel = is_specialnet($pconfig['dst']); ?>
								<option value="any" <?php if ($pconfig['dst'] == "any") { echo "selected"; } ?>>any</option>
								<option value="single" <?php if (($pconfig['dstmask'] == 32) && !$sel) { echo "selected"; $sel = 1; } ?>>Single host or alias"</option>
								<option value="network" <?php if (!$sel) echo "selected"; ?>>Network</option>
								<?php if(have_ruleint_access("pptp")): ?>
								<option value="pptp" <?php if ($pconfig['dst'] == "pptp") { echo "selected"; } ?>>PPTP clients</option>
								<?php endif; ?>
								<?php if(have_ruleint_access("pppoe")): ?>
								<option value="pppoe" <?php if ($pconfig['dst'] == "pppoe") { echo "selected"; } ?>>PPPoE clients</option>
								<?php endif; ?>
								<?php if(have_ruleint_access("l2tp")): ?>
								<option value="l2tp" <?php if ($pconfig['dst'] == "l2tp") { echo "selected"; } ?>>L2TP clients</option>
								<?php endif; ?>

								<?php foreach ($ifdisp as $if => $ifdesc): ?>
								<?php if(have_ruleint_access($if)): ?>
								<option value="<?=$if;?>" <?php if ($pconfig['dst'] == $if) { echo "selected"; } ?>><?=htmlspecialchars($ifdesc);?> subnet</option>
								<option value="<?=$if;?>ip"<?php if ($pconfig['dst'] == $if . "ip") { echo "selected"; } ?>>
								<?=$ifdesc;?> address</option>
								<?php endif; ?>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<tr>
						<td>Address:</td>
						<td style="padding:5px;">
							<input <?=$edit_disabled;?> autocomplete='off' name="dst" type="text" id="dst" size="20" value="<?php if (!is_specialnet($pconfig['dst'])) echo htmlspecialchars($pconfig['dst']);?>">
							/
							<select <?=$edit_disabled;?> name="dstmask" id="dstmask">
								<?php
								for ($i = 31; $i > 0; $i--): ?>
								<option value="<?=$i;?>" <?php if ($i == $pconfig['dstmask']) echo "selected"; ?>><?=$i;?></option>
								<?php endfor; ?>
							</select>
						</td>
					</tr>
				</table><!-- inline3 end -->
			</td>
		</tr>

		<tr id="dprtr" name="dprtr">
			<td valign="top" class="vncell">Destination port range</td>
			<td class="vtable">
				<table id="inline4" cellspacing="0" cellpadding="0">
					<tr>
						<td>from:</td>
						<td style="padding:5px;">
							<select <?=$edit_disabled;?> name="dstbeginport" onchange="dst_rep_change();ext_change()">
								<option value="">(other)</option>
								<option value="any" <?php $bfound = 0; if ($pconfig['dstbeginport'] == "any") { echo "selected"; $bfound = 1; } ?>>any</option>
								<?php foreach ($wkports as $wkport => $wkportdesc): ?>
								<option value="<?=$wkport;?>" <?php if ($wkport == $pconfig['dstbeginport']) { echo "selected"; $bfound = 1; }?>><?=htmlspecialchars($wkportdesc);?></option>
								<?php endforeach; ?>
							</select>
							<input <?=$edit_disabled;?> autocomplete='off' name="dstbeginport_cust" id="dstbeginport_cust" type="text" size="5" value="<?php if (!$bfound && $pconfig['dstbeginport']) echo htmlspecialchars($pconfig['dstbeginport']); ?>">
						</td>
					</tr>
					<tr>
						<td>to:</td>
						<td style="padding:5px;">
							<select <?=$edit_disabled;?> name="dstendport" onchange="ext_change()">
								<option value="">(other)</option>
								<option value="any" <?php $bfound = 0; if ($pconfig['dstendport'] == "any") { echo "selected"; $bfound = 1; } ?>>any</option>
								<?php foreach ($wkports as $wkport => $wkportdesc): ?>
								<option value="<?=$wkport;?>" <?php if ($wkport == $pconfig['dstendport']) { echo "selected"; $bfound = 1; } ?>><?=htmlspecialchars($wkportdesc);?></option>
								<?php endforeach; ?>
							</select>
							<input <?=$edit_disabled;?> autocomplete='off' name="dstendport_cust" id="dstendport_cust" type="text" size="5" value="<?php if (!$bfound && $pconfig['dstendport']) echo htmlspecialchars($pconfig['dstendport']); ?>">
						</td>
					</tr>
				</table><!-- inline4 end -->
				<span>
				    Specify the port or port range for the destination of the packet for this rule.
				</span>
			</td>
		</tr>

		<tr>
			<td valign="top" class="vncell">Log</td>
			<td class="vtable">
				<input name="log" type="checkbox" id="log" value="yes" <?php if ($pconfig['log']) echo "checked"; ?>>
				<b>Log packets that are handled by this rule </b>
				<br>
				<span>
					<b>Hint:</b> the firewall has limited local log space. Don't turn on logging for everything.
				</span>
			</td>
		</tr>
		<tr>
			<td valign="top" class="vncell">Description</td>
			<td class="vtable">
				<input name="descr" type="text" id="descr" maxlength="52" value="<?=htmlspecialchars($pconfig['descr']);?>">
				<br>
				<span>You may enter a description here for your reference.</span>
			</td>
		</tr>
		<?php if (!isset($id) || !($a_filter[$id] && firewall_check_for_advanced_options($a_filter[$id]) <> "")): ?>

		<tr>
			<td class="vncell"></td>
			<td class="vtable">
				<input name="Submit" type="submit" class="btn btn-inverse" value="Save">
				<input type="button" class="btn btn-default" value="Cancel" onclick="history.back()">
				<?php if (isset($id) && $a_filter[$id]): ?>
				<input name="id" type="hidden" value="<?=htmlspecialchars($id);?>">
				<?php endif; ?>
				<input name="after" type="hidden" value="<?=htmlspecialchars($after);?>">
			</td>
		</tr>
		<?php endif; ?>
		<tr>
			<td colspan="2" class="listtopic">ADVANCED FEATURES</td>
		</tr>

		<tr>
			<td valign="top" class="vncell">Source OS</td>
			<td class="vtable">
				<div id="showadvsourceosbox" <?php if ($pconfig['os']) echo "style='display:none'"; ?>>
					<input class="btn btn-mini" type="button" onClick="show_advanced_sourceos()" value="Show">
				</div>

				<div id="showsourceosadv" <?php if (empty($pconfig['os'])) echo "style='display:none'"; ?>> OS Type:
					<select name="os" id="os">
					<?php
						$ostypes = array(
							 "" => "any",
							"AIX" => "AIX",
							"Linux" => "Linux",
							"FreeBSD" => "FreeBSD",
							"NetBSD" => "NetBSD",
							"OpenBSD" => "OpenBSD",
							"Solaris" => "Solaris",
							"MacOS" => "MacOS",
							"Windows" => "Windows",
							"Novell" => "Novell",
							"NMAP" => "NMAP"
						);
						foreach ($ostypes as $ostype => $descr): ?>
						<option value="<?=$ostype;?>" <?php if ($ostype == $pconfig['os']) echo "selected"; ?>><?=htmlspecialchars($descr);?></option>
						<?php endforeach; ?>
					</select>
					<br><b>Note: </b>This only works for TCP rules.
				</div>
			</td>
		</tr>

		<tr id="tcpflags" name="tcpflags">
			<td valign="top" class="vncell">TCP Flags</td>
			<td class="vtable">
				<div id="showtcpflagsbox" <?php if ($pconfig['tcpflags_any'] || $pconfig['tcpflags1'] || $pconfig['tcpflags2']) echo "style='display:none'"; ?>>
					<input class="btn btn-mini" type="button" onClick="show_advanced_tcpflags()" value="Show">
				</div>
				<div id="showtcpflagsadv" <?php if (empty($pconfig['tcpflags_any']) && empty($pconfig['tcpflags1']) && empty($pconfig['tcpflags2'])) echo "style='display:none'"; ?>>
					<div id="tcpheader" name="tcpheader">
						<table id="inline5" cellspacing="0" cellpadding="0">
							<?php
								$setflags = explode(",", $pconfig['tcpflags1']);
								$outofflags = explode(",", $pconfig['tcpflags2']);
								$header = "<td width='40' ></td>";
								$tcpflags1 = "<td width='60' >set</td>";
								$tcpflags2 = "<td width='60' >out of</td>";
								foreach ($tcpflags as $tcpflag) {
									$header .= "<td  width='40' ><b>" . strtoupper($tcpflag) . "</b></td>\n";
									$tcpflags1 .= "<td  width='40' > <input type='checkbox' name='tcpflags1_{$tcpflag}' value='on' ";
									if (array_search($tcpflag, $setflags) !== false)
										$tcpflags1 .= "checked";
									$tcpflags1 .= "></td>\n";
									$tcpflags2 .= "<td  width='40' > <input type='checkbox' name='tcpflags2_{$tcpflag}' value='on' ";
									if (array_search($tcpflag, $outofflags) !== false)
										$tcpflags2 .= "checked";
									$tcpflags2 .= "></td>\n";
								}
								echo "<tr id='tcpheader' name='tcpheader'>{$header}</tr>\n";
								echo "<tr id='tcpflags1' name='tcpflags1'>{$tcpflags1}</tr>\n";
								echo "<tr id='tcpflags2' name='tcpflags2'>{$tcpflags2}</tr>\n";
							?>
						</table><!-- inline5 end -->
					</div>
					<p>
					<input onClick='tcpflags_anyclick(this);' type='checkbox' name='tcpflags_any' value='on' <?php if ($pconfig['tcpflags_any']) echo "checked"; ?>><strong>Any flags</strong>
					<br>
					Use this to choose TCP flags that must be set or cleared for this rule to match.
					</p>
				</div>
			</td>
		</tr>
			<?php
				//build list of schedules
				$schedules = array();
				$schedules[] = "none";//leave none to leave rule enabled all the time
				if(is_array($config['schedules']['schedule'])) {
					foreach ($config['schedules']['schedule'] as $schedule) {
						if ($schedule['name'] <> "")
							$schedules[] = $schedule['name'];
					}
				}
			?>
		<tr>
			<td valign="top" class="vncell">Schedule</td>
			<td class="vtable">
				<div id="showadvschedulebox" <?php if (!empty($pconfig['sched'])) echo "style='display:none'"; ?>>
					<input class="btn btn-mini" type="button" onClick="show_advanced_schedule()" value="Show">
				</div>
				<div id="showscheduleadv" <?php if (empty($pconfig['sched'])) echo "style='display:none'"; ?>>
					<p>
					<select name='sched'>
					<?php
					foreach($schedules as $schedule) {
						if($schedule == $pconfig['sched']) {
							$selected = " SELECTED";
						} else {
							$selected = "";
						}
						if ($schedule == "none") {
							echo "<option value=\"\" {$selected}>none</option>\n";
						} else {
							echo "<option value=\"{$schedule}\" {$selected}>{$schedule}</option>\n";
						}
					}
					?>
					</select><br>
					Leave as 'none' to leave the rule enabled all the time.
					</p>
				</div>
			</td>
		</tr>

		<tr>
			<td valign="top" class="vncell">Gateway</td>
			<td class="vtable">
				<div id="showadvgatewaybox" <?php if (!empty($pconfig['gateway'])) echo "style='display:none'"; ?>>
					<input class="btn btn-mini" type="button" onClick="show_advanced_gateway()" value="Show">
				</div>
				<div id="showgatewayadv" <?php if (empty($pconfig['gateway'])) echo "style='display:none'"; ?>>
					<p>
					<select name='gateway'>
						<option value="">default</option>
						<?php
						/* build a list of gateways */
						$gateways = return_gateways_array();
						// add statically configured gateways to list
						foreach($gateways as $gwname => $gw) {
							if($gw == "")
								continue;
							if($gwname == $pconfig['gateway']) {
								$selected = " SELECTED";
							} else {
								$selected = "";
							}
							echo "<option value=\"{$gwname}\" {$selected}>{$gw['name']} - {$gw['gateway']}</option>\n";
						}
						/* add gateway groups to the list */
						if (is_array($config['gateways']['gateway_group'])) {
							foreach($config['gateways']['gateway_group'] as $gw_group) {
								if($gw_group['name'] == "")
									continue;
								if($pconfig['gateway'] == $gw_group['name']) {
									echo "<option value=\"{$gw_group['name']}\" SELECTED>{$gw_group['name']}</option>\n";
								} else {
									echo "<option value=\"{$gw_group['name']}\">{$gw_group['name']}</option>\n";
								}
							}
						}
						?>
					</select><br>
					Leave as 'default' to use the system routing table.<br>
					Or choose a gateway to utilize policy based routing.
					</p>
				</div>
			</td>
		</tr>

		<tr>
			<td valign="top" class="vncell">Limiter (In/Out)</td>
			<td class="vtable">
				<div id="showadvinoutbox" <?php if (!empty($pconfig['dnpipe'])) echo "style='display:none'"; ?>>
					<input class="btn btn-mini" type="button" onClick="show_advanced_inout()" value="Show">
				</div>
				<div id="showinoutadv" <?php if (empty($pconfig['dnpipe'])) echo "style='display:none'"; ?>>
					<p>
					<select name="dnpipe">
						<?php
							if (!is_array($dnqlist))
								$dnqlist = array();
							echo "<option value=\"none\"";
							if (!$dnqselected) echo " SELECTED";
							echo " >none</option>";
							foreach ($dnqlist as $dnq => $dnqkey) {
								if($dnq == "")
									continue;
								echo "<option value=\"$dnqkey\"";
								if ($dnqkey == $pconfig['dnpipe']) {
									$dnqselected = 1;
									echo " SELECTED";
								}
								echo ">{$dnq}</option>";
							}
						?>
					</select> /
					<select name="pdnpipe">
						<?php
							$dnqselected = 0;
							echo "<option value=\"none\"";
							if (!$dnqselected) echo " SELECTED";
							echo " >none</option>";
							foreach ($dnqlist as $dnq => $dnqkey) {
								if($dnq == "")
									continue;
								echo "<option value=\"$dnqkey\"";
								if ($dnqkey == $pconfig['pdnpipe']) {
									$dnqselected = 1;
									echo " SELECTED";
								}
								echo ">{$dnq}</option>";
							}
						?>
					</select>
				</div>
			</td>
		</tr>

		<?php
			pfSense_handle_custom_code("/usr/local/pkg/firewall_rules/htmlphplate");
		?>
		<tr>
			<td class="vncell"></td>
			<td class="vtable">
				<input name="Submit" type="submit" class="btn btn-inverse" value="Save">
				<input type="button" class="btn btn-default" value="Cancel" onclick="history.back()">
				<?php if (isset($id) && $a_filter[$id]): ?>
					<input name="id" type="hidden" value="<?=htmlspecialchars($id);?>">
				<?php endif; ?>
				<input name="after" type="hidden" value="<?=htmlspecialchars($after);?>">
			</td>
		</tr>
	</table>
</form>
<script language="JavaScript">
<!--
	ext_change();
	typesel_change();
	proto_change();
	<?php if ( (!empty($pconfig['srcbeginport']) && $pconfig['srcbeginport'] != "any") || (!empty($pconfig['srcendport']) && $pconfig['srcendport'] != "any") ): ?>
	show_source_port_range();
	<?php endif; ?>

<?php
	$isfirst = 0;
	$aliases = "";
	$addrisfirst = 0;
	$aliasesaddr = "";
	if($config['aliases']['alias'] <> "" and is_array($config['aliases']['alias']))
		foreach($config['aliases']['alias'] as $alias_name) {
			switch ($alias_name['type']) {
			case "port":
				if($isfirst == 1) $portaliases .= ",";
				$portaliases .= "'" . $alias_name['name'] . "'";
				$isfirst = 1;
				break;
			case "host":
			case "network":
			case "urltable":
				if($addrisfirst == 1) $aliasesaddr .= ",";
				$aliasesaddr .= "'" . $alias_name['name'] . "'";
				$addrisfirst = 1;
				break;
			default:
				break;
			}
		}
?>

	var addressarray=new Array(<?php echo $aliasesaddr; ?>);
	var customarray=new Array(<?php echo $portaliases; ?>);

	var oTextbox1 = new AutoSuggestControl(document.getElementById("src"), new StateSuggestions(addressarray));
	var oTextbox2 = new AutoSuggestControl(document.getElementById("srcbeginport_cust"), new StateSuggestions(customarray));
	var oTextbox3 = new AutoSuggestControl(document.getElementById("srcendport_cust"), new StateSuggestions(customarray));
	var oTextbox4 = new AutoSuggestControl(document.getElementById("dst"), new StateSuggestions(addressarray));
	var oTextbox5 = new AutoSuggestControl(document.getElementById("dstbeginport_cust"), new StateSuggestions(customarray));
	var oTextbox6 = new AutoSuggestControl(document.getElementById("dstendport_cust"), new StateSuggestions(customarray));
</script>
</div>
</body>
</html>
