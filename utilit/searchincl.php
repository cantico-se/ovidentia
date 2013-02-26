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
 * @deprecated	since the new search api
 * @see bab_Search
 *
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

	$obj = new bab_searchFilesCls($object);
	$obj->setQueryOperator($query1, $query2, $option);
	return $obj->searchFiles();
}



/**
 * return indexed files without upload path
 * @param	bab_SearchCriteria	$criteria		search criteria
 * @param 	string 				$object 		(if not specified, the name of the addon will be used)
 * @return array
 */
function bab_searchIndexedFilesFromCriteria(bab_SearchCriteria $criteria, $object = false) {

	$engine = bab_searchEngineInfos();

	if (!$object && isset($GLOBALS['babAddonFolder']))
		$object = $GLOBALS['babAddonFolder'];

	if (false === $engine)
		return false;

	switch($engine['name'])
		{
		case 'swish':
			include_once $GLOBALS['babInstallPath'].'utilit/searchincl.swish.php';
			include_once $GLOBALS['babInstallPath'].'utilit/searchbackend.swish.php';
			break;
		}

	$backend = new bab_SearchSwishBackEnd;

	$obj = new bab_searchFilesCls($object);
	$query = trim($criteria->toString($backend));
	$obj->setQuery($query);

	return $obj->searchFiles();

}






/**
 * Add a new index object
 * if the $name is already used for the same addon, the function return false without any error message
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






/**
 * @package search
 */
class bab_SearchDefaultForm {



	/**
	 * default search form
	 * @return string
	 */
	public static function getHTML() {

		$options = array(
			'OR' 	=> bab_translate('Or'),
			'AND'	=> bab_translate('And'),
			'NOT'	=> bab_translate('Exclude')
		);

		$htmloptions = '';

		foreach($options as $key => $value) {
			if ($key === bab_rp('option')) {
				$option = '<option value="%option%" selected="selected">%title%</option>';
			} else {
				$option = '<option value="%option%">%title%</option>';
			}

			$htmloptions .= str_replace(
				array('%option%'	, '%title%'),
				array($key			, $value),
				$option
			);
		}

		global $babBody;
		$babBody->addJavascriptFile($GLOBALS['babScriptPath'].'search.js');

		return str_replace(
			array('%labelprimary%'			, '%labelsecondary%'				, '%primary%'					, '%htmloptions%'	, '%secondary%'),
			array(bab_translate("Search")	, bab_translate("Advanced search")	, bab_toHtml(bab_rp('what'))	, $htmloptions		, bab_toHtml(bab_rp('what2'))),
			'
			<p id="bab_search_primary_bloc">
				<label for="bab_search_primary">%labelprimary% :</label>
				<input type="text" id="bab_search_primary" name="what" size="40" value="%primary%" />
			</p>

			<p id="bab_search_secondary_bloc">

				<select name="option">
					%htmloptions%
				</select>
				<input type="text" id="bab_search_secondary" name="what2" size="30" value="%secondary%" />
			</p>
			'
		);
	}


	/**
	 * Add criterions from default search form
	 * @return bab_SearchCriteria
	 */
	private static function addFromCriterions(bab_SearchCriteria $crit, bab_SearchTestable $testable) {



		$primary_search = bab_rp('what');
		$secondary_search = bab_rp('what2');
		$option = bab_rp('option');
		$delegation = bab_rp('delegation', null);




		if ($primary_search) {

			$primary_search_criteria = self::searchStringToCriteria($testable, $primary_search);

			if (!($primary_search_criteria instanceOf bab_SearchInvariant)) {
				$crit = $crit->_AND_($primary_search_criteria);
			}
		}

		if ($secondary_search) {


			$secondary_search_criteria = self::searchStringToCriteria($testable, $secondary_search);

			if (!($secondary_search_criteria instanceOf bab_SearchInvariant)) {
				switch($option) {

					case 'AND':
						$crit = $crit->_AND_($secondary_search_criteria);
						break;

					case 'NOT':
						$crit = $crit->_AND_($secondary_search_criteria->_NOT_());
						break;

					case 'OR':
					default:
						$crit = $crit->_OR_($secondary_search_criteria);
						break;
				}
			}
		}


		if (null !== $delegation && ($testable instanceOf bab_SearchRealm) && isset($testable->id_dgowner) && 'DGAll' !== $delegation)
		{
			// if id_dgowner field exist on search real, filter by delegation

			require_once dirname(__FILE__).'/delegincl.php';
			$arr = bab_getUserVisiblesDelegations();

			if (isset($arr[$delegation]))
			{
				$id_dgowner = $arr[$delegation]['id'];
				$crit = $crit->_AND_($testable->id_dgowner->is($id_dgowner));
			}
		}


		return $crit;
	}


	/**
	 * Get array of tags references found using default form
	 * @return array
	 */
	public static function getTagsReferences() {

		$option = bab_rp('option');

		require_once dirname(__FILE__) . '/tagApi.php';
		$oRefMgr = new bab_ReferenceMgr();

		$primary = array();
		$secondary = array();


		if (bab_rp('what')) {
			$primary_search 	= $oRefMgr->get(bab_rp('what'));
			if ($primary_search) {
				foreach($primary_search as $ref) {
					$primary[(string) $ref] = $ref;
				}
			}
		}


		if (bab_rp('what2')) {
			$secondary_search 	= $oRefMgr->get(bab_rp('what2'));
			if ($secondary_search) {
				foreach($secondary_search as $ref) {
					$secondary[(string) $ref] = $ref;
				}
			}
		} else {
			$option = 'OR';
		}

		switch($option) {
			case 'AND':
				return array_intersect($primary, $secondary);

			case 'NOT':
				return array_diff_assoc($primary, $secondary);

			case 'OR':
			default:
				return ($primary + $secondary);
		}
	}



	/**
	 * add default search form criteria to testable object
	 * the criteria is generated from the default form search
	 *
	 * @param	bab_SearchTestable $testable		search realm or search field
	 * @return 	bab_SearchCriteria
	 */
	public static function getCriteria(bab_SearchTestable $testable) {

		$crit = new bab_SearchInvariant;
		
		$criteria = self::addFromCriterions($crit, $testable);
		
		if ($testable instanceOf bab_SearchRealm) {
			$x = $testable->getDefaultCriteria();
			
			if (!($x instanceof bab_SearchInvariant))
			{
				$criteria = $criteria->_AND_($x);
			}
		} 
		
		
		return $criteria;
	}






	/**
	 * create a criteria for search queries without fields (search file content)
	 * the criteria is generated from the default form search
	 *
	 * @param	bab_SearchRealm 	$realm
	 * @return 	bab_SearchCriteria
	 */
	public static function getFieldLessCriteria(bab_SearchRealm $realm) {

		$crit = new bab_SearchInvariant;

		if (!isset($realm->search)) {
			return $crit;
		}

		return self::addFromCriterions($crit, $realm->search);
	}





	/**
	 * Create a <code>bab_searchCriteria</code> from a string of the search form
	 * used for primary_search and secondary_search
	 *
	 * @param	bab_searchTestable	$testable
	 * @param	string				$search
	 * @param	string				$operator
	 * @return bab_searchCriteria
	 */
	public static function searchStringToCriteria($testable, $search, $operator = '_AND_') {

		$criteria = new bab_SearchInvariant;

		if (preg_match_all('/(?:([^"][^\s]+)|(?:"([^"]+)")|(\w))\s*/', $search, $matchs)) {

			$arr = array();

			foreach($matchs[1] as $key => $match) {
				if (trim($match)) {
					$arr[] = trim($match);
				}
				if (trim($matchs[2][$key])) {
					$arr[] = trim($matchs[2][$key]);
				}

				if (trim($matchs[3][$key])) {
					$arr[] = trim($matchs[3][$key]);
				}
			}


			foreach($arr as $keyword) {

				$keyword = trim($keyword, ' ,;.');

				if ($keyword) {
					$criteria = $criteria->$operator($testable->contain($keyword));
				}
			}
		}

		return $criteria;
	}




	public function highlightKeyword() {
		$primary_search = bab_rp('what');
		$secondary_search = bab_rp('what2');

		if ($secondary_search) {
			return $primary_search.' '.$secondary_search;
		}

		return $primary_search;
	}
}

