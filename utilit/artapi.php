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
include_once 'base.php';
include_once $GLOBALS['babInstallPath'].'utilit/artincl.php';
require_once dirname(__FILE__).'/defines.php';



/* CATEGORIES API */




/**
 * Topic title
 * @deprecated replaced by bab_getTopicTitle
 * @see	bab_getTopicTitle
 */
function bab_getCategoryTitle($id)
{
	return bab_getTopicTitle($id);
}

/**
 * Topic description
 * @deprecated replaced by bab_getTopicDescription
 * @see	bab_getTopicDescription
 */	
function bab_getCategoryDescription($id)
	{
		return bab_getTopicDescription($id);
	}	

	
/**
 * Get category title
 * @param int $id
 * @return string
 */
function bab_getTopicCategoryTitle($id)
	{
	global $babDB;
	$query = "select title from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['title'];
		}
	else
		{
		return "";
		}
}

/**
 * Get category description
 * @param int $id
 * @return string
 */
function bab_getTopicCategoryDescription($id)
	{
	global $babDB;
	$query = "select description from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['description'];
		}
	else
		{
		return "";
		}
	}	

/**
 * Get category delegation ID
 * @param int $id
 */
function bab_getTopicCategoryDelegationId($id)
	{
	global $babDB;
	$query = "select id_dgowner from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['id_dgowner'];
		}
	else
		{
		return false;
		}
	}

	
/**
 * Get Category array from id or UUID
 * @param string $identifier		ID or UUID
 * @return array
 */
function bab_getTopicCategoryArray($identifier)
{
	global $babDB;
	$query = "select * from ".BAB_TOPICS_CATEGORIES_TBL." where ";
	if (is_numeric($identifier))
	{
		$query .= "id=".$babDB->quote($identifier);
	} else if (36 === strlen($identifier)) {
		$query .= "uuid=".$babDB->quote($identifier);
	} else {
		throw new Exception('Wrong identifier');
	}
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
	{
		return $babDB->db_fetch_assoc($res);
	}
	
	return null;
}



/**
 * Get topic array from id or UUID
 * @param string $identifier		ID or UUID
 * @return array
 */
function bab_getTopicArray($identifier)
{
	global $babDB;
	$query = "select * from ".BAB_TOPICS_TBL." where ";
	if (is_numeric($identifier))
	{
		$query .= "id=".$babDB->quote($identifier);
	} else if (36 === strlen($identifier)) {
		$query .= "uuid=".$babDB->quote($identifier);
	} else {
		throw new Exception('Wrong identifier');
	}
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
	{
		return $babDB->db_fetch_assoc($res);
	}

	return null;
}
	
	
/**
 * 
 * @throws ErrorException
 * 
 * @param string 	$name
 * @param string 	$description		HTML
 * @param string 	$benabled			Y | N		section
 * @param string 	$template
 * @param string 	$disptmpl
 * @param int 		$topcatid
 * @param int 		$dgowner			Delegation
 * 
 * @return int
 */
function bab_addTopicsCategory($name, $description, $benabled, $template, $disptmpl, $topcatid, $dgowner=0)
	{
	global $babDB;
	require_once dirname(__FILE__).'/uuid.php';
	
	if( empty($name))
		{
		throw new ErrorException(bab_translate("ERROR: You must provide a name !!"));
		}

	$res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where title='".$babDB->db_escape_string($name)."' and id_parent='".$babDB->db_escape_string($topcatid)."' and id_dgowner='".$babDB->db_escape_string($dgowner)."'");
	if( $babDB->db_num_rows($res) > 0)
		{
		throw new ErrorException(bab_translate("This topic category already exists"));
		}
	else
		{
		$req = "insert into ".BAB_TOPICS_CATEGORIES_TBL." (title, description, enabled, template, id_dgowner, id_parent, display_tmpl, date_modification, uuid) VALUES (
		'" .$babDB->db_escape_string($name). "',
		'" . $babDB->db_escape_string($description). "',
		'" . $babDB->db_escape_string($benabled). "', 
		'" . $babDB->db_escape_string($template). "',
		'" . $babDB->db_escape_string($dgowner). "', 
		'" . $babDB->db_escape_string($topcatid). "', 
		'" . $babDB->db_escape_string($disptmpl). "',
		NOW(),
		".$babDB->quote(bab_uuid())."
		)";
		$babDB->db_query($req);

		$id = $babDB->db_insert_id();
		$req = "select max(ordering) from ".BAB_SECTIONS_ORDER_TBL." so, ".BAB_TOPICS_CATEGORIES_TBL." tc where so.position='0' and so.type='3' and tc.id=so.id_section and tc.id_dgowner='".$babDB->db_escape_string($dgowner)."'";
		$res = $babDB->db_query($req);
		$arr = $babDB->db_fetch_array($res);
		if( empty($arr[0]))
			{
			$req = "select max(ordering) from ".BAB_SECTIONS_ORDER_TBL." so where so.position='0'";
			$res = $babDB->db_query($req);
			$arr = $babDB->db_fetch_array($res);
			if( empty($arr[0]))
				$arr[0] = 0;
			}
		$babDB->db_query("update ".BAB_SECTIONS_ORDER_TBL." set ordering=ordering+1 where position='0' and ordering > '".$babDB->db_escape_string($arr[0])."'");
		$req = "insert into ".BAB_SECTIONS_ORDER_TBL." (id_section, position, type, ordering) VALUES ('" .$babDB->db_escape_string($id). "', '0', '3', '" . $babDB->db_escape_string(($arr[0]+1)). "')";
		$babDB->db_query($req);

		$res = $babDB->db_query("select max(ordering) from ".BAB_TOPCAT_ORDER_TBL." where id_parent='".$babDB->db_escape_string($topcatid)."'");
		$arr = $babDB->db_fetch_array($res);
		if( isset($arr[0]))
			$ord = $arr[0] + 1;
		else
			$ord = 1;
		$babDB->db_query("insert into ".BAB_TOPCAT_ORDER_TBL." (id_topcat, type, ordering, id_parent) VALUES ('" .$babDB->db_escape_string($id). "', '1', '" . $babDB->db_escape_string($ord). "', '".$babDB->db_escape_string($topcatid)."')");

		/* update default rights */
		include_once $GLOBALS['babInstallPath'].'admin/acl.php';
		aclCloneRights(BAB_DEF_TOPCATVIEW_GROUPS_TBL, $topcatid, BAB_DEF_TOPCATVIEW_GROUPS_TBL, $id);
		aclCloneRights(BAB_DEF_TOPCATSUB_GROUPS_TBL, $topcatid, BAB_DEF_TOPCATSUB_GROUPS_TBL, $id);
		aclCloneRights(BAB_DEF_TOPCATCOM_GROUPS_TBL, $topcatid, BAB_DEF_TOPCATCOM_GROUPS_TBL, $id);
		aclCloneRights(BAB_DEF_TOPCATMOD_GROUPS_TBL, $topcatid, BAB_DEF_TOPCATMOD_GROUPS_TBL, $id);
		aclCloneRights(BAB_DEF_TOPCATMAN_GROUPS_TBL, $topcatid, BAB_DEF_TOPCATMAN_GROUPS_TBL, $id);		
		return $id;
		}
	}
	
	
	
	
/**
 * Update article category
 * @param int 		$id_category
 * @param string 	$name
 * @param string 	$description
 * @param string 	$benabled		Y|N			This value can be null
 * @param string 	$template					This value can be null
 * @param string 	$disptmpl					This value can be null
 * @param int 		$topcatid					This value can be null
 * @param int 		$dgowner					This value can be null
 * @return bool
 */	
function bab_updateTopicsCategory($id_category, $name, $description, $benabled, $template, $disptmpl, $topcatid, $dgowner=null)
{
	global $babBody, $babDB;
	if( empty($name))
	{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
	}
	
	$res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id=".$babDB->quote($id_category));
	if( $babDB->db_num_rows($res) === 0)
	{
		$babBody->msgerror = bab_translate("This topic category does not exists");
		return false;
	}
	else
	{
		$old = $babDB->db_fetch_assoc($res);
		$tmp = array();	
		
		
		if (isset($name)) 		{	$tmp[]= "title			=".$babDB->quote($name);		}
		if (isset($description)){	$tmp[]= "description	=".$babDB->quote($description);	}
		if (isset($benabled)) 	{	$tmp[]= "enabled		=".$babDB->quote($benabled);	}
		if (isset($template)) 	{	$tmp[]= "template		=".$babDB->quote($template);	}
		if (isset($dgowner)) 	{	$tmp[]= "id_dgowner		=".$babDB->quote($dgowner);		}
		if (isset($topcatid)) 	{	$tmp[]= "id_parent		=".$babDB->quote($topcatid);	}
		if (isset($disptmpl)) 	{	$tmp[]= "display_tmpl	=".$babDB->quote($disptmpl);	}
		
		if (empty($tmp))
		{
			throw new Exception('Nothing to update in topic category');
			return false;
		}
		
		$tmp[]= "date_modification = NOW()";
		
		$req = "UPDATE ".BAB_TOPICS_CATEGORIES_TBL." SET ".implode(', ',$tmp)." WHERE id=".$babDB->quote($id_category)."";
		$babDB->db_query($req);
		
		if ($old['id_parent'] !== (string) $topcatid) {
	
			$res = $babDB->db_query("select max(ordering) from ".BAB_TOPCAT_ORDER_TBL." where id_parent='".$babDB->db_escape_string($topcatid)."'");
			$arr = $babDB->db_fetch_array($res);
			if( isset($arr[0]))
				$ord = $arr[0] + 1;
			else
				$ord = 1;
				
			$babDB->db_query("UPDATE ".BAB_TOPCAT_ORDER_TBL." SET id_parent=".$babDB->quote($topcatid).", ordering=".$babDB->quote($ord)." WHERE id_topcat=".$babDB->quote($id_category)."");
		}
	}
	
	return true;
}	
	

	
	


function bab_getArticleDelegationId($iIdArticle)
{
	global $babDB;
	
	$sQuery = 
		'SELECT 
			topicCategories.id_dgowner iIdDelegation
		FROM ' . 
			BAB_ARTICLES_TBL . ' articles, ' .
			BAB_TOPICS_TBL . ' topics, ' .
			BAB_TOPICS_CATEGORIES_TBL . ' topicCategories 
		WHERE 
			articles.id=' . $babDB->quote($iIdArticle) . ' AND
			topics.id = articles.id_topic AND
			topicCategories.id = topics.id_cat';
	
	$oResult = $babDB->db_query($sQuery);
	if($oResult && $babDB->db_num_rows($oResult) > 0)
	{
		$aData = $babDB->db_fetch_array($oResult);
		return $aData['iIdDelegation'];
	}
	return false;
}


/**
 * Get all article categories with cache
 * @return array
 */
function bab_getArticleCategories()
{
	static $topcats = null;
	if (!is_null($topcats))
		return $topcats;

	global $babDB;

	$res = $babDB->db_query("select id, title, description, id_parent from ".BAB_TOPICS_CATEGORIES_TBL."");
	while($arr = $babDB->db_fetch_array($res))
	{
		$topcats[$arr['id']]['parent'] = $arr['id_parent'];
		$topcats[$arr['id']]['title'] = $arr['title'];
		$topcats[$arr['id']]['description'] = $arr['description'];
	}

	return $topcats;
}


/**
 * Get readable article categories with cache
 * @return array
 */
function bab_getReadableArticleCategories()
{
	static $topcatview = null;
	if (!is_null($topcatview))
		return $topcatview;
	
	global $babDB;
	
	$topcatview = array();
	
	$topview = bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL);
	
	$res = $babDB->db_query("select id_cat from ".BAB_TOPICS_TBL." where id in(".$babDB->quote(array_keys($topview)).")");
	while( $row = $babDB->db_fetch_array($res))
	{
		if( !isset($topcatview[$row['id_cat']]))
		{
			$topcatview[$row['id_cat']] = 1;
		}
	}
	
	if(!empty($topcatview))
	{
		$topcatsview_tmp = $topcatview;
		$topcats = bab_getArticleCategories();
		foreach( $topcatsview_tmp as $cat => $val)
		{
			while(isset($topcats[$cat]) && $topcats[$cat]['parent'] != 0 )
			{
				if( !isset($topcatview[$topcats[$cat]['parent']]))
				{
					$topcatview[$topcats[$cat]['parent']] = 1;
				}
				$cat = $topcats[$cat]['parent'];
			}
		}
	}
	
	return $topcatview;
}


	
	
/**
 * Get categories in db_query resource
 *
 * @param	int|array		$parentid			: list of id of the parent category
 * @param	int|false		$delegationid		: if delegationid is false, categories are not filtered
 * @param   string|false   $rightaccesstable   : name of the right access table in topic. If false, categories are not filtered by user's rights 
 * 
 * Values of $rightsaccesstable :
 * 	false : administrator access
 *  BAB_TOPICSCOM_GROUPS_TBL : right submit comments
 *	BAB_TOPICSMAN_GROUPS_TBL : right manage topic
 *	BAB_TOPICSMOD_GROUPS_TBL : right modify articles
 *	BAB_TOPICSSUB_GROUPS_TBL : right submit articles
 *	BAB_TOPICSVIEW_GROUPS_TBL : right view articles (value by default)
 *
 * @return 	resource|false : first childs of $parentid
 */
function bab_getArticleCategoriesRes($parentid, $delegationid = false, $rightaccesstable = BAB_TOPICSVIEW_GROUPS_TBL) {
	global $babDB;
	
	// Verify the type array of $parentid 
	if (!is_array($parentid)) {
		$parentid = array($parentid);
	}
	

	$sDelegation = ' ';
	if(false !== $delegationid) {
		$sDelegation = ' AND tc.id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
	}
	
	// List of id categories 
	$IdEntries = array();
	
	
	
	if( count($parentid) > 0 ) {
		
		// All categories, childs of $parentid
		$res = $babDB->db_query("select id from ".BAB_TOPICS_CATEGORIES_TBL." where id_parent IN (".$babDB->quote($parentid).")");
		
		// Specifics rights or all rights ? 
		if (false === $rightaccesstable) {
			// Administrator rights 
			
			while ($row = $babDB->db_fetch_assoc($res)) {
				$IdEntries[$row['id']] = $row['id'];
			}
			
		} else {
			// Accessibles topics
			$idtopicsbyrights = bab_getUserIdObjects($rightaccesstable);
			
			// categories with accessibles topics
			$idcategoriesbyrights = array();
			
			if (BAB_TOPICSVIEW_GROUPS_TBL === $rightaccesstable) {
				// if tested access is topic view use cached values
				$idcategoriesbyrights = bab_getReadableArticleCategories();

			} else {
			
				$res2 = $babDB->db_query("
					select id_cat 
					from ".BAB_TOPICS_TBL." 
					where id in(".$babDB->quote($idtopicsbyrights).") AND id_cat NOT IN(".$babDB->quote($idcategoriesbyrights).")
				");
				
				while ($row2 = $babDB->db_fetch_array($res2)) {
					$idcategoriesbyrights[$row2['id_cat']] = 1;
				}
				
				
				// All parents of categories accessibles 
				$idcategoriesbyrightstmp = $idcategoriesbyrights;
				
				foreach($idcategoriesbyrightstmp as $idcategory => $dummy) {
					$idParents = bab_getParentsArticleCategory($idcategory);
					foreach($idParents as $idParent) {
						$idcategoriesbyrights[$idParent['id']] = 1;
					}
				}
			}
			
			// add accessibles categories
			if ($babDB->db_num_rows($res) > 0) {
				while ($row = $babDB->db_fetch_array($res)) {
					if (isset($idcategoriesbyrights[$row['id']]) ) {
						$IdEntries[$row['id']] = $row['id'];
					}
				}
			}
		}
	}

	// All fields and values of categories 
	if($IdEntries) {
		$req = "SELECT 
				tc.* 
				
			from ".BAB_TOPICS_CATEGORIES_TBL." tc 
				LEFT JOIN ".BAB_TOPCAT_ORDER_TBL." tot on tc.id=tot.id_topcat 
				
			WHERE 
				tc.id IN (".$babDB->quote($IdEntries).") 
				and (tot.id IS NULL OR tot.type='1') " . $sDelegation .  " 
				
			order by tot.ordering asc
		";
		
		return $babDB->db_query($req);
	}
	
	return false;
}



/**
 * Get first children articles categories information (id, title, description)
 *
 * @param	array		$parentid		: list of id of the parent category (0 )
 * @param	int|false	$delegationid	: if delegationid is false, categories are not filtered
 * @param   string|false   $rightaccesstable    : name of the right access table in topic. If false, categories are not filtered by user's rights 
 * 
 * Values of $rightsaccesstable :
 *   	false : administrator access
 *	BAB_TOPICSCOM_GROUPS_TBL : right submit comments
 *	BAB_TOPICSMAN_GROUPS_TBL : right manage topic
 *	BAB_TOPICSMOD_GROUPS_TBL : right modify articles
 *	BAB_TOPICSSUB_GROUPS_TBL : right submit articles
 *	BAB_TOPICSVIEW_GROUPS_TBL : right view articles (value by default)
 *
 * @return 	array : array indexed by id categories, categories are childs of $parentid
 */
function bab_getChildrenArticleCategoriesInformation($parentid, $delegationid = false, $rightaccesstable = BAB_TOPICSVIEW_GROUPS_TBL) {
	global $babDB;
	
	/* Verify the type array of $parentid */
	if (!is_array($parentid)) {
		$parentid = array($parentid);
	}
	
	$categories = array();
	

	
	$res = bab_getArticleCategoriesRes($parentid, $delegationid, $rightaccesstable);
	if ($babDB->db_num_rows($res) > 0) {
		while ($row = $babDB->db_fetch_array($res)) {
			$categories[$row['id']] = array('id' => $row['id'], 'title' => $row['title'], 'description' => $row['description']);
		}
	}
	
	return $categories;
}

/*
 * Get parents articles categories information (for each category : id, title, description)
 * @param	int		$categoryid		: id of the category
 * @param   boolean $reverse : reverse results
 * @return 	array : array indexed by id categories, categories are parents of $categoryid
 */
function bab_getParentsArticleCategory($categoryid, $reverse=false) {
	global $babBody, $babDB;
	/*@var $babBody babBody */
	
	$categories = array();
	
	if (!is_numeric($categoryid)) {
		return $categories;
	}
	
	/* List of all categories */
	$topcats = bab_getArticleCategories();
	/* Id categories */
	$idcategories = array();
	if (isset($topcats[$categoryid])) {
		while ($topcats[$categoryid]['parent'] != 0) {
			$idcategories[] = $topcats[$categoryid]['parent'];
			$categoryid = $topcats[$categoryid]['parent'];
		}
	}
	
	if (count($idcategories) > 0) {
		if ($reverse) {
			$idcategories = array_reverse($idcategories);
		}
		$res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id IN (".$babDB->quote($idcategories).")");
		if ($babDB->db_num_rows($res) > 0) {
			while ($row = $babDB->db_fetch_array($res)) {
				$categories[$row['id']] = array('id' => $row['id'], 'title' => $row['title'], 'description' => $row['description']);
			}
		}
		/* order by $idcategories */
		$categoriestmp = array();
		for ($i=0;$i<=count($idcategories)-1;$i++) {
			$idcat = $idcategories[$i];
			if (isset($categories[$idcat])) {
				$categoriestmp[$idcat] = $categories[$idcat];
			}
		}
		$categories = $categoriestmp;
	}
	
	return $categories;
}




/* TOPICS API */
	



/**
 * Get topic title
 * @param	int		$id
 * @return	string
 */
function bab_getTopicTitle($id)
	{
		global $babDB;
		$query = "select category from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($id)."'";
		$res = $babDB->db_query($query);
		if( $res && $babDB->db_num_rows($res) > 0)
		{
			$arr = $babDB->db_fetch_array($res);
			return $arr['category'];
		}
		else
		{
			return "";
		}
	}

	
/**
 * Get topic description
 * @param unknown_type $id
 * @return string
 */
function bab_getTopicDescription($id)
	{
		global $babDB;
		$query = "select description from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($id)."'";
		$res = $babDB->db_query($query);
		if( $res && $babDB->db_num_rows($res) > 0)
		{
			$arr = $babDB->db_fetch_array($res);
			return $arr['description'];
		}
		else
		{
			return "";
		}
		
	}


/**
 * Add a topic
 * @param string	$name
 * @param string	$description
 * @param int		$idCategory
 * @param string	&$error
 * @param array		$topicArr
 * @return unknown_type
 */
function bab_addTopic($name, $description, $idCategory, &$error, $topicArr = array())
{
	global $babBody, $babDB;
	require_once dirname(__FILE__).'/uuid.php';
	
	$arrdefaults = array(	'idsaart'=> 0, 
							'idsacom'=> 0, 
							'idsa_update'=> 0, 
							'notify'=> 'N', 
							'lang'=>$GLOBALS['babLanguage'], 
							'article_tmpl'=>'', 
							'display_tmpl'=>'', 
							'allow_hpages'=>'N',
							'allow_pubdates'=>'N',
							'allow_attachments'=>'N',
							'allow_update'=>0,
							'allow_manupdate'=>0,
							'max_articles'=>10,
							'auto_approbation'=>'N',
							'busetags'=>'N',
							'allow_addImg'=>'N',
							'allow_unsubscribe' => 0,
							'allow_meta' => 0,
							'allow_empty_head' => 0,
							'uuid' => bab_uuid()
							);
	
	if( empty($name))
		{
		$error = bab_translate("ERROR: You must provide a topic name !!");
		return 0;
		}

	$res = $babDB->db_query("select id from ".BAB_TOPICS_TBL." where category='".$babDB->db_escape_string($name)."' and id_cat='".$babDB->db_escape_string($idCategory)."'");
	if( $babDB->db_num_rows($res) > 0)
		{
		$error = bab_translate("ERROR: This topic already exists");
		return 0;
		}

	foreach($arrdefaults as $k=>$v)
	{
		if( isset($topicArr[$k]))
		{
			$arrdefaults[$k]=$topicArr[$k];
		}
	}
	$arrdefaults['category']= $name;
	$arrdefaults['description']= $description;
	$arrdefaults['id_cat']= $idCategory;
	
	$babDB->db_query("insert into ".BAB_TOPICS_TBL." (".implode(',', array_keys($arrdefaults)).", date_modification) values (".$babDB->quote($arrdefaults).", NOW())");
	$id = $babDB->db_insert_id();

	$res = $babDB->db_query("select max(ordering) from ".BAB_TOPCAT_ORDER_TBL." where id_parent='".$babDB->db_escape_string($idCategory)."'");
	$arr = $babDB->db_fetch_array($res);
	if( isset($arr[0]))
	{
		$ord = $arr[0] + 1;
	}
	else
	{
		$ord = 1;
	}
	$babDB->db_query("insert into ".BAB_TOPCAT_ORDER_TBL." (id_topcat, type, ordering, id_parent) VALUES ('" .$babDB->db_escape_string($id). "', '2', '" . $babDB->db_escape_string($ord). "', '".$babDB->db_escape_string($idCategory)."')");

	/* update default rights */
	aclCloneRights(BAB_DEF_TOPCATVIEW_GROUPS_TBL, $idCategory, BAB_TOPICSVIEW_GROUPS_TBL, $id);
	aclCloneRights(BAB_DEF_TOPCATSUB_GROUPS_TBL, $idCategory, BAB_TOPICSSUB_GROUPS_TBL, $id);
	aclCloneRights(BAB_DEF_TOPCATCOM_GROUPS_TBL, $idCategory, BAB_TOPICSCOM_GROUPS_TBL, $id);
	aclCloneRights(BAB_DEF_TOPCATMOD_GROUPS_TBL, $idCategory, BAB_TOPICSMOD_GROUPS_TBL, $id);
	aclCloneRights(BAB_DEF_TOPCATMAN_GROUPS_TBL, $idCategory, BAB_TOPICSMAN_GROUPS_TBL, $id);
	
	return $id;
}







/**
 * Update a topic
 * 
 * @since 7.3.95
 * 
 * @param int 		$id_topic
 * @param string 	$name
 * @param string 	$description
 * @param int 		$idCategory
 * @param string 	&$error
 * @param array 	$topicArr
 * @return int
 */
function bab_updateTopic($id_topic, $name, $description, $idCategory, &$error, $topicArr = array())
{
	global $babBody, $babDB;

	
	if( empty($name))
		{
		$error = bab_translate("ERROR: You must provide a topic name !!");
		return 0;
		}

	$res = $babDB->db_query("select * from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($id_topic)."' ");
	if( $babDB->db_num_rows($res) === 0)
		{
		$error = bab_translate("ERROR: This topic does not exists");
		return 0;
		}
		
	$old = $babDB->db_fetch_assoc($res);

	
	$topicArr['category']= $name;
	$topicArr['description']= $description;
	$topicArr['id_cat']= (string) $idCategory;
	
	
	
	$tmp = array();
	foreach($topicArr as $key => $value)
	{
		$tmp[]= $key.'='.$babDB->quote($value);
	}
	
	$tmp[]= 'date_modification = NOW()';

	$babDB->db_query("UPDATE ".BAB_TOPICS_TBL." SET ".implode(', ', $tmp).' WHERE id='.$babDB->quote($id_topic));
	
	if ($topicArr['id_cat'] !== $old['id_cat'])
	{
		// the topic has been moved
		
		
		$res = $babDB->db_query("select max(ordering) from ".BAB_TOPCAT_ORDER_TBL." where id_parent='".$babDB->db_escape_string($idCategory)."'");
		$arr = $babDB->db_fetch_array($res);
		if( isset($arr[0]))
		{
			$ord = $arr[0] + 1;
		}
		else
		{
			$ord = 1;
		}
		$babDB->db_query("UPDATE ".BAB_TOPCAT_ORDER_TBL." SET ordering=".$babDB->quote($ord).", id_parent=".$babDB->quote($idCategory)." WHERE id_topcat=".$babDB->quote($id_topic));
			
	}
	

	

	return $id_topic;
}






/**
 * Get articles topics (in db_query resource )
 * @param	array		$categoryid		: list of articles categories
 * @param	int|false	$delegationid	: if delegationid is false, topics are not filtered
 * @param   string|false   $rightaccesstable    : name of the right access table in topic. If false, topics are not filtered by user's rights 
 * 
 * Values of $rightsaccesstable :
 * 	false : administrator access
 *	BAB_TOPICSCOM_GROUPS_TBL : right submit comments
 *	BAB_TOPICSMAN_GROUPS_TBL : right manage topic
 *	BAB_TOPICSMOD_GROUPS_TBL : right modify articles
 *	BAB_TOPICSSUB_GROUPS_TBL : right submit articles
 *	BAB_TOPICSVIEW_GROUPS_TBL : right view articles (value by default)
 * 
 * @return 	resource|false
 */
function bab_getArticleTopicsRes($categoryid, $delegationid = false, $rightaccesstable = BAB_TOPICSVIEW_GROUPS_TBL) {
	global $babBody, $babDB;
	

	
	$sDelegation = ' ';
	$sLeftJoin = ' ';
	/* Request with delegation */
	if (false !== $delegationid) {
		$sLeftJoin = 
			'LEFT JOIN ' .
				BAB_TOPICS_TBL . ' tc ON tc.id = id_topcat ' .
			'LEFT JOIN ' .
				BAB_TOPICS_CATEGORIES_TBL . ' tpc ON tpc.id = tc.id_cat ';
		
		$sDelegation = ' AND tpc.id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
	}

	$IdEntries = array();
	
	if (count($categoryid) > 0 ) {
		/* Add topics in $IdEntries in order */
		$req = "select * from ".BAB_TOPCAT_ORDER_TBL. " tco " . $sLeftJoin . " where tco.type='2' and tco.id_parent IN (".$babDB->quote($categoryid).")" . $sDelegation . " order by tco.ordering asc";
		$res = $babDB->db_query($req);
		while ($row = $babDB->db_fetch_array($res)) {
			if (false === $rightaccesstable) {
				/* No right specified : administrator rights */
				array_push($IdEntries, $row['id_topcat']);
			} else {
				/* Specific right */
				if (bab_isAccessValid($rightaccesstable, $row['id_topcat'])) {
					array_push($IdEntries, $row['id_topcat']);
				}
			}
		}
	}

	if (count($IdEntries) > 0) {
		$req = "select 
				tc.*,
				u.id_user unsubscribed 
			from 
			".BAB_TOPICS_TBL." tc 
				LEFT JOIN ".BAB_TOPCAT_ORDER_TBL." tot ON tc.id=tot.id_topcat 
				LEFT JOIN bab_topics_unsubscribe u ON tc.id=u.id_topic AND u.id_user=".$babDB->quote(bab_getUserId())." 
				
			where tc.id IN (".$babDB->quote($IdEntries).") and tot.type='2' 
			order by tot.ordering asc
		";
		return $babDB->db_query($req);
	}
		
	return false;
}

/**
 * Get articles topics information, first children of categories (id, title, description)
 * @param	array		$categoryid		: list of articles categories
 * @param	int|false	$delegationid	: if delegationid is false, topics are not filtered
 * @param   string|false   $rightaccesstable    : name of the right access table in topic. If false, topics are not filtered by user's rights 
 * 
 * Values of $rightsaccesstable :
 * 	false : administrator access
 *	BAB_TOPICSCOM_GROUPS_TBL : right submit comments
 *	BAB_TOPICSMAN_GROUPS_TBL : right manage topic
 *	BAB_TOPICSMOD_GROUPS_TBL : right modify articles
 *	BAB_TOPICSSUB_GROUPS_TBL : right submit articles
 *	BAB_TOPICSVIEW_GROUPS_TBL : right view articles (value by default)
 * 
 * @return 	array
 */
function bab_getChildrenArticleTopicsInformation($categoryid, $delegationid = false, $rightaccesstable = BAB_TOPICSVIEW_GROUPS_TBL) {
	global $babBody, $babDB;
	
	$topics = array();
	

	
	$res = bab_getArticleTopicsRes($categoryid, $delegationid, $rightaccesstable);
	if ($babDB->db_num_rows($res) > 0) {
		while ($row = $babDB->db_fetch_array($res)) {
			$topics[$row['id']] = array('id' => $row['id'], 'title' => $row['category'], 'description' => $row['description']);
		}
	}
	
	return $topics;
}

/**
 * Returns the id of the delegation of the topic
 * @param $idTopic id of the topic
 * @return int|false
 */
function bab_getTopicDelegationId($idTopic)
{
	global $babDB;
	
	$sQuery = 
		'SELECT 
			topicCategories.id_dgowner iIdDelegation
		FROM ' . 
			BAB_TOPICS_TBL . ' topics, ' .
			BAB_TOPICS_CATEGORIES_TBL . ' topicCategories 
		WHERE 
			topics.id = ' . $babDB->quote($idTopic) . ' AND
			topicCategories.id = topics.id_cat';
	
	$oResult = $babDB->db_query($sQuery);
	if($oResult && $babDB->db_num_rows($oResult) > 0)
	{
		$aData = $babDB->db_fetch_array($oResult);
		return $aData['iIdDelegation'];
	}
	return false;
}




/* ARTICLES API */	



	
function bab_getArticleTitle($article)
	{
	global $babDB;
	$query = "select title from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($article)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['title'];
		}
	else
		{
		return "";
		}
	}

/**
 * Get article as an array
 * @param int | string 	$article	 article id or UUID
 * @param bool			$fullpath	 Add CategoriesHierarchy key into array
 * @return array
 */ 
function bab_getArticleArray($article, $fullpath = false)
	{
	global $babDB;

	$query = "select a.*,t.category topic from ".BAB_ARTICLES_TBL." a,".BAB_TOPICS_TBL." t where t.id=a.id_topic AND ";
	if (is_numeric($article))
	{
		$query .= "a.id='".$babDB->db_escape_string($article)."'";
	} else if (36 === strlen($article))
	{
		$query .= "a.uuid='".$babDB->db_escape_string($article)."'";
	} else {
		throw new Exception('Wrong identifier');
	}
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		if ($fullpath) $arr['CategoriesHierarchy'] = viewCategoriesHierarchy_txt($arr['id_topic']);
		return $arr;
		}
	else
		{
		return array();
		}
	}
	
function bab_getDraftArticleArray($id_draft)
	{
	global $babDB;
	$query = "select a.*,t.category topic from ".BAB_ART_DRAFTS_TBL." a LEFT JOIN ".BAB_TOPICS_TBL." t ON t.id=a.id_topic where a.id='".$babDB->db_escape_string($id_draft)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_assoc($res);
		return $arr;
		}
	else
		{
		return array();
		}
	}
	

function bab_getArticleDate($article)
	{
	global $babDB;
	$query = "select date from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($article)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return bab_strftime(bab_mktime($arr['date']));
		}
	else
		{
		return "";
		}
	}

function bab_getArticleAuthor($article)
	{
	global $babDB;
	$query = "select id_author from ".BAB_ARTICLES_TBL." where id='".$babDB->db_escape_string($article)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		$query = "select firstname, lastname from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($arr['id_author'])."'";
		$res = $babDB->db_query($query);
		if( $res && $babDB->db_num_rows($res) > 0)
			{
			$arr = $babDB->db_fetch_array($res);
			return bab_composeUserName($arr['firstname'], $arr['lastname']);
			}
		else
			return bab_translate("Anonymous");
		}
	else
		{
		return bab_translate("Anonymous");
		}
	}




/* ARTICLES API */	


/**
 * Get articles information, first children of a topic (id, idtopic, title, head, body, idauthor, author, sqldate, date, archive (boolean))
 * @param	int		    $topicid		: id parent topic
 * @param   boolean     $fullpath       : add CategoriesHierarchy in results
 * @param   int     	$articlestype   : if 1 : articles & articles archives are added, if 2 : only articles are added, if 3 : only articles archives are added
 * @param   string|false   $rightaccesstable    : name of the right access table in topic. If false, articles are not filtered by user's rights 
 * 
 * Values of $rightsaccesstable :
 * 	false : all rights
 *	BAB_TOPICSCOM_GROUPS_TBL : right submit comments
 *	BAB_TOPICSMAN_GROUPS_TBL : right manage topic
 *	BAB_TOPICSMOD_GROUPS_TBL : right modify articles
 *	BAB_TOPICSSUB_GROUPS_TBL : right submit articles
 *	BAB_TOPICSVIEW_GROUPS_TBL : right view articles (value by default)
 * 
 * @return 	array
 */
function bab_getChildrenArticlesInformation($topicid, $fullpath = false, $articlestype = 2, $rightaccesstable = BAB_TOPICSVIEW_GROUPS_TBL) {
	global $babDB, $babInstallPath;
	
	include_once $babInstallPath.'utilit/topincl.php';
	
	$articles = array();
	
	
	if (!is_numeric($topicid)) {
		return $articles;
	}
	
	/* Verify rights */
	if (false !== $rightaccesstable) {
		/* Specific right */
		$idtopicsbyrights = bab_getUserIdObjects($rightaccesstable); /* all id topics with right */
		if (!isset($idtopicsbyrights[$topicid])) {
			return $articles; /* return nothing */
		}
	}
	
	$query = "select * from ".BAB_ARTICLES_TBL." where id_topic='".$babDB->db_escape_string($topicid)."'";
	if ($articlestype === 2) {
		/* only articles, no archives */
		$query .= " AND archive='N'";
	}
	if ($articlestype === 3) {
		/* only archives */
		$query .= " AND archive='Y'";
	}
	$res = $babDB->db_query($query);
	if ($babDB->db_num_rows($res) > 0) {
		while ($row = $babDB->db_fetch_array($res)) {
			$articles[$row['id']] = array('id' => $row['id'], 'title' => $row['title'], 'head' => $row['head'], 'body' => $row['body'], 'idauthor' => $row['id_author'], 'author' => bab_getArticleAuthor($row['id']), 'sqldate' => $row['date'], 'date' => bab_getArticleDate($row['id']));
			if ($fullpath) {
				$articles[$row['id']]['CategoriesHierarchy'] = viewCategoriesHierarchy_txt($row['id_topic']);
			}
			if ($row['archive'] == 'N') {
				$articles[$row['id']]['archive'] = false;
			} else {
				$articles[$row['id']]['archive'] = true;
			}
		}
	}
	
	return $articles;
}
	

/**
 * Submit draft as article
 * create article and delete draft or send notification to approver
 * @param int $idart
 * @param int &$articleid
 * @return bool
 */
function bab_submitArticleDraft($idart, &$articleid = null)
{
	require_once dirname(__FILE__) . '/artdraft.class.php';
	$draft = new bab_ArtDraft;
	$draft->getFromIdDraft($idart);
	
	if ($draft->submit())
	{
		$articleid = $draft->id_article;
		return true;
	}
	
	return false;
}



/**
 * Adds an article draft
 * 
 * @param string	$title		Title of the new article draft
 * @param string	$head		Head of the new article draft
 * @param string	$body		Body of the new article draft
 * @param int		$idTopic	Id of the topic where we create the article draft
 * @param string	$error 		Returned error message
 * @param array		$articleArr	An array which contains options for the new article draft : date_submission, notify_members...
 * 
 * @return int 		Id of the new article draft
 */
function bab_addArticleDraft($title, $head, $body, $idTopic, &$error, $articleArr = array(), $headFormat = 'html', $bodyFormat = 'html')
{
	global $babBody, $babDB;
	

	/* Options by default */
	$arrdefaults = array(	'id_author'			=> (int) $GLOBALS['BAB_SESS_USERID'],
							'lang'				=> $GLOBALS['babLanguage'], 
							'date_submission'	=> '0000-00-00 00:00:00', 
							'date_archiving'	=> '0000-00-00 00:00:00', 
							'date_publication'	=> '0000-00-00 00:00:00', 
							'hpage_private'		=> 'N', 
							'hpage_public'		=> 'N', 
							'notify_members'	=> 'N', 
							'update_datemodif'	=> 'N',
							'restriction'		=> ''
						);
	/* The title can't be empty */
	if( empty($title)) {
		$error = bab_translate("The title of the article should not be empty");
		bab_debug("Error in function bab_addArticleDraft() : the title of the article can not be empty");
		return 0;
	}
	
	/* Id topic can not be empty */
	$informationTopic = array(
		'allow_update' => 0,
		'allow_manupdate' => 0
	);
	
	if(!empty($idTopic)) {
		$res = $babDB->db_query("select * from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($idTopic)."'");
		if (!$res || $babDB->db_num_rows($res) == 0) {
			$error = bab_translate("Unknown topic");
			return 0;
		} else {
			$informationTopic = $babDB->db_fetch_array($res);
		}
	}
	
	foreach(array('date_submission', 'date_archiving', 'date_publication') as $dkey)
	{
		if (empty($articleArr[$dkey]))
		{
			unset($articleArr[$dkey]);
		}
	}
	
	/* Crush the options by default by the options passed in parameters of the function ($articleArr) */
	foreach($arrdefaults as $k=>$v) {
		if( isset($articleArr[$k])) {
			$arrdefaults[$k]=$articleArr[$k];
		}
	}
	
	/* Verify if the current user can create the article draft */
	if (bab_isAccessValidByUser(BAB_TOPICSSUB_GROUPS_TBL, $idTopic, $arrdefaults['id_author'])
			||	bab_isAccessValidByUser(BAB_TOPICSMOD_GROUPS_TBL, $idTopic, $arrdefaults['id_author'])
			|| ($informationTopic['allow_update'] != '0' && $arrdefaults['id_author'] == $GLOBALS['BAB_SESS_USERID'])
			|| ($informationTopic['allow_manupdate'] != '0' && bab_isAccessValidByUser(BAB_TOPICSMAN_GROUPS_TBL, $idTopic, $arrdefaults['id_author']))) {
	} else {
		$error = bab_translate("Access denied, draft creation failed");
		bab_debug("Error in function bab_addArticleDraft() : the current user has no rights to create the article draft. Verify the rights access of the topic ".$idTopic);
		return 0;
	}
	
	if( empty($arrdefaults['id_author']) ) {
		$res = $babDB->db_query("select id from ".BAB_USERS_LOG_TBL." where sessid='".session_id()."' and id_user='0'");
		if( $res && $babDB->db_num_rows($res) == 1 ) {
			$arr = $babDB->db_fetch_array($res);
			$idanonymous = $arr['id'];
		} else {
			$error = bab_translate("The Ovidentia configuration does not allow article saving for anonymous users (the anonymous users log is missing)");
			return 0;
		}
	} else {
		$idanonymous = 0;
	}
	
	$arrdefaults['title'] = $title;
	$arrdefaults['body'] = $body;
	$arrdefaults['head'] = $head;
	$arrdefaults['body_format'] = $bodyFormat;
	$arrdefaults['head_format'] = $headFormat;
	$arrdefaults['id_topic'] = $idTopic;
	$arrdefaults['id_anonymous'] = $idanonymous;

	$babDB->db_query('INSERT INTO '.BAB_ART_DRAFTS_TBL.' 
			('.implode(',', array_keys($arrdefaults)).', date_creation, date_modification) 
		VALUES 
			('.$babDB->quote($arrdefaults).', NOW(), NOW())
	');
	
	$iddraft = $babDB->db_insert_id();
	
	return $iddraft;
}




/**
 * Adds an article
 * 
 * @param string	$title		Title of the new article
 * @param string	$head		Head of the new article
 * @param string	$body		Body of the new article
 * @param int		$idTopic	Id of the topic where we create the article
 * @param string	$error 		Returned error message
 * @param array		$articleArr	An array which contains options for the new article: date_submission, notify_members...
 * 
 * @return bool
 */
function bab_addArticle($title, $head, $body, $idTopic, &$error, $articleArr = array(), $headFormat = 'html', $bodyFormat = 'html', &$articleId = null)
{
	$articleId = null;
	$iddraft = bab_addArticleDraft($title, $head, $body, $idTopic, $error, $articleArr, $headFormat, $bodyFormat);
	if ($iddraft) {
		return bab_submitArticleDraft($iddraft, $articleId);
	}
	return false;
}



/* COMMENTS API */	
	


/**
 * @param int	$com	The comment id
 * @return string
 */
function bab_getCommentTitle($com)
{
	global $babDB;
	$query = 'SELECT subject FROM '.BAB_COMMENTS_TBL.' WHERE id='.$babDB->quote($com);
	$res = $babDB->db_query($query);
	if ($res && $babDB->db_num_rows($res) > 0) {
		$arr = $babDB->db_fetch_assoc($res);
		return $arr['subject'];
	} else {
		return '';
	}
}	
	


/**
 * @param int	$article	The comment id
 * @return string
 */
function bab_getArticleNbComment($article)
{
	global $babDB;
	$query = 'SELECT count(*) as nb_com FROM '.BAB_COMMENTS_TBL.' WHERE id_article='.$babDB->quote($article);
	$res = $babDB->db_query($query);
	if ($res && $babDB->db_num_rows($res) > 0) {
		$arr = $babDB->db_fetch_assoc($res);
		return $arr['nb_com'];
	} else {
		return '';
	}
}




/**
 * Saves an article comment.
 *
 * If $commentId is not specified, the comment will be created
 * otherwise the specified comment will be updated.
 *
 * The current user will be the author of the post.
 *
 * @param int		$topicId		The article's topic id.
 * @param int		$articleId		The article id.
 * @param string	$subject		The comment title (plain text).
 * @param string	$message		The comment body (in html)
 * @param int		$parentId		The parent comment id.
 * @param int		$commentId		If specified this comment will be updated otherwise a new comment is created.
 *
 * @return int		The comment id.
 */
function bab_saveArticleComment($topicId, $articleId, $subject, $message, $parentId = 0, $articleRating = 0, $commentId = null, $messageFormat= 'html')
{
	global $babDB, $BAB_SESS_USER, $BAB_SESS_EMAIL, $BAB_SESS_USERID;

	if (empty($BAB_SESS_USER)) {
		$authorName = bab_translate('Anonymous');
		$authorEmail = '';
		$authorId = 0;
	} else {
		$authorName = $BAB_SESS_USER;
		$authorEmail = $BAB_SESS_EMAIL;
		$authorId = $BAB_SESS_USERID;
	}

	if (isset($commentId)) {
		$req = 'UPDATE '.BAB_COMMENTS_TBL.'
					SET id_topic = ' . $babDB->quote($topicId) . ',
		 				id_article = ' . $babDB->quote($articleId) . ',
		 				id_parent = ' . $babDB->quote($parentId) . ',
		 				id_last_editor = ' . $babDB->quote($authorId) . ',
		 				last_update = NOW(),
		 				subject = ' . $babDB->quote($subject) . ',
		 				message = ' . $babDB->quote($message) . '
		 		WHERE id = ' . $babDB->quote($commentId);
		$babDB->db_query($req);
	} else {

		$req = 'INSERT INTO '.BAB_COMMENTS_TBL.' (
						id_topic,
						id_article,
						id_parent,
						date,
						subject,
						message,
						message_format,
						article_rating,
						id_author,
						name,
						email)
				VALUES (' . $babDB->quote($topicId). ',
						' . $babDB->quote($articleId). ',
						' . $babDB->quote($parentId). ',
						NOW(),
						' . $babDB->quote($subject) . ',
						' . $babDB->quote($message) . ', 
						' . $babDB->quote($messageFormat) . ', 
						' . $babDB->quote($articleRating) . ',
						' . $babDB->quote($authorId) . ',
						' . $babDB->quote($authorName). ',
						' . $babDB->quote($authorEmail). ')';

		$babDB->db_query($req);
		$commentId = $babDB->db_insert_id();
	}

	// From here we check the approbation workflow for article comments.
	$req = 'SELECT * FROM ' . BAB_TOPICS_TBL . ' WHERE id=' . $babDB->quote($topicId);
	$res = $babDB->db_query($req);
	if ($res && $babDB->db_num_rows($res) > 0) {
		$topic = $babDB->db_fetch_assoc($res);

		if ($topic['idsacom'] != 0) {
			include_once $GLOBALS['babInstallPath'] . 'utilit/afincl.php';
			if ($topic['auto_approbation'] == 'Y') {
				$idfai = makeFlowInstance($topic['idsacom'], 'com-' . $commentId, $GLOBALS['BAB_SESS_USERID']);
			} else {
				$idfai = makeFlowInstance($topic['idsacom'], 'com-' . $commentId);
			}
		}

		if ($topic['idsacom'] == 0 || $idfai === true) {
			$babDB->db_query('UPDATE ' . BAB_COMMENTS_TBL . " SET confirmed='Y' WHERE id=" . $babDB->quote($commentId));
		} elseif(!empty($idfai)) {
			$babDB->db_query('UPDATE ' . BAB_COMMENTS_TBL. ' SET idfai=' . $babDB->quote($idfai) . ' WHERE id=' . $babDB->quote($commentId));
			$nfusers = getWaitingApproversFlowInstance($idfai, true);
			notifyCommentApprovers($commentId, $nfusers);
		}
	}
	return $commentId;
}




/**
 * Returns the average rating of comments for this article.
 * The average rating should be a floating point number between 1 and 5.
 * If the returned value is 0, the article has not been rated yet.
 *
 * @param $articleId
 * @return float			
 */
function bab_getArticleAverageRating($articleId)
{
	global $babDB;

	$sql = 'SELECT AVG(article_rating) AS average_rating
				FROM ' . BAB_COMMENTS_TBL . '
				WHERE id_article = ' . $babDB->quote($articleId) . '
					AND article_rating > 0
				';
	$res = $babDB->db_query($sql);
	$articleComments = $babDB->db_fetch_assoc($res);
	return (float)($articleComments['average_rating']);
}




/**
 * Returns the number of ratings for this article.
 *
 * @param $articleId
 * @return int			
 */
function bab_getArticleNbRatings($articleId)
{
	global $babDB;

	$sql = 'SELECT COUNT(article_rating) AS nb_ratings
				FROM ' . BAB_COMMENTS_TBL . '
				WHERE id_article = ' . $babDB->quote($articleId) . '
					AND article_rating > 0
				';
	$res = $babDB->db_query($sql);
	$articleComments = $babDB->db_fetch_assoc($res);
	return (int)($articleComments['nb_ratings']);
}


/* ASSOCIATED IMAGES API */

	
	

/**
 * This function insert a record in the database.
 * Before using this function, upload the file using 
 * class bab_PublicationImageUploader.
 * For now only one image can be attached
 *
 * @param int		$iIdCategory	Category identifier
 * @param string	$sName			Name of the image
 * @param string	$sRelativePath	Relative path of the image.
 * 									The relative path should be completed by the character '/'.
 * @return bool						True on success, false otherwise 
 */
function bab_addImageToCategory($iIdCategory, $sName, $sRelativePath)
{
	require_once dirname(__FILE__) . '/tableWrapperClass.php';
	
	$oTblWr = new BAB_TableWrapper(BAB_TOPICS_CATEGORIES_IMAGES_TBL);
	
	$aAttribut = array(
		'id'			=> '',
		'idCategory'	=> $iIdCategory,
		'name'			=> $sName,
		'relativePath'	=> $sRelativePath
	);
	
	$bSkipFirst = false;
	return $oTblWr->save($aAttribut, $bSkipFirst);
}

/**
 * This function update a record from the database.
 * Before using this function, upload the file using 
 * class bab_PublicationImageUploader
 * For now only one image can be attached
 *
 * @param int 		$iIdImage		Identifier of the record to update
 * @param int 		$iIdCategory	Category identifier
 * @param string	$sName			Name of the image
 * @param string	$sRelativePath	Relative path of the image.
 * 									The relative path should be completed by the character '/'.
 * @return bool						True on success, false otherwise
 */
function bab_updateImageCategory($iIdImage, $iIdCategory, $sName, $sRelativePath)
{
	require_once dirname(__FILE__) . '/tableWrapperClass.php';
	
	$oTblWr = new BAB_TableWrapper(BAB_TOPICS_CATEGORIES_IMAGES_TBL);
	
	$aAttribut = array(
		'id'			=> $iIdImage,
		'idCategory'	=> $iIdCategory,
		'name'			=> $sName,
		'relativePath'	=> $sRelativePath
	);
	
	return $oTblWr->update($aAttribut);
}

/**
 * Get a record from the database
 * For now only one image can be attached
 *
 * @param int $iIdCategory	Category identifier
 * @return Array|bool	Array on success, false otherwise
 */
function bab_getImageCategory($iIdCategory)
{
	require_once dirname(__FILE__) . '/tableWrapperClass.php';
	
	$oTblWr = new BAB_TableWrapper(BAB_TOPICS_CATEGORIES_IMAGES_TBL);
	
	$aAttribut = array(
		'idCategory'	=> $iIdCategory,
		'id'			=> -1,
		'name'			=> '',
		'relativePath'	=> ''
	);
	
	return $oTblWr->load($aAttribut, 1, 3, 0, 1);
}

/**
 * Delete a record
 * For now only one image can be attached 
 *
 * @param int $iIdCategory
 * @return bool
 */
function bab_deleteImageCategory($iIdCategory)
{
	require_once dirname(__FILE__) . '/tableWrapperClass.php';
	
	$oTblWr = new BAB_TableWrapper(BAB_TOPICS_CATEGORIES_IMAGES_TBL);
	
	$aAttribut = array(
		'idCategory' => $iIdCategory
	);
	
	return $oTblWr->delete($aAttribut);
}


/**
 * This function insert a record in the database.
 * Before using this function, upload the file using 
 * class bab_PublicationImageUploader.
 * For now only one image can be attached
 *
 * @param int		$iIdTopic		Topic identifier
 * @param string	$sName			Name of the image
 * @param string	$sRelativePath	Relative path of the image.
 * 									The relative path should be completed by the character '/'.
 * @return bool						True on success, false otherwise 
 */
function bab_addImageToTopic($iIdTopic, $sName, $sRelativePath)
{
	require_once dirname(__FILE__) . '/tableWrapperClass.php';
	
	$oTblWr = new BAB_TableWrapper(BAB_TOPICS_IMAGES_TBL);
	
	$aAttribut = array(
		'id'			=> '',
		'idTopic'		=> $iIdTopic,
		'name'			=> $sName,
		'relativePath'	=> $sRelativePath
	);
	
	$bSkipFirst = false;
	return $oTblWr->save($aAttribut, $bSkipFirst);
}

/**
 * This function update a record from the database.
 * Before using this function, upload the file using 
 * class bab_PublicationImageUploader
 * For now only one image can be attached
 *
 * @param int 		$iIdImage		Identifier of the record to update
 * @param int 		$iIdTopic		Topic identifier
 * @param string	$sName			Name of the image
 * @param string	$sRelativePath	Relative path of the image.
 * 									The relative path should be completed by the character '/'.
 * @return bool						True on success, false otherwise
 */
function bab_updateImageTopic($iIdImage, $iIdTopic, $sName, $sRelativePath)
{
	require_once dirname(__FILE__) . '/tableWrapperClass.php';
	
	$oTblWr = new BAB_TableWrapper(BAB_TOPICS_IMAGES_TBL);
	
	$aAttribut = array(
		'id'			=> $iIdImage,
		'idTopic'		=> $iIdTopic,
		'name'			=> $sName,
		'relativePath'	=> $sRelativePath
	);
	
	return $oTblWr->update($aAttribut);
}

/**
 * Get a record from the database
 * For now only one image can be attached
 *
 * @param int $iIdTopic	Topic identifier
 * @return Array|bool	Array on success, false otherwise
 */
function bab_getImageTopic($iIdTopic)
{
	require_once dirname(__FILE__) . '/tableWrapperClass.php';
	
	$oTblWr = new BAB_TableWrapper(BAB_TOPICS_IMAGES_TBL);
	
	$aAttribut = array(
		'idTopic'		=> $iIdTopic,
		'id'			=> -1,
		'name'			=> '',
		'relativePath'	=> ''
	);
	
	return $oTblWr->load($aAttribut, 1, 3, 0, 1);
}

/**
 * Delete a record
 * For now only one image can be attached 
 *
 * @param int $iIdTopic
 * @return bool
 */
function bab_deleteImageTopic($iIdTopic)
{
	require_once dirname(__FILE__) . '/tableWrapperClass.php';
	
	$oTblWr = new BAB_TableWrapper(BAB_TOPICS_IMAGES_TBL);
	
	$aAttribut = array(
		'idTopic' => $iIdTopic
	);
	
	return $oTblWr->delete($aAttribut);
}

//////-----------------------


/**
 * This function insert a record in the database.
 * Before using this function, upload the file using 
 * class bab_PublicationImageUploader.
 * For now only one image can be attached
 *
 * @param int		$iIdDraft		Draft article identifier
 * @param string	$sName			Name of the image
 * @param string	$sRelativePath	Relative path of the image.
 * 									The relative path should be completed by the character '/'.
 * @return bool						True on success, false otherwise 
 */
function bab_addImageToDraftArticle($iIdDraft, $sName, $sRelativePath)
{
	require_once dirname(__FILE__) . '/tableWrapperClass.php';
	
	$oTblWr = new BAB_TableWrapper(BAB_ART_DRAFTS_IMAGES_TBL);
	
	$aAttribut = array(
		'idDraft'		=> $iIdDraft,
		'name'			=> $sName,
		'relativePath'	=> $sRelativePath
	);
	
	$bSkipFirst = false;
	return $oTblWr->save($aAttribut, $bSkipFirst);
}

/**
 * Get a record from the database
 * For now only one image can be attached
 *
 * @param int	$iIdDraft	Draft article identifier
 * @return Array|bool	Array on success, false otherwise
 */
function bab_getImageDraftArticle($iIdDraft)
{
	require_once dirname(__FILE__) . '/tableWrapperClass.php';
	
	$oTblWr = new BAB_TableWrapper(BAB_ART_DRAFTS_IMAGES_TBL);
	
	$aAttribut = array(
		'idDraft'		=> $iIdDraft,
		'id'			=> -1,
		'name'			=> '',
		'relativePath'	=> ''
	);
	
	return $oTblWr->load($aAttribut, 1, 3, 0, 1);
}

/**
 * Delete a record
 * For now only one image can be attached 
 *
 * @param int $iIdCategory
 * @return bool
 */
function bab_deleteImageDraftArticle($iIdDraft)
{
	require_once dirname(__FILE__) . '/tableWrapperClass.php';
	
	$oTblWr = new BAB_TableWrapper(BAB_ART_DRAFTS_IMAGES_TBL);
	
	$aAttribut = array(
		'idDraft' => $iIdDraft
	);
	
	return $oTblWr->delete($aAttribut);
}


/**
 * This function insert a record in the database.
 * Before using this function, upload the file using 
 * class bab_PublicationImageUploader.
 * For now only one image can be attached
 *
 * @param int		$iIdArticle		Article identifier
 * @param string	$sName			Name of the image
 * @param string	$sRelativePath	Relative path of the image.
 * 									The relative path should be completed by the character '/'.
 * @return bool						True on success, false otherwise 
 */
function bab_addImageToArticle($iIdArticle, $sName, $sRelativePath)
{
	require_once dirname(__FILE__) . '/tableWrapperClass.php';
	
	$oTblWr = new BAB_TableWrapper(BAB_ARTICLES_IMAGES_TBL);
	
	$aAttribut = array(
		'idArticle'		=> $iIdArticle,
		'name'			=> $sName,
		'relativePath'	=> $sRelativePath
	);
	
	$bSkipFirst = false;
	return $oTblWr->save($aAttribut, $bSkipFirst);
}

/**
 * This function update a record from the database.
 * Before using this function, upload the file using 
 * class bab_PublicationImageUploader
 * For now only one image can be attached
 *
 * @param int 		$iIdImage		Identifier of the record to update
 * @param int		$iIdArticle		Article identifier
 * @param string	$sName			Name of the image
 * @param string	$sRelativePath	Relative path of the image.
 * 									The relative path should be completed by the character '/'.
 * @return bool						True on success, false otherwise
 */
function bab_updateImageArticle($iIdImage, $iIdArticle, $sName, $sRelativePath)
{
	require_once dirname(__FILE__) . '/tableWrapperClass.php';
	
	$oTblWr = new BAB_TableWrapper(BAB_ARTICLES_IMAGES_TBL);
	
	$aAttribut = array(
		'id'			=> $iIdImage,
		'idArticle'		=> $iIdArticle,
		'name'			=> $sName,
		'relativePath'	=> $sRelativePath
	);
	
	return $oTblWr->update($aAttribut);
}

/**
 * Get a record from the database
 * For now only one image can be attached
 *
 * @param int		$iIdArticle		Article identifier
 * @return Array|bool	Array on success, false otherwise
 */
function bab_getImageArticle($iIdArticle)
{
	require_once dirname(__FILE__) . '/tableWrapperClass.php';
	
	$oTblWr = new BAB_TableWrapper(BAB_ARTICLES_IMAGES_TBL);
	
	$aAttribut = array(
		'idArticle'		=> $iIdArticle,
		'id'			=> -1,
		'name'			=> '',
		'relativePath'	=> ''
	);
	
	return $oTblWr->load($aAttribut, 1, 3, 0, 1);
}

/**
 * Delete a record
 * For now only one image can be attached 
 *
 * @param int $iIdCategory
 * @return bool
 */
function bab_deleteImageArticle($iIdArticle)
{
	require_once dirname(__FILE__) . '/tableWrapperClass.php';
	
	$oTblWr = new BAB_TableWrapper(BAB_ARTICLES_IMAGES_TBL);
	
	$aAttribut = array(
		'idArticle' => $iIdArticle
	);
	
	return $oTblWr->delete($aAttribut);
}









/**
 * Test if an article draft is modifiable
 * @param int $iddraft
 * @return bool
 */
function bab_isDraftModifiable($iddraft)
{
	require_once dirname(__FILE__).'/artdraft.class.php';
	$draft = new bab_ArtDraft;
	$draft->getFromIdDraft($iddraft);
	return $draft->isModifiable();
}


/**
 * Test if an article is modifiable by the current user
 * @param int $id_article
 * @return bool
 */
function bab_isArticleModifiable($id_article)
{
	global $babDB;
	
	$res = $babDB->db_query('SELECT 
		a.id_topic,
		t.allow_update, 
		a.id_author, 
		t.allow_manupdate   
		FROM bab_topics t, bab_articles a 
		WHERE a.id_topic=t.id AND a.id='.$babDB->quote($id_article));
	$arr = $babDB->db_fetch_assoc($res);
	
	if (!$arr)
	{
		return false;
	}
	
	$user_id = bab_getUserId();
	
	
	if( bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $arr['id_topic']) || ( $arr['allow_update'] != '0' && $arr['id_author'] == $user_id) || ( $arr['allow_manupdate'] != '0' && bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $arr['id_topic'])))
	{
		return true;
	}
	
	return false;
}




/**
 * Return -1 if unsubscription not allowed in topic or notification disabled or not accessible in topic or user logged out
 * Return 0 if user unsubscribed and notifications activated in topic and unsubscription allowed in topic
 * Return 1 if user subscribed and notifications activated in topic and unsubscription allowed in topic
 * 
 * 
 * @param	int		$id_topic
 * @param	int		$id_user
 * @param	bool	$set		Set subscription status of user to topic
 * 								true : subscribe to notifications (remove table row)
 * 								false : unsubscribe to notifications (add row in table)
 * 
 * @return int
 */
function bab_TopicNotificationSubscription($id_topic, $id_user, $set = null)
{
	global $babDB;
	
	if (!$GLOBALS['BAB_SESS_LOGGED'])
	{
		return -1;
	}
	
	$res = $babDB->db_query("SELECT 
			t.notify, 
			t.allow_unsubscribe, 
			u.id_user   
		FROM bab_topics t LEFT JOIN bab_topics_unsubscribe u ON u.id_topic=t.id AND u.id_user=".$babDB->quote($id_user)." 
		WHERE 
			t.id=".$babDB->quote($id_topic)
	);
	
	$arr = $babDB->db_fetch_assoc($res);
	
	if ('N' === $arr['notify'])
	{
		return -1;
	}
	
	if (0 === (int) $arr['allow_unsubscribe'])
	{
		return -1;
	}
	
	if (!bab_isAccessValidByUser(BAB_TOPICSVIEW_GROUPS_TBL, $id_topic, $id_user))
	{
		return -1;
	}
	
	
	if (null !== $set)
	{
		if (false === $set)
		{
			bab_debug('unsubscribe');
			
			$babDB->db_query("INSERT INTO bab_topics_unsubscribe (id_topic, id_user) 
				VALUES (".$babDB->quote($id_topic).", ".$babDB->quote($id_user).")");
			
			return 1;
			
		} else {
			bab_debug('subscribe');
			$babDB->db_queryWem("DELETE FROM bab_topics_unsubscribe WHERE id_topic=".$babDB->quote($id_topic)." AND id_user=".$babDB->quote($id_user));
			
			return 0;
		}
	}
	
	return (null === $arr['id_user']) ? 1 : 0;
}


