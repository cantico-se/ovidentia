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

	if (BAB_INDEX_WAITING == $idx) {
		$status = array(BAB_INDEX_STATUS_TOINDEX);
	} elseif (BAB_INDEX_ALL == $idx) {
		$status = array(BAB_INDEX_STATUS_TOINDEX, BAB_INDEX_STATUS_INDEXED);
	}

	$job = '';

	if ($allowed_ip == $_SERVER['REMOTE_ADDR']) {

		switch($object) {
			
			case 'bab_files':
				include_once $GLOBALS['babInstallPath'].'utilit/fileincl.php';
				if ($n = indexAllFmFiles($status)) {
					$job = sprintf(bab_translate("Indexation of %s files in the file manager"), $n);
				} else {
					$job = bab_translate("No files to index in the file manager");
				}
				break;

			case 'bab_art_files':
				
				break;

			case 'bab_forumsfiles':
				
				break;

			default:	// Addon
				$addon_jobs = array();
				bab_callAddonsFunction('onIndexObject', $object, $idx, $addon_jobs);
				$job = implode("\n", $addon_jobs);
				break;
		}
		
	} else {
		$GLOBALS['babBodyPopup']->msgerror = sprintf(bab_translate("Access denied, your current IP address (%s) is not allowed"),$_SERVER['REMOTE_ADDR']);
	}


	


	$GLOBALS['babBodyPopup']->babecho(bab_toHtml($job."\n"));
	
}


bab_cleanGpc();

include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";
$GLOBALS['babBodyPopup'] = new babBodyPopup();
$GLOBALS['babBodyPopup']->title = bab_translate('Indexation');

if (isset($_GET['idx'])) {
	if (isset($_GET['obj'])) {
		bab_indexJobs($_GET['idx'], $_GET['obj']);
	} else {
		$engine = bab_searchEngineInfos();
		foreach($engine['indexes'] as $object => $index) {
			if (!$index['index_disabled']) {
				bab_indexJobs($_GET['idx'], $object);
			}
		}
	}
}

printBabBodyPopup();
die();

?>