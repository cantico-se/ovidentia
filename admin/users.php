<?php
/************************************************************************
 * OVIDENTIA http://www.ovidentia.org                                   *
 ************************************************************************
 * Copyright (c) 2003 by CANTICO ( http://www.cantico.fr )              *
 *                                                                      *
 * This file is part of Ovidentia.                                      *
 *                                                                      *
 * Ovidentia is free software; you can redistribute it and/or modify    *
 * it under the terms of the GNU General Public License as published by *
 * the Free Software Foundation; either version 2, or (at your option)  *
 * any later version.													*
 *																		*
 * This program is distributed in the hope that it will be useful, but  *
 * WITHOUT ANY WARRANTY; without even the implied warranty of			*
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.					*
 * See the  GNU General Public License for more details.				*
 *																		*
 * You should have received a copy of the GNU General Public License	*
 * along with this program; if not, write to the Free Software			*
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,*
 * USA.																	*
************************************************************************/
include_once "base.php";
include $babInstallPath."admin/register.php";
include $babInstallPath."utilit/lusersincl.php";

function listUsers($pos, $grp)
	{
	global $babBody;
	class temp
		{
		var $fullname;
		var $urlname;
		var $url;
		var $email;
		var $status;
				
		var $fullnameval;
		var $emailval;

		var $arr = array();
		var $db;
		var $count;
		var $res;

		var $pos;
		var $selected;
		var $allselected;
		var $allurl;
		var $allname;
		var $urlmail;

		var $grp;
		var $group;
		var $groupurl;
		var $checked;
		var $userid;
		var $usert;

		var $nickname;
		var $bmodname;
		var $altbg = true;

		function temp($pos, $grp)
			{
			global $babBody;
			$this->email = bab_translate("Email");
			$this->allname = bab_translate("All");
			$this->update = bab_translate("Update");
			$this->nickname = bab_translate("Nickname");
			$this->db = $GLOBALS['babDB'];
			$this->group = bab_getGroupName($grp);
			$this->grp = $grp;

			switch ($babBody->nameorder[0]) {
				case "L":
					$this->namesearch = "lastname";
					$this->namesearch2 = "firstname";
				break;
				case "F":
				default:
					$this->namesearch = "firstname";
					$this->namesearch2 = "lastname";
				break; }

			if( $pos[0] == "-" )
				{
				$this->pos = $pos[1];
				$this->ord = $pos[0];
				if( $babBody->currentAdmGroup == 0)
					$req = "select * from ".BAB_USERS_TBL." where ".$this->namesearch2." like '".$this->pos."%' order by ".$this->namesearch2.", ".$this->namesearch." asc";
				else
					$req = "select u.* from ".BAB_USERS_TBL." u, ".BAB_USERS_GROUPS_TBL." ug where u.disabled != '1' and ug.id_object=u.id and ug.id_group='".$babBody->currentAdmGroup."' and u.".$this->namesearch2." like '".$this->pos."%' order by u.".$this->namesearch2.", u.".$this->namesearch." asc";
				$this->fullname = bab_composeUserName(bab_translate("Lastname"),bab_translate("Firstname"));
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=users&idx=chg&pos=".$this->ord.$this->pos."&grp=".$this->grp;
				}
			else
				{
				$this->pos = $pos;
				$this->ord = "";
				if( $babBody->currentAdmGroup == 0)
					$req = "select * from ".BAB_USERS_TBL." where ".$this->namesearch." like '".$this->pos."%' order by ".$this->namesearch.", ".$this->namesearch2." asc";
				else
					$req = "select u.* from ".BAB_USERS_TBL." u, ".BAB_USERS_GROUPS_TBL." ug where u.disabled != '1' and ug.id_object=u.id and ug.id_group='".$babBody->currentAdmGroup."' and u.".$this->namesearch." like '".$this->pos."%' order by u.".$this->namesearch.", u.".$this->namesearch2." asc";
				$this->fullname = bab_composeUserName(bab_translate("Firstname"),bab_translate("Lastname"));
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=users&idx=chg&pos=".$this->ord.$this->pos."&grp=".$this->grp;
				}
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);

			if( empty($this->pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS['babUrlScript']."?tg=users&idx=List&pos=&grp=".$this->grp;
			$this->groupurl = $GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$this->grp;
			if( $babBody->currentAdmGroup == 0 && $this->grp == 3 )
				$this->bmodname = true;
			else
				$this->bmodname = false;

			$this->userst = '';
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=user&idx=Modify&item=".$this->arr['id']."&pos=".$this->ord.$this->pos."&grp=".$this->grp;
				if( $this->ord == "-" )
					$this->urlname = bab_composeUserName($this->arr['lastname'],$this->arr['firstname']);
				else
					$this->urlname = bab_composeUserName($this->arr['firstname'],$this->arr['lastname']);

				$this->userid = $this->arr['id'];
				$req = "select * from ".BAB_USERS_LOG_TBL." where id_user='".$this->arr['id']."'";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					$this->status ="*";
				else
					$this->status ="";

				$req = "select * from ".BAB_USERS_GROUPS_TBL." where id_object='".$this->arr['id']."' and id_group='".$this->grp."'";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					{
					$this->checked = "checked";
					if( empty($this->userst))
						$this->userst = $this->arr['id'];
					else
						$this->userst .= ",".$this->arr['id'];
					}
				else
					{
					$this->checked = "";
					}

				$i++;
				return true;
				}
			else
				return false;

			}

		function getnextselect()
			{
			global $babBody, $BAB_SESS_USERID;
			static $k = 0;
			static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			if( $k < 26)
				{
				$this->selectname = substr($t, $k, 1);
				$this->selecturl = $GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$this->ord.$this->selectname."&grp=".$this->grp;

				if( $this->pos == $this->selectname)
					$this->selected = 1;
				else 
					{
					if( $this->ord == "-" )
						{
						if( $babBody->currentAdmGroup == 0)
							$req = "select id from ".BAB_USERS_TBL." where ".$this->namesearch2." like '".$this->selectname."%'";
						else
							$req = "select u.id from ".BAB_USERS_TBL." u,  ".BAB_USERS_GROUPS_TBL." ug where ug.id_object=u.id and ug.id_group='".$babBody->currentAdmGroup."' and u.".$this->namesearch2." like '".$this->selectname."%'";
						}
					else
						{
						if( $babBody->currentAdmGroup == 0)
							$req = "select id from ".BAB_USERS_TBL." where ".$this->namesearch." like '".$this->selectname."%'";
						else
							$req = "select u.id from ".BAB_USERS_TBL." u,  ".BAB_USERS_GROUPS_TBL." ug where ug.id_object=u.id and ug.id_group='".$babBody->currentAdmGroup."' and u.".$this->namesearch." like '".$this->selectname."%'";
						}
					$res = $this->db->db_query($req);
					if( $this->db->db_num_rows($res) > 0 )
						$this->selected = 0;
					else
						$this->selected = 1;
					}
				$k++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp($pos, $grp);
	$babBody->babecho(	bab_printTemplate($temp, "users.html", "userslist"));
	return $temp->count;
	}

function userCreate($firstname, $middlename, $lastname, $nickname, $email)
	{
	global $babBody;
	class temp
		{
		var $firstname;
		var $lastname;
		var $nickname;
		var $email;
		var $password;
		var $repassword;
		var $adduser;
		var $firstnameval;
		var $lastnameval;
		var $nicknameval;
		var $emailval;
		var $notifyuser;
		var $sendpassword;
		var $yes;
		var $no;

		function temp($firstname, $middlename, $lastname, $nickname, $email)
			{
			$this->firstnameval = $firstname != ""? $firstname: "";
			$this->middlenameval = $middlename != ""? $middlename: "";
			$this->lastnameval = $lastname != ""? $lastname: "";
			$this->nicknameval = $nickname != ""? $nickname: "";
			$this->emailval = $email != ""? $email: "";
			$this->firstname = bab_translate("First Name");
			$this->lastname = bab_translate("Last Name");
			$this->middlename = bab_translate("Middle Name");
			$this->nickname = bab_translate("Nickname");
			$this->email = bab_translate("Email");
			$this->password = bab_translate("Password");
			$this->repassword = bab_translate("Retype Password");
			$this->notifyuser = bab_translate("Notify user");
			$this->sendpassword = bab_translate("Send password with email");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->adduser = bab_translate("Confirm");
			}
		}

	$temp = new temp($firstname, $middlename, $lastname, $nickname, $email);
	$babBody->babecho(	bab_printTemplate($temp,"users.html", "usercreate"));
	}

function updateGroup( $grp, $users, $userst)
{
	$db = $GLOBALS['babDB'];

	if( !empty($userst))
		$tab = explode(",", $userst);
	else
		$tab = array();

	for( $i = 0; $i < count($tab); $i++)
	{
		if( count($users) < 1 || !in_array($tab[$i], $users))
		{
			bab_removeUserFromGroup($tab[$i], $grp);
		}
	}
	for( $i = 0; $i < count($users); $i++)
	{
		if( count($tab) < 1 || !in_array($users[$i], $tab))
		{
			bab_addUserToGroup($users[$i], $grp);
		}
	}
}

/* main */
if( !isset($pos))
	$pos = "A";

if( !isset($grp) || empty($grp))
	{
	if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 )
		$grp = 3;
	else if( $babBody->currentAdmGroup != 0 )
		{
		$grp = $babBody->currentAdmGroup;
		}
	}

if( !isset($idx))
	$idx = "List";

if( isset($adduser) && $babBody->isSuperAdmin )
{
	if( !registerUser( $firstname, $lastname, $middlename, $email, $nickname, $password1, $password2, true))
		$idx = "Create";
	else
		{
		switch ($babBody->nameorder[0]){
			case "L":
				$pos = substr($lastname,0,1);
			break;
			case "F":
			default:
				$pos = substr($firstname,0,1);
			break;
		}
		$idx = "List";
		if( $notifyuser == "Y" )
			{
			if( bab_isMagicQuotesGpcOn())
				{
				$firstname = addslashes($firstname);
				$lastname = addslashes($lastname);
				}
			notifyAdminUserRegistration(bab_composeUserName($firstname , $lastname), $email, $nickname, $sendpwd == "Y"? $password1: "" );
			}
		}
}

if( $idx == "chg")
{
	if( $pos[0] == "-")
		$pos = $pos[1];
	else
		$pos = "-" .$pos;
	$idx = "List";
}

if( isset($Updateg) && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0 ))
{
	updateGroup($grp, $users, $userst);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
	exit;
}

if( $idx == "Create" && !$babBody->isSuperAdmin )
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}


switch($idx)
	{	
	case "brow":
		if( $babBody->isSuperAdmin || $babBody->currentAdmGroup != 0 )
			{
			browseUsers($pos, $cb);
			}
		else
			{
			echo bab_translate("Access denied");
			}
		exit;
		break;
	case "Create":
		$babBody->title = bab_translate("Create a user");
		userCreate($firstname, $middlename, $lastname, $nickname, $email);
		$babBody->addItemMenu("List", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=users&idx=Create&pos=".$pos);
		break;
	case "List":
		if( $babBody->isSuperAdmin || $babBody->currentAdmGroup != 0 )
			{
			$babBody->title = bab_translate("Users list");
			$cnt = listUsers($pos, $grp);
			if ($grp != 3 && $grp != $babBody->currentAdmGroup) $babBody->addItemMenu("cancel", bab_translate("Group's members"),$GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$grp);
			$babBody->addItemMenu("List", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=users&idx=List");
			if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0)
				$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=users&idx=Create&pos=".$pos."&grp=".$grp);
			}
		else
			{
			$babBody->msgerror = bab_translate("Access denied");
			}
		break;
	default:
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>
