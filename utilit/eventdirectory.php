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

/**
 * All direcory based events are extended for this event
 * @package events
 * @since 6.1.1
 */
class bab_eventDirectory extends bab_event {
	
	var $id_entry;
	var $id_user;
	
	function getDirEntry() {

		if (isset($this->id_entry)) {
			$id = $this->id_entry;
			$type = BAB_DIR_ENTRY_ID;
		} else {
			$id = $this->id_user;
			$type = BAB_DIR_ENTRY_ID_USER;
		}
		
		return getDirEntry($id, $type, NULL, false);
	}
}

/**
 * a directory entry has been created
 * @package events
 * @since 6.1.1
 */
class bab_eventDirectoryEntryCreated extends bab_eventDirectory {

	function bab_eventDirectoryEntryCreated($id_entry) {
		$this->id_entry = $id_entry;
	}
}

/**
 * a directory entry has been modified
 * @package events
 * @since 6.1.1
 */
class bab_eventDirectoryEntryModified extends bab_eventDirectory {

	function bab_eventDirectoryEntryModified($id_entry) {
		$this->id_entry = $id_entry;
	}
}

/**
 * a directory entry has been deleted
 * @package events
 * @since 6.1.1
 */
class bab_eventDirectoryEntryDeleted extends bab_eventDirectory {

	function bab_eventDirectoryEntryDeleted($id_entry) {
		$this->id_entry = $id_entry;
	}
}


/**
 * a user has been created
 * @package events
 * @since 6.1.1
 */
class bab_eventUserCreated extends bab_eventDirectory {

	function bab_eventUserCreated($id_user) {
		$this->id_user = $id_user;
	}
}

/**
 * a user has been modified
 * @package events
 * @since 6.1.1
 */
class bab_eventUserModified extends bab_eventDirectory {
	
	function bab_eventUserModified($id_user) {
		$this->id_user = $id_user;
	}
}

/**
 * a user has been deleted
 * @package events
 * @since 6.1.1
 */
class bab_eventUserDeleted extends bab_eventDirectory {

	function bab_eventUserDeleted($id_user) {
		$this->id_user = $id_user;
	}
}

/**
 * a user has been attached to a group
 * @package events
 * @since 6.1.1
 */
class bab_eventUserAttachedToGroup extends bab_eventDirectory {

	/**
	 * @public
	 */
	var $id_group;

	function bab_eventUserAttachedToGroup($id_user, $id_group) {
		$this->id_user = $id_user;
		$this->id_group = $id_group;
	}
}


/**
 * a user has been detached from a group
 * @package events
 * @since 6.1.1
 */
class bab_eventUserDetachedFromGroup extends bab_eventDirectory {

	/**
	 * @public
	 */
	var $id_group;

	function bab_eventUserDetachedFromGroup($id_user, $id_group) {
		$this->id_user = $id_user;
		$this->id_group = $id_group;
	}
}


?>