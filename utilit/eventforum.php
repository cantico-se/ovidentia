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
 * each target can add informed users, the next targets will not inform the already informed recipients
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
	 *
	 * @var array
	 */
	protected $forum = null;

	/**
	 * @var int
	 */
	protected $thread_id = null;

	/**
	 * @var string
	 */
	protected $thread_title = null;


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
	 *
	 * @var bool
	 */
	protected $post_confirmed = null;

	/**
	 * Set forum details
	 *
	 * @param	int		$id
	 *
	 * @return bab_eventForumPost
	 */
	public function setForum($id)
	{
		$this->forum_id = $id;
		return $this;
	}

	public function getForumId()
	{
		return $this->forum_id;
	}


	public function getForumInfos()
	{
		if (null === $this->forum)
		{
			global $babDB;
			$this->forum = $babDB->db_fetch_assoc($babDB->db_query("select * from ".BAB_FORUMS_TBL." where id='".$babDB->db_escape_string($this->forum_id)."'"));
		}

		return $this->forum;
	}




	/**
	 * Set thread informations
	 * @param 	int 		$id
	 * @param 	string 		$title
	 * @param 	bool 		$notify		notification of thread author
	 * @return bab_eventForumPost
	 */
	public function setThread($id, $title)
	{
		$this->thread_id = $id;
		$this->thread_title = $title;
		return $this;
	}

	/**
	 *
	 * @return int
	 */
	public function getThreadId()
	{
		return $this->thread_id;
	}

	/**
	 * @return string
	 */
	public function getThreadTitle()
	{
		return $this->thread_title;
	}




	/**
	 * Set post informations
	 * @param int		$id			id of post to notify
	 * @param string 	$author		author name
	 * @return unknown_type
	 */
	public function setPost($id, $author, $confirmed)
	{
		$this->post_id = $id;
		$this->post_author = $author;
		$this->post_confirmed = $confirmed;
	}

	/**
	 *
	 * @return int
	 */
	public function getPostId()
	{
		return $this->post_id;
	}

	/**
	 *
	 * @return string
	 */
	public function getPostAuthor()
	{
		return $this->post_author;
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
	 * If the post require an approval, notify the manager group
	 * or else notify the recipients (notify group)
	 * @return array
	 */
	protected function getRecipients()
	{
		$forum = $this->getForumInfos();

		if ('Y' === $forum['moderation'] && !$this->post_confirmed)
		{
			if ($forum['notification'])
			{
				// notify moderators
				return aclGetAccessUsers(BAB_FORUMSMAN_GROUPS_TBL, $this->forum_id);
			}

			return array();
		}

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

		// remove already notified users
		if (0 < count($this->informed_recipients)) {
			foreach($users as $id_user => $arr) {
				if (isset($this->informed_recipients[$id_user])) {
					unset($users[$id_user]);
				}
			}
		}

		return $users;
	}


	/**
	 * @param	array $types	list of types to get in saved options
	 * 							from :
	 * 							BAB_FORUMNOTIF_NONE
	 * 							BAB_FORUMNOTIF_ALL
	 * 							BAB_FORUMNOTIF_NEWTHREADS
	 * @return unknown_type
	 */
	protected function getFromUserOptions(Array $types)
	{
		global $babDB;

		$users = array();

		$res = $babDB->db_query('
			SELECT
				u.id,
				u.lastname,
				u.firstname,
				u.email
			FROM
				'.BAB_FORUMSNOTIFY_USERS_TBL.' n,
				'.BAB_USERS_TBL.' u
			WHERE
				u.id = n.id_user
				AND u.disabled = 0
				AND n.id_forum='.$babDB->quote($this->forum_id).'
				AND n.forum_notification IN('.$babDB->quote($types).')

		');

		while ($arr = $babDB->db_fetch_assoc($res))
		{
			$users[$arr['id']] = array(
				'name' 		=> bab_composeUserName($arr['firstname'], $arr['lastname']),
				'firstname' => $arr['firstname'],
				'lastname'	=> $arr['lastname'],
				'email' 	=> $arr['email']
			);
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

		// add user to notify for BAB_FORUMNOTIF_ALL
		
		if($this->post_confirmed){

			$users += $this->getFromUserOptions(array(BAB_FORUMNOTIF_ALL));
			
			$users = array_diff_key($users, $this->getFromUserOptions(array(BAB_FORUMNOTIF_NONE, BAB_FORUMNOTIF_NEWTHREADS)));
			
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
class bab_eventForumAfterThreadAdd extends bab_eventForumPost
{
	/**
	 * if necessary, notify also the thread author
	 * @return array
	 */
	protected function getRecipients()
	{
		$users = parent::getRecipients();

		// add user to notify for BAB_FORUMNOTIF_ALL & BAB_FORUMNOTIF_NEWTHREADS
		
		if($this->post_confirmed){

			$users += $this->getFromUserOptions(array(BAB_FORUMNOTIF_ALL, BAB_FORUMNOTIF_NEWTHREADS));
			
			$users = array_diff_key($users, $this->getFromUserOptions(array(BAB_FORUMNOTIF_NONE)));
			
		}

		return $users;
	}
}








/**
 *
 *
 * @param bab_eventForumPost $event
 * @return unknown_type
 */
function bab_onForumPost(bab_eventForumPost $event)
{
	require_once dirname(__FILE__).'/forumincl.php';
	notifyForumGroups($event);
}
