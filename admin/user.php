<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."admin/register.php";
include $babInstallPath."utilit/grpincl.php";
include $babInstallPath."utilit/fileincl.php";


function modifyUser($id)
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

		function temp($id)
			{
			$this->changepassword = bab_translate("Can user change password ?");
			$this->isconfirmed = bab_translate("Account confirmed ?");
			$this->isdisabled = bab_translate("Account disabled ?");
			$this->primarygroup = bab_translate("Primary group");
			$this->none = bab_translate("None");
			$this->modify = bab_translate("Modify");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->db = $GLOBALS['babDB'];
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

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp, "users.html", "usersmodify"));
	}

function listGroups($id)
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

		function temp($id)
			{
			$this->name = bab_translate("Groups Names");
			$this->group = bab_translate("Group");
			$this->updategroups = bab_translate("Update Groups");
			$this->groupst = "";
			$this->id = $id;
			$this->db = $GLOBALS['babDB'];
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
	$temp = new temp($id);
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

function notifyUserconfirmation($name, $email)
	{
	global $babBody, $babAdminEmail, $babInstallPath;

	class tempa
		{
        var $sitename;
        var $linkurl;
		var $username;
		var $message;


		function tempa($name)
			{
            global $babSiteName;
            $this->linkurl = $GLOBALS['babUrl'];
            $this->username = $name;
			$this->sitename = $babSiteName;
			$this->message = bab_translate("Thank You For Registering at our site");
			$this->message .= "<br>". bab_translate("Your registration has been confirmed.");
			$this->message .= "<br>". bab_translate("To connect on our site").", ". bab_translate("simply follow this").": ";
			}
		}
	
	$tempa = new tempa($name);
	$message = bab_printTemplate($tempa,"mailinfo.html", "userconfirmation");

    $mail = new babMail();
    $mail->mailTo($email);
    $mail->mailFrom($babAdminEmail, "Ovidentia Administrator");
    $mail->mailSubject(bab_translate("Registration Confirmation"));
    $mail->mailBody($message, "html");
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
			$req = "delete from users_groups where id_group='".$tab[$i]."' and id_object='".$id."'";
			$res = $db->db_query($req);
		}
	}
	for( $i = 0; $i < count($groups); $i++)
	{
		if( count($tab) < 1 || !in_array($groups[$i], $tab))
		{
			$req = "insert into users_groups (id_group, id_object) VALUES ('" .$groups[$i]. "', '" . $id. "')";
			$res = $db->db_query($req);
		}
	}

	}



function updateUser($id, $changepwd, $is_confirmed, $disabled, $group)
	{
	global $babBody;

	$db = $GLOBALS['babDB'];
	$res = $db->db_query("select firstname, lastname, email, is_confirmed from users where id='$id'");
	if( $res )
		{
		$r = $db->db_fetch_array($res);
		}
	$res = $db->db_query("update users set changepwd='$changepwd', is_confirmed='$is_confirmed', disabled='$disabled' where id='$id'");
	if( !empty($group))
		{
		$db = $GLOBALS['babDB'];
		$db->db_query("update users_groups set isprimary='N'where id_object='$id'");
		$db->db_query("update users_groups set isprimary='Y'where id_object='$id' and id_group='$group'");
		}

	if( $is_confirmed == 1 && $r['is_confirmed'] == 0 )
		{
		notifyUserconfirmation( bab_composeUserName($r['firstname'] , $r['lastname']), $r['email']);
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=users&idx=List");
	}

function confirmDeleteUser($id)
	{
	$db = $GLOBALS['babDB'];

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
	$req = "delete from cal_events where id_cal='".$arr['id']."'";
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

	// delete user from sections_states
	$req = "delete from sections_states where id_user='$id'";
	$res = $db->db_query($req);	

	// delete files owned by this user
	bab_deleteUploadUserFiles("N", $id);

	// delete user
	$req = "delete from users where id='$id'";
	$res = $db->db_query($req);
	bab_callAddonsFunction('bab_user_delete', $id);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=users&idx=List");
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
		$babBody->title = bab_translate("Delete a user");
		deleteUser($item);
		$babBody->addItemMenu("List", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=user&idx=Modify&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Groups", bab_translate("Groups"),$GLOBALS['babUrlScript']."?tg=user&idx=Groups&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Delete", bab_translate("Delete"),$GLOBALS['babUrlScript']."?tg=user&idx=Delete&item=".$item."&pos=".$pos."&grp=".$grp);
		//$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=users&idx=Create");
		break;
	case "Updateu";
		updateGroups($item, $groups, $groupst);
		/* no break */
	case "Groups":
		$babBody->title = bab_getUserName($item) . bab_translate(" is member of");
		listGroups($item);
		$babBody->addItemMenu("List", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Modify", bab_translate("User"),$GLOBALS['babUrlScript']."?tg=user&idx=Modify&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Groups", bab_translate("Groups"),$GLOBALS['babUrlScript']."?tg=user&idx=Groups&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Upadteu", bab_translate("Update"), "javascript:(submitForm('Updateu'))");
		break;
	case "Modify":
		$babBody->title = /* bab_translate("Modify a user") . ": " . */bab_getUserName($item);
		modifyUser($item);
		$babBody->addItemMenu("List", bab_translate("Users"),$GLOBALS['babUrlScript']."?tg=users&idx=List&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=user&idx=Modify&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Groups", bab_translate("Groups"),$GLOBALS['babUrlScript']."?tg=user&idx=Groups&item=".$item."&pos=".$pos."&grp=".$grp);
		$babBody->addItemMenu("Delete", bab_translate("Delete"),$GLOBALS['babUrlScript']."?tg=user&idx=Delete&item=".$item."&pos=".$pos."&grp=".$grp);
		//$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=users&idx=Create");
		break;
	default:
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>