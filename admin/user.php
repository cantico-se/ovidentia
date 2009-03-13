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


function canUpdateUser($user)
{
	global $babBody;
	include_once $GLOBALS['babInstallPath'].'utilit/delegincl.php';
	
	if( $babBody->currentAdmGroup )
		{
		$dg = $babBody->currentAdmGroup;
		}
	elseif( $babBody->isSuperAdmin )
		{
		$dg = 0;
		}

	if( !isset($dg))
		{
		return false;
		}

	$delegations = bab_getUserVisiblesDelegations($user);
	foreach($delegations as $delegation => $arr) 
		{
			if( $arr['id'] == $dg )
			{
				return true;
			}
		}
	return false;
}

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
			global $babBody;
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


			$this->bshowauthtype = false;
			if( $babBody->babsite['authentification'] != BAB_AUTHENTIFICATION_OVIDENTIA )
				{
				$this->bshowauthtype = true;
				$this->authentificationtxt = bab_translate("For is this user which method of authentification necessary must be used");
				$this->ovidentiaauthtxt = bab_translate("Ovidentia");
				$this->siteauthtxt = bab_translate("As defined in site configuration");
				if( $this->arr['db_authentification'] == 'Y')
					{
					$this->yselected = 'selected';
					$this->nselected = '';
					}
				else
					{
					$this->yselected = '';
					$this->nselected = 'selected';
					}
				}


			if ( $babBody->currentAdmGroup != 0 )
				{
				$this->bdelete = false;
				}
			else
				{
				$this->bdelete = true;
				}


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
	$babBody->babecho(	bab_printTemplate($tempb,"users.html", "changenickname"));
	}

function changePassword($item, $pos, $grp)
	{
	global $babBody,$BAB_SESS_USERID;
	class changePasswordCls
		{
		var $newpwd;
		var $renewpwd;
		var $update;
		var $item;
		var $pos;
		var $grp;

		function changePasswordCls($item, $pos, $grp)
			{
			global $babBody, $babDB;

			$res=$babDB->db_query("select db_authentification from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($item)."'");
			$arruser = $babDB->db_fetch_array($res);

			$authentification = $babBody->babsite['authentification'];
			if( $arruser['db_authentification'] == 'Y' )
				{
				$authentification = ''; // force to default
				}

			switch( $authentification )
				{
				case BAB_AUTHENTIFICATION_AD:
					$this->bshowform = false;
					break;
				case BAB_AUTHENTIFICATION_LDAP:
					if( empty($babBody->babsite['ldap_encryptiontype']) )
						{
						$this->bshowform = false;
						}
					else
						{
						$this->bshowform = true;
						}
					break;
				default:
					$this->bshowform = true;
					break;
				}

			$this->item = $item;
			$this->pos = $pos;
			$this->grp = $grp;
			$this->newpwd = bab_translate("New Password");
			$this->renewpwd = bab_translate("Retype New Password");
			$this->update = bab_translate("Update");
			}
		}

	$tempb = new changePasswordCls($item, $pos, $grp);
	$babBody->babecho(	bab_printTemplate($tempb,"users.html", "changepassword"));
	}
	
	
	
function viewgroups() {
	
		global $babBody,$BAB_SESS_USERID;
		class temp
			{
			
			var $altbg = true;

			function temp()
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
						u.id_object='.$babDB->quote($id_user).'
						AND g.id=u.id_group 
					ORDER BY g.name
				';
				bab_debug($req);
				$this->res = $babDB->db_query($req);
				}
				
			function getnextgroup() {
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
	
		$tempb = new temp();
		$html = bab_printTemplate($tempb,"users.html", "viewgroups");
		if (false === bab_rp('popup', false)) {
			$babBody->babecho($html);
		} else {
			$babBody->babpopup($html);
		}
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
            $this->linkurl = $GLOBALS['babUrl'].'?tg=login';
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
	$message .= "<br>". bab_translate("To connect on our site").", ". bab_translate("go to this url").": ";
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
	if(!canUpdateUser($id_user))
		{
		return;
		}
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



function updateUser($id, $changepwd, $is_confirmed, $disabled, $authtype, $group)
	{
	global $babBody;
	if(!canUpdateUser($id))
	{
	return;
	}

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select firstname, lastname, email, is_confirmed from ".BAB_USERS_TBL." where id='$id'");
	if( $res )
		{
		$r = $db->db_fetch_array($res);
		}

	$req = "update ".BAB_USERS_TBL." set changepwd='$changepwd', is_confirmed='$is_confirmed', disabled='$disabled'";
	if( !empty($authtype))
		{
		$req .= ", db_authentification='$authtype'";
		}
	$req .= " where id='$id'";
	$res = $db->db_query($req);

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
	$pos = bab_rp('pos', 'A');
	$grp = bab_rp('grp', '');
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
	}

function confirmDeleteUser($id)
	{
	global $babBody;
	if(!canUpdateUser($id))
	{
	return;
	}
	
	if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 )
		{
		include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
		bab_deleteUser($id);
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=users&idx=List");
	}

function updateNickname($item, $newnick)
	{
	global $babBody, $BAB_HASH_VAR;
	if(!canUpdateUser($item))
	{
	return;
	}

	if ( !empty($newnick) && mb_strpos($newnick, ' ') !== false )
		{
		$babBody->msgerror = bab_translate("Login ID should not contain spaces");
		return false;
		}

	$db = $GLOBALS['babDB'];
	if( !empty($newnick))
		{
		$query = "select * from ".BAB_USERS_TBL." where nickname='".$newnick."' and id!='".$item."'";	
		$res = $db->db_query($query);
		if( $db->db_num_rows($res) > 0)
			{
			$babBody->msgerror = bab_translate("This login ID already exists !!");
			return false;
			}

		$hash=md5($newnick.$BAB_HASH_VAR);
		$req="update ".BAB_USERS_TBL." set confirm_hash='".$hash."', nickname='".$newnick."'".
			"where id='". $item . "'";
		$res = $db->db_query($req);
			
		require_once $GLOBALS['babInstallPath'] . 'utilit/eventdirectory.php';
		$iIdUser = (int) $item;
		$oEvent = new bab_eventUserModified($iIdUser);
		bab_fireEvent($oEvent);
		}

	return true;
	}

function updatePassword($item, $newpwd1, $newpwd2)
	{
	global $babBody, $babDB, $BAB_HASH_VAR;
	if(!canUpdateUser($item))
	{
	return;
	}

	$newpwd1 = trim($newpwd1);
	$newpwd2 = trim($newpwd2);

	if ( empty($newpwd1) && empty($newpwd2) )
		{
		$babBody->msgerror = bab_translate("Password must be at least 6 characters !!");
		return false;
		}

	if( (!empty($newpwd1) || !empty($newpwd2)) && $newpwd1 != $newpwd2)
		{
		$babBody->msgerror = bab_translate("Passwords not match !!");
		return false;
		}
	
	if ( mb_strlen($newpwd1) < 6 )
		{
		$babBody->msgerror = bab_translate("Password must be at least 6 characters !!");
		return false;
		}

	list($nickname, $dbauth) = $babDB->db_fetch_row($babDB->db_query("select nickname, db_authentification from ".BAB_USERS_TBL." where id='".$item."'"));

	$authentification = $babBody->babsite['authentification'];
	if( $dbauth == 'Y' )
		{
		$authentification = ''; // force to default
		}

	switch($authentification)
		{
		case BAB_AUTHENTIFICATION_AD: // Active Directory
			$babBody->msgerror = bab_translate("Nothing Changed !!");
			return false;
			break;
		case BAB_AUTHENTIFICATION_LDAP: // Active Directory
			if( !empty($babBody->babsite['ldap_encryptiontype']))
				{
				include_once $GLOBALS['babInstallPath']."utilit/ldap.php";
				$ldap = new babLDAP($babBody->babsite['ldap_host'], "", false);
				$ret = $ldap->connect();
				if( $ret === false )
					{
					$babBody->msgerror = bab_translate("LDAP connection failed");
					return false;
					}

				$ret = $ldap->bind($babBody->babsite['ldap_admindn'], $babBody->babsite['ldap_adminpassword']);
				if( !$ret )
					{
					$ldap->close();
					$babBody->msgerror = bab_translate("LDAP bind failed");
					return  false;
					}
	
				if( isset($babBody->babsite['ldap_filter']) && !empty($babBody->babsite['ldap_filter']))
					{
					$filter = str_replace('%UID', ldap_escapefilter($babBody->babsite['ldap_attribute']), $babBody->babsite['ldap_filter']);
					$filter = str_replace('%NICKNAME', ldap_escapefilter($nickname), $filter);
					}
				else
					{
					$filter = "(|(".ldap_escapefilter($babBody->babsite['ldap_attribute'])."=".ldap_escapefilter($nickname)."))";
					}

				$attributes = array("dn", $babBody->babsite['ldap_attribute'], "cn");
				$entries = $ldap->search($babBody->babsite['ldap_searchdn'], $filter, $attributes);

				if( $entries === false )
					{
					$ldap->close();
					$babBody->msgerror = bab_translate("LDAP search failed");
					return false;
					}

				$ldappw = ldap_encrypt($newpwd1, $babBody->babsite['ldap_encryptiontype']);
				$ret = $ldap->modify($entries[0]['dn'], array('userPassword'=>$ldappw));
				$ldap->close();
				if( !$ret)
					{
					$babBody->msgerror = bab_translate("Nothing Changed");
					return false;
					}
				}
			break;
		default:
			break;
		}

	$babDB->db_query("update ".BAB_USERS_TBL." set password='". md5(mb_strtolower($newpwd1)). "' where id='". $item . "'");
	$error = '';
	
	include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
	bab_callAddonsFunctionArray('onUserChangePassword', array(
		'id'=>$item, 
		'nickname'=>$nickname, 
		'password'=>$newpwd1, 
		'error'=>&$error
		)
	);
	
	if( !empty($error))
		{
		$babBody->msgerror = $error;
		return false;
		}
	return true;
	}
/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['users'] != 'Y' )
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

$idx = bab_rp('idx','Modify');
$pos = bab_rp('pos');
$grp = bab_rp('grp');


if( isset($modify))
	{
	if(isset($bupdate))
		{
		$group = isset($group) ? $group : '';
		$authtype = isset($authtype) ? $authtype : '';
		updateUser($item, $changepwd, $is_confirmed, $disabled, $authtype, $group);
		}
	else if(isset($bdelete))
		$idx = "Delete";
	}

if( isset($update) && $update == "password")
	{
	if(!updatePassword($item, $newpwd1, $newpwd2))
		$idx = "Modify";
	else
		{
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
		return;
		}
	}

if( isset($update) && $update == "nickname")
	{
	if(!updateNickname($item, $newnick))
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
		$mgroups->setExpandChecked();
		$mgroups->setField('action', 'updategroups');
		$mgroups->setField('item', $_REQUEST['item']);
		$pos = isset($_REQUEST['pos']) ? $_REQUEST['pos'] : 0;
		$grp = isset($_REQUEST['grp']) ? $_REQUEST['grp'] : 0;
		$mgroups->setField('pos', $pos);
		$mgroups->setField('grp', $grp);
		$mgroups->setGroupOption(BAB_REGISTERED_GROUP,'disabled',true);
		$arr = bab_getUserGroups($_REQUEST['item']);
		if (isset($arr['id']))
			$mgroups->setGroupsOptions($arr['id'],'checked',true);
		if ( $babBody->currentAdmGroup != 0 )
			$mgroups->setGroupOption($babBody->currentDGGroup['id_group'],'disabled',true);
		$mgroups->babecho();

		$babBody->addItemMenu("List", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Modify", bab_translate("User"),$GLOBALS['babUrlScript']."?tg=user&idx=Modify&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Groups", bab_translate("Groups"),$GLOBALS['babUrlScript']."?tg=user&idx=Groups&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("unav", bab_translate("Unavailability"), $GLOBALS['babUrlScript']."?tg=options&idx=unav&iduser=".$item );	
		
		break;
		
		
	case 'viewgroups':
		if (false === bab_rp('popup', false)) {
			$babBody->addItemMenu("List", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
			$babBody->addItemMenu("viewgroups", bab_translate("Groups"),$GLOBALS['babUrlScript']."?tg=users&idx=viewgroups&pos=".$pos."&grp=".$grp);
		}
		
		$babBody->setTitle(bab_translate("The user's groups"));
		viewgroups();
		
		break;

	case "Modify":
		$babBody->title = bab_getUserName($item);
		if( !isset($pos)) {$pos='';}
		if( !isset($grp)) {$grp='';}
		modifyUser($item, $pos, $grp);
		changeNickname($item, $pos, $grp);
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