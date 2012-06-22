<?php
//-------------------------------------------------------------------------
// OVIDENTIA http://www.ovidentia.org
// Ovidentia is free software; you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation; either version 2, or (at your option)
// any later version.
//
// This program is distributed in the hope that it will be useful, but
// WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
// See the GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program; if not, write to the Free Software
// Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307,
// USA.
//-------------------------------------------------------------------------
/**
 * @license http://opensource.org/licenses/gpl-license.php GNU General Public License (GPL)
 * @copyright Copyright (c) 2008 by CANTICO ({@link http://www.cantico.fr})
 */



include_once 'base.php';






/**
 * Get translations matchs found in one lang file
 * @param	string	$lang
 * @param	string	$filename
 * @return array
 */
function bab_getLangFileMatchs($lang, $filename)
{
	$file = @fopen($filename, 'r');
	if( $file )
	{
		$tmp = fread($file, filesize($filename));
		fclose($file);


		$charset = 'ISO-8859-15';


		$xml_header_pos = strpos($tmp, "?>");
		if (false !== $xml_header_pos) {
			$xml_header = substr($tmp, 0, $xml_header_pos);
			if (preg_match('/encoding="(UTF-8|ISO-8859-[0-9]{1,2})"/', $xml_header, $m)) {
				$charset = $m[1];
			}
		}

		$tmp = bab_getStringAccordingToDataBase($tmp, $charset);

		if (preg_match('/<'.$lang.'>(.*)<\/'.$lang.'>/s', $tmp)) {
			preg_match_all('/<string\s+id=\"([^\"]*)\">(.*?)<\/string>/s', $tmp , $tmparr);
			return $tmparr;
		}
	}

	return array();
}








function babLoadLanguage($lang, $folder, &$arr)
	{
	if( empty($folder))
		{
		$filename_c = 'lang/lang-'.$lang.'.dat';
		$filename_m = 'lang/lang-'.$lang.'.xml';
		$filename = $GLOBALS['babInstallPath'].'lang/lang-'.$lang.'.xml';
		}
	else
		{
		$filename_c = 'lang/addons-'.$folder.'-lang-'.$lang.'.dat';
		$filename_m = 'lang/addons/'.$folder.'/lang-'.$lang.'.xml';
		$filename = $GLOBALS['babInstallPath'].'lang/addons/'.$folder.'/lang-'.$lang.'.xml';
		}

	if (!file_exists($filename))
		{
		$filename = false;
		}
	else
		{
		$time = filemtime($filename);
		}

	if (!file_exists($filename_m))
		{
		$filename_m = false;
		}
	else
		{
		$time_m = filemtime($filename_m);
		}

	if (!file_exists($filename_c))
		{
		$bfile_c = false;
		}
	else
		{
		$bfile_c = true;
		$time_c = filemtime($filename_c);
		}

	if( !$filename && !$filename_c)
		{
		return;
		}

	if( !$bfile_c || (($filename && ($time > $time_c)) || ($filename_m && ($time_m > $time_c)) ))
		{
		if( $filename )
			{
			$tmparr = bab_getLangFileMatchs($lang, $filename);
			}

		if( isset($tmparr[0]))
			{
			for( $i = 0; $i < count($tmparr[0]); $i++ )
				{
				$arr[$tmparr[1][$i]] = $tmparr[2][$i];
				}
			}

		if ($filename_m)
			{
			$arr_replace = bab_getLangFileMatchs($lang, $filename_m);

			if (isset($arr_replace[0]))
				{
				for( $i = 0; $i < count($arr_replace[0]); $i++ )
					{
					$arr[$arr_replace[1][$i]] = $arr_replace[2][$i];
					}
				}
			}

		if (is_writable(dirname($filename_c))) {
			$file = @fopen($filename_c, 'w');
			if( $file )
				{
				fwrite($file, serialize($arr));
				fclose($file);
				}
			}
		}
	else
		{
			$file = @fopen($filename_c, 'r');
			$arr = unserialize(fread($file, filesize($filename_c)));
			fclose($file);
		}

	}

