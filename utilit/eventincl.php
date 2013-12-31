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



class bab_event {

	/**
	 * Can be set in the callback function to stop the processing done by bab_fireEvent
	 * @var bool
	 */
	public $stop_propagation = false;
	
}

/**
 * Add event listener
 * Once the listener is added, the function $function_name will be fired if bab_fireEvent is called with an event
 * inherited or instancied from the class $event_class_name
 *
 * The function return false if the event listener is already created
 *
 * @param	string	$event_class_name
 * @param	string	$function_name			function name without (), if the function_name string contain a ->, the text before -> will be evaluated to get an object and the text after will be the method (not evaluated)
 * @param	string	$require_file			file path relative to ovidentia core, the file where $function_name is declared, this can be an empty string if function exists in global scope
 * @param	string	[$addon_name]			if addon name is set, additional tests will be verified on access rights before the call, and environement varaibles of addon will be set correctly
 * @param	int		[$priority]				for mutiple calls on one event, the calls will be ordered by priority descending
 *
 * @return boolean
 */
function bab_addEventListener($event_class_name, $function_name, $require_file, $addon_name = BAB_ADDON_CORE_NAME, $priority = 0) {

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
			addon_name,
			priority
			)
		VALUES
			(
			'.$babDB->quote($event_class_name).',
			'.$babDB->quote($function_name).',
			'.$babDB->quote($require_file).',
			'.$babDB->quote($addon_name).',
			'.$babDB->quote($priority).'
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




function bab_fireEvent_addonCtxStack($arr = null) {
	static $stack = array();
	if (null === $arr) {
		return array_pop($stack);
	}

	array_push($stack, $arr);
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

		if (BAB_ADDON_CORE_NAME == $addon_name) {
			bab_fireEvent_addonCtxStack(array(null, BAB_ADDON_CORE_NAME));
			return;
		}

		include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';

		if (!empty($GLOBALS['babAddonFolder'])) {
			$arr = array();
			$arr[1] = $GLOBALS['babAddonFolder'];
			$tmp = explode('/',$GLOBALS['babAddonTarget']);
			$arr[0] = $tmp[1];

			bab_fireEvent_addonCtxStack($arr);
		}

		bab_setAddonGlobals($addon_id);
	}


	function restoreAddonCtx() {
		include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';

		$arr = bab_fireEvent_addonCtxStack();
		if (null === $arr) {
			bab_setAddonGlobals(null);
			return;
		}

		list($old_addon_id, $old_addon_name) = $arr;
		if ($old_addon_id)
		{
			bab_setAddonGlobals($old_addon_id);
		}
	}
}


/**
 * Fire all event registered as listeners
 * @see	bab_addEventListener
 * @param	bab_Event	$event_obj
 */
function bab_fireEvent(bab_Event $event_obj) {
	//echo "<script type=\"text/javascript\">alert(\"" . get_class($event_obj) . "\")</script>";
	//static $arrayEvent = array();
	//$arrayEvent[] = get_class($event_obj);
	//bab_debug($arrayEvent);
	include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';

	global $babDB;

	$obj = new bab_fireEvent_Obj;
	$obj->push_obj($event_obj);
	$classkey = get_class($event_obj);

	static $calls = array();
	static $unused = array();

	if (!isset($calls[$classkey])) {
		$calls[$classkey] = array();

		while($class_name = $obj->pop_className()) {

			if (isset($unused[$class_name])) {
				continue;
			}

			$res = $babDB->db_query('
				SELECT
					l.* ,
					a.id id_addon
				FROM
					'.BAB_EVENT_LISTENERS_TBL.' l
					LEFT JOIN '.BAB_ADDONS_TBL.' a ON a.title = l.addon_name
				WHERE
					l.event_class_name ='.$babDB->quote($class_name).'
				ORDER BY l.priority DESC'
			);

			if (0 < $babDB->db_num_rows($res)) {
				while ($arr = $babDB->db_fetch_assoc($res)) {

					$id_addon = $arr['id_addon'];
					if (BAB_ADDON_CORE_NAME === $arr['addon_name'] ||
					bab_isAddonAccessValid($id_addon)) {


						if (empty($arr['require_file']) || is_file($GLOBALS['babInstallPath'].$arr['require_file'])) {

							$calls[$classkey][] = array(
								'require_file' => $arr['require_file'],
								'function_name' => $arr['function_name'],
								'addon_name' => $arr['addon_name'],
								'addon_id' => $id_addon
							);

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
						"\nFor registered event : ".$arr['event_class_name'].
						"\nthe registration has been removed automatically"
						);

						bab_removeEventListener(
							$arr['event_class_name'],
							$arr['function_name'],
							$arr['require_file']
						);
					}
				}
			} else {

				$unused[$class_name] = 1;
			}
		}
		
		
	}

	
	

	foreach($calls[$classkey] as $arr) {
		
		if ($event_obj->stop_propagation)
		{
			break;
		}
		
		
		$obj->setAddonCtx($arr['addon_id'], $arr['addon_name']);

		if (!empty($arr['require_file'])) {
			require_once $GLOBALS['babInstallPath'].$arr['require_file'];
		}
		
		

		if (function_exists($arr['function_name'])) {
			call_user_func_array($arr['function_name'], array(&$event_obj));
		} else {

			if ($pos = strrpos($arr['function_name'], '->')) {
				$method = mb_substr($arr['function_name'], 2 + $pos);
				$evalstr = 'return '.mb_substr($arr['function_name'], 0, $pos).';';

				// the object part need evaluation

				$object = eval($evalstr);
				if (is_object($object)) {
					call_user_func_array(array($object, $method), array(&$event_obj));
				} else {
					bab_debug('
					Object evaluation failed
					event : '.get_class($event_obj).'
					file : '.$arr['require_file'].'
					eval string : '.$evalstr.'
					');
				}

			} else {

				bab_debug('
				Function unreachable
				event : '.get_class($event_obj).'
				file : '.$arr['require_file'].'
				function : '.$arr['function_name'].'
				');

			}
		}

		$obj->restoreAddonCtx();
	}
}

