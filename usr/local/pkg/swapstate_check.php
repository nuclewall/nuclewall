#!/usr/local/bin/php -q
<?php
function is_ps_running($process)
{
	$output = '';
	exec("/bin/pgrep -anx {$process}", $output, $retval);

	return (intval($retval) == 0);
}

if (is_ps_running('squid'))
{
	require_once('config.inc');
	require_once('util.inc');

	$settings = $config['installedpackages']['squidcache']['config'][0];

	if ($settings['harddisk_cache_system'] != "null")
	{
		$cachedir =($settings['harddisk_cache_location'] ? $settings['harddisk_cache_location'] : '/var/squid/cache');
		$swapstate = $cachedir . '/swap.state';
		$disktotal = disk_total_space(dirname($cachedir));
		$diskfree = disk_free_space(dirname($cachedir));
		$diskusedpct = round((($disktotal - $diskfree) / $disktotal) * 100);
		$swapstate_size = filesize($swapstate);
		$swapstate_pct = round(($swapstate_size / $disktotal) * 100);

		if (($swapstate_pct > 75) || (($diskusedpct > 90) && ($swapstate_size > 1024*1024*1024)))
		{
			mwexec_bg("/bin/rm $swapstate; /usr/local/sbin/squid -k rotate");
			log_error("Squid önbellek dosyasý limiti aþtý. Þimdi sýfýrlanacak.");
		}
	}
}
?>