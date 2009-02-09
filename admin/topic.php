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
include_once $babInstallPath."admin/acl.php";
include_once $babInstallPath."utilit/mailincl.php";
include_once $babInstallPath."utilit/topincl.php";
include_once $babInstallPath."utilit/artincl.php";

function listArticles($id)
	{
	global $babBody;

	class temp
		{
		var $title;
		var $titlename;
		var $articleid;
		var $item;
		var $checkall;
		var $uncheckall;
		var $urltitle;

		var $db;
		var $res;
		var $count;

		var $siteid;
		var $badmin;
		var $homepages;
		var $homepagesurl;
		var $checked0;
		var $checked1;
		var $deletealt;
		var $art0alt;
		var $art1alt;
		var $deletehelp;
		var $art0help;
		var $art1help;
		var $bshowhpg;

		function temp($id)
			{
			global $babBody;

			$this->titlename = bab_translate("Title");
			$this->uncheckall = bab_translate("Uncheck all");
			$this->checkall = bab_translate("Check all");
			$this->deletealt = bab_translate("Delete articles");
			$this->art0alt = bab_translate("Make available to unregistered users home page");
			$this->art1alt = bab_translate("Make available to registered users home page");
			$this->deletehelp = bab_translate("Click on this image to delete selected articles");
			$this->art0help = bab_translate("Click on this image to make selected articles available to unregistered users home page");
			$this->art1help = bab_translate("Click on this image to make selected articles available to registered users home page");
			$this->homepages = bab_translate("Customize home pages ( Registered and unregistered users )");
			$this->badmin = bab_isUserAdministrator();

			$this->item = $id;
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='".$id."' and archive='N' order by date desc";
			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->siteid = $babBody->babsite['id'];
			$this->homepagesurl = $GLOBALS['babUrlScript']."?tg=topman&idx=hpriv&ids=".$babBody->babsite['id'];
			$this->bshowhpg = bab_isAccessValid(BAB_SITES_HPMAN_GROUPS_TBL,1);
			}

		function getnext()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $this->db->db_fetch_array($this->res);
				$req = "select * from ".BAB_HOMEPAGES_TBL." where id_article='".$arr['id']."' and id_group='2' and id_site='".$this->siteid."'";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					$this->checked0 = "checked";
				else
					$this->checked0 = "";
				$req = "select * from ".BAB_HOMEPAGES_TBL." where id_article='".$arr['id']."' and id_group='1' and id_site='".$this->siteid."'";
				$res = $this->db->db_query($req);
				if( $res && $this->db->db_num_rows($res) > 0)
					$this->checked1 = "checked";
				else
					$this->checked1 = "";
				$this->title = $arr['title'];
				$this->articleid = $arr['id'];
				$this->urltitle = $GLOBALS['babUrlScript']."?tg=topic&idx=viewa&item=".$arr['id'];
				$i++;
				return true;
				}
			else
				return false;

			}
		
		}

	$temp = new temp($id);
	$babBody->babecho(	bab_printTemplate($temp,"topics.html", "articleslist"));
	}

function deleteArticles($art, $item)
	{
	global $babBody, $idx;

	class tempa
		{
		var $warning;
		var $message;
		var $title;
		var $urlyes;
		var $urlno;
		var $yes;
		var $no;

		function tempa($art, $item)
			{
			global $BAB_SESS_USERID;
			$this->message = bab_translate("Are you sure you want to delete those articles");
			$this->title = "";
			$items = "";
			$db = $GLOBALS['babDB'];
			for($i = 0; $i < count($art); $i++)
				{
				$req = "select * from ".BAB_ARTICLES_TBL." where id='".$art[$i]."'";	
				$res = $db->db_query($req);
				if( $db->db_num_rows($res) > 0)
					{
					$arr = $db->db_fetch_array($res);
					$this->title .= "<br>". $arr['title'];
					$items .= $arr['id'];
					}
				if( $i < count($art) -1)
					$items .= ",";
				}
			$this->warning = bab_translate("WARNING: This operation will delete articles and their comments"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=topic&idx=Deletea&item=".$item."&action=Yes&items=".$items;
			$this->yes = bab_translate("Yes");
			$this->urlno = $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$item;
			$this->no = bab_translate("No");
			}
		}

	if( count($item) <= 0)
		{
		$babBody->msgerror = bab_translate("Please select at least one item");
		listArticles($item);
		$idx = "Articles";
		return;
		}
	$tempa = new tempa($art, $item);
	$babBody->babecho(	bab_printTemplate($tempa,"warning.html", "warningyesno"));
	}

function modifyCategory($id, $cat, $category, $description, $saart, $sacom, $saupd, $bnotif, $atid, $disptid, $restrict, $bhpages, $bpubdates, $battachment, $bartupdate, $bmanmod, $maxarts, $bautoapp, $busetags)
	{
	global $babBody;
	if( !isset($id))
		{
		$babBody->msgerror = bab_translate("ERROR: You must choose a valid category !!");
		return;
		}
	class temp
		{
		var $category;
		var $description;
		var $add;
		var $langLabel;
		var $langValue;
		var $langselected;
		var $langFiles;
		var $countLangFiles;

		var $db;
		var $arr = array();
		var $arr2 = array();
		var $res;
		var $msie;
		var $count;
		var $topcat;
		var $modcom;
		var $yes;
		var $no;
		var $yesselected;
		var $noselected;
		var $delete;

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
		var $bDisplayDelImgChk = true; 
		var $bDisplayImgModifyTr = true; 
		var $bHaveAssociatedImage = false;
		var $sDisabledUploadReason;
		
		var $sDeleteImageCaption;
		var $sImageModifyMessage;
		
		var $sSelectImageCaption;
		var $sImagePreviewCaption;
		
		var $sHiddenUploadUrl;
		var $sImageUrl = '#';
		
		function temp($id, $cat, $category, $description, $saart, $sacom, $saupd, $bnotif, $atid, $disptid, $restrict, $bhpages, $bpubdates, $battachment, $bartupdate, $bmanmod, $maxarts, $bautoapp, $busetags)
			{
			global $babBody;
			$this->iMaxImgFileSize		= (int) $GLOBALS['babMaxImgFileSize'];
			$this->bUploadPathValid		= is_dir($GLOBALS['babUploadPath']);
			$this->bImageUploadEnable	= (0 !== $this->iMaxImgFileSize && $this->bUploadPathValid);
			$this->topcat				= bab_translate("Topic category");
			$this->title 				= bab_translate("Topic name");
			$this->desctitle 			= bab_translate("Description");
			$this->modcom 				= bab_translate("Approbation schema for comments");
			$this->modart 				= bab_translate("Approbation schema for articles");
			$this->modupd 				= bab_translate("Approbation schema for articles modification");
			$this->notiftxt 			= bab_translate("Allow author to notify group members by mail");
			$this->hpagestxt 			= bab_translate("Allow author to propose articles for homes pages");
			$this->pubdatestxt 			= bab_translate("Allow author to specify dates of publication");
			$this->attachmenttxt 		= bab_translate("Allow author to attach files to articles");
			$this->artupdatetxt 		= bab_translate("Allow author to modify their articles");
			$this->manmodtxt 			= bab_translate("Allow managers to modify articles");
			$this->artmaxtxt 			= bab_translate("Max articles on the archives page");
			$this->yes 					= bab_translate("Yes");
			$this->no 					= bab_translate("No");
			$this->add 					= bab_translate("Update Topic");
			$this->none 				= bab_translate("None");
			$this->delete 				= bab_translate("Delete");
			$this->arttmpltxt 			= bab_translate("Article's model");
			$this->disptmpltxt 			= bab_translate("Display template");
			$this->restricttxt 			= bab_translate("Articles's authors can restrict access to articles");
			$this->yeswithapprobation 	= bab_translate("Yes with approbation");
			$this->yesnoapprobation 	= bab_translate("Yes without approbation");
			$this->autoapprobationtxt 	= bab_translate("Automatically approve author if he belongs to approbation schema");
			$this->tagstxt 				= bab_translate("Use tags");
			$this->tgval 				= "topic";
			$this->item 				= $id;
			$this->langLabel 			= bab_translate("Language");
			$this->langFiles 			= $GLOBALS['babLangFilter']->getLangFiles();
			$this->countLangFiles 		= count($this->langFiles);

			$this->aAllowAddImg			= array('N' => bab_translate("No"), 'Y' => bab_translate("Yes"));
			$this->sAllowAddImg			= bab_translate("Allow authors to attach an image to an article");
			
			$this->sSelectImageCaption	= bab_translate('Select a picture');
			$this->sImagePreviewCaption	= bab_translate('Preview image');
			$this->sTempImgName			= bab_rp('sTempImgName', '');
			$this->sImgName				= bab_rp('sImgName', '');
			$this->sAltImagePreview		= bab_translate("Previlualization of the image");
			$this->sDeleteImageChecked	= (bab_rp('deleteImageChk', 0) == 0) ? '' : 'checked="checked"';
			$this->sDeleteImageCaption	= bab_translate('Remove image');
			$this->sImageModifyMessage	= bab_translate('Changes affecting the image will be taken into account after having saved');
			
			$this->sHiddenUploadUrl		= $GLOBALS['babUrlScript'] . '?tg=topic&idx=getHiddenUpload&iIdTopic=' . $id . '&item=' . $id . '&cat=' . $cat;
			
			//Si on ne vient pas d'un post alors récupérer l'image
			if(!array_key_exists('sImgName', $_POST))
			{
				$aImageInfo	= bab_getImageTopic($id);
				if(false !== $aImageInfo)
				{
					$this->sImgName = $aImageInfo['name'];
					$this->bHaveAssociatedImage = true;
				}
			}
			
			if('' != $this->sTempImgName)
			{
				$this->bHaveAssociatedImage = true;
				$this->sImageUrl = $GLOBALS['babUrlScript'] . '?tg=topic&idx=getImage&iWidth=120&iHeight=90&sImage=' . 
					$this->sTempImgName . '&item=' . $id . '&cat=' . $cat;
			}
			else if('' != $this->sImgName)
			{
				$this->sImageUrl = $GLOBALS['babUrlScript'] . '?tg=topic&idx=getImage&iWidth=120&iHeight=90&sImage=' . 
					$this->sImgName . '&iIdTopic=' . $id . '&item=' . $id . '&cat=' . $cat;
			}
			else
			{
				$this->sImageUrl = '#';
			}
			
			$this->processDisabledUploadReason();
			
			
			
			
			
			
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_TOPICS_TBL." where id='".$id."'";
			$res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($res);

			
			$this->sPostedAllowAddImg = bab_rp('sAllowAddImg', $this->arr['allow_addImg']);
			
			
			$this->cat = $this->arr['id_cat'];

			if (empty($cat)) {
				$this->ncat = $this->arr['id_cat'];
			} else {
				$this->ncat = $cat;
			}
			if(empty($description))
				{
				$this->description = $this->arr['description'];
				}
			else
				{
				$this->description = $description;
				}
			if(empty($category))
				{
				$this->category = bab_toHtml($this->arr['category']);
				}
			else
				{
				$this->category = $category;
				}

			if(empty($sacom))
				{
				$this->sacom = $this->arr['idsacom'];
				}
			else
				{
				$this->sacom = $sacom;
				}
			if(empty($saart))
				{
				$this->saart = $this->arr['idsaart'];
				}
			else
				{
				$this->saart = $saart;
				}
			if(empty($saupd))
				{
				$this->saupd = $this->arr['idsa_update'];
				}
			else
				{
				$this->saupd = $saupd;
				}

			$this->currentsa = $this->saart;

			if(empty($bautoapp))
				{
				$bautoapp = $this->arr['auto_approbation'];
				}

			if( $bautoapp == "N")
				{
				$this->autoappnsel = "selected";
				$this->autoappysel = "";
				}
			else
				{
				$this->autoappnsel = "";
				$this->autoappysel = "selected";
				}
			
			if(empty($bnotif))
				{
				$bnotif = $this->arr['notify'];
				}

			if( $bnotif == "N")
				{
				$this->notifnsel = "selected";
				$this->notifysel = "";
				}
			else
				{
				$this->notifnsel = "";
				$this->notifysel = "selected";
				}

			if(empty($restrict))
				{
				$restrict = $this->arr['restrict_access'];
				}

			if( $restrict == "N")
				{
				$this->restrictnsel = "selected";
				$this->restrictysel = "";
				}
			else
				{
				$this->restrictnsel = "";
				$this->restrictysel = "selected";
				}

			if(empty($bhpages))
				{
				$bhpages = $this->arr['allow_hpages'];
				}

			if( $bhpages == "N")
				{
				$this->hpagesnsel = "selected";
				$this->hpagesysel = "";
				}
			else
				{
				$this->hpagesysel = "selected";
				$this->hpagesnsel = "";
				}

			if(empty($bpubdates))
				{
				$bpubdates = $this->arr['allow_pubdates'];
				}

			if( $bpubdates == "N")
				{
				$this->pubdatesnsel = "selected";
				$this->pubdatesysel = "";
				}
			else
				{
				$this->pubdatesysel = "selected";
				$this->pubdatesnsel = "";
				}

			if(empty($battachment))
				{
				$battachment = $this->arr['allow_attachments'];
				}

			if( $battachment == "N")
				{
				$this->attachmentnsel = "selected";
				$this->attachmentysel = "";
				}
			else
				{
				$this->attachmentysel = "selected";
				$this->attachmentnsel = "";
				}

			if(empty($busetags))
				{
				$busetags = $this->arr['busetags'];
				}

			if( $busetags == "N")
				{
				$this->tagsnsel = "selected";
				$this->tagsysel = "";
				}
			else
				{
				$this->tagsnsel = "";
				$this->tagsysel = "selected";
				}


			if(empty($bartupdate))
				{
				$bartupdate = $this->arr['allow_update'];
				}

			switch($bartupdate)
				{
				case '1':
					$this->artupdateyasel = "selected";
					$this->artupdateysel = "";
					$this->artupdatensel = "";
					break;
				case '2':
					$this->artupdateyasel = "";
					$this->artupdateysel = "selected";
					$this->artupdatensel = "";
					break;
				default:
					$this->artupdateyasel = "";
					$this->artupdateysel = "";
					$this->artupdatensel = "selected";
					break;
					break;
				}

			if(empty($bmanmod))
				{
				$bmanmod = $this->arr['allow_manupdate'];
				}

			switch($bmanmod)
				{
				case '1':
					$this->manmodyasel = "selected";
					$this->manmodysel = "";
					$this->manmodnsel = "";
					break;
				case '2':
					$this->manmodyasel = "";
					$this->manmodysel = "selected";
					$this->manmodnsel = "";
					break;
				default:
					$this->manmodyasel = "";
					$this->manmodysel = "";
					$this->manmodnsel = "selected";
					break;
					break;
				}

			if(empty($atid))
				{
				$this->atid = $this->arr['article_tmpl'];
				}
			else
				{
				$this->atid = $atid;
				}

			if(empty($disptid))
				{
				$this->disptid = $this->arr['display_tmpl'];
				}
			else
				{
				$this->disptid = $disptid;
				}

			if(empty($maxarts))
				{
				$this->maxarticlesval = $this->arr['max_articles'];
				}
			else
				{
				$this->maxarticlesval = $maxarts;
				}
			$this->bdel = true;
			
			/* Parent category */
			global $babDB;
			$req = "select * from ".BAB_TOPICS_CATEGORIES_TBL." where id_dgowner='".$babBody->currentAdmGroup."'";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			$this->array_parent_categories = array();
			for ($i=0;$i<=$this->count-1;$i++) {
				$this->array_parent_categories[] = $babDB->db_fetch_assoc($this->res);
			}
			
			/* Tree view popup when javascript is activated */
			global $babSkinPath;
			$this->urlimgselectcategory = $babSkinPath.'images/nodetypes/category.png';
			$this->idcurrentparentcategory = $this->ncat;
			$this->namecurrentparentcategory = '';
			for ($i=0;$i<=count($this->array_parent_categories)-1;$i++) {
				if ($this->array_parent_categories[$i]['id'] == $this->cat) {
					$this->namecurrentparentcategory = $this->array_parent_categories[$i]['title'];
				}
			}

			$req = "select * from ".BAB_FLOW_APPROVERS_TBL." where id_dgowner='".$babBody->currentAdmGroup."' order by name asc";
			$this->sares = $this->db->db_query($req);
			if( !$this->sares )
				{
				$this->sacount = 0;
				}
			else
				{
				$this->sacount = $this->db->db_num_rows($this->sares);
				}

			$this->usersbrowurl = $GLOBALS['babUrlScript']."?tg=users&idx=brow&cb=";

			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			$editor = new bab_contentEditor('bab_topic');
			$editor->setContent($this->description);
			$editor->setFormat('html');
			$editor->setParameters(array('height' => 150));
			$this->editor = $editor->getEditor();

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
		
		function getnextcat()
			{
			static $i = 0;
			if( $i < $this->count)
				{
				$this->toptitle = $this->array_parent_categories[$i]['title'];
				$this->topid = $this->array_parent_categories[$i]['id'];
				if( $this->array_parent_categories[$i]['id'] == $this->ncat )
					$this->topselected = "selected";
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
			static $i = 0;
			static $j = 0;
			if( $i < $this->sacount)
				{
				$arr = $this->db->db_fetch_array($this->sares);
				$this->saname = $arr['name'];
				$this->said = $arr['id'];
				if( $this->said == $this->currentsa )
					{
					$this->sasel = "selected";
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
					$this->db->db_data_seek($this->sares, 0);
					}
				if($j==0 )
					{
					$this->currentsa = $this->sacom;
					}
				else
					{
					$this->currentsa = $this->saupd;
					}
				$i = 0;
				$j++;
				return false;
				}
			}
			
		function getnextlang()
			{
			static $i = 0;
			if($i < $this->countLangFiles)
				{
				$this->langValue = $this->langFiles[$i];
				if($this->langValue == $this->arr['lang'])
					{
					$this->langselected = 'selected';
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
					$this->arttmplselected = "selected";
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
					$this->disptmplselected = "selected";
				else
					$this->disptmplselected = "";
				$i++;
				return true;
				}
			return false;
			}

		}
	
	$babBody->addStyleSheet('publication.css');
		
	$temp = new temp($id, $cat, $category, $description, $saart, $sacom, $saupd, $bnotif, $atid, $disptid, $restrict, $bhpages, $bpubdates, $battachment, $bartupdate, $bmanmod, $maxarts, $bautoapp, $busetags);
	$babBody->babecho(	bab_printTemplate($temp,"topics.html", "topiccreate"));
	}

function deleteCategory($id, $cat)
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
		var $topics;
		var $article;

		function temp($id, $cat)
			{
			$this->message = bab_translate("Are you sure you want to delete this topic");
			$this->title = bab_getCategoryTitle($id);
			$this->warning = bab_translate("WARNING: This operation will delete the topic, articles and comments"). "!";
			$this->urlyes = $GLOBALS['babUrlScript']."?tg=topic&idx=Delete&category=".$id."&action=Yes&cat=".$cat;
			$this->yes = bab_translate("Yes");
//			$this->urlno = $GLOBALS['babUrlScript']."?tg=topic&idx=Modify&item=".$id;
			$this->urlno = $GLOBALS['babUrlScript'] . '?tg=topcats';
			$this->no = bab_translate("No");
			}
		}

	$temp = new temp($id, $cat);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function viewArticle($article)
	{
	global $babBody;

	class temp
		{
	
		var $content;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $more;
		var $topics;
		var $baCss;
		var $close;
		var $head;
		var $sContent;

		function temp($article)
			{
			$this->babCss	= bab_printTemplate($this,"config.html", "babCss");
			$this->close	= bab_translate("Close");
			$this->db		= $GLOBALS['babDB'];
			$req			= "select * from ".BAB_ARTICLES_TBL." where id='$article'";
			$this->res		= $this->db->db_query($req);
			$this->arr		= $this->db->db_fetch_array($this->res);
			$this->sContent	= 'text/html; charset=' . bab_charset::getIso();
			
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			
			$editor = new bab_contentEditor('bab_article_body');
			$editor->setContent($this->arr['body']);
			$this->content = $editor->getHtml();
			
			$editor = new bab_contentEditor('bab_article_head');
			$editor->setContent($this->arr['head']);
			$this->head = $editor->getHtml();

			}
		}
	
	$temp = new temp($article);
	echo bab_printTemplate($temp,"topics.html", "articleview");
	}

function warnRestrictionArticle($topics)
	{
	global $babBody;

	class tempw
		{
	
		var $warningtxt;
		var $wdisplay;

		function tempw($topics)
			{
			global $babDB;
			$this->wdisplay = false;
			list($acc) = $babDB->db_fetch_row($babDB->db_query("select restrict_access from ".BAB_TOPICS_TBL." where id='".$topics."'"));
			if( $acc == 'N' )
				{
				$res = $babDB->db_query("select id from ".BAB_ARTICLES_TBL." where id_topic='".$topics."' and restriction!=''");
				if( $res && $babDB->db_num_rows($res) > 0 )
					$this->wdisplay = true;
				}
			else
				$this->wdisplay = true;

			$this->warningtxt = bab_translate("WARNING! Some articles uses access restriction. Changing access topic can make them inaccessible");
			}
		}
	
	$temp = new tempw($topics);
	$babBody->babecho( bab_printTemplate($temp,"topics.html", "articlewarning"));
	}

function updateCategory($id, $category, $cat, $saart, $sacom, $saupd, $bnotif, $lang, $atid, $disptid, $restrict, $bhpages, $bpubdates,$battachment, $bartupdate, $bmanmod, $maxarts, $bautoapp, $busetags, $sAllowAddImg)
	{
	include_once $GLOBALS['babInstallPath']."utilit/afincl.php";
	global $babBody;
	if( empty($category))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a topic name !!");
		return false;
		}

	$db = &$GLOBALS['babDB'];

	if( $busetags == 'Y' )
		{
		list($count) = $db->db_fetch_array($db->db_query("select count(id) from ".BAB_TAGS_TBL.""));
		if( $count == 0 )
			{
			$babBody->msgerror = bab_translate("ERROR: You can't use tags. List tags is empty");
			return false;
			}
		}
	else
		{
		$busetags = 'N';
		}
		
		
	include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
	$editor = new bab_contentEditor('bab_topic');
	$description = $editor->getContent();


	if( empty($maxarts))
		{
		$maxarts = 10;
		}
	
	$arr = $db->db_fetch_array($db->db_query("select * from ".BAB_TOPICS_TBL." where id='".$db->db_escape_string($id)."'"));
	if( $arr['idsaart'] != $saart )
		{
		$res = $db->db_query("select id, idfai from ".BAB_ART_DRAFTS_TBL." where id_topic='".$db->db_escape_string($id)."' and result='".BAB_ART_STATUS_WAIT."'");
		while( $row = $db->db_fetch_array($res))
			{
			if( $row['idfai'] != 0 )
				{
				deleteFlowInstance($row['idfai']);
				}

			if( $saart != 0 )
				{
				if( $bautoapp == 'Y' )
					{
					$idfai = makeFlowInstance($saart, "draft-".$row['id'], $GLOBALS['BAB_SESS_USERID']);
					}
				else
					{
					$idfai = makeFlowInstance($saart, "draft-".$row['id']);
					}
				}

			if( $saart == 0 || $idfai === true)
				{
				$db->db_query("update ".BAB_ART_DRAFTS_TBL." set idfai='0' where id='".$row['id']."'");
				$articleid = acceptWaitingArticle($row['id']);
				if( $articleid != 0)
					{
					notifyArticleDraftAuthor($row['id'], 1);
					bab_deleteArticleDraft($row['id']);
					}
				}
			elseif(!empty($idfai))
				{
				$db->db_query("update ".BAB_ART_DRAFTS_TBL." set idfai='".$idfai."' where id='".$row['id']."'");
				$nfusers = getWaitingApproversFlowInstance($idfai, true);
				notifyArticleDraftApprovers($row['id'], $nfusers);
				}

			}
		}

	if( $arr['idsacom'] != $sacom )
		{
		$res = $db->db_query("select id, idfai from ".BAB_COMMENTS_TBL." where id_topic='".$db->db_escape_string($id)."' and confirmed='N'");
		while( $row = $db->db_fetch_array($res))
			{
			if( $row['idfai'] != 0 )
				{
				deleteFlowInstance($row['idfai']);
				}


			if( $sacom != 0 )
				{
				if( $bautoapp == 'Y' )
					{
					$idfai = makeFlowInstance($saart, "com-".$row['id'], $GLOBALS['BAB_SESS_USERID']);
					}
				else
					{
					$idfai = makeFlowInstance($saart, "com-".$row['id']);
					}
				}

			if( $sacom == 0 || $idfai === true)
				{
				$db->db_query("update ".BAB_COMMENTS_TBL." set idfai='0', confirmed = 'Y' where id='".$row['id']."'");
				}
			elseif(!empty($idfai))
				{
				$db->db_query("update ".BAB_COMMENTS_TBL." set idfai='".$idfai."' where id='".$row['id']."'");
				$nfusers = getWaitingApproversFlowInstance($idfai, true);
				notifyCommentApprovers($row['id'], $nfusers);
				}
			}
		}

	if( $arr['idsa_update'] != $saupd )
	{
		$res = $db->db_query("select id, idfai from ".BAB_ART_DRAFTS_TBL." where id_topic='".$db->db_escape_string($id)."' and result='".BAB_ART_STATUS_WAIT."'");
		while( $row = $db->db_fetch_array($res))
			{
			if( $row['idfai'] != 0 )
				{
				deleteFlowInstance($row['idfai']);
				}


			if( $saupd != 0 )
				{
				if( $bautoapp == 'Y' )
					{
					$idfai = makeFlowInstance($saupd, "draft-".$row['id'], $GLOBALS['BAB_SESS_USERID']);
					}
				else
					{
					$idfai = makeFlowInstance($saupd, "draft-".$row['id']);
					}
				}

			if( $saupd == 0 || $idfai === true)
				{
				$articleid = acceptWaitingArticle($row['id']);
				if( $articleid != 0)
					{
					bab_deleteArticleDraft($row['id']);
					}
				}
			elseif(!empty($idfai))
				{
				$db->db_query("update ".BAB_ART_DRAFTS_TBL." set idfai='".$idfai."' where id='".$row['id']."'");
				$nfusers = getWaitingApproversFlowInstance($idfai, true);
				notifyArticleDraftApprovers($row['id'], $nfusers);
				}
			}
	}

	if ((isset($GLOBALS['babApplyLanguageFilter']) && $GLOBALS['babApplyLanguageFilter'] == 'loose') and ($lang != $arr['lang']) and ($lang != '*'))
	{
		$query = "update ".BAB_ARTICLES_TBL." set lang='*' where id_topic='".$db->db_escape_string($id)."'";
		$db->db_query($query);
	}

	$query = "UPDATE ".BAB_TOPICS_TBL." SET 
		category='".$db->db_escape_string($category)."', 
		description='".$db->db_escape_string($description)."', 
		id_cat='".$db->db_escape_string($cat)."', 
		idsaart='".$db->db_escape_string($saart)."', 
		idsacom='".$db->db_escape_string($sacom)."', 
		idsa_update='".$db->db_escape_string($saupd)."', 
		notify='".$db->db_escape_string($bnotif)."', 
		lang='".$db->db_escape_string($lang)."', 
		article_tmpl='".$db->db_escape_string($atid)."', 
		display_tmpl='".$db->db_escape_string($disptid)."', 
		restrict_access='".$db->db_escape_string($restrict)."', 
		allow_hpages='".$db->db_escape_string($bhpages)."', 
		allow_pubdates='".$db->db_escape_string($bpubdates)."', 
		allow_attachments='".$db->db_escape_string($battachment)."', 
		allow_update='".$db->db_escape_string($bartupdate)."', 
		allow_manupdate='".$db->db_escape_string($bmanmod)."', 
		max_articles='".$db->db_escape_string($maxarts)."', 
		auto_approbation='".$db->db_escape_string($bautoapp)."', 
		busetags='".$db->db_escape_string($busetags)."',
		allow_addImg='".$db->db_escape_string($sAllowAddImg)."' 
	WHERE 
		id = '".$id."'";
	$db->db_query($query);

	if( $arr['id_cat'] != $cat )
		{
		$res = $db->db_query("select max(ordering) from ".BAB_TOPCAT_ORDER_TBL." where id_parent='".$db->db_escape_string($cat)."'");
		$arr = $db->db_fetch_array($res);
		if( isset($arr[0]))
			$ord = $arr[0] + 1;
		else
			$ord = 1;
		$db->db_query("update ".BAB_TOPCAT_ORDER_TBL." set id_parent='".$db->db_escape_string($cat)."', ordering='".$ord."' where id_topcat='".$db->db_escape_string($id)."' and type='2'");
		}
		
		
		

	//Image

	$iIdTopic				= $id;
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

	require_once dirname(__FILE__) . '/../utilit/artincl.php';
	
	$oPubPathsEnv = new bab_PublicationPathsEnv();
	
	if(false === $bHaveAssociatedImage)
	{
		//Aucune image n'est associée alors on supprime celle qui était associée avant
		//si on a cliqué sur supprimé(ajax) ou coché supprimer (javascript désactivé)
		if(('' === $sTempName && '' === $sImageName) || bab_rp('deleteImageChk', 0) != 0)
		{
			if($oPubPathsEnv->setEnv($babBody->currentAdmGroup))
			{
				require_once dirname(__FILE__) . '/../utilit/delincl.php';
				bab_deleteUploadDir($oPubPathsEnv->getTopicImgPath($iIdTopic));
				bab_deleteImageTopic($iIdTopic);
			}
		}
		return $iIdTopic;
	}
	
	
	//Une image est associée alors on supprime l'ancienne
	if($oPubPathsEnv->setEnv($babBody->currentAdmGroup))
	{
		require_once dirname(__FILE__) . '/../utilit/delincl.php';
		bab_deleteUploadDir($oPubPathsEnv->getTopicImgPath($iIdTopic));
		bab_deleteImageTopic($iIdTopic);
	}
	
	$oPubImpUpl	= bab_getInstance('bab_PublicationImageUploader');
	if(false === $bFromTempPath)
	{
		$sFullPathName = $oPubImpUpl->uploadTopicImage($babBody->currentAdmGroup, $iIdTopic, $sKeyOfPhpFile);
	}
	else
	{		
		$sFullPathName = $oPubImpUpl->importTopicImageFromTemp($babBody->currentAdmGroup, $iIdTopic, $sTempName, $sImageName);
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


function addToHomePages($item, $homepage, $art)
{
	global $babBody, $idx;

	$idx = "Articles";
	$count = count($art);

	$db = $GLOBALS['babDB'];

	$idsite = $babBody->babsite['id'];

	$req = "select * from ".BAB_ARTICLES_TBL." where id_topic='".$item."' order by date desc";
	$res = $db->db_query($req);
	while( $arr = $db->db_fetch_array($res))
		{
		if( $count > 0 && in_array($arr['id'], $art))
			{
				$req = "select * from ".BAB_HOMEPAGES_TBL." where id_article='".$arr['id']."' and id_group='".$homepage."' and id_site='".$idsite."'";
				$res2 = $db->db_query($req);
				if( !$res2 || $db->db_num_rows($res2) < 1)
				{
					$req = "insert into ".BAB_HOMEPAGES_TBL." (id_article, id_site, id_group) values ('" .$arr['id']. "', '" . $idsite. "', '" . $homepage. "')";
					$db->db_query($req);
				}
			}
		else
			{
				$req = "delete from ".BAB_HOMEPAGES_TBL." where id_article='".$arr['id']."' and id_group='".$homepage."' and id_site='".$idsite."'";
				$db->db_query($req);
			}

		}
}

	
function getImage()
{	
	require_once dirname(__FILE__) . '/../utilit/artincl.php';
	require_once dirname(__FILE__) . '/../utilit/gdiincl.php';

	$iWidth			= (int) bab_rp('iWidth', 0);
	$iHeight		= (int) bab_rp('iHeight', 0);
	$sImage			= (string) bab_rp('sImage', '');
	$sOldImage		= (string) bab_rp('sOldImage', '');
	$iIdTopic		= (int) bab_rp('iIdTopic', 0);
	
	$oEnvObj		= bab_getInstance('bab_PublicationPathsEnv');

	global $babBody;
	$oEnvObj->setEnv($babBody->currentAdmGroup);
	
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


function getHiddenUpload()
{
	require_once $GLOBALS['babInstallPath'].'utilit/hiddenUpload.class.php';
	
	$oHiddenForm = new bab_HiddenUploadForm();
	
	$oHiddenForm->addHiddenField('cat', bab_rp('cat', 0));
	$oHiddenForm->addHiddenField('tg', 'topic');
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
		if('utf8' != bab_charset::getDatabase())
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



/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['articles'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}


$iNbSeconds = 2 * 86400; //2 jours
require_once dirname(__FILE__) . '/../utilit/artincl.php';
bab_PublicationImageUploader::deleteOutDatedTempImage($iNbSeconds);


if(!isset($idx))
	{
	$idx = "Modify";
	}
	
	
if(!isset($cat))
	{
	$db = $GLOBALS['babDB'];
	$r = $db->db_fetch_array($db->db_query("select * from ".BAB_TOPICS_TBL." where id='".$item."'"));
	$cat = $r['id_cat'];
	}

if( isset($add) )
	{
	if( isset($submit))
	{
		$sAllowAddImg = bab_rp('sAllowAddImg', 'N');
		if(!updateCategory($item, $category, $ncat, $saart, $sacom, $saupd, $bnotif, $lang, $atid, $disptid, $restrict, $bhpages, $bpubdates,$battachment, $bartupdate, $bmanmod, $maxarts, $bautoapp, $busetags, $sAllowAddImg))
		{
			$idx = "Modify";
		}
		else
		{
			bab_sitemap::clearAll();
			Header("Location: ". $GLOBALS['babUrlScript'] . '?tg=topcats');
			exit;
		}
	}
	else if( isset($topdel))
		$idx = "Delete";
	}

if( isset($aclview) )
	{
	maclGroups();
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=topcats");
	exit;
	}

if( isset($upart) && $upart == "articles")
	{
	switch($idx)
		{
		case "homepage0":
			addToHomePages($item, 2, $hart0);
			break;
		case "homepage1":
			addToHomePages($item, 1, $hart1);
			break;
		}
	}

	
if( isset($action) && $action == "Yes")
	{
	if( $idx == "Delete" )
		{
		include_once $babInstallPath."utilit/delincl.php";
		bab_confirmDeleteTopic($category);
		bab_sitemap::clearAll();
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=topcats");
		exit;
		}
	else if( $idx == "Deletea")
		{
		include_once $babInstallPath."utilit/delincl.php";
		bab_confirmDeleteArticles($items);
		bab_sitemap::clearAll();
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=topcats");
		exit;
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
	
	case 'uploadTopicImg': // called by ajax
		uploadTopicImg();
		exit;	
	
	case 'deleteTempImage': // called by ajax
		deleteTempImage();
		exit;

	case "viewa":
		viewArticle($item);
		exit;

	case "Articles":
		$babBody->title = bab_translate("List of articles").": ".bab_getCategoryTitle($item);
		listArticles($item);
		warnRestrictionArticle($item);
		$babBody->addItemMenu("list", bab_translate("Topics"), $GLOBALS['babUrlScript']."?tg=topics&idx=list&cat=".$cat);
		$babBody->addItemMenu("Articles", bab_translate("Articles"), $GLOBALS['babUrlScript']."?tg=topic&idx=Articles&item=".$item);
		break;

	case "rights":
		$babBody->title = bab_getCategoryTitle($item);
		$macl = new macl("topic", "Modify", $item, "aclview");
        $macl->addtable( BAB_TOPICSVIEW_GROUPS_TBL,bab_translate("Who can read articles from this topic?"));
        $macl->addtable( BAB_TOPICSSUB_GROUPS_TBL,bab_translate("Who can submit new articles?"));
		$macl->addtable( BAB_TOPICSCOM_GROUPS_TBL,bab_translate("Who can post comment?"));
		$macl->addtable( BAB_TOPICSMOD_GROUPS_TBL,bab_translate("Who can modify articles?"));
        $macl->addtable( BAB_TOPICSMAN_GROUPS_TBL,bab_translate("Who can manage this topic?"));
		$macl->filter(0,0,1,1,1);
        $macl->babecho();
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats");
		$babBody->addItemMenu("rights", bab_translate("Rights"), $GLOBALS['babUrlScript']."?tg=topic&idx=rights&item=".$item);
		break;

	case "Delete":
		$babBody->title = bab_translate("Delete a topic");
		deleteCategory($item, $cat);
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats");
		$babBody->addItemMenu("Delete", bab_translate("Delete"), $GLOBALS['babUrlScript']."?tg=topic&idx=Delete&item=".$item);
		break;

	default:
	case "Modify":
		$babBody->title = bab_translate("Modify a topic");
		if( !isset($ncat)) { $ncat='';}
		if( !isset($category)) { $category='';}
		if( !isset($topdesc)) { $topdesc='';}
		if( !isset($saart)) { $saart='';}
		if( !isset($sacom)) { $sacom='';}
		if( !isset($saupd)) { $saupd='';}
		if( !isset($bnotif)) { $bnotif='';}
		if( !isset($atid)) { $atid='';}
		if( !isset($disptid)) { $disptid='';}
		if( !isset($restrict)) { $restrict='';}
		if( !isset($bhpages)) { $bhpages='';}
		if( !isset($bpubdates)) { $bpubdates='';}
		if( !isset($battachment)) { $battachment='';}
		if( !isset($bartupdate)) { $bartupdate='';}
		if( !isset($bautoapp)) { $bautoapp='';}
		if( !isset($bmanmod)) { $bmanmod='';}
		if( !isset($maxarts)) { $maxarts='';}
		if( !isset($busetags)) { $busetags='';}
		modifyCategory($item, $ncat, $category, $topdesc, $saart, $sacom, $saupd, $bnotif, $atid, $disptid, $restrict, $bhpages, $bpubdates, $battachment, $bartupdate, $bmanmod, $maxarts, $bautoapp, $busetags);
		$babBody->addItemMenu("List", bab_translate("Categories"), $GLOBALS['babUrlScript']."?tg=topcats");
		$babBody->addItemMenu("Modify", bab_translate("Modify"), $GLOBALS['babUrlScript']."?tg=topic&idx=Modify&item=".$item);
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>