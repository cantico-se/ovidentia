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


class swishCls
{


	function swishCls($object)
	{
	$this->uploadDir = $GLOBALS['babBody']->babsite['uploadpath'];

	if (!is_dir($this->uploadDir.'/tmp/'))
		bab_mkdir($this->uploadDir.'/tmp/');

	if (!is_dir($this->uploadDir.'/SearchIndex/'))
		bab_mkdir($this->uploadDir.'/SearchIndex/');

	$this->uploadDir = str_replace('\\' ,'/', $this->uploadDir);

	$this->tmpCfgFile = $this->uploadDir.'/tmp/swish.config';
	$this->mainIndex = $this->uploadDir.'/SearchIndex/'.$object.'.index';
	$this->mergeIndex = $this->uploadDir.'/SearchIndex/'.$object.'.merge.index';
	$this->tempIndex = $this->uploadDir.'/SearchIndex/'.$object.'.temp.index';


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

	$this->object = $object;
	}


	function execCmd($cmd)
	{
	bab_debug($cmd);

	$handle = popen($cmd, 'r');
	if (false === $handle)
		return false;
	$buffer = '';
	while(!feof($handle)) {
	   $buffer .= fgets($handle, 1024);
	}
	pclose($handle);

	bab_debug($buffer);

	return $buffer;
	}
}

class bab_indexFilesCls extends swishCls
	{
		function bab_indexFilesCls($arr_files, $object)
		{
		parent::swishCls($object);
		$this->arr_files = $arr_files;
		}

		function getnextfile()
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

		function setTempConfigFile($indexFile)
		{
		
		if (!is_file($this->swishCmd)) {
			trigger_error('File not found : '.$this->swishCmd);
			return false;
			}
		
		$this->objectIndex = $indexFile;

		$str = bab_printTemplate($this, 'swish.config');

		if ($handle = fopen($this->tmpCfgFile, 'w+')) {
			fwrite($handle, $str);
			fclose($handle);
			}
		}

		
		function indexFiles()
		{
			
			$this->setTempConfigFile($this->mainIndex);			
			$str = $this->execCmd($this->swishCmd.' -c '.escapeshellarg($this->tmpCfgFile));
			unlink($this->tmpCfgFile);
			return $str;
		}

	
		/**
		 * Add file into index
		 * @return boolean
		 */
		function addFilesToIndex() {

			if (!is_file($this->mainIndex)) {
				return $this->indexFiles();
			}
			
			$this->setTempConfigFile($this->mergeIndex);
			$this->execCmd($this->swishCmd.' -c '.escapeshellarg($this->tmpCfgFile));

			
			if (is_file($this->tempIndex)) {
				unlink($this->tempIndex);
				unlink($this->tempIndex.'.prop');
			}
			
			$this->execCmd($this->swishCmd.' -M '.escapeshellarg($this->mainIndex).' '.escapeshellarg($this->mergeIndex).' '.escapeshellarg($this->tempIndex));

			
			unlink($this->tmpCfgFile);

			unlink($this->mergeIndex);
			unlink($this->mergeIndex.'.prop');
			
			if (is_file($this->tempIndex)) {
				unlink($this->mainIndex);
				unlink($this->mainIndex.'.prop');

				rename($this->tempIndex, $this->mainIndex);
				rename($this->tempIndex.'.prop', $this->mainIndex.'.prop');

				return true;
			}
		}
	}


class bab_searchFilesCls extends swishCls
	{
	function bab_searchFilesCls($query1, $query2, $option, $object)
		{
		parent::swishCls($object);
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

	function searchFiles()
		{
		$str = $this->execCmd($this->swishCmd.' -f '.escapeshellarg($this->mainIndex).' -w '.escapeshellarg($this->query));

		$files = array();
		
		if (preg_match_all('/(\d+)\s+(.*)\s+\"(.*)\"\s+\d+/', $str, $matches))
			{
			for( $j = 0; $j< count($matches[1]); $j++)
				{
				$files[] = array(
								'file' => $matches[2][$j],
								'title' => $matches[3][$j] // , 'mark' => $matches[1][$j]
								
								);
				}
			}
		
		return $files;
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

	function getDescription() {
		return 'Swish-e';
	}

	function getAvailableMimeTypes() {
		return array(
				'text/plain',
				'application/pdf',
				'application/vnd.sun.xml.writer',
				'text/xml',
				'text/html',
				'application/vnd.ms-excel',
				'application/msword'
			);
	}
}


?>