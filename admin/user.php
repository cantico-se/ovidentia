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
include_once 'base.php';
require_once dirname(__FILE__).'/../utilit/registerglobals.php';
include_once $babInstallPath.'admin/register.php';




/**
 * Formats a date to an input format: 2009-12-31 => 31/12/2009
 *   
 * @param string	$isoDate		The iso-formatted date to format
 * 
 * @return string
 */
function formatInputDate($isoDate)
{
	if ($isoDate === '0000-00-00') {
		return '';
	}
	$ts = bab_mktime($isoDate . ' 00:00:00');
	$date = date('d-m-Y', $ts);
	return $date;
}


/* 
 * Display the form for modify options of a user
 * $userId : id of the user who must be modify
 * $pos : filter for the list of the users when you clic a letter (a letter (A, B...) or nothing (all letters))
 * $grp : filter for the list of the users when you attach a user in a group (id of a group or nothing)
 */
function modifyUser($userId, $pos, $grp)
{
	global $babBody;

	if (!isset($userId)) {
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid user !!");
		return;
	}
	
	class ModifyUser_Temp
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
		var $userId;
		var $showprimary;

		function ModifyUser_Temp($userId, $pos, $grp)
		{
			global $babBody, $babDB;

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

			$this->validity_period = bab_translate("Account validity period:");
			$this->from = bab_translate("From");
			$this->to = bab_translate("to");
			$this->site_option = bab_translate("Defined in site options");

			$req = 'SELECT * FROM ' . BAB_USERS_TBL . ' WHERE id=' . $babDB->quote($userId);
			$this->res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_assoc($this->res);

			$this->validity_start = formatInputDate($this->arr['validity_start']);
			$this->validity_end = formatInputDate($this->arr['validity_end']);
			$this->id = $userId;
			$this->pos = $pos;
			$this->grp = $grp;

			$this->bshowauthtype = false;

			if ($babBody->babsite['authentification'] != BAB_AUTHENTIFICATION_OVIDENTIA) {
				$this->bshowauthtype = true;
				$this->authentificationtxt = bab_translate("Which authentication method must be used for this user");
				$this->ovidentiaauthtxt = bab_translate("Ovidentia");
				$this->siteauthtxt = bab_translate("As defined in site configuration");
				if ($this->arr['db_authentification'] == 'Y') {
					$this->yselected = 'selected';
					$this->nselected = '';
				} else {
					$this->yselected = '';
					$this->nselected = 'selected';
				}
			}
	
			/* If the current user is admin of a delegation, he can't delete the user */
			if ($babBody->currentAdmGroup != 0) {
				$this->bdelete = false;
			} else {
				$this->bdelete = true;
			}

			$req = 'SELECT * FROM '  .BAB_USERS_GROUPS_TBL . ' WHERE id_object=' . $babDB->quote($userId);
			$this->res = $babDB->db_query($req);
		}


		function getNextGroup()
		{
			global $babDB;

			if ($this->arrgroups = $babDB->db_fetch_assoc($this->res)) {
				if( $this->arrgroups['isprimary'] == 'Y') {
					$this->selected = 'selected';
				} else {
					$this->selected = '';
				}
				$this->groupname = bab_getGroupName($this->arrgroups['id_group']);
				$this->groupid = $this->arrgroups['id_group'];
				return true;
			}
			return false;
		}

	}

	$temp = new ModifyUser_Temp($userId, $pos, $grp);
	$babBody->babEcho(bab_printTemplate($temp, 'users.html', 'usersmodify'));
}




function deleteUser($id)
{
	global $babBody, $BAB_SESS_USERID;

	if ($id == $BAB_SESS_USERID /* || bab_isUserAlreadyLogged($id) */) {
		$babBody->msgerror = bab_translate("Sorry, you cannot delete this user. He is already logged");
		return;
	}
	
	class DeleteUser_Temp
	{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;

		function DeleteUser_Temp($id)
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

	$temp = new DeleteUser_Temp($id);
	$babBody->babecho(	bab_printTemplate($temp, 'warning.html', 'warningyesno'));
}


/* 
 * Display the form for modify nickname of a user
 * $item : id of the user who must be modify
 * $pos : filter for the list of the users when you clic a letter (a letter (A, B...) or nothing (all letters))
 * $grp : filter for the list of the users when you attach a user in a group (id of a group or nothing)
 */
function changeNickname($item, $pos, $grp)
{
	global $babBody,$BAB_SESS_USERID;

	class changeNicknameCls
	{
		var $newnickname;
		var $nicknameval;
		var $update;
		var $item;
		var $pos;
		var $grp;

		function changeNicknameCls($item, $pos, $grp)
		{
			global $babDB;
			$this->item = $item;
			$this->pos = $pos;
			$this->grp = $grp;
			$this->newnickname = bab_translate("Login ID");
			$this->update = bab_translate("Update");
			list($this->nicknameval) = $babDB->db_fetch_row($babDB->db_query("select nickname from ".BAB_USERS_TBL." where id='".$item."'"));
		}
	}

	$tempb = new changeNicknameCls($item, $pos, $grp);
	$babBody->babecho(bab_printTemplate($tempb, 'users.html', 'changenickname'));
}



/* 
 * Display the form for modify password of a user
 * $userId : id of the user who must be modify
 * $pos : filter for the list of the users when you clic a letter (a letter (A, B...) or nothing (all letters))
 * $grp : filter for the list of the users when you attach a user in a group (id of a group or nothing)
 */
function changePassword($userId, $pos, $grp)
{
	global $babBody;

	class changePasswordCls
	{
		var $newpwd;
		var $renewpwd;
		var $update;
		var $userId;
		var $pos;
		var $grp;

		function changePasswordCls($userId, $pos, $grp)
		{
			global $babBody, $babDB;

			$sql = 'SELECT db_authentification FROM ' . BAB_USERS_TBL . ' WHERE id=' . $babDB->quote($userId);
			$res = $babDB->db_query($sql);
			$arruser = $babDB->db_fetch_assoc($res);

			$authentication = $babBody->babsite['authentification'];
			if ($arruser['db_authentification'] == 'Y') {
				$authentication = ''; // force to default
			}

			switch ($authentication)
			{
				case BAB_AUTHENTIFICATION_AD:
					$this->bshowform = false;
					break;

				case BAB_AUTHENTIFICATION_LDAP:
					if (empty($babBody->babsite['ldap_encryptiontype'])) {
						$this->bshowform = false;
					} else {
						$this->bshowform = true;
					}
					break;

				default:
					$this->bshowform = true;
					break;
			}

			$this->item = $userId;
			$this->pos = $pos;
			$this->grp = $grp;
			$this->newpwd = bab_translate("New Password");
			$this->renewpwd = bab_translate("Retype New Password");
			$this->update = bab_translate("Update");
			$this->tsendconfirmationemail = bab_translate("Send an e-mail to the user with its new password");
			$this->tyes = bab_translate("Yes");
			$this->tno = bab_translate("No");
		}
	}

	$tempb = new changePasswordCls($userId, $pos, $grp);
	$babBody->babEcho(bab_printTemplate($tempb, 'users.html', 'changepassword'));
}
	
	


function viewgroups()
{
	global $babBody;

	class ViewGroups_Temp
	{		
		var $altbg = true;

		function ViewGroups_Temp()
		{
			global $babDB;

			$this->t_name = bab_translate("Name");
			$this->t_description = bab_translate("Description");

			$id_user = (int) bab_rp('id_user');

			$req = '
				SELECT 
					g.name,
					g.description  
				FROM 
					'.BAB_USERS_GROUPS_TBL.' u, 
					'.BAB_GROUPS_TBL.' g 
				WHERE 
					u.id_object=' . $babDB->quote($id_user).'
					AND g.id=u.id_group 
				ORDER BY g.name
			';
//			bab_debug($req);
			$this->res = $babDB->db_query($req);
		}

		function getnextgroup()
		{
			global $babDB;
	
			if ($arr = $babDB->db_fetch_assoc($this->res)) {
				$this->altbg = !$this->altbg;
				$this->name = bab_toHtml($arr['name']);
				$this->description = bab_toHtml($arr['description']);
				return true;
			}

			return false;
		}

	}
	
	$tempb = new ViewGroups_Temp();
	$html = bab_printTemplate($tempb, 'users.html', 'viewgroups');
	if (false === bab_rp('popup', false)) {
		$babBody->babecho($html);
	} else {
		$babBody->babpopup($html);
	}

}
	
	

function notifyUserconfirmation($name, $email)
{
	global $babBody, $babAdminEmail, $babInstallPath;

	class NotifyUserconfirmation_Temp
	{
        var $sitename;
        var $linkurl;
		var $username;
		var $message;


		function NotifyUserconfirmation_Temp($name, $msg)
		{
            global $babSiteName;
            $this->linkurl = $GLOBALS['babUrl'].'?tg=login';
            $this->username = $name;
			$this->sitename = $babSiteName;
			$this->message = $msg;
		}
	}
	
	$mail = bab_mail();
	if ($mail == false) {
		return;
	}

	$mail->mailTo($email, $name);
	$mail->mailFrom($babAdminEmail, $GLOBALS['babAdminName']);
	$mail->mailSubject(bab_translate("Registration Confirmation"));
	

	$message = bab_translate("Thank You For Registering at our site");
	$message .= "<br>". bab_translate("Your registration has been confirmed.");
	$message .= "<br>". bab_translate("To connect on our site").", ". bab_translate("go to this url").": ";
	$tempa = new NotifyUserconfirmation_Temp($name, $message);
	$message = $mail->mailTemplate(bab_printTemplate($tempa, 'mailinfo.html', 'userconfirmation'));
	$mail->mailBody($message, "html");


	$message = bab_translate("Thank You For Registering at our site") ."\n";
	$message .= bab_translate("Your registration has been confirmed.")."\n";
	$message .= bab_translate("To connect on our site").", ". bab_translate("go to this url").": ";
	$tempa = new NotifyUserconfirmation_Temp($name, $message);
	$message = $mail->mailTemplate(bab_printTemplate($tempa, 'mailinfo.html', 'userconfirmationtxt'));
	$mail->mailAltBody($message);

	$mail->send();
}



/**
 * 
 * @param int	$userId		The user id
 */
function updateGroups($userId)
{
	include_once $GLOBALS['babInstallPath'] . 'admin/mgroup.php';

	$id_user = $_POST['item'];
	if (!bab_canCurrentUserUpdateUser($userId)) {
		return;
	}

	$selected_groups = mgroups_getSelected();
	$arr = bab_getUserGroups($userId);
	$user_groups = &$arr['id'];


	if (isset($user_groups)) {
		foreach($user_groups as $id_group) {
			if (!in_array($id_group, $selected_groups)) {
				bab_removeUserFromGroup($userId, $id_group);
			}
		}
	}

	foreach($selected_groups as $id_group) {
		if (!in_array($id_group, $user_groups)) {
			bab_addUserToGroup($userId, $id_group);
		}
	}
}



/**
 * Updates the user information.
 * 
 * @param int		$userId				The user id
 * @param int		$changepwd			1 => User is allowed to change password, 0 => User cannot change her password
 * @param int		$isConfirmed		1 => User is confirmed, 0 => User is not confirmed
 * @param int		$disabled
 * @param string	$validityStart		ISO formatted date
 * @param string	$validityEnd		ISO formatted date
 * @param string	$authtype
 * @param int		$primaryGroupId		The user primary group
 */
function updateUser($userId, $changepwd, $isConfirmed, $disabled, $validityStart, $validityEnd, $authtype, $primaryGroupId)
{
	require_once $GLOBALS['babInstallPath'] . '/utilit/dateTime.php';
	global $babBody, $babDB;

	if (!bab_canCurrentUserUpdateUser($userId)) {
		return;
	}

	$hasError = false;

	$sql = 'SELECT firstname, lastname, email, is_confirmed FROM ' . BAB_USERS_TBL . ' WHERE id=' . $babDB->quote($userId);
	$res = $babDB->db_query($sql);
	if ($res) {
		$user = $babDB->db_fetch_assoc($res);
	}

	if (!empty($validityStart)) {
		$validityStartDateTime = bab_DateTime::fromUserInput($validityStart);
		if (is_null($validityStartDateTime)) {
			$babBody->addError(bab_translate("Invalid validity start date"));
			$hasError = true;
		} else {
			$validityStart = $validityStartDateTime->getIsoDate();
		}
	} else {
		$validityStart = '0000-00-00';
	}

	if (!empty($validityEnd)) {
		$validityEndDateTime = bab_DateTime::fromUserInput($validityEnd);
		if (is_null($validityEndDateTime)) {
			$babBody->addError(bab_translate("Invalid validity end date"));
			$hasError = true;
		} else {
			$validityEnd = $validityEndDateTime->getIsoDate();
		}
	} else {
		$validityEnd = '0000-00-00';
	}

	if ($hasError) {
		return;
	}

	$sql = 'UPDATE ' . BAB_USERS_TBL . '
				SET changepwd=' . $babDB->quote($changepwd) . ',
					is_confirmed=' . $babDB->quote($isConfirmed) . ',
					disabled=' . $babDB->quote($disabled) . ',
					validity_start=' . $babDB->quote($validityStart) . ',
					validity_end=' . $babDB->quote($validityEnd);
	if (!empty($authtype)) {
		$sql .= ', db_authentification=' . $babDB->quote($authtype);
	}
	$sql .= ' WHERE id=' . $babDB->quote($userId);
	$res = $babDB->db_query($sql);

	$sql = 'UPDATE ' . BAB_USERS_GROUPS_TBL . ' SET isprimary=\'N\' WHERE id_object=' . $babDB->quote($userId);
	$babDB->db_query($sql);

	// Update the user's primary group.
	if (!empty($primaryGroupId)) {
		$sql = 'UPDATE ' . BAB_USERS_GROUPS_TBL . ' SET isprimary=\'Y\' WHERE id_object=' . $babDB->quote($userId) . ' AND id_group=' . $babDB->quote($primaryGroupId);
		$babDB->db_query($sql);
	}


	if ($isConfirmed == 1 && $user['is_confirmed'] == 0) {
		if ($babBody->babsite['idgroup'] != 0) {
			bab_addUserToGroup($userId, $babBody->babsite['idgroup']);
		}
		notifyUserconfirmation(bab_composeUserName($user['firstname'] , $user['lastname']), $user['email']);
	}

	$pos = bab_rp('pos', 'A');
	$grp = bab_rp('grp', '');
	header('Location: ' . $GLOBALS['babUrlScript'] . '?tg=users&idx=List&pos=' . $pos . '&grp=' . $grp);
}




function confirmDeleteUser($userId)
{
	global $babBody;

	if (!bab_canCurrentUserUpdateUser($userId)) {
		return;
	}

	if ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) {
		include_once $GLOBALS['babInstallPath'] . 'utilit/delincl.php';
		bab_deleteUser($userId);
	}
	header('Location: ' . $GLOBALS['babUrlScript'] . '?tg=users&idx=List');
}



/**
 * Updates the specified user's nickname and add error in babBody
 * 
 * @param int		$userId			The user id		
 * @param string	$newNickname	The new user nickname
 * 
 * @return bool		true on success, false on error
 */
function updateNickname($userId, $newNickname)
{
	
	$error = '';
	$res = bab_updateUserNicknameById($userId, $newNickname, false, $error);
	
	if (!$res) {
		global $babBody;
		$babBody->msgerror = $error;
	}
	
	return $res;
}



/**
 * Updates the specified user's password.
 * 
 * @param int		$userId		The user id		
 * @param string	$newpwd1	The new password
 * @param string	$newpwd2	The new password (again)
 * 
 * @return bool
 */
function updatePassword($userId, $newpwd1, $newpwd2)
{
	$error = '';
	$res = updateUserPasswordById($userId, $newpwd1, $newpwd2, false, false, $error);
	
	if (!$res) {
		global $babBody;
		$babBody->msgerror = $error;
	}
	
	return $res;	
}


/* main */

if (!$babBody->isSuperAdmin && $babBody->currentDGGroup['users'] != 'Y' && $babBody->currentAdmGroup == 0)
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

$idx = bab_rp('idx','Modify');
$pos = bab_rp('pos');
$grp = bab_rp('grp');
$item = bab_rp('item', null);

$modify = bab_rp('modify', null);
$bupdate = bab_rp('bupdate', null);
$bdelete = bab_rp('bdelete', null);

if ((!$babBody->isSuperAdmin && $babBody->currentDGGroup['users'] != 'Y') && $babBody->currentAdmGroup != 0)
{//deleged admin only have access to the group view.
	$idx = bab_rp('idx','viewgroups');
	$pos = bab_rp('pos');
	$grp = bab_rp('grp');
	$item = null;
	
	$modify = null;
	$bupdate = null;
	$bdelete = null;
}

if (isset($modify)) {
	if (isset($bupdate)) {
		$changepwd = bab_rp('changepwd');
		$isConfirmed = bab_rp('is_confirmed');
		$disabled = bab_rp('disabled');
		$authType = bab_rp('authtype');
		$group = bab_rp('group');
		$validityStart = bab_rp('validity_start');
		$validityEnd = bab_rp('validity_end');
		updateUser($item, $changepwd, $isConfirmed, $disabled, $validityStart, $validityEnd, $authType, $group);
	} else if(isset($bdelete)) {
		$idx = 'Delete';
	}
}

if (isset($update) && $update == 'password') {
	if(!updatePassword($item, $newpwd1, $newpwd2)) {
		$idx = 'Modify';
	} else {
		/* Send an e-mail to the user with its new password */
		$vSendConfirmationEmail = bab_rp('sendconfirmationemail', 'no');
		if ($vSendConfirmationEmail == 'yes') {
			global $babInstallPath;
			include_once $babInstallPath.'utilit/mailincl.php';
			
			$mail = bab_mail();
			if ($mail !== false) {
				global $babBody, $babAdminEmail, $babAdminName, $babInstallPath, $babSiteName;
				
				$userName = bab_getUserName($item);
				$userEmail = bab_getUserEmail($item);
				list($nickname) = $babDB->db_fetch_row($babDB->db_query("select nickname from ".BAB_USERS_TBL." where id='".$item."'"));
				$newPassword = $newpwd1;
				
				$mail->mailTo($userEmail, $userName);
				$mail->mailFrom($babAdminEmail, $babAdminName);
				
				$subject = bab_translate('Your password of connexion in the site').' '.$babSiteName.' '.bab_translate('was changed');
				$mail->mailSubject($subject);
				
				$HTMLmessage = $userName.', '.bab_translate('your password of connexion in the site').' '.$babSiteName.' '.bab_translate('was changed').'.<br />';
				$HTMLmessage .= '<br />';
				$HTMLmessage .= bab_translate('Your nickname').' : '.$nickname.'<br />';
				$HTMLmessage .= bab_translate('Your new password').' : '.$newPassword.'<br /><br />';
				$HTMLmessage .= bab_translate('Access to the site').' : <a href="'.$GLOBALS['babUrl'].'">'.$GLOBALS['babUrl'];
				$mail->mailBody($mail->mailTemplate($HTMLmessage));
				
				$TEXTmessage = $userName.', '.bab_translate('your password of connexion in the site').' '.$babSiteName.' '.bab_translate('was changed').".\n";
				$TEXTmessage .= "\n";
				$TEXTmessage .= bab_translate('Your nickname').' : '.$nickname."\n";
				$TEXTmessage .= bab_translate('Your new password').' : '.$newPassword."\n\n";
				$TEXTmessage .= bab_translate('Access to the site').' : <a href="'.$GLOBALS['babUrl'].'">'.$GLOBALS['babUrl'];
				$mail->mailAltBody($TEXTmessage);
				
				$mail->send();
			}
		}		
		
		/* Return to the list of the users */
		header('Location: '.$GLOBALS['babUrlScript'].'?tg=users&idx=List&pos='.$pos.'&grp='.$grp);
		return;
	}
}

if (isset($update) && $update == 'nickname') {
	if(!updateNickname($item, $newnick)) {
		$idx = 'Modify';
	} else {
		header('Location: ' . $GLOBALS['babUrlScript'] . '?tg=users&idx=List&pos=' . $pos . '&grp=' . $grp);
		return;
	}
}

if (isset($action) && $action == 'Yes') {
	confirmDeleteUser($user);
}

if (isset($_POST['action'])) {
	switch ($_POST['action']) {
		case 'updategroups':
			updateGroups(bab_pp('item'));
			break;
	}
}

switch($idx) {

	case 'Delete':

		$babBody->title = bab_translate("Delete a user");
		deleteUser($item);
		$babBody->addItemMenu('List', bab_translate("Users"), $GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu('Modify', bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=user&idx=Modify&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu('Groups', bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=user&idx=Groups&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu('Delete', bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=user&idx=Delete&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu('unav', bab_translate("Unavailability"), $GLOBALS['babUrlScript']."?tg=options&idx=unav&iduser=".$item);
		break;



	case 'Groups':

		$babBody->title = bab_getUserName($item) . bab_translate(" is member of");

		include_once $babInstallPath.'admin/mgroup.php';

		$mgroups = new mgroups('user','Groups', BAB_REGISTERED_GROUP);
		$mgroups->setExpandChecked();
		$mgroups->setField('action', 'updategroups');
		$mgroups->setField('item', $_REQUEST['item']);
		$pos = isset($_REQUEST['pos']) ? $_REQUEST['pos'] : 0;
		$grp = isset($_REQUEST['grp']) ? $_REQUEST['grp'] : 0;
		$mgroups->setField('pos', $pos);
		$mgroups->setField('grp', $grp);
		$mgroups->setGroupOption(BAB_REGISTERED_GROUP,'disabled',true);
		$arr = bab_getUserGroups($_REQUEST['item']);
		if (isset($arr['id'])) {
			$mgroups->setGroupsOptions($arr['id'], 'checked', true);
		}
		if ($babBody->currentAdmGroup != 0) {
			$mgroups->setGroupOption($babBody->currentDGGroup['id_group'], 'disabled', true);
		}
		$mgroups->babecho();

		$babBody->addItemMenu('List', bab_translate("Users"), $GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu('Modify', bab_translate("User"), $GLOBALS['babUrlScript']."?tg=user&idx=Modify&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu('Groups', bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=user&idx=Groups&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu('unav', bab_translate("Unavailability"), $GLOBALS['babUrlScript']."?tg=options&idx=unav&iduser=".$item);	
		
		break;



	case 'viewgroups':

		if (false === bab_rp('popup', false)) {
			$babBody->addItemMenu('List', bab_translate("Users"), $GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
			$babBody->addItemMenu('viewgroups', bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=users&idx=viewgroups&pos=".$pos."&grp=".$grp);
		}
		
		$babBody->setTitle(bab_translate("The user's groups"));
		viewgroups();
		
		break;



	case 'Modify':
		/* $item : contains id of the user who must be modified */
		if (is_numeric($item)) {
			
			if (!bab_canCurrentUserUpdateUser($item))
			{
				$babBody->addError(bab_translate('Access denied'));
				break;
			}
			
			
			$babBody->title = bab_getUserName($item);
			/* $pos : filter for the list of the users when you clic a letter (a letter (A, B...) or nothing (all letters))
			 * $grp : filter for the list of the users when you attach a user in a group (id of a group or nothing)
			 */
			modifyUser($item, $pos, $grp);
			changeNickname($item, $pos, $grp);
			changePassword($item, $pos, $grp);
			$babBody->addItemMenu('List', bab_translate("Users"), $GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
			$babBody->addItemMenu('Modify', bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=user&idx=Modify&item=".$item."&pos=".$pos."&grp=".$grp);
			$babBody->addItemMenu('Groups', bab_translate("Groups"), $GLOBALS['babUrlScript']."?tg=user&idx=Groups&item=".$item."&pos=".$pos."&grp=".$grp);
			$babBody->addItemMenu('unav', bab_translate("Unavailability"), $GLOBALS['babUrlScript']."?tg=options&idx=unav&iduser=".$item);
		}
		break;


	default:
		break;
	}

$babBody->setCurrentItemMenu($idx);
