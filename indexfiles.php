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
include_once $GLOBALS['babInstallPath'].'utilit/indexincl.php';

/**
 * Launch index jobs as automated task (cronjob)
 * @param string $idx
 */
function bab_indexJobs($idx, $object) {

	$reg = bab_getRegistryInstance();

	$reg->changeDirectory('/bab/indexfiles/');
	$allowed_ip = $reg->getValue('allowed_ip');
	if (null == $allowed_ip) {
		$allowed_ip = '127.0.0.1';
	}

	if ($allowed_ip == $_SERVER['REMOTE_ADDR']) {

		switch($object) {
			
			case 'bab_files':

				break;

			case 'bab_art_files':

				break;

			case 'bab_forumsfiles':

				break;

			default:	// Addon
				bab_callAddonsFunction('onIndexObject', $object, $idx);
				break;
		}
		
	} else {
		$GLOBALS['babBody']->msgerror = sprintf(bab_translate("Access denied, your current IP address (%s) is not allowed"),$_SERVER['REMOTE_ADDR']);
	}
}


if (isset($_GET['idx'])) {
	bab_cleanGpc();
	bab_indexJobs($_GET['idx'], $_GET['obj']);
}

?>