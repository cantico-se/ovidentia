<?php

define("BAB_FILE_TIMEOUT", 600);
define("BAB_IMAGE_MAXSIZE", 30000);
define("BAB_IMAGES_UPLOADDIR", "images/");
define("BAB_IMAGES_UPLOADDIR_TMP", "images/tmp/");
define("BAB_IMAGES_UPLOADDIR_COMMON", "images/common/");
define("BAB_IMAGES_TEMP_TBL", "bab_images_temp");

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
			if( is_file(BAB_IMAGES_UPLOADDIR_TMP.$arr[sizeof($arr) -1]) )
				{
				$newfile = $prefix.$arr[sizeof($arr) -1];
				if( rename(BAB_IMAGES_UPLOADDIR_TMP.$arr[sizeof($arr) -1], BAB_IMAGES_UPLOADDIR.$newfile))
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
				$repl .= "/".$newfile;
				$txt = preg_replace("/".preg_quote($link, "/")."/", $repl, $txt);
				}
			}
		}
	return $txt;
	}

function deleteImagesArticle($txt, $article)
	{
	preg_match_all("|src=\"?([^\"' >]+)|i", $txt, $m);
	while(list(,$link) = each($m[1]))
		{
		$arr = explode('/', $link);
		$file = $arr[sizeof($arr) -1];
		$arr = explode( '_', $file );
		if( $arr[0] = $article && $arr[1] = "art" && is_file(BAB_IMAGES_UPLOADDIR.$file))
			@unlink(BAB_IMAGES_UPLOADDIR.$file);
		}
	}

?>