<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
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
		if( $arr[sizeof($arr) -2] == "tmp" )
			{
			clearstatcache();
			if( is_file(BAB_IUD_TMP.$arr[sizeof($arr) -1]) )
				{
				$newfile = $prefix.$arr[sizeof($arr) -1];
				if( !is_dir(BAB_IUD_ARTICLES))
					mkdir(BAB_IUD_ARTICLES, 0700);
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
		if( $arr[0] = $id && $arr[1] = $prefix && is_file(BAB_IUD_ARTICLES.$file))
			@unlink(BAB_IUD_ARTICLES.$file);
		}
	}

?>