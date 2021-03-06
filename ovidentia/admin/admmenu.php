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
 * links of super admin
 * @return array
 */
function bab_superAdminMenuItems()
{
    $list = array(
        array('AdminDelegations', bab_translate("Delegation"), $GLOBALS['babUrlScript']."?tg=delegat", null, Func_Icons::APPS_DELEGATIONS),
        array('AdminStats', bab_translate("Statistics"), $GLOBALS['babUrlScript']."?tg=admstats", null, Func_Icons::APPS_STATISTICS),
        array('AdminThesaurus', bab_translate("Thesaurus"), $GLOBALS['babUrlScript']."?tg=admthesaurus", null, Func_Icons::APPS_THESAURUS)
    );

    $engine = bab_searchEngineInfos();

    if( false !== $engine && $engine['indexes'] ) {

        $list[] = array('SearchIndex', bab_translate("Search indexes"), $GLOBALS['babUrlScript'].'?tg=admindex', null, Func_Icons::APPS_PREFERENCES_SEARCH_ENGINE);
    }

    return $list;
}

function bab_adminMenuAddons()
{
    $addon_urls = array();
    $addons = bab_addonsInfos::getRows();

    $babPhpSelf = bab_getSelf();

    foreach($addons as $row)
    {
        if($row['access'])
        {
            $arr = bab_getAddonsMenus($row, "getAdminSectionMenus");
            reset ($arr);
            while (list ($txt, $url) = each($arr))
            {
                if (0 === mb_strpos($url, $GLOBALS['babUrl'].$babPhpSelf)) {
                    $url = mb_substr($url, mb_strlen($GLOBALS['babUrl'].$babPhpSelf));
                }

                $addon_urls[] = array(
                    'label' => $txt,
                    'url' => $url,
                    'uid' => $row['title'].sprintf('_%u',crc32($url))
                );
            }
        }
    }

    return $addon_urls;
}





/**
 * Create links in sitemap for admin section
 * @param	bab_eventBeforeSiteMapCreated &$event
 */
function bab_sitemap_mergedAdminSection(bab_eventBeforeSiteMapCreated $event)
{
    global $babDB;

    // collect url from addons

    $addon_urls = array();

    if (bab_isUserAdministrator() && $event->loadChildNodes(array('root', 'DGAll', 'babAdmin'))) {
        $addon_urls = bab_adminMenuAddons();
    }

    // add nodes to delegations

    $allobjects = array();
    foreach (bab_getDelegationsObjects() as $arr) {
        $allobjects[$arr[0]] = $arr;
    }

    $delegations = bab_getUserSitemapDelegations();

    foreach ($delegations as $key => $deleg) {

        $dg_prefix = false === $deleg['id'] ? 'bab' : 'babDG'.$deleg['id'];
        $position = array('root', $key, $dg_prefix.'Admin');


        $item = $event->createItem($dg_prefix.'Admin');
        $item->setLabel(bab_translate("Administration"));
        $item->setPosition(array('root', $key));
        $item->addIconClassname(Func_Icons::PLACES_ADMINISTRATOR_HOME);
        $item->progress = true;

        $event->addFolder($item);


        $dgAdmGroups = bab_getDgAdmGroups();

        if (count($dgAdmGroups) > 0) {

            $item = $event->createItem($dg_prefix.'AdminDelegChange');
            $item->setLabel(bab_translate("Change administration"));
            $item->setDescription(bab_translate("Change administration delegation"));
            $item->setLink($GLOBALS['babUrlScript'].'?tg=delegusr');
            $item->setPosition($position);
            $item->addIconClassname('apps-delegations');

            $event->addFunction($item);
        }


        foreach ($allobjects as $o) {
            if (null === $o[3]) {
                continue;
            }

            $item = $event->createItem($dg_prefix.$o[2]);
            $item->setLabel($o[1]);
            if (isset($o[1])) {
                $item->setDescription($o[4]);
            }
            $item->setLink($o[3]);
            $item->setPosition($position);

            if (isset($o[5])) {
                $item->addIconClassname($o[5]);
            }

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

    if (bab_isUserAdministrator()) {

        $iconClassnames = array(
            1 => 'apps-preferences-site',
            2 => 'apps-preferences-mail-server',
            3 => 'apps-preferences-user',
            4 => 'apps-preferences-upload',
            5 => 'apps-preferences-date-time-format',
            6 => 'apps-preferences-calendar',
            8 => 'apps-preferences-authentication',
            10 => 'apps-preferences-wysiwyg-editor',
            11 => 'apps-preferences-search-engine',
            12 => 'apps-preferences-webservices',
            13 => 'apps-preferences-calendar',
        );

        // add nodes without control on delegations

        $superadminDelegations = array();

        if (isset($delegations['DGAll'])) {
            $superadminDelegations['DGAll'] = $delegations['DGAll'];
        }

        if (isset($delegations['DG0'])) {
            $superadminDelegations['DG0'] = $delegations['DG0'];
        }

        $links = bab_superAdminMenuItems();

        foreach ($superadminDelegations as $key => $deleg) {
            $dg_prefix = false === $deleg['id'] ? 'bab' : 'babDG'.$deleg['id'];
            $position = array('root', $key, $dg_prefix.'Admin');

            foreach ($links as $l) {
                $item = $event->createItem($dg_prefix.$l[0]);
                $item->setLabel($l[1]);
                $item->setLink($l[2]);
                if (isset($l[3])) {
                    $item->setDescription($l[3]);
                }
                $item->setPosition($position);
                $item->addIconClassname($l[4]);

                $event->addFunction($item);
            }

            $item = $event->createItem($dg_prefix.'AdminInstall');
            $item->setLabel(bab_translate("Add/remove programs"));
            $item->setLink($GLOBALS['babUrlScript']."?tg=addons");
            $item->setPosition($position);
            $item->addIconClassname(Func_Icons::ACTIONS_LIST_ADD);
            $event->addFolder($item);

            $add_remove_programs = $position;
            $add_remove_programs[] = $dg_prefix.'AdminInstall';

            $item = $event->createItem($dg_prefix.'AdminInstallVersion');
            $item->setLabel(bab_translate("Version"));
            $item->setLink($GLOBALS['babUrlScript']."?tg=addons&idx=version");
            $item->setPosition($add_remove_programs);
            $item->addIconClassname(Func_Icons::ACTIONS_LIST_ADD);
            $event->addFunction($item);

            $item = $event->createItem($dg_prefix.'AdminInstallAddons');
            $item->setLabel(bab_translate("Add-ons"));
            $item->setLink($GLOBALS['babUrlScript']."?tg=addons&idx=list");
            $item->setPosition($add_remove_programs);
            $item->addIconClassname(Func_Icons::ACTIONS_LIST_ADD);
            $event->addFunction($item);

            $item = $event->createItem($dg_prefix.'AdminInstallThemes');
            $item->setLabel(bab_translate("Skins"));
            $item->setLink($GLOBALS['babUrlScript']."?tg=addons&idx=theme");
            $item->setPosition($add_remove_programs);
            $item->addIconClassname(Func_Icons::ACTIONS_LIST_ADD);
            $event->addFunction($item);

            $item = $event->createItem($dg_prefix.'AdminInstallLibraries');
            $item->setLabel(bab_translate("Shared Libraries"));
            $item->setLink($GLOBALS['babUrlScript']."?tg=addons&idx=library");
            $item->setPosition($add_remove_programs);
            $item->addIconClassname(Func_Icons::ACTIONS_LIST_ADD);
            $event->addFunction($item);


            $item = $event->createItem($dg_prefix.'AdminSites');
            $item->setLabel(bab_translate("Sites"));
            $item->setLink($GLOBALS['babUrlScript']."?tg=sites");
            $item->setPosition($position);
            $item->addIconClassname(Func_Icons::APPS_PREFERENCES_SITE);
            $event->addFolder($item);



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


                foreach (bab_getSitesConfigurationMenus() as $number => $label) {

                    $itemposition = $siteposition;
                    $itemposition[] = $siteUid;

                    $item = $event->createItem($siteUid.'Menu'.$number);
                    $item->setLabel($label);
                    $item->setLink($GLOBALS['babUrlScript'].'?tg=site&idx=menu'.$number.'&item='.$arr['id']);
                    $item->setPosition($itemposition);
                    if (isset($iconClassnames[$number])) {
                        $item->addIconClassname($iconClassnames[$number]);
                    }

                    $event->addFunction($item);
                }
            }

            foreach ($addon_urls as $arr) {
                $item = $event->createItem($dg_prefix.$arr['uid']);
                $item->setLabel($arr['label']);
                $item->setLink($arr['url']);
                $item->setPosition(array('root', $key, $dg_prefix.'Admin'));

                $event->addFunction($item);
            }
        }
    }
}



/**
 * Create links in sitemap for admin section
 * @param	bab_eventBeforeSiteMapCreated &$event
 */
function bab_sitemap_adminSection(bab_eventBeforeSiteMapCreated $event)
{
    global $babDB;

    // collect url from addons

    $addon_urls = array();

    if (bab_isUserAdministrator() && $event->loadChildNodes(array('root', 'DGAll', 'babAdmin'))) {
        $addon_urls = bab_adminMenuAddons();
    }


    // add nodes to delegations

    $allobjects = array();
    foreach (bab_getDelegationsObjects() as $arr) {
        $allobjects[$arr[0]] = $arr;
    }

    $delegations = bab_getUserSitemapDelegations();

    foreach ($delegations as $key => $deleg) {

        $dg_prefix = false === $deleg['id'] ? 'bab' : 'babDG'.$deleg['id'];
        $position = array('root', $key, $dg_prefix.'Admin', $dg_prefix.'AdminSection');


        $item = $event->createItem($dg_prefix.'Admin');
        $item->setLabel(bab_translate("Administration"));
        $item->setPosition(array('root', $key));
        $item->addIconClassname(Func_Icons::PLACES_ADMINISTRATOR_HOME);
        $item->progress = true;

        $event->addFolder($item);


        $item = $event->createItem($dg_prefix.'AdminSection');
        $item->setLabel(bab_translate("Ovidentia functions"));
        $item->setPosition(array('root', $key, $dg_prefix.'Admin'));
        $item->addIconClassname(Func_Icons::PLACES_ADMINISTRATOR_APPLICATIONS);
        $item->progress = true;

        $event->addFolder($item);

        $dgAdmGroups = bab_getDgAdmGroups();

        if (count($dgAdmGroups) > 0) {

            $item = $event->createItem($dg_prefix.'AdminDelegChange');
            $item->setLabel(bab_translate("Change administration"));
            $item->setDescription(bab_translate("Change administration delegation"));
            $item->setLink($GLOBALS['babUrlScript'].'?tg=delegusr');
            $item->setPosition($position);
            $item->addIconClassname('apps-delegations');

            $event->addFunction($item);
        }


        foreach ($allobjects as $o) {
            if (null === $o[3]) {
                continue;
            }

            $item = $event->createItem($dg_prefix.$o[2]);
            $item->setLabel($o[1]);
            if (isset($o[1])) {
                $item->setDescription($o[4]);
            }
            $item->setLink($o[3]);
            $item->setPosition($position);

            if (isset($o[5])) {
                $item->addIconClassname($o[5]);
            }

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

    if (bab_isUserAdministrator()) {

        $iconClassnames = array(
            1 => 'apps-preferences-site',
            2 => 'apps-preferences-mail-server',
            3 => 'apps-preferences-user',
            4 => 'apps-preferences-upload',
            5 => 'apps-preferences-date-time-format',
            6 => 'apps-preferences-calendar',
            8 => 'apps-preferences-authentication',
            10 => 'apps-preferences-wysiwyg-editor',
            11 => 'apps-preferences-search-engine',
            12 => 'apps-preferences-webservices',
            13 => 'apps-preferences-calendar',
        );
        // add nodes without control on delegations

        $superadminDelegations = array();

        if (isset($delegations['DGAll'])) {
            $superadminDelegations['DGAll'] = $delegations['DGAll'];
        }

        if (isset($delegations['DG0'])) {
            $superadminDelegations['DG0'] = $delegations['DG0'];
        }


        $links = bab_superAdminMenuItems();

        foreach ($superadminDelegations as $key => $deleg) {
            $dg_prefix = false === $deleg['id'] ? 'bab' : 'babDG'.$deleg['id'];
            $position = array('root', $key, $dg_prefix.'Admin', $dg_prefix.'AdminSection');


            foreach ($links as $l) {
                $item = $event->createItem($dg_prefix.$l[0]);
                $item->setLabel($l[1]);
                $item->setLink($l[2]);
                if (isset($l[3])) {
                    $item->setDescription($l[3]);
                }
                $item->setPosition($position);
                $item->addIconClassname($l[4]);

                $event->addFunction($item);
            }


            $item = $event->createItem($dg_prefix.'AdminInstall');
            $item->setLabel(bab_translate("Add/remove programs"));
            $item->setLink($GLOBALS['babUrlScript']."?tg=addons");
            $item->setPosition($position);
            $item->addIconClassname(Func_Icons::ACTIONS_LIST_ADD);
            $event->addFolder($item);

            $add_remove_programs = $position;
            $add_remove_programs[] = $dg_prefix.'AdminInstall';

            $item = $event->createItem($dg_prefix.'AdminInstallVersion');
            $item->setLabel(bab_translate("Version"));
            $item->setLink($GLOBALS['babUrlScript']."?tg=addons&idx=version");
            $item->setPosition($add_remove_programs);
            $item->addIconClassname(Func_Icons::ACTIONS_LIST_ADD);
            $event->addFunction($item);

            $item = $event->createItem($dg_prefix.'AdminInstallAddons');
            $item->setLabel(bab_translate("Add-ons"));
            $item->setLink($GLOBALS['babUrlScript']."?tg=addons&idx=list");
            $item->setPosition($add_remove_programs);
            $item->addIconClassname(Func_Icons::ACTIONS_LIST_ADD);
            $event->addFunction($item);

            $item = $event->createItem($dg_prefix.'AdminInstallThemes');
            $item->setLabel(bab_translate("Skins"));
            $item->setLink($GLOBALS['babUrlScript']."?tg=addons&idx=theme");
            $item->setPosition($add_remove_programs);
            $item->addIconClassname(Func_Icons::ACTIONS_LIST_ADD);
            $event->addFunction($item);

            $item = $event->createItem($dg_prefix.'AdminInstallLibraries');
            $item->setLabel(bab_translate("Shared Libraries"));
            $item->setLink($GLOBALS['babUrlScript']."?tg=addons&idx=library");
            $item->setPosition($add_remove_programs);
            $item->addIconClassname(Func_Icons::ACTIONS_LIST_ADD);
            $event->addFunction($item);



            $item = $event->createItem($dg_prefix.'AdminSites');
            $item->setLabel(bab_translate("Sites"));
            $item->setLink($GLOBALS['babUrlScript']."?tg=sites");
            $item->setPosition($position);
            $item->addIconClassname(Func_Icons::APPS_PREFERENCES_SITE);
            $event->addFolder($item);



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

                foreach (bab_getSitesConfigurationMenus() as $number => $label) {

                    $itemposition = $siteposition;
                    $itemposition[] = $siteUid;

                    $item = $event->createItem($siteUid.'Menu'.$number);
                    $item->setLabel($label);
                    $item->setLink($GLOBALS['babUrlScript'].'?tg=site&idx=menu'.$number.'&item='.$arr['id']);
                    $item->setPosition($itemposition);

                    if (isset($iconClassnames[$number])) {
                        $item->addIconClassname($iconClassnames[$number]);
                    }

                    $event->addFunction($item);
                }
            }

            $item = $event->createItem($dg_prefix.'AdminSectionAddons');
            $item->setLabel(bab_translate("Add-ons links"));
            $item->setPosition(array('root',$key, $dg_prefix.'Admin'));
            $item->addIconClassname(Func_Icons::PLACES_ADMINISTRATOR_APPLICATIONS);
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

