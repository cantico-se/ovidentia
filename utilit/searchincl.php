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


function bab_highlightWord( $w, $text)
{
	
	$w = trim($w);
	if (empty($w)) {
		return $text;
	}
	$arr = explode(' ',$w);
	
	foreach($arr as $mot)
		{

		$text = str_replace('\"', '"', mb_substr(preg_replace('#(\>(((?>([^><]+|(?R)))*)\<))#se', "preg_replace('#(" . preg_quote($mot,'#') . ")#i', '<span class=\"Babhighlight\">\\\\1</span>', '\\0')", '>' . $text . '<'), 1, -1));

		$he = bab_toHtml($mot);
		
		if ($he != $mot) {
			$text = str_replace('\"', '"', mb_substr(preg_replace('#(\>(((?>([^><]+|(?R)))*)\<))#se', "preg_replace('#(" . preg_quote($he,'#') . ")#i', '<span class=\"Babhighlight\">\\\\1</span>', '\\0')", '>' . $text . '<'), 1, -1));
			}
    
		}

	return trim($text);
}

/**
 * Search a string with bab_toHtml
 */
function bab_sql_finder_he($tbl, $str, $not="")
	{
	global $babDB;
	
	if ($not == "NOT") $op = "AND";
	else $op =  "OR";
	$tmp = bab_toHtml($str);
	if ($tmp != $str)
		return " ".$op." ".$tbl.$not." like '%".$babDB->db_escape_like($tmp)."%'";
	}

function bab_sql_finder($req2,$tablename,$option = "OR",$req1="")
{
	global $babDB;

	$like = '';
	
	switch($option) {
		case 'AND':
		case 'OR':
		case 'NOT':
		
		break;
		default:
			$option = 'OR';
		break;
	}


if (trim($req1) != "") {
	$tb = explode(' ',$req1);
	foreach($tb as $key => $mot)
		{
		if ( $like == "" )
			$like = '('.$tablename." LIKE '%".$babDB->db_escape_like($mot)."%'".bab_sql_finder_he($tablename,$mot);
		else
			$like .= " OR (".$tablename." LIKE '%".$babDB->db_escape_like($mot)."%'".bab_sql_finder_he($tablename,$mot).")";
		}
	if( !empty($like))
		{
		$like .= ')';
		}
	}

if (trim($req2) != "") 
	{
	$tb = explode(" ",trim($req2));
	switch ($option)
		{
		case "NOT":
			foreach($tb as $key => $mot)
				{
				if (trim($req1) == "" && $key==0)
					$like = $tablename." NOT like '%".$babDB->db_escape_like($mot)."%'";
				else
					$like .= " AND ".$tablename." NOT like '%".$babDB->db_escape_like($mot)."%'".bab_sql_finder_he($tablename,$mot," NOT");
				}
		break;
		case "OR":
		case "AND":
		default:
			foreach($tb as $key => $mot)
				{
				$he = bab_sql_finder_he($tablename,$mot);
				if ( trim($req1) == "" && $key == 0 )
					$like = $tablename." like '%".$babDB->db_escape_like($mot)."%'".$he;
				else if ($he != "" && $option == "AND")
					$like .= " AND (".$tablename." like '%".$babDB->db_escape_like($mot)."%'".$he.")";
				else
					$like .= " ".$option." ".$tablename." like '%".$babDB->db_escape_like($mot)."%'".$he;
				}
		break;
		}
	}
	return $like;
}


/**
 * Index files, create new index, drop existing index
 * @param array $arr_files
 * @param string [$object] optional only for modules
 * @return boolean|string
 */
function bab_indexFiles($arr_files, $object = false)
{
	include_once $GLOBALS['babInstallPath']."utilit/indexincl.php";
	$engine = bab_searchEngineInfos();

	if (!$object && isset($GLOBALS['babAddonFolder']))
		$object = $GLOBALS['babAddonFolder'];

	// if the index object does not exist, create it :
	bab_setIndexObject($object, $object, false);

	if (false === $engine || !$object)
		return false;

	$obj = new bab_indexObject($object);
	return $obj->resetIndex($arr_files);
}


/**
 * Search in indexed files
 * @param string $query1
 * @param string $query2
 * @param 'AND'|'OR'|'NOT' $option
 * @param string $object (if not specified, the name of the addon will be used)
 * @return array
 */
function bab_searchIndexedFiles($query1, $query2, $option, $object = false)
{
	$engine = bab_searchEngineInfos();

	if (!$object && isset($GLOBALS['babAddonFolder']))
		$object = $GLOBALS['babAddonFolder'];

	if (false === $engine || !$object)
		return false;

	switch($engine['name'])
		{
		case 'swish':
			include_once $GLOBALS['babInstallPath'].'utilit/searchincl.swish.php';
			break;
		}

	$obj = new bab_searchFilesCls($query1, $query2, $option, $object);
	return $obj->searchFiles();
}



/**
 * Add a new index object
 * if the $name is allready used for the same addon, the function return false without any error message
 * if indexing is disabled, the function return false without any error message
 * @param string $name
 * @param boolean $onload
 * @param string $object this parameter is not required for addons
 * @param boolean $addon this parameter is not required for addons
 * @return int|false
 */
function bab_setIndexObject($object, $name, $onload, $disabled = false) {

	$engine = bab_searchEngineInfos();
	$db = $GLOBALS['babDB'];

	if (false === $engine)
		return false;

	$res = $db->db_query("
		
		SELECT 
			COUNT(*) 
		FROM 
			".BAB_INDEX_FILES_TBL." 
		WHERE 
			 name='".$db->db_escape_string($name)."' 
			OR object ='".$db->db_escape_string($object)."'
	");
	

	list($n) = $db->db_fetch_array($res);
	if ($n > 0)
		return false;

	$onload = $onload ? 1 : 0;
	$disabled = $disabled ? 1 : 0;

	switch($engine['name'])
		{
		case 'swish':
			include_once $GLOBALS['babInstallPath'].'utilit/searchincl.swish.php';
			break;
		}

	$obj = new bab_indexFileCls($object);
	if ($obj->createObject($name, $onload)) {

		$db->db_query("
			
				INSERT INTO 
					".BAB_INDEX_FILES_TBL." 
					(
						name,
						object,
						index_onload,
						index_disabled
					) 
				VALUES 
					(
						'".$db->db_escape_string($name)."',
						'".$db->db_escape_string($object)."',
						'".$onload."',
						'".$disabled."'
					)
			");

		return $db->db_insert_id();
	}

	return false;
}



/**
* Remove index object
* @param string $object
* @return boolean
*/
function bab_removeIndexObject($object) {

	$engine = bab_searchEngineInfos();
	$db = &$GLOBALS['babDB'];

	if (false === $engine) {
		return false;
	}
	
	switch($engine['name'])
		{
		case 'swish':
			include_once $GLOBALS['babInstallPath'].'utilit/searchincl.swish.php';
			break;
		}

	$obj = new bab_indexFileCls($object);
	if ($obj->removeObject()) {
		$db->db_query("DELETE FROM ".BAB_INDEX_FILES_TBL." WHERE id='".$id_index."'");
		return true;
	}

	return false;
}



function bab_removeUploadPath($str) {
	
	$last_char = mb_substr($GLOBALS['babUploadPath'],-1);
	
	if ('/' == $last_char || '\\' == $last_char) {
		return mb_substr($str, mb_strlen($GLOBALS['babUploadPath']));
	} else {
		return mb_substr($str, 1+mb_strlen($GLOBALS['babUploadPath']));
	}
}


/**
 * Remove 3 directories
 */
function bab_removeFmUploadPath($str) {
	$path = bab_removeUploadPath($str);
	
	$arr = explode('/', $path);
	
	unset($arr[0]);
	unset($arr[1]);
	unset($arr[2]);
	
	return implode('/', $arr);
}

?>