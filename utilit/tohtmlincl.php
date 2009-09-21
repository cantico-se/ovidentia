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
	$link = (mb_strlen($matches[0]) > 30) ? mb_substr($matches[0],0,30).'...' : $matches[0];
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
	{
		$pee = htmlspecialchars($pee, ENT_COMPAT, bab_charset::getIso());
	}
		
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


	if ('ISO-8859-15' === bab_charset::getIso()) {
		$pee = str_replace(chr(0x80), chr(0xA4)	, $pee);	// euro
		$pee = str_replace(chr(0x91), "'"		, $pee);	// apostrophe CP1252
		$pee = str_replace(chr(0x92), "'"		, $pee);	// apostrophe CP1252
		$pee = str_replace(chr(0xB4), "'"		, $pee);	// apostrophe ISO-8859-1
		$pee = str_replace(chr(0x9C), chr(0xBD) , $pee);	// oe 
		$pee = str_replace(chr(0x8C), chr(0xBC) , $pee);	// OE
		$pee = str_replace(chr(0x93), '"' 		, $pee);
		$pee = str_replace(chr(0x94), '"' 		, $pee);
		$pee = str_replace(chr(0x85), '...' 	, $pee);
		$pee = str_replace(chr(0x96), '-' 		, $pee);
		$pee = str_replace(chr(0x97), '-' 		, $pee);
		$pee = str_replace(chr(0x88), '^'		, $pee);
		$pee = str_replace(chr(0x99), '<sup>TM<sup>', $pee);

		$pee = str_replace(chr(0x8B), '&lsaquo;', $pee);
		$pee = str_replace(chr(0x9B), '&rsaquo;', $pee);
		$pee = str_replace(chr(0x84), '&bdquo;'	, $pee);
		$pee = str_replace(chr(0x95), '&bull;'	, $pee);
		$pee = str_replace(chr(0x89), '&permil;', $pee);
		$pee = str_replace(chr(0x83), '&fnof;'	, $pee);
		$pee = str_replace(chr(0x86), '&dagger;', $pee);
	}


	return $pee;
	}



?>
