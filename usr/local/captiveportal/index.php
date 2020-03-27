<?php
require_once('auth.inc');
require_once('functions.inc');
require_once('captiveportal.inc');
require_once('connections.inc');
require_once('hotspot_errors.inc');
require_once('local_connection.inc');

$external = $config['datasources']['external'];
$lang = $_COOKIE['user_lang'];

if($external != 'none')
	include('external.inc');

header("Expires: 0");
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Connection: close");

$external = false;
$orig_host = $_ENV['HTTP_HOST'];
/* NOTE: IE 8/9 is buggy and that is why this is needed */
$orig_request = trim($_REQUEST['redirurl'], " /");
$clientip = $_SERVER['REMOTE_ADDR'];

if (!$clientip)
{
	log_error("Unable to get client's IP address.");
	exit;
}

$ifip = portal_ip_from_client_ip($clientip);
if (!$ifip)
	$ourhostname = $config['system']['hostname'] . ':8000';
else
	$ourhostname = "{$ifip}:8000";


if ($orig_host != $ourhostname)
{
    header("Location: http://{$ourhostname}/index.php?redirurl=" . urlencode("http://{$orig_host}/{$orig_request}"));
    exit;
}


if (preg_match("/redirurl=(.*)/", $orig_request, $matches))
	$redirurl = urldecode($matches[1]);
else if ($_REQUEST['redirurl'])
	$redirurl = $_REQUEST['redirurl'];

$clientmac = arp_get_mac_by_ip($clientip);
$mac_addr = str_replace(':', '-', $clientmac);

if (!$clientmac)
{
    captiveportal_logportalauth('unauthenticated', 'noclientmac', $clientip, 'ERROR');
    log_error("HOTSPOT could not determine client's IP address.");
    exit;
}

if($connection)
{
	$checkBlocked = $pdo->prepare("
		SELECT mac_addr FROM blocklist
		WHERE mac_addr = :mac
	");

	$checkBlocked->bindParam(':mac', $mac_addr);
	$checkBlocked->execute();
	$blocked = $checkBlocked->fetch(PDO::FETCH_ASSOC);

	if ($blocked)
	{
		portal_reply_page($redirurl, 'error', $hotspot_errors['block'][$lang]);
		captiveportal_logportalauth('', $clientmac, $clientip, 'BLOCKED MAC ADDRESS');
		exit;
	}
}

else
{
	log_error("HOTSPOT doesn't work correctly. Check external data sources.");
	exit;
}

if ($clientmac && portal_mac_radius($clientmac, $clientip))
{
    exit;
}

else if ($_POST['accept'])
{
    if ($_POST['auth_user'] && $_POST['auth_pass'])
	{
		if($external != 'none' && $external_connection)
		{
			$found = checkUser($external_connection, $settings['table_name'], $settings['username_field'], $settings['password_field'], $_POST['auth_user'], $_POST['auth_pass']);

			if($found)
			{
				captiveportal_logportalauth($_POST['auth_user'], $clientmac, $clientip, 'NEW SESSION');
				portal_allow($clientip, $clientmac, $_POST['auth_user'], $_POST['auth_pass'], array('url_redirection' => $redirurl), null, 'external');
				$external = true;
			}
		}

		if(!$external)
		{
			$auth_list = radius($_POST['auth_user'], $_POST['auth_pass'], $clientip, $clientmac, 'NEW SESSION');
			$type = 'error';

			if (!empty($auth_list['url_redirection']))
			{
				$redirurl = $auth_list['url_redirection'];
				$type = 'redir';
			}

			if ($auth_list['auth_val'] == 1)
			{
				captiveportal_logportalauth($_POST['auth_user'], $clientmac, $clientip, 'ERROR', $auth_list['error']);
				log_error("HOTSPOT doesn't work correctly. Check FreeRADIUS server.");
				exit;
			}

			else if($auth_list['auth_val'] == 3)
			{
				if($auth_list['reply_message'])
				{
					$error_message = $hotspot_errors['session'][$lang];
				}
				else
				{
					$error_message = $hotspot_errors['login'][$lang];
				}

				captiveportal_logportalauth($_POST['auth_user'], $clientmac, $clientip, 'FAILED', $auth_list['reply_message']);
				portal_reply_page($redirurl, 'error', $error_message);
			}
		}
    }
}

else
{
	portal_reply_page($redirurl, 'login', null, $clientmac, $clientip);
}

exit;
?>
