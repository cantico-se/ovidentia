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
/**
* @internal SEC1 NA 08/12/2006 FULL
*/

include_once $GLOBALS['babInstallPath'].'utilit/artapi.php';

class categoriesHierarchy
{

	var $parentscount;
	var $parentname;
	var $parenturl;
	var $burl;
	var $topics;
	var $topictitle;

	public function __construct($topics,$cat,$link)
		{
		global $babDB;
		$this->link = $link;

		if( $topics != 0 )
			{
			$res = $babDB->db_query("select id_cat, category from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($topics)."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$cat = $arr['id_cat'];
				$this->arrparents[] = array($topics, $arr['category']);
				}
			else
				{
				$cat = 0;
				}		
			}
		else
			{
			$topics = 0;
			}

		if( $cat == -1)
			{
			$cat = 0;
			}

		$this->topics = $topics;
		$this->cat = $cat;
		$topcats = bab_getArticleCategories();
		
		if( isset($topcats[$cat]) )
			{
			$this->arrparents[] = array($cat, $topcats[$cat]['title']);
			while( $topcats[$cat]['parent'] != 0 )
			{
				$this->arrparents[] = array($topcats[$cat]['parent'],$topcats[$topcats[$cat]['parent']]['title']);
				$cat = $topcats[$cat]['parent'];
			}
			}
		$this->arrparents[] = array(0, bab_translate("Top"));

		$this->parentscount = count($this->arrparents);
		$this->arrparents = array_reverse($this->arrparents);
		}

	function getnextparent()
		{
		static $i = 0;
		if( $i < $this->parentscount)
			{
			if( $i == ($this->parentscount - 1))
				{
				$this->parenturl = "";
				$this->burl = false;
				}
			else
				{
				$this->burl = true;
				$this->parenturl = bab_toHtml($this->link."&cat=".$this->arrparents[$i][0]);
				}
			$this->parentname = bab_toHtml($this->arrparents[$i][1]);
			$i++;
			return true;
			}
		else
			{ 
			$i = 0;
			return false;
			}
		}
}


/**
 * add to page a representation of a topic category
 * @param int $topics
 * 
 */
function viewCategoriesHierarchy($topics)
	{
	global $babBody;
	class tempvch extends categoriesHierarchy
		{

		function tempvch($topics)
			{
			parent::__construct($topics, -1, $GLOBALS['babUrlScript']."?tg=topusr");
			}
		}

	$temp = new tempvch($topics);
	$babBody->babecho(	bab_printTemplate($temp,"articles.html", "categorieshierarchy"));
	}

class tempvch_txt extends categoriesHierarchy
	{

	function tempvch_txt($topics)
		{
		parent::__construct($topics, -1, $GLOBALS['babUrlScript']."?tg=topusr");
		}
	}

/**
 * Get a textual representation of a topic category
 * @param int $topics
 * @return string HTML
 */
function viewCategoriesHierarchy_txt($topics)
	{
	global $babBody;

	$temp = new tempvch_txt($topics, -1, $GLOBALS['babUrlScript']."?tg=topusr");
	return bab_printTemplate($temp,"articles.html", "categorieshierarchy_txt");
	}













/**
 * get article categories and article topics as an ordered array for use in a drop-down combo box
 * each entry on the returned array will have keys :
 * <ul>
 *		<li>name 		: string;	name of topic or category prefixed by a spacing string (non-breakin spaces) of variable length</li>
 *		<li>category 	: boolean;	if false, the entry is a topic</li>
 * 		<li>id_object 	: int;		id of topic or category</li>
 * </ul> 
 *
 * @param	int			$parentid		: optional id of parent category
 * @param	false|int	$delegationid	: if delegationid is false, categories are not filtered
 * @param	string		$rightaccesstable
 * @return 	array
 */
function bab_getArticleTopicsAsTextTree($parentid = 0, $delegationid = false, $rightaccesstable = BAB_TOPICSVIEW_GROUPS_TBL) {

	static $indentation_level = 0, $categories = false, $idcategoriesbyrights = array();
	$indentation = str_repeat(bab_nbsp(), 3*$indentation_level);
	
	$return = array();

	global $babDB, $babBody;
	
	// Verify the type array of $parentid
	if (!is_array($parentid)) {
		$parentid = array($parentid);
	}
	
	if (false === $rightaccesstable) {
		if (!bab_isUserAdministrator()) {
			$res = false;
		}
	}
	
	//INITALISE CATEGORY ARRAY
	if($categories === false){
		$sDelegation = ' ';
		if(false !== $delegationid) {
			$sDelegation = ' AND id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
		}
	
		// All fields and values of categories 
		$req = "SELECT 
				tc.* 
				
			from ".BAB_TOPICS_CATEGORIES_TBL." tc 
				LEFT JOIN ".BAB_TOPCAT_ORDER_TBL." tot on tc.id=tot.id_topcat 
				
			WHERE 
				tot.type='1' " . $sDelegation .  " 
				
			order by tot.ordering asc
		";
		
		$res = $babDB->db_query($req);
		
		if ($res) {
			while ($arr = $babDB->db_fetch_assoc($res)) {
				$categories[$arr['id_parent']][$arr['id']] = $arr;
			}
		}
	}
	
	//INITIALISE RIGHTS
	if(!isset($idcategoriesbyrights[$rightaccesstable]) && $rightaccesstable !== false){
		//Accessibles topics
		$idtopicsbyrights = bab_getUserIdObjects($rightaccesstable);
		
		// categories with accessibles topics
		$idcategoriesbyrights[$rightaccesstable] = array();
		
		if (BAB_TOPICSVIEW_GROUPS_TBL === $rightaccesstable) {
			// if tested access is topic view use cached values
			$idcategoriesbyrights[$rightaccesstable] = bab_getReadableArticleCategories();
		} else {
		
			$res2 = $babDB->db_query("
				select id_cat 
				from ".BAB_TOPICS_TBL." 
				where id in(".$babDB->quote($idtopicsbyrights).") AND id_cat NOT IN(".$babDB->quote($idcategoriesbyrights[$rightaccesstable]).")
			");

			while ($row2 = $babDB->db_fetch_array($res2)) {
				$idcategoriesbyrights[$rightaccesstable][$row2['id_cat']] = 1;
			}
				
				
			// All parents of categories accessibles
			$idcategoriesbyrightstmp = $idcategoriesbyrights[$rightaccesstable];
				
			foreach($idcategoriesbyrightstmp as $idcategory => $dummy) {
				$idParents = bab_getParentsArticleCategory($idcategory);
				foreach($idParents as $idParent) {
					$idcategoriesbyrights[$rightaccesstable][$idParent['id']] = 1;
				}
			}
		}
	}
	
	if($categories){
		foreach($parentid as $idparent){
			if(isset($categories[$idparent])){
				foreach($categories[$idparent] as $category){
					// Specifics rights or all rights ? 
					if (false === $rightaccesstable) {
					}else{
						if(!isset($idcategoriesbyrights[$rightaccesstable][$category['id']])){
							continue;
						}
					}
				
					$id_category = (int) $category['id'];
					
					$return[] = array(
						'name' 		=> $indentation.$category['title'],
						'category'	=> true,
						'id_object'	=> $id_category
					);
					
					$indentation_level++;
					$sublevel = bab_getArticleTopicsAsTextTree($id_category, $delegationid, $rightaccesstable);
					$indentation_level--;
					
					$return = array_merge($return, $sublevel);
				}
			}
		}
	}

	$res = bab_getArticleTopicsRes($parentid, $delegationid, $rightaccesstable);
	if ($res) {
		while ($arr = $babDB->db_fetch_assoc($res)) {

			$id_topic = (int) $arr['id'];

			$return[] = array(
				'name' 		=> $indentation.$arr['category'],
				'category'	=> false,
				'id_object'	=> $id_topic
			);

		}
	}

	return $return;
}





/**
 * Test if one of the user accessibles topics use tags
 * @return boolean
 */
function bab_userTopicsUseTags() {
	global $babDB;

	$res = $babDB->db_query('
		select COUNT(*) result from '.BAB_TOPICS_TBL.' 
		WHERE id IN('.$babDB->quote(bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL)).') 
		AND busetags=\'Y\'
	');

	if ($arr = $babDB->db_fetch_assoc($res)) {
		return 0 !== (int) $arr['result'];
	}

	return false;
}





/**
 * get topics from a category and topics of the sub-categories with optional access rights verification
 *
 * @param	int			$id_category	optional id of parent category
 * @param	false|int	$delegationid	if delegationid is false, categories are not filtered (default false)
 *
 * @return 	array						a list of ID in keys and values
 */
function bab_getTopicsFromCategory($id_category, $delegationid = false) {
	
	$return = array();

	global $babDB;
	
	$res = bab_getArticleCategoriesRes($id_category, $delegationid);
	
	if ($res) {
		while ($arr = $babDB->db_fetch_assoc($res)) {

			$id_subcategory = (int) $arr['id'];
			$sublevel = bab_getTopicsFromCategory($id_subcategory, $delegationid);
			$return += $sublevel;
		}
	}

	$res = bab_getArticleTopicsRes($id_category, $delegationid);
	if ($res) {
		while ($arr = $babDB->db_fetch_assoc($res)) {

			$id_topic = (int) $arr['id'];
			$return[$id_topic] = $id_topic;
		}
	}

	return $return;

}