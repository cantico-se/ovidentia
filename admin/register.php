<?php
include $babInstallPath."utilit/mailincl.php";
function notifyUserRegistration($link, $name, $email)
	{
	global $body, $babAdminEmail, $babInstallPath;

	class tempa
		{
        var $sitename;
        var $linkurl;
        var $linkname;
		var $username;
		var $message;


		function tempa($link, $name)
			{
            global $babSiteName;
            $this->linkurl = $link;
            $this->linkname = babTranslate("link");
            $this->username = $name;
			$this->sitename = $babSiteName;
			$this->message = babTranslate("Thank You For Registering at our site");
			$this->message .= "<br>". babTranslate("To confirm your registration");
			$this->message .= ", ". babTranslate("simply follow this").": ";
			}
		}
	
	$tempa = new tempa($link, $name);
	$message = babPrintTemplate($tempa,"mailinfo.html", "userregistration");

    $mail = new babMail();
    $mail->mailTo($email);
    $mail->mailFrom($babAdminEmail, "Ovidentia Administrator");
    $mail->mailSubject(babTranslate("Registration Confirmation"));
    $mail->mailBody($message, "html");
    $mail->send();
	}

function notifyAdminRegistration($name, $useremail)
	{
	global $body, $babAdminEmail, $babInstallPath;

	class tempb
		{
        var $sitename;
		var $username;
		var $message;
		var $email;


		function tempb($name, $useremail)
			{
            global $babSiteName;
            $this->email = $useremail;
            $this->username = $name;
			$this->sitename = $babSiteName;
			$this->message = babTranslate("Your site recorded a new registration on behalf of");
			}
		}
	
	$tempb = new tempb($name, $useremail);
	$message = babPrintTemplate($tempb,"mailinfo.html", "adminregistration");

    $mail = new babMail();
	$db = new db_mysql();
	$sql = "select * from users_groups where id_group='3'";
	$result=$db->db_query($sql);
	if( $result && $db->db_num_rows($result) > 0 )
		{
		while( $arr = $db->db_fetch_array($result))
			{
			$sql = "select * from users where id='".$arr[id_object]."'";
			$res=$db->db_query($sql);
			$r = $db->db_fetch_array($res);
			$mail->mailTo($r[email]);
			}
		}

    $mail->mailFrom($babAdminEmail, "Ovidentia Administrator");
    $mail->mailSubject(babTranslate("Registration Confirmation"));
    $mail->mailBody($message, "html");
    $mail->send();
	}

/* generate a random password given a len */
function random_password($length)
	{
	mt_srand((double)microtime() * 1000000);
	$possible = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$str = "";
	while( strlen($str) < $length)
		{
		$str .= substr($possible, mt_rand(0, strlen($possible) - 1), 1);
		}
	return $str;
	}

function isEmailValid ($email)
	{
	return (ereg('^[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+'. '@'. '[-!#$%&\'*+\\/0-9=?A-Z^_`a-z{|}~]+\.' . '[-!#$%&\'*+\\./0-9=?A-Z^_`a-z{|}~]+$', $email));
	}

function registerUser( $fullname, $email, $password1, $password2)
	{
	global $BAB_HASH_VAR, $body, $babUrl, $babAdminEmail, $babSiteName;
	$password1=strtolower($password1);
	$hash=md5($email.$BAB_HASH_VAR);
	$sql="insert into users (fullname,password,email,date,confirm_hash,is_confirmed,changepwd) ".
		"values ('$fullname','". md5($password1) ."','$email', now(),'$hash','0','1')";
	$db = new db_mysql();
	$result=$db->db_query($sql);
	if ($result)
		{
		$id = $db->db_insert_id();
		$sql = "insert into calendar (owner, type) values ('$id', '1')";
		$result=$db->db_query($sql);

		$body->msgerror = babTranslate("Thank You For Registering at our site") ."<br>";
		$body->msgerror .= babTranslate("You will receive an email which let you confirm your registration.");
		$link = $babUrl."index.php?tg=register&cmd=confirm&hash=$hash&email=". urlencode($email);
		//mail ($email,babTranslate("Registration Confirmation"),$message,"From: \"".$babAdminEmail."\" \nContent-Type:text/html;charset=iso-8859-1\n");
		notifyUserRegistration($link, $fullname, $email);
		notifyAdminRegistration($fullname, $email);
		//$body->msgerror = $msg;
		return true;
		}
	else
		return false;
	}

function userLogin($email,$password)
	{
	global $body, $BAB_SESS_USER, $BAB_SESS_EMAIL, $BAB_SESS_USERID, $BAB_SESS_HASHID;
	$password=strtolower($password);
	$sql="select * from users where email='$email' and password='". md5($password) ."'";
	$db = new db_mysql();
	$result=$db->db_query($sql);
	if ($db->db_num_rows($result) < 1)
		{
		$body->msgerror = babTranslate("User not found or password incorrect");
		return false;
		} 
	else 
		{
		$arr = $db->db_fetch_array($result);
		/*
		if( isUserAlreadyLogged($arr[id]))
			{
			$body->msgerror = babTranslate("Sorry, this account is already used elsewhere");
			return false;
			}
		*/
		if( $arr[disabled] == '1')
			{
			$body->msgerror = babTranslate("Sorry, your account is disabled. Please contact your adminsitrator");
			return false;
			}
		if ($arr[is_confirmed] == '1')
			{
			$BAB_SESS_USER = $arr[fullname];
			$BAB_SESS_EMAIL = $arr[email];
			$BAB_SESS_USERID = $arr[id];
			$BAB_SESS_HASHID = $arr[confirm_hash];
			$body->msgerror =  babTranslate("SUCCESS - You Are Now Logged In");
			return true;
			}
		else
			{
			$body->msgerror =  babTranslate("Sorry - You haven't Confirmed Your Account Yet");
			return false;
			}
		}
	}
	

function confirmUser($hash, $email)
	{
	global $BAB_HASH_VAR, $body;
	//verify that they didn't tamper with the email address
	$new_hash=md5($email.$BAB_HASH_VAR);
	if ($new_hash && ($new_hash==$hash))
		{
		//find this record in the db
		$sql="select * from users where confirm_hash='$hash'";
		$db = new db_mysql();
		$result=$db->db_query($sql);
		if( $db->db_num_rows($result) < 1)
			{
			$body->msgerror = babTranslate("User Not Found") ." !";
			return false;
			}
		else
			{
			//confirm the email and set account to active
			$body->msgerror = babTranslate("User Account Updated - You can now log to our site");
			//xx user_set_tokens(db_result($result,0,'user_name'));
			$sql="update users SET email='$email',is_confirmed='1' WHERE confirm_hash='$hash'";
			$result=$db->db_query($sql);
			return true;
			}
		}
	else
		{
		$body->msgerror = babTranslate("Update failed");
		return false;
		}

	}

function userChangePassword($oldpwd, $newpwd)
	{
	global $body, $BAB_SESS_USERID, $BAB_SESS_HASHID;

	$new_password1=strtolower($newpwd);
	$sql="select * from users where id='". $BAB_SESS_USERID ."'";
	$db = new db_mysql();
	$result=$db->db_query($sql);
	if ($db->db_num_rows($result) < 1)
		{
		$body->msgerror = babTranslate("User not found or bad password");
		return false;
		}
	else
		{
		$arr = $db->db_fetch_array($result);
		$oldpwd2 = md5(strtolower($oldpwd));
		if( $oldpwd2 == $arr[password])
			{
			$sql="update users set password='". md5(strtolower($newpwd)). "' ".
				"where id='". $BAB_SESS_USERID . "'";
			$result=$db->db_query($sql);
			if ($db->db_affected_rows() < 1)
				{
				$body->msgerror = babTranslate("Nothing Changed");
				return false;
				}
			else
				{
				$body->msgerror = babTranslate("Password Changed");
				return true;
				}
			}
		else
			{
			$body->msgerror = babTranslate("ERROR: Old password incorrect !!");
			return false;
			}
		}
	}

function sendPassword ($email)
	{
	global $body, $BAB_HASH_VAR, $babAdminEmail;

	if (!empty($email))
		{
		$req="select * from users where email='$email'";
		$db = new db_mysql();
		$res = $db->db_query($req);
		if (!$res || $db->db_num_rows($res) < 1)
			{
			//no matching user found
			$body->msgerror = babTranslate("Incorrect Email Address");
			return false;
			}
		else
			{
			//create a secure, new password
			$new_pass=strtolower(random_password(8));

			//update the database to include the new password
			$req="update users set password='". md5($new_pass) ."' where email='$email'";
			$res=$db->db_query($req);

			//send a simple email with the new password
			echo "pwd = ".$new_pass;
			$message = babTranslate("Your password has been reset to")." : ". $new_pass;
			mail ($email, babTranslate("Password Reset"),$message,"From: \"".$babAdminEmail."\" \nContent-Type:text/html;charset=iso-8859-1\n");
			$body->msgerror = babTranslate("Your new password has been emailed to you.");
			return true;
			}
		}
	else
		{
		$body->msgerror = babTranslate("ERROR - User Name and Email Address Are Required");
		return false;
		}
}

if(isset($cmd) && $cmd == "confirm")
	confirmUser( $hash, $email);
?>