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
require_once 'base.php';
require_once dirname(__FILE__) . '/acl.php';
require_once dirname(__FILE__) . '/../utilit/topincl.php';
require_once dirname(__FILE__) . '/../utilit/tree.php';


class bab_AdmArticleTreeView extends bab_ArticleTreeView
{
	function bab_AdmArticleTreeView($sId)
	{
		parent::bab_ArticleTreeView($sId);
	}

	function onElementAppended(&$oElement, $sIdParent)
	{
		global $babBody;

		if('categoryroot' == $oElement->_type)
		{
			if( !$babBody->currentAdmGroup )
			{
			$sAddCategUrl = $GLOBALS['babUrlScript'] . '?tg=topcats&idx=Create&idp=0';
			$oElement->addAction(
				'addCateg', bab_toHtml(bab_translate("Create a topic category")), 
				$GLOBALS['babSkinPath'] . 'images/Puces/add_category.png', $sAddCategUrl, '');
			}
				
			$sOrderUrl = $GLOBALS['babUrlScript'] . '?tg=topcats&idx=Order&idp=0';
			$oElement->addAction(
				'order', bab_toHtml(bab_translate("Order")), 
				$GLOBALS['babSkinPath'] . 'images/Puces/z-a.gif', $sOrderUrl, '');
		}
		else if('category' == $oElement->_type)
		{
			$iIdParent = $this->getId($sIdParent);
			$iId = $this->getId($oElement->_id);
			
			$sAddCategUrl = $GLOBALS['babUrlScript'] . '?tg=topcats&idx=Create&idp=' . $iId;
			$oElement->addAction(
				'addCateg', bab_toHtml(bab_translate("Create a topic category")), 
				$GLOBALS['babSkinPath'] . 'images/Puces/add_category.png', $sAddCategUrl, '');
			
			$sDelCategUrl = $GLOBALS['babUrlScript'] . '?tg=topcat&idx=Delete&catdel=dummy&item=' . $iId . '&idp=' . $iIdParent;
			$oElement->addAction(
				'delCateg', bab_toHtml(bab_translate("Delete topic category")), 
				$GLOBALS['babSkinPath'] . 'images/Puces/edit_remove.png', $sDelCategUrl, '');
				
			$sAddTopicUrl = $GLOBALS['babUrlScript'] . '?tg=topics&idx=addtopic&cat=' . $iId;
			$oElement->addAction(
				'addTopic', bab_toHtml(bab_translate("Create new topic")), 
				$GLOBALS['babSkinPath'] . 'images/Puces/add_topic.png', $sAddTopicUrl, '');
				
			$sOrderUrl = $GLOBALS['babUrlScript'] . '?tg=topcats&idx=Order&idp=' . $iId;
			$oElement->addAction(
				'order', bab_toHtml(bab_translate("Order")), 
				$GLOBALS['babSkinPath'] . 'images/Puces/z-a.gif', $sOrderUrl, '');


			if('N' == $this->_datas['enabled'])
			{
				$sEnableDisableUrl = $GLOBALS['babUrlScript'] . '?tg=topcats&update=enable&iIdTopCat=' . $iId;
				$oElement->addAction(
					'enableDisable', bab_toHtml(bab_translate("Activate the section")), 
					$GLOBALS['babSkinPath'] . 'images/Puces/action_success.gif', $sEnableDisableUrl, '');
			}
			else 
			{
				$sEnableDisableUrl = $GLOBALS['babUrlScript'] . '?tg=topcats&update=disable&iIdTopCat=' . $iId;
				$oElement->addAction(
					'enableDisable', bab_toHtml(bab_translate("Desactivate the section")), 
					$GLOBALS['babSkinPath'] . 'images/Puces/action_fail.gif', $sEnableDisableUrl, '');
			}

			$sRightUrl = $GLOBALS['babUrlScript'] . '?tg=topcat&idx=rights&item=' . $iId;
			$oElement->addAction(
				'right', bab_toHtml(bab_translate("Default rights")), 
				$GLOBALS['babSkinPath'] . 'images/Puces/access.png', $sRightUrl, '');


			$oElement->setLink($GLOBALS['babUrlScript'] . '?tg=topcat&idx=Modify&item=' . $iId . '&idp=' . $iIdParent);
		}
		else if('topic' == $oElement->_type)
		{
			$iIdParent = $this->getId($sIdParent);
			$iId = $this->getId($oElement->_id);
			
			$sDelTopicUrl = $GLOBALS['babUrlScript'] . '?tg=topic&idx=Delete&topdel=dummy&item=' . $iId . '&cat=' . $iIdParent;
			$oElement->addAction(
				'delCateg', bab_toHtml(bab_translate("Delete the topic")), 
				$GLOBALS['babSkinPath'] . 'images/Puces/edit_remove.png', $sDelTopicUrl, '');
			
			$sRightUrl = $GLOBALS['babUrlScript'] . '?tg=topic&idx=rights&item=' . $iId . '&cat=' . $iIdParent;
			$oElement->addAction(
				'right', bab_toHtml(bab_translate("Rights")), 
				$GLOBALS['babSkinPath'] . 'images/Puces/access.png', $sRightUrl, '');

			$oElement->setLink($GLOBALS['babUrlScript'] . '?tg=topic&idx=Modify&item=' . $iId . '&cat=' . $iIdParent);
		}

	}
	
	function getId($sId)
	{
		static $iIdIdx = 1;
		if(!is_null($sId))
		{
			$aExploded = explode(BAB_TREE_VIEW_ID_SEPARATOR, $sId);
			if(count($aExploded) == 2)
			{
				return $aExploded[$iIdIdx];
			}
		}
		return 0;
	}
}
	

function topcatCreate($idp)
{
	global $babBody;
	class temp
	{
		var $sNameCaption;
		var $sDescriptionCaption;
		var $enabled;
		var $no;
		var $yes;
		var $add;
		var $arrtmpl;
		var $counttmpl;
		var $arrdisptmpl;
		var $countdisptmpl;
		var $templatetxt;
		var $templateval;
		var $templateid;
		var $disptmpltxt;
		var $topcattxt;
		var $topcatid;
		var $topcatval;
		var $nonetxt;
		var $idp;
		var $selected;
		var $iMaxImgFileSize;
		var $bImageUploadEnable = false;
		
		var $sSelectImageCaption;
		var $sImagePreviewCaption;
		
		var $sName;
		var $sDescription;
		
		var $aPrivSection; 
		var $sPrivSecValue;
		var $sPrivSecCaption;
		var $sSelectedPrivSec;
		var $sPostedPrivSec;
		
		var $sSelectedTmpl;
		var $sPostedTmpl;
		
		var $sSelectedDispTmpl;
		var $sPostedDispTmpl;
		
		var $sTempImgName;
		var $sImgName;
		var $sAltImagePreview;
		var $bUploadPathValid = false;
		var $sDisabledUploadReason;
		
		function temp($idp)
		{
			global $babBody, $babDB;
			$this->iMaxImgFileSize		= (int) $GLOBALS['babMaxImgFileSize'];
			$this->bUploadPathValid		= is_dir($GLOBALS['babUploadPath']);
			$this->bImageUploadEnable	= (0 !== $this->iMaxImgFileSize && $this->bUploadPathValid);
			$this->sNameCaption			= bab_translate("Name");
			$this->sDescriptionCaption	= bab_translate("Description");
			$this->enabled				= bab_translate("Section enabled");
			$this->add					= bab_translate("Add");
			$this->templatetxt			= bab_translate("Section template");
			$this->disptmpltxt			= bab_translate("Display template");
			$this->topcattxt			= bab_translate("Topics category parent");
			$this->nonetxt				= '--- ' . bab_translate("None") . ' ---';
			$this->idp					= bab_rp('topcatid', $idp);
			$this->aPrivSection			= array('N' => bab_translate("No"), 'Y' => bab_translate("Yes"));
			$this->sName				= bab_rp('name', '');
			$this->sDescription			= bab_rp('description', '');
			$this->sPostedPrivSec		= bab_rp('benabled', 'N');
			$this->sPostedTmpl			= bab_rp('template', 'template');
			$this->sPostedDispTmpl		= bab_rp('disptmpl', 'default');
			$this->sSelectImageCaption	= bab_translate('Select a picture');
			$this->sImagePreviewCaption	= bab_translate('Preview image');
			$this->sTempImgName			= bab_rp('sTempImgName', '');
			$this->sImgName				= bab_rp('sImgName', '');
			$this->sAltImagePreview		= bab_translate("Previlualization of the image");
			
			$this->processDisabledUploadReason();
			
			$file		= 'topicssection.html';
			$filepath	= 'skins/' . $GLOBALS['babSkin'] . '/templates/' . $file;
			if(!file_exists( $filepath ))
			{
				$filepath = $GLOBALS['babSkinPath'] . 'templates/' . $file;
				if(!file_exists($filepath))
				{
					$filepath = $GLOBALS['babInstallPath'] . 'skins/ovidentia/templates/' . $file;
				}
			}
			
			if(file_exists($filepath))
			{
				$tpl = new babTemplate();
				$this->arrtmpl = $tpl->getTemplates($filepath);
			}
			$this->counttmpl = count($this->arrtmpl);

			$file		= 'topcatdisplay.html';
			$filepath	= 'skins/' . $GLOBALS['babSkin'] . '/templates/' . $file;
			if(!file_exists($filepath))
			{
				$filepath = $GLOBALS['babSkinPath'] . 'templates/' . $file;
				if(!file_exists($filepath))
				{
					$filepath = $GLOBALS['babInstallPath'] . 'skins/ovidentia/templates/' . $file;
				}
			}
				
			if(file_exists($filepath))
			{
				$tpl = new babTemplate();
				$this->arrdisptmpl = $tpl->getTemplates($filepath);
			}
			$this->countdisptmpl = count($this->arrdisptmpl);

			$res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."'");

			$this->arrtopcats = array();
			if($babBody->isSuperAdmin)
			{
				$this->arrtopcats[] = array('id'=> 0, 'title' => $this->nonetxt);
			}
			
			while($arr = $babDB->db_fetch_array($res))
			{
				$this->arrtopcats[] = array('id'=> $arr['id'], 'title' => $arr['title']);
			}
			$this->topcatscount = count($this->arrtopcats);

		}

		function processDisabledUploadReason()
		{
			$this->sDisabledUploadReason = '';
			if(false == $this->bImageUploadEnable)
			{
				$this->sDisabledUploadReason = bab_translate("Loading image is not active because");
				$this->sDisabledUploadReason .= '<UL>';
				
				if('' == $GLOBALS['babUploadPath'])
				{
					$this->sDisabledUploadReason .= '<LI>'. bab_translate("The upload path is not set");
				}
				else if(!is_dir($GLOBALS['babUploadPath']))
				{
					$this->sDisabledUploadReason .= '<LI>'. bab_translate("The upload path is not a dir");
				}
				
				if(0 == $this->iMaxImgFileSize)
				{
					$this->sDisabledUploadReason .= '<LI>'. bab_translate("The maximum size for a defined image is zero byte");
				}
				$this->sDisabledUploadReason .= '</UL>';
			}
		}
		
		function getNextPrivateSectionInfo()
		{
			$this->sSelectedPrivSec = '';
			
			$aDatas = each($this->aPrivSection);
			if(false !== $aDatas)
			{			 
				$this->sPrivSecValue = $aDatas['key'];
				$this->sPrivSecCaption = $aDatas['value'];
				if($this->sPostedPrivSec == $this->sPrivSecValue)
				{
					$this->sSelectedPrivSec = 'selected="selected"';
				}
				return true;
			}
			return false;
		}
		
		function getnexttemplate()
		{
			static $i = 0;
			
			$this->sSelectedTmpl = '';
			
			if($i < $this->counttmpl)
			{
				$this->templateid = $this->arrtmpl[$i];
				$this->templateval = $this->arrtmpl[$i];
				if($this->sPostedTmpl == $this->templateid)
				{
					$this->sSelectedTmpl = 'selected="selected"';
				}
				$i++;
				return true;
			}
			return false;
		}

		function getnextdisptemplate()
		{
			static $i = 0;
			
			$this->sSelectedDispTmpl = '';
			
			if($i < $this->countdisptmpl)
			{
				$this->templateid = $this->arrdisptmpl[$i];
				$this->templateval = $this->arrdisptmpl[$i];
				if($this->sPostedDispTmpl == $this->templateid)
				{
					$this->sSelectedDispTmpl = 'selected="selected"';
				}
				$i++;
				return true;
			}
			return false;
		}

		function getnexttopcat()
		{
			global $babDB;
			static $i = 0;
			if($i < $this->topcatscount)
			{
				$arr = $this->arrtopcats[$i];
				$this->topcatid = $arr['id'];
				$this->topcatval = $arr['title'];
				if($this->idp == $this->topcatid)
				{
					$this->selected = 'selected="selected"';
				}
				else
				{
					$this->selected = "";
				}
				$i++;
				return true;
			}
			else
			{
				return false;
			}
		}
	}
	
	$babBody->addStyleSheet('publication.css');
	$babBody->addJavascriptFile($GLOBALS['babScriptPath'].'prototype/prototype.js');
		
	$temp = new temp($idp);
	$babBody->babecho(bab_printTemplate($temp, 'topcats.html', 'topcatcreate'));
}




function topcatsList($idp)
	{
	global $babBody;
	class temp
		{
		var $name;
		var $url;
		var $description;
				
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $catchecked;
		var $disabled;
		var $checkall;
		var $uncheckall;
		var $update;
		var $topcount;
		var $topcounturl;
		var $topics;
		var $topcats;
		var $topcatcount;
		var $topcatcounturl;
		var $arrparents = array();
		var $countparents;
		var $parentval;
		var $parenturl;
		var $burl;
		var $altbg = true;
		
		function temp($idp)
			{
			global $babBody;
			$this->name = bab_translate("Name");
			$this->description = bab_translate("Description");
			$this->disabled = bab_translate("Section disabled");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->update = bab_translate("Update");
			$this->topics = bab_translate("Number of topics");
			$this->topcats = bab_translate("Number of topics categories");
			
			
			$this->db = $GLOBALS['babDB'];
			$req = "select c.* 
				FROM 
					".BAB_TOPICS_CATEGORIES_TBL." c, 
					".BAB_TOPCAT_ORDER_TBL." o 
				WHERE 
					id_dgowner=".$this->db->quote($babBody->currentAdmGroup)." 
					AND c.id=o.id_topcat 
					AND c.id_parent=".$this->db->quote($idp)." 
					AND type='1' 
				ORDER BY o.ordering
				";
			
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->idp = $idp;

			if( $idp != 0)
				{
				$res = $this->db->db_query("select id_parent from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."' and id='".$idp."'");
				while($arr = $this->db->db_fetch_array($res))
					{
					if( $arr['id_parent'] == 0 )
						break;
					$this->arrparents[] = $arr['id_parent'];
					$res = $this->db->db_query("select id_parent from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."' and id='".$arr['id_parent']."'");
					}
				$this->arrparents[] = 0;
				$this->arrparents = array_reverse($this->arrparents);	
				$this->arrparents[] = $idp;
				}
			$this->countparents = count($this->arrparents);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = $this->altbg ? false : true;
				$this->arr = $this->db->db_fetch_array($this->res);
				$this->url = $GLOBALS['babUrlScript']."?tg=topcat&idx=Modify&item=".$this->arr['id']."&idp=".$this->idp;
				$r = $this->db->db_fetch_array($this->db->db_query("select count(*) as total from ".BAB_TOPICS_TBL." where id_cat='".$this->arr['id']."'"));
				$this->topcount = $r['total'];
				$this->topcounturl = $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$this->arr['id'];
				if( $this->arr['enabled'] == "N")
					$this->catchecked = "checked";
				else
					$this->catchecked = "";
				$r = $this->db->db_fetch_array($this->db->db_query("select count(*) as total from ".BAB_TOPICS_CATEGORIES_TBL." where id_parent='".$this->arr['id']."'"));
				$this->topcatcount = $r['total'];
				$this->topcatcounturl = $GLOBALS['babUrlScript']."?tg=topcats&idx=List&idp=".$this->arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}
		function getnextcat()
			{
			static $i = 0;
			if( $i < $this->countparents)
				{
				if( $this->arrparents[$i] == 0 )
					$this->parentval = bab_translate("Top");
				else
					$this->parentval = bab_getTopicCategoryTitle($this->arrparents[$i]);
				$this->parenturl = $GLOBALS['babUrlScript']."?tg=topcats&idx=List&idp=".$this->arrparents[$i];
				if( $i == $this->countparents - 1 )
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

	$temp = new temp($idp);
	$babBody->babecho(	bab_printTemplate($temp, "topcats.html", "topcatslist"));
	return $temp->count;
	}


function orderTopcat($idp)
	{
	global $babBody;
	class temp
		{		

		var $sorta;
		var $sortd;
		var $idp;

		function temp($idp)
			{
			global $babBody, $BAB_SESS_USERID;
			if( $idp == 0 )
				$catname = bab_translate("Top");
			else
				$catname = bab_getTopicCategoryTitle($idp);

			$this->idp = $idp;
			$this->catname = "---- ".$catname." ----";
			$this->moveup = bab_translate("Move Up");
			$this->movedown = bab_translate("Move Down");
			$this->sorta = bab_translate("Sort ascending");
			$this->sortd = bab_translate("Sort descending");
			$this->create = bab_translate("Modify");
			$this->db = $GLOBALS['babDB'];
			if( $idp == 0 && $babBody->isSuperAdmin )
				$req = "select * from ".BAB_TOPCAT_ORDER_TBL." where id_parent='0' order by ordering asc";
			else
				$req = "select * from ".BAB_TOPCAT_ORDER_TBL." where id_parent='".$idp."' order by ordering asc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				if( $arr['type'] == 1)
					$this->topicval = bab_getTopicCategoryTitle($arr['id_topcat']);
				else if( $arr['type'] == 2)
					$this->topicval = bab_getCategoryTitle($arr['id_topcat']);
				else
					$this->topicval = "";
					
				$this->topicval = bab_toHtml($this->topicval);
					
				$this->topicid = $arr['id'];
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp($idp);
	$babBody->babecho(	bab_printTemplate($temp, "sites.html", "scripts"));
	$babBody->babecho(	bab_printTemplate($temp,"topcats.html", "topcatorder"));
	return $temp->count;
	}

function getHiddenUpload()
{
	require_once $GLOBALS['babInstallPath'].'utilit/hiddenUpload.class.php';
	
	$oHiddenForm = new bab_HiddenUploadForm();
	
	$oHiddenForm->addHiddenField('tg', 'topcats');
	$oHiddenForm->addHiddenField('MAX_FILE_SIZE', $GLOBALS['babMaxImgFileSize']);
	$oHiddenForm->addHiddenField('idx', 'uploadCategoryImg');
	
	header('Cache-control: no-cache');
	die($oHiddenForm->getHtml());
}
	
function addTopCat($name, $description, $benabled, $template, $disptmpl, $topcatid)
{
	global $babBody;
	if(empty($name))
	{
		$babBody->addError(bab_translate("ERROR: You must provide a name !!"));
		return false;
	}

	if($babBody->currentAdmGroup && $topcatid == 0)
	{
		$babBody->addError(bab_translate("Access denied"));
		return false;
	}
	
	$iIdCategory = bab_addTopicsCategory($name, $description, $benabled, $template, $disptmpl, $topcatid, $babBody->currentAdmGroup);
	if(false === $iIdCategory)
	{
		return false;
	}

	$sKeyOfPhpFile			= 'categoryPicture';
	$bHaveAssociatedImage	= false;
	$bFromTempPath			= false;
	$sTempName				= (string) bab_rp('sTempImgName', '');
	$sImageName				= (string) bab_rp('sImgName', '');
	
	//Si image chargée par ajax
	if('' !== $sTempName && '' !== $sImageName)
	{
		$bHaveAssociatedImage	= true;
		$bFromTempPath			= true;
	}
	else
	{//Si image chargée par la voie normal
		if((array_key_exists($sKeyOfPhpFile, $_FILES) && '' != $_FILES[$sKeyOfPhpFile]['tmp_name']))
		{
			$bHaveAssociatedImage = true;
		}
	}	
	
	if(false === $bHaveAssociatedImage)
	{
		return $iIdCategory;
	}
		
	require_once dirname(__FILE__) . '/../utilit/artincl.php';	
	
	$oPubImpUpl	= bab_getInstance('bab_PublicationImageUploader');
	
	if(false === $bFromTempPath)
	{
		$sFullPathName = $oPubImpUpl->uploadCategoryImage($babBody->currentAdmGroup, $iIdCategory, $sKeyOfPhpFile);
	}
	else
	{		
		$sFullPathName = $oPubImpUpl->importCategoryImageFromTemp($babBody->currentAdmGroup, $iIdCategory, $sTempName, $sImageName);
	}

	if(false === $sFullPathName)
	{
		global $babDB;
		list($iIdParent) = $babDB->db_fetch_array($babDB->db_query("select id_parent from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$babDB->db_escape_string($iIdCategory)."'"));
		//Si la catégorie n'a pas été créée lors de la création d'une délégation
		if(!(!$iIdParent && $babBody->currentAdmGroup))
		{
			require_once dirname(__FILE__) . '/../utilit/delincl.php';
			bab_deleteTopicCategory($iIdCategory);
		}
		
		foreach($oPubImpUpl->getError() as $sError)
		{
			$babBody->addError($sError);
		}
		return false;
	}
	
	{
		//Insérer l'image en base
		$aPathParts		= pathinfo($sFullPathName);
		$sName			= $aPathParts['basename'];
		$sPathName		= BAB_PathUtil::addEndSlash($aPathParts['dirname']);
		$sUploadPath	= BAB_PathUtil::addEndSlash(BAB_PathUtil::sanitize($GLOBALS['babUploadPath']));
		$sRelativePath	= mb_substr($sPathName, mb_strlen($sUploadPath), mb_strlen($sFullPathName) - mb_strlen($sName));
		
		/*
		bab_debug(
			'sName         ' . $sName . "\n" .
			'sRelativePath ' . $sRelativePath
		);
		//*/
		
		bab_addImageToCategory($iIdCategory, $sName, $sRelativePath);
	}
	
	return $iIdCategory;
}

function disableTopcats($topcats, $idp)
	{
	global $babBody, $babDB;
	$req = "select id from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."' and id_parent='".$idp."'";
	$res = $babDB->db_query($req);
	while( $row = $babDB->db_fetch_array($res))
		{
		if( count($topcats) > 0 && in_array($row['id'], $topcats))
			$enabled = "N";
		else
			$enabled = "Y";

		$req = "update ".BAB_TOPICS_CATEGORIES_TBL." set enabled='".$enabled."' where id='".$row['id']."'";
		$babDB->db_query($req);
		}
	}

function disableEnableTopcat($iIdTopCat, $sEnable)
{
	global $babBody, $babDB;

	if('Y' == $sEnable || 'N' == $sEnable)
	{
		$sQuery = 'update ' . BAB_TOPICS_CATEGORIES_TBL . ' set enabled = ' . $babDB->quote($sEnable) . ' where id = ' . $babDB->quote($iIdTopCat);
		$babDB->db_query($sQuery);
	}
}

function saveOrderTopcats($idp, $listtopcats)
	{
	global $babBody, $babDB;
	
	for($i=0; $i < count($listtopcats); $i++)
		{
		$babDB->db_query("update ".BAB_TOPCAT_ORDER_TBL." set ordering='".($i+1)."' where id='".$listtopcats[$i]."'");
		}
	}
	
function getImage()
{	
	require_once dirname(__FILE__) . '/../utilit/artincl.php';
	require_once dirname(__FILE__) . '/../utilit/gdiincl.php';

	$iWidth		= (int) bab_rp('iWidth', 120);
	$iHeight	= (int) bab_rp('iHeight', 90);
	$sImage		= (string) bab_rp('sImage', '');
	$sOldImage	= (string) bab_rp('sOldImage', '');
	$oEnvObj	= bab_getInstance('bab_PublicationPathsEnv');

	global $babBody;
	$oEnvObj->setEnv($babBody->currentAdmGroup);
	$sPath = $oEnvObj->getTempPath();
	
	$oImageResize = new bab_ImageResize();
	$oImageResize->resizeImage($sPath . $sImage, $iWidth, $iHeight);

	if(file_exists($sPath . $sOldImage))
	{
		@unlink($sPath . $sOldImage);
	}
}
	
function uploadCategoryImg()
{
	global $babBody;
	require_once dirname(__FILE__) . '/../utilit/artincl.php';
	require_once dirname(__FILE__) . '/../utilit/hiddenUpload.class.php';
	
	$sJSon			= '';
	$sKeyOfPhpFile	= 'categoryPicture';
	$oPubImpUpl		= new bab_PublicationImageUploader();
	$aFileInfo		= $oPubImpUpl->uploadImageToTemp($babBody->currentAdmGroup, $sKeyOfPhpFile);
	
	if(false === $aFileInfo)
	{
		$sMessage = implode(',', $oPubImpUpl->getError());
		if('utf8' != bab_charset::getDatabase())
		{
			$sMessage = utf8_encode($sMessage);
		}
			
		$sJSon = json_encode(array(
				"success"  => false,
				"failure"  => true,
				"sMessage" => $sMessage));
	}
	else
	{
		$sMessage = implode(',', $aFileInfo);
		if('utf8' != bab_charset::getDatabase())
		{
			$sMessage = utf8_encode($sMessage);
		}
			
		$sJSon = json_encode(array(
				"success"	=> true,
				"failure"	=> false,
				"sMessage"	=> $sMessage));
	}
				
	header('Cache-control: no-cache');
	print bab_HiddenUploadForm::getHiddenIframeHtml($sJSon);		
}

function deleteTempImage()
{
	require_once dirname(__FILE__) . '/../utilit/artincl.php';
	
	$sImage		= bab_rp('sImage', '');
	$oEnvObj	= bab_getInstance('bab_PublicationPathsEnv');
	
	$oEnvObj->setEnv($babBody->currentAdmGroup);
	$sPath = $oEnvObj->getTempPath();
	
	if(file_exists($sPath . $sImage))
	{
		@unlink($sPath . $sImage);
	}
	die('');
}

/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['articles'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( !isset($idx))
	$idx = "List";

if( !isset($idp))
	$idp = 0;

if( isset($add))
	{
	$idp = $topcatid;
	if(false === addTopCat($name, $description, $benabled, $template, $disptmpl, $topcatid))
		{
			$idx = 'Create';
		}
	}
elseif( isset($update))
	{
	if( $update == 'disable' || $update == 'enable' )
	{
		disableEnableTopcat($iIdTopCat, (($update == 'enable') ? 'Y' : 'N'));
	}
	if( $update == "order" )
		{
		saveOrderTopcats($idp, $listtopcats);
		}
	}

	
switch($idx)
	{
	case 'getImage':
		getImage(); // called by ajax
		exit;
		
	case 'getHiddenUpload': // called by ajax
		getHiddenUpload();
		exit;
	
	case 'uploadCategoryImg': // called by ajax
		uploadCategoryImg();
		exit;	
	
	case 'deleteTempImage': // called by ajax
		deleteTempImage();
		exit;
		
	case "Order":
		orderTopcat($idp);
		$babBody->title = bab_translate("Order a topic category");
		
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats");
		$babBody->addItemMenu("Order", bab_translate("Order"), $GLOBALS['babUrlScript']."?tg=topcats&idx=Order&idp=".$idp);
		break;
	case "Create":
		topcatCreate($idp);
		$babBody->title = bab_translate("Create a topic category");
		
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats");
		$babBody->addItemMenu("Create", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=topcats&idx=Create&idp=".$idp);
		break;
	case "List":
	default:
		$babBody->title = bab_translate("Categories and topics");
		
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats&idx=List&idp=".$idp);
		$oArtTV = new bab_AdmArticleTreeView('oArtTV');
		$oArtTV->setAttributes(BAB_ARTICLE_TREE_VIEW_SHOW_CATEGORIES | BAB_ARTICLE_TREE_VIEW_SHOW_TOPICS | 
			BAB_TREE_VIEW_MEMORIZE_OPEN_NODES | BAB_ARTICLE_TREE_VIEW_SHOW_ROOT_NODE | BAB_ARTICLE_TREE_VIEW_HIDE_DELEGATIONS);
		$oArtTV->setAction('');
		$oArtTV->order();
		$oArtTV->sort();
		$babBody->babecho($oArtTV->printTemplate());
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>