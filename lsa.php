<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
function browseSa($cb)
	{
	global $babBody;
	class temp
		{
		function temp($cb)
			{
			global $babDB;
			$this->cb = $cb;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");

			$this->sares = $babDB->db_query("select id, name, description from ".BAB_FLOW_APPROVERS_TBL."");
			if( !$this->sares )
				$this->sacount = 0;
			else
				$this->sacount = $babDB->db_num_rows($this->sares);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->sacount)
				{
				$arr = $babDB->db_fetch_array($this->sares);
				$this->sanameval = $arr['name'];
				$this->descval = $arr['description'];
				$this->saname = str_replace("'", "\'", $arr['name']);
				$this->saname = str_replace('"', "'+String.fromCharCode(34)+'",$this->saname);
				$this->said = $arr['id'];
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}

		}

	$temp = new temp($cb);
	echo bab_printTemplate($temp, "lsa.html", "browsesa");
	}

switch($idx)
	{	
	case "brow":
		browseSa($cb);
		exit;
		break;
	}
?>