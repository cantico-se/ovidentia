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
function bab_sitemap_adminSection(bab_eventBeforeSiteMapCreated $event) {
	global $babBody, $babDB;



	// collect url from addons

	$addon_urls = array();
	
	if( $babBody->isSuperAdmin )
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






	// add nodes to delegations


	
	$delegations = bab_getUserAdministratorDelegations();

	foreach( $delegations as $key => $deleg ) {

		$dg_prefix = false === $deleg['id'] ? 'bab' : 'babDG'.$deleg['id'];
		$position = array('root', $key, $dg_prefix.'Admin', $dg_prefix.'AdminSection');
		
		

		$item = $event->createItem($dg_prefix.'Admin');
		$item->setLabel(bab_translate("Administration"));
		$item->setPosition(array('root', $key));
		$item->progress = true;
		
		$event->addFolder($item);
		

		$item = $event->createItem($dg_prefix.'AdminSection');
		$item->setLabel(bab_translate("Ovidentia functions"));
		$item->setPosition(array('root', $key, $dg_prefix.'Admin'));
		$item->progress = true;
		
		$event->addFolder($item);
		
		if( count($babBody->dgAdmGroups) > 0) {

			$item = $event->createItem($dg_prefix.'AdminDelegChange');
			$item->setLabel(bab_translate("Change administration"));
			$item->setDescription(bab_translate("Change administration delegation"));
			$item->setLink($GLOBALS['babUrlScript'].'?tg=delegusr');
			$item->setPosition($position);
			$item->addIconClassname('apps-delegations');
			
			$event->addFunction($item);
		}


		if (isset($deleg['objects']['sections'])) {

			$item = $event->createItem($dg_prefix.'AdminSections');
			$item->setLabel($deleg['objects']['sections']);
			$item->setLink($GLOBALS['babUrlScript'].'?tg=sections');
			$item->setPosition($position);
			$item->addIconClassname('apps-sections');
			
			$event->addFunction($item);
		}



		if (isset($deleg['objects']['faqs'])) {

			$item = $event->createItem($dg_prefix.'AdminFaqs');
			$item->setLabel($deleg['objects']['faqs']);
			$item->setDescription(bab_translate("Frequently Asked Questions"));
			$item->setLink($GLOBALS['babUrlScript'].'?tg=admfaqs');
			$item->setPosition($position);
			$item->addIconClassname('apps-faqs');
			
			$event->addFunction($item);
		}

		
		
		if (isset($deleg['objects']['articles'])) {
				
			$item = $event->createItem($dg_prefix.'AdminArticles');
			$item->setLabel($deleg['objects']['articles']);
			$item->setDescription(bab_translate("Categories and topics management"));
			$item->setLink($GLOBALS['babUrlScript'].'?tg=topcats');
			$item->setPosition($position);
			$item->addIconClassname('apps-articles'); 
			
			$event->addFunction($item);
		}


		if (isset($deleg['objects']['forums'])) {

			$item = $event->createItem($dg_prefix.'AdminForums');
			$item->setLabel($deleg['objects']['forums']);
			$item->setLink($GLOBALS['babUrlScript'].'?tg=forums');
			$item->setPosition($position);
			$item->addIconClassname('apps-forums');
			
			$event->addFunction($item);
		}




		if (isset($deleg['objects']['calendars'])) {

			$item = $event->createItem($dg_prefix.'AdminCalendars');
			$item->setLabel($deleg['objects']['calendars']);
			$item->setLink($GLOBALS['babUrlScript'].'?tg=admcals');
			$item->setPosition($position);
			$item->addIconClassname('apps-calendar');
			
			$event->addFunction($item);
		}


		if (isset($deleg['objects']['mails'])) {

			$item = $event->createItem($dg_prefix.'AdminMail');
			$item->setLabel($deleg['objects']['mails']);
			$item->setLink($GLOBALS['babUrlScript'].'?tg=maildoms&userid=0&bgrp=y');
			$item->setPosition($position);
			$item->addIconClassname('apps-mail');
			
			$event->addFunction($item);
		}




		if (isset($deleg['objects']['filemanager'])) {

			$item = $event->createItem($dg_prefix.'AdminFm');
			$item->setLabel($deleg['objects']['filemanager']);
			$item->setLink($GLOBALS['babUrlScript'].'?tg=admfms');
			$item->setPosition($position);
			$item->addIconClassname('apps-file-manager');
			
			$event->addFunction($item);
		}


		if (isset($deleg['objects']['approbations'])) {

			$item = $event->createItem($dg_prefix.'AdminApprob');
			$item->setLabel($deleg['objects']['approbations']);
			$item->setLink($GLOBALS['babUrlScript'].'?tg=apprflow');
			$item->setPosition($position);
			$item->addIconClassname('apps-approbations');
			
			$event->addFunction($item);
		}
			

			
		if (isset($deleg['objects']['directories'])) {

			$item = $event->createItem($dg_prefix.'AdminDir');
			$item->setLabel($deleg['objects']['directories']);
			$item->setLink($GLOBALS['babUrlScript'].'?tg=admdir');
			$item->setPosition($position);
			$item->addIconClassname('apps-directories');
			
			$event->addFunction($item);

		}
			
		if (isset($deleg['objects']['orgchart'])) {

			$item = $event->createItem($dg_prefix.'AdminCharts');
			$item->setLabel($deleg['objects']['orgchart']);
			$item->setLink($GLOBALS['babUrlScript'].'?tg=admocs');
			$item->setPosition($position);
			$item->addIconClassname('apps-orgcharts');
			
			$event->addFunction($item, $key);

		}
			
		if (isset($deleg['objects']['taskmanager'])) {

			$item = $event->createItem($dg_prefix.'AdminTm');
			$item->setLabel($deleg['objects']['taskmanager']);
			$item->setLink($GLOBALS['babUrlScript'].'?tg=admTskMgr');
			$item->setPosition($position);
			$item->addIconClassname('apps-task-manager');
			
			$event->addFunction($item);
		}

		$item = $event->createItem($dg_prefix.'AdminUsers');
		$item->setLabel(bab_translate("Users"));
		$item->setLink($GLOBALS['babUrlScript'].'?tg=users&bupd=0');
		$item->setPosition($position);
		$item->addIconClassname('apps-users');
		
		$event->addFunction($item);
	

	
		$item = $event->createItem($dg_prefix.'AdminGroups');
		$item->setLabel(bab_translate("Groups"));
		$item->setLink($GLOBALS['babUrlScript'].'?tg=groups');
		$item->setPosition($position);
		$item->addIconClassname('apps-groups');
		
		$event->addFunction($item);

	}

	if( $babBody->isSuperAdmin )
		{

			// add nodes without control on delegations

			$superadminDelegations = array(
				'DGAll' => $delegations['DGAll'],
				'DG0' => $delegations['DG0']
			);

			foreach( $superadminDelegations as $key => $deleg )
			{
				$dg_prefix = false === $deleg['id'] ? 'bab' : 'babDG'.$deleg['id'];
				$position = array('root', $key, $dg_prefix.'Admin', $dg_prefix.'AdminSection');


				$item = $event->createItem($dg_prefix.'AdminDelegations');
				$item->setLabel(bab_translate("Delegation"));
				$item->setLink($GLOBALS['babUrlScript'].'?tg=delegat');
				$item->setPosition($position);
				$item->addIconClassname('apps-delegations');
				
				$event->addFunction($item);


				$item = $event->createItem($dg_prefix.'AdminVacations');
				$item->setLabel(bab_translate("Vacations"));
				$item->setLink($GLOBALS['babUrlScript'].'?tg=admvacs');
				$item->setPosition($position);
				$item->addIconClassname('apps-vacations');
				
				$event->addFunction($item);


				$item = $event->createItem($dg_prefix.'AdminInstall');
				$item->setLabel(bab_translate("Add/remove programs"));
				$item->setLink($GLOBALS['babUrlScript'].'?tg=addons');
				$item->setPosition($position);
				$item->addIconClassname('actions-list-add');
				
				$event->addFunction($item);

				$item = $event->createItem($dg_prefix.'AdminStats');
				$item->setLabel(bab_translate("Statistics"));
				$item->setLink($GLOBALS['babUrlScript'].'?tg=admstats');
				$item->setPosition($position);
				$item->addIconClassname('apps-statistics');
				
				$event->addFunction($item);

				$item = $event->createItem($dg_prefix.'AdminThesaurus');
				$item->setLabel(bab_translate("Thesaurus"));
				$item->setLink($GLOBALS['babUrlScript'].'?tg=admthesaurus');
				$item->setPosition($position);
				$item->addIconClassname('apps-thesaurus');
				
				$event->addFunction($item);

				$engine = bab_searchEngineInfos();

				if( false !== $engine && $engine['indexes'] ) {

					$item = $event->createItem($dg_prefix.'SearchIndex');
					$item->setLabel(bab_translate("Search indexes"));
					$item->setLink($GLOBALS['babUrlScript'].'?tg=admindex');
					$item->setPosition($position);
					
					$event->addFunction($item);
				}


				$item = $event->createItem($dg_prefix.'AdminSites');
				$item->setLabel(bab_translate("Sites"));
				$item->setLink($GLOBALS['babUrlScript']."?tg=sites");
				$item->setPosition($position);
				$item->addIconClassname('apps-preferences-site');
				
				$event->addFunction($item);
			
				
				// sub menu for "Sites"
				
				include_once $GLOBALS['babInstallPath'].'utilit/sitesincl.php';
				$res = bab_getSitesRes();
				while ($arr = $babDB->db_fetch_assoc($res)) {

					$siteUid = $dg_prefix.'AdminSite'.$arr['id'];
					$siteposition = $position;
					$siteposition[] = $dg_prefix.'AdminSites';

					$item = $event->createItem($siteUid);
					$item->setLabel($arr['name']);
					$item->setLink($GLOBALS['babUrlScript']."?tg=site&idx=menusite&item=".$arr['id']);
					$item->setPosition($siteposition);
					
					if (!empty($arr['description'])) {
						$item->setDescription($arr['description']);
					}
					$item->progress = true;
					
					$event->addFolder($item);


					foreach(bab_getSitesConfigurationMenus() as $number => $label) {

						$itemposition = $siteposition;
						$itemposition[] = $siteUid;

						$item = $event->createItem($siteUid.'Menu'.$number);
						$item->setLabel($label);
						$item->setLink($GLOBALS['babUrlScript'].'?tg=site&idx=menu'.$number.'&item='.$arr['id']);
						$item->setPosition($itemposition);
						

						switch($number) {
					
							case 1:	// site
								$item->addIconClassname('apps-preferences-site');
								break;

							case 2: // mail
								$item->addIconClassname('apps-preferences-mail-server');
								break;

							case 3: // user
								$item->addIconClassname('apps-preferences-user');
								break;

							case 4: // upload
								$item->addIconClassname('apps-preferences-upload');
								break;	

							case 5: 
								$item->addIconClassname('apps-preferences-date-time-format');
								break;

							case 6: 
								$item->addIconClassname('apps-preferences-calendar');
								break;

							case 8:
								$item->addIconClassname('apps-preferences-authentication');
								break;	

							case 10:
								$item->addIconClassname('apps-preferences-wysiwyg-editor');
								break;	

							case 11:
								$item->addIconClassname('apps-preferences-search-engine');
								break;	

							case 12:
								$item->addIconClassname('apps-preferences-webservices');
								break;

						}


						$event->addFunction($item);
					}
				}


				if (0 < count($addon_urls)) {
					$item = $event->createItem($dg_prefix.'AdminSectionAddons');
					$item->setLabel(bab_translate("Add-ons links"));
					$item->setPosition(array('root',$key, $dg_prefix.'Admin'));
					$item->progress = true;
					
					$event->addFolder($item);

					foreach($addon_urls as $arr) {
						$item = $event->createItem($dg_prefix.$arr['uid']);
						$item->setLabel($arr['label']);
						$item->setLink($arr['url']);
						$item->setPosition(array('root', $key, $dg_prefix.'Admin', $dg_prefix.'AdminSectionAddons'));
						
						$event->addFunction($item);
					}
				}
			}
		}

}

