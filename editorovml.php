<?php
function dire_ext($rep,$ext )
{
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
	return $fichier;
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
			$this->url = $url;
			$this->cb = "".$cb;
			$this->ext = array (".ovml", ".html", ".htm", ".oml",".ovm");
			$this->tablo_dir = dire_dir($GLOBALS['babOvmlPath'].$url);
			$this->tablo_files = dire_ext($GLOBALS['babOvmlPath'].$url,$this->ext);
			$this->count_dir = count($this->tablo_dir);
			$this->count_files = count($this->tablo_files);
			}

		function getnext_dir()
			{
			global $babBody;
			static $i = 0;
			if( $i < $this->count_dir)
				{
				$subfiles = dire_ext($GLOBALS['babOvmlPath'].$this->tablo_dir[$i],$this->ext);
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
				if ($this->url != "") $url=$this->url."/";
				$this->urlfile = $url.$this->tablo_files[$i];
					
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

switch($idx)
	{
	default:
	case "browse":
		browse($url,$cb);
		exit;
	}
?>