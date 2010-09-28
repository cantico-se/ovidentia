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
require_once $GLOBALS['babInstallPath'].'utilit/dirincl.php';
require_once $GLOBALS['babInstallPath'].'utilit/eventnotifyincl.php';

/**
 * All file manager based events are extended for this event
 * 
 * @package events
 * @since 7.2.93
 */
class bab_eventFm extends bab_event 
{
	/**
	 * Ovidentia references in relation with event
	 * @var array	<bab_reference>
	 */
	private $references = array();
	
	/**
	 * @var int
	 */
	protected $folder_id = null;
	
	
	
	
	/**
	 * 
	 * @param bab_reference $reference
	 * @return bab_eventFm
	 */
	public function addReference(bab_reference $reference) {
		
		$this->references[] = $reference;
		return $this;
	}
	
	/**
	 * Get iterator of ovidentia references
	 * @return array	<bab_reference>
	 */
	public function getReferences()
	{
		return $this->references;
	}
	
	/**
	 * Set current folder
	 * @return bab_eventFm
	 */
	public function setFolderId($id)
	{
		$this->folder_id = $id;
		return $this;
	}
}

/**
 * All file from file manager based events 
 * Store additional informations for registered targets
 * each target can add informed users, the next targets will not inform the allready informed recipients
 * 
 * @package events
 * @since 7.2.93
 */
class bab_eventFmFile extends bab_eventFm implements bab_eventNotifyRecipients
{
	private $informed_recipients = array();
	
	
	private $user_option_notify = null;
	
	
	/**
	 * Add a user to informed user list after a user has been informed about the action done on the file manager file
	 * @param int $id_user
	 * @return bab_eventFmFile
	 */
	public function addInformedUser($id_user)
	{
		$this->informed_recipients[$id_user] = $id_user;
		return $this;
	}
	
	/**
	 * Test if a user has been informed about the action done one file manager file
	 * @param int	$id_user
	 * @return bool
	 */
	public function isUserInformed($id_user)
	{
		return isset($this->informed_recipients[$id_user]);
	}
	
	
	/**
	 * Folder option for notifications.
	 * If the method return true, the recipients of files must be informed about a newly uploaded or updated file
	 * if not set by the setFolderOptionNotify method, this method return false
	 * 
	 * @see self::setFolderOptionNotify()
	 * 
	 * @return bool
	 */
	public function getFolderOptionNotify()
	{
		global $babDB;
		$res = $babDB->db_query('SELECT filenotify FROM '.BAB_FM_FOLDERS_TBL.' WHERE id='.$babDB->quote($this->folder_id));
		if ($arr = $babDB->db_fetch_assoc($res)) {
			return 'Y' === $arr['filenotify'];
		}
		
		return false;
	}
	
	
	/**
	 * User option for notifications
	 * return null if the option is not accessible to user
	 * return true if the user want to notify the recipients or false otherwise
	 * 
	 * @return bool | null
	 */
	public function getUserOptionNotify()
	{
		return $this->user_option_notify;	
	}
	
	/**
	 * Set user option for notifications
	 * @param bool $option
	 * @return bab_eventFmFile
	 */
	public function setUserOptionNotify($option = true)
	{
		$this->user_option_notify = $option;
		return $this;
	}
	
	
	/**
	 * Get user to notify based on folder preferences and access rights
	 * @return array
	 */
	public function getUsersToNotify()
	{
		if (false === $this->getFolderOptionNotify()) {
			return array();
		}
		
		if (false === $this->getUserOptionNotify()) {
			return array();
		}
		
		include_once $GLOBALS['babInstallPath']."admin/acl.php";
		
		$users = aclGetAccessUsers(BAB_FMNOTIFY_GROUPS_TBL, $this->folder_id);
		
		// remove allready notified users
		if (0 < count($this->informed_recipients)) {
			foreach($users as $id_user => $arr) {
				if ($this->isUserInformed($id_user)) {
					unset($users[$id_user]);
				}
			}
		}
		
		return $users;
	}
}


/**
 * After one or more files uploaded into file manager
 * The event must be triggered after approbation, when the file is available to recipients
 * 
 * @package events
 * @since 7.2.93
 */
class bab_eventFmAfterFileUpload extends bab_eventFmFile 
{
	
}


/**
 * After one or more files updated into file manager
 * The event must be triggered after approbation, when the modifications are available to recipients
 * 
 * @package events
 * @since 7.2.93
 */
class bab_eventFmAfterFileUpdate extends bab_eventFmFile
{
	
}

/**
 * After a new version uploaded on an existing file in the file manager
 * The event must be triggered after approbation, when the file is available to recipients
 * 
 * @package events
 * @since 7.2.93
 */
class bab_eventFmAfterAddVersion extends bab_eventFmAfterFileUpdate
{
	
}