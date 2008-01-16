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
	$allowed_ip	= $reg->getValue('allowed_ip','127.0.0.1');
	$allowed_ip = explode(',',$allowed_ip);


	if (BAB_INDEX_WAITING == $idx) {
		$status = array(BAB_INDEX_STATUS_TOINDEX);
	} elseif (BAB_INDEX_ALL == $idx) {
		$status = array(BAB_INDEX_STATUS_TOINDEX, BAB_INDEX_STATUS_INDEXED);
	}

	$job = '';

	if (in_array($_SERVER['REMOTE_ADDR'], $allowed_ip)) {

		$prepare = isset($_GET['prepare']);

		switch($object) {
			
			case 'bab_files':
				include_once $GLOBALS['babInstallPath'].'utilit/fileincl.php';
				$r = indexAllFmFiles($status, $prepare);
				break;

			case 'bab_art_files':
				include_once $GLOBALS['babInstallPath'].'utilit/artincl.php';
				$r = indexAllArtFiles($status, $prepare);
				break;

			case 'bab_forumsfiles':
				include_once $GLOBALS['babInstallPath'].'utilit/forumincl.php';
				$r = indexAllForumFiles($status, $prepare);
				break;

			default:	// Addon

				include_once $GLOBALS['babInstallPath'].'utilit/addonsincl.php';
				bab_callAddonsFunction('onIndexObject', $object, $idx);
				break;
		}

		if (isset($r)) {
			while ($msg = $r->getNextInfo()) {
				$job .= $msg."\n";
			}
	
			while ($msg = $r->getNextError()) {
				$GLOBALS['babBodyPopup']->msgerror .= bab_toHtml($msg." \n", BAB_HTML_ALL);
			}
		}
		
	} else {
		$GLOBALS['babBodyPopup']->msgerror = sprintf(bab_translate("Access denied, your current IP address (%s) is not allowed"),$_SERVER['REMOTE_ADDR']);
	}

	$GLOBALS['babBodyPopup']->babecho(bab_toHtml($job."\n", BAB_HTML_ALL));
	
}



function indexEOF() {

	include_once $GLOBALS['babInstallPath']."utilit/indexincl.php";


	$engine = bab_searchEngineInfos();
	foreach($engine['indexes'] as $object => $index) {
		if (!$index['index_disabled']) {
			$obj = new bab_indexObject($object);
			$obj->applyIndex();
		}
	}
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



if (isset($_GET['cmd'])) {

	// execute une commande appellée par le fichier bat

	switch($_GET['cmd']) {
		case 'EOF': // tester les traitements a effectuer en fin d'indexation
				indexEOF();
			break;
	}
}

printBabBodyPopup();
die();

?>