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


require_once dirname(__FILE__).'/cal.calendarperiod.class.php';
require_once dirname(__FILE__).'/cal.userperiods.class.php';


function bab_insertWorkingHours($iIdUser, $iWeekDay, $sStartHour, $sEndHour)
{
	global $babDB;

	$query = 
		'INSERT INTO ' . BAB_WORKING_HOURS_TBL . ' ' .
			'(`weekDay`, `idUser`, `startHour`, `endHour`) ' .
		'VALUES ' . 
			'(' . $babDB->quote($iWeekDay) . ', ' . $babDB->quote($iIdUser) . ', ' . $babDB->quote($sStartHour) . ', ' . $babDB->quote($sEndHour) . ')'; 

	//bab_debug($query);
	
	$res = $babDB->db_query($query);
	if(false != $res)
	{
		return $babDB->db_insert_id();
	}
	return false;
}

function bab_deleteAllWorkingHours($iIdUser)
{
	global $babDB;
	$query = 'DELETE FROM '	. BAB_WORKING_HOURS_TBL . ' WHERE idUser = \'' . $iIdUser . '\'';
	$babDB->db_query($query);
}





function bab_createDefaultWorkingHours($iIdUser)
{
	require_once($GLOBALS['babInstallPath']. 'utilit/calapi.php');

	$sWorkingDays = null;
	bab_calGetWorkingDays($iIdUser, $sWorkingDays);
	$aWorkingDays = explode(',', $sWorkingDays);
	
	foreach($aWorkingDays as $key => $iWeekDay)
	{
		bab_insertWorkingHours($iIdUser, $iWeekDay, '09:00', '12:00');
		bab_insertWorkingHours($iIdUser, $iWeekDay, '13:00', '18:00');
	}
}



/**
 * Get working hours parameters for user and weekday
 * 
 * @see Func_WorkingHours	Use functionality to get working hours
 * 
 * @param int $id_user
 * @param int $weekday
 */
function bab_getWHours($id_user, $weekday, $db_id_user = NULL) {

	static $result = array();
	
	global $babBody;
	
	if (0 != $id_user && isset($babBody->babsite['user_workdays']) && 'N' === $babBody->babsite['user_workdays']) {
		$id_user = 0;
	}
	
	
	if (isset($result[$id_user.','.$weekday])) {
		return $result[$id_user.','.$weekday];
	}

	

	if (NULL === $db_id_user) {
		$db_id_user = $id_user;
	}
	
	$db = $GLOBALS['babDB'];

	$res = $db->db_query("
		SELECT  
			weekDay,  
			startHour, 
			endHour 
		FROM ".BAB_WORKING_HOURS_TBL." WHERE 
			idUser =".$db->quote($db_id_user)." "
		);

	
	if (0 == $db->db_num_rows($res) && 0 != $id_user) {
		return bab_getWHours($id_user, $weekday, 0);
	}

	for ($i = 0; $i < 7; $i++) {
		if (!isset($result[$id_user.','.$i])) {
			$result[$id_user.','.$i] = array();
		}
	}
	
	while ($arr = $db->db_fetch_assoc($res)) {
		$result[$id_user.','.$arr['weekDay']][] = $arr;
	}
	

	return $result[$id_user.','.$weekday];
}


/**
 * A working period
 */
class bab_WorkingPeriod {
	/**
	 * 
	 * @var string
	 */
	public $begin;
	
	/**
	 * @var string
	 */
	public $end;
	
	/**
	 * 
	 * @param string $begin		ISO datetime
	 * @param string $end		ISO datetime
	 */
	public function __construct($begin, $end)
	{
		$this->begin = $begin;
		$this->end = $end;
	}

}


/**
 * Interface to the working hours functionality
 *
 */
class Func_WorkingHours extends bab_functionality
{
	public function getDescription()
	{
		return bab_translate('Get the working hours configuration');
	}
	
		
	/**
	 * Get the working periods between two date
	 * @param int			$id_user
	 * @param BAB_DateTime 	$begin
	 * @param BAB_DateTime 	$end
	 * 
	 * @return array <bab_WorkingPeriod>
	 *
	 */
	public function selectPeriods($id_user, BAB_DateTime $begin, BAB_DateTime $end)
	{
		throw new Exception('Not implemented');
	}
	
	
	/**
	 * Test if the functionality has default settings (always return periods)
	 * @throws Exception
	 * 
	 * @return bool
	 */
	public function hasDefaultSettings()
	{
		throw new Exception('Not implemented');
	}
	
	/**
	 * Test if the functionality has settings for the user
	 * @param int $id_user
	 * @throws Exception
	 * 
	 * @return bool
	 */
	public function hasUserSettings($id_user)
	{
		throw new Exception('Not implemented');
	}
	
	
	/**
	 * Get url for user setting in a popup
	 * return null if popup not available
	 * 
	 * @param int $id_user
	 * @return string | null
	 */
	public function getUserSettingsPopupUrl($id_user)
	{
		return null;
	}
}


class Func_WorkingHours_Ovidentia extends Func_WorkingHours 
{
	public function getDescription()
	{
		return bab_translate('Users options or site configuration');
	}
	
	
	/**
	 * Get the working periods between two date
	 * @param BAB_DateTime $begin
	 * @param BAB_DateTime $end
	 *
	 * @return array <bab_WorkingPeriod>
	 *
	 */
	public function selectPeriods($id_user, BAB_DateTime $begin, BAB_DateTime $end)
	{
		$arr = array();
		$loop = clone $begin;
		
		$limit = clone $end;
		$limit->add(1, BAB_DATETIME_DAY);
		
		/*@var $loop BAB_DateTime */
		do {
			$ts = $loop->getTimeStamp();
			foreach(bab_getWHours($id_user, $loop->getDayOfWeek()) as $wh) {
				$date = date('Y-m-d', $ts);
				
				if ($date.' '.$wh['startHour'] < $end->getIsoDateTime())
				{
					$arr[] = new bab_WorkingPeriod(
						$date.' '.$wh['startHour'],
						$date.' '.$wh['endHour']
					);
				}
			}
			
			$loop->add(1, BAB_DATETIME_DAY);

		} while ($loop->getTimeStamp() < $limit->getTimeStamp());
		
		return $arr;
	}
	
	
	/**
	 * Test if the functionality has default settings (always return periods)
	 * 
	 * @return bool
	 */
	public function hasDefaultSettings()
	{
		return true;
	}
	
	/**
	 * Test if the functionality has settings for the user
	 * @param int $id_user
	 * @throws Exception
	 *
	 * @return bool
	 */
	public function hasUserSettings($id_user)
	{
		global $babDB;
		
		$res = $babDB->db_query("
				SELECT * 
				FROM ".BAB_WORKING_HOURS_TBL." WHERE
					idUser =".$babDB->quote($id_user)." "
		);
		
		return ($babDB->db_num_rows($res) > 0);
	}
}