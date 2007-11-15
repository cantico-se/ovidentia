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
include_once $babInstallPath."utilit/calapi.php";
include_once $babInstallPath."utilit/dirincl.php";
include_once $babInstallPath."utilit/searchincl.php";

$babLimit = 5;
$navbaritems = 10;
define ("FIELDS_TO_SEARCH", 3);

function highlightWord( $w, $text)
	{
	return bab_highlightWord( $w, $text);
	}
	
	
function unhtmlentities($string)
{
    // replace numeric entities
    $string = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $string);
    $string = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $string);
    // replace literal entities
    $trans_tbl = get_html_translation_table(HTML_ENTITIES);
    $trans_tbl = array_flip($trans_tbl);
    return strtr($string, $trans_tbl);
}
	
/**
 * text from WYSIWYG
 */
function put_text($txt, $limit = 60, $limitmot = 30 )
	{
	$obj = bab_replace_get();
	$obj->ref($txt);
	
	if (strlen($txt) > $limit)
		$out = substr(unhtmlentities(strip_tags($txt)),0,$limit)."...";
	else
		$out = unhtmlentities(strip_tags($txt));
	$arr = explode(" ",$out);
	foreach($arr as $key => $mot)
		$arr[$key] = substr($mot,0,$limitmot);
	$txt = implode(" ",$arr);
	
	return bab_toHtml($txt);
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
		global $babDB;
		
		$res = $babDB->db_query("select id,title from ".BAB_ADDONS_TBL." where enabled='Y' AND installed='Y'");
		while (list($id,$title) = $babDB->db_fetch_array($res))
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
						if (function_exists($func_results))
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
		foreach ($this->tabLinkAddons as $key => $value)
			$out['al-'.$key] = $value;
		foreach ($this->tabSearchAddons as $key => $value)
			if (!isset($out['al-'.$key])) $out['as-'.$key] = $value;
		
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

	function setSearchParam($q1, $q2, $option, $nb_result)
		{
		$this->q1 = $q1;
		$this->q2 = $q2;
		$this->option = $option;
		$this->nb_result = $nb_result;
		}


	function callSearchFunction($id)
		{
		if (!isset($this->i[$id]))
			$this->i[$id] = 0;

		if ($this->i[$id] >= $this->nb_result)
			return false;
		$this->i[$id]++;

		$this->defineAddonGlobals($id);
		$func = $this->func_results[$id];
		return $func($this->q1, $this->q2, $this->option, $this->pos[$id], $this->nb_result);
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

		function tempb($item ,$option )
			{

			global  $babDB,$babBody,$babSearchItems;
			$this->item = $item;
			$this->fields = isset($_POST) ? $_POST : array();
			$this->search = bab_translate("Search");
			$this->all = bab_translate("All");
			$this->in = bab_translate("in");
			if (!isset($this->fields['what'])) $this->fields['what'] = '';
			if (!isset($this->fields['what2'])) $this->fields['what2'] = '';
			$this->what = stripslashes($this->fields['what']);
			$this->fields['what'] = bab_toHtml(stripslashes($this->fields['what']));
			$this->fields['what2'] = bab_toHtml(stripslashes($this->fields['what2']));
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
			$this->t_search_files_only = bab_translate("Search attached files only");
			$this->t_index_priority = bab_translate("Give priority to file content for order");
			$this->tags_txt = bab_translate("Keywords of the thesaurus");
			
			$this->pat = bab_toHtml(bab_rp('pat'));
			$this->field = bab_rp('field');
			$this->order = bab_rp('order');
			$this->atleastone_txt = bab_translate("Or");
			$this->all_txt = bab_translate("And");

			$this->index = bab_searchEngineInfos();
			$this->search_files_only = isset($_POST['search_files_only']);

			$this->beforelink = $GLOBALS['babUrlScript']."?tg=month&callback=beforeJs&ymin=100&ymax=10&month=".date('m')."&year=".date('Y');
			$this->afterlink = $GLOBALS['babUrlScript']."?tg=month&callback=afterJs&ymin=100&ymax=10&month=".date('m')."&year=".date('Y');

			$this->author_url = $GLOBALS['babUrlScript']."?tg=search&idx=browauthor&cb=";

			
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

			$this->el_to_init = Array ( 'a_author', 'a_author_memo', 'a_authorid','a_dd','a_mm','a_yyyy','before','after','before_display' ,'after_display','before_memo','after_memo','what2','what','advenced', 'tagsname');

			for ($i =0 ;$i < FIELDS_TO_SEARCH ; $i++)
				$this->el_to_init[] = "dirfield_".$i;

			
			if ((isset($this->fields['idx']) && $this->fields['idx'] != "find")||((!isset($this->fields['a_mm']))&&(!isset($this->fields['g_mm'])))) 
				{
				foreach($this->el_to_init as  $value)
					if (! isset($this->fields[$value])) $this->fields[$value] = "";
				}

			$this->busetags = false;
			$restc = $babDB->db_query("select tt.id,tt.category, tt.id_cat, tc.title, tt.busetags from ".BAB_TOPICS_TBL." tt left join ".BAB_TOPICS_CATEGORIES_TBL." tc on tt.id_cat=tc.id order by tt.category, tc.title ");
			$this->arrtopicscategories = array();
			while( $row = $babDB->db_fetch_array($restc))
				{
				if( bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $row['id']))
					{
					$this->arrtopics[$row['id_cat']][] = array('id'=>$row['id'], 'category'=>$row['category']);
					if(  $row['busetags'] == 'Y' )
						{
						$this->busetags = true;
						}
					if( !isset($this->arrtopicscategories[$row['id_cat']]))
						{
						$this->arrtopicscategories[$row['id_cat']] = $row['title'];
						}
					}
				}

			$this->counttopicscategories = count($this->arrtopicscategories);

			if( $item == 'e') // Files
				{
				$this->busetags = true;
				}

			if( $this->busetags )
				{
				$babBody->addJavascriptFile($GLOBALS['babScriptPath']."prototype/prototype.js");
				$babBody->addJavascriptFile($GLOBALS['babScriptPath']."scriptaculous/scriptaculous.js");
				$babBody->addStyleSheet('ajax.css');
				}

			$req = "select D.id, D.name, D.id_group id_group from ".BAB_DB_DIRECTORIES_TBL." D,".BAB_GROUPS_TBL." G where D.id_group = '0' OR (D.id_group = G.id AND G.directory='Y') order by D.name";
			$this->resdirs = $babDB->db_query($req);
			$i = 0;
			while ($arr = $babDB->db_fetch_array($this->resdirs) )
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
			$this->resfields = $babDB->db_query($req);
			$this->countfields = $babDB->db_num_rows($this->resfields);
			$i = 0;
			while ($arr = $babDB->db_fetch_array($this->resfields))
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

			if ($item == 'h')
				{
				$this->rescal = array_merge(getAvailableUsersCalendars(),getAvailableGroupsCalendars(),getAvailableResourcesCalendars());
				
				foreach ($this->rescal as $k => $arr)
					{
					$this->rescal[$arr['name']] = $arr;
					unset($this->rescal[$k]);
					}
				ksort($this->rescal);
				$this->rescal = array_values($this->rescal);
				
				$this->countcal = count($this->rescal);
				}

			$this->acces['a'] = true;
			$this->acces['b'] = false;
			$this->acces['c'] = false;
			$this->acces['d'] = false;
			$this->acces['e'] = false;
			$this->acces['f'] = $GLOBALS['BAB_SESS_LOGGED'] && bab_contactsAccess();
			$this->acces['g'] = $this->countdirs;
			$this->acces['h'] = bab_calendarAccess();

			$res = $babDB->db_query("select id from ".BAB_FORUMS_TBL);
			while( $row = $babDB->db_fetch_array($res))
				{
				if(bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $row['id']))
					{
					$this->acces['b'] = true;
					break;
					}
				}

			$res = $babDB->db_query("select id from ".BAB_FAQCAT_TBL);
			while( $row = $babDB->db_fetch_array($res))
				{
				if(bab_isAccessValid(BAB_FAQCAT_GROUPS_TBL, $row['id']))
					{
					$this->acces['c'] = true;
					break;
					}
				}
			$this->acces['d'] = $GLOBALS['BAB_SESS_LOGGED'] && bab_notesAccess();
			
			$oFileManagerEnv =& getEnvObject();
			if($oFileManagerEnv->oAclFm->userHaveStorage() || $oFileManagerEnv->oAclFm->haveRightOnCollectiveFolder())
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
			asort($this->searchItems);
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
				$this->topicid = $this->arrtopics[$this->id_cat][$i]['id'];
				$this->topictitle = bab_toHtml($this->arrtopics[$this->id_cat][$i]['category']);
				$this->selected = isset($this->fields['a_topic']) ? $this->topicid == $this->fields['a_topic'] : '';
				$i++;
				return true;
				}
			else
				{
				$i=0;
				return false;
				}
			}
		
		function getnexttopiccategory() 
			{
			static $i = 0;
			if (list($this->id_cat, $title) = each($this->arrtopicscategories))
				{
				$this->topiccategorytitle = bab_toHtml($title);
				$this->counttopics = count($this->arrtopics[$this->id_cat]);
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}

		function getnextdir() 
			{
			global $babDB;
			static $l = 0;
			if( $l < $this->countdirs)
				{
				
                $arr = $this->dirarr[$l];
				
				$this->topicid = $arr['id'];
				$this->selected = isset($this->fields['g_directory']) && $this->fields['g_directory'] == $arr['id'];

				$this->topictitle = bab_toHtml($arr['name']);

				$req = "select df.id, dfd.name from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." dfd left join ".BAB_DBDIR_FIELDSEXTRA_TBL." df ON df.id_directory=dfd.id_directory and df.id_field = ( ".BAB_DBDIR_MAX_COMMON_FIELDS." + dfd.id ) where df.id_directory='".(($arr['id_group']==0) ? $arr['id'] : 0)."'";
				$res = $babDB->db_query($req);

				$lk = 0;
				while ($arr = $babDB->db_fetch_array($res))
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
					{
					
					$this->selected = "selected";
					}
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
				
				$this->value = isset($this->fields['dirfield_'.$j]) ? $this->fields['dirfield_'.$j] : '';
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
				$this->caltitle = bab_toHtml($this->rescal[$i]['name']);
				$this->selected = isset($this->fields['h_calendar']) && $this->rescal[$i]['idcal'] == $this->fields['h_calendar'];
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
		
		if (1 === $this->countpages) {
			$this->pages = bab_translate("Page");
		}
		
		
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
			
		if (1 === $this->count) {
			$this->results = bab_translate("Result");
		}

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
		var $altbg = true;

		function temp( $item, $what, $order, $option ,$navitem ,$navpos )
			{
			global $BAB_SESS_USERID, $babLimit, $babBody, $babDB;
			
			$this->search = bab_translate("Search");
			$this->arttitle = bab_translate("Articles");
			$this->comtitle = bab_translate("Comments");
			$this->fortitle = bab_translate("Forums");
			$this->faqtitle = bab_translate("Faq");
			$this->nottitle = bab_translate("Notes");
			$this->filtitle = bab_translate("File manager");
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
			$this->t_archive = bab_translate("archive");
			$this->t_from = bab_translate("date_from");
			$this->t_to = bab_translate("date_to");
			$this->t_private = bab_translate("Private");

			$navpos = (int) $navpos;
			$this->fields = $_POST;


			$this->like2 = trim($what);
			$this->like = isset($this->fields['what2']) ? trim($this->fields['what2']) : '';
		

			$this->option = &$option;

			
			$this->what = urlencode($what." ".$this->like);
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
				$req = "create temporary table artresults SELECT a.id, a.id_topic, a.archive, a.title, a.head, a.body, a.restriction, T.category topic, concat( U.lastname, ' ', U.firstname ) author,U.email, UNIX_TIMESTAMP(a.date) date from ".BAB_TOPICS_TBL." T, ".BAB_ARTICLES_TBL." a LEFT JOIN ".BAB_USERS_TBL." U ON a.id_author = U.id WHERE a.id_topic = T.id and 0";
				$babDB->db_query($req);
				$req = "alter table artresults add unique (id)";
				$babDB->db_query($req);

				$req = "create temporary table comresults select C.id, C.id_article, C.id_topic, C.subject,C.message, UNIX_TIMESTAMP(C.date) date, name,email, C.id_author, a.title arttitle, a.body,a.restriction, T.category topic from ".BAB_COMMENTS_TBL." C, ".BAB_ARTICLES_TBL." a, ".BAB_TOPICS_TBL." T where C.id_article=a.id and a.id_topic = T.id and 0";
				$babDB->db_query($req);
				$req = "alter table comresults add unique (id)";
				$babDB->db_query($req); 
				
				$crit_art = "";
				$crit_com = "";
				if (isset($this->fields['a_author_memo']) && trim($this->fields['a_author_memo']) != "")
					{
					if( $GLOBALS['BAB_SESS_USERID'])
						{
						$crit_art = " and (U.id='".$babDB->db_escape_string($this->fields['a_authorid'])."')";
						$crit_com = " and (".finder($this->fields['a_author_memo'],"name",$option)." OR C.id_author='".$babDB->db_escape_string($this->fields['a_authorid'])."')";
						}
					else
						{					
						$crit_art = " and (".finder($this->fields['a_author_memo'],"concat(U.lastname,U.firstname)",$option).")";
						$crit_com = " and (".finder($this->fields['a_author_memo'],"name",$option).")";
						}
					}

				if (isset($this->fields['a_topic']) && trim($this->fields['a_topic']) != "")
					{
					$crit_art .= " and id_topic = ".$babDB->quote($this->fields['a_topic']);
					$crit_com .= " and C.id_topic = ".$babDB->quote($this->fields['a_topic']);
					}

				if (isset($this->fields['after']) && trim($this->fields['after']) != "")
					{
					$crit_art .= " and a.date >= '".$babDB->db_escape_string($this->fields['after'])." 00:00:00'";
					$crit_com .= " and C.date >= '".$babDB->db_escape_string($this->fields['after'])." 00:00:00'";
					}
				if (isset($this->fields['before']) && trim($this->fields['before']) != "")
					{
					$crit_art .= " and a.date <= '".$babDB->db_escape_string($this->fields['before'])." 23:59:59'";
					$crit_com .= " and C.date <= '".$babDB->db_escape_string($this->fields['before'])." 23:59:59'";
					}

				$inart = (is_array($babBody->topview) && count($babBody->topview) > 0 ) ? "and id_topic in (".$babDB->quote(array_keys($babBody->topview)).")" : "and id_topic ='0'";
				$incom = (is_array($babBody->topview) && count($babBody->topview) > 0 ) ? "and C.id_topic in (".$babDB->quote(array_keys($babBody->topview)).")" : "and C.id_topic ='0'";

				$reqsup = '';
				if (!empty($inart))
					{
					$arrids = array();
					$restrictedart = '';
					if( isset($this->fields['tagsname']) && !empty($this->fields['tagsname']))
						{
						$incom = ''; /* don't display comments in search result */
						$this->fields['tagsname'] = trim($this->fields['tagsname']);
						if( !empty($this->fields['tagsname']))
							{
							$maptags = array();
							$tags = array();
							$atags = explode(',', $this->fields['tagsname']);
							for( $k = 0; $k < count($atags); $k++ )
							{
								$atags[$k] = trim($atags[$k]);
								if( !empty($atags[$k]))
									{
									$tags[] = "'".$babDB->db_escape_string($atags[$k])."'";
									}

							}
							if( count($tags))
								{
								$res = $babDB->db_query("select id_art, tag_name from ".BAB_ART_TAGS_TBL." att left join ".BAB_TAGS_TBL." tt on tt.id = att.id_tag WHERE tag_name = ".implode(' or tag_name = ', $tags));
								while( $rr = $babDB->db_fetch_array($res))
									{
									$maptags[$rr['tag_name']][] = $rr['id_art'];
									$arrids[] = $rr['id_art'];
									}
								$optags = intval(bab_pp('optags', 1));
								if( $optags == 0 && count($maptags))
									{
									list(,$arrids) = each($maptags);
									while( list(,$t) = each($maptags) )
										{
										$arrids = array_intersect($arrids, $t);
										}
									}
								}

							if( $arrids )
								{
								$restrictedart = ' AND a.id in ('.$babDB->quote($arrids).')';
								}
							else
								{
								$restrictedart = ' AND 0';
								}
							}
						}

					if (!isset($_POST['search_files_only'])) {

						if ($this->like || $this->like2)
							$reqsup = "AND (
						".finder($this->like,"title",$option,$this->like2)." OR 
						".finder($this->like,"head",$option,$this->like2)." OR 
						".finder($this->like,"body",$option,$this->like2)." OR 
						".finder($this->like,"f.name",$option,$this->like2)." OR 
						".finder($this->like,"f.description",$option,$this->like2)." 
						)";
						
						$req = "
						
						INSERT INTO artresults 
						SELECT 
							a.id, 
							a.id_topic, 
							a.archive, 
							a.title title,
							a.head, 
							LEFT(a.body,100) body, 
							a.restriction, 
							T.category topic, 
							concat( U.lastname, ' ', U.firstname ) author, 
							U.email, UNIX_TIMESTAMP(a.date) date  
						FROM 
							".BAB_TOPICS_TBL." T, 
							".BAB_ARTICLES_TBL." a 
						LEFT JOIN 
							".BAB_USERS_TBL." U ON a.id_author = U.id 
						LEFT JOIN 
							".BAB_ART_FILES_TBL." f ON f.id_article = a.id 

						WHERE 
							a.id_topic = T.id ".$reqsup." ".$inart." ".$crit_art.$restrictedart."  
							GROUP BY a.id 
							";

						bab_debug($req);
						$babDB->db_query($req);
					}

					// indexation

					if ($engine = bab_searchEngineInfos()) {
						if (!$engine['indexes']['bab_art_files']['index_disabled']) {
							$found_files = bab_searchIndexedFiles($this->like2, $this->like, $option, 'bab_art_files');


							$file_path = array();
							foreach($found_files as $arr) {
								$file_path[] = bab_removeUploadPath($arr['file']);
							}

							

							$this->tmptable_inserted_id('artresults');

							$req = "
					
							INSERT INTO artresults 
							SELECT 
								a.id, 
								a.id_topic, 
								a.archive, 
								a.title title,
								a.head, 
								LEFT(a.body,100) body, 
								a.restriction, 
								T.category topic, 
								concat( U.lastname, ' ', U.firstname ) author, 
								U.email, UNIX_TIMESTAMP(a.date) date  
							FROM 
								".BAB_TOPICS_TBL." T, 
								".BAB_ARTICLES_TBL." a
								LEFT JOIN 
								".BAB_USERS_TBL." U ON a.id_author = U.id , 
								".BAB_ART_FILES_TBL." f, 
								".BAB_INDEX_ACCESS_TBL." i 
							
							WHERE 
								f.id_article = a.id AND 
								f.id = i.id_object AND 
								i.id_object_access = T.id AND 
								i.file_path IN(".$babDB->quote($file_path).") AND 
								a.id NOT IN(".$babDB->quote($this->tmp_inserted_id).") AND 
								a.id_topic = T.id ".$inart." ".$crit_art.$restrictedart." 
							GROUP BY a.id ORDER BY ".$babDB->db_escape_string($order)."  
								";

							bab_debug($req);

							$babDB->db_query($req);
						}
					}

					$res = $babDB->db_query("select id, restriction from artresults where restriction!=''");
					while( $rr = $babDB->db_fetch_array($res))
						{
						if( !bab_articleAccessByRestriction($rr['restriction']))
							{
							$babDB->db_query("delete from artresults where id=".$babDB->quote($rr['id']));
							}
						}
					}

				if (!empty($incom))
					{
					if ($this->like || $this->like2)
						$reqsup = "and (".finder($this->like,"subject",$option,$this->like2)." or ".finder($this->like,"message",$option,$this->like2).")";

					$req = "insert into comresults select C.id, C.id_article, C.id_topic, C.subject,C.message, UNIX_TIMESTAMP(C.date) date, name author,email,C.id_author,  a.title arttitle,LEFT(a.body,100) body, a.restriction, T.category topic  from ".BAB_COMMENTS_TBL." C, ".BAB_ARTICLES_TBL." a, ".BAB_TOPICS_TBL." T where C.id_article=a.id and a.id_topic = T.id ".$reqsup." and C.confirmed='Y' ".$incom." ".$crit_com." order by ".$babDB->db_escape_string($order);
					$babDB->db_query($req);
					$res = $babDB->db_query("select id, restriction from comresults where restriction!=''");
					while( $rr = $babDB->db_fetch_array($res))
						{
						if( !bab_articleAccessByRestriction($rr['restriction']))
							$babDB->db_query("delete from comresults where id=".$babDB->quote($rr['id']));
						}
					}

				$req = "select count(*) from artresults";
				$res = $babDB->db_query($req);
				list($nbrows) = $babDB->db_fetch_row($res);
				$this->nbresult += $nbrows;

				if ($navitem != "a") $navpos=0;
				$req = "select * from artresults ORDER BY ".$babDB->db_escape_string($order)." limit ".$navpos.", ".$babLimit;

				$this->resart = $babDB->db_query($req);
				$this->countart = $babDB->db_num_rows($this->resart);
				if( !$this->counttot && $this->countart > 0 )
					$this->counttot = true;
				$this->navbar_a = navbar($babLimit,$nbrows,"a",$navpos);

				$navpos=$this->navpos;
				if ($navitem != "ac") $navpos=0;
				$req = "select count(*) from comresults";
				$res = $babDB->db_query($req);
				list($nbrows) = $babDB->db_fetch_row($res);

				$this->navbar_ac = navbar($babLimit,$nbrows,"ac",$navpos);
				$req = "select * from comresults limit ".$navpos.", ".$babLimit;

				$this->rescom = $babDB->db_query($req);
				$this->countcom = $babDB->db_num_rows($this->rescom);
				if( !$this->counttot && $this->countcom > 0 )
					$this->counttot = true;
				}
				if (!isset($nbrows)) $nbrows = 0;
				$this->nbresult += $nbrows;

			// ------------------------------------------------- POSTS
			if( empty($item) || $item == "b")
				{
				$req = "create temporary table forresults select id, id_thread, subject topic, id id_topic, subject title,message, author, 'dd-mm-yyyy' date from ".BAB_POSTS_TBL." where 0";
				$babDB->db_query($req);
				$req = "alter table forresults add unique (id)";
				$babDB->db_query($req);
				$req = "select id from ".BAB_FORUMS_TBL."";
				$res = $babDB->db_query($req);
				$idthreads = array();
				while( $row = $babDB->db_fetch_array($res))
					{
					if(bab_isAccessValid(BAB_FORUMSVIEW_GROUPS_TBL, $row['id']))
						{
						$req = "select id from ".BAB_THREADS_TBL." where forum=".$babDB->quote($row['id']);
						$res2 = $babDB->db_query($req);
						while( $r = $babDB->db_fetch_array($res2))
							{
							$idthreads[] = $r['id'];
							}
						}
					}
				$temp1 = finder($this->like,"b.subject",$option,$this->like2);
				$temp2 = finder($this->like,"b.message",$option,$this->like2);
				if ($temp1 != "" && $temp2 != "")
					$plus = "( ".$temp1." or ".$temp2.") and";
				else $plus = "";
				if (0 < count($idthreads)) { 
					$req = "
					
					INSERT INTO 
						forresults 
					SELECT 
						b.id, 
						b.id_thread, 
						F.name topic, 
						F.id id_topic, 
						b.subject title,
						b.message, 
						author, 
						UNIX_TIMESTAMP(b.date) date 
					FROM 
						".BAB_POSTS_TBL." b, 
						".BAB_THREADS_TBL." T, 
						".BAB_FORUMS_TBL." F 
					WHERE 
						b.id_thread=T.id 
						AND T.forum=F.id 
						AND ".$plus." b.confirmed='Y' 
						AND b.id_thread IN (".$babDB->quote($idthreads).")";

					$babDB->db_query($req);
				}



				// indexation

				if ($idthreads != "" && $engine = bab_searchEngineInfos()) {
					if (!$engine['indexes']['bab_forumsfiles']['index_disabled']) {
						$found_files = bab_searchIndexedFiles($this->like2, $this->like, $option, 'bab_forumsfiles');


						$file_path = array();
						foreach($found_files as $arr) {
							$file_path[] = bab_removeUploadPath($arr['file']);
						}

						$this->tmptable_inserted_id('forresults');
						
						


						$req = "
					
							INSERT INTO 
								forresults 
							SELECT 
								b.id, 
								b.id_thread, 
								F.name topic, 
								F.id id_topic, 
								b.subject title,
								b.message, 
								author, 
								UNIX_TIMESTAMP(b.date) date 
							FROM 
								".BAB_POSTS_TBL." b,  
								".BAB_THREADS_TBL." T, 
								".BAB_FORUMS_TBL." F, 
								".BAB_FORUMSFILES_TBL." fi,
								".BAB_INDEX_ACCESS_TBL." i 
							WHERE 
								b.id_thread=T.id 
								AND T.forum=F.id 
								AND fi.id_post = b.id 
								AND i.id_object_access = F.id 
								AND i.id_object = fi.id 
								AND i.file_path IN(".$babDB->quote($file_path).") 
								AND b.confirmed='Y' 
								AND b.id_thread IN (".$babDB->quote($idthreads).")";

						if ($this->tmp_inserted_id) {
							$req .= "AND b.id NOT IN(".$babDB->quote($this->tmp_inserted_id).") ";
						}

						$req .= " GROUP BY b.id";

						$babDB->db_query($req);

						bab_debug($req);

						}
					}






				$req = "SELECT count(*) FROM forresults";
				$res = $babDB->db_query($req);
				list($nbrows) = $babDB->db_fetch_row($res);
				$navpos = $this->navpos;
				if ($navitem != "b") $navpos = 0;
				$this->navbar_b = navbar($babLimit,$nbrows,"b",$navpos);
			
				$req = "select * from forresults ORDER BY ".$babDB->db_escape_string($order)." LIMIT ".$navpos.", ".$babLimit;
				$this->resfor = $babDB->db_query($req);
				$this->countfor = $babDB->db_num_rows($this->resfor);
				if( !$this->counttot && $this->countfor > 0 )
					$this->counttot = true;
				}
				$this->nbresult += $nbrows;

			// ---------------------------------------------- FAQ
			if( empty($item) || $item == "c")
				{
				$req = "create temporary table faqresults select id, idcat, question title, response, question topic from ".BAB_FAQQR_TBL." where 0";
				$babDB->db_query($req);
				$req = "alter table faqresults add unique (id)";
				$babDB->db_query($req);
				$req = "select id from ".BAB_FAQCAT_TBL."";
				$res = $babDB->db_query($req);

				$idcat = "";
				while( $row = $babDB->db_fetch_array($res))
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
					$req = "insert into faqresults select T.id, idcat, question title, response, category topic from ".BAB_FAQQR_TBL." T, ".BAB_FAQCAT_TBL." C where idcat=C.id and ".$plus." idcat in (".substr($idcat,0,-1).") order by ".$babDB->db_escape_string($order);
					$babDB->db_query($req);
				}


				$req = "select count(*) from faqresults";
				$res = $babDB->db_query($req);
				list($nbrows) = $babDB->db_fetch_row($res);
				$navpos = $this->navpos;
				if ($navitem != "c") $navpos = 0;
				$this->navbar_c = navbar($babLimit,$nbrows,"c",$navpos);

				$req = "select * from faqresults limit ".$navpos.", ".$babLimit;
				$this->resfaq = $babDB->db_query($req);
				$this->countfaq = $babDB->db_num_rows($this->resfaq);
				if( !$this->counttot && $this->countfaq > 0 )
					$this->counttot = true;
				}
				$this->nbresult += $nbrows;

			// ------------------------------------------------------------------------ PERSONAL NOTES
			if( (empty($item) || $item == "d") && !empty($BAB_SESS_USERID))
				{

				$plus = finder($this->like,"content",$option,$this->like2);
				if ($plus != "") $plus = "($plus) and";
				$req = "select count(*) from ".BAB_NOTES_TBL." where ".$plus." id_user=".$babDB->quote($BAB_SESS_USERID);
				$res = $babDB->db_query($req);
				list($nbrows) = $babDB->db_fetch_row($res);
				$navpos = $this->navpos;
				if ($navitem != "d") $navpos = 0;
				$this->navbar_d = navbar($babLimit,$nbrows,"d",$navpos);

				$req = "select id, content, UNIX_TIMESTAMP(date) date from ".BAB_NOTES_TBL." where ".$plus." id_user=".$babDB->quote($BAB_SESS_USERID)." limit ".$navpos.", ".$babLimit;
				$this->resnot = $babDB->db_query($req);
				$this->countnot = $babDB->db_num_rows($this->resnot);
				if( !$this->counttot && $this->countnot > 0 )
					$this->counttot = true;
				}
				$this->nbresult += $nbrows;

			// ------------------------------------- FILES
			if( empty($item) || $item == "e")
				{

				define('BAB_TYPE_MATCH_DATABASE'	, 1);
				define('BAB_TYPE_MATCH_VERSION'		, 2);
				define('BAB_TYPE_MATCH_FILE'		, 3);
				


				$req = "
					
					CREATE TEMPORARY TABLE filresults 
					
					SELECT 
						id, 
						name title, 
						id_owner, 
						description, 
						'dd-mm-yyyy' datec, 
						'dd-mm-yyyy' datem, 
						path, 
						bgroup, 
						name author, 
						path folder, 
						'".BAB_TYPE_MATCH_DATABASE."' type_match, 
						'0' version_count 
					FROM 
						".BAB_FILES_TBL." 
					WHERE 0
				";
				$babDB->db_query($req);
				$req = "ALTER TABLE filresults add unique (id)";
				$babDB->db_query($req);
				$private = false;

				$sFolderWhereClauseItem = '';
				{
					$aCollectiveFolderRelativePath = array();
					$aCollectiveFolderId = array();
					$aUserFolderRelativePath = array();
					{
						$aIdFolder = array();
						$aDownload = bab_getUserIdObjects(BAB_FMDOWNLOAD_GROUPS_TBL);
						if(is_array($aDownload) && count($aDownload) > 0)
						{
							$aIdFolder = $aDownload;
						}
						
						$aManager = bab_getUserIdObjects(BAB_FMMANAGERS_GROUPS_TBL);
						if(is_array($aManager) && count($aManager) > 0)
						{
							$aIdFolder += $aManager;
						}
						
						foreach($aIdFolder as $iIdFolder)
						{
							$oFmFolder = BAB_FmFolderHelper::getFmFolderById($iIdFolder);
							if(!is_null($oFmFolder))
							{
								$sRelativePath = (strlen(trim($oFmFolder->getRelativePath())) > 0 ? $oFmFolder->getRelativePath() : $oFmFolder->getName() . '/');
								$aCollectiveFolderId[$iIdFolder] = $iIdFolder;
								$aCollectiveFolderRelativePath[$sRelativePath] = 'F.path LIKE \'' . $babDB->db_escape_like($sRelativePath) . '%\'';
							}
						}
						
						$oFileManagerEnv =& getEnvObject();
						if($oFileManagerEnv->oAclFm->userHaveStorage())
						{
							$aUserFolderRelativePath[$BAB_SESS_USERID] = 'U' . $BAB_SESS_USERID . '/';
						}
					}
					
					$aFolderWhereClauseItem = array();
					if(count($aCollectiveFolderRelativePath) > 0)
					{
						$aFolderWhereClauseItem[] = '(F.id_owner in (' . $babDB->quote($aCollectiveFolderId) . 
							') AND (' . implode(' OR ', $aCollectiveFolderRelativePath) . '))';
					}
					
					if(count($aUserFolderRelativePath) > 0)
					{
						$aFolderWhereClauseItem[] = '(F.id_owner in (' . $babDB->quote(array_keys($aUserFolderRelativePath)) . 
							') AND (F.path LIKE \'' . $babDB->db_escape_like($aUserFolderRelativePath[$BAB_SESS_USERID]) . '%\'' . '))';
					}
					
					if(count($aFolderWhereClauseItem) > 0)
					{
						$sFolderWhereClauseItem = 'AND (' . implode(' OR ', $aFolderWhereClauseItem) . ')';
					}
				}

				$plus = "";
				$temp1 = finder($this->like, "F.name",		$option,$this->like2);
				$temp2 = finder($this->like, "description", $option,$this->like2);
				$temp3 = finder($this->like, "F.path",		$option,$this->like2);
				$temp4 = finder($this->like, "R.folder",	$option,$this->like2);
				$temp5 = finder($this->like, "M.fvalue",	$option,$this->like2);
				//$temp6 = finder($this->like, "tag_name",	$option,$this->like2);
				$this->fields['tagsname'] = isset($this->fields['tagsname'])?trim($this->fields['tagsname']):'';
				$tidfiles = array();
				if( !empty($this->fields['tagsname']))
					{
					$maptags = array();
					$tags = array();
					$atags = explode(',', $this->fields['tagsname']);
					for( $k = 0; $k < count($atags); $k++ )
					{
						$atags[$k] = trim($atags[$k]);
						if( !empty($atags[$k]))
							{
							$maptags[$atags[$k]] = array();
							$tags[] = "'".$babDB->db_escape_string($atags[$k])."'";
							}

					}
					if( count($tags))
						{
						$res = $babDB->db_query("select id_file, tag_name from ".BAB_FILES_TAGS_TBL." att left join ".BAB_TAGS_TBL." tt on tt.id = att.id_tag WHERE tag_name = ".implode(' or tag_name = ', $tags));
						while( $rr = $babDB->db_fetch_array($res))
							{
							$maptags[$rr['tag_name']][] = $rr['id_file'];
							$tidfiles[] = $rr['id_file'];
							}
						$optags = intval(bab_pp('optags', 1));
						if( $optags == 0 && count($maptags))
							{
							list(,$tidfiles) = each($maptags);
							while( list(,$t) = each($maptags) )
								{
								$tidfiles = array_intersect($tidfiles, $t);
								}
							}
						}

					}

				$idfiles = '';
				if( count($tidfiles ))
					{
					$idfiles = implode(',', $tidfiles);
					}
				else
					{
					$idfiles = '';
					}
				if (($temp1 != '' ) || $idfiles != '')
				{
					if( $temp1 )
						{
						$plus = "( ".$temp1." or ".$temp2." or ".$temp3." or ".$temp4."";
						if( $idfiles )
							{
							$plus .= " or F.id in(".$idfiles.")";
							}
						$plus .= ") ";
						}
					else
						{
						$plus = "(F.id in(".$idfiles."))";
						}
				}
				else
				{ 
					$plus = '';
				}

                if ($plus && $sFolderWhereClauseItem != "") 
					{

					// indexation

					if ($engine = bab_searchEngineInfos()) { 
						if (!$engine['indexes']['bab_files']['index_disabled']) {

							$found_files = bab_searchIndexedFiles($this->like2, $this->like, $option, 'bab_files');
							$current_version = array();
							$old_version = array();
							foreach($found_files as $arr) {
								$fullpath = bab_removeUploadPath($arr['file']);

								$name = basename($fullpath);
								$path = dirname($fullpath);
								if( !empty($path) && '/' !== $path{strlen($path) - 1}) 
								{
									$path .='/';
								}
								
								$current_version[] = '(F.path=\''.$babDB->db_escape_string($path).'\' AND F.name=\''. $babDB->db_escape_string($name)."')";

								if (preg_match( "/OVF\/\d,\d,(.*)/", $fullpath)) {
									$old_version[] = $babDB->db_escape_string($fullpath);
								}
							}


							if ($current_version) {
								// match found in last version
								$req = "INSERT INTO filresults 
										SELECT 
											F.id, 
											F.name title, 
											F.id_owner, 
											description, 
											UNIX_TIMESTAMP(created) datec, 
											UNIX_TIMESTAMP(modified) datem, 
											F.path, 
											bgroup, 
											concat( U.lastname, ' ', U.firstname ) author, 
											folder, 
											'".BAB_TYPE_MATCH_FILE."', 
											'0'
										FROM ".BAB_FM_FOLDERS_TBL." R, 
											".BAB_FILES_TBL." F 
										LEFT JOIN ".BAB_USERS_TBL." U 
											ON F.author=U.id 
										WHERE 
											(F.id_owner=R.id OR F.bgroup='N') 
											AND (".implode(' OR ',$current_version).")  
											$sFolderWhereClauseItem 
											and state='' and confirmed='Y' 
										GROUP BY F.id";

								bab_debug($req);

								$babDB->db_query($req);

								}


							// $this->tmptable_inserted_id('filresults');
						

							// match found in old version
							
							$req = "REPLACE INTO filresults 
									SELECT 
										F.id, 
										F.name title, 
										F.id_owner, 
										description, 
										UNIX_TIMESTAMP(created) datec, 
										UNIX_TIMESTAMP(modified) datem, 
										F.path, 
										bgroup, 
										concat( U.lastname, ' ', U.firstname ) author, 
										folder, 
										'".BAB_TYPE_MATCH_VERSION."', 
										COUNT(R.id)  
									FROM ".BAB_FM_FOLDERS_TBL." R, 
										".BAB_FILES_TBL." F 
									LEFT JOIN ".BAB_USERS_TBL." U 
										ON F.author=U.id, 
										".BAB_FM_FILESVER_TBL." v, 
										".BAB_INDEX_ACCESS_TBL." a 
									WHERE 
										(F.id_owner=R.id OR F.bgroup='N') 
										AND v.id_file = F.id 
										AND a.id_object = v.id 
										AND a.file_path IN('".implode("', '",$old_version)."') 
										$sFolderWhereClauseItem 
										and state='' 
										and F.confirmed='Y' 
										
									GROUP BY F.id 
									";

							bab_debug($req);

							$babDB->db_query($req);

						}
					}

					$this->tmptable_inserted_id('filresults');
					
					$sPlus = (strlen($plus) > 0) ? 'and ' . $plus . ' ' : ' ';
					
					$req = "INSERT INTO filresults 
						SELECT 
							F.id, 
							F.name title, 
							F.id_owner, 
							description, 
							UNIX_TIMESTAMP(created) datec, 
							UNIX_TIMESTAMP(modified) datem, 
							path, 
							bgroup, 
							concat( U.lastname, ' ', U.firstname ) author, 
							folder, 
							'".BAB_TYPE_MATCH_DATABASE."',
							'0'
						FROM ".BAB_FM_FOLDERS_TBL." R, 
							".BAB_FILES_TBL." F 
						LEFT JOIN ".BAB_USERS_TBL." U 
							ON F.author=U.id 
						WHERE 
							(F.id_owner=R.id OR F.bgroup='N') 
							$sPlus $sFolderWhereClauseItem 
							AND state='' and confirmed='Y' 
							AND F.id NOT IN('".implode("','",$this->tmp_inserted_id)."') 
						GROUP BY 
							F.id ";
					bab_debug($req);
                    $babDB->db_query($req);
					
					// additional fields
					if ($temp5 != "")
						{
						$this->tmptable_inserted_id('filresults');

						$req = "
						INSERT INTO filresults 
							SELECT 
								F.id, 
								F.name title, 
								F.id_owner, 
								description, 
								UNIX_TIMESTAMP(created) datec, 
								UNIX_TIMESTAMP(modified) datem, 
								path, 
								bgroup, 
								concat( U.lastname, ' ', U.firstname ) author, 
								folder,
								'".BAB_TYPE_MATCH_DATABASE."',
								'0'
							FROM ".BAB_FM_FIELDSVAL_TBL." M, 
							".BAB_FM_FOLDERS_TBL." R, 
							".BAB_FILES_TBL." F 
							LEFT JOIN ".BAB_USERS_TBL." U 
							ON F.author=U.id 
						WHERE 
							".$temp5." 
							AND M.id_file=F.id 
							AND (F.id_owner=R.id OR F.bgroup='N') 
							$sFolderWhereClauseItem 
							AND state='' and confirmed='Y' 
							AND F.id NOT IN('".implode("','",$this->tmp_inserted_id)."') 
						GROUP BY 
							F.id ";

						bab_debug($req);
						$babDB->db_query($req);
						}


                    }


				$req = "select count(*) from filresults";
				$res = $babDB->db_query($req);
				list($nbrows) = $babDB->db_fetch_row($res);



				$navpos = $this->navpos;
				if ($navitem != "e") $navpos = 0;
				$this->navbar_e = navbar($babLimit,$nbrows,"e",$navpos);

				$req = "SELECT * FROM filresults ";

				if (isset($_POST['index_priority'])) {
					$req .= " ORDER BY type_match DESC,".$babDB->db_escape_string($order);
				} else {
					$req .= " ORDER BY ".$babDB->db_escape_string($order);
				}

				$req .= " LIMIT ".$navpos.", ".$babLimit;
		

				$this->resfil = $babDB->db_query($req);
				$this->countfil = $babDB->db_num_rows($this->resfil);
				if( !$this->counttot && $this->countfil > 0 )
					$this->counttot = true;
				}
				$this->nbresult += $nbrows;
				$nbrows = null;

			
			// ------------------------------------------------ PERSONAL CONTACTS
			if( empty($item) || $item == "f")
				{
				$req = "create temporary table conresults select lastname title, firstname, compagny, email, id from ".BAB_CONTACTS_TBL." where 0";
				$babDB->db_query($req);
				$req = "alter table conresults add unique (id)";
				$babDB->db_query($req);

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

				$req = "insert into conresults select lastname title, firstname, compagny, email, id from ".BAB_CONTACTS_TBL." where ".$plus." owner='".$babDB->db_escape_string($BAB_SESS_USERID)."' order by ".$babDB->db_escape_string($order);
				$babDB->db_query($req);

				$req = "select count(*) from conresults";
				$res = $babDB->db_query($req);
				list($nbrows) = $babDB->db_fetch_row($res);
				$navpos = $this->navpos;
				if ($navitem != "f") $navpos = 0;
				$this->navbar_f = navbar($babLimit,$nbrows,"f",$navpos);

				$req = "select * from conresults limit ".$navpos.", ".$babLimit;
				$this->rescon = $babDB->db_query($req);
				$this->countcon = $babDB->db_num_rows($this->rescon);
				if( !$this->counttot && $this->countcon > 0 )
					$this->counttot = true;
				}
				$this->nbresult += $nbrows;
			// --------------------------------------------- DIRECTORIES
			$arrview = bab_getUserIdObjects(BAB_DBDIRVIEW_GROUPS_TBL);
			
			if( count($arrview) && (empty($item) || $item == "g"))
				{
				$id_directory = isset($this->fields['g_directory']) ? $this->fields['g_directory'] : '';
				
				
				// champs a afficher en résultats
				$this->dirfields = array('name'=>array(),'description'=>array());

				if ('' == $id_directory)
					{
					// tout les annuaires

					list($search_view_fields) = $babDB->db_fetch_array($babDB->db_query("SELECT search_view_fields FROM ".BAB_DBDIR_OPTIONS_TBL.""));

					if (empty($search_view_fields))
						$search_view_fields = '2,4';
						
					$search_view_fields = explode(',',$search_view_fields);

					$rescol = $babDB->db_query("select * from ".BAB_DBDIR_FIELDS_TBL." where id IN(".$babDB->quote($search_view_fields).")");
					while( $row3 = $babDB->db_fetch_array($rescol))
						{
						$this->dirfields['name'][] = $row3['name'];
						$this->dirfields['description'][] = $row3['description'];
						}
					}
				else
					{
					// un seul annuaire
					$row = $babDB->db_fetch_array($babDB->db_query("SELECT * FROM ".BAB_DB_DIRECTORIES_TBL." WHERE id=".$babDB->quote($id_directory).""));
					
					if (BAB_REGISTERED_GROUP === (int) $row['id_group']) {
						$registered_directory = 1;
					}

					$rescol = $babDB->db_query("select * from ".BAB_DBDIR_FIELDSEXTRA_TBL." where id_directory='".($row['id_group'] != 0? 0: $row['id'])."' and ordering!='0' order by ordering asc");
					while( $row3 = $babDB->db_fetch_array($rescol))
						{
						if( $row3['id_field'] < BAB_DBDIR_MAX_COMMON_FIELDS )
							{
							$rr = $babDB->db_fetch_array($babDB->db_query("select name, description from ".BAB_DBDIR_FIELDS_TBL." where id=".$babDB->quote($row3['id_field'])));
							$this->dirfields['name'][] = $rr['name'];
							$this->dirfields['description'][] = translateDirectoryField($rr['description']);
							}
						else
							{
							$rr = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_DBDIR_FIELDS_DIRECTORY_TBL." where id=".$babDB->quote($row3['id_field'] - BAB_DBDIR_MAX_COMMON_FIELDS).""));
							$this->arrcols[] = array("babdirf".$row3['id'], translateDirectoryField($rr['name']), 1);
							$this->dirfields['name'][] = "babdirf".$row3['id'];
							$this->dirfields['description'][] = translateDirectoryField($rr['name']);
							}					
						}
					}

				
				
				// Critères spécifiques
				
				$crit_fields_reg = array();
				$crit_fields_add = array();

				for($i = 0 ; $i < FIELDS_TO_SEARCH ; $i++)
					{
					$dirselect = isset($this->fields['dirselect_'.$i]) ? $this->fields['dirselect_'.$i] : '';
					$dirfield = isset($this->fields['dirfield_'.$i]) ? $this->fields['dirfield_'.$i] : '';
					if ($dirfield !="") 
						{
						if (0 === strpos($dirselect, 'babdirf'))
							{
							// champ supplémentaire
							$crit_fields_add[] = "t.id_fieldx = '".$babDB->db_escape_string(substr($dirselect,7))."' AND t.field_value LIKE '%".$babDB->db_escape_like($dirfield)."%'";
							}
						else
							{
							$crit_fields_reg[] = "e.".$babDB->db_escape_string($dirselect)." LIKE '%".$babDB->db_escape_like($dirfield)."%'";
							//finder($dirfield, 'e.'.$dirselect);
							}
						}
					}

				

				// critères toutes colonnes

				$crit_fields = $this->searchInAllCols(BAB_DBDIR_ENTRIES_TBL);
				
				// Liste des groupe des annuaires

				$arr_grp = array();
				$res = $babDB->db_query("SELECT g.id FROM ".BAB_GROUPS_TBL." g, ".BAB_DB_DIRECTORIES_TBL." d WHERE g.directory='Y' AND d.id_group=g.id AND d.id IN(".$babDB->quote($arrview).")");
				while ($arr = $babDB->db_fetch_array($res))
					{
					$arr_grp[] = $arr['id'];
					}


				// Liste des annuaires base de donnés

				$arr_dir = array();
				$res = $babDB->db_query("SELECT id FROM ".BAB_DB_DIRECTORIES_TBL." WHERE id_group='0' AND id IN(".$babDB->quote($arrview).")");
				while ($arr = $babDB->db_fetch_array($res))
					{
					$arr_dir[] = $arr['id'];
					}

				$crit_fields_reg_str = implode(' AND ', $crit_fields_reg);
				
				if (!empty($crit_fields_reg_str))
					{
				if (!empty($crit_fields))
						$crit_fields .= ' AND '.$crit_fields_reg_str;
					else
						$crit_fields = $crit_fields_reg_str;
					}


				$crit_fields_add_str = implode(' AND ', $crit_fields_add);
				if (!empty($crit_fields_add_str))
					{
					$crit_fields_add_str = '('.$crit_fields_add_str.') AND ';
					}

				// recherche dans les champs supplémentaires avec la recherche principale + recherche avancée
				$additional = finder($this->like,'t.field_value',$this->option,$this->like2);
				if (!empty($additional))
					{
					if( !empty($crit_fields))
						$crit_fields .= ' OR '.$additional;
					else
						$crit_fields = $additional;
					}

				if (!empty($crit_fields))
					{
					$crit_fields = '('.$crit_fields.') AND ';
					}

				// Si un annuaire spécifique est choisit

				if (!empty($id_directory))
					{
					$chosen_dir = $babDB->db_fetch_array($babDB->db_query("SELECT * FROM ".BAB_DB_DIRECTORIES_TBL." WHERE id=".$babDB->quote($id_directory).""));
					
					if ($chosen_dir['id_group'] == BAB_REGISTERED_GROUP)
						{
						$option_dir = " AND e.id_directory='0'";
						}
					elseif ($chosen_dir['id_group'] > 0)
						{
						$option_dir = " AND u.id_group=".$babDB->quote($chosen_dir['id_group'])." ";
						}
					else
						{
						$option_dir = " AND e.id_directory =".$babDB->quote($id_directory)." ";
						}
					} else $option_dir = '';


				if ($arr_dir) {
					$db_arr_dir = "e.id_directory IN (".$babDB->quote($arr_dir).") OR ";
				}
				else
					{
					$db_arr_dir = '';
					}
					
					
				// $registered_directory
				

				$req = "SELECT 
					e.id 
				FROM `".BAB_DBDIR_ENTRIES_TBL."` e
				LEFT JOIN 
						".BAB_DBDIR_ENTRIES_EXTRA_TBL." t 
						ON t.id_entry = e.id";
				if( count($arr_grp) && in_array(BAB_REGISTERED_GROUP, $arr_grp) && ( empty($id_directory) || isset($registered_directory)))
					{
					$req .= " LEFT JOIN ".BAB_USERS_TBL." dis ON dis.id = e.id_user AND dis.disabled='0' ";
					}
				else
					{
					$req .= " LEFT JOIN ".BAB_USERS_GROUPS_TBL." u ON u.id_object = e.id_user AND u.id_group IN  (".$babDB->quote($arr_grp).") LEFT JOIN ".BAB_USERS_TBL." dis ON dis.id = u.id_object AND dis.disabled='0' ";
					}
					
				$req .= " WHERE 
				".$crit_fields." 
				".$crit_fields_add_str." 
					(
						".$db_arr_dir." 
						(e.id_directory = '0' AND dis.id IS NOT NULL )
					) ".$option_dir." 
				GROUP BY e.id ";

				//print_r($req);

				$this->countdirfields = count($this->dirfields['name']);

				$res = $babDB->db_query($req);
				$nbrows = $babDB->db_num_rows($res);

				$navpos = $this->navpos;
				if ($navitem != "g") $navpos = 0;
				$this->navbar_g = navbar($babLimit,$nbrows,"g",$navpos);
				$tmp = explode(" ",$order);
				if (in_array("title",$tmp)) $order_tmp = "order by sn ASC, givenname ASC";
				else $order_tmp = "order by ".$babDB->db_escape_string($order);

				$req .= " ".$order_tmp." LIMIT ".$navpos.", ".$babLimit;
				$this->resdir = $babDB->db_query($req);
				$this->countdir = $babDB->db_num_rows($this->resdir);

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
					$crit_date = " ce.start_date >= '".$babDB->db_escape_string($this->fields['after'])." 00:00:00'";
					}
				if (isset($this->fields['before']) && trim($this->fields['before']) != "")
					{
					if (!empty($crit_date))
						$crit_date .= " and ";
					$crit_date .= "ce.end_date <= '".$babDB->db_escape_string($this->fields['before'])." 23:59:59'";
					}
				if (isset($this->fields['h_calendar']) && trim($this->fields['h_calendar']) != "")
					{
					$select_idcal = " and ceo.id_cal = '".$babDB->db_escape_string($this->fields['h_calendar'])."'";
					}


				$req = "create temporary table ageresults 
				select 
					ceo.id_cal owner, 
					ce.id id, 
					ce.title, 
					ce.description, 
					ce.location, 
					ce.start_date, 
					ce.end_date, 
					ceo.id_cal id_cal, 
					cct.name categorie, 
					cct.description catdesc, 
					ce.bprivate 
				from 
					".BAB_CAL_EVENTS_OWNERS_TBL." ceo, 
					".BAB_CAL_EVENTS_TBL." ce, 
					".BAB_CAL_CATEGORIES_TBL." cct 
				where 0";
				$babDB->db_query($req);
				
				$list_id_cal = array();
				$tmp =  array_merge(getAvailableUsersCalendars(),getAvailableGroupsCalendars(),getAvailableResourcesCalendars());
				foreach ($tmp as $arr)
					$list_id_cal[] = $arr['idcal'];
				
				if ($this->like || $this->like2)
					{
					$reqsup = "(ceo.id_cal=".$babDB->quote(bab_getPersonalCalendar($GLOBALS['BAB_SESS_USERID']))." OR ce.bprivate='N') AND (".
						finder($this->like,"ce.title",$option,$this->like2)." or ".
						finder($this->like,"ce.description",$option,$this->like2)." or ".
						finder($this->like,"cct.description",$option,$this->like2)." or ".
						finder($this->like,"cct.name",$option,$this->like2)." or ".
						finder($this->like,"ce.location",$option,$this->like2)
						.")";
					}
				else 
					{
					$reqsup = "";
					}

				if (count($list_id_cal) > 0) 
					{
					if (!empty($reqsup))
						{
						$reqsup .= ' and ';
						}
					if (!empty($crit_date))
						$crit_date .= ' and ';

					$req = "insert into ageresults 
					select 
						ceo.id_cal owner, 
						ce.id id, ce.title, 
						ce.description, 
						ce.location, 
						ce.start_date, 
						ce.end_date, 
						ceo.id_cal id_cal, 
						cct.name categorie, 
						cct.description catdesc, 
						ce.bprivate 
					from 
						".BAB_CAL_EVENTS_OWNERS_TBL." ceo 
						left join ".BAB_CAL_EVENTS_TBL." ce on ceo.id_event=ce.id 
						left join ".BAB_CAL_CATEGORIES_TBL." cct on cct.id=ce.id_cat 
					where 
						".$reqsup." ".$crit_date." 
						ceo.id_cal in(".$babDB->quote($list_id_cal).") ".$select_idcal." order by ce.start_date";
					$babDB->db_query($req);
					}

				$req = "select count(*) from ageresults";
				$res = $babDB->db_query($req);
				list($nbrows) = $babDB->db_fetch_row($res);
				$navpos = $this->navpos;
				if ($navitem != "h") $navpos = 0;
				$this->navbar_h = navbar($babLimit,$nbrows,"h",$navpos);

				$req = "select * from ageresults limit ".$navpos.", ".$babLimit;
				$this->resage = $babDB->db_query($req);
				$this->countage = $babDB->db_num_rows($this->resage);
				if( !$this->counttot && $this->countage > 0 )
					$this->counttot = true;
				$this->nbresult += $nbrows;
				}
				
			// --------------------------------------------- ADDONS
			
			$nbrows = 0;
			$this->addons = new bab_addonsSearch;
			$this->addonSearchArray = $this->addons->getsearcharray($item);
			$this->countaddons = count($this->addonSearchArray);
			$this->addons->setSearchParam($this->like2, $this->like, $option, $babLimit);

			$this->addonsdata = array();
			$first_addon_searchresults = array();

			if (is_array($this->addonSearchArray))
				while (list($addon_id,$addon_title) = each($this->addonSearchArray))
					{
					if (isset($addon_id) && is_numeric($addon_id))
						{
						$navpos = $this->navitem == 'as-'.$addon_id ? $this->navpos : 0;
						$this->addons->pos[$addon_id] = $navpos;

						$first_addon_searchresults[$addon_id] = $this->addons->callSearchFunction($addon_id);
						$nbrows = $first_addon_searchresults[$addon_id][1];
						
						$navbar_i = navbar($GLOBALS['babLimit'],$nbrows,'as-'.$addon_id,$navpos);
						if ($nbrows > 0)
							{
							$this->addonsdata[] = array($addon_id, $addon_title, $navbar_i, $first_addon_searchresults);
							$this->nbresult += $nbrows;
							}
						}
					}

			

			if( !$this->counttot && count($this->addonsdata) > 0 )
					$this->counttot = true;

			
			// end

			if( !$this->counttot)
				{
				$babBody->msgerror = bab_translate("Search result empty");
				}

			}

		function tmptable_inserted_id($tablename)
			{
			global $babDB;
			$res = $babDB->db_query("SELECT id FROM ".$babDB->db_escape_string($tablename));
			$this->tmp_inserted_id = array();
			while ($arr = $babDB->db_fetch_assoc($res))
				{
				$this->tmp_inserted_id[] = $arr['id'];
				}
			}

		function dateformat($time)
			{
			return bab_shortDate($time, true);
			}

		function searchInAllCols($table)
			{
			global $babDB;

			if (!$this->like && !$this->like2)
				{
				return '';
				}


			$res = $babDB->db_query("DESCRIBE ".$babDB->db_escape_string($table));
			$like = "(";
			while (list($colname) = $babDB->db_fetch_array($res))
				{
				if ($colname != 'id' && $colname != 'photo_data' && $colname != 'photo_type')
					{
						if ($like != "(") {
							$like .= " or ";
						}
						
						$like .= finder($this->like, 'e.'.$colname, $this->option, $this->like2);
					}
				}
			$like .= ") ";

			return $like;
			}

		

		function getnextart()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countart)
				{
				$arr = $babDB->db_fetch_array($this->resart);
				$this->article = put_text($arr['title']);
				$this->artdate = bab_shortDate($arr['date'], true);
				$this->artauthor = empty($arr['author']) ? bab_translate("Anonymous") : bab_toHtml($arr['author']);
				$this->archive = 'Y' == $arr['archive'];
				$this->arttopic = returnCategoriesHierarchy($arr['id_topic']);
				$this->arttopicid = $arr['id_topic'];
				$this->articleurlpop = bab_toHtml($GLOBALS['babUrlScript']."?tg=search&idx=a&id=".$arr['id']."&w=".$this->what);
				if (strlen(trim(stripslashes($arr['body']))) > 0)
					{
					$this->articleurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$arr['id_topic']."&article=".$arr['id']);
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
					$this->articleurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=".$urlidx."&topics=".$arr['id_topic']."#art".$arr['id']);
					}
				$this->authormail = isset($arr['email']) ? bab_toHtml($arr['email']) : '';
				$this->intro = put_text($arr['head'],300);
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists artresults";
				$babDB->db_query($req);
				return false;
				}
			}

		function getnextcom()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countcom)
				{
				$arr = $babDB->db_fetch_array($this->rescom);
				$this->artdate = bab_toHtml(bab_shortDate($arr['date'], true));
				if( $arr['id_author'] )
					{
					$this->artauthor = bab_toHtml(bab_getUserName($arr['id_author']));
					$this->authormail = bab_toHtml(bab_getUserEmail($arr['id_author']));
					}
				else
					{
					$this->artauthor = bab_toHtml($arr['name']);
					$this->authormail = bab_toHtml($arr['email']);
					}
				$this->arttopic = returnCategoriesHierarchy($arr['id_topic']);
				$this->article = bab_toHtml($arr['arttitle']);
				$this->arttopicid = $arr['id_topic'];
				$this->com = bab_toHtml($arr['subject']);
				if (strlen(trim(stripslashes($arr['body']))) > 0)
					$this->urlok = true;
				else
					$this->urlok = false;
				$this->articleurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=articles&idx=More&topics=".$arr['id_topic']."&article=".$arr['id_article']);
				$this->articleurlpop = bab_toHtml($GLOBALS['babUrlScript']."?tg=search&idx=a&id=".$arr['id_article']."&w=".$this->what);
				$this->comurl = $this->articleurl;
				$this->comurlpop = bab_toHtml($GLOBALS['babUrlScript']."?tg=search&idx=ac&idt=".$arr['id_topic']."&ida=".$arr['id_article']."&idc=".$arr['id']."&w=".$this->what);
				$this->intro = put_text($arr['message'],300);
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists comresults";
				$babDB->db_query($req);
				return false;
				}
			}

		function getnextfor()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countfor)
				{
				$arr = $babDB->db_fetch_array($this->resfor);
				$this->post = bab_toHtml($arr['title']);
				$this->postauthor = bab_toHtml($arr['author']);
				$this->postdate = bab_toHtml(bab_shortDate($arr['date'], true));
				$this->forum = bab_toHtml($arr['topic']);
				$this->forumurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=threads&forum=".$arr['id_topic']);
				$this->intro = put_text($arr['message'],300);
				$this->posturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=posts&idx=List&forum=".$arr['id_topic']."&thread=".$arr['id_thread']."&post=".$arr['id']."&flat=0");
				$this->posturlpop = bab_toHtml($GLOBALS['babUrlScript']."?tg=search&idx=b&idt=".$arr['id_thread']."&idp=".$arr['id']."&w=".$this->what);
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists forresults";
				$babDB->db_query($req);
				return false;
				}
			}
		function getnextfaq()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countfaq)
				{
				$arr = $babDB->db_fetch_array($this->resfaq);
				$this->question = bab_toHtml($arr['title']);
				$this->faqtopic = bab_toHtml($arr['topic']);
				$this->faqtopicid = $arr['idcat'];
				$this->topicurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=faq&idx=questions&item=".$arr['idcat']);
				$this->questionurlpop = bab_toHtml($GLOBALS['babUrlScript']."?tg=search&idx=c&idc=".$arr['idcat']."&idq=".$arr['id']."&w=".urlencode($this->what));
				$this->questionurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=faq&idx=viewq&item=".$arr['idcat']."&idq=".$arr['id']);
				$this->intro = put_text($arr['response'],300);
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists faqresults";
				$babDB->db_query($req);
				return false;
				}
			}

		function getnextfil()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countfil)
				{
				$this->altbg = !$this->altbg;
				$arr = $babDB->db_fetch_array($this->resfil);
				$this->file = bab_toHtml($arr['title']);
				$this->update = bab_toHtml(bab_shortDate($arr['datem'], true));
				$this->created = bab_toHtml(bab_shortDate($arr['datec'], true));
                $this->artauthor = bab_toHtml($arr['author']);
				$this->filedesc = bab_toHtml($arr['description']);
				$this->path = bab_toHtml($arr['path']);
				
				if ($arr['bgroup'] == 'N')
					$this->arttopic = bab_translate("Private folder")."/".bab_toHtml($arr['path']);
				else
					$this->arttopic = bab_toHtml($arr['path']);

				$this->arttopicid = $arr['id_owner'];
				$this->bgroup = $arr['bgroup'];
				$this->filedesc = bab_toHtml($arr['description']);
				$this->fileurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=search&idx=e&id=".$arr['id']."&w=".$this->what);
				$this->type_match = bab_toHtml($arr['type_match']);
				if (0 != $arr['version_count']) {
					$this->version_count = sprintf(bab_translate('There are matches in %d older versions'),$arr['version_count']);
				} else {
					$this->version_count = false;
				}
				
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists filresults";
				$babDB->db_query($req);
				return false;
				}
			}

		function getnextcon()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countcon)
				{
				$arr = $babDB->db_fetch_array($this->rescon);
				$arr['firstname'] = isset($arr['firstname']) ? $arr['firstname']: '';
				$arr['lastname'] = isset($arr['lastname']) ? $arr['lastname']: '';
				$this->fullname = bab_toHtml(bab_composeUserName( $arr['firstname'], $arr['lastname']));
				$this->confirstname = bab_toHtml($arr['firstname']);
				$this->conlastname = bab_toHtml($arr['title']);
				$this->conemail = bab_toHtml($arr['email']);
				$this->concompany = bab_toHtml($arr['compagny']);
				$this->fullnameurl = $GLOBALS['babUrlScript']."?tg=search&idx=f&id=".$arr['id']."&w=".$this->what;
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists conresults";
				$babDB->db_query($req);
				return false;
				}
			}

		function getnextnot()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countnot)
				{
				$arr = $babDB->db_fetch_array($this->resnot);
				$this->content = put_text($arr['content'],400);
				$this->notauthor = $GLOBALS['BAB_SESS_USER'];
				$this->notauthormail = bab_toHtml(bab_getUserEmail($GLOBALS['BAB_SESS_USERID']));
                $this->notdate = bab_toHtml(bab_shortDate($arr['date'], true));
				$this->read_more = bab_translate("Edit");
				$this->noteurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=note&idx=Modify&item=".$arr['id']);
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
						$this->dirvalue = isset($this->dir[$this->name]) ? bab_toHtml($this->dir[$this->name])  : '';
						$this->dirurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=search&idx=g&id=".$this->dir['id']."&w=".$this->what);
						$this->popup = true;
						break;
					case 'givenname':
						$this->dirvalue = isset($this->dir[$this->name]) ? bab_toHtml($this->dir[$this->name])  : '';
						$this->dirurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=search&idx=g&id=".$this->dir['id']."&w=".$this->what);
						$this->popup = true;
						break;
					case 'mfunction':
						$this->dirvalue = isset($this->dir[$this->name]) ? bab_toHtml($this->dir[$this->name])  : '';
						$this->dirurl = 'mailto:'.$this->dirvalue;
						$this->popup = false;
						break;
					default:
						$this->dirvalue = isset($this->dir[$this->name]) ? bab_toHtml($this->dir[$this->name])  : '';
						$this->dirurl = false;
						$this->popup = false;
						break;
					}

				$this->alloworder = 0 !== strpos($this->name,'babdirf');

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
			global $babDB;
			static $i = 0;
			if( $i < $this->countdir)
				{
				list($id) = $babDB->db_fetch_array($this->resdir);
				$this->dir = $babDB->db_fetch_array($babDB->db_query("select * from ".BAB_DBDIR_ENTRIES_TBL." where id=".$babDB->quote($id).""));

				$res = $babDB->db_query("select * from ".BAB_DBDIR_ENTRIES_EXTRA_TBL." where id_entry=".$babDB->quote($id)."");
				while( $arr = $babDB->db_fetch_array($res))
					{
					$this->dir['babdirf'.$arr['id_fieldx']] = $arr['field_value'];
					}

				
				$i++;
				return true;
				}
			else
				{
				return false;
				}
			}

		function getnextage()
			{
			global $babDB,$babBody;
			static $i = 0;
			if( $i < $this->countage)
				{
				$arr = $babDB->db_fetch_array($this->resage);
				$this->agetitle = bab_toHtml($arr['title']);
				$this->agedescription = put_text($arr['description'],400);
				$this->agestart_date = $this->dateformat(bab_mktime($arr['start_date']));
				$this->ageend_date = $this->dateformat(bab_mktime($arr['end_date']));
				$iarr = $babBody->icalendars->getCalendarInfo($arr['id_cal']);
				$this->agecreator = $iarr['name'];
				$this->private = $arr['bprivate'] == 'Y' && $arr['owner'] != bab_getPersonalCalendar($GLOBALS['BAB_SESS_USERID']);
				switch ($iarr['type'])
					{
					case BAB_CAL_USER_TYPE:
						$this->agecreatormail = bab_toHtml(bab_getUserEmail($iarr['idowner']));
						break;
					case BAB_CAL_PUB_TYPE:
					case BAB_CAL_RES_TYPE:
						$this->agecreatormail = "";
						break;
					}
				$this->agecat = bab_toHtml($arr['categorie']);
				$this->agecatdesc = bab_toHtml($arr['catdesc']);
				$this->ageurl = bab_toHtml($GLOBALS['babUrlScript']."?tg=calendar&idx=vevent&evtid=".$arr['id']."&idcal=".$arr['id_cal']);
				$i++;
				return true;
				}
			else
				{
				$req = "drop table if exists ageresults";
				$babDB->db_query($req);
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
					
				// this text is given by the addon as a html string.
				$this->text = isset($addon_searchresults[0]) ? $addon_searchresults[0]  : '';
				
				list( $this->url, $this->urltxt ) = isset($addon_searchresults[2]) && is_array($addon_searchresults[2]) && !empty($addon_searchresults[2][0]) && !empty($addon_searchresults[2][1]) ? $addon_searchresults[2] : array(false,false);
				
				$this->url = bab_toHtml($this->url);
				$this->urltxt = bab_toHtml($this->urltxt);
				
				list( $this->urlpopup, $this->urlpopuptxt ) = isset($addon_searchresults[3]) && is_array($addon_searchresults[3]) && !empty($addon_searchresults[3][0]) && !empty($addon_searchresults[3][1]) ? $addon_searchresults[3] : array(false,false);
				
				$this->urlpopup = bab_toHtml($this->urlpopup);
				$this->urlpopuptxt = bab_toHtml($this->urlpopuptxt);
				
				} 
			return isset($addon_searchresults) && is_array($addon_searchresults) ? true : false;
			}
		}

	$temp = new temp($item, $what, $order, $option,$navitem,$navpos);
	$babBody->babecho(	bab_printTemplate($temp,"search.html", "searchresult"));
	}


class bab_searchVisuPopup
	{
	function bab_searchVisuPopup()
		{
		include_once $GLOBALS['babInstallPath']."utilit/uiutil.php";

		$GLOBALS['babBodyPopup'] = new babBodyPopup();
		}
		

	function printHTML($file,$tpl)
		{
		$GLOBALS['babBodyPopup']->title = $GLOBALS['babBody']->title;
		$GLOBALS['babBodyPopup']->msgerror = $GLOBALS['babBody']->msgerror;
		$GLOBALS['babBodyPopup']->babecho(bab_printTemplate($this, $file, $tpl));
		printBabBodyPopup();
		die();
		}
	}

function viewArticle($article,$w)
	{
	global $babBody;

	class temp extends bab_searchVisuPopup
		{
	
		var $content;
		var $head;
		var $arr = array();
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
			global $babDB;
			
			$this->bab_searchVisuPopup();
			$this->close = bab_translate("Close");
			$this->attachmentxt = bab_translate("Associated documents");
			$this->commentstxt = bab_translate("Comments");
			$this->t_name = bab_translate("Name");
			$this->t_description = bab_translate("Description");
			$this->t_index = bab_translate("Result in file");
			$this->tags_txt = bab_translate("Keywords of the thesaurus");
			$req = "select * from ".BAB_ARTICLES_TBL." where id=".$babDB->quote($article);
			$this->res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_array($this->res);
			$this->title = bab_toHtml($this->arr['title']);
			$this->countf = 0;
			$this->countcom = 0;
			$this->w = $w;
			if( bab_isAccessValid(BAB_TOPICSVIEW_GROUPS_TBL, $this->arr['id_topic']) && bab_articleAccessByRestriction($this->arr['restriction']))
				{
				$GLOBALS['babWebStat']->addArticle($this->arr['id']);
				
				include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
				
				$editor = new bab_contentEditor('bab_article_head');
				$editor->setContent($this->arr['body']);
				
				$this->content = highlightWord($w, $editor->getHtml());
				
				$editor = new bab_contentEditor('bab_article_body');
				$editor->setContent($this->arr['head']);
				
				$this->head = highlightWord($w, $editor->getHtml());

				$this->resf = $babDB->db_query("
					
					SELECT f.*, i.file_path FROM  
						".BAB_ART_FILES_TBL." f
						LEFT JOIN ".BAB_INDEX_ACCESS_TBL." i ON i.id_object = f.id
					WHERE id_article=".$babDB->quote($article)." 
					 GROUP BY f.id
				");

				$this->countf = $babDB->db_num_rows($this->resf);

				$this->found_in_index = array();
				
				if( $this->countf > 0 )
					{
					$this->battachments = true;
						if (bab_searchEngineInfos()) {
							$found_files = bab_searchIndexedFiles($this->w, false, false, 'bab_art_files');
							bab_debug($found_files);
							
							foreach($found_files as $arr) {
								$this->found_in_index[bab_removeUploadPath($arr['file'])] = 1;
							}
						}
					}
				else
					{
					$this->battachments = false;
					}

				$this->rescom = $babDB->db_query("select * from ".BAB_COMMENTS_TBL." where id_article=".$babDB->quote($article)." and confirmed='Y' order by date desc");
				$this->countcom = $babDB->db_num_rows($this->rescom);

				$this->restags = $babDB->db_query("select tag_name from ".BAB_TAGS_TBL." tt left join ".BAB_ART_TAGS_TBL." att on tt.id=att.id_tag where id_art=".$babDB->quote($article)."");
				$this->counttags = $babDB->db_num_rows($this->restags);
				}
			else
				{
				$this->content = "";
				$this->head = bab_translate("Access denied");
				}
			}

		function getnextdoc()
			{
			global $babDB, $arrtop;
			static $i = 0;
			if( $i < $this->countf)
				{
				$arr = $babDB->db_fetch_array($this->resf);
				$this->docurl = $GLOBALS['babUrlScript']."?tg=articles&idx=getf&topics=".$this->arr['id_topic']."&article=".$this->arr['id']."&idf=".$arr['id'];
				$this->docname = highlightWord($this->w, bab_toHtml($arr['name']));
				$this->docdescription = highlightWord($this->w, bab_toHtml($arr['description']));
				$this->in_index = isset($this->found_in_index['articles/'.$this->arr['id'].','.$arr['name']]);
				$this->altbg = !$this->altbg;
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
			global $babDB;
			static $i = 0;
			if( $i < $this->countcom)
				{
				$arr = $babDB->db_fetch_array($this->rescom);
				$this->altbg = !$this->altbg;
				$this->commentdate = bab_toHtml(bab_strftime(bab_mktime($arr['date'])));
				$this->authorname = highlightWord($this->w,bab_toHtml($arr['name']));
				$this->commenttitle = highlightWord($this->w,bab_toHtml($arr['subject']));
				
				$editor = new bab_contentEditor('bab_article_comment');
				$editor->setContent($arr['message']);
				$this->commentbody = highlightWord($this->w,$editor->getHtml());
				
				$i++;
				return true;
				}
			else
				{
				$babDB->db_data_seek($this->rescom,0);
				$i=0;
				return false;
				}
			}
		function getnexttag()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->counttags)
				{
				$arr = $babDB->db_fetch_array($this->restags);
				$this->tagname = bab_toHtml($arr['tag_name']);
				$i++;
				return true;
				}
			else
				{
				$i=0;
				return false;
				}
			}
		}
	
	$temp = new temp($article,$w);
	$temp->printHTML("search.html", "viewart");
	}

function viewComment($topics, $article, $com, $w)
	{
	global $babBody;
	
	class ctp extends bab_searchVisuPopup
		{
		var $subject;
		var $add;
		var $topics;
		var $article;
		var $arr = array();
		var $babCss;

		function ctp($topics, $article, $com, $w)
			{
			global $babDB;
			
			$this->bab_searchVisuPopup();
			$this->title = bab_toHtml(bab_getArticleTitle($article));
			$this->subject = bab_translate("Subject");
			$this->by = bab_translate("By");
			$this->date = bab_translate("Date");
			$this->topics = $topics;
			$this->article = $article;
			$req = "select * from ".BAB_COMMENTS_TBL." where id=".$babDB->quote($com);
			$res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_array($res);
			$this->arr['date'] = bab_toHtml(bab_strftime(bab_mktime($this->arr['date'])));
			$this->arr['subject'] = highlightWord( $w, bab_toHtml($this->arr['subject']));

			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			$editor = new bab_contentEditor('bab_article_comment');
			$editor->setContent($this->arr['message']);
			$this->arr['message'] = highlightWord( $w, $editor->getHtml());
			}
		}

	$ctp = new ctp($topics, $article, $com, $w);
	$ctp->printHTML("search.html", "viewcom");
	}

function viewPost($thread, $post, $w)
	{
	global $babBody;

	class temp extends bab_searchVisuPopup
		{
	
		var $postmessage;
		var $postsubject;
		var $postdate;
		var $postauthor;
		var $title;
		var $babCss;

		function temp($thread, $post, $w)
			{
			global $babDB;
			
			$post = (int) $post;
			$this->bab_searchVisuPopup();
			$req = "select forum from ".BAB_THREADS_TBL." where id=".$babDB->quote($thread);
			$arr = $babDB->db_fetch_array($babDB->db_query($req));
			
			$this->t_files = bab_translate("Dependent files");
			$this->t_found_in_index = bab_translate("Result in file");
			$this->files = bab_getPostFiles($arr['forum'], $post);
			
			$GLOBALS['babBody']->title = bab_toHtml(bab_getForumName($arr['forum']));
			$req = "select * from ".BAB_POSTS_TBL." where id=".$babDB->quote($post);
			$arr = $babDB->db_fetch_array($babDB->db_query($req));
			$this->postdate = bab_toHtml(bab_strftime(bab_mktime($arr['date'])));
			$this->postauthor = bab_toHtml($arr['author']);
			$this->postsubject = highlightWord( $w, bab_toHtml($arr['subject']));
			
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			$editor = new bab_contentEditor('bab_forum_post');
			$editor->setContent($this->arr['message']);
			$this->postmessage = highlightWord( $w, $editor->getHtml());

			if ($this->files && bab_searchEngineInfos()) {
				$found_files = bab_searchIndexedFiles($w, false, false, 'bab_forumsfiles');
				
				foreach($found_files as $arr) {
					$this->found_in_index[bab_removeUploadPath($arr['file'])] = 1;
				}
			}
			}
		
		function getnextfile()
			{
			if ($file = current($this->files))
				{
				
				$this->url = bab_toHtml($file['url']);
				$this->name = bab_toHtml($file['name']);
				$this->size = bab_toHtml($file['size']);
				
				
				next($this->files);
				$this->in_index = isset($this->found_in_index['forums/'.basename($file['path'])]);
				return true;
				}
			else
				return false;
			}
		}
	
	$temp = new temp($thread, $post, $w);
	$temp->printHTML("search.html", "viewfor");
	}

function viewQuestion($idcat, $id, $w)
	{
	global $babBody;
	class temp extends bab_searchVisuPopup
		{
		var $arr = array();
		var $res;
		var $babCss;

		function temp($idcat, $id, $w)
			{
			global $babDB;
			$this->bab_searchVisuPopup();
			$req = "select * from ".BAB_FAQQR_TBL." where id=".$babDB->quote($id);
			$this->res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_array($this->res);
			$this->arr['question'] = highlightWord( $w, bab_toHtml($this->arr['question']));
			
			include_once $GLOBALS['babInstallPath']."utilit/editorincl.php";
			$editor = new bab_contentEditor('bab_faq_response');
			$editor->setContent($this->arr['response']);
			$this->arr['response'] = highlightWord( $w, $editor->getHtml());
			
			$req = "select category from ".BAB_FAQCAT_TBL." where id=".$babDB->quote($idcat);
			$a = $babDB->db_fetch_array($babDB->db_query($req));
			$this->title = highlightWord( $w,  bab_toHtml($a['category']));
			}
			
		}

	$temp = new temp($idcat, $id, $w);
	$temp->printHTML("search.html", "viewfaq");
	}

function viewFile($id, $w)
	{
	global $babBody;
	class temp extends bab_searchVisuPopup
		{
		var $arr = array();
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
		var $altbg = true;

		function temp($id, $w)
			{
			global $babDB;
			$this->bab_searchVisuPopup();
			$this->description = bab_translate("Description");
			$this->keywords = bab_translate("Keywords");
			$this->modifiedtxt = bab_translate("Modified");
			$this->createdtxt = bab_translate("Created");
			$this->postedbytxt = bab_translate("Posted by");
			$this->modifiedbytxt = bab_translate("Modified by");
			$this->download = bab_translate("Download");
			$this->sizetxt = bab_translate("Size");
			$this->pathtxt = bab_translate("Path");
			$this->t_name = bab_translate("Older versions");
			$this->t_versiondate = bab_translate("Date");
			$this->t_index = bab_translate("Result in file");

			$this->babCss = bab_printTemplate($this,"config.html", "babCss");
			$req = "select * from ".BAB_FILES_TBL." where id=".$babDB->quote($id)." and state='' and confirmed='Y'";
			$this->res = $babDB->db_query($req);
			$this->arr = $babDB->db_fetch_array($this->res);
			$access = bab_isAccessFileValid($this->arr['bgroup'], $this->arr['id_owner']);
			if( $access )
				{
				$GLOBALS['babBody']->title = bab_toHtml($this->arr['name']);
				$this->arr['description'] = highlightWord( $w, bab_toHtml($this->arr['description']));
				$res = $babDB->db_query("select tag_name from ".BAB_TAGS_TBL." tt left join ".BAB_FILES_TAGS_TBL." ftt on tt.id=ftt.id_tag where id_file=".$babDB->quote($id)." order by tag_name asc");
				$this->arr['keywords'] = '';
				while( $rr = $babDB->db_fetch_array($res))
					{
					$this->arr['keywords'] .= $rr['tag_name'].', ';
					}

				$this->arr['keywords'] = highlightWord( $w, bab_toHtml($this->arr['keywords']));
				$this->modified = bab_toHtml(bab_shortDate(bab_mktime($this->arr['modified']), true));
				$this->created = bab_toHtml(bab_shortDate(bab_mktime($this->arr['created']), true));
				$this->postedby = bab_toHtml(bab_getUserName($this->arr['author']));
				$this->modifiedby = bab_toHtml(bab_getUserName($this->arr['modifiedby']));
				
				$sPath = removeFirstPath($this->arr['path']);
				$iLength = strlen(trim($sPath));
				if($iLength && '/' === $sPath{$iLength - 1})
				{
					$sPath = substr($sPath, 0, -1);
				}
				
				$iid = $this->arr['id_owner'];
				
				$sUploadPath = BAB_FmFolderHelper::getUploadPath();
				if($this->arr['bgroup'] == "Y")
					{
					$fstat = stat($sUploadPath . $this->arr['path'] . $this->arr['name']);
					$oFmFolder = BAB_FmFolderSet::getRootCollectiveFolder($this->arr['path']);
					if(!is_null($oFmFolder))
						{
							$iid = $oFmFolder->getId();
						}
					}
				else
					{
					$fstat = stat($sUploadPath . $this->arr['path'] . $this->arr['name']);
					}
				$this->geturl = bab_toHtml($GLOBALS['babUrlScript']."?tg=fileman&idx=get&id=".$iid."&gr=".$this->arr['bgroup']."&path=".urlencode($sPath)."&file=".urlencode($this->arr['name']));
				$this->size = bab_formatSizeFile($fstat[7])." ".bab_translate("Kb");
				if( $this->arr['bgroup'] == "Y") {
					$this->rootpath = '';
					$this->resff = $babDB->db_query("select * from ".BAB_FM_FIELDS_TBL." where id_folder=".$babDB->quote($this->arr['id_owner']));
					$this->countff = $babDB->db_num_rows($this->resff);
					}
				else 
					{
					$this->rootpath = bab_translate("Private folder");
					$this->countff = 0;
					}
				$this->path = bab_toHtml($this->rootpath."/".$this->arr['path']);	

				$this->resversion = $babDB->db_query("
					SELECT 
						UNIX_TIMESTAMP(date) versiondate, 
						CONCAT(f.name,' ',v.ver_major,'.',v.ver_minor) name,
						a.file_path 
					FROM 
						".BAB_FILES_TBL." f,
						".BAB_FM_FILESVER_TBL." v,
						".BAB_INDEX_ACCESS_TBL." a 
					WHERE 
						f.id = v.id_file 
						AND v.id_file=".$babDB->quote($this->arr['id'])." 
						AND a.id_object = v.id 
						AND a.id_object_access = f.id_owner

					ORDER BY v.ver_major DESC,v.ver_minor DESC
					");

				$this->countversions = $babDB->db_num_rows($this->resversion);

				if (bab_searchEngineInfos()) {
						$found_files = bab_searchIndexedFiles(trim($w), false, false, 'bab_files');
						
						
						foreach($found_files as $arr) {
							$this->found_in_index[bab_removeUploadPath($arr['file'])] = 1;
						}
					}
				}
			else
				{
				$GLOBALS['babBody']->msgerror = bab_translate("Access denied");
				$this->arr['description'] = "";
				$this->arr['keywords'] = "";
				$this->created = "";
				$this->modifiedby = "";
				$this->modified = "";
				$this->postedby = "";
				$this->geturl = "";
				$this->countff = 0;
				$this->path ='';
				$this->size = '';
				}
			}

		function getnextfield()
			{
			global $babDB;
			static $i = 0;
			if( $i < $this->countff)
				{
				$arr = $babDB->db_fetch_array($this->resff);
				$this->field = bab_toHtml(bab_translate($arr['name']));
				$this->fieldval = '';
				$res = $babDB->db_query("select fvalue from ".BAB_FM_FIELDSVAL_TBL." where  id_field=".$babDB->quote($arr['id'])." and id_file=".$babDB->quote($this->arr['id']));
				if( $res && $babDB->db_num_rows($res) > 0)
					{
					list($this->fieldval) = $babDB->db_fetch_array($res);
					$this->fieldval = bab_toHtml($this->fieldval);
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


		function getnextversion() 
			{
			global $babDB;
			if ($arr = $babDB->db_fetch_assoc($this->resversion)) 
				{
				$this->altbg = !$this->altbg;
				$this->name = bab_toHtml($arr['name']);
				$this->versiondate = bab_toHtml(bab_longDate($arr['versiondate']));
				$this->in_index = isset($this->found_in_index[$arr['file_path']]);
				return true;
				}
			return false;
			}
	}

	$temp = new temp($id, $w);
	$temp->printHTML("search.html", "viewfil");
	return true;
	}


function viewContact($id, $what)
	{
	class temp extends bab_searchVisuPopup
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
			global $babDB, $BAB_SESS_USERID;
			
			$this->bab_searchVisuPopup();
			
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

			$req = "select * from ".BAB_CONTACTS_TBL." where id=".$babDB->quote($id);
			$arr = $babDB->db_fetch_array($babDB->db_query($req));
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
	$temp->printHTML("search.html", "viewcon");
	}

function viewDirectoryUser($id, $what)
{
	global $babBody, $babDB, $babInstallPath;
	list($idd, $idu) = $babDB->db_fetch_array($babDB->db_query("select id_directory, id_user from ".BAB_DBDIR_ENTRIES_TBL." where id=".$babDB->quote($id)));
	$access = false;
	if( $idd == 0 )
	{
		$res = $babDB->db_query("select id, id_group from ".BAB_DB_DIRECTORIES_TBL." where id_group != '0'");
		while( $row = $babDB->db_fetch_array($res))
			{
			$idd = $row['id'];
			list($bdir) = $babDB->db_fetch_array($babDB->db_query("select directory from ".BAB_GROUPS_TBL." where id=".$babDB->quote($row['id_group'])));
			if( $bdir == 'Y' && bab_isAccessValid(BAB_DBDIRVIEW_GROUPS_TBL, $row['id'])) 
				{
				if( $row['id_group'] == 1 )
					{
					$access = true;
					break;
				}
				
				$res2 = $babDB->db_query("select id from ".BAB_USERS_GROUPS_TBL." where id_object=".$babDB->quote($idu)." and id_group=".$babDB->quote($row['id_group']));
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
		summaryDbContactWithOvml(array('directoryid'=>$idd, 'userid'=>$id));
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
	case "browauthor":
		include_once $babInstallPath."utilit/lusersincl.php";
		if( !isset($pos)) { $pos = ''; }
		browseArticlesAuthors($pos, $cb);
		exit;
		break;
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
		searchKeyword($item, $option);
		$order = strtoupper(bab_rp($order, 'ASC'));
		if ($order !== 'ASC' && $order !== 'DESC')
		{
			$order = 'ASC';
		}

		// $fields contains a string of comma separated column identifiers
		// that should be used to order the list of results.
		$orderedFieldNames = explode(',', $field);
		for ($i = 0; $i < count($orderedFieldNames); $i++)
		{
			$orderedFieldNames[$i] = $babDB->backTick(trim($orderedFieldNames[$i])) . ' ' . $order;
		}
		$order = implode(', ', $orderedFieldNames);
		
		if( !isset($navitem)) { $navitem = '';}
		$GLOBALS['babWebStat']->addSearchWord($what);
		startSearch($item, $what, $order, $option, $navitem, $navpos);
		break;

	default:
		$babBody->title = bab_translate("Search");
		searchKeyword($item, $option);
		break;
	}
$babBody->setCurrentItemMenu($idx);
?>