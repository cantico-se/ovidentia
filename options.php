<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";
include $babInstallPath."admin/register.php";

function changePassword()
	{
	global $babBody,$BAB_SESS_USERID;
	class tempb
		{
		var $oldpwd;
		var $newpwd;
		var $renewpwd;
		var $update;
		var $title;

		function tempb()
			{
			$this->oldpwd = bab_translate("Old Password");
			$this->newpwd = bab_translate("New Password");
			$this->renewpwd = bab_translate("Retype New Password");
			$this->update = bab_translate("Update Password");
			$this->title = bab_translate("Change password");
			}
		}

	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_USERS_TBL." where id='$BAB_SESS_USERID'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);

	if( $arr['changepwd'] != 0)
		{
		$tempb = new tempb();
		$babBody->babecho(	bab_printTemplate($tempb,"options.html", "changepassword"));
		}
	else
		$babBody->msgerror = bab_translate("Sorry, You cannot change your password. Please contact administrator");
	}

function changeUserInfo($firstname, $middlename, $lastname, $nickname, $email)
	{
	global $babBody,$BAB_SESS_USERID;
	class temp
		{
		var $firstname;
		var $lastname;
		var $nickname;
		var $email;
		var $middlename;
		var $firstnameval;
		var $lastnameval;
		var $nicknameval;
		var $middlenameval;
		var $emailval;

		var $password;
		var $update;
		var $title;

		function temp($firstname, $middlename, $lastname, $nickname, $email)
			{
			$this->firstnameval = $firstname != ""? $firstname: "";
			$this->lastnameval = $lastname != ""? $lastname: "";
			$this->nicknameval = $nickname != ""? $nickname: "";
			$this->middlenameval = $middlename != ""? $middlename: "";
			$this->emailval = $email != ""? $email: "";
			$this->firstname = bab_translate("First Name");
			$this->lastname = bab_translate("Last Name");
			$this->nickname = bab_translate("Nickname");
			$this->middlename = bab_translate("Middle Name");
			$this->email = bab_translate("Email");

			$this->password = bab_translate("Password");
			$this->update = bab_translate("Update Info");
			$this->title = bab_translate("Change user info");
			}
		}

	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_USERS_TBL." where id='$BAB_SESS_USERID'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);

	$temp = new temp($firstname, $middlename, $lastname, $nickname, $email);
	$babBody->babecho(	bab_printTemplate($temp,"options.html", "changeuserinfo"));
	}

function changeLanguage()
	{
	global $babBody;

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
			$this->title = bab_translate("Prefered language");
			$this->update = bab_translate("Update Language");
            $this->count = 0;

            $db = $GLOBALS['babDB'];
            $req = "select * from ".BAB_USERS_TBL." where id='$BAB_SESS_USERID'";
            $res = $db->db_query($req);
            if( $res && $db->db_num_rows($res) > 0 )
                {
    			$arr = $db->db_fetch_array($res);
                $this->userlang = $arr['lang'];
                }
            else
                $this->userlang = "";
           
            if( $this->userlang == "")
                $this->userlang = $GLOBALS['babLanguage'];

            $this->title .= " : ".$this->userlang;

            $h = opendir($GLOBALS['babInstallPath']."lang/"); 
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
			sort($this->arrfiles);
			reset($this->arrfiles);
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
    $babBody->babecho(	bab_printTemplate($tempa,"options.html", "changelang"));

    }

function changeSkin($skin)
	{
	global $babBody;

	class tempc
		{
		var $title;
        var $count;
        var $userskin;
        var $userstyle;
        var $skinval;
        var $skinselected;
        var $skinname;
		var $update;

        var $arrskins = array();
        var $arrstyles = array();

		var $cntskins;
		var $cntstyles;
		var $skin;

		function tempc($skin)
			{
        	global $BAB_SESS_USERID;
			$this->title = bab_translate("Prefered skin");
			$this->update = bab_translate("Update Skin");
            $this->cntskins = 0;
            $this->cntstyles = 0;

			$db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_USERS_TBL." where id='$BAB_SESS_USERID'";
			$res = $db->db_query($req);
			if( $res && $db->db_num_rows($res) > 0 )
				{
				$arr = $db->db_fetch_array($res);
				$this->userskin = $arr['skin'];
				$this->userstyle = $arr['style'];
				}
			else
				{
				$this->userskin = "";
				$this->userstyle = "";
				}
		   
			if( $this->userskin == "")
				$this->userskin = $GLOBALS['babSkin'];

			if( $this->userstyle == "")
				$this->userstyle = $GLOBALS['babStyle'];

			$this->title .= " : ".$this->userskin ." / ". $this->userstyle;

			if(!isset($skin) || empty($skin))
				$this->skin = $this->userskin;
			else
				$this->skin = $skin;

			if( is_dir($GLOBALS['babInstallPath']."skins/"))
				{
				$h = opendir($GLOBALS['babInstallPath']."skins/"); 
				while ( $file = readdir($h))
					{ 
					if ($file != "." && $file != "..")
						{
						if( is_dir($GLOBALS['babInstallPath']."skins/".$file))
							{
								$this->arrskins[] = $file; 
							}
						} 
					}
				closedir($h);
				$this->cntskins = count($this->arrskins);
				}
            $this->skselectedindex = 0;
            $this->stselectedindex = 0;
			}

		function getnextskin()
			{
			static $i = 0;
			if( $i < $this->cntskins)
				{
				$this->iindex = $i;
                $this->skinname = $this->arrskins[$i];
                $this->skinval = $this->arrskins[$i];
                if( $this->skinname == $this->skin )
					{
	                $this->skselectedindex = $i;
                    $this->skinselected = "selected";
					}
                else
                    $this->skinselected = "";

				$this->arrstyles = array();
				if( is_dir("skins/".$this->skinname."/styles/"))
					{
					$h = opendir("skins/".$this->skinname."/styles/"); 
					while ( $file = readdir($h))
						{ 
						if ($file != "." && $file != "..")
							{
							if( is_file("skins/".$this->skinname."/styles/".$file))
								{
									if( strtolower(substr(strrchr($file, "."), 1)) == "css" )
										{
										$this->arrstyles[] = $file;
										}
								}
							} 
						}
					closedir($h);
					}

				if( is_dir($GLOBALS['babInstallPath']."skins/".$this->skinname."/styles/"))
					{
					$h = opendir($GLOBALS['babInstallPath']."skins/".$this->skinname."/styles/"); 
					while ( $file = readdir($h))
						{ 
						if ($file != "." && $file != "..")
							{
							if( is_file($GLOBALS['babInstallPath']."skins/".$this->skinname."/styles/".$file))
								{
									if( strtolower(substr(strrchr($file, "."), 1)) == "css" )
										{
										if( count($this->arrstyles) == 0 || !in_array($file, $this->arrstyles) )
											$this->arrstyles[] = $file;
										}
								}
							} 
						}
					closedir($h);
					}
				$this->cntstyles = count($this->arrstyles);
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}

		function getnextstyle()
			{
			static $j = 0;
			if( $j < $this->cntstyles)
				{
                $this->stylename = $this->arrstyles[$j];
                $this->styleval = $this->arrstyles[$j];
                if( $this->skinname == $this->skin && $this->userstyle == $this->styleval)
					$this->stselectedindex = $j;
				$j++;
				return true;
				}
			else
				{
				$j = 0;
				return false;
				}
			}
		
		}


    $tempc = new tempc($skin);
    $babBody->babecho(	bab_printTemplate($tempc,"options.html", "changeskin"));
    }

function userChangePassword($oldpwd, $newpwd)
	{
	global $babBody, $BAB_SESS_USERID, $BAB_SESS_HASHID;

	$new_password1=strtolower($newpwd);
	$sql="select * from ".BAB_USERS_TBL." where id='". $BAB_SESS_USERID ."'";
	$db = $GLOBALS['babDB'];
	$result=$db->db_query($sql);
	if ($db->db_num_rows($result) < 1)
		{
		$babBody->msgerror = bab_translate("User not found or bad password");
		return false;
		}
	else
		{
		$arr = $db->db_fetch_array($result);
		$oldpwd2 = md5(strtolower($oldpwd));
		if( $oldpwd2 == $arr['password'])
			{
			$sql="update ".BAB_USERS_TBL." set password='". md5(strtolower($newpwd)). "' ".
				"where id='". $BAB_SESS_USERID . "'";
			$result=$db->db_query($sql);
			if ($db->db_affected_rows() < 1)
				{
				$babBody->msgerror = bab_translate("Nothing Changed");
				return false;
				}
			else
				{
				$babBody->msgerror = bab_translate("Password Changed");
				return true;
				}
			}
		else
			{
			$babBody->msgerror = bab_translate("ERROR: Old password incorrect !!");
			return false;
			}
		}
	}

function updatePassword($oldpwd, $newpwd1, $newpwd2)
	{
	global $babBody, $babInstallPath;

	if( empty($oldpwd) || empty($newpwd1) || empty($newpwd2))
		{
		$babBody->msgerror = bab_translate("You must complete all fields !!");
		return;
		}
	if( $newpwd1 != $newpwd2)
		{
		$babBody->msgerror = bab_translate("Passwords not match !!");
		return;
		}
	if ( strlen($newpwd1) < 6 )
		{
		$babBody->msgerror = bab_translate("Password must be at least 6 characters !!");
		return;
		}

	userChangePassword( $oldpwd, $newpwd1);
	}


function updateLanguage($lang)
	{
    global $BAB_SESS_USERID;
	if( !empty($lang))
		{
        $db = $GLOBALS['babDB'];
        $req = "update ".BAB_USERS_TBL." set lang='".$lang."' where id='".$BAB_SESS_USERID."'";
        $res = $db->db_query($req);
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=options&idx=global");
	}

function updateSkin($skin, $style)
	{
    global $BAB_SESS_USERID;
	if( !empty($skin))
		{
        $db = $GLOBALS['babDB'];
        $req = "update ".BAB_USERS_TBL." set skin='".$skin."', style='".$style."' where id='".$BAB_SESS_USERID."'";
        $res = $db->db_query($req);
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=options&idx=global");
	}

function updateUserInfo($password, $firstname, $middlename, $lastname, $nickname, $email)
	{
	global $babBody, $BAB_HASH_VAR, $BAB_SESS_NICKNAME, $BAB_SESS_USERID, $BAB_SESS_USER, $BAB_SESS_EMAIL;

	$password = strtolower($password);
	$req = "select id from ".BAB_USERS_TBL." where nickname='".$BAB_SESS_NICKNAME."' and password='". md5($password) ."'";
	$db = $GLOBALS['babDB'];
	$res = $db->db_query($req);
	if (!$res || $db->db_num_rows($res) < 1)
		{
		$babBody->msgerror = bab_translate("Password incorrect");
		return false;
		}
	else
		{
		$arr = $db->db_fetch_array($res);
		if( empty($firstname) || empty($lastname) || empty($email))
			{
			$babBody->msgerror = bab_translate( "You must complete all fields !!");
			return false;
			}

		if ( !bab_isEmailValid($email))
			{
			$babBody->msgerror = bab_translate("Your email is not valid !!");
			return false;
			}
		
		if( $BAB_SESS_NICKNAME != $nickname )
			{
			$req = "select id from ".BAB_USERS_TBL." where nickname='".$nickname."'";	
			$res = $db->db_query($req);
			if( $db->db_num_rows($res) > 0)
				{
				$babBody->msgerror = bab_translate("This nickname already exists !!");
				return false;
				}
			}

		$replace = array( " " => "", "-" => "");
		$hashname = md5(strtolower(strtr($firstname.$middlename.$lastname, $replace)));
		$query = "select id from ".BAB_USERS_TBL." where hashname='".$hashname."' and id!='".$BAB_SESS_USERID."'";	
		$res = $db->db_query($query);
		if( $db->db_num_rows($res) > 0)
			{
			$babBody->msgerror = bab_translate("Firstname and Lastname already exists !!");
			return false;
			}

		$hash=md5($nickname.$BAB_HASH_VAR);
		$req = "update ".BAB_USERS_TBL." set firstname='".$firstname."', lastname='".$lastname."', nickname='".$nickname."', email='".$email."', confirm_hash='".$hash."', hashname='".$hashname."' where id='".$BAB_SESS_USERID."'";
		$res = $db->db_query($req);

		$req = "update ".BAB_DBDIR_ENTRIES_TBL." set sn='".$firstname."', mn='".$middlename."', givenname='".$lastname."', email='".$email."' where id_directory='0' and id_user='".$BAB_SESS_USERID."'";
		$res = $db->db_query($req);

		$BAB_SESS_NICKNAME = $nickname;
		$BAB_SESS_USER = bab_composeUserName($firstname, $lastname);
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

if(!isset($skin))
	{
	$skin = "";
	}

$babBody->msgerror = "";

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
        case "skin":
        	updateSkin($skin, $style);
            break;
        case "userinfo":
        	if(updateUserInfo($password, $firstname, $middlename, $lastname, $nickname, $email))
				{
				unset($firstname);
				unset($lastname);
				unset($middlename);
				unset($nickname);
				unset($email);
				}
            break;
        }
	}

function updateStateSection($c, $w, $closed)
	{
	global $HTTP_REFERER, $BAB_SESS_USERID;

	if( !empty($BAB_SESS_USERID))
		{
		$db = $GLOBALS['babDB'];
		$req = "select * from ".BAB_SECTIONS_STATES_TBL." where type='".$w."' and id_section='".$c."' and  id_user='".$BAB_SESS_USERID."'";
		$res = $db->db_query($req);
		if( $res && $db->db_num_rows($res) > 0 )
			$req = "update ".BAB_SECTIONS_STATES_TBL." set closed='".$closed."' where type='".$w."' and id_section='".$c."' and  id_user='".$BAB_SESS_USERID."'";
		else
			$req = "insert into ".BAB_SECTIONS_STATES_TBL." (id_section, closed, type, id_user) values ('".$c."', '".$closed."', '".$w."', '".$BAB_SESS_USERID."')";

		$db->db_query($req);
		}

	Header("Location: ". $HTTP_REFERER);
	}

if( !isset($firstname) &&  !isset($middlename) &&  !isset($lastname) && !isset($nickname) && !isset($email))
	{
	$db = $GLOBALS['babDB'];
	$req = "select sn, mn, givenname, email from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='0' and id_user='".$BAB_SESS_USERID."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$firstname = $arr['sn'];
		$lastname = $arr['givenname'];
		$middlename = $arr['mn'];
		$email = $arr['email'];
		$nickname = bab_getUserNickname($BAB_SESS_USERID);
		}
	}

switch($idx)
	{
	case "cb":
		updateStateSection($s, $w, "Y");
		break;

	case "ob":
		updateStateSection($s, $w, "N");
		break;

	default:
	case "global":
		//$babBody->title = bab_translate("");
		$idcal = bab_getCalendarId($BAB_SESS_USERID, 1);
		changeUserInfo($firstname, $middlename, $lastname, $nickname, $email);
		changePassword();
		changeSkin($skin);
		changeLanguage();
		$babBody->addItemMenu("global", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=options&idx=global");
		if( (bab_getCalendarId(1, 2) != 0  || bab_getCalendarId(bab_getPrimaryGroupId($BAB_SESS_USERID), 2) != 0) && $idcal != 0 )
		{
			$babBody->addItemMenu("calendar", bab_translate("Calendar"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options");
		}
		if( bab_mailAccessLevel())
			$babBody->addItemMenu("options", bab_translate("Mail"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=listacc");
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>
