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

function bab_cpaddons($from,$to)
	{
	function ls_a($wh){
         if ($handle = opendir($wh)) {
             while (false !== ($file = readdir($handle))) {
				if ($file != "." && $file != ".." ) {
						if(!$files) $files="$file";
						else $files="$file\n$files";
				   }
              }
               closedir($handle);
         }
        $arr=explode("\n",$files);
         return $arr;
     }
	function cp($wf, $wto){ 
		  if (!is_dir($wto)) { bab_mkdir($wto,$GLOBALS['babMkdirMode']); }
		  $arr=ls_a($wf);
		  foreach ($arr as $fn){
				  if($fn){
					  $fl="$wf/$fn";
					 $flto="$wto/$fn";
				  if(is_dir($fl)) cp($fl,$flto);
						   else copy($fl,$flto);
				   }
		   }
      }
	function create($path)
	{
	$el = explode("/",$path);
	foreach ($el as $rep)
		{
		if (!is_dir($memo.$rep)) { bab_mkdir($memo.$rep,$GLOBALS['babMkdirMode']); }
		$memo = $memo.$rep."/";
		}
	}
	if (substr($from,-1) != "/") $from.="/";
	if (substr($to,-1) != "/") $to.="/";
	$loc = array("addons",
				"lang/addons",
				"skins/ovidentia/templates/addons",
				"skins/ovidentia/ovml/addons",
				"skins/ovidentia/images/addons");
	foreach ($loc as $path)
		{
		create($to.$path);
		cp($from.$path,$to.$path);
		}
	}
	
	
function bab_writeConfig($replace)
	{
	global $babBody;
	function replace($txt, $var, $value)
		{
		ereg($var."[[:space:]]*=[[:space:]]*\"([^\"]*)\"", $txt, $match);
		if ($match[1] != $value)
			{
			$out = ereg_replace($var."[[:space:]]*=[[:space:]]*\"".preg_quote($match[1],"/")."\"", $var." = \"".$value."\"", $txt);
			if ($out != $txt)
				return $out;
			else
				return false;
			}
		else
			return $txt;
		}
		
	$file = @fopen('config.php', "r");
	if (!$file)
		{
		$babBody->msgerror = bab_translate('Failed to read config file');
		return false;
		}
	$txt = fread($file, filesize('config.php'));
	fclose($file);
	
	$config = array('babDBHost','babDBLogin','babDBPasswd','babDBName','babInstallPath','babUrl');
	
	foreach ($replace as $key => $value)
		{
		$out = replace($txt, $key, $value);
		if (!$out)
			{
			$babBody->msgerror = bab_translate('Config change failed on').' '.$var;
			return false;
			}
		else
			$txt = $out;
		}
		
	$file = fopen('config.php', "w");
	if (!$file)
		{
		$babBody->msgerror = bab_translate('Failed to write into config file');
		return false;
		}
	fputs($file, $out);
	fclose($file);
	
	return true;
	}

?>