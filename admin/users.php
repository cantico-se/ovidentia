<?php
include $babInstallPath."admin/register.php";



function listUsers($pos,$selectby, $like)
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
		var $topurl;
		var $bottomurl;
		var $nexturl;
		var $prevurl;
		var $topname;
		var $bottomname;
		var $nextname;
		var $prevname;

		function temp($pos,$selectby, $like)
			{
			global $babMaxRows;
			$this->fullname = babTranslate("Full Name");
			$this->email = babTranslate("Email");
			$this->db = new db_mysql();
			$this->topurl = "";
			$this->bottomurl = "";
			$this->nexturl = "";
			$this->prevurl = "";
			$this->topname = "";
			$this->bottomname = "";
			$this->nextname = "";
			$this->prevname = "";
			//$req = "select count(*) as total from users";
			$req = "select count(*) as total from users";
			if( !empty($like))
				$req .= " where ".$selectby." like '".$like."%'";

			$this->res = $this->db->db_query($req);
			$row = $this->db->db_fetch_array($this->res);
			$total = $row["total"];

			$req = "select * from users";
			if( !empty($like))
				$req .= " where ".$selectby." like '".$like."%'";
			if( $total < $babMaxRows)
				{
				$req .= " order by ".$selectby." asc";
				}
			else
				$req .= " order by ".$selectby." asc limit $pos,$babMaxRows";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			if( $total > $babMaxRows)
				{
				if( $pos > 0)
					{
					$this->topurl = $GLOBALS[babUrl]."index.php?tg=users&idx=List&selectby=".$selectby."&pos=0&like=".$like;
					$this->topname = "&lt;&lt;";
					}

				$next = $pos - $babMaxRows;
				if( $next >= 0)
					{
					$this->prevurl = $GLOBALS[babUrl]."index.php?tg=users&idx=List&selectby=".$selectby."&pos=".$next."&like=".$like;
					$this->prevname = "&lt;";
					}

				$next = $pos + $babMaxRows;
				if( $next < $total)
					{
					$this->nexturl = $GLOBALS[babUrl]."index.php?tg=users&idx=List&selectby=".$selectby."&pos=".$next."&like=".$like;
					$this->nextname = "&gt;";
					if( $next + $babMaxRows < $total)
						{
						$bottom = $total - $babMaxRows;
						}
					else
						$bottom = $next;
					$this->bottomurl = $GLOBALS[babUrl]."index.php?tg=users&idx=List&selectby=".$selectby."&pos=".$bottom."&like=".$like;
					$this->bottomname = "&gt;&gt;";
					}


				}
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS[babUrl]."index.php?tg=user&idx=Modify&item=".$this->arr[id];
				$this->urlname = $this->arr[fullname];
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
		}

	$temp = new temp($pos,$selectby, $like);
	$body->babecho(	babPrintTemplate($temp, "users.html", "userslist"));
	}

function userCreate()
	{
	global $body;
	class temp
		{
		var $fullname;
		var $email;
		var $password;
		var $repassword;
		var $adduser;

		function temp()
			{
			$this->fullname = babTranslate("Full Name");
			$this->email = babTranslate("Email");
			$this->password = babTranslate("Password");
			$this->repassword = babTranslate("Retype Password");
			$this->adduser = babTranslate("Add User");
			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"users.html", "userscreate"));
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

function addUser( $fullname, $email, $password1, $password2)
	{
	global $body;
	if( empty($fullname) || empty($email) || empty($password1) || empty($password2))
		{
		$body->msgerror = babTranslate("ERROR: You must complete all fields !!");
		return;
		}
	if( $password1 != $password2)
		{
		$body->msgerror = babTranslate("ERROR: Passwords not match !!");
		return;
		}
	if ( strlen($password1) < 6 )
		{
		$body->msgerror = babTranslate("ERROR: Password must be at least 6 characters !!");
		return;
		}

	if ( !isEmailValid($email))
		{
		$body->msgerror = babTranslate("ERROR: Your email is not valid !!");
		return;
		}

	$db = new db_mysql();
	$query = "select * from users where email='$email'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$body->msgerror = babTranslate("ERROR: This email address already exists !!");
		return;
		}
	registerUser($fullname, $email, $password1, $password2);
}

function findUser( $what, $by)
{
	global $body, $pos, $like, $selectby, $idx;

	if( empty($what))
		{
		$body->msgerror = babTranslate("ERROR: You must provide a name or email !!");
		return;
		}

	$pos = 0;
	$like = $what;
	if( $by == "0")
		$selectby = "email";
	else
		$selectby = "fullname";
	$idx = "List";
}

/* main */
if( !isset($pos))
	$pos = 0;
if( !isset($selectby))
	$selectby = "fullname";
if( !isset($like))
	$like = "";

if( !isset($idx))
	$idx = "List";

if( isset($adduser))
	addUser($fullname, $email, $password1, $password2);

if( isset($find))
	findUser($what, $by);

switch($idx)
	{
	case "Find":
		$body->title = babTranslate("Create a user");
		userFind();
		$body->addItemMenu("List", babTranslate("List"),$GLOBALS[babUrl]."index.php?tg=users&idx=List");
		$body->addItemMenu("Create", babTranslate("Create"), $GLOBALS[babUrl]."index.php?tg=users&idx=Create");
		$body->addItemMenu("Find", babTranslate("Find"), $GLOBALS[babUrl]."index.php?tg=users&idx=Find");
		break;
	case "Create":
		$body->title = babTranslate("Create a user");
		userCreate();
		$body->addItemMenu("List", babTranslate("List"),$GLOBALS[babUrl]."index.php?tg=users&idx=List");
		$body->addItemMenu("Create", babTranslate("Create"), $GLOBALS[babUrl]."index.php?tg=users&idx=Create");
		$body->addItemMenu("Find", babTranslate("Find"), $GLOBALS[babUrl]."index.php?tg=users&idx=Find");
		break;
	case "List":
		$body->title = babTranslate("Users list");
		listUsers($pos,$selectby, $like);
		$body->addItemMenu("List", babTranslate("List"),$GLOBALS[babUrl]."index.php?tg=users&idx=List");
		$body->addItemMenu("Create", babTranslate("Create"), $GLOBALS[babUrl]."index.php?tg=users&idx=Create");
		$body->addItemMenu("Find", babTranslate("Find"), $GLOBALS[babUrl]."index.php?tg=users&idx=Find");
		break;
	default:
		break;
	}

$body->setCurrentItemMenu($idx);
?>