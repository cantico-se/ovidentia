<?php
//version 2006-08-07 by LRo
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

define('BABINSTALL','install/babinstall.sql');
define('VERSION_FILE','version.inc');
define('CONFIG','config.php');
define('RENAMEFILE','install.old');
define('LANG','en');

class translate
	{
	function translate($lang)
		{
		$this->str = $this->$lang();
		}
		
	function en()
		{
		return array();
		}
		
	function fr()
		{
		return array();
		}
		
	function str($call)
		{
		return isset($this->str[$call]) ? $this->str[$call] : $call;
		}
	}
	
	
class messageStore
	{
	private $prefix = null;
	public $arr = array();
		
		
	public function __construct($label) 
		{
		$this->prefix = $label;
		}
		
	function add($str) 
		{
			global $install;
			
			$this->arr[] = $str;
			
			if ($install->isShell()) {
				echo $this->prefix." : ".$str."\n";
			}
		}
	}
	
	
class bab_dumpToDb
	{
	function bab_dumpToDb()
		{
		$this->error = &$GLOBALS['error'];
		$this->succes = &$GLOBALS['succes'];
		$this->trans = &$GLOBALS['trans'];
		}
		
	function db_connect()
		{
		global $install;
			
		$this->db = mysql_connect($install->babDBHost, $install->babDBLogin, $install->babDBPasswd);
		if( $this->db )
			{
			$this->succes->add($this->trans->str('Connexion test to mysql server successful'));
			}
		else
			{
			$this->error->add($this->trans->str('Wrong database connexion parameters'));
			return false;
			}

		return true;
		}

	function getDatabase()
	{
		global $install;
		
		$sDBCharset = $install->babDBCharset;
		
		$oResult = mysql_select_db($install->babDBName, $this->db);
		if($oResult === true)
		{
			if(!empty($install->clearDb))
			{
				if(!$this->db_queryWem('DROP DATABASE `'.$install->babDBName.'`'))
				{
					$this->error->add($this->trans->str('Can\'t drop the database : ').$install->babDBName.$this->trans->str(' you must delete it manually'));
					return false;
				}
				else
				{
					$this->succes->add($this->trans->str('Database deleted : ').$install->babDBName);
					$createdatabase = true;
				}
			}
			else
			{
				$oResult = $this->db_queryWem("SHOW VARIABLES LIKE 'character_set_database'");
				if(false === $oResult)
				{
					$this->error->add($this->trans->str('Can\'t get the database charset of : ') . $install->babDBName);
					return false;
				}
				
				$aDbCharset = $this->db_fetch_assoc($oResult);
				if(false === $aDbCharset)
				{
					$this->error->add($this->trans->str('Can\'t fetch the database charset of : ') . $install->babDBName);
					return false;
				}
				
				$sDbCharsetToCompare = (mb_strtolower($sDBCharset) == 'utf8') ? 'utf8' : 'latin1';
				
				if(mb_strtolower($aDbCharset['Value']) !== $sDbCharsetToCompare)
				{
					$this->error->add($this->trans->str('Incompatible database charset, you have selected : ') . 
						$sDBCharset . $this->trans->str(' and the charset of the database is : ') . 
						$aDbCharset['Value']);
					return false;
				}
			}
		}

			
		if(!$oResult || (isset($createdatabase) && $createdatabase === true))
		{
			$sQuery = '';
			if('utf8' === $sDBCharset)
			{
				$sQuery = 'CREATE DATABASE `' . $install->babDBName . '` ' .
					'CHARACTER SET utf8 ' .
					'DEFAULT CHARACTER SET utf8 ' .
					'COLLATE utf8_general_ci ' .
					'DEFAULT COLLATE utf8_general_ci ';
			}
			else
			{
				$sQuery = 'CREATE DATABASE `' . $install->babDBName . '` ' .
					'CHARACTER SET latin1 ' .
					'DEFAULT CHARACTER SET latin1 ' .
					'COLLATE latin1_swedish_ci ' .
					'DEFAULT COLLATE latin1_swedish_ci ';
			}
			
			
			if(!$this->db_queryWem($sQuery))
			{
				$this->error->add($this->trans->str('Can\'t create the database : ').$install->babDBName.$this->trans->str(' you must create it manually'));
				return false;
			}
			else
			{
				$this->succes->add($this->trans->str('Database created : ').$install->babDBName);
				mysql_select_db($install->babDBName, $this->db);
			}
		}
		return true;
	}

	function db_queryWem($query) 
		{
		return mysql_query($query, $this->db);
		}
		
	function db_fetch_array($result)
    	{
		return mysql_fetch_array($result);
		}
		
	function db_fetch_assoc($result)
    	{
		return mysql_fetch_assoc($result);
		}
		
	function quote($param)
		{
		if (is_array($param)) {

				$keys = array_keys($param); 
			
				foreach($keys as $key) {
					$param[$key] = mysql_real_escape_string($param[$key]);
				}

				return "'".implode("','",$param)."'";
			} else {
				return "'".mysql_real_escape_string($param)."'";
			}
		}

		
	function getFileContent()
		{
		$this->fileContent = '';
		$f = fopen(BABINSTALL,'r');
		if ($f === false)
			{
			$this->error->add($this->trans->str('There is an error into configuration, can\'t read sql dump file'));
			return false;
			}
		while (!feof($f)) 
			{
			$this->fileContent .= fread($f, 1024);
			}
		return true;
		}
		
	function workOnQuery()
		{
		$reg = "/(?:INSERT|CREATE)(?:[^';]*'(?:[^']|\\\\'|'')*')*[^';]*;/";
		if (preg_match_all($reg, $this->fileContent, $m))
			{
			$this->succes->add(count($m[0]).' '.$this->trans->str('query founded into dump file'));
			for ($k = 0; $k < count($m[0]); $k++ )
				{
				$query = $m[0][$k];
				if (!$this->db_queryWem($query))
					{
					$this->error->add($this->trans->str('There is an error into sql dump file at query : ')."\n\n".$query."\n".mysql_error());
					return false;
					}
				
				}
				
			$this->succes->add($this->trans->str('Database initialisation done'));
			}
		else
			{
			$this->error->add($this->trans->str('ERROR : can\'t fetch file content'));
			}
			
		return true;
		}

		
	function dbConfig()
		{
		global $install;
			
		if (!empty($install->babUploadPath))
			{
			$this->db_queryWem("UPDATE bab_sites SET uploadpath='".mysql_real_escape_string($install->babUploadPath)."' WHERE id='1'");
			}
		return true;
		}
	}
	
	
function writeConfig()
	{
	global $error,$succes,$trans, $install;
	
	function replace($txt, $var, $value)
		{
		preg_match('/'.preg_quote($var, '/')."\s*=\s*\"([^\"]*)\"/", $txt, $match);
		if ($match[1] != $value)
			{
			$out = preg_replace('/'.preg_quote($var, '/')."\s*=\s*\"".preg_quote($match[1],"/")."\"/", $var." = \"".$value."\"", $txt);
			if ($out != $txt)
				return $out;
			else
				return false;
			}
		else
			return $txt;
		}
		
	$file = @fopen(CONFIG, "r");
	if (!$file)
		{
		$error->add($trans->str('Failed to read config file'));
		return false;
		}
	$txt = fread($file, filesize(CONFIG));
	fclose($file);
	
	$config = array('babDBHost','babDBLogin','babDBPasswd','babDBName','babInstallPath'); 		// ,'babUrl'
	
	foreach ($config as $var)
		{
		$out = replace($txt, $var, $install->$var);
		if (!$out)
			{
			$error->add($trans->str('Config change failed on ').$var);
			return false;
			}
		else
			$txt = $out;
		}
	
		
	$succes->add($trans->str('config.php update successful'));
	
	$file = fopen(CONFIG, "w");
	if (!$file)
		{
		$error->add($trans->str('Failed to write into config file'));
		return false;
		}
	fputs($file, $out);
	fclose($file);
	return true;
	}
	
function renameFile()
	{
	if (!defined('RENAMEFILE'))
		{
		return true;
		}

	global $error,$succes,$trans;
	if (rename(basename($_SERVER['PHP_SELF']),RENAMEFILE))
		{
		$succes->add($trans->str('the file').' '.basename($_SERVER['PHP_SELF']).' '. $trans->str('has been renamed to').' '.RENAMEFILE);
		return true;
		}
	else
		{
		$error->add($trans->str('Failed to rename the file').' '.basename($_SERVER['PHP_SELF']).' '.$trans->str('You must remove it for security reasons'));
		return true; // return true because of a non-blocker error
		}
		
	return false;
	}
	

function _createFmDirectories()
{
	global $error, $succes, $trans, $install;
	
	$sUploadPath = $install->babUploadPath;
	$sLastChar = (string) mb_substr($sUploadPath, 0, -1);
	
	if('\\' !== $sLastChar && '/' !== $sLastChar)
	{
		$sUploadPath .= '/';
	}
	
	$sFmUploadPath			= $sUploadPath . 'fileManager';
	$sCollectiveUploadPath	= $sFmUploadPath . '/collectives';
	$sUserUploadPath		= $sFmUploadPath . '/users';
	
	if(!is_writable($sUploadPath))
	{
		$error->add(sprintf($trans->str(' The directory: %s is not writable'), $sUploadPath));
		return false;
	}
	
	$bCollDirCreated = false;
	$bUserDirCreated = false;
	
	if(!is_dir($sFmUploadPath))
	{
		if(!@mkdir($sFmUploadPath, 0777))
		{
			$error->add(sprintf($trans->str(' The directory: %s have not been created'), $sFmUploadPath));
			return false;
		}
	}

	if(!is_dir($sCollectiveUploadPath))
	{
		$bCollDirCreated = @mkdir($sCollectiveUploadPath, 0777);
		if(false === $bCollDirCreated)
		{
			$error->add(sprintf($trans->str(' The directory: %s have not been created'), $sCollectiveUploadPath));
			return false;
		}
	}
	else 
	{
		$bCollDirCreated = true;
	}
	
	if(!is_dir($sUserUploadPath))
	{
		$bUserDirCreated = @mkdir($sUserUploadPath, 0777);
		if(false === $bCollDirCreated)
		{
			$error->add(sprintf($trans->str(' The directory: %s have not been created'), $sUserUploadPath));
			return false;
		}
	}
	else 
	{
		$bUserDirCreated = true;
	}
	
	return ($bCollDirCreated && $bUserDirCreated);
}
	

/**
 * Checks whether the path is an absolute path.
 * On Windows something like C:/example/path or C:\example\path or C:/example\path
 * On unix something like /example/path
 * 
 * @return bool
 */
function isAbsolutePath($path)
{
	if (DIRECTORY_SEPARATOR === '\\') {
		$path = str_replace(DIRECTORY_SEPARATOR, '/', $path);
		$regexp = '#^[a-zA-Z]\:/#';
	} else {
		$regexp = '#^/#';
	}

	if (0 !== preg_match($regexp, $path)) {
		return true;
	}
	return false;
}





function displayHelp() {
	
	global $install;
	
	$message = "Install ovidentia\n";
	$message .= "php -f install.php -- [--dbhost hostname] [--dbname database] \n";
	$message .= "	[--charset utf8|latin1] [--dbdrop] [--dblogin username] \n";
	$message .= "	[--dbpass password] [--core folder] [--ulpath path] \n\n";

	$message .= "Default values\n";
	
	$message .= "--dbhost  : ".$install->babDBHost."\n";
	$message .= "--dbname  : ".$install->babDBName."\n";
	$message .= "--charset : ".$install->babDBCharset."\n";
	$message .= "--dblogin : ".$install->babDBLogin."\n";
	$message .= "--dbpass  : ".$install->babDBPasswd."\n";
	$message .= "--core    : ".$install->babInstallPath."\n";
	$message .= "--ulpath  : ".$install->babUploadPath."\n";
	
	$message .= "\n";

	print($message);
}






class installInfos {
	
	private $type = null;
	
	public $babDBHost 		= 'localhost';
	public $babDBName 		= 'ovidentia';
	public $babDBCharset 	= 'latin1';
	public $clearDb 		= null;
	public $babDBLogin		= 'root';
	public $babDBPasswd		= '';
	public $babInstallPath	= 'ovidentia/';
	public $babUploadPath	= '/home/upload';
	
	
	private function getFromPost() {
		
		if (get_magic_quotes_gpc()) {
			foreach($_POST as $k=>$v) $_POST["$k"]=stripslashes($v);
		}
		
		
		foreach($this as $property => $defaultvalue) {
			if (isset($_POST[$property])) {
				$this->$property = $_POST[$property];
			}
		}
		
		$this->type = 'web';
	}
	
	
	private function getFromArgs() {
		
		$args = $_SERVER['argv'];
		$next = null;
		unset($args[0]);

		foreach($args as $arg) {

			switch($arg) {

				case '--dbhost':		$next = 'babDBHost';		break;
				case '--dbname':		$next = 'babDBName';		break;
				case '--charset':		$next = 'babDBCharset';		break;
				case '--dbdrop':		$this->clearDb = 'On';		break;
				case '--dblogin':		$next = 'babDBLogin';		break;
				case '--dbpass':		$next = 'babDBPasswd';		break;
				case '--core':			$next = 'babInstallPath';	break;
				case '--ulpath':		$next = 'babUploadPath';	break;
				case '--help':			displayHelp();				break;

				default:
					if (null !== $next) {
						$this->$next = $arg;
						$next = null;
						break;
					}

				
					

			}
		}
	
		
		$this->type = 'shell';
	}
	
	
	
	/**
	 * If install informations are confirmed by the user return true
	 * @return bool
	 */ 
	public function getFromUser() {
		if (isset($_POST) && count($_POST) > 0) {
			$this->getFromPost();
			return true;
			
		} elseif ($this->isShell() && count($_SERVER['argv']) > 1) {
			$this->getFromArgs();
			return true;
		}
		
		return false;
	}
	
	public function isShell() {
		
		if (!isset($_SERVER['HTTP_HOST']) && isset($_SERVER["argv"][1])) {
			return true;
		}
		
		return false;
	}
	
	
	public function testVars()
	{
	global $error,$succes,$trans;
	
	if (!is_file($this->babInstallPath.VERSION_FILE))
		{
		$error->add($trans->str('No access to core. Relative path to ovidentia core is wrong.'));
		return false;
		}

	$GLOBALS['babInstallPath'] = $this->babInstallPath;

	// Checking validity of the selected upload path.
	if (!empty($this->babUploadPath) )
		{
		// The upload path should be an absolute path.
		if (!isAbsolutePath($this->babUploadPath)) {
			$error->add($trans->str('The upload directory path must be an absolute path.'));
			return false;
		}

		if ( !is_dir($this->babUploadPath) && !@mkdir($this->babUploadPath))
			{
			$error->add($trans->str('Can\'t create upload directory'));
			return false;
			}

		@mkdir($this->babUploadPath.'/tmp/');

		if (!_createFmDirectories())
			{
			return false;
			}
		}
		
	if (!empty($this->babUploadPath) && !is_writable($this->babUploadPath))
		{
		$error->add($trans->str('Upload directory is not writable'));
		return false;
		}
		
	$succes->add($trans->str('Configuration test successful'));
	return true;
	}
	
	
	
	/**
	 * @return bab_inifile|false
	 */
	function getIni() {
		
		global $error,$succes,$trans;

		$version_file = $this->babInstallPath.VERSION_FILE;

		if (is_file($version_file)) {

			require_once $this->babInstallPath.'utilit/inifileincl.php';
			
			if (!class_exists('bab_inifile')) {
				$error->add($trans->str('error on file inclusion, bab_inifile is not available'));
				return false;
			}

			$ini = new bab_inifile;
			$ini->inifile($version_file);
			
			return $ini;
		}
		$error->add($trans->str('Can\'t get ini file'));
		return false;
	}
}










	
/* main */




set_time_limit(3600);

$error = new messageStore('ERROR');
$succes = new messageStore('INFO');
$trans = new translate(LANG);
$install = new installInfos;
	
if ($install->getFromUser())
	{
	if ($install->testVars())
		{
		if (false !== $ini = $install->getIni()) {
			$dump = new bab_dumpToDb();
			if ($dump->db_connect())
				{
				if ($ini->isInstallValid($dump, $strerror)) 
					{
					$succes->add($trans->str('Configuration requirements tests successful'));
					
					if ($dump->getDatabase()) 
						{
						if ($dump->getFileContent())
							{
							if ($dump->workOnQuery())
								{
								if ($dump->dbConfig())
									{
									if (writeConfig())
										{
										if (renameFile())
											{
											$succes->add($trans->str('Configuration done'));
											$all_is_ok = true;
											}
										}
									}
								}
							}
						}
					} else {
						$error->add($strerror);
					}
				}
			}
		}
	}
	
	

	
if (!empty($error->arr)) {
	$succes->add($trans->str('Aborted'));
}


if ($install->isShell()) {
	
	if (empty($succes->arr) && empty($error->arr)) {
		
		$error->add($trans->str('One parameter at least must be set by command-line'));
		displayHelp();
	}
	
	die();
}



?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<title>Ovidentia install</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<style type="text/css">

body {
	font-family:verdana;
	}

h1 {
	color:#c00;
	font-family:"Arial black",arial;
	font-size:2em;
	padding:0;
	font-weight:bolder;
	border-bottom:#339 1px solid;
	text-transform:uppercase;
	margin:0;
	}
	
h2 {
	margin:0;
	font-size:.9em;
	font-family:arial;
	text-align:right;
	color:#339;
}

h3 {
	color:#c00;
	font-weight:normal;
	font-size:1em;
	}
	
h4 {
	color:green;
	margin:0;
	}
#form {
	width:60%;
	margin:auto;
	margin-top:1em;
	margin-bottom:2em;
	padding:1em;
	background: ButtonFace; 
	border: 1px solid;
	border-color: ButtonHighlight ButtonShadow ButtonShadow ButtonHighlight;
	text-align:center;
	font-family:times;
	}
	
#form label {
	float:left;
	width:50%;
	white-space:nowrap;
	text-align:right;
	padding-right:.3em;
	font-weight:bold;
	}
	
#form dt {
	margin:.8em 0 .1em 0;
	}
	
#form dd {
	font-style:italic;
	text-align:center;
	margin:0;
	font-size:.9em;
	}
	
fieldset {
	margin-top:1em;
	margin-bottom:1em;
	text-align:left;
	padding:.8em;
	}
	
ul {
	list-style-type: none;
	}
	
a, a:visited {
	color:#000;
	border-bottom:#000 1px solid;
	text-decoration:none;
	}
	
a:hover {
	border-bottom:#00f 1px solid;
	}
</style>
</head>

<body>
	<h1>Ovidentia</h1>
	<h2><?php echo $trans->str('Configuration') ?></h2>
	
	<?php foreach($succes->arr as $msg)
		{
		echo '<h4> - '.nl2br(htmlspecialchars($msg))."</h4>\n";
		} ?>
	<?php if (!empty($error->arr)) {
			foreach($error->arr as $msg) {
				echo '<h3>'.nl2br(htmlspecialchars($msg)).'</h3>';
			}
		}
	 ?>
	
	
	<div id="form">
	<?php
	if (isset($all_is_ok) && $all_is_ok === true)
		{
		
		?>
		<p><?php echo $trans->str('Congratulation, ovidentia is now configured, now you can log in with the default account') ?></p>
		<ul>
			<li><?php echo $trans->str('Login ID') ?> : <strong>admin@admin.bab</strong></li>
			<li><?php echo $trans->str('Password') ?> : <strong>012345678</strong></li>
		</ul>
		<p><a href="index.php?tg=login"><?php echo $trans->str('Go to login page') ?></a></p>
		<?php
		}
	else
		{
		?>
		<p><?php echo $trans->str('Welcome to ovidentia setup') ?></p>
		<form method="post" action="<?php echo basename($_SERVER['PHP_SELF']) ?>">
			<dl>
				<fieldset>
					<legend><?php echo $trans->str('database') ?></legend>
					<dt><label for="babDBHost"><?php echo $trans->str('Database host') ?> :</label><input type="text" id="babDBHost" name="babDBHost" value="<?php echo $install->babDBHost; ?>" /></dt>
					<dt><label for="babDBName"><?php echo $trans->str('Database name') ?> :</label><input type="text" id="babDBName" name="babDBName" value="<?php echo $install->babDBName; ?>" /></dt>
					
					<dt>
						<label for="babDBCharset"><?php echo $trans->str('Charset') ?> :</label>					
						<select id="babDBCharset" name="babDBCharset">
						<?php 					
						$sSelectedCharset = $install->babDBCharset;
						if('utf8' == $sSelectedCharset)
						{
							echo '<option value="utf8" selected="selected">utf8</option><option value="latin1">latin1</option>';
						}
						else
						{
							echo '<option value="utf8">utf8</option><option value="latin1" selected="selected">latin1</option>';
						}
						?>
						</select>					
					</dt>
										
					<dt><label for="babDBName"><?php echo $trans->str('Drop database') ?> :</label><input type="checkbox" id="clearDb" name="clearDb" <?php if($install->clearDb) echo 'checked="checked"'; ?> /></dt>
						<dd><?php echo $trans->str('If the database exists, it will be dropped and data will be lost') ?></dd>
					<dt><label for="babDBLogin"><?php echo $trans->str('Login ID') ?> :</label><input type="text" id="babDBLogin" name="babDBLogin" value="<?php echo $install->babDBLogin; ?>" /></dt>
					<dt><label for="babDBPasswd"><?php echo $trans->str('Password') ?> :</label><input type="password" id="babDBPasswd" name="babDBPasswd" /></dt>
				</fieldset>
				
				<fieldset>
					<legend>Ovidentia</legend>
					<dt><label for="babInstallPath"><?php echo $trans->str('Relative path to ovidentia core') ?> :</label><input type="text" id="babInstallPath" name="babInstallPath" value="<?php echo $install->babInstallPath;?>" /></dt>
					<dt><label for="babUploadPath"><?php echo $trans->str('Upload directory') ?> :</label><input type="text" id="babUploadPath" name="babUploadPath" value="<?php echo $install->babUploadPath;?>" /></dt>
						<dd><?php 
						
							echo $trans->str('Full path to upload the files, ');
						
							if ('/' === DIRECTORY_SEPARATOR) {
								echo $trans->str('example : "/home/upload"');
							} else {
								echo $trans->str('example : "c:\\path-to\\upload-directory\\"');
							}
						
							echo sprintf($trans->str(' (current directory is "%s")'), dirname(__FILE__));
						
						?></dd>
				</fieldset>
			</dl>
			<input type="submit" value="<?php echo $trans->str('Submit') ?>" />
		</form>
		<?php
		}
		?>
	</div>
<a href="http://www.ovidentia.org/">www.ovidentia.org</a>
</body>
</html>

