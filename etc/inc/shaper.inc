<?php
/*
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

require_once('functions.inc');
require_once('util.inc');

function &get_reference_to_me_in_config(&$mypath) 
{
	global $config;

	 $ptr =& $config['shaper'];
	foreach ($mypath as $indeks) {
		$ptr =& $ptr['queue'][$indeks];
	}

	return $ptr;
}

function unset_object_by_reference(&$mypath) 
{
	global $config;

	$ptr =& $config['shaper'];
	for ($i = 0; $i < count($mypath) - 1; $i++) {
		$ptr =& $ptr['queue'][$mypath[$i]];
	}
	unset($ptr['queue'][$mypath[$i]]);
}

function &get_dn_reference_to_me_in_config(&$mypath) 
{
	global $config;

	$ptr =& $config['dnshaper'];
	foreach ($mypath as $indeks) {
		$ptr =& $ptr['queue'][$indeks];
	}

	return $ptr;
}

function unset_dn_object_by_reference(&$mypath) 
{
	global $config;

	$ptr =& $config['dnshaper'];
	for ($i = 0; $i < count($mypath) - 1; $i++) {
		$ptr =& $ptr['queue'][$mypath[$i]];
	}
	unset($ptr['queue'][$mypath[$i]]);
}

function clean_child_queues($type, $mypath) 
{
	$ref = &get_reference_to_me_in_config($mypath);

	switch ($type) {
	case 'HFSC':
		if (isset($ref['borrow'])) unset($ref['borrow']);		
		if (isset($ref['hogs'])) unset($ref['hogs']);
		if (isset($ref['buckets'])) unset($ref['buckets']);
		break;
	case 'PRIQ':
		if (isset($ref['borrow'])) unset($ref['borrow']);		
		if (isset($ref['bandwidth'])) unset($ref['bandwidth']);
		if (isset($ref['bandwidthtype'])) unset($ref['bandwidthtype']);
		/* fall through */
	case 'FAIRQ':
		if (isset($ref['borrow'])) unset($ref['borrow']);		
		/* fall through */
	case 'CBQ':
		if (isset($ref['realtime'])) unset($ref['realtime']);	
		if (isset($ref['realtime1'])) unset($ref['realtime1']);
		if (isset($ref['realtime2'])) unset($ref['realtime2']);
		if (isset($ref['realtime3'])) unset($ref['realtime3']);
		if (isset($ref['upperlimit'])) unset($ref['upperlimit']);
		if (isset($ref['upperlimit1'])) unset($ref['upperlimit1']);
		if (isset($ref['upperlimit2'])) unset($ref['upperlimit2']);
		if (isset($ref['upperlimit3'])) unset($ref['upperlimit3']);
		if (isset($ref['linkshare'])) unset($ref['linkshare']);
		if (isset($ref['linkshare1'])) unset($ref['linkshare1']);
		if (isset($ref['linkshare2'])) unset($ref['linkshare2']);
		if (isset($ref['linkshare3'])) unset($ref['linkshare3']);	
		if (isset($ref['hogs'])) unset($ref['hogs']);
		if (isset($ref['buckets'])) unset($ref['buckets']);
		break;
	}
}

function get_bandwidthtype_scale($type) 
{
        switch ($type) {
        case "Gb":
                $factor = 1024 * 1024 * 1024;
        	break;
        case "Mb":
                $factor = 1024 * 1024;
        	break;
        case "Kb":
                $factor = 1024;
        	break;
        case "b":
        default:
                $factor = 1;
        	break;
        }
        return intval($factor);
}

function get_hfsc_bandwidth($object, $bw) 
{
	$pattern= "/[0-9]+/";
        if (preg_match($pattern, $bw, $match))
                $bw_1 = $match[1];
        else
                return 0;
        $pattern= "/(b|Kb|Mb|Gb|%)/";
        if (preg_match($pattern, $bw, $match)) {
                switch ($match[1]) {
                case '%':
                        $bw_1 = $bw_1 / 100 * get_interface_bandwidth($object);
                        break;
                default:
                        $bw_1 = $bw_1 * get_bandwidthtype_scale($match[0]);
                        break;
                }
		return floatval($bw_1);
        } else
                return 0;
}

function get_interface_bandwidth($object) 
{
	global $altq_list_queues;

        $int = $object->GetInterface();
        $altq =& $altq_list_queues[$int];
        if ($altq) {
                $bw_3 = $altq->GetBandwidth();
                $bw_3 = $bw_3 *  get_bandwidthtype_scale($altq->GetBwscale());
		return floatval($bw_3);
        } else
		return 0;
}

function shaper_do_input_validation($postdata, $reqdfields, $reqdfieldsn, $input_errors) 
{
        foreach ($postdata as $pn => $pd) {
                if (is_string($pd) && preg_match("/[\\x00-\\x08\\x0b\\x0c\\x0e-\\x1f]/", $pd)) {
                        $input_errors[] = "The field '" . $pn . "' contains invalid characters.";
                }
        }

        for ($i = 0; $i < count($reqdfields); $i++) {
                if ($postdata[$reqdfields[$i]] == "") {
                        $input_errors[] = "The field '" . $reqdfieldsn[$i] . "' is required.";
                }
        }
}

function cleanup_queue_from_rules($queue) 
{
	global $config;

	foreach ($config['filter']['rule'] as $rule) {
		if ($rule['defaultqueue'] == $queue)
			unset($rule['defaultqueue']);
		if ($rule['ackqueue'] == $queue)
			unset($rule['ackqueue']);
	}
}

function cleanup_dnqueue_from_rules($queue) 
{
	global $config;

	foreach ($config['filter']['rule'] as $rule) {
		if ($rule['dnpipe'] == $queue)
			unset($rule['dnpipe']);
		if ($rule['pdnpipe'] == $queue)
			unset($rule['pdnpipe']);
	}
}

$dummynet_pipe_list = array();

class dummynet_class {
        var $qname;
	var $qnumber;
        var $qlimit;
        var $description;
	var $qenabled;
	var $link;
	var $qparent;
        var $plr;

        var $buckets;
        /* mask parameters */
        var $mask;
        var $noerror;

        /* Accessor functions */
        function SetLink($link) {
                $this->link = $link;
        }
        function GetLink() {
                return $this->link;
        }
	function Getmask() {
		return $this->mask;
	}
	function SetMask($mask) {
		$this->mask = $mask;
	}
	function &GetParent() {
		return $this->qparent;
	}
	function SetParent(&$parent) {
		$this->qparent = &$parent;
	}
        function GetEnabled() {
                return $this->qenabled;
        }
        function SetEnabled($value) {
                $this->qenabled = $value;
        }
	function CanHaveChildren() {
		return false;
        }
	function CanBeDeleted() {
                return true;
        }
        function GetQname() {
                return $this->qname;
        }
        function SetQname($name) {
                $this->qname = trim($name);
        }
        function GetQlimit() {
                return $this->qlimit;
        }
        function SetQlimit($limit) {
               	$this->qlimit = $limit;
        }
        function GetDescription() {
                return $this->description;
        }
        function SetDescription($str) {
                $this->description = trim($str);
        }
        function GetFirstime() {
                return $this->firsttime;
        }
        function SetFirsttime($number) {
                $this->firsttime = $number;
        }
        function GetBuckets() {
                return $this->buckets;
        }
        function SetBuckets($buckets) {
                $this->buckets = $buckets;
        }
	function SetNumber($number) {
		$this->qnumber = $number;
	}
	function GetNumber() {
		return $this->qnumber;
	}
        function GetPlr() {
                return $this->plr;
        }
        function SetPlr($plr) {
                $this->plr = $plr;
        }

	function build_javascript() { return; }

	function validate_input($data, &$input_errors) {
		$reqdfields[] = "bandwidth";
		$reqdfieldsn[] = "Bandwidth";
		$reqdfields[] = "bandwidthtype";
		$reqdfieldsn[] = "Bandwidthtype";
		$reqdfields[] = "newname";
		$reqdfieldsn[] = "Name";
	
		shaper_do_input_validation($data, $reqdfields, $reqdfieldsn, $input_errors);

		if ($data['plr'] && ((!is_numeric($data['plr'])) ||
			($data['plr'] <= 0 && $data['plr'] > 1))) 
            		$input_errors[] = "Plr must be an integer between 1 and 100.";
		if (($data['buckets'] && (!is_numeric($data['buckets']))) ||
			($data['buckets'] < 1 && $data['buckets'] > 100)) 
            		$input_errors[] = "Buckets must be an integer between 16 and 65535.";
		if ($data['qlimit'] && (!is_numeric($data['qlimit']))) 
            		$input_errors[] = "Queue limit must be an integer";
        	if (!empty($data['newname']) && !preg_match("/^[a-zA-Z0-9_-]+$/", $data['newname']))
			$input_errors[] = "Sınırlayıcı ismi sadece alfanümerik karakterler, '_' ve '-' karakterlerinden oluşabilir.";
        	if (!empty($data['name']) && !preg_match("/^[a-zA-Z0-9_-]+$/", $data['name']))
			$input_errors[] = "Sınırlayıcı ismi sadece alfanümerik karakterler, '_' ve '-' karakterlerinden oluşabilir.";
	}
}

class dnpipe_class extends dummynet_class {
    var $delay;
	var $qbandwidth;
	var $qbandwidthtype;

     var $subqueues = array();

	function CanHaveChildren() {
	        return true;
        }
	function SetDelay($delay) {
		$this->delay = $delay;
	}
	function GetDelay() {
		return $this->delay;
	}
	function GetBwscale() {
                return $this->qbandwidthtype;
        }
        function SetBwscale($scale) {
               	$this->qbandwidthtype = $scale;
        }		
	function delete_queue() {
		cleanup_dnqueue_from_rules($this->GetQname());
		foreach ($this->subqueues as $q)
			$q->delete_queue();
		unset_dn_object_by_reference($this->GetLink());
		mwexec("/sbin/ipfw pipe delete " . $this->GetNumber());
        }
        function GetBandwidth() {
                return $this->qbandwidth;
        }
        function SetBandwidth($bandwidth) {
                $this->qbandwidth = $bandwidth;
        }

	function &add_queue($interface, &$queue, &$path, &$input_errors) {

		if (!is_array($this->subqueues))
			$this->subqueues = array();
			
		$q =& new dnqueue_class();
		$q->SetLink($path);
		$q->SetEnabled("on");
		$q->SetPipe($this->GetQname());
		$q->SetParent(&$this);
		$q->ReadConfig($queue);
		$q->validate_input($queue, $input_errors);
		if (count($input_errors)) {
			log_error("SHAPER: could not create queue " . $q->GetQname() . " on interface {$interface} because: " . print_r($input_errors, true));
			return $q;
		}
		$this->subqueues[$q->GetQname()] = &$q;
            
		return $q;
	}

	function &get_queue_list($q = null) {
		$qlist = array();

		$qlist[$this->GetQname()] = $this->GetNumber();
		if (is_array($this->subqueues)) {
			foreach ($this->subqueues as $queue)
				$queue->get_queue_list(&$qlist);
		}
		return $qlist;
	}
		
        function &find_queue($pipe, $qname) {
                if ($qname == $this->GetQname()) 
                        return $this;
               	foreach ($this->subqueues as $q) {
                       	$result =& $q->find_queue("", $qname);
						if ($result)
                       	        return $result;
               	}
        }

	function &find_parentqueue($pipe, $qname) {
		return NULL;
       	}

	function validate_input($data, &$input_errors) {
		parent::validate_input($data, $input_errors);

		if ($data['bandwidth'] && (!is_numeric($data['bandwidth']))) 
       		     	$input_errors[] = "Hız sınırı bir tamsayı olmalıdır.";
		if ($data['delay'] && (!is_numeric($data['delay'])))
            		$input_errors[] = "Delay must be an integer.";
		}

	function ReadConfig(&$q) {
		if (!empty($q['name']) && !empty($q['newname']) && $q['name'] != $q['newname']) {
			$this->SetQname($q['newname']);
		} else if (!empty($q['newname'])) {
			$this->SetQname($q['newname']);
		} else {
			$this->SetQname($q['name']);
		}
		$this->SetNumber($q['number']);
		if (isset($q['bandwidth']) && $q['bandwidth'] <> "") { 
			$this->SetBandwidth($q['bandwidth']);
			if (isset($q['bandwidthtype']) && $q['bandwidthtype'])
				$this->SetBwscale($q['bandwidthtype']);
		}
		if (isset($q['qlimit']) && $q['qlimit'] <> "")
              		$this->SetQlimit($q['qlimit']);
		else
              		$this->SetQlimit("");
		if (isset($q['mask']) && $q['mask'] <> "")
              		$this->SetMask($q['mask']);
		else
              		$this->SetMask("");
		if (isset($q['buckets']) && $q['buckets'] <> "")
              		$this->SetBuckets($q['buckets']);
		else
              		$this->SetBuckets("");
            	if (isset($q['plr']) && $q['plr'] <> "")
            		$this->SetPlr($q['plr']);
		else
            		$this->SetPlr("");
		if (isset($q['delay']) && $q['delay'] <> "")
            		$this->SetDelay($q['delay']);
		else
			$this->SetDelay(0);
            	if (isset($q['description']) && $q['description'] <> "")
			$this->SetDescription($q['description']);
		else
			$this->SetDescription("");
		$this->SetEnabled($q['enabled']);

        }

	function build_tree() {
		$tree = " <li><a class=\"btn btn-link\" href=\"firewall_shaper_vinterface.php?pipe=" . $this->GetQname() ."&queue=".$this->GetQname() ."&action=show\">"; 
		$tree .= $this->GetQname() . "</a>";
		/*if (is_array($this->subqueues))
		{

			foreach ($this->subqueues as $q) 
			{
				$tree .= $q->build_tree();
			}	
			
		}*/
		$tree .= "</li>";
		return $tree;
	}

        function build_rules() {
		if ($this->GetEnabled() == "")
			return;

       		$pfq_rule = "\npipe ". $this->GetNumber() . " config ";
		if ($this->GetBandwidth() && $this->GetBwscale())
                    	$pfq_rule .= " bw ".trim($this->GetBandwidth()).$this->GetBwscale();
		if ($this->GetQlimit())
                    	$pfq_rule .= " queue " . $this->GetQlimit();
		if ($this->GetPlr())
			$pfq_rule .= " plr " . $this->GetPlr();
		if ($this->GetBuckets())
			$pfq_rule .= " buckets " . $this->GetBuckets();
		if ($this->GetDelay())
			$pfq_rule .= " delay " . $this->GetDelay();

		$mask = $this->GetMask();
		if (!empty($mask)) {
			switch ($mask) {
			case 'srcaddress':
				$pfq_rule .= " mask src-ip 0xffffffff ";
				break;
			case 'dstaddress':
				$pfq_rule .= " mask dst-ip 0xffffffff ";
				break;
			default:
				break;
			}
    		}            
		$pfq_rule .= "\n";

		if (!empty($this->subqueues) && count($this->subqueues) > 0) {
			foreach ($this->subqueues as $q)
			$pfq_rule .= $q->build_rules();
		}
		$pfq_rule .= " \n";

		return $pfq_rule;
        }

	function update_dn_data(&$data) { 
		$this->ReadConfig($data);
	}

        function build_form() { 
		$form = "<tr><td class=\"vncell\">";
		$form .= "Aktif";
		$form .= "</td><td class=\"vtable\">";
		$form .= " <input type=\"checkbox\" id=\"enabled\" name=\"enabled\" value=\"on\"";
		if ($this->GetEnabled() == "on")
			$form .=  " checked/>";
		else
			$form .=  "/>";
		$form .= "</td></tr>";
		$form .= "<tr><td class=\"vncell\">İsim</td>";
		$form .= "<td class=\"vtable\">";
		$form .= "<input type=\"text\" id=\"newname\" name=\"newname\" value=\"";
		$form .= $this->GetQname()."\">";
		$form .= "<input type=\"hidden\" id=\"name\" name=\"name\" value=\"";
		$form .= $this->GetQname()."\">";
		$form .= "</td></tr>";
		$form .= "<tr><td class=\"vncell\">Hız Sınırı";
		$form .= "</td><td class=\"vtable\">";
		$form .= "<input type=\"text\" id=\"bandwidth\" name=\"bandwidth\" value=\"";
		$form .= $this->GetBandwidth() . "\">"; 
		$form .= "<select id=\"bandwidthtype\" name=\"bandwidthtype\">";
		$form .= "<option value=\"Kb\"";
		if ($this->GetBwscale() == "Kb")
			$form .= " selected=\"yes\"";
		$form .= ">Kbit/s</option>";
		$form .= "<option value=\"Mb\"";
		if ($this->GetBwscale() == "Mb")
			$form .= " selected=\"yes\"";
		$form .= ">Mbit/s</option>";
		$form .= "<option value=\"Gb\"";
		if ($this->GetBwscale() == "Gb")
			$form .= " selected=\"yes\"";
		$form .= ">Gbit/s</option>";		
		$form .= "<option value=\"\"";
		if ($this->GetBwscale() == "b")
			$form .= " selected=\"yes\"";
		$form .= ">Bit/s</option>";
		$form .= "</select>";
		$form .= "</td></tr>";
		$form .= "<tr><td class=\"vncell\">Yönü</td>";
		$form .= "<td class=\"vtable\">";
		$form .= "<select name=\"mask\">";
		$form .= "<option value=\"none\"";
		if ($this->GetMask() == "none")
			$form .= " selected=\"yes\"";
		$form .= ">Yok</option>";
		$form .= "<option value=\"srcaddress\"";
		if ($this->GetMask() == "srcaddress")
			$form .= " selected=\"yes\"";
		$form .= ">Kanyak Adresi</option>";
		$form .= "<option value=\"dstaddress\"";
		if ($this->GetMask() == "dstaddress")
			$form .= " selected=\"yes\"";
		$form .= ">Hedef Adresi</option>";
		$form .= "</select>";
		$form .= "</td></tr>";

		return $form;
			
		}

	function wconfig() {
		$cflink =& get_dn_reference_to_me_in_config($this->GetLink());
		
		if (!is_array($cflink))
			$cflink = array();
		
		$cflink['name'] = $this->GetQname();
		$cflink['number'] = $this->GetNumber();
		$cflink['qlimit'] = $this->GetQlimit();
		$cflink['plr'] = $this->GetPlr();
		$cflink['description'] = $this->GetDescription();
		$cflink['bandwidth'] = $this->GetBandwidth();
		$cflink['bandwidthtype'] = $this->GetBwscale();
		$cflink['enabled'] = $this->GetEnabled();
		$cflink['buckets'] = $this->GetBuckets();
		$cflink['mask'] = $this->GetMask();
		$cflink['delay'] = $this->GetDelay();
	}

}

class dnqueue_class extends dummynet_class {
        var $pipeparent;
        var $weight;

        function GetWeight() {
                return $this->weight;
        }
        function SetWeight($weight) {
                $this->weight = $weight;
        }
	function GetPipe() {
		return $this->pipeparent;
	}
	function SetPipe($pipe) {
		$this->pipeparent = $pipe;
	}

	function &add_queue($interface, &$queue, &$path, &$input_errors) { return; }

	function delete_queue() {
		cleanup_dnqueue_from_rules($this->GetQname());
		unset_dn_object_by_reference($this->GetLink());
		mwexec("/sbin/ipfw queue delete " . $this->GetNumber());
        }

	function validate_input($data, &$input_errors) {
		parent::validate_input($data, $input_errors);

		if ($data['weight'] && ((!is_numeric($data['weight'])) ||
			($data['weight'] < 1 && $data['weight'] > 100))) 
       		     	$input_errors[] = "Weight must be an integer between 1 and 100.";
	}

        function &find_queue($pipe, $qname) {
                if ($qname == $this->GetQname()) 
                	return $this;
		else
			return NULL;
        }

	function &find_parentqueue($pipe, $qname) {
		return $this->qparent;
        }

        function &get_queue_list(&$qlist) {
		if ($this->GetEnabled() == "")
			return;
        	$qlist[$this->GetQname()] = "?" .$this->GetNumber();
        }		

	function ReadConfig(&$q) {
		if (!empty($q['name']) && !empty($q['newname']) && $q['name'] != $q['newname']) {
			$this->SetQname($q['newname']);
		} else if (!empty($q['newname'])) {
			$this->SetQname($q['newname']);
		} else {
			$this->SetQname($q['name']);
		}
		$this->SetNumber($q['number']);
		
		if (isset($q['qlimit']) && $q['qlimit'] <> "")
			$this->SetQlimit($q['qlimit']);
		else
			$this->SetQlimit("");
		
		if (isset($q['mask']) && $q['mask'] <> "")
              		$this->SetMask($q['mask']);
		else
			$this->SetMask("");
		
		if (isset($q['weight']) && $q['weight'] <> "")
			$this->SetWeight($q['weight']);
		else
            $this->SetWeight("");
        
		if (isset($q['description']) && $q['description'] <> "")
			$this->SetDescription($q['description']);
		else
			$this->SetDescription("");
		
		$this->SetEnabled($q['enabled']);
        }

	function build_tree() {
		$parent =& $this->GetParent();
		$tree = " <li><a href=\"firewall_shaper_vinterface.php?pipe=" . $parent->GetQname() ."&queue=" . $this->GetQname() ."&action=show\">"; 
		$tree .= $this->GetQname() . "</a>";
		$tree .= "</li>";
		
		return $tree;
	}

        function build_rules() {
		if ($this->GetEnabled() == "")
			return; 

		$parent =& $this->GetParent();
            	$pfq_rule = "queue ". $this->GetNumber() . " config pipe " . $parent->GetNumber();
		if ($this->GetQlimit())
                    	$pfq_rule .= " queue " . $this->GetQlimit();
		if ($this->GetWeight())
			$pfq_rule .= " weight " . $this->GetWeight();
		if ($this->GetBuckets())
			$pfq_rule .= " buckets " . $this->GetBuckets();
		$mask = $this->GetMask();
		if (!empty($mask)) {
			switch ($mask) {
			case 'srcaddress':
				$pfq_rule .= " mask src-ip 0xffffffff ";
				break;
			case 'dstaddress':
				$pfq_rule .= " mask dst-ip 0xffffffff ";
				break;
			default:
				break;
			}
			$pfq_rule .= "\n";
		}

		return $pfq_rule;
	}

        function build_form() { 
		$form = "<tr><td valign=\"center\"><br>";
                $form .= "Enable/Disable";
                $form .= "</td><td>";
                $form .= " <input type=\"checkbox\" id=\"enabled\" name=\"enabled\" value=\"on\"";
                if ($this->GetEnabled() == "on")
                        $form .=  " CHECKED";
                $form .= " ><span class=\"vexpl\"> Enable/Disable queue</span>";
                $form .= "</td></tr>";
		$form .= "<tr><td valign=\"center\"><br><span class=\"vexpl\">Name</span></td>";
		$form .= "<td>";
		$form .= "<input type=\"text\" id=\"newname\" name=\"newname\" value=\"";
		$form .= $this->GetQname()."\">";
		$form .= "<input type=\"hidden\" id=\"name\" name=\"name\" value=\"";
		$form .= $this->GetQname()."\">";
		$form .= "</td></tr>";
		$form .= "<tr><td valign=\"center\">Mask</td>";
		$form .= "<td>";
		$form .= "<select name=\"mask\" class=\"formselect\">";
		$form .= "<option value=\"none\"";
		if ($this->GetMask() == "none")
			$form .= " selected=\"yes\"";
		$form .= ">none</option>";
		$form .= "<option value=\"srcaddress\"";
		if ($this->GetMask() == "srcaddress")
			$form .= " selected=\"yes\"";
		$form .= ">Source addresses</option>";
		$form .= "<option value=\"dstaddress\"";
		if ($this->GetMask() == "dstaddress")
			$form .= " selected=\"yes\"";
		$form .= ">Destination addresses</option>";
		$form .= "</select>";
		$form .= "&nbsp;slots<br>";
		$form .= "<span class=\"vexpl\">If 'source' or 'destination' is chosen, \n";
		$form .= "a dynamic pipe with the bandwidth, delay, packet loss and queue size given above will \n";
		$form .= "be created for each source/destination IP address encountered, \n";
		$form .= "respectively. This makes it possible to easily specify bandwidth \n";
		$form .= "limits per host.</span>";
		$form .= "</td></tr>";
		$form .= "<tr><td valign=\"center\">Description</td>";
		$form .= "<td>";
		$form .= "<input type=\"text\" id=\"description\" class=\"formfld unknown\" size=\"50%\" name=\"description\" value=\"";
		$form .= $this->GetDescription();
		$form .= "\">";
		$form .= "<br> <span class=\"vexpl\">";
		$form .= "You may enter a description here ";
		$form .= "for your reference (not parsed).</span>";
		$form .= "</td></tr>";
		$form .= "<tr id=\"sprtable4\" name=\"sprtable4\">";
		$form .= "<td></td>";

		$form .= "<tr style=\"display:none\" id=\"sprtable\" name=\"sprtable\">";
		$form .= "<td valign=\"center\">Weight</td>";
		$form .= "<td valign=\"center\">";
		$form .= "<input name=\"weight\" type=\"text\" id=\"weight\" size=\"5\" value=\"";
		$form .= $this->GetWeight() . "\">";
		$form .= "&nbsp;<br> <span class=\"vexpl\">Hint: For queues under the same parent "; 
		$form .= "this specifies the share that a queue gets(values range from 1 to 100, you can leave it blank otherwise)</span>";
		$form .= "</td></tr>";
		$form .= "<tr style=\"display:none\" id=\"sprtable1\" name=\"sprtable1\">";
		$form .= "<td valign=\"center\">Packet loss rate</td>";
		$form .= "<td valign=\"center\">";
		$form .= "<input name=\"plr\" type=\"text\" id=\"plr\" size=\"5\" value=\"";
		$form .= $this->GetPlr() . "\">";
		$form .= "&nbsp;<br> <span class=\"vexpl\">Hint: in most cases, you "; 
        	$form .= "should specify 0 here (or leave the field empty).";
		$form .= "A value of 0.001 means one packet in 1000 gets dropped</span>";
		$form .= "</td></tr>";
		$form .= "<tr style=\"display:none\" id=\"sprtable2\" name=\"sprtable2\">";
		$form .= "<td valign=\"center\">Queue Size</td>";
		$form .= "<td>";
		$form .= "<input type=\"text\" id=\"qlimit\" name=\"qlimit\" value=\"";
		$form .= $this->GetQlimit() . "\">";
		$form .= "&nbsp;slots<br>";
		$form .= "<span class=\"vexpl\">Hint: in most cases, you ";
		$form .= "should leave the field empty. All packets in this pipe are placed into a fixed-size queue first, ";
        	$form .= "then they are delayed by value specified in the Delay field, and then they ";
		$form .= "are delivered to their destination.</span>";
		$form .= "</td></tr>";
		$form .= "<tr style=\"display:none\" id=\"sprtable5\" name=\"sprtable5\">";
                $form .= "<td valign=\"center\">Bucket Size</td>";
                $form .= "<td>";
                $form .= "<input type=\"text\" id=\"buckets\" name=\"buckets\" value=\"";
                $form .= $this->GetBuckets() . "\">";
                $form .= "&nbsp;slots<br>";
                $form .= "<span class=\"vexpl\">Hint: in most cases, you ";
                $form .= "should leave the field empty. It increases the hash size set.";
                $form .= "</td></tr>";

		$form .= "<input type=\"hidden\" id=\"pipe\" name=\"pipe\"";
		$form .= " value=\"" . $this->GetPipe() . "\">";

		return $form;
			
	}

        function update_dn_data(&$data) { 
		$this->ReadConfig($data);
	}

	function wconfig() {
		$cflink =& get_dn_reference_to_me_in_config($this->GetLink());
		
		if (!is_array($cflink))
			$cflink = array();
		
		$cflink['name'] = $this->GetQname();
		$cflink['number'] = $this->GetNumber();
		$cflink['qlimit'] = $this->GetQlimit();
		$cflink['description'] = $this->GetDescription();
		$cflink['weight'] = $this->GetWeight();
		$cflink['enabled'] = $this->GetEnabled();
		$cflink['buckets'] = $this->GetBuckets();
		$cflink['mask'] = $this->GetMask();
	}
}

function get_divert_ports() {
    global $layer7_rules_list;
    $dports = array();
    
    foreach($layer7_rules_list as $l7r)
        $dports[] = $l7r->GetRPort();
    
    return $dports;
}

function &get_l7c_reference_to_me_in_config(&$name) {
	global $config;
	
	$ptr = NULL;
	
	if(is_array($config['l7shaper']['container'])) {
		foreach($config['l7shaper']['container'] as $key => $value) {
			if($value['name'] == $name)
				$ptr =& $config['l7shaper']['container'][$key];
		}
	}	
	return $ptr;
// $ptr can be null. has to be checked later
}

function unset_l7_object_by_reference(&$name) {
	global $config;
        
	if(is_array($config['l7shaper']['container'])) {
		foreach($config['l7shaper']['container'] as $key => $value) {
			if($value['name'] == $name) {
				unset($config['l7shaper']['container'][$key]['l7rules']);
				unset($config['l7shaper']['container'][$key]);
				break;
			}
		}
	}
}

function read_layer7_config() {
    global $layer7_rules_list, $config;
    
    $l7cs = &$config['l7shaper']['container'];
    
    $layer7_rules_list = array();
    
    if (!is_array($config['l7shaper']['container']) || !count($config['l7shaper']['container']))
	return;
    
    foreach ($l7cs as $conf) {
	if (empty($conf['name']))
		continue; /* XXX: grrrrrr at php */ 
        $root =& new layer7();
        $root->ReadConfig($conf['name'],$conf);
        $layer7_rules_list[$root->GetRName()] = &$root;
    }
}

function generate_layer7_files() {
    global $layer7_rules_list, $g;
    
    read_layer7_config();
    
    if (!empty($layer7_rules_list)) {
	if (!is_module_loaded("ipdivert.ko"))
		mwexec("/sbin/kldload ipdivert.ko");

	mwexec("rm -f {$g['tmp_path']}/*.l7");
    }
    
    foreach($layer7_rules_list as $l7rules) {
        if($l7rules->GetREnabled()) {
            $filename = $l7rules->GetRName() . ".l7";
            $path = "{$g['tmp_path']}/" . $filename;
        
            $rules = $l7rules->build_l7_rules();
        
            $fp = fopen($path,'w');
            fwrite($fp,$rules);
            fclose($fp);
        }
    }
}

function layer7_start_l7daemon() {
    global $layer7_rules_list, $g;

    foreach($layer7_rules_list as $l7rules) {
        if($l7rules->GetREnabled()) {
            $filename = $l7rules->GetRName() . ".l7";
            $path = "{$g['tmp_path']}/" . $filename;

	    unset($l7pid);
	    /* Only reread the configuration rather than restart to avoid loosing information. */
	    exec("/bin/pgrep -f 'ipfw-classifyd .* -p ". $l7rules->GetRPort() . "'", $l7pid);
	    if (count($l7pid) > 0) {
		log_error("Sending HUP signal to {$l7pid[0]}");
		mwexec("/bin/kill -HUP {$l7pid[0]}");
	    } else {
		// XXX: Hardcoded number of packets to garbage collect and queue length..
		$ipfw_classifyd_init = "/usr/local/sbin/ipfw-classifyd -n 8 -q 700 -c {$path} -p " . $l7rules->GetRPort() . " -P /usr/local/share/protocols";
		mwexec_bg($ipfw_classifyd_init);
	    }
        }
    }
}

function generate_protocols_array() {
	$protocols = return_dir_as_array("/usr/local/share/protocols");
	$protocols_new = array();
	if(is_array($protocols)) {
		foreach($protocols as $key => $proto) {
			if (strstr($proto, ".pat"))
				$protocols_new[$key] =& str_replace(".pat", "", $proto);
		}
		sort($protocols_new);
	}		
	return $protocols_new;
}

function get_l7_unique_list() {
	global $layer7_rules_list;
	
	$l7list = array();
	if(is_array($layer7_rules_list)) 
		foreach($layer7_rules_list as $l7c)
			if($l7c->GetREnabled())
				$l7list[] = $l7c->GetRName();
	
	return $l7list;
}

// Disable a removed l7 container from the filter
function cleanup_l7_from_rules(&$name) {
	global $config;

	if(is_array($config['filter']['rule']))
		foreach ($config['filter']['rule'] as $key => $rule) {
			if ($rule['l7container'] == $name)
				unset($config['filter']['rule'][$key]['l7container']);
		}
}

function get_dummynet_name_list() {
	
	$dn_name_list =& get_unique_dnqueue_list();
	$dn_name = array();
	if(is_array($dn_name_list))
		foreach($dn_name_list as $key => $value)
			$dn_name[] = $key;
	
	return $dn_name;
	
}

function get_altq_name_list() {
	$altq_name_list =& get_unique_queue_list();
	$altq_name = array();
	if(is_array($altq_name_list))
		foreach($altq_name_list as $key => $aqobj)
			$altq_name[] = $key;
		
	return $altq_name;
}

function altq_get_default_queue($interface) {
	global $altq_list_queues;

	$altq_tmp = $altq_list_queues[$interface];
	if ($altq_tmp)
		return $altq_tmp->GetDefaultQueuePresent(); 
	else
		return false;
}

function altq_check_default_queues() {
	global $altq_list_queues;

	$count = 0;
	if (is_array($altq_list_queues)) {
		foreach($altq_list_queues as $altq) {
			if ($altq->GetDefaultQueuePresent())
				$count++;
		}
	}
	else  $count++;;
	
	return 0;
}

function &get_unique_queue_list() {
	global $altq_list_queues;
	
	$qlist = array();
	if (is_array($altq_list_queues)) {
		foreach ($altq_list_queues as $altq) {
			if ($altq->GetEnabled() == "")
				continue;
			$tmplist =& $altq->get_queue_list();
			foreach ($tmplist as $qname => $link) {
				if ($link->GetEnabled() <> "")
					$qlist[$qname] = $link;	
			}
		}
	}
	return $qlist;
}

function &get_unique_dnqueue_list() {
	global $dummynet_pipe_list;
	
	$qlist = array();
	if (is_array($dummynet_pipe_list)) {
		foreach ($dummynet_pipe_list as $dn) {
			if ($dn->GetEnabled() == "")
				continue;
			$tmplist =& $dn->get_queue_list();
			foreach ($tmplist as $qname => $link) {
				$qlist[$qname] = $link;	
			}
		}
	}
	return $qlist;
}

function ref_on_altq_queue_list($parent, $qname) {
	if (isset($GLOBALS['queue_list'][$qname]))
		$GLOBALS['queue_list'][$qname]++;
	else
		$GLOBALS['queue_list'][$qname] = 1;

	unref_on_altq_queue_list($parent);
}

function unref_on_altq_queue_list($qname) {
	$GLOBALS['queue_list'][$qname]--;
	if ($GLOBALS['queue_list'][$qname] <= 1)
		unset($GLOBALS['queue_list'][$qname]);	
}

function read_dummynet_config() {
	global $dummynet_pipe_list, $config;
	$path = array();
	$dnqueuenumber = 1;
	$dnpipenumber = 1;

	if (!is_array($config['dnshaper']))
		$config['dnshaper'] = array();
	if (!is_array($config['dnshaper']['queue']))
		$config['dnshaper']['queue'] = array();
	$a_int = &$config['dnshaper']['queue'];

	$dummynet_pipe_list = array();
	
	if (!is_array($config['dnshaper']['queue'])
		|| !count($config['dnshaper']['queue']))
		return;

	foreach ($a_int as $key => $conf) {
		if (empty($conf['name']))
			continue; /* XXX: grrrrrr at php */ 
		$root =& new dnpipe_class();
		$root->ReadConfig($conf);
		$root->SetNumber($dnpipenumber);	
		$dummynet_pipe_list[$root->GetQname()] = &$root;
		array_push($path, $key);
		$root->SetLink($path);
		if (is_array($conf['queue'])) {
			foreach ($conf['queue'] as $key1 => $q) {
				array_push($path, $key1);
				/* XXX: We cheat a little here till a better way is found. */
				$q['number'] = $dnqueuenumber;
				/* 
				 * XXX: we compeletely ignore errors here but anyway we must have 
				 *	checked them before so no harm should be come from this.
				 */	
				$root->add_queue($root->GetQname(), $q, &$path, $input_errors);
				array_pop($path);

				$dnqueuenumber++;
			} 	
		}
		array_pop($path);
			
		$dnpipenumber++;
	}
}

function get_interface_list_to_show() {
	global $altq_list_queues, $config;
	global $shaperIFlist;

	$tree = "";
	foreach ($shaperIFlist as $shif => $shDescr) {
		if ($altq_list_queues[$shif]) {
			continue;
		} else  {
			if (!is_altq_capable(get_real_interface($shif)))
				continue;
			$tree .= " <li><a href=\"firewall_shaper.php?interface=".$shif."&action=add\">".$shDescr."</a></li>";
		}
	}
	
	return $tree;
}

function filter_generate_dummynet_rules() {
	global $g, $dummynet_pipe_list;
	
	read_dummynet_config();
	
	if (!empty($dummynet_pipe_list)) {
		if (!is_module_loaded("dummynet.ko"))
			mwexec("/sbin/kldload dummynet");
		/* XXX: Needs to be added code elsewhere to clear pipes/queues from kernel when not needed! */
		//mwexec("pfctl -F dummynet");
	}

	$dn_rules = "";
	foreach ($dummynet_pipe_list as $dn) 
		$dn_rules .= $dn->build_rules();

	if (!empty($dn_rules)) {
		file_put_contents("{$g['tmp_path']}/rules.limiter", $dn_rules);
		mwexec("/sbin/ipfw {$g['tmp_path']}/rules.limiter");
	}
	//return $dn_rules;
}

function build_iface_without_this_queue($iface, $qname) {
	global $g, $altq_list_queues;
	global $shaperIFlist;

	$altq =& $altq_list_queues[$iface];
	if ($altq)
		$scheduler = ": " . $altq->GetScheduler();
	$form = "<tr><td width=\"20%\" >";
	$form .= "<a href=\"firewall_shaper.php?interface=" . $iface . "&queue=" . $iface."&action=show\">". $shaperIFlist[$iface] . $scheduler."</a>";
	$form .= "</td></tr>";
	$form .= "<tr><td width=\"100%\">";
	$form .= "<a href=\"firewall_shaper_queues.php?interface=";
	$form .= $iface . "&queue=". $qname . "&action=add\">";
	$form .= "<img src=\"";
	$form .= "./themes/".$g['theme']."/images/icons/icon_plus.gif\"";
	$form .= " width=\"17\" height=\"17\" border=\"0\" title=\"Clone shaper/queue on this interface\">";
	$form .= " Clone shaper/queue on this interface</a></td></tr>";

	return $form;

}

?>
