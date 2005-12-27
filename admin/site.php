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


class site_configuration_cls
{
	var $menu;
	
	function site_configuration_cls($id_site = false)
	{
	$this->t_record = bab_translate("Record");
	$this->yes = bab_translate("Yes");
	$this->no = bab_translate("No");

	$this->item = $id_site;
	$this->db = &$GLOBALS['babDB'];

	$this->menu = array(
			1 => bab_translate('Site configuration'),
			2 => bab_translate('Mail configuration'),
			3 => bab_translate('User options and login configuration'),
			4 => bab_translate('File upload configuration'),
			5 => bab_translate('Date format configuration'),
			6 => bab_translate('Calendar and vacations configuration'),
			7 => bab_translate('Home page managers'),
			8 => bab_translate('Authentification configuration'),
			9 => bab_translate('Inscription configuration'),
			10=> bab_translate('WYSIWYG editor configuration')
		);

	if (isset($GLOBALS['babSearchEngine']))
		{
		$this->menu[11] = bab_translate('Search engine configuration');
		}

	if (false !== $id_site)
		{
		$res = $this->db->db_query("SELECT *, DECODE(smtppassword, \"".$GLOBALS['BAB_HASH_VAR']."\") as smtppass FROM ".BAB_SITES_TBL." WHERE id='".$id_site."'");
		$this->row = $this->db->db_fetch_assoc($res);

		$this->arr = array();
		foreach($this->row as $k => $val)
			{
			$this->arr[$k] = htmlentities($val);
			}

		if (isset($_REQUEST['idx']))
			{
			$key = (int) substr($_REQUEST['idx'],4);
			if (isset($this->menu[$key]))
				$GLOBALS['babBody']->title = $this->menu[$key];
			}
		}
	else
		{
		$GLOBALS['babBody']->title = bab_translate("Create site");
		}



	}
}


function site_menu1()
{
	global $babBody;
	class temp extends site_configuration_cls
		{

		function temp()
			{
			$this->name = bab_translate("Site name");
			$this->description = bab_translate("Description");
			$this->lang = bab_translate("Lang");
			$this->skin = bab_translate("Skin");
			$this->confirmation = bab_translate("Send email confirmation")."?";
			
			$this->stattxt = bab_translate("Enable statistics recording")."?";
			$this->disabled = bab_translate("Disabled");
			$this->imagessize = bab_translate("Max image size ( Kb )");
			$this->langfiltertxt = bab_translate("Language filter");
			$this->siteemail = bab_translate("Email site");
			$this->adminnametxt = bab_translate("Name to use for notification emails");
			$this->name_order_title = bab_translate("User name composition");
			$this->firstlast = bab_translate("Firstname")." ".bab_translate("Lastname");
			$this->lastfirst = bab_translate("Lastname")." ".bab_translate("Firstname");
			$this->babslogan_title = bab_translate("Site slogan");
			
			
			$this->skselectedindex = 0;
			$this->stselectedindex = 0;
			
			
			if (empty($_REQUEST['item']))
				{
				$this->site_configuration_cls();
				$this->row = $this->arr = array(
						'name'			=> '',
						'description'	=> '',
						'lang'			=> '',
						'adminname'		=> '',
						'adminemail'	=> '',
						'skin'			=> '',
						'style'			=> '',
						'babslogan'		=> '',
						'langfilter'	=> ''
					);

				$this->item = '';
				}
			else
				{
				$this->site_configuration_cls($_REQUEST['item']);
				}
			
			
			
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

			}

		function getnextlang()
			{
			static $i = 0;
			if( $i < $this->count)
				{
                $this->langval = $this->arrfiles[$i];
                if( $this->row['lang'] == $this->langval )
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
                if( $this->skinname == $this->row['skin'] )
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
                if( $this->skinname == $this->row['skin'] && $this->row['style'] == $this->styleval)
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
				if($this->row['langfilter'] == $i )
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

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "sites.html", "menu1"));
}


function site_menu2($id)
	{

	global $babBody;
	class temp extends site_configuration_cls
		{

		function temp($id)
			{
			$this->disabled = bab_translate("Disabled");
			$this->mailfunction = bab_translate("Mail function");
			$this->server = bab_translate("Smtp server");
			$this->serverport = bab_translate("Server port");
			$this->none = bab_translate("None");
			$this->smtpuser = bab_translate("SMTP username");
			$this->smtppass = bab_translate("SMTP password");
			$this->smtppass2 = bab_translate("Re-type SMTP password");
			$this->smtp = "smtp";
			$this->sendmail = "sendmail";
			$this->mail = "mail";


			$this->site_configuration_cls($id);
			}


		} // class temp

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp, "sites.html", "menu2"));
	}


function site_menu3($id)
	{

	global $babBody;
	class temp extends site_configuration_cls
		{

		function temp($id)
			{
			$this->change_nickname_title = bab_translate("User can modifiy his nickname");
			$this->change_password_title = bab_translate("User can modifiy his password");
			$this->remember_login_title = bab_translate("Automatic connection");
			$this->login_only = bab_translate("Login only");
			$this->email_password_title = bab_translate("Display option 'Lost Password'");
			$this->user_workdays_title = bab_translate("User can modifiy his working days");			
			$this->change_lang_title = bab_translate("User can modifiy his language");
			$this->change_skin_title = bab_translate("User can modifiy his skin");
			$this->change_date_title = bab_translate("User can modifiy his date format");
			$this->change_unavailability_title = bab_translate("User can modifiy his unavailability");

			$this->site_configuration_cls($id);
			}


		} // class temp

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp, "sites.html", "menu3"));
	}


function site_menu4($id)
	{

	global $babBody;
	class temp extends site_configuration_cls
		{

		function temp($id)
			{
			$this->imgsize_title = bab_translate("Max image size ( Kb )");
			$this->uploadpath_title = bab_translate("Upload path");
			$this->maxfilesize_title = bab_translate("File manager max file size");
			$this->folder_diskspace_title = bab_translate("File manager max group directory size");
			$this->user_diskspace_title = bab_translate("File manager max user directory size");
			$this->total_diskspace_title = bab_translate("File manager max total size");
			$this->t_mb = bab_translate("Mb");
			
			$this->site_configuration_cls($id);
			}


		} // class temp

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp, "sites.html", "menu4"));
	}


function site_menu5($id)
	{

	global $babBody;
	class temp extends site_configuration_cls
		{

		function temp($id)
			{

			$this->regsettings_title = bab_translate("Date and Time formats");
			$this->date_lformat_title = bab_translate("Long date format");
			$this->date_sformat_title = bab_translate("Short date format");
			$this->time_format_title = bab_translate("Time format");

			$this->site_configuration_cls($id);

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


		} // class temp

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp, "sites.html", "menu5"));
	}



function site_menu6($id)
	{

	global $babBody;
	class temp extends site_configuration_cls
		{

		function temp($id)
			{
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
			
			$this->site_configuration_cls($id);

			$this->workdays = array_flip(explode(',',$GLOBALS['babBody']->babsite['workdays']));
			$this->resnw = $this->db->db_query("SELECT * FROM ".BAB_SITES_NONWORKING_CONFIG_TBL." WHERE id_site='".$id."'");

			}

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
	$babBody->babecho(	bab_printTemplate($temp, "sites.html", "menu6"));
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
			$req = "select *, DECODE(smtppassword, \"".$GLOBALS['BAB_HASH_VAR']."\") as smtppass, DECODE(ldap_adminpassword, \"".$GLOBALS['BAB_HASH_VAR']."\") as ldapadminpwd from ".BAB_SITES_TBL." where id='$id'";
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
				$this->ldapfiltersite = $arr['ldap_filter'];
				$this->ldapadmindn = $arr['ldap_admindn'];
				$this->ldapadminpwd1 = $arr['ldapadminpwd'];
				$this->ldapadminpwd2 = $arr['ldapadminpwd'];

				$this->authentificationtxt = bab_translate("Authentification");
				$this->arrayauth = array(BAB_AUTHENTIFICATION_OVIDENTIA => "OVIDENTIA", BAB_AUTHENTIFICATION_LDAP => "LDAP", BAB_AUTHENTIFICATION_AD => "ACTIVE DIRECTORY");

				$this->fieldrequiredtxt = bab_translate("Those fields are required");
				$this->domainnametxt = bab_translate("Domain name");
				$this->hosttxt = bab_translate("Host");
				$this->searchbasetxt = bab_translate("Search base");
				$this->attributetxt = bab_translate("Attribute");
				$this->filtertxt = bab_translate("Filter");
				$this->admindntxt = bab_translate("Admin DN");
				$this->adminpwd1txt = bab_translate("Admin password");
				$this->adminpwd2txt = bab_translate("Re-type admin password");
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
	$babBody->babecho(	bab_printTemplate($temp, "sites.html", "menu8"));
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
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->registration = bab_translate("Activate Registration")."?";
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

			$this->arrsite = $this->db->db_fetch_array($this->db->db_query("select registration, email_confirm, display_disclaimer, idgroup from ".BAB_SITES_TBL." where id='".$id."'"));
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
	$babBody->babecho( bab_printTemplate($temp,"sites.html", "menu9"));
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
	$babBody->babecho(bab_printTemplate($temp,"sites.html", "menu10"));
	}


function siteMenu($id_site)
{
global $babBody;

	class temp extends site_configuration_cls
		{
		function temp($id_site)
			{
			$this->t_delete = bab_translate("Delete");
			$this->site_configuration_cls($id_site);
			}

		function getnext()
			{
			return list($this->page,$this->text) = each($this->menu);
			}
		}

	$temp = new temp($id_site);
	$babBody->babecho(bab_printTemplate($temp,"sites.html", "menu"));
}



/* ************************** RECORD ************************** */



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




function siteSave($name, $description,$babslogan, $lang, $siteemail, $skin, $style, $langfilter, $adminname, $name_order, $statlog)
	{
	global $babBody;
	$db = &$GLOBALS['babDB'];

	$query = "insert into ".BAB_SITES_TBL." (name, description, lang, adminemail, adminname, skin, style, stat_log,  langfilter, babslogan, name_order) VALUES ('" .$name. "', '" . $description. "', '" . $lang. "', '" . $siteemail. "', '" . $adminname. "', '" . $skin. "', '" . $style. "', '" . $statlog. "','".$langfilter."','". $babslogan."','". $name_order."')";
	$db->db_query($query);
	$idsite = $db->db_insert_id();

	$db->db_query("insert into ".BAB_SITES_DISCLAIMERS_TBL." (id_site, disclaimer_text) values ('".$idsite."','')");

	$resf = $db->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='0'");
	while( $row = $db->db_fetch_array($resf))
		{
		$db->db_query("insert into ".BAB_LDAP_SITES_FIELDS_TBL." (id_field, x_name, id_site) values ('".$row['id_field']."','','".$idsite."')");
		$db->db_query("insert into ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes) values ('".$idsite."', '".$row['id_field']."','N','N', 'N')");
		}

	$db->db_query("update ".BAB_SITES_FIELDS_REGISTRATION_TBL." set registration='Y', required='Y' where id_site='".$idsite."' and id_field IN ('2', '4', '6')");	
	$db->db_query("update ".BAB_SITES_FIELDS_REGISTRATION_TBL." set registration='Y' where id_site='".$idsite."' and id_field='3'");	

	return $idsite;
	}

function siteUpdate_menu1()
{
	$db = &$GLOBALS['babDB'];
	global $babBody;

	$name			= &$_POST['name'];
	$description	= &$_POST['description'];
	$babslogan		= &$_POST['babslogan'];
	$lang			= &$_POST['lang'];
	$style			= &$_POST['style'];
	$siteemail		= &$_POST['siteemail'];
	$adminname		= &$_POST['adminname'];
	$skin			= &$_POST['skin'];
	$langfilter		= &$_POST['langfilter'];
	$name_order		= &$_POST['name_order'];
	$statlog		= &$_POST['statlog'];

	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	if( !bab_isMagicQuotesGpcOn())
		{
		$description = addslashes($description);
		$namev = addslashes($name);
		$adminname = addslashes($adminname);
		$babslogan = addslashes($babslogan);
		}
	else
		{
		$namev = $name;
		$name = stripslashes($name);
		}


	if (empty($_POST['item']))
		{
		// create

		$query = "select * from ".BAB_SITES_TBL." where name='$namev'";	
		$res = $db->db_query($query);
		if( $db->db_num_rows($res) > 0)
			{
			$babBody->msgerror = bab_translate("ERROR: This site already exists");
			return false;
			}

		return siteSave($namev, $description,$babslogan, $lang, $siteemail, $skin, $style, $langfilter, $adminname, $name_order, $statlog);
		}
	
	$query = "select * from ".BAB_SITES_TBL." where name='$namev' AND id<>'".$_POST['item']."'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This site already exists");
		return false;
		}

	list($oldname) = $db->db_fetch_row($db->db_query("select name from ".BAB_SITES_TBL." where id='".$_POST['item']."'"));

	if( $oldname != $name && $GLOBALS['babSiteName'] == $oldname)
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
		}


	$req = "UPDATE ".BAB_SITES_TBL." SET 

			name='".$namev."', 
			description='".$description."', 
			lang='".$lang."', 
			adminemail='".$siteemail."', 
			adminname='".$adminname."', 
			skin='".$skin."', 
			style='".$style."', 
			stat_log='".$statlog."', 
			langfilter='" .$langfilter. "', 
			name_order='".$name_order."', 
			babslogan='".$babslogan."' 
			
		where id='".$_POST['item']."'";

	$db->db_query($req);

	return $_POST['item'];
}


function siteUpdate_menu2()
{
	global $babBody;
	$db = &$GLOBALS['babDB'];

	if( $_POST['mailfunc'] == "smtp" && empty($_POST['smtpserver']))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide server address !!");
		return false;
		}

	if( !empty($_POST['smtppass']) || !empty($_POST['smtppass2']))
		{
		if( $_POST['smtppass'] != $_POST['smtppass2'] )
			{
			$babBody->msgerror = bab_translate("ERROR: Passwords not match !!");
			return false;
			}
		}

	if( empty($_POST['serverport']))
		{
		$_POST['serverport'] = "25";
		}
	
	if( !bab_isMagicQuotesGpcOn())
		{
		$_POST['smtpserver'] = addslashes($_POST['smtpserver']);
		$_POST['smtpuser'] = addslashes($_POST['smtpuser']);
		$_POST['smtppass'] = addslashes($_POST['smtppass']);
		}

	$req = "UPDATE ".BAB_SITES_TBL." SET 
			mailfunc = '".$_POST['mailfunc']."', 
			smtpserver = '".$_POST['smtpserver']."', 
			smtpport = '".$_POST['smtpport']."', 
			smtpuser = '".$_POST['smtpuser']."', 
			smtppassword=ENCODE(\"".$_POST['smtppass']."\",\"".$GLOBALS['BAB_HASH_VAR']."\") 
		where id='".$_POST['item']."'";

	$db->db_query($req);

	return true;
}


function siteUpdate_menu3()
{
	$db = &$GLOBALS['babDB'];

	$req = "UPDATE ".BAB_SITES_TBL." SET 
			change_nickname='".$_POST['change_nickname']."',
			change_password='".$_POST['change_password']."',
			change_lang='".$_POST['change_lang']."', 
			change_skin='".$_POST['change_skin']."', 
			change_date='".$_POST['change_date']."',
			change_unavailability='".$_POST['change_unavailability']."',
			user_workdays='".$_POST['user_workdays']."', 
			remember_login='".$_POST['remember_login']."', 
			email_password='".$_POST['email_password']."' 
		WHERE id='".$_POST['item']."'";

	$db->db_query($req);

	return true;
}


function siteUpdate_menu4()
{
	if( !bab_isMagicQuotesGpcOn())
		{
		$_POST['uploadpath'] = addslashes($_POST['uploadpath']);
		}

	if( !is_numeric($_POST['maxfilesize']) || !is_numeric($_POST['folder_diskspace']) || !is_numeric($_POST['user_diskspace']) || !is_numeric($_POST['total_diskspace']))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide all file manager size limits !!");
		return false;
		}

	if( !is_numeric($_POST['imgsize']))
		$_POST['imgsize'] = 25;

	$db = &$GLOBALS['babDB'];

	$req = "UPDATE ".BAB_SITES_TBL." set imgsize='".$_POST['imgsize']."', uploadpath='".$_POST['uploadpath']."', maxfilesize='".$_POST['maxfilesize']."', folder_diskspace='".$_POST['folder_diskspace']."', user_diskspace='".$_POST['user_diskspace']."', total_diskspace='".$_POST['total_diskspace']."'  where id='".$_POST['item']."'";
	$db->db_query($req);

	return true;
}


function siteUpdate_menu5($item,$datelformat, $datesformat, $timeformat)
	{
	global $babBody;
	if( !bab_isMagicQuotesGpcOn())
		{
		$datelformat = addslashes($datelformat);
		$datesformat = addslashes($datesformat);
		$timeformat = addslashes($timeformat);
		}

	$db = &$GLOBALS['babDB'];

	$req = "update ".BAB_SITES_TBL." set date_longformat='".$datelformat."', date_shortformat='".$datesformat."', time_format='".$timeformat."' where id='".$item."'";
	$db->db_query($req);

	return true;
	}

function siteUpdate_menu6($item)
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
	}

function siteUpdate_authentification($id, $authtype, $host, $hostname, $ldpapchkcnx, $searchdn)
	{
	global $babBody, $bab_ldapAttributes, $nickname, $i_nickname, $crypttype, $ldapfilter,$admindn, $adminpwd1, $adminpwd2;

	$db = $GLOBALS['babDB'];
	if( $authtype != BAB_AUTHENTIFICATION_OVIDENTIA )
		{
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

		if( $authtype == BAB_AUTHENTIFICATION_LDAP )
			{
			if( (!isset($nickname) || empty($nickname)) && (!isset($i_nickname) || empty($i_nickname)))
				{
				$babBody->msgerror = bab_translate("You must provide a nickname");
				return false;
				}

			if( !empty($adminpwd1) || !empty($adminpwd2))
				{
				$adminpwd1 = trim($adminpwd1);
				$adminpwd2 = trim($adminpwd2);
				if( $adminpwd1 != $adminpwd2 )
					{
					$babBody->msgerror = bab_translate("Passwords not match !!");
					return false;
					}
				}
			}

		$ldapattr = empty($nickname) ? $i_nickname: $nickname;

		if( $authtype == BAB_AUTHENTIFICATION_AD )
			{
			$crypttype = '';
			$admindn = '';
			$adminpwd1 = '';
			$adminpwd2 = '';
			}

		$ldapfilter = trim($ldapfilter);
		if( empty($ldapfilter))
			{
			switch($authtype)
				{
				case BAB_AUTHENTIFICATION_AD:
					$ldapfilter = '(|(samaccountname=%NICKNAME))';
					break;
				default:
					$ldapfilter = '(|(%UID=%NICKNAME))';
					break;
				}
			}

		$req = "update ".BAB_SITES_TBL." set authentification='".$authtype."'";
		$req .= ", ldap_host='".$host."', ldap_domainname='".$hostname."', ldap_allowadmincnx='".$ldpapchkcnx."', ldap_searchdn='".$searchdn."', ldap_attribute='".$ldapattr."', ldap_encryptiontype='".$crypttype."', ldap_filter='".$ldapfilter."', ldap_admindn='".$admindn."'";
		if( !empty($adminpwd1))
			{
			$req .= ", ldap_adminpassword=ENCODE(\"".$adminpwd1."\",\"".$GLOBALS['BAB_HASH_VAR']."\")";
			}
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
		}
	else
		{
		$db->db_query("update ".BAB_SITES_TBL." set authentification='".$authtype."' where id='".$id."'");
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=sites&idx=list");
	}

function siteUpdateRegistration($item, $rw, $rq, $ml, $cdp, $cen, $group)
{
	global $babBody, $babDB;

	$babDB->db_query("update ".BAB_SITES_TBL." set registration='".$_POST['register']."',display_disclaimer='".$cdp."', email_confirm='".$cen."', idgroup='".$group."' where id='".$item."'");
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

$idx = isset($_REQUEST['idx']) ? $_REQUEST['idx'] : 'menusite';



if( isset($_GET['action']) && $_GET['action'] == "Yes")
	{
	confirmDeleteSite($_GET['site']);
	}


if (isset($_POST['action']))
{
switch ($_POST['action'])
	{
	case 'menu1':
		$menu1 = siteUpdate_menu1();
		if (!$menu1)
			$idx = 'menu1';
		else 
			$_REQUEST['item'] = $menu1;
		break;

	case 'menu2':
		if (!siteUpdate_menu2())
			$idx = 'menu2';
		break;

	case 'menu3':
		if (!siteUpdate_menu3())
			$idx = 'menu3';
		break;

	case 'menu4':
		if (!siteUpdate_menu4())
			$idx = 'menu4';
		break;

	case 'menu5':

		if(!siteUpdate_menu5($_POST['item'],$_POST['date_longformat'], $_POST['date_shortformat'], $_POST['time_format']))
			$idx = 'menu5';

		break;

	case 'menu6':
		siteUpdate_menu6($_POST['item']);
		break;


	case 'menu8':
		if( !empty($Submit))
			{
			$hostname = isset($_POST['hostname']) ? $_POST['hostname'] : '';
			$ldpapchkcnx = isset($_POST['ldpapchkcnx']) ? $_POST['ldpapchkcnx'] : 'N';
			$searchdn = isset($_POST['searchdn']) ? $_POST['searchdn'] : '';

			if(!siteUpdate_authentification($_POST['item'], $_POST['authtype'], $_POST['host'], $hostname, $ldpapchkcnx, $searchdn))
				$idx = "menu8";
			}
		break;

	case 'menu9':
		$ml = isset($_POST['ml']) ? $_POST['ml'] : array();
		$rw = isset($_POST['rw']) ? $_POST['rw'] : array();
		$req = isset($_POST['req']) ? $_POST['req'] : array();
		$cdp = isset($_POST['cdp']) ? $_POST['cdp'] : 'N';
		$cen = isset($_POST['cen']) ? $_POST['cen'] : 'O';

		siteUpdateRegistration($_POST['item'], $rw, $req, $ml, $cdp, $cen, $_POST['group']);
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=site&idx=menusite&item=".$_POST['item']);
		exit;
		break;

	case 'menu10':
		record_editor_configuration($_POST['item']);
		break;

	case 'menu11':
		switch($babSearchEngine)
			{
			case 'swish':
				include $babInstallPath."admin/sitesearch.swish.php";
				break;
			}

		record_site_menu11($_POST['item']);
		break;

	case 'updisc':
		siteUpdateDisclaimer($_POST['item'], $_POST['content']);
		$popupmessage = bab_translate("Update done");
		break;
	}
}


if( isset($aclman) )
	{
	maclGroups();
	}



$babBody->addItemMenu("List", bab_translate("Sites"),$GLOBALS['babUrlScript']."?tg=sites&idx=list");

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

	case "Delete":
		$babBody->title = getSiteName($_REQUEST['item']);
		sectionDelete($_REQUEST['item']);
		$babBody->addItemMenu("List", bab_translate("Sites"),$GLOBALS['babUrlScript']."?tg=sites&idx=list");
		$babBody->addItemMenu("menusite", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
		$babBody->addItemMenu("Delete", bab_translate("Delete"),$GLOBALS['babUrlScript']."?tg=site&idx=Delete&item=".$_REQUEST['item']);
		break;
	
	case "create":
	case "menu1":
		site_menu1();
		if (empty($_REQUEST['item']))
			{
			
			$babBody->addItemMenu("create", bab_translate("Create"),$GLOBALS['babUrlScript']."?tg=site&idx=create");
			}
		else
			{
			$babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
			$babBody->addItemMenu("menu1", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=site&idx=menu1&item=".$_REQUEST['item']);
			}
		break;

	case "menu2":
		$babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
		$babBody->addItemMenu("menu2", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=site&idx=menu2&item=".$_REQUEST['item']);
		site_menu2($_REQUEST['item']);
		break;

	case "menu3":
		$babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
		$babBody->addItemMenu("menu3", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=site&idx=menu3&item=".$_REQUEST['item']);
		site_menu3($_REQUEST['item']);
		break;

	case "menu4":
		$babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
		$babBody->addItemMenu("menu4", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=site&idx=menu4&item=".$_REQUEST['item']);
		site_menu4($_REQUEST['item']);
		break;

	case "menu5":
		$babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
		$babBody->addItemMenu("menu5", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=site&idx=menu5&item=".$_REQUEST['item']);
		site_menu5($_REQUEST['item']);
		break;

	case "menu6":
		$babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
		$babBody->addItemMenu("menu6", bab_translate("Modify"),$GLOBALS['babUrlScript']."?tg=site&idx=menu6&item=".$_REQUEST['item']);
		site_menu6($_REQUEST['item']);
		break;

	
	case "menu7":
		$babBody->title = bab_translate("Home pages managers").": ".getSiteName($_REQUEST['item']);
		$macl = new macl("site", "menusite", $_REQUEST['item'], "aclman");
        $macl->addtable( BAB_SITES_HPMAN_GROUPS_TBL,bab_translate("Who can manage home pages for this site?"));
		$macl->filter(0,0,1,1,1);
        $macl->babecho();
		$babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
		$babBody->addItemMenu("menu7", bab_translate("Managers"),$GLOBALS['babUrlScript']."?tg=site&idx=hman&item=".$_REQUEST['item']);
		break;

	case "menu8":
		$babBody->title = bab_translate("Authentification").": ".getSiteName($_REQUEST['item']);
		siteAuthentification($_REQUEST['item']);
		$babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
		$babBody->addItemMenu("menu8", bab_translate("Authentification"),$GLOBALS['babUrlScript']."?tg=site&idx=auth&item=".$_REQUEST['item']);
		break;

	case "menu9":
		$babBody->title = bab_translate("Registration").": ".getSiteName($_REQUEST['item']);
		include_once $babInstallPath."utilit/dirincl.php";
		siteRegistration($_REQUEST['item']);
		$babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
		$babBody->addItemMenu("menu9", bab_translate("Registration"),$GLOBALS['babUrlScript']."?tg=site&idx=cnx&item=".$_REQUEST['item']);
		break;

	case "menu10":
		$babBody->title = bab_translate("Editor").": ".getSiteName($_REQUEST['item']);
		editor_configuration($_REQUEST['item']);
		$babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
		$babBody->addItemMenu("menu10", bab_translate("Editor"),$GLOBALS['babUrlScript']."?tg=site&idx=editor&item=".$_REQUEST['item']);
		break;

	case "menu11":
		$babBody->title = bab_translate("Search engine");
		$babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
		$babBody->addItemMenu("menu11", bab_translate("Search2"),$GLOBALS['babUrlScript']."?tg=site&idx=editor&item=".$_REQUEST['item']);

		switch($babSearchEngine)
			{
			case 'swish':
				include $babInstallPath."admin/sitesearch.swish.php";
				break;
			}

		site_menu11($_REQUEST['item']);

		break;

	default:
	case 'menusite':
		$babBody->addItemMenu("menusite", bab_translate("Menu"),$GLOBALS['babUrlScript']."?tg=site&item=".$_REQUEST['item']);
		$babBody->title = getSiteName($_REQUEST['item']);
		siteMenu($_REQUEST['item']);
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>