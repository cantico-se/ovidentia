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
 * Ovidentia calendar
 */
class bab_EventCalendar
{
	/**
	 * Name of calendar
	 * @var string
	 */
	protected $name;
	
	/**
	 * Description of calendar
	 * @var string
	 */
	protected $description;
	
	
	/**
	 * Displayable type for calendar (internationalized)
	 * @var string
	 */
	protected $type;
	
	
	/**
	 * Optional id user linked to calendar
	 * @var int
	 */
	protected $id_user;
	
	
	/**
	 * get name of calendar
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}
	
	
	/**
	 * get description of calendar
	 * @return string
	 */
	public function getDescription()
	{
		return $this->description;
	}
	
	
	/**
	 * get description of calendar
	 * @return string
	 */
	public function getType()
	{
		return $this->description;
	}
	
	/**
	 * Get Id user linked to calendar, only for personal calendars
	 * calendars with id_user, will can load additional periods collection with the working periods, non-working periods and vacation periods
	 * 
	 * @return int
	 */
	public function getIdUser()
	{
		return $id_user;
	}
}