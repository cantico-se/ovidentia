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


function OrgChartPage($ocid, $oeid, $iduser, $disp)
{
	class temp
	{

		function temp($ocid, $oeid, $iduser, $disp)
		{

			$this->sContent	= 'text/html; charset=' . bab_charset::getIso();
			
			$this->mode = 'view';
			if(bab_rp('mode', null) === 'edit')
			{
				$this->mode = 'edit';
			}

			$this->frurl = $GLOBALS['babUrlScript']."?tg=frchart&ocid=".$ocid."&oeid=".$oeid."&iduser=".$iduser."&disp=".$disp;
			$this->frturl = $GLOBALS['babUrlScript']."?tg=frchart&idx=frt&ocid=".$ocid."&oeid=".$oeid."&iduser=".$iduser."&disp=".$disp;
			$this->flturl = $GLOBALS['babUrlScript']."?tg=fltchart&ocid=".$ocid."&oeid=".$oeid."&iduser=".$iduser;

			$this->unlockurl = $GLOBALS['babUrlScript']."?tg=charts&idx=unlock&ocid=".$ocid;
		}

	}

	global $babBody;

	$temp = new temp($ocid, $oeid, $iduser, $disp);
	die(bab_printTemplate($temp,"chart.html", "chartpage"));
}


// main

$idx = bab_rp('idx');
$ocid = bab_rp('ocid');
$oeid = bab_rp('oeid', 0);
$iduser = bab_rp('iduser', 0);
$disp = bab_rp('disp', 'disp3');

if (empty($oeid)) {
	$oeinfo = $babDB->db_fetch_array(
		$babDB->db_query(
			"select oet.id
			from ".BAB_OC_ENTITIES_TBL." oet left join ".BAB_OC_TREES_TBL." ctt on ctt.id=oet.id_node
			where oet.id_oc='".$babDB->db_escape_string($ocid)."' and ctt.id_parent='0'"
		)
	);
	$oeid = $oeinfo['id'];
}

/* main */
switch($idx)
{
	default:
		OrgChartPage($ocid, $oeid, $iduser, $disp);
		break;
}