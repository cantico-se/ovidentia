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
/**
* @internal SEC1 NA 18/12/2006 FULL
*/
include_once 'base.php';
require_once dirname(__FILE__).'/utilit/registerglobals.php';
include_once $babInstallPath.'admin/register.php';

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
			global $babBody, $babDB;

			$res=$babDB->db_query("select changepwd, db_authentification from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
			$arr = $babDB->db_fetch_array($res);
			if( $babBody->babsite['change_password'] == 'Y' && $arr['changepwd'] == 1 )
				{
				if( $babBody->babsite['authentification'] != BAB_AUTHENTIFICATION_OVIDENTIA && empty($babBody->babsite['ldap_encryptiontype']) && $arr['db_authentification'] == 'N')
					{
					$this->changepwd = false;
					}
				else
					{
					$this->changepwd = true;
					}
				}
			else
				{
				$this->changepwd = false;
				}

			if( $this->changepwd )
				{
				$this->oldpwd = bab_translate("Old Password");
				$this->newpwd = bab_translate("New Password");
				$this->renewpwd = bab_translate("Retype New Password");
				$this->update = bab_translate("Update Password");
				$this->title = bab_translate("Change password");
				}
			}
		}

	$tempb = new tempb();
	$babBody->babPopup(bab_printTemplate($tempb,"options.html", "changepassword"));
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
	global $babBody;
	$babBody->babPopup(bab_printTemplate($temp,"options.html", "changePasswordUnload"));
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
			$this->firstnameval = $firstname != ""? bab_toHtml($firstname): "";
			$this->lastnameval = $lastname != ""? bab_toHtml($lastname): "";
			$this->nicknameval = $nickname != ""? bab_toHtml($nickname): "";
			$this->middlenameval = $middlename != ""? bab_toHtml($middlename): "";
			$this->emailval = $email != ""? bab_toHtml($email): "";
			$this->firstname = bab_translate("First Name");
			$this->lastname = bab_translate("Last Name");
			$this->nickname = bab_translate("Login ID");
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
			global $babBody, $babDB;

			$this->bupdateuserinfo = false;
			list($id, $allowuu) = $babDB->db_fetch_array($babDB->db_query("select id, user_update from ".BAB_DB_DIRECTORIES_TBL." where id_group='1'"));
			if( $allowuu == "N")
				{
				$res = $babDB->db_query("select dbd.id from ".BAB_DB_DIRECTORIES_TBL." dbd join ".BAB_USERS_GROUPS_TBL." ug where ug.id_object='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and ug.id_group=dbd.id_group and dbd.user_update='Y'");
				if( $res && $babDB->db_num_rows($res) > 0 )
					$allowuu = "Y";
				}

			if( $allowuu == "Y")
				{
				list($idu) = $babDB->db_fetch_array($babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='0' and id_user='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'"));
				$this->bupdateuserinfo = true;
				$this->urldbmod = $GLOBALS['babUrlScript']."?tg=directory&idx=dbmod&id=".$id."&refresh=1";
				$this->updateuserinfo = bab_translate("Update personal informations");
				}

			$res=$babDB->db_query("select changepwd, db_authentification from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
			$arr = $babDB->db_fetch_array($res);
			$this->changenickname = $babBody->babsite['change_nickname'] == 'Y' ? true : false;
			if( $babBody->babsite['change_password'] == 'Y' && $arr['changepwd'] == 1 )
				{
				if( $babBody->babsite['authentification'] != BAB_AUTHENTIFICATION_OVIDENTIA && empty($babBody->babsite['ldap_encryptiontype']) && $arr['db_authentification'] == 'N')
					{
					$this->changepassword = false;
					}
				else
					{
					$this->changepassword = bab_translate("Update Password");
					}
				}
			else
				{
				$this->changepassword = false;
				}
			$this->urlchangepassword = bab_toHtml($GLOBALS['babUrlScript']."?tg=options&idx=changePassword");
			$this->nicknameval = $nickname != ""? $nickname: "";
			$this->nickname = bab_translate("Login ID");
			$this->password = bab_translate("Password");
			$this->update = bab_translate("Update login ID");
			}
		}

	$temp = new temp($nickname);
	$babBody->babecho(	bab_printTemplate($temp,"options.html", "changenickname"));
	}


function changeRegionalSettings()
	{
	global $babBody,$BAB_SESS_USERID;
	class changeRegionalSettingsCls
		{
		var $date_lformat_title;
		var $date_lformat_val;
		var $date_sformat_title;
		var $date_sformat_val;
		var $time_format_title;
		var $time_format_val;
		var $update;
		var $regsettings_title;

		function changeRegionalSettingsCls()
			{
			global $babBody, $babDB;
			$this->date_lformat_title = bab_translate("Long date format");
			$this->date_sformat_title = bab_translate("Short date format");
			$this->time_format_title = bab_translate("Time format");
			$this->regsettings_title = bab_translate("Date and Time formats");

			$res = $babDB->db_query("select date_shortformat, date_longformat, time_format from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
			$arr = $babDB->db_fetch_array($res);
			if( empty($arr['date_shortformat']))
				{
				$this->date_sformat_val = $babBody->babsite['date_shortformat'];
				}
			else
				{
				$this->date_sformat_val = $arr['date_shortformat'];
				}
			if( empty($arr['date_longformat']))
				{
				$this->date_lformat_val = $babBody->babsite['date_longformat'];
				}
			else
				{
				$this->date_lformat_val = $arr['date_longformat'];
				}
			if( empty($arr['time_format']))
				{
				$this->time_format_val = $babBody->babsite['time_format'];
				}
			else
				{
				$this->time_format_val = $arr['time_format'];
				}

			$this->update = bab_translate("Update");

			$formats = bab_getRegionalFormats();

			$this->arrlfdate = $formats['longDate'];
			$this->arrsfdate = $formats['shortDate'];
			$this->arrtime = $formats['hour'];
			}

		function getnextlongdate()
			{
			static $i = 0;
			if( $i < count($this->arrlfdate))
				{
                $this->dateval = bab_toHtml($this->arrlfdate[$i]);
                $this->datetxt = bab_toHtml($this->arrlfdate[$i].' : '.bab_formatDate( bab_getDateFormat($this->arrlfdate[$i]), mktime() ));
				$this->selected = $this->date_lformat_val === $this->arrlfdate[$i];
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextshortdate()
			{
			static $i = 0;
			if( $i < count($this->arrsfdate))
				{
                $this->dateval = bab_toHtml($this->arrsfdate[$i]);
                $this->datetxt = bab_toHtml($this->arrsfdate[$i].' : '.bab_formatDate( bab_getDateFormat($this->arrsfdate[$i]), mktime() ));
				$this->selected = $this->date_sformat_val === $this->arrsfdate[$i];
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnexttime()
			{
			static $i = 0;
			if( $i < count($this->arrtime))
				{
                $this->timeval = bab_toHtml($this->arrtime[$i]);
                $this->timetxt = bab_toHtml($this->arrtime[$i].' : '.date( bab_getTimeFormat($this->arrtime[$i])));
				$this->selected = $this->time_format_val === $this->arrtime[$i];
				$i++;
				return true;
				}
			else
				return false;
			}


		}

	$temp = new changeRegionalSettingsCls();
	$babBody->babecho(bab_printTemplate($temp,'options.html', 'regionalsettings'));
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
        	global $babDB, $BAB_SESS_USERID;
			$this->title = bab_translate("Prefered language");
			$this->update = bab_translate("Update Language");
 			$this->sitelangageval = bab_translate("Language of the site");
            $this->count = 0;

            $req = "select lang, langfilter from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
            $res = $babDB->db_query($req);
            if( $res && $babDB->db_num_rows($res) > 0 )
                {
    			$arr = $babDB->db_fetch_array($res);
                $this->userlang = bab_toHtml($arr['lang']);
                }
            else
				{
                $this->userlang = '';
				}
           

            $this->title .= " : ".$this->userlang;

			$this->arrfiles = bab_getAvailableLanguages();
            $this->count = count($this->arrfiles);
			bab_sort::sort($this->arrfiles);
			reset($this->arrfiles);
			$this->userlangfilter = $arr['langfilter'];
			$this->langfiltertxt = bab_translate("Language filter") . " : " . bab_getInstance('babLanguageFilter')->convertFilterToStr($this->userlangfilter);
			}

		function getnextlang()
			{
			static $i = 0;
			if( $i < $this->count)
				{
                $this->langname = bab_toHtml($this->arrfiles[$i]);
                $this->langval = bab_toHtml($this->arrfiles[$i]);
                if( $this->userlang == $this->langname )
					{
                    $this->langselected = 'selected';
					}
                else
					{
                    $this->langselected = '';
					}
				$i++;
				return true;
				}
			else
				return false;
			} // function getnextlang

		function getnextlangfilter()
		{
			static $i = 0;
			if($i < bab_getInstance('babLanguageFilter')->countFilters())
			{
				$this->langfilterval = 
					bab_toHtml(bab_getInstance('babLanguageFilter')->getFilterStr($i));
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

function changeSkin()
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

		function tempc()
			{
        	global $babDB, $BAB_SESS_USERID;
			$this->title = bab_translate("Prefered skin");
			$this->title_style = bab_translate("Prefered style");
			$this->update = bab_translate("Update Skin");
			$this->tsiteskin = bab_translate("Skin of the site");
            $this->cntskins = 0;
            $this->cntstyles = 0;

			$req = "select * from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
			$res = $babDB->db_query($req);
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$this->userskin = $arr['skin'];
				$this->userstyle = $arr['style'];
				}
			else
				{
				$this->userskin = '';
				$this->userstyle = '';
				}
		   
			if( $this->userskin == '')
				{
				$this->userskin = $GLOBALS['babSkin'];
				}

			if( $this->userstyle == '')
				{
				$this->userstyle = $GLOBALS['babStyle'];
				}
				
			$this->skin_in_bdd = $arr['skin'];

			$this->title .= ' : '.bab_toHtml($this->userskin);
			$this->title_style .= " : ".bab_toHtml(mb_substr($this->userstyle,0,mb_strrpos($this->userstyle, ".")));

			include_once $GLOBALS['babInstallPath'].'utilit/skinincl.php';
			$this->arrskins = bab_skin::getList();
			$this->cntskins = count($this->arrskins);
			bab_sort::natcasesort($this->arrskins);

			$this->skselectedindex = 0;
            $this->stselectedindex = 0;
			}

		function getnextskin() {
			static $i = 0;
			if (list(,$obj) = each($this->arrskins)) {
				$this->iindex = $i+1; /* 0 is the skin of the site (not referenced in $this->arrskins) */
                $this->skinname = bab_toHtml($obj->getName());
                $this->skinval = bab_toHtml($obj->getName());
                if ($this->skinname == $this->skin_in_bdd) {
	                $this->skselectedindex = $i+1; /* 0 is the skin of the site (not referenced in $this->arrskins) */
                    $this->skinselected = "selected";
				} else {
                    $this->skinselected = "";
				}
                
				$this->arrstyles = array_values($obj->getStyles());
				$this->cntstyles = count($this->arrstyles);
				$i++;
				return true;
			} else {
				$i = 0;
				reset($this->arrskins);
				return false;
			}
		}

		function getnextstyle() {
			static $j = 0;
			if ($j < $this->cntstyles) {
                $this->stylename = bab_toHtml($this->arrstyles[$j]);
                $this->styleval = bab_toHtml($this->arrstyles[$j]);
                if ($this->skinname == $this->skin && $this->userstyle == $this->styleval) {
					$this->stselectedindex = $j;
				}
				$j++;
				return true;
			} else {
				$j = 0;
				return false;
			}
		}
		
	}

    $tempc = new tempc();
    $babBody->babecho(bab_printTemplate($tempc,"options.html", "changeskin"));
}


function changeProfiles()
{
	global $babBody,$BAB_SESS_USERID;
	class changeProfilsCls
		{
		var $profileaccess = false;

		function changeProfilsCls()
			{
			global $babBody,$babDB;
			$this->profilestxt = bab_translate("Profiles");
			$this->updatetxt = bab_translate("Update");
			$this->requiredtxt = bab_translate("Those fields are required");
			$this->res = $babDB->db_query("select * from ".BAB_PROFILES_TBL."");
			$this->countpf = $babDB->db_num_rows($this->res);
			$this->altbg = true;
			}

		function getnextprofile(&$skip)
			{
			global $babDB;
			static $j = 0;
			if( $j < $this->countpf)
				{
				$arr = $babDB->db_fetch_array($this->res);
				if( bab_IsAccessValid(BAB_PROFILES_GROUPS_TBL, $arr['id']))
					{
					$this->profileaccess = true;
					$this->pname = bab_toHtml($arr['name']);
					$this->pdesc = bab_toHtml($arr['description']);
					$this->idprofile = bab_toHtml($arr['id']);
					$this->resgrp = $babDB->db_query("select gt.* from ".BAB_PROFILES_GROUPSSET_TBL." pgt left join ".BAB_GROUPS_TBL." gt on pgt.id_group=gt.id where pgt.id_object ='".$babDB->db_escape_string($arr['id'])."'");
					$this->countgrp = $babDB->db_num_rows($this->resgrp);
					if( $arr['multiplicity'] == 'Y' )
						{
						$this->bmultiplicity = true;
						}
					else
						{
						$this->bmultiplicity = false;
						}

					if( $arr['required'] == "Y")
						{
						$this->brequired = true;
						}
					else
						{
						$this->brequired = false;
						}
					}
				else
					{
					$skip = true;
					}
				$j++;
				return true;
				}
			else
				{
				$j = 0;
				return false;
				}
			}

		function getnextgrp()
			{
			global $babBody, $babDB;
			static $i = 0;	
			if( $i < $this->countgrp)
				{
				$arr = $babDB->db_fetch_array($this->resgrp);
				$this->altbg = !$this->altbg;
				$this->grpid = bab_toHtml($arr['id']);
				$this->grpname = bab_toHtml($arr['name']);
				$this->grpdesc = empty($arr['description'])? $arr['name']: $arr['description'];
				$this->grpdesc = bab_toHtml($this->grpdesc);
				if( (isset($GLOBALS["grpids".$this->idprofile]) && count($GLOBALS["grpids".$this->idprofile]) > 0 && in_array($arr['id'] , $GLOBALS["grpids".$this->idprofile])) || bab_isMemberOfGroup($arr['id']))
					{
					if( $this->bmultiplicity == true )
						{
						$this->grpcheck = 'checked';
						}
					else
						{
						$this->grpcheck = 'selected';
						}
					}
				else
					{
					$this->grpcheck = '';
					}
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}

		}

	$temp = new changeProfilsCls();
	$html = bab_printTemplate($temp,"options.html", "profileslist");
	if( $temp->profileaccess )
		{
		$babBody->babecho($html);
		}
}


function showUnavailability($iduser, $fromdate, $todate, $id_substitute)
	{
	global $babBody;

	class temp
		{
		var $firstname;

		function temp($iduser, $fromdate, $todate, $id_substitute)
			{
			global $babDB;

			$this->fromtxt = bab_translate("date_from");
			$this->totxt = bab_translate("date_to");
			$this->usertxt = bab_translate("Substitute");
			$this->deletetxt = bab_translate("Delete");
			$this->update = bab_translate("Update");
			$this->browseurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=lusers&idx=brow&cb=onUser");
			$this->urlfromdate = bab_calendarPopup("fdcb");
			$this->urltodate = bab_calendarPopup("tdcb");

			$this->iduser = bab_toHtml($iduser);

			$res = $babDB->db_query("select * from ".BAB_USERS_UNAVAILABILITY_TBL." where id_user='".$babDB->db_escape_string($iduser)."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
			
				$rr = explode('-', $arr['end_date']);
				$this->enddate_val = bab_toHtml(sprintf("%02s/%02s/%04s", $rr[2], $rr[1], $rr[0]));
				}
			else
				{
				$arr['start_date'] = '';
				$arr['end_date'] = '';
				$arr['id_substitute'] = '';
				}

			if( empty($fromdate))
				{
				if( empty($arr['start_date']))
					{
					$this->fromdate_val = '';
					}
				else
					{
					$rr = explode('-', $arr['start_date']);
					$this->fromdate_val = bab_toHtml(sprintf("%02s/%02s/%04s", $rr[2], $rr[1], $rr[0]));
					}
				}
			else
				{
				$this->fromdate_val = bab_toHtml($fromdate);
				}

			if( empty($todate))
				{
				if( empty($arr['end_date']))
					{
					$this->todate_val = '';
					}
				else
					{
					$rr = explode('-', $arr['end_date']);
					$this->todate_val = bab_toHtml(sprintf("%02s/%02s/%04s", $rr[2], $rr[1], $rr[0]));
					}
				}
			else
				{
				$this->todate_val = bab_toHtml($todate);
				}

			if( empty($id_substitute))
				{
				if( empty($arr['id_substitute']))
					{
					$this->id_substitute_val = '';
					$this->user_disp_val = '';
					}
				else
					{
					$this->id_substitute_val = bab_toHtml($arr['id_substitute']);
					$this->user_disp_val = bab_toHtml(bab_getUserName($arr['id_substitute']));
					}
				}
			else
				{
				$this->id_substitute_val = bab_toHtml($id_substitute);
				$this->user_disp_val = bab_toHtml(bab_getUserName($id_substitute));
				}
			}
		}

	$temp = new temp($iduser, $fromdate, $todate, $id_substitute);
	$babBody->babecho(bab_printTemplate($temp,"options.html", "unavailability"));
	}


function userChangePassword($oldpwd, $newpwd)
	{
	global $babBody, $babDB, $BAB_SESS_USERID, $BAB_SESS_HASHID;

	$new_password1=mb_strtolower($newpwd);

	$res=$babDB->db_query("select password, changepwd, db_authentification from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
	$arruser = $babDB->db_fetch_array($res);
	if( $babBody->babsite['change_password'] == 'Y' && $arruser['changepwd'] == 1 )
		{
		if( $babBody->babsite['authentification'] != BAB_AUTHENTIFICATION_OVIDENTIA && empty($babBody->babsite['ldap_encryptiontype']) && $arruser['db_authentification'] == 'N')
			{
			$changepwd = false;
			}
		else
			{
			$changepwd = true;
			}
		}
	else
		{
		$changepwd = false;
		}

	if( $changepwd == false)
		{
		$babBody->msgerror = bab_translate("Sorry, You cannot change your password. Please contact administrator");
		return false;
		}

	$authentification = $babBody->babsite['authentification'];
	if( $arruser['db_authentification'] == 'Y' )
		{
		$authentification = ''; // force to default
		}

	switch($authentification)
		{
		case BAB_AUTHENTIFICATION_AD:
			if( !empty($babBody->babsite['ldap_encryptiontype']))
				{
				include_once $GLOBALS['babInstallPath']."utilit/ldap.php";
				$ldap = new babLDAP($babBody->babsite['ldap_host'], "", false);
				$ret = $ldap->connect();
				if( $ret === false )
					{
					$babBody->msgerror = bab_translate("LDAP connection failed. Please contact your administrator");
					return false;
					}
				$ret = $ldap->bind($GLOBALS['BAB_SESS_NICKNAME']."@".$babBody->babsite['ldap_domainname'], $oldpwd);
				if( !$ret )
					{
					$ldap->close();
					$babBody->msgerror = bab_translate("LDAP bind failed. Please contact your administrator");
					return false;
					}
				else
					{
					if( isset($babBody->babsite['ldap_filter']) && !empty($babBody->babsite['ldap_filter']))
						{
						$filter = str_replace('%NICKNAME', ldap_escapefilter($GLOBALS['BAB_SESS_NICKNAME']), $babBody->babsite['ldap_filter']);
						}
					else
						{
						$filter = "(|(samaccountname=".ldap_escapefilter($GLOBALS['BAB_SESS_NICKNAME'])."))";
						}

					$attributes = array("dn", $babBody->babsite['ldap_attribute'], "cn");
					$entries = $ldap->search($babBody->babsite['ldap_searchdn'], $filter, $attributes);
					if( $entries === false )
						{
						$ldap->close();
						$babBody->msgerror = bab_translate("LDAP authentification failed. Please verify your login ID and your password");
						return false;
						}

					// create the unicode password 
					$len = mb_strlen($newpwd); 
					$newPass = '"'; 
					for ($i = 0; $i < $len; $i++) 
					{ 
						$newPass .= "{$newpwd{$i}}\000"; 
					} 
					$newPass .= '"'; 

					$ret = $ldap->modify($entries[0]['dn'], array('unicodePwd'=>$newPass));
					$ldap->close();
					if( !$ret)
						{
						$babBody->msgerror = bab_translate("Nothing Changed");
						return false;
						}
					}
				}
			break;
		case BAB_AUTHENTIFICATION_LDAP:
			if( !empty($babBody->babsite['ldap_encryptiontype']))
				{
				include_once $GLOBALS['babInstallPath']."utilit/ldap.php";
				$ldap = new babLDAP($babBody->babsite['ldap_host'], "", false);
				$ret = $ldap->connect();
				if( $ret === false )
					{
					$babBody->msgerror = bab_translate("LDAP connection failed. Please contact your administrator");
					return false;
					}

				if( isset($babBody->babsite['ldap_userdn']) && !empty($babBody->babsite['ldap_userdn']))
				{
				$userdn = str_replace('%UID', ldap_escapefilter($babBody->babsite['ldap_attribute']), $babBody->babsite['ldap_userdn']);
				$userdn = str_replace('%NICKNAME', ldap_escapefilter($nickname), $userdn);
				$ret = $ldap->bind($userdn, $password);
				if( !$ret )
					{
					$msgerror = bab_translate("LDAP authentification failed. Please verify your login ID and your password");
					$ldap->close();
					return false;
					}
				else
					{
					$entries = $ldap->search($userdn, '(objectclass=*)', $attributes);

					if( $entries === false || $entries['count'] == 0 )
						{
						$babBody->msgerror = bab_translate("LDAP search failed");
						$ldap->close();
						return false;
						}
					}
				}
				else
				{

					if( isset($babBody->babsite['ldap_filter']) && !empty($babBody->babsite['ldap_filter']))
						{
						$filter = str_replace('%UID', ldap_escapefilter($babBody->babsite['ldap_attribute']), $babBody->babsite['ldap_filter']);
						$filter = str_replace('%NICKNAME', ldap_escapefilter($GLOBALS['BAB_SESS_NICKNAME']), $filter);
						}
					else
						{
						$filter = "(|(".ldap_escapefilter($babBody->babsite['ldap_attribute'])."=".ldap_escapefilter($GLOBALS['BAB_SESS_NICKNAME'])."))";
						}

					$attributes = array("dn", $babBody->babsite['ldap_attribute'], "cn");
					$entries = $ldap->search($babBody->babsite['ldap_searchdn'], $filter, $attributes);

					if( $entries === false || $entries['count'] == 0 || !isset($entries[0]['dn']) || empty($entries[0]['dn']))
						{
						$ldap->close();
						$babBody->msgerror = bab_translate("LDAP authentification failed. Please verify your login ID and your password");
						return false;
						}

					$ret = $ldap->bind($entries[0]['dn'], $oldpwd);
					if( !$ret )
						{
						$ldap->close();
						$babBody->msgerror = bab_translate("LDAP bind failed. Please contact your administrator");
						return  false;
						}
				}

				$ldappw = ldap_encrypt($newpwd, $babBody->babsite['ldap_encryptiontype']);
				$ret = $ldap->modify($entries[0]['dn'], array('userPassword'=>$ldappw));
				$ldap->close();
				if( !$ret)
					{
					$babBody->msgerror = bab_translate("Nothing Changed");
					return false;
					}
				}
			break;
		default:
			$oldpwd2 = md5(mb_strtolower($oldpwd));
			if( $oldpwd2 != $arruser['password'])
				{
				$babBody->msgerror = bab_translate("ERROR: Old password incorrect !!");
				return false;
				}
			break;
		}

	$result=$babDB->db_query("update ".BAB_USERS_TBL." set password='". md5(mb_strtolower($newpwd)). "' where id='". $babDB->db_escape_string($BAB_SESS_USERID) . "'");
	if ($babDB->db_affected_rows() < 1)
		{
		$babBody->msgerror = bab_translate("Nothing Changed");
		return false;
		}
	else
		{
		include_once $GLOBALS['babInstallPath']."utilit/eventdirectory.php";
		include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
		
		$event = new bab_eventUserModified($BAB_SESS_USERID);
		bab_fireEvent($event);
		
		
		$babBody->msgerror = bab_translate("Password Changed");
		$error = '';
		
		bab_callAddonsFunctionArray('onUserChangePassword', array('id'=>$BAB_SESS_USERID, 'nickname'=>$GLOBALS['BAB_SESS_NICKNAME'], 'password'=>$newpwd, 'error'=>&$error));
		
		if( !empty($error))
			{
			$babBody->msgerror = $error;
			return false;
			}
		return true;
		}
	}


function updatePassword($oldpwd, $newpwd1, $newpwd2)
	{
	global $babBody, $babInstallPath;

	if( empty($GLOBALS['BAB_SESS_USERID']))
		return false;

	if( empty($oldpwd) || empty($newpwd1) || empty($newpwd2))
		{
		$babBody->msgerror =  bab_translate("You must complete all fields !!");
		return false;
		}
	if( $newpwd1 != $newpwd2)
		{
		$babBody->msgerror =  bab_translate("Passwords not match !!");
		return false;
		}
	
	$minPasswordLengh = 6;
	if(ISSET($GLOBALS['babMinPasswordLength']) && is_numeric($GLOBALS['babMinPasswordLength'])){
		$minPasswordLengh = $GLOBALS['babMinPasswordLength'];
		if($minPasswordLengh < 1){
			$minPasswordLengh = 1;
		}
	}
	if( mb_strlen($newpwd1) < $minPasswordLengh)
		{
		$babBody->msgerror =  sprintf(bab_translate("Password must be at least %s characters !!"),$minPasswordLengh);
		return false;
		}

	return userChangePassword( $oldpwd, $newpwd1);
	}


function updateLanguage($lang, $langfilter)
	{
    global $babDB, $BAB_SESS_USERID;
	if( !empty($BAB_SESS_USERID))
		{
		$req = "update ".BAB_USERS_TBL." 
		set 
			lang='".$babDB->db_escape_string($lang)."', 
			langfilter='" .$babDB->db_escape_string($langfilter). "' 
		where 
			id='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
        $res = $babDB->db_query($req);
        
        
        if (0 < $babDB->db_affected_rows($res)) {
			bab_siteMap::clear();
			}

		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=options&idx=global");
	}

/*
 * Update the skin to a user's options
 * Remark : $skin can be empty : it's the skin of the site
 * 
 * @param $skin string
 * @param $style string
 */
function updateSkin($skin, $style) {
    global $babDB, $BAB_SESS_USERID;
    if(empty($skin)) {
    	$style = ''; /* style of skin of the site */
    }
    
	if(!empty($BAB_SESS_USERID)) {
        $req = "update ".BAB_USERS_TBL." set skin='".$babDB->db_escape_string($skin)."', style='".$babDB->db_escape_string($style)."' where id='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
        $res = $babDB->db_query($req);
        
        if (0 < $babDB->db_affected_rows($res)) {
			bab_siteMap::clear();
		}
	}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=options&idx=global");
}



function updateNickname($password, $nickname)
	{
	global $babBody, $babDB, $BAB_HASH_VAR, $BAB_SESS_NICKNAME, $BAB_SESS_USERID, $BAB_SESS_USER, $BAB_SESS_HASHID;

	if( empty($GLOBALS['BAB_SESS_USERID']))
		return false;

	$password = mb_strtolower($password);
	$req = "select id from ".BAB_USERS_TBL." where nickname='".$babDB->db_escape_string($BAB_SESS_NICKNAME)."' and password='". md5($password) ."'";
	$res = $babDB->db_query($req);
	if (!$res || $babDB->db_num_rows($res) < 1)
		{
		$babBody->msgerror = bab_translate("Password incorrect");
		return false;
		}
	else
		{
		$arr = $babDB->db_fetch_array($res);
		if( empty($nickname))
			{
			$babBody->msgerror = bab_translate( "You must complete all fields !!");
			return false;
			}

	
		if( $BAB_SESS_NICKNAME != $nickname )
			{
			$req = "select id from ".BAB_USERS_TBL." where nickname='".$babDB->db_escape_string($nickname)."'";	
			$res = $babDB->db_query($req);
			if( $babDB->db_num_rows($res) > 0)
				{
				$babBody->msgerror = bab_translate("This login ID already exists !!");
				return false;
				}
			}

		$hash=md5($nickname.$BAB_HASH_VAR);
		$req = "update ".BAB_USERS_TBL." set nickname='".$babDB->db_escape_string($nickname)."', hashname='".$hash."', confirm_hash='".$hash."' where id='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
		$res = $babDB->db_query($req);
		
		if( $babDB->db_num_rows($res) > 0)
			{
			include_once $GLOBALS['babInstallPath']."utilit/eventdirectory.php";
			$event = new bab_eventUserModified($BAB_SESS_USERID);
			bab_fireEvent($event);
			}

		$BAB_SESS_NICKNAME = $nickname;
		$BAB_SESS_HASHID = $hash;
		return true;
		}
	}

function updateRegionalSettings($datelformat, $datesformat, $timeformat)
{
	global $babBody, $BAB_SESS_USERID, $babDB;
	$res = $babDB->db_query("update ".BAB_USERS_TBL." set date_shortformat='".$babDB->db_escape_string($datesformat)."', date_longformat='".$babDB->db_escape_string($datelformat)."', time_format='".$babDB->db_escape_string($timeformat)."' where id='".$babDB->db_escape_string($BAB_SESS_USERID)."'");
		
	return true;
}



function updateProfiles()
{
	global $babBody, $babDB;

	$res = $babDB->db_query("select id, required from ".BAB_PROFILES_TBL."");
	$addgroups = array();
	$delgroups = array();
	while( $arr = $babDB->db_fetch_array($res))
	{
	if( bab_IsAccessValid(BAB_PROFILES_GROUPS_TBL, $arr['id']))
		{
		if( isset($_POST["grpids".$arr['id']]))
			{
			$grpvar = $_POST["grpids".$arr['id']];
			}
		else
			{
			$grpvar = array();
			}

		if($arr['required'] == 'Y' && (count($grpvar) == 0 || empty($grpvar[0])))
			{
			$babBody->msgerror = bab_translate( "You must complete all fields !!");
			return false;
			}

		$resgrp = $babDB->db_query("select pgt.id_group from ".BAB_PROFILES_GROUPSSET_TBL." pgt where pgt.id_object ='".$babDB->db_escape_string($arr['id'])."'");
		while( $row = $babDB->db_fetch_array($resgrp))
			{
			if( count($grpvar) > 0  && in_array($row['id_group'], $grpvar ) )
				{
				if( count($addgroups) ==  0  || !in_array($row['id_group'], $addgroups))
					{
					$addgroups[] = $row['id_group'];
					}
				}
			else
				{
				if( count($delgroups) ==  0  || !in_array($row['id_group'], $delgroups))
					{
					$delgroups[] = $row['id_group'];
					}
				}
			}
		}
	}

	for( $i=0; $i < count($addgroups); $i++ )
	{
		bab_addUserToGroup($GLOBALS['BAB_SESS_USERID'], $addgroups[$i]);
	}
	for( $i=0; $i < count($delgroups); $i++ )
	{
		bab_removeUserFromGroup($GLOBALS['BAB_SESS_USERID'], $delgroups[$i]);
	}
	return true;
}

function updateStateSection($c, $w, $closed)
	{
	global $babDB, $HTTP_REFERER, $BAB_SESS_USERID;

	if( !empty($BAB_SESS_USERID))
		{
		$req = "select * from ".BAB_SECTIONS_STATES_TBL." where type='".$babDB->db_escape_string($w)."' and id_section='".$babDB->db_escape_string($c)."' and  id_user='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
		$res = $babDB->db_query($req);
		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$req = "update ".BAB_SECTIONS_STATES_TBL." set closed='".$babDB->db_escape_string($closed)."' where type='".$babDB->db_escape_string($w)."' and id_section='".$babDB->db_escape_string($c)."' and  id_user='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
			}
		else
			{
			$req = "insert into ".BAB_SECTIONS_STATES_TBL." (id_section, closed, type, id_user) values ('".$babDB->db_escape_string($c)."', '".$babDB->db_escape_string($closed)."', '".$babDB->db_escape_string($w)."', '".$BAB_SESS_USERID."')";
			}

		$babDB->db_query($req);
		}

	if (isset($_SERVER['HTTP_REFERER']))
	{
	Header("Location: ". $_SERVER['HTTP_REFERER']);
	exit;
	}
}

function updateUnavailability($iduser, $fromdate, $todate, $id_substitute)
	{
	global $babBody, $babDB;

	if( $iduser != $GLOBALS['BAB_SESS_USERID'] && !bab_isUserAdministrator() && $babBody->currentDGGroup['users'] != 'Y')
		{
		return;
		}

	if( empty($fromdate) || empty($todate))
		{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid date !!");
		return false;
		}

	$rr = explode('/', $fromdate);
	$dbegin = mktime( 0,0,0,$rr[1], $rr[0], $rr[2]);
	$sqlstartdate = sprintf("%04s-%02s-%02s", $rr[2], $rr[1], $rr[0]);
	$rr = explode('/', $todate);
	$dend = mktime( 0,0,0,$rr[1], $rr[0], $rr[2]);
	$sqlenddate = sprintf("%04s-%02s-%02s", $rr[2], $rr[1], $rr[0]);

	if( $dbegin > $dend )
		{
		$babBody->msgerror = bab_translate("Begin date must be less than end date");
		return false;
		}

	if( empty($id_substitute))
		{
		$id_substitute = 0;
		}

	if( $id_substitute == $iduser)
		{
		$babBody->msgerror = bab_translate("ERROR: invalid user");
		return false;
		}

		
	if ($id_substitute)
	{
		// verifier que le suppleant n'est pas absent
	
		$res = $babDB->db_query("select * from ".BAB_USERS_UNAVAILABILITY_TBL." 
				where 
					id_user='".$babDB->db_escape_string($id_substitute)."' 
					AND (start_date<".$babDB->quote($sqlenddate)." AND end_date>".$babDB->quote($sqlstartdate).")
			");
		
		if ($babDB->db_num_rows($res) > 0)
		{
			$babBody->addError(bab_translate("This substitute is not available for this period"));
			return false;
		}
	}

	// verifier que je ne suis pas le supplenant de quelqu'un
	
	$res = $babDB->db_query("select id_user, start_date, end_date from ".BAB_USERS_UNAVAILABILITY_TBL."
		where
			id_substitute='".$babDB->db_escape_string($iduser)."'
			AND (start_date<".$babDB->quote($sqlenddate)." AND end_date>".$babDB->quote($sqlstartdate).")
	");
	
	if ($babDB->db_num_rows($res) > 0)
	{
		$arr = $babDB->db_fetch_assoc($res);
		$babBody->addError(sprintf(bab_translate("%s is unavailable from %s to %s with %s as substitute"), 
				bab_getUserName($arr['id_user']), 
				bab_shortDate(bab_mktime($arr['start_date']), false),
				bab_shortDate(bab_mktime($arr['end_date']), false),
				bab_getUserName($iduser)
			)
		);
		return false;
	}
	

	$res = $babDB->db_query("select * from ".BAB_USERS_UNAVAILABILITY_TBL." where id_user='".$babDB->db_escape_string($iduser)."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$babDB->db_query("update ".BAB_USERS_UNAVAILABILITY_TBL." set start_date='".$babDB->db_escape_string($sqlstartdate)."', end_date='".$babDB->db_escape_string($sqlenddate)."', id_substitute='".$babDB->db_escape_string($id_substitute)."' where id_user='".$babDB->db_escape_string($iduser)."'");
		}
	else
		{
		$babDB->db_query("insert into ".BAB_USERS_UNAVAILABILITY_TBL." (id_user, start_date, end_date, id_substitute ) values ('".$babDB->db_escape_string($iduser)."','".$babDB->db_escape_string($sqlstartdate)."','".$babDB->db_escape_string($sqlenddate)."','".$babDB->db_escape_string($id_substitute)."')");
		}
	$babBody->msgerror = bab_translate("Update done");
	return true;
	}

function deleteUnavailability($iduser)
	{
	global $babDB;

	$babDB->db_query("delete from ".BAB_USERS_UNAVAILABILITY_TBL." where id_user='".$babDB->db_escape_string($iduser)."'");
	}

	
function bab_haveOptionalSections()
{
	global $babDB;
	
	$res = $babDB->db_query("SELECT * FROM bab_sections WHERE optional='Y'");
	if (0 !== $babDB->db_num_rows($res))
	{
		return true;
	}
	
	$res = $babDB->db_query("SELECT * FROM bab_topics_categories WHERE optional='Y'");
	if (0 !== $babDB->db_num_rows($res))
	{
		return true;
	}
	
	$res = $babDB->db_query("SELECT * FROM bab_private_sections WHERE optional='Y'");
	if (0 !== $babDB->db_num_rows($res))
	{
		return true;
	}
	
	return false;
}
	
	

/* main */
if( !isset($BAB_SESS_LOGGED) || !$BAB_SESS_LOGGED)
{
	$babBody->addError(bab_translate("Access denied"));
	return;
}

$idx = bab_rp('idx', 'global');

$babBody->msgerror = '';

if( '' != ($update = bab_pp('update')))
	{
    switch ($update)
        {
        case 'password':
			$msgerror = updatePassword(bab_pp('oldpwd'), bab_pp('newpwd1'), bab_pp('newpwd2'));
        	if ($msgerror)
			{
				changePasswordUnload(bab_translate("Your password has been modified"));
				exit;
			}
			else
			{
				$idx = 'changePassword';
			}
            break;
        case 'lang':
			$lang = bab_pp('lang');
			$langfilter = bab_pp('langfilter');
        	updateLanguage($lang, bab_getInstance('babLanguageFilter')->convertFilterToInt($langfilter));
            break;
        case 'skin':
        	updateSkin(bab_pp('skin'), bab_pp('style'));
            break;
        case 'nickname':
			$password = bab_pp('password');
			$nickname = bab_pp('nickname');
        	if(updateNickname($password, $nickname))
				{
				unset($nickname);
				}
            break;
            
        /*
        case 'userinfo':
 			$password = bab_pp('password');
			$firstname = bab_pp('firstname');
			$middlename = bab_pp('middlename');
			$lastname = bab_pp('lastname');
			$nickname = bab_pp('nickname');
			$email = bab_pp('email');
       		if(updateUserInfo($password, $firstname, $middlename, $lastname, $nickname, $email))
				{
				unset($firstname);
				unset($lastname);
				unset($middlename);
				unset($nickname);
				unset($email);
				}
            break;
        */
            
        case 'profiles':
        	updateProfiles();
			$idx = 'global';
            break;
         case 'regsettings':
			$datelformat = bab_pp('datelformat');
			$datesformat = bab_pp('datesformat');
			$timeformat = bab_pp('timeformat');
        	if(!updateRegionalSettings($datelformat, $datesformat, $timeformat))
				{
				$idx = 'global';
				}
            break;
         case 'unavailability':
			if( isset($_POST['bdelete']))
			{
				$iduser = bab_pp('iduser');
				deleteUnavailability($iduser);
				$fromdate ='';
				$todate ='';
				$id_substitute ='';
				$idx = 'unav';

			}else
				{
				$iduser = bab_pp('iduser');
				$fromdate = bab_pp('fromdate');
				$todate = bab_pp('todate');
				$id_substitute = bab_pp('id_substitute');
				updateUnavailability($iduser, $fromdate, $todate, $id_substitute);				
				$idx = 'unav';
				}
            break;
       }
	}

if( !isset($firstname) &&  !isset($middlename) &&  !isset($lastname) && !isset($nickname) && !isset($email) && $BAB_SESS_USERID != '')
	{
	$req = "select sn, mn, givenname, email from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='0' and id_user='".$babDB->db_escape_string($BAB_SESS_USERID)."'";
	$res = $babDB->db_query($req);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		$firstname = $arr['givenname'];
		$lastname = $arr['sn'];
		$middlename = $arr['mn'];
		$email = $arr['email'];
		$nickname = bab_getUserNickname($BAB_SESS_USERID);
		}
	}
	
switch($idx)
	{
	case 'unav':
		$iduser = bab_rp('iduser');
		$babBody->title = bab_getUserName($iduser);
		$fromdate = bab_pp('fromdate');
		$todate = bab_pp('todate');
		$id_substitute = bab_pp('id_substitute');
		$babBody->addItemMenu('global', bab_translate("Options"), $GLOBALS['babUrlScript'].'?tg=options&idx=global');
		if( ('Y' == $babBody->babsite['change_unavailability'] && $iduser == $GLOBALS['BAB_SESS_USERID']) || bab_isUserAdministrator() || ($babBody->currentAdmGroup && $babBody->currentDGGroup['users'] == 'Y'))
			{
			showUnavailability($iduser, $fromdate, $todate, $id_substitute);
			$babBody->addItemMenu('unav', bab_translate("Unavailability"), $GLOBALS['babUrlScript'].'?tg=options&idx=unav&iduser='.$iduser);
			}
		else
			{
			$babBody->msgerror = bab_translate("Access denied");
			}
		break;

	case 'cb':
		updateStateSection(bab_gp('s'), bab_gp('w'), 'Y');
		break;

	case 'ob':
		updateStateSection(bab_gp('s'), bab_gp('w'), 'N');
		break;

	case 'changePassword':
		changePassword();
		break;
	case 'changePasswordUnload':
		changePasswordUnload(bab_rp('msg'));
		break;

	case 'global':
	default:
		$babBody->title = bab_translate("Options");
		if( !isset($nickname)) { $nickname = ''; }
		changeNickname($nickname);
		if ('Y' == $babBody->babsite['change_skin'])
			{
			changeSkin();
			}
		if ('Y' == $babBody->babsite['change_lang'])
			{
			changeLanguage();
			}
		if ('Y' == $babBody->babsite['change_date'])
			{
			changeRegionalSettings();
			}
		changeProfiles();
		$babBody->addItemMenu('global', bab_translate("Options"), $GLOBALS['babUrlScript'].'?tg=options&idx=global');
		if( bab_getICalendars()->calendarAccess())
			{
			$babBody->addItemMenu('calendar', bab_translate("Calendar Options"), $GLOBALS['babUrlScript'].'?tg=calopt&idx=options');
			}
		if( bab_mailAccessLevel())
			{
			$babBody->addItemMenu('options', bab_translate("Mail"), $GLOBALS['babUrlScript'].'?tg=mailopt&idx=listacc');
			}
		
		if ( bab_haveOptionalSections())
			{
			$babBody->addItemMenu('list', bab_translate("Sections"), $GLOBALS['babUrlScript'].'?tg=sectopt&idx=list');
			}
		
		$iduser = isset($iduser)? $iduser: $BAB_SESS_USERID;
		if( ('Y' == $babBody->babsite['change_unavailability'] && $iduser == $GLOBALS['BAB_SESS_USERID']) || bab_isUserAdministrator() || ($babBody->currentAdmGroup && $babBody->currentDGGroup['users'] == 'Y'))
			{
			$babBody->addItemMenu('unav', bab_translate("Unavailability"), $GLOBALS['babUrlScript'].'?tg=options&idx=unav&iduser='.$iduser);
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab', 'UserOptions');
?>
