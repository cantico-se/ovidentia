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
 * Get Faqs as mysql ressource or false if no accessible faq
 * @param	false|array	$faqid			: array of id or false for all accessible faq
 * @param	false|int	$delegationid	: if delegationid is false, faq are not filtered
 * @return 	ressource|false
 */
function bab_getFaqRes($faqid, $delegationid) {

	global $babDB;
	
	$req = "select id from ".BAB_FAQCAT_TBL;
	
	if( false !== $faqid ) {
		$req .= " where id IN (".$babDB->quote(explode(',', $faqid)).")";
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