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
	$text = ' '.$text.' ';
	$arr = explode(" ",trim(urldecode($w)));
	foreach($arr as $mot)
		{
		$mot_he = htmlentities($mot);
		
		if ($mot != $mot_he)
			{
			$text = preg_replace("/(\s*>[^<]*|\s+)(".$mot_he.")(\s+|[^>]*<\s*)/si", "\\1<span class=\"Babhighlight\">\\2</span>\\3", $text);
			}
		else
			{
			$text = preg_replace("/(\s*>[^<]*|\s+)(".$mot.")(\s+|[^>]*<\s*)/si", "\\1<span class=\"Babhighlight\">\\2</span>\\3", $text);
			}
		}
	return trim($text);
}


function bab_sql_finder_he($tbl,$str,$not="")
	{
	if ($not == "NOT") $op = "AND";
	else $op =  "OR";
	$tmp = htmlentities($str);
	if ($tmp != $str)
		return " ".$op." ".$tbl.$not." like '%".$tmp."%'";
	}

function bab_sql_finder($req2,$tablename,$option = "OR",$req1="")
{
	global $babDB;

	$like = '';

if( !bab_isMagicQuotesGpcOn())
	{
	$req2 = $babDB->db_escape_string($req2);
	$req1 = $babDB->db_escape_string($req1);
	}

if (trim($req1) != "") 
	$like = $tablename." like '%".$req1."%'".bab_sql_finder_he($tablename,$req1);

if (trim($req2) != "") 
	{
	$tb = explode(" ",trim($req2));
	switch ($option)
		{
		case "NOT":
			foreach($tb as $key => $mot)
				{
				if (trim($req1) == "" && $key==0)
					$like = $tablename." like '%".$mot."%'";
				else
					$like .= " AND ".$tablename." NOT like '%".$mot."%'".bab_sql_finder_he($tablename,$mot," NOT");
				}
		break;
		case "OR":
		case "AND":
		default:
			foreach($tb as $key => $mot)
				{
				$he = bab_sql_finder_he($tablename,$mot);
				if ( trim($req1) == "" && $key == 0 )
					$like = $tablename." like '%".$mot."%'".$he;
				else if ($he != "" && $option == "AND")
					$like .= " AND (".$tablename." like '%".$mot."%'".$he.")";
				else
					$like .= " ".$option." ".$tablename." like '%".$mot."%'".$he;
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
	global $babSearchEngine;

	if (!$object && isset($GLOBALS['babAddonFolder']))
		$object = $GLOBALS['babAddonFolder'];

	if (!isset($babSearchEngine) || !$object)
		return false;

	switch($babSearchEngine)
		{
		case 'swish':
			include_once $GLOBALS['babInstallPath'].'utilit/searchincl.swish.php';
			break;
		}

	$obj = new bab_indexFilesCls($arr_files, $object);
	return $obj->indexFiles();
}


/**
 * Search in indexed files
 * @param string $query1
 * @param string $query2
 * @param 'AND'|'OR'|'NOT' $option
 * @param string $object
 * @return array
 */
function bab_searchIndexedFiles($query1, $query2, $option, $object = false)
{
	global $babSearchEngine;

	if (!$object && isset($GLOBALS['babAddonFolder']))
		$object = $GLOBALS['babAddonFolder'];

	if (!isset($babSearchEngine) || !$object)
		return false;

	switch($babSearchEngine)
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
function bab_setIndexObject($name, $onload, $object = null, $addon = true) {

	global $babSearchEngine;
	$db = $GLOBALS['babDB'];
	
	if (null === $object && isset($GLOBALS['babAddonFolder']))
		$object = $GLOBALS['babAddonFolder'];

	if (true === $addon) {
		$req = "SELECT id FROM ".BAB_ADDONS." WHERE title='".$db->db_escape_string($GLOBALS['babAddonFolder'])."'";
		list($id_addon) = $db->db_fetch_array($db->db_query($req));
	} else {
		$id_addon = 0;
	}

	if (!isset($babSearchEngine) || !$object)
		return false;

	$res = $db->db_query("
		
		SELECT 
			COUNT(*) 
		FROM 
			".BAB_INDEX_FILES_TBL." 
		WHERE 
			id_addon='".$id_addon."' 
			AND name='".$db->db_escape_string($name)."' 
	");
	

	list($n) = $db->db_fetch_array($res);
	if ($n > 0)
		return false;

	$onload = $onload ? 1 : 0;

	switch($babSearchEngine)
		{
		case 'swish':
			include_once $GLOBALS['babInstallPath'].'utilit/searchincl.swish.php';
			break;
		}

	$obj = new bab_indexFileCls($object);
	if ($obj->createObject($name, $onload, $id_addon)) {

		$db->db_query("
			
				INSERT INTO 
					".BAB_INDEX_FILES_TBL." 
					(
						name,
						object,
						id_addon,
						index_onload
					) 
				VALUES 
					(
						'".$db->db_escape_string($name)."',
						'".$db->db_escape_string($object)."',
						'".$id_addon."',
						'".$onload."'
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
function bab_removeIndexObject($object = null) {

	global $babSearchEngine;
	$db = &$GLOBALS['babDB'];

	if (null === $object && isset($GLOBALS['babAddonFolder']))
		$object = $GLOBALS['babAddonFolder'];

	
	switch($babSearchEngine)
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


?>