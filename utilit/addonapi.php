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

	//if (function_exists('iconv')) {
	//	return @iconv($sStringIsoCharset, bab_charset::getIso().'//TRANSLIT//IGNORE', $input);
	//} else {
		return mb_convert_encoding($input, bab_charset::getIso(), $sStringIsoCharset);
	//}
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

	//if (function_exists('iconv')) {
	//	return iconv(bab_charset::getIso(), $sIsoCharset.'//TRANSLIT//IGNORE', $input);
	//} else {
		return mb_convert_encoding($input, $sIsoCharset, bab_charset::getIso());
	//}
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
 * @param string $string 	The string to process.
 * @param string $charset 	Optional charset, default is database charset
 * 
 * @return string The processed string (in ascii).
 */
function bab_removeDiacritics($string, $charset = null)
{
	if ( !preg_match('/[\x80-\xff]/', $string) )
	{
        return $string;
	}
	
	if (null === $charset)
	{
		$charset = bab_charset::getIso();
	}

    if (bab_charset::UTF_8 === $charset) {
        $chars = array(
        // Decompositions for Latin-1 Supplement
        chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
        chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
        chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
        chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
        chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
        chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
        chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
        chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
        chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
        chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
        chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
        chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
        chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
        chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
        chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
        chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
        chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
        chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
        chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
        chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
        chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
        chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
        chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
        chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
        chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
        chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
        chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
        chr(195).chr(191) => 'y',
        // Decompositions for Latin Extended-A
        chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
        chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
        chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
        chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
        chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
        chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
        chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
        chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
        chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
        chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
        chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
        chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
        chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
        chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
        chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
        chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
        chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
        chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
        chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
        chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
        chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
        chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
        chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
        chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
        chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
        chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
        chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
        chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
        chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
        chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
        chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
        chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
        chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
        chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
        chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
        chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
        chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
        chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
        chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
        chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
        chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
        chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
        chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
        chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
        chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
        chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
        chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
        chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
        chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
        chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
        chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
        chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
        chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
        chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
        chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
        chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
        chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
        chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
        chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
        chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
        chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
        chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
        chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
        chr(197).chr(190) => 'z', chr(197).chr(191) => 's',
        // Euro Sign
        chr(226).chr(130).chr(172) => 'E',
        // GBP (Pound) Sign
        chr(194).chr(163) => '');

        $string = strtr($string, $chars);
    } else {
        // Assume ISO-8859-1 if not UTF-8
        $chars['in'] = chr(128).chr(131).chr(138).chr(142).chr(154).chr(158)
            .chr(159).chr(162).chr(165).chr(181).chr(192).chr(193).chr(194)
            .chr(195).chr(196).chr(197).chr(199).chr(200).chr(201).chr(202)
            .chr(203).chr(204).chr(205).chr(206).chr(207).chr(209).chr(210)
            .chr(211).chr(212).chr(213).chr(214).chr(216).chr(217).chr(218)
            .chr(219).chr(220).chr(221).chr(224).chr(225).chr(226).chr(227)
            .chr(228).chr(229).chr(231).chr(232).chr(233).chr(234).chr(235)
            .chr(236).chr(237).chr(238).chr(239).chr(241).chr(242).chr(243)
            .chr(244).chr(245).chr(246).chr(248).chr(249).chr(250).chr(251)
            .chr(252).chr(253).chr(255);

        $chars['out'] = "EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy";

        $string = strtr($string, $chars['in'], $chars['out']);
        $double_chars['in'] = array(chr(140), chr(156), chr(198), chr(208), chr(222), chr(223), chr(230), chr(240), chr(254));
        $double_chars['out'] = array('OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th');
        $string = str_replace($double_chars['in'], $double_chars['out'], $string);
    }

    return $string;
	
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
	 * @param string $sCharset The charset code as returned by bab_charset::getDatabase().
	 *
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
 * 
 * @param   string	$time	(eg. '2006-03-10 17:37:02')
 * 
 * @return  int	unix timestamp
 */
function bab_mktime($time)
	{
	$arr = explode(" ", $time); //Split days and hours
	if ('0000-00-00' == $arr[0] || '' == $arr[0]) {
		return null;
	}
	$arr0 = explode("-", $arr[0]); //Split year, month et day
	if (isset($arr[1])) { //If the hours exist we send back days and hours
		$arr1 = explode(":", $arr[1]);
		return mktime( $arr1[0],$arr1[1],$arr1[2],$arr0[1],$arr0[2],$arr0[0]);
		} else { //If the hours do not exist, we send back only days
		return mktime(0,0,0,$arr0[1],$arr0[2],$arr0[0]);
		}
	}
	
	
class bab_DateStrings
{
	/**
	 * 
	 * @return array
	 */
	public static function getMonths()
	{
		static $arr = null;
		
		if (null === $arr)
		{
			$arr = array(
				1=>bab_translate("January"), 
				bab_translate("February"), 
				bab_translate("March"), 
				bab_translate("April"),
	            bab_translate("May"), 
	            bab_translate("June"), 
	            bab_translate("July"), 
	            bab_translate("August"),
	            bab_translate("September"), 
	            bab_translate("October"), 
	            bab_translate("November"), 
	            bab_translate("December")
	        );
		}
		
		return $arr;
	}
	
	/**
	 * 
	 * @param int $i
	 * @return string
	 */
	public static function getMonth($i)
	{
		$months = self::getMonths();
		return $months[$i];
	}
	
	/**
	 * 
	 * @return array
	 */
	public static function getShortMonths()
	{
		static $arr = null;
		
		if (null === $arr)
		{
			$months = self::getMonths();
			
			$arr = array();
			foreach($months as $key => $val) {
				$sm = mb_substr($val, 0 , 3);
				if (count($arr) == 0 || !in_array($sm, $arr)) {
					$arr[$key] = $sm;
				} else {
					$m=4;
					while(in_array($sm, $arr) && $m < mb_strlen($val)) {
						$sm = mb_substr($val, 0 , $m++);
					}
			
					$arr[$key] = $sm;
				}
			}
		}
		
		return $arr;
	}
	
	/**
	 * 
	 * @return arrray
	 */
	public static function getDays()
	{
		static $arr = null;
		
		if (null === $arr)
		{
			$arr = array(
				bab_translate('Sunday'), 
				bab_translate('Monday'),
				bab_translate('Tuesday'), 
				bab_translate('Wednesday'), 
				bab_translate('Thursday'),
				bab_translate('Friday'), 
				bab_translate('Saturday')
			);
		}
		
		return $arr;
	}
	
	
	/**
	 * 
	 * @param int $i
	 * @return string
	 */
	public static function getDay($i)
	{
		$days = self::getDays();
		return $days[$i];
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
	$txt = $format;
	if(preg_match_all("/%(.)/", $format, $m))
		{
		for( $i = 0; $i< count($m[1]); $i++)
			{
			switch($m[1][$i])
				{
				case 'd': /* A short textual representation of a day, three letters */
					$days = bab_DateStrings::getDays();
					$val = mb_substr($days[date("w", $time)], 0 , 3);
					break;
				case 'D': /* day */
					$days = bab_DateStrings::getDays();
					$val = $days[date("w", $time)];
					break;
				case 'j': /* Day of the month with leading zeros */
					$val = date("d", $time);
					break;
				case 'm': /* A short textual representation of a month, three letters */
					$shortMonths = bab_DateStrings::getShortMonths();
					$val = $shortMonths[date("n", $time)];
					break;
				case 'M': /* Month */
					$month = bab_DateStrings::getMonths();
					$val = $month[date("n", $time)];
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
	if( null === $time)
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
	if( null === $time)
		return "";

	if( !isset($GLOBALS['babShortDateFormat']))
		{
		require_once dirname(__FILE__).'/utilit.php';
		$GLOBALS['babShortDateFormat'] = bab_getDateFormat("dd/mm/yyyy");
		$GLOBALS['babTimeFormat'] = bab_getTimeFormat("HH:mm");
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
 * Transform a html string with the security filter configured in site options
 */
function bab_editor_record(&$str)
	{

	global $babDB;
	$str = preg_replace("/((href|src)=['\"]?)".preg_quote($GLOBALS['babUrl'],'/i').'/', "\\1", $str);

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
		require_once dirname(__FILE__).'/loadlanguage.php';
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

	
/**
 * Test if the user is member of administrators group
 * @return bool
 */
function bab_isUserAdministrator()
{
	global $babBody;
	
	if (!isset($babBody->isSuperAdmin))
	{
		return bab_isMemberOfGroup(BAB_ADMINISTRATOR_GROUP);
	}
	
	return $babBody->isSuperAdmin;
}


/**
 * @deprecated the manager of a group does not exists anymore
 * @param int $grpid
 * @return bool
 */
function bab_isUserGroupManager($grpid="")
	{
	trigger_error('deprecated function');
	return false;
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
				if (bab_Groups::isGroupSet($idg))
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
	
	if (is_numeric($group) && BAB_ALLUSERS_GROUP === (int) $group)
	{
		return true;
	}
	
	if('' === $group) {
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
	
	if ($BAB_SESS_USERID == $userid)
	{
		require_once dirname(__FILE__).'/groupsincl.php';
		
		// use session cache
		if (bab_Groups::inUserGroups($id_group))
		{
			return $id_group;
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
			{
				return $id_group;
			}
				
	}
	
	return false;
}

/**
 * Get user ID by email
 * @param string $email
 * @return int
 */
function bab_getUserIdByEmail($email)
	{
	global $babDB;
	$query = "select id from ".BAB_USERS_TBL." where email LIKE '".$babDB->db_escape_string($email)."'";
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return (int) $arr['id'];
		}
	else
		{
		return 0;
		}
	}

	
/**
 * Get user ID by nickname
 * @param string $nickname
 * @return int
 */
function bab_getUserIdByNickname($nickname)
	{
	global $babDB;
	$res = $babDB->db_query("select id from ".BAB_USERS_TBL." where nickname='".$babDB->db_escape_string($nickname)."'");
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		return (int) $arr['id'];
		}
	else
		{
		return 0;
		}
	}

	
/**
 * Get the user ID from logged in user or from the fullname
 * @since 7.8.90 the name parameter is optional since the 7.8.90
 * @param string $name
 * @return int
 */
function bab_getUserId($name = null)
{
		
	if (null === $name)
	{
		require_once dirname(__FILE__).'/session.class.php';
		$session = bab_getInstance('bab_Session');
		
		if (!isset($session->BAB_SESS_USERID))
		{
			return 0;
		}
		
		return ((int) $session->BAB_SESS_USERID);
	}
		
		
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
	
	return 0;
}

/**
 * Get $nb users with $name in their name.
 * @param string 	$name
 * @param int		$nb
 * @return array { int | id , string | lastname , string | firstname }
 */
function bab_getUsersByName( $name, $nb = 5 )
	{
	global $babDB;
	$name = "%".trim($name)."%" ;
	$query = "select id, lastname, firstname from ".BAB_USERS_TBL." where lastname LIKE '".$babDB->db_escape_string($name)."' LIMIT 0,".$nb;
	$res = $babDB->db_query($query);
	if( $babDB->db_num_rows($res) > 0)
		{
		$i = 0;
		while ($arr = $babDB->db_fetch_assoc($res)){
			$resArr[$i]['id'] = $arr['id'];
			$resArr[$i]['lastname'] = $arr['lastname'];
			$resArr[$i]['firstname'] = $arr['firstname'];
			$i++;
		}
		return $resArr;
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
		$usergroups = bab_Groups::getUserGroups();
		for( $i = 0; $i < count($usergroups); $i++ )
			{
			if( $usergroups[$i] != BAB_REGISTERED_GROUP && $usergroups[$i] != BAB_UNREGISTERED_GROUP && $usergroups[$i] != BAB_ALLUSERS_GROUP)
				{
				$arr['id'][] = $usergroups[$i];
				$nm = bab_Groups::getGroupPathName($usergroups[$i]);
				if( empty($nm))
					{
					$nm =  bab_Groups::getSetOfGroupName($usergroups[$i]);
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
				$nm = bab_Groups::getGroupPathName($r['id_group']);
				if( empty($nm))
					{
					$nm =  bab_Groups::getSetOfGroupName($r['id_group']);
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
	require_once $GLOBALS['babInstallPath'].'utilit/session.class.php';
	$session = bab_getInstance('bab_Session');
	
	if( $iduser != '' && ((int) $iduser) !== (int) $session->BAB_SESS_USERID)
		{
			include_once $GLOBALS['babInstallPath']."admin/acl.php";

			$users = aclGetAccessUsers($table, $idobject);

			if( isset($users[ $iduser]))
			{
				return true;
			}
			return false;
		}
		
	$objects = bab_getUserIdObjects($table);

	return isset($objects[$idobject]);
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
	require_once dirname(__FILE__).'/groupsincl.php';
	global $babDB;
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
		elseif ( ($object['id_group'] < BAB_ACL_GROUP_TREE && in_array($object['id_group'], $userGroupIds)) 
		|| ($object['id_group'] > BAB_ACL_GROUP_TREE && bab_Groups::isMemberOfTree($object['id_group'] - BAB_ACL_GROUP_TREE, $userId))) {
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
	require_once dirname(__FILE__).'/groupsincl.php';
	require_once $GLOBALS['babInstallPath'].'utilit/session.class.php';
	
	global $babDB;
	$session = bab_getInstance('bab_Session');
	
	if (!isset($session->bab_groupAccess))
	{
		$groupAccess = array();
	} else {
		$groupAccess = $session->bab_groupAccess;
	}
	
	if(isset($groupAccess) || !isset($groupAccess['acltables'][$table]))
	{
		$groupAccess['acltables'][$table] = array();
	
		$res = $babDB->db_query("SELECT t.id_object, t.id_group, g.nb_groups FROM ".$babDB->backTick($table)." t left join ".BAB_GROUPS_TBL." g on g.id=t.id_group");
	
		while ($row = $babDB->db_fetch_assoc($res)) {
			if( $row['nb_groups'] !== null )
			{
			$rs=$babDB->db_query("select id_group from ".BAB_GROUPS_SET_ASSOC_TBL." where id_set=".$babDB->quote($row['id_group']));
			while( $rr = $babDB->db_fetch_array($rs))
				{
				if( bab_isMemberOfGroup($rr['id_group']))
					{
						$groupAccess['acltables'][$table][$row['id_object']] = $row['id_object'];
					}
				}
			}
			elseif ( ($row['id_group'] < BAB_ACL_GROUP_TREE && bab_isMemberOfGroup($row['id_group'])) 
				|| ($row['id_group'] > BAB_ACL_GROUP_TREE && bab_Groups::isMemberOfTree($row['id_group'] - BAB_ACL_GROUP_TREE))) {
				$groupAccess['acltables'][$table][$row['id_object']] = $row['id_object'];
			}
		}
		
		$session->bab_groupAccess = $groupAccess;
	}

	return $groupAccess['acltables'][$table];
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
			if( preg_match("/lang-([^.]*)/", $file, $regs))
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
			if( preg_match('/lang-([^.]*)/', $file, $regs))
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
 * @since 7.3.0 	the id_user parameter and tg result key
 *
 * @param	int		$id_user
 *
 * @return array
 */
function bab_getActiveSessions($id_user = null)
{
	global $babDB;
	$output = array();

	$query = "SELECT l.id_user,
			l.sessid,
			l.remote_addr,
			l.forwarded_for,
			UNIX_TIMESTAMP(l.dateact) dateact,
			u.firstname,
			u.lastname,
			u.email,
			UNIX_TIMESTAMP(u.lastlog) lastlog,
			UNIX_TIMESTAMP(u.datelog) datelog,
			UNIX_TIMESTAMP(u.date) registration,
			l.tg
			FROM ".BAB_USERS_LOG_TBL." l
			LEFT JOIN ".BAB_USERS_TBL." u ON u.id=l.id_user";

	if (null !== $id_user) {
		$query .= " WHERE l.id_user=".$babDB->quote($id_user);
	}


	$res = $babDB->db_query($query);

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
						'tg' => $arr['tg']
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
 * @deprecated this function does not return extra fields
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
	require_once dirname(__FILE__).'/defines.php';
	
	$id = (int) $id;

	if (BAB_ALLUSERS_GROUP === $id || BAB_REGISTERED_GROUP === $id || BAB_UNREGISTERED_GROUP === $id || BAB_ADMINISTRATOR_GROUP === $id) {
		return bab_translate(bab_Groups::getName($id));
	}
	
	if($fpn)
	{
		$name = bab_Groups::getGroupPathName($id);
		if ('' !== $name)
		{
			// probably a set of groups
			return $name;
		}
	}
	
	return bab_Groups::getName($id);
	
}




/**
 * Get ovidentia groups
 * The returned groups are stored in an array with three keys : id, name, description
 * in each keys, a list of group is stored with ordered numeric keys
 * the default value for the $all parameter is true
 *
 * @param	int			$parent		parent group in tree | if $parent is null, retur the list of group sets
 * @param	boolean		$all		return one level of groups or groups from all sublevels
 * @return	array
 */
function bab_getGroups($parent=BAB_REGISTERED_GROUP, $all=true)
{
		
		
	if (null === $parent)
	{
		// list of groups sets
		global $babDB;
		$resset = $babDB->db_query("SELECT id, name, description FROM ".BAB_GROUPS_TBL." WHERE nb_groups>='0'");
		$arr = array();
		while ($row = $babDB->db_fetch_assoc($resset))
		{
			$arr['id'][] = $row['id'];
			$arr['name'][] = $row['name'];
			$arr['description'][] = $row['description'];
		}
		
		return $arr;
	}
		
	include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";

	$tree = new bab_grptree();
	$groups = $tree->getGroups($parent, '%2$s > ', $all);
	$arr = array();
	foreach ($groups as $row)
		{
		$arr['id'][] = $row['id'];
		$arr['name'][] = $row['name'];
		$arr['description'][] = $row['description'];
		$arr['position'][] = array('lf' => $row['lf'], 'lr' => $row['lr']);
		}

	return $arr;
}



/**
 * Create a group
 * @param string	$name
 * @param string	$description
 * @param int		$managerid		deprecated parameter
 * @param int		$parent
 * @return int
 */
function bab_createGroup( $name, $description, $managerid, $parent = 1)
{
	include_once $GLOBALS['babInstallPath']."utilit/grpincl.php";
	return bab_addGroup($name, $description, $managerid, 0, $parent);
}


/**
 * Update group name and description
 * @param int		$id
 * @param string	$name
 * @param string	$description
 * @return bool
 */
function bab_updateGroup( $id, $name, $description)
{
	include_once $GLOBALS['babInstallPath']."utilit/grpincl.php";
	return bab_updateGroupInfo($id, $name, $description, 0);
}

/**
 * Delete a group
 * @param int $id
 * @return unknown_type
 */
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

/**
 * Send a email notification to a  new registered account
 * @see bab_registerUser()
 *
 * @since 7.3.0
 *
 * @param string $name			Full name of user
 * @param string $email			email address to notifiy
 * @param string $nickname		login ID
 * @param string $pwd			if set, the password will readable in email
 *
 * @return bool
 */
function bab_registerUserNotify($name, $email, $nickname, $pwd = null)
{
	require_once($GLOBALS['babInstallPath']."admin/register.php");
	return notifyAdminUserRegistration($name, $email, $nickname, $pwd);
}


/**
 * Attach a user to a group
 * @param int 	$iduser
 * @param int 	$idgroup
 *
 */
function bab_attachUserToGroup($iduser, $idgroup)
{
	bab_addUserToGroup($iduser, $idgroup);
}


/**
 * Detacha a user from a group
 * @param int	$iduser
 * @param int	$idgroup
 *
 */
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
 * Verify if the current user can update the account (superadmin...) of the user specified by id
 * @since			ovidentia-7-2-92-20100329153357
 *
 * @param $userId	id (int) of the user who must be updated
 * @return bool		true if the current user has rights to update the user
 */
function bab_canCurrentUserUpdateUser($userId) {
	global $babBody;
	include_once $GLOBALS['babInstallPath'].'utilit/delegincl.php';

	/* The user must be authentified */
	if (!bab_userIsloggedin()) {
		return false;
	}

	/* The current user can change his datas */
	$idCurrentUser = $GLOBALS['BAB_SESS_USERID'];
	if ($idCurrentUser !== false && $idCurrentUser != 0) {
		if ($idCurrentUser == $userId) {
			return true;
		}
	}

	/* Verify the right admin */
	if ($babBody->currentAdmGroup) {
		$dg = $babBody->currentAdmGroup;
	} elseif ($babBody->isSuperAdmin) {
		return true;
	} else {
		return false;
	}

	if (!bab_isUserInDelegation($dg, $userId))
	{
		return false;
	}
	
	// Mantis #1867
	// this test was used from 7.5.91 to 7.7.3

	/*
	if (bab_isUserOutOfDelegation($dg, $userId))
	{
		return false;
	}
	*/

	return true;
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
 * @param	array	$info		Array returned by bab_getUserInfos() : the array can contain keys only to be changed
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
 * Updates the specified user's nickname
 * @since			ovidentia-7-2-92-20100329153357
 *
 * @param int		$userId					The user id
 * @param string	$newNickname			The new user nickname
 * @param bool		$ignoreAccessRights		false (value by default) if you want to verify if the current user can update the account (superadmin...)
 * @param string	&$error					Error message
 *
 * @return bool		true on success, false on error
 */
function bab_updateUserNicknameById($userId, $newNickname, $ignoreAccessRights=false, &$error)
{
	global $babDB, $BAB_HASH_VAR;

	/* Test rights */
	if (!$ignoreAccessRights) {
		$res = bab_canCurrentUserUpdateUser($userId);
		if (!$res) {
			$error = bab_translate("You don't have access to update the user");
			return false;
		}
	}

	/* Test if the new nickname is empty */
	if (empty($newNickname)) {
		$error = bab_translate("You must provide a nickname");
		return false;
	}

	/* Test if the new nickname contain spaces */
	/*
	if (mb_strpos($newNickname, ' ') !== false) {
		$error = bab_translate("Login ID should not contain spaces");
		return false;
	}
	*/

	/* Test if the new nickname already exists */
	$db = $GLOBALS['babDB'];
	$query = 'SELECT * FROM ' . BAB_USERS_TBL . '
				WHERE nickname=' . $babDB->quote($newNickname) . '
				AND id!=' . $babDB->quote($userId);
	$res = $babDB->db_query($query);
	if ($babDB->db_num_rows($res) > 0) {
		$error = bab_translate("This login ID already exists !!");
		return false;
	}

	/* Update datas's user */
	$hash = md5($newNickname.$BAB_HASH_VAR);
	$req = 'UPDATE ' . BAB_USERS_TBL . '
			SET confirm_hash=' . $babDB->quote($hash) . ', nickname=' . $babDB->quote($newNickname) . '
			WHERE id='. $babDB->quote($userId);
	$res = $babDB->db_query($req);

	require_once $GLOBALS['babInstallPath'] . 'utilit/eventdirectory.php';
	$event = new bab_eventUserModified((int)$userId);
	bab_fireEvent($event);

	return true;
}

/**
 * Updates the specified user's password
 * @since			ovidentia-7-2-92-20100329180000
 *
 * @param int		$userId						The user id
 * @param string	$newPassword				The new user password
 * @param string	$newPassword2				The new user password (copy : used when we created 2 input fields in a form to confirm the password)
 * @param bool		$ignoreAccessRights			false (value by default) if you want to verify if the current user can update the account (superadmin...)
 * @param bool		$ignoreSixCharactersMinimum	false (value by default) if you want to verify if the password have at least 6 characters
 * @param string	&$error						Error message
 *
 * @return bool		true on success, false on error
 */
function bab_updateUserPasswordById($userId, $newPassword, $newPassword2, $ignoreAccessRights=false, $ignoreSixCharactersMinimum=false, &$error)
{
	global $babBody, $babDB, $BAB_HASH_VAR;

	/* Test rights */
	if (!$ignoreAccessRights) {
		$res = bab_canCurrentUserUpdateUser($userId);
		if (!$res) {
			$error = bab_translate("You don't have access to update the user");
			return false;
		}
	}

	/* Delete spaces in passwords */
	$newPassword = trim($newPassword);
	$newPassword2 = trim($newPassword2);

	/* Test if passwords are same */
	if ($newPassword != $newPassword2) {
		$error = bab_translate("Passwords not match !!");
		return false;
	}

	$minPasswordLengh = 6;
	if(ISSET($GLOBALS['babMinPasswordLength']) && is_numeric($GLOBALS['babMinPasswordLength'])){
		$minPasswordLengh = $GLOBALS['babMinPasswordLength'];
	}

	/* Test if the password have at least $GLOBALS['babMinPasswordLength'] or 6 characters */
	if (!$ignoreSixCharactersMinimum) {
		if (mb_strlen($newPassword) < $minPasswordLengh) {
			$error = sprintf(bab_translate("Password must be at least %s characters !!"),$minPasswordLengh);
			return false;
		}
	}

	/* Verify the authentification mode of the user */
	$sql = 'SELECT nickname, db_authentification FROM ' . BAB_USERS_TBL . ' WHERE id=' . $babDB->quote($userId);
	list($nickname, $dbauth) = $babDB->db_fetch_row($babDB->db_query($sql));

	$authentification = $babBody->babsite['authentification'];
	if ($dbauth == 'Y') {
		$authentification = ''; // force to default
	}

	switch ($authentification)
	{
		case BAB_AUTHENTIFICATION_AD: // Active Directory
			$error = bab_translate("Nothing Changed !!");
			return false;
			break;

		case BAB_AUTHENTIFICATION_LDAP: // Active Directory
			if (!empty($babBody->babsite['ldap_encryptiontype'])) {
				include_once $GLOBALS['babInstallPath']."utilit/ldap.php";
				$ldap = new babLDAP($babBody->babsite['ldap_host'], "", false);
				$ret = $ldap->connect();
				if ($ret === false) {
					$error = bab_translate("LDAP connection failed");
					return false;
				}

				$ret = $ldap->bind($babBody->babsite['ldap_admindn'], $babBody->babsite['ldap_adminpassword']);
				if (!$ret) {
					$ldap->close();
					$error = bab_translate("LDAP bind failed");
					return  false;
				}

				if (isset($babBody->babsite['ldap_filter']) && !empty($babBody->babsite['ldap_filter'])) {
					$filter = str_replace('%UID', ldap_escapefilter($babBody->babsite['ldap_attribute']), $babBody->babsite['ldap_filter']);
					$filter = str_replace('%NICKNAME', ldap_escapefilter($nickname), $filter);
				} else {
					$filter = "(|(".ldap_escapefilter($babBody->babsite['ldap_attribute'])."=".ldap_escapefilter($nickname)."))";
				}

				$attributes = array("dn", $babBody->babsite['ldap_attribute'], "cn");
				$entries = $ldap->search($babBody->babsite['ldap_searchdn'], $filter, $attributes);

				if ($entries === false) {
					$ldap->close();
					$error = bab_translate("LDAP search failed");
					return false;
				}

				$ldappw = ldap_encrypt($newPassword, $babBody->babsite['ldap_encryptiontype']);
				$ret = $ldap->modify($entries[0]['dn'], array('userPassword'=>$ldappw));
				$ldap->close();
				if (!$ret) {
					$error = bab_translate("Nothing Changed");
					return false;
				}
			}
			break;

		default:
			break;
	}

	/* Update the user's password */
	$sql = 'UPDATE ' . BAB_USERS_TBL . ' SET password=' . $babDB->quote(md5(mb_strtolower($newPassword))) . ' WHERE id=' . $babDB->quote($userId);
	$babDB->db_query($sql);

	/* Call the functionnality event onUserChangePassword */
	include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
	bab_callAddonsFunctionArray('onUserChangePassword',
		array(
			'id' => $userId,
			'nickname' => $nickname,
			'password' => $newPassword,
			'error' => &$error
		)
	);

	return true;
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
function bab_debug($data, $severity = DBG_TRACE, $category = '', $shiftdebug = 1)
{
	$file = $line = $function = '';







	// Here we find information about the file and line where bab_debug was called.
	$backtrace = debug_backtrace();
	for($i = 0; $i < $shiftdebug; $i++)
	{
		$call = array_shift($backtrace);
	}
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
 * Remove html entities 
 * 
 * @todo compliance with UTF-8
 * the charset parameter of get_html_translation_table has benn added in php 5.3.4
 * 
 * @param	string	$string
 * @return string
 */
function bab_unhtmlentities($string)
{
	if( bab_charset::getDatabase() == "utf8")
	{//Because this character cause display issue
		$string = str_replace('&nbsp;', ' ', $string);
	}	
	
	// special quote : htmlarea &#8217; and fckeditor &rsquo;
	$string = preg_replace('~&#8217;~', '\'', $string);
	
	// replace numeric entities
	$string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
	$string = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $string);
	
	// replace literal entities
	$trans_tbl = get_html_translation_table(HTML_ENTITIES);
	$trans_tbl = array_flip($trans_tbl);
			
	$trans_tbl['&rsquo;'] = "'";
	
	return strtr($string, $trans_tbl);
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
 * @since 7.1.94
 * @param	string	$file
 * @param	array	$args
 * @return	string	html
 */
function bab_printCachedOvmlTemplate($file, $args = array())
{
	$uidargs = $args;
	if (isset($uidargs['babCurrentDate']))
	{
		unset($uidargs['babCurrentDate']);
	}
	
	
	// We create a unique id based on the filename and named arguments.
	$ovmlId = $file . ':' . http_build_query($uidargs);
	

	if (!isset($_SESSION['ovml_cache'][$ovmlId])) {
		$_SESSION['ovml_cache'][$ovmlId] = array();
	}
	$ovmlCache =& $_SESSION['ovml_cache'][$ovmlId];

	// We check if there the specified ovml is in the cache and the cache is
	// less than 1 hour (or the specified duration) old.
	if (!isset($ovmlCache['timestamp'])
	|| !isset($ovmlCache['content']) 
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
	return $tpl->printout(implode('', file($filepath)), $filepath);
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

	$mots = preg_split('/[\s,\.]+/', $text, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_OFFSET_CAPTURE);

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
			// putenv("LANG=${languageCode}");
			// putenv("LANGUAGE=${languageCode}");

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



function bab_getHashVar()
{
	if (defined('BAB_HASH_VAR'))
	{
		return BAB_HASH_VAR;
	} else {
		return 'aqhjlongsmp';
	}
}


function bab_initMbString() {
	mb_internal_encoding(bab_charset::getIso());
	mb_http_output(bab_charset::getIso());
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
	public function bab_functionality() { }


	public static function getRootPath() {
		require_once dirname(__FILE__).'/defines.php';
		return realpath('.').'/'.BAB_FUNCTIONALITY_ROOT_DIRNAME;
	}


	/**
	 * Include php file with the functionality class
	 * @see bab_functionality::get()
	 * @param	string	$path		path to functionality
	 * @return string | false		the object class name or false if the file already included or false if the include failed
	 */
	public static function includefile($path) {
		$include_result = /*@*/include self::getRootPath().'/'.$path.'/'.BAB_FUNCTIONALITY_LINK_FILENAME;

		if (false === $include_result) {
			trigger_error(sprintf('The functionality %s is not available', $path));
		}

		return $include_result;
	}
	
	/**
	 * Include original php file with the functionality class
	 * 
	 * @since 7.8.90
	 * 
	 * @param string $path 			path to functionality
	 * @return string | false		the object class name or false if the file already included or false if the include failed
	 */
	public static function includeOriginal($path)
	{
		return include self::getRootPath().'/'.$path.'/'.BAB_FUNCTIONALITY_LINK_ORIGINAL_FILENAME;
	}
	
	
	/**
	 * Returns the specified functionality object without the default inherithed object.
	 *
	 * If $singleton is set to true, the functionality object will be instanciated as
	 * a singleton, i.e. there will be at most one instance of the functionality
	 * at a given time.
	 * 
	 * @since 7.8.90
	 * 
	 * @param string 	$path
	 * @param bool 		$singleton
	 * 
	 * @return bab_functionality
	 */
	public static function getOriginal($path, $singleton = true)
	{
		$classname = bab_functionality::includeOriginal($path);
		if (!$classname) {
			return false;
		}
		if ($singleton) {
			return bab_getInstance($classname);
		}
		return new $classname();
	}
	


	/**
	 * Returns the specified functionality object.
	 *
	 * If $singleton is set to true, the functionality object will be instanciated as
	 * a singleton, i.e. there will be at most one instance of the functionality
	 * at a given time.
	 *
	 * @param	string	$path		The functionality path.
	 * @param	bool	$singleton	Whether the functionality should be instanciated as singleton (default true).
	 * @return	bab_functionality	The functionality object or false on error.
	 */
	public static function get($path, $singleton = true) {
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
	 * @param	string	$path
	 * @return array
	 */
	public static function getFunctionalities($path) {
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
 * @return bab_addonInfos
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


/**
 * Download a file
 * @since 7.2.92
 *
 * @param 	bab_Path 	$path			path to source file
 * @param	string		[$filename]		downloaded filename
 * @param	bool		$inline			inline or attachment mode
 * @param	bool		$exit			exit after download
 * @return unknown_type
 */
function bab_downloadFile(bab_Path $path, $filename = null, $inline = true, $exit = true)
{
	if (null === $filename)
	{
		$filename = $path->getBasename();
	}

	$fp = fopen($path->toString(), 'rb');
	if ($fp)
	{
		bab_setTimeLimit(3600);

		if (mb_strtolower(bab_browserAgent()) == 'msie') {
			// header('Cache-Control: public');
			
			// IE8 + https bug : http://stackoverflow.com/questions/1242900/problems-with-header-when-displaying-a-pdf-file-in-ie8
			header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
			header("Pragma: public");
		}

		if ($inline) {
			header('Content-Disposition: inline; filename="'.$filename.'"'."\n");
		} else {
			header('Content-Disposition: attachment; filename="'.$filename.'"'."\n");
		}

		$mime = bab_getFileMimeType($path->toString());
		$fsize = filesize($path->toString());
		header('Content-Type: '.$mime."\n");
		header('Content-Length: '.$fsize."\n");
		header('Content-transfert-encoding: binary'."\n");

		while (!feof($fp)) {
			print fread($fp, 8192);
		}
		fclose($fp);

		if ($exit) {
			exit;
		}

		return true;
	}

	return false;
}



/**
 * @return Func_Widgets
 */
function bab_Widgets()
{
	return bab_functionality::get('Widgets');
}


/**
 * Strip tags and add spaces
 * @param string $str
 */
function bab_strip_tags($str)
{
	$str = preg_replace('/\<[^<]+\>/', '${0} ', $str);
	
	return strip_tags($str);
}




/**
 * return non breaking space
 * @return string
 */
function bab_nbsp()
{
	switch(bab_charset::getIso()) {
		case 'UTF-8':
			return chr(0xC2).chr(0xA0);
		case 'ISO-8859-15':
			return chr(160);
		default:
			return '-';
	}
}
