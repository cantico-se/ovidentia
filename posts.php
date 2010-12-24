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
* @internal SEC1 NA 18/12/2006 FULL
*/
include_once 'base.php';
include_once $babInstallPath.'utilit/forumincl.php';
include_once $babInstallPath.'utilit/topincl.php';
include_once $babInstallPath.'utilit/mailincl.php';

function listPosts($forum, $thread, $post)
	{
	global $babBody;

	class listPostsCls
		{
	
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $forum;
		var $thread;
		var $more;
		var $moreurl;
		var $morename;
		var $confirmurl;
		var $confirmname;
		var $postid;
		var $alternate;
		var $subject;
		var $author;
		var $date;
		var $altflattxt;
		var $flaturl;
		var $noflaturl;
		var $brecent;
		var $altrecentposts;

		function listPostsCls($forum, $thread, $post)
			{
			global $babBody, $babDB, $moderator, $views, $flat;
			$this->subject = bab_translate("Subject");
			$this->author = bab_translate("Author");
			$this->date = bab_translate("Date");
			$this->altnoflattxt = bab_translate("View thread as hierarchical list");
			$this->altflattxt = bab_translate("View thread as flat list");
			$this->altrecentposts = bab_translate("Recent posts");
			$this->t_files = bab_translate("Dependent files");
			$this->reply_txt = bab_translate("Reply");
			$this->waiting_txt = bab_translate("Waiting posts");
			$this->search_txt = bab_translate("Search");
			$this->forum = $forum;
			$this->thread = $thread;
			$this->alternate = 0;
			$this->more = "";
			$this->search_url = bab_toHtml($GLOBALS['babUrlScript']."?tg=forumsuser&idx=search&forum=".urlencode($forum));

			if( $views == "1")
				{
				//update views
				$req = "select * from ".BAB_THREADS_TBL." where id='".$babDB->db_escape_string($thread)."'";
				$res = $babDB->db_query($req);
				$row = $babDB->db_fetch_array($res);
				$views = $row["views"];
				$views += 1;
				$req = "update ".BAB_THREADS_TBL." set views='".$babDB->db_escape_string($views)."' where id='".$babDB->db_escape_string($thread)."'";
				$res = $babDB->db_query($req);
				}
				
			if( $moderator )
				{
				$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($thread)."' and id_parent='0'";
				}
			else
				{
				if( $GLOBALS['BAB_SESS_USERID'] )
					{
					$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($thread)."' and id_parent='0' and (confirmed='Y' or id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."')";
					}
				else
					{
					$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($thread)."' and id_parent='0' and confirmed='Y'";
					}
				}
			$res = $babDB->db_query($req);
			if( $res && $babDB->db_num_rows($res) > 0)
				{
				$arr = $babDB->db_fetch_array($res);
				$firstpost = $arr['id'];
				}
			else
				$firstpost = 0;

			$this->postid = $post;

			if( $this->postid > 0)
				{
				if( $moderator )
					{
					$req = "select * from ".BAB_POSTS_TBL." where id='".$babDB->db_escape_string($this->postid)."'";
					}
				else
					{
					if( $GLOBALS['BAB_SESS_USERID'] )
						{
						$req = "select * from ".BAB_POSTS_TBL." where id='".$babDB->db_escape_string($this->postid)."' and (confirmed='Y' or id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."')";
						}
					else
						{
						$req = "select * from ".BAB_POSTS_TBL." where id='".$babDB->db_escape_string($this->postid)."' and confirmed='Y'";
						}
					}
				$res = $babDB->db_query($req);
				$arr = $babDB->db_fetch_array($res);
				$GLOBALS['babWebStat']->addForumPost($this->postid);
				$this->postdate = bab_toHtml(bab_strftime(bab_mktime($arr['date'])));
				$this->postauthor = $arr['id_author']? bab_getUserName($arr['id_author']):$arr['author'];
				$this->postauthor = bab_getForumContributor($this->forum, $arr['id_author'], $this->postauthor);
				
				$this->postauthor = bab_toHtml($this->postauthor);
				$this->postsubject = bab_toHtml($arr['subject']);
				
				include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
				$editor = new bab_contentEditor('bab_forum_post');
				$editor->setContent($arr['message']);
				$editor->setFormat($arr['message_format']);
				$this->postmessage = $editor->getHtml();
				
				$dateupdate = bab_mktime($arr['dateupdate']);
				$this->confirmurl = '';
				$this->confirmname = '';
				$this->what = $arr['confirmed'];
				if(  $dateupdate > 0)
					{
					$this->more = bab_translate("Modified")." ".bab_strftime($dateupdate);
					}
				else
					{
					$this->more = '';
					}
				if(  $arr['confirmed'] == "Y")
					{
					$this->confirmurl = "";
					$this->confirmname = "";
					}
				else if( $arr['confirmed']  == "N" )
					{
					$this->confirmurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=posts&idx=Confirm&forum=".urlencode($this->forum)."&thread=".urlencode($this->thread)."&post=".urlencode($arr['id'])."&flat=".urlencode($flat));
					$this->confirmname = bab_translate("Confirm");
					$this->deleteurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=posts&idx=DeleteP&forum=".urlencode($this->forum)."&thread=".urlencode($this->thread)."&post=".urlencode($this->postid)."&flat=".urlencode($flat));
					$this->deletename = bab_translate("Refuse");
					}

				$this->forums = bab_get_forums();
				if( ($moderator && $this->forums[$this->forum]['bupdatemoderator'] == 'Y') || ($GLOBALS['BAB_SESS_USERID'] && $this->forums[$this->forum]['bupdateauthor'] == 'Y' && $GLOBALS['BAB_SESS_USERID'] == $arr['id_author']))
					{
					$this->moreurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=posts&idx=Modify&forum=".urlencode($this->forum)."&thread=".urlencode($this->thread)."&post=".urlencode($arr['id'])."&flat=".urlencode($flat));
					$this->morename = bab_translate("Edit");
					}
				else
					{
					$this->moreurl = '';
					}

				$this->files = bab_getPostFiles($this->forum, $this->postid);
				$this->ismanager = bab_isAccessValid(BAB_FORUMSMAN_GROUPS_TBL, $forum );
				$this->displayIndex = bab_isUserAdministrator();

				if( $arr['confirmed']  == "Y" && $GLOBALS['open'] && bab_isAccessValid(BAB_FORUMSREPLY_GROUPS_TBL, $forum) )
					{
					$this->replyurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=posts&idx=reply&forum=".urlencode($forum)."&thread=".urlencode($thread)."&post=".urlencode($post)."&flat=".urlencode($flat));
					}

				$this->postauthordetailsurl = '';
				if( $arr['id_author'] != 0 && $this->forums[$this->forum]['bdisplayauhtordetails'] == 'Y')
					{
					list($this->iddir) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group='".BAB_REGISTERED_GROUP."'"));
					if( bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $this->iddir))
						{
						list($iddbuser) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where id_user='".$babDB->db_escape_string($arr['id_author'])."' and id_directory='0'"));
						$this->postauthordetailsurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=directory&idx=ddbovml&directoryid=".urlencode($this->iddir)."&userid=".urlencode($iddbuser));	
						}
					}


				$this->postauthoremail = '';
				if( $this->forums[$this->forum]['bdisplayemailaddress'] == 'Y' )
					{
					$idauthor = $arr['id_author'] != 0? $arr['id_author']: bab_getUserId( $arr['author']); 
					if( $idauthor )
						{
						$res = $babDB->db_query("select email from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($idauthor)."'");
						if( $res && $babDB->db_num_rows($res) > 0 )
							{
							$rr = $babDB->db_fetch_array($res);
							$this->postauthoremail = bab_toHtml($rr['email']);
							}
						}
					}
				}


			$this->arrresult = array();
			$this->getChild($firstpost, 0, -1, 0);
			$this->count = count($this->arrresult['id']);
			$this->flaturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".urlencode($this->forum)."&thread=".urlencode($this->thread)."&flat=1");
			$this->noflaturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".urlencode($this->forum)."&thread=".urlencode($this->thread)."&post=".urlencode($this->postid)."&flat=0");
			}

		function getChild($id, $delta, $iparent, $leaf)
			{
			global $babDB, $moderator;
			static $k=0;
			if($moderator)
				{
				$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($this->thread)."' and id='".$babDB->db_escape_string($id)."'";
				}
			else
				{
				if( $GLOBALS['BAB_SESS_USERID'] )
					{
					$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($this->thread)."' and id='".$babDB->db_escape_string($id)."' and (confirmed='Y' or id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."')";
					}
				else
					{
					$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($this->thread)."' and id='".$babDB->db_escape_string($id)."' and confirmed='Y'";
					}
				}
			$res = $babDB->db_query($req);
			if( !$res && $babDB->db_num_rows($res) < 1)
				return;
			$arr = $babDB->db_fetch_array($res);
			$idx = $k;
			$this->arrresult["id"][$k] = $arr['id']; 
			$this->arrresult["delta"][$k] = $delta;
			$this->arrresult["parent"][$k] = 1;
			$this->arrresult["iparent"][$k] = $iparent;
			$this->arrresult["leaf"][$k] = $leaf;

			$tab = array();
			if( $iparent >= 0)
				{
				$tab = $this->arrresult["schema"][$iparent];
				$p = $this->arrresult["iparent"][$iparent];
				if( $this->arrresult["leaf"][$iparent] == 1)
					$tab[$this->arrresult["delta"][$p]] = 0;
				else
					{
					if (!isset($this->arrresult["delta"][$p]))
						$this->arrresult["delta"][$p] = '';
					$tab[$this->arrresult["delta"][$p]] = 1;
					}
				}
			$this->arrresult["schema"][$k] = $tab;

			$k++;
			if($moderator)
				{
				$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($this->thread)."' and id_parent='".$babDB->db_escape_string($arr['id'])."' order by date asc";
				}
			else
				{
				if( $GLOBALS['BAB_SESS_USERID'] )
					{
					$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($this->thread)."' and id_parent='".$babDB->db_escape_string($arr['id'])."' and (confirmed='Y' or id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."') order by date asc";
					}
				else
					{
					$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($this->thread)."' and id_parent='".$babDB->db_escape_string($arr['id'])."' and confirmed='Y' order by date asc";
					}
				}
			$res = $babDB->db_query($req);
			if( !$res || $babDB->db_num_rows($res) < 1)
				{
				$this->arrresult['parent'][$k-1] = 0;
				return;
				}
			$count = $babDB->db_num_rows($res);
			for( $i = 0; $i < $count; $i++)
				{
				$arr = $babDB->db_fetch_array($res);
				if( $i == $count -1)
					$this->getChild($arr['id'], $delta + 1, $idx, 1);
				else
					$this->getChild($arr['id'], $delta + 1, $idx, 0);
				}

			}

		function getnext()
			{
			global $babBody, $babDB, $flat;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->replyauthor = "";
				$this->replysubject = "";
				$this->replydate = "";
				$req = "select * from ".BAB_POSTS_TBL." where id='".$babDB->db_escape_string($this->arrresult['id'][$i])."'";
				$res = $babDB->db_query($req);
				$arr = $babDB->db_fetch_array($res);
				$this->replydate = bab_toHtml(bab_shortDate(bab_mktime($arr['date']), true));
				$this->replyauthor = $arr['id_author']? bab_getUserName($arr['id_author']):$arr['author'];
				$this->replyauthor = bab_getForumContributor($this->forum, $arr['id_author'], $this->replyauthor);
				$this->replyauthor = bab_toHtml($this->replyauthor);
				$this->replysubject = bab_toHtml($arr['subject']);

				$idauthor = $arr['id_author'] != 0? $arr['id_author']: bab_getUserId( $arr['author']);

				$this->replymail = 0;
				if( $idauthor )
					{
					$res = $babDB->db_query("select email from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($idauthor)."'");
					if( $res && $babDB->db_num_rows($res) > 0 )
						{
						$r = $babDB->db_fetch_array($res);
						$this->replymail = $r['email']."?subject=";
						if( mb_substr($arr['subject'], 0, 3) != "RE:")
							$this->replymail .= "RE: ";
						$this->replymail .= $arr['subject'];
						}
					}


				if( $arr['confirmed'] == "N")
					$this->confirmed = "C";
				else
					$this->confirmed = "";

				$this->brecent = false;
				if( mktime() - bab_mktime($arr['date']) <= DELTA_TIME )
					$this->brecent = true;
				else if($GLOBALS['BAB_SESS_LOGGED'])
					{
					if( $arr['date'] >= $babBody->lastlog )
						$this->brecent = true;
					}
				
				if( $this->alternate == 0)
					$this->alternate = 1;
				else
					$this->alternate = 0;
				$this->transarr = $this->arrresult['schema'][$i];
				$this->leaf = $this->arrresult['leaf'][$i];
				$this->delta = $this->arrresult['delta'][$i];
				if( $this->arrresult['parent'][$i] == 1)
					{
					$this->parent = 1;
					}
				else
					{
					$this->parent = 0;
					}

				$this->nbtrans = count($this->transarr) - 1;
				if($i == 0)
					{
					$this->first = 1;
					}
				else
					$this->first = 0;

				if( $this->arrresult['id'][$i] == $this->postid )
					$this->current = 1;
				else
					$this->current = 0;
				$this->replysubjecturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".urlencode($this->forum)."&thread=".urlencode($this->thread)."&post=".urlencode($this->arrresult['id'][$i])."&flat=".urlencode($flat));
				$i++;
				return true;
				}
			else
				return false;
			}

		function gettrans()
			{
			static $m = 0;
			if( $m < $this->nbtrans)
				{
				if( $this->transarr[$m] == 1)
					$this->vert = 1;
				else
					$this->vert = 0;

				$m++;
				return true;
				}
			else
				{
				$m = 0;
				return false;
				}
			}

		function getnextfile()
			{
			if ($this->file = current($this->files))
				{
				$this->file_url = bab_toHtml($this->file['url']);
				$this->file_name = bab_toHtml($this->file['name']);
				$this->file_size = bab_toHtml($this->file['size']);
				$this->file_index_label = bab_toHtml($this->file['index_label']);
				next($this->files);
				return true;
				}
			else
				return false;
			}

		}
	
	$temp = new listPostsCls($forum, $thread, $post);
	$babBody->babecho(	bab_printTemplate($temp,"posts.html", "newpostslist"));
	return $temp->count;
	}


function listPostsFlat($forum, $thread, $open, $pos)
	{
	global $babBody;

	class listPostsFlatCls
		{
	
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $forum;
		var $thread;
		var $more;
		var $moreurl;
		var $morename;
		var $confirmurl;
		var $confirmname;
		var $postid;
		var $alternate;
		var $subject;
		var $author;
		var $date;
		var $message;
		var $altnoflattxt;
		var $noflaturl;
		var $backtotoptxt;
		var $replytxt;
		var $breply;
		var $brecent;
		var $altrecentposts;


		function listPostsFlatCls($forum, $thread, $open, $pos)
			{
			global $babBody, $babDB, $moderator, $views, $idx;
			$this->subject = bab_translate("Subject");
			$this->author = bab_translate("Author");
			$this->date = bab_translate("Date");
			$this->date = bab_translate("Message");
			$this->altnoflattxt = bab_translate("View thread as hierarchical list");
			$this->altflattxt = bab_translate("View thread as flat list");
			$this->backtotoptxt = bab_translate("Back to top");
			$this->replytxt = bab_translate("Reply");
			$this->altrecentposts = bab_translate("Recent posts");
			$this->t_files = bab_translate("Dependent files");
			$this->message = bab_translate("Message");
			$this->jumpto_txt = bab_translate("Jump to");
			$this->selecforum_txt = bab_translate("Select a forum");
			$this->noposts_txt = bab_translate("No new posts");
			$this->go_txt = bab_translate("Go");
			$this->treeview_txt = bab_translate("Tree view");
			$this->waiting_txt = bab_translate("Waiting posts");
			$this->search_txt = bab_translate("Search");
			$this->postsubject_txt = bab_translate("Post subject");
			$this->posted_txt = bab_translate("Posted");
			$this->mail_txt = bab_translate("Send a mail");
			$this->forum = $forum;
			$this->thread = $thread;
			$this->idx = bab_toHtml($idx);
			$this->alternate = 0;
			$this->more = "";
			$this->search_url = bab_toHtml($GLOBALS['babUrlScript']."?tg=forumsuser&idx=search&forum=".urlencode($forum));
			$this->altbg = true;

			$res = $babDB->db_query("select * from ".BAB_THREADS_TBL." where id='".$babDB->db_escape_string($thread)."'");
			$this->arrthread = $babDB->db_fetch_array($res);
			if( $views == "1")
				{
				//update views
				$views = $this->arrthread["views"];
				$views += 1;
				$req = "update ".BAB_THREADS_TBL." set views='".$babDB->db_escape_string($views)."' where id='".$babDB->db_escape_string($thread)."'";
				$res = $babDB->db_query($req);
				}
				
			if( $moderator )
				{
				$res = $babDB->db_query("select count(id) as total from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($thread)."'");

				$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($thread)."' order by date asc";
				}
			else
				{
				if( $GLOBALS['BAB_SESS_USERID'] )
					{
					$res = $babDB->db_query("select count(id) as total from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($thread)."' and (confirmed='Y' or id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."')");
					$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($thread)."' and (confirmed='Y' or id_author='".$babDB->db_escape_string($GLOBALS['BAB_SESS_USERID'])."') order by date asc";
					}
				else
					{
					$res = $babDB->db_query("select count(id) as total from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($thread)."' and confirmed='Y'");
					$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($thread)."' and confirmed='Y' order by date asc";
					}
				}

			$row = $babDB->db_fetch_array($babDB->db_query("select display from ".BAB_FORUMS_TBL." where id='".$babDB->db_escape_string($forum)."'"));
			$this->maxrows = $row['display'];

			$row = $babDB->db_fetch_array($res);
			$total = $row["total"];
			$this->countpages = 0;
			if( $total > $this->maxrows)
				{
				$this->gotopage_txt = bab_translate("Goto page");
				$this->gotourl = $GLOBALS['babUrlScript']."?tg=posts&idx=List&flat=1&forum=".urlencode($forum)."&thread=".urlencode($thread)."&pos=";
				$this->gotopages = bab_generatePagination($total, $this->maxrows, $pos);
				$this->countpages = count($this->gotopages);
				$req .= " limit ".$pos.",".$this->maxrows;
				}

			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			$this->flaturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".urlencode($this->forum)."&thread=".urlencode($this->thread)."&flat=1");
			$this->noflaturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".urlencode($this->forum)."&thread=".urlencode($this->thread)."&flat=0");
			if( $open && bab_isAccessValid(BAB_FORUMSREPLY_GROUPS_TBL, $forum) )
				$this->breply = true;
			else
				$this->breply = false;

			$this->forums = bab_get_forums();
			$this->arrforum = $this->forums[$this->forum];
			unset($this->forums[$this->forum]);
			$this->countforums = count($this->forums);
			list($this->iddir) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group='".BAB_REGISTERED_GROUP."'"));
			
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			}


		function getnext()
			{
			global $babBody, $babDB, $flat, $moderator, $BAB_SESS_USERID;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->res);
				$GLOBALS['babWebStat']->addForumPost($arr['id']);
				$this->files = bab_getPostFiles($this->forum,$arr['id']);
				$this->ismanager = bab_isAccessValid(BAB_FORUMSMAN_GROUPS_TBL, $this->forum);
				$this->displayIndex = bab_isUserAdministrator();
				$this->what = $arr['confirmed'];
				$this->postdate = bab_toHtml(bab_strftime(bab_mktime($arr['date'])));
				if( $arr['id_author'] )
					{
					$this->postauthor = bab_getUserName($arr['id_author']);
					$this->postauthor = bab_getForumContributor($this->forum, $arr['id_author'], $this->postauthor);
					}
				else
					{
					$this->postauthor = $arr['author'];
					}
				$this->postauthor = bab_toHtml($this->postauthor);
				$this->postsubject = bab_toHtml($arr['subject']);

				
				$editor = new bab_contentEditor('bab_forum_post');
				$editor->setContent($arr['message']);
				$editor->setFormat($arr['message_format']);
				$this->postmessage = $editor->getHtml();
				
				$this->more = "";
				$this->postid = bab_toHtml($arr['id']);

				$this->brecent = false;
				if( mktime() - bab_mktime($arr['date']) <= DELTA_TIME )
					$this->brecent = true;
				else if($GLOBALS['BAB_SESS_LOGGED'])
					{
					if( $arr['date'] >= $babBody->lastlog )
						$this->brecent = true;
					}

				$dateupdate = bab_mktime($arr['dateupdate']);
				if(  $dateupdate > 0)
					{
					$this->more = bab_translate("Modified")." ".bab_toHtml(bab_strftime($dateupdate));
					}
				else
					{
					$this->more = '';
					}
				if(  $arr['confirmed'] == "Y" && $dateupdate > 0)
					{
					$this->confirmurl = "";
					$this->confirmname = "";
					$this->morename = "";
					}
				else if( $arr['confirmed']  == "N" )
					{
					$this->confirmurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=posts&idx=Confirm&forum=".urlencode($this->forum)."&thread=".urlencode($this->thread)."&post=".urlencode($arr['id'])."&flat=".urlencode($flat));
					$this->confirmname = bab_translate("Confirm");
					$this->deleteurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=posts&idx=DeleteP&forum=".urlencode($this->forum)."&thread=".urlencode($this->thread)."&post=".urlencode($this->postid)."&flat=".urlencode($flat));
					$this->deletename = bab_translate("Refuse");
					}

				if( ($moderator && $this->arrforum['bupdatemoderator'] == 'Y') || ($this->arrthread["active"] == 'Y' && $BAB_SESS_USERID && $this->arrforum['bupdateauthor'] == 'Y' && $BAB_SESS_USERID == $arr['id_author']))
					{
					$this->moreurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=posts&idx=Modify&forum=".urlencode($this->forum)."&thread=".urlencode($this->thread)."&post=".urlencode($arr['id'])."&flat=".urlencode($flat));
					$this->morename = bab_translate("Edit");
					}
				else
					{
					$this->moreurl = '';
					}


				$this->postauthordetailsurl = '';
				if( $arr['id_author'] != 0 && $this->arrforum['bdisplayauhtordetails'] == 'Y')
					{
					if( bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $this->iddir))
						{
						list($iddbuser) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where id_user='".$babDB->db_escape_string($arr['id_author'])."' and id_directory='0'"));
						$this->postauthordetailsurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=directory&idx=ddbovml&directoryid=".urlencode($this->iddir)."&userid=".urlencode($iddbuser));	
						}
					}


				$this->postauthoremail = '';
				if( $this->arrforum['bdisplayemailaddress'] == 'Y' )
					{
					$idauthor = $arr['id_author'] != 0? $arr['id_author']: bab_getUserId( $arr['author']); 
					if( $idauthor )
						{
						$res = $babDB->db_query("select email from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($idauthor)."'");
						if( $res && $babDB->db_num_rows($res) > 0 )
							{
							$rr = $babDB->db_fetch_array($res);
							$this->postauthoremail = bab_toHtml($rr['email']);
							}
						}
					}


				if( $arr['confirmed'] == "N")
					$this->confirmed = "C";
				else
					$this->confirmed = "";
				
				if( $this->alternate == 0)
					$this->alternate = 1;
				else
					$this->alternate = 0;
				$this->replysubjecturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=posts&idx=reply&forum=".urlencode($this->forum)."&thread=".urlencode($this->thread)."&post=".urlencode($arr['id'])."&flat=".urlencode($flat));
				$this->noflaturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".urlencode($this->forum)."&thread=".urlencode($this->thread)."&post=".urlencode($arr['id'])."&flat=0");
				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextfile()
			{
			if ($this->file = current($this->files))
				{
				$this->file_url = bab_toHtml($this->file['url']);
				$this->file_name = bab_toHtml($this->file['name']);
				$this->file_size = bab_toHtml($this->file['size']);
				$this->file_index_label = bab_toHtml($this->file['index_label']);
				next($this->files);
				return true;
				}
			else
				return false;
			}

		function getnextpage()
			{
			static $i = 0;
			if( $i < $this->countpages)
				{
				$this->page = $this->gotopages[$i]['page'];
				$this->bpageurl = $this->gotopages[$i]['url'];
				$this->pageurl = bab_toHtml($this->gotourl.$this->gotopages[$i]['pagepos']);
				$i++;
				return true;
				}
			else
				{
				$i=0;
				return false;
				}
			}
		function getnextforum()
			{
			static $i = 0;
			if( list($key, $val) = each($this->forums))
				{
				$this->forumid = bab_toHtml($key);
				$this->forumname = bab_toHtml($val['name']);
				$i++;
				return true;
				}
			else
				{
				reset($this->forums);
				$i=0;
				return false;
				}
			}
		}
	
	$temp = new listPostsFlatCls($forum, $thread, $open, $pos);
	$babBody->babecho(	bab_printTemplate($temp,"posts.html", "postslistflat"));
	return $temp->count;
	}


function newReply($forum, $thread, $post)
	{
	global $babBody;
	
	class newReplyCls
		{
		var $subject;
		var $name;
		var $message;
		var $add;
		var $forum;
		var $thread;
		var $username;
		var $anonyme;
		var $notifyme;
		var $postid;
		var $noteforum;
		

		function newReplyCls($forum, $thread, $post)
			{
			global $babBody, $BAB_SESS_USER, $BAB_SESS_USERID, $babDB, $flat;
			$this->subject = bab_translate("Subject");
			$this->name = bab_translate("Your Name");
			$this->message = bab_translate("Message");
			$this->add = bab_translate("New reply");
			$this->t_files = bab_translate("Dependent files");
			$this->t_add_field = bab_translate("Add field");
			$this->t_remove_field = bab_translate("Remove field");
			$this->forum = bab_toHtml($forum);
			$this->thread = bab_toHtml($thread);
			$this->postid = bab_toHtml($post);
			$this->flat = bab_toHtml($flat);

			$req = "select * from ".BAB_POSTS_TBL." where id='".$babDB->db_escape_string($post)."'";
			$res = $babDB->db_query($req);
			$arr = $babDB->db_fetch_array($res);
			if( mb_substr($arr['subject'], 0, 3) == "RE:")
				{
				$this->subjectval = bab_toHtml($arr['subject']);
				}
			else
				{
				$this->subjectval = "RE:".bab_toHtml($arr['subject']);
				}

			if( empty($BAB_SESS_USER))
				{
				$this->anonyme = 1;
				}
			else
				{
				$this->anonyme = 0;
				$this->username = $BAB_SESS_USER;
				$this->username = bab_getForumContributor($this->forum, $BAB_SESS_USERID, $this->username);
				}

			$this->username = bab_toHtml($this->username);


			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			$editor = new bab_contentEditor('bab_forum_post');
			$this->editor = $editor->getEditor();

			$this->postdate = bab_toHtml(bab_strftime(bab_mktime($arr['date'])));
			$this->postauthor = bab_toHtml($arr['author']);
			$this->postsubject = bab_toHtml($arr['subject']);

			$editor->setContent($arr['message']);
			$editor->setFormat($arr['message_format']);
			$this->postmessage = $editor->getHtml();
			
			if( bab_isForumModerated($forum))
				{
				$this->noteforum = bab_translate("Note: Posts are moderate and consequently your post will not be visible immediately");
				}
			else
				{
				$this->noteforum = '';
				}

			$this->ismanager = bab_isAccessValid(BAB_FORUMSMAN_GROUPS_TBL, $forum );
			$this->displayIndex = bab_isUserAdministrator();

			$this->maxfilesize = $GLOBALS['babMaxFileSize'];
			$this->files = bab_getPostFiles($this->forum,$post);
			$this->allow_post_files = bab_isAccessValid(BAB_FORUMSFILES_GROUPS_TBL,$forum);
			}

		function getnextfile()
			{
			if ($this->file = current($this->files))
				{
				$this->file_url = bab_toHtml($this->file['url']);
				$this->file_name = bab_toHtml($this->file['name']);
				$this->file_size = bab_toHtml($this->file['size']);
				$this->file_index_label = bab_toHtml($this->file['index_label']);
				next($this->files);
				return true;
				}
			else
				return false;
			}
		}

	$temp = new newReplyCls($forum, $thread, $post);
	$babBody->babecho(	bab_printTemplate($temp,"posts.html", "postcreate"));
	}

function editPost($forum, $thread, $post)
	{
	global $babBody;
	
	class editPostCls
		{
		var $subject;
		var $name;
		var $message;
		var $update;
		var $forum;
		var $thread;
		var $post;
		var $newpost;
		var $arr = array();

		function editPostCls($forum, $thread, $post)
			{
			global $babBody, $babDB, $BAB_SESS_USERID, $flat, $moderator;
			$forums = bab_get_forums();
			$req = "select * from ".BAB_POSTS_TBL." where id='".$babDB->db_escape_string($post)."'";
			$res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_array($res);
			if( ($moderator && $forums[$forum]['bupdatemoderator'] == 'Y' ) || ( $forums[$forum]['bupdateauthor'] == 'Y' && $BAB_SESS_USERID && $BAB_SESS_USERID == $this->arr['id_author']  ))
				{
				$this->subject = bab_translate("Subject");
				$this->name = bab_translate("Name");
				$this->message = bab_translate("Message");
				$this->update = bab_translate("Update reply");
				$this->t_files = bab_translate("Dependent files");
				$this->t_add_field = bab_translate("Add field");
				$this->t_remove_field = bab_translate("Remove field");
				$this->t_files_delete_txt = bab_translate("To delete files use checkboxes");
				$this->forum = bab_toHtml($forum);
				$this->thread = bab_toHtml($thread);
				$this->post = bab_toHtml($post);
				$this->flat = bab_toHtml($flat);

				include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
				$editor = new bab_contentEditor('bab_forum_post');
				$editor->setContent($this->arr['message']);
				$editor->setFormat($this->arr['message_format']);
				$editor->setFormat('html');
				$this->editor = $editor->getEditor();
				
				$this->access = 1;
				$this->author = bab_toHtml($this->arr['author']);
				$this->author = bab_getForumContributor($forum, $this->arr['id_author'], $this->author);
				$this->subjectval = bab_toHtml($this->arr['subject']);

				$this->maxfilesize = $GLOBALS['babMaxFileSize'];
				$this->files = bab_getPostFiles($forum,$post);
				$this->countfiles = count($this->files);
				$this->allow_post_files = bab_isAccessValid(BAB_FORUMSFILES_GROUPS_TBL,$forum);
				}
			else
				{
				$this->access = 0;
				}
			}

		function getnextfile()
			{
			if ($this->file = current($this->files))
				{
				$this->file_url = bab_toHtml($this->file['url']);
				$this->file_name = bab_toHtml($this->file['name']);
				$this->file_size = bab_toHtml($this->file['size']);
				$this->file_index_label = bab_toHtml($this->file['index_label']);
				next($this->files);
				return true;
				}
			else
				return false;
			}
		}

	$temp = new editPostCls($forum, $thread, $post);
	if( $temp->access )
		{
		$babBody->babecho(	bab_printTemplate($temp,"posts.html", "postedit"));
		}
	return $temp->access;

	}

function deleteThread($forum, $thread)
	{
	global $babBody;

	class deleteThreadCls
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

		function deleteThreadCls($forum, $thread)
			{
			global $flat;
			$this->message = bab_translate("Are you sure you want to delete this thread");
			$this->title = bab_getForumThreadTitle($thread);
			$this->warning = bab_translate("WARNING: This operation will delete the thread and all references"). "!";
			$this->urlyes = bab_toHtml($GLOBALS['babUrlScript']."?tg=posts&idx=DeleteT&forum=".$forum."&thread=".$thread."&action=Yes&flat=".$flat);
			$this->yes = bab_translate("Yes");
			$this->urlno = bab_toHtml($GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$forum."&thread=".$thread."&flat=".$flat);
			$this->no = bab_translate("No");
			}
		}

	$temp = new deleteThreadCls($forum, $thread);
	$babBody->babecho(	bab_printTemplate($temp,"warning.html", "warningyesno"));
	}

function moveThread($forum, $thread)
	{
	global $babBody;

	class moveThreadCls
		{

		function moveThreadCls($forum, $thread)
			{
			global $babBody, $flat;
			$this->flat = bab_toHTML($flat);
			$this->forum = bab_toHTML($forum);
			$this->idthread = bab_toHTML($thread);
			$this->thread = bab_toHTML(bab_getForumThreadTitle($thread));
			$this->thread_txt = bab_translate("Thread");
			$this->move_txt = bab_translate("Move to");
			$this->update_txt = bab_translate("Update");

			$this->forums = bab_get_forums();
			$this->arrforum = $this->forums[$this->forum];
			unset($this->forums[$this->forum]);
			$this->countforums = count($this->forums);
			}

		function getnextforum()
			{
			static $i = 0;
			if( list($key, $val) = each($this->forums))
				{
				$this->forumid = bab_toHtml($key);
				$this->forumname = bab_toHtml($val['name']);
				$i++;
				return true;
				}
			else
				{
				reset($this->forums);
				$i=0;
				return false;
				}
			}
		}

	$temp = new moveThreadCls($forum, $thread);
	$babBody->babecho(	bab_printTemplate($temp,"posts.html", "movethread"));
	}

function viewPost($thread, $post)
	{
	global $babBody;

	class viewPostCls
		{
	
		var $postmessage;
		var $postsubject;
		var $postdate;
		var $postauthor;
		var $title;
		var $close;

		function viewPostCls($thread, $post)
			{
			global $babDB;
			$req = "select forum from ".BAB_THREADS_TBL." where id='".$babDB->db_escape_string($thread)."'";
			$arr = $babDB->db_fetch_array($babDB->db_query($req));
			$this->title = bab_toHtml(bab_getForumName($arr['forum']));
			$req = "select * from ".BAB_POSTS_TBL." where id='".$babDB->db_escape_string($post)."'";
			$arr = $babDB->db_fetch_array($babDB->db_query($req));
			$this->postdate = bab_toHtml(bab_strftime(bab_mktime($arr['date'])));
			$this->postauthor = bab_toHtml($arr['author']);
			$this->postauthor = bab_getForumContributor($arr['forum'], $arr['id_author'], $this->postauthor);
			$this->postsubject = bab_toHtml($arr['subject']);
			
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			$editor = new bab_contentEditor('bab_forum_post');
			$editor->setContent($arr['message']);
			$editor->setFormat($arr['message_format']);
			$this->postmessage = $editor->getHtml();
			$this->close = bab_translate("Close");
			$GLOBALS['babWebStat']->addForumPost($post);
			}
		}
	
	$temp = new viewPostCls($thread, $post);
	$babBody->babPopup(bab_printTemplate($temp,"posts.html", "postview"));
	}


function saveReply($forum, $thread, $post, $name, $subject)
	{
	global $babDB, $BAB_SESS_USER, $BAB_SESS_USERID, $babBody;
	
	require_once $GLOBALS['babInstallPath'].'utilit/eventforum.php';
	include_once $GLOBALS['babInstallPath'].'utilit/editorincl.php';

	$editor = new bab_contentEditor('bab_forum_post');
	$message = $editor->getContent();
	$messageFormat = $editor->getFormat();
	
	if( empty($message))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a content for your message")." !";
		return;
		}

	if( empty($subject))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a subject for your message")." !";
		return;
		}

	if( empty($BAB_SESS_USER))
		{
		if( empty($name))
			{
			$name = bab_translate("Anonymous");
			}
		$idauthor = 0;
		}
	else
		{
		$name = $BAB_SESS_USER;
		$idauthor = $BAB_SESS_USERID;
		}


	if( bab_isForumModerated($forum))
		$confirmed = 'N';
	else
		$confirmed = 'Y';


	$req = "insert into ".BAB_POSTS_TBL." (id_thread, date, subject, message, message_format, id_author, author, confirmed, id_parent) values ";
	$req .= "('" .$babDB->db_escape_string($thread). "', now(), '";
	$req .= $babDB->db_escape_string($subject). "', '" . $babDB->db_escape_string($message). "', '" . $babDB->db_escape_string($messageFormat). "', '" . $babDB->db_escape_string($idauthor). "', '". $babDB->db_escape_string($name);
	$req .= "', '". $confirmed."', '". $babDB->db_escape_string($post). "')";
	$res = $babDB->db_query($req);
	$idpost = $babDB->db_insert_id();

	if (bab_isAccessValid(BAB_FORUMSFILES_GROUPS_TBL,$forum))
		bab_uploadPostFiles($idpost, $forum);
	
	$req = "update ".BAB_THREADS_TBL." set lastpost='".$idpost."' where id='".$babDB->db_escape_string($thread)."'";
	$res = $babDB->db_query($req);

	$req = "select t.*, p.subject  from ".BAB_THREADS_TBL." t, ".BAB_POSTS_TBL." p where t.id='".$babDB->db_escape_string($thread)."' AND p.id=t.post";
	$res = $babDB->db_query($req);
	$arr = $babDB->db_fetch_array($res);
	if( $confirmed == "Y" && $arr['notify'] == "Y" && $arr['starter'] != 0)
		{
		$req = "select * from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($arr['starter'])."'";
		$res = $babDB->db_query($req);
		$arr = $babDB->db_fetch_array($res);
        notifyThreadAuthor(bab_getForumThreadTitle($thread), $arr['email'], $name, $idpost);
		}
		
	// fire event if no approbation on post

	if ($confirmed == "Y")
	{
		$event = new bab_eventForumAfterPostAdd;
			
		$event->setForum($forum);
		$event->setThread($arr['id'], $arr['subject']);
		$event->setPost($idpost, $name, 'Y' === $confirmed);
		
		bab_fireEvent($event);
	}
	
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$forum."&thread=".$thread."&post=".$post."&flat=".bab_rp('flat', '1'));
	exit;
	}


function updateReply($forum, $thread, $subject, $post)
	{
	global $babBody, $babDB, $moderator, $BAB_SESS_USERID;

	include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
	$editor = new bab_contentEditor('bab_forum_post');
	$message = $editor->getContent();
	$messageFormat = $editor->getFormat();
	
	if( empty($message))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a content for your message")." !";
		return;
		}

	$forums = bab_get_forums();

	$res = $babDB->db_query("select id_author from ".BAB_POSTS_TBL." where id='".$babDB->db_escape_string($post)."'");
	$arr = $babDB->db_fetch_array($res);
	if( ($moderator && $forums[$forum]['bupdatemoderator'] == 'Y' )|| ( $forums[$forum]['bupdateauthor'] == 'Y' && $BAB_SESS_USERID && $BAB_SESS_USERID == $arr['id_author']  ))
		{
		$req = "UPDATE ".BAB_POSTS_TBL." set message='".$babDB->db_escape_string($message)."', message_format='".$babDB->db_escape_string($messageFormat)."', subject='".$babDB->db_escape_string($subject)."', dateupdate=now() where id='".$babDB->db_escape_string($post)."'";

		$res = $babDB->db_query($req);

		if (bab_isAccessValid(BAB_FORUMSFILES_GROUPS_TBL,$forum))
			{
			$dfiles = bab_pp('dfiles', array());
			if( count($dfiles))
				{
				$files = bab_getPostFiles($forum,$post);
				foreach($files as $f)
					{
					if( in_array($f['name'], $dfiles))
						{
						@unlink($f['path']);
						}
					}
				}
			bab_uploadPostFiles($post, $forum);
			}
		}

	}

function closeThread($thread)
	{
	global $babDB;
	$req = "update ".BAB_THREADS_TBL." set active='N' where id='".$babDB->db_escape_string($thread)."'";
	$res = $babDB->db_query($req);
	}

function openThread($thread)
	{
	global $babDB;
	$req = "update ".BAB_THREADS_TBL." set active='Y' where id='".$babDB->db_escape_string($thread)."'";
	$res = $babDB->db_query($req);
	}

function confirmDeleteThread($forum, $thread)
	{
	include_once $GLOBALS['babInstallPath']."utilit/delincl.php";
	bab_deleteThread($forum, $thread);
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=threads&forum=".$forum);
	}

function confirmMoveThread($forum, $thread, $newforum, $flat)
	{
	global $babDB;
	if( $newforum && $thread )
		{
		$req = "update ".BAB_THREADS_TBL." set forum='".$babDB->db_escape_string($newforum)."' where id = '".$babDB->db_escape_string($thread)."'";
		$res = $babDB->db_query($req);
		}
	Header("Location: ". $GLOBALS['babUrlScript']."?tg=threads&flat=".$flat."&forum=".$forum);
	}

function dlfile($forum,$post,$name)
	{
	if (!bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $forum))
		return;
	$name = urldecode($name);
	$files = bab_getPostFiles($forum,$post);
	foreach ($files as $file)
		{
		if ($name == $file['name'])
			{
			$mime = bab_getFileMimeType($name);
			if( mb_strtolower(bab_browserAgent()) == "msie")
				header('Cache-Control: public');
			$inl = bab_getFileContentDisposition() == 1? 1: '';
			if( $inl == "1" )
				header("Content-Disposition: inline; filename=\"".$file['name']."\""."\n");
			else
				header("Content-Disposition: attachment; filename=\"".$file['name']."\""."\n");
			header("Content-Type: $mime"."\n");
			header("Content-Length: ". filesize($file['path'])."\n");
			header("Content-transfert-encoding: binary"."\n");

			$handle = fopen($file['path'], "r");
			while (!feof($handle)) {
			   $buffer = fread($handle, 4096);
			   echo $buffer;
			}
			fclose($handle);
			die();
			}
		}
	trigger_error('File has been deleted or upload directory has moved');
	}

/* main */
$idx = bab_rp('idx', 'List');
$pos = bab_rp('pos', 0);
$flat = bab_rp('flat', 0);
$forum = bab_rp('forum', 0);

if( isset($forum) && bab_isAccessValid(BAB_FORUMSMAN_GROUPS_TBL, $forum))
	{
	$moderator = true;
	}
else
	{
	$moderator = false;
	}

if( isset($add) && $add == 'addreply' && bab_isAccessValid(BAB_FORUMSREPLY_GROUPS_TBL, $forum))
	{
	$author = bab_pp('author', '');
	$subject = bab_pp('subject', '');
	$postid = bab_pp('postid', 0);
	saveReply($forum, $thread, $postid, $author, $subject);
	$post = $postid;
	}

$update = bab_rp('update', '');
if( $update == 'updatereply' )
	{
	updateReply($forum, $thread, $subject, $post);
	}

$move = bab_rp('move', '');
if( $move == 'movet' )
	{
	$thread = bab_pp('thread', 0);
	$newforum = bab_pp('newforum', 0);
	confirmMoveThread($forum, $thread, $newforum, $flat);
	}

if( $idx == 'Close' && $moderator)
	{
	closeThread($thread);
	$idx = 'List';
	}

if( $idx == 'Open' && $moderator)
	{
	openThread($thread);
	$idx = 'List';
	}

if( $idx == 'DeleteP' && $moderator)
	{
	bab_deletePost($forum, $post);
	unset($post);
	$idx = 'List';
	}

if( isset($action) && $action == 'Yes' && $moderator)
	{
	confirmDeleteThread($forum, $thread);
	}


if( !isset($post))
	{
	if( $moderator )
		{
		$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($thread)."' and id_parent='0'";
		}
	else
		{
		$req = "select * from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($thread)."' and id_parent='0' and confirmed='Y'";
		}
	$res = $babDB->db_query($req);
	if( $res && $babDB->db_num_rows($res) > 0)
		{
		$arr = $babDB->db_fetch_array($res);
		$post = $arr['id'];
		}
	else
		{
		$post = 0;
		}
	}

$babLevelTwo = bab_getForumName($forum);

switch($idx)
	{
	case 'viewp':
		if( $moderator || bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $forum))
			{
			viewPost($thread, $post);
			exit;
			}
		else
			{
			$babBody->title = bab_translate("Access denied");
			}
		break;

	case 'reply':
		if( bab_isAccessValid(BAB_FORUMSREPLY_GROUPS_TBL, $forum))
			{
			$babBody->title = bab_getForumName($forum);
			newReply($forum, $thread, $post);
			$babBody->addItemMenu('List', bab_translate("List"), $GLOBALS['babUrlScript'].'?tg=posts&idx=List&forum='.$forum.'&thread='.$thread.'&post='.$post.'&flat='.$flat);
			$open = bab_isForumThreadOpen($forum, $thread);
			if( bab_isAccessValid(BAB_FORUMSREPLY_GROUPS_TBL, $forum) && $open)
				{
				$babBody->addItemMenu('reply', bab_translate("Reply"), $GLOBALS['babUrlScript'].'?tg=posts&idx=reply&forum='.$forum.'&thread='.$thread.'&post='.$post.'&flat='.$flat);
				}
			if( $moderator )
				{
				if($open)
					{
					$babBody->addItemMenu('Close', bab_translate("Close thread"), $GLOBALS['babUrlScript'].'?tg=posts&idx=Close&forum='.$forum.'&thread='.$thread.'&flat='.$flat);
					}
				else
					{
					$babBody->addItemMenu('Open', bab_translate("Open thread"), $GLOBALS['babUrlScript'].'?tg=posts&idx=Open&forum='.$forum.'&thread='.$thread.'&flat='.$flat);
					}
				}
			}		
		break;

	case 'Modify':
		$babBody->title = bab_getForumName($forum);
		if( editPost($forum, $thread, $post))
			{
			$babBody->addItemMenu('List', bab_translate("List"), $GLOBALS['babUrlScript'].'?tg=posts&idx=List&forum='.$forum.'&thread='.$thread.'&post='.$post.'&flat='.$flat);
			$open = bab_isForumThreadOpen($forum, $thread);
			if( bab_isAccessValid(BAB_FORUMSREPLY_GROUPS_TBL, $forum) && $open)
				{
				$babBody->addItemMenu('reply', bab_translate("Reply"), $GLOBALS['babUrlScript'].'?tg=posts&idx=reply&forum='.$forum.'&thread='.$thread.'&post='.$post.'&flat='.$flat);
				}
			if($open)
				{
				$babBody->addItemMenu('Close', bab_translate("Close thread"), $GLOBALS['babUrlScript'].'?tg=posts&idx=Close&forum='.$forum.'&thread='.$thread.'&flat='.$flat);
				}
			else
				{
				$babBody->addItemMenu('Open', bab_translate("Open thread"), $GLOBALS['babUrlScript'].'?tg=posts&idx=Open&forum='.$forum.'&thread='.$thread.'&flat='.$flat);
				}
			$babBody->addItemMenu('Modify', bab_translate("Modify"), $GLOBALS['babUrlScript'].'?tg=posts&idx=Modify&forum='.$forum.'&thread='.$thread.'&post='.$post.'&flat='.$flat);
			}
		else
			{
			$babBody->msgerror = bab_translate("Access denied");
			}
		break;
	case 'DeleteT':
		if( $moderator)
			{
			deleteThread($forum, $thread);
			if( bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $forum))
				{
				$babBody->title = bab_getForumName($forum);
				$babBody->addItemMenu('List', bab_translate("List"), $GLOBALS['babUrlScript'].'?tg=posts&idx=List&forum='.$forum.'&thread='.$thread.'&post='.$post.'&flat='.$flat);
				$babBody->addItemMenu('DeleteT', bab_translate("Delete thread"), $GLOBALS['babUrlScript'].'?tg=posts&idx=DeleteT&forum='.$forum.'&thread='.$thread.'&flat='.$flat);
				}		
			}
		break;

	case 'MoveT':
		if( $moderator)
			{
			moveThread($forum, $thread);
			if( bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $forum))
				{
				$babBody->title = bab_getForumName($forum);
				$babBody->addItemMenu('List', bab_translate("List"), $GLOBALS['babUrlScript'].'?tg=posts&idx=List&forum='.$forum.'&thread='.$thread.'&post='.$post.'&flat='.$flat);
				$babBody->addItemMenu('MoveT', bab_translate("Move thread"), $GLOBALS['babUrlScript'].'?tg=posts&idx=MoveT&forum='.$forum.'&thread='.$thread.'&flat='.$flat);
				}		
			}
		break;

	case 'Confirm':
		bab_confirmPost($forum, $thread, $post);
		Header('Location: '. $GLOBALS['babUrlScript'].'?tg=threads&idx=List&forum='.$forum);
		exit;
		break;

	case 'dlfile':
		dlfile($_GET['forum'],$_GET['post'],$_GET['file']);
		
		break;

	case 'List':
	default:
		$babBody->title = bab_getForumName($forum);
		$open = bab_isForumThreadOpen($forum, $thread);
		if( bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $forum))
			{
			$GLOBALS['babWebStat']->addForumThread($thread);
			if( $flat == '1')
				{
				$count = listPostsFlat($forum, $thread, $open, $pos);
				}
			else
				{
				$count = listPosts($forum, $thread, $post);
				}
			$babBody->addItemMenu('Threads', bab_translate("Threads"), $GLOBALS['babUrlScript'].'?tg=threads&idx=List&forum='.$forum.'&flat='.$flat);
			$babBody->addItemMenu('List', bab_translate("List"), $GLOBALS['babUrlScript'].'?tg=posts&idx=List&forum='.$forum.'&thread='.$thread.'&post='.$post.'&flat='.$flat);
			if( bab_isAccessValid(BAB_FORUMSPOST_GROUPS_TBL, $forum))
				{
				$babBody->addItemMenu('newthread', bab_translate("New thread"), $GLOBALS['babUrlScript'].'?tg=threads&idx=newthread&forum='.$forum.'&flat='.$flat);
				}
			if( $moderator )
				{
				if( $open)
					{
					$babBody->addItemMenu('Close', bab_translate("Close thread"), $GLOBALS['babUrlScript'].'?tg=posts&idx=Close&forum='.$forum.'&thread='.$thread.'&flat='.$flat);
					}
				else
					{
					$babBody->addItemMenu('Open', bab_translate("Open thread"), $GLOBALS['babUrlScript'].'?tg=posts&idx=Open&forum='.$forum.'&thread='.$thread.'&flat='.$flat);
					}
				$babBody->addItemMenu('DeleteT', bab_translate("Delete thread"), $GLOBALS['babUrlScript'].'?tg=posts&idx=DeleteT&forum='.$forum.'&thread='.$thread.'&flat='.$flat);
				$forums = bab_get_forums();
				if( count($forums) > 1 )
					{
					$babBody->addItemMenu('MoveT', bab_translate("Move thread"), $GLOBALS['babUrlScript'].'?tg=posts&idx=MoveT&forum='.$forum.'&thread='.$thread.'&flat='.$flat);
					}
				}
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>