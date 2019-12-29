<?php
  $pagetitle = gentitle( $pgtitle );
?>

<!DOCTYPE html>
<html>
<head>
	<title><?php echo($config['system']['hostname'] . "." . $config['system']['domain'] . " - " . $pagetitle); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" href="themes/nuclewall/all.css" media="all"/>
	<link rel="stylesheet" href="themes/nuclewall/grids.css" media="all"/>
	<link rel="stylesheet" href="themes/nuclewall/new_tab_menu.css" media="all"/>
	<link rel="stylesheet" href="themes/nuclewall/bootstrap/css/bootstrap.min.css" media="all"/>

	<script type="text/javascript" src="javascript/scriptaculous/prototype.js"></script>
	<script type="text/javascript" src="javascript/jquery-2.1.3.min.js"></script>
	<script type="text/javascript" src="themes/nuclewall/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript">
        jQuery.noConflict();
		(function( $ ) {

		})( jQuery );
    </script>
	<script type="text/javascript" src="javascript/scriptaculous/scriptaculous.js"></script>
	<script type="text/javascript" src="javascript/scriptaculous/effects.js"></script>
	<script type="text/javascript" src="javascript/scriptaculous/dragdrop.js"></script>
	<script type="text/javascript" src="javascript/global.js"></script>

<?php
	$dir  = trim(basename($_SERVER["SCRIPT_FILENAME"], '.php'));
	$path = "{$g['www_path']}/javascript/" . $dir . "/";
	if (is_dir($path)) {
		if ($dh = opendir($path)) {
			while (($file = readdir($dh)) !== false) {
		   		if (is_dir($file))
					continue;
				echo "\t<script type=\"text/javascript\" src=\"/javascript/{$dir}/{$file}\"></script>\n";
			}
			closedir($dh);
		}
	}
?>
