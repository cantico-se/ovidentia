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
include $babInstallPath."admin/register.php";

function changePassword($msgerror)
	{
	global $babBody,$BAB_SESS_USERID;
	class tempb
		{
		var $oldpwd;
		var $newpwd;
		var $renewpwd;
		var $update;
		var $title;

		function tempb($msgerror,$changepwd)
			{
			$this->changepwd = $changepwd!=0 ? true : false;
			$this->oldpwd = bab_translate("Old Password");
			$this->newpwd = bab_translate("New Password");
			$this->renewpwd = bab_translate("Retype New Password");
			$this->update = bab_translate("Update Password");
			$this->title = bab_translate("Change password");
			$this->msgerror = $this->changepwd ? ($msgerror!='' ? $msgerror : false ) : bab_translate("Sorry, You cannot change your password. Please contact administrator");
			}
		}

	$db = $GLOBALS['babDB'];
	$req = "select * from ".BAB_USERS_TBL." where id='$BAB_SESS_USERID'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);

	$tempb = new tempb($msgerror,$arr['changepwd']);
	die(bab_printTemplate($tempb,"options.html", "changepassword"));
	}


function changePasswordUnload($msg)
	{
	class temp
		{
		var $message;
		var $close;
		function temp($msg)
			{
			$this->message = $msg;
			$this->close = bab_translate("Close");
			}
		}
	$temp = new temp($msg);
	die(bab_printTemplate($temp,"options.html", "changePasswordUnload"));
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

	$temp = new temp($firstname, $middlename, $lastname, $nickname, $email);
	$babBody->babecho(	bab_printTemplate($temp,"options.html", "changeuserinfo"));
	}

function changeNickname($nickname)
	{
	global $babBody,$BAB_SESS_USERID;
	class temp
		{
		var $nickname;
		var $nicknameval;
		var $password;
		var $update;
		var $bupdateuserinfo;
		var $updateuserinfo;
		var $urldbmod;

		function temp($nickname)
			{
			global $babDB;
			
			

			$this->bupdateuserinfo = false;

			list($id, $allowuu) = $babDB->db_fetch_array($babDB->db_query("select id, user_update from ".BAB_DB_DIRECTORIES_TBL." where id_group='1'"));
			if( $allowuu == "N")
				{
				$res = $babDB->db_query("select dbd.id from ".BAB_DB_DIRECTORIES_TBL." dbd join ".BAB_USERS_GROUPS_TBL." ug where ug.id_object='".$GLOBALS['BAB_SESS_USERID']."' and ug.id_group=dbd.id_group and dbd.user_update='Y'");
				if( $res && $babDB->db_num_rows($res) > 0 )
					$allowuu = "Y";
				}

			if( $allowuu == "Y")
				{
				list($idu) = $babDB->db_fetch_array($babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='0' and id_user='".$GLOBALS['BAB_SESS_USERID']."'"));
				$this->bupdateuserinfo = true;
				$this->urldbmod = $GLOBALS['babUrlScript']."?tg=directory&idx=dbmod&id=".$id."&idu=".$idu."&refresh=1";
				$this->updateuserinfo = bab_translate("Update personal informations");
				}

			$req="select s.change_nickname,s.change_password,u.changepwd from ".BAB_SITES_TBL." s LEFT JOIN ".BAB_USERS_TBL." u ON u.id='".$GLOBALS['BAB_SESS_USERID']."' where name='".addslashes($GLOBALS['babSiteName'])."'";
			$res=$babDB->db_query($req);
			$arr = $babDB->db_fetch_array($res);
			$this->changenickname = $arr['change_nickname'] == 'Y' ? true : false;
			$this->changepassword = $arr['change_password'] == 'Y' && $arr['changepwd'] == 1 ? bab_translate("Update Password") : false;
			$this->urlchangepassword = $GLOBALS['babUrlScript']."?tg=options&idx=changePassword";
			$this->nicknameval = $nickname != ""? $nickname: "";
			$this->nickname = bab_translate("Nickname");
			$this->password = bab_translate("Password");
			$this->update = bab_translate("Update nickname");
			}
		}

	$temp = new temp($nickname);
	$babBody->babecho(	bab_printTemplate($temp,"options.html", "changenickname"));
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
		var $langfiltertxt;
		var $langfilterval;
		var $langfilterselected;
		var $userlangfilter;

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
			$this->userlangfilter = $arr['langfilter'];
			$this->langfiltertxt = bab_translate("Language filter") . " : " . $GLOBALS['babLangFilter']->convertFilterToStr($this->userlangfilter);
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
			} // function getnextlang

		function getnextlangfilter()
		{
			static $i = 0;
			if($i < $GLOBALS['babLangFilter']->countFilters())
			{
				$this->langfilterval = 
					$GLOBALS['babLangFilter']->getFilterStr($i);
				if($this->userlangfilter == $i)
					{$this->langfilterselected = 'selected';}
				else
					{$this->langfilterselected = '';}
				$i++;
				return true;
		}
			else return false;
		} //getnextlangfilter	

		} //class tempa


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
			$this->title_style = bab_translate("Prefered style");
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

			$this->title .= " : ".$this->userskin;
			$this->title_style .= " : ".substr($this->userstyle,0,strrpos($this->userstyle, "."));

			if(!isset($skin) || empty($skin))
				$this->skin = $this->userskin;
			else
				$this->skin = $skin;

			if( is_dir("skins/"))
				{
				$h = opendir("skins/"); 
				while ( $file = readdir($h))
					{ 
					if ($file != "." && $file != "..")
						{
						if( is_dir("skins/".$file))
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

	if( empty($GLOBALS['BAB_SESS_USERID']))
		return true;

	if( empty($oldpwd) || empty($newpwd1) || empty($newpwd2))
		{
		return bab_translate("You must complete all fields !!");
		}
	if( $newpwd1 != $newpwd2)
		{
		return bab_translate("Passwords not match !!");
		}
	if ( strlen($newpwd1) < 6 )
		{
		return bab_translate("Password must be at least 6 characters !!");
		}

	userChangePassword( $oldpwd, $newpwd1);
	return false;
	}


function updateLanguage($lang, $langfilter)
	{
    global $BAB_SESS_USERID;
	if( !empty($lang) && !empty($BAB_SESS_USERID))
		{
        $db = $GLOBALS['babDB'];
		$req = "update ".BAB_USERS_TBL." set lang='".$lang."', langfilter='" .$langfilter. "' where id='".$BAB_SESS_USERID."'";
        $res = $db->db_query($req);
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=options&idx=global");
	}

function updateSkin($skin, $style)
	{
    global $BAB_SESS_USERID;
	if( !empty($skin) && !empty($BAB_SESS_USERID))
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

	if( empty($GLOBALS['BAB_SESS_USERID']))
		return false;

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

		$req = "update ".BAB_DBDIR_ENTRIES_TBL." set givenname='".$firstname."', mn='".$middlename."', sn='".$lastname."', email='".$email."' where id_directory='0' and id_user='".$BAB_SESS_USERID."'";
		$res = $db->db_query($req);

		$BAB_SESS_NICKNAME = $nickname;
		$BAB_SESS_USER = bab_composeUserName($firstname, $lastname);
		$BAB_SESS_EMAIL = $email;
		$BAB_SESS_HASHID = $hash;
		return true;
		}
	}


function updateNickname($password, $nickname)
	{
	global $babBody, $BAB_HASH_VAR, $BAB_SESS_NICKNAME, $BAB_SESS_USERID, $BAB_SESS_USER, $BAB_SESS_HASHID;

	if( empty($GLOBALS['BAB_SESS_USERID']))
		return false;

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
		if( empty($nickname))
			{
			$babBody->msgerror = bab_translate( "You must complete all fields !!");
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

		$hash=md5($nickname.$BAB_HASH_VAR);
		$req = "update ".BAB_USERS_TBL." set nickname='".$nickname."', hashname='".$hash."', confirm_hash='".$hash."' where id='".$BAB_SESS_USERID."'";
		$res = $db->db_query($req);

		$BAB_SESS_NICKNAME = $nickname;
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

if(!isset($msgerror))
	$msgerror = '';

$babBody->msgerror = "";

if( isset($update))
	{
    switch ($update)
        {
        case "password":
			$msgerror = updatePassword($oldpwd, $newpwd1, $newpwd2);
        	if (!$msgerror)
				changePasswordUnload(bab_translate("Your password has been modified"));
			else
				$idx = "changePassword";
            break;
        case "lang":
        	updateLanguage($lang, $babLangFilter->convertFilterToInt($langfilter));
            break;
        case "skin":
        	updateSkin($skin, $style);
            break;
        case "nickname":
        	if(updateNickname($password, $nickname))
				{
				unset($nickname);
				}
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

if( !isset($firstname) &&  !isset($middlename) &&  !isset($lastname) && !isset($nickname) && !isset($email) && $BAB_SESS_USERID != '')
	{
	$db = $GLOBALS['babDB'];
	$req = "select sn, mn, givenname, email from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='0' and id_user='".$BAB_SESS_USERID."'";
	$res = $db->db_query($req);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		$firstname = $arr['givenname'];
		$lastname = $arr['sn'];
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

	case "changePassword":
		$req="select * from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'";
		$res=$babDB->db_query($req);
		$arr = $babDB->db_fetch_array($res);
		if ($arr['change_password'] == 'Y' ) changePassword($msgerror);
		break;
	case "changePasswordUnload":
		changePasswordUnload($msg);
		break;
	default:
	case "global":
		
		$babBody->title = bab_translate("Options");
		$idcal = bab_getCalendarId($BAB_SESS_USERID, 1);
		changeNickname($nickname);
		changeSkin($skin);
		changeLanguage();
		$babBody->addItemMenu("global", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=options&idx=global");
		if( $idcal != 0 || $babBody->calaccess || bab_calendarAccess() != 0 )
		{
			$babBody->addItemMenu("calendar", bab_translate("Calendar"), $GLOBALS['babUrlScript']."?tg=calopt&idx=options");
		}
		if( bab_mailAccessLevel())
			$babBody->addItemMenu("options", bab_translate("Mail"), $GLOBALS['babUrlScript']."?tg=mailopt&idx=listacc");
		$babBody->addItemMenu("list", bab_translate("Sections"), $GLOBALS['babUrlScript']."?tg=sectopt&idx=list");
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>