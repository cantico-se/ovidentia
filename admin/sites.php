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
			$this->db = &$GLOBALS['babDB'];
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
				$this->url = $GLOBALS['babUrlScript']."?tg=site&idx=menusite&item=".$this->arr['id'];
				$this->hmanagementurl = $GLOBALS['babUrlScript']."?tg=site&idx=menu7&item=".$this->arr['id'];
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
			include_once $GLOBALS['babInstallPath'].'utilit/inifileincl.php';
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

			$ini = new bab_inifile();
			$ini->inifile($GLOBALS['babInstallPath'].'version.inc');

			$this->srcversion = "E-".$ini->getVersion();
			if( $this->srcversion != $bab_ov_dbver_prod."-".$bab_ov_dbver_major.".".$bab_ov_dbver_minor.".".$bab_ov_dbver_build ) {
				$this->srcversion .= " [ ".$bab_ov_dbver_prod."-".$bab_ov_dbver_major.".".$bab_ov_dbver_minor.".".$bab_ov_dbver_build." ]";
				}
	
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp,"sites.html", "versions"));
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
			$this->t_file_name = bab_translate("Name of the archive without extension");
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
	
function database()
	{
	global $babBody;
	class temp
		{

		function temp()
			{
			$this->db = &$GLOBALS['babDB'];
			$this->t_submit = bab_translate("Export database");
			
			$this->t_structure = bab_translate("Export table structure");
			$this->t_data = bab_translate("Export table data");
			$this->t_drop_table = bab_translate("Add 'DROP TABLE' instructions");
			$this->t_tables = bab_translate("Tables");
			
			if (isset($_POST) && count($_POST) > 0)
				$this->val = $_POST;
			else
				{
				$el_to_init = array('structure','data','drop_table');
				foreach($el_to_init as $el)
					{
					$this->val[$el] = true;
					}
				}

			$this->restable = $this->db->db_query("SHOW TABLES");
			}


		function getnexttable()
			{
			if (list($this->table) = $this->db->db_fetch_array($this->restable))
				{
				return true;
				}
			else
				return false;
			}
		}

	$temp = new temp();
	$babBody->babecho(	bab_printTemplate($temp, "sites.html", "database"));
	}
	
function unzipcore() 
	{
	global $babBody;
	
	$core = 'ovidentia/';
	$files_to_extract = array();
	ini_set('max_execution_time',1200);

	$tmpdir = $GLOBALS['babUploadPath'].'/tmp/';
	
	if (!is_dir($tmpdir))
		bab_mkdir($tmpdir,$GLOBALS['babMkdirMode']);

	$ul = $_FILES['zipfile']['name'];
	move_uploaded_file($_FILES['zipfile']['tmp_name'],$tmpdir.$ul);
	
	if (isset($_POST['core_name_switch']) && $_POST['core_name_switch'] == 'specify' && !empty($_POST['dir_name']))
		{
		$new_dir = $_POST['dir_name'];
		}
	else
		{
		$new_dir = substr($ul,0,-4);
		}
	
	if (is_file($tmpdir.$ul))
		{
		include_once $GLOBALS['babInstallPath']."utilit/zip.lib.php";
		$zip = new Zip;
		$zipcontents = $zip->get_List($tmpdir.$ul);
		if (count($zipcontents) > 0)
			{
			if (is_dir($new_dir))
				{
				unlink($tmpdir.$ul);
				$babBody->msgerror = bab_translate("Directory allready exists");
				return false;
				}

			if (!bab_mkdir($new_dir,$GLOBALS['babMkdirMode']))
				{
				$babBody->msgerror = bab_translate("Can't create directory: ").$new_dir;
				return false;
				}

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
				$zip->Extract($tmpdir.$ul,$value,$key,false);
				}
			
			unlink($tmpdir.$ul);
			
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
	if(!siteSave($name, $description, $lang, $siteemail, $skin, $style, $register, $statlog, $mailfunc, $server, $serverport, $imgsize, $smtpuser, $smtppass, $smtppass2, $babLangFilter->convertFilterToInt($langfilter),$total_diskspace, $user_diskspace, $folder_diskspace, $maxfilesize, $uploadpath, $babslogan, $remember_login, $email_password, $change_password, $change_nickname, $name_order, $adminname, $user_workdays))
		$idx = "create";
	}
	
if (isset($_FILES['zipfile']))
	if (unzipcore())
		$idx = "list";
		
if (isset($_POST['action']))
	switch($_POST['action'])
		{
		case 'export_database':

			include_once $GLOBALS['babInstallPath']."utilit/sqlincl.php";

			if (count($_POST['tables']) > 0)
				{
				$structure = !empty($_POST['structure']) ? 1 : 0;
				$drop_table = !empty($_POST['drop_table']) ? 1 : 0;
				$data = !empty($_POST['data']) ? 1 : 0;

				$bab_sqlExport = & new bab_sqlExport($_POST['tables'], $structure, $drop_table, $data);
				$bab_sqlExport->exportFile();
				}
			break;
		}

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
		$babBody->addItemMenu("database", bab_translate("Database"),$GLOBALS['babUrlScript']."?tg=sites&idx=database");
		$babBody->title = bab_translate("Upgrade");
		if (!function_exists('gzopen'))
			$babBody->msgerror = bab_translate("Zlib php module missing");
		zipupgrade();
		break;
		
	case 'database':
		$babBody->addItemMenu("list", bab_translate("Sites"),$GLOBALS['babUrlScript']."?tg=sites&idx=list");
		$babBody->addItemMenu("zipupgrade", bab_translate("Upgrade"),$GLOBALS['babUrlScript']."?tg=sites&idx=zipupgrade");
		$babBody->addItemMenu("database", bab_translate("Database"),$GLOBALS['babUrlScript']."?tg=sites&idx=database");
		$babBody->title = bab_translate("Database management");
		database();
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

		$babBody->addItemMenu("create", bab_translate("Create"),$GLOBALS['babUrlScript']."?tg=site&idx=create");
		$babBody->addItemMenu("version", bab_translate("Versions"),$GLOBALS['babUrlScript']."?tg=sites&idx=version");
		$babBody->addItemMenu("zipupgrade", bab_translate("Upgrade"),$GLOBALS['babUrlScript']."?tg=sites&idx=zipupgrade");
		$babBody->addItemMenu("database", bab_translate("Database"),$GLOBALS['babUrlScript']."?tg=sites&idx=database");
		break;
	}

$babBody->setCurrentItemMenu($idx);


?>