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

function sitesList()
	{
	global $babBody;
	class temp
		{
		var $name;
		var $urlname;
		var $url;
		var $description;
		var $lang;
		var $email;
		var $homepages;
		var $hprivate;
		var $hpublic;
		var $hprivurl;
		var $hpuburl;
		
		var $id;
		var $arr = array();
		var $db;
		var $count;
		var $res;

		function temp()
			{
			$this->name = bab_translate("Site name");
			$this->description = bab_translate("Description");
			$this->lang = bab_translate("Lang");
			$this->email = bab_translate("Email");
			$this->homepages = bab_translate("Home pages");
			$this->hmanagement = bab_translate("Managers");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_SITES_TBL."";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=site&idx=modify&item=".$this->arr['id'];
				$this->hmanagementurl = $GLOBALS['babUrlScript']."?tg=site&idx=hman&item=".$this->arr['id'];
				$this->urlname = $this->arr['name'];
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "sites.html", "siteslist"));
	return $temp->count;
	}


function siteCreate($name, $description, $siteemail, $server, $serverport, $smtpuser, $adminname)
	{
	global $babBody;
	class temp
		{
		var $name;
		var $description;
		var $nameval;
		var $descriptionval;
		var $lang;
		var $langval;
		var $siteemail;
		var $siteemailval;
		var $create;
        var $arrfiles = array();

        var $count;

        var $arrskins = array();
		var $skin;

        var $scount;
        var $siteval;

		var $registration;
		var $yes;
		var $no;

		var $mailfunction;
		var $disabled;
		var $smtp;
		var $sendmail;
		var $mail;
		var $server;
		var $serverval;
		var $serverport;
		var $serverportval;

		var $db;

		var $smtpuser;
		var $smtppass;
		var $smtppass2;

		var $langfiltertxt;
		var $langfilterval;
		var $langfiltersite;
		var $langfilterselected;

		var $adminnametxt;
		var $adminnameval;

		function temp($name, $description, $siteemail, $server, $serverport, $smtpuser, $adminname)
			{

			$this->name = bab_translate("Site name");
			$this->description = bab_translate("Description");
			$this->lang = bab_translate("Lang");
			$this->skin = bab_translate("Skin");
			$this->siteemail = bab_translate("Email site");
			$this->create = bab_translate("Create");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->disabled = bab_translate("Disabled");
			$this->mailfunction = bab_translate("Mail function");
			$this->server = bab_translate("Smtp server");
			$this->serverport = bab_translate("Server port");
			$this->imagessize = bab_translate("Max image size ( Kb )");
			$this->none = bab_translate("None");
			$this->smtp = "smtp";
			$this->sendmail = "sendmail";
			$this->mail = "mail";
			$this->registration = bab_translate("Activate Registration")."?";
			$this->smtpuser = bab_translate("SMTP username");
			$this->smtppass = bab_translate("SMTP password");
			$this->smtppass2 = bab_translate("Re-type SMTP password");
			$this->adminnametxt = bab_translate("Name to use for notification emails");
			$this->t_mb = bab_translate("Mb");

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


			$this->dbvalue = $GLOBALS['HTTP_POST_VARS'];

			if (!isset($this->dbvalue['uploadpath'])) $this->dbvalue['uploadpath'] = $GLOBALS['babUploadPath'];
			if (!isset($this->dbvalue['babslogan'])) $this->dbvalue['babslogan'] = $GLOBALS['babSlogan'];
			if (!isset($this->dbvalue['remember_login'])) 
				{
				if ($GLOBALS['babCookieIdent']) $this->dbvalue['remember_login'] = "Y";
				else $this->dbvalue['remember_login'] = "N";
				}
			if (!isset($this->dbvalue['email_password'])) 
				{
				if ($GLOBALS['babEmailPassword']) $this->dbvalue['email_password'] = "Y";
				else $this->dbvalue['email_password'] = "N";
				}
			if (!isset($this->dbvalue['change_password'])) $this->dbvalue['change_password'] = "Y";
			if (!isset($this->dbvalue['change_nickname'])) $this->dbvalue['change_nickname'] = "Y";
			if (!isset($this->dbvalue['name_order'])) $this->dbvalue['name_order'] = "F L";
			
			if (empty($this->dbvalue['total_diskspace'])) 
				{
				if ($GLOBALS['babMaxTotalSize'] > 0) $this->dbvalue['total_diskspace'] = round($GLOBALS['babMaxTotalSize']/1048576);
				else $this->dbvalue['total_diskspace'] = "200";
				}
			if (empty($this->dbvalue['user_diskspace']))
				{
				if ($GLOBALS['babMaxUserSize'] > 0) $this->dbvalue['user_diskspace'] = round($GLOBALS['babMaxUserSize']/1048576);
				else $this->dbvalue['user_diskspace'] = "50";
				}
			if (empty($this->dbvalue['folder_diskspace']))
				{
				if ($GLOBALS['babMaxGroupSize'] > 0) $this->dbvalue['folder_diskspace'] = round($GLOBALS['babMaxGroupSize']/1048576);
				else $this->dbvalue['folder_diskspace'] = "100";
				}
			if (empty($this->dbvalue['maxfilesize']))
				{
				if ($GLOBALS['babMaxFileSize'] > 0) $this->dbvalue['maxfilesize'] = round($GLOBALS['babMaxFileSize']/1048576);
				else $this->dbvalue['maxfilesize'] = "50";
				}

			$this->nameval = $name == ""? $GLOBALS['babSiteName']: $name;
			$this->descriptionval = $description == ""? "": $description;
			$this->langval = !isset($_POST['lang']) ? $GLOBALS['babLanguage']: $_POST['lang'];
			$this->siteemailval = $siteemail == ""? $GLOBALS['babAdminEmail']: $siteemail;
			$this->adminnameval = $adminname == ""? $GLOBALS['babAdminName']: $adminname;
			$this->serverval = $server == ""? "": $server;
			$this->serverportval = $serverport == ""? "25": $serverport;
			$this->smtpuserval = $smtpuser == ""? "": $smtpuser;
			$this->langfiltertxt = bab_translate("Language filter");
			$this->langfiltersite = $GLOBALS['babLangFilter']->getFilterAsInt();

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
				if( $this->langval == $GLOBALS['babLanguage'])
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

		} //class temp

	$temp = new temp($name, $description, $siteemail, $server, $serverport, $smtpuser, $adminname);
	$babBody->babecho(	bab_printTemplate($temp,"sites.html", "sitecreate"));
	$babBody->babecho(	bab_printTemplate($temp,"sites.html", "skinscripts"));
	}


function viewVersion()
	{
	global $babBody;
	class temp
		{
		var $urlphpinfo;
		var $phpinfo;
		var $srcversiontxt;
		var $baseversiontxt;
		var $srcversion;
		var $baseversion;
		var $phpversiontxt;
		var $phpversion;

		function temp()
			{
			include_once $GLOBALS['babInstallPath']."version.inc";
			$this->srcversiontxt = bab_translate("Ovidentia version");
			$this->phpversiontxt = bab_translate("Php version");
			$this->phpversion = phpversion();
			$this->baseversiontxt = bab_translate("Database server version");
			$db = $GLOBALS['babDB'];
			$arr = $db->db_fetch_array($db->db_query("show variables like 'version'"));
			$this->baseversion = $arr['Value'];
			$this->urlphpinfo = $GLOBALS['babUrlScript']."?tg=sites&idx=phpinfo";
			$this->phpinfo = "phpinfo";
			$this->currentyear = date("Y");
			$res = $db->db_query("select * from ".BAB_INI_TBL."");
			while( $arr = $db->db_fetch_array($res))
				{
				switch($arr['foption'])
					{
					case 'ver_major':
						$bab_ov_dbver_major = $arr['fvalue'];
						break;
					case 'ver_minor':
						$bab_ov_dbver_minor = $arr['fvalue'];
						break;
					case 'ver_build':
						$bab_ov_dbver_build = $arr['fvalue'];
						break;
					case 'ver_prod':
						$bab_ov_dbver_prod = $arr['fvalue'];
						break;
					}
				}
			$this->srcversion = $bab_ver_prod."-".$bab_ver_major.".".$bab_ver_minor.".".$bab_ver_build;
			if( $this->srcversion != $bab_ov_dbver_prod."-".$bab_ov_dbver_major.".".$bab_ov_dbver_minor.".".$bab_ov_dbver_build )
				$this->srcversion .= $bab_ver_info. " [ ".$bab_ov_dbver_prod."-".$bab_ov_dbver_major.".".$bab_ov_dbver_minor.".".$bab_ov_dbver_build." ]";
			else
				$this->srcversion .= $bab_ver_info;
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"sites.html", "versions"));
	}

function siteSave($name, $description, $lang, $siteemail, $skin, $style, $register, $mailfunc, $server, $serverport, $imgsize, $smtpuser, $smtppass, $smtppass2, $langfilter,$total_diskspace, $user_diskspace, $folder_diskspace, $maxfilesize, $uploadpath, $babslogan, $remember_login, $email_password, $change_password, $change_nickname, $name_order, $adminname)
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
		$name = addslashes($name);
		$babslogan = addslashes($babslogan);
		$uploadpath = addslashes($uploadpath);
		$adminname = addslashes($adminname);
		}

	if( !is_numeric($total_diskspace) || !is_numeric($user_diskspace) || !is_numeric($folder_diskspace) || !is_numeric($maxfilesize))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide all file manager size limits !!");
		return false;
		}

	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_SITES_TBL." where name='$name'";	
	$res = $db->db_query($query);
	if( $db->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This site already exists");
		return false;
		}
	else
		{
		if( !is_numeric($imgsize))
			{
			$imgsize = 50;
			}
		$query = "insert into ".BAB_SITES_TBL." (name, description, lang, adminemail, adminname, skin, style, registration, mailfunc, smtpserver, smtpport, imgsize, smtpuser, smtppassword, langfilter,total_diskspace, user_diskspace, folder_diskspace, maxfilesize, uploadpath, babslogan, remember_login, change_password, change_nickname, name_order) VALUES ('" .$name. "', '" . $description. "', '" . $lang. "', '" . $siteemail. "', '" . $adminname. "', '" . $skin. "', '" . $style. "', '" . $register. "', '" . $mailfunc. "', '" . $server. "', '" . $serverport. "', '" . $imgsize. "', '". $smtpuser. "', ENCODE(\"".$smtppass."\",\"".$GLOBALS['BAB_HASH_VAR']."\"),\"".$langfilter."\",'". $total_diskspace ."','". $user_diskspace ."','". $folder_diskspace."','".$maxfilesize."', '".$uploadpath."','". $babslogan."','". $remember_login."', '".$change_password."','". $change_nickname."','". $name_order."')";
		$db->db_query($query);
		$idsite = $db->db_insert_id();
		$db->db_query("insert into ".BAB_SITES_DISCLAIMERS_TBL." (id_site, disclaimer_text) values ('".$idsite."','')");

		$resf = $db->db_query("select * from ".BAB_DBDIR_FIELDSEXTRAEXTRA_TBL." where id_directory='0'");
		while( $row = $db->db_fetch_array($resf))
			{
			$db->db_query("insert into ".BAB_LDAP_SITES_FIELDS_TBL." (id_field, x_name, id_site) values ('".$row['name']."','','".$idsite."')");
			$db->db_query("insert into ".BAB_SITES_FIELDS_REGISTRATION_TBL." (id_site, id_field, registration, required, multilignes) values ('".$idsite."', '".$row['id_field']."','N','N', 'N')");
			}

		$db->db_query("update ".BAB_SITES_FIELDS_REGISTRATION_TBL." set registration='Y', required='Y' where id_site='".$idsite."' and id_field IN ('2', '4', '6')");	
		$db->db_query("update ".BAB_SITES_FIELDS_REGISTRATION_TBL." set registration='Y' where id_site='".$idsite."' and id_field='3'");	
		}
	return true;
	}
	
function zipupgrade()
	{
	global $babBody;
	class temp
		{

		function temp()
			{
			$this->t_file = bab_translate("File");
			$this->t_new_core_name = bab_translate("New core name");
			$this->t_submit = bab_translate("Submit");
			$this->t_file_name = bab_translate("Name of the archive without extention");
			$this->t_upgrade = bab_translate("Upgrade");
			$this->t_copy_addons = bab_translate("Copy addons");
			
			if (isset($_POST)) $this->val = $_POST;
			
			$el_to_init = array('dir_name');
			foreach($el_to_init as $value)
				$this->val[$value] = isset($this->val[$value]) ? $this->val[$value] : '';
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "sites.html", "zipupgrade"));
	}
	
	
function unzipcore() 
	{
	global $babBody;
	
	$core = 'ovidentia/';
	$files_to_extract = array();
	ini_set('max_execution_time',120);
	
	if (!is_dir($GLOBALS['babUploadPath'].'tmp/'))
		bab_mkdir($GLOBALS['babUploadPath'].'tmp/',$GLOBALS['babMkdirMode']);

	$ul = $_FILES['zipfile']['name'];
	move_uploaded_file($_FILES['zipfile']['tmp_name'],$GLOBALS['babUploadPath'].'tmp/'.$ul);
	
	if (isset($_POST['core_name_switch']) && $_POST['core_name_switch'] == 'specify' && !empty($_POST['dir_name']))
		{
		$new_dir = $_POST['dir_name'];
		}
	else
		{
		$new_dir = substr($ul,0,-4);
		}
	
	if (is_file($GLOBALS['babUploadPath'].'tmp/'.$ul))
		{
		include_once $GLOBALS['babInstallPath']."utilit/zip.lib.php";
		$zip = new Zip;
		$zipcontents = $zip->get_List($GLOBALS['babUploadPath'].'tmp/'.$ul);
		if (count($zipcontents) > 0)
			{
			if (is_dir($new_dir))
				{
				unlink($GLOBALS['babUploadPath'].'tmp/'.$ul);
				$babBody->msgerror = bab_translate("Directory allready exists");
				return false;
				}

			bab_mkdir($new_dir,$GLOBALS['babMkdirMode']);
			foreach ($zipcontents as $key => $value)
				{
				if (substr($value['filename'],0,strlen($core)) == $core)
					{
					$subdir = substr($value['filename'],strlen($core));
					$where = isset($subdir) && $subdir != '.' ? $new_dir.'/'.$subdir : $new_dir;
					if ($value['size'] == 0) // directory
						{
						if (!is_dir($where))
							bab_mkdir($where,$GLOBALS['babMkdirMode']);
						}
					else // file
						{
						$files_to_extract[$value['index']] = dirname($where);
						}
					}
				}
			
			foreach ($files_to_extract as $key => $value)
				{
				$zip->Extract($GLOBALS['babUploadPath'].'tmp/'.$ul,$value,$key,false);
				}
			
			unlink($GLOBALS['babUploadPath'].'tmp/'.$ul);
			
			include_once $GLOBALS['babInstallPath'].'utilit/upgradeincl.php';
			if (isset($_POST['copy_addons']))
				{
				bab_cpaddons($GLOBALS['babInstallPath'],$new_dir);
				}
				
			if (isset($_POST['upgrade']))
				{
				if (bab_writeConfig(array('babInstallPath' => $new_dir.'/')))
					{
					header('location:'.$GLOBALS['babUrlScript'].'?tg=version&idx=upgrade');
					}
				}
			}
		else
			{
			$babBody->msgerror = bab_translate("Zipfile reading error");
			return false;
			}
		}
	else
		{
		$babBody->msgerror = bab_translate("Upload error");
		return false;
		}
	return true;
	}



/* main */
if( !isset($BAB_SESS_LOGGED) || empty($BAB_SESS_LOGGED) ||  !$babBody->isSuperAdmin)
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( isset($create))
	{
	if(!siteSave($name, $description, $lang, $siteemail, $skin, $style, $register, $mailfunc, $server, $serverport, $imgsize, $smtpuser, $smtppass, $smtppass2, $babLangFilter->convertFilterToInt($langfilter),$total_diskspace, $user_diskspace, $folder_diskspace, $maxfilesize, $uploadpath, $babslogan, $remember_login, $email_password, $change_password, $change_nickname, $name_order, $adminname))
		$idx = "create";
	}
	
if (isset($_FILES['zipfile']))
	if (unzipcore())
		$idx = "list";

if( !isset($idx))
	$idx = "list";


switch($idx)
	{
	case "phpinfo":
		phpinfo();
		exit;
		break;

	case "version":
		$babBody->title = bab_translate("Ovidentia info");
		viewVersion();
		$babBody->addItemMenu("list", bab_translate("Sites"),$GLOBALS['babUrlScript']."?tg=sites&idx=list");
		$babBody->addItemMenu("version", bab_translate("Versions"),$GLOBALS['babUrlScript']."?tg=sites&idx=version");
		break;
		
	case 'zipupgrade':
		$babBody->addItemMenu("list", bab_translate("Sites"),$GLOBALS['babUrlScript']."?tg=sites&idx=list");
		$babBody->addItemMenu("zipupgrade", bab_translate("Upgrade"),$GLOBALS['babUrlScript']."?tg=sites&idx=zipupgrade");
		$babBody->title = bab_translate("Upgrade");
		if (!function_exists('gzopen'))
			$babBody->msgerror = bab_translate("Zlib php module missing");
		zipupgrade();
		break;

	case "create":
		$babBody->title = bab_translate("Create site");
		if (!isset($name)) $name='';
		if (!isset($description)) $description='';
		if (!isset($siteemail)) $siteemail='';
		if (!isset($server)) $server='';
		if (!isset($serverport)) $serverport='';
		if (!isset($smtpuser)) $smtpuser='';
		if (!isset($smtppass)) $smtppass='';
		if (!isset($adminname)) $adminname='';
		siteCreate($name, $description, $siteemail, $server, $serverport, $smtpuser, $smtppass, $adminname);
		$babBody->addItemMenu("list", bab_translate("Sites"),$GLOBALS['babUrlScript']."?tg=sites&idx=list");
		$babBody->addItemMenu("create", bab_translate("Create"),$GLOBALS['babUrlScript']."?tg=sites&idx=create");
		$babBody->addItemMenu("version", bab_translate("Versions"),$GLOBALS['babUrlScript']."?tg=sites&idx=version");
		break;
	case "list":
	default:
		$babBody->title = bab_translate("Sites list");
		if( sitesList() > 0 )
			{
			$babBody->addItemMenu("list", bab_translate("Sites"),$GLOBALS['babUrlScript']."?tg=sites&idx=list");
			}
		else
			$babBody->title = bab_translate("There is no site");

		$babBody->addItemMenu("create", bab_translate("Create"),$GLOBALS['babUrlScript']."?tg=sites&idx=create");
		$babBody->addItemMenu("version", bab_translate("Versions"),$GLOBALS['babUrlScript']."?tg=sites&idx=version");
		$babBody->addItemMenu("zipupgrade", bab_translate("Upgrade"),$GLOBALS['babUrlScript']."?tg=sites&idx=zipupgrade");
		break;
	}

$babBody->setCurrentItemMenu($idx);


?>