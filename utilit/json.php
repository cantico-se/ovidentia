<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2008 by CANTICO ({@link http://www.cantico.fr})
 */
include_once 'base.php';



/**
 * @param	mixed	$value
 * @return	string
 */
function bab_json_encode($a)
{
	if (is_null($a)) return 'null';
	if ($a === false) return 'false';
	if ($a === true) return 'true';
	if (is_scalar($a)) {
		if (is_float($a)) {
			// Always use "." for floats.
			return floatval(str_replace(",", ".", strval($a)));
		}

		if (is_string($a)) {
			static $jsonReplaces = array(array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'), array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'));
			return '"' . str_replace($jsonReplaces[0], $jsonReplaces[1], $a) . '"';
		} else {
			return $a;
		}
	}
	require_once $GLOBALS['babInstallPath'] . 'utilit/dateTime.php';
	if ($a instanceof BAB_DateTime) {
		/* @var $a BAB_DateTime */
		return 'new Date(Date.UTC(' . $a->getYear() . ',' . ($a->getMonth() - 1) . ',' . $a->getDayOfMonth() . ',' . $a->getHour() . ',' . $a->getMinute() . ',' . $a->getSecond() . ',0))';
	}
	if (is_object($a) && method_exists($a, 'toJson')) {
		return $a->toJson();
	}
	$isList = true;
	for ($i = 0, reset($a); $i < count($a); $i++, next($a)) {
		if (key($a) !== $i) {
			$isList = false;
			break;
		}
	}
	$result = array();
	if ($isList) {
		foreach ($a as $v) {
			$result[] = bab_json_encode($v);
		}
		return '[' . join(',', $result) . ']';
	} else {
		foreach ($a as $k => $v) {
			$result[] = bab_json_encode((string) $k).':'.bab_json_encode($v);
		}
		return '{' . join(',', $result) . '}';
	}
}


