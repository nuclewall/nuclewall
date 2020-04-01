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
	$pconfig['descr'] = base64_decode($a_filter[$id]['descr']);

	if (isset($a_filter[$id]['tcpflags_any']))
		$pconfig['tcpflags_any'] = true;
	else {
		if (isset($a_filter[$id]['tcpflags1']) && $a_filter[$id]['tcpflags1'] <> "")
			$pconfig['tcpflags1'] = $a_filter[$id]['tcpflags1'];
		if (isset($a_filter[$id]['tcpflags2']) && $a_filter[$id]['tcpflags2'] <> "")
			$pconfig['tcpflags2'] = $a_filter[$id]['tcpflags2'];
	}

	if (isset($a_filter[$id]['tag']) && $a_filter[$id]['tag'] <> "")
		$pconfig['tag'] = $a_filter[$id]['tag'];
	if (isset($a_filter[$id]['tagged']) && $a_filter[$id]['tagged'] <> "")
        	$pconfig['tagged'] = $a_filter[$id]['tagged'];
	if (isset($a_filter[$id]['quick']) && $a_filter[$id]['quick'])
		$pconfig['quick'] = $a_filter[$id]['quick'];
	if (isset($a_filter[$id]['allowopts']))
		$pconfig['allowopts'] = true;
	if (isset($a_filter[$id]['disablereplyto']))
		$pconfig['disablereplyto'] = true;

	/* advanced */
	$pconfig['max'] = $a_filter[$id]['max'];
	$pconfig['max-src-nodes'] = $a_filter[$id]['max-src-nodes'];
	$pconfig['max-src-conn'] = $a_filter[$id]['max-src-conn'];
	$pconfig['max-src-states'] = $a_filter[$id]['max-src-states'];
	$pconfig['statetype'] = $a_filter[$id]['statetype'];
	$pconfig['statetimeout'] = $a_filter[$id]['statetimeout'];

	/* advanced - new connection per second banning*/
	$pconfig['max-src-conn-rate'] = $a_filter[$id]['max-src-conn-rate'];
	$pconfig['max-src-conn-rates'] = $a_filter[$id]['max-src-conn-rates'];

	/* Multi-WAN next-hop support */
	$pconfig['gateway'] = $a_filter[$id]['gateway'];

	/* Shaper support */
	$pconfig['defaultqueue'] = $a_filter[$id]['defaultqueue'];
	$pconfig['ackqueue'] = $a_filter[$id]['ackqueue'];
	$pconfig['dnpipe'] = $a_filter[$id]['dnpipe'];
	$pconfig['pdnpipe'] = $a_filter[$id]['pdnpipe'];
	$pconfig['l7container'] = $a_filter[$id]['l7container'];

	//schedule support
	$pconfig['sched'] = $a_filter[$id]['sched'];
	if (!isset($_GET['dup']))
		$pconfig['associated-rule-id'] = $a_filter[$id]['associated-rule-id'];

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

	if( isset($a_filter[$id]['associated-rule-id']) ) {
		$_POST['proto'] = $pconfig['proto'];
		if ($pconfig['proto'] == "icmp")
			$_POST['icmptype'] = $pconfig['icmptype'];
	}

	if ($_POST['type'] == "reject" && $_POST['proto'] <> "tcp")
		$input_errors[] = "Reject türündeki kurallar sadece TCP protokolü ile kullanılabilir";

	if ($_POST['type'] == "match" && $_POST['defaultqueue'] == "none")
		$input_errors[] = "Queue türündeki kurallar sadece kuyruklarla kullanılabilir.";

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
	if ( isset($a_filter[$id]['associated-rule-id'])===false ) {
		$reqdfields[] = "src";
		$reqdfields[] = "dst";
	}
	$reqdfieldsn = explode(",", "Type,Protocol");
	if ( isset($a_filter[$id]['associated-rule-id'])===false ) {
		$reqdfieldsn[] = "Source";
		$reqdfieldsn[] = "Destination";
	}

	if($_POST['statetype'] == "modulate state" or $_POST['statetype'] == "synproxy state") {
		if( $_POST['proto'] != "tcp" )
			$input_errors[] = sprintf("%s sadece TCP protokolü ile geçerlidir.",$_POST['statetype']);
		if(($_POST['statetype'] == "synproxy state") && ($_POST['gateway'] != ""))
			$input_errors[] = sprintf("%s ağ geçidi 'varsayılan' olarak ayarlıysa geçerlidir.", $_POST['statetype']);
	}

	if ( isset($a_filter[$id]['associated-rule-id'])===false &&
	(!(is_specialnet($_POST['srctype']) || ($_POST['srctype'] == "single"))) ) {
		$reqdfields[] = "srcmask";
		$reqdfieldsn[] = "Source bit count";
	}
	if ( isset($a_filter[$id]['associated-rule-id'])===false &&
	(!(is_specialnet($_POST['dsttype']) || ($_POST['dsttype'] == "single"))) ) {
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
                $input_errors[] = sprintf("%s geçerli bir kaynak başlangıç portu değil. 1 ile 65535 arasında sayısal bir değer veya bir port Takma Ad'ı olmalıdır.",$_POST['srcbeginposrt']);
        if ($_POST['srcendport'] && !is_portoralias($_POST['srcendport']))
                $input_errors[] = sprintf("%s  geçerli bir kaynak bitiş portu değil. 1 ile 65535 arasında sayısal bir değer veya bir port Takma Ad'ı olmalıdır.", $_POST['srcendport']);
        if ($_POST['dstbeginport'] && !is_portoralias($_POST['dstbeginport']))
                $input_errors[] = sprintf("%s geçerli bir hedef başlangıç portu değil. 1 ile 65535 arasında sayısal bir değer veya bir port Takma Ad'ı olmalıdır.", $_POST['dstbeginport']);
        if ($_POST['dstendport'] && !is_portoralias($_POST['dstendport']))
                $input_errors[] = sprintf("%s geçerli bir hedef bitiş portu değil. 1 ile 65535 arasında sayısal bir değer veya bir port Takma Ad'ı olmalıdır.", $_POST['dstendport']);
	if ( !$_POST['srcbeginport_cust'] && $_POST['srcendport_cust'])
		if (is_alias($_POST['srcendport_cust']))
			$input_errors[] = 'Kaynak port başlangıcı için Takma Ad kullandıysanız, aynı Takma Ad\'ı bitişte de kullanmalısınız.';
	if ( $_POST['srcbeginport_cust'] && $_POST['srcendport_cust']){
		if (is_alias($_POST['srcendport_cust']) && is_alias($_POST['srcendport_cust']) && $_POST['srcbeginport_cust'] != $_POST['srcendport_cust'])
			$input_errors[] = 'Kaynak port başlangıç ve bitişi için aynı Takma Ad kullanılmalıdır.';
		if ((is_alias($_POST['srcbeginport_cust']) && (!is_alias($_POST['srcendport_cust']) && $_POST['srcendport_cust']!='')) ||
		    ((!is_alias($_POST['srcbeginport_cust']) && $_POST['srcbeginport_cust']!='') && is_alias($_POST['srcendport_cust'])))
			$input_errors[] = 'Başlangıç ve bitiş portları için hem Takma Ad\'ı hem de port numarasını aynı anda kullanamazsınız.';
	}
	if ( !$_POST['dstbeginport_cust'] && $_POST['dstendport_cust'])
		if (is_alias($_POST['dstendport_cust']))
			$input_errors[] = 'Hedef port başlangıcı için Takma Ad kullandıysanız, aynı Takma Ad\'ı bitişte de kullanmalısınız.';
	if ( $_POST['dstbeginport_cust'] && $_POST['dstendport_cust']){
		if (is_alias($_POST['dstendport_cust']) && is_alias($_POST['dstendport_cust']) && $_POST['dstbeginport_cust'] != $_POST['dstendport_cust'])
			$input_errors[] = 'Hedef port başlangıç ve bitişi için aynı Takma Ad kullanılmalıdır.';
		if ((is_alias($_POST['dstbeginport_cust']) && (!is_alias($_POST['dstendport_cust']) && $_POST['dstendport_cust']!='')) ||
		    ((!is_alias($_POST['dstbeginport_cust']) && $_POST['dstbeginport_cust']!='') && is_alias($_POST['dstendport_cust'])))
			$input_errors[] = 'Başlangıç ve bitiş portları için hem Takma Ad\'ı hem de port numarasını aynı anda kullanamazsınız.';
	}

	/* if user enters an alias and selects "network" then disallow. */
	if($_POST['srctype'] == "network") {
		if(is_alias($_POST['src']))
			$input_errors[] = "Takma ad içeriği olarak yalnızca host adı veya takma ad belirtlemisiniz.";
	}
	if($_POST['dsttype'] == "network") {
		if(is_alias($_POST['dst']))
			$input_errors[] = "Takma ad içeriği olarak yalnızca host adı veya takma ad belirtlemisiniz.";
	}

	if (!is_specialnet($_POST['srctype'])) {
		if (($_POST['src'] && !is_ipaddroralias($_POST['src']))) {
			$input_errors[] = sprintf("%s geçerli bir kaynak IP adresi veya Takma Ad değil." ,$_POST['src']);
		}
		if (($_POST['srcmask'] && !is_numericint($_POST['srcmask']))) {
			$input_errors[] = "Geçerli bir kaynak adres bit sayısı belirtilmelidir.";
		}
	}
	if (!is_specialnet($_POST['dsttype'])) {
		if (($_POST['dst'] && !is_ipaddroralias($_POST['dst']))) {
			$input_errors[] = sprintf("%s geçerli bir hedef IP adresi veya Takma Ad değil." ,$_POST['dst']);
		}
		if (($_POST['dstmask'] && !is_numericint($_POST['dstmask']))) {
			$input_errors[] = "Geçerli bir hedef adres bit sayısı belirtilmelidir.";
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
			$input_errors[] = "OS tespiti sadece TCP protokolü ile yapılabilir.";

	if ($_POST['ackqueue'] && $_POST['ackqueue'] != "none") {
		if ($_POST['defaultqueue'] == "none" )
			$input_errors[] = "Kabul kuyruğu seçtiğinizde başka bir kuyruk da seçmelisiniz.";
		else if ($_POST['ackqueue'] == $_POST['defaultqueue'])
			$input_errors[] = "Kabul kuyruğu ve kuyruk aynı olamaz.";
	}
	if (isset($_POST['floating']) && $_POST['pdnpipe'] != "none" && (empty($_POST['direction']) || $_POST['direction'] == "any"))
		$input_errors[] = "Sınırlayıcıları bir hedef seçmeden değişen kurallarda kullanamazsınız.";
	if (isset($_POST['floating']) && $_POST['gateway'] != "" && (empty($_POST['direction']) || $_POST['direction'] == "any"))
		$input_errors[] = "Ağ geçitlerini bir hedef seçmeden değişen kurallarda kullanamazsınız.";
	if ($_POST['pdnpipe'] && $_POST['pdnpipe'] != "none") {
		if ($_POST['dnpipe'] == "none" )
			$input_errors[] = "Çıkıştan önce giriş için bir kuyruk seçmelisiniz.";
		else if ($_POST['pdnpipe'] == $_POST['dnpipe'])
			$input_errors[] = "Giriş ve çıkış kuyruğu aynı olamaz.";
		else if ($pdnpipe[0] == "?" && $dnpipe[0] <> "?")
			$input_errors[] = "Giriş ve çıkışın için birini kuyruk, diğerini sanal arayüz seçemezsiniz. İkisi de aynı türde olmalıdır.";
		else if ($dnpipe[0] == "?" && $pdnpipe[0] <> "?")
			$input_errors[] = "Giriş ve çıkışın için birini kuyruk, diğerini sanal arayüz seçemezsiniz. İkisi de aynı türde olmalıdır.";
		if ($_POST['direction'] == "out" && empty($_POST['gateway']))
			$input_errors[] = "Lütfen bir ağ geçidi seçin. Sınırlayıcının düzgün çalışması için genelde arayüzün kullandığı ağ geçidi seçilir.";
	}
	if( !empty($_POST['ruleid']) && !ctype_digit($_POST['ruleid']))
		$input_errors[] = "ID sayısal bir değer olmalı.";
	if($_POST['l7container'] && $_POST['l7container'] != "none") {
		if(!($_POST['proto'] == "tcp" || $_POST['proto'] == "udp" || $_POST['proto'] == "tcp/udp"))
			$input_errors[] = "TCP veya UDP protokolleri için sadece Katman7 taşıyıcı seçebilirsiniz.";
		if ($_POST['type'] <> "pass")
			$input_errors[] = "Pass türü kurallar için sadece Katman7 taşıyıcı seçebilirsiniz.";

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
			$input_errors[] = "Ayarlanması gereken TCP bayraklarını belirttiğinizde, haricinde kalması gereken bayrakları da belirtmelisiniz.";
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

		if (isset($_POST['tag']))
			$filterent['tag'] = $_POST['tag'];
		if (isset($_POST['tagged']))
			$filterent['tagged'] = $_POST['tagged'];
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
		if ($_POST['allowopts'] == "yes")
			$filterent['allowopts'] = true;
		else
			unset($filterent['allowopts']);
		if ($_POST['disablereplyto'] == "yes")
			$filterent['disablereplyto'] = true;
		else
			unset($filterent['disablereplyto']);
		$filterent['max'] = $_POST['max'];
		$filterent['max-src-nodes'] = $_POST['max-src-nodes'];
		$filterent['max-src-conn'] = $_POST['max-src-conn'];
		$filterent['max-src-states'] = $_POST['max-src-states'];
		$filterent['statetimeout'] = $_POST['statetimeout'];
		$filterent['statetype'] = $_POST['statetype'];
		$filterent['os'] = $_POST['os'];

		/* Nosync directive - do not xmlrpc sync this item */

		$filterent['nosync'] = true;

		/* unless both values are provided, unset the values - ticket #650 */
		if($_POST['max-src-conn-rate'] <> "" and $_POST['max-src-conn-rates'] <> "") {
			$filterent['max-src-conn-rate'] = $_POST['max-src-conn-rate'];
			$filterent['max-src-conn-rates'] = $_POST['max-src-conn-rates'];
		} else {
			unset($filterent['max-src-conn-rate']);
			unset($filterent['max-src-conn-rates']);
		}

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
		strncpy($filterent['descr'], base64_encode($_POST['descr']), 52);

		if ($_POST['gateway'] != "") {
			$filterent['gateway'] = $_POST['gateway'];
		}

		if (isset($_POST['defaultqueue']) && $_POST['defaultqueue'] != "none") {
			$filterent['defaultqueue'] = $_POST['defaultqueue'];
			if (isset($_POST['ackqueue']) && $_POST['ackqueue'] != "none")
				$filterent['ackqueue'] = $_POST['ackqueue'];
		}

		if (isset($_POST['dnpipe']) && $_POST['dnpipe'] != "none") {
			$filterent['dnpipe'] = $_POST['dnpipe'];
			if (isset($_POST['pdnpipe']) && $_POST['pdnpipe'] != "none")
				$filterent['pdnpipe'] = $_POST['pdnpipe'];
		}

		if (isset($_POST['l7container']) && $_POST['l7container'] != "none") {
			$filterent['l7container'] = $_POST['l7container'];
		}

		if ($_POST['sched'] != "") {
			$filterent['sched'] = $_POST['sched'];
		}

		// If we have an associated nat rule, make sure the source and destination doesn't change
		if( isset($a_filter[$id]['associated-rule-id']) ) {
			$filterent['interface'] = $a_filter[$id]['interface'];
			if (isset($a_filter[$id]['protocol']))
				$filterent['protocol'] = $a_filter[$id]['protocol'];
			else if (isset($filterent['protocol']))
				unset($filterent['protocol']);
			if ($a_filter[$id]['protocol'] == "icmp" && $a_filter[$id]['icmptype'])
				$filterent['icmptype'] = $a_filter[$id]['icmptype'];
			else if (isset($filterent['icmptype']))
				unset($filterent['icmptype']);
			$filterent['source'] = $a_filter[$id]['source'];
			$filterent['destination'] = $a_filter[$id]['destination'];
			$filterent['associated-rule-id'] = $a_filter[$id]['associated-rule-id'];
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

		write_config("Bir guvenlik duvari kurali yapilandirildi");
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

$pgtitle = array('GÜVENLİK DUVARI', 'KURALLAR', 'DÜZENLE');
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
			<td colspan="2" class="listtopic">KURAL DÜZENLE</td>
		</tr>
		<?php
			pfSense_handle_custom_code("/usr/local/pkg/firewall_rules/htmlphpearly");
		?>
		<tr>
			<td valign="top" class="vncell">Eylem</td>
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
					Aşağıda belirtilen kritere uyan paketlere ne yapılacağını seçin.<br>
					<b>İpucu:</b> Block ile Reject arasındaki fark şudur; reddedilen paket(REJECT) (TCP sıfırlama veya UDP ICM port erişilemez)
					sahibine geri gönderilir, engellenen paket(BLOCK) ise sessizce silinir. İki durumda da asıl paket 'çöpe gider'.
				</span>
			</td>
		</tr>
		<tr>
			<td valign="top" class="vncell">Devre Dışı</td>
			<td class="vtable">
				<input name="disabled" type="checkbox" id="disabled" value="yes" <?php if ($pconfig['disabled']) echo "checked"; ?>>
				<strong>Bu kuralı devre dışı bırak</strong><br>
				<span>Kuralı silmeden devre dışı bırakmak için işaretleyin.</span>
			</td>
		</tr>

		<?php if ($if == "FloatingRules" || isset($pconfig['floating'])): ?>

		<tr>
			<td valign="top" class="vncell">Hızlı</td>
			<td class="vtable">
				<input name="quick" type="checkbox" id="quick" value="yes" <?php if ($pconfig['quick']) echo "checked=\"checked\""; ?> />
				<b>Eşleşen olduğunda eylemi hemen gerçekleştir.</b><br>
				<span>Bu kurala uyan trafiğin hemen uygulanması için bu seçeneği işaretleyin.</span>
			</td>
			</tr>

		<?php endif; ?>
		<?php $edit_disabled = ""; ?>
		<?php if( isset($pconfig['associated-rule-id']) ): ?>

		<tr>
			<td valign="top" class="vncell">İlişkili filtre kuralı</td>
			<td class="vtable">
				<span><b>Not:</b></span>Bu bir NAT kuralına ilişkilendirilmiş.<br>
				İlişkilendirilmiş filtreleme kurallarının arayüz, protokol, kaynak ya da hedefini değiştiremezsiniz.<br>

				<?php
					$edit_disabled = "disabled";
					if (is_array($config['nat']['rule'])) {
						foreach( $config['nat']['rule'] as $index => $nat_rule ) {
							if( isset($nat_rule['associated-rule-id']) && $nat_rule['associated-rule-id']==$pconfig['associated-rule-id'] ) {
								echo "<a href=\"firewall_nat_edit.php?id={$index}\">" . "NAT kuralını göster" . "</a><br>";
								break;
							}
						}
					}
					echo "<input name='associated-rule-id' id='associated-rule-id' type='hidden' value='{$pconfig['associated-rule-id']}' >";
					if (!empty($pconfig['interface']))
						echo "<input name='interface' id='interface' type='hidden' value='{$pconfig['interface']}' >";
				?>

				<script type="text/javascript">
				editenabled = 0;
				</script>
			</td>
		</tr>

		<?php endif; ?>

		<tr>
			<td valign="top" class="vncell">Arayüz</td>
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
				Bu kurala uyacak paketlerin hangi arayüzden geleceğinini seçiniz.
			</td>
		</tr>

		<?php if ($if == "FloatingRules" || isset($pconfig['floating'])): ?>

		<tr>
			<td valign="top" class="vncell">Yön</td>
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
			<td valign="top" class="vncell">Protokol</td>
			<td class="vtable">
				<select <?=$edit_disabled;?> name="proto" onchange="proto_change()">
				<?php
				$protocols = explode(" ", "TCP UDP TCP/UDP ICMP ESP AH GRE IGMP OSPF any carp pfsync");
				foreach ($protocols as $proto): ?>
					<option value="<?=strtolower($proto);?>" <?php if (strtolower($proto) == $pconfig['proto']) echo "selected"; ?>><?=htmlspecialchars($proto);?></option>
				<?php	endforeach; ?>
				</select>
				<br>
				Bu kuralın eşleşeceği IP protokolünü seçiniz.<br>
				<b>İpucu:</b> Çoğu durumda<em>TCP</em> belirtilir.
			</td>
		</tr>

		<tr id="icmpbox" name="icmpbox">
			<td valign="top" class="vncell">ICMP türü</td>
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
				Eğer yukarıdan ICMP protokolünü seçtiyseniz burada ICMP türü belirtebilirsiniz.
			</td>
		</tr>

		<tr>
			<td valign="top" class="vncell">Kaynak</td>
			<td class="vtable">
				<input <?=$edit_disabled;?> name="srcnot" type="checkbox" id="srcnot" value="yes" <?php if ($pconfig['srcnot']) echo "checked"; ?>>
				<b>Haricinde</b>
				<p>Eşleştirmeyi tersine çevirmek için bu seçeneği kullanabilirsiniz.</p>

				<table id="inline1" cellspacing="0" cellpadding="0">
					<tr>
						<td>Tür</td>
						<td style="padding-left:5px;">
							<select <?=$edit_disabled;?> name="srctype" onChange="typesel_change()">
								<?php
								$sel = is_specialnet($pconfig['src']); ?>
								<option value="any"     <?php if ($pconfig['src'] == "any") { echo "selected"; } ?>>Hepsi</option>
								<option value="single"  <?php if (($pconfig['srcmask'] == 32) && !$sel) { echo "selected"; $sel = 1; } ?>>Tek IP adresi veya Takma Ad</option>
								<option value="network" <?php if (!$sel) echo "selected"; ?>>Ağ</option>
								<?php if(have_ruleint_access("pptp")): ?>
								<option value="pptp"    <?php if ($pconfig['src'] == "pptp") { echo "selected"; } ?>>PPTP istemci</option>
								<?php endif; ?>
								<?php if(have_ruleint_access("pppoe")): ?>
								<option value="pppoe"   <?php if ($pconfig['src'] == "pppoe") { echo "selected"; } ?>>PPPoE istemci</option>
								<?php endif; ?>
								<?php if(have_ruleint_access("l2tp")): ?>
								<option value="l2tp"   <?php if ($pconfig['src'] == "l2tp") { echo "selected"; } ?>>L2TP istemci</option>
								<?php endif; ?>
								<?php
								foreach ($ifdisp as $ifent => $ifdesc): ?>
								<?php if(have_ruleint_access($ifent)): ?>
								<option value="<?=$ifent;?>" <?php if ($pconfig['src'] == $ifent) { echo "selected"; } ?>><?=htmlspecialchars($ifdesc);?> alt ağ</option>
								<option value="<?=$ifent;?>ip"<?php if ($pconfig['src'] ==  $ifent . "ip") { echo "selected"; } ?>>
									<?=$ifdesc?> adresi
								</option>
								<?php endif; ?>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<td>Adres</td>
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
					<input style="margin:0;padding:0;" <?=$edit_disabled;?> type="button" class="btn btn-link" onClick="show_source_port_range()" value="Gelişmiş">
				</div>

			</td>
		</tr>

		<tr style="display:none" id="sprtable" name="sprtable">
			<td valign="top" class="vncell">Kaynak port aralığı</td>
			<td class="vtable">
				<table id="inline2" cellspacing="0" cellpadding="0">
					<tr>
						<td>Başlangıç</td>
						<td style="padding:5px;">
							<select <?=$edit_disabled;?> name="srcbeginport" onchange="src_rep_change();ext_change()">
								<option value="">diğer</option>
								<option value="any" <?php $bfound = 0; if ($pconfig['srcbeginport'] == "any") { echo "selected"; $bfound = 1; } ?>>Hepsi</option>
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
						<td >Bitiş</td>
						<td style="padding:5px;">
							<select <?=$edit_disabled;?> name="srcendport" onchange="ext_change()">
								<option value="">diğer</option>
								<option value="any" <?php $bfound = 0; if ($pconfig['srcendport'] == "any") { echo "selected"; $bfound = 1; } ?>>Hepsi</option>
								<?php foreach ($wkports as $wkport => $wkportdesc): ?>
									<option value="<?=$wkport;?>" <?php if ($wkport == $pconfig['srcendport']) { echo "selected"; $bfound = 1; } ?>><?=htmlspecialchars($wkportdesc);?></option>
								<?php endforeach; ?>
							</select>
							<input <?=$edit_disabled;?> autocomplete='off' name="srcendport_cust" id="srcendport_cust" type="text" size="5" value="<?php if (!$bfound && $pconfig['srcendport']) echo htmlspecialchars($pconfig['srcendport']); ?>">
						</td>
					</tr>
				</table><!-- inline2 end -->
				Bu kural için kaynak port ya da port aralığı belirtiniz. <br>
				Kaynak portları genellikle rastgele seçilir ve neredeyse hiçbir zaman hedef port portla aynı değildir.
			</td>
		</tr>

		<tr>
			<td valign="top" class="vncell">Hedef</td>
			<td class="vtable">
				<input <?=$edit_disabled;?> name="dstnot" type="checkbox" id="dstnot" value="yes" <?php if ($pconfig['dstnot']) echo "checked"; ?>>
				<b>Haricinde</b>
				<p>Eşleştirmeyi tersine çevirmek için bu seçeneği kullanabilirsiniz.</p>

				<table id="inline3" cellspacing="0" cellpadding="0">
					<tr>
						<td>Tür</td>
						<td style="padding:5px;">
							<select <?=$edit_disabled;?> name="dsttype" onChange="typesel_change()">
								<?php
								$sel = is_specialnet($pconfig['dst']); ?>
								<option value="any" <?php if ($pconfig['dst'] == "any") { echo "selected"; } ?>>Hepsi</option>
								<option value="single" <?php if (($pconfig['dstmask'] == 32) && !$sel) { echo "selected"; $sel = 1; } ?>>Tek IP adresi veya Takma Ad</option>
								<option value="network" <?php if (!$sel) echo "selected"; ?>>Ağ</option>
								<?php if(have_ruleint_access("pptp")): ?>
								<option value="pptp" <?php if ($pconfig['dst'] == "pptp") { echo "selected"; } ?>>PPTP istemci</option>
								<?php endif; ?>
								<?php if(have_ruleint_access("pppoe")): ?>
								<option value="pppoe" <?php if ($pconfig['dst'] == "pppoe") { echo "selected"; } ?>>PPPoE istemci</option>
								<?php endif; ?>
								<?php if(have_ruleint_access("l2tp")): ?>
								<option value="l2tp" <?php if ($pconfig['dst'] == "l2tp") { echo "selected"; } ?>>L2TP istemci</option>
								<?php endif; ?>

								<?php foreach ($ifdisp as $if => $ifdesc): ?>
								<?php if(have_ruleint_access($if)): ?>
								<option value="<?=$if;?>" <?php if ($pconfig['dst'] == $if) { echo "selected"; } ?>><?=htmlspecialchars($ifdesc);?> alt ağ</option>
								<option value="<?=$if;?>ip"<?php if ($pconfig['dst'] == $if . "ip") { echo "selected"; } ?>>
								<?=$ifdesc;?> adresi</option>
								<?php endif; ?>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>

					<tr>
						<td>Adres:</td>
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
			<td valign="top" class="vncell">Hedef port aralığı</td>
			<td class="vtable">
				<table id="inline4" cellspacing="0" cellpadding="0">
					<tr>
						<td>Başlangıç</td>
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
						<td>Bitiş</td>
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
					Bu kural için hedef port yada port aralığı belirtin.<br>
					<b>İpucu:</b> Sadece bir portun filtrelenmesi için port türü seçip aralık alanını boş bırakınız.
				</span>
			</td>
		</tr>

		<tr>
			<td valign="top" class="vncell">Olay Kaydı</td>
			<td class="vtable">
				<input name="log" type="checkbox" id="log" value="yes" <?php if ($pconfig['log']) echo "checked"; ?>>
				<b>	Bu kural tarafından işlenen paketlerin kaydını tut</b>
				<br>
				<span>
					<b>İpucu:</b> Yerel günlük kayıt alanı kısıtlıdır.
				</span>
			</td>
		</tr>
		<tr>
			<td valign="top" class="vncell">Açıklama</td>
			<td class="vtable">
				<input name="descr" type="text" id="descr" maxlength="52" value="<?=htmlspecialchars($pconfig['descr']);?>">
				<br>
				<span>İsteğe bağlı bir açıklama girebilirsiniz.</span>
			</td>
		</tr>
		<?php if (!isset($id) || !($a_filter[$id] && firewall_check_for_advanced_options($a_filter[$id]) <> "")): ?>

		<tr>
			<td class="vncell"></td>
			<td class="vtable">
				<input name="Submit" type="submit" class="btn btn-inverse" value="Kaydet">
				<input type="button" class="btn btn-default" value="İptal" onclick="history.back()">
				<?php if (isset($id) && $a_filter[$id]): ?>
				<input name="id" type="hidden" value="<?=htmlspecialchars($id);?>">
				<?php endif; ?>
				<input name="after" type="hidden" value="<?=htmlspecialchars($after);?>">
			</td>
		</tr>
		<?php endif; ?>
		<tr>
			<td colspan="2" class="listtopic">GELİŞMİŞ AYARLAR</td>
		</tr>

		<tr>
			<td valign="top" class="vncell">Kaynak OS</td>
			<td class="vtable">
				<div id="showadvsourceosbox" <?php if ($pconfig['os']) echo "style='display:none'"; ?>>
					<input class="btn btn-mini" type="button" onClick="show_advanced_sourceos()" value="Göster">
				</div>

				<div id="showsourceosadv" <?php if (empty($pconfig['os'])) echo "style='display:none'"; ?>> İşletim sistemi:
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
					<br><b>Not: </b>Bu özellik sadece <b>TCP</b> protokolü ile çalışır.
				</div>
			</td>
		</tr>

		<tr id="tcpflags" name="tcpflags">
			<td valign="top" class="vncell">TCP Bayrakları</td>
			<td class="vtable">
				<div id="showtcpflagsbox" <?php if ($pconfig['tcpflags_any'] || $pconfig['tcpflags1'] || $pconfig['tcpflags2']) echo "style='display:none'"; ?>>
					<input class="btn btn-mini" type="button" onClick="show_advanced_tcpflags()" value="Göster">
				</div>
				<div id="showtcpflagsadv" <?php if (empty($pconfig['tcpflags_any']) && empty($pconfig['tcpflags1']) && empty($pconfig['tcpflags2'])) echo "style='display:none'"; ?>>
					<div id="tcpheader" name="tcpheader">
						<table id="inline5" cellspacing="0" cellpadding="0">
							<?php
								$setflags = explode(",", $pconfig['tcpflags1']);
								$outofflags = explode(",", $pconfig['tcpflags2']);
								$header = "<td width='40' ></td>";
								$tcpflags1 = "<td width='60' >ayarlı</td>";
								$tcpflags2 = "<td width='60' >haricinde</td>";
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
					<input onClick='tcpflags_anyclick(this);' type='checkbox' name='tcpflags_any' value='on' <?php if ($pconfig['tcpflags_any']) echo "checked"; ?>><strong>Tüm bayraklar</strong>
					<br>
					Bu kuralın eşleşmesi için 1 ya da 0 yapılacak TCP bayraklarını belirlemek için kullanabilirsiniz.
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
			<td valign="top" class="vncell">Zamanlama</td>
			<td class="vtable">
				<div id="showadvschedulebox" <?php if (!empty($pconfig['sched'])) echo "style='display:none'"; ?>>
					<input class="btn btn-mini" type="button" onClick="show_advanced_schedule()" value="Göster">
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
							echo "<option value=\"\" {$selected}>hiçbiri</option>\n";
						} else {
							echo "<option value=\"{$schedule}\" {$selected}>{$schedule}</option>\n";
						}
					}
					?>
					</select><br>
					Kuralın her zaman etkin olması için 'hiçbiri' olarak bırakın.
					</p>
				</div>
			</td>
		</tr>

		<tr>
			<td valign="top" class="vncell">Ağ Geçidi</td>
			<td class="vtable">
				<div id="showadvgatewaybox" <?php if (!empty($pconfig['gateway'])) echo "style='display:none'"; ?>>
					<input class="btn btn-mini" type="button" onClick="show_advanced_gateway()" value="Göster">
				</div>
				<div id="showgatewayadv" <?php if (empty($pconfig['gateway'])) echo "style='display:none'"; ?>>
					<p>
					<select name='gateway'>
						<option value="">varsayılan</option>
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
						Sistem yönlendirme tablolarını kullanmak için 'varsayılan'da bırakınız.
						<br>Ya da ilke tabanlı yönlendirme yapmak için bir ağ geçidi seçin.
					</p>
				</div>
			</td>
		</tr>

		<tr>
			<td valign="top" class="vncell">Hız Sınırı (Gelen/Giden)</td>
			<td class="vtable">
				<div id="showadvinoutbox" <?php if (!empty($pconfig['dnpipe'])) echo "style='display:none'"; ?>>
					<input class="btn btn-mini" type="button" onClick="show_advanced_inout()" value="Göster">
				</div>
				<div id="showinoutadv" <?php if (empty($pconfig['dnpipe'])) echo "style='display:none'"; ?>>
					<p>
					<select name="dnpipe">
						<?php
							if (!is_array($dnqlist))
								$dnqlist = array();
							echo "<option value=\"none\"";
							if (!$dnqselected) echo " SELECTED";
							echo " >hiçbiri</option>";
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
							echo " >hiçbiri</option>";
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
				<input name="Submit" type="submit" class="btn btn-inverse" value="Kaydet">
				<input type="button" class="btn btn-default" value="İptal" onclick="history.back()">
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
