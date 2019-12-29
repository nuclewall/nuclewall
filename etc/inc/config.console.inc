<?php
/****h* pfSense/config
 * NAME
 *   config.inc - Functions to manipulate config.xml
 * DESCRIPTION
 *   This include contains various config.xml specific functions.
 * HISTORY
 * $Id$
 ******

	config.console.inc
	Copyright (C) 2004-2010 Scott Ullrich
	All rights reserved.

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


	pfSense_BUILDER_BINARIES:	/sbin/mount	/sbin/sysctl	/sbin/umount	/sbin/halt	/sbin/fsck
	pfSense_MODULE:	config
*/

function set_networking_interfaces_ports() {
	global $noreboot;
	global $config;
	global $g;
	global $fp;

	$fp = fopen('php://stdin', 'r');

	$iflist = get_interface_list();

/* Function flow is based on $key and $auto_assign or the lack thereof */
	$key = null;

/* Only present auto interface option if running from LiveCD and interface mismatch*/
	if ((ereg("cdrom", $g['platform'])) && is_interface_mismatch())
		$auto_assign = false;

	echo <<<EOD
Ethernet kartlarý:
\n
EOD;

	if(!is_array($iflist)) {
		echo "Ethernet kartý bulunamadý!\n";
		$iflist = array();
	} else {
		foreach ($iflist as $iface => $ifa) {
			echo sprintf("% -6s%s %s %s\n", $iface, $ifa['mac'],
				$ifa['up'] ? "  (up)" : "(down)", $ifa['dmesg']);
		}
	}

	$key = 'n';

	if(!$auto_assign) {


		do {
			echo "\nWAN arayüzü için ethernet kartý ismini girin:\n> ";
			$wanif = chop(fgets($fp));
			if ($wanif === '') {
				return;
			}
			if (!array_key_exists($wanif, $iflist)) {
				echo "\n'{$wanif}' geçersiz bir ethernet kartý ismi\n";
				unset($wanif);
				continue;
			}
		} while (!$wanif);

		do {
			echo "\nLAN arayüzü için ethernet kartý ismini girin:\n> ";

			$lanif = chop(fgets($fp));

			if($lanif == "exit") {
				exit;
			}

			if($lanif == "") {
				if($g['minimum_nic_count'] < 2) {
					break;
				} else {
					fclose($fp);
					return;
				}
			}

			if (!array_key_exists($lanif, $iflist)) {
				echo "\n'{$lanif}' geçersiz bir ethernet kartý ismi\n";
				unset($lanif);
				continue;
			}
		} while (!$lanif);

		$i = 0;
		$optif = array();

		if($lanif <> "") {
			while (1) {
				if ($optif[$i])
					$i++;
				$i1 = $i + 1;

				if($config['interfaces']['opt' . $i1]['descr'])
					echo "\nFazladan bir að arayüzü bulundu: {$config['interfaces']['opt' . $i1]['descr']}";

				echo "\nÝsteðe baðlý bir ethernet kartý girebilirsiniz. Yoksa boþ býrakýn.\n> ";

				$optif[$i] = chop(fgets($fp));

				if ($optif[$i]) {
					if (!array_key_exists($optif[$i], $iflist)) {
						echo "\n'{$optif[$i]}' geçersiz bir ethernet kartý ismi\n";
						unset($optif[$i]);
						continue;
					}
				} else {
					unset($optif[$i]);
					break;
				}
			}
		}

		/* check for double assignments */
		$ifarr = array_merge(array($lanif, $wanif), $optif);

		for ($i = 0; $i < (count($ifarr)-1); $i++) {
			for ($j = ($i+1); $j < count($ifarr); $j++) {
				if ($ifarr[$i] == $ifarr[$j]) {
					echo <<<EOD

Hata: Ayný ethernet kartýný 2 ayrý arayüze tanýmlayamazsýnýz.

EOD;
					fclose($fp);
					return;
				}
			}
		}

		echo "\nEthernet kartý-arayüz eþleþtirmeleri aþaðýdaki gibi olacak: \n\n";

		echo "WAN  -> " . $wanif . "\n";
		if ($lanif != "")
			echo "LAN  -> " . $lanif . "\n";
		for ($i = 0; $i < count($optif); $i++) {
			echo "OPT" . ($i+1) . " -> " . $optif[$i] . "\n";
		}

		echo <<<EOD

Onaylýyor musunuz? [e|h]\n>
EOD;
			$key = chop(fgets($fp));
	}

	if (in_array($key, array('e', 'E'))) {
		if($lanif)
		{
			if (!is_array($config['interfaces']['lan']))
				$config['interfaces']['lan'] = array();
			$config['interfaces']['lan']['if'] = $lanif;
			$config['interfaces']['lan']['enable'] = true;
		}
		elseif (!$g['booting'] && !$auto_assign)
		{

echo <<<EODD
LAN arayüzünü silmeyi seçtiniz.

LAN arayüzünü silmek ve IP adresini kaldýrmak
istiyor musunuz? [e|h]\n>
EODD;

				if (strcasecmp(chop(fgets($fp)), "e") == 0) {
					if(isset($config['interfaces']['lan']) && $config['interfaces']['lan']['if'])
						mwexec("/sbin/ifconfig " . $config['interfaces']['lan']['if'] . " delete");
				}
				if(isset($config['interfaces']['lan']))
					unset($config['interfaces']['lan']);
				if(isset($config['dhcpd']['lan']))
					unset($config['dhcpd']['lan']);
				if(isset($config['interfaces']['lan']['if']))
					unset($config['interfaces']['lan']['if']);
				if(isset($config['interfaces']['wan']['blockpriv']))
					unset($config['interfaces']['wan']['blockpriv']);
				if(isset($config['shaper']))
					unset($config['shaper']);
				if(isset($config['ezshaper']))
					unset($config['ezshaper']);
				if(isset($config['nat']))
					unset($config['nat']);
		} else {
			if(isset($config['interfaces']['lan']['if']))
				mwexec("/sbin/ifconfig " . $config['interfaces']['lan']['if'] . " delete");
			if(isset($config['interfaces']['lan']))
				unset($config['interfaces']['lan']);
			if(isset($config['dhcpd']['lan']))
				unset($config['dhcpd']['lan']);
			if(isset($config['interfaces']['lan']['if']))
				unset($config['interfaces']['lan']['if']);
			if(isset($config['interfaces']['wan']['blockpriv']))
				unset($config['interfaces']['wan']['blockpriv']);
			if(isset($config['shaper']))
				unset($config['shaper']);
			if(isset($config['ezshaper']))
				unset($config['ezshaper']);
			if(isset($config['nat']))
				unset($config['nat']);
		}


		if (!is_array($config['interfaces']['wan']))
			$config['interfaces']['wan'] = array();
		$config['interfaces']['wan']['if'] = $wanif;
		$config['interfaces']['wan']['enable'] = true;


		for ($i = 0; $i < count($optif); $i++) {
			if (!is_array($config['interfaces']['opt' . ($i+1)]))
				$config['interfaces']['opt' . ($i+1)] = array();

			$config['interfaces']['opt' . ($i+1)]['if'] = $optif[$i];

			unset($config['interfaces']['opt' . ($i+1)]['enable']);
			$config['interfaces']['opt' . ($i+1)]['descr'] = "OPT" . ($i+1);
		}

		/* remove all other (old) optional interfaces */
		for (; isset($config['interfaces']['opt' . ($i+1)]); $i++)
			unset($config['interfaces']['opt' . ($i+1)]);

		echo "\nAyarlar kaydediliyor...";
		write_config();
		echo "Tamam.";

		if($g['booting'])
			return;

		echo "Ayarlar yeniden yükleniyor...";

		$g['booting'] = false;

		echo "Tamam.\n";

		touch("{$g['tmp_path']}/assign_complete");

		echo "\nÝliþkilendirmeler ayarlandý. Devam etmek için <ENTER> a basýn. ";
		fgets($fp);
		fclose($fp);
		exit;
	}
}


function interfaces_setup() {
	global $iflist, $config, $g, $fp;

	$iflist = get_interface_list();
}

?>
