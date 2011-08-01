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
require_once dirname(__FILE__).'/utilit/registerglobals.php';
include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";

function editorfunctions()
	{
	class temp
		{
		var $functions;
		
		function temp()
			{
			$event = new bab_eventEditorFunctions();
			$event->uid = bab_rp('uid');
			bab_fireEvent($event);
			$this->functions = $event->getFunctions();
		}

		function getnext()
			{
			if (list(,$arr) = each($this->functions)) {
				$this->name = bab_toHtml($arr['name']);
				$this->description = bab_toHtml($arr['description']);
				$this->url = bab_toHtml($arr['url']);
				$this->iconpath = bab_toHtml($arr['iconpath']);
				return true;
			}
			
			return false;
			
		}
	}
	
	global $babBody;
	
	$babBody->addStyleSheet('text_toolbar.css');

	$temp = new temp();
	$babBody->babPopup(bab_printTemplate($temp, 'editorfunctions.html'));

}


editorfunctions();
?>