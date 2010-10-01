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

include_once 'base.php';



/**
 * Get Faqs as mysql resource or false if no accessible faq
 * @param	false|array	$faqid			: array of id or false for all accessible faq
 * @param	false|int	$delegationid	: if delegationid is false, faq are not filtered
 * @return 	resource|false
 */
function bab_getFaqRes($faqid, $delegationid) {

	global $babDB;
	
	if ($faqid !== false && !is_array($faqid) && is_numeric($faqid)) {
		$faqid = array($faqid);
	}
	
	$req = "select id from ".BAB_FAQCAT_TBL;
	
	if( false !== $faqid ) {
		$req .= " where id IN (".$babDB->quote(implode(',', $faqid)).")";
	}

	$sDelegation = ' ';	
	if(false !== $delegationid)	
	{
		$sDelegation = 'id_dgowner = \'' . $babDB->db_escape_string($delegationid) . '\' ';
		$req .= (false !== $faqid) ? (' AND ' . $sDelegation) : (' WHERE ' . $sDelegation);
	}

	$IdEntries = array();

	$res = $babDB->db_query($req);
	while( $row = $babDB->db_fetch_array($res))
		{
		if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $row['id']))
			{
			array_push($IdEntries, $row['id']);
			}
		}

	if( count($IdEntries) > 0 )
	{
		return $babDB->db_query("select * from ".BAB_FAQCAT_TBL." where id IN (".$babDB->quote($IdEntries).") order by category asc");
	}
		
	return false;
}






/**
 * Get the number of accessibles faq in one delegation
 * @param	int | false		$id_delegation		if id_delegation is false, the filter is disabled
 * @return int
 */
function bab_getFaqDgNumber($id_delegation) {
	global $babDB;
	$res = bab_getFaqRes(false, $id_delegation);

	if (false === $res) {
		return 0;
	}

	return $babDB->db_num_rows($res);
}



/**
 * @param	int		$id_question_response
 * @return 	array
 */
function bab_getFaqCategoryHierarchy($id_question_response) {
	global $babDB;

	$return = array();

	// find ID_NODE of parent sub category

	$res = $babDB->db_query('SELECT c.id_node FROM '.BAB_FAQQR_TBL.' q, '.BAB_FAQ_SUBCAT_TBL.' c WHERE q.id_subcat = c.id AND q.id='.$babDB->quote($id_question_response));
	$arr = $babDB->db_fetch_assoc($res);

	$id_node = $arr['id_node'];

	while (0 !== $id_node) {

		$res = $babDB->db_query('SELECT t.id_parent, c.id_cat, c.name FROM '.BAB_FAQ_SUBCAT_TBL.' c, '.BAB_FAQ_TREES_TBL.' t WHERE t.id = '.$babDB->quote($id_node).' AND c.id_node = t.id');
		$arr = $babDB->db_fetch_assoc($res);

		$id_node = (int) $arr['id_parent'];

		if (0 === $id_node) {
			$res = $babDB->db_query('SELECT category FROM '.BAB_FAQCAT_TBL.' WHERE id='.$babDB->quote($arr['id_cat']));
			$row = $babDB->db_fetch_assoc($res);
			$nodename = $row['category'];
		} else {
			$nodename = $arr['name'];
		}

		array_unshift($return, $nodename);
	}

	return $return;
}

