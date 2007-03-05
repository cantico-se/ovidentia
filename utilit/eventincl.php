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

define('BAB_ADDON_CORE_NAME', 'core');

class bab_event {
	
	
}

/**
 * Add event listener
 * Once the listener is added, the function $function_name will be fired if bab_fireEvent is called with an event
 * inherited or instancied from the class $event_class_name
 *
 * The function return false if the event listener is allready created
 *
 * @param	string	$event_class_name
 * @param	string	$function_name		
 * @param	string	$require_file			file path relative to ovidentia core, the file where $function_name is declared
 * @param	string	[$addon_name]
 *
 * @return boolean
 */
function bab_addEventListener($event_class_name, $function_name, $require_file, $addon_name = BAB_ADDON_CORE_NAME) {

	global $babDB;
	
	$res = $babDB->db_query('SELECT * FROM 
		'.BAB_EVENT_LISTENERS_TBL.' 
	WHERE
		event_class_name='.$babDB->quote($event_class_name).' 
		AND function_name='.$babDB->quote($function_name).' 
		AND require_file='.$babDB->quote($require_file).'
	');
	
	if (0 < $babDB->db_num_rows($res)) {
		return false;
	}
	
	$babDB->db_query('
		INSERT INTO '.BAB_EVENT_LISTENERS_TBL.' 
			(
			event_class_name, 
			function_name, 
			require_file,
			addon_name
			) 
		VALUES 
			(
			'.$babDB->quote($event_class_name).',
			'.$babDB->quote($function_name).',
			'.$babDB->quote($require_file).', 
			'.$babDB->quote($addon_name).' 
			)
	');
	
	return true;
}


/**
 * Remove event listener
 * @see		bab_addEventListener()
 * @param	string	$event_class_name
 * @param	string	$function_name		
 * @param	string	$require_file
 */
function bab_removeEventListener($event_class_name, $function_name, $require_file) {
	global $babDB;
	
	$babDB->db_query('DELETE FROM '.BAB_EVENT_LISTENERS_TBL.' WHERE 
		event_class_name 	= '.$babDB->quote($event_class_name).' 
		AND function_name 	= '.$babDB->quote($function_name).' 
		AND require_file	= '.$babDB->quote($require_file).' 
	');
}


class bab_fireEvent_Obj {

	var $stack = array();
	
	function push_className($str) {
		if ($classname = get_parent_class($str)) {
			$this->stack[] = $classname;
			$this->push_className($classname);
		}
	}
	
	function push_obj($obj) {
		$classname = get_class($obj);
		$this->stack[] = $classname;
		$this->push_className($classname);
	}
	
	function pop_className() {
		return array_shift($this->stack);
	}
	
	function setAddonCtx($addon_id, $addon_name) {
		if ((isset($GLOBALS['babAddonFolder']) && $GLOBALS['babAddonFolder'] == $addon_name) 
			|| BAB_ADDON_CORE_NAME == $addon_name) {
			return;
		}
		
		$GLOBALS['babAddonFolder'] = $addon_name;
		$GLOBALS['babAddonTarget'] = "addon/".$addon_id;
		$GLOBALS['babAddonUrl'] = $GLOBALS['babUrlScript']."?tg=addon/".$addon_id."/";
		$GLOBALS['babAddonPhpPath'] = $GLOBALS['babInstallPath']."addons/".$addon_name."/";
		$GLOBALS['babAddonHtmlPath'] = "addons/".$addon_name."/";
		$GLOBALS['babAddonUpload'] = $GLOBALS['babUploadPath']."/addons/".$addon_name."/";
	}
}


/**
 * Fire all event registered as listeners
 * @see	bab_addEventListener
 * @param	object	$event_obj (inherited object of bab_event)
 */
function bab_fireEvent(&$event_obj) {

	global $babDB, $babBody;
	
	$obj = new bab_fireEvent_Obj;
	$obj->push_obj($event_obj);

	
	
	while($class_name = $obj->pop_className()) {
	
		
	
		$res = $babDB->db_query('
			SELECT 
				l.* , 
				a.id id_addon 
			FROM 
				'.BAB_EVENT_LISTENERS_TBL.' l 
				LEFT JOIN '.BAB_ADDONS_TBL.' a ON a.title = l.addon_name 
			WHERE 
				l.event_class_name ='.$babDB->quote($class_name).' 
			ORDER BY l.addon_name'
		);
		
		while ($arr = $babDB->db_fetch_assoc($res)) {

			$id_addon = $arr['id_addon'];
		
			if (BAB_ADDON_CORE_NAME === $arr['addon_name'] || 
			(isset($babBody->babaddons[$id_addon]) && bab_isAccessValid(BAB_ADDONS_GROUPS_TBL, $id_addon))) {

				if (is_file($GLOBALS['babInstallPath'].$arr['require_file'])) {
				
					$obj->setAddonCtx($id_addon, $arr['addon_name']);
					require_once $GLOBALS['babInstallPath'].$arr['require_file'];
					if (function_exists($arr['function_name'])) {
						
						call_user_func_array($arr['function_name'], array(&$event_obj));
						
						bab_debug($event_obj);
					} else {
						bab_debug('
						Function unreachable
						event : '.get_class($event_obj).'
						file : '.$arr['require_file'].'
						function : '.$arr['function_name'].'
						');
					}
				} else {
					bab_debug('
					file unreachable
					event : '.get_class($event_obj).'
					file : '.$arr['require_file'].'
					');
				}
			}
			
			if (NULL === $id_addon && BAB_ADDON_CORE_NAME !== $arr['addon_name']) {
				bab_debug('Missing addon : '.$arr['addon_name'].
				"\nFor registered event : ".$arr['event_class_name']);
			}
		}
	}
}

?>