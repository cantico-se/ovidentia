<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/

function calendarGroups()
	{
	global $body;

	class temp
		{
		var $name;
		var $updategroups;
		var $all;
		var $none;
		var $listgroups;

		var $arr = array();
		var $what = array();
		var $db;
		var $id;
		var $count;
		var $res;
		var $groups;
		var $arrgroups = array();
		var $select;
		var $users;

		function temp()
			{
			$this->name = babTranslate("Groups Names");
			$this->updategroups = babTranslate("Update Groups");
			$this->none = babTranslate("None");
			$this->all = babTranslate("All");
			$this->users = babTranslate("Registered Users");
			$this->listgroups = babTranslate("Groups List");
			$this->db = new db_mysql();
			
			$req = "select * from calendar where owner!='2' and type='2' order by owner asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnextgroup()
			{
			static $i = 0;
			
			if( $i < $this->count)
				{
				$this->arrgroups = $this->db->db_fetch_array($this->res);
				if($this->arrgroups[owner] == 1)
					{
					$this->arrgroups[name] = /*$this->users*/babTranslate("Registered users");
					}
				else
					$this->arrgroups[name] = getGroupName($this->arrgroups[owner]);

				if($this->arrgroups[actif] == "Y")
					{
					$this->arrgroups[select] = "selected";
					}
				else
					{
					$this->arrgroups[select] = "";
					}
				$i++;
				return true;
				}
			else
				{
				return false;
				}

			}
		}
	$temp = new temp($target, $index, $table, $id, $return);
	$body->babecho(	babPrintTemplate($temp, "admcals.html", "calendargroups"));
	}

function groupsUpdate($groups, $what)
	{
	$db = new db_mysql();

	if( $what == "0") // none
		{
		$req = "update calendar set actif='N' where type='2'";
		$res = $db->db_query($req);
		}
	else if( $what == -1 ) // all
		{
		$req = "update calendar set actif='Y' where owner!='2' and type='2'";
		$res = $db->db_query($req);
		}
	else
		{
		$cnt = count($groups);
		$req = "update calendar set actif='N' where type='2'";
		$res = $db->db_query($req);
		if( $cnt > 0)
			{
			for( $i = 0; $i < $cnt; $i++)
				{
				$req = "update calendar set actif='Y' where id='".$groups[$i]."' and type='2'";
				$res = $db->db_query($req);
				}
			}
		}
	}

/* main */
if( !isset($idx))
	$idx = "groups";

if( isset($calgroups) && $calgroups == "update")
	groupsUpdate($groups, $what);

switch($idx)
	{
	default:
	case "groups":
		calendarGroups();
		$body->title = babTranslate("Groups List");
		$body->addItemMenu("groups", babTranslate("Groups"), $GLOBALS[babUrl]."index.php?tg=admcals&idx=groups");
		$body->addItemMenu("listcat", babTranslate("Categories"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listcat&userid=0");
		$body->addItemMenu("resources", babTranslate("Resources"), $GLOBALS[babUrl]."index.php?tg=confcals&idx=listres&userid=0");
		break;
	}

$body->setCurrentItemMenu($idx);

?>