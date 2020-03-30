<?php
/*
	hotspot_status.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.

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
require('functions.inc');
require('filter.inc');
require('shaper.inc');
require('captiveportal.inc');

if ($_GET['act'] == 'del')
{
	captiveportal_disconnect_client($_GET['id']);
	header("Location: hotspot_status.php");
	exit;
}

$cpdb = array();

if (file_exists("{$g['vardb_path']}/captiveportal.db"))
{
	$captiveportallck = lock('captiveportaldb');
	$cpcontents = file("{$g['vardb_path']}/captiveportal.db", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	unlock($captiveportallck);
}
else
{
	$cpcontents = array();
}

$concurrent = count($cpcontents);

$pgtitle = array("HOTSPOT : AKTİF OTURUMLAR ({$concurrent})");

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php if ($input_errors) print_input_errors($input_errors); ?>

<?php

flush();

foreach ($cpcontents as $cpcontent)
{
	$cpent = explode(',', $cpcontent);
	$sessionid = $cpent[5];
	$cpent[3] = str_replace(':', '-', $cpent[3]);
	$cpent[5] = captiveportal_get_last_activity($cpent[2]);

	if(is_mac($cpent[4]))
	{
		$cpent[10] = 'label-success';
	}

	$cpdb[$sessionid] = $cpent;
}


?>
<table cellpadding="0" cellspacing="0">
	<tr>
		<td class='tabnavtbl'>
			<?php
				$tab_array = array();
				$tab_array[] = array('Aktif Oturumlar', true, 'hotspot_status.php');
				$tab_array[] = array('Yerel Kullanıcılar', false, 'hotspot_users.php');
				$tab_array[] = array('Özel İzinli MAC Adresleri', false, 'hotspot_macs.php');
				$tab_array[] = array('Engellenmiş MAC Adresleri', false, 'hotspot_blocklist.php');
				$tab_array[] = array('Oturum Hareketleri', false, 'hotspot_logs.php');
				display_top_tabs($tab_array, true);
			?>
		</td>
	</tr>
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<div class="controls">
							<div class="input-prepend">
								<span class="add-on"><i class="icon-search"></i></span>
								<input id="search" placeholder="IP, MAC veya kullanıcı adı ara" class="input-medium" style="height:20px; width: 210px; margin-right: 20px;" type="text">
							</div>
							<span>Veri kaynağına göre göster:</span>
							<button id="btn-all" class="btn btn-danger btn-mini">Hepsi</button>
							<button id="btn-local" class="btn btn-info btn-mini">Yerel</button>
							<button id="btn-external" class="btn btn-mini">Harici</button>
							<button id="btn-mac" class="btn btn-success btn-mini">Özel İzinli MAC Ad.</button>
						</div>
						<table class="grids sortable">
							<tr>
								<td class="head">IP Adresi</td>
								<td class="head">MAC Adresi</td>
								<td class="head">Kullanıcı Adı</td>
								<td class="head">Oturum Açılışı</td>
								<td class="head">Son Etkinlik</td>
								<td class="head"></td>
							</tr>
							<?php foreach ($cpdb as $sid => $cpent): ?>
							<tr>
								<td id="ip_addr" class="cell dhcpip"><?=$cpent[2];?></td>
								<td id="mac_addr" class="cell hotspotmac"><?=$cpent[3];?></td>
								<td id="username" class="cell hotspotmac left"><span class="label <?=$cpent[10];?>"><?=$cpent[4];?></span></td>
								<td class="cell dhcpdate"><?php echo strftime("%d-%m-%Y %H:%M", $cpent[0]);?></td>
								<td class="cell dhcpdate"><?php if ($cpent[5]) echo strftime("%d-%m-%Y %H:%M", $cpent[5]);?></td>
								<td class="cell tools hotspot">
									<a title="Oturumu kapat" href="hotspot_status.php?act=del&id=<?=$sid;?>" onclick="return confirm('Bu kullanıcının oturumu kapatılsın mı?')">
										<i class="icon-user"></i>
									</a>
									<?php if($cpent[10] == 'label-success'): ?>
									<a title="Özel izinli MAC adreslerinden kaldır" href="hotspot_macs.php?act=del&mac=<?=$cpent[3];?>" onclick="return confirm('Özel izinli MAC adresleri listesinden kaldır?')">
										<i class="icon-trash"></i>
									</a>
									<?php else: ?>
									<a title="MAC adresini engelle" href="hotspot_blocklist_edit.php?act=new&mac=<?=$cpent[3];?>">
										<i class="icon-ban-circle"></i>
									</a>
									<a title="Özel izinli MAC adresleri listesine ekle" href="hotspot_mac_edit.php?act=new&mac=<?=$cpent[3];?>">
										<i class="icon-check"></i>
									</a>
									<?php endif; ?>
								</td>
							</tr>
							<?php endforeach; ?>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</div>
<script type="text/javascript">
jQuery("#search").on("keyup", function()
{
    var value = jQuery(this).val();

    jQuery("table.grids tr").each(function(index)
	{
		if (index !== 0)
		{
            $row = jQuery(this);
            var ip_addr = $row.find("td#ip_addr:first").text();
			var mac_addr = $row.find("td#mac_addr:first").text();
			var username = $row.find("td#username:first").text();

			 if ((mac_addr.indexOf(value) !== 0) && (username.indexOf(value) !== 0) && (ip_addr.indexOf(value) !== 0))
			{
                $row.hide();
            }
            else
			{
                $row.show();
            }
        }
    });
});

jQuery(document).keyup(function(e)
{
	if (e.keyCode == 27)
	{
		jQuery("table.grids tr").show();
		jQuery("#search").val("");
	}
});

jQuery("#btn-mac").click(function()
{
	jQuery("table.grids tr").show();

    jQuery("table.grids tr").each(function(index)
	{
		if (index !== 0)
		{
            $row = jQuery(this);
            var p_class = $row.find("td#username span:first").attr("class");

			 if ((p_class.indexOf("label-success") == -1))
			{
                $row.hide();
            }
            else
			{
                $row.show();
            }

        }
    });
});

jQuery("#btn-local").click(function()
{
	jQuery("table.grids tr").show();

    jQuery("table.grids tr").each(function(index)
	{
		if (index !== 0)
		{
            $row = jQuery(this);
            var p_class = $row.find("td#username span:first").attr("class");

			 if ((p_class.indexOf("label-info") == -1))
			{
                $row.hide();
            }
            else
			{
                $row.show();
            }

        }
    });
});

jQuery("#btn-external").click(function()
{
	jQuery("table.grids tr").show();

    jQuery("table.grids tr").each(function(index)
	{
		if (index !== 0)
		{
            $row = jQuery(this);
            var p_class = $row.find("td#username span:first").attr("class");

			 if ((p_class.indexOf("label external") == -1))
			{
                $row.hide();
            }
            else
			{
                $row.show();
            }

        }
    });
});

jQuery("#btn-all").click(function()
{
	jQuery("table.grids tr").show();
});
</script>
</body>
</html>
