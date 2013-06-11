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
/**
 * @internal SEC1 NA 08/12/2006 FULL
 */
include_once 'base.php';
require_once dirname(__FILE__).'/utilit/registerglobals.php';
include_once $babInstallPath.'utilit/topincl.php';

function listTopicCategory($cat)
{
	global $babBody, $babDB;
	class temp
	{

		var $id;
		var $arr = array();
		var $db;
		var $count;
		var $topicname;
		var $topiccategoryname;
		var $topicdescription;
		var $articlestxt;
		var $articlesurl;
		var $articlescount;
		var $idcat;
		var $arrid = array();
		var $arrcatid = array();
		var $arrparents = array();
		var $waitingtxt;
		var $waitingarticlescount;
		var $waitingcommentscount;
		var $submiturl;
		var $submittxt;
		var $childscount;
		var $childname;
		var $childurl;
		var $parentscount;
		var $parentname;
		var $parenturl;
		var $istopcat;
		var $burl;
		var $bHaveAssociatedImage	= false;
		var $sImageUrl				= '#';

		function temp($cat)
		{
			require_once dirname(__FILE__).'/utilit/artapi.php';
			global $babBody, $babDB, $BAB_SESS_USERID;
			$this->articlestxt = bab_translate("Article") ."(s)";
			$this->waitingtxt = bab_translate("Waiting");
			$this->submittxt = bab_translate("Submit");
			$this->idcat = bab_toHtml($cat); /* don't change variable name */

			$arrtopcat = array();
			$arrtop = array();
			$req = "select * from ".BAB_TOPCAT_ORDER_TBL." where id_parent='".$babDB->db_escape_string($cat)."' order by ordering asc";
			$res = $babDB->db_query($req);
			$topcatview = bab_getReadableArticleCategories();
			while( $row = $babDB->db_fetch_array($res))
			{
				if($row['type'] == '2' && bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL ,$row['id_topcat']))
				{
					array_push($this->arrid, array($row['id_topcat'], 2));
					array_push($arrtop, $row['id_topcat']);
				}
				else if( $row['type'] == '1' && isset($topcatview[$row['id_topcat']]))
				{
					array_push($this->arrid, array($row['id_topcat'], 1));
					array_push($arrtopcat, $row['id_topcat']);
				}
			}
			$this->count = count($this->arrid);

			if( $cat != 0 )
			{
				$this->arrparents[] = $cat;
				
				require_once dirname(__FILE__).'/utilit/artapi.php';
				$topcats = bab_getArticleCategories();
				
				while( $topcats[$cat]['parent'] != 0 )
				{
					$this->arrparents[] = $topcats[$cat]['parent'];
					$cat = $topcats[$cat]['parent'];
				}
			}
			$this->arrparents[] = 0;

			$this->parentscount = count($this->arrparents);
			$this->arrparents = array_reverse($this->arrparents);

			if( count($arrtop) > 0 )
			{
				$res = $babDB->db_query("select * from ".BAB_TOPICS_TBL." where id IN (".$babDB->quote($arrtop).")");
				while( $arr = $babDB->db_fetch_array($res))
				{
					for($i=0; $i < $this->count; $i++)
					{
						if( $this->arrid[$i][1] == 2 && $this->arrid[$i][0]== $arr['id'])
						{
							$this->arrid[$i]['title'] = $arr['category'];
								
							include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
							$editor = new bab_contentEditor('bab_topic');
							$editor->setContent($arr['description']);
							$editor->setFormat($arr['description_format']);
							$this->arrid[$i]['description'] = $editor->getHtml();
								
							$this->arrid[$i]['confirmed'] = 0;
						}
					}
				}

				$res = $babDB->db_query("select count(id) total, id_topic from ".BAB_ARTICLES_TBL." where id IN (".$babDB->quote($arrtop).") GROUP by id_topic");
				while( $arr = $babDB->db_fetch_array($res))
				{
					for($i=0; $i < $this->count; $i++)
					{
						if( $this->arrid[$i][1] == 2 && $this->arrid[$i][0]== $arr['id_topic'])
						{
							$this->arrid[$i]['confirmed'] = $arr['total'];
						}
					}
				}
			}

			if( count($arrtopcat) > 0 )
			{
				$res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id IN (".$babDB->quote($arrtopcat).")");
				while( $arr = $babDB->db_fetch_array($res))
				{
					for($i=0; $i < $this->count; $i++)
					{
						if( $this->arrid[$i][1] == 1 && $this->arrid[$i][0]== $arr['id'])
						{
							$this->arrid[$i]['title'] = $arr['title'];
							$this->arrid[$i]['description'] = $arr['description'];
						}
					}
				}
			}
		}

		function getnext(&$skip)
		{
			global $babBody;
			static $i = 0;
			if( $i < $this->count)
			{
				if (!isset($this->arrid[$i]['title']))
				{
					$skip = true;$i++;
					return true;
				}
				
				$this->submiturl = "";
				$this->childurl = "";
				$this->childname = $this->arrid[$i]['title'];
				$this->childdescription = trim($this->arrid[$i]['description']);


				$this->bHaveAssociatedImage	= false;
				$this->sImageUrl			= '#';


				if( $this->arrid[$i][1] == 1 )
				{
					$this->childurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=topusr&cat=".$this->arrid[$i][0]);
					$this->istopcat = true;
					$this->idtopiccategory = bab_toHtml($this->arrid[$i][0]); /* don't change variable name */
					$this->idtopic = ''; /* don't change variable name */

					$aImageInfo	= bab_getImageCategory($this->idtopiccategory);
					if(false !== $aImageInfo)
					{
						$this->bHaveAssociatedImage = true;
						$this->sImageUrl 			= $GLOBALS['babUrlScript'] . '?tg=topcat&idx=getImage&iWidth=50&sImage=' .
							$aImageInfo['name'] . '&iIdCategory=' . $this->idtopiccategory;
					}

				}
				else
				{
					$this->idtopiccategory = '';
					$this->idtopic = bab_toHtml($this->arrid[$i][0]);
					$this->istopcat = false;

					$aImageInfo	= bab_getImageTopic($this->idtopic);
					if(false !== $aImageInfo)
					{
						$this->bHaveAssociatedImage = true;
						$this->sImageUrl			= $GLOBALS['babUrlScript'] . '?tg=topic&idx=getImage&iWidth=50&sImage=' .
							$aImageInfo['name'] . '&iIdTopic=' . $this->idtopic . '&item=' . $this->idtopic . '&cat=';
					}

					if( $this->arrid[$i]['confirmed'] == 0 )
					$this->submiturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=Submit&topics=".$this->arrid[$i][0]);
					$this->waitingarticlescount = 0;
					$this->waitingcommentscount = 0;
					$this->articlesurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&topics=".$this->arrid[$i][0]."&new=".$this->waitingarticlescount."&newc=".$this->waitingcommentscount);
					$this->childurl = $this->articlesurl;
				}

				$i++;
				return true;
			}
			else
			return false;
		}

		function getnextparent()
		{
			global $babBody;
			static $i = 0;
			if( $i < $this->parentscount)
			{
				if( $this->arrparents[$i] == 0 ) {
					$this->parentname = bab_translate("Top");
				}
				else {
					
					require_once dirname(__FILE__).'/utilit/artapi.php';
					$topcats = bab_getArticleCategories();
					
					$this->parentname = bab_toHtml($topcats[$this->arrparents[$i]]['title']);
				}
				$this->parenturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=topusr&cat=".$this->arrparents[$i]);
				if( $i == $this->parentscount - 1 )
				$this->burl = false;
				else
				$this->burl = true;
				$i++;
				return true;
			}
			else
			return false;
		}
	}

	$template = "default";
	if( $cat != 0 )
	{
		$res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$babDB->db_escape_string($cat)."'");
		if( $res && $babDB->db_num_rows($res) > 0 )
		{
			$arr = $babDB->db_fetch_array($res);
			if( $arr['display_tmpl'] != '' )
			$template = $arr['display_tmpl'];
		}
	}

	$temp = new temp($cat);
	$html = bab_printTemplate($temp,"topcatdisplay.html", $template);
	if (empty($html))
	$html = bab_printTemplate($temp,"topcatdisplay.html", 'default');
	$babBody->babecho( $html );
	return isset($temp->topicscount) ? $temp->topicscount : '';
}

function getTopicImage()
{	
	require_once dirname(__FILE__) . '/utilit/artincl.php';
	require_once dirname(__FILE__) . '/utilit/gdiincl.php';
	
	$iWidth			= (int) bab_rp('iWidth', 0);
	$iHeight		= (int) bab_rp('iHeight', 0);
	$sImage			= (string) bab_rp('sImage', '');
	$sOldImage		= (string) bab_rp('sOldImage', '');
	$iIdTopic		= (int) bab_rp('iIdTopic', 0);
	
	$oEnvObj		= bab_getInstance('bab_PublicationPathsEnv');
	
	$iIdDelegation = bab_getTopicDelegationId($iIdTopic);
	if(false === $iIdDelegation)
	{
		return false;
	}
	$oEnvObj->setEnv($iIdDelegation);
	
	
	$sPath = '';
	if(0 !== $iIdTopic)
	{
		$sPath = $oEnvObj->getTopicImgPath($iIdTopic);
	}
	else
	{
		$sPath = $oEnvObj->getTempPath();
	}
	$oImageResize = new bab_ImageResize();
	$oImageResize->resizeImageAuto($sPath . $sImage, $iWidth, $iHeight);

	if(file_exists($sPath . $sOldImage))
	{
		@unlink($sPath . $sOldImage);
	}
}

	
function getCategoryImage()
{	
	require_once dirname(__FILE__) . '/utilit/artapi.php';
	require_once dirname(__FILE__) . '/utilit/artincl.php';
	require_once dirname(__FILE__) . '/utilit/gdiincl.php';

	$iWidth			= (int) bab_rp('iWidth', 0);
	$iHeight		= (int) bab_rp('iHeight', 0);
	$sImage			= (string) bab_rp('sImage', '');
	$sOldImage		= (string) bab_rp('sOldImage', '');
	$iIdCategory	= (int) bab_rp('iIdCategory', 0);
	$oEnvObj		= bab_getInstance('bab_PublicationPathsEnv');

	$iIdDelegation = bab_getTopicCategoryDelegationId($iIdCategory);
	if(false === $iIdDelegation)
	{
		return false;
	}

	global $babBody;
	$oEnvObj->setEnv($iIdDelegation);
	
	$sPath = '';
	if(0 !== $iIdCategory)
	{
		$sPath = $oEnvObj->getCategoryImgPath($iIdCategory);
	}
	else
	{
		$sPath = $oEnvObj->getTempPath();
	}
	
	$oImageResize = new bab_ImageResize();
	$oImageResize->resizeImageAuto($sPath . $sImage, $iWidth, $iHeight);

	if(file_exists($sPath . $sOldImage))
	{
		@unlink($sPath . $sOldImage);
	}
}


/* main */
$idx = bab_rp('idx', 'list');
$cat = bab_rp('cat', 0);

switch($idx)
{
	case 'getTopicImage':
		getTopicImage(); // called by ajax
		exit;
	case 'getCategoryImage':
		getCategoryImage(); // called by ajax
		exit;
		
	default:
	case 'list':
		$babBody->setTitle(bab_getTopicCategoryTitle($cat));
		bab_siteMap::setPosition('bab', 'ArticleCategory_'.$cat);
		listTopicCategory($cat);
		break;
}
?>
