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
include_once $babInstallPath."utilit/topincl.php";
include_once $babInstallPath."utilit/forumincl.php";
include_once $babInstallPath."utilit/fileincl.php";
include_once $babInstallPath."utilit/calincl.php";
include_once $babInstallPath."utilit/dirincl.php";
include_once $babInstallPath."utilit/searchincl.php";

$babLimit = 5;
$navbaritems = 10;
define ("FIELDS_TO_SEARCH", 3);

function highlightWord( $w, $text)
	{
	return bab_highlightWord( $w, $text);
	}


function put_text($txt, $limit = 60, $limitmot = 30 )
	{
	if (strlen($txt) > $limit)
		$out = substr(strip_tags($txt),0,$limit)."...";
	else
		$out = strip_tags($txt);
	$arr = explode(" ",$out);
	foreach($arr as $key => $mot)
		$arr[$key] = substr($mot,0,$limitmot);
	$txt = implode(" ",$arr);
	bab_replace_ref($txt);
	return $txt;
	}


function finder($req2,$tablename,$option = "OR",$req1="")
	{
	return bab_sql_finder($req2,$tablename,$option,$req1);
	}

function returnCategoriesHierarchy($topics)
	{
	$article_path = new categoriesHierarchy($topics, -1, $GLOBALS['babUrlScript']."?tg=topusr");
	$out = bab_printTemplate($article_path,"search.html", "article_path");
	return $out;
	}



class bab_addonsSearch
	{
	var $tabSearchAddons = array();
	var $tabLinkAddons = array();
	var $titleAddons = array();

	function bab_addonsSearch()
		{
		$db = &$GLOBALS['babDB'];
		$res = $db->db_query("select id,title from ".BAB_ADDONS_TBL." where enabled='Y'");
		while (list($id,$title) = $db->db_fetch_array($res))
			{
			
			if (bab_isAccessValid(BAB_ADDONS_GROUPS_TBL, $id) && is_file($GLOBALS['babAddonsPath'].$title."/init.php"))
				{
				$func_infos = $title."_searchinfos";
				$func_results = $title."_searchresults";

				$this->titleAddons[$id] = $title;
				$this->defineAddonGlobals($id);

				require_once($GLOBALS['babAddonsPath'].$title."/init.php");
				if (function_exists($func_infos))
					{
					
					$data = $func_infos();
					if (is_array($data))
						list($text,$link) = $data;
					else
						$text = $data;

					if (is_string($text))
						{
						$text = htmlentities($text);
						if (function_exists($func_results) && !isset($link))
							{
							$this->func_results[$id] = $func_results;
							$this->tabSearchAddons[$id] = $text;
							}
						if (isset($link))
							{
							$this->tabLinkAddons[$id] = $text;
							$this->querystring[$id] = $link;
							}
						}
					}
				}
			}
		}

	function defineAddonGlobals($id)
		{
		$title = $this->titleAddons[$id];
		$GLOBALS['babAddonFolder'] = $title;
		$GLOBALS['babAddonTarget'] = "addon/".$id;
		$GLOBALS['babAddonUrl'] = $GLOBALS['babUrlScript']."?tg=addon/".$id."/";
		$GLOBALS['babAddonPhpPath'] = $GLOBALS['babInstallPath']."addons/".$title."/";
		$GLOBALS['babAddonHtmlPath'] = "addons/".$title."/";
		$GLOBALS['babAddonUpload'] = $GLOBALS['babUploadPath']."/addons/".$title."/";
		}

	function getmenuarray()
		{

		foreach ($this->tabSearchAddons as $key => $value)
			$out['as-'.$key] = $value;
		foreach ($this->tabLinkAddons as $key => $value)
			$out['al-'.$key] = $value;
		return isset($out) ? $out : array();
		}

	function getsearcharray($item)
		{
		if (empty($item))
			{
			return $this->tabSearchAddons;
			}
		elseif (substr($item,0,3) == 'as-')
			{
			$id = substr($item,3);
			if (!empty($id) && is_numeric($id) && isset($this->tabSearchAddons[$id]))
				return array($id => $this->tabSearchAddons[$id]);	
			}
		}

	function setSearchParam($q1, $q2, $option, $pos, $nb_result)
		{
		$this->q1 = $q1;
		$this->q2 = $q2;
		$this->option = $option;
		$this->pos = $pos;
		$this->nb_result = $nb_result;
		}


	function callSearchFunction($id)
		{
		$this->defineAddonGlobals($id);
		$func = $this->func_results[$id];
		return $func($this->q1, $this->q2, $this->option, $this->pos, $this->nb_result);
		}
	}


function searchKeyword($item , $option = "OR")
	{
	global $babBody;

	class tempb
		{
		var $search;
		var $all;
		var $in;
		var $update;
		var $itemvalue;
		var $itemname;
		var $arr = array();
		var $sfaq;
		var $sart;
		var $snot;
		var $sfor;
		var $sfil;
		var $what;
		var $what2;
		var $dirarr = array();
		var $search;

		function tempb($item ,$option )
			{
			$this->db = $GLOBALS['babDB'];
			global  $babBody,$babSearchItems;
			$this->item = $item;
			$this->fields = $GLOBALS['HTTP_POST_VARS'];
			$this->search = bab_translate("Search");
			$this->all = bab_translate("All");
			$this->in = bab_translate("in");
			if (!isset($this->fields['what'])) $this->fields['what'] = '';
			if (!isset($this->fields['what2'])) $this->fields['what2'] = '';
			$this->what = stripslashes($this->fields['what']);
			$this->fields['what'] = stripslashes($this->fields['what']);
			$this->fields['what2'] = stripslashes($this->fields['what2']);
			$this->reset = bab_translate("Reset");
			$this->Topic = bab_translate("Topic");
			$this->Author = bab_translate("Author");
			$this->Date = bab_translate("Date");
			$this->option_or = bab_translate("or");
			$this->option_and = bab_translate("and");
			$this->option_not = bab_translate("exclude");
			$this->switch = bab_translate("Advanced search"); 

			$this->before = bab_translate("before date");
			$this->after = bab_translate("after date");
			$this->beforelink = $GLOBALS['babUrlScript']."?tg=month&callback=beforeJs&ymin=100&ymax=10&month=".date('m')."&year=".date('Y');;
			$this->afterlink = $GLOBALS['babUrlScript']."?tg=month&callback=afterJs&ymin=100&ymax=10&month=".date('m')."&year=".date('Y');;

			$this->or_selected = "";
			$this->and_selected = "";
			$this->not_selected = "";

			switch ($option)
				{
				case "OR": $this->or_selected = "selected"; break;
				case "AND": $this->and_selected = "selected"; break;
				case "NOT": $this->not_selected = "selected"; break;
				}

			$this->addons = new bab_addonsSearch;

			$this->el_to_init = Array ( 'a_author','a_dd','a_mm','a_yyyy','before','after','before_display' ,'after_display','before_memo','after_memo','what2','what','advenced');

			for ($i =0 ;$i < FIELDS_TO_SEARCH ; $i++)
				$this->el_to_init[] = "dirfield_".$i;

			
			if ((isset($this->fields['idx']) && $this->fields['idx'] != "find")||((!isset($this->fields['a_mm']))&&(!isset($this->fields['g_mm'])))) 
				{
				foreach($this->el_to_init as  $value)
					if (! isset($this->fields[$value])) $this->fields[$value] = "";
				}

			$req = "select id,category from ".BAB_TOPICS_TBL." order by category";
			$this->restopics = $this->db->db_query($req);
			$this->counttopics = $this->db->db_num_rows($this->restopics);

			$req = "select D.id, D.name, D.id_group id_group from ".BAB_DB_DIRECTORIES_TBL." D,".BAB_GROUPS_TBL." G where D.id_group = '0' OR (D.id_group = G.id AND G.directory='Y') order by D.name";
			$this->resdirs = $this->db->db_query($req);
			$i = 0;
			while ($arr = $this->db->db_fetch_array($this->resdirs) )
				{
				$prevname = $i >= 1 && is_array($this->dirarr[$i-1]) ? $this->dirarr[$i-1]['name'] : '';
				if ($arr['name'] != $prevname && bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $arr['id']))
					{
					$this->dirarr[$i] = $arr;
					$i++;
					}
				}
			$this->countdirs = $i;

			$req = "select name,description from ".BAB_DBDIR_FIELDS_TBL." WHERE name != 'jpegphoto' order by description";
			$this->resfields = $this->db->db_query($req);
			$this->countfields = $this->db->db_num_rows($this->resfields);
			$i = 0;
			while ($arr = $this->db->db_fetch_array($this->resfields))
				{
				$this->tbln[$i] = $arr['name'];
				$this->tbld[$i] = translateDirectoryField($arr['description']);
				$i++;
				}
			$fliped = array_flip($this->tbld);
			ksort($fliped);
			$i = 0;
			foreach($fliped as $value => $key)
				{
				$this->tblfields[$i]['name'] = $this->tbln[$key];
				$this->tblfields[$i]['description'] = $value;
				$i++;
				}

			$this->rescal = array_merge(getAvailableUsersCalendars(),getAvailableGroupsCalendars(),getAvailableResourcesCalendars());
			$this->countcal = count($this->rescal);

			$this->acces['a'] = true;
			$this->acces['b'] = false;
			$this->acces['c'] = false;
			$this->acces['d'] = false;
			$this->acces['e'] = false;
			$this->acces['f'] = $GLOBALS['BAB_SESS_LOGGED'] && bab_contactsAccess();
			$this->acces['g'] = $this->countdirs;
			$this->acces['h'] = bab_calendarAccess();

			$res = $this->db->db_query("select id from ".BAB_FORUMS_TBL);
			while( $row = $this->db->db_fetch_array($res))
				{
				if(bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $row['id']))
					{
					$this->acces['b'] = true;
					break;
					}
				}

			$res = $this->db->db_query("select id from ".BAB_FAQCAT_TBL);
			while( $row = $this->db->db_fetch_array($res))
				{
				if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $row['id']))
					{
					$this->acces['c'] = true;
					break;
					}
				}
			$this->acces['d'] = $GLOBALS['BAB_SESS_LOGGED'] && bab_notesAccess();
			bab_fileManagerAccessLevel();
			if( $babBody->ustorage || count($babBody->aclfm) > 0 )
				{
				$this->acces['e'] = true;
				}

			foreach ($babSearchItems as $key => $value)
				{
				if ($this->acces[$key])
					$this->searchItems[$key] = $value;
				}

			$menuarray = $this->addons->getmenuarray();
			$this->searchItems = array_merge($this->searchItems,$menuarray);
			}

		function getnextitem()
			{
			$flag = list($this->itemvalue,$this->itemname) = each($this->searchItems);
			$this->selected = $this->itemvalue == $this->item ? 'selected' : '';
			return $flag;
			}

		function getnexttopic() 
			{
			static $i = 0;
			if( $i < $this->counttopics)
				{
				$arr = $this->db->db_fetch_array($this->restopics);
				$this->topicid = $arr['id'];
				$this->topictitle = put_text($arr['category'],30);
				$this->selected = bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $arr['id']);
				$i++;
				return true;
				}
			else
				return false;
			}
		
		function getnextdir() 
			{
			static $l = 0;
			if( $l < $this->countdirs)
				{
                $arr = $this->dirarr[$l];
				$this->topicid = $arr['id'];
				$this->topictitle = put_text($arr['name'],30);

				$req = "select df.id, dfd.name from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." dfd left join ".BAB_DBDIR_FIELDSEXTRA_TBL." df ON df.id_directory=dfd.id_directory and df.id_field = ( ".BAB_DBDIR_MAX_COMMON_FIELDS." + dfd.id ) where df.id_directory='".(($arr['id_group']==0) ? $arr['id'] : 0)."'";
				$res = $this->db->db_query($req);

				$lk = 0;
				while ($arr = $this->db->db_fetch_array($res))
					{
					$tblxn[$lk] = $arr['id'];
					$tblxd[$lk] = translateDirectoryField($arr['name']);
					$lk++;
					}

				$this->tblxfields = array();
				$this->countfieldsfromdir = 0;
				if( $lk > 0 )
					{
					$fliped = array_flip($tblxd);
					ksort($fliped);
					$lk= 0;
					foreach($fliped as $value => $key)
						{
						$this->tblxfields[$lk]['name'] = $tblxn[$key];
						$this->tblxfields[$lk]['description'] = $value;
						$lk++;
						}
					$this->countfieldsfromdir = count($this->tblxfields);
					}
				
				$l++;
				return true;
				}
			else
				{
				$l = 0;
				return false;
				}
			}
		
		function selectname() 
			{
			static $i = 0;
			if( $i < $this->countfields)
				{
				$this->selindex = $i;
                $arr = $this->tblfields[$i];
				$this->name = $arr['name'];
				$this->description = $arr['description'];
				$this->descriptionJs = addslashes($arr['description']);
				$this->fieldvalue = isset($this->fields[$arr['name']])?$this->fields[$arr['name']]:'';
				if ( isset($this->fields['dirselect_'.$this->j]) && $this->fields['dirselect_'.$this->j] == $arr['name'])
					$this->selected = "selected";
				else	
					$this->selected = false;
				$i++;
				return true;
				}
			else
				{$i = 0;
				return false;}
			}

		function getnextfield()
			{
			static $k = 0;
			if( $k < $this->countfieldsfromdir)
				{
				$this->fieldnamefromdir = "babdirf".$this->tblxfields[$k]['name'] ;
				$this->name = "babdirf".$this->tblxfields[$k]['name'];
				$this->description = $this->tblxfields[$k]['description'];
				$this->fieldindex = $k;
				if ( isset($this->fields['dirselect_'.$this->j]) && $this->fields['dirselect_'.$this->j] == $this->name)
					$this->selected = "selected";
				else	
					$this->selected = false;
				$k++;
				return true;
				}
			else
				{
				$k = 0;
				return false;}
			}

		function getnextfieldtosearch() 
			{
			static $j = 0;
			if( $j < FIELDS_TO_SEARCH)
				{
				$this->fieldcounter = $j;
				$this->j = $j;
				$j++;
				return true;
				}
			else
				{
				$j = 0;
				return false;}
			}

		function getnextcal() 
			{
			static $i = 0;
			if( $i < $this->countcal)
				{
				$this->calendarid = $this->rescal[$i]['idcal'];
				$this->caltitle = put_text($this->rescal[$i]['name'],30);
				$i++;
				return true;
				}
			else
				return false;
			}
		}
	$tempb = new tempb($item ,$option);
	$babBody->babecho(	bab_printTemplate($tempb,"search.html", "search"));
	}

class temp_nav
		{
		function temp_nav($babLimit,$nbrows,$navitem,$navpos)
			{
			global $navbaritems;
			$this->navbaritems = $navbaritems;
			$this->babLimit = $babLimit;
			$this->nbrows = $nbrows;
			$this->navitem = $navitem;
			$this->navpos = $navpos;
			if (($navpos+$babLimit) > $nbrows ) $this->navposend = $navpos+($nbrows - $navpos);
			else $this->navposend = $navpos+$babLimit;
			$this->results = bab_translate("Results");
			$this->pages = bab_translate("Pages");
			$this->to = bab_translate("To");
			$this->from = bab_translate("From");
			$this->countpages = ceil($nbrows/$babLimit);
			
			
			if ( $navpos <= 0 ) $this->previous = false;
			else 
				{ 
				$this->previous = bab_translate("Previous");
				$this->urlprev ="javascript:navsearch(".($this->navpos - $this->babLimit).",'".$this->navitem."')"; 
				}

			if ( $navpos + $babLimit >= $nbrows ) $this->next = false;
			else 
				{
				$this->next = bab_translate("Next");
				$this->urlnext = "javascript:navsearch(".($this->navpos + $this->babLimit).",'".$this->navitem."')"; 
				}
			
			$this->count = ceil($nbrows/$babLimit);
			if ( $this->count > $this->navbaritems )
				$this->count = $this->navbaritems;

			if ((ceil($this->navpos/$babLimit) - ($this->navbaritems/2)) < 0 ) $this->start = 0;
			else $this->start = ceil($this->navpos/$babLimit) - ($this->navbaritems/2);
			$this->page = $this->start + 1;
			}
		function getnext()
			{
			static $i = 0;
			if( $i < $this->count && $this->page < $this->countpages)
				{
				$this->page = $this->start + $i + 1;
				$this->urlpage = "javascript:navsearch(".$this->babLimit*($this->start+$i).",'".$this->navitem."')"; 
				if ( (ceil($this->navpos/$this->babLimit) == $this->start + $i) ) 
	 				$this->selected = true;
				else
					$this->selected = false;
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

function navbar($babLimit,$nbrows,$navitem,$navpos)
	{
	$temp = new temp_nav($babLimit,$nbrows,$navitem,$navpos);
	return bab_printTemplate($temp,"search.html","navbar");
	}

function startSearch( $item, $what, $order, $option ,$navitem, $navpos )
	{
	global $babBody;
	
	class temp
		{
		var $what;
		var $search;
		var $db;
		var $arttitle;
		var $comtitle;
		var $fortitle;
		var $faqtitle;
		var $nottitle;
		var $filtitle;
		var $contitle;
		var $dirtitle;
		var $countart;
		var $countfor;
		var $countnot;
		var $countfaq;
		var $countcom;
		var $countfil;
		var $countcon;
		var $countdir;
		var $counttot;
		var $nbresult = 0;

		function temp( $item, $what, $order, $option ,$navitem ,$navpos )
			{
			global $BAB_SESS_USERID, $babLimit, $babBody;
			
			$this->db = $GLOBALS['babDB'];
			$this->search = bab_translate("Search");
			$this->arttitle = bab_translate("Articles");
			$this->comtitle = bab_translate("Comments");
			$this->fortitle = bab_translate("Forums");
			$this->faqtitle = bab_translate("Faq");
			$this->nottitle = bab_translate("Notes");
			$this->filtitle = bab_translate("Files");
			$this->contitle = bab_translate("Contacts");
			$this->dirtitle = bab_translate("Directories");
			$this->agebigtitle = bab_translate("Calendar");
			$this->total = bab_translate("Number of results in research");
			$this->popup = bab_translate("Popup");
			$this->lastname= bab_translate("Lastname");
			$this->firstname= bab_translate("Firstname");
			$this->email = bab_translate("Email");
			$this->company= bab_translate("Company");
			$this->filename=bab_translate("Filename");
			$this->description=bab_translate("Description");
			$this->folder=bab_translate("Folder");
			$this->author=bab_translate("Author");
			$this->datem=bab_translate("Last update");
			$this->directory=bab_translate("Site directory");
			$this->department=bab_translate("Department");

			$this->fields = $GLOBALS['HTTP_POST_VARS'];

			if( !bab_isMagicQuotesGpcOn())
				{
				$this->like2 = addslashes($what);
				$this->like = addslashes($this->fields['what2']);
				}
			else
				{
				$this->like2 = $what;
				$this->like = $this->fields['what2'];
				}

			
			$this->what = urlencode(addslashes($what." ".$this->fields['what2']));
			$this->countart = 0;
			$this->countfor = 0;
			$this->countnot = 0;
			$this->countfaq = 0;
			$this->countcom = 0;
			$this->countfil = 0;
			$this->countcon = 0;
			$this->countdir = 0;
			$this->countage = 0;
			$this->counttot = false;
			$this->navitem = $navitem;
			$this->navpos = $navpos;
			

// ---------------------------------------- SEARCH ARTICLES AND ARTICLES COMMENTS ---------
			if( empty($item) || $item == "a")
				{
				$req = "create temporary table artresults SELECT a.id, a.id_topic, a.archive, a.title, a.head, a.body, a.restriction, T.category topic, concat( U.lastname, ' ', U.firstname ) author,a.id_author, 'yyyy-mm-dd' date from ".BAB_ARTICLES_TBL." a, ".BAB_TOPICS_TBL." T, ".BAB_USERS_TBL." U where a.id_topic = T.id AND a.id_author = U.id AND 0";
				$this->db->db_query($req);
				$req = "alter table artresults add unique (id)";
				$this->db->db_query($req);

				$req = "create temporary table comresults select C.id, C.id_article, C.id_topic, C.subject,C.message, UNIX_TIMESTAMP(C.date) date, name,email, a.title arttitle, a.body,a.restriction, T.category topic from ".BAB_COMMENTS_TBL." C, ".BAB_ARTICLES_TBL." a, ".BAB_TOPICS_TBL." T where C.id_article=a.id and a.id_topic = T.id and 0";
				$this->db->db_query($req);
				$req = "alter table comresults add unique (id)";
				$this->db->db_query($req); 
				
				$crit_art = "";
				$crit_com = "";
				if (isset($this->fields['a_author']) && trim($this->fields['a_author']) != "")
					{
					$crit_art = " and (".finder($this->fields['a_author'],"concat(U.lastname,U.firstname)",$option).")";
					$crit_com = " and (".finder($this->fields['a_author'],"name",$option).")";
					}

				if (isset($this->fields['a_topic']) && trim($this->fields['a_topic']) != "")
					{
					$crit_art .= " and id_topic = ".$this->fields['a_topic'];
					$crit_com .= " and C.id_topic = ".$this->fields['a_topic'];
					}

				if (isset($this->fields['after']) && trim($this->fields['after']) != "")
					{
					$crit_art .= " and a.date >= '".$this->fields['after']."'";
					$crit_com .= " and C.date >= '".$this->fields['after']."'";
					}
				if (isset($this->fields['before']) && trim($this->fields['before']) != "")
					{
					$crit_art .= " and a.date <= '".$this->fields['before']."'";
					$crit_com .= " and C.date <= '".$this->fields['before']."'";
					}

				$inart = (is_array($babBody->topview) && count($babBody->topview) > 0 ) ? "and id_topic in (".implode($babBody->topview,",").")" : "and id_topic ='0'";
				$incom = (is_array($babBody->topview) && count($babBody->topview) > 0 ) ? "and C.id_topic in (".implode($babBody->topview,",").")" : "and C.id_topic ='0'";

				if (!empty($inart))
					{
					if ($this->like || $this->like2)
						$reqsup = "and (".finder($this->like,"title",$option,$this->like2)." or ".finder($this->like,"head",$option,$this->like2)." or ".finder($this->like,"body",$option,$this->like2).")";
					
					$req = "insert into artresults SELECT a.id, a.id_topic, a.archive, a.title title,a.head, LEFT(a.body,100) body, a.restriction, T.category topic, concat( U.lastname, ' ', U.firstname ) author,a.id_author, UNIX_TIMESTAMP(a.date) date  from ".BAB_ARTICLES_TBL." a, ".BAB_TOPICS_TBL." T, ".BAB_USERS_TBL." U where a.id_topic = T.id AND a.id_author = U.id ".$reqsup." ".$inart." ".$crit_art." order by $order ";
					$this->db->db_query($req);

					$res = $this->db->db_query("select id, restriction from artresults where restriction!=''");
					while( $rr = $this->db->db_fetch_array($res))
						{
						if( !bab_articleAccessByRestriction($rr['restriction']))
							$this->db->db_query("delete from artresults where id='".$rr['id']."'");
						}
					}

				if (!empty($incom))
					{
					if ($this->like || $this->like2)
						$reqsup = "and (".finder($this->like,"subject",$option,$this->like2)." or ".finder($this->like,"message",$option,$this->like2).")";

					$req = "insert into comresults select C.id, C.id_article, C.id_topic, C.subject,C.message, UNIX_TIMESTAMP(C.date) date, name author,email,  a.title arttitle,LEFT(a.body,100) body, a.restriction, T.category topic  from ".BAB_COMMENTS_TBL." C, ".BAB_ARTICLES_TBL." a, ".BAB_TOPICS_TBL." T where C.id_article=a.id and a.id_topic = T.id ".$reqsup." and C.confirmed='Y' ".$incom." ".$crit_com." order by $order ";

					$this->db->db_query($req);
					$res = $this->db->db_query("select id, restriction from comresults where restriction!=''");
					while( $rr = $this->db->db_fetch_array($res))
						{
						if( !bab_articleAccessByRestriction($rr['restriction']))
							$this->db->db_query("delete from comresults where id='".$rr['id']."'");
						}
					}

				$req = "select count(*) from artresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);
				$this->nbresult += $nbrows;

				if ($navitem != "a") $navpos=0;
				$req = "select * from artresults limit ".$navpos.", ".$babLimit;

				$this->resart = $this->db->db_query($req);
				$this->countart = $this->db->db_num_rows($this->resart);
				if( !$this->counttot && $this->countart > 0 )
					$this->counttot = true;
				$this->navbar_a = navbar($babLimit,$nbrows,"a",$navpos);

				$navpos=$this->navpos;
				if ($navitem != "ac") $navpos=0;
				$req = "select count(*) from comresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);

				$this->navbar_ac = navbar($babLimit,$nbrows,"ac",$navpos);
				$req = "select * from comresults limit ".$navpos.", ".$babLimit;

				$this->rescom = $this->db->db_query($req);
				$this->countcom = $this->db->db_num_rows($this->rescom);
				if( !$this->counttot && $this->countcom > 0 )
					$this->counttot = true;
				}
				if (!isset($nbrows)) $nbrows = 0;
				$this->nbresult += $nbrows;

			// ------------------------------------------------- POSTS
			if( empty($item) || $item == "b")
				{
				$req = "create temporary table forresults select id, id_thread, subject topic, id id_topic, subject title,message, author, 'dd-mm-yyyy' date from ".BAB_POSTS_TBL." where 0";
				$this->db->db_query($req);
				$req = "alter table forresults add unique (id)";
				$this->db->db_query($req);
				$req = "select id from ".BAB_FORUMS_TBL."";
				$res = $this->db->db_query($req);
				$idthreads = "";
				while( $row = $this->db->db_fetch_array($res))
					{
					if(bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $row['id']))
						{
						$req = "select id from ".BAB_THREADS_TBL." where forum='".$row['id']."'";
						$res2 = $this->db->db_query($req);
						while( $r = $this->db->db_fetch_array($res2))
							{
							$idthreads .= $r['id'].",";
							}
						}
					}
				$temp1 = finder($this->like,"b.subject",$option,$this->like2);
				$temp2 = finder($this->like,"b.message",$option,$this->like2);
				if ($temp1 != "" && $temp2 != "")
					$plus = "( ".$temp1." or ".$temp2.") and";
				else $plus = "";
				if ($idthreads != "") { 
					$req = "insert into forresults select b.id, b.id_thread, F.name topic, F.id id_topic, b.subject title,b.message, author, UNIX_TIMESTAMP(b.date) date from ".BAB_POSTS_TBL." b, ".BAB_THREADS_TBL." T, ".BAB_FORUMS_TBL." F where b.id_thread=T.id and T.forum=F.id and ".$plus." b.confirmed='Y' and b.id_thread IN (".substr($idthreads,0,-1).") order by ".$order;
					$this->db->db_query($req);
				}

				$req = "select count(*) from forresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);
				$navpos = $this->navpos;
				if ($navitem != "b") $navpos = 0;
				$this->navbar_b = navbar($babLimit,$nbrows,"b",$navpos);
			
				$req = "select * from forresults limit ".$navpos.", ".$babLimit;
				$this->resfor = $this->db->db_query($req);
				$this->countfor = $this->db->db_num_rows($this->resfor);
				if( !$this->counttot && $this->countfor > 0 )
					$this->counttot = true;
				}
				$this->nbresult += $nbrows;

			// ---------------------------------------------- FAQ
			if( empty($item) || $item == "c")
				{
				$req = "create temporary table faqresults select id, idcat, question title, response, question topic,question id_manager, question author from ".BAB_FAQQR_TBL." where 0";
				$this->db->db_query($req);
				$req = "alter table faqresults add unique (id)";
				$this->db->db_query($req);
				$req = "select id from ".BAB_FAQCAT_TBL."";
				$res = $this->db->db_query($req);

				$idcat = "";
				while( $row = $this->db->db_fetch_array($res))
					{
					if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $row['id']))
						{
						$idcat .= $row['id'].",";
						}
					}
				$temp1 = finder($this->like,"question",$option,$this->like2);
				$temp2 = finder($this->like,"response",$option,$this->like2);
				if ($temp1 != "" && $temp2 != "")
					$plus = "( ".$temp1." or ".$temp2.") and";
				else $plus = "";

				if ($idcat != "") {
					$req = "insert into faqresults select T.id, idcat, question title, response, category topic,C.id_manager, concat( U.lastname, ' ', U.firstname ) author from ".BAB_FAQQR_TBL." T, ".BAB_FAQCAT_TBL." C, ".BAB_USERS_TBL." U where idcat=C.id and C.id_manager=U.id and ".$plus." idcat in (".substr($idcat,0,-1).") order by ".$order;
					$this->db->db_query($req);
				}


				$req = "select count(*) from faqresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);
				$navpos = $this->navpos;
				if ($navitem != "c") $navpos = 0;
				$this->navbar_c = navbar($babLimit,$nbrows,"c",$navpos);

				$req = "select * from faqresults limit ".$navpos.", ".$babLimit;
				$this->resfaq = $this->db->db_query($req);
				$this->countfaq = $this->db->db_num_rows($this->resfaq);
				if( !$this->counttot && $this->countfaq > 0 )
					$this->counttot = true;
				}
				$this->nbresult += $nbrows;

			// ------------------------------------------------------------------------ PERSONAL NOTES
			if( (empty($item) || $item == "d") && !empty($BAB_SESS_USERID))
				{
				$plus = "";
				$plus = finder($this->like,"content",$option,$this->like2);
				if ($plus != "") $plus .= " and";
				$req = "select count(*) from ".BAB_NOTES_TBL." where ".$plus." id_user='".$BAB_SESS_USERID."'";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);
				$navpos = $this->navpos;
				if ($navitem != "d") $navpos = 0;
				$this->navbar_d = navbar($babLimit,$nbrows,"d",$navpos);
				
				$plus = "";
				$plus = finder($this->like,"content",$option,$this->like2);
				if ($plus != "") $plus .= " and";
				$req = "select id, content, UNIX_TIMESTAMP(date) date from ".BAB_NOTES_TBL." where ".$plus." id_user='".$BAB_SESS_USERID."' limit ".$navpos.", ".$babLimit;
				$this->resnot = $this->db->db_query($req);
				$this->countnot = $this->db->db_num_rows($this->resnot);
				if( !$this->counttot && $this->countnot > 0 )
					$this->counttot = true;
				}
				$this->nbresult += $nbrows;

			// ------------------------------------- FILES
			if( empty($item) || $item == "e")
				{
				$req = "create temporary table filresults select id, name title, id_owner,description, 'dd-mm-yyyy' datec, 'dd-mm-yyyy' datem, path, bgroup, name author, path folder from ".BAB_FILES_TBL." where 0";
				$this->db->db_query($req);
				$req = "alter table filresults add unique (id)";
				$this->db->db_query($req);
				bab_fileManagerAccessLevel();
				$private = false;
				$idfile = "";
				$grpfiles = " and F.bgroup='Y' ";

				if (isset($babBody->aclfm['id']) && is_array($babBody->aclfm['id']))
				for( $i = 0; $i < count($babBody->aclfm['id']); $i++)
					{
					if( $babBody->aclfm['down'][$i] == 1 || $babBody->aclfm['ma'][$i] == 1)
						{
						$idfile .= $babBody->aclfm['id'][$i].',';						
						}
					}

				if( $babBody->ustorage)
					{
					$idfile .= $BAB_SESS_USERID.',';
					$grpfiles = "";
					}

				$plus = "";
				$temp1 = finder($this->like,"F.name",$option,$this->like2);
				$temp2 = finder($this->like,"description",$option,$this->like2);
				$temp3 = finder($this->like,"keywords",$option,$this->like2);
				$temp4 = finder($this->like,"F.path",$option,$this->like2);
				$temp5 = finder($this->like,"R.folder",$option,$this->like2);
				$temp6 = finder($this->like,"M.fvalue",$option,$this->like2);

				if ($temp1 != "" && $temp2 != "" && $temp3 != "" && $temp4 != "" && $temp5 != "")
					$plus = "( ".$temp1." or ".$temp2." or ".$temp3." or ".$temp4." or ".$temp5." ) and ";
				else $plus = "";

                if ($idfile != "") 
					{
					$req = "insert into filresults select F.id, F.name title, F.id_owner, description, UNIX_TIMESTAMP(created) datec, UNIX_TIMESTAMP(modified) datem, path, bgroup, concat( U.lastname, ' ', U.firstname ) author, folder from ".BAB_FILES_TBL." F, ".BAB_USERS_TBL." U, ".BAB_FM_FOLDERS_TBL." R where F.author=U.id and (F.id_owner=R.id OR F.bgroup='N') and ".$plus." F.id_owner in (".substr($idfile,0,-1).") ". $grpfiles ." and state='' and confirmed='Y' order by ".$order;
                    $this->db->db_query($req);
					
					if ($temp6 != "")
						{
						$req = "insert into filresults select F.id, F.name title, F.id_owner, description, UNIX_TIMESTAMP(created) datec, UNIX_TIMESTAMP(modified) datem, path, bgroup, concat( U.lastname, ' ', U.firstname ) author, folder from ".BAB_FILES_TBL." F, ".BAB_USERS_TBL." U, ".BAB_FM_FOLDERS_TBL." R, ".BAB_FM_FIELDSVAL_TBL." M where ".$temp6." and M.id_file=F.id AND F.author=U.id and (F.id_owner=R.id OR F.bgroup='N') and F.id_owner in (".substr($idfile,0,-1).") ". $grpfiles ." and state='' and confirmed='Y' order by ".$order;
						$this->db->db_query($req);
						}
                    }


				$req = "select count(*) from filresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);

				$navpos = $this->navpos;
				if ($navitem != "e") $navpos = 0;
				$this->navbar_e = navbar($babLimit,$nbrows,"e",$navpos);

				$req = "select * from filresults limit ".$navpos.", ".$babLimit;
				$this->resfil = $this->db->db_query($req);
				$this->countfil = $this->db->db_num_rows($this->resfil);
				if( !$this->counttot && $this->countfil > 0 )
					$this->counttot = true;
				}
				$this->nbresult += $nbrows;
			
			// ------------------------------------------------ PERSONAL CONTACTS
			if( empty($item) || $item == "f")
				{
				$req = "create temporary table conresults select lastname title, firstname, compagny, email, id from ".BAB_CONTACTS_TBL." where 0";
				$this->db->db_query($req);
				$req = "alter table conresults add unique (id)";
				$this->db->db_query($req);

				$plus = "";
				$temp1 = finder($this->like,"firstname",$option,$this->like2);
				$temp2 = finder($this->like,"lastname",$option,$this->like2);
				$temp3 = finder($this->like,"email",$option,$this->like2);
				$temp4 = finder($this->like,"compagny",$option,$this->like2);
				$temp5 = finder($this->like,"jobtitle",$option,$this->like2);
				$temp6 = finder($this->like,"businessaddress",$option,$this->like2);
				$temp7 = finder($this->like,"homeaddress",$option,$this->like2);

				if ($temp1 != "" && $temp2 != "" && $temp3 != "" && $temp4 != "" && $temp5 != "" && $temp6 != "" && $temp7 != "")
					$plus = "( ".$temp1." or ".$temp2." or ".$temp3." or ".$temp4." or ".$temp5." or ".$temp6." or ".$temp7.") and";
				else $plus = "";

				$req = "insert into conresults select lastname title, firstname, compagny, email, id from ".BAB_CONTACTS_TBL." where ".$plus." owner='".$BAB_SESS_USERID."' order by ".$order;
				$this->db->db_query($req);

				$req = "select count(*) from conresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);
				$navpos = $this->navpos;
				if ($navitem != "f") $navpos = 0;
				$this->navbar_f = navbar($babLimit,$nbrows,"f",$navpos);

				$req = "select * from conresults limit ".$navpos.", ".$babLimit;
				$this->rescon = $this->db->db_query($req);
				$this->countcon = $this->db->db_num_rows($this->rescon);
				if( !$this->counttot && $this->countcon > 0 )
					$this->counttot = true;
				}
				$this->nbresult += $nbrows;
			// --------------------------------------------- DIRECTORIES
			if( empty($item) || $item == "g")
				{

				$id_directory = isset($this->fields['g_directory']) ? $this->fields['g_directory'] : '';
				$crit_fields = array();
				
				for($i = 0 ; $i < FIELDS_TO_SEARCH ; $i++)
					{
					$dirselect = isset($this->fields['dirselect_'.$i]) ? $this->fields['dirselect_'.$i] : '';
					$dirfield = isset($this->fields['dirfield_'.$i]) ? $this->fields['dirfield_'.$i] : '';
					if ($dirfield !="") 
						$crit_fields[] = finder($dirfield,$dirselect);
					}

				if( count($crit_fields) > 0 )
					{
					$crit_fields = implode(' and ', $crit_fields);
					}
				else
					{
					$crit_fields = '';
					}
				$dir_fields = array('name'=>array(),'description'=>array());
				
				$likedir = "(";
				
				$arrfields = array();
				$arrfields[] = 'det.id';
				$res = $this->db->db_query("SELECT * FROM ".BAB_DBDIR_FIELDS_TBL." WHERE name != 'jpegphoto'");
				while ($arr = $this->db->db_fetch_array($res))
					{
					$arrfields[] = "det.".$arr['name'];
					$dir_fields['name'][$arr['id']] = $arr['name'];
					$dir_fields['description'][$arr['id']] = $arr['description'];
					if ($likedir != "(")
						$likedir .= " or ";
					$likedir .= $this->like || $this->like2 ? finder($this->like,$arr['name'],$option,$this->like2) : '';
					}
				$likedir .= ") ";

				if (!$this->like && !$this->like2)
					$likedir = '';

				$req = "create temporary table dirresults select ".implode(",", $arrfields).",det.sn name from ".BAB_DBDIR_ENTRIES_TBL." det where 0";
				$this->db->db_query($req);
				$req = "alter table dirresults add unique (id)";
				$this->db->db_query($req);
				
				if (trim($id_directory) != "") 
					{
					$req = "select id,id_group, name from ".BAB_DB_DIRECTORIES_TBL." where id='".$id_directory."'";
					$res = $this->db->db_query($req);
					$row = $this->db->db_fetch_array($res);

					if(bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $row['id']))
						{
						if( !bab_isMagicQuotesGpcOn())
							$dirname = addslashes($row['name']);
						else
							$dirname = $row['name'];
	
						$res = $this->db->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($row['id_group'] != 0? 0: $row['id'])."' and id_field >".BAB_DBDIR_MAX_COMMON_FIELDS);
						$dbdirfields = array();
						$dbdirxfields = array();
						$dbdirfields[] = 'id';
						while( $row2 = $this->db->db_fetch_array($res))
							{
							$rr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($row2['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
							$dbdirxfields[] = "babdirf".$row2['id'];
							}

						$req = "create temporary table bab_dbdir_temptable select ".implode(',', $arrfields).", det.sn name from ".BAB_DBDIR_ENTRIES_TBL." det where 0";
						$this->db->db_query($req);

						if( $row['id_group'] > 0 )
							{
							list($bdir) = $this->db->db_fetch_array($this->db->db_query("select directory from ".BAB_GROUPS_TBL." where  id='".$row['id_group']."'"));
							if( $bdir == 'Y' )
								{
								$grp = ($row['id_group'] > 1) ? "UG.id_group='".$row['id_group']."' and ":'';
								$req = "insert into bab_dbdir_temptable select ".implode(",", $arrfields).", '".$dirname."' name from ".BAB_DBDIR_ENTRIES_TBL." det";
								if( $row['id_group'] > 1 )
									{
									$req .= " left join ".BAB_USERS_GROUPS_TBL." UG on UG.id_object=det.id_user";
									}
								$req .= " where det.id_directory='0'";
								if( $row['id_group'] > 1 )
									{
									$req .= " and UG.id_group='".$row['id_group']."'";
									}
								$this->db->db_query($req);
								}
							}
						else
							{
							$req = "insert into bab_dbdir_temptable select ".implode(",", $arrfields).",'".$dirname."' name from ".BAB_DBDIR_ENTRIES_TBL." det where id_directory='".($row['id_group'] != 0? 0: $row['id'])."'";
							$this->db->db_query($req);
							}

						if( count($dbdirxfields) > 0 )
							{
							for( $m=0; $m < count($dbdirxfields); $m++)
								{
								$this->db->db_query("alter table bab_dbdir_temptable add ".$dbdirxfields[$m]." VARCHAR( 255 ) NOT NULL");
								$this->db->db_query("alter table dirresults add ".$dbdirxfields[$m]." VARCHAR( 255 ) NOT NULL");
								}

							$res = $this->db->db_query("select id from bab_dbdir_temptable");
							while( $arr = $this->db->db_fetch_array($res))
								{
								$tmp = array();
								$res2 = $this->db->db_query("select * from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_entry='".$arr['id']."'");
								while( $rr = $this->db->db_fetch_array($res2))
									{
									$tmp[] = "babdirf".$rr['id_fieldx']." = '".$rr['field_value']."'";
									}
								if( count($tmp) > 0 )
									{
									$this->db->db_query("update bab_dbdir_temptable set ".implode(',', $tmp)." where id='".$arr['id']."'");
									}
								}
							}
							
						$req = "insert into dirresults select g.* from bab_dbdir_temptable g";
						if( !empty($likedir))
							{
							$req .= " where ".$likedir;
							if( !empty($crit_fields))
								{
								$req .= " and ".$crit_fields;
								}
							}
						else
							{
							if( !empty($crit_fields))
								{
								$req .= " where ".$crit_fields;
								}
							}
						$req .= " group by g.id order by sn asc,givenname asc";
						$this->db->db_query($req);
						}

					$this->dirfields = array('name'=>array(),'description'=>array());

					$rescol = $this->db->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($row['id_group'] != 0? 0: $row['id'])."' and ordering!='0' order by ordering asc");
					while( $row3 = $this->db->db_fetch_array($rescol))
						{
						if( $row3['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
							{
							$rr = $this->db->db_fetch_array($this->db->db_query("select name, description from ".BAB_DBDIR_FIELDS_TBL." where id='".$row3['id_field']."'"));
							$this->dirfields['name'][] = $rr['name'];
							$this->dirfields['description'][] = translateDirectoryField($rr['description']);
							}
						else
							{
							$rr = $this->db->db_fetch_array($this->db->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id='".($row3['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS)."'"));
							$this->arrcols[] = array("babdirf".$row3['id'], translateDirectoryField($rr['name']), 1);
							$this->dirfields['name'][] = "babdirf".$row3['id'];
							$this->dirfields['description'][] = translateDirectoryField($rr['name']);
							}					
						}

					}
				else
					{
					// all directories
					$plus = "";
					$plus = finder($this->like,"field_value",$option,$this->like2);
					if ($plus != "") $plus .= " and";

					$req = "select id,id_group, name from ".BAB_DB_DIRECTORIES_TBL." where id_group<'2'";
					$res = $this->db->db_query($req);
					while ($row = $this->db->db_fetch_array($res))
						{
						if(bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $row['id']))
							{
							if( !bab_isMagicQuotesGpcOn())
								$dirname = addslashes($row['name']);
							else
								$dirname = $row['name'];
							if( $row['id_group'] == 1 )
								{
								list($bdir) = $this->db->db_fetch_array($this->db->db_query("select directory from ".BAB_GROUPS_TBL." where  id='".$row['id_group']."'"));
								if( $bdir == 'Y' )
									{
									$req = "insert into dirresults select ".implode(',', $arrfields).", '".$dirname."' name from ".BAB_DBDIR_ENTRIES_TBL." det , ".BAB_USERS_GROUPS_TBL." UG where";
									if( !empty($likedir))
										{
										$req .= " ".$likedir." and";
										}
									$req .= " UG.id_object=det.id_user and det.id_directory='0'";
									if( !empty($crit_fields) )
										{
										$req .= " and ".$crit_fields;
										}
									$req .= " group by det.id order by sn asc,givenname asc";
									$this->db->db_query($req);

									$rs2 = $this->db->db_query("select dext.id_entry from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." dext left join ".BAB_DBDIR_ENTRIES_TBL." det on dext.id_entry=det.id left join ".BAB_USERS_GROUPS_TBL." UG on UG.id_object=det.id_user where ".$plus." det.id_directory='0'");

									while( $rr = $this->db->db_fetch_array($rs2))
										{
										$rs3 = $this->db->db_query("select id from dirresults where id='".$rr['id_entry']."'");
										if( !$rs3 || $this->db->db_num_rows($rs3) == 0 )
											{
											$this->db->db_query("insert into dirresults select ".implode(',', $arrfields).",'".$dirname."' name from ".BAB_DBDIR_ENTRIES_TBL." det where det.id='".$rr['id_entry']."'");
											}
										}								
									}
								}
							else
								{
								$req = "insert into dirresults select ".implode(',', $arrfields).",'".$dirname."' name from ".BAB_DBDIR_ENTRIES_TBL." det where";
								if( !empty($likedir))
									{
									$req .= " ".$likedir." and";
									}
								$req .= " id_directory='".($row['id_group'] != 0? 0: $row['id'])."'";
								if( !empty($crit_fields))
									{
									$req .= " and ".$crit_fields;
									}
								$req .= " group by det.id order by sn asc,givenname asc";
								$this->db->db_query($req);

								$rs2 = $this->db->db_query("select dext.id_entry from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." dext left join ".BAB_DBDIR_ENTRIES_TBL." det on dext.id_entry=det.id where ".$plus." det.id_directory='".($row['id_group'] != 0? 0: $row['id'])."'");
								while( $rr = $this->db->db_fetch_array($rs2))
									{
									$rs3 = $this->db->db_query("select id from dirresults where id='".$rr['id_entry']."'");
									if( !$rs3 || $this->db->db_num_rows($rs3) == 0 )
										{
										$this->db->db_query("insert into dirresults select ".implode(',', $arrfields).",'".$dirname."' name from ".BAB_DBDIR_ENTRIES_TBL." det where det.id='".$rr['id_entry']."'");
										}
									}
								}
							}

						}
					$this->dirfields['name'] = array($dir_fields['name'][2],$dir_fields['name'][4]);
					$this->dirfields['description'] = array($dir_fields['description'][2],$dir_fields['description'][4]);
					}
				$this->countdirfields = count($this->dirfields['name']);

				$req = "select count(*) from dirresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);
				$navpos = $this->navpos;
				if ($navitem != "g") $navpos = 0;
				$this->navbar_g = navbar($babLimit,$nbrows,"g",$navpos);
				$tmp = explode(" ",$order);
				if (in_array("title",$tmp)) $order_tmp = "order by sn ASC, givenname ASC";
				else $order_tmp = "order by ".$order;
				$req = "select * from dirresults ".$order_tmp." limit ".$navpos.", ".$babLimit;
				$this->resdir = $this->db->db_query($req);
				$this->countdir = $this->db->db_num_rows($this->resdir);
				if( !$this->counttot && $this->countdir > 0 )
					$this->counttot = true;
				$this->nbresult += $nbrows;
				}
								
		
		// --------------------------------------------- AGENDA

		if( empty($item) || $item == "h")
				{
				$crit_date = '';
				$select_idcal = '';
				if (isset($this->fields['after']) && trim($this->fields['after']) != "")
					{
					$crit_date = " and ce.start_date >= '".$this->fields['after']." 00:00:00'";
					}
				if (isset($this->fields['before']) && trim($this->fields['before']) != "")
					{
					$crit_date .= " and ce.end_date <= '".$this->fields['before']."' 23:59:59";
					}
				if (isset($this->fields['h_calendar']) && trim($this->fields['h_calendar']) != "")
					{
					$select_idcal = " and ceo.id_cal = '".$this->fields['h_calendar']."'";
					}

				$req = "create temporary table ageresults select ceo.id_cal owner, ce.id id, ce.title, ce.description, ce.start_date, ce.end_date, ceo.id_cal id_cal, cct.name categorie, cct.description catdesc from ".BAB_CAL_EVENTS_OWNERS_TBL." ceo, ".BAB_CAL_EVENTS_TBL." ce, ".BAB_CAL_CATEGORIES_TBL." cct where 0";
				$this->db->db_query($req);
				
				$list_id_cal = array();
				$tmp = array_merge(getAvailableUsersCalendars(),getAvailableGroupsCalendars(),getAvailableResourcesCalendars());
				foreach ($tmp as $arr)
					$list_id_cal[] = $arr['idcal'];
				
				if ($this->like || $this->like2)
					{
					$reqsup = "(".finder($this->like,"ce.title",$option,$this->like2)." or ".finder($this->like,"ce.description",$option,$this->like2).")";
					$reqsupc = "AND (".finder($this->like,"cct.description",$option,$this->like2)." or ".finder($this->like,"cct.name",$option,$this->like2).")";
					}
				else 
					{
					$reqsup = "";
					$reqsupc = "";
					}

				if (count($list_id_cal) > 0) 
					{
					$req = "insert into ageresults select ceo.id_cal owner, ce.id id, ce.title, ce.description, ce.start_date, ce.end_date, ceo.id_cal id_cal, cct.name categorie, cct.description catdesc from ".BAB_CAL_EVENTS_OWNERS_TBL." ceo left join ".BAB_CAL_EVENTS_TBL." ce on ceo.id_event=ce.id left join ".BAB_CAL_CATEGORIES_TBL." cct on cct.id=ce.id_cat where ".$reqsup." ".$crit_date." and ceo.id_cal in(".implode(',',$list_id_cal).")".$select_idcal." order by ".$order;
					$this->db->db_query($req);
					}

				$req = "select count(*) from ageresults";
				$res = $this->db->db_query($req);
				list($nbrows) = $this->db->db_fetch_row($res);
				$navpos = $this->navpos;
				if ($navitem != "h") $navpos = 0;
				$this->navbar_h = navbar($babLimit,$nbrows,"h",$navpos);

				$req = "select * from ageresults limit ".$navpos.", ".$babLimit;
				$this->resage = $this->db->db_query($req);
				$this->countage = $this->db->db_num_rows($this->resage);
				if( !$this->counttot && $this->countage > 0 )
					$this->counttot = true;
				$this->nbresult += $nbrows;
				}
				
			// --------------------------------------------- ADDONS

			$this->addons = new bab_addonsSearch;
			$this->addonSearchArray = $this->addons->getsearcharray($item);
			$this->countaddons = count($this->addonSearchArray);
			$this->addons->setSearchParam($this->like2, $this->like, $option, $navpos, $babLimit);

			$this->addonsdata = array();
			$first_addon_searchresults = array();

			if (is_array($this->addonSearchArray))
				while (list($addon_id,$addon_title) = each($this->addonSearchArray))
					{
					if (isset($addon_id) && is_numeric($addon_id))
						{
						$first_addon_searchresults[$addon_id] = $this->addons->callSearchFunction($addon_id);
						$nbrows = $first_addon_searchresults[$addon_id][1];
						$navpos = $this->navitem == 'as-'.$addon_id ? $this->navpos : 0;
						$this->addons->pos = $navpos;
						$navbar_i = navbar($GLOBALS['babLimit'],$nbrows,'as-'.$addon_id,$navpos);
						if ($nbrows > 0)
							$this->addonsdata[] = array($addon_id, $addon_title, $navbar_i, $first_addon_searchresults);
						}
					}

			$this->nbresult += $nbrows;
			if( !$this->counttot && count($this->addonsdata) > 0 )
					$this->counttot = true;


			// end

			if( !$this->counttot)
				{
				$babBody->msgerror = bab_translate("Search result empty");
				}

			}

		function dateformat($time)
			{
			return bab_shortDate($time, true);
			}

		function getnextart()
			{
			static $i = 0;
			if( $i < $this->countart)
				{
				$arr = $this->db->db_fetch_array($this->resart);
				$this->article = put_text($arr['title']);
				$this->artdate = bab_shortDate($arr['date'], true);
				$this->artauthor = $arr['author'];
				$this->arttopic = returnCategoriesHierarchy($arr['id_topic']);
				$this->arttopicid = $arr['id_topic'];
				$this->articleurlpop = $GLOBALS['babUrlScript']."?tg=search&idx=a&id=".$arr['id']."&w=".$this->what;
				if (strlen(trim(stripslashes($arr['body']))) > 0)
					{
					$this->articleurl = $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$arr['id_topic']."&article=".$arr['id'];
					}
				else
					{
					if( $arr['archive'] ==  'Y' )
						{
						$urlidx = "larch";
						}
					else
						{
						$urlidx = "Articles";
						}
					$this->articleurl = $GLOBALS['babUrlScript']."?tg=articles&idx=".$urlidx."&topics=".$arr['id_topic']."#art".$arr['id'];
					}
				$this->authormail = bab_getUserEmail($arr['id_author']);
				$this->intro = put_text($arr['head'],300);
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists artresults";
				$this->db->db_query($req);
				return false;
				}
			}

		function getnextcom()
			{
			static $i = 0;
			if( $i < $this->countcom)
				{
				$arr = $this->db->db_fetch_array($this->rescom);
				$this->artdate = bab_shortDate($arr['date'], true);
				$this->artauthor = $arr['name'];
				$this->authormail = $arr['email'];
				$this->arttopic = returnCategoriesHierarchy($arr['id_topic']);
				$this->article = put_text($arr['arttitle']);
				$this->arttopicid = $arr['id_topic'];
				$this->com = put_text($arr['subject']);
				if (strlen(trim(stripslashes($arr['body']))) > 0)
					$this->urlok = true;
				else
					$this->urlok = false;
				$this->articleurl = $GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$arr['id_topic']."&article=".$arr['id_article'];
				$this->articleurlpop = $GLOBALS['babUrlScript']."?tg=search&idx=a&id=".$arr['id_article']."&w=".$this->what;
				$this->comurl = $this->articleurl;
				$this->comurlpop = $GLOBALS['babUrlScript']."?tg=search&idx=ac&idt=".$arr['id_topic']."&ida=".$arr['id_article']."&idc=".$arr['id']."&w=".$this->what;
				$this->intro = put_text($arr['message'],300);
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists comresults";
				$this->db->db_query($req);
				return false;
				}
			}

		function getnextfor()
			{
			static $i = 0;
			if( $i < $this->countfor)
				{
				$arr = $this->db->db_fetch_array($this->resfor);
				$this->post = put_text($arr['title']);
				$this->postauthor = $arr['author'];
				$this->postdate = bab_shortDate($arr['date'], true);
				$this->forum = put_text($arr['topic']);
				$this->forumurl = $GLOBALS['babUrlScript']."?tg=threads&forum=".$arr['id_topic'];
				$this->intro = put_text($arr['message'],300);
				$this->posturl = $GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$arr['id_topic']."&thread=".$arr['id_thread']."&post=".$arr['id']."&flat=0";
				$this->posturlpop = $GLOBALS['babUrlScript']."?tg=search&idx=b&idt=".$arr['id_thread']."&idp=".$arr['id']."&w=".$this->what;
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists forresults";
				$this->db->db_query($req);
				return false;
				}
			}
		function getnextfaq()
			{
			static $i = 0;
			if( $i < $this->countfaq)
				{
				$arr = $this->db->db_fetch_array($this->resfaq);
				$this->question = put_text($arr['title']);
				$this->faqtopic = put_text($arr['topic']);
				$this->faqauthor = $arr['author'];
				$this->faqauthormail = bab_getUserEmail($arr['id_manager']);
				$this->faqtopicid = $arr['idcat'];
				$this->topicurl = $GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$arr['idcat'];
				$this->questionurlpop = $GLOBALS['babUrlScript']."?tg=search&idx=c&idc=".$arr['idcat']."&idq=".$arr['id']."&w=".$this->what;
				$this->questionurl = $GLOBALS['babUrlScript']."?tg=faq&idx=viewq&item=".$arr['idcat']."&idq=".$arr['id'];
				$this->intro = put_text($arr['response'],300);
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists faqresults";
				$this->db->db_query($req);
				return false;
				}
			}

		function getnextfil()
			{
			static $i = 0;
			if( $i < $this->countfil)
				{
				$arr = $this->db->db_fetch_array($this->resfil);
				$this->file = put_text($arr['title']);
				$this->update = bab_shortDate($arr['datem'], true);
				$this->created = bab_shortDate($arr['datec'], true);
                $this->artauthor = $arr['author'];
				$this->filedesc = put_text($arr['description']);
				$this->path = $arr['path'];
				if ($arr['bgroup'] == 'N')
					$this->arttopic = $arr['path'];
				else
					$this->arttopic = $arr['folder']."/".$arr['path'];
				$this->arttopicid = $arr['id_owner'];
				$this->bgroup = $arr['bgroup'];
				$this->filedesc = $arr['description'];
				$this->fileurl = $GLOBALS['babUrlScript']."?tg=search&idx=e&id=".$arr['id']."&w=".$this->what;
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists filresults";
				$this->db->db_query($req);
				return false;
				}
			}

		function getnextcon()
			{
			static $i = 0;
			if( $i < $this->countcon)
				{
				$arr = $this->db->db_fetch_array($this->rescon);
				$arr['firstname'] = isset($arr['firstname']) ? $arr['firstname']: '';
				$arr['lastname'] = isset($arr['lastname']) ? $arr['lastname']: '';
				$this->fullname = bab_composeUserName( $arr['firstname'], $arr['lastname']);
				$this->confirstname = $arr['firstname'];
				$this->conlastname = put_text($arr['title']);
				$this->conemail = $arr['email'];
				$this->concompany = $arr['compagny'];
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=search&idx=f&id=".$arr['id']."&w=".$this->what;
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists conresults";
				$this->db->db_query($req);
				return false;
				}
			}

		function getnextnot()
			{
			static $i = 0;
			if( $i < $this->countnot)
				{
				$arr = $this->db->db_fetch_array($this->resnot);
				$this->content = put_text($arr['content'],400);
				$this->notauthor = $GLOBALS['BAB_SESS_USER'];
				$this->notauthormail = bab_getUserEmail($GLOBALS['BAB_SESS_USERID']);
                $this->notdate = bab_shortDate($arr['date'], true);
				$this->read_more = bab_translate("Edit");
				$this->noteurl = $GLOBALS['babUrlScript']."?tg=note&idx=Modify&item=".$arr['id'];
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}

		function getnextdirfield()
			{
			static $i = 0;
			if( $i < $this->countdirfields)
				{
				$this->name = $this->dirfields['name'][$i];
				$this->ordercmd = $this->name == 'sn' ? 'sn, givenname':($this->name == 'givenname' ? 'givenname, sn' : $this->name);
				$this->t_name = translateDirectoryField($this->dirfields['description'][$i]);
				if (isset($this->dir))
				switch ($this->name)
					{
					case 'sn':
						$this->dirvalue = isset($this->dir[$this->name]) ? $this->dir[$this->name]  : '';
						$this->dirurl = $GLOBALS['babUrlScript']."?tg=search&idx=g&id=".$this->dir['id']."&w=".$this->what;
						$this->popup = true;
						break;
					case 'givenname':
						$this->dirvalue = isset($this->dir[$this->name]) ? $this->dir[$this->name]  : '';
						$this->dirurl = $GLOBALS['babUrlScript']."?tg=search&idx=g&id=".$this->dir['id']."&w=".$this->what;
						$this->popup = true;
						break;
					case 'mfunction':
						$this->dirvalue = isset($this->dir[$this->name]) ? $this->dir[$this->name]  : '';
						$this->dirurl = 'mailto:'.$this->dirvalue;
						$this->popup = false;
						break;
					default:
						$this->dirvalue = isset($this->dir[$this->name]) ? $this->dir[$this->name]  : '';
						$this->dirurl = false;
						$this->popup = false;
						break;
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

		function getnextdir()
			{
			static $i = 0;
			if( $i < $this->countdir)
				{
				$arr = $this->db->db_fetch_array($this->resdir);
				foreach ($arr as $key => $value)
					{
					if (in_array($key,$this->dirfields['name']))
						$arr[$key] = stripslashes($value);
					}
				$this->dir= $arr;
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists dirresults";
				$this->db->db_query($req);
				return false;
				}
			}

		function getnextage()
			{
			global $babBody;
			static $i = 0;
			if( $i < $this->countage)
				{
				$arr = $this->db->db_fetch_array($this->resage);
				$this->agetitle = put_text($arr['title']);
				$this->agedescription = put_text($arr['description'],400);
				$this->agestart_date = $this->dateformat(bab_mktime($arr['start_date']));
				$this->ageend_date = $this->dateformat(bab_mktime($arr['end_date']));
				$iarr = $babBody->icalendars->getCalendarInfo($arr['id_cal']);
				$this->agecreator = $iarr['name'];
				switch ($iarr['type'])
					{
					case BAB_CAL_USER_TYPE:
						$this->agecreatormail = bab_getUserEmail($iarr['idowner']);
						break;
					case BAB_CAL_PUB_TYPE:
					case BAB_CAL_RES_TYPE:
						$this->agecreatormail = "";
						break;
					}
				$this->agecat = "".$arr['categorie'];
				$this->agecatdesc = "".put_text($arr['catdesc'],200);
				$this->ageurl = $GLOBALS['babUrlScript']."?tg=calendar&idx=vevent&evtid=".$arr['id']."&idcal=".$arr['id_cal'];
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists ageresults";
				$this->db->db_query($req);
				return false;
				}
			}

		function getnextaddon()
			{
			return list($key,list($this->addon_id, $this->addon_title, $this->navbar_i, $this->first_addon_searchresults)) = each($this->addonsdata);
			}

		function getnextaddonrow()
			{
			if (isset($this->addon_id) && is_numeric($this->addon_id))
				{
				if (isset($this->first_addon_searchresults[$this->addon_id]))
					{
					$addon_searchresults = $this->first_addon_searchresults[$this->addon_id];
					unset($this->first_addon_searchresults[$this->addon_id]);
					}
				else
					$addon_searchresults = $this->addons->callSearchFunction($this->addon_id);
				$this->text = isset($addon_searchresults[0]) ? $addon_searchresults[0]  : '';
				list( $this->url, $this->urltxt ) = isset($addon_searchresults[2]) && is_array($addon_searchresults[2]) && !empty($addon_searchresults[2][0]) && !empty($addon_searchresults[2][1]) ? $addon_searchresults[2] : array(false,false);
				list( $this->urlpopup, $this->urlpopuptxt ) = isset($addon_searchresults[3]) && is_array($addon_searchresults[3]) && !empty($addon_searchresults[3][0]) && !empty($addon_searchresults[3][1]) ? $addon_searchresults[3] : array(false,false);
				} 
			return isset($addon_searchresults) && is_array($addon_searchresults) ? true : false;
			}
		}

	$temp = new temp($item, $what, $order, $option,$navitem,$navpos);
	$babBody->babecho(	bab_printTemplate($temp,"search.html", "searchresult"));
	}

function viewArticle($article,$w)
	{
	global $babBody;

	class temp
		{
	
		var $content;
		var $head;
		var $arr = array();
		var $db;
		var $count;
		var $res;
		var $more;
		var $topics;
		var $babMeta;
		var $babCss;
		var $close;
		var $altbg = false;


		function temp($article,$w)
			{
			$this->close = bab_translate("Close");
			$this->attachmentxt = bab_translate("Associated documents");
			$this->commentstxt = bab_translate("Comments");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_ARTICLES_TBL." where id='$article'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->countf = 0;
			$this->countcom = 0;
			$this->w = $w;
			if( bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $this->arr['id_topic']) && bab_articleAccessByRestriction($this->arr['restriction']))
				{
				$GLOBALS['babWebStat']->addArticle($this->arr['id']);
				$this->content = highlightWord($w,bab_replace($this->arr['body']));
				$this->head = highlightWord($w,bab_replace($this->arr['head']));

				$this->resf = $this->db->db_query("select * from ".BAB_ART_FILES_TBL." where id_article='".$article."'");
				$this->countf = $this->db->db_num_rows($this->resf);

				if( $this->countf > 0 )
					{
					$this->battachments = true;
					}
				else
					{
					$this->battachments = false;
					}

				$this->rescom = $this->db->db_query("select * from ".BAB_COMMENTS_TBL." where id_article='".$article."' and confirmed='Y' order by date desc");
				$this->countcom = $this->db->db_num_rows($this->rescom);
				}
			else
				{
				$this->content = "";
				$this->head = bab_translate("Access denied");
				}
			}

		function getnextdoc()
			{
			global $arrtop;
			static $i = 0;
			if( $i < $this->countf)
				{
				$arr = $this->db->db_fetch_array($this->resf);
				$this->docurl = $GLOBALS['babUrlScript']."?tg=articles&idx=getf&topics=".$this->arr['id_topic']."&article=".$this->arr['id']."&idf=".$arr['id'];
				$this->docname = $arr['name'];
				$i++;
				return true;
				}
			else
				{
				$i = 0;
				return false;
				}
			}

		function getnextcom()
			{
			static $i = 0;
			if( $i < $this->countcom)
				{
				$arr = $this->db->db_fetch_array($this->rescom);
				$this->altbg = !$this->altbg;
				$this->commentdate = bab_strftime(bab_mktime($arr['date']));
				$this->authorname = highlightWord($this->w,$arr['name']);
				$this->commenttitle = highlightWord($this->w,$arr['subject']);
				$this->commentbody = highlightWord($this->w,bab_replace($arr['message']));
				$i++;
				return true;
				}
			else
				{
				$this->db->db_data_seek($this->rescom,0);
				$i=0;
				return false;
				}
			}
		}
	
	$temp = new temp($article,$w);
	echo bab_printTemplate($temp,"search.html", "viewart");
	}

function viewComment($topics, $article, $com, $w)
	{
	global $babBody;
	
	class ctp
		{
		var $subject;
		var $add;
		var $topics;
		var $article;
		var $arr = array();
		var $babCss;

		function ctp($topics, $article, $com, $w)
			{
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->babUrl = $GLOBALS['babUrl'];
			$this->sitename = $GLOBALS['babSiteName'];
			$this->title = bab_getArticleTitle($article);
			$this->subject = bab_translate("Subject");
			$this->by = bab_translate("By");
			$this->date = bab_translate("Date");
			$this->topics = $topics;
			$this->article = $article;
			$db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_COMMENTS_TBL." where id='$com'";
			$res = $db->db_query($req);
			$this->arr = $db->db_fetch_array($res);
			$this->arr['date'] = bab_strftime(bab_mktime($this->arr['date']));
			$this->arr['subject'] = highlightWord( $w, bab_replace($this->arr['subject']));
			$this->arr['message'] = highlightWord( $w, bab_replace($this->arr['message']));
			}
		}

	$ctp = new ctp($topics, $article, $com, $w);
	echo bab_printTemplate($ctp,"search.html", "viewcom");
	}

function viewPost($thread, $post, $w)
	{
	global $babBody;

	class temp
		{
	
		var $postmessage;
		var $postsubject;
		var $postdate;
		var $postauthor;
		var $title;
		var $babCss;

		function temp($thread, $post, $w)
			{
			$db = $GLOBALS['babDB'];
			$req = "select forum from ".BAB_THREADS_TBL." where id='".$thread."'";
			$arr = $db->db_fetch_array($db->db_query($req));
			$this->title = bab_getForumName($arr['forum']);
			$req = "select * from ".BAB_POSTS_TBL." where id='".$post."'";
			$arr = $db->db_fetch_array($db->db_query($req));
			$this->postdate = bab_strftime(bab_mktime($arr['date']));
			$this->postauthor = $arr['author'];
			$this->postsubject = highlightWord( $w, bab_replace($arr['subject']));
			$this->postmessage = highlightWord( $w, bab_replace($arr['message']));
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			}
		}
	
	$temp = new temp($thread, $post, $w);
	echo bab_printTemplate($temp,"search.html", "viewfor");
	}

function viewQuestion($idcat, $id, $w)
	{
	global $babBody;
	class temp
		{
		var $arr = array();
		var $db;
		var $res;
		var $babCss;

		function temp($idcat, $id, $w)
			{
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FAQQR_TBL." where id='$id'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$this->arr['question'] = highlightWord( $w, bab_replace($this->arr['question']));
			$this->arr['response'] = highlightWord( $w, bab_replace($this->arr['response']));
			$req = "select category from ".BAB_FAQCAT_TBL." where id='$idcat'";
			$a = $this->db->db_fetch_array($this->db->db_query($req));
			$this->title = highlightWord( $w,  $a['category']);
			}

		}

	$temp = new temp($idcat, $id, $w);
	echo bab_printTemplate($temp,"search.html", "viewfaq");
	return true;
	}

function viewFile($id, $w)
	{
	global $babBody;
	class temp
		{
		var $arr = array();
		var $db;
		var $res;
		var $babCss;
		var $description;
		var $keywords;
		var $modified;
		var $postedby;
		var $modifiedtxt;
		var $postedbytxt;
		var $createdtxt;
		var $created;
		var $modifiedbytxt;
		var $modifiedby;
		var $sizetxt;
		var $size;
		var $download;
		var $geturl;

		function temp($id, $w)
			{
			$this->description = bab_translate("Description");
			$this->keywords = bab_translate("Keywords");
			$this->modifiedtxt = bab_translate("Modified");
			$this->createdtxt = bab_translate("Created");
			$this->postedbytxt = bab_translate("Posted by");
			$this->modifiedbytxt = bab_translate("Modified by");
			$this->download = bab_translate("Download");
			$this->sizetxt = bab_translate("Size");
			$this->pathtxt = bab_translate("Path");
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_FILES_TBL." where id='$id' and state='' and confirmed='Y'";
			$this->res = $this->db->db_query($req);
			$this->arr = $this->db->db_fetch_array($this->res);
			$access = bab_isAccessFileValid($this->arr['bgroup'], $this->arr['id_owner']);
			if( $access )
				{
				$this->title = $this->arr['name'];
				$this->arr['description'] = highlightWord( $w, $this->arr['description']);
				$this->arr['keywords'] = highlightWord( $w, $this->arr['keywords']);
				$this->modified = bab_shortDate(bab_mktime($this->arr['modified']), true);
				$this->created = bab_shortDate(bab_mktime($this->arr['created']), true);
				$this->postedby = bab_getUserName($this->arr['author']);
				$this->modifiedby = bab_getUserName($this->arr['modifiedby']);
				$this->geturl = $GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$this->arr['id_owner']."&gr=".$this->arr['bgroup']."&path=".urlencode($this->arr['path'])."&file=".urlencode($this->arr['name']);
				if( $this->arr['bgroup'] == "Y")
					$fstat = stat($GLOBALS['babUploadPath']."/G".$this->arr['id_owner']."/".$this->arr['path']."/".$this->arr['name']);
				else
					$fstat = stat($GLOBALS['babUploadPath']."/U".$this->arr['id_owner']."/".$this->arr['path']."/".$this->arr['name']);
				$this->size = bab_formatSizeFile($fstat[7])." ".bab_translate("Kb");
				if( $this->arr['bgroup'] == "Y")
					$this->rootpath = bab_getFolderName($this->arr['id_owner']);
				else
					$this->rootpath = "";
				$this->path = $this->rootpath."/".$this->arr['path'];
				if( $this->arr['bgroup'] == 'Y' )
					{
					$this->resff = $this->db->db_query("select * from ".BAB_FM_FIELDS_TBL." where id_folder='".$this->arr['id_owner']."'");
					$this->countff = $this->db->db_num_rows($this->resff);
					}
				else
					$this->countff = 0;
				}
			else
				{
				$this->title = bab_translate("Access denied");
				$this->arr['description'] = "";
				$this->arr['keywords'] = "";
				$this->created = "";
				$this->modifiedby = "";
				$this->modified = "";
				$this->postedby = "";
				$this->geturl = "";
				$this->countff = 0;
				}
			}

		function getnextfield()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countff)
				{
				$arr = $babDB->db_fetch_array($this->resff);
				$this->field = bab_translate($arr['name']);
				$this->fieldval = '';
				$res = $babDB->db_query("select fvalue from ".BAB_FM_FIELDSVAL_TBL." where id_field='".$arr['id']."' and id_file='".$this->arr['id']."'");
				if( $res && $babDB->db_num_rows($res) > 0)
					{
					list($this->fieldval) = $babDB->db_fetch_array($res);
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

	$temp = new temp($id, $w);
	echo bab_printTemplate($temp,"search.html", "viewfil");
	return true;
	}


function viewContact($id, $what)
	{
	class temp
		{
		var $firstname;
		var $lastname;
		var $email;
		var $compagny;
		var $hometel;
		var $mobiletel;
		var $businesstel;
		var $businessfax;
		var $jobtitle;
		var $businessaddress;
		var $homeaddress;
		var $firstnameval;
		var $lastnameval;
		var $emailval;
		var $compagnyval;
		var $hometelval;
		var $mobiletelval;
		var $businesstelval;
		var $businessfaxval;
		var $jobtitleval;
		var $businessaddressval;
		var $homeaddressval;
		var $addcontactval;
		var $cancel;
		var $babCss;
		var $msgerror;

		function temp($id, $what)
			{
			global $BAB_SESS_USERID;
			$this->firstname = bab_translate("First Name");
			$this->lastname = bab_translate("Last Name");
			$this->email = bab_translate("Email");
			$this->compagny = bab_translate("Compagny");
			$this->hometel = bab_translate("Home Tel");
			$this->mobiletel = bab_translate("Mobile Tel");
			$this->businesstel = bab_translate("Business Tel");
			$this->businessfax = bab_translate("Business Fax");
			$this->jobtitle = bab_translate("Job Title");
			$this->businessaddress = bab_translate("Business Address");
			$this->homeaddress = bab_translate("Home Address");
			$this->cancel = bab_translate("Cancel");
			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$this->msgerror = "";

			$db = $GLOBALS['babDB'];
			$req = "select * from ".BAB_CONTACTS_TBL." where id='".$id."'";
			$arr = $db->db_fetch_array($db->db_query($req));
			if( !empty($BAB_SESS_USERID) && $arr['owner'] == $BAB_SESS_USERID )
				{
				$this->firstnameval = $arr['firstname'];
				$this->lastnameval = $arr['lastname'];
				$this->emailval = $arr['email'];
				$this->compagnyval = $arr['compagny'];
				$this->hometelval = $arr['hometel'];
				$this->mobiletelval = $arr['mobiletel'];
				$this->businesstelval = $arr['businesstel'];
				$this->businessfaxval = $arr['businessfax'];
				$this->jobtitleval = $arr['jobtitle'];
				$this->businessaddressval = $arr['businessaddress'];
				$this->homeaddressval = $arr['homeaddress'];
				}
			else
				{
				$this->msgerror = bab_translate("You don't have access to this contact");
				$this->firstnameval = "";
				$this->lastnameval = "";
				$this->emailval = "";
				$this->compagnyval = "";
				$this->hometelval = "";
				$this->mobiletelval = "";
				$this->businesstelval = "";
				$this->businessfaxval = "";
				$this->jobtitleval = "";
				$this->businessaddressval = "";
				$this->homeaddressval = "";
				}
			}
		}

	$temp = new temp($id, $what);
	echo bab_printTemplate($temp,"search.html", "viewcon");
	}

function viewDirectoryUser($id, $what)
{
	global $babBody, $babDB, $babInstallPath;
	list($idd, $idu) = $babDB->db_fetch_array($babDB->db_query("select id_directory, id_user from ".BAB_DBDIR_ENTRIES_TBL." where id='".$id."'"));
	$access = false;
	if( $idd == 0 )
	{
		$res = $babDB->db_query("select id, id_group from ".BAB_DB_DIRECTORIES_TBL." where id_group != '0'");
		while( $row = $babDB->db_fetch_array($res))
			{
			$idd = $row['id'];
			list($bdir) = $babDB->db_fetch_array($babDB->db_query("select directory from ".BAB_GROUPS_TBL." where id='".$row['id_group']."'"));
			if( $bdir == 'Y' && bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $row['id']))
				{
				if( $row['id_group'] == 1 && $GLOBALS['BAB_SESS_USERID'] != "" )
					{
					$access = true;
					break;
					}
				$res2 = $babDB->db_query("select id from ".BAB_USERS_GROUPS_TBL." where id_object='".$idu."' and id_group='".$row['id_group']."'");
				if( $res2 && $babDB->db_num_rows($res2) > 0 )
					{
					$access = true;
					break;
					}
				}

			}
	}
	elseif( bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $idd))
		{
		$access = true;
		}

	if( $access )
	{
	echo summaryDbContact($idd, $id, false);
	}
	else
	{
		echo bab_translate("Access denied");
	}
}
 

function goto_addon()
	{
	$addons = new bab_addonsSearch;
	$id = substr($_POST['item'],3);
	if (!empty($id) && is_numeric($id) && isset($addons->tabLinkAddons[$id]))
		header('location:'.$GLOBALS['babUrlScript']."?tg=addon/".$id."/".$addons->querystring[$id]);
	}


if( !isset($what))
	$what = "";

if( !isset($idx))
	$idx = "";

if( !isset($item))
	$item = '';

if( !isset($option))
	$option = '';

if ((!isset($navpos)) || ($navpos == ""))
	$navpos = 0;

if((!isset($field)) || ($field==""))
	$field = " title ";

if((!isset($order)) || ($order == ""))
	$order = " ASC";

if((!isset($pat)) || ($pat == ""))
	$pat = $GLOBALS['babSearchUrl'];

if (isset($_POST['item']) && substr($_POST['item'],0,3) == 'al-')
	{
	goto_addon();
	}



switch($idx)
	{
	case "a":
		viewArticle($id, $w);
		exit;
		break;

	case "ac":
		viewComment($idt, $ida, $idc, $w);
		exit;
		break;

	case "b":
		viewPost($idt, $idp, $w);
		exit;
		break;

	case "c":
		viewQuestion($idc, $idq, $w);
		exit;
		break;

	case "e":
		viewFile($id, $w);
		exit;
		break;

	case "f":
		viewContact($id, $w);
		exit;
		break;

	case "g":
		viewDirectoryUser($id, $w);
		exit;
		break;

	case "find":
		$babBody->title = bab_translate("Search");
		searchKeyword( $item , $option);
		$order = str_replace (","," ".$order." ,",$field)." ".$order;
		if( !isset($navitem)) { $navitem = '';}
		$GLOBALS['babWebStat']->addSearchWord($what);
		startSearch( $item, $what, $order, $option,$navitem,$navpos);
		break;

	default:
		$babBody->title = bab_translate("Search");
		searchKeyword( $item ,$option);
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>