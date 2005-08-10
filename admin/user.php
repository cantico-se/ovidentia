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


function modifyUser($id, $pos, $grp)
	{
	global $babBody;
	if( !isset($id))
		{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid user !!");
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
		var $showprimary;

		function temp($id, $pos, $grp)
			{
			$this->showprimary = false;
			$this->changepassword = bab_translate("Can user change password ?");
			$this->isconfirmed = bab_translate("Account confirmed ?");
			$this->isdisabled = bab_translate("Account disabled ?");
			$this->primarygroup = bab_translate("Primary group");
			$this->none = bab_translate("None");
			$this->modify = bab_translate("Modify");
			$this->delete = bab_translate("Delete");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_USERS_TBL." where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->id = $id;
			$this->pos = $pos;
			$this->grp = $grp;

			$req = "select * from ".BAB_USERS_GROUPS_TBL." where id_object='$id'";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;	
			if( $i < $this->count)
				{
				$this->arrgroups = $this->db->db_fetch_array($this->res);
				if( $this->arrgroups['isprimary'] == "Y")
					$this->selected = "selected";
				else
					$this->selected = "";
				$this->groupname = bab_getGroupName($this->arrgroups['id_group']);
				$this->groupid = $this->arrgroups['id_group'];
				$i++;
				return true;
				}
			return false;
			}
		}

	$temp = new temp($id, $pos, $grp);
	$babBody->babecho(	bab_printTemplate($temp, "users.html", "usersmodify"));
	}


function deleteUser($id)
	{
	global $babBody, $BAB_SESS_USERID;

	if( $id == $BAB_SESS_USERID /* || bab_isUserAlreadyLogged($id) */)
		{
		$babBody->msgerror = bab_translate("Sorry, you cannot delete this user. He is already logged");
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
			$this->message = bab_translate("Are you sure you want to delete this user");
			$this->title = bab_getUserName($id);
			$this->warning = bab_translate("WARNING: This operation will delete the user and all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=user&idx=Delete&user=".$id."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=user&idx=Modify&item=".$id;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function changePassword($item, $pos, $grp)
	{
	global $babBody,$BAB_SESS_USERID;
	class tempb
		{
		var $newpwd;
		var $renewpwd;
		var $newnickname;
		var $nicknameval;
		var $update;
		var $item;
		var $pos;
		var $grp;

		function tempb($item, $pos, $grp)
			{
			global $babDB;
			$this->item = $item;
			$this->pos = $pos;
			$this->grp = $grp;
			$this->newnickname = bab_translate("Nickname");
			$this->newpwd = bab_translate("New Password");
			$this->renewpwd = bab_translate("Retype New Password");
			$this->update = bab_translate("Update");
			list($this->nicknameval) = $babDB->db_fetch_row($babDB->db_query("select nickname from ".BAB_USERS_TBL." where id='".$item."'"));
			}
		}

	$tempb = new tempb($item, $pos, $grp);
	$babBody->babecho(	bab_printTemplate($tempb,"users.html", "changepassword"));

	}

function notifyUserconfirmation($name, $email)
	{
	global $babBody, $babAdminEmail, $babInstallPath;

	class tempa
		{
        var $sitename;
        var $linkurl;
		var $username;
		var $message;


		function tempa($name, $msg)
			{
            global $babSiteName;
            $this->linkurl = $GLOBALS['babUrl'];
            $this->username = $name;
			$this->sitename = $babSiteName;
			$this->message = $msg;
			}
		}
	
	$mail = bab_mail();
	if( $mail == false )
		return;

	$mail->mailTo($email, $name);
    $mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
    $mail->mailSubject(bab_translate("Registration Confirmation"));
	

	$message = bab_translate("Thank You For Registering at our site");
	$message .= "<br>". bab_translate("Your registration has been confirmed.");
	$message .= "<br>". bab_translate("To connect on our site").", ". bab_translate("simply follow this").": ";
	$tempa = new tempa($name, $message);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "userconfirmation"));
    $mail->mailBody($message, "html");

	$message = bab_translate("Thank You For Registering at our site") ."\n";
	$message .= bab_translate("Your registration has been confirmed.")."\n";
	$message .= bab_translate("To connect on our site").", ". bab_translate("go to this url").": ";
	$tempa = new tempa($name, $message);
	$message = $mail->mailTemplate(bab_printTemplate($tempa,"mailinfo.html", "userconfirmationtxt"));
    $mail->mailAltBody($message);

	$mail->send();
	}

function updateGroups()
	{
	include_once $GLOBALS['babInstallPath']."admin/mgroup.php";

	$id_user = $_POST['item'];
	$selected_groups = mgroups_getSelected();
	$arr = bab_getUserGroups($_REQUEST['item']);
	$user_groups = &$arr['id'];


	if (isset($user_groups))
		foreach($user_groups as $id_group)
			{
			if (!in_array($id_group, $selected_groups))
				{
				bab_removeUserFromGroup($id_user, $id_group);
				}
			}

	foreach($selected_groups as $id_group)
		{
		if (!in_array($id_group, $user_groups))
			{
			bab_addUserToGroup($id_user, $id_group);
			}
		}
	}



function updateUser($id, $changepwd, $is_confirmed, $disabled, $group)
	{
	global $babBody;

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select firstname, lastname, email, is_confirmed from ".BAB_USERS_TBL." where id='$id'");
	if( $res )
		{
		$r = $db->db_fetch_array($res);
		}
	$res = $db->db_query("update ".BAB_USERS_TBL." set changepwd='$changepwd', is_confirmed='$is_confirmed', disabled='$disabled' where id='$id'");

	$db = $GLOBALS['babDB'];
	$db->db_query("update ".BAB_USERS_GROUPS_TBL." set isprimary='N' where id_object='$id'");
	if( !empty($group))
		{
		$db->db_query("update ".BAB_USERS_GROUPS_TBL." set isprimary='Y' where id_object='$id' and id_group='$group'");
		}

	if( $is_confirmed == 1 && $r['is_confirmed'] == 0 )
		{
		if( $babBody->babsite['idgroup'] != 0)
			{
			bab_addUserToGroup( $id, $babBody->babsite['idgroup']);
			}
		notifyUserconfirmation( bab_composeUserName($r['firstname'] , $r['lastname']), $r['email']);
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=users&idx=List");
	}

function confirmDeleteUser($id)
	{
	include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
	bab_deleteUser($id);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=users&idx=List");
	}

function updatePassword($item, $newpwd1, $newpwd2, $newnick)
	{
	global $babBody, $BAB_HASH_VAR;

	if( (!empty($newpwd1) || !empty($newpwd2)) && $newpwd1 != $newpwd2)
		{
		$babBody->msgerror = bab_translate("Passwords not match !!");
		return false;
		}
	
	if ( !empty($newpwd1) && strlen($newpwd1) < 6 )
		{
		$babBody->msgerror = bab_translate("Password must be at least 6 characters !!");
		return false;
		}

	if ( !empty($newnick) && strpos($newnick, ' ') !== false )
		{
		$babBody->msgerror = bab_translate("Nickname contains blanc characters");
		return false;
		}

	$db = $GLOBALS['babDB'];
	if( !empty($newnick))
		{
		$query = "select * from ".BAB_USERS_TBL." where nickname='".$newnick."' and id!='".$item."'";	
		$res = $db->db_query($query);
		if( $db->db_num_rows($res) > 0)
			{
			$babBody->msgerror = bab_translate("This nickname already exists !!");
			return false;
			}

		$hash=md5($newnick.$BAB_HASH_VAR);
		$req="update ".BAB_USERS_TBL." set confirm_hash='".$hash."', nickname='".$newnick."'".
			"where id='". $item . "'";
		$res = $db->db_query($req);
		}

	if( (!empty($newpwd1) && !empty($newpwd2)) && $newpwd1 == $newpwd2)
		{
		$req="update ".BAB_USERS_TBL." set password='". md5(strtolower($newpwd1)). "' where id='". $item . "'";
		$res = $db->db_query($req);
		}

	return true;
	}

/* main */
if( !$babBody->isSuperAdmin )
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( !isset($idx))
	$idx = "Modify";

if( isset($modify))
	{
	if(isset($bupdate))
		{
		$group = isset($group) ? $group : '';
		updateUser($item, $changepwd, $is_confirmed, $disabled, $group);
		}
	else if(isset($bdelete))
		$idx = "Delete";
	}

if( isset($update) && $update == "password")
	{
	if(!updatePassword($item, $newpwd1, $newpwd2, $newnick))
		$idx = "Modify";
	else
		{
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
		return;
		}
	}

if( isset($action) && $action == "Yes")
	{
	confirmDeleteUser($user);
	}

if( isset($_POST['action']))
	switch($_POST['action'])
		{
			case 'updategroups':
			updateGroups();
			break;
		}

switch($idx)
	{
	case "Delete":
		if( !isset($pos)) {$pos='';}
		if( !isset($grp)) {$grp='';}
		$babBody->title = bab_translate("Delete a user");
		deleteUser($item);
		$babBody->addItemMenu("List", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=user&idx=Modify&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Groups", bab_translate("Groups"),$GLOBALS['babUrlScript']."?tg=user&idx=Groups&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Delete", bab_translate("Delete"),$GLOBALS['babUrlScript']."?tg=user&idx=Delete&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("unav", bab_translate("Unavailability"), $GLOBALS['babUrlScript']."?tg=options&idx=unav&iduser=".$item );
		break;

	case "Groups":
		$babBody->title = bab_getUserName($item) . bab_translate(" is member of");

		include_once $babInstallPath."admin/mgroup.php";

		$mgroups = new mgroups('user','Groups',BAB_REGISTERED_GROUP);
		$mgroups->setField('action', 'updategroups');
		$mgroups->setField('item', $_REQUEST['item']);
		$mgroups->setField('pos', $_REQUEST['pos']);
		$mgroups->setField('grp', $_REQUEST['grp']);
		$mgroups->setGroupOption(BAB_REGISTERED_GROUP,'disabled',true);
		$arr = bab_getUserGroups($_REQUEST['item']);
		if (isset($arr['id']))
			$mgroups->setGroupsOptions($arr['id'],'checked',true);
		$mgroups->babecho();

		$babBody->addItemMenu("List", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Modify", bab_translate("User"),$GLOBALS['babUrlScript']."?tg=user&idx=Modify&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Groups", bab_translate("Groups"),$GLOBALS['babUrlScript']."?tg=user&idx=Groups&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("unav", bab_translate("Unavailability"), $GLOBALS['babUrlScript']."?tg=options&idx=unav&iduser=".$item );	
		
		break;

	case "Modify":
		$babBody->title = bab_getUserName($item);
		if( !isset($pos)) {$pos='';}
		if( !isset($grp)) {$grp='';}
		modifyUser($item, $pos, $grp);
		changePassword($item, $pos, $grp);
		$babBody->addItemMenu("List", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=user&idx=Modify&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Groups", bab_translate("Groups"),$GLOBALS['babUrlScript']."?tg=user&idx=Groups&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("unav", bab_translate("Unavailability"), $GLOBALS['babUrlScript']."?tg=options&idx=unav&iduser=".$item );
		break;
	default:
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>
