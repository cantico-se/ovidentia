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


/**
 * Interface for notification based events
 * @package events
 * @since 7.4.0
 */
interface bab_eventNotifyRecipients
{
	/**
	 * Add a user to informed user list after a user has been notified
	 * @param int $id_user
	 */
	public function addInformedUser($id_user);
	
	
	
	/**
	 * Get user to notify based on preferences and access rights
	 * the method will return a list of recipients to notify without the allready informed users
	 * 
	 * @return array :
	 * array
	 *	(
	 *	[154] =>
	 *	    (
	 *	        [name] => Guillaume Dupont
	 *	        [firstname] => Guillaume
	 *	        [lastname] => Dupont
	 *	        [email] => test@exemple.com
	 *		)
	 *	)
	 *
	 */
	public function getUsersToNotify();
}
