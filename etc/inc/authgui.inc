<?php
include_once('auth.inc');
include_once('priv.inc');

if (!session_auth())
{
	display_login_form();
	exit;
}

$allowedpages = getAllowedPages($HTTP_SERVER_VARS['AUTH_USER']);

if (!isAllowedPage($_SERVER['SCRIPT_NAME']))
{
	if (count($allowedpages) > 0)
	{
		$page = str_replace('*', '', $allowedpages[0]);
		$_SESSION['Post_Login'] = true;
		require_once("functions.inc");
		pfSenseHeader("/{$page}");

		$username = empty($_SESSION["Username"]) ? "(system)" : $_SESSION['Username'];
		if (!empty($_SERVER['REMOTE_ADDR']))
			$username .= '@' . $_SERVER['REMOTE_ADDR'];
		log_error("{$username} kullanicisi {$_SERVER['SCRIPT_NAME']} sayfasina erismek istedi fakat erisim kisitli. {$page} sayfasina yonlendiriliyor.");

		exit;
	}
	else
	{
		echo("<p>Bu sayfaya erisim izniniz yok</p>");
		exit;
	}
}
else 
	$_SESSION['Post_Login'] = true;

if (!$_SESSION['Post_Login'])
{
	$_SESSION['Post_Login'] = true;
	require_once("functions.inc");
	pfSenseHeader($_SERVER['REQUEST_URI']);
	exit;
}

session_commit();

function display_login_form()
{
	require_once("globals.inc");
	global $config, $g;

	unset($input_errors);

	global $FilterIflist;
	$local_ip = false;
	
	if(strstr($_SERVER['HTTP_HOST'], ":"))
	{
		$http_host_port = explode(":", $_SERVER['HTTP_HOST']);
		$http_host = $http_host_port[0];
	}
	else
	{
		$http_host = $_SERVER['HTTP_HOST'];
	}
	
	if (empty($FilterIflist))
	{
		require_once('filter.inc');
		require_once('shaper.inc');
		filter_generate_optcfg_array();
	}

?>

<!DOCTYPE html>
<html>
<head>
<title>Nuclewall - Yönetici Giriþi</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-9"/>
<link rel="stylesheet" href="themes/nuclewall/bootstrap/css/bootstrap-base.css" media="all"/>
</head>
<body id="login_body">
<div id="cookiealert" class="alert" style="display:none">
	<center>
	<strong>Uyarý! </strong>Tarayýcýnýzda çerezler (cookies) aktifleþtirilmemiþ. Oturum açabilmek için çerezleri aktifleþtirin.
	Bkz: <a target="_blank" href="https://support.mozilla.org/tr/kb/%C3%87erezleri%20a%C3%A7%C4%B1p%20kapatma">Firefox</a>
	<a target="_blank" href="https://support.mozilla.org/tr/kb/%C3%87erezleri%20a%C3%A7%C4%B1p%20kapatma">Chrome</a>
	</center>
</div>
<div id="main" class="img-rounded">    
	<form id="iform" class="form-horizontal" name="login_iform" method="post" action="<?=$_SERVER['SCRIPT_NAME'];?>">    
		<div class="control-group">
			<label class="control-label" for="usernamefld">Kullanýcý adý</label>
			<div class="controls">
				<input id="usernamefld" type="text" name="usernamefld" tabindex="1"/>
			</div>
		</div>
		<div class="control-group">
			<label class="control-label" for="passwordfld">Parola</label>
			<div class="controls">
				<input id="passwordfld" type="password" name="passwordfld" tabindex="2"/>
			</div>
		</div>
		<div class="control-group">
			<div class="controls">
				<input type="submit" name="login" class="btn btn-primary" value="Giriþ" tabindex="3"/>
			</div>
		</div>
		<?php if (isset($_SESSION['Login_Error'])) { ?>
		<div class="alert alert-danger" id="inputerrors">
			<?=$_SESSION['Login_Error'];?>
		</div><?php unset($_SESSION['Login_Error']); } ?>
	</form>
</div>
<script type="text/javascript">
if(!navigator.cookieEnabled)
{
	document.getElementById("cookiealert").style.display = 'block';
}
document.getElementById('usernamefld').focus();
</script>
</body>
</html>
<?php
}

?>
