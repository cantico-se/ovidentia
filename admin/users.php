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
include_once $babInstallPath."admin/register.php";
include_once $babInstallPath."utilit/lusersincl.php";

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

			$this->t_online = bab_translate("Online");
			$this->t_unconfirmed = bab_translate("Unconfirmed");
			$this->t_disabled = bab_translate("Disabled");
			$this->t_dirdetail = bab_translate("Detail");

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

			$req = "select u.*, dbt.id as idu from ".BAB_USERS_TBL." u left join ".BAB_DBDIR_ENTRIES_TBL." dbt on u.id=dbt.id_user and dbt.id_directory='0'";

			if( isset($pos) &&  strlen($pos) > 0 && $pos[0] == "-" )
				{
				$this->pos = strlen($pos)>1? $pos[1]: '';
				$this->ord = $pos[0];
				if( $babBody->currentAdmGroup == 0)
					$req .= " where ".$this->namesearch2." like '".$this->pos."%' order by ".$this->namesearch2.", ".$this->namesearch." asc";
				else
					$req .= ", ".BAB_USERS_GROUPS_TBL." ug where u.disabled != '1' and ug.id_object=u.id and ug.id_group='".$babBody->currentAdmGroup."' and u.".$this->namesearch2." like '".$this->pos."%' order by u.".$this->namesearch2.", u.".$this->namesearch." asc";
				$this->fullname = bab_composeUserName(bab_translate("Lastname"),bab_translate("Firstname"));
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=users&idx=chg&pos=".$this->ord.$this->pos."&grp=".$this->grp;
				}
			else
				{
				$this->pos = $pos;
				$this->ord = "";
				if( $babBody->currentAdmGroup == 0)
					$req .= " where ".$this->namesearch." like '".$this->pos."%' order by ".$this->namesearch.", ".$this->namesearch2." asc";
				else
					$req .= ", ".BAB_USERS_GROUPS_TBL." ug where u.disabled != '1' and ug.id_object=u.id and ug.id_group='".$babBody->currentAdmGroup."' and u.".$this->namesearch." like '".$this->pos."%' order by u.".$this->namesearch.", u.".$this->namesearch2." asc";
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
			list($this->iddir) = $this->db->db_fetch_row($this->db->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group='1'"));

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

				$this->dirdetailurl = $GLOBALS['babUrlScript']."?tg=directory&idx=ddb&id=".$this->iddir."&idu=".$this->arr['idu']."&pos=&xf=";
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


function utilit()
	{
	global $babBody;
	class temp
		{

		function temp()
			{
			$this->db = $GLOBALS['babDB'];
			$this->t_nb_total_users = bab_translate('Total users');
			$this->t_nb_unconfirmed_users = bab_translate('Unconfirmed users');
			$this->t_delete_unconfirmed = bab_translate('Delete unconfirmed users from');
			$this->t_days = bab_translate('Days');
			$this->t_ok = bab_translate('Ok');
			$this->js_alert = bab_translate('Day number must be 1 at least');
			
			list($this->arr['nb_total_users']) = $this->db->db_fetch_array($this->db->db_query("SELECT COUNT(*) FROM ".BAB_USERS_TBL.""));

			list($this->arr['nb_unconfirmed_users']) = $this->db->db_fetch_array($this->db->db_query("SELECT COUNT(*) FROM ".BAB_USERS_TBL." WHERE is_confirmed='0'"));
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"users.html", "utilit"));
	}
	
function delete_unconfirmed()
	{
	include $GLOBALS['babInstallPath']."utilit/delincl.php";
	$db = $GLOBALS['babDB'];
	$res = $db->db_query("SELECT id FROM ".BAB_USERS_TBL." WHERE is_confirmed='0' AND (DATE_ADD(datelog, INTERVAL ".$_POST['nb_days']." DAY) < NOW() OR datelog = '0000-00-00 00:00:00')");
	while (list($id) = $db->db_fetch_array($res))
		bab_deleteUser($id);
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
	if( !registerUser( stripslashes($firstname), stripslashes($lastname), stripslashes($middlename), $email, $nickname, $password1, $password2, true))
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
	if( strlen($pos) > 0 && $pos[0] == "-" )
		$pos = strlen($pos)>1? $pos[1]: '';
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
		if (!isset($firstname)) $firstname = '';
		if (!isset($middlename)) $middlename = '';
		if (!isset($lastname)) $lastname = '';
		if (!isset($nickname)) $nickname = '';
		if (!isset($email)) $email = '';
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
				{
				$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=users&idx=Create&pos=".$pos."&grp=".$grp);
				$babBody->addItemMenu("utilit", bab_translate("Utilities"), $GLOBALS['babUrlScript']."?tg=users&idx=utilit");
				}
			}
		else
			{
			$babBody->msgerror = bab_translate("Access denied");
			}
		break;
	case "utilit":
		if ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0)
			{
			if (isset($_POST['action']) && $_POST['action'] == 'delete_unconfirmed')
				delete_unconfirmed();
				
			$babBody->addItemMenu("List", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
			$babBody->addItemMenu("utilit", bab_translate("Utilities"), $GLOBALS['babUrlScript']."?tg=users&idx=utilit");
			$babBody->title = bab_translate("Utilities");
			utilit();
			}
		break;
	default:
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>
