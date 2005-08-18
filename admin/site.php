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
include_once $babInstallPath."utilit/dirincl.php";
include_once $babInstallPath."utilit/nwdaysincl.php";

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

		var $date_lformat_title;
		var $date_sformat_title;
		var $time_format_title;
		var $date_lformat_val;
		var $date_sformat_val;
		var $time_format_val;

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
			$this->stattxt = bab_translate("Enable statistics recording")."?";
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
			$this->regsettings_title = bab_translate("Date and Time formats");
			$this->date_lformat_title = bab_translate("Long date format");
			$this->date_sformat_title = bab_translate("Short date format");
			$this->time_format_title = bab_translate("Time format");

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
			$this->login_only = bab_translate("Login only");
			$this->email_password_title = bab_translate("Display option 'Lost Password'");
			$this->babslogan_title = bab_translate("Site slogan");
			$this->uploadpath_title = bab_translate("Upload path");
			$this->maxfilesize_title = bab_translate("File manager max file size");
			$this->folder_diskspace_title = bab_translate("File manager max group directory size");
			$this->user_diskspace_title = bab_translate("File manager max user directory size");
			$this->total_diskspace_title = bab_translate("File manager max total size");
			$this->user_workdays_title = bab_translate("User can modifiy his working days");

			// bloc 4
			$this->t_workdays = bab_translate("Working days");
			$this->t_nonworking = bab_translate("Non-working days");
			$this->t_add = bab_translate("Add");
			$this->t_ok = bab_translate("Ok");
			$this->t_delete = bab_translate("Delete");
			$this->t_load_date = bab_translate("Load date");
			$this->t_date = bab_translate("Date");
			$this->t_text = bab_translate("Name");
			$this->t_type_date = bab_translate("Date type");
			$this->t_type = bab_getNonWorkingDayTypes(true);

			$this->id = $id;
			$this->langfiltertxt = bab_translate("Language filter");

			$this->db = $GLOBALS['babDB'];
			$req = "select *, DECODE(smtppassword, \"".$GLOBALS['BAB_HASH_VAR']."\") as smtppass from ".BAB_SITES_TBL." where id='$id'";
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
				$this->date_lformat_val = $arr['date_longformat'];
				$this->date_sformat_val = $arr['date_shortformat'];
				$this->time_format_val = $arr['time_format'];
				if( $arr['registration'] == "Y")
					{
					$this->nregister = "";
					$this->yregister = "selected";
					}
				else
					{
					$this->yregister = "";
					$this->nregister = "selected";
					}
				if( $arr['stat_log'] == "Y")
					{
					$this->nstatlog = "";
					$this->ystatlog = "selected";
					}
				else
					{
					$this->ystatlog = "";
					$this->nstatlog = "selected";
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
			$this->arrfiles = bab_getAvailableLanguages();
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

			$this->arrlfdate = array();
			$this->arrlfdate[] = "dd MMMM yyyy";
			$this->arrlfdate[] = "MMMM dd, yyyy";
			$this->arrlfdate[] = "dddd, MMMM dd, yyyy";
			$this->arrlfdate[] = "dddd, dd MMMM, yyyy";
			$this->arrlfdate[] = "dd MMMM, yyyy";

			$this->arrsfdate = array();
			$this->arrsfdate[] = "M/d/yyyy";
			$this->arrsfdate[] = "M/d/yy";
			$this->arrsfdate[] = "MM/dd/yy";
			$this->arrsfdate[] = "MM/dd/yyyy";
			$this->arrsfdate[] = "yy/MM/dd";
			$this->arrsfdate[] = "yyyy-MM-dd";
			$this->arrsfdate[] = "dd-MMM-yy";
			
			$this->arrtime = array();
			$this->arrtime[] = "HH:mm";
			$this->arrtime[] = "HH:mm tt";
			$this->arrtime[] = "HH:mm TT";
			$this->arrtime[] = "HH:mm:ss tt";
			$this->arrtime[] = "HH:mm:ss tt";
			$this->arrtime[] = "h:mm:ss tt";
			$this->arrtime[] = "hh:mm:ss tt";
			$this->arrtime[] = "HH:mm:ss";
			$this->arrtime[] = "H:m:s";


			$this->workdays = array_flip(explode(',',$GLOBALS['babBody']->babsite['workdays']));

			$this->resnw = $this->db->db_query("SELECT * FROM ".BAB_SITES_NONWORKING_CONFIG_TBL." WHERE id_site='".$id."'");

			}
		
		function getnextlongdate()
			{
			static $i = 0;
			if( $i < count($this->arrlfdate))
				{
                $this->dateval = $this->arrlfdate[$i];
                $this->datetxt = bab_formatDate( bab_getDateFormat($this->arrlfdate[$i]), mktime() );
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
                $this->dateval = $this->arrsfdate[$i];
                $this->datetxt = bab_formatDate( bab_getDateFormat($this->arrsfdate[$i]), mktime() );
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
                $this->timeval = $this->arrtime[$i];
                $this->timetxt = date( bab_getTimeFormat($this->arrtime[$i]), mktime() );
				$i++;
				return true;
				}
			else
				return false;
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
				$this->langfilterval =	$GLOBALS['babLangFilter']->getFilterStr($i);
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


		function getnextshortday()
			{
			global $babDays;
			static $i = 0;
			if ($i < 7)
				{
				if( isset($this->workdays[$i] ))
					{
					$this->checked = "checked";
					}
				else
					{
					$this->checked = "";
					}
				$this->dayid = $i;
				$this->shortday = $babDays[$i];
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}

		function getnextnonworking_type()
			{
			static $i = 1;
			if ($i < 100 && isset($this->t_type[$i]))
				{
				$this->type = $i;
				$this->txt = $this->t_type[$i];
				$i++;
				return true;
				}
			else
				{
				$i = 1;
				return false;
				}
			}


		function getnextnonworking()
			{
			if ($arr = $this->db->db_fetch_array($this->resnw))
				{
				$this->value = $arr['nw_text'].'#';
				$this->value .= $arr['nw_type'];
				$this->value .= !empty($arr['nw_day']) ? ','.$arr['nw_day'] : '';
				$this->nw_day = $arr['nw_day'];
				if (!empty($arr['nw_text']))
					$this->text = $arr['nw_text'];
				else
					{
					$this->text = $this->t_type[$arr['nw_type']];
					if (!empty($this->nw_day))
						{
						$this->text .= ' : '.$this->nw_day;
						}
					}
				return true;
				}
			else
				return false;
			}

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
			$req = "select *, DECODE(smtppassword, \"".$GLOBALS['BAB_HASH_VAR']."\") as smtppass from ".BAB_SITES_TBL." where id='$id'";
			$this->res = $this->db->db_query($req);
			if( $this->db->db_num_rows($this->res) > 0 )
				{
				$this->showform = true;
				$arr = $this->db->db_fetch_array($this->res);
				$this->modify = bab_translate("Modify");
				$this->authsite = $arr['authentification'];
				$this->ldaphost = $arr['ldap_host'];
				$this->ldaphostname = $arr['ldap_domainname'];
				$this->ldapsearchdnsite = $arr['ldap_searchdn'];
				$this->ldapattributesite = $arr['ldap_attribute'];
				$this->ldapencryptiontype = $arr['ldap_encryptiontype'];

				$this->authentificationtxt = bab_translate("Authentification");
				$this->arrayauth = array(0 => "OVIDENTIA", 1 => "LDAP", 2 => "ACTIVE DIRECTORY");

				$this->fieldrequiredtxt = bab_translate("Those fields are required");
				$this->domainnametxt = bab_translate("Domain name");
				$this->hosttxt = bab_translate("Host");
				$this->searchbasetxt = bab_translate("Search base");
				$this->attributetxt = bab_translate("Attribute");
				$this->ldpachkcnxtxt = bab_translate("Allow administrators to connect if LDAP authentification fails");

				$this->arrayauthpasstype = array(
					'plain' => bab_translate("plaintext"), 
					'md5-hex' => bab_translate("md5"), 
					'crypt' => bab_translate("The Unix crypt() hash, based on DES"), 
					'sha' => bab_translate("sha-1"), 
					'md5-base64' => bab_translate("md5 encoded with base64"), 
					'ssha' => bab_translate("Salted SHA-1"), 
					'smd5' => bab_translate("Salted MD5")
					);

				$this->authpasstypetxt = bab_translate("Password encryption type");

				if( $arr['ldap_allowadmincnx']  == 'Y' )
					{
					$this->ldpachkcnxchecked = 'checked';
					}
				else
					{
					$this->ldpachkcnxchecked = '';
					}

				$this->resf = $this->db->db_query("select * from ".BAB_LDAP_SITES_FIELDS_TBL." where id_site='".$id."'");
				if( $this->resf && $this->db->db_num_rows($this->resf) > 0)
					{
					$this->countf = $this->db->db_num_rows($this->resf);
					$this->countf++;
					}
				else
					{
					$this->countf = 0;
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

		function getnextfield()
			{
			global $bab_ldapAttributes;
			static $i = 0;
			if( $i < $this->countf)
				{
				$this->iindex = $i;
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
					$this->required = false;
					$arr = $this->db->db_fetch_array($this->resf);
					if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
						{
						$rr = $this->db->db_fetch_array($this->db->db_query("select name, description from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
						$this->ofieldname = translateDirectoryField($rr['description']);
						$filedname = $rr['name'];

						if (in_array($filedname,array('sn','givenname','email')))
							$this->required = true;
						}
					else
						{
						$rr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
						$this->ofieldname = translateDirectoryField($rr['name']);
						$filedname = "babdirf".$arr['id_field'];
						}


									
					$this->ofieldv = $filedname;
					$this->x_ofieldv = $arr['x_name'];

					if( in_array($arr['x_name'], $bab_ldapAttributes))
						{
						$this->ofieldval = '';
						}
					else
						{
						$this->ofieldval = $arr['x_name'];
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
						if( $this->ffieldname == $this->x_ofieldv )
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

		function getnextpasstype()
			{
			static $i = 0;
			if( $i < count($this->arrayauthpasstype))
				{
				list($this->passtypeval, $this->passtypename) = each($this->arrayauthpasstype);
                if( $this->ldapencryptiontype == $this->passtypeval )
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
			$this->confirmationstxt = array(bab_translate("Confirm account by validating address email"), bab_translate("Manual confirmation by administrators"), bab_translate("Confirm account without address email validation"));
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

			
			include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";

			$tree = new bab_grptree();
			$this->groups = $tree->getGroups(BAB_REGISTERED_GROUP, '%s &nbsp; &nbsp; &nbsp; ');
			unset($this->groups[BAB_ADMINISTRATOR_GROUP]);

			$this->arrsite = $this->db->db_fetch_array($this->db->db_query("select email_confirm, display_disclaimer, idgroup from ".BAB_SITES_TBL." where id='".$id."'"));
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
				$this->fieldid = $arr['id'];
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

				if (in_array( $arr['id_field'], array( 2, 3, 4, 6) ))
					{
					$this->disabled = true;
					}
				else 
					{
					$this->disabled = false;
					}

				if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
					{
					$res = $this->db->db_query("select description, name from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'");
					$rr = $this->db->db_fetch_array($res);
					$this->fieldn = translateDirectoryField($rr['description']);
					$this->fieldv = $rr['name'];
					}
				else
					{
					$rr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
					$this->fieldn = translateDirectoryField($rr['name']);
					$this->fieldv = "babdirf".$arr['id'];
					}
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextgrp()
			{

			if( list(,$arr) = each($this->groups) )
				{
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
				return true;
				}
			else
				{
				return false;
				}
			}

		function getnextoption()
			{
			static $i = 0;
			if( $i < count($this->confirmationstxt))
				{
                $this->optname = $this->confirmationstxt[$i];
                $this->optid = $i;
				if( $this->arrsite['email_confirm'] == $i )
					{
					$this->optsel = "selected";
					}
				else
					{
					$this->optsel = "";
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

			$this->editor = bab_editor($this->disclaimerval, 'content', 'discmod');

			}
		}

	$temp = new temp($id, $content);
	echo bab_printTemplate($temp,"sites.html", "disclaimeredit");
	}



function editor_configuration($id_site)
	{
	global $babBody;

	class temp
		{
		function temp($id_site)
			{
			$this->id_site = $id_site;
			$this->db = &$GLOBALS['babDB'];

			$this->t_use_editor = bab_translate("Use WYSIWYG editor");
			$this->t_filter_html = bab_translate("Filter and remove HTML elements");
			$this->t_allow_elements = bab_translate("Allow elements on the filter");
			$this->t_presentation = bab_translate("Presentation tags");
			$this->t_lists = bab_translate("Ordered lists, unordered lists, definitions lists");
			$this->t_tables = bab_translate("Tables");
			$this->t_nonhtml = bab_translate("Scripts and plugins");
			$this->t_images = bab_translate("Images");
			$this->t_iframes = bab_translate("Frames (open external url into document body)");
			$this->t_forms = bab_translate("Forms");
			$this->t_target = bab_translate("Targets on links (open links in new windows)");
			$this->t_submit = bab_translate("Submit");

			$res = $this->db->db_query("SELECT use_editor, filter_html, bitstring FROM ".BAB_SITES_EDITOR_TBL." WHERE id_site='".$id_site."'");

			$this->arr = $this->db->db_fetch_assoc($res);
				
			if (!$this->arr)
				{
				$this->arr = array(
						'use_editor' => 1,
						'filter_html' => 0,
						'bitstring' => '00000000'
					);
				}

			$this->bitstring = preg_split('//', $this->arr['bitstring'], -1, PREG_SPLIT_NO_EMPTY);
			$this->bitstring_len = count($this->bitstring);
			}
		}

	$temp = new temp($id_site);
	$babBody->babecho(bab_printTemplate($temp,"sites.html", "editor"));
	}


function record_editor_configuration($id_site)
{
	$db = &$GLOBALS['babDB'];

	$bitstring = array();
	for ($i = 0; $i < $_POST['bitstring_len']; $i++)
		{
		$bitstring[$i] = in_array($i,$_POST['bitstring']) ? 1 : 0;
		}


	$arr_tags = array(
			0 => 'font b a strong span div h1 h2 h3 h4 h5 p code i sup sub strike i em u abbr acronym del cite dfn ins kbd samp map area address br blockquote center hr q',
			1 => 'ul ol li dl dt dd',
			2 => 'table thead tbody tfoot tr td th caption colgroup col',
			3 => 'object script noscript param embed applet',
			4 => 'img',
			5 => 'iframe',
			6 => 'form input select optgroup textarea label fieldset button'
		);

	$arr_attributes = array(
			0 => 'src href accesskey coords hreflang rel rev shape tabindex type class dir id lang title style align border height width alt bgcolor usemap vspace hspace abbr background hspace nowrap char bordercolor bordercolordark bordercolorlight cite',
			2 => 'cellpadding cellspacing cols rules summary axis colspan rowspan scope',
			3 => 'onclick ondbclick onkeydown onkeypress onkeyup onmousedown onmousemove onmouseout onmouseover onmouseup onload onselect onfocus onblur onchange archive code codebase mayscript object units pluginspage palette hidden classid data declare notab shapes standby defer charset language',
			4 => 'longdesc',
			6 => 'value for',
			7 => 'target'
		);

	$use_editor = isset($_POST['use_editor']) ? 1 : 0;
	$filter_html = isset($_POST['filter_html']) ? 1 : 0;
	$tags = '';
	$attributes = '';
	$verify_href = $bitstring[3] ? 0 : 1;
	$i = 0;
	foreach ($bitstring as $bit)
		{
		if (isset($arr_tags[$i]) && $bit == 1)
			{
			$tags .= ' '.$arr_tags[$i];
			}

		if (isset($arr_attributes[$i]) && $bit == 1)
			{
			$attributes .= ' '.$arr_attributes[$i];
			}
		$i++;
		}

	$res = $db->db_query("SELECT id FROM ".BAB_SITES_EDITOR_TBL." WHERE id_site='".$id_site."'");
	if ($db->db_num_rows($res) > 0)
		{
		$db->db_query("UPDATE ".BAB_SITES_EDITOR_TBL." 
						SET 
							use_editor='".$use_editor."', 
							filter_html='".$filter_html."', 
							tags='".trim($tags)."', 
							attributes='".trim($attributes)."', 
							verify_href='".$verify_href."', 
							bitstring='".implode('',$bitstring)."' 
						WHERE 
							id_site='".$id_site."'
						");
		}
	else
		{
		$db->db_query("INSERT INTO ".BAB_SITES_EDITOR_TBL." 
						(use_editor, filter_html, tags, attributes, verify_href, bitstring, id_site)
						VALUES
							('".$use_editor."', 
							'".$filter_html."', 
							'".trim($tags)."', 
							'".trim($attributes)."', 
							'".$verify_href."', 
							'".implode('',$bitstring)."',
							'".$id_site."')
						");
		}
}


function siteUpdate_bloc1($id, $name, $description, $lang, $style, $siteemail, $skin, $register, $statlog, $mailfunc, $server, $serverport, $imgsize, $smtpuser, $smtppass, $smtppass2, $langfilter, $adminname)
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
		$req .= "description='".$description."', lang='".$lang."', adminemail='".$siteemail."', adminname='".$adminname."', skin='".$skin."', style='".$style."', registration='".$register."', stat_log='".$statlog."', mailfunc='".$mailfunc."', smtpserver='".$server."', smtpport='".$serverport."', imgsize='".$imgsize."', smtpuser='".$smtpuser."', smtppassword=ENCODE(\"".$smtppass."\",\"".$GLOBALS['BAB_HASH_VAR']."\"), langfilter='" .$langfilter. "' where id='".$id."'";
		$db->db_query($req);
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=sites&idx=list");
	}


function siteUpdate_bloc2($id,$total_diskspace, $user_diskspace, $folder_diskspace, $maxfilesize, $uploadpath, $babslogan, $remember_login, $email_password, $change_password, $change_nickname, $name_order, $user_workdays)
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

	$req .= "total_diskspace='".$total_diskspace."', user_diskspace='".$user_diskspace."', folder_diskspace='".$folder_diskspace."', maxfilesize='".$maxfilesize."', uploadpath='".$uploadpath."', babslogan='".$babslogan."', remember_login='".$remember_login."', email_password='".$email_password."', change_password='".$change_password."', change_nickname='".$change_nickname."', name_order='".$name_order."', user_workdays='".$user_workdays."' where id='".$id."'";
	$db->db_query($req);

	Header("Location: ". $GLOBALS['babUrlScript']."?tg=sites&idx=list");
	}

function siteUpdate_bloc3($item,$datelformat, $datesformat, $timeformat)
	{
	global $babBody;
	if( !bab_isMagicQuotesGpcOn())
		{
		$datelformat = addslashes($datelformat);
		$datesformat = addslashes($datesformat);
		$timeformat = addslashes($timeformat);
		}

	$db = $GLOBALS['babDB'];

	$req = "update ".BAB_SITES_TBL." set date_longformat='".$datelformat."', date_shortformat='".$datesformat."', time_format='".$timeformat."' where id='".$item."'";
	$db->db_query($req);

	Header("Location: ". $GLOBALS['babUrlScript']."?tg=sites&idx=list");
	}

function siteUpdate_bloc4($item)
	{
	$db = & $GLOBALS['babDB'];

	if (isset($_POST['workdays']) && count($_POST['workdays']))
		{
		$db->db_query("update ".BAB_SITES_TBL." set workdays='".implode(',',$_POST['workdays'])."' where id='".$item."'");
		}

	if (isset($_POST['nonworking']) && count($_POST['nonworking']))
		{
		$db->db_query("DELETE FROM ".BAB_SITES_NONWORKING_CONFIG_TBL."  where id_site='".$item."'");
		foreach($_POST['nonworking'] as $value)
			{
			$tmp = explode('#',$value);
			if (count($tmp) == 2)
				{
				$text = &$tmp[0];
				$nonworking = &$tmp[1];
				}
			else
				{
				$text = '';
				$nonworking = &$tmp[0];
				}
			$arr = explode(',',$nonworking);
			$type = $arr[0];
			$nw = isset($arr[1]) ? $arr[1] : '';
			if( !bab_isMagicQuotesGpcOn())
				{
				$text = addslashes($text);
				}
			$db->db_query("INSERT INTO ".BAB_SITES_NONWORKING_CONFIG_TBL." (id_site, nw_type, nw_day, nw_text) VALUES ('".$item."', '".$type."', '".$nw."', '".$text."')");
			}

		}

	bab_emptyNonWorkingDays($item);

	Header("Location: ". $GLOBALS['babUrlScript']."?tg=sites&idx=list");
	}

function siteUpdate_authentification($id, $authtype, $host, $hostname, $ldpapchkcnx, $searchdn)
	{
	global $babBody, $bab_ldapAttributes, $nickname, $i_nickname, $crypttype;

	if (!function_exists('ldap_connect'))
		{
		$babBody->msgerror = bab_translate("You must have LDAP enabled on the server");
		return false;
		}

	if (!function_exists('utf8_decode'))
		{
		$babBody->msgerror = bab_translate("You must have XML enabled on the server");
		return false;
		}

	if( empty($host))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a host address !!");
		return false;
		}

	if( $authtype == 1 )
		{
		if( (!isset($nickname) || empty($nickname)) && (!isset($i_nickname) || empty($i_nickname)))
			{
			$babBody->msgerror = bab_translate("You must provide a nickname");
			return false;
			}
		}

	$ldapattr = empty($nickname) ? $i_nickname: $nickname;

	$db = $GLOBALS['babDB'];

	$req = "update ".BAB_SITES_TBL." set authentification='".$authtype."'";
	$req .= ", ldap_host='".$host."', ldap_domainname='".$hostname."', ldap_allowadmincnx='".$ldpapchkcnx."', ldap_searchdn='".$searchdn."', ldap_attribute='".$ldapattr."', ldap_encryptiontype='".$crypttype."'";
	$req .= " where id='".$id."'";
	$db->db_query($req);

	$res = $db->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='0'");
	while( $arr = $db->db_fetch_array($res))
		{
		$val = '';
		if( $arr['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
			{
			$rr = $db->db_fetch_array($db->db_query("select name, description from ".BAB_DBDIR_FIELDS_TBL." where id='".$arr['id_field']."'"));
			$fieldname = $rr['name'];
			}
		else
			{
			$rr = $db->db_fetch_array($db->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($arr['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
			$fieldname = "babdirf".$arr['id_field'];
			}

		if( isset($GLOBALS[$fieldname]) && !empty($GLOBALS[$fieldname]))
			{
			$val = $GLOBALS[$fieldname];
			}
		else
			{
			$var = "i_".$fieldname;
			if( isset($GLOBALS[$var]) && !empty($GLOBALS[$var]))
				{
				$val = $GLOBALS[$var];
				}
			}
		$db->db_query("update ".BAB_LDAP_SITES_FIELDS_TBL." set x_name='".$val."' where id_field='".$arr['id_field']."' and id_site='".$id."'");
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=sites&idx=list");
	}

function siteUpdateRegistration($item, $rw, $rq, $ml, $cdp, $cen, $group)
{
	global $babBody, $babDB;

	$babDB->db_query("update ".BAB_SITES_TBL." set display_disclaimer='".$cdp."', email_confirm='".$cen."', idgroup='".$group."' where id='".$item."'");
	$res = $babDB->db_query("select id from ".BAB_SITES_FIELDS_REGISTRATION_TBL." where id_site='".$item."'");
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
		$req = "update ".BAB_SITES_FIELDS_REGISTRATION_TBL." set registration='".$registration."', required='".$required."', multilignes='".$multilignes."' where id='".$arr['id']."'";
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
	$db->db_query("delete from ".BAB_LDAP_SITES_FIELDS_TBL." where id_site='".$id."'");
	$db->db_query("delete from ".BAB_SITES_DISCLAIMERS_TBL." where id_site='".$id."'");
	// delete site
	$db->db_query("delete from ".BAB_SITES_TBL." where id='".$id."'");
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=sites&idx=list");
	}

function siteUpdateDisclaimer($item, $content)
	{
	global $babDB;

	if( bab_isMagicQuotesGpcOn())
		{
		$content = stripslashes($content);
		}

	bab_editor_record($content);

	$db = &$GLOBALS['babDB'];
	$content = $db->db_escape_string($content);

	$babDB->db_query("update ".BAB_SITES_DISCLAIMERS_TBL." set disclaimer_text='".$content."' where id_site='".$item."'");
	return true;
	}

/* main */
if( !isset($BAB_SESS_LOGGED) || empty($BAB_SESS_LOGGED) ||  !$babBody->isSuperAdmin)
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if (isset($_POST['modify']))
{
switch ($_POST['modify'])
	{

	case 'bloc1':
		if( !empty($Submit))
			{
			if(!siteUpdate_bloc1($item, $name, $description, $lang, $style, $siteemail, $skin, $register, $statlog, $mailfunc, $server, $serverport, $imgsize, $smtpuser, $smtppass, $smtppass2, $babLangFilter->convertFilterToInt($langfilter), $adminname))
				$idx = "modify";
			}
		else if( !empty($delete))
			{
			$idx = "Delete";
			}
		break;

	case 'bloc2':
		if( !empty($Submit))
			{
			if(!siteUpdate_bloc2($item,$total_diskspace, $user_diskspace, $folder_diskspace, $maxfilesize, $uploadpath, $babslogan, $remember_login, $email_password,  $change_password, $change_nickname, $name_order, $user_workdays))
				$idx = "modify";
			}
		break;

	case 'bloc3':
		if( !empty($Submit))
			{
			if(!siteUpdate_bloc3($item,$datelformat, $datesformat, $timeformat))
				$idx = "modify";
			}
		break;

	case 'bloc4':
		siteUpdate_bloc4($_POST['item']);

		break;


	case 'auth':
		if( !empty($Submit))
			{
			if( !isset($hostname)) { $hostname='';}
			if( !isset($ldpapchkcnx)) { $ldpapchkcnx='N';}
			if(!siteUpdate_authentification($item, $authtype, $host, $hostname, $ldpapchkcnx, $searchdn))
				$idx = "auth";
			}
		break;
	}
}


if( isset($update) )
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
		if (!isset($cen)) { $cen = '0'; }
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

if( isset($_POST['action']) && $_POST['action'] == "editor_configuration")
	{
	record_editor_configuration($_POST['id_site']);
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
		include_once $babInstallPath."utilit/dirincl.php";
		siteRegistration($item);
		$babBody->addItemMenu("List", bab_translate("Sites"),$GLOBALS['babUrlScript']."?tg=sites&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=site&idx=modify&item=".$item);
		$babBody->addItemMenu("hman", bab_translate("Managers"),$GLOBALS['babUrlScript']."?tg=site&idx=hman&item=".$item);
		$babBody->addItemMenu("auth", bab_translate("Authentification"),$GLOBALS['babUrlScript']."?tg=site&idx=auth&item=".$item);
		$babBody->addItemMenu("cnx", bab_translate("Registration"),$GLOBALS['babUrlScript']."?tg=site&idx=cnx&item=".$item);
		$babBody->addItemMenu("editor", bab_translate("Editor"),$GLOBALS['babUrlScript']."?tg=site&idx=editor&item=".$item);
		break;

	case "auth":
		$babBody->title = bab_translate("Authentification").": ".getSiteName($item);
		siteAuthentification($item);
		$babBody->addItemMenu("List", bab_translate("Sites"),$GLOBALS['babUrlScript']."?tg=sites&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=site&idx=modify&item=".$item);
		$babBody->addItemMenu("hman", bab_translate("Managers"),$GLOBALS['babUrlScript']."?tg=site&idx=hman&item=".$item);
		$babBody->addItemMenu("auth", bab_translate("Authentification"),$GLOBALS['babUrlScript']."?tg=site&idx=auth&item=".$item);
		$babBody->addItemMenu("cnx", bab_translate("Registration"),$GLOBALS['babUrlScript']."?tg=site&idx=cnx&item=".$item);
		$babBody->addItemMenu("editor", bab_translate("Editor"),$GLOBALS['babUrlScript']."?tg=site&idx=editor&item=".$item);
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
		$babBody->addItemMenu("editor", bab_translate("Editor"),$GLOBALS['babUrlScript']."?tg=site&idx=editor&item=".$item);
		break;

	case "editor":
		if (isset($_POST['id_site']))
			$item = $_POST['id_site'];

		$babBody->title = bab_translate("Editor").": ".getSiteName($item);

		editor_configuration($item);

		$babBody->addItemMenu("List", bab_translate("Sites"),$GLOBALS['babUrlScript']."?tg=sites&idx=list");
		$babBody->addItemMenu("modify", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=site&idx=modify&item=".$item);
		$babBody->addItemMenu("hman", bab_translate("Managers"),$GLOBALS['babUrlScript']."?tg=site&idx=hman&item=".$item);
		$babBody->addItemMenu("auth", bab_translate("Authentification"),$GLOBALS['babUrlScript']."?tg=site&idx=auth&item=".$item);
		$babBody->addItemMenu("cnx", bab_translate("Registration"),$GLOBALS['babUrlScript']."?tg=site&idx=cnx&item=".$item);
		$babBody->addItemMenu("editor", bab_translate("Editor"),$GLOBALS['babUrlScript']."?tg=site&idx=editor&item=".$item);
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
		$babBody->addItemMenu("hman", bab_translate("Managers"),$GLOBALS['babUrlScript']."?tg=site&idx=hman&item=".$item);
		$babBody->addItemMenu("auth", bab_translate("Authentification"),$GLOBALS['babUrlScript']."?tg=site&idx=auth&item=".$item);
		$babBody->addItemMenu("cnx", bab_translate("Registration"),$GLOBALS['babUrlScript']."?tg=site&idx=cnx&item=".$item);
		$babBody->addItemMenu("editor", bab_translate("Editor"),$GLOBALS['babUrlScript']."?tg=site&idx=editor&item=".$item);
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>