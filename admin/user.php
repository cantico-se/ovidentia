<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";
include $babInstallPath."admin/register.php";
include $babInstallPath."utilit/grpincl.php";
include $babInstallPath."utilit/fileincl.php";


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
			$this->shwoprimary = false;
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

function listGroups($id, $pos, $grp)
	{
	global $babBody;
	if( !isset($id))
		{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid user !!");
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

		function temp($id, $pos, $grp)
			{
			$this->name = bab_translate("Groups Names");
			$this->group = bab_translate("Group");
			$this->updategroups = bab_translate("Update Groups");
			$this->pos = $pos;
			$this->grp = $grp;
			$this->groupst = "";
			$this->id = $id;
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_USERS_GROUPS_TBL." where id_object='$id'";
			$this->res1 = $this->db->db_query($req);
			$this->count1 = $this->db->db_num_rows($this->res1);
			if( $this->count1 < 1)
				$this->select = "selected";

			$req = "select * from ".BAB_GROUPS_TBL." where id > 2 order by id asc";
			$this->res2 = $this->db->db_query($req);
			$this->count2 = $this->db->db_num_rows($this->res2);
			}

		function getnextgroup()
			{
			static $i = 0;
			
			if( $i < $this->count2)
				{
				$this->arrgroups = $this->db->db_fetch_array($this->res2);
				$this->groupid = $this->arrgroups['id'];
				$this->groupname = $this->arrgroups['name'];
				$this->groupurl = $GLOBALS['babUrlScript']."?tg=group&idx=Members&item=".$this->arrgroups['id'];
				$this->checked = "";
				$this->primary = "";
				if($this->count1 > 0)
					{
					$this->db->db_data_seek($this->res1, 0);
					$this->primary = "";
					$this->checked = "";
					for( $j = 0; $j < $this->count1; $j++)
						{
						$this->groups = $this->db->db_fetch_array($this->res1);
						if( $this->groups['id_group'] == $this->arrgroups['id'])
							{
							if( $this->groups['isprimary'] == "Y")
								$this->primary = "Y"; 
							$this->checked = "checked";
							if( empty($this->groupst))
								$this->groupst = $this->arrgroups['id'];
							else
								$this->groupst .= ",".$this->arrgroups['id'];
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
	$temp = new temp($id, $pos, $grp);
	$babBody->babecho(	bab_printTemplate($temp, "users.html", "usersgroups"));
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
		var $update;
		var $item;
		var $pos;
		var $grp;

		function tempb($item, $pos, $grp)
			{
			$this->item = $item;
			$this->pos = $pos;
			$this->grp = $grp;
			$this->newpwd = bab_translate("New Password");
			$this->renewpwd = bab_translate("Retype New Password");
			$this->update = bab_translate("Update Password");
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
    $mail->mailFrom($babAdminEmail, bab_translate("Ovidentia Administrator"));
    $mail->mailSubject(bab_translate("Registration Confirmation"));
	

	$message = bab_translate("Thank You For Registering at our site");
	$message .= "<br>". bab_translate("Your registration has been confirmed.");
	$message .= "<br>". bab_translate("To connect on our site").", ". bab_translate("simply follow this").": ";
	$tempa = new tempa($name, $message);
	$message = bab_printTemplate($tempa,"mailinfo.html", "userconfirmation");
    $mail->mailBody($message, "html");

	$message = bab_translate("Thank You For Registering at our site") ."\n";
	$message .= bab_translate("Your registration has been confirmed.")."\n";
	$message .= bab_translate("To connect on our site").", ". bab_translate("go to this url").": ";
	$tempa = new tempa($name, $message);
	$message = bab_printTemplate($tempa,"mailinfo.html", "userconfirmationtxt");
    $mail->mailAltBody($message);

	$mail->send();
	}

function updateGroups($id, $groups, $groupst)
	{
	$db = $GLOBALS['babDB'];

	if( !empty($groupst))
		$tab = explode(",", $groupst);
	else
		$tab = array();
	for( $i = 0; $i < count($tab); $i++)
	{
		if( count($groups) < 1  || !in_array($tab[$i], $groups))
		{
			$req = "delete from ".BAB_USERS_GROUPS_TBL." where id_group='".$tab[$i]."' and id_object='".$id."'";
			$res = $db->db_query($req);
		}
	}
	for( $i = 0; $i < count($groups); $i++)
	{
		if( count($tab) < 1 || !in_array($groups[$i], $tab))
		{
			$req = "insert into ".BAB_USERS_GROUPS_TBL." (id_group, id_object) VALUES ('" .$groups[$i]. "', '" . $id. "')";
			$res = $db->db_query($req);
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
	$db->db_query("update ".BAB_USERS_GROUPS_TBL." set isprimary='N'where id_object='$id'");
	if( !empty($group))
		{
		$db->db_query("update ".BAB_USERS_GROUPS_TBL." set isprimary='Y'where id_object='$id' and id_group='$group'");
		}

	if( $is_confirmed == 1 && $r['is_confirmed'] == 0 )
		{
		$arr2 = $db->db_fetch_array($db->db_query("select idgroup from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'"));
		if( $arr2['idgroup'] != 0)
			{
			$res = $db->db_query("select * from ".BAB_USERS_GROUPS_TBL." where id_object='".$id."' and id_group='".$arr2['idgroup']."'");
			if( !$res || $db->db_num_rows($res) < 1)
				{
				$db->db_query("insert into ".BAB_USERS_GROUPS_TBL." (id_group, id_object) VALUES ('" .$arr2['idgroup']. "', '" . $id. "')");
				}
			}
		notifyUserconfirmation( bab_composeUserName($r['firstname'] , $r['lastname']), $r['email']);
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=users&idx=List");
	}

function confirmDeleteUser($id)
	{
	$db = $GLOBALS['babDB'];

	// delete notes owned by this user
	$req = "delete from ".BAB_NOTES_TBL." where id_user='$id'";
	$res = $db->db_query($req);	

	// delete user from groups
	$req = "delete from ".BAB_USERS_GROUPS_TBL." where id_object='$id'";
	$res = $db->db_query($req);	
					
	$req = "select * from ".BAB_CALENDAR_TBL." where owner='$id' and type='1'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);

	// delete user's events
	$req = "delete from ".BAB_CAL_EVENTS_TBL." where id_cal='".$arr['id']."'";
	$res = $db->db_query($req);	

	// delete user's access
	$req = "delete from ".BAB_CALACCESS_USERS_TBL." where id_user='".$id."'";
	$res = $db->db_query($req);	
	$req = "delete from ".BAB_CALACCESS_USERS_TBL." where id_cal='".$arr['id']."'";
	$res = $db->db_query($req);	

	// delete user's calendar options
	$req = "delete from ".BAB_CALOPTIONS_TBL." where id_user='".$id."'";
	$res = $db->db_query($req);	

	// delete user from calendar
	$req = "delete from ".BAB_CALENDAR_TBL." where owner='$id' and type='1'";
	$res = $db->db_query($req);	

	// delete user from BAB_USERS_LOG_TBL
	$req = "delete from ".BAB_USERS_LOG_TBL." where id_user='$id'";
	$res = $db->db_query($req);	

	// delete user from BAB_MAIL_SIGNATURES_TBL
	$req = "delete from ".BAB_MAIL_SIGNATURES_TBL." where owner='$id'";
	$res = $db->db_query($req);	

	// delete user from BAB_MAIL_ACCOUNTS_TBL
	$req = "delete from ".BAB_MAIL_ACCOUNTS_TBL." where owner='$id'";
	$res = $db->db_query($req);	

	// delete user from BAB_MAIL_DOMAINS_TBL
	$req = "delete from ".BAB_MAIL_DOMAINS_TBL." where owner='$id' and bgroup='N'";
	$res = $db->db_query($req);	

	// delete user from contacts
	$req = "delete from ".BAB_CONTACTS_TBL." where owner='$id'";
	$res = $db->db_query($req);	

	// delete user from BAB_SECTIONS_STATES_TBL
	$req = "delete from ".BAB_SECTIONS_STATES_TBL." where id_user='$id'";
	$res = $db->db_query($req);	

	// delete files owned by this user
	bab_deleteUploadUserFiles("N", $id);

	// delete user from BAB_DBDIR_ENTRIES_TBL
	$req = "delete from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='0' and id_user='".$id."'";
	$res = $db->db_query($req);	

	// delete user from VACATION
	$db->db_query("delete from ".BAB_VAC_MANAGERS_TBL." where id_user='".$id."'");
	$db->db_query("delete from ".BAB_VAC_USERS_RIGHTS_TBL." where id_user='".$id."'");
	$db->db_query("delete from ".BAB_VAC_PERSONNEL_TBL." where id_user='".$id."'");
	$res = 	$db->db_query("select id from ".BAB_VAC_ENTRIES_TBL." where id_user='".$id."'");
	while( $arr = $db->db_fetch_array($res))
	{
		$db->db_query("delete from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry='".$arr['id']."'");
		$db->db_query("delete from ".BAB_VAC_ENTRIES_TBL." where id='".$arr['id']."'");
	}

	// delete user
	$req = "delete from ".BAB_USERS_TBL." where id='$id'";
	$res = $db->db_query($req);
	bab_callAddonsFunction('onUserDelete', $id);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=users&idx=List");
	}

function updatePassword($item, $newpwd1, $newpwd2)
	{
	global $babBody;

	if( empty($newpwd1) || empty($newpwd2))
		{
		$babBody->msgerror = bab_translate("You must complete all fields !!");
		return false;
		}
	if( $newpwd1 != $newpwd2)
		{
		$babBody->msgerror = bab_translate("Passwords not match !!");
		return false;
		}
	if ( strlen($newpwd1) < 6 )
		{
		$babBody->msgerror = bab_translate("Password must be at least 6 characters !!");
		return false;
		}

	$db = $GLOBALS['babDB'];
	$req="update ".BAB_USERS_TBL." set password='". md5(strtolower($newpwd1)). "' ".
		"where id='". $item . "'";
	$res = $db->db_query($req);
	if ($db->db_affected_rows() < 1)
		{
		$babBody->msgerror = bab_translate("Nothing Changed");
		return false;
		}

	return true;
	}

/* main */

if( !isset($idx))
	$idx = "Modify";

if( isset($modify))
	{
	if(isset($bupdate))
		updateUser($item, $changepwd, $is_confirmed, $disabled, $group);
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

if( isset($action) && $action == "Yes")
	{
	confirmDeleteUser($user);
	}

if( isset($updategroups) && $updategroups == "update")
	{
	updateGroups($item, $groups, $groupst);
	$idx = "Groups";
	}

switch($idx)
	{
	case "Delete":
		$babBody->title = bab_translate("Delete a user");
		deleteUser($item);
		$babBody->addItemMenu("List", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=user&idx=Modify&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Groups", bab_translate("Groups"),$GLOBALS['babUrlScript']."?tg=user&idx=Groups&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Delete", bab_translate("Delete"),$GLOBALS['babUrlScript']."?tg=user&idx=Delete&item=".$item."&pos=".$pos."&grp=".$grp);
		//$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=users&idx=Create");
		break;
	case "Groups":
		$babBody->title = bab_getUserName($item) . bab_translate(" is member of");
		listGroups($item, $pos, $grp);
		$babBody->addItemMenu("List", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Modify", bab_translate("User"),$GLOBALS['babUrlScript']."?tg=user&idx=Modify&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Groups", bab_translate("Groups"),$GLOBALS['babUrlScript']."?tg=user&idx=Groups&item=".$item."&pos=".$pos."&grp=".$grp);
		break;
	case "Modify":
		$babBody->title = /* bab_translate("Modify a user") . ": " . */bab_getUserName($item);
		modifyUser($item, $pos, $grp);
		changePassword($item, $pos, $grp);
		$babBody->addItemMenu("List", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=user&idx=Modify&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Groups", bab_translate("Groups"),$GLOBALS['babUrlScript']."?tg=user&idx=Groups&item=".$item."&pos=".$pos."&grp=".$grp);
		//$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=users&idx=Create");
		break;
	default:
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>