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

define("BAB_FILE_TIMEOUT", 36000);
define("BAB_IUF", "images");
define("BAB_IUF_TMP", "tmp");
define("BAB_IUF_COMMON", "common");
define("BAB_IUF_ARTICLES", "articles");
define("BAB_IUD", BAB_IUF."/");
define("BAB_IUD_TMP", BAB_IUF."/".BAB_IUF_TMP."/");
define("BAB_IUD_COMMON", BAB_IUF."/".BAB_IUF_COMMON."/");
define("BAB_IUD_ARTICLES", BAB_IUF."/".BAB_IUF_ARTICLES."/");

function imagesReplace($txt, $prefix, &$tab)
	{
	preg_match_all("|src=\"?([^\"' >]+)|i", $txt, $m);
	while(list(,$link) = each($m[1]))
		{
		$arr = explode('/', $link);
		$newfile = "";
		if( isset($arr[sizeof($arr) -2]) && $arr[sizeof($arr) -2] == "tmp" )
			{
			clearstatcache();
			if( is_file(BAB_IUD_TMP.$arr[sizeof($arr) -1]) )
				{
				$newfile = $prefix.$arr[sizeof($arr) -1];
				if( !is_dir(BAB_IUD_ARTICLES))
					bab_mkdir(BAB_IUD_ARTICLES, $GLOBALS['babMkdirMode']);
				if( rename(BAB_IUD_TMP.$arr[sizeof($arr) -1], BAB_IUD_ARTICLES.$newfile))
					{
					$tab[$arr[sizeof($arr) -1]] = $newfile;
					}
				}

			else if( isset($tab[$arr[sizeof($arr) -1]]))
				{
				$newfile = $tab[$arr[sizeof($arr) -1]];
				}
			if( !empty($newfile))
				{
				array_pop($arr);
				array_pop($arr);
				$repl = implode('/', $arr);
				$repl .= "/".BAB_IUF_ARTICLES."/".$newfile;
				$txt = preg_replace("/".preg_quote($link, "/")."/", $repl, $txt);
				}
			}
		}
	return $txt;
	}

function deleteImages($txt, $id, $prefix)
	{
	preg_match_all("|src=\"?([^\"' >]+)|i", $txt, $m);
	while(list(,$link) = each($m[1]))
		{
		$arr = explode('/', $link);
		$file = $arr[sizeof($arr) -1];
		$arr = explode( '_', $file );
		if( $arr[0] == $id && $arr[1] == $prefix && is_file(BAB_IUD_ARTICLES.$file))
			@unlink(BAB_IUD_ARTICLES.$file);
		}
	}


function imagesUpdateLink($txt, $prefix, $newprefix)
	{
	preg_match_all("|src=\"?([^\"' >]+)|i", $txt, $m);
	while(list(,$link) = each($m[1]))
		{
		$rr = explode('/', $link);
		$file = $rr[sizeof($rr) -1];
		$rr = explode( '_', $file );
		$oldprefix = (isset($rr[0])?$rr[0]:'')."_".(isset($rr[1])?$rr[1]:'')."_";
		if( $oldprefix == $prefix && is_file(BAB_IUD_ARTICLES.$file))
			{
			$txt = preg_replace("/".preg_quote($oldprefix, "/")."/", $newprefix, $txt);
			array_shift($rr);
			array_shift($rr);
			@rename(BAB_IUD_ARTICLES.$file, BAB_IUD_ARTICLES.$newprefix.implode('_', $rr));
			}
		}
	return $txt;
	}

function accentRemover($str)
	{
	$arrconv = array(
				"\xE1" => 'a',
				"\xC1" => 'A',
				"\xE0" => 'a',
				"\xC0" => 'A',
				"\xE2" => 'a',
				"\xC2" => 'A',
				"\xE4" => 'a',
				"\xC4" => 'A',
				"\xE3" => 'a',
				"\xC3" => 'A',
				"\xE5" => 'a',
				"\xC5" => 'A',
				"\xAA" => 'a',
				"\xE7" => 'c',
				"\xC7" => 'C',
				"\xE9" => 'e',
				"\xC9" => 'E',
				"\xE8" => 'e',
				"\xC8" => 'E',
				"\xEA" => 'e',
				"\xCA" => 'E',
				"\xEB" => 'e',
				"\xCB" => 'E',
				"\xED" => 'i',
				"\xCD" => 'I',
				"\xEC" => 'i',
				"\xCC" => 'I',
				"\xEE" => 'i',
				"\xCE" => 'I',
				"\xEF" => 'i',
				"\xCF" => 'I',
				"\xF1" => 'n',
				"\xD1" => 'N',
				"\xF3" => 'o',
				"\xD3" => 'O',
				"\xF2" => 'o',
				"\xD2" => 'O',
				"\xF4" => 'o',
				"\xD4" => 'O',
				"\xF6" => 'o',
				"\xD6" => 'O',
				"\xF5" => 'o',
				"\xD5" => 'O',
				"\x08" => 'o',
				"\xD8" => 'O',
				"\xBA" => 'o',
				"\xF0" => 'o',
				"\xFA" => 'u',
				"\xDA" => 'U',
				"\xF9" => 'u',
				"\xD9" => 'U',
				"\xFB" => 'u',
				"\xDB" => 'U',
				"\xFC" => 'u',
				"\xDC" => 'U',
				"\xFD" => 'y',
				"\xDD" => 'Y',
				"\xFF" => 'y',
				"\xE6" => 'a',
				"\xC6" => 'A',
				"\xDF" => 's',
				);
	return str_replace(array_keys($arrconv),array_values($arrconv),$str);
	}
?>
