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
require_once 'base.php';


/**
 * Helper class for sorting arrays.
 *
 */
class bab_Sort
{
	private static $sKeyName		= null;
	private static $sortKeyMethod	= null;

	const CASE_SENSITIVE			= 0;
	const CASE_INSENSITIVE			= 1;



	/**
	 * Sort an array
	 *
	 * @param array		$aToSort	array to sort
	 * @param int		$iCase		Case used in compare
	 * @return bool
	 */
	public static function sort(array &$aToSort, $iCase = bab_Sort::CASE_SENSITIVE)
	{
		if (bab_Sort::CASE_INSENSITIVE == $iCase) {
			$sortCallback = 'compareStringsInsensitive';
		} else {
			$sortCallback = 'compareStringsSensitive';
		}
		return usort($aToSort, array('bab_sort', $sortCallback));
	}



	



	/**
	 * Sort an array using a case insensitive "natural order" algorithm.
	 *
	 * @param array		$aToSort	The array to sort
	 * @return bool
	 */
	public static function natcasesort(array &$aToSort)
	{
		return uasort($aToSort, array('bab_sort', 'compareStringsInsensitive'));
	}

	/**
	 * Sort an array of objects according to a value returned by a method of the objects in the array
	 *
	 * @param	array	$aToSort			The array of objects to sort
	 * @param	string	$sortKeyMethod		method of objects of the array to get a sortable value (string, int, float)
	 *										the default value is __tostring, it will be really called as a method.
	 *										the __tostring method is usable for object string casting since php 5.2
	 * @param	int		$iCase				Case used in compare
	 * @return	bool
	 */
	public static function sortObjects(array &$aToSort, $sortKeyMethod = '__tostring', $iCase = bab_Sort::CASE_INSENSITIVE) {

		self::$sortKeyMethod = $sortKeyMethod;

		if (bab_Sort::CASE_INSENSITIVE == $iCase) {
			$sortCallback = 'compareObjectsInsensitive';
		} else {
			$sortCallback = 'compareObjectsSensitive';
		}

		return uasort($aToSort, array('bab_sort', $sortCallback));
	}


	/**
	 * Compare case-sensitively two objects.
	 * 
	 * @see 	bab_compare
	 * @param 	object 	$obj1
	 * @param 	object 	$obj2
	 * @return 	int		Same values as bab_compare
	 */
	private static function compareObjectsSensitive($obj1, $obj2)
	{
		$method = self::$sortKeyMethod;

		$str1 = $obj1->$method();
		$str2 = $obj2->$method();

		return self::compareStringsSensitive($str1, $str2);
	}


	/**
	 * Compare case-insensitively two objects.
	 * 
	 * @see 	bab_compare
	 * @param 	object 	$obj1
	 * @param 	object 	$obj2
	 * @return 	int		Same values as bab_compare
	 */
	private static function compareObjectsInsensitive($obj1, $obj2)
	{
		$method = self::$sortKeyMethod;

		$str1 = $obj1->$method();
		$str2 = $obj2->$method();

		return self::compareStringsInsensitive($str1, $str2);
	}



	/**
	 * Sort an array using a case sensitive "natural order" algorithm.
	 *
	 * @param array		$aToSort	The array to sort
	 * @return bool
	 */
	public static function natsort(array &$aToSort)
	{
		return uasort($aToSort, array('bab_sort', 'compareStringsSensitive'));
	}



	/**
	 * Sort an array and maintain index association
	 *
	 * @param array		$aToSort	array to sort
	 * @param string	$sKeyName	If $aToSort is an array of array $sKeyName is the sort key
	 * @param int		$iCase		Case sensitivity used in comparison
	 * @return bool
	 */
	public static function asort(array &$aToSort, $sKeyName = null, $iCase = bab_Sort::CASE_SENSITIVE)
	{
		self::$sKeyName = $sKeyName;

		if(isset(self::$sKeyName)) {
			if (bab_Sort::CASE_INSENSITIVE == $iCase) {
				$sortCallback = 'compareKeysInsensitive';
			} else {
				$sortCallback = 'compareKeysSensitive';
			}			
		} else {
			if (bab_Sort::CASE_INSENSITIVE == $iCase) {
				$sortCallback = 'compareStringsInsensitive';
			} else {
				$sortCallback = 'compareStringsSensitive';
			}			
		}
		return uasort($aToSort, array('bab_sort', $sortCallback));
	}


	/**
	 * Sort an array by key
	 *
	 * @param array		$aToSort	array to sort
	 * @param int		$iCase		Case sensitivity used in comparison
	 * @return bool
	 */
	public static function ksort(array &$aToSort, $iCase = bab_Sort::CASE_SENSITIVE)
	{

		if (bab_Sort::CASE_INSENSITIVE == $iCase) {
			$sortCallback = 'compareStringsInsensitive';
		} else {
			$sortCallback = 'compareStringsSensitive';
		}
		return uksort($aToSort, array('bab_sort', $sortCallback));
	}



	/**
	 * Compare case-sensitively two strings.
	 * 
	 * @see bab_compare
	 * @param string $string1
	 * @param string $string2
	 * @return int		Same values as bab_compare
	 */
	private static function compareStringsSensitive($sStr1, $sStr2)
	{
		return bab_compare($sStr1, $sStr2);
	}


	/**
	 * Compare case-insensitively two strings.
	 * 
	 * @see bab_compare
	 * @param string $string1
	 * @param string $string2
	 * @return int		Same values as bab_compare
	 */
	private static function compareStringsInsensitive($sStr1, $sStr2)
	{
		return bab_compare(mb_strtolower((string) $sStr1), mb_strtolower((string) $sStr2));
	}	
	


	/**
	 * Compare case-sensitively two arrays according to the specified key (self::$sKeyName).
	 *
	 * @see bab_compare
	 * @param array $array1
	 * @param array $array2
	 * @return int
	 */
	private static function compareKeysSensitive(array $array1, array $array2)
	{
		return bab_compare($array1[self::$sKeyName], $array2[self::$sKeyName]);
	}



	/**
	 * Compare case-insensitively two arrays according to the specified key (self::$sKeyName).
	 *
	 * @see bab_compare
	 * @param array $array1
	 * @param array $array2
	 * @return int
	 */
	private static function compareKeysInsensitive(array $array1, array $array2)
	{
		return bab_compare(mb_strtolower($array1[self::$sKeyName]), mb_strtolower($array2[self::$sKeyName]));
	}
}



/**
 * Compare strings
 *
 * @param string $sStr1				Input string to compare
 * @param string $sStr2				Input string to compare
 * @param string $sStringIsoCharset	Iso charset of the input string to compare.
 * 									If this parameter is null strings are then 
 * 									considered in the same format as the database
 *
 * @return							1 if $sStr1 is greater than $sStr2
 * 									0 if $sStr1 is equal to $sStr2
 * 									-1 if $sStr1 is less than $sStr2
 * 									On error boolean  FALSE  is returned
 */
function bab_compare($sStr1, $sStr2, $sInputStringIsoCharset = null)
{
	if (!isset($sInputStringIsoCharset))
	{
		$sInputStringIsoCharset = bab_charset::getIso();
	}

	if (bab_charset::UTF_8 != $sInputStringIsoCharset)
	{
		// warning, characters with diacritics in ISO-8859-15 are not ordered correctly with strnatcmp
		
		// return strnatcmp($sStr1, $sStr2);
		return strnatcmp(bab_removeDiacritics($sStr1), bab_removeDiacritics($sStr2));
	}

	$oCollator = bab_getCollatorInstance();

	return $oCollator->compare($sStr1, $sStr2);
}




/**
 * Get a string according to the charset of the database
 *
 * @param string | array $input			String(s) to convert
 * @param string $sStringIsoCharset		Iso charset of the string to convert
 * @return string | array				The converted string(s)
 */
function bab_getStringAccordingToDataBase($input, $sStringIsoCharset)
{
	if (bab_charset::getIso() === $sStringIsoCharset) {
		return $input;
	}

	if (is_array($input)) {
		foreach($input as $k => $data) {
			$input[$k] = bab_getStringAccordingToDataBase($data, $sStringIsoCharset);
		}
			
		return $input;
	}

	return mb_convert_encoding($input, bab_charset::getIso(), $sStringIsoCharset);
}



/**
 * Get a string according to the charset of the databese
 *
 * @param string 	$input			String(s) to convert (in database charset)
 * @param string 	$sIsoCharset	Iso charset of the output string
 * @return string					The converted string(s)
 */
function bab_convertStringFromDatabase($input, $sIsoCharset)
{
	if (bab_charset::getIso() === $sIsoCharset) {
		return $input;
	}

	return mb_convert_encoding($input, $sIsoCharset, bab_charset::getIso());
}







/**
 * Get a instance of the collator object
 *
 * @param string|array $locale
 * @return Collator
 */
function bab_getCollatorInstance($locale = 'en_US')
{
	require_once $GLOBALS['babInstallPath'].'utilit/i18n.class.php';
	
	static $oCollator = null;
	if(!isset($oCollator))
	{
		$oCollator = new Collator($locale);
	}
	return $oCollator;
}

function bab_multibyteToHex($sBuffer)
{
    $sHexs	= '';
	$iCount = mb_strlen($sBuffer, 'UTF-8');
	for($iIndex = 0; $iIndex < $iCount; $iIndex++)
	{
        $sCh	= mb_substr($sBuffer, $iIndex, 1, 'UTF-8');
        $iChlen	= mb_strwidth($sCh, 'UTF-8');
        for($iIdx = 0; $iIdx < $iChlen; $iIdx++)
        {
            $sHexs = $sHexs . sprintf("%lx ", ord($sCh[$iIdx]));
        }
//        printf("width=%d => '%s' |hex=%s<br>", $iChlen, $sCh, $sHexs);
    }
    return $sHexs; 
}



/**
 * Return a formatted string, Wrapper for sprintf function
 * @param	string 	$format  
 * @param	mixed 	$args
		[ 	mixed 	$...  ]
 * @return
 * @see http://www.php.net/sprintf
 */
function bab_sprintf($sFormat)
{
	$aArgs = func_get_args();	
	return call_user_func_array('sprintf', $aArgs);
}





/**
 * This function replaces characters with diacritics from a ISO-8859-1/latin1
 * encoded string by their corresponding ascii characters.
 *
 * @param string $sString The (latin1 encoded) string to process.
 * @return string The processed string (in ascii).
 */	
function bab_removeDiacritics($sString)
{
	static $aSearch = null;
	static $aReplace = null;

	if (!isset($aSearch)) {
		$aSearch = array(
			chr(192), chr(193), chr(194), chr(195), chr(196), chr(197),
			chr(197),
			chr(200), chr(201), chr(202), chr(203),
			chr(204), chr(205), chr(206), chr(207),
			chr(208),
			chr(209),
			chr(210), chr(211), chr(212), chr(213), chr(214), chr(216),
			chr(217), chr(218), chr(219), chr(220),
			chr(221),
			chr(224), chr(225), chr(226), chr(227), chr(228), chr(229),
			chr(231),
			chr(232), chr(233), chr(234), chr(235),
			chr(236), chr(237), chr(238), chr(239),
			chr(241),
			chr(242), chr(243), chr(244), chr(245), chr(246), chr(248),
			chr(249), chr(250), chr(251), chr(252),
			chr(253), chr(255)
		);
	}

	if (!isset($aReplace)) {
		$aReplace = array(
			'A', 'A', 'A', 'A', 'A', 'A',
			'C',
			'E', 'E', 'E', 'E',
			'I', 'I', 'I', 'I',
			'D',
			'N',
			'O', 'O', 'O', 'O', 'O', 'O',
			'U', 'U', 'U', 'U',
			'Y',
			'a', 'a', 'a', 'a', 'a', 'a',
			'c',
			'e', 'e', 'e', 'e',
			'i', 'i', 'i', 'i',
			'n',
			'o', 'o', 'o', 'o', 'o', 'o',
			'u', 'u', 'u', 'u',
			'y', 'y'
		);
	}

	return str_replace($aSearch, $aReplace, $sString);
}


class bab_charset 
{
	private static $sCharset = null;
	private static $sIsoCharset = null;
	
	/**
	 * UTF-8 encoding.
	 * 
	 * @var string
	 */
	const	UTF_8 = 'UTF-8';

	/**
	 * ISO-8859-15 (latin1) encoding.
	 * 
	 * @var string
	 */
	const	ISO_8859_15 = 'ISO-8859-15';

	/**
	 * Returns the database charset
	 * 
	 * @static
	 * @return   string	The database charset
	 */
	public static function getDatabase() 
	{
		if(!isset(self::$sCharset))
		{
			global $babDB;
			$oResult = $babDB->db_query("SHOW VARIABLES LIKE 'character_set_database'");
			if(false === $oResult)
			{
				self::$sCharset = 'latin1';
			}
			
			$aDbCharset = $babDB->db_fetch_assoc($oResult);
			if(false === $aDbCharset)
			{
				self::$sCharset = 'latin1';
			}
			
			self::$sCharset = $aDbCharset['Value'];
		}
		return self::$sCharset;
	}
	
	private static function resetCharset()
	{
		self::$sCharset = null;
		bab_charset::getDatabase();
	}
	

	/**
	 * Returns the ISO code of the database encoding.
	 * 
	 * @param string $sCharset
	 * @return string
	 */
	public static function getIso() 
	{
		if(!isset(self::$sIsoCharset)) {
			 self::$sIsoCharset = self::getIsoCharsetFromDataBaseCharset(self::getDatabase());
		}
		return self::$sIsoCharset;
	}

	/**
	 * Converts the code of the database encoding to the ISO code.
	 * 
	 * @param string $sCharset
	 * @return string
	 */
	public static function getIsoCharsetFromDataBaseCharset($sCharset)
	{
		switch($sCharset) 
		{
			case 'utf8':
				return self::UTF_8;
				
			case 'latin1':
				return self::ISO_8859_15;
		
			default:
				return '';
		}
	}
}


/**
* @internal SEC1 PR 18/01/2007 FULL
*/

/**
 * Returns a string containing the time formatted according to the user's preferences
 * 
 * @access  public 
 * @return  string	formatted time
 * @param   int	$time	unix timestamp
 */
function bab_time($time)
	{
	if( $time < 0)
		return "";
	return date($GLOBALS['babTimeFormat'], $time);
	}

/**
 * Returns a unix timestamp corresponding to the string $time formatted as a MYSQL DATETIME
 * 
 * @access  public 
 * @return  int	unix timestamp
 * @param   string	$time	(eg. '2006-03-10 17:37:02')
 */
function bab_mktime($time)
	{
	$arr = explode(" ", $time); //Split days and hours
	if ('0000-00-00' == $arr[0] || '' == $arr[0]) {
		return -1;
	}
	$arr0 = explode("-", $arr[0]); //Split year, month et day
	if (isset($arr[1])) { //If the hours exist we send back days and hours
		$arr1 = explode(":", $arr[1]);
		return mktime( $arr1[0],$arr1[1],$arr1[2],$arr0[1],$arr0[2],$arr0[0]);
		} else { //If the hours do not exist, we send back only days
		return mktime(0,0,0,$arr0[1],$arr0[2],$arr0[0]);
		}
	}

/**
 * Returns a string containing the time formatted according to the format
 * 
 * Formatting options:
 * <pre>
 * %d   A short textual representation of a day, three letters
 * %D   day
 * %j   Day of the month with leading zeros
 * %m   A short textual representation of a month, three letters
 * %M   Month
 * %n   Numeric representation of a month, with leading zeros
 * %Y   A full numeric representation of a year, 4 digits
 * %y   A two digit representation of a year
 * %H   24-hour format of an hour with leading zeros
 * %i   Minutes with leading zeros
 * %S   user short date
 * %L   user long date
 * %T   user time format
 * <pre>
 * 
 * 
 * @access  public 
 * @return  string	formatted time
 * @param   string	$format	desired format
 * @param   int	$time	unix timestamp
 */
function bab_formatDate($format, $time)
{
	global $babDays, $babMonths, $babShortMonths;
	$txt = $format;
	if(preg_match_all("/%(.)/", $format, $m))
		{
		for( $i = 0; $i< count($m[1]); $i++)
			{
			switch($m[1][$i])
				{
				case 'd': /* A short textual representation of a day, three letters */
					$val = mb_substr($babDays[date("w", $time)], 0 , 3);
					break;
				case 'D': /* day */
					$val = $babDays[date("w", $time)];
					break;
				case 'j': /* Day of the month with leading zeros */ 
					$val = date("d", $time);
					break;
				case 'm': /* A short textual representation of a month, three letters */
					$val = $babShortMonths[date("n", $time)];
					break;
				case 'M': /* Month */
					$val = $babMonths[date("n", $time)];
					break;
				case 'n': /* Numeric representation of a month, with leading zeros */
					$val = date("m", $time);
					break;
				case 'Y': /* A full numeric representation of a year, 4 digits */
					$val = date("Y", $time);
					break;
				case 'y': /* A two digit representation of a year */
					$val = date("y", $time);
					break;
				case 'H': /* 24-hour format of an hour with leading zeros */
					$val = date("H", $time);
					break;
				case 'i': /* Minutes with leading zeros */
					$val = date("i", $time);
					break;
				case 'S': /* user short date  */
					$val = bab_shortDate($time, false);
					break;
				case 'L': /* user long date  */
					$val = bab_longDate($time, false);
					break;
				case 'T': /* user time format  */
					$val = bab_time($time);
					break;
				}
			if( isset($val))
				{
				$txt = preg_replace("/".preg_quote($m[0][$i])."/", $val, $txt);
				}
			}
		}
	return $txt;
}

/**
 * Returns a string containing the time formatted according to the user's preferences
 * 
 * @access  public 
 * @return  string	formatted time
 * @param   int	$time	unix timestamp
 * @param   boolean $hour	(true == 'Ven 17 Mars 2006',
 *							false == 'Ven 17 Mars 2006 10:11')
 */
function bab_longDate($time, $hour=true)
	{
	if( $time < 0)
		return "";

	if( !isset($GLOBALS['babLongDateFormat']))
		{
		$GLOBALS['babLongDateFormat'] = bab_getDateFormat("ddd dd MMMM yyyy");
		$GLOBALS['babTimeFormat'] = bab_getTimeFormat("HH:mm");
		}

	if( !$hour )
		{
		return bab_formatDate($GLOBALS['babLongDateFormat'], $time );
		}
	else
		{
		return bab_formatDate($GLOBALS['babLongDateFormat'], $time )." ".date($GLOBALS['babTimeFormat'], $time);
		}
	}


/**
 * Returns a string containing the time formatted according to the user's preferences
 * 
 * @access  public 
 * @return  string	formatted time
 * @param   int	$time	unix timestamp
 * @param   boolean $hour	(true == '17/03/2006',
 *							false == '17/03/2006 10:11'
 */
function bab_shortDate($time, $hour=true)
	{
	if( $time < 0)
		return "";

	if( !isset($GLOBALS['babLongDateFormat']))
		{
		$GLOBALS['babLongDateFormat'] = bab_getDateFormat("dd/mm/yyyy");
		}

	if( !$hour )
		{
		return bab_formatDate($GLOBALS['babShortDateFormat'], $time );
		}
	else
		{
		return bab_formatDate($GLOBALS['babShortDateFormat'], $time )." ".date($GLOBALS['babTimeFormat'], $time);
		}
	}


/**
 * @deprecated
 */
function bab_strftime($time, $hour=true)
	{
	return bab_longDate($time, $hour);
	}

/**
 * @deprecated
 */
function bab_editor($content, $editname, $formname, $heightpx=300, $what=3)
	{
	return '<textarea name="'.bab_toHtml($editname).'" cols="50" rows="10">'.bab_toHtml($content).'</textarea>';
	}

/**
 * @deprecated
 */
function bab_editor_record(&$str)
	{
	
	global $babDB;
	$str = eregi_replace("((href|src)=['\"]?)".$GLOBALS['babUrl'], "\\1", $str);

	if (!$arr = $babDB->db_fetch_array($babDB->db_query("SELECT * FROM ".BAB_SITES_EDITOR_TBL." WHERE id_site='".$babDB->db_escape_string($GLOBALS['babBody']->babsite['id'])."'")))
		{
		return;
		}

	if ($arr['filter_html'] == 0)
		{
		return;
		}

	$allowed_tags = explode(' ',$arr['tags']);
	$allowed_tags = array_flip($allowed_tags);

	$allowed_attributes = explode(' ',$arr['attributes']);
	$allowed_attributes = array_flip($allowed_attributes);

	$worked = array();

	preg_match_all("/<\/?([^>]+?)\/?>/i",$str,$out);

	$nbtags = count($out[0]);
	for($i = 0; $i < $nbtags ; $i++)
		{
		$tag  = &$out[0][$i];
		
		list($tmp) = explode(' ',trim($out[1][$i]));
		$name = mb_strtolower($tmp);

		if (!isset($worked[$tag]))
			{
			$worked[$tag] = 1;
			if (isset($allowed_tags[$name]))
				{
				// work on attributes
				preg_match_all("/(\w+)\s*=\s*([\"'])(.*?)\\2/", $out[1][$i], $elements);

				$worked_attributes = array();

				for($j = 0 ; $j < count($elements[0]) ; $j++ )
					{
					$att_elem = &$elements[0][$j];
					$att_name = mb_strtolower($elements[1][$j]);

					if (!empty($att_name) && !isset($allowed_attributes[$att_name]))
						{
						$worked_attributes[$att_elem] = 1;
						$replace_tag = str_replace($att_elem,'',$tag);
						$str = preg_replace("/".preg_quote($tag,"/")."/", $replace_tag, $str);
						}


					if (!empty($att_name) && isset($allowed_attributes[$att_name]) && $att_name == 'href' && $arr['verify_href'] == 1)
						{
						$worked_attributes[$att_elem] = 1;
						$clean_href = ereg_replace("[\"']([^(http|ftp|#|".preg_quote(basename($_SERVER['SCRIPT_NAME'])).")].*)[\"']", '"#"', $att_elem);
						$replace_tag = str_replace($att_elem, $clean_href, $tag);

						$str = preg_replace("/".preg_quote($tag,"/")."/", $replace_tag, $str);

						}
					}
				}
			else
				{
				$str = preg_replace("/".preg_quote($tag,"/")."/", ' ', $str);
				}
			}
		}
	}

/**
 * get browser OS from windows, macos, linux
 * @return string
 */
function bab_browserOS()
	{
	if (!isset($_SERVER['HTTP_USER_AGENT'])) {
		return '';
		}

	$HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];

	if (false !== mb_strrpos(mb_strtolower($HTTP_USER_AGENT), "windows"))
		{
	 	return "windows";
		}
	if (false !== mb_strrpos(mb_strtolower($HTTP_USER_AGENT), "mac"))
		{
		return "macos";
		}
	if (false !== mb_strrpos(mb_strtolower($HTTP_USER_AGENT), "linux"))
		{
		return "linux";
		}
	return "";
	}


/**
 * get browser name from konqueror, opera, msie, nn6, nn4
 * @return string
 */
function bab_browserAgent()
	{
	if (!isset($_SERVER['HTTP_USER_AGENT'])) {
		return '';
		}

	$HTTP_USER_AGENT = $_SERVER['HTTP_USER_AGENT'];

	if (false !== mb_strrpos(mb_strtolower($HTTP_USER_AGENT), "konqueror"))
		{
		return "konqueror";
		}
	if(false !== mb_strrpos(mb_strtolower($HTTP_USER_AGENT), "opera"))
		{
		return "opera";
		}
	if(false !== mb_strrpos(mb_strtolower($HTTP_USER_AGENT), "msie"))
		{
		return "msie";
		}
	if(false !== mb_strrpos(mb_strtolower($HTTP_USER_AGENT), "mozilla"))
		{
		if(false !== mb_strrpos(mb_strtolower($HTTP_USER_AGENT), "gecko"))
			return "nn6";
		else
			return "nn4";
		}
	return "";
	}


/**
 * get browser version with format X.XX or X.X or 0
 * @return string | 0
 */
function bab_browserVersion()
	{
	if (!isset($_SERVER['HTTP_USER_AGENT'])) {
		return 0;
		}

	$tab = explode(";", $_SERVER['HTTP_USER_AGENT']);
	if( ereg("([^(]*)([0-9].[0-9]{1,2})",$tab[1],$res))
		{
		return trim($res[2]);
		}
	return 0;
	}


function bab_translate($str, $folder = "", $lang="")
	{
	static $babLA = array();

	if( empty($lang)) {
		if (!isset($GLOBALS['babLanguage'])) {
			$lang = 'en';
		} else {
			$lang = $GLOBALS['babLanguage'];
		}
	}

	if( empty($lang) || empty($str))
		return $str;

	if( !empty($folder))
		$tag = $folder."/".$lang;
	else
		$tag = "bab/".$lang;

	if( !isset($babLA[$tag])) {

		babLoadLanguage($lang, $folder, $babLA[$tag]);
		
		if (!isset($babLA[$tag])) {
			$babLA[$tag] = array();
		}
	}

	if(isset($babLA[$tag][$str]))
		{
			return $babLA[$tag][$str];
		}
	else
		{
			return $str;
		}
	}

function bab_isUserAdministrator()
	{
	global $babBody;
	return $babBody->isSuperAdmin;
	}

function bab_isUserGroupManager($grpid="")
	{
	global $babBody, $BAB_SESS_USERID;
	if( empty($BAB_SESS_USERID))
		return false;

	reset($babBody->ovgroups);
	while( $arr=each($babBody->ovgroups) ) 
		{ 
		if( $arr[1]['manager'] == $GLOBALS['BAB_SESS_USERID'])
			{
			if( empty($grpid))
				{
				return true;
				}
			else if( $arr[1]['id'] == $grpid )
				{
				return true;
				}
			}
		}
	}

/**
* Return the username of a given user
*
* @param integer	$iIdUser			User identifier
* @param boolean	$bComposeUserName	If true the username will 
* 										be composed	
* 
* @return	mixed	If $bComposeUserName is true the retun value 
* 					is a string, the string is a concatenation of
* 					the firstname and lastname. The meaning depend 
* 					of ovidentia configuration.
* 					If $bComposeUserName is false the return value 
* 					is an array with two keys (firstname, lastname)   
*/
function bab_getUserName($iIdUser, $bComposeUserName = true)
{
	include_once dirname(__FILE__).'/userinfosincl.php';

	if (true === $bComposeUserName) {
		return bab_userInfos::composeName($iIdUser);
	} else {
		return bab_userInfos::arrName($iIdUser);
	}
}


/**
 * Get Email address
 * @param	int	$id
 * @return string
 */
function bab_getUserEmail($id)
	{

	include_once dirname(__FILE__).'/userinfosincl.php';
	if ($row = bab_userInfos::getRow($id)) {
		return $row['email'];
		}
	
	return '';
	}

/**
 * @return string
 */
function bab_getUserNickname($id)
	{
	include_once dirname(__FILE__).'/userinfosincl.php';
	if ($row = bab_userInfos::getRow($id)) {
		return $row['nickname'];
		}
	
	return '';
	}



function bab_getUserSetting($id, $what)
	{
	global $babDB;
	$query = "select ".$babDB->db_escape_string($what)." from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr[$what];
		}
	else
		{
		return "";
		}
	}

function bab_getPrimaryGroupId($userid)
	{
	global $babDB;
	if( empty($userid) || $userid == 0 )
		return "";
	$query = "select id_group from ".BAB_USERS_GROUPS_TBL." where id_object='".$babDB->db_escape_string($userid)."' and isprimary='Y'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['id_group'];
		}
	else
		{
		return "";
		}
	}


/**
 * Get groups members
 * @param	int|array	$ids	id_group or an array of id_group
 * @return false|array
 */
function bab_getGroupsMembers($ids)
	{
	global $babDB;
	if (!is_array($ids))
		{
		$ids = array($ids);
		}

	if( is_array($ids) && count($ids) > 0 )
		{
		if( in_array(BAB_REGISTERED_GROUP, $ids))
			{
			$req = "SELECT id, email, firstname, lastname FROM ".BAB_USERS_TBL." where disabled='0' and is_confirmed='1'";
			}
		else
			{
			global $babBody;

			foreach($ids as $idg)
				{
				if ($babBody->ovgroups[$idg]['nb_groups'] > 0)
					{
					$res = $babDB->db_query("SELECT id_group FROM ".BAB_GROUPS_SET_ASSOC_TBL." WHERE id_set='".$babDB->db_escape_string($idg)."'");
					while ($arr = $babDB->db_fetch_assoc($res))
						{
						$ids[] = $arr['id_group'];
						}
					}
				}

			$req = "SELECT distinct u.id, u.email, u.firstname, u.lastname FROM ".BAB_USERS_GROUPS_TBL." g, ".BAB_USERS_TBL." u WHERE u.disabled='0' and u.is_confirmed='1' and g.id_group IN (".$babDB->quote($ids).") AND g.id_object=u.id";
			}

		
		$res = $babDB->db_query($req);
		$users = array();
		if( $res && $babDB->db_num_rows($res) > 0)
			{
			$i = 0;
			while ($arr = $babDB->db_fetch_array($res))
				{
				$users[$i]['id'] = $arr['id'];
				$users[$i]['name'] = bab_composeUserName($arr['firstname'],$arr['lastname']);
				$users[$i]['email'] = $arr['email'];
				$i++;
				}
			return $users;
			}
		}

		return false;
	}


/**
 * Test if the user is member of a group
 * This function accept a group id since 6.1.1
 * Before 6.1.1 the function return 0 if the user is not member
 * After 6.1.1 if the current user is logged out the function can return BAB_ALLUSERS_GROUP or BAB_UNREGISTERED_GROUP or false
 * @since 	6.1.1
 * @param	int|string	$group		group id or group name
 * @return 	false|int				group id or false if the user is not a member
 */
function bab_isMemberOfGroup($group, $userid="")
{
	global $BAB_SESS_USERID, $babDB;
	if(empty($group)) {
		return false;
	}
		
	if( $userid == "")
		$userid = $BAB_SESS_USERID;
		
	if (is_numeric($group)) {
		$id_group = $group;
	} else {
		$req = "select id from ".BAB_GROUPS_TBL." where name='".$babDB->db_escape_string($group)."'";
		$res = $babDB->db_query($req);
		if( $res && $babDB->db_num_rows($res) > 0)
			{
			$arr = $babDB->db_fetch_array($res);
			$id_group = $arr['id'];
		} else {
			return false;	
		}
	}
	
	switch($id_group) {
		case BAB_ALLUSERS_GROUP:
			return BAB_ALLUSERS_GROUP;
			
		case BAB_REGISTERED_GROUP:
			return $userid ? BAB_REGISTERED_GROUP : false;
			
		case BAB_UNREGISTERED_GROUP:
			return $userid ? false : BAB_UNREGISTERED_GROUP;
			
		default:
			$req = "select id from ".BAB_USERS_GROUPS_TBL." where id_object='".$babDB->db_escape_string($userid)."' and id_group='".$babDB->db_escape_string($id_group)."'";
			$res = $babDB->db_query($req);
			if( $res && $babDB->db_num_rows($res) > 0)
				return $id_group;
			else
				return false;
	}
}

function bab_getUserIdByEmail($email)
	{
	global $babDB;
	$query = "select id from ".BAB_USERS_TBL." where email LIKE '".$babDB->db_escape_string($email)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['id'];
		}
	else
		{
		return 0;
		}
	}

function bab_getUserIdByNickname($nickname)
	{
	global $babDB;
	$res = $babDB->db_query("select id from ".BAB_USERS_TBL." where nickname='".$babDB->db_escape_string($nickname)."'");
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['id'];
		}
	else
		{
		return 0;
		}
	}

function bab_getUserId( $name )
	{
	global $babDB;
	$replace = array( " " => "", "-" => "");
	$hash = md5(mb_strtolower(strtr($name, $replace)));
	$query = "select id from ".BAB_USERS_TBL." where hashname='".$babDB->db_escape_string($hash)."'";	
	$res = $babDB->db_query($query);
	if( $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return $arr['id'];
		}
	else
		return 0;
	}
	
function bab_getUserGroups($id = "")
	{
	global $babBody, $babDB;
	$arr = array('id' => array(), 'name' => array());
	if( empty($id))
		{
		for( $i = 0; $i < count($babBody->usergroups); $i++ )
			{
			if( $babBody->usergroups[$i] != BAB_REGISTERED_GROUP && $babBody->usergroups[$i] != BAB_UNREGISTERED_GROUP && $babBody->usergroups[$i] != BAB_ALLUSERS_GROUP)
				{
				$arr['id'][] = $babBody->usergroups[$i];
				$nm = $babBody->getGroupPathName($babBody->usergroups[$i]);
				if( empty($nm))
					{
					$nm =  $babBody->getSetOfGroupName($babBody->usergroups[$i]);
					}
				$arr['name'][] = $nm;
				}
			}
		return $arr;
		}
	if( !empty($id))
		{
		$res = $babDB->db_query("select id_group from ".BAB_USERS_GROUPS_TBL." where id_object=".$babDB->db_escape_string($id)."");
		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			while( $r = $babDB->db_fetch_array($res))
				{
				$arr['id'][] = $r['id_group'];
				$nm = $babBody->getGroupPathName($r['id_group']);
				if( empty($nm))
					{
					$nm =  $babBody->getSetOfGroupName($r['id_group']);
					}
				$arr['name'][] = $nm;
				}
			}
		}
	return $arr;
	}


function bab_composeUserName( $F, $L)
	{
	global $babBody;
	if( isset($babBody->nameorder))
		return trim(sprintf("%s %s", ${$babBody->nameorder[0]}, ${$babBody->nameorder[1]}));
	else
		return trim(sprintf("%s %s", $F, $L));
	}

/**
 * Connexion status for current user 
 * @return boolean
 */
function bab_userIsloggedin()
	{
	global $BAB_SESS_NICKNAME, $BAB_HASH_VAR, $BAB_SESS_HASHID,$BAB_SESS_LOGGED;

	if (isset($BAB_SESS_LOGGED))
		{
		return $BAB_SESS_LOGGED;
		}
	if (!empty($BAB_SESS_NICKNAME) && !empty($BAB_SESS_HASHID))
		{
		$hash=md5($BAB_SESS_NICKNAME.$BAB_HASH_VAR);
		if ($hash == $BAB_SESS_HASHID)
			{
			$BAB_SESS_LOGGED=true;
			}
		else
			{
			$BAB_SESS_LOGGED=false;
			}
		}
	else
		{
		$BAB_SESS_LOGGED=false;
		}
    return $BAB_SESS_LOGGED;
	}



/**
 * Checks that the specified user can access the object $idobject according to the acl table $table.
 * If $iduser is empty, the check is performed for anonymous users.
 * 
 * @param string	$table		The acl table.
 * @param int		$idobject	The id of the object for which the access is checked.
 * @param mixed		$userId		The user id or '' for anonymous users.	
 *
 * @return bool
 */
function bab_isAccessValidByUser($table, $idobject, $iduser)
{
	$objects = bab_getAccessibleObjects($table, $iduser);
	return array_key_exists($idobject, $objects);
}




/**
 * Checks that the specified user can access the object $idobject according to the acl table $table.
 * If $iduser is empty, the check is performed for the current user.
 * 
 * @param string	$table		The acl table.
 * @param int		$idobject	The id of the object for which the access is checked.
 * @param mixed		$userId		The user id or '' for the current user.
 * 
 * @return bool
 */
function bab_isAccessValid($table, $idobject, $iduser='')
{
	if( $iduser != '')
		{
			include_once $GLOBALS['babInstallPath']."admin/acl.php";
		
			$users = aclGetAccessUsers($table, $idobject);
		
			if( isset($users[ $iduser]))
			{
				return true;
			}
			return false;
//		return bab_isAccessValidByUser($table, $idobject, $iduser);
		}

	if( !isset($_SESSION['bab_groupAccess']['acltables'][$table]))
		{
		bab_getUserIdObjects($table);
		}

	return isset($_SESSION['bab_groupAccess']['acltables'][$table][$idobject]);
}




/**
 * Get the list of id_object accessible by the specified user.
 * If $userId is empty, the check is performed for anonymous users.
 * The id_object is returned in key and in the value of the result array.
 *
 * @param string	$table		The acl table.
 * @param mixed		$userId		The user id or '' for anonymous users.	
 * @return array
 */
function bab_getAccessibleObjects($table, $userId)
{
	global $babBody, $babDB;
	$objects = array();

	if (empty($userId)) {
		// For anonymous users, we just fetch objects accessible to all users or unregistered users.
		$userGroupIds = array(BAB_ALLUSERS_GROUP, BAB_UNREGISTERED_GROUP);
		$sql = 'SELECT id_object FROM '.$babDB->backTick($table).' WHERE id_group IN('.$babDB->quote($userGroupIds).')';
		$res = $babDB->db_query($sql);
		while ($object = $babDB->db_fetch_assoc($res)) {
			$objects[$object['id_object']] = $object['id_object'];
		}
		return $objects;
	}

	$userGroups = bab_getUserGroups($userId);
	$userGroupIds = $userGroups['id'];
	$userGroupIds[] = BAB_REGISTERED_GROUP;
	$userGroupIds[] = BAB_ALLUSERS_GROUP;
	
	$res = $babDB->db_query("SELECT t.id_object, t.id_group, g.nb_groups FROM ".$babDB->backTick($table)." t left join ".BAB_GROUPS_TBL." g on g.id=t.id_group");

	while ($object = $babDB->db_fetch_assoc($res)) {
		if( $object['nb_groups'] !== null )
		{
		$rs=$babDB->db_query("select id_group from ".BAB_GROUPS_SET_ASSOC_TBL." where id_set=".$babDB->quote($object['id_group']));
		while( $rr = $babDB->db_fetch_array($rs))
			{
			if( in_array($rr['id_group'], $userGroupIds))
				{
					$objects[$object['id_object']] = $object['id_object'];
				}
			}
		}
		elseif ( ($object['id_group'] < BAB_ACL_GROUP_TREE && in_array($object['id_group'], $userGroupIds)) || bab_isMemberOfTree($object['id_group'] - BAB_ACL_GROUP_TREE, $userId)) {
			$objects[$object['id_object']] = $object['id_object'];
		}		
	}

	return $objects;
}


/**
 * Get the list of id_object accessible by the current user
 * The id_object is stored in key and in the value
 * @return array
 */
function bab_getUserIdObjects($table)
{
global $babBody, $babDB;
if( !isset($_SESSION['bab_groupAccess']['acltables'][$table]))
	{
	$_SESSION['bab_groupAccess']['acltables'][$table] = array();
	
	$res = $babDB->db_query("SELECT t.id_object, t.id_group, g.nb_groups FROM ".$babDB->backTick($table)." t left join ".BAB_GROUPS_TBL." g on g.id=t.id_group");

	while ($row = $babDB->db_fetch_assoc($res)) {
		if( $row['nb_groups'] !== null )
		{
		$rs=$babDB->db_query("select id_group from ".BAB_GROUPS_SET_ASSOC_TBL." where id_set=".$babDB->quote($row['id_group']));
		while( $rr = $babDB->db_fetch_array($rs))
			{
			if( in_array($rr['id_group'], $babBody->usergroups))
				{
					$_SESSION['bab_groupAccess']['acltables'][$table][$row['id_object']] = $row['id_object'];
				}
			}
		}
		elseif ( ($row['id_group'] < BAB_ACL_GROUP_TREE && in_array($row['id_group'], $babBody->usergroups)) || bab_isMemberOfTree($row['id_group'] - BAB_ACL_GROUP_TREE)) {
			$_SESSION['bab_groupAccess']['acltables'][$table][$row['id_object']] = $row['id_object'];
		}		
	}		
	}

	return $_SESSION['bab_groupAccess']['acltables'][$table];
}


/**
 * @deprecated
 * @see aclGetAccessUsers() in admin/acl.php
 * Il manque la partie pour les ensemble de groupes
 */
function bab_getUsersAccess($table)
{
	global $babBody, $babDB;
	
	trigger_error('deprecated function bab_getUsersAccess');
	$babBody->addError('deprecated function bab_getUsersAccess');

	$ids = array();

	$res = $babDB->db_query("select id_group from ".$babDB->db_escape_string($table));
	while($row = $babDB->db_fetch_array($res))
		{
		$ids[] = $row['id_group'];
		}
	return bab_getGroupsMembers($ids);
}


/**
 * Get group list for access right
 * @param	string	$table
 * @param	int		$idobject
 * @return 	array
 */
function bab_getGroupsAccess($table, $idobject)
{
	global $babBody, $babDB;

	include_once $GLOBALS['babInstallPath']."admin/acl.php";
	$groups = aclGetAccessGroups($table, $idobject);
	return array_keys($groups);
}


/**
 * Get the calendar popup url for an href attribute
 * @deprecated	use the bab_dialog api in bab_dialog.js
 * @return string	url of the calendar popup
 */
function bab_calendarPopup($callback, $month='', $year='', $low='', $high='')
{
	$url = $GLOBALS['babUrlScript']."?tg=month&amp;callback=".$callback;
	if( !empty($month))
	{
		$url .= "&amp;month=".$month;
	}
	if( !empty($year))
	{
		$url .= "&amp;year=".$year;
	}
	if( !empty($low))
	{
		$url .= "&amp;ymin=".$low;
	}
	if( !empty($high))
	{
		$url .= "&amp;ymax=".$high;
	}
	return "javascript:Start('".$url."','OVCalendarPopup','width=250,height=250,status=no,resizable=no,top=200,left=200')";
}


/**
 * Create a directory
 */
function bab_mkdir($path, $mode='')
{
	if( mb_substr($path, -1) == "/" )
		{
		$path = mb_substr($path, 0, -1);
		}
	$umask = umask($GLOBALS['babUmaskMode']);
	if( $mode === '' )
	{
		$mode = $GLOBALS['babMkdirMode'];
	}
	$res = mkdir($path, $mode);
	if (!$res) {
		include_once $GLOBALS['babInstallPath'] . 'utilit/devtools.php';
		bab_debug_print_backtrace();
	}
	umask($umask);
	return $res;
}

/**
 * since ovidentia 5.8.6 quotes are always striped
 * @deprecated
 */
function bab_isMagicQuotesGpcOn()
	{
	return false;
	}



/**
 * Get available languages
 * The returned values are extracted from the list of translation files
 * @return array	list of languages codes
 */
function bab_getAvailableLanguages()
	{
	$langs = array();
	if( is_dir($GLOBALS['babInstallPath'].'lang/'))
	{
	$h = opendir($GLOBALS['babInstallPath'].'lang/'); 
	while ( $file = readdir($h))
		{ 
		if ($file != "." && $file != "..")
			{
			if( eregi("lang-([^.]*)", $file, $regs))
				{
				if( $file == 'lang-'.$regs[1].'.xml')
					$langs[] = $regs[1]; 
				}
			} 
		}
	closedir($h);
	}

	if( is_dir('lang/'))
	{
	$h = opendir('lang/'); 
	while ( $file = readdir($h))
		{ 
		if ($file != "." && $file != "..")
			{
			if( eregi('lang-([^.]*)', $file, $regs))
				{
				if( $file == 'lang-'.$regs[1].'.xml' && !in_array($regs[1], $langs))
					$langs[] = $regs[1]; 
				}
			} 
		}
	closedir($h);
	}
	return $langs;
	}


/**
 * merge a template with a class object instance
 *
 * @param	object	$class
 * @param	string	$file		file path in a template directory (in the core or in the skin)
 * @param	string	[$section]	name of the section in file (begin and end tag)
 *
 * @return string
 */
function bab_printTemplate( &$class, $file, $section="")
	{
	//bab_debug('Template file : '.$file.'<br />'.'Section in template file : '.$section);
	
	global $babInstallPath, $babSkinPath;
	$tplfound = false;
	
	if( isset($GLOBALS['babUseNewTemplateParser']) && $GLOBALS['babUseNewTemplateParser'] === false)
	{
		$tpl = new babTemplate(); /* old template parser */
	}
	else
	{
		$tpl = new bab_Template();
		if (bab_TemplateCache::get('skins/'.$GLOBALS['babSkin'].'/templates/'. $file, $section)) {
			return $tpl->printTemplate($class, 'skins/'.$GLOBALS['babSkin'].'/templates/'. $file, $section);
		}
		if (bab_TemplateCache::get($babSkinPath.'templates/'.$file, $section)) {
			return $tpl->printTemplate($class, $babSkinPath.'templates/'.$file, $section);
		}
		if (bab_TemplateCache::get($babInstallPath.'skins/ovidentia/templates/'.$file, $section)) {
			return $tpl->printTemplate($class, $babInstallPath.'skins/ovidentia/templates/'.$file, $section);
		}
	}

	$filepath = "skins/".$GLOBALS['babSkin']."/templates/". $file;
	if( file_exists( $filepath ) )
		{
		if( empty($section))
			{
				return $tpl->printTemplate($class,$filepath, '');
			}

		$arr = $tpl->getTemplates($filepath);
		$tplfound = in_array($section, $arr);
		}
	
	if( !$tplfound )
		{
		$filepath = $babSkinPath."templates/". $file;
		if( file_exists( $filepath ) )
			{
			if( empty($section))
				{
					return $tpl->printTemplate($class,$filepath, '');
				}

			$arr = $tpl->getTemplates($filepath);
			$tplfound = in_array($section, $arr);
			}

		}

	if( !$tplfound )
		{
		$filepath = $babInstallPath."skins/ovidentia/templates/". $file;
		if( file_exists( $filepath ) )
			{
			if( empty($section))
				{
					return $tpl->printTemplate($class,$filepath, '');
				}

			$arr = $tpl->getTemplates($filepath);
			$tplfound = in_array($section, $arr);
			}

		}

	if( $tplfound ) {
//		$start = microtime(true);
		$t = $tpl->printTemplate($class,$filepath, $section);
//		bab_debug($filepath . ':' . $section . '=' . (int)((microtime(true) - $start) * 1000000));
		return $t;
//		return $tpl->printTemplate($class,$filepath, $section);
	} else {
		return '';
	}
}






/**
 * Get the actives sessions
 * Users currently connected to the site
 * each session is an array with keys :
 *
 * <ul>
 * 	<li>id_user 			: 0 if user is annonymous or supperior if the user is logged</li>
 * 	<li>user_name 			: empty string or user name if the user is logged</li>
 * 	<li>user_email 			: empty string or email if the user is logged</li>
 * 	<li>session_id 			: php session id</li>
 * 	<li>remote_addr 		: IP address of the user</li>
 * 	<li>forwarded_for 		: IP address or empty string, can be the adress of a proxy...</li>
 *	<li>registration_date 	: registration date in timestamp of the user in ovidentia</li>
 * 	<li>previous_login_date : login date of the previous session as timestamp</li>
 *	<li>login_date 			: login date of the current session as timestamp</li>
 *	<li>last_hit_date 		: last refresh date of the user as timestamp</li>
 * </ul>
 *
 * @return array
 */
function bab_getActiveSessions()
{
	global $babDB;
	$output = array();
	$res = $babDB->db_query("SELECT l.id_user,
								l.sessid,
								l.remote_addr,
								l.forwarded_for,
								UNIX_TIMESTAMP(l.dateact) dateact,
								u.firstname,
								u.lastname,
								u.email,
								UNIX_TIMESTAMP(u.lastlog) lastlog,
								UNIX_TIMESTAMP(u.datelog) datelog,
								UNIX_TIMESTAMP(u.date) registration  
								FROM ".BAB_USERS_LOG_TBL." l 
								LEFT JOIN ".BAB_USERS_TBL." u ON u.id=l.id_user");

	while($arr = $babDB->db_fetch_array($res))
		{
		$output[] = array(
						'id_user' => $arr['id_user'],
						'user_name' => bab_composeUserName($arr['firstname'], $arr['lastname']),
						'user_email' => $arr['email'],
						'session_id' => $arr['sessid'],
						'remote_addr' => $arr['remote_addr'] != 'unknown' ? $arr['remote_addr']  : '',
						'forwarded_for' => $arr['forwarded_for'] != 'unknown' ? $arr['forwarded_for']  : '',
						'registration_date' => $arr['registration'],
						'previous_login_date' => $arr['lastlog'],
						'login_date' => $arr['datelog'],
						'last_hit_date' => $arr['dateact'],
							);
		}
	return $output;
}

/**
 * Get mime type by filename extention
 */
function bab_getFileMimeType($file)
{
	global $babDB;
	$mime = "application/octet-stream";
	$iPos = mb_strrpos($file, ".");
    if (false !== $iPos)
        {
        $ext = mb_substr($file,$iPos+1);
		$res = $babDB->db_query("select * from ".BAB_MIME_TYPES_TBL." where ext='".$babDB->db_escape_string($ext)."'");
		if( $res && $babDB->db_num_rows($res) > 0)
			{
			$arr = $babDB->db_fetch_array($res);
			$mime = $arr['mimetype'];
			}
		}
	return $mime;
}

/* API Directories */

/**
 * @deprecated
 * @see bab_getDirEntry
 */
function bab_getUserDirFields($id = false)
	{
	trigger_error('This function is deprecated, please use bab_getDirEntry()');
	
	global $babDB;
	if (false == $id) $id = &$GLOBALS['BAB_SESS_USERID'];
	$query = "select * from ".BAB_DBDIR_ENTRIES_TBL." where id_user='".$babDB->db_escape_string($id)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0) {
		return $babDB->db_fetch_assoc($res);
		}
	else
		return array();
	}


/** 
 * Get a directory entry or a list of entries
 *
 * BAB_DIR_ENTRY_ID_USER		: $id is a user id
 * BAB_DIR_ENTRY_ID				: $id is a directory entry
 * BAB_DIR_ENTRY_ID_DIRECTORY	: liste des champs de l'annuaire
 * BAB_DIR_ENTRY_ID_GROUP		: liste des champs de l'annuaire de groupe
 *
 * @param	false|int	$id
 * @param	int			$type
 * @param	NULL|int	$id_directory
 * @return array
 */

function bab_getDirEntry($id = false, $type = BAB_DIR_ENTRY_ID_USER, $id_directory = NULL ) {
	include_once $GLOBALS['babInstallPath']."utilit/dirincl.php";
	return getDirEntry($id, $type, $id_directory, true);
	}
	
	
/** 
 * Get a directory entry or a list of entries
 * without acces control
 *
 * BAB_DIR_ENTRY_ID_USER		: $id is a user id
 * BAB_DIR_ENTRY_ID				: $id is a directory entry
 * BAB_DIR_ENTRY_ID_DIRECTORY	: liste des champs de l'annuaire
 * BAB_DIR_ENTRY_ID_GROUP		: liste des champs de l'annuaire de groupe
 *
 * @param	false|int	$id
 * @param	int			$type
 * @param	NULL|int	$id_directory
 * @return array
 */

function bab_admGetDirEntry($id = false, $type = BAB_DIR_ENTRY_ID_USER, $id_directory = NULL ) {
	include_once $GLOBALS['babInstallPath']."utilit/dirincl.php";
	return getDirEntry($id, $type, $id_directory, false);
	}
	


/**
 * return an array of directory entries using a search on fields
 * The access rights are not tested but directory entries linked to a disabled user will be ignored
 *
 * @param	int		[$id_directory]		the id of the directory
 * @param	array	[$likefields]		array of filed/like string ( array('sn' => 'admin', 'email'=> '%cantico.fr', 'babdirf27'=>'123') for example )
 * @param	bool	[$and]				true to use AND operator / false for OR operator
 * @return 	array
 */
function bab_searchDirEntriesByField($id_directory, $likefields, $and = true) {
	include_once $GLOBALS['babInstallPath']."utilit/dirincl.php";
	return searchDirEntriesByField($id_directory, $likefields, $and);
	}



/**
 * List of viewables directories for the user
 * For each directory, you will get an array with keys : 
 * <ul>
 * 	<li>id : the ID in table</li>
 *  <li>name</li>
 *  <li>description</li>
 *  <li>entry_id_directory : each entry in this directory will contain the value in the id_directory column, > 0 if the directory is not a group directory</li>
 *  <li>id_group : each entry in this directory will contain the value in the id_group column, > 0 if the directory is a group directory</li>
 * </ul>
 *
 * @param	bool				$accessCtrl		test access rights on directories, true by default
 * @param	int | false			$delegationid	filter the result by delegation
 * @return array				Each key of the returned array is an id_directory
 */ 
function bab_getUserDirectories($accessCtrl = true, $delegationId = false)
{
	include_once $GLOBALS['babInstallPath'].'utilit/dirincl.php';
	return getUserDirectories($accessCtrl, $delegationId);
}

/**
 * return a link to the popup of the directory entry
 * @param	int		[$id]				id_user or id_entry
 * @param	int		[$type]				the type of the $id parameter BAB_DIR_ENTRY_ID_USER | BAB_DIR_ENTRY_ID
 * @param	int		[$id_directory]		if $id is a directory entry
 * @return 	string | false				return false if directory entry is not accessible
 */
function bab_getUserDirEntryLink($id = false, $type = BAB_DIR_ENTRY_ID_USER, $id_directory = false)
{
	include_once $GLOBALS['babInstallPath']."utilit/dirincl.php";
	return getUserDirEntryLink($id, $type, $id_directory);
}


/* API Groups */

/**
 * Get group name
 * @param	int			$id
 * @param	boolean		[$fpn]	full path name
 *
 * @return string
 */
function bab_getGroupName($id, $fpn=true)
	{
	
	$id = (int) $id;
	
	global $babBody;
	if($fpn)
		{
		return $babBody->getGroupPathName($id);
		}
	else
		{
		
		if (BAB_ALLUSERS_GROUP === $id || BAB_REGISTERED_GROUP === $id || BAB_UNREGISTERED_GROUP === $id || BAB_ADMINISTRATOR_GROUP === $id) {
			return bab_translate($babBody->ovgroups[$id]['name']);
		}
		
		
		return $babBody->ovgroups[$id]['name'];
		}
	}




/**
 * Get ovidentia groups
 * The returned groups are stored in an array with three keys : id, name, description
 * in each keys, a list of group is stored with ordered numeric keys
 * the default value for the $all parameter is true
 * 
 * @param	int			$parent		parent group in tree
 * @param	boolean		$all		return one level of groups or groups from all sublevels
 * @return	array
 */
function bab_getGroups($parent=BAB_REGISTERED_GROUP, $all=true)
	{
	include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";

	$tree = new bab_grptree();
	$groups = $tree->getGroups($parent, '%2$s > ', $all);
	$arr = array();
	foreach ($groups as $row)
		{
		$arr['id'][] = $row['id'];
		$arr['name'][] = $row['name'];
		$arr['description'][] = $row['description']; 
		}

	return $arr;
	}

function bab_createGroup( $name, $description, $managerid, $parent = 1)
{
	include_once $GLOBALS['babInstallPath']."utilit/grpincl.php";
	return bab_addGroup($name, $description, $managerid, 0, $parent);
}

function bab_updateGroup( $id, $name, $description, $managerid)
{
	include_once $GLOBALS['babInstallPath']."utilit/grpincl.php";
	return bab_updateGroupInfo($id, $name, $description, $managerid);
}

function bab_removeGroup($id)
{
	include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
	bab_deleteGroup($id);
}


/* API Users */


/**
 * Register a user
 *
 * @param	string		$firstname		mandatory firstname
 * @param	string		$lastname		mandatory lastname
 * @param	string		$middlename		The middlename can be an empty string
 * @param	string		$email			mandatory mail address
 * @param	string		$nickname		mandatory Login ID
 * @param	string		$password1		mandatory password
 * @param	string		$password2		mandatory password verification
 * @param	string		$confirmed		[1|0] 1 : the user is confirmed ; 0 : the user is not confirmed
 * @param	string		&$error			an empty string varaible, the error message will be set in this variable if the user cannot be registered
 * @param	boolean		$bgroup
 *
 * @return 	int|false
 */
function bab_registerUser( $firstname, $lastname, $middlename, $email, $nickname, $password1, $password2, $confirmed, &$error, $bgroup = true)
{
	require_once($GLOBALS['babInstallPath']."utilit/usermodifyincl.php");
	return bab_userModify::addUser( $firstname, $lastname, $middlename, $email, $nickname, $password1, $password2, $confirmed, $error, $bgroup);
}

function bab_attachUserToGroup($iduser, $idgroup)
{
	bab_addUserToGroup($iduser, $idgroup);
}

function bab_detachUserFromGroup($iduser, $idgroup)
{
	bab_removeUserFromGroup($iduser, $idgroup);
}


/**
 * Get user infos from directory and additionnal parameters specific to registered users
 * return all infos necessary to use bab_updateUserById()
 * warning, password is not returned, $info['password_md5'] is returned instead
 *
 * @param	int		$id_user
 *
 * @return 	false|array
 */
function bab_getUserInfos($id_user) {
	include_once $GLOBALS['babInstallPath'].'utilit/dirincl.php';
	include_once $GLOBALS['babInstallPath'].'utilit/userinfosincl.php';

	$directory = getDirEntry($id_user, BAB_DIR_ENTRY_ID_USER, NULL, false);
	
	if (!$directory) {
		return false;
	}
	
	$infos = bab_userInfos::getForDirectoryEntry($id_user);

	if (!$infos) {
		return false;
	}
	
	foreach($directory as $field => $arr) {
		$infos[$field] = $arr['value'];
	}
	
	return $infos;
}


/**
 * Update a user
 * @see bab_getUserInfos()
 * @see bab_fileHandler
 *
 * the 'changepwd' key is not modifiable
 * the 'jpegphoto' can be modified with an instance of bab_fileHandler
 * @since 	6.7.91	add support for jpegphoto key
 *
 * @param	int		$id			ID user
 * @param	array	$info		Array returned by bab_getUserInfos()
 * @param	string	&$error		error message
 * @return 	boolean
 */
function bab_updateUserById($id, $info, &$error)
{
	require_once($GLOBALS['babInstallPath']."utilit/usermodifyincl.php");
	return bab_userModify::updateUserById($id, $info, $error);
}

/**
 * Because of a typing error, we must keep compatibility
 * @deprecated
 */
function bab_uppdateUserById($id, $info, &$error)
{
	return bab_updateUserById($id, $info, $error);
}


/**
 * Update a user by nickname
 */
function bab_updateUserByNickname($nickname, $info, &$error)
{
	$id_user = bab_getUserIdByNickname($nickname);
	if (0 === $id_user) {
		$error = bab_translate("Unknown user");
		return false;
	}
	return bab_updateUserById($id_user, $info, $error);
}



/**#@+
 * Severity levels for bab_debug
 */
define('DBG_TRACE',		 1);
define('DBG_DEBUG',		 2);
define('DBG_INFO',		 4);
define('DBG_WARNING',	 8);
define('DBG_ERROR',		16);
define('DBG_FATAL',		32);
/**#@-*/


/**
 * Log the specified information.
 * 
 * If the file bab_debug.txt is present (same directory as config.php) and writable,
 * the information is appended.  
 * 
 * If $GLOBALS['babDebugLogMinSeverity'] is set, it determines the minimum severity of messages
 * that will be logged in bab_debug.txt. 
 *  
 * 
 * @param mixed		$data		The data to log. If not a string $data is transformed through print_r.
 * @param int		$severity	The severity of the logged information (One of: DBG_TRACE, DBG_DEBUG, DBG_INFO, DBG_WARNING, DBG_ERROR, DBG_FATAL)
 * @param string	$category	A string to categorize the debug information.
 */
function bab_debug($data, $severity = DBG_TRACE, $category = '')
{
	$file = $line = $function = '';

	
	
	
	


	// Here we find information about the file and line where bab_debug was called.
	$backtrace = debug_backtrace();
	$call = array_shift($backtrace);
	if (is_array($call)  && isset($call['file']) && isset($call['line'])) {
		$file = $call['file'];
		$line = $call['line'];
	}

	// Here we find information about the method or function from which bab_debug was called.
	$call = array_shift($backtrace);
	if (is_array($call)) {
		$function = (isset($call['class'])) ? $call['class'] . '::' . $call['function'] : $call['function'];
	}
	
	$message = array(
		'category' => str_replace(' ', '_', $category),
		'severity' => $severity,
		'data' => $data,
		'file' => $file,
		'line' => $line,
		'function' => $function
	);
						 
						 
	if (isset($_COOKIE['bab_debug']) && ((int)$_COOKIE['bab_debug'] & $severity)) {
	
		// We store the information in the global bab_debug_messages that will later be displayed by bab_getDebug
		if (isset($GLOBALS['bab_debug_messages'])) {
			$GLOBALS['bab_debug_messages'][] = $message;
		} else {
			$GLOBALS['bab_debug_messages'] = array($message);
		}
	}

	$debugFilename = 'bab_debug.txt';
	// We immediately log in the bab_debug.txt file.
	if ( (!isset($GLOBALS['babDebugLogMinSeverity']) || ($GLOBALS['babDebugLogMinSeverity'] <= $severity))
		 && file_exists($debugFilename) && is_writable($debugFilename)) {

		$size = 0;
		
		if (is_array($data) || is_object($data)) {
			$size = count($data);
		}

		if (is_string($data)) {
			$size = mb_strlen($data);
		}

		$textinfos = sprintf("type=%s, size=%d\n", gettype($data), $size);
		if (!is_string($data)) {
			$textinfos .= print_r($data, true);
		} else {
			$textinfos .= $data;
		}

		$h = fopen($debugFilename, 'a');
		$date = date('d/m/Y H:i:s');
		$lines = explode("\n", $textinfos);
		foreach ($lines as $text) {
			fwrite($h, $date."\t".$severity."\t".$category."\t".basename($file).'('.$line.')'."\t".$function."\t".$text."\n");
		}
		fwrite($h, "\n");
		fclose($h);
	}
}


/**
 * Returns the html for the debug console, useful for popups
 * 
 * @return string	
 */
function bab_getDebug() {
	if (bab_isUserAdministrator() && isset($GLOBALS['bab_debug_messages'])) {
		include_once $GLOBALS['babInstallPath'].'utilit/devtools.php';
		return bab_f_getDebug();
	}
	return false;
}

/**
 * transform some plain text into html
 * available options :
 * <ul>
 * <li>BAB_HTML_ALL			: a combination of all the options</li>
 * <li>BAB_HTML_ENTITIES	: special characters will be replaced with html entities</li>
 * <li>BAB_HTML_AUTO		: the paragraphs tags will be added only if the text contein some text line-breaks</li>
 * <li>BAB_HTML_P			: double line breaks will be replaced by html paragraphs, if there is no double line breaks, all the text will be in one paragraph</li>
 * <li>BAB_HTML_BR			: Line-breaks will be replaced by html line breaks</li>
 * <li>BAB_HTML_LINKS		: url and email adress will be replaced by links</li>
 * <li>BAB_HTML_JS			: \ and ' and " are encoded for javascript strings, not in BAB_HTML_ALL</li>
 * <li>BAB_HTML_REPLACE		: Replace ovidentia macro $XXX()</li>
 * </ul>
 * @param string $str
 * @param int [$opt] the default value for the option is BAB_HTML_ENTITIES
 * @return string html
 */
function bab_toHtml($str, $option = BAB_HTML_ENTITIES) {
	include_once dirname(__FILE__).'/tohtmlincl.php';
	return bab_f_toHtml($str, $option);
}


/**
 * Return informations about search engine for files content
 * if the function return false, search engine is disabled
 * @return false|array
 */
function bab_searchEngineInfos() {
	include_once $GLOBALS['babInstallPath'].'utilit/indexincl.php';
	
	if (isset($GLOBALS['babSearchEngine'])) {

		$obj = bab_searchEngineInfosObj($GLOBALS['babSearchEngine']);

		return array(
			'name'			=> $GLOBALS['babSearchEngine'],
			'description'	=> $obj->getDescription(),
			'types'			=> $obj->getAvailableMimeTypes(),
			'indexes'		=> bab_searchEngineIndexes()
		);
	}
	return false;
}


/**
 * Get the instance for the registry class
 * 
 * $instance->changeDirectory($dir)
 * $instance->setKeyValue($key, $value)
 * $instance->removeKey($key)
 * $instance->getValue($key)
 * $instance->getValueEx($key)
 * $instance->deleteDirectory()
 * $instance->fetchChildDir()
 * $instance->fetchChildKey()
 *
 * @see bab_registry
 * 
 * @return bab_Registry
 */
function bab_getRegistryInstance() {
	static $_inst = null;
	if (null === $_inst) {
		include_once $GLOBALS['babInstallPath'].'utilit/registry.php';
		$_inst = new bab_registry();
	}

	return $_inst;
}

/**
 * Request param
 * @since 6.0.6
 * @param string $name
 * @param mixed	$default
 * @return mixed
 */
function bab_rp($name, $default = '') {
	if (isset($_GET[$name])) {
		return $_GET[$name];
	}
	if (isset($_POST[$name])) {
		return $_POST[$name];
	}
	return $default;
}

/**
 * Post param
 * @since 6.0.6
 * @param string $name
 * @param mixed	$default
 * @return mixed
 */
function bab_pp($name, $default = '') {
	if (isset($_POST[$name])) {
		return $_POST[$name];
	}
	return $default;
}

/**
 * Get param
 * @since 6.0.6
 * @param string $name
 * @param mixed	$default
 * @return mixed
 */
function bab_gp($name, $default = '') {
	if (isset($_GET[$name])) {
		return $_GET[$name];
	}
	return $default;
}

/**
 * Return the current file content disposition ( attchement, inline, undefined )
 * @since 6.0.6
 * @return mixed ('': undefined, 1: inline, 2: attachment )
 */
function bab_getFileContentDisposition() {
	if (!isset($GLOBALS['babFileContentDisposition']))
		return '';
	else
	{
		switch($GLOBALS['babFileContentDisposition'])
		{
			case 1: return 1;
			case 2: return 2;
			default: return '';
		}
	}

}




/**
 * Convert ovml to html.
 * Similar to @see bab_printOvmlTemplate but caches the parsed result and uses
 * this cache if available.
 * The maximum duration of a cached ovml is 3600s by default but the value can
 * be overridden by the optional '_ovml_cache_duration' parameter passed when
 * calling the ovml file.
 * Eg.: $OVMLCACHE(example.ovml,param1=12,_ovml_cache_duration=86400) for a
 * cache duration of 24h.
 * 
 * The actual caching is done in $_SESSION so it will be lost after the session
 * is destroyed. The cache is stored in a $_SESSION['ovml_cache'] array. It has
 * the following structure:
 * 
 * ['ovml_cache']
 * 		['example.ovml:param1=12&_ovml_cache_duration=86400'] : unique id
 * 			['timestamp'] => timestamp of cached content creation
 * 			['content'] => parsed ovml content
 *      ['example2.ovml:] : unique id
 * 			['timestamp'] => timestamp of cached content creation
 * 			['content'] => parsed ovml content
 *      ...
 * 
 * @param	string	$file
 * @param	array	$args
 * @return	string	html
 */ 
function bab_printCachedOvmlTemplate($file, $args = array())
{
	// We create a unique id based on the filename and named arguments.
	$ovmlId = $file . ':' . http_build_query($args);
	
	if (!isset($_SESSION['ovml_cache'][$ovmlId])) {
		$_SESSION['ovml_cache'][$ovmlId] = array();
	}
	$ovmlCache =& $_SESSION['ovml_cache'][$ovmlId];

	// We check if there the specified ovml is in the cache and the cache is
	// less than 1 hour (or the specified duration) old.
	if (!isset($ovmlCache['timestamp'])
	|| (time() - $ovmlCache['timestamp'] > (isset($args['_ovml_cache_duration']) ? $args['_ovml_cache_duration'] : 3600))) {
		$ovmlCache['timestamp'] = time();
		$ovmlCache['content'] = bab_printOvmlTemplate($file, $args);
	}
	return $ovmlCache['content'];
}



/**
 * Convert ovml to html
 * @param	string	$file
 * @param	array	$args
 * @return	string	html
 */
function bab_printOvmlTemplate($file, $args=array())
{
	global $babInstallPath, $babSkinPath, $babOvmlPath;

	/* Skin local path */
	$filepath = $babOvmlPath.$file; /* Ex. : skins/ovidentia_sw/ovml/test.ovml */
	
	if ($file == '') {
		bab_debug(bab_translate("Error: The name of the OVML file is not specified"));
		return '<!-- '.bab_translate("Error: The name of the OVML file is not specified").' : '.bab_toHtml($filepath).' -->';
	}
	
	if ((false !== mb_strpos($file, '..')) || mb_strtolower(mb_substr($file, 0, 4)) == 'http') {

		return '<!-- ERROR filename: '.bab_toHtml($file).' -->';
	}

	
	if (!file_exists($filepath)) {
		$filepath = $babSkinPath.'ovml/'.$file; /* Ex. : ovidentiainstall/skins/ovidentia/ovml/test.ovml */
		
		if (!file_exists($filepath)) {
			bab_debug(bab_translate("Error: OVML file does not exist").' : '.bab_toHtml($file));
			return '<!-- '.bab_translate("Error: OVML file does not exist").' : '.bab_toHtml($file).' -->';
		}
	}

	$GLOBALS['babWebStat']->addOvmlFile($filepath);
	include_once $babInstallPath.'utilit/omlincl.php';
	$tpl = new babOvTemplate($args);
	return $tpl->printout(implode('', file($filepath)));
}


/**
 * Abbreviate text with 2 types
 * BAB_ABBR_FULL_WORDS 	: the text is trucated if too long without cuting the words
 * BAB_ABBR_INITIAL 	: First letter of each word uppercase with dot
 * 
 * Additional dots are not included in the $max_length parameter
 *
 * @since	6.1.0
 *
 * @param	string	$text
 * @param	int		$type
 * @param	int		$max_length
 * 
 * @return 	string
 */
function bab_abbr($text, $type, $max_length) {
	$len = mb_strlen($text);
	if ($len < $max_length) {
		return $text;
	}
	
	$mots = preg_split('/[\s,]+/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE);

	if (BAB_ABBR_FULL_WORDS === $type) {
		if (1 === count($mots)) {
			return mb_substr($text,0,$max_length).'...';
		}

		for ($i = count($mots)-1; $i >= 0; $i--) {
			if ($mots[$i][1] < $max_length) {
				return mb_substr($text,0,$mots[$i][1]).'...';
			}
		}
	}
	
	if (BAB_ABBR_INITIAL === $type) {
		$n = ceil($max_length/count($mots));
		if ($max_length < $n) {
			return bab_abbr($text, BAB_ABBR_FULL_WORDS, $max_length);
		} else {
			array_walk($mots, create_function('&$v,$k','$v = mb_strtoupper(mb_substr($v[0],0,1)).".";'));
			return implode('',$mots);
		}
	}
}


/**
 * Define and get the locale
 * @see		setLocale 
 * @since 	6.1.1
 * @return 	false|string
 */
function bab_locale() {
	
	static $locale = NULL;

	if (NULL !== $locale) {
		return $locale;
		
	} else {
		global $babLanguage;
		
		
		if (function_exists('textdomain')) {
			// clear gettext cache for mo files modifications
			textdomain(textdomain(NULL));
		}
		
		
		switch(mb_strtolower($babLanguage)) {
			case 'fr':
				$arrLoc = array('fr_FR', 'fr');
				break;
			case 'en':
				$arrLoc = array('en_GB', 'en_US', 'en');
				break;
			default:
				$arrLoc = array(mb_strtolower($babLanguage).'_'.mb_strtoupper($babLanguage), mb_strtolower($babLanguage));
				break;
		}
		
		foreach($arrLoc as $languageCode) {
			
			/*
			 * Some systems only require LANG, others (like Mandrake) seem to require
			 * LANGUAGE also.
			 */
			putenv("LANG=${languageCode}");
			putenv("LANGUAGE=${languageCode}");
			
			if ($locale = setLocale(LC_ALL, $languageCode)) {
				return $locale;
			}

			/*
			 * Try appending some character set names; some systems (like FreeBSD) need this.
			 * Some require a format with hyphen (e.g. gentoo) and others without (e.g. FreeBSD).
			 */
			if (false === $locale) {
				foreach (array('utf8', 'UTF-8', 'UTF8', 
						   'ISO8859-1', 'ISO8859-2', 'ISO8859-5', 'ISO8859-7', 'ISO8859-9',
						   'ISO-8859-1', 'ISO-8859-2', 'ISO-8859-5', 'ISO-8859-7', 'ISO-8859-9',
						   'EUC', 'Big5') as $charset) {
					if (($locale = setlocale(LC_ALL, $languageCode . '.' . $charset)) !== false) {
						return $locale;
					}
				}
			}
		}
		
		if (false === $locale) {
			bab_debug("No locale found for : $languageCode");
			return false;
		}
		
		return $locale;
	}
}




/**
 * Returns a singleton of the specified class.
 *
 * @param string $classname
 * @return object
 */
function bab_getInstance($classname) {
	static $instances = NULL;
	if (is_null($instances)) {
		$instances = array();
	}
	if (!array_key_exists($classname, $instances)) {
		$instances[$classname] = new $classname();
	}
	
	return $instances[$classname];
}





/**
 * Functionality interface
 * Functionalities are inherited from this object, to instanciate a functionality use the static method
 * @see bab_functionality::get($path)
 * @since 6.6.90
 */
class bab_functionality {

	/**
	 * @deprecated Do not remove old constructor while there are functionalities in addons with direct call to bab_functionality::bab_functionality()
	 */
	public function bab_functionality() {}


	public static function getRootPath() {
		return realpath('.').'/'.BAB_FUNCTIONALITY_ROOT_DIRNAME;
	}


	/**
	 * Include php file with the functionality class
	 * @see bab_functionality::get()
	 * @access public
	 * @static
	 * @param	string	$path		path to functionality
	 * @return string | false		the object class name or false if the file allready included or false if the include failed
	 */
	public function includefile($path) {
		$include_result = /*@*/include self::getRootPath().'/'.$path.'/'.BAB_FUNCTIONALITY_LINK_FILENAME;
		
		if (false === $include_result) {
			trigger_error(sprintf('The functionality %s is not available', $path));
		}
		
		return $include_result;
	}


	/**
	 * Returns the specified functionality object.
	 * 
	 * If $singleton is set to true, the functionality object will be instanciated as
	 * a singleton, i.e. there will be at most one instance of the functionality
	 * at a given time.
	 * 
	 * @access public
	 * @static
	 * @param	string	$path		The functionality path.
	 * @param	bool	$singleton	Whether the functionality should be instanciated as singleton (default true).
	 * @return	object				The functionality object or false on error.
	 */
	public function get($path, $singleton = true) {
		$classname = bab_functionality::includefile($path);
		if (!$classname) {
			return false;
		}
		if ($singleton) {
			return bab_getInstance($classname);
		}
		return new $classname();
	}
	
	/**
	 * get functionalities compatible with the interface
	 * @access public
	 * @static
	 * @param	string	$path
	 * @return array
	 */
	public function getFunctionalities($path) {
		require_once $GLOBALS['babInstallPath'].'utilit/functionalityincl.php';
		$obj = new bab_functionalities();
		return $obj->getChildren($path);
	}
	
	/**
	 * Default method to create in inherited functionalities
	 * @access protected
	 * @return string
	 */
	public function getDescription() {
		return '';
	}
	
	
	/**
	 * Get path to functionality at this node which is the current path or a reference to a childnode
	 * @return string
	 */
	public function getPath() {
		require_once $GLOBALS['babInstallPath'].'utilit/functionalityincl.php';
		return bab_Functionalities::getPath(get_class($this));
	}
}


/**
 * Get an object with informations for one addon
 * if the addon does not exist, the function return false
 *
 * @since 6.6.93
 * @param	string	$addonname
 * @return bab_addonInfos|false
 */
function bab_getAddonInfosInstance($addonname) {

	require_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
	static $instances = array();
	
	if (false === array_key_exists($addonname, $instances)) {
		$obj = new bab_addonInfos();
		if (false === $obj->setAddonName($addonname, false)) {
			$instances[$addonname] = false;
		} else {
			$instances[$addonname] = $obj;
		}
	}
	return $instances[$addonname];
}


/**
 * Ensures that the user is logged in.
 * If the user is not logged the "PortalAutentication" functionality
 * is used to let the user log in with its credential.
 * 
 * The parameter $sAuthType can be used to force the authentication method,
 * it must be the name (path) of the functionality to use without 'PortalAuthentication/' and the 'Auth' prefix.
 * E.g. for 'PortalAuthentication/BasicAuth' use basic 
 * 
 * @param	string		$sLoginMessage	Message displayed to the user when asked to log in.
 * @param	string		$sAuthType		Optional authentication type.
 * @since 6.7.0
 *
 * @return boolean
 */
function bab_requireCredential($sLoginMessage = '', $sAuthType = '') {
	require_once $GLOBALS['babInstallPath'].'utilit/loginIncl.php';
	return bab_doRequireCredential($sLoginMessage, $sAuthType);
}






/**
 * Checks that the user has at least one of the specified ACL access ($tables) on $idobject. 
 * 
 * If the user has none of the required accesses and is not logged,
 * the function will require en authentication (through) bab_requireCredential
 * and then check once again the access.
 * 
 * @param	string|array	$tables			The name of the ACL table (*_groups) or an array of them.
 * @param	int				$idObject		
 * @param	string			$loginMessage	Message displayed to the user when asked to log in.
 * @since 6.7.0
 *
 * @return boolean
 */
function bab_requireAccess($tables, $idObject, $loginMessage)
{
	if (is_string($tables)) {
		$tables = array($tables);
	}
	foreach ($tables as $table) {
		if (bab_isAccessValid($table, $idObject, '')) {
			return true;
		}
	}
	if (bab_userIsloggedin()) {
		return false;
	}
	bab_requireCredential($loginMessage);
	foreach ($tables as $table) {
		if (bab_isAccessValid($table, $idObject, '')) {
			return true;
		}
	}
	return false;
}


/**
 * Limits the maximum execution time
 *
 * @param int	$seconds	The maximum execution time, in seconds. If set to zero, no time limit is imposed. 
 */
function bab_setTimeLimit($seconds)
{
	if (function_exists('set_time_limit')) {
		@set_time_limit($seconds);
	}
}



/**
 * Construct a reference string for an ovidentia object
 * the result will have the form : ovidentia://location/module/type/identifier
 * exemple : <code>ovidentia:///articles/article/12</code>
 *
 * @param	string	$module			addon name or core functionality name
 * @param	string	$type			type of object
 * @param	mixed	$identifier		numeric or string to identify object in the type of object
 * @param	string	$location		optional string, 
 *									empty string or another domain 'ovidentia.org' 
 *									or a domain an a path 'ovidentia.org/dev', empty string is the default value it mean the local site
 * @return bab_reference
 */
function bab_buildReference($module, $type, $identifier, $location = '') 
{
	require_once dirname(__FILE__) . '/reference.class.php';
	return bab_Reference::makeReference('ovidentia', $location, $module, $type, $identifier);
}





