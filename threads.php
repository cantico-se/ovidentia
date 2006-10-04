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
include_once $babInstallPath."utilit/forumincl.php";
include_once $babInstallPath."utilit/mailincl.php";

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
			global $babBody;
			$this->topurl = "";
			$this->bottomurl = "";
			$this->nexturl = "";
			$this->prevurl = "";
			$this->topname = "";
			$this->bottomname = "";
			$this->nextname = "";
			$this->prevname = "";
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

			$this->search_url = $GLOBALS['babUrlScript']."?tg=forumsuser&idx=search&forum=".$forum;

			$this->forums = $babBody->get_forums();

			if( $active == 'N')
				{
				$this->idx = "ListC";
				}
			else
				{
				$active = 'Y';
				$this->idx = "List";
				}
			$this->moderator = bab_isAccessValid(BAB_FORUMSMAN_GROUPS_TBL, $forum);

			$this->db = $GLOBALS['babDB'];
			$this->maxrows = $this->forums[$forum]['display'];
			$this->flat = $this->forums[$forum]['bflatview'] == 'Y'? 1: 0;
			$this->bdisplayemailaddress = $this->forums[$forum]['bdisplayemailaddress'];
			$this->bdisplayauhtordetails = $this->forums[$forum]['bdisplayauhtordetails'];;
			$this->bupdateauthor = $this->forums[$forum]['bupdateauthor'];;

			$req = "select count(*) as total from ".BAB_THREADS_TBL." where forum='".$forum."' and active='".$active."'";
			$this->res = $this->db->db_query($req);
			$row = $this->db->db_fetch_array($this->res);
			$total = $row["total"];

			$this->gotopage_txt = bab_translate("Goto page");
			$this->gotourl = $GLOBALS['babUrlScript']."?tg=threads&idx=".$this->idx."&forum=".$forum."&pos=";
			$this->gotopages = bab_generatePagination($total, $this->maxrows, $pos);
			$this->countpages = count($this->gotopages);
		
			$req = "select tt.*, pt.subject, pt.author from ".BAB_THREADS_TBL." tt left join ".BAB_POSTS_TBL." pt on tt.post=pt.id where forum='".$forum."' and active='".$active."' order by pt.date desc";
			if( $total > $this->maxrows)
				{
				$req .= " limit ".$pos.",".$this->maxrows;
				}

			$this->res = $this->db->db_query($req);
			$this->count = $this->db->db_num_rows($this->res);
			$this->forum = $forum;

			unset($this->forums[$this->forum]);
			$this->countforums = count($this->forums);
			list($this->iddir) = $this->db->db_fetch_row($this->db->db_query("select id from ".BAB_DB_DIRECTORIES_TBL." where id_group='".BAB_REGISTERED_GROUP."'"));
			}

		function getnext()
			{
			global $babBody, $BAB_SESS_USERID;
			static $i = 0;
			if( $i < $this->count)
				{
				$this->altbg = !$this->altbg;
				$this->arrthread = $this->db->db_fetch_array($this->res);
				$this->subjecturl = $GLOBALS['babUrlScript']."?tg=posts&idx=List&flat=".$this->flat."&forum=".$this->forum."&thread=".$this->arrthread['id']."&views=1";
				$this->subjectname = $this->arrthread['subject'];
				$this->subjecturlflat  = $this->subjecturl."&flat=".$this->flat;

				$this->threadauthordetailsurl = '';
				if( $this->arrthread['starter'] != 0 && $this->bdisplayauhtordetails == 'Y')
					{
					if( bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $this->iddir))
						{
						$this->threadauthordetailsurl = $GLOBALS['babUrlScript']."?tg=directory&idx=ddbovml&directoryid=".$this->iddir."&userid=".$this->arrthread['starter'];	
						}
					}


				$this->threadauthoremail = '';
				if( $this->bdisplayemailaddress == 'Y' )
					{
					$idauthor = $this->arrthread['starter'] != 0? $this->arrthread['starter']: bab_getUserId( $this->arrthread['author']); 
					if( $idauthor )
						{
						$res = $this->db->db_query("select email from ".BAB_USERS_TBL." where id='".$idauthor."'");
						if( $res && $this->db->db_num_rows($res) > 0 )
							{
							$rr = $this->db->db_fetch_array($res);
							$this->threadauthoremail = $rr['email'];
							}
						}
					}

				$req = "select count(*) as total from ".BAB_POSTS_TBL." where id_thread='".$this->arrthread['id']."' and confirmed='Y'";
				$res = $this->db->db_query($req);
				$row = $this->db->db_fetch_array($res);
				$this->replies = $row["total"] > 0 ? ($row["total"] -1): 0;
				if( $row["total"] == 0 && $this->moderator == false && ($this->bupdateauthor == 'N' || ($BAB_SESS_USERID && $BAB_SESS_USERID != $this->arrthread['starter']) )  )
					{
					$this->disabled = 1;
					}
				else
					{
					$this->disabled = 0;
					}


				$res = $this->db->db_query("select count(*) as total from ".BAB_POSTS_TBL." where id_thread='".$this->arrthread['id']."' and confirmed='N'");
				$ar = $this->db->db_fetch_array($res);
				if( $this->arrthread['active'] != "N" && $ar['total'] > 0)
					$this->status = "*";
				else
					$this->status = "";

				$this->gotothreadpages = array();
				if( ($row['total'] ) > $this->maxrows)
					{
					$total_pages = ceil( ( $row['total'] ) / $this->maxrows );
					$times = 1;
					for($j = 0; $j < $row['total']; $j += $this->maxrows)
						{

							$this->gotothreadurl = $GLOBALS['babUrlScript']."?tg=posts&idx=".$this->idx."&flat=".$this->flat."&forum=".$this->forum."&thread=".$this->arrthread['id']."&pos=";

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
				
				

				$res = $this->db->db_query("select id, date, id_author, author from ".BAB_POSTS_TBL." where id='".($this->arrthread['lastpost'] != 0? $this->arrthread['lastpost']:$this->arrthread['post'])."'");
				$ar = $this->db->db_fetch_array($res);
				$this->lastpostdate = bab_shortDate(bab_mktime($ar['date']), true);

				$this->lastpostauthordetailsurl = '';
				if( $ar['id_author'] != 0 && $this->bdisplayauhtordetails == 'Y')
					{
					if( bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $this->iddir))
						{
						$this->lastpostauthordetailsurl = $GLOBALS['babUrlScript']."?tg=directory&idx=ddbovml&directoryid=".$this->iddir."&userid=".$ar['id_author'];	
						}
					}


				$this->lastpostauthoremail = '';
				if( $this->bdisplayemailaddress == 'Y' )
					{
					$idauthor = $ar['id_author'] != 0? $ar['id_author']: bab_getUserId( $ar['author']); 
					if( $idauthor )
						{
						$res = $this->db->db_query("select email from ".BAB_USERS_TBL." where id='".$idauthor."'");
						if( $res && $this->db->db_num_rows($res) > 0 )
							{
							$rr = $this->db->db_fetch_array($res);
							$this->lastpostauthoremail = $rr['email'];
							}
						}
					}

				$this->lastpostauthor = $ar['author'];
				$this->lastposturl = $GLOBALS['babUrlScript']."?tg=posts&flat=".$this->flat."&forum=".$this->forum."&thread=".$this->arrthread['id']."&pos=".$postpos."#p".$ar['id'];

				$this->brecent = false;
				if( mktime() - bab_mktime($ar['date']) <= DELTA_TIME )
					$this->brecent = true;
				else if($GLOBALS['BAB_SESS_LOGGED'])
					{
					if( $ar['date'] >= $babBody->lastlog )
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
				$this->page = $this->gotothreadpages[$i][0];
				$this->bpageurl = $this->gotothreadpages[$i][2];
				$this->pageurl = $this->gotothreadurl.$this->gotothreadpages[$i][1];
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
				$this->page = $this->gotopages[$i]['page'];
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
		function getnextforum()
			{
			static $i = 0;
			if( list($key, $val) = each($this->forums))
				{
				$this->forumid = $key;
				$this->forumname = $val['name'];
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
	$babBody->babecho(	bab_printTemplate($temp,"threads.html", "threadlist"));
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
			global $BAB_SESS_USER;
			$this->subject = bab_translate("Subject");
			$this->name = bab_translate("Your Name");
			$this->notifyme = bab_translate("Notify me whenever someone replies ( only valid for registered users )");
			$this->message = bab_translate("Message");
			$this->add = bab_translate("New thread");
			$this->post = bab_translate("Post");
			$this->t_files = bab_translate("Dependent files");
			$this->t_add_field = bab_translate("Add field");
			$this->t_remove_field = bab_translate("Remove field");
			$this->forum = $forum;
			$this->flat = bab_rp('flat', 1);;

			if( !isset($_POST['subject']))
				{
				$this->subjectval = '';
				}
			else
				{
				$this->subjectval = htmlentities($_POST['subject']);
				}
			if( empty($BAB_SESS_USER))
				$this->anonyme = 1;
			else
				{
				$this->anonyme = 0;
				$this->username = $BAB_SESS_USER;
				}
			$message = isset($_POST['message']) ? $_POST['message'] : '';
			$this->editor = bab_editor($message, 'message', 'threadcr');
			$this->allow_post_files = bab_isAccessValid(BAB_FORUMSFILES_GROUPS_TBL,$forum);

			if( bab_isForumModerated($forum))
				$this->noteforum = bab_translate("Note: Posts are moderate and consequently your post will not be visible immediately");
			else
				$this->noteforum = "";
			}
		}

	$temp = new temp($forum);
	$babBody->babecho(	bab_printTemplate($temp,"threads.html", "threadcreate"));
	}

function saveThread($forum, $name, $subject, $message, $notifyme)
	{
	global $BAB_SESS_USER, $BAB_SESS_USERID, $babBody;

	if( empty($message))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a content for your message")." !";
		return false;
		}

	if( empty($subject))
		{
		$babBody->msgerror = bab_translate("ERROR: You must provide a subject for your message")." !";
		return false;
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

	if( $notifyme == "Y")
		$notifyme = "Y";
	else
		$notifyme = "N";

	$db = $GLOBALS['babDB'];
	$req = "insert into ".BAB_THREADS_TBL." (forum, date, notify, starter) values ";
	$req .= "('" .$forum. "', now(), '" . $notifyme. "', '". $idstarter. "')";
	$res = $db->db_query($req);
	$idthread = $db->db_insert_id();

	$arr = $db->db_fetch_array($db->db_query("select * from ".BAB_FORUMS_TBL." where id='".$forum."'"));

	if( $arr['moderation'] == "Y" )
		{
		$confirmed = "N";
		}
	else
		{
		$confirmed = "Y";
		}

	bab_editor_record($message);

	$req = "insert into ".BAB_POSTS_TBL." (id_thread, date, subject, message, id_author, author, confirmed, date_confirmed) values ";
	$req .= "('" .$db->db_escape_string($idthread). "', now(), '";
	$req .= $db->db_escape_string($subject). "', '" . $db->db_escape_string($message). "', '" . $db->db_escape_string($idstarter). "', '". $db->db_escape_string($name);


	$req .= "', '". $db->db_escape_string($confirmed). "', now())";
	$res = $db->db_query($req);
	$idpost = $db->db_insert_id();

	if (bab_isAccessValid(BAB_FORUMSFILES_GROUPS_TBL,$forum))
		bab_uploadPostFiles($idpost, $forum);
	
	$req = "update ".BAB_THREADS_TBL." set 
		lastpost='".$db->db_escape_string($idpost)."', 
		post='".$db->db_escape_string($idpost)."' 
		where id = '".$db->db_escape_string($idthread)."'";
	$res = $db->db_query($req);

	$tables = array();
	if( $confirmed == "Y"  )
		{
		$tables[] = BAB_FORUMSNOTIFY_GROUPS_TBL;
		}

	if( $arr['notification'] == "Y" )
		{
		$tables[] = BAB_FORUMSMAN_GROUPS_TBL;
		}

	if( count($tables) > 0 )
		{
		$url = $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$forum."&thread=".$idthread."&flat=".$arr['bflatview']."&views=1";
		notifyForumGroups($forum, stripslashes($subject), stripslashes($name), $arr['name'], $tables, $url);
		}

	Header("Location: ". $GLOBALS['babUrlScript']."?tg=threads&forum=".$forum);
	exit;
	}

function getClosedThreads($forum)
	{
	$db = $GLOBALS['babDB'];
	$req = "select count(*) as total from ".BAB_THREADS_TBL." where forum='$forum' and active='N'";
	$res = $db->db_query($req);
	$arr = $db->db_fetch_array($res);
	return $arr['total'];
	}

/* main */
if(!isset($idx))
	{
	$idx = "List";
	}

if( !isset($pos))
	$pos = 0;

if( isset($add) && $add == "addthread" && bab_isAccessValid(BAB_FORUMSPOST_GROUPS_TBL, $forum))
	{
	if (!isset($uname)) $uname = '';
	if (!isset($notifyme)) $notifyme = '';
	if (!saveThread($forum, $uname, $subject, $message, $notifyme))
		{
		$idx = "newthread";
		}
	}

$babLevelTwo = bab_getForumName($forum);

switch($idx)
	{
	case "newthread":
		if( bab_isAccessValid(BAB_FORUMSPOST_GROUPS_TBL, $forum))
			{
			$babBody->title = bab_getForumName($forum);
			newThread($forum);
			$babBody->addItemMenu("List", bab_translate("Threads"), $GLOBALS['babUrlScript']."?tg=threads&idx=List&forum=".$forum."&flat=".bab_rp('flat', 1));
			$babBody->addItemMenu("newthread", bab_translate("New thread"), $GLOBALS['babUrlScript']."?tg=threads&idx=newthread&forum=".$forum);

			}		
		break;

	case "ListC":
		$babBody->title = bab_getForumName($forum);
		if( bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $forum))
			{
			$babBody->addItemMenu("List", bab_translate("Threads"), $GLOBALS['babUrlScript']."?tg=threads&idx=List&forum=".$forum);
			$count = listThreads($forum, "N", $pos);
			if( $count > 0)
				$babBody->addItemMenu("ListC", bab_translate("Closed"), $GLOBALS['babUrlScript']."?tg=threads&idx=ListC&forum=".$forum);

			if( bab_isAccessValid(BAB_FORUMSPOST_GROUPS_TBL, $forum))
				{
				$babBody->addItemMenu("newthread", bab_translate("New thread"), $GLOBALS['babUrlScript']."?tg=threads&idx=newthread&forum=".$forum);
				}
			}
		break;

	default:
	case "List":
		$babBody->title = bab_getForumName($forum);
		if( bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $forum))
			{
			$GLOBALS['babWebStat']->addForum($forum);
			$count = listThreads($forum, "Y", $pos);
			$babBody->addItemMenu("List", bab_translate("Threads"), $GLOBALS['babUrlScript']."?tg=threads&idx=List&forum=".$forum);
			if( getClosedThreads($forum) > 0)
				$babBody->addItemMenu("ListC", bab_translate("Closed"), $GLOBALS['babUrlScript']."?tg=threads&idx=ListC&forum=".$forum);

			if( bab_isAccessValid(BAB_FORUMSPOST_GROUPS_TBL, $forum))
				{
				$babBody->addItemMenu("newthread", bab_translate("New thread"), $GLOBALS['babUrlScript']."?tg=threads&idx=newthread&forum=".$forum);
				}
			}
		break;
	}
$babBody->setCurrentItemMenu($idx);

?>
