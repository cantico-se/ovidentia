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
 * Url utilities
 */
class bab_url {

	/**
	 * Add or modify a parameter value into an URL
	 * @static
	 * @param string 	$url
	 * @param string 	$param 	name of the variable
	 * @param mixed 	$value
	 * @return string $url
	 */
	function mod($url, $param, $value) {
	
		if (is_array($value)) {
			
			debug_print_backtrace();
			print_r($value);
			
			die();
			$keyval = bab_url::urlAsArray($param, $value);
		} else {
			$keyval = urlencode($param).'='.urlencode($value);
		}
	
		$newurl = preg_replace('/(&|\?)'.preg_quote($param, '/').'=[^&]*/','\\1'.$keyval,$url,1);
		if ($newurl !== $url) {
			return $newurl;
		}
		
		 
		if (false === strpos($url,'?')) {
			$url .= '?'.$keyval;
		} else {
			$url .= '&'.$keyval;
		}
		return $url;
	}


	/**
	 * Returns an URL-encoded query string form a (possibly multi-dimensional) array.
	 *
	 * @param array	$data		The array may be a simple one-dimensional structure,
	 * 							or an array of arrays (who in turn may contain other arrays).
	 * @return string
	 */
	function buildQuery($data) {
		if (function_exists('http_build_query')) {
			// Only available in php >= 5
			$url = http_build_query($data);
		} else {
			// For php < 5 compatibility.
			$url = '';
			foreach ($data as $param => $value)	 {
				$url = self::mod($url, $param, $value);
			}
		}
		
		return $url;
	}
	
	/**
	 * @access private
	 */
	function urlAsArray($name, $arr) {
		
		$params = array();
		
		foreach($arr as $key => $value) {
		
			if (!is_null($value)) {
				if (is_array($value)) {
					$params[] = bab_url::urlAsArray($name.'['.$key.']', $value);
				} else {
					$params[] = urlencode($name.'['.$key.']').'='.urlencode($value);
				}
			}
		}
		
		return implode('&', $params);
	}
	
	
	
		
	/**
	 * Create url from the previous request
	 * Variables parameters for the name of the parameters allowed in the url
	 * @static
	 * @param string [...]
	 * @return string url
	 */
	function request() {
		$arr = func_get_args();
		$url = $_SERVER['PHP_SELF'];
		foreach($arr as $param) {
			$url = bab_url::mod($url, $param, bab_rp($param));
		}
		return $url;
	}
	
	
	/**
	 * Create url from the previous request
	 * @static
	 * @param 	array	$arr	 : array with the name of the parameters allowed in the url
	 * @return 	string url
	 */
	function request_array($arr) {
		$url = $_SERVER['PHP_SELF'];
		foreach($arr as $param) {
			$url = bab_url::mod($url, $param, bab_rp($param));
		}
		return $url;
	}
	
	
	/**
	 * Create url from the previous request
	 * All keys found in get and post
	 * @static
	 * @return string url
	 */
	function request_gp() {
		$arr = isset($_GET) && is_array($_GET) ? array_keys($_GET) : array();
		$arr += isset($_POST) && is_array($_POST) ? array_keys($_POST) : array();
		
		$url = $_SERVER['PHP_SELF'];
		foreach($arr as $param) {
			$url = bab_url::mod($url, $param, (string) bab_rp($param));
		}
		return $url;
	}

}


?>