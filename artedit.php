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
include 'base.php';
include_once $babInstallPath.'utilit/uiutil.php';
include_once $babInstallPath.'utilit/mailincl.php';
include_once $babInstallPath.'utilit/topincl.php';
include_once $babInstallPath.'utilit/artincl.php';
include_once $babInstallPath.'utilit/urlincl.php';
require_once $babInstallPath.'utilit/tree.php';
require_once dirname(__FILE__) . '/utilit/tagApi.php';



function listDrafts()
	{
	global $babBody;
	class listDraftsCls
		{
		var $name;
		var $nametxt;
		var $datesubtxt;
		var $datesub;
		var $statustxt;
		var $status;
		var $urlname;
		var $count;
		var $res;
		var $addtxt;
		var $urladd;
		var $edittxt;
		var $deletetxt;
		var $editurl;
		var $deleteurl;
		var $nbtrash;
		var $previewtxt;
		var $previewurl;
		var $submittxt;
		var $bsubmit;
		var $bsubmiturl;
		
		public $altbg = true;

		public function __construct()
			{
			global $babDB;
			$this->nametxt = bab_translate("Articles");
			$this->datesubtxt = bab_translate("Submission date");
			$this->statustxt = bab_translate("Status");
			$this->proptxt = bab_translate("Properties");
			$this->deletetxt = bab_translate("Delete");
			$this->previewtxt = bab_translate("Preview");
			$this->addtxt = bab_translate("Create a new article");
			$this->modtxt = bab_translate("Modify an existing article");
			$this->attachmenttxt = bab_translate("Attachments");
			$this->submittxt = bab_translate("Submit");
			$this->t_modify = bab_translate("Modify");
			$this->js_confirm_submit = bab_translate("Do you really want to submit")."?";
			$this->confirmdelete = bab_translate('Do you really want to delete the draft?');
			$this->urladd = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=edit");
			$this->urlmod = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=selecttopic");
			$req = "select adt.*, count(adft.id) as total from ".BAB_ART_DRAFTS_TBL." adt left join ".BAB_ART_DRAFTS_FILES_TBL." adft on adft.id_draft=adt.id where id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and adt.trash !='Y' and adt.idfai='0' and adt.result='".BAB_ART_STATUS_DRAFT."' GROUP BY adt.id order by date_modification desc";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			}

		public function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->res);
				$this->urlname = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=edit&iddraft=".$arr['id']);
				$this->deleteurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=delt&idart=".$arr['id']);
				$this->previewurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=preview&idart=".$arr['id']);
				$this->name = bab_toHtml($arr['title']);
				$this->categoryname = viewCategoriesHierarchy_txt($arr['id_topic']);
				$this->datesub = $arr['date_submission'] == "0000-00-00 00:00:00"? "":bab_shortDate(bab_mktime($arr['date_submission']), true);
				$this->datesub = bab_toHtml($this->datesub);
				$this->datepub = $arr['date_publication'] == "0000-00-00 00:00:00"? "":bab_shortDate(bab_mktime($arr['date_publication']), true);
				$this->datepub = bab_toHtml($this->datepub);
				$this->datearch = $arr['date_archiving'] == "0000-00-00 00:00:00"? "":bab_shortDate(bab_mktime($arr['date_archiving']), true);
				$this->datearch = bab_toHtml($this->datearch);
				if( $arr['total'] > 0 )
					{
					$this->attachment = true;
					}
				else
					{
					$this->attachment = false;
					}
				if( $arr['id_topic'] != 0 && !empty($arr['title']) && !empty($arr['head']))
					{
					$this->bsubmit = true;
					$this->bsubmiturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=sub&idart=".$arr['id']."");
					}
				else
					{
					$this->bsubmit = false;
					}
				if( $arr['id_article'] != 0 )
					{
					$this->bupdate = true;
					$this->status = bab_translate("Article in modification");
					}
				else
					{
					$this->bupdate = false;
					$this->status = bab_translate("New article");
					}
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new listDraftsCls();
	$babBody->babecho( bab_printTemplate($temp, "artedit.html", "draftslist"));
	}


	

function listMyArticles()
{
global $babBody;
class listDraftsCls
	{
	public $altbg = true;

	public function __construct()
	{
		global $babDB;
		$this->nametxt = bab_translate("Articles");
		$this->datemodtxt = bab_translate("Modification date");
		$this->statustxt = bab_translate("Status");
		$this->previewtxt = bab_translate("Preview");
		$this->attachmenttxt = bab_translate("Attachments");
		$this->t_modify = bab_translate("Modify");
		$req = "select 
			a.*, 
			count(af.id) as total 
			from bab_articles a left join bab_art_files af on af.id_article=a.id 
			where 
				a.id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' GROUP BY a.id order by a.date_modification desc LIMIT 0,1000";
		$this->res = $babDB->db_query($req);
		$this->count = $babDB->db_num_rows($this->res);
	}

	public function getnext()
	{
	global $babDB;
	static $i = 0;
	if( $i < $this->count)
		{
		$this->altbg = !$this->altbg;
		$arr = $babDB->db_fetch_array($this->res);
		$this->urlname = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=Modify&topics=".$arr['id_topic']."&article=".$arr['id']);
		$this->articleurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$arr['id_topic']."&article=".$arr['id']);
		$this->name = bab_toHtml($arr['title']);
		$this->categoryname = viewCategoriesHierarchy_txt($arr['id_topic']);
		$this->datemod = $arr['date_modification'] == "0000-00-00 00:00:00"? "":bab_shortDate(bab_mktime($arr['date_modification']), true);
		$this->datemod = bab_toHtml($this->datemod);
		
		if( $arr['total'] > 0 )
			{
			$this->attachment = true;
			}
		else
			{
			$this->attachment = false;
			}
		
		$i++;
		return true;
		}
	else
		return false;

	}
}

$temp = new listDraftsCls();
$babBody->babecho( bab_printTemplate($temp, "artedit.html", "myarticles"));	
}
	
	

/**
 * List of drafts submited for approval
 */
function listSubmitedArticles()
	{
	global $babBody;
	class temp
		{
		var $name;
		var $nametxt;
		var $datesubtxt;
		var $datesub;
		var $urlname;
		var $count;
		var $res;
		var $emptytxt;
		var $urlempty;
		var $edittxt;
		var $deletetxt;
		var $editurl;
		var $deleteurl;
		var $previewtxt;
		var $previewurl;
		var $statustxt;

		function temp()
			{
			require_once dirname(__FILE__).'/utilit/wfincl.php';
			global $babDB;
			$this->nametxt = bab_translate("Articles");
			$this->datesubtxt = bab_translate("Submission date");
			$this->deletetxt = bab_translate("Delete");
			$this->previewtxt = bab_translate("Preview");
			$this->statustxt = bab_translate("Status");
			$this->emptytxt = bab_translate("Empty");
			$this->restoretxt = bab_translate("Restore");
			$this->attachmenttxt = bab_translate("Attachments");
			$this->notestxt = bab_translate("Notes");
			$this->urladd = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=new");
			$req = "
				select 
				adt.*, 
				count(adft.id) as totalf 
			from 
				".BAB_ART_DRAFTS_TBL." adt 
					left join ".BAB_ART_DRAFTS_FILES_TBL." adft on adft.id_draft=adt.id  
			where 
				adt.id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' 
				and adt.trash !='Y' 
				and adt.result!='".BAB_ART_STATUS_DRAFT."' 
				
			GROUP BY adt.id order by date_submission desc
			";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->urlname = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=propa&idart=".$arr['id']);
				$this->deleteurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=delt&idart=".$arr['id']);
				$this->previewurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=preview&idart=".$arr['id']);
				$this->restoreurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=rests&idart=".$arr['id']);
				$this->name = bab_toHtml($arr['title']);
				$this->categoryname = viewCategoriesHierarchy_txt($arr['id_topic']);
				$this->datesub = $arr['date_submission'] == "0000-00-00 00:00:00"? "":bab_shortDate(bab_mktime($arr['date_submission']), true);
				$this->datesub = bab_toHtml($this->datesub);

				if( $arr['result'] == BAB_ART_STATUS_WAIT )
					{
					$this->bdelete = false;
					}
				else
					{
					$this->bdelete = true;
					}
				if( $arr['totalf'] > 0 )
					{
					$this->attachment = true;
					}
				else
					{
					$this->attachment = false;
					}

				switch($arr['result'])
					{
					case BAB_ART_STATUS_WAIT:
						$arr = bab_WFGetWaitingApproversInstance($arr['idfai']);
						$users = array();
						foreach($arr as $id_user) {
							$users[] = bab_getUserName($id_user);
						}
						$this->status = sprintf(bab_translate("Waiting for approval by : %s"), implode(', ',$users));
						break;
						
					case BAB_ART_STATUS_OK:
						$this->status = bab_translate("Accepted");
						break;
					case BAB_ART_STATUS_NOK:
						$this->status = bab_translate("Refused");
						break;
					}
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho( bab_printTemplate($temp, "artedit.html", "submitedarticleslist"));
	}

/**
 * 
 * @return unknown_type
 */
function listDraftsInTrash()
	{
	global $babBody;
	class temp
		{
		var $name;
		var $nametxt;
		var $urlname;
		var $datecreatetxt;
		var $datecreate;
		var $datemodifytxt;
		var $datemodify;
		var $count;
		var $res;
		var $addtxt;
		var $urladd;
		var $restoretxt;
		var $restoreurl;
		var $previewtxt;
		var $previewurl;

		function temp()
			{
			global $babDB;
			$this->nametxt = bab_translate("Articles");
			$this->datecreatetxt = bab_translate("Creation");
			$this->datemodifytxt = bab_translate("Modification");
			$this->edittxt = bab_translate("Edit");
			$this->restoretxt = bab_translate("Restore");
			$this->previewtxt = bab_translate("Preview");
			$this->addtxt = bab_translate("Empty");
			$this->urladd = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=empty");
			$req = "select * from ".BAB_ART_DRAFTS_TBL." where id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and trash !='N' order by date_modification desc";
			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			}

		function getnext()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->urlname = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=view&idart=".$arr['id']);
				$this->restoreurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=restore&idart=".$arr['id']);
				$this->previewurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=preview&idart=".$arr['id']);
				$this->name = bab_toHtml($arr['title']);
				$this->datecreate = bab_toHtml(bab_shortDate(bab_mktime($arr['date_creation']), true));
				$this->datemodify = bab_toHtml(bab_shortDate(bab_mktime($arr['date_modification']), true));
				$i++;
				return true;
				}
			else
				return false;

			}
		}

	$temp = new temp();
	$babBody->babecho( bab_printTemplate($temp, "artedit.html", "trashlist"));
	}


/*
function propertiesArticle($idart)
{
	global $babBody;
	class temp
		{
		var $arttxt;
		var $sContent;
		
		function temp($idart)
			{
			global $babDB;
			$this->sContent	= 'text/html; charset=' . bab_charset::getIso();
			$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."' and id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$this->idart = bab_toHtml($idart);
				$this->arttxt = bab_translate("Article");
				$this->pathtxt = bab_translate("Path");
				$this->authortxt = bab_translate("Author");
				$this->confirmtxt = bab_translate("Confirm");
				$this->commenttxt = bab_translate("Comment");
				$this->yes = bab_translate("Yes");
				$this->no = bab_translate("No");
				$this->closetxt = bab_translate("Close");

				$this->datepubtxt = bab_translate("Publication date");
				$this->datearchtxt = bab_translate("Archiving date");
				$this->hpages0txt = bab_translate("Proposed to unregistered users home page");
				$this->hpages1txt = bab_translate("Proposed to registered users home page");
				$this->updatetxt = bab_translate("Update");

				$this->arttitle = bab_toHtml($arr['title']);
				$this->pathname = viewCategoriesHierarchy_txt($arr['id_topic']);
				$this->author = bab_toHtml(bab_getUserName($arr['id_author']));
				$this->datepub = $arr['date_publication'] == "0000-00-00 00:00:00"? "":bab_shortDate(bab_mktime($arr['date_publication']), true);
				$this->datepub = bab_toHtml($this->datepub);
				$this->datearch = $arr['date_archiving'] == "0000-00-00 00:00:00"? "":bab_shortDate(bab_mktime($arr['date_archiving']), true);
				$this->datearch = bab_toHtml($this->datearch);

				if( $arr['hpage_public'] == 'Y')
					{
					$this->hpages0 = $this->yes;
					}
				else
					{
					$this->hpages0 = $this->no;
					}
				if( $arr['hpage_private'] == 'Y')
					{
					$this->hpages1 = $this->yes;
					}
				else
					{
					$this->hpages1 = $this->no;
					}
				if( $arr['result'] == BAB_ART_STATUS_WAIT && $arr['idfai'] != 0 )
					{
					// show waiting approvers
					}
				}
			else
				{
				echo bab_translate("Access denied");
				}
			}

		}

	$temp = new temp($idart);
	$babBody->babPopup(bab_printTemplate($temp, "artedit.html", "propertiesarticles"));
}

*/




/**
 * Display a tree with topics selectables for the step 1 of the publication when we modify an article
 * 
 * @param $topicId : used when we click on the PREV button
 * @param $articleId : used when we click on the PREV button
 */
function bab_showTopicsTreeForModificationOfAnArticle($topicId, $articleId)
{
	class FormTemplate
	{
		var $topicId;
		var $articleId;
		var $rfurl;

		var $title;
		var $headtext;
		var $bodytext;
		var $lang;

		function FormTemplate($topicId, $articleId)
		{
			$this->idtopic = $topicId;
			$this->idarticle = $articleId;
			$this->rfurl = bab_toHtml(bab_rp('rfurl'));
			
			$this->title = bab_pp('title', '');
			$this->headtext = bab_pp('headtext', '');
			$this->bodytext = bab_pp('bodytext', '');
			$this->lang = bab_pp('lang', '');
		}
	};

	$template = new FormTemplate($topicId, $articleId);

	$html = bab_printTemplate($template, 'artedit.html', 'showTopicsTreeForModificationOfAnArticle');

	$topicTree = new bab_ArticleTreeView('article_topics_tree');
	$topicTree->setAttributes(bab_ArticleTreeView::SHOW_TOPICS
							| bab_ArticleTreeView::SELECTABLE_TOPICS
							| bab_ArticleTreeView::HIDE_EMPTY_TOPICS_AND_CATEGORIES
							| bab_ArticleTreeView::SHOW_TOOLBAR
							| bab_ArticleTreeView::MEMORIZE_OPEN_NODES
							);
	$topicTree->setAction(bab_ArticleTreeView::MODIFY_ARTICLES);
	$topicTree->setTopicsLinks('javascript:selectTopic(%s);');
	if ($topicId != '') {
		$topicTree->highlightElement('topic' . bab_ArticleTreeView::ID_SEPARATOR . $topicId);
	}
	$topicTree->order();
	$topicTree->sort();

	$html .= $topicTree->printTemplate();

	return $html; 
}



/**
 * List of articles in one topic, select for modification
 * @param $topicid
 * @return unknown_type
 */
function showChoiceArticleModify($topicid)
{
	global $babBody;
	class temp
		{
		var $res;
		var $count;
		var $topicname;
		var $topicpath;
		var $description;
		var $idtopic;
		var $topicchecked;
		var $idtopicsel;
		var $articleid;
		var $title; 
		var $headtext;
		var $bodytext;
		var $lang;
		var $bmodify;
		var $modifauthor;
		var $altbg = true;
		var $nbartmodify = 0;

		function temp($topicid)
			{
			global $babBody, $babBody, $babDB, $topicid, $articleid, $rfurl;
			$this->count = 0;
			$res = $babDB->db_query("select * from ".BAB_TOPICS_TBL." where id='".$babDB->db_escape_string($topicid)."'");

			
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				if( bab_isAccessValid(BAB_TOPICSMOD_GROUPS_TBL, $topicid) || ($arr['allow_manupdate'] != '0' && bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL,$topicid)) )
					{
					$req = "select at.id, at.title, adt.id_author, adt.id as id_draft from ".BAB_ARTICLES_TBL." at left join ".BAB_ART_DRAFTS_TBL." adt on at.id=adt.id_article where at.id_topic='".$babDB->db_escape_string($topicid)."' and at.archive='N' order by at.ordering asc";
					}
				elseif( $arr['allow_update'] && bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $topicid))
					{
					$req = "select at.id, at.title, adt.id_author, adt.id as id_draft from ".BAB_ARTICLES_TBL." at left join ".BAB_ART_DRAFTS_TBL." adt on at.id=adt.id_article where at.id_topic='".$babDB->db_escape_string($topicid)."' and at.archive='N' and at.id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' order by at.ordering asc";
					}
				else
					{
					$req = '';
					}

				if( $req != '' )
					{
					$this->res = $babDB->db_query($req);
					$this->count = $babDB->db_num_rows($this->res);
					$this->rfurl = bab_toHtml($rfurl);
					$this->topicid = bab_toHtml($topicid);
					$babBody->setTitle(bab_translate("Choose the article"));
					$this->steptitle = viewCategoriesHierarchy_txt($topicid);
					$this->nexttxt = bab_translate("Next");
					$this->canceltxt = bab_translate("Cancel");
					$this->previoustxt = bab_translate("Previous");
					}
				}
			
			if( $req == '' )
				{
				$babBody->addError(bab_translate("Access denied"));
				}
			}

		function getnextarticle()
			{
			global $babDB, $babBody;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->res);
				$this->articleid = bab_toHtml($arr['id']);
				$this->articletitle = bab_toHtml($arr['title']);
				if( $i == 0 )
					{
					$this->articlechecked = 'checked';
					}
				else
					{
					$this->articlechecked = '';
					}
				if( !isset($arr['id_author']) || empty($arr['id_author']))
					{
					$this->modifybytxt = '';
					$this->modifauthor = '';
					$this->bmodify = true;
					$this->nbartmodify++;
					}
				else
					{
					$this->modifybytxt = bab_translate("In modification by");
					$this->modifauthor = bab_toHtml(bab_getUserName($arr['id_author']));
					$this->bmodify = false;
					if( $arr['id_author'] == $GLOBALS['BAB_SESS_USERID'] )
						{
						$this->editdrafttxt = bab_translate("Edit");
						$this->editdrafturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=artedit&idx=edit&iddraft=".$arr['id_draft']);
						$this->bauthor = true;
						}
					else
						{
						$this->bauthor = false;
						}
					}
				$i++;
				return true;
				}
			else
				return false;

			}

		}
	$temp = new temp($topicid);
	$babBody->babecho(bab_printTemplate($temp, "artedit.html", "modarticlechoicestep"));
}




/**
 * Download attachement
 * @param $idart
 * @param $idf
 * @return unknown_type
 */
function getDocumentArticleDraft( $idart, $idf )
	{
	global $babDB, $babBody, $BAB_SESS_USERID;
	$access = false;
	$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."' and id_author='".$babDB->db_escape_string($BAB_SESS_USERID)."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$access = true;
		}

	$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_FILES_TBL." where id='".$babDB->db_escape_string($idf)."' and id_draft='".$babDB->db_escape_string($idart)."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$access = true;
		}

	if( !$access )
		{
		echo bab_translate("Access denied");
		return;
		}

	$arr = $babDB->db_fetch_array($res);
	$file = stripslashes($arr['name']);

	$fullpath = bab_getUploadDraftsPath();
	

	$fullpath .= $arr['id_draft'].",".$file;
	$fsize = filesize($fullpath);

	$mime = bab_getFileMimeType($file);

	if( mb_strtolower(bab_browserAgent()) == "msie")
		header('Cache-Control: public');
	$inl = bab_getFileContentDisposition() == 1? 1: '';
	if( $inl == '1' )
		{
		header("Content-Disposition: inline; filename=\"$file\""."\n");
		}
	else
		{
		header("Content-Disposition: attachment; filename=\"$file\""."\n");
		}
	header("Content-Type: $mime"."\n");
	header("Content-Length: ". $fsize."\n");
	header("Content-transfert-encoding: binary"."\n");
	$fp=fopen($fullpath,"rb");
	print fread($fp,$fsize);
	fclose($fp);
	}

	
	



function deleteDraft($idart)
	{
	global $babDB, $BAB_SESS_USERID;
	$res = $babDB->db_query("select result, id_article from ".BAB_ART_DRAFTS_TBL." where id='".$babDB->db_escape_string($idart)."' and id_author='".$babDB->db_escape_string($BAB_SESS_USERID)."'");
	if( $res && $babDB->db_num_rows($res) == 1 )
		{
		$arr = $babDB->db_fetch_array($res);
		if( $arr['result'] != BAB_ART_STATUS_WAIT )
			{
			require_once dirname(__FILE__).'/utilit/artdraft.class.php';
			$draft = new bab_ArtDraft;
			$draft->getFromIdDraft($idart);
			$draft->delete();
			}
		}
	}

function emptyTrash()
	{
	global $babDB, $BAB_SESS_USERID;
	$res = $babDB->db_query("select id from ".BAB_ART_DRAFTS_TBL." where trash='Y' and id_author='".$babDB->db_escape_string($BAB_SESS_USERID)."'");
	while( $arr = $babDB->db_fetch_array($res))
		{
		deleteDraft($arr['id']);
		}
	}


function restoreArticleDraft($idart)
	{
	global $babDB;
	$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set trash='N' where id='".$babDB->db_escape_string($idart)."' and id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
	list($nbtrash) = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_ART_DRAFTS_TBL." where id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and trash !='N'"));
	if( $nbtrash > 0 )
		{
		return false;
		}
	else
		{
		return true;
		}
	}

function restoreRefusedArticleDraft($idart)
	{
	global $babDB;
	$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set trash='N', result='', date_submission='0000-00-00 :00:00:00' where id='".$idart."' and id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."'");
	list($nbsub) = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_ART_DRAFTS_TBL." where id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and result !='".BAB_ART_STATUS_DRAFT."'"));
	if( $nbsub > 0 )
		{
		return false;
		}
	else
		{
		return true;
		}
	}





/**
 * 
 * @param $idart
 * @param $message
 * @return bool
 */
function submitArticleDraft($idart, &$message)
{
	require_once dirname(__FILE__).'/utilit/artdraft.class.php';
	$draft = new bab_ArtDraft;
	$draft->getFromIdDraft($idart);
	
	if (!$draft->isModifiable())
	{
		$message = bab_translate("You don't have rights to modify this article");
		return false;
	}
	
	$draft->submit();
	return true;
}



/**
 * Return booleans : test if articles drafts exists for the current user : in trash or, not in trash and in approbation (the user can't modify an article in approbation)
 * 
 * @return array indexed by trash and articles
 */
function artedit_init()
{
	global $babDB;

	static $aredit = array();
	
	if (empty($arrinit))
	{
		$aredit['articles'] = false;
		$aredit['trash'] = false;
	
		if( $GLOBALS['BAB_SESS_USERID'] )
		{
			/* Test if there are articles drafts in trash for the current user */
			$arr = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_ART_DRAFTS_TBL." where id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and trash='Y'"));
			if( $arr['total'] != 0 )
			{
				$aredit['trash'] = true;
			}
	
			/* Test if there are articles drafts not in trash and in approbation (the user can't modify an article in approbation) */
			$arr = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_ART_DRAFTS_TBL." where id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."' and result!='".BAB_ART_STATUS_DRAFT."' and trash='N'"));
			if( $arr['total'] != 0 )
			{
				$aredit['articles'] = true;
			}
		}
	}
	return $aredit;
}




	


function getImage()
{	
	require_once dirname(__FILE__) . '/utilit/artincl.php';
	require_once dirname(__FILE__) . '/utilit/gdiincl.php';

	$iIdDraft		= (int) bab_rp('iIdDraft', 0);
	$iIdArticle		= (int) bab_rp('iIdArticle', 0);
	$iWidth			= (int) bab_rp('iWidth', 0);
	$iHeight		= (int) bab_rp('iHeight', 0);
	$sImage			= (string) bab_rp('sImage', '');
	$oEnvObj		= bab_getInstance('bab_PublicationPathsEnv');
	$iIdDelegation	= 0; //Dummy value, i dont need this here
	
	global $babBody;
	$sPath = '';
	
	if(0 < $iIdDraft)
	{
		$oEnvObj->setEnv($iIdDelegation);
		$sPath = $oEnvObj->getDraftArticleImgPath($iIdDraft);
	}
	else
	{
		$iIdDelegation = bab_getArticleDelegationId($iIdArticle);
		if(false === $iIdDelegation)
		{
			return '???';
		}
		
		$oEnvObj->setEnv($iIdDelegation);
		$sPath = $oEnvObj->getArticleImgPath($iIdArticle);
	}
	
	$oImageResize = new bab_ImageResize();
	$oImageResize->resizeImageAuto($sPath . $sImage, $iWidth, $iHeight);
}








/**
 * Get properties of current topic of a draft or the properties of the topic where the draft will be moved to
 * the access must be restricted to the topic of a modifiable draft or a topic whis access to submit group
 * 
 * @return unknown_type
 */
function bab_ajaxTopicRow() {
	
	$idTopic = (int) bab_gp('id_topic', 0);
	$idDraft = (int) bab_gp('id_draft', 0);
	
	if ($idDraft && !bab_isDraftModifiable($idDraft))
	{
		return;
	}
	
	if (!$idDraft && !bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $idTopic))
	{
		// no draft
		return;
	}
	
	if ($idDraft)
	{
		require_once dirname(__FILE__).'/utilit/artdraft.class.php';
		$draft = new bab_ArtDraft;
		$draft->getFromIdDraft($idDraft);
		
		if ($draft->id_topic != $idTopic && !bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $idTopic))
		{
			// no access to target topic
			return;
		}
	}
	
	
	
	$W = bab_Widgets();
	$W->HtmlCanvas();

	global $babDB;
	$res = $babDB->db_query(
		"SELECT * FROM bab_topics WHERE id= " . $babDB->quote($idTopic)
	);
	if( $res && $babDB->db_num_rows($res) == 1 )
	{
		$row = $babDB->db_fetch_assoc($res);
		
		// add restriction groups
		
		if ('Y' === $row['restrict_access'])
		{
			$row['groups'] = array();
			
			require_once dirname(__FILE__).'/admin/acl.php';
			$groups = aclGetAccessGroups(BAB_TOPICSVIEW_GROUPS_TBL, $row['id']);
			
			foreach($groups as $id_group)
			{
				if ($id_group < BAB_ADMINISTRATOR_GROUP)
				{
					continue;
				}
				
				$name = bab_getGroupName($id_group, false);
				if ($name)
				{
					$row['groups'][] = array($id_group, bab_abbr($name, BAB_ABBR_FULL_WORDS, 50));
				}
			}
		}
		
		
		if ('' !== $row['article_tmpl'])
		{
			$row['template'] = bab_getTopicTemplate($row['article_tmpl'], 'html', 'html');
		}
		
		
		$rr = Widget_HtmlCanvas::json_encode($row);
		echo $rr;
	}else{
		echo '0';
	}
}


/**
 * Get temporary loaded attachments as json array
 * @return unknown_type
 */
function bab_ajaxAttachments()
{
	$W = bab_Widgets();
	$W->HtmlCanvas();
	
	$filepicker = $W->FilePicker()->setEncodingMethod(null);
	$files = $filepicker->getTemporaryFiles('articleFiles');
	
	if (!isset($files))
	{
		return;
	}
	
	$json = array();
	$ordering = array();
	
	foreach($files as $f)
	{
		/*@var $f Widget_FilePickerItem */
		
		$key = sprintf("bab_attachement_%u", crc32($f->getFileName()));
		
		
		if (isset($_SESSION['bab_articleTempAttachments'][$f->toString()])) {
			$description = $_SESSION['bab_articleTempAttachments'][$f->toString()]['description'];
			$ordering[$_SESSION['bab_articleTempAttachments'][$f->toString()]['ordering']] = $key;
		} else {
			
			$description = '';
			
			// try to get default description for new file
			
			$FileInfos = @bab_functionality::get('FileInfos');
			if ($FileInfos)
			{
				$meta = $FileInfos->getMetadata($f->getFilePath()->toString());
				try {
					$description = $meta->Doc->Title;
				} catch(lfm_MetadataException $e)
				{
					// ignore error
				}
			}
		}
		
		$json[$key] = array(
			'name' => $f->toString(),
			'filename' => $f->getFileName(),
			'description' => $description
		);
	}
	
	
	if (count($ordering) === count($json))
	{
		$json2 = array();
		ksort($ordering);
		foreach($ordering as $key)
		{
			$json2[$key] = $json[$key];
		}
		$json = $json2;
	}
	
	
	echo Widget_HtmlCanvas::json_encode($json);
}


/**
 * remove temporary loaded attachment
 * @return unknown_type
 */
function bab_ajaxRemoveAttachment()
{
	$id_article = bab_gp('id_article');
	$id_draft = bab_gp('id_draft');
	$filename = bab_gp('filename');
	
	$W = bab_Widgets();
	$W->HtmlCanvas();
	
	$filepicker = $W->FilePicker()->setEncodingMethod(null);
	$files = $filepicker->getTemporaryFiles('articleFiles');
	
	if (!isset($files))
	{
		return;
	}
	
	$json = array();
	
	foreach($files as $f)
	{
		/*@var $f Widget_FilePickerItem */
		
		if ($filename === $f->getFileName())
		{
			$f->delete();
			return;
		}
	}
	
}



/**
 * Save article draft
 * @return unknown_type
 */
function bab_saveArticle(){
	
	global $babBody, $babDB;
	
	include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
	include_once $GLOBALS['babInstallPath']."utilit/dateTime.php";
	require_once $GLOBALS['babInstallPath']."utilit/artdraft.class.php";
	require_once $GLOBALS['babInstallPath']."utilit/arteditincl.php";

	if(bab_pp('cancel', '') != ''){
		$idDraft = bab_pp('iddraft',0);
		if ($idDraft && bab_isDraftModifiable($idDraft))
		{
			$draft = new bab_ArtDraft;
			$draft->getFromIdDraft($idDraft);
			$draft->delete();
		}
		$url = bab_pp('cancelUrl');
		
	} else {

		
		$id_topic = (int) bab_pp('id_topic',0);
		
		if (0 === $id_topic)
		{
			$babBody->addError(bab_translate('The topic is mandatory'));
			return false;
		}
		
		$title = bab_pp('title');
		$title = trim($title);
		
		if (empty($title))
		{
			$babBody->addError(bab_translate('The article title is mandatory'));
			return false;
		}
		
		$headeditor = new bab_contentEditor('bab_article_head');
		$headeditor->setRequestFieldName('head');
		$head = $headeditor->getContent();
		$head = trim($head);
		
		if (empty($head))
		{
			$babBody->addError(bab_translate('The article head is mandatory'));
			return false;
		}
		
		
		
	
		list($busetags) = $babDB->db_fetch_array($babDB->db_query("select busetags from bab_topics where id=".$babDB->quote($id_topic)));
		
	
		$taglist = array();
		if( $busetags == 'Y' )
		{
			
			$tags = bab_pp('tags');
			$tags = trim($tags);
	
			if( !empty($tags))
			{
				$atags = explode(',', $tags);
				foreach( $atags as $tagname )
				{
					$tagname = trim($tagname);
					
					if ('' === $tagname)
					{
						continue;
					}
					
					$oTagMgr	= bab_getInstance('bab_TagMgr');
					$oTag		= $oTagMgr->getByName($tagname);
					if(!($oTag instanceof bab_Tag))
					{
						$babBody->addError(sprintf(bab_translate("The keyword %s does not exists in the thesaurus"), $tagname));
						return false;
					}
					
					$taglist[] = $oTag;
				}
			}
	
			if( count($taglist) == 0 )
			{
				$babBody->addError(bab_translate("You must specify at least one tag"));
				return false;
			}
		}
		
		
		
		
		
		$idDraft = bab_pp('iddraft',0);
		$draft = new bab_ArtDraft;
		
		try {
			if (empty($idDraft))
			{
				// access rights are verified in bab_newArticleDraft()
				$draft->createInTopic($id_topic);
			} else {
				
				if (!bab_isDraftModifiable($idDraft))
				{
					throw new ErrorException(bab_translate('Error, the draft is not modifiable'));
				}
				
				$draft->getFromIdDraft($idDraft);
				$draft->id_topic = $id_topic;
			}
		} 
		catch(ErrorException $e)
		{
			$babBody->addError($e->getMessage());
			return false;
		}
		
		
		$draft->title = $title;
		
		$draft->head = $headeditor->getContent();
		$draft->head_format = $headeditor->getFormat();
		
		$bodyeditor = new bab_contentEditor('bab_article_body');
		$bodyeditor->setRequestFieldName('body');
		$draft->body = $bodyeditor->getContent();
		$draft->body_format = $bodyeditor->getFormat();
		
		$draft->importDate('date_submission', bab_pp('date_submission'), bab_pp('time_submission','00:00:00'));
		$draft->importDate('date_archiving', bab_pp('date_archiving'), bab_pp('time_archiving','00:00:00'));
		$draft->importDate('date_publication', bab_pp('date_publication'), bab_pp('time_publication','00:00:00'));
	
		$draft->hpage_private = bab_pp('hpage_private', 'N');
		$draft->hpage_public = bab_pp('hpage_public', 'N');
		
		$draft->notify_members = bab_pp('notify_members', 'N');
		$draft->lang = bab_pp('lang');
		$draft->setRestriction(bab_pp('restriction'), (array) bab_pp('groups'), bab_pp('operator'));
		$draft->modification_comment = bab_pp('modification_comment', null);
		$draft->update_datemodif = bab_pp('update_datemodif', 'Y');
		
		
		if(bab_pp('submit', '') != ''){
			$draft->save();
			$draft->saveTempAttachments(bab_pp('files', array()));
			$draft->saveTempPicture();
			$draft->saveTags($taglist);
			
			$draft->submit();
			
			$url = bab_pp('submitUrl');
			
		}elseif(bab_pp('draft', '') != ''){
			
			$draft->save();
			$draft->saveTempAttachments(bab_pp('files', array()));
			$draft->saveTempPicture();
			$draft->saveTags($taglist);
			
			$url = $GLOBALS['babUrlScript']."?tg=artedit&idx=list";
			
		}elseif(bab_pp('see', '') != ''){
			
			$draft->save();
			$draft->saveTempAttachments(bab_pp('files', array()));
			$draft->saveTempPicture();
			$draft->saveTags($taglist);
			
			$form = new bab_ArticleDraftEditor;
			$form->fromDraft($draft->getId());
			$form->preview();
			$form->display();
			return true;
		}
	}
	
	if (empty($url))
	{
		$url = $GLOBALS['babUrlScript']."?tg=artedit&idx=list";
	}
	
	
	Header("Location: ". $url);
	exit;
}





/**
 * 
 * @return unknown_type
 */
function bab_art_defaultMenu()
{
	global $babBody;
	$arrinit = artedit_init();
	
	if( $arrinit['trash'] )
	{
		/* There are articles in trash */
		$babBody->addItemMenu("ltrash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=artedit&idx=ltrash");
	}
	if( $arrinit['articles'] )
	{
		/* There are articles in approbation */
		$babBody->addItemMenu("lsub", bab_translate("Waiting"), $GLOBALS['babUrlScript']."?tg=artedit&idx=lsub");
	}
	if ($GLOBALS['BAB_SESS_LOGGED'])
	{
		$babBody->addItemMenu("articles", bab_translate("My Articles"), $GLOBALS['babUrlScript']."?tg=artedit&idx=articles");
	}
}



/* main */



if( !bab_isArticleEditAccess() ) 
{
	
	$babBody->addError(bab_translate('Access denied, no accessible topic'));
	return;
}


$idx = bab_rp('idx', 'list');
$rfurl = bab_rp('rfurl', "?tg=artedit&idx=list");



if( $updstep01 = bab_rp('updstep01'))
{

	if( $updstep01 == 'cancel')
	{
		$idx='unload';
		$refreshurl = $rfurl;
		$popupmessage = '';
	}
	elseif( $updstep01 == 'next')
	{
		$idx = 'choosearticle';
	}
}
elseif( $updstep02 = bab_rp('updstep02') )
{
	if( $updstep02 == 'cancel' )
	{
		$idx='unload';
		$refreshurl = $rfurl;
		$popupmessage = '';
	}
	elseif( $updstep02 == 'prev' )
	{
		$idx = 'selecttopic';
	}
	elseif( $updstep02 == 'next' )
	{
		$articleid = bab_pp('articleid', 0);
		require_once dirname(__FILE__) . '/utilit/artdraft.class.php';
		$draft = new bab_ArtDraft;
		$draft->getFromIdArticle($articleid);

		if ($draft->getId())
		{
			$url = bab_url::get_request('tg');
			$url->idx = 'edit';
			$url->iddraft = $draft->getId();
			$url->location();
		} else {
			$babBody->addError(bab_translate("Draft creation failed"));
		}
	}
}


if( $idx == 'restore')
{
	$idart = bab_gp('idart', 0);
	if( $idart && !restoreArticleDraft($idart))
	{
		$idx = 'ltrash';
	}
	else
	{
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
		exit;
	}
}elseif( $idx == 'rests')
{
	$idart = bab_gp('idart', 0);
	if($idart && !restoreRefusedArticleDraft($idart))
		{
		Header('Location: '. $GLOBALS['babUrlScript'].'?tg=artedit&idx=lsub');
		exit;
		}
	else
		{
		$idx = 'lsub';
		}
}


switch($idx)
	{
		
	case 'empty':
		emptyTrash();
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
		exit;
		break;
		
	case 'delt':
		$idart = bab_gp('idart', 0);
		if( $idart )
		{
			deleteDraft($idart);
		}
		Header('Location: '. $GLOBALS['babUrlScript'].'?tg=artedit');
		exit;
		break;
	

	case "selecttopic":
		$articleId = bab_rp('idart');
		if (!is_numeric($articleId)) {
			$articleId = '';
		}
		$topicId = bab_rp('topicid');
		if (!is_numeric($topicId)) {
			$topicId = '';
		}
		$babBody->setTitle(bab_translate("Choose the topic"));
		$html = bab_showTopicsTreeForModificationOfAnArticle($topicId, $articleId);
		$babBody->babecho($html);
		break;

	case "choosearticle":
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("Choose the article");
		$topicid = bab_rp('topicid');
		showChoiceArticleModify($topicid);
		break;
		
		
	case "getf":
		$idart = bab_rp('idart', 0);
		$idf = bab_rp('idf', 0);
		getDocumentArticleDraft( $idart, $idf );
		exit;
		break;
		
		
	case "propa":
		$idart = bab_rp('idart', 0);
		propertiesArticle( $idart);
		exit;
		break;
		
		
	case "preview":
		$babBody->babPopup(bab_previewArticleDraft(bab_rp('idart')));
		break;
		
	case "save":
		if (bab_saveArticle())
		{
			break;
		}
		// no break
	case "edit":
		$babBody->addItemMenu("list", bab_translate("Drafts"), $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
		$babBody->addItemMenu("edit", bab_translate("New article"), $GLOBALS['babUrlScript']."?tg=artedit&idx=edit");
		require_once dirname(__FILE__).'/utilit/arteditincl.php';
		$form  = new bab_ArticleDraftEditor;
		if ($iddraft = bab_rp('iddraft', null))
		{
			$form->fromDraft($iddraft);
		}
		$form->display();
		break;
		
		
	
	
	case "ajaxTopicRow":
		bab_ajaxTopicRow();
		die;
		break;
		
	case 'ajaxAttachments':
		bab_ajaxAttachments();
		die;
		break;

	case 'ajaxRemoveAttachment':
		bab_ajaxRemoveAttachment();
		die;
		break;
		
	case "ltrash":
		$arrinit = artedit_init();
		if( !$arrinit['trash'] )
		{
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
		exit;
		}
		$babBody->title = bab_translate("List of articles");
		$babBody->addItemMenu("list", bab_translate("Drafts"), $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
		listDraftsInTrash();
		bab_art_defaultMenu();
		break;
		
	case "lsub": // list articles submited for approbation
		$arrinit = artedit_init();
		if( !$arrinit['articles'] )
		{
			$babBody->addError(bab_translate('Access denied, no waiting articles'));
			return;
		}
		$babBody->title = bab_translate("List of submitted articles");
		$babBody->addItemMenu("list", bab_translate("Drafts"), $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
		listSubmitedArticles();
		bab_art_defaultMenu();
		break;
		
	case 'articles':
		if (!$GLOBALS['BAB_SESS_LOGGED'])
		{
			$babBody->addError(bab_translate('Access denied'));
			return;
		}
		$babBody->title = bab_translate("List of articles where i am the author");
		$babBody->addItemMenu("list", bab_translate("Drafts"), $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
		listMyArticles();
		bab_art_defaultMenu();
		break;
	
	case "sub":
		$idart = bab_rp('idart', 0);
		submitArticleDraft( $idart, $babBody->msgerror);
		$idx = "list";
		/* break; */
		
	case "list": /* List of articles drafts */
	default:
		$babBody->title = bab_translate("List of my articles drafts");
		$babBody->addItemMenu("list", bab_translate("Drafts"), $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
		listDrafts();
		bab_art_defaultMenu();
		break;
	}

$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab','UserPublication');


