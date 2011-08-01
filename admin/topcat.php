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
require_once "base.php";
require_once dirname(__FILE__).'/../utilit/registerglobals.php';
require_once dirname(__FILE__) . '/acl.php';
require_once dirname(__FILE__) . '/../utilit/topincl.php';

function topcatModify($id)
{
	global $babBody;
	if(!isset($id))
	{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid topic category !!");
		return;
	}
	
	class tempa
	{
		var $name;
		var $description;
		var $no;
		var $yes;
		var $noselected;
		var $yesselected;
		var $modify;
		var $delete;
		
		var $db;
		var $arr = array();
		var $res;

		var $arrtmpl;
		var $counttmpl;
		var $templatetxt;
		var $templateval;
		var $templateid;
		var $tmplselected;
		var $disptmpltxt;
		var $topcattxt;
		var $topcatid;
		var $topcatval;
		var $nonetxt;

		var $iMaxImgFileSize;
		var $bImageUploadEnable = false;
		var $bHaveAssociatedImage = false;
		var $bUploadPathValid = false;
		var $sDeleteImageChecked = '';
		
		var $sPostedTmpl;
		var $sPostedDispTmpl;
		
		var $sSelectImageCaption;
		var $sImagePreviewCaption;
		
		var $sTempImgName;
		var $sImgName;
		var $sImageUrl;
		var $sAltImagePreview;
		var $sDeleteImageCaption;
		var $sDisabledUploadReason;
		var $sImageModifyMessage;
		
		function tempa($id)
		{
			global $babBody, $babDB;
			$this->iMaxImgFileSize		= (int) $GLOBALS['babMaxImgFileSize'];
			$this->bUploadPathValid		= is_dir($GLOBALS['babUploadPath']);
			$this->bImageUploadEnable	= (0 !== $this->iMaxImgFileSize && $this->bUploadPathValid);
			$this->name					= bab_translate("Name");
			$this->description			= bab_translate("Description");
			$this->enabled				= bab_translate("Section enabled");
			$this->no					= bab_translate("No");
			$this->yes					= bab_translate("Yes");
			$this->modify				= bab_translate("Modify");
			$this->delete				= bab_translate("Delete");
			$this->templatetxt			= bab_translate("Section template");
			$this->disptmpltxt			= bab_translate("Display template");
			$this->topcattxt			= bab_translate("Topics category parent");
			$this->nonetxt				= "--- ".bab_translate("None")." ---";
			$this->db					= $GLOBALS['babDB'];
			$req						= 'SELECT * FROM ' . BAB_TOPICS_CATEGORIES_TBL . ' WHERE id= \'' . $id . '\'';
			$this->res					= $this->db->db_query($req);
			$this->arr					= $this->db->db_fetch_array($this->res);
			$this->arr['title']			= bab_toHtml(bab_rp('title', $this->arr['title']));
			$this->arr['description']	= bab_toHtml(bab_rp('description', $this->arr['description']));
			$this->idp					= bab_rp('topcatid', $this->arr['id_parent']);
			$this->sPostedTmpl			= bab_rp('template', $this->arr['template']);
			$this->sPostedDispTmpl		= bab_rp('disptmpl', $this->arr['display_tmpl']);
			$this->sSelectImageCaption	= bab_translate('Select a picture');
			$this->sImagePreviewCaption	= bab_translate('Preview image');
			$this->sAltImagePreview		= bab_translate("Previsualization of the image");
			$this->sTempImgName			= bab_rp('sTempImgName', '');
			$this->sImgName				= bab_rp('sImgName', '');
			$this->sDeleteImageChecked	= (bab_rp('deleteImageChk', 0) == 0) ? '' : 'checked="checked"';
			$this->sDeleteImageCaption	= bab_translate('Remove image');
			$this->sImageModifyMessage	= bab_translate('Changes affecting the image will be taken into account after having saved');
			
			
			//Si on ne vient pas d'un post alors r�cup�rer l'image
			if(!array_key_exists('sImgName', $_POST))
			{
				$aImageInfo	= bab_getImageCategory($id);
				if(false !== $aImageInfo)
				{
					$this->sImgName = $aImageInfo['name'];
					$this->bHaveAssociatedImage = true;
				}
			}
			
			$this->processDisabledUploadReason();
			
			if('' != $this->sTempImgName)
			{
				$this->sImageUrl = $GLOBALS['babUrlScript'] . '?tg=topcat&idx=getImage&iWidth=120&iHeight=90&sImage=' . 
					$this->sTempImgName;
			}
			else if('' != $this->sImgName)
			{
				$this->sImageUrl = $GLOBALS['babUrlScript'] . '?tg=topcat&idx=getImage&iWidth=120&iHeight=90&sImage=' . 
					bab_toHtml($this->sImgName) . '&iIdCategory=' . $id;
			}
			else
			{
				$this->sImageUrl = '#';
			}
			
			
			if($this->idp == 0 && $babBody->currentAdmGroup)
			{
				$this->bdelete = false;
			}
			else
			{
				$this->bdelete = true;
			}

			$sEnabled = bab_rp('benabled', $this->arr['enabled']);
			if($sEnabled == "Y")
			{
				$this->noselected = "";
				$this->yesselected = 'selected="selected"';
			}
			else
			{
				$this->noselected = 'selected="selected"';
				$this->yesselected = "";
			}

			$file		= 'topicssection.html';
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
			
			/* Parent category */
			$arr_exclude = $this->arr_child($id);

			$res = $babDB->db_query("select * from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."' and id NOT IN(".implode(',',$arr_exclude).") order by title asc");

			$this->arrtopcats = array();
			if($this->idp == 0 || ($this->idp != 0 && $babBody->isSuperAdmin))
			{
				$this->arrtopcats[] = array( 'id'=> 0, 'title' => $this->nonetxt);
			}
			
			while($arr = $babDB->db_fetch_array($res))
			{
				$this->arrtopcats[] = array( 'id'=> $arr['id'], 'title' => $arr['title']);
			}
			$this->topcatscount = count($this->arrtopcats);
			
			/* Tree view popup when javascript is activated
			   !!!!! : it's the same popup when we are in delegation's administration */
			global $babSkinPath;
			$this->idcategory = $id;
			$this->urlimgselectcategory = $babSkinPath.'images/nodetypes/category.png';
			$this->idcurrentparentcategory = $this->idp;
			$this->namecurrentparentcategory = '';
			for ($i=0;$i<=count($this->arrtopcats)-1;$i++) {
				if ($this->arrtopcats[$i]['id'] == $this->idp) {
					$this->namecurrentparentcategory = $this->arrtopcats[$i]['title'];
				}
			}
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
					$this->bHaveAssociatedImage = false;
				}
				else if(!is_dir($GLOBALS['babUploadPath']))
				{
					$this->sDisabledUploadReason .= '<LI>'. bab_translate("The upload path is not a dir");
					$this->bHaveAssociatedImage = false;
				}
				
				if(0 == $this->iMaxImgFileSize)
				{
					$this->sDisabledUploadReason .= '<LI>'. bab_translate("The maximum size for a defined image is zero byte");
				}
				$this->sDisabledUploadReason .= '</UL>';
			}
		}
		
		function arr_child($id)
		{
			$out[] = $id;
			global $babDB;
			$res = $babDB->db_query("SELECT id FROM ".BAB_TOPICS_CATEGORIES_TBL." WHERE id_parent='".$id."'");
			while($arr = $babDB->db_fetch_array($res))
			{
				$add = $this->arr_child($arr['id']);
				if(is_array($add))
				{
					$out = array_merge($out, $add);
				}
			}
			return $out;
		}

		function getnexttemplate()
		{
			static $i = 0;
			if($i < $this->counttmpl)
			{
				$this->templateid = $this->arrtmpl[$i];
				$this->templateval = $this->arrtmpl[$i];
				if($this->templateid == $this->sPostedTmpl)
				{
					$this->tmplselected = 'selected="selected"';
				}
				else
				{
					$this->tmplselected = "";
				}
				$i++;
				return true;
			}
			return false;
		}

		function getnextdisptemplate()
		{
			static $i = 0;
			if($i < $this->countdisptmpl)
			{
				$this->templateid = $this->arrdisptmpl[$i];
				$this->templateval = $this->arrdisptmpl[$i];
				if($this->templateid == $this->sPostedDispTmpl)
				{
					$this->tmplselected = 'selected="selected"';
				}
				else
				{
					$this->tmplselected = "";
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
				if($this->topcatid == $this->arr['id_parent'])
				{
					$this->tmplselected = 'selected="selected"';
				}
				else
				{
					$this->tmplselected = "";
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
	
	global $babBody, $babScriptPath;
	$babBody->addStyleSheet('publication.css');
	$babBody->addJavascriptFile($babScriptPath.'prototype/prototype.js');
	$babBody->addJavascriptFile($babScriptPath.'bab_dialog.js');
	
	$temp = new tempa($id);
	$babBody->babecho(bab_printTemplate($temp, 'topcats.html', 'topcatmodify'));
}


function topcatDelete($id, $idp)
	{
	global $babBody;
	
	class temp
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;

		function temp($id, $idp)
			{
			$this->message = bab_translate("Are you sure you want to delete this topic category");
			$this->title = bab_getTopicCategoryTitle($id);
			$this->warning = bab_translate("WARNING: This operation will delete the topic category with all references"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=topcat&idx=Delete&group=".$id."&action=Yes"."&idp=".$idp;
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=topcats";
			$this->no = bab_translate("No");
			}
		}

	if( $idp == 0 && $babBody->currentAdmGroup)
		{
		$babBody->msgerror = bab_translate("This topic category can't be deleted");
		return false;
		}

	$db = $GLOBALS['babDB'];
	$r = $db->db_fetch_array($db->db_query("select count(*) as total from ".BAB_TOPICS_TBL." where id_cat='".$id."'"));
	if( $r['total'] > 0 )
		{
		$babBody->msgerror = bab_translate("To delete topic category, you must delete topics before");
		return false;
		}

	$temp = new temp($id, $idp);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	return true;
	}

function getHiddenUpload()
{
	require_once $GLOBALS['babInstallPath'].'utilit/hiddenUpload.class.php';
	
	$oHiddenForm = new bab_HiddenUploadForm();
	
	$oHiddenForm->addHiddenField('tg', 'topcat');
	$oHiddenForm->addHiddenField('idx', 'uploadCategoryImg');
	$oHiddenForm->addHiddenField('MAX_FILE_SIZE', $GLOBALS['babMaxImgFileSize']);
	$oHiddenForm->addHiddenField('iIdCategory', bab_rp('iIdCategory', 0));
	
	die($oHiddenForm->getHtml());
}
	
function getImage()
{	
	require_once dirname(__FILE__) . '/../utilit/artincl.php';
	require_once dirname(__FILE__) . '/../utilit/gdiincl.php';

	$iWidth			= (int) bab_rp('iWidth', 0);
	$iHeight		= (int) bab_rp('iHeight', 0);
	$sImage			= (string) bab_rp('sImage', '');
	$sOldImage		= (string) bab_rp('sOldImage', '');
	$iIdCategory	= (int) bab_rp('iIdCategory', 0);
	
	$oEnvObj		= bab_getInstance('bab_PublicationPathsEnv');

	global $babBody;
	$oEnvObj->setEnv($babBody->currentAdmGroup);
	
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
		if('utf8' == bab_charset::getDatabase())
		{
			$sMessage = utf8_encode($sMessage);
		}
		
		/*	
		$sJSon = json_encode(array(
				"success"  => false,
				"failure"  => true,
				"sMessage" => $sMessage));
		//*/
		$sJSon = '{"success":"false", "failure":"true", "sMessage":"' . $sMessage . '"}';
	}
	else
	{
		$sMessage = implode(',', $aFileInfo);
		if('utf8' == bab_charset::getDatabase())
		{
			$sMessage = utf8_encode($sMessage);
		}

		/*
		$sJSon = json_encode(array(
				"success"	=> true,
				"failure"	=> false,
				"sMessage"	=> $sMessage));
		//*/
		$sJSon = '{"success":"true", "failure":"false", "sMessage":"' . $sMessage . '"}';
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

function howToUseDefaultRights($id)
	{
	global $babBody;
	
	class temp
		{
		function temp($id)
			{
			$this->item = $id;
			$this->t_with_selected = bab_translate("On which elements do you want to set those new rights").'?';
			$this->title = bab_getTopicCategoryTitle($id);

			$this->t_topics = bab_translate("Topics of this category");
			$this->t_subcategories = bab_translate("Subcategories first level");
			$this->t_topicssubcategories = bab_translate("Topics of subcategories first level");
			$this->t_all = bab_translate("All children ( subcategories and topics )");
			$this->t_update = bab_translate("Apply");
			}
		}

	$temp = new temp($id);
	$babBody->babecho(bab_printTemplate($temp,"topcats.html", "howtousedefaultrights"));
	return true;
	}
	
function modifyTopcat($oldname, $name, $description, $benabled, $id, $template, $disptmpl, $topcatid)
{
	global $babBody;

	if(empty($name))
	{
		$babBody->msgerror = bab_translate("ERROR: You must provide a name !!");
		return false;
	}

	$db = $GLOBALS['babDB'];
	$query = "select * from ".BAB_TOPICS_CATEGORIES_TBL." where 
		title='".$db->db_escape_string($name)."' 
		and id!='".$db->db_escape_string($id)."' 
		and id_parent='".$db->db_escape_string($topcatid)."' 
		and id_dgowner='".$db->db_escape_string($babBody->currentAdmGroup)."'";
	$res = $db->db_query($query);
	if($db->db_num_rows($res) > 0)
	{
		$babBody->msgerror = bab_translate("This topic category already exists");
		return false;
	}
	else
	{
		$arr = $db->db_fetch_array($db->db_query("select id_parent from ".BAB_TOPICS_CATEGORIES_TBL." where id ='".$db->db_escape_string($id)."'"));

		$query = "update ".BAB_TOPICS_CATEGORIES_TBL." set 
			title='".$db->db_escape_string($name)."', 
			description='".$db->db_escape_string($description)."', 
			enabled='".$db->db_escape_string($benabled)."', 
			template='".$db->db_escape_string($template)."', 
			display_tmpl='".$db->db_escape_string($disptmpl)."', 
			id_parent='".$db->db_escape_string($topcatid)."' 
			where id='".$db->db_escape_string($id)."'
		";
		$db->db_query($query);

		if($arr['id_parent'] != $topcatid)
		{
			$res = $db->db_query("select max(ordering) from ".BAB_TOPCAT_ORDER_TBL." where id_parent='".$db->db_escape_string($topcatid)."'");
			$arr = $db->db_fetch_array($res);
			if( isset($arr[0]))
				$ord = $arr[0] + 1;
			else
				$ord = 1;
			$db->db_query("update ".BAB_TOPCAT_ORDER_TBL." set id_parent='".$db->db_escape_string($topcatid)."', ordering='".$db->db_escape_string($ord)."' where id_topcat='".$db->db_escape_string($id)."' and type='1'");
		}
	}

	//Image

	$iIdCategory			= $id;
	$sKeyOfPhpFile			= 'categoryPicture';
	$bHaveAssociatedImage	= false;
	$bFromTempPath			= false;
	$sTempName				= (string) bab_rp('sTempImgName', '');
	$sImageName				= (string) bab_rp('sImgName', '');
	
	//Si image charg�e par ajax
	if('' !== $sTempName && '' !== $sImageName)
	{
		$bHaveAssociatedImage	= true;
		$bFromTempPath			= true;
	}
	else
	{//Si image charg�e par la voie normal
		if((array_key_exists($sKeyOfPhpFile, $_FILES) && '' != $_FILES[$sKeyOfPhpFile]['tmp_name']))
		{
			$bHaveAssociatedImage = true;
		}
	}	

	require_once dirname(__FILE__) . '/../utilit/artincl.php';
	
	$oPubPathsEnv = new bab_PublicationPathsEnv();
	
	if(false === $bHaveAssociatedImage)
	{
		//Aucune image n'est associ�e alors on supprime celle qui �tait associ�e avant
		//si on a cliqu� sur supprim�(ajax) ou coch� supprimer (javascript d�sactiv�)
		if(('' === $sTempName && '' === $sImageName) || bab_rp('deleteImageChk', 0) != 0)
		{
			if($oPubPathsEnv->setEnv($babBody->currentAdmGroup))
			{
				require_once dirname(__FILE__) . '/../utilit/delincl.php';
				bab_deleteUploadDir($oPubPathsEnv->getCategoryImgPath($iIdCategory));
				bab_deleteImageCategory($iIdCategory);
			}
		}
		return $iIdCategory;
	}
	
	
	//Une image est associ�e alors on supprime l'ancienne
	if($oPubPathsEnv->setEnv($babBody->currentAdmGroup))
	{
		require_once dirname(__FILE__) . '/../utilit/delincl.php';
		bab_deleteUploadDir($oPubPathsEnv->getCategoryImgPath($iIdCategory));
		bab_deleteImageCategory($iIdCategory);
	}
	
	$oPubImpUpl	= bab_getInstance('bab_PublicationImageUploader');
	if(false === $bFromTempPath)
	{
		$sFullPathName = $oPubImpUpl->uploadCategoryImage($babBody->currentAdmGroup, $iIdCategory, $sKeyOfPhpFile);
	}
	else
	{		
		$sFullPathName = $oPubImpUpl->importCategoryImageFromTemp($babBody->currentAdmGroup, $iIdCategory, $sTempName, $sImageName);
	}
	
	{
		//Ins�rer l'image en base
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


function confirmDeleteTopcat($id)
	{
	global $babBody, $babDB;

	list($idparent) = $babDB->db_fetch_array($babDB->db_query("select id_parent from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$babDB->db_escape_string($id)."'"));
	if( !$idparent && $babBody->currentAdmGroup)
		{
		$babBody->msgerror = bab_translate("This topic category can't be deleted");
		return false;
		}

	require_once dirname(__FILE__) . '/../utilit/delincl.php';
	$idp = bab_deleteTopicCategory($id);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=topcats");
	}

function updateDefaultRightsTopics($idcatsrc, $idcat)
{
	global $babDB;

	$res = $babDB->db_query("select id from ".BAB_TOPICS_TBL." where id_cat='".$babDB->db_escape_string($idcat)."'");
	while( $arr = $babDB->db_fetch_array($res))
	{
		aclCloneRights(BAB_DEF_TOPCATVIEW_GROUPS_TBL, $idcatsrc, BAB_TOPICSVIEW_GROUPS_TBL, $arr['id']);
		aclCloneRights(BAB_DEF_TOPCATSUB_GROUPS_TBL, $idcatsrc, BAB_TOPICSSUB_GROUPS_TBL, $arr['id']);
		aclCloneRights(BAB_DEF_TOPCATCOM_GROUPS_TBL, $idcatsrc, BAB_TOPICSCOM_GROUPS_TBL, $arr['id']);
		aclCloneRights(BAB_DEF_TOPCATMOD_GROUPS_TBL, $idcatsrc, BAB_TOPICSMOD_GROUPS_TBL, $arr['id']);
		aclCloneRights(BAB_DEF_TOPCATMAN_GROUPS_TBL, $idcatsrc, BAB_TOPICSMAN_GROUPS_TBL, $arr['id']);
	}
}

function updateDefaultRightsSubCategory($idcat, $idchild)
{
	aclCloneRights(BAB_DEF_TOPCATVIEW_GROUPS_TBL, $idcat, BAB_DEF_TOPCATVIEW_GROUPS_TBL, $idchild);
	aclCloneRights(BAB_DEF_TOPCATSUB_GROUPS_TBL, $idcat, BAB_DEF_TOPCATSUB_GROUPS_TBL, $idchild);
	aclCloneRights(BAB_DEF_TOPCATCOM_GROUPS_TBL, $idcat, BAB_DEF_TOPCATCOM_GROUPS_TBL, $idchild);
	aclCloneRights(BAB_DEF_TOPCATMOD_GROUPS_TBL, $idcat, BAB_DEF_TOPCATMOD_GROUPS_TBL, $idchild);
	aclCloneRights(BAB_DEF_TOPCATMAN_GROUPS_TBL, $idcat, BAB_DEF_TOPCATMAN_GROUPS_TBL, $idchild);
}

function updateDefaultRightsChild($item)
{
	global $babDB;

	$res = $babDB->db_query("select id from ".BAB_TOPICS_CATEGORIES_TBL." where id_parent='".$babDB->db_escape_string($item)."'");
	while( $arr = $babDB->db_fetch_array($res))
	{
		updateDefaultRightsSubCategory($item, $arr['id']);
		updateDefaultRightsTopics($arr['id'], $arr['id']);
		updateDefaultRightsChild($arr['id']);
	}
}

function updateDefaultRights($item, $opt)
{
	global $babDB;
	
	if( in_array(4, $opt )) // All child
	{
		updateDefaultRightsTopics($item, $item);
		updateDefaultRightsChild($item);
	}
	else
	{
		if( in_array(1, $opt)) // Topics of this category
		{
			updateDefaultRightsTopics($item, $item);
		}
		if( in_array(2, $opt)) // Subcategories first level
		{
			$res = $babDB->db_query("select id from ".BAB_TOPICS_CATEGORIES_TBL." where id_parent='".$babDB->db_escape_string($item)."'");
			while( $arr = $babDB->db_fetch_array($res))
			{
				updateDefaultRightsSubCategory($item, $arr['id']);
			}
		}
		if( in_array(3, $opt)) // Topics of subcategories first level
		{
			$res = $babDB->db_query("select id from ".BAB_TOPICS_CATEGORIES_TBL." where id_parent='".$babDB->db_escape_string($item)."'");
			while( $arr = $babDB->db_fetch_array($res))
			{
				updateDefaultRightsTopics($item, $arr['id']);
			}
		}
	}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=topcats");
}


function updateAclGroups()
{
	global $babDB;

	maclGroups();
	$item = bab_pp('item', '');

	list($nbcat) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_TOPICS_CATEGORIES_TBL." where id_parent='".$babDB->db_escape_string($item)."'"));

	list($nbtop) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_TOPICS_TBL." where id_cat='".$babDB->db_escape_string($item)."'"));

	if( $nbcat || $nbtop )
	{
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=topcat&idx=crights&item=".$item);
		exit;
	}
	
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=topcats");
	exit;
}

/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['articles'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}


$iNbSeconds = 2 * 86400; //2 jours
require_once dirname(__FILE__) . '/../utilit/artincl.php';
bab_PublicationImageUploader::deleteOutDatedTempImage($iNbSeconds);


$idx = bab_rp('idx', 'Modify');

if( isset($modify))
	{
	if( isset($submit))
	{
		if(false !== modifyTopcat($oldname, $title, $description, $benabled, $item, $template, $disptmpl, $topcatid))
		{
			bab_sitemap::clearAll();
			Header("Location: ". $GLOBALS['babUrlScript'] . '?tg=topcats');
			exit;
		}
		else
		{
			$idx = 'Modify';
		}
	}
	else if( isset($catdel))
		$idx = "Delete";
	}

if( isset($action))
	{
	switch($action)
		{
		case 'Yes':
			if($idx == "Delete")
				{
				confirmDeleteTopcat($group);
				}
			break;
		case 'updrights':
			$opt = bab_pp('opt', array());
			updateDefaultRights($item, $opt);
			Header("Location: ". $GLOBALS['babUrlScript']."?tg=topcats");
			break;
		}
	}

if( isset($aclview) )
	{
	updateAclGroups();
	}

	
switch($idx)
	{
	case 'getImage':
		getImage(); // called by ajax
		exit;
		
	case 'getHiddenUpload': // called by ajax
		getHiddenUpload();
		break;
	
	case 'uploadCategoryImg': // called by ajax
		uploadCategoryImg();
		exit;	
	
	case 'deleteTempImage': // called by ajax
		deleteTempImage();
		exit;
		
		break;
	case 'crights':
		howToUseDefaultRights($item);
		$babBody->title = bab_translate("Default rights for").": ".bab_getTopicCategoryTitle($item);
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats");
		$babBody->addItemMenu("rights", bab_translate("Default rights"), $GLOBALS['babUrlScript']."?tg=topcat&idx=rights&item=".$item);
		break;
	case 'rights':
		$babBody->title = bab_translate("Default rights for").": ".bab_getTopicCategoryTitle($item);
		$macl = new macl("topcat", "crights", $item, "aclview");
        $macl->addtable( BAB_DEF_TOPCATVIEW_GROUPS_TBL,bab_translate("Who can read articles ?"));
        $macl->addtable( BAB_DEF_TOPCATSUB_GROUPS_TBL,bab_translate("Who can submit new articles ?"));
		$macl->addtable( BAB_DEF_TOPCATCOM_GROUPS_TBL,bab_translate("Who can post comment ?"));
		$macl->addtable( BAB_DEF_TOPCATMOD_GROUPS_TBL,bab_translate("Who can modify articles ?"));
        $macl->addtable( BAB_DEF_TOPCATMAN_GROUPS_TBL,bab_translate("Who can manage ?"));
		$macl->filter(0,0,1,1,1);
        $macl->babecho();
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats");
		$babBody->addItemMenu("rights", bab_translate("Default rights"), $GLOBALS['babUrlScript']."?tg=topcat&idx=rights&item=".$item);
		break;
	case 'Delete':
		if(topcatDelete($item, $idp))
			{
			$babBody->title = bab_translate("Delete topic category");
			$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats&idx=List&idp=".$idp);
			$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=topcat&idx=Delete&item=".$item);
			break;
			}
		/* no break; */
		$idx = 'Modify';
	case 'Modify':
	default:
		topcatModify($item);
		$babBody->title = bab_translate("Modify topic category");
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats&idx=List&idp=".$idp);
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=topcat&idx=Modify&item=".$item."&idp=".$idp);
		break;
	}

$babBody->setCurrentItemMenu($idx);

?>