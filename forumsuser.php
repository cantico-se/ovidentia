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
require_once dirname(__FILE__).'/utilit/registerglobals.php';
include_once $babInstallPath.'utilit/forumincl.php';

// serach type 
define('BAB_FORUMS_ST_LASTPOSTS',	'lastposts');
define('BAB_FORUMS_ST_MYPOSTS',		'myposts');
define('BAB_FORUMS_ST_UNANSWERED',	'unanswered');
define('BAB_FORUMS_ST_UNCONFIRMED',	'unconfirmed');

define('BAB_FORUMS_MAXRESULTS'	, 25);


function listForums()
	{
	global $babBody;

	class listForumsCls
		{

		public $altBg = true;

		function listForumsCls()
			{
			global $babBody, $babDB;

			$this->forums_txt = bab_translate("Forums");
			$this->threads_txt = bab_translate("Threads");
			$this->posts_txt = bab_translate("Posts");
			$this->lastposts_txt = bab_translate("Last posts");
			$this->recentposts_txt = bab_translate("New posts");
			$this->noposts_txt = bab_translate("No new posts");
			$this->viewlastpost_txt = bab_translate("View latest post");

			$this->whoisonline_txt = bab_translate("Who is online");
			$this->nbusers_txt = bab_translate("Number of users using forums");
			$this->nbregistered_txt = bab_translate("Registered users");
			$this->nbanonymous_txt = bab_translate("Anonymous users");
			$this->nbtotalnewposts_txt = bab_translate("Number of posts since last visit");
			$this->nbtotalnewposts = 0;

			$fv = bab_get_forums();

			$this->forums = array();
			if( count($fv))
			{
			list($iddir) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group='".$babDB->db_escape_string(BAB_REGISTERED_GROUP)."'"));
			$this->baccessdir = bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $iddir);
			foreach( $fv as $key => $val )
				{
				$val['id'] = $key;
				list($val['threads']) = $babDB->db_fetch_row($babDB->db_query("select count(id) from ".BAB_THREADS_TBL." where forum='".$babDB->db_escape_string($key)."'"));
				list($val['posts']) = $babDB->db_fetch_row($babDB->db_query("select count(p.id) from ".BAB_POSTS_TBL." p left join ".BAB_THREADS_TBL." t on p.id_thread = t.id where t.forum = '".$babDB->db_escape_string($key)."' and p.id_parent!='0'"));
				$res = $babDB->db_query("select p.* from ".BAB_POSTS_TBL." p left join ".BAB_THREADS_TBL." t on p.id_thread = t.id where t.forum = '".$babDB->db_escape_string($key)."' and p.id=t.lastpost and confirmed='Y' order by p.date desc limit 0,1");
				$val['lastpostauthoremail'] = '';
				$val['lastpostauthordetails'] = '';
				if( $res && $babDB->db_num_rows($res) > 0 )
					{
					$arr = $babDB->db_fetch_array($res);
					$val['author'] = $arr['author'];
					$val['date'] = bab_shortDate(bab_mktime($arr['date']), true);
					$res = $babDB->db_query("select count(p.id) as total from ".BAB_POSTS_TBL." p left join ".BAB_THREADS_TBL." t on p.id_thread = t.id where p.id_thread = '".$babDB->db_escape_string($arr['id_thread'])."' and confirmed='Y'");
					$rr = $babDB->db_fetch_array($res);
					$val['lastposturl'] = '&thread='.$arr['id_thread'];
					if( $rr['total'] >  $val['display'])
						{
						$val['lastposturl'] .= '&pos='.($rr['total'] - $val['display']);
						}
					$val['lastposturl'] .= '#p'.$arr['id'];
					if( $arr['id_author'] != 0 && $val['bdisplayemailaddress'] == 'Y')
						{
						$res = $babDB->db_query("select email from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($arr['id_author'])."'");
						if( $res && $babDB->db_num_rows($res) > 0 )
							{
							$rr = $babDB->db_fetch_array($res);
							$val['lastpostauthoremail'] = $rr['email'];
							}
						}
					if( $arr['id_author'] != 0 && $val['bdisplayauhtordetails'] == 'Y' && $this->baccessdir)
						{
						list($iddbuser) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where id_user='".$babDB->db_escape_string($arr['id_author'])."' and id_directory='0'"));
						$val['lastpostauthordetails'] = $GLOBALS['babUrlScript']."?tg=directory&idx=ddbovml&directoryid=".$iddir."&userid=".$iddbuser;
						}
						
					if( $arr['id_author'] != 0 )
						{
							$val['author'] = bab_getForumContributor($key, $arr['id_author'], $val['author']);
						}
					}
				else
					{
					$val['author'] = '';
					$val['date'] = '';
					$val['lastposturl'] ='';
					}

				if($GLOBALS['BAB_SESS_LOGGED'])
					{
					list($val['nbnewposts']) = $babDB->db_fetch_row($babDB->db_query("select count(p.id) from ".BAB_POSTS_TBL." p left join ".BAB_THREADS_TBL." t on p.id_thread = t.id where t.forum = '".$babDB->db_escape_string($key)."' and p.date_confirm >= '".$babDB->db_escape_string($babBody->lastlog)."' and p.confirmed='Y'"));
					$this->nbtotalnewposts += $val['nbnewposts'];
					}
				else
					{
					$val['nbnewposts'] = 0;
					}
				$this->forums[] = $val;
				}
			}
			$this->count = count($this->forums	);

			$this->nbusers = 0;
			$this->nbregistered = 0;
			$this->nbanonymous = 0;
			$res = $babDB->db_query("select id_user, dateact from ".BAB_USERS_LOG_TBL." where tg in ('posts', 'threads', 'forumsuser')");
			while( $arr = $babDB->db_fetch_array($res))
				{
				$this->nbusers++;
				if( $arr['id_user'] )
					{
					$this->nbregistered++;
					}
				else
					{
					$this->nbanonymous++;
					}
				}
			}

		function getNextForum()
			{
			static $i=0;
			if( $i < $this->count )
				{

				$this->altBg = !$this->altBg;

				$this->forumname = bab_toHtml($this->forums[$i]['name']);
				$this->forumdescription = bab_toHtml($this->forums[$i]['description']);
				$this->threads = bab_toHtml($this->forums[$i]['threads']);
				$this->posts = bab_toHtml($this->forums[$i]['posts']);
				$this->lastpostauthor = bab_toHtml($this->forums[$i]['author']);
				$this->lastpostdate = bab_toHtml($this->forums[$i]['date']);
				$this->nbnewposts = bab_toHtml($this->forums[$i]['nbnewposts']);
				$this->lastpostauthoremail = bab_toHtml($this->forums[$i]['lastpostauthoremail']);
				$this->forumurl = $GLOBALS['babUrlScript']."?tg=threads&forum=".urlencode($this->forums[$i]['id']);
				$this->lastposturl = $GLOBALS['babUrlScript']."?tg=posts&flat=1&forum=".urlencode($this->forums[$i]['id']).$this->forums[$i]['lastposturl'];
				$this->lastpostauthordetails = bab_toHtml($this->forums[$i]['lastpostauthordetails']);
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}
		}

	$temp = new listForumsCls();
	$babBody->babecho(	bab_printTemplate($temp,"forumsuser.html", "forumslist"));
	return $temp->count;
	}


function searchForums()
	{
	global $babBody;

	class searchForumsCls
		{

		public $altBg = true;

		function searchForumsCls()
			{
			global $babBody, $babDB;

			$this->search_txt = bab_translate("Search");
			$this->forum_txt = bab_translate("Forum");
			$this->wordtosearch_txt = bab_translate("Text to search");
			$this->selecforum_txt = bab_translate("All");
			$this->searchoptions_txt = bab_translate("Options");
			$this->optionall_txt = bab_translate("Search post title and message");
			$this->optionsubject_txt = bab_translate("Search post message only");
			$this->wordvalue = bab_toHtml(bab_rp('sword', ''));

			$this->forums = bab_get_forums();
			}

		function getNextForum()
			{
			static $i=0;
			if( list($key, $val) = each($this->forums))
				{

				$this->forumid = bab_toHtml($key);
				$this->forumname = bab_toHtml($val['name']);
				if( isset($_REQUEST['forum']) && $this->forumid == $_REQUEST['forum'] )
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
				{
				return false;
				}
			}
		}

	$temp = new searchForumsCls();
	$babBody->babecho(	bab_printTemplate($temp,"forumsuser.html", "forumssearch"));
	}

function displaySearchResultsForums()
	{
	global $babBody;

	class displaySearchResultsForumsCls
		{

		function displaySearchResultsForumsCls()
			{
			global $babBody, $babDB;

			$this->forum_txt = bab_translate("Forum");
			$this->thread_txt = bab_translate("Thread");
			$this->author_txt = bab_translate("Author");
			$this->post_txt = bab_translate("Posts");
			$this->viewpost_txt = bab_translate("View post");

			$this->altbg = true;
		
			$this->count = 0;
			$this->countpages = 0;
			$this->forums = bab_get_forums();
			$fstype = bab_rp('fstype', '');
			list($this->iddir) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group='".$babDB->db_escape_string(BAB_REGISTERED_GROUP)."'"));

			switch($fstype)
				{
				case BAB_FORUMS_ST_LASTPOSTS:
					break;
				case BAB_FORUMS_ST_MYPOSTS:
					break;
				case BAB_FORUMS_ST_UNANSWERED:
					break;
				case BAB_FORUMS_ST_UNCONFIRMED:
					$req = "select tt.forum, pt.*, pt2.subject as thread from ".BAB_POSTS_TBL." pt left join ".BAB_THREADS_TBL." tt on tt.id=pt.id_thread left join ".BAB_POSTS_TBL." pt2 on pt2.id=tt.post where forum in (".$babDB->quote($fids).") and active='Y' and pt.confirmed='N'";
					break;
				default:
					$forum = bab_rp('forum', '');
					if( empty($forum))
					{
						$fids = array_keys($this->forums);
					}
					else
					{
						$fids = array($forum);
					}

					$sword = bab_rp('sword', '');
					if( !empty($sword))
					{
					$sopt = bab_rp('sopt', 'all');
					$spos = bab_rp('spos', 0);
					
					$req = " ".BAB_POSTS_TBL." pt left join ".BAB_THREADS_TBL." tt on tt.id=pt.id_thread left join ".BAB_POSTS_TBL." pt2 on pt2.id=tt.post left join ".BAB_FORUMS_TBL." ft on ft.id=tt.forum where forum in (".$babDB->quote($fids).") and tt.active='Y' ";

					if( $sopt == 'subject' )
						{
						$req .= "and pt.subject like '%".$babDB->db_escape_like($sword)."%' ";
						}
					else
						{
						$req .= "and (pt.subject like '%".$babDB->db_escape_like($sword)."%' or pt.message like '%".$babDB->db_escape_like($sword)."%') ";
						}
					
					$res = $babDB->db_query("select count(pt.id) as total from ".$req);							
					$row = $babDB->db_fetch_array($res);
					$total = $row["total"];

					$req = "select ft.name, tt.forum, pt.*, pt2.subject as thread, tt.id as id_thread from ".$req." order by pt.date desc";
					if( $total > BAB_FORUMS_MAXRESULTS)
						{
						$req .= " limit ".$spos.",".BAB_FORUMS_MAXRESULTS;
						$this->gotopage_txt = bab_translate("Goto page");
						$this->gotourl = $GLOBALS['babUrlScript']."?tg=forumsuser&idx=searchr&forum=".urlencode($forum)."&sword=".urlencode($sword)."&sopt=".urlencode($sopt)."&spos=";
						$this->gotopages = bab_generatePagination($total, BAB_FORUMS_MAXRESULTS, $spos);
						$this->countpages = count($this->gotopages);
						}
					$this->res = $babDB->db_query($req);
					$this->count = $babDB->db_num_rows($this->res);
					}
					else
					{
						$idx = 'search';
					}
					break;
				} // end switch

			}

		function getnext()
			{
			global $babDB;
			static $i=0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->res);
				$this->forum_name = bab_toHtml($arr['name']);
				$this->forum_url = $GLOBALS['babUrlScript']."?tg=threads&forum=".urlencode($arr['forum']);
				$this->thread_name = bab_toHtml($arr['thread']);
				$this->thread_url = $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".urlencode($arr['forum'])."&thread=".urlencode($arr['id_thread'])."&flat=".urlencode(($this->forums[$arr['forum']]['bflatview']=='Y'?1:0));
				if( $arr['id_author'] )
					{
					$this->author_name = bab_toHtml(bab_getUserName($arr['id_author']));
					$this->author_name = bab_getForumContributor($arr['forum'], $arr['id_author'], $this->author_name);
					}
				else
					{
					$this->author_name = bab_toHtml($arr['author']);
					}

				$this->post_name = bab_toHtml($arr['subject']);
				$this->post_url = $GLOBALS['babUrlScript']."?tg=forumsuser&idx=viewr&post=".urlencode($arr['id']);


				$this->authordetailsurl = '';

				if( $arr['id_author'] != 0 && $this->forums[$arr['forum']]['bdisplayauhtordetails'] == 'Y')
					{
					if( bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $this->iddir))
						{
						list($iddbuser) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where id_user='".$babDB->db_escape_string($arr['id_author'])."' and id_directory='0'"));
						$this->authordetailsurl =  $GLOBALS['babUrlScript']."?tg=directory&idx=ddbovml&directoryid=".urlencode($this->iddir)."&userid=".urlencode($iddbuser);	
						}
					}


				$this->authoremail = '';
				if( $this->forums[$arr['forum']]['bdisplayemailaddress'] == 'Y' )
					{
					$idauthor = $arr['id_author'] != 0? $arr['id_author']: bab_getUserId( $arr['id_author']); 
					if( $idauthor )
						{
						$res = $babDB->db_query("select email from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($idauthor)."'");
						if( $res && $babDB->db_num_rows($res) > 0 )
							{
							$rr = $babDB->db_fetch_array($res);
							$this->authoremail = bab_toHtml($rr['email']);
							}
						}
					}
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}

		function getnextforum()
			{
			static $i=0;
			if( list($key, $val) = each($this->forums))
				{
				$this->forumid = bab_toHtml($key);
				$this->forumname = bab_toHtml($val['name']);
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}

		function getnextpage()
			{
			static $i = 0;
			if( $i < $this->countpages)
				{
				$this->page = bab_toHtml($this->gotopages[$i]['page']);
				$this->bpageurl = $this->gotopages[$i]['url'];
				$this->pageurl = $this->gotourl.$this->gotopages[$i]['pagepos'];
				$i++;
				return true;
				}
			else
				{
				$i=0;
				return false;
				}
			}
		}

	$temp = new displaySearchResultsForumsCls();
	$babBody->babecho(	bab_printTemplate($temp,"forumsuser.html", "threadlist"));
	}


function viewSearchResultForums()
	{
	global $babBody;

	class viewSearchResultForumsCLs
		{
		var $sContent;
		
		function viewSearchResultForumsCLs()
			{
			global $babBody, $babDB;
			$this->subject			= bab_translate("Subject");
			$this->author			= bab_translate("Author");
			$this->date				= bab_translate("Date");
			$this->date				= bab_translate("Message");
			$this->altrecentposts	= bab_translate("Recent posts");
			$this->t_files			= bab_translate("Dependent files");
			$this->message			= bab_translate("Message");
			$this->noposts_txt		= bab_translate("No new posts");
			$this->waiting_txt		= bab_translate("Waiting posts");
			$this->close_txt		= bab_translate("Close");
			$this->postsubject_txt	= bab_translate("Post subject");
			$this->posted_txt		= bab_translate("Posted");
			$this->sContent			= 'text/html; charset=' . bab_charset::getIso();

			$this->forums = bab_get_forums();
			
			$this->files = array();

			$post = bab_rp('post', 0);
			if( $post )
				{
				$res = $babDB->db_query("select pt.*, tt.forum from ".BAB_POSTS_TBL." pt left join ".BAB_THREADS_TBL." tt on tt.id=pt.id_thread where pt.id='".$babDB->db_escape_string($post)."'");
				if( $res && $babDB->db_num_rows($res) )
					{
					$arr = $babDB->db_fetch_array($res);
					if( isset($this->forums[$arr['forum']]))
						{
						$this->forum = $arr['forum'];

						$GLOBALS['babWebStat']->addForumPost($arr['id']);
						$this->files = bab_getPostFiles($this->forum,$arr['id']);

						$this->postdate = bab_strftime(bab_mktime($arr['date']));
						if( $arr['id_author'] )
							{
							$this->postauthor = bab_toHtml(bab_getUserName($arr['id_author']));
							$this->postauthor = bab_getForumContributor($this->forum, $arr['id_author'], $this->postauthor);
							}
						else
							{
							$this->postauthor = bab_toHtml($arr['author']);
							}
						$this->postsubject = bab_toHtml($arr['subject']);
						
						include_once $GLOBALS['babInstallPath'].'utilit/editorincl.php';

						$editor = new bab_contentEditor('bab_forum_post');
						$editor->setContent($arr['message']);
						$editor->setFormat($arr['message_format']);
						$this->postmessage = $editor->getHtml();

						list($this->iddir) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group='".$babDB->db_escape_string(BAB_REGISTERED_GROUP)."'"));
						$this->postauthordetailsurl = '';
						if( $arr['id_author'] != 0 && $this->forums[$this->forum]['bdisplayauhtordetails'] == 'Y')
							{
							list($this->iddir) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group='".$babDB->db_escape_string(BAB_REGISTERED_GROUP)."'"));
							if( bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $this->iddir))
								{
								list($iddbuser) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where id_user='".$babDB->db_escape_string($arr['id_author'])."' and id_directory='0'"));
								$this->postauthordetailsurl = $GLOBALS['babUrlScript']."?tg=directory&idx=ddbovml&directoryid=".$this->iddir."&userid=".$iddbuser;	
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
					else
						{
						}
					}
				}
			}



		function getnextfile()
			{
			if ($this->file = current($this->files))
				{
				$this->file_name = bab_toHtml($this->file['name']);
				$this->file_url = bab_toHtml($this->file['url']);
				next($this->files);
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new viewSearchResultForumsCLs();
	echo bab_printTemplate($temp,"forumsuser.html", "viewpost");
	}

function optionsForums()
	{
	global $babBody;

	class optionsForumsCls
		{

		public $altBg = true;

		function optionsForumsCls()
			{
			global $babBody, $babDB;

			$this->forums_txt = bab_translate("Forums");
			$this->nonotif_txt = bab_translate("No notifications");
			$this->allnotif_txt = bab_translate("All notifications");
			$this->onlynewthreadsnotif_txt = bab_translate("Only for new threads");
			$this->update_txt = bab_translate("Update");
			$this->forums = bab_get_forums();
			
			$this->forumnotif_none = BAB_FORUMNOTIF_NONE;
			$this->forumnotif_all = BAB_FORUMNOTIF_ALL;
			$this->forumnotif_newthreads = BAB_FORUMNOTIF_NEWTHREADS;
			
			}

		function getNextForum()
			{
			global $babDB, $BAB_SESS_USERID;
			static $i=0;
			if( list($key, $val) = each($this->forums))
				{
				$this->altBg = !$this->altBg;
					
				$this->forumid = bab_toHtml($key);
				$this->forumname = bab_toHtml($val['name']);
				$this->forumdescription = bab_toHtml($val['description']);
				$this->forumurl = $GLOBALS['babUrlScript']."?tg=threads&forum=".urlencode($this->forumid);
				$fnotif = BAB_FORUMNOTIF_ALL; 
				if( $BAB_SESS_USERID )
				{
					$res = $babDB->db_query("select forum_notification 
										from ".BAB_FORUMSNOTIFY_USERS_TBL." 
										where id_forum=".$babDB->quote($this->forumid)." 
										and id_user=".$babDB->quote($GLOBALS['BAB_SESS_USERID']));
					if( $res && $babDB->db_num_rows($res))
					{
						$arr = $babDB->db_fetch_array($res);
						$fnotif = $arr['forum_notification'];
					}
				}
				$this->checked_forumnotif_none = '';
				$this->checked_forumnotif_all = '';
				$this->checked_forumnotif_newthreads = '';
				switch($fnotif)
				{
					case BAB_FORUMNOTIF_NONE:
						$this->checked_forumnotif_none = 'checked="checked"';
						break;
					case BAB_FORUMNOTIF_NEWTHREADS:
						$this->checked_forumnotif_newthreads = 'checked="checked"';
						break;
					default:
						$this->checked_forumnotif_all = 'checked="checked"';
						break;
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

	$temp = new optionsForumsCls();
	$babBody->babecho(	bab_printTemplate($temp,"forumsuser.html", "forumsoptions"));
	}	

function saveForumsOptions()
{
	global $babDB;
	$babDB->db_query("delete from ".BAB_FORUMSNOTIFY_USERS_TBL." where id_user=".$babDB->quote($GLOBALS['BAB_SESS_USERID'])."");
	$forums = bab_get_forums();
	if( $GLOBALS['BAB_SESS_USERID'] )
	{
		foreach( $forums as $forumid => $info )
			{
				$sopt = bab_pp('sopt-'.$forumid, 1);
				$babDB->db_query("insert into ".BAB_FORUMSNOTIFY_USERS_TBL." ( id_forum, id_user, forum_notification ) values (".$babDB->quote($forumid).",".$babDB->quote($GLOBALS['BAB_SESS_USERID']).", ".$babDB->quote($sopt).")");
			}
	}
}	
	
/* main */
if(!isset($idx))
	{
	$idx = "list";
	}

$action = bab_pp('action', '');
if( $action == 'options')
{
	saveForumsOptions();
}

switch($idx)
	{
	case "options":
		optionsForums();
		$babBody->title = bab_translate("Choose how you would like to be notified");
		$babBody->addItemMenu("list", bab_translate("Forums"), $GLOBALS['babUrlScript']."?tg=forumsuser&idx=list");
		$babBody->addItemMenu("search", bab_translate("Search"), $GLOBALS['babUrlScript']."?tg=forumsuser&idx=search&sword=".urlencode(bab_rp('sword', '')));
		$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=forumsuser&idx=options");
		break;
	
	case "viewr":
		viewSearchResultForums();
		exit;
		break;

	case "searchr":
		displaySearchResultsForums();
		$babBody->addItemMenu("list", bab_translate("Forums"), $GLOBALS['babUrlScript']."?tg=forumsuser&idx=list");
		$babBody->addItemMenu("search", bab_translate("Search"), $GLOBALS['babUrlScript']."?tg=forumsuser&idx=search&sword=".urlencode(bab_rp('sword', '')));
		$babBody->addItemMenu("searchr", bab_translate("Results"), $GLOBALS['babUrlScript']."?tg=forumsuser&idx=search");
		$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=forumsuser&idx=options");
		break;
	case "search":
		searchForums();
		$babBody->addItemMenu("list", bab_translate("Forums"), $GLOBALS['babUrlScript']."?tg=forumsuser&idx=list");
		$babBody->addItemMenu("search", bab_translate("Search"), $GLOBALS['babUrlScript']."?tg=forumsuser&idx=search&sword=".urlencode(bab_rp('sword', '')));
		$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=forumsuser&idx=options");
		break;
	default:
	case "list":
		listForums();
		$babBody->addItemMenu("list", bab_translate("Forums"), $GLOBALS['babUrlScript']."?tg=forumsuser&idx=list");
		$babBody->addItemMenu("search", bab_translate("Search"), $GLOBALS['babUrlScript']."?tg=forumsuser&idx=search&sword=".urlencode(bab_rp('sword', '')));
		$babBody->addItemMenu("options", bab_translate("Options"), $GLOBALS['babUrlScript']."?tg=forumsuser&idx=options");
		bab_siteMap::setPosition('bab', 'UserForums');
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>
