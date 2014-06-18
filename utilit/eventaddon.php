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
require_once $GLOBALS ['babInstallPath'] . 'utilit/eventincl.php';
require_once $GLOBALS ['babInstallPath'] . 'utilit/dirincl.php';

/**
 * The base event for all addon management related events.
 * 
 * @package events
 * @since 8.2.0
 */
class bab_eventAddon extends bab_event {

	/**
	 * @var string	The version of the addon before the upgrade.
	 */
	public $addonName;
	
	/**
	 * @param string $addonName
	 */
	public function __construct($addonName)
	{
		$this->addonName = $addonName;
	}
}



/**
 * An addon has been installed.
 *
 * @package events
 * @since 8.2.0
 */
class bab_eventAddonInstalled extends bab_eventAddon {
}



/**
 * An addon has been upgraded.
 * 
 * @package events
 * @since 8.2.0
 */
class bab_eventAddonUpgraded extends bab_eventAddon {

	/**
	 * @var string	The version of the addon before the upgrade.
	 */
	public $previousVersion;
	
	/**
	 * @var string	The version of the addon after the upgrade.
	 */
	public $newVersion;
}



/**
 * An addon is going to be deleted.
 *
 * @package events
 * @since 8.2.0
 */
class bab_eventAddonBeforeDeleted extends bab_eventAddon {
}



/**
 * An addon has been deleted.
 *
 * @package events
 * @since 8.2.0
 */
class bab_eventAddonDeleted extends bab_eventAddon {
}



/**
 * An addon has been disabled.
 *
 * @package events
 * @since 8.2.0
 */
class bab_eventAddonDisabled extends bab_eventAddon {
}



/**
 * An addon has been enabled.
 *
 * @package events
 * @since 8.2.0
 */
class bab_eventAddonEnabled extends bab_eventAddon {
}

