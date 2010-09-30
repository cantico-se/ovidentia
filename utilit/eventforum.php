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
include_once 'base.php';
require_once $GLOBALS['babInstallPath'].'utilit/eventincl.php';
require_once $GLOBALS['babInstallPath'].'utilit/eventnotifyincl.php';




/**
 * Event for actions on forum posts
 * Store additional informations for registered targets
 * each target can add informed users, the next targets will not inform the allready informed recipients
 * 
 * @package events
 * @since 7.4.0
 */
class bab_eventForumPost extends bab_event implements bab_eventNotifyRecipients
{
	private $informed_recipients = array();
	
	
	/**
	 * @var int
	 */
	protected $forum_id = null;
	
	/**
	 * @var string
	 */
	protected $forum_name = null;
	
	
	/**
	 * Option set on forum to unable/disable notification of managers (moderators)
	 * @var bool
	 */
	protected $forum_notify = false;
	
	/**
	 * @var int
	 */
	protected $thread_id = null;
	
	/**
	 * @var string
	 */
	protected $thread_title = null;
	
	/**
	 * Used to notify thread author
	 * @var int
	 */
	protected $thread_author = null;
	
	
	/**
	 * Option set on thread to unable/disable notification of thread author
	 * @var bool
	 */
	protected $thread_notify = false;
	
	
	
	/**
	 * 
	 * @var int
	 */
	protected $post_id = null;
	
	/**
	 * author name
	 * @var string
	 */
	protected $post_author = null;

	/**
	 * Set forum details
	 * 
	 * @param	int		$id
	 * @param	string	$name		forum name
	 * @param	bool	$notify		notification of moderators
	 * 
	 * @return bab_eventForumPost
	 */
	public function setForum($id, $name, $notify)
	{
		$this->forum_id = $id;
		$this->forum_name = $name;
		$this->forum_notify = $notify;
		return $this;
	}
	
	/**
	 * Set thread informations
	 * @param 	int 		$id
	 * @param 	string 		$title
	 * @param	int			$author		id user of thread author to notify
	 * @param 	bool 		$notify		notification of thread author
	 * @return bab_eventForumPost
	 */
	public function setThread($id, $title, $author, $notify)
	{
		$this->thread_id = $id;
		$this->thread_title = $title;
		$this->thread_author = $author;
		$this->thread_notify = $notify;
		return $this;
	}
	
	
	/**
	 * get thread author to notify or null if the thread author do not need a notification
	 * @return Array
	 */
	public function getThreadAuthor()
	{
		if (!$this->thread_notify || !$this->thread_author)
		{
			return null;
		}
		
		include_once dirname(__FILE__).'/userinfosincl.php';
		$row = bab_userInfos::getRow($this->thread_author);
		
		return array(
				'name' => bab_composeUserName($row['firstname'], $row['lastname']),
				'firstname' => $row['firstname'],
				'lastname' => $row['lastname'],
				'email' => $row['email']
			);
	}
	
	/**
	 * Set post informations
	 * @param int		$id			id of post to notify
	 * @param string 	$author		author name
	 * @return unknown_type
	 */
	public function setPost($id, $author)
	{
		$this->post_author = $author;
	}
	
	
	/**
	 * Add a user to informed user list after a user has been informed about the action
	 * @param int $id_user
	 * @return bab_eventForumPost
	 */
	public function addInformedUser($id_user)
	{
		$this->informed_recipients[$id_user] = $id_user;
		return $this;
	}
	
	
	/**
	 * 
	 * @return array
	 */
	protected function getRecipients()
	{
		return aclGetAccessUsers(BAB_FORUMSNOTIFY_GROUPS_TBL, $this->forum_id);
	}
	
	
	
	/**
	 * Get user to notify based on folder preferences and access rights
	 * @return array
	 */
	public function getUsersToNotify()
	{
		include_once $GLOBALS['babInstallPath']."admin/acl.php";
		
		$users = $this->getRecipients();
		
		// remove allready notified users
		if (0 < count($this->informed_recipients)) {
			foreach($users as $id_user => $arr) {
				if (isset($this->informed_recipients[$id_user])) {
					unset($users[$id_user]);
				}
			}
		}
		
		return $users;
	}
}


/**
 * After one forum post has been made visible for a population of users
 * The event must be triggered after approbation, when the forum post is visible
 * 
 * @package events
 * @since 7.4.0
 */
class bab_eventForumAfterPostAdd extends bab_eventForumPost 
{
	/**
	 * if necessary, notify also the thread author
	 * @return array
	 */
	protected function getRecipients()
	{
		$users = parent::getRecipients();
		
		if ($this->thread_notify && $this->thread_author)
		{
			$users[$this->thread_author] = $this->getThreadAuthor();
		}
		
		return $users;
	}
}


/**
 * After one forum thread has been made visible for a population of users
 * The event must be triggered after approbation, when the forum post is visible
 * 
 * @package events
 * @since 7.4.0
 */
class bab_eventForumAfterThreadAdd extends bab_eventForumAfterPostAdd 
{
	
}








/**
 * 
 * bab_eventForumAfterPostAdd	notifyForumGroups		utilit/forumincl.php	670			// post or thread confirmed
 * 														posts.php				1151		// new post
 * bab_eventForumAfterThreadAdd	notifyForumGroups		threads.php				555			// new thread
 * 
 * @param bab_eventForumPost $event
 * @return unknown_type
 */
function bab_onForumPost(bab_eventForumPost $event)
{
	
}
