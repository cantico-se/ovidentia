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


function bab_parseUri($matches) {
	$link = (strlen($matches[0]) > 30) ? substr($matches[0],0,30).'...' : $matches[0];
	return '<a href="'.$matches[0].'" title="'.$matches[0].'" class="url" target="_blank">'.$link.'</a>';
}

function bab_f_toHtml($pee, $opt) {

	if (BAB_HTML_AUTO === ($opt & BAB_HTML_AUTO)) {
		if (0 !== preg_match("/(\r|\n)/", $pee)) {
			$opt = $opt | BAB_HTML_P;
		} else {
			$opt = $opt & ~BAB_HTML_P;
		}
	}
	
	/**
	 * the original javascript string must be enclosed in simple quotes
	 */
	if (BAB_HTML_JS === ($opt & BAB_HTML_JS)) {
		$pee = str_replace('\\', '\\\\', $pee);
		$pee = str_replace("'", "\'", $pee);
		$pee = str_replace('"', "'+String.fromCharCode(34)+'",$pee);
	}

	if (BAB_HTML_ENTITIES === ($opt & BAB_HTML_ENTITIES))
		$pee = htmlentities($pee);
		
	if (BAB_HTML_LINKS === ($opt & BAB_HTML_LINKS)) {
		$pee = preg_replace_callback('/(http|https|ftp):(\/\/){0,1}([^\"\s]*)/i','bab_parseUri',$pee);
		$pee = ereg_replace("[_a-zA-Z0-9\-]+(\.[_a-zA-Z0-9\-]+)*\@[_a-zA-Z0-9\-]+(\.[_a-zA-Z0-9\-]+)*(\.[a-zA-Z]{1,5})+", "<a class=\"mailto\" href=\"mailto:\\0\">\\0</a>", $pee);
	}

	if (BAB_HTML_P === ($opt & BAB_HTML_P)) {
		$pee = preg_replace("/(\r\n|\n|\r)/", "\n", $pee);
		$pee = preg_replace("/\n\n+/", "\n\n", $pee);
		$pee = preg_replace("/\n?(.+?)(\n\n|\z)/s", "<p>$1</p>", $pee);
	}

	if (BAB_HTML_BR === ($opt & BAB_HTML_BR)) {
		$pee = nl2br($pee);
	}

	if (BAB_HTML_REPLACE === ($opt & BAB_HTML_REPLACE)) {
		$replace = bab_replace_get();
		$replace->ref($pee);
	}
	
	if (BAB_HTML_REPLACE_MAIL === ($opt & BAB_HTML_REPLACE_MAIL)) {
		$replace = bab_replace_get();
		$replace->email($pee);
	}
	
	
	
	if (BAB_HTML_TAB === ($opt & BAB_HTML_TAB)) {
		$pee = preg_replace("/\t/", "&nbsp; &nbsp; &nbsp; ", $pee);
	}

	return $pee;
	}



?>