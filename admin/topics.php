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
include_once $babInstallPath.'admin/acl.php';
include_once $babInstallPath.'utilit/topincl.php';

function addCategory($cat, $ncat, $category, $description, $saart, $sacom, $saupd, $bnotif, $atid, $disptid, $restrict, $bhpages, $bpubdates, $battachment, $bartupdate, $bmanmod, $maxarts, $bautoapp, $busetags)
	{
	global $babBody;
	class temp
		{
		var $title;
		var $description;
		var $approver;
		var $add;
		var $msie;
		var $idcat;
		var $db;
		var $count;
		var $res;
		var $selected;
		var $topcat;
		var $modcom;
		var $notiftxt;
		var $yes;
		var $no;
		var $langLabel;
		var $langValue;
		var $langselected;
		var $langFiles;
		var $countLangFiles;
		var $arttmpltxt;
		var $arttmplval;
		var $arttmplid;
		var $arttmplselected;
		var $arttmpl;
		var $atid;
		var $arrarttmpl = array();
		var $countarttmpl;
		var $disptmpltxt;
		var $disptmplval;
		var $disptmplid;
		var $disptmplselected;
		var $disptmpl;
		var $disptid;
		var $arrdisptmpl = array();
		var $countdisptmpl;
		var $restrictysel;
		var $restrictnsel;
		var $restricttxt;
		var $manmodtxt;
		var $manmodysel;
		var $manmodnsel;
		
		var $sAllowAddImg;
		var $aAllowAddImg; 
		var $sAllowAddImgItemValue;
		var $sAllowAddImgItemCaption;
		var $sSelectedAllowAddImg;
		var $sPostedAllowAddImg;
		
		var $bImageUploadEnable = false;
		var $iMaxImgFileSize;
		var $sTempImgName;
		var $sImgName;
		var $sAltImagePreview;
		var $bUploadPathValid = false;
		var $bDisplayDelImgChk = false; 
		var $bDisplayImgModifyTr = false; 
		var $bHaveAssociatedImage = false;
		var $sDisabledUploadReason;
		
		var $sSelectImageCaption;
		var $sImagePreviewCaption;
		
		var $sHiddenUploadUrl;
		var $sImageUrl = '#';
		
		function temp($cat, $ncat, $category, $description, $saart, $sacom, $saupd, $bnotif, $atid, $disptid, $restrict, $bhpages, $bpubdates, $battachment, $bartupdate, $bmanmod, $maxarts, $bautoapp, $busetags)
			{
			global $babBody, $babDB;
			$this->iMaxImgFileSize		= (int) $GLOBALS['babMaxImgFileSize'];
			$this->bUploadPathValid		= is_dir($GLOBALS['babUploadPath']);
			$this->bImageUploadEnable	= (0 !== $this->iMaxImgFileSize && $this->bUploadPathValid);
			$this->topcat				= bab_translate("Topic category");
			$this->title 				= bab_translate("Topic name");
			$this->desctitle 			= bab_translate("Description");
			$this->approver 			= bab_translate("Topic manager");
			$this->modcom 				= bab_translate("Approbation schema for comments");
			$this->modart 				= bab_translate("Approbation schema for articles");
			$this->modupd 				= bab_translate("Approbation schema for articles modification");
			$this->notiftxt 			= bab_translate("Allow author to notify group members by mail");
			$this->arttmpltxt 			= bab_translate("Article's model");
			$this->disptmpltxt 			= bab_translate("Display template");
			$this->restricttxt 			= bab_translate("Articles's authors can restrict access to articles");
			$this->hpagestxt 			= bab_translate("Allow author to propose articles for homes pages");
			$this->pubdatestxt 			= bab_translate("Allow author to specify dates of publication");
			$this->attachmenttxt 		= bab_translate("Allow author to attach files to articles");
			$this->artupdatetxt 		= bab_translate("Allow author to modify their articles");
			$this->manmodtxt 			= bab_translate("Allow managers to modify articles");
			$this->artmaxtxt 			= bab_translate("Max articles on the archives page");
			$this->yeswithapprobation	= bab_translate("Yes with approbation");
			$this->yesnoapprobation 	= bab_translate("Yes without approbation");
			$this->tagstxt				= bab_translate("Use tags");
			$this->yes					= bab_translate("Yes");
			$this->no					= bab_translate("No");
			$this->add					= bab_translate("Add");
			$this->none					= bab_translate("None");
			$this->autoapprobationtxt	= bab_translate("Automatically approve author if he belongs to approbation schema");
			$this->tgval				= "topics";
			$this->langLabel			= bab_translate("Language");
			$this->langFiles			= $GLOBALS['babLangFilter']->getLangFiles();
			$this->countLangFiles		= count($this->langFiles);
			$this->item					= "";
			$this->cat					= $cat;
			$this->bhpages				= $bhpages;
			$this->bpubdates			= $bpubdates;
			$this->battachment			= $battachment;
			$this->bartupdate			= $bartupdate;

			$this->aAllowAddImg			= array('N' => bab_translate("No"), 'Y' => bab_translate("Yes"));
			$this->sPostedAllowAddImg	= bab_rp('sAllowAddImg', 'N');
			$this->sAllowAddImg			= bab_translate("Allow authors to attach an image to an article");
			
			$this->sSelectImageCaption	= bab_translate('Select a picture');
			$this->sImagePreviewCaption	= bab_translate('Preview image');
			$this->sTempImgName			= bab_rp('sTempImgName', '');
			$this->sImgName				= bab_rp('sImgName', '');
			$this->sAltImagePreview		= bab_translate("Previlualization of the image");
			
			$this->sHiddenUploadUrl		= $GLOBALS['babUrlScript'] . '?tg=topics&idx=getHiddenUpload&cat=' . $cat;
			
			if('' != $this->sTempImgName)
			{
				$this->bHaveAssociatedImage = true;
				$this->sImageUrl = $GLOBALS['babUrlScript'] . '?tg=topics&idx=getImage&iWidth=120&iHeight=90&sImage=' . 
					$this->sTempImgName . '&cat=' . $cat;
			}
			else
			{
				$this->sImageUrl = '#';
			}
			
			$this->processDisabledUploadReason();
			
			if(empty($description))
				{
				$this->description = "";
				}
			else
				{
				$this->description = $description;
				}

			if(empty($category))
				{
				$this->category = "";
				}
			else
				{
				$this->category = $category;
				}

			if(empty($sacom))
				{
				$this->sacom = 0;
				}
			else
				{
				$this->sacom = $sacom;
				}
			if(empty($saart))
				{
				$this->saart = 0;
				}
			else
				{
				$this->saart = $saart;
				}
			if(empty($saupd))
				{
				$this->saupd = 0;
				}
			else
				{
				$this->saupd = $saupd;
				}

			$this->currentsa = $this->saart;
			if(empty($atid))
				{
				$this->atid = "";
				}
			else
				{
				$this->atid = $atid;
				}

			if(empty($disptid))
				{
				$this->disptid = "";
				}
			else
				{
				$this->disptid = $disptid;
				}

			if(empty($bautoapp))
				{
				$bautoapp = "N";
				}

			if( $bautoapp == "N")
				{
				$this->autoappnsel = 'selected="selected"';
				$this->autoappysel = "";
				}
			else
				{
				$this->autoappnsel = "";
				$this->autoappysel = 'selected="selected"';
				}

			if(empty($bnotif))
				{
				$bnotif = "N";
				}

			if( $bnotif == "N")
				{
				$this->notifnsel = 'selected="selected"';
				$this->notifysel = "";
				}
			else
				{
				$this->notifnsel = "";
				$this->notifysel = 'selected="selected"';
				}

			if(empty($restrict))
				{
				$restrict = "N";
				}

			if( $restrict == "N")
				{
				$this->restrictnsel = 'selected="selected"';
				$this->restrictysel = "";
				}
			else
				{
				$this->restrictnsel = "";
				$this->restrictysel = 'selected="selected"';
				}

			if( $bhpages == "N")
				{
				$this->hpagesnsel = 'selected="selected"';
				$this->hpagesysel = "";
				}
			else
				{
				$this->hpagesysel = 'selected="selected"';
				$this->hpagesnsel = "";
				}

			if( $busetags == "N")
				{
				$this->tagsnsel = 'selected="selected"';
				$this->tagsysel = "";
				}
			else
				{
				$this->tagsnsel = "";
				$this->tagsysel = 'selected="selected"';
				}

			switch($bartupdate)
				{
				case '1':
					$this->artupdateyasel = 'selected="selected"';
					$this->artupdateysel = "";
					$this->artupdatensel = "";
					break;
				case '2':
					$this->artupdateyasel = "";
					$this->artupdateysel = 'selected="selected"';
					$this->artupdatensel = "";
					break;
				default:
					$this->artupdateyasel = "";
					$this->artupdateysel = "";
					$this->artupdatensel = 'selected="selected"';
					break;
					break;
				}

			switch($bmanmod)
				{
				case '1':
					$this->manmodyasel = 'selected="selected"';
					$this->manmodysel = "";
					$this->manmodnsel = "";
					break;
				case '2':
					$this->manmodyasel = "";
					$this->manmodysel = 'selected="selected"';
					$this->manmodnsel = "";
					break;
				default:
					$this->manmodyasel = "";
					$this->manmodysel = "";
					$this->manmodnsel = 'selected="selected"';
					break;
					break;
				}

			if( $battachment == "N")
				{
				$this->attachmentnsel = 'selected="selected"';
				$this->attachmentysel = "";
				}
			else
				{
				$this->attachmentysel = 'selected="selected"';
				$this->attachmentnsel = "";
				}

			if( $bpubdates == "N")
				{
				$this->pubdatesnsel = 'selected="selected"';
				$this->pubdatesysel = "";
				}
			else
				{
				$this->pubdatesysel = 'selected="selected"';
				$this->pubdatesnsel = "";
				}

			if(empty($ncat))
				{
				$this->ncat = $cat;
				}
			else
				{
				$this->ncat = $ncat;
				}
			if(empty($maxarts))
				{
				$this->maxarticlesval = 10;
				}
			else
				{
				$this->maxarticlesval = $maxarts;
				}

			$this->bdel = false;
			
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			$editor = new bab_contentEditor('bab_topic');
			$editor->setContent($this->description);
			$editor->setFormat('html');
			$editor->setParameters(array('height' => 150));
			$this->editor = $editor->getEditor();
			
			$this->idcat = $cat;

			$req = "select * from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."'";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			$req = "select * from ".BAB_FLOW_APPROVERS_TBL." where id_dgowner='".$babBody->currentAdmGroup."' order by name asc";
			$this->sares = $babDB->db_query($req);
			if( !$this->sares )
				{
				$this->sacount = 0;
				}
			else
				{
				$this->sacount = $babDB->db_num_rows($this->sares);
				}
			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";

			$file = "articlestemplate.html";
			$filepath = "skins/".$GLOBALS['babSkin']."/templates/". $file;
			if( !file_exists( $filepath ) )
				{
				$filepath = $GLOBALS['babSkinPath']."templates/". $file;
				if( !file_exists( $filepath ) )
					{
					$filepath = $GLOBALS['babInstallPath']."skins/ovidentia/templates/". $file;
					}
				}
			if( file_exists( $filepath ) )
				{
				$tpl = new babTemplate();
				$arr = $tpl->getTemplates($filepath);
				for( $i=0; $i < count($arr); $i++)
					{
					if( mb_strpos($arr[$i], "head_") !== false ||  mb_strpos($arr[$i], "body_") !== false )
						if( count($this->arrarttmpl) == 0  || !in_array(mb_substr($arr[$i], 5), $this->arrarttmpl ))
							$this->arrarttmpl[] = mb_substr($arr[$i], 5);
					}
				}
			$this->countarttmpl = count($this->arrarttmpl);

			$file = "topicsdisplay.html";
			$filepath = "skins/".$GLOBALS['babSkin']."/templates/". $file;
			if( !file_exists( $filepath ) )
				{
				$filepath = $GLOBALS['babSkinPath']."templates/". $file;
				if( !file_exists( $filepath ) )
					{
					$filepath = $GLOBALS['babInstallPath']."skins/ovidentia/templates/". $file;
					}
				}
			if( file_exists( $filepath ) )
				{
				$tpl = new babTemplate();
				$arr = $tpl->getTemplates($filepath);
				for( $i=0; $i < count($arr); $i++)
					{
					if( mb_strpos($arr[$i], "head_") !== false ||  mb_strpos($arr[$i], "body_") !== false )
						if( count($this->arrdisptmpl) == 0  || !in_array(mb_substr($arr[$i], 5), $this->arrdisptmpl ))
							$this->arrdisptmpl[] = mb_substr($arr[$i], 5);
					}
				}
			$this->countdisptmpl = count($this->arrdisptmpl);
			}
			
		function getNextAllowAddImgItem()
		{
			$this->sSelectedAllowAddImg = '';
			
			$aDatas = each($this->aAllowAddImg);
			if(false !== $aDatas)
			{			 
				$this->sAllowAddImgItemValue = $aDatas['key'];
				$this->sAllowAddImgItemCaption = $aDatas['value'];
				if($this->sAllowAddImgItemValue == $this->sPostedAllowAddImg)
				{
					$this->sSelectedAllowAddImg = 'selected="selected"';
				}
				return true;
			}
			return false;
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
					$this->bHaveAssociatedImage = false;
					$this->sDisabledUploadReason .= '<LI>'. bab_translate("The upload path is not set");
				}
				else if(!is_dir($GLOBALS['babUploadPath']))
				{
					$this->bHaveAssociatedImage = false;
					$this->sDisabledUploadReason .= '<LI>'. bab_translate("The upload path is not a dir");
				}
				
				if(0 == $this->iMaxImgFileSize)
				{
					$this->bHaveAssociatedImage = false;
					$this->sDisabledUploadReason .= '<LI>'. bab_translate("The maximum size for a defined image is zero byte");
				}
				$this->sDisabledUploadReason .= '</UL>';
			}
		}
			
		function getnextcat()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->arr = $babDB->db_fetch_array($this->res);
				$this->toptitle = $this->arr['title'];
				$this->topid = $this->arr['id'];
				if( $this->arr['id'] == $this->ncat )
					$this->topselected = 'selected="selected"';
				else
					$this->topselected = "";
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextschapp()
			{
			global $babDB;
			static $i = 0, $k=0;
			if( $i < $this->sacount)
				{
				$arr = $babDB->db_fetch_array($this->sares);
				$this->saname = $arr['name'];
				$this->said = $arr['id'];
				if( $this->said == $this->currentsa )
					{
					$this->sasel = 'selected="selected"';
					}
				else
					{
					$this->sasel = "";
					}
				$i++;
				return true;
				}
			else
				{
				if( $this->sacount > 0 )
					{
					$babDB->db_data_seek($this->sares, 0);
					}
				if($k==0 )
					{
					$this->currentsa = $this->sacom;
					$k++;
					}
				else
					{
					$this->currentsa = $this->sacom;
					}
				$i = 0;
				return false;
				}
			}
			
		function getnextlang()
			{
			static $i = 0;
			if($i < $this->countLangFiles)
				{
				$this->langValue = $this->langFiles[$i];
				if($this->langValue == $GLOBALS['babLanguage'])
					{
					$this->langselected = 'selected="selected"';
					}
				else
					{
					$this->langselected = '';
					}
				$i++;
				return true;
				}
			return false;
			}

		function getnextarttmpl()
			{
			static $i = 0;
			if($i < $this->countarttmpl)
				{
				$this->arttmplid = $this->arrarttmpl[$i];
				$this->arttmplval = $this->arrarttmpl[$i];
				if( $this->arttmplid == $this->atid )
					$this->arttmplselected = 'selected="selected"';
				else
					$this->arttmplselected = "";
				$i++;
				return true;
				}
			return false;
			}

		function getnextdisptmpl()
			{
			static $i = 0;
			if($i < $this->countdisptmpl)
				{
				$this->disptmplid = $this->arrdisptmpl[$i];
				$this->disptmplval = $this->arrdisptmpl[$i];
				if( $this->disptmplid == $this->disptid )
					$this->disptmplselected = 'selected="selected"';
				else
					$this->disptmplselected = "";
				$i++;
				return true;
				}
			return false;
			}
		}
	
	$babBody->addStyleSheet('publication.css');
		
	$temp = new temp($cat, $ncat, $category, $description, $saart, $sacom, $saupd, $bnotif, $atid, $disptid, $restrict, $bhpages, $bpubdates, $battachment, $bartupdate, $bmanmod, $maxarts, $bautoapp, $busetags);
	$babBody->babecho(	bab_printTemplate($temp,"topics.html", "categorycreate"));
	}

function listCategories($cat)
	{
	global $babBody;
	class temp
		{
		
		var $id;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $select;
		var $approver;
		var $urlcategory;
		var $namecategory;
		var $articles;
		var $urlarticles;
		var $nbarticles;
		var $idcat;
		var $rights;
		var $urlrights;
		var $description;

		function temp($cat)
			{
			global $babBody, $babDB, $BAB_SESS_USERID;
			$this->rights = bab_translate("Rights");
			$this->articles = bab_translate("Article") ."(s)";
			$req = "select id_topcat from ".BAB_TOPCAT_ORDER_TBL." where type='2' and id_parent='".$cat."' order by ordering asc";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			$this->idcat = $cat;
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				if( $i == 0)
					$this->select = "checked";
				else
					$this->select = "";
				$arr = $babDB->db_fetch_array($this->res);
					
				$this->arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_TOPICS_TBL." where id=".$babDB->quote($arr['id_topcat'])));
				
				include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
				$editor = new bab_contentEditor('bab_topic');
				$editor->setContent($this->arr['description']);
				$this->description = $editor->getHtml();
				
				$this->urlrights = $GLOBALS['babUrlScript']."?tg=topic&idx=rights&item=".$this->arr['id']."&cat=".$this->idcat;
				$this->arr['description'] = $this->arr['description'];;
				$this->urlcategory = $GLOBALS['babUrlScript']."?tg=topic&idx=Modify&item=".$this->arr['id']."&cat=".$this->idcat;
				$this->namecategory = $this->arr['category'];
				$req = "select count(*) as total from ".BAB_ARTICLES_TBL." where id_topic='".$this->arr['id']."' and archive='N'";
				$res = $babDB->db_query($req);
				$arr2 = $babDB->db_fetch_array($res);
				$this->nbarticles = $arr2['total'];
				$this->urlarticles = $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$this->arr['id']."&cat=".$this->idcat;
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$temp = new temp($cat);
	$babBody->babecho(	bab_printTemplate($temp,"topics.html", "categorylist"));
	return $temp->count;
	}

function saveCategory($category, $cat, $sacom, $saart, $saupd, $bnotif, $lang, $atid, $disptid, $restrict, $bhpages, $bpubdates, $battachment, $bartupdate, $bmanmod, $maxarts, $bautoapp, $busetags, $sAllowAddImg)
{
	global $babBody, $babDB;
	if(empty($category))
	{
		$babBody->msgerror = bab_translate("ERROR: You must provide a topic name !!");
		return false;
	}

	if($busetags == 'Y')
	{
		list($count) = $babDB->db_fetch_array($babDB->db_query("select count(id) from ".BAB_TAGS_TBL.""));
		if($count == 0)
		{
			$babBody->msgerror = bab_translate("ERROR: You can't use tags. List tags is empty");
			return false;
		}
	}
	else
	{
		$busetags = 'N';
	}

	$arrTopic = array(	'idsaart'=> $saart, 
						'idsacom'=> $sacom, 
						'idsa_update'=> $saupd, 
						'notify'=> $bnotif, 
						'lang'=>$lang, 
						'article_tmpl'=>$atid, 
						'display_tmpl'=>$disptid, 
						'restrict_access'=>$restrict, 
						'allow_hpages'=>$bhpages,
						'allow_pubdates'=>$bpubdates,
						'allow_attachments'=>$battachment,
						'allow_update'=>$bartupdate,
						'allow_manupdate'=>$bmanmod,
						'max_articles'=>$maxarts,
						'auto_approbation'=>$bautoapp,
						'busetags'=>$busetags,
						'allow_addImg'=>$sAllowAddImg
					);
	
	require_once dirname(__FILE__) . '/../utilit/editorincl.php';
	$editor = new bab_contentEditor('bab_topic');
	$description = $editor->getContent();
	$error = '';
	$iIdTopic = bab_addTopic($category, $description, $cat, $error, $arrTopic);

	if(false === $iIdTopic)
	{
		$babBody->addError($error);
		return false;
	}


	$sKeyOfPhpFile			= 'topicPicture';
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
		return $iIdTopic;
	}
		
	require_once dirname(__FILE__) . '/../utilit/artincl.php';	
	
	$oPubImpUpl	= bab_getInstance('bab_PublicationImageUploader');
	
	if(false === $bFromTempPath)
	{
		$sFullPathName = $oPubImpUpl->uploadTopicImage($babBody->currentAdmGroup, $iIdTopic, $sKeyOfPhpFile);
	}
	else
	{		
		$sFullPathName = $oPubImpUpl->importTopicImageFromTemp($babBody->currentAdmGroup, $iIdTopic, $sTempName, $sImageName);
	}

	if(false === $sFullPathName)
	{
		require_once dirname(__FILE__) . '/../utilit/delincl.php';
		bab_confirmDeleteTopic($iIdTopic);
		
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
		
		bab_addImageToTopic($iIdTopic, $sName, $sRelativePath);
	}
	
	return $iIdTopic;
}

function getHiddenUpload()
{
	require_once $GLOBALS['babInstallPath'].'utilit/hiddenUpload.class.php';
	
	$oHiddenForm = new bab_HiddenUploadForm();
	
	$oHiddenForm->addHiddenField('cat', bab_rp('cat', 0));
	$oHiddenForm->addHiddenField('tg', 'topics');
	$oHiddenForm->addHiddenField('MAX_FILE_SIZE', $GLOBALS['babMaxImgFileSize']);
	$oHiddenForm->addHiddenField('idx', 'uploadTopicImg');
	
	header('Cache-control: no-cache');
	die($oHiddenForm->getHtml());
}

	
function uploadTopicImg()
{
	global $babBody;
	require_once dirname(__FILE__) . '/../utilit/artincl.php';
	require_once dirname(__FILE__) . '/../utilit/hiddenUpload.class.php';
	
	$sJSon			= '';
	$sKeyOfPhpFile	= 'topicPicture';
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
if(!$babBody->isSuperAdmin && $babBody->currentDGGroup['articles'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

$idx = bab_rp('idx', 'list');
$cat = intval(bab_rp('cat', 0));

if(isset($_POST['add']))
{
	$sAllowAddImg = bab_rp('sAllowAddImg', 'N');
	if(!saveCategory($category, $ncat, $sacom, $saart, $saupd, $bnotif, $lang, $atid, $disptid, $restrict, $bhpages, $bpubdates, $battachment, $bartupdate, $bmanmod, $maxarts, $bautoapp, $busetags, $sAllowAddImg))
	{
		$idx = 'addtopic';
	}
	else
	{
		$cat = $ncat;
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=topcats");
		exit;
	}
}

if( !$cat )
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

if( !isset($idp))
{
	list($idp) = $babDB->db_fetch_row($babDB->db_query("select id_parent from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$cat."'"));
}

switch($idx)
	{
	case 'getImage':
		getImage(); // called by ajax
		exit;
		
	case 'getHiddenUpload': // called by ajax
		getHiddenUpload();
		exit;
	
	case 'uploadTopicImg': // called by ajax
		uploadTopicImg();
		exit;	
	
	case 'deleteTempImage': // called by ajax
		deleteTempImage();
		exit;

	case "addtopic":
		$babBody->title = bab_translate("Create new topic");
		$ncat = bab_pp('ncat');
		$category = bab_pp('category');
		$topdesc = bab_pp('topdesc');
		$saart = bab_pp('saart');
		$sacom = bab_pp('sacom');
		$saupd = bab_pp('saupd');
		$bnotif = bab_pp('bnotif');
		$atid = bab_pp('atid');
		$disptid = bab_pp('disptid');
		$restrict = bab_pp('restrict');
		$bhpages = bab_pp('bhpages', 'N');
		$bpubdates = bab_pp('bpubdates', 'N');
		$battachment = bab_pp('battachment', 'N');
		$bartupdate = bab_pp('bartupdate', 'N');
		$bautoapp = bab_pp('bautoapp', 'N');
		$bmanmod = bab_pp('bmanmod', 'N');
		$maxarts = bab_pp('maxarts', 10);
		$busetags = bab_pp('busetags', 'N');
		addCategory($cat, $ncat, $category, $topdesc, $saart, $sacom, $saupd, $bnotif, $atid, $disptid, $restrict, $bhpages, $bpubdates, $battachment, $bartupdate, $bmanmod, $maxarts, $bautoapp, $busetags);
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats&idx=List&idp=".$idp);
		$babBody->addItemMenu("addtopic", bab_translate("Create"), $GLOBALS['babUrlScript']."?tg=topics&idx=addtopic&cat=".$cat);
		break;

	case "list":
	default:
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=topcats");
		exit;
	}
$babBody->setCurrentItemMenu($idx);

?>