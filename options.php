<?php
function changePassword()
	{
	global $body,$BAB_SESS_USERID;
	class tempb
		{
		var $oldpwd;
		var $newpwd;
		var $renewpwd;
		var $update;
		var $title;

		function tempb()
			{
			$this->oldpwd = babTranslate("Old Password");
			$this->newpwd = babTranslate("New Password");
			$this->renewpwd = babTranslate("Retype New Password");
			$this->update = babTranslate("Update Password");
			$this->title = babTranslate("Change password");
			}
		}

	$db = new db_mysql();
	$req = "select * from users where id='$BAB_SESS_USERID'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);

	if( $arr[changepwd] != 0)
		{
		$tempb = new tempb();
		$body->babecho(	babPrintTemplate($tempb,"options.html", "changepassword"));
		}
	else
		$body->msgerror = babTranslate("Sorry, You cannot change your password. Please contact administrator");
	}

function changeUserInfo($firstname, $lastname, $nickname, $email)
	{
	global $body,$BAB_SESS_USERID;
	class temp
		{
		var $firstname;
		var $lastname;
		var $nickname;
		var $email;
		var $firstnameval;
		var $lastnameval;
		var $nicknameval;
		var $emailval;

		var $password;
		var $update;
		var $title;

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
			$this->update = babTranslate("Update Info");
			$this->title = babTranslate("Change user info");
			}
		}

	$db = new db_mysql();
	$req = "select * from users where id='$BAB_SESS_USERID'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);

	$temp = new temp($firstname, $lastname, $nickname, $email);
	$body->babecho(	babPrintTemplate($temp,"options.html", "changeuserinfo"));
	}

function changeLanguage()
	{
	global $body;

	class tempa
		{
		var $title;
        var $count;
        var $userlang;
        var $langval;
        var $langselected;
        var $langname;
		var $update;

        var $arrfiles = array();

		function tempa()
			{
        	global $BAB_SESS_USERID;
			$this->title = babTranslate("Prefered language");
			$this->update = babTranslate("Update Language");
            $this->count = 0;

            $db = new db_mysql();
            $req = "select * from users where id='$BAB_SESS_USERID'";
            $res = $db->db_query($req);
            if( $res && $db->db_num_rows($res) > 0 )
                {
    			$arr = $db->db_fetch_array($res);
                $this->userlang = $arr[lang];
                }
            else
                $this->userlang = "";
           
            if( $this->userlang == "")
                $this->userlang = $GLOBAL[babLanguage];

            $this->title .= " : ".$this->userlang;

            $h = opendir('lang/'); 
            while ( $file = readdir($h))
                { 
                if ($file != "." && $file != "..")
                    {
                    if( eregi("lang-([^.]*)", $file, $regs))
                        {
                        if( $file == "lang-".$regs[1].".xml")
                            $this->arrfiles[] = $regs[1]; 
                        }
                    } 
                }
            closedir($h);
            $this->count = count($this->arrfiles);
			}

		function getnextlang()
			{
			static $i = 0;
			if( $i < $this->count)
				{
                $this->langname = $this->arrfiles[$i];
                $this->langval = $this->arrfiles[$i];
                if( $this->userlang == $this->langname )
                    $this->langselected = "selected";
                else
                    $this->langselected = "";
				$i++;
				return true;
				}
			else
				return false;
			}

		}


    $tempa = new tempa();
    $body->babecho(	babPrintTemplate($tempa,"options.html", "changelang"));

    }


function updatePassword($oldpwd, $newpwd1, $newpwd2)
	{
	global $body, $babInstallPath;
	include $babInstallPath."admin/register.php";

	if( empty($oldpwd) || empty($newpwd1) || empty($newpwd2))
		{
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


function updateLanguage($lang)
	{
    global $BAB_SESS_USERID;
	if( !empty($lang))
		{
        $db = new db_mysql();
        $req = "update users set lang='".$lang."' where id='".$BAB_SESS_USERID."'";
        $res = $db->db_query($req);
		}
	Header("Location: index.php?tg=options&idx=global");
	}

function updateUserInfo($password, $firstname, $lastname, $nickname, $email)
	{
	global $body, $BAB_HASH_VAR, $BAB_SESS_NICKNAME, $BAB_SESS_USERID, $BAB_SESS_USER, $BAB_SESS_EMAIL;

	$password = strtolower($password);
	$req = "select * from users where nickname='".$BAB_SESS_NICKNAME."' and password='". md5($password) ."'";
	$db = new db_mysql();
	$res = $db->db_query($req);
	if (!$res || $db->db_num_rows($res) < 1)
		{
		$body->msgerror = babTranslate("Passsssword incorrect");
		return false;
		}
	else
		{
		$arr = $db->db_fetch_array($res);
		if( empty($firstname) || empty($lastname) || empty($email))
			{
			$body->msgerror = babTranslate( "You must complete all fields !!");
			return false;
			}

		if ( !isEmailValid($email))
			{
			$body->msgerror = babTranslate("Your email is not valid !!");
			return false;
			}
		
		if( $BAB_SESS_NICKNAME != $nickname )
			{
			$req = "select * from users where nickname='".$nickname."'";	
			$res = $db->db_query($req);
			if( $db->db_num_rows($res) > 0)
				{
				$body->msgerror = babTranslate("This nickname already exists !!");
				return false;
				}
			}

		if( $arr[firstname] != $firstname || $arr[lastname] != $lastname )
			{
			if( getUserId($firstname. " " . $lastname) == 0)
				{
				$body->msgerror = babTranslate("Firstname and Lastname already exists !!");
				return false;
				}
			}
		$hash=md5($nickname.$BAB_HASH_VAR);
		$replace = array( " " => "", "-" => "");
		$hashname = md5(strtolower(strtr($firstname.$lastname, $replace)));
		$req = "update users set firstname='".$firstname."', lastname='".$lastname."', nickname='".$nickname."', email='".$email."', confirm_hash='".$hash."', hashname='".$hashname."' where id='".$BAB_SESS_USERID."'";
		$res = $db->db_query($req);
		$BAB_SESS_NICKNAME = $nickname;
		$BAB_SESS_USER = composeName($firstname, $lastname);
		$BAB_SESS_EMAIL = $email;
		$BAB_SESS_HASHID = $hash;
		return true;
		}
	}


/* main */
if(!isset($idx))
	{
	$idx = "global";
	}
$body->msgerror = "";

if( isset($update))
	{
    switch ($update)
        {
        case "password":
        	updatePassword($oldpwd, $newpwd1, $newpwd2);
            break;
        case "lang":
        	updateLanguage($lang);
            break;
        case "userinfo":
        	if(updateUserInfo($password, $firstname, $lastname, $nickname, $email))
				{
				unset($firstname);
				unset($lastname);
				unset($nickname);
				unset($email);
				}
            break;
        }
	}


if( !isset($firstname) &&  !isset($lastname) && !isset($nickname) && !isset($email))
	{
	$db = new db_mysql();
	$req = "select * from users where id='".$BAB_SESS_USERID."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$firstname = $arr[firstname];
		$lastname = $arr[lastname];
		$nickname = $arr[nickname];
		$email = $arr[email];
		}
	}

switch($idx)
	{

	default:
	case "global":
		//$body->title = babTranslate("");
		$idcal = getCalendarid($BAB_SESS_USERID, 1);
		changeUserInfo($firstname, $lastname, $nickname, $email);
		changePassword();
		changeLanguage();
		$body->addItemMenu("global", babTranslate("Options"), $GLOBALS[babUrl]."index.php?tg=options&idx=global");
		if( (getCalendarId(1, 2) != 0  || getCalendarId(getPrimaryGroupId($BAB_SESS_USERID), 2) != 0) && $idcal != 0 )
		{
			$body->addItemMenu("calendar", babTranslate("Calendar"), $GLOBALS[babUrl]."index.php?tg=calopt&idx=options");
		}
		if( mailAccessLevel())
			$body->addItemMenu("options", babTranslate("Mail"), $GLOBALS[babUrl]."index.php?tg=mailopt&idx=listacc");
		break;
	}
$body->setCurrentItemMenu($idx);

?>