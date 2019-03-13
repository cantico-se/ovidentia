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
include_once "base.php";


/*
Used by add-ons to let user select a color
*/

function select_color()
	{
	class temp
		{
		function temp()
			{
			$this->title = bab_translate("Color");
			$this->callback = isset($_GET['callback']) ? bab_toHtml($_GET['callback'], BAB_HTML_ENTITIES | BAB_HTML_JS) : false;
			$this->param = isset($_GET['param']) ? bab_toHtml($_GET['param'], BAB_HTML_ENTITIES | BAB_HTML_JS) : false;

			}
		}
	$temp = new temp();
	die(bab_printTemplate($temp, "selectcolor.html", "selectcolor"));
	}

/* main */

$idx = isset($_GET['idx']) ? $_GET['idx'] : 'popup';
switch ($idx)
	{
	default:
	case 'popup':
		select_color();
	}
