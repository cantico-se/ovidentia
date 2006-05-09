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



function bab_getAddonsMenus($row, $what)
{
	global $babDB;
	$addon_urls = array();
	$addonpath = $GLOBALS['babAddonsPath'].$row['title'];
	if( is_file($addonpath."/init.php" ))
		{
		$GLOBALS['babAddonFolder'] = $row['title'];
		$GLOBALS['babAddonTarget'] = "addon/".$row['id'];
		$GLOBALS['babAddonUrl'] = $GLOBALS['babUrlScript']."?tg=addon/".$row['id']."/";
		$GLOBALS['babAddonPhpPath'] = $GLOBALS['babInstallPath']."addons/".$row['title']."/";
		$GLOBALS['babAddonHtmlPath'] = "addons/".$row['title']."/";
		$GLOBALS['babAddonUpload'] = $GLOBALS['babUploadPath']."/addons/".$row['title']."/";
		require_once( $addonpath."/init.php" );
		$func = $row['title']."_".$what;
		if( !empty($func) && function_exists($func))
			{
			while( $func($url, $txt))
				{
				$addon_urls[$txt] = $url;
				}
			$func = $row['title']."_onSectionCreate";
			$res = $babDB->db_query("select id from ".BAB_SECTIONS_ORDER_TBL." where id_section='".$row['id']."' and type='4'");
			if( $res && $babDB->db_num_rows($res) < 1 && function_exists($func))
				{
				$arr = $babDB->db_fetch_array($babDB->db_query("select max(ordering) from ".BAB_SECTIONS_ORDER_TBL." where position='0'"));
				$babDB->db_query("insert into ".BAB_SECTIONS_ORDER_TBL." (id_section, position, type, ordering) VALUES ('" .$row['id']. "', '0', '4', '" . ($arr[0]+1). "')");
				}
			else if( $res && $babDB->db_num_rows($res) > 0 && !function_exists($func))
				{
				$babDB->db_query("delete from ".BAB_SECTIONS_ORDER_TBL." where id_section='".$row['id']."' and type='4'");	
				$babDB->db_query("delete from ".BAB_SECTIONS_STATES_TBL." where id_section='".$row['id']."' and type='4'");	
				}
			}
		}
	return $addon_urls;
}



class babSection
{
var $title;
var $content;
var $hiddenz;
var $position;
var $close;
var $boxurl;
var $bbox;
var $template;

function babSection($title = "Section", $content="<br>This is a sample of content<br>")
{
	$this->title = $title;
	$this->content = bab_replace($content);
	$this->hiddenz = false;
	$this->position = 0;
	$this->close = 0;
	$this->boxurl = "";
	$this->bbox = 0;
	$this->template = "default";
	$this->t_open = bab_translate("Open");
	$this->t_close = bab_translate("Close");
}

function getTitle() { return $this->title;}
function setTitle($title) {	$this->title = $title;}
function getContent() {	return $this->content;}
function getTemplate() { return $this->template;}
function setTemplate($template)
	{
	if( !empty($template))
		$this->template = $template;
	}

function setContent($content)
{
	$this->content = $content;
}

function getPosition()
{
	return $this->position;
}

function setPosition($pos)
{
	$this->position = $pos;
}

function isVisible()
{
	if( $this->hiddenz == true)
		return false;
	else
		return true;
}

function show()
{
	$this->hiddenz = false;
}

function hide()
{
	$this->hiddenz = true;
}

function close()
{
	$this->close = 1;
}

function open()
{
	$this->close = 0;
}

function printout()
{
	$file = "sectiontemplate.html";
	$str = bab_printTemplate($this,$file, $this->template);
	if( empty($str))
		return bab_printTemplate($this,$file, "default");
	else
		return $str;
}

}  /* end of class babSection */


class babSectionTemplate extends babSection
{
var $file;
function babSectionTemplate($file, $section="")
	{
	$this->babSection("","");

	
	$this->file = $file;
	$this->htmlid = substr($this->file,0,-5);
	$this->setTemplate($section);
	}

function printout()
	{
	if( !file_exists( 'skins/'.$GLOBALS['babSkin'].'/templates/'. $this->file ) )
		{
		if (!$this->close)
			$this->content = bab_printTemplate($this,'insections.html', $this->htmlid );
		return bab_printTemplate($this,'sectiontemplate.html', 'default');
		}
	$str = bab_printTemplate($this,$this->file, $this->template);
	if( empty($str))
		return bab_printTemplate($this,$this->file, "template");
	else
		return $str;
	}
}

class babAdminSection extends babSectionTemplate
{
var $array_urls = array();
var $addon_urls = array();
var $head;
var $foot;
var $key;
var $val;

function babAdminSection($close)
	{
	global $babBody, $babDB;
	$this->babSectionTemplate("adminsection.html", "template");
	$this->title = bab_translate("Administration");
	if( $close )
		return;

	if( ($dgcnt = count($babBody->dgAdmGroups)) > 0 )
		{
		if( $babBody->isSuperAdmin || $dgcnt > 1 )
			{
			$this->array_urls[bab_translate("Change administration")] = $GLOBALS['babUrlScript']."?tg=delegusr";
			}
		}

	if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0)
		{
		$this->array_urls[bab_translate("Delegation")] = $GLOBALS['babUrlScript']."?tg=delegat";
		$this->array_urls[bab_translate("Sites")] = $GLOBALS['babUrlScript']."?tg=sites";
		}

	$this->array_urls[bab_translate("Users")] = $GLOBALS['babUrlScript']."?tg=users";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['groups'] == 'Y')
		$this->array_urls[bab_translate("Groups")] = $GLOBALS['babUrlScript']."?tg=groups";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['sections'] == 'Y')
		$this->array_urls[bab_translate("Sections")] = $GLOBALS['babUrlScript']."?tg=sections";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['faqs'] == 'Y')
		$this->array_urls[bab_translate("Faq")] = $GLOBALS['babUrlScript']."?tg=admfaqs";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['articles'] == 'Y')
		$this->array_urls[bab_translate("Articles")] = $GLOBALS['babUrlScript']."?tg=topcats";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['forums'] == 'Y')
		$this->array_urls[bab_translate("Forums")] = $GLOBALS['babUrlScript']."?tg=forums";
	if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 )
		$this->array_urls[bab_translate("Vacation")] = $GLOBALS['babUrlScript']."?tg=admvacs";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['calendars'] == 'Y')
		$this->array_urls[bab_translate("Calendar")] = $GLOBALS['babUrlScript']."?tg=admcals";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['mails'] == 'Y')
		$this->array_urls[bab_translate("Mail")] = $GLOBALS['babUrlScript']."?tg=maildoms&amp;userid=0&amp;bgrp=y";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['filemanager'] == 'Y')
		$this->array_urls[bab_translate("File manager")] = $GLOBALS['babUrlScript']."?tg=admfms";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['approbations'] == 'Y')
		$this->array_urls[bab_translate("Approbations")] = $GLOBALS['babUrlScript']."?tg=apprflow";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || $babBody->currentDGGroup['directories'] == 'Y')
		$this->array_urls[bab_translate("Directories")] = $GLOBALS['babUrlScript']."?tg=admdir";
	if( ($babBody->isSuperAdmin && $babBody->currentAdmGroup == 0) || (isset($babBody->currentDGGroup['orgchart']) && $babBody->currentDGGroup['orgchart'] == 'Y'))
		$this->array_urls[bab_translate("Charts")] = $GLOBALS['babUrlScript']."?tg=admocs";
	
	if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 )
		$this->array_urls[bab_translate("Add-ons")] = $GLOBALS['babUrlScript']."?tg=addons";
	if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 )
		$this->array_urls[bab_translate("Statistics")] = $GLOBALS['babUrlScript']."?tg=admstats";

	$engine = bab_searchEngineInfos();

	if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 && false !== $engine && $engine['indexes'] )
		$this->array_urls[bab_translate("Search indexes")] = $GLOBALS['babUrlScript']."?tg=index";

	$this->head = bab_translate("Currently you administer ");
	if( $babBody->currentAdmGroup == 0 )
		$this->head .= bab_translate("all site");
	else
		$this->head .= $babBody->currentDGGroup['name'];
	$this->foot = "";

	if( $babBody->isSuperAdmin && $babBody->currentAdmGroup == 0 )
		{
		foreach($babBody->babaddons as $row)
			{
			if($row['access'])
				{
				$addonpath = $GLOBALS['babAddonsPath'].$row['title'];
				if( is_dir($addonpath))
					{
					$arr = bab_getAddonsMenus($row, "getAdminSectionMenus");
					reset ($arr);
					while (list ($txt, $url) = each ($arr))
						{
						$this->addon_urls[$txt] = htmlentities($url);
						}
					}
				}
			}
		}
	ksort($this->array_urls);
	ksort($this->addon_urls);
	}

function addUrl()
	{
	static $i = 0;
	if( $i < count($this->array_urls))
		{
		$array_keys = array_keys($this->array_urls);
		$array_vals = array_values($this->array_urls);
		$this->val = $array_vals[$i];
		$this->key = $array_keys[$i];
		$i++;
		return true;
		}
	else
		return false;
	}

function addAddonUrl()
	{
	static $i = 0;
	if( $i < count($this->addon_urls))
		{
		$array_keys = array_keys($this->addon_urls);
		$array_vals = array_values($this->addon_urls);
		$this->val = $array_vals[$i];
		$this->key = $array_keys[$i];
		$i++;
		return true;
		}
	else
		return false;
	}

}

class babUserSection extends babSectionTemplate
{
var $array_urls = array();
var $addon_urls = array();
var $head;
var $foot;
var $key;
var $val;
var $newcount;
var $newtext;
var $newurl;
var $blogged;
var $aidetxt;
var $vacwaiting;

function babUserSection($close)
	{
	global $babDB, $babBody, $BAB_SESS_USERID;
	$this->babSectionTemplate("usersection.html", "template");
	$this->title = bab_translate("User's section");
	$this->vacwaiting = false;

	if( $close )
		{
		return;
		}

	if( !empty($GLOBALS['BAB_SESS_USER']))
		{
		$this->head = bab_translate("You are logged on as").":<br><center><b>";
		$this->head .= $GLOBALS['BAB_SESS_USER'];
		$this->login = bab_translate("You are logged on as");
		}
	else
		{
		$this->head = bab_translate("You are not yet logged in")."<br><center><b>";
		$this->login = bab_translate("You are not yet logged in");
		}
	$this->head .= "</b></center>";
	$this->foot = "";
	$this->aidetxt = bab_translate("Since your last connection:");

	$this->blogged = false;
	$faq = false;
	$req = "select id from ".BAB_FAQCAT_TBL."";
	$res = $babDB->db_query($req);
	while( $row = $babDB->db_fetch_array($res))
		{
		if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $row['id']))
			{
			$faq = true;
			break;
			}
		}
	
	$vac = false;
	$bemail = false;
	$idcal = 0;
	if( !empty($GLOBALS['BAB_SESS_USER']))
		{
		$this->blogged = true;
		$vacacc = bab_vacationsAccess();
		if( count($vacacc) > 0)
			{
			$this->vacwaiting = isset($vacacc['approver']) ? $vacacc['approver'] : '';
			$vac = true;
			}

		$bemail = bab_mailAccessLevel();
		if( $bemail == 1 || $bemail == 2)
			$bemail = true;
		}


	if( !empty($GLOBALS['BAB_SESS_USER']))
		{
		if( count($babBody->topsub) > 0  || count($babBody->topmod) > 0 )
			{
			$this->array_urls[bab_translate("Publication")] = $GLOBALS['babUrlScript']."?tg=artedit";
			}

		$babBody->waitapprobations = bab_isWaitingApprobations();
		if( $babBody->waitapprobations )
			{
			$this->array_urls[bab_translate("Approbations")] = $GLOBALS['babUrlScript']."?tg=approb";
			}
		}

	if( count($babBody->topman) > 0 || bab_isAccessValid(BAB_SITES_HPMAN_GROUPS_TBL, $babBody->babsite['id']))
		{
		$this->array_urls[bab_translate("Articles management")] = $GLOBALS['babUrlScript']."?tg=topman";
		}

	if( !empty($GLOBALS['BAB_SESS_USER']))
		{
		$this->array_urls[bab_translate("Summary")] = $GLOBALS['babUrlScript']."?tg=calview";
		$this->array_urls[bab_translate("Options")] = $GLOBALS['babUrlScript']."?tg=options";
		if( bab_notesAccess())
		$this->array_urls[bab_translate("Notes")] = $GLOBALS['babUrlScript']."?tg=notes";
		}

	if( $faq )
		{
		$this->array_urls[bab_translate("Faq")] = $GLOBALS['babUrlScript']."?tg=faq";
		}
	if( $vac )
		{
		$this->array_urls[bab_translate("Vacation")] = $GLOBALS['babUrlScript']."?tg=vacuser";
		}

	if( $babBody->icalendars->calendarAccess())
		{
		$babBody->calaccess = true;
		switch($babBody->icalendars->defaultview)
			{
			case BAB_CAL_VIEW_DAY: $view='calday';	break;
			case BAB_CAL_VIEW_WEEK: $view='calweek'; break;
			default: $view='calmonth'; break;
			}
		if( empty($babBody->icalendars->user_calendarids))
			{
			$babBody->icalendars->initializeCalendars();
			}
		$idcals = $babBody->icalendars->user_calendarids;
		$this->array_urls[bab_translate("Calendar")] = $GLOBALS['babUrlScript']."?tg=".$view."&amp;calid=".$idcals;
		}

	if( $bemail )
		{
		$this->array_urls[bab_translate("Mail")] = $GLOBALS['babUrlScript']."?tg=inbox";
		}
	if( !empty($GLOBALS['BAB_SESS_USER']) && bab_contactsAccess())
		{
		$this->array_urls[bab_translate("Contacts")] = $GLOBALS['babUrlScript']."?tg=contacts";
		}
	bab_fileManagerAccessLevel();
	if( $babBody->ustorage || (count($babBody->aclfm) > 0 && $babBody->aclfm['bshowfm']))
		{
		$this->array_urls[bab_translate("File manager")] = $GLOBALS['babUrlScript']."?tg=fileman";
		}

	$bdiradd = false;
	$res = $babDB->db_query("select id, id_group from ".BAB_DB_DIRECTORIES_TBL."");
	while( $row = $babDB->db_fetch_array($res))
		{
		if( $row['id_group'] != 0 )
			{
			list($bdiraccess) = $babDB->db_fetch_row($babDB->db_query("select directory from ".BAB_GROUPS_TBL." where id='".$row['id_group']."'"));
			}
		else
			$bdiraccess = 'Y';
		if($bdiraccess == 'Y' && bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $row['id']))
			{
			$bdiradd = true;
			break;
			}
		}

	if( $bdiradd === false )
		{
		$res = $babDB->db_query("select id from ".BAB_LDAP_DIRECTORIES_TBL."");
		while( $row = $babDB->db_fetch_array($res))
			{
			if(bab_isAccessValid(BAB_LDAPDIRVIEW_GROUPS_TBL, $row['id']))
				{
				$this->array_urls[bab_translate("Directories")] = $GLOBALS['babUrlScript']."?tg=directory";
				break;
				}
			}
		}

	if( $bdiradd )
		{
		$this->array_urls[bab_translate("Directories")] = $GLOBALS['babUrlScript']."?tg=directory";
		}

	if( count($babBody->ocids) > 0 )
		{
		$this->array_urls[bab_translate("Charts")] = $GLOBALS['babUrlScript']."?tg=charts";
		}

	if( bab_isAccessValid(BAB_STATSMAN_GROUPS_TBL, 1))
		{
		$this->array_urls[bab_translate("Statistics")] = $GLOBALS['babUrlScript']."?tg=stat";
		}

	foreach( $babBody->babaddons as $row ) 
		{
		if($row['access'])
			{
			$addonpath = $GLOBALS['babAddonsPath'].$row['title'];
			if( is_dir($addonpath))
				{
				$arr = bab_getAddonsMenus($row, 'getUserSectionMenus');
				reset ($arr);
				while (list ($txt, $url) = each ($arr))
					{
					$this->addon_urls[$txt] = htmlentities($url);
					}
				}
			}
		}
	ksort($this->array_urls);
	ksort($this->addon_urls);
	}

function addUrl()
	{
	static $i = 0;
	if( $i < count($this->array_urls))
		{
		$array_keys = array_keys($this->array_urls);
		$array_vals = array_values($this->array_urls);
		$this->url = $array_vals[$i];
		$this->text = $array_keys[$i];
		$i++;
		return true;
		}
	else
		return false;
	}

function addAddonUrl()
	{
	static $i = 0;
	if( $i < count($this->addon_urls))
		{
		$array_keys = array_keys($this->addon_urls);
		$array_vals = array_values($this->addon_urls);
		$this->url = $array_vals[$i];
		$this->text = $array_keys[$i];
		$i++;
		return true;
		}
	else
		return false;
	}

function getnextnew()
	{
	global $babBody;
	static $i = 0;
	if( $i < 4)
		{
		switch( $i )
			{
			case 0:
				$this->newcount = $babBody->get_newarticles();
				$this->newtext = bab_translate("Articles");
				$this->newurl = $GLOBALS['babUrlScript']."?tg=oml&amp;file=newarticles.html&amp;nbdays=0";
				break;
			case 1:
				$this->newcount = $babBody->get_newcomments();
				$this->newtext = bab_translate("Comments");
				$this->newurl = $GLOBALS['babUrlScript']."?tg=oml&amp;file=newcomments.html&amp;nbdays=0";
				break;
			case 2:
				$this->newcount = $babBody->get_newposts();
				$this->newtext = bab_translate("Replies");
				$this->newurl = $GLOBALS['babUrlScript']."?tg=oml&amp;file=newposts.html&amp;nbdays=0";
				break;
			case 3:
				$this->newcount = $babBody->get_newfiles();
				$this->newtext = bab_translate("Files");
				$this->newurl = $GLOBALS['babUrlScript']."?tg=oml&amp;file=newfiles.html&amp;nbdays=0";
				break;
			}
		$i++;
		return true;
		}
	else
		return false;
	}

}


class babTopcatSection extends babSectionTemplate
{
var $head;
var $foot;
var $url;
var $text;
var $arrid = array();
var $count;

function babTopcatSection($close)
	{
	global $babDB, $babBody;
	$this->babSectionTemplate("topcatsection.html", "template");
	$this->title = bab_translate("Topics categories");

	$res = $babDB->db_query("SELECT tct.id, tct.title FROM ".BAB_TOPCAT_ORDER_TBL." tot left join ".BAB_TOPICS_CATEGORIES_TBL." tct on tot.id_topcat=tct.id WHERE tot.id_parent='0' and tot.type='1' order by tot.ordering asc");
	$topcatview = $babBody->get_topcatview();
	while( $row = $babDB->db_fetch_array($res))
		{
		if( isset($topcatview[$row['id']]) )
			{
			if( $close )
				{
				$this->count = 1;
				return;
				}

			$this->arrid[] = array($row['id'], $row['title']);
			}
		}
	$this->head = bab_translate("List of different topics categories");
	$this->count = count($this->arrid);
	}

function topcatGetNext()
	{
	global $babDB, $babBody, $BAB_SESS_USERID;
	static $i = 0;
	if( $i < $this->count)
		{
		$this->text = $this->arrid[$i][1];
		$this->url = $GLOBALS['babUrlScript']."?tg=topusr&amp;cat=".$this->arrid[$i][0];
		$i++;
		return true;
		}
	else
		return false;
	}
}

class babTopicsSection extends babSectionTemplate
{
var $head;
var $foot;
var $url;
var $text;
var $arrid = array();
var $count;
var $newa;
var $newc;
var $waitingc;
var $waitinga;
var $waitingcimg;
var $waitingaimg;
var $bfooter;

function babTopicsSection($cat, $close)
	{
	global $babDB, $babBody;
	static $foot, $waitingc, $waitinga, $waitingaimg, $waitingcimg;
	$this->babSectionTemplate("topicssection.html", "template");
	$r = $babDB->db_fetch_array($babDB->db_query("select description, title, template from ".BAB_TOPICS_CATEGORIES_TBL." where id='".$cat."'"));
	$this->setTemplate($r['template']);
	$this->title = $r['title'];
	$this->head = $r['description'];

	$req = "select top.id topid, type, top.id_topcat id, lang, idsaart, idsacom from ".BAB_TOPCAT_ORDER_TBL." top LEFT JOIN ".BAB_TOPICS_TBL." t ON top.id_topcat=t.id and top.type=2 LEFT JOIN ".BAB_TOPICS_CATEGORIES_TBL." tc ON top.id_topcat=tc.id and top.type=1 where top.id_parent='".$cat."' order by top.ordering asc";
	$res = $babDB->db_query($req);
	$topcatview = $babBody->get_topcatview();
	while( $arr = $babDB->db_fetch_array($res))
		{
		if( $arr['type'] == 2 && isset($babBody->topview[$arr['id']]))
			{
			if( $close )
				{
				$this->count = 1;
				return;
				}

			$whatToFilter = $GLOBALS['babLangFilter']->getFilterAsInt();
			if(($arr['lang'] == '*') or ($arr['lang'] == ''))
				$whatToFilter = 0;
			else if((isset($GLOBALS['babApplyLanguageFilter']) && $GLOBALS['babApplyLanguageFilter'] == 'loose') and ( bab_isUserTopicManager($arr['id']) or bab_isCurrentUserApproverFlow($arr['idsaart']) or bab_isCurrentUserApproverFlow($arr['iddacom'])))
				$whatToFilter = 0;

			if(($whatToFilter == 0)	or ($whatToFilter == 1 and (substr($arr['lang'], 0, 2) == substr($GLOBALS['babLanguage'], 0, 2)))
				or ($whatToFilter == 2 and ($arr['lang'] == $GLOBALS['babLanguage'])))
				array_push($this->arrid, $arr['topid']);
			}
		else if( $arr['type'] == 1 && isset($topcatview[$arr['id']]))
			{
			if( $close )
				{
				$this->count = 1;
				return;
				}
			array_push($this->arrid, $arr['topid']);
			}
		}

	$this->bfooter = 0;
	if( empty($foot)) $foot = bab_translate("Topics with asterisk have waiting articles or comments ");
	if( empty($waitingc)) $waitingc = bab_translate("Waiting comments");
	if( empty($waitinga)) $waitinga = bab_translate("Waiting articles");
	if( empty($waitingaimg)) $waitingaimg = bab_printTemplate($this, "config.html", "babWaitingArticle");
	if( empty($waitingcimg)) $waitingcimg = bab_printTemplate($this, "config.html", "babWaitingComment");

	$this->foot = &$foot;
	$this->waitingc = &$waitingc;
	$this->waitinga = &$waitinga;
	$this->waitingaimg = &$waitingaimg;
	$this->waitingcimg = &$waitingcimg;

	$this->count = count($this->arrid);
	if($this->count > 0)
		{
		$inclause = implode(',', $this->arrid);
		$this->res = $babDB->db_query("SELECT tot.id, tot.id_topcat, tot.type, tt.id AS id_tt, tt.idsacom, tt.idsaart, tt.category, tct.id AS id_tct, tct.title FROM ".BAB_TOPCAT_ORDER_TBL." AS tot LEFT JOIN ".BAB_TOPICS_TBL." AS tt ON tt.id=tot.id_topcat LEFT JOIN ".BAB_TOPICS_CATEGORIES_TBL." AS tct ON tct.id=tot.id_topcat WHERE tot.id IN(".$inclause.") ORDER BY tot.ordering");
		$this->count = $babDB->db_num_rows($this->res);
		}
	} // function babTopicsSection

function topicsGetNext()
	{
	global $babDB, $babBody, $BAB_SESS_USERID, $babInstallPath;
	include_once $babInstallPath."utilit/afincl.php";
	static $i = 0;
	if( $i < $this->count)
		{
		$arr = $babDB->db_fetch_array($this->res, "fff".$i);

		if( $arr['type'] == 2 )
			{
			$this->newa = "";
			$this->newc = "";
			if( bab_isCurrentUserApproverFlow($arr['idsaart']))
				{
				$this->bfooter = 1;
				if( count(bab_getWaitingArticles($arr['id_tt'])) > 0 )
					{
					$this->newa = "a";
					}
				}

			if( bab_isCurrentUserApproverFlow($arr['idsacom']))
				{
				if( count(bab_getWaitingComments($arr['id_tt'])) > 0 )
					{
					$this->newc = "c";
					}
				}
			$this->text = $arr['category'];
			$this->url = $GLOBALS['babUrlScript']."?tg=articles&amp;topics=".$arr['id_tt'];
			}
		else if( $arr['type'] == 1 )
			{
			$this->newa = "";
			$this->newc = "";
			$this->text = $arr['title'];
			$this->url = $GLOBALS['babUrlScript']."?tg=topusr&amp;cat=".$arr['id_tct'];
			}
		$i++;
		return true;
		}
	else
		{
		$i = 0;
		return false;
		}
	}
}

class babForumsSection extends babSectionTemplate
{
var $head;
var $foot;
var $url;
var $text;
var $arrid = array();
var $count;
var $waiting;
var $bfooter;
var $waitingf;
var $waitingpostsimg;

function babForumsSection($close)
	{
	global $babDB, $babBody;
	static $waitingpostsimg;
	$this->babSectionTemplate("forumssection.html", "template");
	$this->title = bab_translate("Forums");

	$res = $babDB->db_query("select * from ".BAB_FORUMS_TBL." where active='Y' order by ordering asc");
	while( $row = $babDB->db_fetch_array($res))
		{
		if(bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $row['id']))
			{
			if( $close )
				{
				$this->count = 1;
				return;
				}
			array_push($this->arrid, $row);
			}
		}
	if( empty($waitingpostsimg)) $waitingpostsimg = bab_printTemplate($this, "config.html", "babWaitingPosts");
	$this->waitingpostsimg = &$waitingpostsimg;
	$this->head = bab_translate("List of different forums");
	$this->waitingf = bab_translate("Waiting posts");
	$this->bfooter = 0;
	$this->count = count($this->arrid);
	$this->foot = "";
	}

function forumsGetNext()
	{
	global $babDB, $babBody, $BAB_SESS_USERID;
	static $i = 0;
	if( $i < $this->count)
		{
		$this->arr = $this->arrid[$i];
		$this->text = $this->arr['name'];
		$this->url = $GLOBALS['babUrlScript']."?tg=threads&amp;forum=".$this->arr['id'];
		$this->waiting = "";
		if( bab_isAccessValid(BAB_FORUMSMAN_GROUPS_TBL, $this->arr['id']))
			{
			$this->bfooter = 1;
			$req = "select count(".BAB_POSTS_TBL.".id) as total from ".BAB_POSTS_TBL." join ".BAB_THREADS_TBL." where ".BAB_THREADS_TBL.".active='Y' and ".BAB_THREADS_TBL.".forum='".$this->arr['id'];
			$req .= "' and ".BAB_POSTS_TBL.".confirmed='N' and ".BAB_THREADS_TBL.".id=".BAB_POSTS_TBL.".id_thread";
			$res = $babDB->db_query($req);
			$ar = $babDB->db_fetch_array($res);
			if( $ar['total'] > 0)
				{
				$this->waiting = "*";
				}
			}
		$i++;
		return true;
		}
	else
		{
		return false;
		}
	}
}



class babMonthA  extends babSection
{
var $currentMonth;
var $currentYear;
var $curmonth;
var $curyear;
var $day3;
var $curmonthevents = array();
var $days;
var $daynumber;
var $now;
var $w;
var $event;
var $dayurl;
var $babCalendarStartDay;


function babMonthA($month = "", $year = "")
	{
	global $babDB,$babBody, $BAB_SESS_USERID;

	$this->babSection("","");

	if(empty($month))
		$this->currentMonth = Date("n");
	else
		{
		$this->currentMonth = $month;
		}
	
	if(empty($year))
		{
		$this->currentYear = Date("Y");
		}
	else
		{
		$this->currentYear = $year;
		}

	$this->babCalendarStartDay = $babBody->icalendars->startday;
	$this->curDay = 0;
	}

function printout()
	{
	global $babBody, $babDB, $babMonths, $BAB_SESS_USERID;
	$this->curmonth = $babMonths[date("n", mktime(0,0,0,$this->currentMonth,1,$this->currentYear))];
	$this->curyear = $this->currentYear;
	$this->days = date("t", mktime(0,0,0,$this->currentMonth,1,$this->currentYear));
	$this->daynumber = date("w", mktime(0,0,0,$this->currentMonth,1,$this->currentYear));
	$this->now = date("j");
	$this->w = 0;
	$todaymonth = date("n");
	$todayyear = date("Y");
	
	$icalendars = $babBody->icalendars;
	$icalendars->initializeCalendars();
	
	$this->idcals = array();

	if (!empty($icalendars->id_percal))
		$this->idcals[] = $icalendars->id_percal;


	foreach($icalendars->pubcal as $id => $pubcal)
		{
		if ($pubcal['view'])
			$this->idcals[] = $id;
		}

	$mktime = mktime(0,0,0,$this->currentMonth, 1,$this->currentYear);
	$daymin = date('Y-m-d', $mktime);
	$mktime = mktime(0,0,0,$this->currentMonth, $this->days,$this->currentYear);
	$daymax = date('Y-m-d', $mktime);
	if(count($this->idcals) > 0)
		{
		$currmonthevents = array();
		$res2 = $babDB->db_query('SELECT c.id_cal, IF(EXTRACT(MONTH FROM e.start_date)<'.$this->currentMonth.', 1, DAYOFMONTH(e.start_date)) AS start_day, IF(EXTRACT(MONTH FROM e.end_date)>'.$this->currentMonth.', '.$this->days.', DAYOFMONTH(e.end_date)) AS end_day FROM '.BAB_CAL_EVENTS_TBL.' e,'.BAB_CAL_EVENTS_OWNERS_TBL.' c  WHERE e.id = c.id_event AND c.id_cal IN ('.implode(',', $this->idcals).') AND ((end_date>=\''.$daymin.'\' AND end_date<=\''.$daymax.'\') OR (start_date<=\''.$daymax.'\' AND start_date>=\''.$daymin.'\'))');
		while($event = $babDB->db_fetch_array($res2))
			{
			for($day = $event['start_day'] ; $day<=$event['end_day']; $day++)
				{
					$currmonthevents[$day][] = $event['id_cal'];
				}
			}
		$this->currmonthevents = $currmonthevents;
		}

	$this->htmlid = 'montha';

	if( !file_exists( 'skins/'.$GLOBALS['babSkin'].'/templates/montha.html' ) )
		{
		if (!$this->close)
			$this->content = bab_printTemplate($this,'insections.html', 'montha');
		$this->title = $this->curmonth.' '.$this->curyear;
		return bab_printTemplate($this,'sectiontemplate.html', 'default');
		}

	return bab_printTemplate($this,"montha.html", "");
	}

	function getnextday3()
		{
		global $babDays;
		static $i = 0;
		if( $i < 7)
			{
			$a = $i + $this->babCalendarStartDay;
			if( $a > 6)
				$a -=  7;
			$this->day3 = substr($babDays[$a], 0, 1);
			$i++;
			return true;
			}
		else
			return false;
		}

	function getnextweek()
		{
		if( $this->w < 7 && $this->curDay < $this->days)
			{
			$this->w++;
			return true;
			}
		else
			{
			return false;
			}
		}

	function getnextday()
		{
		global $babDB;
		static $d = 0;
		static $total = 0;
		if( $d < 7)
			{
			$this->bgcolor = 0;
			$this->event = 0;

			$a = $this->daynumber - $this->babCalendarStartDay;
			if( $a < 0)
				$a += 7;


			if( ($this->w == 1 &&  $d < $a) || $total >= $this->days )
				{
				$this->day = "&nbsp;";
				}
			else
				{
				$total++;
				$this->curDay++;

				if( $total > $this->days)
					{
					return false;
					}

				$this->day = $total;
				if( count($this->idcals) > 0 )
					{
						if(isset($this->currmonthevents[$this->day]) && !empty($this->currmonthevents[$this->day]))
							{
								$idcals = implode(',', array_unique($this->currmonthevents[$this->day]));
								if( !empty($idcals))
									{
										$this->event = 1;
										$this->dayurl = $GLOBALS['babUrlScript']."?tg=calday&amp;calid=".$idcals."&amp;date=".$this->currentYear.",".$this->currentMonth.",".$total;
										$this->day = $total;
									}
							}
					}
				if( $total == $this->now && date("n", mktime(0,0,0,$this->currentMonth,1,$this->currentYear)) == date("n") && $this->currentYear == date("Y"))
					{
					$this->bgcolor = 1;
					}

				}

			if( $total > $this->days)
				{
				return false;
				}
				
			$d++;
			return true;
			}
		else
			{
			$d = 0;
			return false;
			}
		}
}


?>