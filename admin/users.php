<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."admin/register.php";

function listUsers($pos, $grp)
	{
	global $body;
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

		function temp($pos, $grp)
			{
			$this->fullname = babTranslate("Full Name");
			$this->email = babTranslate("Email");
			$this->allname = babTranslate("All");
			$this->db = new db_mysql();
			$this->group = getGroupName($grp);
			$this->grp = $grp;

			$req = "select * from users where firstname like '".$pos."%' order by firstname, lastname asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);

			$this->pos = $pos;

			if( empty($pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS[babUrl]."index.php?tg=users&idx=List&pos=&grp=".$this->grp;
			$this->groupurl = $GLOBALS[babUrl]."index.php?tg=group&idx=Members&item=".$this->grp;

			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS[babUrl]."index.php?tg=user&idx=Modify&item=".$this->arr[id]."&pos=".$this->pos."&grp=".$this->grp;
				$this->urlname = composeName($this->arr[firstname],$this->arr[lastname]);
				$this->userid = $this->arr[id];
				$req = "select * from users_log where id_user='".$this->arr[id]."'";
				$res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res);
				if( $arr2[islogged] == "Y")
					$this->status ="*";
				else
					$this->status ="";

				$req = "select * from users_groups where id_object='".$this->arr[id]."' and id_group='".$this->grp."'";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					{
					$this->checked = "checked";
					if( empty($this->userst))
						$this->userst = $this->arr[id];
					else
						$this->userst .= ",".$this->arr[id];
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
			global $BAB_SESS_USERID;
			static $k = 0;
			static $t = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
			if( $k < 26)
				{
				$this->selectname = substr($t, $k, 1);
				$this->selecturl = $GLOBALS[babUrl]."index.php?tg=users&idx=List&pos=".$this->selectname."&grp=".$this->grp;

				if( $this->pos == $this->selectname)
					$this->selected = 1;
				else 
					{
					$req = "select * from users where firstname like '".$this->selectname."%'";
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
	$body->babecho(	babPrintTemplate($temp, "users.html", "userslist"));
	return $temp->count;
	}

function userCreate($firstname, $lastname, $nickname, $email)
	{
	global $body;
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

		function temp($firstname, $lastname, $nickname, $email)
			{
			$this->firstnameval = $firstname != ""? $firstname: "";
			$this->lastnameval = $lastname != ""? $lastname: "";
			$this->nicknameval = $nickname != ""? $nickname: "";
			$this->emailval = $email != ""? $email: "";
			$this->firstname = babTranslate("First Name");
			$this->lastname = babTranslate("Last Name");
			$this->nickname = babTranslate("Nickname");
			$this->email = babTranslate("Email");
			$this->password = babTranslate("Password");
			$this->repassword = babTranslate("Retype Paasword");
			$this->adduser = babTranslate("Register");
			}
		}

	$temp = new temp($firstname, $lastname, $nickname, $email);
	$body->babecho(	babPrintTemplate($temp,"users.html", "usercreate"));
	}

function updateGroup( $grp, $users, $userst)
{
	$db = new db_mysql();

	if( !empty($userst))
		$tab = explode(",", $userst);
	else
		$tab = array();

	for( $i = 0; $i < count($tab); $i++)
	{
		if( count($users) < 1 || !in_array($tab[$i], $users))
		{
			$req = "delete from users_groups where id_group='".$grp."' and id_object='".$tab[$i]."'";
			$res = $db->db_query($req);
		}
	}
	for( $i = 0; $i < count($users); $i++)
	{
		if( count($tab) < 1 || !in_array($users[$i], $tab))
		{
			$req = "insert into users_groups (id_group, id_object) VALUES ('" .$grp. "', '" . $users[$i]. "')";
			$res = $db->db_query($req);
		}
	}
}

/* main */
if( !isset($pos))
	$pos = "A";

if( !isset($grp))
	$grp = 3;

if( !isset($idx))
	$idx = "List";

if( isset($adduser))
{
	if(!addUser($firstname, $lastname, $nickname, $email, $password1, $password2))
		$idx = "Create";
	else
		$pos = substr($firstname,0,1);
}


switch($idx)
	{	
	case "Create":
		$body->title = babTranslate("Create a user");
		userCreate($firstname, $lastname, $nickname, $email);
		$body->addItemMenu("List", babTranslate("Users"),$GLOBALS[babUrl]."index.php?tg=users&idx=List&pos=".$pos."&grp=".$grp);
		$body->addItemMenu("Create", babTranslate("Create"), $GLOBALS[babUrl]."index.php?tg=users&idx=Create&pos=".$pos);
		break;
	case "Updateg":
		updateGroup($grp, $users, $userst);
		$idx = "List";
		/* no break */
	case "List":
		$body->title = babTranslate("Users list");
		$cnt = listUsers($pos, $grp);
		$body->addItemMenu("List", babTranslate("Users"),$GLOBALS[babUrl]."index.php?tg=users&idx=List");
		if( $cnt > 0 )
			$body->addItemMenu("Upadteg", babTranslate("Update"), "javascript:(submitForm('Updateg'))");
		$body->addItemMenu("Create", babTranslate("Create"), $GLOBALS[babUrl]."index.php?tg=users&idx=Create&pos=".$pos."&grp=".$grp);
		break;
	default:
		break;
	}

$body->setCurrentItemMenu($idx);
?>