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


		function temp($cat, $ncat, $category, $description, $saart, $sacom, $saupd, $bnotif, $atid, $disptid, $restrict, $bhpages, $bpubdates, $battachment, $bartupdate, $bmanmod, $maxarts, $bautoapp, $busetags)
			{
			global $babBody, $babDB;
			$this->topcat = bab_translate("Topic category");
			$this->title = bab_translate("Topic name");
			$this->desctitle = bab_translate("Description");
			$this->approver = bab_translate("Topic manager");
			$this->modcom = bab_translate("Approbation schema for comments");
			$this->modart = bab_translate("Approbation schema for articles");
			$this->modupd = bab_translate("Approbation schema for articles modification");
			$this->notiftxt = bab_translate("Allow author to notify group members by mail");
			$this->arttmpltxt = bab_translate("Article's model");
			$this->disptmpltxt = bab_translate("Display template");
			$this->restricttxt = bab_translate("Articles's authors can restrict access to articles");
			$this->hpagestxt = bab_translate("Allow author to propose articles for homes pages");
			$this->pubdatestxt = bab_translate("Allow author to specify dates of publication");
			$this->attachmenttxt = bab_translate("Allow author to attach files to articles");
			$this->artupdatetxt = bab_translate("Allow author to modify their articles");
			$this->manmodtxt = bab_translate("Allow managers to modify articles");
			$this->artmaxtxt = bab_translate("Max articles on the archives page");
			$this->yeswithapprobation = bab_translate("Yes with approbation");
			$this->yesnoapprobation = bab_translate("Yes without approbation");
			$this->tagstxt = bab_translate("Use tags");
			$this->yes = bab_translate("Yes");
			$this->no = bab_translate("No");
			$this->add = bab_translate("Add");
			$this->none = bab_translate("None");
			$this->autoapprobationtxt = bab_translate("Automatically approve author if he belongs to approbation schema");
			$this->tgval = "topics";
			$this->langLabel = bab_translate("Language");
			$this->langFiles = $GLOBALS['babLangFilter']->getLangFiles();
			$this->countLangFiles = count($this->langFiles);
			$this->item = "";
			$this->cat = $cat;
			$this->bhpages = $bhpages;
			$this->bpubdates = $bpubdates;
			$this->battachment = $battachment;
			$this->bartupdate = $bartupdate;

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
				$bnotif = "N";
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
				$restrict = "N";
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
					if( strpos($arr[$i], "head_") !== false ||  strpos($arr[$i], "body_") !== false )
						if( count($this->arrarttmpl) == 0  || !in_array(substr($arr[$i], 5), $this->arrarttmpl ))
							$this->arrarttmpl[] = substr($arr[$i], 5);
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
					if( strpos($arr[$i], "head_") !== false ||  strpos($arr[$i], "body_") !== false )
						if( count($this->arrdisptmpl) == 0  || !in_array(substr($arr[$i], 5), $this->arrdisptmpl ))
							$this->arrdisptmpl[] = substr($arr[$i], 5);
					}
				}
			$this->countdisptmpl = count($this->arrdisptmpl);
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
			global $babDB;
			static $i = 0, $k=0;
			if( $i < $this->sacount)
				{
				$arr = $babDB->db_fetch_array($this->sares);
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

function saveCategory($category, $cat, $sacom, $saart, $saupd, $bnotif, $lang, $atid, $disptid, $restrict, $bhpages, $bpubdates, $battachment, $bartupdate, $bmanmod, $maxarts, $bautoapp, $busetags)
	{
	global $babBody, $babDB;
	if( empty($category))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a topic name !!");
		return false;
		}

	if( $busetags == 'Y' )
		{
		list($count) = $babDB->db_fetch_array($babDB->db_query("select count(id) from ".BAB_TAGS_TBL.""));
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

	if( $busetags == 'Y' )
		{
		list($count) = $babDB->db_fetch_array($babDB->db_query("select count(id) from ".BAB_TAGS_TBL.""));
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


	
	
	$query = "select id from ".BAB_TOPICS_TBL." where category='".$babDB->db_escape_string($category)."' and id_cat='".$babDB->db_escape_string($cat)."'";	
	$res = $babDB->db_query($query);
	if( $babDB->db_num_rows($res) > 0)
		{
		$babBody->msgerror = bab_translate("ERROR: This topic already exists");
		return false;
		}

	if( empty($maxarts))
		{
		$maxarts = 10;
		}
		
	include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
	$editor = new bab_contentEditor('bab_topic');
	$description = $editor->getContent();

	$query = "insert into ".BAB_TOPICS_TBL." ( category, description, id_cat, idsaart, idsacom, idsa_update, notify, lang, article_tmpl, display_tmpl, restrict_access, allow_hpages, allow_pubdates, allow_attachments, allow_update, allow_manupdate, max_articles, auto_approbation, busetags) 
	
	values (
		'".$babDB->db_escape_string($category). "', 
		'" .$babDB->db_escape_string($description). "', 
		'" .$babDB->db_escape_string($cat). "', 
		'" .$babDB->db_escape_string($saart). "', 
		'" .$babDB->db_escape_string($sacom). "', 
		'" .$babDB->db_escape_string($saupd). "', 
		'" .$babDB->db_escape_string($bnotif). "', 
		'" .$babDB->db_escape_string($lang). "', 
		'" .$babDB->db_escape_string($atid). "', 
		'" .$babDB->db_escape_string($disptid). "', 
		'" .$babDB->db_escape_string($restrict). "', 
		'" .$babDB->db_escape_string($bhpages). "', 
		'" .$babDB->db_escape_string($bpubdates). "', 
		'" .$babDB->db_escape_string($battachment). "', 
		'" .$babDB->db_escape_string($bartupdate). "', 
		'" .$babDB->db_escape_string($bmanmod). "', 
		'".$babDB->db_escape_string($maxarts). "', 
		'".$babDB->db_escape_string($bautoapp). "', 
		'".$babDB->db_escape_string($busetags)."'
	)";
	$babDB->db_query($query);
	$id = $babDB->db_insert_id();

	$res = $babDB->db_query("select max(ordering) from ".BAB_TOPCAT_ORDER_TBL." where id_parent='".$babDB->db_escape_string($cat)."'");
	$arr = $babDB->db_fetch_array($res);
	if( isset($arr[0]))
		$ord = $arr[0] + 1;
	else
		$ord = 1;
	$babDB->db_query("insert into ".BAB_TOPCAT_ORDER_TBL." (id_topcat, type, ordering, id_parent) VALUES ('" .$babDB->db_escape_string($id). "', '2', '" . $babDB->db_escape_string($ord). "', '".$babDB->db_escape_string($cat)."')");

	/* update default rights */
	aclCloneRights(BAB_DEF_TOPCATVIEW_GROUPS_TBL, $cat, BAB_TOPICSVIEW_GROUPS_TBL, $id);
	aclCloneRights(BAB_DEF_TOPCATSUB_GROUPS_TBL, $cat, BAB_TOPICSSUB_GROUPS_TBL, $id);
	aclCloneRights(BAB_DEF_TOPCATCOM_GROUPS_TBL, $cat, BAB_TOPICSCOM_GROUPS_TBL, $id);
	aclCloneRights(BAB_DEF_TOPCATMOD_GROUPS_TBL, $cat, BAB_TOPICSMOD_GROUPS_TBL, $id);
	aclCloneRights(BAB_DEF_TOPCATMAN_GROUPS_TBL, $cat, BAB_TOPICSMAN_GROUPS_TBL, $id);
	
	return true;
	}

/* main */
if( !$babBody->isSuperAdmin && $babBody->currentDGGroup['articles'] != 'Y')
{
	$babBody->msgerror = bab_translate("Access denied");
	return;
}

$idx = bab_rp('idx', 'list');
$cat = intval(bab_rp('cat', 0));

if( isset($_POST['add']) )
	{
	if(!saveCategory($category, $ncat, $sacom, $saart, $saupd, $bnotif, $lang, $atid, $disptid, $restrict, $bhpages, $bpubdates, $battachment, $bartupdate, $bmanmod, $maxarts, $bautoapp, $busetags))
		{
//		$idx = 'addtopic';
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=topcats");
		}
	$cat = $ncat;
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
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>