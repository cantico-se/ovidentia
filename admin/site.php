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
include_once $babInstallPath."admin/acl.php";

$bab_ldapAttributes = array('uid', 'cn', 'sn', 'givenname', 'mail', 'telephonenumber', 'mobile', 'homephone', 'facsimiletelephonenumber', 'title', 'o', 'street', 'l', 'postalcode', 'st', 'homepostaladdress', 'jpegphoto', 'departmentnumber');


function getSiteName($id)
	{
	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_SITES_TBL." where id='$id'";
	$res = $db->db_query($query);
	if( $res && $db->db_num_rows($res) > 0)
		{
		$arr = $db->db_fetch_array($res);
		return $arr['name'];
		}
	else
		{
		return "";
		}
	}

function siteModify($id)
	{

	global $babBody;
	class temp
		{
		var $name;
		var $description;
		var $nameval;
		var $descriptionval;
		var $lang;
		var $skin;
		var $langsite;
		var $skinsite;
		var $siteemail;
		var $siteemailval;
		var $create;
	
		var $id;
		var $arr = array();
		var $db;
		var $res;

        var $langselected;
        var $siteselected;
        var $arrfiles = array();
        var $arrdir = array();

		var $registration;
		var $yes;
		var $no;
		var $yselected;
		var $nselected;

		var $mailfunction;
		var $disabled;
		var $smtp;
		var $sendmail;
		var $mail;
		var $server;
		var $serverval;
		var $serverport;
		var $serverportval;
		var $mailselected;
		var $smtpselected;
		var $sendmailselected;
		var $disabledselected;

		var $smtpuser;
		var $smtpuserval;
		var $smtppass;
		var $smtppass2;
		var $smtppassval;

		var $langfiltertxt;
		var $langfilterval;
		var $langfiltersite;
		var $langfilterselected;		

		var $adminnametxt;
		var $adminnameval;

		function temp($id)
			{
			$this->name = bab_translate("Site name");
			$this->description = bab_translate("Description");
			$this->lang = bab_translate("Lang");
			$this->skin = bab_translate("Skin");
			$this->siteemail = bab_translate("Email site");
			$this->create = bab_translate("Modify");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->delete = bab_translate("Delete");
			$this->confirmation = bab_translate("Send email confirmation")."?";
			$this->registration = bab_translate("Activate Registration")."?";
			$this->disabled = bab_translate("Disabled");
			$this->mailfunction = bab_translate("Mail function");
			$this->server = bab_translate("Smtp server");
			$this->serverport = bab_translate("Server port");
			$this->imagessize = bab_translate("Max image size ( Kb )");
			$this->none = bab_translate("None");
			$this->smtpuser = bab_translate("SMTP username");
			$this->smtppass = bab_translate("SMTP password");
			$this->smtppass2 = bab_translate("Re-type SMTP password");
			$this->adminnametxt = bab_translate("Name to use for notification emails");
			$this->t_mb = bab_translate("Mb");

			$this->smtp = "smtp";
			$this->sendmail = "sendmail";
			$this->mail = "mail";
			$this->mailselected = "";
			$this->smtpselected = "";
			$this->sendmailselected = "";
			$this->disabledselected = "";

			// bloc 2
			$this->firstlast = bab_translate("Firstname")." ".bab_translate("Lastname");
			$this->lastfirst = bab_translate("Lastname")." ".bab_translate("Firstname");
			$this->name_order_title = bab_translate("User name composition");
			$this->change_nickname_title = bab_translate("User can modifiy his nickname");
			$this->change_password_title = bab_translate("User can modifiy his password");
			$this->remember_login_title = bab_translate("Automatic connection");
			$this->email_password_title = bab_translate("Display option 'Lost Password'");
			$this->babslogan_title = bab_translate("Site slogan");
			$this->uploadpath_title = bab_translate("Upload path");
			$this->maxfilesize_title = bab_translate("File manager max file size");
			$this->folder_diskspace_title = bab_translate("File manager max group directory size");
			$this->user_diskspace_title = bab_translate("File manager max user directory size");
			$this->total_diskspace_title = bab_translate("File manager max total size");

			$this->id = $id;
			$this->langfiltertxt = bab_translate("Language filter");

			$this->db = $GLOBALS['babDB'];
			$req = "select *, DECODE(smtppassword, \"".$GLOBALS['BAB_HASH_VAR']."\") as smtppass, DECODE(ldap_password, \"".$GLOBALS['BAB_HASH_VAR']."\") as ldappass  from ".BAB_SITES_TBL." where id='$id'";
			$this->res = $this->db->db_query($req);
			if( $this->db->db_num_rows($this->res) > 0 )
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->nameval = $arr['name'];
				$this->descriptionval = $arr['description'];
				$this->langsite = $arr['lang'];
				$this->skinsite = $arr['skin'];
				$this->stylesite = $arr['style'];
				$this->siteemailval = $arr['adminemail'];
				$this->adminnameval = $arr['adminname'];
				$this->serverval = $arr['smtpserver'];
				$this->serverportval = $arr['smtpport'];
				$this->imgsizeval = $arr['imgsize'];
				$this->smtpuserval = $arr['smtpuser'];
				$this->smtppassval = $arr['smtppass'];
				$this->dbvalue = $arr;
				$this->dbvalue['babslogan'] = str_replace('"',"''",$GLOBALS['babSlogan']);
				$this->dbvalue['total_diskspace'] = round($GLOBALS['babMaxTotalSize']/1048576);
				$this->dbvalue['user_diskspace'] = round($GLOBALS['babMaxUserSize']/1048576);
				$this->dbvalue['folder_diskspace'] = round($GLOBALS['babMaxGroupSize']/1048576);
				$this->dbvalue['maxfilesize'] = round($GLOBALS['babMaxFileSize']/1048576);
				if( $arr['registration'] == "Y" || $GLOBALS['babCookieIdent'])
					{
					$this->nregister = "";
					$this->yregister = "selected";
					}
				else
					{
					$this->yregister = "";
					$this->nregister = "selected";
					}
				switch($arr['mailfunc'])
					{
					case "mail":
						$this->mailselected = "selected";
						break;
					case "smtp":
						$this->smtpselected = "selected";
						break;
					case "sendmail":
						$this->sendmailselected = "selected";
						break;
					default:
						$this->disabledselected = "selected";
						break;
					}

				}

			$this->langfiltersite = $arr['langfilter'];
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
		
		function getnextlang()
			{
			static $i = 0;
			if( $i < $this->count)
				{
                $this->langval = $this->arrfiles[$i];
                if( $this->langsite == $this->langval )
                    $this->langselected = "selected";
                else
                    $this->langselected = "";
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextskin()
			{
			static $i = 0;
			if( $i < $this->cntskins)
				{
				$this->iindex = $i;
                $this->skinname = $this->arrskins[$i];
                $this->skinval = $this->arrskins[$i];
                if( $this->skinname == $this->skinsite )
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
                if( $this->skinname == $this->skinsite && $this->stylesite == $this->styleval)
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

		function getnextlangfilter()
			{
				static $i = 0;
				if( $i < ($GLOBALS['babLangFilter']->countFilters()))
				{
					$this->langfilterval =
						$GLOBALS['babLangFilter']->getFilterStr($i);
					if($this->langfiltersite == $i )
					{
						$this->langfilterselected = "selected";
			}
					else
					{
						$this->langfilterselected = "";
					}
					$i++;
					return true;
		}
				else
					return false;
			} //getnextlangfilter

		} // class temp

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp, "sites.html", "sitemodify"));
	$babBody->babecho(	bab_printTemplate($temp,"sites.html", "skinscripts"));
	}

function siteAuthentification($id)
	{

	global $babBody;
	class clsSiteAuthentification
		{

		function clsSiteAuthentification($id)
			{
			global $bab_ldapAttributes;
			$this->db = $GLOBALS['babDB'];
			$this->id = $id;
			$req = "select *, DECODE(smtppassword, \"".$GLOBALS['BAB_HASH_VAR']."\") as smtppass, DECODE(ldap_password, \"".$GLOBALS['BAB_HASH_VAR']."\") as ldappass  from ".BAB_SITES_TBL." where id='$id'";
			$this->res = $this->db->db_query($req);
			if( $this->db->db_num_rows($this->res) > 0 )
				{
				$this->showform = true;
				$arr = $this->db->db_fetch_array($this->res);
				$this->modify = bab_translate("Modify");
				$this->authsite = $arr['authentification'];
				$this->ldaphost = $arr['ldap_host'];
				$this->ldappasssite = $arr['ldappass'];
				$this->ldapbasednsite = $arr['ldap_basedn'];
				$this->ldapuserdnsite = $arr['ldap_userdn'];
				$this->ldapsearchdnsite = $arr['ldap_searchdn'];
				$this->ldapattributesite = $arr['ldap_attribute'];
				$this->ldappasstypesite = $arr['ldap_passwordtype'];

				$this->authentificationtxt = bab_translate("Authentification");
				$this->arrayauth = array(0 => "OVIDENTIA", 1 => "LDAP");
				$this->arrayauthpasstype = array('text' => 'plaintext', 'md5' => 'md5', 'unix' => 'unix', 'sha' => 'sha-1');

				$this->fieldrequiredtxt = bab_translate("Those fields are required");
				$this->authpasstxt = bab_translate("Password");
				$this->authpassconftxt = bab_translate("Confirm");
				$this->hosttxt = bab_translate("Host");
				$this->basedntxt = bab_translate("Base DN");
				$this->userdntxt = bab_translate("Bind DN");
				$this->searchbasetxt = bab_translate("Search base");
				$this->attributetxt = bab_translate("Attribute");
				$this->authpasstypetxt = bab_translate("Password encryption type");
				$this->ldpachkcnxtxt = bab_translate("Allow administrators to connect if LDAP authentification fails");
				if( $arr['ldap_allowadmincnx']  == 'Y' )
					{
					$this->ldpachkcnxchecked = 'checked';
					}
				else
					{
					$this->ldpachkcnxchecked = '';
					}

				$this->resf = $this->db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL);
				if( $this->resf && $this->db->db_num_rows($this->resf) > 0)
					{
					$this->countf = $this->db->db_num_rows($this->resf);
					$this->countf++;
					}
				else
					{
					$this->countf = 0;
					}

				$this->siteattributes = array();
				$res = $this->db->db_query("select * from ".BAB_LDAP_SITES_FIELDS_TBL." where id_site='".$id."'");
				while($row = $this->db->db_fetch_array($res))
					{
					$this->siteattributes[$row['name']] = $row['x_name'];
					}


				sort($bab_ldapAttributes);
				$this->countv = count($bab_ldapAttributes);
				}
			else
				{
				$this->showform = false;
				}
			}

		function getnextauth()
			{
			static $i = 0;
			if( $i < count($this->arrayauth))
				{
                $this->authval = $i;
                $this->authname = $this->arrayauth[$i];
                if( $this->authsite == $this->authval )
					{
                    $this->authselected = "selected";
					}
                else
					{
                    $this->authselected = "";
					}
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextpasstype()
			{
			static $i = 0;
			if( $i < count($this->arrayauthpasstype))
				{
				list($this->passtypeval, $this->passtypename) = each($this->arrayauthpasstype);
                if( $this->ldappasstypesite == $this->passtypeval )
					{
                    $this->passtypeselected = "selected";
					}
                else
					{
                    $this->passtypeselected = "";
					}
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextfield()
			{
			global $bab_ldapAttributes;
			static $i = 0;
			if( $i < $this->countf)
				{
				if( 0  == $i )
					{
					$this->ofieldname = bab_translate("Nickname");
					$this->ofieldv = "nickname";
					$this->required = true;				
					if( in_array($this->ldapattributesite, $bab_ldapAttributes) )
						{
						$this->ofieldval = '';
						}
					else
						{
						$this->ofieldval = $this->ldapattributesite;
						}
					}
				else
					{
					$arr = $this->db->db_fetch_array($this->resf);
					$this->required = false;				
					$this->ofieldname = bab_translate($arr['description']);
					$this->ofieldv = $arr['name'];
					if( in_array($arr['x_name'], $bab_ldapAttributes) && $this->siteattributes[$this->ofieldv] == $arr['x_name'])
						{
						$this->ofieldval = '';
						}
					else
						{
						$this->ofieldval = isset($this->siteattributes[$this->ofieldv])? $this->siteattributes[$this->ofieldv]: '';
						}
					}
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				if( $this->countf > 0 )
					{
					$this->db->db_data_seek($this->resf, 0);
					}
				return false;
				}
			}
		
		function getnextval()
			{
			global $bab_ldapAttributes;
			static $k = 0;
			if( $k < $this->countv)
				{
				$this->ffieldid = $bab_ldapAttributes[$k];
				$this->ffieldname = $bab_ldapAttributes[$k];

				if( $this->ofieldv == "nickname" )
					{
						if( $this->ffieldname == $this->ldapattributesite)
						{
						$this->fselected = "selected";
						}
						else
						{
						$this->fselected = "";
						}
					}
				else
					{
						if( isset($this->siteattributes[$this->ofieldv]) && $this->ffieldname == $this->siteattributes[$this->ofieldv] )
						{
						$this->fselected = "selected";
						}
						else
						{
						$this->fselected = "";
						}
					}

				$k++;
				return true;
				}
			else
				{
				$k = 0;
				return false;
				}
			}
		}

	$temp = new clsSiteAuthentification($id);
	$babBody->babecho(	bab_printTemplate($temp, "sites.html", "siteauthentification"));
	}


function sectionDelete($id)
	{
	global $babBody;
	
	class temp
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;
		var $topics;
		var $article;

		function temp($id)
			{
			$this->message = bab_translate("Are you sure you want to delete this site");
			$this->title = getSiteName($id);
			$this->warning = bab_translate("WARNING: This operation will delete the site and all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=site&idx=Delete&site=".$id."&action=Yes";
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=site&idx=modify&item=".$id;
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}


function siteRegistration($id)
	{
	global $babBody;
	class temp
		{
		function temp($id)
			{
			global $babBody;
			$this->item = $id;
			$this->field = bab_translate("Fields to display in registration form");
			$this->rw = bab_translate("Use in registration");
			$this->required = bab_translate("Required");
			$this->multilignes = bab_translate("Multilignes");
			$this->disclaimertxt = bab_translate("Registration options");
			$this->disclaimer = bab_translate("Display link to Disclaimer/Privacy Statement");
			$this->editdptxt = bab_translate("Edit");
			$this->groupregistration = bab_translate("Default group for confirmed users");
			$this->emailnotification = bab_translate("Send email confirmation")."?";
			$this->none = bab_translate("None");
			$this->add = bab_translate("Modify");
			$this->db = $GLOBALS['babDB'];
			$this->res = $this->db->db_query("select * from ".BAB_SITES_FIELDS_REGISTRATION_TBL." where id_site='".$id."'");
			if( $this->res && $this->db->db_num_rows($this->res) > 0)
				{
				$this->count = $this->db->db_num_rows($this->res);
				}
			else
				{
				$this->count = 0;
				}
			$this->altbg = true;
			$this->urleditdp = $GLOBALS['babUrlScript']."?tg=site&idx=editdp&item=".$id;

			$this->grpres = $this->db->db_query("select id, name from ".BAB_GROUPS_TBL." where id > '3'");
			$this->grpcount = $this->db->db_num_rows($this->grpres);

			$this->arrsite = $this->db->db_fetch_array($this->db->db_query("select email_confirm, display_disclaimer, idgroup from ".BAB_SITES_TBL." where id='".$id."'"));
			if( $this->arrsite['email_confirm'] == "Y")
				{
				$this->enchecked = "checked";
				}
			else
				{
				$this->enchecked = "";
				}
			if( $this->arrsite['display_disclaimer'] == "Y")
				{
				$this->dpchecked = "checked";
				}
			else
				{
				$this->dpchecked = "";
				}
			}

		function getnextfield()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$this->fieldid = $arr['id_field'];
				$this->altbg = !$this->altbg;

				if( $arr['registration'] == "Y")
					{
					$this->rwchecked = "checked";
					}
				else
					{
					$this->rwchecked = "";
					}

				if( $arr['required'] == "Y")
					{
					$this->reqchecked = "checked";
					}
				else
					{
					$this->reqchecked = "";
					}
				if( $arr['multilignes'] == "Y")
					{
					$this->mlchecked = "checked";
					}
				else
					{
					$this->mlchecked = "";
					}

				if (in_array( $this->fieldid, array( 2, 3, 4, 6) ))
					{
					$this->disabled = true;
					}
				else 
					{
					$this->disabled = false;
					}

				$arr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
				$this->fieldn = bab_translate($arr['description']);
				$this->fieldv = $arr['name'];
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextgrp()
			{
			static $i = 0;
			if( $i < $this->grpcount)
				{
				$arr = $this->db->db_fetch_array($this->grpres);
                $this->grpname = $arr['name'];
                $this->grpid = $arr['id'];
				if( $this->arrsite['idgroup'] == $this->grpid )
					{
					$this->grpsel = "selected";
					}
				else
					{
					$this->grpsel = "";
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
	$babBody->babecho( bab_printTemplate($temp,"sites.html", "siteregistration"));
	}

function editDisclaimerPrivacy($id, $content)
	{
	global $babBody;

	class temp
		{
		var $disclaimertxt;
		var $modify;

		var $db;
		var $arr = array();
		var $res;
		var $msie;
		var $brecevt;
		var $all;
		var $thisone;
		var $updaterec;

		function temp($id, $content)
			{
			$this->disclaimertxt = bab_translate("Disclaimer/Privacy Statement");
			$this->modify = bab_translate("Update");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_SITES_DISCLAIMERS_TBL." where id_site='".$id."'";
			$res = $this->db->db_query($req);
			if( empty($content))
				{
				if( $res && $this->db->db_num_rows($res) > 0 )
					{
					$arr = $this->db->db_fetch_array($res);
					$this->disclaimerval = $arr['disclaimer_text'];
					}
				else
					{
					$this->disclaimerval = '';
					}
				}
			else
				{
				$this->disclaimerval = $content;
				}
			if(( strtolower(bab_browserAgent()) == "msie") and (bab_browserOS() == "windows"))
				{
				$this->msie = 1;
				}
			else
				{
				$this->msie = 0;
				}
			}
		}

	$temp = new temp($id, $content);
	echo bab_printTemplate($temp,"sites.html", "disclaimeredit");
	}


function siteUpdate_bloc1($id, $name, $description, $lang, $style, $siteemail, $skin, $register, $mailfunc, $server, $serverport, $imgsize, $smtpuser, $smtppass, $smtppass2, $langfilter, $adminname)
	{
	global $babBody;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	if( $mailfunc == "smtp" && empty($server))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide server address !!");
		return false;
		}

	if( !empty($smtppass) || !empty($smtppass2))
		{
		if( $smtppass != $smtppass2 )
			{
			$babBody->msgerror = bab_translate("ERROR: Passwords not match !!");
			return false;
			}
		}

	if( empty($serverport))
		{
		$serverport = "25";
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$description = addslashes($description);
		$namev = addslashes($name);
		$adminname = addslashes($adminname);
		}
	else
		{
		$namev = $name;
		$name = stripslashes($name);
		}

	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_SITES_TBL." where name='".$namev."' and id!='".$id."'";
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This site already exists");
		return false;
		}
	else
		{
		list($oldname) = $db->db_fetch_row($db->db_query("select name from ".BAB_SITES_TBL." where id='".$id."'"));
		$req = "update ".BAB_SITES_TBL." set ";

		if( $oldname != $name)
			{
			$filename = "config.php";
			$file = @fopen($filename, "r");
			$txt = fread($file, filesize($filename));
			fclose($file);
			$reg = "babSiteName[[:space:]]*=[[:space:]]*\"([^\"]*)\"";
			$res = ereg($reg, $txt, $match);
			$reg = "babSiteName[[:space:]]*=[[:space:]]*\"".$match[1]."\"";
			$out = ereg_replace($reg, "babSiteName = \"".$name."\"", $txt);
			$file = fopen($filename, "w");
			fputs($file, $out);
			fclose($file);
			$req .= "name='".$namev."', ";
			}

		if( !is_numeric($imgsize))
			$imgsize = 25;
		$req .= "description='".$description."', lang='".$lang."', adminemail='".$siteemail."', adminname='".$adminname."', skin='".$skin."', style='".$style."', registration='".$register."', mailfunc='".$mailfunc."', smtpserver='".$server."', smtpport='".$serverport."', imgsize='".$imgsize."', smtpuser='".$smtpuser."', smtppassword=ENCODE(\"".$smtppass."\",\"".$GLOBALS['BAB_HASH_VAR']."\"), langfilter='" .$langfilter. "' where id='".$id."'";
		$db->db_query($req);
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=sites&idx=list");
	}


function siteUpdate_bloc2($id,$total_diskspace, $user_diskspace, $folder_diskspace, $maxfilesize, $uploadpath, $babslogan, $remember_login, $email_password, $change_password, $change_nickname, $name_order)
	{
	global $babBody;
	if( !bab_isMagicQuotesGpcOn())
		{
		$babslogan = addslashes($babslogan);
		$uploadpath = addslashes($uploadpath);
		}

	if( !is_numeric($total_diskspace) || !is_numeric($user_diskspace) || !is_numeric($folder_diskspace) || !is_numeric($maxfilesize))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide all file manager size limits !!");
		return false;
		}

	$db = $GLOBALS['babDB'];

	list($oldname) = $db->db_fetch_row($db->db_query("select name from ".BAB_SITES_TBL." where id='".$id."'"));
	$req = "update ".BAB_SITES_TBL." set ";

	$req .= "total_diskspace='".$total_diskspace."', user_diskspace='".$user_diskspace."', folder_diskspace='".$folder_diskspace."', maxfilesize='".$maxfilesize."', uploadpath='".$uploadpath."', babslogan='".$babslogan."', remember_login='".$remember_login."', email_password='".$email_password."', change_password='".$change_password."', change_nickname='".$change_nickname."', name_order='".$name_order."' where id='".$id."'";
	$db->db_query($req);

	Header("Location: ". $GLOBALS['babUrlScript']."?tg=sites&idx=list");
	}

function siteUpdate_authentification($id, $authtype, $host, $ldpapchkcnx, $basedn, $userdn, $ldappass1, $ldappass2, $searchdn, $passtype)
	{
	global $babBody, $bab_ldapAttributes, $nickname, $i_nickname;

	if( empty($host))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a host address !!");
		return false;
		}

	if( $ldappass1 != $ldappass2)
		{
		$babBody->msgerror = bab_translate("ERROR: Passwords not match !!");
		return false;
		}

	if( (!isset($nickname) || empty($nickname)) && (!isset($i_nickname) || empty($i_nickname)))
		{
		$babBody->msgerror = bab_translate("You must provide a nickname");
		return false;
		}

	$ldapattr = empty($nickname) ? $i_nickname: $nickname;

	$db = $GLOBALS['babDB'];

	$req = "update ".BAB_SITES_TBL." set authentification='".$authtype."'";
	if( $authtype == 1 )
		{
		$req .= ", ldap_host='".$host."', ldap_allowadmincnx='".$ldpapchkcnx."', ldap_basedn='".$basedn."', ldap_userdn='".$userdn."', ldap_searchdn='".$searchdn."', ldap_attribute='".$ldapattr."', ldap_passwordtype='".$passtype."'";
		if( !empty($ldappass1) )
			$req .= ", ldap_password=ENCODE(\"".$ldappass1."\",\"".$GLOBALS['BAB_HASH_VAR']."\")";

		}
	$req .= " where id='".$id."'";
	$db->db_query($req);

	$res = $db->db_query("select * from ".BAB_DBDIR_FIELDS_TBL."");
	while( $row = $db->db_fetch_array($res))
		{
		$val = '';
		if( isset($GLOBALS[$row['name']]) && !empty($GLOBALS[$row['name']]))
			{
			$val = $GLOBALS[$row['name']];
			}
		else
			{
			$var = "i_".$row['name'];
			if( isset($GLOBALS[$var]) && !empty($GLOBALS[$var]))
				{
				$val = $GLOBALS[$var];
				}
			}
		$db->db_query("update ".BAB_LDAP_SITES_FIELDS_TBL." set x_name='".$val."' where name='".$row['name']."' and id_site='".$id."'");
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=sites&idx=list");
	}

function siteUpdateRegistration($item, $rw, $rq, $ml, $cdp, $cen, $group)
{
	global $babBody, $babDB;

	$babDB->db_query("update ".BAB_SITES_TBL." set display_disclaimer='".$cdp."', email_confirm='".$cen."', idgroup='".$group."' where id='".$item."'");
	$res = $babDB->db_query("select * from ".BAB_DBDIR_FIELDS_TBL);
	while( $arr = $babDB->db_fetch_array($res))
		{
		if( count($rw) > 0 && in_array($arr['id'], $rw))
			{
			$registration = "Y";
			}
		else
			{
			$registration = "N";
			}
		if( count($rq) > 0 && in_array($arr['id'], $rq))
			{
			$required = "Y";
			}
		else
			{
			$required = "N";
			}
		if( count($ml) > 0 && in_array($arr['id'], $ml))
			{
			$multilignes = "Y";
			}
		else
			{
			$multilignes = "N";
			}
		$req = "update ".BAB_SITES_FIELDS_REGISTRATION_TBL." set registration='".$registration."', required='".$required."', multilignes='".$multilignes."' where id_site='".$item."' and id_field='".$arr['id']."'";
		$babDB->db_query($req);
		}
}

function confirmDeleteSite($id)
	{
	$db = $GLOBALS['babDB'];
	// delete homepages
	$db->db_query("delete from ".BAB_HOMEPAGES_TBL." where id_site='".$id."'");
	// delete ldap settings
	$db->db_query("delete from ".BAB_LDAP_SITES_FIELDS_TBL." where id_site='".$id."'");
	// delete registration settings
	$db->db_query("delete from ".BAB_SITES_FIELDS_REGISTRATION_TBL." where id_site='".$id."'");
	$db->db_query("delete from ".BAB_SITES_DISCLAIMERS_TBL." where id_site='".$id."'");
	// delete site
	$db->db_query("delete from ".BAB_SITES_TBL." where id='".$id."'");
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=sites&idx=list");
	}

function siteUpdateDisclaimer($item, $content)
	{
	global $babDB;

	if( !bab_isMagicQuotesGpcOn())
		{
		$content = addslashes($content);
		}
	$babDB->db_query("update ".BAB_SITES_DISCLAIMERS_TBL." set disclaimer_text='".$content."' where id_site='".$item."'");
	return true;
	}

/* main */
if( !isset($BAB_SESS_LOGGED) || empty($BAB_SESS_LOGGED) ||  !$babBody->isSuperAdmin)
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( isset($modify) && $modify=="bloc1")
	{
	if( !empty($Submit))
		{
		if(!siteUpdate_bloc1($item, $name, $description, $lang, $style, $siteemail, $skin, $register, $mailfunc, $server, $serverport, $imgsize, $smtpuser, $smtppass, $smtppass2, $babLangFilter->convertFilterToInt($langfilter), $adminname))
			$idx = "modify";
		}
	else if( !empty($delete))
		{
		$idx = "Delete";
		}
	}
elseif( isset($modify) && $modify=="bloc2")
	{
	if( !empty($Submit))
		{
		if(!siteUpdate_bloc2($item,$total_diskspace, $user_diskspace, $folder_diskspace, $maxfilesize, $uploadpath, $babslogan, $remember_login, $email_password,  $change_password, $change_nickname, $name_order))
			$idx = "modify";
		}
	}
elseif( isset($modify) && $modify =="bloc3")
	{
	if( !empty($Submit))
		{
		if( !isset($passtype)) { $passtype='text';}
		if( !isset($ldpapchkcnx)) { $ldpapchkcnx='N';}
		if(!siteUpdate_authentification($item, $authtype, $host, $ldpapchkcnx, $basedn, $userdn, $ldappass1, $ldappass2, $searchdn, $passtype))
			$idx = "auth";
		}
	}
elseif( isset($update) )
	{
	if( $update == "updisc" )
		{
		siteUpdateDisclaimer($item, $content);
		$popupmessage = bab_translate("Update done");
		}
	else if( $update == "enreg" )
		{
		if (!isset($ml)) { $ml = array(); }
		if (!isset($rw)) { $rw = array(); }
		if (!isset($req)) { $req = array(); }
		if (!isset($cdp)) { $cdp = 'N'; }
		if (!isset($cen)) { $cen = 'N'; }
		siteUpdateRegistration($item, $rw, $req, $ml, $cdp, $cen, $group);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=sites&idx=list");
		exit;
		}
	}
elseif( isset($aclman) )
	{
	maclGroups();
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=sites&idx=list");
	}

if( !isset($idx))
	$idx = "modify";

if( isset($action) && $action == "Yes")
	{
	confirmDeleteSite($site);
	}

switch($idx)
	{
	case "unload":
		include_once $babInstallPath."utilit/uiutil.php";
		if( !isset($popupmessage)) { $popupmessage ='';}
		if( !isset($refreshurl)) { $refreshurl ='';}
		popupUnload($popupmessage, $refreshurl);
		exit;
	case "editdp":
		if( !isset($content)) { $content ='';}
		editDisclaimerPrivacy($item, $content);
		exit;
		break;
	case "cnx":
		$babBody->title = bab_translate("Registration").": ".getSiteName($item);
		siteRegistration($item);
		$babBody->addItemMenu("List", bab_translate("Sites"),$GLOBALS['babUrlScript']."?tg=sites&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=site&idx=modify&item=".$item);
		$babBody->addItemMenu("hpriv", bab_translate("Private"),$GLOBALS['babUrlScript']."?tg=site&idx=hpriv&item=".$item);
		$babBody->addItemMenu("hpub", bab_translate("Public"),$GLOBALS['babUrlScript']."?tg=site&idx=hpub&item=".$item);
		$babBody->addItemMenu("auth", bab_translate("Authentification"),$GLOBALS['babUrlScript']."?tg=site&idx=auth&item=".$item);
		$babBody->addItemMenu("cnx", bab_translate("Registration"),$GLOBALS['babUrlScript']."?tg=site&idx=cnx&item=".$item);
		break;

	case "auth":
		$babBody->title = bab_translate("Authentification").": ".getSiteName($item);
		siteAuthentification($item);
		$babBody->addItemMenu("List", bab_translate("Sites"),$GLOBALS['babUrlScript']."?tg=sites&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=site&idx=modify&item=".$item);
		$babBody->addItemMenu("hpriv", bab_translate("Private"),$GLOBALS['babUrlScript']."?tg=site&idx=hpriv&item=".$item);
		$babBody->addItemMenu("hpub", bab_translate("Public"),$GLOBALS['babUrlScript']."?tg=site&idx=hpub&item=".$item);
		$babBody->addItemMenu("auth", bab_translate("Authentification"),$GLOBALS['babUrlScript']."?tg=site&idx=auth&item=".$item);
		$babBody->addItemMenu("cnx", bab_translate("Registration"),$GLOBALS['babUrlScript']."?tg=site&idx=cnx&item=".$item);
		break;

	case "hman":
		$babBody->title = bab_translate("Home pages managers").": ".getSiteName($item);
		$macl = new macl("site", "modify", $item, "aclman");
        $macl->addtable( BAB_SITES_HPMAN_GROUPS_TBL,bab_translate("Who can manage home pages for this site?"));
		$macl->filter(0,0,1,1,1);
        $macl->babecho();
		$babBody->addItemMenu("List", bab_translate("Sites"),$GLOBALS['babUrlScript']."?tg=sites&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=site&idx=modify&item=".$item);
		$babBody->addItemMenu("hman", bab_translate("Managers"),$GLOBALS['babUrlScript']."?tg=site&idx=hman&item=".$item);
		$babBody->addItemMenu("auth", bab_translate("Authentification"),$GLOBALS['babUrlScript']."?tg=site&idx=auth&item=".$item);
		$babBody->addItemMenu("cnx", bab_translate("Registration"),$GLOBALS['babUrlScript']."?tg=site&idx=cnx&item=".$item);
		break;

	case "Delete":
		$babBody->title = getSiteName($item);
		sectionDelete($item);
		$babBody->addItemMenu("List", bab_translate("Sites"),$GLOBALS['babUrlScript']."?tg=sites&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=site&idx=modify&item=".$item);
		$babBody->addItemMenu("Delete", bab_translate("Delete"),$GLOBALS['babUrlScript']."?tg=site&idx=Delete&item=".$item);
		break;
	default:
	case "modify":
		$babBody->title = getSiteName($item);
		siteModify($item);
		$babBody->addItemMenu("List", bab_translate("Sites"),$GLOBALS['babUrlScript']."?tg=sites&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=site&idx=modify&item=".$item);
		$babBody->addItemMenu("hpriv", bab_translate("Private"),$GLOBALS['babUrlScript']."?tg=site&idx=hpriv&item=".$item);
		$babBody->addItemMenu("hpub", bab_translate("Public"),$GLOBALS['babUrlScript']."?tg=site&idx=hpub&item=".$item);
		$babBody->addItemMenu("auth", bab_translate("Authentification"),$GLOBALS['babUrlScript']."?tg=site&idx=auth&item=".$item);
		$babBody->addItemMenu("cnx", bab_translate("Registration"),$GLOBALS['babUrlScript']."?tg=site&idx=cnx&item=".$item);
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>