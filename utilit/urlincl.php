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
	 * @param string $url
	 * @param string $param name of the variable
	 * @param string $value
	 * @return string $url
	 */
	function mod($url, $param, $value) {
		$n = 0;
		$url = preg_replace('/(&|\?)'.preg_quote($param, '/').'=[^&]+/','\\1'.$param.'='.urlencode($value),$url,1, $n);
		if (1 == $n) {
			return $url;
		} elseif (false === strpos($url,'?')) {
			$url .= '?'.$param.'='.urlencode($value);
		} else {
			$url .= '&'.$param.'='.urlencode($value);
		}
		return $url;
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

}


?>