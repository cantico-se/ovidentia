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

/**
* @internal SEC1 PR 16/02/2007 FULL
*/

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
			global $babDB;
			
			$this->email = bab_translate("Email");
			$this->allname = bab_translate("All");
			$this->update = bab_translate("Update");
			$this->nickname = bab_translate("Nickname");

			$this->t_online = bab_translate("Online");
			$this->t_unconfirmed = bab_translate("Unconfirmed");
			$this->t_disabled = bab_translate("Disabled");
			$this->t_dirdetail = bab_translate("Detail");
			
			$this->checkall = bab_translate("Check all");
			$this->uncheckall = bab_translate("Uncheck all");

			
			$this->group = bab_toHtml(bab_getGroupName($grp));
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

			// group members
			$this->group_members = array();
			$res = $babDB->db_query("SELECT id_object FROM ".BAB_USERS_GROUPS_TBL." WHERE id_group='".$babDB->db_escape_string($this->grp)."'");
			while (list($id_user) = $babDB->db_fetch_array($res))
				{
				$this->group_members[$id_user] = $id_user;
				}

			// User login status
			$this->users_logged = array();
			$res = $babDB->db_query("SELECT id_user FROM ".BAB_USERS_LOG_TBL."");
			while (list($id_user) = $babDB->db_fetch_array($res))
				{
				$this->users_logged[$id_user] = $id_user;
				}


			$this->bupdate = isset($_REQUEST['bupd'])? $_REQUEST['bupd']: 0;

			$req = "SELECT u.* from ".BAB_USERS_TBL." u";

			if( isset($pos) &&  strlen($pos) > 0 && $pos[0] == "-" )
				{
				$this->pos = strlen($pos)>1? $pos[1]: '';
				$this->ord = $pos[0];
				if( $babBody->currentAdmGroup == 0 || ($this->bupdate && $babBody->currentDGGroup['battach'] == 'Y' && $this->grp == $babBody->currentDGGroup['id_group']))
					{
					$req .= " where ".$this->namesearch2." like '".$babDB->db_escape_string($this->pos)."%' order by ".$babDB->db_escape_string($this->namesearch2).", ".$babDB->db_escape_string($this->namesearch)." asc";
					}
				else
					{
					$req .= ", ".BAB_USERS_GROUPS_TBL." ug, ".BAB_GROUPS_TBL." g where u.disabled != '1' and ug.id_object=u.id and ug.id_group=g.id AND g.lf>='".$babDB->db_escape_string($babBody->currentDGGroup['lf'])."' AND g.lr<='".$babDB->db_escape_string($babBody->currentDGGroup['lr'])."' and u.".$babDB->db_escape_string($this->namesearch2)." like '".$babDB->db_escape_string($this->pos)."%' order by u.".$babDB->db_escape_string($this->namesearch2).", u.".$babDB->db_escape_string($this->namesearch)." asc";
					}

				$this->fullname = bab_toHtml(bab_composeUserName(bab_translate("Lastname"),bab_translate("Firstname")));
				$this->fullnameurl = bab_toHtml( $GLOBALS['babUrlScript']."?tg=users&idx=chg&pos=".urlencode($this->ord.$this->pos)."&grp=".urlencode($this->grp));
				}
			else
				{
				$this->pos = $pos;
				$this->ord = "";
				if( $babBody->currentAdmGroup == 0 || ($this->bupdate && $babBody->currentDGGroup['battach'] == 'Y' && $this->grp == $babBody->currentDGGroup['id_group']))
					$req .= " where ".$babDB->db_escape_string($this->namesearch)." like '".$babDB->db_escape_string($this->pos)."%' order by ".$babDB->db_escape_string($this->namesearch).", ".$babDB->db_escape_string($this->namesearch2)." asc";
				else
					$req .= ", ".BAB_USERS_GROUPS_TBL." ug, ".BAB_GROUPS_TBL." g where u.disabled != '1' and ug.id_object=u.id and ug.id_group=g.id AND g.lf>='".$babDB->db_escape_string($babBody->currentDGGroup['lf'])."' AND g.lr<='".$babDB->db_escape_string($babBody->currentDGGroup['lr'])."' and u.".$babDB->db_escape_string($this->namesearch)." like '".$babDB->db_escape_string($this->pos)."%' order by u.".$babDB->db_escape_string($this->namesearch).", u.".$babDB->db_escape_string($this->namesearch2)." asc";
				$this->fullname = bab_toHtml(bab_composeUserName(bab_translate("Firstname"),bab_translate("Lastname")));
				$this->fullnameurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=users&idx=chg&pos=".urlencode($this->ord.$this->pos)."&grp=".urlencode($this->grp));
				}
				
			bab_debug($req);

			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);

			if( empty($this->pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=users&idx=List&pos=&grp=".$this->grp."&bupd=".$this->bupdate);
			$this->groupurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$this->grp);
			
			list($iddir) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group='1'"));
			$this->set_directory = $babBody->currentAdmGroup == 0 && bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL,$iddir);
			
			if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['users'] == 'Y' )
				$this->bmodname = true;
			else
				$this->bmodname = false;

			$this->userst = '';

			if( $babBody->currentAdmGroup != 0 && $grp == $babBody->currentDGGroup['id_group'] && ($babBody->currentDGGroup['battach'] != 'Y' || $this->bupdate == 0))
				{
				$this->bshowform = false;
				}
			else
				{
				$this->bshowform = true;
				}
			

			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				global $babDB;
				$this->arr = $babDB->db_fetch_array($this->res);
				$this->url = bab_toHtml( $GLOBALS['babUrlScript']."?tg=user&idx=Modify&item=".urlencode($this->arr['id'])."&pos=".urlencode($this->ord.$this->pos)."&grp=".urlencode($this->grp));
				if( $this->ord == "-" )
					$this->urlname = bab_toHtml(bab_composeUserName($this->arr['lastname'],$this->arr['firstname']));
				else
					$this->urlname = bab_toHtml(bab_composeUserName($this->arr['firstname'],$this->arr['lastname']));

				$this->userid = bab_toHtml($this->arr['id']);

				if( isset($this->users_logged[$this->userid]))
					$this->status ="*";
				else
					$this->status ="";

				if( isset($this->group_members[$this->userid]))
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

				//$this->dirdetailurl = $GLOBALS['babUrlScript']."?tg=directory&idx=ddb&id=".$this->iddir."&idu=".$this->arr['idu']."&pos=&xf=";

				$this->dirdetailurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=users&idx=dirv&id_user=".urlencode($this->userid));
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
				$this->selecturl = bab_toHtml( $GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".urlencode($this->ord.$this->selectname)."&grp=".urlencode($this->grp)."&bupd=".urlencode($this->bupdate));
				$this->selected = 0;
				
				if( $this->pos == $this->selectname)
					$this->selected = 1;

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

function userCreate()
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

		function temp()
			{
			$this->firstnameval 	= bab_toHtml(bab_pp('firstname'));
			$this->middlenameval 	= bab_toHtml(bab_pp('middlename'));
			$this->lastnameval 		= bab_toHtml(bab_pp('lastname'));
			$this->nicknameval 		= bab_toHtml(bab_pp('nickname'));
			$this->emailval 		= bab_toHtml(bab_pp('email'));
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

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"users.html", "usercreate"));
	}


function utilit()
	{
	global $babBody;
	class temp
		{

		function temp()
			{
			global $babDB;
			$this->t_nb_total_users = bab_translate('Total users');
			$this->t_nb_unconfirmed_users = bab_translate('Unconfirmed users');
			$this->t_delete_unconfirmed = bab_translate('Delete unconfirmed users from');
			$this->t_days = bab_translate('Days');
			$this->t_ok = bab_translate('Ok');
			$this->js_alert = bab_translate('Day number must be 1 at least');
			
			list($this->arr['nb_total_users']) = $babDB->db_fetch_array($babDB->db_query("SELECT COUNT(*) FROM ".BAB_USERS_TBL.""));

			list($this->arr['nb_unconfirmed_users']) = $babDB->db_fetch_array($babDB->db_query("SELECT COUNT(*) FROM ".BAB_USERS_TBL." WHERE is_confirmed='0'"));
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"users.html", "utilit"));
	}
	
function delete_unconfirmed()
	{
	include $GLOBALS['babInstallPath']."utilit/delincl.php";
	global $babDB;
	$res = $babDB->db_query("SELECT id FROM ".BAB_USERS_TBL." WHERE is_confirmed='0' AND (DATE_ADD(datelog, INTERVAL ".$babDB->db_escape_string($_POST['nb_days'])." DAY) < NOW() OR datelog = '0000-00-00 00:00:00')");
	while (list($id) = $babDB->db_fetch_array($res))
		bab_deleteUser($id);
	}

function updateGroup( $grp, $users, $userst)
{

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



function dir_view($id_user)
{
global $babDB;

$arr = $babDB->db_fetch_array($babDB->db_query("SELECT dbt.id FROM ".BAB_DBDIR_ENTRIES_TBL." dbt WHERE '".$babDB->db_escape_string($id_user)."'=dbt.id_user and dbt.id_directory='0'"));

list($iddir) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group='1'"));

Header("Location: ". $GLOBALS['babUrlScript']."?tg=directory&idx=ddb&id=".$iddir."&idu=".$arr['id']."&pos=&xf=");

}


/* main */

$pos = bab_rp('pos','A');
$grp = bab_rp('grp');
$idx = bab_rp('idx');

if( !isset($grp) || empty($grp))
	{
	if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 )
		$grp = 3;
	else if( $babBody->currentAdmGroup != 0 )
		{
		$grp = $babBody->currentDGGroup['id_group'];
		}
	}



if( isset($adduser) && ($babBody->isSuperAdmin || $babBody->currentDGGroup['users'] == 'Y'))
{
	$iduser = registerUser( stripslashes($firstname), stripslashes($lastname), stripslashes($middlename), $email, $nickname, $password1, $password2, true);
	if( !$iduser)
		$idx = "Create";
	else
		{
		if( $babBody->currentAdmGroup != 0 && $babBody->currentDGGroup['users'] == 'Y')
			{
			bab_addUserToGroup($iduser, $babBody->currentDGGroup['id_group']);
			}

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
	$users = isset($_POST['users']) ? $_POST['users'] : array();
	updateGroup($_POST['grp'], $users, $_POST['userst']);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp."&bupd=".$_REQUEST['bupd']);
	exit;
}

if( $idx == "Create" && !$babBody->isSuperAdmin && $babBody->currentDGGroup['users'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}


switch($idx)
	{
	case "dirv": 

		 dir_view($_GET['id_user']);
		exit;
		break;

	case "brow": // Used by add-ons but deprecated
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

		userCreate();
		$babBody->addItemMenu("List", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=users&idx=Create&pos=".$pos);
		break;
	case "List":
		if( $babBody->isSuperAdmin || $babBody->currentAdmGroup != 0 )
			{
			$babBody->setTitle(bab_translate("Users list"));
			$cnt = listUsers($pos, $grp);

			$babBody->addItemMenu("List", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=users&idx=List");
			if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['users'] == 'Y')
				{
				$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=users&idx=Create&pos=".$pos."&grp=".$grp);
				}
			if( $babBody->currentAdmGroup != 0 && $babBody->currentDGGroup['battach'] == 'Y' && isset($_REQUEST['bupd']) && $_REQUEST['bupd'] == 0)
				{
				$babBody->addItemMenu("Attach", bab_translate("Attach"),$GLOBALS['babUrlScript']."?tg=users&idx=List&grp=".$grp."&bupd=1");
				}
			if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 )
				{
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
			$babBody->setTitle(bab_translate("Utilities"));
			utilit();
			}
		break;
	default:
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>
