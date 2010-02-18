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
	
	
	private $url = null;
	

	/**
	 * Add or modify a parameter value into an URL
	 * 
	 * @param string 	$url
	 * @param string 	$param 	name of the variable
	 * @param mixed 	$value
	 * @return string $url
	 */
	public static function mod($url, $param, $value) {
	
		if (is_array($value)) {
			$keyval = bab_url::urlAsArray($param, $value);
		} else {
			$keyval = urlencode($param).'='.urlencode($value);
		}
	
		$newurl = preg_replace('/(&|\?)'.preg_quote($param, '/').'=[^&]*/', '\\1'.$keyval, $url, 1, $count);
		if ($count > 0) {
			return $newurl;
		}

		 
		if (false === mb_strpos($url,'?')) {
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
	public static function buildQuery($data) {
		return http_build_query($data);
	}
	
	/**
	 * Convert an array as query string
	 * 
	 * @param	string	$name
	 * @param	array	$arr
	 * 
	 * @return	string
	 */
	private static function urlAsArray($name, $arr) {
		
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
	 * 
	 * @param string [...]
	 * @return string url
	 */
	public static function request() {
		$arr = func_get_args();
		$url = basename($_SERVER['PHP_SELF']);
		foreach($arr as $param) {
			$url = bab_url::mod($url, $param, bab_rp($param));
		}
		return $url;
	}
	
	
	/**
	 * Create url from the previous request
	 * 
	 * @param 	array	$arr	 : array with the name of the parameters allowed in the url
	 * @return 	string url
	 */
	public static function request_array($arr) {
		$url = basename($_SERVER['PHP_SELF']);
		foreach($arr as $param) {
			$url = bab_url::mod($url, $param, bab_rp($param));
		}
		return $url;
	}
	
	
	/**
	 * Create url from the previous request
	 * All keys found in get and post
	 *
	 * @return string url
	 */
	public static function request_gp() {
		$arr1 = isset($_GET) && is_array($_GET) ? array_keys($_GET) : array();
		$arr2 = isset($_POST) && is_array($_POST) ? array_keys($_POST) : array();
		$arr = array_merge($arr1, $arr2);
		
		$url = basename($_SERVER['PHP_SELF']);
		foreach($arr as $param) {
			$url = bab_url::mod($url, $param, bab_rp($param));
		}
		return $url;
	}
	
	/**
	 * Create url object from the previous request
	 * Variables parameters for the name of the parameters allowed in the url
	 * @since 7.1.94
	 * 
	 * @param string [...]
	 * @return bab_url
	 */
	public static function get_request() {
		
		$arr = func_get_args();
		$url = basename($_SERVER['PHP_SELF']);
		foreach($arr as $param) {
			$url = bab_url::mod($url, $param, bab_rp($param));
		}
		
		return new bab_url($url);
	}

	/**
	 * Create url object from the previous request
	 * All keys found in get and post
	 * @since 7.1.94
	 * 
	 * @return bab_url
	 */
	public static function get_request_gp() {
		return new bab_url(self::request_gp());
	}

	/**
	 * Create bab_url object
	 * @param	string	$url	initialize object with url, if no parameter, current url without parameters will be used
	 * @since 7.1.94
	 * 
	 */ 
	public function __construct($url) {
		$this->url = $url;
	}
	
	/**
	 * Property overloading to set an url parameter
	 * 
	 * 
	 * @param	string	$param
	 * @param mixed 	$value
	 * 
	 * @since 7.1.94
	 */ 
	public function __set($param, $value) {
		$this->url = self::mod($this->url, $param, $value);
	}
	
	
	
	/**
	 * Url as String
	 * The method is NOT __toString because the beaviour is not as expexted before php 5.2.0
	 * 
	 * @since 7.1.94
	 * 
	 * @return string
	 */ 
	public function toString() {
		return $this->url;
	}
	
	
	
	
	/**
	 * add a header "location" for this URL and exit program
	 * 
	 * @since 7.2.2
	 */ 
	public function location() {
		header('location:'.$this->url);
		exit;
	}
}


?>
