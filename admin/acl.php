<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
function aclGroups($target, $index, $table, $id, $return)
	{
	global $body;
	if( !isset($id))
		{
		$body->msgerror = babTranslate("ERROR: You must choose a valid item !!");
		return;
		}
	class temp
		{
		var $name;
		var $updategroups;
		var $disabled;
		var $everybody;
		var $users;
		var $guests;
		var $listgroups;
		var $return;

		var $arr = array();
		var $what = array();
		var $db;
		var $id;
		var $count;
		var $res;
		var $groups;
		var $arrgroups;
		var $select;
		var $table;
		var $target;
		var $index;

		function temp($target, $index, $table, $id, $return)
			{
			$this->table = $table;
			$this->target = $target;
			$this->index = $index;
			$this->return = $return;
			$this->name = babTranslate("Groups Names");
			$this->updategroups = babTranslate("Update Groups");
			$this->disabled = babTranslate("Disabled");
			$this->everybody = babTranslate("Everybody");
			$this->users = babTranslate("Registered Users");
			$this->guests = babTranslate("Unregistered Users");
			$this->listgroups = babTranslate("Groups List");
			$this->db = new db_mysql();
			$this->id = $id;
			$this->what[everybody] = "";
			$this->what[users] = "";
			$this->what[guests] = "";
			$this->what[disabled] = "";
			
			$req = "select * from ".$table." where id_object='$id'";
			$this->res1 = $this->db->db_query($req);
			$this->count1 = $this->db->db_num_rows($this->res1);
			if( $this->count1 < 1 )
				$this->what[disabled] = "selected";			
			else if( $this->count1 == 1)
				{
				$arr = $this->db->db_fetch_array($this->res1);
				if( $arr[id_group] < 3)
					{
					$this->count1 = 0;
					switch($arr[id_group])
						{
						case 0:
							$this->what[everybody] = "selected";
							break;
						case 1:
							$this->what[users] = "selected";
							break;
						case 2:
							$this->what[guests] = "selected";
							break;
						}
					}
				}

			$req = "select * from groups where id > 2 order by id asc";
			$this->res2 = $this->db->db_query($req);
			$this->count2 = $this->db->db_num_rows($this->res2);
			}

		function getnextgroup()
			{
			static $i = 0;
			
			if( $i < $this->count2)
				{
				$this->arrgroups = $this->db->db_fetch_array($this->res2);
				if($this->count1 > 0)
					{
					$this->db->db_data_seek($this->res1, 0);
					$this->arrgroups[select] = "";
					for( $j = 0; $j < $this->count1; $j++)
						{
						$this->groups = $this->db->db_fetch_array($this->res1);
						if( $this->groups[id_group] == $this->arrgroups[id])
							{
							$this->arrgroups[select] = "selected";
							break;
							}
						}
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
	$body->babecho(	babPrintTemplate($temp, "acl.html", "aclgroups"));
	}

function aclUpdate($table, $id, $groups, $what)
	{
	$db = new db_mysql();
	$req = "delete from ".$table." where id_object = '$id'";
	$res = $db->db_query($req);

	$arr = array();

	if( $what == "")
		{
		$cnt = count($groups);
		if( $cnt > 0)
			{
			for( $i = 0; $i < $cnt; $i++)
				{
				$req = "insert into ".$table." (id_object, id_group) values ('". $id. "', '" . $groups[$i]. "')";
				$res = $db->db_query($req);
				}
			}
		}
	else if( $what != -1)
		{
		$req = "insert into ".$table." (id_object, id_group) values ('". $id. "', '" . $what. "')";
		$res = $db->db_query($req);
		}
	}
?>