<?php
/************************************************************************
 * Ovidentia                                                            *
 ************************************************************************
 * Copyright (c) 2001, CANTICO ( http://www.cantico.fr )                *
 ***********************************************************************/
include_once "base.php";
class babTempFiles
{
var $tmpdir;
var $elapsed;

function babTempFiles($path, $elapsed = 3600, $prefix = "ov_")
	{
	$this->elapsed = $elapsed;
	$this->prefix = $prefix;

	if( substr($path, -1) != "/")
		$path .= "/";

	if( !empty($path) && is_dir($path))
		{
		$this->tmpdir = $path;
		$h = opendir($this->tmpdir);
		$size = 0;
		while (($f = readdir($h)) != false)
			{
			if ($f != "." and $f != "..") 
				{
				$fpath = $this->tmpdir.$f;
				if (is_file($fpath) && substr($f, 0, strlen($this->prefix)) == $this->prefix)
					{
					$ftime = substr($f, strlen($this->prefix));
					if( mktime() - $ftime > $this->elapsed )
						unlink( $fpath );
					}
				}
			}
		closedir($h);
		}
	}


function tempfile( $tmpfile, $file )
	{
	if( empty($this->tmpdir) || !is_dir($this->tmpdir))
		return "";

	if ($ext = strrchr($file,"."))
		{
		$ext = substr($ext,1);
		}
	else
		$ext = "";

	$filename = $this->prefix.mktime();
	if( $ext )
		$filename .= ".".$ext;

	if( !move_uploaded_file($tmpfile, $this->tmpdir.$filename))
		{
		return "";
		}
	else
		{
		return $this->tmpdir.$filename;
		}
	}
}
?>
