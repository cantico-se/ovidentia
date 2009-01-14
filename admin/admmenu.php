
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

/**
 * Create links in sitemap for admin section
 * @param	bab_eventBeforeSiteMapCreated &$event
 */
function bab_sitemap_adminSection(&$event) {
	global $babBody, $babDB;
	
	$item = $event->createItem('babAdmin');
	$item->setLabel(bab_translate("Administration"));
	$item->setPosition(array('root', 'DGAll'));
	$event->addFolder($item);
	
	$item = $event->createItem('babAdminSection');
	$item->setLabel(bab_translate("Ovidentia functions"));
	$item->setPosition(array('root', 'DGAll','babAdmin'));
	$event->addFolder($item);

	
	
	
	$array_urls = array();

	if( ($dgcnt = count($babBody->dgAdmGroups)) > 0 )
		{
		if( $babBody->isSuperAdmin || $dgcnt > 1 )
			{
			$array_urls[] = array(
				'label' => bab_translate("Change administration"),
				'url' => $GLOBALS['babUrlScript']."?tg=delegusr",
				'uid' => 'babAdminDelegChange',
				'desc' => bab_translate("Change administration delegation")
				);
			
			}
		}

	if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0)
		{
		$array_urls[] = array(
				'label' => bab_translate("Delegation"),
				'url' => $GLOBALS['babUrlScript']."?tg=delegat",
				'uid' => 'babAdminDelegations'
			);
		}

	$array_urls[] = array(
		'label' => bab_translate("Users"),
		'url' => $GLOBALS['babUrlScript']."?tg=users&bupd=0",
		'uid' => 'babAdminUsers',
		'desc' => bab_translate("Users management")
	);
			
	$array_urls[] = array(
		'label' => bab_translate("Groups"),
		'url' => $GLOBALS['babUrlScript']."?tg=groups",
		'uid' => 'babAdminGroups'
	);
	
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['sections'] == 'Y') {
		$array_urls[] = array(
			'label' => bab_translate("Sections"),
			'url' => $GLOBALS['babUrlScript']."?tg=sections",
			'uid' => 'babAdminSections' 
		);
	}
		
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['faqs'] == 'Y') {
		$array_urls[] = array(
			'label' => bab_translate("Faq"),
			'url' => $GLOBALS['babUrlScript']."?tg=admfaqs",
			'uid' => 'babAdminFaqs', 
			'desc' => bab_translate("Frequently Asked Questions")
		);
	}
	
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['articles'] == 'Y') {
		$array_urls[] = array(
			'label' => bab_translate("Articles"),
			'url' => $GLOBALS['babUrlScript']."?tg=topcats",
			'uid' => 'babAdminArticles', 
			'desc' => bab_translate("Categories and topics management")
		);
	}
	
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['forums'] == 'Y') {
		$array_urls[] = array(
			'label' => bab_translate("Forums"),
			'url' => $GLOBALS['babUrlScript']."?tg=forums",
			'uid' => 'babAdminForums'
		);
	}
	
	
	
	if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 ) {
		$array_urls[] = array(
			'label' => bab_translate("Vacations"),
			'url' => $GLOBALS['babUrlScript']."?tg=admvacs",
			'uid' => 'babAdminVacations'
		);
	}
	
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['calendars'] == 'Y') {
		$array_urls[] = array(
			'label' => bab_translate("Calendar"),
			'url' => $GLOBALS['babUrlScript']."?tg=admcals",
			'uid' => 'babAdminCalendars'
		);
	}
	
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['mails'] == 'Y') {
		$array_urls[] = array(
			'label' => bab_translate("Mail"),
			'url' => $GLOBALS['babUrlScript']."?tg=maildoms&userid=0&bgrp=y",
			'uid' => 'babAdminMail'
		);
	}
	
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['filemanager'] == 'Y') {
		$array_urls[] = array(
			'label' => bab_translate("File manager"),
			'url' => $GLOBALS['babUrlScript']."?tg=admfms",
			'uid' => 'babAdminFm'
		);
	}
	
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['approbations'] == 'Y') {
		$array_urls[] = array(
			'label' => bab_translate("Approbations"),
			'url' => $GLOBALS['babUrlScript']."?tg=apprflow",
			'uid' => 'babAdminApprob'
		);
	}
	

	
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['directories'] == 'Y') {
		$array_urls[] = array(
			'label' => bab_translate("Directories"),
			'url' => $GLOBALS['babUrlScript']."?tg=admdir",
			'uid' => 'babAdminDir'
		);
	}
	
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || (isset($babBody->currentDGGroup['orgchart']) && $babBody->currentDGGroup['orgchart'] == 'Y')) {
		$array_urls[] = array(
			'label' => bab_translate("Charts"),
			'url' => $GLOBALS['babUrlScript']."?tg=admocs",
			'uid' => 'babAdminCharts'
		);
	}
	
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['taskmanager'] == 'Y') {
		$array_urls[] = array(
			'label' => bab_translate("Task Manager"),
			'url' => $GLOBALS['babUrlScript'].'?tg=admTskMgr',
			'uid' => 'babAdminTm'
		);
	}
	
	if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 ) {
		$array_urls[] = array(
			'label' => bab_translate("Add-ons"),
			'url' => $GLOBALS['babUrlScript']."?tg=addons",
			'uid' => 'babAdminAddons'
		);
	}
		
	if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 ) {
		$array_urls[] = array(
			'label' => bab_translate("Statistics"),
			'url' => $GLOBALS['babUrlScript']."?tg=admstats",
			'uid' => 'babAdminStats'
		);
	}

	if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 ) {
		$array_urls[] = array(
			'label' => bab_translate("Thesaurus"),
			'url' => $GLOBALS['babUrlScript']."?tg=admthesaurus",
			'uid' => 'babAdminThesaurus'
		);
	}
	
	
	$engine = bab_searchEngineInfos();

	if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 && false !== $engine && $engine['indexes'] ) {
		$array_urls[] = array(
			'label' => bab_translate("Search indexes"),
			'url' => $GLOBALS['babUrlScript']."?tg=admindex",
			'uid' => 'babAdminSearchIndex' 
		);
	}
	
	foreach($array_urls as $arr) {

		$link = $event->createItem($arr['uid']);
		$link->setLabel($arr['label']);
		$link->setLink($arr['url']);
		$link->setPosition(array('root','DGAll', 'babAdmin','babAdminSection'));
		if (isset($arr['desc'])) {
			$link->setDescription($arr['desc']);
		}
		$event->addFunction($link);
	}

	
	$addon_urls = array();
	
	if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 )
		{
		$addons = bab_addonsInfos::getRows();
		foreach($addons as $row)
			{
			if($row['access'])
				{
				$addonpath = $GLOBALS['babAddonsPath'].$row['title'];
				if( is_dir($addonpath))
					{
					$arr = bab_getAddonsMenus($row, "getAdminSectionMenus");
					reset ($arr);
					while (list ($txt, $url) = each($arr))
						{
						if (0 === mb_strpos($url, $GLOBALS['babUrl'].$GLOBALS['babPhpSelf'])) {
							$url = mb_substr($url, mb_strlen($GLOBALS['babUrl'].$GLOBALS['babPhpSelf']));
						}

						$addon_urls[] = array(
							'label' => $txt,
							'url' => $url,
							'uid' => $row['title'].sprintf('_%u',crc32($url))
							);
						}
					}
				}
			}
		}
		

	
	if (0 < count($addon_urls)) {
		$item = $event->createItem('babAdminSectionAddons');
		$item->setLabel(bab_translate("Add-ons links"));
		$item->setPosition(array('root','DGAll','babAdmin'));
		$event->addFolder($item);

		foreach($addon_urls as $arr) {
			$link = $event->createItem($arr['uid']);
			$link->setLabel($arr['label']);
			$link->setLink($arr['url']);
			$link->setPosition(array('root', 'DGAll', 'babAdmin','babAdminSectionAddons'));
			$event->addFunction($link);
		}
	}




	if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) {

		$item = $event->createItem('babAdminSites');
		$item->setLabel(bab_translate("Sites"));
		$item->setLink($GLOBALS['babUrlScript']."?tg=sites");
		$item->setPosition(array('root','DGAll', 'babAdmin','babAdminSection'));
		$event->addFolder($item);
		
		
		// sub menu for "Sites"
		
		include_once $GLOBALS['babInstallPath'].'utilit/sitesincl.php';
		$res = bab_getSitesRes();
		while ($arr = $babDB->db_fetch_assoc($res)) {

			$siteUid = 'babAdminSite'.$arr['id'];

			$item = $event->createItem($siteUid);
			$item->setLabel($arr['name']);
			$item->setLink($GLOBALS['babUrlScript']."?tg=site&idx=menusite&item=".$arr['id']);
			$item->setPosition(array('root','DGAll', 'babAdmin','babAdminSection', 'babAdminSites'));
			if (!empty($arr['description'])) {
				$item->setDescription($arr['description']);
			}
			$event->addFolder($item);


			foreach(bab_getSitesConfigurationMenus() as $number => $label) {

				$item = $event->createItem($siteUid.'Menu'.$number);
				$item->setLabel($label);
				$item->setLink($GLOBALS['babUrlScript'].'?tg=site&idx=menu'.$number.'&item='.$arr['id']);
				$item->setPosition(array('root','DGAll', 'babAdmin','babAdminSection', 'babAdminSites', $siteUid));
				$event->addFunction($item);
			}
		}
	}
}

