<?php
/* $Id$ */
/*
	status_gateway_groups.php

	Copyright (C) 2013-2020 Ogun Acik
	All rights reserved.

	Copyright (C) 2010 Seth Mos <seth.mos@dds.nl>.
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

if (!is_array($config['gateways']['gateway_group']))
	$config['gateways']['gateway_group'] = array();

$a_gateway_groups = &$config['gateways']['gateway_group'];

$gateways_status = return_gateways_status();

$pgtitle = array('STATUS ', 'GATEWAY GROUPS');

?>

<?php include('head.inc'); ?>
</head>
<body>
<?php include('fbegin.inc'); ?>

<table cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<?php
				$tab_array = array();
				$tab_array[] = array('Gateways', false, 'status_gateways.php');
				$tab_array[] = array('Gateways Groups', true, 'status_gateway_groups.php');
				display_top_tabs($tab_array);
			?>
		</td>
	</tr>
	<tr>
		<td>
             <table class="tabcont" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<table class="grids">
							<tr>
								<td class="head">Group Name</td>
								<td class="head">Gateways</td>
								<td class="head">Description</td>
							</tr>
							<?php $i = 0; foreach ($a_gateway_groups as $gateway_group): ?>
							<tr>
								<td class="cell"><?php echo $gateway_group['name']; ?></td>
								<td class="cell">
									<?php
									$priorities = array();
									foreach($gateway_group['item'] as $item) {
										$itemsplit = explode("|", $item);
										$priorities[$itemsplit[1]] = true;
									}
									$priority_count = count($priorities);
									ksort($priorities);

									foreach($priorities as $number => $tier) {
										echo  sprintf("Tier %s, ", $number);
									}
									echo "<br>";
									$priority_arr = array();
									foreach($gateway_group['item'] as $item) {
										$itemsplit = explode("|", $item);
										$priority_arr[$itemsplit[1]][] = $itemsplit[0];
									}
									ksort($priority_arr);

									foreach($priority_arr as $number => $tier)
									{
										foreach($tier as $member)
										{
											echo	htmlspecialchars($member) . ", ";
										}

									}
									?>
								</td>
								<td class="cell description">
									<?=htmlspecialchars(base64_decode($gateway_group['descr']));?>
								</td>
							</tr>
							<?php $i++; endforeach; ?>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
</div>
</body>
</html>
