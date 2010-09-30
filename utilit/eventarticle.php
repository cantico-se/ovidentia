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
 * Event for actions on articles
 * Store additional informations for registered targets
 * each target can add informed users, the next targets will not inform the allready informed recipients
 * 
 * @package events
 * @since 7.4.0
 */
class bab_eventArticle extends bab_event implements bab_eventNotifyRecipients
{
	private $informed_recipients = array();
	
	
	/**
	 * @var int
	 */
	private	  $topic_id 		= null;
	protected $topic_name 		= null;
	
	protected $article_id 		= null;
	protected $article_title 	= null;
	protected $article_author 	= null;
	
	/**
	 * 
	 * @var string
	 */
	private $restriction;
	
	

	/**
	 * Set informations usefull for notifications
	 * 
	 * @param	int		$topic_id
	 * @param	string	$topic_name
	 * 
	 * @param	int		$article_id
	 * @param	string	$article_title
	 * @param	string	$article_author
	 * 
	 * @return bab_eventArticle
	 */
	public function setInformations($topic_id, $topic_name, $article_id, $article_title, $article_author)
	{
		$this->topic_id 		= $topic_id;
		$this->topic_name 		= $topic_name;
		$this->article_id 		= $article_id;
		$this->article_title 	= $article_title;
		$this->article_author 	= $article_author;
		
		return $this;
	}
	
	
	public function getTopicId()
	{
		return $this->topic_id;
	}
	
	/**
	 * 
	 * @return string
	 */
	public function getTopicName()
	{
		return $this->topic_name;
	}
	
	public function getArticleId()
	{
		return $this->article_id;
	}
	
	
	public function getArticleTitle()
	{
		return $this->article_title;
	}
	
	public function getArticleAuthor()
	{
		return $this->article_author;
	}
	
	
	
	/**
	 * Set access restriction of article to use for the recipients of the articles
	 * @param string $restriction
	 * @return bab_eventArticle
	 */
	public function setRestriction($restriction)
	{
		if ('' === $restriction)
		{
			return $this;
		}
		
		$this->restriction = $restriction;
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
	 * Get user to notify based on article restrictions and access rights
	 * @return array
	 */
	public function getUsersToNotify()
	{
		include_once $GLOBALS['babInstallPath']."admin/acl.php";
		
		$users = aclGetAccessUsers(BAB_TOPICSVIEW_GROUPS_TBL, $this->topic_id);
		
		// remove allready notified users
		if (0 < count($this->informed_recipients)) {
			foreach($users as $id_user => $arr) {
				
				if( null !== $this->restriction && !bab_articleAccessByRestriction($restriction, $id)){
					unset($users[$id_user]);
					continue;
				}
				
				if (isset($this->informed_recipients[$id_user])) {
					unset($users[$id_user]);
				}
				
			}
		}
		
		return $users;
	}
}


/**
 * After one article has been added to a topic and is made visible for a population of users
 * The event must be triggered after approbation, when the article is visible
 * 
 * @package events
 * @since 7.4.0
 */
class bab_eventAfterArticleAdd extends bab_eventArticle 
{
	
}





/**
 * Default notifications for articles
 * 
 * 
 * @param bab_eventArticle $event
 * @return unknown_type
 */
function bab_onArticle(bab_eventArticle $event)
{
	require_once dirname(__FILE__).'/artincl.php';
	
	notifyArticleGroupMembers($event, bab_translate("An article has been published"));
}