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




/* CATEGORIES API */




/**
 * @deprecated
 * @see	bab_getTopicTitle
 */
function bab_getCategoryTitle($id)
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
 * @deprecated
 * @see	bab_getTopicDescription
 */	
function bab_getCategoryDescription($id)
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
	

function bab_addTopicsCategory($name, $description, $benabled, $template, $disptmpl, $topcatid, $dgowner=0)
	{
	global $babBody, $babDB;
	if( empty($name))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
		}

	$res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where title='".$babDB->db_escape_string($name)."' and id_parent='".$babDB->db_escape_string($topcatid)."' and id_dgowner='".$babDB->db_escape_string($dgowner)."'");
	if( $babDB->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("This topic category already exists");
		return false;
		}
	else
		{
		$req = "insert into ".BAB_TOPICS_CATEGORIES_TBL." (title, description, enabled, template, id_dgowner, id_parent, display_tmpl) VALUES (
		'" .$babDB->db_escape_string($name). "',
		'" . $babDB->db_escape_string($description). "',
		'" . $babDB->db_escape_string($benabled). "', 
		'" . $babDB->db_escape_string($template). "',
		'" . $babDB->db_escape_string($dgowner). "', 
		'" . $babDB->db_escape_string($topcatid). "', 
		'" . $babDB->db_escape_string($disptmpl). "'
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
 * Get first children articles categories (in db_query ressource )
 *
 * @param	array		$parentid		: list of id of the parent category
 * @param	int|false	$delegationid	: if delegationid is false, categories are not filtered
 * @param   string|false   $rightaccesstable    : name of the right access table in topic. If false, categories are not filtered by user's rights 
 * 
 * Values of $rightsaccesstable :
 * 	false : administrator access (the user must be an administrator)
 *  BAB_TOPICSCOM_GROUPS_TBL : right submit comments
 *	BAB_TOPICSMAN_GROUPS_TBL : right manage topic
 *	BAB_TOPICSMOD_GROUPS_TBL : right modify articles
 *	BAB_TOPICSSUB_GROUPS_TBL : right submit articles
 *	BAB_TOPICSVIEW_GROUPS_TBL : right view articles (value by default)
 *
 * @return 	ressource|false : first childs of $parentid
 */
function bab_getArticleCategoriesRes($parentid, $delegationid = false, $rightaccesstable = BAB_TOPICSVIEW_GROUPS_TBL) {
	global $babBody, $babDB;
	
	if (false === $rightaccesstable) {
		if (!bab_isUserAdministrator()) {
			return false;
		}
	}

	$sDelegation = ' ';
	if(false !== $delegationid) {
		$sDelegation = ' AND id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
	}
	
	/* List of id categories */
	$IdEntries = array();
	if( count($parentid) > 0 ) {
		/* All categories, childs of $parentid */
		$res = $babDB->db_query("select id from ".BAB_TOPICS_CATEGORIES_TBL." where id_parent IN (".$babDB->quote($parentid).")");
		/* Specifics rights or all rights ? */
		if (false === $rightaccesstable) {
			/* Administrator rights */
			if ($babDB->db_num_rows($res) > 0) {
				while ($row = $babDB->db_fetch_array($res)) {
					if (!in_array($row['id'], $IdEntries)) {
						array_push($IdEntries, $row['id']);
					}
				}
			}
		} else {
			/* Specific right */
			$idtopicsbyrights = bab_getUserIdObjects($rightaccesstable); /* all id topics with right */
			
			$idcategoriesbyrights = array();
			
			/* All id categories, parents of topics with right */
			$res2 = $babDB->db_query("select id_cat from ".BAB_TOPICS_TBL." where id in(".$babDB->quote(array_keys($idtopicsbyrights)).")");
			if ($babDB->db_num_rows($res2) > 0) {
				while ($row2 = $babDB->db_fetch_array($res2)) {
					if (!in_array($row2['id_cat'], $idcategoriesbyrights)) {
						$idcategoriesbyrights[$row2['id_cat']] = $row2['id_cat'];
					}
				}
			}
			
			if ($babDB->db_num_rows($res) > 0) {
				while ($row = $babDB->db_fetch_array($res)) {
					if (isset($idcategoriesbyrights[$row['id']]) ) {
						if (!in_array($row['id'], $IdEntries)) {
							array_push($IdEntries, $row['id']);
						}
					}
				}
			}
		}
	}

	/* All fields and values of categories */
	if($IdEntries) {
		$req = "SELECT tc.* from ".BAB_TOPICS_CATEGORIES_TBL." tc 
			LEFT JOIN ".BAB_TOPCAT_ORDER_TBL." tot on tc.id=tot.id_topcat 
			WHERE tc.id IN (".$babDB->quote($IdEntries).") and tot.type='1' " . $sDelegation .  " order by tot.ordering asc";
		
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
 *   	false : administrator access (the user must be an administrator)
 *	BAB_TOPICSCOM_GROUPS_TBL : right submit comments
 *	BAB_TOPICSMAN_GROUPS_TBL : right manage topic
 *	BAB_TOPICSMOD_GROUPS_TBL : right modify articles
 *	BAB_TOPICSSUB_GROUPS_TBL : right submit articles
 *	BAB_TOPICSVIEW_GROUPS_TBL : right view articles (value by default)
 *
 * @return 	array : array indexed by id categories, categories are childs of $parentid
 */
function bab_getChildrenArticleCategoriesInformation($parentid, $delegationid = false, $rightaccesstable = BAB_TOPICSVIEW_GROUPS_TBL) {
	global $babBody, $babDB;
	
	$categories = array();
	
	if (false === $rightaccesstable) {
		if (!bab_isUserAdministrator()) {
			return $categories;
		}
	}
	
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
	
	$categories = array();
	
	if (!is_numeric($categoryid)) {
		return $categories;
	}
	
	/* List of all categories */
	$topcats = $babBody->get_topcats();
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
		return bab_getCategoryTitle($id);
	}

function bab_getTopicDescription($id)
	{
		return bab_getCategoryDescription($id);
	}


function bab_addTopic($name, $description, $idCategory, &$error, $topicArr = array())
{
	global $babBody, $babDB;
	$arrdefaults = array(	'idsaart'=> 0, 
							'idsacom'=> 0, 
							'idsa_update'=> 0, 
							'notify'=> 'N', 
							'lang'=>$GLOBALS['babLanguage'], 
							'article_tmpl'=>'', 
							'display_tmpl'=>'', 
							'restrict_access'=>'N', 
							'allow_hpages'=>'N',
							'allow_pubdates'=>'N',
							'allow_attachments'=>'N',
							'allow_update'=>0,
							'allow_manupdate'=>0,
							'max_articles'=>10,
							'auto_approbation'=>'N',
							'busetags'=>'N',
							'allow_addImg'=>'N'
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
	
	$babDB->db_query("insert into ".BAB_TOPICS_TBL." (".implode(',', array_keys($arrdefaults)).") values (".$babDB->quote($arrdefaults).")");
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
 * Get articles topics (in db_query ressource )
 * @param	array		$categoryid		: list of articles categories
 * @param	int|false	$delegationid	: if delegationid is false, topics are not filtered
 * @param   string|false   $rightaccesstable    : name of the right access table in topic. If false, topics are not filtered by user's rights 
 * 
 * Values of $rightsaccesstable :
 * 	false : administrator access (the user must be an administrator)
 *	BAB_TOPICSCOM_GROUPS_TBL : right submit comments
 *	BAB_TOPICSMAN_GROUPS_TBL : right manage topic
 *	BAB_TOPICSMOD_GROUPS_TBL : right modify articles
 *	BAB_TOPICSSUB_GROUPS_TBL : right submit articles
 *	BAB_TOPICSVIEW_GROUPS_TBL : right view articles (value by default)
 * 
 * @return 	ressource|false
 */
function bab_getArticleTopicsRes($categoryid, $delegationid = false, $rightaccesstable = BAB_TOPICSVIEW_GROUPS_TBL) {
	global $babBody, $babDB;
	
	if (false === $rightaccesstable) {
		if (!bab_isUserAdministrator()) {
			return false;
		}
	}
	
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
				$idtopicsbyrights = bab_getUserIdObjects($rightaccesstable); /* all id topics with right */
				if (isset($idtopicsbyrights[$row['id_topcat']])) {
					array_push($IdEntries, $row['id_topcat']);
				}
			}
		}
	}

	if (count($IdEntries) > 0) {
		$req = "select tc.* from ".BAB_TOPICS_TBL." tc left join ".BAB_TOPCAT_ORDER_TBL." tot on tc.id=tot.id_topcat where tc.id IN (".$babDB->quote($IdEntries).") and tot.type='2' order by tot.ordering asc";
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
 * 	false : administrator access (the user must be an administrator)
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
	
	if (false === $rightaccesstable) {
		if (!bab_isUserAdministrator()) {
			return $topics;
		}
	}
	
	$res = bab_getArticleTopicsRes($categoryid, $delegationid, $rightaccesstable);
	if ($babDB->db_num_rows($res) > 0) {
		while ($row = $babDB->db_fetch_array($res)) {
			$topics[$row['id']] = array('id' => $row['id'], 'title' => $row['category'], 'description' => $row['description']);
		}
	}
	
	return $topics;
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

// used in add-ons since 4.09
function bab_getArticleArray($article,$fullpath = false)
	{
	global $babDB;
	$query = "select a.*,t.category topic from ".BAB_ARTICLES_TBL." a,".BAB_TOPICS_TBL." t where a.id='".$babDB->db_escape_string($article)."' AND t.id=a.id_topic";
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
 * 	false : all rights (the user must be an administrator)
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
	
	if (false === $rightaccesstable) {
		if (!bab_isUserAdministrator()) {
			return $articles;
		}
	}
	
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
	
function bab_submitArticleDraft($idart)
{
	
	global $babBody, $babDB, $BAB_SESS_USERID;
	$res = $babDB->db_query("select id_article, id_topic, id_author, approbation from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arr = $babDB->db_fetch_array($res);

		if( $arr['id_topic'] == 0 )
			{
			return false;
			}

		if( $arr['id_article'] != 0 )
			{
			$babDB->db_query("insert into ".BAB_ART_LOG_TBL." (id_article, id_author, date_log, action_log) values ('".$arr['id_article']."', '".$babDB->db_escape_string($arr['id_author'])."', now(), 'commit')");	
			
			$res = $babDB->db_query("select at.id_topic, at.id_author, tt.allow_update, tt.allow_manupdate, tt.idsa_update as saupdate, tt.auto_approbation from ".BAB_ARTICLES_TBL." at left join ".BAB_TOPICS_TBL." tt on at.id_topic=tt.id where at.id='".$babDB->db_escape_string($arr['id_article'])."'");
			$rr = $babDB->db_fetch_array($res);
			if( $rr['saupdate'] != 0 && ( $rr['allow_update'] == '2' && $rr['id_author'] == $GLOBALS['BAB_SESS_USERID']) || ( $rr['allow_manupdate'] == '2' && bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $rr['id_topic'])))
				{
				if( $arr['approbation'] == '2' )
					{
					$rr['saupdate'] = 0;
					}
				}
			}
		else
			{
			$res = $babDB->db_query("select tt.idsaart as saupdate, tt.auto_approbation from ".BAB_TOPICS_TBL." tt where tt.id='".$babDB->db_escape_string($arr['id_topic'])."'");
			$rr = $babDB->db_fetch_array($res);
			}

		if( $rr['saupdate'] !=  0 )
			{
			include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
			if( $rr['auto_approbation'] == 'Y' )
				{
				$idfai = makeFlowInstance($rr['saupdate'], "draft-".$idart, $GLOBALS['BAB_SESS_USERID']); // Auto approbation
				}
			else
				{
				$idfai = makeFlowInstance($rr['saupdate'], "draft-".$idart);
				}
			}

		if( $rr['saupdate'] ==  0 || $idfai === true)
			{
			if( $arr['id_article'] != 0 )
				{
				$babDB->db_query("insert into ".BAB_ART_LOG_TBL." (id_article, id_author, date_log, action_log) values ('".$babDB->db_escape_string($arr['id_article'])."', '".$arr['id_author']."', now(), 'accepted')");		
				}

			$articleid = acceptWaitingArticle($idart);
			if( $articleid == 0)
				{
				return false;
				}
			bab_deleteArticleDraft($idart);
			}
		else
			{
			if( !empty($idfai))
				{
				$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set result='".BAB_ART_STATUS_WAIT."' , idfai='".$idfai."', date_submission=now() where id='".$babDB->db_escape_string($idart)."'");
				$nfusers = getWaitingApproversFlowInstance($idfai, true);
				notifyArticleDraftApprovers($idart, $nfusers);
				}
			}		
		}
	return true;
}

function bab_addArticleDraft( $title, $head, $body, $idTopic, &$error, $articleArr=array())
{
	global $babBody, $babDB;
	$arrdefaults = array(	'id_author'=>$GLOBALS['BAB_SESS_USERID'],
							'lang'=>$GLOBALS['babLanguage'], 
							'date_submission'=> '0000-00-00 00:00:00', 
							'date_archiving'=> '0000-00-00 00:00:00', 
							'date_publication'=> '0000-00-00 00:00:00', 
							'hpage_private'=> 'N', 
							'hpage_public'=> 'N', 
							'notify_members'=> 'N', 
							'update_datemodif'=> 'N',
							'restriction'=>''
						);
							
	if( empty($title))
	{
		$error = bab_translate("The title of the article should not be empty");
		return 0;
	}

	if(!empty($idTopic))
	{
		$res = $babDB->db_query("select id from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($idTopic)."'");
		if( !$res || $babDB->db_num_rows($res) == 0)
		{
			$error = bab_translate("Unknown topic");
			return 0;
		}
	}
	
	foreach($arrdefaults as $k=>$v)
	{
		if( isset($articleArr[$k]))
		{
			$arrdefaults[$k]=$articleArr[$k];
		}
	}
	
	if( !bab_isAccessValidByUser(BAB_TOPICSSUB_GROUPS_TBL, $idTopic, $arrdefaults['id_author'])  && !bab_isAccessValidByUser(BAB_TOPICSMOD_GROUPS_TBL, $idTopic, $arrdefaults['id_author']))
	{
		$error = bab_translate("Access denied");
		return 0;
	}
	
	if( empty($arrdefaults['id_author']) )
		{
		$res = $babDB->db_query("select id from ".BAB_USERS_LOG_TBL." where sessid='".session_id()."' and id_user='0'");
		if( $res && $babDB->db_num_rows($res) == 1 )
			{
			$arr = $babDB->db_fetch_array($res);
			$idanonymous = $arr['id'];
			}
		else
			{
			return 0;
			}
		}
	else
		{
		$idanonymous = 0;
		}
			
	$arrdefaults['title'] = $title;
	$arrdefaults['body'] = $body;
	$arrdefaults['head'] = $head;
	$arrdefaults['id_topic'] = $idTopic;	
	$arrdefaults['id_anonymous'] = $idanonymous;	
	
	$babDB->db_query("insert into ".BAB_ART_DRAFTS_TBL." (".implode(',', array_keys($arrdefaults)).") values (".$babDB->quote($arrdefaults).")");
	$iddraft = $babDB->db_insert_id();
	$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set date_creation=now(), date_modification=now() where id='".$iddraft."'");
	return $iddraft;
}

function bab_addArticle( $title, $head, $body, $idTopic, &$error, $articleArr=array())
{
	$iddraft = bab_addArticleDraft($title, $head, $body, $idTopic, $error, $articleArr);
	if( $iddraft)
	{
		return bab_submitArticleDraft($iddraft);
	}
	else
	{
		return false;
	}
}



/* COMMENTS API */	
	


	
function bab_getCommentTitle($com)
	{
	global $babDB;
	$query = "select subject from ".BAB_COMMENTS_TBL." where id='".$babDB->db_escape_string($com)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['subject'];
		}
	else
		{
		return "";
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
 * @param int		$topicId
 * @param int		$articleId
 * @param string	$subject
 * @param string	$message
 * @param int		$parentId
 * @param int		$commentId
 */
function bab_saveArticleComment($topicId, $articleId, $subject, $message, $parentId = 0, $commentId = null)
{
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
		 				last_update = NOW(),
		 				subject = ' . $babDB->quote($subject) . ',
		 				message = ' . $babDB->quote($message) . ')
		 		WHERE id = ' . $babDB->quote($commentId);		
		$babDB->db_query($req);
	} else {
		$req = 'INSERT INTO '.BAB_COMMENTS_TBL.' (id_topic, id_article, id_parent, date, subject, message, id_author, name, email)
				VALUES (' . $babDB->quote($topicId). ', ' . $babDB->quote($articleId). ', ' . $babDB->quote($parentId). ', NOW(), ' . $babDB->quote($subject) . ', ' . $babDB->quote($message). ', ' . $babDB->quote($authorId) . ', ' . $babDB->quote($authorName). ', ' . $babDB->quote($authorEmail). ')';
	
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
	return true;
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
		'id'			=> '',
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
		'id'			=> '',
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

