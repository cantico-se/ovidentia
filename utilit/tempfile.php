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
