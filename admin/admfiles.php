<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/


function listGroups()
	{
	global $body;
	class temp
		{
		var $fullname;
		var $public;
		var $private;
		var $url;
		var $urlname;
		var $group;
			
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $burl;

		function temp()
			{
			$this->fullname = babTranslate("Groups");
			$this->public = babTranslate("Public");
			$this->private = babTranslate("Private");
			$this->modify = babTranslate("Update");
			$req = "select * from groups order by id asc";
			$this->db = new db_mysql();
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->burl = true;
				$this->arr = $this->db->db_fetch_array($this->res);
				if( $this->arr[id] == 1 || $this->arr[id] == 2)
					$this->burl = false;

				if( $this->arr[gstorage] == "Y")
					$this->gstorage = "checked";
				else
					$this->gstorage = "";
				if( $this->arr[ustorage] == "Y")
					$this->ustorage = "checked";
				else
					$this->ustorage = "";
				$this->url = $GLOBALS[babUrl]."index.php?tg=group&idx=Modify&item=".$this->arr[id];
				if( $this->arr[id] < 3 )
					$this->urlname = getGroupName($this->arr[id]);
				else
					$this->urlname = $this->arr[name];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp, "admfiles.html", "admfileslist"));
	}

function updateGroups($groups, $ugroups )
	{
	$db = new db_mysql();
	$req = "select id from groups";
	$res = $db->db_query($req);
	while( $row = $db->db_fetch_array($res))
		{
		if( count($ugroups) > 0 && in_array($row[id], $ugroups))
			$us = "Y";
		else
			$us = "N";

		if( count($groups) > 0 && in_array($row[id], $groups))
			$gs = "Y";
		else
			$gs = "N";
		$req = "update groups set gstorage='".$gs."', ustorage='".$us."' where id='".$row[id]."'";
		$db->db_query($req);
		}
	}


/* main */
if( !isset($idx))
	$idx = "list";

if( isset($update) && $update == "update")
	updateGroups($groups, $ugroups );

switch($idx)
	{
	case "Modify":
	default:
		$body->title = babTranslate("File manager");
		listGroups();
		break;
	}

$body->setCurrentItemMenu($idx);

?>