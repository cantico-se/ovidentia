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
include "base.php";
include_once $babInstallPath."utilit/uiutil.php";
include_once $babInstallPath."utilit/mailincl.php";
include_once $babInstallPath."utilit/topincl.php";
include_once $babInstallPath."utilit/artincl.php";

function listDrafts()
	{
	global $babBody;
	class temp
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

		function temp()
			{
			global $babDB;
			$this->nametxt = bab_translate("Articles");
			$this->datesubtxt = bab_translate("Submission");
			$this->statustxt = bab_translate("Status");
			$this->proptxt = bab_translate("Properties");
			$this->deletetxt = bab_translate("Delete");
			$this->previewtxt = bab_translate("Preview");
			$this->addtxt = bab_translate("Publish");
			$this->attachmenttxt = bab_translate("Attachments");
			$this->submittxt = bab_translate("Submit");
			$this->urladd = $GLOBALS['babUrlScript']."?tg=artedit&idx=s0";
			$req = "select adt.*, count(adft.id) as total from ".BAB_ART_DRAFTS_TBL." adt left join ".BAB_ART_DRAFTS_FILES_TBL." adft on adft.id_draft=adt.id where id_author='".$GLOBALS['BAB_SESS_USERID']."' and adt.trash !='Y' and adt.idfai='0' and adt.result='".BAB_ART_STATUS_DRAFT."' GROUP BY adt.id order by date_modification desc";
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
				$this->urlname = $GLOBALS['babUrlScript']."?tg=artedit&idx=s1&idart=".$arr['id'];
				$this->propurl = $GLOBALS['babUrlScript']."?tg=artedit&idx=s3&idart=".$arr['id'];
				$this->deleteurl = $GLOBALS['babUrlScript']."?tg=artedit&idx=movet&idart=".$arr['id'];
				$this->previewurl = $GLOBALS['babUrlScript']."?tg=artedit&idx=preview&idart=".$arr['id'];
				$this->name = $arr['title'];
				$this->datesub = $arr['date_submission'] == "0000-00-00 00:00:00"? "":bab_formatDate("%j/%n/%Y %H:%i", bab_mktime($arr['date_submission']));
				$this->datepub = $arr['date_publication'] == "0000-00-00 00:00:00"? "":bab_formatDate("%j/%n/%Y %H:%i", bab_mktime($arr['date_publication']));
				$this->datearch = $arr['date_archiving'] == "0000-00-00 00:00:00"? "":bab_formatDate("%j/%n/%Y %H:%i", bab_mktime($arr['date_archiving']));
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
					$this->bsubmiturl = $GLOBALS['babUrlScript']."?tg=artedit&idx=sub&idart=".$arr['id']."";
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

	$temp = new temp();
	$babBody->babecho( bab_printTemplate($temp, "artedit.html", "draftslist"));
	}


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
			global $babDB;
			$this->nametxt = bab_translate("Articles");
			$this->datesubtxt = bab_translate("Submission");
			$this->proptxt = bab_translate("Properties");
			$this->deletetxt = bab_translate("Delete");
			$this->previewtxt = bab_translate("Preview");
			$this->statustxt = bab_translate("Status");
			$this->emptytxt = bab_translate("Empty");
			$this->restoretxt = bab_translate("Restore");
			$this->attachmenttxt = bab_translate("Attachments");
			$this->urladd = $GLOBALS['babUrlScript']."?tg=artedit&idx=new";
			$req = "select adt.*, count(adft.id) as totalf, count(adnt.id) as totaln from ".BAB_ART_DRAFTS_TBL." adt left join ".BAB_ART_DRAFTS_FILES_TBL." adft on adft.id_draft=adt.id  left join ".BAB_ART_DRAFTS_NOTES_TBL." adnt on adnt.id_draft=adt.id where adt.id_author='".$GLOBALS['BAB_SESS_USERID']."' and adt.trash !='Y' and adt.result!='".BAB_ART_STATUS_DRAFT."' GROUP BY adt.id order by date_submission desc";
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
				$this->urlname = $GLOBALS['babUrlScript']."?tg=artedit&idx=propa&idart=".$arr['id'];
				$this->propurl = $GLOBALS['babUrlScript']."?tg=artedit&idx=prop&idart=".$arr['id'];
				$this->deleteurl = $GLOBALS['babUrlScript']."?tg=artedit&idx=delt&idart=".$arr['id'];
				$this->previewurl = $GLOBALS['babUrlScript']."?tg=artedit&idx=preview&idart=".$arr['id'];
				$this->restoreurl = $GLOBALS['babUrlScript']."?tg=artedit&idx=rests&idart=".$arr['id'];
				$this->name = $arr['title'];
				$this->datesub = $arr['date_submission'] == "0000-00-00 00:00:00"? "":bab_formatDate("%j/%n/%Y %H:%i", bab_mktime($arr['date_submission']));
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
				if( $arr['totaln'] > 0 )
					{
					$this->bnotes = true;
					}
				else
					{
					$this->bnotes = false;
					}
				switch($arr['result'])
					{
					case BAB_ART_STATUS_WAIT:
						$this->status = bab_translate("Waiting");
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
			$this->urladd = $GLOBALS['babUrlScript']."?tg=artedit&idx=empty";
			$req = "select * from ".BAB_ART_DRAFTS_TBL." where id_author='".$GLOBALS['BAB_SESS_USERID']."' and trash !='N' order by date_modification desc";
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
				$this->urlname = $GLOBALS['babUrlScript']."?tg=artedit&idx=view&idart=".$arr['id'];
				$this->restoreurl = $GLOBALS['babUrlScript']."?tg=artedit&idx=restore&idart=".$arr['id'];
				$this->previewurl = $GLOBALS['babUrlScript']."?tg=artedit&idx=preview&idart=".$arr['id'];
				$this->name = $arr['title'];
				$this->datecreate = bab_formatDate("%j/%n/%Y %H:%i", bab_mktime($arr['date_creation']));
				$this->datemodify = bab_formatDate("%j/%n/%Y %H:%i", bab_mktime($arr['date_modification']));
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



function propertiesArticle($idart)
{
	global $babBody;
	class temp
		{
		var $arttxt;

		function temp($idart)
			{
			global $babDB;
			$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$idart."' and id_author='".$GLOBALS['BAB_SESS_USERID']."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$this->idart = $idart;
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

				$this->arttitle = $arr['title'];
				$this->pathname = viewCategoriesHierarchy_txt($arr['id_topic']);
				$this->author = bab_getUserName($arr['id_author']);
				$this->datepub = $arr['date_publication'] == "0000-00-00 00:00:00"? "":bab_formatDate("%j/%n/%Y %H:%i", bab_mktime($arr['date_publication']));
				$this->datearch = $arr['date_archiving'] == "0000-00-00 00:00:00"? "":bab_formatDate("%j/%n/%Y %H:%i", bab_mktime($arr['date_archiving']));
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
					/* show waiting approvers */
					}
				}
			else
				{
				echo bab_translate("Access denied");
				}
			}

		}

	$temp = new temp($idart);
	echo bab_printTemplate($temp, "artedit.html", "propertiesarticles");
}


function showChoiceTopic()
{
	global $babBodyPopup;
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

		function temp()
			{
			global $babBodyPopup, $babBody, $babDB, $idart, $topicid, $articleid, $title, $headtext, $bodytext, $lang;
			$this->count = $babBody->topsub;
			if( $this->count > 0 )
				{
				if(!isset($idart)) { $idart = 0;}
				if(!isset($topicid)) { $topicid = 0;}
				if(!isset($articleid)) { $articleid = 0;}
				if( !isset($title)) { $title = '';}
				if( !isset($headtext)) { $headtext = '';}
				if( !isset($bodytext)) { $bodytext = '';}
				if( !isset($lang)) { $lang = '';}
				$this->idart = $idart;
				$this->idtopicsel = $topicid;
				$this->title = htmlentities($title);
				$this->headtext = htmlentities($headtext);
				$this->bodytext = htmlentities($bodytext);
				$this->lang = htmlentities($lang);
				$babBodyPopup->title = bab_translate("Choose the topic");
				$this->res = $babDB->db_query("select * from ".BAB_TOPICS_TBL." where id IN (".implode(',', $babBody->topsub).") order by id_cat");
				$this->count = $babDB->db_num_rows($this->res);
				$this->steptitle = bab_translate("list of topics");
				$this->nexttxt = bab_translate("Next");
				$this->savetxt = bab_translate("Save and close");
				$this->canceltxt = bab_translate("Cancel");
				}

			if( $this->count == 0 )
				{
				$babBodyPopup->msgerror = bab_translate("Access denied");
				}
			}

		function getnexttopic()
			{
			global $babDB, $babBody;
			static $i = 0;
			if( $i < $this->count)
				{
				$arr = $babDB->db_fetch_array($this->res);
				$this->idtopic = $arr['id'];
				$this->topicname = $arr['category'];
				$this->topicpath = viewCategoriesHierarchy_txt($arr['id']);
				$this->description = $arr['description'];
				if( $this->idtopicsel == $this->idtopic )
					{
					$this->topicchecked = 'checked';
					}
				else
					{
					$this->topicchecked = '';
					}
				$i++;
				return true;
				}
			else
				return false;

			}

		}
	$temp = new temp();
	$babBodyPopup->babecho(bab_printTemplate($temp, "artedit.html", "topicchoicestep"));
}

function showEditArticle()
{
	global $babBodyPopup;
	class temp
		{
		var $topicname;
		var $topicpath;

		function temp()
			{
			global $babBodyPopup, $babBody, $babDB, $idart, $topicid, $articleid, $title, $headtext, $bodytext, $lang;
			if(!isset($idart)) { $idart = 0;}
			if(!isset($topicid)) { $topicid = 0;}
			if(!isset($articleid)) { $articleid = 0;}
			$this->access = false;
			$this->bprev = false;
			$this->warnmessage = '';
			if( isset($title) || isset($headtext) || isset($bodytext) || isset($lang) )
				{
				$this->content = bab_editArticle($title, $headtext, $bodytext, $lang, '');
				}
			else
				{
				$this->content = '';
				}

			if( $topicid != 0 && $idart != 0 )
				{
				list($drafidtopic) = $babDB->db_fetch_array($babDB->db_query("select id_topic from ".BAB_ART_DRAFTS_TBL." where id='".$idart."'"));
				if( $topicid != $drafidtopic )
					{
					$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set id_topic='".$topicid."', id_article='0', restriction='', notify_members='N', hpage_public='N', hpage_private='N', date_submission='0000-00-00 00:00:00', date_publication='0000-00-00 00:00:00', date_archiving='0000-00-00 00:00:00'  where id='".$idart."'");
					$articleid = 0;
					}
				}
			
			if( $this->content == '' && ($idart != 0 || $topicid != 0 || $articleid != 0) )
				{
				if( $idart != 0 )
					{
					$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$idart."' and id_author='".$GLOBALS['BAB_SESS_USERID']."'");
					if( $res && $babDB->db_num_rows($res) > 0 )
						{
						$this->access = true;
						$arr = $babDB->db_fetch_array($res);
						$topicid = $arr['id_topic'];
						$articleid = $arr['id_article'];
						$this->content = bab_editArticle($arr['title'], $arr['head'], $arr['body'], $arr['lang'], "");
						}
					}
				elseif( $articleid != 0 )
					{
					$res = $babDB->db_query("select at.*, tt.allow_update, tt.allow_manupdate from ".BAB_ARTICLES_TBL." at left join ".BAB_TOPICS_TBL." tt on at.id_topic=tt.id  where at.id='".$articleid."'");
					if( $res && $babDB->db_num_rows($res) == 1 )
						{
						$arr = $babDB->db_fetch_array($res);
						if( ($arr['allow_update'] != '0' && $arr['id_author'] == $GLOBALS['BAB_SESS_USERID'] ) || (count($babBody->topmod) > 0 && in_array($arr['id_topic'], $babBody->topmod )) || ($arr['allow_manupdate'] != '0' && bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $arr['id_topic'])))
							{
							$topicid = $arr['id_topic'];
							$this->access = true;
							}
						if( empty($this->content))
							{
							$this->content = bab_editArticle($arr['title'], $arr['head'], $arr['body'], $arr['lang'], "");
							}
						}
					}
				elseif( $topicid != 0 )
					{
					if( count($babBody->topsub) > 0 && in_array($topicid, $babBody->topsub ))
						{
						$res = $babDB->db_query("select tt.article_tmpl from ".BAB_TOPICS_TBL." tt  where id='".$topicid."'");
						if( $res && $babDB->db_num_rows($res) == 1 )
							{
							$arr = $babDB->db_fetch_array($res);
							$this->access = true;
							if( empty($this->content))
								{
								$this->content = bab_editArticle('', '', '', $GLOBALS['babLanguage'], $arr['article_tmpl']);
								}
							}
						}
					}
				}
			else
				{
				if( count($babBody->topsub) > 0 )
					{
					if( empty($this->content))
						{
						$this->content = bab_editArticle('', '', '', $GLOBALS['babLanguage'], '');
						}
					$this->access = true;
					}
				}

			if( $this->access )
				{
				$this->submittxt = bab_translate("Finish");
				$this->previoustxt = bab_translate("Previous");
				$this->nexttxt = bab_translate("Next");
				$this->savetxt = bab_translate("Save and close");
				$this->canceltxt = bab_translate("Cancel");
				$this->confirmsubmit = bab_translate("Are you sure you want to submit this article?");
				$this->confirmcancel = bab_translate("Are you sure you want to remove this draft?");
				$this->idart = $idart;
				$this->idtopic = $topicid;
				$this->idarticle = $articleid;
				if( $this->idarticle )
					{
					$this->bprev = false;
					}
				else
					{
					$this->bprev = true;
					}
				if( $this->idtopic != 0 )
					{
					$this->bsubmit = true;
					$this->steptitle = viewCategoriesHierarchy_txt($this->idtopic);
					}
				else
					{
					$this->bsubmit = false;
					$this->steptitle = bab_translate("No topic");
					}

				$this->bupprobchoice = false;

				if( $this->idarticle != 0 || $this->idtopic != 0 )
					{
					if( $this->idarticle != 0 )
						{
						$res = $babDB->db_query("select at.id_topic, at.id_author, tt.allow_update, tt.allow_manupdate, tt.idsa_update as saupdate, adt.approbation from ".BAB_ARTICLES_TBL." at left join ".BAB_TOPICS_TBL." tt on at.id_topic=tt.id left join ".BAB_ART_DRAFTS_TBL." adt on at.id=adt.id_article where at.id='".$this->idarticle."'");
						$arr = $babDB->db_fetch_array($res);
						if( $arr['saupdate'] != 0 && ( $arr['allow_update'] == '2' && $arr['id_author'] == $GLOBALS['BAB_SESS_USERID']) || ( $arr['allow_manupdate'] == '2' && bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $arr['id_topic'])))
							{
							$this->bupprobchoice = true;
							$this->yesapprobtxt = bab_translate("With approbation");
							$this->noapprobtxt = bab_translate("Without approbation");
							switch( $arr['approbation'] )
								{
								case 1:
									$this->yesapprobsel = "selected";
									$this->noapprobsel = "";
									break;
								case 2: 
									$this->yesapprobsel = "";
									$this->noapprobsel = "selected";
									break;
								default: 
									$this->yesapprobsel = "";
									$this->noapprobsel = "";
									break;
								}
							}
						}
					else
						{
						$res = $babDB->db_query("select tt.idsaart as saupdate from ".BAB_TOPICS_TBL." tt where tt.id='".$this->idtopic."'");
						$arr = $babDB->db_fetch_array($res);
						}

					if( $arr['saupdate'] != 0 )
						{
						$this->warnmessage = bab_translate("Note: Articles are moderate and consequently your article will not be visible immediately");
						}
					else
						{
						$this->warnmessage = "";
						}
					}
				}
			else
				{
				$babBodyPopup->msgerror = bab_translate("Access denied");
				}
			}
		}

	$temp = new temp();
	$babBodyPopup->babecho(bab_printTemplate($temp, "artedit.html", "editarticlestep"));
}

function showPreviewArticle($idart)
{
	global $babBodyPopup;
	class temp
		{
		function temp($idart)
			{
			global $babBodyPopup, $babBody, $babDB, $BAB_SESS_USERID;
			$babBodyPopup->title = bab_translate("Preview article");
			$this->access = false;
			$res = $babDB->db_query("select id_topic, id_article, title, head, approbation from ".BAB_ART_DRAFTS_TBL." where id='".$idart."' and id_author='".$BAB_SESS_USERID."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$arr = $babDB->db_fetch_array($res);
				$this->access = true;
				}

			if( $this->access )
				{
				$this->submittxt = bab_translate("Finish");
				$this->previoustxt = bab_translate("Previous");
				$this->nexttxt = bab_translate("Next");
				$this->savetxt = bab_translate("Save and close");
				$this->canceltxt = bab_translate("Cancel");
				$this->confirmsubmit = bab_translate("Are you sure you want to submit this article?");
				$this->confirmcancel = bab_translate("Are you sure you want to remove this draft?");
				$this->idart = $idart;
				if( $arr['id_topic'] != 0 )
					{
					$this->steptitle = viewCategoriesHierarchy_txt($arr['id_topic']);
					}
				else
					{
					$this->steptitle = bab_translate("No topic");
					}

				if( $arr['id_topic'] != 0 && !empty($arr['title']) && !empty($arr['head']))
					{
					$this->bsubmit = true;
					}
				else
					{
					$this->bsubmit = false;
					}
				
				if( $arr['id_article'] != 0 )
					{
					$res = $babDB->db_query("select at.id_topic, at.id_author, tt.allow_update, tt.allow_manupdate, tt.idsa_update as saupdate from ".BAB_ARTICLES_TBL." at left join ".BAB_TOPICS_TBL." tt on at.id_topic=tt.id where at.id='".$arr['id_article']."'");
					$rr = $babDB->db_fetch_array($res);
					if( $rr['saupdate'] != 0 && ( $rr['allow_update'] == '2' && $rr['id_author'] == $GLOBALS['BAB_SESS_USERID']) || ( $arr['allow_manupdate'] == '2' && bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $rr['id_topic'])))
						{
						$this->bupprobchoice = true;
						$this->yesapprobtxt = bab_translate("With approbation");
						$this->noapprobtxt = bab_translate("Without approbation");
						switch( $arr['approbation'] )
							{
							case 1:
								$this->yesapprobsel = "selected";
								$this->noapprobsel = "";
								break;
							case 2: 
								$this->yesapprobsel = "";
								$this->noapprobsel = "selected";
								break;
							default: 
								$this->yesapprobsel = "";
								$this->noapprobsel = "";
								break;
							}
						}
					}
				else
					{
					$this->bupprobchoice = false;
					}

				$this->content = bab_previewArticleDraft($idart, 0);
				}
			else
				{
				$babBodyPopup->msgerror = bab_translate("Access denied");
				}
			}
		}
	$temp = new temp($idart);
	$babBodyPopup->babecho(bab_printTemplate($temp, "artedit.html", "previewarticlestep"));
}


function showSetArticleProperties($idart)
	{
	global $babBodyPopup;
	class temp
		{

		function temp($idart)
			{
			global $babBodyPopup, $babBody, $babDB, $BAB_SESS_USERID, $topicid;
			$this->access = false;

			$req = "select * from ".BAB_ART_DRAFTS_TBL." where id_author='".$GLOBALS['BAB_SESS_USERID']."' and id='".$idart."'";
			$res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($res);
			if( $this->count > 0 )
				{
				$this->access = true;
				$this->idart = $idart;
				$arr = $babDB->db_fetch_array($res);
				$this->submittxt = bab_translate("Finish");
				$this->previoustxt = bab_translate("Previous");
				$this->savetxt = bab_translate("Save and close");
				$this->canceltxt = bab_translate("Cancel");
				$this->topictxt = bab_translate("Topic");
				$this->titletxt = bab_translate("Title");
				$this->confirmsubmit = bab_translate("Are you sure you want to submit this article?");
				$this->confirmcancel = bab_translate("Are you sure you want to remove this draft?");
				$this->idart = $idart;
				if( $arr['id_topic'] != 0 && bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $arr['id_topic']))
					{
					$this->steptitle = viewCategoriesHierarchy_txt($arr['id_topic']);
					}
				else
					{
					$arr['id_topic'] = 0;
					$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set id_topic='0' where id='".$idart."'");
					$this->steptitle = bab_translate("No topic");
					}

				if( $arr['id_topic'] != 0 && !empty($arr['title']) && !empty($arr['head']))
					{
					$this->bsubmit = true;
					}
				else
					{
					$this->bsubmit = false;
					}

				if( $arr['id_article'] != 0 )
					{
					$this->bshowtopics = false;
					$res = $babDB->db_query("select at.id_topic, at.id_author, tt.allow_update, tt.allow_manupdate, tt.idsa_update as saupdate from ".BAB_ARTICLES_TBL." at left join ".BAB_TOPICS_TBL." tt on at.id_topic=tt.id where at.id='".$arr['id_article']."'");
					$rr = $babDB->db_fetch_array($res);
					if( $rr['saupdate'] != 0 && ( $rr['allow_update'] == '2' && $rr['id_author'] == $GLOBALS['BAB_SESS_USERID']) || ( $arr['allow_manupdate'] == '2' && bab_isAccessValid(BAB_TOPICSMAN_GROUPS_TBL, $rr['id_topic'])))
						{
						$this->bupprobchoice = true;
						$this->yesapprobtxt = bab_translate("With approbation");
						$this->noapprobtxt = bab_translate("Without approbation");
						switch( $arr['approbation'] )
							{
							case 1:
								$this->yesapprobsel = "selected";
								$this->noapprobsel = "";
								break;
							case 2: 
								$this->yesapprobsel = "";
								$this->noapprobsel = "selected";
								break;
							default: 
								$this->yesapprobsel = "";
								$this->noapprobsel = "";
								break;
							}
						}
					}
				else
					{
					$this->bupprobchoice = false;
					$this->bshowtopics = true;
					}

				$this->draftname = $arr['title'];

				if( count($babBody->topsub) > 0 )
					{
					$this->restopics = $babDB->db_query("select tt.id, tt.category, tt.restrict_access, tct.title, tt.notify from ".BAB_TOPICS_TBL." tt LEFT JOIN ".BAB_TOPICS_CATEGORIES_TBL." tct on tct.id=tt.id_cat where tt.id IN(".implode(',', $babBody->topsub).")");
					$this->counttopics = $babDB->db_num_rows($this->restopics);
					}
				else
					{
					$this->counttopics = 0;
					}

				$this->allowpubdates  = false;
				$this->notifymembers = false;
				$this->restrictaccess = false;
				$this->allowhpages = false;
				$this->allowattachments  = false;

				$req = "select * from ".BAB_CALOPTIONS_TBL." where id_user='".$GLOBALS['BAB_SESS_USERID']."'";
				$res = $babDB->db_query($req);
				$this->elapstime = 5;
				$this->ampm = false;
				if( $res && $babDB->db_num_rows($res))
					{
					$rr = $babDB->db_fetch_array($res);
					if( $rr['ampm'] == "Y")
						{
						$this->ampm = true;
						}
					}


				$this->datesubtitle = bab_translate("Date of submission");
				$this->datesuburl = $GLOBALS['babUrlScript']."?tg=month&callback=dateSub&ymin=0&ymax=2";
				$this->datesubtxt = bab_translate("Submission date");
				$this->invaliddate = bab_translate("ERROR: End date must be older");
				$this->invaliddate = str_replace("'", "\'", $this->invaliddate);
				$this->invaliddate = str_replace('"', "'+String.fromCharCode(34)+'",$this->invaliddate);
				$this->cdateecheck = '';
				if( $arr['date_submission'] != '0000-00-00 00:00:00' )
					{
					$this->cdatescheck = 'checked';
					$rr = explode(" ", $arr['date_submission']);
					$rr0 = explode("-", $rr[0]);
					$rr1 = explode(":", $rr[1]);
					$this->yearsub = $rr0[0];
					$this->monthsub = $rr0[1];
					$this->daysub = $rr0[2];
					$this->timesub = $rr1[0].":".$rr1[1];
					}
				else
					{
					$this->cdatescheck = '';
					$this->yearsub = date("Y");
					$this->monthsub = date("n");
					$this->daysub = date("j");
					$this->timesub = "00:00";
					}

				$this->daysel = $this->daysub;
				$this->monthsel = $this->monthsub;
				$this->yearsel = $this->yearsub - date("Y") + 1;
				$this->timesel = $this->timesub;

				$this->drafttopic = $topicid == '' ? $arr['id_topic']: $topicid;
				/* Traiter le cas de modification d'article */
				if( count($babBody->topsub) == 0 || !in_array($this->drafttopic, $babBody->topsub ))
					{
					$this->drafttopic = 0;
					}

				$this->topicpath = '';
				if( $this->drafttopic != 0 )
					{
					$this->topicpath = viewCategoriesHierarchy_txt($this->drafttopic);
					$arrtop = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_TOPICS_TBL." where id='".$this->drafttopic."'"));

					if( $arrtop['notify'] == 'Y')
						{
						$this->notifymembers = true;
						$this->notifytitle = bab_translate("Notification");
						$this->notifmtxt = bab_translate("Notify users once the article is published ");
						if( $arr['notify_members'] == 'Y')
							{
							$this->cnotifmcheck = 'checked';
							}
						else
							{
							$this->cnotifmcheck = '';
							}
						}

					if( $arrtop['restrict_access'] == 'Y' )
						{
						$this->restrictaccess = true;
						$this->restrictiontitletxt = bab_translate("Access restriction");
						$this->operatortxt = bab_translate("Operator");
						$this->ortxt = bab_translate("Or");
						$this->andtxt = bab_translate("And");
						$this->groupstxt = bab_translate("Groups");
						$this->restrictiontxt = bab_translate("Access restriction");
						$this->norestricttxt = bab_translate("No restriction");
						$this->yesrestricttxt = bab_translate("Groups");
						$this->resgrp = $babDB->db_query("select * from ".BAB_TOPICSVIEW_GROUPS_TBL." where id_object='".$this->drafttopic."' and id_group > '2'");
						if( $this->resgrp )
							{
							$this->countgrp = $babDB->db_num_rows($this->resgrp);
							}
						else
							{
							$this->countgrp = 0;
							}
						if( strchr($arr['restriction'], "&"))
							{
							$this->arrrest = explode('&', $arr['restriction']);
							$this->operatororysel = '';
							$this->operatorornsel = 'selected';
							}
						else if( strchr($arr['restriction'], ","))
							{
							$this->arrrest = explode(',', $arr['restriction']);
							}
						else
							$this->arrrest = array($arr['restriction']);

						if( empty($arr['restriction']))
							{
							$this->norestrictsel = 'selected';
							$this->yesrestrictsel = '';
							}
						else
							{
							$this->norestrictsel = '';
							$this->yesrestrictsel = 'selected';
							}
						}

					if( $arrtop['allow_hpages'] == 'Y' )
						{
						$this->allowhpages = true;
						$this->hpagestitle = bab_translate("Home pages");
						$this->hpage0txt = bab_translate("Add to unregistered users home page");
						$this->hpage1txt = bab_translate("Add to registered users home page");
						if( $arr['hpage_private'] == 'Y' )
							{
							$this->chpage0check = "checked";
							}
						else
							{
							$this->chpage0check = "";
							}

						if( $arr['hpage_public'] == 'Y' )
							{
							$this->chpage1check = "checked";
							}
						else
							{
							$this->chpage1check = "";
							}
						}

					if( $arrtop['allow_pubdates'] == 'Y' )
						{
						$this->allowpubdates  = true;
						$this->datepubtitle = bab_translate("Dates of publication and archiving");
						$this->datebeginurl = $GLOBALS['babUrlScript']."?tg=month&callback=dateBegin&ymin=0&ymax=2";
						$this->datebegintxt = bab_translate("Publication date");
						$this->dateendurl = $GLOBALS['babUrlScript']."?tg=month&callback=dateEnd&ymin=0&ymax=2";
						$this->dateendtxt = bab_translate("Archiving date");
						if( $arr['date_publication'] != '0000-00-00 00:00:00' )
							{
							$this->cdatebcheck = 'checked';
							$rr = explode(" ", $arr['date_publication']);
							$rr0 = explode("-", $rr[0]);
							$rr1 = explode(":", $rr[1]);
							$this->yearbegin = $rr0[0];
							$this->monthbegin = $rr0[1];
							$this->daybegin = $rr0[2];
							$this->timebegin = $rr1[0].":".$rr1[1];
							}
						else
							{
							$this->cdatebcheck = '';
							$this->yearbegin = date("Y");
							$this->monthbegin = date("n");
							$this->daybegin = date("j");
							$this->timebegin = "00:00";
							}
						if( $arr['date_archiving'] != '0000-00-00 00:00:00' )
							{
							$this->cdateecheck = 'checked';
							$rr = explode(" ", $arr['date_archiving']);
							$rr0 = explode("-", $rr[0]);
							$rr1 = explode(":", $rr[1]);
							$this->yearend = $rr0[0];
							$this->monthend = $rr0[1];
							$this->dayend = $rr0[2];
							$this->timeend = $rr1[0].":".$rr1[1];
							}
						else
							{
							$this->cdateecheck = '';
							$this->yearend = date("Y");
							$this->monthend = date("n");
							$this->dayend = date("j");
							$this->timeend = "00:00";
							}
						}

					if( $arrtop['allow_attachments'] == 'Y' )
						{
						$this->allowattachments  = true;
						$this->addtxt = bab_translate("Add");
						$this->filetxt = bab_translate("File");
						$this->desctxt = bab_translate("Description");
						$this->filetitle = bab_translate("Associated documents");
						$this->deletetxt = bab_translate("Delete");
						$this->resfiles = $babDB->db_query("select id, name, description from ".BAB_ART_DRAFTS_FILES_TBL." where id_draft='".$idart."'");
						$this->countfiles = $babDB->db_num_rows($this->resfiles);
						if( $this->countfiles > 0 )
							{
							$this->warnfilemessage = bab_translate("Warning! If you change topic, you can lost associated documents");
							}
						else
							{
							$this->warnfilemessage = '';
							}
						}
					}
				}
			else
				{
				$message = bab_translate("Access denied");
				}

			if(!empty($message))
				{
				$this->msgerror = $message;
				$this->message = bab_printTemplate($this,"warning.html", "texterror");
				}
			else
				{
				$this->message = '';
				}
			}

		function getnexttopic()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->counttopics)
				{
				$arr = $babDB->db_fetch_array($this->restopics);
				$this->topicname = $arr['category'];
				$this->categoryname = $arr['title'];
				$this->idtopic = $arr['id'];
				if( $this->drafttopic == $arr['id'] )
					{
					$this->selected = 'selected';
					}
				else
					{
					$this->selected = '';
					}
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnextgroup()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countgrp)
				{
				$arr = $babDB->db_fetch_array($this->resgrp);
				$this->grpid = $arr['id_group'];
				$this->grpname = bab_getGroupName($arr['id_group']);
				if( in_array($this->grpid, $this->arrrest))
					$this->grpcheck = 'checked';
				else
					$this->grpcheck = '';
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}

			}

		function getnextfile()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countfiles)
				{
				$arr = $babDB->db_fetch_array($this->resfiles);
				$this->urlfile = $GLOBALS['babUrlScript']."?tg=artedit&idx=getf&idart=".$this->idart."&idf=".$arr['id'];
				$this->deleteurl = $GLOBALS['babUrlScript']."?tg=artedit&idx=s3&updstep3=delf&idart=".$this->idart."&idf=".$arr['id'];
				$this->name = $arr['name'];
				$this->docdesc = $arr['description'];
				$i++;
				return true;
				}
			else
				return false;

			}

		function getnextday()
			{
			static $i = 1, $p=0;

			if( $i <= date("t"))
				{
				$this->dayid = $i;
				if( $this->daysel == $i)
					{
					$this->selected = "selected";
					}
				else
					$this->selected = "";
				
				$i++;
				return true;
				}
			else
				{
				if( $p == 0 && $this->allowpubdates )
					{
					$this->daysel = $this->daybegin;
					$p++;
					}
				elseif( $this->allowpubdates )
					{
					$this->daysel = $this->dayend;
					}
				$i = 1;
				return false;
				}

			}

		function getnextmonth()
			{
			global $babMonths;
			static $i = 1, $p;

			if( $i < 13)
				{
				$this->monthid = $i;
				$this->monthname = $babMonths[$i];
				if( $this->monthsel == $i)
					{
					$this->selected = "selected";
					}
				else
					$this->selected = "";

				$i++;
				return true;
				}
			else
				{
				if( $p == 0  && $this->allowpubdates )
					{
					$this->monthsel = $this->monthbegin;
					$p++;
					}
				elseif( $this->allowpubdates )
					{
					$this->monthsel = $this->monthend;
					}
				$i = 1;
				return false;
				}

			}
		function getnextyear()
			{
			static $i = 0, $p;
			if( $i < 3)
				{
				$this->yearid = $i+1;
				$this->yearidval = date("Y") + $i;
				if( $this->yearsel == $this->yearid )
					$this->selected = "selected";
				else
					$this->selected = "";
				$i++;
				return true;
				}
			else
				{
				if( $p == 0  && $this->allowpubdates )
					{
					$this->yearsel = $this->yearbegin - date("Y") + 1;
					$p++;
					}
				elseif( $this->allowpubdates )
					{
					$this->yearsel = $this->yearend - date("Y") + 1;
					}
				$i = 0;
				return false;
				}

			}

		function getnexttime()
			{

			static $i = 0, $p = 0;

			if( $i < 1440/$this->elapstime)
				{
				$this->timeval = sprintf("%02d:%02d", ($i*$this->elapstime)/60, ($i*$this->elapstime)%60);
				if( $this->ampm )
					$this->time = bab_toAmPm($this->timeval);
				else
					$this->time = $this->timeval;
				if( $this->timeval == $this->timesel )
					{
					$this->selected = "selected";
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
				if( $p == 0  && $this->allowpubdates )
					{
					$this->timesel = $this->timebegin;
					$p++;
					}
				elseif( $this->allowpubdates )
					{
					$this->timesel = $this->timeend;
					}
				$i = 0;
				return false;
				}

			}
		}

	$temp = new temp($idart);
	$babBodyPopup->babecho(bab_printTemplate($temp, "artedit.html", "propertiesarticlestep"));
	}



function getDocumentArticleDraft( $idart, $idf )
	{
	global $babDB, $babBody, $BAB_SESS_USERID;
	$access = false;
	$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$idart."' and id_author='".$BAB_SESS_USERID."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$access = true;
		}

	$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_FILES_TBL." where id='".$idf."' and id_draft='".$idart."'");
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

	$mime = "application/octet-stream";
	if ($ext = strrchr($file,"."))
		{
		$ext = substr($ext,1);
		$db = $GLOBALS['babDB'];
		$res = $db->db_query("select * from ".BAB_MIME_TYPES_TBL." where ext='".$ext."'");
		if( $res && $db->db_num_rows($res) > 0)
			{
			$rr = $db->db_fetch_array($res);
			$mime = $rr['mimetype'];
			}
		}
	if( strtolower(bab_browserAgent()) == "msie")
		header('Cache-Control: public');
	$inl = "";
	if( $inl == "1" )
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

function delDocumentArticleDraft( $idart, $idf )
	{
	global $babDB, $babBody, $BAB_SESS_USERID;
	$access = false;
	$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$idart."' and id_author='".$BAB_SESS_USERID."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$access = true;
		}

	if( !$access )
		{
		echo bab_translate("Access denied");
		return;
		}

	$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_FILES_TBL." where id='".$idf."' and id_draft='".$idart."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arr = $babDB->db_fetch_array($res);
		$fullpath = bab_getUploadDraftsPath();
		$fullpath .= $arr['id_draft'].",".$arr['name'];
		unlink($fullpath);
		$babDB->db_query("delete from ".BAB_ART_DRAFTS_FILES_TBL." where id='".$idf."'");
		}
	}


function deleteDraft($idart)
	{
	global $babDB, $BAB_SESS_USERID;
	$res = $babDB->db_query("select result, id_article from ".BAB_ART_DRAFTS_TBL." where id='".$idart."' and id_author='".$BAB_SESS_USERID."'");
	if( $res && $babDB->db_num_rows($res) == 1 )
		{
		$arr = $babDB->db_fetch_array($res);
		if( $arr['result'] != BAB_ART_STATUS_WAIT )
			{
			if( $arr['id_article'] != 0 )
				{
				$babDB->db_query("insert into ".BAB_ART_LOG_TBL." (id_article, id_author, date_log, action_log) values ('".$arr['id_article']."', '".$BAB_SESS_USERID."', now(), 'unlock')");		
				}
			bab_deleteArticleDraft($idart);
			}
		}
	}

function emptyTrash()
	{
	global $babDB, $BAB_SESS_USERID;
	$res = $babDB->db_query("select id from ".BAB_ART_DRAFTS_TBL." where trash='Y' and id_author='".$BAB_SESS_USERID."'");
	while( $arr = $babDB->db_fetch_array($res))
		{
		deleteDraft($arr['id']);
		}
	}

function moveArticleDraftToTrash($idart)
	{
	global $babDB, $BAB_SESS_USERID;
	$res = $babDB->db_query("select id, result, id_article from ".BAB_ART_DRAFTS_TBL." where trash='N' and id='".$idart."' and id_author='".$BAB_SESS_USERID."'");
	if( $res && $babDB->db_num_rows($res) == 1 )
		{
		$arr = $babDB->db_fetch_array($res);
		if( $arr['result'] != BAB_ART_STATUS_WAIT )
			{
			if( $arr['id_article'] != 0 )
				{
				$babDB->db_query("insert into ".BAB_ART_LOG_TBL." (id_article, id_author, date_log, action_log) values ('".$arr['id_article']."', '".$BAB_SESS_USERID."', now(), 'unlock')");		
				}
			$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set id_article='0', trash='Y' where id='".$idart."' and id_author='".$BAB_SESS_USERID."'");
			}
		}
	}

function restoreArticleDraft($idart)
	{
	global $babDB;
	$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set trash='N' where id='".$idart."' and id_author='".$GLOBALS['BAB_SESS_USERID']."'");
	list($nbtrash) = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_ART_DRAFTS_TBL." where id_author='".$GLOBALS['BAB_SESS_USERID']."' and trash !='N'"));
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
	$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set trash='N', result='', date_submission='0000-00-00 :00:00:00' where id='".$idart."' and id_author='".$GLOBALS['BAB_SESS_USERID']."'");
	list($nbsub) = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_ART_DRAFTS_TBL." where id_author='".$GLOBALS['BAB_SESS_USERID']."' and result !='".BAB_ART_STATUS_DRAFT."'"));
	if( $nbsub > 0 )
		{
		return false;
		}
	else
		{
		return true;
		}
	}


function editArticleDraft($idart, $title, $headtext, $bodytext, $lang, $message)
	{
	global $babBodyPopup;
	class temp
		{
		var $content;
		var $idart;

		function temp($idart, $title, $headtext, $bodytext, $lang, $message)
			{
			global $babDB, $babBodyPopup, $BAB_SESS_USERID;
			$this->idart = $idart;
			if(!empty($message))
				{
				$babBodyPopup->msgerror = $message;
				}
			else
				{
				$babBodyPopup->message = '';
				}
			$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$idart."' and id_author='".$BAB_SESS_USERID."'");
			if( $res && $babDB->db_num_rows($res) > 0 )
				{
				$this->updatetxt = bab_translate("Update");
				if( !empty($title) || !empty($headtext) || !empty($bodytext) || !empty($lang) )
					{
					$this->content = bab_editArticle($title, $headtext, $bodytext, $lang, '');
					}
				else
					{
					$arr = $babDB->db_fetch_array($res);
					$this->content = bab_editArticle($arr['title'], $arr['head'], $arr['body'], '', '');
					}
				}
			else
				{
				$this->content = bab_translate("Access denied");
				}
			}

		}

	$temp = new temp($idart, $title, $headtext, $bodytext, $lang, $message);
	$babBodyPopup->babecho(bab_printTemplate($temp, "artedit.html", "editdraft"));
	}

function previewArticleDraft($idart)
	{
	global $babBody, $babDB, $BAB_SESS_USERID;
	$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$idart."' and id_author='".$BAB_SESS_USERID."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arr = $babDB->db_fetch_array($res);
		class temp
			{
			var $content;

			function temp($idart)
				{
				$this->content = bab_previewArticleDraft($idart, 0);
				}
			}

		$temp = new temp($idart);
		echo bab_printTemplate($temp, "artedit.html", "previewarticle");
		}
	else
		{
		echo bab_translate("Access denied");
		}
	}


function updateArticleDraft($idart, $title, $headtext, $bodytext, $lang, $approbid, &$message)
{
	global $babDB, $BAB_SESS_USERID, $babBody ;
	include_once $GLOBALS['babInstallPath']."utilit/imgincl.php";

	$title = trim($title);
	$bodytext = trim($bodytext);
	$headtext = trim($headtext);

	if( empty($title))
		{
		$message = bab_translate("ERROR: You must provide a title");
		return false;
		}

	if( empty($headtext))
		{
		$message = bab_translate("ERROR: You must provide a head for your article");
		return false;
		}

	if($lang == '') { $lang = $GLOBALS['babLanguage']; }

	if( !strcasecmp($bodytext, "<P>&nbsp;</P>"))
		{
		$bodytext = "";
		}

	if( bab_isMagicQuotesGpcOn())
		{
		$headtext = stripslashes($headtext);
		$bodytext = stripslashes($bodytext);
		$title = stripslashes($title);
		}

	$ar = array();
	$headtext = imagesReplace($headtext, $idart."_draft_", $ar);
	$bodytext = imagesReplace($bodytext, $idart."_draft_", $ar);

	$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set title='".addslashes($title)."', head='".addslashes(bab_stripDomainName($headtext))."', body='".addslashes(bab_stripDomainName($bodytext))."', date_modification=now(), lang='" .$lang. "', approbation='".$approbid."' where id='".$idart."'");
	return true;
}


function addDocumentArticleDraft($idart, $docf_name, $doc_f, $description, &$message)
{
	global $babDB, $BAB_SESS_USERID, $babMaxFileSize;
	$res = $babDB->db_query("select * from ".BAB_ART_DRAFTS_TBL." where id='".$idart."' and id_author='".$BAB_SESS_USERID."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$doc = '';
		if( !empty($docf_name) && $docf_name != "none")
			{
			$size = filesize($doc_f);
			if( $size > $GLOBALS['babMaxFileSize'])
				{
				$message= bab_translate("The file was greater than the maximum allowed") ." :". $GLOBALS['babMaxFileSize'];
				return false;
				}
			include_once $GLOBALS['babInstallPath']."utilit/fileincl.php";
			$totalsize = getDirSize($GLOBALS['babUploadPath']);

			if( $size + $totalsize > $GLOBALS['babMaxTotalSize'])
				{
				$message = bab_translate("There is not enough free space");
				return false;
				}

			$filename = trim($docf_name);
			if( bab_isMagicQuotesGpcOn())
				{
				$filename = stripslashes($filename);
				$description = stripslashes($description);
				}

			if( isset($GLOBALS['babFileNameTranslation']))
				{
				$filename = strtr($filename, $GLOBALS['babFileNameTranslation']);
				}

			$osfname = $idart.",".$filename;
			$path = bab_getUploadDraftsPath();
			if( $path === false )
				{
				$message = bab_translate("Can't create directory");
				return false;
				}

			if( file_exists($path.$osfname))
				{
				$message = bab_translate("A file with the same name already exists");
				return false;
				}

			if( !get_cfg_var('safe_mode'))
				{
				set_time_limit(0);
				}
			if( !move_uploaded_file($doc_f, $path.$osfname))
				{
				$babBody->msgerror = bab_translate("The file could not be uploaded");
				return false;
				}

			if( !bab_isMagicQuotesGpcOn())
				{
				$filename = addslashes($filename);
				$description = addslashes($description);
				}

			$babDB->db_query("insert into ".BAB_ART_DRAFTS_FILES_TBL." (id_draft, name, description) values ('" .$idart. "', '".$filename."','".$description."')");
			return true;

			}
		else
			{
			$message = bab_translate("Please select a file to upload");
			return false;
			}	
		}
	return false;
}


function updatePropertiesArticleDraft()
{
	global $babBody, $babDB, $BAB_SESS_USERID, $idart, $topicid, $cdateb, $cdatee, $cdates, $yearbegin, $monthbegin, $daybegin, $timebegin, $yearend, $monthend, $dayend, $timeend, $yearsub, $monthsub, $daysub, $timesub, $restriction, $hpage0, $hpage1, $notifm, $approbid;

	$date_sub = "0000-00-00 00:00";
	$date_pub = "0000-00-00 00:00";
	$date_arch = "0000-00-00 00:00";
	if( isset($cdateb)) 
		{
		$date_pub = sprintf("%04d-%02d-%02d %s:00", date("Y") + $yearbegin - 1, $monthbegin, $daybegin, $timebegin);
		}
	if( isset($cdatee)) 
		{ 
		$date_arch = sprintf("%04d-%02d-%02d %s:00", date("Y") + $yearend - 1, $monthend, $dayend, $timeend);
		}
	if( isset($cdates)) 
		{
		$date_sub = sprintf("%04d-%02d-%02d %s:00", date("Y") + $yearsub - 1, $monthsub, $daysub, $timesub);
		}

	if( isset($restriction) && !empty($restriction))
		{
		if( isset($grpids) && count($grpids) > 0)
			{
			$restriction = implode($operator, $grpids);
			}
		}
	else
		{
		$restriction = '';
		}

	if( !isset($hpage0)) { $hpage0 = 'N';} 
	if( !isset($hpage1)) { $hpage1 = 'N';} 
	if( !isset($notifm)) { $notifm = 'N';} 
	if( !isset($approbid)) { $approbid = '0';} 

	/* Traiter le cas de modification d'article */
	if( count($babBody->topsub) == 0 || !in_array($topicid, $babBody->topsub ))
		{
		$topicid= 0;
		}

	if( $topicid != 0 )
	{
	$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set id_topic='".$topicid."', restriction='".$restriction."', notify_members='".$notifm."', hpage_public='".$hpage1."', hpage_private='".$hpage0."', date_submission='".$date_sub."', date_publication='".$date_pub."', date_archiving='".$date_arch."', approbation='".$approbid."'  where id='".$idart."' and id_author='".$GLOBALS['BAB_SESS_USERID']."'");
	list($allowattach) = $babDB->db_fetch_array($babDB->db_query("select allow_attachments from ".BAB_TOPICS_TBL." where id='".$topicid."'"));
	if( $allowattach == 'N' )
		{
		bab_deleteDraftFiles($idart);
		}
	}
	else
	{
	$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set id_topic='0', restriction='', notify_members='N', hpage_public='N', hpage_private='N', date_submission='".$date_pub."', date_publication='0000-00-00 00:00:00', date_archiving='0000-00-00 00:00:00', approbation='".$approbid."' where id='".$idart."' and id_author='".$GLOBALS['BAB_SESS_USERID']."'");
	bab_deleteDraftFiles($idart);
	}

}


function submitArticleDraft( $idart, $message)
{
	global $babBody, $babDB;
	$res = $babDB->db_query("select id_topic from ".BAB_ART_DRAFTS_TBL." where id='".$idart."'");
	if( $res && $babDB->db_num_rows($res) > 0 )
		{
		$arr = $babDB->db_fetch_array($res);
		if( $arr['id_topic'] == 0 )
			{
			$message = bab_translate("You must specify a topic");
			return false;
			}

		if( !bab_isAccessValid(BAB_TOPICSSUB_GROUPS_TBL, $arr['id_topic']) )
			{
			$message = bab_translate("You don't have rights to submit articles in this topic");
			return false;
			}
		return bab_submitArticleDraft( $idart);
		}
	else
	{
		$message = bab_translate("Access denied");
		return false;
	}
}

function savePreviewDraft($idart, $approbid)
{
	global $babDB, $BAB_SESS_USERID;
	$res = $babDB->db_query("select id from ".BAB_ART_DRAFTS_TBL." where id='".$idart."' and id_author='".$BAB_SESS_USERID."'");
	if( $res && $babDB->db_num_rows($res) == 1 )
		{
		$babDB->db_query("update ".BAB_ART_DRAFTS_TBL." set approbation='".$approbid."' where id='".$idart."'");
		}
}

function artedit_init()
{
	global $babDB;

	$aredit = array();
	$aredit['articles'] = false;
	$aredit['trash'] = false;

	if( $GLOBALS['BAB_SESS_USERID'] )
	{
		$arr = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_ART_DRAFTS_TBL." where id_author='".$GLOBALS['BAB_SESS_USERID']."' and trash='Y'"));
		if( $arr['total'] != 0 )
		{
			$aredit['trash'] = true;
		}

		$arr = $babDB->db_fetch_array($babDB->db_query("select count(id) as total from ".BAB_ART_DRAFTS_TBL." where id_author='".$GLOBALS['BAB_SESS_USERID']."' and result!='".BAB_ART_STATUS_DRAFT."' and trash='N'"));
		if( $arr['total'] != 0 )
		{
			$aredit['articles'] = true;
		}
	}
	return $aredit;
}
/* main */
$artedit = array();
if( empty($GLOBALS['BAB_SESS_USERID']) || $GLOBALS['BAB_SESS_USERID'] == 0 || (count($babBody->topsub) == 0  && count($babBody->topmod) == 0))
{
	$idx = 'denied';
}
else {
if(!isset($idx))
	{
	$idx = "list";
	}

if( isset($updstep0))
{
	if( $updstep0 == 'cancel' )
	{
		if( isset($idart) && $idart != 0 )
			{
			deleteDraft($idart);
			unset($idart);
			$refreshurl = $GLOBALS['babUrlScript']."?tg=artedit&idx=list";
			}
		else
			{
			$refreshurl = "";
			}
		$idx='unload';
		$popupmessage = "";
	}
}
elseif( isset($updstep1))
{
	if( $updstep1 == 'cancel' )
	{
		if( isset($idart) && $idart != 0 )
			{
			deleteDraft($idart);
			unset($idart);
			$refreshurl = $GLOBALS['babUrlScript']."?tg=artedit&idx=list";
			}
		else
			{
			$refreshurl = "";
			}
		$idx='unload';
		$popupmessage = "";
	}
	elseif( $updstep1 == 'save' )
	{
		if( !isset($idart) || $idart == 0 )
			{
			$idart = bab_newArticleDraft($topicid, $articleid);
			}
		if( !isset($approbid)) { $approbid =0;}
		$message = '';
		if( $idart == 0 )
		{
			$message = bab_translate("Draft creation failed");
			$idx = 's0';
		}elseif(!updateArticleDraft($idart, $title, $headtext, $bodytext, $lang, $approbid, $message))
		{
			deleteDraft($idart);
			unset($idart);
			$idx = 's1';
		}
		else
		{
		$idx='unload';
		$popupmessage = bab_translate("Update done");
		$refreshurl = $GLOBALS['babUrlScript']."?tg=artedit&idx=list";
		}
	}
	elseif( $updstep1 == 'prev' )
	{
		$idx = 's0';
	}
	elseif( $updstep1 == 'submit' )
	{
		if( !isset($idart) || $idart == 0 )
			{
			$idart = bab_newArticleDraft($topicid, $articleid);
			}
		if( !isset($approbid)) { $approbid =0;}
		$message = '';
		if( $idart == 0 )
		{
			$message = bab_translate("Draft creation failed");
			$idx = 's0';
		}elseif(!updateArticleDraft($idart, $title, $headtext, $bodytext, $lang, $approbid, $message))
		{
			deleteDraft($idart);
			unset($idart);
			$idx = 's1';
		}
		else
		{
		$message = '';
		if( !submitArticleDraft( $idart, $message) )
			{
			$idx = 's1';
			}
		else
			{
			$idx='unload';
			$popupmessage = bab_translate("Update done");
			$refreshurl = $GLOBALS['babUrlScript']."?tg=artedit&idx=list";
			}
		}
	}
	elseif( $updstep1 == 'next' )
	{
		if( !isset($idart) || $idart == 0 )
			{
			$idart = bab_newArticleDraft($topicid, $articleid);
			}
		if( !isset($approbid)) { $approbid =0;}
		$message = '';
		if( $idart == 0 )
		{
			$message = bab_translate("Draft creation failed");
			$idx = 's0';
		}elseif(!updateArticleDraft($idart, $title, $headtext, $bodytext, $lang, $approbid, $message))
		{
			deleteDraft($idart);
			unset($idart);
			$idx = 's1';
		}
		else
		{
			$idx = 's2';
		}
	}
}
elseif( isset($updstep2))
{
	if( $updstep2 == 'cancel' )
	{
		if( isset($idart) && $idart != 0 )
			{
			deleteDraft($idart);
			unset($idart);
			$refreshurl = $GLOBALS['babUrlScript']."?tg=artedit&idx=list";
			}
		else
			{
			$refreshurl = "";
			}
		$idx='unload';
		$popupmessage = "";
	}
	elseif( $updstep2 == 'save' )
	{
		$idx='unload';
		if( isset($approbid)) 
			{ 
			savePreviewDraft($idart, $approbid);
			}
		$popupmessage = bab_translate("Update done");
		$refreshurl = $GLOBALS['babUrlScript']."?tg=artedit&idx=list";
	}
	elseif( $updstep2 == 'submit' )
	{
		$message = '';
		if( isset($approbid)) 
			{ 
			savePreviewDraft($idart, $approbid);
			}
		if( !submitArticleDraft( $idart, $message) )
			{
			$idx = 's2';
			}
		else
			{
			$idx='unload';
			$popupmessage = bab_translate("Update done");
			$refreshurl = $GLOBALS['babUrlScript']."?tg=artedit&idx=list";
			}
	}
	elseif( $updstep2 == 'next' )
	{
		if( isset($approbid)) 
			{
			savePreviewDraft($idart, $approbid);
			}
		$idx = 's3';
	}
	elseif( $updstep2 == 'prev' )
	{
		$idx = 's1';
	}
}
elseif( isset($updstep3))
{
	if( $updstep3 == 'cancel' )
	{
		if( isset($idart) && $idart != 0 )
			{
			deleteDraft($idart);
			unset($idart);
			$refreshurl = $GLOBALS['babUrlScript']."?tg=artedit&idx=list";
			}
		else
			{
			$refreshurl = "";
			}
		$idx='unload';
		$popupmessage = "";
	}
	elseif( $updstep3 == 'fadd')
	{
		if( isset($docf_name) && isset($idart) && $idart != 0 )
		{
		$message = '';
		if( addDocumentArticleDraft($idart, $docf_name, $docf, $docdesc, $message) )
			{
			Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=s3&idart=".$idart);
			exit;
			}
		else
			{
			$idx='s3';
			}
		}
		$idx='s3';
	}
	elseif( $updstep3 == 'delf')
	{
		if( isset($idart) && $idart != 0 )
		{
		delDocumentArticleDraft( $idart, $idf );
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=s3&idart=".$idart);
		}
		$idx='s3';
	}
	elseif( $updstep3 == 'proptop')
	{
		$idx='s3';
	}
	elseif( $updstep3 == 'save' )
	{
		updatePropertiesArticleDraft();
		$idx='unload';
		$popupmessage = bab_translate("Update done");
		$refreshurl = $GLOBALS['babUrlScript']."?tg=artedit&idx=list";
	}
	elseif( $updstep3 == 'submit' )
	{
		$message = '';
		updatePropertiesArticleDraft();
		if( !submitArticleDraft( $idart, $message) )
			{
			$idx = 's3';
			}
		else
			{
			$idx='unload';
			$popupmessage = bab_translate("Update done");
			$refreshurl = $GLOBALS['babUrlScript']."?tg=artedit&idx=list";
			}
	}
	elseif( $updstep3 == 'prev' )
	{
		$idx = 's1';
	}
}


if($idx == 'movet')
{
	moveArticleDraftToTrash($idart);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
	exit;
}elseif( $idx == 'restore')
{
	if( !restoreArticleDraft($idart))
	{
		$idx = 'ltrash';
	}
	else
	{
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
		exit;
	}
}elseif( $idx == 'empty')
{
	emptyTrash();
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
	exit;
}elseif( $idx == 'delt')
{
	deleteDraft($idart);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=lsub");
	exit;
}elseif( $idx == 'rests')
{
	if(!restoreRefusedArticleDraft($idart))
		{
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=lsub");
		exit;
		}
	else
		{
		$idx = "lsub";
		}
}
}

switch($idx)
	{
	case "denied":
		$babBody->msgerror = bab_translate("Access denied");
		break;
	case "s0":
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("Choose the topic");
		showChoiceTopic();
		printBabBodyPopup();
		exit;
		break;
	case "s1":
		if( !isset($message)) { $message = '';}
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->msgerror = $message;
		$babBodyPopup->title = bab_translate("Write article");
		showEditArticle();
		printBabBodyPopup();
		exit;
		break;
	case "s2":
		if( !isset($message)) { $message = '';}
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->msgerror = $message;
		$babBodyPopup->title = bab_translate("Preview");
		showPreviewArticle($idart);
		printBabBodyPopup();
		exit;
		break;
	case "s3":
		if( !isset($message)) { $message = '';}
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->msgerror = $message;
		$babBodyPopup->title = bab_translate("Set article properties");
		showSetArticleProperties($idart);
		printBabBodyPopup();
		exit;
		break;
	case "unload":
		include_once $babInstallPath."utilit/uiutil.php";
		if( !isset($popupmessage)) { $popupmessage ='';}
		if( !isset($refreshurl)) { $refreshurl ='';}
		popupUnload($popupmessage, $refreshurl);
		exit;
	case "getf":
		getDocumentArticleDraft( $idart, $idf );
		exit;
		break;
	case "propa":
		propertiesArticle( $idart);
		exit;
		break;
	case "preview":
		previewArticleDraft($idart);
		exit;
		break;
	case "edit":
		if( !isset($message)) { $message = '';}
		if( !isset($title)) { $title = '';}
		if( !isset($headtext)) { $headtext = '';}
		if( !isset($bodytext)) { $bodytext = '';}
		if( !isset($lang)) { $lang = '';}
		$babBodyPopup = new babBodyPopup();
		$babBodyPopup->title = bab_translate("Article edition");
		editArticleDraft($idart, $title, $headtext, $bodytext, $lang, $message);
		printBabBodyPopup();
		exit;
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
		$babBody->addItemMenu("ltrash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=artedit&idx=ltrash");
		if( $arrinit['articles'] )
		{
		$babBody->addItemMenu("lsub", bab_translate("My Articles"), $GLOBALS['babUrlScript']."?tg=artedit&idx=lsub");
		}
		break;
	case "lsub":
		$arrinit = artedit_init();
		if( !$arrinit['articles'] )
		{
		Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
		exit;
		}
		$babBody->title = bab_translate("List of submitted articles");
		$babBody->addItemMenu("list", bab_translate("Drafts"), $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
		listSubmitedArticles();
		if( $arrinit['trash'] )
		{
		$babBody->addItemMenu("ltrash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=artedit&idx=ltrash");
		}
		$babBody->addItemMenu("lsub", bab_translate("My Articles"), $GLOBALS['babUrlScript']."?tg=artedit&idx=lsub");
		break;
	case "sub":
		if( submitArticleDraft( $idart, $babBody->msgerror) )
			{
			Header("Location: ". $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
			exit;
			}
		$idx = "list";
		/* break; */
	case "list":
	default:
		$arrinit = artedit_init();
		$babBody->title = bab_translate("List of articles");
		$babBody->addItemMenu("list", bab_translate("Drafts"), $GLOBALS['babUrlScript']."?tg=artedit&idx=list");
		listDrafts();
		if( $arrinit['trash'] )
		{
		$babBody->addItemMenu("ltrash", bab_translate("Trash"), $GLOBALS['babUrlScript']."?tg=artedit&idx=ltrash");
		}
		if( $arrinit['articles'] )
		{
		$babBody->addItemMenu("lsub", bab_translate("My Articles"), $GLOBALS['babUrlScript']."?tg=artedit&idx=lsub");
		}
		break;
	}

$babBody->setCurrentItemMenu($idx);
?>