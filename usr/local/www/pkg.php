<?php
/* $Id$ */
/*
    pkg.php
	
	Copyright (C) 2013-2015 Ogün AÇIK
	All rights reserved.
	
    Copyright (C) 2004-2012 Scott Ullrich <sullrich@gmail.com>
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


require_once("guiconfig.inc");

$xml = $_REQUEST['xml'];

if($xml == "") {
	print_info_box_np("HATA: Böyle bir sayfa yok.");
	exit;
} else {
	if(file_exists("/usr/local/pkg/" . $xml))
		$pkg = parse_xml_config_pkg("/usr/local/pkg/" . $xml, "packagegui");
	else {
		echo "Dosya bulunamadı " . htmlspecialchars($xml);
		exit;
	}
}

if($pkg['donotsave'] <> "") {
	Header("Location: pkg_edit.php?xml=" . $xml);
	exit;
}

if ($pkg['include_file'] != "") {
	require_once($pkg['include_file']);
}

$package_name = $pkg['menu'][0]['name'];
$section      = $pkg['menu'][0]['section'];
$config_path  = $pkg['configpath'];
$title	      = $pkg['title'];

if($_REQUEST['startdisplayingat']) 
	$startdisplayingat = $_REQUEST['startdisplayingat'];

if($_REQUEST['display_maximum_rows']) 
	if($_REQUEST['display_maximum_rows'])
		$display_maximum_rows = $_REQUEST['display_maximum_rows'];

$evaledvar = $config['installedpackages'][xml_safe_fieldname($pkg['name'])]['config'];

if ($_GET['act'] == "update") {
		if(is_array($config['installedpackages'][$pkg['name']]) && $pkg['name'] != ""){
			#get current values
			$current_values=$config['installedpackages'][$pkg['name']]['config'];
			#get updated ids
			$update_list=explode(",",$_GET['ids']);
			#sort ids to know what to change
			$sort_list=$update_list;
			sort($sort_list);
			#apply updates
			foreach(explode(",",$_GET['ids']) as $key=> $value){
				$config['installedpackages'][$pkg['name']]['config'][$sort_list[$key]]=$current_values[$update_list[$key]];
				}
			#save current config
			write_config();
			#sync package
			eval ("{$pkg['custom_php_resync_config_command']}");
			}
}

if ($_GET['act'] == "del") {
		foreach ($evaledvar as $ip) {
			if($pkg['adddeleteeditpagefields']['columnitem'])
			  foreach ($pkg['adddeleteeditpagefields']['columnitem'] as $column) {
				  ${xml_safe_fieldname($column['fielddescr'])} = $ip[xml_safe_fieldname($column['fieldname'])];
			  }
		}

		$a_pkg = &$config['installedpackages'][xml_safe_fieldname($pkg['name'])]['config'];

		if ($a_pkg[$_GET['id']]) {
			unset($a_pkg[$_GET['id']]);
			write_config();
			if($pkg['custom_delete_php_command'] <> "") {
			    if($pkg['custom_php_command_before_form'] <> "")
					eval($pkg['custom_php_command_before_form']);
		    		eval($pkg['custom_delete_php_command']);
			}
			header("Location:  pkg.php?xml=" . $xml);
			exit;
	    }
}

ob_start();

$iflist = get_configured_interface_with_descr(false, true);
$evaledvar = $config['installedpackages'][xml_safe_fieldname($pkg['name'])]['config'];

if($pkg['custom_php_global_functions'] <> "")
	eval($pkg['custom_php_global_functions']);

if($pkg['custom_php_command_before_form'] <> "")
	eval($pkg['custom_php_command_before_form']);

$pgtitle = array($title);
include("head.inc");

?>

<body>
<?php
include("fbegin.inc");
?>
<form action="pkg.php" name="pkgform" method="get">
<input type='hidden' name='xml' value='<?=$_REQUEST['xml']?>'>
<? if($_GET['savemsg'] <> "") $savemsg = htmlspecialchars($_GET['savemsg']); ?>
<?php if ($savemsg) print_info_box($savemsg); ?>
<table width="100%" border="0" cellpadding="0" cellspacing="0">
<?php
if ($pkg['tabs'] <> "") {
    $tab_array = array();
    foreach($pkg['tabs']['tab'] as $tab) {
        if($tab['tab_level'])
            $tab_level = $tab['tab_level'];
        else
            $tab_level = 1;
        if(isset($tab['active'])) {
            $active = true;
        } else {
            $active = false;
        }
		if(isset($tab['no_drop_down']))
			$no_drop_down = true;
        $urltmp = "";
        if($tab['url'] <> "") $urltmp = $tab['url'];
        if($tab['xml'] <> "") $urltmp = "pkg_edit.php?xml=" . $tab['xml'];

        $addresswithport = getenv("HTTP_HOST");
        $colonpos = strpos($addresswithport, ":");
        if ($colonpos !== False) {
            //my url is actually just the IP address of the pfsense box
            $myurl = substr($addresswithport, 0, $colonpos);
        } else {
            $myurl = $addresswithport;
        }
        // eval url so that above $myurl item can be processed if need be.
        $url = str_replace('$myurl', $myurl, $urltmp);

        $tab_array[$tab_level][] = array(
                        $tab['text'],
                        $active,
                        $url
                    );
    }

    ksort($tab_array);
    foreach($tab_array as $tab) {
		echo '<tr><td>';
		display_top_tabs($tab, $no_drop_down);
        echo '</td></tr>';
    }
}
?>
<script>
	function setFilter(filtertext) {
		$('pkg_filter').value = filtertext;
		document.pkgform.submit();
	}
</script>

<?
if($pkg['adddeleteeditpagefields']['movable']){
?>
        <style type="text/css">
            td {
                border:0px solid black;
            }
            
            td.up {
                cursor:pointer;
            }

            td.down {
                cursor:pointer;
            }
            
        </style>


 <script type="text/javascript">
                Event.observe(window, "load", function() {
                    $$(".up").each(function(element) {
                        element.observe("click", up);
                    });

                    $$(".down").each(function(element) {
                        element.observe("click", down);
                    });
                });

                
                function up(event) {
                    var element = event.element().ancestors()[1];
                    var previous = element.previous('.movable');
                    if (previous) {
                        previous.remove();
                        element.insert({after:previous});
                    }
                }

                function down(event) {
                    var element = event.element().ancestors()[1];
                    var next = element.next('.movable');
                    if (next) {
                        next.remove();
                        element.insert({before:next});
                    }
                }

                function alertItems() {
                         ids = $$('tr[class="movable"]').collect(function(el) { return el.id; });
                         return(ids.join(','));
                }

                function save_changes_to_xml(xml) {
                	if(confirm("<?=gettext("Do you really want to save changes?");?>")){
                		var axel = Math.random() + "";
    					var num = axel * 1000000000000000000;
                		location.href="/pkg.php?xml=" + xml + "&act=update&ids=" + alertItems() + "&nocache=" + num;
                		}
                    
                }
                
        </script>

<?php }
echo "\t<script type=\"text/javascript\" src=\"javascript/domTT/domLib.js\"></script>\n";
echo "\t<script type=\"text/javascript\" src=\"javascript/domTT/domTT.js\"></script>\n";
echo "\t<script type=\"text/javascript\" src=\"javascript/domTT/behaviour.js\"></script>\n";
echo "\t<script type=\"text/javascript\" src=\"javascript/domTT/fadomatic.js\"></script>\n";
?>
<tr><td>
<table class="tabcont" cellpadding="0" cellspacing="0">
	<tr>
		<td>
			<table width="100%" class="grids">
			<tbody>
			<?php				
				$include_filtering_inputbox = false;
				$colspan = 0;
				if($pkg['adddeleteeditpagefields']['columnitem'] <> "") 
					foreach ($pkg['adddeleteeditpagefields']['columnitem'] as $column)
						$colspan++;
				if($pkg['fields']['field']) {
					// First find the sorting type field if it exists
					foreach($pkg['fields']['field'] as $field) {
						if($field['type'] == "sorting") {
							$sortable="on";
							if(isset($field['include_filtering_inputbox'])) 
								$include_filtering_inputbox = true;
							if($display_maximum_rows < 1) 
								if($field['display_maximum_rows']) 
									$display_maximum_rows = $field['display_maximum_rows'];
							echo "<tr><td class='head' colspan='$colspan'><center>";
							echo "<tr><td class='head' colspan='$colspan'><center>";
							echo "Filter by: ";
							$isfirst = true;
							for($char = 65; $char < 91; $char++) {
								if(!$isfirst) 
									echo " | ";
								echo "<a href=\"#\" onClick=\"setFilter('" . chr($char) . "');\">" . chr($char) . "</a>";
								$isfirst = false;
							}
							echo "</td></tr>";
							echo "<tr><td colspan='$colspan'><center>";
							if($field['sortablefields']) {
								echo "Filter field: <select name='pkg_filter_type'>";
								foreach($field['sortablefields']['item'] as $si) {
									if($si['name'] == $_REQUEST['pkg_filter_type']) 
										$SELECTED = "SELECTED";
									else 
										$SELECTED = "";
									echo "<option value='{$si['name']}' {$SELECTED}>{$si['name']}</option>";
								}
								echo "</select>";
							}
							if($include_filtering_inputbox) 
								echo "&nbsp;&nbsp;Filter text: <input id='pkg_filter' name='pkg_filter' value='" . $_REQUEST['pkg_filter'] . "'> <input type='submit' value='Filtrele'>";
							echo "</td></tr><tr><td></td></tr>";
						}
					}
				}
				?>
				<tr>
				<?php
				if($display_maximum_rows) {
					$totalpages = ceil(round((count($evaledvar) / $display_maximum_rows),9));
					$page = 1;
					$tmpcount = 0;
					$tmppp = 0;
					if(is_array($evaledvar)) {
						foreach ($evaledvar as $ipa) {
							if($tmpcount == $display_maximum_rows) {
								$page++;
								$tmpcount = 0;
							}
							if($tmppp == $startdisplayingat)
						 		break;
							$tmpcount++;
							$tmppp++;
						}
					}
					echo "<tr><td colspan='" . count($pkg['adddeleteeditpagefields']['columnitem']) . "'>";
					echo "<table width='100%'>";
					echo "<tr>";
					echo "<td align='left'>Displaying page $page of $totalpages</b></td>";
					echo "<td align='right'>Rows per page: <select onChange='document.pkgform.submit();' name='display_maximum_rows'>";
					for($x=5; $x<250; $x++) {
						if($x == $display_maximum_rows)
							$SELECTED = "SELECTED";
						else 
							$SELECTED = "";
						echo "<option value='$x' $SELECTED>$x</option>\n";
						$x=$x+4;
					}
					echo "</select></td></tr>";
					echo "</table>";
					echo "</td></tr>";
				}
				$cols = 0;
				if($pkg['adddeleteeditpagefields']['columnitem'] <> "") {
				    foreach ($pkg['adddeleteeditpagefields']['columnitem'] as $column) {
						echo "<td class=\"head\">" . $column['fielddescr'] . "</td>";
						$cols++;
				    }
				}
				echo "<td class='head'> </td>";
				echo "</tr>";
				$i=0;
				$pagination_startingrow=0;
				$pagination_counter=0;
			    if($evaledvar)
			    foreach ($evaledvar as $ip) {
				if($startdisplayingat) {
					if($i < $startdisplayingat) {
						$i++;
						continue;
					}
				}
				if($_REQUEST['pkg_filter']) {
					// Handle filterered items
					if($pkg['fields']['field'] && !$filter_regex) {
						// First find the sorting type field if it exists
						foreach($pkg['fields']['field'] as $field) {
							if($field['type'] == "sorting") {
								if($field['sortablefields']['item']) {
									foreach($field['sortablefields']['item'] as $sf) {
										if($sf['name'] == $_REQUEST['pkg_filter_type']) {
											$filter_fieldname = $sf['fieldname'];
											if($sf['regex'])
												$filter_regex = str_replace("%FILTERTEXT%", $_REQUEST['pkg_filter'], trim($sf['regex']));
											else
												$filter_regex = "/{$_REQUEST['pkg_filter']}/i";
										}
									}
								}
							}
						}
					}
					// Do we have something to filter on?
					unset($filter_matches);
					if($pkg['adddeleteeditpagefields']['columnitem'] <> "") {
						foreach ($pkg['adddeleteeditpagefields']['columnitem'] as $column) {
							$fieldname = $ip[xml_safe_fieldname($column['fieldname'])];
							if($column['fieldname'] == $filter_fieldname) {
								if($filter_regex) {
									//echo "$filter_regex - $fieldname<p/>";
									preg_match($filter_regex, $fieldname, $filter_matches);
									break;
								}
							}
						}
					}
					if(!$filter_matches) {
						$i++;
						continue;
					}
				}
				if($pkg['adddeleteeditpagefields']['movable'])
					echo "<tr valign=\"top\" class=\"movable\" id=\"$i\">\n";
				else
					echo "<tr  valign=\"top\" >\n";
				if($pkg['adddeleteeditpagefields']['columnitem'] <> '')
					foreach ($pkg['adddeleteeditpagefields']['columnitem'] as $column) {
						if ($column['fieldname'] == 'description')
							$class = 'cell description';
						else if ($column['fieldname'] == 'disabled')
							$class = 'cell disabled';
						else
							$class = 'cell';
				?>
						<td class="<?=$class;?>" ondblclick="document.location='pkg_edit.php?xml=<?=$xml?>&act=edit&id=<?=$i;?>';">
							<?php
								$fieldname = $ip[xml_safe_fieldname($column['fieldname'])];
								if ($column['type'] == 'interface') {
									echo  $column['prefix'] . $iflist[$fieldname] . $column['suffix'];
							    } 
								else {
							    	if ($column['encoding'] == 'base64')
										echo  $column['prefix'] . base64_decode($fieldname) . $column['suffix'];
									else if($column['listmodeon'] && $fieldname == 'on')
										echo $column['prefix'] . $column['listmodeon'] . $column['suffix'];
								   	else if($column['listmodeoff'] && $fieldname == '')
										echo $column['prefix'] . $column['listmodeoff']. $column['suffix'];
									else {
										if ($column['fieldname'] == 'disabled') {
											if ($fieldname == 'on')
												echo "<span class=\"label\">Pasif</span>";
											else 
												echo "<span class=\"label label-success\">Aktif</span>";
										}
										else
											echo $column['prefix'] . $fieldname ." ". $column['suffix'];
									}
							    }
							?>
						</td>

				<?php } ?>

				<td class="cell tools" >
					<a href="pkg_edit.php?xml=<?=$xml?>&act=edit&id=<?=$i;?>" title="Düzenle">
					<i class="icon-edit"></i>
					</a>
					<a href="pkg.php?xml=<?=$xml?>&act=del&id=<?=$i;?>" onclick="return confirm('<?=gettext("Silmek istediğinize emin misiniz?");?>')" title="Sil">
					<i class="icon-trash"></i>
					</a>
				</td>
</tr>
<?php
	
	if($display_maximum_rows) {
		if($pagination_counter == ($display_maximum_rows-1) or 
		$i ==  (count($evaledvar)-1)) {
			$colcount = count($pkg['adddeleteeditpagefields']['columnitem']);
			$final_footer = "";
			$final_footer .= "<tr><td colspan='$colcount'>";
			$final_footer .=  "<table width='100%'><tr>";
			$final_footer .=  "<td align='left'>";
			$startingat = $startdisplayingat - $display_maximum_rows;
			if($startingat > -1) {
				$final_footer .=  "<a href='pkg.php?xml=" . $_REQUEST['xml'] . "&startdisplayingat={$startingat}&display_maximum_rows={$display_maximum_rows}'>";
			} else {
				if($startingnat > 1) 
					$final_footer .=  "<a href='pkg.php?xml=" . $_REQUEST['xml'] . "&startdisplayingat=0&display_maximum_rows={$display_maximum_rows}'>";
			}
			$final_footer .=  "<font size='2'><< Previous page</a>";
			if($tmppp + $display_maximum_rows > count($evaledvar)) 
				$endingrecord = count($evaledvar);
			else 
				$endingrecord = $tmppp + $display_maximum_rows;
			$final_footer .=  "</td><td align='center'>";
			$tmppp++;
			$final_footer .=  "<font size='2'>Displaying {$tmppp} - {$endingrecord} / " . count($evaledvar) . " records";
			$final_footer .=  "</td><td align='right'>&nbsp;";
			if(($i+1) < count($evaledvar))
				$final_footer .=  "<a href='pkg.php?xml=" . $_REQUEST['xml'] . "&startdisplayingat=" . ($startdisplayingat + $display_maximum_rows) . "&display_maximum_rows={$display_maximum_rows}'>";
			$final_footer .=  "<font size='2'>Next page >></a>";	
			$final_footer .=  "</td></tr></table></td></tr>";
			$i = count($evaledvar);
			break;
		}
	}
	$i++;
	$pagination_counter++;
	}
?>
				<tr>
					<td class="cell" colspan="<?=$cols?>"></td>
					<td class="cell tools">
						<a href="pkg_edit.php?xml=<?=$xml?>&id=<?=$i?>" title="Ekle">
							<i class="icon-plus icon_plus_right"></i>
						</a>
					</td>
				</tr>
			</tbody>
					</table>
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
