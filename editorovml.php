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

function dire_ext($rep,$ext )
{
	if (!is_dir($rep)) return false;
	$reper = opendir($rep);
	$i = 0;
	while($dir = readdir($reper))
		{
		if (($dir != ".") && ($dir != "..") && (in_array(strrchr($dir,"."),$ext)) ) 
			{
			$fichier[$i] = $dir ; 
			$i++;
			}
		
		}
	return $fichier;
}

function dire_dir($rep )
{
	if (!is_dir($rep)) return false;
	$reper = opendir($rep);
	$i = 0;
	while($dir = readdir($reper))
		{
		if (($dir != ".") && ($dir != "..") && is_dir($rep."/".$dir) ) 
			{
			$fichier[$i] = $dir ; 
			$i++;
			}
		
		}
	if (isset($fichier))
		return $fichier;
	else
		return false;
}


function browse($url,$cb)
	{
	global $babBody, $babDB;

	class temp
		{
		var $cb;
		var $db;
		var $count;

		function temp($url,$cb)
			{
			if ( $url != "" ) 
				{
				$this->backlink = true;
				$upperpath = substr($url,0,strrpos($url,"/"));
				$this->backlink = $GLOBALS['babUrlScript']."?tg=editorovml&url=".$upperpath;
				}
			$this->path = is_dir($GLOBALS['babOvmlPath'].'editor') ? 'editor/' : '';
			$this->url = $url;
			$this->cb = "".$cb;
			$this->ext = array (".ovml", ".html", ".htm", ".oml",".ovm");
			$this->tablo_dir = dire_dir($GLOBALS['babOvmlPath'].$this->path.$this->url);
			$this->tablo_files = dire_ext($GLOBALS['babOvmlPath'].$this->path.$this->url,$this->ext);
			$this->count_dir = count($this->tablo_dir);
			if (is_array($this->tablo_files))
				$this->count_files = count($this->tablo_files);
			else
				$this->count_files = 0;
			
			}

		function getnext_dir()
			{
			global $babBody;
			static $i = 0;
			if( $i < $this->count_dir)
				{
				$subfiles = dire_ext($GLOBALS['babOvmlPath'].$this->path.$this->tablo_dir[$i],$this->ext);
				if (count($subfiles)>0)
					{
					$this->displink = true;
					$this->upurl = $GLOBALS['babUrlScript']."?tg=editorovml&url=".$this->tablo_dir[$i];
					}
				else
					$this->displink = false;
				
				$this->title = urlencode($this->tablo_dir[$i]);
				$i++;
				return true;
				}
			else
				return false;
			}
		
		function getnext_file()
			{
			global $babBody;
			static $i = 0;
			if( $i < $this->count_files)
				{
				$this->file = $this->tablo_files[$i];
				if ($this->url != "")
					{
					$url=$this->url."/";
					}
				else
					$url='';
				$this->urlfile = $this->path.$url.$this->tablo_files[$i];
					
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($url,$cb);
	echo bab_printTemplate($temp,"editorovml.html", "editorovml");
	}

if(!isset($url))
	{
	$url = "";
	}

if(!isset($cb))
	{
	$cb = "EditorOnInsertOvml";
	}

if (isset($_GET['idx']))
switch($_GET['idx'])
	{
	default:
	case "browse":
		browse($url,$cb);
		exit;
	}
?>