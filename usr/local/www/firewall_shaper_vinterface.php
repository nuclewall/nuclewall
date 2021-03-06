<?php
/* $Id$ */
/*
	firewall_shaper_vinterface.php
	Copyright (C) 2004, 2005 Scott Ullrich
	Copyright (C) 2008 Ermal Luçi
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
require_once('functions.inc');
require_once('filter.inc');
require_once('shaper.inc');

if($_GET['reset'] <> "") {
	mwexec("/usr/bin/killall -9 pfctl php");
	exit;
}

$pgtitle = array('GÜVENLİK DUVARI', 'HIZ SINIRLAYICILAR');

read_dummynet_config();

if ($_GET)
{
	if ($_GET['queue'])
        	$qname = htmlspecialchars(trim($_GET['queue']));
        if ($_GET['pipe'])
                $pipe = htmlspecialchars(trim($_GET['pipe']));
        if ($_GET['action'])
                $action = htmlspecialchars($_GET['action']);
}

if ($_POST)
{
	if ($_POST['name'])
        	$qname = htmlspecialchars(trim($_POST['name']));
	else if ($_POST['newname'])
        	$qname = htmlspecialchars(trim($_POST['newname']));
        if ($_POST['pipe'])
        	$pipe = htmlspecialchars(trim($_POST['pipe']));
	else
		$pipe = htmlspecialchars(trim($qname));
	if ($_POST['parentqueue'])
		$parentqueue = htmlspecialchars(trim($_POST['parentqueue']));
}

if ($pipe)
{
	$dnpipe = $dummynet_pipe_list[$pipe];
	if ($dnpipe)
	{
		$queue =& $dnpipe->find_queue($pipe, $qname);
	}
	else
		$addnewpipe = true;
}

$dontshow = false;
$newqueue = false;
$output_form = "";

if ($_GET)
{
	switch ($action)
	{
	case "delete":
		if ($queue)
		{
			if (is_array($config['filter']['rule']))
			{
				foreach ($config['filter']['rule'] as $rule)
				{
					if ($rule['dnpipe'] == $queue->GetNumber() || $rule['pdnpipe'] == $queue->GetNumber())
						$input_errors[] = "Bu sınırlayıcı bir kural tarafından kullanılıyor. Silmeden önce kuraldan kaldırın.";
				}
			}

			if (!$input_errors)
			{
				$queue->delete_queue();
				write_config("Bir hiz sinirlayici silindi");
				mark_subsystem_dirty('shaper');
				header("Location: firewall_shaper_vinterface.php");
				exit;
			}
			$output_form .= $queue->build_form();
		}
		else
		{
			$input_errors[] = "{$qname} isminde bir sınırlayıcı bulunamadı.";
			$output_form .= "<p class=\"pgtitle\">" . $dn_default_shaper_msg."</p>";
			$dontshow = true;
		}
		break;
	case "resetall":
			foreach ($dummynet_pipe_list as $dn)
				$dn->delete_queue();
			unset($dummynet_pipe_list);
			$dummynet_pipe_list = array();
			unset($config['dnshaper']['queue']);
			unset($queue);
			unset($pipe);
			$can_add = false;
			$can_enable = false;
			$dontshow = true;
			foreach ($config['filter']['rule'] as $key => $rule) {
				if (isset($rule['dnpipe']))
					unset($config['filter']['rule'][$key]['dnpipe']);
				if (isset($rule['pdnpipe']))
					unset($config['filter']['rule'][$key]['pdnpipe']);
			}
			write_config();

			$retval = 0;
                        $retval = filter_configure();
                        $savemsg = get_std_save_message($retval);

                        if (stristr($retval, "error") <> true)
	                        $savemsg = get_std_save_message($retval);
                        else
       	                	$savemsg = $retval;

			$output_form = $dn_default_shaper_message;

		break;
	case "add":
		if ($addnewpipe)
		{
			$q = new dnpipe_class();
			$q->SetQname($pipe);
		}
		else
			$input_errors[] = "Yeni sınırlayıcı yaratılamadı.";

		if ($q)
		{
			$output_form .= $q->build_form();
            unset($q);
			$newqueue = true;
		}
		break;
	case "show":
		if ($queue)
			$output_form .= $queue->build_form();
		else
			$input_errors[] = "Sınırlayıcı bulunamadı.";
		break;
	case "enable":
		if ($queue) {
				$queue->SetEnabled("on");
				$output_form .= $queue->build_form();
				write_config();
				mark_subsystem_dirty('shaper');
		} else
				$input_errors[] = "Sınırlayıcı bulunamadı.";
		break;
	case "disable":
		if ($queue) {
				$queue->SetEnabled("");
				$output_form .= $queue->build_form();
				write_config();
				mark_subsystem_dirty('shaper');
		} else
				$input_errors[] = "Sınırlayıcı bulunamadı.";
		break;
	default:
		$output_form .= "<p class=\"pgtitle\">" . $dn_default_shaper_msg."</p>";
		$dontshow = true;
		break;
	}
}
else if ($_POST)
{
	unset($input_errors);

	if ($addnewpipe)
	{
		$dnpipe =& new dnpipe_class();

		$dnpipe->ReadConfig($_POST);
		$dnpipe->validate_input($_POST, &$input_errors);
		if (!$input_errors)
		{
			unset($tmppath);
			$tmppath[] = $dnpipe->GetQname();
			$dnpipe->SetLink(&$tmppath);
			$dnpipe->wconfig();
			write_config("Bir hiz sinirlayici eklendi");
			mark_subsystem_dirty('shaper');
			$can_enable = true;
       		     	$can_add = true;
		}
		read_dummynet_config();
		$output_form .= $dnpipe->build_form();

	}
	else if ($parentqueue)
	{
		if ($dnpipe) {
			$tmppath =& $dnpipe->GetLink();
			array_push($tmppath, $qname);
			$tmp =& $dnpipe->add_queue($pipe, $_POST, $tmppath, &$input_errors);
			if (!$input_errors) {
				array_pop($tmppath);
				$tmp->wconfig();
				write_config();
				$can_enable = true;
                		$can_add = false;
				mark_subsystem_dirty('shaper');
				$can_enable = true;
			}
			read_dummynet_config();
			$output_form .= $tmp->build_form();
		} else
			$input_errors[] = "Sınırlayıcı eklenemedi.";
	}
	else if ($_POST['apply'])
	{
		write_config("Hız sinirlayici ayarlari kaydedildi");

			$retval = 0;
			$retval = filter_configure();
			$savemsg = get_std_save_message($retval);

			if (stristr($retval, "error") <> true)
					$savemsg = get_std_save_message($retval);
			else
					$savemsg = $retval;

			clear_subsystem_dirty('shaper');

			if ($queue) {
				$output_form .= $queue->build_form();
				$dontshow = false;
			}
			else {
				$output_form .= $dn_default_shaper_message;
				$dontshow = true;
			}
	}
	else if ($queue)
	{
                $queue->validate_input($_POST, &$input_errors);
                if (!$input_errors) {
                            	$queue->update_dn_data($_POST);
                            	$queue->wconfig();
				write_config("Bir hiz sinirlayici duzenlendi");
				mark_subsystem_dirty('shaper');
				$dontshow = false;
                }
		read_dummynet_config();
		$output_form .= $queue->build_form();
	}
	else
	{
		$output_form .= "<p class=\"pgtitle\">" . $dn_default_shaper_msg."</p>";
		$dontshow = true;
	}
}
else
{
	$output_form .= "<p class=\"pgtitle\">" . $dn_default_shaper_msg."</p>";
	$dontshow = true;
}

if ($queue)
{
	if ($queue->GetEnabled())
		$can_enable = true;
	else
		$can_enable = false;
	if ($queue->CanHaveChildren())
	{
		$can_add = true;
	}
	else
		$can_add = false;
}

$tree = "<ul style=\"padding-left:0px;\">";
if (is_array($dummynet_pipe_list))
{
	foreach ($dummynet_pipe_list as $tmpdn)
	{
		$tree .= $tmpdn->build_tree();
	}
}

$tree .= <<<EOD
<li><a title="Ekle" href="firewall_shaper_vinterface.php?pipe=yeni&action=add">
<i class="icon-plus"></i>
</a></li>
EOD;
$tree .= "</ul>";

if (!$dontshow || $newqueue)
{
	$output_form .= "<tr><td class=\"vncell\">";
	$output_form .= "</td><td class=\"vtable\">";
	$output_form .= "<input type=\"submit\" name=\"Submit\" value=\"Kaydet\" class=\"btn btn-inverse\"> ";
	$output_form .= "<a class=\"btn btn-default\" href=\"firewall_shaper_vinterface.php?pipe=";
	$output_form .= $pipe . "&queue=";

	if ($queue)
	{
		$output_form .= "&queue=" . $queue->GetQname();
	}

	$output_form .= "&action=delete\">";
	$output_form .= "Sil</a>";
	$output_form .= "</td></tr>";
}


$output .= $output_form;
?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<?php if ($input_errors) print_input_errors($input_errors); ?>

<form action="firewall_shaper_vinterface.php" method="post" id="iform" name="iform">

<?php if ($savemsg) print_info_box($savemsg); ?>
<?php if (is_subsystem_dirty('shaper')): ?><p>
<?php print_info_box_np("Hız sınırlayıcı ayarları değiştirildi.<br>Değişikliklerin etkili olması için uygulamalısınız.", true);?>
<?php endif; ?>
<table cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td class="vncell">Sınırlayıcılar</td>
					<td class="vtable">
						<div style="margin:0;"  class="pagination">
						<?php echo $tree; ?>
						</div>
					</td>
				</tr>
				<?php echo $output; ?>
				<tr>
					<td colspan="2">

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
