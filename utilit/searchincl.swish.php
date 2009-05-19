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

/**
 * Search with Swish-e search engine on system
 *
 * @package 	search
 * @subpackage 	swish-e
 */
class swishCls
{
	public $msgerror = false;

	protected $system_charset;
	private $uploadDir;

	public function __construct($object)
	{

	$registry = bab_getRegistryInstance();
	$registry->changeDirectory('/bab/indexfiles/');

	if ('windows' === bab_browserOS()) {
		$default_charset = 'ISO-8859-1';
	} else {
		$default_charset = 'UTF-8';
	}

	$this->system_charset = $registry->getValue('system_charset', $default_charset);
	
	$this->uploadDir = $GLOBALS['babBody']->babsite['uploadpath'];

	if (!is_dir($this->uploadDir.'/tmp/'))
		bab_mkdir($this->uploadDir.'/tmp/');

	if (!is_dir($this->uploadDir.'/SearchIndex/'))
		bab_mkdir($this->uploadDir.'/SearchIndex/');

	$this->uploadDir = str_replace('\\' ,'/', $this->uploadDir);

	$this->tmpCfgFile = $this->uploadDir.'/tmp/'.$object.'swish.config';
	$this->mainIndex = $this->uploadDir.'/SearchIndex/'.$object.'.index';
	$this->mergeIndex = $this->uploadDir.'/SearchIndex/'.$object.'.merge.index';
	$this->tempIndex = $this->uploadDir.'/SearchIndex/'.$object.'.temp.index';
	$this->indexLog = $this->uploadDir.'/SearchIndex/'.$object.'index.log';
	$this->errorLog = $this->uploadDir.'/SearchIndex/error.log';
	$this->batchFile = $this->uploadDir.'/SearchIndex/index.bat';


	$db = &$GLOBALS['babDB'];
	$res = $db->db_query("SELECT * FROM ".BAB_SITES_SWISH_TBL." WHERE id_site='".$GLOBALS['babBody']->babsite['id']."'");
	if ($arr = $db->db_fetch_assoc($res))
		{
		$this->swishCmd = $arr['swishcmd'];		// 'C:/Progra~1/SWISH-E/swish-e';
		$this->pdftotext = $arr['pdftotext'];	// 'C:/Progra~1/SWISH-E/lib/swish-e/pdftotext';
		$this->xls2csv = $arr['xls2csv'];		// 'C:/Progra~1/SWISH-E/lib/swish-e/xls2csv';
		$this->catdoc = $arr['catdoc'];
		$this->unzip = $arr['unzip'];
		}
	
	
	if (empty($this->swishCmd)) {
		if ('windows' === bab_browserOS()) {
			$this->swishCmd = 'C:/Progra~1/SWISH-E/swish-e.exe';
		} else {
			$this->swishCmd = $this->getDefaultCommand('swish-e');
		}
	}
	
	
	if (empty($this->pdftotext)) {
		if ('windows' === bab_browserOS()) {
			$this->pdftotext = 'C:/Progra~1/SWISH-E/lib/swish-e/pdftotext.exe';
		} else {
			$this->pdftotext = $this->getDefaultCommand('pdftotext');
		}
	}
	
	if (empty($this->xls2csv)) {
		if ('windows' === bab_browserOS()) {
			$this->xls2csv = 'C:/Progra~1/SWISH-E/lib/swish-e/xls2csv.exe';
		} else {
			$this->xls2csv = $this->getDefaultCommand('xls2csv');
		}
	}
	
	if (empty($this->catdoc)) {
		if ('windows' === bab_browserOS()) {
			$this->catdoc = 'C:/Progra~1/SWISH-E/lib/swish-e/catdoc.exe';
		} else {
			$this->catdoc = $this->getDefaultCommand('catdoc');
		}
	}
	
	if (empty($this->unzip)) {
		if ('windows' === bab_browserOS()) {
			$this->unzip = 'C:/Progra~1/SWISH-E/lib/swish-e/unzip.exe';
		} else {
			$this->unzip = $this->getDefaultCommand('unzip');
		}
	}

	$this->object = $object;
	}
	
	
	/**
	 * Find executable on linux
	 */
	function getDefaultCommand($command) {
		
		$obj = $this->execCmd('whereis -b '.$command);
		while ($str = $obj->getNextDebug()) {
			if (preg_match("/\/[\/\w+\-]+/", $str, $m)) {
				if (isset($m[0])) {
					return $m[0];
				}
			}
		}
		
		return '/usr/bin/'.$command;
	}
	
	


	/**
	 * Execute system command
	 * @param string $cmd
	 * @return object bab_indexReturn
	 */
	function execCmd($cmd)
	{

	


	$r = new bab_indexReturn;
	$r->addDebug($cmd);

	$handle = popen($cmd, 'r');
	if (false === $handle) {
		$r->result = false;
		$r->addError(bab_translate("No access rights to system command execution"));
		return $r;
	}
	$buffer = '';
	while(!feof($handle)) {
	   $buffer .= fgets($handle, 1024);
	}
	pclose($handle);

	$buffer = bab_getStringAccordingToDataBase($buffer, $this->system_charset);

	$r->addDebug($buffer);

	return $r;
	}
}


/**
 * @return object bab_indexReturn
 */
class bab_batchFile {

	var $file;
	var $msgerror = false;
	var $fh;

	function bab_batchFile($file) {
		$this->file = $file;
	}

	function init() {
		$file = $this->file;
		$r = new bab_indexReturn;
		
		if (is_file($file) && !is_writable($file)) {
			$r->addError(sprintf(bab_translate("The file %s is not writable"),$file));
			$r->result = false;
			return $r;
		}

		if (file_exists($file)) {
			$mode = 'a';
		} else {
			$mode = 'w+';
			$r->addInfo(sprintf(bab_translate('The script %s has been created, please execute it to index the shudeled tasks'), $file));
		}

		if (!$this->fh = fopen($file, $mode)) {
			$r->addError(sprintf(bab_translate("Cannot open or create the file %s"),$file));
			$r->result = false;
		} else {
			$r->result = true;
		}

		return $r;
	}

	function addCmd($cmd) {
		if (fwrite($this->fh, $cmd."\n") === FALSE) {
			trigger_error(bab_translate("Error : cannot write to file"));
			return false;
		}
		return true;
	}

	function close() {
		if ($this->fh) {
			fclose($this->fh);
		}
	}
}

class bab_indexFilesCls extends swishCls
	{
		public function __construct($arr_files, $object)
		{
		parent::__construct($object);
		$this->arr_files = $arr_files;
		}


		/**
		 * template method
		 * @return bool
		 */
		public function getnextfile()
		{
		if (list(,$this->file) = each($this->arr_files))
			{
			$this->file = '"'.str_replace('\\' ,'/', $this->file);
			$this->file .= "\"\n";
			return true;
			}
		else
			return false;
		}

		/**
		 * @return bab_indexReturn
		 */
		public function setTempConfigFile($indexFile) {

			$r = new bab_indexReturn;
		
			if (!is_file($this->swishCmd)) {
				$r->result = false;
				$r->addError(sprintf(bab_translate('File not found : %s'),$this->swishCmd));
				return $r;
				}
			
			$this->objectIndex = $indexFile;

			$str = bab_printTemplate($this, 'swish.config');

			if ($handle = fopen($this->tmpCfgFile, 'w+')) {
				fwrite($handle, $str);
				fclose($handle);
				$r->result = true;
				$r->addDebug($str);
				return $r;
				}
			
			$r->result = false;
			$r->addError(bab_translate('Unexpected error : cannot write to upload directory'));
			return $r;
		}

		/**
		 * @return bab_indexReturn
		 */
		public function checkTimeout() {
			global $babDB;
		
			$r = new bab_indexReturn;

			$res = $babDB->db_query('SELECT * FROM '.BAB_INDEX_SPOOLER_TBL.' WHERE object='.$babDB->quote($this->object));

			if (0 < $babDB->db_num_rows($res)) {
			
				$object = $babDB->db_fetch_assoc($res);
				$object['function_parameter'] = unserialize($object['function_parameter']);
			
				if (!file_exists($this->indexLog)) {
					// locked but not launched yet
					$r->result = BAB_INDEX_PENDING;
					$r->addError(sprintf(bab_translate("There is a lock on the index file %s, indexation is in a waiting state"),$this->object));
				} else {
					
					$content = implode("", @file($this->indexLog));
					if (false === mb_strpos($content,'OVIDENTIA EOF')) {
						// file created but script not finished
						$r->result = BAB_INDEX_RUNNING;
						$r->addDebug($content);
						$r->addError(sprintf(bab_translate("There is a lock on the index file %s, indexing in progress"),$this->object));
					} else {
						// callback
						if (!empty($object['require_once'])) {
							require_once($object['require_once']);
							if (call_user_func($object['function'], $object['function_parameter'])) {
								// free object
								$babDB->db_query('DELETE FROM '.BAB_INDEX_SPOOLER_TBL.' WHERE object='.$babDB->quote($this->object));
								unlink($this->tmpCfgFile);
								unlink($this->indexLog);
								unlink($this->batchFile);
								unlink($this->errorLog);

								$r->result = BAB_INDEX_FREE;
								$r->addDebug(sprintf(bab_translate("The lock has been removed from %s"),$this->object));
							} else {
								
								$r->result = false;
								$r->addError("Error with callback function in ".$this->object);
								$r->addDebug($object['function']);
							}
						}
					}
				}
			} else {
				$r->result = BAB_INDEX_FREE;
				// $r->addDebug(sprintf(bab_translate("There is no lock on %s"),$this->object));
			}

			return $r;
		}


		/**
		 * Buid environement for indexation by command line
		 * Once the indexation is done, required file is included and the callback function is called
		 * Use the callback to set the flags coorectly in the database for the files
		 * The function_parameter is the only parameter given to the callback function
		 * this value will be serialized if necessary, so non serializable objects are forbidden
		 * @param string $require_once file to include
		 * @param string|array $function callback
		 * @param mixed $function_parameter
		 * @return bab_indexReturn
		 */
		public function prepareIndex($require_once, $function, $function_parameter) {

			global $babDB;
			
			$r = $this->checkTimeout();
			$r->result = BAB_INDEX_FREE === $r->result;
			$r->merge($this->setTempConfigFile($this->mainIndex));

			if (false === $r->result) {
				return $r;
			}

			$babDB->db_query('
				INSERT INTO '.BAB_INDEX_SPOOLER_TBL.' (object, require_once, function, function_parameter) 
				VALUES (
					'.$babDB->quote($this->object).',
					'.$babDB->quote($require_once).',
					'.$babDB->quote($function).',
					'.$babDB->quote(serialize($function_parameter)).'
				)
			');

			static $addEOF = NULL;

			if (NULL === $addEOF) {
				register_shutdown_function(array($this,'addEOF'));
				$addEOF = 0;
			}


			$bat = new bab_batchFile($this->batchFile);
			$r->merge($bat->init());

			if (false === $r->result) {
				return $r;
			}

			$bat->addCmd($this->swishCmd.' -c '.escapeshellarg($this->tmpCfgFile).' >> '.escapeshellarg($this->indexLog) .' 2>  '.escapeshellarg($this->errorLog));
			$bat->addCmd('echo "OVIDENTIA EOF" >> '.escapeshellarg($this->indexLog));
			$bat->close();

			$r->addDebug(sprintf(bab_translate('Indexation of %s has been added as a pending task'), $this->object));
			$r->result = true;

			return $r;
		}


		/**
		 * Register shutdown function
		 * wget http://users.ugent.be/~bpuype/wget/
		 * @since 6.5.91
		 */
		public function addEOF() {
			$bat = new bab_batchFile($this->batchFile);
			$bat->init();

			if (!defined('BAB_SWISHE_WGET_URL')) {
			
				$registry = bab_getRegistryInstance();
				$registry->changeDirectory('/bab/indexfiles/');
				$default_command = $registry->getValue('http_query_command', 'wget -q --spider %s');

				define('BAB_SWISHE_WGET_URL', $default_command);
			}
			
			$bat->addCmd(sprintf(BAB_SWISHE_WGET_URL, escapeshellarg($GLOBALS['babUrlScript'].'?tg=usrindex&cmd=EOF')));
			$bat->close();
		}


		/**
		 * Index files
		 * @return object bab_indexReturn
		 */
		public function indexFiles()
		{
			$r = $this->checkTimeout();
			
			if (BAB_INDEX_FREE === $r->result) {
				$r->merge($this->setTempConfigFile($this->mainIndex));	
				$r->merge($this->execCmd($this->swishCmd.' -c '.escapeshellarg($this->tmpCfgFile)));
				unlink($this->tmpCfgFile);
				$r->result = true;
			} else {
				$r->result = false;
				$r->addError(bab_translate("There is a pending prepared indexation, you can't launch another one at the same time"));
			}

			return $r;
		}

	
		/**
		 * Add file into index
		 * @return object bab_indexReturn
		 */
		public function addFilesToIndex() {

			if (!is_file($this->mainIndex)) {
				return $this->indexFiles();
			}

			$r = new bab_indexReturn;
			
			$r->merge($this->setTempConfigFile($this->mergeIndex));
			$r->merge($this->execCmd($this->swishCmd.' -c '.escapeshellarg($this->tmpCfgFile)));

			
			if (is_file($this->tempIndex)) {
				unlink($this->tempIndex);
				unlink($this->tempIndex.'.prop');
			}
			
			$r->merge($this->execCmd($this->swishCmd.' -M '.escapeshellarg($this->mainIndex).' '.escapeshellarg($this->mergeIndex).' '.escapeshellarg($this->tempIndex)));

			
			@unlink($this->tmpCfgFile);

			@unlink($this->mergeIndex);
			@unlink($this->mergeIndex.'.prop');
			
			if (is_file($this->tempIndex)) {
				@unlink($this->mainIndex);
				@unlink($this->mainIndex.'.prop');

				@rename($this->tempIndex, $this->mainIndex);
				@rename($this->tempIndex.'.prop', $this->mainIndex.'.prop');

				$r->result = true;
			} else {
				$r->addError(bab_translate("There is an error on the swish indexing process, no file to merge"));
				$r->result = false;
			}

			return $r;
		}
	}


class bab_searchFilesCls extends swishCls
	{

	private $query;

	public function __construct($object) {
		parent::__construct($object);
	}


	/**
	 * set query for swish-e
	 * @see bab_Search
	 */
	public function setQuery($query) {
		$this->query = $query;
	}



	/**
	 * create query for swish-e
	 * @deprecated since new search API
	 *
	 * @param	string	$query1
	 * @param	string	$query2
	 * @param	string	$option		'AND'|'OR'|'NOT'
	 * @param	string	$object
	 */
	public function setQueryOperator($query1, $query2, $option)
		{
		
		$query1 = preg_replace_callback("/\s(OR|NOT|AND|or|not|and)\s/", create_function('$v','return \' "\'.$v[1].\'" \';'), $query1);

		//space = OR in ovidentia

		$query1 = str_replace(' ', ' OR ', $query1);
		
		$this->query = $query1;
		if (!empty($query2))
			{
			$query2 = preg_replace_callback("/\s(OR|NOT|AND|or|not|and)\s/", create_function('$v','return \' "\'.$v[1].\'" \';'), $query2);
			$this->query .= ' '.$option.' ('.$query2.')';
			}
		}


	/**
	 * Execute swish-e command
	 * @return array
	 */
	public function searchFiles()
		{
		if (empty($this->swishCmd)) {
			return array();
		}
			
		bab_debug($this->query, DBG_INFO ,'swish-e');


		// try with php extension
		$return  = $this->searchFilesPecl();

		if (false !== $return) {
			return $return;
		}


		$cmd_query = bab_convertStringFromDatabase($this->query, $this->system_charset);
		
		// set a locale for escapeshellarg
		bab_locale();
		$system = $this->swishCmd.' -f '.escapeshellarg($this->mainIndex).' -w '.escapeshellarg($cmd_query);

		$r = $this->execCmd($system);
		$str = $r->debuginfos[1];

		$files = array();
		$debug = '';
		
		if (preg_match_all('/(\d+)\s+(.*)\s+\"(.*)\"\s+\d+/', $str, $matches))
			{
			for( $j = 0; $j< count($matches[1]); $j++)
				{
				$files[] = array(
						'file' 		=> $matches[2][$j],
						'title' 	=> $matches[3][$j], 
						'relevance' => (int) $matches[1][$j]
					);

				$debug .= $matches[2][$j]."\n";
				}
			}
		bab_debug('swish-e query : \''.$this->query."'\n\n".$debug, DBG_INFO ,'swish-e');
		return $files;
		}


	/**
	 * Search with PECL library
	 * @link http://www.php.net/swish
	 * @return array
	 */
	public function searchFilesPecl() {
		if (!class_exists('Swish')) {
			return false;
		}

		try {

			$swish = new Swish($this->mainIndex);
			$results = $swish->query($this->query);
			$files = array();
			
			while($result = $results->nextResult()) {
	
				bab_debug($result, DBG_INFO, 'swish-e');

				$files[] = array(
					'file' 		=> (string)	$result->swishdocpath,
					'title' 	=> (string) @$result->swishtitle, 
					'relevance' => (int) 	$result->swishrank
				);
			}

			$files = bab_getStringAccordingToDataBase($files, $this->system_charset);

			return $files;

		} catch (SwishException $e) {
			return false;
		}
	}



}








class bab_indexFileCls extends swishCls {
	
	function bab_indexFileCls($object) {
		
		parent::swishCls($object);
	}

	/**
	 * @see bab_setIndexObject()
	 * @return boolean
	 */
	function createObject($name, $onload) {
		
		return true;
	}

	/**
	 * @see bab_removeIndexObject()
	 * @return boolean
	 */
	function removeObject() {
		return unlink($this->mainIndex);
	}

}



class searchEngineInfosObjCls {

	public function getDescription() {
		return 'Swish-e';
	}

	public function getAvailableMimeTypes() {
		return array(
			'text/plain',
			'application/pdf',
			'application/vnd.sun.xml.writer',
			'text/xml',
			'text/html',
			'application/vnd.ms-excel',
			'application/msword',
			'application/vnd.oasis.opendocument.text',
			'application/vnd.oasis.opendocument.spreadsheet',
			'application/vnd.oasis.opendocument.presentation'
		);
	}
}


?>