<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include $babInstallPath."admin/register.php";

function listUsers($pos)
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

		function temp($pos)
			{
			global $babMaxRows;
			$this->fullname = babTranslate("Full Name");
			$this->email = babTranslate("Email");
			$this->allname = babTranslate("All");
			$this->db = new db_mysql();

			$req = "select * from users where firstname like '".$pos."%' order by firstname, lastname asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);

			$this->pos = $pos;

			if( empty($pos))
				$this->allselected = 1;
			else
				$this->allselected = 0;
			$this->allurl = $GLOBALS[babUrl]."index.php?tg=users&idx=List&pos=";

			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS[babUrl]."index.php?tg=user&idx=Modify&item=".$this->arr[id]."&pos=".$this->pos;
				$this->urlname = composeName($this->arr[firstname],$this->arr[lastname]);
				$req = "select * from users_log where id_user='".$this->arr[id]."'";
				$res = $this->db->db_query($req);
				$arr2 = $this->db->db_fetch_array($res);
				if( $arr2[islogged] == "Y")
					$this->status ="*";
				else
					$this->status ="";
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
				$this->selecturl = $GLOBALS[babUrl]."index.php?tg=users&idx=List&pos=".$this->selectname;

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

	$temp = new temp($pos);
	$body->babecho(	babPrintTemplate($temp, "users.html", "userslist"));
	}

function userFind()
	{
	global $body;
	class temp
		{
		var $fullname;
		var $email;
		var $password;
		var $repassword;
		var $finduser;
		var $by;

		function temp()
			{
			$this->fullname = babTranslate("Full Name");
			$this->email = babTranslate("Email");
			$this->what = babTranslate("Email or Full name");
			$this->by = babTranslate("By");
			$this->finduser = babTranslate("Find User");
			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"users.html", "usersfind"));
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


function findUser( $what, $by)
{
	global $body, $pos, $like, $selectby, $idx;

	if( empty($what))
		{
		$body->msgerror = babTranslate("You must provide a name or email !!");
		return;
		}

	$pos = "";
	$like = $what;
	if( $by == "0")
		$selectby = "email";
	else
		$selectby = "fullname";
	$idx = "List";
}

/* main */
if( !isset($pos))
	$pos = "A";
if( !isset($selectby))
	$selectby = "firstname";
if( !isset($like))
	$like = "";

if( !isset($idx))
	$idx = "List";

if( isset($adduser))
{
	if(!addUser($firstname, $lastname, $nickname, $email, $password1, $password2))
		$idx = "Create";
	else
		$pos = substr($firstname,0,1);
}

if( isset($find))
	findUser($what, $by);

switch($idx)
	{
	/*
	case "Find":
		$body->title = babTranslate("Create a user");
		userFind();
		$body->addItemMenu("List", babTranslate("List"),$GLOBALS[babUrl]."index.php?tg=users&idx=List");
		$body->addItemMenu("Create", babTranslate("Create"), $GLOBALS[babUrl]."index.php?tg=users&idx=Create");
		$body->addItemMenu("Find", babTranslate("Find"), $GLOBALS[babUrl]."index.php?tg=users&idx=Find");
		break;
	*/
	case "Create":
		$body->title = babTranslate("Create a user");
		userCreate($firstname, $lastname, $nickname, $email);
		$body->addItemMenu("List", babTranslate("Users"),$GLOBALS[babUrl]."index.php?tg=users&idx=List");
		$body->addItemMenu("Create", babTranslate("Create"), $GLOBALS[babUrl]."index.php?tg=users&idx=Create&pos=".$pos);
		//$body->addItemMenu("Find", babTranslate("Find"), $GLOBALS[babUrl]."index.php?tg=users&idx=Find");
		break;
	case "List":
		$body->title = babTranslate("Users list");
		listUsers($pos);
		$body->addItemMenu("List", babTranslate("Users"),$GLOBALS[babUrl]."index.php?tg=users&idx=List");
		$body->addItemMenu("Create", babTranslate("Create"), $GLOBALS[babUrl]."index.php?tg=users&idx=Create&pos=".$pos);
		//$body->addItemMenu("Find", babTranslate("Find"), $GLOBALS[babUrl]."index.php?tg=users&idx=Find");
		break;
	default:
		break;
	}

$body->setCurrentItemMenu($idx);
?>