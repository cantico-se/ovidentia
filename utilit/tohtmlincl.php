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
		$pee = preg_replace("/(\r\n|\n|\r)/", '\n', $pee);
	}

	if (BAB_HTML_ENTITIES === ($opt & BAB_HTML_ENTITIES))
	{
		$pee = htmlspecialchars($pee, ENT_COMPAT, bab_charset::getIso());
	}
		
	if (BAB_HTML_LINKS === ($opt & BAB_HTML_LINKS)) {
		$pee = preg_replace_callback('/(http|https|ftp):(\/\/){0,1}([^\"\s]*)/i','bab_parseUri',$pee);
		$pee = preg_replace("/[_a-zA-Z0-9\-]+(\.[_a-zA-Z0-9\-]+)*\@[_a-zA-Z0-9\-]+(\.[_a-zA-Z0-9\-]+)*(\.[a-zA-Z]{1,5})+/", "<a class=\"mailto\" href=\"mailto:\\0\">\\0</a>", $pee);
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
		
		
		
		// 					euro		apostrophe 	apostrophe	apostrophe				oe			OE
		//								CP1252		CP1252		ISO-8859-1
		$source 	= array(chr(0x80),	chr(0x91),	chr(0x92),	chr(0xB4),	chr(0x9C),	chr(0x8C),	chr(0x93),	chr(0x94),	chr(0x85),	chr(0x96),	chr(0x97),	chr(0x88),	chr(0x99),		chr(0x8B),	chr(0x9B),	chr(0x84),	chr(0x95),	chr(0x89),	chr(0x83),	chr(0x86));
		$replace	= array(chr(0xA4),	"'",		"'",		"'",		chr(0xBD),	chr(0xBC),	'"',		'"',		'...',		'-',		'-',		'^',		'<sup>TM</sup>',	'&lsaquo;',	'&rsaquo;',	'&bdquo;',	'&bull;',	'&permil;',	'&fnof;',	'&dagger;');
		
		$pee = str_replace($source, $replace	, $pee);
		
	}


	return $pee;
	}




	
/**
 * Remove html entities 
 * 
 * @todo compliance with UTF-8
 * the charset parameter of get_html_translation_table has benn added in php 5.3.4
 * 
 * @param	string	$string
 * @return string
 */
function bab_unhtmlentities($string)
{
	// special quote : htmlarea &#8217; and fckeditor &rsquo;
	$string = preg_replace('~&#8217;~', '\'', $string);
	
	// replace numeric entities
	$string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
	$string = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $string);
	
	// replace literal entities
	$trans_tbl = get_html_translation_table(HTML_ENTITIES);
	$trans_tbl = array_flip($trans_tbl);
			
	$trans_tbl['&rsquo;'] = "'";
	
	return strtr($string, $trans_tbl);
}
