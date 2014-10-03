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



include_once $GLOBALS['babInstallPath'].'utilit/addonapi.php';
include_once $GLOBALS['babInstallPath'].'utilit/template.php';
include_once $GLOBALS['babInstallPath'].'utilit/userincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/mailincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/sitemap.php';
include_once $GLOBALS['babInstallPath'].'utilit/eventincl.php';
include_once $GLOBALS['babInstallPath'].'utilit/groupsincl.php';



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

	
/**
 * Get stylesheet url selected in options
 * @return string
 */
function bab_getCssUrl()
	{
	global $babInstallPath, $babSkinPath, $babSkin;
	
	$skin = new bab_Skin($babSkin);
	
	$filepath = $skin->getThemePath().'styles/'. $GLOBALS['babStyle'];
	if( !file_exists( $filepath ) )
		{
		$filepath = $babSkinPath.'styles/'. $GLOBALS['babStyle'];
		if( !file_exists( $filepath ) )
			{
			$filepath = $babInstallPath.'skins/ovidentia/styles/ovidentia.css';
			}
		}
	return bab_getStaticUrl().$filepath;
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


/**
 * Test if time format use AM/PM
 * @return bool
 */
function bab_isAmPm()
{
	require_once dirname(__FILE__).'/settings.class.php';
	
	$settings = bab_getInstance('bab_Settings');
	/*@var $settings bab_Settings */
	$arr = $settings->getSiteSettings();
	
	if( $arr['time_format'] == '') {
		return false;
	}

	$pos = mb_strpos(mb_strtolower($arr['time_format']), 't');
	if( $pos === false)
	{
		return false;
	}
	
	return true;
}



/**
 * 
 * @param string $format
 * @return string
 */
function bab_getTimeFormat($format)
{
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




/**
 * Page head
 *
 */
class babHead
{
	/**
	 * Page title in raw text
	 * used for referencing
	 * @see babBody::setTitle()
	 * @var string
	 */
	private $page_title = null;
	
	
	/**
	 * Contain page description used for referencing
	 * @see babBody::setDescription()
	 * @var string
	 */
	private $page_description = null;
	
	/**
	 * Contain page keywords used for referencing
	 * @see babBody::setKeywords()
	 * @var string
	 */
	private $page_keywords = null;
	
	/**
	 * 
	 * @var string
	 */
	private $canonicalUrl = null;
	
	/**
	 * 
	 * @var string
	 */
	private $imageUrl = null;
	
	
	/**
	 * Get page title
	 * @return string
	 */
	public function getTitle()
	{
		if (null === $this->page_title)
		{
			return $GLOBALS['babBody']->raw_title;
		}
		
		return $this->page_title;
	}
	
	/**
	 * Set page title with a text string (no html)
	 * @param	string $title
	 */
	public function setTitle($title) {
		$this->page_title = $title;
	}
	
	/**
	 * 
	 * @param string $description
	 */
	public function setDescription($description)
	{
		$this->page_description = $description;
	}
	
	
	/**
	 * 
	 * @param string $keywords
	 */
	public function setKeywords($keywords)
	{
		$this->page_keywords = $keywords;
	}
	
	
	/**
	 *
	 * @param string $canonicalUrl
	 */
	public function setCanonicalUrl($canonicalUrl)
	{
		$this->canonicalUrl = $canonicalUrl;
	}
	
	
	/**
	 * An image representation for the the page (at least a 200x200 px is recomended)
	 * this can be used in a <meta property="og:image"> tag from the opengraph API or <link rel="image_src">
	 * @since 7.9.0
	 * @param string $imageUrl
	 * 
	 * 
	 */
	public function setImageUrl($imageUrl)
	{
		$this->imageUrl = $imageUrl;
	}
	
	
	
	/**
	 *
	 * @return string
	 */
	public function getDescription()
	{
		return $this->page_description;
	}
	
	/**
	 * @return string
	 */
	public function getKeywords()
	{
		return $this->page_keywords;
	}
	
	/**
	 * @return string
	 */
	public function getCanonicalUrl()
	{
		return $this->canonicalUrl;
	}
	
	/**
	 * @since 7.9.0
	 * @return string
	 */
	public function getImageUrl()
	{
		return $this->imageUrl;
	}
}



/**
 * page body
 *
 */
class babBody
{
	/**
	 * 
	 * @var array
	 */
	public $sections = array();
	
	/**
	 * 
	 * @var babMenu
	 */
	public $menu;
	
	
	/**
	 * Messages to display on page
	 * @see babBody::addMessage();
	 * @var unknown_type
	 */
	public $messages = array();
	
	/**
	 * Error message as html
	 * @access public
	 */
	public $msgerror;
	
	/**
	 * List of errors as text
	 * @see babBody::addError()
	 * @access public
	 */
	public $errors = array();
	
	/**
	 * 
	 * @var string
	 */
	public $content;
	
	
	/**
	 * Page title text
	 * @var string
	 */
	public $raw_title;
	
	/**
	 * Page title
	 * HTML
	 */
	public $title;
	
	
	/**
	 * 
	 * @var string
	 */
	public $message;
	
	/**
	 * 
	 * @var string
	 */
	public $script;
	
	
	/**
	 * @deprecated use bab_Settings instead
	 * @var array
	 */
	public $babsite;
	
	
	/**
	 * List of stylesheets
	 * @var array
	 */
	public $styleSheet = array();
	
	
	public function __construct()
	{
		global $babDB;
		$this->menu = new babMenu();
		$this->message = '';
		$this->script = '';
		$this->title = '';
		$this->msgerror = '';
		$this->content = '';
		$this->saarray = array();
		$this->babaddons = array();
	
		require_once dirname(__FILE__).'/session.class.php';
		$session = bab_getInstance('bab_Session');
		if (isset($session->bab_page_messages))
		{
			foreach($session->bab_page_messages as $message)
			{
				$this->addMessage($message);
			}
			unset($session->bab_page_messages);
		}
		
		
		if (isset($session->bab_page_errors))
		{
			foreach($session->bab_page_errors as $error)
			{
				$this->addError($error);
			}
			unset($session->bab_page_errors);
		}
	}
	
	
	/**
	 * 
	 */
	public function __isset($propertyName) {
		switch($propertyName) {
			case 'isSuperAdmin':
			case 'lastlog':
			case 'currentAdmGroup':
			case 'currentDGGroup':
				return true;
				
			default:
				return false;
		}
	}
	
	public function __get($propertyName) {
		switch($propertyName) {
			case 'isSuperAdmin':
				trigger_error('babBody->isSuperAdmin is deprecated, please use bab_isUserAdministrator() instead');
				return bab_isUserAdministrator();
				
			case 'lastlog':
				trigger_error('babBody->lastlog is deprecated, please use bab_userInfos::getUserSettings() instead');
				if (bab_isUserLogged())
				{
					require_once dirname(__FILE__).'/userinfosincl.php';
					$usersettings = bab_userInfos::getUserSettings();
					return $usersettings['lastlog'];
				}
				return '';
				
			case 'currentAdmGroup':
				trigger_error('babBody->currentAdmGroup is deprecated, please use bab_getCurrentAdmGroup() instead');
				return bab_getCurrentAdmGroup();
				
			case 'currentDGGroup':
				trigger_error('babBody->currentDGGroup is deprecated, please use bab_getCurrentDGGroup() instead');
				return bab_getCurrentDGGroup();
		}
	}
	
	
	
	public function resetContent()
	{
		$this->content = '';
	}
	
	
	public function babecho($txt)
	{
		$this->content .= $txt;
	}
	
	
	/**
	 * Set page title with a text string (no html)
	 * @param	string $title
	 */
	public function setTitle($title) {
		$this->raw_title = $title;
		$this->title = bab_toHtml($title);
	}
	
	
	/**
	 * Add text message to page
	 * @param string $message
	 */
	public function addMessage($message)
	{
		$this->messages[] = $message;
		return $this;
	}
	
	/**
	 * Add message to display in next page
	 * @param unknown_type $message
	 */
	public function addNextPageMessage($message)
	{
		$session = bab_getInstance('bab_Session');
		if (!isset($session->bab_page_messages))
		{
			$session->bab_page_messages = array();
		}
		$messages = $session->bab_page_messages;
		$messages[] = $message;
		$session->bab_page_messages = $messages;
		
		return $this;
	}
	
	
	/**
	 * Add error to display in next page
	 * @param unknown_type $message
	 */
	public function addNextPageError($message)
	{
		$session = bab_getInstance('bab_Session');
		if (!isset($session->bab_page_errors))
		{
			$session->bab_page_errors = array();
		}
		$messages = $session->bab_page_errors;
		$messages[] = $message;
		$session->bab_page_errors = $messages;
	
		return $this;
	}
	
	
	/**
	 * Add error message
	 * @param	string $title
	 */
	public function addError($error) {
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
	public function babpopup($txt) {
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
	
	
	public function loadSections()
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
									if( isset($BAB_SESS_LOGGED) && $BAB_SESS_LOGGED && (bab_isUserAdministrator() || bab_getCurrentAdmGroup() != 0))
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
	
					if( $arr2['access'] && is_file($GLOBALS['babInstallPath'].'addons/'.$arr2['title'].'/init.php'))
						{
						bab_setAddonGlobals($arr2['id']);
	
						require_once( $GLOBALS['babInstallPath'].'addons/'.$arr2['title'].'/init.php' );
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
				$langFilterValues = bab_getInstance('babLanguageFilter')->getLangValues();
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
	
	public function addSection($sec)
	{
		array_push($this->sections, $sec);
	}
	
	public function showSection($title)
	{
		for( $i = 0; $i < count($this->sections); $i++)
			{
			if( !strcmp($this->sections[$i]->getTitle(), $title))
				{
				$this->sections[$i]->show();
				}
			}
	}
	
	public function hideSection($title)
	{
		for( $i = 0; $i < count($this->sections); $i++)
			{
			if( !strcmp($this->sections[$i]->getTitle(), $title))
				{
				$this->sections[$i]->hide();
				}
			}
	}
	
	public function addItemMenu($title, $txt, $url, $enabled=true)
	{
		$this->menu->addItem($title, $txt, $url, $enabled);
	}
	
	public function addItemMenuAttributes($title, $attr)
	{
		$this->menu->addItemAttributes($title, $attr);
	}
	
	public function setCurrentItemMenu($title, $enabled=false)
	{
		$this->menu->setCurrent($title, $enabled);
	}
	
	/**
	 * Add a stylesheet to the page
	 * @param string $filename
	 * @return void
	 */
	public function addStyleSheet($filename)
	{
		if (!in_array($filename, $this->styleSheet))
		{
			// $filename can be relative to styles folders
			// or a full path relative to main folder
	
			if ($GLOBALS['babInstallPath'] === mb_substr($filename, 0, mb_strlen($GLOBALS['babInstallPath'])) || 'vendor' === mb_substr($filename, 0, mb_strlen('vendor')))
			{
				$this->styleSheet[] = '../../'.$filename;
			} else {
				$this->styleSheet[] = $filename;
			}
		}
	}
	
	
	/**
	 * Add a javscript file url to head
	 * 
	 * @param string $file	static javascript file URL
	 * @param bool $defer	Defer script loading
	 */
	public function addJavascriptFile($file, $defer = false)
	{
		global $babOvidentiaJs;
		static $jfiles = array();
		
	
		if( !array_key_exists($file, $jfiles))
		{
			$jfiles[$file] = 1;
			
			$defer_attribute = '';
			if ($defer)
			{
				$defer_attribute = ' defer="defer" ';
			}
			
			if ($GLOBALS['babInstallPath'] === mb_substr($file, 0, mb_strlen($GLOBALS['babInstallPath'])))
			{
				$file = bab_getStaticUrl().$file;
			}
			$babOvidentiaJs .= '"></script>'."\n\t".'<script type="text/javascript" '.$defer_attribute.' src="'.$file;
		}
	}
	
	/**
	 * Adds some javascript code to the current page.
	 *
	 * @param string $code
	 */
	public function addJavascript($code)
	{
		$this->script .= "\n" . $code;
	}
	
	
	public function getnextstylesheet()
	{
		return list(,$this->file) = each($this->styleSheet);
	}
	
	
	public function printout()
	{
	    if (count($this->styleSheet) > 0 && false !== current($this->styleSheet))
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

	/**
	 * @deprecated Use bab_getArticleCategories()
	 */
	public function get_topcats() {
		require_once dirname(__FILE__).'/artapi.php';
		trigger_error('deprecated : '.__FUNCTION__);
		return bab_getArticleCategories();
	}

	/**
	 * @deprecated Use bab_getReadableArticleCategories()
	 */
	public function get_topcatview() {
		require_once dirname(__FILE__).'/artapi.php';
		trigger_error('deprecated : '.__FUNCTION__);
		return bab_getReadableArticleCategories();
	}



}  /* end of class babBody */




/**
 * Collection of calendars
 *
 * @return bab_icalendars
 */
function bab_getICalendars($id_user = '') {

	include_once $GLOBALS['babInstallPath'].'utilit/calincl.php';
	static $calendars = null;

	if (!isset($calendars[$id_user])) {
		
		$calendars[$id_user] = new bab_icalendars($id_user);
	}

	return $calendars[$id_user];
}





/**
 * Update users settings
 */
function bab_updateUserSettings()
{
	global $babDB;
	require_once dirname(__FILE__).'/delegincl.php';
	

	if(bab_isUserLogged())
	{
		require_once dirname(__FILE__).'/settings.class.php';
		require_once dirname(__FILE__).'/userinfosincl.php';
		
		$settings = bab_getInstance('bab_Settings');
		/*@var $settings bab_Settings */
		$site = $settings->getSiteSettings();
		
		
		$id_user = bab_getUserId();
		
		$babDB->db_query("update ".BAB_USERS_LOG_TBL." set id_user='".$babDB->db_escape_string($id_user)."' where sessid='".$babDB->db_escape_string(session_id())."'");
		
		
		if( $arr = bab_userInfos::getUserSettings() )
			{
			if('Y' === $site['change_lang']) {

				if( $arr['lang'] != '')
					{
					$GLOBALS['babLanguage'] = $arr['lang'];
					}

				if($arr['langfilter'] != '') {
					bab_getInstance('babLanguageFilter')->setFilter($arr['langfilter']);
				}

			}



			if('Y' === $site['change_skin']) {

				if ($arr['skin'] !== $GLOBALS['babSkin'] && !empty($arr['skin']))
					{
					$GLOBALS['babSkin'] = $arr['skin'];
				}

				if(!empty($arr['style']) && is_file('skins/'.$GLOBALS['babSkin'].'/styles/'.$arr['style']))
					{
					$GLOBALS['babStyle'] = $arr['style'];
				}
			}


			if('Y' === $site['change_date']) {

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

			if (isset($_GET['debug'])) {
				if (0 == $_GET['debug']) {
					setcookie('bab_debug', '', time() - 31536000); // remove
				} else {
					setcookie('bab_debug', $_GET['debug'], time() + 31536000); // 1 year
				}
			}
			

		}

		if('Y' === $site['change_unavailability'])
		{
			// les retirer le cache de l'approbation si les parametre d'indisponibilite sont actif
			if (isset($_SESSION['bab_waitingApprobations'][$id_user]))
			{
				unset($_SESSION['bab_waitingApprobations'][$id_user]);
			}
		}
	}





	// verify skin validity

	include_once dirname(__FILE__).'/skinincl.php';
	$objSkin = new bab_skin($GLOBALS['babSkin']);
	if (!$objSkin->isAccessValid()) {
		$GLOBALS['babSkin'] = bab_skin::getDefaultSkin()->getName();
	}

	
	if (bab_isUserLogged() || !defined('BAB_DISABLE_ANONYMOUS_LOG') || 0 == BAB_DISABLE_ANONYMOUS_LOG)
	{
		bab_UsersLog::update();
	}
}





/**
 * Change the bab_users_log table
 * mandatory for logged in users
 * if not used for logged out users, article draft will not work for anonymous
 *
 */
class bab_UsersLog
{
	
	/**
	 * Get row in user log for current user
	 * @return array | false
	 */
	public static function getCurrentRow()
	{
		global $babDB;
		
		static $row = null;
		
		if (!isset($row))
		{
			$query = "select id, id_dg, id_user, cpw, sessid, remote_addr, grp_change, schi_change from ".BAB_USERS_LOG_TBL." where sessid='".$babDB->db_escape_string(session_id())."'";
			
			if (bab_isUserLogged())
			{
				$query .= ' OR (id_user='.$babDB->quote(bab_getUserId()).' AND sessid<>'.$babDB->quote(session_id()).') ORDER BY dateact DESC';
			}
			
			$res = $babDB->db_query($query);
			if( $res && $babDB->db_num_rows($res) > 0)
			{
				$row = $babDB->db_fetch_assoc($res);
			} else {
				$row = false;
			}
		}
		
		return $row;
	}
	
	
	
	/**
	 * Chech remote addr, grp_change, schi_change
	 * cleanup session cache if necessary
	 */
	public static function check()
	{
		if (isset($_SERVER['REMOTE_ADDR'])) {
			$REMOTE_ADDR = $_SERVER['REMOTE_ADDR'];
		} else {
			$REMOTE_ADDR = '0.0.0.0';
		}
		
		if ( session_id() && (bab_rp('tg') !== 'version' || bab_rp('idx') !== 'upgrade'))
		{
		
			$arr = bab_UsersLog::getCurrentRow();
			if($arr)
			{
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
	}
	
	
	
	
	/**
	 * Update an insert row
	 * check for multiple connexion with same account if configured in site : auth_multi_session
	 */
	public static function update()
	{
		global $babDB, $babBody, $BAB_SESS_USERID;
		
		$HTTP_X_FORWARDED_FOR = isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['HTTP_X_FORWARDED_FOR'] : '0.0.0.0';
		$REMOTE_ADDR = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '0.0.0.0';
		
		$arr = self::getCurrentRow();
		if($arr)
		{
			if ($arr['sessid'] == session_id())
			{
				bab_setUserPasswordVariable($arr['id'], $arr['cpw'], $arr['id_user']);
				
				require_once dirname(__FILE__).'/delegincl.php';
				$delegation = bab_getInstance('bab_currentDelegation');
				if($arr['id_dg'] != '0')
				{
					$delegation->set($arr['id_dg']);
				}
		
				$babDB->db_query("update ".BAB_USERS_LOG_TBL." set
						dateact=now(),
						remote_addr=".$babDB->quote($REMOTE_ADDR).",
						forwarded_for=".$babDB->quote($HTTP_X_FORWARDED_FOR).",
						id_dg='".$babDB->db_escape_string(bab_getCurrentAdmGroup())."',
						grp_change=NULL,
						schi_change=NULL,
						tg='".$babDB->db_escape_string(bab_rp('tg'))."'
						where
						id = '".$babDB->db_escape_string($arr['id'])."'
						");
		
			} elseif (0 === (int) $babBody->babsite['auth_multi_session']) {
				// another session exists for the same user ID (first is the newest)
				// we want to stay with the newest session so the current session must be disconnected
		
				require_once dirname(__FILE__).'/loginIncl.php';
				bab_logout(false);
				$babBody->addError(bab_translate('You will be disconnected because another user has logged in with your account'));
			}
		}
		else
		{
			if( !empty($BAB_SESS_USERID))
			{
				$userid = $BAB_SESS_USERID;
			}
			else
			{
				$userid = 0;
			}
		
			$babDB->db_query("insert into ".BAB_USERS_LOG_TBL." (id_user, sessid, dateact, remote_addr, forwarded_for, id_dg, grp_change, schi_change, tg) 
				values ('".$babDB->db_escape_string($userid)."', '".session_id()."', now(), '".$babDB->db_escape_string($REMOTE_ADDR)."', '".$babDB->db_escape_string($HTTP_X_FORWARDED_FOR)."', '".$babDB->db_escape_string(bab_getCurrentAdmGroup())."', NULL, NULL, '".$babDB->db_escape_string(bab_rp('tg'))."')");
		}
		
	}
	
	
	
	/**
	 * Cleanup expired sessions from bab_users_log
	 * cleanup associated article draft
	 */
	public static function cleanup()
	{
		global $babDB;
		
		$maxlife = (int) get_cfg_var('session.gc_maxlifetime');
		if (0 === $maxlife)
		{
			$maxlife = 1440;
		}
		
		$res = $babDB->db_query("select id from ".BAB_USERS_LOG_TBL." WHERE (UNIX_TIMESTAMP(dateact) + ".$babDB->quote($maxlife).") < UNIX_TIMESTAMP()");
		while( $row  = $babDB->db_fetch_array($res))
		{
			$res2 = $babDB->db_query("select id from ".BAB_ART_DRAFTS_TBL." where id_author='0' and id_anonymous='".$babDB->db_escape_string($row['id'])."'");
			while( $arr  = $babDB->db_fetch_array($res2))
			{
				bab_deleteArticleDraft($arr['id']);
			}
			$babDB->db_query("delete from ".BAB_USERS_LOG_TBL." where id='".$babDB->db_escape_string($row['id'])."'");
		}
		
	}
}


/**
 * Get the ID of the current admin delegation.
 * 
 * This function remplaces the $babBody->currentAdmGroup variable.
 * 
 * @return int
 */
function bab_getCurrentAdmGroup()
{
	require_once dirname(__FILE__).'/delegincl.php';
	$delegation = bab_getInstance('bab_currentDelegation');
	/*@var $delegation bab_currentDelegation */
	return $delegation->getCurrentAdmGroup();
}


/**
 * Returns an array with all information about the delegation.
 * 
 * This method replace the $babBody->currentDGGroup variable
 * @return array
 */
function bab_getCurrentDGGroup()
{
	require_once dirname(__FILE__).'/delegincl.php';
	$delegation = bab_getInstance('bab_currentDelegation');
	/*@var $delegation bab_currentDelegation */
	return $delegation->getCurrentDGGroup();
}


/**
 * Test if the functionality is delegated in the current delegation
 * @param string $functionname
 * @return bool
 */
function bab_isDelegated($functionname)
{
	$arr = bab_getCurrentDGGroup();
	
	if (!isset($arr[$functionname]))
	{
		return false;
	}
	
	return ('Y' === $arr[$functionname]);
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
	global $BAB_SESS_USERID;
	if( extension_loaded('mcrypt') && !empty($cpw) && isset($GLOBALS['babEncryptionKey']) && !isset($_REQUEST['babEncryptionKey']) && !empty($GLOBALS['babEncryptionKey']) && !empty($BAB_SESS_USERID) && $BAB_SESS_USERID == $id_user)
	{
	$GLOBALS['babUserPassword'] = bab_decrypt($cpw, md5($id.session_id().$BAB_SESS_USERID.$GLOBALS['babEncryptionKey']));
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
 * Get ovidentia version from ini file
 * @return string
 */
function bab_getIniVersion() {
	
	$arr = parse_ini_file($GLOBALS['babInstallPath'].'version.inc');
	
	return $arr['version'];
}



/**
 * Get skin path
 * @return string
 */
function bab_getSkinPath() {
	global $babInstallPath;
	return bab_getStaticUrl().$babInstallPath."skins/ovidentia/";
}




/**
 * 
 * @param string	$relativeFilepath	A path relative to the skin path or to the kernel's skin path.
 * @param string	$filename
 */
function bab_getSkinnableFile($relativeFilepath)
{
	$filepath = 'skins/' . $GLOBALS['babSkin'] . '/' . $relativeFilepath;
	if (!file_exists($filepath)) {
		$filepath = $GLOBALS['babSkinPath'] . '/' . $relativeFilepath;
	}
	if (!file_exists($filepath)) {
		return null;
	}

	return $filepath;
}




/**
 * Returns the path to a template file in the kernel's template path or in
 * the current skin's template path if it was overwritten there.
 * 
 * @param string	$filename
 * @return string	The path to the template file.
 */
function bab_getSkinnableTemplate($filename)
{
	return bab_getSkinnableFile('templates/' . $filename);
}




/**
 * Returns the path to an ovml file in the kernel's ovml path or in
 * the current skin's ovml path if it was overwritten there.
 * 
 * @param string	$filename
 * @return string	The path to the ovml file.
 */
function bab_getSkinnableOvml($filename)
{
	return bab_getSkinnableFile('ovml/' . $filename);
}






/**
 * Get the site settings and set globals variables : $babSkin, $babUploadPath...
 * This function is called from index.php
 */
function bab_updateSiteSettings()
{
	global $babDB;

	$babBody = bab_getInstance('babBody');
	$BAB_HASH_VAR = bab_getHashVar();
	
	require_once dirname(__FILE__).'/settings.class.php';
	
	$settings = bab_getInstance('bab_Settings');
	/*@var $settings bab_Settings */
	
	try {
		$arr = $settings->getSiteSettings();
	} catch (ErrorException $e)
	{
		$babBody->addError($e->getMessage());
		return;
	}

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
		$GLOBALS['babLanguage'] = 'fr'; }




	if( $arr['adminemail'] != '')
		{
		$GLOBALS['babAdminEmail'] = $arr['adminemail'];
		}
	else {
		$GLOBALS['babAdminEmail'] = 'admin@your-domain.com'; }
	if( $arr['langfilter'] != '')
		{
		bab_getInstance('babLanguageFilter')->setFilter($arr['langfilter']);
		}
	else {
		bab_getInstance('babLanguageFilter')->setFilter(0); }
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

	$GLOBALS['babQuotaFM'] = $arr['quota_total'];
	$GLOBALS['babQuotaFolder'] = $arr['quota_folder'];
	
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
		$babBody->nameorder = explode(' ',$arr['name_order']);
		}
	else {
		$babBody->nameorder = Array('F','L');
	}
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

	
	bab_UsersLog::cleanup();



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

			$this->langFilterNames = array(bab_translate("No filter")
				,bab_translate("Filter language")
				,bab_translate("Filter language and country")
				//,bab_translate("Filter translated")
				);

		} //function LanguageFilter


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


