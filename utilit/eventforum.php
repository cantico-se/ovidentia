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
	 * Set current folder
	 * @return bab_eventArticle
	 */
	public function setForumId($id)
	{
		$this->forum_id = $id;
		return $this;
	}
	
	
	/**
	 * Add a user to informed user list after a user has been informed about the action
	 * @param int $id_user
	 * @return bab_eventFmFile
	 */
	public function addInformedUser($id_user)
	{
		$this->informed_recipients[$id_user] = $id_user;
		return $this;
	}
	
	
	
	/**
	 * Get user to notify based on folder preferences and access rights
	 * @return array
	 */
	public function getUsersToNotify()
	{
		include_once $GLOBALS['babInstallPath']."admin/acl.php";
		
		$users = aclGetAccessUsers(BAB_FORUMSNOTIFY_GROUPS_TBL, $this->forum_id);
		
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
