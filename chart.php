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

function OrgChartPage($ocid)
	{
	class temp
		{

		function temp($ocid)
			{
			$this->flturl = '';//$GLOBALS['babUrlScript']."?tg=fltchart&ocid=".$ocid;
			$this->flburl = '';//$GLOBALS['babUrlScript']."?tg=flbchart&ocid=".$ocid;
			$this->frurl = $GLOBALS['babUrlScript']."?tg=frchart&ocid=".$ocid;
			$this->frturl = $GLOBALS['babUrlScript']."?tg=frchart&idx=frt&ocid=".$ocid;
			}

		}
	$temp = new temp($ocid);
	die(bab_printTemplate($temp,"chart.html", "chartpage"));
	}

if (isset($idx))
	$idx = '';

/* main */
switch($idx)
	{
	default:
		OrgChartPage($ocid);
		exit;
		break;
	}
?>