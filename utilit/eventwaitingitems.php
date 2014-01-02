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

include 'base.php';
include_once $GLOBALS['babInstallPath'].'utilit/eventincl.php';


/**
 * Event fired when the approbation page is displayed
 * 
 * @since 6.1.1
 * @package events
 */
class bab_eventBeforeWaitingItemsDisplayed extends bab_event
{
	/**
	 * @see self::addObject
	 * @var array
	 */
	public $objects = array();
	


	/**
	 * Add object to the waiting items page
	 * 
	 * @param string 	$title		Title for the list of items
	 * @param Array 	$arr		the list of items waiting for approval,
	 * 								Format for each item :
	 * 									text 		: plain text on one line
	 * 									description : HTML content
	 * 									url			: url to the approval form
	 * 									popup		: boolean (url opening method)
	 * 									idschi		: int
	 * 								
	 */
	public function addObject($title, Array $arr) {
		static $i = 0;
		$key = mb_strtolower(mb_substr($title,0,3));
		$this->objects[$key.$i] = array(
			'title' => $title,
			'arr'	=> $arr
		);

		$i++;
	}

}



/**
 * Set Tests if there is waiting items or not
 * @since 8.0.99
 */
class bab_eventWaitingItemsStatus extends bab_eventBeforeWaitingItemsDisplayed
{
	/**
	 * Property set after the bab_fireEvent if there are item to approve
	 * @var bool
	 */
	public $status = false;
	
	
	
	public function addObject($title, Array $arr) {
	
		if (count($arr) > 0)
		{
			$this->addStatus(true);
		}
		
		return parent::addObject($title, $arr);
	}
	
	
	/**
	 * Use this method in the callback function
	 *
	 * @param bool $status true : there are waiting items | false : no waiting items
	 */
	public function addStatus($status)
	{
		if ($status)
		{
			$this->status = true;
			$this->stop_propagation = true;
		}
	
		return $this;
	}
}



/**
 * To require the number of waiting items
 * @since 8.0.99
 */
class bab_eventWaitingItemsCount extends bab_eventBeforeWaitingItemsDisplayed
{
	/**
	 * Item count for each title
	 * @var array
	 */
	public $itemcount = array();
	
	
	/**
	 * Use in callback function, get only objects with count > 0
	 * @param string $title
	 * @param int $count
	 */
	public function addItemCount($title, $count)
	{
		$this->itemcount[] = array(
				'title' => $title,
				'count' => $count
		);
	}
	
	
	public function addObject($title, Array $arr) {
		
		$this->addItemCount($title, count($arr));
		return parent::addObject($title, $arr);
	}
	
	
	
	/**
	 * Get number of waiting items
	 * @return int
	 */
	public function getTotalCount()
	{
		$total = 0;
		foreach($this->itemcount as $arr)
		{
			$total += $arr['count'];
		}
	
		bab_debug($this->itemcount);
		return $total;
	}
}


