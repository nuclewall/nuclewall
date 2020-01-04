<?php
/*
	signed_files.php

	Copyright (C) 2013-2020 Ogün AÇIK
	All rights reserved.
*/

require('config.inc');
require('guiconfig.inc');

$root_dir = '/var/5651/signed_files';

if(($_POST['act'] == 'list') and $_POST['date'])
{
	$d = $_POST['date'];
	$logs_dir = "$root_dir/$d";

	if(is_dir($logs_dir))
		$files = scandir($logs_dir);

	if($files)
	{
		$x = count($files);
		$r_files = array();

		for($i=2,$k=0; $i<$x; $i++)
		{
			$r_files[$k]['file'] = $files[$i];
			$r_files[$k]['sign'] = $files[$i+1];
			$r_files[$k]['log'] = $files[$i+2];
			$i=$i+2;
			$k++;
		}

		foreach($r_files as $row)
		{
			// Get timestamp from filename
			$p1  = explode('-', $row['file']);
			$p2  = explode('.', $p1[1]);
			$ts = $p2[0];

			// Convert timestamp to date
			$date = date("H:i:s d-m-Y", $ts);

			$html .= <<<EOF
				<tr>
					<td class="cell">{$row['file']}</td>
					<td class="cell">$date</td>
					<td class="cell tools">
						<a title="İndir" href="ajax_handlers/signed_files.php?act=download&f=$d-$ts"><i class="icon-download-alt"></i></a>
						<a name="$d-$ts" title="İmzayı kontrol et" href="#"><i class="icon-check"></i></a>
					</td>
				</tr>

EOF;
		}
		echo $html;
	}

	else
	{
		echo "<tr><td class=\"cell\" colspan=4>Kayıt bulunamadı.</td></tr>";
	}
}

else if(($_GET['act'] == 'download') and $_GET['f'])
{
	$path  = explode('-', $_GET['f']);
	$dir = $path[0];
	$filename = "dhcp-{$path[1]}.txt";
	$tar_file = '/tmp/' . $filename . '.tar';

	exec("cp $root_dir/$dir/$filename $root_dir/$dir/$filename.imza $root_dir/$dir/$filename.log /tmp/");
	exec("chflags 0 /tmp/$filename /tmp/$filename.imza /tmp/$filename.log");
	exec("tar -cvf $tar_file -C /tmp $filename $filename.imza $filename.log && rm -f /tmp/$filename /tmp/$filename.imza /tmp/$filename.log");

	if (file_exists($tar_file))
	{
		header('Content-Description: File Transfer');
		header('Content-Type: application/octet-stream');
		header('Content-Disposition: attachment; filename='.basename($tar_file));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($tar_file));
		ob_clean();
		flush();
		readfile($tar_file);
	}
}

else if(($_POST['act'] == 'checksign') and $_POST['f'])
{
	$path  = explode('-', $_POST['f']);
	$dir = $path[0];
	$filename = "dhcp-{$path[1]}.txt";

	$verify = exec("verify_file.sh $root_dir/$dir/$filename");

	echo $verify;
}

else
	echo "No request";

?>
