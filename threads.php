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
include_once $babInstallPath.'utilit/mailincl.php';

function listThreads($forum, $active, $pos)
	{
	global $babBody;

	class temp
		{
		var $thread;
		var $starter;
		var $replies;
		var $repliesname;
		var $views;
		var $lastpost;
		var $lastpostdate;
		var $subjecturl;
		var $subjecturlflat;
		var $subjectname;

		var $arrthread = array();
		var $arrpost = array();
		var $db;
		var $count;
		var $res;
		var $forum;
		var $status;
		var $topurl;
		var $bottomurl;
		var $nexturl;
		var $prevurl;
		var $topname;
		var $bottomname;
		var $nextname;
		var $prevname;
		var $disabled;

		var $openthreadsinfo;
		var $waitthreadsinfo;
		var $closedthreadsinfo;
		var $brecent;
		var $altrecentposts;

		var $active;
		var $alternate;

		function temp($forum, $active, $pos)
			{
			global $babBody, $babDB;
			$this->topurl = '';
			$this->bottomurl = '';
			$this->nexturl = '';
			$this->prevurl = '';
			$this->topname = '';
			$this->bottomname = '';
			$this->nextname = '';
			$this->prevname = '';
			$this->thread = bab_translate("Thread");
			$this->starter = bab_translate("Starter");
			$this->repliesname = bab_translate("Replies");
			$this->views = bab_translate("Views");
			$this->lastpost = bab_translate("Last Post");
			$this->openthreadsinfo = bab_translate("Opened threads");
			$this->waitthreadsinfo = bab_translate("Waiting posts");
			$this->closedthreadsinfo = bab_translate("Closed threads");
			$this->altrecentposts = bab_translate("Recent posts");
			$this->jumpto_txt = bab_translate("Jump to");
			$this->selecforum_txt = bab_translate("Select a forum");
			$this->go_txt = bab_translate("Go");
			$this->noposts_txt = bab_translate("No new posts");
			$this->viewlastpost_txt = bab_translate("View latest post");
			$this->search_txt = bab_translate("Search");
			$this->alternate = 0;
			$this->active = $active;
			$this->altbg = true;

			$this->search_url = bab_toHtml($GLOBALS['babUrlScript'].'?tg=forumsuser&idx=search&forum='.$forum);

			$this->forums = bab_get_forums();

			if( $active == 'N')
				{
				$this->idx = 'ListC';
				}
			else
				{
				$active = 'Y';
				$this->idx = 'List';
				}
			$this->moderator = bab_isAccessValid(BAB_FORUMSMAN_GROUPS_TBL, $forum);

			$this->maxrows = $this->forums[$forum]['display'];
			$this->flat = $this->forums[$forum]['bflatview'] == 'Y'? 1: 0;
			$this->bdisplayemailaddress = $this->forums[$forum]['bdisplayemailaddress'];
			$this->bdisplayauhtordetails = $this->forums[$forum]['bdisplayauhtordetails'];;
			$this->bupdateauthor = $this->forums[$forum]['bupdateauthor'];;

			$req = "select count(*) as total from ".BAB_THREADS_TBL." where forum='".$babDB->db_escape_string($forum)."' and active='".$active."'";
			$this->res = $babDB->db_query($req);
			$row = $babDB->db_fetch_array($this->res);
			$total = $row['total'];

			$this->gotopage_txt = bab_translate("Goto page");
			$this->gotourl = $GLOBALS['babUrlScript'].'?tg=threads&idx='.$this->idx.'&forum='.$forum.'&pos=';
			$this->gotopages = bab_generatePagination($total, $this->maxrows, $pos);
			$this->countpages = count($this->gotopages);
		
			$req = "select tt.*, pt.author, pt.id_author, pt.date as lastpostdate from ".BAB_THREADS_TBL." tt left join ".BAB_POSTS_TBL." pt on tt.lastpost=pt.id where forum='".$babDB->db_escape_string($forum)."' and active='".$babDB->db_escape_string($active)."' order by pt.date desc";
			if( $total > $this->maxrows)
				{
				$req .= ' limit '.$pos.','.$this->maxrows;
				}

			$this->res = $babDB->db_query($req);
			$this->count = $babDB->db_num_rows($this->res);
			$this->forum = $forum;

			unset($this->forums[$this->forum]);
			$this->countforums = count($this->forums);
			list($this->iddir) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group='".$babDB->db_escape_string(BAB_REGISTERED_GROUP)."'"));
			}

		function getnext(&$skip)
			{
			global $babBody, $babDB, $BAB_SESS_USERID;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$this->arrthread = $babDB->db_fetch_array($this->res);
				$this->thread_views = bab_toHtml($this->arrthread['views']);
				$res = $babDB->db_query("select * from ".BAB_POSTS_TBL." where id='".$babDB->db_escape_string($this->arrthread['post'])."'");
				$ar = $babDB->db_fetch_array($res);

				
				$this->subjecturl = bab_toHtml($GLOBALS['babUrlScript'].'?tg=posts&idx=List&flat='.$this->flat.'&forum='.$this->forum.'&thread='.$this->arrthread['id'].'&views=1');
				$this->subjectname = bab_toHtml($ar['subject']);
				$this->subjecturlflat  = $this->subjecturl.'&flat='.$this->flat;

				$this->threadauthor = bab_toHtml(bab_getUserName($this->arrthread['starter']));
				$this->threadauthor = bab_getForumContributor($this->forum, $this->arrthread['starter'], $this->threadauthor);
				

				$this->threadauthordetailsurl = '';
				if( $this->arrthread['starter'] != 0 && $this->bdisplayauhtordetails == 'Y')
					{
					if( bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $this->iddir))
						{
						list($iddbuser) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where id_user='".$babDB->db_escape_string($this->arrthread['starter'])."' and id_directory='0'"));
						$this->threadauthordetailsurl = bab_toHtml($GLOBALS['babUrlScript'].'?tg=directory&idx=ddbovml&directoryid='.$this->iddir.'&userid='.$iddbuser);	
						}
					}


				$this->threadauthoremail = '';
				if( $this->bdisplayemailaddress == 'Y' )
					{
					$idauthor = $this->arrthread['starter'] != 0? $this->arrthread['starter']: bab_getUserId( $this->arrthread['author']); 
					if( $idauthor )
						{
						$res = $babDB->db_query("select email from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($idauthor)."'");
						if( $res && $babDB->db_num_rows($res) > 0 )
							{
							$rr = $babDB->db_fetch_array($res);
							$this->threadauthoremail = bab_toHtml($rr['email']);
							}
						}
					}

				$req = "select count(*) as total from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($this->arrthread['id'])."' and confirmed='Y'";
				$res = $babDB->db_query($req);
				$row = $babDB->db_fetch_array($res);
				$this->replies = bab_toHtml(($row['total'] > 0 ? ($row['total'] -1): 0));
				if( $row['total'] == 0 && $this->moderator == false && ($this->bupdateauthor == 'Y' && $BAB_SESS_USERID != $this->arrthread['starter']) ) 
					{
					$this->disabled = 1;
					$skip = true;
					$i++;
					return true;
					}
				else
					{
					$this->disabled = 0;
					}


				$res = $babDB->db_query("select count(*) as total from ".BAB_POSTS_TBL." where id_thread='".$babDB->db_escape_string($this->arrthread['id'])."' and confirmed='N'");
				$ar = $babDB->db_fetch_array($res);
				if( $this->arrthread['active'] != 'N' && $ar['total'] > 0)
					{
					$this->status = '*';
					}
				else
					{
					$this->status = '';
					}

				$this->gotothreadpages = array();
				if( ($row['total'] ) > $this->maxrows)
					{
					$total_pages = ceil( ( $row['total'] ) / $this->maxrows );
					$times = 1;
					for($j = 0; $j < $row['total']; $j += $this->maxrows)
						{

							$this->gotothreadurl = $GLOBALS['babUrlScript'].'?tg=posts&idx='.$this->idx.'&flat='.$this->flat.'&forum='.$this->forum.'&thread='.$this->arrthread['id'].'&pos=';

							$this->gotothreadpages[] = array($times, $j, 1);

							if( $times == 1 && $total_pages > 4 )
							{
								$this->gotothreadpages[] = array('...', 0, 0);
								$times = $total_pages - 3;
								$j += ( $total_pages - 4 ) * $this->maxrows;
							}
							else if ( $times < $total_pages )
							{
								$this->gotothreadpages[] = array(', ', 0, 0);
							}
							$times++;
						}
					}

				$this->countgotothreadpages = count($this->gotothreadpages);
				if( $this->countgotothreadpages )
					{
					$postpos = $this->gotothreadpages[$this->countgotothreadpages-1][1];
					}
				else
					{
					$postpos = '';
					}
				
				

				$this->lastpostdate = bab_toHtml(bab_shortDate(bab_mktime($this->arrthread['lastpostdate']), true));

				$this->lastpostauthordetailsurl = '';
				if( $this->arrthread['id_author'] != 0 && $this->bdisplayauhtordetails == 'Y')
					{
					if( bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $this->iddir))
						{
						list($iddbuser) = $babDB->db_fetch_row($babDB->db_query("select id from ".BAB_DBDIR_ENTRIES_TBL." where id_user='".$babDB->db_escape_string($this->arrthread['id_author'])."' and id_directory='0'"));
						$this->lastpostauthordetailsurl = bab_toHtml($GLOBALS['babUrlScript'].'?tg=directory&idx=ddbovml&directoryid='.$this->iddir.'&userid='.$iddbuser);	
						}
					}


				$this->lastpostauthoremail = '';
				if( $this->bdisplayemailaddress == 'Y' )
					{
					$idauthor = $this->arrthread['id_author'] != 0? $this->arrthread['id_author']: bab_getUserId( $this->arrthread['author']); 
					if( $idauthor )
						{
						$res = $babDB->db_query("select email from ".BAB_USERS_TBL." where id='".$babDB->db_escape_string($idauthor)."'");
						if( $res && $babDB->db_num_rows($res) > 0 )
							{
							$rr = $babDB->db_fetch_array($res);
							$this->lastpostauthoremail = bab_toHtml($rr['email']);
							}
						}
					}

				if( $this->arrthread['id_author'] )
					{
					$this->lastpostauthor = bab_getUserName($this->arrthread['id_author']);
					$this->lastpostauthor = bab_getForumContributor($this->forum, $this->arrthread['id_author'], $this->lastpostauthor);
					}
				else
					{
					$this->lastpostauthor = $this->arrthread['author'];
					}
				$this->lastpostauthor = bab_toHtml($this->lastpostauthor);
				$this->lastposturl = bab_toHtml($GLOBALS['babUrlScript'].'?tg=posts&flat='.$this->flat.'&forum='.$this->forum.'&thread='.$this->arrthread['id'].'&pos='.$postpos.'#p'.$this->arrthread['lastpost']);

				$this->brecent = false;
				if( mktime() - bab_mktime($this->arrthread['lastpostdate']) <= DELTA_TIME )
					$this->brecent = true;
				else if($GLOBALS['BAB_SESS_LOGGED'])
					{
					require_once dirname(__FILE__).'/utilit/userinfosincl.php';
					$usersettings = bab_userInfos::getUserSettings();
					
					if( $this->arrthread['lastpostdate'] >= $usersettings['lastlog'] )
						$this->brecent = true;
					}
					



				$i++;
				return true;
				}
			else
				return false;
			}

		function getnextthreadpage()
			{
			static $i = 0;
			if( $i < $this->countgotothreadpages)
				{
				$this->page = bab_toHtml($this->gotothreadpages[$i][0]);
				$this->bpageurl = $this->gotothreadpages[$i][2];
				$this->pageurl = bab_toHtml($this->gotothreadurl.$this->gotothreadpages[$i][1]);
				$i++;
				return true;
				}
			else
				{
				$i=0;
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
	
	$temp = new temp($forum, $active, $pos);
	$babBody->babecho(	bab_printTemplate($temp,'threads.html', 'threadlist'));
	return $temp->count;
	}

function newThread($forum)
	{
	global $babBody;
	
	class temp
		{
		var $subject;
		var $name;
		var $message;
		var $add;
		var $forum;
		var $username;
		var $anonyme;
		var $notifyme;
		var $noteforum;

		function temp($forum)
			{
			global $babBody, $BAB_SESS_USER, $BAB_SESS_USERID;
			$this->subject = bab_translate("Subject");
			$this->name = bab_translate("Your Name");
			$this->notifyme = bab_translate("Notify me whenever someone replies ( only valid for registered users )");
			$this->message = bab_translate("Message");
			$this->add = bab_translate("New thread");
			$this->post = bab_translate("Post");
			$this->t_files = bab_translate("Dependent files");
			$this->t_add_field = bab_translate("Add field");
			$this->t_remove_field = bab_translate("Remove field");
			$this->forum = bab_toHtml($forum);
			$this->flat = bab_toHtml(bab_rp('flat', 1));
			
			$oCaptcha = @bab_functionality::get('Captcha');
			$this->bUseCaptcha = false;
			if(false !== $oCaptcha && (!isset($BAB_SESS_USERID) || empty($BAB_SESS_USERID)))
			{
				$this->bUseCaptcha = true;
				$this->sCaptchaCaption1 = bab_translate("Word Verification");
				$this->sCaptchaSecurityData = $oCaptcha->getGetSecurityHtmlData();
				$this->sCaptchaCaption2 = bab_translate("Enter the letters in the image above");
			}

			if( !isset($_POST['subject']))
				{
				$this->subjectval = '';
				}
			else
				{
				$this->subjectval = bab_toHtml(bab_pp('subject', ''));
				}

			if( empty($BAB_SESS_USER))
				{
				$this->anonyme = 1;
				}
			else
				{
				$this->anonyme = 0;
				$this->username = bab_toHtml($BAB_SESS_USER);
				$this->username = bab_getForumContributor($this->forum, $BAB_SESS_USERID, $this->username);
				}
			$message = isset($_POST['message']) ? $_POST['message'] : '';
			
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			$editor = new bab_contentEditor('bab_forum_post');
			$editor->setContent($message);
//			$editor->setFormat('html');
			$this->editor = $editor->getEditor();

			$this->allow_post_files = bab_isAccessValid(BAB_FORUMSFILES_GROUPS_TBL,$forum);
			$this->maxfilesize = $GLOBALS['babMaxFileSize'];

			if( bab_isForumModerated($forum))
				$this->noteforum = bab_translate("Note: Posts are moderate and consequently your post will not be visible immediately");
			else
				$this->noteforum = '';
			}
		}

	$temp = new temp($forum);
	$babBody->babecho(	bab_printTemplate($temp,'threads.html', 'threadcreate'));
	}

function saveThread()
	{
	global $BAB_SESS_USER, $BAB_SESS_USERID, $babBody, $babDB;
	include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
	include_once $GLOBALS['babInstallPath']."utilit/eventforum.php";

	$forum = intval(bab_pp('forum', 0));
	$subject = strval(bab_pp('subject', ''));
	$notifyme = bab_pp('notifyme', 'N');
	$name = strval(bab_pp('uname', ''));
	
	
	$editor = new bab_contentEditor('bab_forum_post');
	$message = $editor->getContent();

	if(!bab_isAccessValid(BAB_FORUMSPOST_GROUPS_TBL, $forum))
		{
		$babBody->msgerror = bab_translate("Access denied");
		return false;
		}

	if( empty($message))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a content for your message").' !';
		return false;
		}

	if( empty($subject))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a subject for your message").' !';
		return false;
		}

	$oCaptcha = @bab_functionality::get('Captcha');
	if(false !== $oCaptcha && (!isset($BAB_SESS_USERID) || empty($BAB_SESS_USERID)))
		{
		$sCaptchaSecurityCode = bab_pp('sCaptchaSecurityCode', '');
					
		if(!$oCaptcha->securityCodeValid($sCaptchaSecurityCode))
			{
			$babBody->msgerror = bab_translate("The captcha value is incorrect");
			return false;
			}
		}

	if( empty($BAB_SESS_USER))
		{
		if( empty($name))
			{
			$name = bab_translate("Anonymous");
			}
		$idstarter = 0;
		}
	else
		{
		$name = $BAB_SESS_USER;
		$idstarter = $BAB_SESS_USERID;
		}

	if( $notifyme == 'Y')
		$notifyme = 'Y';
	else
		$notifyme = 'N';

	$req = "insert into ".BAB_THREADS_TBL." (forum, date, notify, starter) values ";
	$req .= "('" .$babDB->db_escape_string($forum). "', now(), '" . $notifyme. "', '". $babDB->db_escape_string($idstarter). "')";
	$res = $babDB->db_query($req);
	$idthread = $babDB->db_insert_id();

	$arr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_FORUMS_TBL." where id='".$babDB->db_escape_string($forum)."'"));

	if( $arr['moderation'] == 'Y' )
		{
		$confirmed = 'N';
		}
	else
		{
		$confirmed = 'Y';
		}


	$req = "insert into ".BAB_POSTS_TBL." (id_thread, date, subject, message, id_author, author, confirmed, date_confirm) values ";
	$req .= "('" .$babDB->db_escape_string($idthread). "', now(), '";
	$req .= $babDB->db_escape_string($subject). "', '" . $babDB->db_escape_string($message). "', '" . $babDB->db_escape_string($idstarter). "', '". $babDB->db_escape_string($name);


	$req .= "', '". $babDB->db_escape_string($confirmed). "', now())";
	$res = $babDB->db_query($req);
	$idpost = $babDB->db_insert_id();

	if (bab_isAccessValid(BAB_FORUMSFILES_GROUPS_TBL,$forum))
		{
		bab_uploadPostFiles($idpost, $forum);
		}
	
	$req = "update ".BAB_THREADS_TBL." set 
		lastpost='".$babDB->db_escape_string($idpost)."', 
		post='".$babDB->db_escape_string($idpost)."' 
		where id = '".$babDB->db_escape_string($idthread)."'";
	$res = $babDB->db_query($req);

	$event = new bab_eventForumAfterThreadAdd;
		
	$event->setForum($forum);
	$event->setThread($idthread, $subject);
	$event->setPost($idpost, $name, 'Y' === $confirmed);
	
	bab_fireEvent($event);

	Header('Location: '. $GLOBALS['babUrlScript'].'?tg=threads&forum='.urlencode($forum));
	exit;
	}

function getClosedThreads($forum)
	{
	global $babDB;
	$req = "select count(*) as total from ".BAB_THREADS_TBL." where forum='".$babDB->db_escape_string($forum)."' and active='N'";
	$res = $babDB->db_query($req);
	$arr = $babDB->db_fetch_array($res);
	return $arr['total'];
	}

/* main */
if(!isset($idx))
	{
	$idx = 'List';
	}

if( !isset($pos))
	$pos = 0;

if( isset($add) && $add == 'addthread' && bab_isAccessValid(BAB_FORUMSPOST_GROUPS_TBL, $forum))
	{
	if (!saveThread())
		{
		$idx = 'newthread';
		}
	}




switch($idx)
	{
	case 'newthread':
		if( bab_isAccessValid(BAB_FORUMSPOST_GROUPS_TBL, $forum))
			{
			$babBody->title = bab_getForumName($forum);
			newThread($forum);
			$babBody->addItemMenu('List', bab_translate("Threads"), $GLOBALS['babUrlScript'].'?tg=threads&idx=List&forum='.$forum.'&flat='.bab_rp('flat', 1));
			$babBody->addItemMenu('newthread', bab_translate("New thread"), $GLOBALS['babUrlScript'].'?tg=threads&idx=newthread&forum='.$forum);

			}		
		break;

	case 'ListC':
		$babBody->title = bab_getForumName($forum);
		if( bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $forum))
			{
			$babBody->addItemMenu('List', bab_translate("Threads"), $GLOBALS['babUrlScript'].'?tg=threads&idx=List&forum='.$forum);
			$count = listThreads($forum, 'N', $pos);
			if( $count > 0)
				$babBody->addItemMenu('ListC', bab_translate("Closed"), $GLOBALS['babUrlScript'].'?tg=threads&idx=ListC&forum='.$forum);

			if( bab_isAccessValid(BAB_FORUMSPOST_GROUPS_TBL, $forum))
				{
				$babBody->addItemMenu('newthread', bab_translate("New thread"), $GLOBALS['babUrlScript'].'?tg=threads&idx=newthread&forum='.$forum);
				}
			}
		break;

	default:
	case 'List':
		$babBody->title = bab_getForumName($forum);
		if( bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $forum))
			{
			$GLOBALS['babWebStat']->addForum($forum);
			$count = listThreads($forum, 'Y', $pos);
			$babBody->addItemMenu('List', bab_translate("Threads"), $GLOBALS['babUrlScript'].'?tg=threads&idx=List&forum='.$forum);
			if( getClosedThreads($forum) > 0)
				$babBody->addItemMenu('ListC', bab_translate("Closed"), $GLOBALS['babUrlScript'].'?tg=threads&idx=ListC&forum='.$forum);

			if( bab_isAccessValid(BAB_FORUMSPOST_GROUPS_TBL, $forum))
				{
				$babBody->addItemMenu('newthread', bab_translate("New thread"), $GLOBALS['babUrlScript'].'?tg=threads&idx=newthread&forum='.$forum);
				}
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);
bab_siteMap::setPosition('bab', 'UserForum'.$forum);
?>
