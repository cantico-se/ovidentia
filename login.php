<?php
include $babInstallPath."admin/register.php";

function displayLogin()
	{
	global $body;
	class temp
		{
		var $email;
		var $password;

		function temp()
			{
			$this->email = babTranslate("Email");
			$this->password = babTranslate("Password");
			$this->login = babTranslate("Login");
			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"login.html", "login"));
	}

function changePassword()
	{
	global $body,$BAB_SESS_USERID;
	class temp
		{
		var $oldpwd;
		var $newpwd;
		var $renewpwd;
		var $update;

		function temp()
			{
			$this->oldpwd = babTranslate("Old Password");
			$this->newpwd = babTranslate("New Password");
			$this->renewpwd = babTranslate("Retype New Password");
			$this->update = babTranslate("Update Password");
			}
		}

	$db = new db_mysql();
	$req = "select * from users where id='$BAB_SESS_USERID'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);

	if( $arr[changepwd] != 0)
		{
		$temp = new temp();
		$body->babecho(	babPrintTemplate($temp,"login.html", "changepassword"));
		}
	else
		$body->msgerror = babTranslate("Sorry, You cannot change your password. Please contact administrator");
	}

function emailPassword()
	{
	global $body;
	class temp
		{
		var $email;
		var $send;

		function temp()
			{
			$this->email = babTranslate("Your email");
			$this->send = babTranslate("Send");
			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"login.html", "emailpassword"));
	}

function signOn( $email, $password)
	{
	global $body, $BAB_SESS_USER, $BAB_SESS_USERID;
	if( empty($email) || empty($password))
		{
		$body->msgerror = babTranslate("You must complete all fields !!");
		return false;
		}
	if ( !isEmailValid($email))
		{
		$body->msgerror = babTranslate("Your email is not valid !!");
		return false;
		}

	if( !userLogin($email, $password))
		return false;

	$db = new db_mysql();
	$req="select * from users_log where id_user='$BAB_SESS_USERID'";
	$res=$db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$req="update users_log set datelog=now(), dateact=now(),islogged='Y' where id_user='$BAB_SESS_USERID'";
		}
	else
		{
		$req="insert into users_log (id_user, datelog,dateact,islogged) values ('$BAB_SESS_USERID', now(), now(), 'Y')";
		}
	$res=$db->db_query($req);
	return true;
	}

function signOff()
	{
	global $body, $BAB_HASH_VAR, $BAB_SESS_USER, $BAB_SESS_EMAIL, $BAB_SESS_USERID, $BAB_SESS_HASHID,$LOGGED_IN;
	
	$db = new db_mysql();
	$req="select * from users_log where id_user='$BAB_SESS_USERID'";
	$res=$db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$req="update users_log set islogged='N' where id_user='$BAB_SESS_USERID'";
		$res=$db->db_query($req);
		}

	$BAB_SESS_USER = "";
	$BAB_SESS_EMAIL = "";
	$BAB_SESS_USERID ="";
	$BAB_SESS_HASHID = "";
	session_unregister("BAB_SESS_USER");
	session_unregister("BAB_SESS_EMAIL");
	session_unregister("BAB_SESS_USERID");
	session_unregister("BAB_SESS_HASHID");
	Header("Location: index.php");
	}

function updatePassword($oldpwd, $newpwd1, $newpwd2)
	{
	global $body;
	if( empty($oldpwd) || empty($newpwd1) || empty($newpwd2))
		{
		echo $oldpwd ."   ". $newpwd1."     ". $newpwd2;
		$body->msgerror = babTranslate("You must complete all fields !!");
		return;
		}
	if( $newpwd1 != $newpwd2)
		{
		$body->msgerror = babTranslate("Passwords not match !!");
		return;
		}
	if ( strlen($newpwd1) < 6 )
		{
		$body->msgerror = babTranslate("Password must be at least 6 characters !!");
		return;
		}

	userChangePassword( $oldpwd, $newpwd1);
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
			$this->repassword = babTranslate("Retype Paasword");
			$this->adduser = babTranslate("Register");
			}
		}

	$temp = new temp();
	$body->babecho(	babPrintTemplate($temp,"login.html", "usercreate"));
	}

function addUser( $fullname, $email, $password1, $password2)
	{
	global $body;
	if( empty($fullname) || empty($email) || empty($password1) || empty($password2))
		{
		$body->msgerror = babTranslate( "You must complete all fields !!");
		return;
		}
	if( $password1 != $password2)
		{
		$body->msgerror = babTranslate("Passwords not match !!");
		return;
		}
	if ( strlen($password1) < 6 )
		{
		$body->msgerror = babTranslate("Password must be at least 6 characters !!");
		return;
		}

	if ( !isEmailValid($email))
		{
		$body->msgerror = babTranslate("Your email is not valid !!");
		return;
		}

	$db = new db_mysql();
	$query = "select * from users where email='$email'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$body->msgerror = babTranslate("This email address already exists !!");
		return;
		}
	registerUser($fullname, $email, $password1, $password2);
}

/* main */
if( isset($login) && $login == "login")
	{
	if(!signOn($email, $password))
		return;
	Header("Location: index.php");
	}

if( isset($update) && $update == "update")
	{
	updatePassword($oldpwd, $newpwd1, $newpwd2);
	}

if( isset($adduser) && $adduser == "register")
	{
	addUser($fullname, $email, $password1, $password2);
	}

if( isset($sendpassword) && $sendpassword == "send")
	{
	sendPassword($email);
	}

switch($cmd)
	{
	case "signoff":
		signOff();
		break;

	case "register":
		$body->title = babTranslate("Please provide a valid email.") . "<br>";
		$body->title .= babTranslate("We will send you an email for confirmation before you can use our services") . "<br>";
		$body->addItemMenu("register", babTranslate("Register"), $GLOBALS[babUrl]."index.php?tg=login&cmd=register");
		$body->addItemMenu("emailpwd", babTranslate("Lost Password"), $GLOBALS[babUrl]."index.php?tg=login&cmd=emailpwd");
		$body->setCurrentItemMenu($cmd);
		userCreate();
		break;

	case "newpwd":
		$body->title = babTranslate("Change password");
		changePassword();
		break;

	case "emailpwd":
		$body->title = babTranslate("Email a new password");
		$body->addItemMenu("register", babTranslate("Register"), $GLOBALS[babUrl]."index.php?tg=login&cmd=register");
		$body->addItemMenu("emailpwd", babTranslate("Lost Password"), $GLOBALS[babUrl]."index.php?tg=login&cmd=emailpwd");
		$body->setCurrentItemMenu($cmd);
		emailPassword();
		break;

	case "signon":
	default:
		$body->title = babTranslate("Login");
		$body->addItemMenu("signon", babTranslate("Login"), $GLOBALS[babUrl]."index.php?tg=login&cmd=signon");
		$body->addItemMenu("register", babTranslate("Register"), $GLOBALS[babUrl]."index.php?tg=login&cmd=register");
		$body->addItemMenu("emailpwd", babTranslate("Lost Password"), $GLOBALS[babUrl]."index.php?tg=login&cmd=emailpwd");
		$body->setCurrentItemMenu($cmd);
		displayLogin();
		break;
	}

?>