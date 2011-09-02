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

/**
* @internal SEC1 PR 2006-12-12 FULL
*/


include_once 'base.php';
include_once $babInstallPath.'utilit/addonapi.php';
include_once $babInstallPath.'utilit/template.php';
include_once $babInstallPath.'utilit/userincl.php';
include_once $babInstallPath.'utilit/mailincl.php';
include_once $babInstallPath.'utilit/sitemap.php';
include_once $babInstallPath.'utilit/eventincl.php';




function bab_encrypt($txt,$key)
	{
	$td = mcrypt_module_open(MCRYPT_TRIPLEDES, '', MCRYPT_MODE_CFB, '');
	if( $td === false)
		return '';
	$key = mb_substr($key, 0, mcrypt_enc_get_key_size($td));
	$iv_size = mcrypt_enc_get_iv_size($td);
	$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	if( mcrypt_generic_init($td, $key, $iv) != -1 )
		{
		$crypttxt = mcrypt_generic($td, $txt);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		$crypttxt = $iv.$crypttxt;
		return $crypttxt;
		}
	return '';
	}

function bab_decrypt($txt,$key)
	{
	$td = mcrypt_module_open(MCRYPT_TRIPLEDES, '', MCRYPT_MODE_CFB, '');
	if( $td === false)
		return '';
	$key = mb_substr($key, 0, mcrypt_enc_get_key_size($td));
	$iv_size = mcrypt_enc_get_iv_size($td);
	$iv = mb_substr($txt,0,$iv_size);
	$txt = mb_substr($txt,$iv_size);
	if (mcrypt_generic_init($td, $key, $iv) != -1)
		{
		$crypttxt = mdecrypt_generic($td, $txt);
		mcrypt_generic_deinit($td);
		mcrypt_module_close($td);
		return rtrim($crypttxt);
		}
	return '';
	}

function bab_formatAuthor($format, $id)
{
	global $babDB;
	static $bab_authors = array();

	$txt = bab_translate('Anonymous');

	if( !empty($id))
		{
		if( !isset($bab_authors[$id])) {	
			$res = $babDB->db_query("select givenname, sn, mn from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='0' and id_user='".$babDB->db_escape_string($id)."'");
			if( $res && $babDB->db_num_rows($res) > 0 ) {
				$bab_authors[$id] = $babDB->db_fetch_array($res);
			}
		}
		if (isset($bab_authors[$id])) {
			if(preg_match_all('/%(.)/', $format, $m))
				{
				$txt = $format;
				for( $i = 0; $i< count($m[1]); $i++)
					{
					switch($m[1][$i])
						{
						case 'F':
							$val = $bab_authors[$id]['givenname'];
							break;
						case 'L':
							$val = $bab_authors[$id]['sn'];
							break;
						case 'M':
							$val = $bab_authors[$id]['mn'];
							break;
						}
					$txt = preg_replace('/'.preg_quote($m[0][$i]).'/', $val, $txt);
					}
				}
			}
		}

	return $txt;
}

function bab_stripDomainName($txt) {
	return eregi_replace("((href|src)=['\"]?)".$GLOBALS['babUrl'], '\\1', $txt);
	}

function bab_isEmailValid($email)
	{
	if( empty($email) || preg_match('/\s+/', $email))
		return false;
	else
		return true;
	}

function bab_getCssUrl()
	{
	global $babInstallPath, $babSkinPath;
	$filepath = 'skins/'.$GLOBALS['babSkin'].'/styles/'. $GLOBALS['babStyle'];
	if( !file_exists( $filepath ) )
		{
		$filepath = $babSkinPath.'styles/'. $GLOBALS['babStyle'];
		if( !file_exists( $filepath ) )
			{
			$filepath = $babInstallPath.'skins/ovidentia/styles/ovidentia.css';
			}
		}
	return $filepath;
	}

/**
 * Date and time format proposed for site configuration and user configuration
 * @array
 */
function bab_getRegionalFormats() {

	return array(
		'longDate' => array(
			'dd MMMM yyyy',
			'MMMM dd, yyyy',
			'dddd, MMMM dd, yyyy',
			'dddd dd MMMM yyyy',
			'dd MMMM, yyyy'
		),

		'shortDate'	=> array(
			'M/d/yyyy',
			'dd/MM/yyyy',
			'dd/MM/yy',
			'M/d/yy',
			'MM/dd/yy',
			'MM/dd/yyyy',
			'yy/MM/dd',
			'yyyy-MM-dd',
			'dd-MMM-yy'
		),
			
		'hour' => array(
			'HH:mm',
			'HH:mm tt',
			'HH:mm TT',
			'HH:mm:ss tt',
			'HH:mm:ss tt',
			'h:mm:ss tt',
			'hh:mm:ss tt',
			'HH:mm:ss',
			'H:m:s'
		)
	);
}



function bab_getDateFormat($format)
{
	$format = strtolower($format);


	$format = preg_replace("/(?<!m)m(?!m)/", "$1%n$2", $format);
	$format = preg_replace("/(?<!m)mm(?!m)/", "$1%n$2", $format);
	$format = preg_replace("/(?<!m)mmm(?!m)/", "$1%m$2", $format);
	$format = preg_replace("/(?<!m)m{4,}(?!m)/", "$1%M$3", $format);

	$format = preg_replace("/(?<!d)d(?!d)/", "$1%j$2", $format);
	$format = preg_replace("/(?<!d)dd(?!d)/", "$1%j$2", $format);
	$format = preg_replace("/(?<!d)ddd(?!d)/", "$1%d$2", $format);
	$format = preg_replace("/(?<!d)d{4,}(?!d)/", "$1%D$2", $format);
	
	$format = preg_replace("/(?<!y)y(?!y)/", "$1%y$2", $format);
	$format = preg_replace("/(?<!y)yy(?!y)/", "$1%y$2", $format);
	$format = preg_replace("/(?<!y)yyy(?!y)/", "$1%Y$2", $format);
	$format = preg_replace("/(?<!y)y{4,}(?!y)/", "$1%Y$2", $format);

	return $format;
}

function bab_getTimeFormat($format)
{
	global $babBody;
	$pos = mb_strpos(mb_strtolower($format), 't');
	if( $pos !== false)
	{
		$babBody->ampm = true;
	}
	else
	{
		$babBody->ampm = false;
	}

	$format = preg_replace("/(?<!h)h(?!h)/", "$1g$2", $format);
	$format = preg_replace("/(?<!h)h{2,}(?!h)/", "$1h$2", $format);

	$format = preg_replace("/(?<!H)H(?!H)/", "$1G$2", $format);
	$format = preg_replace("/(?<!H)H{2,}(?!H)/", "$1H$2", $format);

	$format = preg_replace("/(?<!m)m{1,}(?!m)/", "$1i$2", $format);
	$format = preg_replace("/(?<!s)s{1,}(?!s)/", "$1s$2", $format);

	$format = preg_replace("/(?<!t)t{1,}(?!t)/", "$1a$2", $format);
	$format = preg_replace("/(?<!T)T{1,}(?!T)/", "$1A$2", $format);

	return $format;
}





/**
 * This function convert the input string to the charset
 * of the database. If the charset of the string and the
 * database match so the string is not converted.
 *
 * bug with utf8 in url on firefox
 *
 * @param	string $sString	The string to convert
 * @return	string			The converted input string
 */
function bab_convertToDatabaseEncoding($sString)
{
	/* An ending '�' (and probably other accentuated chars) mislead mb_detect_encoding
	 * Adding a character will suppress the situation where the error occurs and will not modify our variable. 
	 * And it will still work if the error in the function will be fixed one day.
	 */
	$sDetectedEncoding	= mb_detect_encoding($sString.'a', 'UTF-8, ISO-8859-15');
	$sEncoding			= bab_charset::getIso(); 
		
	if($sEncoding != $sDetectedEncoding)
	{
		return mb_convert_encoding($sString, $sEncoding, $sDetectedEncoding);
	}
	return $sString;
}


/**
 * return non breakin space
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






class babMenu
{
var $curItem = '';
var $items = array();

function babMenu()
{
	$GLOBALS['babCurrentMenu'] = '';
}

function addItem($title, $txt, $url, $enabled=true)
{
	$this->items[$title]['text'] = $txt;
	$this->items[$title]['url'] = $url;
	$this->items[$title]['enabled'] = $enabled;
}

function addItemAttributes($title, $attr)
{
	$this->items[$title]['attributes'] = $attr;
}

function setCurrent($title, $enabled=false)
{
	foreach($this->items as $key => $val)
		{
		if( !strcmp($key, $title))
			{
			$this->curItem = $key;
			$this->items[$key]['enabled'] = $enabled;
			if( !$enabled )
				$GLOBALS['babCurrentMenu'] = $this->items[$key]['text'];
			break;
			}
		}
}
}  /* end of class babMenu */

class babBody
{
var $sections = array();
var $menu;

/**
 * error message as html
 * @access public
 */
var $msgerror;

/**
 * List of errors as text
 * @see babBody::addError()
 * @access public
 */
var $errors = array();

var $content;

/**
 * Page title
 * @access public
 */
var $title;

var $message;
var $script;
var $lastlog; /* date of user last log */

/* $babBody->get_topcatview() */
/* all topics categories : $babBody->get_topcats() */

var $calaccess;
var $isSuperAdmin;
var $currentAdmGroup; /* current group administrated by current user */	
var $currentDGGroup; /* contains database row of current delegation groups */
var $dgAdmGroups; /* all groups administrated by current user */
var $ovgroups; /* all ovidentia groups */
var $groupPathName; /* see function getGroupPathName */
var $babsite;
var $ocids; /* orgnization chart ids */
var $ampm; /* true: use am/pm */
var $waitapprobations; /* true if there are waiting approbations */
var $acltables = array();
var $idprimaryoc = 0; /* id of primary organizational chart */
var $substitutes = array();
var $styleSheet = array();

//var $aclfm;
//var $babsite;
//var $waitingarticles;
//var $waitingcomments;

function babBody()
{
	global $babDB;
	$this->menu = new babMenu();
	$this->message = '';
	$this->script = '';
	$this->title = '';
	$this->msgerror = '';
	$this->content = '';
	$this->lastlog = '';
	$this->calaccess = false;
	$this->isSuperAdmin = false;
	$this->currentAdmGroup = 0;
	$this->currentDGGroup = array('id' => 0);
	$this->dgAdmGroups = array();
	$this->usergroups = array();
	$this->saarray = array();
	$this->babaddons = array();
	$this->waitapprobations = false;
	$this->substitutes[0] = array(); /* nominatif */
	$this->substitutes[1] = array(); /* fonctionnel */


	if (isset($_SERVER['REMOTE_ADDR'])) {
		$REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
	} else {
		$REMOTE_ADDR = '0.0.0.0';
	}



	if ( session_id() && (bab_rp('tg') !== 'version' || bab_rp('idx') !== 'upgrade'))
		{
		$res = $babDB->db_query("select remote_addr, grp_change, schi_change from ".BAB_USERS_LOG_TBL." where sessid='".session_id()."'");
		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$arr = $babDB->db_fetch_assoc($res);
			if ((isset($GLOBALS['babCheckIpAddress']) && $GLOBALS['babCheckIpAddress'] === true) && $arr['remote_addr'] != $REMOTE_ADDR)
				{
				die(bab_translate("Access denied, your session id has been created by another ip address than yours"));
				}

			if (1 == $arr['grp_change'] && isset($_SESSION['bab_groupAccess']))
				{
				unset($_SESSION['bab_groupAccess']);
				}

			if (1 == $arr['schi_change'] && isset($_SESSION['bab_waitingApprobations']))
				{
				unset($_SESSION['bab_waitingApprobations']);
				}
			}
		}
	

	if (isset($_SESSION['bab_groupAccess']['ovgroups']))
		{
		$this->ovgroups = &$_SESSION['bab_groupAccess']['ovgroups'];
		}
	else
		{
		$res = $babDB->db_query("select * from ".BAB_GROUPS_TBL."");
		while( $arr = $babDB->db_fetch_array($res))
			{
			$arr['member'] = 'N';
			$arr['primary'] = 'N';
			$this->ovgroups[$arr['id']] = $arr;
			}

		$_SESSION['bab_groupAccess']['ovgroups'] = &$this->ovgroups;
		}
	
	if (isset($_SESSION['bab_groupAccess']['usergroups']))
		{
		$this->usergroups = &$_SESSION['bab_groupAccess']['usergroups'];
		}
	else
		{
		$_SESSION['bab_groupAccess']['usergroups'] = &$this->usergroups;
		}
}

function getGroupPathName($id_group, $id_parent = BAB_REGISTERED_GROUP)
{
	if (isset($this->groupPathName[$id_parent][$id_group]))
		return $this->groupPathName[$id_parent][$id_group];
	
	include_once $GLOBALS['babInstallPath'].'utilit/grptreeincl.php';

	$this->groupPathName[$id_parent] = array();
	
	$tree = new bab_grptree();
	$groups = $tree->getGroups($id_parent);
	$arr = array();
	foreach ($groups as $row)
		{
		$this->groupPathName[$id_parent][$row['id']] = $row['name'];
		}

	return isset($this->groupPathName[$id_parent][$id_group]) ? $this->groupPathName[$id_parent][$id_group] : '';
}

function getSetOfGroupName($id_group)
{
	static $groupset = array();
	global $babDB;

	if (isset($groupset[$id_group]))
		{
		return $groupset[$id_group];
		}
	
	$res = $babDB->db_query("SELECT id, name FROM ".BAB_GROUPS_TBL." WHERE nb_groups>='0'");
	while( $arr = $babDB->db_fetch_array($res))
	{
		$groupset[$arr['id']] = bab_translate("Sets of groups").' > '.$arr['name'];
	}
	return isset($groupset[$id_group]) ? $groupset[$id_group] : '';
}


function resetContent()
{
	$this->content = '';
}


function babecho($txt)
{
	$this->content .= $txt;
}

/**
 * Set page title with a text string (no html)
 * @param	string $title
 */
function setTitle($title) {
	$this->title = bab_toHtml($title);
}

/**
 * Add error message
 * @param	string $title
 */
function addError($error) {
	$this->errors[] = $error;
	if (empty($this->msgerror)) {
		$this->msgerror = bab_toHtml($error);
	} else {
		$this->msgerror .= '<br /> '.bab_toHtml($error);
	}
}

/**
 * View as popup
 * @param	string	$txt
 * 
 */ 
function babpopup($txt) {
	include_once $GLOBALS['babInstallPath'].'utilit/uiutil.php';
	$GLOBALS['babBodyPopup'] = new babBodyPopup();
	$GLOBALS['babBodyPopup']->menu 			= & $GLOBALS['babBody']->menu;
	$GLOBALS['babBodyPopup']->styleSheet 	= & $GLOBALS['babBody']->styleSheet;
	$GLOBALS['babBodyPopup']->title 		= & $GLOBALS['babBody']->title;
	$GLOBALS['babBodyPopup']->msgerror 		= & $GLOBALS['babBody']->msgerror;
	$GLOBALS['babBody']->babecho($txt);
	$GLOBALS['babBodyPopup']->babecho($GLOBALS['babBody']->content);
	printBabBodyPopup();
	die();
}


function loadSections()
{
	
	include_once $GLOBALS['babInstallPath'].'utilit/utilitsections.php';
	
	global $babDB, $babBody, $BAB_SESS_LOGGED, $BAB_SESS_USERID;
	$babSectionsType = isset($_SESSION['babSectionsType'])? $_SESSION['babSectionsType']:BAB_SECTIONS_ALL;
	$req = "SELECT ".BAB_SECTIONS_ORDER_TBL.".*, ".BAB_SECTIONS_STATES_TBL.".closed, ".BAB_SECTIONS_STATES_TBL.".hidden, ".BAB_SECTIONS_STATES_TBL.".id_section AS states_id_section FROM ".BAB_SECTIONS_ORDER_TBL." LEFT JOIN ".BAB_SECTIONS_STATES_TBL." ON ".BAB_SECTIONS_STATES_TBL.".id_section=".BAB_SECTIONS_ORDER_TBL.".id_section AND ".BAB_SECTIONS_STATES_TBL.".type=".BAB_SECTIONS_ORDER_TBL.".type AND ".BAB_SECTIONS_STATES_TBL.".id_user='".$babDB->db_escape_string($BAB_SESS_USERID)."' ORDER BY ".BAB_SECTIONS_ORDER_TBL.".ordering ASC";
	$res = $babDB->db_query($req);
	$arrsections = array();
	$arrsectionsinfo = array();
	$arrsectionsbytype = array();
	$arrsectionsorder = array();

	while($arr =  $babDB->db_fetch_array($res))
		{
			$objectid = $arr['id'];

			$arrsectioninfo = array('close'=>0, 'bshow'=>false);
			$typeid = $arr['type'];
			$sectionid = $arr['id_section'];

			if(isset($arr['states_id_section']) && !empty($arr['states_id_section']))
				{
					if( $arr['closed'] == 'Y')
						{
							$arrsectioninfo['close'] = 1;
						}
					if( $arr['hidden'] == 'N')
						{
							$arrsectioninfo['bshow'] = true;
						}
				}

			if($typeid == 1 || $typeid == 3 || $typeid == 4)
				{
					$arrsectionsbytype[$typeid][$sectionid] = $objectid;
					$arrsectioninfo['type'] = $typeid;
				}
			else
				{
					$arrsectionsbytype['users'][$sectionid] = $objectid;
					$arrsectioninfo['type'] = $typeid;
				}

			$arrsectioninfo['position'] = $arr['position'];
			$arrsectioninfo['sectionid'] = $sectionid;
			$arrsectionsinfo[$objectid] = $arrsectioninfo;

			$arrsectionsorder[] = $objectid;
		}

	// BAB_PRIVATE_SECTIONS_TBL

	$type = 1;
	if(!empty($arrsectionsbytype[$type]) && ($babSectionsType & BAB_SECTIONS_CORE) )
		{
			$res2 = $babDB->db_query("select * from ".BAB_PRIVATE_SECTIONS_TBL." where id IN(".$babDB->quote(array_keys($arrsectionsbytype[$type])).")");
			while($arr2 = $babDB->db_fetch_array($res2))
				{
					$arrdbinfo[$arr2['id']] = $arr2;
				}
			foreach($arrsectionsbytype[$type] AS $sectionid => $objectid)
				{
					$arr2 = $arrdbinfo[$sectionid];
					$arrsectioninfo = $arrsectionsinfo[$objectid];

					switch($sectionid)
						{
							case 1: // admin
								if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && ($babBody->isSuperAdmin || $babBody->currentAdmGroup != 0))
									{
										$sec = new babAdminSection($arrsectioninfo['close']);
										$arrsections[$objectid] = $sec;
									}
								break;
							case 2: // month
								if( $arr2['enabled'] == 'Y' && ( $arr2['optional'] == 'N' || $arrsectioninfo['bshow'] ))
									{
										$sec = new babMonthA();
										$arrsections[$objectid] = $sec;
									}
								break;
							case 3: // topics
								$sec = new babTopcatSection($arrsectioninfo['close']);
								if( $sec->count > 0 )
									{
										if( $arr2['enabled'] == 'Y' && ( $arr2['optional'] == 'N' || $arrsectioninfo['bshow'] ))
											{
												$arrsections[$objectid] = $sec;
											}
									}
								break;
							case 4: // Forums
								$sec = new babForumsSection($arrsectioninfo['close']);
								if( $sec->count > 0 )
									{
										if( $arr2['enabled'] == 'Y'  && ( $arr2['optional'] == 'N' || $arrsectioninfo['bshow'] ))
											{
												$arrsections[$objectid] = $sec;
											}
									}
								break;
							case 5: // user's section
								if( $arr2['enabled'] == 'Y' )
									{
										$sec = new babUserSection($arrsectioninfo['close']);
										$arrsections[$objectid] = $sec;
									}
								break;
						}
				}
		}

	// BAB_TOPICS_CATEGORIES_TBL sections
	$type = '3';
	if(!empty($arrsectionsbytype[$type]) && ($babSectionsType & BAB_SECTIONS_ARTICLES))
		{
			if( isset($_SESSION['babOvmlCurrentDelegation']) && $_SESSION['babOvmlCurrentDelegation'] !== '' )
			{
				$req = "select id, enabled, optional from ".BAB_TOPICS_CATEGORIES_TBL." where id IN(".$babDB->quote(array_keys($arrsectionsbytype[$type])).") and id_dgowner='".$babDB->db_escape_string($_SESSION['babOvmlCurrentDelegation'])."'";
			}
			else
			{
				$req = "select id, enabled, optional from ".BAB_TOPICS_CATEGORIES_TBL." where id IN(".$babDB->quote(array_keys($arrsectionsbytype[$type])).")";
			}
			$res2 = $babDB->db_query($req);
			while($arr2 = $babDB->db_fetch_array($res2))
				{
					$sectionid = $arr2['id'];
					$objectid = $arrsectionsbytype[$type][$sectionid];
					if( $arr2['enabled'] == 'Y' && ( $arr2['optional'] == 'N' || $arrsectionsinfo[$objectid]['bshow'] ))
						{
							$sec = new babTopicsSection($sectionid, $arrsectionsinfo[$objectid]['close']);
							if( $sec->count > 0 )
								{
									$arrsections[$objectid] = $sec;
								}
						}
				}
		}

	// BAB_ADDONS_TBL sections
	$type = '4';
	if(!empty($arrsectionsbytype[$type]) && ($babSectionsType & BAB_SECTIONS_ADDONS))
		{
		$at = array_keys($arrsectionsbytype[$type]);
		for($i=0; $i < count($at); $i++)
			{
				if($arr2 = bab_addonsInfos::getRow($at[$i]))
				{
				$sectionid = $arr2['id'];
				$objectid = $arrsectionsbytype[$type][$sectionid];

				if( $arr2['access'] && is_file($GLOBALS['babAddonsPath'].$arr2['title'].'/init.php'))
					{
					bab_setAddonGlobals($arr2['id']);
					
					require_once( $GLOBALS['babAddonsPath'].$arr2['title'].'/init.php' );
					$func = $arr2['title'].'_onSectionCreate';
					if(function_exists($func))
						{
						if (!isset($template)) $template = false;
						$stitle = '';
						$scontent = '';
						if($func($stitle, $scontent, $template))
							{
								if( !$arrsectionsinfo[$objectid]['close'])
									{
									$sec = new babSection($stitle, $scontent);
									$sec->setTemplate($template);
									}
								else
									{
									$sec = new babSection($stitle, '');
									}
								$sec->setTemplate($arr2['title']);
								$sec->htmlid = $arr2['title'];
								$arrsections[$objectid] = $sec;
							}
						}
					}
				}
			}
		}

	// personnalized sections
	$type = 'users';
	if(!empty($arrsectionsbytype[$type]) && ($babSectionsType & BAB_SECTIONS_SITE))
		{
			$langFilterValues = $GLOBALS['babLangFilter']->getLangValues();
			$req = "SELECT * FROM ".BAB_SECTIONS_TBL." WHERE id IN(".$babDB->quote(array_keys($arrsectionsbytype[$type])).") and enabled='Y'";
			if( count($langFilterValues) > 0 )
				{
					$req .= " AND SUBSTRING(lang, 1, 2 ) IN ('*',".implode(',',$langFilterValues).")"; // $langFilterValues is already escaped
				}
			if( isset($_SESSION['babOvmlCurrentDelegation']) && $_SESSION['babOvmlCurrentDelegation'] !== '')
			{
				$req .=" and id_dgowner='".$babDB->db_escape_string($_SESSION['babOvmlCurrentDelegation'])."'";
			}
			$res2 = $babDB->db_query($req);
			while($arr2 = $babDB->db_fetch_array($res2))
				{
					$sectionid = $arr2['id'];
					$objectid = $arrsectionsbytype[$type][$sectionid];
					if(bab_isAccessValid(BAB_SECTIONS_GROUPS_TBL, $sectionid))
						{
							if($arr2['optional'] == 'N' || $arrsectionsinfo[$objectid]['bshow'])
								{
									if(!$arrsectionsinfo[$objectid]['close'])
										{
										if( $arr2['script'] == 'Y')
											{
												eval("\$arr2['content'] = \"".$arr2['content']."\";");
											}
										else 
											{
												include_once $GLOBALS['babInstallPath'].'utilit/editorincl.php';
												$editor = new bab_contentEditor('bab_section');
												$editor->setContent($arr2['content']);
												$editor->setFormat($arr2['content_format']);
												$arr2['content'] = $editor->getHtml();
											}
										$sec = new babSection($arr2['title'], $arr2['content']);
										}
									else
										{
										$sec = new babSection($arr2['title'], '');
										}
									$sec->setTemplate($arr2['template']);
									$sec->htmlid = 'customsection';
									$arrsections[$objectid] = $sec;
								}
						}
				}
		}

	foreach($arrsectionsorder AS $objectid)
		{
			$sectionid = $arrsectionsinfo[$objectid]['sectionid'];
			$type = $arrsectionsinfo[$objectid]['type'];
			if(isset($arrsections[$objectid]))
				{
					$sec = $arrsections[$objectid];
					$sec->setPosition($arrsectionsinfo[$objectid]['position']);
					$sec->close = $arrsectionsinfo[$objectid]['close'];
					$sec->bbox = 1;
					if(empty($BAB_SESS_USERID))
						{
							$sec->bbox = 0;
						}
					if( $sec->close )
						{
							$sec->boxurl = $GLOBALS['babUrlScript'].'?tg=options&amp;idx=ob&amp;s='.$sectionid.'&amp;w='.$type;
						}
					else
						{
							$sec->boxurl = $GLOBALS['babUrlScript'].'?tg=options&amp;idx=cb&amp;s='.$sectionid.'&amp;w='.$type;
						}
					$babBody->addSection($sec);
				}
		}
}

function addSection($sec)
{
	array_push($this->sections, $sec);
}

function showSection($title)
{
	for( $i = 0; $i < count($this->sections); $i++)
		{
		if( !strcmp($this->sections[$i]->getTitle(), $title))
			{
			$this->sections[$i]->show();
			}
		}
}

function hideSection($title)
{
	for( $i = 0; $i < count($this->sections); $i++)
		{
		if( !strcmp($this->sections[$i]->getTitle(), $title))
			{
			$this->sections[$i]->hide();
			}
		}
}

function addItemMenu($title, $txt, $url, $enabled=true)
{
	$this->menu->addItem($title, $txt, $url, $enabled);
}

function addItemMenuAttributes($title, $attr)
{
	$this->menu->addItemAttributes($title, $attr);
}

function setCurrentItemMenu($title, $enabled=false)
{
	$this->menu->setCurrent($title, $enabled);
}

function addStyleSheet($filename)
{
	if (!in_array($filename, $this->styleSheet))
	{
		$this->styleSheet[] = $filename;
	}
}

function addJavascriptFile($file)
{
	global $babOvidentiaJs;
	static $jfiles = array();

	if( !in_array($file, $jfiles))
	{
		$jfiles[] = $file;
		$babOvidentiaJs .= '"></script><script type="text/javascript" src="'.$file; 
	}
}

/**
 * Adds some javascript code to the current page.
 *
 * @param string $code
 */
function addJavascript($code)
{
	$this->script .= "\n" . $code;
}


function getnextstylesheet()
{
return list(,$this->file) = each($this->styleSheet);
}


function printout()
{
    if (count($this->styleSheet) > 0)
		{
		$this->content = bab_printTemplate($this,'uiutil.html', 'styleSheet').$this->content;
		}
	
	if(!empty($this->msgerror))
		{
		$this->message = bab_printTemplate($this,'warning.html', 'texterror');
		//return '';
		}
	else if(!empty($this->title))
		{
		$this->message = bab_printTemplate($this,'warning.html', 'texttitle');
		}
	return $this->content;
}


function get_topcats() {
		static $topcats = null;
		if (!is_null($topcats))
			return $topcats;

		global $babDB;

		$res = $babDB->db_query("select id, title, description, id_parent from ".BAB_TOPICS_CATEGORIES_TBL."");
		while($arr = $babDB->db_fetch_array($res))
			{
			$topcats[$arr['id']]['parent'] = $arr['id_parent'];
			$topcats[$arr['id']]['title'] = $arr['title'];
			$topcats[$arr['id']]['description'] = $arr['description'];
			}

		return $topcats;
	}


function get_topcatview() {
		static $topcatview = null;
		if (!is_null($topcatview))
			return $topcatview;

		global $babDB;

		$topcatview = array();

		$topview = bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL);

		$res = $babDB->db_query("select id_cat from ".BAB_TOPICS_TBL." where id in(".$babDB->quote(array_keys($topview)).")");
		while( $row = $babDB->db_fetch_array($res))
			{
			if( !isset($topcatview[$row['id_cat']]))
				{
				$topcatview[$row['id_cat']] = 1;
				}
			}

		if(!empty($topcatview))
			{
			$topcatsview_tmp = $topcatview;
			$topcats = $this->get_topcats();
			foreach( $topcatsview_tmp as $cat => $val)
				{
				while(isset($topcats[$cat]) && $topcats[$cat]['parent'] != 0 )
					{
					if( !isset($topcatview[$topcats[$cat]['parent']]))
						{
						$topcatview[$topcats[$cat]['parent']] = 1;
						}
					$cat = $topcats[$cat]['parent'];
					}
				}
			}

		return $topcatview;
	}


function get_newarticles() {
	
	static $newarticles = null;
	if (!is_null($newarticles))	
		return $newarticles;
	
	
	$newarticles = 0;
	$topview = bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL);
	if( count($topview) > 0 )
		{
		global $babDB;
		$res = $babDB->db_query("select id_topic, restriction from ".BAB_ARTICLES_TBL." where (date_publication = '0000-00-00 00:00:00' OR date_publication <= now()) AND date >= '".$babDB->db_escape_string($this->lastlog)."'");
		while( $row = $babDB->db_fetch_array($res))
			{
			if( isset($topview[$row['id_topic']]) && ( $row['restriction'] == '' || bab_articleAccessByRestriction($row['restriction']) ))
				{
				$newarticles++;
				}
			}
		}
	return $newarticles;
	}

function get_newcomments() {

	static $newcomments = null;
	if (!is_null($newcomments))	
		return $newcomments;

	$newcomments = 0;
	$topview = bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL);
	if( count($topview) > 0 )
		{
		global $babDB;
		$res = $babDB->db_query("select id_topic from ".BAB_COMMENTS_TBL." where confirmed='Y' and date >= '".$babDB->db_escape_string($this->lastlog)."'");
		while( $row = $babDB->db_fetch_array($res))
			{
			if( isset($topview[$row['id_topic']]) )
				{
				$newcomments++;
				}
			}
		}
	return $newcomments;
	}



function get_newposts() {

	static $newposts = null;
	if (!is_null($newposts))	
		return $newposts;

	global $babDB;

	list($newposts) = $babDB->db_fetch_array($babDB->db_query("select count(p.id) from ".BAB_POSTS_TBL." p, ".BAB_THREADS_TBL." t where p.date >= '".$this->lastlog."' and p.confirmed='Y' and p.id_thread=t.id and t.forum IN(".$babDB->quote(array_keys(bab_getUserIdObjects(BAB_FORUMSVIEW_GROUPS_TBL))).")"));

	return $newposts;
	}

function get_newfiles() {

	static $newfiles = null;
	if (!is_null($newfiles))	
		return $newfiles;

	$arrfid = array();
	$arrfid = bab_getUserIdObjects(BAB_FMDOWNLOAD_GROUPS_TBL);
	
	if( is_array($arrfid) && count($arrfid) > 0 )
		{
		global $babDB;
		$req = "select count(f.id) from ".BAB_FILES_TBL." f where f.bgroup='Y' and f.state='' and f.confirmed='Y' and f.id_owner IN (".$babDB->quote($arrfid).")";
		$req .= " and f.modified >= '".$babDB->db_escape_string($this->lastlog)."'";
		$req .= " order by f.modified desc";

		list($newfiles) = $babDB->db_fetch_row($babDB->db_query($req));
		}
	else
		{
			$newfiles = 0;
		}

	return $newfiles;
	}



}  /* end of class babBody */




/**
 * Collection of calendars
 * 
 * @return bab_icalendars
 */
function bab_getICalendars() {

	static $calendars = null;

	if (null === $calendars) {
		include_once $GLOBALS['babInstallPath'].'utilit/calincl.php';
		$calendars = new bab_icalendars();
	}

	return $calendars;
}





function bab_isMemberOfTree($id_group, $id_user = '')
{
	global $babBody, $babDB;

	$lf = &$babBody->ovgroups[$id_group]['lf'];
	$lr = &$babBody->ovgroups[$id_group]['lr'];

	if (!empty($id_user))
		{
		if ($id_group == 0 || $id_group == 1)
			return true;


		$res = $babDB->db_query("SELECT COUNT(g.id) FROM ".BAB_GROUPS_TBL." g, ".BAB_USERS_GROUPS_TBL." u WHERE u.id_group=g.id AND u.id_object='".$babDB->db_escape_string($id_user)."' AND g.lf >= '".$babDB->db_escape_string($babBody->ovgroups[$id_group]['lf'])."' AND g.lr <= '".$babDB->db_escape_string($babBody->ovgroups[$id_group]['lr'])."'");
		list($n) = $babDB->db_fetch_array($res);
		return $n > 0 ? true : false;
		}
	foreach($babBody->usergroups as $idg)
	{
	if ($babBody->ovgroups[$idg]['lf'] >= $lf && $babBody->ovgroups[$idg]['lr'] <= $lr)
		{
		return true;
		}
	}
	return false;
}


function bab_updateUserSettings()
{
	global $babDB, $babBody,$BAB_SESS_USERID;

	
	if( 0 == count($babBody->usergroups) )
		{
		foreach($babBody->ovgroups as $key => $val)
			{
			$babBody->ovgroups[$key]['member'] = 'N';
			}

		$babBody->ovgroups[BAB_ALLUSERS_GROUP]['member'] = 'Y';
		$babBody->usergroups[] = BAB_ALLUSERS_GROUP;

		if( !empty($BAB_SESS_USERID))
			{		
			$res=$babDB->db_query("select id_group, isprimary from ".BAB_USERS_GROUPS_TBL." where id_object='".$babDB->db_escape_string($BAB_SESS_USERID)."'");
			$babBody->ovgroups[BAB_REGISTERED_GROUP]['member'] = 'Y';
			$babBody->usergroups[] = BAB_REGISTERED_GROUP;
			while( $arr = $babDB->db_fetch_array($res))
				{
				$babBody->usergroups[] = $arr['id_group'];
				$babBody->ovgroups[$arr['id_group']]['member'] = 'Y';
				$babBody->ovgroups[$arr['id_group']]['primary'] = $arr['isprimary'];
				}

			$res=$babDB->db_query("select distinct id_set from ".BAB_GROUPS_SET_ASSOC_TBL." where id_group IN(".$babDB->quote($babBody->usergroups).")");
			while( $arr = $babDB->db_fetch_array($res))
				{
				$babBody->usergroups[] = $arr['id_set'];
				$babBody->ovgroups[$arr['id_set']]['member'] = 'Y';
				}
				
				$babDB->db_query("update ".BAB_USERS_LOG_TBL." set id_user='".$babDB->db_escape_string($BAB_SESS_USERID)."' where sessid='".$babDB->db_escape_string(session_id())."'");			
			}
		else
			{
			$babBody->ovgroups[BAB_UNREGISTERED_GROUP]['member'] = 'Y';
			$babBody->usergroups[] = BAB_UNREGISTERED_GROUP;
			}
		}

	
	$babBody->isSuperAdmin = false;

	if( !empty($BAB_SESS_USERID))
		{
		$res=$babDB->db_query("select lang, skin, style, lastlog, langfilter, date_shortformat, date_longformat, time_format from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($BAB_SESS_USERID)."'");
		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$arr = $babDB->db_fetch_array($res);
			
			if('Y' === $babBody->babsite['change_lang']) {
			
				if( $arr['lang'] != '')
					{
					$GLOBALS['babLanguage'] = $arr['lang'];
					}
				
				if($arr['langfilter'] != '') {
					$GLOBALS['babLangFilter']->setFilter($arr['langfilter']);
				}
			
			}
			
			
			
			if('Y' === $babBody->babsite['change_skin']) {
				
				if ($arr['skin'] !== $GLOBALS['babSkin'] && !empty($arr['skin']))
					{
					$GLOBALS['babSkin'] = $arr['skin'];
				}
	
				if(!empty($arr['style']) && is_file('skins/'.$GLOBALS['babSkin'].'/styles/'.$arr['style']))
					{
					$GLOBALS['babStyle'] = $arr['style'];
				}
			}
			
			
			if('Y' === $babBody->babsite['change_date']) {

				if( $arr['date_shortformat'] != '') {
					$GLOBALS['babShortDateFormat'] = bab_getDateFormat($arr['date_shortformat']) ;
					}
	
				if( $arr['date_longformat'] != '') {
					$GLOBALS['babLongDateFormat'] = bab_getDateFormat($arr['date_longformat']) ;
					}
	
				if( $arr['time_format'] != '') {
					$GLOBALS['babTimeFormat'] = bab_getTimeFormat($arr['time_format']) ;
					}
			}
			

			$babBody->lastlog = $arr['lastlog'];

			if( $babBody->ovgroups[BAB_ADMINISTRATOR_GROUP]['member'] == 'Y') {
				$babBody->isSuperAdmin = true;
				
				if (isset($_GET['debug'])) {
					if (0 == $_GET['debug']) {
						setcookie('bab_debug', '', time() - 31536000); // remove
					} else {
						setcookie('bab_debug', $_GET['debug'], time() + 31536000); // 1 year
					}
				}
			}

			$res = $babDB->db_query("SELECT dg.id FROM ".BAB_DG_ADMIN_TBL." da,".BAB_DG_GROUPS_TBL." dg where da.id_user='".$babDB->db_escape_string($BAB_SESS_USERID)."' AND da.id_dg=dg.id AND dg.id_group >= '0'");
			while( $arr = $babDB->db_fetch_array($res) ) {
				$babBody->dgAdmGroups[] = $arr['id'];
			}
			
		}
		
		
		if('Y' === $babBody->babsite['change_unavailability']) 
			{

			$res = $babDB->db_query("select id_user, id_substitute from ".BAB_USERS_UNAVAILABILITY_TBL." where curdate() between start_date and end_date");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				unset($_SESSION['bab_waitingApprobations'][$BAB_SESS_USERID]);
				include_once $GLOBALS['babInstallPath'].'utilit/ocapi.php';
				$superiors = array();
				$entities = bab_OCGetUserEntities($BAB_SESS_USERID);
				if( count($entities['temporary']) > 0 )
					{
					for( $i=0; $i < count($entities['temporary']); $i++ )
						{
						$idsup = bab_OCGetSuperior($entities['temporary'][$i]['id']);
						if( $idsup )
							{
							$superiors[] =  $idsup['id_user'];
							}
						}
					}		
	
				while($arr = $babDB->db_fetch_array($res))
					{
					$idsup = 0;
					if( count($superiors) && in_array($arr['id_user'], $superiors))
						{
						if( count($babBody->substitutes[1]) == 0 ||  !in_array($arr['id_user'], $babBody->substitutes[1]) )
							{
							$babBody->substitutes[1][] = $arr['id_user'];
							}
						}
	
					if( $arr['id_substitute'] == $BAB_SESS_USERID && (count($babBody->substitutes[0]) == 0 || !in_array($arr['id_user'], $babBody->substitutes[0])))
						{
						$add = true;
						$entities = bab_OCGetUserEntities($arr['id_user']);
						if( count($entities['superior']) > 0 )
							{
							for( $i=0; $i < count($entities['superior']); $i++ )
								{
								$idte = bab_OCGetTemporaryEmployee($entities['superior'][$i]['id']);
								if( $idte && $idte['id_user'] != $BAB_SESS_USERID)
									{
									$add = false;
									break;
									}
								}
							}
	
						if( count($babBody->substitutes[0]) == 0 || !in_array($arr['id_user'], $babBody->substitutes[0]) )
							{
							$babBody->substitutes[0][] = $arr['id_user'];
							}
	
						if( $add && (count($babBody->substitutes[1]) == 0 || !in_array($arr['id_user'], $babBody->substitutes[1]) ))
							{
							$babBody->substitutes[1][] = $arr['id_user'];
							}
						}
					}
				}
			}
		}


	// verify skin validity

	include_once dirname(__FILE__).'/skinincl.php';
	$objSkin = new bab_skin($GLOBALS['babSkin']);
	if (!$objSkin->isAccessValid()) {
		$GLOBALS['babSkin'] = bab_skin::getDefaultSkin()->getName(); 
	}
	
	$HTTP_X_FORWARDED_FOR = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '0.0.0.0';
	$REMOTE_ADDR = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';

	$query = "select id, id_dg, id_user, cpw, sessid from ".BAB_USERS_LOG_TBL." where sessid='".$babDB->db_escape_string(session_id())."'";
	
	if ($GLOBALS['BAB_SESS_LOGGED'])
	{
		$query .= ' OR (id_user='.$babDB->quote($GLOBALS['BAB_SESS_USERID']).' AND sessid<>'.$babDB->quote(session_id()).') ORDER BY dateact DESC';
	}
	
	$res = $babDB->db_query($query);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
			$arr = $babDB->db_fetch_array($res);
			
			if ($arr['sessid'] == session_id())
			{
				bab_setUserPasswordVariable($arr['id'], $arr['cpw'], $arr['id_user']);
				bab_setCurrentDelegation($arr['id_dg']);
		
				$babDB->db_query("update ".BAB_USERS_LOG_TBL." set 
					dateact=now(), 
					remote_addr=".$babDB->quote($REMOTE_ADDR).", 
					forwarded_for=".$babDB->quote($HTTP_X_FORWARDED_FOR).", 
					id_dg='".$babDB->db_escape_string($babBody->currentDGGroup['id'])."', 
					grp_change=NULL, 
					schi_change=NULL, 
					tg='".$babDB->db_escape_string(bab_rp('tg'))."'  
				where 
					id = '".$babDB->db_escape_string($arr['id'])."'
				");
			} else {
				// another session exists for the same user ID (first is the newest)
				// we want to stay with the newest session so the current session must be disconnected
				
				require_once dirname(__FILE__).'/loginIncl.php';
				bab_signOff();
			}
		}
	else
		{
		if( !empty($BAB_SESS_USERID))
			{
			$userid = $BAB_SESS_USERID;
			if( !$babBody->isSuperAdmin && count($babBody->dgAdmGroups) > 0 )
				{
				$babBody->currentAdmGroup = $babBody->dgAdmGroups[0];
				$babBody->currentDGGroup = $babDB->db_fetch_array($babDB->db_query("select dg.* from ".BAB_DG_GROUPS_TBL." dg where dg.id_group='".$babDB->db_escape_string($babBody->dgAdmGroups[0])."'"));
				}
			}
		else
			{
			$userid = 0;
			}

		$babDB->db_query("insert into ".BAB_USERS_LOG_TBL." (id_user, sessid, dateact, remote_addr, forwarded_for, id_dg, grp_change, schi_change, tg) values ('".$babDB->db_escape_string($userid)."', '".session_id()."', now(), '".$babDB->db_escape_string($REMOTE_ADDR)."', '".$babDB->db_escape_string($HTTP_X_FORWARDED_FOR)."', '".$babDB->db_escape_string((int) $babBody->currentDGGroup['id'])."', NULL, NULL, '".$babDB->db_escape_string(bab_rp('tg'))."')");
		}
		
		
	$maxlife = (int) get_cfg_var('session.gc_maxlifetime');
	if (0 === $maxlife)
		{
		$maxlife = 1440;
		}
		
	$babDB->db_query("delete from ".BAB_USERS_LOG_TBL." where (UNIX_TIMESTAMP(dateact) + ".$babDB->quote($maxlife).") < UNIX_TIMESTAMP()");
}


/**
 * Set a global variable with user password if the mcrypt mode is activated
 * this work if $babEncryptionKey is set in config.php 
 * @param	int		$id		bab_user_log ID
 * @param 	string 	$cpw	encrypted password
 * @param	int		$id_user
 * @return unknown_type
 */
function bab_setUserPasswordVariable($id, $cpw, $id_user)
{
	if( extension_loaded('mcrypt') && !empty($cpw) && isset($GLOBALS['babEncryptionKey']) && !isset($_REQUEST['babEncryptionKey']) && !empty($GLOBALS['babEncryptionKey']) && !empty($BAB_SESS_USERID) && $BAB_SESS_USERID == $id_user)
	{
	$GLOBALS['babUserPassword'] = bab_decrypt($cpw, md5($id.session_id().$BAB_SESS_USERID.$GLOBALS['babEncryptionKey']));
	}
}


/**
 * Set necessary variable for delegations
 * @param int $id_dg		user current delegation
 * @return unknown_type
 */
function bab_setCurrentDelegation($id_dg)
{
	global $babBody, $babDB;
	
	if( 0 != $id_dg && count($babBody->dgAdmGroups) > 0 && in_array($id_dg, $babBody->dgAdmGroups ))
	{
		$babBody->currentDGGroup = $babDB->db_fetch_array($babDB->db_query("select dg.*, g.lf, g.lr from ".BAB_DG_GROUPS_TBL." dg, ".BAB_GROUPS_TBL." g where g.id=dg.id_group AND dg.id='".$babDB->db_escape_string($id_dg)."'"));
		$babBody->currentAdmGroup = &$babBody->currentDGGroup['id'];
		
	}
	else if( !$babBody->isSuperAdmin && count($babBody->dgAdmGroups) > 0 )
	{
		$babBody->currentDGGroup = $babDB->db_fetch_array($babDB->db_query("select dg.*, g.lf, g.lr from ".BAB_DG_GROUPS_TBL." dg, ".BAB_GROUPS_TBL." g where g.id=dg.id_group AND dg.id='".$babDB->db_escape_string($babBody->dgAdmGroups[0])."'"));
		$babBody->currentAdmGroup = &$babBody->currentDGGroup['id'];
	}
}



/**
 * Get version stored in database or NULL if Ovidentia is not installed correctely
 * @return NULL|string
 */
function bab_getDbVersion() {

	static $dbVersion = false;

	if (false === $dbVersion) {
		global $babDB;
		$dbver = array();
		$res = $babDB->db_query("select foption, fvalue from ".BAB_INI_TBL." ");
		if (3 <= $babDB->db_num_rows($res)) {
			while ($rr = $babDB->db_fetch_array($res)) {
				$dbver[$rr['foption']] = $rr['fvalue'];
			}
			
			$dbVersion = $dbver['ver_major'].".".$dbver['ver_minor'].".".$dbver['ver_build'];
			
			if (isset($dbver['ver_nightly']) && '0' != $dbver['ver_nightly']) {
				$dbVersion .= '.'.$dbver['ver_nightly'];
			}
			
		} else {
			$dbVersion = NULL;
		}
	}
	
	return $dbVersion;
}



/**
 * Get skin path
 * @return string
 */
function bab_getSkinPath() {

	global $babInstallPath;

	return $babInstallPath."skins/ovidentia/";
}









/**
 * Get the site settings and set globals variables : $babSkin, $babUploadPath...
 * This function is called from index.php
 */ 
function bab_updateSiteSettings()
{
	global $babDB, $babBody;
	
	
	

	$req="select *, DECODE(smtppassword, \"".$babDB->db_escape_string($GLOBALS['BAB_HASH_VAR'])."\") as smtppass, DECODE(ldap_adminpassword, \"".$babDB->db_escape_string($GLOBALS['BAB_HASH_VAR'])."\") as ldap_adminpassword from ".BAB_SITES_TBL." where name='".$babDB->db_escape_string($GLOBALS['babSiteName'])."'";
	$res=$babDB->db_query($req);
	if ($babDB->db_num_rows($res) == 0)
		{
		$babBody->msgerror = bab_translate("Configuration error : babSiteName in config.php not match site name in administration sites configuration");
		}
	$arr = $babDB->db_fetch_assoc($res);
	$babBody->babsite = $arr;


	$GLOBALS['babSkin'] = $arr['skin'];

	
	

	if( $arr['style'] != '')
		{
		$GLOBALS['babStyle'] = $arr['style'];
		}
	else {
		$GLOBALS['babStyle'] = 'ovidentia.css'; 
		}

	if( $arr['lang'] != '')
		{
		$GLOBALS['babLanguage'] = $arr['lang'];
		}
	else {
		$GLOBALS['babLanguage'] = 'en'; }

	


	if( $arr['adminemail'] != '')
		{
		$GLOBALS['babAdminEmail'] = $arr['adminemail'];
		}
	else {
		$GLOBALS['babAdminEmail'] = 'admin@your-domain.com'; }
	if( $arr['langfilter'] != '')
		{
		$GLOBALS['babLangFilter']->setFilter($arr['langfilter']);
		}
	else {
		$GLOBALS['babLangFilter']->setFilter(0); }
	// options bloc2
	if( !empty($arr['total_diskspace']))
		{
		$GLOBALS['babMaxTotalSize'] = $arr['total_diskspace']*1048576;
		}
	else
		{
		$GLOBALS['babMaxTotalSize'] = '200000000';
		}
	if( !empty($arr['user_diskspace']))
		{
		$GLOBALS['babMaxUserSize'] = $arr['user_diskspace']*1048576;
		}
	else
		{
		$GLOBALS['babMaxUserSize'] = '30000000';
		}
	if( !empty($arr['folder_diskspace']))
		{
		$GLOBALS['babMaxGroupSize'] = $arr['folder_diskspace']*1048576;
		}
	else
		{
		$GLOBALS['babMaxGroupSize'] = '50000000'; 
		}
		
	if( !empty($arr['imgsize']))
		{
		$GLOBALS['babMaxImgFileSize'] = $arr['imgsize']*1024;
		}
	else
		{
		$GLOBALS['babMaxImgFileSize'] = 0; 
		}
		

	if( !empty($arr['maxfilesize']))
		{
		$GLOBALS['babMaxFileSize'] = $arr['maxfilesize']*1048576;
		}
	else
		{
		include_once $GLOBALS['babInstallPath'].'utilit/inifileincl.php';
		$GLOBALS['babMaxFileSize'] = bab_inifile_requirements::getIniMaxUpload(); 
		}
	if( !empty($arr['maxzipsize']) && $arr['maxzipsize']<$GLOBALS['babMaxFileSize'])
		{
		$GLOBALS['babMaxZipSize'] = $arr['maxzipsize']*1048576;
		}
	else
		{
		$GLOBALS['babMaxZipSize'] = $GLOBALS['babMaxFileSize'];
		}
		
	if( !empty($arr['uploadpath']))
		{
		$GLOBALS['babUploadPath'] = $arr['uploadpath'];
		}
	else
		{
		$GLOBALS['babUploadPath'] = '';
		}

	if( $arr['babslogan'] != '')
		{
		$GLOBALS['babSlogan'] = $arr['babslogan'];
		}
	else
		{
			$GLOBALS['babSlogan'] = '';
		}
	if( $arr['name_order'] != '')
		{
		$GLOBALS['babBody']->nameorder = explode(' ',$arr['name_order']);
		}
	else {
		$GLOBALS['babBody']->nameorder = Array('F','L');}
	if( $arr['remember_login'] == 'Y')
		{
		$GLOBALS['babCookieIdent'] = true;
		}
	elseif ($arr['remember_login'] == 'L')
		{
		$GLOBALS['babCookieIdent'] = 'login';
		}
	else {
		$GLOBALS['babCookieIdent'] = false ; 
		}
	if( $arr['email_password'] == 'Y') {
		$GLOBALS['babEmailPassword'] = true ; }
	else {
		$GLOBALS['babEmailPassword'] = false ; }

	$GLOBALS['babAdminName'] = $arr['adminname'];

	if( $arr['date_shortformat'] == '') {
		$GLOBALS['babShortDateFormat'] = bab_getDateFormat('dd/mm/yyyy') ; }
	else {
		$GLOBALS['babShortDateFormat'] = bab_getDateFormat($arr['date_shortformat']) ; }

	if( $arr['date_longformat'] == '') {
		$GLOBALS['babLongDateFormat'] = bab_getDateFormat('ddd dd mmmm yyyy') ; }
	else {
		$GLOBALS['babLongDateFormat'] = bab_getDateFormat($arr['date_longformat']) ; }

	if( $arr['time_format'] == '') {
		$babBody->ampm = false;
		$GLOBALS['babTimeFormat'] = bab_getTimeFormat('HH:mm') ; }
	else {
		$GLOBALS['babTimeFormat'] = bab_getTimeFormat($arr['time_format']) ; }

	if( $arr['authentification'] == 1 ) // LDAP authentification
		{
		$babBody->babsite['registration'] ='N';
		$babBody->babsite['change_nickname'] ='N';
		}


	if (NULL === bab_getDbVersion()) {
		include_once $GLOBALS['babInstallPath'].'utilit/upgradeincl.php';
		bab_newInstall();
	}
	

	$res = $babDB->db_query("select id, UNIX_TIMESTAMP(dateact) as lasthit, UNIX_TIMESTAMP() as time from ".BAB_USERS_LOG_TBL);
	while( $row  = $babDB->db_fetch_array($res))
		{
		if( ($row['lasthit'] + get_cfg_var('session.gc_maxlifetime')) < $row['time']) 
			{
			$res2 = $babDB->db_query("select id from ".BAB_ART_DRAFTS_TBL." where id_author='0' and id_anonymous='".$babDB->db_escape_string($row['id'])."'");
			while( $arr  = $babDB->db_fetch_array($res2))
				{
				bab_deleteArticleDraft($arr['id']);
				}
			$babDB->db_query("delete from ".BAB_USERS_LOG_TBL." where id='".$babDB->db_escape_string($row['id'])."'");
			}
		}
		
		
		
	$babDB->db_query('LOCK TABLE '.BAB_ART_DRAFTS_TBL.' WRITE');

	$res = $babDB->db_query("select id,id_author, id_topic, id_article, date_submission from ".BAB_ART_DRAFTS_TBL." where result='".BAB_ART_STATUS_DRAFT."' and date_submission <= now() and date_submission !='0000-00-00 00:00:00'");
	$drafts = array();
	while( $arr  = $babDB->db_fetch_array($res))
	{
		$drafts[$arr['id']] = $arr;
	}
	
	if( $drafts)
	{
		$babDB->db_query("UPDATE ".BAB_ART_DRAFTS_TBL." SET date_submission='0000-00-00 00:00:00' WHERE id IN(".$babDB->quote(array_keys($drafts)).")");
	}
	
	$babDB->db_query('UNLOCK TABLES');
		
	if( $drafts )
	{		
	include_once $GLOBALS['babInstallPath'].'utilit/topincl.php';
	include_once $GLOBALS['babInstallPath'].'utilit/artincl.php';
	foreach($drafts as $arr)
		{
			
		if( $arr['id_article'] != 0 )
			{
			$res = $babDB->db_query("select at.id_topic, at.id_author, tt.allow_update, tt.allow_manupdate from ".BAB_ARTICLES_TBL." at left join ".BAB_TOPICS_TBL." tt on at.id_topic=tt.id  where at.id='".$babDB->db_escape_string($arr['id_article'])."'");
			if( $res && $babDB->db_num_rows($res) == 1 )
				{
				$rr = $babDB->db_fetch_array($res);
				if( ( $rr['allow_update'] != '0' && $rr['id_author'] == $arr['id_author']) || bab_isAccessValidByUser(BAB_TOPICSMOD_GROUPS_TBL, $rr['id_topic'], $arr['id_author']) || ( $rr['allow_manupdate'] != '0' && bab_isAccessValidByUser(BAB_TOPICSMAN_GROUPS_TBL, $rr['id_topic'], $arr['id_author'])))
					{
					bab_submitArticleDraft($arr['id']);	
					continue;
					}
				}
			}

		if( $arr['id_topic'] != 0 && bab_isAccessValidByUser(BAB_TOPICSSUB_GROUPS_TBL, $arr['id_topic'], $arr['id_author']))
			{
			bab_submitArticleDraft($arr['id']);	
			}
		}
	}

	

	$res = $babDB->db_query("select id from ".BAB_ARTICLES_TBL." where date_archiving <= now() and date_archiving !='0000-00-00 00:00:00' and archive='N'");
	while( $arr  = $babDB->db_fetch_array($res))
		{
		$babDB->db_query("update ".BAB_ARTICLES_TBL." set archive='Y' where id = '".$babDB->db_escape_string($arr['id'])."'");
		}
		
		
	
}

class babLanguageFilter
	{
		var $langFilterNames;
		var $activeLanguageFilter;
		var $activeLanguageValues;

		function babLanguageFilter()
			{
				$this->setFilter(0);
			} //function LanguageFilter
			
		function translateTexts()
		{
			$this->langFilterNames = array(bab_translate("No filter")
					,bab_translate("Filter language")
					,bab_translate("Filter language and country")
					//,bab_translate("Filter translated")
					);
		}

		function setFilter($filterInt)
			{
				$this->activeLanguageValues = array();
				switch($filterInt)
				{
					case 2:
						$this->activeLanguageValues[] = '\'*\'';
						$this->activeLanguageValues[] = '\'\'';
						break;
					case 1:
						$this->activeLanguageValues[] = '\''.mb_substr($GLOBALS['babLanguage'], 0, 2).'\'';
						$this->activeLanguageValues[] = '\'*\'';
						$this->activeLanguageValues[] = '\'\'';
						break;
					case 0:
					default:
						break;
				}
				$this->activeLanguageFilter = $filterInt;
			}

		function getFilterAsInt()
			{
				return $this->activeLanguageFilter;
			}

		function getFilterAsStr()
			{
				return $this->langFilterNames[$this->activeLanguageFilter];
			}

		function convertFilterToStr($filterInt)
			{
				return $this->langFilterNames[$filterInt];
			}
		
		function convertFilterToInt($filterStr)
			{
				$i = 0;
				while ($i < count($this->langFilterNames))
					{
						if ($this->langFilterNames[$i] == $filterStr) return $i;
						$i++;
					}
				return 0;
			}

		function countFilters()
			{
				return count($this->langFilterNames);
			}

		function getFilterStr($i)
			{
				return $this->langFilterNames[$i];
			} 

		function isLangFile($fileName)
			{
				$res = mb_substr($fileName, 0, 5);
				if ($res != 'lang-')
				{
					return false;
				}
				
				$iOffset = mb_strpos($fileName, '.');
				if(false === $iOffset)
				{
					return false;
				}
				
				$iOffset = mb_strpos($fileName, '.');
				if(false === $iOffset)
				{
					return false;
				}

				$sFileExtention = mb_strtolower(mb_substr($fileName, $iOffset));
				
				if($sFileExtention != '.xml')
				{
					return false;
				}
				
				return true;
			}

		function getLangCode($file)
			{
				$langCode = mb_substr($file,5);
				return mb_substr($langCode,0,mb_strlen($langCode)-4);
			}

		function readLangFiles()
			{
				global $babInstallPath;
				$tmpLangFiles = array();
				$i = 0;
				if (file_exists($babInstallPath.'lang'))
					{
						$folder = opendir($babInstallPath.'lang');
						while (false!==($file = readdir($folder)))
							{
								if ($this->isLangFile($file))
									{
										$tmpLangFiles[$i] = $this->getLangCode($file);
										$i++;
									}
							}
				closedir($folder);
					}
				if (file_exists('lang'))
					{
						$folder = opendir('lang');
						while (false!==($file = readdir($folder)))
							{
								if ($this->isLangFile($file))
									{
										$tmpLangFiles[$i] = $this->getLangCode($file);
										$i++;
									}
							}
						closedir($folder);
}
				$tmpLangFiles[] = '*';
				bab_sort::sort($tmpLangFiles);
				$this->langFiles = array();
				$tmpLang = '';
				$i = 0;
				$tmpLangFiles[-1]='';
				while ($i < count($tmpLangFiles) - 1)
				{
					if ($tmpLangFiles[$i] != $tmpLangFiles[$i-1])
					{
						$this->langFiles[] = $tmpLangFiles[$i];
					}
					$i++;
				}
			} // function readLangFiles() // 2003-09-08
		
		function getLangFiles()
			{
				static $callNbr = 0;
				if($callNbr == 0)
					{
						$this->readLangFiles();
						$callNbr++;
					}
				return $this->langFiles;
			}  // getLangFiles

		function getLangValues()
			{
				return $this->activeLanguageValues;
			}  // getLangFiles

	} //class LanguageFilter




function bab_initMbString() {
	mb_internal_encoding(bab_charset::getIso());
	mb_http_output(bab_charset::getIso());
}





bab_initMbString();
$babBody = new babBody();
$BAB_HASH_VAR='aqhjlongsmp';
$babLangFilter = new babLanguageFilter();

?>
