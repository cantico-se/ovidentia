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
 * Add a fixed vacation for a user
 * appelle lors de la creation/modification d'un droit de conge pre attribue
 * 
 * @param int $id_user
 * @param int $id_right
 * @param string $datebegin			ISO datetime
 * @param string $dateend			ISO datetime
 * @param string $remarks
 * @param float $total			 	decimal(4,2)
 */
function addFixedVacation($id_user, $id_right, $datebegin , $dateend, $remarks, $total)
{
	global $babDB;
	require_once dirname(__FILE__).'/userinfosincl.php';
	
	$creationdate = bab_userInfos::getCreationDate($id_user);
	
	if ($creationdate >= $dateend)
	{
		// ignore fixed vacation because the user does not exists at that time
		return;
	}
	


	$babDB->db_query("insert into ".BAB_VAC_ENTRIES_TBL." 
	(id_user, date_begin, date_end, comment, date, idfai, status) 
		values  
			(
				".$babDB->quote($id_user).", 
				".$babDB->quote($datebegin).", 
				".$babDB->quote($dateend).", 
				".$babDB->quote($remarks).", 
				curdate(), 
				'0', 
				'Y'
			)
		");

	$identry = $babDB->db_insert_id();

	$babDB->db_query("INSERT INTO ".BAB_VAC_ENTRIES_ELEM_TBL." 
		(id_entry, id_right, quantity) 
		values  
			(
				" .$babDB->quote($identry). ",
				" .$babDB->quote($id_right). ",
				" .$babDB->quote($total). "
			)
		");

	bab_vac_updateEventCalendar($identry);
	bab_vac_createPeriod($identry);
}



/**
 * Update dates and quantity of a fixed vacation for a user
 * appelle lors de la creation/modification d'un droit de conge pre attribue
 * 
 * @param	int		$id_user
 * @param 	int		$id_right
 * @param	string	$datebegin		ISO datetime
 * @param	string	$dateend		ISO datetime
 * @param	float	$total			decimal(4,2)
 * 
 * @return bool
 */
function updateFixedVacation($id_user, $id_right, $datebegin , $dateend, $total)
{
	global $babDB;
	require_once dirname(__FILE__).'/userinfosincl.php';
	
	$creationdate = bab_userInfos::getCreationDate($id_user);

	$res = $babDB->db_query("select 
		vet.id as entry, 
		vet.date_begin, 
		vet.date_end,
		veet.id as entryelem 
	from ".BAB_VAC_ENTRIES_ELEM_TBL." veet 
		left join ".BAB_VAC_ENTRIES_TBL." vet 
		on veet.id_entry=vet.id 
		where veet.id_right=".$babDB->quote($id_right)." 
			and vet.id_user=".$babDB->quote($id_user)."
	");

	if (0 === $babDB->db_num_rows($res)) {
		return false;
	}


	while( $arr = $babDB->db_fetch_array($res))
	{
		if ($creationdate >= $dateend)
		{
			removeFixedVacation($arr['entry']);
			
		} else {
		
		
			$babDB->db_query("
			UPDATE ".BAB_VAC_ENTRIES_TBL." 
				SET 
				date_begin	=".$babDB->quote($datebegin).", 
				date_end	=".$babDB->quote($dateend)." 
				
			WHERE 
				id=".$babDB->quote($arr['entry'])."
			");
	
			$babDB->db_query("update ".BAB_VAC_ENTRIES_ELEM_TBL." 
					set 
					quantity=".$babDB->quote($total)." 
				where 
					id=".$babDB->quote($arr['entryelem'])
			);
	
			bab_vac_updateEventCalendar($arr['entry']);
		
		}
	
	
		$begin = BAB_DateTime::fromIsoDateTime($arr['date_begin']);
		$end = BAB_DateTime::fromIsoDateTime($arr['date_end']);
	
		// try to update event copy in other backend (caldav)
		bab_vac_updatePeriod($arr['entry'], $begin, $end);
	}

	return true;
}


/**
 * Remove fixed vacation
 * @param int $id_entry
 */
function removeFixedVacation($id_entry)
{
	global $babDB;
	require_once dirname(__FILE__).'/dateTime.php';
	
	$res = $babDB->db_query("select id_user, date_begin, date_end FROM ".BAB_VAC_ENTRIES_TBL." where id=".$babDB->quote($id_entry));
	$arr = $babDB->db_fetch_array($res);
	

	$babDB->db_query("delete from ".BAB_VAC_ENTRIES_TBL." where id='".$babDB->db_escape_string($id_entry)."'");
	$babDB->db_query("delete from ".BAB_VAC_ENTRIES_ELEM_TBL." where id_entry='".$babDB->db_escape_string($id_entry)."'");
	
	bab_vac_clearCalendars();
	
	// try to delete event copy in other backend (caldav)
	
	$begin = BAB_DateTime::fromIsoDateTime($arr['date_begin']);
	$end = BAB_DateTime::fromIsoDateTime($arr['date_end']);
	$period = bab_vac_getPeriod($id_entry, $arr['id_user'],  $begin, $end);
	if ($period) {
		$period->delete();
	}
}




/**
 * Update all fixed rights for one user
 * @param	int	$id_user
 */
function bab_vac_updateFixedRightsOnUser($id_user) {

	global $babDB;

	// trouver les droits fixes de l'utilisateur

	$res = $babDB->db_query('
		SELECT 
			r.id,
			r.quantity,
			r.date_begin_fixed,
			r.date_end_fixed 
		FROM 
			'.BAB_VAC_USERS_RIGHTS_TBL.' ur, 
			'.BAB_VAC_RIGHTS_TBL.' r 
		WHERE 
			r.id = ur.id_right 
			AND r.date_begin_fixed <> \'0000-00-00 00:00:00\' 
			AND ur.id_user = '.$babDB->quote($id_user).'
	');

	while ($arr = $babDB->db_fetch_assoc($res)) {
		if (false === updateFixedVacation($id_user, $arr['id'], $arr['date_begin_fixed'] , $arr['date_end_fixed'], $arr['quantity'])) {
			addFixedVacation($id_user, $arr['id'], $arr['date_begin_fixed'] ,  $arr['date_end_fixed'], '', $arr['quantity']);
		}
	}
}


