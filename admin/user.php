<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."admin/register.php";
include $babInstallPath."utilit/grpincl.php";


function modifyUser($id)
	{
	global $body;
	if( !isset($id))
		{
		$body->msgerror = babTranslate("ERROR: You must choose a valid user !!");
		return;
		}
	class temp
		{
		var $changepassword;
		var $isconfirmed;
		var $primarygroup;
		var $groupname;
		var $groupid;
		var $none;
		
		var $isdisabled;
		var $modify;
		var $yes;
		var $no;

		var $arr = array();
		var $arrgroups = array();
		var $db;
		var $count;
		var $res;
		var $id;

		function temp($id)
			{
			$this->changepassword = babTranslate("Can user change password ?");
			$this->isconfirmed = babTranslate("Account confirmed ?");
			$this->isdisabled = babTranslate("Account disabled ?");
			$this->primarygroup = babTranslate("Primary group");
			$this->none = babTranslate("None");
			$this->modify = babTranslate("Modify");
			$this->yes = babTranslate("Yes");
			$this->no = babTranslate("No");
			$this->db = new db_mysql();
			$req = "select * from users where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->id = $id;

			$req = "select * from users_groups where id_object='$id'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;	
			if( $i < $this->count)
				{
				$this->arrgroups = $this->db->db_fetch_array($this->res);
				if( $this->arrgroups[isprimary] == "Y")
					$this->selected = "selected";
				else
					$this->selected = "";
				$this->groupname = getGroupName($this->arrgroups[id_group]);
				$this->groupid = $this->arrgroups[id_group];
				$i++;
				return true;
				}
			return false;
			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp, "users.html", "usersmodify"));
	}

function listGroups($id)
	{
	global $body;
	if( !isset($id))
		{
		$body->msgerror = babTranslate("ERROR: You must choose a valid user !!");
		return;
		}
	class temp
		{
		var $name;
		var $updategroups;
		var $group;

		var $db;
		var $id;
		var $count;
		var $res1;
		var $res2;
		var $groups;
		var $arrgroups;
		var $checked;
		var $primary;
		var $groupid;
		var $groupst;
		var $groupurl;
		var $groupname;

		function temp($id)
			{
			$this->name = babTranslate("Groups Names");
			$this->group = babTranslate("Group");
			$this->updategroups = babTranslate("Update Groups");
			$this->id = $id;
			$this->db = new db_mysql();
			$req = "select * from users_groups where id_object='$id'";
			$this->res1 = $this->db->db_query($req);
			$this->count1 = $this->db->db_num_rows($this->res1);
			if( $this->count1 < 1)
				$this->select = "selected";

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
				$this->groupid = $this->arrgroups[id];
				$this->groupname = $this->arrgroups[name];
				$this->groupurl = $GLOBALS[babUrl]."index.php?tg=group&idx=Members&item=".$this->arrgroups[id];
				if($this->count1 > 0)
					{
					$this->db->db_data_seek($this->res1, 0);
					$this->primary = "";
					$this->checked = "";
					for( $j = 0; $j < $this->count1; $j++)
						{
						$this->groups = $this->db->db_fetch_array($this->res1);
						if( $this->groups[id_group] == $this->arrgroups[id])
							{
							if( $this->groups[isprimary] == "Y")
								$this->primary = "Y"; 
							$this->checked = "checked";
							if( empty($this->groupst))
								$this->groupst = $this->arrgroups[id];
							else
								$this->groupst .= ",".$this->arrgroups[id];
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
	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp, "users.html", "usersgroups"));
	}

function deleteUser($id)
	{
	global $body, $BAB_SESS_USERID;

	if( $id == $BAB_SESS_USERID /* || isUserAlreadyLogged($id) */)
		{
		$body->msgerror = babTranslate("Sorry, you cannot delete this user. He is already logged");
		return;
		}
	
	class temp
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;

		function temp($id)
			{
			$this->message = babTranslate("Are you sure you want to delete this user");
			$this->title = getUserName($id);
			$this->warning = babTranslate("WARNING: This operation will delete the user and all references"). "!";
			$this->urlyes = $GLOBALS[babUrl]."index.php?tg=user&idx=Delete&user=".$id."&action=Yes";
			$this->yes = babTranslate("Yes");
			$this->urlno = $GLOBALS[babUrl]."index.php?tg=user&idx=Modify&item=".$id;
			$this->no = babTranslate("No");
			}
		}

	$temp = new temp($id);
	$body->babecho(	babPrintTemplate($temp,"warning.html", "warningyesno"));
	}


function updateGroups($id, $groups, $groupst)
	{
	$db = new db_mysql();

	$tab = explode(",", $groupst);

	for( $i = 0; $i < count($tab); $i++)
	{
		if( !in_array($tab[$i], $groups))
		{
			$req = "delete from users_groups where id_group='".$tab[$i]."' and id_object='".$id."'";
			$res = $db->db_query($req);
		}
	}
	for( $i = 0; $i < count($groups); $i++)
	{
		if( !in_array($groups[$i], $tab))
		{
			$req = "insert into users_groups (id_group, id_object) VALUES ('" .$groups[$i]. "', '" . $id. "')";
			$res = $db->db_query($req);
		}
	}

	}

function updateUser($id, $changepwd, $is_confirmed, $disabled, $group)
	{
	global $body;

	$req = "update users set changepwd='$changepwd', is_confirmed='$is_confirmed', disabled='$disabled' where id='$id'";
	$db = new db_mysql();
	$res = $db->db_query($req);
	if( !empty($group))
		{
		$req = "update users_groups set isprimary='Y'where id_object='$id' and id_group='$group'";
		$db = new db_mysql();
		$res = $db->db_query($req);
		}
	Header("Location: index.php?tg=users&idx=List");
	}

function confirmDeleteUser($id)
	{
	$db = new db_mysql();

	// delete notes owned by this user
	$req = "delete from notes where id_user='$id'";
	$res = $db->db_query($req);	

	// delete user from groups
	$req = "delete from users_groups where id_object='$id'";
	$res = $db->db_query($req);	
					
	$req = "select * from calendar where owner='$id' and type='1'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);

	// delete user's events
	$req = "delete from cal_events where id_cal='".$arr[id]."'";
	$res = $db->db_query($req);	

	// delete user's access
	$req = "delete from calaccess_users where id_user='".$id."'";
	$res = $db->db_query($req);	

	// delete user from calendar
	$req = "delete from calendar where owner='$id' and type='1'";
	$res = $db->db_query($req);	

	// delete user from users_log
	$req = "delete from users_log where id_user='$id'";
	$res = $db->db_query($req);	

	// delete user from mail_signatures
	$req = "delete from mail_signatures where owner='$id'";
	$res = $db->db_query($req);	

	// delete user from mail_accounts
	$req = "delete from mail_accounts where owner='$id'";
	$res = $db->db_query($req);	

	// delete user from mail_domains
	$req = "delete from mail_domains where owner='$id' and bgroup='N'";
	$res = $db->db_query($req);	

	// delete user from contacts
	$req = "delete from contacts where owner='$id'";
	$res = $db->db_query($req);	

	// delete user
	$req = "delete from users where id='$id'";
	$res = $db->db_query($req);	
	Header("Location: index.php?tg=users&idx=List");
	}

/* main */

if( !isset($idx))
	$idx = "Modify";

if( isset($modify))
	updateUser($item, $changepwd, $is_confirmed, $disabled, $group);

if( isset($action) && $action == "Yes")
	{
	confirmDeleteUser($user);
	}

switch($idx)
	{
	case "Delete":
		$body->title = babTranslate("Delete a user");
		deleteUser($item);
		$body->addItemMenu("List", babTranslate("Users"),$GLOBALS[babUrl]."index.php?tg=users&idx=List&pos=".$pos."&grp=".$grp);
		$body->addItemMenu("Modify", babTranslate("Modify"),$GLOBALS[babUrl]."index.php?tg=user&idx=Modify&item=".$item."&pos=".$pos."&grp=".$grp);
		$body->addItemMenu("Groups", babTranslate("Groups"),$GLOBALS[babUrl]."index.php?tg=user&idx=Groups&item=".$item."&pos=".$pos."&grp=".$grp);
		$body->addItemMenu("Delete", babTranslate("Delete"),$GLOBALS[babUrl]."index.php?tg=user&idx=Delete&item=".$item."&pos=".$pos."&grp=".$grp);
		//$body->addItemMenu("Create", babTranslate("Create"), $GLOBALS[babUrl]."index.php?tg=users&idx=Create");
		break;
	case "Updateu";
		updateGroups($item, $groups, $groupst);
		/* no break */
	case "Groups":
		$body->title = getUserName($item) . babTranslate(" is member of");
		listGroups($item);
		$body->addItemMenu("List", babTranslate("Users"),$GLOBALS[babUrl]."index.php?tg=users&idx=List&pos=".$pos."&grp=".$grp);
		$body->addItemMenu("Modify", babTranslate("User"),$GLOBALS[babUrl]."index.php?tg=user&idx=Modify&item=".$item."&pos=".$pos."&grp=".$grp);
		$body->addItemMenu("Groups", babTranslate("Groups"),$GLOBALS[babUrl]."index.php?tg=user&idx=Groups&item=".$item."&pos=".$pos."&grp=".$grp);
		$body->addItemMenu("Upadteu", babTranslate("Update"), "javascript:(submitForm('Updateu'))");
		break;
	case "Modify":
		$body->title = /* babTranslate("Modify a user") . ": " . */getUserName($item);
		modifyUser($item);
		$body->addItemMenu("List", babTranslate("Users"),$GLOBALS[babUrl]."index.php?tg=users&idx=List&pos=".$pos."&grp=".$grp);
		$body->addItemMenu("Modify", babTranslate("Modify"),$GLOBALS[babUrl]."index.php?tg=user&idx=Modify&item=".$item."&pos=".$pos."&grp=".$grp);
		$body->addItemMenu("Groups", babTranslate("Groups"),$GLOBALS[babUrl]."index.php?tg=user&idx=Groups&item=".$item."&pos=".$pos."&grp=".$grp);
		$body->addItemMenu("Delete", babTranslate("Delete"),$GLOBALS[babUrl]."index.php?tg=user&idx=Delete&item=".$item."&pos=".$pos."&grp=".$grp);
		//$body->addItemMenu("Create", babTranslate("Create"), $GLOBALS[babUrl]."index.php?tg=users&idx=Create");
		break;
	default:
		break;
	}

$body->setCurrentItemMenu($idx);
?>