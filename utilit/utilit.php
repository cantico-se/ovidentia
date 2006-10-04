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
include_once $babInstallPath."utilit/addonapi.php";
include_once $babInstallPath."utilit/template.php";
include_once $babInstallPath."utilit/userincl.php";
include_once $babInstallPath."utilit/mailincl.php";

$babLdapEncodingTypes = array(0 => '', BAB_LDAP_UTF8_ISO_8859_1 => "UTF8 -> ISO_8859_1", BAB_LDAP_T61_ISO_8859_1 => "T61 -> ISO_8859_1");

function bab_ldapDecode($str, $type)
{
	
	switch($type)
	{
		case BAB_LDAP_UTF8_ISO_8859_1:
			return utf8_decode($str);
			break;

		case BAB_LDAP_T61_ISO_8859_1:
			return ldap_t61_to_8859($str);
			break;

		default:
			return $str;
			break;
	}

}

function bab_ldapEncode($str, $type)
{
	switch($type)
	{
		case BAB_LDAP_UTF8_ISO_8859_1:
			return utf8_encode($str);
			break;

		case BAB_LDAP_T61_ISO_8859_1:
			return ldap_8859_to_t61($str);
			break;

		default:
			return $str;
			break;
	}
}


function bab_encrypt($txt,$key)
	{
	$td = mcrypt_module_open(MCRYPT_TRIPLEDES, '', MCRYPT_MODE_CFB, '');
	if( $td === false)
		return '';
	$key = substr($key, 0, mcrypt_enc_get_key_size($td));
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
	$key = substr($key, 0, mcrypt_enc_get_key_size($td));
	$iv_size = mcrypt_enc_get_iv_size($td);
	$iv = substr($txt,0,$iv_size);
	$txt = substr($txt,$iv_size);
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

	$txt = bab_translate("Anonymous");

	if( !empty($id))
		{
		if( !isset($bab_authors[$id]))
			{	
			$res = $babDB->db_query("select givenname, sn, mn from ".BAB_DBDIR_ENTRIES_TBL." where id_directory='0' and id_user='".$id."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$bab_authors[$id] = $babDB->db_fetch_array($res);
				}
			}

		if(preg_match_all("/%(.)/", $format, $m))
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
				$txt = preg_replace("/".preg_quote($m[0][$i])."/", $val, $txt);
				}
			}
		}

	return $txt;
}

function bab_stripDomainName ($txt)
	{
	return eregi_replace("((href|src)=['\"]?)".$GLOBALS['babUrl'], "\\1", $txt);
	}

function bab_isEmailValid ($email)
	{
	if( empty($email) || ereg(' ', $email))
		return false;
	else
		return true;
	}

function bab_getCssUrl()
	{
	global $babInstallPath, $babSkinPath;
	$filepath = "skins/".$GLOBALS['babSkin']."/styles/". $GLOBALS['babStyle'];
	if( !file_exists( $filepath ) )
		{
		$filepath = $babSkinPath."styles/". $GLOBALS['babStyle'];
		if( !file_exists( $filepath ) )
			{
			$filepath = $babInstallPath."skins/ovidentia/styles/ovidentia.css";
			}
		}
	return $filepath;
	}

function bab_getDateFormat($format)
{
	$format = preg_replace("/(?<!M)M(?!M)/i", "$1%n$2", $format);
	$format = preg_replace("/(?<!M)MM(?!M)/i", "$1%n$2", $format);
	$format = preg_replace("/(?<!M)MMM(?!M)/i", "$1%m$2", $format);
	$format = preg_replace("/(?<!M)M{4,}(?!M)/i", "$1%M$3", $format);

	$format = preg_replace("/(?<!D)D(?!D)/i", "$1%j$2", $format);
	$format = preg_replace("/(?<!D)DD(?!D)/i", "$1%j$2", $format);
	$format = preg_replace("/(?<!D)DDD(?!D)/i", "$1%d$2", $format);
	$format = preg_replace("/(?<!D)D{4,}(?!D)/i", "$1%D$2", $format);
	
	$format = preg_replace("/(?<!Y)Y(?!Y)/i", "$1%y$2", $format);
	$format = preg_replace("/(?<!Y)YY(?!Y)/i", "$1%y$2", $format);
	$format = preg_replace("/(?<!Y)YYY(?!Y)/i", "$1%Y$2", $format);
	$format = preg_replace("/(?<!Y)Y{4,}(?!Y)/i", "$1%Y$2", $format);

	return $format;
}

function bab_getTimeFormat($format)
{
	global $babBody;
	$pos = strpos(strtolower($format), "t");
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

	$format = preg_replace("/(?<!m)m{1,}(?!m)/i", "$1i$2", $format);
	$format = preg_replace("/(?<!s)s{1,}(?!s)/i", "$1s$2", $format);

	$format = preg_replace("/(?<!t)t{1,}(?!t)/", "$1a$2", $format);
	$format = preg_replace("/(?<!T)T{1,}(?!T)/", "$1A$2", $format);

	return $format;
}

function bab_printOvmlTemplate( $file, $args=array())
	{
	global $babInstallPath, $babSkinPath, $babOvmlPath;
	if( strstr($file, "..") || strtolower(substr($file, 0, 4)) == 'http' )
		return "<!-- ERROR filename: ".$file." -->";

	$filepath = $babOvmlPath.$file;
	if( !file_exists( $filepath ) )
		{
		$filepath = $babSkinPath."ovml/". $file;
		if( !file_exists( $filepath ) )
			{
			$filepath = $babInstallPath."skins/ovidentia/ovml/". $file;
			}
		}

	if( !file_exists( $filepath ) )
		return "<!-- ERROR filename: ".$filepath." -->";

	$GLOBALS['babWebStat']->addOvmlFile($filepath);
	include_once $GLOBALS['babInstallPath']."utilit/omlincl.php";
	$tpl = new babOvTemplate($args);
	return $tpl->printout(implode("", file($filepath)));
	}

function babLoadLanguage($lang, $folder, &$arr)
	{
	if( empty($folder))
		{
		$filename_c = "lang/lang-".$lang.".dat";
		$filename_m = "lang/lang-".$lang.".xml";
		$filename = $GLOBALS['babInstallPath']."lang/lang-".$lang.".xml";
		}
	else
		{
		$filename_c = "lang/addons-".$folder."-lang-".$lang.".dat";
		$filename_m = "lang/addons/".$folder."/lang-".$lang.".xml";
		$filename = $GLOBALS['babInstallPath']."lang/addons/".$folder."/lang-".$lang.".xml";
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
			$file = @fopen($filename, "r");
			if( $file )
				{
				$tmp = fread($file, filesize($filename));
				fclose($file);
				preg_match("/<".$lang.">(.*)<\/".$lang.">/s", $tmp, $m);
				preg_match_all("/<string\s+id=\"([^\"]*)\">(.*?)<\/string>/s", isset($m[1]) ? $m[1] : '' , $tmparr);
				}
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
			$file = @fopen($filename_m, "r");
			if( $file )
				{
				$tmp = fread($file, filesize($filename_m));
				fclose($file);
				preg_match("/<".$lang.">(.*)<\/".$lang.">/s", $tmp, $m);
				preg_match_all("/<string\s+id=\"([^\"]*)\">(.*?)<\/string>/s", $m[1], $arr_replace);
				for( $i = 0; $i < count($arr_replace[0]); $i++ )
					{
					$arr[$arr_replace[1][$i]] = $arr_replace[2][$i];
					}
				}
			}

		if (is_file($filename_c)) {	
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



function bab_callAddonsFunction($func)
{
	$babBody = & $GLOBALS['babBody'];

	$oldBabAddonFolder = isset($GLOBALS['babAddonFolder'])? $GLOBALS['babAddonFolder']: '';
	$oldBabAddonTarget = isset($GLOBALS['babAddonTarget'])? $GLOBALS['babAddonTarget']: '';
	$oldBabAddonUrl =  isset($GLOBALS['babAddonUrl'])? $GLOBALS['babAddonUrl']: '';
	$oldBabAddonPhpPath =  isset($GLOBALS['babAddonPhpPath'])? $GLOBALS['babAddonPhpPath']: '';
	$oldBabAddonHtmlPath =  isset($GLOBALS['babAddonHtmlPath'])? $GLOBALS['babAddonHtmlPath']: '';
	$oldBabAddonUpload =  isset($GLOBALS['babAddonUpload'])? $GLOBALS['babAddonUpload']: '';

	foreach($babBody->babaddons as $key => $row)
		{ 
		if($row['access'])
			{
			$addonpath = $GLOBALS['babAddonsPath'].$row['title'];
			if( is_file($addonpath."/init.php" ))
				{
				$GLOBALS['babAddonFolder'] = $row['title'];
				$GLOBALS['babAddonTarget'] = "addon/".$row['id'];
				$GLOBALS['babAddonUrl'] = $GLOBALS['babUrlScript']."?tg=addon/".$row['id']."/";
				$GLOBALS['babAddonPhpPath'] = $GLOBALS['babInstallPath']."addons/".$row['title']."/";
				$GLOBALS['babAddonHtmlPath'] = "addons/".$row['title']."/";
				$GLOBALS['babAddonUpload'] = $GLOBALS['babUploadPath']."/addons/".$row['title']."/";
				require_once( $addonpath."/init.php" );
				$call = $row['title']."_".$func;
				if( !empty($call)  && function_exists($call) )
					{
					$args = func_get_args();
					$call .= "(";
					for($k=1; $k < sizeof($args); $k++)
						eval ( "\$call .= \"$args[$k],\";");
					$call = substr($call, 0, -1);
					$call .= ")";
					eval ( "\$retval = $call;");
					}
				}
			}
		}

	$GLOBALS['babAddonFolder'] = $oldBabAddonFolder;
	$GLOBALS['babAddonTarget'] = $oldBabAddonTarget;
	$GLOBALS['babAddonUrl'] = $oldBabAddonUrl;
	$GLOBALS['babAddonPhpPath'] = $oldBabAddonPhpPath;
	$GLOBALS['babAddonHtmlPath'] = $oldBabAddonHtmlPath;
	$GLOBALS['babAddonUpload'] = $oldBabAddonUpload;
}


function bab_callAddonsFunctionArray($func, $args)
{
	$babBody = & $GLOBALS['babBody'];

	$oldBabAddonFolder = isset($GLOBALS['babAddonFolder'])? $GLOBALS['babAddonFolder']: '';
	$oldBabAddonTarget = isset($GLOBALS['babAddonTarget'])? $GLOBALS['babAddonTarget']: '';
	$oldBabAddonUrl =  isset($GLOBALS['babAddonUrl'])? $GLOBALS['babAddonUrl']: '';
	$oldBabAddonPhpPath =  isset($GLOBALS['babAddonPhpPath'])? $GLOBALS['babAddonPhpPath']: '';
	$oldBabAddonHtmlPath =  isset($GLOBALS['babAddonHtmlPath'])? $GLOBALS['babAddonHtmlPath']: '';
	$oldBabAddonUpload =  isset($GLOBALS['babAddonUpload'])? $GLOBALS['babAddonUpload']: '';

	foreach($babBody->babaddons as $key => $row)
		{ 
		$addonpath = $GLOBALS['babAddonsPath'].$row['title'];
		if( is_file($addonpath."/init.php" ))
			{
			$GLOBALS['babAddonFolder'] = $row['title'];
			$GLOBALS['babAddonTarget'] = "addon/".$row['id'];
			$GLOBALS['babAddonUrl'] = $GLOBALS['babUrlScript']."?tg=addon/".$row['id']."/";
			$GLOBALS['babAddonPhpPath'] = $GLOBALS['babInstallPath']."addons/".$row['title']."/";
			$GLOBALS['babAddonHtmlPath'] = "addons/".$row['title']."/";
			$GLOBALS['babAddonUpload'] = $GLOBALS['babUploadPath']."/addons/".$row['title']."/";
			require_once( $addonpath."/init.php" );
			$call = $row['title']."_".$func;
			if( function_exists($call) )
				{
				$call($args);
				}
			}
		}

	$GLOBALS['babAddonFolder'] = $oldBabAddonFolder;
	$GLOBALS['babAddonTarget'] = $oldBabAddonTarget;
	$GLOBALS['babAddonUrl'] = $oldBabAddonUrl;
	$GLOBALS['babAddonPhpPath'] = $oldBabAddonPhpPath;
	$GLOBALS['babAddonHtmlPath'] = $oldBabAddonHtmlPath;
	$GLOBALS['babAddonUpload'] = $oldBabAddonUpload;
}




class babMenu
{
var $curItem = "";
var $items = array();

function babMenu()
{
	$GLOBALS['babCurrentMenu'] = "";
}

function addItem($title, $txt, $url, $enabled=true)
{
	$this->items[$title]["text"] = $txt;
	$this->items[$title]["url"] = $url;
	$this->items[$title]["enabled"] = $enabled;
}

function addItemAttributes($title, $attr)
{
	$this->items[$title]["attributes"] = $attr;
}

function setCurrent($title, $enabled=false)
{
	foreach($this->items as $key => $val)
		{
		if( !strcmp($key, $title))
			{
			$this->curItem = $key;
			$this->items[$key]["enabled"] = $enabled;
			if( !$enabled )
				$GLOBALS['babCurrentMenu'] = $this->items[$key]["text"];
			break;
			}
		}
}
}  /* end of class babMenu */

class babBody
{
var $sections = array();
var $menu;
var $msgerror;
var $content;
var $title;
var $message;
var $script;
var $lastlog; /* date of user last log */

var $topview = array();
var $topsub = array();
var $topcom = array();
var $topmod = array();
var $topman = array();
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
	$this->message = "";
	$this->script = "";
	$this->title = "";
	$this->msgerror = "";
	$this->content = "";
	$this->lastlog = "";
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


	if (!isset($GLOBALS['REMOTE_ADDR'])) $GLOBALS['REMOTE_ADDR'] = '0.0.0.0';
	if (!isset($GLOBALS['HTTP_X_FORWARDED_FOR'])) $GLOBALS['HTTP_X_FORWARDED_FOR'] = '0.0.0.0';

	$idx = isset($GLOBALS['idx']) ? $GLOBALS['idx'] : '';
	
	if ( session_id() && ($GLOBALS['tg'] != 'version' || $idx != 'upgrade'))
		{
		$res = $babDB->db_query("select remote_addr, grp_change, schi_change from ".BAB_USERS_LOG_TBL." where sessid='".session_id()."'");
		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$arr = $babDB->db_fetch_array($res);
			if ((!isset($GLOBALS['babCheckIpAddress']) || $GLOBALS['babCheckIpAddress'] == true) && $arr['remote_addr'] != $GLOBALS['REMOTE_ADDR'])
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
	

	if (isset($_SESSION['bab_groupAccess']))
		{
		$this->ovgroups = &$_SESSION['bab_groupAccess']['ovgroups'];
		$this->usergroups = &$_SESSION['bab_groupAccess']['usergroups'];
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
		$_SESSION['bab_groupAccess']['usergroups'] = &$this->usergroups;
		}
}

function getGroupPathName($id_group, $id_parent = BAB_REGISTERED_GROUP)
{
	if (isset($this->groupPathName[$id_parent][$id_group]))
		return $this->groupPathName[$id_parent][$id_group];
	
	include_once $GLOBALS['babInstallPath']."utilit/grptreeincl.php";

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

function resetContent()
{
	$this->content = "";
}

function babecho($txt)
{
	$this->content .= $txt;
}


function babpopup($txt) {
	include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
	$GLOBALS['babBodyPopup'] = new babBodyPopup();
	$GLOBALS['babBodyPopup']->styleSheet = & $GLOBALS['babBody']->styleSheet;
	$GLOBALS['babBodyPopup']->title = & $GLOBALS['babBody']->title;
	$GLOBALS['babBodyPopup']->msgerror = & $GLOBALS['babBody']->msgerror;
	$GLOBALS['babBodyPopup']->babecho($txt);
	printBabBodyPopup();
	die();
}


function loadSections()
{
	
	include $GLOBALS['babInstallPath']."utilit/utilitsections.php";
	
	global $babDB, $babBody, $BAB_SESS_LOGGED, $BAB_SESS_USERID;

	$req = "SELECT ".BAB_SECTIONS_ORDER_TBL.".*, ".BAB_SECTIONS_STATES_TBL.".closed, ".BAB_SECTIONS_STATES_TBL.".hidden, ".BAB_SECTIONS_STATES_TBL.".id_section AS states_id_section FROM ".BAB_SECTIONS_ORDER_TBL." LEFT JOIN ".BAB_SECTIONS_STATES_TBL." ON ".BAB_SECTIONS_STATES_TBL.".id_section=".BAB_SECTIONS_ORDER_TBL.".id_section AND ".BAB_SECTIONS_STATES_TBL.".type=".BAB_SECTIONS_ORDER_TBL.".type AND ".BAB_SECTIONS_STATES_TBL.".id_user='".$BAB_SESS_USERID."' ORDER BY ".BAB_SECTIONS_ORDER_TBL.".ordering ASC";
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
					if( $arr['closed'] == "Y")
						{
							$arrsectioninfo['close'] = 1;
						}
					if( $arr['hidden'] == "N")
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
	if(!empty($arrsectionsbytype[$type]))
		{
			$res2 = $babDB->db_query("select * from ".BAB_PRIVATE_SECTIONS_TBL." where id IN(".implode(',', array_keys($arrsectionsbytype[$type])).")");
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
								if( $arr2['enabled'] == "Y" && ( $arr2['optional'] == 'N' || $arrsectioninfo['bshow'] ))
									{
										$sec = new babMonthA();
										$arrsections[$objectid] = $sec;
									}
								break;
							case 3: // topics
								$sec = new babTopcatSection($arrsectioninfo['close']);
								if( $sec->count > 0 )
									{
										if( $arr2['enabled'] == "Y" && ( $arr2['optional'] == 'N' || $arrsectioninfo['bshow'] ))
											{
												$arrsections[$objectid] = $sec;
											}
									}
								break;
							case 4: // Forums
								$sec = new babForumsSection($arrsectioninfo['close']);
								if( $sec->count > 0 )
									{
										if( $arr2['enabled'] == "Y"  && ( $arr2['optional'] == 'N' || $arrsectioninfo['bshow'] ))
											{
												$arrsections[$objectid] = $sec;
											}
									}
								break;
							case 5: // user's section
								if( $arr2['enabled'] == "Y" )
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
	if(!empty($arrsectionsbytype[$type]))
		{
			$res2 = $babDB->db_query("select id, enabled, optional from ".BAB_TOPICS_CATEGORIES_TBL." where id IN(".implode(',', array_keys($arrsectionsbytype[$type])).")");
			while($arr2 = $babDB->db_fetch_array($res2))
				{
					$sectionid = $arr2['id'];
					$objectid = $arrsectionsbytype[$type][$sectionid];
					if( $arr2['enabled'] == "Y" && ( $arr2['optional'] == 'N' || $arrsectionsinfo[$objectid]['bshow'] ))
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
	if(!empty($arrsectionsbytype[$type]))
		{
		$at = array_keys($arrsectionsbytype[$type]);
		for($i=0; $i < count($at); $i++)
			{
				if( isset($babBody->babaddons[$at[$i]]))
				{
				$arr2 = $babBody->babaddons[$at[$i]];
				$sectionid = $arr2['id'];
				$objectid = $arrsectionsbytype[$type][$sectionid];

				if( $arr2['access'] && is_file($GLOBALS['babAddonsPath'].$arr2['title']."/init.php"))
					{
					$GLOBALS['babAddonFolder'] = $arr2['title'];
					$GLOBALS['babAddonTarget'] = "addon/".$sectionid;
					$GLOBALS['babAddonUrl'] = $GLOBALS['babUrlScript']."?tg=addon/".$sectionid."/";
					$GLOBALS['babAddonPhpPath'] = $GLOBALS['babInstallPath']."addons/".$arr2['title']."/";
					$GLOBALS['babAddonHtmlPath'] = "addons/".$arr2['title']."/";
					$GLOBALS['babAddonUpload'] = $GLOBALS['babUploadPath']."/addons/".$arr2['title']."/";
					require_once( $GLOBALS['babAddonsPath'].$arr2['title']."/init.php" );
					$func = $arr2['title']."_onSectionCreate";
					if(function_exists($func))
						{
						if (!isset($template)) $template = false;
						if($func($stitle, $scontent, $template))
							{
								if( !$arrsectionsinfo[$objectid]['close'])
									{
									$sec = new babSection($stitle, $scontent);
									$sec->setTemplate($template);
									}
								else
									{
									$sec = new babSection($stitle, "");
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

	// user's sections
	$type = 'users';
	if(!empty($arrsectionsbytype[$type]))
		{
			$langFilterValues = $GLOBALS['babLangFilter']->getLangValues();
			$req = "SELECT * FROM ".BAB_SECTIONS_TBL." WHERE id IN(".implode(',', array_keys($arrsectionsbytype[$type])).") and enabled='Y'";
			if( count($langFilterValues) > 0 )
				{
					$req .= " AND SUBSTRING(lang, 1, 2 ) IN (".implode(',', $langFilterValues).")";
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
										if( $arr2['script'] == "Y")
											{
												eval("\$arr2['content'] = \"".$arr2['content']."\";");
											}
										$sec = new babSection($arr2['title'], $arr2['content']);
										}
									else
										{
										$sec = new babSection($arr2['title'], "");
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
							$sec->boxurl = $GLOBALS['babUrlScript']."?tg=options&amp;idx=ob&amp;s=".$sectionid."&amp;w=".$type;
						}
					else
						{
							$sec->boxurl = $GLOBALS['babUrlScript']."?tg=options&amp;idx=cb&amp;s=".$sectionid."&amp;w=".$type;
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

function addStyleSheet($file)
{
	$this->styleSheet[] = $file;
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

function getnextstylesheet()
{
return list(,$this->file) = each($this->styleSheet);
}


function printout()
{
    if (count($this->styleSheet) > 0)
		{
		$this->content = bab_printTemplate($this,"uiutil.html", "styleSheet").$this->content;
		}
	
	if(!empty($this->msgerror))
		{
		$this->message = bab_printTemplate($this,"warning.html", "texterror");
		//return "";
		}
	else if(!empty($this->title))
		{
		$this->message = bab_printTemplate($this,"warning.html", "texttitle");
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

		$topview = bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL);

		$res = $babDB->db_query("select id_cat from ".BAB_TOPICS_TBL." where id in('".implode("','",array_keys($topview))."')");
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
				while( $topcats[$cat]['parent'] != 0 )
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
	if( count($this->topview) > 0 )
		{
		global $babDB;
		$res = $babDB->db_query("select id_topic, restriction from ".BAB_ARTICLES_TBL." where (date_publication = '0000-00-00 00:00:00' OR date_publication <= now()) AND date >= '".$this->lastlog."'");
		while( $row = $babDB->db_fetch_array($res))
			{
			if( isset($this->topview[$row['id_topic']]) && ( $row['restriction'] == '' || bab_articleAccessByRestriction($row['restriction']) ))
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
	if( count($this->topview) > 0 )
		{
		global $babDB;
		$res = $babDB->db_query("select id_topic from ".BAB_COMMENTS_TBL." where confirmed='Y' and date >= '".$this->lastlog."'");
		while( $row = $babDB->db_fetch_array($res))
			{
			if( isset($this->topview[$row['id_topic']]) )
				{
				$newcomments++;
				}
			}
		}
	return $newcomments;
	}

function get_forums() {
		static $forumsview = null;
		if (!is_null($forumsview))
			return $forumsview;

		global $babDB;

		$fv = bab_getUserIdObjects(BAB_FORUMSVIEW_GROUPS_TBL);
		$res = $babDB->db_query("select * from ".BAB_FORUMS_TBL." where active='Y' and id in ('".implode("','",array_keys($fv))."')  order by ordering asc");
		while($arr = $babDB->db_fetch_array($res))
			{
			$forumsview[$arr['id']]['name'] = $arr['name'];
			$forumsview[$arr['id']]['description'] = $arr['description'];
			$forumsview[$arr['id']]['display'] = $arr['display'];
			$forumsview[$arr['id']]['moderation'] = $arr['moderation'];
			$forumsview[$arr['id']]['bdisplayemailaddress'] = $arr['bdisplayemailaddress'];
			$forumsview[$arr['id']]['bdisplayauhtordetails'] = $arr['bdisplayauhtordetails'];
			$forumsview[$arr['id']]['bflatview'] = $arr['bflatview'];
			$forumsview[$arr['id']]['bupdatemoderator'] = $arr['bupdatemoderator'];
			$forumsview[$arr['id']]['bupdateauthor'] = $arr['bupdateauthor'];
			}

		return $forumsview;
	}

function get_newposts() {

	static $newposts = null;
	if (!is_null($newposts))	
		return $newposts;

	global $babDB;

	list($newposts) = $babDB->db_fetch_array($babDB->db_query("select count(p.id) from ".BAB_POSTS_TBL." p, ".BAB_THREADS_TBL." t where p.date >= '".$this->lastlog."' and p.confirmed='Y' and p.id_thread=t.id and t.forum IN('".implode("','",array_keys(bab_getUserIdObjects(BAB_FORUMSVIEW_GROUPS_TBL)))."')"));

	return $newposts;
	}

function get_newfiles() {

	static $newfiles = null;
	if (!is_null($newfiles))	
		return $newfiles;

	$arrfid = array();
	$newfiles = 0;
	if( isset($this->aclfm['id']))
		{
		for( $i = 0; $i < count($this->aclfm['id']); $i++)
			{
			if($this->aclfm['down'][$i])
				{
				$arrfid[] = $this->aclfm['id'][$i];
				}
			}
		}

	if( count($arrfid) > 0 )
		{
		global $babDB;
		$req = "select count(f.id) from ".BAB_FILES_TBL." f where f.bgroup='Y' and f.state='' and f.confirmed='Y' and f.id_owner IN (".implode(',', $arrfid).")";
		$req .= " and f.modified >= '".$this->lastlog."'";
		$req .= " order by f.modified desc";

		list($newfiles) = $babDB->db_fetch_row($babDB->db_query($req));
		}

	return $newfiles;
	}


function & get_icalendars() {

	if (!isset($this->priv_icalendars)) {
		include_once $GLOBALS['babInstallPath']."utilit/calincl.php";
		$this->priv_icalendars = & new bab_icalendars();
	}

	return $this->priv_icalendars;
	}

}  /* end of class babBody */




function bab_isMemberOfTree($id_group, $id_user = '')
{
	global $babBody;

	$lf = &$babBody->ovgroups[$id_group]['lf'];
	$lr = &$babBody->ovgroups[$id_group]['lr'];

	if (!empty($id_user))
		{
		if ($id_group == 0 || $id_group == 1)
			return true;

		$db = &$GLOBALS['babDB'];
		$res = $db->db_query("SELECT COUNT(g.id) FROM ".BAB_GROUPS_TBL." g, ".BAB_USERS_GROUPS_TBL." u WHERE u.id_group=g.id AND u.id_object='".$id_user."' AND g.lf >= '".$babBody->ovgroups[$id_group]['lf']."' AND g.lr <= '".$babBody->ovgroups[$id_group]['lr']."'");
		list($n) = $db->db_fetch_array($res);
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
		$babBody->ovgroups[BAB_ALLUSERS_GROUP]['member'] = 'Y';
		$babBody->usergroups[] = BAB_ALLUSERS_GROUP;

		if( !empty($BAB_SESS_USERID))
			{		
			$res=$babDB->db_query("select id_group, isprimary from ".BAB_USERS_GROUPS_TBL." where id_object='".$BAB_SESS_USERID."'");
			$babBody->ovgroups[BAB_REGISTERED_GROUP]['member'] = 'Y';
			$babBody->usergroups[] = BAB_REGISTERED_GROUP;
			while( $arr = $babDB->db_fetch_array($res))
				{
				$babBody->usergroups[] = $arr['id_group'];
				$babBody->ovgroups[$arr['id_group']]['member'] = 'Y';
				$babBody->ovgroups[$arr['id_group']]['primary'] = $arr['isprimary'];
				}

			$res=$babDB->db_query("select id_group, id_set from ".BAB_GROUPS_SET_ASSOC_TBL." where id_group IN('".implode("','",$babBody->usergroups)."')");
			while( $arr = $babDB->db_fetch_array($res))
				{
				$babBody->usergroups[] = $arr['id_set'];
				$babBody->ovgroups[$arr['id_set']]['member'] = 'Y';
				}
			}
		else
			{
			$babBody->ovgroups[BAB_UNREGISTERED_GROUP]['member'] = 'Y';
			$babBody->usergroups[] = BAB_UNREGISTERED_GROUP;
			}
		}


	

	$res = $babDB->db_query("select * from ".BAB_ADDONS_TBL." where enabled='Y' AND installed='Y'");
	while( $arr = $babDB->db_fetch_array($res))
		{
		$arr['access'] = false;
		if (bab_isAccessValid(BAB_ADDONS_GROUPS_TBL, $arr['id']))
			{
			$arr_ini = @parse_ini_file( $GLOBALS['babAddonsPath'].$arr['title']."/addonini.php");
			if( !empty($arr_ini['version']))
				{
				if ($arr_ini['version'] == $arr['version']) {
					$arr['access'] = true;
					}
				else {
					$babDB->db_query("UPDATE ".BAB_ADDONS_TBL." SET installed='N' WHERE id='".$arr['id']."'");
					}
				}
			}
		$babBody->babaddons[$arr['id']] = $arr;
		}


	$babBody->topman = bab_getUserIdObjects(BAB_TOPICSMAN_GROUPS_TBL);
	$babBody->topsub = bab_getUserIdObjects(BAB_TOPICSSUB_GROUPS_TBL);
	$babBody->topcom = bab_getUserIdObjects(BAB_TOPICSCOM_GROUPS_TBL);
	$babBody->topmod = bab_getUserIdObjects(BAB_TOPICSMOD_GROUPS_TBL);
	$babBody->topview = bab_getUserIdObjects(BAB_TOPICSVIEW_GROUPS_TBL);

	$babBody->icalendars = $babBody->get_icalendars();

	$babBody->ocids = bab_orgchartAccess();

	$babBody->isSuperAdmin = false;

	if( !empty($BAB_SESS_USERID))
		{

		$res=$babDB->db_query("select lang, skin, style, lastlog, langfilter, date_shortformat, date_longformat, time_format from ".BAB_USERS_TBL." where id='".$BAB_SESS_USERID."'");
		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			$arr = $babDB->db_fetch_array($res);
			if( $arr['lang'] != "")
				{
				$GLOBALS['babLanguage'] = $arr['lang'];
				}
			
			if($arr['langfilter'] != '')
				$GLOBALS['babLangFilter']->setFilter($arr['langfilter']);
			
			if(!empty($arr['skin']) && is_dir("skins/".$arr['skin']))
				{
				$GLOBALS['babSkin'] = $arr['skin'];
				}

			if(!empty($arr['style']) && is_file("skins/".$GLOBALS['babSkin']."/styles/".$arr['style']))
				{
				$GLOBALS['babStyle'] = $arr['style'];
				}

			if( $arr['date_shortformat'] != '') {
				$GLOBALS['babShortDateFormat'] = bab_getDateFormat($arr['date_shortformat']) ;
				}

			if( $arr['date_longformat'] != '') {
				$GLOBALS['babLongDateFormat'] = bab_getDateFormat($arr['date_longformat']) ;
				}

			if( $arr['time_format'] != '') {
				$GLOBALS['babTimeFormat'] = bab_getTimeFormat($arr['time_format']) ;
				}

			

			$babBody->lastlog = $arr['lastlog'];

			
			

			bab_fileManagerAccessLevel();



			if( $babBody->ovgroups[BAB_ADMINISTRATOR_GROUP]['member'] == 'Y') {
				$babBody->isSuperAdmin = true;
				
				if (isset($_GET['debug']))
					{
					if (1 == $_GET['debug'])
						setcookie('bab_debug','1',time()+31536000); // 1 year
					if (0 == $_GET['debug'])
						setcookie('bab_debug','',time()-31536000); // remove
					}
				}

			$res = $babDB->db_query("SELECT dg.id FROM ".BAB_DG_ADMIN_TBL." da,".BAB_DG_GROUPS_TBL." dg where da.id_user='".$BAB_SESS_USERID."' AND da.id_dg=dg.id AND dg.id_group >= '0'");
			while( $arr = $babDB->db_fetch_array($res) )
				{
				$babBody->dgAdmGroups[] = $arr['id'];
				}
			
			}

		$res = $babDB->db_query("select id_user, id_substitute from ".BAB_USERS_UNAVAILABILITY_TBL." where curdate() between start_date and end_date");
		if( $res && $babDB->db_num_rows($res) > 0 )
			{
			include_once $GLOBALS['babInstallPath']."utilit/ocapi.php";
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

	
	$res = $babDB->db_query("select id, id_dg, id_user, cpw from ".BAB_USERS_LOG_TBL." where sessid='".session_id()."'");
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		if( extension_loaded('mcrypt') && !empty($arr['cpw']) && isset($GLOBALS['babEncryptionKey']) && !isset($_REQUEST['babEncryptionKey']) && !empty($GLOBALS['babEncryptionKey']) && !empty($BAB_SESS_USERID) && $BAB_SESS_USERID == $arr['id_user'])
			{
			$GLOBALS['babUserPassword'] = bab_decrypt($arr['cpw'], md5($arr['id'].session_id().$BAB_SESS_USERID.$GLOBALS['babEncryptionKey']));
			}

		if( 0 != $arr['id_dg'] && count($babBody->dgAdmGroups) > 0 && in_array($arr['id_dg'], $babBody->dgAdmGroups ))
			{
			$babBody->currentDGGroup = $babDB->db_fetch_array($babDB->db_query("select dg.*, g.lf, g.lr from ".BAB_DG_GROUPS_TBL." dg, ".BAB_GROUPS_TBL." g where g.id=dg.id_group AND dg.id='".$arr['id_dg']."'"));
			$babBody->currentAdmGroup = &$babBody->currentDGGroup['id'];
			
			}
		else if( !$babBody->isSuperAdmin && count($babBody->dgAdmGroups) > 0 )
			{
			$babBody->currentDGGroup = $babDB->db_fetch_array($babDB->db_query("select dg.*, g.lf, g.lr from ".BAB_DG_GROUPS_TBL." dg, ".BAB_GROUPS_TBL." g where g.id=dg.id_group AND dg.id='".$babBody->dgAdmGroups[0]."'"));
			$babBody->currentAdmGroup = &$babBody->currentDGGroup['id'];
			}


		$babDB->db_query("update ".BAB_USERS_LOG_TBL." set dateact=now(), remote_addr='".$GLOBALS['REMOTE_ADDR']."', forwarded_for='".$GLOBALS['HTTP_X_FORWARDED_FOR']."', id_dg='".$babBody->currentDGGroup['id']."', grp_change=NULL, schi_change=NULL, tg='".$babDB->db_escape_string($GLOBALS['tg'])."'  where id = '".$arr['id']."'");
		}
	else
		{
		if( !empty($BAB_SESS_USERID))
			{
			$userid = $BAB_SESS_USERID;
			if( !$babBody->isSuperAdmin && count($babBody->dgAdmGroups) > 0 )
				{
				$babBody->currentAdmGroup = $babBody->dgAdmGroups[0];
				$babBody->currentDGGroup = $babDB->db_fetch_array($babDB->db_query("select dg.* from ".BAB_DG_GROUPS_TBL." dg where dg.id_group='".$babBody->dgAdmGroups[0]."'"));
				}
			}
		else
			{
			$userid = 0;
			}

		$babDB->db_query("insert into ".BAB_USERS_LOG_TBL." (id_user, sessid, dateact, remote_addr, forwarded_for, id_dg, grp_change, schi_change, tg) values ('".$userid."', '".session_id()."', now(), '".$GLOBALS['REMOTE_ADDR']."', '".$GLOBALS['HTTP_X_FORWARDED_FOR']."', '".$babBody->currentDGGroup['id']."', NULL, NULL, '".$babDB->db_escape_string($GLOBALS['tg'])."')");
		}

	$res = $babDB->db_query("select id, UNIX_TIMESTAMP(dateact) as time from ".BAB_USERS_LOG_TBL);
	while( $row  = $babDB->db_fetch_array($res))
		{
		if( $row['time'] + get_cfg_var('session.gc_maxlifetime') < time()) 
			$babDB->db_query("delete from ".BAB_USERS_LOG_TBL." where id='".$row['id']."'");
		}

}

function bab_updateSiteSettings()
{
	global $babDB, $babBody;

	$req="select *, DECODE(smtppassword, \"".$GLOBALS['BAB_HASH_VAR']."\") as smtppass, DECODE(ldap_adminpassword, \"".$GLOBALS['BAB_HASH_VAR']."\") as ldap_adminpassword from ".BAB_SITES_TBL." where name='".addslashes($GLOBALS['babSiteName'])."'";
	$res=$babDB->db_query($req);
	if ($babDB->db_num_rows($res) == 0)
		{
		$babBody->msgerror = bab_translate("Configuration error : babSiteName in config.php not match site name in administration sites configuration");
		}
	$arr = $babDB->db_fetch_array($res);
	$babBody->babsite = $arr;

	if( $arr['skin'] != "")
		{
		$GLOBALS['babSkin'] = $arr['skin'];
		}
	else {
		$GLOBALS['babSkin'] = "ovidentia"; }

	if( $arr['style'] != "")
		{
		$GLOBALS['babStyle'] = $arr['style'];
		}
	else {
		$GLOBALS['babStyle'] = "ovidentia.css"; }

	if( $arr['lang'] != "")
		{
		$GLOBALS['babLanguage'] = $arr['lang'];
		}
	else {
		$GLOBALS['babLanguage'] = "en"; }
	if( $arr['adminemail'] != "")
		{
		$GLOBALS['babAdminEmail'] = $arr['adminemail'];
		}
	else {
		$GLOBALS['babAdminEmail'] = "admin@your-domain.com"; }
	if( $arr['langfilter'] != "")
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
		$GLOBALS['babMaxTotalSize'] = "200000000";
		}
	if( !empty($arr['user_diskspace']))
		{
		$GLOBALS['babMaxUserSize'] = $arr['user_diskspace']*1048576;
		}
	else
		{
		$GLOBALS['babMaxUserSize'] = "30000000";
		}
	if( !empty($arr['folder_diskspace']))
		{
		$GLOBALS['babMaxGroupSize'] = $arr['folder_diskspace']*1048576;
		}
	else
		{
		$GLOBALS['babMaxGroupSize'] = "50000000"; 
		}
	if( !empty($arr['maxfilesize']))
		{
		$GLOBALS['babMaxFileSize'] = $arr['maxfilesize']*1048576;
		}
	else
		{
		$GLOBALS['babMaxFileSize'] = "30000000"; 
		}
	if( !empty($arr['uploadpath']))
		{
		$GLOBALS['babUploadPath'] = $arr['uploadpath'];
		}
	else
		{
		$GLOBALS['babUploadPath'] = '';
		}

	if(!empty($GLOBALS['babUploadPath']) && !is_dir($GLOBALS['babUploadPath']."/addons/"))
		{
		bab_mkdir($GLOBALS['babUploadPath']."/addons/", $GLOBALS['babMkdirMode']);
		}

	if( $arr['babslogan'] != "")
		{
		$GLOBALS['babSlogan'] = $arr['babslogan'];
		}
	if( $arr['name_order'] != "")
		{
		$GLOBALS['babBody']->nameorder = explode(" ",$arr['name_order']);
		}
	else {
		$GLOBALS['babBody']->nameorder = Array('F','L');}
	if( $arr['remember_login'] == "Y")
		{
		$GLOBALS['babCookieIdent'] = true;
		$GLOBALS['c_nickname'] = '';
		}
	elseif ($arr['remember_login'] == "L")
		{
		$GLOBALS['babCookieIdent'] = 'login' ;
		$GLOBALS['c_nickname'] = isset($_COOKIE['c_nickname']) ? trim($_COOKIE['c_nickname']) : '';
		}
	else {
		$GLOBALS['babCookieIdent'] = false ; 
		$GLOBALS['c_nickname'] = '';
		}
	if( $arr['email_password'] == "Y") {
		$GLOBALS['babEmailPassword'] = true ; }
	else {
		$GLOBALS['babEmailPassword'] = false ; }

	$GLOBALS['babAdminName'] = $arr['adminname'];

	if( $arr['date_shortformat'] == "") {
		$GLOBALS['babShortDateFormat'] = bab_getDateFormat("dd/mm/yyyy") ; }
	else {
		$GLOBALS['babShortDateFormat'] = bab_getDateFormat($arr['date_shortformat']) ; }

	if( $arr['date_longformat'] == "") {
		$GLOBALS['babLongDateFormat'] = bab_getDateFormat("ddd dd MMMM yyyy") ; }
	else {
		$GLOBALS['babLongDateFormat'] = bab_getDateFormat($arr['date_longformat']) ; }

	if( $arr['time_format'] == "") {
		$babBody->ampm = false;
		$GLOBALS['babTimeFormat'] = bab_getTimeFormat("HH:mm") ; }
	else {
		$GLOBALS['babTimeFormat'] = bab_getTimeFormat($arr['time_format']) ; }

	if( $arr['authentification'] == 1 ) // LDAP authentification
		{
		$babBody->babsite['registration'] ='N';
		$babBody->babsite['change_nickname'] ='N';
		}

	

	$res = $babDB->db_query("select id, UNIX_TIMESTAMP(dateact) as time from ".BAB_USERS_LOG_TBL);
	while( $row  = $babDB->db_fetch_array($res))
		{
		if( $row['time'] + get_cfg_var('session.gc_maxlifetime') < time()) 
			{
			$res2 = $babDB->db_query("select id from ".BAB_ART_DRAFTS_TBL." where id_author='0' and id_anonymous='".$row['id']."'");
			while( $arr  = $babDB->db_fetch_array($res2))
				{
				bab_deleteArticleDraft($arr['id']);
				}
			$babDB->db_query("delete from ".BAB_USERS_LOG_TBL." where id='".$row['id']."'");
			}
		}

	$res = $babDB->db_query("select id,id_author, id_topic, id_article, date_submission from ".BAB_ART_DRAFTS_TBL." where result='".BAB_ART_STATUS_DRAFT."' and date_submission <= now() and date_submission !='0000-00-00 00:00:00'");
	if( $res && $babDB->db_num_rows($res) > 0 )
	{
	include_once $GLOBALS['babInstallPath']."utilit/topincl.php";
	include_once $GLOBALS['babInstallPath']."utilit/artincl.php";
	while( $arr  = $babDB->db_fetch_array($res))
		{
		$bsubmit = false;
		if( $arr['id_article'] != 0 )
			{
			$res = $babDB->db_query("select at.id_topic, at.id_author, tt.allow_update, tt.allow_manupdate from ".BAB_ARTICLES_TBL." at left join ".BAB_TOPICS_TBL." tt on at.id_topic=tt.id  where at.id='".$arr['id_article']."'");
			if( $res && $babDB->db_num_rows($res) == 1 )
				{
				$rr = $babDB->db_fetch_array($res);
				if( ( $rr['allow_update'] != '0' && $rr['id_author'] == $GLOBALS['BAB_SESS_USERID']) || bab_isAccessValidByUser(BAB_TOPICSMOD_GROUPS_TBL, $rr['id_topic'], $arr['id_author']) || ( $rr['allow_manupdate'] != '0' && bab_isAccessValidByUser(BAB_TOPICSMAN_GROUPS_TBL, $rr['id_topic'], $arr['id_author'])))
					{
					$bsubmit = true;
					}
				}
			}

		if( $arr['id_topic'] != 0 && bab_isAccessValidByUser(BAB_TOPICSSUB_GROUPS_TBL, $arr['id_topic'], $arr['id_author']))
			{
			$bsubmit = true;
			}

		if( $bsubmit )
			{
			bab_submitArticleDraft($arr['id']);
			}
		}
	}

	$res = $babDB->db_query("select id from ".BAB_ARTICLES_TBL." where date_archiving <= now() and date_archiving !='0000-00-00 00:00:00' and archive='N'");
	while( $arr  = $babDB->db_fetch_array($res))
		{
		$babDB->db_query("update ".BAB_ARTICLES_TBL." set archive='Y' where id = '".$arr['id']."'");
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
						$this->activeLanguageValues[] = '\''.substr($GLOBALS['babLanguage'], 0, 2).'\'';
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
				$res = substr($fileName, 0, 5);
				if ($res != "lang-") return false;
				$res = strtolower(strstr($fileName, "."));
				if ($res != ".xml") return false;
				return true;
			}

		function getLangCode($file)
			{
				$langCode = substr($file,5);
				return substr($langCode,0,strlen($langCode)-4);
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
				if (file_exists("lang"))
					{
						$folder = opendir("lang");
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
				sort($tmpLangFiles);
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

$babBody = new babBody();
$BAB_HASH_VAR='aqhjlongsmp';
$babLangFilter = new babLanguageFilter();
?>