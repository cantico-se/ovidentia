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
	
	

	
	

class bab_sqlExport
	{
	function bab_sqlExport()
		{
		ini_set('max_execution_time','2400');
		
		$this->opt_structure = !empty($_POST['structure']) ? 1 : 0;
		$this->opt_drop_table = !empty($_POST['drop_table']) ? 1 : 0;
		$this->opt_data = !empty($_POST['data']) ? 1 : 0;
		
		//
		
		$this->search       = array("\x00", "\x0a", "\x0d", "\x1a"); //\x08\\x09, not required
        $this->replace      = array('\0', '\n', '\r', '\Z');
		$this->autoComma = 0;
		$this->commaGroup = array();
		$this->key_index = array();
		$this->dump = '';
		$this->db = &$GLOBALS['babDB'];
		
		$this->commentPush(bab_translate("Ovidentia database dump"));
		
		$this->commentPush("babSiteName : ".$GLOBALS['babSiteName'],true);
		$this->commentPush("babDBName : ".$GLOBALS['babDBName'],true);
		$this->commentPush("babDBLogin : ".$GLOBALS['babDBLogin'],true);
		$this->commentPush("babInstallPath : ".$GLOBALS['babInstallPath'],true);
		$this->commentPush("babUrl : ".$GLOBALS['babUrl'],true);
		
		$version = array();
		$res = $this->db->db_query("SELECT fvalue FROM ".BAB_INI_TBL."");
		while (list($v) = $this->db->db_fetch_array($res))
			{
			$version[] = $v;
			}
			
		$this->commentPush(bab_translate('Ovidentia version')." : ".implode('.',$version),true);
			
		$arr = $this->db->db_fetch_array($this->db->db_query("show variables like 'version'"));
		$this->commentPush(bab_translate('Database server version')." : ".$arr['Value'],true);
		$this->commentPush(bab_translate('Php version')." : ".phpversion(),true);
		$this->commentPush(bab_translate('Date')." : ".bab_longDate(time()),true);

		foreach($_POST['tables'] as $tablename)
			{
			$this->handleTable($tablename);
			}
		}
		
	function sqlAddslashes($str)
		{
		global $babDB;
		return $babDB->db_escape_string($str);
		}
		
	function commentPush($str,$nobr = false)
		{
		if (!$nobr) $this->dump .= "\n";
		$this->dump .= "# ".$str."\n";
		if (!$nobr) $this->dump .= "\n";
		}
		
	function brPush()
		{
		$this->dump .= "\n";
		}
		
	function autoCommaStart()
		{
		$this->autoComma = 1;
		}
		
	function autoCommaEnd()
		{
		$this->autoComma = 0;
		$cnt = count($this->commaGroup);
		for ($i = 0; $i < $cnt ; $i++)
			{
			if ($i < $cnt-1)
				$this->dumpPush($this->commaGroup[$i].',');
			else
				$this->dumpPush($this->commaGroup[$i]);
			}
			
		$this->commaGroup = array();
		}
		
	function dumpPush($str)
		{
		if ($this->autoComma)
			$this->commaGroup[] = $str;
		else
			$this->dump .= $str."\n";
		}
		
	function handleTable(&$name)
		{
		if ($this->opt_structure)
			$this->commentPush(bab_translate('Table structure for table')." `".$name."`");

		if ($this->opt_drop_table)
			$this->dumpPush("DROP TABLE IF EXISTS `".$name."`;");
		if ($this->opt_structure)
			{
			$this->dumpPush("CREATE TABLE `".$name."` (");
			
			$this->autoCommaStart();
			}
			
		$this->table_collumn = array();
		$describe = $this->db->db_query("DESCRIBE ".$name);
		while($collumn = $this->db->db_fetch_array($describe))
			{
			$this->handleCollumn($collumn);
			}
			
		if ($this->opt_structure)
			{
			$key = $this->db->db_query("SHOW KEYS FROM ".$name);
			while($line = $this->db->db_fetch_array($key))
				{
				$this->handleKey($line);
				}
			$this->dumpKeys();
			
			$this->autoCommaEnd();
			
			$this->dumpPush(") TYPE=MyISAM;");
			}
			
		
			
		if ($this->opt_data)
			{
			$this->commentPush(bab_translate('Dumping data for table')." `".$name."`");
			
			$select = $this->db->db_query("SELECT * FROM ".$name);
			while($line = $this->db->db_fetch_array($select))
				{
				$this->handleData($line,$name);
				}
			}
			
		$this->brPush();
		}
		
	function handleCollumn(&$collumn)
		{
		$this->table_collumn[] = $collumn['Field'];
		$this->collumn_type[$collumn['Field']] = $collumn['Type'];
		if ($this->opt_structure)
			{
			$str = $collumn['Field'].' '.$collumn['Type'];
			if (!empty($collumn['Default']) )
				{
				$collumn['Default'] = str_replace('\\','\\\\',$collumn['Default']);
				$collumn['Default'] = str_replace("'","''",$collumn['Default']);
				$str .= ' DEFAULT \''.$collumn['Default'].'\'';
				}
			if ($collumn['Null'] != 'YES')
				$str .= ' NOT NULL';
			if (!empty($collumn['Extra']))
				$str .= ' ' . $collumn['Extra'];
				
			$this->dumpPush($str);
			}
		}
		
	function handleKey(&$row)
		{
		
		$kname    = $row['Key_name'];
		$comment  = (isset($row['Comment'])) ? $row['Comment'] : '';
		$sub_part = (isset($row['Sub_part'])) ? $row['Sub_part'] : '';

		if ($kname != 'PRIMARY' && $row['Non_unique'] == 0) {
			$kname = 'UNIQUE|' . $kname;
			}
		if ($comment == 'FULLTEXT') {
			$kname = 'FULLTEXT|' . $kname;
			}
		if (!isset($index[$kname])) {
			$this->key_index[$kname] = array();
			}
		if ($sub_part > 1) {
			$this->key_index[$kname][] = $row['Column_name'] . '(' . $sub_part . ')';
			} 
		else {
			$this->key_index[$kname][] = $row['Column_name'];
			}
		}
		
	function dumpKeys()
		{
		if (!is_array($this->key_index) || count($this->key_index)  == 0)
			return false;
		reset($this->key_index);

		while (list($x, $columns) = each($this->key_index)) 
			{
			if ($x == 'PRIMARY') {
				$schema_create = 'PRIMARY KEY (';
				} else 
			if (substr($x, 0, 6) == 'UNIQUE') {
				$schema_create = 'UNIQUE ' . substr($x, 7) . ' (';
				} else 
			if (substr($x, 0, 8) == 'FULLTEXT') {
				$schema_create = 'FULLTEXT ' . substr($x, 9) . ' (';
				} 
			else {
				$schema_create = 'KEY ' . $x . ' (';
				}
			$this->dumpPush( $schema_create.implode($columns, ', ') . ')');
			}
		$this->key_index = array();
		}
		
	function getType($str)
		{
		if (substr_count($str,'(') > 0)
			{
			$tmp = explode('(',$str);
			return $tmp[0];
			}
		else
			return $str;
		}
		
	function handleData(&$line,&$table)
		{
		$value = array();
		for ($i = 0 ; $i < count($this->table_collumn) ; $i++ )
			{
			$col = $this->table_collumn[$i];
			
			if (!isset($line[$col]))
				$value[$i] = 'NULL';
			elseif ($line[$col] == 0 || $line[$col] != '')
				{
				switch ($this->getType($this->collumn_type[$col]))
					{
					case 'tinyint':
					case 'smallint':
					case 'mediumint':
					case 'int':
					case 'bigint':
					case 'timestamp':
						$value[$i] = "'".$line[$col]."'";
						break;
					case 'blob':
					case 'mediumblob':
					case 'longblob':
					case 'tinyblob':
						if (!empty($line[$col]))
							$value[$i] = '0x' . bin2hex($line[$col]);
						else
							$value[$i] = "''";
						break;
					default: // string
						$value[$i] = "'".str_replace($this->search, $this->replace, $this->sqlAddslashes($line[$col]))."'";
						break;
					}
				}
			else
				{
				$value[$i] = "''";
				}
			}

		$this->dumpPush( 'INSERT INTO '.$table.' ('.implode(',',$this->table_collumn).') VALUES ('.implode(',',$value).');');
		}
		
	function exportFile()
		{
		header("content-type:text/plain");
		header("Content-Disposition: attachment; filename=".$GLOBALS['babSiteName'].".sql");
		die($this->dump);
		}
		
	function recordTofile($filename)
		{
		return;
		}
		
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

			if (count($_POST['tables']) > 0)
				{
				$bab_sqlExport = & new bab_sqlExport();
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